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


/**********************************************************
 * Alienvault Menu
 *
 * This object displays all menus in User Interface
 **********************************************************/

function Av_menu()
{
    <?php
    $error_msg = '';

    try
    {
        $db      = new ossim_db();
        $conn    = $db->connect();

        $av_menu = unserialize($_SESSION['av_menu']);

        if (!is_object($av_menu))
        {
            $av_menu = new Menu($conn);
        }
        $h_menus = $av_menu->get_hmenus_info();

        $db->close();

    }
    catch(Exception $e)
    {
        $error_msg = $e->getMessage();
    }

    try
    {
        list($s_name, $s_ip) = Session::get_local_sysyem_info();
        $s_show = TRUE;
    }
    catch (Exception $e)
    {
        $s_name = '';
        $s_ip = '';
        $s_show = FALSE;
    }

    if ($error_msg != '')
    {
        ?>
        this.url_base     = "/ossim";
        this.error_msg    = "<?php echo $error_msg?>";
        this.m_option     = '';
        this.sm_option    = '';
        this.h_option     = '';
        this.l_option     = '';

        this.menu         = '';
        this.h_menus       = new Array();
        <?php
    }
    else
    {
        ?>
        this.url_base  = "/ossim";
        this.error_msg = "";
        this.m_option  = "<?php echo $av_menu->get_m_option()?>";
        this.sm_option = "<?php echo $av_menu->get_m_option()."-".$av_menu->get_sm_option()?>";
        this.h_option  = "<?php echo $av_menu->get_h_option()?>";
        this.l_option  = "<?php echo $av_menu->get_l_option()?>";

        this.menu      = "<?php echo str_replace("\n", "\\n", $av_menu->render_menu())?>";

        this.h_menus    = new Array();
        <?php

        foreach ($h_menus as $menu_id => $hmenu_data)
        {
            ?>
            this.h_menus["<?php echo $menu_id?>"] = new Array();
            this.h_menus["<?php echo $menu_id?>"]['html']           = "<?php echo str_replace("\n", "\\n", $hmenu_data['html'])?>";
            this.h_menus["<?php echo $menu_id?>"]['default_url']    = "<?php echo $hmenu_data['default_url']?>";
            this.h_menus["<?php echo $menu_id?>"]['default_option'] = "<?php echo $hmenu_data['default_option']?>";
            this.h_menus["<?php echo $menu_id?>"]['menu_title']     = "<?php echo $hmenu_data['menu_title']?>";
            this.h_menus["<?php echo $menu_id?>"]['items']          = new Array();

            <?php

            foreach ($hmenu_data['items'] as $hmenu_id => $it_data)
            {
                if ($av_menu->get_h_option() == $hmenu_id)
                {
                    ?>
                    this.l_option  = "<?php echo $it_data['default_loption']?>";
                    <?php
                }
                ?>

                //Default local menu option for every hmenu option
                this.h_menus["<?php echo $menu_id?>"]['items']['<?php echo $hmenu_id?>'] = new Array();

                this.h_menus["<?php echo $menu_id?>"]['items']['<?php echo $hmenu_id?>']['default_loption'] = '<?php echo $it_data['default_loption']?>';

                //Bookmark related to every hmenu option
                this.h_menus["<?php echo $menu_id?>"]['items']['<?php echo $hmenu_id?>']['bookmark'] = new Array();

                this.h_menus["<?php echo $menu_id?>"]['items']['<?php echo $hmenu_id?>']['bookmark']['url']   = '<?php echo $it_data['bookmark']['url']?>';
                this.h_menus["<?php echo $menu_id?>"]['items']['<?php echo $hmenu_id?>']['bookmark']['param'] = '<?php echo $it_data['bookmark']['param']?>';
                <?php
            }
        }
    }
    ?>


    this.c_menu  = "#c_menu";
    this.c_hmenu = "#c_hmenu";

    // It is true if the user clicks on another menu option (Only for main menu).
    this.m_transition = false;

    //Timeout to show exit loading section image
    var loading_timeout;

    /* Iframe config variables */
    var resize_timeout;
    var iframe_height;

    /* Bookmark Variables */
    this.bookmark_param = '';

    /* System Name Variables */
    this.system_name = "<?php echo $s_name ?>";
    this.system_ip = "<?php echo $s_ip ?>";
    this.show_system_info = <?php echo ($s_show) ? 'true' : 'false' ?>;

    this.show = function()
    {
        var that = this;

        //There is an error, user will be redirected to login page
        if (typeof(that.error_msg) == 'string' && that.error_msg != '')
        {
            var session = new Session('', '');

            session.redirect();

            return false;
        }


        if (typeof(that.menu) == 'string' && that.menu != '')
        {
            //Show menu
            $(that.c_menu).html(that.menu);

            //Bind handlers with menu
            bind_menu_handler(that);
        }
    };


    /**
    * This function actives the cliked option on the main menu
    *
    */
    this.active_option = function(sm_option)
    {
        var that = this;

        //Set active option
        if (typeof(sm_option) == 'string' && sm_option != '')
        {
            that.m_transition = (that.sm_option != sm_option) ? true : false;

            var m_option   = sm_option.split('-');

            that.m_option  = m_option[0];
            that.sm_option = sm_option;

            //Remove last h_option and l_option
            that.h_option  = '';
            that.l_option  = '';

            /* JavaScript and CSS Effects */

            //Hide horizontal menu and submenu title when user clicks on another menu
            if (that.m_transition == true)
            {
                $(that.c_hmenu).hide();

                $('#submenu_title').css('visibility', 'hidden');
            }

            $(that.c_menu + " ul li.active").removeClass('active');
            $(that.c_menu + " ul li a.active").removeClass('active').addClass('default');
            $(that.c_menu + " ul li ul li a.active").removeClass('active').addClass('default');

            $(that.c_menu + " ul li a#m_opt_"+that.m_option).parent().addClass('active');
            $(that.c_menu + " ul li a#m_opt_"+that.m_option).removeClass('default').addClass('active');
            $(that.c_menu + " ul li ul li a#sm_opt_"+that.sm_option).removeClass('default').addClass('active');


            // Set up h_option and l_option by default
            var _h_option = '';
            var _l_option = '';

            try
            {
                _h_option = that.h_menus[sm_option]['default_option'];
            }
            catch(err)
            {
                _h_option = '';

                console.log('<?php echo _("Menu option not found or you don\'t have permissions to see this page")?>');
            }

            that.active_hmenu_option(_h_option, _l_option);
        }
    };


    /**
    * This function actives the clicked option on the secondary menu
    *
    */
    this.active_hmenu_option = function(h_option, l_option)
    {
        var that = this;

        //Set h_option
        if (typeof(h_option) == 'string' && h_option != '')
        {
            that.h_option = h_option;

            //Set l_option
            if (typeof(l_option) == 'string' && l_option != '')
            {
                that.l_option = l_option;
            }
            else
            {
                // l_option is not given, set default l_option for h_option. l_option could be empty.

                that.l_option = that.h_menus[that.sm_option]['items'][h_option]['default_loption'];
            }

            /* JavaScript and CSS Effects */

            $(that.c_hmenu + " ul li a.active").removeClass('active').addClass('default');
            $(that.c_hmenu + " ul li a#h_opt_"+that.h_option).removeClass('default').addClass('active');

            var pos = $(that.c_hmenu + " ul li a.active").position();

            if(typeof(pos) == 'undefined' || pos == null)
            {
                var pos_t = 0;
                var pos_l = 0;
            }
            else
            {
                var pos_t = pos.top;
                var pos_l = pos.left;
            }

            var width = $(that.c_hmenu + " ul li a.active").parent().width();

            $('#blob').animate(
            {
                left : pos_l,
                top : pos_t,
                width : width
            },
            {
                duration : 400,
                easing : 'easeOutExpo',
                queue : false
            });
        }
    };


    /**
    * This function draws the secondary menu
    *
    */
    this.show_hmenu = function()
    {
        var that = this;

       //URL has menu options in the query string, we update menu options
        var url = document.getElementById("main").contentWindow.document.location.href;

            url = $.url(url);

        var _m_option  = (typeof(url.param('m_opt'))  != 'undefined') ? url.param('m_opt')  : '';
        var _sm_option = (typeof(url.param('sm_opt')) != 'undefined') ? url.param('sm_opt') : '';
        var _h_option  = (typeof(url.param('h_opt'))  != 'undefined') ? url.param('h_opt')  : '';
        var _l_option  = (typeof(url.param('l_opt'))  != 'undefined') ? url.param('l_opt')  : '';


        //Check conditions
        var cnd_1 = (typeof(_m_option)  == 'string'  && _m_option  != '');
        var cnd_2 = (typeof(_sm_option) == 'string'  && _sm_option != '');
        var cnd_3 = (typeof(_h_option)  == 'string'  && _h_option  != '');
        var cnd_4 = (typeof(_l_option)  == 'string'  && _l_option  != '');

        //We are in the same place, don't change current options
        if (!cnd_1 && !cnd_2 && !cnd_3 && !cnd_4)
        {
            return false;
        }

        //Set up menu option
        if (cnd_1 && cnd_2)
        {
            var sm_option = _m_option + '-' + _sm_option;
            that.active_option(sm_option);
        }


        //Set up and draw secondary menu
        var c_h_id = that.c_hmenu + '_' + that.sm_option;

        //Secondary menu already exists, we only set up the clicked option
        if ($(c_h_id).length >= 1)
        {
            /*
             We clicked on a local menu option
                - Secondary menu option doesn't change
                - Local menu option change
            */
            if (_h_option == '')
            {
                _h_option = $(that.c_hmenu + " ul li a.active").attr('id').replace('h_opt_', '');
            }

            that.active_hmenu_option(_h_option, _l_option);
        }
        else
        {
            //We clicked on a new main menu option, Secondary menu change

            try
            {
                var _h_menu = that.h_menus[that.sm_option]['html'];
            }
            catch(err)
            {
                var _h_menu = '';
                console.log('<?php echo _("Submenu option not found or you don\'t have permissions to see this page")?>');
            }

            //Change and show subname title
            /*
            * By default the value is the the selected menu option, if you want to specify a custom name, define the
            * field 'menu_title' in the menu class.
            * */
            var subname_title =  that.h_menus[that.sm_option]['menu_title'] || $(that.c_menu + " ul li ul li a.active").text();

            if (subname_title != '')
            {
                $('#submenu_title').text(subname_title);
                $('#submenu_title').css('visibility', 'visible');
            }
            else
            {
                $('#submenu_title').text('');
            }

            //Add new secondary menu
            if (_h_menu != '')
            {
                $(that.c_hmenu).html(_h_menu);

                //Show secondary menu when it has two options or more
                var num_options = _h_menu.match(/\<li/g);

                if (num_options.length > 1)
                {
                    $(that.c_hmenu).show();
                }
                else
                {
                    $(that.c_hmenu).hide();
                }

                //Activate option by default
                if (_h_option == 'undefined')
                {
                    _h_option = $(that.c_hmenu + " ul li").first().find('a').attr('id').replace('h_opt_', '');
                    _l_option = '';
                }

                //Set up secondary menu option and local option by default if it exists
                that.active_hmenu_option(_h_option, _l_option);

                //Bind handler with horizontal menu
                bind_hmenu_handler(that);
            }
        }
    };


    /**
    * This function realizes some actions before loading page content
    *
    */
    this.pre_load_content = function()
    {
        //Killing the process running in the iframe before starting load the new content
        this._kill_iframe_process();

        //Removing resizing timeout before load new content
        clearTimeout(resize_timeout);

        /* Showing loading box */
        var top_l   = ($(window).height() / 2 ) - 100;
            top_l   = (top_l > 0) ? top_l : '250';

        var config  = {
            style: 'top: '+ top_l +'px;'
        };

        var loading_box = Message.show_loading_spinner('av_main_loader', config);

        if ($('.av_w_overlay').length < 1)
        {
            $(document.body).append('<div class="av_w_overlay"></div>');
        }

        if ($('.l_box').length < 1)
        {
            $(document.body).append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
            this.add_kill_loading();
        }

        $('.l_box').show();
        // End of loading box

        //We make the iframe hidden, but it is important not to do display none!!
        $('#main').css('visibility', 'hidden');

        //Fixing height to 0
        set_height(0);
    };


    /**
    * This function realizes some actions after loading page content
    *
    */
    this.post_load_content = function()
    {
        var that = this;

        //Getting the height of the content of the iframe
        var h = get_height();

        //Setting the iframe height to the height of its content
        set_height(h);

        iframe_height = h;

        //Activating periodic preservation of iframe height
        preserve_height();

        //Hidding loading box
        hide_loading_box();

        //Removing timeout for killing the loading message
        if(loading_timeout)
        {
            clearTimeout(loading_timeout);
        }

        //Hidding icon for killing the loading message
        $('#close_l_box').hide();

        //Focus on the iframe.
        $('#main').contents().find('a:visible').first().focus().blur();

        //Setting the focus to the top of the window.
        window.scrollTo(0,0);

        //Setting the focus of the iframe content to the top of the window.
        top.frames['main'].scrollTo(0,0);

        //Showing the iframe once the contents is loaded
        $('#main').css('visibility', 'visible');

        //Show help icon
        if ($(that.c_hmenu + ' ul li[id^="li_"]').length > 1)
        {
            $('#c_help').css('top', '-80px');
        }
        else
        {
            $('#c_help').css('top', '-30px');
        }

        $('#c_help img').show();


        //Setting the bookmark
        this.set_bookmark();

        //Bind handler with local menu
        bind_lmenu_handler(that);

        $.cookie("sess", random_session_id);
    };


    /**
    * This function loads page content and secondary menu in the iframe
    *
    */
    this.load_content = function(url)
    {
        var that = this;

        $('#main').off('load');
        $('#main').on('load', function()
        {
            that.show_hmenu();
            that.post_load_content();
        });

        var that = this;

        if (url != '')
        {
            //Actions executed before the content was loaded
            that.pre_load_content();

            if(url.match(/^\//) && !url.match(/^\/ossim/))
            {
                var url = that.url_base + url;
            }

            //Load HTML page into iframe
            $('#main').attr('src', url);
        }
        else
        {
            alert('<?php echo Util::js_entities(_("Url not found"))?>');
        }
    };


    this._kill_iframe_process = function()
    {
        try
        {
            if (navigator.appName == 'Microsoft Internet Explorer')
            {
                top.frames['main'].document.execCommand('Stop');
            }
            else
            {
                top.frames['main'].stop();
            }

            this.show_hmenu();
        }
        catch(Err)
        {
            //console.log(Err);
        }
    }


    this.add_kill_loading = function()
    {
        var that = this;

        $('#av_main_loader').append('<div id="close_l_box" title="<?php echo _('Stop Loading') ?>"><img src="/ossim/pixmaps/cross_white_icon.png"/></div>');

        $('#close_l_box').on('click', function()
        {
            that._kill_iframe_process();
            hide_loading_box();

        });

        if(loading_timeout)
        {
            clearTimeout(loading_timeout);
        }

        loading_timeout = setTimeout(function()
        {
            $('#close_l_box').show();

        }, 15000);
    }


    /**
    * This function returns the help URL related to current page
    *
    */
    this.show_help = function(h_window)
    {
        var that = this;

        var menu_option = that.sm_option.split('-');

        var _m_option  = menu_option[0];
        var _sm_option = menu_option[1];
        var _h_option  = that.h_option;
        var _l_option  = that.l_option;

        $.ajax({
            data: "m_opt="+_m_option+"&sm_opt="+_sm_option+"&h_opt="+_h_option+"&l_opt="+_l_option,
            type: "POST",
            url: '/ossim/home/providers/get_help.php',
            dataType: "json",
            beforeSend: function()
            {
                $('#c_help img').attr('src', '/ossim/pixmaps/loader.gif');
            },
            error: function(data)
            {
                $('#c_help img').attr('src', '/ossim/pixmaps/help_hmenu_gray.png');

                //Check expired session
                var session = new Session(data, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();

                    return false;
                }

                var url = 'https://www.alienvault.com/help/product/';

                h_window.location.href = url;
            },
            success: function(data)
            {
                $('#c_help img').attr('src', '/ossim/pixmaps/help_hmenu_gray.png');

                try
                {
                    var url = data.url;
                }
                catch(err)
                {
                    var url = 'https://www.alienvault.com/help/product/';
                }

                h_window.location.href = url;
            }
        });
    };


    /* Remote Interfaces */

    /**
    * This function shows the link to remote interfaces
    *
    */
    this.add_ri_link = function()
    {
        $('#link_settings').after("<a id='link_remote_interfaces' href='javascript:void(0);'><?php echo _('Remote Interfaces')?></a>");

        $('#link_remote_interfaces').off('click')

        $('#link_remote_interfaces').click(function(event)
        {
            event.preventDefault();

            params = {
                caption : "<?php echo _('Launch Remote Interfaces')?>",
                url     : '/ossim/remote_interfaces/launch_ri.php',
                height  : 600,
                width   : 700
            };

            LB_show(params);
        });
    };


    /**
    * This function deletes the link to remote interfaces
    *
    */
    this.remove_ri_link = function()
    {
        $('#sep_ri').remove();
        $('#link_remote_interfaces').before().remove();
        $('#link_remote_interfaces').remove();
    }


    /* Private functions */

    function bind_menu_handler(that)
    {
        //Unbind all handlers
        $(that.c_menu + " ul li ul li a").off();

        $(that.c_menu + " ul li ul li a").each(function(index, value)
        {
            //Bind click event with submenu option

            $(this).click(function(event)
            {
                event.preventDefault();

                var _h_option = $(this).parent().attr('id').replace('li_', '');

                that.active_option(_h_option);

                that.load_content(that.h_menus[that.sm_option]['default_url']);
            });
        });
    }


    function bind_hmenu_handler(that)
    {
        //Unbind all handlers
        $(that.c_hmenu + " ul li a").off();

        $(that.c_hmenu + " ul li a").each(function(index, value) {

            //Bind click event with hmenu option

            $(this).click(function(event){
                event.preventDefault();

                that.load_content($(this).attr('href'));
            });
        });


        $(that.c_hmenu + " ul").spasticNav({
            overlap : 0,
            speed : 400,
            reset : 200
        });
    }


    function bind_lmenu_handler(that)
    {
        //Unbind all handlers
        $("#main").contents().find("#c_lmenu .button").off();

        $("#main").contents().find("#c_lmenu .button").on("click", function (event) {

            event.preventDefault();

            var caption = $(this).text();
            var url     = $(this).attr('href');
            var height  = '600';
            var width   = '80%';

            if (typeof(url) != 'undefined' && url != '')
            {
                if ($(this).hasClass('m_greybox'))
                {
                    url    = that.url_base + url;
                    params = {
                        caption : caption,
                        url     : url,
                        height  : height,
                        width   : width
                    };
                    parent.LB_show(params);
                }
                else
                {
                    that.load_content(url);
                }
            }
        });
    }



    /* IFRAME FUNCTIONS */

    /* Gets the iframe content body height */
    function get_height()
    {
        var height = 0;

        if($("#main").contents().find('body').length > 0)
        {
            var _h1 = $("#main").contents().find('html').outerHeight(true);
            var _h2 = $("#main").contents().find('body').outerHeight(true);

            height = (_h1 > _h2) ? _h1: _h2;
        }

        return height;
    }


    /* Sets the iframe height */
    function set_height(h)
    {
        $('#main').height(h);
    }


    function compute_new_height(reload)
    {
        var h = get_height();

        if(h != iframe_height)
        {
            iframe_height = h;
            set_height(iframe_height);
        }

        if(reload)
        {
            resize_timeout = setTimeout(function()
            {
                compute_new_height(true);
            }, 750);
        }
    }


    /* Periodic function resize the iframe */
    function preserve_height()
    {
        compute_new_height(true);
    }


    /*
    *
    *   Bookmarks Functions
    *
    */

    //This function add to the url the bookmark string
    this.set_bookmark = function()
    {
        //Getting the current hash
        var hash = location.hash;
            hash = clean_hash(hash);

        //Getting the new hash.
        bookmark = this.get_new_hash();

        //If the new hash is empty, nothing is set up on the url
        if(bookmark == '' )
        {
            return false;
        }

        bookmark = '#' + bookmark;

        if (this.bookmark_param != '')
        {
            //Adding the parameter
            bookmark += '-' + this.bookmark_param;
            this.bookmark_param = '';
        }

        //Adding the system info
        if (this.show_system_info)
        {
            bookmark += '    --    [' + this.system_name + ' - ' + this.system_ip + ']';
        }

        //If the current hash is not equal to the new hash, then we set up the new hash.
        if(hash != bookmark)
        {
            //Checking if we can use HTML5 hash function
            if(typeof history.replaceState == 'function')
            {
                history.replaceState(null, null, bookmark);
            }
            //If not, we use the normal function.
            else
            {
                location.hash = bookmark;
            }
        }
    }


    //This function set the class variable bookmark_param with the given param.
    this.set_bookmark_params = function(param)
    {
        //If the param is empty, then finish.
        if(param == '' )
        {
            return false;
        }

        this.bookmark_param = param;
    }


    //This functions gets the bookmark string from the menu options when we are currently.
    this.get_new_hash = function()
    {
        //Getting the menu options of the current page
        var moptions = this.sm_option.split('-');

        //Bookmark
        var bookmark = new Array();
        //Parameters which will compose the bookmark string: Menu Option, Submenu Option, Hmenu option.
        var c_list   = new Array(moptions[0], moptions[1], this.h_option);

        //Going through the options to build up the bookmark string
        for (var i = 0; i < c_list.length; i++)
        {
          elem = c_list[i];

          //Adding the option to the string if this one is not empty
          if(elem != '')
          {
              bookmark.push(elem);
          }
        }
        //Joining all the bookmarks strings using "/"
        bookmark = bookmark.join('/');

        return bookmark;
    }


    //This function get the url of one specific bookmark.
    this.get_bookmark_url = function()
    {
        //Getting the current hash
        var _hash  = location.hash;

        _hash = clean_hash(_hash);

        //If the hash is empty, the url to load is empty
        if(_hash == '' || _hash == '#')
        {
            return '';
        }

        //Splitting the hash by "-" since the hash format is as follow: #option/suboption/hoption-param
        _hash     = _hash.split('-');

        //Getting the bookmark string
        var hash  = _hash[0];
        //Getting the param
        var param = _hash[1];

        //Adapting the bookmark string to get the url using the hash
        hash = hash.replace(/\//g, '-').replace('#', '');

        //Getting the url from the hash "bookmark string --> url"

        var aux_hash = hash.split('-');

        var _m_option  = aux_hash[0];
        var _sm_option = aux_hash[0]+'-'+aux_hash[1];
        var _h_option  = aux_hash[2];

        try
        {
            var _url = this.h_menus[_sm_option]['items'][_h_option]['bookmark'];
        }
        catch(err)
        {
            var _url = null;
        }

        if(typeof(_url) == 'object' && _url != null)
        {
            var url = '';

            //Getting the menu parameters for the url

            this.m_option  = _m_option;
            this.sm_option = _sm_option;
            this.h_option  = _h_option;

            if(typeof param != 'undefined' && _url['param'] != null)
            {
                url = _url['param'] + param;
            }
            else
            {
               url = _url['url'];
            }

            //Building the url plus parameters
            url = this.get_menu_url(url, aux_hash[0],  aux_hash[1], aux_hash[2], '');

            return url;
        }
        else
        {
            return '';
        }
    }


    //This functions returns the url with the menu options added
    this.get_menu_url = function(url, m_option, sm_option, h_option, l_option)
    {
        //If the url is empty then returns empty
        if (typeof(url) == 'undefined' || url == '')
        {
            return '';
        }

        //The first operator for the parameters is by default "?"
        var link = '?';

        //If the "?" appears in the given url, then the default operator for parameters changes to "&"
        if(url.match(/\?/))
        {
            link = '&';
        }

        //Menu option and submenu_option have to be both fulfilled or then we return the given url without any menu option

        var cnd_1 = (typeof(m_option) != 'undefined'  && m_option  != '');
        var cnd_2 = (typeof(sm_option) != 'undefined' && sm_option != '');
        var cnd_3 = (typeof(h_option) != 'undefined'  && h_option  != '');
        var cnd_4 = (typeof(l_option) != 'undefined'  && l_option  != '');

        if (cnd_1 && cnd_2)
        {
            //Menu option
            url += link + 'm_opt=' + m_option;
            //Sub menu option
            url += "&sm_opt=" + sm_option;

            //H_menu option
            if (cnd_3)
            {
                url += "&h_opt=" + h_option;
            }

            //l_menu option
            if (cnd_4)
            {
                url += "&l_opt=" + l_option;
            }
        }

        return url;
    }


    function clean_hash(hash)
    {
        return hash.replace(/\s+.*$/, '');
    }


    /*  FUNCTION TO IDENTIFY THE SYSTEM ON THE TOP BAR */

    this.display_system_name = function(max_length)
    {
        $('#top_system_info').empty();

        if (!this.show_system_info)
        {
            return false;
        }

        if (typeof max_length != 'number')
        {
            max_length = 15;
        }

        var hostname = this.system_name;

        if (hostname.length > max_length)
        {
            hostname = hostname.substr(0, max_length) + '&#8230;';
        }

        var elem = $('#top_system_info');

        var name = $('<span/>',
        {
            'title': this.system_name + '   ' + this.system_ip,
            'html' : hostname + '  ' + this.system_ip
        }).appendTo(elem);

        if (typeof $.fn.tipTip == 'function')
        {
            name.tipTip();
        }

        $('<span/>',
        {
            'class': 'sep_ri',
            'text' : '|'
        }).appendTo(elem);

        var title = document.title;
            title = title.replace('/\s+\[.*?\]$/', '');

        document.title = title + ' [' + this.system_name + ' - ' + this.system_ip + ']';
    }
};


/*** Overlay spinner isolated functions ***/

var timeout_qry_state = null;
var db_image = '<img style="vertical-align: text-bottom" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAALVWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNC4yLjItYzA2MyA1My4zNTI2MjQsIDIwMDgvMDcvMzAtMTg6MTI6MTggICAgICAgICI+CiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczpJcHRjNHhtcENvcmU9Imh0dHA6Ly9pcHRjLm9yZy9zdGQvSXB0YzR4bXBDb3JlLzEuMC94bWxucy8iCiAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgIHhtbG5zOnN0RXZ0PSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VFdmVudCMiCiAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICB4bWxuczp4bXBSaWdodHM9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9yaWdodHMvIgogICBwaG90b3Nob3A6QXV0aG9yc1Bvc2l0aW9uPSJBcnQgRGlyZWN0b3IiCiAgIHBob3Rvc2hvcDpDcmVkaXQ9Ind3dy5nZW50bGVmYWNlLmNvbSIKICAgcGhvdG9zaG9wOkRhdGVDcmVhdGVkPSIyMDEwLTAxLTAxIgogICBJcHRjNHhtcENvcmU6SW50ZWxsZWN0dWFsR2VucmU9InBpY3RvZ3JhbSIKICAgeG1wOk1ldGFkYXRhRGF0ZT0iMjAxMC0wMS0wM1QyMTozMzoxMyswMTowMCIKICAgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjgzMURGOTNDODFGN0RFMTE5RUFCOTBENzA3OEFGOTRBIgogICB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjgzMURGOTNDODFGN0RFMTE5RUFCOTBENzA3OEFGOTRBIgogICB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjU3ODI0QzM3QTdGOERFMTE4MjFDRTRCMkM3RTM2RDcwIj4KICAgPElwdGM0eG1wQ29yZTpDcmVhdG9yQ29udGFjdEluZm8KICAgIElwdGM0eG1wQ29yZTpDaUFkckNpdHk9IlByYWd1ZSIKICAgIElwdGM0eG1wQ29yZTpDaUFkclBjb2RlPSIxNjAwMCIKICAgIElwdGM0eG1wQ29yZTpDaUFkckN0cnk9IkN6ZWNoIFJlcHVibGljIgogICAgSXB0YzR4bXBDb3JlOkNpRW1haWxXb3JrPSJrYUBnZW50bGVmYWNlLmNvbSIKICAgIElwdGM0eG1wQ29yZTpDaVVybFdvcms9Ind3dy5nZW50bGVmYWNlLmNvbSIvPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJzYXZlZCIKICAgICAgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDo4MzFERjkzQzgxRjdERTExOUVBQjkwRDcwNzhBRjk0QSIKICAgICAgc3RFdnQ6d2hlbj0iMjAxMC0wMS0wMlQxMDoyODo1MSswMTowMCIKICAgICAgc3RFdnQ6Y2hhbmdlZD0iL21ldGFkYXRhIi8+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOkNDMjgxQTAzREJGN0RFMTFBOTAwODNFMEExMjUzQkZEIgogICAgICBzdEV2dDp3aGVuPSIyMDEwLTAxLTAyVDIxOjExOjI5KzAxOjAwIgogICAgICBzdEV2dDpjaGFuZ2VkPSIvbWV0YWRhdGEiLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiCiAgICAgIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6NTc4MjRDMzdBN0Y4REUxMTgyMUNFNEIyQzdFMzZENzAiCiAgICAgIHN0RXZ0OndoZW49IjIwMTAtMDEtMDNUMjE6MzM6MTMrMDE6MDAiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii9tZXRhZGF0YSIvPgogICAgPC9yZGY6U2VxPgogICA8L3htcE1NOkhpc3Rvcnk+CiAgIDxkYzp0aXRsZT4KICAgIDxyZGY6QWx0PgogICAgIDxyZGY6bGkgeG1sOmxhbmc9IngtZGVmYXVsdCI+Z2VudGxlZmFjZS5jb20gZnJlZSBpY29uIHNldDwvcmRmOmxpPgogICAgPC9yZGY6QWx0PgogICA8L2RjOnRpdGxlPgogICA8ZGM6c3ViamVjdD4KICAgIDxyZGY6QmFnPgogICAgIDxyZGY6bGk+aWNvbjwvcmRmOmxpPgogICAgIDxyZGY6bGk+cGljdG9ncmFtPC9yZGY6bGk+CiAgICA8L3JkZjpCYWc+CiAgIDwvZGM6c3ViamVjdD4KICAgPGRjOmRlc2NyaXB0aW9uPgogICAgPHJkZjpBbHQ+CiAgICAgPHJkZjpsaSB4bWw6bGFuZz0ieC1kZWZhdWx0Ij5UaGlzIGlzIHRoZSBpY29uIGZyb20gR2VudGxlZmFjZS5jb20gZnJlZSBpY29ucyBzZXQuIDwvcmRmOmxpPgogICAgPC9yZGY6QWx0PgogICA8L2RjOmRlc2NyaXB0aW9uPgogICA8ZGM6Y3JlYXRvcj4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGk+QWxleGFuZGVyIEtpc2VsZXY8L3JkZjpsaT4KICAgIDwvcmRmOlNlcT4KICAgPC9kYzpjcmVhdG9yPgogICA8ZGM6cmlnaHRzPgogICAgPHJkZjpBbHQ+CiAgICAgPHJkZjpsaSB4bWw6bGFuZz0ieC1kZWZhdWx0Ij5DcmVhdGl2ZSBDb21tb25zIEF0dHJpYnV0aW9uIE5vbi1Db21tZXJjaWFsIE5vIERlcml2YXRpdmVzPC9yZGY6bGk+CiAgICA8L3JkZjpBbHQ+CiAgIDwvZGM6cmlnaHRzPgogICA8eG1wUmlnaHRzOlVzYWdlVGVybXM+CiAgICA8cmRmOkFsdD4KICAgICA8cmRmOmxpIHhtbDpsYW5nPSJ4LWRlZmF1bHQiPkNyZWF0aXZlIENvbW1vbnMgQXR0cmlidXRpb24gTm9uLUNvbW1lcmNpYWwgTm8gRGVyaXZhdGl2ZXM8L3JkZjpsaT4KICAgIDwvcmRmOkFsdD4KICAgPC94bXBSaWdodHM6VXNhZ2VUZXJtcz4KICA8L3JkZjpEZXNjcmlwdGlvbj4KIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+Cjw/eHBhY2tldCBlbmQ9InIiPz6kY5+uAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAQtJREFUeNqkU8ENgzAMxNAPEo+++fTJA4kyQkZgg7abdIOOUEboCGzQlB8LMAESH1B6jpKolQqCEslyArbvfE48b+Miu8nz/Ah3JiLBx4l4qZSq4Esp5csVyLLsbpIf8BW8/JWNZC4s4AsuUtf1xUvT9ABTsNNS2hzLOY5BkiTKIGgGTHUiVzMAQ2bgNU1DO96M42gDCmOLly4wDAOjipUDqFwBMNgzMk8AbcxOATGVmcTVaRDHsdWAe7c6/FrCAGmAtm3JtvApUr5aA7SwTQMw2KZBFEXq46rOamDugAbouo50gTAM1T8Pqe97d5FKIJ9XvUKi8utDEARH3/dvsCdMTRj/u3GszXsLMAAZFnp98OzEgAAAAABJRU5ErkJggg=="/>';

var random_session_id = (Math.random()*1e32).toString(36);

function show_overlay_spinner(qry_state)
{
    /* Showing loading box */
    var top_l   = ($(window).height() / 2 ) - 100;
        top_l   = (top_l > 0) ? top_l : '250';

    var config  = {
        style: 'top: '+ top_l +'px;'
    };

    var loading_box = Message.show_loading_spinner('av_main_loader', config);
    var loading_msg = '';

    if (typeof qry_state != 'undefined')
    {
        var config = {
            style: 'top: '+ (top_l+120) +'px; display:none'
        };

        var loading_msg  = Message.show_loading_spinner('av_qry_state', config);
    }

    if ($('.av_w_overlay').length < 1)
    {
        $(document.body).append('<div class="av_w_overlay"></div>');
    }

    if ($('.l_box').length < 1)
    {
        $(document.body).append('<div class="l_box" style="display:none;">' + loading_msg + loading_box+'</div>');
    }

    $('.l_box').show();

    if (typeof qry_state != 'undefined')
    {
        timeout_qry_state = setTimeout('get_qry_state()', 5000);
    }
    else
    {
        $('#av_qry_state').hide();
    }
}


function hide_overlay_spinner()
{
    clearTimeout(timeout_qry_state);
    timeout_qry_state = null;

    //Hiddig loading box
    hide_loading_box();
    $('#close_l_box').hide();
    $('#av_qry_state').hide();

    //Focus on the iframe.
    $('#main').contents().find('a:visible').first().focus().blur();

    //Setting the focus to the top of the window.
    window.scrollTo(0,0);

    //Setting the focus of the iframe content to the top of the window.
    top.frames['main'].scrollTo(0,0);
}


function get_qry_state(force)
{
    var force_kill = (typeof(force) != 'undefined') ? '?force_kill=1' : '';

    $.cookie("sess", random_session_id);

    $.ajax(
    {
        url: "/ossim/home/controllers/qry_state.php" + force_kill,
        dataType: "json",
        success: function(data)
        {
            if(typeof(data) != 'undefined' && data != null && data.status != '')
            {
                $('#av_qry_state').show();
                $('#av_qry_state').html(db_image +' '+ data.status)

                if (timeout_qry_state != null)
                {
                    timeout_qry_state = setTimeout('get_qry_state()', 5000);
                }
            }
        }
    });
}
