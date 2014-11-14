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


/**
* Function list:
* - IsValidAction()
* - IsValidActionOp()
* - ActOnSelectedAlerts()
* - GetActionDesc()
* - ProcessSelectedAlerts()
* - Action_ag_by_id_Pre()
* - Action_ag_by_id_Op()
* - Action_ag_by_id_Post()
* - Action_ag_by_name_Pre()
* - Action_ag_by_name_Op()
* - Action_ag_by_name_Post()
* - Action_add_new_ag_pre()
* - Action_add_new_ag_Op()
* - Action_add_new_ag_Post()
* - Action_del_alert_pre()
* - Action_del_alert_op()
* - Action_del_alert_post()
* - Action_email_alert_pre()
* - Action_email_alert_op()
* - Action_email_alert_post()
* - Action_email_alert2_pre()
* - Action_email_alert2_op()
* - Action_email_alert2_post()
* - Action_csv_alert_pre()
* - Action_csv_alert_op()
* - Action_csv_alert_post()
* - Action_clear_alert_pre()
* - Action_clear_alert_op()
* - Action_clear_alert_post()
* - Action_archive_alert_pre()
* - Action_archive_alert_op()
* - Action_archive_alert_post()
* - Action_archive_alert2_pre()
* - Action_archive_alert2_op()
* - Action_archive_alert2_post()
* - PurgeAlert()
* - PurgeAlert_ac()
* - send_email()
*/

defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/base_ag_common.php");
include_once ("$BASE_path/includes/base_constants.inc.php");
function IsValidAction($action, $valid_actions) {
    return in_array($action, $valid_actions);
}
function IsValidActionOp($action_op, $valid_action_op) {
    return is_array($valid_action_op) && in_array($action_op, $valid_action_op);
}
/*
= action: action to perform (e.g. ag_by_id, ag_by_name, clear_alerts, delete_alerts, email_alerts)
= valid_action: array of valid actions ($action must be in valid_action)
= action_op: select operation to perform with $action (e.g. gettext("Delete Selected"), gettext("Delete ALL on Screen"), gettext("Delete Entire Query"))
$action_op needs to be passed by reference, because its value will need to get
changed in order for alerts to be re-displayed after the operation.
= valid_action_op: array of valid action operations ($action_op must be in $valid_action_op)
= $action_arg: argument for the action
= $context: what page is the $action being performed in?
- 1: from query results page
- 2: from signature/alert page
- 3: from sensor page
- 4: from AG maintenance page
- 5: base_qry_alert.php	PAGE_ALERT_DISPLAY
- 6: base_stat_iplink.php	PAGE_STAT_IPLINK
- 7: base_stat_class.php	PAGE_STAT_CLASS
- 8: base_stat_uaddr.php	PAGE_STAT_UADDR
- 9: base_stat_ports.php	PAGE_STAT_PORTS

= $action_chk_lst: (used only when gettext("Delete Selected") is the $action_op)
a sparse array where each element contains a key to alerts which should be acted
on.  Some elements will be blank based on the checkbox state.
Depending on the setting of $context, these keys may be either
sid/cid pairs ($context=1), signature IDs ($context=2), or sensor IDs ($context=3)

= $action_lst: (used only when gettext("Delete ALL on Screen") is the $action_op)
an array denoting all elements on the screen, where each element contains a key to
alerts which should be acted on. Depending on the setting of $context, these keys
may be either sid/cid pairs ($context=1), signature IDs ($context=2), or sensor
IDs ($context=3)
= $num_alert_on_screen: count of alerts on screen (used to parse through $alert_chk_lst for
_SELECTED and gettext("Delete ALL on Screen") $action_op).
= $num_alert_in_query: count of alerts in entire query. Passed by reference since delete operations
will decrement its value
= $action_sql: (used only when gettext("Delete Entire Query") is the $action_op)
SQL used to extract all the alerts to operate on
= $page_caller: $caller variable from page
= $db: handle to the database
= $action_param: extra data passed about an alert in addition to what is
entered by users in $action_arg
*/
function ActOnSelectedAlerts($action, $valid_action, &$action_op, $valid_action_op, $action_arg, $context, $action_chk_lst, $action_lst, $num_alert_on_screen, &$num_alert_in_query, $action_sql, $page_caller, $db, $action_param = "") {
    GLOBAL $current_view, $last_num_alerts, $freq_num_alerts, $caller, $ag_action, $debug_mode, $max_script_runtime;
    /* Verify that an action was actually selected */
    if (!IsValidActionOp($action_op, $valid_action_op)) return;
    /* Verify that action was selected when action operation is clicked */
    if (IsValidActionOp($action_op, $valid_action_op) && $action == " ") {
        ErrorMessage(gettext("No action was specified on the events"));
        return;
    }
    /* Verify that validity of action   */
    if (!(IsValidAction($action, $valid_action) && IsValidActionOp($action_op, $valid_action_op))) {
        ErrorMessage("'" . $action . "'" . gettext(" is an invalid action"));
        return;
    }
    /* Verify that those actions that need an argument have it
    *
    * Verify #1: Adding to an AG needs an argument
    */
    if (($action_arg == "") && (($action == "ag_by_id") || ($action == "ag_by_name"))) {
        ErrorMessage(gettext("Could not add events since no AG was specified"));
        return;
    }
    /* Verify #2: Emailing alerts needs an argument */
    if (($action_arg == "") && (($action == "email_alert") || ($action == "email_alert2") || ($action_arg == "csv_alert"))) {
        ErrorMessage(gettext("Could not email events since no email address was specified"));
        return;
    }
    //if ($debug_mode > 0) echo "==== " . gettext("ACTION") . " ======<BR>" . gettext("context") . " = $context<BR><BR>";
    if (ini_get("safe_mode") != true) set_time_limit($max_script_runtime);
    if ($action_op == gettext("Delete Selected")) {
        /* on packet lookup, only examine the first packet */
        if ($context == PAGE_ALERT_DISPLAY) {
            $tmp = 1;
            ProcessSelectedAlerts($action, $action_op, $action_arg, $action_param, $context, $action_chk_lst, $tmp, $action_sql, $db);
            $num_alert_in_query = $tmp;
        } else {
            ProcessSelectedAlerts($action, $action_op, $action_arg, $action_param, $context, $action_chk_lst, $num_alert_in_query, $action_sql, $db);
        }
    } else if ($action_op == gettext("Delete ALL on Screen")) {
        ProcessSelectedAlerts($action, $action_op, $action_arg, $action_param, $context, $action_lst, $num_alert_in_query, $action_sql, $db);
    } else if ($action_op == gettext("Delete Entire Query")) {
        if (($context == PAGE_QRY_ALERTS)) /* on alert listing page */ {
            if ($page_caller == "last_tcp" || $page_caller == "last_udp" || $page_caller == "last_icmp" || $page_caller == "last_any") {
                $limit_start = 0;
                $limit_offset = $last_num_alerts;
                $tmp_num = $last_num_alerts;
            } else {
                $tmp_num = $num_alert_in_query;
                $limit_start = $limit_offset = - 1;
            }
        } else if ($context == PAGE_ALERT_DISPLAY) {
            $tmp_num = 1;
            $limit_start = $limit_offset = - 1;
        } else if ($context == PAGE_STAT_ALERTS) /* on unique alerts page */ {
            if ($page_caller == "most_frequent" || $page_caller == "last_alerts") {
                $limit_start = 0;
                if ($page_caller == "last_alerts") $limit_offset = $tmp_num = $last_num_ualerts;
                if ($page_caller == "most_frequent") $limit_offset = $tmp_num = $freq_num_alerts;
            } else {
                $tmp_num = $num_alert_in_query;
                $limit_start = $limit_offset = - 1;
            }
        } else if ($context == PAGE_STAT_SENSOR) /* on unique sensor page */ {
            $tmp_num = $num_alert_in_query;
            $limit_start = $limit_offset = - 1;
        } else if ($context == PAGE_QRY_AG) /* on the AG page */ {
            $tmp_num = $num_alert_in_query;
            $limit_start = $limit_offset = - 1;
        }
        ProcessSelectedAlerts($action, $action_op, $action_arg, $action_param, $context, $action_lst, $tmp_num, /*&$num_alert_in_query*/
        $action_sql, $db, $limit_start, $limit_offset);
        $num_alert_in_query = $tmp_num;
    }
    /* In unique alert or unique sensor:
    * Reset the "$submit" to be a view # to mimic a browsing operation
    * so the alerts are re-displayed after the operation completes
    */
    if ($context == PAGE_STAT_ALERTS || $context == PAGE_STAT_SENSOR) $action_op = $current_view;
    /* In Query results, alert lookup, or AG maintenance:
    * Reset the "$submit" to be a view # to mimic a browsing operation
    * However if in alert lookup, set "$submit" to be the $caller (i.e. sid, cid)
    */
    if (($context == PAGE_QRY_ALERTS) || ($context == PAGE_QRY_AG)) {
        /* Reset $submit to a browsing view # */
        if ((strstr($page_caller, "#") == "") && ($action_op != gettext("Query DB"))) {
            $action_op = $current_view;
        }
        /* but if in Alert Lookup, set $submit to (sid,cid) */
        else {
            $action_op = $page_caller;
        }
    }
    /* If action from AG maintenance, set operation to 'view' after
    * running the specified action;
    */
    if ($context == PAGE_QRY_AG) {
        $ag_action = "view";
    }
}
function GetActionDesc($action_name) {
    $action_desc["ag_by_id"] = gettext("ADD to AG (by ID)");
    $action_desc["ag_by_name"] = gettext("ADD to AG (by Name)");
    $action_desc["add_new_ag"] = gettext("Create AG (by Name)");
    $action_desc["clear_alert"] = gettext("Clear from AG");
    $action_desc["del_alert"] = gettext("Delete event(s)");
    $action_desc["email_alert"] = gettext("Email event(s) (full)");
    $action_desc["email_alert2"] = gettext("Email event(s) (summary)");
    $action_desc["csv_alert"] = gettext("Email event(s) (csv)");
    $action_desc["archive_alert"] = gettext("Archive event(s) (copy)");
    $action_desc["archive_alert2"] = gettext("Archive event(s) (move)");
    return $action_desc[$action_name];
}
function ProcessSelectedAlerts($action, &$action_op, $action_arg, $action_param, $context, $action_lst, &$num_alert, $action_sql, $db, $limit_start = - 1, $limit_offset = - 1) {
	GLOBAL $debug_mode;
    $action_cnt = 0;
    $dup_cnt = 0;
    $action_desc = "";
    if ($action == "ag_by_id") $action_desc = gettext("ADD to AG (by ID)");
    else if ($action == "ag_by_name") $action_desc = gettext("ADD to AG (by Name)");
    else if ($action == "del_alert") $action_desc = gettext("Delete event(s)");
    else if ($action == "email_alert") $action_desc = gettext("Email event(s) (full)");
    else if ($action == "email_alert2") $action_desc = gettext("Email event(s) (summary)");
    else if ($action == "csv_alert") $action_desc = gettext("Email event(s) (csv)");
    else if ($action == "clear_alert") $action_desc = gettext("Clear from AG");
    else if ($action == "archive_alert") $action_desc = gettext("Archive event(s) (copy)");
    else if ($action == "archive_alert2") $action_desc = gettext("Archive event(s) (move)");
    else if ($action == "add_new_ag") $action_desc = gettext("ADD-New-AG");
    if ($action == "") return;
    // if ($debug_mode > 0) {
        // echo "<BR>==== $action_desc Alerts ========<BR>
           // num_alert = $num_alert<BR>
           // action_sql = $action_sql<BR>
           // action_op = $action_op<BR>
           // action_arg = $action_arg<BR>
           // action_param = $action_param<BR>
           // context = $context<BR>
           // limit_start = $limit_start<BR>
           // limit_offset = $limit_offset<BR>";
    // }
    /* Depending from which page/listing the action was spawned,
    * the entities selected may not necessarily be specific
    * alerts.  For example, sensors or alert names may be
    * selected.  Thus, each one of these entities referred to as
    * alert_blobs, the specific alerts associated with them must
    * be explicitly extracted.  This blob structures SQL must be
    * used to extract the list, where the passed selected keyed
    * will be the criteria in this SQL.
    *
    * Note: When acting on any page where gettext("Delete Entire Query") is
    * selected this is also a blob.
    */
    /* if only manipulating specific alerts --
    * (in the Query results or AG contents list)
    */
    if (($context == PAGE_QRY_ALERTS) || ($context == PAGE_QRY_AG) || ($context == PAGE_ALERT_DISPLAY)) {
        $num_alert_blobs = 1;
        if ($action_op == gettext("Delete Entire Query")) $using_blobs = true;
        else $using_blobs = false;
    }
    /* else manipulating by alert blobs -- e.g. signature, sensor */
    else {
        $num_alert_blobs = $num_alert;
        $using_blobs = true;
    }
    $blob_alert_cnt = $num_alert;
    if ($debug_mode > 0) echo "using_blobs = $using_blobs<BR>";
    /* ******* SOME PRE ACTION ********* */
    $function_pre = "Action_" . $action . "_Pre";
    $action_ctx = $function_pre($action_arg, $action_param, $db);
    //if ($debug_mode > 0) echo "<BR>Gathering elements from " . sizeof($action_lst) . " alert blobs<BR>";
    /* Loop through all the alert blobs */
    $deltmp = "";
    if ($action == "del_alert") {
        $count = count($action_lst);
        $aux_count = ($count > 0) ? $count : 1;
        $aux_cnt = ($blob_alert_cnt > 0) ? $blob_alert_cnt : 1;
        $interval = ($action_op == "Selected") ? 100 / $aux_count : 100 / $aux_cnt;
        $rnd = time();
        $deltmp = "/var/tmp/delsql_$rnd";
        $f = fopen($deltmp, "w+");
        //fputs($f, "/* count=$count interval=$interval blob_alert_cnt=$blob_alert_cnt num_alert_blobs=$num_alert_blobs num_alert=$num_alert */\n");
        if($_SESSION["server"][4]!="") {
            fputs($f, "USE ".$_SESSION["server"][4].";\n");
        }
        fputs($f, "CREATE TABLE IF NOT EXISTS `deletetmp` (`id` int(11) NOT NULL,`perc` int(11) NOT NULL, PRIMARY KEY (`id`));\n");
        fputs($f, "INSERT INTO deletetmp (id,perc) VALUES ($rnd,1) ON DUPLICATE KEY UPDATE perc=1;\n");
    }
    for ($j = 0; $j < $num_alert_blobs; $j++) {
        /* If acting on a blob construct, or on the_ENTIREQUERY
        * of a non-blob structure (which is equivalent to 1-blob)
        * run a query to get the results.
        *
        * For each unique blob construct two SQL statement are
        * generated: one to retrieve the alerts ($sql), and another
        * to count the number of actual alerts in this blob
        */
        if (($using_blobs)) {
            $sql = $action_sql;
            /* Unique Signature listing */
            if ($context == PAGE_STAT_ALERTS) {
                if (!isset($action_lst[$j])) $tmp = array(0,0);
                else $tmp =  preg_split("/[\s;]+/",$action_lst[$j]);
                $sql = "SELECT hex(acid_event.id) as id " . $action_sql . " AND acid_event.plugin_id='" . $tmp[0] . "' AND acid_event.plugin_sid='" . $tmp[1] . "'";
                $sql2 = "SELECT count(acid_event.id) " . $action_sql . " AND acid_event.plugin_id='" . $tmp[0] . "' AND acid_event.plugin_sid='" . $tmp[1] . "'";
            }
            /* Unique Sensor listing */
            else if ($context == PAGE_STAT_SENSOR) {
                if (!isset($action_lst[$j])) $tmp = - 1;
                else $tmp = $action_lst[$j];
                $sql = "SELECT hex(acid_event.id) as id FROM acid_event WHERE device_id='$tmp'";
                $sql2 = "SELECT count(acid_event.id) FROM acid_event WHERE device_id='$tmp'";
            }
            /* Unique Classification listing DEPRECATED NO USE */
            else if ($context == PAGE_STAT_CLASS) {
                $sql = $sql2 = "";
            }
            /* Unique IP links listing */
            else if ($context == PAGE_STAT_IPLINK) {
                $sql = $sql2 = "";
            }
            /* Unique IP addrs listing */
            else if ($context == PAGE_STAT_UADDR) {
                if (!isset($action_lst[$j])) {
                    $tmp = " AND ip_src=NULL AND ip_dst=NULL";
                } else {
                    $aux = explode("_",$action_lst[$j]);
                    $tmp = "";
                    if (preg_match("/\d+\.\d+\.\d+\.\d+/",$aux[0])) $tmp .= " AND ip_src=unhex('".bin2hex(@inet_pton($aux[0]))."')";
                    if (preg_match("/\d+\.\d+\.\d+\.\d+/",$aux[1])) $tmp .= " AND ip_dst=unhex('".bin2hex(@inet_pton($aux[1]))."')";
                    if (preg_match("/[0-9a-fA-F]+/",$aux[2]))       $tmp .= " AND ctx=unhex('".$aux[2]."')";
                }
                $sql = "SELECT hex(acid_event.id) as id " . $action_sql . $tmp;
                $sql2 = "SELECT count(acid_event.id) " . $action_sql . $tmp;
            }
            /* Ports listing */
            else if ($context == PAGE_STAT_PORTS) {
                if (!isset($action_lst[$j])) {
                    $tmp = "ip_proto='-1'";
                } else {
                    $tmp = $action_lst[$j];
                    $tmp_proto = strtok($tmp, "_");
                    $tmp_porttype = strtok("_");
                    $tmp_ip = strtok("_");
                    $ctx = strtok("_");
                    if ($tmp_proto == TCP) $tmp = "ip_proto='" . TCP . "'";
                    else if ($tmp_proto == UDP) $tmp = "ip_proto='" . UDP . "'";
                    else $tmp = "ip_proto IN (" . TCP . ", " . UDP . ")";
                    ($tmp_porttype == SOURCE_PORT) ? ($tmp.= " AND layer4_sport='" . $tmp_ip . "'") : ($tmp.= " AND layer4_dport='" . $tmp_ip . "'");
                    $tmp .= " AND ctx=unhex('$ctx')";
                }
                $sql = "SELECT hex(acid_event.id) as id FROM acid_event WHERE " . $tmp;
                $sql2 = "SELECT count(acid_event.id) FROM acid_event WHERE " . $tmp;
            }
            /* if acting on alerts by signature or sensor, count the
            * the number of alerts
            */
            if (($context == PAGE_STAT_ALERTS) || ($context == PAGE_STAT_SENSOR) || ($context == PAGE_STAT_CLASS) || ($context == PAGE_STAT_IPLINK) || ($context == PAGE_STAT_UADDR) || ($context == PAGE_STAT_PORTS)) {
                $result2 = $db->baseExecute($sql2);
                $myrow2 = $result2->baseFetchRow();
                $blob_alert_cnt = $myrow2[0];
                $result2->baseFreeRows();
            }
            //if ($debug_mode > 0) echo "$j = [using SQL $num_alert for blob " . (isset($action_lst[$j]) ? $action_lst[$j] : "") . "]: $sql<BR>";
            /* Execute the SQL to get the alert listing */
            if ($action != "del_alert" || $blob_alert_cnt <= 10000) { // only if not is a raw delete
                if ($limit_start == - 1)
                    $result = $db->baseExecute($sql, -1, -1, false);
                else
                    $result = $db->baseExecute($sql, $limit_start, $limit_offset, false);
                if ($db->baseErrorMessage() != "") {
                    ErrorMessage("Error retrieving alert list to $action_desc ".$db->baseErrorMessage());
                    return -1;
                }
            }
        }
        /* Limit the number of alerts acted on if in "top x alerts" */
        if ($limit_start != - 1) $blob_alert_cnt = $limit_offset;
        $interval2 = ($blob_alert_cnt>0) ?  100 / $blob_alert_cnt : 100;
        
        /* Call background purge if num of alerts is too high */
        if ($action == "del_alert" && $blob_alert_cnt > 10000) {
        	fclose($f);
        	unlink($deltmp);
        	$total_aux = 0;
        	
        	// Create table with uuids to delete
        	$db->baseExecute("CREATE TABLE IF NOT EXISTS del_$rnd ( id binary(16) NOT NULL, PRIMARY KEY ( id ) )");
        	if ($using_blobs) {
        	    $total_aux = $blob_alert_cnt;
        		/*for ($i = 0; $i < $blob_alert_cnt; $i++) {
                    $myrow = $result->baseFetchRow();
                    $id = $myrow[0];
                    if ($id != "") {	
                    	$db->baseExecute("INSERT IGNORE INTO del_$rnd VALUES (UNHEX('$id'))");
        				$total_aux++;
                    }
        		}*/
        	} elseif (is_array($action_lst)) {
	        	foreach ($action_lst as $action_lst_element) {
	        		GetNewResultID($action_lst_element, $seq, $id);
	        		$db->baseExecute("INSERT IGNORE INTO del_$rnd VALUES (UNHEX('$id'))");
	        		$total_aux++;
	        	}
        	}
        	if ($total_aux < 1) $total_aux = 1;
        	$block                  = 5000;
        	$_SESSION["deletetask"] = $rnd;
            $deltmp                 = "del_$rnd";
            $db_name                = ($_SESSION["server"][4]!="") ? $_SESSION["server"][4] : "alienvault_siem";

            $f = fopen("/var/tmp/$deltmp", "w+");
            
            fputs($f, "/* ****************Background Purge Execution*************** */\n");
            fputs($f, "USE ".$db_name.";\n");
            if ($using_blobs) {
                fputs($f, "CREATE TABLE IF NOT EXISTS del_$rnd ( id binary(16) NOT NULL, PRIMARY KEY ( id ) );\n");
                fputs($f, "INSERT IGNORE INTO del_$rnd ".str_replace("hex(acid_event.id) as id","acid_event.id",$sql).";\n");
            }
            fputs($f, "CREATE TABLE IF NOT EXISTS `deletetmp` (`id` int(11) NOT NULL,`perc` int(11) NOT NULL, PRIMARY KEY (`id`));\n");
            fputs($f, "INSERT INTO deletetmp (id,perc) VALUES ($rnd,1) ON DUPLICATE KEY UPDATE perc=1;\n");
            fputs($f, "SET AUTOCOMMIT=0;\n");
            
            for ($j=0;$j<$total_aux;$j+=$block) {
            
                fputs($f, "DELETE FROM acid_event WHERE id in (select id from $deltmp) limit $block;\n");
                fputs($f, "DELETE FROM idm_data WHERE event_id in (select id from $deltmp) limit $block;\n");
                fputs($f, "DELETE FROM reputation_data WHERE event_id in (select id from $deltmp) limit $block;\n");
                fputs($f, "DELETE FROM extra_data WHERE event_id in (select id from $deltmp) limit $block;\n");
                fputs($f, "DELETE FROM $deltmp limit $block;\n");
                fputs($f, "COMMIT;\n");
                $perc = round((($j+$block)*100)/$total_aux,0); if ($perc>99) $perc=99;
                fputs($f, "UPDATE deletetmp SET perc=$perc WHERE id=$rnd;\n");
            	
            }
            
            fputs($f, "DELETE FROM acid_event WHERE id in (select id from $deltmp) limit $block;\n");
            fputs($f, "DELETE FROM idm_data WHERE event_id in (select id from $deltmp) limit $block;\n");
            fputs($f, "DELETE FROM reputation_data WHERE event_id in (select id from $deltmp) limit $block;\n");
            fputs($f, "DELETE FROM extra_data WHERE event_id in (select id from $deltmp) limit $block;\n");
            fputs($f, "DELETE FROM $deltmp limit $block;\n");
            fputs($f, "UPDATE deletetmp SET perc=100 WHERE id=$rnd;\nCOMMIT;\n");
            fputs($f, "DROP TABLE $deltmp;\n");
            fputs($f, "COMMIT;\n");
            
            fclose($f);
                        
            // POST ACTION AND FLUSH MEMCACHE

            shell_exec("/usr/share/ossim/scripts/forensics/bg_purge_from_siem.sh $deltmp > /var/tmp/latest_siem_events_purge.log 2>&1 &");
        	echo "<script>bgtask();</script>\n";
        	return;
        }
        
        echo "<script>bgtask();</script>\n";
        
        /* Loop through the specific alerts in a particular blob */
        for ($i = 0; $i < $blob_alert_cnt; $i++) {
            /* Verify that have a selected alert */
            if (isset($action_lst[$i]) || $using_blobs) {
                /* If acting on a blob */
                if ($using_blobs) {
                    $myrow = $result->baseFetchRow();
                    $id = $myrow[0];
                } else GetNewResultID($action_lst[$i], $seq, $id);
                if ($id != "") {
                    //if ($debug_mode > 0) echo $id . '<BR>';
                    /* **** SOME ACTION on (sid, cid) ********** */
                    $function_op = "Action_" . $action . "_op";
                    $action_ctx = & $action_ctx;
                    if ($action == "del_alert") $tmp = $function_op($id, $db, $deltmp, $action_cnt, ($interval2 < $interval) ? $interval2 : $interval, $f);
                    else $tmp = $function_op($sid, $cid, $db, $action_arg, $action_ctx);
                    if ($tmp == 0) {
                        ++$dup_cnt;
                    } else if ($tmp == 1) {
                        ++$action_cnt;
                    }
                }
            }
        }
        /* If acting on a blob, free the result set used to get alert list */
        if ($using_blobs) $result->baseFreeRows();
    }
    if ($action == "del_alert") {
        fputs($f, "UPDATE deletetmp SET perc=100 WHERE id=$rnd;\nCOMMIT;\n");
        fclose($f);
    }
    /* **** SOME POST-ACTION ******* */
    $function_post = "Action_" . $action . "_post";
    if ($action == "del_alert")
       $function_post($action_arg, $action_ctx, $db, $num_alert, $action_cnt, $context, $deltmp);
    else
       $function_post($action_arg, $action_ctx, $db, $num_alert, $action_cnt);
    if ($dup_cnt > 0) ErrorMessage(gettext("Ignored ") . $dup_cnt . gettext(" duplicate event(s)"));
    if ($action_cnt > 0) {
        /*
        *  Print different message if alert action units (e.g. sensor
        *  or signature) are not individual alerts
        */
        //if (($context == PAGE_STAT_ALERTS) || ($context == PAGE_STAT_SENSOR) || ($context == PAGE_STAT_CLASS) || ($context == PAGE_STAT_IPLINK) || ($context == PAGE_STAT_UADDR) || ($context == PAGE_STAT_PORTS)) {
        //    if ($action == "del_alert") ErrorMessage(_("Deleting") . " " . $action_cnt . gettext(" event(s)"));
        //    else ErrorMessage(gettext("Successful") . " $action_desc - " . gettext("on") . " $action_cnt " . gettext(" event(s)") . " (" . gettext("in") . " $num_alert_blobs blobs)");
        //} else {
        //    if ($action == "del_alert") ErrorMessage(_("Deleting") . " " . $action_cnt . gettext(" event(s)"));
        //    else ErrorMessage(gettext("Successful") . " $action_desc - " . $action_cnt . gettext(" event(s)"));
        //}
    } else if ($action_cnt == 0) ErrorMessage(gettext("No events were selected or the") . " $action_desc " . gettext("was not successful"));
    //error_log("cnt:$action_cnt,dup:$dup_cnt,desc:$action_desc,file:$deltmp\n",3,"/var/tmp/dellog");
    $db->baseCacheFlush();
    // if ($debug_mode > 0) {
        // echo "-------------------------------------<BR>
          // action_cnt = $action_cnt<BR>
          // dup_cnt = $dup_cnt<BR>
          // num_alert = $num_alert<BR> 
          // ==== $action_desc Alerts END ========<BR>";
    // }
}
/*
*
*  function Action_*_Pre($action, $action_arg)
*
*  RETURNS: action context
*/
/*
*  function Action_*_Op($sid, $cid, &$db, $action_arg, &$action_ctx)
*
*  RETURNS: 1: successful act on an alert
*           0: ignored (duplicate) or error
*/
/*
* function Action_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt)
*
*/
/* ADD to AG (by ID) ****************************************/
function Action_ag_by_id_Pre($action_arg, $action_param, $db)
/*
* $action_arg: a AG ID
*/ {
    if (VerifyAGID($action_arg, $db) == 0) ErrorMessage(gettext("Unknown AG ID specified (AG probably does not exist)"));
    return null;
}
function Action_ag_by_id_Op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_ag_by_id_Post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    /* none */
}
/* ADD to AG (by Name ) *************************************/
function Action_ag_by_name_Pre($action_arg, $action_param, $db) {
    return GetAGIDbyName($action_arg, $db);
}
function Action_ag_by_name_Op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_ag_by_name_Post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    /* none */
}
/* ADD NEW AG (by Name) *************************************/
function Action_add_new_ag_pre($action_arg, $action_param, $db) {
    return 0;
}
function Action_add_new_ag_Op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_add_new_ag_Post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
}
/* DELETE **************************************************/
function Action_del_alert_pre($action_arg, $action_param, $db) {
    GLOBAL $num_alert_blobs;
    return $num_alert_blobs;
}
function Action_del_alert_op($id, $db, $deltmp, $j, $interval, $f) {
    return PurgeAlert($id, $db, $deltmp, $j, $interval, $f);
}
function Action_del_alert_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt, $context, $deltmp) {
    $sel_cnt = 0;
    $action_lst_cnt = count(ImportHTTPVar("action_lst"));
    $action_chk_lst = ImportHTTPVar("action_chk_lst");
    /* count the number of check boxes selected  */
    for ($i = 0; $i < $action_lst_cnt; $i++) {
        if (isset($action_chk_lst[$i])) $sel_cnt++;
    }
    if ($sel_cnt > 0) /* 1 or more check boxes selected ? */
    $num_alert-= $sel_cnt;
    /* No, must have been a Delete ALL on Screen or Delete Entire Query  */
    elseif ($context == 1) /* detail alert list ? */
    $num_alert-= $action_cnt;
    else $num_alert-= count(ImportHTTPVar("action_chk_lst"));
    if ($deltmp != "") {
        // launch delete in background
        $rnd = explode("_", $deltmp);
        $_SESSION["deletetask"] = $rnd[1];
        //error_log("launch $deltmp\n",3,"/var/tmp/dellog");
        shell_exec("nohup cat $deltmp | /usr/bin/ossim-db alienvault_siem > /var/tmp/latest_siem_events_purge.log 2>&1 &");
        echo "<script>bgtask();</script>\n";
    }
}
/* Email ***************************************************/
function Action_email_alert_pre($action_arg, $action_param, $db) {
    return "";
}
function Action_email_alert_op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_email_alert_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    return 0;
}
/* Email ***************************************************/
function Action_email_alert2_pre($action_arg, $action_param, $db) {
    return "";
}
function Action_email_alert2_op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_email_alert2_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    return 0;
}
/* CSV    ***************************************************/
function Action_csv_alert_pre($action_arg, $action_param, $db) {
    return "";
}
function Action_csv_alert_op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_csv_alert_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    return 0;
}
/* Clear ***************************************************/
function Action_clear_alert_pre($action_arg, $action_param, $db) {
    return $action_param;
}
function Action_clear_alert_op($id, $db, $action_arg, &$ctx) {
    return 0;
}
function Action_clear_alert_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    $num_alert-= $action_cnt;
}
/* Archive ***************************************************/
function Action_archive_alert_pre($action_arg, $action_param, $db) {
    GLOBAL $DBlib_path, $DBtype, $archive_dbname, $archive_host, $archive_port, $archive_user;
    $db2 = NewBASEDBConnection($DBlib_path, $DBtype);
    $db2->baseConnect($archive_dbname, $archive_host, $archive_port, $archive_user, "");
    return $db2;
}
function Action_archive_alert_op($id, &$db, $action_arg, &$ctx) {
    return 0;
}
function Action_archive_alert_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    /* BEGIN LOCAL FIX */
    /* Call UpdateAlertCache to properly set cid values and make sure caches are current */
    $archive_db = & $action_ctx;
    UpdateAlertCache($archive_db);
    UpdateAlertCache($db);
    /* END LOCAL FIX */
}
function Action_archive_alert2_pre($action_arg, $action_param, $db) {
    return Action_archive_alert_pre($action_arg, $action_param, $db);
}
function Action_archive_alert2_op($id, &$db, $action_arg, &$ctx) {
    return 0;
}
function Action_archive_alert2_post($action_arg, &$action_ctx, $db, &$num_alert, $action_cnt) {
    return 0;
}
/* This function accepts a (sid,cid) and purges it
* from the database
*
* - (sid,cid) : sensor, event id pair to delete
* - db        : database handle
*
* RETURNS: 0 or 1 depending on whether the alert was deleted
*/
function PurgeAlert($id, $db, $deltmp, $j, $interval, $f) {
    $del_table_list = array(
        "idm_data",
        "reputation_data",
        "extra_data",
        "acid_event"
    );
    $del_cnt = 0;
    $del_str = "";
    fputs($f, "SET AUTOCOMMIT=0;\n");
    for ($k = 0; $k < count($del_table_list); $k++) {
        /* If trying to add to an BASE table append ag_ to the fields */
        if (strstr($del_table_list[$k], "acid_event") == "")
            $sql2 = "DELETE FROM " . $del_table_list[$k] . " WHERE event_id=unhex('$id')";
        else
            $sql2 = "DELETE FROM " . $del_table_list[$k] . " WHERE id=unhex('$id')";
        //$db->baseExecute($sql2);
        if ($id != "") fputs($f, "$sql2;\n");
        //if ($db->baseErrorMessage() != "") ErrorMessage(gettext("Error Deleting Event") . " " . $del_table_list[$k]);
        //else 
        if ($k == 0) $del_cnt = 1;
    }
    //fputs($f, PurgeAlert_ac($id, $db)); => Now we use a delete trigger
    fputs($f, "COMMIT;\n");
    $perc = round($j * $interval, 0); if ($perc>100) $perc=99;
    $rnd = explode("_", $deltmp);
    fputs($f, "UPDATE deletetmp SET perc=$perc WHERE id=" . $rnd[1] . ";\n");
    //
    return $del_cnt;
}
/* This function accepts a TO, SUBJECT, BODY, and MIME information and
* sends the appropriate message
*
* RETURNS: boolean on success of sending message
*
*/
function send_email($to, $subject, $body, $mime) {
    if ($to != "") {
        return mail($to, $subject, $body, $mime);
    } else {
        ErrorMessage(gettext("MAIL ERROR: No recipient Specified"));
        return false;
    }
}
?>
