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
Session::logcheck('configuration-menu', 'AlienVaultInventory');


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

$s_type_ids = array(
	'nmap' => 5,
	'ocs'  => 3,
    'wmi'  => 4
);

$frequencies = array(
    '3600'    => 'Hourly', 
    '86400'   => 'Daily', 
    '604800'  => 'Weekly', 
    '2419200' => 'Monthly'
);


$s_type = (GET('s_type') == 'nmap' || GET('s_type') == 'ocs' || GET('s_type') == 'wmi') ? GET('s_type') : 'nmap';


$order = GET('sortname');
$order = (empty($order)) ? POST('sortname') : $order;

$page = (!empty($_POST['page'])) ? POST('page') : 1;
$rp   = (!empty($_POST['rp'])  ) ? POST('rp')   : 20;

ossim_valid($order,  OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,   'illegal:' . _('Order'));
ossim_valid($page,   OSS_DIGIT, OSS_NULLABLE,             'illegal:' . _('Page'));
ossim_valid($rp,     OSS_DIGIT, OSS_NULLABLE,             'illegal:' . _('Rp'));
ossim_valid($s_type, OSS_ALPHA,                           'illegal:' . _('s_type'));

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
	$order = 'name';
}

$db   = new ossim_db();
$conn = $db->connect();

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";

$task_list = Inventory::get_list($conn, '', $s_type_ids[$s_type]);
$total     = count($task_list);

$xml .= "<rows>\n";
$xml .= "<page>$page</page>\n";
$xml .= "<total>$total</total>\n";


foreach($task_list as $task) 
{ 
    //Parameters    
    $sensor_name = Av_sensor::get_name_by_id($conn, $task['task_sensor']);
	
	if ($sensor_name == '')
	{
    	 $sensor_name = _('Unknown');
	}	
	
	$link_modify = "<a style='font-weight:bold;' href=\"./task_edit.php?id=".$task['task_id']."&s_type=$s_type\">".utf8_encode($task['task_name'])."</a>";
	
	if ($s_type == 'wmi') 
	{
		preg_match('/wmipass:(.*)/', $task['task_params'], $found);
		
		if ($found[1] != '') 
		{
			$task['task_params'] = preg_replace('/wmipass:(.*)/', '', $task['task_params']);
			$task['task_params'] = $task['task_params'] . 'wmipass:' . preg_replace('/./', '*', $found[1]);
		}
	}
	
	$task['enabled'] = ($task['task_enable']) ? "<img src='../pixmaps/tables/tick.png'/>" : "<img src='../pixmaps/tables/cross.png'/>";
	
	
	//XML
	
	$xml .= "<row id='".$task['task_id']."'>";    
    $xml .= "<cell><![CDATA[".$link_modify."]]></cell>";
    $xml .= "<cell><![CDATA[".$sensor_name."]]></cell>";    	

	if ($s_type != 'ocs')
	{
		if($s_type != 'nmap') 
		{
			$xml .= "<cell><![CDATA[".$task['task_params']."]]></cell>";
		}
		else 
		{ 
		    // Clean nmap options: 192.168.1.193 192.168.1.10#-T5 -A -sS -p 1-65535
			$xml .= "<cell><![CDATA[" . str_replace(" ", ", ", preg_replace("/#.*/", "", $task['task_params']))."]]></cell>";
		}
	}
	
    $xml.= "<cell><![CDATA[".$frequencies[$task['task_period']]."]]></cell>";
    $xml.= "<cell><![CDATA[".(($task['task_enable']) ? "<img src='../pixmaps/tables/tick.png'>" : "<img src='../pixmaps/tables/cross.png'>")."]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;
$db->close();
?>