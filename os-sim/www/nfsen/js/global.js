/* nfsen global used javascript functions */

function GetCookieValue(varname) {

	var Cookie = document.cookie;
	if ( Cookie == null ) 
		return null;

//	alert("Cookie: " + document.cookie);

	cookie_vars = Cookie.split("; ");
	for (i=0; i<cookie_vars.length; i++) {
		if ( cookie_vars[i].split("=")[0] == varname ) {
			return cookie_vars[i].split("=")[1];
		}
	}

	return null;

} // End of GetCookieValue

function SetCookieValue(name, value) {

	document.cookie = name + "=" + value;

// alert("New cookie: " + document.cookie);

} // End of GetCookieValue

function Hash() {
	this.length = 0;
	this.items = new Array();
	for (var i = 0; i < arguments.length; i += 2) {
		if (typeof(arguments[i + 1]) != 'undefined') {
			this.items[arguments[i]] = arguments[i + 1];
			this.length++;
		}
	}
   
	this.removeItem = function(in_key)
	{
		var tmp_value;
		if (typeof(this.items[in_key]) != 'undefined') {
			this.length--;
			var tmp_value = this.items[in_key];
			delete this.items[in_key];
		}
	   
		return tmp_value;
	}

	this.getItem = function(in_key) {
		return this.items[in_key];
	}

	this.setItem = function(in_key, in_value)
	{
		if (typeof(in_value) != 'undefined') {
			if (typeof(this.items[in_key]) == 'undefined') {
				this.length++;
			}

			this.items[in_key] = in_value;
		}
	   
		return in_value;
	}

	this.hasItem = function(in_key)
	{
		return typeof(this.items[in_key]) != 'undefined';
	}
} // End of Hash

/* hit box functions */

var hintboxobj;
var horizontal_offset="9px" //horizontal offset of hint box from anchor link

var vertical_offset="0" //horizontal offset of hint box from anchor link. No need to change.
var ie=document.all;
var ns6=document.getElementById&&!document.all;

function getposOffset(what, offsettype){
	var totaloffset=(offsettype=="left")? what.offsetLeft : what.offsetTop;
	var parentEl=what.offsetParent;
	while (parentEl!=null){
		totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft : totaloffset+parentEl.offsetTop;
		parentEl=parentEl.offsetParent;
	}
	return totaloffset;
}

function iecompattest(){
	return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function clearbrowseredge(obj, boxobj, whichedge){
	var edgeoffset=(whichedge=="rightedge")? parseInt(horizontal_offset)*-1 : parseInt(vertical_offset)*-1;
	if (whichedge=="rightedge"){
		var windowedge=ie && !window.opera? iecompattest().scrollLeft+iecompattest().clientWidth-30 : window.pageXOffset+window.innerWidth-40;
		boxobj.contentmeasure=boxobj.offsetWidth;
		if (windowedge-boxobj.x < boxobj.contentmeasure)
			edgeoffset=boxobj.contentmeasure+obj.offsetWidth+parseInt(horizontal_offset);
	} else{
		var windowedge=ie && !window.opera? iecompattest().scrollTop+iecompattest().clientHeight-15 : window.pageYOffset+window.innerHeight-18;
		boxobj.contentmeasure=boxobj.offsetHeight;
		if (windowedge-boxobj.y < boxobj.contentmeasure)
			edgeoffset=boxobj.contentmeasure-obj.offsetHeight;
	}
	return edgeoffset;
}

function showhint(menucontents, obj, e, tipwidth){
	if ((ie||ns6) && document.getElementById("hintbox")){
		hintboxobj=document.getElementById("hintbox");
		hintboxobj.innerHTML=menucontents;;

		hintboxobj.style.left=hintboxobj.style.top=-500;
		if (tipwidth!=""){
			hintboxobj.widthobj=hintboxobj.style;
			hintboxobj.widthobj.width=tipwidth;
		}
		hintboxobj.x=getposOffset(obj, "left");
		hintboxobj.y=getposOffset(obj, "top");
		hintboxobj.style.left=hintboxobj.x-clearbrowseredge(obj, hintboxobj, "rightedge")+obj.offsetWidth+"px";
		hintboxobj.style.top=hintboxobj.y-clearbrowseredge(obj, hintboxobj, "bottomedge")+"px";
		hintboxobj.style.visibility="visible";
		obj.onmouseout=hidetip;
	}
}

function hidetip(e){
	hintboxobj.style.visibility="hidden";
	hintboxobj.style.left="-500px";
}

