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


require_once dirname(__FILE__) . '/conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');

//Current sensor
$sensor_id = (!empty($_REQUEST['sensor_id'])) ? $_REQUEST['sensor_id'] : $_SESSION['ossec_sensor'];

ossim_valid($sensor_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _('Sensor'));

if (ossim_error())
{
    die(ossim_error());
}

if (!empty($sensor_id))
{
    $_SESSION['ossec_sensor'] = $sensor_id;
}


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

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <!-- Own styles: -->
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>

    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type='text/javascript' src="/ossim/js/codemirror/codemirror.js"></script>

    <!-- Dynatree libraries: -->
    <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.cookie.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="/ossim/js/greybox.js"></script>

    <link type="text/css" rel="stylesheet" href="/ossim/style/tree.css" />

    <!-- Autocomplete libraries: -->
    <script type="text/javascript" src="/ossim/js/jquery.autocomplete.pack.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.autocomplete.css"/>

    <!-- JQuery tipTip: -->
    <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>

    <!-- Elastic textarea: -->
    <script type="text/javascript" src="/ossim/js/jquery.elastic.source.js" charset="utf-8"></script>

    <!-- Own libraries: -->
    <script type="text/javascript" src="js/ossec_msg.php"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/rules.js"></script>
    <script type='text/javascript' src='/ossim/js/notification.js'></script>
    <script type='text/javascript' src='/ossim/js/token.js'></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>

    <script type='text/javascript'>

        //AutoComplete

        content_ac = ["var", "accuracy", "frequency", "group", "id", "ignore", "level", "maxsize", "name", "timeframe",
            "match","regex","decoded_as","category","srcip","dstip","user","program_name","hostname","time",
            "weekday", "url","if_sid","if_group","if_level","if_matched_sid","if_matched_group","if_matched_level",
            "same_source_ip","same_source_port","same_location","description","list"    
        ];

        content_ac.sort();

        var layer     = null;
        var nodetree  = null;
        var i         = 1;

        var editor    = null;
        var timer     = null;


        $(document).ready(function() {

            //Handlers
            $('.oss_tabs li').each(function(index) {
                $(this).click( function(){
                    var id = $(this).attr('id');
                
                    if (id.match(/tab1/))
                    {
                        load_tab1();
                    }
                    else
                    {
                        load_tab2('');
                    }
                })
            });

            load_interface('');
        });
    </script>
</head>

<body>

    <?php
    require '../local_menu.php';

    $s_class = (Session::is_pro() && count($s_data['sensors']) > 1) ? 's_show' : 's_hide';
    ?>

    <div class='c_filter_and_actions'>
        <div class='c_filter'>
            <label for='sensors'><?php echo _('Select sensor')?>:</label>
            <select id='sensors' name='sensors' class='disabled vfield <?php echo $s_class?>' disabled='disabled'>
                <?php echo $sensor_opt?>
            </select>
        </div>
    </div>

    <div id='container_center'>


        <table id='tab_menu'>
            <tr>
                <td id='oss_mcontainer'>
                    <ul class='oss_tabs'>
                        <li id='litem_tab1' class='active'><a href="#tab1" id='link_tab1'><?php echo _('Rules Files')?></a></li>
                        <li id='litem_tab2'><a href="#tab2" id='link_tab2'><?php echo _('Rule Editor')?></a></li>
                    </ul>
                </td>
            </tr>
        </table>

        <div id='container_c_info'><div id='c_info'></div></div>

        <div id='c_tabs'>
            <table id='tab_container'>
                <tr>
                    <td id='oss_clcontainer'></td>

                    <td id='oss_crcontainer'>

                        <div id="tabs">
                            <div id="tab1" class="tab_content"></div>
                            
                            <div id="tab2" class="tab_content" style='display:none;'></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class='noborder'></td>
                    <td class='noborder'>
                        <div class='notice'>
                            <div><span>(*) <?php echo _('You must restart Ossec for the changes to take effect')?></span></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>