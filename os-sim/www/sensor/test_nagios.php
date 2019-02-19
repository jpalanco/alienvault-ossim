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

Session::logcheck('configuration-menu', 'PolicySensors');

$sensor_id   = POST('sensor_id');
$nagios_user = POST('user');
$nagios_pass = POST('pass');

ossim_valid($sensor_id,  OSS_HEX,                                       'illegal:' . _('Sensor ID'));
ossim_valid($nagios_user, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _('Availability Monitoring User'));
ossim_valid($nagios_pass, OSS_PASSWORD, OSS_NULLABLE,                   'illegal:' . _('Availability Monitoring Password'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();

$sensor = Av_sensor::get_object($conn, $sensor_id);

if (Util::is_fake_pass($nagios_pass)) 
{
	$nagios_options = $sensor->get_nagios_credentials($conn);	
	$nagios_pass    = $nagios_options['password'];
}

try
{
    $url = $sensor->get_nagios_url($nagios_user, $nagios_pass);
    
    if ($url != '') 
    {   
        echo "<img src='../pixmaps/tick.png'/>";
    }
}
catch(Exception $e)
{    
    echo preg_replace("/\:.*/", '', $e->getMessage());
}

$db->close();
?>
