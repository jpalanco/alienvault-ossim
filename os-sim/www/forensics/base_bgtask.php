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
include_once ("$BASE_path/includes/base_db.inc.php");
$msg = _("No pending tasks.") . "<br>" . _("All tasks successfully completed.");
if ($_SESSION["deletetask"] != "") {
	$db = NewBASEDBConnection($DBlib_path, $DBtype);
    $db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
    //$temp_sql = "SELECT perc FROM deletetmp WHERE id=".$_SESSION["deletetask"];
    $db->baseExecute("CREATE TABLE IF NOT EXISTS `deletetmp` (`id` int(11) NOT NULL,`perc` int(11) NOT NULL, PRIMARY KEY (`id`))");
    $temp_sql = "SELECT perc,id FROM deletetmp";
    $tmp_result = $db->baseExecute($temp_sql);
    $perc = 0;
    $tasks = false;
    while ($myrow = $tmp_result->baseFetchRow()) {
        $perc = $myrow[0];
        echo _("Delete") . " &nbsp;&nbsp;<b>$perc</b>%<br>\n";
        if ($perc == 100) {
            if (file_exists("/var/tmp/del_" . $myrow[1])) unlink("/var/tmp/del_" . $myrow[1]);
            $db->baseExecute("DELETE FROM deletetmp WHERE id=" . $myrow[1]);
            if ($myrow[1] == $_SESSION["deletetask"]) unset($_SESSION["deletetask"]);
        } elseif (!file_exists("/var/tmp/del_" . $myrow[1])) {
            $db->baseExecute("DELETE FROM deletetmp WHERE id=" . $myrow[1]);
        }
        $tasks = true;
    }
    $tmp_result->baseFreeRows();
    //
    if (!$tasks) {
        $tmptable = "del_".$_SESSION["deletetask"];
        $tmp_result = $db->baseExecute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_siem' AND TABLE_NAME = '$tmptable'");
        if ($myrow = $tmp_result->baseFetchRow()) {
            $tmp_result->baseFreeRows();
            echo _("Processing events.")."<br/>"._("Please be patience,<br/>this may take several minutes...");            
        } else {
            unset($_SESSION["deletetask"]);
        }
    }
} else {
    echo $msg;
}
?>