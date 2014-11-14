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
Session::logcheck("environment-menu", "ReportsWireless");

require_once 'Wireless.inc';

$sensor = GET('sensor');
$file   = str_replace("../","",GET('file'));

ossim_valid($sensor, OSS_IP_ADDR, 'illegal: sensor');
ossim_valid($file, OSS_TEXT,      'illegal: file');

if (ossim_error()) {
    die(ossim_error());
}
# sensor list with perms
require_once 'ossim_db.inc';
$db   = new ossim_db();
$conn = $db->connect();

if ( !validate_sensor_perms($conn,$sensor,", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1") ) 
{
     echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
        
     $db->close();
     exit();
}

$db->close();
#
$path = "/var/ossim/kismet/parsed/$sensor/$file";

if (file_exists($path))
{
    header('Content-Type: application/xml');
    echo file_get_contents($path);
}
?>
