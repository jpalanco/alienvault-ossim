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

Session::useractive();

$sess_id  = Util::get_sess_cookie();
$conn_id  = intval($_SESSION[$sess_id]['_connection_id']);
$last_id  = intval($_SESSION[$sess_id]['_last_checked_id']);
$force    = intval($_GET['force_kill']);

$response = array('id' => $conn_id, 'status' => '', 'sess' => $sess_id);

if ($conn_id > 0 || $last_id > 0)
{
    $db   = new ossim_db();
    $conn = $db->connect();
    
    if ($last_id > 0 && $conn_id != $last_id)
    {
        $response['kill'] = $last_id;

        $conn->Execute('KILL ?', array($last_id));
    }
    
    $_SESSION[$sess_id]['_last_checked_id'] = $conn_id;
    $params                                 = array($conn_id);
    
    if ($force)
    {
        $response['kill'] = $conn_id;

        $conn->Execute('KILL ?', $params);
    }
    else
    {
        $query = 'SELECT state FROM INFORMATION_SCHEMA.PROCESSLIST WHERE id=?';        
        $rs    = $conn->Execute($query, $params);
        
        if ($rs && !$rs->EOF) 
        {
            $response['status'] = ($rs->fields['state']) ? $rs->fields['state'] : "Sending data";
        }
    }
    
    @$db->close();
}

echo json_encode($response);
