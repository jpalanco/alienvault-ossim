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

Session::logcheck("analysis-menu", "ControlPanelAlarms");


$db   = new ossim_db(TRUE);
$conn = $db->connect();

$mssp = Session::show_entities();

list($count_tags, $tags) = Tag::get_tags_by_type($conn, 'alarm');
$intents      = Alarm::get_intents($conn);
$sensors      = Av_sensor::get_list($conn, array(), FALSE, TRUE);
$_groups_data = Asset_group::get_list($conn);
$asset_groups = $_groups_data[0];


//Autocomplete
$autocomplete_keys = array('hosts');
$hosts_str         = Autocomplete::get_autocomplete($conn, $autocomplete_keys);

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'datepicker.css',                'def_path' => TRUE),
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',       'def_path' => TRUE),
            array('src' => 'tipTip.css',                    'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',         'def_path' => TRUE),
            array('src' => '/alarm/console.css',            'def_path' => TRUE),
            array('src' => 'av_tags.css',                   'def_path' => TRUE),
            array('src' => 'ui.slider.extras.css',          'def_path' => TRUE),
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                         'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                      'def_path' => TRUE),
            array('src' => 'utils.js',                              'def_path' => TRUE),
            array('src' => 'notification.js',                       'def_path' => TRUE),
            array('src' => 'token.js',                              'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',                 'def_path' => TRUE),
            array('src' => 'greybox.js',                            'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                  'def_path' => TRUE),
            array('src' => 'jquery.dataTables.plugins.js',          'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',           'def_path' => TRUE),
            array('src' => 'jquery.placeholder.js',                 'def_path' => TRUE),
            array('src' => '/alarm/js/alarm_group_list.js.php',     'def_path' => FALSE),
            array('src' => 'selectToUISlider.jQuery.js',            'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');

        require '../host_report_menu.php';

    ?>

	<script type="text/javascript">

        $(document).ready(function()
        {
            $('#arangeA, #arangeB').selectToUISlider({
                tooltip: false,
                labelSrc: 'text',
                sliderOptions: {
                    stop: function(event, ui) {
                        reload_alarm_groups();
                    }
                }
            });

            setGroupID('<?php echo $_GET['backlog']?>');
            load_alarm_list();
        });

	</script>
</head>

<body>

    <div id='bg_container'></div>

    <div id='ag_notif'></div>


    <div class="filters uppercase">
        <img id='search_arrow' src='/ossim/pixmaps/arrow_right.png' />
        <a href='javascript:;' onclick="toggle_filters()"><?php echo _('Search and filter') ?></a>
    </div>

    <div class='clear_layer'></div>

    <div id='alarm_group_params'>
        <form id='filter_group_alarms'>

            <div class='p_column'>

                <label for='group_type'><?php echo _('Group By')?></label>
                <select id="group_type" class='ag_param' name="group_type" >
    			    <option value='all'><?php echo _('Alarm Name, Src/Dst, Date') ?></option>
    			    <option value='namedate'><?php echo _('Alarm Name, Date') ?></option>
    			    <option value='name' selected><?php echo _('Alarm Name') ?></option>
    			    <option value='similar'><?php echo _('Similar Alarms') ?></option>
    			</select>

                <label for='alarm_name'><?php echo _('Alarm name') ?></label>
                <input type="search" id="alarm_name" class='ag_param' name="alarm_name" value="">

                <label for='src_ip'><?php echo _('Source IP Address') ?></label>
                <input type="search" id="src_ip" class='ag_param' name="src_ip" value=""/>

                <label for='dst_ip'><?php echo _('Destination IP Address') ?></label>
                <input type='search' id='dst_ip' class='ag_param' name='dst_ip' value=''>

                <label for='asset_group'><?php echo _('Asset Group') ?></label>
                <select id='asset_group' class='ag_param' name='asset_group'>
                    <option value=''><?php echo (count($asset_groups) > 0) ? '' : '- '._('No groups found').' -' ?></option>
                    <?php
                    foreach ($asset_groups as $group_id => $group_obj)
                    {
                        ?>
                        <option value='<?php echo $group_id ?>'><?php echo $group_obj->get_name() ?></option>
                        <?php
                    }
                    ?>
                </select>

                <label><?php echo _('Date') ?></label>
                <div class="datepicker_range">
                    <div class='calendar_from'>
                        <div class='calendar'>
                            <input name='date_from' id='date_from' class='date_filter ag_param' type="input" value="<?php echo $date_from ?>">
                        </div>
                    </div>
                    <div class='calendar_separator'>
                        -
                    </div>
                    <div class='calendar_to'>
                        <div class='calendar'>
                            <input name='date_to' id='date_to' class='date_filter ag_param' type="input" value="<?php echo $date_to ?>">
                        </div>
                    </div>
                </div>

            </div>

            <div class='p_column'>

                <label for='sensor_query'><?php echo _('Sensor')?></label>
                <select name="sensor_query" class='ag_param' id="sensor_query">
    				<option value=""></option>
    				<?php
    				foreach ($sensors[0] as $_sensor_id => $_sensor)
    				{
    					echo '<option value="'. $_sensor_id .'">'. $_sensor['name'] .' (' . $_sensor['ip'] . ')</option>';
    				}
    				?>
    			</select>

                <label for='intent'><?php echo _('Intent') ?></label>
                <select id="intent" class='ag_param' name="intent">
                    <option value="0"></option>
                    <?php
                    foreach ($intents as $kingdom_id => $kingdom_name)
                    {
                        echo '<option value="'.$kingdom_id.'" '.$selected.'>'.Util::htmlentities($kingdom_name).'</option>';
                    }
                    ?>
                </select>

                <label for='directive_id'><?php echo _('Directive ID') ?></label>
                <input type="search" id="directive_id" class='ag_param' name="directive_id" value="">

                <!--<label for='num_alarms_page'><?php echo _('Num. alarm groups per page') ?></label>
                <input type="search" id="num_alarms_page" name="num_alarms_page" value="">-->

                <label for='num_events_op'><?php echo _('Number of events in alarm') ?></label>
                <select id='num_events_op' class='ag_param alarms_op' name='num_events_op'>
                    <option value="less"><=</option>
                    <option value="more">>=</option>
                </select>
                <input type='search' class='ag_param alarms_op_value' id='num_events' name='num_events' value=''>
                <label><?php echo _('Risk level in alarms')?></label>
                <div id="asset_value_slider" class="filter_left_slider">
                <?php
                $risks = array(
                    _("Low"),_("Medium"),_("High")
                );
                $risk_selected = function($risk,$key,$risk_selected) {
                    $selected = $key == $risk_selected ? "selected='selected'" : "";
                    echo "<option value='$key' $selected>"._($risk)."</option>";
                };
                ?>
                <select class="filter_range hidden" id="arangeA" name="min_risk">
                    <?php array_walk($risks,$risk_selected,0);?>
                </select>
                <select class="filter_range hidden" id="arangeB" name="vmax_risk">
                    <?php array_walk($risks,$risk_selected,2);?>
                </select>
                </div>


                <label for='tag'><?php echo _('Label') ?></label>
                <select id='tag' class='ag_param' name='tag'>
                    <option value=''></option>
                    <?php
                    foreach ($tags as $t)
                    {
                        echo '<option value="'. $t->get_id() .'">'. $t->get_name() .'</option>';
                    }
                    ?>
                </select>
                
            </div>

            <div class='p_column'>

                <label for='show_options'><?php echo _('Show') ?></label>
                <select id="show_options" class='ag_param' name="show_options">
    				<option value="1"><?php echo _('All Groups') ?></option>
    				<option value="2"><?php echo _('My Groups') ?></option>
    				<option value="3"><?php echo _('Groups Without Owner') ?></option>
    				<option value="4"><?php echo _('My Groups & Without Owner') ?></option>
    			</select>

    			<label for='refresh_time'>
    			     <?php echo _('Autorefresh') ?>
    			     <a id='refresh_now' href="javascript:;" >[ <?php echo _('Refresh Now') ?> ]</a>
    			</label>
                <select id="refresh_time" name="refresh_time">
    				<option value="0"></option>
    				<option value="30000"><?php echo _('30 Seconds') ?></option>
    				<option value="60000"><?php echo _('1 Minute') ?></option>
    				<option value="180000"><?php echo _('3 Minutes') ?></option>
    				<option value="600000"><?php echo _('10 Minutes') ?></option>
    			</select>

    			<br/><br/>
                <input id='no_resolv' class='ag_param' type='checkbox' name='no_resolv' value='1'>
                <label class='line' for='a_resolve'><?php echo _('Do not resolve IP Names') ?></label><br/>

                <input id='hide_closed' class='ag_param' type='checkbox' name='hide_closed' value='1' checked >
                <label class='line' for='hide_closed'><?php echo _('Hide Closed Alarms') ?></label><br/>

            </div>

            <div class='clear_layer'></div>

        </form>
    </div>


    <div id='body_ga'>

        <div id='header_rga'>

            <button type="button" disabled id='b_close_selected'>
                <img src='style/img/unlock.png' height="14px" align="absmiddle" style="padding-right:8px"/>
                <span><?php echo _('Close selected') ?></span>
            </button>

            <?php
            if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
            {
                ?>

                <button type="button" disabled id='b_delete_selected' class='av_b_secondary'>
                    <img src='style/img/trash_fill.png' height="14px" align="absmiddle" style="padding-right:8px"/>
                    <span><?php echo _('Delete selected') ?></span>
                </button>
                <?php
            }
            ?>

        </div>


        <table id='t_grouped_alarms' class='table_data'>
            <thead>
                <tr>
                    <th><input type='checkbox' id='allcheck'/></th>
                    <th>
                        <img src='../pixmaps/plus.png' id='expandcollapse' border='0' title="<?php echo _('Expand/Collapse ALL')?>"/>
                    </th>
                    <th><?php echo _('Group')?></th>
                    <th><?php echo _('Date')?></th>
                    <th><?php echo _('Owner')?></th>
                    <th><?php echo _('Highest Risk')?></th>
                    <th><?php echo _('Description')?></th>
                    <th><?php echo _('Status')?></th>
                    <th><?php echo _('Action')?></th>
                </tr>
            </thead>

            <tbody><tr><td></td></tr></tbody>
        </table>


    </div>

    <div id='footer_ga'>
        <?php
        if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
        {
            ?>
            <a href='javascript:delete_all_groups();'><?php echo _('Delete ALL') ?></a>
            <?php
        }
        ?>
    </div>

    <div id='alarm_list_template'>
        <table class='table_data alarm_list'>
            <thead>
                <tr>
                    <th></th>
                    <th><?php echo _('Alarm Name') ?></th>
                    <th><?php echo _('Events') ?></th>
                    <th><?php echo _('Risk') ?></th>
                    <th><?php echo _('Duration') ?></th>
                    <th><?php echo _('OTX') ?></th>
                    <th><?php echo _('Source') ?></th>
                    <th><?php echo _('Destination') ?></th>
                    <th><?php echo _('Status') ?></th>
                    <th><?php echo _('Action') ?></th>
                </tr>
            </thead>

            <tbody><tr><td></td></tr></tbody>
        </table>
    </div>

</body>
</html>
