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

$validation_errors = array();

$validate = array (
    'asset_id'   => array('validation' => 'OSS_HEX',                                              'e_message' => 'illegal:' . _('Asset ID')),
    'sensor_id'  => array('validation' => 'OSS_HEX',                                              'e_message' => 'illegal:' . _('Sensor ID')),
    'asset_ip'   => array('validation' => 'OSS_IP_ADDRCIDR',                                      'e_message' => 'illegal:' . _('Asset IP')),
    'agent_id'   => array('validation' => 'OSS_DIGIT',                                            'e_message' => 'illegal:' . _('Agent ID')),
    'user'       => array('validation' => 'OSS_USER',                                             'e_message' => 'illegal:' . _('User')),
    'pass'       => array('validation' => 'OSS_PASSWORD',                                         'e_message' => 'illegal:' . _('Password')),
    'domain'     => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Domain'))
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

    echo json_encode($data);
    exit();
}


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();

$token = POST('token');

//Checking form token
if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    if (Token::verify('tk_form_a_deployment', $token) == FALSE)
    {
        Token::show_error();

        exit();
    }
}

$validation_errors = validate_form_fields('POST', $validate);

//Check Token
if (empty($validation_errors))
{
    $sensor_id = POST('sensor_id');

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $validation_errors['sensor_id'] = _('Unable to deploy HIDS agent. The selected sensor is not allowed. Please update the sensor in asset details and try again');
    }
}



if (is_array($validation_errors) && !empty($validation_errors))
{
    $data['status'] = 'error';

    if (POST('ajax_validation_all') == TRUE)
    {
        $data['data'] = $validation_errors;
    }
    else
    {
        $data['data'] = '<div>'._('The following errors occurred').":</div>
            <div style='padding: 10px;'>".implode('<br/>', $validation_errors).'</div>';
    }
}
else
{
    if (POST('ajax_validation_all') == TRUE)
    {
        $data['status'] = 'OK';
        $data['data']   = _('HIDS data successfully checked');
    }
    else
    {
        $asset_id  = POST('asset_id');
        $sensor_id = POST('sensor_id');
        $agent_id  = POST('agent_id');

        try
        {
            $d_data = array(
                'asset_id'   => $asset_id,
                'w_ip'       => POST('asset_ip'),
                'w_user'     => POST('user'),
                'w_password' => POST('pass'),
                'w_domain'   => POST('domain'),
                'agent_id'   => $agent_id
            );

            $data['status'] = 'success';
            $data['data']   = Ossec_agent::deploy_windows_agent($sensor_id, $d_data);
        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
            $data['data']   = $e->getMessage();
        }
    }
}

$db->close();
echo json_encode($data);
