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


if (!Session::am_i_admin())
{
     $config_nt = array(
        'content' => _("You do not have permission to see this section"),
        'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => false
        ),
        'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
    ); 
                    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    
    die();
}

$_system_list_data = Av_center::get_avc_list_from_api(TRUE);
$default_system_id = strtolower(Util::get_default_uuid());

if ($_system_list_data['status'] != 'success')
{
    // Exception
}

$system_list = $_system_list_data['data'];


$checking_msg = _('Checking for backups in progress');


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
            array('src' => 'av_common.css',                                            'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                                            'def_path' => TRUE),
            array('src' => 'tipTip.css',                                               'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',                                    'def_path' => TRUE),
            array('src' => '/configuration/administration/backups_configuration.css',  'def_path' => TRUE)
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
            array('src' => 'jquery.md5.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.placeholder.js',                         'def_path' => TRUE),
            array('src' => '/av_backup/js/backup.js.php',                   'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>

    <script type='text/javascript'>
        
        $(document).ready(function() 
        {
            // Load dataTable
            load_backup_list();

            // Check if there's any backup job (Not implemented yet in the API)
            backup_status();
            
            // Backup Now button
            $('#backup_now_button').click(function()
            {
                launch_backup();
            });

            // Delete button
            $('#delete_selection').click(function()
            {
                if ($('.backup_check:checked').length > 0)
                {
                    var keys = {"yes": "<?php echo Util::js_entities(_('Yes')) ?>","no": "<?php echo Util::js_entities(_('No')) ?>"};
                    var msg  = "<?php echo _('Are you sure you want to delete the selected backup files') ?>?";
                    av_confirm(msg, keys).done(function()
                    {
                        delete_backups();
                    });
                }
            });

            // Check All
            $('[data-bind="chk-all-backups"]').on('change', function()
            {
                var status = $('[data-bind="chk-all-backups"]').prop('checked');
                $('.backup_check').prop('checked', status).trigger('change');
            });
            
            // Search by ENTER key
            $("#search_filter").keyup(function (e) 
            {
                if (e.keyCode == 13) 
                {
                    backup_datatable.fnDraw();

                    __current_search_value = $('#search_filter').val();
                }
            });

            // Clean search
            $("#search_filter").on('input', function()
            {
                if ($(this).val() == '' && __current_search_value != '')
                {
                    backup_datatable.fnDraw();

                    __current_search_value = '';
                }
            });

            // System Selector handle
            $('#system_id').on('change', function()
            {
                if (typeof(__job_interval) == 'number')
                {
                    clearInterval(__job_interval);
                }
                
                backup_datatable.fnDraw();

                $('#action_info_launch_backup').html("<img src='/ossim/pixmaps/loading3.gif'/> <?php echo $checking_msg ?>");
                $('#backup_now_button').prop('disabled', true);
                
                backup_status();
            });

            // ToolTips
            $(".info").tipTip({maxWidth: '380px'});
            
        });
        // End of document.ready
        
    </script>
    

</head>

<body>

<?php
    //Local menu             
    include_once '../local_menu.php';
    session_write_close();
?>

    <!-- Download form launcher -->
    <form id='download_form' method='post' action='<?php echo AV_MAIN_PATH . "/av_backup/controllers/download_backup.php" ?>'>
    
        <input type='hidden' name='system_id'   id='download_form_system_id'   value=''/>
        <input type='hidden' name='token'       id='download_form_token'       value=''/>
        <input type='hidden' name='backup_file' id='download_form_backup_file' value=''/>
        <input type='hidden' name='job_id'      id='download_form_job_id'      value=''/>
    
    </form>

    
    
    
    <div id='main_container'>
    
        <div class="left_side">
            <div class='filter_left_section'>
                <input id='search_filter' data-bind="search-asset" class='input_search_filter' name='search_filter' type="search" value="" placeholder="<?php echo _('Search') ?>" />
            </div>
 
            
            <br/>
            

            
        </div>
        
        
        <div class="content">
            
            <div id="backup_notif"></div>

            
            <div id='backup_section_title'>
                <?php echo _('AlienVault Configuration Backups') ?>
            </div>
            
            
            <div class='backup_system_selection'>
                <?php echo _('Show Backups for') ?>:
                <select name='system_id' id='system_id'>
                    <?php
                    foreach($system_list as $system_id => $system_data)
                    {
                        $selected = ($system_id == $default_system_id) ? ' selected' : '';
                        ?>
                        <option value="<?php echo $system_id ?>" <?php echo $selected ?>>
                            <?php echo $system_data['hostname'].' ['.$system_data['admin_ip'].']' ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
            
            
            <div id='content_result'>
                
                <div id='table_title'>
                    
                    <?php echo _('Backup History') ?>
    
                </div>
                
                
                
                <div id='action_buttons'>
                
                    <div id='action_info_delete_backup'></div>
                    
                    <img id='delete_selection' class='img_action disabled info' title='<?php echo _('Delete selected backups') ?>' src="/ossim/pixmaps/delete.png"/>
    
                </div>
            
                
                <table id='backup_dt' class='noborder table_data' width="100%" align="center">
                    <thead>
                        <tr>
                            <th>
                                <input type='checkbox' data-bind='chk-all-backups' />  
                            </th>
                            
                            <th>
                                <?php echo _('System') ?>
                            </th>
                            
                            <th>
                                <?php echo _('Date') ?>
                            </th>
                            
                            <th>
                                <?php echo _('Backup') ?>
                            </th>
                            
                            <th>
                                <?php echo _('Type') ?>
                            </th>
                            
                            <th>
                                <?php echo _('Version') ?>
                            </th>
                            
                            <th>
                                <?php echo _('Size') ?>
                            </th>
                            
                            <th>
                                <?php echo _('Download') ?>
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Do not delete, this is to show the first "Loading" message -->
                        <tr><td></td></tr>
                    </tbody>
                    
                </table>

            </div>
            
            
            <div id='content_header'>
                
                <div id='actions_title'>
                    <?php echo _('Backup Actions') ?>
                </div>
                
                <div id='form_buttons'>
                    
                    <div id='form_button_run'>
                        <input type='button' id='backup_now_button' class='small' value='<?php echo _('Run Backup Now') ?>' disabled/>
                    </div>
                    
                    <div id='action_info_launch_backup'><img src='/ossim/pixmaps/loading3.gif'/> <?php echo $checking_msg ?></div>
                    
                </div>
                
                <div class='clear_layer'></div>
                
            </div>
            
            
            <div class='backup_tips'>
                <ul>
                    <li><?php echo _('All system configurations including system profile, network configuration, asset inventory data, policy rules, plugins, correlation directives, and other basic configuration settings, will be backed up daily.') ?></li>
                    <li><?php echo _("To restore your system from an existing backup, go to the 'Maintenance & Troubleshooting' menu in the AlienVault console and choose 'Backups' and 'Restore configuration backup'.") ?></li>
                </ul>
            </div>
            
            
        </div>
        
    </div>

</body>

</html>

<?php
