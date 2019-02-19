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


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");

		
//Setting DB connection			
$db    = new ossim_db();
$conn  = $db->connect(true);

//Getting the current user
$user  = Session::get_session_user();

//This is the type of security widget.
$type  = GET("type");
//ID of the widget
$id    = GET("id");


//Validation
ossim_valid($type,	OSS_TEXT, 					'illegal:' . _("type"));
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
		if ($key == "type") {
			if (!preg_match("/^[a-zA-Z]{3}_?[a-zA-Z]*$/",$val)) {
				ossim_set_error('illegal: '. _($key));
			}
		} else {
			ossim_valid($val, constant($validation[$key]), 'illegal: '. _($key));
		}
	}
}

if (ossim_error()) 
{
	die(ossim_error());
}
//End of validation.
	

$assets_filters = $param_filters = array();
$assets_filters = get_asset_filters($conn, $winfo['asset']);	
	

//Variables to store the chart information
$data  = array();	//The widget's data itself.


session_write_close();

//Now the widget's data will be calculated depending of the widget's type. 
switch($type)
{
	case 'ticket':        

		Session::logcheck("analysis-menu", "IncidentsIncidents");
		
		$param_filters = array();

		if ( preg_match("/u_(.*)/", $winfo['asset'], $fnd) ) 
		{
			$param_filters['user'] = $fnd[1];
		}
		elseif ( preg_match("/e_(.*)/", $winfo['asset'], $fnd) ) 
		{
			$param_filters['user'] = $fnd[1];
		} 
		elseif (!strcasecmp("ALL_ASSETS", $winfo['asset']) || empty($winfo['asset']))
		{
			$param_filters['user'] = $user;
		}

		$param_filters["assets"] = array();

		if (empty($param_filters['user']))
		{

			if (is_array($assets_filters["assets"]['host']))
			{
				foreach ($assets_filters["assets"]['host'] as $k => $v)
				{
					$param_filters["assets"][$k] = $v['ip']; 
				}
			}

			if (is_array($assets_filters["assets"]['net']))
			{
				foreach ($assets_filters["assets"]['net'] as $k => $v)
				{
					$param_filters["assets"][$k] = $v['ip']; 
				}
			}
		}
	
	
		$operator = ($chart_info['type'] != '')? $chart_info['type'] : 'max';

		$list     = Incident::get_list_filtered($conn, $param_filters["assets"], array("where" => " AND incident.status = 'Open'"), $param_filters["user"]);
		
		if (is_array($list) && !empty($list))
		{

			$min = 10;
			$max = 0;
			$avg = 0;
			
			foreach($list as $t)
			{
				if ($t->get_priority() < $min)
				{
					$min = $t->get_priority();
				}
				
				if ($t->get_priority() > $max)
				{
					$max = $t->get_priority();
				}
				
				$avg += $t->get_priority();
			}
			
			switch($operator)
			{
				case 'min':
					$level = $min;					
					break;
					
				case 'max':
					$level = $max;					
					break;
					
				default:
					$total = count($list);
					$level = round($avg/$total);				
			}
		
		} 
		else
		{
			$level = 0;
			
		}
		
		$data[] = $level;
		$link   = Menu::get_menu_url('/ossim/incidents/index.php', 'analysis', 'tickets');
		$min    = 0;
		$max    = 10;
			
	break;
	
	case 'alarm':        

		Session::logcheck("analysis-menu", "ControlPanelAlarms");
		
		//Alarm Filters	
		list($ajoin,$awhere) = Security_report::make_where_alarm($conn, '', '', array(), $assets_filters);
		$awhere              = preg_replace('/AND \(a\.timestamp.*/', '', $awhere);
		
		$operator = ($chart_info['type'] != '')? $chart_info['type'] : 'max';

		$sqlgraph = "SELECT $operator(a.risk) as level FROM alienvault.alarm a $ajoin where a.status='open' $awhere";
		
		
		$rg = $conn->CacheExecute($sqlgraph);
		
		if (!$rg)
		{
			print $conn->ErrorMsg();
		}
		else 
		{				
			$level  = $rg->fields["level"];
		}

		$data[] = intval($level);
		$link   = Menu::get_menu_url('/ossim/alarm/alarm_console.php', 'analysis', 'alarms');
		$min    = 0;
		$max    = 10;
			
	break;

	default:
			
			echo _("Unknown Type");
			exit();

	
			
}

$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';

