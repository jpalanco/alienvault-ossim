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


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once 'av_init.php';

$m_perms  = array ('analysis-menu', 'analysis-menu');
$sm_perms = array ('EventsForensics', 'ControlPanelAlarms');

Session::logcheck($m_perms, $sm_perms);

list($ip, $ctx) = explode('-', GET('ip'));

ossim_valid($ip, OSS_IP_ADDR_0,          'illegal:' . _('Ip'));
ossim_valid($ctx, OSS_HEX, OSS_NULLABLE, 'illegal:' . _('Ctx')); // Maybe nullable from Logger resolves

if (ossim_error()) 
{
    die(ossim_error());
}

$db      = new ossim_db();
$conn    = $db->connect();

$net = array_shift(Asset_host::get_closest_net($conn, $ip, $ctx));

if (is_array($net)) 
{
	if ($net['icon'] != '') 
	{
	   echo "<img src='data:image/png;base64,".base64_encode($net['icon'])."' border='0'/> ";
	}
	
	echo '<strong>'.$net['name'].'</strong> ('.$net['ips'].')';
}
else 
{
	echo "<b>$ip</b> "._('not found in home networks');
}

$db->close();