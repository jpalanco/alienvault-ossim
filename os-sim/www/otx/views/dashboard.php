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

Session::logcheck("dashboard-menu", "IPReputation");


Reputation::flush_raputation_from_session();

$perms = array(
    'admin'  => Session::am_i_admin(),
    'alarms' => Session::logcheck_bool('analysis-menu', 'ControlPanelAlarms'),
    'events' => Session::logcheck_bool('analysis-menu', 'EventsForensics'),
    'pro'    => Session::is_pro()
);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
    <title><?php echo _('Open Threat Exchange Dashboard') ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => '/js/jqplot/jquery.jqplot.css',  'def_path' => FALSE),
            array('src' => 'tipTip.css',                    'def_path' => TRUE),
            array('src' => 'av_common.css',                 'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                         'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                      'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',                  'def_path' => TRUE),
            array('src' => 'notification.js',                       'def_path' => TRUE),
            array('src' => 'utils.js',                              'def_path' => TRUE),
            array('src' => 'av_map.js.php',                         'def_path' => TRUE),
            array('src' => 'markerclusterer.js',                    'def_path' => TRUE),
            array('src' => 'jqplot/jquery.jqplot.min.js',           'def_path' => TRUE),
            array('src' => 'jqplot/plugins/jqplot.pieRenderer.js',  'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',                 'def_path' => TRUE),
            array('src' => 'Chart.js',                              'def_path' => TRUE),
            array('src' => 'jquery.graphup.js',                     'def_path' => TRUE),
            array('src' => 'av_dot_chart.js.php',                   'def_path' => TRUE),
            array('src' => '/otx/js/otx_dashboard.js.php',          'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');  
    ?>

    <script type="text/javascript">
    
        $(document).ready(function() 
        {
            otx_summary_dashboard(<?php echo json_encode($perms) ?>);
        });
    
    </script>
</head>
<body>
    
    <div id='otx_notif'></div>
    <div id='otx_dashboard_wrapper'>
        
        <div class='header_title p_chart'>
            <div class='pulse_summary_col'><?php echo _('Pulses Subscribed') ?></div>
            <div class='pulse_summary_col'><?php echo _('Indicators') ?></div>
            <div class='pulse_summary_col'><?php echo _('Last Updated') ?></div>
            <div class='pulse_summary_col'><?php echo _('Number of Alarms') ?></div>
            <div class='pulse_summary_col'><?php echo _('Number of Events') ?></div>
            <div class='clear_layer'></div>
        </div>
        <div id='pulse_summary' class='data_body' data-bind='p-summary'>
            <div class='pulse_summary_col av_link' data-bind="p-pulses" data-link='otx-config'></div>
            <div class='pulse_summary_col av_link' data-bind="p-iocs" data-link='otx-config'></div>
            <div class='pulse_summary_col av_link' data-bind="p-update-date" data-link='otx-config'></div>
            <div class='pulse_summary_col av_link' data-bind="p-alarms" data-link='otx-alarms'></div>
            <div class='pulse_summary_col av_link' data-bind="p-events" data-link='otx-events'></div>
            <div class='clear_layer'></div>
        </div>
        
        
        <div class='header_title p_chart'>
            <?php echo _('Events from Most Active OTX Pulses') ?>
        </div>
        
        <div id='pulse_top' class='data_body p_chart' data-bind='p-top-pulses'>
            
              <div id='chart_top'></div>
              
        </div>
        
        <div class='header_title p_chart'>
            <?php echo _('Events from All OTX Pulses') ?>
        </div>
        
        <div id='pulse_trend' class='data_body p_chart' data-bind='p-trend-pulses'>
            <div id="chart_trend_wrap">
                <div>
                    <canvas id="chart_trend" height="200" width="600"></canvas>
                </div>
            </div>
        </div>
        
        <div class='header_title'>
            <?php echo _('IP Reputation') ?>
            
            <div id='ipr_options' data-bind='ipr-options'>
                
                <label for="rep_type"><?php echo _("Source:");?></label>
                <select id="rep_type" data-bind="rep-type">
                    <option value="1"><?php echo _("SIEM Events") ?></option>
                    <option value="0"><?php echo _("Reputation Data") ?></option>
                </select> 
                
                &nbsp;
                
                <label for="act_filter"><?php echo _("Filter by Activity:");?></label>
                <select id="act_filter" data-bind="act-filter"></select>
                
            </div>
        </div>
        <div id='ipr_data' class='data_body' data-bind='ipr-data'>
    
            <div id="ipr_map"></div>
            
            <div id='ipr_summary'>
                
                <div class='column column_side'>
                    <div class='s_header'>
                        <?php echo _('GENERAL STATISTICS') ?>
                    </div>
                    <table id='r_summary' class='table_data' data-bind="r-summary"></table>
                </div>
                <div class='column column_center'>
                    <div class='s_header'>
                        <?php echo _('MALICIOUS IPS BY ACTIVITY') ?>
                    </div>
                    <div id='r_chart' data-bind="r-chart"></div>
                </div>
                <div class='column column_side'>
                    <div class='s_header'>
                        <?php echo _('TOP 10 COUNTRIES') ?>
                    </div>
                    <table id='r_top' class='table_data' data-bind="r-top"></table>
                </div>
                
                <div class='clear_layer'></div>
            </div>
                
        </div>  

    </div>  
</body>
</html>