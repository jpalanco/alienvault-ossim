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
require_once 'av_init.php';

Session::logcheck('environment-menu', 'ToolsScan');

ini_set('max_execution_time','1200');

session_write_close();

//Validate action type

$action = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


$user = Session::get_session_user();

$scan_file        = 'last_asset_object-'. md5($user);
$scan_report_file = AV_TMP_DIR.'/last_scan_report-'. md5($user);

try
{
    //Validate Form token

    $token = POST('token');

    if (Token::verify('tk_assets_form', $token) == FALSE)
    {
        $e_msg = Token::create_error_message();

        Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
    }

    switch ($action)
    {
        case 'delete_scan':

            try
            {
                $av_scan = Av_scan::get_object_from_file($scan_file);

                $av_scan->delete_scan();
                Cache_file::remove_file($scan_file);
                $data['status'] = 'success';
                $data['data']   = _('Asset scan has been permanently deleted');
            }
            catch(Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = sprintf('Failed to delete asset scan: %s', $e->getMessage());
            }


        break;


        case 'stop_scan':

            try
            {
                $av_scan = Av_scan::get_object_from_file($scan_file);
    
                $av_scan->stop();
            }
            catch(Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = sprintf('Failed to stop asset scan: %s', $e->getMessage());
            }

            $data['status'] = 'success';
            $data['data']   = _('Asset scan has been stopped');

        break;


        case 'run_scan':

            $_POST['custom_ports'] = str_replace(' ', '',  $_POST['custom_ports']);
            $_POST['autodetect']   = ($_POST['autodetect'] == '1') ? 1 : 0;
            $_POST['rdns']         = ($_POST['rdns'] == '1') ? 1 : 0;

            $assets          = POST('assets');
            $sensor          = POST('sensor');
            $scan_type       = POST('scan_type');
            $timing_template = POST('timing_template');
            $custom_ports    = POST('custom_ports');
            $autodetect      = POST('autodetect');
            $rdns            = POST('rdns');

            $validate = array (
                'sensor'          => array('validation' => 'OSS_HEX, OSS_ALPHA, OSS_NULLABLE',                      'e_message' => 'illegal:' . _('Sensor')),
                'scan_type'       => array('validation' => 'OSS_LETTER',                                            'e_message' => 'illegal:' . _('Scan Mode')),
                'timing_template' => array('validation' => 'OSS_TIMING_TEMPLATE',                                   'e_message' => 'illegal:' . _('Timing Template')),
                'custom_ports'    => array('validation' => "OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, ','",    'e_message' => 'illegal:' . _('Custom Ports')),
                'autodetect'      => array('validation' => 'OSS_BINARY',                                            'e_message' => 'illegal:' . _('Autodetected services and OS')),
                'rdns'            => array('validation' => 'OSS_BINARY',                                            'e_message' => 'illegal:' . _('Reverse DNS ')),
            );

            $validation_errors = validate_form_fields('POST', $validate);

            //Validate assets

            if (!is_array($validation_errors) || empty($validation_errors))
            {
                if (is_array($assets) && count($assets) > 0)
                {
                    $assets_string = array();
                    $excludes = array();
                    foreach ($assets as $asset)
                    {
			if (strpos($asset,"!") === 0) {
                            $excludes[] = str_replace("!","",$asset);
                            continue;
                        }


                        // Validate UUID#IP or IP, other cases will fail
                        $_asset = explode('#', $asset);
                        if (count($_asset) == 1)
                        {
                            $_asset_ip = $_asset[0];
                            ossim_valid($_asset_ip, OSS_IP_ADDRCIDR, 'illegal:' . _('Asset IP'));

                        }
                        elseif (count($_asset) == 2)
                        {
                            $_asset_ip = $_asset[1];
                            $_asset_id = $_asset[0];

                            ossim_valid($_asset_ip, OSS_IP_ADDRCIDR,  'illegal:' . _('Asset IP'));
                            ossim_valid($_asset_id, OSS_HEX,          'illegal:' . _('Asset ID'));
                        }
                        else
                        {
                            ossim_set_error(_('Asset not allowed'));
                        }

                        if (ossim_error())
                        {
                            $validation_errors['assets[]'] = ossim_get_error_clean();

                            break;
                        }
                        else
                        {
                            //IP_CIDR and ID is pushed
                            array_push($assets_string, $asset);
                        }
                    }

                    $assets_p = implode(' ', $assets_string);
                }
                else
                {
                    $validation_errors['assets[]'] = _("Error in the 'Target selection' field (missing required field)");
                }
            }


            if (is_array($validation_errors) && !empty($validation_errors))
            {
                //Formatted message
                $error_msg = '<div>'._('The following errors occurred').":</div>
                              <div style='padding: 5px;'>".implode('<br/>', $validation_errors).'</div>';

                Util::response_bad_request($error_msg);
            }
            else
            {

                $autodetect      = ($autodetect == 1) ? 'true' : 'false';
                $rdns            = ($rdns == 1)       ? 'true' : 'false';

                $scan_options = array(
                    'scan_type'     => $scan_type,
                    'scan_timing'   => $timing_template,
                    'autodetect_os' => $autodetect,
                    'reverse_dns'   => $rdns,
                    'scan_ports'    => $custom_ports,
                    'idm'           => 'false',
                    'excludes'      => implode(",",$excludes)
                );

                $av_scan = new Av_scan($assets_p, $sensor, $scan_options);
                $job_id = $av_scan->run();

                //File to cache scan object
                $scan_file = 'last_asset_object-'.md5($user);

                Av_scan::set_object_in_file($av_scan, $scan_file);

                $data['status'] = 'success';
                $data['data']   = $job_id;
            }

        break;


        case 'scan_status':

            $av_scan = Av_scan::get_object_from_file($scan_file);

            if (!is_object($av_scan) || empty($av_scan))
            {
                $scan_running = FALSE;
                $scan_message = _('No asset scan running');
                $scan_status  = array(
                    'code'  => 0,
                    'descr' => Av_scan::ST_IDLE
                );
                $scan_status = array(
                    'message'  => $scan_message,
                    'status'   => $scan_status,
                    'progress' => array(
                        'percent' => 0,
                        'current' => 0,
                        'total'   => 0,
                        'time'    => 0
                    )
                );
            }
            else
            {
                //Getting scan status
                $status = $av_scan->get_status();

                //Getting general information
                $targets     = $av_scan->get_targets('scan_format');
                $targets_txt = Av_scan::targets_to_string($targets);

                //Database connection
                list($db, $conn) = Ossim_db::get_conn_db();

                $sensor     = Av_sensor::get_object($conn, $av_scan->get_sensor());
                $sensor_txt = $sensor->get_name().' ['.$sensor->get_ip().']';

                $db->close();

                $scan_status['message']             = sprintf(_('Scanning target/s: <strong>%s</strong> with sensor <strong>%s</strong>, please wait...'), $targets_txt, $sensor_txt);
                $scan_status['status']              = $status['status'];
                $scan_status['progress']['percent'] = round(($status['scanned_targets'] / $status['number_of_targets']) * 100);
                $scan_status['progress']['current'] = $status['scanned_targets'];
                $scan_status['progress']['total']   = $status['number_of_targets'];

                if (intval($status['remaining_time']) < 0)
                {
                    $scan_status['progress']['time'] = _('Calculating Remaining Time');
                }
                else
                {
                    $scan_status['progress']['time'] = Welcome_wizard::format_time($status['remaining_time']) . ' ' . _('remaining');
                }
            }

            $data['status'] = 'success';
            $data['data']   = $scan_status;

        break;


        case 'show_scan_report':

            if (file_exists($scan_report_file))
            {
                require_once 'scan_util.php';

                $scan_report = file_get_contents($scan_report_file);
                $scan_report = unserialize($scan_report);

                $data['status'] = 'success';
                $data['data']   = NULL;


                if (!empty($scan_report['scanned_ips']))
                {
                    ob_start();

                    //Database connection
                    list($db, $conn) = Ossim_db::get_conn_db();

                    scan2html($conn, $scan_report);

                    $data['data'] = ob_get_contents();

                    ob_end_clean();
                }
                else
                {
                    @unlink($scan_report_file);

                    $e_msg = _('Asset scan finished');
                    Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
                }
            }

        break;


        case 'download_scan_report':

            try
            {
                $av_scan = Av_scan::get_object_from_file($scan_file);

                $scan_report = $av_scan->download_scan_report();

                $db   = new ossim_db();
                $conn = $db->connect();

                if (!empty($scan_report))
                {
                    $nmap_parser = new Nmap_parser();
                    $scan_report = $nmap_parser->parse_json($scan_report, $av_scan->get_sensor());
                    $fqdns = ($_POST['rdns'] == '1') ? 1 : 0;

                    if ($fqdns == 1) {
                        foreach ($scan_report["scanned_ips"] as $host_ip => $host_arr) {
                            if ($host_arr["fqdn"] == "") {
                                $api_client  = new Alienvault_client();
                                $system_id = $scan_report["sensor"]["ctx"];
                                $response = $api_client->system($system_id)->get_fqdns($system_id, $host_ip);
                                $fqdn = json_decode($response, true)['data']['fqdn'];
                                $scan_report["scanned_ips"][$host_ip]["fqdn"] = $fqdn;
                            };
                        };
                    };

                    file_put_contents($scan_report_file, serialize($scan_report));
                }

                $av_scan->delete_scan();

                Cache_file::remove_file($scan_file);
            }
            catch(Exception $e)
            {
                ;
            }

            $data['status'] = 'success';
            $data['data']   = NULL;

        break;
    }

}
catch(Exception $e)
{
    Util::response_bad_request($e->getMessage());
}

echo json_encode($data);
