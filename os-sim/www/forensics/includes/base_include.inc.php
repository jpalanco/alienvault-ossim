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
**/


ini_set('max_execution_time', 1200);
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/includes/base_db.inc.php");
//
GLOBAL $db_memcache;
$db_memcache = intval($db_memcache);
//
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
if (!isset($_SESSION["_user"])) {
    $ossim_link = $conf->get_conf("ossim_link", FALSE);
    $login_location = $ossim_link . '/session/login.php';
	header("Location: $login_location");
	exit;
}
// Solera API
$_SESSION["_solera"] = ($conf->get_conf("solera_enable", FALSE)) ? true : false;
//
// Get Host names to translate IP -> Host Name
require_once ("ossim_db.inc");
$dbo = new ossim_db(true);
// Multiple Database Server selector
$conn = $dbo->connect();
$database_servers = Databases::get_list($conn);
$dbo->close();
//
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
{
	$dbo->enable_cache();
	$conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
}
else
{
	$dbo->enable_cache();
	$conn = $dbo->connect();
}

include_once ("$BASE_path/base_common.php");
$sensors = $hosts = $ossim_servers = $sensor_names = array();

$sensors                = Av_sensor::get_all($conn, TRUE);
foreach ($sensors as $_sensor)
{
    $sensor_names[$_sensor['ip']] = $_sensor['name'];
}

list($hosts, $host_ids) = Asset_host::get_basic_list($conn, array(), TRUE);
$entities               = Session::get_all_entities($conn);

$rep_activities = Reputation::get_reputation_activities($conn,"ORDER BY descr",$db_memcache);
$rep_severities = array("High" => "High Severity", "Medium" => "Medium Severity", "Low" => "Low Severity");	
//
// added default home host/lan to SESSION[ip_addr]
//
if ($_GET["addhomeips"]=="src" || $_GET["addhomeips"]=="dst") {
    $_nets = Asset_net::get_all($conn,TRUE);

	// adding all not external lans
	$local_ips = array();
	$total_ips = 0;
	foreach ($_nets as $current_net) {
	    $cirds = explode(",",$current_net['ips']);
	    foreach ($cirds as $cidr)
    		if (!$current_net['external'] && preg_match("/(.*)\.(.*)\.(.*)\.(.*)\/(.*)/",$cidr,$fields)) {
    			$local_ips[] = array(" ","ip_".$_GET["addhomeips"],"=",$fields[1],$fields[2],$fields[3],$fields[4],$cidr," ","OR",$fields[5]);
    			$total_ips++;
    		}
    }
	// adding rest of hosts
	foreach ($hosts as $current_ip => $_hips) {
	   foreach ($_hips as $ctx => $_hdata)
            if ($_hdata["home"] && !Asset_host::is_ip_in_cache_cidr($conn, $current_ip, $ctx)) {
                $fields = explode(".",$current_ip);
                $local_ips[] = array(" ","ip_".$_GET["addhomeips"],"=",$fields[0],$fields[1],$fields[2],$fields[3],$current_ip," ","OR","");
                $total_ips++;
            }
    }
	if (count($local_ips)>0) {
		$local_ips[count($local_ips)-1][9]=" "; // delete last OR
		$_SESSION['ip_addr'] = $_GET['ip_addr'] = $local_ips;
		$_SESSION['ip_addr_cnt'] = $_GET['ip_addr_cnt'] = $total_ips;
	}
	$_SESSION["_hostgroup"] = "";
	//print_r($_SESSION["ip_addr"]);
} elseif ($_GET["addhomeips"]=="-1"){
	if ($_SESSION["_hostgroup"]!="") {
		$_SESSION["_hostgroup"] = "";
		$_SESSION['ip_addr'] = "";
		$_SESSION['ip_addr_cnt'] = "";
	}
}
elseif ($_GET["addhomeips"]!="" && valid_hex32($_GET["addhomeips"]))
{ // PENDING CTX
	require_once("base_common.php");
	$_SESSION["_hostgroup"] = $_GET["addhomeips"];
	
	$ips       = array();
	$total_ips = 0;
	$hg        = GetOssimHostsFromHostGroups($_SESSION["_hostgroup"]);

	foreach ($hg as $iph) 
	{
	    $hips = explode(',', $iph);
	    
	    foreach ($hips as $iph) 
	    {
    		$fields = explode('.', $iph);
    		
    		$ips[] = array(' ', 'ip_src', '=', $fields[0], $fields[1], $fields[2], $fields[3], $iph, ' ', 'OR', '');
    		$total_ips++;
    		
    		$ips[] = array(' ', 'ip_dst', '=', $fields[0], $fields[1], $fields[2], $fields[3], $iph, ' ', 'OR', '');
    		$total_ips++;
        }
	}

	if (count($ips)>0) 
	{
		$ips[count($ips)-1][9]   = " "; // delete last OR
		$_SESSION['ip_addr']     = $_GET['ip_addr'] = $ips;
		$_SESSION['ip_addr_cnt'] = $_GET['ip_addr_cnt'] = $total_ips;
	}
	
}
$dbo->close($conn);
//
include_once ("$BASE_path/includes/base_output_html.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_criteria.inc.php");
include_once ("$BASE_path/includes/base_output_query.inc.php");
include_once ("$BASE_path/includes/base_log_error.inc.php");
include_once ("$BASE_path/includes/base_log_timing.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/includes/base_cache.inc.php");
include_once ("$BASE_path/includes/base_net.inc.php");
include_once ("$BASE_path/includes/base_signature.inc.php");
?>
