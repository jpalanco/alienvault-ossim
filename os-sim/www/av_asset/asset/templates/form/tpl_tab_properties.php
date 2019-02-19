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


/****************************************************
 ****************** POST validation *****************
 ****************************************************/

$validate = array(
    'is_editable' => array('validation' => 'OSS_LETTER, OSS_SCORE',                     'e_message'  =>  'illegal:' . _('Edit permission')),
    'ips[]'       => array('validation' => 'OSS_NULLABLE, OSS_SEVERAL_IP_ADDRCIDR_0',   'e_message'  =>  'illegal:' . _('IP Addresses'))
);


$is_editable = POST('is_editable');
$ips         = POST('ips');

$validation_errors = validate_form_fields('POST', $validate);

if (!empty($validation_errors))
{
    Util::response_bad_request(_('Tab could not be loaded'));
}


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

    <?php
    if ($is_editable != 'no')
    {
        ?>
        <div id='tp_av_info'></div>

        <div class='pf_container'>
            <form method="POST" name="properties_form" id="properties_form" action="" enctype="multipart/form-data">

                <fieldset>
                    <legend><?php echo _('Add New Property')?></legend>

                    <input type="hidden" name="action" id="tp_current_action"/>
                    <input type="hidden" name="asset_id" id="tp_asset_id" class="vfield"/>
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
                                <option value="50"><?php echo _('MAC Address')?></option>
                            </select>
                        </div>

                        <div class='tp_e_container'>
                            <div class="td_ap_form" id="td_ap_form_1">
                                <input type="text" name="p_value" id="p_value" placeholder="<?php echo _('Enter Property')?>"/>
                                <input type="checkbox" name="p_locked" id="p_locked" value='1'/>
                                <label for='p_locked'><?php echo _('Lock property')?></label>
                            </div>

                            <div class="td_ap_form" id="td_ap_form_2">
                                <?php
                                if (count($ips) > 1)
                                {
                                    ?>
                                    <select name="mac_ip" id="mac_ip">
                                        <option value=""><?php echo _('IP Address')?></option>
                                        <?php
                                        foreach ($ips as $ip_aux)
                                        {
                                            ?>
                                            <option value="<?php echo $ip_aux?>"><?php echo $ip_aux?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <input type="text" name="mac_ip_text" id="mac_ip_text" disabled="disabled" readonly="readonly" value="<?php echo $ips[0]?>"/>
                                    <input type="hidden" name="mac_ip" id="mac_ip" value="<?php echo $ips[0]?>"/>
                                    <?php
                                }
                                ?>
                                <input type="text" name="mac" id="mac" placeholder="<?php echo _('MAC Address')?>"/>
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
        <?php
    }
    ?>

    <div class='pf_container pf_container_td'>
        <?php
        if ($is_editable != 'no')
        {
            ?>
            <div class="action_buttons">
                <img id="tp_delete_selection" class="delete_selection img_action disabled" src="/ossim/pixmaps/delete-big.png"/>
            </div>

            <div class='msg_selection' id='tp_msg_selection' data-bind='tp_msg-selection'>
                <span></span>
                <a href='javascript:;' class='av_l_main' data-bind='tp_chk-all-filter'></a>
            </div>
            <?php
        }

        // Property list
        include AV_MAIN_ROOT_PATH.'/av_asset/common/templates/tpl_dt_properties.php';
        ?>
    </div>

</div>
