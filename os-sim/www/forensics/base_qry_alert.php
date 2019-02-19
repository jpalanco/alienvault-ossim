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
        $url.= '&amp;sort_order=' . urlencode($sort_order) . '&amp;asciiclean=0&amp;minimal_view='.$_GET['minimal_view'].'">' . _("Normal Display") . '</a>';
        return $url;
    } else {
        //create link to cleaned payload display
        $url = '<a href="base_qry_alert.php?' . $query;
        $url.= '&amp;sort_order=' . urlencode($sort_order) . '&amp;asciiclean=1&amp;minimal_view='.$_GET['minimal_view'].'">' . _("Plain Display") . '</a>';
        return $url;
    }
}
function PrintBinDownload($db, $id) {
    // Offering a URL to a download possibility:
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    if (isset($_GET['asciiclean']) && ($_GET['asciiclean'] == 1) || ((isset($_COOKIE['asciiclean']) && $_COOKIE['asciiclean'] == "clean") && (!isset($_GET['asciiclean'])))) {
        $url = '<a href="base_payload.php?' . $query;
        $url.= '&amp;download=1&amp;id=' . urlencode($id) . '&amp;asciiclean=1&amp;minimal_view='.$_GET['minimal_view'].'">&nbsp;['._("Download of Payload").']</a>';
    } else {
        $url = '<a href="base_payload.php?' . $query;
        $url.= '&amp;download=1&amp;id=' . urlencode($id) . '&amp;asciiclean=0&amp;minimal_view='.$_GET['minimal_view'].'">&nbsp;['._("Download of Payload").']</a>';
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
        $url.= '&amp;download=' . urlencode($type) . '&amp;id=' . urlencode($id) . '&amp;asciiclean=1&amp;minimal_view='.urlencode($_GET['minimal_view']).'">&nbsp;['._("Download in pcap format").']</a>';
    } else {
        $url = '<a href="base_payload.php?' . Util::htmlentities($query);
        $url.= '&amp;download=' . urlencode($type) . '&amp;id=' . urlencode($id) . '&amp;asciiclean=0&amp;minimal_view='.urlencode($_GET['minimal_view']).'">&nbsp;['._("Download in pcap format").']</a>';
    }
    return $url;
}
function PrintPacketLookupBrowseButtons2($seq, $order_by_tmp, $where_tmp, $db, &$previous_button, &$next_button) {
    echo "\n\n<!-- Single Alert Browsing Buttons -->\n";
    $order_by = $order_by_tmp;
    $where = $where_tmp;
    $previous_button = '<input type="submit" name="submit" id="sbutton" value="" style="display:none">
                        <input type="hidden" name="noheader" value="true">';
    $next_button     = '';
    if ($seq < 1)
    {
        $sql = "SELECT acid_event.id $where $order_by limit $seq,2";
        $result2 = $db->baseExecute($sql);
        $myrow2 = $result2->baseFetchRow();
        $myrow2 = $result2->baseFetchRow();
        if (!empty($myrow2))
        {
            $next_button .= '<a href="" onclick="$(\'#sbutton\').val(\'#'.($seq + 1) . '-' . strtoupper(bin2hex($myrow2["id"])).'\');$(\'#sbutton\').click();return false">' . _("NEXT") . ' &gt;</a>'. "\n";
        }
    }
    else
    {
        $sql = "SELECT acid_event.id $where $order_by limit " . intval($seq - 1) . ",3";
        $result2 = $db->baseExecute($sql);
        $myrow2 = $result2->baseFetchRow();
        $previous_button .= '<a href="" onclick="$(\'#sbutton\').val(\'#'.($seq - 1) . '-' . strtoupper(bin2hex($myrow2["id"])).'\');$(\'#sbutton\').click();return false">&lt; ' . _("PREVIOUS") . '</a>'. "\n";
        $myrow2 = $result2->baseFetchRow();
        $myrow2 = $result2->baseFetchRow();
        if (!empty($myrow2))
        {
            $next_button .= '<a href="" onclick="$(\'#sbutton\').val(\'#'.($seq + 1) . '-' . strtoupper(bin2hex($myrow2["id"])).'\');$(\'#sbutton\').click();return false">' . _("NEXT") . ' &gt;</a>'. "\n";
        }
    }
    $result2->baseFreeRows();
}
function showShellcodeAnalysisLink($id, $signature) {
    $url = (!preg_match("/shellcode/i",$signature)) ? '' : '&nbsp;&nbsp;&nbsp;<a class="greybox" href="shellcode.php?id=' . $id . '">['._("Shellcode Analysis").']</a>';
    return $url;
}
function PrintPacketLookupBrowseButtons($seq, $save_sql, $db, &$previous_button, &$next_button) {
    echo "\n\n<!-- Single Alert Browsing Buttons -->\n";
    $result2 = $db->baseExecute($save_sql);
    if ($seq == 0) $previous_button = '[ ' . _("First") . ' ]' . "\n";
    $i = 0;
    while ($i <= $seq + 1) {
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button = '[ ' . _("Last") . ' ]' . "\n";
        else if ($i == $seq - 1) {
            $previous_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&lt;&lt; ' . _("Previous") . ' #';
            $previous_button.= ($seq - 1) . '-' . strtoupper(bin2hex($myrow2[0])) . '">' . "\n";
        } else if ($i == $seq + 1) {
            $next_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&gt;&gt; ' . _("Next") . ' #';
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
        return array( ($is_snort) ? $row["hex_payload"] : $row["data_payload"], $row["binary_data"]);
    }

}
/*
*  Need to import $submit and set the $QUERY_STRING early to support
*  the back button.  Otherwise, the value of $submit will not be passed
*  to the history.
*/
/* This call can include "#xx-(xx-xx)" values and "submit" values. */
$submit = ImportHTTPVar("submit", VAR_DIGIT | VAR_PUNC | VAR_LETTER, array(
    _("Delete Selected"),
    _("Delete ALL on Screen"),
    _ENTIREQUERY
));

//if(preg_match("/^#0(-\(\d+-\d+\))$/", $submit, $matches)){
//$submit = "#1" . $matches[1];
//}
$sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
$pag = ImportHTTPVar("pag", VAR_DIGIT);

$_SERVER["QUERY_STRING"] = "submit=" . rawurlencode($submit);
//unset($_GET["sort_order"]);
$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . _("Query DB"));
$cs->ReadState();
$qs = new QueryState();
$page_title = _("Event");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
if (!array_key_exists("minimal_view", $_GET) && !array_key_exists("noheader", $_GET)) PrintCriteria("");
$criteria_clauses = ProcessCriteria();

// Include base_header.php
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink(), 1);

$from = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE " . $criteria_clauses[1];
$qs->AddValidAction("del_alert");
$qs->SetActionSQL($sort_sql[0] . $from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_ALERT_DISPLAY, $db);
$et->Mark("Alert Action");

/* If get a valid (sid,cid) store it in $caller.
* But if $submit is returning from an alert action
* get the (sid,cid) back from $caller
*/
if ($submit == _("Delete Selected")) $submit = ImportHTTPVar("caller", VAR_DIGIT | VAR_PUNC);
else $caller = $submit;
/* Setup the Query Results Table -- However, this data structure is not
* really used for output.  Rather, it duplicates the sort SQL set in
*  base_qry_sqlcalls.php
*/
$qro = new QueryResultsOutput("");
$qro->AddTitle(_("Signature"), "sig_a", " ", " ORDER BY sig_name ASC", "sig_d", " ", " ORDER BY sig_name DESC");
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

/* Verify that have extracted (eid, seq) correctly */
if (empty($eid))
{
    ErrorMessage(_("Invalid row-id pair") . " (" . $seq . "," . $eid . ")");
    exit();
}
$tmp_sql = $sort_sql[1];
if (!array_key_exists("minimal_view", $_GET)) echo "<!-- END HEADER TABLE -->
          </div> </TD>
           </TR>
          </TABLE>";
echo "<FORM METHOD=\"GET\" ID='alertform' ACTION=\"base_qry_alert.php\">\n";

/* Make Selected */
echo "\n<INPUT TYPE=\"hidden\" NAME=\"action_chk_lst[0]\" VALUE=\"$submit\">\n";
echo "\n<INPUT TYPE=\"hidden\" NAME=\"action\" id=\"alertaction\" VALUE=\"\">\n";

$empty    = "<span style='color:gray'>"._("N/A")."</span>"; //IMPORTANT!!! iF YOU CHANGE THE VALUE, CHANGE IT ALSO IN THE KDB, (THIS DOCUMENT DOWN HERE)
$filename = $username = $password = $userdata1 = $userdata2 = $userdata3 = $userdata4 = $userdata5 = $userdata6 = $userdata7 = $userdata8 = $userdata9 = $empty;
$idm_data = array();
$payload  = "";
$snort_ids = range(SNORT_MIN_PLUGIN_ID, SNORT_MAX_PLUGIN_ID);

/* Event */
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

if ($plugin_id == "" || $plugin_sid == "")
{
    // Try to get info from alienvault.event (alarms)
    $result2->baseFreeRows();
    $sql2 = "SELECT *, HEX(src_host) AS src_host, HEX(dst_host) AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) AS dst_net FROM alienvault.event WHERE id=unhex('$eid')";
    $result2 = $db->baseExecute($sql2);
    $myrow2 = $result2->baseFetchRow();
    if (empty($myrow2))
    {
        ErrorMessage(_("Event not found in MySQL Database. Probably Deleted"));
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
    while ($idmdata = $resulti->baseFetchRow())
    {
        if ($idmdata["from_src"]) $userdomains["src"][] = $idmdata["username"]."@".$idmdata["domain"];
        else                      $userdomains["dst"][] = $idmdata["username"]."@".$idmdata["domain"];
    }
    if (!empty($userdomains))
    {
        $idm_data["src_userdomains"] = implode(", ",$userdomains["src"]);
        $idm_data["dst_userdomains"] = implode(", ",$userdomains["dst"]);
    }
    $resulti->baseFreeRows();
    /* Get sensor parameters: */
    $sensor_id = bin2hex($myrow2['sensor_id']);
    $sql4 = "SELECT name,inet6_ntoa(ip) as ip FROM alienvault.sensor WHERE id=unhex('$sensor_id')";
    $result4 = $db->baseExecute($sql4);
    $myrow4 = $result4->baseFetchRow();
    $myrow4["interface"] = $myrow2["interface"];
    $sensor_ip   = $myrow4['ip'];
    $sensor_name = $myrow4['name'];
    $result4->baseFreeRows();
    $detail = "";
}
else
{
    /* Get sensor parameters: */
    $sensor_id = $myrow2['device_id'];
    $sql4 = "SELECT s.name,inet6_ntoa(s.ip) as ip,d.interface,inet6_ntoa(d.device_ip) as device_ip FROM alienvault_siem.device d, alienvault.sensor s WHERE d.sensor_id=s.id AND d.id=" . $sensor_id;
    $result4 = $db->baseExecute($sql4);
    $myrow4 = $result4->baseFetchRow();
    $sensor_ip   = $myrow4['ip'];
    $sensor_name = $myrow4['name'];
    $result4->baseFreeRows();
    $encoding = 1; # default base64=1
    $detail = $myrow4["detail"];

}

/* Get plugin id & sid */
$sql5 = "SELECT alienvault.plugin.name, alienvault.plugin_sid.name FROM alienvault.plugin LEFT JOIN alienvault.plugin_sid ON alienvault.plugin_sid.plugin_id = alienvault.plugin.id WHERE alienvault.plugin_sid.sid = $plugin_sid and alienvault.plugin.id = $plugin_id";
$result5 = $db->baseExecute($sql5);
if ($myrow5 = $result5->baseFetchRow())
{
    $plugin_name = $myrow5[0];
    $plugin_sid_name = $myrow5[1];
    $result5->baseFreeRows();
}
// empty plugin name...search only plugin name
if ($plugin_name=="")
{
    $sql5 = "SELECT name FROM alienvault.plugin WHERE id = $plugin_id";
    $result5 = $db->baseExecute($sql5);
    if ($myrow5 = $result5->baseFetchRow())
    {
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
    $payload = ($is_snort) ? $myrow6["hex_payload"] : $myrow6["data_payload"];
    $binary = $myrow6["binary_data"];
    $encoding = ($is_snort) ? 0 : 2; # ascii=2; hex=0; base64=1
    $context = $myrow6["context"];
    $result6->baseFreeRows();
}

$otxiocs = 0;
// reputation_data
$idm_data["rep_prio_dst"] = $idm_data["rep_rel_dst"] = $idm_data["rep_act_dst"] = $empty;
$idm_data["rep_prio_src"] = $idm_data["rep_rel_src"] = $idm_data["rep_act_src"] = $empty;
$repinfo_src = false;
$repinfo_dst = false;
$sql7 = "select * from reputation_data where event_id=unhex('$eid')";
$result7 = $db->baseExecute($sql7);
if ($repdata = $result7->baseFetchRow()) {
    $result7->baseFreeRows();
    foreach ($repdata as $k => $v) $idm_data[$k] = $v;
    $idm_data["rep_act_src"] = str_replace(';',', ',$idm_data["rep_act_src"]);
    $idm_data["rep_act_dst"] = str_replace(';',', ',$idm_data["rep_act_dst"]);
    if (!empty($idm_data["rep_act_src"])) 
    {
        $repinfo_src = true;
        $otxiocs++;
    }
    if (!empty($idm_data["rep_act_dst"]))
    {
        $repinfo_dst = true;
        $otxiocs++;
    }
}
$myrow2["pulse"] = '';
// otx_data
$sql9 = "select hex(pulse_id) as pulse,ioc_value from otx_data where event_id=unhex('$eid')";
$result9 = $db->baseExecute($sql9);
while ($otxdata = $result9->baseFetchRow()) {
    $result9->baseFreeRows();
    // $otx_data[$otxdata['pulse']][] = $otxdata['ioc_value'];
    $myrow2["pulse"] = $otxdata['pulse'];
    $otxiocs++;
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
$idm_data["src_userdomains"] = $idm_data["dst_userdomains"] = $empty;
if ($userdomain) {
    $idm_data["src_userdomains"] = implode(", ",$userdomains["src"]);
    $idm_data["dst_userdomains"] = implode(", ",$userdomains["dst"]);
}
// Empty text if empty value
foreach ($idm_data as $k => $v) if (empty($v)) $idm_data[$k] = $empty;

// OTX icon
$repinfo = $repinfo_src || $repinfo_dst;
$otxinfo = $myrow2["pulse"] != '';
$myrow2['otx'] = '';
if ($otxinfo && $repinfo)
{
    $myrow2['otx'] = 'otxrep';
}
elseif ($otxinfo && !$repinfo)
{
    $myrow2['otx'] = 'otx';
}
elseif (!$otxinfo && $repinfo)
{
    $myrow2['otx'] = 'rep';
}

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
_("Event date").": <b>". Util::htmlentities($event_date)."</b><br>"._("Timezone").": <b>". Util::htmlentities(Util::timezone($tzone))."</b>";

// This is one array that contains all the ids that are been used by snort, this way we will show more info for those events.

// COMMON DATA
//
require_once 'classes/geolocation.inc';
$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');
$_conn  = $dbo->connect();

$limitc = 6;
//
// SOURCE
//
$src_nets   = GetClosestNets($db, $myrow2['src_host'], $current_sip, $ctx, $limitc);
$aux        = array();
foreach ($src_nets as $nid => $nname) $aux[] = '<a href="#" data-url="'.Menu::get_menu_url(AV_MAIN_PATH.'/av_asset/common/views/detail.php?asset_id='.$nid, 'environment', 'assets', 'networks').'">'.Util::htmlentities($nname).'</a>';
if (empty($aux)) $aux[] = $empty;
$src_nets   = implode(', ',$aux) . (count($aux)>=$limitc ? ' [...]' : '');

$src_groups = GetAssetGroups($db, $myrow2['src_host'], $current_sip, $ctx, $limitc);
$aux        = array();
foreach ($src_groups as $nid => $nname) $aux[] = '<a href="#" data-url="'.Menu::get_menu_url(AV_MAIN_PATH.'/av_asset/common/views/detail.php?asset_id='.$nid, 'environment', 'assets', 'asset_groups').'">'.Util::htmlentities($nname).'</a>';
if (empty($aux)) $aux[] = $empty;
$src_groups   = implode(', ',$aux) . (count($aux)>=$limitc ? ' [...]' : '');

$src_output = Asset_host::get_extended_name($_conn, $geoloc, $current_sip, $ctx, $myrow2['src_host'], $myrow2["src_net"]);
$sip_aux    = $src_output['name'];
$src_img    = $src_output['html_icon'];
$src_loc    = preg_match("/data-title\s*=\s*'([^\d]+)'/",$src_img,$matches) ? $src_img.' <a target="_blank" href="'.$gmaps_url.'">'.$matches[1].'</a>' : '';

$ip_src_data = (preg_match("/data-title\s*=\s*'\d+/",$src_img) ? $src_img.' ' : ' ') . ($myrow2['src_host'] !='' ? '<A HREF="#" data-url="'.Menu::get_menu_url(AV_MAIN_PATH.'/av_asset/common/views/detail.php?asset_id='.$myrow2['src_host'], 'environment', 'assets', 'assets').'">' : '<A HREF="#" data-url="'.AV_MAIN_PATH.'/forensics/base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">') . $sip_aux . ($current_sip==$sip_aux ? '' : ' ['.$current_sip.']');

$reptooltip_src = getreptooltip($idm_data["rep_prio_src"],$idm_data["rep_rel_src"],$idm_data["rep_act_src"],$current_sip);

// Source Map
$src_latitude = $src_longitude = 0;
if (valid_hex32($myrow2['src_host']))
{
    if ($src_obj = Asset_host::get_object($_conn, $myrow2['src_host']))
    {
        $coordinates = $src_obj->get_location();
        if (floatval($coordinates['lat']) != 0) $src_latitude = floatval($coordinates['lat']);
        if (floatval($coordinates['lon']) != 0) $src_longitude = floatval($coordinates['lon']);
        if (empty($src_loc))
        {
            $src_location = Asset_host::get_extended_location($_conn, $geoloc, $current_sip, $myrow2['src_host']);
            if ($src_location['country']['code'])
            {
                $src_loc  = '<img src="../pixmaps/flags/'.$src_location['country']['code'].'.png"/> <a target="_blank" href="'.$gmaps_url.'">'.$src_location['country']['name'].'</a>';
            }
        }
    }
}

if (!$src_latitude && !$src_longitude)
{
    $record = $geoloc->get_location_from_file($current_sip);

    if ($record->latitude != 0 && $record->longitude != 0)
    {
        $src_latitude  = $record->latitude;
        $src_longitude = $record->longitude;
    }

    if (empty($src_loc) && $record->country_name != '')
    {
        $src_loc = '<img src="../pixmaps/flags/'.strtolower($record->country_code).'.png"/> <a target="_blank" href="'.$gmaps_url.'">'.$record->country_name.'</a>';
    }
}

$src_loc = str_replace('__LAT__',$src_latitude,str_replace('__LONG__',$src_longitude,$src_loc));

//
// DESTINATION
//
$dst_nets   = GetClosestNets($db, $myrow2['dst_host'], $current_dip, $ctx, $limitc);
$aux        = array();
foreach ($dst_nets as $nid => $nname) $aux[] = '<a href="#" data-url="'.Menu::get_menu_url(AV_MAIN_PATH.'/av_asset/common/views/detail.php?asset_id='.$nid, 'environment', 'assets', 'networks').'">'.Util::htmlentities($nname).'</a>';
if (empty($aux)) $aux[] = $empty;
$dst_nets   = implode(', ',$aux) . (count($aux)>=$limitc ? ' [...]' : '');

$dst_groups = GetAssetGroups($db, $myrow2['dst_host'], $current_dip, $ctx, $limitc);
$aux        = array();
foreach ($dst_groups as $nid => $nname) $aux[] = '<a href="#" data-url="'.Menu::get_menu_url(AV_MAIN_PATH.'/av_asset/common/views/detail.php?asset_id='.$nid, 'environment', 'assets', 'asset_groups').'">'.Util::htmlentities($nname).'</a>';
if (empty($aux)) $aux[] = $empty;
$dst_groups   = implode(', ',$aux) . (count($aux)>=$limitc ? ' [...]' : '');

$dst_output = Asset_host::get_extended_name($_conn, $geoloc, $current_dip, $ctx, $myrow2["dst_host"], $myrow2["dst_net"]);
$dip_aux    = $dst_output['name'];
$dst_img    = $dst_output['html_icon'];
$dst_loc    = preg_match("/data-title\s*=\s*'([^\d]+)'/",$dst_img,$matches) ? $dst_img.' <a target="_blank" href="'.$gmaps_url.'">'.$matches[1].'</a>' : '';

$ip_dst_data = (preg_match("/data-title\s*=\s*'\d+/",$dst_img) ? $dst_img.' ' : ' ') . ($myrow2['dst_host'] !='' ? '<A HREF="#" data-url="'.Menu::get_menu_url(AV_MAIN_PATH.'/av_asset/common/views/detail.php?asset_id='.$myrow2['dst_host'], 'environment', 'assets', 'assets').'">' : '<A HREF="#" data-url="'.AV_MAIN_PATH.'/forensics/base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask=32">') . $dip_aux . ($current_dip==$dip_aux ? '' : ' ['.$current_dip.']');

$reptooltip_dst = getreptooltip($idm_data["rep_prio_dst"],$idm_data["rep_rel_dst"],$idm_data["rep_act_dst"],$current_dip);

// Destination Map
$dst_latitude = $dst_longitude = 0;
if (valid_hex32($myrow2['dst_host']))
{
    if ($dst_obj = Asset_host::get_object($_conn, $myrow2['dst_host']))
    {
        $coordinates = $dst_obj->get_location();
        if (floatval($coordinates['lat']) != 0) $dst_latitude = floatval($coordinates['lat']);
        if (floatval($coordinates['lon']) != 0) $dst_longitude = floatval($coordinates['lon']);
        if (empty($dst_loc))
        {
            $dst_location = Asset_host::get_extended_location($_conn, $geoloc, $current_dip, $myrow2['dst_host']);
            if ($dst_location['country']['code'])
            {
                $dst_loc  = '<img src="../pixmaps/flags/'.$dst_location['country']['code'].'.png"/> <a target="_blank" href="'.$gmaps_url.'">'.$dst_location['country']['name'].'</a>';
            }
        }
    }
}
if (!$dst_latitude && !$dst_longitude)
{
    $record = $geoloc->get_location_from_file($current_dip);

    if ($record->latitude != 0 && $record->longitude != 0)
    {
        $dst_latitude  = $record->latitude;
        $dst_longitude = $record->longitude;
    }

    if (empty($dst_loc) && $record->country_name != '')
    {
        $dst_loc = '<img src="../pixmaps/flags/'.strtolower($record->country_code).'.png"/> <a target="_blank" href="'.$gmaps_url.'">'.$record->country_name.'</a>';
    }
}

$dst_loc = str_replace('__LAT__',$src_latitude,str_replace('__LONG__',$src_longitude,$dst_loc));

$dbo->close($_conn);

// Signature
$htmlTriggeredSignature=explode("##", BuildSigByPlugin($plugin_id, $plugin_sid, $db, $ctx));

// Extradata translation adding
$myrow2['filename'] = $myrow6['filename'];
$myrow2['username'] = $myrow6['username'];
for ($k = 1; $k <= 9; $k++)
{
    $myrow2['userdata'.$k] = $myrow6['userdata'.$k];
}

$signature = TranslateSignature($htmlTriggeredSignature[1], $myrow2);
// VIEW
$back = "<a href=\"base_qry_main.php?num_result_rows=-1&submit=Query+DB&caller=&pag=$pag&current_view=$pag\">"._('Security Events')."</a>";
if (!array_key_exists("minimal_view", $_GET))
{
    PrintPacketLookupBrowseButtons2($seq, $tmp_sql, $sort_sql[0] . $from . $where, $db, $previous, $next);
?>
<!-- Breadcrum -->
<div id="bread_crumb" class="av_breadcrumb">
    <div class="av_breadcrumb_item av_link"><?php echo $back ?></div>
    <div class="av_breadcrumb_separator"></div>
    <div class="av_breadcrumb_item last"><?php echo $signature ?></div>
    <div class='siem_detail_pagination'><?php echo $previous .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'. $next ?></div>
</div>
<div class="av_breadcrumb title_header">
    <div class="siem_title"><?php echo $signature ?></div>
    <div class='actions'>
        <button id="actions_link_detail" class="button">
            <?php echo _('Actions') ?> &nbsp;&#x25be;
        </button>
        <div id="actions_dd" class="dropdown dropdown-secondary dropdown-close dropdown-tip dropdown-anchor-right" style='display:none'>
            <ul id="actions_dd_ul" class="dropdown-menu">
                <li><a href="#" onclick="delete_event();return false"><?php echo _('Delete') ?></a></li>
                <?php
                if ( Session::menu_perms("analysis-menu", "IncidentsOpen") )
                {
                    $link_incident = "../incidents/newincident.php?ref=Event&title=" . urlencode($htmlTriggeredSignature[1]) . "&priority=1&src_ips=".$current_sip."&event_start=".$myrow['timestamp']."&event_end=".$myrow['timestamp']."&src_ports=".$myrow["layer4_sport"]."&dst_ips=".$current_dip."&dst_ports=".$myrow["layer4_dport"];
                ?>
                <li><a href="#" onclick="new_incident('<?php echo $link_incident ?>');return false"><?php echo _('Create Ticket') ?></a></li>
                <?php
                }
                ?>
                <li><a href="#" onclick="insert_into_dsgroup();return false"><?php echo _('Insert into DS Group') ?></a></li>
                <li><a href="#" onclick="edit_directive();return false"><?php echo _('Edit Event Properties') ?></a></li>
                <li><a href="#" onclick="learn_more();return false" id="kdb_docs"><?php echo _('Learn More') ?></a></li>
            </ul>
        </div>
    </div>
</div>
<?php
// In graybox external minimal view (no pagging)
}
elseif (!array_key_exists("noback", $_GET))
{
    $back = str_replace(_('Security Events'),_('Back'),$back);
    echo "<div align='center'>$back</div><br/>";
}
else
{
    ?><div class="av_breadcrumb title_header">
        <div class="siem_title"><?php echo $signature ?></div>
    </div><?php
}

$txtzone = "<a href=\"javascript:;\" class=\"tzoneimg\" txt=\"<img src='../pixmaps/timezones/".rawurlencode(Util::timezone($tz)).".png' width='400' height='205' border=0>\">".Util::timezone($tz)."</a>";

// Taxonomy
list ($cat,$subcat) = GetCategorySubCategory($plugin_id,$plugin_sid,$db);

// Risk & Proto
$ossim_risk = ($ossim_risk_c < $ossim_risk_a) ? $ossim_risk_a : $ossim_risk_c;

$p_name = Protocol::get_protocol_by_number($ip_proto, TRUE);

if (FALSE === $p_name)
{
    $p_name = _('UNKNOWN');
}

$otx_link = '<a class="trlnk __CLASS__" href="#" txt="__TOOLTIP__" onclick="GB_show(\''._("OTX Details").'\',\''.str_replace('__EVENTID__',$eid,$otx_detail_url).'\',500,\'80%\');return false">__VALUE__</a>';
?>

<script type="text/javascript" src="../js/utils.js"></script>
<script type="text/javascript" src="../js/av_map.js.php"></script>
<script type="text/javascript" src="../js/notification.js"></script>
<?php
if (array_key_exists("minimal_view", $_GET))
{
    echo '<style type="text/css">body { margin:5px 25px !important } </style>';
}

echo '
       <div class="siem_detail_table nodoubleborder">
          <div class="siem_detail_column_left nodoubleborder">
                  <TABLE class="siem_table">
                    <TR>
                        <th>' . _("Date") . '</th>
                        <TD> ' .  Util::htmlentities($tzdate) . " " . $txtzone . '</TD>
                    </TR>
                        <th>' . _("AlienVault Sensor") . '</th>
                        <TD>' .  Util::htmlentities( ($myrow4["ip"] != '') ? $myrow4["name"]." [".$myrow4["ip"]."]" : _("Unknown")) . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Device IP") . '</th>
                        <TD>' . (($myrow4["device_ip"] == "") ? "&nbsp;<I>"._("N/A")."</I>&nbsp;" : $myrow4["device_ip"]) . (($myrow4["interface"] == "" || $myrow4["device_ip"] == "") ? "" : "&nbsp;[" . $myrow4["interface"] . "]") . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Event Type ID") . '</th>
                        <TD>' . $plugin_sid . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Unique Event ID#"). '</th>
                        <TD>' . formatUUID($eid) . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Protocol") . '</th>
                        <TD>' . strtoupper($p_name) . '</TD>
                    </TR>
                 </TABLE>';

echo '      </div>
            <div class="siem_detail_sep"></div>
            <div class="siem_detail_column_right nodoubleborder">
                  <TABLE class="siem_table">
                    <TR>
                        <th>' . _("Category") . '</th>
                        <TD>' . ($cat ? $cat : $empty) . '</TD>
                    </TR>
                        <th>' . _("Sub-Category") . '</th>
                        <TD>' . ($subcat ? $subcat : $empty) . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Data Source Name") . '</th>
                        <TD>' . $plugin_name . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Data Source ID") . '</th>
                        <TD>' . Util::htmlentities($plugin_id) ;
                                $return = array();
                                $osvdb_url = 'http://cve.mitre.org/cgi-bin/cvename.cgi?name=';
                                $osvdb_url_keyword = 'http://cve.mitre.org/cgi-bin/cvekey.cgi?keyword=';
                                foreach(explode($osvdb_url,$htmlTriggeredSignature[0]) as $key => $value )
                                {
                                    if($key!=0)
                                    {
                                        $posIni=strpos($value,"'");
                                        if ($posIni !== FALSE)
                                        {
                                            $cve_number = substr($value,0,$posIni);
                                            $return[]   = (preg_match('/cve/i', $cve_number)) ? $cve_number : 'CVE-'.$cve_number;
                                        }
                                    }
                                }
                                if(!empty($return))
                                {
                                    $arrayData='data='.implode('__',$return).'&plugin_id='.$plugin_id.'&plugin_sid='.$plugin_sid;
                                ?>
                                    &nbsp;<a href="<?php echo $osvdb_url_keyword.urlencode(implode(" ",$return))?>" title="<?php echo _("Info from OSVDB");?>" target="osvdb"><img src="../pixmaps/cve.gif" border="0" align="abdmiddle"></a>
                                    <?php
                                }
echo '                </TD>
                    </TR>
                    <TR>
                        <th>' . _("Product Type") . '</th>
                        <TD>' . GetSourceTypeFromPluginID($plugin_id,$db) . '</TD>
                    </TR>
                    <TR>
                        <th>' . _("Additional Info") . '</th>
                        <TD>' . ($htmlTriggeredSignature[0] ? $htmlTriggeredSignature[0] : $empty) . '</TD>
                    </TR>
                 </TABLE>
            </div>
        </div>
';
$risk_text = Util::get_risk_rext($ossim_risk,0);
/* Summary Bar */
echo '<TABLE class="siem_table">
      <TR>
           <th class="autow">' . _("Priority") . '</th>
           <th class="autow">' . _("Reliability") . '</th>
           <th class="autow">' . _("Risk") . '</th>
           <th class="autow otx">' . ($myrow2['otx']!='' ? '<img class="otx" src="../pixmaps/'.$myrow2['otx'].'_icon.png" border=0/> ' : '') . _("OTX Indicators") . '</th>
      </TR>
      <TR>
           <TD class="center">' . $ossim_priority . '</TD>
           <TD class="center">' . $ossim_reliability . '</TD>
           <TD class="center"><span class="risk-bar '.$risk_text.'">' . _($risk_text) . '</span></TD>
           <TD class="center">'.($otxiocs ? str_replace('__CLASS__','',str_replace('__VALUE__', $otxiocs, $otx_link)) : $otxiocs).'</TD>
      </TR>
     </TABLE>';

/* SRC & DST */

$result4->baseFreeRows();
$result2->baseFreeRows();

/* MongoDB Inventory data */
require_once("classes/inventory.inc");

$idb = new Idmdb();
$src_host = $dst_host = false;
if ($_SESSION['_idm'] && $idb->available()=="")
{
    $src_host = $idb->get_properties($myrow2['src_host'],$myrow2['timestamp']);
    $dst_host = $idb->get_properties($myrow2['dst_host'],$myrow2['timestamp']);
}

echo '<div class="siem_detail_table">
          <div class="siem_detail_column_left">
                <div class="siem_detail_section">
                    <div class="siem_left">'._("Source").'</div>
                    <div class="siem_right">
                        <div id="'.$current_sip.';'.$sip_aux.';'.$myrow2["src_host"].'" ctx="'.$ctx.'" class="HostReportMenu siem_cell">' . $ip_src_data . '</a></div>
                        <div class="siem_cell"></div>
                    </div>
                </div>
                <div class="siem_detail_content">';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Hostname") . ': '.$idm_data["src_hostname"].'</div>
                             <div class="content_r">' . _("Location") . ': '.($src_loc ? $src_loc : $empty).'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("MAC Address") . ': '.$idm_data["src_mac"].'</div>
                             <div class="content_r">' . _("Context") . ': '.($entities[$src_host["ctx"]]!="" ? $entities[$src_host["ctx"]] : (($src_host["ctx"]=="") ? $empty : _("Unknown"))).'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Port") . ': '.$layer4_sport.'</div>
                             <div class="content_r">' . _("Asset Groups") . ': '.$src_groups.'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Latest update") . ': '.($src_host["date"] ? $src_host["date"] : $empty).'</div>
                             <div class="content_r">' . _("Networks") . ': '.$src_nets.'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Username & Domain") . ': '.$idm_data["src_userdomains"].'</div>
                             <div class="content_r">' . _("Logged Users") . ': '.(($src_host["username"]) ? preg_replace("/,\s*$/",'',str_replace("|",", ",implode("<br>",$src_host["username"]))) : $empty).'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Asset Value") . ': '.$ossim_asset_src.'</div>
                             <div class="content_r">' . _("OTX IP Reputation") . ': '.($repinfo_src ?  str_replace('__CLASS__','scriptinfoimg',str_replace('__TOOLTIP__',$reptooltip_src,str_replace('__VALUE__', _('Yes'), $otx_link))) : _('No')).'</div>
                          </div>';
                    /*
                    $src_img = getrepimg($idm_data["rep_prio_src"],$idm_data["rep_rel_src"],$idm_data["rep_act_src"],$current_sip);
                    $src_bgcolor = getrepbgcolor($idm_data["rep_prio_src"],1);
                    echo '<hr>';
                    echo '<div class="content_c">' . _("Reputation Priority") . ': '.$idm_data["rep_prio_src"].'</div>';
                    echo '<div class="content_c">' . _("Reputation Reliability") . ': '.$idm_data["rep_rel_src"].'</div>';
                    echo '<div class="content_c">' . _("Reputation Activity") . ': '.$idm_data["rep_act_src"].'</div>';
                    */
                    echo "<div class='siem_table_data'><table class='table_data'><thead><th>"._('Service')."</th><th>"._('Port')."</th><th>"._('Protocol')."</th></thead><tbody>";
                    if (is_array($src_host["service"]))
                    {
                        foreach ($src_host["service"] as $service)
                        {
                            $aux = explode('|',preg_replace("/,\s*$/",'',$service));
                            echo '<tr><td>'.$aux[2].'</td><td>'.$aux[1].'</td><td>'.strtoupper($aux[0]).'</td></tr>';
                        }
                    }
                    echo "</tbody></table></div>";
echo '          </div>
          </div>
          <div class="siem_detail_sep"></div>
          <div class="siem_detail_column_right">
                <div class="siem_detail_section">
                    <div class="siem_left">'._("Destination").'</div>
                    <div class="siem_right">
                        <div id="'.$current_dip.';'.$dip_aux.';'.$myrow2["dst_host"].'" ctx="'.$ctx.'" class="HostReportMenu siem_cell">' . $ip_dst_data . '</a></div>
                        <div class="siem_cell"></div>
                    </div>
                </div>
                <div class="siem_detail_content">';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Hostname") . ': '.$idm_data["dst_hostname"].'</div>
                             <div class="content_r">' . _("Location") . ': '.($dst_loc ? $dst_loc : $empty).'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("MAC Address") . ': '.$idm_data["dst_mac"].'</div>
                             <div class="content_r">' . _("Context") . ': '.($entities[$dst_host["ctx"]]!="" ? $entities[$dst_host["ctx"]] : (($dst_host["ctx"]=="") ? $empty : _("Unknown"))).'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Port") . ': '.$layer4_dport.'</div>
                             <div class="content_r">' . _("Asset Groups") . ': '.$dst_groups.'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Latest update") . ': '.($dst_host["date"] ? $dst_host["date"] : $empty).'</div>
                             <div class="content_r">' . _("Networks") . ': '.$dst_nets.'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Username & Domain") . ': '.$idm_data["dst_userdomains"].'</div>
                             <div class="content_r">' . _("Logged Users") . ': '.(($dst_host["username"]) ? preg_replace("/,\s*$/",'',str_replace("|",", ",implode("<br>",$dst_host["username"]))) : $empty).'</div>
                          </div>';
                    echo '<div class="content_c">
                             <div class="content_l">' . _("Asset Value") . ': '.$ossim_asset_dst.'</div>
                             <div class="content_r">' . _("OTX IP Reputation") . ': '.($repinfo_dst ? str_replace('__CLASS__','scriptinfoimg',str_replace('__TOOLTIP__',$reptooltip_dst,str_replace('__VALUE__', _('Yes'), $otx_link))) : _('No')).'</div>
                          </div>';
                    /*
                    $dst_img = getrepimg($idm_data["rep_prio_dst"],$idm_data["rep_rel_dst"],$idm_data["rep_act_dst"],$current_dip);
                    $dst_bgcolor = getrepbgcolor($idm_data["rep_prio_dst"],1);
                    echo '<hr>';
                    echo '<div class="content_c">' . _("Reputation Priority") . ': '.$idm_data["rep_prio_dst"].'</div>';
                    echo '<div class="content_c">' . _("Reputation Reliability") . ': '.$idm_data["rep_rel_dst"].'</div>';
                    echo '<div class="content_c">' . _("Reputation Activity") . ': '.$idm_data["rep_act_dst"].'</div>';
                    */
                    echo "<div class='siem_table_data'><table class='table_data'><thead><th>"._('Service')."</th><th>"._('Port')."</th><th>"._('Protocol')."</th></thead><tbody>";
                    if (is_array($dst_host["service"]))
                    {
                        foreach ($dst_host["service"] as $service)
                        {
                            $aux = explode('|',preg_replace("/,\s*$/",'',$service));
                            echo '<tr><td>'.$aux[2].'</td><td>'.$aux[1].'</td><td>'.strtoupper($aux[0]).'</td></tr>';
                        }
                    }
                    echo "</tbody></table></div>";

echo '          </div>
          </div>
      </div>';

// END COMMON DATA

/*
if (!$src_host && !$dst_host)
{
    if (Session::is_pro())
        echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Context").'</div>
              <div class="siem_detail_content">&nbsp;'._("Event Context information is not available").'. '.$idb->available().'</div>
              <DIV id="src_map" style="display:none"></DIV>
              <DIV id="dst_map" style="display:none"></DIV>
            </div>';
    else
        echo '<br><div class="siem_detail_table">
              <div class="siem_detail_section">'._("Context").'</div>
              <div class="siem_detail_content">&nbsp;'._("Event Context information is only available in AlienVault USM Server").'</div>
              <DIV id="src_map" style="display:none"></DIV>
              <DIV id="dst_map" style="display:none"></DIV>
            </div>';
}
*/

/* USERDATA */

$extradata1 = array();
$extradata2 = array();
if ($filename!=$empty) $extradata1["filename"] = $filename;
if ($username!=$empty) $extradata1["username"] = $username;
if ($password!=$empty) $extradata1["password"] = $password;
if ($userdata1!=$empty) $extradata1["userdata1"] = $userdata1;
if ($userdata2!=$empty) $extradata1["userdata2"] = $userdata2;
if ($userdata3!=$empty) $extradata1["userdata3"] = $userdata3;

if ($userdata4!=$empty)
{
    if (count($extradata1)<6) $extradata1["userdata4"] = $userdata4;
    else                      $extradata2["userdata4"] = $userdata4;
}
if ($userdata5!=$empty)
{
    if (count($extradata1)<6) $extradata1["userdata5"] = $userdata5;
    else                      $extradata2["userdata5"] = $userdata5;
}
if ($userdata6!=$empty)
{
    if (count($extradata1)<6) $extradata1["userdata6"] = $userdata6;
    else                      $extradata2["userdata6"] = $userdata6;
}
if ($userdata7!=$empty)
{
    if (count($extradata1)<6) $extradata1["userdata7"] = $userdata7;
    else                      $extradata2["userdata7"] = $userdata7;
}
if ($userdata8!=$empty)
{
    if (count($extradata1)<6) $extradata1["userdata8"] = $userdata8;
    else                      $extradata2["userdata8"] = $userdata8;
}
if ($userdata9!=$empty)
{
    if (count($extradata1)<6) $extradata1["userdata9"] = $userdata9;
    else                      $extradata2["userdata9"] = $userdata9;
}

if (!$is_snort && !empty($extradata1))
{

    if ($plugin_id == 5004)
    { // Anomalies => Show userdata1 and 2 like last and new values
        $lv = preg_replace("/(.*)\|(.*)\|(.*)/","\\3 (\\2/\\1)",$userdata1);
        $nv = preg_replace("/(.*)\|(.*)\|(.*)/","\\3 (\\2/\\1)",$userdata2);
        echo '<TABLE class="siem_table">
                <TR>
                    <th class="autow">'._("Last value").'</th>
                    <th class="autow">'._("New value").'</th>
                </TR>
                <TR>
                   <TD class="center">' . Util::htmlentities($lv) . '</TD>
                   <TD class="center">' . Util::htmlentities($nv) . '</TD>
                </TR>
              </TABLE>';
    }
    else
    {
        echo '<TABLE class="siem_table"><TR>';
        foreach ($extradata1 as $k => $v) echo '<th class="autow">'._($k).'</th>';
        echo '</TR><TR>';
        foreach ($extradata1 as $k => $v) echo '<TD class="center">'.Util::htmlentities($v).'</TD>';
        echo '</TR>';
        if (!empty($extradata2))
        {
            echo '<TR>';
            foreach ($extradata2 as $k => $v) echo '<th class="autow">'._($k).'</th>';
            echo '</TR><TR>';
            foreach ($extradata2 as $k => $v) echo '<TD class="center">'.Util::htmlentities($v).'</TD>';
            echo '</TR>';
        }
        echo '</TABLE>';
    }
}

if (!array_key_exists("minimal_view", $_GET))
{
    /* KDB SECTION */
    
    $vars['_SENSOR']            = $sensor_name;
    $vars['_SRCIP']             = $current_sip;
    $vars['_SRCMAC']            = $idm_data['src_mac'];
    $vars['_DSTIP']             = $current_dip;
    $vars['_DSTMAC']            = $idm_data['dst_mac'];
    $vars['_SRCPORT']           = (string)$layer4_sport;
    $vars['_DSTPORT']           = (string)$layer4_dport;
    $vars['_SRCCRITICALITY']    = (string)$idm_data['rep_prio_src'];
    $vars['_DSTCRITICALITY']    = (string)$idm_data['rep_prio_dst'];
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
    $vars['_ALARMRISKSCORE']    = 'N/A';
    $vars['_ALARMRELIABILITY']  = 'N/A';
    $vars['_HOST_NAME']         = 'N/A';
    $vars['_HOST_IP']           = 'N/A';
    $vars['_HOST_FQDN']         = 'N/A';
    $vars['_HOST_DESC']         = 'N/A';
    $vars['_NET_CIDR']          = 'N/A';
    $vars['_NET_NAME']          = 'N/A';
    $vars['_HG_NAME']           = 'N/A';
    $vars['_NG_NAME']           = 'N/A';
    $vars['_SRCREPACTIVITY']    = $idm_data['rep_act_src'];
    $vars['_DSTREPACTIVITY']    = $idm_data['rep_act_dst'];
    $vars['_SRCREPRELIABILITY'] = (string)$idm_data['rep_rel_src'];
    $vars['_DSTREPRELIABILITY'] = (string)$idm_data['rep_rel_dst'];
    
    $vars = array_map(function ($a) { return str_replace("<span style='color:gray'>"._("N/A")."</span>", '', $a); }, $vars );
    
    $_SESSION['_kdb_vars'] = $vars;
    $kdb_hide              = 'yes';
    
    require_once '../repository/repository_view.php';
    
}

/* PAYLOAD */

if ($is_snort)
{
    echo '<div class="siem_detail_table">
              <div class="siem_detail_section">'._("Payload");
    echo showShellcodeAnalysisLink($eid, $plugin_sid_name);
    echo '</div>';
}
else
{
    echo '<div class="siem_detail_table">
              <div class="siem_detail_section">'._("Raw Log").'</div>';

}

echo '       <div class="siem_detail_content siem_border">';
if ($payload)
{
    if ($binary) {
         $hex = bin2hex(Util::format_payload_extermnal($binary));
         PrintPacketPayload($hex, $encoding, 1, $plugin_id==$otx_plugin_id);
    } else {
         PrintPacketPayload($payload, $encoding, 1, ($plugin_id==$otx_plugin_id ? true : false));
    }
    if ($layer4_proto == "1")
    {
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
                $work = bin2hex(base64_decode(str_replace("\n", "", Util::htmlentities($payload))));
            } else {
                /* assuming that encoding is hex */
                $work = str_replace("\n", "", Util::htmlentities($payload));
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
            if ($ICMPitype == "5")
            {
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
            if ($ICMPitype == "5")
            {
                echo '<TD class="plfield">';
                echo '<A HREF="base_stat_ipaddr.php?ip=' . $gateway . '&amp;netmask=32" TARGET="_PL_SIP">' . $gateway . '</A></TD>';
                echo '<TD class="plfield">' . baseGetHostByAddr($gateway, $ctx, $db) . '</TD>';
            }
            echo '<TD class="plfield">'.Protocol::get_protocol_by_number($icmp_proto, TRUE).'</TD>';
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
}
else
{
    /* Don't have payload so lets print out why by checking the detail level */
    /* if have fast detail level */
    echo '<div class="siem_detail_dark">';
    if ($detail == "0") echo '<BR> &nbsp <I>' . _("Fast logging used -i so payload was discarded") . '</I><BR>';
    else echo '<div class="siem_detail_payloadnone">' . _("none") . '</div>';
    echo '</div>';
}

if ($is_snort)
{
    if ($plugin_id==1001)
    {
        //
        // snort rule detection
        //
        echo '<div><div class="siem_detail_snorttitle">'._("Rule Detection").'</div>';
        $result = Util::execute_command("grep -n ? /etc/suricata/rules/*.rules /etc/snort/rules/*.rules | head -n1", array("sid:$plugin_sid;"), 'string');

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

ExportHTTPVar("caller", $caller);
echo "</FORM>\n\n";


if (array_key_exists("minimal_view", $_GET))
{
   echo "</FORM>\n\n";
?>
    </div><br/><div class="center">
        <button class="button" id="view_more" data-url="<?php echo Menu::get_menu_url(AV_MAIN_PATH . "/forensics/base_qry_alert.php?noheader=true&pag=$pag&submit=" . rawurlencode($submit), 'analysis', 'security_events', 'security_events') ?>"><?php echo _('View More') ?></button>    </div><br/>
<?php
}
?>

    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>

    <script>
    $(document).ready(function(){
        <?php if (preg_match("/^#[0-9]+-([A-F0-9]+)/",$submit,$matches)) { ?>
            var id = '<?php echo $matches[1]; ?>';
            top.av_menu.set_bookmark_params(id);
        <?php } ?>
        $('.scriptinfoimg').tipTip({
            defaultPosition: "right",
            content: function (e) {
                return $(this).attr('txt')
            }
        });

        $('.tzoneimg').tipTip({
            defaultPosition: "down",
            maxWidth: '450px',
            content: function (e) {
                return $(this).attr('txt')
            }
        });

        $('.table_data').dataTable({
            "iDisplayLength": 5,
            "sPaginationType": "full_numbers",
            "bPaginate": true,
            "bLengthChange": false,
            "bFilter": false,
            "bSort": true,   
            "bInfo": true,   
            "bJQueryUI": true,
            "aaSorting": [[ 0, "asc" ]],
            "aoColumns": [
                { "bSortable": true },
                { "bSortable": true },
                { "bSortable": true }
            ],
            oLanguage : {
                "sProcessing": "Processing...",
                "sLengthMenu": "Show _MENU_ services",
                "sZeroRecords": "No matching services found",
                "sEmptyTable": "No services available",
                "sLoadingRecords": "Loading...",
                "sInfo": "Showing _START_ to _END_ of _TOTAL_ services",
                "sInfoEmpty": "Showing 0 to 0 of 0 services",
                "sInfoFiltered": "(filtered from _MAX_ total services)",
                "sInfoPostFix": "",
                "sInfoThousands": ",",
                "sSearch": "Search",
                "sUrl": "",
                "oPaginate": {
                    "sFirst":    "First",
                    "sPrevious": "Previous",
                    "sNext":     "Next",
                    "sLast":     "Last"
                }
            }
        });

        $("button[data-url], a[data-url]").on('click', function(){
            var params           = new Array();
            params['url_detail'] = $(this).data('url');
            parent.GB_hide(params);
        });

        $('#actions_link_detail').on('click',function(event)
        {
            event.stopPropagation();
            var diff = ($.browser.webkit && !(/chrome/.test(navigator.userAgent.toLowerCase()))) ? -3 : 0;
            var vl = $('#actions_link_detail').offset();
            var ll = vl.left - $('#actions_dd').outerWidth(true) + $('#actions_link_detail').outerWidth(true) + diff;
            $('#actions_dd').css({position: 'absolute', left: ll}).toggle();
            return false;
        });
        
        <?php if (!$kdb_docs) { ?>
        $('#kdb_docs').addClass('disabled').attr('onclick','return false');
        <?php } ?>

    });

    var plid = '<?php echo intval($plugin_id) ?>';
    var psid = '<?php echo intval($plugin_sid) ?>';

    // Edit Directive
    function edit_directive()
    {
        GB_show('<?php echo _("Edit Event Properties") ?>','/forensics/modify_relprio.php?id='+plid+'&sid='+psid,280,450);
        $('#actions_dd').toggle();
    }

    // Events grouping button click
    function insert_into_dsgroup()
    {
        if (plid != "" && psid != "")
        {
            GB_show("<?php echo _("Insert into existing DS Group") ?>","/policy/insertsid.php?plugin_id="+plid+"&plugin_sid="+psid,'650','65%');
        }
        $('#actions_dd').toggle();
    }

    // Create new ticket
    function new_incident(url)
    {
        GB_show("<?php echo _('New Ticket') ?>", url, 550,'85%');
        $('#actions_dd').toggle();
    }

    // KDB
    function learn_more()
    {
        GB_show("<?php echo _('Knowledge Base') ?>", "/forensics/kdb.php?plugin_id="+plid+"&plugin_sid="+psid, '70%','80%');
        $('#actions_dd').toggle();
    }

    // Delete Event
    function delete_event()
    {
        if (confirm("<?php echo _('Are you sure you want to continue?') ?>"))
        {
            $('#alertaction').val('del_alert');
            $('#alertform').attr('METHOD','POST').attr('ACTION','base_qry_main.php');
            $('#sbutton').val('Delete Selected');
            $('#sbutton').click();
        }
        $('#actions_dd').toggle();
    }
    </script>
<?php
$qs->SaveState();
PrintBASESubFooter();
$et->Mark("Get Query Elements");
if (!array_key_exists("minimal_view", $_GET)) $et->PrintTiming();
echo "</body></html>";
?>
