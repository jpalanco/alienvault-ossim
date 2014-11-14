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
       $av_menu = unserialize($_SESSION['av_menu']);

       if (!is_object($av_menu))
       {
            $db   = new ossim_db();
            $conn = $db->connect();

            $av_menu = new Menu($conn);

            $db->close();
       }

       $h_menus = $av_menu->get_hmenus_info();

    }
    catch(Exception $e)
    {
        $error_msg = $e->getMessage();
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

    var bookmark_param = '';


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
            var sm_option = _m_option+'-'+_sm_option;
            that.active_option(sm_option);
        }

        //Set up and draw secondary menu
        var c_h_id = that.c_hmenu+'_'+that.sm_option;

        //Secondary menu already exists, we only set up the clicked option
        if ($(c_h_id).length >= 1)
        {
            /*
             We have clicked on a local menu option
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
            var subname_title =  $(that.c_menu + " ul li ul li a.active").text();
                subname_title = (subname_title == '<?php echo _("OTX")?>') ? subname_title+' <?php echo _("(Open Threat Exchange)")?>' : subname_title;
                
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
            url: '/ossim/home/get_help.php',
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
        
        $('#link_remote_interfaces').click(function(event){
             event.preventDefault();
             
             var caption = '<?php echo _("Launch Remote Interfaces")?>';
             var url     = '../remote_interfaces/launch_ri.php';
             var height  = '600';
             var width   = '700';

             LB_show(caption, url, height, width);
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
                    url = that.url_base + url;
                    parent.LB_show(caption, url, height, width);
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

        //Getting the new hash.
        bookmark = this.get_new_hash();
        
        //If the new hash is empty, nothing is set up on the url
        if(bookmark == '' )
        {
            return false;
        }

        bookmark = '#' + bookmark;

        //Removing param if already exist
        bookmark = bookmark.replace(/(\-[A-Z0-9]+)$/, '');
        
        if (bookmark_param != '')
        {
            //Adding the parameter
            bookmark += '-' + bookmark_param;

            bookmark_param = '';
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

        bookmark_param = param;
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

        var _url = this.h_menus[_sm_option]['items'][_h_option]['bookmark'];


        if(typeof(_url) == 'object')
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
};


/*** Overlay spinner isolated functions ***/

function show_overlay_spinner()
{
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
    }

    $('.l_box').show();
}


function hide_overlay_spinner()
{
    //Hiddig loading box
    hide_loading_box();
    $('#close_l_box').hide();

    //Focus on the iframe.
    $('#main').contents().find('a:visible').first().focus().blur();

    //Setting the focus to the top of the window.
    window.scrollTo(0,0);

    //Setting the focus of the iframe content to the top of the window.
    top.frames['main'].scrollTo(0,0);
}