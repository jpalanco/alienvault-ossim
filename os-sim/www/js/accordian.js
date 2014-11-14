/*
DezinerFolio.com Simple Accordians.

Author  : G.S.Navin Raj Kumar
Website : http://dezinerfolio.com

Modified by jmalbarracin 2009-04-02
Added option to collapse option if expanded
*/

/*
* The Variable names have been compressed to achive a higher level of compression.
*/

// Prototype Method to get the element based on ID
function ele(d){
	return document.getElementById(d);
}

// set or get the current display style of the div
function dsp(d,v){
	if(v==undefined){
		return d.style.display;
	}else{
		d.style.display=v;
	}
}

// set or get the height of a div.
function sh(d,v){
	// if you are getting the height then display must be block to return the absolute height
	if(v==undefined){
		if(dsp(d)!='none'&& dsp(d)!=''){
			return d.offsetHeight;
		}
		viz = d.style.visibility;
		d.style.visibility = 'hidden';
		o = dsp(d);
		dsp(d,'block');
		r = parseInt(d.offsetHeight);
		dsp(d,o);
		d.style.visibility = viz;
		return r;
	}else{
		d.style.height=v;
	}
}
/*
* Variable 'S' defines the speed of the accordian
* Variable 'T' defines the refresh rate of the accordian
*/
s=7;
t=10;
var af=false;

//Collapse Timer is triggered as a setInterval to reduce the height of the div exponentially.
function ct(d){
	d = ele(d);
	if(sh(d)>10){
		v = Math.round(sh(d)/d.s);
		v = (v<1) ? 1 :v ;
		v = (sh(d)-v);
		sh(d,v+'px');
		d.style.opacity = (v/d.maxh);
		d.style.filter= 'alpha(opacity='+(v*100/d.maxh)+');';
	}else{
		sh(d,0);
		dsp(d,'none');
		d.style.opacity = 1;
		d.style.filter= 'alpha(opacity=100);';
		clearInterval(d.t);
	}
}

//Expand Timer is triggered as a setInterval to increase the height of the div exponentially.
function et(d){
	d = ele(d);
	if(sh(d)<d.maxh){
		v = Math.round((d.maxh-sh(d))/d.s);
		v = (v<1) ? 1 :v ;
		v = (sh(d)+v);
		sh(d,v+'px');
		//d.style.opacity = (v/d.maxh);
		//d.style.filter= 'alpha(opacity='+(v*100/d.maxh)+');';
	}else{
		sh(d,d.maxh);
		clearInterval(d.t);
        // jump to default url when expand accordian
        if (typeof(d.getAttribute('url'))=='string')
            parent.frames['main'].document.location.href = d.getAttribute('url');
	}
}

// Collapse Initializer
function cl(d){
	if(dsp(d)=='block'){
		clearInterval(d.t);
		d.t=setInterval('ct("'+d.id+'")',t);
	}
}

//Expand Initializer
function ex(d){
	if(dsp(d)=='none'){
		dsp(d,'block');
		d.style.height='0px';
		clearInterval(d.t);
		d.t=setInterval('et("'+d.id+'")',t);
	}
}

// Removes Classname from the given div.
function cc(n,v){
	s=n.className.split(/\s+/);
	for(p=0;p<s.length;p++){
		if(s[p]==v+n.tc){
			s.splice(p,1);
			n.className=s.join(' ');
			//alert(n.className);
			break;
		}
	}
}
//Accordian Initializer
function Accordian(d,s,tc){
	// get all the elements that have id as content
	l=ele(d).getElementsByTagName('div');
	c=[];
	for(i=0;i<l.length;i++){
		h=l[i].id;
		if(h.substr(h.indexOf('-')+1,h.length)=='content'){c.push(h);}
	}
	sel=null;
	//then search through headers
	for(i=0;i<l.length;i++){
		h=l[i].id;
		if(h.substr(h.indexOf('-')+1,h.length)=='header'){
			d=ele(h.substr(0,h.indexOf('-'))+'-content');
			d.style.display='none';
			d.style.overflow='hidden';
			d.maxh =sh(d);
			d.s=(s==undefined)? 7 : s;
			h=ele(h);
			h.tc=tc;
			h.c=c;
			// set the onclick function for each header.
			h.onclick = function(){
				for(i=0;i<this.c.length;i++){
					cn=this.c[i];
					n=cn.substr(0,cn.indexOf('-'));
					if((n+'-header')==this.id){
						if (!af || (af && !hh(ele(n+'-header')))) {
							ex(ele(n+'-content'));
							n=ele(n+'-header');
							cc(n,'');
							n.className=n.className+' '+n.tc;
						}else{
							cl(ele(n+'-content'));
							cc(ele(n+'-header'),'');
						}
					}else{
						cl(ele(n+'-content'));
						cc(ele(n+'-header'),'');
					}
				}
			}
			if(h.className.match(/header_highlight/)!=undefined){ sel=h;}
		}
	}
	if(sel!=undefined){sel.onclick();af=true;}
}
function hh(d) {
	return (d.className.match(/header_highlight/)!=undefined)
}
