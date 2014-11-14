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
$ssid   = base64_decode(GET('ssid'));
$sensor = GET('sensor');
$mac    = GET('mac');

ossim_valid($ssid, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC_EXT, '\<\>', 'illegal: ssid');
ossim_valid($sensor, OSS_IP_ADDR,                                         'illegal: sensor');
ossim_valid($mac, OSS_MAC, OSS_NULLABLE,                                  'illegal: mac');

if (ossim_error()) 
{
    die(ossim_error());
}


$db    = new ossim_db();
$conn  = $db->snort_connect();
$ossim = $db->connect();

if (!validate_sensor_perms($ossim,$sensor,", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1")) 
{
    echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
        
    $db->close();
    exit();
}

if ($mac != '' && GET('action') == "delete") 
{
	Wireless::del_ap($ossim,$mac,$ssid,$sensor);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

<table class='table_list' id='w_form'>
	<th><?php echo _("MAC")?></th>
	<th><?php echo _("Type")?></th>
	<th><?php echo _("# Clients")?></th>
	<th><?php echo _("Channel")?></th>
	<th><?php echo _("Speed")?></th>
	<th><?php echo _("Cloaked")?></th>
	<th><?php echo _("Encryption")?></th>
	<th><?php echo _("1st Seen")?></th>
	<th><?php echo _("Last Seen")?></th>
	<th><?php echo _("Sensor")?></th>
	<th></th>
	<?php
	
	$aps = Wireless::get_wireless_aps($conn,$ssid,$sensor);
	$i   = 0;
	
	foreach ($aps as $data) 
	{
		$color = ($i++ % 2 == 0) ? "class='odd'" : "class='even'";
		
		if (preg_match("/laptop/",$data['nettype'])) 
		{
			$color .= " bgcolor='#D4D1EF'"; // other color for 'probe'
		}
		
		$enc = ($data['encryption'] == 'None') ? 'None' : str_replace("None", "<span style='color:red'>None</span>",$data['encryption']);
		
		echo "<tr $color>
			<td>".$data['mac']."<br><span style='font-size:9px'>".$data['vendor']."</span></td>
			<td>".$data['nettype']."</td>
			<td>".$data['clients']."</td>
			<td>".$data['channel']."</td>
			<td>".$data['maxrate']." Mbps</td>
			<td>".$data['cloaked']."</td>
			<td>$enc</td>
			<td><font color='".Wireless::date_color($data['firsttime'],1)."'>".$data['firsttime']."</font></td>
			<td><font color='".Wireless::date_color($data['lasttime'],2)."'>".$data['lasttime']."</font></td>
			<td>$sensor</td>
			<td width='20' nowrap='nowrap'>
				<a href='ap_edit.php?ssid=".urlencode(base64_encode($ssid))."&mac=".urlencode($data['mac'])."&sensor=".urlencode($sensor)."'><img src='../pixmaps/pencil.png' border='0'/></a>
				<a href='ap.php?action=delete&ssid=".urlencode(base64_encode($ssid))."&mac=".urlencode($data['mac'])."&sensor=".urlencode($sensor)."'><img src='../vulnmeter/images/delete.gif' border='0'/></a>
			</td>
		</tr>";
		
		if ($data['notes'] != '')
		{
			echo "<tr $color>
			   <td colspan='11' style='text-align:left;padding:0px 10px 10px 0px'>
			       <img src='../pixmaps/theme/arrow-315-small.png' border='0' align='absmiddle'/><b>Notes:</b> ".utf8_encode(nl2br($data['notes']))."
			   </td>
			</tr>";
		}
	}
	?>
</table>
<br>
</body>
</html>
<?php $db->close(); ?>
