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
Session::logcheck("configuration-menu", "CorrelationDirectives");

$query        = POST('query');
$toggled      = GET('toggled');
$toggled_dir  = GET('toggled_dir');
$toggled_info = GET('dir_info');
$engine_id    = (GET('engine_id') != "") ? GET('engine_id') : POST('engine_id');
ossim_valid($query, OSS_TEXT, OSS_PUNC, OSS_NULLABLE,    'illegal:' . _("Query"));
ossim_valid($toggled, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Toggled"));
ossim_valid($engine_id, OSS_HEX, '\-', OSS_NULLABLE, 'illegal:' . _("Engine ID"));
ossim_valid($toggled_dir, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Directive ID"));
ossim_valid($toggled_info, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Directive Option"));
if (ossim_error()) {
    die(ossim_error());
}
if (GET('msg_success') == 1) {
	$msg_success = _("Directive successfully saved");
}

$conf = $GLOBALS["CONF"];
if ($engine_id == "") {
	$engine_id = $conf->get_conf("default_engine_id", false);
}
$found = 0;

// Default engine is not allowed by CTX user perms
if (Session::get_ctx_where() != "" && Session::is_pro() && !Acl::entityAllowed(strtoupper(str_replace("-", "", $engine_id)))) {
	if ($_SESSION['_user_vision']['ctx'] != "") {
		$engine_id = Util::uuid_format($_SESSION['_user_vision']['ctx']);
	}
}

$directive_editor = new Directive_editor($engine_id);

// Default toggle User Contributed
if ($toggled == "") {
	$toggled = "user.xml";
}

if (POST('delete_directive_id') != "") {
	$toggled = POST('file');
	ossim_valid(POST('delete_directive_id'), OSS_DIGIT, 'illegal:' . _("Directive ID"));
	ossim_valid(POST('file'), OSS_ALPHA, OSS_PUNC,      'illegal:' . _("File"));
	if (ossim_error()) {
	    die(ossim_error());
	}

	if ($directive_editor->delete_directive(POST('delete_directive_id'), POST('file'))) {
		$msg_success = _("The directive was successfully deleted");
	} else {
		$msg_error = _("Unable to delete this directive");
	}
}

if (POST('clone_directive_id') != "") {
	$toggled = POST('file');
	ossim_valid(POST('clone_directive_id'), OSS_DIGIT, 'illegal:' . _("Directive ID"));
	ossim_valid(POST('file'), OSS_ALPHA, OSS_PUNC,     'illegal:' . _("File"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	if ($directive_editor->clone_directive(POST('clone_directive_id'), POST('file'))) {
		$msg_success = _("The directive was successfully cloned");
	} else {
		$msg_error = _("Unable to clone this directive");
	}
}

if (POST('enable_directive_id') != "") {
	ossim_valid(POST('enable_directive_id'), OSS_DIGIT, 'illegal:' . _("Directive ID"));
	ossim_valid(POST('file'), OSS_ALPHA, OSS_PUNC,      'illegal:' . _("File"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	$directive_editor->enable_directive(POST('file'), POST('enable_directive_id'));
	$msg_success = _("The directive was successfully enabled");
	$toggled = POST('file');
}
if (POST('disable_directive_id') != "") {
	ossim_valid(POST('disable_directive_id'), OSS_DIGIT, 'illegal:' . _("Directive ID"));
	ossim_valid(POST('file'), OSS_ALPHA, OSS_PUNC,       'illegal:' . _("File"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	$directive_editor->disable_directive(POST('file'), POST('disable_directive_id'));
	$msg_success = _("The directive was successfully disabled");
	$toggled = POST('file');
}

if (POST('touser_directive_id') != "") {
	ossim_valid(POST('touser_directive_id'), OSS_DIGIT, 'illegal:' . _("Directive ID"));
	ossim_valid(POST('file'), OSS_ALPHA, OSS_PUNC,       'illegal:' . _("File"));
	if (ossim_error()) {
	    die(ossim_error());
	}

	if ($directive_editor->clone_directive_touser(POST('touser_directive_id'), POST('file'))) {
		$msg_success = _("The directive was successfully cloned to User Contributed");
	} else {
		$msg_error = _("Unable to clone this directive");
	}
}

$categories = $directive_editor->get_categories($query);
$disabled_directives = $directive_editor->get_disabled_directives();

// Get toggled category if there is a directive parameter
if ($toggled_dir != "") {
	foreach ($categories as $category) {
		foreach ($category['directives'] as $directive_id => $directive_name) {
			if ($directive_id == $toggled_dir) {
				$toggled = $category['xml_file'];
			}
		}
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<link rel="stylesheet" type="text/css" href="../style/tipTip.css" />
<link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css"/>
<link rel="stylesheet" type="text/css" href="../style/jquery.dataTables.css"/>

<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
<script type="text/javascript" src="../js/jquery.jeditable.js"></script>
<script type="text/javascript" src="../js/notification.js"></script>
<script type="text/javascript" src="../js/greybox.js"></script>
<script type="text/javascript" src="../js/jquery.dataTables.js"></script>
<script type="text/javascript">
$(document).ready(function(){

	$(".info").tipTip();

	<?php if ($msg_success != "") { ?>
	notify('<?php echo $msg_success ?>', 'nf_success');
	<?php } ?>
	<?php if ($msg_error != "") { ?>
	notify('<?php echo $msg_error ?>', 'nf_error');
	<?php } ?>


	//View KDB Document
	GB_TYPE = 'w';
	$(document).on("click", "a.greybox_kdb", function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,'450','85%');
		return false;
	});

	<?php
	if ($toggled != "" && $toggled_dir != "")
	{
		//third parameter has to be false or apply button will be red!! Important!
		echo "toggle_directive($toggled_dir, '$toggled', false);";

		if($toggled_info)
		{
			echo "toggle_directive_info($toggled_dir);";
			echo "document.location.href='#dir_head_$toggled_dir';";
		}
	}
	?>
});

function GB_onhide(url, params)
{
	if(typeof(params) != 'object')
	{
		document.location.reload();
		return false;
	}
	if(params['reload'] == true)
	{
		document.location.href=GB_makeurl('index.php?engine_id=<?php echo $engine_id ?>&toggled=&toggled_dir='+ params['directive'] +'&msg_success=1'+'&dir_info='+((typeof(params['dir_info']) != "undefined") ? '1' : ''));
	}
	else
	{
		toggle_directive(params['directive'], params['xml'], true);
		notify('<?php echo _("The rule was successfully created") ?>', 'nf_success');
	}

}

function restart_directives() {

	if(confirm("<?php echo _("In order to apply the changes, the server must be restarted. This action will temporarily stop the correlation processes. Are you sure you want to continue?") ?>"))
	{
		$.ajax({
			data:  {"action": 1},
			type: "POST",
			url: "directives_ajax.php",
			dataType: "json",
			async: false,
			success: function(data){
				if(data.error)
				{
					notify('<?php echo _("Error restarting the server") ?>', 'nf_error');
				}
				else
				{
					//notify('<?php echo _("Server restarted successfully") ?>', 'nf_success');
					$('span.apply').removeClass('reload_red');
				}

			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				notify('<?php echo _("Error restarting the server") ?>', 'nf_error');
			}
		});
	}

}

function toggle_category(Objet, Image) {
	VarDIV = document.getElementById(Objet);

	if (VarDIV.style.display == 'none') {
		VarDIV.style.display = 'block';
		Image.src="../pixmaps/flechebf.gif";
	} else {
		VarDIV.style.display = 'none';
		Image.src="../pixmaps/flechedf.gif";
	}
}
function toggle_directive(dir_id, file, force_show) {

	//alert("get_rules.php?directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>");
	if ($('#dir_arrow_'+dir_id).attr('src') == "../pixmaps/flechebf.gif" && !force_show) {
		$('#dir_arrow_'+dir_id).attr('src', '../pixmaps/flechedf.gif');
		$('#rules_'+dir_id).html("");
		$('.directive_header_'+dir_id).removeClass('theader');
		$('.directive_header_'+dir_id).css('font-weight', 'normal');
	} else {

		//if force_show is true, the apply button will be red!
		if(typeof(force_show) == 'undefined')
		{
			force_show = false;
		}

		$('#rules_'+dir_id).html("<br><img src='../pixmaps/loading3.gif' align='absmiddle'> <?php echo _("Loading rules") ?><br><br>");
		$('#dir_arrow_'+dir_id).attr('src', '../pixmaps/flechebf.gif');
		$.ajax({
			type: "GET",
			url: "get_rules.php",
			async: false,
			data: "directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>",
			success: function(msg){
				$('#rules_'+dir_id).html(msg);
				$('.directive_header_'+dir_id).addClass('theader');
				$('.directive_header_'+dir_id).css('font-weight', 'bold');
				rules_postload(dir_id, file, force_show);
			}
		});
	}
}
function toggle_directive_info(dir_id) {
	//alert("get_directive_info.php?directive_id="+dir_id);
	if ($('#info_arrow_'+dir_id).attr('src') == "../pixmaps/arrow_green_down.gif") {
		$('#info_arrow_'+dir_id).attr('src', '../pixmaps/arrow_green.gif');
		$('#info_'+dir_id).html("");
	} else {
		$('#info_'+dir_id).html("<br><img src='../pixmaps/loading3.gif' align='absmiddle'> <?php echo _("Loading directive info") ?><br><br>");
		$('#info_arrow_'+dir_id).attr('src', '../pixmaps/arrow_green_down.gif');
		$.ajax({
			type: "GET",
			url: "get_directive_info.php",
			data: "directive_id="+dir_id,
			success: function(msg){
				$('#info_'+dir_id).html(msg);
			}
		});
	}
}
function toggle_directive_kdb(dir_id) {
	//alert("get_directive_kdb.php?directive_id="+dir_id);
	if ($('#kdb_arrow_'+dir_id).attr('src') == "../pixmaps/arrow_green_down.gif") {
		$('#kdb_arrow_'+dir_id).attr('src', '../pixmaps/arrow_green.gif');
		$('#kdb_'+dir_id).html("");
	} else {
		$('#kdb_'+dir_id).html("<br><img src='../pixmaps/loading3.gif' align='absmiddle'> <?php echo _("Loading directive info") ?><br><br>");
		$('#kdb_arrow_'+dir_id).attr('src', '../pixmaps/arrow_green_down.gif');
		$.ajax({
			type: "GET",
			url: "get_directive_kdb.php",
			data: "directive_id="+dir_id,
			success: function(msg){
				$('#kdb_'+dir_id).html(msg);
				$('.table_data_'+dir_id).dataTable( {
					"iDisplayLength": 15,
					"sPaginationType": "full_numbers",
					"bLengthChange": false,
					"bJQueryUI": true,
					"aaSorting": [[ 1, "asc" ]],
					"aoColumns": [
						{ "bSortable": true },
						{ "bSortable": true }
					],
					oLanguage : {
						"sProcessing": "<?php echo _('Processing') ?>...",
						"sLengthMenu": "Show _MENU_ entries",
						"sZeroRecords": "<?php echo _('No matching records found') ?>",
						"sEmptyTable": "<?php echo _('No data available in table') ?>",
						"sLoadingRecords": "<?php echo _('Loading') ?>...",
						"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
						"sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
						"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
						"sInfoPostFix": "",
						"sInfoThousands": ",",
						"sSearch": "<?php echo _('Search') ?>:",
						"sUrl": "",
						"oPaginate": {
							"sFirst":    "<?php echo _('First') ?>",
							"sPrevious": "<?php echo _('Previous') ?>",
							"sNext":     "<?php echo _('Next') ?>",
							"sLast":     "<?php echo _('Last') ?>"
						}
					},
					"fnInitComplete": function() {
						var tittle = "<div style='position:absolute;width:30%;margin:0 auto;text-align:center;right:0;left:0;top:4px'><b><?php echo _('KDB') ?></b></div>"
						$('div.dt_header').prepend(tittle);
					}
				});
			}
		});
	}
}
function toggle_directive_rulelist(dir_id) {
	if ($('#rulelist_arrow_'+dir_id).attr('src') == "../pixmaps/arrow_green_down.gif") {
		$('#rulelist_arrow_'+dir_id).attr('src', '../pixmaps/arrow_green.gif');
		$('#rulelist_'+dir_id).hide();
	} else {
		$('#rulelist_arrow_'+dir_id).attr('src', '../pixmaps/arrow_green_down.gif');
		$('#rulelist_'+dir_id).show();
	}
}
function toggle_directive_rulemore(rule_id) {
	if ($('#rulemore_arrow_'+rule_id).attr('src') == "../pixmaps/arrow_green_down.gif") {
		$('#rulemore_arrow_'+rule_id).attr('src', '../pixmaps/arrow_green.gif');
		$('#rulemore_'+rule_id).hide();
	} else {
		$('.rulemore_arrow').attr('src', '../pixmaps/arrow_green.gif');
		$('.rulemore').hide();
		$('#rulemore_arrow_'+rule_id).attr('src', '../pixmaps/arrow_green_down.gif');
		$('#rulemore_'+rule_id).show();
	}
}
function move_rule(rule, dir_id, file, direction) {
	//alert("get_rules.php?mode=move&direction="+direction+"&directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>&rule="+rule);
	$('#rules_'+dir_id).html("<br><img src='../pixmaps/loading3.gif' align='absmiddle'> <?php echo _("Updating rules") ?><br><br>");
	$('#dir_arrow_'+dir_id).attr('src', '../pixmaps/flechebf.gif');
	$.ajax({
		type: "GET",
		url: "get_rules.php",
		data: "mode=move&direction="+direction+"&directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>&rule="+rule,
		success: function(msg){
			$('#rules_'+dir_id).html(msg);
			rules_postload(dir_id, file);
		}
	});
}
function copy_rule(rule, dir_id, file) {
	//alert("get_rules.php?mode=copy&directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>&rule="+rule);
	$('#rules_'+dir_id).html("<br><img src='../pixmaps/loading3.gif' align='absmiddle'> <?php echo _("Updating rules") ?><br><br>");
	$('#dir_arrow_'+dir_id).attr('src', '../pixmaps/flechebf.gif');
	$.ajax({
		type: "GET",
		url: "get_rules.php",
		data: "mode=copy&directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>&rule="+rule,
		success: function(msg){
			$('#rules_'+dir_id).html(msg);
			rules_postload(dir_id, file);
		}
	});
}
function delete_rule(rule, dir_id, file) {
	//alert("get_rules.php?mode=delete&directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>&rule="+rule);
	$('#rules_'+dir_id).html("<br><img src='../pixmaps/loading3.gif' align='absmiddle'> <?php echo _("Deleting rule") ?><br><br>");
	$('#dir_arrow_'+dir_id).attr('src', '../pixmaps/flechebf.gif');
	$.ajax({
		type: "GET",
		url: "get_rules.php",
		data: "mode=delete&directive_id="+dir_id+"&file="+file+"&engine_id=<?php echo $engine_id ?>&rule="+rule,
		success: function(msg){
			if (msg.match(/ERRORDELETE/)) {
				notify('<?php echo _("Unable to delete this rule") ?>', 'nf_error');
				toggle_directive(dir_id, file, true);
			} else {
				$('#rules_'+dir_id).html(msg);
				rules_postload(dir_id, file);
			}
		}
	});
}

// Load on-ready javascript plugins for ajax recent html content
function rules_postload(dir_id, file, reset) {

	if(typeof(reset) == 'undefined')
	{
		reset = true;
	}

	$(".editable").editable("save_attribute.php", {
		indicator : "<img src='../pixmaps/loading.gif' width='16'>",
		type   : 'text',
		submitdata: { engine_id: "<?php echo $engine_id ?>" },
		select : true,
		width  : '70',
		submit : 'OK',
		cancel : '<?php echo _("Cancel") ?>',
		callback : function(value, settings) {
			if( value.error == 0 ){
				$('span.apply').addClass('reload_red');
			}
		}
	});
	$(".editablepass").editable("save_attribute.php", {
		indicator : "<img src='../pixmaps/loading.gif' width='16'>",
		type   : 'password',
		submitdata: { engine_id: "<?php echo $engine_id ?>" },
		select : true,
		width  : '70',
		submit : 'OK',
		cancel : '<?php echo _("Cancel") ?>',
		callback : function(value, settings) {
			if( value.error == 0 ){
				$('span.apply').addClass('reload_red');
			}
		}
	});
	$(".editable").bind("mouseover", function() {
		$('.jeditable_msg').show();
	});
	$(".editable").bind("mouseout", function() {
		$('.jeditable_msg').hide();
	});

	$(".info").tipTip();


	if(reset)
		$('span.apply').addClass('reload_red');
}
</script>

</head>
<body style="margin:0px;">
<form method="post" id="actionform">
<input type="hidden" name="engine_id" id="engine_id" value="<?php echo $engine_id ?>" />
<input type="hidden" name="file" id="file" value="" />
<input type="hidden" name="delete_directive_id" id="delete_directive_id" value="" />
<input type="hidden" name="clone_directive_id" id="clone_directive_id" value="" />
<input type="hidden" name="enable_directive_id" id="enable_directive_id" value="" />
<input type="hidden" name="disable_directive_id" id="disable_directive_id" value="" />
<input type="hidden" name="touser_directive_id" id="touser_directive_id" value="" />
</form>

<table border="0" width="100%" class="transparent" cellspacing="0" cellpadding="0">
	<tr>
		<td class="nobborder" style="padding:12px">
			<table class="transparent">
				<tr>
					<td class="nobborder" style="font-size:20px"><?php echo _("Correlation Directives") ?></td>
					<td valign="bottom" class="nobborder" style="font-size:14px;padding-left:10px;padding-bottom:3px" id="found_msg"></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td style="font-size:20px;text-align:left"></td></tr>
	<tr>
		<td class='header_band'>
			<table class="transparent" width="100%">
				<tr>
					<td class="nobborder" style="white-space:nowrap;">
						<div class="fbutton" id="new_directive_button" onclick="GB_show('New Directive', '/ossim/directives/wizard_directive.php?engine_id=<?php echo $engine_id ?>', 600, '90%');"><div><span class="add" style="padding-left:20px;font-size:12px"><b><?php echo _("New Directive") ?></b></span></div></div>
						<div class="btnseparator"></div>
						<div class="fbutton" onclick="GB_show('Test Directives', '/ossim/directives/test.php?engine_id=<?php echo $engine_id ?>', 200, 500);"><div><span class="test" style="padding-left:20px;font-size:12px"><b><?php echo _("Test Directives") ?></b></span></div></div>
						<div class="btnseparator"></div>
						<?php if (Session::am_i_admin() && 1 == 2) { // Temporary hidden ?>
						<div class="fbutton" onclick="GB_show('User Contributed Directives', '/ossim/directives/editxml.php?engine_id=<?php echo $engine_id ?>', 600, '90%');"><div><span class="xml" style="padding-left:20px;font-size:12px"><b><?php echo _("Edit XML") ?></b></span></div></div>
						<div class="btnseparator"></div>
						<?php } ?>
						<div class="fbutton" onclick="restart_directives();"><div><span class="apply <?php echo (Web_indicator::is_on("Reload_directives")) ? "reload_red" : "" ?>" style="padding-left:20px;font-size:12px"><b><?php echo _("Restart Server") ?></b></span></div></div>
						<div class="btnseparator"></div>
    						
    						<form method="post">
    						<input type="hidden" name="engine_id" id="engine_id" value="<?php echo $engine_id ?>" />
    						
    						<div class='fbutton'><span class='search_label'><?php echo "<b>"._("Search")."</b> "._("a directive name") ?>:</span></div>
    								
    						<div class='fbutton'><input type="text" name="query" id="query" value="<?php echo Util::htmlentities($query) ?>"/></div>
    								
    					    <div class='fbutton'><input type="submit" value="<?php echo _("Search") ?>" class="small" id="search_button" /></div>
						
						<?php if ($query != "") { ?>
						<div class='fbutton'><input type="button" value="<?php echo _("Clean") ?>" class="small" id="clean_button" onclick="document.location.href='index.php?engine_id=<?php echo $engine_id ?>'"/></div>
						<?php } ?>
    						</form>
    						
						
					</td>
					<?php
					if (Session::is_pro() && count($available_engines = $directive_editor->get_available_engines()) > 1)
					{
    					?>
    					<td>
    						<table align="right" class="transparent">
    							<tr>
    								<td style="font-size:12px"><?php echo _("Select Engine") ?>:</td>
    								<td class="center nobborder" style="padding-left:5px">
    								<select name="engine_param" onchange="document.location.href='index.php?engine_id='+this.value" style="font-size:12px">
    								<?php foreach ($available_engines as $e_id => $e_name) { ?>
    								<option value="<?php echo $e_id ?>" <?php if ($engine_id == $e_id) echo "selected" ?>><?php echo $e_name ?></option>
    								<?php } ?>
    								</select>
    								</td>
    							</tr>
    						</table>
    					</td>
    					<?php
    				}
    				?>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td style="border:0px;padding:5px" valign="top">
			<table width="100%" style="border:0px;background-color:transparent;">
				<?php foreach ($categories as $category) { ?>
				<tr>
					<td class="nobborder">
						<table cellpadding='0' cellspacing='4' style="border:0px; background-color:transparent;" width="100%">
							<tr>
								<td class="nobborder" style="padding-left:4px" <?php echo ($category['active']) ? "" : "style='background:#eeeeee'" ?>>
									<table cellpadding='0' cellspacing='0' style="border:0px; background-color:transparent;" width="100%">
										<tr>
											<td style="border:0px" width="20">
												 <img id="img_<?php echo $category['name'] ?>" align="left" border="0"
												 <?php if (($query != "" || $category['xml_file'] == $toggled) && count($category['directives']) > 0) { ?>
												 src="../pixmaps/flechebf<?php if (!$category['active'] || count($category['directives']) < 1) echo "_gray" ?>.gif"
												 <?php } else { ?>
												 src="../pixmaps/flechedf<?php if (!$category['active'] || count($category['directives']) < 1) echo "_gray" ?>.gif"
												 <?php } ?>
												 <?php if ($category['active'] && count($category['directives']) > 0) { ?>
												 onclick="toggle_category('<?php echo $category['name']; ?>',this)"
												 <?php } ?>
												 style="cursor:pointer"/>
											</td>
											<td style="text-align:left">
											    <a href="" onclick="<?php if ($category['active'] && count($category['directives']) > 0) { ?>toggle_category('<?php echo $category['name']; ?>',document.getElementById('img_<?php echo $category['name'] ?>'));<?php } ?>return false" class='category_link'>
											        <b><?php echo $category['name'] ?></b>
											    </a>
											     <?php if (count($category['directives']) > 0) { ?> <font style="color:#666666;font-size:10px;font-weight:normal">[<?php echo "<b>".count($category['directives'])."</b> "._("directive").((count($category['directives']) > 1) ? "s" : ""); ?>]</font><?php } ?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<div id="<?php echo $category['name']; ?>" <?php if (($query == "" && $category['xml_file'] != $toggled) || count($category['directives']) < 1) { ?>style="display:none"<?php } ?>>
						<table class="transparent" width="100%" cellspacing="0">
							<?php foreach ($category['directives'] as $directive_id => $directive_name) { ?>
							<tr>
								<td style="border:0px;padding-left:20px" width="20">
									 <img id="dir_arrow_<?php echo $directive_id ?>" align="left" border="0" src="../pixmaps/flechedf<?php if (!$category['active'] || count($category['directives']) < 1) echo "_gray" ?>.gif" onclick="toggle_directive(<?php echo $directive_id ?>, '<?php echo $category['xml_file'] ?>')" style="cursor:pointer"/>
								</td>
								<td style="text-align:center;border:0px;white-space:nowrap;width:40px;padding-right:8px" width="40">
									<?php if ($category['xml_file'] == "user.xml") { ?>
									<?php if ($disabled_directives[$category['xml_file']][$directive_id]) { ?>
									<a href="" id="enable_directive_button_<?php echo $directive_id; ?>" onclick="$('#enable_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit();return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Enable this directive"); ?>" class="info"><img src="../pixmaps/cross.png" border="0"></img></a>
									<?php } else { ?>
									<a href="" id="disable_directive_button_<?php echo $directive_id; ?>" onclick="$('#disable_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit();return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Disable this directive"); ?>" class="info"><img src="../pixmaps/tick.png" border="0"></img></a>
									<?php } ?>
									<a href="" id="clone_directive_button_<?php echo $directive_id; ?>" onclick="if (confirm('<?php echo Util::js_entities(gettext("Are you sure you want to clone this directive ?")); ?>')) { $('#clone_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit(); } return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Clone this directive"); ?>" class="info"><img src="../pixmaps/copy.png" border="0"></img></a>
									<a href="" id="delete_directive_button_<?php echo $directive_id; ?>" onclick="if (confirm('<?php echo Util::js_entities(gettext("Are you sure you want to delete this directive ?")); ?>')) { $('#delete_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit(); } return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Delete this directive"); ?>" class="info"><img src="../pixmaps/delete.gif" border="0"></img></a>
									<a href="" id="edit_directive_button_<?php echo $directive_id; ?>" onclick="GB_show('Edit Directive', '/ossim/directives/wizard_directive.php?engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $directive_id ?>', 500, 400);return false;" title="<?php echo gettext("Edit this directive"); ?>" class="info" style="font-size:12px"><img src="../pixmaps/pencil.png" border="0"/></a>
									<?php } else { ?>
										<?php if ($disabled_directives[$category['xml_file']][$directive_id]) { ?>
										<a href="" id="enable_directive_button_<?php echo $directive_id; ?>" onclick="$('#enable_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit();return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Enable this directive"); ?>" class="info"><img src="../pixmaps/cross.png" border="0"></img></a>
										<?php } else { ?>
										<a href="" id="disable_directive_button_<?php echo $directive_id; ?>" onclick="$('#disable_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit();return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Disable this directive"); ?>" class="info"><img src="../pixmaps/tick.png" border="0"></img></a>
										<?php } ?>
										<a href="" id="clone_directive_touser_button_<?php echo $directive_id; ?>" onclick="if (confirm('<?php echo Util::js_entities(gettext("Are you sure you want to clone this directive to user category ?")); ?>')) { $('#touser_directive_id').val('<?php echo $directive_id; ?>');$('#file').val('<?php echo $category['xml_file'] ?>');$('#actionform').submit(); } return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("Clone this directive to user"); ?>" class="info"><img src="../pixmaps/copy.png" border="0"></img></a>
										<a href="" onclick="return false;" style="marging-left:20px; cursor:pointer" title="<?php echo gettext("This directive is part of the AlienVault Feed and therefore it can not be modified. Clone it in order to make changes."); ?>" class="info"><img src="../pixmaps/delete.gif" border="0" class="disabled"/></a>
										<a href="" onclick="return false;" title="<?php echo gettext("This directive is part of the AlienVault Feed and therefore it can not be modified. Clone it in order to make changes."); ?>" class="info" style="font-size:12px"><img src="../pixmaps/pencil.png" border="0" class="disabled"/></a>
									<?php } ?>
								</td>
								<td style="text-align:left;padding:5px;font-size:13px;border-bottom:0px" class="directive_header_<?php echo $directive_id ?>">
									<a href='javascript:;' class='directive_link' name='dir_head_<?php echo $directive_id ?>'><?php echo $directive_name; ?></a>
									<br/><font style="font-size:10px"><?php echo $directive_editor->get_directive_intent($directive_id) ?></font>
								</td>
							</tr>
							<tr><td colspan="3" style="padding-left:40px;padding-right:0px;padding-top:0px;padding-bottom:3px" align="left"><div id="rules_<?php echo $directive_id ?>"></div></td></tr>
							<?php $found++; } ?>
							<?php if ($category['xml_file'] == "user.xml") { ?>
							<!--
							<tr>
								<td style="border:0px;padding-left:20px" width="20">
									 &nbsp;
								</td>
								<td colspan="2"><input type="button" onclick="GB_show('New Directive', 'wizard_directive.php?xml_file=<?php echo $file ?>&engine_id=<?php echo $engine_id ?>', 600, '90%');return false;" value="<?php echo _("Create new directive") ?>"/></td>
							</tr>
							 -->
							<?php } ?>
						</table>
						</div>
					</td>
				</tr>
				<?php } ?>
			</table>
		</td>
	</tr>
</table>
</body>
<script type="text/javascript">
$(document).ready(function(){
	$('#found_msg').html("<?php echo _("Found")." <b>".number_format($found)."</b> "._("directives")." ".(($query != "") ? _("matching")." '<b>". Util::htmlentities($query) ."</b>'" : _("in the system")) ?>");
});
</script>
</html>
