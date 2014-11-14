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


//Config File
require_once (dirname(__FILE__) . '/../../../../config.inc');

session_write_close();

$validate = array (
    'system_id'                => array('validation' => "OSS_DIGIT, OSS_LETTER, '-'",            'e_message' => 'illegal:' . _('System ID')),
    'server_url'               => array('validation' => 'OSS_URL_ADDRESS',                       'e_message' => 'illegal:' . _('Server url')),
    'hostname'                 => array('validation' => 'OSS_FQDNS',                             'e_message' => 'illegal:' . _('Hostname')),
    'h_hostname'               => array('validation' => 'OSS_FQDNS',                             'e_message' => 'illegal:' . _('Old hostname')),
    'admin_ip'                 => array('validation' => 'OSS_IP_ADDR',                           'e_message' => 'illegal:' . _('Admin IP')),
    'h_admin_ip'               => array('validation' => 'OSS_IP_ADDR',                           'e_message' => 'illegal:' . _('Old admin IP')),
    'yn_ntp_server'            => array('validation' => 'yes,no',                                'e_message' => 'illegal:' . _('NTP Server Status')),
    'ntp_server'               => array('validation' => 'OSS_FQDN_IP',                           'e_message' => 'illegal:' . _('NTP Server')),
    'yn_mailserver_relay'      => array('validation' => 'yes,no',                                'e_message' => 'illegal:' . _('Mail Server Status')),
    'mailserver_relay'         => array('validation' => 'OSS_MAIL_SERVER_ADDRESS, OSS_NULLABLE', 'e_message' => 'illegal:' . _('Mail Server')),
    'mailserver_relay_passwd'  => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',            'e_message' => 'illegal:' . _('Pass')),
    'mailserver_relay_passwd2' => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',            'e_message' => 'illegal:' . _('Confirm pass')),
    'mailserver_relay_user'    => array('validation' => 'OSS_MAIL_USER, OSS_NULLABLE',           'e_message' => 'illegal:' . _('User')),
    'mailserver_relay_port'    => array('validation' => 'OSS_PORT, OSS_NULLABLE',                'e_message' => 'illegal:' . _('Port'))
);


if (GET('ajax_validation') == TRUE)
{
    //Special Case

    if (isset($_GET['ntp_server']) && $_GET['ntp_server'] == 'no')
    {
        $_GET['ntp_server'] = '';
    }

    if (isset($_GET['mailserver_relay']) && $_GET['mailserver_relay'] == 'no')
    {
        $_GET['mailserver_relay'] = '';
    }

    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    else
    {
        //One IP address or server name is allowed (Patch temporary)
        if (isset($_GET['ntp_server']) && $_GET['ntp_server'] != '')
        {
            $ntp_servers = trim($_GET['ntp_server']);
            $ntp_servers = str_replace(' ', '', $ntp_servers);
            $ntp_servers = explode(",", $ntp_servers);

            if (count($ntp_servers) > 1)
            {
                $data['status'] = 'error';
                $data['data']   = array('ntp_server' => _("Error in the 'NTP Server' field (More than one server)"));
            }
        }
    }

    echo json_encode($data);
    exit();
}
else
{
    //Special Cases


    //Check NTP Server
    if ($_POST['yn_ntp_server'] == 'no')
    {
        $_POST['ntp_server'] = '';
        unset($validate['ntp_server']);
    }

    //Check Mail Server
    if ($_POST['yn_mailserver_relay'] == 'no')
    {
        $_POST['mailserver_relay']         = '';
        $_POST['mailserver_relay_passwd']  = '';
        $_POST['mailserver_relay_passwd2'] = '';
        $_POST['mailserver_relay_user']    = '';
        $_POST['mailserver_relay_port']    = '';
    }


    $validation_errors = validate_form_fields('POST', $validate);


    //Special check for Mail Server Re parameters
    $cnd_1 = (empty($validation_errors['mailserver_relay_passwd']) && empty($validation_errors['mailserver_relay_passwd2']));
    $cnd_2 = (POST('mailserver_relay_passwd') != POST('mailserver_relay_passwd2'));

    if ($cnd_1 && $cnd_2)
    {
        $validation_errors['mailserver_relay_passwd'] = _('Passwords do not match');
    }


    $mailserver_relay = strtolower(trim($_POST['mailserver_relay']));
    $cnd_1 = ($_POST['yn_mailserver_relay'] != 'no');
    $cnd_2 = ($mailserver_relay == 'localhost' || $mailserver_relay == '127.0.0.1');

    if ($cnd_1 && $cnd_2)
    {
        $validation_errors['mailserver_relay'] = _("Local IP not allowed");
    }

    //One IP address or server name is allowed (Patch temporary)
    if (empty($validation_errors['ntp_server']) && $_POST['ntp_server'] != '')
    {
        $ntp_servers = trim($_POST['ntp_server']);
        $ntp_servers = str_replace(' ', '', $ntp_servers);
        $ntp_servers = explode(",", $ntp_servers);

        if (count($ntp_servers) > 1)
        {
            $validation_errors['ntp_server'] = _("Error in the 'NTP Server' field (More than one server)");
        }
    }

    //Check Admin IPs
    if (empty($validation_errors['admin_ip']) && empty($validation_errors['system_id']))
    {
        $system_id = POST('system_id');

        $admin_ips = array();

        try
        {
            $general_cnf = Av_center::get_general_configuration($system_id);

            $admin_ips[$general_cnf['data']['admin_ip']['value']] = $general_cnf['data']['admin_ip']['value'];

            $st = Av_center::get_system_status($system_id, 'network');

            foreach ($st['interfaces'] as $i_name => $i_data)
            {
                if ($i_name != 'lo' && $i_data['ipv4']['address'] != '')
                {
                    $admin_ips[$i_data['ipv4']['address']] = $i_data['ipv4']['address'];
                }
            }
        }
        catch(Exception $e)
        {
            ;
        }

        if (!array_key_exists(POST('admin_ip'), $admin_ips))
        {
            $validation_errors['admin_ip'] = _('Admin IP not allowed');
        }
    }

    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status']  = 'error';
        $data['data']    = $validation_errors;

        echo json_encode($data);
        exit();
    }
    elseif (POST('ajax_validation_all') == TRUE && empty($validation_errors))
    {
        $data['status'] = 'OK';

        echo json_encode($data);
        exit();
    }
}


//Action: Save General Configuration
$action = POST('action');

if ($action == 'save_changes')
{
    $system_id = POST('system_id');
    ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));

    if (ossim_error())
    {
        $data['status']  = 'error';
        $data['data']    = ossim_get_error();

        echo json_encode($data);
        exit();
    }

    $data = array();
    $data['general_admin_ip'] = POST('admin_ip');
    $data['general_hostname'] = POST('hostname');

    if (POST('ntp_server') == '')
    {
        $data['general_ntp_server'] = 'no';
    }
    else
    {
        //Change NTP Server format(from comma-separated to space-separated)

        /*
        $ntp_servers = trim(POST('ntp_server'));
        $ntp_servers = str_replace(' ', '', $ntp_servers);
        $ntp_servers = str_replace(',', ' ', $ntp_servers);
        */

        //One IP address or server name is allowed (Patch temporary)
        $data['general_ntp_server'] = POST('ntp_server');
    }


    if (POST('mailserver_relay') == '')
    {
        $data['general_mailserver_relay']        = 'no';
        $data['general_mailserver_relay_passwd'] = 'unconfigured';
        $data['general_mailserver_relay_user']   = 'unconfigured';
        $data['general_mailserver_relay_port']   = '25';
    }
    else
    {
        $data['general_mailserver_relay'] = POST('mailserver_relay');

        if(!Util::is_fake_pass(POST('mailserver_relay_passwd')))
        {
            $data['general_mailserver_relay_passwd'] = (POST('mailserver_relay_passwd') == '') ? 'unconfigured' : POST('mailserver_relay_passwd');
        }

        $data['general_mailserver_relay_user']   = (POST('mailserver_relay_user') == '') ? 'unconfigured' : POST('mailserver_relay_user');
        $data['general_mailserver_relay_port']   = (POST('mailserver_relay_port') == '') ? '25'           : POST('mailserver_relay_port');
    }

    $res = Av_center::set_network_configuration($system_id, $data);

    echo json_encode($res);
}
?>