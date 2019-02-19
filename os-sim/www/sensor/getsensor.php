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

Session::logcheck('configuration-menu', 'PolicySensors');


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";

require_once 'get_sensors.php';


$db   = new ossim_db();
$conn = $db->connect();


$order = GET('sortname');
$order = (empty($order)) ? POST('sortname') : $order;


//Search item
$field  = POST('qtype');
$search = GET('query');

if (empty($search))
{ 
    $search = POST('query');
}

$onlyactive = (intval(GET('onlyactive')) > 0) ? 1 : 0;

$page = (!empty($_POST['page'])) ? POST('page') : 1;
$rp   = (!empty($_POST['rp'])  ) ? POST('rp')   : 20;


ossim_valid($order,  OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, 'illegal:' . _('Order'));
ossim_valid($page,   OSS_DIGIT,                                     'illegal:' . _('Page'));
ossim_valid($rp,     OSS_DIGIT,                                     'illegal:' . _('Rp'));
ossim_valid($field,  OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,             'illegal:' . _('Field'));


if (!empty($search))
{
    $search = (mb_detect_encoding($search." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
    $search = trim($search);
        
    switch ($field)
    {
        case 'name':
            ossim_valid($search, OSS_HOST_NAME, 'illegal:' . _('Name'));
            
            $search = escape_sql($search, $conn);
            $where  = "name like '%$search%'";
        break;
        
        case 'ip':
            ossim_valid($search, OSS_IP_ADDR, 'illegal:' . _('IP'));
            
            if ( preg_match("/\d+\.\d+\.\d+\.\d+/",$search) )
            {
                $ip    = bin2hex(@inet_pton($search));
                $where = "ip = unhex('$ip')";
            }
        break;
        
        default:
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
    $order .= (POST('sortorder') == 'asc') ? '' : ' desc';
}
else
{
    $order = "name";
}

$start = (($page - 1) * $rp);
$limit = "$start, $rp";

if (is_array($_SESSION['_sensor_list']))
{
    $sensor_list = $_SESSION['_sensor_list'];
}
else
{
    list($sensor_list, $err) = server_get_sensors();
}
    
$sensor_stack            = array();
$sensor_stack_off        = array();
$sensor_configured_stack = array();

if (count($sensor_list) > 0) 
{
    foreach($sensor_list as $sensor => $plugins)
    {
        if (in_array($sensor, $sensor_stack))
        {
            continue;
        }

        array_push($sensor_stack, $sensor);
    }
}

$active_sensors = 0;
$total_sensors  = 0;

$filters = array(
    'where'    => $where,
    'order_by' => $order,
    'limit'    => $limit
);

list($sensor_list, $total) = Av_sensor::get_list($conn, $filters, FALSE, TRUE);


$xml  = '';
$xml .= "<rows>\n";
$xml .= "<page>$page</page>\n";
$xml .= "<total>$total</total>\n";


foreach($sensor_list as $sensor_id => $s_data)
{
    $ip = $s_data['ip'];

    //The sensor is not active and we want only the active sensors
    if (!in_array($ip, $sensor_stack) && $onlyactive > 0)
    {
        continue;
    }
    
    if (in_array($ip, $sensor_stack) && $onlyactive < 0)
    {
        continue;
    }


    $xml.= "<row id='$sensor_id'>";
    $xml.= "<cell><![CDATA[" . "<a style='font-weight:bold;' href=\"./interfaces.php?sensor_id=".$sensor_id ."\">$ip</a>" . "]]></cell>";
    $total_sensors++;
    $xml.= "<cell><![CDATA[ <span class='a_name'>".Util::htmlentities($s_data['name'])."</span>]]></cell>";
    
    if (Session::show_entities())
    {
        $entities = implode(', ', $s_data['ctx']);
        
        $xml.= "<cell><![CDATA[".utf8_encode($entities)."]]></cell>";
    }

    $xml.= "<cell><![CDATA[".$s_data['priority']."]]></cell>";
    $xml.= "<cell><![CDATA[".$s_data['port']."]]></cell>";
    $xml.= "<cell><![CDATA[".$s_data['properties']['version']."]]></cell>";

    if (in_array($ip , $sensor_stack))
    {
        $xml.= "<cell><![CDATA[<img class='s_active' src='../pixmaps/tables/tick.png'>]]></cell>";
        
        $active_sensors++;
        
        array_push($sensor_configured_stack, $ip);
    } 
    elseif (in_array($ip , $sensor_stack_off))
    {
        $xml.= "<cell><![CDATA[<img src='../pixmaps/tables/warning.png' title='the following sensor(s) are being reported as enabled by the server but are not configured' alt='the following sensor(s) are being reported as enabled by the server but are not configured'>]]></cell>";
    } 
    else
    {
        $xml.= "<cell><![CDATA[<img src='../pixmaps/tables/cross.png'>]]></cell>";
    }


    $desc = $s_data['descr'];
    
    if ($desc == '') 
    {
        $desc = "&nbsp;";
    }
    
    $xml.= "<cell><![CDATA[". $desc ."]]></cell>";
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;

$db->close();
