<?php
/**
* Backup.inc
*
* File Backup.inc is used to:
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
* @package    ossim-framework\Database
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Insert()
* - Delete()
* - str2date()
* - array2str()
* - running_restoredb()
*/
		
class Backup
{
    function Insert($insert, $filter_by = "", $nomerge = "merge")
    {
		if (!is_array($insert) || count($insert)<1) return;
		// Make frameworkd call
		// backup action="backup_restore"  begin="2012-05-12" end="2012-06-12" entity="563f77f5-4061-43c5-bc2b-b57f8fef95f8" newbbdd="1" bbddhost="host" bbdduser="kkka2" bbddpasswd="agaag"
		$conf   = $GLOBALS["CONF"];
		$dbhost = $conf->get_conf("ossim_host");
		$dbuser = $conf->get_conf("ossim_user");
		$dbpass = $conf->get_conf("ossim_pass");
		
		$first = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$insert[count($insert)-1]);
		$last  = (preg_match("/\d\d\d\d\d\d\d\d/",$insert[0])) ?  preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$insert[0]) : $first;
		$files = "$first-$last";
		$newdb = ($nomerge == "merge") ? "0" : "1";
		
		$cmd = 'backup action="backup_restore"  begin="'.$first.'" end="'.$last.'" entity="'.$filter_by.'" newbbdd="'.$newdb.'" bbddhost="'.$dbhost.'" bbdduser="'.$dbuser.'" bbddpasswd="'.$dbpass.'"';
		$status = Backup::send_command($cmd);
		if ($status > 0)
		{
			Backup::insert_restore_db("Insert events from $first to $last");
		}
        
        $infolog = array(
            $files
        );
        Log_action::log(44, $infolog);
        return $status;
    }


    function Delete($delete)
    {
		if (!is_array($delete) || count($delete)<1) return;
		// Make frameworkd call
		// backup action="backup_restore"  begin="2012-05-12" end="2012-06-12" entity="563f77f5-4061-43c5-bc2b-b57f8fef95f8" newbbdd="1" bbddhost="host" bbdduser="kkka2" bbddpasswd="agaag"
		$conf   = $GLOBALS["CONF"];
		$dbhost = $conf->get_conf("ossim_host");
		$dbuser = $conf->get_conf("ossim_user");
		$dbpass = $conf->get_conf("ossim_pass");
		
		$files = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",Backup::array2str($delete));
		
		$cmd = 'backup action="purge_events"  dates="'.$files.'" bbddhost="'.$dbhost.'" bbdduser="'.$dbuser.'" bbddpasswd="'.$dbpass.'"';
		$status = Backup::send_command($cmd);
		if ($status > 0)
		{
			Backup::insert_restore_db("Delete events from $files");
		}
        
        $infolog = array(
            $files
        );
        Log_action::log(45, $infolog);
        return $status;
    }


    function str2date($str)
    {
        list($year, $month, $day) = sscanf($str, "%4s%2s%2s");
        return "$year-$month-$day";
    }


    function array2str($arr)
    {
        $str = '';
        if (is_array($arr))
        {
            while (list($key, $value) = each($arr))
            {
                if ($str == '')
                {
                    $str = $value;
                }
                else 
                {
                    $str.= "," . $value;
                }
            }
        }
        return $str;
    }


	function insert_restore_db($list)
	{
		$db1    = new ossim_db();
		$conn1  = $db1->connect();
		$conn1->Execute(ossim_query("INSERT INTO restoredb_log (date, pid, users, data, status, percent) 
            VALUES ('', 0, '".Session::get_session_user()."', '$list', 1, 0)"));
		$db1->close();
	}


	function set_failed()
	{
		$db1    = new ossim_db();
		$conn1  = $db1->connect();
		$conn1->Execute(ossim_query("UPDATE restoredb_log SET status = -1,percent = 100 WHERE status=1"));
		$db1->close();
	}


	function set_finished()
	{
		$db1    = new ossim_db();
		$conn1  = $db1->connect();
		$conn1->Execute(ossim_query("UPDATE restoredb_log SET status = 2,percent = 100 WHERE status=1"));
		$db1->close();
	}


	function send_command($action="")
	{
		if ($action=="") $action = 'backup action="backup_status"';
		$timeout = array('sec' => 5, 'usec' => 0);
	    $ossim_conf = $GLOBALS["CONF"];
	    $address = $ossim_conf->get_conf("frameworkd_address");
	    $port = $ossim_conf->get_conf("frameworkd_port");
		
		error_log(gmdate("Y-m-d H:i:s").", Launch: $action\n",3,"/var/tmp/restore_actions.log");
    	// try to connect with frameworkd and "getconnectedagents"
	    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0)
	    {
			$msg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
			error_log(gmdate("Y-m-d H:i:s").", $msg\n\n",3,"/var/tmp/restore_actions.log");
	        return $msg;
	    }
	    /* connect */
	    socket_set_block($socket);	    
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$timeout);
		socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$timeout);
	    $result = @socket_connect($socket, $address, $port);
	    if (!$result)
	    {
			$msg = _("Can't connect with frameworkd")." ($address:$port)";
			error_log(gmdate("Y-m-d H:i:s").", $msg\n\n",3,"/var/tmp/restore_actions.log");
	        return $msg;
	    }
    	$in = "$action\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 5120, PHP_BINARY_READ);
		// Error found
		if (preg_match("/.* errno\=\"(\d+)\" error\=\"(.*)\" ack(end)?$/",$out,$found))
		{
			@socket_close($socket);
			error_log(gmdate("Y-m-d H:i:s").", return:".$found[2]."\n\n",3,"/var/tmp/restore_actions.log");
			return $found[2];
		}
		// Status found
		if (preg_match("/.* status\=\"(\d+)\" ack(end)?$/",$out,$found))
		{
			@socket_close($socket);
			error_log(gmdate("Y-m-d H:i:s").", return:".$found[1]."\n\n",3,"/var/tmp/restore_actions.log");
			return $found[1];
	    }
		// Bad response
	    @socket_close($socket);
		$msg = _("Bad response from frameworkd");
		error_log(gmdate("Y-m-d H:i:s").", $msg\n\n",3,"/var/tmp/restore_actions.log");
		return $msg;
	}


	function is_running()
	{
		$status = Backup::send_command('backup action="backup_status"');
		return (intval($status)>0) ? true : false;
    }


    function print_backup_process($status = 0)
    {
        $error       = "";
    	$reloc       = false;
    	$in_progress = "<script type='text/javascript'>
			            $('#restore_msg').html('".gettext("Current task in progress")."<img src=\"../pixmaps/loading3.gif\" border=\"0\">');
			            </script>\n";
    	$error       = "<script type='text/javascript'>
			            $('#restore_msg').html('".gettext("Error during restore")."');
			            </script>\n";
		$redirect    = "<script type='text/javascript'>parent.location.href='index.php';</script>"; 
    	
    	$status      = intval($status);
    	
    	// If $status == 2 an action is running
    	
    	$run         = ($status == 2) ? $status : Backup::is_running();
    	
		if ($run>0)
		{
			echo $in_progress;
			flush();ob_flush();
			session_write_close();
			$error = "";
			$reloc = true;
			while ($run>0)
			{
				sleep(5);
				$run = Backup::is_running();
				if ($run > 0)
				{ // Running=1 or Pending=2
					echo $in_progress;
				}
				elseif( $run < 0)
				{ // Failed
					Backup::set_failed();
					echo $error;
				}
				else
				{ // Finished
					Backup::set_finished();
					$reloc = true;
				}
				flush();ob_flush();
			}
		}
        if ($reloc)
        {
        	echo $redirect;
        }
    }
} //class backup

?>
