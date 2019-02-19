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
 *  av_network_detail Class.
 *
 *  @inherits av_detail.
 */
function av_network_detail(o)
{
    //Default Detail Options
	opt =
	{
		'id'            : '',
		'section'       : 0,
		'scroll_section': false,
		'perms'         : {}
	};
	
	$.extend(opt, o || {});
	
    this.info =
    {
        'name'     : '',
        'icon'     : '',
        'owner'    : '',
        'cidrs'    : [],
        'sensors'  : []
    }


    
    /*******************************************************************************************/
    /************************            ACTIONS FUNCITONS             *************************/
    /*******************************************************************************************/

    this.edit_asset = function()
    {        		
        var url   = this.cfg.network.views + "net_form.php?id=" + this.asset_id;
        var title = "<?php echo _('Edit Network') ?>";          

        if (!parent.is_lightbox_loaded(window.name))
        {             
            GB_show(title, url, '70%', '750');
        }
        else
        {
            document.location.href = url;
        }
        
        return false;
    }
    
    
    this.delete_asset = function()
    {
        var __self = this;
        var token  = Token.get_token("net_form");
        var data   =
        {
            'action'  : 'delete_net',
            'token'   : token,
            'asset_id': __self.asset_id
        }
        
        $.ajax(
        {
            type: "POST",
            url: __self.cfg.network.controllers + "net_actions.php",
            data: data,
            dataType: "json",
            success: function(data)
            {
                __self.go_back('notif=delete');
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

    this.export_asset = function()
    {
        document.location.href = this.cfg.network.views  + 'export_net.php?net_id=' + this.asset_id;
    }
    
    
    /*******************************************************************************************/
    /**************************            DRAW FUNCTIONS             **************************/
    /*******************************************************************************************/
    
    this.draw_info = function()
    {        
        var __self = this;
               
        __self.load_info().done(function()
        {
            draw_text($('[data-bind="network_title"]'), __self.info.name);
            draw_icon($('[data-bind="network_icon"]'), __self.info.icon);
            draw_list($('[data-bind="network_cidr"]'), __self.info.cidrs);
            draw_range($('[data-bind="network_value"]'), 0, 5, __self.info.asset_value);
            draw_text($('[data-bind="network_owner"]'), __self.info.owner);
            draw_list($('[data-bind="networ_sensors"]'), format_sensors(__self.info.sensors));
            draw_text($('[data-bind="asset_descr"]'), __self.info.descr);
        });
    }
    
    
    /*******************************************************************************************/
    /**************************            EXTRA FUNCTIONS             *************************/
    /*******************************************************************************************/
    
    /*
     *  Function to Go Back   
     */
    this.go_back = function(params)
    {
        var url = this.cfg.network.views + 'list.php?back=1';
        
        if (typeof params == 'string' && params != '')
        {
            url += '&' + params;
        }
        
        link(url, "environment", "assets", "networks");
    }
    
    
    /*
     *  Function to reload the sections when actions are performed.  
     */
    this.manage_reload = function(url, params)
    {
        var __self    = this;
        
        if (url.match(/software/))
        {
            //Reload Software Tab
            __self.reload_section('software');
        }
        else if (url.match(/properties/))
        {
            //Reload Property Tab
            __self.reload_section('properties');
        }
        else if (url.match(/services/))
        {
            //Reload Service Tab
            __self.reload_section('services');
        }
        else if (url.match(/net_form/))
        {
            __self.load_map();
            __self.draw_info();
            
            __self.reload_section('software');
            __self.reload_section('properties');
        }
        else if (url.match(/plugins/))
        {
            //Reload Service Tab
            __self.reload_section('plugins');
        }
        else if(url.match(/tag_manager/))
        {
            __self.draw_labels();
        }
        else if (url.match(/base_qry_alert/))
        {
            if (typeof(params) == 'object' && typeof params['url_detail'] != 'undefined')
            {
                go_to(params['url_detail']);
            }
        }
        
    }
    
    
    
    /*******************************************************************************************/
    /**************************            BINDING & INIT             **************************/
    /*******************************************************************************************/
    
    this.bind_handlers = function()
    {        
        var __self = this;
        
        $("[data-bind='export-asset']").on('click', function()
        {
            __self.export_asset();
        });
    }
    

    this.init = function(opt)
    {
        var __self = this;
        
        this.asset_type = 'network';
        this.asset_id   = opt.id;
        this.perms      = opt.perms;
        this.db         = new av_session_db('db_' + this.asset_type);

        //Section Tabs
        this.sections =
        {
            "id"       : 'detail_sections',
            "selected" : 0,
            "hide"     : 0,
            "tabs"     : 
            [
                {
                    "id"   : "tab_assets",
                    "name" : "<?php echo Util::js_entities(_('Assets'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_assets.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_assets();
                    }
                },
                {
                    "id"   : "tab_vulnerabilities",
                    "name" : "<?php echo Util::js_entities(_('Vulnerabilities'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_vulnerabilities.php",
                    "hide" : !this.perms.vulnerabilities,
                    "load_callback": function()
                    {
                        __self.av_sections.load_vulnerabilities();
                    }
                },
                {
                    "id"   : "tab_alarms",
                    "name" : "<?php echo Util::js_entities(_('Alarms'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_alarms.php",
                    "hide" : !this.perms.alarms,
                    "load_callback": function()
                    {
                        __self.av_sections.load_alarms();
                    }
                },
                {
                    "id"   : "tab_events",
                    "name" : "<?php echo Util::js_entities(_('Events'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_events.php",
                    "hide" : !this.perms.events,
                    "load_callback": function()
                    {
                        __self.av_sections.load_events();
                    }
                },
                {
                    "id"   : "tab_software",
                    "name" : "<?php echo Util::js_entities(_('Software'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_software.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_software();
                    }
                },
                {
                    "id"   : "tab_services",
                    "name" : "<?php echo Util::js_entities(_('Services'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_services.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_services();
                    }
                },
                {
                    "id"   : "tab_plugins",
                    "name" : "<?php echo Util::js_entities(_('Plugins'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_plugins.php",
                    "hide" : !this.perms.plugins,
                    "load_callback": function()
                    {
                        __self.av_sections.load_plugins();
                    }
                },
                {
                    "id"   : "tab_properties",
                    "name" : "<?php echo Util::js_entities(_('Properties'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_properties.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_properties();
                    }
                },
                {
                    "id"   : "tab_netflow",
                    "name" : "<?php echo Util::js_entities(_('Netflow'))?>",
                    "href" : this.cfg.common.templates + "tpl_dt_netflows.php",
                    "hide" : !this.perms.netflows,
                    "load_callback": function()
                    {
                        __self.av_sections.load_netflows();
                        
                    }
                }
            ]
        };
        
        this.actions = 
        [
            {
                "id"    : "edit",
                "name"  : "<?php echo _('Edit') ?>",
                "perms" : "edit",
                "action": function()
                {
                    __self.edit_asset(); 
                }
            },
            {
                "id"    : "delete",
                "name"  : "<?php echo _('Delete') ?>",
                "perms" : "delete",
                "action": function()
                {
                    var msg  = "<?php echo _('Are you sure you want to permanently delete this network?') ?>";
                
                    av_confirm(msg, __confirm_keys).done(function()
                    {
                        __self.delete_asset();
                    });
                }
            },
            {
                "id"    : "nmap_scan",
                "name"  : "<?php echo _('Run Asset Scan') ?>",
                "perms" : "nmap",
                "action": function()
                {
                    __self.asset_scan();
                }
            },
            {
                "id"    : "vulnerability_scan",
                "name"  : "<?php echo _('Run Vulnerability Scan') ?>",
                "perms" : "vulnerabilities",
                "action": function()
                {
                    __self.vuln_scan();
                }
            }
        ];
        
        var section = this.translate_tab_section(opt.section);
		
		this.load_sections(section, opt.scroll_section);
        this.detail_init();    
    }       
    
        
    this.init(opt); 
    
}

av_network_detail.prototype = new av_detail;
