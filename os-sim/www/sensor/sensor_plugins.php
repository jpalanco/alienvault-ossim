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


ob_implicit_flush();

require_once 'av_init.php';
require_once 'get_sensors.php';


Session::logcheck('configuration-menu', 'PolicySensors');


$info_error = NULL;

$ip_get = GET('sensor');
ossim_valid($ip_get, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _('Sensor IP'));


if (ossim_error())
{
    die(ossim_error());
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript">


        function GB_onhide()
        {
            document.location.reload();
        }

        function contenido(id)
        {
            var id_icono = id.substr(4);

            if ($("#icono"+id_icono).attr('src') == "../pixmaps/server--plus.png")
            {
                var ip = id.substr (4);
                    ip = ip.replace(/_/g, ".");

                $.ajax({
                    type: "GET",
                    url: "get_sensor_info.php",
                    data: { sensor_ip: ip },
                    beforeSend: function(xhr) {
                        $("#"+id).show();
                        $("#icono"+id_icono).attr('src','../pixmaps/server--minus.png');
                        var loading = '<img width="16" align="absmiddle" src="../pixmaps/loading3.gif">';

                        $("#"+id).html(loading+' <?php echo _("Loading data..."); ?>');
                        $("#"+id).css({ padding: "5px 0px 5px 0px" });
                    },
                    success: function(msg) {
                        $("#"+id).css({ padding: "0px 0px 0px 0px" });
                        $('#'+id).html(msg);
                    }
                });
            }
            else
            {
                $("#icono"+id_icono).attr('src','../pixmaps/server--plus.png');
                $("#"+id).hide();
            }
        }

        function load_lead(cid,pid,sip)
        {
            var icon_id = 'img_'+cid;

            if ($("#"+icon_id).attr('src') == "../pixmaps/plus-small.png")
            {
                $.ajax({
                    type: "GET",
                    url: "get_sensor_leads.php?pid="+pid+"&sensor="+sip,
                    data: "",
                    success: function(msg) {
                        $('#plugin_'+cid).html(msg);
                        $("#"+icon_id).attr('src','../pixmaps/minus-small.png');

                        $('#plugin_'+cid).show();
                        $('#selector_'+cid).show();
                        mark(cid, false);
                    }
                });
            }
            else
            {
                $("#"+icon_id).attr('src', '../pixmaps/plus-small.png');
                $('#plugin_'+cid).hide();
                $('#selector_'+cid).hide();
            }
        }

        function mark(id, show_processing)
        {
            if (show_processing == true)
            {
                $('#selector_'+id+ ' .m_button').addClass('av_b_processing');
            }


            var y = $('#yellow_'+id).val()*3600;
            var r = $('#red_'+id).val()*3600; // need seconds
            var now = new Date; // Generic JS date object
            var unixtime_ms = now.getTime(); // Returns milliseconds since the epoch
            var unixtime = parseInt(unixtime_ms / 1000);

            $('#plugin_'+id+' .trc').each(function(){
                var eventdate = parseInt($(this).attr('txt'));
                var img = "";
                var bgcolor = "";
                if (unixtime - eventdate >= r)
                {
                    img = "../pixmaps/flag_red.png";
                    bgcolor = "#FFDFDF";
                }
                else if (unixtime - eventdate >= y)
                {
                    img = "../pixmaps/flag_yellow.png";
                    bgcolor = "#FFFBCF";
                }
                else
                {
                    img = "../pixmaps/flag_green.png";
                    bgcolor = "#DEEBDB";
                }

                $(this).css("background-color",bgcolor);

                $('td img',this).attr("src", img);
            });

            if (show_processing == true)
            {
                setTimeout(function(){$('#selector_'+id+ ' .m_button').removeClass('av_b_processing');},200);
            }
        }

        $(document).ready(function() {

            <?php
            if($ip_get != '' && Session::sensorAllowed($ip_get))
            {
                ?>
                var loading = '<img width="16" align="absmiddle" src="../pixmaps/loading3.gif">';
                var id      = '<?php echo $ip_get;?>';
                    id = id.replace(/\./g, "_");
                    id = 'capa'+id;

                $("#icono"+id).attr('src','../pixmaps/server--minus.png');
                $("#"+id).html(loading+' <?php echo _("Loading data..."); ?>');
                $("#"+id).css({ padding: "5px 0px 5px 0px" });
                $.ajax({
                    type: "GET",
                    url: "get_sensor_info.php",
                    data: { sensor_ip: '<?php echo $ip_get; ?>' },
                    success: function(msg) {
                        $("#"+id).css({ padding: "0px 0px 0px 0px" });
                        $('#'+id).html(msg);
                        $('#'+id).show();
                        $('#loading').hide();
                    }
                });
                <?php
            }
            else
            {
                ?>$('#loading').hide();<?php
            }
            ?>

            //Greybox options

            $('.c_back_button').show();
        });
    </script>

<style type="text/css">
    html,body
    {
        height : auto !important;
        height:100%;
        min-height:100%;
    }

    #error_messages
    {
        width: 70%;
    }

    .ossim_error
    {
       width: auto;
    }

    .error_item
    {
       padding-left: 20px;
    }

    .s_info
    {
        font-family:tahoma;
        font-size:11px;
        font-weight:normal;
    }

    #loading
    {
        position: absolute;
        width: 99%;
        height: 99%;
        margin: auto;
        text-align: center;
        background: transparent;
        z-index: 10000;
    }

    #loading div
    {
        position: relative;
        top: 40%;
        margin:auto;
    }

    #loading div span
    {
        margin-left: 5px;
        font-weight: bold;
    }

    .t_sensors
    {
        margin: 35px auto 15px auto;
        width: 100%;
        text-align: center;
        background: transparent;
    }

    .td_sensor
    {
        text-align: left;
        padding-left:5px;
        background: #E5E5E5;
        height: 25px;
        white-space: nowrap;
    }

    .t_sensor_info
    {
        width: 36px;
        height: 100%;
        border-collapse: collapse;
    }

    .t_sensor_info td.bk_top
    {
        background:url('/ossim/pixmaps/bktop.gif') no-repeat;
        background-position: left top;
        height: 20px;
        vertical-align: top;
    }

    .t_sensor_info td.bk_center
    {
        background:url('/ossim/pixmaps/bkcenter.gif') repeat-y;
        height: 48px;
    }

    .t_sensor_info td.bk_bg
    {
        background:url('/ossim/pixmaps/bkbg.gif') repeat-y;
    }

    .t_sensor_info td.bk_bottom
    {
        background:url('/ossim/pixmaps/bkdown.gif') no-repeat;
        background-position: left bottom;
        height: 20px;
        vertical-align: bottom;
    }

    .t_sensor_info_data .tr_si_refresh, .t_sensor_info_data .tr_si_refresh:hover
    {
        background: transparent !important;
    }

    .t_sensor_info_data .tr_si_refresh td
    {
        text-align: center;
        padding-top: 10px;
        background: transparent !important;
    }

    .tr_l_sensor, .tr_l_sensor:hover
    {
        background: white !important;
    }

    .tr_l_sensor > td
    {
        display:none;
        padding-left:10px;
        border-bottom:none;
        background: white !important;
    }

    .hidden_row
    {
        display: none;
    }

</style>
    <?php include_once '../host_report_menu.php';?>
</head>

<body>
<?php
/* connect to db */

$db    = new ossim_db();
$conn  = $db->connect();


// Sensors perm check
if (!Session::menu_perms('configuration-menu', 'PolicySensors'))
{
    echo ossim_error(_("You need permissions of section '")."<b>"._("Configuration -> AlienVault Components -> Sensors")."</b>"._("' to see this page. Contact with the administrator."), AV_NOTICE);
    exit();
}

?>

<div id='loading'>
    <div>
       <img src='../pixmaps/loading3.gif' alt='<?php echo _("Loading")?>'/><span><?php echo _('Loading sensor information, please wait a few seconds,')?> ...</span>
    </div>
</div>



<?php

    ob_flush();

    $conf           = $GLOBALS['CONF'];
    $acid_link      = $conf->get_conf('acid_link');
    $acid_prefix    = $conf->get_conf('event_viewer');
    $acid_main_link = str_replace('//', '/', $conf->get_conf('acid_link') . '/' . $acid_prefix . "_qry_main.php?clear_allcriteria=1&search=1&bsf=Query+DB&ossim_risk_a=+");

    $db_sensor_list = array();
    $list_no_active = array();

    $aux_sensor_list = Av_sensor::get_basic_list($conn);

    if (is_array($aux_sensor_list))
    {
        foreach($aux_sensor_list as $aux_s_data)
        {
            $db_sensor_list[]                  = $aux_s_data['ip'];
            $db_sensor_rel[$aux_s_data['ip']]  = $aux_s_data['name'];
            $list_no_active[$aux_s_data['ip']] = $aux_s_data['name'];
        }
    }
    list($sensor_list, $err) = server_get_sensors();

    if ($err != '')
    {
        $info_error[] = $err;
    }

    if (!$sensor_list && empty($ip_get))
    {
        $info_error[] = _("There aren't any sensors connected to OSSIM server");
    }

    $ossim_conf = $GLOBALS['CONF'];


if (!empty($info_error))
{
    $msg = "<div class='error_item'>".implode("</div><div class='error_item'>", $info_error)."</div>";

    $config_nt = array(
        'content' => $msg,
        'options' => array (
            'type'          => 'nf_warning',
            'cancel_button' => FALSE
        ),
        'style'   => 'width: 80%; margin: 20px auto;'
    );

    $nt = new Notification('nt_1', $config_nt);

    $nt->show();
}

?>
<div class="c_back_button">
    <input type='button' class="av_b_back" onclick="document.location.href='sensor.php';return false;"/>
</div>

<table class="t_sensors noborder" cellpadding='0' cellspacing='0'>

    <?php
    foreach($sensor_list as $sensor=>$sensor_plugins_list)
    {
        $ip = $sensor;

        unset($list_no_active[$ip]); // Remove active sensors of inactive list
        if (isset($db_sensor_rel[$ip]))
        {
            $name = $db_sensor_rel[$ip];
        }

        $state = "start";

        /* get plugin list for each sensor */
        //$sensor_plugins_list = server_get_sensor_plugins($ip);
        /*
        *  show sensor ip (and sensor name if available)
        *  at the top of the table
        */
        $up_enabled    = 0;
        $down_disabled = 0;
        $totales       = 0;

        foreach($sensor_plugins_list as $plugin_id=>$sensor_plugin)
        {
            $state   = $sensor_plugin['state'];
            $enabled = $sensor_plugin['enabled'];

            if ($state == 'start' || $enabled == 'true')
            {
                $up_enabled++;
            }
            if ($state == 'stop' || $enabled != 'true')
            {
                $down_disabled++;
            }

            $totales++;
        }

    ?>

    <tr>
        <td class='noborder'>
            <a href='' onclick="contenido('capa<?php echo str_replace(".", "_", $ip)?>'); return false;">
                <?php
                    $id_estado = "icono" . str_replace(".","_",$ip);
                    $src       = ( $ip_get==$ip ) ? "../pixmaps/server--minus.png" : "../pixmaps/server--plus.png";
                ?>
                <img id='<?php echo $id_estado?>' align='bottom' src="<?php echo $src?>" border='0'>
            </a>
        </td>

        <td class='td_sensor'>
            <table class='noborder' border='0' cellpadding='0' cellspacing='0' style='background-color:transparent;' nowrap='nowrap'>
                <tr>
                    <td class='noborder' style='padding-right:2px;'></td>
                    <td class='noborder' style='text-align: left;padding-right:4px;'>
                        <?php
                            $suf      = (isset($name)) ? $name : $ip;
                            $id_s     = $ip.";".$suf;
                            $name_txt = (isset($name)) ? " [ $name ] " : "";
                        ?>
                        <a href='' onclick="contenido('capa<?php echo str_replace(".", "_", $ip)?>');return false;" class='HostReportMenu' id='<?php echo $id_s?>'><?php echo $ip.$name_txt?></a>
                    </td>

                    <td class="noborder" style="text-align: left;">
                        <span class="s_info"> [ <?php echo _('UP or ENABLED')?>: </span>
                        <span class="s_info" style="color:#089313;font-weight:bold;"><?php echo $up_enabled?></span>
                        <span class="s_info">/ <?php echo _('DOWN or DISABLED')?>: </span>
                        <span class="s_info" style="color:#E00E01;font-weight:bold;"><?php echo $down_disabled?></span>
                        <span class="s_info">/ <?php echo _('Totals')?>: </span>
                        <span class="s_info" style="color:#000000;font-weight:bold;"><?php echo $totales?></span>
                        <span class="s_info"> ]</span>
                        <?php

                        if (is_array($db_sensor_list) && !in_array($ip, $db_sensor_list))
                        {
                            echo "<span style='margin-left: 15px;'>";
                                echo "<b>"._("Warning")."</b>:"._("The sensor is being reported as enabled by the server but isn't configured.");
                                echo "&nbsp;"._("Click")." <a href='".Menu::get_menu_url("/ossim/sensor/newsensorform.php?ip=$ip", "configuration", "deployment", "components", "sensors")."'>"._("here")."</a> "._("to configure the sensor").".";
                            echo "</span>";
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td colspan='2' height="1"></td>
    </tr>

    <tr>
        <td class="noborder"></td>
        <td class="noborder" valign="top">
            <div id="<?php echo "capa" . str_replace('.', '_', $ip); ?>" style="diplay:none;"></div>
        </td>
    </tr>

    <?php
    }

    foreach($list_no_active as $key => $value)
    {
        ?>
        <tr>
            <td class="noborder"><img align="bottom" src="../pixmaps/server.png" border="0"/></td>
            <td class="noborder" style="text-align: left;padding-left:5px;" height="25" bgcolor="#F2F2F2" nowrap='nowrap'>
                <table class="noborder transparent" border='0' cellpadding='0' cellspacing='0' nowrap='nowrap'>
                    <tr>
                        <td class="noborder" style="padding-right:2px;"></td>
                        <td class="noborder" style="text-align: left;color:#696563;padding-right:4px;"><?php echo $key ." [ ". $value ." ] "; ?></td>
                        <td class="noborder" style="padding-right:4px;"><img align="bottom" src="../pixmaps/chart_bar_off.png" border="0"/></td>
                        <td class="noborder" style="text-align: left;">
                            <span class="s_info" style="color:#696563;"> [ <?php echo _('UP or ENABLED')?>: </span>
                            <span class="s_info" style="color:#089313;font-weight:bold;"> - </span>
                            <span class="s_info" style="color:#696563;">/ <?php echo _('DOWN or DISABLED')?>: </span>
                            <span class="s_info" style="color:#E00E01;font-weight:bold;"> - </span>
                            <span class="s_info" style="color:#696563;">/<?php echo _('Totals')?>: </span>
                            <span class="s_info" style="color:#000000;font-weight:bold;"> - </span>
                            <span class="s_info" style="color:#696563;"> ]</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td></td>
            <td height="1" bgcolor="#FFFFFF"></td>
        </tr>
        <?php
    }
    ?>

    </table>
</body>
</html>

<?php
$db->close();
?>

