<?php
/**
* Vulnerabilities.inc
*
* File Vulnerabilities.inc is used to:
*   - Manage vulnerabilities section
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

require_once __DIR__ . '/../ossim_sql.inc';

class Vulnerabilities
{
    public static $job_statuses = array (
        'C' => 'Completed',
        'D' => 'Delayed (No Scan Slots)',
        'F' => 'Failed',
        'H' => 'Invalid Scan Config',
        'I' => 'Incomplete',
        'K' => 'Killed',
        'R' => 'Running',
        'S' => 'Scheduled',
        'T' => 'Timeout expired'
    );


    /**
     * Get all plugins from a GVM profile given a search filter
     * @param object $dbconn   Database access object
     * @param string $sid      Profile ID
     * @param array  $filters  Array with SQL filters
     * @return array|bool
     * @throws Exception
     */
    public static function get_plugins_by_profile($dbconn, $sid, $filters){
        Ossim_db::check_connection($dbconn);

        $plugins = array();
        $total   = 0;
        $cnt     = '';
        $q_where = '';
        $q_order = '';
        $q_limit = '';

        if (!empty($filters['where']))
        {
            $q_where .= $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_order .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $cnt      = 'SQL_CALC_FOUND_ROWS ';
            $q_limit .= ' LIMIT '.$filters['limit'];
        }

        $query = ossim_query("SELECT $cnt t1.cve_id as cve, t1.id, t1.name as vuln_name, t3.name as category_name, t4.name as family_name, t2.enabled
                  FROM vuln_nessus_plugins AS t1
                  INNER JOIN vuln_nessus_settings_plugins AS t2 ON t2.id = t1.id
                  INNER JOIN vuln_nessus_category AS t3 ON t3.id=t1.category
                  INNER JOIN vuln_nessus_family AS t4 ON t4.id=t1.family
                  WHERE t2.sid = ?
                  $q_where
                  $q_order
                  $q_limit");

        $params = array($sid);

        $rs = $dbconn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $dbconn->ErrorMsg());
            return false;
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($dbconn, $query);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF) {

            $plugins[$rs->fields['id']] = array(
                'id'            => $rs->fields['id'],
                'cve'           => $rs->fields['cve'],
                'vuln_name'     => $rs->fields['vuln_name'],
                'category_name' => $rs->fields['category_name'],
                'family_name'   => $rs->fields['family_name'],
                'enabled'       => $rs->fields['enabled']
            );

            $rs->MoveNext();
        }

        return array($plugins, $total);
    }

    /**
     * Get stats related to a given profile
     * @param object $dbconn  Database access object
     * @param string $sid     Profile ID
     * @return array
     * @throws Exception
     */
    public static function get_plugin_stats_by_profile($dbconn, $sid){
        Ossim_db::check_connection($dbconn);

        $plugins_stats = array();
        $q_where = '';

        if (!empty($filters['where']))
        {
            $q_where .= $filters['where'];
        }

        $query = "SELECT SUM(if (t2.enabled='N',1,0)) AS disabled, SUM(if (t2.enabled='Y',1,0)) AS enabled
                                FROM vuln_nessus_plugins AS t1
                                LEFT JOIN vuln_nessus_settings_plugins AS t2 ON t2.id = t1.id
                                WHERE t2.sid = ?";

        $params = array($sid);
        $rs = $dbconn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $dbconn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $plugins_stats = array(
                'total' => $rs->fields['disabled'] + $rs->fields['enabled'],
                'disabled' => $rs->fields['disabled'],
                'enabled' => $rs->fields['enabled']
            );

            $rs->MoveNext();
        }

        return $plugins_stats;
    }

    public static function get_users_and_entities_filter($dbconn)
    {
        $items = array();

        if(Session::is_pro())
        {
            if(!Session::am_i_admin())
            {
                $entities = Acl::get_entities_to_assign($dbconn);

                if (Acl::am_i_proadmin())
                {
                    $users           = array();
                    $users_to_assign = Session::get_users_to_assign($dbconn);

                    foreach ($users_to_assign as $user)
                    {
                        $users[] = $user->get_login();
                    }
                }
                else
                {
                    $users = array( Session::get_session_user() );
                }

                $items = array_merge(array_keys($entities), $users);
            }
        }
        else
        {
            if(!Session::am_i_admin())
            {
                $items[] = Session::get_session_user();
            }
        }

        $query_filter = (count($items)>0) ? "'".implode("', '",$items)."'" : '';

        return array($items, $query_filter);
    }

    public static function update_vuln_job_assets($dbconn, $action, $job_id, $job_type)
    {
        $params = array(
            $job_id,
            $job_type
        );

        $dbconn->Execute('DELETE FROM vuln_job_assets WHERE job_id=? AND job_type=?', $params);

        if ($action == 'insert')
        {
            $id     = ($job_type == 0) ? 0 : $job_id;

            $params = array($id);

            $rs     = $dbconn->Execute("CALL _update_vuln_assets(?)", $params);

            if (method_exists($rs, 'free'))
            {
                $rs->free();
            }
        }
    }

    public static function is_allowed_credential($conn, $name, $login)
    {
        //Get credential filter
        $perms = '';

        if(!Session::am_i_admin())
        {
            $users     = Session::get_users_to_assign($conn);
            $entities  = array_keys(Session::get_entities_to_assign($conn));

            $c_perms['0'] = '0';

            foreach($users as $k)
            {
                $c_perms[$k->get_login()] = $k->get_login();
            }

            foreach($entities as $entity_id)
            {
                $c_perms[$entity_id] = $entity_id;
            }

            $perms = "AND login IN ('".implode("', '", $c_perms)."')";
        }

        $query  = ossim_query("SELECT * FROM user_config WHERE category='credentials' AND name=? AND login=? $perms");
        $name = mb_convert_encoding($name,  'ISO-8859-1', 'UTF-8');
        $params = array($name, $login);

        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            return FALSE;
        }
        else
        {
            return ( count($rs->fields) >= 1 ) ? TRUE : FALSE;
        }
    }

    public static function check_credential($conn, $host_id_ip, $name, $login)
    {
        $debug  = FALSE;
        $result = array();
        $lcheck = FALSE;  // check with local sensor flag

        if(preg_match("/[a-z0-9]{32}#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i", $host_id_ip))
        {
            list($host_id, $host_ip) = explode("#", $host_id_ip);

            if (!Session::hostAllowed($conn, $host_id))
            {
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
        else
        {
            // select all sensors
            $host_ip = $host_id_ip;  // host_id_ip in format xxx.xxx.xxx.xxx

            $lcheck = TRUE;
        }

        if ($lcheck)
        {
            $cdata = self::get_credential_data($conn, $name, $login);

            if ($lcheck)
            {
                // check with local sensor
                if( $cdata["login"] != '' && $cdata["password"] != '')
                {
                    $pass_file      = '/var/tmp/check_credential_' . Util::uuid();

                    // check ssh credential with user/login
                    $cdata["password"] = html_entity_decode(mb_convert_encoding($cdata["password"], 'UTF-8'));

                    $hfile = fopen($pass_file, 'w');
                    fwrite($hfile, $cdata["password"]);
                    fclose($hfile);

                    $command = 'sshpass -f ? ssh -o "StrictHostKeyChecking=no" -o "ConnectTimeout=5" ? "echo \"ok\";exit"';
                    $params  = array($pass_file, $cdata["login"]. '@' . $host_ip);

                    $arr_out = Util::execute_command($command, $params, 'array', FALSE); // Don't throw exceptions

                    $out = implode("\n",$arr_out);

                    if($debug)
                    {
                        file_put_contents("/tmp/credentials.log", "> in ".$command."\n", FILE_APPEND);
                        file_put_contents("/tmp/credentials.log", "< out ".$out."\n", FILE_APPEND);
                    }

                    if(preg_match("/Connection timed out/i", $out))
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Host unreachable")."</span>");
                    }
                    else if(preg_match("/No route to host/i", $out))
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("No route to host")."</span>");
                    }
                    else if(preg_match("/Authentication failed/i", $out))
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Authentication failed")."</span>");
                    }
                    else if(preg_match("/ok/i", $out))
                    {
                        $result[] = array( "type" => "test_ok", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#5BB531;'>"._("Successfully logged in")."</span>");
                    }
                    else
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SSH User/Password"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Wrong credentials")."</span>");
                    }

                    // check smb credential with user/login

                    $hfile = fopen($pass_file, 'w');

                    // domain\user

                    $login_data = preg_split('/[\\\\]{1}/', $cdata["login"]);

                    if (count($login_data) == 2)
                    {
                        $cdata["login"] = $login_data[1];

                        fwrite($hfile, 'domain=' . $login_data[0] . "\n");
                    }

                    fwrite($hfile, 'username=' . $cdata["login"] . "\n");
                    fwrite($hfile, 'password=' . $cdata["password"] . "\n");
                    fclose($hfile);

                    $command = 'smbclient -A ? -p ? -m ? -L ? ?';
                    $params  = array($pass_file, '445', 'SMB3', $host_ip, '\\\\\\\\'.$host_ip.'\\\\');

                    $arr_out = array();

                    $return_var = 0;

                    $arr_out = Util::execute_command($command, $params, 'array', FALSE, $return_var); // Don't throw exceptions

                    $out = implode(" ",$arr_out);

                    if($debug)
                    {
                        file_put_contents("/tmp/credentials.log", "> in ".$command."\n", FILE_APPEND);
                        file_put_contents("/tmp/credentials.log", "< out ".$out."\n", FILE_APPEND);
                    }

                    if(preg_match("/HOST_UNREACHABLE/", $out))
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Host unreachable")."</span>");
                    }
                    else if(preg_match("/LOGON_FAILURE|UNSUCCESSFUL|ACCESS_DENIED/", $out))
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Wrong credentials")."</span>");
                    }
                    else if(preg_match("/CONNECTION_REFUSED/", $out))
                    {
                        $result[] = array( "type" => "test_ko", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Connection refused")."</span>");
                    }
                    else if(preg_match("/Sharename/", $out) || ($return_var && !$out))
                    {
                        $result[] = array( "type" => "test_ok", "message" => _("Checking SMB User/Password"), "sensor" => "Local", "status" => "<span style='color:#5BB531;'>"._("Successfully logged in")."</span>");
                    }

                    if (file_exists($pass_file))
                    {
                        unlink($pass_file);
                    }
                }
                else if($cdata["login"] != '' && file_exists($cdata["private_key_file"]))
                {
                    // check ssh credential with login/private key
                    $command = 'chmod 600 ? && ssh -q -o "BatchMode=yes" -o "StrictHostKeyChecking=no" -o "ConnectTimeout=5" -i ? ? "echo 2>&1" && echo "OK" || echo "NOK"';
                    $params  = array($cdata["private_key_file"], $cdata["private_key_file"], $cdata["login"].'@'.$host_ip);

                    $arr_out = Util::execute_command($command, $params, 'array');

                    @unlink($cdata["private_key_file"]);

                    $out = implode(" ",$arr_out);

                    if($debug)
                    {
                        file_put_contents("/tmp/credentials.log", "> in ".$command."\n", FILE_APPEND);
                        file_put_contents("/tmp/credentials.log", "< out ".$out."\n", FILE_APPEND);
                    }

                    if (!preg_match("/NOK/i", $out))
                    {
                        $result[] = array( "type" => "test_ok", "message" => _("Checking ssh User/Private Key"), "sensor" => "Local", "status" => "<span style='color:#5BB531;'>"._("Successfully logged in")."</span>");
                    }
                    else
                    {
                        $arr_out = Util::execute_command("nmap -sn -PE ?", array($host_ip), 'array');

                        $out = implode(" ",$arr_out);

                        if(preg_match("/Host seems down/i", $out))
                        {
                            $result[] = array( "type" => "test_ko", "message" => _("Checking ssh User/Private Key"), "sensor" => "Local", "status" => "<span style='color:#D7B044;'>"._("Host unreachable")."</span>");
                        }
                        else
                        {
                            $result[] = array( "type" => "test_ko", "message" => _("Checking ssh User/Private Key"), "sensor" => "Local", "status" => "<span style='color:#E5383B;'>"._("Wrong credentials")."</span>");
                        }
                    }
                }
            }
        }
        else
        {
            $result[] = array( "type" => "error", "message" => _("Not found any sensor") );
        }

        return $result;
    }

    public static function get_credential_data($conn, $name, $login)
    {
        $uuid = Util::get_encryption_key();

        $query = ossim_query ( "SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue
                                FROM user_config
                                WHERE category='credentials' AND name=? AND login=?" );

        $conn->SetFetchMode(ADODB_FETCH_BOTH);
        $name = mb_convert_encoding($name,  'ISO-8859-1', 'UTF-8');
        $rs = $conn->Execute($query, array ($name, $login));
        if (!$rs)
        {
            print $conn->ErrorMsg();

            return array( "login" => '', "password" => '', "private_key_file" => '');

        }
        else if (!$rs->EOF)
        {
            preg_match("/<login>(.*)<\/login>/", $rs->fields ["dvalue"], $found);

            if($found[1] != '')
            {
                $login = $found[1];
            }

            preg_match("/<password>(.*)<\/password>/", $rs->fields ["dvalue"], $found);

            if($found[1] != '')
            {
                $password = $found[1];
            }

            preg_match("/<private>([^<]+)<\/private>/m", $rs->fields ["dvalue"], $found);

            if($found[1] != '')
            {
                $private_key = $found[1];
                $file_name = "/tmp/cred_".$login."_".$name."_".time();
                file_put_contents($file_name, $private_key);
            }
            else
            {
                $file_name = '';
            }

            return array( "login" => $login, "password" => $password, "private_key_file" => $file_name );
        }
    }

    public static function get_credentials($conn, $protocol = '')
    {
        $uuid = Util::get_encryption_key();

        //Get credential filter
        $perms = '';
        if(!Session::am_i_admin())
        {
            $users        = Session::get_users_to_assign($conn);
            $entities     = array_keys(Session::get_entities_to_assign($conn));
            $c_perms["0"] = "0";

            foreach($users as $k)
            {
                $c_perms[$k->get_login()] = $k->get_login();
            }

            foreach($entities as $entity_id)
            {
                $c_perms[$entity_id] = $entity_id;
            }

            $perms = "AND login IN ('".implode("', '", $c_perms)."')";
        }

        $query = ossim_query("SELECT *, AES_DECRYPT(UNHEX(value),'".$uuid."') as dvalue FROM user_config WHERE category='credentials' $perms");

        $conn->SetFetchMode(ADODB_FETCH_BOTH);

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            $list = array();
        }
        else
        {
            $list = array();

            while (!$rs->EOF)
            {
                $ctype = (preg_match("/\<key\>/", $rs->fields["dvalue"])) ? "Key" : "Password";

                if($protocol == '' || $protocol == "ssh" || ($ctype == "Password" && $protocol == "smb"))
                {
                    $list[] = array("login" => $rs->fields["login"], "name" => mb_convert_encoding($rs->fields["name"],  'UTF-8', 'ISO-8859-1'), "type" => $ctype);
                }

                $rs->MoveNext();
            }
        }

        return $list;
    }

    public static function get_total_gvm_running_scans($dbconn)
    {
        $query = ossim_query("SELECT count(vuln_jobs.id) AS total_jobs FROM vuln_jobs WHERE vuln_jobs.status='R' $perm_filter");

        $rf    = $dbconn->Execute($query);

        if (!$rf)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $total = intval($rf->fields['total_jobs']);

        return $total;
    }

    public static function get_running_gvm_scans($dbconn, $offset = 0, $limit = 5)
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
            $perm_filter = '';
        }

        // First count to paginate the jobs

        $total_scans = Vulnerabilities::get_total_gvm_running_scans($dbconn);

        $requested_page = $offset + 1;
        $current_pages  = ceil($total_scans / $limit);

        if ($requested_page > $current_pages)
        {
            $offset = 0;  // Go to the first page
        }

        $begin = $offset * $limit;

        $query = "SELECT t1.failed_attempts, t1.meth_Wcheck, t6.name as profile, t1.scan_NEXT, t1.meth_TARGET, t1.scan_PID, t1.id, t1.name, t1.username, t1.scan_START, t1.scan_END,
                         t1.scan_SUBMIT, t1.status, t1.notify, t3.hostname, t1.credentials, t1.notify
                        FROM vuln_jobs t1
                        LEFT JOIN vuln_nessus_servers t3 on t1.notify = t3.hostname
                        LEFT JOIN vuln_nessus_reports t5 on t1.report_id = t5.report_id
                        LEFT JOIN vuln_nessus_settings t6 on t1.profile_id=t6.id
                        WHERE t1.status='R' $perm_filter
                        ORDER BY t1.id DESC limit $begin, $limit";

        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $dbconn->Execute($query);

        if ($total_scans > 1)
        {
            $running_scans_title = _("<strong>%d</strong> Running Scans");
        }
        else if($total_scans == 1)
        {
            $running_scans_title = _("<strong>%d</strong> Running Scan");
        }
        else
        {
            $running_scans_title = _("Running Scans");
        }

        $sInfo .= "<div id='c_action'><div id='c_action_error'></div></div>";

        $sInfo .= "<table style='margin-top:5px;' class='w100 transparent'>
                        <tr><td class='sec_title center'>".sprintf($running_scans_title, $total_scans)."</td></tr>
                   </table>";

        $sInfo .= "<table class='table_list t_width'>";

        $out    = 0;

        if(!$result->EOF)
        {
            while (!$result->EOF) {
                $out++;

                if ($out == 1) {
                    $sInfo .= "#HEADER#";
                }

                list ($failed_attempts, $meth_Wcheck, $profile, $scan_next, $meth_TARGET,
                    $scan_PID, $nid, $nname, $nusername, $start_date, $end_date, $submit_date, $nstatus,
                    $nnotify, $shostname, $credentials, $notify) = $result->fields;

                $sensor = Av_sensor::get_object($dbconn, $notify);
                $sensor_ip = $sensor->get_ip();

                $gvm = new Gvm($sensor_ip);
                $allowed_task_status = 'New|Queued|Requested|Running|Stopped|Stop Requested|Incomplete|Done';
                $task_detail = $gvm->get_task_detail_by_id($allowed_task_status, $nid);

                $targets_to_resolve = explode("\n", $meth_TARGET);

                $max_hosts_exceeded = FALSE;
                $hosts_to_scan = count($targets_to_resolve);
                if (count($targets_to_resolve) > 10 ){
                    $max_hosts_exceeded = TRUE;
                    $targets_to_resolve = array_slice($targets_to_resolve, 0, 10);
                }

                $ttargets = array();

                foreach ($targets_to_resolve as $id_ip) {
                    if (preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+\/\d{1,2}$/i", $id_ip, $found)) {
                        if (Asset_net::is_in_db($dbconn, $found[1])) {
                            $cidr = preg_replace("/^([a-f\d]{32})#/i", '', $id_ip);

                            $net_name = Asset_net::get_name_by_id($dbconn, $found[1]);

                            $ttargets[] = $cidr . ' (' . $net_name . ')';

                            $aname[$cidr] = '(' . $net_name . ')'; // to display names in hidden tr
                        } else {
                            $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", '', $id_ip);
                        }
                    } else if (preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+$/i", $id_ip, $found)) {
                        if (Asset_host::is_in_db($dbconn, $found[1])) {
                            $ip = preg_replace("/^([a-f\d]{32})#/i", '', $id_ip);

                            $host_name = Asset_host::get_name_by_id($dbconn, $found[1]);

                            $ttargets[] = $ip . ' (' . $host_name . ')';

                            $aname[$ip] = '(' . $host_name . ')'; // to display names in hidden tr
                        } else {
                            $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", '', $id_ip);
                        }
                    } else if (preg_match("/^([a-f\d]{32})#hostgroup/i", $id_ip, $found)) {
                        $hostgroup_name = Asset_group::get_name_by_id($dbconn, $found[1]);
                        $ttargets[] = ($hostgroup_name == _('Unknown')) ? _('Unknown hostgroup') : $hostgroup_name;
                    } else if (preg_match("/^([a-f\d]{32})#netgroup/i", $id_ip, $found)) {
                        $netgroup_name = Net_group::get_name_by_id($dbconn, $found[1]);
                        $ttargets[] = ($netgroup_name == _('Unknown')) ? _('Unknown netgroup') : $netgroup_name;
                    } else {
                        $ttargets[] = $id_ip;
                    }
                }

                $ustart_date = $start_date; // save start_date in UTC to display scan time

                if ($tz != 0) {
                    if (!is_null($submit_date)) {
                        $submit_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($submit_date) + (3600 * $tz));
                    }

                    if (!is_null($start_date)) {
                        $start_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($start_date) + (3600 * $tz));
                    }

                    if (!is_null($end_date)) {
                        $end_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($end_date) + (3600 * $tz));
                    }

                    if (!is_null($scan_next)) {
                        $scan_next = gmdate("YmdHis", Util::get_utc_unixtime($scan_next) + (3600 * $tz));
                    }
                }

                if ($nnotify != '') {
                    if (Av_sensor::is_in_db($dbconn, $nnotify)) {
                        $shostname = Av_sensor::get_name_by_id($dbconn, $nnotify);
                    } else {
                        $shostname = _("unknown");
                    }
                }

                // check job status
                $tstatus = array("status" => '', "progress" => '');

                $statuses = Vulnerabilities::$job_statuses;
                $tstatus = $gvm->get_task_status($nid);

                if ($tstatus['status'] != '') {
                    $job_status = "<strong>" . _("Job Status") . "</strong>: " . $tstatus["status"] . "<br>";
                }

                if (preg_match("/requested$/i", $tstatus["status"])) {
                    $image = "<img src=\"./images/requested.png\" border=\"0\" title=\"" . _("Requested") . "\" alt=\"" . _("Requested") . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "I") {
                    $image = "<img src=\"./images/paused.png\" border=\"0\" title=\"" . _("Stopped") . "\" alt=\"" . _("Stopped") . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "C") {
                    $image = "<img src=\"./images/complete.png\" border=\"0\" title=\"" . _($statuses[$nstatus]) . "\" alt=\"" . _("$statuses[$nstatus]") . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "R") {
                    $image = "<img src=\"./images/running.png\" border=\"0\" title=\"" . _("Running") . "\" alt=\"" . _("Running") . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "S") {
                    $image = "<img src=\"./images/sched.png\" border=\"0\" title=\"" . _($statuses[$nstatus]) . "\" alt=\"" . _($statuses[$nstatus]) . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "D") {
                    $image = "<img src=\"./images/delayed.png\" border=\"0\" title=\"" . _($statuses[$nstatus]) . "\" alt=\"" . _($statuses[$nstatus]) . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "F" || $nstatus == "H" || $nstatus == "K") {
                    $image = "<img src=\"./images/warning.png\" border=\"0\" title=\"" . _($statuses[$nstatus]) . "\" alt=\"" . _($statuses[$nstatus]) . "\" align=\"absmiddle\">";
                } elseif ($nstatus == "T") {
                    $image = "<img src=\"./images/timeout.png\" border=\"0\" title=\"" . _("Timeout expired") . "\" alt=\"" . _("Timeout expired") . "\" align=\"absmiddle\">";
                }

                if (valid_hex32($nusername)) {
                    $nusername = Session::get_entity_name($dbconn, $nusername);
                }

                $sInfo .= "<tr onclick='show_details(\"task$nid\")' class='tasks cstatus' id='$nid'>"; // job tr

                $credentialm = array();
                if ($credentials != '') {
                    list($ssh_credential, $smb_credential) = explode("|", $credentials);

                    if ($ssh_credential != '') {
                        $ssh_credential = str_replace("#", " (", $ssh_credential);
                        $ssh_credential .= ")";
                        $credentialm[] = "<strong>" . _("SSH: ") . "</strong>" . $ssh_credential;
                    }

                    if ($smb_credential != '') {
                        $smb_credential = str_replace("#", " (", $smb_credential);
                        $smb_credential .= ")";
                        $credentialm[] = "<strong>" . _("SMB: ") . "</strong>" . $smb_credential;
                    }
                }

                $meth_TARGET  = implode("<br/>", $ttargets);
                $meth_TARGET  = ($max_hosts_exceeded == TRUE) ? $meth_TARGET."<br/>&nbsp;..." : $meth_TARGET;

                $target_label = _('Targets');
                if ($hosts_to_scan > 1){
                    $target_label .= " (".$hosts_to_scan." "._("hosts").")";
                }
                $target_label .= ":";

                $meth_Wcheck = nl2br(trim($meth_Wcheck));
                $meth_Wcheck = str_replace('"', '&quot;', $meth_Wcheck);
                $meth_Wcheck = preg_replace('/Scan Server/', 'Scan Sensor', $meth_Wcheck);

                $tooltip_message = "<strong>" . _("Sensor") . ":</strong> $shostname
                                    <br>$job_status<strong>" . _("Job ID") . ":</strong> $nid
                                    <br><strong>" . _("Profile") . ":</strong> $profile
                                    <br>" . ((count($credentialm) > 0) ? implode("<br/>", $credentialm) . "<br/>" : '') . "
                                    <strong>" . _("Failed attempts") . ":</strong> $failed_attempts
                                    <br><strong> ".$target_label."</strong>
                                    <br>$meth_TARGET
                                    <br>" . (($meth_Wcheck != '') ? "<strong>" . _("Messages") . ":</strong>
                                    <br>" . $meth_Wcheck : '');

                $sInfo .= "<td style='text-align:left;width:35%;padding:3px 0px 3px 3px;'>$image&nbsp;<div class='tip' title=\"$tooltip_message\">" . html_entity_decode($nname) . "</div></td>";

                $sInfo .= "<td style='width:12%;'>" . $nusername . "</td>";

                if ($start_date) {
                    $current_date = gmdate("Y-m-d H:i:s");
                    $nmins = "RUN >" . datediff("n", $current_date, $ustart_date);
                    $nmins .= " mins";
                }

                // Scan time
                $sInfo .= "<td style='width:12%;'>";

                $time_text = "<strong>" . _("Submit date") . ":</strong> $submit_date";
                $time_text .= "<br/><strong>" . _("Start date") . ":</strong> $start_date";

                $sInfo .= "<div class='tip' title='$time_text'>";
                $sInfo .= ($nmins == "0 mins") ? "< 1 min" : "$nmins";
                $sInfo .= "</div>";

                $sInfo .= "</td>";
                $sInfo .= "<td style='width:26%;'>";
                $sInfo .= "<div style='width:44%;float:left;padding:0px 0px 0px 0px' id='nessus_threads_$nid'>
                                <div class='av_activitybar'><div class='stripes'></div><span class='text-percents'>-</span></div>
                           </div>";
                $sInfo .= "</td>";

                $sInfo .= "<td class='c_actions'>";

                if ($tstatus["status"] != '') {
                    $task_status = $tstatus["status"];

                    // Stop button
                    if ($task_status == "Running") {
                        $sInfo .= "<img align='absmiddle' class='manageJob' id='stopTask_$nid' title='" . _("Stop current scan job") . "' alt='" . _("Stop current scan job") . "' src='./images/stop_task.png'  border='0'>";
                    } else {
                        $sInfo .= "<img align='absmiddle' class='bdisabled' src='./images/stop_task.png' border='0'></a>";
                    }
                } else {
                    $sInfo .= "<span style='font-size:10px'>" . _("Requesting status...") . "</span>";
                }

                $sInfo .= "<span style='display:none' id='changing_task_status_$nid'><img align='absmiddle' src='../pixmaps/loading.gif' width='16' border='0'></span></td>";

                $sInfo .= "</td></tr>";

                if ($task_detail['status'] == 'success' && !empty($task_detail['data']['host_progress'])) {
                    $task_prg_image = function ($prg) {
                        $prg_states = array("green", "green", "green", "green");
                        switch (true) {
                            case $prg <= 25:
                                $prg_states = array("yellow", "gray", "gray", "gray");
                                break;
                            case $prg <= 50:
                                $prg_states = array("green", "yellow", "gray", "gray");
                                break;
                            case $prg <= 75:
                                $prg_states = array("green", "green", "yellow", "gray");
                                break;
                            case $prg <= 99:
                                $prg_states = array("green", "green", "green", "yellow");
                                break;
                        }
                        $task_progress = "";
                        for ($i = 0; $i < 3; $i++) {
                            $task_progress .= "<img src='./images/light_{$prg_states[$i]}_lit.png' border='0'/>";
                        }
                        $task_progress .= "<img style='padding-right:7px;' src='./images/light_{$prg_states[$i]}_lit.png' border='0'/>";
                        return $task_progress;
                    };

                    $i = 1;
                    $lines = count($task_detail['data']['host_progress']);

                    foreach ($task_detail['data']['host_progress'] as $h_progress) {

                        $h_progress_details = "<div style='float:left;width:55%;text-align:right;padding:3px 0px 0px 0px'>";
                        $h_progress_details .= $task_prg_image($h_progress['progress']);
                        $h_progress_details .= "</div><div style='float:right;width:43%;text-align:left'>" . $h_progress['progress'] . "%</div>";

                        $sInfo .= "<tr style='display:none;' class='task$nid'>";
                        $sInfo .= "<td style='text-align:right;' class='transparent ip_detail'><img src='../pixmaps/theme/" . ((count($lines) != $i) ? "bmltL_nes2.png" : "bmltL_ne2.png") . "' /></td>";
                        $sInfo .= "<td colspan='2' class='ip_detail' style='text-align:left;padding:0px 0px 0px 15px;'>" . $h_progress['ip'] . " " . $aname[$h_progress['ip']] . "</td>";
                        $sInfo .= "<td class='ip_detail'>";
                        $sInfo .= "<div id='$nid-" . $h_progress['md5_ip'] . "-per' style='width:44%;float:left;text-align:center'>" . $h_progress_details . "</div>";
                        $sInfo .= "<td class='ip_detail'>&nbsp;</td>";
                        $sInfo .= "</tr>";
                        $i++;
                    }

                    if (count($i) % 2 != 0) {
                        $sInfo .= "<tr style='display:none;'><td colspan='5'></td></tr>";
                    }
                }

                $result->MoveNext();
            }
        }

        if($out == 0)
        {
            $sInfo .= "<tr><td colspan='5' class='empty_results'>"._("No Running Scans")."</td></tr>";
            $sInfo = preg_replace("/#HEADER#/", '', $sInfo);
        }
        else
        {
            $header ="<tr>
                        <th>"._("Job Name")."</th>
                        <th>"._("Owner")."</th>
                        <th>"._("Scan Time")."</th>
                        <th style='padding: 0px !important;'>
                            <div style='width:44%;float:left;'>"._("Progress")."</div>
                        </th>
                        <th>"._("Action")."</th>
                        </tr>";
            $sInfo = preg_replace("/#HEADER#/", $header, $sInfo);
        }

        $sInfo .= "</table>";

        if ($total_scans > $limit)
        {
            $rs_ppage = $rs_npage = $offset;

            $rs_ppage--; // the previous page
            $rs_npage++; // the next page

            $previous_attr = ($rs_ppage >= 0)             ? "href='manage_jobs.php?rs_page=$rs_ppage' class='lmargin pn_buttons'" : "class='lmargin link_paginate_disabled' href='' onclick='return false'";
            $next_attr     = ($rs_npage < $current_pages) ? "href='manage_jobs.php?rs_page=$rs_npage' class='lmargin pn_buttons'" : "class='lmargin link_paginate_disabled' href='' onclick='return false'";

            $sInfo .= "<div class='fright tmargin'>";

            $sInfo .= "<a $previous_attr>< " . _("PREVIOUS") . "</a>";
            $sInfo .= "<a $next_attr>" . _("NEXT") . "></a>";

            $sInfo .= "</div>";
        }

        return $sInfo;
    }


    /**
     * Get the families that will be affected by a plugin update
     *
     * @param object $dbconn          Database access object
     * @param string $sid             Profile ID
     * @param array  $search_filters  Search criteria to find plugins that will be enabled or disable
     * @param array  $plugins         Plugins IDs that will be enabled or disabled individually
     * @return array
     * @throws Exception
     */
    public static function get_affected_families($dbconn, $sid, $search_filters, $plugins)
    {
        $result = array(
            "status"  => "OK",
            "message" => array()
        );

        $families = array();

        if ($plugins['enable_all'] || $plugins['disable_all']) {
            $q_where = '';
            $q_tables = '';

            $params = array();
            $params[] = ($plugins['enable_all'] == '1') ? 'N' : 'Y';
            $params[] = $sid;

            if (!empty($search_filters['cve']) || !empty($search_filters['plugin'])) {
                $q_tables .= 'INNER JOIN vuln_nessus_plugins ON vuln_nessus_plugins.id = vuln_nessus_settings_plugins.id';
            }

            if (!empty($search_filters['family_id'])) {
                $params[] = $search_filters['family_id'];
                $q_where .= " AND vuln_nessus_settings_plugins.family = ?";
            }

            if (!empty($search_filters['category_id'])) {
                $params[] = $search_filters['category_id'];
                $q_where .= " AND vuln_nessus_settings_plugins.category = ?";
            }

            if (!empty($search_filters['cve'])) {
                $params[] = '%' . $search_filters['cve'] . '%';
                $q_where .= " AND vuln_nessus_plugins.cve_id LIKE ?";
            }

            if (!empty($search_filters['plugin'])) {
                $params[] = '%' . $search_filters['plugin'] . '%';
                $q_where .= " AND vuln_nessus_plugins.name LIKE ?";
            }

            $query = "SELECT DISTINCT vuln_nessus_settings_plugins.family FROM vuln_nessus_settings_plugins $q_tables
                      WHERE vuln_nessus_settings_plugins.enabled = ? AND sid = ?
                      $q_where";

            $rs = $dbconn->Execute($query, $params);

            if (!$rs) {
                Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

                $result["status"] = "error";
                $result["message"] = $dbconn->ErrorMsg();

                return $result;
            }

            while (!$rs->EOF)
            {
                $families[$rs->fields['family']] = $rs->fields['family'];
                $rs->MoveNext();
            }
        }

        if ($plugins['enable_all'] == 0 && count($plugins['enabled']) > 0) {
            $params = array($sid);

            $query = "SELECT DISTINCT vuln_nessus_settings_plugins.family FROM vuln_nessus_settings_plugins
                      WHERE sid = ? and id IN ('".implode("','", $plugins['enabled'])."')
                      AND vuln_nessus_settings_plugins.enabled = 'N'";

            $rs = $dbconn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();

                return $result;
            }

            while (!$rs->EOF)
            {
                $families[$rs->fields['family']] = $rs->fields['family'];
                $rs->MoveNext();
            }
        }

        if ($plugins['disable_all'] == 0 && count($plugins['disabled']) > 0) {
            $params = array($sid);

            $query = "SELECT DISTINCT vuln_nessus_settings_plugins.family FROM vuln_nessus_settings_plugins
                      WHERE sid = ? and id IN ('".implode("','", $plugins['disabled'])."')
                      AND vuln_nessus_settings_plugins.enabled = 'Y'";

            $rs = $dbconn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();

                return $result;
            }

            while (!$rs->EOF)
            {
                $families[$rs->fields['family']] = $rs->fields['family'];
                $rs->MoveNext();
            }
        }

        $result["message"] = $families;

        return $result;
    }


    /**
     * Save GVM plugins into the database
     *
     * @param object $dbconn          Database access object
     * @param string $sid             Profile ID
     * @param array  $search_filters  Search criteria to find plugins that will be enabled or disable
     * @param array  $plugins         Plugins IDs that will be enabled or disabled individually
     * @return array
     * @throws Exception
     */
    public static function saveplugins_in_db($dbconn, $sid, $search_filters, $plugins)
    {
        $result = array(
            'status' => 'OK',
            'message' => array()
        );

        //Get families that will be affected by changes
        $affected_families = self::get_affected_families($dbconn, $sid, $search_filters, $plugins);

        if ($affected_families['status'] == 'error'){
            return $affected_families;
        }

        $result['message'] = $affected_families['message'];

        if ($plugins['enable_all'] || $plugins['disable_all']) {
            $q_where = '';
            $q_tables = '';

            $params = array();
            $params[] = ($plugins['enable_all'] == '1') ? 'Y' : 'N';
            $params[] = $sid;

            if (!empty($search_filters['cve']) || !empty($search_filters['plugin'])) {
                $q_tables .= 'INNER JOIN vuln_nessus_plugins ON vuln_nessus_plugins.id = vuln_nessus_settings_plugins.id';
            }

            if (!empty($search_filters['family_id'])) {
                $params[] = $search_filters['family_id'];
                $q_where .= " AND vuln_nessus_settings_plugins.family = ?";
            }

            if (!empty($search_filters['category_id'])) {
                $params[] = $search_filters['category_id'];
                $q_where .= " AND vuln_nessus_settings_plugins.category = ?";
            }

            if (!empty($search_filters['cve'])) {
                $params[] = '%' . $search_filters['cve'] . '%';
                $q_where .= " AND vuln_nessus_plugins.cve_id LIKE ?";
            }

            if (!empty($search_filters['plugin'])) {
                $params[] = '%' . $search_filters['plugin'] . '%';
                $q_where .= " AND vuln_nessus_plugins.name LIKE ?";
            }

            $query = "UPDATE vuln_nessus_settings_plugins $q_tables
                      SET vuln_nessus_settings_plugins.enabled = ?
                      WHERE sid = ?
                      $q_where";

            $rs = $dbconn->Execute($query, $params);

            if (!$rs) {
                Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

                $result["status"] = "error";
                $result["message"] = $dbconn->ErrorMsg();

                return $result;
            }
        }

        if ($plugins['enable_all'] == 0 && count($plugins['enabled']) > 0) {
            $params = array($sid);

            $query = "UPDATE vuln_nessus_settings_plugins
                  SET enabled = 'Y'
                  WHERE sid = ? and id IN ('".implode("','", $plugins['enabled'])."')";


            $rs = $dbconn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();

                return $result;
            }
        }

        if ($plugins['disable_all'] == 0 && count($plugins['disabled']) > 0) {
            $params = array($sid);

            $query = "UPDATE vuln_nessus_settings_plugins
                  SET enabled = 'N'
                  WHERE sid = ? and id IN ('".implode("','", $plugins['disabled'])."')";


            $rs = $dbconn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

                $result["status"]  = "error";
                $result["message"] = $dbconn->ErrorMsg();

                return $result;
            }
        }

        return $result;
    }


    public static function saveprefs_in_db($dbconn, $sid, $data)
    {
        // get the profile prefs for use later
        $query = "SELECT t.id, t.nessus_id,
            t.type, t.category
            FROM vuln_nessus_preferences_defaults t
            LEFT JOIN vuln_nessus_settings_preferences n
            ON t.nessus_id = n.nessus_id
            and n.sid = '$sid'
            order by category desc, nessusgroup, nessus_id";

        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        $result = $dbconn->execute($query);

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

            list ($nvt_oid, $nessus_id, $type, $category) = $result->fields;

            $result_update = self::updatedb($nessus_id, $$vname, $dbconn, $type, $category, $sid, $uuid, $nvt_oid);

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

        $query = "select n.nessus_id
            from vuln_nessus_settings_preferences n
            left join vuln_nessus_preferences_defaults t
            on n.nessus_id = t.nessus_id
            where t.nessus_id is null";

        $result = $dbconn->execute($query);

        while(!$result->EOF)
        {
            list ($pleasedeleteme) = $result->fields;
            $query2 = "DELETE from vuln_nessus_settings_preferences where nessus_id = \"$pleasedeleteme\"";
            $dbconn->execute($query2);

            $result->MoveNext();
        }

        return array('status' => 'OK', 'message' => '');
    }

    public static function updatedb($nessus_id, $fieldvalue, $dbconn, $type, $category, $sid, $uuid, $nvt_oid=null)
    {
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        if ($type=="C" and $fieldvalue=='')
        {
            $fieldvalue = "no";
        }

        $query = "SELECT count(*) FROM vuln_nessus_settings_preferences WHERE sid = ? AND nessus_id = ?";

        $params = array($sid, $nessus_id);

        $result = $dbconn->execute($query, $params);

        if($result === FALSE)
        {
            // SQL error
            return array("status" => "error", "message" => $dbconn->ErrorMsg());
        }

        list($existing)=$result->fields;

        if ($existing == 0)
        {
            # Do an insert statement
            if ($type == "P" && !empty($fieldvalue))
            {
                $query  = "INSERT vuln_nessus_settings_preferences SET nessus_id = ?, value = AES_ENCRYPT(? , ?), type = ?, category = ?, sid = ?, id=?";
                $params = array($nessus_id, $fieldvalue, $uuid, $type, $category, $sid, $nvt_oid);
            }
            else
            {
                $query  = "INSERT vuln_nessus_settings_preferences SET nessus_id = ?, value = ?, type = ?, category = ?, sid = ?, id=?";
                $params = array($nessus_id, $fieldvalue, $type, $category, $sid, $nvt_oid);
            }
        }
        else
        {
            if ($type == "P" && Util::is_fake_pass($fieldvalue))
            {
                $query  = "UPDATE vuln_nessus_settings_preferences SET type = ?, category = ?  WHERE nessus_id = ? AND sid = ?";
                $params = array($type, $category, $nessus_id, $sid);
            }
            else
            {
                if ( $type == "P" && !empty($fieldvalue) )
                {
                    $query  = "UPDATE vuln_nessus_settings_preferences SET value = AES_ENCRYPT(?, ?) , type = ?, category = ? WHERE nessus_id = ? AND sid = ?";
                    $params = array($fieldvalue, $uuid, $type, $category, $nessus_id, $sid);
                }
                else
                {
                    $query  = "UPDATE vuln_nessus_settings_preferences SET value = ? , type = ?, category = ? WHERE nessus_id = ? AND sid = ?";
                    $params = array($fieldvalue, $type, $category, $nessus_id, $sid);
                }
            }
        }

        $result = $dbconn->execute($query, $params);

        if($result === FALSE)
        {
            // SQL error
            return array("status" => "error", "message" => $dbconn->ErrorMsg());
        }

        return array( "status" => "OK", "message" => '');
    }

    /**
     * Save GVM plugins into a sensor
     *
     * @param object  $dbconn     Database access object
     * @param string  $sensor_id  Sensor ID
     * @param string  $sid        Profile ID
     * @param array   $families   Families that will be updated
     * @return array
     * @throws Exception
     */
    public static function saveplugins_in_sensor($dbconn, $sensor_id, $sid, $families)
    {
        $result = array(
            "status"  => "OK",
            "message" => ''
        );

        //Check that GVM profile exists in the sensor
        $rs_status = Vulnerabilities::check_profile_in_sensor ($dbconn, $sensor_id, $sid);

        if($rs_status["status"] == "not_found") {
            $result["status"]  = "error";
            $result["message"] = _('Profile not found');

            return $result;
        }

        //Count all families
        $query = "SELECT count(id) AS total FROM vuln_nessus_family";
        $rs = $dbconn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $dbconn->ErrorMsg());

            $result["status"] = "error";
            $result["message"] = $dbconn->ErrorMsg();

            return $result;
        }

        $total_families = intval($rs->fields['total']);
        $sensor_ip = Av_sensor::get_ip_by_id($dbconn, $sensor_id);

        $gvm = new Gvm($sensor_ip);


        //All families will be updated at once
        if (count($families) == $total_families){
            $result = $gvm->set_plugins_by_family($sid);
        } else {
            //Update families
            if (count($families) > 0) {
                foreach ($families as $f_key => $f_id){
                    $res = $gvm->set_plugins_by_family($sid, $f_id);

                    if ($res['status'] == 'error') {
                        $result = $res;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Save autoenable config for a GVM profile in a sensor
     *
     * @param object  $dbconn     Database access object
     * @param string  $sensor_id  Sensor ID
     * @param string  $sid        Profile ID
     * @return array
     * @throws Exception
     */
    public static function save_autoenable_plugins_in_sensor($dbconn, $sensor_id, $sid)
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

        $gvm    = new Gvm($sensor_object->get_ip());
        $result = $gvm->set_plugins_by_family($sid);

        return $result;
    }

    public static function saveprefs_in_sensor($dbconn, $sensor_id, $sid)
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

        $gvm    = new Gvm($sensor_object->get_ip());

        $result = $gvm->set_preferences($sid);

        return $result;
    }

    public static function update_db_profile($dbconn, $sid, $sname, $sdescription, $username, $data)
    {
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        if (empty($sname))
        {
            return array("message" => "Please specify profile name", "status" => "error");
        }

        $params = array($sname, Util::htmlentities($sdescription), $username, $sid);
        $query  = "UPDATE vuln_nessus_settings
                   SET name = ?, description = ?, owner = ?
                   WHERE id = ?";

        $dbconn->execute($query, $params);

        while (list($key, $value) = each($data))
        {
            $value = intval($value);
            $key   = substr($key, 2);
            $query = "update vuln_nessus_settings_family set status = ? where sid = ? and fid = ?";
            $params = array($value, $sid, $key);

            $results = $dbconn->Execute($query, $params);
        }

        $query  = "select t1.fid, t1.status from vuln_nessus_settings_family as t1 where sid = ?";
        $params = array($sid);

        $result = $dbconn->execute($query, $params);

        while (!$result->EOF)
        {
            list($fid, $catstatus) = $result->fields;

            if ($catstatus == 3)
            {
                $query = "update vuln_nessus_settings_plugins set enabled = 'N' where family = ? and sid = ?";
                $params = array($fid, $sid);
                $dbconn->execute($query, $params);
            }
            elseif ($catstatus == 1 || $catstatus == 2)
            {
                $query = "update vuln_nessus_settings_plugins set enabled = 'Y' where family = ? and sid = ?";
                $params = array($fid, $sid);
                $dbconn->execute($query, $params);
            }

            $result->MoveNext();
        }

        return array ('status' => 'OK', 'message' => '');
    }


    public static function create_db_profile($dbconn, $sname, $sdescription, $username, $cloneid, $data)
    {
        $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

        $sdescription = Util::htmlentities($sdescription);

        if (empty($sname))
        {
            return array("message" => "Please specify profile name", "status" => "error");
        }

        if($cloneid != '')
        {
            // create new entry in the vuln_nessus_settings table first and get the new id
            $newPID = md5($sname);
            $params = array(
                $newPID,
                $sname,
                $sdescription,
                $username
            );

            $insert = "INSERT INTO vuln_nessus_settings (`id`, `name`, `description`, `owner` )
                       VALUES (?, ?, ?, ?)";
            $result = $dbconn->execute($insert, $params);
            if($result === FALSE)
            {
                return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
            }

            $params = array($cloneid);
            $query = "insert into vuln_nessus_settings_family (`sid`, `fid`, `status`)
                    (select '$newPID' as sid, fid, status
                    from vuln_nessus_settings_family
                    where sid = ?)";

            $result = $dbconn->execute($query, $params);
            if($result === FALSE)
            {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }

            $params = array($cloneid);
            $query = "insert into vuln_nessus_settings_preferences (`sid`, `id`, `nessus_id`, `value`, `category`, `type`)
                   (select '$newPID' as sid, id, nessus_id, value,
                       category, type
                    from vuln_nessus_settings_preferences
                    where sid = ?)";

            $result = $dbconn->execute($query, $params);
            if($result === FALSE)
            {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }

            $params = array($cloneid);
            $query = "insert into vuln_nessus_settings_plugins (`id`, `sid`, `enabled`, `category`, `family`)
                        (select id, '$newPID' as sid, enabled, category,
                            family from vuln_nessus_settings_plugins
                         where sid = ?)";


            $result = $dbconn->execute($query, $params);
            if($result === FALSE)
            {
                return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
            }
        }
        else
        {
            // Create a new profile from scratch

            // Check if the name is duplicate or not
            $params = array($sname);
            $query = "SELECT count(name) FROM vuln_nessus_settings WHERE name = ?";
            $result = $dbconn->execute($query, $params);

            list($count) = $result->fields;

            if ($count > 0)
            {
                return ( array ( "message" => "Cannot create new profile. Duplicate profile name $sname exists.", "status" => "error" ) );
            }
            else
            {
                $sid = md5($sname);
                $params = array($sid, $sname, $sdescription, $username);
                $query  = "INSERT into vuln_nessus_settings (`id`, `name`, `description`, `owner`)
                       values (?, ?, ?, ?)";

                $result = $dbconn->execute($query, $params);
                if($result === FALSE)
                {
                    return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
                }

                while (list($key, $value) = each ($data))
                {
                    $value = intval($value);
                    $key = substr($key, 2);

                    $params = array($sid, $key, $value);
                    $query  = "insert into vuln_nessus_settings_family (`sid`, `fid`, `status`) values(?, ?, ?)";

                    $result = $dbconn->Execute($query, $params);
                    if($result === FALSE)
                    {
                        return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
                    }
                }

                $query = "insert into vuln_nessus_settings_preferences (`sid`, `id`, `nessus_id`, `value`, `category`, `type`)
                          select '$sid' as sid, id, nessus_id, value, category, type from vuln_nessus_preferences";

                $result = $dbconn->execute($query);

                if($result === FALSE)
                {
                    return array ("message" => $dbconn->ErrorMsg(), "status" => "error" );
                }

                $query = "insert into vuln_nessus_settings_plugins (`id`, `sid`, `enabled`, `category`, `family`)
                            select id, '$sid' as sid, enabled, category, family from vuln_nessus_plugins";

                $result = $dbconn->execute($query);
                if($result === FALSE)
                {
                    return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
                }

                $query = "select t1.fid, t1.status
                          from vuln_nessus_settings_family as t1
                          where sid = ?";

                $params = array($sid);
                $result = $dbconn->execute($query, $params);

                while (!$result->EOF)
                {
                    list($fid, $catstatus) = $result->fields;

                    if ($catstatus == 3)
                    {
                        $params = array($fid, $sid);
                        $query1 = "update vuln_nessus_settings_plugins
                                   set enabled='N'
                                   where family=? and sid=?";


                        $result1 = $dbconn->execute($query1, $params);
                        if($result1 === FALSE)
                        {
                            return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
                        }
                    }
                    elseif ($catstatus == 1 || $catstatus == 2)
                    {
                        $params = array($fid, $sid);
                        $query1 = "update vuln_nessus_settings_plugins
                             set enabled='Y'
                             where family=? and sid=?";

                        $result1 = $dbconn->execute($query1, $params);
                        if($result1 === FALSE)
                        {
                            return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
                        }
                    }

                    $result->MoveNext();
                }
            }
        }

        return array("message" => '', "status" => "OK");
    }


    public static function create_sensor_profile($dbconn, $sensor_id, $sname, $owner, $cloneid )
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

        $gvm    = new Gvm($sensor_object->get_ip());

        $sid    = $dbconn->GetOne("SELECT id FROM vuln_nessus_settings WHERE name='$sname' AND owner='$owner'");

        $result = $gvm->create_new_config($sid, $cloneid);

        return $result;
    }


    public static function get_profiles($dbconn, $default="")
    {
        $query  = "SELECT id, `name` FROM vuln_nessus_settings ";

        if($default !== ""){
            $query .= " WHERE `default` = $default";
        }

        $result = $dbconn->execute($query);
        if($result === FALSE)
        {
            return ( array ( "message" => $dbconn->ErrorMsg(), "status" => "error" ) );
        }

        $salida = [];
        while (!$result->EOF) {
            $salida[$result->fields["id"]] = $result->fields["name"];
            $result->MoveNext();
        }

        return $salida;
    }


    public static function get_default_profiles($dbconn){
        return static::get_profiles($dbconn, 1);
    }


    public static function check_profile_in_sensor ($dbconn, $sensor_id, $sid)
    {
        $sensor_object = new Av_sensor($sensor_id);

        $sensor_object->load_from_db($dbconn);

        $check_result  = $sensor_object->check_vs_connection($dbconn);

        if( $check_result != '' )
        {
            $result['status']  = 'error';
            $result['message'] = $check_result;

            return $result;
        }

        $gvm    = new Gvm($sensor_object->get_ip());

        $config_id = $gvm->get_config_id_by_sid($sid);

        if($config_id != '')
        {
            $result = array('status' => 'already_exits', 'message' => '');
        }
        else
        {
            $result = array('status' => 'not_found', 'message' => '');
        }

        return $result;
    }


    public static function can_modify_profile($conn, $name, $owner)
    {
        $defaultProfiles = static::get_default_profiles($conn);
        if( in_array($name, array_values($defaultProfiles)) )
        {
            return FALSE;
        }
        else if (Session::am_i_admin())
        {
            return TRUE;
        }
        else
        {
            $me = Session::get_session_user();

            $my_user_vision = $_SESSION['_user_vision']['user'];
            if (empty($my_user_vision)  && Session::is_pro())
            {
                $user_vision    = Acl::get_user_vision($conn);
                $my_user_vision = $user_vision['user'];
            }

            if (($owner == $me || $my_user_vision[$owner] == 2))
            {
                return TRUE;
            }
        }

        return FALSE;
    }


    public static function can_delete_profile($conn, $name, $owner)
    {
        return static::can_modify_profile($conn, $name, $owner);
    }


    public static function delete_sensor_config($dbconn, $sensor_id, $sid)
    {

        $sensor_object = new Av_sensor($sensor_id);

        $sensor_object->load_from_db($dbconn);

        $check_result  = $sensor_object->check_vs_connection($dbconn);

        if( $check_result != '' )
        {
            $result['status']  = 'error';
            $result['message'] = $check_result;

            return $result;
        }

        $gvm    = new Gvm($sensor_object->get_ip());
        $result = $gvm->delete_config($sid);

        return $result;
    }


    public static function delete_db_profile($dbconn, $sid)
    {

        $query = "delete from vuln_nessus_settings where id=?";
        if ($dbconn->execute($query, [$sid]) === FALSE)
        {
            return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
        }


        $query="delete from vuln_nessus_settings_preferences where sid=?";
        if ($dbconn->execute($query, [$sid]) === FALSE)
        {
            return array ("message" => $dbconn->ErrorMsg(), "status" => "error" );
        }

        $query = "delete from vuln_nessus_settings_plugins where sid=?";
        if ($dbconn->execute($query, [$sid]) === FALSE)
        {
            return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
        }

        $query = "delete from vuln_nessus_settings_family where sid=?";
        if($dbconn->execute($query, [$sid])=== FALSE)
        {
            return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
        }

        $query = "delete from vuln_nessus_settings_sensor where vns_id=?";
        if($dbconn->execute($query, [$sid])=== FALSE)
        {
            return array ("message" => $dbconn->ErrorMsg(), "status" => "error");
        }

        return array ("message" => '', "status" => "OK");
    }


    public static function modify_profile_ID_in_sensors($dbconn, $new_name, $new_user)
    {
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

            $gvm    = new Gvm($sensor_object->get_ip());

            $config_id = $gvm->get_config_id_by_name_comment($new_name, $new_user);

            $xml = $gvm->execute_command("<modify_config config_id='$config_id'><name>$new_name</name><comment>$new_user</comment></modify_config>");

            if ($xml === FALSE)
            {
                return array ( "message" => "Error modifying config id $config_id in sensor $sensor_id", "status" => "error" );
            }
        }

        return array("message" => '', "status" => "OK");
    }

    /**
     * Function get_vulnerabilities
     *
     * This function returns the asset vulnerabilities
     *
     * @param object  $conn     Database access object
     * @param string  $tables   [Optional] Database tables
     * @param array   $filters  [Optional] Filters applied to the query
     * @param boolean $cache    [Optional] Use cached information
     *
     * @access public
     * @return array          List of vulnerabilities
     * @throws Exception      If a connection error occurred
     */
    public static function get_vulnerabilities($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $vulnerabilities = array();

        $total = 0;

        //Build SQL

        $perms_where = Asset_host::get_perms_where('host.', TRUE);

        $q_select = 'lr.hostIP, lr.app, lr.service, lr.port, lr.protocol, lr.scriptid, lr.risk, lr.msg, p.name, HEX(lr.ctx) as ctx';

        $q_tables = 'host, host_ip, vuln_nessus_latest_results lr LEFT JOIN vuln_nessus_plugins p ON p.id=lr.scriptid';

        $q_where  = 'WHERE lr.falsepositive = "N"
                     AND host_ip.host_id = host.id
                     AND host.ctx = lr.ctx
                     AND lr.hostIP = INET6_NTOA(host_ip.ip) ' . $perms_where;

        if (!empty($tables))
        {
            $q_tables .= $tables;
        }

        if (!empty($filters['where']))
        {
            $q_where  .= ' AND ' . $filters['where'];
        }

        $q_where  .= (!empty($filters['order_by'])) ? ' ORDER BY ' . $filters['order_by'] : ' ORDER BY lr.risk ASC';


        if (!empty($filters['limit']))
        {
            $q_select  = ' SQL_CALC_FOUND_ROWS ' . $q_select;
            $q_where  .= ' LIMIT ' . $filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        // Has LIMIT
        if (!empty($filters['limit']))
        {
            $query = "SELECT DISTINCT $q_select, HEX(host.id) AS h_id FROM $q_tables $q_where";

            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = Ossim_db::get_found_rows($conn, $query, $cache);

            while (!$rs->EOF)
            {
                $id          = $rs->fields['h_id'];
                $ip          = $rs->fields['hostIP'];
                $ctx         = $rs->fields['ctx'];
                $app         = $rs->fields['app'];
                $service     = $rs->fields['service'];
                $port        = $rs->fields['port'];
                $protocol    = $rs->fields['protocol'];
                $plugin_id   = $rs->fields['scriptid'];
                $plugin_name = ($rs->fields['scriptid'] == 0) ? _('Open port') : $rs->fields['name'];
                $risk        = $rs->fields['risk'];
                $msg         = $rs->fields['msg'];

                $vulnerabilities[$id][] = array(
                    'ip'        => $ip,
                    'app'       => $app,
                    'service'   => $service,
                    'port'      => $port,
                    'protocol'  => $protocol,
                    'plugin_id' => $plugin_id,
                    'plugin'    => $plugin_name,
                    'risk'      => $risk,
                    'msg'       => $msg,
                    'date'      => self::get_latest_timestamp($conn, $ip, $ctx, $service, $port, $protocol, $plugin_id)
                );

                $rs->MoveNext();
            }
        }
        else
        {
            $counter_name = ($cache) ? 'total_'.md5($q_select.$q_tables.$q_where) : 'total';

            // First count to do block requests
            $query = ossim_query("SELECT COUNT(*) AS $counter_name FROM (SELECT DISTINCT $q_select, HEX(host.id) AS h_id FROM $q_tables $q_where) as vulns_list");

            $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rf)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = intval($rf->fields[$counter_name]);

            $block = 2000;
            $rf->Free();

            for ($i = 0; $i <= $total; $i += $block)
            {
                $query = ossim_query("SELECT DISTINCT $q_select, HEX(host.id) AS h_id FROM $q_tables $q_where LIMIT $i, $block");

                $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                while (!$rs->EOF)
                {
                    $id          = $rs->fields['h_id'];
                    $ip          = $rs->fields['hostIP'];
                    $ctx         = $rs->fields['ctx'];
                    $app         = $rs->fields['app'];
                    $service     = $rs->fields['service'];
                    $port        = $rs->fields['port'];
                    $protocol    = $rs->fields['protocol'];
                    $plugin_id   = $rs->fields['scriptid'];
                    $plugin_name = ($rs->fields['scriptid'] == 0) ? _('Open port') : $rs->fields['name'];
                    $risk        = $rs->fields['risk'];
                    $msg         = $rs->fields['msg'];

                    $vulnerabilities[$id][] = array(
                        'ip'        => $ip,
                        'app'       => $app,
                        'service'   => $service,
                        'port'      => $port,
                        'protocol'  => $protocol,
                        'plugin_id' => $plugin_id,
                        'plugin'    => $plugin_name,
                        'risk'      => $risk,
                        'msg'       => $msg,
                        'date'      => self::get_latest_timestamp($conn, $ip, $ctx, $service, $port, $protocol, $plugin_id)
                    );

                    $rs->MoveNext();
                }

                $rs->Free();
            }
        }

        return array($vulnerabilities, $total);
    }


    /**
    * This function returns the latest timestamp of a vulnerability
    *
    * @param object   $conn       Database access object
    * @param string   $ip         Asset IP
    * @param array    $ctx        Asset Context
    * @param string   $service    Service
    * @param integer  $port       Port
    * @param integer  $protocol   Protocol
    * @param integer  $plugin_id  Plugin ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_latest_timestamp($conn, $ip, $ctx, $service, $port, $protocol, $plugin_id)
    {
        Ossim_db::check_connection($conn);

        $query = 'SELECT MAX(scantime) AS scantime FROM vuln_nessus_latest_results
            WHERE hostIP = ?
            AND ctx = UNHEX(?)
            AND service = ?
            AND port = ?
            AND protocol = ?
            AND scriptid = ?
            AND falsepositive = "N"';

        $params = array($ip, $ctx, $service, $port, $protocol, $plugin_id);

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $scantime = $rs->fields['scantime'];

        return $scantime;
    }

    /**
     * This function returns the label related to a risk factor
     *
     * @param integer $risk  Risk Factor
     * @return string
     */
    public static function get_severity_by_risk($risk)
    {
        if (is_numeric($risk)){
            if ($risk == 1)
            {
                return _('Critical');
            }
            elseif ($risk == 2)
            {
                return _('High');
            }
            elseif ($risk == 3)
            {
                return _('Medium');
            }
            elseif ($risk <= 6)
            {
                return _('Low');
            }
            else
            {
                return _('Info');
            }
        } else{
            return _('Unknown');
        }
    }


    public static function is_scheduled($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $params = array($id);
        $query  = "SELECT job_id FROM vuln_job_assets WHERE asset_id = UNHEX(?)";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
     * This function returns an OTX link to get information about a specific CVE Id
     *
     * @param string $cve_id CVE ID
     * @return bool|string
     */
    public static function get_cve_link($cve_id){
        $cve_link = 'https://otx.alienvault.com/indicator/cve/';

        $cve_id = strtoupper(trim($cve_id));
        if (!security_class::ossim_valid($cve_id, OSS_CVE_ID, 'illegal:' . _('CVE Id'))) {

            security_class::ossim_clean_error();
            return false;
        }

        return $cve_link.$cve_id;
    }
}

/* End of file vulnerabilities.inc */
/* Location: ../include/classes/vulnerabilities.inc */
