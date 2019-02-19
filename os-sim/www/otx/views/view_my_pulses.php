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

Session::logcheck("dashboard-menu", "IPReputation");

$type        = GET('type');
$id          = GET('id');
$event_alarm = GET('alarm_event');

ossim_valid($type,          'alarm|event|alarm_event',  'illegal:' . _('Type'));
ossim_valid($id,            OSS_HEX,                    'illegal:' . _('ID'));
ossim_valid($event_alarm,   OSS_HEX, OSS_NULLABLE,      'illegal:' . _('Alarm Event'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db(TRUE);
$conn = $db->connect();


$p_list = array();
$r_list = array();

if ($type == 'alarm')
{
    $p_list = Alarm::get_alarm_pulses($conn, $id, TRUE);
    $r_list = Alarm::get_alarm_reputation($conn, $id, TRUE);
}
elseif ($type == 'event')
{
    $p_list = Siem::get_event_pulses($conn, $id, FALSE, TRUE);
    $r_list = Siem::get_event_reputation($conn, $id, FALSE, TRUE);
}
elseif ($type == 'alarm_event')
{
    $p_list = Siem::get_event_pulses($conn, $id, $event_alarm, TRUE);
    $r_list = Siem::get_event_reputation($conn, $id, TRUE, TRUE);
}

$otx_info = array(
    'type'       => $type,
    'id'         => $id,
    'pulse_list' => $p_list,
    'rep_list'   => $r_list
);

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('Open Threat Exchange Configuration') ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',             'def_path' => TRUE),
            array('src' => 'tipTip.css',                'def_path' => TRUE),
            array('src' => 'jquery.select.css',         'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',     'def_path' => TRUE),
            array('src' => 'av_common.css',             'def_path' => TRUE),
            array('src' => 'otx/av_ioc_view.css',       'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                     'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',              'def_path' => TRUE),
            array('src' => 'notification.js',                   'def_path' => TRUE),
            array('src' => 'utils.js',                          'def_path' => TRUE),
            array('src' => 'token.js',                          'def_path' => TRUE),
            array('src' => 'jquery.select.js',                  'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',              'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',             'def_path' => TRUE),
            array('src' => '/otx/js/av_otx_pulse_view.js.php',  'def_path' => FALSE),
        );

        Util::print_include_files($_files, 'js');  
    ?>

    <script type='text/javascript'>
                
        $(document).on('ready', function()
        {
            $('#ioc_view').AV_otx_pulse_view(<?php echo json_encode($otx_info) ?>);
        });
        
    </script>
</head>

<body>
    <div id='ioc_view_notif'></div>
    <div id='ioc_view'></div>
</body>
</html>