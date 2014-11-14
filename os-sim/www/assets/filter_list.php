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

Asset_filter_list::create_filter_copy();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _('AlienVault USM'); ?> </title>
        <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
    
        <?php
    
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css',                 'def_path' => TRUE),
                array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
                array('src' => 'tipTip.css',                    'def_path' => TRUE),
                array('src' => 'jquery.switch.css',             'def_path' => TRUE),
                array('src' => 'simplePagination.css',          'def_path' => TRUE),
                array('src' => '/assets/asset_search.css',      'def_path' => TRUE),
                array('src' => '/assets/asset_filters.css',     'def_path' => TRUE)
            );
    
            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',                         'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',                      'def_path' => TRUE),
                array('src' => 'utils.js',                              'def_path' => TRUE),
                array('src' => 'jquery.tipTip.js',                    	'def_path' => TRUE),
                array('src' => 'notification.js',                       'def_path' => TRUE),
                array('src' => 'token.js',                              'def_path' => TRUE),
                array('src' => 'jquery.placeholder.js',                 'def_path' => TRUE),
                array('src' => 'jquery.switch.js',                      'def_path' => TRUE),
                array('src' => 'jquery.simplePagination.js',            'def_path' => TRUE),
                array('src' => '/assets/js/assets_filters.js.php',      'def_path' => FALSE)
            );
    
            Util::print_include_files($_files, 'js');
    
        ?>
        
        
        <script type='text/javascript'>
            
            var __filters = 
            {
                "7" : {"filter": "network",     "name": "<?php echo _('Network') ?>",     "full_search": false },
                "8" : {"filter": "device_type", "name": "<?php echo _('Device Type') ?>", "full_search": false },
                "14": {"filter": "sensor",      "name": "<?php echo _('Sensor') ?>",      "full_search": false },
                "9" : {"filter": "software",    "name": "<?php echo _('Software') ?>",    "full_search": true },
                "10": {"filter": "service",     "name": "<?php echo _('Service') ?>",     "full_search": false },
                "13": {"filter": "location",    "name": "<?php echo _('Location') ?>",    "full_search": false }
            };
            
            var __filter_id    = 7;
            
            var __search_all   = false;
            
            var __total_items  = 0;
            var __search       = '';
            
            var __delete_tags  = {};
            var __create_tags  = {};
            
            var __timeout_fil  = false;
            
            $(document).ready(function()
            {                           
                load_filters_handlers();

			});

        </script>
        
    </head>
    
    <body>    
        
        <div id="av_notif_lb"></div>
        
        <div id='tab_filter_list'>
            <ul>
                <li>
                    <a href="#tab_filter_container" data-filter_id='7'>
                        <?php echo _('Network') ?>
                    </a>
                </li>
                <li>
                    <a href="#tab_filter_container" data-filter_id='9'>
                        <?php echo _('Software') ?>
                    </a>
                </li>
                <li>
                    <a href="#tab_filter_container" data-filter_id='14'>
                        <?php echo _('Sensor') ?>
                    </a>
                </li>
                <li>
                    <a href="#tab_filter_container" data-filter_id='8'>
                        <?php echo _('Device Type') ?>
                    </a>
                </li>
                <li>
                    <a href="#tab_filter_container" data-filter_id='10'>
                        <?php echo _('Ports/Services') ?>
                    </a>
                </li>  
                <li>
                    <a href="#tab_filter_container" data-filter_id='13'>
                        <?php echo _('Location') ?>
                    </a>
                </li> 
            </ul>
            

            <div id='tab_filter_container'>
            
                <div id="filter_search">
                    <input id='filter_search_input' type='text' placeholder="<?php echo _('Search') ?>">
                    <div id="clear_search_input"></div> 
                </div>
                
                <div id="full_search">
                    <div id='s-toggle' class="toggle on" data-label="<?php echo _('Inventory') ?>"></div>
                </div>
                
                <div class='clear_layer'></div>
                
                <div id="filter_list">
                    <div id='filter_list_msg'></div>
                    <div id='filter_loading_layer'>
                        <div>
                            <?php echo _('Loading Data') ?>
                            <img src='/ossim/pixmaps/loading.gif' />
                        </div>
                    </div>
                    
                    <div id='column_1' class='filter_column'></div>
                    
                    <div id='column_2' class='filter_column'></div>
                    
                    <div id='column_3' class='filter_column'></div>
                    
                    <div class='clear_layer'></div>
                    
                </div>
                
                <div id="filter_paginator_section">
                    <ul id="filter_paginator"></ul>
                </div>
                
            </div>
        
        </div>
        
        <div id='filer_actions'>
            <a href='javascript:;' id='f_cancel' class='button av_b_secondary'><?php echo _('Cancel') ?></a>
            <a href='javascript:;' id='f_apply'  class='button av_b_main'><?php echo _('Apply') ?></a>
        </div>
        
    </body>
    
</html>