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

Session::logcheck("configuration-menu", "PluginGroups");

function get_plugin_list($conn, $product_type) {
	$list = "";
	$product_types = implode(",", $product_type);
	$plugin_list = Plugin::get_list($conn, "WHERE product_type IN ($product_types)");
	foreach ($plugin_list as $plugin) {
		$list .= ($list != "") ? ",".$plugin->get_id() : $plugin->get_id();
	}
	if ($list == "") {
		$list = "0";
	}
	return $list;
}

$plugin_id = GET('plugin_id');
$product_type = GET('product_type');
$directive_id = GET('directive_id');
$engine_id = GET('engine_id');
$xml_file = GET('xml_file');
$rule_id = GET('rule_id');
if ($product_type == "null") $product_type = "";

ossim_valid($plugin_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("ID"));
ossim_valid($product_type, OSS_DIGIT, '\,', OSS_NULLABLE, 'illegal:' . _("Product Type"));
ossim_valid($rule_id, OSS_DIGIT, '\-', OSS_NULLABLE, 'illegal:' . _("rule ID"));
ossim_valid($directive_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Directive ID"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Engine ID"));
if (ossim_error()) {
    die(ossim_error());
}


//DB Options
$db      = new ossim_db();
$conn    = $db->connect();

//Rule Object
if ($rule_id != "") {
	$directive_editor = new Directive_editor($engine_id);
	$rule = $directive_editor->get_rule($directive_id, $xml_file, $rule_id);
}
//Container with the html code that the ajax is gonna response
$options = "<select id='pluginsids' class='multiselect_sids' multiple='multiple' name='sids[]' style='display:none;width:850px;height:300px'>\n";

if ($rule->plugin_sid != "ANY" && $rule->plugin_sid != "" && $rule->plugin_id != "" && !preg_match("/\:PLUGIN\_SID/",$rule->plugin_sid) && $rule->plugin_id == $plugin_id) {
	$sids = explode(",",$rule->plugin_sid);
	$range = "";
	$sin = array();
	foreach ($sids as $sid) {
			if (preg_match("/(\d+)-(\d+)/",$sid,$found)) {
					$range .= " OR (sid BETWEEN ".$found[1]." AND ".$found[2].")"; 
			} else { 
					$sin[] = $sid;
			}
	}
	if (count($sin)>0) $where = "sid in (".implode(",",$sin).") $range";
	else $where = preg_replace("/^ OR /","",$range);
	
	$plugin_id_list = ($product_type) ? get_plugin_list($conn, $product_type)  : $rule->plugin_id;
	$w = ($plugin_id_list != "") ? "plugin_id in (".$plugin_id_list.")" : "1=1";
	
	$plugin_list = Plugin_sid::get_list($conn, "WHERE $w AND ($where)");
	foreach($plugin_list as $plugin) {
		$id_plugin = $plugin->get_sid();
		$name = "$id_plugin - ".trim($plugin->get_name());
		if (strlen($name)>73) $name=substr($name,0,70)."...";
		$options .= "<option value='$id_plugin' selected>$name</option>\n";
	}
}

$options .= "</select><br><br><span id='msg'></span><br><br>";

$response['error'] = false ;
$response['data']  = $options;

echo json_encode($response);


$db->close($conn);

?>
