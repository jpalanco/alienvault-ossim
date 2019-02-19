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
var av_menu    = null;
var h_window   = null;
var __internet = null;

function load_menu_scripts()
{
    //Menu
    av_menu = new Av_menu();
    av_menu.show();

    //console.log(av_menu);
    av_menu.display_system_name();

    //Internet
    __internet = new Av_internet_check();

    <?php

    $av_menu = unserialize($_SESSION['av_menu']);

    if (!is_object($av_menu))
    {
        $db   = new ossim_db();
        $conn = $db->connect();

        $av_menu = new Menu($conn);

        $db->close();
    }

    /* Remote Interface */
    if(isset($_SESSION['ri']) && !empty($_SESSION['ri']))
    {
        $url    = $_SESSION['ri']['url'];
        $m_opt  = $_SESSION['ri']['m_opt'];
        $sm_opt = $_SESSION['ri']['sm_opt'];
        $h_opt  = $_SESSION['ri']['h_opt'];

        unset($_SESSION['ri']);

        $av_menu->set_menu_option($m_opt, $sm_opt);
        $av_menu->set_hmenu_option($h_opt);

        $_SESSION['av_menu'] = serialize($av_menu);

        ?>
        var url = "<?php echo $url?>";
        <?php
    }
    else
    {
        $url  = $av_menu->get_current_url();

        ?>
        var url = "<?php echo Menu::get_menu_url($url, $av_menu->get_m_option(), $av_menu->get_sm_option(), $av_menu->get_h_option())?>";
        <?php
    }
    ?>


    var b_url = av_menu.get_bookmark_url();

    if(b_url != '')
    {
        url = b_url;
    }
    else
    {
        av_menu.m_option  = "<?php echo $av_menu->get_m_option()?>";
        av_menu.sm_option = "<?php echo $av_menu->get_m_option()."-".$av_menu->get_sm_option()?>";
        av_menu.h_option  = "<?php echo $av_menu->get_h_option()?>";
    }

    av_menu.load_content(url);


    //Handler to detect when the url hash is modified manually.
    $(window).on('hashchange', function()
    {
        var b_url = av_menu.get_bookmark_url();

        if(b_url != '')
        {
            url = b_url;

            av_menu.load_content(url);
        }
        else
        {
            av_menu.set_bookmark();
        }

    });

    $('#link_notification_center').on('click', function(e)
    {
        e.preventDefault();

        $('#sm_opt_message_center-message_center').trigger('click');
    });


    $('#link_settings').on('click', function(e)
    {
         e.preventDefault();

         $('#sm_opt_settings-settings').trigger('click');
    });

    $('#link_support').on('click', function(e)
    {
         e.preventDefault();

         $('#sm_opt_support-support').trigger('click');
    });

    $('#link_copyright').on('click', function(e)
    {
        e.preventDefault();

        //this function is in utils.js
        if (is_internet_available())
        {
            var win=window.open('/ossim/av_routing.php?action_type=LEGAL_FOOTER', "AlienVault");
                win.focus();

        }
        else
        {
            document.location.href = '/ossim/legal/download.php';
        }

    });



    <?php

    $pro        = Session::is_pro();
    $am_i_admin = Session::am_i_admin();

    /* Remote Interfaces */
    if ($pro && $am_i_admin)
    {
        $db   = new Ossim_db();
        $conn = $db->connect();

        $aux_ri_interfaces = Remote_interface::get_list($conn, "WHERE status=1");

        $ri_total = $aux_ri_interfaces[1];

        if ($ri_total > 0)
        {
            ?>
            av_menu.add_ri_link();
            <?php
        }
    }
    ?>


    $('#c_help img').click(function(event){
         var width  = 1024;
         var height = 768;

         var left = (screen.width/2)-(width/2);
         var top  = (screen.height/2)-(height/2);

         var w_parameters = "left="+left+", top="+top+", height="+height+", width="+width+", location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, titlebar=no";

         h_window = window.open('/ossim/loading.php', 'AlienVault Wiki', w_parameters);
         h_window.focus();

         setTimeout(function(){av_menu.show_help(h_window)}, 200);
    });


    //console.log(av_menu);

    // Menu plugin activation
    $(".flexnav").flexNav();

    // When the content is scrolled, the menu is changed.
    $(window).scroll(function ()
    {
        if ($(this).scrollTop() > 43)
        {
            $('#nav_container').addClass("f_nav");
        }
        else
        {
            $('#nav_container').removeClass("f_nav");
        }
    });


    /* SIDEBAR FUNCTIONS */

    //Handlers for the sidebar
    $('#notif_bt').on('click', function(){

        var win_width = $(window).width();
        //Opening the side bar
        if ($('#notif_layer').hasClass('notif_closed'))
        {
            $('#notif_layer').removeClass('notif_closed');

            $('#notif_layer').show();

            if (win_width > 1335)
            {
                $('#notif_resume').animate({ 'left': '0px' }, 600, function()
                {
                    $('#notif_container').animate({ 'margin-right': '350px' }, 750, function()
                    {
                        $('#notif_bt').removeClass('notif_closed');
                        $('#notif_bt').addClass('notif_open');
                    });
                });
            }
            else
            {
                $('#notif_container').animate({ 'margin-right': '350px' }, 750, function()
                {
                    $('#notif_bt').removeClass('notif_closed');
                    $('#notif_bt').addClass('notif_open');
                });

                $('#notif_resume').css('left', '0px');
            }


            $('#notif_layer').addClass('notif_open');
        }
        //Closing the side bar
        else if ($('#notif_layer').hasClass('notif_open'))
        {
            $('#notif_layer').removeClass('notif_open');

            $('#notif_container').animate({ 'margin-right': '0' }, 750, function()
            {
                $('#notif_layer').hide();

                $('#notif_bt').removeClass('notif_open');
                $('#notif_bt').addClass('notif_closed');

                if (win_width > 1335)
                {
                    $('#notif_resume').animate({ 'left': '-49px' }, 600, function()
                    {
                        $(this).css("left", "-50px"); //Hack to avoid blurred text
                    });
                }
                else
                {
                    $('#notif_resume').css("left", "-50px");
                }

            });

            $('#notif_layer').addClass('notif_closed');

        }

        return false;

    });

       
    load_sidebar_data();
    get_notifications_number()
    bind_notif_links();
    
    
    $('#notif_container').on('click', '#notif_resume', function()
    {
        $('#notif_bt').trigger('click');
    });
    
    <?php
    if ($_SESSION['_welcome_wizard_bar'] === TRUE)
    {
        unset($_SESSION['_welcome_wizard_bar']);
    ?>
        setTimeout(function()
        {
            $('#home_wizard_wrapper').slideDown(500, function()
            {
                $(this).height(0);
            });

        }, 1000);

        setTimeout(function()
        {
            $('#home_wizard_wrapper').slideUp(500);
        }, 30000);

    <?php
    }
    ?>
}

// Get notifications number
function get_notifications_number()
{
    $.ajax(
    {
        data: {"bypassexpirationupdate": "1"},
        type: 'POST',
        url: '<?php echo AV_MAIN_PATH ?>/message_center/providers/get_notifications_stats.php',
        dataType: 'json',
        success: function (data)
        {
            var cnd_1 = (typeof(data) == 'undefined' || data == null);
            var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

            if (cnd_1 || cnd_2)
            {
                // Hide notification bubble
                update_notification_bubble(0, false);
            }
            else
            {
                update_notification_bubble(data.data.unread, true);
            }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown)
        {
            //Check expired session
            var session = new Session(XMLHttpRequest, '');

            if (true == session.check_session_expired())
            {
                session.redirect();
                return;
            }

            // Hide notification bubble
            update_notification_bubble(0, false);
        },
        complete: function()
        {
            setTimeout(get_notifications_number, 300000);
        }
    });
}
