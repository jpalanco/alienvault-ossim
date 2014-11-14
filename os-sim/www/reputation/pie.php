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

$reputation = new Reputation();
$type       = intval(GET("type"));

//$rep_file = trim(`grep "reputation" /etc/ossim/server/config.xml | perl -npe 's/.*\"(.*)\".*/$1/'`);

if ($reputation->existReputation()) 
{
   ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - <?php echo gettext("IP reputation"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache">
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery-1.4.2.min.js"></script>
		<!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
		<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
		<link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
		<script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
		<script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
   
    	<style type="text/css">
    		
    		#chart .jqplot-point-label 
    		{
    		  border: 1.5px solid #aaaaaa;
    		  padding: 1px 3px;
    		  background-color: #eeccdd;
    		}
    		
    		.jqplot-data-label 
    		{
    			font-size: 12px;
    		}
    		
    		.jqplot-legend-title  
    		{
    			text-align: left !important;
    		}
    		
    		.jqplot-title 
    		{
    			background-color: rgba(200,200,200,0.1);
    		}
    		
    	</style>
        <?php
        list($ips, $cou, $order, $total) = $reputation->get_data($type);   
        
        $data  = array();
        $order = array_splice($order,0,10);
    	
    	foreach($order as $type => $ocurrences) 
    	{
    	   $data[] = "['$type [".Util::number_format_locale($ocurrences,0)."]',$ocurrences]";
    	}
    	
    	$data = implode(",", $data);   	
        ?>	
    	<script type='text/javascript'>
    	            		
    		function myClickHandler(ev, gridpos, datapos, neighbor, plot) 
    		{
                //mouseX = ev.pageX; mouseY = ev.pageY;
                if (neighbor) 
                {
            		//alert('x:' + neighbor.data[0] + ' y:' + neighbor.data[1] + ' pi:' + neighbor.pointIndex);
            		activity = neighbor.data[0].replace(/ \[.*/,'');
            		if (typeof(parent.change_act)=='function') 
            		{
            		    parent.change_act(activity);
            		}
        		}
            }
            	
    		$(document).ready(function(){
    					
    			$.jqplot.config.enablePlugins = true;
    			$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
    						
    			s1 = [<?php echo $data; ?>];			
    			
    			<?php											
    			if (!empty($data))
    			{
        			?>    			
        			plot1 = $.jqplot('chart', [s1], {
        				grid: {
        					drawBorder: false, 
        					drawGridlines: false,
        					background: 'rgba(255,255,255,0)',
        					shadow:false
        				},
        				<?php if ($colors!="") { ?>seriesColors: [ <?php echo $colors; ?> ], <?php } ?>
        				axesDefaults: {
        					
        				},
        				seriesDefaults:{
        		            padding:10,
        					renderer:$.jqplot.PieRenderer,
        					rendererOptions: {
        						diameter: '170',
        						showDataLabels: true,
        						dataLabels: "value",
        						dataLabelFormatString: '%d'
        					}								
        				},
        				
        				legend: {
        					show: true,
        					rendererOptions: {
        						numberCols: 2
        					},
        					location:'e'
        				}
        			});
        			<?php
    			}
    			else
    			{
        			?>    			
        			$('#chart').html('<div class="gray center" style="padding-top: 100px;"><?php echo _("No data available")?></div>');
        			<?php
    			}
    			?>			
    		});				
    	</script>
	</head> 
	  
	<body style="overflow:hidden" scroll="no">
		<div class='otx_p_container'>
            
            <div class='otx_p_left'>
			    <div class='otx_p_title otx_p_title_extrapadding'><?php echo _("General statistics")?></div>
				<table class='otx_table'>
    				<tr>
    				    <td class="ne"><?php echo _("Number of IPs in the database")?> </td>
    				    <td class="grb">&nbsp;<?php echo Util::number_format_locale($total, 0)?></td>
    				</tr>
    				<tr>
    				    <td class="ne"><?php echo _("Latest update")?></td>
    				    <td class="grb">&nbsp;<?php echo gmdate("Y-m-d H:i:s",filemtime($reputation->rep_file)+(3600*Util::get_timezone()))?></td>
    				</tr>
    			</table>
			</div>	
			
			<div class='otx_p_middle'>
				<div class='otx_p_title'><?php echo _("Malicious IPs by Activity")?></div>
				<div id="chart" style="width:400px; height:220px"></div>
			</div>
		
			<div class='otx_p_right'>
				<div class='otx_p_title otx_p_title_extrapadding'><?php echo _("Top 10 Countries")?></div>										
					<?php
					if (is_array($cou) && !empty($cou))
					{
                        ?>
                        <table class='otx_table'>	
                            <tr>
                                <td class="neb"><?php echo _("Country")?></td>
                                <td class="neb"><?php echo _("IPs #")?></td>
                            </tr>
                                <?php    						
                                $cou = array_splice($cou, 0, 10);			
                                foreach ($cou as $c => $value)
                                { 
                                    $info = explode(";", $c);
                                    $flag = '';
                                
                                    if ($info[1] != '') 
                                    {
                                        $flag = "<img src='../pixmaps/".($info[1]=="1x1" ? "" : "flags/") . strtolower($info[1]).".png' border='0' width='16' height='11' title='".$info[0]."'>&nbsp;";
                                    }
                                    ?>
                                    <tr>
                                        <td class="gr"><?php echo $flag . $info[0] ?></td>
                                        <td class="grb"><?php echo Util::number_format_locale($value,0); ?></td>
                                    </tr>
                                    <?php 
                                }
                                ?>
                            </table>
                            <?php 						
                        }
						else
						{
    						?>                                
                            <div class="gray center" style="padding-top: 100px;"><?php echo _("No data available")?></div>                              
    						<?php
						}
						?>				
                </div>       
           </div>
	</body>
	</html>
	<?php
	}
?>
