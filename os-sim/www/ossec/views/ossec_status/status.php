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

require_once dirname(__FILE__) . '/../../conf/config.inc';

$m_perms  = array('environment-menu', 'environment-menu');
$sm_perms = array('EventsHids', "EventsHidsConfig");

if (!Session::menu_perms($m_perms, $sm_perms))
{
    Session::unallowed_section(NULL, 'noback', $m_perms[0], $sm_perms[0]);
}

//Current sensor
$sensor_id = GET('sensor_id');

ossim_valid($sensor_id,  OSS_HEX, OSS_NULLABLE,  'illegal:' . _("Sensor ID"));

if (!ossim_error())
{
    $_SESSION['ossec_sensor'] = $sensor_id;
}
else
{
    unset($_GET['sensor_id']);
    ossim_clean_error();
}

$sensor_id = $_SESSION['ossec_sensor'];

$db     = new ossim_db();
$conn   = $db->connect();

$s_data = Ossec_utilities::get_sensors($conn, $sensor_id);

//Check available sensors
if (!is_array($s_data['sensors']) || empty($s_data['sensors']))
{
    $styles = 'width: 90%; text-align:left; margin: 50px auto;';

    echo ossim_error(_('There is no sensor available'), AV_INFO, $styles);
    exit();
}

$db->close();

$sensor_opt = $s_data['sensor_opt'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/environment/detection/hids-agents.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>

    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.flot.pie.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.simpletip.js"></script>

    <!-- JQuery DataTable: -->
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>

    <!-- JQuery tipTip: -->
    <script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>

    <script type="text/javascript" src='/ossim/ossec/js/ossec_msg.php'></script>
    <script type='text/javascript' src='/ossim/js/notification.js'></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>
    <script type="text/javascript" src='/ossim/ossec/js/common.js'></script>
    <script type="text/javascript" src='/ossim/ossec/js/agents.js'></script>

    <script type="text/javascript" src="/ossim/js/greybox.js"></script>

    <?php require AV_MAIN_ROOT_PATH.'/host_report_menu.php';?>
    <script type="text/javascript">

        function formatNmb(nNmb)
        {
            var sRes = "";
            for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
            {
                sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? "<?php echo Ossec_utilities::thousands_locale()?>": '') + sRes;
            }

            return sRes;
        }


        function showTooltip(x, y, contents)
        {
            $('<div id="tooltip" class="tooltipLabel"><span style="font-size:10px;">' + contents + '</span></div>').css({
                position: 'absolute',
                display: 'none',
                top: y - 28,
                left: x - 10,
                border: '1px solid #ADDF53',
                padding: '1px 2px 1px 2px',
                'background-color': '#CFEF95',
                opacity: 0.80
            }).appendTo("body").fadeIn(200);
        }


        function load_agent_information()
        {
            var sensor_id = $('#sensors').val();

            $.ajax({
                type: "POST",
                url: "/ossim/ossec/providers/ossec_status/load_agents.php",
                data: "sensor_id="+sensor_id,
                beforeSend: function(xhr){
                    $('#r_loading').remove();

                    $('#sensors').after('<img id="r_loading" style="vertical-align: middle; margin-left: 3px;" src="/ossim/pixmaps/loading.gif" width="13" alt="<?php echo _('Loading')?>">');
                },
                error: function(data){
                    $('#r_loading').remove();
                },
                success: function(data){

                    $('#r_loading').remove();

                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }

                    $('#body_al_1').html(data);
                    $('#sensors').removeAttr('disabled');

                    show_select();
                }
            });
        }


        $(document).ready(function() {

            load_contextmenu();

            load_agent_information();
        });

    </script>
</head>

<body>

<?php include_once AV_MAIN_ROOT_PATH.'/local_menu.php'; ?>

    <div id='container_center'>

        <div class='status_sec'>
            <div class="oss_containter_graph">
                <table class='transparent' style="width: 100%;">
                    <tr>
                        <td class='noborder pad_0'>
                            <table class='oss_graph'>
                                <tr><th class='headerpr_no_bborder'><?php echo _('HIDS Events Trend')?></th></tr>
                                <tr>
                                    <td>
                                        <iframe src="/ossim/panel/event_trends.php?type=hids" frameborder="0" style="width:470px;height:215px;overflow:hidden"></iframe>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class='noborder pad_0'>
                            <table class='oss_graph' align='right'>
                                <tr><th class='headerpr_no_bborder'><?php echo _('HIDS Data Sources')?></th></tr>
                                <tr>
                                    <td>
                                        <iframe src="/ossim/panel/pie_graph.php?type=hids" frameborder="0" style="width:470px;height:215px;overflow:hidden"></iframe>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class='status_sec'>
            <div id='c_agent_table'>

                <?php $s_class = (Session::is_pro() && count($s_data['sensors']) > 1) ? 's_show' : 's_hide';?>

                <div class='c_filter_and_actions'>
                    <div class='c_filter'>
                        <label for='sensors'><?php echo _('Select sensor')?>:</label>
                        <select id='sensors' name='sensors' class='vfield <?php echo $s_class?>' disabled='disabled'>
                            <?php echo $sensor_opt?>
                        </select>
                    </div>
                </div>

                <div class='body_al' id='body_al_1'>
                    <table id='agent_table'>
                        <tr>
                            <td class='td_load'>
                                <span style='margin-right:5px'><?php echo _('Loading data')?></span><img src="<?php echo OSSEC_IMG_PATH;?>/loading.gif" align='absmiddle' border="0" width="13" alt="..." />
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
