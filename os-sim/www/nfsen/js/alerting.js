var process_dialog   = true;
var HitCR = false;

function SetConditionRow(row_num) {

	var comp 			 = document.getElementById("comp_" + row_num );
	var comp_type 		 = document.getElementById("comp_type_" + row_num);
	var plus_label 		 = document.getElementById("plus-label_" + row_num);
	var minus_label 	 = document.getElementById("minus-label_" + row_num);
	var plusminus_label  = document.getElementById("plusminus-label_" + row_num);
	var scale 			 = document.getElementById("scale_" + row_num);
	if ( comp.value == 2 && comp_type.value == 0 ) {
		comp_type.value = 1;
	}
	if ( comp_type.value == 0 ) {
			plus_label.style.display 	  = 'none';
			minus_label.style.display 	  = 'none';
			plusminus_label.style.display = 'none';
			if ( comp.value == 2 ) 
				comp.value = 0;
			if ( scale.value == 5 )
				scale.value = 0;
			scale.options[5].disabled = 1;
	} else {
		scale.options[5].disabled = 0;
		switch (comp.value) {
			case '0':
				plus_label.style.display 	  = '';
				minus_label.style.display 	  = 'none';
				plusminus_label.style.display = 'none';
				break;
			case '1':
				plus_label.style.display 	  = 'none';
				minus_label.style.display 	  = '';
				plusminus_label.style.display = 'none';
				break;
			case '2':
				plus_label.style.display 	  = 'none';
				minus_label.style.display 	  = 'none';
				plusminus_label.style.display = '';
				break;
		}
	}
} // End of SetConditionRow

function SetupCondition(offset, which) {

	switch (which) {
		case 0:
			EnableDisableCondition(0, offset, 1);
			EnableDisableCondition(offset, 2*offset, 0);
			document.getElementById("plugin_condition_select").style.display = 'none';
			break;
		case 1:
			EnableDisableCondition(0, offset, 0);
			EnableDisableCondition(offset, 2*offset, 1);
			document.getElementById("plugin_condition_select").style.display = 'none';
			break;
		case 2:
			EnableDisableCondition(0, offset, 0);
			EnableDisableCondition(offset, 2*offset, 0);
			document.getElementById("plugin_condition_select").style.display = '';
			break;
	}

} // End of SetupCondition

function EnableDisableCondition (from, to, enabled) {

	// Make sure row 0 is enabled
	var row_num = from;
	if ( enabled )
		document.getElementById("visible_" + row_num).value = 1;

	for ( row_num = from; row_num < to; row_num++ ) {
		row = document.getElementById("row_" + row_num);
		if ( enabled ) {
			if ( document.getElementById("visible_" + row_num ).value > 0) 
				row.style.display = '';
			else
				row.style.display = 'none';
		} else {
			row.style.display = 'none';
		}
	}

} // End of EnableDisableCondition

function DeleteRow(row_num) {

	document.getElementById("row_" + row_num ).style.display = 'none';
	document.getElementById("visible_" + row_num ).value = 0;
	row_num--;
	document.getElementById("add_row_" + row_num ).style.display = '';

} // DeleteRow

function EnableRow(row_num) {

	document.getElementById("row_" + row_num ).style.display = '';
	document.getElementById("visible_" + row_num ).value = 1;
	row_num--;
	document.getElementById("add_row_" + row_num ).style.display = 'none';

} // EnableRow

function SetAction(action) {
	var checked = document.getElementById("action_" + action ).checked;

	if ( checked && action == 0 ) {
		// everything else off
		for ( var i=1; i<4; i++ ) {
			document.getElementById("action_" + i ).checked = 0;
		}
		document.getElementById("action_email").disabled 		 = 1;
		document.getElementById("action_subject").disabled 		 = 1;
		document.getElementById("action_system").disabled 		 = 1;
		document.getElementById("alert_plugin").disabled = 1;
	}
	if ( checked && action > 0 ) {
		// switch off 'no action'
		document.getElementById("action_0").checked = 0;
	}

	switch (action) {
		case 1:
			document.getElementById("action_email").disabled = checked ? 0 : 1;
			document.getElementById("action_subject").disabled = checked ? 0 : 1;
			break;
		case 2:
			document.getElementById("action_system").disabled = checked ? 0 : 10;
			break;
		case 3:
			document.getElementById("action_plugin").disabled = checked ? 0 : 1;
			break;
	}

	// make sure at least 'no action' is selected if nothing else
	checked = 0;
	for ( var i=1; i<4; i++ ) {
		if ( document.getElementById("action_" + i ).checked > 0 )
			checked = 1;
	}
	if ( checked == 0 ) {
		document.getElementById("action_0").checked = 1;
	}

} // End of SetAction

function EnableEdit(condition_plugins, action_plugins) {

	// Enable Filter Table
	document.getElementById("channellist").disabled = false;
	document.getElementById("filter").disabled = false;

	// radio buttons
	document.getElementById("type0").disabled = false;
	document.getElementById("type1").disabled = false;
	if ( condition_plugins > 0 )
		document.getElementById("type2").disabled = false;

	// Enable SumStatTable
	var i=0;
	while ( document.getElementById("row_" + i) ) {
		if ( document.getElementById("op_" + i) )
			document.getElementById("op_" + i).disabled = false;
		document.getElementById("type_" + i).disabled = false;
		document.getElementById("comp_" + i).disabled = false;
		document.getElementById("comp_type_" + i).disabled = false;
		document.getElementById("stat_type_" + i).disabled = false;
		document.getElementById("comp_value_" + i).disabled = false;
		document.getElementById("scale_" + i).disabled = false;
		
		if ( document.getElementById("visible_" + i ).value ) {
			var elem = document.getElementById("delete_row_" + i);
			if ( elem )
				elem.style.display = '';

			var next = document.getElementById("visible_" + (i+1) );
			if ( next && next.value == 0 ) {
				elem = document.getElementById("add_row_" + i);
				if ( elem ) 
					elem.style.display = '';
			}
		}
		i++;
	}

	// plugin condition table
	if ( condition_plugins > 0 )
		document.getElementById("plugin_condition").disabled = false;

	// trigger table
	document.getElementById("trigger_type").disabled = false;
	document.getElementById("trigger_number").disabled = false;
	document.getElementById("trigger_blocks").disabled = false;

	// action table
	for ( var i=0; i<3; i++ ) {
		document.getElementById("action_" + i ).disabled = 0;
	}
	if ( action_plugins > 0 )
		document.getElementById("action_3" ).disabled = 0;

	if ( document.getElementById("action_1").checked ) {
		document.getElementById("action_email").disabled = 0;
		document.getElementById("action_subject").disabled = 0;
	}
	if ( document.getElementById("action_2").checked ) {
		document.getElementById("action_system").disabled = 0;
	}
	if ( document.getElementById("action_3").checked ) {
		document.getElementById("action_plugin").disabled = 0;
	}
	// Controls
	document.getElementById("row_controls").style.display = '';
	document.getElementById("EventTable").style.display = 'none';


} // End of EnableEdit

function ConfirmDeleteAlert(alert) {
    answer =  confirm("Are you sure to delete alert: '" + alert + "'?");
    process_dialog = answer;
	if ( answer ) 
		SetAlertName(alert);
}

function SetAlertName(alert) {
	document.getElementById("alert").value = alert;
} // End of SetAlertName

function NoCRSubmit() {
	HitCR = true;
} // End of NoCRSubmit

function ProcessForm() {
	if ( HitCR == true ) {
		HitCR = false;
		return false;
	}

	if ( !process_dialog ) {
		process_dialog = true;
		return false;
	} else {
		return true;
	}

} // End of ProcessForm

function SetAlertStatus(type, alertname, arg) {
	document.getElementById("avg_frame").src="rrdgraph.php?cmd=get-alertgraph&alert=" + alertname + "&arg=" + type + "+" + arg;
	SetCookieValue("avg_type", type);
} // End of SetAlertStatus

function ChangeAVGtype ( elem ) {

} // End of ChangeAVGtype

