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

set_time_limit(0);

/****************************************************
 ******************** Scan Data *********************
 ****************************************************/

//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


$ctx = POST('sensor_ctx');

ossim_valid($ctx, OSS_HEX,   'illegal:' . _('CTX'));

if (ossim_error())
{
    echo ossim_error(_('Error! Scan results not found'));
    exit();
}

//All sensors

$filters = array(
    'where' => "sensor.id = acl_sensors.sensor_id AND acl_sensors.entity_id = UNHEX('$ctx')",
);


$all_sensors = Av_sensor::get_basic_list($conn, $filters);

//Closing database connection
$db->close();

$asset_value  = 2;

$num_ips = REQUEST('ips');
$msg     = GET('msg');

ossim_valid($num_ips, OSS_DIGIT,   'illegal:' . _('Scanned IPs'));

if (ossim_error())
{
    echo ossim_error();
    exit();
}

$ips = array();

for ($i = 0; $i < $num_ips; $i++)
{
    if (ossim_valid(POST("ip_$i"), OSS_IP_ADDR, 'illegal:' . _('IP address')))
    {
        $ips[] = POST("ip_$i");
        ossim_clean_error();
    }
    else if(POST("ip_$i") == "")
    {
        $ips[] = '';
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _('OSSIM Framework');?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
        <meta http-equiv="Pragma" content="no-cache"/>

        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/notification.js"></script>
        <script type="text/javascript" src="../js/ajax_validator.js"></script>
        <script type="text/javascript" src="../js/messages.php"></script>
        <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
        <script type="text/javascript" src="../js/utils.js"></script>

        <!-- Dynatree libraries: -->
        <script type="text/javascript" src="../js/jquery.cookie.js"></script>
        <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
        <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
        <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
        <script type="text/javascript" src="../js/combos.js"></script>
        <script type="text/javascript" src="../js/token.js"></script>
        <script type="text/javascript" src="../js/jquery.base64.js"></script>
        <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
        <!-- <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script> -->
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <link rel="stylesheet" type="text/css" href="../style/environment/assets/asset_discovery.css"/>
        <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css" />
        <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
        <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>

        <style type='text/css'>

            #scan_container input[type='text'], #scan_container input[type='hidden']
            {
                width: 98%;
                height: 18px;
            }

            #scan_container select
            {
                width: 98%;
                height: 18px;
            }

            #scan_container textarea
            {
                width: 98%;
                height: 40px;
            }

            #scan_container table
            {
                border-collapse: collapse;
                border-spacing: 0px;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            }

            #scan_container
            {
                width: 680px;
                margin: auto;
                padding: 20px 0px;
            }

            #scan_container #t_container
            {
                border-collapse: collapse;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            }

            #t_container td
            {
                text-align: left;
                vertical-align: top;
            }

            #t_container .td_left
            {
                padding: 6px 15px 0px 10px;
                width: 45%;
                border: none;
            }

            #t_container .td_right
            {
                padding: 6px 10px 0px 15px;
                width: 55%;
                border: none;
            }

            #t_container label, #t_container .s_label
            {
                font-size: 13px;
            }

            #t_container .l_sbox
            {
                font-size: 11px !important;
            }

            #t_container #l_context
            {
                vertical-align: bottom;
                padding-top: 8px;
            }

            #t_container #l_sensors
            {
                vertical-align: bottom;
                padding-top: 8px;
            }

            #asset_value
            {
                width: 60px !important;
            }

            #descr
            {
                margin-bottom: 15px;
            }

            #av_info
            {
                width: 80%;
                margin: 10px auto;
            }


            /* Scan Summary*/

            #summary_container
            {
                width:  800px;
                margin: 20px auto;
                padding-bottom: 10px;
            }

            .error
            {
                color: #D8000C !important;
            }

            .warning
            {
                color: #9F6000 !important;
            }

            .success
            {
                color: #4F8A10 !important;
            }

            #summary_container #t_sm_container
            {
                border-collapse: collapse;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            }

            #t_sm_container th_details
            {
                width: 40px;
            }

            #t_sm_container .td_details img
            {
                cursor: pointer;
            }

            .dataTables_wrapper .dt_header div.dt_title
            {
                top:6px;
                left: 0px;
                right: 0px;
                margin: auto;
                text-align: center;
            }

            .details_info
            {
                display:none;
            }

            .host_details_w, .host_details_w:hover
            {
               color: #9F6000 !important;
               background-color: #FEEFB3 !important;
            }

            .table_data  > tbody > tr:hover > td.host_details_w
            {
                background-color: #FEEFB3 !important;
            }

            .host_details_e, .host_details_e:hover
            {
                background: #FFBABA !important;
                color: #D8000C !important;
            }

            .table_data  > tbody > tr:hover > td.host_details_e
            {
                background: #FFBABA !important;
            }

            .tray_container
            {
                border: 0px;
                background-color: inherit;
                position:relative;
                height:100%;
                margin: 2px 5px;
            }

            .tray_triangle
            {
                position: absolute;
                z-index: 99999999;
                top: -17px;
                left: 20px;
                width:0;
                height:0;
                border-color: transparent transparent #FFBABA transparent;
                border-style: solid;
                border-width: 7px;
            }

            .tt_error
            {
                 border-color: transparent transparent #FFBABA transparent;
            }

            .tt_warning
            {
                 border-color: transparent transparent #FEEFB3 transparent;
            }

            .tray_container ul
            {
                text-align: left;
                padding: 10px 0px 10px 20px;
            }

            .tray_container ul li
            {
                text-align: left;
                list-style-type: square;
                color: inherit;
            }

        </style>

        <script type='text/javascript'>


            /****************************************************
             ******************* AJAX Validator *****************
             ****************************************************/

            $(document).ready(function(){


               /***************************************************
                *********************** Token *********************
                ***************************************************/

                Token.add_to_forms();


               /****************************************************
                ************ Ajax Validator Configuration **********
                ****************************************************/

                var av_config = {
                    validation_type: 'complete', // single|complete
                    errors:{
                        display_errors: 'all', //  all | summary | field-errors
                        display_in: 'av_info'
                    },
                    form : {
                        id  : 'scan_form',
                        url : $('#scan_form').attr('action')
                    },
                    actions: {
                        on_submit:{
                            id: 'send',
                            success: '<?php echo _('Save')?>',
                            checking: '<?php echo _('Saving')?>'
                        }
                    }
                };

                ajax_validator = new Ajax_validator(av_config);

                $('#send').click(function() {

                    var msg = "<?php echo _('The information in the inventory will be overwritten with the results of the active asset discovery. Would you like to continue?')?>";

                    if(confirm(msg))
                    {
                        if (ajax_validator.check_form() == true)
                        {
                            $.ajax({
                                type: "POST",
                                url: 'save_scan.php',
                                data: $('#scan_form').serialize(),
                                beforeSend: function(xhr){

                                    $('#av_info').html('');

                                    show_loading_box('scan_container', '<?php echo _('Saving scanned assets')?>...', '');
                                },
                                error: function(data){

                                    //Check expired session
                                    var session = new Session(data, '');

                                    if (session.check_session_expired() == true)
                                    {
                                        session.redirect();

                                        return;
                                    }

                                    hide_loading_box();

                                    var config_nt = { content: av_messages['unknown_error'],
                                        options: {
                                            type:'nf_error',
                                            cancel_button: false
                                        },
                                        style: 'width: 80%; margin: 50px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                    };

                                    var nt = new Notification('nt_error', config_nt);

                                    $('body').html(nt.show());

                                    window.scrollTo(0,0);
                                },
                                success: function(data){

                                    //Check expired session
                                    var session = new Session(data, '');

                                    if (session.check_session_expired() == true)
                                    {
                                        session.redirect();

                                        return;
                                    }

                                    hide_loading_box();

                                    $('body').html(data);

                                    window.scrollTo(0,0);
                                }
                            });
                        }
                    }
                });


                $('#cancel').click(function() {
                    $('.av_b_back').trigger('click');
                });



               /****************************************************
                ********************** Tooltips ********************
                ****************************************************/

                $(".info").tipTip({maxWidth: '380px'});


               /****************************************************
                ******************* Greybox Options ****************
                ****************************************************/


                if (!parent.is_lightbox_loaded(window.name))
                {
                    $('.c_back_button').show();
                }
            });

        </script>
    </head>

    <body>

        <div class="c_back_button">
            <input type='button' class="av_b_back" onclick="javascript:history.go(-1);"/>
        </div>

        <div id="av_info">
            <?php
            if ($msg == 'saved')
            {
                $config_nt = array(
                    'content' => _('Assets saved successfully'),
                    'options' => array (
                        'type'          => 'nf_success',
                        'cancel_button' => TRUE
                   ),
                    'style'   => 'width: 80%; margin: auto; text-align:center;'
                );

                $nt = new Notification('nt_1', $config_nt);
                $nt->show();
            }
            ?>
        </div>

        <div id="scan_container">

            <p>
               <?php echo _("Please, fill these global properties about the assets you've scanned");?>
            </p>

            <div class="legend">
                <?php echo _('Values marked with (*) are mandatory');?>
            </div>

            <form method="POST" name="scan_form" id="scan_form" action="save_scan.php" enctype="multipart/form-data">

                <?php
                for ($i = 0; $i < $num_ips; $i++)
                {
                    echo "<input type='hidden' class='vfield' name='ips[]' id='ip_$i' value='".$ips[$i]."'/>";
                }

                foreach ($_POST as $k => $v)
                {
                    if(preg_match("/^fqdn/", $k) == TRUE)
                    {
                        ?>
                        <input type="hidden" class='vfield' name="<?php echo Util::htmlentities($k) ?>" value="<?php echo Util::htmlentities($v) ?>"/>
                        <?php
                    }
                }
                ?>

                <table id="t_container">

                    <!-- Group name and Description labels-->
                    <tr>
                        <td class="td_left">
                            <label for="group_name"><?php echo _('Optional group name')?></label>
                        </td>

                        <td class="td_right">
                            <label for="descr"><?php echo _('Description')?></label>
                        </td>
                    </tr>


                    <!-- Group name and Description inputs -->
                    <tr>
                        <td class="td_left">
                            <input type="text" name="group_name" id="group_name" class='vfield'/>
                        </td>

                        <td class="td_right">
                            <textarea name="descr" id="descr" class="vfield"><?php echo $descr;?></textarea>
                        </td>
                    </tr>


                    <!-- Asset value/External Asset -->
                    <tr>
                        <td class="td_left">
                            <table>
                                <tr>
                                    <td>
                                        <label for="asset_value"><?php echo _('Asset Value') . required();?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <select name="asset_value" id="asset_value" class="vfield">
                                            <?php
                                            for ($i = 0; $i <= 5; $i++)
                                            {
                                                $selected = ($asset_value == $i) ? "selected='selected'" : '';
                                                echo "<option value='$i' $selected>$i</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="td_right">
                            <table>
                                <tr>
                                    <td>
                                        <span class="s_label" id="sl_external"><?php echo _('External Asset') . required();?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="radio" id="external_yes" name="external" class="vfield" value="1"/>
                                        <label for="external_yes"><?php echo _('Yes')?></label>

                                        <input type="radio" id="external_no" name="external" checked="checked" class="vfield" value="0"/>
                                        <label for="external_no"><?php echo _('No')?></label>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>


                    <!-- Sensor labels -->
                    <tr>
                       <td class="td_left" colspan="2">
                            <span class="s_label" id="sl_sboxs[]"><?php echo _('Sensors') . required();?></span>
                        </td>
                    </tr>


                    <!-- Sensors -->
                    <?php
                    $s_chks     = array();
                    $no_sensors = '';

                    //Current CTX
                    $c_ctx = $ctx;

                    if (empty($c_ctx) && !Session::is_pro())
                    {
                        $c_ctx = Session::get_default_ctx();
                    }

                    if (count($all_sensors) <= 0)
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
                            $s_name = $s_data['name'];
                            $s_ip   = $s_data['ip'];


                            $s_chk_id      = 'id="sboxs'.$i.'"';
                            $s_chk_class   = ' class="vfield"';
                            $s_chk_checked = ' checked="checked"';

                            $s_chk_opt    = $s_chk_id.$s_chk_class.$s_chk_checked;

                            $s_chk_label  = '<label class="l_sbox" for="sboxs'.$i.'">'.$s_ip." (".$s_name.")".'</label>';

                            $s_chks[] = '<input type="checkbox" name="sboxs[]" '.$s_chk_opt.' value="'.$s_id.'"/>'.$s_chk_label;

                            $i++;
                        }
                    }
                    ?>

                    <tr>
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
                    </tr>

                    <!-- Save and Cancel buttons -->
                    <tr>
                        <td colspan="2" style="text-align: center; padding-top: 10px;">
                            <input type="button" name="cancel" class="av_b_secondary" id="cancel" value="<?php echo _('Cancel')?>"/>
                            <input type="button" name="send" id="send" value="<?php echo _('Save')?>"/>
                        </td>
                    </tr>

                </table>

            </form>

        </div>

    </body>
</html>
