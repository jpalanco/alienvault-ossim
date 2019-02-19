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

if ($_SESSION['_siem_sensor_query']=="") {
    echo "-##-##-";
    die();
}

$device_id  = ImportHTTPVar("id", VAR_DIGIT);
$sql        = str_replace("DEVICEID", $device_id, $_SESSION['_siem_sensor_query']);

session_write_close();

$qs = new QueryState();
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$rs = $qs->ExecuteOutputQueryNoCanned($sql, $db);
if ($row = $rs->baseFetchRow()) {
    $unique_addrs = BuildUniqueAlertLink("?sensor=" . urlencode($device_id)) . Util::number_format_locale($row[0],0) . '</A>';
    $src_addrs    = BuildUniqueAddressLink(1, "&amp;sensor=" . urlencode($device_id)) . Util::number_format_locale($row[1],0) . '</A>';
    $dst_addrs    = BuildUniqueAddressLink(2, "&amp;sensor=" . urlencode($device_id)) . Util::number_format_locale($row[2],0) . '</A>';
}
$rs->baseFreeRows();
echo "$unique_addrs##$src_addrs##$dst_addrs";
?>
