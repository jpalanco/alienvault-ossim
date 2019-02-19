<?php

/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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
 * Public License can be found in `/usr/share/common-licenses/GPL-2".
 *
 * Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

require_once "av_init.php";


/********************************
 ****** CHECK USER SESSION ******
 ********************************/

Session::useractive();


// Actual supported messages level and types
$message_levels = array('Info', 'Warning', 'Error');
$message_types  = array('Update', 'Deployment', 'Information', 'AlienVault'/*, 'Ticket', 'Alarm', 'Security'*/);
$todelete = $av_menu->check_perm("message_center-menu", "MessageCenterDelete");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('Message Center')?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php

    // CSS Files
    $_files = array();

    $_files[] = array('src' => 'av_common.css',                                  'def_path' => TRUE);
    $_files[] = array('src' => 'jquery-ui.css',                                  'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.dataTables.css',                          'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.dropdown.css',                          'def_path' => TRUE);


    Util::print_include_files($_files, 'css');

    // JS Files
    $_files = array();

    $_files[] = array('src' => 'jquery.min.js',                                  'def_path' => TRUE);
    $_files[] = array('src' => 'jquery-ui.min.js',                               'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.tipTip.js',                               'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.dataTables.js',                           'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.dropdown.js',                           'def_path' => TRUE);
    $_files[] = array('src' => 'utils.js',                                       'def_path' => TRUE);
    $_files[] = array('src' => 'messages.php',                                   'def_path' => TRUE);
    $_files[] = array('src' => 'token.js',                                       'def_path' => TRUE);
    $_files[] = array('src' => 'greybox.js',                                     'def_path' => TRUE);
    $_files[] = array('src' => 'notification.js',                                'def_path' => TRUE);
    $_files[] = array('src' => 'urlencode.js',                                   'def_path' => TRUE);
    $_files[] = array('src' => 'av_system_notifications.js.php',                 'def_path' => TRUE);
    $_files[] = array('src' => '/message_center/js/message_center.js.php',       'def_path' => FALSE);

    Util::print_include_files($_files, 'js');

    ?>

</head>
<body>

<div id="notifications_container">
    <div id="av_info"></div>

    <!-- Notifications filters -->
    <div class="notifications_left" id="notifications_filters">

        <div id='filter_search' class="filter_section">
            <div class="filter_element">
                <input type="search" id="nf_search" name="nf_search" placeholder="<?php echo _('Search') ?>" maxlength="30"/>
            </div>
        </div>

        <div class="filter_section">
            <div class="filter_element">
                <input type="radio" id="nf_unread" name="nf_view" class="nf_filter"
                       data-filter-type="nf_view" value="unread" checked>
                <label for="nf_unread"><?php echo _('Unread') ?></label><span data-stat="nf_unread"></span>
            </div>
            <div class="filter_element">
                <input type="radio" id="nf_all" name="nf_view" class="nf_filter"
                       data-filter-type="nf_view" value="all">
                <label for="nf_all"><?php echo _('All Messages') ?></label><span data-stat="nf_all"></span>
            </div>
        </div>

        <div class="filter_section">
            <div class="filter_section_title"><?php echo _('Message Type') ?></div>

            <?php foreach ($message_types as $message_type)
            {
                $original_message_type = $message_type;
                $message_type = strtolower($message_type) ;

                echo

                    '<div class="filter_element">'.
                    '<input type="checkbox" id="nf_'.$message_type.'" name="nf_'.$message_type.'" class="nf_filter" '.
                    'data-filter-type="nf_type" value="'.$message_type.'">'.
                    '<label for="nf_'.$message_type.'"> '._($original_message_type). '</label>'.
                    '<span data-stat="nf_'.$message_type.'"></span>'.
                    '</div>';
            } ?>
        </div>

        <div class="filter_section">
            <div class="filter_section_title"><?php echo _('Priority') ?></div>

            <?php foreach ($message_levels as $message_level)
            {
                $original_message_level = $message_level;
                $message_level = strtolower($message_level) ;

                echo

                    '<div class="filter_element">'.
                    '<input type="checkbox" id="nf_'.$message_level.'" name="nf_'.$message_level.'" class="nf_filter" '.
                    'data-filter-type="nf_level" value="'.$message_level.'">'.
                    '<label for="nf_'.$message_level.'"> '._($original_message_level). '</label>'.
                    '<span data-stat="nf_'.$message_level.'"></span>'.
                    '</div>';
            } ?>
        </div>

    </div>

    <div class="notifications_right">
        <div class="msg" id="selectall" style=" display: inline-block; margin: 0 26%; visibility: hidden">
            <?=sprintf(_("You have selected %s messages."),'<span></span>')?>
            <a href="#" ><?=sprintf(_("Select all %s messages."),'<span></span>')?></a>
        </div>

        <?php if ($todelete) { ?>
        <div class="av_table_actions">
            <button id="button_action" class="button avt_action small disabled av_b_disabled" disabled="disabled" href="javascript:;" data-dropdown="#dropdown-actions" data-selection="avt_action"><?php echo _("Actions")?>  ▾ </button>
            <div id="dropdown-actions" data-bind="dropdown-actions" class="dropdown dropdown-close dropdown-tip dropdown-anchor-right">
                <ul class="dropdown-menu center">
                    <li><a href="#" id="delete"><?php echo _("Delete")?></a></li>
                </ul>
            </div>
        </div>
        <?php } ?>
        <!-- Notifications list -->
        <div id="notifications_list">
            <table class="table_data">
                <thead>
                <tr>
                    <?php if ($todelete) { ?>
                    <th><input type='checkbox' id='chk-all-rows'/></th>
                    <?php } ?>
                    <th><?php echo _('Date'); ?></th>
                    <th><?php echo _('Subject'); ?></th>
                    <th><?php echo _('Priority'); ?></th>
                    <th><?php echo _('Type'); ?></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <!-- Notifications details -->
        <div id="notification_details"></div>

    </div>

</div>

</body>
</html>
