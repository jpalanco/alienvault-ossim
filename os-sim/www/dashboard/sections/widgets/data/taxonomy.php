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

//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
Session::logcheck("analysis-menu", "EventsForensics");


//Setting DB connection
$db = new ossim_db(TRUE);
$conn = $db->connect();

//Getting the current user
$user = Session::get_session_user();

//This is the type of security widget.
$type = GET("type");
//ID of the widget
$id = GET("id");


//Validation
ossim_valid($type, OSS_TEXT, 'illegal:' . _("type"));
ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Widget ID"));

if (ossim_error()) {
	die(ossim_error());
}
//End of validation

//Array that contains the widget's general info
$winfo = array();
//Array that contains the info about the widget's representation, this is: chart info, tag cloud info, etc.
$chart_info = array();

//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if (!isset($id) || empty($id)) {

	$winfo['height'] = GET("height");                    //Height of the widget
	$winfo['wtype'] = GET("wtype");                    //Type of widget: chart, tag_cloud, etc.
	$winfo['asset'] = GET("asset");                    //Assets implicated in the widget
	$chart_info = json_decode(GET("value"), true);        //Params of the widget representation, this is: type of chart, legend params, etc.

} else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{

	//Getting the widget's info from DB
	$winfo = get_widget_data($conn, $id);        //Check it out in widget_common.php
	$chart_info = $winfo['params'];                    //Params of the widget representation, this is: type of chart, legend params, etc.

}

//Validation
ossim_valid($winfo['wtype'], OSS_TEXT, 'illegal:' . _("Type"));
ossim_valid($winfo['height'], OSS_DIGIT, 'illegal:' . _("Widget ID"));
ossim_valid($winfo['asset'], OSS_HEX, OSS_SCORE, OSS_ALPHA, OSS_USER, 'illegal:' . _("Asset/User/Entity"));

if (is_array($chart_info) && !empty($chart_info)) {
	$validation = get_array_validation();

	foreach ($chart_info as $key => $val) {
		if ($validation[$key] == '') {
			continue;
		}

		eval("ossim_valid(\"\$val\", " . $validation[$key] . ", 'illegal:" . _($key) . "');");
	}
}

if (ossim_error()) {
	die(ossim_error());
}
//End of validation.


$assets_filters = array();
$assets_filters = get_asset_filters($conn, $winfo['asset']);
$query_where = Security_report::make_where($conn, '', '', array(), $assets_filters, '', '', false);


//Variables to store the chart information
$data = array();    //The widget's data itself.
$label = array();    //Widget's label such as legend in charts, titles in tag clouds, etc...
$links = array();    //Links of each element of the widget.

$tz = Util::get_timezone();

/*
*
*	The code below is copied from /panel and will have to be adapted to the new DB structutre of the 4.0 version, that's why it is not commented.
*
*/

//Now the widget's data will be calculated depending of the widget's type.

session_write_close();

//It gets the range , date_from, date_to and the forensic link
//Date range.
$range = ($chart_info['range'] > 0) ? ($chart_info['range'] * 86400) : 432000;
//Dates
$date_from = date("Y-m-d", $timeutc - $range);
$date_to = date("Y-m-d");
$date_from_link = '&time_range=range&time%5B0%5D%5B1%5D=>=&time%5B0%5D%5B2%5D=' . date("m", $timeutc - $range) .'&time%5B0%5D%5B3%5D=' . date("j", $timeutc - $range).'&time%5B0%5D%5B4%5D=' . date("Y", $timeutc - $range).'&time%5B0%5D%5B5%5D=' . date("G", $timeutc - $range).'&time%5B0%5D%5B6%5D=' . date("i", $timeutc - $range).'&time%5B0%5D%5B7%5D=' . date("s", $timeutc - $range).'&time%5B0%5D%5B9%5D=AND' ;
$date_to_link = '&time%5B1%5D%5B1%5D=<=&time%5B1%5D%5B2%5D=' . date("m") . '&time%5B1%5D%5B3%5D=' . date("j").'&time%5B1%5D%5B4%5D=' . date("Y").'&time%5B1%5D%5B5%5D=' . date("G") . '&time%5B1%5D%5B6%5D=' . date("i") . '&time%5B1%5D%5B7%5D=' . date("s");
$link_date = '&time_cnt=2' . $date_from_link . $date_to_link;

//Link to the forensic site.
$forensic_link = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?&plugin=&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d", 'analysis', 'security_events', 'security_events');

//Top Hosts with Malware Detected
if ($type == 'malware_by_host') {

	//Limit of host to show in the widget.
	$limit = ($chart_info['top'] != '') ? $chart_info['top'] : 10;

	//Sql Query
	//TO DO: Use parameters in the query.
	$taxonomy = make_where($conn, array("Malware" => array("Spyware", "Adware", "Fake_Antivirus", "KeyLogger", "Trojan", "Virus", "Worm", "Generic", "Backdoor", "Virus_Detected") , "Antivirus" => array("Virus_Detected")));

	$conn->Execute("CREATE TEMPORARY TABLE _tmp_plg (id int(11) NOT NULL, sid int(11) NOT NULL, cat_id int(11) NOT NULL, subcategory_id int(11) NOT NULL,   PRIMARY KEY (`id`,`sid`)) ENGINE=MEMORY");
	$conn->Execute("REPLACE INTO _tmp_plg 
                    SELECT plugin_id,sid , c.cat_id, p.subcategory_id
                    FROM alienvault.plugin_sid p 
                    LEFT JOIN alienvault.subcategory c ON c.cat_id=p.category_id AND c.id=p.subcategory_id 
                    WHERE 1 $taxonomy");

	$sqlgraph = "SELECT SUM(acid_event.cnt) AS num_events, acid_event.ip_src AS name , p.cat_id, p.subcategory_id
                 FROM alienvault_siem.po_acid_event AS acid_event, _tmp_plg p 
                 WHERE p.id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid 
                 AND acid_event.timestamp BETWEEN '" . gmdate("Y-m-d H:i:s", gmdate("U") - $range) . "' AND '" . gmdate("Y-m-d H:i:s") . "' $query_where 
                 GROUP BY acid_event.ip_src ORDER BY num_events DESC limit $limit";


	$rg = $conn->CacheExecute($sqlgraph);

	$links = array();

	if (!$rg) {
		print $conn->ErrorMsg();
	} else {
		$conn->Execute("DROP TABLE _tmp_plg");

		while (!$rg->EOF) {
			if ($rg->fields["name"] == "") {
				$rg->fields["name"] = _("Unknown category");
			}

			$data[] = $rg->fields["num_events"];
			$label[] = $ip_src = inet_ntop($rg->fields["name"]);
			$link_category =  '&category%5B0%5D=' . $rg->fields["cat_id"] . '&category%5B1%5D=' . $rg->fields["subcategory_id"] ;
			$link_ip_src = '&ip_addr%5B0%5D%5B1%5D=ip_src&ip_addr%5B0%5D%5B2%5D=0&ip_addr%5B0%5D%5B3%5D=' . $ip_src . '&ip_addr_cnt=1' ;
			$links[] = $forensic_link . "&clear_allcriteria=1" . $link_category . $link_ip_src . $link_date;

			$rg->MoveNext();

		}
	}

	$colors = get_widget_colors(count($data));
} else {
	switch ($type) {
		case 'login':
			$taxonomy = make_where($conn, array("Authentication" => array("Login", "Failed")));
			break;

		case 'malware':
			$taxonomy = make_where($conn, array("Malware" => array("Spyware", "Adware", "Fake_Antivirus", "KeyLogger", "Trojan", "Virus", "Worm", "Generic", "Backdoor", "Virus_Detected"), "Antivirus" => array("Virus_Detected")));
			break;

		case 'firewall':
			$taxonomy = make_where($conn, array("Access" => array("Firewall_Permit", "Firewall_Deny", "ACL_Permit", "ACL_Deny")));
			break;

		case 'exploits':
			$taxonomy = make_where($conn, array("Exploit" => array("Shellcode", "SQL_Injection", "Browser", "ActiveX", "Command_Execution", "Cross_Site_Scripting", "FTP", "File_Inclusion", "Windows", "Directory_Traversal", "Attack_Response", "Denial_Of_Service", "PDF", "Buffer_Overflow", "Spoofing", "Format_String", "Misc", "DNS", "Mail", "Samba", "Linux")));
			break;

		case 'system':
			$taxonomy = make_where($conn, array("System" => array("Warning", "Emergency", "Critical", "Error", "Notification", "Information", "Debug", "Alert")));
			break;


	}

	if (!empty($taxonomy)) {

		//Sql Query
		//TO DO: Use parameters in the query.
		$query = "  SELECT sum(acid_event.cnt) as num_events, c.cat_id, c.id, c.name 
                    FROM alienvault_siem.ac_acid_event acid_event, alienvault.plugin_sid p, alienvault.subcategory c 
                    WHERE c.id=p.subcategory_id AND p.plugin_id=acid_event.plugin_id AND p.sid=acid_event.plugin_sid AND 
                    acid_event.timestamp BETWEEN '" . gmdate("Y-m-d H:00:00", $timeutc - $range) . "' 
                    AND '" . gmdate("Y-m-d H:59:59") . "' $query_where TAXONOMY group by c.id,c.name order by num_events desc LIMIT 10";

		$sqlgraph = str_replace("TAXONOMY", $taxonomy, $query);

		$rg = $conn->CacheExecute($sqlgraph);

		if (!$rg) {
			print $conn->ErrorMsg();
		} else {
			while (!$rg->EOF) {

				if ($rg->fields["name"] == "") {
					$rg->fields["name"] = _("Unknown category");
				}

				if ($rg->fields["num_events"] > 0) {
					$data[] = $rg->fields["num_events"];
					$label[] = _($rg->fields["name"]);
					$links[] = $forensic_link . "&clear_allcriteria=1" . '&category%5B0%5D=' . $rg->fields["cat_id"] . '&category%5B1%5D=' . $rg->fields["id"] . $link_date;
				}

				$rg->MoveNext();
			}
		}

		$colors = get_widget_colors(count($data));
	}
}

$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';
