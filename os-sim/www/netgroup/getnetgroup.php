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


Session::logcheck('environment-menu', 'PolicyNetworks');


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";	


$db   = new ossim_db();
$conn = $db->connect();


$order = GET('sortname');
$order = (empty($order)) ? POST('sortname') : $order;

//Search item
$search = GET('query');
$field  = POST('qtype');

if (empty($search))
{ 
	$search = POST('query');
}

$page  = POST('page');

$page = (!empty($_POST['page'])) ? POST('page') : 1;
$rp   = (!empty($_POST['rp'])  ) ? POST('rp')   : 20;


$net_group_name = GET('net_group_name');

ossim_valid($net_group_name, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,  'illegal:' . _('Net group name'));
ossim_valid($order, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE,          'illegal:' . _('Order'));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE,                      'illegal:' . _('Page'));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE,                        'illegal:' . _('Rp'));
ossim_valid($field, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,           'illegal:' . _('Field'));

if (!empty($search))
{
	$search = (mb_detect_encoding($search.' ', 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
		
	if ($field == 'name')
	{
		ossim_valid($search, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,    'illegal:' . _('Name'));
		
		$search = escape_sql($search, $conn);
		$where  = "g.name LIKE '%$search%'";
	}
	else
	{	
		ossim_set_error(_("Error in the 'Quick Search Field' field (missing required field)"));
	}
}

if (ossim_error()) 
{
	$db->close();
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}

if (!empty($order)) 
{
	$order .= (POST('sortorder') == "asc") ? "" : " desc";
}
else
{
	$order = "name";
}

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$xml   = '';

$net_group_list = Net_group::get_list($conn, $where, "ORDER BY $order $limit");

if ($net_group_list[0]) 
{
    $total = $net_group_list[0]->get_foundrows();
    
	if ($total == 0)
	{ 
		$total = count($net_group_list);
	}
} 
else
{ 
	$total = 0;
}


	
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach($net_group_list as $net_group) 
{
    $name = $net_group->get_name();
    $id   = $net_group->get_id();
	$xml .= "<row id='".$id."'>";
    $link_modify = "<a class='a_name' style='font-weight:bold;' href=\"./netgroup_form.php?id=".$id."\">" . Util::htmlentities($name) . "</a>";
    $xml .= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $nets = "";
    
	if ($network_list = $net_group->get_networks($conn))
	{
		foreach($network_list as $network)
		{
			$net_id = $network->get_net_id();
			
			$filters = array(
			     'where' => "id = UNHEX('".$net_id."')"
			);
						
			$_aux_net_list = Asset_net::get_list($conn, '', $filters);
			$aux_net_list = $_aux_net_list[0];			
			
			$nets.= (($nets == '') ? '' : ', ') . Util::htmlentities($aux_net_list[$net_id]['name']);
		}
    }
	
    $xml.= "<cell><![CDATA[" . html_entity_decode($nets) . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $net_group->get_threshold_c() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $net_group->get_threshold_a() . "]]></cell>";
    /* Nessus
    if ($scan_list = Net_group_scan::get_list($conn, "WHERE net_group_name = '$name' AND plugin_id = 3001")) {
        $scan_types = "<img src='../pixmaps/tables/tick.png'>";
    } else {
        $scan_types = "<img src='../pixmaps/tables/cross.png'>";
    }
    $xml.= "<cell><![CDATA[" . $scan_types . "]]></cell>"; */
   
    $desc = $net_group->get_descr();
    if ($desc == "") 
    {
		$desc = "&nbsp;";
	}
	
    $xml.= "<cell><![CDATA[" . utf8_encode($desc) . "]]></cell>";
    
    // KDB
    $rep = '';
    if ($linkedocs = Repository::have_linked_documents($conn, $id, 'net_group')) 
    {
    	$rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('../repository/repository_list.php?keyname=" . $id . "&type=net_group&nosize=1')\" class=\"blue\" target=\"main\">[" . $linkedocs . "]</a>&nbsp;";
    }
    
	$rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('../repository/asset_repository.php?id=" . $id . "&name=" . urlencode($name) . "&linktype=net_group')\"><img src=\"../pixmaps/tables/table_row_insert.png\" border=0 title=\"Add KDB\" alt=\"Add KDB\" align=\"absmiddle\"></a>";
    
    $xml.= "<cell><![CDATA[" . utf8_encode($rep) . "]]></cell>";    

    // Notes
    $rep = '';
    if ($notes = Notes::howmanynotes($conn, $id, 'net_group')) 
    {
    	$rep .= "<a href=\"javascript:;\" onclick=\"GB_notes('../asset_details/ajax/view_notes.php?type=net_group&id=" . $id . "')\" class=\"blue\" target=\"main\">[" . $notes . "]</a>&nbsp;";
    }
    
    $rep .= "<a href=\"javascript:;\" onclick=\"GB_notes('../asset_details/ajax/view_notes.php?type=net_group&id=" . $id . "')\"><img src=\"../pixmaps/notes.png\" border=0 title=\"View Notes\" alt=\"View Notes\" width='16px' align=\"absmiddle\"></a>";
    $xml.= "<cell><![CDATA[" . utf8_encode($rep) . "]]></cell>";
       
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;
$db->close();
?>