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

function alarm_detail(alarm, perms)
{
    this.backlog_id     = alarm.backlog_id || '';
    this.event_id       = alarm.event_id || '';
    this.engine         = alarm.engine || '';
    this.agent_ctx      = alarm.agent_ctx || '';
    
    this.plugin_id      = alarm.plugin_id || '';
    this.plugin_sid     = alarm.plugin_sid || '';
    this.sid_name       = alarm.sid_name || '';
    this.taxonomy       = alarm.taxonomy || [];
    
    this.status         = alarm.status || '';
    this.risk           = alarm.risk || '';
    this.risk_text      = alarm.risk_text || '';
    this.attack_pattern = alarm.attack_pattern || '';
    this.created        = alarm.created || '';
    this.duration       = alarm.duration || '';
    this.events         = alarm.events || '';
    this.otx_icon       = alarm.otx_icon || '';
    this.iocs           = alarm.iocs || 0;
    
    this.event_start    = alarm.event_start || '';
    this.event_end      = alarm.event_end || '';
    this.src_ips        = alarm.src_ips || '';
    this.dst_ips        = alarm.dst_ips || '';
    this.src_ports      = alarm.src_ports || '';
    this.dst_ports      = alarm.dst_ports || '';
       
    this.sources        = alarm.sources || [];
    this.destinations   = alarm.destinations || [];
    
    this.tags           = alarm.tags || {};
    
    this.perms          = $.extend(
    {
        "admin": false,
        "pro"  : false
    }, perms || {});
    
    var __box_tabs      = {};
    var __alarm_url     = <?php echo json_encode(Alarm::get_alarm_path()) ?>;
    var __asset_url     = <?php echo Asset::get_path_url() ?>;
    var __confirm_keys  = 
    {
        "yes": "<?php echo Util::js_entities(_('Yes')) ?>",
        "no" : "<?php echo Util::js_entities(_('No')) ?>"
    };
    
    var __otx_url = "<?php echo Reputation::getlabslink('XXXX') ?>";
    
    var self = this;
    
    this.init = function()
    {      
        //Setting the config for ajax request errors.
        $.ajaxSetup(
        {
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
        
        
        try
        {
            top.av_menu.set_bookmark_params(self.backlog_id);
            
            if (parent.is_lightbox_loaded(window.name))
            {
                $('[data-alarm="main-wrapper"]').addClass('with_lb');
            }
        }
        catch (Err){}
            
        
        self.build_alarm_name();
        
        self.load_alarm_summary();
        
        self.initialize_alarm_assets();
        
        self.load_tabs();
        
        self.load_breadcrumb();
        
        self.load_actions();
        
        self.load_alarm_tags();
        
    }
       
       
    this.build_alarm_name = function()
    {       
        var tooltip, d_url = '';
        
        if (self.plugin_id == 1505 && self.plugin_sid != '')
        {
            if (self.plugin_id > 500000 && self.taxonomy['id'] == '')
            {
                tooltip = "<?php echo _('Add Intent & Strategy & Method Metadata') ?>";
            }
            else
            {
                tooltip = "<?php echo _('Directive ID: ') ?>" + self.plugin_sid ;
            }
        
            if(self.plugin_sid > 500000)
            {
                d_url = "/ossim/directives/wizard_directive.php?engine_id=" + self.engine + "&directive_id=" + self.plugin_sid;
            }
        }
        else
        {
            d_url   = "/ossim/directives/wizard_directive.php?engine_id=" + self.engine;
            tooltip = "<?php echo _('Create a Directive for this Alarm') ?>";
        } 
        
        var $name = $('[data-alarm="name"]').empty().attr('title', tooltip).tipTip()
        
        if (self.taxonomy['id'])
        {
            $name.append('<img src="/ossim/alarm/style/img/'+ self.taxonomy['id'] +'.png" class="img_intent"/> ');
            
            $('<a/>',
            {
                'class': 'alarm_name cursor_default',
                'html' : self.taxonomy['category'] + ' &mdash; ' + self.taxonomy['subcategory']
            }).appendTo($name);
        }
        else
        {
            $name.html(self.sid_name).addClass('alarm_name pointer');
        }
        
        $name.off('click')
        .on('click', function()
        {
            self.load_directive_editor(d_url);
        });
    }
    
    
    this.load_alarm_summary = function()
    {
        if (self.status == 'correlating')
        {
            $cor = $('<img></img>',
            {
               "src"  : "/ossim/alarm/style/img/correlating.gif", 
               "class": "corr_img",
               "title": "<?php echo _('This alarm is still being correlated and therefore it can not be modified.') ?>"
            }).tipTip();
            
            $('[data-alarm="status"]').html($cor).addClass('c_img');
        }
        else if (self.status == 'closed')
        {
            $('[data-alarm="status"]').html("<?php echo _('Closed') ?>");
        }
        else
        {
            $('[data-alarm="status"]').html("<?php echo _('Open') ?>");
        }
        $('[data-alarm="risk_text"]').html(self.risk_text);
        $('[data-alarm="attack_pattern"]').text(self.attack_pattern);
        $('[data-alarm="created"]').html(self.created);
        $('[data-alarm="duration"]').html(self.duration);
        $('[data-alarm="events"]').text($.number(self.events));
        
        var $otx_cell = $('[data-alarm="otx"]').empty();
        if (self.iocs > 0)
        {
            $('<a/>',
            {
                'click': self.open_otx,
                'text' : $.number(self.iocs)
            }).appendTo($otx_cell);
        }
        else
        {
            $otx_cell.text(0);
        }
        
        if (self.otx_icon)
        {
            $('[data-alarm="otx-icon"]').attr('src', self.otx_icon).show();
        }
        else
        {
            $('[data-alarm="otx-icon"]').hide();
        }
    }
    
    
    this.load_tabs = function()
    {
        var sections =
        {
            "id"       : 'alarm_tabs',
            "selected" : 0,
            "hide"     : 0,
            "tabs"     :
            [
                {
                    "id"   : "alarm_tabs",
                    "name" : "<?php echo Util::js_entities(_('Events')) ?>",
                    "href" : __alarm_url['view'] + "alarm_event_list.php?backlog_id="+ self.backlog_id +"&show_all=2&box=1&hide=directive",
                    "hide" : false,
                    "load_callback": function()
                    {
                        tabs.show_selected_tab();
                    }
                }
            ]
        };
        
        var tabs = new Av_tabs(sections)
        tabs.draw_tabs();
    }
    
    
    this.load_breadcrumb = function()
    {
        var items = 
        {
            'all'   : {'title': "<?php echo _('Alarms') ?>", 'action': self.go_back},
            'alarm' : {'title': self.sid_name, 'action': ''}
        };
                
        $('[data-alarm="breadcrumb"]').AVbreadcrumb(
        {
            'items': items
        });
    }
    
    
    this.load_actions = function()
    {
        var actions = 
        [
            {
                "name": "<?php echo Util::js_entities(_('Create Ticket')) ?>",
                "show": true,
                "action": self.create_ticket
            },
            <?php if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsClose") ) { ?>
            {
                "name": "<?php echo Util::js_entities(_('Close Alarm')) ?>",
                "show": (self.status == 'open'),
                "action": self.close_alarm
            },
            <?php } ?>
            {
                "name": "<?php echo Util::js_entities(_('Open Alarm')) ?>",
                "show": (self.status == 'closed'),
                "action": self.open_alarm
            },
            <?php if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete") ) { ?>
            {
                "name": "<?php echo Util::js_entities(_('Delete Alarm')) ?>",
                "show": (self.status != 'correlating'),
                "action": self.delete_alarm
            },
            <?php } ?>
            {
                "name": "<?php echo Util::js_entities(_('Add Label')) ?>",
                "show": false,
                "action": self.add_label
            },
            {
                "name": "<?php echo Util::js_entities(_('Learn More')) ?>",
                "show": true,
                "action": self.learn_more
            }
        ];
        
        var $dd = $('[data-alarm="dropdown-actions"]').empty();
        $.each(actions, function(i,v)
        {
            if (v.show)
            {
                var $li = $('<li/>').appendTo($dd);
                
                $('<a/>',
                {
                    "text" : v.name,
                    "click": v.action
                }).appendTo($li);
            }
        })
    }
    
    
    this.initialize_alarm_assets = function()
    {        
        $.each(['dst', 'src'], function(_i, type)
        {
            var data_assets = (type == 'src') ? self.sources : self.destinations
            var total_ips   = Object.keys(data_assets).length;
                        
            $('[data-alarm="total-'+ type +'"]').text(total_ips);

            var $ip_cell = $('[data-alarm="select-'+ type +'"]').empty();
            if (total_ips == 1)
            {
                var ip = Object.keys(data_assets)[0];
                var id = data_assets[ip]['uuid'];
                
                $ip_cell.html('<strong>' + ip + '</strong>');
                self.load_asset_data(type, id, ip);
            }
            else
            {
                var $select = $('<select/>').appendTo($ip_cell);
                
                $.each(data_assets, function(i,v)
                {
                    $('<option/>',
                    {
                        "text": i
                    }).data({"ip": i, "id": v.uuid}).appendTo($select);
                })
                
                $select.off('change').on('change', function(a, b)
                {
                    var option = $("option:selected", this);
                    var id     = $(option).data('id');
                    var ip     = $(option).data('ip');
                    
                    self.load_asset_data(type, id, ip);     
                    
                }).select2(
                {
                    'hideSearchBox' : (total_ips < 5)
                }).trigger('change');
            }
            
        });
    }
    
    
    this.load_asset_data = function(type, id, ip)
    {
        $.ajax(
        {
            type: "POST",
            url: __alarm_url['provider'] + "alarm_asset_info.php",
            data: {"backlog_id": self.backlog_id, "asset_ip": ip, "asset_id": id},
            dataType: "json",
            success: function(data)
            {
                self.load_box(type, data);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                var error = XMLHttpRequest.responseText;
                show_notification('alarm_notification', error, 'nf_error', 5000, true);
            }
        });
    }
    
    
    this.load_box = function(type, asset)
    {              
        //Getting the box: src or dst
        var $box = $('[data-alarm="box-'+ type +'"]').empty(); 

        //Cloning template into box
        $('[data-alarm="box-template"]').contents().clone().appendTo($box).show();


        format_name();
        
        format_location();
        
        format_groups();
        
        format_networks();
        
        format_ip_reputation();
        
        format_extra_details();
        
        
        //Section Tabs
        
        //Adding ID to load the tabs.
        $box.find('[data-alarm="asset-tab"]').attr('id', 'asset_tab_' + type);
        var from_inventory = (typeof asset.id == 'string' && asset.id.length > 0);
        
        var sections =
        {
            "id"       : 'asset_tab_' + type,
            "selected" : 0,
            "hide"     : 0,
            "tabs"     :
            [
                {
                    "id"   : "tab_vulnerabilities_" + type,
                    "name" : "<?php echo Util::js_entities(_('Vulnerabilities')) ?>",
                    "href" : __asset_url.common.templates + "tpl_dt_vulnerabilities.php",
                    "hide" : !from_inventory,
                    "load_callback": function()
                    {
                        load_vulnerabilities();
                    }
                },
                {
                    "id"   : "tab_ports_" + type,
                    "name" : "<?php echo Util::js_entities(_('Open Ports')) ?>",
                    "href" : __alarm_url.template + "tpl_tab_ports.php",
                    "hide" : false,
                    "load_callback": function()
                    {
                        load_ports();
                    }
                },
                {
                    "id"   : "tab_properties_" + type,
                    "name" : "<?php echo Util::js_entities(_('Properties')) ?>",
                    "href" : __asset_url.common.templates + "tpl_dt_properties.php",
                    "hide" : !from_inventory,
                    "load_callback": function()
                    {
                        load_properties();
                    }
                },
                {
                    "id"   : "tab_notes_" + type,
                    "name" : "<?php echo Util::js_entities(_('Notes')) ?>",
                    "href" : __alarm_url.template + "tpl_tab_notes.php",
                    "hide" : !from_inventory,
                    "load_callback": function()
                    {
                        load_notes();
                    }
                }
            ]
        };
            
        __box_tabs[type] = new Av_tabs(sections);
        __box_tabs[type].draw_tabs();   
        
            
        /*  Box Functions  */
        
        function format_name()
        {
            var $name = $('<span>',
            {
                'class': 'HostReportMenu',
                'text' : asset.name,
                'id'   : asset.ip + ';' + asset.name + ';' + asset.id
            }).appendTo($box.find('[data-alarm="asset-name"]'));
                
            if (asset.id)
            {
                $name.addClass('av_link')
                .text(asset.name + ' (' + asset.ip + ')')
                .off('click').on('click', function()
                {
                    var url = '/ossim/av_asset/common/views/detail.php?asset_id=' + asset.id;
                    link(url, 'environment', 'assets', 'assets');
                });
            }
            else
            {
                $name.attr('ctx', self.agent_ctx)
            }
        }
        
        
        function format_location()
        {
            var $loc = $box.find('[data-alarm="asset-location"]').find('[data-bind="val"]');
            
            if (asset.location.country)
            {
                if (asset.location.flag)
                {
                    $('<img/>',
                    {
                        'src'  : asset.location.flag,
                        'class': 'alarm_flag'
                    }).appendTo($loc);
                }
                $loc.append(asset.location.country);
            }
            else
            {
                set_unknown($loc);
            }
        }
        
        
        function format_groups()
        {
            var $g = $box.find('[data-alarm="group-list"]').find('[data-bind="val"]');
            
            if (Object.keys(asset.groups).length > 0)
            {
                $.each(asset.groups, function(i, n)
                {
                    $('<div/>',
                    {
                        'class': 'fleft av_link alarm_box_group' ,
                        'text' : n.name,
                        'click': function()
                        {
                            var url = '/ossim/av_asset/common/views/detail.php?asset_id=' + i;
                            link(url, 'environment', 'assets', 'asset_groups');
                        }
                    }).appendTo($g);
                });
                
                $g.show_more({items_to_show: 5, display_button: 'outside'});
            }
            else
            {
                set_unknown($g);
            }
        }
        
        
        function format_networks()
        {
            var $n = $box.find('[data-alarm="network-list"]').find('[data-bind="val"]');
            
            if (Object.keys(asset.nets).length > 0)
            {
                $.each(asset.nets, function(i, g)
                {
                    $('<span/>',
                    {
                        'class': 'fleft av_link alarm_box_net',
                        'text' : g.name,
                        'click': function()
                        {
                            var url = '/ossim/av_asset/common/views/detail.php?asset_id=' + i;
                            link(url, 'environment', 'assets', 'networks');
                        }
                    }).appendTo($n);
                });
                
                $n.show_more({items_to_show: 5, display_button: 'outside'});
            }
            else
            {
                set_unknown($n);
            }
        }
        
        
        function format_ip_reputation()
        {
            var $n = $box.find('[data-alarm="ip-reputation"]').find('[data-bind="val"]');
            
            if (asset.reputation)
            {
                var url  = __otx_url.replace('XXXX', asset.ip);
                        
                $('<a/>', 
                {
                    "href"  : url,
                    "target": "_blank",
                    "html"  : "<?php echo _('Yes') ?>"
                }).appendTo($n);
            }
            else
            {
                $n.html("<?php echo _('No') ?>");
            }
        }
        
        
        function format_extra_details()
        {
            var $extra_url = $box.find('[data-alarm="extra-url"]');
            $extra_url.attr('href', function()
            {
                return $(this).attr('href').replace('###IP###', asset.ip)
            });
                        
            $box.find('[data-alarm="extra-siem"]').off('click').on('click', function()
            {
                var dir = (type == 'src') ? 'Src' : 'Dst';
                var url = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&search_str="+ asset.ip +"&submit="+ dir +"+IP";
                
                link(url, 'analysis', 'security_events', 'security_events');
            });
            
            if(self.perms['pro'])
            {
                $box.find('[data-alarm="extra-logger"]').off('click').on('click', function()
                {
                    var url = '/ossim/sem/index.php?query=ip%3D' + asset.ip;
                    link(url, 'analysis', 'raw_logs', 'raw_logs');
                });
            }
            else
            {
                $box.find('[data-alarm="extra-logger"]').off('click').addClass('av_l_disabled');
            }

        }


        function load_vulnerabilities()
        {
            $box.find("[data-bind='av_table_vulnerabilities']").AV_table(
            {
                "ajax_url": __asset_url.common.providers + "dt_vulnerabilities.php",
                "language": "vulnerabilities",
                "num_rows": 5,
                "load_params":
                [
                    {"name": "asset_id",   "value": asset.id},
                    {"name": "asset_type", "value": 'asset'}
                ],
                "dt_params":
                {
                    "aLengthMenu"  : [5, 10, 20],
                    "bLengthChange": true,
                    "aoColumns":
                    [
                        {"bSortable": true,  "sClass": "left"},
                        {"bVisible": false},
                        {"bSortable": true,  "sClass": "left dt_force_wrap"},
                        {"bSortable": true,  "sClass": "left"},
                        {"bSortable": true,  "sClass": "left dt_force_wrap"},
                        {"bSortable": true,  "sClass": "left"}
                    ]
                },
                "on_complete_ajax": function()
                {
                    __box_tabs[type].show_selected_tab();
                }
            });
        }
        
        
        function load_properties()
        {
            $box.find("[data-bind='av_table_properties']").AV_table(
            {
                "ajax_url": __asset_url.common.providers + "dt_properties.php",
                "language": "properties",
                "num_rows": 5,
                "load_params":
                [
                    {"name": "asset_id",   "value": asset.id},
                    {"name": "asset_type", "value": 'asset'}
                ],
                "dt_params":
                {
                    "aLengthMenu"  : [5, 10, 20],
                    "bLengthChange": true,
                    "aoColumns":
                    [
                        {"bVisible": false},
                        {"bVisible": false},
                        {"bSortable": true,  "sClass": "left"},
                        {"bSortable": true,  "sClass": "left"},
                        {"bSortable": true,  "sClass": "left"},
                        {"bSortable": true,  "sClass": "left"},
                        {"bVisible": false}
                    ]
                },
                "on_complete_ajax": function()
                {
                    __box_tabs[type].show_selected_tab();
                }
            });
        }
        
        
        function load_ports()
        {
            $box.find("[data-bind='av_table_alarm_ports']").AV_table(
            {
                "ajax_url"   : __alarm_url.provider + "dt_alarm_asset_port.php",
                "language"   : "ports",
                "num_rows"   : 5,
                "load_params":
                [
                    {"name": "backlog_id", "value": self.backlog_id},
                    {"name": "asset_ip",   "value": asset.ip},
                    {"name": "source",     "value": type},
                ],
                "dt_params":
                {
                    "aLengthMenu"  : [5, 10, 20],
                    "bLengthChange": true,
                    "aoColumns":
                    [
                        {"bSortable": true,  "sClass": "left"},
                        {"bSortable": true,  "sClass": "left"}
                    ]
                },
                "on_complete_ajax": function()
                {
                    __box_tabs[type].show_selected_tab();
                }
            });
        }
        
        function load_notes()
        {
            $box.find('[data-alarm="iframe-note"]')
            .off('load').on('load', function()
            {
                __box_tabs[type].show_selected_tab();
            })
            .attr("src", "/ossim/av_asset/common/views/notes.php?asset_type=asset&asset_id=" + asset.id);
            
        }
    }
    
    
    this.load_alarm_tags = function()
    {
        var $label_container = $('[data-alarm="label-container"]');
        
        //Callback to load the Show More Plugin.
        var __reload_show_more = function ()
        {
            $label_container.show_more('reload');
        };
        
        $.each(self.tags, function(i, tag)
        {
            var $label = draw_tag(tag, self.backlog_id, __reload_show_more);
            $label_container.append($label);
        });
        
        $label_container.show_more({items_to_show: 10, display_button: 'outside'});
        
        
        var options =
        {
            'load_tags_url'         : '<?php echo AV_MAIN_PATH?>/tags/providers/get_dropdown_tags.php',
            'manage_components_url' : '<?php echo AV_MAIN_PATH?>/tags/controllers/tag_components_actions.php',
            'allow_edit'            : self.perms['admin'],
            'select_component_mode' : 'single',
            'component_id'          : self.backlog_id,
            'tag_type'              : 'alarm',
            'show_tray_triangle'    : true,
            'on_save': function (status, data)
            {
                if (status == 'OK')
                {
                    var $label = draw_tag(data, self.backlog_id, __reload_show_more);
        
                    $label.appendTo($label_container);
                    $label_container.show_more('reload');
                }
                else
                {
                    show_notification('alarm_notification', data, 'nf_error', 20000, true);
                }
            },
            'on_delete': function (status, data)
            {
                if (status == 'OK')
                {
                    $('[data-tag-id="' + data.id + '"]').remove();
                    $label_container.show_more('reload');
                }
                else
                {
                    show_notification('alarm_notification', data, 'nf_error', 20000, true);
                }
            }
        };
        
        $('[data-alarm="label-selection"]').av_dropdown_tag(options);
    }
       
    
    this.close_alarm = function()
    {
        var msg = "<?php echo _('Are you sure you want to close this alarm?') ?>";
        av_confirm(msg, __confirm_keys).done(function()
        {
            self.modify_alarm(1).done(function()
            {
                self.status = 'closed';
                self.load_alarm_summary();
                self.load_actions();
            });
        });
    };
    
    
    this.open_alarm = function()
    {
        var msg = "<?php echo _('Are you sure you want to open this alarm?') ?>";
        av_confirm(msg, __confirm_keys).done(function()
        {
            self.modify_alarm(2).done(function()
            {
                self.status = 'open';
                self.load_alarm_summary();
                self.load_actions();
            });
        });
    };
    
    
    this.delete_alarm = function()
    {
        var msg = "<?php echo _('Are you sure you want to delete this alarm?') ?>";
        av_confirm(msg, __confirm_keys).done(function()
        {
            self.modify_alarm(6).done(function()
            {
                self.go_back();
            });
        });
    };
       
    
    this.modify_alarm = function(action)
    {
        var atoken = Token.get_token("alarm_operations");
        
        return $.ajax(
        {
            data:  {"action": action, "data": {"id": self.backlog_id}},
            type: "POST",
            url : __alarm_url['controller'] + "alarm_actions.php?token="+atoken,
            dataType: "json",
            success: function(data) 
            {
                if (data.error)
                {
                    show_notification('alarm_notification', data.msg, 'nf_error', 15000, true);
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) 
            {
                show_notification('alarm_notification', textStatus, 'nf_error', 15000, true);
            }
        });
    }
    
    
    this.create_ticket = function()
    {
        var title = "<?php echo _('New Ticket') ?>";
        var url   = "/ossim/incidents/newincident.php?ref=Alarm&title=" + urlencode(self.sid_name) + "&priority=" + self.risk + "&event_start=" + self.event_start + "&event_end=" + self.event_end +  "&src_ips=" + self.src_ips + "&dst_ips=" + self.dst_ips + "&src_ports=" + self.src_ports +  "&dst_ports=" + self.dst_ports + "&backlog_id=" + self.backlog_id + "&event_id=" + self.event_id;
        
        GB_show(title, url, 490, '90%');
    };
    
    
    this.learn_more = function()
    {
        var title = "<?php echo _('Knowledge Base') ?>";
        var url   = __alarm_url['view'] + "alarm_kdb.php?backlog_id=" + self.backlog_id;
        
        GB_show(title, url, '70%','70%');
    };
    
    
    this.add_label = function(){};
    
    
    this.open_otx = function()
    {
        var title = "<?php echo _('OTX DETAILS') ?>";
        var url   = "/ossim/otx/views/view_my_pulses.php?type=alarm&id=" + self.backlog_id;
        GB_show(title, url, '70%','70%');
    }
    
    
    this.load_directive_editor = function(url)
    {
        if (url != '')
        {
            var title = "<?php echo _('Directive Editor') ?>";

            GB_show(title, url, 500, '75%');
        }
    }
    
    
    this.go_back = function()
    {
        var url    = "/alarm/alarm_console.php?<?php echo $_SESSION['_alarm_criteria'] ?>";
        var p_menu = "analysis";
        var s_menu = "alarms";
        var t_menu = "alarms";
        
        link(url, p_menu, s_menu, t_menu);
    };
    
    
    function set_unknown($elem)
    {
        $elem.html("<span class='unknown'><?php echo Util::js_entities(_('Unknown'))?></span>");
    }
    
    
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
    
    
    this.init();
}



function GB_onhide(url, params)
{
    if (url.match(/base_qry_alert\.php/))
    {
        if (typeof(params) == 'object' && typeof params['url_detail'] != 'undefined')
        {
            try
            {
                top.av_menu.load_content(params['url_detail']);
            }
            catch(Err)
            {
                document.location.href = params['url_detail'];
            }
        }
    }
    else if (url.match(/newincident\.php/))
    {
        var url = "/ossim/incidents/index.php?m_opt=analysis&sm_opt=tickets&h_opt=tickets";
        
        try
        {
            top.av_menu.load_content(url);
        }
        catch(Err)
        {
            document.location.href = url;
        }
    }
    else if (url.match(/wizard_directive\.php/))
    {
        document.location.reload();
    }
}

