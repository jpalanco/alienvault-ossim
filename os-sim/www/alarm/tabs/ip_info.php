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

Session::logcheck("analysis-menu", "ControlPanelAlarms");

/* connect to db */
$db              = new ossim_db(TRUE);
$conn            = $db->connect();

$origin          = GET('prefix');

ossim_valid($origin,   "src","dst",         'illegal:' . _("Prefix"));

if ( ossim_error() )  die(ossim_error());


$data        = $_SESSION['_alarm_stats'][$origin ];
$event_info  = $_SESSION['_alarm_stats']['event_info'];

$ctx         = $event_info['agent_ctx'];
$hosts_ids   = array();


// Do not delete, this var is used in single_ip.php
$geoloc      = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');
$geoloc_org  = NULL;

if (Session::is_pro() && file_exists('/usr/share/geoip/GeoIPOrg.dat')) 
{
    $geoloc_org  = new Geolocation('/usr/share/geoip/GeoIPOrg.dat');    
}

?>
	<style type='text/css'>

		.ui-widget {
			font-family: Arial;
			color: #333;
		}
		
		.ui-widget-header, .ui-widget-content a {
			color: #333;
		}
		
		#common_detail {
			width:95%;
			position: relative;
			overflow:auto;
			height: 180px;
		}
		
		.container-left {
			width:45%;
			float:left;
			height:100%;
			position:relative;
		}
		
		.container-right {
			width:55%;
			float:right;
			height:100%;
		}
				
		.view {
			width:100%;
			margin:0 auto;
			text-align:center;	
			clear:both;
			position:relative;
		}
		
		.loading_panel{
			width: 50% !important; 
			margin: 150px auto !important; 
		}
			
	</style>
	
	<script type="text/javascript">
	
		function reload_info(prefix, ip, id)
		{
			$('#if_loading_<?php echo $origin ?>').show()
			
			var url = "extra/host_info.php?prefix="+ prefix +"&ip="+ ip +"&id="+ id;
			
			iframe = document.getElementById('if_detail_<?php echo $origin ?>'); 
			iframe.src = url; 
		}
				
		$(document).ready(function(){

			$('.table_data_<?php echo $origin ?>').dataTable( {
				"bFilter": true,
				"sScrollY": "135",
				"iDisplayLength": 7,
				"sPaginationType": "full_numbers",
				"bLengthChange": false,
				"bJQueryUI": true,
				"aaSorting": [[ 1, "asc" ]],
				"aoColumns": [
					{ "bSortable": true },
					{ "bSortable": true },
					{ "bSortable": false },
					<?php 
					if (is_object($geoloc_org)) 
					{ 
    					?>
    					{ "bSortable": false },					
    					<?php 
    				} 
    				?>					
					{ "bSortable": false }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Processing') ?>...",
					"sLengthMenu": "Show _MENU_ entries",
					"sZeroRecords": "<?php echo _('No matching records found') ?>",
					"sEmptyTable": "<?php echo _('No data available in table') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "<?php echo _('Total: _TOTAL_ IPs') ?>",
					"sInfoEmpty": "<?php echo _('Total: 0 IPs') ?>",
					"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
					"sInfoPostFix": "",
					"sInfoThousands": ",",
					"sSearch": "<?php echo _('Search') ?>",
					"sUrl": "",
					"oPaginate": {
						"sFirst":    "<?php echo _('First') ?>",
						"sPrevious": "<?php echo _('Previous') ?>",
						"sNext":     "<?php echo _('Next') ?>",
						"sLast":     "<?php echo _('Last') ?>"
					}
				},
				"fnInitComplete": function() {
					
					var tittle = "<div style='position:absolute;width:30%;margin:0 auto;text-align:left;left:10px;top:2px'><b><?php echo _('IPs List') ?></b></div>"
					$('#table_<?php echo $origin ?>').find('div.dt_header').prepend(tittle);
					
				}
			});
			
			$('.alarm-help').tipTip({content: $(this).attr('data-title')});

            if (typeof(load_contextmenu) == 'function')
            {
                load_contextmenu();
            }									
		});
		
	</script>


<div style='width:100%;height:400px;position:relative;overflow:hidden;'>
	<div class='container-left'>
	
		<div id='common_detail'>
			<?php
            $complete=true;
			if(count($data['ip']) == 1)
			{
				$prefix = "_t". $origin;
				include "../boxes/single_ip.php";
			}
			else
			{
			    $prefix = '_t'. $origin;
				include "../boxes/multiple_ip.php";
			}
			?>
		</div>
		
		<div id='table_<?php echo $origin ?>' style='width:95%;float:left;position:absolute;bottom:10px;display:block;'>
			<table class='table_data table_data_<?php echo $origin ?>' width='100%' align="center">
				<thead>
					<tr>
						
						<th>
							<a href='javascript:;' class='alarm-help' title='<?php echo _('Associated Events') ?>'> # </a>
						</th>
						
						<th>
							<?php echo _("IP"); ?>
						</th>
						
						<th>
							<?php echo _("Loc"); ?>
						</th>

						<?php 
						if (is_object($geoloc_org)) 
						{ 
        					?>
    						<th>
    							<?php echo _("Org"); ?>
    						</th>
    						<?php 
						} 
						?>
											
						<th>
							<?php echo _("Info"); ?>
						</th>												
					</tr>
				</thead>
				<tbody>
					<?php foreach($data['ip'] as $ip => $dip) 
					{ 
						$hostname = '';
						$location = $dip['country'];
						$flag     = strtolower($location);
						$uuid     = strtoupper(preg_replace('/^0x|NULL/i', '', $dip['uuid']));
						
						if( !isset($hosts_ids[$uuid]) )
						{
							$_hname = Asset_host::get_name_by_id($conn, $uuid);
							$hosts_ids[$uuid] = ( $_hname == _('Unknown') ) ? '' : $_hname;
						}
						
						$hostname = $hosts_ids[$uuid];
						
						$hostname = ($hostname == '' && $hostname != $ip)? $ip : $hostname . " ($ip)" ;
						
						if (strlen($location) == 2 && $location != $geoloc->get_country_name($location))
						{
							$location = $geoloc->get_country_name($location);
						}

						if(file_exists("/usr/share/ossim/www/pixmaps/flags/$flag.png"))
						{
							$flag = "/ossim/pixmaps/flags/$flag.png";
						}
						else
						{
							// Try to geoloc
							$record = $geoloc->get_location_from_file($ip);
							$flag   = strtolower($record->country_code);
							
							if (!file_exists("/usr/share/ossim/www/pixmaps/flags/$flag.png")) 
							{
							    $flag      = "/ossim/alarm/style/img/unknown.png";  
								$location = _('Unknown');
							} 
							else 
							{
							   $flag     = "/ossim/pixmaps/flags/$flag.png";
							   $location = $record->country_name;
							}
						}
						
					?>
					<tr>
						<td style="font-size:10px">
							<?php echo $dip['count'] ?>
						</td>
						
						<td style="font-size:10px">
							<a href='javascript:;' onclick="reload_info('<?php echo $origin ?>','<?php echo $ip ?>','<?php echo $uuid ?>');" ><?php echo $hostname ?></a>
						</td>
						
						<td style="font-size:10px">
							<?php echo "<img src='$flag' width='20px' height='14px' align='absmiddle' class='alarm-help' data-title=\"$location\" style='padding-bottom:5px;'/>"; ?>
						</td>

						<?php 
						if ($geoloc_org) 
						{ 
    						?>
    						<td style="font-size:10px">
    							<?php echo $geoloc_org->get_organization_by_host($ip); ?>
    						</td>
    						<?php 
    				    } 
    				    ?>
						
						<td style="font-size:10px">
							[<a href="http://www.projecthoneypot.org/ip_<?php echo $ip ?>" target="_blank">Honey-Pot</a>] 
							[<a href="http://lacnic.net/cgi-bin/lacnic/whois?lg=EN&query=<?php echo $ip ?>" target="_blank">Whois</a>] 
							[<a href="http://www.dnswatch.info/dns/ip-location?ip=<?php echo $ip ?>&submit=Locate+IP" target="_blank">Reverse-DNS</a>]
						</td>
						
					</tr>
					<?php 
					}
						$ip   = (count($data['ip']) == 1) ? $ip : '';
						$uuid = (count($data['ip']) == 1) ? $uuid : '';
						
					?>
				</tbody>
			</table>
		</div>
	
	</div>
	
	<div class='container-right'>

		<div class='view'>
			<div id='if_loading_<?php echo $origin ?>' style="position:absolute;z-index:100;top:0px;left:0px;width:100%;height:100%;background:#fefefe;">
				<div class='loading_panel'>
					<div style='padding: 10px; overflow: hidden;'>
						<?php echo _("Loading asset details") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
					</div>
				</div>
			</div>
			
			
			<iframe id='if_detail_<?php echo $origin ?>' class='if_detail' name='if_detail_<?php echo $origin ?>' onload="$('#if_loading_<?php echo $origin ?>').hide()" src='extra/host_info.php?prefix=<?php echo $origin ?>&ip=<?php echo $ip ?>&id=<?php echo $uuid ?>' height='400px' width='100%' frameborder=0 marginwidth='0' marginheight='0'></iframe>
		</div>
		
	</div>
	
</div>


<?php
$db->close();

$geoloc->close();

if (is_object($geoloc_org))
{
    $geoloc_org->close();
}
?>