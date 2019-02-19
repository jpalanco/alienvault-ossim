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

require_once dirname(__FILE__) . '/../../conf/config.inc';


Session::logcheck('environment-menu', 'EventsHidsConfig');

$events_hids_config = Session::menu_perms('environment-menu', 'EventsHidsConfig');

$agent_data = explode('###', base64_decode(GET('agent_data')));

$_POST['agent_id']   = $agent_data[0];
$_POST['ip_cidr']    = $agent_data[1];
$_POST['sensor_id']  = REQUEST('sensor_id');


$agent_id   = POST('agent_id');
$ip_cidr    = POST('ip_cidr');
$sensor_id  = POST('sensor_id');

$validate = array (
    'sensor_id'   => array('validation' => "OSS_HEX",            'e_message' => 'illegal:' . _('Sensor ID')),
    'sensor_id'   => array('validation' => "OSS_HEX",            'e_message' => 'illegal:' . _('Agent ID')),
    'ip_cidr'     => array('validation' => 'OSS_IP_ADDRCIDR',    'e_message' => 'illegal:' . _('Agent IP'))
);


if ($ip_cidr == 'any')
{
    $validate['ip_cidr'] = array('validation' => 'any',          'e_message' => 'illegal:' . _('Agent IP'));
}


$validation_errors = validate_form_fields('POST', $validate);

//Get Sensor IP for selected sensor
if (empty($validation_errors))
{
    $db   = new ossim_db();
    $conn = $db->connect();

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $db->close();

        $validation_errors['sensor_id'] = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information"), Av_sensor::get_name_by_id($conn, $sensor_id));
    }

    $db->close();
}


if (is_array($validation_errors) && !empty($validation_errors))
{
    $errors = implode("<br/>", $validation_errors);
    $errors = str_replace('"', '\"', $errors);


    $content = "<div style='text-align: left; padding-left:5px;'>"._('The following errors occurred').":</div>
                <div style='padding-left:15px; text-align: left;'>$errors</div>";

    $config_nt = array(
        'content' => $content,
        'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => FALSE
        ),
        'style'   => 'width: 90%; margin: 50px auto 100px auto; text-align: left;'
    );

    $nt = new Notification('nt_1', $config_nt);

    $nt->show();
    exit();
}

//Current sensor
$_SESSION['ossec_sensor'] = $sensor_id;
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
            array('src' => 'tree.css',                                      'def_path' => TRUE),
            array('src' => '/environment/detection/hids-agent_form.css',    'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',                       'def_path' => TRUE),
            array('src' => '/common/grid_system.css',                       'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',                   'def_path' => TRUE),
            array('src' => 'jquery.cookie.js',                              'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js',                            'def_path' => TRUE),
            array('src' => 'notification.js',                               'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                             'def_path' => TRUE),
            array('src' => 'messages.php',                                  'def_path' => TRUE),
            array('src' => 'utils.js',                                      'def_path' => TRUE),
            array('src' => 'token.js',                                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                              'def_path' => TRUE),
            array('src' => '/ossec/js/ossec_msg.php',                       'def_path' => FALSE),
            array('src' => '/ossec/js/agents.js',                           'def_path' => FALSE)
        );

        Util::print_include_files($_files, 'js');
    ?>

    <script type="text/javascript">

        $(document).ready(function() {

            asset_tree_by_sensor('c_tree', '<?php echo $sensor_id?>');

            $("#asset").autocomplete('/ossim/ossec/providers/agents/assets_by_sensor.php', {
				minChars: 0,
				width: 350,
                matchContains: "word",
				max: 50,
				autoFill: false,
				scroll: true,
                scrollHeight: 150,
                mustMatch: true,
                extraParams: { sensor_id: '<?php echo $sensor_id?>' },
                formatItem: function(row, i, max, value)
                {
                    return (value.split('###'))[3];
                },
                formatResult: function(data, value)
                {
                    return (value.split('###'))[3];
                }
			}).result(function(event, item)
            {
                if (typeof(item) != 'undefined' && item != null)
                {
                    var _aux_item = item[0].split('###');
                    var asset_id  = _aux_item[0];

                    $('#asset_id').val(asset_id);
                    $('#send').prop('disabled', false);
                }
                else
                {
                    var asset_descr = $('#asset_descr').val();
                    var asset       = $('#asset').val();

                    if (asset_descr != '' && asset_descr != asset)
                    {
                        $('#asset_id').val('');
                        $('#send').prop('disabled', true);
                    }
                }
            });


            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'form_agent',
                    url : '/ossim/ossec/controllers/agents/link_asset_to_agent.php'
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success:  $('#send').val(),
                        checking: av_messages['submit_text']
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').off('click');
            $('#send').click(function(){
                manage_agent('link_asset');
            });
        });

    </script>
</head>

<body>
    <div id='c_agent_form'>

        <div id='av_info'></div>

        <form method='POST' name='form_agent' id='form_agent'>

            <input type='hidden' name='sensor_id' id='sensor_id' class='vfield' value="<?php echo $sensor_id?>"/>
            <input type='hidden' name='agent_id' id='agent_id' class='vfield' value="<?php echo $agent_id?>"/>
            <input type='hidden' name='asset_id' id='asset_id' class='vfield'/>
            <input type='hidden' name='ip_cidr' id='ip_cidr' class='ip_cidr' value="<?php echo $ip_cidr?>"/>
            <input type='hidden' name='asset_descr' id='asset_descr'/>

            <div class="grid-container">

                <div class="row">
                    <div class="col-6 c_label">
                        <label for='asset'><?php echo _('Select an asset to connect to HIDS agent. This will associate the agent with the asset so that you can see the status of the agent from the asset views.')?></label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <input type='text' name='asset' id='asset' placeholder="<?php echo _('Search by IP address or name')?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6" id='c_tree'></div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div id='c_actions'>
                            <input type="button" id='send' disabled='disabled' value="<?php echo _('Save')?>"/>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</body>

</html>

