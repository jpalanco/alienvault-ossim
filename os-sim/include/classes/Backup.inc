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
    public static function Insert($insert, $filter_by = '', $nomerge = "merge")
    {
        if (!is_array($insert) || count($insert) < 1)
        {
            return;
        }

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

        $cmd    = 'backup action="backup_restore"  begin="'.$first.'" end="'.$last.'" entity="'.$filter_by.'" newbbdd="'.$newdb.'" bbddhost="'.$dbhost.'" bbdduser="'.$dbuser.'" bbddpasswd="'.$dbpass.'"';
        $status = self::send_command($cmd);

        if ($status > 0)
        {
            self::insert_restore_db("Insert events from $first to $last");
        }

        $infolog = array($files);
        Log_action::log(44, $infolog);

        return $status;
    }


    public static function Delete($delete)
    {
        if (!is_array($delete) || count($delete) < 1)
        {
            return;
        }

        // Make frameworkd call
        // backup action="backup_restore"  begin="2012-05-12" end="2012-06-12" entity="563f77f5-4061-43c5-bc2b-b57f8fef95f8" newbbdd="1" bbddhost="host" bbdduser="kkka2" bbddpasswd="agaag"
        $conf   = $GLOBALS["CONF"];
        $dbhost = $conf->get_conf("ossim_host");
        $dbuser = $conf->get_conf("ossim_user");
        $dbpass = $conf->get_conf("ossim_pass");

        $files = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",self::array2str($delete));

        $cmd = 'backup action="purge_events"  dates="'.$files.'" bbddhost="'.$dbhost.'" bbdduser="'.$dbuser.'" bbddpasswd="'.$dbpass.'"';
        $status = self::send_command($cmd);
        
        if ($status > 0)
        {
            self::insert_restore_db("Delete events from $files");
        }

        $infolog = array($files);

        Log_action::log(45, $infolog);

        return $status;
    }

    public function array2str($arr)
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


    public function insert_restore_db($list)
    {
        $db1    = new ossim_db();
        $conn1  = $db1->connect();
        $rs = $conn1->Execute(ossim_query("INSERT INTO restoredb_log (date, pid, users, data, status, percent)
            VALUES ('', 0, '".Session::get_session_user()."', '$list', 1, 0)"));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn1->ErrorMsg());
        }
        
        $db1->close();
    }


    public static function set_failed($conn)
    {
        $rs = $conn->Execute(ossim_query("UPDATE restoredb_log SET status = -1,percent = 100 WHERE status=1"));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function set_finished($conn)
    {
        $rs = $conn->Execute(ossim_query("UPDATE restoredb_log SET status = 2,percent = 100 WHERE status=1"));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
    
    /**
     * This function launches a query to ac_acid_event to know the progress of purge/restore action
     * 
     * @param object $conn
     * @param array  $dates
     * @return number
     */
    public static function get_progress($conn, $dates)
    {
        $total     = 0;
        $dates_str = '"'.implode('", "', $dates).'"';
        $sql       = 'SELECT sum(cnt) as total FROM alienvault_siem.ac_acid_event WHERE DATE(timestamp) IN ('.$dates_str.')';
        $rs        = $conn->Execute($sql);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $total = intval($rs->fields['total']);
                $rs->MoveNext();
            }
        }
        
        return $total;
    }


    public function send_command($action = '')
    {
        if ($action == '')
        {
            $action = 'backup action="backup_status"';
        }
        
        $timeout    = array('sec' => 5, 'usec' => 0);
        $ossim_conf = $GLOBALS["CONF"];
        $address    = '127.0.0.1';
        $port       = $ossim_conf->get_conf("frameworkd_port");

        file_put_contents("/var/tmp/restore_actions.log", gmdate("Y-m-d H:i:s").", Launch: $action\n", FILE_APPEND);

        // try to connect with frameworkd and "getconnectedagents"
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $msg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            file_put_contents("/var/tmp/restore_actions.log", gmdate("Y-m-d H:i:s").", $msg\n\n", FILE_APPEND);

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
            file_put_contents("/var/tmp/restore_actions.log", gmdate("Y-m-d H:i:s").", $msg\n\n", FILE_APPEND);
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
            file_put_contents("/var/tmp/restore_actions.log", gmdate("Y-m-d H:i:s").", return:".$found[2]."\n\n", FILE_APPEND);
            return $found[2];
        }

        // Status found
        if (preg_match("/.* status\=\"(\d+)\" ack(end)?$/",$out,$found))
        {
            @socket_close($socket);
            file_put_contents("/var/tmp/restore_actions.log", gmdate("Y-m-d H:i:s").", return:".$found[1]."\n\n", FILE_APPEND);
            return $found[1];
        }

        // Bad response
        @socket_close($socket);
        $msg = _("Bad response from frameworkd");
        file_put_contents("/var/tmp/restore_actions.log", gmdate("Y-m-d H:i:s").", $msg\n\n", FILE_APPEND);

        return -1;
    }

    /**
     * This function returns the status of a purge/restore backup action
     * 
     * @param object $conn
     * @return multitype:<number, string, number>
     */
    public static function is_running($conn)
    {
        $running_data = '';
        $status       = 0;
        $mode         = '';
        $dates        = array();
        $progress     = 0;
        
        // Look for running backup actions (database)
        $rs = $conn->Execute("SELECT * FROM restoredb_log WHERE status = 1");
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $running_data = $rs->fields['data'];
                $rs->MoveNext();
            }
        }
        
        if ($running_data != '')
        {
            // Look for running backup actions (frameworkd)
            $status = self::send_command('backup action="backup_status"');
            
            // Error from frameworkd
            if ($status < 0)
            {
                self::set_failed($conn);
            }
            // Something is running
            elseif ($status > 0)
            {
                if (preg_match('/Insert events from (\d{4}\-\d{2}\-\d{2}) to (\d{4}\-\d{2}\-\d{2})/', $running_data, $found))
                {
                    $mode      = 'insert';
                    $date_from = $found[1];
                    $date_to   = $found[2];
                    $date_cur  = strtotime($date_from);
                    while ($date_cur <= strtotime($date_to))
                    {
                        $dates[]   = date("Y-m-d", $date_cur);
                        $date_cur += 86400;
                    }
                }
                elseif (preg_match('/Delete events from (\d{4}\-\d{2}\-\d{2},?)+/', $running_data, $found))
                {
                    $mode  = 'delete';
                    $dates = explode(',', $found[1]);
                }
                
                // Calculate pending events to insert/purge
                if (count($dates) > 0)
                {
                    $progress = self::get_progress($conn, $dates);
                }
            }
            // Nothing is running (update restoredb_log table)
            else
            {
                self::set_finished($conn);
            }
        }
        
        return array($status, $mode, $progress);
    }
}

/* End of file Backup.inc */
/* Location: ../include/classes/Backup.inc */
