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

/**
* Function list:
* - createDBIndex()
* - verify_db()
* - verify_php_build()
* - EventsByAddr()
* - EventCntByAddr()
* - UniqueEventsByAddr()
* - UniqueEventCntByAddr()
* - UniqueEventTotalsByAddr()
* - UniqueSensorCntByAddr()
* - StartTimeForUniqueEventByAddr()
* - StopTimeForUniqueEventByAddr()
*/


require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

function createDBIndex($db, $table, $field, $index_name) {
    $sql = 'CREATE INDEX ' . $index_name . ' ON ' . $table . ' (' . $field . ')';
    $db->baseExecute($sql, -1, -1, false);
    if ($db->baseErrorMessage() != "") ErrorMessage(gettext("Unable to CREATE INDEX for") . " '" . $field . "' : " . $db->baseErrorMessage());
    else ErrorMessage(gettext("Successfully created INDEX for") . " '" . $field . "'");
}
function verify_db($db, $alert_dbname, $alert_host) {
    /*
    $msg = '<B>' . gettext("The underlying database") . ' ' . $alert_dbname . '@' . $alert_host . ' ' . gettext("appears to be incomplete/invalid") . '</B>';
    $sql = "SELECT ip_src FROM iphdr";
    $result = $db->baseExecute($sql, 0, 1, false);
    if ($db->baseErrorMessage() != "") return $msg . '<BR>' . $db->baseErrorMessage() . '
            <P>' . gettext("It might be an older version.  Only alert databases created by Snort 1.7-beta0 or later are supported");
    $base_table = array(
        "acid_ag",
        "acid_ag_alert",
        "acid_ip_cache",
        "acid_event",
        "base_users",
        "base_roles"
    );
    for ($i = 0; $i < count($base_table); $i++) {
        if (!$db->baseTableExists($base_table[$i])) return $msg . '.  <P>' . gettext("The database version is valid, but the BASE DB structure") . ' 
              (table: ' . $base_table[$i] . ')' . gettext("is not present. Use the <A HREF='base_db_setup.php'>Setup page</A> to configure and optimize the DB.");
    }*/
    return "";
}
function verify_php_build($DBtype)
/* Checks whether the necessary libraries is built into PHP */ {
    /* Check PHP version >= 4.0.4 */
    $current_php_version = phpversion();
    $version = explode(".", $current_php_version);
    /* account for x.x.xXX subversions possibly having text like 4.0.4pl1 */
    if (is_numeric(substr($version[2], 1, 1))) $version[2] = substr($version[2], 0, 2);
    else $version[2] = substr($version[2], 0, 1);
    /* only version PHP 4.0.4+ or 4.1+.* are valid */
    if (!(($version[0] >= 4) && ((($version[1] == 0) && ($version[2] >= 4)) || ($version[1] > 0) || ($version[0] > 4)))) {
        return "<FONT COLOR=\"#FF0000\">" . gettext("PHP ERROR") . "</FONT>: " . "<B>" . gettext("Incompatible version") . "</B>: <FONT>" . gettext("Version") . " " . $current_php_version . " " . gettext("of PHP is too old.  Please upgrade to version 4.0.4 or later") . "</FONT>";
    }
    if (($DBtype == "mysql") || ($DBtype == "mysqlt")) {
        if (!(function_exists("mysql_connect"))) {
            return "<FONT COLOR=\"#FF0000\">" . gettext("PHP ERROR") . "</FONT>: " . gettext("<B>PHP build incomplete</B>: <FONT>the prerequisite MySQL support required to read the alert database was not built into PHP. Please recompile PHP with the necessary library (<CODE>--with-mysql</CODE>)</FONT>");
        }
    } else if ($DBtype == "postgres") {
        if (!(function_exists("pg_connect"))) {
            return "<FONT COLOR=\"#FF0000\">" . gettext("PHP ERROR") . "</FONT>: " . gettext("<B>PHP build incomplete</B>: <FONT>the prerequisite PostgreSQL support required to read the alert database was not built into PHP. Please recompile PHP with the necessary library (<CODE>--with-pgsql</CODE>)</FONT>");
        }
    } else if ($DBtype == "mssql") {
        if (!(function_exists("mssql_connect"))) {
            return "<FONT COLOR=\"#FF0000\">" . gettext("PHP ERROR") . "</FONT>: " . gettext("<B>PHP build incomplete</B>: <FONT>the prerequisite MS SQL Server support required to read the alert database was not built into PHP. Please recompile PHP with the necessary library (<CODE>--enable-mssql</CODE>)</FONT>");
        }
    } else if ($DBtype == "oci8") {
        if (!(function_exists("ocilogon"))) {
            return "<FONT COLOR=\"#FF0000\">" . gettext("PHP ERROR") . "</FONT>: " . gettext("<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to read the alert database was not built into PHP. Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>");
        }
    } else {
        $errsqldbtypeinfo1 = gettext("The variable <CODE>\$DBtype</CODE> in <CODE>base_conf.php</CODE> was set to the unrecognized database type of ");
        $errsqldbtypeinfo2 = gettext("Only the following databases are supported: <PRE>
                MySQL         : 'mysql'
                PostgreSQL    : 'postgres'
                MS SQL Server : 'mssql'
                Oracle        : 'oci8'
             </PRE>");
        return "<B>" . gettext("Invalid Database Type Specified") . "</B>: " . $errsqldbtypeinfo1 . "'$DBtype'." . $errsqldbtypeinfo2;
    }
    return "";
}
/* ******************* DB Query Routines ************************************ */
function EventsByAddr($db, $i, $ip) {
    $ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT HEX(id) AS signature FROM acid_event WHERE (ip_src=UNHEX('$ip32')) OR (ip_dst=UNHEX('$ip32'))");
    while ($myrow = $result->baseFetchRow()) $sig[] = $myrow[0];
    $result->baseFreeRows();
    return $sig[$i];
}
function EventCntByAddr($db, $ip) {
    $ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT count(ip_src) FROM acid_event WHERE " . "(ip_src=UNHEX('$ip32')) OR (ip_dst=UNHEX('$ip32'))");
    $myrow = $result->baseFetchRow();
    $event_cnt = $myrow[0];
    $result->baseFreeRows();
    return $event_cnt;
}
function UniqueEventsByAddr($db, $i, $ip) {
    $ip32 = baseIP2long($ip);
    $result = $db->baseExecute("SELECT DISTINCT plugin_id,plugin_sid FROM acid_event WHERE " . "(ip_src='$ip32') OR (ip_dst='$ip32')");
    while ($myrow = $result->baseFetchRow()) $sig[] = array($myrow[0],$myrow[1]);
    $result->baseFreeRows();
    return $sig[$i];
}
function UniqueEventCntByAddr($db, $ip) {
    $sig = array();
	$ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT DISTINCT plugin_id,plugin_sid FROM acid_event WHERE " . "(ip_src=UNHEX('$ip32')) OR (ip_dst=UNHEX('$ip32'))");
    while ($myrow = $result->baseFetchRow()) $sig[] = array($myrow[0],$myrow[1]);
    $result->baseFreeRows();
    return $sig;
}
function UniqueEventTotalsByAddr($db, $ip, $current_event) {
    $ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT count(*) FROM acid_event WHERE " . "( (ip_src=UNHEX('$ip32') OR ip_dst=UNHEX('$ip32')) AND plugin_id='".$current_event[0]."' and plugin_sid='".$current_event[1]."')");
    $myrow = $result->baseFetchRow();
    $tmp = $myrow[0];
    $result->baseFreeRows();
    return $tmp;
}
function UniqueSensorCntByAddr($db, $ip, $current_event) {
    $ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT DISTINCT plugin_sid FROM acid_event WHERE " . "( (ip_src=UNHEX('$ip32') OR ip_dst=UNHEX('$ip32')) AND plugin_id='".$current_event[0]."' and plugin_sid='".$current_event[1]."')");
    while ($myrow = $result->baseFetchRow()) $sid[] = $myrow[0];
    $count = count($sid);
    $result->baseFreeRows();
    return $count;
}
function StartTimeForUniqueEventByAddr($db, $ip, $current_event) {
    $ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT min(timestamp) FROM acid_event WHERE " . "((ip_src=UNHEX('$ip32') OR ip_dst=UNHEX('$ip32')) AND plugin_id='".$current_event[0]."' and plugin_sid='".$current_event[1]."');");
    $myrow = $result->baseFetchRow();
    $start_time = $myrow[0];
    $result->baseFreeRows();
    return $start_time;
}
function StopTimeForUniqueEventByAddr($db, $ip, $current_event) {
    $ip32 = bin2hex(inet_pton(trim($ip)));
    $result = $db->baseExecute("SELECT max(timestamp) FROM acid_event WHERE " . "((ip_src=UNHEX('$ip32') OR ip_dst=UNHEX('$ip32')) AND plugin_id='".$current_event[0]."' and plugin_sid='".$current_event[1]."');");
    $myrow = $result->baseFetchRow();
    $stop_time = $myrow[0];
    $result->baseFreeRows();
    return $stop_time;
}
?>
