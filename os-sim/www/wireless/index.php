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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tree.css" />
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dynatree.js"></script>  
    <script type="text/javascript" src="/ossim/js/jquery.cookie.js"></script>
    <script type="text/javascript" src="/ossim/js/greybox.js"></script> 
       
    <?php require "../host_report_menu.php" ?>
 
    <script type="text/javascript">
	       	
    	var last_url = "";
    	var loading  = '<img src="../pixmaps/loading.gif" width="13" border="0" align="absmiddle"><span style="margin-left: 3px;"><?php echo _("Loading xmls")?>...</span>';
    	   	
    	function postload() 
    	{
    		var lnk = $('#loadme').attr('lnk');
    		
    		if (lnk) 
    		{
    		    load_data(lnk);
    		}
    	}
    	
    	function showhide(layer,img)
    	{
    		$(layer).toggle();
    		
    		if ($(img).attr('src').match(/plus/))
    		{
    			$(img).attr('src','../pixmaps/minus-small.png')
    		}
    		else
    		{
    			$(img).attr('src','../pixmaps/plus-small.png')
    		}
    	}
    	    		
    	function load_data(url) 
    	{
    		last_url = url;
    		
    		if (url.match(/_pdf/)) 
    		{
    			window.open(url, '', '');
    		} 
    		else 
    		{
    			$('#data').hide();
    			$('#loading').show();
    			$.ajax({
    				type: "GET",
    				url: url,
    				success: function(msg) {
    					
    					var width = $('#c_data').innerWidth();
    					    					    					    
    					if (!isNaN(width))
    					{
                            width = parseInt(width);
                                                                                 
                            $('#data').css('width', width);                         
    					}
    					 					
    					$('#data').html(msg);    					    					
    					
    					$('#loading').hide();
    					$('#data').show();
    					
    					activate_table(url);
    				}
    			});
    		}
    	}
	
    	function activate_table(url) 
    	{
    		$("a.greybox").click(function(){    			
    			
    			var t = this.title || $(this).text() || this.href;
    			
    			GB_show(t,this.href,350,"85%");
    			
    			return false;
    		});   
    		    		    		
    		if (url.match(/networks/)) 
    		{        		
        		var aa_sorting = [[ 0, "asc" ]];
        		
        		var ao_columns = [
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": false }
    			];
    			
    		}
    		else if (url.match(/clients/)) 
    		{
        		var aa_sorting = [[ 0, "asc" ]];
        		
        		var ao_columns = [
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    			    { "bSortable": true }
    			];        		
    		}
    		else if (url.match(/sensors/)) 
    		{
        		var aa_sorting = [[ 0, "asc" ]];
        		
        		var ao_columns = [
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },    				
    				{ "bSortable": false }
    			];            		
    		}
    		else 
    		{
        		var aa_sorting = [[ 0, "asc" ]];
        		
        		var ao_columns = [
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },
    				{ "bSortable": true },    				
    				{ "bSortable": true }
    			];            		
    		}    	
    		
    		$('#results').dataTable( {
    			"iDisplayLength": 10,
    			"sPaginationType": "full_numbers",
    			"bPaginate": true,
    			"bLengthChange": false,
    			"bFilter": false,
    			"bSort": true,
    			"bInfo": true,
    			"bJQueryUI": true,    			     			
        		"aaSorting": aa_sorting,
    			"aoColumns": ao_columns,    			
    			"oLanguage" : {
    				"sProcessing": "<?php echo _('Processing') ?>...",
    				"sLengthMenu": "Show _MENU_ entries",
    				"sZeroRecords": "<?php echo _('No matching records found') ?>",
    				"sEmptyTable": "<?php echo _('No entries found') ?>",
    				"sLoadingRecords": "<?php echo _('Loading') ?>...",
    				"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
    				"sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
    				"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
    				"sInfoPostFix": "",
    				"sInfoThousands": ",",
    				"sSearch": "<?php echo _('Search') ?>:",
    				"sUrl": "",
    				"oPaginate": {
                        "sFirst":    "<?php echo _('First') ?>",
                        "sPrevious": "<?php echo _('Previous') ?>",
                        "sNext":     "<?php echo _('Next') ?>",
                        "sLast":     "<?php echo _('Last') ?>"
                    }
    			}
    		});

    		$('.tiptip').tipTip({content: $(this).attr('data-title')});
    		
    		load_contextmenu();
    	}
    
	function changeview(si, param) 
	{
        load_data('networks.php?index='+si+'&order=ssid&'+param)
    }
    
	function changeviewc(si, param) 
	{
        load_data('clients.php?index='+si+'&'+param)
    }
	
	function GB_onclose(url) 
	{	
		if (url.match(/_edit/)) 
		{                       
            // launch default active node
            if (last_url) 
            {
                load_data(last_url);
            }
		}
		else if (url.match(/setup/))
		{
    		document.location.reload();    		
		}
	}
             
	function browsexml(sensor,date) 
	{
        $('#browsexml').html(loading);
        $.ajax({
            type: "GET",
            data: { sensor: sensor, date: date },
            url: "browse_sensor.php",
            success: function(msg) {
                $('#browsexml').html(msg);
                layer = null;
                nodetree = null;
            }
        });
    }
    
	var layer    = null;
    var nodetree = null;
    var i = 1;
    
	function viewxml(file, sensor) 
	{
        if (nodetree!=null) 
        {
            nodetree.removeChildren();
            
            $(layer).remove();
        }
        
		layer = '#srctree'+i;
        $('#wcontainer').append('<div id="srctree'+i+'" style="width:100%"></div>');
        $(layer).dynatree({
            initAjax: { url: "view_xml.php", data: { sensor: sensor, file: file } },
            clickFolderMode: 2,
            onActivate: function(dtnode) {},
            onDeactivate: function(dtnode) {}
        });
        
		nodetree = $(layer).dynatree("getRoot");
        i=i+1
    }
    </script>
</head>

<body>
<?php

//Local menu		      
include_once '../local_menu.php';

//Sensor list with perms
require_once 'Wireless.inc';

$db                        = new ossim_db();
$conn                      = $db->connect();
$locations                 = Wireless::get_locations($conn);
 
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
$max          = count($locations);


?>
<table id="t_container">
	
<?php
if ($max == 0) 
{
	?>
	<tr>
		<td>
		<?php
		$no_location_txt = "<div style='padding: 10px; font-size: 13px;'>"._("No locations have been configured").".<br/>"._("Please click on Setup (upper-side) to define a new location")."</div>";
	
		echo "<div style='margin: 100px auto;'>".ossim_error($no_location_txt, AV_INFO)."</div>";  
		?>	
		</td>
	</tr>
	<?php
}
else
{
    ?>
    <tr>
		<td id='t_c_tree'>
            
            <div class='sec_title' style='padding-bottom: 4px;'><?php echo _('Locations')?></div>
            
            <?php
            if ($max > 0)
			{
				foreach ($ossim_sensors as $sensor) 
				{
					$sensors_list[] = $sensor['ip'];
				}
					
				$i     = 0; 
				$first = 0;
				$img3  = "<img src='../pixmaps/theme/ltL_nes.gif' align='absmiddle' border='0'/>";
				$img4  = "<img src='../pixmaps/theme/ltL_ne.gif' align='absmiddle' border='0'/>";
				$plus  = "<img src='../pixmaps/plus-small.png' align='absmiddle' border='0' id='imgX'>";
				$gray  = "<img src='../pixmaps/plus-small-gray.png' align='absmiddle' border='0'/>";
				$minus = "<img src='../pixmaps/minus-small.png' align='absmiddle' border='0' id='imgX'/>";
				$si    = 0; 
			
				unset($_SESSION["sensors"]);

				foreach ($locations as $data) 
				{
					$i++;
					$expand = ($i==1) ? "id='loadme'" : "";
					
					//Filter only allowed sensors
					$valid_sensors = array();
					
					foreach ($data["sensors"] as $s) 
					{
						if (in_array($s["ip"],$sensors_list))
						{
							$valid_sensors[] = $s["ip"];
						}
					}
					
					if (count($valid_sensors) > 0) 
					{
						$_SESSION["sensors"][] = implode(",",$valid_sensors);
						$first++;
						
						$active = ($first==1) ? "block" : "none";
						$img    = ($first==1) ? str_replace("X", $i, $minus) : str_replace("X", $i, $plus); 									
						
						echo "<div class='tree_location_b'><a href='javascript:;' onclick=\"showhide('#cell$i','#img$i')\">$img</a><img src='../pixmaps/theme/net_group.png' align='absmiddle'>&nbsp;".$data["location"]."</div>\n";
						echo "<div id='cell$i' style='display:$active'><div style='padding-left:22px'>$img3<img src='../pixmaps/theme/wifi.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('networks.php?index=$si&order=ssid')\" lnk='networks.php?order=ssid' class='tlink' $expand>"._("Networks")."</a></div>\n";
						echo "<div style='padding-left:22px'>$img3<img src='../pixmaps/theme/net.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('clients.php?index=$si')\" class='tlink'>"._("Clients")."</a></div>\n";
						echo "<div style='padding-left:22px'>$img3<img src='../pixmaps/theme/host.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('sensors.php?index=$si&location=".urlencode(base64_encode($data['location']))."')\" class='tlink'>"._("Sensors")."</a></div>\n";
						echo "<div style='padding-left:22px'>$img3<img src='../pixmaps/theme/report.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('events.php?index=$si')\" class='tlink'>"._("Events")."</a></div>\n";
						echo "<div style='padding-left:22px'>$img4<img src='../pixmaps/monitor.png' align='absmiddle'>&nbsp;<span class='tlink'>"._("Reports")."</span></div>\n";
						echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"networks_pdf.php?index=$si&order=ssid&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Networks")."</a></div>\n";
						echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"aps_pdf.php?index=$si&type=1&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Cloaked Networks having uncloaked APs")."</a></div>\n";
						echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"aps_pdf.php?index=$si&type=2&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Encrypted Networks having unencrypted APs")."</a></div>\n";
						echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"aps_pdf.php?index=$si&type=3&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Networks using weak encryption")."</a></div>\n";
						echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"clients_pdf.php?index=$si&type=3&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Suspicious clients")."</a></div>\n";
						echo "</div>"; 
						
						$si++;    									
					} 
					else
					{
						echo "<div class='tree_location_b'>$gray<img src='../pixmaps/theme/net_group.png' align='absmiddle'>&nbsp;".$data["location"]."</div>\n";
					}
				}
			}	          
            ?>		
		</td>
		
		
		<td id='t_c_info'>
			<div id='c_data'>
    			<span id="loading" style="display:none;text-align:center; margin-top: 150px;">
    				<img src="../pixmaps/loading.gif" width="13" border='0' align="absmiddle"><span style='margin-left: 3px;'><?php echo _("Loading data")?>...</span>
    			</span>
    
    			<div id="data"></div>
			</div>
		</td>  
    <?php
}
?>    	
</table>    				  

</body>
</html>

