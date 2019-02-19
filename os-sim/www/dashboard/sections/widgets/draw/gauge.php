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

Session::logcheck('dashboard-menu', 'ControlPanelExecutive');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<?php
	if(isset($widget_refresh) && $widget_refresh!=0)
		echo('<meta http-equiv="refresh" content="'.$widget_refresh.'">');
	?>
	<title><?php echo _("Threat Level")?></title>
	
	<?php
	//CSS Files
	$_files = array(
        array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
	    array('src' => 'dashboard/overview/gauge.css',      'def_path' => TRUE),
	    array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
	);
	
	Util::print_include_files($_files, 'css');
	
	
	//JS Files
	$_files = array(
	    array('src' => 'jquery.min.js', 'def_path' => TRUE)
	);
	
	Util::print_include_files($_files, 'js');
	?>
    
	<script type="text/javascript"> 

	// Link to Risk Metrics
    	function click_handler()
    	{
        	var url = "<?php echo $link ?>";
        	
        	if(url != '')
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

    	// Constants
    	var minCord  = {x: -60, y: -60};
    	var maxCord  = {x: 60, y: -60};
    	var radius   = 90;
    	// Offset to the center of the circle in the div
    	var center_x = 96;
    	var center_y = 94;
    	    
    	// Some Calculations
    	var startAngle = (6.4831 + Math.atan2(minCord.y, minCord.x));
    	var endAngle = Math.atan2(maxCord.y, maxCord.x);
    	var degreesSweep = (-endAngle) + startAngle;

    	// Get the position of the point in the circle
    	function positionOnArc(magnitude)
    	{
        	// Min and max adjustment patch to the background image
        	// The calculation of the arc doesn't fit exactly to the circle colors
        	// This is the easiest solution
        	if (magnitude == 0)
        	{
            	magnitude = -5;
        	}
        	else if (magnitude == 100)
        	{
            	magnitude = 108;
        	}
        	
    	    var numDegrees = degreesSweep * (magnitude/100.0);
    	    var angle = (startAngle - numDegrees);
    	    var posX = radius * Math.cos(angle);
    	    var posY = radius * Math.sin(angle);
    	    
    	    return [posX, posY];
    	}  

    // Put the point in position
    	function updatePlot(value)
    	{        
    	    var data     = positionOnArc(value);
    	    var x_offset = data[0];
    	    var y_offset = data[1];
    	    var x        = center_x + x_offset;
    	    var y        = center_y - y_offset;

    	    $('#gauge_point').css('left', x);
    	    $('#gauge_point').css('top',  y);
    	}
    	
    $(document).ready(function()
    {
        updatePlot(<?php echo $data_angle ?>);

        $('#gauge_holder').click(function() {
            click_handler();
        });
    });
	</script>
	
	</head>
	
	<body>
	
	<div class='gauge_container'>
	
	<div class='gauge_cell'>
	
	<div id='gauge_holder'>
	    
	    <div id='gauge_point'></div>
	    
        <div id='gauge_label_container'>
            
            <div id='gauge_label_number'><?php echo $data ?></div>
            
            <div id='gauge_label_text'>
                <?php
                // Text cases by value
                if ($data > ($min + 4*$v))
                {
                    $label = _('VERY HIGH');
                }
                elseif ($data > ($min + 3*$v))
                {
                    $label = _('HIGH');
                }
                elseif ($data > ($min + 2*$v))
                {
                    $label = _('ELEVATED');
                }
                elseif ($data > ($min + $v))
                {
                    $label = _('PRECAUTION');
                }
                else
                {
                    $label = _('LOW');
                }
                
                echo $label;
                
                ?>
            </div>
        </div>
    </div>
    
    </div>
    
    </div>

    </body>
</html>

<?php
/* End of file gauge.php */
/* Location: ./dashboard/sections/widgets/draw/gauge.php */
