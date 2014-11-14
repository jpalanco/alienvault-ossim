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


set_include_path('/usr/share/ossim/include');

require_once 'av_init.php';


$user   = $argv[1];
$file   = $argv[2];

$db     = new ossim_db();
$conn   = $db->connect();
$config = new User_config($conn);


if (!preg_match("/^\/var\/tmp\//",$file) && !preg_match("/^\/tmp\//",$file)) 
{
	echo "Error: 'file' parameter must be a valid /tmp file\n";
	exit;
}

if (!file_exists($file)) {
	echo "Error: '$file' file does not exist\n";
	exit;
}

$pid = @shell_exec("(cat '$file' | ossim-db; rm -f '$file'; echo 'flush_all' | /bin/nc -q 2 127.0.0.1 11211; sleep 1) > /tmp/alarm_bg_result 2>&1 & echo $!");

$config->set($user, 'background_task', $pid, 'simple', 'alarm');

$db->close($conn);

