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
require_once 'classes/Util.inc';
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");

if ($_SESSION['_siem_plugins_query']=="") {
    echo "-##-";
    die();
}

$plugin_id  = ImportHTTPVar("plugin", VAR_DIGIT | VAR_USCORE);
$device_id  = ImportHTTPVar("id", VAR_HEX);
$sql        = str_replace("DID", $device_id, $_SESSION['_siem_plugins_query']);

if (preg_match("/\d+_\d+/",$plugin_id))
{
    $sc  = explode("_",$plugin_id);
    $sql = str_replace("PLUGIN_ID", $sc[0], str_replace("SUBCAT", $sc[1], $sql));
}
else
{
    $sql = str_replace("PLUGIN_ID", $plugin_id, $sql);
}

session_write_close();

$tz = Util::get_timezone();

$qs = new QueryState();
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

$name = $timestamp = '-';
$rs   = $qs->ExecuteOutputQueryNoCanned($sql, $db);
if ($row = $rs->baseFetchRow())
{
    $name      = $row[0];    
    $timestamp = ($tz!=0) ? gmdate("Y-m-d H:i:s",strtotime($row[1]." GMT")+(3600*$tz)) : $row[1];
    if (preg_match("/_acid_event/",$sql))
    {
        $timestamp = str_replace(":00:00","H",$timestamp);
    }
}
$rs->baseFreeRows();

echo "$name##$timestamp";
?>
