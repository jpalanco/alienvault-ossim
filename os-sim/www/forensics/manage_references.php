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

Session::logcheck("analysis-menu", "EventsForensics");

if ( !Session::am_i_admin() ) 
{
	echo ossim_error(_("You don't have permission to see this page"));
	exit();
}

$db          = new ossim_db(true);
$conn        = $db->connect();
$plugin_list = Plugin::get_list($conn, "ORDER BY name", 0);

require 'base_conf.php';
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");

/* Connect to the Alert database */
$db_snort = NewBASEDBConnection($DBlib_path, $DBtype);
$db_snort->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 1);
$qs       = new QueryState();

$newref = GET('newref');
$delete = GET('deleteref');

$error_msg = null;

if ( $newref != "" )
{
	ossim_valid($newref, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("New Reference"));
	
	if ( !ossim_error() )
	{
		$sql = "INSERT INTO reference_system (ref_system_name) VALUES (\"$newref\")";
		$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
	}
	else
	{
		$error_msg = ossim_get_error();
		ossim_clean_error();
	}
}

if ( preg_match("/^\d+$/",$delete) )
{
	ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Reference ID"));
	
	if ( !ossim_error() )
	{
		$sql    = "SELECT sig_reference.ref_id FROM sig_reference,reference WHERE reference.ref_system_id=$delete AND reference.ref_id=sig_reference.ref_id";
		$result = $qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
		$ids    = "";
		
		while ($myrow = $result->baseFetchRow())
		{
			if ($ids != "") $ids .= ",";
			$ids .= $myrow[0];
		}
		
		if ($ids != "")
		{
			$sql = "DELETE FROM sig_reference WHERE ref_id in ($ids)";
			$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
		}
		
		$sql = "DELETE FROM reference_system WHERE ref_system_id=$delete";
		$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
		
		$sql = "DELETE FROM reference WHERE ref_system_id=$delete";
		$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
	}
	else
	{
		$error_msg = ossim_get_error();
		ossim_clean_error();
	}
}

$sql       = "SELECT * FROM reference_system";
$result    = $qs->ExecuteOutputQuery($sql, $db_snort);
$ref_types = array();

while ($myrow = $result->baseFetchRow()) {
	$ref_types[] = $myrow;
}
?>

<!-- <?php echo gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION; ?> -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo gettext("iso-8859-1"); ?>">
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>

	<script type="text/javascript">

		function GB_onclose() {
			document.location.href = '<?php echo AV_MAIN_PATH ?>/forensics/manage_references.php';
		}

		function ref_delete(id)
		{
			var delete_link_id = '#delete_link_'+id;
			var delete_img_id  = 'delete_img_'+id;
					
			$.ajax({
				type: "GET",
				url: "manage_references_checkdel.php?id="+id,
				data: "",
				beforeSend: function( xhr ) {
														
					if ( $(delete_link_id).length > 0 )
					{
						var loading_msg = "<img id='"+delete_img_id+"' src='../pixmaps/loading.gif' width='12' alt='<?php echo _("Loading")?>' align='absmiddle'/>";
						
						$(delete_link_id).hide();
						$(delete_link_id).before(loading_msg);
					}
				},
				success: function(data){
														
					if ( isNaN(data) )
					{
						$('#'+delete_img_id).remove();
						$(delete_link_id).show();
					
						$('#av_info').html(data);
						return;
					}
					
					if ( data != "0" && data != "" ) 
					{
						if (confirm("<?php echo  Util::js_entities(_("This reference type is linked in some events (at least "))?>"+data+"<?php echo  Util::js_entities(_("). Are you sure to delete?"))?>")) 
						{
							document.fdelete.deleteref.value = id;
							document.fdelete.submit();
						}
					} 
					else if ( data == "0" )
					{
						document.fdelete.deleteref.value = id;
						document.fdelete.submit();
					}
				}
			});
		}

		function ref_delete_plugin(plugin_id, plugin_sid, ref_id)
		{
			if (confirm("<?php echo  Util::js_entities(_("Are you sure to delete reference for plugin_sid "))?>"+plugin_sid+"?"))
			{
				$.ajax({
					type: "GET",
					url: "manage_references_getrefs.php",
					data: { plugin_id:plugin_id, plugin_sid:plugin_sid, delete_ref_id:ref_id },
					success: function(data) {
						$("#references_found").html(data);
					}
				});
			}
		}

		function load_sid()
		{
			var id = $('#plugin_id1').val();
			
			$.ajax({
				type: "POST",
				url: "../conf/pluginref_ajax.php",
				data: { plugin_id:id, num:1, manage:1 },
				beforeSend: function( xhr ) {
					$("#sid1").html("<img src='../pixmaps/loading.gif' width='12' alt='<?php echo _("Loading")?>' align='absmiddle'/><span style='margin-left:5px'><?php echo _("Loading")?>...</span>");
				},
				success: function(msg) {
					$("#sid1").html(msg);
				}
			});
		}
		
		function load_ref(num){
				
			var inp_id = ( num == 1) ? '#plugin_sid1' : '#plugin_sid2';
			var sel_id = ( num == 1) ? '#sidajax1' : '#sidajax2';		
			
			var value = $(sel_id).val();
			$(inp_id).val(value);
		}

		function load_refs(){
					
			var plugin_id    = document.getElementById('plugin_id1').value;
			var plugin_sid   = document.getElementById('sidajax1').value;
			var newref_type  = document.getElementById('newref_type').value;
								
			$.ajax({
				type: "GET",
				url: "manage_references_getrefs.php",
				data: { plugin_id:plugin_id, plugin_sid:plugin_sid, newref_type:newref_type },
				success: function(msg) {
					$("#references_found").html(msg);
				}
			});
		}

		function create_reference() {
			
			var plugin_id    = $('#plugin_id1').val(); 
			var plugin_sid   = $('#plugin_sid1').val(); 
			var newref_type  = $('#newref_type').val(); 
			var newref_value = $('#newref_value').val();
			
			if ( plugin_id != '' && plugin_sid != '' & newref_type != '' && newref_value != '' ) 
			{
				$.ajax({
					type: "GET",
					url: "manage_references_getrefs.php",
					data: { plugin_id:plugin_id, plugin_sid:plugin_sid, newref_type:newref_type, newref_value:newref_value },
					success: function(msg) {
						$("#references_found").html(msg);
					}
				});
			}
			else{
				alert ("<?php echo Util::js_entities(_("Must select Data Source/Event Type pair and type a value"))?>");
			}
		}


		// GreyBox
		$(document).ready(function(){
			
			GB_TYPE = 'w';
			
			$("a.greybox").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href, 400, 600);
				return false;
			});

			if (!parent.is_lightbox_loaded(window.name))
			{
			    $('.c_back_button').show();
			}
			<?php
			$p_url = Menu::get_menu_url('/ossim/conf/plugin.php', 'configuration', 'threat_intelligence', 'data_source');
			?>
			$(".c_back_button").click(function(){
				document.location.href='<?php echo $p_url ?>';
			});
		});
	</script>
	
	<style type='text/css'>
		#t_ref{
			margin: 50px auto;
			max-width: 1200px;
			white-space: nowrap;
			width: 100%;
			background: transparent;
			border-collapse: collapse;
			border-spacing: 0;
			border: none;
		}

		.headerpr{
			width: 50%;
		}
		
		.t_container{
			vertical-align: top;
		    border-top: 1px solid #DFDFDF;
		    padding: 0px;
		}
		
		select{ 
			height: 20px; 
			width: 98% !important;
		}
		
		#av_info{
			margin: 30px auto;
			width: 90%;
		}
		
	</style>
</head>

<body>

<div class='c_back_button'>
    <input type='button' class="av_b_back"/>
</div>

<div id='av_info'>
	<?php
	if ( !empty($error_msg) ){
		echo ossim_error($error_msg);
	}
	?>
</div>

<table id='t_ref'>
	
	<tr>
		<td class='sec_title'><?php echo _("Reference Types")?></td>
		<td class='sec_title'><?php echo _("New Reference")?></td>
	</tr>
	
	<tr>
		<td class='t_container'>
			<table class='table_list'>
				<form method="GET" name="fdelete">
					<input type="hidden" name="deleteref" id="deleteref" value=""/>
				<?php 
				$i = 1; 
				foreach ($ref_types as $myrow) 
				{ 
					$color = ( $i%2 == 0 )? "odd" :"even";
					?>
					<tr class="<?php echo $color?>">
						<td class="center"><img src='manage_references_icon.php?id=<?php echo $myrow[0]?>' border='0'/></td>
						<td><?php echo $myrow[1]?></td>
						<td><?php echo str_replace("%value%","<b>%value%</b>",$myrow[3])?></td>
						<td>
							<a href="manage_references_modifysys.php?id=<?php echo $myrow[0]?>" title="Edit Reference" class="greybox">
								<img src="../pixmaps/tables/table_edit.png" alt="<?php echo _("Edit")?>" title="<?php echo _("Edit")?>" border="0"/>
							</a>
						</td>
						<td>
							<a id='delete_link_<?php echo $myrow[0]?>' style='cursor:pointer;' onclick="ref_delete(<?php echo $myrow[0]?>);return false;">
								<img src="../pixmaps/tables/table_row_delete.png" alt="<?php echo _("Delete")?>" title="<?php echo _("Delete")?>" border="0"/>
							</a>
						</td>
					</tr>
					<?php 
					$i++; 
				} 
				?>
				</form>
			</table>
		</td>
		
		<td class='t_container'>
			<table width='100%' class='transparent'>														
				<?php 
				if ( $message != "" ) 
				{ 
					?>
					<tr>
						<td class="" id="message" colspan='2' style="text-align:center">
							<?php echo $message?>
						</td>
					</tr>
					<?php 
				} 
				?>
								
				<tr>
					<th class="left"><?php echo _("Reference Type")?></th>
					<td class="left">
						<input type="hidden" name="plugin_sid1" id="plugin_sid1" value=""/>
						<select name="newref_type" id="newref_type">
							<?php 
							foreach ($ref_types as $myrow) 
							{ 
								?>
								<option value="<?php echo $myrow[0]?>"><?php echo $myrow[1]?></option>
								<?php 
							} 
							?>
						</select>
					</td>
				</tr>					
																		
				<tr>
					<th class="left"><?php echo _("Data Source ID")?></th>
					<td class="left">
						<select name="plugin_id1" id="plugin_id1" onchange="load_sid();">
							<option value=""><?php echo _("Select Data Source ID")?></option>
							<?php
							foreach($plugin_list as $plugin) 
							{
								$id          = $plugin->get_id();
								$plugin_name = $plugin->get_name();
								?>
								<option value="<?php echo $id?>"><?php echo $plugin_name?></option>
								<?php 
							} 
							?>
						</select>
					</td>
				</tr>
												
				<tr>
					<th class="left"><?php echo _("Event Type")?></th>
					<td class="left" id="sid1">
						<select name="" disabled='disabled'>
							<option value=""><?php echo _("Select Event Type")?></option>
						</select>
					</td>
				</tr>
				
				<tr><td colspan='2'>&nbsp;&nbsp;</td></tr>
					
				<tr><td id="references_found" colspan='2'></td></tr>
			</table>
		</td>
	</tr>
	
	<tr><td colspan='2'>&nbsp;&nbsp;</td></tr>
	
	<tr>
		<td class='center' valign='middle' style='padding: 10px 0px;'>
			<form method="GET" name="fadd">
				<strong><?php echo _("New reference type")?></strong>: 
				<input type="text" name="newref" id="newref" value=""/>
				<input type="button" class="small av_b_secondary" onclick="document.fadd.submit();return false;" value="<?php echo _("New Reference Type")?>"/>
			</form>
		</td>
		
		<td class='center' valign='middle' style='padding: 10px 0px;'>
			<strong><?php echo _("New Reference Value")?></strong>:
			<input style='width: 250px;' type="text" name="newref_value" id="newref_value" value=""/>
			<input type="button" class="small av_b_secondary" id="create_button" onclick="create_reference()" value="<?php echo _("Create Reference")?>"/>
		</td>
	</tr>
	
</table>

</body>
</html>
