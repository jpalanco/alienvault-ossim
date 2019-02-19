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


ob_implicit_flush();
ini_set('include_path', '/usr/share/ossim/include');

require_once 'av_init.php';

//Arguments
$targets  = $argv[1];
$sensor   = $argv[2];
$user     = $argv[3];


$_POST['sensor'] = $sensor;
$_POST['user']   = $user;


$validate = array (
    'sensor' => array('validation' => 'OSS_HEX, OSS_ALPHA, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Sensor')),
    'rdns'   => array('validation' => 'OSS_BINARY, OSS_NULLABLE',          'e_message' => 'illegal:' . _('Scan Owner'))
);


$validation_errors = validate_form_fields('POST', $validate);

if (!is_array($validation_errors) || empty($validation_errors))
{
    $targets = explode(' ', $targets);

    if (is_array($targets) && count($targets) > 0)
    {
        $targets_string = array();

        foreach ($targets as $target)
        {
            // Validate UUID#IP or IP, other cases will fail
            $_target = explode('#', $target);

            if (count($_target) == 1)
            {
                $_target_ip = $_target[0];

                ossim_valid($_target_ip, OSS_IP_ADDRCIDR, 'illegal:' . _('Asset IP'));

            }
            elseif (count($_target) == 2)
            {
                $_target_ip = $_target[1];
                $_target_id = $_target[0];

                ossim_valid($_target_ip, OSS_IP_ADDRCIDR,  'illegal:' . _('Asset IP'));
                ossim_valid($_target_id, OSS_HEX,          'illegal:' . _('Asset ID'));
            }
            else
            {
                ossim_set_error(_('Asset not allowed'));
            }

            if (ossim_error())
            {
                $validation_errors['assets[]'] = strip_tags(ossim_get_error_clean());

                break;
            }
            else
            {
                //IP_CIDR and ID is pushed
                array_push($targets_string, $target);
            }
        }

        $targets_p = implode(' ', $targets_string);
    }
    else
    {
        $validation_errors['assets[]'] = _("Error in the 'Target selection' field (missing required field)");
    }
}



if (is_array($validation_errors) && !empty($validation_errors))
{
    //Formatted message
    $error_msg = _('The following errors occurred').":\n".implode("\n", $validation_errors);
    $error_msg = strip_tags($error_msg);

    die($error_msg);
}


try
{
    //Autologin in UI and AlienVault API

    //Database connection
    list($db, $conn) = Ossim_db::get_conn_db();

    $db   = new Ossim_db();
    $conn = $db->connect();


    $user_obj = Session::get_user_info($conn, $user, TRUE, FALSE);
    $pass     = $user_obj->get_pass();

    $session = new Session($user, $pass, '');
    $session->login(TRUE);

    $db->close();

    $is_disabled = $session->is_user_disabled();

    if ($is_disabled == TRUE)
    {
        $e_msg = _('Error! Scan cannot be completed: Scan owner is disabled');

        Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
    }

    $client = new Alienvault_client($user);
    $client->auth()->login($user, $pass);


    //Launching scan


    $scan_options = array(
        'scan_type'     => 'ping',
        'scan_timing'   => 'T3',
        'autodetect_os' => 'false',
        'reverse_dns'   => 'false',
        'scan_ports'    => '',
        'idm'           => 'false'
    );


    $av_scan = new Av_scan($targets_p, $sensor, $scan_options);
    $av_scan->run();

    Av_scan::set_object_in_file($av_scan, $scan_file);

    echo "Ping scan:\n";
    echo "\tTargets: ".$av_scan->get_targets('scan_format')."\n";
    echo "\tSensor: ".$av_scan->get_sensor()."\n";
    echo "\tScan Options: \n";

    $sc_options = $av_scan->get_scan_options();
    foreach($sc_options as $sc_type => $sc_value)
    {
        echo "\t\t$sc_type: $sc_value\n";
    }

    $status        = $av_scan->get_status();
    $scan_finished = array(Av_scan::ST_SCAN_FINISHED, Av_scan::ST_FAILED);

    while (!in_array($status['status']['code'], $scan_finished))
    {
        sleep(2);
        $status = $av_scan->get_status();
    }

    // Getting discovered hosts
    $scan_report = $av_scan->download_scan_report();

    // Deleting Scan Report
    $av_scan->delete_scan();

    //Parsing scan report
    $nmap_parser = new Nmap_parser();
    $scan_report = $nmap_parser->parse_json($scan_report, $av_scan->get_sensor());

    if (!empty($scan_report['scanned_ips']))
    {
        foreach ($scan_report['scanned_ips'] as $ip => $hdata)
        {
            if ($hdata['status'] == 'up')
            {
                echo "Host $ip appears to be up\n";
            }
        }
    }

}
catch(Exception $e)
{
    echo strip_tags($e->getMessage());
}

echo "\n\n";

