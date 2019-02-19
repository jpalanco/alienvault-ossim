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

Session::logcheck('analysis-menu', 'EventsForensics');


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$page = (!empty($_POST['page'])) ? POST('page') : 1;
$rp   = (!empty($_POST['rp'])  ) ? POST('rp')   : 20;


$order = GET('sortname');
$order = (empty($order)) ? POST('sortname') : $order;


ossim_valid($order, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _('Order'));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE,             'illegal:' . _('Page'));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE,               'illegal:' . _('Rp'));


if (ossim_error())
{
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}


if (!empty($order))
{
	$order .= (POST('sortorder') == 'asc') ? '' : ' desc';
}
else
{
	$order = "name";
}

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$db   = new ossim_db();
$conn = $db->connect();
$xml  = '';

$db_list = Databases::get_list($conn, "ORDER BY $order $limit");

if ($db_list[0])
{
    $total = $db_list[0]->get_foundrows();

	if ($total == 0)
	{
		$total = count($db_list);
	}
}
else
{
	$total = 0;
}


$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach($db_list as $db_server)
{
    $id   = $db_server->get_id();
    $name = $db_server->get_name();
    $ip   = $db_server->get_ip();

    $xml .= "<row id='".$id."'>";
    $link_modify = "<a style='font-weight:bold;' href='./newdbsform.php?id=$id'>".Util::htmlentities($name)."</a>";

	$xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $ip . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $db_server->get_port() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $db_server->get_user() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . preg_replace("/./","*",$db_server->get_pass()) . "]]></cell>";

    $xml.= "<cell><![CDATA[".$db_server->get_html_icon()."]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;
$db->close();
?>
