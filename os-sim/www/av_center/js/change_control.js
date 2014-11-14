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

//Change control
var change_control = null;
        

function Change_control(new_config){
   
    //Private Attributes
    var config;
	
	//Events submit button
	var s_events;
	
    //Form Elements with changes
	var changed_elements;
    
    //Form Elements parsed
	var form_elements;
	
    //HTML elements ignored
	var e_ignored;
    
	//Change counter
	var counter;
			
	//Notification
	var nt_cc;
	
	_initialize(new_config);
		
		
	/******************************************************
	*****************  Public Functions  ******************
	*******************************************************/
		
    
    this.get_config = function() {
        //console.log(config);
        return config;
    };
	
	
	this.get_changes = function() {
        
        var changes   = (counter == 0) ? null : changed_elements;
        return [counter, changed_elements];
    };
    
    this.set_config = function (new_config) {
        _initialize(new_config);
    };
	
	this.change_control = function (){
		        
        $.each(form_elements, function(index, data){
			_bind_handler(index);
        });
        
       //console.log(form_elements);
    };
    
    this.reset = function (){
		
        //Form Elements with changes
        changed_elements = new Object;
        
        //Form Elements parsed
        form_elements = new Object();
        form_elements = _get_form_elements(config.elem.form_id);
		
		 $.each(form_elements, function(index, data){
			data.data = _get_values(index);
        });
             
       	counter   = 0;
                
        //Disable submit button		
		$('#'+config.elem.submit_id).attr('disabled', 'disabled');
		$('#'+config.elem.submit_id).off();
        
        if (counter < 1 && typeof(nt_cc) == 'object')
        {
            nt_cc.remove();
        }
   	};
	
	/******************************************************
	 *****************  Private Functions  *****************
	 *******************************************************/	
	
	function _initialize(new_config)
	{
		_set_config(new_config)		
		
		s_events    = new Array();
		_copy_events(config.elem.submit_id);
		
        //HTML elements ignored
		e_ignored = new Array();
		
		if (typeof(config.elem.ignored) == 'string' && config.elem.ignored != '')
        {
            var ids = config.elem.ignored.split(',')
			var size = ids.length;
            for (var i=0; i<size; i++)
            {
                e_ignored[ids[i].trim()] = true;
            }
        }       
		
        
        counter   = 0;
        
        //Form elements with changes
        changed_elements = new Object;
        
        //Form elements parsed
		form_elements = new Object();
        form_elements = _get_form_elements(config.elem.form_id);
        	                       
        $.each(form_elements, function(index, data){
			data.data = _get_values(index);
        });
       
		//Disable submit button		
		$('#'+config.elem.submit_id).attr('disabled', 'disabled');
		$('#'+config.elem.submit_id).off();
							
		nt_cc = null;
	};
       	
	function _check_config()
	{
		if (typeof(config.elem.form_id) == 'undefined' || config.elem.form_id == '')
		{
			var error = new Error();
				error.name    = 'TypeError';  
				error.message = "Configuration error: Option 'form id' is empty or undefined";  
			
			throw error;
		}
		
		if (typeof(config.elem.submit_id) == 'undefined' || config.elem.submit_id == '')
		{
			var error = new Error();
				error.name    = 'TypeError';  
				error.message = "Configuration error: Option 'submit id' is empty or undefined";  
			
			throw error;
		}
	}
	
	function _set_config(new_config){
        
		if (typeof(new_config) != 'object' || new_config == '')
		{					
			var default_config = {   
				elem : {
					form_id: ($('form').get(0)).id,
					submit_id :  $('#'+($('form').get(0)).id + ' input[type="submit"]').attr('id')
				},
				changes : {
					display_in: 'container_info',
					message: "You have made changes, click submit button to save these changes"
				}
            };
						
			config = default_config;
		}
		else{
			config = new_config;
		}
                		
		_check_config();
	};
    
    function _get_form_elements(form_id){
        
        var res      = new Object();
        var elements = $('input[type="radio"], input[type="checkbox"], input[type="text"],input[type="hidden"], input[type="password"], select, textarea', $('#'+form_id));
        
        $.each(elements, function(index){
			var id   = (typeof($(this).attr('id')) == 'undefined')   ? '' : $(this).attr('id');
            var name = (typeof($(this).attr('name')) == 'undefined') ? '' : $(this).attr('name');
			
						            
            if (id != '' && name != '' && typeof(e_ignored[id]) == 'undefined')
            {
               	var type = _get_type(name);
                res[id]  = new Object();
                res[id]['name']    = name;
                res[id]['type']    = type[0];
                res[id]['subtype'] = type[1];
                res[id]['data']    = null;
            }
		});
        
        return res;
    };
	
    function _get_type(name){
        
        /*
            Types : INPUT | SELECT | TEXTAREA
            Subtypes:
               INPUT    : TEXT | CHECKBOX | RADIO | HIDDEN | PASSWORD | BUTTON | SUBMIT | FILE | RESET
               SELECT   : SINGLE | MULTIPLE
               TEXTAREA : (empty)
        */        
       
        var elem = document.getElementsByName(name);
				
        var res    = new Array();
            res[0] = elem[0].tagName;
		
		if (res[0] == 'INPUT'){
			 res[1] = elem[0].type.toUpperCase();
        }
		else 
		{
			if (res[0] == 'SELECT')
			{
				res[1] = (elem[0].multiple) ? 'MULTIPLE' : 'SINGLE';
			}
			else if (res[0] == 'TEXTAREA')
			{
				res[1] = '';
			}
		}
        
        return res;
	};
		
	function _get_values(id)
	{
        var elem = form_elements[id];
        var v    = new Object();
		                
		if (elem.type == 'INPUT' && (elem.subtype == 'CHECKBOX' || elem.subtype == 'RADIO') )
		{
			v['values'] = ($('#'+id+':checked').length == 1) ? 1 : 0;
		}
        else if (elem.type == 'SELECT' && elem.subtype == 'MULTIPLE')
        {
            var values= $('#'+id).val();
            
			if (values == null)
			{
                v['values'] = null;
				v['length'] = 0;
            }
            else
			{            
				var cont = 0;
				v['values'] = new Object();
				$.each(values, function(index, data){
					v['values'][data] = data;
					cont++;
				});
				
				v['length'] = cont;
            }            
        }
        else
        {
           	v['values'] = $('#'+id).val();
        }
		
		return v;
	};
	
	function _bind_handler(id)
	{
		
        var elem = form_elements[id];	
        
		$('#'+id).off('keyup', _check_changes);
		$('#'+id).off('change', _check_changes);
		
		if (elem.type == 'INPUT')
		{
			if (elem.subtype == "RADIO" || elem.subtype == "CHECKBOX" || elem.subtype == "HIDDEN")
			{
				$('#'+id).change(function() { 
					_check_changes(id);
				}); 
			}
			else
			{
				$('#'+id).keyup(function() { 
					_check_changes(id);
				}); 
			}
		}
		else 
		{
			if (elem.type == 'SELECT')
			{
				$('#'+id).change(function() { 
					_check_changes(id);
				});
			}
			else if (elem.type == 'TEXTAREA')
			{
				$('#'+id).keyup(function() { 
					_check_changes(id);
				}); 
			}
		}
	}
		
	// True  -> Initial values and current values are differents
	// False -> Initial values and current values are equals
	function _is_changed(id)
	{
        var elem            = form_elements[id];
        var original_values = elem.data.values;
				                 
		if (elem.type == 'INPUT')
		{
			if (elem.subtype == "RADIO" || elem.subtype == "CHECKBOX")
			{
				var inputs = document.getElementsByName(elem.name);
				var size   = inputs.length;
				
				for (var i=0; i<size; i++)
				{
                    var o_checked = form_elements[inputs[i].id].data.values;
					var c_checked = ($('#'+inputs[i].id+':checked').length == 1) ? 1 : 0
					
					if (o_checked != c_checked){
						return true;
					}
				}
                
                return false;
			}
		}
		else if (elem.type == 'SELECT' && elem.subtype == 'MULTIPLE')
        {
            var selected_options = $('#'+id).val();
												                                                            
            if (selected_options != null && original_values != null)
            {
                if (selected_options.length != elem.data.length){
                    return true;
                }
                else
                {
                    for (var i in selected_options)
                    {
                        if (typeof(original_values[selected_options[i]]) == 'undefined')
                        {
                            return true;
                        }
                    }
                    return false;
                }
            }
            else
            {
                if (selected_options == null && original_values == null)
                {
                    return false;
                }
                
                return true;
            }
        }
        
        return ($('#'+id).val() != original_values);
        
	}
	
	function _check_changes(id)
	{
        var elem = form_elements[id];		
	
		if (_is_changed(id) == true )
		{
            if (typeof(changed_elements[elem.name]) == 'undefined')
			{
				changed_elements[elem.name] = true;
				counter++;
			}
        }
		else
		{
            if (typeof(changed_elements[elem.name]) != 'undefined')
			{
				delete changed_elements[elem.name]
				counter--;
                
            }
        }
        
        _execute_actions();
	};
	
	
	function _copy_events(id)
	{
		var event_array = $('#'+id).data('events');
						
		if (event_array != null)
		{
			// Log ALL handlers for ALL events:
			$.each(event_array, function(i, event){
				$.each(event, function(i, handler)
				{
					var type   = event[i].type;
					var data   = (typeof(event[i].data) == 'object') ? event[i].data : null;
					var h_func = handler['handler'];
					s_events[i] = new Array(type, data, h_func);
				});
			});
		}
	};
	
	function _bind_events(id){
		
		var length = s_events.length;
		
		for (var i=0; i<length; i++)
		{
			$('#'+id).on(s_events[i][0], s_events[i][1], s_events[i][2]);
		}
	};
    
    function _execute_actions(){
        		
        if (counter > 0)
        {
            //Activing submit button
			$('#'+config.elem.submit_id).removeAttr('disabled');			
			$('#'+config.elem.submit_id).off();
			_bind_events(config.elem.submit_id);
								
			if ($('#nt_cc').length < 1)
			{
				var config_nt = { 
					content: config.changes.message, 
					options: {
						type:'nf_info',
						cancel_button: true
					},
					style: 'display:none; width: 95%; margin: auto; padding: 1px 5px;'
				};
	
				nt_cc = new Notification('nt_cc',config_nt);
				
                //Show notification if no exists others notifications
                var cnd_1 = $('#'+config.changes.display_in+ ' div').hasClass('nf_success');
                var cnd_2 = $('#'+config.changes.display_in+ ' div').hasClass('nf_warning');
                var cnd_3 = $('#'+config.changes.display_in+ ' div').hasClass('nf_error');
                              
				if (!(cnd_1 || cnd_2 || cnd_3))
				{
					$('#'+config.changes.display_in).html(nt_cc.show());
					nt_cc.fade_in(1000);
				}
			}
	    }
        else
        {
			//Submit button			
			$('#'+config.elem.submit_id).attr('disabled', 'disabled');
			$('#'+config.elem.submit_id).off();
						
			if ($('#nt_cc').length >= 1)
			{
                $('#nt_cc').remove();
            }
        }
    };
}
	