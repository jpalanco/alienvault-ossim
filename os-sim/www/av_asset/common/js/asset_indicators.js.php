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

(function($) 
{    
    /*
     *  AV_asset_indicator jQuery Plugin.
     */
    $.fn.AV_asset_indicator = function(o) 
    {
	    var __self = this;
	    var opt    = 
	    {
            "asset_type" : '',
            "asset_id"   : '',
            "class"      : '',
            "onclick"    : null,
            "perms"      :
            {
                "assets"         : true,
                "vulnerabilities": true,
                "alarms"         : true,
                "events"         : true,
                "availability"   : true,
                "services"       : true,
                "groups"         : true,
                "notes"          : true
            }
        };
	    
        $.extend(opt, o || {});
      
        var __cfg      = <?php echo Asset::get_path_url() ?>;
        var __ajax_url = __cfg.common.providers;
        
        //Led level classes
        var draw_class = 
        {
            0: 'c_off',
            1: 'c_low',
            2: 'c_medium',
            3: 'c_high'
        };
        
        var __indicators = {};
        
        
        /*
         *  Function to initialize the plugin.
         */
        this.init = function()
        {
            var __self = this;
            
            //Indicator Configuration.
            __indicators = 
            {
                'assets':
                {
                    'load'    : __self.load_assets,
                    'provider': 'get_status_assets.php',
                    'legend'  : "<?php echo _('Assets') ?>"
                },
                'vulnerabilities':
                {
                    'load'    : __self.load_vulnerabilities,
                    'provider': 'get_status_vulnerabilities.php',
                    'legend'  : "<?php echo _('Vulnerabilities') ?>" 
                },
                'alarms':
                {
                    'load'    : __self.load_alarms,
                    'provider': 'get_status_alarms.php',
                    'legend'  : "<?php echo _('Alarms') ?>" 
                },
                'events':
                {
                    'load'    : __self.load_events,
                    'provider': 'get_status_events.php',
                    'legend'  : "<?php echo _('Events') ?>" 
                },
                'services':
                {
                    'load'    : __self.load_services,
                    'provider': 'get_status_services.php',
                    'legend'  : "<?php echo _('Services') ?>" 
                },
                'availability':
                {
                    'load'    : __self.load_availability,
                    'provider': 'get_status_availability.php',
                    'legend'  : "<?php echo _('Availability') ?>" 
                },
                
                'notes':
                {
                    'load'    : __self.load_notes,
                    'provider': 'get_status_notes.php',
                    'legend'  : "<?php echo _('Notes') ?>" 
                },
                'groups':
                {
                    'load'    : __self.load_groups,
                    'provider': 'get_status_groups.php',
                    'legend'  : "<?php echo _('Groups') ?>"
                }  
            };
            
            var indicator_order = [];
            
            if (opt.asset_type == 'asset')
            {
                indicator_order = 
                [
                    "vulnerabilities",
                    "alarms",
                    "events",
                    "availability",
                    "services",
                    "groups",
                    "notes"
                ];
            }
            else if (opt.asset_type == 'group' || opt.asset_type == 'network')
            {
                indicator_order = 
                [
                    "assets",
                    "vulnerabilities",
                    "alarms",
                    "events",
                    "availability",
                    "services",
                    "notes"
                ];
            }
            else
            {
                return false;
            }
            
            
            $.each(indicator_order, function(index, id)
            {
                var indicator = __indicators[id];
                var circle    = $('<div></div>',
                {
                    'id'   : id,
                    'class': 'circle_indicator ' + opt.class,                      
                    'html' : '<span class="circle_val">-</span>',
                    'click': function()
                    {
                        //If we define a function for clicking then we use it
                        if (typeof opt.onclick == 'function')
                        {
                            opt.onclick(id);
                        }
                        else
                        {
                            //Otherwise we use the default action.
                            __self.link_detail(id);
                        }
                    },
                    'data-indicator'      : id, 
                    'data-indicator-level': 'c_off',
                    'data-indicator-type' : opt.asset_type
                }).appendTo(__self);
                
                $('<div></div>',
                {
                    'class': 'circle_legend',                      
                    'text' : indicator.legend
                }).appendTo(circle);
        
                if (opt.perms[id] === false)
                {
                    circle.off('click').css('cursor', 'default');   
                }
                else
                {
                    indicator.load(); 
                }
            });
        }
        
        
        /*
         *  Function to load the alarm indicator.
         */
        this.load_alarms = function()
        {
            var id = 'alarms';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the event indicator.
         */
        this.load_events = function()
        {
            var id = 'events';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the vulnerability indicator.
         */
        this.load_vulnerabilities = function()
        {
            var id = 'vulnerabilities';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the service indicator.
         */
        this.load_services = function()
        {
            var id = 'services';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the availability indicator.
         */
        this.load_availability = function()
        {
            var id = 'availability';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the assets indicator.
         */
        this.load_assets = function()
        {
            var id = 'assets';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the groups indicator.
         */
        this.load_groups = function()
        {
            var id = 'groups';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to load the notesindicator.
         */
        this.load_notes = function()
        {
            var id = 'notes';
            
            return load_indicator_data(id);
        }
        
        
        /*
         *  Function to reload the indicator content.
         *
         * @param  url     Url. 
         * @param  url     Url. 
         * @param  url     Url. 
         * @param  url     Url. 
         */
        function reload_indicator(id, val, level, tooltip)
        {
            var $circle     = $('#'+ id, __self);
            var $circle_val = $('.circle_val', $circle);
            
            if (typeof val == 'number')
            {
                val = $.number_readable(val)
            }
            
            $circle_val.html(val);
            
            if (typeof tooltip != 'undefined' && tooltip != '')
            {
                if (typeof $.fn.tipTip == 'function')
                {
                    $circle_val.attr('data-title', tooltip).tipTip({attribute: 'data-title'});
                }
                else
                {
                    $circle_val.attr('title', tooltip);
                }
            }
            
            if (typeof level != 'undefined')
            {
                var c_level = draw_class[level];
                $circle.attr("data-indicator-level", c_level);
            }
        }
        
        
        /*
         *  Function to load the indicator data.
         *
         * @param  id     Indicator ID. 
         */
        function load_indicator_data(id)
        {
              
            if (opt.perms[id] === false)
            {
                reload_indicator(id, '-', 0, '');
                return false;
            }
            
            var indicator = __indicators[id];
            var url       = __ajax_url + indicator.provider;
            var params    =
            {
                'asset_type': opt.asset_type,
                'asset_id'  : opt.asset_id
            }
            
            return $.ajax(
            {
                type: "POST",
                url: url,
                data: params,
                dataType: "json",
                beforeSend: function()
                {
                    reload_indicator(id, '<img src="/ossim/pixmaps/loading.gif"/>');
                },
                success: function(data)
                {
                    reload_indicator(id, data.value, data.level, data.tooltip);
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
                    
                    reload_indicator(id, '-', 0, '');
                }
            });
        }
        
        
        /*
         *  Function to link to the detail of the given asset with the specified section.
         *
         * @param  section     Section to load within the detail. 
         */
        this.link_detail = function(section)
        {
	        var url = __cfg.asset.detail + '?asset_id='+ opt.asset_id + '&section=' + section;
	        
	        if (opt.asset_type == 'asset')
	        {
		        link(url, "environment", "assets", "assets");
	        }
	        else if (opt.asset_type == 'group')
	        {
		        link(url, "environment", "assets", "asset_groups");
	        }
	        else if (opt.asset_type == 'network')
	        {
		        link(url, "environment", "assets", "networks");
	        }
        }
        
        this.init();
        
        return this;
    }
    
    
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
	        top.av_menu.load_content(url);
	    }
	    catch(Err)
	    {
	        document.location.href = url
	
	    }
	
	    return false;
	}
          
})(jQuery);
