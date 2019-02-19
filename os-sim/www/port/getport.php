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
	$order = "port_number";
}

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$db   = new ossim_db();
$conn = $db->connect();

$xml  = "";
$port_list = Port::get_list($conn, " AND port_number > 0", "ORDER BY $order $limit");


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

foreach($port_list as $port) 
{

	$port_number = $port->get_port_number();
    $protocol    = $port->get_protocol_name();
    $service     = ($port->get_service() == '') ? "&nbsp;" : $port->get_service();
    $descr       = ($port->get_descr() == '') ? "&nbsp;" : $port->get_descr();
    
    $id   = $port_number . "@@".$protocol;
	
	
    $xml .= "<row id='".$id."'>";
    $link_modify = "<a style='font-weight:bold;' href=\"./modifyportform.php?id=".$id."\">" .Util::htmlentities($port_number) . "</a>";
    
	$xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
	$xml.= "<cell><![CDATA[" . $protocol . "]]></cell>";
	$xml.= "<cell><![CDATA[" . $service . "]]></cell>";
    $xml.= "<cell><![CDATA[" . utf8_encode($descr) . "]]></cell>";
	
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;
$db->close();
?>
