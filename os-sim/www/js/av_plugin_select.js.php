<?php
header("Content-type: text/javascript");

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

require_once 'av_init.php';

$init_error = FALSE;

$sensor = (GET('sensor') != '') ? GET('sensor') : 'local';

ossim_valid($sensor,  OSS_ALPHA, OSS_HEX, OSS_NULLABLE,  'illegal:' . _('Sensor'));

if (ossim_error())
{
    die(ossim_error());
}

/*
 * For performance and synchronicity, the Vendors list must be static.
 * Note that it must be reloaded when the sensor is manually changed.
 */

try
{
    $_vendor_list = Software::get_hardware_vendors($sensor);
}
catch (Exception $e)
{
    $_vendor_list = array();
    $init_error   = $e->getMessage();
}

?>

// Global variables
/* Note: presaved_data is used by asset_plugin_list.js.php dataTable object
 *       Must be global to store the changes in all dataTable pages
 *       And be able to use externally from edit_plugins.php
 */
var presaved_data = {};

function AVplugin_select()
{
    // Private variables
    var __vendor_list_cached = <?php echo json_encode($_vendor_list) ?>;
    var __av_plugin_ajax_url = "/ossim/av_asset/common/controllers/plugin_ajax.php";
    var __max_rows           = 10;
    var __sensor             = "<?php echo $sensor ?>";
    
    
    <?php
    if ($init_error != FALSE)
    {
    ?>
    _show_notif("<?php echo $init_error ?>");
    <?php
    }
    ?>
    
    
    /*
     * Constructor: creates into a table container the three select boxes
     *              with vendor/model/version to configure the plugins for
     *              an asset.
     *
     * @param  container   HTML Table object where the selectors will fit
     * @param  o           Options to initialize the plugins selected 
     */
    this.create = function(container, o, counter)
    {
        var text = ' <?php echo _('Plugin limit reached. You cannot add more than 10 plugins to an asset.') ?>';
        var is_limit = false;
        if (100-counter < __max_rows) {
            __max_rows = 100-counter;
            text = ' <?php echo _('Plugin limit reached. You cannot add more than 100 plugins to an sensor.') ?>';
        }
        o = $.extend(
       	    [{
            model        : '',
       	    vendor       : '',
            version      : '',
       	    model_list   : {},
            version_list : {},
            }], o || {});

        if (__max_rows > 0 || o.length > 1 || o[0].model != ":") {
            $.each(o, function(key, val)
            {
                _create_selectors_row(container, val);
            });
        } else {
            is_limit = true;
        }
        _new_add_button(container,text,is_limit);
        
        _refresh_buttons_status(container);
    }
        
        
        
    /*******************************************
    ****** HTML Object Creation Functions ****** 
    *******************************************/
    
    /*
     * This function creates one row of selectors vendor/model/version
     *
     * @param  container    HTML Table object where the selectors will fit
     * @param  o            Options to initialize the plugins selected
     */
    function _create_selectors_row(container, o)
    {
        /* Overlay Layer */
        var _row_container = $('<tr>', {}).appendTo(container);
        
        //Creating Vendor Select
        _new_select(_row_container, {}, o.vendor, 'vendor');
        
        //Creating Model Select
        _new_select(_row_container, o.model_list || {}, o.model, 'model');
    
        //Creating Version Select
        _new_select(_row_container, o.version_list || {}, o.version, 'version');
        
        //Creating +/- Button
        _new_remove_button(_row_container);
    }
    
    /*
     * This function creates one selector
     *
     * @param  row       HTML TR object where the selector will fit
     * @param  list      Options to initialize the select object
     * @param  selected  Option selected from 'list'
     * @param  name      Type of selector vendor/model/version
     */
    function _new_select(row, list, selected, name)
    {
        var td = $('<td>', {}).appendTo(row);
        var select = $('<select>', 
        {
            "class"     : "select_plugin " + name,
            "data-name" : name,
            change: function()
            {
                if (name == 'vendor')
                {
                    _load_model(this);
                    var version = $('select.version', row);
                    _restart_select(version, true);
                }
                else if (name == 'model')
                {
                    _load_version(this);
                }
                
                // Save changes when deleting selection or the last selector changed
                // Note that _load_*() methods will save changes into ajax success part
                if (name == 'version' || this.value == '')
                {
                    // Enable or Disable Add button
                    var _container = $(row).closest('table');
                    _refresh_buttons_status(_container);
                    
                    
                    _presave_changes(this);
                }
                
            }
            
        }).appendTo(td);
        
        _new_option('', '', select);
        
        
        // Vendor list is pre-loaded
        if (name == 'vendor')
        {
            list = __vendor_list_cached;
        }
        
        try
        {
            $.each(list, function(_cpe, _name) 
            {                                
                _new_option(_cpe, _name, select);
                
            });
        }
        catch(Err){}
        
        _start_select(select, list, selected);
        
    }
    
    
    /*
     * This function adds one option to a select box
     *
     * @param  val      Value attribute
     * @param  name     Text of the option
     * @param  select   HTML Select object
     */
    function _new_option(val, name, select)
    {
        $('<option>', 
        {
            "value" : val,
            "text"  : name
        }).appendTo(select);
        
        return true
    }
    
    
    /*
     * This function initializes a select box with the jQuery plugin 'select2'
     * Also, in case of 'vendor' selector, will fix a selection issue with some cpe
     *
     * @param  elem         HTML Select object
     * @param  list         Options to initialize selected [Case 'vendor' only]
     * @param  selected     Option selected                [Case 'vendor' only]
     */
    function _start_select(elem, list, selected)
    {
        var place_holder = '';
        var name = $(elem).data('name');
        
        if (name == 'vendor')
        {
            place_holder = "<?php echo Util::js_entities(_('Select Vendor')) ?>";
        }
        else if (name == 'model')
        {
            place_holder = "<?php echo Util::js_entities(_('Select Model')) ?>";
        }
        else if (name == 'version')
        {
            place_holder = "<?php echo Util::js_entities(_('Select Version')) ?>";
        }
        
        $(elem).select2(
        {
            placeholder : place_holder,
            allowClear  : true
        });
        
        
        if (name == 'vendor' && selected != '')
        {            
            /* Vendor list does not duplicate the cpe type (o, h, a) so we need to figure out if there is a matching */
            /* DEPRECATED
            var s = selected;
            
            if (typeof list[s] == 'undefined')
            {
                cpes_v = ['a', 'h', 'o'];
                
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
            */
        }
        
        
        $(elem).select2('val', selected);
        
        
        // Auto-select 'model' and 'version' when only ONE option is selectable
        if ((name == 'model' || name == 'version') && $('option', elem).length == 2)
        {
            selected = $('option:eq(1)', elem).val();
            $(elem).select2('val', selected);
            $(elem).trigger('change');
        }
    }

    
    /*
     * This function restart a previously created selector
     *
     * @param  elem    HTML Select object
     * @param  start   Flag to call or not _start_select()
     */
    function _restart_select(elem, start)
    {
        $(elem).select2("destroy");
        
        $(elem).empty();
        
        _new_option('','',elem)
        
        if (start == true)
        {
            _start_select(elem, {}, '');
        }
    }
    
    
    /*
     * This function adds a button to create a new row below
     *
     * @param  container         HTML TABLE Object
     */
    function _new_add_button(container, text, is_error)
    {
        var _tr = $('<tr>', {'id': 'add_button_row'}).appendTo(container);
        var _td = $('<td>', {'class': 'left', 'colspan': 3}).appendTo(_tr);
        $('<input>',
        {
            "class"     : "small av_b_secondary select2-add-button",
            "type"      : "button",
            "value"     : "<?php echo _('Add Plugin') ?>",
            "data-name" : "add",
            "disabled"  : true, // Enable or disable when vendor changes
            click       : function()
            {
                $(_tr).remove();
                
                var _options = {
                    model        : '',
                    vendor       : '',
                    version      : '',
                    model_list   : {},
                    version_list : {},
                }
                
                _create_selectors_row(container, _options);
                _new_add_button(container,text,false);
                _refresh_buttons_status(container);
            }
        }
        ).appendTo(_td);
        var span = $('<span>',
        {
            id  : 'add_button_msg',
            text: text,
            class: 'italic'
        }
        );
	span.appendTo(_td);
	if (is_error) {
		span.show();
	}
    }
    
    
    /**
    * This function refresh the enabled/disabled property of the buttons 'Add Plugin' and delete
    * It must be called when adding or deleting rows, and when vendor select changes...
    *
    * @param  container         HTML TABLE Object
    */
    function _refresh_buttons_status(container)
    {
        // Trash buttons
        if ($('tr', container).length <= 2)
        {
            $('.select2-remove-button', container).prop('disabled', true);
        }
        else
        {
            $('.select2-remove-button', container).prop('disabled', false);
        }
        
        
        var _is_disabled = false;
        
        // Add Plugin button
        $.each($('select.vendor, select.model, select.version', container), function(key, val)
        {
            if ($(val).val() == '')
            {
                _is_disabled = true;
            }
        });
        
        // Disable when reach row limit
        if ($('tr', container).length > __max_rows)
        {
            _is_disabled = true;
            
            $('#add_button_msg', container).show();
        }
        else
        {
            $('#add_button_msg', container).hide();
        }
        
        if (_is_disabled)
        {
            $('.select2-add-button', container).prop('disabled', true);
        }
        else
        {
            $('.select2-add-button', container).prop('disabled', false);
        }
    }
    
    
    /*
     * This function adds a button to remove the row
     *
     * @param  row   HTML TR Object
     */
    function _new_remove_button(row)
    {
        var td = $('<td>', {}).appendTo(row);
        $('<input>',
        {
            "class"     : "small select2-remove-button",
            "type"      : "button",
            "value"     : "",
            "data-name" : "del",
            click       : function(){ _delete_plugin_row(this); }
        }
        ).appendTo(td);
    }

    
    /*
     * This function removes a row with a plugin selection
     *
     * @param  clicked_button    To know if we have to remove or not
     */
    function _delete_plugin_row(clicked_button)
    {
        var _row   = $(clicked_button).closest('tr');
        var _table = $(clicked_button).closest('table');
        
        $(_row).remove();
        
        _refresh_buttons_status(_table);
        
        var _whatever_row_selector = $('.select_plugin', _table);
        _presave_changes(_whatever_row_selector);
    }
    
    
    
    /*
     * This function loads the model selector
     *
     * @param  _vendor   HTML Select Object with vendors
     */
    function _load_model(_vendor)
    {
        var vendor  = $(_vendor);
        var model   = vendor.closest('td').next().find('select');
        var version = model.closest('td').next().find('select');
        
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
    
    
    /*
     * This function loads the version selector
     *
     * @param  _model   HTML Select Object with models
     */
    function _load_version(_model)
    {
        var model   = $(_model);
        var version = model.closest('td').next().find('select');
        
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
        
            
    
    
    
    /*******************************************
    ******* Data Ajax Request Functions ******** 
    *******************************************/
    
    /*
     * This function request the plugin options to fill the selectors
     *
     * @param  action    Output data of the ajax response
     * @param  data      Some parameters to the provider
     * @param  select    HTML Select object
     */
    function _load_elems(action, data, select)
    {
        var ctoken = Token.get_token("plugin_select");
        
        data['sensor'] = __sensor;
        
        
        // Place the loading gif over the select, will be overwritten when request is done
        var _loading_gif = $('<div>', {class: 'select2-loading'});
        $(select).closest('td').find('div').append(_loading_gif);
        
        
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
                        _show_notif(data.msg);
                        
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
                        
                        _presave_changes(select);
                        
                        _start_select(select, items, '');
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
                
                _show_notif(errorThrown);
            }
        });
    }
        
        
        
        
        
        
    /*******************************************
    ********** Data Storage Functions ********** 
    *******************************************/
    
    /*
     * This function changes the scope of the plugins
     * Each sensor could be its own collection of plugins
     * Then when a sensor is changed, must reload the pre-loaded vendor list
     * If the selectors has been invoked from dataTable (aset_plugin_list.js.php)
     * Then we must redraw once the vendors has been reloaded
     *
     * @param  new_sensor                   New sensor selected
     * @param  _av_plugin_list  [Optional]  asset_plugin_list.js.php Object to redraw dataTable
     *
     */
    this.change_sensor = function(new_sensor, _av_plugin_list)
    {
        __sensor = new_sensor;
        
        
        // Must reload static vendor list for the new selected sensor
        
        var ctoken     = Token.get_token("plugin_select");
        var data       = {};
        data['sensor'] = __sensor;
        
        $.ajax(
        {
            url: __av_plugin_ajax_url + "?token=" + ctoken,
            data: { "action": "vendor_list", "data": data },
            type: "POST",
            dataType: "json",
            success: function(data)
            {                
                if (typeof data != 'undefined' && data != null)
                {
                    if (data.error)
                    {
                        _show_notif(data.msg);
                        
                        return false;
                    }
                    
                    if (typeof data.data.items != 'undefined')
                    {
                        __vendor_list_cached = data.data.items;
                        
                        if (typeof _av_plugin_list != 'undefined')
                        {
                            _av_plugin_list.dt_obj.fnDraw();
                        }
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
                
                _show_notif(errorThrown);
            }
        });
    }
    
    
    /*
     * This function saves the changes made to a selector
     * The change will be saved into 'presaved_data' array
     *
     * @param  box    HTML Select object
     */
    function _presave_changes(box)
    {
        var _table = $(box).closest('table');
        var _asset = _table.attr('data-asset_id');
        
        var _asset_plugins = [];
        
        $(_table).find('tr:not(#add_button_row)').each(function(i)
        {
            _asset_plugins[i] = {};
                
            _asset_plugins[i]['vendor']  = $('select.vendor', this).val() || '';
            _asset_plugins[i]['model']   = $('select.model', this).val() || '';
            _asset_plugins[i]['version'] = $('select.version', this).val() || '';
            
            var _mlist_arr = {};
            var _vlist_arr = {};
            
            $('select.model', this).each(function (i, op)
            {
                if ($(op).val != '')
                {
                    _mlist_arr[$(op).val()] = $(op).text();
                }
            });
            
             $('select.version', this).each(function (i, op)
            {
                if ($(op).val != '')
                {
                    _vlist_arr[$(op).val()] = $(op).text();
                }
            });
            
            var _mlist = JSON.stringify(_mlist_arr);
            var _vlist = JSON.stringify(_vlist_arr);
            
            _asset_plugins[i].mlist = _mlist;
            _asset_plugins[i].vlist = _vlist;
        });
            
        presaved_data[_asset] = _asset_plugins;
    }
    
    
    /*
     * This function saves the changes made to all selectors
     * The data is previously saved into 'presaved_data' array
     *
     * @param  plugin_callback    External function to execute when apply_changes is done
     * @param  url [Optional]     URL which the ajax request will go
     */
    this.apply_changes = function(plugin_callback, url)
    {
        if (typeof url != 'undefined' && url != '')
        {
            var ajax_url = url;
        }
        else
        {
            var ajax_url = __av_plugin_ajax_url;
        }
        
        var ctoken = Token.get_token("plugin_select");    
        
        $.ajax(
        {
            url: ajax_url + "?token=" + ctoken,
            data: {"action": "set_plugins", "data": { "plugin_list": presaved_data, "sensor": __sensor } },
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
                
                _show_notif(errorThrown);
                
            }
        });
    }
    
    
    
    
    
    
    
    /*******************************************
    ************** Util Functions ************** 
    *******************************************/
    
    /*
     * This function shows a notification message
     *
     * @param  msg    String with the notification message
     */
    function _show_notif(msg)
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
}
