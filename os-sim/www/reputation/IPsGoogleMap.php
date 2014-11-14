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


ini_set('memory_limit', '1024M');
set_time_limit(300);

require_once 'av_init.php';
Session::logcheck("dashboard-menu", "IPReputation");


require_once 'classes/Reputation.inc';


$act  = GET('act');
if (empty($act)) $act = "All";
$type = intval(GET('type'));

ossim_valid($act, OSS_INPUT,OSS_NULLABLE, 'illegal: Action');

if (ossim_error()) 
{
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo _("IP Reputation")?></title>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<!--<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>-->
	<script type="text/javascript" src=" https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	
	<script type="text/javascript">
    
		var script = '<script type="text/javascript" src="../js/markerclusterer.js"><' + '/script>';
		document.write(script);
		
		if ( typeof(google) != 'undefined' && google != null )
		{
			<?php
			$Reputation = new Reputation();

			if ( !$Reputation->existReputation() ) {
				exit();
			}
			
			$nodes = array();

			list($ips,$cou,$order,$total) = $Reputation->get_data($type,$act);
			session_write_close();

			foreach ($ips as $activity => $ip_data) if ($activity==$act || $act=="All")
			{
				foreach ($ip_data as $ip => $latlng) {
					if(preg_match("/-?\d+(\.\d+)?,-?\d+(\.\d+)?/",$latlng)) {
						$tmp = explode(",", $latlng);
						$node = "{ ip: '$ip [$activity]', lat: '".$tmp[0]."', lng: '".$tmp[1]."'}";
						$nodes[$ip] = $node;
					} 
				}
			}
			?>
			
			var points = [ <?php echo implode(",",$nodes) ?> ];

			function init_map() {
				var map = new google.maps.Map(document.getElementById("map"));
				map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
				map.setOptions({
					navigationControl: true,
					navigationControlOptions: { style: google.maps.NavigationControlStyle.ZOOM_PAN }
				});
				var zoom = 3;
				var pos = new google.maps.LatLng(37.1833,-3.6141);
				map.setCenter(pos);	
				map.setZoom(zoom);
				
				var markers = [];
				for (i in points) {
					var p = new google.maps.LatLng(points[i].lat, points[i].lng);
					var marker = new google.maps.Marker({
						position: p,
						title: points[i].ip
					});
					markers.push(marker);
				}
				
				var mcOptions = {gridSize: 80, maxZoom: 15};
				var markerCluster = new MarkerClusterer(map, markers, mcOptions);
				
				<?php 
				if ( count($nodes) < 1 ) 
				{ 
					?>
					var marker = new google.maps.Marker({'position': pos});
					google.maps.event.addListener(marker,'click',function(){
						var infoWin = new google.maps.InfoWindow({
							content: "<font style='font-family:arial;font-size:14px'><?php echo _("No external hosts found") ?></font>",
							position: pos
						});
					});
					<?php 
				}
				?>
			}
		}	
		
		$(document).ready(function(){
			
			if ( typeof(google) != 'undefined' && google != null ){
				init_map();
			}
			else
			{
				var config_nt = { 
					content: '<?php echo _("Feature not available, you need Internet connection")?>.', 
					options: {
						type:'nf_warning',
						cancel_button: false
					},
					style: 'width: 80%; margin: 150px auto; padding: 5px 0px; text-align: center;'
				};
			
				nt = new Notification('nt_map',config_nt);
				
				$('#map').html(nt.show());	
			}
			
			if (typeof(parent.show_map)=='function') {
				parent.show_map();
			}
		});
	</script>
	
	<style type='text/css'>
	body, html {
		height:100%;
		width:100%;
		margin:0px;
		padding:0px;
	}
	</style>
</head>

<body>
	<div id="map" style="width: 100%; height: 100%"></div>
</body>

</html>