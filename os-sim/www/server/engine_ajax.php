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

if (!Session::logcheck_bool('configuration-menu', 'PolicyServers') || !Session::is_pro())
{
	$return['error'] = TRUE;
	$return['msg']   = _('You do not have permission to achieve this action');
}

$mssp = intval($conf->get_conf('alienvault_mssp'));

if (!$mssp)
{
	$return['error'] = TRUE;
	$return['msg']   = _("Only MSSP configuration is allowed to achieve this action");
}



function change_ctx_engine($conn, $data)
{
	$old_engine = $data['old_engine'];
	$new_engine = $data['new_engine'];
	$ctx        = $data['ctx'];
	
	ossim_valid($old_engine, 	OSS_HEX,	'illegal:' . _('Prev Engine ID'));
	ossim_valid($new_engine, 	OSS_HEX,	'illegal:' . _('New Engine ID'));
	ossim_valid($ctx, 			OSS_HEX, 	'illegal:' . _('Context ID'));
		
	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
    Acl::change_engine($conn, $ctx, $old_engine, $new_engine);    

	$return['error'] = FALSE;
	$return['data']  = _('Error! Engine could not be changed');
	
	return $return;  
} 



function change_engine_name($conn, $data)
{
	$engine = $data['engine'];
	$name   = $data['name'];
	
	ossim_valid($engine, 	OSS_HEX, 					'illegal:' . _('Engine ID'));
	ossim_valid($name , 	OSS_ALPHA, OSS_PUNC_EXT, 	'illegal:' . _('Engine Name'));
		
	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}

	if(Session::get_entity_type($conn, $engine) != 'engine')
	{
		$return['error'] = TRUE;
		$return['msg']   = _('You can only modify name to engines');
		
		return $return;
	}
	
    Acl::rename_entity($conn, $engine, $name);
	
	Web_indicator::set_on('Reload_servers');

	$return['error'] = FALSE;
	$return['data']  = '';
	
	return $return;  
} 


function insert_engine($conn, $data)
{ 
	$server = $data['server'];
	$name   = $data['name'];
	
	ossim_valid($server, 	OSS_HEX, 					'illegal:' . _('Engine ID'));
	ossim_valid($name , 	OSS_ALPHA, OSS_PUNC_EXT, 	'illegal:' . _('Engine Name'));
		
	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		return $return;
	}
	
    $id = Acl::insert_entities($conn, 'engine', 'admin', $name, '', array(), array(), array(), '', '', $server);
    
    Alarm::clone_taxonomy($conn,$id);
    
    $id = Util::uuid_format($id);
    
    if (!is_dir(_MAIN_PATH."/$id"))
    {
        Directive_editor::init_engine($id);
    }
	
	Web_indicator::set_on('Reload_servers');

	$return['error'] = FALSE;
	$return['data']  = '';
	
	return $return;  
} 


function delete_engine($conn, $data)
{ 
	$id = $data['engine'];
		
	ossim_valid($id, OSS_HEX, 'illegal:' . _('Engine ID'));
		
	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	if($id == Session::get_default_engine($conn))
	{
		$return['error'] = TRUE;
		$return['msg']   = _('It is not allowed to delete the default engine');
		
		return $return;
	}
	
	$contexts = Acl::get_contexts_by_engine($conn, $id);
	
	if(count($contexts) > 0)
	{
		$return['error'] = TRUE;
		$return['msg']   = _('There are contexts asociated to this engine. You are not allowed to delete this engine');
		
		return $return;
	}
	
	
    Acl::delete_entities($conn,$id);
    
    Alarm::delete_from_taxonomy($conn,$id);
	
	$id = Util::uuid_format($id);
	
    if (is_dir(_MAIN_PATH."/$id"))
    {
    		Directive_editor::remove_engine($id);
    }
    
	Web_indicator::set_on('Reload_servers');

	$return['error'] = FALSE;
	$return['data']  = '';
	
	return $return;  
} 


$login = Session::get_session_user();
$db    = new ossim_db();
$conn  = $db->connect();

$action = POST('action');
$data   = POST('data');

ossim_valid($action,	OSS_DIGIT,	'illegal:' . _('Action'));

if (ossim_error()) 
{
    die(ossim_error());
}

if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	if(!Session::is_pro() || (!Session::am_i_admin() && !Acl::am_i_proadmin())) 
	{
		$response['error'] = TRUE;
		$response['msg']   = _('You do not have permission to do this action');
	}	
	else
	{
		switch($action)
		{		
			case 1:			
				$response = change_ctx_engine($conn, $data);			
			break;
		
			case 2:			
				$response = change_engine_name($conn, $data);			
			break;
			
			case 3:			
				$response = insert_engine($conn, $data);			
			break;
				
			case 4:			
				$response = delete_engine($conn, $data);			
			break;
						
										
			default:
				$response['error'] = TRUE;
				$response['msg']   = _('Wrong Option Chosen');
		}
	}
	
}
else
{
	$response['error'] = TRUE;
	$response['msg']   = _('Invalid Request');
}

if(!$response['error'])
{
	Util::memcacheFlush();
}
echo json_encode($response);


$db->close();  
?>