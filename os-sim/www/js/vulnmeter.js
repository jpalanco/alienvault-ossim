// Original:  Phil Webb (phil@philwebb.com) 
// Web Site:  http://www.philwebb.com 
// This script and many more are available free online at 
// The JavaScript Source!! http://javascript.internet.com

function updateProfilePlugins (profile) {

	$('#loading').show();

	$.ajax({
		type: 'POST',
		url: 'profiles_ajax.php',
		dataType: 'json',
		data: { type: 'plugins_available', sid: profile },
		success: function(msg) {
			$('#pavailable').html(msg.message);
			$('#pavailable').show();
			$('#loading').hide();
		},
		error: function(){
			$('#loading').hide();
		}
	});
    

}
function showPluginsByFamily(filter,profile){
    $("#updates_info").hide();
    
    if(filter!='Select Family') {
    var loading = '<img width="16" align="absmiddle" src="images/loading.gif">';
    $('#dplugins').html(loading);
        $.ajax({
                type: "GET",
                url: "get_plugins.php",
                data: { family: filter, sid: profile },
                success: function(msg) {
                    $('#dplugins').html(msg);
                    document.getElementById('tick1').style.display = 'block';
                    document.getElementById('tick2').style.display = 'none';
                    
                    document.getElementById('cve').style.display = 'block';
                    document.getElementById('cve').selected=true;
                    // $(".scriptinfo").simpletip({
                        // position: 'right',
                        // onBeforeShow: function() { 
                            // var id = this.getParent().attr('lid');
                            // this.load('lookup.php?id=' + id);
                        // }
                    // });
                }
        });
    }
}
function showPluginsByCVE(filter,profile){
    $("#updates_info").hide();

    if(filter!='Select CVE Id') {
        var loading = '<img width="16" align="absmiddle" src="images/loading.gif">';
        $('#dplugins').html(loading);
        $.ajax({
                type: "GET",
                url: "get_plugins.php",
                data: { cve: filter, sid: profile },
                success: function(msg) {
                    $('#dplugins').html(msg);
                    document.getElementById('tick1').style.display = 'none';
                    document.getElementById('tick2').style.display = 'block';
                    
                    document.getElementById('family').style.display = 'block';
                    document.getElementById('family').selected=true;
                    $(".scriptinfo").simpletip({
                        position: 'right',
                        onBeforeShow: function() { 
                            var id = this.getParent().attr('lid');
                            this.load('lookup.php?id=' + id);
                        }
                    });
                    $('.updatepluginsajax').bind('click', function() { 
                        $('#div_updateplugins').show();
                    });
                }
        });
    }
}
function confirmDelete(){
    return confirm("Are you sure you wish to delete this entry?");
}
function CheckEm(cbObj, Name, CheckValue ){
	var elems = document.getElementById(Name).getElementsByTagName("input");
	for(var i=0;i<elems.length;i++){
		if (elems[i].type=="checkbox") {
			elems[i].checked=CheckValue;
		}
	}
}
function CheckEmp(form, CheckValue ){
	var elems = form.getElementsByTagName("input");
	for(var i=0;i<elems.length;i++){
		if (elems[i].type=="checkbox") {
			elems[i].checked=CheckValue;
		}
	}
}
function showLayer(theSel, number) {
	// Hide last displayed form
    //alert(number);
	these_forms = new Array();
	these_forms[0] = 1;
	these_forms[1] = 2;
	these_forms[2] = 3;
	these_forms[3] = 4;
	these_forms[4] = 5;
	these_forms[5] = 6;
	these_forms[6] = 7;
	these_forms[7] = 8;
    
	for (var i = 0; i < these_forms.length; i++)
	{
		if (these_forms[i] != number)
		{
		if (document.getElementById(theSel + these_forms[i]).style.display == 'block')
		
			document.getElementById(theSel + these_forms[i]).style.display = 'none'
		
		}
	}
	
	// Show selected form
	
	if ( number != 0 ) {
		document.getElementById(theSel + number).style.display = 'block';
	}
	if ( theSel == 'idSched' && number > 2 ) {	
		document.getElementById('idSched2').style.display = 'block';
	}
    
    document.getElementById('days').style.display = 'none';
    document.getElementById('weeks').style.display = 'none';
    
	if ( theSel == 'idSched' && number == 2 ) {	 // to display "every day" option
		document.getElementById('idSched7').style.display = 'block';
        document.getElementById('days').style.display = '';
	}
    
	if ( theSel == 'idSched' && number == 4 ) {	 // to display "every week" option
		document.getElementById('idSched7').style.display = 'block';
        document.getElementById('weeks').style.display = '';
	}
    
    // to select a start date
    if (  theSel == 'idSched' && number == 2 || number == 4 || number == 5 || number == 6) {
		document.getElementById('idSched8').style.display = 'block';
    }
}

function move(fbox, tbox) {
   var arrFbox = new Array();
//   var arrTbox = new Array();
   var strTbox = "";
   var arrLookup = new Array();
   var i;
//   for (i = 0; i < tbox.options.length; i++) {
//      arrLookup[tbox.options[i].text] = tbox.options[i].value;
//      arrTbox[i] = tbox.options[i].text;
//   }
   var fLength = 0;
//   var tLength = arrTbox.length;
   for(i = 0; i < fbox.options.length; i++) {
      arrLookup[fbox.options[i].text] = fbox.options[i].value;
      if (fbox.options[i].selected && fbox.options[i].value != "") {
         if (tbox.value == "") {
            tbox.value = fbox.options[i].value;
         } else {
            tbox.value = tbox.value + ", " + fbox.options[i].value;
         }
         //arrTbox[tLength] = fbox.options[i].text;
         //tLength++;
      }
//      else {
//         arrFbox[fLength] = fbox.options[i].text;
//         fLength++;
//      }
   }
   //arrFbox.sort();
   //arrTbox.sort();
//   fbox.length = 0;
//   tbox.length = 0;
//   var c;
//   for(c = 0; c < arrFbox.length; c++) {
//      var no = new Option();
//      no.value = arrLookup[arrFbox[c]];
//      no.text = arrFbox[c];
//      fbox[c] = no;
//   }
//   for(c = 0; c < arrTbox.length; c++) {
//      var no = new Option();
//      no.value = arrLookup[arrTbox[c]];
//      no.text = arrTbox[c];
//      tbox[c] = no;
//   }
}
//  End 

function move2(fbox, tbox) {
   var arrFbox = new Array();
   var arrTbox = new Array();
   var arrLookup = new Array();
   var i;
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
      }
      else {
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

function selectAllOptions(selStr)
{
   var selObj = document.getElementById(selStr);
   for (var i=0; i<selObj.options.length; i++) {
      selObj.options[i].selected = true;
   }
}

function refreshParent(url) {
   if (url=="") {
      window.opener.location.href=window.opener.location.href;
   } else {
      window.opener.location.href=url;
   }
   self.close();
}

function popup(url,title) { 
   var newwindow = ''; 
   if (!newwindow.closed && newwindow.location) {
      newwindow.location.href = url;
   } else {
      newwindow=window.open(url,title,'height=600,width=800,resizable=yes,scrollbars=yes,toolbar=no,status=yes'); 
      if (!newwindow.opener) {newwindow.opener = self;}
   }
   if (window.focus) {newwindow.focus();}
   return false;

   //if (window.focus()) { 
   //   newwindow.focus(); 
   //} 
}

function OnSubmitForm()
{
   if(document.hostSearch.op[0].checked == true) {
      document.hostSearch.action ="hosts.php?op=view&ip="+document.hostSearch.host.value;
   } else if(document.hostSearch.op[1].checked == true) {
      document.hostSearch.action ="hosts.php?op=view&host="+document.hostSearch.host.value;
   }
   return true;
}

function showDiv(num, name, max)
{
    //starting at one, loop through until the number chosen by the user
    for(i = 0; i <= max; i++){
        var b = name + i;
        var style = 'none';
        //change visibility to block, or 'visible'
        if(i == num) {
            style = 'block';
        }
        document.getElementById(b).style.display = style;
    }
}

function showDivPlugins(num, name, max, hidden, max2)
{
    //starting at one, loop through until the number chosen by the user
    for(i = 0; i <= max; i++){
        var b = name + i;
        var style = 'none';
        //change visibility to block, or 'visible'
        if(i == num) { style = 'block' }
        document.getElementById(b).style.display = style;
    }
    for(i = 0; i <= max2; i++){
        var b = hidden + i;
        var style = 'none';
        document.getElementById(b).style.display = style;
    }
    if(name=='family') {
        document.getElementById('cve').style.display = 'block';
        document.getElementById('cve').selected=true;
        document.getElementById('tick1').style.display = 'block';
        document.getElementById('tick2').style.display = 'none';
   }
    if(name=='cve') {
        document.getElementById('family').style.display = 'block';
        document.getElementById('family').selected=true;
        document.getElementById('cve').style.display = 'none';
        document.getElementById('tick1').style.display = 'none';
        document.getElementById('tick2').style.display = 'block';
   }
}

// ajax function
function getPage(url, id)
  {
  var xmlHttp;
  try
    {
    // Firefox, Opera 8.0+, Safari
    xmlHttp=new XMLHttpRequest();
    }
  catch (e)
    {
    // Internet Explorer
    try
      {
      xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
      }
    catch (e)
      {
      try
        {
        xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
      catch (e)
        {
        alert("Your browser does not support AJAX!");
        return false;
        }
      }
    }
    xmlHttp.onreadystatechange=function()
      {
      if(xmlHttp.readyState==4)
        {
        document.getElementById(id).innerHTML=xmlHttp.responseText;
        }
      }
    xmlHttp.open("GET",url,true);
    xmlHttp.send(null);
}


//** Tab Content script v2.0- � Dynamic Drive DHTML code library (http://www.dynamicdrive.com)
//** Updated Oct 7th, 07 to version 2.0. Contains numerous improvements:
//   -Added Auto Mode: Script auto rotates the tabs based on an interval, until a tab is explicitly selected
//   -Ability to expand/contract arbitrary DIVs on the page as the tabbed content is expanded/ contracted
//   -Ability to dynamically select a tab either based on its position within its peers, or its ID attribute (give the target tab one 1st)
//   -Ability to set where the CSS classname "selected" get assigned- either to the target tab's link ("A"), or its parent container 

////NO NEED TO EDIT BELOW////////////////////////

function ddtabcontent(tabinterfaceid){
	this.tabinterfaceid=tabinterfaceid //ID of Tab Menu main container
	this.tabs=document.getElementById(tabinterfaceid).getElementsByTagName("a") //Get all tab links within container
	this.enabletabpersistence=true
	this.hottabspositions=[] //Array to store position of tabs that have a "rel" attr defined, relative to all tab links, within container
	this.subcontentids=[] //Array to store ids of the sub contents ("rel" attr values)
	this.revcontentids=[] //Array to store ids of arbitrary contents to expand/contact as well ("rev" attr values)
	this.selectedClassTarget="link" //keyword to indicate which target element to assign "selected" CSS class ("linkparent" or "link")
}

ddtabcontent.getCookie=function(Name){ 
	var re=new RegExp(Name+"=[^;]+", "i"); //construct RE to search for target name/value pair
	if (document.cookie.match(re)) //if cookie found
		return document.cookie.match(re)[0].split("=")[1] //return its value
	return ""
}

ddtabcontent.setCookie=function(name, value){
	document.cookie = name+"="+value+";path=/" //cookie value is domain wide (path=/)
}

ddtabcontent.prototype={

	expandit:function(tabid_or_position){ //PUBLIC function to select a tab either by its ID or position(int) within its peers
		this.cancelautorun() //stop auto cycling of tabs (if running)
		var tabref=""
		try{
			if (typeof tabid_or_position=="string" && document.getElementById(tabid_or_position).getAttribute("rel")) //if specified tab contains "rel" attr
				tabref=document.getElementById(tabid_or_position)
			else if (parseInt(tabid_or_position)!=NaN && this.tabs[tabid_or_position].getAttribute("rel")) //if specified tab contains "rel" attr
				tabref=this.tabs[tabid_or_position]
		}
		catch(err){alert("Invalid Tab ID or position entered!")}
		if (tabref!="") //if a valid tab is found based on function parameter
			this.expandtab(tabref) //expand this tab
	},

	setpersist:function(bool){ //PUBLIC function to toggle persistence feature
			this.enabletabpersistence=bool
	},

	setselectedClassTarget:function(objstr){ //PUBLIC function to set which target element to assign "selected" CSS class ("linkparent" or "link")
		this.selectedClassTarget=objstr || "link"
	},

	getselectedClassTarget:function(tabref){ //Returns target element to assign "selected" CSS class to
		return (this.selectedClassTarget==("linkparent".toLowerCase()))? tabref.parentNode : tabref
	},

	expandtab:function(tabref){
		var subcontentid=tabref.getAttribute("rel") //Get id of subcontent to expand
		//Get "rev" attr as a string of IDs in the format ",john,george,trey,etc," to easily search through
		var associatedrevids=(tabref.getAttribute("rev"))? ","+tabref.getAttribute("rev").replace(/\s+/, "")+"," : ""
		this.expandsubcontent(subcontentid)
		this.expandrevcontent(associatedrevids)
		for (var i=0; i<this.tabs.length; i++){ //Loop through all tabs, and assign only the selected tab the CSS class "selected"
			this.getselectedClassTarget(this.tabs[i]).className=(this.tabs[i].getAttribute("rel")==subcontentid)? "selected" : ""
		}
		if (this.enabletabpersistence) //if persistence enabled, save selected tab position(int) relative to its peers
			ddtabcontent.setCookie(this.tabinterfaceid, tabref.tabposition)
	},

	expandsubcontent:function(subcontentid){
		for (var i=0; i<this.subcontentids.length; i++){
			var subcontent=document.getElementById(this.subcontentids[i]) //cache current subcontent obj (in for loop)
			subcontent.style.display=(subcontent.id==subcontentid)? "block" : "none" //"show" or hide sub content based on matching id attr value
		}
	},


	expandrevcontent:function(associatedrevids){
		var allrevids=this.revcontentids
		for (var i=0; i<allrevids.length; i++){ //Loop through rev attributes for all tabs in this tab interface
			//if any values stored within associatedrevids matches one within allrevids, expand that DIV, otherwise, contract it
			document.getElementById(allrevids[i]).style.display=(associatedrevids.indexOf(","+allrevids[i]+",")!=-1)? "block" : "none"
		}
	},

	autorun:function(){ //function to auto cycle through and select tabs based on a set interval
		var currentTabIndex=this.automode_currentTabIndex //index within this.hottabspositions to begin
		var hottabspositions=this.hottabspositions //Array containing position numbers of "hot" tabs (those with a "rel" attr)
		this.expandtab(this.tabs[hottabspositions[currentTabIndex]])
		this.automode_currentTabIndex=(currentTabIndex<hottabspositions.length-1)? currentTabIndex+1 : 0 //increment currentTabIndex
	},

	cancelautorun:function(){
		if (typeof this.autoruntimer!="undefined")
			clearInterval(this.autoruntimer)
	},

	init:function(automodeperiod){
		var persistedtab=ddtabcontent.getCookie(this.tabinterfaceid) //get position of persisted tab (applicable if persistence is enabled)
		var persisterror=true //Bool variable to check whether persisted tab position is valid (can become invalid if user has modified tab structure)
		this.automodeperiod=automodeperiod || 0
		for (var i=0; i<this.tabs.length; i++){
			this.tabs[i].tabposition=i //remember position of tab relative to its peers
			if (this.tabs[i].getAttribute("rel")){
				var tabinstance=this
				this.hottabspositions[this.hottabspositions.length]=i //store position of "hot" tab ("rel" attr defined) relative to its peers
				this.subcontentids[this.subcontentids.length]=this.tabs[i].getAttribute("rel") //store id of sub content ("rel" attr value)
				this.tabs[i].onclick=function(){
					tabinstance.expandtab(this)
					tabinstance.cancelautorun() //stop auto cycling of tabs (if running)
					return false
				}
				if (this.tabs[i].getAttribute("rev")){ //if "rev" attr defined, store each value within "rev" as an array element
					this.revcontentids=this.revcontentids.concat(this.tabs[i].getAttribute("rev").split(/\s*,\s*/))
				}
				if (this.enabletabpersistence && parseInt(persistedtab)==i || !this.enabletabpersistence && this.getselectedClassTarget(this.tabs[i]).className=="selected"){
					this.expandtab(this.tabs[i]) //expand current tab if it's the persisted tab, or if persist=off, carries the "selected" CSS class
					persisterror=false //Persisted tab (if applicable) was found, so set "persisterror" to false
					//If currently selected tab's index(i) is greater than 0, this means its not the 1st tab, so set the tab to begin in automode to 1st tab:
					this.automode_currentTabIndex=(i>0)? 0 : 1
				}
			}
		} //END for loop
		if (persisterror) //if an error has occured while trying to retrieve persisted tab (based on its position within its peers)
			this.expandtab(this.tabs[this.hottabspositions[0]]) //Just select first tab that contains a "rel" attr
		if (parseInt(this.automodeperiod)>500 && this.hottabspositions.length>1){
			this.automode_currentTabIndex=this.automode_currentTabIndex || 0
			this.autoruntimer=setInterval(function(){tabinstance.autorun()}, this.automodeperiod)
		}
	} //END int() function

} //END Prototype assignment
