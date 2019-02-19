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
require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$db        = new ossim_db();
$conn      = $db->connect();

$page      = POST('page');
$rp        = POST('rp');

$sortname  = (!empty($_POST['sortname']) )? POST('sortname')  : GET('sortname');
$sortorder = (!empty($_POST['sortorder']))? POST('sortorder') : GET('sortorder');

$page      = (empty($page)) ? 1  : $page;
$rp        = (empty($rp))   ? 25 : $rp;

$sensor    = GET('sensor');


//Search item
$field     = POST('qtype');
$search    = GET('query');

if (empty($search) )
{ 
	$search = POST('query');
}


if (!empty($search) )
{
	$search = (mb_detect_encoding($search." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
	$search = trim($search);
		
	switch ($field)
	{
		case 'ip':
			ossim_valid($search, OSS_IP_ADDR, 'illegal:' . _('IP'));
			
			$search = escape_sql($search, $conn);
			$where  = " AND ip like '%$search%' OR hostname like '%$search%'";
		break;
		
		case 'user':
		case 'hostname':
			ossim_valid($search, OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT, 'illegal:' . _("$field"));
			
			$search = escape_sql($search, $conn);
			$where  = " AND $field like '%$search%'";
		break;
		
		default:
			ossim_set_error(_("Error in the 'Quick Search Field' field (missing required field)"));
	}
}


ossim_valid($sensor,  		OSS_HEX,								   'illegal:' . _('Sensor'));
ossim_valid($sortname,  	",", OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,   'illegal:' . _('Order Name'));
ossim_valid($sortorder, 	OSS_LETTER, OSS_SCORE, OSS_NULLABLE,       'illegal:' . _('Sort Order'));
ossim_valid($field, 		OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,         'illegal:' . _('Field'));
ossim_valid($page, 			OSS_DIGIT,                                 'illegal:' . _('Page'));
ossim_valid($rp, 			OSS_DIGIT,                                 'illegal:' . _('Rp'));


if (ossim_error())
{
	$db->close();
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}

$sensor     = escape_sql($sensor, $conn);

$sortname   = (!empty($sortname) )? $sortname  : "hostname";
$sortname   = ($sortname == 'ip')? "INET_ATON(ip)" : $sortname;
$sortorder  = (!empty($sortorder) && strtolower($sortorder) == 'desc')? 'DESC' : 'ASC';
$order      = $sortname." ".$sortorder;

$start      = (($page - 1) * $rp );
$limit      = "LIMIT $start, $rp";


/* Storing the sensor in session to remember the selection in the sensor combo */
$_SESSION['ossec_sensor'] = $sensor;


Ossec_agentless::syncronize_ossec_agentless($conn, $sensor);


$extra = (!empty($where))? $where." ORDER BY $order $limit" : " ORDER BY $order $limit";

list($agentless_list, $total) = Ossec_agentless::get_list($conn, $sensor, $extra);

$xml = "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach($agentless_list as $agentless)
{
    $ip   		= $agentless->get_ip();
	$hostname 	= "<a style='font-weight:bold;' href='/ossim/ossec/views/agentless/al_form.php?sensor=$sensor&ip=".urlencode($ip)."'>" .$agentless->get_hostname() . "</a>";
	$user 		= $agentless->get_user();
    $status     = $agentless->get_status();
	
    if ($status == 0 )
    {
		$status = "<img src='". OSSIM_IMG_PATH."/tables/cross.png' alt='"._('Disabled')."' title='"._('Disabled')."'/>";
	}
	else if ($status == 1 )
	{
        $status = "<img src='". OSSIM_IMG_PATH."/tables/exclamation.png' alt='"._('Modified')."' title='"._('Not configured')."'/>";
    }
	else
	{
		$status = "<img src='". OSSIM_IMG_PATH."/tables/tick.png' alt='"._('Enabled')."' title='"._('Enabled')."'/>";
	}
		
	$desc = ($agentless->get_descr() == '')? "&nbsp;" : $agentless->get_descr();
  
  
    $xml.= "<row id='$ip'>";
		$xml.= "<cell><![CDATA[" .  $hostname  . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $ip        . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $user      . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $status    . "]]></cell>";
		$xml.= "<cell><![CDATA[" .  $desc      . "]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";

echo $xml;
$db->close();
?>
