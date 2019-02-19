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

Session::logcheck('environment-menu', 'PolicyHosts');

session_write_close();

//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


//Properties
$p_obj      = new Properties($conn);
$properties = $p_obj->get_properties();

//Closing database connection
$db->close();

// Nor Operating System neither Model will be displayed
unset($properties[3]);
unset($properties[14]);
?>

<div id="tp_container">

    <div id='tp_av_info'></div>

    <div class='pf_container'>
        <form method="POST" name="properties_form" id="properties_form" action="" enctype="multipart/form-data">

            <fieldset>
                <legend><?php echo _('Add New Property')?></legend>

                <input type="hidden" name="action" id="tp_current_action"/>
                <input type="hidden" name="item_id" id="tp_item_id"/>

                <div id='tp_fe_container'>

                    <div class='tp_e_container'>
                        <select name="property_id" id="tp_property_id">
                            <option value=""><?php echo _(' Choose Type')?></option>
                            <?php
                            foreach ($properties as $p_id => $p_data)
                            {
                                ?>
                                <option value="<?php echo $p_id?>"><?php echo $p_data['description']?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>

                    <div class='tp_e_container'>
                        <div class="td_ap_form" id="td_ap_form_1">
                            <input type="text" name="p_value" id="p_value" placeholder="<?php echo _('Enter Property')?>"/>
                            <input type="checkbox" name="p_locked" id="p_locked" value='1'/>
                            <label for='p_locked'><?php echo _('Lock property')?></label>
                        </div>
                    </div>

                    <div class='tp_e_container'>
                        <input type="button" id="tp_save" name="tp_save" class="small" value="<?php echo _('Save')?>"/>
                        <input type="button" id="tp_cancel" name="tp_cancel" class="small" value="<?php echo _('Cancel')?>"/>
                    </div>
                </div>

            </fieldset>
        </form>
    </div>

    <div class='pf_container pf_container_td'>
        <div class="action_buttons">
            <img id="tp_delete_selection" class="delete_selection img_action disabled" src="/ossim/pixmaps/delete-big.png"/>
        </div>

        <div class='msg_selection' id='tp_msg_selection' data-bind='tp_msg-selection'>
            <span></span>
            <a href='javascript:;' class='av_l_main' data-bind='tp_chk-all-filter'></a>
        </div>

        <table class='table_data' id='table_data_properties'>
            <thead>
                <tr>
                    <th class="center"><input type='checkbox' data-bind='chk-all-rows'/></th>
                    <th><?php echo _('Type')?></th>
                    <th><?php echo _('Property')?></th>
                    <th><?php echo _('Source Name')?></th>
                    <th><?php echo _('Actions')?></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
