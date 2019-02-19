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


require_once ('av_init.php');
Session::logcheck("configuration-menu", "ConfigurationPlugins");

$plugin_id = GET('plugin_id');
$sid       = GET('sid');

ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("Plugin ID"));
ossim_valid($sid,       OSS_DIGIT, 'illegal:' . _("SID"));

if (ossim_error())
{
    die(ossim_error());
}
    
$db         = new ossim_db();
$conn       = $db->connect();

$plugin_obj = Plugin_sid::get_list($conn, "WHERE plugin_id = $plugin_id AND sid = $sid");

if ( is_object($plugin_obj[0]) )
{
	$name        = Util::htmlentities(str_replace("&lt;","<",str_replace("&gt;",">",$plugin_obj[0]->get_name())),ENT_QUOTES);
	$reliability = intval($plugin_obj[0]->get_reliability());
	$priority    = intval($plugin_obj[0]->get_priority());
	$category    = $plugin_obj[0]->get_category_id();
	if ($category == "") $category = "0";
	$subcategory = $plugin_obj[0]->get_subcategory_id();
	
	$list_categories    = Category::get_list($conn);
	$list_subcategories = Subcategory::get_list($conn,'WHERE cat_id='.$category.' ORDER BY name');
}
else
{
	$db->close($conn);
	ossim_error(_("Error retrieving information from Data Source"));
	exit();
}

$db->close($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript">
		function load_subcategory(category_id) {
			$.ajax({
				type: "POST",
				url: "modifypluginsid_ajax.php",
				data: { category_id:category_id },
				beforeSend: function( xhr ) {
					$('#ajaxSubCategory').html('<img src="../pixmaps/loading.gif" width="12" align="top" alt="<?php echo _("Loading")?>"><span style="margin-left: 3px;"><?php echo _("Loading")?> ...</span>');
				},
				success: function(msg) {
					$("#ajaxSubCategory").html(msg);
				}
			});
		}
		
		$(document).ready(function(){
		
			$('#category').change(function() { 
				var category_id = $('#category').val();
				load_subcategory(category_id);
			});
			
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'f_plugin',
					url : "modifypluginsid.php"
				},
				actions: {
					on_submit:{
						id: 'send',
						success: '<?php echo _("Update")?>',
						checking: '<?php echo _("Updating")?>'
					}
				}
			};
		
			ajax_validator = new Ajax_validator(config);
		
		    $('#send').click(function(event) { 
				event.preventDefault();
				ajax_validator.submit_form();
			});
		});
	</script>
	<style type='text/css'>
		
		a {cursor:pointer;}
		
		input[type='text'], input[type='hidden'], select {width: 98%; height: 18px;}
		textarea {width: 97%; height: 45px;}
		
		#t_plugin {
			margin: 20px auto;
			width: 500px;
		}
		
		#t_plugin th {width: 150px;}
		
		#av_info {width: 650px; margin: 10px auto;}
	</style>
</head>

<body>

<div id='av_info'></div>
    
<form method="POST" name="f_plugin" id="f_plugin" action="modifypluginsid.php">
    <input type="hidden" class='vfield' name="plugin_id" id="plugin_id" value="<?php echo $plugin_id?>"/>
	<input type="hidden" class='vfield' name="sid" id="sid" value="<?php echo $sid?>"/>
	
    <table id='t_plugin'>
		<tr>
			<th><?php echo _("Name") . required();?></th>
			<td class="left"><textarea name="ds_name" id="ds_name" class='vfield'><?php echo $name;?></textarea></td>
		</tr>
					
		<tr>
			<th><?php echo _("Category")?></th>
			<td class="left">
				<select class='vfield' name="category" id='category'>
					<option value=''>&nbsp;</option>
					<?php 
					foreach ($list_categories as $c) 
					{ 
						$selected = ( $category == $c->get_id() ) ? ' selected="selected"' : '';
						?>
						<option value='<?php echo $c->get_id();?>'<?php echo $selected?>><?php echo  str_replace('_', ' ', $c->get_name());?></option>
						<?php 
					} 
					?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th><?php echo _("Subcategory")?></th>
			<td class="left">
				<div id="ajaxSubCategory">
					<select class='vfield' name="subcategory" id="subcategory">						
						<?php 
						if( $subcategory == '' )
						{ 
							?>
							<option value='' selected='selected'>-- <?php echo _("Select a category")?> -- </option>
							<?php
						}
						else
						{
							foreach ($list_subcategories as $sbc) 
							{
								$selected = ( $subcategory == $sbc->get_id() ) ? ' selected="selected"' : '';
								?>
								<option value='<?php echo $sbc->get_id();?>'<?php echo $selected?>><?php echo  str_replace('_', ' ', $sbc->get_name()); ?></option>
								<?php
							}
						}
						?>					
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<th><?php echo _("Reliability") . required();?></th>
			<td class="left">
				<select class='vfield' name="reliability" name="reliability">
					<?php
					for($i=0; $i<=10; $i++) 
					{ 
						$selected = ( $reliability == $i ) ? ' selected="selected"' : "";
						?>
						<option value='<?php echo $i?>'<?php echo $selected?>><?php echo $i?></option>
						<?php 
					} 
					?>
				</select>
			</td>
		</tr>
  
		<tr>
			<th><?php  echo _("Priority") . required();?></th>
			<td class="left">
				<select class='vfield' name="priority" name="priority">
					<?php
					for($i=0; $i<=5; $i++) 
					{ 
						$selected = ( $priority == $i ) ? ' selected="selected"' : "";
						?>
						<option value='<?php echo $i?>'<?php echo $selected?>><?php echo $i?></option>
						<?php 
					} 
					?>
				</select>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center" style="padding: 10px;">
				<input type="button" name='back' id='back' class='av_b_secondary' onclick="history.go(-1)" value="<?php echo _('Back')?>"/>
				<input type="button" name='send' id='send' value="<?php echo _("Update")?>"/>
			</td>
		</tr>
	</table>
</form>

<p align="center" style="font-style: italic;"><?php echo _("Values marked with (*) are mandatory");?></p>

</body>
</html>
