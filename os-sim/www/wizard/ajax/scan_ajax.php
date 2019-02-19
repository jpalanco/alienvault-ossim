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

require_once 'av_init.php';

//First we check we have session active
Session::useractive();

//Then we check the permissions
if (!Session::am_i_admin())
{
    $response['error']    = TRUE;
    $response['critical'] = TRUE;
    $response['msg']      = _('You do not have permissions to see this section');

    echo json_encode($response);

    exit -1;
}



/*
 * <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------>
 */

function set_scan_error_message($wizard, $msg)
{
    $wizard->set_step_data('scan_step', -1);

    $wizard->set_step_data('scan_error_msg', $msg);

    $wizard->save_status();
}


function modify_scan_networks($conn, $wizard, $data)
{
    $step = intval($wizard->get_step_data('scan_step'));

    if ($step == 1 || $step == 2)
    {
        $response['error'] = TRUE;
        $response['msg']   = _('There is a Asset scan running, you have to wait until it completes.');

        return $response;
    }


    $ids = array_keys($data['nets']);

    ossim_valid($ids,   OSS_HEX,    'illegal:' . _("Network ID"));

    if (ossim_error())
    {
        $response['error'] = TRUE ;
        $response['msg']   = ossim_get_error();

        ossim_clean_error();

        return $response;
    }

    $nets     = array();
    $ip_count = 0;

    foreach ($ids as $id)
    {
        $cidrs = Asset_net::get_ips_by_id($conn, $id);

        $cidrs = preg_replace('/\s*,\s*/', ' ', $cidrs);

        $nets[$id] = trim($cidrs);

        $cidr_list = explode(' ', $cidrs);

        foreach ($cidr_list as $cidr)
        {
            list($dir, $mask) = explode('/', $cidr);

            if ($mask > 0 && $mask <= 32)
            {
                $ip_count += 1 << (32 - $mask);
            }
        }
    }

    $wizard->clean_step_data();

    $wizard->set_step_data('scan_step', 0);

    $wizard->set_step_data('scan_nets', $nets);

    $wizard->set_step_data('scan_ips_count', $ip_count);

    $wizard->save_status();


    $response['error']         = FALSE ;
    $response['data']['total'] = Util::number_format_locale($ip_count);

    return $response;
}


function do_scan($wizard)
{
    try
    {
        $next_step = 1;
        $data = array('finish' => FALSE);

        //File to cache scan object
        $user      = Session::get_session_user();
        $scan_file = 'w_last_asset_object-'.md5($user);

        $step = intval($wizard->get_step_data('scan_step'));

        if ($step == 0)
        {
            @unlink($scan_file);
        }

        $obj = Av_scan::get_object_from_file($scan_file);

        if (!is_object($obj) || empty($obj))
        {
            $nets = $wizard->get_step_data('scan_nets');

            if (count($nets) < 1)
            {
                $e_msg = _('Invalid networks selected to scan');
                Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
            }

            $nets = implode(' ', $nets);

            $scan_options = array(
                'scan_type'     => 'fast',
                'scan_timing'   => 'T3',
                'autodetect_os' => 'true',
                'reverse_dns'   => 'true',
                'scan_ports'    => '',
                'idm'           => 'false'
            );

            $obj = new Av_scan($nets, 'local', $scan_options);
            $obj->run();

            Av_scan::set_object_in_file($obj, $scan_file);
        }

        $aux_status = $obj->get_status();

        $status = $aux_status['status']['code'];
        $total  = $aux_status['number_of_targets'];

        switch($status)
        {
            case Av_scan::ST_SEARCH_FINISHED:
            case Av_scan::ST_SCANNING_HOSTS:
            case Av_scan::ST_SCAN_FINISHED:

                $next_step = ($total > 0) ? 2 : 3;

                $wizard->set_step_data('scan_hosts', $total);

                $data = array('finish' => TRUE);

            break;
        }


        $wizard->set_step_data('scan_step', $next_step);

        //error_log("Step: $step\n", 3, '/tmp/test_wizard');
        //error_log("Next Step: $next_step\n", 3, '/tmp/test_wizard');
        //error_log(var_export($aux_status, TRUE)."\n", 3, '/tmp/test_wizard');

        $response['error'] = FALSE;
        $response['data']  = $data;

        $wizard->save_status();
    }
    catch(Exception $e)
    {
        //error_log("Error: ".$e->getMessage()."\n", 3, '/tmp/test_wizard');

        $msg = _('Error! Asset scan cannot be completed.  Please try again');
        set_scan_error_message($wizard, $msg);

        $response['error'] = TRUE;
    }

    return $response;
}


function check_scan_progress($conn, $wizard)
{
    //File to cache scan object
    $user      = Session::get_session_user();
    $scan_file = 'w_last_asset_object-'.md5($user);

    $data = array();

    try
    {
        $obj = Av_scan::get_object_from_file($scan_file);

        //Get status
        $aux_status = $obj->get_status();
        $status     = $aux_status['status']['code'];

        if ($status == Av_scan::ST_SCAN_FINISHED)
        {
            //Scanning has finished properly
            $info = array();

            $scan_report = $obj->download_scan_report();

            //Delete scan
            $obj->delete_scan();
            Cache_file::remove_file($scan_file);

            //Parsing scan report
            $nmap_parser = new Nmap_parser();
            $scan_report = $nmap_parser->parse_json($scan_report, $obj->get_sensor());

            // Add summary 
            $scan_report['nmap_data']['elapsed'] = $aux_status['elapsed_time'];

            $info = Welcome_wizard::format_result_scan($conn, $scan_report);

            $wizard->set_step_data('scan_step', 3);
            $wizard->set_step_data('scan_info', $info);

            $data['finish'] = TRUE;
        }
        else
        {
            $percent  = ($aux_status['scanned_targets'] / $aux_status['number_of_targets']) * 100;

            $data['finish']  = FALSE;
            $data['percent'] = round($percent);
            $data['current'] = $aux_status['scanned_targets'];
            $data['total']   = $aux_status['number_of_targets'];

            if ($aux_status['remaining_time'] == -1)
            {
                $data['time'] = _('Calculating Remaining Time');
            }
            else
            {
                $data['time'] = Welcome_wizard::format_time($aux_status['remaining_time']) . ' ' . _('remaining');
            }
        }


        $response['error'] = FALSE;
        $response['data']  = $data;

        $wizard->save_status();
    }
    catch(Exception $e)
    {
        $msg = _('Error! Asset scan cannot be completed.  Please try again');
        set_scan_error_message($wizard, $msg);

        $response['error'] = TRUE;
    }

    return $response;
}


function cancel_scan($wizard)
{
    //File to cache scan object
    $user      = Session::get_session_user();
    $scan_file = 'w_last_asset_object-'.md5($user);

    $step = intval($wizard->get_step_data('scan_step'));

    if ($step == 1 || $step == 2)
    {
        $obj = Av_scan::get_object_from_file($scan_file);

        $obj->stop();

        $obj->delete_scan();

        Cache_file::remove_file($scan_file);
    }

    $wizard->clean_step_data();

    $wizard->save_status();

    $response['error'] = FALSE;

    return $response;
}


function schedule_scan($conn, $wizard, $data)
{
    $step = intval($wizard->get_step_data('scan_step'));
    $nets = $wizard->get_step_data('scan_nets');

    if ($step != 3 || count($nets) < 1)
    {
        $response['error'] = TRUE ;
        $response['msg']   = _('Asset Scan not valid to schedule');

        return $response;
    }

    $sched = $data['sch_opt'];

    ossim_valid($sched,   OSS_DIGIT,    'illegal:' . _("Schedule Option"));

    if (ossim_error())
    {
        $response['error'] = TRUE ;
        $response['msg']   = ossim_get_error();

        ossim_clean_error();

        $wizard->set_step_data('scan_nets', -1);

        return $response;
    }

    if ($sched == 1) //Daily
    {
        $period = 86400;
    }
    elseif ($sched == 2) //Weekly
    {
        $period = 604800;
    }
    else  //Monthly
    {
        $period = 2419200;
    }

    $sensor_id = Av_sensor::get_default_sensor($conn);
    $name      = _('Default_wizard_scan');
    $type      = 5;

    $targets   = array();

    foreach ($nets as $cidrs)
    {
        $cidrs = explode(' ', $cidrs);

        foreach ($cidrs as $cidr)
        {
            $targets[$cidr] = $cidr;
        }

    }
    $params = Util::nmap_with_excludes($targets,array("-sL","-sn","-PE","-n","-T3"));

    Inventory::insert($conn, $sensor_id, $name, $type, $period, $params, $targets);

    $response['error'] = FALSE;
    $response['data']  = array();

    return $response;
}


/*
 * <------------------------   END OF THE FUNCTIONS   ------------------------>
 */





/*
 * <-------------------------   BODY OF THE SCRIPT   ------------------------->
 */

$action = POST("action");   //Action to perform.
$data   = POST("data");     //Data related to the action.


ossim_valid($action,    OSS_INPUT,  'illegal:' . _("Action"));

if (ossim_error())
{
    $response['error'] = TRUE ;
    $response['msg']   = ossim_get_error();
    ossim_clean_error();

    echo json_encode($response);

    die();
}

//Default values for the response.
$response['error'] = TRUE ;
$response['msg']   = _('Error when processing the request');

//checking if it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token
    if ( !Token::verify('tk_welcome_wizard', GET('token')) )
    {
        $response['error'] = TRUE ;
        $response['msg']   = _('Invalid Action');
    }
    else
    {
        //List of all the possibles functions
        $function_list = array
        (
            'scan_networks'   => array('name' => 'modify_scan_networks',  'params' => array('conn', 'wizard', 'data')),
            'scan_progress'   => array('name' => 'check_scan_progress',   'params' => array('conn', 'wizard')),
            'do_scan'         => array('name' => 'do_scan',               'params' => array('wizard')),
            'cancel_scan'     => array('name' => 'cancel_scan',           'params' => array('wizard')),
            'schedule_scan'   => array('name' => 'schedule_scan',         'params' => array('conn', 'wizard', 'data'))
        );

        $_function = $function_list[$action];

        //Checking we have a function associated to the action given
        if (is_array($_function) && function_exists($_function['name']))
        {
            $wizard = Welcome_wizard::get_instance();

            if (is_object($wizard))
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                //Now we translate the params list to a real array with the real parameters
                $params = array();
                foreach($_function['params'] as $p)
                {
                    $params[] = $$p;
                }

                try
                {
                    //Calling to the function
                    $response = call_user_func_array($_function['name'], $params);

                    if ($response === FALSE)
                    {
                        throw new Exception(_('Sorry, operation was not completed due to an error when processing the request. Try again later'));
                    }
                }
                catch(Exception $e)
                {
                    $response['error']    = TRUE;
                    $response['critical'] = TRUE;
                    $response['msg']      = $e->getMessage();
                }

                $db->close();
            }
            else
            {
                $response['error']    = TRUE;
                $response['critical'] = TRUE;
                $response['msg']      = _('Sorry, operation was not completed due to an error when processing the request. Try again later');
            }
        }
        else
        {
           $response['error']    = TRUE;
           $response['critical'] = TRUE;
           $response['msg']      = _('Wrong Option Chosen');
        }
    }
}

//Returning the response to the AJAX call.
echo json_encode($response);

