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
* - PrintCleanURL()
* - PrintBinDownload()
* - PrintPcapDownload()
* - PrintPacketLookupBrowseButtons2()
* - showShellcodeAnalysisLink()
* - PrintPacketLookupBrowseButtons()
*/

require ("base_conf.php");
require ("vars_session.php");
$_SESSION['norefresh'] = 1;
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");
require_once ('classes/Util.inc');

// set cookie for packet display
if (isset($_GET['asciiclean'])) {
    1 == $_GET['asciiclean'] ? setcookie('asciiclean', 'clean', 0, "/ossim/forensics", NULL, 1, 1) : setcookie('asciiclean', 'normal', 0, "/ossim/forensics", NULL, 1, 1);
}

//if ($_GET['minimal_view'] == "1") {
	//require("../host_report_menu.php");
//}

// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$offset = 0;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");

function PrintCleanURL() {
    // This function creates the url to display the cleaned up payload -- Kevin
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    $sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
    if ((isset($_GET['asciiclean']) && $_GET['asciiclean'] == 1) || (isset($_COOKIE['asciiclean']) && ($_COOKIE['asciiclean'] == "clean") && (!isset($_GET['asciiclean'])))) {
        //create link to non-cleaned payload display
        $url = '<a href="base_qry_alert.php?' . $query;
        $url.= '&amp;sort_order=' . urlencode($sort_order) . '&amp;asciiclean=0&amp;minimal_view='.$_GET['minimal_view'].'">' . gettext("Normal Display") . '</a>';
        return $url;
    } else {
        //create link to cleaned payload display
        $url = '<a href="base_qry_alert.php?' . $query;
        $url.= '&amp;sort_order=' . urlencode($sort_order) . '&amp;asciiclean=1&amp;minimal_view='.$_GET['minimal_view'].'">' . gettext("Plain Display") . '</a>';
        return $url;
    }
}
function PrintBinDownload($db, $id) {
    // Offering a URL to a download possibility:
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    if (isset($_GET['asciiclean']) && ($_GET['asciiclean'] == 1) || ((isset($_COOKIE['asciiclean']) && $_COOKIE['asciiclean'] == "clean") && (!isset($_GET['asciiclean'])))) {
        $url = '<a href="base_payload.php?' . $query;
        $url.= '&amp;download=1&amp;id=' . urlencode($id) . '&amp;asciiclean=1&amp;minimal_view='.$_GET['minimal_view'].'">'._("Download of Payload").'</a>';
    } else {
        $url = '<a href="base_payload.php?' . $query;
        $url.= '&amp;download=1&amp;id=' . urlencode($id) . '&amp;asciiclean=0&amp;minimal_view='.$_GET['minimal_view'].'">'._("Download of Payload").'</a>';
    }
    return $url;
}
function PrintPcapDownload($db, $id) {
    if (is_array($db->DB->MetaColumnNames('data')) && (!in_array("pcap_header", $db->DB->MetaColumnNames('data')) || !in_array("data_header", $db->DB->MetaColumnNames('data')))) {
        $type = 3;
    } else {
        $type = 2;
    }
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    if ((isset($_GET['asciiclean']) && $_GET['asciiclean'] == 1) || (isset($_COOKIE['asciiclean']) && ($_COOKIE["asciiclean"] == "clean") && (!isset($_GET['asciiclean'])))) {
        $url = '<a href="base_payload.php?' . Util::htmlentities($query);
        $url.= '&amp;download=' . urlencode($type) . '&amp;id=' . urlencode($id) . '&amp;asciiclean=1&amp;minimal_view='.urlencode($_GET['minimal_view']).'"><img src="../pixmaps/wireshark.png" border="0" align="absmiddle" >'._("Download in pcap format").'</a>';
    } else {
        $url = '<a href="base_payload.php?' . Util::htmlentities($query);
        $url.= '&amp;download=' . urlencode($type) . '&amp;id=' . urlencode($id) . '&amp;asciiclean=0&amp;minimal_view='.urlencode($_GET['minimal_view']).'"><img src="../pixmaps/wireshark.png" border="0" align="absmiddle"> '._("Download in pcap format").'</a>';
    }
    return $url;
}
function PrintPacketLookupBrowseButtons2($seq, $order_by_tmp, $where_tmp, $db, &$previous_button, &$next_button) {
    echo "\n\n<!-- Single Alert Browsing Buttons -->\n";
    //if ($where_tmp != "") $_SESSION["where"] = $where_tmp;
    //if ($order_by_tmp != "") $_SESSION["order_by"] = $order_by_tmp;
    //$order_by = $_SESSION["order_by"];
    //$where = $_SESSION["where"];
    $order_by = $order_by_tmp;
    $where = $where_tmp;
    $previous_button = '<input type="submit" name="submit" id="sbutton" value="" style="display:none">
                        <input type="hidden" name="noheader" value="true">';
    $next_button     = '';
    if ($seq < 1) {
        $sql = "SELECT acid_event.id $where $order_by limit $seq,2";
        //echo $sql;
        $result2 = $db->baseExecute($sql);
        $previous_button .= '[ ' . gettext("First") . ' ]' . "\n";
        $myrow2 = $result2->baseFetchRow();
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button .= '[ ' . gettext("Last") . ' ]' . "\n";
        else {
            $next_button .= '<a href="" onclick="$(\'#sbutton\').val(\'#'.($seq + 1) . '-' . strtoupper(bin2hex($myrow2["id"])).'\');$(\'#sbutton\').click();return false">' . gettext("NEXT") . ' &gt;</a>'. "\n";
        }
    } else {
        $sql = "SELECT acid_event.id $where $order_by limit " . intval($seq - 1) . ",3";
        //echo $sql;
        $result2 = $db->baseExecute($sql);
        $myrow2 = $result2->baseFetchRow();
        $previous_button .= '<a href="" onclick="$(\'#sbutton\').val(\'#'.($seq - 1) . '-' . strtoupper(bin2hex($myrow2["id"])).'\');$(\'#sbutton\').click();return false">&lt; ' . gettext("PREVIOUS") . '</a>'. "\n";
        $myrow2 = $result2->baseFetchRow();
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button .= '[ ' . gettext("Last") . ' ]' . "\n";
        else {
            $next_button .= '<a href="" onclick="$(\'#sbutton\').val(\'#'.($seq + 1) . '-' . strtoupper(bin2hex($myrow2["id"])).'\');$(\'#sbutton\').click();return false">' . gettext("NEXT") . ' &gt;</a>'. "\n";
        }
    }
    $result2->baseFreeRows();
}
function showShellcodeAnalysisLink($id, $signature) {
    $url = (!preg_match("/shellcode/i",$signature)) ? '' : '<a style="color:lightgray" href="shellcode.php?id=' . $id . '">'._("Shellcode Analysis").'</a>';
    return $url;
}
function PrintPacketLookupBrowseButtons($seq, $save_sql, $db, &$previous_button, &$next_button) {
    echo "\n\n<!-- Single Alert Browsing Buttons -->\n";
    $result2 = $db->baseExecute($save_sql);
    if ($seq == 0) $previous_button = '[ ' . gettext("First") . ' ]' . "\n";
    $i = 0;
    while ($i <= $seq + 1) {
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button = '[ ' . gettext("Last") . ' ]' . "\n";
        else if ($i == $seq - 1) {
            $previous_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&lt;&lt; ' . gettext("Previous") . ' #';
            $previous_button.= ($seq - 1) . '-' . strtoupper(bin2hex($myrow2[0])) . '">' . "\n";
        } else if ($i == $seq + 1) {
            $next_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&gt;&gt; ' . gettext("Next") . ' #';
            $next_button.= ($seq + 1) . '-' . strtoupper(bin2hex($myrow2[0])) . '">' . "\n";
        }
        $i++;
    }
    $result2->baseFreeRows();
}
function GetPayloadFromAV($db, $eid, $is_snort) {
    $res = $db->baseExecute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME='extra_data' AND COLUMN_NAME='event_id'");
	$mr = $res->baseFetchRow();
	if (empty($mr)) {
	    $res->baseFreeRows();
        return array(_("Payload does not exists. This event has been purged from the SIEM database"), "");
    } else {
        $res = $db->baseExecute("SELECT data_payload,hex(data_payload) as hex_payload,hex(binary_data) as binary_data FROM alienvault.extra_data WHERE event_id=unhex('$eid')");
        $row = $res->baseFetchRow();
        $res->baseFreeRows();
        return array( ($is_snort) ? $row["hex_payload"] : Util::htmlentities($row["data_payload"]), $row["binary_data"]);
    }
        
}
/*
*  Need to import $submit and set the $QUERY_STRING early to support
*  the back button.  Otherwise, the value of $submit will not be passed
*  to the history.
*/
/* This call can include "#xx-(xx-xx)" values and "submit" values. */
$submit = ImportHTTPVar("submit", VAR_DIGIT | VAR_PUNC | VAR_LETTER, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
//if(preg_match("/^#0(-\(\d+-\d+\))$/", $submit, $matches)){
//$submit = "#1" . $matches[1];
//}
$sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
$_SERVER["QUERY_STRING"] = "submit=" . rawurlencode($submit);
//unset($_GET["sort_order"]);
$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . gettext("Query DB"));
$cs->ReadState();
$qs = new QueryState();
$page_title = gettext("Event");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
if (!array_key_exists("minimal_view", $_GET) && !array_key_exists("noheader", $_GET)) PrintCriteria("");
$criteria_clauses = ProcessCriteria();

// Include base_header.php
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$from = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE " . $criteria_clauses[1];
// Payload special case
//if (preg_match("/data_payload/", $criteria_clauses[1])) {
//    $where = ",extra_data WHERE acid_event.sid = extra_data.sid AND acid_event.cid=extra_data.cid AND " . $criteria_clauses[1];
//}
//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
//$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->SetActionSQL($sort_sql[0] . $from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_ALERT_DISPLAY, $db);
$et->Mark("Alert Action");
/* If get a valid (sid,cid) store it in $caller.
* But if $submit is returning from an alert action
* get the (sid,cid) back from $caller
*/
if ($submit == gettext("Delete Selected")) $submit = ImportHTTPVar("caller", VAR_DIGIT | VAR_PUNC);
else $caller = $submit;
/* Setup the Query Results Table -- However, this data structure is not
* really used for output.  Rather, it duplicates the sort SQL set in
*  base_qry_sqlcalls.php
*/
$qro = new QueryResultsOutput("");
$qro->AddTitle(gettext("Signature"), "sig_a", " ", " ORDER BY sig_name ASC", "sig_d", " ", " ORDER BY sig_name DESC");
$qro->AddTitle("Timestamp", "time_a", " ", " ORDER BY timestamp ASC ", "time_d", " ", " ORDER BY timestamp DESC ");
$qro->AddTitle("Source<BR>Address", "sip_a", " ", " ORDER BY ip_src ASC", "sip_d", " ", " ORDER BY ip_src DESC");
$qro->AddTitle("Dest.<BR>Address", "dip_a", " ", " ORDER BY ip_dst ASC", "dip_d", " ", " ORDER BY ip_dst DESC");
$qro->AddTitle("Layer 4<BR>Proto", "proto_a", " ", " ORDER BY layer4_proto ASC", "proto_d", " ", " ORDER BY layer4_proto DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
/* Apply sort criteria */
if ($sort_sql[1]=="" && !isset($sort_order)) $sort_order = "time_d";
if ($sort_order == "sip_a") {
    $sort_sql[1] = " ORDER BY ip_src ASC,timestamp DESC";
    $where = str_replace("1  AND ( timestamp", "ip_src >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "sip_d") {
    $sort_sql[1] = " ORDER BY ip_src DESC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_src >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "dip_a") {
    $sort_sql[1] = " ORDER BY ip_dst ASC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "dip_d") {
    $sort_sql[1] = " ORDER BY ip_dst DESC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "sig_a") {
    $sort_sql[1] = " ORDER BY plugin_id ASC,plugin_sid,timestamp DESC";
} elseif ($sort_order == "sig_d") {
    $sort_sql[1] = " ORDER BY plugin_id DESC,plugin_sid,timestamp DESC";
} elseif ($sort_order == "time_a") {
    $sort_sql[1] = " ORDER BY timestamp ASC";
} elseif ($sort_order == "time_d") {
    $sort_sql[1] = " ORDER BY timestamp DESC";
} elseif ($sort_order == "oasset_d_a") {
    $sort_sql[1] = " ORDER BY ossim_asset_dst ASC,timestamp DESC";
} elseif ($sort_order == "oasset_d_d") {
    $sort_sql[1] = " ORDER BY ossim_asset_dst DESC,timestamp DESC";
} elseif ($sort_order == "oprio_a") {
    $sort_sql[1] = " ORDER BY ossim_priority ASC,timestamp DESC";
} elseif ($sort_order == "oprio_d") {
    $sort_sql[1] = " ORDER BY ossim_priority DESC,timestamp DESC";
} elseif ($sort_order == "oriska_a") {
    $sort_sql[1] = " ORDER BY ossim_risk_c ASC,timestamp DESC";
} elseif ($sort_order == "oriska_d") {
    $sort_sql[1] = " ORDER BY ossim_risk_c DESC,timestamp DESC";
} elseif ($sort_order == "oriskd_a") {
    $sort_sql[1] = " ORDER BY ossim_risk_a ASC,timestamp DESC";
} elseif ($sort_order == "oriskd_d") {
    $sort_sql[1] = " ORDER BY ossim_risk_a DESC,timestamp DESC";
} elseif ($sort_order == "oreli_a") {
    $sort_sql[1] = " ORDER BY ossim_reliability ASC,timestamp DESC";
} elseif ($sort_order == "oreli_d") {
    $sort_sql[1] = " ORDER BY ossim_reliability DESC,timestamp DESC";
} elseif ($sort_order == "proto_a") {
    $sort_sql[1] = " ORDER BY ip_proto ASC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $where);
} elseif ($sort_order == "proto_d") {
    $sort_sql[1] = " ORDER BY ip_proto DESC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $where);
}

$save_sql = "SELECT acid_event.id " . $sort_sql[0] . $from . $where . $sort_sql[1];
//print_r($save_sql);
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
GetNewResultID($submit, $seq, $eid);
// if ($debug_mode > 0) echo "\n====== Alert Lookup =======<BR>
           // submit = $submit<br>
		   // eid = $eid<BR>
           // seq = $seq<BR>\n" . "===========================<BR>\n";
/* Verify that have extracted (eid, seq) correctly */
if (empty($eid)) {
    ErrorMessage(gettext("Invalid row-id pair") . " (" . $seq . "," . $eid . ")");
    exit();
}
$tmp_sql = $sort_sql[1];
if (!array_key_exists("minimal_view", $_GET)) echo "<!-- END HEADER TABLE -->
		  </div> </TD>
           </TR>
          </TABLE>";
echo "<FORM METHOD=\"GET\" ID='alertform' ACTION=\"base_qry_alert.php\">\n";
// Normal view
$back = "<a href=\"base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1\">&lt; "._('BACK')."</a>";
if (!array_key_exists("minimal_view", $_GET)) {
	PrintPacketLookupBrowseButtons2($seq, $tmp_sql, $sort_sql[0] . $from . $where, $db, $previous, $next);
	// \n<B>" . gettext("Event") . " ID: " . formatUUID($eid) . "</B><BR>
	echo "<div><div class='siem_detail_back'>$back</div> <div class='siem_detail_pagination'>\n$previous &nbsp;&nbsp; \n$next\n</div> <div class='clear_layer'></div> </div>\n";
	//echo "<HR style='border:none;background:rgb(202, 202, 202);height:1px;margin-top:15px;margin-bottom:15px'>\n";
	echo "<br/>";
// In graybox external minimal view (no pagging)
} elseif (!array_key_exists("noback", $_GET)) {
	echo "<div align='center'>$back</div><br/>";
}

/* Make Selected */
echo "\n<INPUT TYPE=\"hidden\" NAME=\"action_chk_lst[0]\" VALUE=\"$submit\">\n";

$empty    = "<span style='color:gray'><i>"._("empty")."</i></span>"; //IMPORTANT!!! iF YOU CHANGE THE VALUE, CHANGE IT ALSO IN THE KDB, (THIS DOCUMENT DOWN HERE)
$filename = $username = $password = $userdata1 = $userdata2 = $userdata3 = $userdata4 = $userdata5 = $userdata6 = $userdata7 = $userdata8 = $userdata9 = $empty;
$idm_data = array();
$payload  = "";
$snort_ids = range(1000, 1500);

/* Event */
//$sql2 = "SELECT signature, timestamp FROM acid_event WHERE sid='" .  filterSql($sid,$db) . "' AND cid='" .  filterSql($cid,$db) . "'";
$sql2 = "SELECT *, HEX(ctx) AS ctx, HEX(src_host) AS src_host, HEX(dst_host) AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) AS dst_net FROM acid_event WHERE id=unhex('$eid')";
//echo $sql2;

$result2 = $db->baseExecute($sql2);
$myrow2 = $result2->baseFetchRow();
$plugin_id = $myrow2["plugin_id"];
$plugin_sid = $myrow2["plugin_sid"];
$timestamp = $myrow2["timestamp"];
$ctx = $myrow2["ctx"];
$tzone = $myrow2["tzone"];
$ip_src = $myrow2["ip_src"]; $current_sip = @inet_ntop($ip_src);
$ip_dst = $myrow2["ip_dst"]; $current_dip = @inet_ntop($ip_dst);
$ip_proto = $myrow2["ip_proto"];
$layer4_sport = $myrow2["layer4_sport"];
$layer4_dport = $myrow2["layer4_dport"];
$ossim_priority= $myrow2["ossim_priority"];
$ossim_reliability = $myrow2["ossim_reliability"];
$ossim_asset_src = $myrow2["ossim_asset_src"];
$ossim_asset_dst = $myrow2["ossim_asset_dst"];
$ossim_risk_c = $myrow2["ossim_risk_c"];
$ossim_risk_a = $myrow2["ossim_risk_a"];
$idm_data['src_hostname'] = Util::htmlentities($myrow2['src_hostname']);
$idm_data['src_mac']      = formatMAC($myrow2['src_mac']);
$idm_data['dst_hostname'] = Util::htmlentities($myrow2['dst_hostname']);
$idm_data['dst_mac']      = formatMAC($myrow2['dst_mac']);
$is_snort  = in_array($plugin_id, $snort_ids);
$encoding = 2; # default ascii=2

if ($plugin_id == "" || $plugin_sid == "") {
    // Try to get info from alienvault.event (alarms)
    $result2->baseFreeRows();
	$sql2 = "SELECT *, HEX(src_host) AS src_host, HEX(dst_host) AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) AS dst_net FROM alienvault.event WHERE id=unhex('$eid')";
	$result2 = $db->baseExecute($sql2);
	$myrow2 = $result2->baseFetchRow();
	if (empty($myrow2)) {
        ErrorMessage(gettext("Event not found in MySQL Database. Probably Deleted"));
        exit();
    }
	$ctx = strtoupper(bin2hex($myrow2["agent_ctx"]));
	$plugin_id = $myrow2['plugin_id'];
	$plugin_sid = $myrow2['plugin_sid'];
	$is_snort  = in_array($plugin_id, $snort_ids);
	$encoding = ($is_snort) ? 0 : 2; # ascii=2; hex=0; base64=1
	$timestamp = $myrow2['timestamp'];
	$tzone = $myrow2['tzone'];
	$ip_src = $myrow2['src_ip']; $current_sip = @inet_ntop($ip_src);
	$ip_dst = $myrow2['dst_ip']; $current_dip = @inet_ntop($ip_dst);
	$ip_proto = $myrow2['protocol'];
	$layer4_sport = $myrow2['src_port'];
	$layer4_dport = $myrow2['dst_port'];
	$ossim_priority= $myrow2['priority'];
	$ossim_reliability = $myrow2['reliability'];
	$ossim_asset_src = $myrow2['asset_src'];
	$ossim_asset_dst = $myrow2['asset_dst'];
	$ossim_risk_c = $myrow2['risk_c'];
	$ossim_risk_a = $myrow2['risk_a'];
    $filename = Util::htmlentities($myrow2["filename"]); if ($filename=="") $filename=$empty;
    $username = Util::htmlentities($myrow2["username"]); if ($username=="") $username=$empty;
    $password = Util::htmlentities($myrow2["password"]); if ($password=="") $password=$empty;
    $userdata1 = Util::htmlentities($myrow2["userdata1"]); if ($userdata1=="") $userdata1=$empty;
    $userdata2 = Util::htmlentities($myrow2["userdata2"]); if ($userdata2=="") $userdata2=$empty;
    $userdata3 = Util::htmlentities($myrow2["userdata3"]); if ($userdata3=="") $userdata3=$empty;
    $userdata4 = Util::htmlentities($myrow2["userdata4"]); if ($userdata4=="") $userdata4=$empty;
    $userdata5 = Util::htmlentities($myrow2["userdata5"]); if ($userdata5=="") $userdata5=$empty;
    $userdata6 = Util::htmlentities($myrow2["userdata6"]); if ($userdata6=="") $userdata6=$empty;
    $userdata7 = Util::htmlentities($myrow2["userdata7"]); if ($userdata7=="") $userdata7=$empty;
    $userdata8 = Util::htmlentities($myrow2["userdata8"]); if ($userdata8=="") $userdata8=$empty;
    $userdata9 = Util::htmlentities($myrow2["userdata9"]); if ($userdata9=="") $userdata9=$empty;
    list($payload, $binary) = GetPayloadFromAV($db, $eid, $is_snort);
    
    $context = 0;
    $idm_data['src_hostname'] = Util::htmlentities($myrow2['src_hostname']);
    $idm_data['src_mac']      = formatMAC($myrow2['src_mac']);
    $idm_data['dst_hostname'] = Util::htmlentities($myrow2['dst_hostname']);
    $idm_data['dst_mac']      = formatMAC($myrow2['dst_mac']);
    // reputation data
    $idm_data['rep_prio_src'] = $myrow2['rep_prio_src'];
    $idm_data['rep_prio_dst'] = $myrow2['rep_prio_dst'];
    $idm_data['rep_rel_src']  = $myrow2['rep_rel_src'];
    $idm_data['rep_rel_dst']  = $myrow2['rep_rel_dst'];
    $idm_data['rep_act_src']  = $myrow2['rep_act_src'];
    $idm_data['rep_act_dst']  = $myrow2['rep_act_dst'];
    // idm_data
    $userdomains = array();
    $sqli = "select * from alienvault.idm_data where event_id=unhex('$eid')";
    $resulti = $db->baseExecute($sqli);
    while ($idmdata = $resulti->baseFetchRow()) {
        if ($idmdata["from_src"]) $userdomains["src"][] = $idmdata["username"]."@".$idmdata["domain"];
        else                      $userdomains["dst"][] = $idmdata["username"]."@".$idmdata["domain"];
    }
    if (!empty($userdomains)) {
        $idm_data["src_userdomains"] = implode(", ",$userdomains["src"]);
        $idm_data["dst_userdomains"] = implode(", ",$userdomains["dst"]);
    }
    $resulti->baseFreeRows();
    /* Get sensor parameters: */
    $sensor_id = bin2hex($myrow2['sensor_id']);
    $sql4 = "SELECT name,ip FROM alienvault.sensor s WHERE id=unhex('$sensor_id')";
    $result4 = $db->baseExecute($sql4);
    $myrow4 = $result4->baseFetchRow();
    $myrow4["interface"] = $myrow2["interface"];
	$sensor_ip   = @inet_ntop($myrow4['ip']);
	$sensor_name = $myrow4['name'];
    $result4->baseFreeRows();
    $detail = "";
}
else {
    /* Get sensor parameters: */
    $sensor_id = $myrow2['device_id'];
    $sql4 = "SELECT s.name,s.ip,d.interface FROM alienvault_siem.device d, alienvault.sensor s WHERE d.sensor_id=s.id AND d.id=" . $sensor_id;
    $result4 = $db->baseExecute($sql4);
    $myrow4 = $result4->baseFetchRow();
	$sensor_ip   = @inet_ntop($myrow4['ip']);
	$sensor_name = $myrow4['name'];
    $result4->baseFreeRows();
    $encoding = 1; # default base64=1
    $detail = $myrow4["detail"];

}

/* Get plugin id & sid */
$sql5 = "SELECT alienvault.plugin.name, alienvault.plugin_sid.name FROM alienvault.plugin LEFT JOIN alienvault.plugin_sid ON alienvault.plugin_sid.plugin_id = alienvault.plugin.id WHERE alienvault.plugin_sid.sid = $plugin_sid and alienvault.plugin.id = $plugin_id";
$result5 = $db->baseExecute($sql5);
if ($myrow5 = $result5->baseFetchRow()) {
    $plugin_name = $myrow5[0];
    $plugin_sid_name = $myrow5[1];
    $result5->baseFreeRows();
}
// empty plugin name...search only plugin name
if ($plugin_name=="") {
	$sql5 = "SELECT name FROM alienvault.plugin WHERE id = $plugin_id";
	$result5 = $db->baseExecute($sql5);
	if ($myrow5 = $result5->baseFetchRow()) {
	    $plugin_name = $myrow5[0];
	    $result5->baseFreeRows();
	}
}

// extra_data
$context = 0;
$sql6 = "select *,hex(data_payload) as hex_payload,hex(binary_data) as binary_data from extra_data where event_id=unhex('$eid')";
//echo $sql6;
$result6 = $db->baseExecute($sql6);
if ($myrow6 = $result6->baseFetchRow()) {
    $filename = Util::htmlentities($myrow6["filename"]); if ($filename=="") $filename=$empty;
    $username = Util::htmlentities($myrow6["username"]); if ($username=="") $username=$empty;
    $password = Util::htmlentities($myrow6["password"]); if ($password=="") $password=$empty;
    $userdata1 = Util::htmlentities($myrow6["userdata1"]); if ($userdata1=="") $userdata1=$empty;
    $userdata2 = Util::htmlentities($myrow6["userdata2"]); if ($userdata2=="") $userdata2=$empty;
    $userdata3 = Util::htmlentities($myrow6["userdata3"]); if ($userdata3=="") $userdata3=$empty;
    $userdata4 = Util::htmlentities($myrow6["userdata4"]); if ($userdata4=="") $userdata4=$empty;
    $userdata5 = Util::htmlentities($myrow6["userdata5"]); if ($userdata5=="") $userdata5=$empty;
    $userdata6 = Util::htmlentities($myrow6["userdata6"]); if ($userdata6=="") $userdata6=$empty;
    $userdata7 = Util::htmlentities($myrow6["userdata7"]); if ($userdata7=="") $userdata7=$empty;
    $userdata8 = Util::htmlentities($myrow6["userdata8"]); if ($userdata8=="") $userdata8=$empty;
    $userdata9 = Util::htmlentities($myrow6["userdata9"]); if ($userdata9=="") $userdata9=$empty;
    $payload = ($is_snort) ? $myrow6["hex_payload"] : Util::htmlentities($myrow6["data_payload"]);
    $binary = $myrow6["binary_data"];
    $encoding = ($is_snort) ? 0 : 2; # ascii=2; hex=0; base64=1
    $context = $myrow6["context"];
    $result6->baseFreeRows();
}

// reputation_data
$sql7 = "select * from reputation_data where event_id=unhex('$eid')";
$result7 = $db->baseExecute($sql7);
if ($repdata = $result7->baseFetchRow()) {
    $result7->baseFreeRows();
    foreach ($repdata as $k => $v) $idm_data[$k] = $v;
}
// idm_data
$userdomain = false;
$userdomains = array("src"=>array(),"dst"=>array());
$sql8 = "select * from idm_data where event_id=unhex('$eid')";
$result8 = $db->baseExecute($sql8);
while ($idmdata = $result8->baseFetchRow()) {
    $idmdata["username"] = trim($idmdata["username"]);
    $idmdata["domain"]   = trim($idmdata["domain"]);

	if ( !empty($idmdata["username"]) )
	{
    	$idm_u = $idmdata["username"];
		$idm_d = ( $idmdata["domain"] != '' ) ? "@".$idmdata["domain"] : '';

		if ( intval($idmdata["from_src"]) ) {
			$userdomains["src"][] = Util::htmlentities($idm_u.$idm_d);
			$userdomain = true;
		}
    	else{
			$userdomains["dst"][] = Util::htmlentities($idm_u.$idm_d);
			$userdomain = true;
		}
    }
}
$result8->baseFreeRows();
if ($userdomain) {
    $idm_data["src_userdomains"] = implode(", ",$userdomains["src"]);
    $idm_data["dst_userdomains"] = implode(", ",$userdomains["dst"]);
}
// Empty text if empty value
foreach ($idm_data as $k => $v) if (empty($v)) $idm_data[$k] = $empty;

// Timezone
$tz = Util::get_timezone();
$event_date = $timestamp;
$tzdate = $event_date;
$event_date_uut = get_utc_unixtime($db,$event_date);
// Event date timezone
if ($tzone!=0) $event_date = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tzone));
// Apply user timezone
if ($tz!=0) $tzdate = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tz));

$tzcell = ($event_date==$timestamp || $event_date==$tzdate) ? 0 : 1;
_("Event date").": ".htmlspecialchars("<b>".$event_date."</b><br>"._("Timezone").": <b>".Util::timezone($tzone)."</b>");

// This is one array that contains all the ids that are been used by snort, this way we will show more info for those events.

// COMMON DATA
//
require_once 'classes/geolocation.inc';
$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');
$_conn  = $dbo->connect();

// Source
$src_output = Asset_host::get_extended_name($_conn, $geoloc, $current_sip, $ctx, $myrow2['src_host'], $myrow2["src_net"]);
$sip_aux    = $src_output['name'];
$src_img    = $src_output['html_icon'];

$ip_src_data = '<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $src_img . " " . $sip_aux;

// Source Map
$src_latitude = $src_longitude = 0;
if (valid_hex32($myrow2['src_host']))
{
    if ($src_obj = Asset_host::get_object($_conn, $myrow2['src_host']))
    {
            $coordinates = $src_obj->get_location();
            if (floatval($coordinates['lat']) != 0) $src_latitude = floatval($coordinates['lat']);
            if (floatval($coordinates['lon']) != 0) $src_longitude = floatval($coordinates['lon']);
    }
}
if (!$src_latitude && !$src_longitude && $record->latitude!=0 && $record->longitude!=0)
{
    $src_latitude  = $record->latitude;
    $src_longitude = $record->longitude;
}

// Destination
$dst_output = Asset_host::get_extended_name($_conn, $geoloc, $current_dip, $ctx, $myrow2["dst_host"], $myrow2["dst_net"]);
$dip_aux    = $dst_output['name'];
$dst_img    = $dst_output['html_icon'];

$ip_dst_data = '<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask=32">' . $dst_img . " " . $dip_aux;

// Destination Map
$dst_latitude = $dst_longitude = 0;
if (valid_hex32($myrow2['dst_host']))
{
    if ($dst_obj = Asset_host::get_object($_conn, $myrow2['dst_host']))
    {
            $coordinates = $dst_obj->get_location();
            if (floatval($coordinates['lat']) != 0) $dst_latitude = floatval($coordinates['lat']);
            if (floatval($coordinates['lon']) != 0) $dst_longitude = floatval($coordinates['lon']);    
    }
}
if (!$dst_latitude && !$dst_longitude && $record->latitude!=0 && $record->longitude!=0)
{
    $dst_latitude  = $record->latitude;
    $dst_longitude = $record->longitude;
}

$dbo->close($_conn);

$txtzone = "<a href=\"javascript:;\" class=\"scriptinfoimg\" txt=\"<img src='../pixmaps/timezones/".rawurlencode(Util::timezone($tz)).".png' border=0>\">".Util::timezone($tz)."</a>";

list ($cat,$subcat) = GetCategorySubCategory($plugin_id,$plugin_sid,$db);
echo '
       <div class="siem_detail_table">
          <div class="siem_detail_section">Normalized<br>Event</div>
          <div class="siem_detail_content">
                  <TABLE class="table_list">
                    <TR>
                        <th>' . _("Date") . '</th>
                        '.($tzcell ? '<th>'._("Event date").'</th>' : '').'
                        <th>' . gettext("Alienvault Sensor") . '</th>
                        <th>' . gettext("Interface") . '</th>
					</TR>
                    <TR>
                        <TD> ' . htmlspecialchars($tzdate) . " " . $txtzone . '</TD>
                        '.($tzcell ? '<TD nowrap>'.$event_date.' '.Util::timezone($tzone).'</TD>' : '').'
                       <TD>' . htmlspecialchars( (@inet_ntop($myrow4["ip"])) ? $myrow4["name"]." [".inet_ntop($myrow4["ip"])."]" : _("Unknown")) . '</TD>
                       <TD>' . (($myrow4["interface"] == "") ? "&nbsp;<I>-</I>&nbsp;" : $myrow4["interface"]) . '</TD>
					</TR>
				  </TABLE>
                  <br/>
                  <TABLE class="table_list">
                    <TR>
                        <th>' . _("Triggered Signature") . '</th>
                        <th>' . _("Event Type ID") . '</th>
                        <th>' . _("Category") . '</th>
                        <th>' . _("Sub-Category") . '</th>
					</TR>
                    <TR>
                        <TD><a href="javascript:;" class="trlnka" id="'.$plugin_id.';'.$plugin_sid.'">';
                    	$htmlTriggeredSignature=str_replace("##", "", BuildSigByPlugin($plugin_id, $plugin_sid, $db));
                    	
                    	// Extradata translation adding
                    	$myrow2['filename'] = $myrow6['filename'];
                    	$myrow2['username'] = $myrow6['username'];
                    	for ($k = 1; $k <= 9; $k++)
                    	{
                    	    $myrow2['userdata'.$k] = $myrow6['userdata'.$k];
                    	}
                    	
                    	echo TranslateSignature($htmlTriggeredSignature, $myrow2).'</a></TD>
                        <TD>' . $plugin_sid . '</TD>
                        <TD>' . $cat . '</TD>
                        <TD>' . $subcat . '</TD>
                    </TR>
                  </TABLE>
                  <br/>
                  <TABLE class="table_list">
                    <TR>
                        <th>' . _("Data Source Name") . '</th>
                        <th>' . _("Product Type") . '</th>
                        <th>' . _("Data Source ID") . '</th>
					</TR>
                    <TR>
                        <TD>' . $plugin_name . '</TD>
                        <TD>' . GetSourceTypeFromPluginID($plugin_id,$db) . '</TD>
                        <TD>' . Util::htmlentities($plugin_id) . (($_GET['minimal_view'] == "" && Session::am_i_admin()) ? '&nbsp;<a href="javascript:;" onclick="GB_show(\''._("Modify Rel/Prio").'\',\'/forensics/modify_relprio.php?id='.Util::htmlentities($plugin_id).'&sid='.Util::htmlentities($plugin_sid).'\',280,450)" ><img align="abstop" src="../vulnmeter/images/pencil.png" border="0" alt="'._("Modify Rel/Prio").'" title="'._("Modify Rel/Prio").'"></a>' : '') ;
//'<a href="http://cve.mitre.org/cgi-bin/cvename.cgi?name=2009-0033" target="_blank"><img src="manage_references_icon.php?id=5" alt="cve" title="cve" border="0"></a> <a href="http://cve.mitre.org/cgi-bin/cvename.cgi?name=2007-5976" target="_blank"><img src="manage_references_icon.php?id=5" alt="cve" title="cve" border="0"></a> pads: New service detectedArray';

	//<--
	$return = array();
	$osvdb_url = 'http://cve.mitre.org/cgi-bin/cvename.cgi?name=';
	$osvdb_url_keyword = 'http://cve.mitre.org/cgi-bin/cvekey.cgi?keyword=';
	foreach(explode($osvdb_url,$htmlTriggeredSignature) as $key => $value ){
		if($key!=0){
			$posIni=strpos($value,"'");
			if ($posIni !== FALSE)
			{
			    $cve_number = substr($value,0,$posIni);
				$return[]   = (preg_match('/cve/i', $cve_number)) ? $cve_number : 'CVE-'.$cve_number;
			}
		}
	}
	if(!empty($return)){
		$arrayData='data='.implode('__',$return).'&plugin_id='.$plugin_id.'&plugin_sid='.$plugin_sid;
	?>
		&nbsp;<a href="<?php echo $osvdb_url_keyword.urlencode(implode(" ",$return))?>" title="<?php echo _("Info from OSVDB");?>" target="osvdb"><img src="../pixmaps/cve.gif" border="0" align="abdmiddle"></a>
		<?php
	}
	//-->
	$ossim_risk = ($ossim_risk_c < $ossim_risk_a) ? $ossim_risk_a : $ossim_risk_c;

echo '                </TD>
                    </TR>
                  </TABLE>
                  <br/>
                  <TABLE class="table_list">
                  <TR>
                       <th>' . gettext("Source Address") . '</th>
                       <th>' . gettext("Source Port") . '</th>
                       <th>' . gettext("Destination Address") . '</th>
                       <th>' . gettext("Destination Port") . '</th>
                       <th>' . gettext("Protocol") . '</th>
                  </TR>
                  <TR>
                       <TD class="plfield" nowrap><div id="'.$current_sip.';'.$sip_aux.';'.$myrow2["src_host"].'" ctx="'.$ctx.'" class="HostReportMenu">' . $ip_src_data . '</div></TD>
                       <TD class="plfield" nowrap>' . $layer4_sport . '</TD>
                       <TD class="plfield" nowrap><div id="'.$current_dip.';'.$dip_aux.';'.$myrow2["dst_host"].'" ctx="'.$ctx.'" class="HostReportMenu">' . $ip_dst_data . '</div></TD>
                       <TD class="plfield" nowrap>' . $layer4_dport . '</TD>
                       <TD class="plfield" nowrap>' . IPProto2str($ip_proto) . '</TD>
                  </TR>
                  </TABLE>
             </div>
          </div>

          <BR>

          <div class="siem_detail_table">
              <div class="siem_detail_section">SIEM</div>
              <div class="siem_detail_content">
          ';

echo '    <TABLE class="table_list">
          <TR>
 	           <th>' . _("Unique Event ID#"). '</th>
               <th>' . gettext("Asset").' S<img border="0" align="absmiddle" src="images/arrow-000-small.gif">D</th>
               <th>' . gettext("Priority") . '</th>
               <th>' . gettext("Reliability") . '</th>
               <th>' . gettext("Risk") . '</th>
          </TR>
          <TR>
 	          <TD>' . formatUUID($eid) . '</TD>
               <TD nowrap><img src="bar2.php?value=' . $ossim_asset_src . '&value2=' . $ossim_asset_dst . '&max=5" border="0" align="absmiddle" title="'.$ossim_asset_src -> $ossim_asset_dst.'"></TD>
               <TD nowrap><img src="bar2.php?value=' . $ossim_priority . '&max=5" border="0" align="absmiddle" title="'.$ossim_priority.'"></TD>
               <TD nowrap><img src="bar2.php?value=' . $ossim_reliability . '&max=9" border="0" align="absmiddle" title="'.$ossim_reliability.'"></TD>
               <TD nowrap><img src="bar2.php?value=' . $ossim_risk . '&max=9" border="0" align="absmiddle" title="'.$ossim_risk.'"></TD>
          </TR>
         </TABLE>';

$extradata1 = array();
$extradata2 = array();
if ($filename!=$empty) $extradata1["filename"] = $filename;
if ($username!=$empty) $extradata1["username"] = $username;
if ($password!=$empty) $extradata1["password"] = $password;
if ($userdata1!=$empty) $extradata1["userdata1"] = $userdata1;
if ($userdata2!=$empty) $extradata1["userdata2"] = $userdata2;
if ($userdata3!=$empty) $extradata1["userdata3"] = $userdata3;

if ($userdata4!=$empty) {
    if (count($extradata1)<6) $extradata1["userdata4"] = $userdata4;
    else                      $extradata2["userdata4"] = $userdata4;
}
if ($userdata5!=$empty) {
    if (count($extradata1)<6) $extradata1["userdata5"] = $userdata5;
    else                      $extradata2["userdata5"] = $userdata5;
}
if ($userdata6!=$empty) {
    if (count($extradata1)<6) $extradata1["userdata6"] = $userdata6;
    else                      $extradata2["userdata6"] = $userdata6;
}
if ($userdata7!=$empty) {
    if (count($extradata1)<6) $extradata1["userdata7"] = $userdata7;
    else                      $extradata2["userdata7"] = $userdata7;
}
if ($userdata8!=$empty) {
    if (count($extradata1)<6) $extradata1["userdata8"] = $userdata8;
    else                      $extradata2["userdata8"] = $userdata8;
}
if ($userdata9!=$empty) {
    if (count($extradata1)<6) $extradata1["userdata9"] = $userdata9;
    else                      $extradata2["userdata9"] = $userdata9;
}

if (!$is_snort && !empty($extradata1)) {

    if ($plugin_id == 5004) { // Anomalies => Show userdata1 and 2 like last and new values

        echo '<br/>    <TABLE class="table_list">';
        echo '            <TR>
                            <th>'._("Last value").'</th>
                            <th>'._("New value").'</th>
                          </TR>
                          <TR>
                           <TD>' . Util::htmlentities($userdata1) . '</TD>
                           <TD>' . Util::htmlentities($userdata2) . '</TD>
                          </TR>
                       </TABLE>';

    } else {
        echo '<br/><TABLE class="table_list"><TR>';
        foreach ($extradata1 as $k => $v) echo '<th>'._($k).'</th>';
        echo '</TR><TR>';
        foreach ($extradata1 as $k => $v) echo '<TD>'.Util::htmlentities(Util::wordwrap($v, 30, ' ', TRUE)).'</TD>';
        echo '</TR>';
        if (!empty($extradata2)) {
            echo '<TR>';
            foreach ($extradata2 as $k => $v) echo '<th>'._($k).'</th>';
            echo '</TR><TR>';
            foreach ($extradata2 as $k => $v) echo '<TD>'.Util::htmlentities($v).'</TD>';
            echo '</TR>';
        }
        echo '</TABLE>';
    }
}


// END COMMON DATA

        /* IDM */
        if ($_SESSION['_idm'] && !empty($idm_data)) {
		    echo '<br/>
                    <div class="siem_detail_table">
                    <div class="siem_detail_subsection">IDM</div>
                    <div class="siem_detail_subcontent">
					<TABLE class="table_list">
					<TR>
                        <!-- IDM Subtitle missing -->
					   <th>' . gettext("Src Username & Domain") . '</th>
					   <th>' . gettext("Src Hostname") . '</th>
					   <th>' . gettext("Src MAC") . '</th>
					   <th>' . gettext("Dst Username & Domain") . '</th>
					   <th>' . gettext("Dst Hostname") . '</th>
					   <th>' . gettext("Dst MAC") . '</th>
					</TR>
					<TR>
					  <TD nowrap>' . $idm_data["src_userdomains"] . '</TD>
					  <TD nowrap>' . $idm_data["src_hostname"] . '</TD>
					  <TD nowrap>' . $idm_data["src_mac"] . '</TD>
					  <TD nowrap>' . $idm_data["dst_userdomains"] . '</TD>
					  <TD nowrap>' . $idm_data["dst_hostname"] . '</TD>
					  <TD nowrap>' . $idm_data["dst_mac"] . '</TD>
					</TR>
					</TABLE>
                    </div>
                    </div>
			     ';
			$src_img = getrepimg($idm_data["rep_prio_src"],$idm_data["rep_rel_src"],$idm_data["rep_act_src"],$current_sip);
			$dst_img = getrepimg($idm_data["rep_prio_dst"],$idm_data["rep_rel_dst"],$idm_data["rep_act_dst"],$current_dip);
			$src_bgcolor = getrepbgcolor($idm_data["rep_prio_src"],1);
			$dst_bgcolor = getrepbgcolor($idm_data["rep_prio_dst"],1);
		    echo '<br/>
                    <div class="siem_detail_table">
                    <div class="siem_detail_subsection">'._('REPUTATION').'</div>
                    <div class="siem_detail_subcontent">
					<TABLE class="table_list">
					<TR><!-- REPUTATION Subtitle missing -->
					   <th>' . gettext("Source Address") . '</th>
					   <th>' . gettext("Priority") . '</th>
					   <th>' . gettext("Reliability") . '</th>
					   <th>' . gettext("Activity") . '</th>
					   <th>' . gettext("Destination Address") . '</th>
					   <th>' . gettext("Priority") . '</th>
					   <th>' . gettext("Reliability") . '</th>
					   <th>' . gettext("Activity") . '</th>
					</TR>
					<TR>
					  <TD '.$src_bgcolor.' nowrap>' . $src_img . $current_sip . '</TD>
					  <TD '.$src_bgcolor.' nowrap>' . $idm_data["rep_prio_src"] . '</TD>
					  <TD '.$src_bgcolor.' nowrap>' . $idm_data["rep_rel_src"] . '</TD>
					  <TD '.$src_bgcolor.' nowrap>' . $idm_data["rep_act_src"] . '</TD>
					  <TD '.$dst_bgcolor.' nowrap>' . $dst_img . $current_dip. '</TD>
					  <TD '.$dst_bgcolor.' nowrap>' . $idm_data["rep_prio_dst"] . '</TD>
					  <TD '.$dst_bgcolor.' nowrap>' . $idm_data["rep_rel_dst"] . '</TD>
					  <TD '.$dst_bgcolor.' nowrap>' . $idm_data["rep_act_dst"] . '</TD>
					</TR>
					</TABLE>
                    </div>
                    </div>
			     ';

		}

/*if ($resolve_IP == 1) {
    echo '  <TR>
              <TD>
                <TABLE BORDER=0 CELLPADDING=4>
                  <TR><TD CLASS="iptitle" ALIGN=CENTER ROWSPAN=2>FQDN</TD>
                       <TD class="header">' . gettext("Sensor") . ' ' . gettext("Name") . '</TD>
                  </TR>
                  <TR><TD class="plfield">' . (baseGetHostByAddr(inet_ntop($myrow4["ip"]), $db, $dns_cache_lifetime)) . '</TD>
                  </TR>
                 </TABLE>
            </TR>';
}*/
$result4->baseFreeRows();

echo '   </div></div>';
$result2->baseFreeRows();

// IDM => Host Inventory
require_once("classes/inventory.inc");

$idb = new Idmdb();
$src_host = $dst_host = false;
if ($_SESSION['_idm'] && $idb->available()=="") {
    $src_host = $idb->get_properties($myrow2['src_host'],$myrow2['timestamp']);
    $dst_host = $idb->get_properties($myrow2['dst_host'],$myrow2['timestamp']);
}
if ($src_host || $dst_host ) {
    echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Context").'</div>
              <div class="siem_detail_content">
                  <table class="transparent w100 context_table">
                      <tr>
                         <td class="left_context_td">
                              <div class="siem_detail_subsection_h">' . gettext("Source")  . '</div>
                              <TABLE class="table_list">
                                <TR>
                                    <th>' . _("Hostname") . '</th>
                                    <th>' . _("IP") . '</th>
                                    <th>' . _("MAC") . '</th>
                                    <th>' . _("Context") . '</th>
                                </TR>
                                <TR>
                                    <TD> &nbsp;' . $src_host["hostname"] . '</TD>
                                    <TD> &nbsp;' . $src_host["ip"] . '</TD>
                                    <TD> &nbsp;' . $src_host["mac"] . '</TD>
                                    <TD> &nbsp;' . ($entities[$src_host["ctx"]]!="" ? $entities[$src_host["ctx"]] : (($src_host["ctx"]=="") ? "" : _("Unknown"))) . '</TD>
                                <TR>
                              </TABLE>
                              <br/>
                              <TABLE class="table_list">
                                <TR>
                                    <th>' . _("Latest update") . '</th>
                                    <th>' . _("Services") . '</th>
                                    <th>' . _("Users info") . '</th>
                                </TR>
                                <TR>
                                    <TD> &nbsp;' . $src_host["date"] . '</TD>
                                    <TD> &nbsp;' .(($src_host) ? implode(", ", preg_replace("/(.*)\|(.*)\|(.*)/","\\3 (\\2/\\1)",$src_host["service"])) : "") . '</TD>
                                    <TD> &nbsp;' . (($src_host) ? str_replace("|",", ",implode("<br>",$src_host["username"])) : "") . '</TD>
                                <TR>
                                
                              </TABLE>
                          </td>  
                          <td class="right_context_td">
                              <DIV id="src_map" style="width:100%;height:180px"></DIV>
                          </td>
                          </tr>
                    </table>
                    
                  <br/>
                    
                  <table class="transparent w100 context_table">
                      <tr>
                         <td class="left_context_td">
                              <div class="siem_detail_subsection_h">' . gettext("Destination")  . '</div>
                              <TABLE class="table_list">
                                <TR>
                                    <th>' . _("Hostname") . '</th>
                                    <th>' . _("IP") . '</th>
                                    <th>' . _("MAC") . '</th>
                                    <th>' . _("Context") . '</th>
                                </TR>
                                <TR>
                                    <TD> &nbsp;' . $dst_host["hostname"] . '</TD>
                                    <TD> &nbsp;' . $dst_host["ip"] . '</TD>
                                    <TD> &nbsp;' . $dst_host["mac"] . '</TD>
                                    <TD> &nbsp;' . ($entities[$dst_host["ctx"]]!="" ? $entities[$dst_host["ctx"]] : (($dst_host["ctx"]=="") ? "" : _("Unknown"))) . '</TD>
                                <TR>
                              </TABLE>
                              <br/>
                              <TABLE class="table_list">
                                <TR>
                                    <th>' . _("Latest update") . '</th>
                                    <th>' . _("Services") . '</th>
                                    <th>' . _("Users info") . '</th>
                                </TR>
                                <TR>
                                    <TD> &nbsp;' . $dst_host["date"] . '</TD>
                                    <TD> &nbsp;' .(($dst_host) ? implode(", ", preg_replace("/(.*)\|(.*)\|(.*)/","\\3 (\\2/\\1)",$dst_host["service"])) : "") . '</TD>
                                    <TD> &nbsp;' . (($dst_host) ? str_replace("|",", ",implode("<br>",$dst_host["username"])) : "") . '</TD>
                                <TR>
                                
                              </TABLE>
                          </td>  
                          <td class="right_context_td">
                              <DIV id="dst_map" style="width:100%;height:180px"></DIV>
                          </td>
                       </tr>
                   </table>

        </div></div>';
} else {
    if (Session::is_pro())
        echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Context").'</div>
              <div class="siem_detail_content">&nbsp;'._("Event Context information is not available").'. '.$idb->available().'</div>
            </div>';
    else
        echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Context").'</div>
              <div class="siem_detail_content">&nbsp;'._("Event Context information is only available in AlienVault USM Server").'</div>
            </div>';
}



/* KDB SECTION */


$vars['_SENSOR']            = $sensor_name;
$vars['_SRCIP']             = $current_sip;
$vars['_SRCMAC']            = $idm_data['src_mac'];
$vars['_DSTIP']             = $current_dip;
$vars['_DSTMAC']            = $idm_data['dst_mac'];
$vars['_SRCPORT']           = $layer4_sport;
$vars['_DSTPORT']           = $layer4_dport;
$vars['_SRCCRITICALITY']    = $idm_data['rep_prio_src'];
$vars['_DSTCRITICALITY']    = $idm_data['rep_prio_dst'];
$vars['_SRCUSER']           = $username;
$vars['_FILENAME']          = $filename;
$vars['_USERDATA1']         = $userdata1;
$vars['_USERDATA2']         = $userdata2;
$vars['_USERDATA3']         = $userdata3;
$vars['_USERDATA4']         = $userdata4;
$vars['_USERDATA5']         = $userdata5;
$vars['_USERDATA6']         = $userdata6;
$vars['_USERDATA7']         = $userdata7;
$vars['_USERDATA8']         = $userdata8;
$vars['_USERDATA9']         = $userdata9;
$vars['_ALARMRISKSCORE']    = '';
$vars['_ALARMRELIABILITY']  = '';
$vars['_SRCREPACTIVITY']    = $idm_data['rep_act_src'];
$vars['_DSTREPACTIVITY']    = $idm_data['rep_act_dst'];
$vars['_SRCREPRELIABILITY'] = $idm_data['rep_rel_src'];
$vars['_DSTREPRELIABILITY'] = $idm_data['rep_rel_dst'];


$vars = array_map(function ($a) { return str_replace("<span style='color:gray'><i>"._("empty")."</i></span>", '', $a); }, $vars );


echo '	<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("KDB").'</div>
              <div class="siem_detail_content">';

					require_once '../repository/repository_view.php';

echo '			</div>
		   </div>';





if ($is_snort) {
    echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Payload").'
              ';
    //echo ("<br>" . PrintCleanURL());
    //echo ("<br>" . PrintBinDownload($db, $eid));
    echo showShellcodeAnalysisLink($eid, $plugin_sid_name);
    echo "</div>";
} else {
    echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Raw Log").'</div>';
    echo "<style type='text/css'>
    pre.nowrapspace { white-space: -moz-pre-wrap !important; white-space: -pre-wrap;white-space: -o-pre-wrap;white-space: pre-wrap;white-space: normal; min-height:20px; }
    </style>\n";
}
echo '       <div class="siem_detail_content">';
if ($payload) {
    /* print the packet based on encoding type */
    PrintPacketPayload($payload, $encoding, 1);
    if ($layer4_proto == "1") {
        if ( /* IF ICMP source quench */
        ($ICMPitype == "4" && $ICMPicode == "0") ||
        /* IF ICMP redirect */
        ($ICMPitype == "5") ||
        /* IF ICMP parameter problem */
        ($ICMPitype == "12" && $ICMPicode == "0") ||
        /* IF ( network, host, port unreachable OR
        frag needed OR network admin prohibited OR filtered) */
        ($ICMPitype == "3" || $ICMPitype == "11") && $ICMPicode == "0" || $ICMPicode == "1" || $ICMPicode == "3" || $ICMPicode == "4" || $ICMPicode == "9" || $ICMPicode == "13") {
            /* 0 == hex, 1 == base64, 2 == ascii; cf. snort-2.4.4/src/plugbase.h */
            if ($encoding == 1) {
                /* encoding is base64 */
                $work = bin2hex(base64_decode(str_replace("\n", "", $payload)));
            } else {
                /* assuming that encoding is hex */
                $work = str_replace("\n", "", $payload);
            }
            /*
            *  - depending on how the packet logged, 32-bits of NULL padding after
            *    the checksum may still be present.
            */
            if (substr($work, 0, 8) == "00000000") $offset = 8;
            /* for dest. unreachable, frag needed and DF bit set indent the padding
            * of MTU of next hop
            */
            else if (($ICMPitype == "3") && ($ICMPicode == "4")) $offset+= 8;
            if ($ICMPitype == "5") {
                $gateway = hexdec($work[0 + $offset] . $work[1 + $offset]) . "." . hexdec($work[2 + $offset] . $work[3 + $offset]) . "." . hexdec($work[4 + $offset] . $work[5 + $offset]) . "." . hexdec($work[6 + $offset] . $work[7 + $offset]);
                $offset+=8;
            }
            $icmp_src = hexdec($work[24 + $offset] . $work[25 + $offset]) . "." . hexdec($work[26 + $offset] . $work[27 + $offset]) . "." . hexdec($work[28 + $offset] . $work[29 + $offset]) . "." . hexdec($work[30 + $offset] . $work[31 + $offset]);
            $icmp_dst = hexdec($work[32 + $offset] . $work[33 + $offset]) . "." . hexdec($work[34 + $offset] . $work[35 + $offset]) . "." . hexdec($work[36 + $offset] . $work[37 + $offset]) . "." . hexdec($work[38 + $offset] . $work[39 + $offset]);
            $icmp_proto = hexdec($work[18 + $offset] . $work[19 + $offset]);
            $hdr_offset = ($work[$offset + 1]) * 8 + $offset;
            $icmp_src_port = hexdec($work[$hdr_offset] . $work[$hdr_offset + 1] . $work[$hdr_offset + 2] . $work[$hdr_offset + 3]);
            $icmp_dst_port = hexdec($work[$hdr_offset + 4] . $work[$hdr_offset + 5] . $work[$hdr_offset + 6] . $work[$hdr_offset + 7]);
            echo '<TABLE BORDER=0 class="siem_detail_dark">';
            echo '<TR>';
            if ($ICMPitype == "5") {
                echo '<TD class="header">Gateway IP</TD>';
                echo '<TD class="header">Gateway Name</TD>';
            }
            echo '<TD class="header">Protocol</TD>';
            echo '<TD class="header">Org.Source IP</TD>';
            echo '<TD class="header">Org.Source Name</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="header">Org.Source Port</TD>';
            echo '<TD class="header">Org.Destination IP</TD>';
            echo '<TD class="header">Org.Destination Name</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="header">Org.Destination Port</TD>';
            echo '</TR>';
            echo '<TR>';
            if ($ICMPitype == "5") {
                echo '<TD class="plfield">';
                echo '<A HREF="base_stat_ipaddr.php?ip=' . $gateway . '&amp;netmask=32" TARGET="_PL_SIP">' . $gateway . '</A></TD>';
                echo '<TD class="plfield">' . baseGetHostByAddr($gateway, $ctx, $db) . '</TD>';
            }
            echo '<TD class="plfield">' . IPProto2Str($icmp_proto) . '</TD>';
            echo '<TD class="plfield">';
            echo '<A HREF="base_stat_ipaddr.php?ip=' . $icmp_src . '&amp;netmask=32" TARGET="_PL_SIP">' . $icmp_src . '</A></TD>';
            echo '<TD class="plfield">' . baseGetHostByAddr($icmp_src, $ctx, $db) . '</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="plfield">' . $icmp_src_port . '</TD>';
            echo '<TD class="plfield">';
            echo '<A HREF="base_stat_ipaddr.php?ip=' . $icmp_dst . '&amp;netmask=32" TARGET="_PL_DIP">' . $icmp_dst . '</A></TD>';
            echo '<TD class="plfield">' . baseGetHostByAddr($icmp_dst, $ctx, $db) . '</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="plfield">' . $icmp_dst_port . '</TD>';
            echo '</TR>';
            echo '</TABLE>';
        }
    }
} else {
    /* Don't have payload so lets print out why by checking the detail level */
    /* if have fast detail level */
    echo '<div class="siem_detail_dark">';
    if ($detail == "0") echo '<BR> &nbsp <I>' . gettext("Fast logging used -i so payload was discarded") . '</I><BR>';
    else echo '<div class="siem_detail_payloadnone">' . gettext("none") . '</div>';
    echo '</div>';
}

if ($is_snort) {
	if ($plugin_id==1001) {
		//
		// snort rule detection
	    //
	    echo '<div><div class="siem_detail_snorttitle"><img src="../pixmaps/snort.png" border="0" align="absmiddle"> &nbsp; '._("Snort rule Detection").'</div>';
		$result = exec("grep -n 'sid:$plugin_sid;' /etc/snort/rules/*.rules");
		// format: /etc/snort/rules/ddos.rules:53:alert tcp $EXTERNAL_NET any -> $HOME_NET 15104 (msg:"DDOS mstream client to handler"; flow:stateless; flags:S,12; reference:arachnids,111; reference:cve,2000-0138; classtype:attempted-dos; sid:249; rev:8;)
		preg_match("/(.*?):\d+:(.*?) \((.*?);\)/",$result,$found);
		if (trim($result)=="" || count($found)<=1) {
			echo "<div class='siem_detail_snortattr'>"._("No rules found for sid")." <b>$plugin_sid</b></div>\n";
		} else {
			$file = basename($found[1]);
			echo "<div class='siem_detail_snortattr'><b>File:</b> $file</div>\n";
			$rule = $found[2];
			echo "<div class='siem_detail_snortattr'><b>Rule:</b> ".Util::htmlentities(str_replace(",",", ",$rule))."</div>\n";
			$more = explode(";",$found[3]);
			foreach ($more as $dat) {
				$val = explode(":",$dat);
				if ($val[0]!="") echo "<div class='siem_detail_snortattr siem_detail_snorttab'><b>".Util::htmlentities(trim($val[0])).":</b> ".Util::htmlentities($val[1])."</div>\n";
			}
		}
	    echo '</div>';
	}
	//
	// pcap
	//
    if (!empty($binary)) include ("base_payload_pcap.php");
}
?>
  <?php
echo '</div></div><br>';
?>
    <script type="text/javascript" src="https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="../js/av_map.js.php"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript">
        
        function draw_maps() 
        {                
            av_map_src = new Av_map('src_map');
            av_map_dst = new Av_map('dst_map');
            
            if(Av_map.is_map_available())
            {                    
                //Source Host
                                
                var src_latitude  = '<?php echo $src_latitude;?>';
                var src_longitude = '<?php echo $src_longitude;?>';
                                     
                av_map_src.set_location(src_latitude, src_longitude);                                       
    
                if(av_map_src.get_lat() != '' && av_map_src.get_lng() != '')
                {                        
                    av_map_src.set_zoom(3);
                    
                    av_map_src.add_marker(av_map_src.get_lat(), av_map_src.get_lng());
                    av_map_src.draw_map(3);
                    av_map_src.map.setOptions({draggable: false});
                    
                    // Change title and drag property
                    av_map_src.markers[0].setTitle('<?php echo $region_src?>');
                    av_map_src.markers[0].setDraggable(false);
                    
                    av_map_src.markers[0].setMap(av_map_src.map);                     
                }
                else
                {
                    $('#src_map').html('<div style="padding-top: 90px"><?php echo _('No location')?></div>');
                }
                
                
                //Destination Host             
                
                var dst_latitude  = '<?php echo $dst_latitude;?>';
                var dst_longitude = '<?php echo $dst_longitude;?>';
                                    
                av_map_dst.set_location(dst_latitude, dst_longitude);
                                   
    
                if(av_map_dst.get_lat() != '' && av_map_dst.get_lng() != '')
                {                        
                    av_map_dst.set_zoom(3);
                    av_map_dst.add_marker(av_map_dst.get_lat(), av_map_dst.get_lng());
                    av_map_dst.draw_map(3);
                    av_map_dst.map.setOptions({draggable: false});
                    
                    // Change title and drag property
                    av_map_dst.markers[0].setTitle('<?php echo $region_dst?>');
                    av_map_dst.markers[0].setDraggable(false);
                    
                    av_map_dst.markers[0].setMap(av_map_dst.map);                     
                }
                else
                {
                    $('#dst_map').html('<div style="padding-top: 90px"><?php echo _('No location')?></div>');
                }                                  
            }
            else
            {                
                if ($('#src_map').length >= 1)
    			{    				
    				av_map_src.draw_warning();
    				$('#src_map').parent().css('vertical-align', 'middle');
    				$('#src_map').css('height', 'auto');	
    			}
    
    			if ($('#dst_map').length >= 1)
    			{
    				av_map_dst.draw_warning();
    				$('#dst_map').parent().css('vertical-align', 'middle');
    				$('#dst_map').css('height', 'auto');
    			}
            }         
        }
        
   </script>
<?php
if (!array_key_exists("minimal_view", $_GET)) {
	echo "<div style='float:left'>$back</div> <div style='text-align:center'>\n$previous &nbsp;&nbsp; \n$next\n</div>\n";
	$qs->PrintAlertActionButtons();
}
?>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	<script>
    $(document).ready(function(){

        $('.scriptinfo').tipTip({
			defaultPosition: "right",
			content: function (e) {
				var ip  = $(this).attr('ip').replace(/;.*/, '');
				var ctx = $(this).attr('ctx');
				$.ajax({
					url: 'base_netlookup.php?ip=' + ip + ';' + ctx,
					success: function (response) {
						e.content.html(response); // the var e is the callback function data (see above)
					}
				});
				return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.
			}
	    });
		
		$('.scriptinfoimg').tipTip({
			defaultPosition: "down",
			content: function (e) {
				return $(this).attr('txt')
			}
	    }); 

        draw_maps();

    });
	</script>
<?php

$qs->SaveState();
ExportHTTPVar("caller", $caller);
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
if (!array_key_exists("minimal_view", $_GET)) $et->PrintTiming();
//echo "<script>if (typeof(load_tree)=='function') load_tree();</script>"; // Loaded from postload method (base_output_html.inc.php)
echo "<br></body></html>";
?>
