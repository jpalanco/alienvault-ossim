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


require 'base_conf.php';
require 'vars_session.php';
require_once 'classes/Util.inc';
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");
require_once 'classes/geolocation.inc';

if(GET('fqdn') == 'yes' || GET('fqdn') == 'no')
{
    $_SESSION['siem_default_group'] = "base_stat_iplink.php?sort_order=events_d&fqdn=" . GET('fqdn');
}

if ($_REQUEST['sort_order']=='') $_GET['sort_order']='events_d';

$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$fqdn = ImportHTTPVar("fqdn", VAR_ALPHA | VAR_SPACE);
$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState('base_stat_iplink.php', '&fqdn='.$fqdn);
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_alerts, gettext("Most Frequent Events"), "occur_d");
$qs->AddCannedQuery("last_alerts", $last_num_ualerts, gettext("Last Events"), "last_d");
$qs->MoveView($submit); /* increment the view if necessary */

$page_title = gettext("IP Links");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$from = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE " . $criteria_clauses[1];
//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
//$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
//$qs->AddValidActionOp(gettext("Delete Selected"));
//$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->SetActionSQL($from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_IPLINK, $db);
$et->Mark("Alert Action");
/* Run the query to determine the number of rows (No LIMIT)*/
$qs->current_view = 0;
$qs->num_result_rows = UniqueLinkCnt($db, $criteria_clauses[0], " WHERE ".$criteria_clauses[1]);
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_iplink.php?fqdn=$fqdn&caller=$caller");
$qro->AddTitle(" ");
if ($fqdn=="yes") $qro->AddTitle(gettext("Source FQDN"));
$qro->AddTitle(gettext("Source IP"), "sip_a", "", " ORDER BY ip_src ASC", "sip_d", "", " ORDER BY ip_src DESC");
$qro->AddTitle(gettext("Direction"));
$qro->AddTitle(gettext("Destination IP"), "dip_a", "", " ORDER BY ip_dst ASC", "dip_d", "", " ORDER BY ip_dst DESC");
if ($fqdn=="yes") $qro->AddTitle(gettext("Destination FQDN"));
$qro->AddTitle(gettext("Protocol"), "proto_a", "", " ORDER BY ip_proto ASC", "proto_d", "", " ORDER BY ip_proto DESC");
$qro->AddTitle(gettext("Unique Dst Ports"), "dport_a", "", " ORDER BY clayer4 ASC", "dport_d", "", " ORDER BY clayer4 DESC");
$qro->AddTitle(gettext("Unique Events"), "sig_a", "", " ORDER BY csig ASC", "sig_d", "", " ORDER BY csig DESC");
$qro->AddTitle(gettext("Total Events"), "events_a", "", " ORDER BY ccid ASC", "events_d", "", " ORDER BY ccid DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
$sql = "SELECT SQL_CALC_FOUND_ROWS acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto, hex(acid_event.ctx) as ctx, COUNT(DISTINCT acid_event.layer4_dport) as clayer4, COUNT(acid_event.id) as ccid, COUNT(DISTINCT acid_event.plugin_id, acid_event.plugin_sid) csig, HEX(acid_event.src_host) AS src_host, HEX(acid_event.dst_host) AS dst_host " . $sort_sql[0] . $from . $where . " GROUP by ip_src, ip_dst, ip_proto " . $sort_sql[1] ;
#$sql = "SELECT DISTINCT acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto " . $sort_sql[0] . $from . $where . $sort_sql[1];
/* Run the Query again for the actual data (with the LIMIT) */
$qs->current_view = $submit;
//echo "<br>$sql<br>\n";
session_write_close();
$result = $qs->ExecuteOutputQuery($sql, $db);
$qs->GetCalcFoundRows('', $result->baseRecordCount(), $db);

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$displaying = gettext("Displaying unique ip links %d-%d of <b>%s</b> matching your selection.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_iplink.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;
$report_data = array(); // data to fill report_data

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();

while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $sip = $myrow[0]; $ip_sip = inet_ntop($sip);
    $dip = $myrow[1]; $ip_dip = inet_ntop($dip);
    $src_host = $myrow[7];
    $dst_host = $myrow[8];
    $proto = $myrow[2];
    $ctx = $myrow[3];
    if ($fqdn=="yes") {
		$sip_fqdn = baseGetHostByAddr($ip_sip, $ctx, $db);
		$dip_fqdn = baseGetHostByAddr($ip_dip, $ctx, $db);
	}
    /* Get stats on the link */
    if ($sip && $dip) {
        #$temp = "SELECT COUNT(DISTINCT layer4_dport), " . "COUNT(acid_event.cid), COUNT(DISTINCT acid_event.signature)  " . $from . $where . " AND acid_event.ip_src='" . $sip . "' AND acid_event.ip_dst='" . $dip . "' AND acid_event.ip_proto='" . $proto . "'";
        #$result2 = $db->baseExecute($temp);
        #$row = $result2->baseFetchRow();
        #$num_occurances = $row[1];
        #$num_unique_dport = $row[0];
        #$num_unique = $row[2];
        #$result2->baseFreeRows();
        $num_unique_dport = $myrow[4];
        $num_occurances = $myrow[5];
        $num_unique = $myrow[6];
        /* Print out */
        qroPrintEntryHeader($i);
        $tmp_ip_criteria = '&amp;ip_addr%5B0%5D%5B0%5D=+&amp;ip_addr%5B0%5D%5B1%5D=ip_src&amp;ip_addr%5B0%5D%5B2%5D=%3D' . '&amp;ip_addr%5B0%5D%5B3%5D=' . $ip_sip . '&amp;ip_addr%5B0%5D%5B8%5D=+&amp;ip_addr%5B0%5D%5B9%5D=AND' . '&amp;ip_addr%5B1%5D%5B0%5D=+&amp;ip_addr%5B1%5D%5B1%5D=ip_dst&amp;ip_addr%5B1%5D%5B2%5D=%3D' . '&amp;ip_addr%5B1%5D%5B3%5D=' . $ip_dip . '&amp;ip_addr%5B1%5D%5B8%5D=+&amp;ip_addr%5B1%5D%5B9%5D=+' . '&amp;ip_addr_cnt=2';
        $tmp_rowid = $sip . "_" . $dip . "_" . $proto;
        //echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
        //echo '        <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
        echo "<td></td>";

        $geo_info = Asset_host::get_extended_location($_conn, $geoloc, $ip_sip);
        if ($geo_info['html_icon'] != '')
        {
            $s_country_img = $geo_info['html_icon'].' ';
            $slnk          = $current_url.preg_replace("/.*src\='\/ossim([^']+)'.*/", "\\1", $s_country_img);
        }
        else
        {
            $s_country_img = "";
            $slnk          = "";
        }

        $div1 = '<div id="'.$ip_sip.';'.$ip_sip.';'.$src_host.'" ctx="'.$ctx.'" class="HostReportMenu">'; $bdiv1 = '</div>';

        $geo_info = Asset_host::get_extended_location($_conn, $geoloc, $ip_dip);
        if ($geo_info['html_icon'] != "")
        {
            $d_country_img = $geo_info['html_icon']." ";
            $dlnk          = $current_url.preg_replace("/.*src\='\/ossim([^']+)'.*/", "\\1", $d_country_img);
        }
        else
        {
            $d_country_img = "";
            $dlnk          = "";
        }

		$div2 = '<div id="'.$ip_dip.';'.$ip_dip.';'.$dst_host.'" ctx="'.$ctx.'" class="HostReportMenu">'; $bdiv2 = '</div>';
        if ($fqdn=="yes") qroPrintEntry('<FONT>' . $sip_fqdn . '</FONT>');
        qroPrintEntry($div1 . $s_country_img . BuildAddressLink($ip_sip , 32) . $ip_sip . '</A>'. $bdiv1, "", "", "nowrap");
        qroPrintEntry('<img src="images/dash.png" border="0">');
        qroPrintEntry($div2 . $d_country_img . BuildAddressLink($ip_dip , 32) . $ip_dip . '</A>' . $bdiv2, "", "", "nowrap");
        if ($fqdn == "yes")
        {
            qroPrintEntry('<FONT>' . $dip_fqdn . '</FONT>');
        }

        $p_name = Protocol::get_protocol_by_number($proto, TRUE);
        if (FALSE === $p_name)
        {
            $p_name = _('UNKNOWN');
        }
        qroPrintEntry('<FONT>'.$p_name.'</FONT>');

        $tmp = '<A HREF="base_stat_ports.php?port_type=2&amp;proto=' . $proto . $tmp_ip_criteria . '">';
        qroPrintEntry($tmp . Util::number_format_locale($num_unique_dport,0) . '</A>');
        $tmp = '<A HREF="base_stat_alerts.php?foo=1' . $tmp_ip_criteria . '">';
        qroPrintEntry($tmp . Util::number_format_locale($num_unique,0) . '</A>');
        $tmp = '<A HREF="base_qry_main.php?new=1' . '&amp;num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1' . $tmp_ip_criteria . '">';
        qroPrintEntry($tmp . Util::number_format_locale($num_occurances,0) . '</A>');
        qroPrintEntryFooter();
    }
    $i++;

    // report_data

    $p_name = Protocol::get_protocol_by_number($proto, TRUE);
    if (FALSE === $p_name)
    {
        $p_name = '';
    }

    $report_data[] = array (
        $ip_sip,
        '',
        $ip_dip,
        '',
        $p_name,
        "",
        "",
        "",
        "",
        "",
        "",
        $num_unique_dport,
        $num_unique,
        $num_occurances,
        ($s_country_img!=''||$d_country_img!='') ? $s_country_img."####".$d_country_img : ''
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_iplinks_report_type);
$qs->SaveState();
echo "<input type='hidden' name='fqdn' value='".Util::htmlentities($fqdn)."'>\n";
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
$db->baseClose();
echo "</body>\r\n</html>";
?>
