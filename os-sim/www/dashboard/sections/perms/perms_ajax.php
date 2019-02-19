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
$cond1 = Session::logcheck_bool("dashboard-menu", "ControlPanelExecutive");
$cond1 = $cond1 && Session::logcheck_bool("dashboard-menu", "ControlPanelExecutiveEdit");
$cond2 = !Session::am_i_admin();
$cond3 = Session::is_pro() && !Acl::am_i_proadmin();

if (!$cond1 && $cond2 && $cond3)
{
	$response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');
    
    echo json_encode($response);
    exit -1;
}

require_once AV_MAIN_ROOT_PATH . '/dashboard/sections/widgets/widget_common.php';


/****************************************************************************************************************/
/*																												*/
/************************************************ TABS FUNTIONS *************************************************/
/*																												*/
/****************************************************************************************************************/

function clone_tab($data)
{
	$from  = $data['from'];
	$to    = $data['to'];
	$panel = $data['panel'];
	$notif = $data['notif'];
	
	ossim_valid($from	, OSS_USER					, 'illegal:' . _("User Origin"));
	ossim_valid($to	    , OSS_USER					, 'illegal:' . _("User Destiny"));
	ossim_valid($panel	, OSS_DIGIT					, 'illegal:' . _("Tab ID"));
	ossim_valid($notif	, OSS_DIGIT, OSS_NULLABLE	, 'illegal:' . _("Tab ID"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();

		$return['error'] = TRUE ;
		$return['msg']   = $info_error;

		return $return;
	}

	if(!get_user_valid($from) || !get_user_valid($to))
	{
		$return['error'] = TRUE ;
		$return['msg']   = _('You do not have permission to clone this tab');
		return $return;
	}

	try
	{
		$tab     = new Dashboard_tab($panel, $from);

		$_cloned = $tab->clone_tab($to);
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE ;
		$return['msg']   = $e->getMessage();

		return $return;
    }

    if($notif)
    {
    	$_SESSION['_db_perms_msg_index'] = 1;
	}

    $data['msg']   = _("Tab cloned successfully");
    $data['id']    = $_cloned->get_id();
    $data['user']  = $_cloned->get_user();
    $data['title'] = $_cloned->get_title();


	$return['error']  = FALSE ;
	$return['data']   = $data;

	return $return;
} 


function clone_tab_entity($conn, $data)
{	
	$from   = $data['from'];
	$entity = $data['to'];
	$panel  = $data['panel'];
	
	ossim_valid($from	, OSS_USER	, 'illegal:' . _("User Origin"));
	ossim_valid($entity , OSS_HEX	, 'illegal:' . _("User Destiny"));
	ossim_valid($panel	, OSS_DIGIT	, 'illegal:' . _("Tab ID"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE ;
		$return['msg']   = $info_error;

		return $return;
	}
	
	if(!get_user_valid($from) && !get_entity_valid($entity))
	{
		$return['error'] = TRUE ;
		$return['msg']   = _('You do not have permission to clone this tab');
		return $return;
	
	}
	
	try 
	{
		$tab = new Dashboard_tab($panel, $from);
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE ;
		$return['msg']   = $e->getMessage();

		return $return;
    }


	
	$users = Acl::get_all_users_by_entity($conn, $entity);
			
	foreach($users as $user)
	{
		if($from == $user) 
		{
			continue;
		}
		
		try 
		{
			$tab->clone_tab($user);
		}
		catch (Exception $e)
	    {
	    	$return['error'] = TRUE ;
			$return['msg']   = $e->getMessage();

			return $return;
	    }
	}
	
	$_SESSION['_db_perms_msg_index'] = 1;

	$return['error'] = FALSE ;
	$return['msg']   = _("Tab Cloned Successfully");

	return $return;
}


function clone_tab_all($conn, $data)
{ 
	$panel   = $data['panel'];
	$from    = $data['user'];
		
	ossim_valid($from	, OSS_USER	, 'illegal:' . _("User Origin"));
	ossim_valid($panel	, OSS_DIGIT	, 'illegal:' . _("Tab ID"));
	
	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE ;
		$return['msg']   = $info_error;

		return $return;
	}
	
	if(!get_user_valid($from))
	{
		$return['error'] = TRUE ;
		$return['msg']   = _('You do not have permission to clone this tab');
		return $return;
	
	}

	try 
	{
		$tab = new Dashboard_tab($panel, $from);
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE ;
		$return['msg']   = $e->getMessage();

		return $return;
    }

    $users = Session::get_users_to_assign($conn);

    if(count($users) == 1 && $users[0]->login == $from)
    {
    	$return['error'] = TRUE ;
		$return['msg']   = 'unique_user';

		return $return;
    }

	foreach($users as $user)
	{
		if($from == $user->login) 
		{
			continue;
		}
		
		try 
		{
			$tab->clone_tab($user->login);
		}
		catch (Exception $e)
	    {
	    	$return['error'] = TRUE ;
			$return['msg']   = $e->getMessage();

			return $return;
	    }
	}
	
	$_SESSION['_db_perms_msg_index'] = 1;

	$return['error'] = FALSE ;
	$return['msg']   = _("Tab Cloned Successfully");

	return $return;
		
} 


function delete_tab($data)
{
	$panel   = $data['panel'];
	$user    = $data['user'];

	ossim_valid($panel,		OSS_DIGIT, 		'illegal:' . _("Tab"));
	ossim_valid($user, 		OSS_USER,		'illegal:' . _("User"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE ;
		$return['msg']   = $info_error;

		return $return;
	}
	
	if(!get_user_valid($user))
	{
		$return['error'] = TRUE ;
		$return['msg']   = _('You do not have permission to delete this tab');

		return $return;
	}
	
	try 
	{
		$tab = new Dashboard_tab($panel, $user);

		if($tab->is_locked())
		{
			$return['error'] = TRUE ;
			$return['msg']   = _("You cannot modify this tab");

			return $return;
		}

		$tab->delete();
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE ;
		$return['msg']   = $e->getMessage();

		return $return;
    }

	$return['error'] = FALSE ;
	$return['msg']   = _("Tab deleted successfully");

	return $return;
}



function change_disable_option($data)
{
	$panel   = $data['panel'];
	$user    = $data['user'];
	
	ossim_valid($panel, OSS_DIGIT, 'illegal:' . _("Tab"));
	ossim_valid($user,  OSS_USER,  'illegal:' . _("User"));
	

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();

		$return['error'] = TRUE ;
		$return['msg']   = $info_error;

		return $return;
	}

	if(!get_user_valid($user))
	{
		$return['error'] = TRUE ;
		$return['msg']   = _('You do not have permission to modify this tab');

		return $return;
	}

	try
	{
		$tab = new Dashboard_tab($panel, $user);

		$tab->set_visible(1 - intval($tab->is_visible()));

		$tab->save_db();

	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE ;
		$return['msg']   = $e->getMessage();

		return $return;
    }

	$return['error'] = FALSE ;
	$return['msg']   = _("Visibility Option Changed Successfully");

	return $return;
}



/****************************************************************************************************************/
/*																												*/
/*********************************************** PERMS FUNTIONS *************************************************/
/*																												*/
/****************************************************************************************************************/

function get_user_valid($user)
{
	if (Session::is_pro())
	{
		$user_list = $_SESSION['_user_vision']['users_to_assign'];

		return (isset($user_list[$user]));		
	} 
	else
	{
		return Session::am_i_admin();
	}
	
	return FALSE;
	
}


function get_entity_valid($entity)
{
	$entity_list = $_SESSION['_user_vision']['entities_to_assign'];

	return (isset($entity_list[$entity]));
}




/***************************************************************************************************/
/***************************************************************************************************/
/*                                         MAIN                                                    */
/***************************************************************************************************/
/***************************************************************************************************/

$action = POST("action");
$data   = POST("data");

ossim_valid($action,	OSS_INPUT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    $response['error'] = TRUE ;
	$response['msg']   = ossim_error();
	ossim_clean_error();
}


$db                = new ossim_db();
$conn              = $db->connect();

$response['error'] = TRUE ;
$response['msg']   = _('Error when processing the request');


if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	if ( !Token::verify('tk_dashboard_perms_ajax', GET('token')) )
	{		
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
		switch($action)
		{
			case 'delete_tab':
				$response = delete_tab($data);
			break;
			
			case 'clone_tab':
				$response = clone_tab($data);
			break;
			
			case 'clone_tab_entity':
				$response = clone_tab_entity($conn, $data);
			break;
				
			case 'clone_tab_all':
				$response = clone_tab_all($conn, $data);
			break;

			case 'change_visibility':
				$response = change_disable_option($data);
			break;

			default:
				$response['error'] = TRUE ;
				$response['msg']   = _('Wrong Option Chosen');
		}
	}
}

echo json_encode($response);

$db->close();
