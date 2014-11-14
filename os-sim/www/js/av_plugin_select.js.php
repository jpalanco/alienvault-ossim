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

var __av_plugin_ajax_url = "/ossim/asset_details/ajax/plugin_ajax.php";

(function($) 
{                     

    $.fn.AVplugin_select = function(o) 
    {
        
        o = $.extend(
        {
            model        : '',
            vendor       : '',
            version      : '',
            vendor_list  : {},
            model_list   : {},
            version_list : {},
            
            
        }, o || {});
    
        var container, row, td, model, vendor, version, trash;
        
        
        /* Overlay Layer */
        container = this;
        row       = $('<tr>', {}).appendTo(container);
       
        
        //Creating Vendor Select
        vendor = _new_select(o.vendor_list || {}, o.vendor, 'vendor');
        
        //Creating Model Select
        model = _new_select(o.model_list || {}, o.model, 'model');
    
        //Creating Version Select
        version = _new_select(o.version_list || {}, o.version, 'version');
        
        /* Creating trash icon*/
        /*
        td = _new_td();
        
        trash = $('<img>', 
        {
            "src"   : "/ossim/pixmaps/delete.png",
            "style" : "cursor:pointer",
            click   : function()
            {
                msg = "<?php echo _('Give me a nice message to confirm')?>";
                av_confirm(msg).done(_delete_plugin);
            }
        }).appendTo(td);
        */

        /*  FUNCTIONS */
        
        function _new_td()
        {
            return $('<td>', {}).appendTo(row);
        }
    
        function _new_select(list, selected, name)
        {
            td = _new_td();
            
            select = $('<select>', 
            {
                "class"     : "select_plugin " + name,
                "data-name" : name,
                change: function()
                {
                    if (name == 'vendor')
                    {
                        _load_model();
                    }
                    else if (name == 'model')
                    {
                        _load_version();
                    }
                }
                
            }).appendTo(td);
            
            _new_option('', '', select);
            
            try
            {
                $.each(list, function(_cpe, _name) 
                {                                
                    _new_option(_cpe, _name, select);
                    
                });
            }
            catch(Err){}
            
            _start_select(select);
            
            /* Vendor list does not duplicate the cpe type (o, h, a) so we need to figure out if there is a matching */
            if (name == 'vendor' && selected != '')
            {
                var s = selected;
                
                if (list[s] == undefined)
                {
                    cpes_v = ['a', 'h', 'o']
                    $.each(cpes_v, function(i, v)
                    {
                        s = s.replace(/(cpe:\/)\w:(.*)/, '$1'+ v +':$2')
                        
                        if (list[s] != undefined)
                        {
                            selected = s;
                            return false;
                        }
                        
                    });
                }
            }
            
            select.select2('val', selected);
            
            return select;
        }
        
        function _new_option(val, name, select)
        {
            $('<option>', 
            {
                "value" : val,
                "text"  : name
            }).appendTo(select);
            
            return true
        }
        
        function _start_select(elem)
        {
            var place_holder = '';
            var name = $(elem).data('name');
            
            if (name == 'vendor')
            {
                place_holder = "<?php echo _('Select Vendor') ?>";
            }
            else if (name == 'model')
            {
                place_holder = "<?php echo _('Select Model') ?>";
            }
            else if (name == 'version')
            {
                place_holder = "<?php echo _('Select Version') ?>";
            }
            
            $(elem).select2(
            {
                placeholder : place_holder,
                allowClear  : true
            });
            
        }
    
        function _restart_select(elem, start)
        {
            $(elem).select2("destroy");
            		
        	$(elem).empty();
        	
        	_new_option('','',elem)
        	
        	if (start == true)
        	{
                _start_select(elem);
            }
        }
    
    
        function _delete_plugin()
        {
            total = $(container).find('tr').length
            
            version.trigger('change');
            
            if (total > 1)
            {
                $(row).remove();
            }
            else
            {
                vendor.select2("val", "");
                _restart_select(model, true);
                _restart_select(version, true);
            }
    
        }
        
        
        /*  Function to change the combo boxes  */
        function _load_vendor()
        {
            var action = 'vendor_list';
            var data   = {};
            
            _load_elems(action, data, vendor);
        }
        
        /*  Function to change the combo boxes  */
        function _load_model()
        {   
            var v_val = vendor.val();
            
            if (v_val == '')
            {
                _restart_select(model, true);
                _restart_select(version, true);
            }
            else
            {
                var action = 'model_list';
                var data   = {};
    
                
                data['vendor'] = v_val
                
                _load_elems(action, data, model);
            }
        }
        
        /*  Function to change the combo boxes  */
        function _load_version()
        {
            var m_val = model.val();
            
            if (m_val == '')
            {
                _restart_select(version, true);
            }
            else
            {
                var action = 'version_list';
                var data   = {};

                data['model']  = m_val
                
                _load_elems(action, data, version)
            }
        }
        
                
        /*  Function to change the combo boxes  */
        function _load_elems(action, data, select)
        {
            var ctoken = Token.get_token("plugin_select");
            
        	$.ajax(
        	{
        		url: __av_plugin_ajax_url + "?token=" + ctoken,
        		data: { "action": action, "data": data },
        		type: "POST",
        		dataType: "json",
        		success: function(data)
        		{    		    
            		if (typeof data != 'undefined' && data != null)
            		{
                		if (data.error)
                		{
                    		av_plugin_notif(data.msg);
                    		
                    		return false;
                		}
                		
                		if (typeof data.data.items != 'undefined')
                		{
                            var items = data.data.items;
                            
                            _restart_select(select, false);
            
                            $.each(items, function(_cpe, _name) 
                            {
                                _new_option(_cpe, _name, select);
                            });
                            
                            _start_select(select);
                        }
        
                    }
        		},
        		error: function(XMLHttpRequest, textStatus, errorThrown) 
        		{	
                    //Checking expired session
            		var session = new Session(XMLHttpRequest, '');
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
                    
                    av_plugin_notif(errorThrown);
        		}
        	});
        }
    
    }

})(jQuery);



function _get_selected_plugins()
{
	var plugin_list = {};
	
	$('.plugin_list').each(function(j) 
	{
    	var asset   = $(this).data('host');
    	var plugins = {};
    	
    	$(this).find('tr').each(function(i)
    	{
            var vendor  = $('select.vendor', this).val() || '';
            var model   = $('select.model', this).val() || '';
            var version = $('select.version', this).val() || '';
            
            if (vendor + model + version != '')
            {
            
            }
            
            plugins[i] = {};
            
            plugins[i]['vendor']  = vendor
            plugins[i]['model']   = model
            plugins[i]['version'] = version
            
        });
        
        plugin_list[asset] = plugins;
        
    });
                
    return plugin_list;
}

function av_apply_plugin(plugin_callback, url)
{
    if (typeof url != 'undefined' && url != '')
    {
        var ajax_url = url;
    }
    else
    {
        var ajax_url = __av_plugin_ajax_url;
    }
    var plugin_list = _get_selected_plugins();
    
    var ctoken = Token.get_token("plugin_select");    
    
	$.ajax(
	{
		url: ajax_url + "?token=" + ctoken,
		data: {"action": "set_plugins", "data": { "plugin_list": plugin_list, "sensor": $('#default_sensor').val() } },
		type: "POST",
		dataType: "json",
		success: function(data)
		{    		    
    		if (typeof plugin_callback == 'function')
            {
                plugin_callback(data);
            }
            else
            {
                return false;
            }              
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) 
		{	
            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            av_plugin_notif(errorThrown);
            
		}
	});
}

function av_plugin_notif(msg)
{
    if ($('#av_plugin_notif').length == 0)
    {
        $('<div>', 
        {
            "id"    : 'av_plugin_notif',
            "style" : "position:absolute;top:8px;left:0;right:0;text-align:center"
            
        }).appendTo($('body'));
    }
    
            
    show_notification('av_plugin_notif', msg, 'nf_error', 7500, true);
    
    setTimeout(function()
    {
        $('#av_plugin_notif').remove();
        
    }, 7500);
}

