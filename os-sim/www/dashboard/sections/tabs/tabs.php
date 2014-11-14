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

Session::logcheck("dashboard-menu", "ControlPanelExecutive");	

$add_url  = "/ossim/dashboard/sections/tabs/tab_add.php";
?>

<div class='db_tab_container version_color'>

    <?php
    if($show_edit) 
    {
    ?>
        <div class='dashboard_tab_add'>
            <a href='<?php echo $add_url ?>' title="<?php echo _('New Tab') ?>" class='coolbox_add'>+</a>
        </div>
       

    <?php
    }
    ?>

    <div class='db_tab_list <?php echo ($show_edit) ? 'sortable' : ''?>'>

        <?php
        foreach($tab_list as $tab)
        {
            if (!is_object($tab))
            {
                continue;
            }

            echo $tab->print_tab($show_edit);

        }
        ?>  
        <div id="db_tab_blob"></div>

    </div>

    <div class='dashboard_options_tab'> 

        <?php 
        if ($can_edit)
        {
        ?>
        <img id='op_edition' class='db_img_opt' src='pixmaps/edit.png' title="<?php echo ($show_edit) ? _('Switch to View Mode') : _('Switch to Edit Mode') ?>"/>
        <?php
        }
        if ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin()) )
        { 
        ?>
        <img id='op_permissions' class='db_img_opt'src='pixmaps/permissions.png' title="<?php echo _('Permissions') ?>"/>
        <?php  
        }
        ?>
        <img id='op_fullscreen' class='db_img_opt' src='pixmaps/full-screen.png' title="<?php echo _('Full Screen') ?>"/>

    </div>

</div>