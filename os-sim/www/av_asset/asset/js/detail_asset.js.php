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
 *  av_asset_detail Class.
 *
 *  @inherits av_detail.
 */
function av_asset_detail(o)
{
    //Default Detail Options
	opt =
	{
		'id'            : '',
		'section'       : 0,
		'scroll_section': false,
		'perms'         : {}
	};

	//Merging default and custom detail options.
	$.extend(opt, o || {});


	//Asset General Info
    this.info =
    {
        'hostname'     : '',
        'descr'        : '',
        'ips'          : [],
        'icon'         : '',
        'fqdn'         : '',
        'os'           : '',
        'model'        : '',
        'asset_value'  : '',
        'asset_type'   : '',
        'devices_types': [],
        'networks'     : [],
        'sensors'      : []
    }

    //Asset Labels
    this.labels = {}



    /*******************************************************************************************/
    /************************            ACTIONS FUNCITONS             *************************/
    /*******************************************************************************************/


    /*
     *  Function to open the asset netflows section in a new window.
     */
    this.open_netflows = function()
    {
        try
        {
            var ip = Object.keys(this.info.ips)[0];
        }
        catch(Err)
        {
            var ip = '';
        }

        if (ip != '')
        {
            var url = this.cfg.ossim + "nfsen/nfsen.php?tab=2&ip=" + ip;
            link(url, "environment", "netflow", "details");
        }
    }


    /*
     *  Function to load the asset edition lightbox
     */
    this.edit_asset = function()
    {
        var url   = this.cfg.asset.views + "asset_form.php?id=" + this.asset_id + "&asset_type=" + this.asset_type;
        var title = "<?php echo _('Edit Asset') ?>";

        GB_show(title, url, '80%', '850');
    }


    /*
     *  Function to delete the asset.
     */
    this.delete_asset = function()
    {
        var __self = this;
        var token  = Token.get_token("asset_form");
        var data   =
        {
            'action'  : 'delete_asset',
            'token'   : token,
            'asset_id': __self.asset_id
        }

        $.ajax(
        {
            type: "POST",
            url: __self.cfg.asset.controllers + "asset_actions.php",
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


    /*******************************************************************************************/
    /**************************            DRAW FUNCTIONS             **************************/
    /*******************************************************************************************/


    /*
     *  Function to retrieve and draw the general asset info
     */
    this.draw_info = function()
    {
        var __self = this;

        __self.load_info().done(function()
        {
            draw_icon($('[data-bind="asset_icon"]'), __self.info.icon);
            draw_text($('[data-bind="asset_title"]'), __self.info.hostname);
            draw_list($('[data-bind="asset_ip"]'), format_ips(__self.info.ips));
            draw_text($('[data-bind="asset_fqdn"]'), __self.info.fqdn, true);
            draw_text($('[data-bind="asset_os"]'), __self.info.os, true);
            draw_text($('[data-bind="asset_model"]'), __self.info.model);
            draw_text($('[data-bind="asset_type"]'), __self.info.asset_type);
            draw_range($('[data-bind="asset_value"]'), 0, 5, __self.info.asset_value);
            draw_list($('[data-bind="asset_devices"]'), __self.info.devices);
            draw_list($('[data-bind="asset_sensors"]'), format_sensors(__self.info.sensors));
            draw_list($('[data-bind="asset_networks"]'), format_networks(__self.info.networks));
            draw_text($('[data-bind="asset_descr"]'), __self.info.descr);

            //Reloading Actions to load the agent deploy permissions!
            __self.perms['deploy_agent'] = __self.perms['hids'] && __self.info['os'] && __self.info['os'].match(/(^microsoft|windows)/i);
            __self.load_actions();
        });
    }


    /*
     *  Function to Draw the Asset Labels.
     */
    this.draw_labels = function()
    {
        var __self = this;
        var elem   = $('[data-bind="detail_label_container"]');

        __self.load_labels().done(function ()
        {
            elem.show_more('destroy');
            elem.empty();

            $.each(__self.labels, function (index, tag)
            {
                draw_label(elem, tag);
            });

            elem.show_more({items_to_show: 10, display_button: 'outside'});
        });
    };


    /*******************************************************************************************/
    /**************************            EXTRA FUNCTIONS             *************************/
    /*******************************************************************************************/


    /*
     *  Function to Go Back
     *
     * @param  params    Params to load in the url to the asset list.
     */
    this.go_back = function(params)
    {
        var url = this.cfg.asset.views + 'list.php?back=1';

        if (typeof params == 'string' && params != '')
        {
            url += '&' + params;
        }

        link(url, "environment", "assets", "assets");
    }


    /*
     *  Function to reload the sections when actions are performed.
     *
     * @param  url      Lightbox URL
     * @param  params   Data sent through the lightbox.
     */
    this.manage_reload = function(url, params)
    {
        var __self = this;

        if (url.match(/hids/))
        {
            try
            {
                if (typeof(params) == 'object')
                {
                    var action = params.action || '';

                    if (action == 'discover_os')
                    {
                        url = '/ossim/netscan/index.php?action=custom_scan&scan_type=normal&sensor=local&host_id=' + __self.asset_id;

                        link(url, "environment", "assets", "assets");
                    }
                    else if (action == 'go_to_hids')
                    {
                        url = '/ossim/ossec/views/ossec_status/status.php';

                        link(url, "environment", "detection");
                    }
                    else if (action == 'go_to_mc')
                    {
                        url = '/message_center/views/message_center.php';

                        link(url, "message_center", "message_center");
                    }
                    else if (action == 'agent_deployed')
                    {
                        __self.draw_info();
                        __self.load_environment_info();

                        show_notification('asset_notif', params.msg, 'nf_success', 5000, true);
                    }
                }
            }
            catch(Err)
            {
                ;
            }
        }
        else if (url.match(/software/))
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
            __self.av_indicators.load_services();
        }
        else if (url.match(/asset_form/))
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
        else if(url.match(/add_to_group/))
        {
            //Reload Group Tab
            __self.reload_section('group');
            __self.av_indicators.load_groups();
        }
        else if (url.match(/netscan/))
        {
            __self.load_environment_info();
        }
        else if (url.match(/vulnmeter/))
        {
            __self.load_environment_info();
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


    /*
     *  Function to Bind the custom asset handlers.
     */
    this.bind_handlers = function()
    {
        var __self = this;

        if (__self.perms['edit'])
        {
            var options =
            {
                'load_tags_url'        : '<?php echo AV_MAIN_PATH?>/tags/providers/get_dropdown_tags.php',
                'manage_components_url': '<?php echo AV_MAIN_PATH?>/tags/controllers/tag_components_actions.php',
                'allow_edit'           : __self.perms['admin'],
                'tag_type'             : __self.asset_type,
                'select_component_mode': 'single',
                'component_id'         : __self.asset_id,
                'show_tray_triangle'   : true,
                'on_save'              : function (status, data)
                {
                    __self.add_label(status, data);
                },
                'on_delete'            : function (status, data)
                {
                    __self.delete_label(status, data);
                }
            };

            $('#label_selection').av_dropdown_tag(options);

            $("[data-bind='export-asset']").hide();
        }
        else
        {
            $('#label_selection').hide();
        }


        $netflows = $('[data-bind="netflows_link"]').show();
        if (__self.perms['netflows'])
        {
            $netflows.on('click', function()
            {
                __self.open_netflows();
            });
        }
        else
        {
            $netflows.addClass('av_l_disabled');
        }

    }


    /*
     * Function to Init the asset detail.
     *
     * @param  opt    Asset Detail Options
     */
    this.init = function(opt)
    {
        var __self = this;

        this.asset_type = 'asset';
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
                    "id"   : "tab_vulnerabilities",
                    "name" : "<?php echo Util::js_entities(_('Vulnerabilities')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_vulnerabilities.php",
                    "hide" : !this.perms.vulnerabilities,
                    "load_callback": function()
                    {
                        __self.av_sections.load_vulnerabilities();
                    }
                },
                {
                    "id"   : "tab_alarms",
                    "name" : "<?php echo Util::js_entities(_('Alarms')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_alarms.php",
                    "hide" : !this.perms.alarms,
                    "load_callback": function()
                    {
                        __self.av_sections.load_alarms();
                    }
                },
                {
                    "id"   : "tab_events",
                    "name" : "<?php echo Util::js_entities(_('Events')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_events.php",
                    "hide" : !this.perms.events,
                    "load_callback": function()
                    {
                        __self.av_sections.load_events();
                    }
                },
                {
                    "id"   : "tab_software",
                    "name" : "<?php echo Util::js_entities(_('Software')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_software.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_software();
                    }
                },
                {
                    "id"   : "tab_services",
                    "name" : "<?php echo Util::js_entities(_('Services')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_services.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_services();
                    }
                },
                {
                    "id"   : "tab_plugins",
                    "name" : "<?php echo Util::js_entities(_('Plugins')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_plugins.php",
                    "hide" : !this.perms.plugins,
                    "load_callback": function()
                    {
                        __self.av_sections.load_plugins();
                    }
                },
                {
                    "id"   : "tab_properties",
                    "name" : "<?php echo Util::js_entities(_('Properties')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_properties.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_properties();
                    }
                },
                {
                    "id"   : "tab_netflow",
                    "name" : "<?php echo Util::js_entities(_('Netflow')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_netflows.php",
                    "hide" : !this.perms.netflows,
                    "load_callback": function()
                    {
                        __self.av_sections.load_netflows();

                    }
                },
                {
                    "id"   : "tab_groups",
                    "name" : "<?php echo Util::js_entities(_('Groups')) ?>",
                    "href" : this.cfg.common.templates + "tpl_dt_groups.php",
                    "load_callback": function()
                    {
                        __self.av_sections.load_groups(
                        {
                            "on_delete_group": __self.av_indicators.load_groups
                        });
                    }
                }
            ]
        };

        //Actions Allowed in the detail section.
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
                    var msg  = "<?php echo _('Are you sure you want to permanently delete this asset?') ?>";

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
            },
            {
                "id"    : "enable_monitoring",
                "name"  : "<?php echo _('Enable Availability Monitoring') ?>",
                "perms" : "availability",
                "action": function()
                {
                    __self.toggle_monitoring('enable');
                }
            },
            {
                "id"    : "disable_monitoring",
                "name"  : "<?php echo _('Disable Availability Monitoring') ?>",
                "perms" : "availability",
                "action": function()
                {
                    __self.toggle_monitoring('disable');
                }
            },
            {
                "id"    : "deploy_hids",
                "name"  : "<?php echo _('Deploy HIDS Agent') ?>",
                "perms" : "deploy_agent",
                "action": function()
                {
                    __self.deploy_hids();
                }
            }
        ];

        var section = this.translate_tab_section(opt.section);

		this.load_sections(section, opt.scroll_section);
		this.load_suggestions();
        this.detail_init();
        this.draw_labels();
    }

    this.init(opt);
}

//Inherit from av_detail
av_asset_detail.prototype = new av_detail;
