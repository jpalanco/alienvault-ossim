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
$sensor  = GET('sensor');
$ssid    = base64_decode(GET('ssid'));
$si      = intval(GET('index'));
$hideold = intval(GET('hideold'));
$trusted = intval(GET('trusted'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";

ossim_valid($order, OSS_ALPHA, OSS_NULLABLE,                                    'illegal: order');
ossim_valid($ssid, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC_EXT, '\<\|\>%"\{\}\`', 'illegal: ssid');
ossim_valid($sensor, OSS_IP_ADDR, OSS_NULLABLE,                                 'illegal: sensor');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC,                                      'illegal: sensors');

if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db();
$conn = $db->connect();

if (GET('action') == "delete") 
{
    # sensor list with perm
    if (!validate_sensor_perms($conn,$sensor,", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1")) 
    {
        echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
        
        $db->close();
        exit();
    }
    
    Wireless::del_network($conn,$ssid,$sensor);
}

if ($trusted > 0)                   
{
    $_SESSION["trusted"]=$trusted;
}

if (!isset($_SESSION["trusted"])) 
{
    $_SESSION["trusted"]=1;
}

if ($hideold > 0)                   
{
    $_SESSION["hideold"]=$hideold;
}

if (!isset($_SESSION["hideold"])) 
{
    $_SESSION["hideold"]=2;
}
?>

<form id='f_filter'>
	<input type="hidden" name="si" value="<?php echo $si?>">
	<?php echo _("Show All")?>  <input type="radio" name="trusted" onclick="changeview(this.form.si.value,'trusted='+this.value)" value="1" <?php echo ($_SESSION["trusted"]==1) ? "checked" : ""?>>
	<?php echo _("Trusted")?>   <input type="radio" name="trusted" onclick="changeview(this.form.si.value,'trusted='+this.value)" value="2" <?php echo ($_SESSION["trusted"]==2) ? "checked" : ""?>>
	<?php echo _("Untrusted")?> <input type="radio" name="trusted" onclick="changeview(this.form.si.value,'trusted='+this.value)" value="3" <?php echo ($_SESSION["trusted"]==3) ? "checked" : ""?>>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo _("Hide old ones")?> <input type="checkbox" onclick="changeview(this.form.si.value,'hideold='+(this.checked ? '1' : '2'))" name="hideold" <?php echo ($_SESSION["hideold"]==1) ? "checked='checked'" : ""?>>
</form>

<table class='table_data' id="results">
	<thead>
	    <tr>   
    		<th><?php echo _("Network SSID")?></th>
    		<th><?php echo _("# of APs")?></th>
    		<th><?php echo _("# Clients")?></th>
    		<th><?php echo _("Type")?></th>
    		<th><?php echo _("Encryption<br/>Type")?></th>
    		<th><?php echo _("Cloaked")?></th>
    		<th><?php echo _("1st Seen")?></th>
    		<th><?php echo _("Last Seen")?></th>
    		<th><?php echo _("Description")?></th>
    		<th><?php echo _("Notes")?></th>
    		<th>&nbsp;</th>
	    </tr>
	</thead>
	
	<tbody>
		<?php
		
		$networks = Wireless::get_wireless_networks($conn, $order, $sensors);
		$i = 0;
		
		$nossid = array();		
		
		if (is_array($networks) && !empty($networks))
		{
    		foreach ($networks as $data) 
    		{						
    			$_SESSION["clients"][$data['ssid']] = $data['macs'];
    			$enc                                = ($data['encryption']=="None") ? "None" : str_replace("None","<span style='color:red'>None</span>",str_replace(","," ",$data['encryption']));
    			echo "<tr>
    					<td class='left'>
    					   <a href=\"ap.php?ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."\" class='greybox' title='Access Points: ".Util::htmlentities($data['ssid'])."'>".Util::htmlentities(utf8_encode($data['ssid']))."</a>
    					</td>
    					
    					<td class='td_counter'>
    					   <a href=\"ap.php?ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."\" class='greybox' title='Access Points: ".Util::htmlentities($data['ssid'])."'>".$data['aps']."</a>
    					</td>
    					
    					<td class='td_counter'>
    					   <a href=\"clients_gb.php?index=$si&ssid=".urlencode(base64_encode($data['ssid']))."\" class='greybox' title='Clients: ".Util::htmlentities($data['ssid'])."'>".$data['clients']."</a>
    					</td>
    					
    					<td>".$data['type']."</td>
    					
    					<td>$enc</td>
    					
    					<td>".str_replace("Yes/No","Yes/<font color=red>No</font>",str_replace("No/Yes","Yes/No",$data['cloaked']))."</td>
    					
    					<td class='td_date'><span style='color:".Wireless::date_color($data['firsttime'],1)."'>".$data['firsttime']."</span></td>
    					
    					<td class='td_date'><span style='color:".Wireless::date_color($data['lasttime'],2)."'>".$data['lasttime']."</span></td>
    					
    					<td>".$data['description']."</td>
    					
    					<td style='text-align:left;'>".nl2br($data['notes'])."</td>
    					
    					<td style='white-space:nowrap;'>
    						<a href=\"network_edit.php?ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."\" class='greybox' title='Edit ".Util::htmlentities($data['ssid'])." description, type and notes'><img src='../vulnmeter/images/pencil.png' border='0'/></a>
    						<a href=\"javascript:load_data('networks.php?order=$order&action=delete&ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."')\"><img src='../vulnmeter/images/delete.gif' border='0'/></a>
    					</td>
    				</tr>";
    		}
		}				
		?>
	</tbody>
</table>

<?php $db->close(); ?>
