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


require_once dirname(__FILE__).'/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$file       = POST('file');
$sensor_id  = POST('sensor_id');


ossim_valid($sensor_id, OSS_HEX,                   'illegal:' . _('Sensor ID'));
ossim_valid($file, OSS_ALPHA, OSS_SCORE, OSS_DOT,  'illegal:' . _('File'));


if (!ossim_error())
{
    $db    = new ossim_db();
    $conn  = $db->connect();

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        ossim_set_error(_('Error! Sensor not allowed'));
    }

    $db->close();
}


if (ossim_error())
{
	$data['status'] = 'error';
	$data['data']   = _('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".ossim_get_error_clean().'</div>';

	echo json_encode($data);
	exit();
}


//Rule file

$_SESSION['_current_file']   = $file;

try
{
    $rule_data = Ossec::get_rule_file($sensor_id, $file);

    $data['status'] = 'success';
    $data['data']   = $rule_data['data'];
}
catch(Exception $e)
{
    $data['status'] = 'error';
    $data['data']   = $e->getMessage();
}

echo json_encode($data);
?>
