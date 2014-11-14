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

Session::logcheck("configuration-menu", "PolicyActions");


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$page = (!empty($_POST['page'])) ? POST('page') : 1;

$rp   = (!empty($_POST['rp'])) ? POST('rp')   : 20;


$order = GET('sortname');

$order = (empty($order)) ? POST('sortname') : $order;


ossim_valid($order, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT,                           'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT,                             'illegal:' . _("rp"));

if (ossim_error()) 
{
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}


if (!empty($order)) 
{
	$order .= (POST('sortorder') == "asc") ? "" : " desc";
}
else
{
	$order = "descr";
}


$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

list($db, $conn) = Ossim_db::get_conn_db();

$xml = "";

$action_list = Action::get_list($conn, "ORDER BY $order $limit");

if (is_array($action_list)) 
{
    if ($action_list[0]) 
	{
        $total = $action_list[0]->get_foundrows();
        
        if ($total == 0) 
        {
            $total = count($action_list);
        }
    } 
	else
	{ 
		$total = 0;
	}
			
    $xml.= "<rows>\n";
    $xml.= "<page>$page</page>\n";
    $xml.= "<total>$total</total>\n";
	
    foreach ($action_list as $action) 
	{
		
		$id   =	$action->get_id(); 			
		$name = $action->get_name();
		$name = ( empty($name) ) ? _("Unknown") : "<a href='actionform.php?id=$id'>$name</a>";
		
		$desc = $action->get_descr();
		$desc = ( empty($desc) ) ? "&nbsp;" : $desc;
				
        $xml.= "<row id='" . $id . "'>";
		$xml.= "<cell><![CDATA[" . $name . "]]></cell>";
		$xml.= "<cell><![CDATA[" . $action->get_action_type_text($conn) . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";
        $xml.= "</row>\n";
    }
	
    $xml.= "</rows>\n";
}
else
{
	$xml = "<rows>\n<page>$page</page>\n<total>0</total>\n</rows>\n";
}

echo $xml;

$db->close();
