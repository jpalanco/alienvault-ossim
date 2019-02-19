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


$db   = new ossim_db();
$conn = $db->connect();



$validate = array (
    'hostname'    => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT',   'e_message' => 'illegal:' . _('Hostname')),
    'ip'          => array('validation' => 'OSS_IP_ADDR',                                               'e_message' => 'illegal:' . _('IP')),
    'sensor'      => array('validation' => 'OSS_HEX',                                                   'e_message' => 'illegal:' . _('Sensor')),
    'user'        => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT',                     'e_message' => 'illegal:' . _('User')),
    'descr'       => array('validation' => 'OSS_ALL, OSS_NULLABLE',                                     'e_message' => 'illegal:' . _('Description')),
    'pass'        => array('validation' => 'OSS_PASSWORD',                                              'e_message' => 'illegal:' . _('Password')),
    'ppass'       => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',                                'e_message' => 'illegal:' . _('Privileged Password')),
    'use_su'      => array('validation' => 'OSS_BINARY, OSS_NULLABLE',                                  'e_message' => 'illegal:' . _('Option use_su'))
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



$validation_errors = validate_form_fields('POST', $validate);
if (is_array($validation_errors) && !empty($validation_errors))
{
    Util::response_bad_request(implode('<br/>', $validation_errors));
}

if (POST('pass') != POST('passc'))
{
    Util::response_bad_request(_('Password fields are different'));
}

if (!empty($_POST['ppass']) && (POST('ppass') != POST('ppassc')))
{
    Util::response_bad_request(_('Privileged Password fields are different'));
}

if (!Ossec_utilities::is_sensor_allowed($conn, POST('sensor')))
{
    Util::response_bad_request(_('Error! Sensor not allowed'));
}


$entries   = is_array(POST('entries')) ? POST('entries') : array();

foreach ($entries as $entry)
{
    ossim_valid($entry['id_type'],      OSS_NOECHARS, OSS_SCORE, OSS_LETTER,    'illegal:' . _('Type'));
    ossim_valid($entry['frequency'],    OSS_DIGIT,                              'illegal:' . _('frequency'));
    ossim_valid($entry['state'],        OSS_NOECHARS, OSS_SCORE, OSS_LETTER,    'illegal:' . _('State'));
    ossim_valid($entry['arguments'],    OSS_NOECHARS, OSS_TEXT, OSS_SPACE, OSS_AT, OSS_NULLABLE, OSS_PUNC_EXT, '\`', '\<', '\>', 'illegal:' . _('Arguments'));
        
    if (ossim_error())
    {
        Util::response_bad_request(ossim_get_error_clean());
    }
}



$ip        = POST('ip');
$sensor_id = POST('sensor');
$hostname  = POST('hostname');
$user      = POST('user');
$pass      = POST('pass');
$ppass     = POST('ppass');
$use_su    = POST('use_su');


$descr     = Util::utf8entities(POST('descr'));
$descr     = mb_convert_encoding($descr, 'ISO-8859-1', 'UTF-8');


try
{
    $agentless = Ossec_agentless::get_object($conn, $sensor_id, $ip);
    $status    = (is_object($agentless) && $agentless->get_status() == 0) ? 0 : 1;
}
catch (Exception $e)
{
    $status = 1;
}

try
{
    Ossec_agentless::save_in_db($conn, $ip, $sensor_id, $hostname, $user, $pass, $ppass, $use_su, $descr, $status);
    Ossec_agentless::save_agentless_monitoring_entries($conn, $ip, $sensor_id, $entries);
}
catch(Exception $e)
{
    Util::response_bad_request($e->getMessage());
}


$db->close();