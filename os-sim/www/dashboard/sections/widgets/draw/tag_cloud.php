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
    
    
$min_font_size = 10;
$max_font_size = 35;
$maximum_count = max(array_values($data));
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
    if(isset($widget_refresh) && $widget_refresh!=0)
    {
        echo('<meta http-equiv="refresh" content="'.$widget_refresh.'">');
    }
	?>
	
	<title><?php echo _("Tag Cloud")?></title>
	

	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => 'tipTip.css',                        'def_path' => TRUE),
            array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                         'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                      'def_path' => TRUE),
            array('src' => '/dashboard/js/jquery.tagcanvas.js',     'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>
	<style type="text/css">	

		.centrada 
		{
			text-align:center;
			vertical-align:middle;
			width:100%;
			height:<?php echo $height ?>px;
		}

		ul 
		{
			list-style-type: none;
		}

		.tctooltip 
		{
			font-size: 12px;
			background: #333;
			color: #EEE;
			padding: 7px;
			font-weight: bold;
		}

	</style>	

	<script type='text/javascript'>
		
		function redirec(url)
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
		
		$(document).ready(function(){

			$('#myCanvas').tagcanvas({
				textColour : '<?php echo $colors ?>',
				outlineColour: '<?php echo $colors ?>',
				outlineThickness: 1,
				textFont: '',
				initial: [0.017,-0.017],
				tooltipDelay: 200,
				maxSpeed: 0.04,
				minSpeed: 0.0008,
				shape : '<?php echo $type ?>',
				weight : true,
		        weightFrom : 'data-weight',
				frontSelect: true,
				freezeActive: true,
				zoom: 1.15,
				zoomMin: 0.7,
				zoomMax: 1.7,		
				tooltip: 'div'
			}); 

			$(".info").tipTip();				
			
		});
	</script>	
</head>

<body style="overflow:hidden" scroll="no">
	
	<?php 
	if (count($cloud) > 0) 
	{ 
	?>
	
	<table class='centrada transparent container'>
		<tr>
			<td class="center">
			
			<?php 
			if ($type == 'sphere' || $type == 'hcylinder') 
			{ 
			?>
				<canvas  width='<?php echo $height + ($height/2)?>px' height='<?php echo $height ?>px' id="myCanvas">					  
					<ul>
                        <?php 
                        foreach ($cloud as $element) 
                        { 
                            $div      = (log($maximum_count) == 0) ? 1 : log($maximum_count);
                            $div      = log($element['num']) / $div;
                            
                            $fontsize = round(0.5 + ($div * ($max_font_size-$min_font_size) + $min_font_size));
                        ?>
						<li>
							<a href='javascript:;' title="<?php echo $element['title'] ?>" onclick='redirec("<?php echo $element['url'] ?>");' data-weight='<?php echo round($fontsize) ?>' ><?php echo $element['object'] ?></a>
						</li>
						<?php
						}
						?>
					</ul>
				</canvas>					
				
			<?php 
			} 
			else 
			{ 
				foreach ($cloud as $element) 
				{ 
					$div      = (log($maximum_count) == 0) ? 1 : log($maximum_count);
                    $div      = log($element['num']) / $div;
                    
                    $fontsize = round(0.5 + ($div * ($max_font_size-$min_font_size) + $min_font_size));
					
					echo "<a href='javascript:;' title=\"". $element['title']."\" onclick='redirec(\"" .$element['url']. "\");' style='font-size:".round($fontsize)."px;text-decoration:none;color:#$colors' class='info'>".$element['object']."</a> &nbsp;&nbsp;";
				}
				
			} ?>
			
			
			</td>
		</tr>
	</table>

	<?php 
	} 
	else 
	{ 
	?>
		<table class="transparent" align="center">
			<tr>
				<td class="center nobborder" style="font-family:arial;font-size:12px;background-color:white;padding-top:40px"><?php echo $nodata_text ?></td>
			</tr>
		</table>
		
    <?php
    }
    ?>
		
	</body>
</html>