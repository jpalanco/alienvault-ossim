/*

javascript RRD graph time frame selector
BSD license http://sysctl.org/bsd.php
Philippe Bourcier - 2006

DOM drag functions inspired by
http://www.youngpup.net/2001/domdrag

UNIX timestamp conversion tools by
http://www.captain.at/review-unixtime-javascript.php

*/

function unixtimetodate(ts) {
    var theDate = new Date(ts * 1000);
	var year = theDate.getFullYear();
	var month = theDate.getMonth() + 1;
	if ( month < 10 )
		month = "0" + month;
	var day = theDate.getDate();
	if ( day < 10 )
		day = "0" + day;
	var hour = theDate.getHours();
	if ( hour < 10 ) 
		hour = "0" + hour;
	var minute = theDate.getMinutes();
	if ( minute < 10 )
		minute = "0" + minute;

    return year + "-" + month + "-" + day + "-" + hour + "-" + minute;
}

if(document.all && !document.getElementById) {
    document.getElementById = function(id) { return document.all[id]; }
}

var ReportBox = {

		obj : null,
		init : function(box_tleft, box_tright, ts, tcs, tce, xmin, step ) {
			box_tleft.xmin = xmin;
			box_tleft.step = step;
			box_tleft.tstart = ts;
			box_tleft.unixtime = tcs;
			box_tleft.innerHTML = ReportBox.unix2iso(tcs);
			box_tleft.update = ReportBox.update;

			box_tright.xmin = xmin;
			box_tright.step = step;
			box_tright.tstart = ts;
			box_tright.unixtime = tce;
			box_tright.innerHTML = ReportBox.unix2iso(tce);
			box_tright.update = ReportBox.update;

		},
		unix2iso : function (ts) {
			var theDate = new Date((ts + GMToffset) * 1000);
			var year = theDate.getFullYear();

			var month = theDate.getMonth() + 1;
			if ( month < 10 )
				month = "0" + month;

			var day = theDate.getDate();
			if ( day < 10 )
				day = "0" + day;

			var hour = theDate.getHours();
			if ( hour < 10 ) 
				hour = "0" + hour;

			var minute = theDate.getMinutes();
			if ( minute < 10 )
				minute = "0" + minute;
			
			return year + "-" + month + "-" + day + "-" + hour + "-" + minute;
		},
		update : function(x) {
			var box = this;
			box.unixtime = box.tstart + ((x - box.xmin)*box.step);
			box.innerHTML = ReportBox.unix2iso(box.unixtime);
		}
};

var Drag = {

        obj : null,
        init : function(s, e, psize, minX, maxX) {
                onDrag = new Function();

                if (isNaN(parseInt(s.style.left))) s.style.left = "0px";
                s.minX  = typeof minX != 'undefined' ? minX : null;
                s.maxX  = typeof maxX != 'undefined' ? maxX : null;
                if (isNaN(parseInt(e.style.left))) e.style.left = "0px";
                e.minX  = typeof minX != 'undefined' ? minX : null;
                e.maxX  = typeof maxX != 'undefined' ? maxX : null;

        s.stop = e;
        e.stop = s;
		s.delta = psize - 1;
		e.delta = 0;
		s.isleft = 1;
		e.isleft = 0;

                s.onmousedown = Drag.start;
                e.onmousedown = Drag.start;
        },
        start : function(m) {
                var o = Drag.obj = this;
                var x = parseInt(o.style.left) + o.delta;
        		var z = parseInt(o.stop.style.left) + parseInt(o.stop.delta);

                m = Drag.fixE(m);
                o.lastMouseX = m.clientX;
				
				if ( o.isleft == 1 )
        			o.maxX = z;
        		else 
					o.minX = z;

                if (o.minX != null) o.minMouseX = m.clientX - x + o.minX;
                if (o.maxX != null) o.maxMouseX = o.minMouseX + o.maxX - o.minX;
                document.onmousemove = Drag.drag;
                document.onmouseup   = Drag.end;
                return false;
        },
        drag : function(m) {
                m = Drag.fixE(m);
                var o = Drag.obj;
                var ex  = m.clientX;
                var x = parseInt(o.style.left) + o.delta;
                var nx;

                if (o.minX != null) ex = Math.max(ex, o.minMouseX);
                if (o.maxX != null) ex = Math.min(ex, o.maxMouseX);
                nx = x + (ex - o.lastMouseX);

                Drag.obj.style["left"] = nx - o.delta + "px";
                Drag.obj.lastMouseX = ex;
                Drag.obj.onDrag(nx);
                return false;
        },
        end : function() {
                document.onmousemove = null;
                document.onmouseup   = null;
                Drag.obj = null;
				UpdateStat();
        },
        fixE : function(m) {
                if (typeof m == 'undefined') m = window.event;
                if (typeof m.layerX == 'undefined') m.layerX = m.offsetX;
                return m;
        }
};


var DragCursor = {

        obj : null,
        init : function(DragHandle, minX, maxX) {

                onDrag = new Function();

                if (isNaN(parseInt(DragHandle.style.left))) DragHandle.style.left = "0px";
                DragHandle.minX  = typeof minX != 'undefined' ? minX : null;
                DragHandle.maxX  = typeof maxX != 'undefined' ? maxX : null;

                DragHandle.onmousedown = DragCursor.start;
        },
        set : function(m) {
				// Set cursor only in single slot mode
				if ( CursorMode == 1 ) 
					return false;

                var o = DragCursor.obj = document.getElementById('CursorDragHandle');
                m = DragCursor.fixE(m);
				var ex = m.clientX;

				if ( ex >= o.minX && ex <= o.maxX ) {
					o.style["left"] = ex - o.z + "px";
					document.getElementById("StartLine").style.left = ex + "px";
                	o.lastMouseX = ex;
					document.getElementById("box_tleft").update(ex);
					document.getElementById("box_tright").update(ex);
					UpdateStat();
				}

                return false;
        },
        start : function(m) {
                var o = DragCursor.obj = document.getElementById('CursorDragHandle');
                var x = parseInt(o.style.left) + o.z;

                m = DragCursor.fixE(m);
				var ex = m.clientX;
                o.lastMouseX = ex;

                o.minMouseX = m.clientX - x + o.minX;
                o.maxMouseX = o.minMouseX + o.maxX - o.minX;

                document.onmousemove = DragCursor.drag;
                document.onmouseup   = DragCursor.end;
                return false;
        },
        drag : function(m) {
                m = DragCursor.fixE(m);
                var o = DragCursor.obj;
                var ex  = m.clientX;
                var x = parseInt(o.style.left) + o.z;
                var nx;

                if (o.minX != null) ex = Math.max(ex, o.minMouseX);
                if (o.maxX != null) ex = Math.min(ex, o.maxMouseX);
                nx = x + (ex - o.lastMouseX);

               	DragCursor.obj.style["left"] = nx - o.z + "px";
                DragCursor.obj.lastMouseX = ex;
                DragCursor.obj.onDrag(nx);
                return false;
        },
        end : function() {
                document.onmousemove = null;
                document.onmouseup   = null;
                DragCursor.obj = null;
				UpdateStat();
        },
        fixE : function(m) {
                if (typeof m == 'undefined') m = window.event;
                if (typeof m.layerX == 'undefined') m.layerX = m.offsetX;
                return m;
        }
};

/*
 * ts	UNIX start time of graph
 * te	UNIX end time of graph
 * ps	UNIX start time of profile
 * tcs	UNIX start time of left (start) cursor
 * tce	UNIX end time of right (end) cursor
 * width	width of graph in pixel from ts to te
 * rrd_off	rrd offset from left margin of picture to ts
 */
function WSelectInit(ts, te, ps, tcs, tce, width, rrd_off) {

	var step = (te - ts)/width;
	var rrd = document.getElementById("MainGraph");
	var startx = rrd.offsetLeft;
	var starty = rrd.offsetTop;

	var xposstart = startx + rrd_off + Math.floor( (tcs-ts)*width/(te-ts) );
	var xposstop  = startx + rrd_off + Math.floor( (tce-ts)*width/(te-ts) );
	var ypos      = 227+starty;    // compatible with most RRD graphs
	var yposbox   = 32+starty;     // compatible with most RRD graphs
	var xmin = rrd_off + startx;    // compatible with most RRD graphs
	var xmax = width +xmin;         // for an image width of 667

	var StartDragHandle = document.getElementById("StartDragHandle");
	var StopDragHandle  = document.getElementById("StopDragHandle");
	
	// in case the profile start is later than than start time of the graph, limit xmin to profile start
	if ( ps > ts ) {
		xmin = xmin + Math.floor((ps-ts)*width/(te-ts));
		ts = ps;
	}

	/* 
		while an object is invisible it's width is set to zero => StartDragHandle.offsetWidth = 0.
		so use hard coded value 7
	 */
	var psize = 7;

	var StartLine = document.getElementById("StartLine");
	var SpanBox   = document.getElementById("SpanBox");
	var StopLine  = document.getElementById("StopLine");

	StartDragHandle.style.left    	= xposstart - psize + 1 + "px";
	StartDragHandle.style.top     	= ypos + "px";

	StopDragHandle.style.left     	= xposstop + "px";
	StopDragHandle.style.top      	= ypos + "px";

	StartLine.style.top  = yposbox + "px";
	StartLine.style.left = xposstart;

	StopLine.style.top  = yposbox + "px";
	StopLine.style.left = xposstop;

	SpanBox.style.top  = yposbox + "px";
	var width = xposstop - xposstart - 1;
	if ( width < 0 )
		width = 0;

	SpanBox.style.left = xposstart + 1;
	SpanBox.style.width = width;

	StartDragHandle.style.display 	= '';
	StopDragHandle.style.display 	= '';
	SpanBox.style.display    = '';
	StopLine.style.display   = '';
	StartLine.style.display  = '';

	reportBoxtleft	 = document.getElementById("box_tleft");
	reportBoxtright	 = document.getElementById("box_tright");

	Drag.init(StartDragHandle, StopDragHandle, psize, xmin, xmax);
	ReportBox.init(reportBoxtleft, reportBoxtright, ts, tcs, tce, xmin, step );
	StartDragHandle.onDrag = function(x) {
		var x_graph = x-xmin;
		var w = StartDragHandle.maxX - x;
		reportBoxtleft.update(x);
		StartLine.style.left = x;
		SpanBox.style.left = x + 1;
		SpanBox.style.width = w;
	}
	StopDragHandle.onDrag  = function(x) {
		var w = x - StopDragHandle.minX;
		reportBoxtright.update(x);
		StopLine.style.left = x;
		SpanBox.style.width = w;
	}
}

/*
 * ts	UNIX start time of graph
 * te	UNIX end time of graph
 * ps	UNIX start time of profile
 * tcs	UNIX time of cursor
 * width	width of graph in pixel from ts to te
 * rrd_off	rrd offset from left margin of picture to ts
 */
function SlotSelectInit(ts, te, ps, tcs, width, rrd_off) {

	var step = (te - ts)/width;

	var rrd = document.getElementById("MainGraph")
	var startx = rrd.offsetLeft;
	var starty = rrd.offsetTop;

	var xposstart = startx + rrd_off + Math.floor( (tcs-ts)*width/(te-ts) );
	var ypos      = 227+starty;    // compatible with most RRD graphs
	var yposbox   = 32+starty;     // compatible with most RRD graphs
	var xmin = rrd_off + startx;          // compatible with most RRD graphs
	var xmax = width +xmin;         // for an image width of 667

	// in case the profile start is later than than start time of the graph, limit xmin to profile start
	if ( ps > ts ) {
		xmin = xmin + Math.floor((ps-ts)*width/(te-ts));
		ts = ps;
	}
	var StartLine = document.getElementById("StartLine");
	var CursorDragHandle   = document.getElementById("CursorDragHandle");

	// postition the cursor
	StartLine.style.top  = yposbox   + "px";
	StartLine.style.left = xposstart + "px";
	StartLine.style.display = '';

	// position the drag handle
	CursorDragHandle.style.left	= xposstart - 6 + "px";
	CursorDragHandle.style.top	= ypos      + "px";
	CursorDragHandle.style.display = '';

	var psize = parseInt(CursorDragHandle.offsetWidth);
	var z = Math.floor(psize/2);

	/* 	while an object is invisible, it's dimensions are set to 0 by the browser
		therefore, we assume, it's our standard handle with z = 6, and hard code it into 
		the postion above. If not correct it now. But doing so, does not proper display the handle
	 */
	if ( z != 6 ) {
		CursorDragHandle.style.left	= xposstart - z + "px";
	}

	CursorDragHandle.z			= z;

	reportBoxtleft	 = document.getElementById("box_tleft");
	reportBoxtright	 = document.getElementById("box_tright");

	DragCursor.init(CursorDragHandle, xmin, xmax);
	ReportBox.init(reportBoxtleft, reportBoxtright, ts, tcs, tcs, xmin, step );
	CursorDragHandle.onDrag = function(x) {
		var x_graph = x-xmin;
		reportBoxtleft.update(x);
		reportBoxtright.update(x);
		StartLine.style.left = x + "px";
	}
}

var CursorMode = 0;
var GMToffest  = 0;

function SetCursorMode(ts, te, pe, tcs, tce, width, rrd_off) {

	var StartDragHandle  = document.getElementById("StartDragHandle");
	var StopDragHandle   = document.getElementById("StopDragHandle");
	var CursorDragHandle = document.getElementById("CursorDragHandle");
	var StartLine = document.getElementById("StartLine");
	var SpanBox   = document.getElementById("SpanBox");
	var StopLine  = document.getElementById("StopLine");

	StartDragHandle.style.display = 'none';
	StopDragHandle.style.display = 'none';
	CursorDragHandle.style.display = 'none';
	StartLine.style.display = 'none';
	SpanBox.style.display = 'none';
	StopLine.style.display = 'none';

	index = document.getElementById("ModeSelector").selectedIndex;
	if ( index == CursorMode )
		return;

	CursorMode = index;

	if ( index == 0 ) {
		SlotSelectInit(ts, te, pe, tcs, 576, rrd_off);
		UpdateStat();
	} else
		WSelectInit(ts, te, pe, tcs, tce, 576, rrd_off);


} // End of SetSelectMode

function UpdateStat() {

	document.getElementById("cursor_mode").value = CursorMode;

	var tleft = document.getElementById("box_tleft").unixtime;
	document.getElementById("tleft").value = tleft - ( tleft % 300 );

	var tright = document.getElementById("box_tright").unixtime;
	document.getElementById("tright").value = tright - ( tright % 300 );

	document.getElementById('slotselectform').submit();
} // End of UpdateStat

/* functions to handle stat table */

var current_visible = 1;

function GetStatPrefs() {

	var is_visible;
	var statpref = GetCookieValue('statpref');
	if ( statpref == null ) {
		is_visible = new Array( 0, 0, 0 );
	} else {
		is_visible = statpref.split(":");
	}

	return is_visible;

} // End of GetStatPrefs

function SetStatPrefs(is_visible) {
	SetCookieValue("statpref", is_visible.join(":"));
} // End of SetStatPrefs

function CollapseExpandStat(rows, proto, col_visible) {

	var display_opt;
	var colspan_opt;
	var is_visible = GetStatPrefs();
	if ( is_visible[proto] == 0 ) {
		display_opt = 'none';
		colspan_opt = 1;
		is_visible[proto] = col_visible;
		document.getElementById("arrow" + proto + "_down").style.display = 'none';
		document.getElementById("arrow" + proto + "_right").style.display = '';
	} else {
		display_opt = '';
		colspan_opt = 5;
		is_visible[proto] = 0;
		document.getElementById("arrow" + proto + "_down").style.display = '';
		document.getElementById("arrow" + proto + "_right").style.display = 'none';
	}

	for ( var row=0; row<= rows; row++ ) {
		for (var col=1; col<=5; col++ ) {
			cell = document.getElementById('id.' + row + '.' + proto + '.' + col);
			if ( col != col_visible ) {
				cell.style.display = display_opt;
			}
		}
	}
	var label = document.getElementById("label" + proto);
	label.colSpan = colspan_opt;
	SetStatPrefs(is_visible);

} // End of CollapseStat

function ShowHideStat() {

	var is_visible = GetCookieValue('statvisible') == 1 ? true : false;

	if ( is_visible ) {
		document.getElementById('stattable').style.display = 'none';
		document.getElementById('stat_arrow_down').style.display = 'none';
		document.getElementById('stat_arrow_right').style.display = '';

	} else {
		document.getElementById('stattable').style.display = '';
		document.getElementById('stat_arrow_down').style.display = '';
		document.getElementById('stat_arrow_right').style.display = 'none';
	}
	SetCookieValue("statvisible", is_visible ? 0 : 1);

} // End of ShowHideStat

/* functions required for the netflow processing tables */

var form_ok = true;
var is_edit_format = 0;
var edit_filter = null;

function ValidateProcessForm() {
	return form_ok;
} // End of ValidateProcessForm

function SwitchOptionTable(index) {

	var ListItems = new Array ('listN', 'timesorted');
	var StatItems = new Array ('topN', 'stattype', 'limitoutput');

	// common rows : Aggregate, output

	if ( index == 0 ) {
		list_style = '';
		stat_style = 'none';
		document.getElementById("AggregateRow").style.display = '';
		document.getElementById("FormatSelect").style.display = '';
	} else {
		list_style = 'none';
		stat_style = '';
		ShowHideOptions();
	}

	for (var i=0; i<ListItems.length; i++ ) {
		var item = ListItems[i];
		document.getElementById(item + "Row").style.display = list_style;
	}
	for (var i=0; i<StatItems.length; i++ ) {
		var item = StatItems[i];
		document.getElementById(item + "Row").style.display = stat_style;
	}

	PresetAggregate(index);

} // End of SwitchOptionTable

function PresetAggregate(mode) {
	var AggregateItems = new Array('aggr_proto', 'aggr_srcport', 'aggr_srcip', 'aggr_dstport', 'aggr_dstip');

	is_bidir = document.getElementById('aggr_bidir').checked;
	if ( is_bidir ) {
		for (var i=0; i<AggregateItems.length; i++ ) {
			var item = AggregateItems[i];
			document.getElementById(item).disabled = is_bidir;
		}
	}
	var checked = mode == 0 ? false : true;
	for (var i=0; i<AggregateItems.length; i++ ) {
		var item = AggregateItems[i];
		document.getElementById(item).checked = checked;
	}
	document.getElementById('aggr_srcselect').selectedIndex = 0;
	document.getElementById('aggr_srcselect').selectedIndex = 0;
	document.getElementById('aggr_srcnetbits').style.display = 'none';
	document.getElementById('aggr_dstnetbits').style.display = 'none';

} // End of PresetAggregate

function ToggleAggregate() {
	var AggregateItems = new Array('aggr_proto', 'aggr_srcport', 'aggr_srcip', 'aggr_dstport', 'aggr_dstip');

	is_checked = document.getElementById('aggr_bidir').checked;
	for (var i=0; i<AggregateItems.length; i++ ) {
		var item = AggregateItems[i];
		document.getElementById(item).disabled = is_checked;
	}

} // End of ToggleAggregate

function NetbitEntry(which) {

	index = document.getElementById('aggr_' + which + "select").selectedIndex;
	entry = document.getElementById('aggr_' + which + "netbits");
	switch(index) {
		case 0:	
			entry.style.display = 'none';
			break;
		case 1:
			entry.style.display = '';
			break;
		case 2:
			entry.style.display = '';
			break;
	}
} // End of NetbitEntry

function SelectAllSources () {
	var selector = document.getElementById("SourceSelector");
	var num_options = selector.length;
	for(var i=0; i<num_options; i++ ) {
		selector.options[i].selected = true;
	}
} // End of SelectAllSources

function ShowHideOptions() {
	var index = document.getElementById("StatTypeSelector").selectedIndex;
	var AggrRow = document.getElementById("AggregateRow");
	var OutputRow = document.getElementById("FormatSelect");
	if ( index == 0 ) {
		AggrRow.style.display   = '';
		OutputRow.style.display = '';
	} else {
		AggrRow.style.display   = 'none';
		OutputRow.style.display = 'none';
	}
} // End of ShowHideOptions

function CustomOutputFormat () {
	
	var output_select = document.getElementById("output");
	var edit_block = document.getElementById("fmt_edit");
	var edit_icon = document.getElementById("fmt_doedit");
	var space = document.getElementById("space");
	var edit_format = document.getElementById("customfmt");

	var value = output_select.options[output_select.selectedIndex].value;
	var format = fmts.getItem(value);

	if ( value == 'custom ...' ) { // custom is selected
		is_edit_format = 0;
		edit_block.style.display = '';
		space.style.display = '';
		edit_format.value = '';
		edit_icon.style.display = 'none';
		document.getElementById("fmt_delete").style.display = 'none';
	} else {
		edit_block.style.display = 'none';

		if ( format == value ) {
			space.style.display = '';
			edit_icon.style.display = 'none';
		} else {
			space.style.display = 'none';
			edit_icon.style.display = '';
		}
	}
}  // End of CustomOutputFormat

function EditCustomFormat () {
	
	var output_select = document.getElementById("output");
	var edit_block = document.getElementById("fmt_edit");
	var edit_format = document.getElementById("customfmt");


	var value = output_select.options[output_select.selectedIndex].value;
	var format = fmts.getItem(value);
	edit_format.value = format;
	edit_block.style.display = '';
	is_edit_format = 1;

	document.getElementById("fmt_delete").style.display = '';
	document.getElementById("fmt_save").value = value;


}  // End of EditCustomFormat

function DeleteOutputFormat () {
	
	var output_select = document.getElementById("output");
	var fmt_delete = document.getElementById("fmt_delete");

	var value = output_select.options[output_select.selectedIndex].value;
	var answer = confirm("Delete output format '" + value + "'?"); 
	if ( answer == false ) {
		form_ok = false;
	} else {
		form_ok = true;
		fmt_delete.value = value;
	}

}  // End of DeleteOutputFormat

function SaveOutputFormat() {

	if ( is_edit_format ) 
		return;

	var done = 0;

	var fmt_name = '';
	while ( !done ) {
		fmt_name = prompt("Save output format as", fmt_name);
		if ( fmt_name == null ) {
			done = 1;
			form_ok = false;
			continue;
		}

		if ( fmts.hasItem(fmt_name) ) {
			alert("Format name '" + fmt_name + "' already exists!");
			form_ok = false;
			continue;
		}

		if ( fmt_name == '' ) {
			alert("Select a format name first");
			form_ok = false;
			continue;
		}

		re = /[^A-Za-z0-9\-+_]+/;
		if ( re.test(fmt_name) ) {
			alert("Invalid characters in format name '" + fmt_name + "'");
			form_ok = false;
		} else {
			done=1;
			form_ok = true;
		}
	}

	document.getElementById("fmt_save").value = fmt_name;

} // End of SaveOutputFormat

function HandleFilter(mode) {
	var FilterSelect = document.getElementById("DefaultFilter");
	var value = FilterSelect.options[FilterSelect.selectedIndex].value;

	if ( mode == 0 ) { // Show/Hide edit icon
		if ( value == -1 ) {
			document.getElementById("filter_edit").style.display = 'none';
		} else {
			document.getElementById("filter_edit").style.display = '';
		}
		return;
	}

	if ( mode == 1 ) { // Enable edit of filter
		if ( value == -1 ) {
			return;
		}

		document.getElementById("filter_name").value = value;
		// prevent error message of possible garbage or errornous filter in text box
		document.getElementById("filter").value = '';
		done=1;
		form_ok = true;
		return;
	}

	if ( mode == 2 ) { // Save filter
		var done = 0;
		var length = document.getElementById("filter").value.length;
		if ( length == 0 ) {
			alert("Enter a filter first!");
			form_ok = false;
			return;
		} 

		var filter_name;
		if ( FilterSelect.selectedIndex > 0 )
			filter_name = value;
		else
			filter_name = '';

		while ( !done ) {
			form_ok = true;	
			if ( filter_name != '' ) {
				for (var i=0; i<DefaultFilters.length; i++ ) {
					var item = DefaultFilters[i];
					if ( item == filter_name ) {
						form_ok = confirm("Filter '" + filter_name + "' already exists! Overwrite this filter?");
					}
				}
				if ( !form_ok ) 
					filter_name = '';
			} 

			if ( filter_name == '' ) {
				filter_name = prompt("Save filter as");
				if ( filter_name == null ) {
					done = 1;
					form_ok = false;	
					filter_name = '';
				} else if ( filter_name == '' ) 
					alert("Select a filter name first");
				continue;
			}

			re = /[^A-Za-z0-9\-+_]+/;
			if ( re.test(filter_name) ) {
				alert("Invalid characters in filter name '" + filter_name + "'");
				filter_name = '';
				form_ok = false;	
				continue;
			} else {
				done=1;
				form_ok = true;
			}
		}
	
		if ( form_ok ) {
			document.getElementById("filter_name").value = filter_name;
			document.getElementById("FlowProcessingForm").submit();
		}
		return;
	}

	if ( mode == 3 && edit_filter != null ) { // Delete filter
		var answer = confirm("Delete default filter '" + edit_filter + "'?"); 
		if ( answer == false ) {
			form_ok = false;
		} else {
			form_ok = true;
			document.getElementById("filter_name").value = edit_filter;
			// prevent error message of possible garbage or errornous filter in text box
			document.getElementById("filter").value = '';
		}
		return;
	}

} // End of HandleFilter

/* 
 * functions for the lookup box. This is an extension to the hintbox and needs 
 * the functions specified in nfsen.css and global.js
 */

var lookupboxobj;

function lookup(lookup_string, obj, e){
	if ((ie||ns6) && document.getElementById("lookupbox")){
		lookupboxobj=document.getElementById("lookupbox")
		document.getElementById("cframe").src='lookup.php?lookup=' + lookup_string;

		lookupboxobj.style.left=lookupboxobj.style.top=-500;
		lookupboxobj.x=getposOffset(obj, "left");
		lookupboxobj.y=getposOffset(obj, "top");
		lookupboxobj.style.left=lookupboxobj.x-clearbrowseredge(obj, lookupboxobj, "rightedge")+obj.offsetWidth+"px";
		lookupboxobj.style.top=lookupboxobj.y-clearbrowseredge(obj, lookupboxobj, "bottomedge")+"px";
		lookupboxobj.style.visibility="visible";
	}
}

function hidelookup(e){
	lookupboxobj.style.visibility="hidden";
	lookupboxobj.style.left="-500px";
	document.getElementById("cframe").src='about:blank';
}

function ResetProcessingForm (){

	// clear sources
	var selector = document.getElementById("SourceSelector");
	var num_options = selector.length;
	for(var i=0; i<num_options; i++ ) {
		selector.options[i].selected = false;
	}

	// Reset default filter
	document.getElementById("DefaultFilter").selectedIndex = 0;

	// Clear filter
	document.getElementById("filter").value = '';

	if ( document.getElementById('modeselect0').checked == 1 ) {
		document.getElementById("listN").selectedIndex = 0;
		PresetAggregate(0);
		document.getElementById("timesorted").checked = 0;
	}
	if ( document.getElementById('modeselect1').checked == 1 ) {
		document.getElementById("TopN").selectedIndex = 0;
		document.getElementById("StatTypeSelector").selectedIndex = 0;
		document.getElementById("statorder").selectedIndex = 0;
		PresetAggregate(0);
		document.getElementById("limitoutput").checked = 0;
		document.getElementById("limitwhat").selectedIndex = 0;
		document.getElementById("limithow").selectedIndex = 0;
		document.getElementById("limitscale").selectedIndex = 0;
		document.getElementById("limitsize").value = 0;
	}
	document.getElementById("output").selectedIndex = 0;
	document.getElementById("IPv6_long").checked = 0;

} // End of ResetProcessingForm
