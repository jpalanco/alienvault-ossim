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


//
// $Id: lookup.php,v 1.2 2009/12/11 18:01:28 jmalbarracin Exp $
//

/***********************************************************/
/*                    Inprotect                            */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                            */
/*                                                         */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                                    */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                       */
/*                                                         */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                    */
/*                                                         */
/* Contact Information:                                    */
/* inprotect-devel@lists.sourceforge.net                   */
/* http://inprotect.sourceforge.net                        */
/***********************************************************/
/* See the README.txt and/or help files for more           */
/* information on how to use & config.                     */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                                         */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items         */
/* discovered with this program's use.                     */
/***********************************************************/
require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$pageTitle = "Lookup";

$getParams = array( "disp", "id", "op", "nid", "lookup", "eventid", "org", "site", "showlive", "last30" );

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
	   if (isset($_GET[$gp])) { 
         $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES); 
      } else { 
         $$gp=""; 
      }
   }
   break;
}


function subtractTime($hours=0, $minutes=0, $seconds=0, $months=0, $days=0, $years=0) {
	$totalHours = date("H") - $hours;
	$totalMinutes = date("i") - $minutes;
	$totalSeconds = date("s") - $seconds;
	$totalMonths = date("m") - $months;
	$totalDays = date("d") - $days;
	$totalYears = date("Y") - $years;
	$timeStamp = mktime($totalHours, $totalMinutes, $totalSeconds, $totalMonths, $totalDays, $totalYears);
	$myTime = date("Y-m-d H:i:s", $timeStamp);
	return $myTime;
}


function script_details($id, $op, $nid) {
	global $enableNotes, $username, $site_code, $user_sites, $dbconn;
	
	$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

if ($op=="delnote" and $nid<>"") {
    if(!is_numeric($nid)) { 
       require_once 'footer.php';
       echo "Cannot access this page - nid is non numeric"; 
       die();
    }
    logAccess ( "Security violation - Requested non numeric noteid " . $nid );

    $query="delete from nessus_notes 
            where id=$nid and username='$username'";
    $result=$dbconn->execute($query);
}

$result=$dbconn->Execute("SELECT t1.id, t1.name, t2.name, t3.name, t1.copyright, t1.summary, t1.description, t1.version, 
	t1.cve_id, t1.bugtraq_id FROM vuln_nessus_plugins t1
	LEFT JOIN vuln_nessus_family t2 on t1.family=t2.id
	LEFT JOIN vuln_nessus_category t3 on t1.category=t3.id
	WHERE t1.id='$id'");
list($pid, $pname, $pfamily, $pcategory, $pcopyright, $psummary, $pdescription, $pversion, $pcve_id, $pbugtraq_id)= $result->fields;

$pdescription=htmlspecialchars($pdescription, ENT_QUOTES);

echo "
<center><B>Nessus plugin details</B></center>
<B>ID:</B> $pid<BR>
<B>Name:</B> $pname<BR>
<B>Family:</B> $pfamily<BR>
<B>Category:</B> $pcategory<BR>
<B>Copyright:</B> $pcopyright<BR>
<B>Summary:</B> $psummary<BR>
<B>Description:</B> ".preg_replace("/\n/","<br>",$pdescription)."<BR>
<B>Version:</B> $pversion<BR>
<B>CVE IDs: </B>";
$CVEs = preg_split ("/[\s,]+/", $pcve_id);
foreach($CVEs as $CVE){
   echo"<a href=\"http://www.cvedetails.com/cve/$CVE/\" target=\"_blank\">$CVE</a> ";
}
$Bugtraqs = preg_split ("/[\s,]+/", $pbugtraq_id);
echo"<br/><B>Bugtraq IDs: </B>";
foreach($Bugtraqs as $Bugtraq){
   echo"<a href=\"http://www.securityfocus.com/bid/$Bugtraq\">$Bugtraq</a>  ";
}

$result->Close();
echo <<<EOT
<BR/><BR/>

EOT;
   if($enableNotes) {
      echo '<font color="red"><B>Notes:</B></font><BR>';

      $query = "select id, note 
             from vuln_nessus_notes 
             where username='$username' and pid=$id";
      $result=$dbconn->execute($query);
      while (!$result->EOF) {
         list($nid, $note)=$result->fields;
         echo <<<EOT
<hr><a href="lookup.php?op=delnote&amp;nid=$nid&amp;id=$id">
<img alt="Delete Note" src="images/false.png" border=0></a>&nbsp;&nbsp;$note<BR>
EOT;
          $result->MoveNext();
      }

      echo <<<EOT
<BR>
<a href="notes.php?op=add&amp;pid=$id&httpfrom=lookup">
<img alt="Add a Custom Note" src="images/note.png" border=0>
&nbsp;&nbsp;Add a Custom Note</a></font>
EOT;
   }
}

function script_id( $id, $lookup, $details ) {
	global $showlive, $last30, $org, $site, $uroles, $username, $dbconn;
	
	$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    if ( !$uroles['reports'] && !$uroles['admin'] ) {
    	 if ( $org == "" && $site == "" ) {
         	//$org_code = getUserOrg ($username);
			$org_code = "";
    	 }
    } else { $org_code = ""; }

    $sql_filter="";  
    
    if ( $org_code ) { 
    	$sql_filter = " AND ORG='$org_code'";
    } elseif ( $org ) { 
    	$sql_filter = " AND ORG='$org'";
	}
	
    if ( $site ) {
    	$sql_filter .= " AND site_code='$site'";
    } 
          
	
	if ( $lookup == "bysubnets" ) {
		$query = "SELECT t1.site_code, t1.ORG, t3.hostip, t3.hostname, t1.dtLastScanned, t3.service, t3.risk, t3.msg
			FROM vuln_subnets t1
			LEFT JOIN vuln_jobs t2 ON t1.CIDR = t2.fk_name
				AND ( t2.scan_SUBMIT >= t1.dtLastScanned OR t1.report_id = t2.report_id )
			LEFT JOIN vuln_nessus_results t3 ON t2.report_id = t3.report_id
			WHERE $sql_filter  t1.status != 'available' and t1.serial_flag='N' AND
			t3.scriptid='$id' GROUP BY t3.hostip ORDER BY INET_NTOA(t3.hostip) ASC";
	}elseif ( $lookup == "byage" ) {
		subtractTime($hours=0, $minutes=0, $seconds=0, $months=0, $days=0, $years=0);
		$query = "SELECT t1.site_code, t1.ORG, t1.hostip, t1.hostname, t1.lastscandate, t2.service, t2.risk, t2.msg
			FROM vuln_hosts t1
			LEFT JOIN vuln_Incidents t2 ON t1.id = t2.host_id
			WHERE $sql_filter t2.status != 'resolved' AND t2.scriptid='$id' 
			GROUP BY t2.host_id ORDER BY t1.site_code";	
			
	} else {
		$query = "SELECT t2.site_code, t2.ORG, t2.hostip, t2.hostname, t2.lastscandate, t1.service, t1.risk, t1.msg
		  FROM vuln_Incidents t1
		  LEFT JOIN vuln_hosts t2 on t1.host_id=t2.id
		  WHERE t1.scriptid='$id' and t1.status = 'open' $sql_filter ORDER BY t2.ORG,t2.site_code,t2.lastscandate";	
	}
	$result = $dbconn->execute($query);

	
	#ECHO "sql=$query<br>";
	
	
	echo "<table summary=\"Plugin Matches [ <font color=red>$pid</a> ]\" border=\"1\" width=\"100%\">";
	
	
	if ( $details == "1" ) {
		echo "<tr><td colspan=7><h4>Vulnerabilities found:</h4></tr>
		<tr>
			<td><font face=\"Verdana\" color=\"#666666\" size=\"4\"><b>Host&nbsp;&nbsp;</b></font></td>
      		<td><font face=\"Verdana\" color=\"#666666\" size=\"4\"><b>Severity&nbsp;&nbsp;</b></font></td>
      		<td colspan=5><font face=\"Verdana\" color=\"#666666\" size=\"4\"><b>Description&nbsp;&nbsp;</b></font></td>
      	</tr>";
		
	} else {
		echo "<tr><td colspan=7><h4>Vulnerabilities found:</h4></tr>";
		
		
	}
	
	$htmldetails = "";
	$i = 0;
   while (!$result->EOF) {
       list( $sCODE, $sORG, $hostIP, $hostname, $lastscanned, $service, $risk, $msg)=$result->fields;		
		$i = $i += 1;
		$msg=preg_replace("/^[ \t]*/","",$msg);
        $msg=wordwrap(preg_replace("/\n/","<br>",$msg),100,"<br>",1);
        
        if ( $details == "1" ) {
        	$htmldetails .= "<tr>
			<td>$hostIP<br>$hostname</td>
      		<td>$service<br>".getrisk($risk)."</td>
      		<td colspan=5>$msg</td>
      	</tr>";
        	
        } else {
        	if ( $htmldetails == "" ) {
        		$legendcode = "</table><br>". printLegend() . "<br><table border=\"1\" width=\"100%\">";
        		$htmldetails .= "<tr><td>RISK</td><td colspan=6>".getrisk($risk)."</td></tr>
        		<tr><td>SERVICE</td><td colspan=6>$service</td></tr>
        		<tr><td>MSG</td><td colspan=6>$msg</td></tr>
				<tr><td colspan=7>&nbsp;</td></tr>
				$legendcode
				<tr><td colspan=7><h4>VULNERABLE HOSTS</h4></td></tr>
				<tr><td colspan=7>&nbsp;</td></tr>
				<tr><td>COUNT</td>
					<td>STATUS</td>
					<td>HOSTIP</td>
					<td>HOSTNAME</td>
					<td>SITE</td>
					<td>ORG</td>
					<td>LastScanned</td>
				</tr>";
        	}
			if ( $showlive ) {
        		$arrHOST = check_host( $hostname, $hostIP );
        		if ( $arrHOST['hostname'] ) { $hostname = $arrHOST['hostname']; }
        		if ( $arrHOST['hostip'] ) { $hostIP = $arrHOST['hostip']; }
        
        		$rating = $arrHOST['rating'];
        		$rating_color = $arrHOST['rating_color']; 
			}
        		
        	if ( $sCODE == $sORG ) { $sORG = "&nbsp;"; }; # no reason to show it twice
        	
        	if ( !$showlive || $rating >=3 ) {
        	$htmldetails .= "<tr>
        		<td>[$i]</td>
        		<td bgcolor=\"$rating_color\">$rating</td>
        		<td>$hostIP</td>
        		<td>$hostname</td>
        		<td>$sCODE</td>
        		<td>$sORG</td>
        		<td>$lastscanned</td>
        		</tr>";
        	}
        }
        $result->MoveNext();     	
	}
	
	echo $htmldetails;
	
	echo "</table>";
		
}

function result_id( $eventid ) {
     global $userinfo, $user_sites, $dbconn;
     
     $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

     $query = "SELECT hostip, hostname, service, risk, msg 
          FROM vuln_nessus_results
          WHERE result_id='$eventid' LIMIT 1";

     $result = $dbconn->execute($query);
     $numrows = $result->NumRows();

     ECHO "sql=$query<br>";

     if ($numrows < 1) {
          die ("<h3><font color=red>No Results</font></h3>");
     }

     echo "<table summary=\"Plugin Matches [ <font color=red>$pid</a> ]\" border=\"1\" width=\"100%\">";

     if ( $details == "1" ) {
          echo <<<EOT
<tr><td colspan=4><h4>Vulnerabilities found:</h4></tr>
<tr>
   <td><font face="Verdana" color="#666666" size="4"><b>Host&nbsp;&nbsp;</b></font></td>
   <td><font face="Verdana" color="#666666" size="4"><b>Severity&nbsp;&nbsp;</b></font></td>
   <td><font face="Verdana" color="#666666" size="4"><b>Description&nbsp;&nbsp;</b></font></td>
</tr>
EOT;
     } else {
          echo "<tr><td colspan=4><h4>Vulnerabilities found:</h4></tr>";
     }
     $htmldetails = "";
     $i = 0;
     while (!$result->EOF) {
          list( $hostIP, $hostname, $service, $risk, $msg)=$result->fields;	
          $i = $i += 1;
          $msg=preg_replace("/^[ \t]*/","",$msg);
          $msg=wordwrap(preg_replace("/\n/","<br>",$msg),100,"<br>",1);
          $msg=hyperlink($msg);

          if ( $details == "1" ) {
               $htmldetails .= "<tr>
   <td>$hostIP<br>$hostname</td>
   <td>$service<br>".getrisk($risk)."</td>
   <td>$msg</td>
</tr>";
          } else {
               $htmldetails .= "<tr><td>RISK</td><td colspan=4>".getrisk($risk)."</td></tr>
<tr><td>SERVICE</td><td colspan=4>$service</td></tr>
<tr><td>MSG</td><td colspan=4>$msg</td></tr>
<tr><td colspan=4>&nbsp;</td></tr>
<tr><td colspan=4><h4>VULNERABLE HOSTS</h4></td></tr>
<tr><td colspan=4>&nbsp;</td></tr>
<tr><td>HOSTIP</td><td>HOSTNAME</td><td>SITE</td><td>Location</td></tr>
<tr><td>[$i]&nbsp;&nbsp;$hostIP</td><td>$hostname</td>
   <td> $sCODE</td><td>$sORG</td></tr>";
          }
     }

     echo $htmldetails;
     echo "</table>";
}

switch($disp) {

	case "details":
	   script_details($id, $op, $nid);
	   break;
	   	
	case "result":
		result_id( $eventid );
		break;
	
	case "scriptid":
		#script_id($id, $lookup, $details, $site_code );
		break;	
	
    default:
	   script_details($id, $op, $nid);
	   break;
}

?>
