<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/


require ("base_conf.php");
require ("vars_session.php");
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");

if (GET('sensor') != "") ossim_valid(GET('sensor'), OSS_DIGIT, 'illegal:' . _("sensor"));
$cc = GET('cc');
$location = GET('location');
$category = GET('category');
ossim_valid($cc, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("cc"));
ossim_valid($location, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("location"));
ossim_valid($category, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("category"));
if (ossim_error()) {
    die(ossim_error());
}

// Linked from Dashboard with taxonomy filter
$rest_filter = "";
if ($category != "") {
	$rest_filter = "&category%5B0%5D=$category";
}

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

// The below three lines were moved from line 87 because of the odd errors some users were having
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs = new QueryState();
$cs = new CriteriaState("base_stat_country.php");
$cs->ReadState();
$criteria_clauses = ProcessCriteria();
$from = "FROM acid_event " . $criteria_clauses[0];
$where = ($criteria_clauses[1] != "") ? " WHERE " . $criteria_clauses[1] : " ";

$sql = "(SELECT DISTINCT ip_src,'S' $from $where) UNION (SELECT DISTINCT ip_dst,'D' $from $where)";

$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);
$country_src = array();
$country_dst = array();

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
	
while ($myrow = $result->baseFetchRow()) {
    if ($myrow[0] == NULL) continue;
    $currentIP = inet_ntop($myrow[0]);
    $ip_type = $myrow[1];
    
    $_country_aux = $geoloc->get_country_by_host($conn, $currentIP);
    $country      = strtolower($_country_aux[0]);
    $country_name = $_country_aux[1];
        
    if ($cc == "local" && $country_name == "") { // local ip
        if ($ip_type=='S')
            $country_src[] = $currentIP;
        else
            $country_dst[] = $currentIP;
    } elseif ($country == $cc) {
        if ($ip_type=='S')
            $country_src[] = $currentIP;
        else
            $country_dst[] = $currentIP;
    }
}
$result->baseFreeRows();
$dbo->close($_conn);
$geoloc->close();
//
if ($location == "srcaddress") $country_dst=array();
if ($location == "dstaddress") $country_src=array();
//
$ips = array();
$i = 1;
$total = count($country_src)+count($country_dst);
foreach ($country_src as $ip) {
	$or = ($i < $total) ? "OR" : "";
	$fields = explode(".",$ip);
	$ips[] = array(" ","ip_src","=",$fields[0],$fields[1],$fields[2],$fields[3],$ip," ",$or,"");
	//$ips[] = array(" ","ip_src","=",$ip,"","","",""," ",$or,"");
	$i++;
}
foreach ($country_dst as $ip) {
	$or = ($i < $total) ? "OR" : "";
	$fields = explode(".",$ip);
	$ips[] = array(" ","ip_dst","=",$fields[0],$fields[1],$fields[2],$fields[3],$ip," ",$or,"");
	//$ips[] = array(" ","ip_src","=",$ip,"","","",""," ",$or,"");
	$i++;
}

$_SESSION['ip_addr'] = $ips;
$_SESSION['ip_addr_cnt'] = $total;
$_SESSION['layer4'] = "";
$_SESSION["ip_field"] = array (
	array ("","","=")
);
$_SESSION["ip_field_cnt"] = 1;

//print_r($_SESSION["ip_addr"]); exit();
if ($location == "alerts") header('Location:base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1'.$rest_filter);
if ($location == "address" || $location == "srcaddress") header('Location:base_stat_uaddr.php?addr_type=1&sort_order=occur_d');
if ($location == "dstaddress") header('Location:base_stat_uaddr.php?addr_type=2&sort_order=occur_d');
?>
