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

$sections = array(
	"2" => _("Plugin"),
	"3" => _("Event Type")
);

$rule_id = (GET('rule_id') != "") ? GET('rule_id') : POST("rule_id");
$directive_id = (GET('directive_id') != "") ? GET('directive_id') : POST("directive_id");
$xml_file = (GET('xml_file') != "") ? GET('xml_file') : POST('xml_file');
$engine_id = (GET('engine_id') != "") ? GET('engine_id') : POST('engine_id');
ossim_valid($rule_id, OSS_DIGIT, '\-', 'illegal:' . _("rule ID"));
ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("xml_file"));
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _("Engine ID"));
if (ossim_error()) {
    die(ossim_error());
}

if (POST('mode') != "") {
	// Do not allow all ANY in source type
	if (POST('plugin_id') == "" && POST('product_list') == "" && POST('category') < 1 && POST('plugin_sid_list') == "") {
		die(ossim_error(_("You cannot save this rule. No event source type defined")));
	}
	
	// For taxonomy option, always detector type
	if (POST('type') == "") $_POST["type"] = "detector";
	
	if (POST("plugin_sid") == "LIST") $_POST["plugin_sid"] = POST("plugin_sid_list");
	if (POST("product") == "LIST") $_POST["product"] = POST("product_list");
	ossim_valid(POST('plugin_id'), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("plugin ID"));
	ossim_valid(POST("plugin_sid"), OSS_PLUGIN_SID, '_', '!', OSS_NULLABLE, 'illegal:' . _("plugin sid"));
    ossim_valid(POST("plugin_sid_list"), OSS_PLUGIN_SID_LIST, OSS_NULLABLE, 'illegal:' . _("plugin sid list"));
    ossim_valid(POST("product"), OSS_ALPHA, OSS_NULLABLE, ',', 'illegal:' . _("Product Type"));
    ossim_valid(POST("product_list"), OSS_DIGIT, OSS_NULLABLE, ',', 'illegal:' . _("Product Type"));
    ossim_valid(POST("category"), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Category"));
    ossim_valid(POST("subcategory"), OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Subcategory"));
    ossim_valid(POST('type'), OSS_LETTER, OSS_NULLABLE, 'illegal:' . _("type"));
	if (ossim_error()) {
        die(ossim_error());
    }
    
    $directive_editor = new Directive_editor($engine_id);
	$directive_editor->save_rule_attrib($rule_id, $directive_id, $xml_file, array("plugin_id", "type", "plugin_sid", "product", "category", "subcategory"), array(POST("plugin_id"), POST("type"), POST("plugin_sid"), POST("product"), POST("category"), POST("subcategory")));
	?>
	<script type="text/javascript">
	var params          = new Array();
	params['xml']       = "<?php echo $xml_file ?>";
    params['directive'] = "<?php echo $directive_id ?>";
    params['reload']    = true;
	parent.GB_hide(params);
	</script><?php
	exit;
}

$directive_editor = new Directive_editor($engine_id);
$rule = $directive_editor->get_rule($directive_id, $xml_file, $rule_id);

$db = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin::get_list($conn);
if ($plugin_list == "") {
	$plugin_list = array();
}
$plugin_names = array();

$plugin_list_order = array();
foreach($plugin_list as $plugin) {
        $plugin_names[$plugin->get_id()] = $plugin->get_name();
        $plugin_list_order[strtolower($plugin->get_name()).";".$plugin->get_id()] = $plugin;
        if ($rule->plugin_id == $plugin->get_id()) $plugin_type = $plugin->get_type();
}
ksort($plugin_list_order);
$plugin_list = array(); // redefine to order
foreach ($plugin_list_order as $name => $plugin) {
        $plugin_list[] = $plugin;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link type="text/css" rel="stylesheet" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<link type="text/css" rel="stylesheet" href="/ossim/style/jquery-ui-1.7.custom.css" />
<link type="text/css" rel="stylesheet" href="/ossim/style/ui.multiselect.css" />

<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
<script type="text/javascript" src="/ossim/js/jquery.tmpl.1.1.1.js"></script>
<script type="text/javascript" src="/ossim/js/ui.multiselect_search.js"></script>
<script type="text/javascript" src="/ossim/js/combos.js"></script>

<script type="text/javascript">
$(document).ready(function(){
	load_categories();
	init_ptypes();
});
var wizard_current = 2;
var is_monitor = false;
//*************** Step 2 functions ******************
//Plugin search
function search_plugin(q) {
	var str = "";
	var _regex = new RegExp( "^" + q, "i");
	$('.plugin_line').each(function() {
		val = $(this).attr("id");
		aux = val.split(";");
		pid = aux[0];
		pname = aux[1];
		visible = false;
		if (q.match(/^\d+$/)) {
			if (pid == q) visible = true;
		} else {
			if (pname.match(_regex)) {
				visible = true;
			}
		}
		if (!visible) {
			str += pname;
			document.getElementById(val).style.display='none';
		} else {
			document.getElementById(val).style.display='block';
		}
	});
	//alert(str);
}
//Plugin mode toggle
function change_event_type(type, step)
{
    // Taxonomy -> Event Type
	if (type == 0)
	{
	    if (step == 'id')
		{
	        $(".multiselect_product").multiselect('selectNone');
		}
		else if (step == 'sid')
		{
		    $('#category').val('');
		    $('#subcategory').val('');
		}

		$('#txn_'+step).hide();
		$('#dsg_'+step).show();
	}
	// Event Type -> Taxonomy
	else
	{
	    if (step == 'id')
		{
		    $('#plugin_id').val('');
		}
		else if (step == 'sid')
		{
		    $(".multiselect_sids").multiselect('selectNone');
		}
		
		$('#dsg_'+step).hide();
		$('#txn_'+step).show();
	}
}
//Taxonomy product types multiselect
function init_ptypes() {
	$(".multiselect_product").multiselect({
		searchable: false,
		dividerLocation: 0.5,
		nodeComparator: function (node1,node2){ return 1 }
	});
}
function save_ptypes() {
	var product_list = getselectedcombovalue('productselect');
	if (product_list != "") {
			document.getElementById('product').value = "LIST";
			document.getElementById('product_list').value = product_list;
	} else {
			document.getElementById('product').value = "ANY";
			document.getElementById('product_list').value = "";
	}
}

//*************** Step 3 functions ******************
function init_sids(id,m,product_type) {
	is_monitor = m;
	//alert("plugin_sid_ajax.php?plugin_id="+id+"&product_type="+product_type+"&engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $directive_id ?>&rule_id=<?php echo $rule_id ?>&xml_file=<?php echo $xml_file ?>");
	$.ajax({
		data: {plugin_id: id, product_type: product_type, engine_id: '<?php echo $engine_id ?>', directive_id: <?php echo $directive_id ?>, rule_id: '<?php echo $rule_id ?>', xml_file: '<?php echo $xml_file ?>' },
		type: "GET",
		url: "plugin_sid_ajax.php", 
		dataType: "json",
		async: false,
		success: function(data){
			if(!data.error){	
				$('#ms_body').html('');
				$('#ms_body').html(data.data);
				$('#msg').html(data.message);
			}
		}
    });
	$("#pluginsids").multiselect({
		searchDelay: 700,
		searchable: true,
		sortable: 'both',
		dividerLocation: 0.5,
		remoteUrl: 'plugin_sid.php',
		remoteParams: { plugin_id: id, product_type: product_type },
		nodeComparator: function (node1,node2){ return 1 },
		dataParser: customDataParser,
		sizelength: 73
	});
	
	// Force to Taxonomy
	if (typeof product_type != "undefined" && product_type != "") {
		$('#plug_type_sid_tax').attr("checked", true);
		$('.plug_type_sid').attr("disabled", true);
		change_event_type(1, "sid");
	} else {
		$('.plug_type_sid').attr("disabled", false);
	}
}
function save_sids() {
	var current_sid = document.getElementById('plugin_sid').value;
	if (!current_sid.match(/\d\:PLUGIN\_SID/)) {
			var plugin_sid_list = getselectedcombovalue('pluginsids');
			if (plugin_sid_list != "") {
					document.getElementById('plugin_sid').value = plugin_sid_list;
					document.getElementById('plugin_sid_list').value = plugin_sid_list;
			} else {
					document.getElementById('plugin_sid').value = "ANY";
					document.getElementById('plugin_sid_list').value = "";
			}
	}
}
var customDataParser = function(data) {
	if ( typeof data == 'string' ) {
		var pattern = /^(\s\n\r\t)*\+?$/;
		var selected, line, lines = data.split(/\n/);
		data = {};
		$('#msg').html('');
		for (var i in lines) {
			line = lines[i].split("=");
			if (!pattern.test(line[0])) {
				if (i==0 && line[0]=='Total') {
					$('#msg').html("<?php echo _("Total plugin sids found:")?> <b>"+line[1]+"</b>");
				} else {
					// make sure the key is not empty
					selected = (line[0].lastIndexOf('+') == line.length - 1);
					if (selected) line[0] = line.substr(0,line.length-1);
					// if no value is specified, default to the key value
					data[line[0]] = {
						selected: false,
						value: line[1] || line[0]
					};
				}
			}
		}
	} else {
		this._messages($.ui.multiselect.constante.MESSAGE_ERROR, $.ui.multiselect.locale.errorDataFormat);
		data = false;
	}
	return data;
};
function load_categories() {
	$.ajax({
		data:  {"action": 5, "data":  {"ctx": "<?php echo Session::get_default_ctx() ?>"}},
		type: "POST",
		url: "../policy/policy_ajax.php", 
		dataType: "json",
		async: false,
		success: function(data){ 
				if(!data.error){
					if(data.data != ''){								
						$('#category').html(data.data);
						<?php if ($rule->category != "") { ?>
						$('#category').val(<?php echo $rule->category ?>);
						load_subcategories(<?php echo $rule->category ?>)
						<?php } ?>
					}
				} 
			}
	});
	return true;
}
function load_subcategories(cat_id){
	$.ajax({
		data:  {"action": 6, "data":  {"id": cat_id, "ctx": "<?php echo Session::get_default_ctx() ?>"}},
		type: "POST",
		url: "../policy/policy_ajax.php", 
		dataType: "json",
		async: false,
		success: function(data){ 
				if(!data.error){
					if(data.data != ''){								
						$('#subcategory').html(data.data);
						<?php if ($rule->subcategory != "") { ?>
						$('#subcategory').val(<?php echo $rule->subcategory ?>);
						<?php } ?>
					}
				} 
			}
	});
	
	return true;
}

function save_form() {
	save_ptypes();
	save_sids();
	document.sourceform.submit();
}
function wizard_next() {
	document.getElementById('wizard_'+wizard_current).style.display = "none";
	if (wizard_current == 0)  document.getElementById('steps').style.display = "";
	else $('#link_'+wizard_current).css("font-weight", "normal");
	wizard_current++;
	document.getElementById('wizard_'+(wizard_current)).style.display = "block";
	if (wizard_current == 2) {
		init_ptypes();
	}
	$('#link_'+wizard_current).css("font-weight", "bold");
}
function wizard_back() {
	document.getElementById('wizard_'+wizard_current).style.display = "none";
	if (wizard_current == 0)  document.getElementById('steps').style.display = "";
	else $('#link_'+wizard_current).css("font-weight", "normal");
	wizard_current--;
	document.getElementById('wizard_'+(wizard_current)).style.display = "block";
	$('#link_'+wizard_current).css("font-weight", "bold");
}
function wizard_goto(num) {
	document.getElementById('wizard_'+wizard_current).style.display = "none";
	$('#link_'+wizard_current).css("font-weight", "normal");
	var aux_step = wizard_current;
	wizard_current = num;
	wizard_refresh();

	if (num == 2) {
		init_ptypes();
	}
	if (num == 3) {
		if(aux_step < 3)
			init_sids($('#plugin_id').val(),is_monitor,$('#productselect').val());
	}
}
function wizard_refresh() {
	document.getElementById('wizard_'+(wizard_current)).style.display = "block";
	$('#link_'+wizard_current).css("font-weight", "bold");
}
</script>
</head>
<body>
<form name="sourceform" method="post">
<input type="hidden" name="mode" value="save"></input>
<input type="hidden" name="rule_id" value="<?php echo $rule_id; ?>" />
<input type="hidden" name="directive_id" value="<?php echo $directive_id; ?>" />
<input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>" />
<input type="hidden" name="engine_id" value="<?php echo $engine_id; ?>" />
<?php
$none_checked = 'true';
$flag_tax = ($rule->product || $rule->plugin_id == "") ? true : false;
$product_types = Product_type::get_list($conn);
?>
<table class="transparent" style="width:100%;height:100%" cellpadding="0" cellspacing="0">
	<tr>
		<td class="nobborder" id="steps" style="border-bottom:1px solid #EEEEEE !important;padding:5px" height="20">
			<table class="transparent">
				<tr>
					<td class="nobborder"><img src="../pixmaps/wand.png" alt="wizard"></img></td>
					<?php foreach ($sections as $num => $section_title) { ?>
					<td class="nobborder" style="font-size:11px" id="step_<?php echo $num ?>" nowrap><?php if ($num > 2) echo " > " ?><a href='' onclick='wizard_goto(<?php echo $num ?>);return false;' style="<?php if ($num == 2) echo "font-weight:bold" ?>" id="link_<?php echo $num ?>"><?php echo $section_title ?></a></td>
					<?php } ?>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table class="transparent" align="center">
				<tr>
					<td>
<!-- #### STEP 2: Plugin #### -->
						<div id="wizard_2">
						<?php
						$plugin_list = Plugin::get_list($conn, "ORDER BY name");
						$none_checked = 'true';
						$product_types = Product_type::get_list($conn);
						?>
						<input type="hidden" name="plugin_id" id="plugin_id" value="" />
						<input type="hidden" name="type" id="type" value="" />
						<table width="500" class="transparent" align="center">
						    <tr>
								<td class="nobborder center" style='height:48px;'>
									<div style="padding-bottom:10px">
										<?php echo _('Choose between ')."<b>"._("Event Types Selection")."</b> "._("or")." <b>"._('Taxonomy')."</b>" ?>
									</div>
									<div style="padding-bottom:10px">
										<input type="radio" name="plug_type_id" id="plug_type_id" value="0" onclick="change_event_type(0, 'id');" <?php echo (!$flag_tax) ? "checked='checked'": "" ?>/> <b><?php echo _("Event Types")?></b>
										<input type="radio" name="plug_type_id" id="plug_type_id" value="1" onclick="change_event_type(1, 'id');" <?php echo ($flag_tax) ? "checked='checked'": "" ?>/> <b><?php echo _("Taxonomy")?></b>
									</div>
								</td>
							</tr>
							<tr>
								<td class='noborder' valign="top">
									<div id='dsg_id' style='height:100%;width:100%;<?php echo (!$flag_tax) ? "": "display:none;" ?>' >
										<table>
									        <!-- ##### plugin id ##### -->
									        <tr>
									                <th style="white-space: nowrap; padding: 5px;font-size:12px">
									                        <?php echo gettext("Select a Plugin"); ?>
									                </th>
									        </tr>
									        <?php if ($rule->plugin_id != "") { ?>
									        <tr>
									                <td><?php echo _("Already selected")?>: <input type="button" value="Continue with <?php echo ($plugin_names[$rule->plugin_id] != "") ? $plugin_names[$rule->plugin_id] : $rule->plugin_id ?>" onclick="document.getElementById('plugin_id').value='<?php echo $rule->plugin_id ?>';wizard_next();init_sids(<?php echo $rule->plugin_id ?>,<?php echo ($plugin_type == '2') ? "true" : "false" ?>)"></input> <?php echo _("or select another one.") ?></td>
									        </tr>
									        <?php } ?>
									        <tr>
									                <td class="nobborder">
									                        <table class="transparent" width="100%">
									                                <tr>
									                                        <td class="nobborder" colspan="5">
									                                        <div style="overflow:auto;height:300px;width:500px;border:1px solid #EEEEEE">
									                                                <table class="transparent" width="100%">
									                                                <?php
									                                                foreach($plugin_list as $plugin) {
									                                                    $plugin_type = $plugin->get_type();
									                                                    // Skip monitor plugins for root rule
									                                                    if ($plugin_type != '1' && (!$rule->level || $rule->level <= 1)) { continue; }
									                                                    if ($plugin_type == '1') $type_name = 'Detector';
									                                                    elseif ($plugin_type == '2') $type_name = 'Monitor';
									                                                    else $type_name = 'Other (' . $plugin_type . ')';
									                                                    ?>
									                                                
									                                                <tr id="<?php echo $plugin->get_id() ?>;<?php echo strtolower($plugin->get_name()) ?>" class="plugin_line" style="display:block">
									                                                    <td class="nobborder"><input type="button" onclick="document.getElementById('plugin_id').value='<?php echo $plugin->get_id() ?>';document.getElementById('type').value='<?php echo ($plugin_type == '2') ? "monitor" : "detector" ?>';wizard_next();init_sids(<?php echo $plugin->get_id() ?>,<?php echo ($plugin_type == '2') ? "true" : "false" ?>);" value="<?php echo $plugin->get_name() ?>"/></td>
									                                                    <td class="nobborder"><?php echo $type_name." - ".$plugin->get_description() ?></td>
									                                                </tr>
									                                                
									                                                <?php } ?>
									                                                </table>
									                                        </div>
									                                        </td>
									                                </tr>
									                                <tr><td class="nobborder" colspan="3">&middot; <?php echo "<b>"._("Search")."</b> "._("a plugin name or ID") ?>: <input type="text" name="search_string" id="search_string" value="" onkeyup="search_plugin(this.value)"></input></td></tr>
									                        </table>
									                </td>
									        </tr>
									        <tr>
												<td class="center nobborder">
													<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"></input>
												</td>
											</tr>
										</table>
									</div>
									
									<div id='txn_id' style='height:100%;width:100%;<?php echo ($flag_tax) ? "": "display:none;" ?>'>
										<table width="100%">
											<tr>
												<th style="background-position:top center;padding:5px;font-size:12px" valign='middle'>
													<?php echo _("Product Type") ?><br/>
												</th>
											</tr>
											<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY product type") ?></i></td></tr>
											<tr>
												<td class="left nobborder">
													<input type="hidden" name="product" id="product" value=""></input>
													<input type="hidden" name="product_list" id="product_list" value=""></input>
													<select id="productselect" class="multiselect_product" multiple="multiple" name="productselect[]" style='width:600px;height:300px;'>
														<?php
														$rule_types = explode(",", $rule->product);
														foreach ($product_types  as $ptype) {
															$selected = (in_array($ptype->get_id(), $rule_types)) ? "selected" : "";
															echo "<option value='".$ptype->get_id()."' $selected>".$ptype->get_name()."</option>\n";
														}
														?>
													</select>
												</td>
											</tr>
											<tr>
												<td class="center nobborder">
													<input type="button" id="button_next" value="Next" onclick="wizard_next();init_sids(0,<?php echo ($plugin_type == '2') ? "true" : "false" ?>,$('#productselect').val())"></input>
													<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"></input>
												</td>
											</tr>
										</table>
									</div>
									
									</td>
								</tr>
						</table>
						</div>
						
<!-- ################## STEP 3: Plugin SID ######################## -->
<?php
$flag_tax = ($rule->category || $rule->product) ? true : false;
?>
						<div id="wizard_3" style="display:none">
						<input type="hidden" name="plugin_sid" id="plugin_sid" value="ANY">
						<input type="hidden" name="plugin_sid_list" id="plugin_sid_list" value="ANY" />
						<table class="transparent" width="500" align="center">
							<tr>
								<td class="nobborder center" style='height:48px;'>
									<div style="padding-bottom:10px">
										<?php echo _('Choose between ')."<b>"._("Event Sub-Types Selection")."</b> "._("or")." <b>"._('Taxonomy')."</b>" ?>
									</div>
									<div style="padding-bottom:10px">
										<input type="radio" name="plug_type_sid" class="plug_type_sid" value="0" onclick="change_event_type(0, 'sid');" <?php echo (!$flag_tax) ? "checked='checked'": "" ?> <?php if ($rule->product != "") echo "disabled" ?>/> <b><?php echo _("Event Sub-Types")?></b>
										<input type="radio" name="plug_type_sid" id="plug_type_sid_tax" class="plug_type_sid" value="1" onclick="change_event_type(1, 'sid');" <?php echo ($flag_tax) ? "checked='checked'": "" ?> <?php if ($rule->product != "") echo "disabled" ?>/> <b><?php echo _("Taxonomy")?></b>
									</div>
								</td>
							</tr>
							<tr>
								<td class='noborder' valign="top">
									<div id='dsg_sid' style='height:100%;width:100%;<?php echo (!$flag_tax) ? "": "display:none;" ?>' >
						                <!-- ##### plugin sid ##### -->
						                <table>
									        <tr>
									                <th style="white-space: nowrap; padding: 5px;font-size:12px">
									                        <?php echo gettext("Plugin Signatures"); ?>
									                </th>
									        </tr>
									        <tr>
									                <td class="nobborder">
									                        <table class="transparent">
									                                <tr>
									                                        <td class="nobborder">
									                                                <div id='ms_body'>
									                                                        
									                                                </div>
									                                        </td>
									                                </tr>
									                        </table>
									                </td>
									        </tr>
									        <tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY signature") ?></i></td></tr>
									        <tr>
									                <td class="center nobborder" style="padding-top:10px">
									                        <input type="button" id="back_button_sid" value="<?php echo _("Back") ?>" onclick="wizard_back();"></input>
															<input type="button" id="button_finish_sid" value="<?php echo ($rule->level > 1) ? _("Selected from List") : _("Finish") ?>" onclick="save_form();">
															<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"></input>
									                </td>
									        </tr>
									        <?php for ($i = 1; $i <= $rule->level - 1; $i++) {
									                        $sublevel = $i . ":PLUGIN_SID";
									                        //echo "<option value=\"$sublevel\">$sublevel</option>";
									                        ?><tr><td class="center nobborder"><input type="button" value="<?php echo _("Plugin Sid from rule of level")." $i" ?>" onclick="document.getElementById('plugin_sid').value='<?php echo $sublevel ?>';save_form()"></td></tr><?php
									                        $sublevel = "!" . $i . ":PLUGIN_SID";
									                        ?><tr><td class="center nobborder"><input type="button" value="<?php echo "!"._("Plugin Sid from rule of level")." $i" ?>" onclick="document.getElementById('plugin_sid').value='<?php echo $sublevel ?>';save_form()"></td></tr><?php
									                        //echo "<option value=\"$sublevel\">$sublevel</option>";?>
									        <?php } ?>
									    </table>
									 </div>
									 
									 <div id='txn_sid' style='height:100%;width:100%;<?php echo ($flag_tax) ? "": "display:none;" ?>'>
										<table width="100%">
											<tr>
												<th colspan="3" style="background-position:top center" valign='middle'>
													<?php echo _("Taxonomy Parameters") ?><br/>
												</th>
											</tr>
											<tr>
												<td class="nobborder" style="text-align:right"><?php echo _("Category")?>:</td>
												<td class="left nobborder">
													<input type="hidden" name="category_aux" value="<?php echo $rule->category ?>">
													<select name="category" id='category' style='width:165px;' onchange='load_subcategories($(this).val());'>
														<option value='' selected='selected'><?php echo _("ANY") ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<td class="nobborder" style="text-align:right"><?php echo _("Subcategory")?>:</td>
												<td class="left nobborder">
													<select name="subcategory" id='subcategory' style='width:165px;'>
														<option value='' selected='selected'><?php echo _("ANY") ?></option>
													</select>
												</td>
												<td class="center nobborder">
													<input type="button" id="back_button_txn" value="<?php echo _("Back") ?>" onclick="wizard_back();"></input>
													<input type="button" id="button_finish_txn" value="<?php echo _("Finish") ?>" onclick="save_form();"></input>
													<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"></input>
												</td>
											</tr>
										</table>
									</div>
								</td>
							</tr>
						</table>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</body>
</html>
<?php
$db->close($conn);
?>
