<?php

/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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
require_once(dirname(__FILE__).'/../../../../config.inc');

session_write_close();

$system_id = POST('system_id');
$action    = POST('action');

ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:'._('System ID'));
ossim_valid($action,    OSS_LETTER,            '_', 'illegal:'._('Action'));

if (ossim_error())
{
    $data['status'] = 'error';
    $data['data']   = ossim_get_error();

    echo json_encode($data);
    exit();
}


if ($action == 'check_cidr')
{
    $cidr = POST('cidr');
    ossim_valid($cidr, OSS_IP_CIDR, 'illegal:'._('CIDR'));

    if (ossim_error())
    {
        $res['status'] = 'error';
        $res['data']   = ossim_get_error();
    }
    else
    {
        $res['status'] = 'success';
        $res['data']   = md5($cidr);
    }

    echo json_encode($res);
    exit();
}
elseif ($action == 'check_server')
{
    $new_server = POST('new_server');
    $old_server = POST('old_server');
    $priority   = POST('priority');

    ossim_valid($new_server, OSS_IP_ADDR, 'illegal:'._('IP Address'));
    ossim_valid($priority, '0,1,2,3,4,5', 'illegal:'._('Priority'));

    if (!empty($old_server))
    {
        ossim_valid($old_server, OSS_IP_ADDR, 'illegal:'._('IP Address'));
    }

    if (ossim_error())
    {
        $res['status'] = 'error';
        $res['data']   = ossim_get_error();

        echo json_encode($res);
        exit();
    }

    session_start();
    $cnf_data  = $_SESSION['sensor_cnf'];
    $server_ip = $cnf_data['server_ip']['value'];
    session_write_close();

    //Update master server
    if (!empty($old_server) && $old_server == $server_ip)
    {
        $res['status']              = 'success';
        $res['data']['id']          = md5($new_server);
        $res['data']['server_type'] = _('Server, Inventory');
        $res['data']['is_master']   = TRUE;
    }
    else
    {
        $res['status']              = 'success';
        $res['data']['id']          = md5($new_server);
        $res['data']['server_type'] = _('Server');
        $res['data']['is_master']   = FALSE;
    }

    session_write_close();

    echo json_encode($res);
    exit();
}
elseif ($action == 'detectors')
{
    try
    {
        $db         = new ossim_db();
        $conn       = $db->connect();
        $sensor_ids = Av_center::get_component_id_by_system($conn, $system_id);
        $db->close();

        $res['status'] = 'success';
        $res['data']   = Av_center::get_detectors_status($sensor_ids['canonical']);
    }
    catch (\Exception $e)
    {
        $res['status'] = 'error';
        $res['data']   = $e->getMessage();
    }

    echo json_encode($res);
    exit();
}
