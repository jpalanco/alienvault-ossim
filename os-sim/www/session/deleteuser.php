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

Session::logcheck('configuration-menu', 'ConfigurationUsers');

$data = array (
    'status' => 'error', 
    'data'   => ''
);

if (!Token::verify('tk_delete_user', GET('token')))
{
    $data['data'] = Token::create_error_message();
    echo json_encode($data);
    exit();
}

$loguser = Session::get_session_user();
$user    = GET('user');

$db   = new ossim_db();
$conn = $db->connect();

$pro  = Session::is_pro();

ossim_valid($user, OSS_USER, 'illegal:' . _('User name'));

if (ossim_error())
{
    $data['data'] = ossim_get_error_clean();
}

$delete_perms = FALSE;

if (Session::am_i_admin())
{
	$delete_perms = TRUE;
}
else
{
    if ($pro && Acl::am_i_proadmin())
	{
		if (isset($_SESSION['_user_vision']['user']))
		{
			$my_users = $_SESSION['_user_vision']['user'];
		}
		else
		{
			$_SESSION['_user_vision'] = Acl::get_user_vision($conn);
			$my_users = $_SESSION['_user_vision']['user'];
		}
		
		$delete_perms = ($my_users[$user] == 2) ? TRUE : FALSE;
	
	}
}

if ($delete_perms == FALSE)
{
	$data['data'] = _('Permission error').' - '._('Only admin can do that');
	echo json_encode($data);
	exit();
}

if ($loguser == $user) 
{
    $data['data'] = _('Permission error').' - '._('A user can not remove himself');
    
	echo json_encode($data);
	exit();
}


//Remove associated PDF report 

$uuid = Session::get_secure_id($user);
$url  = "/usr/share/ossim/www/tmp/scheduler/$uuid";

if (is_dir($url) && !empty($uuid))
{
	exec("rm -r $url");
}
	
//Deleting user's tabs
User_config::delete_panels($conn, $user);
   
//Deleting the user
Session::delete_user($conn, $user);

Util::memcacheFlush();

$db->close();


$data['status']  = 'OK';
$data['data']    = _('User removed successfully');

echo json_encode($data);
exit();
?>