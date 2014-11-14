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
<html lang="en">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<?php
		if(isset($widget_refresh) && $widget_refresh!=0)
			echo('<meta http-equiv="refresh" content="'.$widget_refresh.'">');
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
            }
			
        </style>
        
        <script class="code" type="text/javascript">
		
        
			var links = [<?php echo $links; ?>];

			function format_dot_number(num)
			{	
				var num = num + "";
				var i   = num.length-3;
				
				while (i>0)
				{
					num =  num.substring(0,i)+"."+num.substring(i);
					i   -= 3;
				}
				
				return num;
			}
	

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
				if (neighbor == null)
				{
					$('#myToolTip').hide().empty();
					
					isShowing = -1;
					
				}

				if (neighbor != null) 
				{
					if (neighbor.pointIndex!=isShowing) 
					{
						var class_name = $('#chart').attr('class');

						var text = neighbor.data[0] + "<br><b>" + format_dot_number(neighbor.data[1]) + "</b>";
						$('#myToolTip').html(text).css({left:gridpos.x, top:gridpos.y-5}).show();
						isShowing = neighbor.pointIndex
					}
				}
			}
							
			$(document).ready(function()
			{		
				$.jqplot.config.enablePlugins = true;                        

				$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMoveHandler]);
				$.jqplot.eventListenerHooks.push(['jqplotClick', myClickHandler]); 
				
				
				s1 = [<?php echo $data; ?>];
                        
				plot1 = $.jqplot('chart', [s1], 
				{
					grid: 
					{
						drawBorder: false, 
						drawGridlines: false,
						background: 'transparent',
						shadow:false
					},
					<?php if ($colors!="") { ?>seriesColors: [ <?php echo $colors; ?> ], <?php } ?>

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