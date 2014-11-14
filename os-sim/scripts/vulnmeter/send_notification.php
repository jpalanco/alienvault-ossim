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


set_include_path('/usr/share/ossim/include');
require_once 'av_init.php'; 

$job_id   = $argv[1];
$message  = $argv[2];

if ($job_id == '')
{
    return FALSE;
}


$levels = array("1" => "Serious:", "2" => "High:", "3" => "Medium:", "6" => "Low:", "7" => "Info:");

$db     = new ossim_db();
$dbconn = $db->connect();

// select data for specified job_id

if (!$result = $dbconn->Execute(ossim_query("SELECT vj.report_id, vns.name as profile, vj.meth_VSET as profile_id, vj.name, vj.username, vj.fk_name, vj.scan_SUBMIT, vj.scan_START, vj.scan_END, TIMESTAMPDIFF(MINUTE, vj.scan_START, vj.scan_END) as duration, vj.meth_TARGET
                            FROM vuln_jobs as vj, vuln_nessus_settings as vns WHERE vj.id=$job_id and vj.meth_VSET=vns.id"))) {
    echo $dbconn->ErrorMsg()."\n";
    $dbconn->close();
}
else 
{
    $report_id = $result->fields["report_id"];
    $username  = $result->fields["username"];

    if (intval($report_id)!=0 || $message != "") 
    {
        $data = Session::get_user_info($dbconn, 'admin', TRUE);
    
        // API Login to read email settings
        // 
        
        $cc = new Alienvault_client();
        $cc->auth()->login('admin', $data->get_pass());
            
        $attachments = array();
        $subject     = _('Scan Job Notification: ').$result->fields["name"];        
                
        $width = 115;
        $body = '<html>
                    <head>
                        <title>'.$subject.'</title>
                        </head>
                        <body>'.
                        '<table width="100%" cellspacing="0" cellpadding="0" style="border:0px;">'.
                        '<tr><td colspan="2" style="text-decoration: underline;">'._('Email scan summary').'</td></tr>'.
                        '<tr><td colspan="2">&nbsp;</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Scan Title:').'</td><td>'.$result->fields["name"].'</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Profile:').'</td><td>'.$result->fields["profile"].'</td></tr>';
                        
        $body .=  '<tr><td width="'.$width.'">'._('Submit Date:').'</td><td>SCAN_SUBMIT</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Start Date:').'</td><td>SCAN_START</td></tr>';
                        
                        
        $body .=  '<tr><td width="'.$width.'">'._('Duration:').'</td><td>'.((intval($result->fields["duration"])==0) ? "< 1 min" : $result->fields["duration"]." mins").'</td></tr>'.
                        '<tr><td colspan="2">&nbsp;</td></tr>'.
                        '<tr><td width="'.$width.'">'._('Launched By:').'</td><td>'.(($result->fields["fk_name"]!="")? $result->fields["fk_name"]:_("Unknown")).'</td></tr>';
                        
		if (valid_hex32($username))
		{
			$visible_for  = Acl::get_entity_name($dbconn, $username);
		}
		else 
		{
            $visible_for = $username;
        }
        
        $body .= '<tr><td width="'.$width.'">'._('Job visible for:').'</td><td>'.(($visible_for!="")? $visible_for:_("Unknown")).'</td></tr>';
        $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
        
        if(intval($report_id)!= 0 && $message == "") 
		{
            $body .= '<tr><td colspan="2" style="text-decoration: underline;">'._('Summary of Scanned Hosts').'</td></tr>';
            $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
        
                                     
            if (!$result_ip_name = $dbconn->Execute(ossim_query("SELECT distinct t1.hostip as ip, HEX(t1.ctx) as ctx FROM vuln_nessus_results t1 
                                                                WHERE t1.report_id=$report_id"))) {
                echo $dbconn->ErrorMsg()."\n";
                $dbconn->close();
                return;
            }
                                     
            $total = 0;
            
            while(!$result_ip_name->EOF) 
            {
                $hostip  = $result_ip_name->fields['ip'];
                $hostctx = $result_ip_name->fields['ctx'];
            
                // read data from vuln_nessus_latest_results to generate stats
                if (!$result_stats=$dbconn->execute("SELECT note FROM vuln_nessus_latest_reports
                                        WHERE hostIP = '$hostip' AND ctx = UNHEX('$hostctx')
                                        AND sid=".$result->fields["profile_id"]." AND username='$username'")) {
                    echo $dbconn->ErrorMsg()."\n";
                    $dbconn->close();
                    
                    return;
                }

                $risk_stats = explode(";",$result_stats->fields["note"]);
                
                $hostname = "";
                
                $query = ossim_query ("SELECT hostname FROM host, host_ip WHERE host.id = host_ip.host_id AND host_ip.ip = UNHEX(?) AND host.ctx = UNHEX(?)");
                
                
                if (! $rs = & $dbconn->Execute ($query, array (bin2hex(inet_pton($hostip)), $hostctx))) 
                {
                    print $dbconn->ErrorMsg();
                } 
                else 
                {
                    if (! $rs->EOF) 
                    {
                        $hostname = $rs->fields['hostname'];
                    }
                }
                
                $body .= '<tr><td width="'.$width.'">'._('Hostname:').'</td><td>'.(($hostname!="")? $hostname:_("Unknown")).'</td></tr>';
                $body .= '<tr><td width="'.$width.'">'._('Ip:').'</td><td>'.$hostip.'</td></tr>';
                
                if (!$result_risk = $dbconn->Execute(ossim_query("SELECT count(risk) as count, risk
                                                    FROM (SELECT DISTINCT risk, port, protocol, app, scriptid, msg, hostIP, HEX(ctx) as ctx FROM vuln_nessus_results
                                                    WHERE report_id  in ($report_id) AND hostip='$hostip' AND ctx=UNHEX('$hostctx') AND FALSEpositive='N') as t GROUP BY risk"))) {
                    echo $dbconn->ErrorMsg()."\n";
                    
                    $dbconn->close();
                    
                    return;
                }

                $subtotal = 0;
                
                while(!$result_risk->EOF) 
                {
                    $count_risk = $result_risk->fields['count'];
                    $risk       = $result_risk->fields['risk'];
                
                    if ($risk=="1")
                    {
                        $diff = intval($count_risk-$risk_stats[0]);
                    }
                    else if ($risk=="2")
                    {    
                        $diff = intval($count_risk-$risk_stats[1]);
                    }
                    else if ($risk=="3")
                    {    
                        $diff = intval($count_risk-$risk_stats[2]);
                    }
                    else if ($risk=="6")
                    {    
                        $diff = intval($count_risk-$risk_stats[3]);
                    }
                    else if ($risk=="7")
                    {
                        $diff = intval($count_risk-$risk_stats[4]);
                    }
                    if ($diff==0)
                    {    
                        $body .= '<tr><td width="'.$width.'">'._($levels[$risk]).'</td><td>'.$count_risk.' (=)</td></tr>';
                    }
                    else
                    {
                        $body .= '<tr><td width="'.$width.'">'._($levels[$risk]).'</td><td>'.$count_risk.' ('.(($diff>0)? "+".$diff : $diff).')</td></tr>';
                    }
                    
                    $subtotal+=$count_risk;
                    
                    $result_risk->MoveNext();
                }
                
                $total+= $subtotal;
                
                $body .= '<tr><td width="'.$width.'">'._("Subtotal:").'</td><td>'.$subtotal.'</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $result_ip_name->MoveNext();
            }
            
            $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
            $body .= '<tr><td width="'.$width.'">'._("Total:").'</td><td>'.$total.'</td></tr>';
            $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
            // show explanation
            $body .= '<tr><td colspan="2">'._("(+)(-)(=): Difference with previous detection for each host/vulnerability pair.").'</td></tr>';
        
        }
        else 
        {
            $body .= '<tr><td colspan="2"><strong>'._("The scan failed:").'</strong></td></tr>';
            $body .= '<tr><td colspan="2">'.$message.'</td></tr>';
        }
                        
        $body .= '</table>';
        $body .= '</body>';
        $body .= '</html>';                

        if(intval($report_id)!=0) 
        {
            // generate PDF
            $query = ossim_query ("SELECT scantime, report_key FROM vuln_nessus_reports WHERE report_id=$report_id");
            if (! $rs = & $dbconn->Execute ($query)) 
            {
                print $dbconn->ErrorMsg();
            } 
            else 
            {
                if (! $rs->EOF) 
                {
                    $scan_END   = $rs->fields['scantime'];
                    $report_key = $rs->fields['report_key'];
                }
            }
            
            $file_path = "/usr/share/ossim/www/tmp/".$result->fields["name"]."_".$scan_END.".pdf";
            $file_path = str_replace(" ", "", $file_path);
            $file_name = $result->fields["name"]."_".$scan_END.".pdf";
            
            exec ("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/respdf.php '".$report_id."' > ".$file_path);
            
            
            if(file_exists($file_path) && filesize($file_path) <= 5242880) 
            {
                $attachments[] = array(
                    "path"  => $file_path,
                    "name"  => $file_name                
                );  
            }                                                                        
        }
        
        if (!valid_hex32($username)) //username is a user
        { 
            $body  = get_timestamps($dbconn, $username, $result->fields['scan_START'], $result->fields['scan_SUBMIT'], $body);         
            
            $email = get_email($dbconn, $username);
                       
            Util::send_email($dbconn, $email, $subject, $body, $attachments);
        }
        else 
        { // username is a entity
            $entity_data = Acl::get_entity($dbconn, $username, FALSE, FALSE);

            if($entity_data["admin_user"]!="") // exists pro admin
            { 
                $body  =  get_timestamps ($dbconn, $entity_data["admin_user"], $result->fields['scan_START'], $result->fields['scan_SUBMIT'], $body);
                
                $email = get_email($dbconn, $entity_data["admin_user"]);
                
                Util::send_email($dbconn, $email, $subject, $body, $attachments);
            }
            else 
            { 
                // doesn't exit pro admin
                $users_list = Acl::get_users_by_entity($dbconn, $username);
                foreach ($users_list as $k => $user_data)  // send an e-mail to each user
                { 
                    if($user_data['email'] != "") 
                    {
                        $body = get_timestamps ($dbconn, $user_data['login'], $result->fields['scan_START'], $result->fields['scan_SUBMIT'], $body);
                                              
                        if ($user_data['email'] != '')
                        {
                            Util::send_email($dbconn, $user_data['email'], $subject, $body, $attachments);
                        }                     
                    }
                }
            }
        }
        
        if(file_exists($file_path)) 
        {
            unlink($file_path);
        }
        
        $cc->auth()->logout();
    }
    
    $dbconn->close();

}



function get_timestamps ($dbconn, $login, $scan_START, $scan_SUBMIT, $body) 
{
    $user_timezone = $dbconn->GetOne("SELECT timezone FROM users WHERE login='".$login."'");

    $tz = get_timezone($user_timezone);

    if($tz != 0) 
    {
        $scan_START  = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($scan_START)+(3600*$tz));
        $scan_SUBMIT = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($scan_SUBMIT)+(3600*$tz));
    }

    $body_part_with_timestamp = str_replace("SCAN_SUBMIT", $scan_SUBMIT, $body);
    $body_part_with_timestamp = str_replace("SCAN_START", $scan_START, $body_part_with_timestamp);

    return $body_part_with_timestamp;
}


function get_timezone($tz="") 
{
    if (is_numeric($tz)) 
    {
        return $tz; // old numeric stored format
    }
    
    $this_tz = new DateTimeZone($tz);
    $offset  = $this_tz->getOffset(new DateTime("now", $this_tz));
    
    if ($offset!=0) 
    {
        $offset /= 3600;
    }
    
    return $offset;
}


function get_email ($dbconn, $login) 
{
    $user_email = "";

    $query = ossim_query ( "SELECT email FROM users WHERE login=?" );
    if (! $rs = & $dbconn->Execute ( $query, array ($login) )) 
    {
        print $dbconn->ErrorMsg();
    } 
    else 
    {
        if (!$rs->EOF) 
        {
            $user_email = $rs->fields['email'];
        }
    }
    
    return $user_email;
}


?>