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

ini_set("max_execution_time","360");
require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$delete          = GET('delete');
$scantime        = GET('scantime');
$rvalue          = GET('rvalue');
$value           = GET('value');
$ctx_filter      = GET('ctx');
$type            = GET('type');
$sortby          = GET('sortby');
$widget_mode     = GET('widget_mode');

ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE,                                            'illegal:' . _("delete"));
ossim_valid($scantime, OSS_DIGIT, OSS_NULLABLE,                                          'illegal:' . _("scantime"));
ossim_valid($rvalue, OSS_SCORE, OSS_NULLABLE, OSS_DOT, OSS_ALPHA, OSS_SPACE, OSS_COLON,  'illegal:' . _("rvalue"));
ossim_valid($value, OSS_TEXT, OSS_NULLABLE,                                              'illegal:' . _("value"));
ossim_valid($ctx_filter, OSS_NULLABLE, OSS_HEX,                                          'illegal:' . _("ctx_filter"));
ossim_valid($type, OSS_ALPHA, "hn", "freetext", "service", OSS_NULLABLE,                 'illegal:' . _("type"));
ossim_valid($delete_ipl_ctx, OSS_BASE64, OSS_NULLABLE,                                   'illegal:' . _("delete_ipl_ctx"));
ossim_valid($sortby, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,                                  'illegal:' . _("sort by"));
ossim_valid($widget_mode, OSS_DIGIT, OSS_NULLABLE,                                       'illegal:' . _("Widget Mode"));

if (ossim_error()) {
    die(ossim_error());
}

$net = "";

if ( $type=="net" && preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$value) ){
	$net = $value;
}

$widget_mode = ($widget_mode == 1) ? TRUE : FALSE;

$db = new ossim_db();
$conn = $db->connect();

// number of hosts to show per page with viewing all
$pageSize = 10;
$allres   = 1;

$getParams = array( "disp", "op", "output", "scantime", "scantype", "reporttype", "key", "offset", "sortdir", "allres", "fp","nfp", "wh", "bg", "filterip", "critical", "increment","type","value", "delete", "delete_ipl_ctx", "roffset", "sreport");
$postParams = array( "disp", "op", "output", "scantime", "type", "value", "offset",
    "scantype", "fp","nfp", "filterip", "critical", "increment", "roffset", "sreport");
$post = FALSE;

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
       if (isset($_GET[$gp])) {
         $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES);
      } else {
         $$gp = "";
      }
   }
    break;
case "POST" :
   $post = TRUE;
   foreach($postParams as $pp) {
      if (isset($_POST[$pp])) {
        $$pp=htmlspecialchars(mysql_real_escape_string(trim($_POST[$pp])), ENT_QUOTES);
      } else {
        $$pp = "";
      }
   }
    break;
}

$offset  = intval($offset);   // latest results table

$roffset = intval($roffset);  // reports table

$sreport = intval($sreport);  // to show reports

//for autocomplete input

$autocomplete_keys = array('hosts_ips', 'nets_cidrs', 'sensors');
$assets            = Autocomplete::get_autocomplete($dbconn, $autocomplete_keys);

// ctx permissions

$perms_where = (Session::get_ctx_where() != "") ? " AND ctx in (".Session::get_ctx_where().")" : "";

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($conn);

// Delete Section

if( !empty($delete) && !empty($scantime) ){ // a single scan in latest results tables
    $params = array($delete, $scantime);
    $query = "SELECT hostIP, HEX(ctx) as ctx, sid, username FROM vuln_nessus_latest_reports WHERE report_key=? and scantime=? $perms_where";
    $result=$dbconn->execute($query, $params);

    if(Session::hostAllowed_by_ip_ctx($dbconn, $result->fields["hostIP"], $result->fields["ctx"])) {
        $dhostIP   = $result->fields["hostIP"];
        $dctx      = $result->fields["ctx"];
        $dusername = $result->fields["username"];
        $dsid      = $result->fields["sid"];

        $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_key=? and scantime=? $perms_where";
        $result=$dbconn->execute($query, $params);

        $params = array($dhostIP, $dctx, $dusername, $dsid, $scantime);
        $query = "DELETE FROM vuln_nessus_latest_results WHERE hostIP=? and ctx=UNHEX(?) and username=? and sid=? and scantime=? $perms_where";

        $result=$dbconn->execute($query, $params);
        
        Util::memcacheFlush();
    }
}
else if( !empty($delete) ) // a report
{

    $query_onlyuser = (!empty( $arruser)) ?  " AND username in ($user)" : "" ;

    $dbconn->execute("DELETE FROM vuln_jobs WHERE report_id = $delete $query_onlyuser");
    
    $dbconn->execute("DELETE FROM vuln_nessus_reports WHERE report_id = $delete $query_onlyuser");
    
    $report_id = $dbconn->GetOne("SELECT report_id FROM vuln_nessus_reports WHERE report_id = ".$delete);
    
    if ($report_id =="")
    {
        $dbconn->execute("DELETE FROM vuln_nessus_results WHERE report_id = $delete");
    
        if ( file_exists("/usr/share/ossim/uploads/nbe/".$delete.".nbe") ) {
            unlink("/usr/share/ossim/uploads/nbe/".$delete.".nbe");
        }
    }
    
    Util::memcacheFlush();
}
else if( !empty($delete_ipl_ctx) ) { // all results for a IP in latest results
    $delete_ipl_ctx  = base64_decode($delete_ipl_ctx);
    list($ipl, $ctx) = explode("#", $delete_ipl_ctx);

    if(Session::hostAllowed_by_ip_ctx($dbconn, $ipl, $ctx)) {
        $params = array($ipl, $ctx);

        $query = "DELETE FROM vuln_nessus_latest_reports WHERE hostIP=? and ctx=UNHEX(?) $perms_where";
        $result=$dbconn->execute($query, $params);

        $query = "DELETE FROM vuln_nessus_latest_results WHERE hostIP=? and ctx=UNHEX(?) $perms_where";
        $result=$dbconn->execute($query, $params);
        
        Util::memcacheFlush();
    }
}

function list_reports($type, $value, $sortby, $sortdir, $widget_mode ) {

   global $allres, $roffset, $pageSize, $dbconn;
   global $user, $arruser;
   
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $filteredView = FALSE;

   $selRadio = array( "","","","");

   $query_onlyuser="";
   $url_filter="";

   if(!empty($arruser)) {$query_onlyuser = " AND t1.username in ($user)";}

   if( !empty($sortby) ) {
        $or_sortby = $sortby;
   }
   else {
        $or_sortby = "";
   }

   if( !empty($sortdir) ) {
        $or_sortdir = $sortdir;
   }
   else {
        $or_sortdir = "";
   }

   if ($sortby == "jobname") {
        $sortby    = "t2.name";
   }
   else if ($sortby == "profile") {
        $sortby    = "t3.name";
   }

   if ($sortby == "" ) { $sortby = "scantime"; }
   if ($sortdir == "" && !preg_match("/(ASC|DESC)$/i",$sortby)) { $sortdir = "DESC"; }

    $queryw="";
    $queryl="";

    $leftjoin = "";
    if ($type=="net") $leftjoin = "LEFT JOIN vuln_nessus_results t5 ON t5.report_id=t1.report_id";

     $querys="SELECT distinct t1.sid as sid, t1.report_id, t4.name as jobname, t4.scan_submit, t4.meth_target, t1.scantime,
     t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t3.name as profile, t4.id as jobid, t4.meth_SCHED, t1.name as report_name
     FROM vuln_nessus_reports t1
     LEFT JOIN vuln_jobs t2 ON t1.report_id=t2.report_id
     LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
     LEFT JOIN vuln_jobs t4 on t1.report_id = t4.report_id $leftjoin
     WHERE t1.deleted = '0' AND t1.scantime IS NOT NULL ";

    //Set up the SQL query based on the search form input (if any)
    switch($type) {
		case "scantime":
			$selRadio[0] = "CHECKED";
			$utc_data    = Util::get_utc_from_date($dbconn, $value, Util::get_timezone());
		    $q           = $utc_data[6];
			$q           = str_replace("-","",$q);
			$q           = str_replace(":","",$q);
            $q           = str_replace(" ","",$q);
			$queryw      = " AND t1.scantime LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
			$queryl      = " limit $roffset,$pageSize";
			$stext       =  "<b>"._("Search for Date/Time")."</b> = '*$value*'";
			$url_filter  = "&type=$type&value=$value";
		break;

		case "jobname":
			$selRadio[1] = "CHECKED";
			$q           = strtolower($value);
			$queryw      = " AND t1.name LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
			$queryl      = " limit $roffset,$pageSize";
			$stext       =  "<b>"._("Search for Job Name")."</b> = '*".html_entity_decode($q)."*'";
			$url_filter  = "&type=$type&value=$value";
		break;

		case "fk_name":
			$selRadio[2] = "CHECKED";
			$q           = strtolower($value);
			$queryw      = " AND t1.fk_name LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
			$queryl      = " limit $roffset,$pageSize";
			$stext       = _("Search for Subnet/CIDR")." = '*$q*'";
			$url_filter  = "&type=$type&value=$value";
		break;

		case "username":
			$selRadio[3] = "CHECKED";
			$q           = strtolower($value);
			$queryw      = " AND t1.username LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
			$queryl      = " limit $roffset,$pageSize";
			$stext       = "<b>"._("Search for user")."</b> = '*$q*'";
			$url_filter  = "&type=$type&value=$value";
		break;

		case "net":
			$selRadio[4] = "CHECKED";
			if (!preg_match("/\//",$value)) {
				$q = $value;
			}
			else
			{
				$tokens = explode("/", $value);
				$bytes = explode(".",$tokens[0]);

				if($tokens[1]=="24"){
					$q = $bytes[0].".".$bytes[1].".".$bytes[2].".";
				}
				else if ($tokens[1]=="16"){
					$q = $bytes[0].".".$bytes[1].".";
				}
				else if ($tokens[1]=="8"){
					$q = $bytes[0].".";
				}
				else if ((int)$tokens[1]>24){
					$q = $bytes[0].".".$bytes[1].".".$bytes[2].".".$bytes[3];
				}
			}

			$queryw = " AND (t4.meth_TARGET LIKE '%$q%' OR t5.hostIP LIKE '%$q%') $query_onlyuser order by $sortby $sortdir";
			$queryl = " limit $roffset,$pageSize";

			if (!preg_match("/\//",$value)) {
				$stext =  "<b>"._("Search for Host")."</b> = '*".html_entity_decode($q)."*'";
			}
			else{
				$stext =  "<b>"._("Search for Subnet/CIDR")."</b> = '*$q*'";
			}

			$url_filter="&type=$type&value=$value";

		break;

		default:
			$selRadio[1] = "CHECKED";
			$viewAll     = FALSE;
			$queryw      = "$query_onlyuser order by $sortby $sortdir";
			$queryl      = " limit $roffset,$pageSize";
			$stext       = "";
		break;
	}

	$reportCount = 0;

	if(!$filteredView)
	{
		$queryc = "SELECT count(t1.report_id)
                    FROM vuln_nessus_reports t1 LEFT JOIN vuln_jobs t2 on t1.report_id = t2.report_id
                    LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
                    WHERE t1.deleted = '0'";
		$reportCount = $dbconn->GetOne($queryc.$queryw);

      $previous = $roffset - $pageSize;
      if ($previous < 0) {
         $previous = 0;
      }

      $last = (intval($reportCount/$pageSize))*$pageSize;
      
      if ( $last < 0 )
      {
          $last = 0;
      }
      
      $next = $roffset + $pageSize;

      $pageEnd = $roffset + $pageSize;

      $value = html_entity_decode($value);
      
      $w_val = intval($widget_mode);

		//echo "<center><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\"><tr><td class=\"headerpr\" style=\"border:0;\">"._("Reports")."</td></tr></table></center>";
		// output the search form
echo "<table cellspacing='0' cellpadding='0' class='w100 transparent'>";
echo "<tr><td class='sec_title'>"._("Scan Reports Details")."</td></tr>";
echo "<tr><td style='padding-top:12px;' class='transparent'>";
      echo <<<EOT
<center>
<form name="hostSearch" action="index.php" method="GET">
<input type="hidden" name="widget_mode" value="$w_val">
<input type="text" length="25" name="rvalue" id="rvalue" value="$value">
EOT;
echo "
<input type=\"radio\" name=\"type\" value=\"scantime\" $selRadio[0]>"._("Date")."/"._("Time")."
<input type=\"radio\" name=\"type\" value=\"jobname\" $selRadio[1]>"._("Job Name")."
<input type=\"radio\" name=\"type\" value=\"net\" $selRadio[4]>"._("Host")."/"._("Net")."
";
     echo <<<EOT
<input type="hidden" name="sortby" value="$sortby">
<input type="hidden" name="allres" value="$allres">
<input type="hidden" name="op" value="search">&nbsp;&nbsp;&nbsp;
EOT;
echo "<input type=\"submit\" name=\"submit\" value=\""._("Find")."\" id=\"reports_find_button\" class=\"av_b_secondary small\">";
     echo <<<EOT
</form>
</center>
</p>
EOT;
   } else {
      // get the search result count
      $queryc = "SELECT count( report_id ) FROM vuln_nessus_reports WHERE t1.deleted = '0' ";
      $scount=$dbconn->GetOne($queryc.$queryw);
      echo "<p>$scount report";
      if($scount != 1) {
         echo "s";
      } else {
      }
      echo " "._("found matching search criteria")." | ";
      echo " <a href='index.php' alt='"._("View All Reports")."'>"._("View All Reports")."</a></p>";
   }

   echo "<p>";
   echo $stext;
   echo "</p>";
   echo "</td></tr></table>";
   // get the hosts to display
   //print_r($querys.$queryw.$queryl);
   $result=$dbconn->GetArray($querys.$queryw.$queryl);

   if($result === false) {
      $errMsg[] = _("Error getting results").": " . $dbconn->ErrorMsg();
      $error++;
      dispSQLError($errMsg,$error);
   } else {
      $tdata = array();
      foreach($result as $data) {

         $data['vSerious'] = 0;
         $data['vHigh']    = 0;
         $data['vMed']     = 0;
         $data['vLow']     = 0;
         $data['vInfo']    = 0;
         $vulns_in_report  = false;

         // query for reports for each IP
         
         $perms_where = Asset_host::get_perms_where('host.', TRUE);
         
         if (!empty($perms_where))
         {
             $query_risk = "SELECT count(lr.risk) as count, lr.risk, lr.hostIP, lr.ctx
                            FROM vuln_nessus_results lr, host, host_ip hi
                            WHERE host.id=hi.host_id AND inet6_ntop(hi.ip)=lr.hostIP $perms_where AND report_id in (?) AND falsepositive='N'
                            GROUP BY lr.risk, lr.hostIP, lr.ctx";
         }
         else
         {
             $query_risk = "SELECT count(lr.risk) as count, lr.risk, lr.hostIP, lr.ctx
                            FROM vuln_nessus_results lr
                            WHERE report_id in (?) AND falsepositive='N'
                            GROUP BY lr.risk, lr.hostIP, lr.ctx";
         }


         $result_risk = $dbconn->Execute($query_risk,array($data['report_id']));
         while(!$result_risk->EOF) {

            $vulns_in_report = TRUE;
                
            if($result_risk->fields["risk"]==7) {
                $data['vInfo'] += $result_risk->fields['count'];
            }
            else if($result_risk->fields["risk"]==6) {
                $data['vLow'] += $result_risk->fields['count'];
            }
            else if($result_risk->fields["risk"]==3) {
                $data['vMed'] += $result_risk->fields['count'];
            }
            else if($result_risk->fields["risk"]==2) {
                $data['vHigh'] += $result_risk->fields['count'];
            }
            else if($result_risk->fields["risk"]==1) {
                $data['vSerious'] += $result_risk->fields['count'];
            }

            $result_risk->MoveNext();
         }

        $data['clink'] = "respdfc.php?scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
        $data['plink'] = "respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
        $data['hlink'] = "reshtml.php?disp=html&amp;output=full&scantime=".$data['scantime']."&scantype=".$data['scantype'].$more;
        $data['rerun'] = "sched.php?disp=rerun&job_id=".$data['jobid'].$more;
        $data['xlink'] = "rescsv.php?scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
        $data['xbase'] = "restextsummary.php?scantime=".$data['scantime']."&scantype=".$data['scantype'].$more."&key=".$data['report_key'];
        $list = array();

		if($data["report_type"]=="I")
		{
		    $perms_where = Asset_host::get_perms_where('host.', TRUE);
		
            $dbconn->execute("CREATE TEMPORARY TABLE tmph (id binary(16) NOT NULL,ip varchar(64) NOT NULL,ctx binary(16) NOT NULL, PRIMARY KEY ( id, ip ));");
            $dbconn->execute("REPLACE INTO tmph SELECT id, inet6_ntop(ip), ctx FROM host, host_ip WHERE host.id=host_ip.host_id $perms_where;");
            
            $result_import = $dbconn->execute("SELECT DISTINCT hostIP, HEX(vuln_nessus_results.ctx) as ctx, hex(id) as host_id FROM vuln_nessus_results LEFT JOIN tmph ON tmph.ctx=vuln_nessus_results.ctx AND hostIP=tmph.ip WHERE report_id = " . $data['report_id']);
            
            
            while (!$result_import->EOF)
			{
                if( valid_hex32($result_import->fields["host_id"]) ) {
                    $list[] = $result_import->fields["host_id"] . "#" . $result_import->fields["hostIP"];
                }
                else {
                    $list[] = $result_import->fields["hostIP"];
                }

                $result_import->MoveNext();
            }
            
            $dbconn->execute("DROP TABLE tmph;");
        }
        else {
            $list = explode("\n", trim($data['meth_target']));
        }

        //var_dump($list);
        if(count($list)==1)
		{
            $list[0] = trim($list[0]);
            $asset   = resolve_asset($dbconn, $list[0], true);
            $data['target'] = "<span class='tip' title='".clean_id($list[0])."'>". $asset . "</span>";

        }
        elseif(count($list)==2)
		{
            $list[0] = trim($list[0]);
            $asset   = resolve_asset($dbconn, $list[0], true);
            $list[0] = "<span class='tip' title='".clean_id($list[0])."'>". $asset . "</span>";

            $list[1] = trim($list[1]);
            $asset   = resolve_asset($dbconn, $list[1], true);
            $list[1] = "<span class='tip' title='".clean_id($list[1])."'>". $asset . "</span>";

            $data['target'] = $list[0].', '.$list[1];
        }
        else
		{
            $list[0] = trim($list[0]);
            $asset   = resolve_asset($dbconn, $list[0], true);
            $list[0] = "<span class='tip' title='".clean_id($list[0])."'>". $asset . "</span>";


            $list[count($list)-1] = trim($list[count($list)-1]);
            $asset                = resolve_asset($dbconn, $list[count($list)-1], true);
            $list[count($list)-1] = "<span class='tip' title='".clean_id($list[count($list)-1])."'>". $asset . "</span>";

            $data['target'] = $list[0]." ... ".$list[count($list)-1];
        }

		if ($data["report_type"]=="I") $data["jobname"] = $data["report_name"];

        if( ($data['vSerious'] == 0 && $data['vHigh'] == 0 && $data['vMed'] == 0 && $data['vLow'] == 0 &&  $data['vInfo'] == 0) && $vulns_in_report )
		{
            $data['vSerious'] = "-";
            $data['vHigh']    = "-";
            $data['vMed']     = "-";
            $data['vLow']     = "-";
            $data['vInfo']    = "-";
        }

        $data['target'] = preg_replace("/[0-9a-f]{32}#/i","",$data['target']);
        $tdata[] = $data;
      }

      if($sortdir == "ASC") { $sortdir = "DESC"; } else { $sortdir = "ASC"; }
      $url = $_SERVER['SCRIPT_NAME'] . "?offset=0&sortby=%var%&sortdir=$sortdir".$url_filter;

      $fieldMapLinks = array();

         $fieldMapLinks = array(
            gettext("HTML Results") => array(
                     'url' => '%param%',
                   'param' => 'hlink',
                   'target' => 'main',
                    'icon' => 'images/html.png'),
             gettext("PDF Results") => array(
                     'url' => '%param%',
                   'param' => 'plink',
                  'target' => '_blank',
                    'icon' => 'images/pdf.png'),
           gettext("EXCEL Results") => array(
                     'url' => '%param%',
                   'param' => 'xlink',
                  'target' => '_blank',
                    'icon' => 'images/page_white_excel.png'));
      $fieldMap = array(
               "Date/Time" => array( 'var' => 'scantime', 'link' => $url ),
               "Job Name"  => array( 'var' => 'jobname', 'link' => $url ),
               "Targets" => array( 'var' => 'target', 'link' => $url ),
               "Profile" => array( 'var' => 'profile', 'link' => $url ),
               "Serious" => array( 'var' => 'vSerious', 'link' => $url ),
               "High" => array( 'var' => 'vHigh', 'link' => $url ),
               "Medium" => array( 'var' => 'vMed', 'link' => $url ),
               "Low" => array( 'var' => 'vLow', 'link' => $url ),
               "Info" => array( 'var' => 'vInfo', 'link' => $url ),
               "Links" => $fieldMapLinks);

      if(count($tdata)>0) {
        drawTable($fieldMap, $tdata, "Hosts", get_hosts($dbconn));
      }
      else {?>
        <table class="table_list">
            <tr><td class="nobborder" style="text-align:center;padding: 8px 0px 0px 0px;"><strong><?php echo _("No reports found"); ?></strong><br/><br/></td></tr>
        </table>
      <?php
        }
   }

   // draw the pager again, if viewing all hosts
   if($last!=0) {    
   ?>
        <div class="fright tmargin">
            <?php
            if ($next > $pageSize)
            {
            ?>
		        <a href="index.php?sreport=1&<?php echo "sortdir=$or_sortdir&roffset=$previous&sortby=$or_sortby$url_filter" ?>" class="pager">< <?php echo _("PREVIOUS")?> </a>
		    <?php
		    }
		    else
		    {
    		?>
		        <a class='link_paginate_disabled' href="" onclick='return false'>< <?php echo _("PREVIOUS")?> </a>
    		<?php
		    }
		    
            if ($next <= $last)
            {
		    ?>
                <a class='lmargin' href="index.php?sreport=1&<?php echo "sortdir=$or_sortdir&roffset=$next&sortby=$or_sortby$url_filter" ?>">  <?php echo _("NEXT") ?> ></a>
            <?php
            }
            else
            {
            ?>
                <a class='link_paginate_disabled lmargin' href="" onclick='return false'><?php echo _("NEXT")?> ></a>
            <?php    
            }
            ?>
        </div>
   <?php
   }
   else {
    echo "<p>&nbsp;</p>";
   }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("Vulnmeter"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
  <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css" />
  <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.autocomplete.css"/>
  <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
  <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
  <script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>
  <script type="text/javascript" src="/ossim/js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="/ossim/js/jquery.autocomplete.pack.js"></script>
  <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
  <script language="JavaScript" src="/ossim/js/jquery.flot.pie.js"></script>
  <script type="text/javascript" src="/ossim/js/vulnmeter.js"></script>
  <script type="text/javascript" src="/ossim/js/greybox.js"></script>
  <?php require ("../host_report_menu.php"); ?>

  <style type="text/css">

	img.downclick { cursor:pointer; }


	.tab_box {
    	padding:0px 0px 0px 0px;
    	height:230px !important;
	}

	.valigntop {
		vertical-align: top;
		padding: 0px !important;
	}
	.paddingTop {
		padding:7px 0px 0px 0px;
	}
    img.downclick { cursor:pointer; }
	.format_or{
		padding:0px 5px 0px 5px;
		text-align:center;
		border-bottom: none;
	}
	img.disabled {
		opacity: .50;
		filter:Alpha(Opacity=50);
		-moz-opacity: 0.5 !important;
		-khtml-opacity: 0.5 !important;
	}
    .rPadding {
        padding: 0px 0px 4px 0px;
        text-align: left;
    }
    .cvPadding {
        padding: 20px 0px 4px 0px;
        text-align: left;
    }
    .lr_border {
        border-left:1px solid #CCC;
        border-right:1px solid #CCC;
    }
    .section {
        text-decoration: none;
    }
    #cvrightdiv {
        float:right;
        width:82%;
    }
    #cvleftdiv {
        float:left;
        width:17%;
        padding: 8px 0px 0px 0px;
        text-align:left;
    }
    .main_container {
        background: #F9F9F9 !important;
        border-collapse:collapse;
        width:100%;
        border: 1px solid #C4C0BB;
    }
    
    .tmargin
    {
        margin: 5px 0px 0px 0px;
    }
  </style>
  <script>
    $(document).ready(function() {
        <?php
        if($rvalue !="")
        { ?>
            var h = document.body.scrollHeight || 1000000;window.scrollTo(0,document.body.scrollHeight);
            window.scrollTo(0,h);
        <?php
        }

        if ($widget_mode)
        {
        ?>
          $('#c_lmenu').hide();
        <?php
        }
        ?>
        $('.tip').tipTip({defaultPosition:"right",maxWidth:'400px'});
		$('#rtabs').tabs({
			selected: 0,
			collapsible: false
		});
		$('#atabs').tabs({
			selected: 0,
			collapsible: false
		});

        // Autocomplete assets
        var assets = [
            <?php echo $assets ?>
        ];
        $(".assets").autocomplete(assets, {
            minChars: 0,
            width: 300,
            max: 100,
            matchContains: true,
            autoFill: true,
            formatItem: function(row, i, max) {
                return row.txt;
            }
        }).result(function(event, item) {

            $('#assets').val(item.ip);

        });

        $('.downclick').bind("click",function(){
            var cls = $(this).attr('value');
            $('.'+cls).toggle();
            if ($(this).attr('src').match(/ltP_nesi/))
                $(this).attr('src','../pixmaps/theme/ltP_neso.gif')
            else
                $(this).attr('src','../pixmaps/theme/ltP_nesi.gif')
        });
		$('.section').click(function() {
			var id = $(this).find('img').attr('id');

			toggle_section(id);
		});
        $('.downclick').bind("click",function(){
            var cls = $(this).attr('value');
            $('.'+cls).toggle();
            if ($(this).attr('src').match(/ltP_nesi/))
                $(this).attr('src','../pixmaps/theme/ltP_neso.gif')
            else
                $(this).attr('src','../pixmaps/theme/ltP_nesi.gif')
        });
        GB_TYPE = 'w';
        $("a.greybox").click(function(){
            var dest = $(this).attr('href');
            var gwidth = '';
            if($(this).attr('gwidth')!="") {
                gwidth = $(this).attr('gwidth');
            }
            else {
                gwidth = 400;
            }
            GB_show($(this).attr('gtitle'), dest, $(this).attr('gheight'), gwidth);
            return false;
        });
    });

	function toggle_section(id){
		var section_id = id.replace('_arrow', '');
		var section    = '.'+section_id;
		if ($(section).is(':visible')){
			$('#'+id).attr('src','../pixmaps/arrow_green.gif');
		}
		else{
			$('#'+id).attr('src','../pixmaps/arrow_green_down.gif');
		}
		$(section).toggle();
	}

    function confirmDelete(key){
        var ans = confirm("<?php echo  Util::js_entities(_("Are you sure you want to delete this report?"))?>");
        if (ans) document.location.href='index.php?delete='+key;
    }

    function switch_user(select) {
        if(select=='entity' && $('#transferred_entity').val()!=''){
            $('#user').val('');
        }
        else if (select=='user' && $('#transferred_user').val()!=''){
            $('#entity').val('');
        }
    }

    var freport="";
    var sreport="";

    function GB_onhide(url)
    {
        if (freport==""||sreport=="")
        {
            document.location.href = GB_makeurl('<?php echo AV_MAIN_PATH ?>/vulnmeter/index.php?m_opt=environment&sm_opt=vulnerabilities');
		}
		else
		{
            top.frames['main'].window.location.href = GB_makeurl('<?php echo AV_MAIN_PATH ?>/vulnmeter/compare_reports.php?freport='+freport+'&sreport='+sreport);
        }
    }

  </script>
<head>
<body>
<?php

//Local menu
include_once '../local_menu.php';


// * For current vulnerabilities
function list_results ( $type, $value, $ctx_filter, $sortby, $sortdir ) {

   global $allres, $offset, $pageSize, $dbconn;
   global $user, $arruser;
   
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $filteredView = FALSE;

   $selRadio = array( "","","","");

   $query_onlyuser="";
   $url_filter="";

   // Deprecated filter
   //if(!empty($arruser)) {$query_onlyuser = " AND t1.username in ($user)";}


    $sortby = "t1.results_sent DESC, t1.hostIP DESC";

    $sortdir = "";

    $queryw="";
    $queryl="";

    $querys = "SELECT distinct t1.hostIP, HEX(t1.ctx) as ctx, t1.scantime, t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t1.sid, t3.name as profile
    FROM vuln_nessus_latest_reports AS t1 LEFT JOIN vuln_nessus_settings AS t3 ON t1.sid = t3.id, vuln_nessus_latest_results AS t5
    WHERE
    t1.hostIP      = t5.hostIP
    AND t1.ctx     = t5.ctx
    AND t1.deleted = '0' ";

     // set up the SQL query based on the search form input (if any)
    if($type=="scantime" && $value!="") {
        $selRadio[0] = "CHECKED";
        $q = $value;
        $queryw = " AND t1.scantime LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
        $queryl = " limit $offset,$pageSize";
        $stext =  "<b>"._("Search for Date/Time")."</b> = '*$q*'";
        $url_filter="&type=$type&value=$value";
    }
    else if($type=="service" && $value!="") {
        $selRadio[5] = "CHECKED";
        $q = $value;
        $queryw = " AND t5.service LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
        $queryl = " limit $offset,$pageSize";
        $stext =  "<b>"._("Search for Service")."</b> = '*".html_entity_decode($q)."*'";
        $url_filter="&type=$type&value=$value";
    }
    else if($type=="freetext" && $value!="") {
        $selRadio[6] = "CHECKED";
        $q = $value;
        $queryw = " AND t5.msg LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
        $queryl = " limit $offset,$pageSize";
        $stext =  "<b>"._("Search for Free Text")."</b> = '*".html_entity_decode($q)."*'";
        $url_filter="&type=$type&value=$value";
    }
   else if($type=="hostip" && $value!="") {
      $selRadio[1] = "CHECKED";
      $q = strtolower($value);
      $queryw = " t1.hostIP LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize";
      $stext =  "<b>"._("Search for Host-IP")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
    }
   else if($type=="fk_name" && $value!="") {
      $selRadio[2] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.fk_name LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize";
      $stext = _("Search for Subnet/CIDR")." = '*$q*'";
      $url_filter="&type=$type&value=$value";
    }
   else if($type=="username" && $value!="") {
      $selRadio[3] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.username LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize";
      $stext = "<b>"._("Search for user")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
    }
    else if($type=="hn" && $value!="") {
        if(!empty($ctx_filter)) {
            $queryw = " AND t1.ctx=UNHEX('$ctx_filter')";
        }

        $selRadio[4] = "CHECKED";
        if (preg_match("/\//",$value)) {
            $ip_range = array();
            $ip_range = Cidr::expand_CIDR($value, "SHORT");
            $queryw .= " AND (inet_aton(t1.hostIP) >= '".$ip_range[0]."' AND inet_aton(t1.hostIP) <='".$ip_range[1]."') $query_onlyuser order by $sortby $sortdir";
        }
        elseif (preg_match("/\,/",$value)) {
            $q = implode("','",explode(",",$value));
            $queryw .= " AND t1.hostIP in ('$q') $query_onlyuser order by $sortby $sortdir";
            $q = "Others";
        }
        else {
            $q = $value;
            $queryw .= " AND t1.hostIP LIKE '$q' $query_onlyuser order by $sortby $sortdir";
        }

        $queryl = " limit $offset,$pageSize";
        if (!preg_match("/\//",$value)) {
            $stext =  "<b>"._("Search for Host")."</b> = '".html_entity_decode($q)."'";
        }
        else {
            $stext =  "<b>"._("Search for Subnet/CIDR")."</b> = '$value'";
        }
        $url_filter="&type=$type&value=$value";
    }
    else {
      $selRadio[4] = "CHECKED";
      $viewAll = FALSE;
      $queryw = "$query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize";
      $stext = "";
    }

   // set up the pager and search fields if viewing all hosts
   $reportCount = 0;
   if(!$filteredView) {
      $dbconn->Execute(str_replace("SELECT distinct", "SELECT SQL_CALC_FOUND_ROWS distinct", $querys).$queryw);
      $reportCount = $dbconn->GetOne("SELECT FOUND_ROWS() as total");

      $previous = $offset - $pageSize;
      if ($previous < 0) {
         $previous = 0;
      }

      $last = (intval($reportCount/$pageSize))*$pageSize;
      if ( $last < 0 ) { $last = 0; }
      $next = $offset + $pageSize;

      $pageEnd = $offset + $pageSize;
	$value=html_entity_decode($value);
//echo "<center><table cellspacing='0' cellpadding='0' border='0' width='100%'><tr><td class='headerpr' style='border:0;'>"._("Current Vulnerablities")."</td></tr></table>";
      // output the search form
echo "<table class='w100 transparent'>";
echo "<tr><td class='sec_title'>"._("Asset Vulnerability Details")."</td></tr>";
echo "<tr><td style='padding:12px 0px 0px 0px;' class='transparent'>";

?>
    <div id='cvleftdiv'>
        <a id="new_scan_button" class="button" href="<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/sched.php?smethod=schedule&hosts_alive=1&scan_locally=1', 'environment', 'vulnerabilities','scan_jobs'); ?>" style="text-decoration:none;">
        <?php echo _("New Scan Job"); ?>
        </a>
    </div>
    <div id='cvrightdiv'>

<?php
      echo '<form name="hostSearch" id="hostSearch" action="index.php" method="GET">
<input type="text" length="25" name="value" id="assets" class="assets" style="margin:0px !important;" value="'.Util::htmlentities($value).'">';
// cvfiltertype -> current vulnerabilities filter type
echo "
<input type=\"radio\" name=\"type\" value=\"service\" $selRadio[5]>"._("Service")."
<input type=\"radio\" name=\"type\" value=\"freetext\" $selRadio[6]>"._("Free text")."
<input type=\"radio\" name=\"type\" value=\"hn\" $selRadio[4]>"._("Host/Net")."
";
echo "<input type=\"submit\" name=\"submit\" value=\""._("Find")."\" id=\"current_vulns_find_button\" class=\"av_b_secondary small\" style=\"margin-left:15px;\">";
     echo <<<EOT
</form>
</p>
EOT;
   } else {
      // get the search result count
      $queryc = "SELECT count( report_id ) FROM vuln_nessus_latest_reports WHERE t1.deleted = '0' ";
      $scount=$dbconn->GetOne($queryc.$queryw);
      echo "<p>$scount report";
      if($scount != 1) {
         echo "s";
      } else {
      }
      echo " "._("found matching search criteria")." | ";
      echo " <a href='index.php' alt='"._("View All Reports")."'>"._("View All Reports")."</a></p>";
   }

   echo "<p>";
   echo $stext;
   echo "</p>";
   echo "</div></td></tr></table>";

   $result = array();

   // get the hosts to display
   $result=$dbconn->GetArray($querys.$queryw.$queryl);

   // main query
   //echo $querys.$queryw.$queryl;
   $delete_ids = array();

    if(count($result)>0) {
        foreach ($result as $rpt) {
            $delete_ids[] = $dreport_id = $rpt["report_id"];
        }
    }

    $_SESSION["_dreport_ids"]=implode(",", $delete_ids);

   //echo "$querys$queryw$queryl";
   if($result === false) {
      $errMsg[] = _("Error getting results").": " . $dbconn->ErrorMsg();
      $error++;
      dispSQLError($errMsg,$error);
   } else {
       $data['vInfo'] = 0;
       $data['vLow'] = 0;
       $data['vMed'] = 0;
       $data['vHigh'] = 0;
       $data['vSerious'] = 0;
       
       $perms_where  = Asset_host::get_perms_where('host.', TRUE);

       if (!empty($perms_where))
       {
           $queryt = "SELECT count(lr.result_id) AS total, lr.risk, lr.hostIP, HEX(lr.ctx) AS ctx
                        FROM vuln_nessus_latest_results lr, host, host_ip hi
                        WHERE host.id=hi.host_id AND inet6_ntop(hi.ip)=lr.hostIP $perms_where AND falsepositive='N'
                        GROUP BY risk, hostIP, ctx";
       }
       else 
       {
           $queryt = "SELECT count(lr.result_id) AS total, risk, lr.hostIP, HEX(lr.ctx) AS ctx
                        FROM vuln_nessus_latest_results lr
                        WHERE falsepositive='N'
                        GROUP BY risk, hostIP, ctx";
       }
       
       //echo "$queryt<br>";
       
       $resultt = $dbconn->Execute($queryt);
         while(!$resultt->EOF) {  
             
            $riskcount = $resultt->fields['total'];
            $risk      = $resultt->fields['risk'];
            
            if($risk==7)
                $data['vInfo']+= $riskcount;
            else if($risk==6)
                $data['vLow']+=$riskcount;
            else if($risk==3)
                $data['vMed']+=$riskcount;
            else if($risk==2)
                $data['vHigh']+=$riskcount;
            else if($risk==1)
                $data['vSerious']+=$riskcount;

            $resultt->MoveNext();
      }

      if($data['vInfo']==0 && $data['vLow']==0 && $data['vMed']==0 && $data['vHigh']==0 && $data['vSerious']==0 )
            $tdata [] = array("report_id" =>"All","host_name" => "", "scantime" => "", "username" => "",
                            "scantype" => "", "report_key" => "", "report_type" => "", "sid" => "", "profile" => "",
                        "hlink" =>"", "plink" => "", "xlink" =>"",
                        "vSerious" => $data['vSerious'], "vHigh" => $data['vHigh'], "vMed" => $data['vMed'],
                        "vLow" => $data['vLow'], "vInfo" => $data['vInfo']);

      else

            $tdata [] = array("report_id" =>"All","host_name" => "", "scantime" => "", "username" => "",
                            "scantype" => "", "report_key" => "", "report_type" => "", "sid" => "", "profile" => "",
                        "hlink" => "lr_reshtml.php?ipl=all&disp=html&output=full&scantype=M",
                        "plink" => "lr_respdf.php?ipl=all&scantype=M",
                        "xlink" => "lr_rescsv.php?ipl=all&scantype=M",
                        "dlink" =>"",
                        "vSerious" => $data['vSerious'], "vHigh" => $data['vHigh'], "vMed" => $data['vMed'],
                        "vLow" => $data['vLow'], "vInfo" => $data['vInfo']);

      foreach($result as $data) {
        if(!Session::hostAllowed_by_ip_ctx($dbconn, $data["hostIP"], $data["ctx"])) continue;

        $host_id = key(Asset_host::get_id_by_ips($dbconn, $data["hostIP"], $data["ctx"]));
            
        if(valid_hex32($host_id)) {
            $data['host_name'] = Asset_host::get_name_by_id($dbconn, $host_id);
        }

        $data['vSerious'] = 0;
        $data['vHigh'] = 0;
        $data['vMed'] = 0;
        $data['vLow'] = 0;
        $data['vInfo'] = 0;

         // query for reports for each IP
         $query_risk = "SELECT distinct risk, port, protocol, app, scriptid, msg, hostIP FROM vuln_nessus_latest_results WHERE hostIP = '".$data['hostIP'];
         $query_risk.= "' AND username = '".$data['username']."' AND sid =".$data['sid']." AND ctx = UNHEX('".$data['ctx']."') AND falsepositive='N'";

         $result_risk = $dbconn->Execute($query_risk);
         while(!$result_risk->EOF) {
            if($result_risk->fields["risk"]==7)
                $data['vInfo']++;
            else if($result_risk->fields["risk"]==6)
                $data['vLow']++;
            else if($result_risk->fields["risk"]==3)
                $data['vMed']++;
            else if($result_risk->fields["risk"]==2)
                $data['vHigh']++;
            else if($result_risk->fields["risk"]==1)
                $data['vSerious']++;
            $result_risk->MoveNext();
         }
         
         $data['plink'] = "lr_respdf.php?treport=latest&ipl=" . urlencode($data['hostIP']) . "&ctx=".$data['ctx'] . "&scantype=" . $data['scantype'];
         $data['hlink'] = "lr_reshtml.php?treport=latest&ipl=" . urlencode($data['hostIP']) . "&ctx=".$data['ctx'] . "&scantype=" . $data['scantype'];
         $data['xlink'] = "lr_rescsv.php?treport=latest&ipl=" . urlencode($data['hostIP']) . "&ctx=".$data['ctx'] . "&scantype=" . $data['scantype'];
         
         
         if(Session::am_i_admin()) {
            $data['dlink'] = "index.php?delete=".$data['report_key']."&scantime=".$data['scantime'];
         }

        $list = explode("\n", trim($data['meth_target']));

        if(count($list)==1) {
            $list[0]=trim($list[0]);

			$data['target'] = resolve_asset($dbconn, $list[0]);

        }
        elseif(count($list)==2) {

            $list[0] = trim($list[0]);
            $list[0] = resolve_asset($dbconn, $list[0]);

            $list[1] = trim($list[1]);
            $list[1] = resolve_asset($dbconn, $list[1]);

            $data['target'] = $list[0].' '.$list[1];
        }
        else {
            $list[0] = trim($list[0]);
            $list[0] = resolve_asset($dbconn, $list[0]);

            $list[count($list)-1] = trim($list[count($list)-1]);
            $list[count($list)-1] = resolve_asset($dbconn, $list[count($list)-1]);

            $data['target'] = $list[0]." ... ".$list[count($list)-1];
        }
        $tdata[] = $data;
    }
      if($sortdir == "ASC") { $sortdir = "DESC"; } else { $sortdir = "ASC"; }
      $url = $_SERVER['SCRIPT_NAME'] . "?offset=$offset&sortby=%var%&sortdir=$sortdir".$url_filter;

      $fieldMapLinks = array();

         $fieldMapLinks = array(
            gettext("HTML Results") => array(
                     'url' => '%param%',
                   'param' => 'hlink',
                   'target' => 'main',
                    'icon' => 'images/html.png'),
             gettext("PDF Results") => array(
                     'url' => '%param%',
                   'param' => 'plink',
                  'target' => '_blank',
                    'icon' => 'images/pdf.png'),
           gettext("EXCEL Results") => array(
                     'url' => '%param%',
                   'param' => 'xlink',
                  'target' => '_blank',
                    'icon' => 'images/page_white_excel.png')
);
         if(Session::am_i_admin()) {
            $fieldMapLinks ["DELETE Results"] = array(
                     'url' => '%param%',
                   'param' => 'dlink',
                  'target' => 'main',
                    'icon' => 'images/delete.gif');
         }

      $fieldMap = array(
               "Host - IP"  => array( 'var' => 'hostip'),
               "Date/Time" => array( 'var' => 'scantime'),
               "Profile" => array( 'var' => 'profile'),
               "Serious" => array( 'var' => 'vSerious'),
               "High" => array( 'var' => 'vHigh'),
               "Medium" => array( 'var' => 'vMed'),
               "Low" => array( 'var' => 'vLow'),
               "Info" => array( 'var' => 'vInfo'),
               "Links" => $fieldMapLinks);

        // echo "<pre>";
        // var_dump($tdata);
        // echo "</pre>";
        if(count($tdata)>1){
            drawTableLatest($fieldMap, $tdata, "Hosts");
        }
      elseif (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
      	echo "<br><span class='gray'>"._("No results found: ")."</span><a href='sched.php?smethod=schedule&hosts_alive=1&scan_locally=1'>"._("Click here to run a Vulnerability Scan now")."</a><br><br>";
      }

   }
   // draw the pager again, if viewing all hosts
   if(!$filteredView && $reportCount>10) {
   ?>
    <div class="fright tmargin">
        <?php
        
        if ($next > $pageSize)
        {
        ?>
	        <a href="index.php?<?php echo "offset=$previous$url_filter" ?>" class="pager">< <?php echo _("PREVIOUS")?> </a>
	    <?php
	    }
	    else
	    {
		?>
	        <a class='link_paginate_disabled' href="" onclick='return false'>< <?php echo _("PREVIOUS")?> </a>
		<?php
	    }
	    
        if ($next <= $last)
        {
	    ?>
            <a class='lmargin' href="index.php?<?php echo "offset=$next$url_filter" ?>">  <?php echo _("NEXT") ?> ></a>
        <?php
        }
        else
        {
        ?>
            <a class='link_paginate_disabled lmargin' href="" onclick='return false'><?php echo _("NEXT")?> ></a>
        <?php    
        }
        ?>
    </div>
<?php

   }
   else {
    echo "<p>&nbsp;</p>";
   }
}
?>

	<table style="margin: 0px auto;width:100%" cellspacing="0" cellpadding="0" class="transparent">
		<tr>
			<td class="valigntop" width="48%">
				<div id="rtabs" style='width:100%;margin:0 auto;padding:0;height:100%;'>
					<ul>
					   <li><a href="#tabs-1" class="ptab" id="by_severity_tab"><?php echo _("By Severity") ?></a></li>
					   <li><a href="#tabs-2" class="ptab" id="by_services_tab"><?php echo _("By Services - Top 10") ?></a></li>
					</ul>
					<div id="tabs-1" class="tab_box statistics_background"><?php stats_severity_services($type, $value, "severity");?></div>
				    <div id="tabs-2" class="tab_box statistics_background"><?php stats_severity_services($type, $value, "service");?></div>
				</div>
			</td>
			<td style="width:45px;">&nbsp;</td>
			<td width="48%">
				<div id="atabs" style='width:100%;margin:0 auto;padding:0;height:100%;'>
					<ul>
					<li><a href="#tabs-1" class="ptab" id="top_10_hosts_tab"><?php echo _("Top 10 Hosts") ?></a></li>
					<li><a href="#tabs-2" class="ptab" id="top_10_networks_tab"><?php echo _("Top 10 Networks") ?></a></li>
					</ul>
					<div id="tabs-1" class="tab_box statistics_background"><?php stats_networks_hosts($type, $value, "hosts");?></div>
					<div id="tabs-2" class="tab_box statistics_background"><?php stats_networks_hosts($type, $value, "networks");?></div>
				</div>
		</tr>
    <?php
    if (!$widget_mode)
    {
    ?>
		<tr>
      <td colspan="3" class="cvPadding">
          <a class="section">
              <img id="cv_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green<?php echo ($rvalue == "" && $sortby == "" && $sreport == 0) ? "_down": ""?>.gif">
              <strong><?php echo _("CURRENT VULNERABILITIES"); ?></strong>
          </a>
      </td>
		</tr>
		<tr class='cv' <?php echo ($rvalue == "" && $sortby == "" && $sreport == 0) ? "": "style='display:none'"?>>
			<td colspan="3" style="padding:0px 0px 0px 0px;">
			<?php
			    list_results( $type, $value, $ctx_filter, $sortby, $sortdir );  // $value variable is used with current vulnerabilities
			?>
			</td>
		</tr>
    <?php
    }
    else
    {
    ?>
      <tr>
        <td colspan="3" class="cvPadding"></td>
      </tr>
    <?php
    }
    ?>
		<tr>
        <td colspan="3" class="rPadding">
            <a class="section">
                <img id="reports_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green<?php echo ($rvalue != "" || $sortby != "" || $widget_mode || $sreport == 1) ? "_down": ""?>.gif">
                <strong><?php echo _("REPORTS"); ?></strong>
            </a>
        </td>
		</tr>
		<tr class="reports" <?php echo ($rvalue != "" || $sortby != "" || $widget_mode || $sreport == 1) ? "": "style='display:none'"?>>
			<td colspan="3" style="padding:10px 0px 0px 0px;">
            <?php
				list_reports( $type, $rvalue, $sortby, $sortdir, $widget_mode ); // $rvalue variable is used with reports
            ?>
        </td>
		</tr>
		</table>
		<br/>
<?php
function clean_id($string) {
    return preg_replace("/^[a-f\d]{32}#/i", "", $string);
}
require_once 'footer.php';
?>