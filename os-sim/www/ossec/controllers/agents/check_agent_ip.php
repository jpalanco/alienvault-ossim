<?php

/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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

session_write_close();

$validation_errors = array();

$sensor_id  = POST('sensor_id');
$asset_id   = POST('asset_id');
$token      = POST('token');

$_REQUEST['ip_cidr'] = strtolower(REQUEST('ip_cidr'));
$ip_cidr = REQUEST('ip_cidr');


$validate = array(
    'sensor_id'  => array('validation' => "OSS_HEX",         'e_message' => 'illegal:' . _('Sensor ID')),
    'asset_id'   => array('validation' => "OSS_HEX",         'e_message' => 'illegal:' . _('Asset')),
    'ip_cidr'    => array('validation' => 'OSS_IP_ADDRCIDR', 'e_message' => 'illegal:' . _('IP/CIDR')));

if ($ip_cidr == 'any')
{
    $validate['ip_cidr'] = array('validation' => 'any',     'e_message' => 'illegal:' . _('IP/CIDR'));
}


$db   = new ossim_db();
$conn = $db->connect();


//Check Token
if (!Token::verify('tk_f_agents', $token))
{
    $error = Token::create_error_message();

    Util::response_bad_request($error);
}


$validation_errors = validate_form_fields('POST', $validate);

//Extra validations

if (empty($validation_errors['sensor_id']) && !Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
{
    $validation_errors['sensor_id'] = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information"), Av_sensor::get_name_by_id($conn, $sensor_id));
}


if (is_array($validation_errors) && !empty($validation_errors))
{
    $error_msg = "<div style='text-align: left;'>"._('The following errors occurred').":</div>
                  <div style='padding-left:15px; text-align: left;'>".implode('<br/>', $validation_errors)."</div>";


    $error = Token::create_error_message();

    Util::response_bad_request($error_msg);
}


$warning_msg = '';

//Validate IP/CIDR

if ($ip_cidr != 'any' && $ip_cidr != '0.0.0.0/0')
{
    if (Asset_host_ips::valid_ip($ip_cidr))
    {
        //Agent IP/CIDR is an IP address
        $asset_ips = Asset_host_ips::get_ips_to_string($conn, $asset_id);

        if (preg_match('/'.$ip_cidr.'/', $asset_ips) == FALSE)
        {
            $warning_msg = _('The asset IP and IP/CIDR do not match');
        }
    }
    else
    {
        //Agent IP/CIDR is an CIDR
        $ip_range = CIDR::expand_cidr($ip_cidr, 'SHORT', 'LONG');

        $asset_ips_obj = new Asset_host_ips($asset_id);
        $asset_ips_obj->load_from_db($conn);

        $asset_ips = $asset_ips_obj->get_ips();

        $valid_ip_range = FALSE;

        foreach($asset_ips as $a_data)
        {
            $ip = Asset_host_ips::ip2ulong($a_data['ip']);

            if ($ip >= $ip_range[0] && $ip <= $ip_range[1])
            {
                $valid_ip_range = TRUE;
                break;
            }
        }

        if ($valid_ip_range == FALSE)
        {
            $warning_msg = _('The selected asset IP is out of the IP/CIDR range');
        }
    }
}

$db->close();


$data['status'] = 'success';
$data['data']   = $warning_msg;


echo json_encode($data);
exit();
