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


function av_list()
{
    this.cfg        = <?php echo Asset::get_path_url() ?>;
    this.ajax_url   = this.cfg.common.controllers + 'asset_filter_ajax.php';
    this.asset_type = '';
    this.perms      = {};
    this.table      = {};
    this.actions    = {};

    this.confirm_keys =
    {
        "yes": "<?php echo Util::js_entities(_('Yes')) ?>",
        "no": "<?php echo Util::js_entities(_('No')) ?>"
    };    
 
    
    this.init_list = function()
    {
        var __self = this;
        
        /* Line to prevent the autocomplete in the browser */ 
        $('input').attr('autocomplete','off');
        
         //All available Actions 
        this.actions = 
        {
            "edit":
            {
                "id"    : "edit",
                "name"  : "<?php echo _('Edit') ?>",
                "perms" : "edit",
                "action": function()
                {
                    __self.edit_selection();
                }
            },
            "delete":
            {
                "id"    : "delete",
                "name"  : "<?php echo _('Delete') ?>",
                "perms" : "delete",
                "action": function()
                {
                    var n_assets = __self.get_num_selected_assets();
                    var msg      = "";

                    if (__self.asset_type == 'asset')
                    {
                        msg = "<?php echo Util::js_entities(_('Are you sure you want to permanently delete ### asset(s)?'))?>".replace('###', n_assets);
                    }
                    else if (__self.asset_type == 'group')
                    {
                        msg = "<?php echo Util::js_entities(_('Are you sure you want to permanently delete ### group(s)?'))?>".replace('###', n_assets);
                    }
                    else
                    {
                        msg = "<?php echo Util::js_entities(_('Are you sure you want to permanently delete ### network(s)?'))?>".replace('###', n_assets);
                    }

                    av_confirm(msg, __self.confirm_keys).done(function()
                    {
                        __self.delete_selection();
                    });
                }
            },
            "nmap_scan":
            {
                "id"    : "nmap_scan",
                "name"  : "<?php echo _('Run Asset Scan') ?>",
                "perms" : "nmap",
                "action": function()
                {
                    __self.asset_scan();
                }
            },
            "vulnerability_scan":
            {
                "id"    : "vulnerability_scan",
                "name"  : "<?php echo _('Run Vulnerability Scan') ?>",
                "perms" : "vulnerabilities",
                "action": function()
                {
                    __self.vuln_scan();
                }
            },
            "enable_monitoring":
            {
                "id"    : "enable_monitoring",
                "name"  : "<?php echo _('Enable Availability Monitoring') ?>",
                "perms" : "availability",
                "action": function()
                {
                    var msg  = "<?php echo _('Warning: Enabling Availability Monitoring for this asset group will update all of the assets in the group. Some of these assets may be in other asset groups with Availability  Monitoring currently disabled. Are you sure you would like to continue? ') ?>";
                    __self.save_selection().done(function() {
                        (__self.asset_type == 'asset') ?  __self.toggle_monitoring('enable') : __self.group_toggle_monitoring(msg,'enable');
                    });
                }
            },
            "disable_monitoring":
            {
                "id"    : "disable_monitoring",
                "name"  : "<?php echo _('Disable Availability Monitoring') ?>",
                "perms" : "availability",
                "action": function()
                {
                    __self.save_selection().done(function() {
                        var msg  = "<?php echo _('Warning: Disabling Availability Monitoring for this asset group will update all of the assets in the group. Some of these assets may be in other asset groups with Availability  Monitoring currently enabled. Are you sure you would like to continue? ') ?>";
                        (__self.asset_type == 'asset') ?  __self.toggle_monitoring('disable') : __self.group_toggle_monitoring(msg,'disable');
                    });
                }
            },
            "add_to_group":
            {
                "id"    : "add_to_group",
                "name"  : "<?php echo _('Create/Add to Group') ?>",
                "perms" : "",
                "action": function()
                {
                    __self.save_to_group();
                }
            },
            "add_note":
            {
                "id"    : "add_note",
                "name"  : "<?php echo _('Add Note') ?>",
                "perms" : "",
                "action": function()
                {
                    __self.add_note();
                }
            },
            "add_host":
            {
                "id"    : "add_host",
                "name"  : "<?php echo _('Add Host') ?>",
                "perms" : "create",
                "action": function()
                {
                    __self.add_asset();
                }
            },
            "add_network":
            {
                "id"    : "add_net",
                "name"  : "<?php echo _('Add Network') ?>",
                "perms" : "create",
                "action": function()
                {
                    __self.add_asset();
                }
            },
            "import_csv":
            {
                "id"    : "import_csv",
                "name"  : "<?php echo _('Import CSV') ?>",
                "perms" : "create",
                "action": function()
                {
                    __self.import_csv();
                }
            },
            "import_siem":
            {
                "id"    : "import_siem",
                "name"  : "<?php echo _('Import From SIEM') ?>",
                "perms" : "create",
                "action": function()
                {
                    __self.import_siem();
                }
            },
            "discover_new_assets":
            {
                "id"    : "discover_new_assets",
                "name"  : "<?php echo _('Scan for New Assets') ?>",
                "perms" : "create",
                "action": function()
                {
                    __self.discover_new_assets();
                }
            },
            "deploy_hids_agents":
            {
                "id"    : "deploy_hids_agents",
                "name"  : "<?php echo _('Deploy HIDS Agents') ?>",
                "perms" : "deploy_agents",
                "action": function()
                {
                    __self.deploy_hids_agents();
                }
            }
        }

        __self.draw_dropdown_options($('[data-bind="dropdown-actions"]'), __self.allowed_actions);
    }
    
    /**************************************************************************/
    /***************************  FILTER FUNCTIONS  ***************************/
    /**************************************************************************/
    
    /*  Function to modifiy the filters  */
    this.set_filter_value = function(id, value, del_value, tag_label)
    {
        var __self = this
        var params = {};
        var data   = {};
    
        data["id"]       = id;
        data["filter"]   = value;
        data["delete"]   = ~~del_value;
        data["reload"]   = 1;
    
        params["action"] = "modify_filter";
        params["data"]   = data;
    
        var ctoken = Token.get_token("asset_filter_value");
    	$.ajax(
    	{
    		data: params,
    		type: "POST",
    		url: __self.ajax_url + "?token="+ctoken,
    		dataType: "json",
    		beforeSend: function()
    		{
        	   __self.disable_search_inputs();
    		},
    		success: function(data)
    		{
        		
        		if (!data.error)
        		{
                    if (del_value)
                    {
                       __self.remove_tag(id, value);
                    }
                    else
                    {
                        if (typeof tag_label != 'undefined' && tag_label != '' )
                        {
                            __self.create_tag(tag_label, id, value);
                        }
                    }
                    __self.reload_table();
        		}
        		else
        		{
            		__self.enable_search_inputs();
            		show_notification('asset_notif', data.msg, 'nf_error', 5000, true);
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
                
                __self.enable_search_inputs();
	    		
                show_notification('asset_notif', errorThrown, 'nf_error', 5000, true);
    		}
    	});
    }
    
    
    /*  Function to apply the filters and reload the datatables  */
    this.reload_assets_group = function(force)
    {
        var __self = this;
        
        if (typeof force == 'undefined')
        {
            force = false;
        }
    
        var data      = {};
    
        data["force"] = ~~force;
    
    	var ctoken = Token.get_token("asset_filter_value");
    	$.ajax(
    	{
    		data: {"action":"reload_group", "data": data },
    		type: "POST",
    		url: __self.ajax_url + "?token=" + ctoken,
    		dataType: "json",
    		beforeSend: function()
    		{
                __self.disable_search_inputs();
    		},
    		success: function(data)
    		{
                //if the datatables is defined we reload it
                __self.reload_table();
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

                show_notification('asset_notif', errorThrown, 'nf_error', 5000, true);
    		},
    		complete: function()
    		{
        		__self.enable_search_inputs();
    		}
    	});
    }


    /*  Function to restore the search --> It deletes the filter_list object  */
    this.restart_search = function()
    {
        var __self = this;
        var params = {
            'type': this.asset_type
        }
        
        var ctoken = Token.get_token("asset_filter_value");
        $.ajax(
    	{
    		data: {"action":"restart_search", "data": params},
    		type: "POST",
    		url: __self.ajax_url + "?token=" + ctoken,
    		dataType: "json",
    		success: function(data)
    		{
        		try
        		{
                    if (data.error)
                    {
                        document.location.reload();
                    }
    
                    __self.remove_all_filters();
                    __self.reload_table();
                }
                catch(Err)
                {
                    document.location.reload();
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
    
                show_notification('asset_notif', errorThrown, 'nf_error', 5000, true);
    		}
    	});
    }

    
    /*  Function to restore the filter_list object if we cancel the filters in the extra_filter Lightbox  */
    this.restore_filter_list = function()
    {
        var __self = this;
        var ctoken = Token.get_token("asset_filter_value");
        $.ajax(
    	{
    		data: {"action": "cancel_filter"},
    		type: "POST",
    		url: __self.ajax_url + "?token=" + ctoken,
    		dataType: "json",
            error: function(XMLHttpRequest, textStatus, errorThrown)
    		{
                //Checking expired session
        		var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
    		}
    	});
    }
    
    
    this.reload_table = function()
    {
        this.table.reload_table();
    }
    
    
    this.modify_date_filter = function(that)
    {
        var filter = $(that).data('filter');
    
        var from   = $('#date_from_'+filter).val();
        var to     = $('#date_to_'+filter).val();
    
        var value  = 'range;' + from + ';' + to;
    
        this.set_filter_value(filter, value, 0);
    
        return false;
    }

   
        
    /**************************************************************************/
    /***************************  ACTION FUNCTIONS  ***************************/
    /**************************************************************************/
    
    /* Function to open export hosts page  */
    this.add_asset = function(){};
    
    /* Function to open export hosts page  */
    this.export_selection = function(){};    
    
    /* Function to delete all assets which match with filter criteria */
    this.delete_selection = function(){
        
        var __self = this;
        
        __self.save_selection().done(function()
        {
            //AJAX data

            var h_data = 
            {
                "token" : Token.get_token("delete_" + __self.asset_type + "_bulk")
            };

            var _msg = "";

            if (__self.asset_type == 'asset')
            {
                _msg = '<?php echo Util::js_entities(_("Deleting asset(s)... Please Wait")) ?>';
            }
            else if (__self.asset_type == 'group')
            {
                _msg = '<?php echo Util::js_entities(_("Deleting group(s)... Please Wait")) ?>';
            }
            else
            {
                _msg = '<?php echo Util::js_entities(_("Deleting network(s)... Please Wait")) ?>';
            }

            $.ajax(
            {
                type: "POST",
                url: __self.cfg[__self.asset_type].controllers  + "bk_delete.php",
                data: h_data,
                dataType: "json",
                beforeSend: function()
                {
                    $('#asset_notif').empty();

                    show_loading_box('main_container', _msg , '');
                },
                success: function(data)
                {
                    hide_loading_box();

                    __self.restart_search();

                    show_notification('asset_notif', data.data, 'nf_success', 15000, true);
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

                    hide_loading_box();
                    
                    var _msg  = XMLHttpRequest.responseText;
                    
                    var _type = (_msg.match(/policy/)) ? 'nf_warning' : 'nf_error';

                    show_notification('asset_notif', _msg, _type, 15000, true);

                    __self.reload_table();
                }
            });
        });
    };


    /*  Function to open edit assets form lightbox */
    this.edit_selection = function()
    {
        var __self = this;

        __self.save_selection().done(function()
        {
            var url   = __self.cfg.asset.views + "asset_form.php?edition_type=bulk&asset_type=" + __self.asset_type;
            var title = "<?php echo Util::js_entities(_('Edit Assets')) ?>";

            GB_show(title, url, '600', '825');

        });
    }
    
    /*  Function to open new host form lightbox  */
    this.add_note = function()
    {
        var __self = this;
        
        __self.save_selection().done(function()
        {
            var url   = __self.cfg.common.views + 'bk_add_note.php?type=' + __self.asset_type;
            var title = "<?php echo Util::js_entities(_('Add Note')) ?>";
            
            GB_show(title, url, '350', '500');
            
        });
    }
    
    
    /*  Function to open new host form lightbox  */
    this.asset_scan = function()
    {
        var __self = this;
        
        __self.save_selection((__self.asset_type == 'group')).done(function()
        {
            var url   = '/ossim/netscan/new_scan.php?type=' + __self.asset_type;
            var title = "<?php echo Util::js_entities(_('Asset Scan')) ?>";
            
            GB_show(title, url, '600', '720');
            
        });
    }
    
    
    /*  Function to open new host form lightbox  */
    this.vuln_scan = function()
    {
        var __self = this;
        
        __self.save_selection().done(function()
        {
            var url   = '/ossim/vulnmeter/new_scan.php?action=create_scan&type=' + __self.asset_type;
            var title = "<?php echo Util::js_entities(_('Vulnerability Scan')) ?>";
            
            GB_show(title, url, '600', '720');
            
        })
    }

    /*  Function to enable and disable  Availability Monitoring to the selected assets  */
    this.toggle_monitoring = function(action)
    {
        var __self = this;
        var ctoken = Token.get_token("toggle_monitoring");
        $.ajax(
            {
                type: "POST",
                url: __self.cfg.common.controllers + "bk_toggle_monitoring.php",
                data: 'token=' + ctoken + '&asset_type=' + __self.asset_type + '&action=' + action,
                dataType: "json",
                success: function(data)
                {
                    if (data.status == 'OK')
                    {
                        show_notification('asset_notif', data.data, 'nf_success', 15000, true);
                        __self.reload_assets_group(true);
                    }
                    else if (data.status == 'warning')
                    {
                        show_notification('asset_notif', data.data, 'nf_warning', 15000, true);
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

                    var error = XMLHttpRequest.responseText;
                    show_notification('asset_notif', error, 'nf_error', 5000, true);
                }
            });
    }

    /*  Function to enable and disable  Availability Monitoring to the selected assets group  */
    this.group_toggle_monitoring = function (msg,action) {
        var __self = this;
        var token  = Token.get_token("ag_form");
        $.ajax(
            {
                type: "POST",
                url: __self.cfg.group.controllers + "group_actions.php",
                data: {token: token, action: 'is_unique_group'},
                dataType: "json",
                success: function(data)
                {
                    console.log(data);

                    if (data.unique) {
                        __self.toggle_monitoring(action);
                        return;
                    }

                    av_confirm(msg, __self.confirm_keys).done(function()
                    {
                        __self.toggle_monitoring(action);
                    });

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

                    var error = XMLHttpRequest.responseText;
                    show_notification('asset_notif', error, 'nf_error', 5000, true);
                }
            });

    }

    /*  Function to open deploy HIDS form lightbox  */
    this.deploy_hids_agents = function()
    {
        var __self = this;

        __self.save_selection().done(function()
        {
            var url   = __self.cfg.common.views  + 'bk_deploy_hids_form.php?type=' + __self.asset_type;
            var title = "<?php echo Util::js_entities(_('Deploy HIDS Agents')) ?>";

            GB_show(title, url, '60%', '750');
        });
    }


    this.init_labels = function(elem)
    {
        var __self = this;
        
        var labels = $("<img/>",
        {
            "id"            : "label_selection",
            "class"         : "avt_action avt_img disabled",
            "src"           : "/ossim/pixmaps/label.png",
            "data-selection": "avt_action"
        });

        var av_dropdown_tag = labels.av_dropdown_tag( 
        {
            'load_tags_url'        : '<?php echo AV_MAIN_PATH?>/tags/providers/get_dropdown_tags.php',
            'manage_components_url': '<?php echo AV_MAIN_PATH?>/tags/controllers/tag_components_actions.php',
            'allow_edit'           : __self.perms.admin,
            'tag_type'             : 'asset',
            'select_from_filter'   : true,
            'show_tray_triangle'   : true,
            'on_save'              : function (status, data)
            {
                if (status == 'OK')
                {
                    show_notification('asset_notif', data['components_added_msg'], 'nf_success', 5000, true);
                }
                else
                {
                    show_notification('asset_notif', data, 'nf_error', 5000, true);
                }
            },
            'on_delete'            : function (status, data)
            {
                if (status == 'OK')
                {
                    show_notification('asset_notif', data['components_deleted_msg'], 'nf_success', 5000, true);
                }
                else
                {
                    show_notification('asset_notif', data, 'nf_error', 5000, true);
                }
            }
        });
        
        labels.appendTo(elem);
        
        labels.off('click').on('click', function()
        {
            if (!__self.action_enabled(this))
            {
                return false;
            }
            else
            {
                __self.save_selection((__self.asset_type != 'asset'));
                av_dropdown_tag.show_dropdown();
            }
        });
    }
    
    
    /**************************************************************************/
    /*************************  SELECTION FUNCTIONS  **************************/
    /**************************************************************************/

    this.update_asset_counter = function()
    {
        num = this.table.get_total_items();
        
        $('#num_assets').text(this.number(num));
    }
        
    this.get_num_selected_assets = function()
    {
        return this.table.get_selected_items();
    }
    
    
    this.save_selection = function(members)
    {
        members  = (typeof members != 'boolean') ? 0 : ~~members; 
        
        var sel  = this.table.get_selection();
        var data =
        {
            "asset_type"  : this.asset_type,
            "assets"      : sel['items'],
            "all"         : sel['all'],
            "save_members": members
        };

        var token = Token.get_token("save_selection");  
        return $.ajax(
        {
            type    : "POST",
            url     : this.cfg.common.controllers  + "save_selection.php",
            data    : {"action": "save_list_selection", "token": token, "data": data},
            dataType: "json"
        }).fail(function(obj)
        {
            //Checking expired session
            var session = new Session(obj, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            show_notification('asset_notif', obj.responseText, 'nf_error', 15000, true);
        });
    } 
    
    
    /**************************************************************************/
    /***************************  LINKS FUNCTIONS  ****************************/
    /**************************************************************************/
    
    /*  Function to open the host detail */
    this.load_asset_detail = function(id){};
    
    
    /*  Function to open extra filters lightbox  */
    this.show_more_filters = function()
    {
        var url   = this.cfg.common.views  + 'extra_filters.php';
        var title = "<?php echo Util::js_entities(_('More Filters')) ?>";
        
        GB_show(title, url, '600', '1100');
    }
    
    
    
    /**************************************************************************/
    /****************************  TAGS FUNCTIONS  ****************************/
    /**************************************************************************/

    /*  Function to create a tag filter  */
    this.create_tag = function(label, filter, value)
    {
        var tag_info  = filter + '###' + value;
        var tag_class = $.md5('label_'+tag_info) + ' filter_' + filter;
    
        $('#tags_filters').tagit('createTag', label, tag_class, tag_info);
    }
    
    
    /*  Function to delete a tag filter  */
    this.remove_tag = function(filter, value)
    {
        var tag_info  = filter + '###' + value;
        var tag_class = $.md5('label_'+tag_info);
    
        //Removing the tag
        $('#tags_filters li.'+tag_class).remove();
    
        //Deselecting the checkboxes
        $('#filter_'+filter).prop('checked', false);
    
        this.disable_range_selector(filter);
    
        //Deselecting the date radios
        $('#filter_'+filter + ' input').prop('checked', false);
    
        //Hidding date inputs
        $('#filter_'+filter + ' .asset_date_range').hide();
    
        //Removing the content from the dates selected
        $('#filter_'+filter + ' .date_filter').val('');
        $('#filter_'+filter + ' .date_filter').datepicker("option", {minDate: null, maxDate: null});
    }
    
    
    
    /**************************************************************************/
    /***************************  DRAW FUNCTIONS  *****************************/
    /**************************************************************************/
           
    this.action_enabled = function(elem)
    {
        if ($(elem).hasClass('disabled') || $(elem).hasClass('av_b_disabled'))
        {
            return false;
        }
        
        return true;
    }
        
    
    this.disable_search_inputs = function()
    {
        $('.input_search_filter').prop('disabled', true);
        $('.calendar input').datepicker('disable');
        $('.ui-slider').slider('disable');
        $('body').css('cursor', 'wait'); 
        $('[data-bind="more-filters"]').addClass('av_b_disabled');
    }
    
    
    this.enable_search_inputs = function()
    {
        $('.input_search_filter').prop("disabled", false);
        $('.calendar input').datepicker('enable');
    
        if ($('#filter_6').prop('checked'))
        {
            $('#asset_value_slider .ui-slider').slider('enable');
        }
    
        if ($('#filter_5').prop('checked'))
        {
            $('#vulns_slider .ui-slider').slider('enable');
        }
    
        $('[data-bind="more-filters"]').removeClass('av_b_disabled');
        
        $('body').css('cursor', 'default');
    }
    
    
    /*  Function to unmark all the filters  */
    this.remove_all_filters = function()
    {
        //Uncheck checkboxes and radio
        $('.input_search_filter').prop('checked', false);
    
        //Restart range selectors
        this.disable_range_selector('all');
        
        this.clean_checked();
            
        //Removing filter tags
        $("#tags_filters .tagit-choice").remove();
    
        //Hidding date inputs
        $('.asset_date_range').hide();
    
        //Empty the date picker
        $('.date_filter').val('');
        $('.date_filter').datepicker("option", {minDate: null, maxDate: null});
    }
    
    
    this.clean_checked = function()
    {
        this.table.reset_selection();
    }
    
    this.disable_range_selector = function(filter)
    {
        //Vulns Range
        if (filter == 5 || filter == 'all')
        {
            //Disabling Slider
            $('#vulns_slider .ui-slider').slider('disable');
            //Restoring default value
            $('#vulns_slider .ui-slider').slider('values', [0,4]);
            $('#vrangeA option:eq(0)').prop('selected', true);
            $('#vrangeB option:eq(4)').prop('selected', true);
        }
        //Asset value range
        else if (filter == 6 || filter == 'all')
        {
            //Disabling Slider
            $('#asset_value_slider .ui-slider').slider('disable');
            //Restoring default value
            $('#asset_value_slider .ui-slider').slider('values', [0,5]);
            $('#arangeA option:eq(0)').prop('selected', true);
            $('#arangeB option:eq(5)').prop('selected', true);
        }
    }
    
    
    this.draw_dropdown_options = function(dropdown, options)
    {   
        var __self    = this;
        var flag_hide = true;
        
        var ul = $('<ul/>',
        {
            'class': 'dropdown-menu'
        });
        
        options.forEach(function(i,v)
        {   
            
            if (!__self.actions[i] || __self.perms[__self.actions[i].perms] === false)
            {
                return true;
            }

            var item = __self.actions[i];
            var li   = $('<li/>').appendTo(ul);
            
            $('<a/>',
            {
                "href" : "#" + v,
                "id"   : item.id,
                "text" : item.name,
                "click": function()
                {
                    item.action();
                }
            }).appendTo(li);

            flag_hide = false;
        });
        
        if (!flag_hide)
        {
            ul.appendTo(dropdown);
        }
        else
        {
            $('[data-dropdown="#'+ dropdown.attr('id') +'"]').prop('disabled', true);
            dropdown.hide();
        }   
    }
    
    /**************************************************************************/
    /***************************  EXTRA FUNCTIONS  ****************************/
    /**************************************************************************/
    
    /*  Validation Filter for Autocomplete  */
    this.is_ip_cidr = function(val)
    {
        var pattern = /^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\/([1-9]|[1-2][0-9]|3[0-2]))?$/ ;
    
        if (val.match(pattern))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    this.number = function(n)
    {
        if (typeof $.number == 'function')
        {
            return $.number(n);
        }
        else
        {
            return n;
        }
    }    

}
