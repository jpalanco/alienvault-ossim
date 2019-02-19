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

Session::logcheck('environment-menu', 'PolicyNetworks');


/****************************************************
 ********************* Tooltips *********************
 ****************************************************/

$t_ips = "<div style='font-weight:normal; width: 170px;'>
              <div><span class='bold'>Format:</span> CIDR [,CIDR,...]</div>
              <div><span class='bold'>CIDR:</span> xxx.xxx.xxx.xxx/xx</div>
          </div>";


/****************************************************
 ******************** Net Data **********************
 ****************************************************/

//Flags

$is_new_net = TRUE;

//Database connection
$db = new ossim_db();
$conn = $db->connect();


$id  = GET('id');
$msg = GET('msg');

if (!empty($id))
{
    ossim_valid($id, OSS_HEX, 'illegal:' . _('Net ID'));

    if (ossim_error())
    {
        echo ossim_error(_('Error! Network not found'));

        exit();
    }

    $net = new Asset_net($id);
    $net->load_from_db($conn);

    $is_new_net = FALSE;

    $can_i_modify_ips = Asset_net::can_i_modify_ips($conn, $id);
}
else
{
    //New net
    $id = Util::uuid();

    $net = new Asset_net($id);

    $can_i_modify_ips = TRUE;
}


//Getting net data
$id   = $net->get_id();
$name = $net->get_name();

//Net Sensors
list($all_sensors, $s_total) = Av_sensor::get_list($conn, '', FALSE, TRUE);

//CTX
$ctx      = $net->get_ctx();
$ctx_name = (empty($ctx)) ? _('None') : Session::get_entity_name($conn, $ctx);

$is_ext_ctx   = FALSE;
$context_type = 'local';

$ext_ctxs = Session::get_external_ctxs($conn);

if (!empty($ext_ctxs[$ctx]))
{
    $is_ext_ctx   = TRUE;
    $context_type = 'remote';
    $r_server     = Server::get_server_by_ctx($conn, $ctx);

    if ($r_server)
    {
        $r_server_name = $r_server->get_name() . ' ('. $r_server->get_ip() .')';
    }
    else
    {
        $r_server_name = '';
    }
}

$descr = $net->get_descr();

$icon  = $net->get_icon();
$icon  = (!empty($icon)) ? 'data:image/png;base64,'.base64_encode($icon) : '';

$external = $net->get_external();

$asset_value = $net->get_asset_value();
$owner       = $net->get_owner();

//Net Ips
$ips = $net->get_ips();


$net_sensors = $net->get_sensors();
$sensors     = $net_sensors->get_sensors();

//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',             'def_path' => TRUE),
        array('src' => 'jquery.autocomplete.css',   'def_path' => TRUE),
        array('src' => 'tree.css',                  'def_path' => TRUE),
        array('src' => 'av_icon.css',               'def_path' => TRUE),
        array('src' => 'tipTip.css',                'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
        array('src' => 'utils.js',                      'def_path' => TRUE),
        array('src' => 'notification.js',               'def_path' => TRUE),
        array('src' => 'token.js',                      'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
        array('src' => 'ajax_validator.js',             'def_path' => TRUE),
        array('src' => 'messages.php',                  'def_path' => TRUE),
        array('src' => 'jquery.elastic.source.js',      'def_path' => TRUE),
        array('src' => 'jquery.cookie.js',              'def_path' => TRUE),
        array('src' => 'jquery.tmpl.1.1.1.js',          'def_path' => TRUE),
        array('src' => 'jquery.dynatree.js',            'def_path' => TRUE),
        array('src' => 'combos.js',                     'def_path' => TRUE),
        array('src' => 'jquery.base64.js',              'def_path' => TRUE),
        array('src' => 'av_icon.js.php',                'def_path' => TRUE),
        array('src' => 'asset_context_tree.js.php',     'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'js');
    ?>


    <style type='text/css'>

        input[type='text'], input[type='hidden'], select
        {
            width  : 98%;
            height : 18px;
        }

        textarea
        {
            width : 98%;
        }

        table
        {
            border-collapse : collapse;
            border-spacing  : 0px;
            border          : none;
            background      : none;
            margin          : auto;
            width           : 100%;
        }

        .text_ips
        {
            cursor     : default !important;
            font-style : italic !important;
            opacity    : 0.5 !important;
        }

        #text_ips, #ips
        {
            height : 30px !important;
        }

        #descr
        {
            height : 75px;
        }

        .img_format
        {
            color      : gray;
            font-style : italic;
            font-size  : 10px;
        }

        .legend
        {
            font-size  : 10px;
            font-style : italic;
            text-align : center;
            padding    : 0px 0px 5px 0px;
            margin     : auto;
            width      : 400px;
        }

        #net_container
        {
            width          : 680px;
            margin         : 20px auto;
            padding-bottom : 10px;
        }

        #net_container #t_container
        {
            border-collapse : collapse;
            border          : none;
            background      : none;
            margin          : auto;
            width           : 100%;
        }

        #t_container td
        {
            text-align     : left;
            vertical-align : top;
        }

        #t_container .td_left
        {
            padding : 6px 15px 0px 10px;
            width   : 50%;
            border  : none;
        }

        #t_container .td_right
        {
            padding : 6px 10px 0px 15px;
            width   : 50%;
            border  : none;
        }

        #t_container label, #t_container .s_label
        {
            font-size : 13px;
        }

        #t_container .l_sbox
        {
            font-size : 11px !important;
        }

        #t_container #l_fqdns
        {
            vertical-align : bottom;
        }

        #t_container #l_context
        {
            vertical-align : bottom;
            padding-top    : 8px;
        }

        #t_container #l_sensors
        {
            vertical-align : bottom;
            padding-top    : 8px;
        }

        #asset_value
        {
            width : 60px;
        }

        #del_selected
        {
            width      : 98%;
            margin     : 3px 0px 0px 3px;
            text-align : right;
        }

        #av_info
        {
            width  : 80%;
            margin : 10px auto;
        }

    </style>

    <script type='text/javascript'>

        var __cfg = <?php echo Asset::get_path_url() ?>;

        /****************************************************
         ******************* AJAX Validator *****************
         ****************************************************/

        function submit_form()
        {
            ajax_validator.submit_form();
        }


        $(document).ready(function()
        {
            $('#net_form').attr('action', __cfg.network.controllers + '/save_net.php');

            /****************************************************
             ************************ Icon **********************
             ****************************************************/


            <?php
            $show_icon_actions = ($is_ext_ctx == TRUE) ? 'false' : 'true';
            ?>

            $('#icon').av_icon({
                icon : "<?php echo $icon?>",
                show_actions : <?php echo $show_icon_actions?>
            });


            /****************************************************
             ************* Contexts and Sensors *****************
             ****************************************************/

            <?php
            if (Session::is_pro() && Session::show_entities())
            {
                ?>
                load_tree_context('<?php echo $context_type?>');
                <?php
            }
            ?>


            /***************************************************
             *********************** Token *********************
             ***************************************************/

            Token.add_to_forms();


            /****************************************************
             ************ Ajax Validator Configuration **********
             ****************************************************/

            var config = {
                validation_type: 'complete', // single|complete
                errors:          {
                    display_errors: 'all', //  all | summary | field-errors
                    display_in:     'av_info'
                },
                form:            {
                    id:  'net_form',
                    url: $('#net_form').attr('action')
                },
                actions:         {
                    on_submit: {
                        id:       'send',
                        success:  '<?php echo _('Save')?>',
                        checking: '<?php echo _('Updating')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').click(function ()
            {
                submit_form();
            });

            $('#cancel').click(function ()
            {
                parent.GB_close();
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
            else
            {
                $('#net_container').css('margin', '0px auto 20px auto');

                <?php
                if ($msg == 'saved')
                {
                    $_message = _('Your changes have been saved.');
                ?>
                    
                    if (typeof(parent) != 'undefined')
                    {
                        //Try - Catch to avoid if this launch an error, the lightbox must be closed.
                        try
                        {
                            top.frames['main'].show_notification('asset_notif', "<?php echo $_message ?>", 'nf_success', 15000, true);
                        }
                        catch(Err){}
    
                        var params =
                        {
                            'id': "<?php echo $id ?>"
                        }
    
                        parent.GB_hide(params);
                    }  
                      
                <?php
                }
                ?>
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
        if ($is_ext_ctx == TRUE)
        {
            $config_nt = array(
                'content' => _('The properties of this asset can only be modified at the USM:') . " <strong>$r_server_name</strong>",
                'options' => array(
                    'type'          => 'nf_warning',
                    'cancel_button' => TRUE
                ),
                'style'   => 'width: 80%; margin: auto; text-align:center;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        ?>
    </div>

    <div id="net_container">

    <div class="legend">
        <?php echo _('Values marked with (*) are mandatory'); ?>
    </div>

    <form method="POST" name="net_form" id="net_form" enctype="multipart/form-data">
        
        <input type="hidden" name="id" id="id" class="vfield" value="<?php echo $id;?>"/>
        <input type="hidden" name="ctx" id="ctx" class="vfield" value="<?php echo $ctx;?>"/>

        <table id="t_container">

            <!-- Netname and Icon labels -->
            <tr>
                <td class="td_left">
                    <label for="n_name"><?php echo _('Name') . required(); ?></label>
                </td>

                <td class="td_right">
                    <label for="icon"><?php echo _('Icon') ?></label>
                    <span class="img_format"><?php echo _('Allowed format: Up to 400x400 PNG, JPG or GIF image')?></span>
                </td>
            </tr>


            <!-- Netname and Icon inputs -->
            <tr>
                <td class="td_left">
                    <input type="text" class="vfield" name="n_name" id="n_name" value="<?php echo $name; ?>"/>
                </td>

                <td class="td_right">
                    <input type="file" class="vfield" name="icon" id="icon"/>
                </td>
            </tr>


            <!-- IPs/Owner and Description labels-->
            <tr>
                <td class="td_left">
                    <label for="ips"><?php echo _('CIDR') . required(); ?></label>
                </td>

                <td class="td_right">
                    <label for="description"><?php echo _('Description') ?></label>
                </td>
            </tr>


            <!-- IPs/owner and Description -->
            <tr>
                <td class="td_left">
                    <table>
                        <tr>
                            <td>
                                <?php
                                if ($can_i_modify_ips == TRUE)
                                {
                                    ?>
                                    <textarea class="vfield info" name="ips" id="ips"
                                              title="<?php echo $t_ips ?>"/><?php echo $ips ?></textarea>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <textarea class="text_ips" name="text_ips" id="text_ips" readonly="readonly"
                                              disabled="disabled"/><?php echo $ips ?></textarea>

                                    <input type="hidden" class="vfield" name="ips" id="ips" value="<?php echo $ips ?>"/>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="owner"><?php echo _('Owner') ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" class="vfield" name="owner" id="owner" value="<?php echo $owner ?>"/>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="td_right">
                    <textarea name="descr" id="descr" class='vfield'><?php echo $descr ?></textarea>
                </td>
            </tr>


            <!-- Context and Sensors labels -->
            <tr>
                <?php
                if (Session::show_entities() && Session::is_pro())
                {
                    ?>
                    <td id="l_context" class="td_left">
                        <label for="ctx"><?php echo _('Context') . required(); ?></label>
                    </td>

                    <td id="l_sensors" class="td_right">
                                            <span class="s_label" id="sl_sboxs[]"><?php echo _('Sensors') . required(); ?><span>
                    </td>
                <?php
                }
                else
                {
                    ?>
                    <td class="td_left" colspan="2">
                        <span class="s_label" id="sl_sboxs[]"><?php echo _('Sensors') . required(); ?></span>
                    </td>
                <?php
                }
                ?>
            </tr>


            <!-- Context Tree and Sensors -->
            <?php
            $s_chks     = array();
            $no_sensors = '';

            //Current CTX
            $c_ctx = $ctx;

            if (empty($c_ctx) && !Session::is_pro())
            {
                $c_ctx = Session::get_default_ctx();
            }

            if ($s_total <= 0)
            {
                $config_nt = array(
                    'content' => _('Warning! No sensors found'),
                    'options' => array(
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

                $any_s_checked = FALSE;

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
                    if ($s_status == 'enabled')
                    {
                        if ($is_new_net == TRUE && $any_s_checked == FALSE)
                        {
                            //Select first sensor by default for new networks
                            $s_chk_checked = " checked='checked'";
                            $any_s_checked = TRUE;
                        }
                        else if ($is_new_net == FALSE && array_key_exists($s_id, $sensors))
                        {
                            $s_chk_checked = " checked='checked'";
                            $any_s_checked = TRUE;
                        }
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
                        <div id="entity_selected">
                            <?php
                            echo _('Context selected') . ': <strong>' . Util::htmlentities($ctx_name) . "</strong>";
                            ?>
                        </div>
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


            <tr>
                <td class="td_left">

                    <!-- Asset value and external asset labels and inputs -->

                    <table>
                        <tr>
                            <td>
                                <label for="asset_value"><?php echo _('Asset Value') . required(); ?></label>
                            </td>
                            <td>
                                <span class="s_label" id="sl_external"><?php echo _('External Asset') . required(); ?></span>
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
                            <td>
                                <?php
                                $chk_ext_yes = ($external == 1) ? 'checked="checked"' : '';
                                $chk_ext_no  = ($external == 0) ? 'checked="checked"' : '';
                                ?>

                                <input type="radio" id="external_yes" name="external" class="vfield"
                                       value="1" <?php echo $chk_ext_yes ?>/>
                                <label for="external_yes"><?php echo _('Yes') ?></label>

                                <input type="radio" id="external_no" name="external" class="vfield"
                                       value="0" <?php echo $chk_ext_no ?>/>
                                <label for="external_no"><?php echo _('No') ?></label>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="td_rigth">
                    &nbsp;
                </td>
            </tr>

            <?php
            if ($is_ext_ctx == FALSE)
            {
                ?>
                <!-- Save and Cancel buttons -->
                <tr>
                    <td colspan="2" style="text-align: center; padding-top: 10px;">
                        <input type="button" name="cancel" class="av_b_secondary" id="cancel" value="<?php echo _('Cancel') ?>"/>
                        <input type="button" name="send" id="send" value="<?php echo _('Save') ?>"/>
                    </td>
                </tr>
                <?php
            }
            ?>

        </table>
    </form>

</div>

</body>

</html>
