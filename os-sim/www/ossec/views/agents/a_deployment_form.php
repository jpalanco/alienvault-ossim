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


require_once (dirname(__FILE__) . '/../../conf/config.inc');

Session::logcheck('environment-menu', 'EventsHidsConfig');

$_POST = $_GET;

$agent_data = explode('###', base64_decode($_POST['agent_data']));

$_POST['agent_id'] = $agent_data[0];
$_POST['asset_id'] = $agent_data[1];


$sensor_id = POST('sensor_id');
$asset_id  = POST('asset_id');
$agent_id  = POST('agent_id');


$validate = array (
    'sensor_id' => array('validation' => "OSS_HEX",    'e_message' => 'illegal:' . _('Sensor ID')),
    'asset_id'  => array('validation' => "OSS_HEX",    'e_message' => 'illegal:' . _('Asset ID')),
    'agent_id'  => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Agent ID'))
);


$validation_errors = validate_form_fields('POST', $validate);


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


if (empty($validation_errors))
{
    //Extra validations
    try
    {
        if (Asset_host::is_in_db($conn, $asset_id) == FALSE)
        {
            $e_msg = _('Unable to deploy HIDS agent. This asset no longer exists in the asset inventory. Please check with your system admin for more information');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
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

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }
    }
    catch(Exception $e)
    {
        $validation_errors['asset_id'] = $e->getMessage();
    }


    if (empty($validation_errors))
    {
        //Getting asset information
        $_ips = $asset->get_ips();
        $ips  = $_ips->get_ips();


        //Checking HIDS Sensor
        $cnd_1 = (Ossec_utilities::is_sensor_allowed($conn, $sensor_id) == FALSE);

        $asset_sensors = Asset_host_sensors::get_sensors_by_id($conn, $asset_id);

        $cnd_2 = (empty($asset_sensors[$sensor_id]));

        if ($cnd_1 || $cnd_2)
        {
            $validation_errors['sensor_id'] = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information"), Av_sensor::get_name_by_id($conn, $sensor_id));
        }
        else
        {
            $system_ids  = Av_center::get_system_id_by_component($conn, $sensor_id);

            $res = Av_center::get_system_info_by_id($conn, $system_ids['non-canonical']);

            if ($res['status'] == 'success')
            {
                //We use this function to calculate sensor name because in HA environments there are two systems for one Sensor ID
                if (empty($res['data']['ha_ip']))
                {
                    $sensor_name = $res['data']['name'];
                }
                else
                {
                    $sensor_name = Av_sensor::get_name_by_id($conn, $sensor_id);
                }

                $sensor_ip = $res['data']['current_ip'];

                if (Ossec_utilities::get_default_sensor_id() == $sensor_id && empty($res['data']['ha_ip']))
                {
                    $sensor_ip = $res['data']['admin_ip'];
                }

                $sensor_ip_txt = $sensor_ip.' ['.$sensor_name.']';
            }

            //Getting Agent information
            $_aux_agent = Asset_host::get_related_hids_agents($conn, $asset_id, $sensor_id);

            $agent_key = md5(strtoupper($sensor_id).'#'.$agent_id);
            $agent     = $_aux_agent[$agent_key];

            if (empty($agent))
            {
                $validation_errors['agent_id'] = _('Error! Agent information cannot be retrieved from system');
            }
            else
            {
                $agent_descr = $agent['name'].' ('.$agent['ip_cidr'].')';
            }
        }
    }
}


$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/ossim/js/notification.js"></script>
    <script type="text/javascript" src="/ossim/js/ajax_validator.js"></script>
    <script type="text/javascript" src="/ossim/js/messages.php"></script>
    <script type="text/javascript" src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="/ossim/js/token.js"></script>

    <!-- Jquery Elastic Source: -->
    <script type="text/javascript" src="/ossim/js/jquery.elastic.source.js" charset="utf-8"></script>

    <!-- Greybox: -->
    <script type="text/javascript" src="/ossim/js/greybox.js"></script>

    <!-- JQuery tipTip: -->
    <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>

    <script type="text/javascript" src="/ossim/ossec/js/ossec_msg.php"></script>
    <script type="text/javascript" src="/ossim/ossec/js/common.js"></script>
    <script type="text/javascript" src="/ossim/ossec/js/agents.js"></script>

    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>

    <script type="text/javascript">

        var timer = null;

        $(document).ready(function() {
            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'c_info'
                },
                form : {
                    id  : 'form_a_deployment',
                    url : '/ossim/ossec/controllers/agents/a_deployment.php'
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success:  '<?php echo _('Save')?>',
                        checking: '<?php echo _('Saving')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').off('click')
            $('#send').click(function()
            {
                if (ajax_validator.check_form() == true)
                {
                    deploy_windows_agent();
                }
            });

            Token.add_to_forms();
        });

    </script>


    <style type='text/css'>

        input[type='text'], input[type='hidden'], input[type='password']
        {
            width: 98%;
            height: 18px;
        }

        select
        {
            width: 99%;
            height: 22px;
        }

        .legend
        {
            font-size: 10px;
            font-style: italic;
            text-align: center;
            padding: 20px 0px 5px 0px;
            margin: 20px auto 5px auto;
            width: 400px;
        }

        #c_deployment
        {
            width: 800px;
            min-height: 400px;
            border: none !important;
            margin: auto;
        }

        #t_a_deployment
        {
            width: 80%;
            border: none !important;
            border-collapse: collapse;
            margin: auto;
        }

        #t_a_deployment table
        {
            width: 100%;
            margin: auto;
            border: solid 1px #CCCCCC;
        }

        #t_a_deployment #table_container th
        {
            width: 200px;
        }

        #container_center
        {
            height: 100%;
            margin: 20px 0px;
            position: relative;
        }

        #c_info
        {
            width: 90%;
            margin: auto;
            position: relative !important;
            top: 0px !important;
        }

        #c_help
        {
            width: 90%;
            margin: 20px auto 0px auto;
            text-align: left;
        }

        #c_actions
        {
            padding: 10px 0px 20px 0px;
            margin: auto;
            text-align: center;
        }

    </style>

</head>

<body>



<div id='container_center'>

    <?php
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
            'style'   => 'width: 80%; margin: 50px auto 100px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);

        $nt->show();
    }
    else
    {
        /*
        echo "<pre>";
            print_r($_POST);
        echo "</pre>";
        */
        ?>
        <div id='c_deployment'>

            <div id='c_info'></div>

            <div id='c_help'></div>

            <div class="legend">
                <?php echo _('Values marked with (*) are mandatory');?>
            </div>

            <table id='t_a_deployment'>
                <tr>
                    <td class="nobborder" valign="top">
                        <form id='form_a_deployment' name='form_a_deployment' method="POST">
                            <input type="hidden" name="agent_id" id="agent_id" class='vfield' value="<?php echo $agent_id?>"/>
                            <input type="hidden" name="sensor_id" id="sensor_id" class='vfield' value="<?php echo $sensor_id?>"/>
                            <input type="hidden" name="asset_id" id="asset_id" class='vfield' value="<?php echo $asset_id?>"/>

                            <table id='table_container'>
                                <tr>
                                    <th>
                                        <label for='sensor_ip_txt'><?php echo _('HIDS Server IP');?></label>
                                    </th>
                                    <td class="left">
                                        <input type="text" disabled='disabled' readonly='readonly' name="sensor_ip_txt" id="sensor_ip_txt" value="<?php echo $sensor_ip_txt;?>"/>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <label for='agent_ip_txt'><?php echo _('Agent');?></label>
                                    </th>
                                    <td class="left">
                                        <input type="text" disabled='disabled' readonly='readonly' name="agent_ip_txt" id="agent_ip_txt" value="<?php echo $agent_descr;?>"/>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <label for='asset_ip'><?php echo _('Asset IP') . required();?></label>
                                    </th>
                                    <td class="left">
                                        <?php
                                        if (count($ips) > 1)
                                        {
                                            ?>
                                            <select id='asset_ip' name='asset_ip' class='vfield'>
                                                <?php
                                                foreach ($ips as $ip)
                                                {
                                                    ?>
                                                    <option value="<?php echo $ip['ip']?>"><?php echo $ip['ip']?></option>
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                            <?php
                                        }
                                        else
                                        {
                                            $ips = array_pop($ips);
                                            ?>
                                            <input type="hidden" class='vfield' name="asset_ip" id="asset_ip" value="<?php echo $ips['ip'];?>"/>
                                            <input type="text" disabled='disabled' readonly='readonly' name="asset_ip_txt" id="asset_ip_txt" value="<?php echo $ips['ip'];?>"/>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <label for='domain'><?php echo _('Domain')?></label>
                                    </th>
                                    <td class="left">
                                        <input type="text" class='info vfield' name="domain" id="domain" value="<?php echo $domain;?>"/>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <label for='user'><?php echo _('User') . required();?></label>
                                    </th>
                                    <td class="left">
                                        <input type="text" class='vfield' name="user" id="user"/>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <label for='pass'><?php echo _('Password') . required();?></label>
                                    </th>
                                    <td class="left">
                                        <input type="password" class='vfield' name="pass" id="pass" autocomplete="off"/>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>
            </table>

            <div id='c_actions'>
                <input type="button" id='send' value="<?php echo _('Deploy')?>"/>
            </div>

        </div>
        <?php
    }
    ?>
</div>

</body>
</html>
