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

$asset_type = GET('type');

Session::logcheck('environment-menu', 'EventsHidsConfig');
Session::logcheck_by_asset_type($asset_type);

session_write_close();


ossim_valid($asset_type, 'asset','network', 'group', 'illegal:' . _('Asset Type'));

if (ossim_error())
{
    echo ossim_error();
}


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


if ($asset_type == 'network' || $asset_type == 'group')
{
    Filter_list::save_members_from_selection($conn, $asset_type);
}


$total_selected    = Filter_list::get_total_selection($conn, 'asset');
$total_unknown_os  = 0;
$total_not_windows = 0;

if ($total_selected > 0)
{
    //Getting assets with unknown Operating System
    $tables = 'LEFT JOIN host_properties hp ON hp.host_id=host.id AND hp.property_ref=3 INNER JOIN user_component_filter f ON f.asset_id = host.id';

    $filters = array(
        'where' => '(hp.host_id IS NULL OR hp.value IS NULL OR hp.value LIKE "%unknown%")
                    AND f.asset_type="asset" AND f.session_id = "'.session_id().'"'
    );

    list($assets_unknown_os, $total_unknown_os) = Asset_host::get_list($conn, $tables, $filters, FALSE);


    //Getting assets with Operating System distinct to Windows
    $tables = ', host_properties hp,  user_component_filter f';

    $filters = array(
        'where' => 'hp.host_id=host.id AND hp.property_ref=3 AND (hp.value NOT LIKE "windows%" AND hp.value NOT LIKE "microsoft%")
                    AND f.asset_id = host.id AND f.asset_type="asset" AND f.session_id = "'.session_id().'"'
    );


    list($assets_not_w_os, $total_not_windows) = Asset_host::get_list($conn, $tables, $filters, FALSE);

}
else
{
    echo ossim_error(_('Unable to deploy HIDS agents. The selected assets do not have a Windows operating system. Please update the operating system and try again'), AV_WARNING);
    exit();
}

$db->close();

if ($total_unknown_os > 0 || $total_not_windows > 0)
{
    $action     = 'select_os';
    $f_template = AV_MAIN_ROOT_PATH.'/av_asset/common/templates/hids/tpl_bk_select_os.php';
}
else
{
    $action     = 'deploy_all_agents';
    $f_template = AV_MAIN_ROOT_PATH.'/av_asset/common/templates/hids/tpl_bk_deploy_hids.php';
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="X-UA-Compatible" content="IE=7"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(),           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                                 'def_path' => TRUE),
            array('src' => 'tipTip.css',                                    'def_path' => TRUE),
            array('src' => '/common/grid_system.css',                       'def_path' => TRUE),
            array('src' => '/environment/assets/deploy-hids.css',           'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'greybox.js',                                    'def_path' => TRUE),
            array('src' => 'notification.js',                               'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                             'def_path' => TRUE),
            array('src' => 'messages.php',                                  'def_path' => TRUE),
            array('src' => 'utils.js',                                      'def_path' => TRUE),
            array('src' => 'token.js',                                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                              'def_path' => TRUE),
            array('src' => '/av_asset/asset/js/deploy_hids.js.php',         'def_path' => FALSE)
        );

        Util::print_include_files($_files, 'js');
    ?>

    <script type="text/javascript">

        $(document).ready(function() {

            var action = '<?php echo $action?>';

            hids_agent = new Hids_agent();

            if (action == 'deploy_all_agents')
            {
                var config = {
                    validation_type: 'complete', // single|complete
                    errors:{
                        display_errors: 'all', //  all | summary | field-errors
                        display_in: 'av_info'
                    },
                    form : {
                        id  : 'deploy_form',
                        url : '/ossim/av_asset/common/controllers/bk_deploy_hids.php?action=deploy_all_agents'
                    },
                    actions: {
                        on_submit:{
                            id: 'send',
                            success:  $('#send').val(),
                            checking: '<?php echo _('Deploying')?>'
                        }
                    }
                };

                ajax_validator = new Ajax_validator(config);

                $('#send').off('click').on('click', function(){

                    if (ajax_validator.check_form() == true)
                    {
                        hids_agent.bulk_deploy(action);
                    }
                });
            }
            else
            {
                $('#show_assets').off('click').on('click', function(){

                    hids_agent.skip_assets('show_unsupported').done(function()
                    {
                        parent.GB_close({"action" : "show_unsupported", "status": "", "msg": ""});
                    });
                });

                <?php
                if(($total_unknown_os + $total_not_windows) < $total_selected)
                {
                    ?>
                    $('#continue').prop('disabled', false);
                    $('#continue').off('click').on('click', function(){

                        hids_agent.skip_assets('remove_unsupported').done(function()
                        {
                            document.location.href = '/ossim/av_asset/common/views/bk_deploy_hids_form.php?type=asset';
                        });
                    });
                    <?php
                }
                ?>
            }

            $('#cancel').click(function(){
                parent.GB_hide();
            });
        });

    </script>
</head>

<body>
    <div id='c_deploy'>

        <div id='av_info'></div>

        <div id='c_deploy_form'>
            <form method='POST' name='deploy_form' id='deploy_form'>
                <?php
                include $f_template;
                ?>
            </form>
        </div>
    </div>
</body>
</html>

