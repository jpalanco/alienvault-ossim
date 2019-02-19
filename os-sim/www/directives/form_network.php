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

$rule_id      = (POST('rule_id') != '')      ? POST('rule_id')      : GET('rule_id');
$directive_id = (POST('directive_id') != '') ? POST('directive_id') : GET('directive_id');
$xml_file     = (POST('xml_file') != '')     ? POST('xml_file')     : GET('xml_file');
$engine_id    = (POST('engine_id') != '')    ? POST('engine_id')    : GET('engine_id');

ossim_valid($rule_id, OSS_DIGIT, '\-', 'illegal:' . _("rule ID"));
ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("xml_file"));
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _("Engine ID"));
if (ossim_error()) {
    die(ossim_error());
}

// Secondary validation
$error     = FALSE;
$error_msg = array();

$db = new ossim_db();
$conn = $db->connect();

if (POST('mode') != "") {
	// Force assets when user perms, cannot be ANY
	$has_perms = (Session::get_host_where() != "" || Session::get_net_where() != "") ? TRUE : FALSE;
    if ($has_perms && (POST('from') == "ANY" || (POST('from') == "LIST" && count($_POST["fromselect"]) < 1)))
	{
		$_POST["from"] = "LIST";
		$assets_aux = array();
	    
		$_list_data = Asset_host::get_basic_list($conn);
		$_host_aux  = array_keys($_list_data[1]);
		foreach ($_host_aux as $h_id)
		{
			$assets_aux[] = Util::uuid_format($h_id);
		}
		
		$_list_data = Asset_net::get_list($conn);
		$_net_aux   = array_keys($_list_data[0]);
		foreach ($_net_aux as $n_id)
		{
			$assets_aux[] = Util::uuid_format($n_id);
		}
		
		$_POST["fromselect"] = $assets_aux;
	}
	if ($has_perms && (POST('to') == "ANY" || (POST('to') == "LIST" && count($_POST["toselect"]) < 1)))
	{
		$_POST["to"] = "LIST";
		$assets_aux = array();
		
		$_list_data = Asset_host::get_basic_list($conn);
		$_host_aux  = array_keys($_list_data[1]);
		foreach ($_host_aux as $h_id)
		{
		    $assets_aux[] = Util::uuid_format($h_id);
		}
		
		$_list_data = Asset_net::get_list($conn);
		$_net_aux   = array_keys($_list_data[0]);
		foreach ($_net_aux as $n_id)
		{
		    $assets_aux[] = Util::uuid_format($n_id);
		}
		
		$_POST["toselect"] = $assets_aux;
	}
	
	// Assets parameters can be multiselect([UUID1, UUID2, ...]) or string(ANY, HOME_NET, ...)
	$assets_from = (POST("from") == "LIST") ? implode(',', $_POST["fromselect"]) : POST("from");
	$assets_to   = (POST("to")   == "LIST") ? implode(',', $_POST["toselect"])   : POST("to");
    
    if (POST("port_from") == "LIST") $_POST["port_from"] = POST("port_from_list");
    if (POST("port_to") == "LIST") $_POST["port_to"] = POST("port_to_list");
    
	ossim_valid(POST("xml_file"), OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("xml file"));
	//ossim_valid(POST("from"), OSS_FROM, OSS_NULLABLE, '-\/', 'illegal:' . _("from"));
    //ossim_valid(POST("from_list"), OSS_FROM, OSS_NULLABLE, '-\/' ,'illegal:' . _("from list"));
	ossim_valid($assets_from, OSS_FROM, OSS_NULLABLE, '-\/', 'illegal:' . _("from"));
    ossim_valid(POST("port_from"), OSS_PORT_FROM, OSS_NULLABLE, 'illegal:' . _("port from"));
    ossim_valid(POST("port_from_list"), OSS_PORT_FROM_LIST, OSS_NULLABLE, 'illegal:' . _("port from list"));
    //ossim_valid(POST("to"), OSS_TO, OSS_NULLABLE, '-\/', 'illegal:' . _("to"));
    //ossim_valid(POST("to_list"), OSS_TO, OSS_NULLABLE, '-\/', 'illegal:' . _("to list"));
    ossim_valid($assets_to, OSS_TO, OSS_NULLABLE, '-\/', 'illegal:' . _("to"));
    ossim_valid(POST("port_to"), OSS_PORT_TO, OSS_NULLABLE, 'illegal:' . _("port to"));
    ossim_valid(POST("port_to_list"), OSS_PORT_TO_LIST, OSS_NULLABLE, 'illegal:' . _("port from list"));
    ossim_valid(POST("from_rep"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation from"));
    ossim_valid(POST("to_rep"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation to"));
    ossim_valid(POST("from_rep_min_pri"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation from min priority"));
    ossim_valid(POST("to_rep_min_pri"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation to min priority"));
    ossim_valid(POST("from_rep_min_rel"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation from min reliability"));
    ossim_valid(POST("to_rep_min_rel"), OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Reputation to min reliability"));
	if (ossim_error()) {
        die(ossim_error());
    }
    
    // Secondary validation
    if (!Directive_editor::valid_directive_port(POST("port_from")) || !Directive_editor::valid_directive_port(POST("port_from_list")))
    {
        $error       = TRUE;
        $error_msg[] = _('Invalid source port value');
    }
    
    if (!Directive_editor::valid_directive_port(POST("port_to")) || !Directive_editor::valid_directive_port(POST("port_to_list")))
    {
        $error       = TRUE;
        $error_msg[] = _('Invalid destination port value');
    }
    
    if (!$error)
    {
        $directive_editor = new Directive_editor($engine_id);
        $directive_editor->save_rule_attrib($rule_id, $directive_id, $xml_file, array("from", "to", "port_from", "port_to", "from_rep", "to_rep", "from_rep_min_pri", "to_rep_min_pri", "from_rep_min_rel", "to_rep_min_rel"), array($assets_from, $assets_to, POST('port_from'), POST('port_to'), POST('from_rep'), POST('to_rep'), POST('from_rep_min_pri'), POST('to_rep_min_pri'), POST('from_rep_min_rel'), POST('to_rep_min_rel')));
        ?>
        <script type="text/javascript">
        var params          = new Array();
        params['xml']       = "<?php echo $xml_file ?>";
        params['directive'] = "<?php echo $directive_id ?>";
        params['reload']    = true;
        parent.GB_hide(params);
        </script>
        <?php
        exit;
    }
    else
    {
        $config_nt   = array(
            'content' => '',
            'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => true
            ),
            'style'   => 'position:absolute;top:15px;left:50%;margin-left:-150px;text-align:center;padding:1px 30px;z-index:999'
        );
        $config_nt['content'] = implode('<br>', $error_msg);
        $nt = new Notification('nt_notif', $config_nt);
        
        $nt->show();
    }
}

$directive_editor = new Directive_editor($engine_id);
$rule = $directive_editor->get_rule($directive_id, $xml_file, $rule_id);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<link type="text/css" rel="stylesheet" href="../style/tree.css" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
<script type="text/javascript" src="../js/combos.js"></script>
<script type="text/javascript" src="../js/notification.js"></script>

<script type="text/javascript">
$(document).ready(function(){
	load_tree();
});
var layer_i = null;
var nodetree_i = null;
var i=1;
var layer_j = null;
var nodetree_j = null;
var j=1;
function load_tree(filter)
{
	var e_filter = ""; // Maybe entity filter
	var combo2 = "toselect";
	var suf2 = "to";
	if (nodetree_j!=null) {
			nodetree_j.removeChildren();
			$(layer_j).remove();
	}
	layer_j = '#dsttree'+j;
	$('#container'+suf2).append('<div id="dsttree'+j+'" style="width:100%"></div>');

	$(layer_j).dynatree({
			initAjax: { url: "../tree.php?key="+e_filter+"assets", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
					if (dtnode.data.key.match(/net_/) || dtnode.data.key.match(/host_/)) {
						var k = dtnode.data.key.replace("net_","");
						k = k.replace("host_","");
						key_can = k.replace(/(........)(....)(....)(....)(............)/, "$1-$2-$3-$4-$5");
						addto(combo2,dtnode.data.val,key_can.toLowerCase());
					}
                    // Click on asset group, fill box with its members
					if (dtnode.data.key.match(/hostgroup_/))
					{
					    $.ajax({
					        type: 'GET',
					        url: "../tree.php",
					        data: 'key=' + dtnode.data.key + ';4000',
					        dataType: 'json',
					        success: function(data)
					        {
						        if (data.length < 1)
						        {
						            var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
						            var msg = '<?php echo _('Unable to fetch the asset group members') ?>';
						            show_notification('error_info', msg, 'nf_error', 0, 1, nf_style);
						        }
						        else
						        {
                                    // Group reached the 200 top of page: show warning
                                    var last_element = data[data.length - 1].key;

                                    if (last_element.match(/hostgroup_/))
                                    {
                                        var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
                                        var msg = '<?php echo _('This asset group has more than 4000 assets, please try again with a smaller group') ?>';
                                        show_notification('error_info', msg, 'nf_warning', 0, 1, nf_style);
                                    }
                                    else
                                    {
                                        jQuery.each(data, function(i, group_member)
                                        {
                                            var k = group_member.key.replace("host_","");
                                            var key_can = k.replace(/(........)(....)(....)(....)(............)/, "$1-$2-$3-$4-$5");
                                            addto(combo2, group_member.val, key_can.toLowerCase());
                                        });
                                    }
						        }
					        }
					      });
					}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
					dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
			}
	});
	nodetree_j = $(layer_j).dynatree("getRoot");
	j=j+1;
	
	var combo1 = "fromselect";
	var suf1 = "from";
	if (nodetree_i!=null) {
			nodetree_i.removeChildren();
			$(layer_i).remove();
	}
	layer_i = '#srctree'+i;
	$('#container'+suf1).append('<div id="srctree'+i+'" style="width:100%"></div>');
	$(layer_i).dynatree({
			initAjax: { url: "../tree.php?key="+e_filter+"assets", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
				if (dtnode.data.key.match(/net_/) || dtnode.data.key.match(/host_/)) {
					var k = dtnode.data.key.replace("net_","");
					k = k.replace("host_","");
					key_can = k.replace(/(........)(....)(....)(....)(............)/, "$1-$2-$3-$4-$5");
					addto(combo1,dtnode.data.val,key_can.toLowerCase());
				}
				// Click on asset group, fill box with its members
				if (dtnode.data.key.match(/hostgroup_/))
				{
				    $.ajax({
				        type: 'GET',
				        url: "../tree.php",
				        data: 'key=' + dtnode.data.key + ';4000',
				        dataType: 'json',
				        success: function(data)
				        {
					        if (data.length < 1)
					        {
					            var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
					            var msg = '<?php echo _('Unable to fetch the asset group members') ?>';
					            show_notification('error_info', msg, 'nf_error', 0, 1, nf_style);
					        }
					        else
					        {
                                // Group reached the 4000 top of page: show warning
                                var last_element = data[data.length - 1].key;

                                if (last_element.match(/hostgroup_/))
                                {
                                    var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
                                    var msg = '<?php echo _('This asset group has more than 4000 assets, please try again with a smaller group') ?>';
                                    show_notification('error_info', msg, 'nf_warning', 0, 1, nf_style);
                                }
                                else
                                {
                                    jQuery.each(data, function(i, group_member)
                                    {
                                        var k = group_member.key.replace("host_","");
                                        var key_can = k.replace(/(........)(....)(....)(....)(............)/, "$1-$2-$3-$4-$5");
                                        addto(combo1, group_member.val, key_can.toLowerCase());
                                    });
                                }
					        }
				        }
				      });
				}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
					dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
			}
	});
	nodetree_i = $(layer_i).dynatree("getRoot");
	i=i+1;
}

function onChangePortSelectBox(id,val) {
	if (val == "LIST") {
		if (id.match(/port/)) document.getElementById(id+'_input').style.display = 'inline';
		else document.getElementById(id+'_input').style.visibility = 'visible';
		document.getElementById(id+'_list').value = "";
	} else {
		if (id.match(/port/)) document.getElementById(id+'_input').style.display = 'none';
		else document.getElementById(id+'_input').style.visibility = 'hidden';
		document.getElementById(id+'_list').value = val;
	}
}

function onChangeSelectBox(id,val)
{
	if (val == "LIST")
	{
		document.getElementById(id+'_input').style.visibility = 'visible';
	}
	else
	{
		deleteall(id + 'select');
		document.getElementById(id+'_input').style.visibility = 'hidden';
	}
}

function save_network() {
	selectall('fromselect');
	selectall('toselect');
	var from_list = getselectedcombovalue('fromselect');
	var to_list = getselectedcombovalue('toselect');
	var port_from_list = document.getElementById('port_from_list').value;
	var port_to_list = document.getElementById('port_to_list').value;

	if (from_list != "")
	{
		document.getElementById('from').value = "LIST";
	}
	else if (document.getElementById('from').value == "")
	{
		document.getElementById('from').value = "ANY";
	}

	if (to_list != "")
	{
		document.getElementById('to').value = "LIST";
	}
	else if (document.getElementById('to').value == "")
	{
		document.getElementById('to').value = "ANY";
	}
	
	if (port_from_list != "" && port_from_list != "ANY") document.getElementById('port_from').value = "LIST";
	if (port_to_list != "" && port_to_list != "ANY") document.getElementById('port_to').value = "LIST";
}

function save_form() {
	save_network();
	document.netform.submit();
}
</script>

</head>
<body>

<div id='error_info'></div>

<form name="netform" method="post">
<input type="hidden" name="rule_id" value="<?php echo $rule_id; ?>" />
<input type="hidden" name="directive_id" value="<?php echo $directive_id; ?>" />
<input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>" />
<input type="hidden" name="engine_id" value="<?php echo $engine_id; ?>" />
<table class="transparent" align="center">
	<tr>
		<td class="container">
			<input type="hidden" name="mode" value="save"></input>
			<table class="transparent">
				<tr>
					<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
						<?php echo gettext("Network"); ?>
					</th>
				</tr>
				<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY asset") ?></i></td></tr>
				<tr>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<!-- ##### from ##### -->
							<tr>
								<td class="nobborder" valign="top">
									<table class="transparent">
										<tr>
											<th><?php echo _("Source Host/Network") ?></th>
										</tr>
										<tr>
											<td class="nobborder">
											<div id="from_input" style="visibility:<?php echo (preg_match("/\:...\_IP/",$rule->from)) ? "hidden" : "visible" ?>">
												<table>
													<tr>
														<td class="nobborder" valign="top">
															<table align="center" class="noborder">
															<tr>
																<th style="background-position:top center"><?php echo _("Source") ?>
																</th>
																<td class="left nobborder">
																	<select id="fromselect" name="fromselect[]" size="12" multiple="multiple" style="width:150px">
																	<?php
																	if ($rule->from != "ANY" && $rule->from != "" && !preg_match("/\:...\_IP/",$rule->from)) {
																		$pre_list = explode(",", $rule->from);
																		foreach ($pre_list as $list_element) {
																			// Asset ID: Resolve by name
																			if (preg_match("/(\!)?([0-9A-Fa-f\-]{36})/", $list_element, $found)) {
																				$uuid_aux = str_replace("-", "", strtoupper($found[2]));
																				$h_obj    = Asset_host::get_object($conn, $uuid_aux);
																				if ($h_obj != null) {
																					echo "<option value='".$found[1].$found[2]."'>".$found[1].$h_obj->get_name()." (".$h_obj->get_ips()->get_ips('string').")</option>\n";
																				} else {
																					$n_obj = Asset_net::get_object($conn, $uuid_aux);
																					if ($n_obj != null) {
																						echo "<option value='".$found[1].$found[2]."'>".$found[1].$n_obj->get_name()." (".$n_obj->get_ips().")</option>\n";
																					}
																				}
																				// Another one (HOME_NET, 12.12.12.12...)
																			} else {
																				echo "<option value='$list_element'>$list_element</option>\n";
																			}
																		}
																	}
																	?>
																	</select>
																	<input type="button" class="small" value=" [X] " onclick="deletefrom('fromselect');"/>
																</td>
															</tr>
															</table>
														</td>
														<td valign="top" class="nobborder">
															<table class="noborder" align='center'>
															<tr><td class="left nobborder" id="inventory_loading_sources"></td></tr>
															<tr>
																<td class="left nobborder" nowrap>
																	<?php echo _("Asset")?>: <input type="text" id="filterfrom" name="filterfrom" size='18'/>
																	&nbsp;<input type="button" id="button_filter_from" class="small" value="<?php echo _("Filter")?>" onclick="load_tree(this.form.filterfrom.value)" />&nbsp;<input type="button" id="button_addip_from" class="small" value="<?php echo _("Add IP")?>" onclick="addto('fromselect',this.form.filterfrom.value,this.form.filterfrom.value)" /> 
																	<div id="containerfrom" class='container_ptree'></div>
																</td>
															</tr>
															<tr><td class="left nobborder"><input type="button" id="button_homenet_from" value="<?php echo _("Home Net") ?>" onclick="addto('fromselect','HOME_NET','HOME_NET')"> <input type="button" id="button_nothomenet_from" value="!<?php echo _("Home Net") ?>" onclick="addto('fromselect','!HOME_NET','!HOME_NET')"></td></tr>
															</table>
														</td>
													</tr>
												</table>
											</div>
											</td>
										</tr>
										<?php if ($rule->level > 1) { ?>
										<tr>
											<td class="center nobborder">
											From a parent rule: <select name="from" id="from" style="width:180px" onchange="onChangeSelectBox('from',this.value)">
											<?php
											echo "<option value=\"LIST\"></option>";
											for ($i = 1; $i <= $rule->level - 1; $i++) {
											    $sublevel = $i . ":SRC_IP";
											    $selected = ($rule->from == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>Source IP from level $i</option>";
											    $sublevel = "!" . $i . ":SRC_IP";
											    $selected = ($rule->from == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>!Source IP from level $i</option>";
											    $sublevel = $i . ":DST_IP";
											    $selected = ($rule->from == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>Destination IP from level $i</option>";
											    $sublevel = "!" . $i . ":DST_IP";
											    $selected = ($rule->from == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>!Destination IP from level $i</option>";
											}
											?>
											</select>
											</td>
										</tr>
										<?php } else { ?>
										<input type="hidden" name="from" id="from" value="ANY"></input>
										<?php } ?>
									</table>
								</td>
							</tr>
							<tr><th><?php echo _("Source Port(s)") ?></th></tr>
							<tr><td class="nobborder">&middot; <i><?php echo _("Use comma to specify several ports").'<br>&middot '._("Can be negated using '!'") ?></i></td></tr>
							<tr>
								<td class="center nobborder">
									<?php if ($rule->level > 1) { ?>
									From a parent rule: <select style="width:180px" name="port_from" id="port_from" onchange="onChangePortSelectBox('port_from',this.value)">
									<?php
									echo "<option value=\"LIST\"></option>";
									for ($i = 1; $i <= $rule->level - 1; $i++) {
									    $sublevel = $i . ":SRC_PORT";
									    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>Source Port from level $i</option>";
									    $sublevel = "!" . $i . ":SRC_PORT";
									    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>!Source Port from level $i</option>";
									    $sublevel = $i . ":DST_PORT";
									    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>Destination Port from level $i</option>";
									    $sublevel = "!" . $i . ":DST_PORT";
									    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>!Destination Port from level $i</option>";
									}
									?>
									</select>&nbsp;
									<?php } else { ?>
									<input type="hidden" name="port_from" id="port_from" value="LIST"></input>
									<?php } ?>
									<div id="port_from_input" style="display:<?php echo (preg_match("/\_PORT/",$rule->port_from)) ? "none" : "inline" ?>"><input type="text" name="port_from_list" id="port_from_list" value="<?php echo $rule->port_from ?>"></input></div>
								</td>
							</tr>
							
							<tr><td class="nobborder"><a id="link_reputation_from" href="" onclick="$('#rep_from_div').toggle(); if($('#rep_from_div').is(':visible')){ $('#rep_from_arrow').attr('src','../pixmaps/arrow_green_down.gif'); } else{ $('#rep_from_arrow').attr('src','../pixmaps/arrow_green.gif'); } return false;"><img id="rep_from_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo "<b>"._("Reputation")."</b> "._("options") ?></a></td></tr>
							<tr>
								<td class="nobborder" id="rep_from_div" style="display:none">
									<table class="noborder" align='center' width='100%'>
										<tr>
											<th style="background-position:top center;" valign='middle'>
												<?php echo _("Reputation Parameters") ?><br/>
											</th>
										</tr>
										<tr>
											<td class="left nobborder">
												<div style='text-align: left; padding:10px 0 15px 10px; clear: both;'>
													<div style='float: left; width:90px;'><?php echo _("Reputation from")?>:</div>
													<div style='float: left;'>
														<select id="from_rep" name="from_rep" style="width:60px" onchange="if(this.value=='true') $('.rep_from_select').attr('disabled',false); else $('.rep_from_select').attr('disabled',true);">
															<option value=''><?php echo _("No") ?></option>
															<option value='true' <?php if ($rule->from_rep == "true" || $rule->from_rep_min_pri || $rule->from_rep_min_rel) echo "selected" ?>><?php echo _("Yes") ?></option>
														</select>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td class="left nobborder">
												<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
													<div style='float: left; width:90px;'><?php echo _("Min Priority")?>:</div>
													<div style='float: left;'>
														<select id="from_rep_min_pri" name="from_rep_min_pri" class="rep_from_select" <?php if ($rule->from_rep != "true" && !$rule->from_rep_min_pri && !$rule->from_rep_min_rel) echo "disabled" ?>>
															<option value="">-</option>
															<?php
															for($i=1; $i <= 10; $i++) {
																$selected = ($rule->from_rep_min_pri == $i) ? "selected" : "";
																echo "<option value=$i $selected>$i</option>\n";
															}
															?>
														</select>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td class="left nobborder">
												<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
													<div style='float: left; width:90px;'><?php echo _("Min Reliablility")?>:</div>
													<div style='float: left;'>
														<select id="from_rep_min_rel" name="from_rep_min_rel" class="rep_from_select" <?php if ($rule->from_rep != "true" && !$rule->from_rep_min_pri && !$rule->from_rep_min_rel) echo "disabled" ?>>
															<option value="">-</option>
															<?php
															for($i=1; $i <= 10; $i++) {
																$selected = ($rule->from_rep_min_rel == $i) ? "selected" : "";
																echo "<option value=$i $selected>$i</option>\n";
															}
															?>
														</select>
													</div>
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
									
					<td class="nobborder" valign="top">
						<table class="transparent">
							<!-- ##### to ##### -->
							<tr>
								<td class="nobborder" valign="top">
									<table class="transparent">
										<tr>
											<th><?php echo _("Destination Host/Network") ?></th>
										</tr>
										<tr>
											<td class="nobborder">
											<div id="to_input" style="visibility:<?php echo (preg_match("/\:...\_IP/",$rule->to)) ? "hidden" : "visible" ?>">
												<table>
													<tr>
														<td class="nobborder" valign="top">
															<table align="center" class="noborder">
															<tr>
																<th style="background-position:top center"><?php echo _("Destination") ?>
																</th>
																<td class="left nobborder">
																	<select id="toselect" name="toselect[]" size="12" multiple="multiple" style="width:150px">
																	<?php
																	if ($rule->to != "ANY" && $rule->to != "" && !preg_match("/\:...\_IP/",$rule->to)) { 
																		$pre_list = explode(",", $rule->to);
																		foreach ($pre_list as $list_element) {
																			// Asset ID: Resolve by name
																			if (preg_match("/(\!)?([0-9A-Fa-f\-]{36})/", $list_element, $found)) {
																				$uuid_aux = str_replace("-", "", strtoupper($found[2]));
																				$h_obj = Asset_host::get_object($conn, $uuid_aux);
																				if ($h_obj != null) {
																					echo "<option value='".$found[1].$found[2]."'>".$found[1].$h_obj->get_name()." (".$h_obj->get_ips()->get_ips('string').")</option>\n";
																				} else {
																					$n_obj = Asset_net::get_object($conn, $uuid_aux);
																					if ($n_obj != null) {
																						echo "<option value='".$found[1].$found[2]."'>".$found[1].$n_obj->get_name()." (".$n_obj->get_ips().")</option>\n";
																					}
																				}
																				// Another one (HOME_NET, 12.12.12.12...)
																			} else {
																				echo "<option value='$list_element'>$list_element</option>\n";
																			}
																		}
																	}
																	?>
																	</select>
																	<input type="button" class="small" value=" [X] " onclick="deletefrom('toselect');"/>
																</td>
															</tr>
															</table>
														</td>
														<td valign="top" class="nobborder">
															<table class="noborder" align='center'>
															<tr><td class="left nobborder" id="inventory_loading_sources"></td></tr>
															<tr>
																<td class="left nobborder">
																	<?php echo _("Asset")?>: <input type="text" id="filterto" name="filterto"/>
																	&nbsp;<input type="button" id="button_filter_to" class="small" value="<?php echo _("Filter")?>" onclick="load_tree(this.form.filterto.value)" />&nbsp;<input type="button" id="button_addip_to" class="small" value="<?php echo _("Add IP")?>" onclick="addto('toselect',this.form.filterto.value,this.form.filterto.value)" /> 
																	<div id="containerto" class='container_ptree'></div>
																</td>
															</tr>
															<tr><td class="left nobborder"><input type="button" id="button_homenet_to" value="<?php echo _("Home Net") ?>" onclick="addto('toselect','HOME_NET','HOME_NET')"> <input type="button" id="button_nothomenet_to" value="!<?php echo _("Home Net") ?>" onclick="addto('toselect','!HOME_NET','!HOME_NET')"></td></tr>
															</table>
														</td>
													</tr>
												</table>
											</div>
											</td>
										</tr>
										<?php if ($rule->level > 1) { ?>
										<tr>
											<td class="center nobborder">
											From a parent rule: <select name="to" id="to" style="width:180px" onchange="onChangeSelectBox('to',this.value)">
											<?php
											echo "<option value=\"LIST\"></option>";
											for ($i = 1; $i <= $rule->level - 1; $i++) {
											    $sublevel = $i . ":SRC_IP";
											    $selected = ($rule->to == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>Source IP from level $i</option>";
											    $sublevel = "!" . $i . ":SRC_IP";
											    $selected = ($rule->to == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>!Source IP from level $i</option>";
											    $sublevel = $i . ":DST_IP";
											    $selected = ($rule->to == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>Destination IP from level $i</option>";
											    $sublevel = "!" . $i . ":DST_IP";
											    $selected = ($rule->to == $sublevel) ? " selected" : "";
											    echo "<option value=\"$sublevel\"$selected>!Destination IP from level $i</option>";
											}
											?>
											</select>
											</td>
										</tr>
										<?php } else { ?>
										<input type="hidden" name="to" id="to" value="ANY"></input>
										<?php } ?>
									</table>
								</td>
							</tr>
							<tr><th><?php echo _("Destination Port(s)") ?></th></tr>
							<tr><td class="nobborder">&middot; <i><?php echo _("Use comma to specify several ports.").'<br>&middot '._("Can be negated using '!'") ?></i></td></tr>
							<tr>
								<td class="center nobborder">
									<?php if ($rule->level > 1) { ?>
									From a parent rule: <select style="width:180px" name="port_to" id="port_to" onchange="onChangePortSelectBox('port_to',this.value)">
									<?php
									echo "<option value=\"LIST\"></option>";
									for ($i = 1; $i <= $rule->level - 1; $i++) {
									    $sublevel = $i . ":SRC_PORT";
									    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>Source Port from level $i</option>";
									    $sublevel = "!" . $i . ":SRC_PORT";
									    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>!Source Port from level $i</option>";
									    $sublevel = $i . ":DST_PORT";
									    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>Destination Port from level $i</option>";
									    $sublevel = "!" . $i . ":DST_PORT";
									    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
									    echo "<option value=\"$sublevel\"$selected>!Destination Port from level $i</option>";
									}
									?>
									</select>&nbsp;
									<?php } else { ?>
									<input type="hidden" name="port_to" id="port_to" value="LIST"></input>
									<?php } ?>
									<div id="port_to_input" style="display:<?php echo (preg_match("/\_PORT/",$rule->port_to)) ? "none" : "inline" ?>"><input type="text" name="port_to_list" id="port_to_list" value="<?php echo $rule->port_to ?>"></input></div>
								</td>
							</tr>
							
							<tr><td class="nobborder"><a href="" id="link_reputation_to" onclick="$('#rep_to_div').toggle(); if($('#rep_to_div').is(':visible')){ $('#rep_to_arrow').attr('src','../pixmaps/arrow_green_down.gif'); } else{ $('#rep_to_arrow').attr('src','../pixmaps/arrow_green.gif'); } return false;"><img id="rep_to_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo "<b>"._("Reputation")."</b> "._("options") ?></a></td></tr>
							<tr>
								<td class="nobborder" id="rep_to_div" style="display:none">
									<table class="noborder" align='center' width='100%'>
										<tr>
											<th style="background-position:top center;" valign='middle'>
												<?php echo _("Reputation Parameters") ?><br/>
											</th>
										</tr>
										<tr>
											<td class="left nobborder">
												<div style='text-align: left; padding:10px 0 15px 10px; clear: both;'>
													<div style='float: left; width:90px;'><?php echo _("Reputation to")?>:</div>
													<div style='float: left;'>
														<select id="to_rep" name="to_rep" style="width:60px" onchange="if(this.value=='true') $('.rep_to_select').attr('disabled',false); else $('.rep_to_select').attr('disabled',true);">
															<option value=''><?php echo _("No") ?></option>
															<option value='true' <?php if ($rule->to_rep == "true" || $rule->to_rep_min_pri || $rule->to_rep_min_rel) echo "selected" ?>><?php echo _("Yes") ?></option>
														</select>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td class="left nobborder">
												<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
													<div style='float: left; width:90px;'><?php echo _("Min Priority")?>:</div>
													<div style='float: left;'>
														<select id="to_rep_min_pri" name="to_rep_min_pri" class="rep_to_select" <?php if ($rule->to_rep != "true" && !$rule->to_rep_min_pri && !$rule->to_rep_min_rel) echo "disabled" ?>>
															<option value="">-</option>
															<?php
															for($i=1; $i <= 10; $i++) {
																$selected = ($rule->to_rep_min_pri == $i) ? "selected" : "";
																echo "<option value=$i $selected>$i</option>\n";
															}
															?>
														</select>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td class="left nobborder">
												<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
													<div style='float: left; width:90px;'><?php echo _("Min Reliablility")?>:</div>
													<div style='float: left;'>
														<select id="to_rep_min_rel" name="to_rep_min_rel" class="rep_to_select" <?php if ($rule->to_rep != "true" && !$rule->to_rep_min_pri && !$rule->to_rep_min_rel) echo "disabled" ?>>
															<option value="">-</option>
															<?php
															for($i=1; $i <= 10; $i++) {
																$selected = ($rule->to_rep_min_rel == $i) ? "selected" : "";
																echo "<option value=$i $selected>$i</option>\n";
															}
															?>
														</select>
													</div>
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="center nobborder" style="padding-top:10px">
						<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()">
						<input type="button" value="<?php echo _("Modify") ?>" onclick="save_form()">
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
