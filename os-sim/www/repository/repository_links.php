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

require_once 'repository_common.php';


Session::logcheck("configuration-menu", "Osvdb");


$id_document  = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
$go_back      = intval(REQUEST("go_back"));
$link_type    = "host";


ossim_valid($id_document, OSS_DIGIT,  'illegal:' . _("id_document"));


if ( ossim_error() ) 
{
    die(ossim_error());
}


$link_types = array(
		"directive"  => "Directive",
		"host"       => "Host",
		"host_group" => "Host Group",
		"incident"   => "Ticket",
		"net"        => "Net",
		"net_group"  => "Net Group",
		"plugin_sid" => "Plugin sid",
		"taxonomy"   => "Taxonomy"
	);

	
if (empty($link_types[$link_type]))
{

	$msg = _('Invalid link type');
	show_notification($msg);
	
	exit; 
	
}
 

$conf = $GLOBALS["CONF"];
$db   = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();



$rel_list = Repository::get_relationships($conn, $id_document);


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>	
	
	<?php
	
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',          'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'css');


    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',         'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',      'def_path' => TRUE),
        array('src' => 'notification.js',       'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'js');
        
    ?>
	
	<style type='text/css'>
	
		.ossim_error, .ossim_success 
		{ 
		  width: auto;
		}
			
		.wrap_content
		{
			width: 100%;
			margin: 10px auto 25px auto;
			text-align: center;		
		}
		
		#submit_button
		{
			padding-top:10px;
		}
		
		#newlinkname
		{
			text-align: center;
		}
		
		.c_back_button
		{
    		position: relative;
    		clear: both;
    		height: auto;
		}
		
	</style>
	
	
	<script>
		
		function show_notification(msg, type)
		{
			var config_nt = { content: msg, 
							  options: {
								type: type,
								cancel_button: false
							  },
							  style: 'display:none; text-align:center;margin: 10px auto;'
							};
			
			nt = new Notification('nt_1',config_nt);
			
			$('#container_info').html(nt.show());
			nt.fade_in(1000);
			setTimeout("nt.fade_out(1000);",2000);
		}
		
		function change_type(val, extra)
		{
			if(typeof(extra) == 'undefined')
			{
				extra = '';
			}
			
			$.ajax({
				data:  {"action": 1, "data": {"type": val, "extra": extra}},
				type: "POST",
				url: "repository_ajax.php", 
				dataType: "json",
				beforeSend: function(){
				
						var loading = "<img src='/ossim/pixmaps/loading3.gif' /> <?php echo _('Loading Options') ?></div>";
						
						if(val == 'sid')
						{
							$('#sidselect').html(loading);
						}
						else if(val == 'subcategory')
						{
							//do nothing
						}
						else
						{
							$('#newlinkname').html(loading);
						}
				},
				success: function(data){ 
				
						if(data.error)
						{
							show_notification(data.msg, 'nf_error');
							$('#newlinkname').html('');
						} 
						else
						{
							var select = data.data;
							
							if(val == 'sid')
							{
								$('#sidselect').html(select);	
							}
							else if(val == 'subcategory')
							{
								$('#subcatselect').html(select);
							}
							else
							{
								$('#newlinkname').html(select);		
							}				
						}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) 
					{
						show_notification(textStatus, 'nf_error');
						$('#newlinkname').html('');
					}
			});
		
		
		}
		
		
		function delete_link(id_doc, del_link, del_type)
		{
			if(id_doc == '' || del_link == '' || del_type == '')
			{
				show_notification('<?php echo _('Invalid Delete Parameters') ?>', 'nf_error');
				return false;
			}
			
			$.ajax({
				data:  {"action": 2, "data": {"id": id_doc, "link": del_link, "type": del_type}},
				type: "POST",
				url: "repository_ajax.php", 
				dataType: "json",
				success: function(data)
						{ 
							if(data.error)
							{
								show_notification(data.msg, 'nf_error');
							} 
							else
							{
								show_notification(data.msg, 'nf_success');
								del_link = del_link.replace(/#/g, '\\#');
								$('#link_'+del_link).remove();
												
							}
						},
				error: function(XMLHttpRequest, textStatus, errorThrown) 
					{
						show_notification(textStatus, 'nf_error');
					}
			});
		
		}
		
		function insert_link()
		{
		
			var id_doc    = <?php echo $id_document ?>;
			var link_type = $('#linktype').val();
			var new_link  = '';

			if(link_type == 'taxonomy')
			{
				ptype  = ($('#ptypeselect').val() == '' || $('#ptypeselect').val() == 'undefined') ? 0 : $('#ptypeselect').val();
				cat    = ($('#catselect').val() == '' || $('#catselect').val() == 'undefined') ? 0 : $('#catselect').val();
				subcat = ($('#subcatselect').val() == '' || $('#subcatselect').val() == 'undefined') ? 0 : $('#subcatselect').val();
				
				new_link  = ptype + '##' + cat + '##' + subcat; 
				
			}
			else
			{
				new_link  = $('#linkname').val(); 
			}
			
			new_link  = (typeof(new_link) == 'undefined')? '' : new_link;
			link_type = (typeof(link_type) == 'undefined')? '' : link_type;
			
			if(id_doc == '' || new_link == '' || link_type == '')
			{
				show_notification('<?php echo _('Invalid Link Parameters') ?>', 'nf_error');
				return false;
			}
			
			$.ajax({
				data:  {"action": 3, "data": {"id": id_doc, "link": new_link, "type": link_type}},
				type: "POST",
				url: "repository_ajax.php", 
				dataType: "json",
				success: function(data)
						{ 
							if(data.error)
							{
								show_notification(data.msg, 'nf_error');
							} 
							else
							{
								show_notification(data.msg, 'nf_success');
								$('#items_list tbody').append(data.data);
												
							}
						},
				error: function(XMLHttpRequest, textStatus, errorThrown) 
					{
						show_notification(textStatus, 'nf_error');
					}
			});
		
		}
		
		function go_back()
        {
            document.location.href="repository_document.php?id_document=<?php echo $id_document ?>&options=1";
            
            return false;
        }
        
		$(document).ready(function()
		{
		
			change_type('<?php echo $link_type ?>');
			
			$('.c_back_button').show();
		
		});
	
	</script>
</head>

<body>

    <?php 
    if ($go_back) 
    {
        ?>

        <div class="c_back_button" >         
            <input type="button" class="av_b_back" onclick="go_back();"/> 
        </div> 

        <?php
    }
    ?>

	<div class='wrap_content'>
	
		<div id='container_info'></div>
		
		<form name="flinks" method="GET">
		
			<input type="hidden" name="id_document" value="<?php echo $id_document ?>"/>
			<input type="hidden" name="insert"      value="0"/>
			<input type="hidden" name="go_back"     value="<?php echo $go_back ?>"/>
			
			<table class="transparent table_list" align="center">
				<tr>
					<th>
					   <?php echo _("Link Type")?>
				    </th>
					<th>
					   <?php echo _("Value")?>
				    </th>
					<th>
					   <?php echo _("Action")?>
				    </th>
				</tr>
				<tr>
					<td valign="top" class="center" style='width:30%'>
						<select id="linktype" name="linktype" onchange="change_type($(this).val());">
						<?php							
							foreach ($link_types as $k => $v)
							{
								$selected = ( $k == $link_type ) ? "selected='selected'" : "";
								echo "<option value='$k' $selected>$v</option>";
							}
						?>
						</select>
					</td>
					
					<td valign="top" class="center" style='width:50%'>
						<div id="newlinkname">
    						<img src='/ossim/pixmaps/loading3.gif' /> <?php echo _('Loading Options') ?>
    				    </div>
					</td>
					
					<td valign="top" class="center" style='width:20%'>
						<input class="small av_b_secondary" type="button" value="<?php echo _("Link")?>" onclick="insert_link();">
					</td>
					
				</tr>
			</table>

			<div id='submit_button'>
				<input type="button" onclick="parent.GB_hide();" value="<?php echo _("Finish")?>">
			</div>
			
		</form>
	</div>

	<br>
	
	<div class='wrap_content'>
		<table id='items_list' class="transparent table_list" align="center">
			<tr>
				<th>
				    <?php echo _("Linked To")?>
				</th>
				<th>
				    <?php echo _("Value")?>
				</th>
				<th>
				    <?php echo _("Action")?>
				</th>
			</tr>
			
			<?php
			$rel_list = (is_array($rel_list)) ? $rel_list : array();
			foreach($rel_list as $rel) 
			{
				$rel['id'] = $id_document;
				$item = build_item_list($conn, $rel);
				
				echo $item;
			} 
			?>
		
		</table>
	</div>

</body>

</html>

<?php

$db->close();
