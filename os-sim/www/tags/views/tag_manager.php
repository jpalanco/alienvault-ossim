<?php

/**
 * tag_manager.php
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
 * Public License can be found in `/usr/share/common-licenses/GPL-2'.
 *
 * Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

require_once 'av_init.php';

// Checking admin privileges
if (!Session::am_i_admin())
{
    echo ossim_error(_('You do not have permissions to see this section'));
    exit();
}

// Get type
$tag_type = GET('tag_type');

// Validate action type
ossim_valid($tag_type, OSS_ALPHA, '_', 'illegal:' . _('Label type'));

if (ossim_error())
{
    echo $GLOBALS['ossim_last_error'];
    exit();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('Label Manager') ?></title>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',             'def_path' => TRUE),
        array('src' => 'tipTip.css',                'def_path' => TRUE),
        array('src' => 'jquery-ui.css',             'def_path' => TRUE),
        array('src' => 'jquery.dataTables.css',     'def_path' => TRUE),
        array('src' => 'av_tags.css',               'def_path' => TRUE),
        array('src' => 'tags/tags.css',             'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                     'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
        array('src' => 'jquery.dataTables.js',              'def_path' => TRUE),
        array('src' => 'utils.js',                          'def_path' => TRUE),
        array('src' => 'notification.js',                   'def_path' => TRUE),
        array('src' => 'token.js',                          'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',                  'def_path' => TRUE),
        array('src' => 'ajax_validator.js',                 'def_path' => TRUE),
        array('src' => 'messages.php',                      'def_path' => TRUE),
        array('src' => '/tags/js/tag_manager.js.php',       'def_path' => FALSE)
    );

    Util::print_include_files($_files, 'js');

    $action = $tag_type == "incident" ? "/ossim/incidents/incidenttag.php" : "../controllers/tag_actions.php";
    ?>

</head>
<body>

<form method="POST" name="tag_form" id="tag_form" action="<?=$action?>" enctype="multipart/form-data">

    <div id="tag_manager">

        <div id="av_info" class="hidden"></div>

        <div id="tag_list_container">
            <table class="noborder table_data" id="tag_table">
                <thead>
                <tr>
                    <th class="th_tag"><?php echo _('Label List') ?></th>
                    <th class="th_actions"><?php echo _('Actions') ?></th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>

        <div id="tag_form_container">
            <fieldset>
                <legend><?php echo _('Name') ?></legend>
                <input type="text" id="tag_name" name="tag_name" class="tag_field vfield" maxlength="30"/>
		<? if ($tag_type == "incident") { ?>
                <legend><?php echo _('Description') ?></legend>
                <textarea id="tag_description" name="tag_description" class="tag_field vfield"></textarea>
		<? } ?>
            </fieldset>


            <fieldset>
                <legend><?php echo _('Style') ?></legend>
                <?php for ($i = 1; $i <= 24; $i++)
                {
                    echo '<span class="tag_style av_tag_'.$i.'">'._('Label').'</span>';
                } 
                ?>
            </fieldset>

            <fieldset class="tag_preview">
                <legend><?php echo _('Preview') ?></legend>
                <span id="tag_preview" class="av_tag_1"><?php echo _('Label') ?></span>
            </fieldset>

            <input type="hidden" id="tag_id" name="tag_id" class="vfield" value=""/>
            <input type="hidden" id="tag_type" name="tag_type" class="vfield" value="<?php echo $tag_type ?>"/>
            <input type="hidden" id="tag_class" name="tag_class" class="vfield" value=""/>
            <input type="hidden" id="action" name="action" class="vfield" value="save_tag"/>

        </div>

    </div>

    <div id="action_buttons">
        <input type="button" name="send" id="send" value="<?php echo _('Save') ?>"/>
        <input type="button" name="cancel" id="cancel" class="av_b_secondary" value="<?php echo _('Cancel') ?>"/>
    </div>

</form>

</body>
</html>
