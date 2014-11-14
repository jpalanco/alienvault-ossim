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

$id = GET("id");

ossim_valid($id,	OSS_DIGIT,		'illegal:' . _("Tab ID"));


if (ossim_error())
{
    die(ossim_error());
}


try
{
	$tab = new Dashboard_tab($id);
}
catch (Exception $e) 
{
	die($e->getMessage());
}

$enable = ($tab->is_visible()) ? _("Hide Tab") : _("Show Tab");

if ($tab->is_default())
{	
	$default = "<div class='div_list ui-icon ui-icon-circle-check'></div><span>". _("Default Tab") ."</span>";
} 
else 
{
	$default     = "<a class='default_tab' href='javascript:;'><div class='div_list ui-icon ui-icon-check'></div><span>". _("Set Default") ."</span></a>";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                     'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',       'def_path' => TRUE)       
        );
        
        Util::print_include_files($_files, 'js');
    ?> 
	
	<style type='text/css'>		
		body{
    		background: #333333 !important;
    		opacity: .90 !important;
        	filter:Alpha(Opacity=90) !important;
        	-moz-opacity: 0.9 !important;  
            -khtml-opacity: 0.9 !important;  
		}
		
		a:hover{
		  text-decoration:none;
		}
		
		a {
			text-decoration:none;
			color:black;
		}
		
		td {
			text-align:left;
			white-space:nowrap;
		}
		
		.div_list{
			float:left;
		}
		
		span{
			padding-left:5px;
			color: white;
		}

		.ui-icon { 
		     width: 16px; 
		     height: 16px; 
		     background-image: url(/ossim/pixmaps/theme/ui-icons-widgets.png) !important; 
        }		
	</style>
	
	<script type='text/javascript'>	
		
		$(document).ready(function() {		

			var parent = top.frames['main'];
		
			$(".change_title").click(function () {
			
				if (typeof(parent.change_tab_title_menu) == "function") parent.change_tab_title_menu(<?php echo $id ?>);
				if (typeof(parent.CB_hide) == "function") parent.CB_hide();
			
			});
			
			$(".enable_tab").click(function () {
			
				if (typeof(parent.disable_tab) == "function") parent.disable_tab(<?php echo $id ?>);
				if (typeof(parent.CB_hide) == "function") parent.CB_hide();				
			
			});
			
			$(".default_tab").click(function () {
							
				if (typeof(parent.set_default_tab) == "function") parent.set_default_tab(<?php echo $id ?>);
				if (typeof(parent.CB_hide) == "function") parent.CB_hide();		
			
			});
			
			$(".remove_tab").click(function () {	
			
				if (typeof(parent.delete_tab) == "function") parent.delete_tab(<?php echo $id ?>);
				if (typeof(parent.CB_hide) == "function") parent.CB_hide();				
			
			});
			
			$(".clone_tab").click(function () {	
			
				if (typeof(parent.clone_tab) == "function") parent.clone_tab(<?php echo $id ?>);
				if (typeof(parent.CB_hide) == "function") parent.CB_hide();				
			
			});		
		});	
	</script>

</head>
<body class="transparent">
	<div style="margin:0 auto;width:90%;">	
		<table class="transparent" align='center' style='padding:5px 0px 0px 0px;' width='100%'>
						
			<tr>
				<td class="noborder">
				    <a class='enable_tab' href='javascript:;'>
				        <div class='div_list ui-icon ui-icon-power'></div>
				        <span><?php echo $enable ?></span>
				    </a>
				</td>
			</tr>
						
			<tr>
				<td class="noborder"><?php echo $default ?></td>
			</tr>
			
			<?php 
			if (!$tab->is_locked()) 
			{ 
			?>		
				
			<tr>
				<td class="noborder">
				    <a class='change_title' href='javascript:;'>
				        <div class='change_title div_list ui-icon ui-icon-pencil'></div>
				        <span><?php echo _("Change Title") ?></span>
				    </a>
				</td>							
			</tr>
									
			<tr>
				<td class="noborder">
				    <a class='remove_tab' href='javascript:;'>
				        <div class='div_list ui-icon ui-icon-trash'></div>
				        <span><?php echo _("Delete Tab") ?></span>
				    </a>
				</td>
			</tr>
			
			<?php
			} 
			?>			
						
			<tr>
				<td class="noborder">
				    <a class='clone_tab' href='javascript:;'>
				        <div class='div_list ui-icon ui-icon-copy'></div>
				        <span><?php echo _("Clone Tab") ?></span>
				    </a>
				</td>							
			</tr>
			
		</table>
	</div>

</body>

</html>

