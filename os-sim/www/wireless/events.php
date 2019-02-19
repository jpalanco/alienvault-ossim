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

ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal: order');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC,   'illegal: sensors');

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->snort_connect();

?>
<table class="table_data" id="results">
	<thead>
        <tr>
            <th style='width:250px;'><?php echo _("Signature")?></th>
            <th><?php echo _("Total #")?></th>
            <th><?php echo _("Wireless IDS<br/>Sensor")?></th>
            <th><?php echo _("Source<br/>Address")?></th>
            <th><?php echo _("Dest.<br/>Address")?></th>
            <th><?php echo _("First")?></th>
            <th><?php echo _("Last")?></th>
        </tr>
	</thead>
	<tbody>
	<?php
	
	$events = Wireless::get_events($conn,explode(',', $sensors));
	
	$i = 0;
	
	if (is_array($events) && !empty($events))
	{
    	foreach ($events as $data) 
    	{		
    		?>
            <tr>
                <td class='td_signature' style='text-align:left;'><?php echo $data['signature']?></td>
                <td class='td_counter'><?php echo $data['total']?></td>
                <td class='td_ip_addr'><?php echo $data['sensor']?></td>
                <td class='td_counter'><?php echo $data['src']?></td>
                <td class='td_counter'><?php echo $data['dst']?></td>
                <td class='td_date'><?php echo $data['first']?></td>
                <td class='td_date'><?php echo $data['last']?></td>
            </tr>
    		<?php
    	}
	}
	?>
	</tbody>
</table>

<br/>

<form action="../forensics/base_qry_main.php" method="GET">	
	<input type="hidden" name="m_opt" value="analysis"/>
	<input type="hidden" name="sm_opt" value="security_events"/>
	<input type="hidden" name="h_opt" value="security_events"/>
	<input type="hidden" name="search" value="1"/>
	<input type="hidden" name="hidden_menu" value="1"/>	
	<input type="hidden" name="plugin" value="1596"/>
	<input type="hidden" name="timerange" value="all"/>
	<input type="hidden" name="clear_criteria" value="time"/>
	<input type="hidden" name="bsf" value="Query DB"/>
	<input type="hidden" name="search" value="1"/>
	<input type="submit" value="View All"/>
</form>

<?php $db->close();?>
