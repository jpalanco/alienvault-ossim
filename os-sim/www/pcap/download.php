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


ini_set("max_execution_time","300"); 

require_once 'av_init.php';

Session::logcheck('environment-menu', 'TrafficCapture');

$scan_name = GET('scan_name');
$sensor_ip = GET('sensor_ip');

ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _('Scan name'));
ossim_valid($sensor_ip, OSS_IP_ADDR,                                 'illegal:' . _('Sensor ip'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$scan_info = explode('_', $scan_name);
$users = Session::get_users_to_assign($dbconn);

$my_users = array();
foreach( $users as $k => $v )
{
    $my_users[$v->get_login()] = 1;  
}

if($my_users[$scan_info[1]] != 1 && !Session::am_i_admin())
{
    return;
}

$scan = new Traffic_capture();

$file = $scan->get_pcap_file($scan_name, $sensor_ip);


if(preg_match("/^E/i",$file))
{ 
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title> <?php echo gettext("OSSIM Framework"); ?> - Traffic capture </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id()?>"/>
    </head>
    <body>
    <?php
    $config_nt = array(
            'content' => $file,
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
        );

    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    ?>
    </body>
    </html>
    <?php
}
else if(file_exists($file))
{
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: no-cache'); // no-cache, public
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Content-Description: File Transfer');
    header('Content-Type: application/binary');
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: inline; filename='.$scan_name);
    readfile($file);
}
// Clean temp files 
if (file_exists($file))
{
    unlink($file);
}

$db->close();

?>
