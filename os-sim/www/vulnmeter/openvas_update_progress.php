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

require_once 'av_init.php';
Session::logcheck("environment-menu", "EventsVulnerabilitiesScan");


$conf = $GLOBALS["CONF"];
$host = $conf->get_conf("nessus_host", FALSE);
$user = $conf->get_conf("nessus_user", FALSE);
$pass = $conf->get_conf("nessus_pass", FALSE);
$port = $conf->get_conf("nessus_port", FALSE);
$omp  = new Omp($host, $port, $user, $pass);

$data = array();

$cmd = "ps ax | grep updateplugins.pl | egrep -v 'grep'";

$output = Util::execute_command($cmd, FALSE, 'array');

$data['running'] = (preg_match('/updateplugins/',$output[0])) ? 'yes' : 'no';
$data['lines']   = '';

if ($data['running'] == 'yes' && file_exists('/var/tmp/openvas_update'))
{    
    $all_lines = array_map("trim", file('/var/tmp/openvas_update'));
    
    $data['lines'] = array_diff($all_lines, $_SESSION['openvas_update_last_lines']);
    
    $data['lines'] = implode("<br />", $data['lines']);
    
    $_SESSION['openvas_update_last_lines'] = $all_lines;
}
elseif ($omp->are_there_pending_tasks())
{
    $data['running'] = 'pending';
    $data['lines']   = _('Unable to launch REPAIR SCANNER DB, because there are running tasks.');
}
else
{
   $data['lines'] = '';
}

echo json_encode($data);

