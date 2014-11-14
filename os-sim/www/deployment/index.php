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


require_once 'deploy_common.php';


//Checking perms
check_deploy_perms();


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

$options          = array();
$locations        = Locations::get_list($conn);

//Global Visibility
$location_percent = get_global_visibility($conn);

//Asset Visibility
$options          = array("type" => 'asset_network');
$network_percent  = get_asset_visibility($conn, $options);

$options          = array("type" => 'asset_server');
$server_percent   = get_asset_visibility($conn, $options);

//Network Visibility
$types            = array('ids', 'vulns', 'passive', 'active', 'netflow');

foreach ($types as $t)
{
	$var     = $t."_percent";
	$options = array("type" => $t, "percent" => true);
	$$var    = get_network_visibility($conn, $options);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	
	<!--[if lt IE 9]>
		<script type="text/javascript" src="/ossim/js/excanvas.js"></script>
		<script type="text/javascript" src="/ossim/js/html5shiv.js"></script>
	<![endif]-->
	
	<?php
	
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'tipTip.css',                    'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',         'def_path' => TRUE),
            array('src' => 'jquery.easy-pie-chart.css',     'def_path' => TRUE),
            array('src' => 'av_common.css',                 'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                   'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                'def_path' => TRUE),
            array('src' => 'utils.js',                        'def_path' => TRUE),
            array('src' => 'notification.js',                 'def_path' => TRUE),
            array('src' => 'token.js',                        'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                'def_path' => TRUE),
            array('src' => 'greybox.js',                      'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',            'def_path' => TRUE),
            array('src' => 'jquery.easy-pie-chart.js',        'def_path' => TRUE),
            array('src' => '/deployment/js/deploy.js.php',    'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>
	
	<script type='text/javascript'>
		var table_exist      = false;
		var current_location = '';

		
		$(document).ready(function(){
		
			GB_TYPE = 'w';
						
			//Attachements and Relationships GB
			$(document).on("click", ".g_net", function()
			{
				var url = top.av_menu.get_menu_url(this.href, 'environment', 'assets_groups', 'networks');
    			var t   = this.title || $(this).text();
    			
				GB_show(t, url, "70%", "700");
				
				return false;
			});
			
			$(document).on('click', '.g_loc', function()
			{
    			var url = top.av_menu.get_menu_url(this.href, 'configuration', 'deployment', 'locations');
    			var t   = this.title || $(this).text();
    			
				GB_show(t, url, "70%", "600");
				
				return false;
    			
			});

			
			load_circle('#c_location', <?php echo $location_percent[0] ?>);
			load_circle('#c_net', <?php echo $network_percent[0] ?>);
			load_circle('#c_server', <?php echo $server_percent[0] ?>);
			load_circle('#c_ids', <?php echo $ids_percent[0] ?>);
			load_circle('#c_vulns', <?php echo $vulns_percent[0] ?>);
			load_circle('#c_pasive', <?php echo $passive_percent[0] ?>);
			load_circle('#c_active', <?php echo $active_percent[0] ?>);
			load_circle('#c_netflow', <?php echo $netflow_percent[0] ?>);
			
			load_circle('#n_devices', 0);
			load_circle('#n_servers', 0);
			
			
			load_location_list();
			
			$('.ignore_loc').click(function(e){
				var location = $(this).parents('td').data('location');
				GB_show("<?php echo _('Configure Services') ?>","services.php?location="+location,"315","500");
				e.stopPropagation();
			});

						
			$(document).on("click", ".locations tr", function()
			{
				$(".locations tr").removeClass('selected');
				$(this).addClass('selected');
				
				$('#net_data').hide();
				hide_slider_panel(true);
				
				$('#service_help').show();				
				
				var td      = $(this).find('td');
				var id      = $(td).data('location');		
				var nets    = $(td).data('nets');
				var sensors = $(td).data('sensors');			
				
				current_location = id;
				
				if (sensors > 0) 
				{
					if (nets > 0) 
					{
						$('#net_info').hide();
						load_net_list(id);				
					}
					else
					{
						$('#net_list').hide();	
						var txt = "<?php echo _('There are no networks related to this location') ?>. <a href='../net/net_form.php' title='<?php echo _('Add network') ?>' class='g_net'><?php echo _('click here to add a network') ?></a>";
						$('#net_info').html(txt);
						$('#net_info').show();

					}
				}
				else if (id != undefined)
				{
					$('#net_list').hide();	
					var txt = "<?php echo _('There are no sensors related to this location') ?>. <a href='/ossim/sensor/newlocationsform.php?id="+id+"' title='<?php echo _('Relate sensor to location') ?>' class='g_loc'><?php echo _('click here to relate a sensor') ?></a>";
					$('#net_info').html(txt);
					$('#net_info').show();
				}
				
			});
			
			
			$(document).on("click", ".networks tr", function()
			{			
				$(".networks tr").removeClass('selected');
				$(this).addClass('selected');
				
				var net_id = $(this).find('.net_item').attr('net');	

				/** reset the net values **/
				
				hide_slider_panel(true);
				
				$('#service_help').hide();
				$('.res_item').off('click');
				$('.tlink').off('click');
								
				$(".tlink").removeClass('l_error');
				
				update_circle('#n_devices', 0);
				update_circle('#n_servers', 0);
				
				/**                     **/
				
				//Loading new values
				load_net_services(net_id);
			
			});
						
			//TipTip
			$('.box_help').tipTip();
							
		});
				
		
	</script>
	
</head>

<body>

<?php 

if ($error)
{
?>
	<div style='width:100%margin:0 auto;'>
	
		<?php
		
		$config_nt = array(
			'content' => $error_msg,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => true
			),
			'style'   => 'width: 45%; margin: 20px auto; text-align: center;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		
		?>
		
	</div>
	
<?php
	die();
}
?>

	<div id='container'>
		<div id='main_notif'></div>
		
		
		<table class='transparent clear_table' width="100%" align='center'>
			<tr>
				<td class='noborder'>
					<div id='box_global' class='box_chart box_help' title='<?php echo _('Global Visibility') ?>' >
						<div class='box_title sec_title'><?php echo _('Global Visibility') ?></div>
						<table class='transparent circle'>
							<tr>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
									
										<div class='percentage' id='c_location' data-percent=""><span id='c_location_label'></span></div>
										<div class='box_subtitle' style='max-width:180px;'><?php echo _('Locations with Sensors') ?></div>
										<div class='box_count'>
											<?php echo   $location_percent[1] . "/" . $location_percent[2] ?> <?php echo _('Locations with sensors') ?>
										</div>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</td>
								
				<td class='noborder'>
					<div id='box_assets' class='box_chart box_help' title='<?php echo _('Assets Visibility') ?>' >
						<div class='box_title sec_title'><?php echo _('Assets Visibility') ?></div>
						<table class='transparent circle'>
							<tr>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_net' data-percent="">
											<span id='c_net_label'></span>
										</div>
										<div class='box_subtitle'>
											<?php echo _('Network Devices') ?>
										</div>
										<div class='box_count' id='counter_net_devices'>
											<?php echo   $network_percent[1] . "/" . $network_percent[2] . " " . _('Configured') ?>
										</div>
									</div>
								</td>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_server' data-percent="">
											<span id='c_server_label'></span>
										</div>
										<div class='box_subtitle'>
											<?php echo _('Servers') ?>
										</div>
										<div class='box_count' id='counter_server_devices'>
											<?php echo   $server_percent[1] . "/" . $server_percent[2] . " " . _('Configured') ?>
										</div>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</td>
								
				<td class='noborder'>
					<div id='box_networks' class='box_chart box_help' title='<?php echo _('Network Visibility') ?>' >
						<div class='box_title sec_title'><?php echo _('Network Visibility') ?></div>
						<table class='transparent circle'>
							<tr>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_ids' data-percent=""><span id='c_ids_label'></span></div>
										<div class='box_subtitle'><?php echo _('IDS Enabled') ?></div>
										<div class='box_count'><?php echo   $ids_percent[1] . "/" . $ids_percent[2] . " " . _('Networks') ?></div>
									</div>
								</td>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_vulns' data-percent=""><span id='c_vulns_label'></span></div>
										<div class='box_subtitle'><?php echo _('Vulnerability Scans') ?></div>
										<div class='box_count'><?php echo   $vulns_percent[1] . "/" . $vulns_percent[2] . " " . _('Networks') ?></div>
									</div>
								</td>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_pasive' data-percent=""><span id='c_pasive_label'></span></div>
										<div class='box_subtitle'><?php echo _('Passive Inventory') ?></div>
										<div class='box_count'><?php echo   $passive_percent[1] . "/" . $passive_percent[2] . " " . _('Networks') ?></div>
									</div>
								</td>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_active' data-percent=""><span id='c_active_label'></span></div>
										<div class='box_subtitle'><?php echo _('Active Inventory') ?></div>
										<div class='box_count'><?php echo   $active_percent[1] . "/" . $active_percent[2] . " " . _('Networks') ?></div>
									</div>
								</td>
								<td class='noborder' valign='top'>
									<div class='box_circle'>
										<div class='percentage' id='c_netflow' data-percent=""><span id='c_netflow_label'></span></div>
										<div class='box_subtitle'><?php echo _('Netflow Monitoring') ?></div>
										<div class='box_count'><?php echo   $netflow_percent[1] . "/" . $netflow_percent[2] . " " . _('Networks') ?></div>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</td>
			</tr>		
		</table>
				
		
		<div class="cat_container">
			<table class='transparent clear_table' width="100%" align='center' >
				<tr>
					<td class='noborder' width='20%' valign='top'>
						
						<div id="first" class="category-section">
							<div id='location_list'>
								<table class='noborder locations' width='100%' align="center">
									<tbody>
									<?php 
									$locations = is_array($locations) ? $locations : array();
									
									foreach ($locations as $l) 
									{ 
										$id       = $l->get_id();
										$name     = $l->get_name();
										
										$icon     = (file_exists("../pixmaps/flags/".$l->get_country().".png")) ? '<img src="../pixmaps/flags/'.$l->get_country().'.png" border="0" class="flag">' : '';
										
										$total_n  = Locations::count_related_networks($conn, $id);
										$class    = ($total_n > 0) ?  colorize_location($conn, $id) : 'l_error';
										
										$sensors  = Locations::get_related_sensors($conn, $id);
										
										if (count($sensors) == 0)
										{
											$tooltip  = "0 ". _('Sensors') ."<br> $total_n ". _('Networks');
										}
										else
										{
											$tooltip  = count($sensors) ." ". ((count($sensors) ==1) ? _('Sensor') : _('Sensors')) .":<br> ";
											$tooltip  .= "<ul>";
											
											foreach ($sensors as $s)
											{
												$tooltip  .= "<li>". $s[1] ." (". $s[2] .")</li>";
											}
											
											$tooltip  .= "</ul>";											
											$tooltip  .= "$total_n ". (($total_n ==1) ? _('Network') : _('Networks')) ;
										
										}
									
									?>
									
									<tr class='<?php echo $class ?>'>
										<td class='location' data-sensors='<?php echo count($sensors) ?>' data-nets='<?php echo $total_n ?>' data-location='<?php echo $id ?>'>
											<a href="javascript:;"><?php echo $icon . " " . Util::htmlentities($name)?></a>
											<small title='<?php echo $tooltip ?>' class='box_help'>(<?php echo "$total_n"?>)</small>
											<div class="fright">
												<a href='javascript:;' class='ignore_loc'>
												    <img src="/ossim/pixmaps/status/wrench.png" border="0" style="height:12px;" />
												</a>
												<img src='/ossim/pixmaps/br_next.png' height='10px'>
											</div>
										</td>
									</tr>
									
									<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
						
					</td>
			
					<td class='noborder' width='30%' valign='top'>
						<div id="second" class="category-section">
							<div id='net_info' class='helper-text'><?php echo _('Click on a location to retrieve its network list') ?></div>
							<div style='display:none;' id='net_list'>
								<table class='noborder networks' width='100%' align="center">
									<tbody></tbody>
								</table>
							</div>
						</div>
					</td>
					<td class='noborder' width='50%' valign='top'>
					
						<div id="third" class="category-section">
						
							<div id='net_notif'></div>
							<div id='service_help' class='helper-text'><?php echo _('Click on a network to retrieve the available services') ?></div>
							<input type='hidden' id='net_id_selected' value=''>
							<table id='net_data' class='transparent' align="center">
								<tr>
									<td class='noborder' width='100%' colspan='2'>
										<div class='net_info'>	
											<div class='big line' id='net_name' style='text-decoration:underline;'></div>
											<div class='midbig line'><strong><?php echo _('Owner') ?>: </strong><span id='net_owner'></span></div>
											<div class='line' id='net_descr'></div>
										</div>
									</td>
								</tr>
								<tr>
									<td class='noborder' width='75%'>
										<table id='t_chk_item' class='transparent' align='left' width='100%'>
											<tr>
												<td class='res_item' id='service_ids'>
													<div class="item_result">
														<img src="/ossim/pixmaps/status/quiz.png" alt="" align="absmiddle"/>
														<strong><?php echo _('IDS Enabled') ?></strong>
													</div>
												</td>
											</tr>
											
											<tr>
												<td class='res_item' id='service_vulns'>
													<div class="item_result">
														<img src="/ossim/pixmaps/status/quiz.png" alt="" align="absmiddle"/>
														<strong><?php echo _('Vulnerability Scan Scheduled') ?></strong>
													</div>
												</td>
											</tr>
											
											<tr>
												<td class='res_item' id='service_passive'>
													<div class="item_result">
														<img src="/ossim/pixmaps/status/quiz.png" alt="" align="absmiddle"/>
														<strong><?php echo _('Passive Inventory Enabled') ?></strong>
													</div>
												</td>
											</tr>
											
											<tr>
												<td class='res_item' id='service_active'>
													<div class="item_result">
														<img src="/ossim/pixmaps/status/quiz.png" alt="" align="absmiddle"/>
														<strong><?php echo _('Active Inventory Enabled') ?></strong>
													</div>
												</td>
											</tr>
											
											<tr>
												<td class='res_item' id='service_netflow'>
													<div class="item_result">
														<img src="/ossim/pixmaps/status/quiz.png" alt="" align="absmiddle"/>
														<strong><?php echo _('Netflow Monitoring Enabled') ?></strong>
													</div>
												</td>
											</tr>

										</table>								
									</td>
									
									<td class='noborder' width='25%' valign='middle'>
										<div class='box_circle_net'>
											<div class='percentage' id='n_devices' data-percent="">
												<span id='n_devices_label'></span>
											</div>
											<div class='box_subtitle' >
												<a href='javascript:;' onclick="load_slider_panel('network');" title='<?php echo _('Click here to see the network devices list') ?>' class='box_help'>
													<img src='/ossim/pixmaps/network-adapters.png' height='12' border=0 align='absline'/>
													<?php echo _('Network Devices') ?>
												</a>
											</div>
											<div class='box_count tlink' id='service_net'>
												<span id='n_devices_count'>0/0</span> <?php echo _('Configured') ?>
											</div>
										</div>	
										<div class='box_circle_net'>
											<div class='percentage' id='n_servers' data-percent="">
												<span id='n_servers_label'></span>
											</div>
											<div class='box_subtitle'>
												<a href='javascript:;' onclick="load_slider_panel('server');" title='<?php echo _('Click here to see the servers list') ?>' class='box_help'>
													<img src='/ossim/pixmaps/servers--arrow.png' height='12' border=0 align='absline'/>
													<?php echo _('Servers') ?>
												</a>
											</div>
											<div class='box_count tlink' id='service_server'>
												<span id='n_servers_count'>0/0</span> <?php echo _('Configured') ?>
											</div>
										</div>
										
									</td>	
									
								</tr>		

								<tr>
									<td id='service_netflow' class="center" colspan='2'>

										<a href='javascript:;' onclick="load_fade_panel();" title='<?php echo _('Click here to see the hosts without device type assigned') ?>' class='box_help'>
										
											<img src='/ossim/pixmaps/tools_gray.png' height='12' border=0 align='absline'/>
											<?php echo _('Unclassified Asset List') ?>
										</a>

									</td>
								</tr>			
							</table>
							
							<div id='slidep'>
								<div id='slide_button'>																	
									 <div class='c_back_button' style='display:block;'>
                                	     <input type='button' class="av_b_back" onclick="hide_slider_panel();"/>                                	
                                	</div>									
								</div>
								
								<div class='loading_box' id='if_loading'>
									<div class='loading_panel'>
										<div style='padding: 10px; overflow: hidden;'>
											<?php echo _("Loading asset details") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
										</div>
									</div>
								</div>
							</div>

						</div>
					</td>
				</tr>
			</table>

			<div id='device_assets_list'>

				<div id='fade_button'>										
					 <div class='c_back_button' style='display:block;'>
                	     <input type='button' class="av_b_back" onclick="hide_fade_panel();"/>                
                	</div>					
				</div>	
				
				<div class='loading_box' id='fade_loading'>
					<div class='loading_panel'>
						<div class='text' style='padding: 10px; overflow: hidden;'>
							<?php echo _("Loading asset details") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
						</div>
					</div>
				</div>

			</div>

		</div>
	</div>

</body>
</html>
<?php
$db->close();
