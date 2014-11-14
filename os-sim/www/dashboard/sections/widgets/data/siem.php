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
Session::logcheck("dashboard-menu", "ControlPanelExecutive");

		
		
//Setting DB connection			
$db    = new ossim_db(TRUE);
$conn  = $db->connect();

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
	$winfo['asset']  = GET("asset");					//Assets implicated in the widget.
	$chart_info      = unserialize(GET("value")); 		//Params of the widget representation, this is: type of chart, legend params, etc.

} 
else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{ 

	//Getting the widget's info from DB
	$winfo      = get_widget_data($conn, $id);		//Check it out in widget_common.php
	$chart_info = $winfo['params'];					//Params of the widget representation, this is: type of chart, legend params, etc.
	
}

//Validation
ossim_valid($winfo['wtype'], 	OSS_TEXT, 								'illegal:' . _("Type"));
ossim_valid($winfo['height'],	OSS_DIGIT,        						'illegal:' . _("Widget ID"));
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
//Now the widget's data will be calculated depending of the widget's type. 
switch($type){

	case 'eventsbysensordata':   
		
		Session::logcheck("analysis-menu", "EventsForensics");
		
		$query_where = Security_report::make_where($conn, '', '', array(), $assets_filters, "", "", false);
		$height      = $winfo['height'];
		
		include ("../draw/radar.php");			
			
	break;
		
	case 'source_type':
	
		Session::logcheck("analysis-menu", "EventsForensics");
		
		//Date range.
		$range          = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 604800;

		$query_where    = Security_report::make_where($conn, '','', array(), $assets_filters, '', '', false);
		
		//Limit of host to show in the widget.
		$limit          = ($chart_info['top'] != '')? $chart_info['top'] : 10;

		//Link to the forensic site.
		$link          = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]=".gmdate("m",$timeutc-$range)."&time[0][3]=".gmdate("d",$timeutc-$range)."&time[0][4]=".gmdate("y",$timeutc-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timeutc)."&time[1][3]=".gmdate("d",$timeutc)."&time[1][4]=".gmdate("y",$timeutc)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query+DB&num_result_rows=-1&sort_order=time_d&hmenu=Forensics&smenu=Forensics&utc=1";
		
		$forensic_link = Menu::get_menu_url($link, 'analysis', 'security_events');
		//Sql Query
		$sqlgraph      = "SELECT sum( acid_event.cnt ) as num_events,c.id,c.name FROM alienvault_siem.ac_acid_event as acid_event, alienvault.plugin p, alienvault.product_type c WHERE c.id=p.product_type AND p.id=acid_event.plugin_id $query_where AND acid_event.day BETWEEN '".gmdate("Y-m-d",gmdate("U")-$range)."' AND '".gmdate("Y-m-d")."' group by c.id having num_events > 0 order by num_events desc LIMIT $limit";

		$ac = $txt_pt = array();
	
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
		    	$ac[$rg->fields["id"]] += $rg->fields["num_events"];
				$txt_pt[$rg->fields["id"]] = _($rg->fields["name"]);
				
		        $rg->MoveNext();
		    }
		}
		
		arsort($ac);		
		$ac = array_slice($ac, 0, $limit, true);
		
		foreach ($ac as $st => $events) 
		{
			$data[]  = $events;
			$label[] =  _($txt_pt[$st]);
			$links[] = "'$forensic_link&sourcetype=".urlencode($st)."'";
		}
		
		$colors = get_widget_colors(count($data));
		
		break;
		
		
	case 'category':	
	   	
		Session::logcheck("analysis-menu", "EventsForensics");
		//Date range.
		$range         = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 604800;
		
		$query_where   = Security_report::make_where($conn, '', '', array(), $assets_filters, "", "", false);
		
		//Limit of host to show in the widget.
		$limit         = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		
		//Link to the forensic site.
		$link          = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]= &time[0][1]=>=&time[0][8]= &time[0][9]=AND&time[1][1]=<=&time[0][2]=".gmdate("m",$timeutc-$range)."&time[0][3]=".gmdate("d",$timeutc-$range)."&time[0][4]=".gmdate("y",$timeutc-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timeutc)."&time[1][3]=".gmdate("d",$timeutc)."&time[1][4]=".gmdate("y",$timeutc)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query DB&num_result_rows=-1&sort_order=time_d&hmenu=Forensics&smenu=Forensics&utc=1";
		$forensic_link = Menu::get_menu_url($link, 'analysis', 'security_events');

		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph     = "SELECT sum( acid_event.cnt ) as num_events,p.category_id,c.name FROM alienvault_siem.ac_acid_event as acid_event, alienvault.plugin_sid p, alienvault.category c WHERE c.id=p.category_id AND p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND acid_event.day BETWEEN '".gmdate("Y-m-d",$timeutc-$range)."' AND '".gmdate("Y-m-d")."' $query_where group by p.category_id having num_events > 0 order by num_events desc LIMIT $limit";
			
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else
		{
		    while (!$rg->EOF) 
		    {
                if ($rg->fields["name"]=="")
                {
					$rg->fields["name"] = _("Unknown category");
				}
				
		        $data[]  = $rg->fields["num_events"];
				$label[] = $rg->fields["name"];
                $links[] = "'$forensic_link&category%5B1%5D=&category%5B0%5D=".$rg->fields["category_id"]."'";

				$rg->MoveNext();
		    }
		}
		
		$colors = get_widget_colors(count($data));
		
		break;
	
	case 'siemlogger':
	
		//Amount of hours to show in the widget.
		//$max   = ($chart_info['range'] == '')? 16 : $chart_info['range'];
		$max = 16; //By now it will be always 24 hours
		
		//Type of graph. In this case is the simple raphael.
		$js    = "analytics_duo";
		
		//Retrieving the data of the widget
		$trend1 = (Session::menu_perms("analysis-menu", "EventsForensics")) ? SIEM_trends($max, $assets_filters) : array();
		//Empty logger if any user perms over ctx, host, net
		$trend2 = array();
		
		if (Session::is_pro() && Session::menu_perms("analysis-menu", "ControlPanelSEM")) 
		{
			$trend2 = Logger_trends();
		}
		
		for ($i=$max-1; $i>=0; $i--) 
		{
			$h        = gmdate("j G",$timetz-(3600*$i))."h";
			$label[]  = preg_replace("/^\d+ /","",$h);
			$data1[]  = ($trend1[$h]!="") ? $trend1[$h] : 0;
			$data2[]  = ($trend2[$h]!="") ? $trend2[$h] : 0;
		}
		
		$data[]       = $data1;
		$data[]       = $data2;
		$siem_url     = "'".Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz)."&time[0][3]=".gmdate("d",$timetz)."&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events') ."'";
		$siem_url_y   = "'".Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz-86400)."&time[0][3]=".gmdate("d",$timetz-86400)."&time[0][4]=".gmdate("Y",$timetz-86400)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz-86400)."&time[1][3]=".gmdate("d",$timetz-86400)."&time[1][4]=".gmdate("Y",$timetz-86400)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events') ."'";    
		
		if (Session::is_pro())
		{
			$logger_url   = "'".Menu::get_menu_url('/ossim/sem/index.php?start='.urlencode(gmdate("Y-m-d",$timetz)." HH:00:00").'&end='.urlencode(gmdate("Y-m-d",$timetz)." HH:59:59"), 'analysis', 'raw_logs') ."'";
       		$logger_url_y = "'".Menu::get_menu_url('/ossim/sem/index.php?start='.urlencode(gmdate("Y-m-d",$timetz-86400)." HH:00:00").'&end='.urlencode(gmdate("Y-m-d",$timetz-86400)." HH:59:59"), 'analysis', 'raw_logs') ."'";       
        }
        else
        {
        	$logger_url   = "'" . Menu::get_menu_url('/ossim/ossem/index.php', 'analysis', 'raw_logs') . "'";
       		$logger_url_y = "'" . Menu::get_menu_url('/ossim/ossem/index.php', 'analysis', 'raw_logs') . "'";
        }
		
		$colors = "'#94CF05'";
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		break;
	
	case 'alarmstickets':
		
		if (!Session::menu_perms("analysis-menu", "ControlPanelAlarms") && !Session::menu_perms("analysis-menu", "IncidentsIncidents"))
		{
			Session::unallowed_section(false);
		}
		//Tickets filters
		$param_filters = array();

		$param_filters = array();
	
		if (preg_match("/u_(.*)/", $winfo['asset'], $fnd)) 
		{
			$param_filters['user'] = $fnd[1];
		}
		elseif (preg_match("/e_(.*)/", $winfo['asset'], $fnd)) 
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


		//Alarm Filters	
		list($ajoin,$awhere) = Security_report::make_where_alarm($conn, '', '', array(), $assets_filters);
		$awhere              = preg_replace('/AND \(a\.timestamp.*/', '', $awhere);
		

		session_write_close();

		//Date ranges
		$_today        = gmdate("Y-m-d",$timetz);
		
		$_1Ago         = gmdate("Y-m-d",$timetz-(86400*1));
		$_1Ago_day 	   = 1;
		
		$_2Ago         = gmdate("Y-m-d",$timetz-(86400*2));
		$_2Ago_day 	   = 2;
		
		$_Week         = gmdate("Y-m-d",$timetz-(86400*7));
		$_Week_day 	   = 7;
		
		$_2Week        = gmdate("Y-m-d",$timetz-(86400*14));
		$_2Week_day    = 14;
		
		if (Session::menu_perms("analysis-menu", "ControlPanelAlarms")){

			// Alarms
			$tzc   = Util::get_tzc();
			
			$_date = "convert_tz(a.timestamp,'+00:00','$tzc')";
			
			$query = "SELECT * FROM 
			(SELECT count(DISTINCT(a.backlog_id)) as Today from alarm a $ajoin where $_date >= '$_today' $awhere) AS Today, 
			(SELECT count(DISTINCT(a.backlog_id)) as Yesterd from alarm a $ajoin where $_date between '$_1Ago' AND '$_today' $awhere)  AS Yesterd,
			(SELECT count(DISTINCT(a.backlog_id)) as 2DAgo from alarm a $ajoin where $_date between '$_2Ago' AND '$_1Ago' $awhere)  AS 2DAgo,
			(SELECT count(DISTINCT(a.backlog_id)) as Week from alarm a $ajoin where $_date between '$_Week' AND '$_2Ago' $awhere)  AS Week,
			(SELECT count(DISTINCT(a.backlog_id)) as 2Weeks from alarm a $ajoin where $_date between '$_2Week' AND '$_Week' $awhere)  AS 2Weeks;";

			//echo $query;
			
			if (!$rs = & $conn->CacheExecute($query)) 
			{
				print $conn->ErrorMsg();
				exit();
			}
			while (!$rs->EOF) 
			{
				$values[] = $rs->fields["Today"];
				$values[] = $rs->fields["Yesterd"];
				$values[] = $rs->fields["2DAgo"];
				$values[] = $rs->fields["Week"];
				$values[] = $rs->fields["2Weeks"];
				
				$rs->MoveNext();
			}
			
			$links[] = "'".Menu::get_menu_url("/ossim/alarm/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hour=00&minutes=00&hide_closed=0&date_from=".gmdate("Y-m-d",$timetz)."&date_to=".gmdate("Y-m-d",$timetz), "analysis", "alarms") . "'";
			$links[] = "'".Menu::get_menu_url("/ossim/alarm/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hour=00&minutes=00&hide_closed=0&date_from=".gmdate("Y-m-d",$timetz-(86400*$_1Ago_day))."&date_to=".gmdate("Y-m-d",$timetz-(86400*$_1Ago_day)), "analysis", "alarms") . "'";
			$links[] = "'".Menu::get_menu_url("/ossim/alarm/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hour=00&minutes=00&hide_closed=0&date_from=".gmdate("Y-m-d",$timetz-(86400*$_2Ago_day))."&date_to=".gmdate("Y-m-d",$timetz-(86400*($_1Ago_day+1))), "analysis", "alarms") . "'";
			$links[] = "'".Menu::get_menu_url("/ossim/alarm/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hour=00&minutes=00&hide_closed=0&date_from=".gmdate("Y-m-d",$timetz-(86400*$_Week_day))."&date_to=".gmdate("Y-m-d",$timetz-(86400*($_2Ago_day+1))), "analysis", "alarms") . "'";
			$links[] = "'".Menu::get_menu_url("/ossim/alarm/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hour=00&minutes=00&hide_closed=0&date_from=".gmdate("Y-m-d",$timetz-(86400*$_2Week_day))."&date_to=".gmdate("Y-m-d",$timetz-(86400*($_Week_day+1))), "analysis", "alarms") . "'";

		} 
		else 
		{
			$values = array(0,0,0,0,0);
			$links  = array(0,0,0,0,0);
		}

		
		if (Session::menu_perms("analysis-menu", "IncidentsIncidents"))
		{
			// Tickets
			$today = Incident::incidents_by_date($conn, "'".$_today."'", $param_filters["assets"], $param_filters["user"], " AND incident.status = 'Open' ");
			$yday  = Incident::incidents_by_date($conn, array("'$_1Ago'","'$_today'"), $param_filters["assets"], $param_filters["user"], " AND incident.status = 'Open' ");
			$ago2  = Incident::incidents_by_date($conn, array("'$_2Ago'","'$_1Ago'"), $param_filters["assets"], $param_filters["user"], " AND incident.status = 'Open' ");
			$week  = Incident::incidents_by_date($conn, array("'$_Week'","'$_2Ago'"), $param_filters["assets"], $param_filters["user"], " AND incident.status = 'Open' ");
			$week2 = Incident::incidents_by_date($conn, array("'$_2Week'","'$_Week'"), $param_filters["assets"], $param_filters["user"], " AND incident.status = 'Open' ");
			
			$values2[] = $today;
			$values2[] = $yday;
			$values2[] = $ago2;
			$values2[] = $week;
			$values2[] = $week2;
			
			$links2 = Menu::get_menu_url("/ossim/incidents/index.php?status=&hmenu=Tickets&smenu=Tickets", "analysis", "tickets");
		}
		else 
		{
			$values2 = array(0,0,0,0,0);
			$links2  = "";
		}

		if (!empty($values) || !empty($values2))
		{
			$data[0] = $values;
			$data[1] = $values2;
		}
		
		$serie1 = _('Alarms');
		$serie2 = _('Tickets');
		
		$label = array(Util::html_entities2utf8(_('Today')),
		               Util::html_entities2utf8(_('-1 Day')),
		               Util::html_entities2utf8(_('-2 Days')),
		               Util::html_entities2utf8(_('Week')),
		               Util::html_entities2utf8(_('2 Weeks'))
		               );
	
		break;
	
		
}
//print_r($sqlgraph);
$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';
