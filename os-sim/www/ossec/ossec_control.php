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


require_once dirname(__FILE__).'/conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


//Current sensor
$sensor_id = $_SESSION['ossec_sensor'];

$db     = new ossim_db();
$conn   = $db->connect();

$s_data = Ossec_utilities::get_sensors($conn, $sensor_id);

$sensor_opt = $s_data['sensor_opt'];

$db->close();


//Check available sensors
if (!is_array($s_data['sensors']) || empty($s_data['sensors']))
{
    $styles = 'width: 90%; text-align:left; margin: 50px auto;';
    
    echo ossim_error(_('There is no sensor available'), AV_INFO, $styles);
    
    exit();
}


$oss_plugin_id = "7000-7999";

$link_siem = Menu::get_menu_url("../forensics/base_qry_main.php?&plugin=$oss_plugin_id&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d", 'analysis', 'security_events', 'security_events');


//Ossec Status

$response = Ossec_control::execute_action($sensor_id, 'status');
$response = Ossec_control::get_html_status($response);


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type='text/javascript' src="/ossim/js/notification.js"></script>
    <script type='text/javascript' src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="/ossim/js/token.js"></script>
    <script type="text/javascript" src="/ossim/js/greybox.js"></script>
    
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/ossec_msg.php"></script>
        
    <script type="text/javascript">

        function load_tab1()
        {
            $('.num_line').hide();
            execute_action("status", "#ossc_result");
        }

        function load_tab2()
        {
            $('.num_line').show();
            execute_action("ossec_log", "#logs_result");
        }

        function load_tab3()
        {
            $('.num_line').show();
            execute_action("alerts_log", "#alerts_result");
        }


        function execute_action(action, div_load, extra)
        {
            show_loading_box('tabs', '<?php echo _('Processing action...')?>', '');

            var sensor_id = $('#sensors').val();
            var token     = Token.get_token('f_ossec_control');
            var data      = "action="+action+"&sensor_id="+sensor_id+"&token="+token;

            if (action == 'ossec_log' || action == 'alerts_log')
            {
                var num_lines = $('#oss_num_line').val();
                data += "&num_lines="+num_lines;
            }

            $.ajax({
                type: "POST",
                url: "data/ossec_control/ajax/actions.php",
                data: data,
                dataType: 'json',
                error: function(data){

                    hide_loading_box();

                    //Check expired session
                    var session = new Session(data, '');
                    
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }

                    var html = "<div style='margin:auto; padding: 30px 0px;'>"+notify_error(ossec_msg['unknown_error'])+"</div>";

                    var width = $(div_load).width();
                        width = (isNaN(width)) ? 785 : parseInt(width)-45;
                        width = width+'px';

                    $(div_load).html("<div style='padding: 5px 10px 10px 10px; width:"+width+"'>"+html+"</div>");
                },
                success: function(data){

                    hide_loading_box();

                    //Check expired session
                    var session = new Session(data, '');
                    
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }

                    var cnd_1 = (typeof(data) == 'undefined' || data == null);
                    var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status == 'error');

                    if (cnd_1 || cnd_2)
                    {
                        var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data
                        var html = "<div style='margin:auto; padding: 30px 0px;'>"+notify_error(error_msg)+"</div>";
                    }
                    else
                    {
                        var status = (data.data).split("###");

                        var html   = data.data;

                        if (div_load == "#ossc_result")
                        {
                            $('#ossc_buttons_actions').html(status[1]);
                            bind_action('#cont_system_action');
                            bind_action('#cont_cs_action');
                            bind_action('#cont_al_action');
                            bind_action('#cont_dbg_action');
                            
                            html = status[0];
                        }
                    }

                    var width = $(div_load).width();
                        width = (isNaN(width)) ? 785 : parseInt(width)-45;
                        width = width+'px';

                    $(div_load).html("<div style='padding: 5px 10px 10px 10px; width:"+width+"'>"+html+"</div>");
                }
            });
        }

        function bind_action(id)
        {
            $(id+ ' input').off();

            switch (id)
            {
                case "#cont_cs_action":
                    $(id+ ' input').click(function() {
                        var action = ($(this).val() == "<?php echo _('Enable')?>") ? "enable_cs" : "disable_cs";
                        execute_action(action, "#ossc_result");
                    });
                break;

                case "#cont_al_action":
                    $(id+ ' input').click(function() {
                        var action = ($(this).val() == "<?php echo _('Enable')?>") ? "enable_al" : "disable_al";
                        execute_action(action, "#ossc_result");
                    });
                break;

                case "#cont_dbg_action":
                    $(id+ ' input').click(function() {
                        var action = ($(this).val() == "<?php echo _('Enable')?>") ? "enable_dbg" : "disable_dbg";
                        execute_action(action, "#ossc_result");
                    });
                break;

                case "#cont_system_action":

                    $(id+ ' input').click(function() {

                        var action = $(this).val();
                        
                        if (action == "<?php echo _("Start")?>")
                        {
                            var action = "Start";
                        }
                        else if (action == "<?php echo _("Restart")?>")
                        {
                            var action = "Restart";
                        }
                        else
                        {
                            if (action == "<?php echo _("Restart")?>")
                            {
                                var action = "Stop";
                            }
                        }

                        execute_action(action, "#ossc_result");
                    });
                break;
            }
        }



        $(document).ready(function() {
            
            //Tabs
            $("ul.oss_tabs li:first").addClass("active");
                            
            $('#oss_num_line').change(function(){
                var active = $('.oss_tabs .active').attr('id');
                                                
                if (active == 'litem_tab2')
                {
                    load_tab2();
                }
                else if (active == 'litem_tab3')
                {
                    load_tab3();
                }
            });

            $("ul.oss_tabs li").click(function(event) {
                event.preventDefault(); 
                show_tab_content(this); 
            });
            
            $("#link_tab1, #refresh").click(function(event) { load_tab1();});
            
            $("#link_tab2").click(function(event) { load_tab2(); });
            
            $("#link_tab3").click(function(event) { load_tab3();});
            
            $("#show_actions").click(function(event) { 
                event.preventDefault();

                if ($("#show_actions").hasClass('hide'))
                {
                    $("#show_actions").removeClass();
                    $("#show_actions").addClass('show');
                    $("#show_actions span").html('<?php echo _('Hide Actions')?>');

                    $('#table_ossc_actions').show();
                    $('#ossc_actions').css('height', '100px');
                }
                else
                {
                    $("#show_actions").removeClass();
                    $("#show_actions").addClass('hide');
                    $("#show_actions span").html('<?php echo _('Show Actions')?>');
                    
                    $('#table_ossc_actions').hide();
                    $('#ossc_actions').css('height', '1px');
                }
            });
            
            $('#sensors').change(function(){
                
                var active = $('.oss_tabs .active').attr('id');
                    
                if (active == 'litem_tab1')
                {
                    load_tab1();
                }
                else if (active == 'litem_tab2')
                {
                    load_tab2();
                }
                else if (active == 'litem_tab3')
                {
                    load_tab3();
                }
            });

            bind_action('#cont_cs_action');
            bind_action('#cont_al_action');
            bind_action('#cont_dbg_action');
            bind_action('#cont_system_action');
            
            $('#sensors').removeAttr('disabled');
            show_select();
        });
    
    </script>
</head>

<body>

<?php
include_once '../local_menu.php';
?>

    <div id='container_center'>

        <table id='tab_menu'>
            <tr>
                <td id='oss_mcontainer'>
                    <ul class='oss_tabs'>
                        <li id='litem_tab1'><a href="#tab1" id='link_tab1'><?php echo _('Ossec Control')?></a></li>
                        <li id='litem_tab2'><a href="#tab2" id='link_tab2'><?php echo _('Ossec Log')?></a></li>
                        <li id='litem_tab3'><a href="#tab3" id='link_tab3'><?php echo _('Alerts Log')?></a></li>
                    </ul>
                </td>
            </tr>
        </table>

        <table id='tab_container' class='oss_control'>
            <tr>
                <td>
                    <div id='tabs'>
                        <div class='c_filter_and_actions'>

                            <?php $s_class = (Session::is_pro() && count($s_data['sensors']) > 1) ? 's_show' : 's_hide';?>

                            <div class='c_filter'>
                                <label for='sensors'><?php echo _('Select sensor')?>:</label>
                                <select id='sensors' name='sensors' class='vfield <?php echo $s_class?>' disabled="disabled">
                                    <?php echo $sensor_opt?>
                                </select>
                            </div>
                        </div>


                        <div class='cont_num_line'>
                            <div class='num_line'>
                                <label for='oss_num_line'><?php echo _('View')?>:</label>
                                <select name='oss_num_line' id='oss_num_line'>
                                    <option value='50' selected='selected'>50</option>
                                    <option value='100'>100</option>
                                    <option value='250'>250</option>
                                    <option value='500'>500</option>
                                    <option value='5000'>5000</option>
                                </select>
                            </div>
                        </div>

                        <div id='tab1' class='generic_tab tab_content'>

                            <div id='ossc_actions'>
                                <div class='l_ossc_actions'>
                                    <img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/>
                                    <a id='show_actions' class='show'><span class='bold'><?php echo _('Hide actions')?></span></a>
                                </div>

                                <table id='table_ossc_actions'>
                                    <tr>
                                        <th class='headerpr' colspan='4'><?php echo _('Actions')?></th>
                                    </tr>

                                    <tr id='ossc_buttons_actions'>
                                        <td id='cont_cs_action'><?php echo $response['buttons']['syslog']?></td>
                                        <td id='cont_al_action'><?php echo $response['buttons']['agentless']?></td>
                                        <td id='cont_dbg_action'><?php echo $response['buttons']['debug']?></td>
                                        <td id='cont_system_action'><?php echo $response['buttons']['system']?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class='headerpr_no_bborder' id='ossec_header'>
                                <div class='r_ossc_actions'>
                                    <a id='refresh'>
                                        <img align='top' border="0" src="<?php echo OSSIM_IMG_PATH?>/refresh2.png" title='<?php echo _('Refresh Output')?>'/>
                                    </a>
                                </div>
                                <div class='c_ossc_actions'><?php echo _('Ossec Output')?></div>
                            </div>

                            <div id='ossc_result' class='div_pre'>
                                <div style='padding: 5px 10px 10px 10px;'>
                                    <?php echo $response['stdout'];?>
                                </div>
                            </div>
                        
                        </div>

                        <div id='tab2' class='generic_tab tab_content' style='display:none;'>
                            <div class='headerpr_no_bborder' id='logs_header'><?php echo _('Ossec Log');?></div>
                            <div id='logs_result' class='log div_pre'></div>
                        </div>

                        <div id='tab3' class='generic_tab tab_content' style='display:none;'>
                            <div class='headerpr_no_bborder' id='alerts_header'><?php echo _('Alerts log');?></div>
                            <div id='alerts_result' class='log div_pre'></div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class='bottom_link'>
                    <a href='<?php echo $link_siem?>' target='main'>
                        <span><?php echo _("If you're looking for the HIDS events please go to Analysis -> SIEM")?></span>
                    </a>
                </td>
            </tr>
        </table>
        
    </div>
</body>
</html>