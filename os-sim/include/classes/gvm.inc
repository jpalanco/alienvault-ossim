<?php
/**
* gvm.inc
*
* File gvm.inc is used to:
*   - To manage GVM protocol
*
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
* @package    ossim-framework\Vulnerabilities
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Gvm
{
    private $binary_location;

    private $ip;

    private $db;

    private $conn;

    private $error_message;

    /**
     * @codeCoverageIgnore
     */
    public static function factory($host = '') {
        return new Gvm($host);
    }

    public function __construct($host = '') {
        require_once __DIR__ . "/../ossim_db.inc";
        $this->db = new Ossim_db();
        $this->conn = $this->db->connect();

        $conf = $GLOBALS["CONF"];

        $this->binary_location = $conf->get_conf("gvm_path", FALSE);  // ** to use standard input **

        if($host=="") {
            $this->ip = $conf->get_conf("gvm_host", FALSE);
        }
        else {
            $this->ip = $host;
        }

        $this->host_id = Av_sensor::get_id_by_ip($this->conn, $this->ip);

        $this->error_message = "";
    }


    function __destruct(){
        $this->db->close();
    }


    /*****************************************************
     *
     * Open VAS user-related methods.
     *  - get_error_msg
     *
     *****************************************************/

    public function get_error_msg(){
        return $this->error_message;
    }


    public function execute_command ($command) {

        $ip = $this->ip;

        $alienvault_conn = Alienvault_conn::factory();
        $provider_registry = Provider_registry::factory();
        $client = Alienvault_client::factory($alienvault_conn, $provider_registry);

        $response = $client->gvm()->execute_gvm_command($ip, $command);
        $response = @json_decode($response, TRUE);

        $data = html_entity_decode($response["data"]["command_return"]);
        $status = $response["status"];

        if ($status == 'error') {
            $this->error_message = $response["message"];
            return false;
        } else {
            $xml = @simplexml_load_string($data);
            if (!is_object($xml)) {
                $this->error_message = trim($data);
                return FALSE;
            }
            else if (!preg_match("/^20.*/",$xml->attributes()->status)) {
                $this->error_message = trim($xml->attributes()->status_text);
                return FALSE;
            }

            return $xml;
        }
    }


    public function get_config_id_by_sid($sid) {

        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $this->conn->execute("SELECT sensor_gvm_config_id FROM vuln_nessus_settings_sensor WHERE vns_id=? and sensor_id=UNHEX(?)", [$sid, $this->host_id]);
        if ($result === FALSE)
        {
            return "";
        }

        return $result->fields["sensor_gvm_config_id"];
    }

    // ------  protected and private functions ------

    protected function delete_tasks_by_config_id($config) {
        $delete_tasks = array();
        $xml = $this->execute_command("<get_tasks />");

        if ($xml !== FALSE) {
            foreach ($xml->task as $task) { // save tasks with config_id = $config
                if($config == (string)$task->config->attributes()->id) {    $delete_tasks[]= (string)$task->attributes()->id;    }
            }
            foreach ($delete_tasks as $dtask) {
                if( $this->execute_command("<delete_task task_id='$dtask' />") === FALSE) {
                    return FALSE;
                }
            }

            $this->execute_command("<empty_trashcan />");

            return TRUE;
        }
        else {
            return FALSE;
        }
    }


    private function get_allowed_users() {

        $users    = Session::get_users_to_assign($this->conn);

        $entities = (Session::is_pro()) ? Acl::get_entities_to_assign($this->conn) : array();

        $allowed_users_entities = array();

        foreach ($users as $user)
            $allowed_users_entities[] = $user->get_login();

        foreach ($entities as $entity_id => $data)
            $allowed_users_entities[] = $entity_id;

        return $allowed_users_entities;
    }


    // ------  public functions ------

    public function get_config_id_by_name_comment($name, $comment="") {


        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $query = "
          SELECT sensor_gvm_config_id
          FROM vuln_nessus_settings_sensor vnss
            INNER JOIN vuln_nessus_settings vns ON  vnss.vns_id = vns.id
          WHERE vns.name=? and sensor_id=UNHEX(?)";
        $params = [$name, $this->host_id];

        if( $comment !== "" ) {
            $query .= " and vns.owner = ?";
            $params[] = $comment;
        }

        $result = $this->conn->Execute($query, $params);

        if ($result === FALSE)
        {
            return array ("message" => $this->conn->ErrorMsg(), "status" => "error");
        }

        return $result->fields["sensor_gvm_config_id"];

    }


    public function set_plugins_by_family ($sid, $fam = "") {

        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $id_config = $this->get_config_id_by_sid($sid);

        if ($id_config == "") {
            $res["status"]  = "error";
            $res["message"] = "[set_plugins_by_family] "._("Profile not found!!");
            return $res;
        }

        # to be able to modify config shouldn't have related tasks
        if ($this->delete_tasks_by_config_id($id_config) === FALSE) {
            $res["status"]  = "error";
            $res["message"] = "[set_plugins_by_family] "._("Couldn't delete the profile tasks!");
            return $res;
        }

         // Select families with the enabled an disabled plugins
        $params = [$sid];
        $query= "SELECT family as fid, vnf.name AS family_name,
                        SUM(if (vnsp.enabled='N',1,0)) AS plugins_disabled, SUM(if (vnsp.enabled='Y',1,0)) AS plugins_enabled
               FROM vuln_nessus_settings_plugins vnsp
                    INNER JOIN vuln_nessus_family vnf ON  vnsp.family=vnf.id
               WHERE vnsp.sid = ? ";

        if ( $fam != "" ){
            $query .= " AND vnf.id=?";
            $params[] = $fam;
        }

        $query .= " GROUP BY family, vnsp.sid";
        $result = $this->conn->Execute($query, $params);

        $command_start = "<modify_config config_id='$id_config'>";
        $command_end = "</modify_config>";

        $command_group_start = "<commands>";
        $command_group_end = "</commands>";

        $command = "";
        #with growing=0 no new family will be include
        $command_family_selection = "<family_selection><growing>0</growing>";
        $enable_whole_family = False;
        while (!$result->EOF) {
            $fname = $result->fields['family_name'];

            //disabling the family
            if ($result->fields['plugins_enabled'] == 0) {
                $command .= $command_start."<nvt_selection><family>$fname</family></nvt_selection>".$command_end;
            }else{
                //if family doesn't have any disabled plugins we can enable the whole family
                if ($result->fields['plugins_disabled'] == 0) {
                    $enable_whole_family= True;
                    #with growing=0 we're telling that if there is something new (family/nvt) gvm doesn't nothing and we will handle it manually later in this script
                    $command_family_selection .= "<family><name>$fname</name><all>1</all><growing>0</growing></family>";
                } else {
                    //if family has enabled and disabled plugins theses should enable one by one
                    $command .= $command_start."<nvt_selection><family>$fname</family>";
                    $query = "SELECT  p.oid
                           FROM vuln_nessus_settings_plugins AS sp
                                LEFT JOIN vuln_nessus_plugins AS p ON sp.id = p.id
                           WHERE sp.enabled =  'Y'
                           AND sp.sid =  ? AND sp.family=?";
                    $params = [$sid, $result->fields['fid']];
                    $results_nvt = $this->conn->Execute($query, $params);
                    while (!$results_nvt->EOF) {
                        $command .= "<nvt oid=\"" . $results_nvt->fields['oid'] . "\"/>";
                        $results_nvt->MoveNext();
                    }
                    $command .= "</nvt_selection>".$command_end;
                }
            }
            $result->MoveNext();
        }
        $command_family_selection .= "</family_selection>";

        $command_to_execute = $command;
        if($enable_whole_family){
            $command_to_execute .= $command_start.$command_family_selection.$command_end;
        }

        if ($this->execute_command($command_group_start.$command_to_execute.$command_group_end) === FALSE) {
            $res["status"]  = "error";
            $res["message"] = "[set_plugins_by_family] ".$this->error_message;
            return $res;
        }


        return ( array( "status" => "OK", "message" => "") );
    }


    public function set_preferences ($sid) {
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $id_config = $this->get_config_id_by_sid($sid);
        $uuid      = Util::get_encryption_key();

        if ($id_config == "") {
            $res["status"]  = "error";
            $res["message"] = _("Profile not found!");
            return $res;
        }

        if ($this->delete_tasks_by_config_id($id_config) === FALSE) {
            $res["status"]  = "error";
            $res["message"] = _("Couldn't delete the profile tasks!");
            return $res;
        }

        $command = "";

        // Select preferences

        $params = array($uuid, $sid);
        $query = "SELECT sp.id, sp.nessus_id as nessus_id, sp.value as value, AES_DECRYPT(sp.value, ?) as dvalue, sp.type, sp.category
                  FROM vuln_nessus_settings_preferences AS sp
                  WHERE sp.sid=? AND sp.value != ''";

        $result=$this->conn->Execute($query, $params);

        $i = 0;
        $chunk_size = 100;
        while (!$result->EOF) {
            $command .= "<modify_config config_id='$id_config'>";
            $command .= "<preference>";
            if($result->fields['id'] != "") {
                $command .= "<nvt oid='".$result->fields['id']."'/>";
            }
            $command .= "<name>".$result->fields['nessus_id']."</name>";

            if($result->fields['type'] == "P"){
                $pref_value = Util::utf8_encode2($result->fields['dvalue']);
            } else {
                $pref_value = Util::utf8_encode2($result->fields['value']);
            }

            $command .= "<value>".base64_encode($pref_value)."</value>";
            $command .= "</preference>";
            $command .= "</modify_config>\n";

            if ($command!="" && $i == $chunk_size) {
                $command = "<commands>".$command."</commands>";

                if ($this->execute_command($command) === FALSE) {
                    $res["status"]  = "error";
                    $res["message"] = $this->error_message;
                    return $res;
                }
                $i = 0;
                $command = "";
            }

            $i ++;

            $result->MoveNext();
        }

        if ($command!="") {
            $command = "<commands>".$command."</commands>";

            if ($this->execute_command($command) === FALSE) {
                $res["status"]  = "error";
                $res["message"] = $this->error_message;
                return $res;
            }
        }

        return (array( "status" => "OK", "message" => ""));
    }


    /**
     * @codeCoverageIgnore
     */
    public function check_connection() {
        $xml = $this->execute_command("<help />");

        if($xml === FALSE) {
            return $this->error_message;
        }
        else {
            return "";
        }
    }


    public function create_new_config($sid, $clone_id) {
        $existent_profiles = Vulnerabilities::get_profiles($this->conn);

        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $this->conn->Execute("SELECT `name`, `owner` FROM vuln_nessus_settings WHERE id='$sid' LIMIT 1");

        $pname = $result->fields['name'];
        $puser = $result->fields['owner'];

        if (array_key_exists($clone_id, $existent_profiles))
        {
            $id_ff = $this->get_config_id_by_name_comment($existent_profiles[$clone_id]);

        }
        else{
            $id_ff = $this->get_config_id_by_name_comment("Base");
        }

        $result_create = $this->execute_command("<create_config><copy>".$id_ff."</copy><name>$pname</name><comment>$puser</comment></create_config>");

        if ($result_create === FALSE)
        {
            return ( array("message" => $this->error_message, "status" => "error") );
        }

        $result_create = $result_create->attributes()->id;

        $result = $this->save_vnss_in_db($sid, $result_create);
        if($result["status"] == "error")
            return $result;

        #if not cloned I should set the plugins and preferences as are in DB
        if (!array_key_exists($clone_id, $existent_profiles)) {
            $result = $this->set_plugins_by_family($sid);

            if ($result["status"] == "error") {
                return $result;
            }

            $result = $this->set_preferences($sid);

            if ($result["status"] == "error") {
                return $result;
            }
        }

        return ["status" => "success", "message" => ""];
    }

    public function save_vnss_in_db($sid, $result_create){
        $query = "REPLACE INTO vuln_nessus_settings_sensor (`sensor_id`, `vns_id`, `sensor_gvm_config_id`) VALUES (UNHEX(?), ?, ?)";
        $result = $this->conn->execute($query, [$this->host_id, $sid, $result_create] );
        if($result === FALSE)
        {
            return array ("message" => $this->conn->ErrorMsg(), "status" => "error") ;
        }
        return array ("message" => "", "status" => "success") ;
    }


    /**
     * This function stops a GVM task
     * @param string $job_id Job ID
     * @codeCoverageIgnoreStart
     */
    public function stop_task($job_id){
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $params = array($job_id);
        $query = "SELECT task_id, name FROM vuln_jobs WHERE id=?";

        $rs = $this->conn->Execute($query, $params);

        if (!$rs) {
            $this->error_message = $this->conn->ErrorMsg();
            return FALSE;
        }

        $task_id = $rs->fields["task_id"];
        $job_name = $rs->fields["name"];

        $this->execute_command ("<stop_task task_id='$task_id' />");

        #GVM bug: Task is stopped but connection is closed remotely and call doesn't return 200 OK
        $max_retries = 5;
        $status = 'Stopped';
        $stopped = FALSE;
        $i = 0;

        while ($stopped == FALSE && $i < $max_retries){
            $response = $this->get_task_detail_by_id($status, $job_id);

            if ($response['status'] == 'success' && $response['data']['status'] == 'Stopped'){
                $this->error_message = '';
                $stopped = TRUE;
            } else {
                $this->error_message = _("Job '$job_name' cannot be stopped");
                $i++;
                sleep(15);
            }
        }

        //GVM stopped the task, now we need to wait until the status is updated in the database
        if ($stopped == TRUE) {
            $max_retries = 20;
            $db_status = 'R';
            $i = 0;

            while ($db_status != 'I' && $i < $max_retries){
                $params = array($job_id);
                $query = "SELECT status FROM vuln_jobs WHERE id=?";
                $rs = $this->conn->Execute($query, $params);

                if ($rs){
                    $db_status = $rs->fields["status"];
                }

                if ($db_status == 'I') {
                    $this->error_message = '';
                    return TRUE;
                } else {
                    $this->error_message = _("Job status is not properly updated in the database");
                    $i++;
                    sleep(10);
                }
            }
        }

        return FALSE;
    }

    /**
     * @codeCoverageIgnoreStart
     */
    public function resume_task($job_id) {
        //Job status will be set to 'S' (Scheduled) again and nessus_jobs.pl will run job scan again

        $query = "UPDATE vuln_jobs SET status = 'S' where id = ?";
        $params = array($job_id);

        $rs = $this->conn->Execute($query, $params);

        if (!$rs)
        {
            $this->error_message = $this->conn->ErrorMsg();
        }
    }

    /**
     * @codeCoverageIgnoreStart
     */
    public function delete_task($job_id){

        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $this->conn->Execute("SELECT task_id FROM vuln_jobs WHERE id=$job_id");
        $task_id = $result->fields["task_id"];

        $tstatus = $this->get_task_status($job_id);

        if($tstatus["status"]!="") {
            if($tstatus["status"]!="Stopped") {
                $this->stop_task($job_id);
            }
            $this->execute_command ("<delete_task task_id='$task_id' />");
        }
    }


    /*
     * @codeCoverageIgnoreEnd
     */
    public function get_task_status($job_id) {
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $status = array(
            "status"   => "",
            "progress" => "")
        ;

        $result = $this->conn->Execute("SELECT task_id FROM vuln_jobs WHERE id=$job_id");
        $task_id = $result->fields["task_id"];

        $xml = $this->execute_command("<get_tasks task_id='$task_id'/>");

        if ($xml === FALSE) {
            return ($status);
        }

        $status["status"] = trim((string) $xml->task[0]->status);
        $status["progress"] = trim((string) $xml->task[0]->progress);

        return ($status);
    }


    public function get_task_detail_by_id($states, $job_id = '')
    {
        $response = Array(
            "status" => "success",
            "data"   => array()
        );

        if($job_id == '')
		{
            $this->error_message = "JOB ID is empty";

	        $response = Array(
                "status" => "error",
                "data"   => $this->error_message
            );

            return $response;
		}

		$this->conn->SetFetchMode(ADODB_FETCH_BOTH);

        $tasks_info_arr = array();

        $arr_states = explode("|", $states);

        $result_servers=$this->conn->Execute("SELECT DISTINCT notify, meth_TARGET, task_id FROM vuln_jobs WHERE id=$job_id");

        $sensor_id = $result_servers->fields['notify'];
        $targets   = $result_servers->fields['meth_TARGET'];
		$task_id   = $result_servers->fields['task_id'];

		if (!empty($sensor_id)){
            $sensor = Av_sensor::get_object($this->conn, $sensor_id);
            $sensor_ip = $sensor->get_ip();

            $this->set_server_properties($sensor_ip);
        }
		else
		{
            $this->error_message = "Sensor not found";
		    $response = Array(
                "status" => "error",
                "data"   => $this->error_message
            );

            return $response;
		}

		$xml = $this->execute_command("<get_tasks task_id='$task_id'/>");

		//There's no XML
		if ($xml === FALSE) {
	        //Task has finished and the report is being created
            $response['status'] = 'error';
            $response['data'] = _('Task not found');
            return $response;
		};

        $allowed_users = $this->get_allowed_users();

        $result = $this->conn->Execute("SELECT id, username FROM vuln_jobs WHERE task_id='$task_id'");
        $username = $result->fields["username"];

        $status = (string)$xml->task->status;
        $progress = (int)$xml->task->progress;

        if((Session::am_i_admin() || in_array($username, $allowed_users)) && in_array($status, $arr_states))
        {
            $response['data'] = array (
                'task_id' => $task_id,
                'job_id' => $job_id,
                'status' => $status,
                'progress' => $progress,
                'host_progress' => array()
            );

            foreach($xml->task->progress->host_progress as $host_progress)
            {
                $response['data']['host_progress'][(string)$host_progress->host] = array(
                    'ip' => (string)$host_progress->host,
                    'md5_ip' => md5((string)$host_progress->host),
                    'progress' => (int)$host_progress
                );
            }
        } else {
            $this->error_message = "Action not allowed";
            $response = Array(
                "status" => "error",
                "data"   => $this->error_message
            );
        }

        return $response;
    }


    public function delete_config($sid)
    {
        $config_id = $this->get_config_id_by_sid($sid);

        if($this->delete_tasks_by_config_id($config_id) === FALSE)
        {
            $res["status"]  = "error";
            $res["message"] = _("Couldn't delete the profile tasks!");
            return $res;
        }

        if($this->execute_command ("<delete_config config_id='$config_id' />") === FALSE)
        {
            $res["status"]  = "error";
            $res["message"] = $this->error_message;
            return $res;
        }

        return (array("status" => "OK", "message" => ""));
    }


    public function set_server_properties($ip)
    {
        $this->ip = $ip;
    }

    public function get_ip(){
        return $this->ip;
    }

    public function get_host_id(){
        return $this->host_id;
    }

    public function get_binary_location(){
        return $this->binary_location;
    }
}

/* End of file gvm.inc */
/* Location: ../include/classes/gvm.inc */
