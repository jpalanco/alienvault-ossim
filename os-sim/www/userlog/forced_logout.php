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


require_once ('av_init.php');
require_once ('ossim_db.inc');

Session::useractive();
$id         = POST('id');
$my_session = session_id();

$db         = new ossim_db();
$dbconn     = $db->connect();

if ( $id == $my_session )
{	
	$data['status'] = 'error';
    $data['data']   = _("Autologout is not allowed"); 
        
    echo json_encode($data);    
    exit();
}

//Now, we are gonna check if we can force the logout of the user:
$allowed_users = array();
$flag_delete   = false;

if  ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin()) )
{
	if ( Session::am_i_admin() ){
		$users_list = Session::get_list($dbconn, "ORDER BY login");
    }
	else{
		$users_list = Acl::get_my_users($dbconn,Session::get_session_user());
    }
	
	
	if ( is_array($users_list) && !empty($users_list) )
	{
		foreach($users_list as $k => $v){
			$users[] = ( is_object($v) )? $v->get_login() : $v["login"];
        }
		
		$where = "WHERE login in ('".implode("','",$users)."')";
	}
}
else{ 
	$where = "WHERE login = '".Session::get_session_user()."'";
}

$allowed_users = Session_activity::get_list($dbconn, $where." ORDER BY activity desc");

foreach ($allowed_users as $user)
{
	if ($user->get_id() == $id)
	{
		$flag_delete = true;
		break;
	}
}

if( $flag_delete )
{
	$res1 = Session_activity::delete($id);
	$res2 = Session_activity::delete_session($id);
}

		
if ( $res1 && $res2 )
{
	$data['status'] = 'OK';
	$data['data']   = _("Logout successfully"); 
	
}
else
{
	$data['status'] = 'error';
	$data['data']   = _("Logout unsuccessfully"); 
}

$db->close($dbconn);

echo json_encode($data);    
exit();
?>