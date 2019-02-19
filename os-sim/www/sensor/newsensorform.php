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


$db   = new ossim_db();
$conn = $db->connect();

Session::logcheck('configuration-menu', 'PolicySensors');

$version = $conf->get_conf("ossim_server_version");

$disable_inputs   = FALSE;

$array_priority   = array ('1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '10'=>'10');

$id     = GET('id');
$ip     = GET('ip');
$update = intval(GET('update'));

ossim_valid($id, OSS_HEX, OSS_NULLABLE,     'illegal:' . _('Sensor ID'));
ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _('IP'));

if (ossim_error())
{
    die(ossim_error());
}

// From 'Insert' link detected by server
if ($ip != '' && $id == '')
{
    $unregistered_sensors = Av_sensor::get_unregistered($conn);

    foreach($unregistered_sensors as $s_data)
    {
        if ($s_data['ip'] == $ip)
        {
            $id = $s_data['id'];
            break;
        }
    }

    if ($id == '')
    {
        $id = Av_sensor::get_id_by_ip($conn, $ip);
    }

    if ($id != '')
    {
        $disable_inputs = TRUE;
        $sname = 'sensor-'.str_replace('.', '-', $ip);
    }
}

$can_i_modify_elem = TRUE;
$external_ctx      = '';
$is_ossim_sensor   = FALSE;

if ($id != '')
{
    $sensor = Av_sensor::get_object($conn, $id);

    if (is_object($sensor) && !empty($sensor))
    {
        $sname           = ($sname != '') ? $sname : $sensor->get_name();
        $ip              = $sensor->get_ip();
        $priority        = $sensor->get_priority();
        $descr           = $sensor->get_descr();
        $tzone           = $sensor->get_tzone();

        $sensor_entities = $sensor->get_ctx();
        $external_ctxs   = Session::get_external_ctxs($conn);

        foreach ($sensor_entities as $e_id => $e_name)
        {
            if (!empty($external_ctxs[$e_id]))
            {
                $can_i_modify_elem = FALSE;
                $external_ctx      = $external_ctxs[$e_id];
            }
        }

        $is_ossim_sensor = (preg_match("/^[2-5]/", $sensor->get_property('version'))) ? TRUE : FALSE;

        unset($_SESSION['_sensor']);
    }
}

$action   = ($id != '') ? "modifysensor.php?sensor_id=$id" : "newsensor.php";
$back_url = Menu::get_menu_url("/ossim/sensor/sensor.php", "configuration", "deployment", "components", "sensors");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
    <title> <?php echo _('OSSIM Framework');?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="../js/combos.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>

    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>

    <script type="text/javascript">

        $(document).ready(function(){

            Token.add_to_forms();

            $('textarea').elastic();

            // Entities tree
            <?php
            if (Session::show_entities())
            {
                ?>
                $("#tree").dynatree({
                    initAjax: { url: "../tree.php?key=contexts&extra_options=<?php echo ($can_i_modify_elem) ? "local" : "remote" ?>" },
                    clickFolderMode: 2,
                    onActivate: function(dtnode) {
                        var key = dtnode.data.key.replace(/e_/, "");
                        if (key != "") {
                            k = key.replace("e_","");
                            <?php
                            if ( $is_ossim_sensor)
                            {
                                ?>
                                $("#entities").val(k);
                                $('#entity_selected').html("<?php echo _('Context selected') ?>: <b>"+dtnode.data.val+"</b>");
                                <?php
                            }
                            else
                            {
                                ?>
                                addto("entities",dtnode.data.val,k);
                                <?php
                            }
                            ?>
                        }
                    },
                    onDeactivate: function(dtnode) {}
                });
                <?php 
            }

            ?>

            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: '<?php echo ( $action == 'modifysensor.php' ) ? 'field-errors' : 'all'?>', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'form_s',
                    url : '<?php echo $action?>'
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success: '<?php echo _('SAVE')?>',
                        checking: '<?php echo _('Saving')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').click(function()
            {
                <?php
                if (Session::show_entities() && !$disable_inputs && !$is_ossim_sensor)
                {
                    ?>
                    selectall('entities');

                    if ($('#entities option').length > 0)
                    {
                        $('#num_entities_check').val('1');
                    }
                    <?php
                }
                ?>

                if (ajax_validator.check_form() == true)
                {
                    ajax_validator.submit_form();
                }
                else
                {
                    if (typeof(parent.doIframe) == 'function')
                    {
                        parent.doIframe();
                    }
                }
            });

            $('#isolated').click(function()
            {
                $('#neighborsensor').attr('disabled','disabled');
                ajax_validator.reset();
            });

            $('#neighbor').click(function(event)
            {
                $('#neighborsensor').removeAttr('disabled').focus();
                ajax_validator.reset();
            });

            //Greybox options

            if (top.is_lightbox_loaded(window.name))
            {
                $('#sensor_container').css({'margin':'10px auto 20px auto', 'width':'470px'});
                $('#table_form th').css("width", "130px");
                $('#av_info').css({"width": "550px", "margin" : "10px auto"});
            }
            else
            {
                $('.c_back_button').show();
            }
        });
    </script>

    <style type='text/css'>

        input[type='text'], input[type='hidden'], select
        {
            width: 98%;
            height: 18px;
        }

        textarea
        {
            width: 97%;
            height: 45px;
        }

        .text_ip
        {
            cursor: default !important;
            font-style: italic !important;
            opacity: 0.5 !important;
        }

        #sensor_container
        {
            text-align:center;
            width: 600px;
            margin: 40px auto 20px auto;
            padding-bottom: 10px;
        }

        #sensor_container #table_form
        {
            margin: auto;
        }

        .legend
        {
            font-style: italic;
            text-align: center;
            padding: 0px 0px 5px 0px;
            margin: auto;
            width: 400px;
        }

        #tree
        {
            width: 100%;
        }

        #t_entities
        {
            width: 98%;
            text-align: left;
        }

        #td_delete
        {
            vertical-align: bottom;
            width: 50px;
            text-align: right;
        }

        #td_delete input
        {
            margin: 0px;
        }

        #entities
        {
            height: 50px;
            width: 100%;
        }

        .val_error_r
        {
            min-width: 270px !important;
        }

        .cancel_button
        {
            display: none;
        }

        #table_form
        {
            width: 100%;
        }

        #table_form th
        {
            width: 150px;
        }

        #av_info
        {
            width: 580px;
            margin: 10px auto;
        }
    </style>

</head>

<body>

    <div class="c_back_button">
        <input type='button' class="av_b_back" onclick='document.location.href="<?php echo $back_url ?>";return false;'/>
    </div>

    <div id='av_info'>
        <?php
        if ($update == 1)
        {
            $config_nt = array(
                'content' => _('Sensor successfully updated'),
                'options' => array (
                    'type'          => 'nf_success',
                    'cancel_button' => TRUE
                ),
                'style'   => 'width: 100%; margin: auto; text-align:center;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }

        if (!$can_i_modify_elem)
        {
            $config_nt = array(
                'content' => _('The properties of this asset can only be modified at the USM:')." <strong>".$external_ctx."</strong>",
                'options' => array (
                    'type'          => 'nf_warning',
                    'cancel_button' => TRUE
                ),
                'style'   => 'width: 100%; margin: auto; text-align:center;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        ?>
    </div>

    <div id='sensor_container'>

        <div class='legend'>
             <?php echo _('Values marked with (*) are mandatory');?>
        </div>

        <form method="POST" name='form_s' id='form_s' action="<?php echo $action?>">

            <input type="hidden" name="insert" value="insert"/>

            <table id='table_form'>

                <?php
                if ($id != '')
                {
                    echo '<input type="hidden" name="sensor_id" id="sensor_id" class="vfield" value="'. $id .'">';
                }

                if ($disable_inputs)
                {
                    echo '<input type="hidden" class="vfield" name="sname" id="sname" value="'. $sname .'">';
                }

                if (!$disable_inputs)
                {
                    ?>
                    <tr>
                        <th>
                            <label for='sname'><?php echo _('Name') . required();?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' name="sname" id="sname" value="<?php echo $sname?>">
                        </td>
                    </tr>
                    <?php
                }
                ?>

                <tr>
                    <th>
                        <label for='ip'><?php echo _('IP') . required();?></label>
                    </th>
                    <td class="left">
                        <?php
                        if ($disable_inputs || $is_ossim_sensor)
                        {
                            ?>
                            <input type="text" class='text_ip' name="text_ip" id="text_ip" value="<?php echo $ip?>" readonly='readonly' disabled='disabled'/>
                            <input type="hidden" class='vfield' name="ip" id="ip" value="<?php echo $ip ?>"/>
                            <?php
                        }
                        else
                        {
                            ?>
                            <input type="text" class='vfield' name="ip" id="ip" value="<?php echo $ip ?>"/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

                <?php
                if (Session::is_pro() && $disable_inputs)
                {
                    ?>
                    <tr>
                        <td class="nobborder" colspan="2" style="padding:20px 10px;background:#eefad2">

                            <table class="transparent" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="center noborder" valign="top" colspan="2" style="padding-bottom:10px;font-size:13px">
                                        <label for="neighborsensor"><?php echo _('Does this sensor monitor a network already monitored by another sensor?')?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="center noborder" valign="top">
                                        <input type="radio" name="isolated" value="0" id="neighbor" checked/>
                                        <label for="neighbor"><?php echo _('Yes') ?></label>
                                    </td>
                                    <td class="center noborder" valign="top" width="45%">
                                        <input type="radio" name="isolated" value="1" id="isolated"/>
                                        <label for="isolated"><?php echo _('No, this is an isolated sensor') ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="center noborder">
                                        <input type="hidden" name="entities[]" id="entities" class='vfield' value="<?php echo Session::get_default_ctx() ?>"/>
                                        <input type="hidden" name="num_entities_check" id="num_entities_check" value=""/>
                                        <br>

                                        <select name="neighborsensor" class='vfield' id="neighborsensor" style="width:150px">
                                            <?php
                                            list($s_list, $s_total) = Av_sensor::get_list($conn, array(), FALSE, TRUE);
                                            $empty  = 1;
                                            foreach ($s_list as $s_id => $s_data)
                                            {
                                                if ($s_data['properties']['version'] != 'unknown')
                                                {
                                                    echo "<option value='".$s_id."'>".$s_data['name']."\n";

                                                    $empty = 0;
                                                }
                                            }
                                            if ($empty)
                                            {
                                                echo "<option value='00000000000000000000000000000000'>"._('Local sensor')."\n";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td class="center noborder" style="padding-left:10px">
                                        <input type="hidden" class='vfield' style="width:120px" name="newcontext" value="<?php echo $sname?>">
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for='location'><?php echo _('Location');?></label>
                        </th>
                        <td class="left">
                            <select name="location" id="location" class='vfield'>
                                <?php
                                $locations = Locations::get_list($conn);
                                foreach ($locations as $lc)
                                {
                                    echo "<option value='".$lc->get_id()."'>".$lc->get_name()."</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <?php
                }
                else
                {
                    if (Session::show_entities())
                    {
                        $e_url = Menu::get_menu_url('../acl/entities.php', 'configuration', 'administration', 'users', 'structure');
                        ?>
                        <tr>
                            <th>
                                <label for='entities'><?php echo _('Context') . required();?></label><br/>
                            </th>

                            <td class="nobborder">
                                <table id='t_entities' class="transparent">
                                    <tr>
                                        <td class="noborder left">
                                            <div id="tree"></div>
                                        </td>
                                    </tr>
                                    <?php
                                    if ($is_ossim_sensor) // we must choose only one related context
                                    {
                                        $entity_id = $entity_name = "";
                                        if (is_array($sensor_entities))
                                        {
                                            foreach ($sensor_entities as $entity_id => $entity_name)
                                            {
                                                if(Acl::is_logical_entity($conn, $entity_id)) continue;
                                                break;
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="nobborder">
                                            <input type="hidden" name="entities[]" class='vfield' id="entities" value="<?php echo $entity_id ?>"/>
                                            <input type="hidden" name="num_entities_check" id="num_entities_check" value="1"/>
                                            <div id="entity_selected">
                                                <?php echo _('Context selected').": <b>". ($entity_name == '' ? _('None') : $entity_name) ."</b>"; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    }
                                    else
                                    {
                                        ?>
                                        <tr>
                                            <td class="left noborder" id='td_entities'>
                                                <input type="hidden" name="num_entities_check" id="num_entities_check" value="<?php if (!Session::is_pro()) echo "1" ?>"/>
                                                <select multiple="multiple" size="11" name="entities[]" class='vfield' id="entities">
                                                <?php
                                                if (is_array($sensor_entities))
                                                {
                                                    foreach ($sensor_entities as $entity_id => $entity_name)
                                                    {
                                                        if(Acl::is_logical_entity($conn, $entity_id))
                                                        {
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<?php echo $entity_id ?>"><?php echo $entity_name ?></option>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="noborder" id='td_delete'>
                                                <input type="button" value=" [X] " onclick="deletefrom('entities')" class="small"/>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </table>
                            </td>
                        </tr>
                        <?php
                    }
                    else
                    {
                        ?>
                        <input type="hidden" name="entities[]" id="entities" class='vfield' value="<?php echo Session::get_default_ctx() ?>"/>
                        <input type="hidden" name="num_entities_check" id="num_entities_check" value="<?php if (!Session::is_pro()) echo "1" ?>"/>
                        <?php
                    }
                }
                ?>
                <tr>
                    <th>
                        <label for='priority'><?php echo _('Priority');?></label>
                    </th>
                    <td class="left">
                        <select name="priority" id="priority" class='vfield'>
                        <?php
                            if (!in_array($priority, $array_priority))
                            {
                                $priority = "5";
                            }

                            foreach ($array_priority as $v)
                            {
                                $selected = ($priority == $v) ? "selected='selected'" : '';
                                echo "<option value='$v' $selected>$v</option>";
                            }
                        ?>
                        </select>
                    </td>
                </tr>

                <?php $tz = (isset($tzone)) ? $tzone : intval(date("O"))/100;?>
                <tr>
                    <th>
                        <label for='tzone'><?php echo _('Timezone');?></label>
                    </th>

                    <td class="left">
                        <select name="tzone" id="tzone" class='vfield'>
                            <option value="-12" <?php if ($tz == "-12")   echo "selected='selected'" ?>>GMT-12:00</option>
                            <option value="-11" <?php if ($tz == "-11")   echo "selected='selected'" ?>>GMT-11:00</option>
                            <option value="-10" <?php if ($tz == "-10")   echo "selected='selected'" ?>>GMT-10:00</option>
                            <option value="-9.5" <?php if ($tz == "-9.5") echo "selected='selected'" ?>>GMT-9:30</option>
                            <option value="-9" <?php if ($tz == "-9")     echo "selected='selected'" ?>>GMT-9:00</option>
                            <option value="-8" <?php if ($tz == "-8")     echo "selected='selected'" ?>>GMT-8:00</option>
                            <option value="-7" <?php if ($tz == "-7")     echo "selected='selected'" ?>>GMT-7:00</option>
                            <option value="-6" <?php if ($tz == "-6")     echo "selected='selected'" ?>>GMT-6:00</option>
                            <option value="-5" <?php if ($tz == "-5")     echo "selected='selected'" ?>>GMT-5:00</option>
                            <option value="-4.5" <?php if ($tz == "-4.5") echo "selected='selected'" ?>>GMT-4:30</option>
                            <option value="-4" <?php if ($tz == "-4")     echo "selected='selected'" ?>>GMT-4:00</option>
                            <option value="-3.5" <?php if ($tz == "-3.5") echo "selected='selected'" ?>>GMT-3:30</option>
                            <option value="-3" <?php if ($tz == "-3")     echo "selected='selected'" ?>>GMT-3:00</option>
                            <option value="-2" <?php if ($tz == "-2")     echo "selected='selected'" ?>>GMT-2:00</option>
                            <option value="-1" <?php if ($tz == "-1")     echo "selected='selected'" ?>>GMT-1:00</option>
                            <option value="0" <?php if ($tz == "0")       echo "selected='selected'" ?>>UTC</option>
                            <option value="1" <?php if ($tz == "1")       echo "selected='selected'" ?>>GMT+1:00</option>
                            <option value="2" <?php if ($tz == "2")       echo "selected='selected'" ?>>GMT+2:00</option>
                            <option value="3" <?php if ($tz == "3")       echo "selected='selected'" ?>>GMT+3:00</option>
                            <option value="3.5" <?php if ($tz == "3.5")   echo "selected='selected'" ?>>GMT+3:30</option>
                            <option value="4" <?php if ($tz == "4")       echo "selected='selected'" ?>>GMT+4:00</option>
                            <option value="4.5" <?php if ($tz == "4.5")   echo "selected='selected'" ?>>GMT+4:30</option>
                            <option value="5" <?php if ($tz == "5")       echo "selected='selected'" ?>>GMT+5:00</option>
                            <option value="5.5" <?php if ($tz == "5.5")   echo "selected='selected'" ?>>GMT+5:30</option>
                            <option value="5.75" <?php if ($tz == "5.75") echo "selected='selected'" ?>>GMT+5:45</option>
                            <option value="6" <?php if ($tz == "6")       echo "selected='selected'" ?>>GMT+6:00</option>
                            <option value="6.5" <?php if ($tz == "6.5")   echo "selected='selected'" ?>>GMT+6:30</option>
                            <option value="7" <?php if ($tz == "7")       echo "selected='selected'" ?>>GMT+7:00</option>
                            <option value="8" <?php if ($tz == "8")       echo "selected='selected'" ?>>GMT+8:00</option>
                            <option value="8.75" <?php if ($tz == "8.75") echo "selected='selected'" ?>>GMT+8:45</option>
                            <option value="9" <?php if ($tz == "9")       echo "selected='selected'" ?>>GMT+9:00</option>
                            <option value="9.5" <?php if ($tz == "9.5")   echo "selected='selected'" ?>>GMT+9:30</option>
                            <option value="10" <?php if ($tz == "10")     echo "selected='selected'" ?>>GMT+10:00</option>
                            <option value="10.5" <?php if ($tz == "10.5") echo "selected='selected'" ?>>GMT+10:30</option>
                            <option value="11" <?php if ($tz == "11")     echo "selected='selected'" ?>>GMT+11:00</option>
                            <option value="11.5" <?php if ($tz == "11.5") echo "selected='selected'" ?>>GMT+11:30</option>
                            <option value="12" <?php if ($tz == "12")     echo "selected='selected'" ?>>GMT+12:00</option>
                            <option value="12.75" <?php if ($tz == "12.75") echo "selected='selected'" ?>>GMT+12:45</option>
                            <option value="13" <?php if ($tz == "13")     echo "selected='selected'" ?>>GMT+13:00</option>
                            <option value="14" <?php if ($tz == "14")     echo "selected='selected'" ?>>GMT+14:00</option>
                        </select>
                    </td>
                </tr>

                <input type="hidden" class='vfield' name="port" id="port" value="40001"/>

                <tr>
                    <th><label for='descr'><?php echo _('Description');?></label></th>
                    <td class="left noborder">
                        <textarea name="descr" class='vfield' id="descr"><?php echo $descr;?></textarea>
                    </td>
                </tr>
                <?php
                if ($disable_inputs)
                {
                    ?>
                    <tr>
                        <td class="center noborder" valign="top" colspan="2" style="padding-top:10px;font-size:13px">
                            <label for="rpass"><?php echo _('Please enter the root password of the remote system in order to configure it.')?></label>
                            <br>
                            <input type="password" class='vfield' style="margin-top:10px;width:180px" name="rpass" id="rpass" autocomplete="off">
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <td colspan="2" align="center" style="padding: 10px;">
                        <?php
                        if ($can_i_modify_elem)
                        {
                            ?>
                            <input type="button" id='send' name='send' value="<?php echo _('SAVE');?>"/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

            </table>

        </form>
    </div>

</body>
</html>
<?php
$db->close();
