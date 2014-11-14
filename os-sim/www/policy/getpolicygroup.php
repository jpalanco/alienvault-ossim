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


Session::logcheck("configuration-menu", "PolicyPolicy");


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";


//DB
$db    = new ossim_db();
$conn  = $db->connect();

//Version
$pro   = Session::is_pro();

//Parameters
$page  = POST('page');
$rp    = POST('rp');


$page = ( !empty($_POST['page']) ) ? POST('page') : 1;
$rp   = ( !empty($_POST['rp'])   ) ? POST('rp')   : 25;

$order = GET('sortname');
$order = ( empty($order) ) ? POST('sortname') : $order;

ossim_valid($order,	OSS_ALPHA, OSS_SCORE, OSS_DOT, OSS_NULLABLE,  'illegal:' . _("order"));
ossim_valid($page, 	OSS_DIGIT, 									  'illegal:' . _("page"));
ossim_valid($rp, 	OSS_DIGIT, 									  'illegal:' . _("rp"));

if (ossim_error()) 
{
	echo ossim_error();
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}


if (!empty($order)) 
{
	if ($order == 'order')
	{
		$order = "policy_group.order";
	}
	
	$order.= (POST('sortorder') == "asc") ? "" : " desc";
}
else
{
	$order = "policy_group.order";
}


$total = 0;
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$policygroup_list = Policy_group::get_list($conn, '', " and policy_group.id <> UNHEX('00000000000000000000000000000000') ORDER BY $order $limit");

if ($policygroup_list[0]) 
{
    
	$total = $policygroup_list[0]->get_foundrows();
	
    if ($total == 0) 
    {
		$total = count($policygroup_list);
	}
}

$xml = "";
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach ($policygroup_list as $policygrp)
{
	$id  = $policygrp->get_group_id();
	
    $xml.= "<row id='$id'>";
    $xml.= "<cell><![CDATA[" . $policygrp->get_order() . "]]></cell>";
    
	$pgname = $policygrp->get_name();
	$pgname = ( empty($pgname) ) ? _("Unknown") : "<a href='newpolicygroupform.php?id=$id'>$pgname</a>";
	
	$xml.= "<cell><![CDATA[" . $pgname . "]]></cell>";
	
	if($pro)
	{
		$entity = Acl::get_entity_name($conn,$policygrp->get_ctx()); 
		$xml.= "<cell><![CDATA[" . $entity . "]]></cell>";
	}
		
    $xml.= "<cell><![CDATA[" . $policygrp->get_descr() . "]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";

echo $xml;

$db->close();
