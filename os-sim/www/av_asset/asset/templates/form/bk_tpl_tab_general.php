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

$t_location   = '<div>'._("You can type any location (address, country, city, ...)").'</div>';


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


//Getting all sensors
$filters = array(
    'order_by' => "priority DESC"
);

list($all_sensors, $s_total) = Av_sensor::get_list($conn, $filters, FALSE, TRUE);


//Common Context
$ctx = Asset_host::get_common_ctx($conn);


//Closing database connection
$db->close();

?>

<div id="bk_tg_container">

    <div id='tg_av_info'></div>

    <div class="legend">
        <?php echo _('Only filled values will be updated');?>
    </div>


    <form method="POST" name="asset_form" id="asset_form" action="" enctype="multipart/form-data">

        <input type="hidden" name="asset_type" id="tg_asset_type" class="vfield"/>

        <table id='tg_t_container'>

            <!-- Asset value/External Asset and Icon labels-->
            <tr>
                <td class="td_left">
                    <table>
                        <tr>
                            <td class="w50">
                                <label for="asset_value"><?php echo _('Asset Value')?></label>
                            </td>
                            <td class="w50">
                                <span class="s_label" id="sl_external"><?php echo _('External Asset')?></span>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="td_right pad_t_15">
                    <label for="icon"><?php echo _('Icon')?></label>
                    <span class="img_format"><?php echo _('Allowed format: Up to 400x400 PNG, JPG or GIF image')?></span>
                </td>
            </tr>


            <!-- Asset value/External Asset and Icon inputs -->
            <tr>
                <td class="td_left">
                    <table>
                        <tr>
                            <td class="w50">
                                <select name="asset_value" id="asset_value" class="vfield">
                                    <option value=''></option>
                                    <?php
                                    for ($i = 0; $i <= 5; $i++)
                                    {
                                        echo "<option value='$i'>$i</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td class="w50">
                                <input type="radio" id="external_yes" name="external" class="vfield" value="1"/>
                                <label for="external_yes"><?php echo _('Yes')?></label>

                                <input type="radio" id="external_no" name="external" class="vfield" value="0"/>
                                <label for="external_no"><?php echo _('No')?></label>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="td_right">
                    <input type="file" class="vfield" name="icon" id="icon"/>
                </td>
            </tr>


            <!-- Description and location labels -->
            <tr>
                <td id='l_descr' class="td_left">
                    <label for="descr"><?php echo _('Description')?></label>
                </td>

                <td class="td_right">
                    <span class="s_label" id="sl_location"><?php echo _('Location')?></span>
                </td>
            </tr>


            <!-- Description and location inputs -->
            <tr>
                <td class="td_left">
                    <table>
                        <tr>
                            <td>
                                <textarea name="descr" id="descr" class="vfield"></textarea>
                            </td>
                        </tr>

                        <!-- SO label -->
                        <tr>
                            <td>
                                <label for="os"><?php echo _('Operating System')?></label>
                            </td>
                        </tr>

                        <!-- SO input -->
                        <tr>
                            <td>
                                <input type="text" name="os" id="os" class="vfield"/>
                                <input type="hidden" name="os_cpe" id="os_cpe"/>
                                <div class='cpe_results' id='os_sr'><div></div></div>
                            </td>
                        </tr>

                        <!-- Model label -->
                        <tr>
                            <td>
                                <label for="model"><?php echo _('Model')?></label>
                            </td>
                        </tr>

                        <!-- Model input -->
                        <tr>
                            <td>
                                <input type="text" name="model" id="model" class="vfield"/>
                                <input type="hidden" name="model_cpe" id="model_cpe"/>
                                <div class='cpe_results' id='model_sr'><div></div></div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="td_right">
                    <table>
                        <tr>
                            <input type="text" class="info" id="search_location" name="search_location" data-title="<?php echo $t_location?>"/>
                        </tr>

                        <tr>
                            <td>
                                <div id='c_map'></div>
                            </td>
                        </tr>

                        <tr>
                            <td class="td_right">
                                <span class="s_label" id="sl_longitude"><?php echo _('Latitude/Longitude')?></span>

                                <input type="text" id="latitude" name="latitude"/>
                                <input type="text" id="longitude" name="longitude"/>
                                <input type="hidden" id="zoom" name="zoom"/>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>


            <!-- Sensor label -->
            <tr class='tr_sensors'>
                <td class="td_left pad_t_15" colspan="2">
                    <span class="s_label" id="sl_sboxs[]"><?php echo _('Sensors')?></span>
                </td>
            </tr>


            <!-- Sensor inputs -->
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


                    if ($s_status == 'enabled')
                    {
                        $s_chk_id     = ' id = "sboxs'.$i.'"';
                        $s_chk_class  = ' class="vfield sensor_check'.$s_ctxs.'"';

                        $s_chk_opt    = $s_chk_id.$s_chk_class;

                        $s_chk_label  = '<label class="l_sbox" for="sboxs'.$i.'">'.$s_ip." (".$s_name.")".'</label>';

                        $s_chks[] = '<input type="checkbox" name="sboxs[]" '.$s_chk_opt.' value="'.$s_id.'"/>'.$s_chk_label;

                        $i++;
                    }
                }
            }
            ?>

            <tr class='tr_sensors'>
                <td class="td_left" colspan="2">
                    <?php
                    if (is_array($s_chks) && !empty($s_chks))
                    {
                        ?>
                        <table>
                            <?php
                            $size = count($s_chks);
                            for ($i = 0; $i <= $size; $i=$i+2)
                            {
                                ?>
                                <tr>
                                    <td class='td_left'><?php echo $s_chks[$i]?></td>
                                    <td class='td_left'><?php echo $s_chks[($i+1)]?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
                        <?php
                    }
                    ?>
                </td>
            </tr>

            <!-- Device labels -->
            <tr>
                <td class="td_left pad_t_15">
                    <label for="devices"><?php echo _('Devices Types')?></label>
                </td>
                <td class="td_right pad_t_15">
                    &nbsp;
                </td>
            </tr>

            <tr>
                <td class="td_left">
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
                <td class="td_left">
                    &nbsp;
                </td>
            </tr>
        </table>

        <div id='tg_actions'>
            <input type="button" name="tg_send" id="tg_send" value="<?php echo _('Save')?>"/>
        </div>

    </form>
</div>
