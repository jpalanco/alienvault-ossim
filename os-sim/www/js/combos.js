	//
	// COMBO functions
	//
	// delete selected options in a combo
	function deletefrom (mysel) {
		var delems = [];
		var myselect=document.getElementById(mysel);
		
		if ( typeof(myselect) != 'undefined' && myselect != null )
		{
			for (var i=0; i<myselect.options.length; i++){
				if (myselect.options[i].selected==true) {
					delems.push(i);
					myselect.options[i].selected=false;
				}
			}
			
			for (var i=delems.length-1; i>=0; i--){
				myselect.remove(delems[i])
			}
		}
	}
	// delete all options in a combo
	function deleteall (mysel) {
		var delems = [];
		var myselect=document.getElementById(mysel);
		for (var i=0; i<myselect.options.length; i++)
			delems.push(i);
		for (var i=delems.length-1; i>=0; i--)
			myselect.remove(delems[i]);
	}
	// add element to a combo
	function addto (mysel,txt,val, flag) {
		if(typeof(flag) == undefined) flag = false;
		if (val==null) val=txt
		if (!exists_in_combo(mysel,txt,val, flag)) {
			var elOptNew = document.createElement('option');
			elOptNew.text = txt
			elOptNew.value = val
			try {
				document.getElementById(mysel).add(elOptNew, null); // standards compliant; doesn't work in IE
			}
			catch(ex) {
				document.getElementById(mysel).add(elOptNew); // IE only
			}
		}
	}
	// exist txt,val in combo mysel
	function exists_in_combo(mysel,txt,val, flag) {
		if(typeof(flag) == undefined) flag = false;
		var myselect=document.getElementById(mysel)
		for (var i=0; i<myselect.options.length; i++){
			if(flag){
				if (myselect.options[i].value==val)
					return true;				
			}else{
				if (myselect.options[i].value==val && myselect.options[i].text==txt)
					return true;				
			}
					
		}
		
		return false;
	}
	// delete option if txt,val exists in combo mysel
	function deletevaluefrom(mysel,txt,val) {
		var delems = [];
		var myselect=document.getElementById(mysel);
		for (var i=0; i<myselect.options.length; i++)
			if (myselect.options[i].value==val && myselect.options[i].text==txt)
				delems.push(i);
		for (var i=delems.length-1; i>=0; i--)
			myselect.remove(delems[i]);
	}	
	// select all elements of a multiselect combo
	function selectall (mysel) {
		var myselect=document.getElementById(mysel);
		
		if ( typeof(myselect) != 'undefined' && myselect != null )
		{
			for (var i=0; i<myselect.options.length; i++){
				myselect.options[i].selected=true;
			}
		}
	}
	// return all combo elements
	function getcombotext (mysel) {
		var elems = [];
		var myselect=document.getElementById(mysel);
		for (var i=0; i<myselect.options.length; i++)
			elems.push(myselect.options[i].text);
		return elems;
	}
	// return all selected combo elements by text
	function getselectedcombotext (mysel) {
		var elems = [];
		var myselect=document.getElementById(mysel);
		for (var i=0; i<myselect.options.length; i++)
			if (myselect.options[i].selected==true)
				elems.push(myselect.options[i].text);
		return elems;
	}
	// return all selected combo elements by value
	function getselectedcombovalue (mysel) {
		var elems = [];
		var myselect=document.getElementById(mysel);
		for (var i=0; i<myselect.options.length; i++)
			if (myselect.options[i].selected==true)
				elems.push(myselect.options[i].value);
		return elems;
	}
