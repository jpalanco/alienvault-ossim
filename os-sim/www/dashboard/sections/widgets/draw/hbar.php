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
    if (isset($widget_refresh) && $widget_refresh != 0)
    {
        echo('<meta http-equiv="refresh" content="'.$widget_refresh.'">');
    }
    ?>
    <title><?php echo _("Bar Chart")?></title>

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
        array('src' => 'jqplot/jquery-1.4.2.min.js',                            'def_path' => TRUE),
        array('src' => 'jqplot/jquery.jqplot.min.js',                           'def_path' => TRUE),
        array('src' => '/dashboard/js/widget.js.php',                           'def_path' => FALSE),
        array('src' => 'jqplot/plugins/jqplot.barRenderer.js',                  'def_path' => TRUE),
        array('src' => 'jqplot/plugins/jqplot.categoryAxisRenderer.min.js',     'def_path' => TRUE),
        array('src' => 'jqplot/plugins/jqplot.pointLabels.min.js',              'def_path' => TRUE),
        array('src' => 'jqplot/plugins/jqplot.canvasTextRenderer.js',           'def_path' => TRUE),
        array('src' => 'jqplot/plugins/jqplot.canvasAxisTickRenderer.js',       'def_path' => TRUE),
        array('src' => 'jqplot/plugins/jqplot.enhancedLegendRenderer.js',       'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'js');

    ?>

    <style type="text/css">                
		#chart .jqplot-point-label 
		{
		  border: 1.5px solid #aaaaaa;
		  padding: 1px 3px;
		  background-color: #eeccdd;
		} 
		
		.jqplot-yaxis
		{
    		text-transform: uppercase;
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
				if (neighbor.pointIndex!=isShowing) 
				{
    				isShowing = neighbor.pointIndex
    				
    				try
                    {
                        var index   = neighbor.data[1] - 1;
                        var tooltip = tooltip_legend[index] ? tooltip_legend[index] : plot.axes.yaxis.ticks[index];
                        
                        tooltip += '<br/>';
                        tooltip +=  '<strong>(' + format_dot_number(neighbor.data[0]) +  ')</strong>';
                    }
                    catch(Err)
                    {
                        var tooltip = '';
                    }
                    
    				jqplot_show_tooltip($('#myToolTip'), tooltip, ev, plot);
					
				}
			}
			else
			{
    			myLeaveHandler()
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
			
			line1 = <?php echo $data ?>;
			plot1 = $.jqplot('chart', [line1], 
			{					
				seriesDefaults:
				{
					renderer:$.jqplot.BarRenderer, 
					rendererOptions:
					{
						barDirection:'horizontal',
						barPadding:2,
						barMargin:2,
						varyBarColor : true,
						shadowAlpha:0
					}
				},
				series:
				[
				   { 
				       pointLabels:
				       {
				           show: false 
				       }, 
				       label: "<?php echo $serie ?>", 
				       shadow: false, 
				       renderer: $.jqplot.BarRenderer 
				   }
				],	
						        
				<?php 
				if ($colors != "") 
				{ 
				?>
				   seriesColors: [<?php echo $colors ?>], 
				   
			    <? 
			    } 
			    ?>      
			                    
			    grid:
				{
				    background: 'transparent',
				    shadow: false,
				    borderWidth: 0,
				    borderColor: '#EEEEEE',
				    gridLineColor: '#EEEEEE',
				    bottomMargin: '50px'
				},
				axes:
				{
					yaxis:
					{
						renderer:$.jqplot.CategoryAxisRenderer,
						ticks: <?php echo $label ?>
					}, 
					xaxis:
					{
						min:0, 
						tickOptions:
						{
    						formatString:'%d'
    				    }
    				}
				}
			});
			

			$('#chart').append('<div id="myToolTip"></div>');
			$('#chart').mouseleave(myLeaveHandler);
				
		});
		
    </script>

</head>

<body style="overflow:hidden" scroll="no">
	<table class='transparent container' style='width:100%; height:<?php echo $height ?>px;'>
		<tr>
			<td align='center' valign='middle' >
				<div id='chart' class='d_chart_container' style='height:<?php echo $height ?>px;'></div>
			</td>
		</tr>
	</table>
</body>

</html>
