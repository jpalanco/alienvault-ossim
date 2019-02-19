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

require_once ('av_init.php');
require_once ('vars_session.php');

Session::logcheck("analysis-menu", "EventsForensics");
require_once 'ossim_db.inc';

$iu1 = "Timeline.DateTime.MINUTE";
$iu2 = "Timeline.DateTime.HOUR";
$intpx  = 20;
$intpx2 = 300;
$steps1 = "{unit:Timeline.DateTime.MINUTE, pixelsPerInterval:$intpx},";

switch ( GET('resolution')){
	case "s":
		$iu1 = "Timeline.DateTime.SECOND";
		$iu2 = "Timeline.DateTime.MINUTE";
		$intpx  = 20;
		$steps1 = "";
		$steps2 = "";		
	break;
	
	case "m":
		$iu1 = "Timeline.DateTime.MINUTE";
		$iu2 = "Timeline.DateTime.HOUR";
		$intpx  = 20;
		$steps1 = "{unit:Timeline.DateTime.MINUTE, pixelsPerInterval:$intpx},";
		$steps2 = "{unit:Timeline.DateTime.HOUR, pixelsPerInterval:$intpx2},";		
	break;
	
	case "h":
		$iu1 = "Timeline.DateTime.HOUR";
		$iu2 = "Timeline.DateTime.DAY";
		$intpx  = 50;
		$steps1 = "{unit:Timeline.DateTime.HOUR, pixelsPerInterval:$intpx},";
		$steps1 .= "{unit:Timeline.DateTime.MINUTE, pixelsPerInterval:20},";
		$steps2 = "{unit:Timeline.DateTime.DAY, pixelsPerInterval:$intpx2},";
		$steps2 .= "{unit:Timeline.DateTime.HOUR, pixelsPerInterval:$intpx2},";		
	break;
	
	case "d":
		$iu1 = "Timeline.DateTime.DAY";
		$iu2 = "Timeline.DateTime.MONTH";
		$intpx  = 50;
		$steps1 = "{unit:Timeline.DateTime.DAY, pixelsPerInterval:$intpx},";
		$steps1 .= "{unit:Timeline.DateTime.HOUR, pixelsPerInterval:50},";
		$steps1 .= "{unit:Timeline.DateTime.MINUTE, pixelsPerInterval:20},";
		$steps2 = "{unit:Timeline.DateTime.MONTH, pixelsPerInterval:$intpx2},";
		$steps2 .= "{unit:Timeline.DateTime.DAY, pixelsPerInterval:$intpx2},";
		$steps2 .= "{unit:Timeline.DateTime.HOUR, pixelsPerInterval:$intpx2},";				
	break;
}



$db = new ossim_db(true);
$conn = $db->connect();

$sql = "SELECT * FROM `datawarehouse`.`report_data` WHERE id_report_data_type = $events_report_type AND USER = ? ORDER BY dataV2 ASC LIMIT 0,1";

$user = $_SESSION['_user'];
settype($user, "string");
$params = array(
	$user
);

			
if (!$rs = $conn->Execute($sql, $params)) {
	print 'Error: ' . $conn->ErrorMsg() . '<br/>';
	exit;
}
else
{
	$date = explode (" ",  $rs->fields['dataV2']);
	$d = explode("-", $date[0]);
	$t = explode(":", $date[1]);

	if ($t[0]!="")
		$timestamp = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
	else
		$timestamp = time();
	$init_date = date("M d Y G:i:s", $timestamp)." GMT";
	
}

$sql = "SELECT * FROM `datawarehouse`.`report_data` WHERE id_report_data_type = $events_report_type AND USER = ? ORDER BY dataV2 DESC LIMIT 0,1";

$params = array(
	$user
);

			
if (!$rs = $conn->Execute($sql, $params)) {
	print 'Error: ' . $conn->ErrorMsg() . '<br/>';
	exit;
}
else
{
	$date = explode (" ",  $rs->fields['dataV2']);
	$d = explode("-", $date[0]);
	$t = explode(":", $date[1]);

	if ($t[0]!="")
		$timestamp = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
	else
		$timestamp = time();
	$end_date = date("M d Y G:i:s", $timestamp)." GMT";
	
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>Forensics Timeline</title>
<style type="text/css">

body {font-family: Arial, Verdana, Helvetica, sans serif; font-size: 12px;}

.txt_desc { text-align: center; width:98%; margin:auto; padding-bottom:20px;}

a{text-decoration: none; color: #4e487d; font-size: 12px;}
a:hover {text-decoration: underline;}

.timeline-default {
    font-size: 8pt;
    border: 1px solid #aaa;
}
.timeline-event-label { padding-left:5px; }

.timeline-event-bubble-time {display: none;}

.df { color: #4e487d; text-align: center;}


</style>
<!--<script type='text/javascript' src="http://static.simile.mit.edu/timeline/api-2.3.0/timeline-api.js?bundle=true" type="text/javascript"></script>-->
<script>
 Timeline_ajax_url="/ossim/forensics/js/timeline_ajax/simile-ajax-api.js";
 Timeline_urlPrefix='/ossim/forensics/js/timeline_js/';       
 Timeline_parameters='bundle=true';
</script>
<script type='text/javascript' src="/ossim/forensics/js/timeline_js/timeline-api.js" type="text/javascript"></script>
<script type='text/javascript' src="/ossim/js/jquery.min.js" type="text/javascript"></script>
 
<script type='text/javascript'>
	var tl = null;
	function onLoad() {
		var eventSource = new Timeline.DefaultEventSource();
		
		var date = "<?=$init_date?>";
		
		var mytheme = Timeline.getDefaultTheme();
		mytheme.mouseWheel = "zoom";
		
		var bandInfos = [
			Timeline.createBandInfo({
				overview:       true,
				eventSource:    eventSource,
				date:           date,
				width:          "18%", 
				intervalUnit:   <?=$iu2?>, 
				intervalPixels: <?=$intpx2?>,
				theme: mytheme				
				//zoomIndex:0,
				//zoomSteps:[
				//	<?=$steps2?>
				//	{unit:Timeline.DateTime.MINUTE, pixelsPerInterval:20}
				//]	
			}),
			Timeline.createBandInfo({
				eventSource:    eventSource,
				date:           date,
				width:          "82%", 
				intervalUnit:   <?=$iu1?>, 
				intervalPixels: <?=$intpx?>,
				theme: mytheme,
				zoomIndex:0,
				zoomSteps:[
					<?=$steps1?>
					{unit:Timeline.DateTime.SECOND, pixelsPerInterval:20}
				]
			})
		];
		bandInfos[0].syncWith = 1;
		bandInfos[0].highlight = true;   
        bandInfos[0].decorators = [
            new Timeline.SpanHighlightDecorator({
                startDate:  "<?=$init_date?>",
                endDate:    "<?=$end_date?>",
                startLabel: "<?=_("first event")?>",
                endLabel:   "<?=_("last event")?>",
                color:      "#28BC04",
                opacity:    10,
                theme:      mytheme
            })
        ];        
		tl = Timeline.create(document.getElementById("tm"), bandInfos);
		Timeline.loadXML("base_timeline_xml.php", function(xml, url) { eventSource.loadXML(xml, url); });
		
		setupFilterHighlightControls(document.getElementById("controls"), tl, [0,1], mytheme);
	}

	var resizeTimerID = null;
	function onResize() {
		if (resizeTimerID == null) {
			resizeTimerID = window.setTimeout(function() {
				resizeTimerID = null;
				tl.layout();
			}, 500);
		}
	}
    
    function pageForward() {
            var maxDate = tl.getBand(0).getMaxVisibleDate();
            tl.getBand(0).setMinVisibleDate(maxDate);
    }

    function pageBack() {
            var minDate = tl.getBand(0).getMinVisibleDate();
            tl.getBand(0).setMaxVisibleDate(minDate);
    }
    
	// highlight controls
	function centerSimileAjax(date) {
	    tl.getBand(0).setCenterVisibleDate(SimileAjax.DateTime.parseGregorianDateTime(date));
	}
	
	function setupFilterHighlightControls(div, timeline, bandIndices, theme) {
	    var table = document.createElement("table");
	    var tr = table.insertRow(0);
	    
	    var td = tr.insertCell(0);
	    td.innerHTML = "<?=_("Filter")?>:";
	    
	    td = tr.insertCell(1);
	    td.innerHTML = "<?=_("Highlight")?>:";
	    
	    var handler = function(elmt, evt, target) {
	        onKeyPress(timeline, bandIndices, table);
	    };
	    
	    tr = table.insertRow(1);
	    tr.style.verticalAlign = "top";
	    
	    td = tr.insertCell(0);
	    
	    var input = document.createElement("input");
	    input.type = "text"; input.size = 12;
	    SimileAjax.DOM.registerEvent(input, "keypress", handler);
	    td.appendChild(input);
	    
	    for (var i = 0; i < theme.event.highlightColors.length; i++) {
	        td = tr.insertCell(i + 1);
	        
	        input = document.createElement("input");
	        input.type = "text"; input.size = 12;
	        SimileAjax.DOM.registerEvent(input, "keypress", handler);
	        td.appendChild(input);
	        
	        var divColor = document.createElement("div");
	        divColor.style.height = "4px";
	        divColor.style.background = theme.event.highlightColors[i];
	        td.appendChild(divColor);
	    }
	    
	    td = tr.insertCell(tr.cells.length);
	    var button = document.createElement("button");
	    button.innerHTML = "<?=_("Clear All")?>";
	    SimileAjax.DOM.registerEvent(button, "click", function() {
	        clearAll(timeline, bandIndices, table);
	    });
	    td.appendChild(button);
	    
	    div.appendChild(table);
	}
	
	var timerID = null;
	function onKeyPress(timeline, bandIndices, table) {
	    if (timerID != null) {
	        window.clearTimeout(timerID);
	    }
	    timerID = window.setTimeout(function() {
	        performFiltering(timeline, bandIndices, table);
	    }, 300);
	}
	function cleanString(s) {
	    return s.replace(/^\s+/, '').replace(/\s+$/, '');
	}
	function performFiltering(timeline, bandIndices, table) {
	    timerID = null;
	    
	    var tr = table.rows[1];
	    var text = cleanString(tr.cells[0].firstChild.value);
	    
	    var filterMatcher = null;
	    if (text.length > 0) {
	        var regex = new RegExp(text, "i");
	        filterMatcher = function(evt) {
	            return regex.test(evt.getText()) || regex.test(evt.getDescription());
	        };
	    }
	    
	    var regexes = [];
	    var hasHighlights = false;
	    for (var x = 1; x < tr.cells.length - 1; x++) {
	        var input = tr.cells[x].firstChild;
	        var text2 = cleanString(input.value);
	        if (text2.length > 0) {
	            hasHighlights = true;
	            regexes.push(new RegExp(text2, "i"));
	        } else {
	            regexes.push(null);
	        }
	    }
	    var highlightMatcher = hasHighlights ? function(evt) {
	        var text = evt.getText();
	        var description = evt.getDescription();
	        for (var x = 0; x < regexes.length; x++) {
	            var regex = regexes[x];
	            if (regex != null && (regex.test(text) || regex.test(description))) {
	                return x;
	            }
	        }
	        return -1;
	    } : null;
	    
	    for (var i = 0; i < bandIndices.length; i++) {
	        var bandIndex = bandIndices[i];
	        timeline.getBand(bandIndex).getEventPainter().setFilterMatcher(filterMatcher);
	        timeline.getBand(bandIndex).getEventPainter().setHighlightMatcher(highlightMatcher);
	    }
	    timeline.paint();
	}
	function clearAll(timeline, bandIndices, table) {
	    var tr = table.rows[1];
	    for (var x = 0; x < tr.cells.length - 1; x++) {
	        tr.cells[x].firstChild.value = "";
	    }
	    
	    for (var i = 0; i < bandIndices.length; i++) {
	        var bandIndex = bandIndices[i];
	        timeline.getBand(bandIndex).getEventPainter().setFilterMatcher(null);
	        timeline.getBand(bandIndex).getEventPainter().setHighlightMatcher(null);
	    }
	    timeline.paint();
	}
</script>      

</head>
<body onload="onLoad();" onresize="onResize();">
	
	<div id="tm" class="timeline-default" style="height:380px;margin:0px;padding:0px"></div>
	<div id="controls" style="padding-top:0px;color:gray;font-size:11px;float:left"></div> 
	<div id="marks" style="padding-top:5px;color:gray;font-size:11px;float:right">
	<a href="javascript:pageBack();" style="color:gray;font-size:11px"><<|</a>&nbsp;&nbsp;
	<a href="javascript:pageForward();" style="color:gray;font-size:11px">|>></a>
    
	</div>

	<div class="timeline-message-container" style='display: block'>
		<div style="height: 33px; background: url(js/timeline_ajax/images/message-top-left.png) no-repeat scroll left top transparent; padding-left: 44px;">
			<div style="height: 33px; background: url(js/timeline_ajax/images/message-top-right.png) no-repeat scroll right top transparent;"></div>
		</div>
		
		<div style="background: url(js/timeline_ajax/images/message-left.png) repeat-y scroll left top transparent; padding-left: 44px;">
			<div style="background: url(js/timeline_ajax/images/message-right.png) repeat-y scroll right top transparent; padding-right: 44px;">
				<div class="timeline-message"><img src="js/timeline_js/images/progress-running.gif"> Loading...</div>
			</div>
		</div>
		
		<div style="height: 55px; background: url(js/timeline_ajax/images/message-bottom-left.png) no-repeat scroll left bottom transparent; padding-left: 44px;">
			<div style="height: 55px; background: url(js/timeline_ajax/images/message-bottom-right.png) no-repeat scroll right bottom transparent;"></div>
		</div>
	</div>

</body>
</html>
