<?php
/**
 * dt_logs.php
 * 
 * File dt_logs.php is used to:
 * - Response ajax call from backup logs dataTable
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';


if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

// Close session write for real background loading
session_write_close();

$sec     =  POST('sEcho');
$maxrows = intval(POST('top'));
$status  = intval(POST('status'));


ossim_valid($sec, OSS_DIGIT, 'illegal: sEcho');

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


$tz = Util::get_timezone();

$array_result = array();
$file_log_api = "/var/log/alienvault/api/backup-notifications.log";
$file_log_frm = "/var/log/ossim/frameworkd.log";
$log_sources  = array('api' => 'Configuration', 'frm' => 'Events');

switch($status)
{
    case '1':
        $status_grep_api = " -- INFO --";
        $status_grep_frm = "BackupManager \[INFO\]";
        break;
    case '2':
        $status_grep_api = " -- WARNING --";
        $status_grep_frm = "BackupManager \[WARNING\]";
        break;
    case '3':
        $status_grep_api = " -- ERROR --";
        $status_grep_frm = "BackupManager \[ERROR\]";
        break;
    default:
        $status_grep_api = " -- ";
        $status_grep_frm = "BackupManager";
        break;
}


$f_api = fopen($file_log_api, "r");
$f_frm = fopen($file_log_frm, "r");

try
{
    $cmd              = "cat ? | grep ? | tail -n ?";
    $params           = array($file_log_api, $status_grep_api, $maxrows);
    $array_result_api = Util::execute_command($cmd, $params, 'array');
    $flag_error_api   = FALSE;
}
catch(Exception $e)
{
    $flag_error_api = TRUE;
}

try
{
    $cmd              = "cat ? | grep ? | grep -v 'password' | grep -v 'Checking' | grep -v 'Reloading Backup Configuration' | tail -n ?";
    $params           = array($file_log_frm, $status_grep_frm, $maxrows);
    $array_result_frm = Util::execute_command($cmd, $params, 'array');
    $flag_error_frm   = FALSE;
}
catch(Exception $e)
{
    $flag_error_frm = TRUE;
}

$array_result_frm = array_reverse($array_result_frm);
$array_result_api = array_reverse($array_result_api);

$data = array();

if (!$flag_error_api && !$flag_error_frm && (!empty($array_result_api) || !empty($array_result_frm)))
{
    $i_api = 0;
    $i_frm = 0;
    $top   = $maxrows * 2;
    	
    for($i = 0; $i < $top; $i++)
    {
        $contents_api = ($array_result_api[$i_api] != '') ? $array_result_api[$i_api] : '';
        $contents_frm = ($array_result_frm[$i_frm] != '') ? $array_result_frm[$i_frm] : '';
    
        if ($contents_api != '' || $contents_frm != '')
        {
    
        // Parse API Log 2011-07-01 13:41:58.859468 [FRAMEWORKD] -- INFO -- backup file already created (/var/lib/ossim/backup/phpgacl-backup_2011-07-01.sql)
            preg_match("/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\.\d+\s+\[[A-Za-z]+\]\s--\s([A-Za-z]+)\s--\s(.*)/", $contents_api, $_fields['api']);
    
            // Parse Frameworkd Log
            preg_match("/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}),\d+\s+BackupManager\s+\[([A-Za-z]+)\]\:\s*(.*)/", $contents_frm, $_fields['frm']);
    
    
            $_log_date_api = ($_fields['api'][1] != '') ? $_fields['api'][1] : '1970-01-01 00:00:00';
            $_log_date_frm = ($_fields['frm'][1] != '') ? $_fields['frm'][1] : '1970-01-01 00:00:00';
    
            $selected_log = ($_log_date_api > $_log_date_frm) ? 'api' : 'frm';
    
            $log_status = $_fields[$selected_log][2];
            $log_msg    = $_fields[$selected_log][3];
            $log_date   = $_fields[$selected_log][1];
    
            if ($tz != 0)
            {
                $log_date = gmdate("Y-m-d H:i:s",strtotime(Util::utc_from_localtime($log_date))+(3600*$tz));
            }
    
            //IF INFO -> COLOR = DFF7FF
            //ELSE (WARNING) --> COLOR = FFFFDF
            $background_color = ($log_status == "INFO") ? "#DFF7FF" : "#FFFFDF" ;
    
            $_res = array();
            
            $_res[] = $log_date;
            $_res[] = $log_sources[$selected_log];
            $_res[] = $log_status;
            $_res[] = $log_msg;
            
            $_res['dtRowData']['background_color'] = $background_color;
            
            $data[] = $_res;
                            
            // Increase pointer
            if ($selected_log == 'api')
            {
                $i_api++;
            }
            else
            {
                $i_frm++;
            }
        }
    }
}

$total = count($data);

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total_display;
$response['aaData']               = $data;

echo json_encode($response);



/* End of file dt_logs.php */
/* Location: /av_backup/providers/dt_logs.php */
