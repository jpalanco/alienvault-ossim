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
require_once 'common.php';
require_once 'sensor_filter.php';


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
Session::logcheck("analysis-menu", "EventsForensics");



/**
 * Function: get_honeypot_category
 * 		Get the category_id for honeypot category
 *
 * Author: Javier Martinez Navajas (fjmnav@alienvault.com)
 * Creation Date: 14/03/2012
 * Modification Date: 
 *
 *
 * Get the category_id of the table alienvault.category when the category matches with honeypot
 *
 *
 * @param $conn
 *   DB Handler
 *		
 *
 * @return
 *   An array with the ids which belong to honeypot.
 *
 */		
function get_honeypot_category($conn)
{

	$array_id = array();
	$sqlgraph = "SELECT id FROM category WHERE name Like '%Honeypot%'";

    $rg = $conn->CacheExecute($sqlgraph);

	if (!$rg)
	{
	    print $conn->ErrorMsg();
	} 
	else 
	{
	    while (!$rg->EOF) 
	    {
	        $array_id[] = $rg->fields["id"];
	        $rg->MoveNext();
	    }
	}
	
	return $array_id;
}

	
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



$assets_filters = array();
$assets_filters = get_asset_filters($conn, $winfo['asset']);		
$query_where    = Security_report::make_where($conn, '', '', array(), $assets_filters, '', '', false);


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
$query_where    = Security_report::make_where($conn, '', '', array(), $assets_filters, '', '', false);

//Variables to store the chart information
$data  = array();	//The widget's data itself.
$label = array();	//Widget's label such as legend in charts, titles in tag clouds, etc...
$links = array();	//Links of each element of the widget.


/*
*
*	The code below is copied from /panel and will have to be adapted to the new DB structutre of the 4.0 version, that's why it is not commented.
*
*/

$honeypot_category = get_honeypot_category($conn);

session_write_close();
//Now the widget's data will be calculated depending of the widget's type. 
switch($type)
{
	case 'src':
		//Date range.
		$range         = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 432000;

		//Limit of host to show in the widget.
		$limit         = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		
		//Link to the forensic site.
		$link          = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
		$forensic_link = Menu::get_menu_url($link, 'analysis', 'security_events');

		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph      = "select sum(acid_event.cnt) as num_events, acid_event.ip_src as name from alienvault_siem.po_acid_event acid_event, alienvault.plugin_sid p WHERE p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND p.category_id in (".implode(',',$honeypot_category).") AND acid_event.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $query_where group by acid_event.ip_src order by num_events desc limit $limit";
		
		$rg = $conn->CacheExecute($sqlgraph);
		
		if (!$rg)
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
							
		        $data[]  = $rg->fields["num_events"];
				$label[] = inet_ntop($rg->fields["name"]);
                $links[] = $forensic_link . urlencode("&category[0]=19&ip_addr[0][0]= &ip_addr[0][1]=ip_src&ip_addr[0][2]==&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]=+&ip_addr[0][9]=+&ip_addr_cnt=1");
		        
				$rg->MoveNext();
		    }
		}

		$colors = get_widget_colors(count($data));
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		break;
		
	
	case 'dst':
		
		//Date range.
		$range         = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 432000;

		//Limit of host to show in the widget.
		$limit         = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		
		//Link to the forensic site.
		$link          = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]= &time[0][1]=>=&time[0][8]= &time[0][9]=AND&time[1][1]=<=&time[0][2]=".gmdate("m",$timetz-$range)."&time[0][3]=".gmdate("d",$timetz-$range)."&time[0][4]=".gmdate("Y",$timetz-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timetz)."&time[1][3]=".gmdate("d",$timetz)."&time[1][4]=".gmdate("Y",$timetz)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
		$forensic_link = Menu::get_menu_url($link, 'analysis', 'security_events');

		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph      = "select sum(acid_event.cnt) as num_events, acid_event.ip_dst as name from alienvault_siem.po_acid_event acid_event, alienvault.plugin_sid p WHERE p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND p.category_id in (".implode(',',$honeypot_category).") AND acid_event.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $query_where group by acid_event.ip_dst order by num_events desc limit $limit";
		
		$rg = $conn->CacheExecute($sqlgraph);
		
		if (!$rg)
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {			
		        $data[]  = $rg->fields["num_events"];
				$label[] = inet_ntop($rg->fields["name"]);
		        $links[] = $forensic_link . urlencode("&category[0]=19&ip_addr[0][0]= &ip_addr[0][1]=ip_src&ip_addr[0][2]==&ip_addr[0][3]=".$rg->fields["name"]."&ip_addr[0][8]= &ip_addr[0][9]= &ip_addr_cnt=1");
		        

				$rg->MoveNext();
		    }
		}
		
		$colors = get_widget_colors(count($data));
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		break;
		
		
	case 'events':	
			
		//Date range.
		$range         = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 432000;

		//Limit of host to show in the widget.
		$limit         = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		
		//Link to the forensic site.
		$link          = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]= &time[0][1]=>=&time[0][8]= &time[0][9]=AND&time[1][1]=<=&time[0][2]=".gmdate("m",$timeutc-$range)."&time[0][3]=".gmdate("d",$timeutc-$range)."&time[0][4]=".gmdate("Y",$timeutc-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timeutc)."&time[1][3]=".gmdate("d",$timeutc)."&time[1][4]=".gmdate("Y",$timeutc)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query DB&sig_type=1&sig[0]==&sig[1]=QQQ&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
		$forensic_link = Menu::get_menu_url($link, 'analysis', 'security_events');

		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph = "select sum(acid_event.cnt) as val,p.name,p.plugin_id,p.sid FROM alienvault_siem.ac_acid_event acid_event, alienvault.plugin_sid p WHERE p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND p.category_id in (".implode(',',$honeypot_category).") AND acid_event.timestamp BETWEEN '".gmdate("Y-m-d H:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:59:59")."' $query_where group by p.name order by val desc limit $limit";
		
        $rg = $conn->CacheExecute($sqlgraph);

		if (!$rg)
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
		        $data[]  = $rg->fields["val"];
				$label[] = $rg->fields["name"];
		        
                $link    = str_replace("QQQ",$rg->fields["plugin_id"]."%3B".$rg->fields["sid"],$forensic_link);
		        $links[] = $link;
		        
				$rg->MoveNext();
		    }
		}

		$colors = get_widget_colors(count($data));
		
		$serie  = _('Events');
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		break;
		
		
	case 'trend':
	
		//Amount of days to show in the widget.
		$max    = ($chart_info['range'] == '')? 7 : $chart_info['range'];
		//Type of graph. In this case is the simple raphael.
		$js     = "analytics";
		//Retrieving the data of the widget
		$values = SIEM_trends_week("taxonomy=honeypot", $max, $assets_filters);
		
		//Formating the info into a generinf format valid for the handler.
		for ($i=$max-1; $i>=0; $i--) 
		{
		    $tref    = $timetz-(86400*$i);
		
			$d       = gmdate("j M", $tref);
			$label[] = $d;
			$data[]  = ($values[$d]!="") ? $values[$d] : 0;
			$link    = "/forensics/base_qry_main.php?clear_allcriteria=1&category[0]=19&time_range=range&time[0][0]= &time[0][1]=>=&time[0][2]=".gmdate("m", $tref)."&time[0][3]=".gmdate("d",$tref)."&time[0][4]=".gmdate("Y", $tref)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[0][8]= &time[0][9]=AND&time[1][0]= &time[1][1]=<=&time[1][2]=".gmdate("m", $tref)."&time[1][3]=".gmdate("d",$tref)."&time[1][4]=".gmdate("Y", $tref)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&time[1][8]= &time[1][9]= &submit=Query DB&num_result_rows=-1&time_cnt=2&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
			$link    = Menu::get_menu_url($link, 'analysis', 'security_events');	
			$links[$d] = $link;
		        
		}
		
		//Widget's links
		$siem_url    = $links;
				
		$colors = "'#854F61'";
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";

		break;
		
	case "honeypot":
			
		//Date range.
		$range         = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 432000;

		//Limit of host to show in the widget.
		$limit         = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		
		//Link to the forensic site.
		$link          = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]=".gmdate("m",$timeutc-$range)."&time[0][3]=".gmdate("d",$timeutc-$range)."&time[0][4]=".gmdate("Y",$timeutc-$range)."&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]=".gmdate("m",$timeutc)."&time[1][3]=".gmdate("d",$timeutc)."&time[1][4]=".gmdate("Y",$timeutc)."&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query+DB&num_result_rows=-1&time_cnt=1&sort_order=time_d&hmenu=Forensics&smenu=Forensics";
		$forensic_link = Menu::get_menu_url($link, 'analysis', 'security_events');

		//Sql Query
		//TO DO: Use parameters in the query.
		$sqlgraph = "select sum(acid_event.cnt) as num_events,pl.name,pl.id as plugin_id FROM alienvault_siem.ac_acid_event acid_event, alienvault.plugin pl, alienvault.plugin_sid p WHERE p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND p.plugin_id=pl.id AND p.category_id in (".implode(',',$honeypot_category).") AND acid_event.timestamp BETWEEN '".gmdate("Y-m-d H:00:00",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:59:59")."' $query_where group by p.plugin_id order by num_events desc limit $limit";
		
		$rg = $conn->CacheExecute($sqlgraph);
		
		if (!$rg)
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
		    while (!$rg->EOF) 
		    {
							
		        $data[]  = $rg->fields["num_events"];
				$label[] = $rg->fields["name"];
                $links[] = $forensic_link . urlencode("&plugin=".$rg->fields["plugin_id"]);
		        
				$rg->MoveNext();
		    }
		}
		
		$colors = get_widget_colors(count($data));
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		break;
	

	case "countries":
		
		//Filters of sensors.
		
		//Date range.
		$range    = ($chart_info['range']  > 0)? ($chart_info['range'] * 86400) : 432000;

		//Limit of host to show in the widget.
		$limit    = ($chart_info['top'] != '')? $chart_info['top'] : 10;
		
		$geoloc   = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");
		$sqlgraph = "select acid_event.ip_src as ip, sum(acid_event.cnt) as num_events FROM alienvault_siem.po_acid_event AS acid_event, alienvault.plugin pl, alienvault.plugin_sid p WHERE p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND p.plugin_id=pl.id AND p.category_id in (".implode(',',$honeypot_category).") AND acid_event.timestamp BETWEEN '".gmdate("Y-m-d H:i:s",gmdate("U")-$range)."' AND '".gmdate("Y-m-d H:i:s")."' $query_where group by acid_event.ip_src order by num_events desc";

		$countries = array();
		$country_names = array();

        $rg = $conn->CacheExecute($sqlgraph);

		if (!$rg)
		{
		    print $conn->ErrorMsg();
		}
		else
		{
		    while (!$rg->EOF && count($countries) < $limit) 
		    {
        		$_country_aux   = $geoloc->get_country_by_host($conn, inet_ntop($rg->fields['ip']));
        		$country        = strtolower($_country_aux[0]);
        		$country_name   = $_country_aux[1];

        		if ($country_name != "") 
        		{
        			$countries[$country] += $rg->fields['num_events'];
        			$country_names[$country] = $country_name; 
        		}

		        $rg->MoveNext();
		    }
		}
		
		arsort($countries);
		
		foreach ($countries as $c=>$val) 
		{
			$data[]  = $val;
			$label[] = $country_names[$c];

			$link    = Menu::get_menu_url("/ossim/forensics/base_stat_country_alerts.php?cc=$c&location=alerts&category=19", 'analysis', 'security_events');
			$links[] = "'$link'";
		        
		}
				
		$colors = get_widget_colors(count($data));
		
		//Message in case of empty widget.
		$nodata_text = "No data available yet";
		
		$geoloc->close();
		
	break;
			
}

$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';
