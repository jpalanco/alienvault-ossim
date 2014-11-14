<?php
/**
* omp.inc
*
* File omp.inc is used to:
*   - To manage OMP protocol
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

class Omp
{
    private $binary_location;
    private $latest_binary_location;
    private $ip;
    private $port;
    private $user;
    private $password;
    private $db;
    private $conn;
    private $error_message;
    
    public function __construct($host = '', $port = '', $user = '', $pass = '', $path = '') {

        require_once "ossim_db.inc";
        $this->db = new ossim_db();
        $this->conn = $this->db->connect();
        
        $this->latest_binary_location = "/usr/bin/omp";  // ** to use standard input **
   
        if ($path=="" || $host=="" || $port=="" || $user=="" || $pass=="") {
            $conf = $GLOBALS["CONF"];
        }
        
        if($path=="") { $this->binary_location = $conf->get_conf("nessus_path", FALSE); }
            else { $this->binary_location = $path; }
        
        if($host=="") { $this->ip = $conf->get_conf("nessus_host", FALSE); }
            else { $this->ip = $host; }
        
        if($port=="") { $this->port = $conf->get_conf("nessus_port", FALSE); }
        else { $this->port = $port; }
        
        if($user=="") { $this->user = $conf->get_conf("nessus_user", FALSE); }
        else { $this->user = $user; }
        
        if($pass=="") { $this->password = $conf->get_conf("nessus_pass", FALSE); }
        else { $this->password = $pass; }

        $this->user     = str_replace("'","'\"'\"'",$this->user);
        $this->password = str_replace("'","'\"'\"'",$this->password);
        
        $this->error_message = "";
        
    }
    
    function __destruct(){
        $this->db->close($this->conn);
    }
    
    // ------  private functions ------
    
    public function execute_command ($command, $sinput=TRUE) {
        
        $binary_location        = $this->binary_location;
        $latest_binary_location = $this->latest_binary_location;
        $ip                     = $this->ip;
        $port                   = $this->port;
        $user                   = $this->user;
        $password               = $this->password;
        $tmpfile                = "/usr/share/ossim/www/tmp/omp".substr(preg_replace("/\D/", "", uniqid(md5(rand()), true)),0,15).".xml";;
        
        $file_omp_command = "";
        
        if($sinput && file_exists($latest_binary_location)) { // use standard input stream
            $file_omp_command = "/usr/share/ossim/www/tmp/omp_command".substr(preg_replace("/\D/", "", uniqid(md5(rand()), true)),0,15).".xml";
            
            file_put_contents($file_omp_command, $command);
            
            $ecommand = $latest_binary_location." -h $ip -p $port -u '$user' -w '$password' -iX - < ".$file_omp_command." > $tmpfile 2>&1";
        }
        else {
            $ecommand = "$binary_location -h $ip -p $port -u '$user' -w '$password' -iX '$command' > $tmpfile 2>&1";
        }
        
        system($ecommand); // execute OMP command
        
        if($sinput && file_exists($file_omp_command)) {
            unlink($file_omp_command);
        }
        
        if(!file_exists($tmpfile)) {
          return FALSE;
        }
        else {
            $data = file_get_contents($tmpfile);
        
            unlink($tmpfile);
        }
        $xml = @simplexml_load_string($data);
        
        if (!is_object($xml)) {
            $this->error_message = trim($data);
            return FALSE;
        }
        else if (!preg_match("/^20.*/",$xml->attributes()->status)) {
            //echo _("Error: status = ".$xml->attributes()->status.", status_text = ".$xml->attributes()->status_text." ($tmpfile)")."<br>";
            $this->error_message = trim($xml->attributes()->status_text);
            return FALSE;
        }

        return $xml;
    }
    
    public function get_config_id_by_sid($sid) {
 
        $config_id = "";
        
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $result = $this->conn->Execute("SELECT name, owner FROM vuln_nessus_settings WHERE id=$sid");
        $cname = $result->fields["name"];
        $cuser = $result->fields["owner"];

        $xml = $this->execute_command ("<get_configs />");

        if ($xml==FALSE) { return $config_id; }
        
        foreach ($xml->config as $config) {
            if( ($cname ==(string)$config->name) && ($cuser==(string)$config->comment) ){
                $config_id = (string)$config->attributes()->id;
            }
        }
        return $config_id;
    }
    
    private function delete_tasks_by_config_id($config) {
        
        $delete_tasks = array();
        
        $xml = $this->execute_command("<get_tasks />");
        
        if ($xml !== FALSE) {
            foreach ($xml->task as $task) { // save tasks with config_id = $config
                if($config == (string)$task->config->attributes()->id) {    $delete_tasks[]= (string)$task->attributes()->id;    }
            }
            foreach ($delete_tasks as $dtask) {
                if( $this->execute_command("<delete_task task_id=\"$dtask\" />") === FALSE) {
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
    
    
    public function get_results_by_ips($task_id) {
        
        $results_by_ips = array();
        
        $xml = $this->execute_command("<get_tasks task_id=\"$task_id\" details=\"1\"/>");
        
        if ($xml==FALSE) return 0;
        
        foreach ($xml->task->reports->report as $report) {
            $last_report_id = (string)$report->attributes()->id;
        }
        
        $xml = $this->execute_command("<get_reports report_id=\"$last_report_id\" />");
        
        if ($xml==FALSE) return 0;
        
        if(gettype($xml->report->report->results->result) == "object") $results = $xml->report->report->results->result; // OpenVAS 4
        else $results = $xml->report->results->result; // OpenVAS 3
        
        foreach ($results as $result) {
            if ((string)$result->nvt->risk_factor != "") { $risk_factor = (string)$result->nvt->risk_factor; }
            else { $risk_factor = "Info"; }
            
            if($risk_factor == "Critical")  { $risk_factor = "Serious"; }
            if($risk_factor == "Log")       { $risk_factor = "Info"; }
            if($risk_factor == "None")      { $risk_factor = "Info"; }
            if($risk_factor == "Passed")    { $risk_factor = "Info"; }
            if($risk_factor == "Unknown")   { $risk_factor = "Medium"; }
            if($risk_factor == "Failed")    { $risk_factor = "High"; }
        
            if (!isset($results_by_ips[(string)$result->host][$risk_factor])) {
                $results_by_ips[(string)$result->host][$risk_factor] = 1;
            }
            else {
                $results_by_ips[(string)$result->host][$risk_factor]+=1;
            }
        }
        
        return $results_by_ips;
    }
    
    // ------  public functions ------
    
    public function get_config_id_by_name_comment($name, $comment="") {

        $config_id = "";
    
        $xml = $this->execute_command ("<get_configs />");
        
        if ($xml==FALSE) { return $config_id; }

        foreach ($xml->config as $config) {
            if($comment!="") {
                if( ($name ==(string)$config->name) && ($comment==(string)$config->comment) ) {    $config_id = (string)$config->attributes()->id;    }
            }
            else {
                if( ($name ==(string)$config->name) ) {    $config_id = (string)$config->attributes()->id;    }
            }
        }
        return $config_id;
    }
    
    
    public function set_plugins_by_family ($sid, $fam = "") {
    
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
    
        $familyHash = array();
        $command = "";

        $id_config = $this->get_config_id_by_sid($sid);

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
        
        $this->execute_command("<empty_trashcan />");
        
        // Disable all families
        
        $fam_where = ( $fam != "" ) ? "WHERE id=$fam" : "";
        
        $result=$this->conn->Execute("SELECT name FROM vuln_nessus_family $fam_where");
        
        $command = "<commands>";
        while (!$result->EOF) {
            $fname = $result->fields['name'];
            $command .= "<modify_config config_id=\"$id_config\"><nvt_selection><family>$fname</family></nvt_selection></modify_config>";
            $result->MoveNext();
        }
        $command .= "</commands>";

        if ($this->execute_command($command) === FALSE) {
            $res["status"]  = "error";
            $res["message"] = $this->error_message;
            return $res;
        }
        
        // Select enabled plugins
        
        $fam_filter = ( $fam != "" ) ? "AND f.id=$fam" : "";
        
        $result=$this->conn->Execute("SELECT f.name as fname, p.oid as plugin_id FROM vuln_nessus_settings_plugins AS sp
                                            LEFT JOIN vuln_nessus_plugins AS p ON sp.id = p.id
                                            LEFT JOIN vuln_nessus_family AS f ON sp.family = f.id
                                            WHERE sp.enabled = 'Y'
                                            AND sp.sid = '$sid' $fam_filter");
        while (!$result->EOF) {
            $fname = $result->fields['fname'];
            $plugin_id = $result->fields['plugin_id'];
            $familyHash["$fname"]["$plugin_id"]++;
            $result->MoveNext();
        }
        
        // Update openvas-manager plugins
        
        foreach ($familyHash as $family => $v2) {
            $command = "<modify_config config_id=\"$id_config\"><nvt_selection><family>$family</family>";
            foreach ($v2 as $oid => $v3) {
                $command .= "<nvt oid=\"".$oid."\"/>";
            }
            $command .= "</nvt_selection></modify_config>";
            
            if ($this->execute_command($command) === FALSE) {
                $res["status"]  = "error";
                $res["message"] = $this->error_message;
                return $res;
            }
        }
        
        return ( array( "status" => "OK", "message" => "") );
    }
    
    public function set_preferences ($sid) {
        
        require_once 'classes/Util.inc';
        
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
        
        $this->execute_command("<empty_trashcan />");
        
        $command = "";
        
        
        // Select preferences
        
        $result=$this->conn->Execute("SELECT sp.nessus_id as nessus_id, sp.value as value, AES_DECRYPT(sp.value,'$uuid') as dvalue, sp.type, sp.category, p.oid as oid FROM vuln_nessus_settings_preferences AS sp
                                        LEFT JOIN vuln_nessus_plugins AS p ON p.id = sp.id
                                        WHERE sp.sid=$sid AND sp.value!=''");

        
        while (!$result->EOF) {
            if(!Util::is_utf8($result->fields['nessus_id'])) {
                $command .= "<modify_config config_id=\"$id_config\">";
                $command .= "<preference>";
                if($result->fields['oid']!="") {
                    $command .= "<nvt oid=\"".$result->fields['oid']."\"/>";
                }
                $command .= "<name>".$result->fields['nessus_id']."</name>";
                //$command .= "<name>".Util::utf8_encode2($result->fields['nessus_id'])."</name>";
                //$command .= "<name><![CDATA[".$result->fields['nessus_id']."]]></name>"; 
                $command .= "<value>".base64_encode((!empty($result->fields['dvalue'])) ? $result->fields['dvalue'] : $result->fields['value'])."</value>";
                
                //if($result->fields['type']=="P") {
                //    error_log("password: ".$result->fields['nessus_id']."-".base64_encode((!empty($result->fields['dvalue'])) ? $result->fields['dvalue'] : $result->fields['value'])."\n",3,"/tmp/setting.log");
                //}
                $command .= "</preference>";
                $command .= "</modify_config>\n";
                
                /*
                if ($result->fields['type']=="P") {
                    $result->fields['value'] = $this->conn->GetOne( "SELECT AES_DECRYPT('".$result->fields['value']."','".$uuid."')" );
                }
                
                $this->update_preferences_sqlite($id_config, $result->fields['category'], $result->fields['nessus_id'], $result->fields['value']);
                */
            }
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
        
        return ( array( "status" => "OK", "message" => "") );
    }
    public function get_tasks_using_config($sid) {
        $running_tasks = array();
        
        $id_config = $this->get_config_id_by_sid($sid);
        
        if ($id_config =="") { return; }
        
        $xml = $this->execute_command("<get_tasks />");
        
        foreach ($xml->task as $task) { 
            if((string)$task->status != "Done" && $id_config == (string)$task->config->attributes()->id) {    $running_tasks[]= (string)$task->attributes()->id;    }
        }
        
        return $running_tasks;
    }
    public function check_connection() {
        $xml = $this->execute_command("<help />");
        
        if($xml===FALSE) {
            return $this->error_message;
        }
        else {
            return "";
        }
    }
    public function get_used_sids() {
    
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
    
        $used_configs = array();
        $pname = "";
        $puser = "";
        $sids = array();
        
        $xml = $this->execute_command("<get_tasks />");
        
        if ($xml == FALSE) { return $sids; }
        
        foreach ($xml->task as $task) { 
            if((string)$task->status != "Done" && (string)$task->status != "Stopped" && !in_array((string)$task->config->attributes()->id, $used_configs)) {
                $used_configs[]= (string)$task->config->attributes()->id;
            }
        }
        
        // get profile sid
        foreach($used_configs as $config) {
            $xml = $this->execute_command("<get_configs config_id=\"$config\"/>");
            foreach($xml->config as $config) {
                $pname = (string)$config->name;
                $puser = (string)$config->comment;
                $result=$this->conn->Execute("SELECT id FROM vuln_nessus_settings WHERE name='$pname' AND owner='$puser' LIMIT 1");
                $sids[] = $result->fields['id'];
            }
        }
        return $sids;
    }
    public function create_new_config($sid, $clone_id) {
    
        $default_profiles = array('1' => 'Deep', '2' => 'Default', '3' => 'Ultimate');
    
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $result=$this->conn->Execute("SELECT name, owner FROM vuln_nessus_settings WHERE id='$sid' LIMIT 1");
            
        $pname = $result->fields['name'];
        $puser = $result->fields['owner'];
        
        $id_config = $this->get_config_id_by_sid($sid);
          
        if (array_key_exists($clone_id, $default_profiles))
        { 
            $id_to_clone = $this->get_config_id_by_name_comment($default_profiles[$clone_id]);
            $result      = $this->execute_command("<create_config><copy>$id_to_clone</copy><name>$pname</name><comment>$puser</comment></create_config>");
            
            if ($result === FALSE)
            {  
                return ( array("message" => $this->error_message, "status" => "error") ); 
            }   
        }
        else if($id_config != '')
        {
            $result = $this->set_plugins_by_family($sid);
            
            if($result["status"] == "error") {
                return $result;
            }
            
            $result = $this->set_preferences($sid);
            
            if($result["status"] == "error") {
                return $result;
            }
        }
        else
        {
            $id_ff = $this->get_config_id_by_name_comment("Full and fast");
            $this->execute_command("<create_config><copy>".$id_ff."</copy><name>$pname</name><comment>$puser</comment></create_config>");
            $result = $this->set_plugins_by_family($sid);
            
            if($result["status"] == "error") {
                return $result;
            }
            
            $result = $this->set_preferences($sid);

            if($result["status"] == "error") {
                return $result;
            }
        }
        
        return ( array("message" => "", "status" => "OK") );
    }
    public function play_task($job_id){
    
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $this->execute_command ("<start_task task_id=\"$config_id\" />");

    }
    public function pause_task($job_id){
    
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];

        $this->execute_command ("<pause_task task_id=\"$config_id\" />");

    }
    
    public function stop_task($job_id){
        
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $this->execute_command ("<stop_task task_id=\"$config_id\" />");

    }
    
    public function delete_task($job_id){
        
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $tstatus = $this->get_task_status($job_id);

        if($tstatus["status"]!="") {
            if($tstatus["status"]!="Stopped") {
                $this->stop_task($job_id);
            }
            $this->execute_command ("<delete_task task_id=\"$config_id\" />");
        }
    }
    
    public function resume_task($job_id) {
    
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $task_status = $this->get_task_status($job_id);
        
        if( preg_match("/^stopped.*/i", $task_status["status"]) ) {
            $this->execute_command ("<resume_stopped_task task_id=\"$config_id\" />");
        }
        else {
            $this->execute_command ("<resume_paused_task task_id=\"$config_id\" />");
        }
    }
    public function get_task_status($job_id) {
        
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $status = array("status"=>"", "progress"=>"");
        
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $xml = $this->execute_command("<get_tasks task_id=\"$config_id\"/>");
        
        if ($xml==FALSE) { return ($status); }
        
        $status["status"] = trim((string) $xml->task[0]->status);
        $status["progress"] = trim((string) $xml->task[0]->progress);
        
        return ($status);

    }
    public function get_number_of_results($states, $job_id = ''){
        
		if($job_id == '' ) {
			return 0;
		}
		
		$this->conn->SetFetchMode(ADODB_FETCH_BOTH);
		
        $arr_states = explode("|", $states);        
               
        $result_servers=$this->conn->Execute("SELECT meth_Wcheck FROM vuln_jobs WHERE status='R'");
        
        $_ip       = $this->ip;
        $_port     = $this->port;
        $_user     = $this->user;
        $_password = $this->password;

        
        while (!$result_servers->EOF)
        {
            $server_ip = $result_servers->fields['meth_Wcheck'];

            preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/",$server_ip,$found);
            
            if ($found[1] != '')
            {
                $sensor_id     = Av_sensor::get_id_by_ip($this->conn, $found[1]);
            
                $sensor_object = new Av_sensor($sensor_id);
        
                $sensor_object->load_from_db($this->conn);
                
                $ov_credentials = $sensor_object->get_vs_credentials($this->conn);
                        
                $port     = $ov_credentials['port'];
                $user     = $ov_credentials['user'];
                $password = $ov_credentials['password'];
            }
            
            $this->set_server_properties($found[1], $port, $user, $password);
            
            $xml = $this->execute_command("<get_tasks />");
            
            if ($xml==FALSE) return 0;
            
            $allowed_users = $this->get_allowed_users();

            $total = 0;
            $running_requested_tasks = 0;

            foreach ($xml->task as $task) {
                $task_id = $task->attributes()->id;
                $result = $this->conn->Execute("SELECT id, username FROM vuln_jobs WHERE meth_CPLUGINS='$task_id'");
                $username = $result->fields["username"];
                
                if( (in_array($username, $allowed_users) || Session::am_i_admin()) && in_array((string)$task->status, $arr_states) ) {
                        $task_status = $this->get_task_status($result->fields["id"]);
                        
                        if ($task_status["status"] == "Requested" || $task_status["status"] == "Running")  $running_requested_tasks++;
                
                        $results_by_ips = array();
                        $results_by_ips = $this->get_results_by_ips($task_id);
                        
                        foreach($task->progress->host_progress as $hprogress) {
                            $total += intval((isset($results_by_ips[(string)$hprogress->host]["Serious"])) ? $results_by_ips[(string)$hprogress->host]["Serious"] : "0");
                            $total += intval((isset($results_by_ips[(string)$hprogress->host]["High"])) ? $results_by_ips[(string)$hprogress->host]["High"] : "0");
                            $total += intval((isset($results_by_ips[(string)$hprogress->host]["Medium"])) ? $results_by_ips[(string)$hprogress->host]["Medium"] : "0");
                            $total += intval((isset($results_by_ips[(string)$hprogress->host]["Low"])) ? $results_by_ips[(string)$hprogress->host]["Low"] : "0");
                            $total += intval((isset($results_by_ips[(string)$hprogress->host]["Info"])) ? $results_by_ips[(string)$hprogress->host]["Info"] : "0");
                        }
                }
            }

            $result_servers->MoveNext();
        }
        
        $this->ip       = $_ip;
        $this->port     = $_port;
        $this->user     = $_user;
        $this->password = $_password;
        
        if($total==0 && $running_requested_tasks>0) { return -1; }

        return $total;
    }

    public function get_tasks_details($states){
        
        $this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $arr_states = explode("|", $states);        
                
        $result_servers=$this->conn->Execute("SELECT DISTINCT meth_Wcheck FROM vuln_jobs WHERE status='R'");
        
        $_ip       = $this->ip;
        $_port     = $this->port;
        $_user     = $this->user;
        $_password = $this->password;

        
        while (!$result_servers->EOF) {
            $server_ip = $result_servers->fields['meth_Wcheck'];

            preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/",$server_ip,$found);
            
            if ($found[1] != '')
            {
                $sensor_id     = Av_sensor::get_id_by_ip($this->conn, $found[1]);
            
                $sensor_object = new Av_sensor($sensor_id);
        
                $sensor_object->load_from_db($this->conn);
                
                $ov_credentials = $sensor_object->get_vs_credentials($this->conn);
                        
                $port     = $ov_credentials['port'];
                $user     = $ov_credentials['user'];
                $password = $ov_credentials['password'];
            
                $this->set_server_properties($found[1], $port, $user, $password);
            }
            
            $xml = $this->execute_command("<get_tasks />");
            
            if ($xml==FALSE) return 0;
            
            $allowed_users = $this->get_allowed_users();

            $tasks_info .= "<table width='100%' class='transparent' cellspacing='0' cellpadding='0'>";
            $tasks_info .= "<tr><th>"._("Job Name")."</th><td class='noborder'><img src='./images/high.png'/></td><td class='noborder'><img src='./images/medium.png'/></td><td class='noborder'><img src='./images/low.png'/></td><td class='noborder'><img src='./images/inf.png'/></td><th>"._("State")."</th></tr>";
            
            foreach ($xml->task as $task) {
                $results_by_ips = array();
                $results_by_ips = $this->get_results_by_ips((string)$task->attributes()->id);
            
                $task_id = $task->attributes()->id;
                $result = $this->conn->Execute("SELECT id, username FROM vuln_jobs WHERE meth_CPLUGINS='$task_id'");
                $username = $result->fields["username"];
                
                if( (in_array($username, $allowed_users) || Session::am_i_admin()) && in_array((string)$task->status, $arr_states) ) {
                    if ((int)$task->progress <=25) {
                        $task_progress = "<img src='./images/light_yellow_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                        $task_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                    
                    }
                    else if ((int)$task->progress <=50) {
                        $task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                        $task_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                    }
                    else if ((int)$task->progress <=75) {
                        $task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
                        $task_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                        }
                    else if ((int)$task->progress <=99) {
                        $task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img style='padding-right:7px;' src='./images/light_yellow_lit.png' border='0'/>";}
                    else {
                        $task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                        $task_progress .= "<img style='padding-right:7px;' src='./images/light_green_lit.png' border='0'/>";
                    }
                    $tstatus = $this->get_task_status($result->fields["id"]);
                    
                    $simage = "";
                    if ($tstatus["status"]=="Paused") { $simage = "<img align='absmiddle' style='padding:2px 4px 2px 0px' src='./images/paused.png' border='0'/>"; }
                    else if ($tstatus["status"]=="Running") { $simage = "<img align='absmiddle' style='padding:2px 4px 2px 0px' src='./images/running.png' border='0'/>"; }
                    else if ($tstatus["status"]=="Requested" || $tstatus["status"]=="Pause Requested") { $simage = "<img align='absmiddle' style='padding:2px 4px 2px 0px' src='./images/requested.png' border='0'/>"; }
                    
                    $chole = $cwarning = $cinfo = $clog = 0;
                    
                    foreach($task->progress->host_progress as $hprogress) {
                        $chole    += intval((isset($results_by_ips[(string)$hprogress->host]["High"])) ? $results_by_ips[(string)$hprogress->host]["High"] : "0");
                        $cwarning += intval((isset($results_by_ips[(string)$hprogress->host]["Medium"])) ? $results_by_ips[(string)$hprogress->host]["Medium"] : "0");
                        $cinfo    += intval((isset($results_by_ips[(string)$hprogress->host]["Low"])) ? $results_by_ips[(string)$hprogress->host]["Low"] : "0");
                        $clog     += intval((isset($results_by_ips[(string)$hprogress->host]["Info"])) ? $results_by_ips[(string)$hprogress->host]["Info"] : "0");
                    }
                    
                    $tasks_info .= "<tr>";
                    $tasks_info .= "<td width='36%' style='font-size:10px;padding-left:5px;' class='nobborder'>".(($simage!="") ? $simage : "").(string)$task->name."</td>";
                    $tasks_info .= "<td width='10%' style='font-size:10px;' class='noborder'>";
                    $tasks_info .= $chole;
                    $tasks_info .= "</td>";
                    $tasks_info .= "<td width='10%' style='font-size:10px;' class='noborder'>";
                    $tasks_info .= $cwarning;
                    $tasks_info .= "</td>";
                    $tasks_info .= "<td width='10%' style='font-size:10px;' class='noborder'>";
                    $tasks_info .= $cinfo;
                    $tasks_info .= "</td>";
                    $tasks_info .= "<td width='10%' style='font-size:10px;' class='noborder'>";
                    $tasks_info .= $clog;
                    $tasks_info .= "</td>";
                    if ($tstatus["status"]=="Requested" || $tstatus["status"]=="Pause Requested") {
                        $tasks_info .= "<td width='24%' style='font-size:10px;' class='noborder'>"._("Requested")."</td>";
                    }
                    else {
                        $tasks_info .= "<td width='24%' style='font-size:10px;' class='noborder'><div style='float:left;padding:3px 0px 0px 8px;'>$task_progress</div><div style='float:right;padding-right:8px;'>".trim((string)$task->progress)."%</div></td>";
                    }
                    $tasks_info .= "</tr>";
                
                
                    $i = 1;
                    $num = count($task->progress->host_progress);
                    foreach($task->progress->host_progress as $hprogress) {

                        if ((int)$hprogress <=25) {
                            $host_progress = "<img src='./images/light_yellow_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                            $host_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                        }
                        else if ((int)$hprogress <=50) {
                            $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                            $host_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                        }
                        else if ((int)$hprogress <=75) {
                            $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
                            $host_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                        }
                        else if ((int)$hprogress <=99) {
                            $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img style='padding-right:7px;' src='./images/light_yellow_lit.png' border='0'/>";}
                        else {
                            $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                            $host_progress .= "<img style='padding-right:7px;' src='./images/light_green_lit.png' border='0'/>";
                        }
                    
                        if( $i==$num || count($task->progress->host_progress)==1 ) {
                            $tree_image = "<img align='absmiddle' border='0' src='../pixmaps/theme/ltL_ne.gif'>";
                        }
                        else {
                            $tree_image = "<img align='absmiddle' border='0' src='../pixmaps/theme/ltL_nes.gif'>";
                        }
                        if($i%2==1) {
                            $tasks_info .= "<tr bgcolor='#cecece'>";
                        }
                        else {
                            $tasks_info .= "<tr>";
                        }
                        $tasks_info .= "<td class='noborder' style='font-size:10px;padding-left:8px;text-align:left;'>$tree_image<b>".(string)$hprogress->host."</b></td>";
                        $tasks_info .= "<td class='noborder' style='font-size:10px;'>";
                        $tasks_info .= (isset($results_by_ips[(string)$hprogress->host]["High"])) ? $results_by_ips[(string)$hprogress->host]["High"] : "0";
                        $tasks_info .= "</td>";
                        $tasks_info .= "<td class='noborder' style='font-size:10px;'>";
                        $tasks_info .= (isset($results_by_ips[(string)$hprogress->host]["Medium"])) ? $results_by_ips[(string)$hprogress->host]["Medium"] : "0";
                        $tasks_info .= "</td>";
                        $tasks_info .= "<td class='noborder' style='font-size:10px;'>";
                        $tasks_info .= (isset($results_by_ips[(string)$hprogress->host]["Low"])) ? $results_by_ips[(string)$hprogress->host]["Low"] : "0";
                        $tasks_info .= "</td>";
                        $tasks_info .= "<td class='noborder' style='font-size:10px;'>";
                        $tasks_info .= (isset($results_by_ips[(string)$hprogress->host]["Info"])) ? $results_by_ips[(string)$hprogress->host]["Info"] : "0";
                        $tasks_info .= "</td>";
                        $tasks_info .= "<td class='noborder' style='font-size:10px;'><div style='float:left;padding:3px 0px 0px 8px;'>$host_progress</div><div style='float:right;padding-right:8px;'>".trim((string)$hprogress)."%</div></td>";
                        $tasks_info .= "</tr>";
                        
                        $i++;
                    }
                    $tasks_info .= "<tr><td class='nobborder' coslpan='6'>&nbsp;</td></tr>";
                }
                
            }
            $tasks_info .= "</table>";
            $result_servers->MoveNext();
            
        }
        
        $this->ip       = $_ip;
        $this->port     = $_port;
        $this->user     = $_user;
        $this->password = $_password;
            
            
        return $tasks_info;
    }
    
    
    public function get_task_detail_by_id($states, $job_id = '', $plain_text = FALSE) 
    {
		if($job_id == '')
		{
			return ( array('total' => '', 'detail' => '') ) ;
		}
		
		$this->conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $tasks_info_arr = array();
        
        $arr_states = explode("|", $states);        
                
        $result_servers=$this->conn->Execute("SELECT DISTINCT meth_Wcheck, meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        
        $server_ip = $result_servers->fields['meth_Wcheck'];
		$task_id   = $result_servers->fields['meth_CPLUGINS'];

        preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/",$server_ip,$found);
            
        if ($found[1] != '')
        {
            $sensor_id     = Av_sensor::get_id_by_ip($this->conn, $found[1]);
        
            $sensor_object = new Av_sensor($sensor_id);
        
            $sensor_object->load_from_db($this->conn);
                
            $ov_credentials = $sensor_object->get_vs_credentials($this->conn);
                        
            $port     = $ov_credentials['port'];
            
            $user     = $ov_credentials['user'];
            
            $password = $ov_credentials['password'];
                
            $this->set_server_properties($found[1], $port, $user, $password);
        }
		else 
		{
			return ( array("total" => "", "detail" => "") ) ;
		}
            
        $xml = $this->execute_command("<get_tasks />");
            
        if ($xml==FALSE) return ( array("total" => "", "detail" => "") ) ;
            
        $allowed_users = $this->get_allowed_users();

        foreach ($xml->task as $task) 
        {
			if( $task_id == (string)$task->attributes()->id ) 
			{
				$results_by_ips = array();
				$results_by_ips = $this->get_results_by_ips((string)$task->attributes()->id);
				
				$task_id = $task->attributes()->id;
				$result = $this->conn->Execute("SELECT id, username FROM vuln_jobs WHERE meth_CPLUGINS='$task_id'");
				$username = $result->fields["username"];
					
				if( (in_array($username, $allowed_users) || Session::am_i_admin()) && in_array((string)$task->status, $arr_states) ) 
				{
					if ((int)$task->progress <=25) 
					{
						$task_progress = "<img src='./images/light_yellow_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
						$task_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
					}
					else if ((int)$task->progress <=50) 
					{
						$task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
						$task_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
					}
					else if ((int)$task->progress <=75) 
					{
						$task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
						$task_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
					}
					else if ((int)$task->progress <=99) 
					{
						$task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img style='padding-right:7px;' src='./images/light_yellow_lit.png' border='0'/>";}
					else 
					{
						$task_progress = "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
						$task_progress .= "<img style='padding-right:7px;' src='./images/light_green_lit.png' border='0'/>";
					}
					
					$tstatus = $this->get_task_status($result->fields["id"]);
						
					$simage = "";
					if ($tstatus["status"]=="Paused") { $simage = "<img align='absmiddle' style='padding:2px 4px 2px 0px' src='./images/paused.png' border='0'/>"; }
					else if ($tstatus["status"]=="Running") { $simage = "<img align='absmiddle' style='padding:2px 4px 2px 0px' src='./images/running.png' border='0'/>"; }
					else if ($tstatus["status"]=="Requested" || $tstatus["status"]=="Pause Requested") { $simage = "<img align='absmiddle' style='padding:2px 4px 2px 0px' src='./images/requested.png' border='0'/>"; }
					   
					$cserious = $chole = $cwarning = $cinfo = $clog = 0;
						
					foreach($task->progress->host_progress as $hprogress) 
					{
                        $cserious += intval((isset($results_by_ips[(string)$hprogress->host]["Serious"])) ? $results_by_ips[(string)$hprogress->host]["Serious"] : "0");
						$chole    += intval((isset($results_by_ips[(string)$hprogress->host]["High"])) ? $results_by_ips[(string)$hprogress->host]["High"] : "0");
						$cwarning += intval((isset($results_by_ips[(string)$hprogress->host]["Medium"])) ? $results_by_ips[(string)$hprogress->host]["Medium"] : "0");
						$cinfo    += intval((isset($results_by_ips[(string)$hprogress->host]["Low"])) ? $results_by_ips[(string)$hprogress->host]["Low"] : "0");
						$clog     += intval((isset($results_by_ips[(string)$hprogress->host]["Info"])) ? $results_by_ips[(string)$hprogress->host]["Info"] : "0");
					}
					
					$i = 1;
					
					foreach($task->progress->host_progress as $hprogress) 
					{
						$tasks_info = "";
                        if(!$plain_text) 
                        {
                                if ((int)$hprogress <=25) 
                                {
                                    $host_progress = "<img src='./images/light_yellow_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                                    $host_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                                }
                                else if ((int)$hprogress <=50) 
                                {
                                    $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_gray_lit.png' border='0'/>";
                                    $host_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                                }
                                else if ((int)$hprogress <=75) 
                                {
                                    $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_yellow_lit.png' border='0'/>";
                                    $host_progress .= "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                                }
                                else if ((int)$hprogress <=99) 
                                {
                                    $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img style='padding-right:7px;' src='./images/light_yellow_lit.png' border='0'/>";}
                                else 
                                {
                                    $host_progress = "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img src='./images/light_green_lit.png' border='0'/>";
                                    $host_progress .= "<img style='padding-right:7px;' src='./images/light_green_lit.png' border='0'/>";
                                }
                                
                                $tasks_info .= "<strong>".(string)$hprogress->host."</strong>|";
                            }
                            else 
                            {
                                $tasks_info .= md5((string)$hprogress->host).";";
                                $host_progress = (int)$hprogress;
                            }
                            
                            $tasks_info .= ((isset($results_by_ips[(string)$hprogress->host]["Serious"])) ? $results_by_ips[(string)$hprogress->host]["Serious"] : "0").";";
                            $tasks_info .= ((isset($results_by_ips[(string)$hprogress->host]["High"])) ? $results_by_ips[(string)$hprogress->host]["High"] : "0").";";
                            $tasks_info .= ((isset($results_by_ips[(string)$hprogress->host]["Medium"])) ? $results_by_ips[(string)$hprogress->host]["Medium"] : "0").";";
                            $tasks_info .= ((isset($results_by_ips[(string)$hprogress->host]["Low"])) ? $results_by_ips[(string)$hprogress->host]["Low"] : "0").";";
                            $tasks_info .= ((isset($results_by_ips[(string)$hprogress->host]["Info"])) ? $results_by_ips[(string)$hprogress->host]["Info"] : "0")."|";
                            
                            if(!$plain_text) 
                            {
                                $tasks_info .= "<div style='float:left;width:55%;text-align:right;padding:3px 0px 0px 0px'>".$host_progress."</div><div style='float:right;width:43%;text-align:left'>".trim((string)$hprogress)."%</div>";
                            }
                            else 
                            {
                                $tasks_info .= $host_progress;
                            }
                        
						$tasks_info_arr [] = $tasks_info;
						$i++;
					}
				}
			}
        }
        $session_key = "vuln_details_".$job_id.(($plain_text) ? "true" : "false");
        
        if( preg_match("/\d+\|\d+\|\d+\|\d+\|\d+/", "$cserious|$chole|$cwarning|$cinfo|$clog") ) 
        {
            $_SESSION[$session_key] = array("total" => "$cserious|$chole|$cwarning|$cinfo|$clog", "detail" => implode("#", $tasks_info_arr));
            return ( array("total" => "$cserious|$chole|$cwarning|$cinfo|$clog", "detail" => implode("#", $tasks_info_arr)) );
        }
        else if(!empty($_SESSION[$session_key]) && count($_SESSION[$session_key]) ==2 )
        {
            return $_SESSION[$session_key];
        }
        else 
        {
            return (array("total" => "0|0|0|0|0", "detail" => ""));
        }
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
        
        if($this->execute_command ("<delete_config config_id=\"$config_id\" />") === FALSE) 
        {
            $res["status"]  = "error";
            $res["message"] = $this->error_message;
            return $res;
        }
        
        return (array("status" => "OK", "message" => ""));
    }
    
    
    public function set_server_properties($ip, $port, $user, $password) 
    {
        $this->ip       = $ip;
        $this->port     = $port;
        $this->user     = $user;
        $this->password = $password;

        $this->user     = str_replace("'","'\"'\"'",$this->user);
        $this->password = str_replace("'","'\"'\"'",$this->password);
    }
    
    
    public function update_preferences_sqlite($uuid, $category, $pref_name, $pref_value) 
    {
        $sqlite = new Omp_sqlite("/var/lib/openvas/mgr/tasks.db");

        $sqlite->connect_db();

        // get "config id" from configs table

        $result    = $sqlite->execute_db("SELECT id FROM configs WHERE uuid='".$uuid."'");
        $row       = $sqlite->fetch_row_db($result);
        $config_id = $row["id"];
        


        // get preference id (id field)from config to modify the preference

        $result = $sqlite->execute_db("SELECT id FROM config_preferences WHERE config ='".$config_id."' AND name='".$pref_name."'");
        $row = $sqlite->fetch_row_db($result);
        $preference_id = $row["id"];

        // update if preference exists
        if($preference_id != "") 
        {
            $result = $sqlite->execute_db("UPDATE config_preferences SET value = '".$pref_value."' where config = '".$config_id."' AND name = '".$pref_name."'");
        }
        else 
        { // else insert
            $result = $sqlite->execute_db("SELECT MAX(id)+1 as next_id FROM config_preferences");
            $row = $sqlite->fetch_row_db($result);
            $next_id = $row["next_id"];
            
            $sqlite->execute_db("INSERT INTO config_preferences (id, config, type, name, value) VALUES ('".$next_id."', '".$config_id."', '".$category."', '".$pref_name."', '".$pref_value."')");
        }
    }
}

/* End of file omp.inc */
/* Location: ../include/classes/omp.inc */
