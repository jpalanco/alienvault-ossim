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

$order = GET('order');
$si = intval(GET('index'));
$sensors  = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
$aps      = GET('aps');
$mac      = GET('mac');
$sensor   = GET('sensor');

$hideold  = intval(GET('hideold'));
$trusted  = intval(GET('trusted'));
$knownmac = intval(GET('knownmac'));

ossim_valid($order, OSS_ALPHA, OSS_NULLABLE,                    'illegal: order');
ossim_valid($aps, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, OSS_PUNC, 'illegal: aps');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC,                      'illegal: sensors');
ossim_valid($sensor, OSS_IP_ADDR, OSS_NULLABLE,                 'illegal: sensor');
ossim_valid($mac, OSS_MAC, OSS_NULLABLE,                        'illegal: mac');

if (ossim_error()) 
{
    die(ossim_error());
}

$db    = new ossim_db();
$ossim = $db->connect();
$conn  = $db->snort_connect();

if ($mac != '' && $sensor != '' && GET('action') == "delete") 
{
    if (!validate_sensor_perms($ossim,$sensor,", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1")) 
    {
        echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
        
        $db->close();
        exit();
    }
    
	Wireless::del_clients($ossim,$mac,$sensor);
}

if ($trusted > 0)                         
{
    $_SESSION["trusted"] = $trusted;
}

if (!isset($_SESSION["trusted"]))       
{
    $_SESSION["trusted"] = 1;
}

if ($hideold > 0)                         
{
    $_SESSION["hideold"] = $hideold;
}

if (!isset($_SESSION["hideold"]))       
{
    $_SESSION["hideold"] = 2;
}

if ($knownmac > 0)         
{               
    $_SESSION["knownmac"] = $knownmac;
}

if (!isset($_SESSION["knownmac"]))
{  
    $_SESSION["knownmac"] = 2;
}
?>

<form style="margin-bottom:4px">
	<input type="hidden" name="si" value="<?php echo $si?>"/>
	<?php echo ("Show All")?>  <input type="radio" name="trusted" onclick="changeviewc(this.form.si.value,'trusted='+this.value)" value="1" <?php echo ($_SESSION["trusted"]==1) ? "checked" : ""?>/>
	<?php echo ("Trusted")?>   <input type="radio" name="trusted" onclick="changeviewc(this.form.si.value,'trusted='+this.value)" value="2" <?php echo ($_SESSION["trusted"]==2) ? "checked" : ""?>/>
	<?php echo ("Untrusted")?> <input type="radio" name="trusted" onclick="changeviewc(this.form.si.value,'trusted='+this.value)" value="3" <?php echo ($_SESSION["trusted"]==3) ? "checked" : ""?>/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo ("Hide old ones")?> <input type="checkbox" onclick="changeviewc(this.form.si.value,'hideold='+(this.checked ? '1' : '2'))" name="hideold" <?php echo ($_SESSION["hideold"]==1) ? "checked" : ""?>/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo ("Known mac vendors")?> <input type="checkbox" onclick="changeviewc(this.form.si.value,'knownmac='+(this.checked ? '1' : '2'))" name="knownmac" <?php echo ($_SESSION["knownmac"]==1) ? "checked" : ""?>>
</form>

<table class='table_data' id="results">
	<thead>
	    <tr>
    		<th><?php echo ("Client Name")?></th>
    		<th><?php echo ("MAC")?></th>
    		<th><?php echo ("IP Addr")?></th>
    		<th><?php echo ("Type")?></th>
    		<th><?php echo ("Encryption")?></th>
    		<th><?php echo ("WEP")?></th>
    		<th><?php echo ("1st Seen")?></th>
    		<th><?php echo ("Last Seen")?></th>
    		<th><?php echo ("Connected To")?></th>
    		<th>&nbsp;</th>
	    </tr>
	</thead>
	
	<tbody>
	<?php
	
	$plugin_sids = Wireless::get_plugin_sids($ossim);
	$clients = Wireless::get_wireless_clients($ossim,$order,$sensors,$aps);
	$c = 0;
	
	if (is_array($clients) && !empty($clients))
	{		
    	foreach ($clients as $data) 
    	{		
    		$sids = array();
    		
    		foreach ($data['sids'] as $sid) 
    		{
        		if ($sid!=0 && $sid!=3 && $sid!=19) 
        		{
        			$color = "bgcolor='#FFCA9F'";
        			$plg = ($plugin_sids[$sid]!="") ? $plugin_sids[$sid] : $sid;
        			$sids[] = $plg;
        		}
    		}
    		
    		$sidsstr = implode("<br>",$sids);
    		//
    		$connected = "";
    		$rest = "<b>APs</b><br>";
    		if (count($data['connected'])>3) 
    		{
    			$i = 0; 
    			$max = 3;
    			foreach ($data['connected'] as $mac) 
    			{
        			if (trim($mac)!="") 
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
    			}
    			
                if (trim($sidsstr)!="") 
                {
                    $rest .= "<b>Attacks</b><br>".trim($sidsstr);
                }
    			
    			$connected .= "<a href='javascript:;' class='tiptip' data-title='$rest'>[".($i-$max)." more]</a>";
    		} 
    		else 
    		{
    			$connected = implode("<br>",$data['connected']);
    		}
    		
    		echo "<tr $color>
    			<td>".$data['name']."</td>
    			<td class='td_mac'>".$data['mac']."<br><font style='font-size:10px'>".$data['vendor']."</font></td>
    			<td class='td_ip_addr'><a target='main' class='HostReportMenu' id='".$data['ip'].";".$data['ip'].";".$data['id']."' href='".Menu::get_menu_url("../av_asset/common/views/detail.php?asset_id=".$data['id'], 'environment', 'assets', 'assets')."'>".$data['ip']."</a></td>
    			<td>".$data['type']."</td>
    			<td>".$data['encryption']."</td>
    			<td>".$data['encoding']."</td>
    			<td class='td_date'><font color='".Wireless::date_color($data['firsttime'],1)."'>".$data['firsttime']."</font></td>
    			<td class='td_date'><font color='".Wireless::date_color($data['lasttime'],2)."'>".$data['lasttime']."</font></td>
    			<td class='td_date'>$connected</td>
    			<td><a href=\"javascript:load_data('clients.php?action=delete&mac=".urlencode($data['mac'])."&sensor=".urlencode($data['sensor'])."')\"><img src='../vulnmeter/images/delete.gif' border='0'/></a></td>
    		</tr>";
    	}
    } 
	?>
	</tbody>
</table>

<?php $db->close(); ?>
