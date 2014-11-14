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
$q = addslashes(urldecode(GET('q')));
$product_type = GET('product_type');
if ($product_type == "null") $product_type = "";
if ($plugin_id < 1) $plugin_id = "";

ossim_valid($plugin_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("ID"));
ossim_valid($q, OSS_TEXT, OSS_NULLABLE);
ossim_valid($product_type, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Product Type"));

if (ossim_error()) {
    return false;
}

$db = new ossim_db();
$conn = $db->connect();
$more = "";
if ($q != "") {
        $more = (preg_match("/^\d+$/",$q)) ? "AND sid like '$q%'" : "AND name like '%$q%'";
}

$plugin_id_list = ($product_type) ? get_plugin_list($conn, $product_type)  : $plugin_id;
$w = ($plugin_id_list != "") ? "plugin_id in (".$plugin_id_list.")" : "1=1";
$plugin_list = Plugin_sid::get_list($conn, "WHERE $w $more ORDER BY plugin_id, sid LIMIT 150");

if ($plugin_list[0]->foundrows>150) echo "Total=".$plugin_list[0]->foundrows."\n";
foreach($plugin_list as $plugin) {
    $id = $plugin->get_sid();
    $name = "$id - ".trim($plugin->get_name());
    //if (strlen($name)>73) $name=substr($name,0,70)."...";
    echo "$id=$name\n";
}
$db->close($conn);

?>