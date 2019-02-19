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

//Config File
require_once 'av_init.php';

Session::logcheck_ajax('environment-menu', 'PolicyHosts');

$asset_type = POST('asset_type');
$token      = POST('token');
$action     = POST('action');

// Validate Form token
if (Token::verify('tk_toggle_monitoring', POST('token')) == FALSE)
{		
	$error = Token::create_error_message();
	Util::response_bad_request($error);
}

session_write_close();

ossim_valid($asset_type,  OSS_LETTER,               'illegal: '._('Asset Type'));
ossim_valid($action,      'enable', 'disable',      'illegal: '._('Action'));

if (ossim_error()) 
{
    $error = ossim_get_error();
    Util::response_bad_request($error);
}

/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

$data = array();

try
{
    $_class_name = ($asset_type == 'group') ? 'Asset_group_scan' : 'Asset_host_scan';
    
    if ($action == 'enable')
    {
        $success = $_class_name::bulk_enable_monitoring($conn);
    }
    else
    {   
        $success = $_class_name::bulk_disable_monitoring($conn);
    }
    
    if ($success == FALSE)
    {
        
        if ($action == 'enable')
        {
            $group_message = _('None of the selected groups have been updated. Probably the groups you selected are already being monitored');
            $asset_message = _('None of the selected assets have been updated. Probably the assets you selected are already being monitored');
        }
        else
        {
            $group_message = _('None of the selected groups have been updated. Probably the groups you selected aren\'t already being monitored');
            $asset_message = _('None of the selected assets have been updated. Probably the assets you selected aren\'t already being monitored');   
        }
        
        
        $data['status'] = 'warning';
        $data['data']   = ($asset_type == 'group') ? $group_message : $asset_message;
    }
    else
    {
        $data['status'] = 'OK';
        
        
        if ($action == 'enable')
        {
            $group_message = _('Availability monitoring enabled successfully on the selected groups');
            $asset_message = _('Availability monitoring enabled successfully on the selected assets');
        }
        else
        {
            $group_message = _('Availability monitoring disabled successfully on the selected groups');
            $asset_message = _('Availability monitoring disabled successfully on the selected assets');   
        }
        
        $data['data']   = ($asset_type == 'group') ? $group_message : $asset_message;
    }
}
catch(Exception $e)
{
    $error = $e->getMessage();
    Util::response_bad_request($error);
}


$db->close();

echo json_encode($data);

