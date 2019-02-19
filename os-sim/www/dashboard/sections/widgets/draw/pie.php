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

Session::logcheck("dashboard-menu", "ControlPanelExecutive");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<?php
        if (isset($widget_refresh) && $widget_refresh != 0)
        {
            echo('<meta http-equiv="refresh" content="'.$widget_refresh.'">');
        }
        ?>
        <title><?php echo _("Pie Chart")?></title>

        <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => '/js/jqplot/jquery.jqplot.css',      'def_path' => FALSE),
            array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jqplot/jquery-1.4.2.min.js',                         'def_path' => TRUE),
            array('src' => 'jqplot/jquery.jqplot.min.js',                        'def_path' => TRUE),
            array('src' => '/dashboard/js/widget.js.php',                        'def_path' => FALSE),
            array('src' => 'jqplot/plugins/jqplot.pieRenderer.js',               'def_path' => TRUE),
            array('src' => 'jqplot/plugins/jqplot.enhancedLegendRenderer.js',    'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'js');

        ?>
          
		<!-- END: load jqplot -->

        <style type="text/css">            
            
			#chart .jqplot-point-label 
			{
				border: 1.5px solid #aaaaaa;
				padding: 1px 3px;
				background-color: #eeccdd;			  
			}     
			
			#chart .jqplot-data-label 
			{
				color: 	#707070;
				font-size: 18px !important;
			} 
						
			table .jqplot-table-legend
			{		
				border-spacing:1px !important;
				border:none;
				
			}
			
			td.jqplot-legend-title
            {
                padding-right: 5px;
                text-align: left;
                font-size: 12px;
            	color: #666666;
            	cursor: pointer;
            }
            
			
        </style>
        
        <script class="code" type="text/javascript">
		        
            var tooltip_legend = <?php echo $tooltip ?>;
			var links = <?php echo $links ?>;

			function myClickHandler(ev, gridpos, datapos, neighbor, plot) 
			{
				if(neighbor != null) 
				{
					url = links[neighbor.pointIndex];
					
					if (typeof(url)!='undefined' && url!='') 
					{
						if (typeof top.av_menu.load_content == 'function')
						{
    						top.av_menu.load_content(url);
						}
						else
						{
    						top.frames['main'].location.href = url;
						}						
					}
				}
			}
			
			var isShowing = -1;
					
			function myMoveHandler(ev, gridpos, datapos, neighbor, plot) 
			{
				if (neighbor != null) 
				{
					if (neighbor.pointIndex != isShowing) 
					{
    					isShowing = neighbor.pointIndex;
    					
    					var tooltip  = tooltip_legend[isShowing] ? tooltip_legend[isShowing] : neighbor.data[0];
						    tooltip += '<br/>';
                            tooltip += '<strong>(' + format_dot_number(neighbor.data[1]) +  ')</strong>';
						
						jqplot_show_tooltip($('#myToolTip'), tooltip, ev, plot);						
					}
				}
				else
				{
    				myLeaveHandler();
				}
			}
			
			function myLeaveHandler()
    		{
        		$('#myToolTip').hide().empty();
                isShowing = -1;
    		}
    									
			$(document).ready(function()
			{		
				$.jqplot.config.enablePlugins = true;                        

				$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
				$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
				
				s1 = <?php echo $data ?>;
                        
				plot1 = $.jqplot('chart', [s1], 
				{
					grid: 
					{
						drawBorder: false, 
						drawGridlines: false,
						background: 'transparent',
						shadow:false
					},
					<?php if ($colors!="") { ?>seriesColors: [ <?php echo $colors ?> ], <?php } ?>

					seriesDefaults:
					{
						padding:0,
						renderer:$.jqplot.PieRenderer,
						rendererOptions: 
						{
								showDataLabels: true,
								dataLabelThreshold: 10,
								dataLabelPositionFactor: 1.18,
								sliceMargin: 0,
								shadow:false
						}                                                           
					},
					legend:
					{
						renderer: $.jqplot.EnhancedLegendRenderer,
						rendererOptions: 
						{
    						numberColumns: <?php echo $legend_columns ?>
				        },
						show: true, 
						location:  's',
						placement: 'outsideGrid',
						yoffset: 0
					}
				});

				$('#chart').append('<div id="myToolTip"></div>');
				$('#chart').mouseleave(myLeaveHandler);
				
				
				/* Redirect when the legend item is clicked */
				$('td.jqplot-legend-title').click(function()
				{
    			    var index = $(this).data('elem_index');   
    			    var url   = links[index];
					
					if (typeof(url) != 'undefined' && url != '') 
					{
						if (typeof top.av_menu.load_content == 'function')
						{
    						top.av_menu.load_content(url);
						}
						else
						{
    						top.frames['main'].location.href = url;
						}
					} 				
				});
				
				/* Tooltip when the legend is hovered */
				$('td.jqplot-legend-title').mouseenter(function()
				{
    			    var index    = $(this).data('elem_index');   
                    var elem     = plot1.data[0][index];
                    
    			    if (elem == undefined || elem[0] == undefined || elem[1] == undefined)
    			    {
        			    return false;
    			    }
    			        	
                    var tooltip  = (tooltip_legend[index]) ? tooltip_legend[index] : elem[0];     		    
                        tooltip += '<br/>';
                        tooltip += '<strong>(' + format_dot_number(elem[1]) +  ')</strong>';
                    
                    $('#myToolTip').html(tooltip).css(
                	{
                		"max-width": Math.round(plot1._width/1.5) + 'px',
                		"left": 10, 
                		"top": 20
                	}).show();         
                    
				}).mouseleave(function()
				{
                    $('#myToolTip').hide().empty();
                });
				
				

			});
        </script>
    
	</head>
  
	<body style="overflow:hidden" scroll="no">
	
		<table class='container' style='width:100%; height:<?php echo $height ?>px;'>
			<tr>
				<td align='center' valign='top'>
					<div id='chart' class='pie d_chart_container' style='height:<?php echo $height ?>px;'></div>
				</td>
			</tr>
		</table>

	</body>
</html>
