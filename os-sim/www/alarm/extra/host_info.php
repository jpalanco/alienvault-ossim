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

$h_id         = GET('id');
$h_ip         = GET('ip');
$prefix       = GET('prefix');

ossim_valid($h_id,   	OSS_HEX,OSS_NULLABLE,      		'illegal:' . _("Asset ID"));
ossim_valid($h_ip,  	OSS_IP_ADDR_0, OSS_NULLABLE,    'illegal:' . _("Ip"));
ossim_valid($prefix,	'src','dst',         			'illegal:' . _("Prefix"));

if (ossim_error())
{ 
    die(ossim_error());   
}  


$gloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');


$data = $_SESSION['_alarm_stats'][$prefix];

/* connect to db */
$db     = new ossim_db(TRUE);
$conn   = $db->connect();

$h_obj  = Asset_host::get_object($conn, $h_id, TRUE);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo gettext("OSSIM Framework");?></title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>

	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	
	<!-- Google Maps: -->
	<script type="text/javascript" src="https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	<script type="text/javascript" src="/ossim/js/av_map.js.php"></script>    

	<style type="text/css">
		
		.ui-corner-tl 
		{ 
		    -moz-border-radius-topleft: 0px; 
		    -webkit-border-top-left-radius: 0px; 
		}
		
		.ui-corner-tr 
		{ 
    		-moz-border-radius-topright: 0px; 
    		-webkit-border-top-right-radius: 0px; 
		}
		
		.ui-corner-bl 
		{ 
    		-moz-border-radius-bottomleft: 0px; 
    		-webkit-border-bottom-left-radius: 0px; 
		}
		
		.ui-corner-br 
		{ 
    		-moz-border-radius-bottomright: 0px; 
    		-webkit-border-bottom-right-radius: 0px; 
		}
		
		.ui-corner-top 
		{ 
            -moz-border-radius-topleft: 0px; 
            -webkit-border-top-left-radius: 0px; 
            -moz-border-radius-topright: 0px; 
            -webkit-border-top-right-radius: 0px; 
		}
		
		.ui-corner-bottom 
		{ 
    		-moz-border-radius-bottomleft: 0px; 
    		-webkit-border-bottom-left-radius: 0px; 
    		-moz-border-radius-bottomright: 0px; 
    		-webkit-border-bottom-right-radius: 0px; 
		}
		
		.ui-corner-right 
		{  
    		-moz-border-radius-topright: 0px; 
    		-webkit-border-top-right-radius: 0px; 
    		-moz-border-radius-bottomright: 0px; 
    		-webkit-border-bottom-right-radius: 0px; 
		}
		
		.ui-corner-left 
		{ 
    		-moz-border-radius-topleft: 0px; 
    		-webkit-border-top-left-radius: 0px; 
    		-moz-border-radius-bottomleft: 0px; 
    		-webkit-border-bottom-left-radius: 0px; 
		}
		
		.ui-corner-all 
		{ 
    		-moz-border-radius: 0px; 
    		-webkit-border-radius: 0px; 
		}

		.ui-tabs .ui-tabs-panel 
		{
			padding: 0px;
		}
		
		.ui-widget 
		{
			font-family: Arial;
			color: #333;
		}
		
		body
		{
			width:99%;
			height:100%;
			overflow-x:hidden;
		}
		
		iframe
		{
			width:100%;
			height:350px;
			border: none;

		}		
		
		div.accordion .ui-widget-content, div.accordion .ui-state-default, div.accordion .ui-state-active 
		{
			border: none;
		}
		
		#tabs
		{
			height:95%;
			width:97%;
			position:fixed;
			overflow:hidden;
		}
		
		#c_map
		{
    		width: 95%; 
    		height: 320px;
    		margin: 15px auto;
		}
		
	</style>
	
	<script type='text/javascript'> 	
	
    	function set_tab(tab) 
    	{   
        	$("#tabs").tabs("select" , "tabs-"+tab);
    	}

    	$(document).ready(function() 
    	{
    		$("#tabs").tabs();
    		
    		var m_index = 0;
    		av_map = new Av_map('c_map');
    		
    		if(Av_map.is_map_available())
            {
                <?php                                                
                if (is_array($data['ip']) && !empty($data['ip']))
                {
                    $ips      = array_keys($data['ip']);
                    $num_ips  = count($ips);
                    
                    $first_ip = $ips[0];
                                      
                    $record = $gloc->get_location_from_file($first_ip);
                    $lat = $record->latitude;
                    $lng = $record->longitude;                 
                    
                    if ($num_ips > 1)
                    {
                        ?>
                        av_map.set_location('', '');                                                               
                        <?php
                    }
                    else
                    {
                        ?>
                        av_map.set_location('<?php echo $lat?>', '<?php echo $lng?>');                            
                        <?php                        
                    }                                        
                    
                    ?>
                    av_map.draw_map();
                                                                                  
                    av_map.map.setOptions({draggable: false}); 
                    <?php                                      
                                                
                    //Add new marker by Ip
                    foreach($ips as $ip) 
                    {                            
                        $record = $gloc->get_location_from_file($ip);
                        $lat = $record->latitude;
                        $lng = $record->longitude; 
                        
                        if ($lat != '' && $lng != '')
                        {
                            ?>                                                                                
                            av_map.add_marker('<?php echo $lat?>', '<?php echo $lng?>');
    						av_map.map.setZoom(4);
    						
    						m_index = Object.keys(av_map.markers).length - 1;
    						av_map.markers[m_index].setDraggable(false);
    						
    						<?php
    						if($ip == $h_ip)
    						{
                                ?>
                                av_map.markers[m_index].setIcon('../style/img/yellow-dot.png');
                                <?php
    						}    
                        }                     		   					
                    }                           
                }
                else
                {
                    //No IPs with geolocation
                    ?>                         
                    av_map.set_zoom(2);           
                    av_map.draw_map();                 
                    av_map.map.setOptions({draggable: false});                 
                    <?php                    
                }            
                ?>                               
    		}
    		else
    		{
    		    av_map.draw_warning();
    		}    							
    	});
	
	</script>
	
</head>

<body>
	
	<div id="tabs">

    	<?php
		if(is_object($h_obj))
		{
    		?>    
    		<ul>
    			<li><a href="#tabs-1"><?php echo _('Location') ?></a></li>
    			<li><a href="#tabs-2"><?php echo _('Vulnerabilities') ?></a></li>
    			<li><a href="#tabs-3"><?php echo _('Host Properties') ?></a></li>
    			<li><a href="#tabs-4"><?php echo _('Notes') ?></a></li>
    		</ul>
    		
    		<div id="tabs-1">
    			 <div id="c_map"></div>
    		</div>
    		
    		<div id="tabs-2">
    			<iframe src="host_vulns.php?ip=<?php echo $h_ip ?>" marginwidth='0' marginheight='0'></iframe>
    		</div>
    		
    		
    		<div id="tabs-3">
    			<iframe src="host_properties.php?id=<?php echo $h_id ?>" marginwidth='0' marginheight='0'></iframe>
    		</div>
    		
    		<div id="tabs-4" style='position:relative;'>
    			<iframe src="../../asset_details/ajax/view_notes.php?id=<?php echo $h_id ?>&type=host" marginwidth='0' marginheight='0'></iframe>
    		</div>
		<?php
		} 
		else 
		{
    		?>    
    		<ul>
    			<li><a href="#tabs-1"><?php echo _('Location') ?></a></li>
    		</ul>
    		
    		<div id="tabs-1">
    			 <div id="c_map"></div>
    		</div>    				
    		<?php
		}
		?>
		
	</div>

</body>

</html>
<?php
$gloc->close();
$db->close();
?>