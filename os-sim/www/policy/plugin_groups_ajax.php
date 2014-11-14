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


if (!Session::logcheck_bool("configuration-menu", "PluginGroups"))
{
    $response['error']  = true ;
    $response['output'] = _('You do not have permissions to see this section');
    
    echo json_encode($response);
    exit -1;
}


function modify_plugingroup_plugin($conn, $data)
{ 
    $plugin_group = $data['plugin_group'];
    $plugin_id    = $data['plugin_id'];
    $sids_str     = $data['plugin_sids'];

    ossim_valid($plugin_id,         OSS_DIGIT,      'illegal:' . _("Plugin ID"));
    ossim_valid($plugin_group,      OSS_HEX,        'illegal:' . _("Plugin GroupID"));

    if (ossim_error())
    {
        $info_error = "Error: ".ossim_get_error();
        ossim_clean_error();
        
        $return['error'] = true ;
        $return['msg']   = $info_error;
        
        return $return;
    }
    
    $total_sel = 1;
    
    if (is_array($sids_str))
    {
        $total_sel = count($sids_str);
        $sids_str  = implode(',', $sids_str); 
    }

    if ($sids_str !== '') 
    {
        list($valid, $data) = Plugin_sid::validate_sids_str($sids_str);
        
        if (!$valid) 
        {
            $return['error'] = true ;
            $return['msg']   = _("Error for data source ") . $plugin_id . ': ' . $data;
            return $return;

        }

        if ($sids_str == "ANY") 
        {
            $sids_str = "0";
        }
        else
        {
            $total    = Plugin_sid::get_sidscount_by_id($conn,$plugin_id);
            $sids_str = ($total_sel == $total) ? "0" : $sids_str;
        }        

        Plugin_group::edit_plugin($conn, $plugin_group, $plugin_id, $sids_str);
    }

    $return['error']  = false ;
    $return['output'] = '';
    return $return;  
} 


$db     = new ossim_db();
$conn   = $db->connect();

$action = POST("action");
$data   = POST("data");

ossim_valid($action,    OSS_DIGIT,  'illegal:' . _("Action"));

if (ossim_error()) 
{
    die(ossim_error());
}

if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){

    switch($action)
    {
        case 1:         
            $response = modify_plugingroup_plugin($conn, $data);            
            break;            
                                    
        default:
            $response['error'] = true ;
            $response['msg']   = 'Wrong Option Chosen';
    }
    
    echo json_encode($response);

    $db->close();
}
