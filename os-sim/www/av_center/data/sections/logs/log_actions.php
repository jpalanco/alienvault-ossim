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
require_once (dirname(__FILE__) . '/../../../config.inc');
session_write_close();

$system_id  = POST('system_id');
$action     = POST('action');
$log_id     = POST('log_id');
$num_rows   = (POST('num_rows') == '') ? 50 : POST('num_rows');
$profiles   = (empty($_POST['profiles'])) ? array() : array_flip(explode(',', $_POST['profiles']));


ossim_valid($system_id,   OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));
ossim_valid($action, OSS_LETTER, '_',                 'illegal:' . _('Action'));
ossim_valid($log_id, OSS_LETTER, '_',                 'illegal:' . _('Log id'));
ossim_valid($num_rows, OSS_DIGIT,                     'illegal:' . _('Num Rows'));

if (ossim_error()) 
{
	$data['status']  = 'error';
	$data['data']    = ossim_get_error();
	
	echo json_encode($data);
	exit;
}

if ($action == 'view_log')
{
	session_start();
	
	if (!isset($_SESSION['log_files']))
	{
		$_SESSION['log_files'] = Av_center::get_available_logs();
		$log_files = $_SESSION['log_files'];
	}
	else
	{
		$log_files = $_SESSION['log_files'];
	}	
	
	session_write_close();
	
	if (empty($log_files[$log_id]))
	{
		$data['status']  = 'error';
		$data['data']    = _("Log file not found");
	}
	else
	{
		if (array_key_exists($log_files[$log_id]['section'], $profiles) ||  $log_files[$log_id]['section'] == 'system')
		{
			$data = Av_center::get_log_file($system_id, $log_id, $num_rows);
		}
		else
		{
			$data['status']  = 'error';
			$data['data']    = _("You don't have permission to view this log");
		}
	}
		
	echo json_encode($data);
	exit();
}
