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
//
$order    = GET('order');
$location = base64_decode(GET('location'));

ossim_valid($order, OSS_ALPHA, OSS_NULLABLE,                  'illegal: order');
ossim_valid($location, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal: location');

if (ossim_error()) 
{
    die(ossim_error());
}

$db    = new ossim_db();
$conn  = $db->connect();
$snort = $db->snort_connect();
?>
<table class="table_data" id="results">
	<thead>
	    <tr>   
    		<th><?php echo _("Sensor")?></th>
    		<th><?php echo _("IP Addr")?></th>
    		<th><?php echo _("MAC")?></th>
    		<th><?php echo _("Model #")?></th>
    		<th><?php echo _("Serial #")?></th>
    		<th><?php echo _("Mounting <br/>Location")?></th>
    		<th><?php echo _("In-Service")?></th>
    		<th><?php echo _("Status")?></th>
    		<th>&nbsp;</th>
	    </tr>
	</thead>
	
	<tbody>
		<?php
		# sensor list with perms
		list($all_sensors,$_total) = Av_sensor::get_list($conn);
		$ossim_sensors             = array();

		foreach ($all_sensors as $_sid => $sen) 
		{
    		if ($sen['properties']['has_kismet'] == 1) 
    		{
    		    $ossim_sensors[] = $sen;
    		}
		}
		
		$sensors_list = array();
		
		foreach ($ossim_sensors as $sensor) 
		{
		    $sensors_list[] = $sensor['ip'];
		}
		
		$locations = Wireless::get_locations($conn,$location);
		
		$i = 0;

		if (isset($locations[0])) 
		{			
			if (is_array($locations[0]['sensors'] ) && !empty($locations[0]['sensors'] ))
			{			
    			foreach ($locations[0]['sensors'] as $data) 
    			{				
    				if (!in_array($data['ip'], $sensors_list)) 
    				{
    				    $color .= " bgcolor='#FFCA9F'";
    				}
    				
    				echo "<tr $color>
    					<td><a href=\"javascript:;\" onclick=\"browsexml('".$data['ip']."','')\">".$data['sensor']."</a></td>
    					<td class='td_ip_addr'>".$data['ip']."</td>
    					<td class='td_mac'>".$data['mac']."</td>
    					<td>".$data['model']."</td>
    					<td>".$data['serial']."</td>
    					<td style='text-align:left;padding-left:10px'>".$data["mounting_location"]."</td>
    					<td>".Wireless::get_firstevent_date($snort,$data['ip'])."</td>
    					<td class='td_status'><img src='../pixmaps/tables/tick.png'></td>
    					<td width='20'>
    						<a href='sensor_edit.php?location=".urlencode(base64_encode($location))."&sensor=".urlencode($data["sensor"])."' class='greybox' title='Edit ".$data["sensor"]." details'>
    							<img src='../vulnmeter/images/pencil.png' border='0'/>
    						</a>
    					</td>
    				</tr>";
    			}
    		}    		
		}
		?>
		</tbody>
</table>
<div id="browsexml"></div>
<?php $db->close(); ?>
