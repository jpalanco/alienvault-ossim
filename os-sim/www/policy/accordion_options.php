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


Session::logcheck("configuration-menu", "PolicyPolicy");


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?=_("OSSIM Framework")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui.css" />

	
	<style>
	
		body 
		{
			height:100%;
			background: rgb(51, 51, 51) !important
		}
		
		#cond
		{
			height:auto;
			width:100%;
			vertical-align:middle;
		}
		
		.cond_list
		{
			padding:0 5px 5px 5px;
			text-align:center;
		}

		a, a:hover
		{
			text-decoration:none;
			color: white;
		}			

	</style>

	<script type="text/javascript" src="../js/jquery.min.js"></script>

	<script type="text/javascript">
		
		var frame   = $(top.frames['main'].document);
		
		
		function apply(id){
			
			if(typeof(id) == 'undefined')
				return false;
			

			$('.accond-'+id, $(frame)).show();

			
			if(typeof(parent.redraw_accordion) == 'function'){
				parent.redraw_accordion(id);
			}
			if(typeof(parent.CB_hide) == 'function'){
				parent.CB_hide();
			}

		
		}
		
		function load_options(){
		
			$('#conditions', $(frame)).show();
			$('#consequences', $(frame)).hide();
			
			var options  =  {
				'6' : '<?php echo _('Sensors') ?>',
				'9' : '<?php echo _('Reputation') ?>',
				'12' : '<?php echo _('Event Priority') ?>',
				'11' : '<?php echo _('Time Range') ?>'
			};

			
			var cond_list = '';
			$('#conditions', $(frame)).children('ol').children('li:hidden').each(function(){			
				var key = $(this).attr('class').replace('accond-', '');
				cond_list += "<div class='cond_list'><a href='javascript:;' class='option' id='"+key+"'>"+options[key]+"</a></div>";					
			});
			
			if(cond_list.length == 0){
				cond_list += "<div class='cond_list'><?php echo _('There are no more conditions to add') ?></div>";
			}
					
			$('#cond').html(cond_list);
		
		
		}
		
		$(document).ready(function(){
		
			load_options();
			
			$('.option').click(function(){
				id = $(this).attr('id');
				apply(id);
				
			});

		});
	</script>
	
	
	
</head>
<body>	
	
	<table class='transparent' width='100%' height='100%'>
		<tr>
			<td  class='noborder' valign='middle'>
				<div id='cond'></div>
			</td>
		</tr>

	</table>
	
</body>