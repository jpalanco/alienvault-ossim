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

if (!preg_match("/\d+\.\d+\.\d+\.\d+/",$_REQUEST['ip']))
{
    header("Location: base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d");
}
$start = time();
$sig = array();
require ("base_conf.php");
require ("vars_session.php");
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_common.php");

function PrintPortscanEvents($db, $ip) {
    GLOBAL $portscan_file;
    if (!$portscan_file) {
        ErrorMessage(gettext("PORTSCAN EVENT ERROR: ") . gettext("No file was specified in the $portscan_file variable."));
        return;
    }
    $fp = fopen($portscan_file, "r");
    if (!$fp) {
        ErrorMessage(gettext("PORTSCAN EVENT ERROR: ") . gettext("Unable to open Portscan event file") . " '" . $portscan_file . "'");
        return;
    }
    echo '<TABLE border="0" width="100%" cellspacing="0" cellpadding="5" class="table_list">
        <TR>
           <TD CLASS="headerbasestat">' . gettext("Date/Time") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Source IP") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Source Port") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Destination IP") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Destination Port") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("TCP Flags") . '</TD>
        </TR>';
    $total = 0;
    // Patch regex DoS possible vuln
    $ip = Util::regex($ip);
    while (!feof($fp)) {
        $contents = fgets($fp, 255);
        if (preg_match($ip, $contents)) {
            $total++;
            if ($i % 2 == 0) {
                $color = "DDDDDD";
            } else {
                $color = "FFFFFF";
            }
            $contents = preg_replace("/\s\s/", " ", $contents);
            $elements = explode(" ", $contents);
            echo '<tr bgcolor="' . $color . '"><td align="center">' . $elements[0] . ' ' . $elements[1] . ' ' . $elements[2] . '</td>';
            preg_match("/([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*):([0-9]*)/", $elements[3], $store);
            echo '<td align="center">' . $store[1] . '</td>';
            echo '<td align="center">' . $store[2] . '</td>';
            preg_match("/([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*):([0-9]*)/", $elements[5], $store);
            echo '<td align="center">' . $store[1] . '</td>';
            echo '<td align="center">' . $store[2] . '</td>';
            echo '<td align="center">' . $elements[7] . '</td></tr>';
        }
    }
    fclose($fp);
    echo '<TR>
         <TD CLASS="headerbasestat" align="left">' . gettext("Total Hosts Scanned") . '</TD>
         <TD CLASS="headerbasestat">' . $total . '</TD>
         <TD CLASS="headerbasestat" colspan="4">&nbsp;</TD>
        </TR>
        </TABLE>';
}
function PrintEventsByIP($db, $ip) {
    $ip = Util::htmlentities($ip);
    GLOBAL $debug_mode;
    $count = 0;
    /* Jeffs stuff */
    /* Count total events for the given address */
    $event_cnt = EventCntByAddr($db, $ip);
    /* Grab unique alerts and count them */
    $unique_events = UniqueEventCntByAddr($db, $ip, $count);
    $unique_event_cnt = count($unique_events);
    printf("<B>" . gettext("%d unique events detected among %d events on %s") . "/32</B><BR>", $unique_event_cnt, $event_cnt, Util::htmlentities($ip));
    /* Print the Statistics on Each of the Unique Alerts */
    echo '<TABLE BORDER=0 class="table_list">
        <TR>
           <TD CLASS="headerbasestat">' . gettext("TCP Flags") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Total<BR> Occurrences") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Num of Sensors") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("First<BR> Occurrence") . '</TD>
           <TD CLASS="headerbasestat">' . gettext("Last<BR> Occurrence") . '</TD>
        </TR>';
    for ($i = 0; $i < $unique_event_cnt; $i++) {
        $current_event = $unique_events[$i];
        $total = UniqueEventTotalsByAddr($db, $ip, $current_event);
        $num_sensors = UniqueSensorCntByAddr($db, $ip, $current_event);
        $start_time = StartTimeForUniqueEventByAddr($db, $ip, $current_event);
        $stop_time = StopTimeForUniqueEventByAddr($db, $ip, $current_event);
        $cellcolor = ($i % 2 != 0) ? "bgcolor='#f2f2f2'" : "";
        /* Print out */
        echo "<TR $cellcolor>";
        // if ($debug_mode > 1) {
        // SQLTraceLog(__FILE__ . ":" . __LINE__ . ":" . __FUNCTION__ . ": Before BuildSigByID()");
        // }
        $signame = BuildSigByPlugin($unique_events[$i][0], $unique_events[$i][1], $db);
        echo "  <TD ALIGN='center'> " . str_replace("##","",html_entity_decode($signame));
        // if ($debug_mode > 1) {
        // SQLTraceLog(__FILE__ . ":" . __LINE__ . ":" . __FUNCTION__ . ": After BuildSigByID()");
        // }
        $tmp_iplookup = 'base_qry_main.php?new=1&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=' . urlencode($unique_events[$i][0].";".$unique_events[$i][1]) . '&num_result_rows=-1&submit=' . gettext("Query DB") . '&current_view=-1&ip_addr_cnt=2' . BuildIPFormVars(urlencode($ip));
        $tmp_sensor_lookup = 'base_stat_sensor.php?sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=' . urlencode($unique_events[$i][0].";".$unique_events[$i][1]) . '&ip_addr_cnt=2' . BuildIPFormVars(urlencode($ip));
        echo "  <TD align='center'> <A HREF=\"$tmp_iplookup\">".Util::htmlentities($total)."</A> ";
        echo "  <TD align='center'> <A HREF=\"$tmp_sensor_lookup\">".Util::htmlentities($num_sensors)."</A> ";
        //echo "  <TD align='center'> $num_sensors";
        echo "  <TD align='center'> $start_time";
        echo "  <TD align='center' valign='middle'> $stop_time";
        echo '</TR>';
    }
    echo "</TABLE>\n";
}

$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_stat_ipaddr.php");
$cs->ReadState();
$ip = ImportHTTPVar("ip", VAR_DIGIT | VAR_PERIOD);
$ip = Util::htmlentities($ip);
$netmask = ImportHTTPVar("netmask", VAR_DIGIT);
$action = ImportHTTPVar("action", VAR_ALPHA);
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE);
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
if ($netmask=='') $netmask = "32";
$page_title = $ip . '/' . $netmask;

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

// Include base_header.php
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
if (sizeof($sig) != 0 && strstr($sig[1], "spp_portscan")) $sig[1] = "";
/*  Build new link for criteria-based sensor page
*                    -- ALS <aschroll@mitre.org>
*/
$tmp_sensor_lookup = 'base_stat_sensor.php?ip_addr_cnt=2&m_opt=analysis&sm_opt=security_events&h_opt=security_events' . BuildIPFormVars($ip);
$tmp_srcdst_iplookup = 'base_qry_main.php?new=2&m_opt=analysis&sm_opt=security_events&h_opt=security_events' . '&amp;num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1&amp;ip_addr_cnt=2' . BuildIPFormVars($ip);
$tmp_src_iplookup = 'base_qry_main.php?new=2&m_opt=analysis&sm_opt=security_events&h_opt=security_events' . '&amp;num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1&amp;ip_addr_cnt=1' . BuildSrcIPFormVars($ip);
$tmp_dst_iplookup = 'base_qry_main.php?new=2&m_opt=analysis&sm_opt=security_events&h_opt=security_events' . '&amp;num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1&amp;ip_addr_cnt=1' . BuildDstIPFormVars($ip);
echo '<CENTER><BR>';
echo '<table border=0 cellpadding=0 cellspacing=0 class="table_list" style="width:90%">';
echo '<tr style="background-color:#F2F2F2;"><td align=\'right\' class="uppercase">';
printf("<FONT>" . gettext("all events with <b>%s/%s</b> as") . ":</FONT>", Util::htmlentities($ip), Util::htmlentities($netmask));
echo '</td>';
echo '<td align=\'left\' style=\'padding-left:15px;\'>
 <A target="main" class="flnk" HREF="' . $tmp_src_iplookup . '">' . gettext("Source") . '</A> | 
 <A target="main" class="flnk" HREF="' . $tmp_dst_iplookup . '">' . gettext("Destination") . '</A> | 
 <A target="main" class="flnk" HREF="' . $tmp_srcdst_iplookup . '">' . gettext("Source") . ' or ' . gettext("Destination") . '</A><BR></td></tr>';
 
echo '<tr><td align=\'right\' class="uppercase">';
echo gettext("show") . ':</td><td align=\'left\' style=\'padding-left:15px;\'>
       <A target="main" class="flnk" HREF="base_stat_alerts.php?ip_addr_cnt=2&m_opt=analysis&sm_opt=security_events&h_opt=security_events' . BuildIPFormVars($ip) . '">' . gettext("Unique Events") . '</A>
       <BR></td></tr>';

echo "<tr style=\"background-color:#F2F2F2;\"><td style=\"text-align:right;\" class='uppercase'>";
echo '<FONT>' . gettext("Registry lookup (whois) in") . ': </td><td align=\'left\' style=\'padding-left:15px;\'>';
echo '
       <A HREF="http://www.ripe.net/perl/whois?query=' . $ip . '" target="_NEW">RIPE</A> | 
       <A HREF="http://whois.arin.net/rest/nets;q=' . $ip . '?showDetails=true&showARIN=false&ext=netref2" target="_NEW">ARIN WHOIS-RWS</A> | 
       <A HREF="http://lacnic.net/cgi-bin/lacnic/whois?lg=EN&query=' . $ip . '" target="_NEW">LACNIC</A><BR></FONT></td></tr>';
$octet = preg_split("/\./", $ip);
$classc = sprintf("%03s.%03s.%03s", $octet[0], $octet[1], $octet[2]);

echo '<tr><td align=\'right\' class="uppercase"><FONT>' . gettext("External") . ': </td><td align=\'left\' style=\'padding-left:15px;\'>' . '<!-- <A HREF="' . $external_dns_link . $ip . '" target="_NEW">DNS</A>';
echo ' | <A HREF="' . $external_whois_link . $ip . '" target="_NEW">whois</A> | --> ' . '<A HREF="' . $external_all_link . $ip . '" target="_NEW">Extended whois</A>';
echo ' | <A HREF="http://www.dshield.org/ipinfo.php?ip=' . $ip . '&amp;Submit=Submit" target="_NEW">DShield.org IP Info</A>';
echo ' | <A HREF="http://www.trustedsource.org/query.php?q=' . $ip . '" target="_NEW">TrustedSource.org IP Info</A>';
echo ' | <A HREF="http://www.projecthoneypot.org/ip_' . $ip . '" target="_NEW">Project Honey Pot</A>';
echo ' | <A HREF="http://www.spamhaus.org/query/bl?ip=' . $ip . '" target="_NEW">Spamhaus.org IP Info</A>';
echo ' | <A HREF="http://www.spamcop.net/w3m?action=checkblock&ip=' . $ip . '" target="_NEW">Spamcop.net IP Info</A>';
echo ' | <A HREF="http://www.senderbase.org/senderbase_queries/detailip?search_string=' . $ip . '" target="_NEW">Senderbase.org IP Info</A>';
echo ' | <A HREF="http://isc.sans.org/ipinfo.html?ip=' . $ip . '" target="_NEW">ISC Source/Subnet Report</A>';
echo ' | <A HREF="http://www.mywot.com/en/scorecard/' . $ip . '" target="_NEW">WOT Security Scorecard</A>';
echo ' | <A HREF="http://www.malwareurl.com/ns_listing.php?ip=' . $ip . '" target="_NEW">MalwareURL</A>';
echo ' | <A HREF="http://www.google.com/search?q=' . $ip . '" target="_NEW">Google</A>';
echo '<BR> </FONT></td></tr></table>';
?>
</CENTER>
<BR>
<script>
    var paramns       = new Array();
    paramns['nostop'] = 1;
    $(document).ready(function(){
        $('.flnk').on('click', function(){
            setTimeout('parent.GB_hide(paramns)', 250);
        });
    });
</script>
<FORM METHOD="POST" ACTION="base_stat_ipaddr.php">

<?php
/* Print the Statistics the IP address */
echo ' <p align="CENTER">FQDN: <B>';
if ($resolve_IP == 0) echo '  (' . gettext("no DNS resolution attempted") . ')';
else {
    if ($ip != "255.255.255.255") echo baseGetHostByAddr(Util::htmlentities($ip), '', $db);
    else echo Util::htmlentities($ip) . ' (Broadcast)';
}
//if (VerifySocketSupport()) echo '&nbsp;&nbsp;( <A HREF="base_stat_ipaddr.php?ip=' . $ip . '&amp;netmask=' . $netmask . '&amp;action=whois">local whois</A> )';
echo '</B></p>
        <TABLE BORDER=0 class="table_list" style="width:90%">
        <TR>
           <TD CLASS="headerbasestat uppercase">' . gettext("Devices #") . '</TD>
           <TD CLASS="headerbasestat uppercase">' . gettext("Src Occurances #") . '</TD>
           <TD CLASS="headerbasestat uppercase">' . gettext("Dst Occurances #") . '</TD>
           <TD CLASS="headerbasestat uppercase">' . gettext("First Event Date") . '</TD>
           <TD CLASS="headerbasestat uppercase">' . gettext("Last Event Date") . '</TD>
        </TR>';
        
/* Number of Sensors, First, and Last timestamp */
$temp = "SELECT COUNT(DISTINCT device_id), MIN(timestamp), MAX(timestamp) FROM po_acid_event WHERE ip_src = inet6_aton('$ip') OR ip_dst = inet6_aton('$ip')";
$result2 = $db->baseExecute($temp);
$row2 = $result2->baseFetchRow();
$num_sensors = $row2[0];
$start_time = ($row2[1]) ? str_replace(':00:00','H',$row2[1]) : '-';
$stop_time = ($row2[2]) ? str_replace(':00:00','H',$row2[2]) : '-';
$result2->baseFreeRows();

/* Unique instances as Source Address  */
$temp = "SELECT ifnull(SUM(cnt),0) from po_acid_event WHERE ip_src=inet6_aton('$ip')";
$result2 = $db->baseExecute($temp);
$row2 = $result2->baseFetchRow();
$num_src_ip = $row2[0];
$result2->baseFreeRows();

/* Unique instances Dest. Address  */
$temp = "SELECT ifnull(SUM(cnt),0) from po_acid_event WHERE ip_dst=inet6_aton('$ip')";
$result2 = $db->baseExecute($temp);
$row2 = $result2->baseFetchRow();
$num_dst_ip = $row2[0];
$result2->baseFreeRows();
/* Print out */
echo '<TR>';
if ($num_sensors == 0) echo '<TD ALIGN="center" bgcolor="#F2F2F2">' . $num_sensors . '</TD>';
else                   echo '<TD ALIGN="center" bgcolor="#F2F2F2"><A target="main" class="flnk" HREF="' . $tmp_sensor_lookup . '">' . $num_sensors . '</A></TD>';
if ($num_src_ip == 0)  echo '<TD ALIGN="center" bgcolor="#F2F2F2">' . $num_src_ip. '</TD>';
else                   echo '<TD ALIGN="center" bgcolor="#F2F2F2"><A target="main" class="flnk" HREF="' . $tmp_src_iplookup . '">' . $num_src_ip . '</A></TD>';
if ($num_dst_ip == 0)  echo '<TD ALIGN="center" bgcolor="#F2F2F2">' . $num_dst_ip. '</TD>';
else                   echo '<TD ALIGN="center" bgcolor="#F2F2F2"><A target="main" class="flnk" HREF="' . $tmp_dst_iplookup . '">' . $num_dst_ip . '</A></TD>';
echo '   <TD align="center" bgcolor="#F2F2F2">' . $start_time . '
         <TD align="center" bgcolor="#F2F2F2" valign="middle">' . $stop_time . '
       </TR>
      </TABLE></CENTER>';
if ($action == "events")
{
    echo '<BR>
            <CENTER><P>';
    PrintEventsByIP($db, $ip);
    echo ' </CENTER>';
}
else if ($action == "whois")
{
    echo "\n<B>" . gettext("Whois Information") . "</B>" . "<PRE>" . baseGetWhois($ip, $db, $whois_cache_lifetime) . "</PRE>";
}
else if ($action == "portscan")
{
    echo '<HR>
            <CENTER><P>';
    PrintPortscanEvents($db, $ip);
    echo ' </CENTER>';
}
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->PrintTiming();
$db->baseClose();
echo "</body>\r\n</html>";
?>
