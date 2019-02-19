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

/**
* This jQuery class is used to draw a dataTable with the plugin list provided by the API
* It is used in the plugins section to list plugins and custom plugins
*/

function av_plugin_list()
{
    this.table      = {};
    this.actions    = {};

    this.confirm_keys =
    {
        "yes": "<?php echo Util::js_entities(_('Yes')) ?>",
        "no": "<?php echo Util::js_entities(_('No')) ?>"
    };    
 
    
    /*
    * This function initializes the dataTable object for the plugin list
    * Using av_table.js.php jQuery class
    */
    this.init = function()
    {
        var __self = this;
        
        /* Line to prevent the autocomplete in the browser */ 
        $('input').attr('autocomplete','off');
        
        __self.table = $("[data-bind='av_table_plugins']").AV_table(
        {
            "selectable"    : false,
            "num_rows"      : 10,
            "search"        : true,
            "length_change" : false,
            "language"      : "plugins",
            "dt_params"     :
            {
                "aoColumns":
                [
                    {"bSortable": false, "sClass": "center", "sWidth": "30px"},
                    {"bSortable": true,  "sClass": "left",   "sWidth": "50px"},
                    {"bSortable": true,  "sClass": "left", "sType": "plugins"},
                    {"bSortable": true,  "sClass": "left", "sType": "plugins"},
                    {"bSortable": true,  "sClass": "center"},
                    {"bSortable": true,  "sClass": "left",   "bSearchable": false},
                    {"bSortable": true,  "sClass": "center", "bSearchable": false},
                    {"bSortable": false, "sClass": "center td_nowrap", "sWidth": "50px"}
                ],
                "aaSorting": [[ 2, "asc" ],[ 3, "asc" ]]
            },
            "on_draw_row" : function()
            {
                // Enable/Disable Trashcan
                $('.plugin_check').on('change', function()
                {
                    if ($('.plugin_check:checked').length > 0)
                    {
                        $('#delete_selection').removeClass('disabled');
                    }
                    else
                    {
                        $('#delete_selection').addClass('disabled');
                    }
                });
                
                $('[data-bind="download-plugin"]').click(function()
                {
                    var _plugin = $(this).attr('data-plugin');
                    var _ctoken = Token.get_token("plugin_download");
                    
                    $('#download_form_token').val(_ctoken);
                    $('#download_form_plugin').val(_plugin);
                    $('#download_form').submit();
                });
            }
        });
        
        
        /*
        * Client-side pagination event (not implemented in av_table.php.js)
        */
        __self.table.on('page.dt', function ()
        {
            // Uncheck page checkboxes
            $('.plugin_check, [data-bind="chk-all-plugins"]').prop('checked', false);
            
            // Disable Trashcan
            $('#delete_selection').addClass('disabled');
        });
    }

    jQuery.fn.dataTableExt.oSort["plugins-desc"] = function (x, y) {
        var pl = new av_plugin_list();
        return pl.custom_compare(y,x);
    };
     
    jQuery.fn.dataTableExt.oSort["plugins-asc"] = function (x, y) {
        var pl = new av_plugin_list();
        return pl.custom_compare(x,y);
    };

   this.custom_compare = function (x,y) {
       var xs = this.get_chunks(x);
       var ys = this.get_chunks(y);
       while (true) {
          x = xs.shift();
          if (x == undefined) return true;
          y = ys.shift();
          if (y == undefined) return false;
          var isx = jQuery.isNumeric(x);
          var isy = jQuery.isNumeric(y);
          if (isx && !isy) return true;
          if (!isx && isy) return false;
          var xl = x.length;
          var yl = y.length;
          if (xl < yl) {
              ys.unshift(y.substr(xl - yl));
              y = y.substr(0,xl);
          }
          if (xl > yl) {
              xs.unshift(x.substr(yl - xl));
              x = x.substr(0,yl);
          }
          if (x != y) return x > y;
       }
   };

   this.get_chunks = function(data) {
	return data.split(/([^0-9]+)|([^a-zA-Z]+)/).filter(function(e) { return e; });
   };
    
    /*
    * This function calls to a controller which deletes a list of plugins via API
    * It reloads the main plugin list page when it is done
    */
    this.delete_plugins = function()
    {
        msg = "<?php echo Util::js_entities(_('Warning: Removing this plugin will disable it on all sensors. Are you sure you would like to continue?'))?>";

        var _selected_plugins = [];

        $('.plugin_check:checked').each(function(id, elem)
        {
            _selected_plugins.push($(elem).attr('name'));
        });
        
        if (_selected_plugins.length > 0)
        {
            var ctoken = Token.get_token("plugin_actions");
            
            av_confirm(msg, this.confirm_keys).done(function()
            {
                show_loading_box('main_container', '<?php echo Util::js_entities(_('Please wait...')) ?>', '');
                $.ajax({
                    type: 'POST',
                    url: '<?php echo AV_MAIN_PATH . "/av_plugin/controllers/plugin_actions.php" ?>',
                    dataType: 'json',
                    data: {'action': 'delete_plugin', 'plugin_list': _selected_plugins, 'token': ctoken},
                    success: function(data)
                    {
                        hide_loading_box();
                        location.href = location.href;
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        hide_loading_box();
                        //Checking expired session
                        var session = new Session(XMLHttpRequest, '');
                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }
                        
                        var error = XMLHttpRequest.responseText;
                        show_notification('plugin_notif', error, 'nf_error', 5000, true);
                    }
                });
            });
        }
    }
}
