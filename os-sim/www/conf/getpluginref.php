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
Session::logcheck("configuration-menu", "CorrelationCrossCorrelation");


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$db    = new ossim_db();
$conn  = $db->connect();

$page = ( !empty($_POST['page']) ) ? POST('page') : 1;
$rp   = ( !empty($_POST['rp'])   ) ? POST('rp')   : 50;

/*
$order = GET('sortname');
if (empty($order))  $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
*/

$search = GET('query');

if ( empty($search) ){ 
	$search = POST('query');
}

$field = POST('qtype');

//ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT,                             'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT,                               'illegal:' . _("rp"));
ossim_valid($search, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,   'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA,  OSS_PUNC, OSS_NULLABLE,   'illegal:' . _("field"));


if ( !empty($search) )
{
	$search = (mb_detect_encoding($search." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
	$search = escape_sql($search, $conn);
	
	switch ($field)
	{
		case "plugin_sid":
			$where .= ",plugin_sid WHERE plugin_sid.plugin_id=plugin_reference.plugin_id AND plugin_sid.sid=plugin_reference.plugin_sid AND plugin_sid.name like '%" . $search . "%'";
		break;
		
		case "plugin_id":
			$where .= ",plugin WHERE plugin.id=plugin_reference.plugin_id AND plugin.name like '%" . $search . "%'";
		break;
		
		case "reference_sid":
			$where .= ",plugin_sid WHERE plugin_sid.plugin_id=plugin_reference.reference_id AND plugin_sid.sid=plugin_reference.reference_sid AND plugin_sid.name like '%" . $search . "%'";
		break;
		
		case "reference_id":
			$where .= ",plugin WHERE plugin.id=plugin_reference.reference_id AND plugin.name like '%" . $search . "%'";
		break;
		
		default:
			ossim_set_error(_("Error in the 'Quick Search Field' field (missing required field)"));
	}
}

if ( ossim_error() ) 
{
	$db->close($conn);
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}

$order = "plugin_reference.plugin_id";

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$xml = "";
$xml.= "<rows>\n";

if ($plugin_list = Plugin_reference::get_list($conn, "$where ORDER BY $order $limit")) 
{
  	
	$total = $plugin_list[0]->get_foundrows();
    if ($total == 0){ 
		$total = count($plugin_list);
	}
    
    $xml.= "<page>$page</page>\n";
    $xml.= "<total>$total</total>\n";
	
    foreach($plugin_list as $plugin) 
	{
        $id      = $plugin->get_plugin_id();
        $sid     = $plugin->get_plugin_sid();
        $ref_id  = $plugin->get_reference_id();
        $ref_sid = $plugin->get_reference_sid();
        $xml    .= "<row id='$id"."_"."$sid"."_"."$ref_id"."_"."$ref_sid'>";
		
		$lnk_id      = "<a href='newpluginrefform.php?plugin_id1=$id&plugin_sid1=$sid&plugin_id2=$ref_id&plugin_sid2=$ref_sid'>". Plugin::get_name_by_id($conn,$id) ."</a>";
		$lnk_sid     = "<a href='newpluginrefform.php?plugin_id1=$id&plugin_sid1=$sid&plugin_id2=$ref_id&plugin_sid2=$ref_sid'>". Plugin_sid::get_name_by_idsid($conn,$id,$sid) ."</a>";
		$lnk_ref     = "<a href='newpluginrefform.php?plugin_id1=$id&plugin_sid1=$sid&plugin_id2=$ref_id&plugin_sid2=$ref_sid'>". Plugin::get_name_by_id($conn,$ref_id) ."</a>";
		$lnk_ref_sid = "<a href='newpluginrefform.php?plugin_id1=$id&plugin_sid1=$sid&plugin_id2=$ref_id&plugin_sid2=$ref_sid'>". Plugin_sid::get_name_by_idsid($conn,$ref_id,$ref_sid) ."</a>";
		
		$xml.= "<cell><![CDATA[" . $lnk_id . "]]></cell>";
		$xml.= "<cell><![CDATA[" . $lnk_sid . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $lnk_ref . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $lnk_ref_sid . "]]></cell>";
        $xml.= "</row>\n";
    }
}

$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>
