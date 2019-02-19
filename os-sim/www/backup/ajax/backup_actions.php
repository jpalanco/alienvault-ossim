<?php
/**
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
*/

require_once 'av_init.php';
Session::logcheck("configuration-menu", "ToolsBackup");

$response['status']   = 'success';
$response['message']  = '';
$response['progress'] = '';

$action     = GET("action");
$dates_list = (GET("dates_list") != '') ? explode(',', GET("dates_list")) : array();
$nomerge    = (GET("nomerge") != "") ? GET("nomerge") : "merge"; // $_GET['merge'] is empty, always merge by default
$filter_by  = GET('filter_by');

// Disable filter (Entity/User) to prevent framework error
// Note: remove filter_by parameter when the select boxes are removed from UI 
$filter_by = '';

ossim_valid($action, "insert", "delete", "status",         'illegal:' . _("Action"));
ossim_valid($nomerge, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("nomerge"));
ossim_valid($filter_by, OSS_NULLABLE, OSS_DIGIT, OSS_USER, 'illegal:' . _("filter_by"));

foreach ($dates_list as $_date)
{
	ossim_valid($_date, OSS_DIGIT, 'illegal:' . _("Date"));
}

if (ossim_error())
{
    $response['status']  = 'error';
    $response['message'] = ossim_get_error();
}
else
{
    switch($action)
    {
        // Restore button
        case 'insert':
            
            if (Token::verify('tk_insert_events', GET('token')) == FALSE)
            {
                $response['status']  = 'error';
                $response['message'] = Token::create_error_message();
            }
            elseif (count($dates_list) > 0)
            {
                $launch_status = Backup::Insert($dates_list, $filter_by, $nomerge);
                
                if ($launch_status > 0)
                {
                    $response['status']  = 'success';
                    $response['message'] = _('The backup process is inserting events...');
                }
                else
                {
                    $response['status']  = 'error';
                    $response['message'] = _('Sorry, operation was not completed due to an error when restoring events');
                }
            }
            else
            {
                $response['status']  = 'error';
                $response['message'] = _('Please, select the dates you want to restore');
            }
            
            break;
        
        // Purge button
        case 'delete':
            
            if (Token::verify('tk_delete_events', GET('token')) == FALSE)
            {
                $response['status']  = 'error';
                $response['message'] = Token::create_error_message();
            }
            elseif (count($dates_list) > 0)
            {
                $launch_status = Backup::Delete($dates_list);
                
                if ($launch_status > 0)
                {
                    $response['status']  = 'success';
                    $response['message'] = _('The backup process is purging events...');
                }
                else 
                {
                    $response['status']  = 'error';
                    $response['message'] = _('Sorry, operation was not completed due to an error when purging events');
                }
            }
            else
            {
                $response['status']  = 'error';
                $response['message'] = _('Please, select the dates you want to purge');
            }
            
            break;
        
        // Ajax status interval check
        case 'status':
            
            $db   = new ossim_db();
            $conn = $db->connect();
            
            list($is_running, $mode, $progress) = Backup::is_running($conn);
            
            $db->close();
            
            if ($is_running > 0)
            {
                $response['status']   = 'success';
                $response['message']  = ($mode == 'insert') ? _('The backup process is inserting events...') : _('The backup process is purging events...');
                $response['progress'] = Util::number_format_locale($progress);
            }
            elseif ($is_running < 0)
            {
                $response['message']  = _('Bad response from frameworkd. Please, check the logs for more info');
                $response['status']   = 'error';
            }
            
            break;
    }
}

echo json_encode($response);
