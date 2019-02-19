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
    'is_editable' =>  array('validation' => 'OSS_LETTER, OSS_SCORE',                     'e_message'  =>  'illegal:' . _('Edit permission')),
    'ips[]'       =>  array('validation' => 'OSS_NULLABLE, OSS_SEVERAL_IP_ADDRCIDR_0',   'e_message'  =>  'illegal:' . _('IP Addresses'))
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


//Closing database connection
$db->close();

?>

<div id="ts_container">
    <?php
    if ($is_editable != 'no')
    {
        ?>
        <div id='ts_av_info'></div>

        <div class='sf_container'>
            <form method="POST" name="services_form" id="services_form" action="" enctype="multipart/form-data">

                <fieldset>
                    <legend><?php echo _('Add New Service')?></legend>

                    <input type="hidden" name="action" id="ts_current_action"/>
                    <input type="hidden" name="asset_id" id="ts_asset_id" class="vfield"/>
                    <input type="hidden" name="property_id" id="ts_property_id" class="vfield"/>
                    <input type="hidden" name="item_id" id="ts_item_id"/>

                    <div id='ts_fe_container'>
                        <div class='ts_e_container'>
                            <?php
                            if (count($ips) > 1)
                            {
                                ?>
                                <select name="s_ip" id="s_ip">
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
                                <div id="s_ip_text"><?php echo $ips[0]?></div>
                                <input type="hidden" name="s_ip" id="s_ip" value="<?php echo $ips[0]?>"/>
                                <?php
                            }
                            ?>
                        </div>

                        <div class='ts_e_container'>
                            <input type="text" name="s_port" id="s_port" placeholder="<?php echo _('Enter Port')?>"/>
                        </div>

                        <div class='ts_e_container'>
                            <select id="s_protocol" name="s_protocol">
                                <option value=""><?php echo _('Protocol')?></option>
                                <option value="6">TCP</option>
                                <option value="17">UDP</option>
                            </select>
                        </div>

                        <div class='ts_e_container'>
                            <input type="text" name="s_name" id="s_name" placeholder="<?php echo _('Enter Service')?>"/>
                        </div>

                        <div class='ts_e_container'>
                            <input type="button" id="ts_save" name="ts_save" class="small" value="<?php echo _('Save')?>"/>
                            <input type="button" id="ts_cancel" name="ts_cancel" class="small" value="<?php echo _('Cancel')?>"/>
                        </div>
                    </div>

                </fieldset>
            </form>
        </div>
        <?php
    }
    ?>


    <div class='sf_container sf_container_td'>
        <?php
        if ($is_editable != 'no')
        {
            ?>
            <div class="action_buttons">

                <a href='javascript:;' id='monitoring_actions' class='button av_b_secondary small' data-bind='ts_m-actions' data-dropdown="#dropdown-services">
                    <?php echo _('Availability Monitoring')?> &nbsp;&#x25be;
                </a>

                <div id="dropdown-services" data-bind="dropdown-service" class="dropdown dropdown-secondary dropdown-close dropdown-tip dropdown-anchor-right dropdown-relative">
                    <ul class="dropdown-menu">
                        <li><a href="#1" data-bind="enable_monitoring"><?php echo _('Enable')?></a></li>
                        <li><a href="#2" data-bind="disable_monitoring"><?php echo _('Disable')?></a></li>
                    </ul>
                </div>

                <img id="ts_delete_selection" class="delete_selection img_action disabled" src="/ossim/pixmaps/delete-big.png"/>

            </div>

            <div class='msg_selection' id='ts_msg_selection' data-bind='ts_msg-selection'>
                <span></span>
                <a href='javascript:;' class='av_l_main' data-bind='ts_chk-all-filter'></a>
            </div>
            <?php
        }

        // Service list
        include AV_MAIN_ROOT_PATH.'/av_asset/common/templates/tpl_dt_services.php';
        ?>
    </div>
</div>
