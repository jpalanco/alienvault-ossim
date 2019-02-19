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

require_once 'av_init.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$db = new ossim_db();
$dbconn = $db->connect();

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$sid = GET("sid");
$fam = GET("family");
$cve = GET("cve");

ossim_valid($sid, OSS_DIGIT, 'illegal:' . _("sid"));
ossim_valid($fam, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("family"));
ossim_valid($cve, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("cve"));
if (ossim_error()) {
    die(ossim_error());
}

$falsepositives = array();

$query = "select t1.id from vuln_nessus_settings_plugins as t1, vuln_nessus_results as t2
            where t1.id=t2.scriptid and t2.falsepositive='Y' and t1.sid=$sid";
$result = $dbconn->Execute($query);
while ( !$result->EOF ) {
    $falsepositives[] = $result->fields['id'];
    $result->MoveNext(); 
}

$data = array();
if ($fam!=""){
    /*$famQuery = "SELECT t4.name AS nfamily, t1.cve_id AS cve, t1.id, t1.name, t3.name AS pname, t2.enabled
                FROM vuln_nessus_plugins AS t1
                LEFT JOIN vuln_nessus_category AS t3 ON t3.id = t1.category, vuln_nessus_settings_plugins AS t2
                LEFT JOIN vuln_nessus_family AS t4 ON t4.id = t2.family
                WHERE t4.name = '$fam'
                AND t1.id = t2.id
                AND t2.sid = $sid
                ORDER BY t1.name";*/

    $famQuery = "select t1.cve_id as cve, t1.id, t1.name, t3.name as pname, t2.enabled from vuln_nessus_plugins as t1
                 left join vuln_nessus_category as t3 on t3.id=t1.category, vuln_nessus_settings_plugins as t2
                 where t2.family=$fam and t1.id=t2.id 
                 and t2.sid=$sid order by t1.name";

    $stmt = $dbconn->Prepare($famQuery);
    $data = $dbconn->GetArray($stmt);
} elseif ($cve!=""){
    $cveQuery = "select t1.cve_id as cve, t1.id, t1.name, t3.name as pname, t2.enabled from vuln_nessus_plugins as t1
                left join vuln_nessus_category as t3 on t3.id=t1.category, vuln_nessus_settings_plugins as t2 where t1.id=t2.id 
                and t2.sid=$sid and t1.cve_id like '%$cve%'";
    
    $stmt = $dbconn->Prepare($cveQuery);
    $data = $dbconn->GetArray($stmt);
}


//$name = ($fam!="") ? $data[0]['nfamily']: $cve;

$name = ($fam!="") ? $fam: $cve;


$text = "<center>";
$text .= "<form method='post' action='settings.php' >";
$text .= "<input type='hidden' name='disp' value='saveplugins'>";
$text .= "<input type='hidden' name='sid' value='$sid'>";

if ($fam!="") {
    $text .= "<input type='hidden' name='fam' value='$fam'>";
}
else {
    $text .= "<input type='hidden' name='cve' value='$cve'>";
}
$text .= "<table width='800' id='ptable' class='table_list'>\n";

$text .= "<tr>";
$text .= "<th width='50'>"._("Enabled")."</th>";
$text .= "<th width='150'>"._("VulnID")."</th>";
$text .= "<th>"._("Vuln Name")."</th>";
$text .= "<th width='110'>"._("CVE Id")."</th>";
$text .= "<th>"._("Plugin Category")."</th>";
$text .= "</tr>\n";
foreach($data as $element) {
    if (in_array($element['id'], $falsepositives)){
        $text .= "<tr bgcolor=\"#FFCFD1\">";
    }
    else {
        $text .= "<tr>";
    }
    $checked = "";
    if($element['enabled'] == "Y") { $checked = "checked='checked'"; }
    $text .= "<td><INPUT class='plugin' type=checkbox name='PID" . $element['id'] . "' id='" . $element['id'] . "' $checked></input></td>";
    if (in_array($element['id'], $falsepositives)){
        $text .= "<td><img alt=\""._("Mark as false positive")."\" title=\""._("Mark as false positive")."\" src=\"images/false.png\" border=\"0\" align=\"absmiddle\">&nbsp;&nbsp;". $element['id'] . "</td>";
    }
    else {
        $text .= "<td>" . $element['id'] . "</td>";
    }
    $text .= "<td style=\"text-align:left;\"><a href='javascript:;' lid='".$element['id']."' class='scriptinfo' style='text-decoration:none;'>".$element['name']."</a></td>";
    $text .= "<td>";
    if($element['cve']=="") {
        $text .= "-";
    }
    else {
        $listcves = explode(",", $element['cve']);
        foreach($listcves as $c){
            $c = trim($c);
            $text .= "<a href='http://www.cvedetails.com/cve/$c/' lid='".$element['id']."' class='scriptinfo' style='text-decoration:none;' target='_blank'>$c</a><br>";
        }
    }
    $text .= "</td>";
    $text .= "<td>" . strtoupper($element['pname']). "</td>";
    $text .= "</tr>\n";
   }
    $text .= "</table><br>\n";
    $text .= "<input type='button' name='cbAll' value='"._("Check All")."' onclick=\"CheckEmp(this.form, true);\" class=\"av_b_transparent\"/>";
    $text .= "&nbsp;&nbsp;";
    $text .= "<input type='button' name='cbAll' value='"._("UnCheck All")."' onclick=\"CheckEmp(this.form, false);\" class=\"av_b_transparent\"/>";
    $text .= "&nbsp;&nbsp;";
    $text .= "<input type=\"button\" name=\"saveplugins\" value=\""._("Update")."\" id=\"updateplugins\" class=\"button\"></form>";
    $text .= "</center>\n";
    
    $sIDs = array();

    if ( Vulnerabilities::scanner_type() == "omp" ) {
        
    	list($sensor_list, $total) = Av_sensor::get_list($dbconn);
    
    	foreach ($sensor_list as $sensor_id => $sensor_data) 
    	{
            if( intval($sensor_data['properties']['has_vuln_scanner']) == 1)
            {
                $sIDs[] = array( 'name' => $sensor_data['name'], 'id' => $sensor_id );
            }
    	}    
    }
    
    $text .= "<script type='text/javascript'>
                $('#updateplugins').on('click', function(event) {
                
                	$('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').attr('disabled', 'disabled');
					$('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').addClass('disabled');
                	
                    $('#updates_info').hide();
                    
                    var ids = ".json_encode($sIDs).";
                    
                    var active_plugins = [];
                    $('input:checked').each(function(index) {
                        active_plugins.push($(this).attr('name').replace('PID', ''));
                    });
                    
                    clean_updates_table();
                    
                    window.scrollTo(0, 0);
                    
                    notifications_changes('Updating Database', 'database', 'loading', '');
                    
                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        data: { type: 'save_database_plugins', sid:'".$sid."', fam:'".$fam."', cve:'".$cve."', plugins: active_plugins.join(','), action: 'Update' },
                        dataType: 'json',
                        success: function(dmsg) {
                            notifications_changes('Updating Database', 'database', dmsg.status, dmsg.message);
                            var sensor_count = 0;
                            $.each(ids, function(k,v){
                                notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');
                            
                                $.ajax({
                                    type: 'POST',
                                    url: 'profiles_ajax.php',
                                    dataType: 'json',
                                    data: { type: 'save_sensor_plugins', sid:'".$sid."', sensor_id: v.id, fam:'".$fam."', action: 'Update' },
                                    success: function(msg) {
                                        var status;
                                    
                                        if (msg == null) {
                                            status = 'error';
                                        }
                                        else {
                                            status = msg.status;
                                        }
                                        notifications_changes(v.name + ' Sensor ' + ' update', v.id, status, msg.message);
                                        
                                        sensor_count++;
											
										if(sensor_count == ids.length) {
											$('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').removeAttr('disabled');
											$('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').removeClass('disabled');
										}
                                    }
                                });
                            });
                        }
                    });
                });
            </script>";
    
    $dbconn->disconnect();
    echo $text;
?>
