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
require_once 'get_sensors.php';

$ip = GET('sensor_ip');

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _('Sensor ip'));

if (ossim_error()) 
{
    die(ossim_error());
}

if(!Session::sensorAllowed($ip))
{
    exit();
} 

list($sensor_list, $err) = server_get_sensors();

foreach ($sensor_list as $sip => $info) 
{
	foreach ($info as $plugin_id => $data)
	{
		$data['sensor']        = $sip;
		$data['plugin_id']     = $plugin_id;
		$sensor_plugins_list[] = $data;
	}
}

$sensor_plugins_list = server_get_sensor_plugins($ip);

$db             = new ossim_db(TRUE);
$conn           = $db->connect();

$acid_link      = $conf->get_conf('acid_link');
$acid_prefix    = $conf->get_conf('event_viewer');

$acid_main_link = str_replace('//', '/', $conf->get_conf('acid_link') . '/' . $acid_prefix . "_qry_main.php?clear_allcriteria=1&search=1&bsf=Query+DB&ossim_risk_a=+");

?>
<table class="transparent" width="100%" height="100%">
	<tr height="100%">
		<td width="36" height="100%">
			<table class='t_sensor_info transparent'>
				<tr><td class='bk_top'>&nbsp;</td></tr>
				<tr><td class='bk_bg'>&nbsp;</td></tr>
				<tr><td class='bk_center'>&nbsp;</td></tr>
				<tr><td class='bk_bg'>&nbsp;</td></tr>
				<tr><td class='bk_bottom'>&nbsp;</td></tr>
			</table>
		</td>
		
		<td>
			<table class="t_sensor_info_data table_list" align="left" width="100%">
				<tr>
					<th></th>
					<th> <?php echo _('Plugin'); ?> </th>
					<th> <?php echo _('Process Status'); ?> </th>
					<th> <?php echo _('Action'); ?> </th>
					<th> <?php echo _('Plugin status'); ?> </th>
					<th> <?php echo _('Action'); ?> </th>
					<th> <?php echo _('Latest Security Event'); ?> </th>
				</tr>
				
				<?php
				if ($sensor_plugins_list) 
				{	
					foreach($sensor_plugins_list as $sensor_plugin) 
					{
                        if ($sensor_plugin['sensor'] == $ip) 
                        {
                            $cid     = str_replace('.', '_', $ip).'_'.$sensor_plugin['plugin_id'];
                            $id      = $sensor_plugin['plugin_id'];
                            $state   = $sensor_plugin['state'];
                            $enabled = $sensor_plugin['enabled'];
                            
                            if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id")) 
                            {
                            	$plugin_name = $plugin_list[0]->get_name();
                            } 
                            else 
                            {
                            	$plugin_name = $id;
                            }
                            
                            $events = Plugin::get_latest_SIM_Event_by_plugin_id($conn, $id, $ip);
                            $event = $events[0];
                            ?>
							<tr>
								<td width="16"><a href="javascript:;" onclick="load_lead('<?php echo $cid?>','<?php echo $id?>','<?php echo $ip?>')"><img id='img_<?php echo $cid?>' src="../pixmaps/plus-small.png" border="0" align="absmiddle"></a></td>
								<td><?php echo $plugin_name ?></td>
									<?php 
									if ($state == 'start') 
									{ 
										?>
										<td><span style='color:green; font-weight: bold;'><?php echo _('UP'); ?></span></td>
										<td>
                                            <a href="javascript:void(0);" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=stop&id=$id"?>',250, 530); return false">
                                                <?php echo _("Stop");?>
                                            </a>
										</td>
										<?php
									} 
									elseif ($state == 'stop') 
									{
										?>
										<td><span style='color:red; font-weight: bold;'><?php echo _('DOWN'); ?></span></td>
										<td>
                                            <a href="javascript:void(0);" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=start&id=$id"?>',250, 530); return false">
                                                <?php echo _("Start");?> 
                                            </a>
										</td>
										<?php
									} 
									else 
									{
										?>
										<td><?php echo _('Unknown'); ?></td>
										<td>-</td>
										<?php
									}
									
									if ($enabled == 'true') 
									{
										?>
										<td><span style='color:green; font-weight: bold;'><?php echo _('ENABLED'); ?></span></td>
										<td><a href="javascript:void(0);" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=disable&id=$id" ?>',250,530);return false"><?php echo _("Disable"); ?> </a></td>
										<?php
									} 
									else 
									{
										?>
										<td><span style='color:red; font-weight: bold;'><?php echo _('DISABLED'); ?></span></td>
										<td><a href="javascript:void(0);" onclick="GB_show('Command','<?php echo "sensor_plugins.php?sensor=$ip&ip=$ip&cmd=enable&id=$id" ?>',250,530);return false"><?php echo _('Enable'); ?> </a></td>
										<?php
									}
								?>
								<td>
									<table class="noborder">
										<tr>
											<td class="small nobborder" nowrap='nowrap'><i><?php echo $event["timestamp"]?></i>&nbsp;</td>
											<td class="small nobborder">											 
                                                <?php
                                                $f_url = Menu::get_menu_url($acid_main_link."&plugin=".urlencode($sensor_plugin["plugin_id"]), 'analysis', 'security_events', 'security_events');
                                                ?>                                                
                                                <a href="<?php echo $f_url?>"><strong><?php echo $event["sig_name"]?></strong></a>
											 </td>
										</tr>
									</table>
								</td>
							</tr>
							
							<tr colspan="7" class="hidden_row"><td></td></tr>
							
							<tr class='tr_l_sensor' id="tr_l_sensor_<?php echo $cid?>">
								<td colspan="2" id="selector_<?php echo $cid?>">
									<form style="margin:0px">
										<table class="transparent center">
											<tr>
												<td class="noborder"><img src="../pixmaps/flag_yellow.png" border="0"></td>
												<td class="noborder"><input type="text" size="4" id="yellow_<?php echo $cid?>" value="12"> <?=_('hours')?></td>
											</tr>
											
											<tr>
												<td class="noborder"><img src="../pixmaps/flag_red.png" border="0"></td>
												<td class="noborder"><input type="text" size="4" id="red_<?php echo $cid?>" value="48"> <?=_('hours')?></td>
											</tr>
											<tr>
												<td colspan="2" class="noborder" align="center">
												    <input type="button" class="m_button small" onclick="mark('<?php echo $cid?>', true)" value="<?php echo _("Mark")?>">
												</td>
											</tr>
										</table>
									</form>
								</td>    
								<td colspan="5" id="plugin_<?php echo $cid?>"></td>
							</tr>
							<?php
						} // if
        
					} // foreach
    
					?>
					<tr class='tr_si_refresh'>
						<td colspan="7">
							<a class='button small' href="<?php echo "sensor_plugins.php?sensor=$ip" ?>"> <?php echo _('Refresh')?> </a>
						</td>
					</tr>
				<?php
				} // if
				?>
			</table>
		</td>
	</tr>
</table>

<?php
$db->close();
?>