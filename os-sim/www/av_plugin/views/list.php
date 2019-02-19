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

$tz = Util::get_timezone();

// Retrieve the plugin list from the API
$av_plugin   = new Av_plugin();
$error       = '';
$plugin_list = array();
try
{
    $plugin_list = $av_plugin->get_plugin_list();
}
catch(Exception $e)
{
    $error = $e->getMessage();
}

$av_plugin   = new Av_plugin();

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
            array('src' => 'jquery.dropdown.css',                                      'def_path' => TRUE),
            array('src' => 'av_table.css',                                             'def_path' => TRUE),
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
            array('src' => 'av_table.js.php',                               'def_path' => TRUE),
            array('src' => 'av_storage.js.php',                             'def_path' => TRUE),
            array('src' => 'jquery.md5.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.placeholder.js',                         'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                            'def_path' => TRUE),
            array('src' => '/av_plugin/js/plugin_list.js.php',              'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>
    
    <script type='text/javascript'>

        /**********  LIGHTBOX EVENTS  **********/
	function GB_onclose(url, params){
		if (url != "/ossim/av_plugin/views/add_plugin.php") return;
		$.post("/ossim/session/token.php",{'f_name': 'plugin_actions'},function(data) {
			data = {"token": $.parseJSON(data).data, "action":"rollback"}
			$.post('<?php echo AV_MAIN_PATH . "/av_plugin/controllers/plugin_actions.php" ?>',data);
			location.href = location.href;
		});
	}

        function GB_onhide(url, params)
        {
            if (typeof params == 'undefined')
            {
                params = {};
            }

            // Linked from wizard finish 'Configure Event Types' button
            if (typeof params['plugin_id'] != 'undefined' && params['plugin_id'] != '')
            {
                var _sids_url          = '<?php echo AV_MAIN_PATH . '/conf/pluginsid.php?plugin_id=' ?>' + params['plugin_id'];
                var _url               = top.av_menu.get_menu_url(_sids_url, 'configuration', 'threat_intelligence', 'data_source');
                document.location.href = _url;
            }
        }


        
    
        $(document).ready(function() 
        {
            var list = new av_plugin_list();
            list.init();
            
            // Error from the API
            <?php if ($error != '')
            {
            ?>
            show_notification('plugin_notif', "<?php echo $error ?>", 'nf_error');
            <?php
            }
            ?>
            

            // ToolTips
            $(".info").tipTip({maxWidth: '380px'});

            // Check All
            $('[data-bind="chk-all-plugins"]').on('change', function()
            {
                var status = $('[data-bind="chk-all-plugins"]').prop('checked');
                $('.plugin_check:enabled').prop('checked', status).trigger('change');
            });
            
            // Search Input
            $('[data-bind="search-plugin"]').on('keyup', function()
            {
                list.table.dt.fnFilter(this.value);
            
            }).on('input', function()
            {
                if ($(this).val() == '')
                {
                    list.table.dt.fnFilter(this.value);
                }
            });

            // Add New Plugin
            $('[data-bind="add-plugin"]').click(function()
            {
                var url   = "<?php echo AV_MAIN_PATH . "/av_plugin/views/add_plugin.php" ?>";
                var title = "<?php echo Util::js_entities(_('Add New Plugin')) ?>";
                GB_show(title, url, '600', '1250');
            });

            // Delete button
            $('[data-bind="delete-plugins"]').click(function()
            {
                list.delete_plugins();
            });
        });

    </script>
    

</head>

<body>

<?php 
    //Local menu
    include_once '../local_menu.php';
?>

    <!-- Download form launcher -->
    <form id='download_form' method='post' action='<?php echo AV_MAIN_PATH . "/av_plugin/controllers/download_plugin.php" ?>'>
    
        <input type='hidden' name='token'       id='download_form_token'       value=''/>
        <input type='hidden' name='plugin'      id='download_form_plugin'      value=''/>
    
    </form>

    
    
    
    <div id='main_container'>
        
        <div class="content">
            
            <div id="plugin_notif"></div>

            
            <div id='plugin_section_title'>
            
                <?php echo _('Plugins') ?>
                
            </div>
            
            
            
            <div id='content_result'>
                
                <div id='search_container'>
                    
                    <input type='search' id='search_filter' data-bind='search-plugin' class='input_search_filter' placeholder='<?php echo _('Search') ?>' />
                    
                </div>
                
                <div id='action_buttons'>
                    
                    <input type='button' id='add_button' data-bind='add-plugin' value='<?php echo _('Add New Plugin') ?>' />
                    
                    <img id='delete_selection' data-bind='delete-plugins' class='img_action disabled info' title='<?php echo _('Delete selected plugins') ?>' src="/ossim/pixmaps/delete.png"/>
    
                </div>
                
                <div data-name="plugins" data-bind="av_table_plugins">
    
                    <table class="table_data" id="table_data_plugins">
                        <thead>
                            <tr>
                                
                                <th class="center"><input type='checkbox' data-bind='chk-all-plugins'/></th>
                                
                                <th>
                                    <?php echo _('Plugin ID') ?>
                                </th>
                                
                                <th>
                                    <?php echo _('Vendor') ?>
                                </th>
                                
                                <th>
                                    <?php echo _('Model') ?>
                                </th>
                                
                                <th>
                                    <?php echo _('Version') ?>
                                </th>
                                
                                <th>
                                    <?php echo _('Last Update') ?>
                                </th>
                                
                                <th>
                                    <?php echo _('Event Types') ?>
                                </th>
                                
                                <th>
                                    <?php echo _('Action') ?>
                                </th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            
                            <?php
                            if (count($plugin_list) > 0)
                            {
                                foreach ($plugin_list as $plugin_data)
                                {
                                    $check_disabled = ($plugin_data['plugin_type'] < 1) ? 'disabled' : '';
                                ?>
                                
                                <tr>
                                    <td>
                                        <input type='checkbox' class='plugin_check' name='<?php echo $plugin_data['plugin_name'] ?>' value='1' <?php echo $check_disabled ?>/>
                                    </td>
                                    <td><?php echo $plugin_data['plugin_id'] ?></td>
                                    <td><?php echo $plugin_data['vendor'] ?></td>
                                    <td><?php echo $plugin_data['model'] ?></td>
                                    <td><?php echo $plugin_data['version'] ?></td>
                                    <td><?php echo gmdate("m/d/Y H:i:s", strtotime($plugin_data['last_update'])  + (3600*$tz)) ?></td>
                                    <td><?php echo Util::number_format_locale($plugin_data['nsids']) ?></td>
                                    <td><a href="javascript:;" class="download_button" data-bind="download-plugin" data-plugin="<?php echo $plugin_data['plugin_name'] ?>">
                                         <img src="/ossim/pixmaps/download_dt.png" border="0">
                                        </a>
                                    </td>
                                </tr>
                                
                                <?php
                                }
                            }
                            ?>
                            
                        </tbody>
                    </table>
                    
                </div>
                

            </div>
            
            
        </div>
        
    </div>

</body>

</html>

<?php
/* End of file list.php */
/* Location: /av_backup/controllers/backup_actions.php */
