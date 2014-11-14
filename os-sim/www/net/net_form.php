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


$id = GET('id');
$msg = GET('msg');

if (!empty($id))
{
    ossim_valid($id, OSS_HEX, 'illegal:' . _('Net ID'));

    if (ossim_error())
    {
        echo ossim_error(_('Error! Net not found'));

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
$id = $net->get_id();
$name = $net->get_name();

//Net Sensors
list($all_sensors, $s_total) = Av_sensor::get_list($conn, '', FALSE, TRUE);

//CTX
$ctx = $net->get_ctx();
$ctx_name = (empty($ctx)) ? _('None') : Session::get_entity_name($conn, $ctx);
$is_ext_ctx = FALSE;
$context_type = 'local';

$ext_ctxs = Session::get_external_ctxs($conn);

if (!empty($ext_ctxs[$ctx]))
{
    $is_ext_ctx   = TRUE;
    $context_type = 'remote';
}

$descr = $net->get_descr();
$icon = $net->get_html_icon();
$external = $net->get_external();

$asset_value = $net->get_asset_value();
$threshold_a = $net->get_threshold_a();
$threshold_c = $net->get_threshold_c();
$owner = $net->get_owner();

//Net Ips
$ips = $net->get_ips();


$net_sensors = $net->get_sensors();
$sensors = $net_sensors->get_sensors();

//Net Scan
$is_nagios_enabled = Asset_net_scan::is_plugin_in_net($conn, $id, 2007);


//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo _('OSSIM Framework'); ?></title>
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
<script type="text/javascript" src="../js/av_icon.js.php"></script>
<script type="text/javascript" src="../js/asset_context_tree.js.php"></script>

<!-- <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script> -->

<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
<link rel="stylesheet" type="text/css" href="../style/tree.css"/>
<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>

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

    #t_icon
    {
        width  : auto;
        margin : 0px;
        border : none !important;
    }

    #t_icon td
    {
        padding : 2px;
        border  : none;
    }

    #td_icon
    {
        border     : solid 1px #888888 !important;
        text-align : center !important;
        margin     : 0px auto;
        padding    : 0px !important;
        width      : 18px;
    }

    #td_icon_actions
    {
        white-space  : nowrap;
        padding-left : 5px !important;
    }

    .custom_input_file
    {
        overflow : hidden;
        position : relative;
    }

    .custom_input_file input[type="file"]
    {
        display : none;
    }

    .r_loading
    {
        position : absolute;
        right    : 1px;
        top      : 5px;
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

    #threshold_c, #threshold_a
    {
        width : 30px;
    }

    #av_info
    {
        width  : 80%;
        margin : 10px auto;
    }

</style>

<script type='text/javascript'>


    /****************************************************
     ******************* AJAX Validator *****************
     ****************************************************/

    function submit_form()
    {
        ajax_validator.submit_form();
    }


    $(document).ready(function ()
    {


        /****************************************************
         ************************ Icon **********************
         ****************************************************/

        var ri_config = {
            token_id: 'net_form',
            asset_id: '<?php echo $id?>',
            actions:  {
                url:       'net_actions.php',
                container: 'c_remove_icon'
            },
            icon:     {
                input_file_id: 'icon',
                container:     'td_icon',
                restrictions:  {
                    width:  16,
                    height: 16
                }
            },
            errors:   {
                display_errors: true,   // true|false
                display_in:     'av_info'
            }
        };


        bind_icon_actions(ri_config);


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

            // Loaded from details and some data changed
            <?php
            if ($msg == 'saved')
            {
            ?>
            if (typeof(top.frames['main'].force_reload) != 'undefined')
            {
                top.frames['main'].force_reload = 'info';
            }
            <?php
        }
            ?>
        }


        /*******************************************************************
         ** Close "Add Network" dialog box after success network creation **
         *******************************************************************/

        <?php
        if ('saved' === $msg)
        {
        ?>
        parent.GB_hide();
        <?php
        }
        ?>

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
            'content' => _('The properties of this asset can only be modified at the USM:') . " <strong>" . $external_ctxs[$ctx] . "</strong>",
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

<form method="POST" name="net_form" id="net_form" action="save_net.php" enctype="multipart/form-data">

<input type="hidden" name="id" id="id" class="vfield" value="<?php echo $id; ?>"/>

<input type="hidden" name="ctx" id="ctx" class="vfield" value="<?php echo $ctx; ?>"/>

<table id="t_container">

<!-- Netname and Icon labels -->
<tr>
    <td class="td_left">
        <label for="n_name"><?php echo _('Name') . required(); ?></label>
    </td>

    <td class="td_right">
        <label for="icon"><?php echo _('Icon') ?></label>
        <span class="img_format"><?php echo _('Allowed format') . ': 16x16 ' . _('png | jpg | gif image') ?></span>
    </td>
</tr>


<!-- Netname and Icon inputs -->
<tr>
    <td class="td_left">
        <input type="text" class="vfield" name="n_name" id="n_name" value="<?php echo $name; ?>"/>
    </td>

    <td class="td_right">
        <div style="position:relative; width: 98%;">
            <div class="r_loading"></div>
        </div>
        <table id="t_icon">
            <tr>
                <td id="td_icon">
                    <?php echo $icon ?>
                </td>

                <td id="td_icon_actions">
                                        
                                        <span id='c_remove_icon'>
                                            <?php
                                            if ($icon != '')
                                            {
                                                ?>
                                                <a id='remove_icon'
                                                   href="javascript:void(0)"><?php echo _('Remove icon') ?></a>
                                                <span> <?php echo _('or') ?> </span>
                                            <?php
                                            }
                                            ?>
                                        </span>
                                        
                                        <span class="custom_input_file" id="custom_input_file">
                                            <a href="javascript:void(0)"><?php echo _('Choose file') ?> ...</a>
                                            <input type="file" class="vfield" name="icon" id="icon"/>
                                        </span>
                </td>
            </tr>
        </table>
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
$s_chks = array();
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

    foreach ($all_sensors as $s_id => $s_data)
    {
        $s_name   = $s_data['name'];
        $s_ip     = $s_data['ip'];
        $all_ctxs = $s_data['ctx'];

        $s_ctxs   = '';
        $s_status = 'disabled';

        //Search enabled sensors by CTXs
        foreach ($all_ctxs as $e_id => $e_name)
        {
            if ($e_id == $c_ctx && !empty($c_ctx))
            {
                $s_status = 'enabled';
            }

            $s_ctxs .= ' ' . $e_id;
        }

        //Search checked sensors
        if ($is_new_net == FALSE)
        {
            $s_chk_checked = (!empty($sensors[$s_id])) ? ' checked="checked"' : '';
        }
        else
        {
            //First sensor is selected by default
            $s_chk_checked = ($i == 1 && !Session::show_entities()) ? " checked='checked'" : '';
        }

        $s_chk_id    = ' id = "sboxs' . $i . '"';
        $s_chk_class = ' class="vfield sensor_check' . $s_ctxs . '"';

        $s_chk_status = ($s_status == 'disabled' && Session::show_entities()) ? ' disabled="disabled"' : "";

        $s_chk_opt = $s_chk_id . $s_chk_class . $s_chk_status . $s_chk_checked;

        $s_chk_label = '<label class="l_sbox" for="sboxs' . $i . '">' . $s_ip . " (" . $s_name . ")" . '</label>';

        $s_chks[] = '<input type="checkbox" name="sboxs[]" ' . $s_chk_opt . ' value="' . $s_id . '"/>' . $s_chk_label;

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
                echo _('Context selected') . ': <strong>' . $ctx_name . "</strong>";
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
                            <td><?php echo $s_chks[$i] ?></td>
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
                    $chk_ext_no = ($external == 0) ? 'checked="checked"' : '';
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

    <td class="td_right">

        <!-- Thresholds and Scan Options labels and inputs -->

        <table>
            <tr>
                <td>
                    <span class="s_label" id=""><?php echo _('Thresholds') . required(); ?></span>
                </td>
                <td>
                    <label for="nagios"><?php echo _('Scan options'); ?></label>
                </td>
            </tr>

            <tr>
                <td>
                    <label for='threshold_c'><?php echo _("C") ?>:</label>
                    <input type="text" name="threshold_c" id="threshold_c" class="vfield"
                           value="<?php echo $threshold_c ?>"/>
                    &nbsp;
                    <label for='threshold_a'><?php echo _("A") ?>:</label>
                    <input type="text" name="threshold_a" id="threshold_a" class="vfield"
                           value="<?php echo $threshold_a ?>"/>

                </td>
                <td>
                    <?php $checked = ($is_nagios_enabled == TRUE) ? "checked='checked'" : ''; ?>
                    <input type="checkbox" class="vfield" name="nagios" id="nagios" value="1" <?php echo $checked ?>/>
                    <span style='margin-left: 2px;'><?php echo _('Availability Monitoring'); ?></span>
                </td>
            </tr>
        </table>
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
