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

$order   = GET('order');
$si      = intval(GET('index'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
$ssid    = base64_decode(GET('ssid'));
$aps     = GET('aps');

if ($aps == '' && $_SESSION["clients"][$ssid] != '') 
{
    $aps = $_SESSION["clients"][$ssid]; 
}

$mac    = GET('mac');
$sensor = GET('sensor');

ossim_valid($order, OSS_ALPHA, OSS_NULLABLE,                      'illegal: order');
ossim_valid($ssid, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC_EXT, '\<\>', 'illegal: ssid');
ossim_valid($aps, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, OSS_PUNC,   'illegal: aps');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC,                        'illegal: sensors');
ossim_valid($sensor, OSS_IP_ADDR, OSS_NULLABLE,                   'illegal: sensor');
ossim_valid($mac, OSS_MAC, OSS_NULLABLE,                          'illegal: mac');

if (ossim_error()) 
{
    die(ossim_error());
}

$db    = new ossim_db();
$ossim = $db->connect();
$conn  = $db->snort_connect();

if ($mac != '' && $sensor!= '' && $ssid != '' && GET('action') == 'delete') 
{
    if (!validate_sensor_perms($ossim,$sensor,", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1")) 
    {
        echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
        
        $db->close();
        exit();
    }
	
	Wireless::del_clients($ossim,$mac,$sensor,$ssid);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>
    <?php require_once "../host_report_menu.php" ?>
    <script type="text/javascript">
        function postload() 
        {
        	$('.tiptip').tipTip({content: $(this).attr('data-title')});
        }    
    </script>
</head>
<body>
<table class='table_data' id="w_form">
	<thead>
		<th><?php echo _("Client Name")?></th>
		<th><?php echo _("MAC")?></th>
		<th><a href="clients_gb.php?sensors=<?php echo urlencode($sensors)?>&order=ip"><?php echo _("IP Addr")?></a></th>
		<th><?php echo _("Type")?></th>
		<th><?php echo _("Encryption")?></th>
		<th><?php echo _("WEP")?></th>
		<th><?php echo _("1st Seen")?></th>
		<th><?php echo _("Last Seen")?></th>
		<th><?php echo _("Connected To")?></th>
		<th>&nbsp;</th>
	</thead>
	
	<tbody>
		<?php		
		$plugin_sids = Wireless::get_plugin_sids($ossim);
		$clients     = Wireless::get_wireless_clients($ossim,$order,$sensors,$aps);
		$c = 0;
		
		
		if (is_array($clients) && !empty($clients))
		{
    		foreach ($clients as $data) 
    		{			
    			$sids  = array();
    			
    			foreach ($data['sids'] as $sid) if ($sid!=0 && $sid!=3 && $sid!=19) 
    			{
    				$color  = "bgcolor='#FFCA9F'";
    				$plg    = ($plugin_sids[$sid]!="") ? $plugin_sids[$sid] : $sid;
    				$sids[] = $plg;
    			}
    			
    			$sidsstr = implode("<br>",$sids);
    			
    			$connected = "";
    			$rest      = "<b>APs</b><br>";
    			
    			if (count($data['connected'])>3) 
    			{
    				$i=0; 
    				$max = 3;
    				foreach ($data['connected'] as $mac) if (trim($mac)!="") 
    				{
    					if ($i++ < $max) 
    					{
    				        $connected .= trim($mac)."<br>";
    					}
    					else 
    					{
    					    $rest .= trim($mac)."<br>";
    					} 
    				}
    				
    				if (trim($sidsstr) != '') 
    				{
    				    $rest .= "<b>Attacks</b><br>".trim($sidsstr);
    				}
    				
    				$connected .= "<a href='javascript:;' class='scriptinfo' txt='$rest'>[".($i-$max)." more]</a>";
    			} 
    			else 
    			{
    				$connected = implode("<br>",$data['connected']);
    			}
    			
    			echo "<tr $color>
    				<td>".$data['name']."</td>
    				<td>".$data['mac']."<br><font style='font-size:10px'>".$data['vendor']."</font></td>
    				<td><a target='main' class='HostReportMenu' id='".$data['ip'].";".$data['ip'].";".$data['id']."' href='".Menu::get_menu_url("../asset_details/index.php?id=".$data['id'], 'environment', 'assets', 'assets')."'>".$data['ip']."</a></td>
    				<td>".$data['type']."</td>
    				<td>".$data['encryption']."</td>
    				<td>".$data['encoding']."</td>
    				<td><font color='".Wireless::date_color($data['firsttime'],1)."'>".$data['firsttime']."</font></td>
    				<td><font color='".Wireless::date_color($data['lasttime'],2)."'>".$data['lasttime']."</font></td>
    				<td style='padding:0px 5px 0px 5px;text-align:left' nowrap='nowrap'>$connected</td>
    				<td><a href='?action=delete&ssid=".urlencode(base64_encode($ssid))."&mac=".urlencode($data['mac'])."&sensor=".urlencode($data['sensor'])."'><img src='../vulnmeter/images/delete.gif' border='0'/></a></td>
    			</tr>";
    		}
		}
		else
		{
    		?>
    		<tr><td colspan="10"><?php echo _("No clients found")?></td></tr>
    		<?php
		}				
		?>
	</tbody>
</table>

<?php $db->close(); ?>
<br>
</body>
</html>