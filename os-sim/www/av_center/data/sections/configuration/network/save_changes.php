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
    'admin_dns'          => array('validation' => 'OSS_SEVERAL_IP_ADDRCIDR_0',  'e_message' => 'illegal:' . _('DNS Server')),
    'firewall_active'    => array('validation' => 'yes,no',                     'e_message' => 'illegal:' . _('Firewall')),
    'admin_ip'           => array('validation' => 'OSS_IP_ADDR',                'e_message' => 'illegal:' . _('IP')),
    'admin_gateway'      => array('validation' => 'OSS_IP_ADDR',                'e_message' => 'illegal:' . _('Gateway')),
    'admin_netmask'      => array('validation' => 'OSS_IP_ADDR',                'e_message' => 'illegal:' . _('Netmask'))
);


if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    else
    {
        //3 DNS Server IPs maximum allowed
        if ($_GET['name'] == 'admin_dns')
        {
            $dns = explode(',', str_replace(' ', '', $_GET['admin_dns']));

            if (count($dns) > 3)
            {
                $data['status'] = 'error';
                $data['data'][$_GET['name']] = _("Error in the 'DNS Servers' (More than 3 DNS Server IPs introduced)");
            }
        }
    }

    echo json_encode($data);
    exit();
}
else
{
    $validation_errors = validate_form_fields('POST', $validate);

    //3 DNS Server IPs maximum allowed
    if (empty($validation_errors['admin_dns']) && $_POST['admin_dns'] != '')
    {
        $dns = explode(',', str_replace(' ', '', $_POST['admin_dns']));

        if (count($dns) > 3)
        {
            $validation_errors['admin_dns'] = _("Error in the 'DNS Servers' (More than 3 DNS Server IPs introduced)");
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


//Action: Save Network Configuration
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
        exit;
    }

    $data = array();
    $data['general_admin_dns']     = str_replace(' ', '', POST('admin_dns'));
    $data['firewall_active']       = POST('firewall_active');
    $data['general_admin_ip']      = POST('admin_ip');
    $data['general_admin_gateway'] = POST('admin_gateway');
    $data['general_admin_netmask'] = POST('admin_netmask');

    $res = Av_center::set_general_configuration($system_id, $data);

    echo json_encode($res);
}