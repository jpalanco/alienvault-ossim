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

$directive_id = GET('directive_id');
ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));

if (ossim_error()) 
{
    die(ossim_error());
}

$db    = new ossim_db();
$conn  = $db->connect();

// Get Directive info
list($properties,$num_properties) = Compliance::get_category($conn,"AND category.sid=$directive_id");
$iso_groups = Compliance_iso27001::get_groups($conn,"WHERE SIDSS_Ref LIKE '$directive_id' OR SIDSS_Ref LIKE '$directive_id,%' OR SIDSS_Ref LIKE '%,$directive_id' OR SIDSS_Ref LIKE '%,$directive_id,%'");
$pci_groups = Compliance_pci::get_groups($conn,"WHERE SIDSS_ref LIKE '$directive_id' OR SIDSS_ref LIKE '$directive_id,%' OR SIDSS_ref LIKE '%,$directive_id' OR SIDSS_ref LIKE '%,$directive_id,%'");

$criteria = array(
    "src_ip"        => '',
    "dst_ip"        => '',
    "hide_closed"   => 0,
    "order"         => '',
    "inf"           => 0,
    "sup"           => 5,
    "date_from"     => '',
    "date_to"       => '',
    "query"         => '',
    "directive_id"  => $directive_id,
    "intent"        => 0,
    "sensor"        => '',
    "tag"           => '',
    "num_events"    => '',
    "num_events_op" => 0,
    "plugin_id"     => '',
    "plugin_sid"    => '',
    "ctx"           => '',
    "host"          => '',
    "net"           => '',
    "host_group"    => ''
);
list($alarms,$num_alarms) = Alarm::get_list($conn, $criteria);
?>
	
<table class="transparent" height="100%" width="100%">
	<tr>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th colspan="2" height="15"><?php echo _("Properties")?></th></tr>
				<?php 
				if (count($properties) < 1) 
				{ 
    				?>
    				<tr>
    				    <td class="nobborder" style="color:gray;padding:10px">
    				        <i><?php echo _("No properties found")?></i><input type="button" class='small av_b_secondary' value="<?php echo _("Edit") ?>" onclick="GB_show('Directive properties', 'form_properties.php?sid=<?php echo $directive_id ?>', 600, 600)"/>
    				    </td>
    				</tr>
    				<?php 
        		} 
        		else 
        		{            		
                    foreach ($properties as $p) 
                    { 
                        ?>
        				<tr>
        				    <td width="50%" class="nobborder" style="text-align:right"><?php echo _("Targeted")?></td>
        				    <td class="nobborder" style="padding-right:5px;padding-left:5px">
        				        <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_targeted()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
        				    </td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("Approach")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_approach()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("Exploration")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_exploration()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
        				    <td class="nobborder" style="text-align:right"><?php echo _("Penetration")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_penetration()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right" nowrap><?php echo _("General Malware")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_generalmalware()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("IMP QOS")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_imp_qos()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("IMP Infleak")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_imp_infleak()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("IMP Lawful")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_imp_lawful()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("IMP Image")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_imp_image()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
        				<td class="nobborder" style="text-align:right"><?php echo _("IMP Financial")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_imp_financial()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
        				<td class="nobborder" style="text-align:right"><?php echo _("IMP Infleak")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_imp_infleak()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
        				    <td class="nobborder" style="text-align:right"><?php echo _("Availability")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_D()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("Integrity")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_I()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("Confidentiality")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_C()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				
        				<tr>
            				<td class="nobborder" style="text-align:right"><?php echo _("Net Anomaly")?></td>
            				<td class="nobborder" style="padding-right:5px;padding-left:5px">
            				    <img align="absmiddle" src="../pixmaps/tables/<?php echo ($p->get_net_anomaly()) ? "tick-small-circle.png" : "cross-small-circle.png"?>"/>
            				</td>
        				</tr>
        				<tr>
        					<td colspan="2" style='padding: 10px 0px;'>
        						<input type="button" class='small' value="<?php echo _("Edit") ?>" onclick="GB_show('Directive properties', 'form_properties.php?sid=<?php echo $directive_id ?>', 600, 600)"/>
        						
        						<input type="button" class='small' value="<?php echo _("Remove") ?>" onclick="if(confirm('<?php echo _("Are you sure to delete all properties?") ?>')) document.location.href='form_properties.php?sid=<?php echo $directive_id ?>&only_delete=1'"/>
        					</td>
        				</tr>
				    <?php 
				    } 
				} 
				?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th height="15"><?php echo _("ISO27001")?></th></tr>
				<?php 
				if (count($iso_groups) < 1) 
				{    				
    				?>
    				<tr>
    				    <td class="nobborder" style="color:gray;padding:10px; white-space: nowrap;"><i><?php echo _("No ISO27001 found")?></i></td>
    				</tr>
    				<?php 
        		} 
        		else 
        		{ 
				    foreach ($iso_groups as $title=>$data)
				    { 
    				    foreach ($data['subgroups'] as $ref=>$iso) 
    				    { 
        				    ?>
        				    <tr>
        				        <td class="nobborder" style="text-align:left">
        				            <strong><?php echo $iso['Ref']?></strong> <?php echo utf8_encode($iso['Security_controls'])?>
        				        </td>
        				    </tr>
        				    <?php 
        				}
    				} 
				} 
			?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th height="15"><?php echo _("PCI")?></th></tr>
				<?php 
				if (count($pci_groups) < 1) 
				{ 
    				?>
    				<tr>
    				    <td class="nobborder" style="color:gray;padding:10px; white-space: nowrap;">
    				        <i><?php echo _("No PCI found")?></i>
    				    </td>
    				</tr>
    				<?php 
				} 
				else 
				{ 
				    foreach ($pci_groups as $title=>$data)
				    { 
    				    foreach ($data['subgroups'] as $ref=>$iso) 
    				    { 
    				        ?>
    				        <tr>
    				            <td class="nobborder" style="text-align:left">
    				                <strong><?php echo $iso['Ref']?></strong> <?php echo utf8_encode($iso['Security_controls'])?>
    				            </td>
    				        </tr>
    				        <?php 
    				    }
				    } 
				    ?>
				<?php } ?>
			</table>
		</td>
		<td class="nobborder" valign="top">
			<table height="100%" width="100%">
				<tr><th colspan="3" height="15"><?php echo _("Alarms")?></th></tr>
				<?php 
				if (count($alarms) < 1) 
				{ 
    				?>
    				<tr>
        				<td class="nobborder" style="color:gray;padding:10px; white-space: nowrap;">
        				    <i><?php echo _("No Alarms found")?></i>
        				</td>
    				</tr>
    				<?php 
        		} 
        		else 
        		{ 
            		?>
    				<tr>
    					<th height="10"><?php echo _("Name")?></th>
    					<th height="10"><?php echo _("Risk")?></th>
    					<th height="10"><?php echo _("Status")?></th>
    				</tr>
    				<?php 
    				$i = 0; 
				
    				foreach ($alarms as $alarm) 
    				{ 
    					$bg    = "white";
    					$color = "black";
    					$risk  = $alarm->get_risk();
    					
    					if ($risk > 7) 
    					{ 
    					   $bg="red"; 
    					   $color="white"; 
    					}					
    					elseif ($risk > 4) 
    					{ 
    					   $bg="orange"; 
    					   $color="black"; 
    					}					
    					elseif ($risk > 2) 
    					{ 
    					   $bg="green"; 
    					   $color="white"; 
    					}
    					?>
        				<tr>
        					<td class="nobborder" style="text-align:left"><?php echo str_replace("directive_event: ","",$alarm->get_sid_name())?></td>
        					<td class="nobborder" style="text-align:center;background-color:<?php echo $bg?>;color:<?php echo $color?>"><?php echo $risk?></td>
        					<td class="nobborder" style="text-align:center">
        					    <img src="../pixmaps/<?php echo ($alarm->get_status() == "open") ? "lock-unlock.png" : "lock.png"?>"/>
        					</td>
        				</tr>
        				<?php 
        				$i++; 
    				} 
    				 
    				if (count($alarms) >= 5) 
    				{ 
    					$url_alarm = Menu::get_menu_url("../alarm/alarm_console.php?hide_closed=1&directive_id=$directive_id", 'analysis', 'alarms', 'alarms');  
    					?>
    					<tr>
    					   <td colspan="3" class="nobborder" style="text-align:right">
    					       <a href="<?php echo $url_alarm ?>"><?php echo _("More")?>>></a>
    					   </td>
    					</tr>
    					<?php
    				} 
    			}
			?>
			</table>
		</td>
	</tr>
</table>
