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
require_once 'config.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("Vulnmeter"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/vulnmeter.js"></script>
	<script type="text/javascript" src="../js/jquery.sparkline.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.json-2.2.js"></script>
    <style type="text/css">
        #legend
        {
            width: 100px !important;
            margin: 0px !important;
        }
        table.gray_border {
            border: 1px solid #C4C0BB;
        }
        table.gray_border2 {
            border-left: 1px solid #C4C0BB;
            border-right: 1px solid #C4C0BB;
            border-bottom: 1px solid #C4C0BB;
            border-top: 1px solid white;
        }
        img.downclick { cursor:pointer; }
        #tiptip_content {
            font: normal 11px Helvetica, Arial, sans-serif !important;
        }
        img.disabled {
            opacity: .50;
            filter:Alpha(Opacity=50);
            -moz-opacity: 0.5 !important;
            -khtml-opacity: 0.5 !important;
        }
        .t_width {
            margin:0px;
            width:100%;
        }
        .t_border {
            border-collapse: collapse;
        }
        .c_r_lmenu {
            right: 0px;
        }
        .lmargin {
            margin: 0px 0px 0px 30px;
        }
        
        tr.tasks:hover 
        { 
            background: #DEEBDB !important; cursor: pointer !important;   
        }
		.bdisabled 
		{
			filter:alpha(opacity=50);
			-moz-opacity:0.5;
			-khtml-opacity: 0.5;
			opacity: 0.5;
		}
		.ip_detail
		{
		    height:22px !important;
		    padding:0px !important;
        }
        .job_detail
        {
            width:200px;
            text-align: left;
        }
        .job_status
        {
            padding: 0px 0px 0px 52px;
        }
        .job_messages
        {
            padding: 0px 0px 0px 52px;
            font-size: 10px;
            margin-top:5px;
        } 
    </style>
	<?php require ("../host_report_menu.php") ?>
	
	<script type='text/javascript'>
		var refresh = true;

		function postload() {
			<?php
			if(Vulnerabilities::scanner_type() == "omp") 
			{ 
				?>
				refresh_state();
				<?php
			}
			?>
			
			$('.tip').tipTip({defaultPosition:"right",maxWidth:'400px'});
			
			$(".manageJob").click(function(event){
				if (window.event && jQuery.browser.msie){ window.event.cancelBubble=true; }
				else { event.stopPropagation(); }
				
				var tmp     = $(this).attr("id").split('#');
				var command = tmp[0];
				var id      = tmp[1];
				
				$('#changing_task_status_'+id).toggle();

				$.ajax({
					type: "GET",
					url: "manage_jobs.php",
					data: { disp: command, job_id: id },
					success: function(msg) {
						if(command=='pause_task') {
							alert("<?php echo Util::js_entities(_("Pausing job, please wait a few seconds."))?>");
							document.location.reload();
						}
						else if(command=='play_task') {
							alert("<?php echo Util::js_entities(_("Starting job, please wait a few seconds."))?>");
							document.location.reload();
						}
						else if(command=='stop_task') {
							alert("<?php echo Util::js_entities(_("Stopping job, please wait a few seconds."))?>");
							setTimeout('document.location.href="<?php echo Menu::get_menu_url('manage_jobs.php', 'environment', 'vulnerabilities', 'scan_jobs') ?>"',25000);
						}
						else if(command=='resume_task') {
							alert("<?php echo Util::js_entities(_("Resuming job, please wait a few seconds."))?>");
							document.location.reload();
						}
					}
				});
			});
			setInterval('refresh_page()',120000);
		}

		function refresh_page() {
			if(refresh) {
				location.reload();
			}
		}
		
		// 
		function cancelScan(id) {
			$('#working').toggle();
			$.ajax({
				type: "GET",
				url: "manage_jobs.php",
				data: { disp: "kill", sid: id },
				success: function(msg) {
					alert("<?php echo Util::js_entities(_("Cancelling job, please wait a few seconds. Server will stop current scan as soon as possible."))?>");
					document.location.reload();
				}
			});
		}
    
		function deleteTask(id) {
			if (confirmDelete()) {
				$.ajax({
					type: "GET",
					url: "manage_jobs.php",
					data: { disp: 'delete_task', job_id: id },
					success: function(msg) {
        				$.ajax({
        					type: "GET",
        					url: "sched.php",
        					data: { disp: 'delete_scan', job_id: id },
        					success: function(msg) {
            					document.location.reload();
        					}
        				});
					}
				});
			}
		}
		
		
		var date5m = new Date();
		date5m.setTime(date5m.getTime() + (5 * 60 * 1000));
		
		var max_points = 30;
		var last_state = 0;
		
		function refresh_state() {
			var tasks = [];
			var param = "";
			var state = (last_state == 0) ? "?bypassexpirationupdate=1" : "";
			var show_sparkline = false;
			$('.cstatus').each(function() {
				tasks.push(this.id);
			});
			if( tasks.length>0 ) {
				param = "&tasks=" + tasks.join("%23");

				$.ajax({
					type: "GET",
					url: "get_state.php"+state+param,
					success: function(msg) {
						var jobs =msg.split("-");
						
						for (var i=0;i<jobs.length;i++) {
							// 78|274|1bac027cf67efcc4d10125724221fc48;13;27;27;1;145;98#f17702c16d07775a172f69f0ab895418;0;0;0;0;6;0#e6fa3bcdd869cbef3219d47f6c886add;0;0;1;0;54;98
							var data = jobs[i].split("|"); // data[0] = job id, data[1] = vulns, data[2] = vulns data

							if(data[1] == "0") {
								$('#' + data[0] + '-seri').html(0);
								$('#' + data[0] + '-high').html(0);
								$('#' + data[0] + '-medi').html(0);
								$('#' + data[0] + '-low').html(0);
								$('#' + data[0] + '-info').html(0);
							}
							else {
								var jobs_ips = data[2].split("#");
								
								var seri_vulns = 0;
								var high_vulns = 0;
								var medi_vulns = 0;
								var low_vulns  = 0;
								var info_vulns = 0;

								for (var j=0;j<jobs_ips.length;j++) {
									var vulns_data = jobs_ips[j].split(";");
									
									if( $('#' + data[0] + '-' + vulns_data[0] + '-seri').length == 0 ) {
										document.location.href = 'manage_jobs.php';
									}
									
									seri_vulns = seri_vulns + parseInt(vulns_data[1]);
									high_vulns = high_vulns + parseInt(vulns_data[2]);
									medi_vulns = medi_vulns + parseInt(vulns_data[3]);
									low_vulns  = low_vulns  + parseInt(vulns_data[4]);
									info_vulns = info_vulns + parseInt(vulns_data[5]);
									
									$('#' + data[0] + '-' + vulns_data[0] + '-seri').html(vulns_data[1]);
									$('#' + data[0] + '-' + vulns_data[0] + '-high').html(vulns_data[2]);
									$('#' + data[0] + '-' + vulns_data[0] + '-medi').html(vulns_data[3]);
									$('#' + data[0] + '-' + vulns_data[0] + '-low').html(vulns_data[4]);
									$('#' + data[0] + '-' + vulns_data[0] + '-info').html(vulns_data[5]);
									$('#' + data[0] + '-' + vulns_data[0] + '-per').html(get_task_progress(vulns_data[6]));
								}

								if ( typeof(seri_vulns) != "number" ) {
									seri_vulns = "-";
									high_vulns = "-";
									medi_vulns = "-";
									low_vulns  = "-";
									info_vulns = "-";
								}
								$('#' + data[0] + '-seri').html(seri_vulns);
								$('#' + data[0] + '-high').html(high_vulns);
								$('#' + data[0] + '-medi').html(medi_vulns);
								$('#' + data[0] + '-low').html(low_vulns);
								$('#' + data[0] + '-info').html(info_vulns);
								
								if (typeof(data[0]) != 'undefined' && data[0] != '') {
									show_sparkline   = true;
									var nessuspoints = [];
									if ($.cookie('nessuspoints_'+data[0])) nessuspoints = $.evalJSON($.cookie('nessuspoints_'+data[0]));
									
									nessuspoints.push(data[1]);
									if (nessuspoints.length > max_points)
										nessuspoints.splice(0,1);
									$('#nessus_threads_'+data[0]).sparkline( nessuspoints, { width:nessuspoints.length*4, chartRangeMin: '0' } );
									
									$.cookie('nessuspoints_'+data[0], $.toJSON(nessuspoints), { expires: date5m });
								}
							}
						}
						if (show_sparkline) { // show sparkline when data is available
							$.sparkline_display_visible();
						}
						// 
						setTimeout (refresh_state,4000);
					}
				});
			}
		}

		$(document).ready(function(){
			GB_TYPE = 'w';			
			$("a.greybox").click(function(){
				var title  = $(this).attr('title');
				var width  = 400;
				var height = 190;
				dest       = $(this).attr('href');
							
				if(typeof(title) == "undefined") {
					title = '<?php echo _("Make this scan job visible for:")?>';
				}
				
				if(title == '<?php echo _("Import nbe file") ?>') {
					var width  = '80%';
					var height = 320;
				}
				
				refresh = false;

				GB_show(title, dest, height, width);
				return false;
			});

		});

				
		function get_task_progress (percentage) {
			var host_progress = "<div style='float:left;width:55%;text-align:right;padding:3px 0px 0px 0px'>";

			if (percentage <=25) {
				host_progress += "<img src='./images/light_yellow_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_gray_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_gray_lit.png' border='0'/>";
				host_progress += "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
			}
			else if (percentage <=50) {
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_yellow_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_gray_lit.png' border='0'/>";
				host_progress += "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
			}
			else if (percentage <=75) {
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_yellow_lit.png' border='0'/>";
				host_progress += "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
			}
			else if (percentage <=99) {
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img style='padding-right:7px;' src='./images/light_yellow_lit.png' border='0'/>";}
			else {
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
				host_progress += "<img style='padding-right:7px;' src='./images/light_green_lit.png' border='0'/>";
		   }
				host_progress += "</div><div style='float:right;width:43%;text-align:left'>" + percentage + "%</div>";

				return host_progress;
		}
		
		var rname;
		
		function GB_onclose(url) {
		
			n = new Array();
		
			if(typeof(url) != "undefined") {
				n=url.match(/import_nbe/g);
			}

            if(n==null) {
			    document.location.href = '<?php echo AV_MAIN_PATH ?>/vulnmeter/manage_jobs.php';
			}
			else if( n[0] == "import_nbe" && typeof(rname) != "undefined" && rname !="undefined" ) {
				document.location.href = '<?php echo AV_MAIN_PATH ?>/vulnmeter/index.php?rvalue='+rname+'&type=jobname&m_opt=environment&sm_opt=vulnerabilities';
			}

		}
	</script>
</head>

<body>
<?php

//Local menu		      
include_once '../local_menu.php';

$pageTitle = _("Manage Jobs");

require_once 'functions.inc';

$myhostname="";

$getParams = array( 'disp', 'schedid', 'sortby', 'sortdir', 'viewall', 'setstatus', 'enabled', 'job_id');

$hosts = array();
//$hosts = host_ip_name($dbconn);

switch ($_SERVER['REQUEST_METHOD']) {
case "GET" :
    foreach($getParams as $gp) 
    {
		if (isset($_GET[$gp])) { 
			$$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES);
		} else { 
			$$gp="";
		}
    }
	
    $range_start = "";
    $range_end   = "";
    
	break;
}

$version = $conf->get_conf("ossim_server_version");

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

$query  = "select count(*) as total from vuln_nessus_plugins";
$result = $dbconn->execute($query);
$pluginscount = $result->fields['total'];

if ($pluginscount==0) {
    //include_once('header2.php');
    die ("<h2>"._("Please run updateplugins.pl script first before using web interface").".</h2>");
}


function delete_sched( $schedid ) {
    global $viewall, $sortby, $sortdir, $uroles, $username, $dbconn;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $sql_require = "";
    if ( ! $uroles['admin'] ) { $sql_require = "AND username='$username'"; }

    $query = "SELECT id, name FROM vuln_job_schedule WHERE id = '$schedid' $sql_require";
    //echo "query=$query<br>";
    $result=$dbconn->Execute($query);
    list( $jid, $nname ) = $result->fields;

    if ( $jid > 0 ) {
       $query = "DELETE FROM vuln_job_schedule WHERE id = '$schedid' $sql_require";
       $result=$dbconn->Execute($query);

        $infolog = array($nname);
        Log_action::log(68, $infolog);
        
    } else {
       //echo "Not Authorized to Delete Reoccuring Schedule <i>\"$nname\"</i>";
 //logAccess( "UNAUTHORIZED ATTEMPT TO DELETED Reoccuring Schedule $nname" );
    }
    main_page ( $viewall, $sortby, $sortdir );
}

function set_status ( $schedid, $enabled ) {
    global $viewall, $sortby, $sortdir, $uroles, $username, $dbconn;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $sql_require = "";
    if ( ! $uroles['admin'] ) { $sql_require = "AND username='$username'"; }

    $query = "SELECT id, name FROM vuln_job_schedule WHERE id = '$schedid' $sql_require";
    //echo "query=$query<br>";
    $result=$dbconn->Execute($query);
    list( $jid, $nname ) = $result->fields;

    if ( $jid > 0 ) {
       $query = "UPDATE vuln_job_schedule SET enabled ='$enabled' WHERE id = '$schedid' $sql_require";
       $result=$dbconn->Execute($query);

    } else {
       echo _("Not Authorized to CHANGLE STATUS for Reoccuring Schedule")." <i>\"$nname\"</i>";
    }
    main_page ( $viewall, $sortby, $sortdir );
}



function main_page ( $viewall, $sortby, $sortdir ) 
{    		
	global $uroles, $username, $dbconn, $hosts;
    global $arruser, $user;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    $tz = Util::get_timezone();

    if ($sortby == "" ) { $sortby = "id"; }
    if ($sortdir == "" ) { $sortdir = "DESC"; }

    $sql_order="order by $sortby $sortdir";


	if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) 
	{
		?>
		<div style="width:50%; position: relative; height: 5px; float:left">
			
			<div style="width:100%; position: absolute; top: -41px;left:0px;">
    			<div style="float:left; height:28px; margin:5px 5px 0px 0px;">
    				<a class="button" href="<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/sched.php?smethod=schedule&hosts_alive=1&scan_locally=1', 'environment', 'vulnerabilities', 'scan_jobs');?>">
                            <?php echo _("New Scan Job");?>
    				</a>
    			</div>
    			
    			<div style="float:left;height:28px;margin:5px 5px 0px -2px;">
    				<a class="greybox button av_b_secondary" href="import_nbe.php" title="<?php echo _("Import nbe file") ?>">
    				        <?php echo _("Import nbe file");?>
    				</a>
    			</div>
			</div>		
			
		</div>
		
		<?php
	}

if ( intval($_GET['page'])!=0 ){ $page = intval($_GET['page']);}
else $page = 1;

$pagesize = 10;

if($username=="admin") {$query = "SELECT count(id) as num FROM vuln_jobs";}
else {$query = "SELECT count(id) as num FROM vuln_jobs where username='$username'";}

$result = $dbconn->Execute($query);
$jobCount =$result->fields["num"];

$num_pages = ceil($jobCount/$pagesize);

//echo "num_pages:[".$num_pages."]";
//echo "jobCount:[".$jobCount."]";
//echo "page:[".$page."]";

if (Vulnerabilities::scanner_type() == "omp") { // We can display scan status with OMP protocol
    echo Vulnerabilities::get_omp_running_scans($dbconn);
}
else { // Nessus
    all_jobs(0,10, "R"); 
}
?>

<?php

$schedulejobs = _("Scheduled Jobs");
   echo <<<EOT

   <table style='margin-top:20px;' class='w100 transparent'><tr><td class='sec_title'>$schedulejobs</td></tr></table>
   <table summary="Job Schedules" class='w100 table_list'>
EOT;

   if($sortdir == "ASC") { $sortdir = "DESC"; } else { $sortdir = "ASC"; }
   $arr = array( "name" => "Name", "schedule_type" => "Schedule Type", "time" => "Time", "next_CHECK" => "Next Scan", "enabled" => "Status");


// modified by hsh to return all scan schedules
if (empty($arruser)){
    $query = "SELECT t2.name as profile, t1.meth_TARGET, t1.id, t1.name, t1.schedule_type, t1.meth_VSET, t1.meth_TIMEOUT, t1.username, t1.enabled, t1.next_CHECK, t1.email
              FROM vuln_job_schedule t1 LEFT JOIN vuln_nessus_settings t2 ON t1.meth_VSET=t2.id ";
}
else {
    $query = "SELECT t2.name as profile, t1.meth_TARGET, t1.id, t1.name, t1.schedule_type, t1.meth_VSET, t1.meth_TIMEOUT, t1.username, t1.enabled, t1.next_CHECK, t1.email
              FROM vuln_job_schedule t1 LEFT JOIN vuln_nessus_settings t2 ON t1.meth_VSET=t2.id WHERE username in ($user) ";
}
    $query .= $sql_order;
    $result=$dbconn->execute($query);

    if ($result->EOF){
        echo "<tr><td class='empty_results' height='20' style='text-align:center;'>"._("No Scheduled Jobs")."</td></tr>";
    }
    if (!$result->EOF) {
        echo "<tr>";
        foreach ( $arr as $order_by => $value) {
        echo "<th><a href=\"manage_jobs.php?sortby=$order_by&sortdir=$sortdir\">"._($value)."</a></th>";
        }
        if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
            echo "<th>"._("Action")."</th></tr>";
        }
    }
    
    $colors  = array("#FFFFFF", "#EEEEEE");
    $color   = 0;
    
    
    while (!$result->EOF) {
       list ($profile, $targets, $schedid, $schedname, $schedtype, $sid, $timeout, $user, $schedstatus, $nextscan, $servers )=$result->fields;
        
        $name    = Av_sensor::get_name_by_id($dbconn, $servers);
        
        $servers = ( $name != '' ) ? $name : "unknown";
        
        $targets_to_resolve = explode("\n", $targets);
        $ttargets           = array();

        foreach($targets_to_resolve as $id_ip) {
            if( preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+\/\d{1,2}/i", $id_ip, $found) && Asset_net::is_in_db($dbconn, $found[1])) {
                $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip)." (".Asset_net::get_name_by_id($dbconn, $found[1]).")";
            }
            else if( preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+/i", $id_ip, $found) &&  Asset_host::is_in_db($dbconn, $found[1])) {
                $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip)." (".Asset_host::get_name_by_id($dbconn, $found[1]).")";
            }
            else {
                $ttargets[] = preg_replace("/[a-f\d]{32}/i","",$id_ip);
            }
        }

        $targets = implode("<BR/>", $ttargets);
       
        $tz = intval($tz);
        $nextscan = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($nextscan)+(3600*$tz));
        
        preg_match("/\d+\-\d+\-\d+\s(\d+:\d+:\d+)/",$nextscan,$found);
        $time = $found[1];

       switch ($schedtype) {
       case "N":
          $stt = _("Once (Now)");
          break;
       case "O":
          $stt = _("Once");
          break;
       case "D":
          $stt = _("Daily");
          break;
       case "W":
          $stt = _("Weekly");
          break;
       case "M":
          $stt = _("Monthly");
          break;
       case "Q":
          $stt = _("Quarterly");
          break;
       case "H":
          $stt = _("On Hold");
          break;
       case "NW":
          $stt = _("N<sup>th</sup> weekday of the month");
          break;
       default:
          $stt="&nbsp;";
          break;
       }

       switch ($schedstatus) {
       case "1":
          $itext=_("Disable Scheduled Job");
          $isrc="images/stop_task.png";
          $ilink = "manage_jobs.php?disp=setstatus&schedid=$schedid&enabled=0";
          break;
       default:
          $itext=_("Enable Scheduled Job");
          $isrc="images/play_task.png";
          $ilink = "manage_jobs.php?disp=setstatus&schedid=$schedid&enabled=1";          
          break;
       }
       
        if (!Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
            $ilink = "javascript:return false;";
        }

       if ( $schedstatus ) { 
          $txt_enabled = "<td><a href=\"$ilink\"><font color=\"green\">"._("Enabled")."</font></a></td>"; 
       } else { 
          $txt_enabled = "<td><a href=\"$ilink\"><font color=\"red\">"._("Disabled")."</font></a></td>"; 
       }

       require_once ('classes/Security.inc');

        if(valid_hex32($user)) 
        {
            $user = Session::get_entity_name($dbconn, $user);
        }
       
       echo "<tr bgcolor=\"".$colors[$color%2]."\">";
    if ($profile=="") $profile=_("Default");
    echo "<td><span class=\"tip\" title=\"<b>"._("Owner").":</b> $user<br><b>"._("Server").":</b> $servers<br /><b>"._("Scheduled Job ID").":</b> $schedid<br><b>"._("Profile").":</b> $profile<br><b>"._("Targets").":</b><br>".$targets."\">$schedname</span></td>";
?>
    <td><?php echo $stt ?></td>
    <td><?php echo $time ?></td>
    <td><?php echo $nextscan ?></td>
<?php
    echo <<<EOT
    $txt_enabled
    <td style="padding-top:2px;"><a href="$ilink"><img alt="$itext" src="$isrc" border=0 title="$itext"></a>&nbsp;
EOT;
    if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
    echo "<a href='".Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/sched.php?disp=edit_sched&sched_id='.$schedid, 'environment', 'vulnerabilities', 'scan_jobs')."'><img src='images/pencil.png' title='"._("Edit Scheduled")."'></a>&nbsp;";
    echo "<a href='manage_jobs.php?disp=delete&amp;schedid=$schedid' onclick='return confirmDelete();'><img src='images/delete.gif' title='".gettext("Delete Scheduled")."'></a>";
    }
    echo "</td>";
       echo <<<EOT
</tr>
EOT;
       $result->MoveNext();
       $color++;
    }
    echo <<<EOT
</table>
EOT;

?>
<br />
<?php
$out = all_jobs(($page-1)*$pagesize,$pagesize);
?>
<table width="100%" align="center" class="transparent" cellspacing="0" cellpadding="0">
    <tr>
        <td class="nobborder" valign="top" style="padding-top:5px;">
            <div class="fright">
                <?
                if ($out!=0 && $num_pages!=1)
        		{
        			$page_url = "manage_jobs.php";
        			
                    if ($page==1 && $page==$num_pages){ 
        				echo '<a href="" class="link_paginate_disabled" onclick="return false">< '._("PREVIOUS").'</a>';
        				echo '<a class="lmargin link_paginate_disabled" href="" onclick="return false">'._("NEXT").' ></a>'; 
        			} 
                    elseif ($page==1){ 
        				echo '<a href="" class="link_paginate_disabled" onclick="return false">< ' . _("PREVIOUS") . '</a>';
        				echo '<a class="lmargin" href="'.$page_url.'?page='.($page+1).'">'._("NEXT").' ></a>&nbsp;';
        			}
                    elseif($page == $num_pages){
        				echo '<a href="'.$page_url.'?page='.($page-1).'">< '._("PREVIOUS").'</a>';
        				echo '<a class="lmargin link_paginate_disabled" href="" onclick="return false">' . _("NEXT").' ></a>';
        			}
                    else {
        				echo '<a href="'.$page_url.'?page='.($page-1).'">< '._("PREVIOUS").'</a><a class="lmargin" href="'.$page_url.'?page='.($page+1).'">'._("NEXT").' ></a>';
        			}
                }
                ?>
            </div>
        </td>
    </tr>
    </table>
<?
}

$commands = array("play_task", "pause_task", "stop_task", "resume_task", "delete_task"); // OMP commands

if ( in_array($disp, $commands) ) { // get server info to manage tasks

    $uuid = Util::get_system_uuid();

    $result_server = $dbconn->Execute("SELECT meth_Wcheck FROM vuln_jobs WHERE id=".$job_id );
    
    preg_match("/.*\s(\d+\.\d+\.\d+\.\d+)<.*/", $result_server->fields['meth_Wcheck'], $found);
    
    $sensor_id     = Av_sensor::get_id_by_ip($dbconn, $found[1]);
    
    $sensor_object = new Av_sensor($sensor_id);
        
    $sensor_object->load_from_db($dbconn);
            
    $ov_credentials = $sensor_object->get_vs_credentials($dbconn);
            
    $port     = $ov_credentials['port'];
    $user     = $ov_credentials['user'];
    $password = $ov_credentials['password'];          
    
    $omp = new Omp($sensor_object->get_ip(), $port, $user, $password);
}

switch($disp) {

    case "kill":
        $schedid = intval($schedid);
        if ($schedid>0) {
            system("sudo /usr/share/ossim/scripts/vulnmeter/cancel_scan.pl $schedid");
        }
        break;
    case "play_task":
        $omp->play_task($job_id);
        break;
        
    case "pause_task":
        $omp->pause_task($job_id);
        break;
        
    case "stop_task":
        $omp->stop_task($job_id);
        break;
        
    case "resume_task":
        $omp->resume_task($job_id);
        break;
        
    case "delete_task":
        $omp->delete_task($job_id);
        break;

    case "delete":
        delete_sched( $schedid );
        break;

    case "setstatus":
        set_status ( $schedid, $enabled );
        break;    
        
    default:
       main_page( 1, $sortby, $sortdir );
       break;
}

require_once("footer.php");

?>
