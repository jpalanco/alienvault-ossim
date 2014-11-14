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


Session::useractive();

if (!Session::am_i_admin())
{
    Av_exception::throw_error(Av_exception::USER_ERROR, _('You do not have permissions to see this section'));
}


$wizard = Welcome_wizard::get_instance(); 

if(!$wizard instanceof Welcome_wizard)
{
     Av_exception::throw_error(Av_exception::USER_ERROR, 'There was an unexpected error');
}

//Getting the scan step to know if we have a scan running
$step = intval($wizard->get_step_data('scan_step'));

//Selected nets
$nets_selected = $wizard->get_step_data('scan_nets');
$nets_selected = (is_array($nets_selected)) ?  $nets_selected : array();

$n_ids = array_fill_keys(array_keys($nets_selected), 1);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _("AlienVault " . (Session::is_pro() ? "USM" : "OSSIM")); ?> </title>
        <link rel="Shortcut Icon" type="image/x-icon" href="/ossim/favicon.ico">
        <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
    
        <?php
    
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css?only_common=1',           'def_path' => TRUE),
                array('src' => 'home.css',                              'def_path' => TRUE),
                array('src' => 'jquery-ui.css',                         'def_path' => TRUE),
                array('src' => 'lightbox.css',                          'def_path' => TRUE),
                array('src' => 'jquery.dataTables.css',                 'def_path' => TRUE),

                array('src' => '/wizard/wizard.css',                    'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
                array('src' => 'utils.js',                                      'def_path' => TRUE),
                array('src' => 'notification.js',                               'def_path' => TRUE),
                array('src' => 'token.js',                                      'def_path' => TRUE),
                array('src' => 'jquery.placeholder.js',                         'def_path' => TRUE),
                array('src' => 'jquery.dataTables.js',                          'def_path' => TRUE),
                array('src' => 'jquery.dataTables.plugins.js',                  'def_path' => TRUE),
                array('src' => '/wizard/js/wizard_actions.js.php',              'def_path' => FALSE)
            );
    
            Util::print_include_files($_files, 'js');
    
        ?>
    
        <script type='text/javascript'>
            
            var __total_nets_selected  = <?php echo json_encode($n_ids, JSON_FORCE_OBJECT) ?>;
            var __reload_nets          = false;
            
            $(document).ready(function() 
            {

                $("#w_accordion").accordion(
                {
    				autoHeight: false,
    				//navigation: true,
    				collapsible: true,
    				//active: false,
    				create: function()
    				{
    					load_js_net_list_scan();
    				},
    				change: function(event, ui) 
    				{ 
    				    var id  = $(ui.newHeader).attr('id');						
    				    
    				    if (id == 'w_opt_csv')
    				    {
        				    __reload_nets = true;
    				    }
    				    else
    				    {
        				    if (__reload_nets)
        				    {
        				        try
                                {
                                    __dt_nets._fnAjaxUpdate();
                                }
                                catch(Err){}
                                
            				    __reload_nets = false;
        				    }
    				    }
						
    				}

    			});
                        
                
            });
            
        </script>
        
    </head>
    <body>
        
        <div id='wizard_notif' class='w_lb_notif'></div>
        
        <div id='net_list_container'>
            <div class='wizard_subtitle'>
                <?php echo _('The discovery scan will first ping your assets, then probe the services to identify operating system.  Add networks manually or import networks from a CSV, if you do not see the networks you would like to scan.') ?>
            </div>
             
            <div id='w_accordion'>
            
                <h3 id='w_opt_scan'><a href='#'><?php echo _('Scan Networks')?></a></h3>
                <div>
                    <div class='net_ranges_container'>
    
                        <div id='net_inputs'>
                        
                            <div class='wizard_subtitle'>
                                <?php echo _("Add Networks") ?>
                            </div>
                            
                            <input type='text' id='net_name' value='' placeholder="<?php echo _('Network Name') ?>">
                            <input type='text' id='net_cidr' value='' placeholder="<?php echo _('CIDR') ?>">
                            <input type='text' id='net_dscr' value='' placeholder="<?php echo _('Description') ?>">
                
                            <button id="add_net" disabled="disabled" class='add_item small av_b_secondary'>+ <?php echo _('Add')?></button>
                            
                            
                        </div>
                        
                        <table id='table_net_results' class='table_data item_results'>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo _('Network Name')?></th>
                                    <th><?php echo _('CIDR')?></th>
                                    <th><?php echo _('# of Possible Assets')?></th>
                                    <th><?php echo _('Description')?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td></td></tr>
                            </tbody>
                        </table>
                        
                    </div>
                
                </div>
            
            
            
                <h3 id='w_opt_csv'><a href='#'><?php echo _('Import From CSV')?></a></h3>
                <div id='csv_option' class='item_body'>
                
                    <iframe id="csv_iframe" src="/ossim/net/import_all_nets.php?import_type=welcome_wizard_nets" name="csv_iframe"></iframe>
               
                </div>
            
            </div>
        
        </div>

        
        <div id='GB_action_buttons'>
        
            <button id='gb_b_cancel' class='av_b_secondary'>
                <?php echo _('Cancel') ?>
            </button>
            
            <button id='gb_b_apply' disabled>
                <?php echo _('Scan Now') ?>
            </button>
            
        </div>
        
        
    </body> 
</html>

