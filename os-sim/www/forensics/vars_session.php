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
require_once 'classes/asset_host.inc';
Session::logcheck("analysis-menu", "EventsForensics");

if ( !isset($_SESSION["_user"]) )
{
	$ossim_link     = $conf->get_conf("ossim_link", FALSE);
    $login_location = $ossim_link . '/session/login.php';

	header("Location: $login_location");
	exit();
}


// Timezone correction
$tz = Util::get_timezone();
$timetz = gmdate("U")+(3600*$tz); // time to generate dates with timezone correction

// IDM Mode?
$idm_enabled    = ( $conf->get_conf("enable_idm", FALSE) == 1 && Session::is_pro() )     ? true : false;
$cloud_instance = ( $conf->get_conf("cloud_instance", FALSE) == 1 && Session::is_pro() ) ? true : false;

$_SESSION['_idm'] = $idm_enabled;

// Custom Views
$login    = Session::get_session_user();

$db_aux   = new ossim_db();
$conn_aux = $db_aux->connect();


$config = new User_config($conn_aux);



$_SESSION['views'] = $config->get($login, 'custom_views', 'php', "siem");
$default_view = ($config->get($login, 'custom_view_default', 'php', "siem") != "") ? $config->get($login, 'custom_view_default', 'php', "siem") : (($idm_enabled) ? 'IDM' : 'default');

// First create default views if not exists (important!)
$session_data = $_SESSION;
foreach ($_SESSION as $k => $v)
{
    if (preg_match("/^(_|alarms_|back_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|siem_current_query|siem_current_query_graph|deletetask|mdspw).*/",$k))
    	unset($session_data[$k]);
}

// Default
if ($_SESSION['views']['default'] == "" || count($_SESSION['views']['default']['cols'])==9)
{
    $_SESSION['views']['default']['cols'] = array('SIGNATURE','DATE','SENSOR','IP_PORTSRC','IP_PORTDST','ASSET','RISK');
    //$_SESSION['views']['default']['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','IP_PORTDST','ASSET','PRIORITY','RELIABILITY','RISK','IP_PROTO');
    //$_SESSION['views']['Detail']['data'] = $session_data;
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}

// Taxonomy
if ($_SESSION['views']['Taxonomy'] == "")
{
	$_SESSION['views']['Taxonomy']['cols'] = array('SIGNATURE','DATE','IP_SRC','IP_DST','PRIORITY','RISK','PLUGIN_NAME','PLUGIN_SOURCE_TYPE','PLUGIN_SID_CATEGORY','PLUGIN_SID_SUBCATEGORY');
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}

// Reputation
if ($_SESSION['views']['Reputation'] == "")
{
	$_SESSION['views']['Reputation']['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','REP_PRIO_SRC','REP_ACT_SRC','IP_PORTDST','REP_PRIO_DST','REP_ACT_DST');
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}

// Detail
if ($_SESSION['views']['Detail'] == "")
{
	$_SESSION['views']['Detail']['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','SENSOR','PLUGIN_SID_CATEGORY','PLUGIN_SID_SUBCATEGORY','USERNAME','PASSWORD','USERDATA1','FILENAME');
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}

// Risk Analysis
if ($_SESSION['views']['Risk Analysis'] == "")
{
	$_SESSION['views']['Risk Analysis']['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','IP_PORTDST','USERNAME','ASSET','PRIORITY','RELIABILITY','RISK');
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}

// IDM
if ($idm_enabled && $_SESSION['views']['IDM'] == "")
{
	$_SESSION['views']['IDM']['cols'] = array('SIGNATURE','DATE','SENSOR','IP_PORTSRC','IP_PORTDST','RISK');
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}

// sensor or entity queries
if (preg_match("/^[A-F0-9]{32}$/i",$_GET["sensor"]))
{
    $_GET["ctx"] = $_GET["sensor"];
    unset($_GET["sensor"]);
}


// ********* IP and Host Searches ***********

if ($_GET["search_str"]=="search term") unset($_GET["search_str"]);
$ips_submit  = array(_("Src or Dst IP"),_("Src IP"),_("Dst IP"));
$host_submit = array(_("Src or Dst Host"),_("Src Host"),_("Dst Host"));

// Conversion: Searching by hostname, but IP selected
if ($_GET["search_str"] != "" && in_array($_GET["submit"], $ips_submit) && !preg_match("/\d+\.\d+\.\d+\.\d+/", $_GET["search_str"]))
{
    $negated_op         = (preg_match('/^\!/', $_GET["search_str"])) ? '!' : '';
    $_GET["search_str"] = Util::htmlentities(preg_replace("/[^0-9A-Za-z\!\-\_\.]/", "", $_GET["search_str"])); // htmlentities for fortify test
    $_ips_aux           = Asset_host::get_ips_by_name($conn_aux, $_GET["search_str"]);
    $_GET["search_str"] = $negated_op.implode(" OR $negated_op", array_keys($_ips_aux));
}

// Conversion: Searching by IP, but Host selected
if ($_GET["search_str"] != "" && in_array($_GET["submit"], $host_submit) && preg_match("/^\!?\d+\.\d+\.\d+\.\d+$/", $_GET["search_str"]))
{
	$_GET['submit'] = str_replace(" Host", " IP", $_GET['submit']);
}

// Hostname
if ($_GET["search_str"] != "" && in_array($_GET["submit"], $host_submit) && !preg_match("/\d+\.\d+\.\d+\.\d+/", $_GET["search_str"]))
{
    $negated_op         = (preg_match('/^\!/', $_GET["search_str"])) ? 'NOT IN' : 'IN';
	$_GET["search_str"] = Util::htmlentities(preg_replace("/[^0-9A-Za-z\!\-\_\.]/", "", $_GET["search_str"])); // htmlentities for fortify test
	$hids = Asset_host::get_id_by_name($conn_aux, $_GET["search_str"]);
	$htype = ($_GET["submit"]==_("Src or Dst Host")) ? "both" : (($_GET["submit"]==_("Src Host")) ? "src" : "dst");
	$_SESSION["hostid"] = array(array_shift(array_keys($hids)), $_GET["search_str"], $htype, $negated_op);
	unset($_GET["search_str"]);
}

$db_aux->close();

if ($_SESSION['view_name_changed']) { $_GET['custom_view'] = $_SESSION['view_name_changed']; $_SESSION['view_name_changed'] = ""; $_SESSION['norefresh'] = 1; }
else $_SESSION['norefresh'] = "";

$custom_view = $_GET['custom_view'];
ossim_valid($custom_view, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, "Invalid: custom_view");

if (ossim_error()) {
    die(ossim_error());
}

if ($custom_view != "") {
	$_SESSION['current_cview'] = Util::htmlentities($custom_view);
	if (is_array($_SESSION['views'][$custom_view]['data']))
		foreach ($_SESSION['views'][$custom_view]['data'] as $skey=>$sval) {
			if (!preg_match("/^(_|alarms_|back_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|siem_current_query|siem_current_query_graph|deletetask|mdspw).*/",$skey))
			    $_SESSION[$skey] = $sval;
			else
                unset($_SESSION[$skey]);
		}
}
if ($_SESSION['current_cview'] == "") {
	$_SESSION['current_cview'] = $default_view;
}

// Columns data (for matching on print functions)
$_SESSION['views_data'] = array(
	"SID_NAME" => array("title"=>"sid name","width"=>"40","celldata" => ""),
	"IP_PROTO" => array("title"=>"L4-proto","width"=>"40","celldata" => "")
);

// TIME RANGE
// Security hash
$valid_operator = array(">=" => ">=", "<=" => "<=", ">" => ">", "<" => "<", "=" => "=");
if ($_GET['time_range'] != "") {
    // defined => save into session
    if (isset($_GET['time'])) {
    	// Secure assign to session time
    	$_SESSION['time'][0][1] = $valid_operator[$_GET['time'][0][1]];

    	$_SESSION['time'][0][0] = Util::htmlentities($_GET['time'][0][0]);
    	$_SESSION['time'][0][2] = Util::htmlentities($_GET['time'][0][2]);
    	$_SESSION['time'][0][3] = Util::htmlentities($_GET['time'][0][3]);
    	$_SESSION['time'][0][4] = Util::htmlentities($_GET['time'][0][4]);
    	$_SESSION['time'][0][5] = Util::htmlentities($_GET['time'][0][5]);
    	$_SESSION['time'][0][6] = Util::htmlentities($_GET['time'][0][6]);
    	$_SESSION['time'][0][7] = Util::htmlentities($_GET['time'][0][7]);
    	$_SESSION['time'][0][8] = Util::htmlentities($_GET['time'][0][8]);
    	$_SESSION['time'][0][9] = Util::htmlentities($_GET['time'][0][9]);

    	$_SESSION['time'][1][1] = $valid_operator[$_GET['time'][1][1]];

    	$_SESSION['time'][1][0] = Util::htmlentities($_GET['time'][1][0]);
    	$_SESSION['time'][1][2] = Util::htmlentities($_GET['time'][1][2]);
    	$_SESSION['time'][1][3] = Util::htmlentities($_GET['time'][1][3]);
    	$_SESSION['time'][1][4] = Util::htmlentities($_GET['time'][1][4]);
    	$_SESSION['time'][1][5] = Util::htmlentities($_GET['time'][1][5]);
    	$_SESSION['time'][1][6] = Util::htmlentities($_GET['time'][1][6]);
    	$_SESSION['time'][1][7] = Util::htmlentities($_GET['time'][1][7]);
    	$_SESSION['time'][1][8] = Util::htmlentities($_GET['time'][1][8]);
    	$_SESSION['time'][1][9] = Util::htmlentities($_GET['time'][1][9]);
    }
    if (isset($_GET['time_cnt'])) $_SESSION['time_cnt'] = intval($_GET['time_cnt']);
    $_GET['time_range'] = Util::htmlentities(preg_replace("/[^A-Za-z0-9]/", "",$_GET['time_range'])); // htmlentities for fortify test
    if (isset($_GET['time_range'])) $_SESSION['time_range'] = Util::htmlentities($_GET['time_range']);
} elseif ($_SESSION['time_range'] != "" && $_GET['date_range'] == "") {
    // not defined => load from session or unset
    if ($_GET["clear_criteria"] == "time" || $_GET["clear_allcriteria"] == 1) {
        unset($_SESSION['time']);
        unset($_SESSION['time_cnt']);
        $_GET['time_range']     = "all";
        $_SESSION['time_range'] = "all";
    } else {
        if (isset($_SESSION['time'])) {
        	// Secure assign to session time
	    	$_GET['time'][0][1] = $valid_operator[$_SESSION['time'][0][1]];

	    	$_GET['time'][0][0] = Util::htmlentities($_SESSION['time'][0][0]);
	    	$_GET['time'][0][2] = Util::htmlentities($_SESSION['time'][0][2]);
	    	$_GET['time'][0][3] = Util::htmlentities($_SESSION['time'][0][3]);
	    	$_GET['time'][0][4] = Util::htmlentities($_SESSION['time'][0][4]);
	    	$_GET['time'][0][5] = Util::htmlentities($_SESSION['time'][0][5]);
	    	$_GET['time'][0][6] = Util::htmlentities($_SESSION['time'][0][6]);
	    	$_GET['time'][0][7] = Util::htmlentities($_SESSION['time'][0][7]);
	    	$_GET['time'][0][8] = Util::htmlentities($_SESSION['time'][0][8]);
	    	$_GET['time'][0][9] = Util::htmlentities($_SESSION['time'][0][9]);

	    	$_GET['time'][1][1] = $valid_operator[$_SESSION['time'][1][1]];

	    	$_GET['time'][1][0] = Util::htmlentities($_SESSION['time'][1][0]);
	    	$_GET['time'][1][2] = Util::htmlentities($_SESSION['time'][1][2]);
	    	$_GET['time'][1][3] = Util::htmlentities($_SESSION['time'][1][3]);
	    	$_GET['time'][1][4] = Util::htmlentities($_SESSION['time'][1][4]);
	    	$_GET['time'][1][5] = Util::htmlentities($_SESSION['time'][1][5]);
	    	$_GET['time'][1][6] = Util::htmlentities($_SESSION['time'][1][6]);
	    	$_GET['time'][1][7] = Util::htmlentities($_SESSION['time'][1][7]);
	    	$_GET['time'][1][8] = Util::htmlentities($_SESSION['time'][1][8]);
	    	$_GET['time'][1][9] = Util::htmlentities($_SESSION['time'][1][9]);
        }
        
        // From advanced search is always a ranged search
        if (!empty($_POST['time']))
        {
            $_SESSION['time_range'] = 'range';
        }
        
        if (isset($_SESSION['time_cnt'])) $_GET['time_cnt'] = $_SESSION['time_cnt'];
        if (isset($_SESSION['time_range'])) $_GET['time_range'] = $_SESSION['time_range'];
    }
} elseif ($_GET['date_range'] == "week") {
	$start_week = explode("-",gmdate("Y-m-d", $timetz - (24 * 60 * 60 * 7)));
	$_GET['time'][0] = array(
        null,
        ">=",
        $start_week[1] ,
        $start_week[2] ,
        $start_week[0] ,
        null,
        null,
        null,
        null,
        null
    );
    $_GET['time_cnt'] = "1";
    $_GET['time_range'] = "week";

    // Secure assign to session time
    $_SESSION['time'][0][1] = $valid_operator[$_GET['time'][0][1]];

    $_SESSION['time'][0][0] = Util::htmlentities($_GET['time'][0][0]);
    $_SESSION['time'][0][2] = Util::htmlentities($_GET['time'][0][2]);
    $_SESSION['time'][0][3] = Util::htmlentities($_GET['time'][0][3]);
    $_SESSION['time'][0][4] = Util::htmlentities($_GET['time'][0][4]);
    $_SESSION['time'][0][5] = Util::htmlentities($_GET['time'][0][5]);
    $_SESSION['time'][0][6] = Util::htmlentities($_GET['time'][0][6]);
    $_SESSION['time'][0][7] = Util::htmlentities($_GET['time'][0][7]);
    $_SESSION['time'][0][8] = Util::htmlentities($_GET['time'][0][8]);
    $_SESSION['time'][0][9] = Util::htmlentities($_GET['time'][0][9]);

    $_SESSION['time'][1][1] = $valid_operator[$_GET['time'][1][1]];

    $_SESSION['time'][1][0] = Util::htmlentities($_GET['time'][1][0]);
    $_SESSION['time'][1][2] = Util::htmlentities($_GET['time'][1][2]);
    $_SESSION['time'][1][3] = Util::htmlentities($_GET['time'][1][3]);
    $_SESSION['time'][1][4] = Util::htmlentities($_GET['time'][1][4]);
    $_SESSION['time'][1][5] = Util::htmlentities($_GET['time'][1][5]);
    $_SESSION['time'][1][6] = Util::htmlentities($_GET['time'][1][6]);
    $_SESSION['time'][1][7] = Util::htmlentities($_GET['time'][1][7]);
    $_SESSION['time'][1][8] = Util::htmlentities($_GET['time'][1][8]);
    $_SESSION['time'][1][9] = Util::htmlentities($_GET['time'][1][9]);

    $_SESSION['time_cnt'] = "1";
    $_SESSION['time_range'] = "week";
} else {
    // Old default => load today values
    /*
    $_GET['time'][0] = array(
        null,
        ">=",
        gmdate("m",$timetz) ,
        gmdate("d",$timetz) ,
        gmdate("Y",$timetz) ,
        null,
        null,
        null,
        null,
        null
    );
    $_GET['time_range'] = "today";
    $_SESSION['time_range'] = "today";
    */
    // default => load day values
    $_GET['time'][0] = array(
        null,
        ">=",
        gmdate("m", strtotime("-1 day UTC",$timetz)) ,
        gmdate("d", strtotime("-1 day UTC",$timetz)) ,
        gmdate("Y", strtotime("-1 day UTC",$timetz)) ,
        gmdate("H",$timetz),
        null,
        null,
        null,
        null
    );
    
    $_GET['time_cnt'] = "1";
    $_GET['time_range'] = "day";

    // Secure assign to session time
    $_SESSION['time'][0][1] = $valid_operator[$_GET['time'][0][1]];

    $_SESSION['time'][0][0] = Util::htmlentities($_GET['time'][0][0]);
    $_SESSION['time'][0][2] = Util::htmlentities($_GET['time'][0][2]);
    $_SESSION['time'][0][3] = Util::htmlentities($_GET['time'][0][3]);
    $_SESSION['time'][0][4] = Util::htmlentities($_GET['time'][0][4]);
    $_SESSION['time'][0][5] = Util::htmlentities($_GET['time'][0][5]);
    $_SESSION['time'][0][6] = Util::htmlentities($_GET['time'][0][6]);
    $_SESSION['time'][0][7] = Util::htmlentities($_GET['time'][0][7]);
    $_SESSION['time'][0][8] = Util::htmlentities($_GET['time'][0][8]);
    $_SESSION['time'][0][9] = Util::htmlentities($_GET['time'][0][9]);

    $_SESSION['time_cnt'] = "1";
    $_SESSION['time_range'] = "day";
}

// NUMEVENTS
$numevents = intval($_GET["numevents"]);
if ($numevents>0) {
	GLOBAL $show_rows;
	$show_rows = $numevents;
}
// PAYLOAD
// IP
// LAYER 4 PROTO
//print_r($_GET);
//print_r($_SESSION['time']);

// IP search by url (host report link)
if (preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",trim($_GET["ip"]),$fnd)) {
    if ($fnd[1]>=0 && $fnd[1]<=255 && $fnd[2]>=0 && $fnd[2]<=255 && $fnd[3]>=0 && $fnd[3]<=255 && $fnd[4]>=0 && $fnd[4]<=255) {
        $_GET["ip_addr"] = array (
    		array ("","ip_both","=",intval($fnd[1]),intval($fnd[2]),intval($fnd[3]),intval($fnd[4]),Util::htmlentities($_GET['ip']))
    	);
    	$_SESSION["ip_addr"] = array (
    		array ("","ip_both","=",intval($fnd[1]),intval($fnd[2]),intval($fnd[3]),intval($fnd[4]),Util::htmlentities($_GET['ip']))
    	);

    	$_GET["ip_addr_cnt"] = 1;
    	$_SESSION["ip_addr_cnt"] = 1;

    	$_GET["ip_field"] = array (
    		array ("","","=")
    	);
    	$_SESSION["ip_field"] = array (
    		array ("","","=")
    	);

    	$_GET["ip_field_cnt"] = 1;
    	$_SESSION["ip_field_cnt"] = 1;
    }
}
//
// DATABASES
//
if ($_GET["server"]!="") {
	if ($_GET["server"]=="local")
	{
	    unset($_SESSION["server"]);
	}
	else
	{
	    $_server = intval($_GET["server"]);    	
	    if ($_server > 0)
	    {
	        // Query DB server
    	    $dbo  = new ossim_db();
    	    $conn = $dbo->connect();
            list($db_server) = Databases::get_list($conn,'WHERE id = '.$_server);
        	$dbo->close();
        	unset($dbo);    	    
        	if (is_object($db_server))
        	{
            	$_SESSION["server"] = array($db_server->get_ip(), $db_server->get_port(), $db_server->get_user(), $db_server->get_pass(), $db_server->get_name());
        	}
	    }
	}
	Util::memcacheFlush(false);
}

if (is_array($_SESSION['server']) && $_SESSION["server"][0] != '')
{
    // Change connect variables
    $alert_host       = $_SESSION['server'][0];
    $alert_port       = $_SESSION['server'][1];
    $alert_user       = $_SESSION['server'][2];
    $alert_password   = $_SESSION['server'][3];
    $alert_ext_dbname = $_SESSION['server'][4];
    $alert_dbname     = (preg_match("/\_restore/", $alert_ext_dbname)) ? $alert_ext_dbname : 'alienvault_siem';

    $db_connect_method = DB_PCONNECT;


    $dbo = new ossim_db();
    error_reporting(E_ERROR | E_PARSE);

    // Try to connect
    try
    {
        $dbo->enable_cache();
        $conn_aux = $dbo->custom_connect((($alert_port == "") ? $alert_host : ($alert_host . ":" . $alert_port)), $alert_user, $alert_password);
    }
    catch(Exception $e)
    {
        unset($_SESSION['server']);

        $w_html  = sprintf(_('Warning! Unable to connect to <strong>%s (%s)</strong>.'), Util::htmlentities($alert_ext_dbname), Util::htmlentities($alert_host));
        //$w_html .= '&nbsp;&nbsp;'._('Connection has been restored to')." <a style='color: #9f6000; font-weight: bold;' href='base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d'>"._('local')."</a>.";
        $w_html .= '<div style="padding: 3px 0px;">'._('In order to connect to the selected database, go to the <i>External Databases</i> section and follow the instructions provided by the help icon.').'</div>';

        $warning = new Av_warning('<div style="padding: 2px;">'.$w_html.'</div>');
        $warning->display();

        exit();
    }

    $dbo->close();
    unset($dbo);
    error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
}

$current_url = Util::get_ossim_url();
$events_report_type = 33;
$graph_report_type = 34;
$criteria_report_type = 35;
$unique_events_report_type = 36;
$unique_iplinks_report_type = 37;
$sensors_report_type = 38;
$unique_addr_report_type = 40;
$src_port_report_type = 42;
$dst_port_report_type = 44;
$unique_plugins_report_type = 46;
$unique_country_events_report_type = 48;
//
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
    "ASSET" => _("Asset &nbsp;<br>S<img src='images/arrow-000-small.gif' border=0 align=absmiddle>D"),
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
$current_cols_widths = array(
    "SIGNATURE" => "45mm",
    "IP_PORTSRC" => "25mm",
    "IP_PORTDST" => "25mm",
    "ASSET" => "12mm",
    "PRIORITY" => "12mm",
    "RELIABILITY" => "12mm",
    "RISK" => "12mm",
    "IP_PROTO" => "10mm",
);
$siem_events_title = _("Security Events");
?>
