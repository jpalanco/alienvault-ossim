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

Session::logcheck("configuration-menu", "PolicyPorts");


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$page = ( !empty($_POST['page']) ) ? POST('page') : 1;
$rp   = ( !empty($_POST['rp'])   ) ? POST('rp')   : 20;

$order = GET('sortname');
$order = ( empty($order) ) ? POST('sortname') : $order;


ossim_valid($order, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,   'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE,               'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE,                 'illegal:' . _("rp"));


if ( ossim_error() ) 
{
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}

if ( !empty($order) ) {
	$order .= (POST('sortorder') == "asc") ? "" : " desc";
}
else{
	$order = "name";
}

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$db   = new ossim_db();
$conn = $db->connect();

$xml  = "";
$port_list = Port_group::get_list($conn, "ORDER BY $order $limit");


if ($port_list[0]) 
{
    $total = $port_list[0]->get_foundrows();
	
    if ($total == 0) {
		$total = count($port_list);
	}
} 
else{ 
	$total = 0;
}

$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach($port_list as $port_group) 
{
    $name = $port_group->get_name();
    $id   = $port_group->get_id();
    $xml .= "<row id='".$id."'>";
    $link_modify = "<a style='font-weight:bold;' href=\"./newportgroupform.php?id=".$id."\">" .Util::htmlentities($name) . "</a>";
    $xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    
	$ports = array();
    
	foreach($port_group->get_reference_ports($conn, $id) as $port) {
        $ports[]= $port->get_port_number() . "-" . $port->get_protocol_name();
    }
    
    $ports = Port_group::group_ports($ports);
    
	$xml.= "<cell><![CDATA[" . implode(', ', $ports) . "]]></cell>";
    
	if (Session::show_entities()) {
    	$xml .= "<cell><![CDATA[" . Session::get_entity_name($conn, $port_group->get_ctx()) . "]]></cell>";
    }
    
	$desc = $port_group->get_descr();
    if ($desc == "") {
		$desc = "&nbsp;";
	}
	
    $xml.= "<cell><![CDATA[" . utf8_encode($desc) . "]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;
$db->close();
?>