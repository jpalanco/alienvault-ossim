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
    {
        echo('<meta http-equiv="refresh" content="'.$widget_refresh.'">');
    }
    ?>
    <title><?php echo _("Raphael Chart")?></title>
    
    
    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
        array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'css');
    
    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => '/dashboard/js/widget.js.php',   'def_path' => FALSE),
        array('src' => 'raphael/raphael.js',            'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'js');
    
    ?>

</head>

<body style="overflow:hidden;font-size:11px">
	<table id="data" style="display:none">
        <tfoot>
            <tr>
            	<?php	
                for ($i=0;$i<$max;$i++) 
                {
                    $day = ($label[$i]!="") ? $label[$i] : "-";
                    echo "<th>$day</th>\n";
                }
            	?>
            </tr>
        </tfoot>
        <tbody>
            <tr>
            	<?php	
            	for ($i=0;$i<$max;$i++) 
            	{
                    $value = ($trend1[$i]!="") ? $trend1[$i] : 0;
                    if ($value!=0)
                    {
                    	$empty=false;
                    }
                    echo "<td>$value</td>\n"; 
                }
            	?>	
            </tr>
        </tbody>
    </table>
    <table id="data2" style="display:none">
        <tbody>
            <tr>
            	<?php	
                for ($i=0;$i<$max;$i++) 
                {
                    $value = ($trend2[$i]!="") ? $trend2[$i] : 0;
                    
                    if ($value!=0)
                    {
                    	$empty=false;
                    }
                    
                    echo "<td>$value</td>\n"; 
                }
            	?>
            </tr>
        </tbody>
    </table>
	
    <script language="javascript">
        <?php
        if ($empty)
        { 
            echo "var max_aux=100;\n"; 
        }
        ?>    
        var logger_url   = <? echo json_encode($logger_url); ?>;      
        var siem_url     = <? echo json_encode($siem_url); ?>;      
		var width        = $('body').width();
        var height       = <? echo $height ?>;
		var color        = <? echo $colors ?>;
		

		// Variables to know which logger counters are not available yet
		var logger_last  = '<?php echo $logger_last_date ?>';
		var dates        = new Array;
		
		<?php
		if (is_array($dates)) 
		{
    		foreach ($dates as $_date)
    		{
    		?>
                dates.push('<?php echo $_date ?>');
    		<?php
            }
        }
		?>

    </script>
    
    <?php 
    if (!empty($label)) 
    {  
    ?>
    
	<script src="/ossim/dashboard/js/<?php echo $js ?>.js"></script>
	<script src="/ossim/js/raphael/popup.js"></script>	
		
	<table class='transparent container' style='width:100%; height:<?php echo $height ?>px;'>
		<tr>
			<td align='center' valign='middle' >
				<div id='holder' class='d_chart_container' style='height:<?php echo $height ?>px;'></div>
			</td>
		</tr>
	</table>
	
	<?php 
	} 
	else 
	{ 
	?>
	<table class='transparent container' style='width:100%; height:<?php echo $height ?>px;'>
		<tr>
			<td align='center' valign='middle' ><? echo _($nodata_text) ?></td>
		</tr>
	</table>
	
	<?php
	} 
	?>
</body>
</html>
