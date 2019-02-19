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

$s_type = GET('s_type');

Session::logcheck('environment-menu', 'AlienVaultInventory');

/****************************************************
 ************** Configuration Options ***************
 ****************************************************/

$scan_types = array(
    'nmap' => 5,
    'wmi'  => 4
);


//Validation array

$validate = array (
    's_type'      => array('validation' => 'nmap,wmi',                         'e_message' => 'illegal:' . _('Scheduler Type')),
    'task_id'     => array('validation' => 'OSS_DIGIT, OSS_NULLABLE',          'e_message' => 'illegal:' . _('Task ID')),
    'task_name'   => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_SCORE',  'e_message' => 'illegal:' . _('Name')),
    'task_sensor' => array('validation' => 'OSS_HEX',                          'e_message' => 'illegal:' . _('Sensor')),
    'task_period' => array('validation' => 'OSS_DIGIT',                        'e_message' => 'illegal:' . _('Frequency'))
);

if ($s_type == 'nmap')
{
    $task_params = [];
    if (GET('task_params') != '')
    {
        $task_params = str_replace(' ', '', GET('task_params'));
        $_GET['task_params'] = preg_replace(array("/^!/","/,!/"),array("",","),$task_params);
    }

    if (POST('task_params') != '')
    {
        $task_params = str_replace(' ', '', POST('task_params'));
        $_POST['task_params'] = preg_replace(array("/^!/","/,!/"),array("",","),$task_params);
    }
    $validate['task_params']     = array('validation' => 'OSS_IP_CIDR',                                        'e_message' => 'illegal:' . _('Targets to scan'));
    $validate['scan_type']       = array('validation' => 'OSS_ALPHA, OSS_SCORE',                               'e_message' => 'illegal:' . _('Scan type'));
    $validate['timing_template'] = array('validation' => 'OSS_TIMING_TEMPLATE',                                'e_message' => 'illegal:' . _('Timing_template'));
    $validate['custom_ports']    = array('validation' => "OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, ','", 'e_message' => 'illegal:' . _('Custom Ports'));
    $validate['rdns']            = array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Reverse DNS resolution'));
    $validate['autodetect']      = array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Autodetect services and OS'));
}
elseif ($s_type == 'wmi')
{
    $validate['task_params'] = array('validation' => 'OSS_PASSWORD',  'e_message' => 'illegal:' . _('Credentials'));
}


/****************************************************
************** Checking field to field **************
*****************************************************/


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
        //Extended validation
        if ($_GET['name'] == 'task_params')
        {
            $params = GET($_GET['name']);

            if ($s_type == 'nmap')
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                if (!Asset_net::is_cidr_in_my_nets($conn, $params))
                {
                    $data = array(
                        'status' => 'error',
                        'data'   => array(
                            $_GET['name'] => sprintf(_("Error! The network %s is not allowed.  Please check your network settings"), Util::htmlentities($params))
                        )
                    );
                }

                $db->close();
            }
            elseif ($s_type =='wmi')
            {
                //Format example: wmihost:ip_address;wmiuser:user;wmipass:pass
                $pattern = '/\s*wmihost:(.*);wmiuser:(.*);wmipass:(.*)\s*/';

                preg_match($pattern, $params, $matches);
                $wmi_host = trim($matches[1]);
                $wmi_user = trim($matches[2]);
                $wmi_pass = trim($matches[3]);


                ossim_clean_error();

                if (!ossim_valid($wmi_host, OSS_IP_ADDR, 'illegal:' . _('WMI Host')))
                {
                    ossim_clean_error();
                    ossim_valid($wmi_host, OSS_HOST_NAME, 'illegal:' . _('WMI Host'));
                }

                ossim_valid($wmi_user, OSS_USER . '\\\/', 'illegal:' . _('WMI User'));
                ossim_valid($wmi_pass, OSS_PASSWORD, 'illegal:' . _('WMI Password'));

                if (ossim_error())
                {
                    $data = array(
                        'status' => 'error',
                        'data'   => array(
                            $_GET['name'] => sprintf(_("Error! The credential format is not allowed.  Please check the format again"))
                        )
                    );
                }
            }
        }

        if ($_GET['name'] == 'task_period')
        {
            $frequency = intval(GET($_GET['name']));

            if ($frequency < 1800)
            {
                $data = array(
                    'status' => 'error',
                    'data'   => array(
                        $_GET['name'] => sprintf(_('Invalid time between scans').'. <br/>'._('Entered value').": '<strong>%s</strong>' (1800(s) "._('minimum').")", Util::htmlentities($frequency))
                    )
                );
            }
        }
    }

    echo json_encode($data);
    exit();
}


/****************************************************
**************** Checking all fields ****************
*****************************************************/

//Checking form token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    $token = POST('token');

    if (Token::verify('tk_ss_form', $token) == FALSE)
    {
        Token::show_error();

        exit();
    }
}


$s_type       = POST('s_type');
$task_id      = intval(POST('task_id'));
$name         = POST('task_name');
$sensor_id    = POST('task_sensor');
$params       = POST('task_params');
$frequency    = POST('task_period');


$validation_errors = validate_form_fields('POST', $validate);


//Extra validations


if (empty($validation_errors))
{
    switch ($s_type)
    {
        case 'nmap':

            $db   = new ossim_db();
            $conn = $db->connect();

            $params    = POST('task_params');
            $sensor_id = POST('task_sensor');

            if (!Asset_net::is_cidr_in_my_nets($conn, $params))
            {
                $validation_errors['task_params'] = sprintf(_("Error! The network %s is not allowed.  Please check your network settings"), Util::htmlentities($params));
            }
            else if(!Asset_net::check_cidr_by_sensor($conn, $params, $sensor_id))
            {
                $validation_errors['task_params'] = _("You can't scan the specified network using this sensor");
            }

            $db->close();

        break;

        case 'wmi':

            $pattern = '/\s*wmihost:(.*);wmiuser:(.*);wmipass:(.*)\s*/';

            preg_match($pattern, $params, $matches);
            $wmi_host = trim($matches[1]);
            $wmi_user = trim($matches[2]);
            $wmi_pass = trim($matches[3]);

            ossim_clean_error();

            if (!ossim_valid($wmi_host, OSS_IP_ADDR, 'illegal:' . _('WMI Host')))
            {
                ossim_clean_error();
                ossim_valid($wmi_host, OSS_HOST_NAME, 'illegal:' . _('WMI Host'));
            }

            ossim_valid($wmi_user, OSS_USER . '\\\/', 'illegal:' . _('WMI User'));
            ossim_valid($wmi_pass, OSS_PASSWORD, 'illegal:' . _('WMI Password'));

            if (ossim_error())
            {
                $validation_errors['task_params'] = sprintf(_("Error! The credential format is not allowed.  Please check the format again"));
            }

        break;
    }

    if ($frequency < 1800)
    {
        $validation_errors['task_period'] = sprintf(_('Invalid time between scans').'. <br/>'._('Entered value').": '<strong>%s</strong>' (1800(s) "._('minimum').")", Util::htmlentities($frequency));
    }
}


$data['status'] = 'OK';
$data['data']   = $validation_errors;


if (POST('ajax_validation_all') == TRUE)
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
    }

    echo json_encode($data);
    exit();
}
else
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _('OSSIM Framework');?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache">
        <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(), 'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');
        ?>
    </head>

    <body>
    <?php

    if ($data['status'] != 'error')
    {
        switch ($s_type)
        {
            case 'nmap':
                $targets = str_replace(' ', '', $task_params);
                $targets = str_replace("\n", ' ', $targets);
                $targets = str_replace(',', ' ', $targets);

                $nmap_options     = array();

                $scan_type        = POST('scan_type');
                $timing_template  = POST('timing_template');
                $custom_ports     = POST('custom_ports');
                $rdns             = (POST('rdns') == '1') ? 1 : 0;
                $autodetect       = (POST('autodetect') == '1') ? 1 : 0;

                $nmap_options[]   = '-'.$timing_template;

                // Append Autodetect
                if ($autodetect)
                {
                    $nmap_options[] = '-A';
                }
                // Append RDNS
                if (!$rdns)
                {
                    $nmap_options[] = '-n';
                }
                if ($scan_type == 'fast')
                {
                    $nmap_options[] = '-sV -p21,22,23,25,53,80,113,115,135,139,161,389,443,445,554,1194,1241,1433,3000,3306,3389,8080,9390,27017';
                }
                elseif ($scan_type == 'custom')
                {
                    $nmap_options[] = "-sS -sV -p $custom_ports";
                }
                elseif ($scan_type == 'normal')
                {
                    $nmap_options[] = '-sS -sV';
                }
                elseif ($scan_type == 'full')
                {
                    $nmap_options[] = '-sV -sS -p1-65535';
                }
                else
                {
                    $nmap_options[] = '-sn -PE';
                }

                $params = $targets.'#'.implode(' ', $nmap_options);

            break;

            case 'wmi':
                preg_match('/wmipass:(.*)/', $params, $matches);

                if ($matches[1] != '' && preg_match('/^\*+$/', $matches[1]) && $_SESSION['wmi_pass'] != '')
                {
                    $params = preg_replace('/wmipass:(.*)/', '', $params);
                    $params = $params . 'wmipass:' . $_SESSION['wmi_pass'];
                }
            break;

            default:
                $targets = NULL;
                $params  = NULL;
        }


        $db   = new ossim_db();
        $conn = $db->connect();
        try
        {
            if ($task_id != '')
            {
                Inventory::modify($conn, $task_id, $sensor_id, $name, $scan_types[$s_type], $frequency, $params, $targets);
            }
            else
            {
                $task_id = Inventory::insert($conn, $sensor_id, $name, $scan_types[$s_type], $frequency, $params, $targets);
            }
        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
            $data['data']   = array ('php_exception' => $e->getMessage());
        }

        $db->close();
    }


    if ($data['status'] == 'error')
    {
        $txt_error = '<div>'._('The following errors occurred').":</div>
                      <div style='padding: 10px;'>".implode('<br/>', $data['data']).'</div>';

        $config_nt = array(
            'content' => $txt_error,
            'options' => array (
                'type'           => 'nf_error',
                'cancel_button'  => FALSE
            ),
            'style'    =>  'width: 80%; margin: 20px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    else
    {
        ?>
        <script type='text/javascript'>
            document.location.href = '/ossim/av_schedule_scan/views/schedule_scan_form.php?task_id=<?php echo $task_id?>&msg=saved&s_type=<?php echo $s_type?>';
        </script>
        <?php
    }
    ?>
    </body>
</html>
