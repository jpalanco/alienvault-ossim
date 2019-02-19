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


function show_notif($msg, $type)
{
    $config_nt   = array(
        'content' => $msg,
        'options' => array (
            'type'          => $type,
            'cancel_button' => true
        ),
        'style'   => 'position:absolute;top:15px;left:0;right:0;margin:0 auto;width:400px;text-align:center;padding:1px 20px;z-index:999'
    );

    $nt = new Notification('nt_notif', $config_nt);
    $nt->show();
}


$back  = intval(GET('back'));
$notif = GET('notif');

ossim_valid($notif,    OSS_TEXT, OSS_NULLABLE,    'illegal:' . _('Notification Message'));

if (ossim_error())
{
    die(ossim_error());
}

//This params are validated in the class.
$filter_id   = GET("filter_id");
$filter_val  = GET("filter_value");
$cond_filter = $filter_id != '' && $filter_val != '';


$db   = new ossim_db();
$conn = $db->connect();

//Getting the object with the filters. Unserialize needed.
$filter_list = Filter_list::retrieve_filter_list_session();

//If we don't have any object, we create a new one.
if (!($filter_list instanceof Filter_asset_list) || !$back || $cond_filter)
{
    try
    {
        $filter_list = new Filter_asset_list($conn);

        if ($cond_filter)
        {
            $filter_list->modify_filter($filter_id, $filter_val, 0);
        }

        $filter_list->store_filter_list_session();
    }
    catch(Exception $e)
    {
        show_notif( $e->getMessage(), 'nf_error');
    }

}

$cont = $filter_list->get_num_filter_added();

if ($cont > 0)
{
    try
    {
        $filter_list->force_reload();
        $filter_list->apply_filter_search($conn);
    }
    catch(Exception $e)
    {
        show_notif( $e->getMessage(), 'nf_error');
    }

}

$filters    = $filter_list->get_filters();

//Alarms
$_alarms    = $filters[3]->is_active();

//Events
$_events    = $filters[4]->is_active();

//Asset Updated
$d_updated  = $filters[2]->get_val();
$d_u_from   = $filters[2]->get_from();
$d_u_to     = $filters[2]->get_to();

//Asset Created
$d_created  = $filters[1]->get_val();
$d_c_from   = $filters[1]->get_from();
$d_c_to     = $filters[1]->get_to();

//Vulnerabilities
$flag_vulns = $filters[5]->is_active();
$vulns_from = ($flag_vulns) ? $filters[5]->get_to() : 7;
$vulns_to   = ($flag_vulns) ? $filters[5]->get_from() : 1;

//Asset Value
$flag_av = $filters[6]->is_active();
$av_from = ($flag_av) ? $filters[6]->get_from() : 0;
$av_to   = ($flag_av) ? $filters[6]->get_to() : 5;

//Availability
$availability_status = $filters[17]->get_values();

//HIDS
$hids_status = $filters[26]->get_values();


// Getting Filter Legends
$f_legend = Filter_list::get_filter_legends();


// Getting Permissions:
$perm_add  = Session::can_i_create_assets();

$perms = array (
    'admin'           => Session::am_i_admin(),
    'create'          => $perm_add,
    'delete'          => $perm_add,
    'vulnerabilities' => Session::logcheck_bool('environment-menu', 'EventsVulnerabilitiesScan'),
    'alarms'          => Session::logcheck_bool('analysis-menu', 'ControlPanelAlarms'),
    'events'          => Session::logcheck_bool('analysis-menu', 'EventsForensics'),
    'availability'    => Session::logcheck_bool('environment-menu', 'MonitorsAvailability'),
    'deploy_agents'   => Session::logcheck_bool('environment-menu', 'EventsHidsConfig'),
    'nmap'            => Session::logcheck_bool('environment-menu', 'ToolsScan')
);

switch ($notif)
{
    case 'delete':
        $msg = _('Asset has been permanently deleted.');
        show_notif($msg, 'nf_success');

    break;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'tipTip.css',                    'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',         'def_path' => TRUE),
            array('src' => 'datepicker.css',                'def_path' => TRUE),
            array('src' => 'ui.slider.extras.css',          'def_path' => TRUE),
            array('src' => 'jquery.tag-it.css',             'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',           'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.css',           'def_path' => TRUE),
            array('src' => 'jquery.select.css',             'def_path' => TRUE),
            array('src' => 'av_tags.css',                   'def_path' => TRUE),
            array('src' => 'av_table.css',                  'def_path' => TRUE),
            array('src' => '/assets/asset_list_view.css',   'def_path' => TRUE),
            array('src' => 'assets/asset_indicators.css',   'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',                          'def_path' => TRUE),
            array('src' => 'utils.js',                                      'def_path' => TRUE),
            array('src' => 'notification.js',                               'def_path' => TRUE),
            array('src' => 'token.js',                                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                              'def_path' => TRUE),
            array('src' => 'greybox.js',                                    'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                          'def_path' => TRUE),
            array('src' => 'selectToUISlider.jQuery.js',                    'def_path' => TRUE),
            array('src' => 'jquery.tag-it.js',                              'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                            'def_path' => TRUE),
            array('src' => 'jquery.md5.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.placeholder.js',                         'def_path' => TRUE),
            array('src' => 'av_tags.js.php',                                'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.js',                            'def_path' => TRUE),
            array('src' => 'av_table.js.php',                               'def_path' => TRUE),
            array('src' => 'jquery.select.js',                              'def_path' => TRUE),
            array('src' => 'av_storage.js.php',                             'def_path' => TRUE),
            array('src' => '/av_asset/common/js/asset_list.js.php',         'def_path' => FALSE),
            array('src' => '/av_asset/common/js/asset_list_bind.js.php',    'def_path' => FALSE),
            array('src' => '/av_asset/common/js/asset_indicators.js.php',   'def_path' => FALSE),
            array('src' => '/av_asset/asset/js/list_asset.js.php',          'def_path' => FALSE),
        );

        Util::print_include_files($_files, 'js');

    ?>

    <script type='text/javascript'>

        var __asset_list = null;

        /**********  LIGHTBOX EVENTS  **********/

        function GB_onclose(url, params)
        {
            __asset_list.handle_close_lightbox(url, params);
        }


        function GB_onhide(url, params)
        {
            __asset_list.handle_hide_lightbox(url, params);
        }


        $(document).ready(function()
        {
            __asset_list = new av_asset_list(<?php echo json_encode($perms)?>);

            if (!<?php echo $back ?>)
            {
                __asset_list.clean_checked();
            }

            load_search_handlers(__asset_list);


            //DISABLING SLIDERS
            <?php
            if (!$flag_vulns)
            {
                echo "$('#vulns_slider .ui-slider').slider('disable');";
            }

            if (!$flag_av)
            {
                echo "$('#asset_value_slider .ui-slider').slider('disable');";
            }
            ?>
        });

    </script>


</head>

<body>
    <div id='main_container'>
        <div class="left_side">
            <div class='filter_left_section'>
                <input id='search_filter' data-bind="search-asset" class='input_search_filter' name='search_filter' type="search" value="" placeholder="<?php echo _('Search') ?>">
            </div>

            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <input id='filter_3' class='value_filter input_search_filter' data-id='3' type="checkbox" <?php echo ($_alarms) ? 'checked' : '' ?> ><?php echo _('Has Alarms') ?>
                </div>
            </div>


             <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <input id='filter_4' class='value_filter input_search_filter' data-id='4' type="checkbox" <?php echo ($_events) ? 'checked' : '' ?> ><?php echo _('Has Events') ?>
                </div>
            </div>


            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <input id='filter_5' class='input_search_filter' type="checkbox" <?php echo ($flag_vulns) ? 'checked' : '' ?> ><?php echo _('Vulnerabilities') ?>
                </div>

                <div id='vulns_slider' class='filter_left_slider'>
        			<select class='filter_range' id="vrangeA">
            			<?php
            			foreach ($f_legend['vulns'] as $v_id => $v_name)
            			{
                			$selected = ($vulns_from == $v_id) ? "selected='selected'" : '';

                			echo "<option value='$v_id' $selected>$v_name</option>";
            			}
            			?>
        			</select>
        			<select class='filter_range' id="vrangeB">
            			<?php
            			foreach ($f_legend['vulns'] as $v_id => $v_name)
            			{
                			$selected = ($vulns_to == $v_id) ? "selected='selected'" : '';

                			echo "<option value='$v_id' $selected>$v_name</option>";
            			}
            			?>
        			</select>
                </div>
            </div>

            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <input id='filter_6' class='input_search_filter' type="checkbox" <?php echo ($flag_av) ? 'checked' : '' ?>><?php echo _('Asset Value') ?>
                </div>
                <div id='asset_value_slider' class='filter_left_slider'>
        			<select class='filter_range' id="arangeA">
        				<?php
            			foreach ($f_legend['asset_value'] as $a_id => $a_name)
            			{
                			$selected = ($av_from == $a_id) ? "selected='selected'" : '';

                			echo "<option value='$a_id' $selected>$a_name</option>";
            			}
            			?>
        			</select>
        			<select class='filter_range' id="arangeB">
        				<?php
            			foreach ($f_legend['asset_value'] as $a_id => $a_name)
            			{
                			$selected = ($av_to == $a_id) ? "selected='selected'" : '';

                			echo "<option value='$a_id' $selected>$a_name</option>";
            			}
            			?>
        			</select>
                </div>

            </div>

            <br/>

            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <?php echo _('HIDS Status') ?>
                </div>
                <div id='filter_26' class='filter_left_content hids_status_input' data-filter='26'>
                    <?php
                    foreach ($f_legend['hids'] as $_id => $_name)
                    {
                        $checked = ($hids_status === $_id) ? 'checked' : '';
                        echo "<input class='input_search_filter' type='radio' name='hids_status' $checked value='$_id'><span>$_name</span></br>";
                    }
                    ?>
                </div>
            </div>


            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <?php echo _('Availability Status') ?>
                </div>
                <div id='filter_17' class='filter_left_content availability_status_input' data-filter='17'>
                    <?php
                    foreach ($f_legend['availability'] as $_id => $_name)
                    {
                        $checked = ($availability_status === $_id) ? 'checked' : '';
                        echo "<input class='input_search_filter' type='radio' name='availability_status' $checked value='$_id'><span>$_name</span></br>";
                    }
                    ?>
                </div>
            </div>


            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <?php echo _('Show Assets Added') ?>
                </div>
                <div id='filter_1' class='filter_left_content asset_date_input' data-filter='1'>
                    <?php
                    foreach ($f_legend['date_ranges'] as $d_id => $d_name)
                    {
                        $checked = ($d_created == $d_id) ? 'checked' : '';
                        echo "<input class='input_search_filter' type='radio' name='asset_added' $checked value='$d_id'><span>$d_name</span></br>";
                    }
                    ?>
                    <div class='asset_date_range datepicker_range' style="<?php echo ($d_created != 'range') ? 'display:none' : '' ?>">
                        <div class='calendar'>
                            <input id='date_from_1' data-filter='1' class='input_search_filter date_filter' type="input" value="<?php echo $d_c_from ?>">
                        </div>
                        <div class='calendar'>
                            <input id='date_to_1' data-filter='1' class='input_search_filter date_filter' type="input" value="<?php echo $d_c_to ?>">
                        </div>
                    </div>

                </div>
            </div>

            <div class='filter_left_section'>
                <div class='filter_left_title'>
                    <?php echo _('Last Updated') ?>
                </div>
                <div id='filter_2' class='filter_left_content asset_date_input' data-filter='2'>
                    <?php
                    foreach ($f_legend['date_ranges'] as $d_id => $d_name)
                    {
                        $checked = ($d_updated == $d_id) ? 'checked' : '';
                        echo "<input class='input_search_filter' type='radio' name='asset_updated' $checked value='$d_id'><span>$d_name</span></br>";
                    }
                    ?>
                    <div class='asset_date_range datepicker_range' style="<?php echo ($d_updated != 'range') ? 'display:none' : '' ?>">
                        <div class='calendar'>
                            <input id='date_from_2' data-filter='2' class='input_search_filter date_filter' type="input" value="<?php echo $d_u_from ?>">
                        </div>
                        <div class='calendar'>
                            <input id='date_to_2' data-filter='2' class='input_search_filter date_filter' type="input" value="<?php echo $d_u_to ?>">
                        </div>
                    </div>
                </div>
            </div>

            <br><br>

            <a href='javascript:;' class='fleft button av_b_secondary' data-bind='more-filters'>
                <?php echo _('More Filters') ?>
            </a>


        </div>


        <div class="content">

            <div id="asset_notif"></div>
            <div id='content_header'>

                <div id='asset_section_title'>
                    <?php echo _('Assets') ?>
                </div>

                <div id='action_add_buttons'>

                    <button id='button_add' class='button av_b_secondary' data-dropdown="#dropdown-add">
                        <?php echo _('Add Assets') ?> &nbsp;&#x25be;
                    </button>

                    <img id='export_selection' class='disabled tiptip' data-selection="avt_action" data-bind="export-selection" data-title="<?php echo _('Select assets to export') ?>" src="/ossim/pixmaps/download-big.png"/>

                </div>

                <div id='as_tags'>

                    <ul id='tags_filters'>
                    <?php
                        foreach ($filters as $f_id => $_filter)
                        {
                            if ($_filter->is_active())
                            {
                                $tag_params = $_filter->get_tag_params($conn);

                                foreach($tag_params as $tag_id => $tag_name)
                                {
                                    $tag_label  = Util::htmlentities($tag_name);
                                    $tag_class  = md5("label_$tag_id") . ' filter_' . $f_id;

                                    echo '<li class="'. $tag_class .'" data-info="'. $tag_id .'">'. $tag_label .'</li>';
                                }
                            }
                        }
                    ?>

                    </ul>

                    <div id='result_search'>

                        <div id='num_assets'>0</div>
                        <span><?php echo _('Assets') ?></span>

                    </div>

                    <div id='restart_search'>
                        <a href='javascript:;' data-bind="restart-search"><?php echo _('Clear All Filters') ?></a>
                    </div>

                    <div class='clear_layer'></div>

                </div>

                <div class='clear_layer'></div>

            </div>



            <div id='content_result'>

                <?php include AV_MAIN_ROOT_PATH . '/av_asset/common/templates/tpl_dt_assets.php' ?>

            </div>



        </div>

    </div>

    <div id="dropdown-add" data-bind="dropdown-add" class="dropdown dropdown-secondary dropdown-close dropdown-tip dropdown-anchor-right"></div>
    <div id="dropdown-actions" data-bind="dropdown-actions" class="dropdown dropdown-close dropdown-tip dropdown-anchor-right"></div>

</body>
</html>

<?php
$db->close();
