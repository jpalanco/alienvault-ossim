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

function get_hosts($dbconn) {
    $hosts = array();
    $query = "select distinct inet6_ntop(hi.ip),hex(h.ctx) from host h,host_ip hi where h.id=hi.host_id";
    
    $dbconn->SetFetchMode(ADODB_FETCH_NUM);
    
    if (!$result = & $dbconn->Execute ( $query )) {
        print $dbconn->ErrorMsg();
    } else {
        while (!$result->EOF) {
           $hosts[$result->fields[1]][$result->fields[0]] = 1;
           $result->MoveNext();
        }
    }
    return $hosts;
}

function hosts_to_insert($dbconn, $report_id, $hosts=array()) {
    $in_assets = array();
    $ips = array();

    $query = "SELECT distinct hostIP, HEX(ctx) AS ctx FROM vuln_nessus_results WHERE report_id=?";

    $dbconn->SetFetchMode(ADODB_FETCH_ASSOC);

    if (!$result = & $dbconn->Execute ( $query, array($report_id) )) {
        print $dbconn->ErrorMsg();
    } 
    else 
    {
        while (!$result->EOF) 
        {
            $ip  = $result->fields["hostIP"];
            $ctx = $result->fields["ctx"];   
            
            if (!$hosts[$ctx][$ip])
            {
                $net_id = key(Asset_host::get_closest_net($dbconn, $ip, $ctx));
            
                if (valid_hex32($net_id) && Session::netAllowed($dbconn, $net_id))
                {
                    $ips[] = $ip."#".$ctx;
                } 
            }
            
            $result->MoveNext();
        }
    }
    
    return $ips;
}

function host_ip_name($dbconn){

    $hosts_nets = array();
    
    $_host_data = Asset_host::get_basic_list($dbconn);
    $host_list  = $_host_data[1];
    
    foreach($host_list as $host_id => $host_data) {
        $hips = explode(',', $host_data['ips']);
        
        foreach($hips as $hip) {
            $hosts_nets[$host_id."#".trim($hip)] = $host_data['name'];
        }
    }

    list($net_list, $total) = Asset_net::get_list($dbconn);
    
    foreach($net_list as $net_id => $net_data) {
        $hosts_nets[$net_id."#".$net_data['ips']] = $net_data['name'];
    }

    return $hosts_nets;
}

function tooltip_hosts($hosts,$ipsnames){
    $list = array();
    $result = array();
    $tmp_ipsnames = array();
    
    $out = "";
    
    $tmp_ipsnames = $ipsnames;

    $list = explode("\n", trim($hosts));
    foreach ($list as $host){
        $ohost = $host; // to display asset names with !
        $host = preg_replace('/^\!/', '', $host);
        $host = trim($host);
        
        if( ($ipsnames[$host]!="") && ($host!=$ipsnames[$host]) ){
            if(preg_match("/\d+\.\d+\.\d+\.\d+/",$host)) {
                $result[] = $ohost." (".$ipsnames[$host].")";
            }
            else {
                $result[] = $ipsnames[$host]." (".$ohost.")";
            }
        }
        else
            $result[] = $ohost;
        unset ($ipsnames);
        $ipsnames = $tmp_ipsnames;
    }
    $out = implode("<br>", $result);

    return $out;
}

function subjobs ($name, $scan_submit, $data) {
    $result=array();
    foreach($data as $entry) {
        if($entry['jobname']==$name && $entry['scan_submit']==$scan_submit && $entry['sid']!=0 && $scan_submit!=""){
            $result[] = $entry;
        }
    }
    return $result;
}
function subjobslatest ($ip, $ctx, $data) {
    $result=array();
    foreach($data as $entry) {
        if($entry['hostIP']==$ip && $entry['ctx']==$ctx){
            $result[] = $entry;
        }
    }
    return $result;
}

function status ($arruser, $user) {
    global $username, $dbconn;

    $tz = Util::get_timezone();
    
    $query_onlyuser = "";
    
    if(!empty($arruser)) {$query_onlyuser = " and username in ($user)";}

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $query = "SELECT count(id) as count 
             FROM vuln_jobs 
             WHERE status = 'R'".$query_onlyuser;
    $running =  $dbconn->GetOne($query);
    
    $query = "SELECT count(id) as count 
             FROM vuln_job_schedule WHERE enabled='1'".$query_onlyuser;
    $scheduled =  $dbconn->GetOne($query);
    
    $query = "SELECT count(id) as count 
             FROM vuln_jobs 
             WHERE status = 'C'".$query_onlyuser;
    $complete =  $dbconn->GetOne($query);

    $query = "SELECT count(id) as count 
             FROM vuln_jobs 
             WHERE status = 'F'".$query_onlyuser;
    $failed =  $dbconn->GetOne($query);
    
    $query = "SELECT count(id) as count 
             FROM vuln_jobs 
             WHERE status = 'T'".$query_onlyuser;
             
    $timeout =  $dbconn->GetOne($query);
    
    
    echo "<center><table cellspacing=\"0\" style=\"margin-top:8px;\" class=\"headerpr_no_bborder\" cellpadding=\"0\" border=\"0\" width=\"90%\"><tr><td style=\"border:0;\">"._("Status")."</td></tr></table></center>";
    echo "<center><table cellspacing=\"2\" cellpadding=\"0\" border=\"0\" width=\"90%\">";
    echo "<tr><th>"._("Running")."</th><th>"._("Scheduled")."</th><th>"._("Completed")."</th><th>"._("Failed")."</th><th>"._("Timed Out")."</th><th width='452'>"._("Scan Server Status")."</th></tr>";
    echo "<tr>";
    if ($running==0){
        echo "<td class=\"nobborder\" style=\"text-align:center;\">$running</td>";
    }
    else {
        $query = "SELECT t1.id, t1.name,t1.scan_START, s.name
               FROM vuln_jobs t1
               LEFT JOIN vuln_nessus_servers t2 ON t1.scan_SERVER = t2.id
               LEFT JOIN sensor s ON t2.hostname=HEX(s.id)
               WHERE t1.status='R'".((empty($arruser))? "":" AND t1.username in (".$user.")")."
               ORDER BY t1.scan_START DESC";
               
        echo "<td class=\"nobborder\" style=\"text-align:center;\">";
        echo "<div class=\"balloon\">";
        echo "  <a href=\"javascript:;\">$running</a>";
        echo "  <span class=\"tooltip\">";
        echo "      <span class=\"top\"></span>";
        echo "      <span class=\"middle ne11\">";
        $result = $dbconn->Execute($query);
        while ( !$result->EOF ) {
            $tstatus = array("status"=>"", "progress"=>"");
            
            if($_SESSION["scanner"]=="omp") {
                
                $uuid = Util::get_encryption_key();

                $result_server = $dbconn->Execute("SELECT meth_Wcheck FROM vuln_jobs WHERE id=".$result->fields['id'] );
                
                preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/", $result_server->fields['meth_Wcheck'], $found);
                
                $result_server_properties = $dbconn->Execute("SELECT port, user, AES_DECRYPT(password,'".$uuid."') as password FROM vuln_nessus_servers WHERE hostname='".$found[1]."'" );

                $omp = new Omp($found[1], $result_server_properties->fields['port'], $result_server_properties->fields['user'], $result_server_properties->fields['password']);
                
                $tstatus = $omp->get_task_status($result->fields['id']);
            }
            
            if($tz!=0) {
                $result->fields['scan_START'] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($result->fields['scan_START'])+(3600*$tz));
            }
            
            if( preg_match("/requested$/i", $tstatus["status"]) ) {
                echo "<img src=\"./images/requested.png\" boder=\"0\" title=\""._("Requested")."\" alt=\""._("Requested")."\" align=\"absmiddle\"/> ".$result->fields['name']."<br><b>"._("Start at").":</b><br>".$result->fields['scan_START']."<br><b>"._("IP Server").":</b><br>".$result->fields['name']."<br><br>";
            }
            else if($tstatus["status"]=="Paused") {
                echo "<img src=\"./images/paused.png\" boder=\"0\" title=\""._("Paused")."\" alt=\""._("Paused")."\" align=\"absmiddle\"/> ".$result->fields['name']."<br><b>"._("Start at").":</b><br>".$result->fields['scan_START']."<br><b>"._("IP Server").":</b><br>".$result->fields['name']."<br><br>";
            }
            else {
                echo "<img src=\"./images/running.png\" boder=\"0\" title=\""._("Running")."\" alt=\""._("Running")."\" align=\"absmiddle\"/> ".$result->fields['name']."<br><b>"._("Start at").":</b><br>".$result->fields['scan_START']."<br><b>"._("IP Server").":</b><br>".$result->fields['name']."<br><br>";
            }
            $result->MoveNext();
        }
        echo "      </span>";
        echo "      <span class=\"bottom\"></span>";
        echo "  </span>";
        echo "</div>";
        echo "</td>";
    }
    if ($scheduled==0){
        echo "<td class=\"nobborder\" style=\"text-align:center;\">$scheduled</td>";
    }
    else {
        echo "<td class=\"nobborder\" style=\"text-align:center;\"><a href=\"".Menu::get_menu_url('manage_jobs.php', 'environment', 'vulnerabilities', 'scan_jobs')."\" target='main'>$scheduled</a></td>";
    }
    echo "<td class=\"nobborder\" style=\"text-align:center;\">$complete</td>";
    echo "<td class=\"nobborder\" style=\"text-align:center;\">$failed</td>";
    echo "<td class=\"nobborder\" style=\"text-align:center;\">$timeout</td>";
    //$query = "SELECT hostname, status
    //           FROM vuln_nessus_servers
    //           WHERE enabled='1' AND TYPE='L'";
    //$result = $dbconn->Execute($query);
    //switch ($result->fields['status']) {
    //    case "N":
    //        $srv_stat_desc= "<font color=red>"._("Off-Line")."</font>";
    //        break;
    //    case "C":
    //        $srv_stat_desc= "<font color=gray>"._("Checking")."</font>";
    //        break;
    //    case "A":
    //        $srv_stat_desc= "<font color=darkgreen>"._("On-Line")."</font>";
    //        break;
    //    default:
    //        $srv_stat_desc= "<font color=gray>"._("Unknown")."</font>";
    //        break;
    //}
    echo "<td class=\"nobborder\" style=\"text-align:center;\">";
    echo "<table width=\"100%\" class=\"noborder\"><tr><td id=\"td1\" width=\"40%\" class=\"nobborder\" style=\"text-align:center\">";
    echo "<div class=\"bballoon\">";
    echo "  <a id=\"status_server\" href=\"#\"></a>";
    echo "  <span class=\"tooltip\">";
    echo "      <span class=\"top\"></span>";
    echo "      <span class=\"middle ne11\" style=\"text-align:left;\">";
    echo "          <table class=\"noborder\" width=\"100%\" style=\"background-color:transparent;\">";
    echo "              <tr><td class=\"noborder\">";
    echo "                  <table class=\"noborder\" style=\"background-color:transparent\">";
    if ($_SESSION["scanner"]!="omp") {
        echo "                  <tr><td class=\"nobborder\"><b>"._("Current Threads").":</b></td><td class='nobborder' style='font-size:11px;padding-left:5px' id='nta'></td></tr>";
    }
    echo "                   </table>";
    echo "              </td></tr>";
#    echo "              <tr height='8'><td class=\"noborder\" style='text-align:left;padding-left:5px'></td></tr>";
    echo "              <tr><td class=\"noborder\" id='nmta' style='font-size:10px;text-align:left'>";
    #echo "                  <table class=\"noborder\" width=\"100%\" style=\"background-color:transparent\">";
    #echo "                       <tr><td class=\"nobborder\"><b>"._("Nmap Threads").":</b></td><td class=\"center\" id='nmta' style='font-size:11px;'></td></tr>";
    #echo "                   </table>";
    echo "              </td></tr>";
    #echo "              <tr height='8'><td class=\"noborder\"><div id='nmap_threads'><font color=\"#222222\"><img width=\"16\" align=\"absmiddle\" src=\"./images/loading.gif\" border=\"0\" alt=\""._("Loading")."\" title=\""._("Loading")."\">&nbsp;&nbsp;"._("Loading")."...</font></div></td></tr>";
    echo "          </table>";
    echo "      </span>";
    echo "      <span class=\"bottom\"></span>";
    echo "  </span>";
    echo "</div>";
    echo "</td><td id=\"td2\" width=\"60%\" class=\"nobborder\">";
    echo "<div id='nessus_threads'><font color=\"#222222\"><img width=\"16\" align=\"absmiddle\" src=\"./images/loading.gif\" border=\"0\" alt=\""._("Loading")."\" title=\""._("Loading")."\">&nbsp;&nbsp;"._("Loading")."...</font></div>";
    echo "</tr>";
    echo "</table>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
}

// sanitize the form input
function sanitize($input) {
   $output = $tmp = "";
   if(get_magic_quotes_gpc()) {
      $tmp = stripslashes(trim($input));
   } else {
      $tmp = $input;
   }
   $output = mysql_real_escape_string($tmp);
   $output = htmlspecialchars( $output , ENT_QUOTES);
   return $output;
}

function stats_severity_services($type, $filter="", $graph="severity") {
    global $dbconn, $arruser, $user;
    
    $perms_where = Asset_host::get_perms_where('host.', TRUE);
	
	if( $graph != 'severity' && $graph != 'service' ) {
		$graph = 'severity';
	}
    
    $severity = array();
    $colors = array();
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    if ($type == 'hn')
    {
        if (preg_match('/\//',$filter))
        {
            $ips    = array_shift(array_values(Asset_net::expand_cidr($filter, 'SHORT', 'LONG')));
            $filter = " AND inet_aton(lr.hostIP) BETWEEN '".$ips[0]."'AND '".$ips[1]."'";
        }
        else if($filter != '')
        {
            $filter = " AND lr.hostIP LIKE '$filter'";
        }
        else {
            $filter = "";
        }
    }
    else if ($type == 'service')
    {
        $filter = "AND lr.service LIKE '%$filter%'";
    
    }
    else if ($type == 'freetext')
    {
        $filter = "AND lr.msg LIKE '%$filter%'";
    }
     
    if (!empty($perms_where))
    {
        $query = "SELECT count(lr.result_id) AS total, lr.risk
                  FROM vuln_nessus_latest_results lr, host, host_ip hi
                  WHERE host.id=hi.host_id AND inet6_ntop(hi.ip)=lr.hostIP $perms_where $filter AND lr.falsepositive='N' GROUP BY lr.risk";             
    }
    else
    {
        $query = "SELECT count(lr.result_id) AS total, lr.risk
                  FROM vuln_nessus_latest_results lr
                  WHERE 1=1 $filter AND lr.falsepositive='N' GROUP BY lr.risk";        
    }
    
    $result = $dbconn->Execute($query);
    
    while ( !$result->EOF ) {
        if($result->fields["risk"]==7){
            $severity["Info"]+=$result->fields["total"];
            if($colors["#F0E68C"]=="") {
                $colors["#F0E68C"]++;
            }
        }
        else if($result->fields["risk"]==6){
            $severity["Low"]+=$result->fields["total"];
            if($colors["#FFD700"]=="") {
                $colors["#FFD700"]++;
            }
        }
        else if($result->fields["risk"]==3){
            $severity["Medium"]+=$result->fields["total"];
            if($colors["#FFA500"]=="") {
                $colors["#FFA500"]++;
            }
        }
        else if($result->fields["risk"]==2){
            $severity["High"]+=$result->fields["total"];
            if($colors["#FF0000"]=="") {
                $colors["#FF0000"]++;
            }
        }
        else if($result->fields["risk"]==1){
            $severity["Serious"]+=$result->fields["total"];
            if($colors["#C835ED"]=="") {
                $colors["#C835ED"]++;
            }
        }

        $result->MoveNext();
    }
    $lcolors = "\"".implode("\",\"", array_keys($colors))."\"";

    $services = array();
    
    if (!empty($perms_where))
    {
        $query = "SELECT count(lr.result_id) AS total, lr.port, lr.protocol, lr.app
                    FROM vuln_nessus_latest_results lr, host, host_ip hi
                    WHERE host.id=hi.host_id AND inet6_ntop(hi.ip)=lr.hostIP $perms_where $filter AND lr.falsepositive='N'
                    GROUP BY lr.port, lr.protocol, lr.app HAVING lr.port>0 ORDER BY total";
    }
    else
    {
        $query = "SELECT count(lr.result_id) AS total, lr.port, lr.protocol, lr.app
                    FROM vuln_nessus_latest_results lr
                    WHERE 1=1 $filter AND lr.falsepositive='N' GROUP BY lr.port, lr.protocol, lr.app HAVING lr.port>0 ORDER BY total";
    }

    $result = $dbconn->Execute($query);
    
    $i = 0;
    while ( !$result->EOF && $i <10) {
        $services[$result->fields['app']." (".$result->fields['protocol']."/".$result->fields['port'].")"] = $result->fields['total'];
        $i++;
        $result->MoveNext();
    }
?>
<style type='text/css'>
.pieLabel div{
	font-size: 10px;
	border: 1px solid gray;
	background: #f2f2f2;
	padding: 1px;
	text-align: center;
}
.legendColorBox { border:0 none; }
.legendLabel { border:0 none; }
div.legend table { border:0 none; }
div.legend td { text-align:left; font-size:10px }

#services_pie, #severity_pie {
	width:395px;
	height:223px;
	text-align:left;
	padding:15px 0px 0px 0px;
}
</style>

<table cellpadding="0" cellspacing="0" style="width:100%;" height="268" align="center" class="transparent">
    <tr>
	<?php
	if($graph == "severity") 
	{ 
    	?>
		<td class="noborder" valign="top" >
			<table cellpadding="0" cellspacing="0" width="100%" align="center" height="250" class="transparent">
			     <tr>
			         <td>
            			<?php
            			if(count($severity)>0) 
            			{
                			?>
            				<div id="severity_pie"></div>
            				<?php
                	    }
                	    else 
                	    {                    	       
                	        ?>
            				<div style="width:100%;padding:0px 3px 0px 0px;" class="grey"><?php echo _("No results found")?></div>
            				<?php
            		    }
            		    ?>
            		    </td>
                   </tr>
             </table>
		</td>
		<?php
	}
	
	if( $graph == "service" ) 
	{
	    ?>
		<td class="noborder" valign="top">
            <table cellpadding="0" cellspacing="0" width="100%" align="center" height="250" class="transparent">
                <tr>
                    <td class="noborder" align="center" id="hostcontainer">
                    <?php
                    if(count($services)>0) 
                    {
                        ?>
                        <div id="services_pie"></div>
                        <?php
                    } 
                    else 
                    {
                        ?>
                        <div style="width:100%;color:#17457C;"><?php echo _("No results found")?></div>
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
    if( count($severity)>0 && $graph == "severity" ) {
    ?>
		$.plot($("#severity_pie"), [
		<? $i=0;foreach ($severity as $sev => $val) { ?>
			<?=($i++==0) ? "" : ","?>{ label: "<?=$sev.' ['.$val.']'?>",  data: <?=$val?>}
		<? } ?>
		], 
		{
			pie: { 
				show: true, 
				pieStrokeLineWidth: 1, 
				pieStrokeColor: '#FFF', 
				pieChartRadius: 90, 			// by default it calculated by 
				//centerOffsetTop:30,
				centerOffsetLeft:80, 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
				showLabel: true,				//use ".pieLabel div" to format looks of labels
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
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
    if( count($services)>0 && $graph == "service" ) {				
    ?>
		$.plot($("#services_pie"), [
		<? 
		$i=0;foreach ($services as $service => $val) {
            $ssearch = array();
            $ssearch = explode(" ", $service);
		?>		
			<?=($i++==0) ? "" : ","?>{ label: "<a href='<?php echo Menu::get_menu_url('index.php?value='.urlencode($ssearch[0]).'&type=service', 'environment', 'vulnerabilities')?>'><?=$service." [".$val."]"?>",  data: <?=$val?>}
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
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
            colors: ["#ACD7E6","#79CCE9","#99BBDB","#6BB1E9","#758AC9","#4C74E9","#4D56B5","#2A33E8","#3A39AA","#2020E8"],
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
function stats_networks_hosts($type, $filter="", $graph="hosts") {
    global $username, $uroles, $dbconn, $arruser, $user;
    
    $perms_where = Asset_host::get_perms_where('host.', TRUE);
	
	if($graph != "hosts" && $graph != "networks" ) {
		$graph = "hosts";
	}
	
	$dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    if($filter!="" && preg_match('/\//',$filter))
    {        
        $ips = array_shift(array_values(Asset_net::expand_cidr($filter, 'SHORT', 'LONG')));
        
        $filter = " AND inet_aton(lr.hostIP) BETWEEN '".$ips[0]."' AND '".$ips[1]."'";
    } 
    elseif ($type=="hn")
    {
        if (preg_match("/\,/",$filter)) {
          $q = implode("','",explode(",",$filter));
          $filter = " AND lr.hostIP IN ('$q')";
        }
        else if($filter!=""){
            $filter = " AND lr.hostIP LIKE '$filter'";
        }
        else {
            $filter = "";
        }
    }
    
    $queryf = "";

    // top 10 hosts and networks
    $top10h = $top10n = $nips = $others = array();
    
    if($type=="hn" && $filter!="") {
        $queryf = $filter;
    }
    else if($type=="service"){
        $queryf = " and lr.service like '%$filter%'";
    }
    else if($type=="freetext") {
        $queryf = " and lr.msg like '%$filter%'";
    }

    if (!empty($perms_where))
    {
        $query = "SELECT count(lr.result_id) as vulnerability, lr.hostip, HEX(lr.ctx) as ctx FROM 
                vuln_nessus_latest_results lr, host, host_ip hi
                WHERE host.id=hi.host_id AND inet6_ntop(hi.ip)=lr.hostIP $perms_where $queryf AND falsepositive='N'
                GROUP BY hostip ORDER BY vulnerability DESC LIMIT 1000";
    }
    else
    {
        $query = "SELECT count(lr.result_id) as vulnerability, lr.hostip, HEX(lr.ctx) as ctx FROM 
                vuln_nessus_latest_results lr
                WHERE 1=1 $queryf AND falsepositive='N'
                GROUP BY hostip ORDER BY vulnerability DESC LIMIT 1000";
    }
    
    $result = $dbconn->Execute($query);

    while (!$result->EOF) 
    {
        $ip      = $result->fields["hostip"];
        $ctx     = $result->fields["ctx"];
        
        $id      = key(Asset_host::get_id_by_ips($dbconn, $ip, $ctx));
        
        $hkey    = '';
        
        if(valid_hex32($id))
        {
            $hkey = $id;
        }
        else
        {
            $hkey = "$ctx#$ip";
        }
        
        if (!isset($top10h[$hkey])) 
        {        
            $top10h[$hkey]+=$result->fields["vulnerability"];
            
            $net    = Asset_host::get_closest_net($dbconn, $ip, $ctx);
            $net_id = key($net);            

            if (valid_hex32($net_id))
            {
                $netkey           = $net_id;
                $ips              = $net[$net_id]['ips'];
                
                $nips[$netkey]    = $ips;
                $top10n[$netkey] += $result->fields["vulnerability"];
            }
            else if (trim($ip) !='' && trim($ctx) !='')
            {
                $others["$ctx#$ip"] = trim($ip);
                $top10n["Others"]  += $result->fields["vulnerability"];
            }
        }
        
        $result->MoveNext();
    }    

    arsort($top10h);
    arsort($top10n);
    $maxn = 0;
    $maxh = 0;
    foreach($top10h as $key => $value) 
    {
        $maxh+=$value;
    }
    
    foreach($top10n as $key => $value) 
    {
        $maxn+=$value;
    }
?>
<table cellpadding=0 cellspacing=0 width="100%" class="transparent">
<tr>
<?php
$empty_message = "<tr><td class='nobborder' height='200' style='text-align:center'><div style='padding-top:48px;width:100%;' class='grey'>"._("No results found")."</div></td></tr>";

if ($graph == "networks") { ?>
    <td class="noborder" valign="top">
        <table cellpadding="0" cellspacing="0" width="100%" height="220" class="transparent">
            <tr><td valign="top" class="nobborder" style="padding-bottom:6px;">
                <table cellpadding="0" cellspacing="0" width="100%" class="transparent">
                    <? $i=0;foreach ($top10n as $net_id => $val) {
                        if ($i<10 && $net_id != '')
                        {
                            $i++;
                            $fnets=1;
                            if(preg_match("/^[a-f\d]{32}$/i",$net_id))
                            {
                                $net_name = Asset_net::get_name_by_id($dbconn, $net_id);
                            }
                            else
                            {
                                $net_name = $net_id;
                            }
                            ?>
                            <tr>
                                <td class="noborder" style="text-align:right;padding:5px 5px 0px 5px;" valign="top" width="50%">
								<?php
									$link_text  = ($nips[$net_id]!="" && $nips[$net_id]!=$net_name)? $net_name : $nips[$net_id];
                                    if( $link_text == "" ){
                                        $link_text = "Others";
                                        $nips[$net_id] = "";
                                    }
									$link_text .= " [$val]";
								?>
                                    <a <?php echo ($nips[$net_id]!="" && $nips[$net_id]!=$net_name)? " class='tip' title='".$nips[$net_id]."'" : "" ?> style='font-size:10px;' href="<?php echo Menu::get_menu_url('index.php?value='.(($nips[$net_id]!="") ? $nips[$net_id] : implode(",", $others)).'&type=hn', 'environment', 'vulnerabilities') ?>"><?php echo $link_text ?></a>
                                </td>
                                <td class="noborder" width="50%" valign="top" style="text-align:left;padding:5px 5px 0px 5px;">
                                <?
                                    $width = intval(($val*205)/$maxn);
                                    echo "<div style=\"border: 1px solid rgb(204, 204, 204); padding: 1px;\"><img src=\"../pixmaps/risk_blue.jpg\" width=\"".$width."px\" height=\"15\" /></div>";
                                ?>
                                </td>
                            </tr>
                           <?
                        }
                    }
                    if($fnets!=1) {
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
            <tr><td valign="top" class="nobborder" style="padding-bottom:6px;">
                <table cellpadding=0 cellspacing=0 width="100%" class="transparent">
                    <? $i=0;foreach ($top10h as $host => $val) {
                        $name        = "";
                        
                        if(preg_match("/^[\da-f]{32}$/i", $host))
                        {  
                            // Fill host name
                            $name        = Asset_host::get_name_by_id($dbconn, $host);
                            $ctx         = Asset_host::get_ctx_by_id($dbconn, $host);
                            $hip         = Asset_host_ips::get_ips_to_string($dbconn, $host);
                        }
                        else if(preg_match("/[\da-f]{32}#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i", $host))
                        {
                            // Fill host name
                            list($ctx, $hip) = explode("#", $host);
                            $name            = $hip;
                        }
                        
                        if ($i<10) {
                            $fhosts = 1;
                            $i++;
                            
                            ?>
                            <tr>
                                <td class="noborder" style="text-align:right;padding:5px 5px 0px 5px;" valign="top" width="50%">
									<?php
										$link_text  = ($hip!="" && $hip!=$name)? $name : $hip;
										$link_text .= " [$val]";
									?>
                                    <a <?php echo ($hip!="" && $hip!=$name)? " class='tip' title='".$hip."'" : "" ?> style='font-size:10px;' id='<?php echo (preg_match("/^[\da-f]{32}$/i", $host) ? $host: $hip).";".$name.";".((preg_match("/^[\da-f]{32}$/i", $host)) ? $host : ''); ?>' ctx='<?php echo $ctx;?>' class='HostReportMenu' href="<?php echo Menu::get_menu_url('index.php?value='.$hip.'&type=hn', 'environment', 'vulnerabilities')?>"><?php echo $link_text;?></a>
                                </td>
                                <td class="noborder" width="50%" valign="top" style="text-align:left;padding:5px 5px 0px 5px;">
                                <?
                                    $width = intval(($val*210)/$maxh);
                                    echo "<div style='border: 1px solid rgb(204, 204, 204); padding: 1px;'><img src='../pixmaps/risk_light_blue.jpg' width='".$width."px' height='15'/></div>";
                                ?>
                                </td>
                            </tr>
                        <?}
                    }
                    if($fhosts!=1) {
				        echo $empty_message;
                    }
                    ?>
                </table>
            </td></tr>
        </table>
    </td>
	<?php
	}
	?>
</tr>
</table>
<?
}
function stats() {
    global $username, $uroles, $dbconn;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    // top 10 hosts and networks
    $top10h = $top10n = $nips = array();
    $query = "select count(*) as vulnerability,hostip from (select distinct hostip,port,protocol,app,scriptid,msg,risk from vuln_nessus_latest_results) as t group by hostip";
    $result = $dbconn->Execute($query);
    
    while (!$result->EOF) 
    {
        $ip = $result->fields["hostip"];
        
        if (!isset($top10h[$ip])) 
        {
            $top10h[$ip]+=$result->fields["vulnerability"];
            
            $net    = Asset_host::get_closest_net($dbconn, $ip);
            $net_id = key($net);
            
            if (valid_hex32($net_id))
            {
                $ips = $net[$net_id]['ips'];
                $net = "$ips [$net]";
                $nips[$net] = $ips;
                $top10n[$net]+=$result->fields["vulnerability"];
            }
            else
            {
                $top10n["Others"]+=$result->fields["vulnerability"];
            }
        }
        $result->MoveNext();
    }    

	arsort($top10h);
	arsort($top10n);
?>
<style>
.pieLabel div{
	font-size: 10px;
	border: 1px solid gray;
	background: #f2f2f2;
	padding: 1px;
	text-align: center;
}
.legendColorBox { border:0 none; }
.legendLabel { border:0 none; }
div.legend table { border:0 none; }
div.legend td { text-align:left; font-size:11px }
</style>

<table cellpadding=0 cellspacing=0 width="900" align="center" class="noborder">
<tr>
	<td class="headerpr" width="49%"><?= _("Top 10 Networks");?></td>
	<td width="15" class="noborder"></td>
	<td class="headerpr" width="49%"><?= _("Top 10 Host");?></td>
</tr>
<tr>
	<td class="noborder">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 30px" align="center">
		<div id="nets_pie" style="width:395px;height:190px"></div>
		</td></tr></table>
	</td>
	<td width="15" class="noborder"></td>
	<td class="noborder">
		<table cellpadding=0 cellspacing=0 width="100%" align="center"><tr><td class="noborder" style="padding:5px 5px 5px 50px" align="center" id="hostcontainer">
		<div id="hosts_pie" style="width:395px;height:190px;text-align:left;"></div>
		</td></tr></table>
	</td>
</tr>
</tr></table>

<script>
	$(function () {
		$.plot($("#nets_pie"), [
		<? $i=0;foreach ($top10n as $host => $val) if ($i<10 && Session::netAllowed($dbconn, $nips[$host], $_SESSION['_user'])) { ?>
			<?=($i++==0) ? "" : ","?>{ label: "<a href=\"<?php echo Menu::get_menu_url('index.php?value='.urlencode($nips[$host]).'&type=net', 'environment', 'vulnerabilities') ?>\"><?=(GET('value')==$nips[$host]) ? "<b>$host</b>" : $host?> [<?=$val?>]</a>",  data: <?=$val?>}
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
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
			colors: ["#ADD8E6","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#00008B"],
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
		$i=0;foreach ($top10h as $host => $val) if ($i<10 && Session::hostAllowed($dbconn, $host)) {
		$query = "select hostname from host where ip='$host'";
        $resname = $dbconn->Execute($query);
        if(!$resname->EOF && $host!=$resname->fields[0]) $name = $resname->fields[0];
           else $name = "";
		?>
			<?=($i++==0) ? "" : ","?>{ label: "<a target='main' href=\"<?php echo Menu::get_menu_url('index.php?value='.urlencode($host).'&type=hostip', 'environment', 'vulnerabilities') ?>\"><?=(GET('value')==$host) ? "<b>" : ""?><?=$name?><?=(($name!="")? " (":"")?><?=$host?><?=(($name!="")? ")":"")?><?=(GET('value')==$host) ? "</b>" : ""?> [<?=$val?>]</a>",  data: <?=$val?>}
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
				labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
				//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
				labelBackgroundOpacity: 0.55, 	// default is 0.85
				labelFormatter: function(serie){// default formatter is "serie.label"
					//return serie.label;
					//return serie.data;
					//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
					return Math.round(serie.percent)+'%';
				}
			},
			colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
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
			data: { net: ips },
			url: "get_hostpie.php",
			success: function(msg) {
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
						labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
						//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
						labelBackgroundOpacity: 0.55, 	// default is 0.85
						labelFormatter: function(serie){// default formatter is "serie.label"
							//return serie.label;
							//return serie.data;
							//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
							return Math.round(serie.percent)+'%';
						}
					},
					colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
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

function all_jobs($offset, $pageSize, $status=""){
    global $username, $uroles, $dbconn, $hosts;
    
    $statuses = array('C' => _('Completed'),
                      'R' => _('Running'),
                      'S' => _('Schedule'),
                      'F' => _('Failed'),
                      'T' => _('Timeout expired')
                     );
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    $conf  = $GLOBALS["CONF"];

    $tz   = Util::get_timezone();
    $uuid = Util::get_encryption_key();

    $out     = 0;
    $message = "";

    $version     = $conf->get_conf("ossim_server_version");
    $nessus_path = $conf->get_conf("nessus_path");

    list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);
    //print_r($entities);
    // all jobs

    if (empty($arruser))
    {
         $query = "SELECT t1.meth_CPLUGINS, t1.failed_attempts, t1.meth_Wcheck, t6.name as profile, t5.report_key, t1.report_id, t1.scan_NEXT, t1.meth_TARGET, t5.scantime, t1.failed_attempts, t1.scan_PRIORITY, t1.scan_PID, t1.id, t1.name, t1.username, t1.scan_START, t1.scan_END, 
                       t1.scan_SUBMIT, t1.status, t1.notify, t3.hostname
                   FROM vuln_jobs t1
                   LEFT JOIN vuln_nessus_servers t3 on t1.scan_ASSIGNED = t3.id
                   LEFT JOIN vuln_nessus_reports t5 on t1.report_id = t5.report_id
                   LEFT JOIN vuln_nessus_settings t6 on t1.meth_VSET=t6.id WHERE ".(($status=="")? "t1.status!='R'":"t1.status='R'")."
                   ORDER BY t1.id DESC LIMIT $offset,$pageSize";
    }
    else 
    {
         $query = "SELECT t1.meth_CPLUGINS, t1.failed_attempts, t1.meth_Wcheck, t6.name as profile, t5.report_key, t1.report_id, t1.scan_NEXT, t1.meth_TARGET, t5.scantime, t1.failed_attempts, t1.scan_PRIORITY, t1.scan_PID, t1.id, t1.name, t1.username, t1.scan_START, t1.scan_END, 
                       t1.scan_SUBMIT, t1.status, t1.notify, t3.hostname
                   FROM vuln_jobs t1
                   LEFT JOIN vuln_nessus_servers t3 on t1.scan_ASSIGNED = t3.id
                   LEFT JOIN vuln_nessus_reports t5 on t1.report_id = t5.report_id 
                   LEFT JOIN vuln_nessus_settings t6 on t1.meth_VSET=t6.id
                   WHERE t1.username in ($user) AND ".(($status=="")? "t1.status!='R'":"t1.status='R'")."
                   ORDER BY t1.id DESC LIMIT $offset,$pageSize";
    }
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    $result = $dbconn->Execute($query);
    $sInfo .= "<center><table style=\"margin-top:5px;\" class=\"w100 transparent\"><tr><td class=\"sec_title\">".(($status=="") ? _("All Scans") : _("Running Scans"))."</td></tr></table>";


    if( !$result->EOF ) 
    {
        $out = 1;
        $selenium_id = 0;

        $sInfo .= "<table class=\"table_list\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">";
        $sInfo .= "<tr>
              <th>"._("Status")."</th>
              <th>"._("Job Name")."</th>
              <th>"._("Launch Time")."</th>
              <th>"._("Scan Start Time")."</th>
              <th>"._("Scan End Time")."</th>
              <th>"._("Scan Time")."</th>
              <th>"._("Next Scan")."</th>
              <th>"._("Action")."</th>
              </tr>";



        while ( !$result->EOF ) 
        {
            list ($task_id, $failed_attempts, $meth_Wcheck, $profile, $report_key, $report_id, $scan_next, $meth_TARGET, $id_report, $failed_attempts, $scan_PRIORITY,
                  $scan_PID, $nid, $nname, $nusername, $start_date, $end_date, $submit_date, $nstatus, $nnotify, $shostname) = $result->fields;
            
            $targets_to_resolve = explode("\n",$meth_TARGET);
            $ttargets           = array();
        
            foreach($targets_to_resolve as $id_ip) 
            {
                if(preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+\/\d{1,2}$/i", $id_ip, $found)) 
                {                    
                    if(Asset_net::is_in_db($dbconn, $found[1])) {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip) ." (" . Asset_net::get_name_by_id($dbconn, $found[1]) . ")";
                    }
                    else {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
                    }
                }
                else if(preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+$/i", $id_ip, $found)) 
                {
                    if(Asset_host::is_in_db($dbconn, $found[1])) {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip)." (". Asset_host::get_name_by_id($dbconn, $found[1]) . ")";
                    }
                    else {
                        $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip);
                    }
                }
                else{
                    $ttargets[] = $id_ip;
                }
            }
        
            $meth_TARGET = implode("\n", $ttargets);
            
            $ustart_date = $start_date; // save start_date in UTC to display scan time
            
            if( $tz != 0 ) 
            {
                if(!is_null($submit_date)) {
                    $submit_date = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($submit_date)+(3600*$tz));
                }
                if(!is_null($start_date)) {
                    $start_date  = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($start_date)+(3600*$tz));
                }
                if(!is_null($end_date)) {
                    $end_date    = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($end_date)+(3600*$tz));
               }
               if(!is_null($scan_next)) {
                    $scan_next   = gmdate("YmdHis",Util::get_utc_unixtime($scan_next)+(3600*$tz));
                }
            }
        
            if( $nnotify != "" ) 
            {
                if(Av_sensor::is_in_db($dbconn, $nnotify))
                {
                    $shostname = Av_sensor::get_name_by_id($dbconn, $nnotify);
                }
                else
                {
                    $shostname = _("unknown");
                }
            }

            if( $shostname == "" ) {
                $shostname = "-";
            }
            
            $query = "SELECT DISTINCT risk, port, protocol, app, scriptid, msg, hostIP, HEX(ctx) as ctx FROM vuln_nessus_results
                        WHERE report_id in ($report_id) AND scriptid <> 10180 AND falsepositive='N'";
            
            $vulns           = -1;    // vulns to display
            $vulns_in_report = false;
            
            if (! $resv = $dbconn->Execute ( $query )) {
                print 'error: ' . $conn->ErrorMsg() . '<BR>';
                exit ();
            }
            while (! $resv->EOF) {
                $vulns_in_report = true;
                if(Session::hostAllowed_by_ip_ctx($dbconn, $resv->fields["hostIP"], $resv->fields['ctx'])) { 
                    $vulns = ($vulns==-1) ? 1 : $vulns+1;
                }
                $resv->MoveNext ();
            }
           

            if ( $start_date && $end_date ) 
            {
                if ( $scntHosts ) {
                    $nhosts = $scntHosts;
                }
               
                $scantime = $end_date;
                $scantime = str_replace("-", "", $scantime);
                $scantime = str_replace(" ", "", $scantime);
                $scantime = str_replace("/", "", $scantime);
                $scantime = str_replace(":", "", $scantime);

                if ( $siScantime ) {
                    $nmins = "$siScantime mins";
                } 
                else { 
                    $nmins = datediff("n", $end_date, $start_date ) . " mins";
                }
               
                if ($uroles['admin'] || $uroles['uadmin']) {
                    $buttons = "<a href=\"sched.php?disp=delete_scan&job_id=$nid\" onclick=\"return confirmDelete();\"><img align=\"absmiddle\" src=\"images/delete.gif\" title=\""._("Delete")."\" alt=\""._("Delete")."\" border=0></a>";
                }
            } 
            elseif ( $start_date ) 
            {
               $buttons = "";
               $curren_date = gmdate("Y-m-d H:i:s");
               
               $nmins = "RUN..>" . datediff("n", $curren_date, $ustart_date );
               $nmins .= " mins";
            } 
            elseif ( $submit_date ) 
            {
               $buttons = "<img src=\"images/sched.png\" title=\""._("JobID").": $nid, "._("scan_START").": $start_date, "._("scan_PID").": $scan_PID, "._("scan_PRIORITY").": $scan_PRIORITY, "._("failed_attempts").": $failed_attempts\" alt=\""._("JobID").": $nid, "._("scan_START").": $start_date, "._("scan_PID").": $scan_PID, "._("scan_PRIORITY").": $scan_PRIORITY, "._("failed_attempts").": $failed_attempts\" border=0>";
               $nmins = "&nbsp";
            } 
            else 
            { 
               $nmins = "&nbsp";
               $start_date = "";
               $end_date = "<i>"._("Never")."</i>";
            }

            $job_status = "";
          
            $tstatus = array("status"=>"", "progress"=>"");
          
            if (preg_match("/omp\s*$/i", $nessus_path) && $task_id!="" && $status=="R") 
            {
                preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/", $meth_Wcheck, $found);
                
                $sensor_id = Av_sensor::get_id_by_ip($dbconn, $found[1]);
                
                $result_server_properties = $dbconn->Execute("SELECT port, user, AES_DECRYPT(password,'".$uuid."') as dpassword, password FROM vuln_nessus_servers WHERE hostname='".$sensor_id."'" );
                
                $password = ($result_server_properties->fields['dpassword']!="") ? $result_server_properties->fields['dpassword']:$result_server_properties->fields['password'];
                
                $omp = new Omp($found[1], $result_server_properties->fields['port'], $result_server_properties->fields['user'], $password);
                
                $tstatus = $omp->get_task_status($nid);
                
                if($tstatus["status"]!="") {
                    $job_status = "<b>"._("Job Status")."</b>: ".$tstatus["status"].(($tstatus["progress"]!=-1 && $tstatus["status"]!="Requested")? " ".$tstatus["progress"]."%":"")."<br>";
                }
            }
            
            if (preg_match("/requested$/i", $tstatus["status"])) { $image = "<img src=\"./images/requested.png\" boder=\"0\" title=\""._("Requested")."\" alt=\""._("Requested")."\" align=\"absmiddle\">"; }
            elseif (preg_match("/^pause/i", $tstatus["status"])) { $image = "<img src=\"./images/paused.png\" boder=\"0\" title=\""._("Paused")."\" alt=\""._("Paused")."\" align=\"absmiddle\">"; }
            elseif ($nstatus=="C" || $nstatus=="G") { $image = "<img src=\"./images/complete.png\" boder=\"0\" title=\""._("Completed")."\" alt=\""._("Completed")."\" align=\"absmiddle\">"; }
            elseif ($nstatus=="R") { $image = "<img src=\"./images/running.png\" boder=\"0\" title=\""._("Running")."\" alt=\""._("Running")."\" align=\"absmiddle\">"; }
            elseif ($nstatus=="S") { $image = "<img src=\"./images/sched.png\" boder=\"0\" title=\""._("Schedule")."\" alt=\""._("Schedule")."\" align=\"absmiddle\">"; }
            elseif ($nstatus=="F") { $image = "<img src=\"./images/warning.png\" boder=\"0\" title=\""._("Failed")."\" alt=\""._("Failed")."\" align=\"absmiddle\">"; } 
            elseif ($nstatus=="T") { $image = "<img src=\"./images/timeout.png\" boder=\"0\" title=\""._("Timeout expired")."\" alt=\""._("Timeout expired")."\" align=\"absmiddle\">"; }

            if (strtotime($scan_next)<=strtotime($submit_date)) { 
                $scan_next = "-";
            }
            else {
                $scan_next = date("Y-m-d H:i:s",strtotime($scan_next));  
            }

            $add_info = "ct=$curtime st=$start_date fd=$end_date";
            $arrIP = split('[/.]', $nname);

            $searchIP = "$arrIP[0].$arrIP[1].$arrIP[2].";
          
            $scan_info = "";

            $meth_TARGETorig = $meth_TARGET;
            $meth_TARGET = trim(nl2br((strlen($meth_TARGET)>=100) ? substr($meth_TARGET,0,100)."..." : $meth_TARGET));
          
            if( valid_hex32($nusername) ) { 
                $nusername = Session::get_entity_name($dbconn, $nusername);
            }
            
            if($nstatus=="C" || $nstatus=="G")
                $colorscan = "#EFFFF7";
            elseif ($failed_attempts>0)
                $colorscan = "#EFE1E0";
            elseif($nstatus=="T")
                $colorscan = "#FFFFDF";
            elseif($nstatus=="S")
                $colorscan = "#DFF7FF";
            elseif($nstatus=="R")
                $colorscan = "#D1E7EF";
            elseif($nstatus=="F")
                $colorscan = "#EFE1E0";
            
            $status_detail = preg_replace('/Scan Server Selected:\s+\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s*<br \/>/', '', nl2br(trim($meth_Wcheck)));
            
            $sInfo .= "<tr bgcolor=\"$colorscan\">";
            $sInfo .= "<td class='job_detail'><div class='job_status'>" . $image . " ". $statuses[$nstatus]."</div><div class='job_messages'>" . $status_detail . "</div></td>";

            if ($nstatus == "C") 
            {
                  if(intval($vulns)>0) {
                    $html_link = "href='reshtml.php?disp=html&amp;output=full&amp;scantime=$id_report&amp;scantype=M'";
                }
                else {
                    $html_link = "";
                }
          
                $sInfo .= "<td style=\"text-align:center;\"><a class=\"tip\" style=\"text-decoration:none;\" $html_link title=\"<b>"._("Owner").":</b> $nusername<br><b>"._("Server").":</b> $shostname<br>$job_status<b>"._("Job ID").":</b> $nid<br><b>"._("Profile").":</b> $profile<br><b>"._("Failed attempts").":</b> $failed_attempts<br><b>"._("Targets").":</b><br>".tooltip_hosts($meth_TARGETorig,$hosts)."<br/>".(($meth_Wcheck!="")? "<b>"._("Messages").":</b><br>".nl2br(trim($meth_Wcheck)) : "")."\">".html_entity_decode($nname)."</a>";
                $sInfo .= "</td>";
            }
            else if ($nstatus=="F") {
                $sInfo .= "<td style=\"text-align:center;\"><a class=\"tip\" style=\"text-decoration:none;\" href='javascript:;' title=\"<b>"._("Owner").":</b> $nusername<br><b>"._("Server").":</b> $shostname<br>$job_status<b>"._("Job ID").":</b> $nid<br><b>"._("Profile").":</b> $profile<br><b>"._("Failed attempts").":</b> $failed_attempts<br><b>"._("Targets").":</b><br>".tooltip_hosts($meth_TARGETorig,$hosts)."<br/>".(($meth_Wcheck!="")? "<b>"._("Messages").":</b><br>".nl2br(trim($meth_Wcheck)) : "")."\">".html_entity_decode($nname)."</a></td>";
            }
            else { // $nstatus == "R"
                $sInfo .= "<td style=\"text-align:center;\"><a class=\"tip\" style=\"text-decoration:none;\" href='javascript:;' title=\"<b>"._("Owner").":</b> $nusername<br><b>"._("Server").":</b> $shostname<br>$job_status<b>"._("Job ID").":</b> $nid<br><b>"._("Profile").":</b> $profile<br><b>"._("Failed attempts").":</b> $failed_attempts<br><b>"._("Targets").":</b><br>".tooltip_hosts($meth_TARGETorig,$hosts)."</br>".(($meth_Wcheck!="")? "<b>"._("Messages").":</b><br>".nl2br(trim($meth_Wcheck)) : "")."\">".html_entity_decode($nname)."</a></td>";
            }
            
            $sInfo .= "<td>$submit_date</td>
               <td>$start_date</td>
               <td>$end_date</td>";
               
            if($nmins =="0 mins")
                $sInfo .= "<td>< 1 min</td>";
            else
                $sInfo .= "<td>$nmins</td>";
         
            $sInfo .= "<td>$scan_next</td>";
            
            $user_name_filter = array();
            $c_user           = Session::get_session_user();
            if( Session::is_pro() )
            {
                if ( Acl::am_i_proadmin() ) 
                {
                    $user_name_filter = Acl::get_entities_to_assign($dbconn);
                    
                    $users = Acl::get_my_users($dbconn, $c_user); 
                    foreach ($users as $us) {
                        $user_name_filter[$us->get_login()] = $us->get_login();
                    }
                }
                else{
                    $user_name_filter[$c_user] = $c_user;
                }
            }
            else{
                $user_name_filter[$c_user] = $c_user;
            }
        
            if ( preg_match("/omp\s*$/i", $nessus_path) ) // OMP
            { 
                $sInfo .= "<td>";
                
                if ($task_id=="") {
                    $sInfo .= "<a href=\"sched.php?disp=delete_scan&job_id=$nid\" onclick=\"return confirmDelete();\"><img align=\"absmiddle\" src=\"images/delete.gif\" title=\""._("Delete")."\" alt=\""._("Delete")."\" border=0></a>&nbsp;";
                }
                else if ($tstatus["status"]!="Done" && $tstatus["status"]!="Stopped" && $tstatus["status"]!="")
                {
                    
                    $task_status = $tstatus["status"];
                    
                    if($task_status=="Running" || $task_status=="Requested" && !preg_match("/.*\srequested/i", $task_status)) { // pause button
                        $sInfo .= "<a href=\"javascript:;\" onclick=\"changeTaskStatus('$nid','pause_task');\"><img align=\"absmiddle\" title=\""._("Pause current scan job")."\" alt=\""._("Pause current scan job")."\" src=\"./images/pause_task.png\"  border=0></a>";
                    }
                    else {
                        $sInfo .= "<img align=\"absmiddle\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"./images/pause_task.png\"  border=0></a>";
                    }
                    
                    
                    // resume button
                    if( preg_match("/^pause.*/i", $task_status) && !preg_match("/.*\srequested/i", $task_status)) {
                        $sInfo .= "<a href=\"javascript:;\" onclick=\"changeTaskStatus('$nid','resume_task');\"><img align=\"absmiddle\" title=\""._("Resume scan job")."\" alt=\""._("Resume scan job")."\" src=\"./images/resume_task.png\"  border=0></a>";
                    }
                    else {
                        $sInfo .= "<img align=\"absmiddle\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"./images/resume_task.png\"  border=0></a>";
                    }
                    
                    // stop button
                    if($task_status=="Running" || $task_status=="Paused" && !preg_match("/.*\srequested/i", $task_status)) { 
                        $sInfo .= "<a href=\"javascript:;\" onclick=\"changeTaskStatus('$nid','stop_task');\"><img align=\"absmiddle\" title=\""._("Stop current scan job")."\" alt=\""._("Stop current scan job")."\" src=\"./images/stop_task.png\"  border=0></a>";
                    }
                    else {
                        $sInfo .= "<img align=\"absmiddle\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"./images/stop_task.png\"  border=0></a>";
                    }
                }
                else if (( $nstatus=="T" || $nstatus=="C" || $tstatus["status"]=="Stopped") && $nstatus!="F") 
                {
                    if (Session::am_i_admin()){
                        $sInfo.="<a class=\"greybox\" href=\"change_user.php?id=$report_id\"><img align=\"absmiddle\" src=\"../pixmaps/group.png\" title=\""._("Change owner")."\" alt=\""._("Change owner")."\" border=\"0\"></a>&nbsp;";
                    }
                    else if(Session::is_pro())
                    {
                        if( Acl::am_i_proadmin() ) {
                            $sInfo.="<a class=\"greybox\" href=\"change_user.php?id=$report_id\"><img align=\"absmiddle\" src=\"../pixmaps/group.png\" title=\""._("Change owner")."\" alt=\""._("Change owner")."\" border=\"0\"></a>&nbsp;";
                        }
                    }
                    
                    if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) 
                    {
                        if(!$vulns_in_report || $vulns!=-1) {
                            $sInfo.="<a href=\"".Menu::get_menu_url('sched.php?disp=rerun&job_id='.$nid, 'environment', 'vulnerabilities', 'scan_jobs')."\"><img align=\"absmiddle\" src=\"./images/relaunch.gif\" border=\"0\" title=\""._("Rerun Scan")."\" alt=\""._("Rerun Scan")."\"></a>&nbsp;";
                        }
                        else{
                            $sInfo.="<img align=\"absmiddle\" class= \"disabled\" src=\"./images/relaunch.gif\" border=\"0\" title=\""._("Action not allowed")."\" alt=\""._("Action not allowed")."\"/>&nbsp;";
                        }
                    }
                   
                    if (!$vulns_in_report) {
                        // report without results
                        $vulns = 0;
                        $sInfo.="<img align=\"absmiddle\" class=\"disabled\" src=\"./images/html.png\" border=\"0\" title=\""._("No results found")."\" alt=\""._("No results found")."\">&nbsp;";
                        $sInfo.="<img align=\"absmiddle\" class=\"disabled\" src=\"./images/pdf.png\" border=\"0\" title=\""._("No results found")."\" alt=\""._("No results found")."\">&nbsp;";
                        $sInfo.="<img align=\"absmiddle\" class=\"disabled\" src=\"./images/page_white_excel.png\" border=\"0\" title=\""._("No results found")."\" alt=\""._("No results found")."\">&nbsp;";
                    }
                    elseif($vulns!=-1) {
                        // report with results
                        
                        $sInfo.="<a id='html_report_$selenium_id' href=\"reshtml.php?disp=html&amp;output=full&amp;scantime=$id_report&amp;scantype=M\"><img align=\"absmiddle\" src=\"./images/html.png\" border=\"0\" title=\""._("View report")."\" alt=\""._("View report")."\"></a>&nbsp;";
                        $sInfo.="<a href=\"respdf.php?scantime=$id_report&amp;key=$report_key&amp;scantype=M\" target=\"_blank\"><img align=\"absmiddle\" src=\"./images/pdf.png\" border=\"0\" title=\""._("View report")."\" alt=\""._("View report")."\"></a>&nbsp;";
                        $sInfo.="<a href=\"rescsv.php?scantime=$id_report&amp;key=$report_key&amp;scantype=M\" target=\"_blank\"><img align=\"absmiddle\" src=\"./images/page_white_excel.png\" border=\"0\" title=\""._("View report")."\" alt=\""._("View report")."\"></a>&nbsp;";
                        
                    }
                    else {
                        // the user has no permission
                        $vulns = "-";
                        $sInfo.="<img align=\"absmiddle\" class=\"disabled\" src=\"./images/html.png\" border=\"0\" title=\""._("Report not allowed")."\" alt=\""._("Report not allowed")."\">&nbsp;";
                        $sInfo.="<img align=\"absmiddle\" class=\"disabled\" src=\"./images/pdf.png\" border=\"0\" title=\""._("Report not allowed")."\" alt=\""._("Report not allowed")."\">&nbsp;";
                        $sInfo.="<img align=\"absmiddle\" class=\"disabled\" src=\"./images/page_white_excel.png\" border=\"0\" title=\""._("Report not allowed")."\" alt=\""._("Report not allowed")."\">&nbsp;";
                    }
                                       
                    if(!$vulns_in_report || $vulns!="-") 
                    {
                        if(file_exists("/usr/share/ossim/uploads/nbe/".$report_id.".nbe")) {
                            $sInfo.="<a href=\"export_nbe.php?report_id=$report_id\" style=\"display: inline\"><img title=\""._("Download results in nbe format")."\" alt=\""._("Download results in nbe format")."\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"></a>&nbsp;";
                        }
                        else {
                            $sInfo.="<a href=\"export_nbe.php?job_id=$nid\" style=\"display: inline\"><img title=\""._("Export job results in nbe format")."\" alt=\""._("Export job results in nbe format")."\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"></a>&nbsp;";
                        }
                    }
                    else {
                        $sInfo.="<img class=\"disabled\" title=\""._("Action not allowed")."\" alt=\""._("Action not allowed")."\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"/>&nbsp;";
                    }
                    
                    $sInfo.="($vulns)";
                }
                else if ($nstatus=="F") {
                    $sInfo.="<a href=\"".Menu::get_menu_url('sched.php?disp=rerun&job_id='.$nid, 'environment', 'vulnerabilities', 'scan_jobs')."\"><img align=\"absmiddle\" src=\"./images/relaunch.gif\" border=\"0\" title=\""._("Rerun Scan")."\" alt=\""._("Rerun Scan")."\"></a>";
                    $sInfo .= "<span style=\"font-size: 7pt;padding:0px 4px 0px 4px;\">["._("Scan failed")."]</span>";
                }
                
                if(preg_match("/omp\s*$/i", $nessus_path) && $task_id!="" && $nstatus!="G") 
                {
                // delete button
                    if($tstatus["status"]=="Done" || $tstatus["status"]=="" || $tstatus["status"]=="Stopped" ||
                       $tstatus["status"]=="Paused" && !preg_match("/.*\srequested/i", $tstatus["status"])) {
                           if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
                            if( ($vulns!=0 && $vulns!="-") || Session::am_i_admin() ) {
                                $sInfo .= "<a id=\"delete_job_$selenium_id\" href=\"javascript:;\" onclick=\"deleteTask('$nid');\"><img align=\"absmiddle\" title=\""._("Delete scan job")."\" alt=\""._("Delete scan job")."\" src=\"./images/delete.gif\"  border=0></a>";
                            }
                            else {
                                $sInfo .= "<img class=\"disabled\" align=\"absmiddle\" title=\""._("Action not allowed")."\" alt=\""._("Action not allowed")."\" src=\"./images/delete.gif\"  border=0 />";
                            }
                        }
                    }
                    else {
                        $sInfo .= "<img align=\"absmiddle\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"./images/delete.gif\"  border=0></a>";
                    }
                }
                
                $sInfo .= "<span style='display:none' id='changing_task_status_$nid'><img align=\"absmiddle\" src=\"../pixmaps/loading.gif\" width=16 border=0></span></td>";
            }
            else 
            { // OpenVAS or nessus 
                
                if ($nstatus=="C" || ($nstatus=="T" && $report_id!="0")) 
                {
                    $sInfo.="<td style=\"text-align:left;padding-left:15px;\">";
                    
                    if (Session::am_i_admin()) {
                        $sInfo.="<a class=\"greybox\" href=\"change_user.php?id=$report_id\"><img align=\"absmiddle\" src=\"../pixmaps/group.png\" title=\""._("Change owner")."\" alt=\""._("Change owner")."\" border=\"0\"></a>&nbsp;";
                    }
                    else if(Session::is_pro())
                    {
                        if(Acl::am_i_proadmin()) {
                            $sInfo.="<a class=\"greybox\" href=\"change_user.php?id=$report_id\"><img align=\"absmiddle\" src=\"../pixmaps/group.png\" title=\""._("Change owner")."\" alt=\""._("Change owner")."\" border=\"0\"></a>&nbsp;";
                        }
                    }
              
                    if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
                        $sInfo.="<a href=\"".Menu::get_menu_url('sched.php?disp=rerun&job_id='.$nid, 'environment', 'vulnerabilities', 'scan_jobs')."\"><img align=\"absmiddle\" src=\"./images/relaunch.gif\" border=\"0\" title=\""._("Rerun Scan")."\" alt=\""._("Rerun Scan")."\"></a>&nbsp;$scan_info&nbsp;<a href=\"".Menu::get_menu_url('reshtml.php?disp=html&output=full&scantime='.$id_report.'&scantype=M', 'environment', 'vulnerabilities', 'scan_jobs')."\"><img align=\"absmiddle\" src=\"./images/html.png\" border=\"0\" title=\""._("View report")."\" alt=\""._("View report")."\"></a>&nbsp;";
                    }
                    
                    $sInfo.="<a href=\"respdf.php?scantime=$id_report&amp;key=$report_key&amp;scantype=M\" target=\"_blank\"><img align=\"absmiddle\" src=\"./images/pdf.png\" border=\"0\" title=\""._("View report")."\" alt=\""._("View report")."\"></a>&nbsp;";
                    $sInfo.="<a href=\"rescsv.php?scantime=$id_report&amp;key=$report_key&amp;scantype=M\" target=\"_blank\"><img align=\"absmiddle\" src=\"./images/page_white_excel.png\" border=\"0\" title=\""._("View report")."\" alt=\""._("View report")."\"></a>&nbsp;";

                

                    if(file_exists("/usr/share/ossim/uploads/nbe/".$report_id.".nbe")) {
                        $sInfo.="<a href=\"export_nbe.php?report_id=$report_id\" style=\"display: inline\"><img title=\""._("Download results in nbe format")."\" alt=\""._("Download results in nbe format")."\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"></a>&nbsp;";
                    }
                    else{
                        $sInfo.="<a href=\"export_nbe.php?job_id=$nid\" style=\"display: inline\"><img title=\""._("Export job results in nbe format")."\" alt=\""._("Export job results in nbe format")."\" style=\"display: inline;\" src=\"../pixmaps/download.png\" width=\"15\" align=\"absmiddle\" border=\"0\"></a>&nbsp;";
                    }
                    $sInfo.="($vulns)</td>";
                }
                else if ($nstatus=="F" && $meth_Wcheck=="") 
                {
                    $sInfo.="<td style=\"text-align:left;padding-left:15px;\"><a href=\"".Menu::get_menu_url('sched.php?disp=rerun&job_id='.$nid, 'environment', 'vulnerabilities', 'scan_jobs')."\"><img align=\"absmiddle\" src=\"./images/relaunch.gif\" border=\"0\" title=\""._("Rerun Scan")."\" alt=\""._("Rerun Scan")."\"></a>&nbsp;";
                    $sInfo.="<a href=\"".Menu::get_menu_url('sched.php?disp=delete_scan&job_id='.$nid, 'environment', 'vulnerabilities', 'scan_jobs')."\" onclick=\"return confirmDelete();\"><img align=\"absmiddle\" src=\"images/delete.gif\" title=\""._("Delete")."\" alt=\""._("Delete")."\" border=0></a>&nbsp;";
                    $sInfo.="<span style=\"font-size: 7pt;\">["._("Scan failed")."]</span></td>";
                }
                else 
                {
                    $sInfo .="<td align=\"center\">";
                    if ($nstatus=="R" && $scan_PID>0){
                        $sInfo .="<a href=\"javascript:;\" onclick=\"cancelScan('$scan_PID');\"><img align=\"absmiddle\" src=\"../pixmaps/cancel.png\" title=\""._("Cancel current scan job")."\" alt=\""._("Cancel current scan job")."\" border=0></a><span style='display:none' id='working'><img align=\"absmiddle\" src=\"../pixmaps/loading.gif\" width=16 border=0></span>";
                    }  
                    
                    $sInfo .="<a href=\"".Menu::get_menu_url('sched.php?disp=delete_scan&job_id='.$nid, 'environment', 'vulnerabilities', 'scan_jobs')."\" onclick=\"return confirmDelete();\"><img align=\"absmiddle\" src=\"images/delete.gif\" title=\""._("Delete")."\" alt=\""._("Delete")."\" border=0></a></td>";
                }
            }
            $selenium_id++;
            
            $sInfo .="</tr>";
            $result->MoveNext();

        }

        $sInfo .= "</table>";
    }
     
    if( $out==0 ) 
    {
        if( $status=="R" ) {
            $sInfo .="<table class='w100 table_list'><tr><td class='nobborder empty_results' height='20' style='text-align:center'>"._("No Running Jobs")."</td></tr></table>";
        }
        else{
            $sInfo .="<table class='w100 table_list'><tr><td class='nobborder empty_results' height='20' style='text-align:center'>"._("No Jobs")."</td></tr></table>";
        }
    }
    
    $sInfo .="</center>";
     
    echo $sInfo;
    
    return $out;
}

function dispSQLError($err, $errNum) {
   echo "<center><table class='error'>
         <tr><th> $errNum SQL Errors<th></tr>\n";
   foreach($err as $e) {
      echo "<tr><td>$e</td></tr>\n";
   }
   echo "</table></center>\n";
}

function logAccess( $action ) {
    global $logh, $username, $dbconn;

    $protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    /*** return the full address ***/
    $url = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    #$logh->log("$username : $url : Page accessed", PEAR_LOG_DEBUG);

    $query_log="insert into log (ip, username, url, action) values('$_SERVER[REMOTE_ADDR]','$username', '$url', '$action' )";
    $result_log=$dbconn->execute($query_log);

}

function getAddress() {
    /*** check for https ***/
    $protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    /*** return the full address ***/
    return $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
 }

function online() {
  global $client_uname, $client_ip, $username, $dbconn;
  
  $client_uname="";
  
  $client_ip = $_SERVER['REMOTE_ADDR'];
  $guest = 0;
  
  if (isset($username)) {
    $client_uname = $username;
  }
  if ( $client_uname == "" ) {
    $client_uname = $client_ip;
    $guest = 1;
  }
  
  $past = time()-3600;
  $query = "DELETE FROM vuln_sessions WHERE time < '$past'";
  $dbconn->execute($query);

  $query = "SELECT time FROM vuln_sessions WHERE uname='$client_uname'";
  $result = $dbconn->execute($query);
  $ctime = time();
  if (!empty($client_uname)) {
    $client_uname = substr($client_uname, 0,25);
   list ( $match ) = $result->fields;
    if ( $match ) {
	 $dbconn->execute("UPDATE vuln_sessions SET uname='$client_uname', time='$ctime', host_addr='$client_ip', 
	 	guest='$guest' WHERE uname='$client_uname'");
    } else {
	 $dbconn->execute("INSERT INTO vuln_sessions (uname, time, host_addr, guest
	 	) VALUES ('$client_uname', '$ctime', '$client_ip', '$guest')");
    }
  }
}

function get_ip( $host ){
  $hostip = @gethostbyname( $host );
  $ip = ( $hostip == $host ) ? $host : long2ip( ip2long( $hostip ) );
  //echo sprintf("Resolved %s to %s", $host, $ip);
  return $ip;
} 

function check_host ( $eval_hostname, $eval_hostip ) {
	
	#RESOLVE NAME 
	$bname_resolve = true;
	$bip_resolve = true;
	$balive = false;
	
	$res=`nslookup -timeout=1 -retry=0 $eval_hostname`;
 	if (preg_match('/\nName:(.*)\n/', $res, $out)) {
   		$lookup_hostname =  trim($out[1]);
 	} else {
   		$lookup_hostname =  $host;		
 		$bname_resolve = false; 		
 	}
	
	#IP LOOKUP
	$test_ip = @gethostbyname( $eval_hostname );
	$lookup_ip = ( $test_ip == $eval_hostname ) ? $eval_hostname : long2ip( ip2long( $test_ip ) );
	
	if ( $lookup_ip == $eval_hostname ) {
		$lookup_ip = $eval_hostip;
		$bip_resolve = false;		
	}
	
	$res = shell_exec("ping -c 1 -w 1 $lookup_ip");
	if (preg_match('/rtt/', $res, $out)) {
   		$balive = true;
 	} else {
   		$balive = false; 		
 	}
 	
 	if ( $bname_resolve & $balive ) {				#HOSTNAME IS ALIVE
 		$match_rating = 4;
 		$match_color = "green";
 	}elseif ( $bname_resolve && $bip_resolve ) {	#NAME IP / MATCH ( NOT ONLINE )
 		$match_rating = 3;
 		$match_color = "blue";
 	} elseif ( $bname_resolve ) {					#NAME EXISTS ( NO IP FOUND SHOULD NOT BE ALIVE )
 		$match_rating = 2;
 		$match_color = "yellow";
 	} elseif ( $balive ) {							#IP IS ALIVE ( DID NOT VERIFY HOSTNAME )
 		$match_rating = 1;
 		$match_color = "red";
 	} else {										#DEAD HOST NO DNS NAME
 		$match_rating = 0;
 		$match_color = "black";
 	}
		
	$results = array(
		"hostname" => $lookup_hostname,
		  "hostip" => $lookup_ip,
	  "match_name" => $bname_resolve,
	    "match_ip" => $bip_resolve,
		   "alive" => $balive,
		  "rating" => $match_rating,
    "rating_color" => $match_color
	);
	
	return $results;
	
}

function getUserRoles ($user) {
   global $dbconn;
   $finalroles = array();
   // get users roles, used for access control decisions
   //  user roles are a union of the users individually assigned roles
   //  and any roles assigned based on user group membership
   // get the roles from group memebership for the user
   $query = "SELECT r.roleName
             FROM vuln_users as u
             LEFT JOIN vuln_user_groups as ug ON u.pn_uname = ug.pn_uname
             LEFT JOIN vuln_group_roles as gr ON ug.gid = gr.gid
             LEFT JOIN vuln_roles as r ON gr.roleID = r.roleID
             WHERE u.pn_uname = '$user'";
   $groles = $dbconn->GetArray($query);
   if($groles === false) {
      $errMsg[] = "SQL Error getting user groups: " . $dbconn->ErrorMsg();
      $error = 1;
   } else {
      foreach($groles as $role) {
         $finalroles[$role['roleName']] = TRUE;
      }
   }

   // now get any directly assigned roles
   $query = "SELECT r.roleName
             FROM vuln_users as u
             LEFT JOIN vuln_user_roles as ur ON u.pn_uname = ur.pn_uname
             LEFT JOIN vuln_roles as r ON ur.roleID = r.roleID
             WHERE u.pn_uname = '$user'";
   $uroles = $dbconn->GetArray($query);
   if($uroles === false) {
      $errMsg[] = "SQL Error getting users roles: " . $dbconn->ErrorMsg();
      $error++;
   } else {
      foreach($uroles as $role) {
         $finalroles[$role['roleName']] = TRUE;
      }
   }
   
   // now set the other roles to FALSE in the array
   $query = "SELECT roleName
             FROM vuln_roles";
   $roles = $dbconn->GetArray($query);
   if($roles === false) {
   }  else {
      foreach($roles as $role) {
         $finalroles[$role['roleName']] = (isset($finalroles[$role['roleName']])) ? TRUE : FALSE;
      }
   }
   return $finalroles;
}

function getSiteCodes ($user) {
   global $dbconn;
   $finalsites = array();
   // get users associated sites, used for access control decisions
   //  user sites are a union of the users individually assigned site_access
   //  and any sites assigned based on user group membership
   // get the roles from group memebership for the user
   // allowing tasks such as report admin / self service / scanning / etc
   // for the associated address space

   $query = "SELECT t1.site_code as siteName
          FROM vuln_sites t1
               LEFT JOIN vuln_org_sites t2 on t1.id = t2.siteID
               LEFT JOIN vuln_org_users t3 on t2.orgID = t3.orgID
          WHERE t3.pn_uname = '$user'";

   
   
   $usites = $dbconn->GetArray($query);
   if($usites === false) {
      $errMsg[] = "SQL Error getting users sites: " . $dbconn->ErrorMsg();
      $error++;
   } else {
      foreach( $usites as $site ) {
         $finalsites[$site['siteName']] = TRUE;
      }
   }
   
   return $finalsites;
}

function getUserSystems ($user) {
   global $dbconn;
   $finalsystems = array();
   // get users associated sites, used for access control decisions
   //  user sites are a union of the users individually assigned site_access
   //  and any sites assigned based on user group membership
   // get the roles from group memebership for the user
   // allowing tasks such as report admin / self service / scanning / etc
   // for the associated address space

   $query = "SELECT t1.acronym
          FROM vuln_systems t1
               LEFT JOIN vuln_system_users t2 on t1.id = t2.sysID
          WHERE t2.pn_uname = '$user'";

   
   
   $usystems = $dbconn->GetArray($query);
   if($usystems === false) {
      $errMsg[] = "SQL Error getting users sytems: " . $dbconn->ErrorMsg();
      $error++;
   } else {
      foreach( $usystems as $system ) {
         $finalsystems[$system['acronym']] = TRUE;
      }
   }
   
   return $finalsystems;
}

function getComponent ($user) {
   global $dbconn;

   // get users associated org, used for access control decisions
   //  user sites are a union of the users individually assigned site_access
   //  and any sites assigned based on user group membership
   // get the roles from group memebership for the user
   // allowing tasks such as report admin / self service / scanning / etc
   // for the associated address space

   $query = "SELECT component FROM vuln_users WHERE pn_uname = '$user'";
   $result=$dbconn->execute($query);
   list ( $user_component ) = $result->fields;

   return "$user_component";
}


function getuserTZ ( $username ) {
   global $dbconn;

   #MAKE SURE THERE IS A VALID ABBREV
   $tz = "";
   $query = "SELECT t2.abbrev FROM vuln_users t1
   		LEFT JOIN vuln_timezones t2 on t1.user_TZ=t2.abbrev
   		WHERE t1.pn_uname='$username'";
   $result=$dbconn->execute($query);
   list ( $tz ) = $result->fields;
   return "$tz";

}

function switchTime_TimeZone ( $value, $to_tz, $newformat="" ) {  
   global $username, $dbconn;
   
   if ( strlen($value) == 0 || $value == "now" ) {
      #NOW
      $arrTime = localtime(time(), true);
      $year = 1900 + $arrTime["tm_year"];
      $mon = 1 + $arrTime["tm_mon"];
      $mday =  $arrTime["tm_mday"];
      $hour = $arrTime["tm_hour"];
      $min = $arrTime["tm_min"];
      $sec = $arrTime["tm_sec"];	
   } else { # A VALUE WAS PASSED
   	   if (strlen($value) == 19) {
   	       $year=substr($value,0,4);
           $mon=substr($value,5,2);
           $mday=substr($value,8,2);
           $hour=substr($value,11,2);
           $min=substr($value,14,2);
           $sec=substr($value,17,2); 	       
   	   } elseif (strlen($value) == 14) {
   	       $year=substr($value,0,4);
           $mon=substr($value,4,2);
           $mday=substr($value,6,2);
           $hour=substr($value,8,2);
           $min=substr($value,10,2);
           $sec=substr($value,12,2); 	
       } else {	
          return $value;
       }
   }   
   	
   $server_offset = date('Z');
   $server_offset = ( $server_offset / ( 60*60 ) );	

   $query = "SELECT t2.offset, t2.abbrev FROM vuln_users t1
   		LEFT JOIN vuln_timezones t2 on t1.user_TZ=t2.abbrev
   		WHERE t1.pn_uname='$username'";
   $result=$dbconn->execute($query);
   list ( $user_offset, $user_tz ) = $result->fields;	

   if ( $to_tz == "user" ) {   
      $diff_offset = ( $user_offset  - $server_offset );	
   } elseif ( $to_tz == "server" ) { 	
   	  $diff_offset = ( ( $user_offset  - $server_offset ) * -1 );
   } else {
   	  $diff_offset = 0;
   }

   if ( $newformat == "TZdate" ) {
      $user_time = strftime("%m/%d/%Y %H:%M:%S", mktime($hour+$diff_offset,$min,$sec,$mon,$mday,$year));
      $user_time .= " $user_tz";
   } elseif ( $newformat == "asdate" ) {
      $user_time = strftime("%m/%d/%Y %H:%M:%S", mktime($hour+$diff_offset,$min,$sec,$mon,$mday,$year));
   } else {	#SQL FORMAT
      $user_time = strftime("%Y%m%d%H%M%S", mktime($hour+$diff_offset,$min,$sec,$mon,$mday,$year));
   }   
      
   return $user_time;
   
}

function getUserOrg ($user) {
   global $dbconn;

   // get users associated org, used for access control decisions
   //  user sites are a union of the users individually assigned site_access
   //  and any sites assigned based on user group membership
   // get the roles from group memebership for the user
   // allowing tasks such as report admin / self service / scanning / etc
   // for the associated address space

   $query = "SELECT t1.org_code
          FROM vuln_orgs t1
               LEFT JOIN vuln_org_users t2 on t1.id = t2.orgID
          WHERE t2.pn_uname = '$user'";

   $result=$dbconn->execute($query);

   list ( $org_code ) = $result->fields;

   return "$org_code";

}

function getProfileName ($sid) {
   global $dbconn;
   
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $query = "SELECT name, description FROM vuln_nessus_settings 
      WHERE id='$sid' LIMIT 1";

   $result=$dbconn->execute($query);

   list ( $pname, $pdesc ) = $result->fields;

   return "$pname - $pdesc";

}

function getUserName ($login) {
   global $dbconn;

   $query = "SELECT user_namef, user_namel
             FROM vuln_contacts
             WHERE fk_uname = '$login' LIMIT 1";

   $result=$dbconn->execute($query);

   list ( $fname, $lname ) = $result->fields;

   return array( "login" => $login,
                 "fname" => $fname,
                 "lname" => $lname);

}

function inArray ($tmpItem, $tmpArray) {
   $found=0;

   for ($i=0; $i<count($tmpArray); $i++) {
      if ($tmpItem==$tmpArray[$i]) {
         $found=1;
         break;
      }
   }

   if ($found) { return 1; }
   else { return 0; }
}

function getrisk($risk) {

    switch ($risk) {
    case 1:
            $risk="Serious";
        break;
    case 2:
        $risk="High";
        break;
    case 3:
        $risk="Medium";
        break;
    case 4:
        $risk="Medium/Low";
        break;
    case 5:
        $risk="Low/Medium";
        break;
    case 6:
        $risk="Low";
        break;
    case 7:
        $risk="Info";
        break;
    case 8:
        $risk="Exceptions";
        break;        
    }

    return $risk;
}

function getScnCATID() {
	global $dbconn;
		
	$query = "SELECT id FROM vuln_nessus_category WHERE name='scanner'";
	$result=$dbconn->execute($query);
    list ( $cat_id ) = $result->fields;
    return $cat_id;
}

function getcrisk($risk) {

    switch ($risk) {
    case 2:
        $risk="Failed";
        break;
    case 3:
        $risk="Error";
        break;
    case 6:
        $risk="Passed";
        break;
    }

    return $risk;
}

// Activate Hyperlinks in Msg to New Windows
function activateHyperlink($text) {
 // match protocol://address/path/file.extension?some=variable&another=asf%
 $text = preg_replace("/([a-zA-Z]+:\/\/[a-z0-9\_\.\-]+".
"[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%\=\,\.\_]+)/",
" <a href=\"$1\" target=\"_blank\">$1</a>", $text);
 // match www.something.domain/path/file.extension?some=variable&another=asf%
 $text = preg_replace("/[^a-z]+[^:\/\/](www\.".
"[^\.]+[\w][\.|\/][a-zA-Z0-9\/\*\-\?\&\%\=\,\.]+)/",
" <a href=\"http://$1\" target=\"_blank\">$1</a>", $text);
 // match name@address + ?subject etc...
$text = preg_replace(
 "/([\s|\,\>])([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-z" .
 "A-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})" .
 "([A-Za-z0-9\!\?\@\#\$\%\^\&\*\(\)\_\-\=\+]*)" .
 "([\s|\.|\,\<])/i",
 "$1<a href=\"mailto:$2$3\">$2</a>$4",
 $text);

 	return $text;
}

function hostLink($host, $ip, $type = "name", $edit = FALSE) {

   if($edit) {
      $edit = "disp=edit_host";
   } else {
      $edit = "disp=view";
   }
   
   // special case for unknown hostnames
   if(strstr($host,"unknown")) {
      $ip = substr($host,8);
      $url = "<a href='hosts.php?disp=add&hostip=$ip&disp=add' onClick=\"popup('hosts.php?hostip=$ip','Add Host');return false;\" title='Add host'>Unknown</a>";
      return $url;
   }

   // blank hostname and ip
   if($host == "" && $ip == "") {
      $url = "<a href='hosts.php?$edit' onClick=\"popup('hosts.php?$edit','Lookup Host');return false;\" title='Lookup host'>All</a>";
      return $url;
   }

   if($type == "name") {
      $url = "<a href='hosts.php?$edit&hostname=$host&hostip=$ip' onClick=\"popup('hosts.php?$edit&hostname=$host&hostip=$ip','Lookup Host');return false;\" title='Lookup host'>$host</a>";
   } else { // IP address
      // do special check for multiple IP addresses, comma separated
      $ips = explode(",",$ip);
      foreach($ips as $v) {
         $url[] = "<a href='hosts.php?$edit&hostname=$host&hostip=$v' onClick=\"popup('hosts.php?$edit&hostname=$host&hostip=$v','Lookup IP');return false;\" title='Lookup IP'>$v</a> ";
      }
      $url = implode($url,",");
   }
   return $url;
}

function validateip($ip) {
    $retip=0;

    if (ereg('^([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])$',$ip,$r)) {
        $ip1=intval($r[1])*16777216+intval($r[2])*65536+intval($r[3])*256+intval($r[4]);
        if ($ip1<>0) {
            $retip=1;
        }
    } elseif (ereg('^([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])-([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])$',$ip,$r)) {

        $ip1=intval($r[1])*16777216+intval($r[2])*65536+intval($r[3])*256+intval($r[4]);
        $ip2=intval($r[5])*16777216+intval($r[6])*65536+intval($r[7])*256+intval($r[8]);

        if ($ip2>$ip1 and $ip1<>0) {
            $retip=1;
        }
    }
    return($retip);
}

// function to check if the submitted IP address is in an allowed Network Zone
// that the user has rights to scan.
function inrange($ip, $dbconn) {
    $check=0;
    // check for single IP address, and split it into the octets
    if (ereg('^([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])$',$ip,$r)) {

        $ip1=intval($r[1])*16777216+intval($r[2])*65536+intval($r[3])*256+intval($r[4]);
        $ip2=0;

        if ($ip1<>0) {
            $check=1;
        }
    // check for range of IP address, and split them into the two sets of
    // octets (for ip1 and ip2)
    } elseif (ereg('^([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])-([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])\.([0-9]{1,2}|[01][0-9]{2,3}|2[0-4][0-9]|25[0-5])$',$ip,$r)) {

        $ip1=intval($r[1])*16777216+intval($r[2])*65536+intval($r[3])*256+intval($r[4]);
        $ip2=intval($r[5])*16777216+intval($r[6])*65536+intval($r[7])*256+intval($r[8]);

        if ($ip2>$ip1 and $ip1<>0) {
            $check=1;
        }
    }

    if ($check==1) {
        // get the username
        $uname=htmlspecialchars(mysql_real_escape_string(trim($_SESSION['user'])), ENT_QUOTES);
        $found=0;

        // get the ranges for all the Network Zones that the user has
        // access to so we can check to see if the IP(s) are in one of them.
        /*
		$query="SELECT range_start, range_end 
                FROM vuln_nessus_zone_ranges 
                INNER JOIN vuln_nessus_user_zones 
                   ON vuln_nessus_user_zones.zid=vuln_nessus_zone_ranges.zoneid 
                WHERE vuln_nessus_user_zones.username='$uname'";

        $result=$dbconn->execute($query);

        while(!$result->EOF) {

            list($range_start, $range_end)=$result->fields;

            $rip1=0;
            $rip2=0;

            // single IP, range that is a single IP as well
            if ($ip2==0 and $range_start==$range_end) {
                $rip1=$range_start;

                if ($rip1==$ip1) {
                    $found=1;
                    break;
                }
            }

            // if this is a valid range (start is before end)
            if ($range_start<$range_end) {

                $rip1=$range_start;
                $rip2=$range_end;

                // for a single IP, see if it's between the start and the end
                // (inclusive)
                // or for a range, the starting IP and ending IP have to both
                // be between the start and end (inclusive)
                if (($ip1>=$rip1 and $ip1<=$rip2 and $ip2==0) 
                     or ($ip1>=$rip1 and $ip2<=$rip2 and $ip2<>0)) {
                    $found=1;
                    break;
                }
            }
            $result->MoveNext();
        }*/
        return($found);
    } else {
        return(0);
    }
}

function range2List ( $istart, $iend ) {
     
     // generate ip addrs
     $arry1 = explode(".",$istart);
     $arry2 = explode(".",$iend);

     $a1 = $arry1[0]; $b1 = $arry1[1]; $c1 = $arry1[2]; $d1 = $arry1[3];
     $a2 = $arry2[0]; $b2 = $arry2[1]; $c2 = $arry2[2]; $d2 = $arry2[3];

     $result = array();     
     while( $d2 >= $d1 || $c2 > $c1 || $b2 > $b1 || $a2 > $a1){
          if($d1 > 255){
               $d1 = 1;
               $c1 ++;
          }

          if($c1 > 255){
               $c1 = 1;
               $b1 ++;
          } 

          if($b1 > 255){
               $b1 = 1;
               $a1 ++;
          } 

          #echo "$a1 $b1 $c1 $d1 <br>";
          array_push($result, $a1. "." .$b1. "." .$c1. "." .$d1 );
          $d1 ++;
     }
     
     return $result;
}

function gen_strtotime($value, $newformat) {

	if ( strlen($value) == 0 || $value == "now" ) {
      #NOW
      $arrTime = localtime(time(), true);
      $year = 1900 + $arrTime["tm_year"];
      $mon = 1 + $arrTime["tm_mon"];
      $mday =  $arrTime["tm_mday"];
      $hour = $arrTime["tm_hour"];
      $min = $arrTime["tm_min"];
      $sec = $arrTime["tm_sec"];	
   } else { # A VALUE WAS PASSED
       if (strlen($value) != 14) {
          return $value;
        }
        $year=substr($value,0,4);
        $mon=substr($value,4,2);
        $mday=substr($value,6,2);
        $hour=substr($value,8,2);
        $min=substr($value,10,2);
        $sec=substr($value,12,2);
   }

   if ( $newformat == "asdate" ) {
      $value2 = sprintf("%02d/%02d/%04d %02d:%02d:%02d", $mon, $mday, $year, $hour, $min, $sec);
   } else {	#SQL FORMAT
      $value2 = sprintf("%04d-%02d-%02d %02d:%02d:%02d",$year, $mon, $mday, $hour, $min, $sec);
   }
   return $value2;

}

function gen_timeasstr() {
	
	$arrTime = localtime(time(), true);
	
	$year = 1900 + $arrTime["tm_year"];
	$mon = 1 + $arrTime["tm_mon"];
	$mday =  $arrTime["tm_mday"];
	$hour = $arrTime["tm_hour"];
	$min = $arrTime["tm_min"];
	$sec = $arrTime["tm_sec"];
	
	
	$ltime = sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec);
	
	return $ltime;
}

function datediff($interval, $date1, $date2)
{

   //convert the dates into timestamps
  $date1 = strtotime($date1);
  $date2 = strtotime($date2);
  $seconds = $date2 - $date1;

   if ($seconds < 0)
   {
           $tmp = $date1;
           $date1 = $date2;
           $date2 = $tmp;
           $seconds = 0-$seconds;
   }

   //reconvert the timestamps into dates
   if ($interval =='y' || $interval=='m') {
       $date1 = date("Y-m-d h:i:s", $date1);
       $date2=  date("Y-m-d h:i:s", $date2);
   }

   switch($interval) {
       case "y":
           list($year1, $month1, $day1) = split('-', $date1);
           list($year2, $month2, $day2) = split('-', $date2);
           $time1 = (date('H',$date1)*3600) + (date('i',$date1)*60) + (date('s',$date1)
);
           $time2 = (date('H',$date2)*3600) + (date('i',$date2)*60) + (date('s',$date2)
);
           $diff = $year2 - $year1;

           if($month1 > $month2) {
               $diff -= 1;
           } elseif($month1 == $month2) {
               if($day1 > $day2) {
                   $diff -= 1;
               } elseif($day1 == $day2) {
                   if($time1 > $time2) {
                       $diff -= 1;
                   }
               }
           }
           break;
       case "m":
           list($year1, $month1, $day1) = split('-', $date1);
           list($year2, $month2, $day2) = split('-',$date2);
           $time1 = (date('H',$date1)*3600) + (date('i',$date1)*60) + (date('s',$date1)
);
           $time2 = (date('H',$date2)*3600) + (date('i',$date2)*60) + (date('s',$date2)
);
           $diff = ($year2 * 12 + $month2) - ($year1 * 12 + $month1);
           if($day1 > $day2) {
               $diff -= 1;
           } elseif($day1 == $day2) {
               if($time1 > $time2) {
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

function addException($hostip,$res,$plugin,$schedid,$hostname) {
   return "<br><a href='exceptions.php?disp=new&hostip=$hostip&sid=$schedid&plugin=$plugin&hostname=$hostname&rid=$res' target='_blank'>Add Exception</a>";
}

function printException($hostip, $plugin, $dbconn, $excel = FALSE, $hostname = "",
                        $port = "", $sid = "") {
   //$esubmit = ( strpos($_SESSION['roles'],"esubmit") ) === false ? FALSE : TRUE;
   $esubmit = $_SESSION['roles']['esubmit'];
   if ($hostip == "") {
      return "No IP specified to locate exceptions.";
   }
   // modified to look for exceptions that include the specific IP as
   //  well as any that are tagged for "ALL" hosts, not "Rejected" 
   //  exceptions, though.
   $equery="select eid, status, hostip, submitDate
            from vuln_exceptions
            where ( hostip LIKE '%$hostip%' OR hostip = 'ALL' )
               and plugin = '$plugin'
               and status <> 'Rejected'";
   if (is_numeric($port)) {
      $equery .= " and ( port = " . intval($port) . " OR port IS NULL )"; 
   }

   $eresult=$dbconn->execute($equery);
   if(!$eresult) { 
      return $dbconn->ErrorMsg(); 
   } else {
      $resArray = $eresult->GetArray();
      if (count($resArray) > 0) {
         foreach($resArray as $num=>$resData) {
            if($excel) {
               $foo .= "$resData[eid] - $resData[submitDate] - $resData[status]\n";
            } else {
               $foo .= "<a href='exceptions.php?disp=view&eid=$resData[eid]' title='View Exception' onClick=\"popup('exceptions.php?disp=view&eid=$resData[eid]','ViewException');return false;\">";
               $foo .= "$resData[eid]</a> - $resData[submitDate] - $resData[status]<br>\n";
            }
         }
      } else {
         $foo = "No Exceptions on file.";
         if($esubmit && !$excel) {
            $foo .= "<br><a href='exceptions.php?disp=new&hostip=$hostip&sid=$sid&plugin=$plugin&hostname=$hostname&port=$port' target='_blank'>Add Exception</a>";
         }
      }
      return $foo;
   }
}

function printLegend ( ) {

	$arrRating = array(
		"4" => array ( "color" => "green", "description" => "HOSTNAME IS ALIVE"),
		"3" => array ( "color" => "blue", "description" => "HOSTNAME/IP ( EXACT MATCH - NOT ONLINE )"),
		"2" => array ( "color" => "yellow", "description" => "IP DIFFERS FROM RECORD ( LIKLEY DHCP - HOST NOT ONLINE )"),
		"1" => array ( "color" => "red", "description" => "IP IS ALIVE ( HOSTNAME NOT IN DNS )"),
		"0" => array ( "color" => "black", "description" => "DEAD HOST ( HOSTNAME NOT IN DNS )"),
	);  
   
$htmldetails = "<b>LEGEND</b>
<table width=\"100%\" border=\"0\">
   <tr><td width=\"30\" >VALUE</td><td>DESCRIPTION</td></tr>";

	foreach( $arrRating as $name => $value ) {   
		$htmldetails .="<tr><td width=\"30\" bgcolor=\"$value[color]\">$name</td><td>$value[description]</td></tr>";	
	}	
	$htmldetails .="</table>";
	return $htmldetails;
}

function arrPortResultsCompare ($arrA, $arrB)
{
   if ($arrA[1]==$arrB[1]) { return 0; }
   return $arrA[1]>$arrB[1] ? 1 : -1;
}

function arrScanResultsCompare ($arrA, $arrB)
{
   global $sortby;

   if ($sortby==1) {
# Sort by port (tcp first, then udp) followed by risk (highest first)
      if (($arrA[1]==$arrB[1]) && ($arrA[2]==$arrB[2])) {
         if ($arrA[4]==$arrB[4]) { return 0; }
         if ($arrA[4]<$arrB[4]) { return -1; }
         if ($arrA[4]>$arrB[4]) { return 1; }
      }
      if (($arrA[1]==$arrB[1]) && ($arrA[2]>$arrB[2])) { return 1; }
      if (($arrA[1]==$arrB[1]) && ($arrA[2]<$arrB[2])) { return -1; }
      return $arrA[1]>$arrB[1] ? 1 : -1;
   } else {
# Sort by risk (highest first) followed by port (tcp first, then udp)
      if ($arrA[4]==$arrB[4]) {
         if (($arrA[1]==$arrB[1]) && ($arrA[2]==$arrB[2])) { return 0; }
         if (($arrA[1]==$arrB[1]) && ($arrA[2]>$arrB[2])) { return 1; }
         if (($arrA[1]==$arrB[1]) && ($arrA[2]<$arrB[2])) { return -1; }
         if ($arrA[1]>$arrB[1]) { return 1; }
         if ($arrA[1]<$arrB[1]) { return -1; }
      }
      return $arrA[4]>$arrB[4] ? 1 : -1;
   }
}

function make_link ($type,$data) {
   switch ($type) {
   case 'exception':
      return "<a href='editException.php?op=view&eid=$data'>$data</a>";
   default:
      break;
   }
}

function drawForm($data, $action, $method = "POST") {
   $size = "";
   $maxsize = "";
   $cols = "";
   $rows = "";
   $onsubmit = "";

   if ( !empty($data) ) {
      if ( ! empty( $data['onsubmit'])) {
         $onsubmit = "onSubmit=\"$data[onsubmit]\"";

      }
      echo "<form action=\"$action\" method=\"$method\" $onsubmit>\n";
      echo "<table summary='$data[summary]'>\n";
      foreach ($data['fields'] as $name=>$fd) {
         echo "<tr><th>$name</th>\n";
         if(is_array($fd)) {
            switch($fd['type']) {
            case 'input':
               if(! empty( $fd['size'])) { $size = " size=".$fd['size']; } else { $size = ""; }
               if(! empty($fd['maxsize'])) { $mazsize = " maxsize=".$fd['maxsize']; }
               echo "<td><input name='$fd[name]' $size $maxsize value='".$fd['value']."'></input>\n";
               break;
            case 'select':
               // for a select, the "value" element of the array is an
               // array itself
               if(! empty($fd['multiple'])) {
                  echo "<td><select name='".$fd['name']."' multiple size=".$fd['multiple'].">\n";
               } else {
                  echo "<td><select name='".$fd['name']."'>\n";
               }
               if (! empty($fd['value'] )) {
                  foreach ($fd['value'] as $name=>$val) {
                     // if this is a one dimensional array, make the name = val
                     if($fd['nameIndex'] == 0) { $name = $val; }
                     // if we have a "selected" element in the array
                     if(is_array($fd['selected'])) {
                        if(in_array($name,$fd['selected'])) {
                           echo "<option value='$name' selected>$val</option>\n";
                        } else {
                           echo "<option value='$name'>$val</option>\n";
                        }
                     } elseif($name == $fd[selected]) {
                        echo "<option value='$name' selected>$val</option>\n";
                     } else {
                        echo "<option value='$name'>$val</option>\n";
                     }
                  }
               }
               echo "</select>\n";
               break;
            case 'twobox': // two-box select, you move the selected items
                           // between box A and box B to add/remove them
               // the "value" element of the array is an
               // array itself
               if($fd['id'] <> "") {
                  $fdid1 = "id='all_$fd[id]'";
                  $fdid2 = "id='$fd[id]'";
               }
               $tablehead = <<<EOT
<td>
<table summary='UserSelection'>
<tr>
<th>Available</th>
<th></th>
<th>Assigned</th></tr>
<tr><td>
EOT;
               $sel1 = "<select name='all_$fd[name]' $fdid1 multiple size=".$fd['multiple'].">\n";
               $sel2 = "<select name='".$fd['name']."' $fdid2 multiple size=".$fd['multiple'].">\n";
               // loop through all the entries in $fd[value] and populate
               // the select boxes
               foreach ($fd['value'] as $name=>$val) {
                  // if this is a one dimensional array, make the name = val
                  if($fd['nameIndex'] == 0) { $name = $val; }
                  // if we have a "selected" element in the array
                  if(is_array($fd['selected'])) {
                     // if it's in the "selected" array, do put it in the
                     // all_* select box
                     if(in_array($name,$fd['selected'])) {
                        $sel2 .= "<option value='$name'>$val</option>\n";
                     } else {
                        $sel1 .= "<option value='$name'>$val</option>\n";
                     }
                  } elseif($name == $fd['selected']) {
                     $sel2 .= "<option value='$name'>$val</option>\n";
                  } else {
                     $sel1 .= "<option value='$name'>$val</option>\n";
                  }
               }
               $sel1 .= "</select>\n";
               $sel2 .= "</select>\n";
               // draw the two move buttons
               $btns = "<input type='button' value='Add >>' onclick=\"move2(this.form.all_$fd[id],this.form.$fd[id])\">";
               $btns .= "<br/>";
               $btns .= "<input type='button' value='<< Remove' onclick=\"move2(this.form.$fd[id],this.form.all_$fd[id])\">";
               // output it all
               echo $tablehead;
               echo $sel1;
               echo "</td><td>";
               echo $btns;
               echo "</td><td>";
               echo $sel2;
               echo "</td></tr></table>";
               break;
            case 'textarea':
               if(! empty( $fd['cols'] )) { $cols = " cols=$fd[cols]"; }
               if(! empty( $fd['rows'] )) { $rows = " rows=$fd[rows]"; }
               echo "<td><textarea name='$fd[name]' $cols $rows>$fd[value]</textarea>\n";
               break;
            case 'timeinput':
               // timeinput is a special input class
               // we want to print out select boxes for the month, day and year
               // and have a text box for the hour:min
               //  see if we have been given a starting value and set the
               //  initial values accordingly
               if(isset($fd['value'])) {
                  list($dmy,$hms) = explode(" ",$fd['value']);
                  list($year,$mon,$day) = explode("-",$dmy);
                  list($hr,$min,$sec) = explode(":",$hms);
               }
               echo "<td>";
               echo "<select name='year'>\n";
               for($y=2007;$y<=2010;$y++){
                  if($y == $year) {
                     echo "<option value='$y' selected>$y</option>\n";
                  } else {
                     echo "<option value='$y'>$y</option>\n";
                  }
               }
               echo "</select>\n";
               echo "<select name='month'>\n";
               for($m=1;$m<=12;$m++) {
                  if($m == $mon) {
                     echo "<option value='$m' selected>$m</option>\n";
                  } else {
                     echo "<option value='$m'>$m</option>\n";
                  }
               }
               echo "</select>\n";
               echo "<select name='day'>\n";
               for($d=1;$d<=31;$d++) {
                  if($d == $day) {
                     echo "<option value='$d' selected>$d</option>\n";
                  } else {
                     echo "<option value='$d'>$d</option>\n";
                  }
               }
               echo "</select>\n";
               $time = ($time == "") ? "18:30" : "$hr:$min";
               echo "<input name='time' value='$time'></input>\n";
            }
            if(!empty($fd['comment'])) {
               echo "&nbsp;&nbsp;$fd[comment]";
            }
            echo "</td>\n";
         } else {
            echo "<td>$fd</td>\n"; 
         }
         echo "</tr>\n";
      }
      echo "</table>\n";
      // now draw the form buttons
      if ( ! empty( $data['buttons'])) {
         foreach($data['buttons'] as $name=>$fb) {
            switch ($fb['type']) {
               case 'submit':
                  echo "<input type='submit' name='$fb[name]' value='$fb[value]'></input>\n";
                  break;
            }
         }
      }
      // last, tack on any hidden fields
      if ( ! empty( $data['hidden'])) {
         foreach($data['hidden'] as $name=>$val) {
            echo "<input type='hidden' name='$name' value='$val'></input>\n";
         }
      }
      echo "</form>\n";
   }
}

function drawTable($map, $data, $summary = "", $hosts=array()) {
   echo "<center><table class='table_list' summary='$summary' width='100%' cellspacing='0' cellpadding='3'>";
   echo "<tr>";
   if ( !empty($map)) {
      foreach ($map as $fieldName=>$fieldValue) {
         if($fieldName != "Links") {
            // see if we need to create sorting links in the table headers
           if($fieldName=="Targets"){
               echo "<th width='150'>"._("Targets")."</th>";
              }
           elseif(is_array($fieldValue)) {
               $link = str_replace("%var%", $fieldValue['var'], $fieldValue['link']);
                  if($fieldName=="Date/Time"){
                      echo "<th width='130'><a href='$link' text='Sort by $fieldName'>"._("Date")."/"._("Time")."</a></th>";
                   }
                   elseif($fieldName=="Job Name"){
                      echo "<th><a href='$link' text='Sort by $fieldName'>"._("Job Name")."</a></th>";
                   }
                   elseif($fieldName=="Serious"){
                      echo "<th width='170'><div style='float:left;width:19%;'><img src='./images/serious.png'/></div>";
                   }
                   elseif($fieldName=="High"){
                      echo "<div style='float:left;width:19%;'><img src='./images/high.png'/></div>";
                   }
                   elseif($fieldName=="Medium"){
                      echo "<div style='float:left;width:19%;'><img src='./images/medium.png'/></div>";
                   }
                   elseif($fieldName=="Low"){
                      echo "<div style='float:left;width:19%;'><img src='./images/low.png'/></div>";
                   }
                   elseif($fieldName=="Info"){
                      echo "<div style='float:left;width:19%;'><img src='./images/inf.png'/></th>";
                   }
                   else {
                      echo "<th><a href='$link' text='Sort by $fieldName'>"._($fieldName)."</a></th>";
                   }
            }
              else{
               echo "<th>$fieldName</th>";
            }
         } else {
            echo "<th>&nbsp;</th>";
         }
      }
   }
   echo "</tr>";
   $already = array();
   if ( !empty($data)){
      $selenium_id = 0;
      
      foreach($data as $entry) {
        $job_name = $entry['jobname'];
        $scan_submit = $entry['scan_submit'];
        $scantime = $entry['scantime'];

        $agg = subjobs($job_name,$scan_submit,$data);
        if (count($agg)>1) {
            //print_r($agg);
            if (!in_array($job_name."-".$scan_submit,$already)) {
                $already[] = $job_name."-".$scan_submit;
                # aggregate data
                $entry['target'] = $entry['profile'] = '';
                $entry['hlink'] = "reshtml.php?disp=html&amp;output=full&scansubmit=".$scan_submit."&amp;scantype=M";
                $entry['plink'] = "respdf.php?scansubmit=".$scan_submit."&amp;scantype=M";
                $entry['xlink'] = "rescsv.php?scansubmit=".$scan_submit."&amp;scantype=M&amp;scantime=".$scantime;
                $entry['vInfo'] = 0;
                $entry['vLow'] = 0;
                $entry['vMed'] = 0;
                $entry['vHigh'] = 0;
                $entry['vSerious'] = 0;
                foreach($agg as $entry2) {
                    $entry['vInfo'] += $entry2['vInfo'];
                    $entry['vLow'] += $entry2['vLow'];
                    $entry['vMed'] += $entry2['vMed'];
                    $entry['vHigh'] += $entry2['vHigh'];
                    $entry['vSerious'] += $entry2['vSerious'];
                }
				$random = time();
                cellprint($map,$data,$entry,'',"ltP_nesi.gif","class='downclick'",$random,$hosts,$selenium_id);
                $i=1;
                foreach($agg as $entry2) {
                    $entry2['jobname'] = date("Y-m-d H:i:s",strtotime($entry2['scantime']));
                    $entry2['scantime'] = '';
                    if($i!=count($agg))
                        cellprint($map, $data, $entry2, '', "ltL_nes2.gif","",$random,$hosts,$selenium_id);
                    else
                        cellprint($map, $data, $entry2, '', "ltL_ne2.gif","",$random,$hosts,$selenium_id);
                    $i++;
                }
            }
        }
        else 
        {
            cellprint($map,$data,$entry,'',"","","",$hosts,$selenium_id);
        }
        
        $selenium_id++;
      }
   }
   echo "</table></center>";
}

function drawTableLatest($map, $data, $summary = "") {
   global $dbconn;
   
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   echo "<center><table summary='$summary' class='table_list' width='100%' cellspacing='0'>";
   echo "<tr>";
   if ( !empty($map)) {
      foreach ($map as $fieldName=>$fieldValue) {
         if($fieldName != "Links") {
            // see if we need to create sorting links in the table headers
           if($fieldName=="Targets"){
               echo "<th width='150'>"._("Targets")."</th>";
              }
           elseif(is_array($fieldValue)) {
               $link = str_replace("%var%", $fieldValue['var'], $fieldValue['link']);
                  if($fieldName=="Date/Time"){
                      echo "<th width='70'>"._("Date")."/"._("Time")."</th>";
                   }
                   elseif($fieldName=="Host - IP"){
                      echo "<th width='110'>"._("Host - IP")."</th>";
                   }
                   elseif($fieldName=="Serious"){
                      echo "<th width='170'><div style='float:left;width:19%;'><img src='./images/serious.png'/></div>";
                   }
                   elseif($fieldName=="High"){
                      echo "<div style='float:left;width:19%;'><img src='./images/high.png'/></div>";
                   }
                   elseif($fieldName=="Medium"){
                      echo "<div style='float:left;width:19%;'><img src='./images/medium.png'/></div>";
                   }
                   elseif($fieldName=="Low"){
                      echo "<div style='float:left;width:19%;'><img src='./images/low.png'/></div>";
                   }
                   elseif($fieldName=="Info"){
                      echo "<div style='float:left;width:19%;'><img src='./images/inf.png'/></th>";
                   }
                   else {
                      echo "<th width='70'>"._($fieldName)."</th>";
                   }
            }
              else{
               echo "<th>$fieldName</th>";
            }
         } else {
            echo "<th width='48'>&nbsp;</th>";
         }
      }
   }
   echo "</tr>";
   $already = array();
   $colors  = array("#FFFFFF", "#EEEEEE");
   $color   = 0;
   $selenium_id = 0;
   
   if ( !empty($data)){
      foreach($data as $entry) {
        $ip   = $entry['hostIP'];
        $ctx  = $entry['ctx'];
        
        $wip  = ($entry['hostIP']!="") ? "AND hostIP='".$entry['hostIP']."'":"";
        $wctx = ($entry['ctx']!="")    ? "AND ctx=UNHEX('".$entry['ctx']."')":"";
        $scantime = $entry['scantime'];
        $agg = subjobslatest($ip, $ctx, $data);
        
        if (count($agg)>1) {
            //print_r($agg);
            if (!in_array($ip,$already)) {
                $already[] = $ip;

                # aggregate data
                $entry['target'] = $entry['profile'] = '';
                $entry['hlink'] = "lr_reshtml.php?ipl=$ip&ctx=$ctx&disp=html&output=full&scantype=M";
                $entry['plink'] = "lr_respdf.php?ipl=$ip&ctx=$ctx&scantype=M";
                $entry['xlink'] = "lr_rescsv.php?ipl=$ip&ctx=$ctx&scantype=M";
                $entry['dlink'] = "index.php?delete_ipl_ctx=".base64_encode($ip."#".$ctx);
                
                $query_lr = "select count(*) as total,risk from (select distinct port, protocol, app, scriptid, msg, risk, hostIP from vuln_nessus_latest_results where falsepositive='N' $wip $wctx) as t group by risk";

                $result_lr=$dbconn->Execute($query_lr);
                while(!$result_lr->EOF) {
                    list ($total, $trik) = $result_lr->fields;
                    if($trik==7)
                        $entry['vInfo'] = $total;
                    else if($trik==6)
                        $entry['vLow'] = $total;
                    else if($trik==3)
                        $entry['vMed'] = $total;
                    else if($trik==2)
                        $entry['vHigh'] = $total;
                    else if($trik==1)
                        $entry['vSerious'] = $total;
                    $result_lr->MoveNext();
                }

                $random = time();
                $entry['scantime'] = '';

                cellprintlatest($map,$data,$entry,$colors[$color%2],"bltP_nesi.gif","class='downclick'",$random,$selenium_id);

                $i=1;
                foreach($agg as $entry2) {
                    $entry2['jobname'] = date("Y-m-d H:i:s",strtotime($entry2['scantime']));
                    $entry2['hostIP']  = '';
                    if($i!=count($agg))
                        cellprintlatest($map, $data, $entry2, "#DEEBDB", "bltL_nes2.gif","",$random,$selenium_id);
                    else
                        cellprintlatest($map, $data, $entry2, "#DEEBDB", "bltL_ne2.gif","",$random,$selenium_id);
                    $i++;
                }
            }
        }
        else {
            cellprintlatest($map,$data,$entry,$colors[$color%2],"","","",$selenium_id);
            }
        $color++;
        $selenium_id++;
      }
   }
   echo "</table></center>";
}

function cellprint ($map, $data, $entry, $color, $image, $class, $random, $hosts, $selenium_id) {
 //
 global $dbconn;
 
 $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
 
 $tz = Util::get_timezone();
 
 if ($color!="" && $entry["report_type"]!="I")
    echo "<tr bgcolor=\"$color\" class='cell$random'>";
 else if($entry["report_type"]=="I")
    echo "<tr bgcolor=\"#FCEAC9\">";
 else
    echo "<tr>";
 if ( !empty($map)){
    foreach($map as $fieldName=>$fieldValue) {
    
       if($fieldName != "Links") {
          // some extra processing to allow custom links
          if(is_array($fieldValue)) {
            if ($fieldValue['var'] =="scantime"){
                if($tz!=0) {
                    $entry[$fieldValue['var']] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($entry[$fieldValue['var']])+3600*$tz);
                }
                else {
                    $entry[$fieldValue['var']] = date("Y-m-d H:i:s",strtotime($entry[$fieldValue['var']]));
                }
                if ($color!="" && $entry["report_type"]!="I"){
                    echo "<td width=\"130\" style=\"text-align:center;background-color: ".$color.";\" class=\"nobborder\" nowrap>".$entry[$fieldValue['var']].(($image!="")? "<image src=\"../pixmaps/theme/$image\" border=\"0\" align=\"absmiddle\">": "")."</td>";
                }
                else if($entry[$fieldValue['var']] != ""){
                    echo "<td width=\"130\" ".(($image!="")? "style=\"text-align:center;padding-right:2px\" class=\"nobborder\"": "")." nowrap>". $entry[$fieldValue['var']] .(($image!="")? "&nbsp;<image src=\"../pixmaps/theme/$image\" border=\"1\" align=\"absmiddle\" value='cell$random' $class>": "")."</td>";
                }
                else {
                    echo "<td width=\"130\" nowrap>&nbsp;</td>";
                }
            }
            elseif ($fieldValue['var'] =="hostip"){
                if ($entry["report_id"]!=""){
                    echo "<td width='150' style='text-align:center;' nowrap>".(($entry["host_name"]!=long2ip($entry["report_id"]) && $entry["host_name"]!="")? $entry['host_name']." (".long2ip($entry['report_id']).")": long2ip($entry['report_id']))."</td>";
                }
                else {
                    echo "<td width='150' style='text-align:center;' nowrap>&nbsp;</td>";
                }
            }
            elseif ($fieldValue['var'] =="target"){
                echo "<td width='150' style='text-align:center;padding:0px 7px;'nowrap>".$entry[$fieldValue['var']]."</td>";
            }
           elseif($fieldValue['var']=="vSerious"){
            if(!is_numeric($entry[$fieldValue['var']])) {
                $disable_report = true;
            }
            else {
                $disable_report = false;
            }
              $vSerious = $entry[$fieldValue['var']];
              echo "<td width='110' nowrap><div style='margin-left:7px;float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vHigh"){
              $vHigh = $entry[$fieldValue['var']];
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vMed"){
              $vMed = $entry[$fieldValue['var']];
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vLow"){
              $vLow = $entry[$fieldValue['var']];
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vInfo"){
              $vInfo = $entry[$fieldValue['var']];
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div></td>";
           }
            else {echo "<td nowrap width='110'>" .(($fieldValue['var'] == "profile" && $entry[$fieldValue['var']]=="") ? _("-"): $entry[$fieldValue['var']]). "</td>";}
          } else {
             echo "<td nowrap>$entry[$fieldValue]</td>";
          }
       } else {
          echo "<td nowrap width='150'>";
          foreach($fieldValue as $linkName=>$linkData) {
             $onclick = "";
             $target = "";
             if ( !empty ( $linkData['onclick'] )) { $onclick = "onclick='".$linkData['onclick']."'"; }
             if ( !empty ( $linkData['target'] )) { $target = "target=\"".$linkData['target'] ."\""; }
             $link = str_replace("%param%", $entry[$linkData['param']], $linkData['url']);

            if(!$disable_report && !($vSerious==0 && $vHigh==0 && $vMed==0 && $vLow==0 && $vInfo==0)) {
            
            	 
	            $report_sid = ($linkName == "HTML Results") ? "id=\"html_report_".$selenium_id."\"" : ""; // for selenium tests
            	
                echo "<a style=\"text-decoration:none\" $report_sid href=\"$link\" alt=\"$linkName\" title=\"$linkName\" $target>
                <img src=\"" .  $linkData['icon'] . "\" alt=\"$linkName\" $onclick ></img></a>";
            }
            else {
                echo "<img class=\"disabled\" src=\"" .$linkData['icon'] . "\" alt=\""._("Report not allowed")."\" title=\""._("Report not allowed")."\"></img>&nbsp;";
            }
          }
            $ips = array();
            if(!preg_match("/scansubmit/",$link)) {
                $ips = hosts_to_insert($dbconn, $entry["report_id"],$hosts);
                if(!$disable_report) {
                ?>
                <a class="greybox" gtitle="<?php echo _("Make this scan job visible for:")?>" gheight="190" href="change_user.php?id=<?php echo $entry["report_id"];?>"><img src="../pixmaps/group.png" title="<?php echo _("Change owner")?>" alt="<?php echo _("Change owner")?>" border="2"></a>
                <?php
                }
                else {
                ?>
                <img src="../pixmaps/group.png" class="disabled" title="<?php echo _("Action not allowed")?>" alt="<?php echo _("Action not allowed")?>" border="2" />
                <?php
                }
            }
            if(count($ips)>0)
                echo "<a class='greybox' gtitle='"._("Insert into assets:")."' gwidth='800' gheight='".(150+count($ips)*30)."' href='add_hosts.php?report_id=".$entry["report_id"]."'><img src='../pixmaps/theme/host_add.png' title='Add Hosts to Assets' alt='Add Hosts to Assets' border='0'></a>";
            if(!$disable_report) {
            ?>
            <a class="greybox" gwidth="550" gtitle="<?php echo _("Compare reports:")?>" gheight="150" href="select_report.php?freport=<?php echo $entry["report_id"];?>"><img src="../pixmaps/compare.png" title="<?php echo _("Compare with other")?>" alt="<?php echo _("Compare with other")?>" border="2"></a>
            <?
            }
            else {?>
                <img src="../pixmaps/compare.png" class="disabled" title="<?php echo _("Action not allowed")?>" alt="<?php echo _("Action not allowed")?>" border="2">
            <?php
            }
            if($entry["report_type"]=="I")  {
            ?>
                <a href="export_nbe.php?report_id=<?php echo $entry["report_id"]; ?>" alt="<?php echo _("Download uploaded nbe file"); ?>" title="<?php echo _("Download uploaded nbe file"); ?>"><img src="../pixmaps/download.png" alt="<?php echo _("Download uploaded nbe file"); ?>"></img></a>
            <?php
            } ?>
            <a id="delete_report_<?php echo $selenium_id ?>" href="javascript:;" onclick="confirmDelete('<?php echo $entry["report_id"]; ?>')" alt="<?php echo _("Delete Report"); ?>" title=" <?php echo _("Delete Report"); ?>"><img src="images/delete.gif" alt="<?php echo _("Delete Report"); ?>"></img></a>
            <?php
            echo "</td>";

       }
    }
    echo "</tr>\n";
 }
 
}

function cellprintlatest ($map, $data, $entry, $color, $image, $class, $random,$selenium_id) {
 //
 global $dbconn;
 
 $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
 
 $tz = Util::get_timezone();
 
 if ($color!="")
    echo "<tr bgcolor=\"$color\" class='cell$random'>";
 else
    echo "<tr>";
 if ( !empty($map)){
    foreach($map as $fieldName=>$fieldValue) {
    
       if($fieldName != "Links") {
          // some extra processing to allow custom links
          if(is_array($fieldValue)) {
            if ($fieldValue['var'] =="scantime"){
                if($entry[$fieldValue['var']] != ""){
                    if($tz!=0) {
                        $entry[$fieldValue['var']] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($entry[$fieldValue['var']])+3600*$tz);
                    }
                    else {
                        $entry[$fieldValue['var']] = date("Y-m-d H:i:s",strtotime($entry[$fieldValue['var']]));
                    }
                    echo "<td width='70' nowrap>" . $entry[$fieldValue['var']] ."</td>";
                }
                else {
                    echo "<td width='70' nowrap>-</td>";
                }
            }
            elseif ($fieldValue['var'] =="hostip"){
                if ($entry["report_id"]=="All"){
                    echo "<td width='150' style='text-align:center;padding:0px !important;' nowrap><b>".$entry["report_id"]."</b></td>";
                }
                else if ($entry["hostIP"]!=""){
                	$hip = (($entry["host_name"]!=$entry["hostIP"] && $entry["host_name"]!="")? $entry['host_name']." (".$entry['hostIP'].")": $entry['hostIP']);
                    echo "<td width='150' ".(($image!="")? "class='nobborder'" : "")." style='text-align:right;".(($image!="")? "padding: 1":"padding-right:26")."px !important;' nowrap><strong>".$hip.(($image!="")? "&nbsp;<image src=\"../pixmaps/theme/$image\" border=\"1\" align=\"absmiddle\" value='cell$random' $class>": "")."</strong></td>";
                }
                else {
                    echo "<td width='150' ".(($image=="ltL_nes2.gif")? "class='nobborder'" : "")." style='padding:0px !important;text-align:right;background-color:#fafafa;' nowrap><b>&nbsp;".(($image!="")? "<image src=\"../pixmaps/theme/$image\" border=\"0\" align=\"absmiddle\">": "")."</b></td>";
                }
            }
            elseif ($fieldValue['var'] =="target"){
                echo "<td width='150' style='text-align:center;' nowrap>".$entry[$fieldValue['var']]."</td>";
            }
           elseif($fieldValue['var']=="vSerious"){
              echo "<td nowrap><div style='margin-left:7px;float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vHigh"){
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vMed"){
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vLow"){
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div>";
           }
           elseif($fieldValue['var']=="vInfo"){
              echo "<div style='float:left;width:18%;'>".$entry[$fieldValue['var']]."</div></td>";
           }
            else {echo "<td nowrap>" .(($fieldValue['var'] == "profile" && $entry[$fieldValue['var']]=="" && $color!="") ? _("-"): $entry[$fieldValue['var']]). "</td>";}
          } else {
             echo "<td nowrap>$entry[$fieldValue]</td>"; 
          }
       } else {
          echo "<td style=\"text-align:center;\" nowrap>";
          foreach($fieldValue as $linkName=>$linkData) {
            $link = str_replace("%param%", $entry[$linkData['param']], $linkData['url']);
             if($link!="") {
                 $onclick = "";
                 $target = "";
                 if ( !empty ( $linkData['onclick'] )) { $onclick = "onclick='".$linkData['onclick']."'"; }
                 if ( !empty ( $linkData['target'] )) { $target = "target=\"".$linkData['target'] ."\""; }
                 
                 if ($link!="") {
                 	$report_sid = ($linkName == "HTML Results") ? "id=\"latest_html_report_".$selenium_id."\"" : ""; // for selenium tests
                 	$report_sid = ($linkName == "DELETE Results") ? "id=\"latest_delete_".$selenium_id."\"" : ""; // for selenium tests
                 	
                    echo "<a $report_sid style=\"text-decoration:none;\" href=\"$link\" alt=\"$linkName\" title=\"$linkName\" $target>
                        <img src=\"" .  $linkData['icon'] . "\" alt=\"$linkName\" $onclick ></img></a>";
                 }
                 else
                    echo "<img src=\"" .  $linkData['icon'] . "\" alt=\"$linkName\" $onclick ></img>&nbsp;&nbsp;";
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
	if ($asset == '')
	{
	    return '-';
    }
	
	if(preg_match("/^([a-f\d]+)#(.*)/i",$asset, $found))
	{
		if (Asset_host::is_in_db($conn, $found[1]))
		{
			$_asset_name = Asset_host::get_name_by_id($conn, $found[1]) . ((!$without_ip) ? " (" .$found[2]. ")" : '');
		}
		elseif (Asset_net::is_in_db($conn, $found[1]))
		{
			$_asset_name = Asset_net::get_name_by_id($conn, $found[1]) . ((!$without_ip) ? " (" .$found[2]. ")" : '');
		}
		else
		{
			$_asset_name = $found[2];
		}
	}
	else 
	{
		$_asset_name = $asset;
	}
	
	return $_asset_name;

}

function exists_feed_tables($dbconn)
{
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $query   = "SELECT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_plugins_feed' AND COLUMN_NAME = 'id')";
    $table = $dbconn->GetOne( $query );
    if (!$table) return false;

    $query = "SELECT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_family_feed' AND COLUMN_NAME = 'id')";
    $table = $dbconn->GetOne( $query );
    if (!$table) return false;

    $query = "SELECT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault' AND TABLE_NAME = 'vuln_nessus_category_feed' AND COLUMN_NAME = 'id')";
    $table = $dbconn->GetOne( $query );    
    if (!$table) return false;
    
    return true;
}

function extract_fields($description)
{
    $fields_regexp = "(Solution|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Insight|Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)";

    $results = array();

    $description = str_replace("\\r", "", $description);
    
    $description = str_replace("\n", " ", $description);

    $description = preg_replace("/" . $fields_regexp . "\s*:/","DELIMITER\\1:",$description);
    
    $fields = explode('DELIMITER', $description);
    
    foreach ($fields as $field)
    {
        if (!empty($field))
        {
            preg_match('/^' . $fields_regexp . ':(.*)/', trim($field), $found);

            if (!empty($found[1]))
            {
                $found[1] = trim($found[1]);
            
                if (preg_match("/(Synopsis|Overview)/", $found[1]))
                {
                    $found[1] = 'Overview';
                }
                else if (preg_match("/(Solution|Fix)/", $found[1]))
                {
                    $found[1] = 'Fix';
                }
                
                $results[$found[1]] = trim(strip_tags($found[2]));
            }
        }
    }
    
    return $results;
}
?>
