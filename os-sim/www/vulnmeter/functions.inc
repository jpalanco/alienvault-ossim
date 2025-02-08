<?php
/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2013 AlienVault
 * All rights reserved.
 *
 * This package is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 dated June, 1991.
 * You may not use, modify or distribute this program under any other version
 * of the GNU General Public License.
 *
 * This package is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this package; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA  02110-1301  USA
 *
 *
 * On Debian GNU/Linux systems, the complete text of the GNU General
 * Public License can be found in `/usr/share/common-licenses/GPL-2'.
 *
 * Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */


/*
* Common functions that can be used by any PHP page.
* It keeps the code cleaner
*/

require_once 'av_init.php';
require_once 'ossim_sql.inc';

const SCAN_TIME_TYPE = "scantime";

function get_hosts($dbconn)
{
    $hosts = array();
    $query = "select distinct inet6_ntoa(hi.ip),hex(h.ctx) from host h,host_ip hi where h.id=hi.host_id";

    $dbconn->SetFetchMode(ADODB_FETCH_NUM);

    $result = $dbconn->Execute($query);
    if (!$result) {
        print $dbconn->ErrorMsg();
    } else {
        while (!$result->EOF) {
            $hosts[$result->fields[1]][$result->fields[0]] = 1;
            $result->MoveNext();
        }
    }
    return $hosts;
}


function hosts_to_insert($dbconn, $report_id, $hosts = array())
{
    $ips = array();

    $query = "SELECT distinct hostIP, HEX(ctx) AS ctx FROM vuln_nessus_results WHERE report_id=?";

    $dbconn->SetFetchMode(ADODB_FETCH_ASSOC);

    $result = $dbconn->Execute($query, array($report_id));
    if (!$result) {
        print $dbconn->ErrorMsg();
    } else {
        while (!$result->EOF) {
            $ip = $result->fields["hostIP"];
            $ctx = $result->fields["ctx"];

            if (!$hosts[$ctx][$ip]) {
                $net_id = key(Asset_host::get_closest_net($dbconn, $ip, $ctx));

                if (valid_hex32($net_id) && Session::netAllowed($dbconn, $net_id)) {
                    $ips[] = $ip . "#" . $ctx;
                }
            }

            $result->MoveNext();
        }
    }

    return $ips;
}


function tooltip_hosts($hosts, $ipsnames)
{
    $list = array();
    $result = array();
    $tmp_ipsnames = array();

    $out = "";

    $tmp_ipsnames = $ipsnames;
    
    $max_hosts_exceeded = FALSE;
    
    if (count($hosts) > 10 ){
        $max_hosts_exceeded = TRUE;
        $hosts = array_slice($hosts, 0, 10);
    }
    
    foreach ($hosts as $host) {
        $ohost = $host; // to display asset names with !
        $host = preg_replace('/^\!/', '', $host);
        $host = trim($host);

        if (($ipsnames[$host] != "") && ($host != $ipsnames[$host])) {
            if (preg_match("/\d+\.\d+\.\d+\.\d+/", $host)) {
                $result[] = $ohost . " (" . $ipsnames[$host] . ")";
            } else {
                $result[] = $ipsnames[$host] . " (" . $ohost . ")";
            }
        } else
            $result[] = $ohost;
        unset ($ipsnames);
        $ipsnames = $tmp_ipsnames;
    }
    $out = implode("<br>", $result);
    
    $out = ($max_hosts_exceeded == TRUE) ? $out."<br/>&nbsp;..." : $out;

    return $out;
}


function subjobs($name, $scan_submit, $data)
{
    $result = array();
    foreach ($data as $entry) {
        if ($entry['jobname'] == $name && $entry['scan_submit'] == $scan_submit && $entry['sid'] != 0 && $scan_submit != "") {
            $result[] = $entry;
        }
    }
    return $result;
}


function subjobslatest($ip, $ctx, $data)
{
    $result = array();
    foreach ($data as $entry) {
        if ($entry['hostIP'] == $ip && $entry['ctx'] == $ctx) {
            $result[] = $entry;
        }
    }
    return $result;
}


// sanitize the form input
function sanitize($input)
{
    $output = $tmp = "";
    if (get_magic_quotes_gpc()) {
        $tmp = stripslashes(trim($input));
    } else {
        $tmp = $input;
    }

    $db = new ossim_db();
    $conn = $db->connect();

    $output = escape_sql($tmp, $conn);

    $db->close();

    $output = Util::htmlentities($output);
    return $output;
}


function stats_severity_services($type, $filter = "", $graph = "severity")
{
    global $dbconn, $arruser, $user;

    $perms_where = Asset_host::get_perms_where('host.', TRUE);
    $from_count = "";
    $where_count = "";
    if (!empty($perms_where)) {
        $from_count = ", host, host_ip hi ";
        $where_count = " AND host.id=hi.host_id AND inet6_ntoa(hi.ip)=lr.hostIP $perms_where";
    }

    if ($graph != 'severity' && $graph != 'service') {
        $graph = 'severity';
    }

    $severity = array();
    $colors = array();

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    if ($type == 'hn') {
        if (preg_match('/\//', $filter)) {
            $ips = array_shift(array_values(Asset_net::expand_cidr($filter, 'SHORT', 'LONG')));
            $filter = " AND inet_aton(lr.hostIP) BETWEEN '" . $ips[0] . "'AND '" . $ips[1] . "'";
        } else if ($filter != '') {
            $filter = " AND lr.hostIP LIKE '$filter'";
        } else {
            $filter = "";
        }
    } else if ($type == 'service') {
        $filter = "AND lr.service LIKE '%$filter%'";

    } else if ($type == 'freetext') {
        $filter = "AND lr.msg LIKE '%$filter%'";
    }//If this types are received, it means the find option from reports is being using
    else if ($type == 'jobname' || $type == SCAN_TIME_TYPE || $type == "net") {
        $filter = "";
    }


    $query = "select hostIP, HEX(ctx) AS ctx, count(*) as total, risk
                        from (select distinct lr.port, lr.protocol, lr.app, lr.scriptid, lr.risk, lr.msg, lr.hostIP, lr.ctx
                        from vuln_nessus_latest_results lr $from_count
                        where falsepositive='N' $perms_where $where_count)
                        as t group by risk, hostIP, ctx";

    $result = $dbconn->Execute($query);

    while (!$result->EOF) {
        if ($result->fields["risk"] == 7) {
            $severity["Info"] += $result->fields["total"];
            if ($colors["#F0E68C"] == "") {
                $colors["#F0E68C"]++;
            }
        } else if ($result->fields["risk"] == 6) {
            $severity["Low"] += $result->fields["total"];
            if ($colors["#FFD700"] == "") {
                $colors["#FFD700"]++;
            }
        } else if ($result->fields["risk"] == 3) {
            $severity["Medium"] += $result->fields["total"];
            if ($colors["#FFA500"] == "") {
                $colors["#FFA500"]++;
            }
        } else if ($result->fields["risk"] == 2) {
            $severity["High"] += $result->fields["total"];
            if ($colors["#FF0000"] == "") {
                $colors["#FF0000"]++;
            }
        } else if ($result->fields["risk"] == 1) {
            $severity["Critical"] += $result->fields["total"];
            if ($colors["#C835ED"] == "") {
                $colors["#C835ED"]++;
            }
        }

        $result->MoveNext();
    }
    $lcolors = "\"" . implode("\",\"", array_keys($colors)) . "\"";

    $services = array();

    if (!empty($perms_where)) {
        $query = "SELECT count(lr.result_id) AS total, lr.port, lr.protocol, lr.app
                    FROM vuln_nessus_latest_results lr, host, host_ip hi
                    WHERE host.id=hi.host_id AND inet6_ntoa(hi.ip)=lr.hostIP $perms_where $filter AND lr.falsepositive='N'
                    GROUP BY lr.port, lr.protocol, lr.app HAVING lr.port>0 ORDER BY total";
    } else {
        $query = "SELECT count(lr.result_id) AS total, lr.port, lr.protocol, lr.app
                    FROM vuln_nessus_latest_results lr
                    WHERE 1=1 $filter AND lr.falsepositive='N' GROUP BY lr.port, lr.protocol, lr.app HAVING lr.port>0 ORDER BY total";
    }

    $result = $dbconn->Execute($query);

    $i = 0;
    while (!$result->EOF && $i < 10) {
        $services[$result->fields['app'] . " (" . $result->fields['protocol'] . "/" . $result->fields['port'] . ")"] = $result->fields['total'];
        $i++;
        $result->MoveNext();
    }
    ?>
    <style type='text/css'>
        .pieLabel div {
            font-size: 10px;
            border: 1px solid gray;
            background: #f2f2f2;
            padding: 1px;
            text-align: center;
        }

        .legendColorBox {
            border: 0 none;
        }

        .legendLabel {
            border: 0 none;
        }

        div.legend table {
            border: 0 none;
        }

        div.legend td {
            text-align: left;
            font-size: 10px
        }

        #services_pie, #severity_pie {
            width: 395px;
            height: 223px;
            text-align: left;
            padding: 15px 0px 0px 0px;
        }
    </style>

    <table cellpadding="0" cellspacing="0" style="width:100%;" height="268" align="center" class="transparent">
        <tr>
            <?php
            if ($graph == "severity") {
                ?>
                <td class="noborder" valign="top">
                    <table cellpadding="0" cellspacing="0" width="100%" align="center" height="250" class="transparent">
                        <tr>
                            <td>
                                <?php
                                if (count($severity) > 0) {
                                    ?>
                                    <div id="severity_pie"></div>
                                    <?php
                                } else {
                                    ?>
                                    <div style="width:100%;padding:0px 3px 0px 0px;"
                                         class="grey"><?php echo _("No results found") ?></div>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </td>
                <?php
            }

            if ($graph == "service") {
                ?>
                <td class="noborder" valign="top">
                    <table cellpadding="0" cellspacing="0" width="100%" align="center" height="250" class="transparent">
                        <tr>
                            <td class="noborder" align="center" id="hostcontainer">
                                <?php
                                if (count($services) > 0) {
                                    ?>
                                    <div id="services_pie"></div>
                                    <?php
                                } else {
                                    ?>
                                    <div style="width:100%;color:#17457C;"><?php echo _("No results found") ?></div>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </td>
                <?php
            }
            ?>
        </tr>
    </table>

    <script type='text/javascript'>
        $(function () {
            <?php
            if( count($severity) > 0 && $graph == "severity" ) {
            ?>
            $.plot($("#severity_pie"), [
                    <? $i = 0;foreach ($severity as $sev => $val) { ?>
                        <?=($i++ == 0) ? "" : ","?>{label: "<?=$sev . ' [' . $val . ']'?>", data: <?=$val?>}
                    <? } ?>
                ],
                {
                    pie: {
                        show: true,
                        pieStrokeLineWidth: 1,
                        pieStrokeColor: '#FFF',
                        pieChartRadius: 90, 			// by default it calculated by
                        //centerOffsetTop:30,
                        centerOffsetLeft: 80, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
                        showLabel: true,				//use ".pieLabel div" to format looks of labels
                        labelOffsetFactor: 5 / 6, 		// part of radius (default 5/6)
                        //labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
                        labelBackgroundOpacity: 0.55, 	// default is 0.85
                        labelFormatter: function (serie) {// default formatter is "serie.label"
                            //return serie.label;
                            //return serie.data;
                            //return serie.label+'<br/>'+Math.round(serie.percent)+'%';
                            return Math.round(serie.percent) + '%';
                        }
                    },
                    colors: [<?=$lcolors?>],
                    legend: {
                        show: true,
                        position: "ne",
                        backgroundOpacity: 0
                    }
                });
            <?php
            }
            if( count($services) > 0 && $graph == "service" ) {
            ?>
            $.plot($("#services_pie"), [
                    <?
                    $i = 0;foreach ($services as $service => $val) {
                    $ssearch = array();
                    $ssearch = explode(" ", $service);
                    ?>
                        <?=($i++ == 0) ? "" : ","?>{
                        label: "<a href='<?php echo Menu::get_menu_url('index.php?value=' . urlencode($ssearch[0]) . '&type=service', 'environment', 'vulnerabilities')?>'><?=$service . " [" . $val . "]"?>",
                        data: <?=$val?>}
                    <? } ?>
                ],
                {
                    pie: {
                        show: true,
                        pieStrokeLineWidth: 1,
                        pieStrokeColor: '#FFF',
                        pieChartRadius: 90, 			// by default it calculated by
                        //centerOffsetTop:30,
                        //centerOffsetLeft:30, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
                        showLabel: true,				//use ".pieLabel div" to format looks of labels
                        labelOffsetFactor: 5 / 6, 		// part of radius (default 5/6)
                        //labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
                        labelBackgroundOpacity: 0.55, 	// default is 0.85
                        labelFormatter: function (serie) {// default formatter is "serie.label"
                            //return serie.label;
                            //return serie.data;
                            //return serie.label+'<br/>'+Math.round(serie.percent)+'%';
                            return Math.round(serie.percent) + '%';
                        }
                    },
                    colors: ["#ACD7E6", "#79CCE9", "#99BBDB", "#6BB1E9", "#758AC9", "#4C74E9", "#4D56B5", "#2A33E8", "#3A39AA", "#2020E8"],
                    //colors: ["#ADD8E6","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#00008B"],
                    //colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
                    legend: {
                        show: true,
                        position: "ne",
                        backgroundOpacity: 0
                    }
                });
            <?php
            }?>
        });
    </script>

    <?
}


function stats_networks_hosts($type, $filter = "", $graph = "hosts")
{
    global $username, $uroles, $dbconn, $arruser, $user;

    $perms_where = Asset_host::get_perms_where('host.', TRUE);

    if ($graph != "hosts" && $graph != "networks") {
        $graph = "hosts";
    }

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    //In this case only the filter can be applied if the type of search is by host/net
    if ($filter != "" && preg_match('/\//', $filter) && $type == "hn") {
        $ips = array_shift(array_values(Asset_net::expand_cidr($filter, 'SHORT', 'LONG')));

        $filter = " AND inet_aton(lr.hostIP) BETWEEN '" . $ips[0] . "' AND '" . $ips[1] . "'";
    } elseif ($type == "hn") {
        if (preg_match("/\,/", $filter)) {
            $q = implode("','", explode(",", $filter));
            $filter = " AND lr.hostIP IN ('$q')";
        } else if ($filter != "") {
            $filter = " AND lr.hostIP LIKE '$filter'";
        } else {
            $filter = "";
        }
    }

    $queryf = "";

    // top 10 hosts and networks
    $top10h = $top10n = $nips = $others = array();

    if ($type == "hn" && $filter != "") {
        $queryf = $filter;
    } else if ($type == "service") {
        $queryf = " and lr.service like '%$filter%'";
    } else if ($type == "freetext") {
        $queryf = " and lr.msg like '%$filter%'";
    }

    if (!empty($perms_where)) {
        $query = "SELECT count(*) as vulnerability, vulns_list.hostip, vulns_list.ctx as ctx FROM
                  (SELECT DISTINCT HEX(lr.ctx) as ctx, lr.hostIP, lr.app, lr.service, lr.port, lr.protocol, lr.scriptid, lr.risk, lr.msg FROM
                   vuln_nessus_latest_results lr, host, host_ip hi
                   WHERE host.id=hi.host_id AND inet6_ntoa(hi.ip)=lr.hostIP $perms_where $queryf AND falsepositive='N') as vulns_list
                   GROUP BY hostip ORDER BY vulnerability DESC LIMIT 1000";
    } else {
        $query = "SELECT count(*) as vulnerability, vulns_list.hostip, vulns_list.ctx as ctx FROM
                  (SELECT DISTINCT HEX(lr.ctx) as ctx, lr.hostIP, lr.app, lr.service, lr.port, lr.protocol, lr.scriptid, lr.risk, lr.msg FROM
                   vuln_nessus_latest_results lr
                   WHERE 1=1 $queryf AND falsepositive='N') as vulns_list GROUP BY hostip ORDER BY vulnerability DESC LIMIT 1000";
    }

    $result = $dbconn->Execute($query);

    while (!$result->EOF) {
        $ip = $result->fields["hostip"];
        $ctx = $result->fields["ctx"];

        $id = key(Asset_host::get_id_by_ips($dbconn, $ip, $ctx));

        $hkey = '';

        if (valid_hex32($id)) {
            $hkey = $id;
        } else {
            $hkey = "$ctx#$ip";
        }

        if (!isset($top10h[$hkey])) {
            $top10h[$hkey] += $result->fields["vulnerability"];

            $net = Asset_host::get_closest_net($dbconn, $ip, $ctx);
            $net_id = key($net);

            if (valid_hex32($net_id)) {
                $netkey = $net_id;
                $ips = $net[$net_id]['ips'];

                $nips[$netkey] = $ips;
                $top10n[$netkey] += $result->fields["vulnerability"];
            } else if (trim($ip) != '' && trim($ctx) != '') {
                $others["$ctx#$ip"] = trim($ip);
                $top10n["Others"] += $result->fields["vulnerability"];
            }
        }

        $result->MoveNext();
    }

    arsort($top10h);
    arsort($top10n);
    $maxn = 0;
    $maxh = 0;
    foreach ($top10h as $key => $value) {
        $maxh += $value;
    }

    foreach ($top10n as $key => $value) {
        $maxn += $value;
    }
    ?>
    <table cellpadding=0 cellspacing=0 width="100%" class="transparent">
        <tr>
            <?php
            $empty_message = "<tr><td class='nobborder' height='200' style='text-align:center'><div style='padding-top:48px;width:100%;' class='grey'>" . _("No results found") . "</div></td></tr>";

            if ($graph == "networks") { ?>
                <td class="noborder" valign="top">
                    <table cellpadding="0" cellspacing="0" width="100%" height="220" class="transparent">
                        <tr>
                            <td valign="top" class="nobborder" style="padding-bottom:6px;">
                                <table cellpadding="0" cellspacing="0" width="100%" class="transparent">
                                    <? $i = 0;
                                    foreach ($top10n as $net_id => $val) {
                                        if ($i < 10 && $net_id != '') {
                                            $i++;
                                            $fnets = 1;
                                            if (preg_match("/^[a-f\d]{32}$/i", $net_id)) {
                                                $net_name = Asset_net::get_name_by_id($dbconn, $net_id);
                                            } else {
                                                $net_name = $net_id;
                                            }
                                            ?>
                                            <tr>
                                                <td class="noborder" style="text-align:right;padding:5px 5px 0px 5px;"
                                                    valign="top" width="50%">
                                                    <?php
                                                    $link_text = ($nips[$net_id] != "" && $nips[$net_id] != $net_name) ? $net_name : $nips[$net_id];
                                                    if ($link_text == "") {
                                                        $link_text = "Others";
                                                        $nips[$net_id] = "";
                                                    }
                                                    $link_text .= " [$val]";
                                                    ?>
                                                    <a <?php echo ($nips[$net_id] != "" && $nips[$net_id] != $net_name) ? " class='tip' title='" . $nips[$net_id] . "'" : "" ?>
                                                            style='font-size:10px;'
                                                            href="<?php echo Menu::get_menu_url('index.php?value=' . (($nips[$net_id] != "") ? $nips[$net_id] : implode(",", $others)) . '&type=hn', 'environment', 'vulnerabilities') ?>"><?php echo $link_text ?></a>
                                                </td>
                                                <td class="noborder" width="50%" valign="top"
                                                    style="text-align:left;padding:5px 5px 0px 5px;">
                                                    <?
                                                    $width = intval(($val * 205) / $maxn);
                                                    echo "<div style=\"border: 1px solid rgb(204, 204, 204); padding: 1px;\"><img src=\"../pixmaps/risk_blue.jpg\" width=\"" . $width . "px\" height=\"15\" /></div>";
                                                    ?>
                                                </td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    if ($fnets != 1) {
                                        echo $empty_message;
                                    }
                                    ?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
                <?php
            }
            if ($graph == "hosts") {
                ?>
                <td class="noborder">
                    <table cellpadding=0 cellspacing=0 width="100%" height="200" class="transparent">
                        <tr>
                            <td valign="top" class="nobborder" style="padding-bottom:6px;">
                                <table cellpadding=0 cellspacing=0 width="100%" class="transparent">
                                    <? $i = 0;
                                    foreach ($top10h as $host => $val) {
                                        $name = "";

                                        if (preg_match("/^[\da-f]{32}$/i", $host)) {
                                            // Fill host name
                                            $name = Asset_host::get_name_by_id($dbconn, $host);
                                            $ctx = Asset_host::get_ctx_by_id($dbconn, $host);
                                            $hip = Asset_host_ips::get_ips_to_string($dbconn, $host);
                                        } else if (preg_match("/[\da-f]{32}#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i", $host)) {
                                            // Fill host name
                                            list($ctx, $hip) = explode("#", $host);
                                            $name = $hip;
                                        }

                                        if ($i < 10) {
                                            $fhosts = 1;
                                            $i++;

                                            ?>
                                            <tr>
                                                <td class="noborder" style="text-align:right;padding:5px 5px 0px 5px;"
                                                    valign="top" width="50%">
                                                    <?php
                                                    $link_text = ($hip != "" && $hip != $name) ? $name : $hip;
                                                    $link_text .= " [$val]";
                                                    ?>
                                                    <a <?php echo ($hip != "" && $hip != $name) ? " class='tip' title='" . $hip . "'" : "" ?>
                                                            style='font-size:10px;'
                                                            id='<?php echo (preg_match("/^[\da-f]{32}$/i", $host) ? $host : $hip) . ";" . $name . ";" . ((preg_match("/^[\da-f]{32}$/i", $host)) ? $host : ''); ?>'
                                                            ctx='<?php echo $ctx; ?>' class='HostReportMenu'
                                                            href="<?php echo Menu::get_menu_url('index.php?value=' . $hip . '&type=hn', 'environment', 'vulnerabilities') ?>"><?php echo $link_text; ?></a>
                                                </td>
                                                <td class="noborder" width="50%" valign="top"
                                                    style="text-align:left;padding:5px 5px 0px 5px;">
                                                    <?
                                                    $width = intval(($val * 210) / $maxh);
                                                    echo "<div style='border: 1px solid rgb(204, 204, 204); padding: 1px;'><img src='../pixmaps/risk_light_blue.jpg' width='" . $width . "px' height='15'/></div>";
                                                    ?>
                                                </td>
                                            </tr>
                                        <?
                                        }
                                    }
                                    if ($fhosts != 1) {
                                        echo $empty_message;
                                    }
                                    ?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
                <?php
            }
            ?>
        </tr>
    </table>
    <?
}


function stats()
{
    global $username, $uroles, $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    // top 10 hosts and networks
    $top10h = $top10n = $nips = array();
    $query = "select count(*) as vulnerability,hostip from (select distinct hostip,port,protocol,app,scriptid,msg,risk from vuln_nessus_latest_results) as t group by hostip";
    $result = $dbconn->Execute($query);

    while (!$result->EOF) {
        $ip = $result->fields["hostip"];

        if (!isset($top10h[$ip])) {
            $top10h[$ip] += $result->fields["vulnerability"];

            $net = Asset_host::get_closest_net($dbconn, $ip);
            $net_id = key($net);

            if (valid_hex32($net_id)) {
                $ips = $net[$net_id]['ips'];
                $net = "$ips [$net]";
                $nips[$net] = $ips;
                $top10n[$net] += $result->fields["vulnerability"];
            } else {
                $top10n["Others"] += $result->fields["vulnerability"];
            }
        }
        $result->MoveNext();
    }

    arsort($top10h);
    arsort($top10n);
    ?>
    <style>
        .pieLabel div {
            font-size: 10px;
            border: 1px solid gray;
            background: #f2f2f2;
            padding: 1px;
            text-align: center;
        }

        .legendColorBox {
            border: 0 none;
        }

        .legendLabel {
            border: 0 none;
        }

        div.legend table {
            border: 0 none;
        }

        div.legend td {
            text-align: left;
            font-size: 11px
        }
    </style>

    <table cellpadding=0 cellspacing=0 width="900" align="center" class="noborder">
        <tr>
            <td class="headerpr" width="49%"><?= _("Top 10 Networks"); ?></td>
            <td width="15" class="noborder"></td>
            <td class="headerpr" width="49%"><?= _("Top 10 Host"); ?></td>
        </tr>
        <tr>
            <td class="noborder">
                <table cellpadding=0 cellspacing=0 width="100%" align="center">
                    <tr>
                        <td class="noborder" style="padding:5px 5px 5px 30px" align="center">
                            <div id="nets_pie" style="width:395px;height:190px"></div>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="15" class="noborder"></td>
            <td class="noborder">
                <table cellpadding=0 cellspacing=0 width="100%" align="center">
                    <tr>
                        <td class="noborder" style="padding:5px 5px 5px 50px" align="center" id="hostcontainer">
                            <div id="hosts_pie" style="width:395px;height:190px;text-align:left;"></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        </tr></table>

    <script>
        $(function () {
            $.plot($("#nets_pie"), [
                    <? $i = 0;foreach ($top10n as $host => $val) if ($i < 10 && Session::netAllowed($dbconn, $nips[$host], $_SESSION['_user'])) { ?>
                        <?=($i++ == 0) ? "" : ","?>{
                        label: "<a href=\"<?php echo Menu::get_menu_url('index.php?value=' . urlencode($nips[$host]) . '&type=net', 'environment', 'vulnerabilities') ?>\"><?=(GET('value') == $nips[$host]) ? "<b>$host</b>" : $host?> [<?=$val?>]</a>",
                        data: <?=$val?>}
                    <? } ?>
                ],
                {
                    pie: {
                        show: true,
                        pieStrokeLineWidth: 1,
                        pieStrokeColor: '#FFF',
                        //pieChartRadius: 100, 			// by default it calculated by
                        //centerOffsetTop:30,
                        //centerOffsetLeft:30, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
                        showLabel: true,				//use ".pieLabel div" to format looks of labels
                        labelOffsetFactor: 5 / 6, 		// part of radius (default 5/6)
                        //labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
                        labelBackgroundOpacity: 0.55, 	// default is 0.85
                        labelFormatter: function (serie) {// default formatter is "serie.label"
                            //return serie.label;
                            //return serie.data;
                            //return serie.label+'<br/>'+Math.round(serie.percent)+'%';
                            return Math.round(serie.percent) + '%';
                        }
                    },
                    colors: ["#ADD8E6", "#00BFFF", "#4169E1", "#4682B4", "#0000CD", "#483D8B", "#00008B"],
                    legend: {
                        show: true,
                        position: "ne",
                        backgroundOpacity: 0
                    }
                });
            //$("#nets_pie").bind("plothover", function (event, pos, item) {
            //	alert(item);
            //});
            $.plot($("#hosts_pie"), [
                    <?
                    $i = 0;foreach ($top10h as $host => $val) if ($i < 10 && Session::hostAllowed($dbconn, $host)) {
                    $query = "select hostname from host where ip='$host'";
                    $resname = $dbconn->Execute($query);
                    if (!$resname->EOF && $host != $resname->fields[0]) $name = $resname->fields[0];
                    else $name = "";
                    ?>
                        <?=($i++ == 0) ? "" : ","?>{
                        label: "<a target='main' href=\"<?php echo Menu::get_menu_url('index.php?value=' . urlencode($host) . '&type=hostip', 'environment', 'vulnerabilities') ?>\"><?=(GET('value') == $host) ? "<b>" : ""?><?=$name?><?=(($name != "") ? " (" : "")?><?=$host?><?=(($name != "") ? ")" : "")?><?=(GET('value') == $host) ? "</b>" : ""?> [<?=$val?>]</a>",
                        data: <?=$val?>}
                    <? } ?>
                ],
                {
                    pie: {
                        show: true,
                        pieStrokeLineWidth: 1,
                        pieStrokeColor: '#FFF',
                        //pieChartRadius: 100, 			// by default it calculated by
                        //centerOffsetTop:30,
                        //centerOffsetLeft:30, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
                        showLabel: true,				//use ".pieLabel div" to format looks of labels
                        labelOffsetFactor: 5 / 6, 		// part of radius (default 5/6)
                        //labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
                        labelBackgroundOpacity: 0.55, 	// default is 0.85
                        labelFormatter: function (serie) {// default formatter is "serie.label"
                            //return serie.label;
                            //return serie.data;
                            //return serie.label+'<br/>'+Math.round(serie.percent)+'%';
                            return Math.round(serie.percent) + '%';
                        }
                    },
                    colors: ["#EEE8AA", "#F0E68C", "#FFD700", "#FF8C00", "#DAA520", "#D2691E", "#B8860B"],
                    legend: {
                        show: true,
                        position: "ne",
                        backgroundOpacity: 0
                    }
                });
        });

        function load_pie(ips) {
            var loading = '<img width="16" align="absmiddle" src="images/loading.gif">&nbsp;&nbsp;<?=_("Loading")?>...';
            $("#hosts_pie").html(loading);
            $.ajax({
                type: "GET",
                data: {net: ips},
                url: "get_hostpie.php",
                success: function (msg) {
                    eval(msg);
                    $.plot($("#hosts_pie"), datapie,
                        {
                            pie: {
                                show: true,
                                pieStrokeLineWidth: 1,
                                pieStrokeColor: '#FFF',
                                //pieChartRadius: 100, 			// by default it calculated by
                                //centerOffsetTop:30,
                                //centerOffsetLeft:30, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
                                showLabel: true,				//use ".pieLabel div" to format looks of labels
                                labelOffsetFactor: 5 / 6, 		// part of radius (default 5/6)
                                //labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
                                labelBackgroundOpacity: 0.55, 	// default is 0.85
                                labelFormatter: function (serie) {// default formatter is "serie.label"
                                    //return serie.label;
                                    //return serie.data;
                                    //return serie.label+'<br/>'+Math.round(serie.percent)+'%';
                                    return Math.round(serie.percent) + '%';
                                }
                            },
                            colors: ["#EEE8AA", "#F0E68C", "#FFD700", "#FF8C00", "#DAA520", "#D2691E", "#B8860B"],
                            legend: {
                                show: true,
                                position: "ne",
                                backgroundOpacity: 0
                            }
                        });
                }
            });
        }
    </script>
    <?
}


function get_alternative_report($nid, $nname, $system_end_date, $report_id, $dbconn)
{
    $tempname = "$nid - $nname";
    $query = "SELECT report_id FROM vuln_nessus_report_stats WHERE report_id = $report_id OR dtLastUpdated  = '$system_end_date' OR '$tempname' LIKE CONCAT(name,'%')";
    $rx = $dbconn->Execute($query);
    if ($rx->NumRows() === 1 && $rx->fields["report_id"] != $report_id) {
        return $rx->fields["report_id"];
    }
    return false;
}


function get_finished_gvm_scans($offset, $pageSize)
{
    global $uroles, $dbconn, $hosts;

    $statuses = Vulnerabilities::$job_statuses;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $conf = $GLOBALS["CONF"];

    $tz = Util::get_timezone();

    $out = 0;

    list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

    if (empty($arruser)) {
        $query = "SELECT t1.task_id, t1.failed_attempts, t1.meth_Wcheck, t6.name as profile, t5.report_key, t1.report_id, t1.scan_NEXT, t1.meth_TARGET, t5.scantime, t1.failed_attempts, t1.scan_PRIORITY, t1.scan_PID, t1.id, t1.name, t1.username, t1.scan_START, t1.scan_END,
                       t1.scan_SUBMIT, t1.status, t1.notify, t3.hostname
                   FROM vuln_jobs t1
                   LEFT JOIN vuln_nessus_servers t3 on t1.notify = t3.hostname
                   LEFT JOIN vuln_nessus_reports t5 on t1.report_id = t5.report_id
                   LEFT JOIN vuln_nessus_settings t6 on t1.profile_id=t6.id WHERE t1.status != 'R'
                   ORDER BY t1.id DESC LIMIT $offset,$pageSize";
    } else {
        $query = "SELECT t1.task_id, t1.failed_attempts, t1.meth_Wcheck, t6.name as profile, t5.report_key, t1.report_id, t1.scan_NEXT, t1.meth_TARGET, t5.scantime, t1.failed_attempts, t1.scan_PRIORITY, t1.scan_PID, t1.id, t1.name, t1.username, t1.scan_START, t1.scan_END,
                       t1.scan_SUBMIT, t1.status, t1.notify, t3.hostname
                   FROM vuln_jobs t1
                   LEFT JOIN vuln_nessus_servers t3 on t1.notify = t3.hostname
                   LEFT JOIN vuln_nessus_reports t5 on t1.report_id = t5.report_id
                   LEFT JOIN vuln_nessus_settings t6 on t1.profile_id=t6.id
                   WHERE t1.username in ($user) AND t1.status != 'R'
                   ORDER BY t1.id DESC LIMIT $offset,$pageSize";
    }

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    $result = $dbconn->Execute($query);
    $sInfo = "<table style=\"margin: 5px auto;\" class=\"w100 transparent\"><tr><td class=\"sec_title\">"._("All Scans")."</td></tr></table>";

    if (!$result->EOF) {
        $out = 1;
        $selenium_id = 0;

        $sInfo .= "<table id='t_all_scans' class=\"table_list\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">";
        $sInfo .= "<tr id='t_all_scans_headers'>
              <th>" . _("Job Name") . "</th>
              <th>" . _("Launch Time") . "</th>
              <th>" . _("Scan Start Time") . "</th>
              <th>" . _("Scan End Time") . "</th>
              <th>" . _("Scan Time") . "</th>
              <th>" . _("Next Scan") . "</th>
              <th>" . _("Reports") . "</th>
              <th>" . _("Actions") . "</th>
              </tr>";


        while (!$result->EOF) {
            list ($task_id, $failed_attempts, $meth_Wcheck, $profile, $report_key, $report_id, $scan_next, $meth_TARGET,
                  $id_report, $failed_attempts, $scan_PRIORITY, $scan_PID, $nid, $nname, $nusername, $start_date,
                  $end_date, $submit_date, $nstatus, $nnotify, $shostname) = $result->fields;

            $system_end_date = $end_date;
            $targets_to_resolve = explode("\n", $meth_TARGET);
            $ttargets = array();

            //Resolve targets
            foreach ($targets_to_resolve as $id_ip) {
                if (preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+\/\d{1,2}$/i", $id_ip, $found)) {
                    if (Asset_net::is_in_db($dbconn, $found[1])) {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip) . " (" . Asset_net::get_name_by_id($dbconn, $found[1]) . ")";
                    } else {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
                    }
                } else if (preg_match("/^([a-f\d]{32})#hostgroup/i", $id_ip, $found)) {
                    $hostgroup_name = Asset_group::get_name_by_id($dbconn, $found[1]);
                    $ttargets[] = ($hostgroup_name == _('Unknown')) ? _('Unknown hostgroup') : $hostgroup_name;
                } else if (preg_match("/^([a-f\d]{32})#netgroup/i", $id_ip, $found)) {
                    $netgroup_name = Net_group::get_name_by_id($dbconn, $found[1]);
                    $ttargets[] = ($netgroup_name == _('Unknown')) ? _('Unknown netgroup') : $netgroup_name;
                } else if (preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+$/i", $id_ip, $found)) {
                    if (Asset_host::is_in_db($dbconn, $found[1])) {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip) . " (" . Asset_host::get_name_by_id($dbconn, $found[1]) . ")";
                    } else {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
                    }
                } else {
                    $ttargets[] = $id_ip;
                }
            }

            $meth_TARGET = implode("\n", $ttargets);


            //Save start_date in UTC to display scan time
            $ustart_date = $start_date;

            if ($tz != 0) {
                if (!is_null($submit_date)) {
                    $submit_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($submit_date) + (3600 * $tz));
                }
                if (!is_null($start_date)) {
                    $start_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($start_date) + (3600 * $tz));
                }
                if (!is_null($end_date)) {
                    $end_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($end_date) + (3600 * $tz));
                }
                if (!is_null($scan_next)) {
                    $scan_next = gmdate("YmdHis", Util::get_utc_unixtime($scan_next) + (3600 * $tz));
                }
            }

            //Sensor
            if ($nnotify != "") {
                if (Av_sensor::is_in_db($dbconn, $nnotify)) {
                    $shostname = Av_sensor::get_name_by_id($dbconn, $nnotify);
                } else {
                    $shostname = _("unknown");
                }
            }

            if ($shostname == "") {
                $shostname = "-";
            }
            $getRiskQuery = function ($report_id) {
                return "SELECT DISTINCT risk, port, protocol, app, scriptid, msg, hostIP, HEX(ctx) as ctx FROM vuln_nessus_results
                        WHERE report_id in ($report_id) AND scriptid <> 10180 AND falsepositive='N'";
            };
            $query = $getRiskQuery($report_id);

            $vulns = -1;    // vulns to display
            $vulns_in_report = false;

            if (!$resv = $dbconn->Execute($query)) {
                print 'Error: ' . $dbconn->ErrorMsg() . '<BR>';
                exit ();
            }

            if ($report_id != 0 && $resv->EOF) {
                $rid = get_alternative_report($nid, $nname, $system_end_date, $report_id, $dbconn);
                if ($rid) {
                    $report_id = $rid;
                    $query = $getRiskQuery($report_id);
                    $resv = $dbconn->Execute($query);
                    if (!$resv->EOF) {
                        $query = "SELECT report_key,scantime FROM vuln_nessus_reports WHERE report_id = {$report_id }";
                        $rx = $dbconn->Execute($query);
                        if (!$rx->EOF) {
                            $id_report = $rx->fields["scantime"];
                            $report_key = $rx->fields["report_key"];
                        }
                    }
                }
            }

            while (!$resv->EOF) {
                $vulns_in_report = true;
                if (Session::hostAllowed_by_ip_ctx($dbconn, $resv->fields["hostIP"], $resv->fields['ctx'])) {
                    $vulns = ($vulns == -1) ? 1 : $vulns + 1;
                }
                $resv->MoveNext();
            }


            if ($start_date && $end_date) {
                $nmins = datediff("n", $end_date, $start_date) . " mins";
            } elseif ($start_date) {
                $current_date = gmdate("Y-m-d H:i:s");

                $nmins = "RUN..>" . datediff("n", $current_date, $ustart_date);
                $nmins .= " mins";
            } elseif ($submit_date) {
                $nmins = "&nbsp";
            } else {
                $nmins = "&nbsp";
                $start_date = "";
                $end_date = "<i>" . _("Never") . "</i>";
            }

            $job_status = "";

            $tstatus = array(
                "status" => "",
                "progress" => ""
            );

            if ($nstatus == "I" ){
                $image = "<img src='./images/paused.png' border='0' title='"._("Stopped"). "' alt='"._("Stopped")."' align='absmiddle'>";
            } elseif ($nstatus == "C") {
                $image = "<img src='./images/complete.png' border='0' title='"._($statuses[$nstatus])."' alt='"._($statuses[$nstatus])."' align='absmiddle'>";
            }
            elseif ($nstatus == "S") {
                $image = "<img src='./images/sched.png' border='0' title='"._($statuses[$nstatus])."' alt='"._($statuses[$nstatus])."' align='absmiddle'>";
            }
            elseif ($nstatus == "D") {
                $image = "<img src='./images/delayed.png' border='0' title='"._($statuses[$nstatus])."' alt='"._($statuses[$nstatus])."' align='absmiddle'>";
            }
            elseif ($nstatus == "F" || $nstatus == "H" || $nstatus == "K") {
                $image = "<img src='./images/warning.png' border='0' title='".$statuses[$nstatus]."' alt='".$statuses[$nstatus]."' align='absmiddle'>";
            } elseif ($nstatus == "T") {
                $image = "<img src='./images/timeout.png' border='0' title='"._("Timeout expired"). "' alt='"._("Timeout expired")."' align='absmiddle'>";
            }

            if (strtotime($scan_next) <= strtotime($submit_date)) {
                $scan_next = "-";
            } else {
                $scan_next = date("Y-m-d H:i:s", strtotime($scan_next));
            }

            $meth_TARGETorig = explode("\n", trim($meth_TARGET));
            
            if (valid_hex32($nusername)) {
                $nusername = Session::get_entity_name($dbconn, $nusername);
            }

            $sInfo .= "<tr>";
    
            $meth_Wcheck = nl2br(trim($meth_Wcheck));
            $meth_Wcheck = str_replace('"', '&quot;', $meth_Wcheck);
            $status_detail = preg_replace(array('/Scan Server Selected:\s+\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s*<br \/>/', '/Nmap/'), array('', _('Scanner')), $meth_Wcheck);
            $status_detail = preg_replace('/Scan Server/', 'Scan Sensor', $status_detail);
        
            $target_label = _('Targets');
            if (count($meth_TARGETorig) > 1){
                $target_label .= " (".count($meth_TARGETorig)." "._("hosts").")";
            }
            $target_label .= ":";
            
            $tooltip = "<b>"._('Owner').": </b>$nusername<br/>
                        <b>"._('Sensor').": </b>$shostname<br/>$job_status
                        <b>"._('Job ID').": </b>$nid<br/>
                        <b>"._('Profile').": </b>$profile<br/>
                        <b>"._('Status').": </b>$statuses[$nstatus]<br/>
                        <b>"._('Failed attempts').": </b> $failed_attempts<br/>
                        <b>".$target_label."</b><br/>".tooltip_hosts($meth_TARGETorig, $hosts)."<br/>".
            (($status_detail != '') ? '<b>'._('Messages').': </b><br/>'.$status_detail : '');

            if ($nstatus == "C" || $nstatus == "I") {
                $html_link = "";
                if (intval($vulns) > 0) {
                    $html_link = "href='reshtml.php?disp=html&amp;output=full&amp;scantime=$id_report&amp;scantype=M'";
                }
            } else {
                $html_link = "href='javascript:;'";
            }

            $sInfo .= "<td style='text-align:left;'><a class='tip' style='text-decoration:none;' $html_link title=\"".$tooltip."\"><div class='job_detail'>$image".$nname."</div></a><div class='job_messages'>".$status_detail."</div></td>";

            $sInfo .= "<td>$submit_date</td>
               <td>$start_date</td>
               <td>$end_date</td>";

            if ($nmins == "0 mins")
                $sInfo .= "<td>< 1 min</td>";
            else
                $sInfo .= "<td>$nmins</td>";

            $sInfo .= "<td>$scan_next</td>";

            $user_name_filter = array();
            $c_user = Session::get_session_user();
            if (Session::is_pro()) {
                if (Acl::am_i_proadmin()) {
                    $user_name_filter = Acl::get_entities_to_assign($dbconn);

                    $users = Acl::get_my_users($dbconn, $c_user);
                    foreach ($users as $us) {
                        $user_name_filter[$us->get_login()] = $us->get_login();
                    }
                } else {
                    $user_name_filter[$c_user] = $c_user;
                }
            } else {
                $user_name_filter[$c_user] = $c_user;
            }

            $sInfo .= "<td class='job_reports'>";

            if ($nstatus == "C" || $nstatus == "I" || $nstatus == "T"){
                if (!$vulns_in_report) {
                    // report without results
                    $vulns = 0;
                    $sInfo .= "<img align=\"absmiddle\" class=\"disabled\" src=\"./images/html.png\" border=\"0\" title=\"" . _("No results found") . "\" alt=\"" . _("No results found") . "\">&nbsp;";
                    $sInfo .= "<img align=\"absmiddle\" class=\"disabled\" src=\"./images/pdf.png\" border=\"0\" title=\"" . _("No results found") . "\" alt=\"" . _("No results found") . "\">&nbsp;";
                    $sInfo .= "<img align=\"absmiddle\" class=\"disabled\" src=\"./images/page_white_excel.png\" border=\"0\" title=\"" . _("No results found") . "\" alt=\"" . _("No results found") . "\">&nbsp;";
                } elseif ($vulns != -1) {
                    // report with results

                    $sInfo .= "<a id='html_report_$selenium_id' href=\"reshtml.php?disp=html&amp;output=full&amp;scantime=$id_report&amp;scantype=M\"><img align=\"absmiddle\" src=\"./images/html.png\" border=\"0\" title=\"" . _("View report") . "\" alt=\"" . _("View report") . "\"></a>&nbsp;";
                    $sInfo .= "<a href=\"respdf.php?scantime=$id_report&amp;key=$report_key&amp;scantype=M\" target=\"_blank\"><img align=\"absmiddle\" src=\"./images/pdf.png\" border=\"0\" title=\"" . _("View report") . "\" alt=\"" . _("View report") . "\"></a>&nbsp;";
                    $sInfo .= "<a href=\"rescsv.php?scantime=$id_report&amp;key=$report_key&amp;scantype=M\" target=\"_blank\"><img align=\"absmiddle\" src=\"./images/page_white_excel.png\" border=\"0\" title=\"" . _("View report") . "\" alt=\"" . _("View report") . "\"></a>&nbsp;";

                } else {
                    // the user has no permission
                    $vulns = "-";
                    $sInfo .= "<img align=\"absmiddle\" class=\"disabled\" src=\"./images/html.png\" border=\"0\" title=\"" . _("Report not allowed") . "\" alt=\"" . _("Report not allowed") . "\">&nbsp;";
                    $sInfo .= "<img align=\"absmiddle\" class=\"disabled\" src=\"./images/pdf.png\" border=\"0\" title=\"" . _("Report not allowed") . "\" alt=\"" . _("Report not allowed") . "\">&nbsp;";
                    $sInfo .= "<img align=\"absmiddle\" class=\"disabled\" src=\"./images/page_white_excel.png\" border=\"0\" title=\"" . _("Report not allowed") . "\" alt=\"" . _("Report not allowed") . "\">&nbsp;";
                }

                if (!$vulns_in_report || $vulns != "-") {
                    if (file_exists("/usr/share/ossim/uploads/nbe/" . $report_id . ".nbe")) {
                        $sInfo .= "<a href=\"export_nbe.php?report_id=$report_id\" style=\"display: inline\"><img title=\"" . _("Download results in nbe format") . "\" alt=\"" . _("Download results in nbe format") . "\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"></a>&nbsp;";
                    } else {
                        $sInfo .= "<a href=\"export_nbe.php?job_id=$nid\" style=\"display: inline\"><img title=\"" . _("Export job results in nbe format") . "\" alt=\"" . _("Export job results in nbe format") . "\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"></a>&nbsp;";
                    }
                } else {
                    $sInfo .= "<img class=\"disabled\" title=\"" . _("Action not allowed") . "\" alt=\"" . _("Action not allowed") . "\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"/>&nbsp;";
                }

                $sInfo .= "<div class='job_results_number'>($vulns)</div>";
            } else {
                $sInfo .= "<div>-</div>";
            }



            $sInfo .= "</td><td class='job_actions' id='job_actions_$nid'>";

            $task_status = $tstatus["status"];

            if ($nstatus == "S" || $nstatus == "D"){
                //Job is scheduled but it's not running
                $sInfo .= "<div class='no_action'></div>";
                $sInfo .= "<div class='no_action'></div>";
                $sInfo .= "<div class='no_action'></div>";
                $sInfo .= "<a href=\"javascript:void(0);\" onclick=\"return confirm_delete('delete_scan', '".$nid."');\"><img align=\"absmiddle\" src=\"images/delete.gif\" title=\"" . _("Delete") . "\" alt=\"" . _("Delete") . "\" border=0></a>";
            }
            else {
                if ($nstatus == "F" || $nstatus == "H" || $nstatus == "K") {
                    $sInfo .= "<div class='no_action'></div>";
                    $sInfo .= "<div class='no_action'></div>";
                    $sInfo .= "<a href=\"" . Menu::get_menu_url('sched.php?action=rerun_scan&job_id=' . $nid, 'environment', 'vulnerabilities', 'scan_jobs') . "\"><img align=\"absmiddle\" src=\"./images/relaunch.gif\" border=\"0\" title=\"" . _("Rerun Scan") . "\" alt=\"" . _("Rerun Scan") . "\"></a>";
                } else if ($nstatus == "T" || $nstatus == "C" || $nstatus == "I") {

                    if ($nstatus == "I") {
                        $sInfo .= "<a href='javascript:;' class='manageJob' id='resumeTask_$nid'><img align=\"absmiddle\" title=\"" . _("Resume scan job") . "\" alt=\"" . _("Resume scan job") . "\" src=\"./images/resume_task.png\"  border=0></a>";
                    } else {
                        $sInfo .= "<div class='no_action'></div>";
                    }

                    if (Session::am_i_admin() || (Session::is_pro() && Acl::am_i_proadmin())) {
                        $sInfo .= "<a class=\"greybox\" href=\"change_user.php?id=$report_id\"><img align=\"absmiddle\" src=\"../pixmaps/group.png\" title=\"" . _("Change owner") . "\" alt=\"" . _("Change owner") . "\" border=\"0\"></a>";
                    } else {
                        $sInfo .= "<div class='no_action'></div>";
                    }

                    if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
                        if (!$vulns_in_report || $vulns != -1) {
                            $sInfo .= "<a href=\"" . Menu::get_menu_url('sched.php?action=rerun_scan&job_id=' . $nid, 'environment', 'vulnerabilities', 'scan_jobs') . "\"><img align=\"absmiddle\" src=\"./images/relaunch.gif\" border=\"0\" title=\"" . _("Rerun Scan") . "\" alt=\"" . _("Rerun Scan") . "\"></a>";
                        } else {
                            $sInfo .= "<img align=\"absmiddle\" class= \"disabled\" src=\"./images/relaunch.gif\" border=\"0\" title=\"" . _("Action not allowed") . "\" alt=\"" . _("Action not allowed") . "\"/>&nbsp;";
                        }
                    } else {
                        $sInfo .= "<div class='no_action'></div>";
                    }
                }

                if ($task_id != "") {
                    // Show delete button
                    $is_not_running = ($tstatus["status"] == "" || $tstatus["status"] == "Done" || $tstatus["status"] == "Stopped" || $tstatus["status"] == "Stop requested");
                    if ($is_not_running) {
                        if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
                            if (($vulns != 0 && $vulns != "-") || Session::am_i_admin()) {
                                $sInfo .= "<a id=\"delete_job_$nid\" href=\"javascript:;\" onclick=\"deleteTask('$nid');\"><img align=\"absmiddle\" title=\"" . _("Delete scan job") . "\" alt=\"" . _("Delete scan job") . "\" src=\"./images/delete.gif\"  border=0></a>";
                            } else {
                                $sInfo .= "<img class=\"disabled\" align=\"absmiddle\" title=\"" . _("Action not allowed") . "\" alt=\"" . _("Action not allowed") . "\" src=\"./images/delete.gif\"  border=0 />";
                            }
                        }
                    } else {
                        $sInfo .= "<img align=\"absmiddle\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"./images/delete.gif\"  border=0></a>";
                    }
                } else {
                    $sInfo .= "<a href=\"javascript:void(0);\" onclick=\"return confirm_delete('delete_scan', '".$nid."');\"><img align=\"absmiddle\" src=\"images/delete.gif\" title=\"" . _("Delete") . "\" alt=\"" . _("Delete") . "\" border=0></a>";
                }
            }

            $sInfo .= "<span style='display:none' id='changing_task_status_$nid'><img align=\"absmiddle\" src=\"../pixmaps/loading.gif\" width='16' border='0'></span></td>";

            $selenium_id++;

            $sInfo .= "</tr>";
            $result->MoveNext();

        }

        $sInfo .= "</table>";
    }

    if ($out == 0) {
        $sInfo .= "<table class='w100 table_list'><tr><td class='nobborder empty_results' height='20' style='text-align:center'>" . _("No Jobs") . "</td></tr></table>";
    }

    echo $sInfo;

    return $out;
}


function dispSQLError($err, $errNum)
{
    echo "<center><table class='error'>
         <tr><th> $errNum SQL Errors<th></tr>\n";
    foreach ($err as $e) {
        echo "<tr><td>$e</td></tr>\n";
    }
    echo "</table></center>\n";
}


function getrisk($risk)
{
    switch ($risk) {
        case 1:
            $risk = "Critical";
            break;
        case 2:
            $risk = "High";
            break;
        case 3:
            $risk = "Medium";
            break;
        case 4:
            $risk = "Medium/Low";
            break;
        case 5:
            $risk = "Low/Medium";
            break;
        case 6:
            $risk = "Low";
            break;
        case 7:
            $risk = "Info";
            break;
        case 8:
            $risk = "Exceptions";
            break;
    }

    return $risk;
}


function gen_strtotime($value, $newformat) {

    if (strlen($value) == 0 || $value == "now") {
        #NOW
        $arrTime = localtime(time(), true);
        $year = 1900 + $arrTime["tm_year"];
        $mon = 1 + $arrTime["tm_mon"];
        $mday = $arrTime["tm_mday"];
        $hour = $arrTime["tm_hour"];
        $min = $arrTime["tm_min"];
        $sec = $arrTime["tm_sec"];
    } else { # A VALUE WAS PASSED
        if (strlen($value) != 14) {
            return $value;
        }
        $year = substr($value, 0, 4);
        $mon = substr($value, 4, 2);
        $mday = substr($value, 6, 2);
        $hour = substr($value, 8, 2);
        $min = substr($value, 10, 2);
        $sec = substr($value, 12, 2);
    }

    if ($newformat == "asdate") {
        $value2 = sprintf("%02d/%02d/%04d %02d:%02d:%02d", $mon, $mday, $year, $hour, $min, $sec);
    } else {    #SQL FORMAT
        $value2 = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $mon, $mday, $hour, $min, $sec);
    }
    return $value2;
}


function datediff($interval, $date1, $date2)
{
    //convert the dates into timestamps
    $date1 = strtotime($date1);
    $date2 = strtotime($date2);
    $seconds = $date2 - $date1;

    if ($seconds < 0) {
        $tmp = $date1;
        $date1 = $date2;
        $date2 = $tmp;
        $seconds = 0 - $seconds;
    }

    //reconvert the timestamps into dates
    if ($interval == 'y' || $interval == 'm') {
        $date1 = date("Y-m-d h:i:s", $date1);
        $date2 = date("Y-m-d h:i:s", $date2);
    }

    switch ($interval) {
        case "y":
            list($year1, $month1, $day1) = preg_split('/\-/', $date1);
            list($year2, $month2, $day2) = preg_split('/\-/', $date2);
            $time1 = (date('H', $date1) * 3600) + (date('i', $date1) * 60) + (date('s', $date1)
                );
            $time2 = (date('H', $date2) * 3600) + (date('i', $date2) * 60) + (date('s', $date2)
                );
            $diff = $year2 - $year1;

            if ($month1 > $month2) {
                $diff -= 1;
            } elseif ($month1 == $month2) {
                if ($day1 > $day2) {
                    $diff -= 1;
                } elseif ($day1 == $day2) {
                    if ($time1 > $time2) {
                        $diff -= 1;
                    }
                }
            }
            break;
        case "m":
            list($year1, $month1, $day1) = preg_split('/\-/', $date1);
            list($year2, $month2, $day2) = preg_split('/\-/', $date2);
            $time1 = (date('H', $date1) * 3600) + (date('i', $date1) * 60) + (date('s', $date1)
                );
            $time2 = (date('H', $date2) * 3600) + (date('i', $date2) * 60) + (date('s', $date2)
                );
            $diff = ($year2 * 12 + $month2) - ($year1 * 12 + $month1);
            if ($day1 > $day2) {
                $diff -= 1;
            } elseif ($day1 == $day2) {
                if ($time1 > $time2) {
                    $diff -= 1;
                }
            }
            break;
        case "w":
            // Only simple seconds calculation needed from here on
            $diff = floor($seconds / 604800);
            break;
        case "d":
            $diff = floor($seconds / 86400);
            break;
        case "h":
            $diff = floor($seconds / 3600);
            break;
        case "n":
            $diff = floor($seconds / 60);
            break;
        case "s":
            $diff = $seconds;
            break;
    }

    return $diff;
}


function drawTable($map, $data, $summary = "", $hosts = array())
{
    echo "<center><table class='table_list' summary='$summary' width='100%' cellspacing='0' cellpadding='3'>";
    echo "<tr>";
    if (!empty($map)) {
        foreach ($map as $fieldName => $fieldValue) {
            if ($fieldName != "Links") {
                // see if we need to create sorting links in the table headers
                if ($fieldName == "Targets") {
                    echo "<th>" . _("Targets") . "</th>";
                } elseif (is_array($fieldValue)) {
                    $link = str_replace("%var%", $fieldValue['var'], $fieldValue['link']);
                    if ($fieldName == "Date/Time") {
                        echo "<th width='130'><a href='$link' text='Sort by $fieldName'>" . _("Date") . "/" . _("Time") . "</a></th>";
                    } elseif ($fieldName == "Job Name") {
                        echo "<th width='150'><a href='$link' text='Sort by $fieldName'>" . _("Job Name") . "</a></th>";
                    } elseif ($fieldName == "Critical") {
                        echo "<th width='270'><div style='float:left;width:19%;'><img src='./images/critical.png'/></div>";
                    } elseif ($fieldName == "High") {
                        echo "<div style='float:left;width:19%;'><img src='./images/high.png'/></div>";
                    } elseif ($fieldName == "Medium") {
                        echo "<div style='float:left;width:19%;'><img src='./images/medium.png'/></div>";
                    } elseif ($fieldName == "Low") {
                        echo "<div style='float:left;width:19%;'><img src='./images/low.png'/></div>";
                    } elseif ($fieldName == "Info") {
                        echo "<div style='float:left;width:19%;'><img src='./images/inf.png'/></th>";
                    } else {
                        echo "<th><a href='$link' text='Sort by $fieldName'>" . _($fieldName) . "</a></th>";
                    }
                } else {
                    echo "<th>$fieldName</th>";
                }
            } else {
                echo "<th>&nbsp;</th>";
            }
        }
    }
    echo "</tr>";
    $already = array();
    if (!empty($data)) {
        $selenium_id = 0;

        foreach ($data as $entry) {
            $job_name = $entry['jobname'];
            $scan_submit = $entry['scan_submit'];
            $scantime = $entry['scantime'];

            $agg = subjobs($job_name, $scan_submit, $data);
            if (count($agg) > 1) {
                if (!in_array($job_name . "-" . $scan_submit, $already)) {
                    $already[] = $job_name . "-" . $scan_submit;
                    # aggregate data
                    $entry['target'] = $entry['profile'] = '';
                    $entry['hlink'] = "reshtml.php?disp=html&amp;output=full&scansubmit=" . $scan_submit . "&amp;scantype=M";
                    $entry['plink'] = "respdf.php?scansubmit=" . $scan_submit . "&amp;scantype=M";
                    $entry['xlink'] = "rescsv.php?scansubmit=" . $scan_submit . "&amp;scantype=M&amp;scantime=" . $scantime;
                    $entry['vInfo'] = 0;
                    $entry['vLow'] = 0;
                    $entry['vMed'] = 0;
                    $entry['vHigh'] = 0;
                    $entry['vCritical'] = 0;
                    foreach ($agg as $entry2) {
                        $entry['vInfo'] += $entry2['vInfo'];
                        $entry['vLow'] += $entry2['vLow'];
                        $entry['vMed'] += $entry2['vMed'];
                        $entry['vHigh'] += $entry2['vHigh'];
                        $entry['vCritical'] += $entry2['vCritical'];
                    }
                    $random = time();
                    cellprint($map, $data, $entry, '', "ltP_nesi.gif", "class='downclick'", $random, $hosts, $selenium_id);
                    $i = 1;
                    foreach ($agg as $entry2) {
                        $entry2['jobname'] = date("Y-m-d H:i:s", strtotime($entry2['scantime']));
                        $entry2['scantime'] = '';
                        if ($i != count($agg))
                            cellprint($map, $data, $entry2, '', "ltL_nes2.gif", "", $random, $hosts, $selenium_id);
                        else
                            cellprint($map, $data, $entry2, '', "ltL_ne2.gif", "", $random, $hosts, $selenium_id);
                        $i++;
                    }
                }
            } else {
                cellprint($map, $data, $entry, '', "", "", "", $hosts, $selenium_id);
            }

            $selenium_id++;
        }
    }
    echo "</table></center>";
}


function countVulnerabilities($dbconn, $query, $entry)
{

    //Reset count in order to prevent errors
    $entry['vInfo'] = $entry['vLow'] = $entry['vMed'] = $entry['vHigh'] = $entry['vCritical'] = 0;
    $result = $dbconn->Execute($query);
    while (!$result->EOF) {
        list ($total, $trik) = $result->fields;
        if ($trik == 7)
            $entry['vInfo'] = $total;
        else if ($trik == 6)
            $entry['vLow'] = $total;
        else if ($trik == 3)
            $entry['vMed'] = $total;
        else if ($trik == 2)
            $entry['vHigh'] = $total;
        else if ($trik == 1)
            $entry['vCritical'] = $total;
        $result->MoveNext();
    }

    return $entry;

}


function drawTableLatest($map, $data, $summary = "")
{
    global $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    echo "<center><table summary='$summary' class='table_list' width='100%' cellspacing='0'>";
    echo "<tr>";
    if (!empty($map)) {
        foreach ($map as $fieldName => $fieldValue) {
            if ($fieldName != "Links") {
                // see if we need to create sorting links in the table headers
                if ($fieldName == "Targets") {
                    echo "<th width='150'>" . _("Targets") . "</th>";
                } elseif (is_array($fieldValue)) {
                    $link = str_replace("%var%", $fieldValue['var'], $fieldValue['link']);
                    if ($fieldName == "Date/Time") {
                        echo "<th width='70'>" . _("Date") . "/" . _("Time") . "</th>";
                    } elseif ($fieldName == "Host - IP") {
                        echo "<th width='150'>" . _("Host - IP") . "</th>";
                    } elseif ($fieldName == "Critical") {
                        echo "<th width='270'><div style='float:left;width:19%;'><img src='./images/critical.png'/></div>";
                    } elseif ($fieldName == "High") {
                        echo "<div style='float:left;width:19%;'><img src='./images/high.png'/></div>";
                    } elseif ($fieldName == "Medium") {
                        echo "<div style='float:left;width:19%;'><img src='./images/medium.png'/></div>";
                    } elseif ($fieldName == "Low") {
                        echo "<div style='float:left;width:19%;'><img src='./images/low.png'/></div>";
                    } elseif ($fieldName == "Info") {
                        echo "<div style='float:left;width:19%;'><img src='./images/inf.png'/></th>";
                    } else {
                        echo "<th width='70'>" . _($fieldName) . "</th>";
                    }
                } else {
                    echo "<th>$fieldName</th>";
                }
            } else {
                echo "<th width='48'>&nbsp;</th>";
            }
        }
    }
    echo "</tr>";
    $already = array();
    $colors = array("#FFFFFF", "#EEEEEE");
    $color = 0;
    $selenium_id = 0;

    if (!empty($data)) {
        foreach ($data as $entry) {
            $ip = $entry['hostIP'];
            $ctx = $entry['ctx'];

            $wip = ($entry['hostIP'] != "") ? "AND hostIP='" . $entry['hostIP'] . "'" : "";
            $wctx = ($entry['ctx'] != "") ? "AND ctx=UNHEX('" . $entry['ctx'] . "')" : "";
            $scantime = $entry['scantime'];
            $agg = subjobslatest($ip, $ctx, $data);

            if (count($agg) > 1) {
                if (!in_array($ip, $already)) {
                    $already[] = $ip;
                    //the owner is cleaned
                    $entry['username'] = "-";
                    # aggregate data
                    $entry['target'] = $entry['profile'] = '';
                    $entry['hlink'] = "lr_reshtml.php?ipl=$ip&ctx=$ctx&disp=html&output=full&scantype=M";
                    $entry['plink'] = "lr_respdf.php?ipl=$ip&ctx=$ctx&scantype=M";
                    $entry['xlink'] = "lr_rescsv.php?ipl=$ip&ctx=$ctx&scantype=M";
                    $entry['dlink'] = "index.php?delete_ipl_ctx=" . base64_encode($ip . "#" . $ctx);

                    $query_lr = "select count(*) as total,risk from (select distinct port, protocol, app, scriptid, msg, risk, hostIP from vuln_nessus_latest_results where falsepositive='N' $wip $wctx) as t group by risk";
                    $entry = countVulnerabilities($dbconn, $query_lr, $entry);

                    $random = time();
                    $entry['scantime'] = '';

                    cellprintlatest($map, $data, $entry, $colors[$color % 2], "bltP_nesi.gif", "class='downclick'", $random, $selenium_id);

                    $i = 1;
                    foreach ($agg as $entry2) {
                        $entry2['jobname'] = date("Y-m-d H:i:s", strtotime($entry2['scantime']));
                        $entry2['hostIP'] = '';
                        $query_lr2 = "select count(*) as total,risk from (select distinct port, protocol, app, scriptid, msg, risk, hostIP from vuln_nessus_latest_results where falsepositive='N' AND scantime = " . $entry2["scantime"] . "  $wip $wctx) as t group by risk";
                        //Sub reports risk counts must be updated according to the scan time
                        $entry2 = countVulnerabilities($dbconn, $query_lr2, $entry2);
                        $entry2['hlink'] = "lr_reshtml2.php?ipl=$ip&ctx=$ctx&disp=html&output=full&scantype=M";
                        //The only way to get the report key is using the scantime
                        $query_report_key = "SELECT report_key FROM vuln_nessus_reports WHERE scantime = " . $entry2["scantime"];
                        $report_key = $dbconn->GetOne($query_report_key);

                        $entry2['hlink'] = "reshtml.php?disp=html&amp;output=full&scantime=" . $entry2['scantime'] . "&scantype=" . $entry2['scantype'] . "&key=" . $report_key;
                        $entry2['plink'] = "respdf.php?scantime=" . $entry2['scantime'] . "&scantype=" . $entry2['scantype'] . "&key=" . $report_key;
                        $entry2['xlink'] = "rescsv.php?scantime=" . $entry2['scantime'] . "&scantype=" . $entry2['scantype'] . "&key=" . $report_key;
                        if ($i != count($agg))
                            cellprintlatest($map, $data, $entry2, "#DEEBDB", "bltL_nes2.gif", "", $random, $selenium_id);
                        else
                            cellprintlatest($map, $data, $entry2, "#DEEBDB", "bltL_ne2.gif", "", $random, $selenium_id);
                        $i++;
                    }
                }
            } else {
                cellprintlatest($map, $data, $entry, $colors[$color % 2], "", "", "", $selenium_id);
            }
            $color++;
            $selenium_id++;
        }
    }
    echo "</table></center>";
}


function cellprint($map, $data, $entry, $color, $image, $class, $random, $hosts, $selenium_id)
{
    //
    global $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $tz = Util::get_timezone();

    if ($color != "" && $entry["report_type"] != "I")
        echo "<tr bgcolor=\"$color\" class='cell$random'>";
    else if ($entry["report_type"] == "I")
        echo "<tr bgcolor=\"#FCEAC9\">";
    else
        echo "<tr>";
    if (!empty($map)) {
        foreach ($map as $fieldName => $fieldValue) {

            if ($fieldName != "Links") {
                // some extra processing to allow custom links
                if (is_array($fieldValue)) {
                    if ($fieldValue['var'] == SCAN_TIME_TYPE) {
                        if ($tz != 0) {
                            $entry[$fieldValue['var']] = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($entry[$fieldValue['var']]) + 3600 * $tz);
                        } else {
                            $entry[$fieldValue['var']] = date("Y-m-d H:i:s", strtotime($entry[$fieldValue['var']]));
                        }
                        if ($color != "" && $entry["report_type"] != "I") {
                            echo "<td width=\"130\" style=\"text-align:center;background-color: " . $color . ";\" class=\"nobborder\" nowrap>" . $entry[$fieldValue['var']] . (($image != "") ? "<image src=\"../pixmaps/theme/$image\" border=\"0\" align=\"absmiddle\">" : "") . "</td>";
                        } else if ($entry[$fieldValue['var']] != "") {
                            echo "<td width=\"130\" " . (($image != "") ? "style=\"text-align:center;padding-right:2px\" class=\"nobborder\"" : "") . " nowrap>" . $entry[$fieldValue['var']] . (($image != "") ? "&nbsp;<image src=\"../pixmaps/theme/$image\" border=\"1\" align=\"absmiddle\" value='cell$random' $class>" : "") . "</td>";
                        } else {
                            echo "<td width=\"130\" nowrap>&nbsp;</td>";
                        }
                    } elseif ($fieldValue['var'] == "hostip") {
                        if ($entry["report_id"] != "") {
                            echo "<td width='150' style='text-align:center; white-space: nowrap'>" . (($entry["host_name"] != long2ip($entry["report_id"]) && $entry["host_name"] != "") ? $entry['host_name'] . " (" . long2ip($entry['report_id']) . ")" : long2ip($entry['report_id'])) . "</td>";
                        } else {
                            echo "<td width='150' style='text-align:center; white-space: nowrap'>&nbsp;</td>";
                        }
                    } elseif ($fieldValue['var'] == "target") {
                        echo "<td style='text-align:center;padding:0px 7px;'nowrap>" . $entry[$fieldValue['var']] . "</td>";
                    } elseif ($fieldValue['var'] == "vCritical") {
                        if (!is_numeric($entry[$fieldValue['var']])) {
                            $disable_report = true;
                        } else {
                            $disable_report = false;
                        }
                        $vCritical = $entry[$fieldValue['var']];
                        echo "<td width='110' nowrap><div style='margin-left:7px;float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vHigh") {
                        $vHigh = $entry[$fieldValue['var']];
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vMed") {
                        $vMed = $entry[$fieldValue['var']];
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vLow") {
                        $vLow = $entry[$fieldValue['var']];
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vInfo") {
                        $vInfo = $entry[$fieldValue['var']];
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div></td>";
                    } else {
                        echo "<td nowrap width='110'>" . (($fieldValue['var'] == "profile" && $entry[$fieldValue['var']] == "") ? _("-") : $entry[$fieldValue['var']]) . "</td>";
                    }
                } else {
                    echo "<td nowrap>$entry[$fieldValue]</td>";
                }
            } else {
                echo "<td nowrap width='180'>";
                foreach ($fieldValue as $linkName => $linkData) {
                    $onclick = "";
                    $target = "";
                    if (!empty ($linkData['onclick'])) {
                        $onclick = "onclick='" . $linkData['onclick'] . "'";
                    }
                    if (!empty ($linkData['target'])) {
                        $target = "target=\"" . $linkData['target'] . "\"";
                    }
                    $link = str_replace("%param%", $entry[$linkData['param']], $linkData['url']);
                    if (!$disable_report && !($vCritical == 0 && $vHigh == 0 && $vMed == 0 && $vLow == 0 && $vInfo == 0)) {


                        $report_sid = ($linkName == "HTML Results") ? "id=\"html_report_" . $selenium_id . "\"" : ""; // for selenium tests

                        echo "<a style=\"text-decoration:none\" $report_sid href=\"$link\" alt=\"$linkName\" title=\"$linkName\" $target>
                <img src=\"" . $linkData['icon'] . "\" alt=\"$linkName\" $onclick ></img></a>";
                    } else {
                        echo "<img class=\"disabled\" src=\"" . $linkData['icon'] . "\" alt=\"" . _("Report not allowed") . "\" title=\"" . _("Report not allowed") . "\"></img>&nbsp;";
                    }
                }
                $ips = array();
                if (!preg_match("/scansubmit/", $link)) {
                    $ips = hosts_to_insert($dbconn, $entry["report_id"], $hosts);
                    if (!$disable_report) {
                        ?>
                        <a class="greybox" gtitle="<?php echo _("Make this scan job visible for:") ?>" gheight="190"
                           href="change_user.php?id=<?php echo $entry["report_id"]; ?>"><img src="../pixmaps/group.png"
                                                                                             title="<?php echo _("Change owner") ?>"
                                                                                             alt="<?php echo _("Change owner") ?>"
                                                                                             border="2"></a>
                        <?php
                    } else {
                        ?>
                        <img src="../pixmaps/group.png" class="disabled" title="<?php echo _("Action not allowed") ?>"
                             alt="<?php echo _("Action not allowed") ?>" border="2"/>
                        <?php
                    }
                }
                if (count($ips) > 0)
                    echo "<a class='greybox' gtitle='" . _("Insert into assets:") . "' gwidth='800' gheight='" . (150 + count($ips) * 30) . "' href='add_hosts.php?report_id=" . $entry["report_id"] . "'><img src='../pixmaps/theme/host_add.png' title='Add Hosts to Assets' alt='Add Hosts to Assets' border='0'></a>";
                if (!$disable_report) {
                    ?>
                    <a class="greybox" gwidth="550" gtitle="<?php echo _("Compare reports:") ?>" gheight="150"
                       href="/ossim/vulnmeter/select_report.php?freport=<?php echo $entry["report_id"]; ?>"><img
                                src="../pixmaps/compare.png" title="<?php echo _("Compare with other") ?>"
                                alt="<?php echo _("Compare with other") ?>" border="2"></a>
                    <?
                } else { ?>
                    <img src="../pixmaps/compare.png" class="disabled" title="<?php echo _("Action not allowed") ?>"
                         alt="<?php echo _("Action not allowed") ?>" border="2">
                    <?php
                }
                if ($entry["report_type"] == "I") {
                    ?>
                    <a href="export_nbe.php?report_id=<?php echo $entry["report_id"]; ?>"
                       alt="<?php echo _("Download uploaded nbe file"); ?>"
                       title="<?php echo _("Download uploaded nbe file"); ?>">
                       <img src="../pixmaps/download.png" alt="<?php echo _("Download uploaded nbe file"); ?>"/></a>
                    <?php
                } ?>
                <a id="delete_report_<?php echo $selenium_id ?>" href="javascript:;"
                   onclick="delete_report('<?php echo $entry["report_id"]; ?>')" alt="<?php echo _("Delete Report"); ?>"
                   title=" <?php echo _("Delete Report"); ?>">
                    <img src="images/delete.gif" alt="<?php echo _("Delete Report"); ?>"/></a>
                <?php
                echo "</td>";

            }
        }
        echo "</tr>\n";
    }

}


function cellprintlatest($map, $data, $entry, $color, $image, $class, $random, $selenium_id)
{
    global $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $tz = Util::get_timezone();

    if ($color != "")
        echo "<tr bgcolor=\"$color\" class='cell$random'>";
    else
        echo "<tr>";
    if (!empty($map)) {
        foreach ($map as $fieldName => $fieldValue) {

            if ($fieldName != "Links") {
                // some extra processing to allow custom links
                if (is_array($fieldValue)) {
                    if ($fieldValue['var'] == SCAN_TIME_TYPE) {
                        if ($entry[$fieldValue['var']] != "") {
                            if ($tz != 0) {
                                $entry[$fieldValue['var']] = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($entry[$fieldValue['var']]) + 3600 * $tz);
                            } else {
                                $entry[$fieldValue['var']] = date("Y-m-d H:i:s", strtotime($entry[$fieldValue['var']]));
                            }
                            echo "<td width='70' nowrap>" . $entry[$fieldValue['var']] . "</td>";
                        } else {
                            echo "<td width='70' nowrap>-</td>";
                        }
                    } elseif ($fieldValue['var'] == "hostip") {
                        if ($entry["report_id"] == "All") {
                            echo "<td width='150' style='text-align:center;padding:0px !important;' nowrap><b>" . $entry["report_id"] . "</b></td>";
                        } else if ($entry["hostIP"] != "") {
                            $hip = (($entry["host_name"] != $entry["hostIP"] && $entry["host_name"] != "") ? $entry['host_name'] . " (" . $entry['hostIP'] . ")" : $entry['hostIP']);
                            echo "<td width='150' " . (($image != "") ? "class='nobborder'" : "") . " style='text-align:right;" . (($image != "") ? "padding: 1" : "padding-right:26") . "px !important;' nowrap><strong>" . $hip . (($image != "") ? "&nbsp;<image src=\"../pixmaps/theme/$image\" border=\"1\" align=\"absmiddle\" value='cell$random' $class>" : "") . "</strong></td>";
                        } else {
                            echo "<td width='150' " . (($image == "ltL_nes2.gif") ? "class='nobborder'" : "") . " style='padding:0px !important;text-align:right;background-color:#fafafa;' nowrap><b>&nbsp;" . (($image != "") ? "<image src=\"../pixmaps/theme/$image\" border=\"0\" align=\"absmiddle\">" : "") . "</b></td>";
                        }
                    } elseif ($fieldValue['var'] == "target") {
                        echo "<td width='150' style='text-align:center;' nowrap>" . $entry[$fieldValue['var']] . "</td>";
                    } elseif ($fieldValue['var'] == "vCritical") {
                        echo "<td nowrap><div style='margin-left:7px;float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vHigh") {
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vMed") {
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vLow") {
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div>";
                    } elseif ($fieldValue['var'] == "vInfo") {
                        echo "<div style='float:left;width:18%;'>" . $entry[$fieldValue['var']] . "</div></td>";
                    } else {
                        if ($fieldValue['var'] == "profile"){
                            if ($entry[$fieldValue['var']] == "" && $color != ""){
                                $entry[$fieldValue['var']] = "-";
                            }

                        } elseif ($fieldValue['var'] == "username"){
                            if (valid_hex32($entry[$fieldValue['var']])) {
                                $entry[$fieldValue['var']] = Session::get_entity_name($dbconn, $entry[$fieldValue['var']]);
                            }
                        }

                        echo "<td nowrap>".$entry[$fieldValue['var']]."</td>";
                    }
                } else {
                    echo "<td nowrap>$entry[$fieldValue]</td>";
                }
            } else {
                echo "<td style=\"text-align:center;\" nowrap>";
                foreach ($fieldValue as $linkName => $linkData) {
                    $link = str_replace("%param%", $entry[$linkData['param']], $linkData['url']);
                    if ($link != "") {
                        $onclick = "";
                        $target = "";
                        if (!empty ($linkData['onclick'])) {
                            $onclick = "onclick='" . $linkData['onclick'] . "'";
                        }
                        if (!empty ($linkData['target'])) {
                            $target = "target=\"" . $linkData['target'] . "\"";
                        }

                        if ($link != "") {
                            $report_sid = ($linkName == "HTML Results") ? "id=\"latest_html_report_" . $selenium_id . "\"" : ""; // for selenium tests
                            $report_sid = ($linkName == "DELETE Results") ? "id=\"latest_delete_" . $selenium_id . "\"" : ""; // for selenium tests

                            echo "<a $report_sid style=\"text-decoration:none;\" href=\"$link\" alt=\"$linkName\" title=\"$linkName\" $target>
                        <img src=\"" . $linkData['icon'] . "\" alt=\"$linkName\" $onclick ></img></a>";
                        } else
                            echo "<img src=\"" . $linkData['icon'] . "\" alt=\"$linkName\" $onclick ></img>&nbsp;&nbsp;";
                    }
                }
                echo "</td>";
            }
        }
    }
    echo "</tr>\n";
}


function resolve_asset($conn, $asset, $without_ip = FALSE)
{
    if ($asset == '') {
        return '-';
    }

    if (preg_match("/^([a-f\d]+)#(.*)/i", $asset, $found)) {
        if (Asset_host::is_in_db($conn, $found[1])) {
            $_asset_name = Asset_host::get_name_by_id($conn, $found[1]) . ((!$without_ip) ? " (" . $found[2] . ")" : '');
        } elseif (Asset_net::is_in_db($conn, $found[1])) {
            $_asset_name = Asset_net::get_name_by_id($conn, $found[1]) . ((!$without_ip) ? " (" . $found[2] . ")" : '');
        } else if ($found[2] == 'hostgroup') {
            $_asset_name = Asset_group::get_name_by_id($conn, $found[1]);
            $_asset_name = ($_asset_name == _('Unknown')) ? _('Unknown hostgroup') : $_asset_name;
        } else if ($found[2] == 'netgroup') {
            $_asset_name = Net_group::get_name_by_id($conn, $found[1]);
            $_asset_name = ($_asset_name == _('Unknown')) ? _('Unknown netgroup') : $_asset_name;
        } else {
            $_asset_name = $found[2];
        }
    } else {
        $_asset_name = $asset;
    }

    return $_asset_name;

}

function extract_fields($description)
{
    $fields_regexp = "(Insight|CVSS Base Score|Vulnerability Detection Method|Vulnerability Detection Result|CVSS Base Vector|Summary|Solution|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Detection|Vulnerability Insight|Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)";

    $results = array();

    $description = str_replace("\\r", "", $description);

    $description = str_replace("\n", " ", $description);

    $description = preg_replace("/" . $fields_regexp . "\s*:/", "DELIMITER\\1:", $description);

    $fields = explode('DELIMITER', $description);

    foreach ($fields as $field) {
        if (!empty($field)) {
            preg_match('/^' . $fields_regexp . ':(.*)/', trim($field), $found);

            if (!empty($found[1])) {
                $found[1] = trim($found[1]);

                if (preg_match("/(Synopsis|Overview)/", $found[1])) {
                    $found[1] = 'Overview';
                } else if (preg_match("/(Solution|Fix)/", $found[1])) {
                    $found[1] = 'Fix';
                }

                $results[$found[1]] = trim(strip_tags($found[2]));
            }
        }
    }

    return $results;
}

/**
 * Get an OTX link (HTML) to get information about a CVE Id
 * @param array $matches CVE IDs
 * @return string
 */
function get_cve_link($matches)
{
    $cve_link = Vulnerabilities::get_cve_link($matches[0]);

    return "<a class='otx-link gray' href='" . $cve_link . "' target='_blank'>$matches[0]</a>";
}

/**
 * Get a vulnerability reference link
 * @param array $matches References (URLs)
 * @return string
 */
function get_vuln_reference_link($matches)
{
    return "<a class='xref-link gray' href='" . $matches[0] . "' target='_blank'>$matches[0]</a>";
}
