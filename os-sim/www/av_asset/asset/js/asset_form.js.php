<?php
header('Content-type: text/javascript');

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
?>


/**********************************************************************
 ******************  MANAGE TABS FOR ASSET EDITION  *******************
 **********************************************************************/

function Av_tabs_asset_edition(tab_config)
{
    //Private variables
    var __self = this;

    var __cfg = <?php echo Asset::get_path_url()?>;


    //Public methods
    this.load_general_callback = function()
    {
        // Get asset information
        var __ajax_options = this.ajax_options.data;

        $.ajax({
            type: "POST",
            url: __cfg.asset.providers + "get_asset_info.php",
            data: __ajax_options,
            dataType: "json",
            error: function(xhr){

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var __error_msg = '<?php echo _("Sorry, asset data was not loaded due to an error when processing the request")?>';

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                show_error_loading_data(__self.id, __error_msg);

                __self.show_selected_tab();
            },
            success: function(data){

                //Asset data
                var asset_data = data.data;

                //Form configuration
                var form_options = {
                    'type' : 'single',
                    'controllers':
                    {
                        'submit_form' : __cfg.asset.controllers + 'save_asset.php',
                        'upload_icon' : __cfg.asset.controllers + 'asset_actions.php'
                    },
                    'providers':
                    {
                        'autocomplete' : __cfg.asset.providers + 'search_cpe.php'
                    },
                    'asset_data' : asset_data
                };

                if (form_options.type == 'single' && asset_data.is_editable == 'no')
                {
                    show_warning_no_editable('av_tab_info', asset_data.ctx.related_server);

                    $("#asset_form input").addClass('disabled').attr("disabled", "disabled");
                    $("#asset_form select").addClass('disabled').attr("disabled", "disabled");
                    $("#asset_form textarea").addClass('disabled').attr("disabled", "disabled");
                }


                //Load asset data in form
                $('#tg_asset_id').val(asset_data.id);
                $('#ctx').val(asset_data.ctx.id);
                $('#asset_name').val(asset_data.name);


                if (asset_data.icon)
                {
                    $('#td_icon').html(asset_data.icon);
                }
                else
                {
                    $('#c_remove_icon').empty();
                }

                $('#asset_ip').val(asset_data.ip);

                if (asset_data.is_editable == 'no_ip')
                {
                    $('#asset_text_ip').val(asset_data.ip);
                }

                $('#fqdns').val(asset_data.fqdns);

                $('#asset_value').val(asset_data.asset_value);

                if (asset_data.external == 1)
                {
                    $('#external_yes').prop('checked', true);
                }
                else
                {
                    $('#external_no').prop('checked', true);
                }

                if (asset_data.location.lat && asset_data.location.lon)
                {
                    $('#latitude').val(asset_data.location.lat);
                    $('#longitude').val(asset_data.location.lon);
                    $('#zoom').val(asset_data.location.zoom);
                }

                $('#descr').val(asset_data.descr);

                $('#os').val(asset_data.os);

                $('#model').val(asset_data.model);


                $.each(asset_data.devices, function(d_value, d_text)
                {
                    $('#devices').append("<option value=" + d_value + ">" + d_text + "</option>");
                });

                if (Object.keys(asset_data.sensors).length > 0)
                {
                    $('.sensor_check').prop('checked', false);

                    $.each(asset_data.sensors, function(sensor_id, s_data)
                    {
                        $("input:checkbox[value=" + sensor_id + "]").prop("checked", true);
                    });
                }

                //Load handlers
                load_asset_form_handlers(form_options);

                __self.show_selected_tab();
            }
        });
    };


    this.load_properties_callback = function()
    {
        var asset_data = this.ajax_options.data;

        // Form configuration
        var form_options = {
            'type' : 'single',
            'controllers':
            {
                'submit_form' : __cfg.asset.controllers + 'asset_actions.php'
            },
            'providers':
            {
                'datatable' : __cfg.common.providers + 'dt_properties.php'
            },
            'asset_data' : asset_data
        };

        if (form_options.type == 'single' && asset_data.is_editable == 'no')
        {
            show_warning_no_editable('av_tab_info', asset_data.ctx.related_server);
        }


        //Load asset data in form
        $('#tp_asset_id').val(asset_data.id);
        $('#tp_current_action').val('new_property');


        //Load handlers
        load_property_form_handlers(form_options);

        __self.show_selected_tab();
    };


    this.load_software_callback = function()
    {
        var asset_data = this.ajax_options.data;

        // Form configuration
        var form_options = {
            'type' : 'single',
            'controllers':
            {
                'submit_form' : __cfg.asset.controllers + 'asset_actions.php'
            },
            'providers':
            {
                'autocomplete' : __cfg.asset.providers + 'search_cpe.php',
                'datatable'    : __cfg.common.providers + 'dt_software.php'
            },
            'asset_data' : asset_data
        };

        if (form_options.type == 'single' && asset_data.is_editable == 'no')
        {
            show_warning_no_editable('av_tab_info', asset_data.ctx.related_server);
        }


        //Load asset data in form
        $('#tsw_asset_id').val(asset_data.id);
        $('#tsw_property_id').val(60);
        $('#tsw_current_action').val('new_software');

        //Load handlers
        load_software_form_handlers(form_options);

        __self.show_selected_tab();
    };


    this.load_services_callback = function()
    {
        var asset_data = this.ajax_options.data;

        // Form configuration
        var form_options = {
            'type' : 'single',
            'controllers' : {
                'submit_form' : __cfg.asset.controllers + 'asset_actions.php'
            },
            'providers' : {
                'datatable' : __cfg.common.providers + 'dt_services.php'
            },
            'asset_data' : asset_data
        };

        if (form_options.type == 'single' && asset_data.is_editable == 'no')
        {
            show_warning_no_editable('av_tab_info', asset_data.ctx.related_server);
        }


        //Load asset data in form
        $('#ts_asset_id').val(asset_data.id);
        $('#ts_property_id').val(40);
        $('#ts_current_action').val('new_service');


        //Load handlers
        load_service_form_handlers(form_options);

        __self.show_selected_tab();
    };


    //Tab configuration
    var tabs =
    {
        "id"       : tab_config.id,
        "selected" : tab_config.selected,
        "hide"     : tab_config.hide,
        "tabs"     : {
            "0" :
            {
                "id"   : "tab_general",
                "name" : "<?php echo Util::js_entities(_('General'))?>",
                "href" : __cfg.asset.templates + "form/tpl_tab_general.php",
                "ajax_options" : {
                    "data" : tab_config.asset_options
                },
                "load_callback" : this.load_general_callback
            },
            "1" :
            {
                "id"   : "tab_properties",
                "name" : "<?php echo Util::js_entities(_('Properties'))?>",
                "href" : __cfg.asset.templates + "form/tpl_tab_properties.php",
                "ajax_options" : {
                    "data" : tab_config.asset_options
                },
                "load_callback" : this.load_properties_callback
            },
            "2" :
            {
                "id"   : "tab_software",
                "name" : "<?php echo Util::js_entities(_('Software'))?>",
                "href" : __cfg.asset.templates + "form/tpl_tab_software.php",
                "ajax_options" : {
                    "data" : tab_config.asset_options
                },
                "load_callback" : this.load_software_callback
            },
            "3" :
            {
                "id"   : "tab_services",
                "name" : "<?php echo Util::js_entities(_('Services'))?>",
                "href" : __cfg.asset.templates + "form/tpl_tab_services.php",
                "hide" : true,
                "ajax_options" : {
                    "data" : tab_config.asset_options
                },
                "load_callback" : this.load_services_callback
            }
        }
    };


    //Extend functionality. Call parent object
    Av_tabs.apply(this, [tabs]);
};




/*********************************************************************
 ***************  MANAGE TABS FOR ASSET BULK EDITION  ****************
 *********************************************************************/

function Av_tabs_asset_bulk_edition(tab_config)
{
    //Private variables
    var __self = this;

    var __cfg = <?php echo Asset::get_path_url()?>;


    //Public methods
    this.load_general_callback = function()
    {
        // Get asset information
        var __ajax_options = this.ajax_options.data;

        $.ajax({
            type: "POST",
            url: __cfg.asset.providers + "bk_get_assets_info.php",
            data: __ajax_options,
            dataType: "json",
            error: function(xhr){

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var __error_msg = '<?php echo _("Sorry, asset data was not loaded due to an error when processing the request")?>';

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                show_error_loading_data(__self.id, __error_msg);

                __self.show_selected_tab();
            },
            success: function(data){

                //Asset data
                var asset_data = data.data;

                //Form configuration
                var form_options = {
                    'type' : 'bulk',
                    'controllers':
                    {
                        'submit_form' : __cfg.asset.controllers + 'bk_save_assets.php',
                        'upload_icon' : __cfg.asset.controllers + 'bk_asset_actions.php'
                    },
                    'providers':
                    {
                        'autocomplete' : __cfg.asset.providers + 'search_cpe.php'
                    },
                    'asset_data' : asset_data
                };


                /* Asset Type (Only for Bulk Edition) */
                if (typeof(asset_data.asset_type) != 'undefined')
                {
                    $('#tg_asset_type').val(asset_data.asset_type);
                }

                $('#c_remove_icon').empty();

                //Set CTX
                if (typeof(asset_data.ctx) != 'undefined')
                {
                    $('#ctx').val(asset_data.ctx.id);
                }
                else
                {
                    //No common CTX, remove sensor section
                    $('.tr_sensors').remove();
                }

                //Load handlers
                load_asset_form_handlers(form_options);

                __self.show_selected_tab();
            }
        });
    };


    this.load_properties_callback = function()
    {
        // Form configuration
        var form_options = {
            'type' : 'bulk',
            'controllers':
            {
                'submit_form' : __cfg.asset.controllers + 'bk_asset_actions.php'
            },
            'providers':
            {
                'datatable' : __cfg.asset.providers + 'bk_dt_properties.php'
            }
        };

        //Load handlers
        load_property_form_handlers(form_options);

        __self.show_selected_tab();
    };


    this.load_software_callback = function()
    {
        // Form configuration
        var form_options = {
            'type' : 'bulk',
            'controllers':
            {
                'submit_form' : __cfg.asset.controllers + 'bk_asset_actions.php'
            },
            'providers':
            {
                'autocomplete' : __cfg.asset.providers + 'search_cpe.php',
                'datatable'    : __cfg.asset.providers + 'bk_dt_software.php'
            }
        };

        //Load handlers
        $('#tsw_property_id').val(60);
        $('#tsw_current_action').val('new_software');

        load_software_form_handlers(form_options);

        __self.show_selected_tab();
    };


    //Tab configuration
    var tabs =
    {
        "id"       : tab_config.id,
        "selected" : tab_config.selected,
        "hide"     : tab_config.hide,
        "tabs"     : {
            "0" :
            {
                "id"   : "tab_general",
                "name" : "<?php echo Util::js_entities(_('General'))?>",
                "href" : __cfg.asset.templates + "form/bk_tpl_tab_general.php",
                "ajax_options" : {
                    "data" : tab_config.asset_options
                },
                "load_callback" : this.load_general_callback
            },
            "1" :
            {
                "id"   : "tab_properties",
                "name" : "<?php echo Util::js_entities(_('Properties'))?>",
                "href" : __cfg.asset.templates + "form/bk_tpl_tab_properties.php",
                "load_callback" : this.load_properties_callback
            },
            "2" :
            {
                "id"   : "tab_software",
                "name" : "<?php echo Util::js_entities(_('Software'))?>",
                "href" : __cfg.asset.templates + "form/bk_tpl_tab_software.php",
                "load_callback" : this.load_software_callback
            }
        }
    };


    //Extend functionality. Call parent object
    Av_tabs.apply(this, [tabs]);
};




/*******************************************************
 ******************  Asset handlers  *******************
 *******************************************************/


function load_asset_form_handlers(form_options)
{
    var asset_data  = form_options.asset_data || {};
    var is_editable = (form_options.type == 'bulk' || asset_data.is_editable != 'no');


    /* Tooltips */

    $(".info").tipTip({maxWidth: '380px', attribute: 'data-title'});


    /* Icon */

    $('#icon').av_icon({
        icon : asset_data.icon,
        show_actions : is_editable
    });


    /* Contexts and Sensors */

    if ($("#tree").length > 0)
    {
        var ctx_type = (is_editable == true) ? 'local' : 'remote';

        load_tree_context(ctx_type);
    }


    /* Geolocation */

    av_map = new Av_map('c_map');

    Av_map.is_map_available(function(conn)
    {
        if (conn)
        {
            if (typeof(asset_data.location) != 'undefined')
            {
                av_map.set_location(asset_data.location.lat, asset_data.location.lon);
                av_map.set_zoom(asset_data.location.zoom);
            }

            av_map.draw_map();

            if(av_map.get_lat() && av_map.get_lng())
            {
                av_map.add_marker(av_map.get_lat(), av_map.get_lng());
            }

            $('#search_location').geo_autocomplete(new google.maps.Geocoder, {
                selectFirst: true,
                minChars: 3,
                cacheLength: 50,
                width: 245,
                scroll: true,
                scrollHeight: 330
            }).result(function(_event, _data) {
                if (_data)
                {
                    //Set map coordenate
                    av_map.map.fitBounds(_data.geometry.viewport);

                    var aux_lat = _data.geometry.location.lat();
                    var aux_lng = _data.geometry.location.lng();

                    av_map.set_location(aux_lat, aux_lng);

                    $('#latitude').val(av_map.get_lat());
                    $('#longitude').val(av_map.get_lng());

                    //Save address
                    av_map.set_address(_data.formatted_address);

                    // Marker (Add or update)
                    av_map.remove_all_markers();
                    av_map.add_marker(av_map.get_lat(), av_map.get_lng());

                    av_map.map.setZoom(8);
                }
            });


            if (is_editable == true)
            {
                //Latitude and Longitude (Handler Onchange event)
                av_map.bind_pos_actions();

                //Search box (Handler Key up and Blur events)
                av_map.bind_sl_actions();

                if (typeof(asset_data.location) != 'undefined')
                {
                    av_map.set_address_by_coordenates(av_map.lat_lng);
                }
            }
            else
            {
                //No edit permissions, readonly mpa
                if (typeof(av_map.markers[0]) != 'undefined')
                {
                    av_map.map.setOptions({draggable: false});
                    av_map.markers[0].setDraggable(false);
                }
            }
        }
        else
        {
            av_map.draw_warning();

            $('#search_location, #latitude, #longitude').attr('disabled', 'disabled');
        }
    });


    /* Devices */

    bind_device_actions();


    if (is_editable == true)
    {
        /* Operating System */

        var _os_cpe_config = {
            'widget_id'     : 'os',
            'input_id'      : 'os_cpe',
            'data_provider' : form_options.providers.autocomplete,
            'error_msg'     : '',
            'minChars'      : 1,
            'width'         : 400,
            'matchContains' : false,
            'multiple'      : false,
            'autoFill'      : false,
            'mustMatch'     : false,
            'scroll'        : true,
            'scrollHeight'  : 150,
            'extraParams'   : {
                'cpe_type'  : 'os'
            }
        }

        bind_cpe_actions(_os_cpe_config);


        /* Model */

        var _model_cpe_config = {
            'widget_id'     : 'model',
            'input_id'      : 'model_cpe',
            'data_provider' : form_options.providers.autocomplete,
            'error_msg'     : '',
            'minChars'      : 1,
            'width'         : 400,
            'matchContains' : false,
            'multiple'      : false,
            'autoFill'      : false,
            'mustMatch'     : false,
            'scroll'        : true,
            'scrollHeight'  : 150,
            'extraParams'   : {
                'cpe_type'  : 'hardware'
            }
        }

        bind_cpe_actions(_model_cpe_config);


        /* Token */

        Token.add_to_forms();


        /* AJAX Validator */

        var av_config = {
           validation_type: 'complete', // single|complete
           errors: {
               display_errors: 'all', //  all | summary | field-errors
               display_in: 'tg_av_info'
           },
           form: {
               id: 'asset_form',
               url: form_options.controllers.submit_form
           },
           actions: {
               on_submit: {
                   id: 'tg_send',
                   success: '<?php echo _('Save')?>',
                   checking: '<?php echo _('Saving')?>'
               }
           }
        };

        ajax_validator = new Ajax_validator(av_config);

        $('#tg_send').click(function()
        {
            $('#' + av_config.form.id).attr('action', form_options.controllers.submit_form)
            selectall('devices');

            if (ajax_validator.check_form() == true)
            {
                ajax_validator.submit_form();
            }
        });
    }
}



/*******************************************************
 ***************  Properties handlers  *****************
 *******************************************************/

function load_property_form_handlers(form_options)
{
    var asset_data  = form_options.asset_data || {};
    var is_editable = (form_options.type == 'bulk' || asset_data.is_editable != 'no');


    /* DataTable for properties */
    var __p_config = {
        'list_type'   : form_options.type,
        'edit_mode'   : ~~is_editable,
        'asset_data'  : asset_data,
        'providers'   : form_options.providers,
        'controllers' : form_options.controllers,
        'action_callbacks' : {
            'reset'  : reset_form,
            'edit'   : edit_item,
            'delete' : delete_items
        }
    };


    var av_property_list = new Av_property_list(__p_config);
        av_property_list.draw();


    if (is_editable == true)
    {
        /* Form actions */
        $('#tp_property_id').on('change', function() {

            var p_data = {
                'row_id'   : '',
                'row_data' : {
                    'p_id' : parseInt($('#tp_property_id').val())
                }
            };

            edit_item('property', p_data);
        });

        //Save property
        $('#tp_save').on('click', function(xhr){
            save_item('property', form_options, av_property_list);
        });

        //Cancel edition
        $('#tp_cancel').off('click').on('click', function(xhr){
            reset_form('property');
        });
    }
}



/*******************************************************
 ***************  Properties handlers  *****************
 *******************************************************/

function load_software_form_handlers(form_options)
{
    var asset_data  = form_options.asset_data || {};
    var is_editable = (form_options.type == 'bulk' || asset_data.is_editable != 'no');


    /* DataTable for software */
    var __p_config = {
        'list_type'   : form_options.type,
        'edit_mode'   : ~~is_editable,
        'asset_data'  : asset_data,
        'providers'   : form_options.providers,
        'controllers' : form_options.controllers,
        'action_callbacks' : {
            'reset'  : reset_form,
            'edit'   : edit_item,
            'delete' : delete_items
        }
    };

    var av_software_list = new Av_software_list(__p_config);
        av_software_list.draw();


    if (is_editable == true)
    {
        /* Software */

        var _s_cpe_config = {
            'widget_id'     : 'sw_name',
            'input_id'      : 'sw_cpe',
            'data_provider' : form_options.providers.autocomplete,
            'error_msg'     : '',
            'minChars'      : 1,
            'width'         : 400,
            'matchContains' : false,
            'multiple'      : false,
            'autoFill'      : false,
            'mustMatch'     : true,
            'scroll'        : true,
            'scrollHeight'  : 150,
            'extraParams'   : {
                'cpe_type'  : 'software'
            }
        }

        bind_cpe_actions(_s_cpe_config);

        //Save software
        $('#tsw_save').on('click', function(xhr){
            save_item('software', form_options, av_software_list);
        });

        //Cancel edition
        $('#tsw_cancel').off('click').on('click', function(xhr){
            reset_form('software');
        });
    }
}




/*******************************************************
 ****************  Services handlers  ******************
 *******************************************************/

function load_service_form_handlers(form_options)
{
    var asset_data  = form_options.asset_data || {};
    var is_editable = (form_options.type == 'bulk' || asset_data.is_editable != 'no');


    /* DataTable for services */

    var __p_config = {
        'list_type'   : 'single',
        'edit_mode'   : ~~is_editable,
        'asset_data'  : asset_data,
        'providers'   : form_options.providers,
        'controllers' : form_options.controllers,
        'action_callbacks' : {
            'reset'      : reset_form,
            'edit'       : edit_item,
            'delete'     : delete_items,
            'monitoring' : toggle_monitoring
        }
    };

    var av_service_list = new Av_service_list(__p_config);
        av_service_list.draw();


    if (is_editable == true)
    {
        $('#s_protocol').on('change', function() {
            fill_ports();
        });

        $('#s_port').on('keyup', function() {
            fill_ports();
        });

        //New service
        $('#ts_save').off('click').on('click', function(xhr){
            save_item('service', form_options, av_service_list);
        });

        //Cancel edition
        $('#ts_cancel').off('click').on('click', function(xhr){
            reset_form('service');
        });
    }
}


/******************************************************
 *****************  Format functions  *****************
 ******************************************************/


function show_warning_no_editable(id, data)
{
    var __server = '<?php echo _('Unknown')?>';

    if  (typeof(data) != 'undefined' && data.name !== '')
    {
        __server =  data.name + ' (' + data.ip + ')';
    }

    var __error_msg = '<?php echo _('Asset can only be modified at the USM')?>' + ': ' + '<div style="margin-top: 3px">' + __server + '</div>';

    var config_nt = { content: __error_msg,
        options: {
            type: 'nf_warning',
            cancel_button: false
        },
        style: 'position: absolute; width:100%; text-align:center; margin: 50px auto 0px auto'
    };

    var nt = new Notification('nt_ext_ctx_error', config_nt);

    $('#' + id).html(nt.show());
}


function show_error_loading_data(id, msg)
{
    var config_nt = { content: msg,
        options: {
            type: 'nf_error',
            cancel_button: false
        },
        style: 'width:80%; text-align:center; margin: 100px auto;'
    };

    var nt = new Notification(id + '_nt_load_error', config_nt);

    $('#' + id + ' .ui-tabs-panel:not(.ui-tabs-hide)').html(nt.show());
}
