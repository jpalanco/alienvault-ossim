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

if ($_SESSION['_siem_port_query']=="") {
    echo "-##-";
    die();
}

$device_id  = ImportHTTPVar("id", VAR_HEX);
$ip_port    = ImportHTTPVar("port", VAR_DIGIT);
$sql        = str_replace("DEVICEID", $device_id, str_replace("IP_PORT", $ip_port, $_SESSION['_siem_port_query']));

session_write_close();

$qs = new QueryState();
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$rs = $qs->ExecuteOutputQueryNoCanned($sql, $db);
if ($row = $rs->baseFetchRow())
{
    $src_addrs = $row[0];
    $dst_addrs = $row[1];
}
$rs->baseFreeRows();
echo "$src_addrs##$dst_addrs";
?>
