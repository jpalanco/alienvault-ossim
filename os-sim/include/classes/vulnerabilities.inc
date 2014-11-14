<?php
/**
* Vulnerabilities.inc
*
* File Vulnerabilities.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
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


/**
 * Function list:
 * - import_vuln_nessus_reports_from_ossim
 * - import_vuln_nessus_results_from_ossim
 * - function get_sensor_ids
 * - translate_targets
 */

class Vulnerabilities {

    function get_users_and_entities_filter($dbconn)
    {
        $items = array();
        
        if(Session::is_pro()) 
        {
            if(!Session::am_i_admin()) {
                $entities          = Acl::get_entities_to_assign($dbconn);
                $users             = array();
                $users_to_assign   = Session::get_users_to_assign($dbconn);

                foreach ($users_to_assign as $user) {
                    $users[] = $user->get_login();
                    }
                     
                $items = array_merge(array_keys($entities), $users);
        	}
        }
        else
        {
            if(!Session::am_i_admin()) {
                $items[] = Session::get_session_user();
            }
        }
        
        $sql_filter = (count($items)>0) ? "'".implode("', '",$items)."'" : "";
        
        return array($items, $sql_filter);
    }
    
    function scanner_type() {

        if( $_SESSION["scanner"] != "" ) {
            return $_SESSION["scanner"];
        }
        else 
		{
            require_once ('ossim_conf.inc');
            
            $scanner = $GLOBALS["CONF"]->db_conf["scanner_type"];

            if( preg_match("/omp/i", $scanner) ){
                $_SESSION["scanner"]="omp";
			}
            else if (preg_match("/openvas/i", $scanner)){
                $_SESSION["scanner"]="openvas";
			}
            else{
                $_SESSION["scanner"]="nessus";
			}
                
            return $_SESSION["scanner"];
        }
    }
    
    function is_allowed_credential($conn, $name, $login) {
        		            
		//Get credential filter
		$perms = "";
		if( !Session::am_i_admin() ) 
		{
			$users     = Session::get_users_to_assign($conn);
			$entities  = array_keys(Session::get_entities_to_assign($conn));
			
			$c_perms['0'] = '0';
			
			foreach($users as $k) {
				$c_perms[$k->get_login()] = $k->get_login();
			}
			
			foreach($entities as $entity_id) {
				$c_perms[$entity_id] = $entity_id;
			}
			
			$perms = "AND login IN ('".implode("', '", $c_perms)."')";
		}
        
        $query  = ossim_query("SELECT * FROM user_config WHERE category='credentials' AND name=? AND login=? $perms");
		$params = array($name, $login);
		
        if ( !$rs = & $conn->Execute($query, $params) ){
            return FALSE;
        }
        else{
            return ( count($rs->fields) >= 1 ) ? TRUE : FALSE;         
	    }
    }
    
    function check_credential($conn, $host_id_ip, $name, $login) {
        $debug          = false;
        $result         = array();
        $lcheck         = false;  // check with local sensor flag
        $remote_sensors = array(); 
        
		$local_ip       = trim(`grep ^admin_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
		if ($local_ip == "") {
		  $local_ip     = trim(`grep ^framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
		}
        
        if( preg_match("/[a-z0-9]{32}#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i", $host_id_ip) ) {
            list($host_id, $host_ip) = explode("#", $host_id_ip);
        
            if (!Session::hostAllowed($conn, $host_id)) {
                $result[] = array( 'type' => 'error', 'message' => _('Host not allowed') );
                return $result;
            }
            
            if (!Asset_host::is_in_db($conn, $host_id))
            {
                $result[] = array( 'type' => 'error', 'message' => _('Host not found') );
                return $result;
            }
            

            $lcheck = TRUE; //this is for temporal issue
        }
        else { // select all sensors
            $host_ip = $host_id_ip;  // host_id_ip in format xxx.xxx.xxx.xxx
        
            $lcheck = TRUE;
        }
        

        if ( ($lcheck || count($remote_sensors) > 0) ) {
            $cdata = Vulnerabilities::get_credential_data($conn, $name, $login);
            
            if ( $lcheck ) { // check with local sensor
                if( $cdata["login"]!= "" && $cdata["password"] !="" )
                {
                    $pass_file      = '/var/tmp/check_credetial_' . Util::uuid();
                
                    // check ssh credential with user/login
                    $cdata["password"] = html_entity_decode(mb_convert_encoding($cdata["password"], 'UTF-8'));
                    
                    $hfile = fopen($pass_file, 'w');
                    fwrite($hfile, $cdata["password"]);
                    fclose($hfile);               
     
                    $command = 'sshpass -f ' . $pass_file . ' ssh -o "StrictHostKeyChecking=no" -o "ConnectTimeout=5" '.$cdata["login"]. '@' . $host_ip . ' "echo \"ok\";exit"';
                    
                    $arr_out = array();
                    
                    exec($command, $arr_out);
                    
                    $out = implode("\n",$arr_out);
                    
                    if($debug) {
                        error_log("> in ".$command."\n", 3,"/tmp/credentials.log");
                        error_log("< out ".$out."\n", 3,"/tmp/credentials.log");
                    }
                    
                    if(preg_match("/Connection timed out/i", $out)) {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Host unreachable")."</span>");
                    }
                    else if(preg_match("/No route to host/i", $out)) {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("No route to host")."</span>");
                    }
                    else if(preg_match("/Authentication failed/i", $out)) {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Authentication failed")."</span>");
                    }
                    else if(preg_match("/ok/i", $out)) {
                        $result[] = array( "type" => "test_ok", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#5BB531;'>"._("Successfully logged in")."</span>");
                    }
                    else {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Wrong credentials")."</span>");
                    }
                    
                    // check smb credential with user/login
                    
                    $hfile = fopen($pass_file, 'w');
                    fwrite($hfile, 'username=' . $cdata["login"] . "\n");
                    fwrite($hfile, 'password=' . $cdata["password"] . "\n");
                    fclose($hfile);
                    
                    if (file_exists('/usr/bin/winexe') && file_exists('/etc/ossim/agent/smb.conf'))
                    {
                        $command = 'winexe -A ' . $pass_file . ' -s /etc/ossim/agent/smb.conf //' . $host_ip . ' "ipconfig" | grep ERROR';
                    }
                    else
                    {
                        $command = 'smbclient -A ' . $pass_file . ' -L ' . $host_ip . ' \\\\\\\\' . $host_ip . '\\\\';
                    }
                                                            
                    $arr_out    = array();
                    
                    $return_var = 0;
                    
                    exec($command, $arr_out, $return_var);
                    
                    $out = implode(" ",$arr_out);
                    
                    if($debug){
                        error_log("> in ".$command."\n", 3,"/tmp/credentials.log");
                        error_log("< out ".$out."\n", 3,"/tmp/credentials.log");
                    }
                   
                    if(preg_match("/HOST_UNREACHABLE/", $out)) {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Host unreachable")."</span>");
                    }
                    else if(preg_match("/LOGON_FAILURE|UNSUCCESSFUL|ACCESS_DENIED/", $out)) {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Wrong credentials")."</span>");
                    }
                    else if(preg_match("/CONNECTION_REFUSED/", $out)) {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Connection refused")."</span>");
                    }
                    else if(preg_match("/Sharename/", $out) || ($return_var && !$out)) {
                        $result[] = array( "type" => "test_ok", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#5BB531;'>"._("Successfully logged in")."</span>");
                    }
                    
                    if (file_exists($pass_file))
                    {
                        unlink($pass_file);
                    }
                }
                else if( $cdata["login"]!="" && file_exists($cdata["private_key_file"]) ) {
                    // check ssh credential with login/private key
                    $command = 'chmod 600 '.$cdata["private_key_file"].' && ssh -q -o "BatchMode=yes" -o "StrictHostKeyChecking=no" -o "ConnectTimeout=5" -i '.$cdata["private_key_file"].' '.$cdata["login"].'@'.$host_ip.' "echo 2>&1" && echo "OK" || echo "NOK"';
                    
                    $arr_out = array();
                    exec($command, $arr_out);
                    
                    @unlink($cdata["private_key_file"]);
                    
                    $out = implode(" ",$arr_out);
                    
                    if($debug) {
                        error_log("> in ".$command."\n", 3,"/tmp/credentials.log");
                        error_log("< out ".$out."\n", 3,"/tmp/credentials.log");
                    }
                    
                    if (!preg_match("/NOK/i", $out)) {
                        $result[] = array( "type" => "test_ok", "message" => _("Checking ssh User/Private Key"), "sensor" => "Local", "status" => "<span style='color:#5BB531;'>"._("Successfully logged in")."</span>");
                    }
                    else {
                        exec("nmap -sn -PE ".$host_ip, $arr_out);
                        
                        $out = implode(" ",$arr_out);
                        
                        if(preg_match("/Host seems down/i", $out)) {
                            $result[] = array( "type" => "test_ko", "message" => _("Checking ssh User/Private Key"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Host unreachable")."</span>");
                        }
                        else {
                            $result[] = array( "type" => "test_ko", "message" => _("Checking ssh User/Private Key"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Wrong credentials")."</span>");
                        }
                    }
                }
            }
            // else {   // check credential with remote sensors
            // }
        }
        else {
            $result[] = array( "type" => "error", "message" => _("Not found any sensor") );
        }
        
        return $result;
    }
    
    function get_credential_data($conn, $name, $login) {
        $uuid = Util::get_encryption_key();
        
        $query = ossim_query ( "SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue 
                                FROM user_config
                                WHERE category='credentials' AND name=? AND login=?" );
                                
        $conn->SetFetchMode(ADODB_FETCH_BOTH);

        $rs = $conn->Execute ( $query, array ($name, $login ) );
        if (!$rs) 
		{
            print $conn->ErrorMsg();
            
            return array( "login" => "", "password" => "", "private_key_file" => "");
        
        } 
		else if (!$rs->EOF) 
		{
            preg_match("/<login>(.*)<\/login>/", $rs->fields ["dvalue"], $found);
            
            if($found[1] != "") {
                $login = $found[1];
            }
            
            preg_match("/<password>(.*)<\/password>/", $rs->fields ["dvalue"], $found);
            
            if($found[1] != "") {
                $password = $found[1];
            }
            
            preg_match("/<private>([^<]+)<\/private>/m", $rs->fields ["dvalue"], $found);

            if($found[1] != "") {
                $private_key = $found[1];
                $file_name = "/tmp/cred_".$login."_".$name."_".time();
                file_put_contents($file_name, $private_key);
            }
            else{
                $file_name = "";
            }
            
            return array( "login" => $login, "password" => $password, "private_key_file" => $file_name );
        }
    }
    function get_credentials($conn, $protocol="") {
        
        $uuid = Util::get_encryption_key();
            
		//Get credential filter
		$perms = "";
		if( !Session::am_i_admin() ) 
		{
			$users        = Session::get_users_to_assign($conn);
			$entities     = array_keys(Session::get_entities_to_assign($conn));
			$c_perms["0"] = "0";
		
			foreach($users as $k) {
				$c_perms[$k->get_login()] = $k->get_login();
			}
			
			foreach($entities as $entity_id) {
				$c_perms[$entity_id] = $entity_id;
			}
			
			$perms = "AND login IN ('".implode("', '", $c_perms)."')";
		}
        
        $query = ossim_query("SELECT *, AES_DECRYPT(UNHEX(value),'".$uuid."') as dvalue FROM user_config WHERE category='credentials' $perms");
        
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
		
        if ( !$rs = & $conn->Execute($query) ){
            $list = array();
        }
        else 
        {
            $list = array();
            
			while (!$rs->EOF) 
            {
                $ctype = (preg_match("/\<key\>/", $rs->fields["dvalue"])) ? "Key" : "Password";
                
                if( $protocol == "" || $protocol == "ssh" || ($ctype == "Password" && $protocol == "smb") ) {
                    $list[] = array("login" => $rs->fields["login"], "name" => $rs->fields["name"], "type" => $ctype);
                }

                $rs->MoveNext();
            }
        }
		
        return $list;
    }
	
    function get_omp_running_scans($dbconn) 
    {
        $tz   = Util::get_timezone();        
        
        $out = 0;
        
        $perms = array();
        
        if(!Session::am_i_admin()) 
        {
            $users    = Session::get_users_to_assign($dbconn);
            $entities = Session::get_entities_to_assign($dbconn);
        
            foreach($users as $k => $v) 
            {
                $perms[] = $v->get_login();
            }
            foreach ($entities as $k => $v) 
            {
                $perms[] = $k;
            }
            
            $perm_filter = "AND t1.username in ('".implode("','", $perms)."')";
        }
        else 
        {
            $perm_filter = "";
        }
        
         $query = "SELECT t1.failed_attempts, t1.meth_Wcheck, t6.name as profile, t1.scan_NEXT, t1.meth_TARGET, t1.scan_PID, t1.id, t1.name, t1.username, t1.scan_START, t1.scan_END, 
                          t1.scan_SUBMIT, t1.status, t1.notify, t3.hostname, t1.credentials
                          FROM vuln_jobs t1
                            LEFT JOIN vuln_nessus_servers t3 on t1.scan_ASSIGNED = t3.id
                            LEFT JOIN vuln_nessus_reports t5 on t1.report_id = t5.report_id 
                            LEFT JOIN vuln_nessus_settings t6 on t1.meth_VSET=t6.id
                            WHERE t1.status='R' $perm_filter
                            ORDER BY t1.id DESC";
                            
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $dbconn->Execute($query);
        
        $sInfo .= " <script type='text/javascript'>
                       function show_details(id) {
                            $('.'+id).toggle();
                        }
                    </script>
                  ";
        
        $sInfo .= "<table style='margin-top:5px;' class='w100 transparent'>
                        <tr><td class='sec_title center'>"._("Running Scans")."</td></tr>
                   </table>";

        $sInfo .= "<table class='table_list t_width'>";
        
        $out    = 0;
        
        if(!$result->EOF) 
        {
            while (!$result->EOF) 
            {
                $out++;
                
                if($out == 1) 
                {
                    $sInfo .= "#HEADER#";
                }
                list ($failed_attempts, $meth_Wcheck, $profile, $scan_next, $meth_TARGET,
                    $scan_PID, $nid, $nname, $nusername, $start_date, $end_date, $submit_date, $nstatus,
                    $nnotify, $shostname, $credentials) = $result->fields;

                $omp = new Omp();
                $task_detail = $omp->get_task_detail_by_id("Running|Paused|Pause Requested|Requested", $nid);

                $targets_to_resolve = explode("\n",$meth_TARGET);
                $ttargets           = array();
        
                foreach ($targets_to_resolve as $id_ip) 
                {
                    if (preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+\/\d{1,2}$/i", $id_ip, $found))
                    {
                        if (Asset_net::is_in_db($dbconn, $found[1]))
                        {
							$cidr = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
							
							$net_name     = Asset_net::get_name_by_id($dbconn, $found[1]);
							
                            $ttargets[]   = $cidr . ' (' . $net_name . ')';
                            
							$aname[$cidr] =  '(' . $net_name . ')'; // to display names in hidden tr
                        }
                        else
                        {
                            $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
                        }
                    }
                    else if(preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+$/i", $id_ip, $found))
                    {
                        if(Asset_host::is_in_db($dbconn, $found[1]))
                        {
							$ip = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
							
							$host_name     = Asset_host::get_name_by_id($dbconn, $found[1]);
							
                            $ttargets[] = $ip . ' (' . $host_name . ')';
                            
							$aname[$ip] =  '(' . $host_name . ')'; // to display names in hidden tr
                        }
                        else 
                        {
							$ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
                        }
                    }
                    else 
                    {
                        $ttargets[] = $id_ip;
                    }
                }
    
                $meth_TARGET = implode("<br/>", $ttargets);
        
                $ustart_date = $start_date; // save start_date in UTC to display scan time
        
                if($tz != 0) 
                {
                    if(!is_null($submit_date)) 
                    {
                        $submit_date = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($submit_date)+(3600*$tz));
                    }
                    
                    if(!is_null($start_date)) 
                    {
                        $start_date  = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($start_date)+(3600*$tz));
                    }
                    
                    if(!is_null($end_date)) 
                    {
                        $end_date    = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($end_date)+(3600*$tz));
                    }
                    
                    if(!is_null($scan_next)) 
                    {
                        $scan_next   = gmdate("YmdHis",Util::get_utc_unixtime($scan_next)+(3600*$tz));
                    }
                }

                if ($nnotify != '')
                {
                    if (Av_sensor::is_in_db($dbconn, $nnotify))
                    {
                        $shostname = Av_sensor::get_name_by_id($dbconn, $nnotify);
                    }
                    else
                    {
                        $shostname = _("unknown");
                    }
                }
                
                // check job status
                $tstatus = array("status"=>"", "progress"=>"");

                if (preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/", $meth_Wcheck, $found)) 
                {
                
                    $sensor_id = Av_sensor::get_id_by_ip($dbconn, $found[1]);
                    
                    if( $sensor_id != '' ) 
                    {
                    
                        $sensor_object = new Av_sensor($sensor_id);
        
                        $sensor_object->load_from_db($dbconn);
                        
                        $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                
                        $port     = $ov_credentials['port'];
                        
                        $user     = $ov_credentials['user'];
                        
                        $password = $ov_credentials['password'];
                        
                        $omp = new Omp($sensor_object->get_ip(), $port, $user, $password);
                
                        $tstatus = $omp->get_task_status($nid);
                    }
                }
            
                if ($tstatus['status'] != '')
                {
                    $job_status = "<strong>"._("Job Status")."</strong>: ".$tstatus["status"].(($tstatus["progress"]!=-1 && $tstatus["status"]!="Requested")? " ".$tstatus["progress"]."%":"")."<br>";
                }
                    
                if (preg_match("/requested$/i", $tstatus["status"])) { $image = "<img src=\"./images/requested.png\" boder=\"0\" title=\""._("Requested")."\" alt=\""._("Requested")."\" align=\"absmiddle\">"; }
                elseif (preg_match("/^pause/i", $tstatus["status"])) { $image = "<img src=\"./images/paused.png\" boder=\"0\" title=\""._("Paused")."\" alt=\""._("Paused")."\" align=\"absmiddle\">"; }
                elseif ($nstatus=="C" || $nstatus=="G") { $image = "<img src=\"./images/complete.png\" boder=\"0\" title=\""._("Completed")."\" alt=\""._("Completed")."\" align=\"absmiddle\">"; }
                elseif ($nstatus=="R") { $image = "<img src=\"./images/running.png\" boder=\"0\" title=\""._("Running")."\" alt=\""._("Running")."\" align=\"absmiddle\">"; }
                elseif ($nstatus=="S") { $image = "<img src=\"./images/sched.png\" boder=\"0\" title=\""._("Schedule")."\" alt=\""._("Schedule")."\" align=\"absmiddle\">"; }
                elseif ($nstatus=="F") { $image = "<img src=\"./images/warning.png\" boder=\"0\" title=\""._("Failed")."\" alt=\""._("Failed")."\" align=\"absmiddle\">"; } 
                elseif ($nstatus=="T") { $image = "<img src=\"./images/timeout.png\" boder=\"0\" title=\""._("Timeout expired")."\" alt=\""._("Timeout expired")."\" align=\"absmiddle\">"; }

                if(valid_hex32($nusername)) 
                { 
                    $nusername = Session::get_entity_name($dbconn, $nusername);
                }
            
                $sInfo .= "<tr onclick='show_details(\"task$nid\")' class='tasks cstatus' id='$nid'>"; // job tr
                
				$credentialm = array();
				if( $credentials != "") {
					list($ssh_credential, $smb_credential) = explode("|", $credentials);
					if($ssh_credential != "") {
						$ssh_credential = str_replace("#", " (", $ssh_credential);
						$ssh_credential .= ")"; 
						$credentialm[] =  "<strong>"._("SSH: ") . "</strong>" . $ssh_credential;
					}
					if($smb_credential != "") {
						$smb_credential = str_replace("#", " (", $smb_credential);
						$smb_credential .= ")";
						$credentialm[] =  "<strong>"._("SMB: ") ."</strong>" . $smb_credential;
					}
				}
				
				$tooltip_message = "<strong>"._("Server").":</strong> $shostname
									<br>$job_status<strong>"._("Job ID").":</strong> $nid
									<br><strong>"._("Profile").":</strong> $profile
									<br>".((count($credentialm)>0) ? implode("<br/>", $credentialm)."<br/>" : "")."
									<strong>"._("Failed attempts").":</strong> $failed_attempts
									<br><strong>"._("Targets").":</strong>
									<br>".$meth_TARGET.(($meth_Wcheck!="")? "<strong>"._("Messages").":</strong>
									<br>".nl2br(trim($meth_Wcheck)) : "");
				
                $sInfo .= "<td style='text-align:left;width:15%;padding:3px 0px 3px 3px;'>$image&nbsp;<span class='tip' title='$tooltip_message'>".html_entity_decode($nname)."</span></td>";
                
                $sInfo .= "<td style='width:12%;'>".$nusername."</td>";
                
                if ( $start_date ) {
                    $curren_date = gmdate("Y-m-d H:i:s");
                    $nmins = "RUN >" . datediff("n", $curren_date, $ustart_date );
                    $nmins .= " mins";
                }
                
                // Scan time
                $sInfo .= "<td style='width:12%;'>";
                
                $time_text  = "<strong>"._("Submit date").":</strong> $submit_date";
                $time_text .= "<br/><strong>"._("Start date").":</strong> $start_date";
                
                $sInfo .= "<div class='tip' title='$time_text'>";
                $sInfo .= ( $nmins =="0 mins" ) ? "< 1 min" : "$nmins";
                $sInfo .= "</div>";
                
                $sInfo .= "</td>";
                
                preg_match("/(\d+)\|(\d+)\|(\d+)\|(\d+)\|(\d+)/", $task_detail["total"], $found);
                $sInfo .= "<td style='width:46%;'>";                
                $sInfo .= "<div id='$nid-seri' style='width:11%;clear:both;float:left;font-size:10px;padding-top:2px'>".(($found[1]!="") ? $found[1] : "")."</div>";
                $sInfo .= "<div id='$nid-high' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($found[2]!="") ? $found[2] : "")."</div>";
                $sInfo .= "<div id='$nid-medi' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($found[3]!="") ? $found[3] : "")."</div>";
                $sInfo .= "<div id='$nid-low' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($found[4]!="") ? $found[4] : "")."</div>";
                $sInfo .= "<div id='$nid-info' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($found[5]!="") ? $found[5] : "")."</div>";
                $sInfo .= "<div style='width:44%;float:left;padding:0px 0px 0px 0px' id='nessus_threads_$nid'></div>";
                $sInfo .= "</td>";
                    
                $sInfo .= "<td style='text-align:center;width:15%;'>";
                if ($tstatus["status"]!="Done" && $tstatus["status"]!="Stopped" && $tstatus["status"]!=""){
                    $task_status = $tstatus["status"];
                    
                    // pause button
                    if($task_status=="Running" || $task_status=="Requested" && !preg_match("/.*\srequested/i", $task_status)) { // pause button
                        $sInfo .= "<img align='absmiddle' class='manageJob' id='pause_task#$nid' title='"._("Pause current scan job")."' alt='"._("Pause current scan job")."' src='./images/pause_task.png'  border='0'>";
                    }
                    else {
                        $sInfo .= "<img align='absmiddle' class='bdisabled' src='./images/pause_task.png' border=0></a>";
                    }
                
                    // resume button
                    if( preg_match("/^pause.*/i", $task_status) && !preg_match("/.*\srequested/i", $task_status)) {
                        $sInfo .= "<img align='absmiddle' class='manageJob' id='resume_task#$nid' title='"._("Resume scan job")."' alt='"._("Resume scan job")."' src='./images/resume_task.png'  border='0'>";
                    }
                    else {
                        $sInfo .= "<img align='absmiddle' class='bdisabled' src='./images/resume_task.png'  border='0'></a>";
                    }
                
                    // stop button
                    if($task_status=="Running" || $task_status=="Paused" && !preg_match("/.*\srequested/i", $task_status)) { // onclick='changeTaskStatus(\"$nid\",\"stop_task\");'
                        $sInfo .= "<img align='absmiddle' class='manageJob' id='stop_task#$nid' title='"._("Stop current scan job")."' alt='"._("Stop current scan job")."' src='./images/stop_task.png'  border='0'>";
                    }
                    else {
                        $sInfo .= "<img align='absmiddle' class='bdisabled' src='./images/stop_task.png' border='0'></a>";
                    }
                }
                else {
                    $sInfo .= "<span style='font-size:10px'>"._("Requesting task status...")."</span>";
                }

                $sInfo .= "<span style='display:none' id='changing_task_status_$nid'><img align='absmiddle' src='../pixmaps/loading.gif' width='16' border='0'></span></td>";

                $sInfo .= "</td></tr>";
                
                if( $task_detail["detail"] !="" ) {
                    $lines = explode("#", $task_detail["detail"]);
                    $i = 1;
                    foreach ($lines as $line) if ($line !="") { 
                        preg_match("/(.*)\|(.*)\|(.*)/", $line, $found);
                        preg_match("/(\d+);(\d+);(\d+);(\d+);(\d+)/", $found[2], $nvulns);
                        $sInfo .= "<tr style='display:none;' class='task$nid'>";
                        
                        preg_match("/(\d+\.\d+\.\d+\.\d+)/", $found[1], $data);
                        
                        $md5IP  = md5($data[1]); 
                        
                        $sInfo .= "<td style='text-align:right;' class='transparent ip_detail'><img src='../pixmaps/theme/".((count($lines)!=$i) ? "bmltL_nes2.png": "bmltL_ne2.png")."' /></td>";
                        $sInfo .= "<td colspan='2' class='ip_detail' style='text-align:left;padding:0px 0px 0px 15px;'>".$found[1]." ".$aname[strip_tags($found[1])]."</td>";
                        $sInfo .= "<td class='ip_detail'>";
                        $sInfo .= "<div id='$nid-$md5IP-seri' style='width:11%;clear:both;float:left;font-size:10px;padding-top:2px'>".(($nvulns[1]!="") ? $nvulns[1] : "")."</div>";
                        $sInfo .= "<div id='$nid-$md5IP-high' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($nvulns[2]!="") ? $nvulns[2] : "")."</div>";
                        $sInfo .= "<div id='$nid-$md5IP-medi' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($nvulns[3]!="") ? $nvulns[3] : "")."</div>";
                        $sInfo .= "<div id='$nid-$md5IP-low' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($nvulns[4]!="") ? $nvulns[4] : "")."</div>";
                        $sInfo .= "<div id='$nid-$md5IP-info' style='width:11%;float:left;font-size:10px;padding-top:3px'>".(($nvulns[5]!="") ? $nvulns[5] : "")."</div>";
                        $sInfo .= "<div id='$nid-$md5IP-per' style='width:44%;float:left;text-align:center'>".$found[3]."</div>";
                        $sInfo .= "<td class='ip_detail'>&nbsp;</td>";
                        $sInfo .= "</tr>";
                        $i++;
                    }
                    
                    if (count($lines)%2 != 0)
                    {
                        $sInfo .= "<tr style='display:none;'><td colspan='4'></td></tr>";
                    }
                }
                $result->MoveNext();
            }
        }
        
        if($out==0){
            $sInfo .= "<tr><td colspan='5' class='empty_results'>"._("No Runnings Scans")."</td></tr>";
            $sInfo = preg_replace("/#HEADER#/", "", $sInfo);
        }
        else {
            $header ="<tr>
                        <th>"._("Job Name")."</th>
                        <th>"._("Owner")."</th>
                        <th>"._("Scan Time")."</th>
                        <th style='padding: 0px !important;'>
                            <div style='width:11%;float:left;'><img src='./images/serious.png'/></div>
                            <div style='width:11%;float:left;'><img src='./images/high.png'/></div>
                            <div style='width:11%;float:left;'><img src='./images/medium.png'/></div>
                            <div style='width:11%;float:left;'><img src='./images/low.png'/></div>
                            <div style='width:11%;float:left;'><img src='./images/inf.png'/></div>
                            <div style='width:44%;float:left;'>"._("Vulns. Trend / Scan Progress")."</div>
                        </th>
                        <th>"._("Action")."</th>
                        </tr>";
            $sInfo = preg_replace("/#HEADER#/", $header, $sInfo);
        }
        $sInfo .= "</table>";
        return $sInfo;
    }    
    function import_vuln_nessus_reports_from_ossim($conn, $report_id, $return) {
        // Insert data in vuln_nessus_reports
        $entity  = $_SESSION['_entity'];
        
        $vnr_query = "SELECT * FROM ossim.vuln_nessus_reports WHERE report_id=?";
        
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        if (!$vnrq = $conn->Execute($vnr_query, array($report_id))) 
        {
            $return['errors'][] = $conn->ErrorMsg();
            return $return;
            
        }
        else if(!empty($vnrq->fields["name"]))
        {
            $vnr_query = "INSERT INTO alienvault.vuln_nessus_reports ( username, name, sid, scantime, report_type, scantype, report_key, note, failed, results_sent, deleted ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
            
            // translate entities IDs
            if(preg_match("/^\d+$/",$vnrq->fields["username"]) ) {
                if($entity[$vnrq->fields["username"]]!=""){
                    $vnrq->fields["username"] = $entity[$vnrq->fields["username"]];
                }
                else {
                    $return['warnings'][] = "Couldn't resolve entity ID: ".$vnrq->fields["username"];
                    $vnrq->fields["username"] = "admin";
                }
            }
            
            $params = array(
                $vnrq->fields["username"],
                $vnrq->fields["name"],
                $vnrq->fields["sid"],
                $vnrq->fields["scantime"],
                $vnrq->fields["report_type"],
                $vnrq->fields["scantype"],
                $vnrq->fields["report_key"],
                $vnrq->fields["note"],
                $vnrq->fields["failed"],
                $vnrq->fields["results_sent"],
                $vnrq->fields["deleted"]
            );
    
            if ( !$sq = $conn->Execute($vnr_query, $params) ) {
                $return['errors'][] = $conn->ErrorMsg();
                return array("return" => $return, "report_id" => -1);
            }
            
            $vnr_query = "SELECT report_id FROM vuln_nessus_reports WHERE scantime=? AND report_key=? ORDER BY scantime DESC LIMIT 1";
            
            if ( !$sq = $conn->Execute($vnr_query, array($vnrq->fields["scantime"], $vnrq->fields["report_key"])) ) {
                $return['errors'][] = $conn->ErrorMsg();
                return array("return" => $return, "report_id" => -1);
            }
            else {
                if(!preg_match("/^\d+$/",$sq->fields["report_id"])) {
                    $return['errors'][] = _("Error getting new report id");
                    $new_report_id      = -1;
                }
                else {
                    $new_report_id = $sq->fields["report_id"];
                }
            }
            
        }
        
        return array("return" => $return, "report_id" => $new_report_id);
    }
    function import_vuln_nessus_results_from_ossim($conn, $report_id, $new_report_id, $return) {
    
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
    
        $ctx     = $_SESSION['_ctx'];
        
        // Insert data in vuln_nessus_results
        $vnr_query = "SELECT * FROM ossim.vuln_nessus_results WHERE report_id=?";
        if (!$vnrq = $conn->Execute($vnr_query, array($report_id))) 
        {
            $return['errors'][] = $conn->ErrorMsg();
            return $return;
            
        }
        else
        {
            while (!$vnrq->EOF) {
                $vnr_query = "INSERT INTO vuln_nessus_results (report_id, scantime, record_type, hostIP, ctx, hostname, service, port, protocol, app, scriptid, risk, msg, falsepositive) VALUES
                                (?, ?, ?, ?, UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = array(
                    $new_report_id,
                    $vnrq->fields["scantime"],
                    $vnrq->fields["record_type"],
                    $vnrq->fields["hostIP"],
                    $ctx,
                    $vnrq->fields["hostname"],
                    $vnrq->fields["service"],
                    $vnrq->fields["port"],
                    $vnrq->fields["protocol"],
                    $vnrq->fields["app"],
                    $vnrq->fields["scriptid"],
                    $vnrq->fields["risk"],
                    $vnrq->fields["msg"],
                    $vnrq->fields["falsepositive"]
                );
        
                if (!$conn->Execute($vnr_query, $params)) 
                {
                    $return['errors'][] = $conn->ErrorMsg();
                    
                    return $return;
                }
                
                $vnrq->MoveNext();
            }
        }
        
        return $return;
    }
    
    function get_sensor_ids($conn, $scan_assigned, $return) {
    
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
    
        $sensors = $_SESSION['_sensors'];
    
        // find new scan_ASSIGNED field (scan_ASSIGNED is the sensor ID)
        if( is_numeric(intval($scan_assigned)) ) {
            $server_query = "SELECT hostname FROM ossim.vuln_nessus_servers WHERE id=?";
            if ( !$sq = $conn->Execute($server_query, array(intval($scan_assigned))) ) {
                $return['errors'][] = $conn->ErrorMsg();
                return $return;
            }
            else if(!empty($sq->fields["hostname"])) {
                $scan_assigned = $sensors[$sq->fields["hostname"]]; 
            }
        }

        // find new scan_SERVER field (scan_SERVER is the id field in vuln_nessus_servers)
        if(preg_match("/[\da-f]{32}/i", $scan_assigned)) {
            $server_query = "SELECT * FROM alienvault.vuln_nessus_servers WHERE hostname=?";
            if ( !$sq = $conn->Execute($server_query, array($scan_assigned)) ) {
                $return['errors'][] = $conn->ErrorMsg();
                return $return;
            }
            else {
                $scan_server = $sq->fields["id"]; 
            }
        }
        return array("scan_assigned" => $scan_assigned, "scan_server" => $scan_server);
    }
    
    function translate_targets($conn, $meth_TARGET, $ctx) {
        
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
        
        $new_meth_target = array();
        $targets         = explode("\n", $meth_TARGET);
        
        foreach ($targets as $target) if($target != '')
        {
            $target   = trim($target);
            $asset_id = '';
            
            if (preg_match("/^\d+\.\d+\.\d+\.\d+$/",$target))
            {
                $asset_id = key(Asset_host::get_id_by_ips($conn, $target, $ctx));
            }
            else if (preg_match("/^\d+\.\d+\.\d+\.\d+$\/\d{1,2}/",$target))
            {
                $asset_id = key(Asset_net::get_id_by_ips($conn, $target, $ctx));
            }
            
            $new_meth_target[] = ($asset_id != '') ? $asset_id . '#' . $target : $target;
        }
        
        return implode("\n", $new_meth_target);
    }
    
    function get_latest_vulns($conn,$ip) {
    
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
    
        $query = ossim_query("SELECT count(distinct service,scantime,risk,scriptid,msg) as total FROM vuln_nessus_latest_results WHERE hostIP=? AND risk<7");

        if (!$rs = & $conn->Execute($query,array($ip))) 
        {
            print $conn->ErrorMsg();
        }
        else
        {
            return $rs->fields["total"];
        }
        return 0;
    }
    
    function get_latest_vulns_data($conn,$ip) {
    
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
    
        $list = array();
        $query = ossim_query("SELECT distinct l.service,l.scantime,l.risk,l.scriptid,l.msg,p.name FROM vuln_nessus_latest_results l LEFT JOIN vuln_nessus_plugins p ON p.id=l.scriptid WHERE hostIP=? AND l.risk<7");

        if (!$rs = & $conn->Execute($query,array($ip))) 
        {
            print $conn->ErrorMsg();
        }
        else
        {   
            while (!$rs->EOF) {
                $arr = array();
                $arr["service"]  = $rs->fields["service"];
                $arr["date"]     = gmdate("Y-m-d H:i:s",strtotime($rs->fields["scantime"]));
                $arr["risk"]     = $rs->fields["risk"];
                $arr["pluginid"] = $rs->fields["scriptid"];
                $arr["plugin"]   = $rs->fields["name"];
                $list[]          = $arr;
                $rs->MoveNext();
            }
        }
        return $list;
    }

    function saveplugins_in_db($dbconn, $plugins, $sid, $fam, $cve, $action) {

        $result = array();
        
        $result["message"] = "";
        $result["status"]  = "OK";
        
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
        if ($action=="Update") {
            if ($fam!="") {
                if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='N' where sid=$sid and family=$fam") ) {
                    $result["status"]  = "error";
                    $result["message"] = $dbconn->ErrorMsg();
                    
                    return $result;
                }

                foreach ($plugins as $plugin) {
                    if(is_numeric(trim($plugin))) {
                        if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='Y' where ID=".trim($plugin)." and sid=$sid") ) {
                            $result["status"]  = "error";
                            $result["message"] = $dbconn->ErrorMsg();
                            
                            return $result;
                        }
                    }
                }
                
                return $result;
            }
            else{
                if (! $rs = & $dbconn->Execute ("SELECT id FROM vuln_nessus_plugins WHERE cve_id LIKE '%$cve%'")) {
                    $result["status"]  = "error";
                    $result["message"] = $dbconn->ErrorMsg();
                } else {
                    while ( ! $rs->EOF ) {
                        if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='N' where id=".$rs->fields['id']." and sid=$sid") ) {
                            $result["status"]  = "error";
                            $result["message"] = $dbconn->ErrorMsg();
                            
                            return $result;
                        }
                        $rs->MoveNext();
                    }
                    foreach ($plugins as $plugin) {
                        if(is_numeric(trim($plugin))) {
                            if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='Y' where ID=".trim($plugin)." and sid=$sid") ) {
                                $result["status"]  = "error";
                                $result["message"] = $dbconn->ErrorMsg();
                                
                                return $result;
                            }
                        }
                    }
                }
                
                return $result;
            }
       }

        if ($action=="Enable All") {
            if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='Y' where sid=$sid") ) {
                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();
            }
            
            return $result;
        }
       
        if ($action=="Enable Non DOS") {
            
            if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='Y' where sid=$sid") ) {
                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();
               
                return $result;
            }

            if (! $rs = & $dbconn->Execute ( "SELECT id FROM vuln_nessus_category WHERE name IN ('denial', 'flood', 'destructive_attack','kill_host')" ) ) {
                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();
               
                return $result;
            } else {
                while(! $rs->EOF) {
                    if ( !$dbconn->Execute("UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND category=".$rs->fields['id']) ) {
                        $result["status"]  = "error";
                        $result["message"] = $dbconn->ErrorMsg();
                       
                        return $result;
                    }
                    $rs->MoveNext();
                }
            }

            return $result;
        }
        
        if ($action=="Disable All") {
            if ( !$dbconn->Execute("Update vuln_nessus_settings_plugins set enabled='N' where sid=$sid") ) {
                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();
            }

            return $result;
        }
    }
    
    
    function saveprefs_in_db($dbconn, $sid, $data)
    {
        // get the profile prefs for use later
        $sql = "SELECT t.nessus_id,
            t.type, t.category
            FROM vuln_nessus_preferences_defaults t
            LEFT JOIN vuln_nessus_settings_preferences n
            ON t.nessus_id = n.nessus_id
            and n.sid = $sid
            order by category desc, nessusgroup, nessus_id";

        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $dbconn->execute($sql);
        
        if($result === FALSE)
        { 
            // SQL error
            return array('status' => 'error', 'message' => $dbconn->ErrorMsg());
        }

        $counter = 0;

        $uuid = Util::get_encryption_key();

        while(!$result->EOF)
		{
			$counter++;
			
			$vname = "form".$counter;
			
			if (isset($data[$vname])) 
			{
				$$vname = $data[$vname];
			}
			else
			{
				$$vname = '';
			}

			list ($nessus_id, $type, $category) = $result->fields;

			$result_update = Vulnerabilities::updatedb($nessus_id, $$vname, $dbconn, $type, $category, $sid, $uuid);

			if($result_update['status'] == 'error')
			{
				return $result_update;
			}

			$result->MoveNext();
		} // end while loop

        /*
        * find all records in the vuln_nessus_settings_preferences table that
        * have no matching value in vuln_nessus_preferences_defaults
        * and delete them from vuln_nessus_preferences
        */

        $sql = "select n.nessus_id
            from vuln_nessus_settings_preferences n
            left join vuln_nessus_preferences_defaults t
            on n.nessus_id = t.nessus_id
            where t.nessus_id is null";

        $result = $dbconn->execute($sql);

        while(!$result->EOF) 
        {
            list ($pleasedeleteme) = $result->fields;
            $sql2 = "DELETE from vuln_nessus_settings_preferences where nessus_id = \"$pleasedeleteme\"";
            $dbconn->execute($sql2);
            
            $result->MoveNext();
        }

        return array('status' => 'OK', 'message' => '');
	}
	
	
	function updatedb($nessus_id, $fieldvalue, $dbconn, $type, $category, $sid, $uuid) {
	
	    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
		if ($type=="C" and $fieldvalue=="") {
			$fieldvalue="no";
		}

		$sql = "SELECT count(*) FROM vuln_nessus_settings_preferences WHERE sid = $sid AND nessus_id = \"$nessus_id\"";
		$result=$dbconn->execute($sql);
		
	    if($result === false) { // SQL error
			return ( array( "status" => "error", "message" => $dbconn->ErrorMsg() ) );
	    }

		list($existing)=$result->fields;
    
		if ($existing == 0) {
			# Do an insert statement
			$sql_field_value = ( $type == "P" && !empty($fieldvalue)) ? "AES_ENCRYPT('$fieldvalue','$uuid')" : "'$fieldvalue'";
        
			$sql = "INSERT vuln_nessus_settings_preferences SET nessus_id = '". addslashes($nessus_id) ."', value=$sql_field_value, type='$type', category='$category', sid=$sid";
		} 
		else 
		{
			if ($type == "P" && Util::is_fake_pass($fieldvalue) )
				$sql = "UPDATE vuln_nessus_settings_preferences SET type='$type', category='$category'  WHERE nessus_id = '". addslashes($nessus_id) ."' AND sid = $sid";
			else {
				$sql_field_value = ( $type == "P" && !empty($fieldvalue) ) ? "AES_ENCRYPT('$fieldvalue','$uuid')" : "'$fieldvalue'";
				$sql  = "UPDATE vuln_nessus_settings_preferences SET value=$sql_field_value, type='$type', category='$category' WHERE nessus_id = '". addslashes($nessus_id) ."' AND sid = $sid";
			}
		}
    
		$result=$dbconn->execute($sql);
		
	    if($result === false) { // SQL error
			return ( array( "status" => "error", "message" => $dbconn->ErrorMsg() ) );
	    }
	    
	    return ( array( "status" => "OK", "message" => "" ) );
	}
	
		
	function saveprefs_in_sensor($dbconn, $sensor_id, $sid) {
		
        $sensor_object = new Av_sensor($sensor_id);
        
        $sensor_object->load_from_db($dbconn);
        
        $check_result  = $sensor_object->check_vs_connection($dbconn);

		if( $check_result != '' ) {
			$result['status']  = 'error';
			$result['message'] = $check_result;
				
			return $result;
		}

        $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                
        $port     = $ov_credentials['port'];
        $user     = $ov_credentials['user'];
        $password = $ov_credentials['password']; 
        
        $omp    = new Omp($sensor_object->get_ip(), $port, $user, $password);

        $result = $omp->set_preferences($sid);

		return $result;
		
    }
    
    function saveplugins_in_sensor($dbconn, $sensor_id, $sid, $fam = '', $action = '') {
                
        $sensor_object = new Av_sensor($sensor_id);
        
        $sensor_object->load_from_db($dbconn);
        
        $check_result  = $sensor_object->check_vs_connection($dbconn);

		if( $check_result != '' ) {
            $result['status']  = 'error';
            $result['message'] = $check_result;
			
			return $result;
        }
        
        if($action != 'Update') {
            unset($fam);
        }
        
        $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                
        $port     = $ov_credentials['port'];
        $user     = $ov_credentials['user'];
        $password = $ov_credentials['password'];
        
        
        $omp    = new Omp($sensor_object->get_ip(), $port, $user, $password);
        $result = $omp->set_plugins_by_family($sid, $fam);
        
        return $result;
    
    }
    
    
    function update_db_profile($dbconn, $sid, $sname, $sdescription, $username, $sautoenable, $tracker, $data) 
    {    
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        $host_tracker = 0;

        if($tracker == 'on') 
        { 
            $host_tracker = 1;
        }
        
        $params = array($sname, $sdescription, $sautoenable, $host_tracker, $username, $sid);
        
        $query  = "UPDATE vuln_nessus_settings 
                   SET  name = ?,
                        description = ?, 
                        autoenable = ?,
                        update_host_tracker = ?,
                        owner = ?
                   WHERE id = ?";
        
        $result = $dbconn->execute($query, $params);

        while (list($key, $value) = each($data))
        {
             $value = Util::htmlentities(mysql_real_escape_string(trim($value)), ENT_QUOTES);

             if (substr($key, 0, 2) == "f_") 
             {
                $type  = substr($key,0,1);
                $key   = substr($key, 2);
                $query = "update vuln_nessus_settings_family 
                          set status=$value 
                          where sid=$sid and fid=$key";
                
                $results = $dbconn->Execute($query);

             } 
             elseif(substr($key, 0, 2) == "c_") 
             {
                $type    = substr($key,0,1);
                $key     = substr($key, 2);
                $query   = "update vuln_nessus_settings_category set status=$value where sid=$sid and cid=$key";
                $results = $dbconn->Execute($query);
             }
          }

          if ($sautoenable == "C") 
          {
             $query  = "select t1.cid, t1.status from vuln_nessus_settings_category as t1, vuln_nessus_category as t2 where sid=$sid";
             $result = $dbconn->execute($query);

             while (!$result->EOF) 
             {
                list($cid, $catstatus) = $result->fields;
                
                if ($catstatus == 4) 
                {
                   $query1  = "update vuln_nessus_settings_plugins set enabled='N' where category=$cid and sid=$sid";
                   $result1 = $dbconn->execute($query1);
                } 
                elseif ($catstatus == 1) 
                {
                   $query1="update vuln_nessus_settings_plugins set enabled='Y' where category=$cid and sid=$sid";
                   $result1=$dbconn->execute($query1);
                }
                
                $result->MoveNext();
             }
          } 
          elseif($sautoenable == "F") 
          {
             $query  = "select t1.fid, t1.status from vuln_nessus_settings_family as t1, vuln_nessus_family as t2 where sid=$sid";
             $result = $dbconn->execute($query);

             while (!$result->EOF) 
             {
                list($fid, $catstatus) = $result->fields;
                
                if ($catstatus == 4) 
                {
                   $query1="update vuln_nessus_settings_plugins set enabled='N' where family=$fid and sid=$sid";
                   $result1=$dbconn->execute($query1);
                } 
                elseif ($catstatus == 1) 
                {
                   $query1="update vuln_nessus_settings_plugins set enabled='Y' where family=$fid and sid=$sid";
                   $result1=$dbconn->execute($query1);
                }
                
                $result->MoveNext();
             }
          }
    
        return array ('status' => 'OK', 'message' => '');
    }
    

    function create_db_profile($dbconn, $sname, $sdescription, $sautoenable, $username, $cloneid, $tracker, $data ) {
    
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        if($cloneid != "") {
            // get the data from the original profile
            $query = "SELECT autoenable, type FROM vuln_nessus_settings WHERE id = $cloneid";
            $result = $dbconn->GetArray($query);
            if($result === false) {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );

            } else {
                $orig = $result[0];
            }
            // create new entry in the vuln_nessus_settings table first and get
            // the new id
            $params = array(
                $sname,
                $sdescription,
                $orig['autoenable'],
                $orig['type'],
                $username,
                $tracker
            );
            
            $insert = "INSERT INTO vuln_nessus_settings
                          (name, description, autoenable, type, owner, update_host_tracker )
                       VALUES (?, ?, ?, ?, ?, ?)";
            $result = $dbconn->execute($insert, $params);
            if($result === false) {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            } else {
                $newPID = $dbconn->Insert_ID();
            }

            $query="insert into vuln_nessus_settings_family (select $newPID as sid, fid, status 
                        from vuln_nessus_settings_family
                        where sid=$cloneid)";
            $result=$dbconn->execute($query);
            if($result === false) {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }
            $query="insert into vuln_nessus_settings_category
                       (select $newPID as sid, cid, status 
                        from vuln_nessus_settings_category
                        where sid=$cloneid)";
            
            $result=$dbconn->execute($query);
            if($result === false) {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }
            $query="insert into vuln_nessus_settings_preferences 
                       (select $newPID as sid, id, nessus_id, value, 
                           category, type 
                        from vuln_nessus_settings_preferences
                        where sid=$cloneid)";
            $result=$dbconn->execute($query);
            if($result === false) {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }
            $query = "insert into vuln_nessus_settings_plugins 
                        (select id, $newPID as sid, enabled, category, 
                            family from vuln_nessus_settings_plugins 
                         where sid = $cloneid)";
            
            $result=$dbconn->execute($query);
            if($result === false) {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }
             $sid = $newPID; // necessary so that success links have the sid set
            } else {
             // create a new profile from scratch
                if( $sname<>"" and ($sautoenable=="N" or $sautoenable=="C" or $sautoenable=="F") ) {
          
                # see if this is duplicate name or not
                $query="SELECT count(name)
                        FROM vuln_nessus_settings
                        WHERE name='$sname'";
          
                $result=$dbconn->execute($query);
                list($count)=$result->fields;
                if ($count>0) {
                    return ( array ( "message" => "Cannot create new profile. Duplicate profile name $sname exists.", "status" => "error" ) );
                } else {
                
                    $type = " ";
                    
                    $params = array($sname, $sdescription, $sautoenable, $type, $username);
                    $query  = "INSERT into vuln_nessus_settings (name, description, autoenable, type, owner)
                           values (?, ?, ?, ?, ?)";
                    
                    $result=$dbconn->execute($query, $params);
                    if($result === false) {
                        return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                    } else {
                      $sid = $dbconn->Insert_ID();
                    }
       
                   // improve logic here, only add these if this profile
                   // is set to autoenable anything, otherwise skip this
                    while (list($key, $value) = each ($data)) {
                        $value=Util::htmlentities(mysql_real_escape_string(trim($value)), ENT_QUOTES);
                        if (substr($key,0,2)=="f_") {
                            $type=substr($key,0,1);
                            $key=substr($key, 2);
                            $query="insert into vuln_nessus_settings_family values($sid, $key, $value)";
                            $results=$dbconn->Execute($query);
                            if($result === false) {
                                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                            }
                      } elseif (substr($key,0,2)=="c_") {
                            $type=substr($key,0,1);
                            $key=substr($key, 2);
                            $query="insert into vuln_nessus_settings_category values($sid, $key, $value)";
                            $results=$dbconn->Execute($query);
                            if($result === false) {
                                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                            }
                        }
                    }

                    $query="insert into vuln_nessus_settings_preferences 
                              select $sid as sid, id, nessus_id, value, 
                                     category, type 
                              from vuln_nessus_preferences";
                    $result=$dbconn->execute($query);
                    if($result === false) {
                        return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                    }
                    $query = "insert into vuln_nessus_settings_plugins 
                                select id, $sid as sid, enabled, category, 
                                       family from vuln_nessus_plugins 
                                where deleted is null";
                    $result=$dbconn->execute($query);
                    if($result === false) {
                        return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                    }
        
                    if ($sautoenable=="C") {
                        $query="select t1.cid, t1.status 
                              from vuln_nessus_settings_category as t1, 
                                   vuln_nessus_category as t2 
                              where sid=$sid";
                        $result=$dbconn->execute($query);
        
                        while (!$result->EOF) {
                            list($cid, $catstatus)=$result->fields;
                            if ($catstatus==4) {
                                $query1="update vuln_nessus_settings_plugins 
                                     set enabled='N' 
                                     where category=$cid 
                                           and sid=$sid";
                                $result1=$dbconn->execute($query1);
                                if($result1 === false) {
                                    return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                                }
                        } elseif ($catstatus==1) {
                            $query1="update vuln_nessus_settings_plugins 
                                     set enabled='Y' 
                                     where category=$cid 
                                           and sid=$sid";
                            $result1=$dbconn->execute($query1);
                            if($result1 === false) {
                                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                            }
                        }
                            $result->MoveNext();
                        }
                    }   elseif($sautoenable=="F") {
                        $query="select t1.fid, t1.status 
                                    from vuln_nessus_settings_family as t1, 
                                    vuln_nessus_category as t2 
                                    where sid=$sid";
                        $result=$dbconn->execute($query);
       
                        while (!$result->EOF) {
                            list($fid, $catstatus)=$result->fields;
                            if ($catstatus==4) {
                                $query1="update vuln_nessus_settings_plugins 
                                         set enabled='N' 
                                         where family=$fid 
                                               and sid=$sid";
                                $result1=$dbconn->execute($query1);
                                if($result1 === false) {
                                    return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                                }
                            } elseif ($catstatus==1) {
                                $query1="update vuln_nessus_settings_plugins 
                                     set enabled='Y' 
                                     where family=$fid 
                                           and sid=$sid";
                                $result1=$dbconn->execute($query1);
                                if($result1 === false) {
                                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
                                }
                            }
                            $result->MoveNext();
                        }
                   }
                }
            }
            else {
                return ( array("message" => "Please specify profile name", "status" => "error") );
            }
        }
        
        return ( array("message" => "", "status" => "OK") );
    }

    function create_sensor_profile( $dbconn, $sensor_id, $sname, $owner, $cloneid ) {
        
        $sensor_object = new Av_sensor($sensor_id);
        
        $sensor_object->load_from_db($dbconn);
        
        $check_result  = $sensor_object->check_vs_connection($dbconn);

        if($check_result != '')
        {
            $result['status']  = 'error';
            $result['message'] = $check_result;
            
            return $result;
        }
        
        $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                
        $port     = $ov_credentials['port'];
        $user     = $ov_credentials['user'];
        $password = $ov_credentials['password'];
        
        $omp    = new Omp($sensor_object->get_ip(), $port, $user, $password );
        
        $sid    = $dbconn->GetOne("SELECT id FROM vuln_nessus_settings WHERE name='$sname' AND owner='$owner'");
        
        $result = $omp->create_new_config($sid, $cloneid);
        
        return $result;

    }
    
    function check_profile_in_sensor ($dbconn, $sensor_id, $sid) {
    
        $sensor_object = new Av_sensor($sensor_id);
        
        $sensor_object->load_from_db($dbconn);
        
        $check_result  = $sensor_object->check_vs_connection($dbconn);

		if( $check_result != '' ) {
            $result['status']  = 'error';
            $result['message'] = $check_result;
			
			return $result;
        }
        
        
        $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                
        $port     = $ov_credentials['port'];
        $user     = $ov_credentials['user'];
        $password = $ov_credentials['password'];
            
        $omp    = new Omp($sensor_object->get_ip(), $port, $user, $password);
        
        $config_id = $omp->get_config_id_by_sid($sid);
        
        if($config_id != '') {
            return(array('status' => 'already_exits', 'message' => ''));
        }
        
        $result    = $omp->create_new_config($sid);
        
        return $result;
    
    }
    
    function can_modify_profile($conn, $name, $owner) {
        
        if( $name == "Deep" || $name == "Default" || $name == "Ultimate" ) {
            return false;
        }
        else if ( Session::am_i_admin() ) {
            return true;
        }
        else
        {
            $me = Session::get_session_user();
            
            $my_user_vision = $_SESSION['_user_vision']['user'];
            if ( empty($my_user_vision)  && Session::is_pro() )
            {
                $user_vision    = Acl::get_user_vision($conn);
                $my_user_vision = $user_vision['user'];
            }

            if ( ($owner == $me || $my_user_vision[$owner] == 2) )
                return true;
        }
            
        return false;
    }

    function can_delete_profile($conn, $name, $owner) {
        
        if ( $name != "Default" && $name != "Deep" && $name != "Ultimate" )
        {
            $me = Session::get_session_user();
            
            $my_user_vision = $_SESSION['_user_vision']['user'];
            
            if ( empty($my_user_vision) && Session::is_pro() )
            {
                $user_vision    = Acl::get_user_vision($conn);
                $my_user_vision = $user_vision['user'];
            }
            
            if ( $owner == $me || $my_user_vision[$owner] == 2 || Session::am_i_admin() )
                return true;
        }
        
        return false;
    }
    
    function delete_sensor_config($dbconn, $sensor_id, $sid) {
    
        $sensor_object = new Av_sensor($sensor_id);
        
        $sensor_object->load_from_db($dbconn);
        
        $check_result  = $sensor_object->check_vs_connection($dbconn);

		if( $check_result != '' ) {
            $result['status']  = 'error';
            $result['message'] = $check_result;
			
			return $result;
        }
        
        $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                
        $port     = $ov_credentials['port'];
        $user     = $ov_credentials['user'];
        $password = $ov_credentials['password'];
        
        
        $omp    = new Omp($sensor_object->get_ip(), $port, $user, $password);
        $result = $omp->delete_config($sid);
        
        return $result;
    
    }
    
    function delete_db_profile($dbconn, $sid){

        $query = "delete from vuln_nessus_settings where id=$sid";
        if ($dbconn->execute($query) === false) {
            return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
        }

        $query="delete from vuln_nessus_settings_preferences where sid=$sid";
        if ($dbconn->execute($query) === false) {
            return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
        }
         
        $query="delete from vuln_nessus_settings_plugins where sid=$sid";
        
        if ($dbconn->execute($query) === false) {
            return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
        }
         
        $query = "delete from vuln_nessus_settings_family where sid=$sid";
        
        if($dbconn->execute($query)=== false) {
            return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
        }
         
        $query = "delete from vuln_nessus_settings_category where sid=$sid";
        if($dbconn->execute($query)=== false) {
            return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
        }
        
        return ( array ( "message" => "", "status" => "OK" ) );
    
    }
    
    function modify_profile_ID_in_sensors($dbconn, $old_name, $old_user, $new_name, $new_user) {
    
       	$sensor_data = Av_sensor::get_list($dbconn);

        $sensor_list = $sensor_data[0];

    	foreach ($sensor_list as $sensor_id => $sensor_data) 
    	{
            if( intval($sensor_data['properties']['has_vuln_scanner']) == 1)
            {
                $sIDs[] = $sensor_id;
            }
    	}
    
		foreach($sIDs as $sensor_id)
		{
		    $sensor_object = new Av_sensor($sensor_id);
        
            $sensor_object->load_from_db($dbconn);
        
            $check_result  = $sensor_object->check_vs_connection($dbconn);
	
			if($check_result != '')
			{
				$result['status']  = 'error';
				$result['message'] = $check_result;
					
				return $result;
			}
		        
            $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
                    
            $port     = $ov_credentials['port'];
            $user     = $ov_credentials['user'];
            $password = $ov_credentials['password'];        
	        
	        $omp    = new Omp($sensor_object->get_ip(), $port, $user, $password);
	        
	    	$config_id = $omp->get_config_id_by_name_comment($old_name, $old_user);
	    	
	    	$xml = $omp->execute_command("<modify_config config_id='$config_id'><name>$new_name</name><comment>$new_user</comment></modify_config>");
	    	
	    	if ($xml===FALSE)
	    	{
		    	return ( array ( "message" => "Error modifing config id $config_id in sensor $sensor_id", "status" => "error" ) );
	    	}
	    }

		return ( array ( "message" => "", "status" => "OK" ) );
    
    }

}
