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


require ("base_conf.php");
require ("vars_session.php");
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
   <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta http-equiv="pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" HREF="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>">
		<!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
		<style>
        .plot_msg
        {
        	    text-align: center;
        	    width: 100%;
        	    padding-top: 20px;
        }
        </style>
		<script type="text/javascript" src="../js/jquery.min.js"></script>
		<script  type="text/javascript" src="../js/jquery.flot.pie.js"></script>
		<script language="javascript" src="../js/jquery.bgiframe.min.js"></script>
		
		<script type='text/javascript'>
	
		function formatNmb(nNmb){
			var sRes = ""; 
			for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
				sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? "<?=thousands_locale()?>": "") + sRes;
			return sRes;
		}
		
		var url = new Array(50)
		
		function showTooltip(x, y, contents, link) {
			link = link.replace(".","");
			link = link.replace(",","");
			$('<div id="tooltip" class="tooltipLabel" onclick="top.frames[\'main\'].document.location.href=\'' + url[link] + '&submit=Query DB\'"><a href="' + url[link] + '&submit=Query DB" target="main" style="font-size:10px;">' + contents + '</a></div>').css( {
				position: 'absolute',
				display: 'none',
				top: y - 18,
				left: x + 5,
				border: '1px solid #ADDF53',
				padding: '1px 2px 1px 2px',
				'background-color': '#CFEF95',
				opacity: 0.80
			}).appendTo("body").fadeIn(200);
		}
		
		Array.prototype.in_array = function(p_val) {
			for(var i = 0, l = this.length; i < l; i++) {
				if(this[i] == p_val) {
					return true;
				}
			}
			return false;
		}	
    </script>
		
    </head>
    
	<body>
		<center><div id="plotareaglobal" class="plot" style="text-align:center;margin:12px 15px 0px 0px;"></div></center>

		<?php
		$qs = new QueryState();
		$db = NewBASEDBConnection($DBlib_path, $DBtype);
		$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

		$sqlgraph = $_SESSION['siem_current_query_graph'];
		$tr = ($_SESSION["time_range"] != "") ? $_SESSION["time_range"] : "all";
		$trdata = array(0,0,$tr);
		if ($tr=="range")
        {
			$desde = strtotime($_SESSION["time"][0][4]."-".$_SESSION["time"][0][2]."-".$_SESSION["time"][0][3].' '.$_SESSION['time'][0][5].':'.$_SESSION['time'][0][6].':'.$_SESSION['time'][0][7]);
			$hasta = strtotime($_SESSION["time"][1][4]."-".$_SESSION["time"][1][2]."-".$_SESSION["time"][1][3].' '.$_SESSION['time'][1][5].':'.$_SESSION['time'][1][6].':'.$_SESSION['time'][1][7]);
			$trdata = array ($desde,$hasta,"range");
		}
		list($x, $y, $xticks, $xlabels) = range_graphic($trdata);
		if (count($y) > 1)
		{
        		//echo "SQLG:$sqlgraph -->";
        		$res = $qs->ExecuteOutputQueryNoCanned($sqlgraph, $db);
        		//echo " COUNT:".$res->baseRecordCount()."<br>";
        		while ($rowgr = $res->baseFetchRow()) {
        			//print_r($rowgr);
        			$label = trim($rowgr[1] . " " . $rowgr[2]);
        			if (isset($y[$label]) && $y[$label] == 0) $y[$label] = $rowgr[0];
        			//echo "$label = $rowgr[0] <br>";
        		}
        		// Report data
        		$gdata = array();
        		foreach ($y as $label => $val) {
        			$gdata[] = array ($label,"","","","","","","","","","",$val,0,0);
        		}
        		$qs->SaveReportData($gdata,$graph_report_type);
        		//print_r($xlabels);print_r($xticks);print_r ($x);print_r ($y);
        		$plot = plot_graphic("plotareaglobal", 60, 600, $x, $y, $xticks, $xlabels, true, 'base_qry_main.php?num_result_rows=-1&current_view=-1');
        		//echo "PLOT:".Util::htmlentities($plot).".";
        		echo $plot;
        
        		$res->baseFreeRows();
		}
		else
		{
		    echo '<div class="plot_msg">'._('Trend graph is not available with this date range').'</div>';
		}
echo "</body></html>";
?>
