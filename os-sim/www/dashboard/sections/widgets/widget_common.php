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


function getNewId($dbconn, $tab)
{
	$user = Session::get_session_user();
	
	ossim_valid($tab,	OSS_DIGIT,	'illegal:' . _("Tab ID"));
	
	if (ossim_error()) 
	{
		die(ossim_error());
	}
	
	$query = "SELECT max(id) as id FROM dashboard_widget_config WHERE panel_id=? and user=?";
	
	$params = array(
		$tab,
		$user
	);
		
	if (!$rs = & $dbconn->Execute($query, $params))
	{
	    print $dbconn->ErrorMsg();
		exit();
	} 
	else 
	{
	    if (!$rs->EOF) 
	    {
	        $id = $rs->fields["id"];		
			return (($id != "") ? $id + 1 : 0);
	    } 
	    else
	    {
			print 'Error getting the new Widget ID: ' . $dbconn->ErrorMsg() . '<br/>';
			exit();
		}
	}	
}


function reorder_widgets($dbconn, $tab)
{
	$user = Session::get_session_user();
	
	ossim_valid($tab,	OSS_DIGIT,	'illegal:' . _("Tab ID"));
	
	if (ossim_error()) 
	{
		die(ossim_error());
	}
	
	$query  = "UPDATE dashboard_widget_config set fil = (fil + 1) WHERE panel_id=? and user=? and col=0";
	
	$params = array(
		$tab,
		$user
	);
		
	if (!$dbconn->Execute($query, $params))
	{
	    print $dbconn->ErrorMsg();
		
		return TRUE;
	} 
	else 
	{
		return FALSE;
	}	
}


function getColumn($dbconn, $id)
{
	$query = "SELECT col FROM dashboard_widget_config WHERE id=?";
	
	ossim_valid($id,	OSS_DIGIT,	'illegal:' . _("Widget ID"));
	if (ossim_error()) 
	{
		die(ossim_error());
	}
	
	$params = array(
		$id
	);
		
	if (!$rs = & $dbconn->Execute($query, $params))
	{
	    print 'Error retrieving the Widget Column: ' . $dbconn->ErrorMsg() . '<br/>';
		exit();
	} 
	else 
	{
		return $rs->fields["col"];		
	}	
}


function getOrder($dbconn, $id)
{
	$query = "SELECT fil FROM dashboard_widget_config WHERE id=?";
	
	ossim_valid($id,	OSS_DIGIT,	'illegal:' . _("Widget ID"));
	
	if (ossim_error()) 
	{
		die(ossim_error());
	}
	
	$params = array(
		$id
	);
		
	if (!$rs = & $dbconn->Execute($query, $params))
	{
	    print $dbconn->ErrorMsg();
		exit();
	}
	else 
	{
        return $rs->fields["fil"];
	}	
}


function add_url_character($url)
{
	return (preg_match('/\?/', $url)) ? "&" : "?";
}


function get_widget_data($conn, $id)
{
	ossim_valid($id,	OSS_DIGIT,	'illegal:' . _("Widget ID"));
	
	if (ossim_error()) 
	{
		die(ossim_error());
	}

	$data   = array();
	
	$query  = "SELECT * FROM dashboard_widget_config where id=?";						
	$params = array(
		$id
	);
		
	if ($result = $conn->Execute($query, $params))
	{
		$data['height']  = $result->fields['height'];	
		$data['wtype']   = $result->fields['type'];			
		$data['refresh'] = $result->fields['refresh'];
		$data['params']  = unserialize($result->fields['params']);
		$data['asset']   = $result->fields['asset'];
		$data['media']   = $result->fields['media'];
	} 

	return $data;

}


function get_array_validation()
{
    $validation = array();

    $validation['range'] = "OSS_DIGIT, OSS_NULLABLE";
    $validation['top']   = "OSS_DIGIT, OSS_NULLABLE";
    $validation['type']  = "OSS_TEXT, OSS_NULLABLE";
    
    return $validation;
}


function html2rgb($color)
{
	if ($color[0] == '#')
	{
		$color = substr($color, 1);
    }
    
    $arr = false;
    
    if (strlen($color) == 6 || strlen($color) == 3)
    {
        if (strlen($color) == 6)
        {
            list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
        }
        else
        {
            list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
        }

        $r   = hexdec($r);
        $g   = hexdec($g);
        $b   = hexdec($b);
        
        $arr = array($r, $g, $b);        
    }
    
	return $arr;
}


function rgbToHex($rgb)
{
	return sprintf('%02x', $rgb[0]).sprintf('%02x', $rgb[1]).sprintf('%02x', $rgb[2]);
}


function get_widget_colors($steps=0) 
{ 
	$chart_color = array(
		"#fac800",	
		"#7d71bd",
		"#2fc9e5",
		"#c12fe5",
		"#ff8a00",
		"#f65dc9",
		"#1e2ad1",
		"#00fcff",
		"#94cf05",
		"#fa0000"
	);
	
	$color_result = array();
	$total        = count($chart_color);
	
	for($i=0; $i <$steps; $i++)
	{
		$color_result[] = '"'. $chart_color[($i%$total)] .'"';
	}
	
	return implode(",", $color_result);
}	


function get_asset_filters($conn, $asset)
{
	if( !Session::is_pro() || preg_match("/ALL_ASSETS/",$asset) )
	{
		$return['ctx']              = array();
		$return['assets']['host']   = array();
		$return['assets']['net']    = array();
		$return['assets']['sensor'] = array();
		
		return $return;
	}
	else
	{
		include_once AV_MAIN_ROOT_PATH . '/report/asset_type_functions.php';

		$filters = getAssetFilter(array('assets' => $asset), $conn);
		
		return $filters;
	}
}