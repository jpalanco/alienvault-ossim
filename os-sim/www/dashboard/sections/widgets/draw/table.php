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
		<title><? echo _("Dashboard Table Widget") ?></title>
		
		<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                             'def_path' => TRUE),
            array('src' => '/dashboard/js/widget.js.php',               'def_path' => FALSE),
        );
        
        Util::print_include_files($_files, 'js');

    ?>
		<script type="text/javascript">
		      
    		function click_handler(url)
        	{
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
			
		</script>
		
	</head>
	<body style="overflow:hidden">
	
		<table width="100%" height="100%" class='table_list'>
			<?php 
			if (count($data) > 0) 
			{ 
				?>
				<tr>
					<th><?php echo $serie ?></th>
					<th><?php echo _("Count") ?></th>
				</tr>
				<?php 
				$i = 0;
				for($i=0; $i< count($data); $i++) 
				{ 
				?>
					<tr>
						<td class='left'>
							<a href='javascript:;' onclick='click_handler("<?php echo $links[$i] ?>");'>
    							<?php echo $label[$i] ?>
    				        </a>
						</td>
						<td class='center'>
							<b><?php echo Util::number_format_locale($data[$i]) ?></b>
						</td>
					</tr>
					<?php 
				}				
			} 
			else 
			{    				
				?>
				<tr>
					<td class="center nobborder" style="font-family:arial;font-size:12px;background-color:white;padding-top:40px">	
						<?php echo $nodata_text ?>
					</td>
				</tr>
				<?php 
            } 
            ?>
		</table>

	</body>
</html>
