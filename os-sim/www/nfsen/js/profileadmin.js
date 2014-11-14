
// generic show/hide table row function 
function Table_set_toggle() {
	/* ShowHide method */
	var ShowHide = function() {
		this.style.display = ((this.style.display == '') ? 'none' : '');
		return false;
	}
	
	for (var oTable, a = 0; a < arguments.length; ++a) {
		oTable = document.getElementById(arguments[a]);
     	var r = 0, arrow, row, rows = oTable.rows;
     	while (row = rows.item(r++))
			row.toggle = ShowHide;
	}

	/* convenience function */
	self.ShowHide = function(id_num) {
		document.getElementById(id_num + i).toggle();
	}

} // End of Table_set_toggle

// Initialize all other show/hide elements in the profile stat table
function Init_ShowHide_profile()
{
	/* ShowHide method */
	var ShowHide = function() {
		this.style.display = ((this.style.display == '') ? 'none' : '');
		return false;
	}
	
	arrow_suffix = new Array("r", "d");
	done = 0;
	id   = 1;
	while ( done == 0 ) {
		channel      = "ch" + id;
		oTable = document.getElementById(channel);
		if ( oTable == null )
			done = 1;
		else {
			Table_set_toggle(channel);
     		var arrow;
			// initialize collaps/expand channel arrow
			for (i=0; i < arrow_suffix.length; i++ ) {
				arrow = document.getElementById(channel + arrow_suffix[i] ).toggle = ShowHide;
			}
			
			id++;
		}
	}

	// initialize collaps/expand all arrows	
	for (i=0; i < arrow_suffix.length; i++ ) {
		document.getElementById("all_" + arrow_suffix[i] ).toggle = ShowHide;
	}

	// setup edit max size and description table rows
	document.getElementById("ed_max_ro").toggle = ShowHide;
	document.getElementById("ed_max_rw").toggle = ShowHide;

	document.getElementById("ed_exp_ro").toggle = ShowHide;
	document.getElementById("ed_exp_rw").toggle = ShowHide;

	document.getElementById("ed_description_ro").toggle = ShowHide;
	document.getElementById("ed_description_rw").toggle = ShowHide;
	
	document.getElementById("ed_group_ro").toggle = ShowHide;
	document.getElementById("ed_group_rw").toggle = ShowHide;
	
	document.getElementById("ed_type_ro").toggle = ShowHide;
	document.getElementById("ed_type_rw").toggle = ShowHide;
	
	/* convenience function */
	self.ShowHide = function(id_num)
	{
		for ( i=1; i <= 3; i++ ) {
			document.getElementById(id_num + '_' + i).toggle();
		}
		// toggle arrows
		document.getElementById(id_num + "r").toggle();	// right arrow
		document.getElementById(id_num + "d").toggle();	// down arrow
	}
}

// called by the yellow channel arrows to show/hide all channels in the table
function ToggleAll(what) {
	done = 0;
	id   = 1;
	while ( done == 0 ) {
		channel = "ch" + id;
		element = document.getElementById(channel);
		if ( element == null ) {
			done = 1;
		} else {
			for ( i=1; i <= 3; i++ ) {
				element = document.getElementById(channel + '_' + i);
				element.style.display = ((what == 'hide') ? 'none' : '');
			}
			/// set show/hide of collapse/expand channel arrow
			element = document.getElementById(channel + "r");
			element.style.display = ((what == 'hide') ? '' : 'none');
			element = document.getElementById(channel + "d");
			element.style.display = ((what == 'hide') ? 'none' : '');			
		}
		id++;
	}
	
	// set show/hide of collapse/expand all arrow
	element = document.getElementById("all_r");
	element.style.display = ((what == 'hide') ? '' : 'none');
	element = document.getElementById("all_d");
	element.style.display = ((what == 'hide') ? 'none' : '');
	SetCookieValue("extended_channellist", what == 'hide' ? 0 : 1);
}

// Called by the edit icon to enable edit mode for the description, max size and expire values
function EnableEdit(element) 
{
	document.getElementById(element + "_ro").toggle(); 
	document.getElementById(element + "_rw").toggle();
}

var confirm_delete = 0;
var	abort_dialog   = 0;

// Called by the delete icon to delete a profile 
function ConfirmDeleteProfile(profile, profilegroup)
{
	var form = document.getElementById('profileform');
	answer =  confirm("Are you sure to delete profile: '" + profile + "' in profilegroup '" + profilegroup + "'?");
	confirm_delete = 0;
	return answer;
}

// Called by the cancel icon to cancel building a profile 
function ConfirmCancelBuild(profile, profilegroup)
{
	var form = document.getElementById('profileform');
	answer =  confirm("Cancel building profile: '" + profile + "' in profilegroup '" + profilegroup + "'?");
	confirm_delete = 0;
	return answer;
}


function GetXY(event) {
	if (!event)
		event = window.event;
	alert("x-Wert: " + event.screenX + " / y-Wert: " + event.screenY);
}

// Called by the Colour Selector. Depending on the entry, the corresponding colour selector page is called in a overlay window
function SelectColour() {
	var url;
	index = document.getElementById("colour_selector").selectedIndex;
	switch(index) {
		case 0:	// do nothing
			break;
		case 1:
			url = "colour_palette.html";
			loadwindow(url, 310, 178);
			break;
		case 2:
			url = "colour_picker.html";
			loadwindow(url, 290, 310);
			break;
	}
	
}

// if a colour was entered in the text field, adapt the background colour of the cell
function Validate_colour() {
	colour_entry = document.getElementById("colour");
	colour = colour_entry.value;
	re = /^#[0-9a-f]{6}$/i;
	if ( re.test(colour) ) {
		document.getElementById("colour_cell").style.backgroundColor = colour;
		return true;
	} else {
		alert("Invalid colour. Use syntax '#dddddd'");
		colour_entry.value = '#';
		colour_entry.focus();
		colour_entry.select();
		return false;
	}
} 

// dynamically open the frame and load the colour palette
function loadwindow(url,width,height){

	var dwindow = document.getElementById("dwindow")
	dwindow.style.display='';
	dwindow.style.width=width+"px";
	dwindow.style.height=height+"px";
	dwindow.style.left="100px";
	dwindow.style.top="100px";
	
	document.getElementById("colourframe").src=url;
}

// source selector/deselector function
function move(fbox_id, tbox_id ) {
	var arrFbox = new Array();
	var arrTbox = new Array();
	var arrLookup = new Array();
	var i;

	var fbox = document.getElementById(fbox_id);
	var tbox = document.getElementById(tbox_id);

	for (i = 0; i < tbox.options.length; i++) {
		arrLookup[tbox.options[i].text] = tbox.options[i].value;
		arrTbox[i] = tbox.options[i].text;
	}

	var fLength = 0;
	var tLength = arrTbox.length;

	for(i = 0; i < fbox.options.length; i++) {
		arrLookup[fbox.options[i].text] = fbox.options[i].value;
		if (fbox.options[i].selected && fbox.options[i].value != "") {
			arrTbox[tLength] = fbox.options[i].text;
			tLength++;
		} else {
			arrFbox[fLength] = fbox.options[i].text;
			fLength++;
   		}
	}

	arrFbox.sort();
	arrTbox.sort();
	fbox.length = 0;
	tbox.length = 0;
	var c;

	for(c = 0; c < arrFbox.length; c++) {
		var no = new Option();
		no.value = arrLookup[arrFbox[c]];
		no.text = arrFbox[c];
		fbox[c] = no;
	}

	for(c = 0; c < arrTbox.length; c++) {
		var no = new Option();
		no.value = arrLookup[arrTbox[c]];
		no.text = arrTbox[c];
		tbox[c] = no;
	}
}

// Limit possible orders in context of selected sign

var num_pos = 0;
var num_neg = 0;

function SetOrderSelector(init, preset) {
	var i, j;

	var orderSelector = document.getElementById('order_selector');
	var signSelector  = document.getElementById('sign_selector');

	// empty existing items
	for (i = orderSelector.options.length; i >= 0; i--) {
		orderSelector.options[i] = null; 
	}

	var which = signSelector.selectedIndex;
	if ( which == 0 ) {
		if ( !init ) {
			num_neg--;
			num_pos++;
		}
		j = num_pos;
	} else {
		if ( !init ) {
			num_neg++;
			num_pos--;
		}
		j = num_neg;
	}

	for (i = 0; i < j; i++) {
		orderSelector.options[i] = new Option(i+1);
		orderSelector.options[i].value = i+1;
	}

	orderSelector.options[preset-1].selected = true;
}

function ValidateEditForm() {

	// If user presses Cancel
	if ( abort_dialog )
		return true;

	if ( document.getElementById("name").value.length == 0 ) {
		alert("Enter a channel name first!");
		return false;
	}

	// select all sources to be set in POST request
	var SourceSelector = document.getElementById('channel_sources');

	if ( confirm_delete == 1 ) {
		channelname_element = document.getElementById('name');
		answer =  confirm("Are you sure to delete channel '" + channelname_element.value + "'");
		confirm_delete = 0;
		return answer;
	} 

	// Verify colour syntax in colour field
	if ( Validate_colour() == false )
		return false;

	// at least one element must be selected
	var num_items = SourceSelector.options.length;
/*
	for (i = 0; i < SourceSelector.options.length; i++) {
		if (SourceSelector.options[i].selected) 
			num_items--;
	}
*/
	if ( num_items == 0 ) {
		alert("At least 1 source must be selected");
		return false;
	}

	for (i=0; i < SourceSelector.options.length; i++) {
		SourceSelector.options[i].selected = true; 
	}

	return true;
} 

var cancelAction = false;

function ValidateNewprofileForm () {

	if ( cancelAction ) {
		return true;
	}

	var profile = document.getElementById('profile').value;

	if ( profile.length == 0 ) {
		alert("Enter a profile name first!");
		return false;
	}

	re = /[^A-Za-z0-9\-+_]+/;
	if ( re.test(profile) ) {
		alert("Invalid characters in profile name '" + profile + "'");
		return false;
	} 

	var groupselect = document.getElementById('groupselect');
	var inputbox 	= document.getElementById('profilegroup');
	var which = groupselect.selectedIndex;

	var profilegroup = groupselect[which].value;

	if ( profilegroup == 'New group ...' ) {
		profilegroup = inputbox.value;
	} 
	if ( profilegroup == '(nogroup)' ) {
		profilegroup = '.';
	} else if ( re.test(profilegroup) ) {
		alert("Invalid characters in group '" + profilegroup + "'");
		return false;
	} 

	document.getElementById('newprofileswitch').value = profilegroup + '/' + profile;

	return true;

} // End of ValidateNewprofileForm

/* called, when the type if the profile ('classic' or 'individual') is changed by clicking on
 * the radio buttons.
 * toggles the display status of the filter and source selector
 */
function ChannelWizard(type) {

	if ( type == "classic" ) {
		document.getElementById('select_sources_element').style.display = '';
		document.getElementById('filter_element').style.display = '';
	}
	if ( type == "individual" ) {
		document.getElementById('select_sources_element').style.display = 'none';
		document.getElementById('filter_element').style.display = 'none';
	}
} // ChannelWizard

function HandleGroupSelect() {

	var groupselect = document.getElementById('groupselect');
	var which = groupselect.selectedIndex;

	var group = groupselect[which].value;
	var inputbox = document.getElementById('profilegroup');
	inputbox.value = '';
	if ( group == 'New group ...' ) {
		inputbox.style.display = '';
	} else {
		inputbox.style.display = 'none';
	}
	// orderSelector.options[preset-1].selected = true;

} // End of HandleGroupSelect

function ConfirmNewType( current_type ) {
	var type_selector = document.profiletypeform.profile_type;
	var new_type = 0;
	for ( i=0; i<=3; i++ ) {
		if ( type_selector[i].checked ) 
			new_type = type_selector[i].value;
	}

	current_shadow 	= (current_type & 4) > 0;
	new_shadow 	 	= (new_type & 4) > 0;
	current_type 	= current_type & 3;
	new_type 	 	= new_type & 3;

	var text = '';
	// text = current_shadow + " " + new_shadow + " " + current_type + " " + new_type;

	if ( !current_shadow && new_shadow ) {
		text = "Please note:\nChanging the profile to a shadow profile will delete all profile data of this profile!";
	}
	if ( current_type == 2 && new_type == 1 ) {
		text = text + "\nChanging the profile to a history profile will stop profiling data for this profile";
	}
	if ( text != '' ) {
		text = text + "\nIs this ok?";
		return confirm(text);
	} else {
		return true;
	}

} // End of ConfirmNewType
