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

Session::logcheck_by_asset_type('asset');
Session::logcheck('environment-menu', 'EventsHidsConfig');

session_write_close();

$asset_id = GET('asset_id');


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();

if (Asset_host::is_in_db($conn, $asset_id) == FALSE)
{
    echo ossim_error(_('Unable to deploy HIDS agent. This asset no longer exists in the asset inventory. Please check with your system admin for more information'));

    exit();
}

$asset = new Asset_host($conn, $asset_id);
$asset->load_from_db($conn);

//Check asset context
$ext_ctxs = Session::get_external_ctxs($conn);
$ctx      = $asset->get_ctx();

if (!empty($ext_ctxs[$ctx]))
{
    $e_msg = _('Asset can only be deployed at this USM');

    //Server related to CTX
    $server_obj = Server::get_server_by_ctx($conn, $ctx);

    if ($server_obj)
    {
        $s_name = $server_obj->get_name();
        $s_ip   = $server_obj->get_ip();

        $server = $s_name . ' ('.$s_ip.')';

        $e_msg = sprintf(_("Unable to deploy agent to assets on a child server. Please login to %s to deploy the HIDS agents"), $server);
    }

    echo ossim_error($e_msg, AV_WARNING);

    exit();
}


//Getting asset information

$_ips = $asset->get_ips();
$ips  = $_ips->get_ips();


//Getting Operating System
$os = $asset->get_os();
$os = $os['value'];

//Checking sensors
$asset_sensors = $asset->get_sensors();
$sensors       = $asset_sensors->get_sensors();


//HIDS sensors
$s_data = Ossec_utilities::get_sensors($conn, $sensor_id);
$hids_sensors = $s_data['sensors'];

$asset_hids_sensors = array();

if (is_array($sensors) && !empty($sensors))
{
    foreach($sensors as $s_id => $s_data)
    {
        $asset_hids_sensors[$s_id] = $hids_sensors[$s_id];
    }
}


if (empty($asset_hids_sensors))
{
    echo ossim_error(_('Unable to deploy HIDS agent. The asset does not have a valid sensor. Please update the sensor in asset details and try again'));

    exit();
}


if (empty($os))
{
    $action     = 'select_os';
    $f_template = AV_MAIN_ROOT_PATH.'/av_asset/asset/templates/form/hids/tpl_select_os.php';
}
elseif (preg_match('/^windows|microsoft/i', $os))
{
    //Deploy HIDS Agent
    $action     = 'deploy_agent';
    $f_template = AV_MAIN_ROOT_PATH.'/av_asset/asset/templates/form/hids/tpl_deploy_agent.php';
}
else
{
    //Deploy Agentless
    $action     = 'deploy_agentless';
    $f_template = AV_MAIN_ROOT_PATH.'/av_asset/asset/templates/form/hids/tpl_deploy_agentless.php';
}

$db->close();
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

            var asset_id = '<?php echo $asset_id?>';
            var action   = '<?php echo $action?>';
            var chk_msg  = (action == 'select_os') ? '<?php echo _('Saving')?>' : '<?php echo _('Deploying')?>';

            //AJAX validator
            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'deploy_form',
                    url : '/ossim/av_asset/asset/controllers/deploy_hids.php?action=' + action
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success:  $('#send').val(),
                        checking: chk_msg
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            //Creating HIDS object
            hids_agent = new Hids_agent();

            if (action == 'deploy_agent' || action == 'deploy_agentless')
            {
                var ha_func    = hids_agent.deploy;
                var ha_context = hids_agent;
                var ha_args    = [asset_id, action];

                //Show select box

                if ($('#ip_address option').length > 1)
                {
                    $('#c_ip_address').show();
                }

                if ($('#sensor_id option').length > 1)
                {
                    $('#c_sensor').show();
                }
            }
            else
            {
                var ha_func    = hids_agent.set_os;
                var ha_context = hids_agent;
                var ha_args    = [asset_id];
            }

            $('#send').off('click').on('click', function(){

                if (ajax_validator.check_form() == true)
                {
                    ha_func.apply(ha_context, ha_args);
                }
            });


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
                <input type='hidden' name='asset_id' id='asset_id' class='vfield' value='<?php echo $asset_id?>'/>
                <?php
                    include $f_template;
                ?>
            </form>
        </div>
    </div>

</body>
</html>

