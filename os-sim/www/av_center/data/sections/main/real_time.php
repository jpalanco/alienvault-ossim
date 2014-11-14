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


if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/sections/main/real_time.php')
{
    exit();
}


$system_id = POST('system_id');

ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));

if (ossim_error())
{
    $data['status'] = 'error';

    $data['data'] = array(
        'status'    => NULL,
        'ha_status' => NULL,
        'mem_used'  => '0.00',
        'swap_used' => '0.00',
        'cpu_load'  => '0.00',
        'update'    => array(
            'any_pending'     => NULL,
            'release_type'    => NULL,
            'release_version' => NULL
        )
    );

    echo json_encode($data);
    exit();
}

try
{
    $data['status'] = 'success';
    $s_data = Av_center::get_system_status($system_id, 'general', TRUE);

    //Check if there are packages to install
    $is_updated = Av_center::is_system_updated($system_id);

    $release_type    = NULL;
    $release_version = NULL;

    if ($is_updated == FALSE)
    {
        $release_info = Av_center::get_release_info($system_id);
        $release_type = $release_info['type'];
        $release_type = $release_info['version'];
    }

    $data['data']= array(
        'status'     => 'up',
        'ha_status'  => $s_data['ha_status'],
        'mem_used'   => $s_data['memory']['ram']['percent_used'],
        'swap_used'  => $s_data['memory']['swap']['percent_used'],
        'cpu_load'   => $s_data['cpu']['load_average'],
        'update'     => array(
            'any_pending'     => $is_updated,
            'release_type'    => $release_type,
            'release_version' => $release_version
        )
    );
}
catch(Exception $e)
{
     $data['status'] = 'error';
     $data['data']   = array(
        'status'    => 'down',
        'ha_status' => NULL,
        'mem_used'  => '0.00',
        'swap_used' => '0.00',
        'cpu_load'  => '0.00',
        'update'    => array(
            'any_pending'     => NULL,
            'release_type'    => NULL,
            'release_version' => NULL
        )
     );
}

echo json_encode($data);