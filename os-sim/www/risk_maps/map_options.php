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
require_once 'riskmaps_functions.php';

Session::logcheck('dashboard-menu', 'BusinessProcessesEdit');


/****************************************************************************************************************
 *
 ************************************************* MAPS FUNTIONS ************************************************
 *
 ****************************************************************************************************************/


function change_map_title($conn, $data)
{
    $id    = $data['id'];
    $title = $data['title'];

    if (mb_detect_encoding($data['title']." ", 'UTF-8,ISO-8859-1') == 'UTF-8')
    {
        $title = mb_convert_encoding($data['title'], 'ISO-8859-1', 'UTF-8');
    }

    ossim_valid($title, OSS_INPUT,    'illegal:' . _('Title'));
    ossim_valid($id,  OSS_HEX,        'illegal:' . _('Map'));

    if (ossim_error())
    {
        $info_error = "Error: ".ossim_get_error();
        ossim_clean_error();

        $return['error'] = TRUE;
        $return['msg']   = $info_error;

        return $return;
    }

    if (!is_map_editable($conn, $id))
    {
        $return['error'] = TRUE;
        $return['msg']   = _("You do not have permission to edit this map");

        return $return;
    }

    $query = "UPDATE risk_maps SET name = ? WHERE map = UNHEX(?)";
    $params = array($title, $id);

    if ($conn->Execute($query, $params) === FALSE)
    {
        $return['error'] = TRUE;
        $return['msg']   = $conn->ErrorMsg() . '.';

        return $return;
    }

    $return['error'] = FALSE;
    $return['msg']   = _('Map name changed');

    return $return;
}


function delete_map($conn, $id)
{
    ossim_valid($id, OSS_HEX, 'illegal:' . _('Map'));

    if (ossim_error())
    {
        $info_error = "Error: ".ossim_get_error();
        ossim_clean_error();

        $return['error'] = TRUE;
        $return['msg']   = $info_error;

        return $return;
    }

    if (!is_map_editable($conn,$id))
    {
        $return['error'] = TRUE;
        $return['msg']   = _("You do not have permission to edit this map");

        return $return;
    }

    $map_name = "map".$id.".jpg";

    if (getimagesize("maps/$map_name"))
    {
        unlink("maps/$map_name");

        //Deleting the indicators that appear in the map to be deleted.
        $query  = "DELETE FROM risk_indicators WHERE map=unhex(?)";
        $params = array($id);
        $result = $conn->Execute($query, $params);
        
        //Deleting the indicator that are linking to the map to be deleted.
        $query = "DELETE FROM risk_indicators WHERE url='view.php?map=$id'";
        $result = $conn->Execute($query);

        //Deleting the map.
        $query = "DELETE FROM risk_maps WHERE map=unhex(?)";
        $params = array($id);
        $result = $conn->Execute($query, $params);

        $config      = new User_config($conn);
        $login       = Session::get_session_user();
        $default_map = $config->get($login, "riskmap", 'simple', 'main');

        if (strcasecmp($default_map, $id) == 0)
        {
            $map = get_map($conn, '00000000000000000000000000000001');

            if (!empty($map))
            {
                set_default_map($conn, $map);
            }
        }

        $return['error'] = FALSE;
        $return['msg']   = _("Map deleted successfully");

        return $return;
    }

    $return['error'] = TRUE;
    $return['msg']   = _("The map couldn't be deleted");

    return $return;
}


function set_default_map($conn, $id)
{
    ossim_valid($id, OSS_HEX, 'illegal:' . _('Map'));

    if (ossim_error())
    {
        $info_error = "Error: ".ossim_get_error();
        ossim_clean_error();
        $return['error'] = TRUE;
        $return['msg']   = $info_error;

        return $return;
    }

    if (!is_map_editable($conn,$id))
    {
        $return['error'] = TRUE;
        $return['msg']   = _("You do not have permission to edit this map");
        
        return $return;
    }

    $login  = Session::get_session_user();
    $config = new User_config($conn);

    $config->set($login, "riskmap", $id, 'simple', "main");

    $return['error'] = FALSE;
    $return['msg']   = _("Default map changed successfully");

    return $return;
}


$login  = Session::get_session_user();
$db     = new ossim_db();
$conn   = $db->connect();

$action = POST("action");
$data   = POST("data");

ossim_valid($action,    OSS_DIGIT,  'illegal:' . _('Action'));

if (ossim_error())
{
    die(ossim_error());
}

if ($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    switch($action)
    {
        case 1:
            $response = delete_map($conn, $data);
        break;

        case 2:
            $response = set_default_map($conn, $data);
        break;

        case 3:
            $response = change_map_title($conn, $data);
        break;

        default:
            $response['error'] = TRUE ;
            $response['msg']   = _('Wrong Option Chosen');
    }

    echo json_encode($response);

    $db->close();
}