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

/*
 *  av_detail_section class.
 *
 * @param  opt  Class options. 
 */
function av_detail_section(opt)
{
    //Path object configuration.
    this.cfg = <?php echo Asset::get_path_url() ?>;
    //Default Detail Options
    this.o =
    {
        "asset_id"       : "",
        "asset_type"     : "",
        "permissions"    : {},
        "sections"       : {},
        "scroll_section" : null,
    };
    
    //Merging default and custom class options
    $.extend(this.o, opt || {});

    //Hash with the table sections.
    this.av_tables = {};
    //Tab plugin
    this.av_tabs   = new Av_tabs(this.o.sections);
    this.av_tabs.draw_tabs();
    
    //AV_confirm keys definition.
    var __keys = 
    {   
        "yes": "<?php echo Util::js_entities(_('Yes')) ?>",
        "no" : "<?php echo Util::js_entities(_('No')) ?>"
    };
    
    var __self = this;
    
    if (__self.o.scroll_section != null)
    {
        setTimeout(function()
        {
            scroll_to(__self.o.scroll_section);
        }, 500);
    }

    /*
     * Function to open s given section.
     *
     * @param  name   Section Name. 
     */
    this.open_section = function(name, scroll_elem)
    {
        var tab = $("[data-id='tab_"+ name +"']").attr('href');
        this.av_tabs.open_tab(tab);
        
        if (typeof scroll_elem == 'object')
        {
            scroll_to(scroll_elem);
        }
    }

    
    /*
     * Function to reload a given section.
     *
     * @param  id   Section ID. 
     */
    this.reload_section = function(id)
    {
        if (typeof this.av_tables[id] == 'object' &&  typeof this.av_tables[id].reload_table == 'function')
        {
            this.av_tables[id].reload_table();
        }
    }
    
    
    /*
     * Function to check if we can perform an action.
     *
     * @param  id     Section where we are going to perform the action. 
     */
    this.action_forbidden = function(id)
    {
        //If the table does not have checkboxes, we can perform the action.
        if (!this.av_tables[id].is_selectable())
        {
            return false;
        }
        
        //If the table has checboxes and there aren't items selected we cannot perform the action.
        var total = this.av_tables[id].get_selected_items();

        if (total < 1)
        {
            return true;
        }

        return false;
    }


    /*
     * Function to save the members of an asset.
     *
     * @param  id   Section ID. 
     */
    this.save_members = function(id)
    {
        if (__self.action_forbidden(id))
        {
            return $.Deferred().reject();
        }

        var select = __self.av_tables[id].get_selection();
        var data   =
        {
            "asset_id"   : __self.o.asset_id,
            "asset_type" : __self.o.asset_type,
            "search"     : select.search,
            "member_type": 'asset',
            "all"        : 1
        };

        var token  = Token.get_token("save_selection");
        
        return $.ajax(
        {
            type    : "POST",
            url     : __self.cfg.common.controllers  + "save_selection.php",
            data    : {"action": "save_member_selection", "token": token, "data": data},
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


    /*
     * Function to save the selected assets.
     *
     * @param  id   Section ID. 
     */
    this.save_selection = function(id)
    {
        if (__self.action_forbidden(id))
        {
            return $.Deferred().reject();
        }

        var search = __self.av_tables[id].get_selection();
        var data   = 
        {
            "asset_id"   : __self.o.asset_id,
            "asset_type" : __self.o.asset_type,
            "member_type": id,
            "all"        : search.all,
            "assets"     : search.items,
            "search"     : search.search
        };
        

        var token = Token.get_token("save_selection");
        return $.ajax(
        {
            type    : "POST",
            url     : __self.cfg.common.controllers  + "save_selection.php",
            data    : {"action": "save_member_selection", "token": token, "data": data},
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




    /*******************************************************************************************************/
    /****************************                ASSET SECTION                ******************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Asset Tab.
     *
     * @param  opt  Class options. 
     */
    this.load_assets = function(o)
    {
        var opt =
        {
            "on_delete_asset" : function(){}
        };

        $.extend(opt, o || {});


        __self.av_tables['asset'] = $("[data-bind='av_table_assets']").AV_table(
        {
            "selectable": true,
            "with_tray" : true,
            "language": "assets",
            "ajax_url": __self.cfg.common.providers + "dt_assets.php",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "center", "sWidth": "30px"},
                    { "bSortable": true,  "sClass": "left", "sWidth": "170px"},
                    { "bSortable": true,  "sClass": "left", "sWidth": "150px"},
                    { "bSortable": false, "sClass": "left dt_force_wrap"},
                    { "bSortable": true,  "sClass": "left dt_force_wrap"},
                    { "bSortable": true,  "sClass": "center dt_force_wrap"},
                    { "bSortable": true,  "sClass": "center dt_force_wrap"},
                    { "bSortable": true,  "sClass": "center dt_force_wrap"},
                    { "bSortable": false, "sClass": "center", "sWidth": "35px"}
                ],
                "aaSorting": [[ 1, "asc" ]]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            },
            "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
            {
                var id = aData['DT_RowId'];

                $('<img></img>',
                {
                    "class" : "avt_img",
                    "src"   : "/ossim/pixmaps/show_details.png",
                    "click" : function(e)
                    {
                        e.preventDefault();
                        e.stopPropagation();

                        load_detail(id);
                    }
                }).appendTo($("td:nth-child(9)", nRow));

                $(nRow).addClass('asset_tr');
            },
            "on_open_tray": function(ui, row, wrapper)
            {
                wrapper.AV_asset_indicator(
                {
                    'asset_type' : 'asset',
                    'asset_id'   : row.attr('id'),
                    'class'      : 'circle_tray',
                    'perms'      : __self.o.permissions
                }).hide();   
            },
            "on_row_dbl_click": function(ui, row)
            {
                load_detail(row.attr('id'));
            },
            "on_finish_draw": function(ui, oSettings, json)
            {
                var elem = ui.dt_actions;

                if (__self.o.permissions['edit'])
                {
                    if (__self.o.asset_type == 'group')
                    {
                        $('<button></button>',
                        {
                            "text"     : "<?php echo _('Add Assets') ?>",
                            "class"    : "av_b_secondary small avt_action",
                            "click"    : function()
                            {
                                add_assets();
                            }
                        }).appendTo(elem);

                        $('<img></img>',
                        {
                            "class": "avt_img avt_action",                            
                            "src"  : "/ossim/pixmaps/delete-big.png",
                            "click": function(e)
                            {
                                if (!__self.action_forbidden('asset'))
                                {
                                    var msg = "<?php echo _('Are you sure you want to remove the selected assets from the group') ?>";
                                    av_confirm(msg, __keys).done(remove_assets);
                                }
                            },
                            "data-selection": "avt_action"
                        }).appendTo(elem);
                    }
                }

                $('<img></img>',
                {
                    "class": "avt_img avt_action",
                    "src"  : "/ossim/pixmaps/edit.png",
                    "click": function(e)
                    {
                        edit_assets();
                    },
                    "data-selection": "avt_action"
                }).appendTo(elem);

                // Labels dropdown
                var options = 
                {
                    'load_tags_url'        : '<?php echo AV_MAIN_PATH?>/tags/providers/get_dropdown_tags.php',
                    'manage_components_url': '<?php echo AV_MAIN_PATH?>/tags/controllers/tag_components_actions.php',
                    'allow_edit'           : __self.o.permissions['admin'],
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
                };

                var $label_selection = $('<img></img>',
                {
                    "class"         : "avt_img avt_action",
                    "src"           : "/ossim/pixmaps/label.png",
                    "data-selection": "avt_action"
                }).appendTo(elem);

                $label_selection.av_dropdown_tag(options);

                $label_selection.off('click').on('click', function (e)
                {
                    apply_labels($label_selection);
                });
            }
        });
        

        /*
         * Function to open the lightbox for adding assets to the group.
         */
        function add_assets()
        {
            var url   = __self.cfg.group.views + "add_asset_to_group.php?group_id="+ __self.o.asset_id;
            var title = "<?php echo _('Add Assets to Group') ?>";

            GB_show(title, url, '80%', '850');
        }

        
        /*
         * Function to remove assets from group.
         */
        function remove_assets()
        {
            __self.save_selection('asset').done(function(data)
            {
                var data =
                {
                    "action"  : "delete_assets",
                    "asset_id": __self.o.asset_id,
                    "token"   : Token.get_token("ag_form")
                };

                $.ajax(
                {
                    type: "POST",
                    url: __self.cfg.group.controllers  + "group_actions.php",
                    data: data,
                    dataType: "json",
                    success: function(data)
                    {
                        __self.reload_section('asset');

                        opt.on_delete_asset();
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

                        var _msg  = XMLHttpRequest.responseText;

                        show_notification('asset_notif', _msg, "error", 15000, true);

                    }
                });

            });
        }


        /*
         * Function to open the lightbox for editting the assets. 
         */
        function edit_assets()
        {
            __self.save_selection('asset').done(function(data)
            {
                var url   = __self.cfg.asset.views + "asset_form.php?edition_type=bulk";
                var title = "<?php echo _('Edit Assets') ?>";

                GB_show(title, url, '80%', '850');
            });
        }
        
        
        /*
         * Function to apply labels to the assets.
         *
         * @param  elem     Elem where the label dropdown will be openned . 
         */
        function apply_labels(elem)
        {
            __self.save_selection('asset').done(function(data)
            {
                elem.show_dropdown();
            });
        }
        
        
        /*
         * Function to load asset detail.
         *
         * @param  id     Asset ID. 
         */
        function load_detail(id)
        {
            var url    = __self.cfg.asset.detail + '?asset_id=' + id;
            var p_menu = 'environment';
            var s_menu = 'assets';
            var t_menu = 'assets';
    
            link(url, p_menu, s_menu, t_menu);
        }
    }




    /*******************************************************************************************************/
    /****************************                GROUP SECTION                ******************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Group Tab.
     *
     * @param  opt  Class options. 
     */
    this.load_groups = function(o)
    {
        var opt =
        {
            "on_delete_group" : function(){}
        };

        $.extend(opt, o || {});

        __self.av_tables['group'] = $("[data-bind='av_table_groups']").AV_table(
        {
            "selectable": true,
            "with_tray" : true,
            "language": "groups",
            "ajax_url": __self.cfg.asset.providers + "dt_groups.php",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "center", "sWidth": "35px"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px"}
                ]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            },
            "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
            {
                var id = aData['DT_RowId'];

                $('<img></img>',
                {
                    "class" : "avt_img",
                    "src"   : "/ossim/pixmaps/show_details.png",
                    "click" : function(e)
                    {
                        e.preventDefault();
                        e.stopPropagation();

                        load_detail(id);
                    }
                }).appendTo($("td:last-child", nRow));

                $(nRow).addClass('asset_tr');
            },
            "on_open_tray": function(ui, row, wrapper)
            {
                wrapper.AV_asset_indicator(
                {
                    'asset_type' : 'group',
                    'asset_id'   : row.attr('id'),
                    'class'      : 'circle_tray',
                    'perms'      : __self.o.permissions
                }).hide();   
            },
            "on_row_dbl_click": function(ui, row)
            {
                load_detail(row.attr('id'));
            },
            "on_finish_draw": function(ui, oSettings, json)
            {
                var elem = ui.dt_actions;

                $('<button></button>',
                {
                    "text"     : "<?php echo _('Add to Group') ?>",
                    "class"    : "av_b_secondary small avt_action",
                    "click"    : function()
                    {
                        add_groups();
                    }
                }).appendTo(elem);

                $('<img></img>',
                {
                    "class": "avt_img avt_action",
                    "src"  : "/ossim/pixmaps/delete-big.png",
                    "click": function(e)
                    {
                        if (true || !__self.action_forbidden('group'))
                        {
                            var msg = "<?php echo _('Are you sure you want to remove the asset from the selected groups') ?>";
                            av_confirm(msg, __keys).done(remove_groups);
                        }
                    },
                    "data-selection": "avt_action"
                }).appendTo(elem);

            }
        });
        
                
        /*
         * Function to load the group detail.
         *
         * @param  id     Group ID. 
         */
        function load_detail(id)
        {
            var url    = __self.cfg.asset.detail + '?asset_id=' + id;
            var p_menu = 'environment';
            var s_menu = 'assets';
            var t_menu = 'asset_groups';

            link(url, p_menu, s_menu, t_menu);
        }

        
        /*
         * Function to add the asset to a group.
         */
        function add_groups()
        {
            var url   = __self.cfg.asset.views + "add_to_group.php?asset_id="+ __self.o.asset_id;
            var title = "<?php echo _('Add Groups to Asset') ?>";

            GB_show(title, url, '80%', '850');
        }

        
        /*
         * Function to remove the asset from a group.
         */
        function remove_groups()
        {
            __self.save_selection('group').done(function(data)
            {
                //AJAX data
                var data =
                {
                    "action"  : "delete_from_groups",
                    "asset_id": __self.o.asset_id,
                    "token"   : Token.get_token("asset_form")
                };

                $.ajax(
                {
                    type: "POST",
                    url: __self.cfg.asset.controllers  + "asset_actions.php",
                    data: data,
                    dataType: "json",
                    success: function(data)
                    {
                        __self.reload_section('group');
                        opt.on_delete_group();
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

                        var _msg  = XMLHttpRequest.responseText;

                        show_notification('asset_notif', _msg, "error", 15000, true);
                    }
                });
            });
        }
    }




    /*******************************************************************************************************/
    /****************************                VULNS SECTION                ******************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Vulnerabilities Tab.
     */
    this.load_vulnerabilities = function()
    {
        __self.av_tables['vulnerabilities'] = $("[data-bind='av_table_vulnerabilities']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_vulnerabilities.php",
            "language": "vulnerabilities",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"}
                ]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            }
        });
    }




    /*******************************************************************************************************/
    /****************************                ALARM SECTION                ******************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Alarm Tab.
     */
    this.load_alarms = function()
    {
        __self.av_tables['alarms'] = $("[data-bind='av_table_alarms']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_alarms.php",
            "language": "alarms",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "td_nowrap left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "center"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px"},
                    { "bSortable": false, "sClass": "left td_nowrap", "sWidth": "70px"},
                    { "bSortable": false, "sClass": "left td_nowrap", "sWidth": "70px"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px"}
                ]
            },
            "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
            {
                var id    = aData['DT_RowId'];
                var $cell = null;
                
                $cell = $("td:nth-child(6)", nRow).empty();
                if (aData[5] != '')
                {
                    $('<img></img>',
                    {
                        "class" : "otx_icon pointer",
                        "src"   : aData[5],
                        "click" : function(e)
                        {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            var title   = "<?php echo Util::js_entities(_('OTX Pulse')) ?>";
                            var url     = "/ossim/otx/views/view_my_pulses.php?type=alarm&id=" + id;
                            
                            GB_show(title, url, 600, '65%');
    
                        }
                    }).appendTo($cell);
                }
                else
                {
                    $cell.html("<?php echo _('N/A') ?>");
                }
                
                $cell = $("td:nth-child(9)", nRow).empty();
                $('<img></img>',
                {
                    "class" : "avt_img",
                    "src"   : "/ossim/pixmaps/show_details.png",
                    "click" : function(e)
                    {
                        e.preventDefault();
                        e.stopPropagation();

                        load_detail(id);
                    }
                }).appendTo($cell);
                
                if ($('td:eq(3)',nRow).html() == '')
                {
                    $('td:eq(2)',nRow).attr('colspan', 2);
                    $('td:eq(3)',nRow).hide();
                }

            },
            "on_row_dbl_click": function(ui, row)
            {
                load_detail(row.attr('id'));
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            }
        });
        
        
        /*
         * Function to go to the alarm detail.
         *
         * @param  id   Alarm ID. 
         */
        function load_detail(id)
        {
            var url    = __self.cfg.ossim + '/alarm/alarm_detail.php?backlog='+urlencode(id);
            var p_menu = 'analysis';
            var s_menu = 'alarms';
            var t_menu = 'alarms';

            link(url, p_menu, s_menu, t_menu);
        }
    }




    /*******************************************************************************************************/
    /***************************                SERVICES SECTION                ****************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Services Tab.
     */
    this.load_services = function()
    {
        __self.av_tables['services'] = $("[data-bind='av_table_services']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_services.php",
            "language": "services",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "center", "sWidth": "35px", "bVisible": false},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "center"},
                    { "bSortable": false, "sClass": "center"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px", "bVisible": false}
                ],
                "aaSorting": [[ 2, "asc" ]]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            },
            "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
            {
                $("td:last-child", nRow).empty()

                if (aData['DT_RowData']['nagios'] == 0)
                {
                    var nagios_enabled = '<?php echo _('No')?>';
                }
                else
                {
                    var nagios_enabled = '<?php echo _('Yes')?>';
                }

                $("td:last-child", nRow).html(nagios_enabled);
            },
            "on_finish_draw": function(ui, oSettings, json)
            {
                var elem = ui.dt_actions;

                if (__self.o.permissions['edit'] && __self.o.asset_type == 'asset')
                {
                    $('<button></button>',
                    {
                        "text"     : "<?php echo _('Edit Services') ?>",
                        "class"    : "av_b_secondary small avt_action",
                        "click"    : function()
                        {
                            edit_services();
                        }
                    }).appendTo(elem);
                }
            }
        });

        
        /*
         * Function to edit the services of an asset.
         */
        function edit_services()
        {
            var url   = __self.cfg.asset.views + "asset_form.php?id="+ __self.o.asset_id +"&c_tab=services";
            var title = "<?php echo _('Edit Services') ?>";

            GB_show(title, url, '80%', '850');

            return false;
        }

    }




    /*******************************************************************************************************/
    /****************************                PLUGIN SECTION                *****************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Plugin Tab.
     */
    this.load_plugins = function()
    {
        __self.av_tables['plugins'] = $("[data-bind='av_table_plugins']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_plugins.php",
            "language": "plugins",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "bLengthChange": (__self.o.asset_type == 'asset') ? false : true,
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "left", "bVisible": (__self.o.asset_type == 'asset') ? false : true },
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "center"}
                ]
            },
            "on_complete_ajax": function(ui)
            {
                if (__self.o.asset_type == 'asset')
                {
                    $(ui).find('.dt_footer').hide();
                }
                
                __self.av_tabs.show_selected_tab();
            },
            "on_finish_draw": function(ui, oSettings, json)
            {
                if (__self.o.permissions['admin'] && __self.o.permissions['plugins'])
                {
                    var elem = ui.dt_actions;
                    var name = "<?php echo _('Edit Plugins') ?>";

                    $('<button></button>',
                    {
                        "text"     : name,
                        "class"    : "av_b_secondary small avt_action",
                        "click"    : function()
                        {
                            edit_plugins();

                        }
                    }).appendTo(elem);
                }
            }
        });


        /*
         * Function to edit the plugins.
         */
        function edit_plugins()
        {
            var url   = __self.cfg.common.views + "edit_plugins.php?asset_type=" + __self.o.asset_type + "&asset_id=" + __self.o.asset_id;
            var title = "<?php echo Util::js_entities(_('Edit Plugins')) ?>";

            GB_show(title, url, '80%', '850');
        }
    }




    /*******************************************************************************************************/
    /****************************                EVENT SECTION                ******************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Event Tab.
     */
    this.load_events = function()
    {
        __self.av_tables['events'] = $("[data-bind='av_table_events']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_events.php",
            "language": "events",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left td_nowrap", "sWidth": "70px"},
                    { "bSortable": false, "sClass": "left td_nowrap", "sWidth": "70px"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "center"},
                    { "bSortable": false, "sClass": "center", "sWidth": "35px"}
                ],
                "aaSorting": [[ 0, "desc" ]]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            },
            "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
            {
                var id = aData['DT_RowId'];

                $('<img></img>',
                {
                    "class" : "avt_img",
                    "src"   : "/ossim/pixmaps/show_details.png",
                    "click" : function(e)
                    {
                        e.preventDefault();
                        e.stopPropagation();

                        load_detail(id);
                    }
                }).appendTo($("td:nth-child(7)", nRow));
            },
            "on_row_dbl_click": function(ui, row)
            {
                load_detail(row.attr('id'));
            },
        });


        /*
         * Function to load the event detail.
         *
         * @param  id   Event ID. 
         */
        function load_detail(id)
        {
            var title  = "<?php echo Util::js_entities(_('Event Detail')) ?>";
            var url    = __self.cfg.ossim + '/forensics/base_qry_alert.php?submit=%230-'+urlencode(id)+'&clear_allcriteria=1&minimal_view=1&noback=1';

            GB_show(title, url, '70%', '80%');
        }
    }

    /*******************************************************************************************************/
    /***************************                PROPERTY SECTION                ****************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Property Tab.
     */
    this.load_properties = function()
    {
        __self.av_tables['properties'] = $("[data-bind='av_table_properties']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_properties.php",
            "language": "properties",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "center", "sWidth": "35px", "bVisible": false},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px", "bVisible": false}
                ],
                "aaSorting": [[ 4, "desc" ]]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            },
            "on_finish_draw": function(ui, oSettings, json)
            {
                if (__self.o.permissions['edit'])
                {
                    var elem = ui.dt_actions;

                    $('<button></button>',
                    {
                        "text" : "<?php echo _('Edit Properties') ?>",
                        "class": "av_b_secondary small avt_action",
                        "click": function()
                        {
                            if (__self.o.asset_type == 'asset')
                            {
                                edit_properties();
                            }
                            else
                            {
                                edit_bulk_properties();
                            }
                        },
                        "data-selection": "avt_action"
                    }).appendTo(elem);
                }
            }
        });

        
        /*
         * Function to load the properties in bulk mode.
         */
        function edit_bulk_properties()
        {
            __self.save_members('properties').done(function(data)
            {
                var url   = __self.cfg.asset.views + "asset_form.php?edition_type=bulk&c_tab=properties&hide_tab_list=1"
                var title = "<?php echo _('Edit Properties') ?>";

                GB_show(title, url, '80%', '850');
            });
        }

        
        /*
         * Function to load the properties of a single asset.
         */
        function edit_properties()
        {
            var url   = __self.cfg.asset.views + "asset_form.php?id="+ __self.o.asset_id + "&c_tab=properties&hide_tab_list=1";
            var title = "<?php echo _('Edit Properties') ?>";

            GB_show(title, url, '80%', '850');
        }
    }




    /*******************************************************************************************************/
    /***************************                SOFTWARE SECTION                *****************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Software Tab.
     */
    this.load_software = function()
    {
        __self.av_tables['software'] = $("[data-bind='av_table_software']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_software.php",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "center", "sWidth": "35px", "bVisible": false},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px", "bVisible": false}
                ],
                "aaSorting": [[ 3, "desc" ]]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            },
            "on_finish_draw": function(ui, oSettings, json)
            {
                if (__self.o.permissions['edit'])
                {
                    var elem = ui.dt_actions;

                    $('<button></button>',
                    {
                        "text" : "<?php echo _('Edit Software') ?>",
                        "class": "av_b_secondary small avt_action",
                        "click": function()
                        {
                            if (__self.o.asset_type == 'asset')
                            {
                                edit_software();
                            }
                            else
                            {
                                edit_bulk_software();
                            }
                        },
                        "data-selection": "avt_action"
                    }).appendTo(elem);
                }
            }
        });


        /*
         * Function to load the software in bulk mode.
         */
        function edit_bulk_software()
        {
            __self.save_members('software').done(function(data)
            {
                var url = __self.cfg.asset.views + "asset_form.php?edition_type=bulk&c_tab=software&hide_tab_list=1";

                var title = "<?php echo _('Edit Software') ?>";

                GB_show(title, url, '80%', '850');

            });
        }

        
        /*
         * Function to load the software of a single asset.
         */
        function edit_software()
        {
            var url = __self.cfg.asset.views + "asset_form.php?id="+ __self.o.asset_id + "&c_tab=software&hide_tab_list=1";

            var title = "<?php echo _('Edit Software') ?>";

            GB_show(title, url, '80%', '850');
        }
    }




    /*******************************************************************************************************/
    /***************************                NETFLOW SECTION                *****************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the Netflow Tab.
     */
    this.load_netflows = function()
    {
        __self.av_tables['netflows'] = $("[data-bind='av_table_netflows']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_netflows.php",
            "language": "netflows",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "center"}
                ]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            }
        });
    }




    /*******************************************************************************************************/
    /***************************                HISTORY SECTION                *****************************/
    /*******************************************************************************************************/


    /*
     * Function to Load the History Tab.
     */
    this.load_history = function()
    {
        __self.av_tables['history'] = $("[data-bind='av_table_history']").AV_table(
        {
            "ajax_url": __self.cfg.common.providers + "dt_history.php",
            "load_params":
            [
                {"name": "asset_id",   "value": __self.o.asset_id},
                {"name": "asset_type", "value": __self.o.asset_type}
            ],
            "dt_params":
            {
                "aoColumns":
                [
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"}
                ]
            },
            "on_complete_ajax": function()
            {
                __self.av_tabs.show_selected_tab();
            }
        });
    }


    /*******************************************************************************************************/
    /**************************                PRIVATE FUNCTIONS                ****************************/
    /*******************************************************************************************************/
    
    /*
     * Function to load a link using the menu options.
     *
     * @param  url     Url. 
     * @param  p_menu  Primary Menu. 
     * @param  s_menu  Secondary Menu. 
     * @param  t_menu  Tertiary Menu. 
     */
    function link(url, p_menu, s_menu, t_menu)
    {
        try
        {
            url = top.av_menu.get_menu_url(url, p_menu, s_menu, t_menu);
            top.av_menu.load_content(url);
        }
        catch(Err)
        {
            document.location.href = url

        }

        return false;
    }
    
}
