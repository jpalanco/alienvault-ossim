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
require_once (dirname(__FILE__) . '/../../../config.inc');
session_write_close();

$system_id  = POST('system_id');
$confirm    = intval(POST('confirm'));

ossim_valid($system_id,  OSS_UUID, 'illegal:' . _('System ID'));

if (ossim_error())
{
    $data['status'] = 'error';
    $data['data']   = ossim_get_error();
}
else
{ 
    //Getting system status
    
    $local_id = strtolower(Util::get_system_uuid());

    try
    {    
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $ha_enabled = Av_center::is_ha_enabled($conn, $system_id);
               
        $db->close();
        
    }
    catch(Exception $e)
    {        
        $db->close();
        
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
        
        echo json_encode($data);
    }
        
    $can_be_removed = ($system_id != $local_id && $ha_enabled == FALSE) ? TRUE : FALSE;
    
    if ($can_be_removed == FALSE)
    {
        $data['status'] = 'error';
        
        if ($system_id == $local_id)
        {
            $data['data'] = _('You are not allowed to delete the local system');
        }
        else
        {
            $data['data'] = _('The remove request cannot be processed because you have HA enabled and running');
        }
    }
    else
    {
        $data         = array();
        $force_delete = TRUE;
        
        //If we do not confirm the delete, then we check if the system is down
        if (!$confirm)
        {
            $reachable = Av_center::is_system_reachable($system_id);
            
            //If the system is down then we'll ask the user for extra confirmation
            if (!$reachable)
            {
                $force_delete = FALSE;
            }
        }
        
        //Delete the system
        if ($force_delete)
        {
            try
            {
                $res = Av_center::delete_system($system_id);
        
                // Refresh
                Av_component::report_changes('sensors');
                Av_component::report_changes('servers');
        
                $data['status'] = 'success';
                $data['data']   = $res;
            }
            catch(Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = $e->getMessage();
            }
        }        
        else
        {
            //Ask for confirmation

            $data['status'] = 'confirm';
            $data['data']   = '';
        }
    }
}

echo json_encode($data);
