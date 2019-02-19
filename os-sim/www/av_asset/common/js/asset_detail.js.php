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
 *  av_detail Class.
 */
function av_detail()
{
    //Path object configuration.
    this.cfg = <?php echo Asset::get_path_url() ?>;
    //Asset Type.
    this.asset_type = '';
    //Asset ID.
    this.asset_id = '';
    //Circle Indicator Object
    this.av_indicators = null;
    //Circle indicator list to dsplay.
    this.asset_indicators = {};
    //Detail Section Object.
    this.av_sections = null;
    //Section to display.
    this.sections = {};
    //Location Map
    this.av_map = null;
    //Permission Hash.
    this.perms = {};




    /*******************************************************************************************/
    /**************************          INDICATORS SECTION           **************************/
    /*******************************************************************************************/

    /*
     *  Function to Draw the Circle Indicators
     */
    this.draw_indicators = function()
    {
        var __self = this;
        
        __self.av_indicators = $('[data-bind="detail_indicators"]').AV_asset_indicator(
        {
            'asset_type' : this.asset_type,
            'asset_id'   : this.asset_id,
            'class'      : 'circle_tray',
            'display'    : this.asset_indicators,
            'perms'      : this.perms,
            'onclick'    : function(id)
            {
                //Indicatos that will open a tab.
                var cond1 = 
                {
                    'alarms'         : 1, 
                    'events'         : 1, 
                    'services'       : 1, 
                    'vulnerabilities': 1, 
                    'assets'         : 1, 
                    'groups'         : 1
                };
                
                //Indicators that will scroll to a section.
                var cond2 = 
                {
                    'notes': 1
                };

                if (cond1[id])
                {
                    __self.open_section(id);
                }
                else if(cond2[id])
                {
                    scroll_to($('[data-bind="detail_'+ id +'"]'));
                }
                else
                {
                    return false;
                }
            }
        });
    }



    /*******************************************************************************************/
    /**************************              MAP SECTION              **************************/
    /*******************************************************************************************/

    /*
     *  Function to Load the Map.
     */
    this.load_map = function()
    {
        var __self = this;
        
        //First cleaning the map layer.
        $('#asset_map').empty();
        
        __self.av_map = new Av_map('asset_map');
        Av_map.is_map_available(function(conn)
        {
            if(conn)
            {
                //Loading the location of the asset(s).
                var data = 
                {
                    "asset_id": __self.asset_id,
                    "asset_type": __self.asset_type
                };

                $.ajax(
                {
                    data    : data,
                    type    : "POST",
                    url     : __self.cfg.common.providers + "get_asset_location.php",
                    dataType: "json",
                    success : function(locations)
                    {
                        //Initializing the map to 0,0 and zoom 1.
                        __self.av_map.set_location('', '');
                        __self.av_map.set_center_zoom(false);
                        __self.av_map.set_zoom(1);

                        //If we only have one single location, we display that location and zoom it.
                        if (typeof locations == 'object' && Object.keys(locations).length == 1)
                        {
                            try
                            {
                                var key = Object.keys(locations)[0];

                                __self.av_map.set_location(locations[key].lat, locations[key].lon);
                                __self.av_map.set_center_zoom(true);
                                __self.av_map.set_zoom(locations[key].zoom || 4);
                            }
                            catch(Err)
                            {

                            }
                        }
                        
                        //Drawing the maps
                        __self.av_map.draw_map();
                        
                        //Drawing the markers.
                        var markers = [];
                        
                        $.each(locations, function(i, p)
                        {
                            var pos    = new google.maps.LatLng(p.lat, p.lon);
                            var marker = new google.maps.Marker(
                            {
                                position: pos,
                                title: p.name
                            });
                            
                            /*
                                If we are displaying the markers in the group/network detail, then we'll make the marker clickable and we'll link to the asset detail.
                            */
                            if (__self.asset_type != 'asset')
                            {
                                google.maps.event.addListener(marker, 'click', function()
                                {
                                    var url = __self.cfg.common.views + 'detail.php?asset_id=' + i;
                                    link(url, 'environment', 'assets', 'assets');
                                });
                            }
                            markers.push(marker);
                        });

                        var mcOptions     = {gridSize: 80, maxZoom: 15};
                        var markerCluster = new MarkerClusterer(__self.av_map.map, markers, mcOptions);
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

                        var msg_error = XMLHttpRequest.responseText;
                        show_notification('asset_notif', msg_error, 'nf_error', 5000, true);
                    }
                });
            }
            else
            {
                __self.av_map.draw_warning();
            }
        });
    }



    /*******************************************************************************************/
    /*************************          GENERAL INFO SECTION           *************************/
    /*******************************************************************************************/

    /*
     *  Function to retrieve the Asset Info.
     */
    this.load_info = function()
    {
        var __self = this;
        var data   = 
        {
            "asset_id"  : __self.asset_id,
            "asset_type": __self.asset_type
        };


        return $.ajax(
        {
            data    : data,
            type    : "POST",
            url     : __self.cfg.common.providers + "get_asset_info.php",
            dataType: "json",
            success : function(data)
            {
                $.extend(__self.info, data || {});
                __self.perms['deploy_agent'] = __self.perms['hids'] && __self.info['os'] && __self.info['os'].match(/(^microsoft|windows)/i);
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

                var msg_error = XMLHttpRequest.responseText;
                show_notification('asset_notif', msg_error, 'nf_error', 5000, true);
            }
        });
    }




    /*********************************************************************************
     *************************             LABELS               **********************
     *********************************************************************************/

    /**
     *  Load asset labels
     */
    this.load_labels = function ()
    {
        var __self = this;
        var data   = 
        {
            "asset_id"  : __self.asset_id,
            "asset_type": __self.asset_type
        };

        return $.ajax(
        {
            data      : data,
            type      : 'POST',
            url       : __self.cfg.common.providers + 'get_asset_tags.php',
            dataType  : 'json',
            success   : function (data)
            {
                var tags = {};
                
                //Callback to load the Show More Plugin.
                var reload_show_more = function ()
                {
                    $('[data-bind="detail_label_container"]').show_more('reload');
                };

                $.each(data, function (index, tag)
                {
                    tags[index] = draw_tag(tag, __self.asset_id, reload_show_more);
                });

                __self.labels = tags;
            },
            error     : function (XMLHttpRequest, textStatus, errorThrown)
            {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var msg_error = XMLHttpRequest.responseText;
                show_notification('asset_notif', msg_error, 'nf_error', 5000, true);
            }
        });
    };

    /**
     * Add label to asset
     *
     * @param  status  OK/error
     * @param  data    Tag/error message
     */
    this.add_label = function(status, data)
    {
        var __self = this;

        if (status == 'OK')
        {
            var $label_container = $('[data-bind="detail_label_container"]');
            
            //Callback to load the Show More Plugin.
            var reload_show_more = function ()
            {
                $label_container.show_more('reload');
            };

            __self.labels[data.id] = draw_tag(data, __self.asset_id, reload_show_more);

            draw_label($label_container, __self.labels[data.id]);
            $label_container.show_more('reload');
        }
        else
        {
            show_notification('asset_notif', data, 'nf_error', 5000, true);
        }
    };


    /**
     * Remove label from asset
     *
     * @param  status  OK/error
     * @param  data    Tag/error message
     */
    this.delete_label = function(status, data)
    {
        var __self = this;

        if (status == 'OK')
        {
            delete __self.labels[data.id];
            $('[data-tag-id="' + data.id + '"]').remove();
            $('[data-bind="detail_label_container"]').show_more('reload');
        }
        else
        {
            show_notification('asset_notif', data, 'nf_error', 5000, true);
        }
    };




    /*******************************************************************************************/
    /****************************           NOTE SECTION            ****************************/
    /*******************************************************************************************/

    /*
     *  Function to Load the Notes.
     */
    this.load_notes = function()
    {
        var __self = this;
        
		$("[data-bind='detail_notes']").av_note(
		{
            asset_type   : __self.asset_type,
            asset_id     : __self.asset_id,
            notif_div    : "",
            afterAdd     : function()
            {
                __self.av_indicators.load_notes();
            },
            afterDelete  : function()
            {
                __self.av_indicators.load_notes();
            }
        });
    }

    


    /*******************************************************************************************/
    /*************************          ENVIRONMENT SECTION           **************************/
    /*******************************************************************************************/

    /*
     *  Function to Load the Environment Section (Led Section).
     */
    this.load_environment_info = function()
    {
        var __self = this;
        var data   = 
        {
            "asset_id"  : __self.asset_id,
            "asset_type": __self.asset_type
        };

        return $.ajax(
        {
            data      : data,
            type      : "POST",
            url       : __self.cfg.common.providers + "get_asset_environment.php",
            dataType  : "json",
            beforeSend: function()
            {
                $('#detail_snapshot .detail_led').removeClass('led_gray led_green led_red led_yellow');
            },
            success   : function(data)
            {
                       
                $.each(data, function(i, data)
                {
                    var level = data.level;
                    var path  = data.link;
                    
                    $elem = $('[data-bind="led_'+ i +'"]').addClass('led_' + level);

                    if (__self.perms[i])
                    {
                        $elem.on('click', function()
                        {
                            link(path[0], path[1], path[2], path[3]);
                        });
                    }
                    else
                    {
                        $elem.addClass('av_l_disabled');
                    }
                });
                
            },
            error     : function(XMLHttpRequest, textStatus, errorThrown)
            {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var msg_error = XMLHttpRequest.responseText;
                show_notification('asset_notif', msg_error, 'nf_error', 5000, true);
            }
        });
    }




    /*******************************************************************************************/
    /**************************          SUGGESTION SECTION           **************************/
    /*******************************************************************************************/
    
    /*
     *  Function to Load the Suggestions
     */
    this.load_suggestions = function()
    {
        var __self = this;
        
        $('[data-bind="detail_suggestions"]').show();
        
        $('[data-bind="suggestion_list"]').av_suggestions(
        {
            'asset_id'   : __self.asset_id,
            'asset_type' : __self.asset_type
        });
    }




    /*******************************************************************************************/
    /*****************************          TAB SECTION           ******************************/
    /*******************************************************************************************/


    /*
     *  Function to Load the Tabs
     *
     * @param  id  Section to load. 
     */
    this.load_sections = function(id, scroll)
    {
        if (typeof id == 'number')
        {
            this.sections.selected = id; 
        }
        
        var section_opt =
        {
            'asset_id'      : this.asset_id,
            'asset_type'    : this.asset_type,
            'sections'      : this.sections,
            'permissions'   : this.perms
        }
        
        if (typeof scroll != 'undefined' && scroll == true)
        {
            section_opt['scroll_section'] = $('[data-bind="detail_sections"]');
        }

        this.av_sections = new av_detail_section(section_opt);
    }




    /*******************************************************************************************/
    /*****************************        ACTION FUNCTIONS         *****************************/
    /*******************************************************************************************/

    /*
     *  Function to init the Asset Scan Lightbox.
     */
    this.asset_scan = function()
    {
        var url   = '/ossim/netscan/new_scan.php?type=' + this.asset_type;
        var title = "<?php echo Util::js_entities(_('Asset Scan')) ?>";

        GB_show(title, url, '600', '720');
    }


    /*
     *  Function to init the Vulnerability Scan Lightbox.
     */
    this.vuln_scan = function()
    {
        var url   = '/ossim/vulnmeter/new_scan.php?action=create_scan&type=' + this.asset_type;
        var title = "<?php echo Util::js_entities(_('Vulnerability Scan')) ?>";

        GB_show(title, url, '600', '720');
    }


    /*
     *  Function to init the HIDS Agent Lightbox.
     */
    this.deploy_hids = function()
    {
        var __self = this;
        var url    = __self.cfg.asset.views + 'deploy_hids_form.php?asset_id=' + this.asset_id;
        var title  = "<?php echo Util::js_entities(_('Deploy HIDS Agent')) ?>";

        GB_show(title, url, '400', '650');
    }

    
    /*
     *  Function to toggle Availability Monitoring.
     *
     * @param  action  Whether to Enable or Disable. 
     */
    this.toggle_monitoring = function(action)
    {
        var __self = this;
        var token  = Token.get_token("toggle_monitoring");
        $.ajax(
        {
            type: "POST",
            url: __self.cfg.common.controllers + "bk_toggle_monitoring.php",
            data: {token: token, asset_type: __self.asset_type, action: action},
            dataType: "json",
            success: function(data)
            {
                if (data.status == 'OK')
                {
                    show_notification('asset_notif', data.data, 'nf_success', 15000, true);

                }
                else if (data.status == 'warning')
                {
                    show_notification('asset_notif', data.data, 'nf_warning', 15000, true);
                }
                
                __self.av_indicators.load_availability();  
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
                    if (data.unique) {
                        __self.toggle_monitoring(action);
                        return;
                    }

                    av_confirm(msg, __confirm_keys).done(function()
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




    /*******************************************************************************************/
    /*****************************         EXTRA FUNCTIONS         *****************************/
    /*******************************************************************************************/

    /*
     *  Function to Load the Asset Actions into the Dropdown.
     */
    this.load_actions = function()
    {
        var __self = this;
        
        var hide   = true; 
        var $elem  = $('[data-bind="dropdown-actions"] ul').empty();
        
        $.each(this.actions, function(i, v)
        {
            if (__self.perms[v.perms])
            {
                hide = false;
                $('<a></a>',
                {
                    'href': '#' + i,
                    'data-bind': v.id,
                    'text': v.name,
                    'click': function()
                    {
                        if (typeof v.action == 'function')
                        {
                            v.action();
                        }
                    }
                }).appendTo($('<li></li>').appendTo($elem));
            }
        });
        
        if (hide)
        {
            $('[data-bind="button_action"]').addClass('av_b_disabled');
        }
    }

    
    /*
     *  Function to Open a Tab
     *
     * @param  sec_id  Section to open. 
     */
    this.open_section = function(sec_id)
    {
        this.av_sections.open_section(sec_id, $('[data-bind="detail_sections"]'));
    }


    /*
     *  Function to Reload a Tab
     *
     * @param  sec_id  Section to reload. 
     */
    this.reload_section = function(sec_id)
    {
        this.av_sections.reload_section(sec_id); 
    }
    
    
    /*
     *  Function to translate the tab name into the tab id.
     *
     * @param  tab  Tab Name to translate into id. 
     */
    this.translate_tab_section = function(tab)
    {
	    var __self    = this;
	    var selected  = 0;

	    $.each(__self.sections.tabs, function(i, v)
	    {
			if (v.id.match(tab))
			{
				selected = i;
				
				return false;
			}
	    });
	    
	    return selected;
    }




    /*******************************************************************************************/
    /**************************            BINDING & INIT             **************************/
    /*******************************************************************************************/

    /*
     *  Function to init the detail.
     */
    this.detail_init = function()
    {   
        this.load_actions();

        $('.info_' + this.asset_type).show();

        this.draw_info();

        this.draw_indicators();

        this.load_map();

        this.load_environment_info();

        this.load_notes();

        this.bind_handlers();
    }

}


/*******************************************************************************************************/
/*******************************************************************************************************/
/**************************                GLOBAL FUNCTIONS                 ****************************/
/*******************************************************************************************************/
/*******************************************************************************************************/

/*
 *  AV_confirm keys definition.
 */
var __confirm_keys = {"yes": "<?php echo Util::js_entities(_('Yes')) ?>","no": "<?php echo Util::js_entities(_('No')) ?>"};


/*
 *  Function to load a link using the menu options.
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
    }
    catch(Err){}

    go_to(url);
}


/*
 *  Function to load a link using the load_content funciton.
 *
 * @param  url     Url. 
 */
function go_to(url)
{
    try
    {
        top.av_menu.load_content(url);
    }
    catch(Err)
    {
        document.location.href = url

    }

    return false;
}



/**********************************************************************/
/****************          Drawing Functions          *****************/
/**********************************************************************/

/*
 *  Function to draw a text. If empty, it draws Unknown.
 *
 * @param  elem         Elem where we are going to draw the text. 
 * @param  text         Text to display. 
 * @param  leave_blank  Wheter or not display Unknown when the text is empty. 
 */
function draw_text(elem, text, leave_blank)
{
    if (!text)
    {
        if (leave_blank)
        {
            elem.hide();
        }
        else
        {
            elem.html("<span class='empty_elem'><?php echo _('Unknown') ?></span>");
        }
        
        return false;
    }

    $(elem).addClass('ellipsis').attr('title', text).text(text).show();
}


/*
 *  Function to draw a list of elems (Show More Plugin Added).
 *
 * @param  elem   Elem where we are going to draw the list. 
 * @param  list   List of items. 
 * @param  limit  Max number of items to display. 
 */
function draw_list(elem, list, limit)
{
    if (list.length < 1)
    {
        elem.html("<span class='empty_elem'><?php echo _('Unknown') ?></span>");
        return false;
    }

    if (typeof limit == 'undefined')
    {
        limit = 6;
    }

    var ul = $('<ul></ul>',
    {
        'class': 'detail_elem_list'
    });

    $.each(list, function(i, v)
    {
        $('<li></li>',
        {
           'text' : v,
           'title': v,
           'class': 'ellipsis'
        }).appendTo(ul);
    });

    elem.html(ul);
    ul.show_more({items_to_show: limit});
}


/*
 *  Function to draw a base64 icon.
 *
 * @param  elem   Elem where we are going to draw the icon. 
 * @param  img    Base64 Icon. 

 */
function draw_icon(elem, img)
{
    if (img == '')
    {
        $(elem).attr('src', '').hide();
    }
    else
    {
        $(elem).attr('src', 'data:image/png;base64,' + img).show();
    }
}


/*
 *  Function to draw a label.
 *
 * @param  elem   Elem where we are going to draw the label.  
 * @param  label  Label to draw. 
 */
function draw_label(elem, label)
{
    var __self = this;

    label.appendTo(elem);
}


/*
 *  Function to draw a range selector highlighting the selected option: 1 2 3 4 5.
 *
 * @param  elem   Elem where we are going to draw the range.  
 * @param  inf    Range Bottom level. 
 * @param  sup    Range Top level. 
 * @param  sel    Range Selected level.  
 */
function draw_range(elem, inf, sup, sel)
{
    elem.empty();
    for (var i = inf; i <= sup; i++)
    {
        var val = $('<span></span>').text(i).appendTo(elem); 
        
        if (parseInt(sel) === i)
        {
            val.addClass('asset_value_selected');
        }
    }
}



/**********************************************************************/
/*************         Drawing Format Functions         ***************/
/**********************************************************************/

/*
 *  Function to format the ip list as "ip [mac]".
*
* @param  list  List of unformatted IPs. 
*/
function format_ips(list)
{
    var ips = [];

    $.each(list, function(i, val)
    {
        var ip = val.ip;

        if (val.mac)
        {
            ip += ' [' + val.mac + ']';
        }
        ips.push(ip);

    });

    return ips;
}


/*
 *  Function to format the network list as "network_name (network_cidr)".
 *
 * @param  list  List of unformatted networks. 
 */
function format_networks(list)
{
    var networks = [];

    $.each(list, function(i, val)
    {
        var net = val.name + ' (' + val.ips + ')';

        networks.push(net);

    });

    return networks;
}


/*
 *  Function to format the sensor list as "sensor_name (sensor_ip)".
 *
 * @param  list  List of unformatted sensors. 
 */
function format_sensors(list)
{
    var sensors = [];

    $.each(list, function(i, val)
    {
        var sensor = val.name + ' (' + val.ip + ')';

        sensors.push(sensor);

    });

    return sensors;
}
