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
 ********************* Tooltips *********************
 ****************************************************/

$t_name  = "<div>"._('A valid hostname must satisfy the following rules (according RFC 1123)').":</div>
            <div>
                <ul class='ul_tiptip'>
                    <li>"._("Hostname may contain ASCII letters a-z (not case sensitive), digits, and/or hyphens ('-')")."</li>
                    <li>"._("Hostname <strong>MUST NOT</strong> contain a '.' (period) or '_' (underscore)")."</li>
                    <li>"._("Hostname <strong>MUST NOT</strong> contain a space")."</li>
                    <li>"._("Hostname can be up to 63 characters")."</li>
                </ul>
            </div>";

$t_ips   = '<div>'._("You can type one unique IP Address or an IP list separated by commas: IP1, IP2, IP3...").'</div>';

$t_fqdns = "<div>"._('A valid FQDN must satisfy the following rules (according RFC 952, 1035, 1123 and 2181)').":</div>
            <div>
                <ul class='ul_tiptip'>
                    <li>"._("Hostnames are composed of a series of labels concatenated with dots. Each label is 1 to 63 characters long.")."</li>
                    <li>"._("It may contain the ASCII letters a-z (in a case insensitive manner), the digits 0-9, and the hyphen ('-').")."</li>
                    <li>"._("Labels cannot start or end with hyphens (RFC 952).")."</li>
                    <li>"._("Labels can start with numbers (RFC 1123).")."</li>
                    <li>"._("Max length of ascii hostname including dots is 253 characters (not counting trailing dot).")."</li>
                    <li>"._("Underscores ('_') are not allowed in hostnames")."</li>
                    <li>"._("Use comma to separate multiple FQDNs")."</li>
                </ul>
            </div>";

$t_location   = '<div>'._("You can type any location (address, country, city, ...)").'</div>';


/****************************************************
 ****************** POST validation *****************
 ****************************************************/

$validate = array(
    'is_editable' =>  array('validation' => 'OSS_LETTER, OSS_SCORE',  'e_message'  =>  'illegal:' . _('Edit permission'))
);


$is_editable = POST('is_editable');

$validation_errors = validate_form_fields('POST', $validate);

if (!empty($validation_errors))
{
    Util::response_bad_request(_('Tab could not be loaded'));
}


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


//Default asset values
$ctx         = Session::get_default_ctx();
$ctx_name    = Session::get_entity_name($conn, $ctx);


//Getting all sensors
$filters = array(
    'order_by' => "priority DESC"
);

list($all_sensors, $s_total) = Av_sensor::get_list($conn, $filters, FALSE, TRUE);


//Closing database connection
$db->close();

?>

<div id="tg_container">

    <div id='tg_av_info'></div>

    <div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>


    <form method="POST" name="asset_form" id="asset_form" action="" enctype="multipart/form-data">
        
        <input type="hidden" name="asset_id" id="tg_asset_id" class="vfield"/>
        <input type="hidden" name="ctx" id="ctx" class="vfield"/>

        <table id='tg_t_container'>

            <!-- Hostname and Icon labels -->
            <tr>
                <td class="td_left">
                    <label for="asset_name"><?php echo _('Name') . required();?></label>
                </td>

                <td class="td_right">
                    <label for="icon"><?php echo _('Icon')?></label>
                    <span class="img_format"><?php echo _('Allowed format: Up to 400x400 PNG, JPG or GIF image')?></span>
                </td>
            </tr>

            <!-- Hostname and Icon inputs -->
            <tr>
                <td class="td_left">
                    <input data-title="<?php echo $t_name?>" type="text" class="info vfield" name="asset_name" id="asset_name"/>
                </td>

                <td class="td_right">
                    <input type="file" class="vfield" name="icon" id="icon"/>
                </td>
            </tr>


            <!-- IPs and location -->
            <tr>
                <td class="td_left">
                    <label for="asset_ip"><?php echo _('IP Address') . required();?></label>
                </td>

                <td class="td_right">
                    <span class="s_label" id="sl_location"><?php echo _('Location')?></span>
                </td>
            </tr>


            <!-- IPs and location inputs -->
            <tr>
                <td class="td_left">
                    <?php
                    if ($is_editable == 'no_ip')
                    {
                        ?>
                        <input type="text" class="asset_text_ip" name="asset_text_ip" id="asset_text_ip" readonly="readonly" disabled="disabled"/>

                        <input type="hidden" class="vfield" name="asset_ip" id="asset_ip"/>
                        <?php
                    }
                    else
                    {
                        ?>
                        <input type="text" class="info vfield" name="asset_ip" id="asset_ip" data-title="<?php echo $t_ips?>"/>
                        <?php
                    }
                    ?>
                </td>

                <td class="td_right">
                    <input type="text" class="info" id="search_location" name="search_location" data-title="<?php echo $t_location?>"/>
                </td>
            </tr>


            <!-- FQDN label and Google Maps -->
            <tr>
                <td id="l_fqdns" class="td_left">
                    <label for="fqdns"><?php echo _('FQDN/Aliases')?></label>
                </td>

                <td class="td_right" rowspan="2">
                    <div id='c_map'></div>
                </td>
            </tr>


            <!-- FQDN textarea -->
            <tr>
                <td class="td_left">
                    <textarea name="fqdns" id="fqdns" class="info vfield" data-title="<?php echo $t_fqdns?>"></textarea>
                </td>
            </tr>


            <!-- Asset value/External Asset and Latitude/Longitude (Labels and inputs) -->
            <tr>
                <td class="td_left">
                    <table>
                        <tr>
                            <td>
                                <label for="asset_value"><?php echo _('Asset Value') . required();?></label>
                            </td>
                            <td>
                                <span class="s_label" id="sl_external"><?php echo _('External Asset') . required();?></span>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <select name="asset_value" id="asset_value" class="vfield">
                                    <?php
                                    $default_av = 2;
                                    for ($i = 0; $i <= 5; $i++)
                                    {
                                        $selected = ($default_av == $i) ? "selected='selected'" : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="radio" id="external_yes" name="external" class="vfield" checked="checked" value="1"/>
                                <label for="external_yes"><?php echo _('Yes')?></label>

                                <input type="radio" id="external_no" name="external" class="vfield" value="0"/>
                                <label for="external_no"><?php echo _('No')?></label>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="td_right">
                    <span class="s_label" id="sl_longitude"><?php echo _('Latitude/Longitude')?></span>

                    <input type="text" id="latitude" name="latitude"/>
                    <input type="text" id="longitude" name="longitude"/>
                    <input type="hidden" id="zoom" name="zoom"/>
                </td>
            </tr>


            <!-- Context and Sensors labels -->
            <tr>
                <?php
                if (Session::show_entities() && Session::is_pro())
                {
                    ?>
                    <td id="l_context" class="td_left">
                        <label for="ctx"><?php echo _('Context') . required();?></label>
                    </td>

                    <td id="l_sensors" class="td_right">
                        <span class="s_label" id="sl_sboxs[]"><?php echo _('Sensors') . required();?></span>
                    </td>
                    <?php
                }
                else
                {
                    ?>
                    <td class="td_left" colspan="2">
                        <span class="s_label" id="sl_sboxs[]"><?php echo _('Sensors') . required();?></span>
                    </td>
                    <?php
                }
                ?>
            </tr>



            <!-- Context Tree and Sensors -->
            <?php
            $s_chks     = array();
            $no_sensors = '';

            if ($s_total <= 0)
            {
                $config_nt = array(
                    'content' => _('Warning! No sensors found'),
                    'options' => array (
                        'type'          => 'nf_warning',
                        'cancel_button' => FALSE
                    ),
                    'style'   => 'width: 80%; margin: 25px auto; text-align: left; font-size: 11px;'
                );

                $nt         = new Notification('nt_1', $config_nt);
                $no_sensors = $nt->show(FALSE);
            }
            else
            {
                $i = 1;
                $checked_by_default = FALSE;

                foreach($all_sensors as $s_id => $s_data)
                {
                    $s_name   = $s_data['name'];
                    $s_ip     = $s_data['ip'];
                    $all_ctxs = $s_data['ctx'];

                    $s_ctxs   = '';
                    $s_status = 'disabled';

                    //Search enabled sensors by CTXs
                    foreach ($all_ctxs as $e_id => $e_name)
                    {
                        if ($e_id == $ctx && !empty($ctx))
                        {
                            $s_status = 'enabled';
                        }

                        $s_ctxs .= ' '.$e_id;
                    }

                    $s_chk_checked = '';

                    //Sensors of selected CTX are checked by default
                    if ($s_status == 'enabled' && $checked_by_default == FALSE)
                    {
                        $s_chk_checked      = " checked='checked'";
                        $checked_by_default = TRUE;
                    }

                    $s_chk_id     = ' id = "sboxs'.$i.'"';
                    $s_chk_class  = ' class="vfield sensor_check'.$s_ctxs.'"';

                    $s_chk_status = ($s_status == 'disabled' && Session::show_entities()) ? ' disabled="disabled"' : "";

                    $s_chk_opt    = $s_chk_id.$s_chk_class.$s_chk_status.$s_chk_checked;

                    $s_chk_label  = '<label class="l_sbox" for="sboxs'.$i.'">'.$s_ip." (".$s_name.")".'</label>';

                    $s_chks[] = '<input type="checkbox" name="sboxs[]" '.$s_chk_opt.' value="'.$s_id.'"/>'.$s_chk_label;

                    $i++;
                }
            }
            ?>

            <tr>
                <?php
                if (Session::show_entities() && Session::is_pro())
                {
                    ?>
                    <td class="td_left">
                        <div id="tree"></div>
                        <br/>
                        <div id="entity_selected"><?php echo _('Context selected').': <strong>'.Util::htmlentities($ctx_name)."</strong>";?></div>
                    </td>

                    <td class="td_right">
                        <?php
                        if (is_array($s_chks) && !empty($s_chks))
                        {
                            echo implode('<br/>', $s_chks);
                        }
                        else
                        {
                            echo $no_sensors;
                        }
                        ?>
                    </td>
                    <?php
                }
                else
                {
                    ?>
                    <td class="td_left" colspan="2">
                        <?php
                        if (is_array($s_chks) && !empty($s_chks))
                        {
                            ?>
                            <table>
                                <?php
                                $size = count($s_chks);
                                for ($i = 0; $i <= $size; $i++)
                                {
                                    ?>
                                    <tr>
                                        <td><?php echo $s_chks[$i]?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>
                            <?php
                        }
                        else
                        {
                            echo $no_sensors;
                        }
                        ?>
                    </td>
                    <?php
                }
                ?>
            </tr>


            <!-- SO and Model labels -->
            <tr>
                <td id='l_so' class="td_left">
                    <label for="os"><?php echo _('Operating System')?></label>
                </td>

                <td id='l_model' class="td_right">
                    <label for="model"><?php echo _('Model')?></label>
                </td>
            </tr>


            <!-- SO and Model inputs -->
            <tr>
                <td class="td_left">
                    <input type="text" name="os" id="os" class="vfield"/>
                    <input type="hidden" name="os_cpe" id="os_cpe"/>
                    <div class='cpe_results' id='os_sr'><div></div></div>
                </td>

                <td class="td_right">
                    <input type="text" name="model" id="model" class="vfield"/>
                    <input type="hidden" name="model_cpe" id="model_cpe"/>
                    <div class='cpe_results' id='model_sr'><div></div></div>
                </td>
            </tr>


            <!-- Description and Device labels -->
            <tr>
                <td id='l_descr' class="td_left">
                    <label for="descr"><?php echo _('Description')?></label>
                </td>

                <td id='l_devices' class="td_right">
                    <label for="devices"><?php echo _('Devices Types')?></label>
                </td>
            </tr>


            <!-- Description and Device inputs -->
            <tr>
                <td class="td_left">
                    <table>
                        <tr>
                            <td colspan="2">
                                <textarea name="descr" id="descr" class="vfield"></textarea>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="td_right">
                    <table>
                        <tr>
                            <td>
                                <select name="device_type" id="device_type">
                                    <option selected='selected' value="0"><?php echo _('Devices')?></option>
                                </select>
                            </td>

                            <td>
                                <select name="device_subtype" id="device_subtype">
                                    <option selected='selected' value="0"><?php echo _('Types')?></option>
                                </select>
                            </td>

                            <td>
                                <input type="button" class="av_b_secondary small" id="add_device" name="add_device" value="<?php echo _('Add')?>"/>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="3">
                                <select id="devices" name="devices[]" multiple="multiple">
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="3">
                                <div id='del_selected'>
                                     <input type="button" class="av_b_secondary small" id='delete_device' value=" [X] "/>
                                </div>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>

        <?php
        if ($is_editable != 'no')
        {
            ?>
            <div id='tg_actions'>
                <input type="button" name="tg_send" id="tg_send" value="<?php echo _('Save')?>"/>
            </div>
            <?php
        }
        ?>
    </form>
</div>       
