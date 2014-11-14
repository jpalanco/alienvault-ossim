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

require_once 'sensor_filter.php';
require_once '../widget_common.php';
require_once 'common.php';


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
Session::logcheck("analysis-menu", "EventsForensics");

		
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
	$winfo['asset']  = GET("asset");					//Assets implicated in the widget
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

//Now the widget's data will be calculated depending of the widget's type. 
switch($type)
{

	case "tcp":
		//Filters of assets.
		$query_where = Security_report::make_where($conn, '', '', array(), $assets_filters, '', '', false);
		
		//Max number of attacks to show in the widget.
		$limit = ($chart_info['top'] != '')? $chart_info['top'] : 30;
		//Sql Query
		//TO DO: Use parameters in the query.
		$sql   = "select layer4_dport as port, count(*) as num from alienvault_siem.acid_event where layer4_dport != 0  and ip_proto=6 $query_where group by port order by num desc limit $limit";
		
		if (!$rs = & $conn->CacheExecute($sql)) 
		{
		    print $conn->ErrorMsg();
		}
		else 
		{
			$array_aux = array();
		    while (!$rs->EOF) 
		    {			
				$array_aux[$rs->fields["port"]] = $rs->fields["num"];
				$link = Menu::get_menu_url('/ossim/forensics/base_qry_main.php?tcp_port[0][0]=&tcp_port[0][1]=layer4_dport&tcp_port[0][2]==&tcp_port[0][3]='.$rs->fields["port"].'&tcp_port[0][4]=&tcp_port[0][5]=&tcp_flags[0]=&layer4=TCP&num_result_rows=-1&current_view=-1&new=1&submit=QUERYDBP&sort_order=sig_a&clear_allcriteria=1&clear_criteria=time&time_range=all', 'analysis', 'security_events');
				$links[$rs->fields["port"]] = $link; 
				$rs->MoveNext();
		    }
			
			//Ordering the result by the name of the ports instead of the numbers of attacks.
			ksort($array_aux);			
			$data   = array_values($array_aux);
			$label  = array_keys($array_aux);
			
			//Name of the serie, just in case a chart is displayed
			$serie  = 'Amount of Attacks';
			
			$colors = "#333333";
		}

		break;
		
	case "udp":
	
		//Filters of assets.
		$query_where = Security_report::make_where($conn, '', '', array(), $assets_filters, '', '', false);
		
		//Max number of attacks to show in the widget.
		$limit = ($chart_info['top'] != '')? $chart_info['top'] : 30;
		//Sql Query
		//TO DO: Use parameters in the query.
		$sql   = "select layer4_dport as port, count(*) as num from alienvault_siem.acid_event where layer4_dport != 0  and ip_proto=17 $query_where group by port order by num desc limit $limit;";
		
		if (!$rs = & $conn->CacheExecute($sql)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    $array_aux = array();
		    while (!$rs->EOF) 
		    {			
				$array_aux[$rs->fields["port"]] = $rs->fields["num"];
				
				$link = Menu::get_menu_url('/ossim/forensics/base_qry_main.php?udp_port[0][0]=&udp_port[0][1]=layer4_dport&udp_port[0][2]==&udp_port[0][3]='.$rs->fields["port"].'&udp_port[0][4]=&udp_port[0][5]=&udp_flags[0]=&layer4=UDP&num_result_rows=-1&current_view=-1&new=1&submit=QUERYDBP&sort_order=sig_a&clear_allcriteria=1&clear_criteria=time&time_range=all', 'analysis', 'security_events');
				$links[$rs->fields["port"]] = $link; 

				$rs->MoveNext();
		    }
			
			//Ordering the result by the name of the ports instead of the numbers of attacks.
			ksort($array_aux);			
			$data   = array_values($array_aux);
			$label  = array_keys($array_aux);
			
			//Name of the serie, just in case a chart is displayed
			$serie  = 'Amount of Attacks';
			
			$colors = "#333333";
		}

		break;
		
			
	case "promiscuous":
		    	
		//Date range.
		$range          = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 604800;
		
		//Filters of assets.
		$query_where    = Security_report::make_where($conn, gmdate("Y-m-d 00:00:00",gmdate("U")-$range), gmdate("Y-m-d 23:59:59"), array(), $assets_filters);
		
		
		//Limit of host to show in the widget.
		$limit          = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		//Link to the forensic site.
		$forensic_link  = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("y",$timetz-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events');

		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph       = "select count(distinct(ip_dst)) as num_events,ip_src as name from alienvault_siem.acid_event  WHERE 1=1 $query_where group by ip_src having ip_src<>0x0 order by num_events desc limit $limit";

		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		}
		else
		{
		    while (!$rg->EOF) 
		    {
		        $data[]  = $rg->fields["num_events"];
				$label[] = inet_ntop($rg->fields["name"]);
				
				$links[] = "'$forensic_link&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".inet_ntop($rg->fields["name"])."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1'";

		        $rg->MoveNext();
		    }
		}
		
		$colors = get_widget_colors(count($data));
		
		break;


	case "unique":
		
		//Date range.
		$range          = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 604800;
		
		//Filters of assets.
		$query_where    = Security_report::make_where($conn, gmdate("Y-m-d 00:00:00",gmdate("U")-$range), gmdate("Y-m-d 23:59:59"), array(), $assets_filters);
		
		//Limit of host to show in the widget.
		$limit          = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		//Link to the forensic site.
		$forensic_link  = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("y",$timetz-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events');
		
		//Sql Query
		$sqlgraph       = "select count(distinct plugin_id,plugin_sid) as num_events,ip_src as name from alienvault_siem.acid_event WHERE 1=1 $query_where group by ip_src having ip_src<>0x0 order by num_events desc limit $limit";
		
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
                $data[]  = $rg->fields["num_events"];
				$label[] = inet_ntop($rg->fields["name"]);
				
				$links[] = "'$forensic_link&ip_addr[0][0]=+&ip_addr[0][1]=ip_src&ip_addr[0][2]=%3D&ip_addr[0][3]=".inet_ntop($rg->fields["name"])."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1'";
		      
		        $rg->MoveNext();
		    }
		}
		
		$colors = get_widget_colors(count($data));
		
		break;
				
		
	case "alarms":
		
		//Checking if we have permissions to go through this section
		Session::logcheck("analysis-menu", "ControlPanelAlarms");

		//Filters of sensors.
		list($ajoin,$awhere) = Security_report::make_where_alarm($conn, '', '', array(), $assets_filters);
		$awhere              = preg_replace('/AND \(a\.timestamp.*/', '', $awhere);
    	
		//Limit of alarms to show in the widget.
		$limit                = ($chart_info['top'] != '')? $chart_info['top'] : 5;
		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph = "select count(*) as num_events,p.name from alienvault.plugin_sid p, alienvault.alarm a $ajoin WHERE p.plugin_id=a.plugin_id AND p.sid=a.plugin_sid $awhere group by p.name order by num_events desc limit $limit";
		
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
		        $data[]  = $rg->fields["num_events"];
				$name    = Util::signaturefilter($rg->fields["name"]);	
				$label[] = ((strlen($name)>25) ? substr($name,0,25)."..." : $name);

				$link    = Menu::get_menu_url("/ossim/alarm/alarm_console.php?num_alarms_page=50&hmenu=Alarms&smenu=Alarms&hide_closed=1&query=".$rg->fields["name"], 'analysis', 'alarms');
				$links[] = "'$link'";

		        $rg->MoveNext();
		    }
		}
		
		
		$colors = get_widget_colors(count($data));
		
		break;
		
	case "events":
	
		//Filters of assets.
		$query_where = Security_report::make_where($conn, '', '', array(), $assets_filters, '','', false);
    	
		//Limit of alarms to show in the widget.
		$limit    = ($chart_info['top'] != '')? $chart_info['top'] : 5;
		
		//Sql Query
		$sqlgraph = "SELECT sum( acid_event.cnt ) as num_events, p.name, p.plugin_id, p.sid from alienvault_siem.ac_acid_event as acid_event, alienvault.plugin_sid p WHERE p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid $query_where group by p.name order by num_events desc limit $limit";
		
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
		        $data[]  = $rg->fields["num_events"];
				$name    = Util::signaturefilter($rg->fields["name"]);	
				$label[] = ((strlen($name)>25) ? substr($name,0,25)."..." : $name);
				
				$link    = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=all&submit=Query+DB&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=".$rg->fields["plugin_id"]."%3B".$rg->fields["sid"]."&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events');
				$links[] = "'$link'";

		        $rg->MoveNext();
		    }
		}
		
		$colors = get_widget_colors(count($data));
		
		break;
		

	case 'siemhours':
	
		//Amount of hours to show in the widget.
		$max = ($chart_info['range'] == '')? 16 : $chart_info['range'];
		//Type of graph. In this case is the simple raphael.
		$js     = "analytics";
		//Retrieving the data of the widget
		$values = SIEM_trends($max, $assets_filters);

		//Formating the info into a generinf format valid for the handler.
		for ($i=$max-1; $i>=0; $i--) 
		{
			$h       = gmdate("j G",$timetz-(3600*$i))."h";
			$label[] = preg_replace("/\d+ /","",$h);
			$data[]  = ($values[$h]!="") ? $values[$h] : 0;

			$link    = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=".gmdate("m",$timetz)."&time[0][3]=".gmdate("d",$timetz)."&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=HH&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=HH&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events');
			$links[] = "'$link'";

		}    
		
		//Widget's links
		$siem_url    = $links[0];
		
		$colors      = "'#444444'";
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		break;
		
		
	case 'siemdays':
	
		//Amount of days to show in the widget.
		$max = ($chart_info['range'] == '')? 7 : $chart_info['range'];
		
		//Type of graph. In this case is the simple raphael.
		$js = "analytics";
		
		//Retrieving the data of the widget
		$values = SIEM_trends_week("", $max, $assets_filters);
		
		//Formating the info into a generinf format valid for the handler.
		for ($i=$max-1; $i>=0; $i--) 
		{
			$d = gmdate("j M",$timetz-(86400*$i));
			$label[] = $d;
			$data[]  = ($values[$d]!="") ? $values[$d] : 0;

			$link    = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time[0][0]=+&time[0][1]=>%3D&time[0][2]=MM&time[0][3]=ZZ&time[0][4]=".gmdate("Y",$timetz)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]=+&time[0][9]=AND&time[1][0]=+&time[1][1]=<%3D&time[1][2]=MM&time[1][3]=ZZ&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]=+&time[1][9]=+&submit=Query+DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics", 'analysis', 'security_events');
			$links[] = "'$link'";

		}
		
		//Widget's links
		$siem_url    = $links[0];
		
		$colors      = "'#444444'";
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";

		break;
		
	//In case of error a message will be shown.
	default:		
		$nodata_text = _("Unknown Type");			
}
	
$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';

