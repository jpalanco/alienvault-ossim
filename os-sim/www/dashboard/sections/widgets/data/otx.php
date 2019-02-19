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
require_once '../widget_common.php';
require_once 'sensor_filter.php';
require_once 'common.php';


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "IPReputation");


//Setting DB connection			
$db    = new ossim_db(TRUE);
$conn  = $db->connect();

//Getting the current user
$user  = Session::get_session_user();

//This is the type of otx widget.
$type  = GET("type");
//ID of the widget
$id    = GET("id");


//Validation
ossim_valid($type,	OSS_TEXT, 					'illegal:' . _("Widget Type"));
ossim_valid($id, 	OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("Widget ID"));

if (ossim_error()) 
{
    die(ossim_error());
}
//End of validation

//Array that contains the widget's general info
$winfo		= array();
//Array that contains the info about the widget's representation, this is: chart info, tag cloud info, etc.
$chart_info = array();


//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if (!isset($id) || empty($id))
{
	$winfo['height'] = GET("height");					//Height of the widget
	$winfo['wtype']  = GET("wtype");					//Type of widget: chart, tag_cloud, etc.
	$winfo['asset']  = GET("asset");					//Assets implicated in the widget
	$chart_info      = json_decode(GET("value"),true); 		//Params of the widget representation, this is: type of chart, legend params, etc.
	
} 
else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{ 
	//Getting the widget's info from DB
	$winfo      = get_widget_data($conn, $id);		//Check it out in widget_common.php
	$chart_info = $winfo['params'];					//Params of the widget representation, this is: type of chart, legend params, etc.
	
}

//Validation
ossim_valid($winfo['wtype'], 	OSS_TEXT, 								'illegal:' . _("Type"));
ossim_valid($winfo['height'],	OSS_DIGIT, 								'illegal:' . _("Widget ID"));
ossim_valid($winfo['asset'], 	OSS_HEX,OSS_SCORE,OSS_ALPHA,OSS_USER, 	'illegal:' . _("Asset/User/Entity"));

if (is_array($chart_info) && !empty($chart_info))
{
	$validation = get_array_validation();
		
	foreach($chart_info as $key=>$val)
	{
    	if ($validation[$key] == '')
    	{
        	continue;
    	}
    	
		eval("ossim_valid(\"\$val\", ".$validation[$key].", 'illegal:" . _($key)."');");
	}	
}

if (ossim_error()) 
{
	die(ossim_error());
}
//End of validation.


$assets_filters = array();
$assets_filters = get_asset_filters($conn, $winfo['asset']);	

//Variables to store the chart information
$data  = array();	//The widget's data itself.
$label = array();	//Widget's label such as legend in charts, titles in tag clouds, etc...
$links = array();	//Links of each element of the widget.



/*
*
*	The code below is copied from /panel and will have to be adapted to the new DB structutre of the 4.0 version, that's why it is not commented.
*
*/
session_write_close();

$otx = new Otx();
$otx->load();
    
$c1 = $otx->get_token();
$c2 = $c1 && $otx->get_key_version() < 2;

if (!$c1 || $c2)
{
    $_GET['error_type'] = (!$c1) ? 'token' : 'old_key';
    require '../draw/otx_unregistered.php';
    
    die();
}

//Now the widget's data will be calculated depending of the widget's type. 
switch($type)
{
	case "top":
	
	    $limit  = ($chart_info['top'] > 0) ? $chart_info['top'] : 5;
	    $range  = ($chart_info['range'] > 0) ? $chart_info['range'] : 14;
	    
	    $params = array(
	        'top'   => $limit, 
	        'range' => $range
	    );
	    
	    $range  = $range * 86400;
	    
        try
        {
            $top_otx = $otx->get_top_pulses($params);
            foreach ($top_otx as $p_id => $pulse)
            {
                $name      = $pulse['name'];
                $data[]    = $pulse['total'];
                $label[]   = (strlen($name) > 28) ? substr($name, 0, 25) . '...' : $name;
                $tooltip[] = $name;
                
                $link      = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]=". gmdate("m", $timeutc - $range) ."&time[0][3]=". gmdate("d", $timeutc - $range) ."&time[0][4]=". gmdate("Y", $timeutc - $range) ."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=". gmdate("m", $timeutc) ."&time[1][3]=". gmdate("d", $timeutc) ."&time[1][4]=". gmdate("Y", $timeutc) ."&time[1][5]=23&time[1][6]=59&time[1][7]=59&otx[0]=". $p_id ."&submit=Query+DB&num_result_rows=-1&sort_order=time_d";
                
                $f_link    = Menu::get_menu_url($link, 'analysis', 'security_events', 'security_events');
                
                $links[]   = $f_link;
            }
            
        }
        catch (Exception $e){}
		
		$colors      = get_widget_colors(count($data));
		$nodata_text = _('No recent OTX activity.');
		
		$hide_x_axis = TRUE;

		break;
		
	//In case of error a message will be shown.
	default:
		$nodata_text = _("Unknown Widget Type");			
}
	
$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';
