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
require_once 'classes/pdf.inc';
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';

Session::menu_perms("analysis-menu", "EventsForensics");

$path = '/usr/share/ossim/www/report/os_reports/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);


// Initialize var

$report_name    = $report_data['report_name'];
$subreport_name = $report_data['subreports'][$subreport_id]['name'];
$title          = $subreport_name;


$user           = POST('reportUser');
$report_unit    = POST('reportUnit'); 
$type           = POST('Type');
$date_from      = POST('date_from');  
$date_to        = POST('date_to'); 

ossim_valid($user,        OSS_USER, OSS_NULLABLE,             'illegal:' . _('User'));
ossim_valid($report_unit, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _('reportUnit'));
ossim_valid($type,        OSS_DIGIT, OSS_NULLABLE,            'illegal:' . _('Type'));
ossim_valid($date_from,   OSS_DATE, OSS_NULLABLE,             'illegal:' . _('Date From'));
ossim_valid($date_to,     OSS_DATE, OSS_NULLABLE,             'illegal:' . _('Date To'));

if (ossim_error())
{
    echo 'error###'.ossim_get_error_clean();
    exit;
}

//Variable field
$pro            = Session::is_pro();
$var_field      = ( Session::show_entities() ) ? "Context" : "Sensor";

/*
Security_DB_Events
Security_DB_Unique_Events
Security_DB_Sensors
Security_DB_Unique_Address - Types: 1, 2
Security_DB_Source_Port - Types: 0, 1, 2
Security_DB_Destination_Port - Types: 0, 1, 2
Security_DB_Unique_Plugin
Security_DB_Unique_Country_Events
SIEM_Events_Unique_IP_Links
*/


$type_id = array("Security_DB_Events"                => "33",
				 "Security_DB_Unique_Events"         => "36",
				 "Security_DB_Sensors"               => "38",
				 "Security_DB_Unique_Address"        => "40",
				 "Security_DB_Source_Port"           => "42",
				 "Security_DB_Destination_Port"      => "44",
				 "Security_DB_Unique_Plugin"         => "46",
				 "Security_DB_Unique_Country_Events" => "48",
				 "SIEM_Events_Unique_IP_Links"       => "37");
				 

$fields = array( "Security_DB_Events"                => "dataV1, dataV2, dataV3, dataV4, dataV5, dataV6, dataV10, dataV11",
				 "Security_DB_Unique_Events"         => "dataV1, dataV2, dataI2, dataI3",
				 "Security_DB_Sensors"               => "dataV1, dataV2, dataV3, dataV4, dataV7, dataI2, dataI3",
				 "Security_DB_Unique_Address"        => "dataV1, cell_data, dataV11, dataI3, dataV3, dataV4",
				 "Security_DB_Source_Port"           => "dataV1, dataV11, dataI3, dataV2, dataV3, dataV4",
				 "Security_DB_Destination_Port"      => "dataV1, dataV11, dataI3, dataV2, dataV3, dataV4",
				 "Security_DB_Unique_Plugin"         => "dataV1, dataV11, dataI1, dataV2, dataV7",
				 "Security_DB_Unique_Country_Events" => "dataV1, cell_data, dataV3, dataI1, dataI2, dataI3",
				 "SIEM_Events_Unique_IP_Links"       => "dataV1, cell_data, dataV3, dataV4, dataV5, dataI1, dataI2, dataI3");
				

//Query Parameters
$params   = array();
$params[] = $type_id[$report_unit];
$params[] = $user;

$where = "";	
if ( $type != '' )
{
	$params[] = $type;
	$where    = " AND dataI1 = ?";
}
		 
$query = "SELECT ".$fields[$report_unit]." FROM datawarehouse.report_data WHERE id_report_data_type=? AND user=?";
		  
if ( !empty($where) ){
		$query .= $where;
}
?>