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

Session::logcheck('analysis-menu', 'EventsForensics');
Session::logcheck('report-menu', 'ReportsReportServer');

$rtype = GET('rtype');
$pro   = Session::is_pro();

ossim_valid($rtype, OSS_DIGIT, 'illegal:' . _("Report type"));

if ( ossim_error() ) 
{
    $config_nt = array(
			'content' => _("Invalid report type"),
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => false
			),
			'style'   => 'margin: 20px auto; width: 80%; text-align: center;'
		); 
					
			
	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
	exit();
}

$addr_type = intval(GET('addr_type'));

$type = array("33" => "Events",
              "38" => "Sensors",
              "36" => "Unique_Events",
              "46" => "Unique_Plugins",
              "40" => "Unique_Addresses",
              "42" => "Source_Port",
              "44" => "Destination_Port",
              "37" => "Unique_IP_links",
              "48" => "Unique_Country_Events");

$current_cols_titles = array(
    "SIGNATURE" => _("Signature"),
    "ENTITY" => _("Context"),
    "DATE" => _("Date")." ".Util::timezone($tz),
    "IP_PORTSRC" => _("Source"),
    "IP_PORTDST" => _("Destination"),
    "SENSOR" => _("Sensor"),
    "IP_SRC" => _("Src IP"),
    "IP_DST" => _("Dst IP"),   
    "IP_SRC_FQDN" => _("Src IP FQDN"),
    "IP_DST_FQDN" => _("Dst IP FQDN"),     
    "PORT_SRC" => _("Src Port"),
    "PORT_DST" => _("Dst Port"),
    "ASSET" => _("Asset S->D"),
    "PRIORITY" => _("Prio"),
    "RELIABILITY" => _("Rel"),
    "RISK" => _("Risk"),
    "IP_PROTO" => _("L4-proto"),
    "USERDATA1" => _("Userdata1"),
    "USERDATA2" => _("Userdata2"),
    "USERDATA3" => _("Userdata3"),
    "USERDATA4" => _("Userdata4"),
    "USERDATA5" => _("Userdata5"),
    "USERDATA6" => _("Userdata6"),
    "USERDATA7" => _("Userdata7"),
    "USERDATA8" => _("Userdata8"),
    "USERDATA9" => _("Userdata9"),
    "USERNAME" => _("Username"),
    "FILENAME" => _("Filename"),
    "PASSWORD" => _("Password"),
    "PAYLOAD" => _("Payload"),
    "PLUGIN_ID" => _("Data Source ID"),
    "PLUGIN_SID" => _("Event Type ID"),
    "PLUGIN_DESC" => _("Data Source Description"),
    "PLUGIN_NAME" => _("Data Source Name"),
    "PLUGIN_SOURCE_TYPE" => _("Source Type"),
    "PLUGIN_SID_CATEGORY" => _("Category"),
    "PLUGIN_SID_SUBCATEGORY" => _("SubCategory"),
	'SRC_USERDOMAIN' => _("IDM User@Domain Src IP"),
	'DST_USERDOMAIN' => _("IDM User@Domain Dst IP"),
    'SRC_HOSTNAME' => _("IDM Source"),
    'DST_HOSTNAME' => _("IDM Destination"),
    'SRC_MAC' => _("IDM MAC Src IP"),
    'DST_MAC' => _("IDM MAC Dst IP"),
    'REP_PRIO_SRC' => _("Rep Src IP Prio"),
    'REP_PRIO_DST' => _("Rep Dst IP Prio"),
    'REP_REL_SRC' => _("Rep Src IP Rel"),
    'REP_REL_DST' => _("Rep Dst IP Rel"),
    'REP_ACT_SRC' => _("Rep Src IP Act"),
    'REP_ACT_DST' => _("Rep Dst IP Act"),
	"DEVICE" => _("Device")
);

$user      = $_SESSION["_user"];
$path_conf = $GLOBALS["CONF"];

/* database connect */
$db   = new ossim_db();
$conn = $db->connect();
//$conn = $db->custom_connect('localhost',$path_conf->get_conf("ossim_user"),$path_conf->get_conf("ossim_pass"));

$config = new User_config($conn);
$default_view = ($config->get($login, 'custom_view_default', 'php', "siem") != "") ? $config->get($login, 'custom_view_default', 'php', "siem") : (($idm_enabled) ? 'IDM' : 'default');

$output_name = $type[$rtype]."_" . $user . "_" . date("Y-m-d",time()) . ".csv";

$csv_header = "";
$csv_body   = "";

$var_data   = ( Session::show_entities() ) ? "Context" : "Sensor";


if($type[$rtype]=="Events") {

	$sql = "SELECT dataV1, dataV2, dataV11, dataV3, dataV5, dataV10, cell_data
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
	if ($_SESSION['current_cview'] != $default_view) {
		foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $colname) {
			if ($csv_header != "") { $csv_header .= ";"; }
			$csv_header .= $current_cols_titles[$colname];
		}
		$csv_header .= "\n";
	} else {
		$csv_header .= "Signature;Date;$var_data;Source;Destination;Risk\n";
	}
}
else if($type[$rtype]=="Sensors") {
    
	$csv_header .= "Sensor;Name;Total Events #;Unique Events #;Unique Src #;Unique Dst #\n";
    $sql = "SELECT dataV7, dataV1, dataI2, dataI3, dataV3, dataV4
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Events") {
   $csv_header .= "Signature;Total #;Unique Src #;Unique Dst #\n";
   $sql = "SELECT dataV1, dataV2, dataI2, dataI3
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
			
}
else if($type[$rtype]=="Unique_Plugins") {
   $csv_header .= "Plugin;Events #;$var_data;Last Event;Date\n";
   $sql = "SELECT dataV1, dataI1, dataV11, dataV2, dataV7
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Addresses") {
    
	if ( $addr_type == 1 )
       $csv_header .= "Src IP address;$var_data;Total #;Unique Events #;Unique Dst Contacted #\n";
    else
       $csv_header .= "Dst IP address;$var_data;Total #;Unique Events #;Unique Src Contacted #\n";

    $sql = "SELECT dataV1, dataV11, dataI3, dataV3, dataV4
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Source_Port" || $type[$rtype]=="Destination_Port") {
    $csv_header .= "Port;$var_data;Occurrences #;Unique Events #;Unique Src #; Unique Dst #\n";
    $sql = "SELECT dataV1, dataV11, dataI3, dataV2, dataV3, dataV4
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_IP_links") {
    $csv_header .= "Source IP;Destination IP;Protocol;Unique Dst Ports #;Unique Events #;Total Events #\n";
    $sql = "SELECT dataV1, dataV3, dataV5, dataI1, dataI2, dataI3
            FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}
else if($type[$rtype]=="Unique_Country_Events") {
   $csv_header .= "Country;Total #;Unique Src #;Unique Dst #;Events\n";
   $sql = "SELECT dataV1, dataI1, dataI2, dataI3
           FROM datawarehouse.report_data WHERE id_report_data_type=$rtype and user='$user'";
}



$result = $conn->Execute($sql);

while ( !$result->EOF ) 
{
    if($type[$rtype]=="Events") 
	{
        list ($dataV1, $dataV2, $dataV11, $dataV3, $dataV5, $dataV10, $cell_data) = $result->fields;

        $m = array();
        $risk   = 0;
             
        preg_match('/value=(\d+)/', $dataV10, $m);
        $risk = $m[1];      
        if ($_SESSION['current_cview'] != $default_view) {
			$cell_data_array = json_decode($cell_data);
        	$flag = false;
			foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $colname) {
				if ($flag) { $csv_body .= ";"; }
				$coldata = $cell_data_array->$colname;
				if (preg_match("/.*bar2\.php\?value\=(\d+)\&value2\=(\d+).*/", $coldata, $found)) {
					$coldata = $found[1]."->".$found[2];
				}
				elseif (preg_match("/.*bar2\.php\?value\=(\d+).*/", $coldata, $found)) {
					$coldata = $found[1];
				}
				$coldata = preg_replace("/\<img [^\>]+\>/", "", $coldata);
				$coldata = preg_replace("/\<br\>/", "", $coldata);
				$coldata = str_replace(";", ",", $coldata);
        		$csv_body .= $coldata;
        		$flag = true;
			}
			$csv_body .= "\n";
		} else {
        	$csv_body .= "$dataV1;$dataV2;$dataV11;$dataV3;$dataV5;$risk\n";
		}
    }
    if($type[$rtype]=="Sensors") {
        list ($dataV7, $dataV1, $dataI2, $dataI3, $dataV3, $dataV4) = $result->fields;
        $csv_body .= "$dataV7;$dataV1;$dataI2;$dataI3;$dataV3;$dataV4\n";
	}
    else if ($type[$rtype]=="Unique_Events"){
        list ($dataV1, $dataV2, $dataI2, $dataI3, $dataV3, $dataV4) = $result->fields;
        $csv_body .= "$dataV1;$dataV2;$dataI2;$dataI3;\n";
    }
    else if($type[$rtype]=="Unique_Plugins") {
        list ($dataV1, $dataI1, $dataV11, $dataV2, $dataV7) = $result->fields;
        $csv_body .= "$dataV1;$dataI1;$dataV11;$dataV2;$dataV7\n";
    }
    else if($type[$rtype]=="Unique_Addresses") {
        list ($dataV1, $dataV11, $dataI3, $dataV3, $dataV4) = $result->fields;
        $csv_body .= "$dataV1;$dataV11;$dataI3;$dataV3;$dataV4\n";
    }
    else if($type[$rtype]=="Source_Port" || $type[$rtype]=="Destination_Port") {
        list ($dataV1, $dataV11, $dataI3, $dataV2, $dataV3, $dataV4) = $result->fields;
        $csv_body .= "$dataV1;$dataV11;$dataI3;$dataV2;$dataV3;$dataV4;\n";
    }
    else if($type[$rtype]=="Unique_IP_links") {
        list ($dataV1, $dataV3, $dataV5, $dataI1, $dataI2, $dataI3) = $result->fields;
        $csv_body .= "$dataV1;$dataV3;$dataV5;$dataI1;$dataI2;$dataI3\n";
    }
    else if($type[$rtype]=="Unique_Country_Events") {
        list ($dataV1, $dataI1, $dataI2, $dataI3) = $result->fields;
       $csv_body .= "$dataV1;$dataI1;$dataI2;$dataI3\n";
    }
    $result->MoveNext();
}

if ( !empty($csv_body) )
{
	$csv_body = $csv_header.$csv_body; 
	header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Disposition: attachment; filename=\"$output_name\";");
	header('Content-Type: application/force-download');
	header("Content-Transfer-Encoding: binary");
	header("Content-length: " . strlen($csv_body));
	
	echo $csv_body;
}
else{
	
	if ( $result->RecordCount() == 0 ) 
	{
		$nf_type = 'nf_warning';
		$e_txt   = _("No data.  Empty file.");
	}
	else
	{
		$nf_type = 'nf_error';
		$e_txt   = _("An error occurred: Failed to download file");
	}
	
	
	$config_nt = array(
			'content' => $e_txt,
			'options' => array (
				'type'          => $nf_type,
				'cancel_button' => false
			),
			'style'   => 'margin: 20px auto; width: 80%; text-align: center;'
		); 
					
			
	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
	exit();
}	
?>