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


header("Expires: Mon, 20 Mar 1998 12:01:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", FALSE);
header("Pragma: no-cache");


require_once 'av_init.php';
require_once 'riskmaps_functions.php';

Session::logcheck('dashboard-menu', 'BusinessProcesses');


$map       = $_GET['map'];
$edit_mode = ($_GET['edit_mode'] == '1') ? TRUE : FALSE;

ossim_valid($map, OSS_HEX, 'illegal:'._('Map'));

if (ossim_error())
{
    $data = array(
        'status' => 'error',
        'data'   => ossim_get_error_clean(),
    );

    echo json_encode($data);
    exit();
}

$db   = new Ossim_db();
$conn = $db->connect();


$indicators = array();
$ri_data = get_indicators_from_map($conn, $map);

foreach ($ri_data as $ri_id => $indicator)
{
    $indicators[$ri_id] = $indicator;
    $indicators[$ri_id]['html'] = draw_indicator($conn, $indicator, $edit_mode);
}

$db->close();

$data = array(
    'status' => 'success',
    'data'   => $indicators,
);


echo json_encode($data);
