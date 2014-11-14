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
Session::logcheck("environment-menu", "ReportsWireless");

require_once 'Wireless.inc';


$sensor = GET('sensor');
$file   = GET('file');

ossim_valid($sensor, OSS_IP_ADDR, 'illegal: sensor');
ossim_valid($file, OSS_TEXT,      'illegal: file');

if (ossim_error()) 
{
    die(ossim_error());
}

//Sensor list with perms

$db   = new ossim_db();
$conn = $db->connect();

if (!validate_sensor_perms($conn, $sensor, ", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1")) 
{        
    echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
   
    $db->close();
    exit;
}

$db->close();


$path = "/var/ossim/kismet/parsed/$sensor/$file";

$tree = array(
    'title'        => Util::utf8_encode2(_("Networks")),
    'key'          => "key1",
    'isFolder'     => TRUE,
    'icon'         => "../../pixmaps/any.png",
    'expand'       => TRUE,
    'hideCheckbox' => TRUE,
    'isLazy'       => FALSE,
    'children'     => array()
);


if (file_exists($path) == FALSE) 
{    
    $tree['children'] = array(
        'title'        => '<span>'.Util::utf8_encode2(_('No networks found')).'</span>',
        'key'          => 'load_error',
        'icon'         => '',
        'hideCheckbox' => TRUE,
        'noLink'       => TRUE,
        'hideCheckbox' => TRUE         
    );
    
    $json_encode = @json_encode($tree);

    echo $json_encode;
    
    exit();    
}


    
$xml = simplexml_load_file($path);

$i = 1;
     
foreach ($xml as $k => $v) 
{
	if ($k == 'wireless-network') 
	{
        $val = trim(str_replace("'", '', $v->SSID));
        
        if ($val == '') 
        {
            $val = "<no ssid>";
        }
                                          
        $j          = 1;
        $t_children = array();
                        		
		$title  = (strlen($val) > 30) ? substr($val, 0, 30).'...' : $val;
		$title .= ' <font style="font-size:80%; font-weight:normal">('.$v->BSSID.')</font>'; 
				
		$tooltip = Util::utf8_encode2(Util::htmlentities($val)).' '.$v->BSSID; 
		
		$tree['children'][] = array(
            'title'        => $title,
            'tooltip'      => $tooltip,
            'key'          => "key1$i",
            'isFolder'     => TRUE,
            'icon'         => "../../pixmaps/theme/wifi.png",
            'expand'       => FALSE,
            'hideCheckbox' => TRUE,
            'isLazy'       => FALSE      
        );
		        
		foreach ($v as $k1 => $v1) 
		{
    		if ($k1 == 'wireless-client') 
    		{
                foreach ($v1 as $k2 => $v2) 
                {
                    if ($k2 == 'client-mac') 
                    {
                        $title = Util::utf8_encode2($v2);
                                                                                
                        $t_children[] = array(
                            'title'        => $title,
                            'key'          => "key1$i$j",
                            'isFolder'     => TRUE,
                            'icon'         => "../../pixmaps/theme/net.png"
                        );                         
                        
                        $j++;
                    }
                }
            }
        }
                        
        if (is_array($t_children) && !empty($t_children))
        {
            $tree['children'][$i-1]['children'] = $t_children;
        }        
                            
        $i++;
    }
}


$json_encode = @json_encode($tree);

if (json_last_error() != JSON_ERROR_NONE)
{
    $tree = array(
        'title'        => '',
        'key'          => 'tree_error',
        'isFolder'     => TRUE,
        'icon'         => '../../pixmaps/any.png',
        'hideCheckbox' => TRUE, 
        'expand'       => TRUE,
        'noLink'       => TRUE,
        'hideCheckbox' => TRUE,
        'addClass'     => 'size12',
        'children'     => array(
            'title'        => '<span>'.Util::utf8_encode2(_('Load error')).'</span>',
            'key'          => 'load_error',
            'icon'         => '',
            'hideCheckbox' => TRUE,
            'noLink'       => TRUE,
            'hideCheckbox' => TRUE,
            'addClass'     => 'bold_red dynatree-statusnode-error'
        )
    );
    
    $json_encode = @json_encode($tree);        
}                   

echo $json_encode;

?>