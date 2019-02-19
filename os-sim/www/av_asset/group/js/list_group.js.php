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


function av_group_list(perms)
{
    this.asset_type    = 'group';
    this.perms         = perms;
    
    this.allowed_actions =
    [
        "delete",
        "nmap_scan",
        "vulnerability_scan",
        "deploy_hids_agents",
        "enable_monitoring",
        "disable_monitoring",
        "add_note"
    ];
    
    this.add_actions =[];
        

    this.init = function()
    {
        var __self = this;
        
        __self.init_list();
        
        __self.disable_search_inputs();
                        
        __self.table = $("[data-bind='av_table_groups']").AV_table(
        {
            "selectable": true,
            "num_rows"  : <?=isset($_SESSION["per_page"]) ? $_SESSION["per_page"] : 20?>,
            "with_tray" : true,
            "language"  : "groups",
            "ajax_url"  : __self.cfg.group.providers + "load_groups_result.php",
            "dt_params" :
            {
                "aoColumns":
                [
                    {"bSortable": false, "sClass": "center", "sWidth": "30px"},
                    {"bSortable": true,  "sClass": "left", "sWidth": "200px"},
                    {"bSortable": false, "sClass": "left", "sWidth": "200px"},
                    {"bSortable": false, "sClass": "left"},
                    {"bSortable": false, "sClass": "center"},
                    {"bSortable": false, "sClass": "center"},
                    {"bSortable": false, "sClass": "center"},
                    {"bSortable": false, "sClass": "center td_nowrap", "sWidth": "50px"}
                ],
                "aaSorting": [[ 1, "asc" ]]
            },
            "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
            {
                var id    = aData['DT_RowId'];
                var $cell = $("td:last-child", nRow);
                
                $("<img/>",
                {
                    "id"   : "edit_selection",
                    "class": "avt_img",
                    "src"  : "/ossim/pixmaps/edit.png",
                    "click": function(e)
                    {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        __self.edit_group(id);
                    }
                }).appendTo($cell);
                
                $('<img/>',
                {
                    "class": "avt_img",
                    "src"  : "/ossim/pixmaps/show_details.png",
                    "click": function(e)
                    {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        __self.load_asset_detail(id);
                    }
                }).appendTo($cell);
            },
            "on_open_tray": function(ui, row, wrapper)
            {
                wrapper.AV_asset_indicator(
                {
                    'asset_type': __self.asset_type,
                    'asset_id'  : row.attr('id'),
                    'class'     : 'circle_tray',
                    'perms'     : __self.perms
                }).hide();   
            },
            "on_row_dbl_click": function(ui, row)
            {
                __self.load_asset_detail(row.attr('id'));
            },
            "on_complete_ajax": function(ui, jqXHR, textStatus)
            {
                __self.enable_search_inputs();
                __self.update_asset_counter();
            },
            "on_finish_draw": function (ui, oSettings, json)
            {
                var actions = ui.dt_actions;              
                
                __self.init_labels(actions);
                
                $("<button/>",
                {
                    "id"            : "button_action",
                    "class"         : "button avt_action av_b_disabled small",
                    "href"          : "javascript:;",
                    "html"          : "<?php echo _('Actions') ?> &nbsp;&#x25be;",
                    "data-dropdown" : "#dropdown-actions",
                    "data-selection": "avt_action"
                }).appendTo(actions);
        
            },
            "on_action_status_change": function (selected) 
            {
                if (selected == 0)
                {
                    $('[data-selection="avt_action"]').prop('disabled', true).addClass('disabled av_b_disabled');
                }
                else
                {
                    $('[data-selection="avt_action"]').prop('disabled', false).removeClass('disabled av_b_disabled');
                }
            }
        });
        
        
        $('[data-bind="add-asset"]').on('click', function()
        {
            __self.add_asset();
        });
    }
    

    
    /**************************************************************************/
    /*****************************  ADD FUNCTIONS  ****************************/
    /**************************************************************************/
    
    this.add_asset = function()
    {       
        var url   = this.cfg.group.views  + 'new_group.php';
        var title = "<?php echo _('Create New Group') ?>";
        
        GB_show(title, url, '300', '360');
    }
    
    
    /**************************************************************************/
    /***************************  ACTION FUNCTIONS  ***************************/
    /**************************************************************************/
    
    /*  Function to open a lightbox to edit the asset group  */
    this.edit_group = function(asset_id)
    {
        var url   = this.cfg.group.views + "group_form.php?id=" + asset_id;
        var title = "<?php echo _('Edit Group') ?>";

        GB_show(title, url, '50%', '650');

        return false;
        
    }

    /**************************************************************************/
    /***************************  LINKS FUNCTIONS  ****************************/
    /**************************************************************************/
    
    /*  Function to open the group detail */
    this.load_asset_detail = function(id)
    {
        if (typeof id == 'undefined' || id == '')
        {
            return false;
        }
        
        var url = this.cfg.group.detail + '?asset_id='+urlencode(id);
        
        try
        {
            url = top.av_menu.get_menu_url(url, 'environment', 'assets', 'asset_groups');
    	    top.av_menu.load_content(url);
        }
        catch(Err)
        {
            document.location.href = url

        }
    }
    
    
    
    /**************************************************************************/
    /*************************  LIGHTBOX CALLBACKS  ***************************/
    /**************************************************************************/
    
    this.handle_close_lightbox = function(url, params)
    {
        var __self = this;

        if (typeof params == 'undefined')
        {
            params = {};
        }

        var cond_restore = url.match(/extra_filters/);
        var cond_force   = url.match(/edition_type=bulk/);
        var cond_hids    = url.match(/bk_deploy_hids/);
        
        //If we cancel the extra filter Lightbox, we restore the filter object
        if (cond_restore)
        {
            __self.restore_filter_list();
        }
        //If we close the bulk edition form, then we force the reload
        else if (cond_force)
        {
            __self.reload_assets_group(true);
        }
        else if (cond_hids)
        {
            if (typeof(params.action) == 'string' && params.action != '')
            {
                if (params.action == 'agents_deployed')
                {
                    var msg     = params.msg;
                    var nf_type = 'nf_' + params.status;
    
                    show_notification('asset_notif', msg, nf_type, 15000, true);
                }
                else if (params.action == 'show_unsupported')
                {
                    var url = __self.cfg.asset.views + 'list.php';

                    try
                    {
                        url = top.av_menu.get_menu_url(url, 'environment', 'assets', 'assets');
                        top.av_menu.load_content(url);
                    }
                    catch(Err)
                    {
                        document.location.href = url
                    }
                }
            }
        }
    }


    this.handle_hide_lightbox = function(url, params)
    {
        var __self = this;

        if (typeof params == 'undefined')
        {
            params = {};
        }

        var cond_reload = url.match(/extra_filters/);
        var cond_force  = url.match(/group_form/);
        var cond_new    = url.match(/new_group/);
        var cond_notes  = url.match(/bk_add_note/);

        if (cond_new)
        {
            __self.load_asset_detail(params['id']);
        }
        else if (cond_force)
        {
             __self.reload_assets_group(true);
        }
        else if (cond_reload)
        {
            __self.reload_assets_group();
        }
        else if (cond_notes)
        {
            var n_groups = __self.get_num_selected_assets();
            var msg      = "<?php echo Util::js_entities(_('Your note has been added to ### group(s).')) ?> ".replace('###', n_groups);

            show_notification('asset_notif', msg, 'nf_success', 15000, true);
        }
    }
    
    
    /***************  INIT THE LIST ***************/
    this.init();

}

av_group_list.prototype = new av_list;
