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

$sensor_id = REQUEST('sensor_id');

ossim_valid($sensor_id, OSS_HEX,  'illegal:' . _('Sensor ID'));

if (!ossim_error())
{
    $db    = new ossim_db();
    $conn  = $db->connect();

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $db->close();
        $error_msg = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information"), Av_sensor::get_name_by_id($conn, $sensor_id));

        echo ossim_error($error_msg);
        exit();
    }

    $db->close();
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
            array('src' => '/environment/detection/hids-agent_form.css',    'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',                       'def_path' => TRUE),
            array('src' => 'tree.css',                                      'def_path' => TRUE),
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

                    var asset_id    = _aux_item[0];
                    var asset_ip    = _aux_item[1];
                    var asset_name  = _aux_item[2];
                    var asset_descr = _aux_item[3];

                    $('#asset_descr').val(asset_descr);
                    $('#asset_id').val(asset_id);
                    $('#agent_name').prop('disabled', false).val(asset_name);
                    $('#ip_cidr').prop('disabled', false).val(asset_ip);
                    $('#send').prop('disabled', false);

                    $('#dhcp').prop('disabled', false);
                    $('#dhcp').prop('checked', false);
                }
                else
                {
                    var asset_descr = $('#asset_descr').val();
                    var asset       = $('#asset').val();

                    if (asset_descr != '' && asset_descr != asset)
                    {
                        $('#asset_id').val('');
                        $('#agent_name').prop('disabled', true).val('');
                        $('#ip_cidr').prop('disabled', true).val('');
                        $('#send').prop('disabled', true);

                        $('#dhcp').prop('disabled', true);
                        $('#dhcp').prop('checked', false);
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
                    url : '/ossim/ossec/controllers/agents/save_agent.php'
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
                $('#ip_cidr').prop('disabled', false)
                manage_agent('new')
            });


            $('#dhcp').off('change').on('change', function(event) {

                if ($('#ip_cidr').val() != 'any')
                {
                    $('#ip_cidr').prop('disabled', true);
                    $('#ip_cidr').data('org_ip_cidr', $('#ip_cidr').val());
                    $('#ip_cidr').val('any');
                }
                else
                {
                    $('#ip_cidr').prop('disabled', false);
                    $('#ip_cidr').val($('#ip_cidr').data('org_ip_cidr'));
                }
            });

        });

    </script>
</head>

<body>
    <div id='c_agent_form'>

        <div id='av_info'></div>

        <form method='POST' name='form_agent' id='form_agent'>

            <input type='hidden' name='sensor_id' id='sensor_id' class='vfield' value="<?php echo $sensor_id?>"/>
            <input type='hidden' name='asset_id' id='asset_id' class='vfield'/>
            <input type='hidden' name='asset_descr' id='asset_descr'/>

            <div class='legend'>
                <?php echo _('Values marked with (*) are mandatory');?>
            </div>

            <div class="grid-container">

                <div class="row">
                    <div class="col-6 c_label">
                        <label class='f_required' for='asset'><?php echo _('Select an asset to connect to HIDS agent. This will associate the agent with the asset so that you can see the status of the agent from the asset views.')?></label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <input type='text' name='asset' id='asset' placeholder="<?php echo _('Search by IP address or name')?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 c_label" id='c_tree'></div>
                </div>

                <div class="row">
                    <div class="col-6 c_label">
                        <label class='f_required' for='agent_name'><?php echo _('Agent Name')?></label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <input type='text' name='agent_name' id='agent_name' class='vfield' disabled="disabled"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 c_label" id="ip_cidr_label">
                        <label class='f_required' for='agent_name'><?php echo _('IP/CIDR')?></label>
                    </div>
                    <div>
                        <input type='checkbox' name='dhcp' id='dhcp' disabled="disabled" value='1'/>
                        <?php echo _('This is a dynamic IP address (DHCP)');?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <input type='text' name='ip_cidr' id='ip_cidr' class='vfield' disabled="disabled"/>
                    </div>
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

