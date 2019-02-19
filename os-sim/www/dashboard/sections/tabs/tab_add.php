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

$pro = Session::is_pro();

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
			
			body
			{
        		background: #333333 !important;
            }
			
			td, span 
			{
				color:white;
				padding:6px 2px;
			}
			
			#title_tab
			{
    			color: #333;
			}
					
			#preview 
			{  
				background-color:#E5E5E5;
				border:1px dotted #888888;
				font-size:14px;
				width:30px;
				height:26px;
				margin:0 40px;
				text-align: center;
			}

			#extra_info
			{
				font-size:11px;
				font-style:italic;
			}

			#extra_info_a
			{
				font-size:11px;
				font-style:italic;
				tex-decoration:none;
				font-weight:bold;
				color:white;
			}

		</style>
		
		<script type='text/javascript'>
		
			$(document).ready(function() 
			{
				var parent = top.frames['main'];

				$("#slider_layout").slider(
				{
					animate: false,
					range: "min",
					disabled: false,
					value: 2,
					min:   1,
					max:   8,
					step:  1,
					slide: function(event, ui) {
						$("#span_layout").html(ui.value);
						$("#hidden_layout").val(ui.value);
					}
				});


				$("#new_tab").click(function () 
				{		
					var layout = $("#hidden_layout").val();
					var name   = $("#title_tab").val();


					if (typeof(parent.add_new_tab) == "function") parent.add_new_tab(name, layout);
					if (typeof(parent.CB_hide) == "function") parent.CB_hide();
				});
			
			
			});
		
		</script>

	</head>
	<body class="transparent">

		<table class="transparent" align="center" style="padding-top:10px;" width="90%">			
			<tr>
				<td class="nobborder" width="25%" style='text-align:right'><?php echo _("Title:");?></td>
				<td class="nobborder" width="5%"></td>			
				<td class="nobborder" style='text-align:left'>				
					<input type="text" id='title_tab' name="title" onclick="$(this).val('');" style='width:135px;' value="<?php echo _("New Tab");?>"/>
				</td>
			</tr> 
					
			<tr>	
				<td class="nobborder" width="25%" style='text-align:right'><?php echo _("Columns").": " ?></td>
				<td class="nobborder" width="5%"></td>
				<td class="nobborder" style='text-align:left'>	
					<input type="hidden" id="hidden_layout" name="layout" value='2' />
					<table class="transparent" align='center'>
						<tr>
							<td class="nobborder" style="padding:0px 0px 0px 0px;"><div id="slider_layout" style="width:130px;"></div></td>									
							<td class="nobborder" style="padding:0px 0px 0px 15px;"><span id="span_layout" style="font-size:11px;">2</span></td>							
						</tr>
					</table>

				</td>
			</tr>				
			<tr>
				<td class="nobborder" colspan='3' style="text-align:center; padding:20px 0px 5px 0px;">					
					<a href='javascript:;' id='new_tab' class='button'><?php echo _("Add New") ?> </a>
				</td>
			</tr>
			
			<tr>
				<td class="nobborder" colspan='3' style="text-align:center; padding:12px 0 0 0;">					
					<span id='extra_info'>Or click <a id='extra_info_a' href='tab_clone.php'>here</a> to clone one existing tab</span>
				</td>
			</tr>
			
		</table>
	</body>
</html>
