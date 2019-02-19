<?php
header("Content-type: text/javascript");

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

(function ($)
{
    $.fn.av_wizard = function (options)
    {
        options = $.extend(
        {
            "steps"  : [],
            "finish" : function() {}
        }, options || {});
        
        __current_step    = 1;
        __enabled_steps   = 1;
        __total_steps     = options.steps.length;
        
        __init.call(this);

        
        
        /*****************
        /** Constructor **
        *****************/
        
        /*
         *  Function to create the html code
         */
        function __init()
        {
            // Containers
            $('body').append('<div id="wizard_notif"></div>');
            $(this).append('<div id="wizard_options"><ul id="wizard_path_container"></ul></div>');
            $(this).append('<div id="wizard_wrapper"><div class="wrap"><div id="wizard_loading"><div id="av_main_loader"></div></div><div id="wizard_step"></div></div></div>');
            $(this).append('<div class="clear_layer"></div>');
            
            // Steps
            $.each(options.steps, function(key, val)
            {
                var i = key + 1;
                
                var _li_num = $('<div></div>', {'class': 'wizard_number', 'html': i});
                var _li_txt = $('<div></div>', {'class': 'step_name av_l_disabled av_link', 'html': val.title});
                var _opt_li = $('<li></li>',
                {
                    'id': i
                }).append(_li_num).append(_li_txt);
                
                $('#wizard_path_container').append(_opt_li);
                
            });
            
            // Handle left menu clicks
            $('.step_name').on('click', function()
            {
                if ($(this).hasClass('av_l_disabled'))
                {
                    return false;
                }
                
                __current_step = parseInt($(this).parents('li').first().attr('id'));
                
                load_wizard_step();
                
            });
            
            load_wizard_step();
                
        }
        
        
        /*  Function to load the content of the current wizard step  */
        function load_wizard_step()
        {
            var ctoken = Token.get_token("wizard");
            var _self  = this;
            var opts = options.steps[__current_step - 1];
            $.ajax(
            {
                url: opts.src + "?token="+ctoken,
                data: opts.data != undefined ? opts.data : {},
                type: "POST",
                dataType: "html",
                beforeSend: function()
                {
                    //Cleaning the current content before load the new step content
                    $('#wixard_step').empty();
                },
                success: function(data)
                {
                    // Clean Step Handlers
                    load_js_step = function(){};
                    next_allowed = function(){ return true };
                    
                    
                    //Adding the content
                    $('#wizard_step').html(data);
                    
                    var _button_next = (_self.__current_step < _self.__total_steps) ? $('<button id="next_step" class="fright"><?php echo _('Next') ?></button>') : $('<button id="next_step" class="fright"><?php echo _('Finish') ?></button>');
                    var _button_back = (_self.__current_step > 1) ? $("<a href='javascript:;' id='prev_step' class='av_l_main'><?php echo _('Back') ?></a>") : '&nbsp;';
                    
                    var _buttons = $('<div class="wizard_button_list"></div>').append(_button_back).append(_button_next);
                    
                    $('#wizard_step').append(_buttons);
                    
                    //Hiding the loading message
                    $('#wizard_loading').hide();
        
                    //Adjusting the height of the container
                    adjust_container_height();
                    change_selected_step_option();
        
                    // Execute Step Handlers: optionally defined in provided data.
                    load_js_step();
                    
                    
                    $('#next_step').click(function()
                    {
                        if (next_allowed())
                        {
                            next_step();
                        }
                    });
                    $('#prev_step').click(function()
                    {
                        prev_step();
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
                    //Hidding the loading to show the error notification
                    $('#wizard_loading').hide();
                    show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
                }
            });
        }
        
        /*  Function to set the height of the container  */
        function adjust_container_height()
        {
            var h = 0;
        
            try
            {
                //Windows height - 220 (Height of the headers and margins)
                h = window.innerHeight - 151;
            }
            catch(Err){}
        
            //The minimun height is 475
            h = (h < 475) ? 475 : h;
        
            $('#wizard_step').css('min-height', h + 'px');
        
        }
        
        /*  Function to load the next step  */
        function next_step()
        {
            __current_step += 1;
            
            if (__current_step > __enabled_steps)
            {
                __enabled_steps = __current_step;
            }
            
            if (__current_step > __total_steps)
            {
                options.finish();
            }
            else
            {
                load_wizard_step();
            }
        }
        
        /*  Function to load the next step  */
        function prev_step()
        {
            __current_step -= 1;
            
            load_wizard_step();
        }
        
        /*  Function to select as active the current step in the left menu  */
        function change_selected_step_option()
        {
            for (var i = 1; i <= __total_steps; i++)
            {
                if (i <= __enabled_steps && i != __current_step)
                {
                    $('#wizard_path_container li#'+ i + ' .step_name').removeClass('av_l_disabled');
                    $('#wizard_path_container li#'+ i + ' .wizard_number').addClass('s_visited');
                }
                else
                {
                    $('#wizard_path_container li#'+ i + ' .step_name').addClass('av_l_disabled');
                    $('#wizard_path_container li#'+ i + ' .wizard_number').removeClass('s_visited');
        
                    if (i == __current_step)
                    {
                        $('#wizard_path_container li').removeClass('current_step');
                        $('#wizard_path_container li#'+ i).addClass('current_step');
                    }
                }
            }
        
        }
    }
})(jQuery);
