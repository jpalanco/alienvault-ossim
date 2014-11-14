<?php
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

$db_path = '/usr/share/ossim/www/dashboard';
set_include_path(get_include_path() . PATH_SEPARATOR . $db_path);


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");

list($db, $conn) = Ossim_db::get_conn_db();

$login = Session::get_session_user();
$pro   = Session::is_pro();


/* Getting the default tab */
if (!empty($_SESSION['default_tab']))
{
    $default_tab = $_SESSION['default_tab'];
}
else
{
    $config_aux  = new User_config($conn);
    $default_tab = $config_aux->get($login, 'panel_default', 'simple', "main");
    $default_tab = ($default_tab > 0) ? $default_tab : 1;

    //We save the default tab in session
    $_SESSION['default_tab'] = $default_tab;
}

/* Getting the current panel */
$panel_id = $default_tab;

if (GET('panel_id') != "")
{
    $panel_id = GET('panel_id');
}
elseif ($_SESSION['_db_panel_selected'] != "")
{
    $panel_id = $_SESSION['_db_panel_selected'];
}


$edit = 0;

if (GET('edit') != "")
{
    $edit = GET('edit');
}
elseif ($_SESSION['_db_show_edit'] != "")
{
    $edit = $_SESSION['_db_show_edit'];
}

$fullscreen = GET('fullscreen');

ossim_valid($panel_id,      OSS_DIGIT,              'illegal:' . _("Tab ID"));
ossim_valid($edit,          '0','1',OSS_NULLABLE,   'illegal:' . _("Edit Tab Option"));
ossim_valid($fullscreen,    OSS_DIGIT,OSS_NULLABLE, 'illegal:' . _("Fullscreen Option"));

if (ossim_error()) 
{
    die(ossim_error());
}


/* Getting the mode we want to see the tabs */
if ($edit == '1')
{
    $_SESSION['_db_show_edit'] = "1";
}
else
{
    $_SESSION['_db_show_edit'] = "0";
}

/* Getting the tab list */
$tab_list = Dashboard_tab::get_tabs_by_user($login, $edit);


if (empty($tab_list))
{
    $config_nt = array(
        'content' => _('No tabs have been found').".",
        'options' => array (
            'type'          => 'nf_warning',
            'cancel_button' => ''
        ),
        'style'   => ' margin:25px auto 0 auto;text-align:center;padding:3px 30px;'
    ); 
    
    $nt = new Notification('nt_panel', $config_nt);
    $nt->show();

    die(); 
}

if(empty($tab_list[$panel_id]))
{
    $_panel_keys = array_keys($tab_list);
    $panel_id    = $_panel_keys[0];
}

$_SESSION['_db_panel_selected'] = $panel_id;

if (Session::menu_perms("dashboard-menu", "ControlPanelExecutiveEdit")) 
{
    $show_edit = $edit;
    $can_edit  = 1;
} 
else
{
    $show_edit = 0;
    $can_edit  = 0;
}

session_write_close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("AlienVault USM"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <?php

        $dashboard_refresh = ($conf->get_conf("dashboard_refresh", FALSE) );

        if ($dashboard_refresh!=0)
        {
           echo('<meta http-equiv="refresh" content="'.$dashboard_refresh.'">');
        }

    ?>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                         'def_path' => TRUE),
            array('src' => 'tipTip.css',                            'def_path' => TRUE),
            array('src' => 'coolbox.css',                           'def_path' => TRUE),
            array('src' => 'dashboard/overview/dashboard.css',      'def_path' => TRUE)
            
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                       'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                    'def_path' => TRUE),
            array('src' => 'utils.js',                            'def_path' => TRUE),
            array('src' => 'token.js',                            'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                    'def_path' => TRUE),
            array('src' => '/dashboard/js/jquery.editinplace.js', 'def_path' => FALSE),
            array('src' => 'greybox.js',                          'def_path' => TRUE),
            array('src' => 'coolbox.js',                          'def_path' => TRUE),
            array('src' => 'notification.js',                     'def_path' => TRUE),
            array('src' => '/dashboard/js/dashboard.js.php',      'def_path' => FALSE),
            array('src' => '/dashboard/js/dashboard_widget.js',   'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>

    <script type='text/javascript'>

        var panel_id      = '<?php echo $panel_id ?>';
        var default_tab   = '<?php echo $default_tab ?>';
        var layout        = 1;
        var edit_mode     = '<?php echo ($show_edit) ? "edit" : "view" ?>';
        var fullscreen    = <?php echo ($fullscreen) ? "true" : "false" ?>;
        var locked        = true;

        var unknown_error = "<?php echo _('Unknown Error')?>";
        var src_ajax      = "src/dashboard_ajax.php";

        var httpR         = null;
        
        
        $(document).ready(function() 
        {
            GB_TYPE = 'w';
            
            $("a.greybox_add").on("click", function()
            { 
                var u = this.href + panel_id;
                var t = this.title || $(this).text() || this.href;

                GB_show_nohide(t, u, 600, 1000);

                return false;
            });


            $('#op_fullscreen').on('click', function()
            {
                db_fullscreen();
            });

            $('#op_permissions').on('click', function()
            {
                
                var u = "/ossim/dashboard/sections/perms/index.php";                
                var t = "<?php echo Util::html_entities2utf8(_('Dashboard Permissions')) ?>";
                
                GB_show_nohide(t, u, 650, 1200);
                
                return false;
            });


            $('#op_edition').on('click', function()
            {   
                var url = "index.php?panel_id=" + panel_id + "&edit=<?php echo ($show_edit)? 0 : 1 ?>";
                
                document.location.href = url;
                
            });


            /*********************************** PREVENT ***********************************/
            //Preventing right click default action of the mouse when right-clicking above the title and icon of the tabs
            $(document).on('contextmenu', ".editInPlace, a.dashboard_options", function(e){ e.preventDefault() });            


            //Adding sortable property to the tabs
            $(".sortable").sortable(
            {
                axis: 'x',
                tolerance: 'pointer',
                start: function()
                {
                    $('#db_tab_blob').hide();
                    $('.db_tab_opts').css('visibility', 'hidden');
                },
                stop: function() 
                {
                    $('.db_tab_opts').css('visibility', 'visible');
                    saveTabsOrder();

                    draw_selected_layer(false);
                    $('#db_tab_blob').show();
                        
                }

            }).disableSelection();
            

            /************************************ COOLBOX ***********************************/
            
            //Menu to create or clone a new tab
            $("a.coolbox_add").on('click',function(e)
            {
                var url = this.href;
                
                CB_show(this, this.title, url , 200, 280);
                
                return false;
            });
            

            //Menu to create or clone a new tab
            $(".tab-options").on('click', function(e)
            {
                if(e.which === 1 || e.which === 3)
                {     
                    var url   = $(this).data('url');
                    
                    var title = $(this).attr('title');

                    CB_show(this,title,url,100,125);                
                }
            });
            
            $("a.dashboard_options").on('mousedown', function(e)
            {
                if(e.which === 1 || e.which === 3)
                {                         
                    var url = this.href;
                    
                    CB_show(this, this.title, url,125, 125);     

                }

                return false;
                
            });
                    
            /******************************** EDIT IN PLACE ********************************/
            
            //Edit in place: It chandes directly the title of the tab
            $(".editInPlace").editInPlace(
            {
                preinit: function()
                {
                    $('#db_tab_blob').hide();
                },
                postclose: function()
                {
                    $('#db_tab_blob').show();
                },
                callback: function(unused, enteredText, prevText) 
                { 
                    var id  = $(this).parents("div.db_tab_tab").first().data('id');
                    change_tab_title(id, enteredText, prevText);
                    
                    return true;                
                },                
                show_buttons  : true,
                save_button   : '<button class="inplace_save eipbutton"><img align="absmiddle" border="0" height="14px" width="14px" src="../pixmaps/tables/tick.png"></button>',
                cancel_button : '<button class="inplace_cancel eipbutton"><img align="absmiddle" border="0" height="14px" width="14px" src="../pixmaps/tables/cross.png"></button>',
                bg_over       : 'transparent'
            });       

            /***************************** TABS FUNCTIONS *****************************/
            
            $(".tab-options").hover(function()
            {
                $(this).removeClass('ui-icon-plus');
                $(this).addClass('ui-icon-circle-plus');    

            }, function()
            {
                $(this).removeClass('ui-icon-circle-plus');
                $(this).addClass('ui-icon-plus');
            });
            
            
            $(".db_tab_title").click(function()
            {
                if ($(this).find('.inplace_field').length == 0)
                {
                    var id = $(this).parent('div').data('id');
                    load_tab(id);
                }
            });    
            
            setTimeout(function()
            {
                load_tab(panel_id);
                
            }, 500);
            

        });// End of document.ready
        
    </script>

</head>


<body style="<?php echo ($fullscreen)? 'overflow-y:auto!important' : ''?>" >


<?php   
if ($fullscreen != 1) 
{
    include "sections/tabs/tabs.php";
}
else 
{
    $current_tab = $tab_list[$panel_id];
    if (is_object($current_tab))
    {
        echo "<h2 id=> " . $current_tab->get_title() . "</h2>";
    }
    
}
?>

<div id="db_notif_container"></div>

<!-- displays saveConfig errors -->
<div class="db_notif_container" style="<?php echo ($show_edit) ? "" : "display:none" ?>">
    <div id="db_notif"  class="db_notif"></div>
    <div id="db_unsave" class="db_notif"></div>
</div>


<div id='edit_switch_view'></div>

<div id='menu_config_container'>
    <div id="menu_config">
        <div class='config_sec'>
            <a href="sections/wizard/wizard.php?tab_panel=" class="greybox_add" title="Widget Wizard"><strong><?php echo _('Add widget') ?></strong></a>
        </div>
        <div id='config_separator'></div>
        <div class='config_sec'>
            <div id="slider_title"><?php echo _('Change Layout') ?>:</div> 
            <div id="slider_layout"></div>
            <div id="span_layout"></div>
        </div>
    </div>
</div>

<div id='locked_edit_tab'></div>


<div id='container' style="" data-tab=""></div>


</body>

</html>
<?php 
$db->close();