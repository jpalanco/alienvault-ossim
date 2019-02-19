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

Session::logcheck('configuration-menu', 'Osvdb');

require_once 'repository_common.php';


function get_link_select($conn, $data)
{
	$type       = $data['type'];
	$extra_info = $data['extra'];
	
	ossim_valid($type,			OSS_INPUT,					'illegal:' . _('Type'));
	ossim_valid($extra_info,	OSS_DIGIT, OSS_NULLABLE,	'illegal:' . _('Parameter'));
		
	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	
	switch($type)
	{
		case 'host':
		case 'host_group':
		case 'net':
		case 'net_group':
		case 'incident':
		
			list($list, $num_rows) = Repository::get_hostnet($conn, $type);
			
			$result = build_select($list);
			
		break;			
			
		case 'plugin_sid':
	
			$result = plugin_select($conn);
			
		break;
			
		case 'sid':
	
			$result = pluginsids_select($conn, $extra_info);
			
		break;						
			
		case "directive":
			
			$result = directives_select();
			
		break;			
			
		case 'taxonomy':
		
			$result = taxonomy_select($conn);
			
		break;
			
		case 'subcategory':
		
			$result = subcategory_select($conn, $extra_info);
			
		break;
		
		default:
		
			$return['error'] = TRUE ;
			$return['msg']   = _('Invalid Type');
			
			return $return;			
	}
	
	
	$return['error'] = FALSE;
	$return['data']  = $result;
	
	return $return;
	
}


function delete_link($conn, $data)
{

	$delete      = $data['link'];
	$id_document = $data['id'];
	$link_type   = $data['type'];
	
	ossim_valid($delete, 		OSS_DIGIT, OSS_HEX, OSS_NULLABLE, '#',  'illegal:' . _('Document ID'));
	ossim_valid($link_type, 	OSS_INPUT, 							    'illegal:' . _('Link Type'));
	ossim_valid($id_document, 	OSS_DIGIT,							    'illegal:' . _('Document ID'));
	
	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	if ($delete != '') 
	{	
		$result = Repository::delete_relationships($conn, $id_document, $delete);
		
		if(!empty($result))
		{
			$return['error'] = TRUE;
			$return['msg']   = $result;
			return $return;
		}
		
		if ($link_type == 'plugin_sid') 
		{	
			$result = Repository::delete_snort_references($conn, $id_document);
			
			if(!empty($result))
			{
				$return['error'] = TRUE;
				$return['msg']   = $result;
				return $return;
			}
		}	
	}

	$return['error'] = FALSE;
	$return['msg']   = _('Link deleted successfully');

	return $return;	
}


function insert_link($conn, $data)
{

	$new_linkname = $data['link'];
	$id_document  = $data['id'];
	$link_type    = $data['type'];
	
	ossim_valid($link_type, 	OSS_INPUT,		'Illegal:' . _('Link Type'));
	ossim_valid($id_document, 	OSS_DIGIT,		'Illegal:' . _('Document ID'));

	
	switch($link_type)
	{
		case 'directive':
		
			ossim_valid($new_linkname, 		OSS_DIGIT, 		'illegal:' . _('Directive'));
						
		break;
		
		
		case 'incident':
		
			ossim_valid($new_linkname, 		OSS_DIGIT, 		'illegal:' . _('Incident ID'));
			
		break;
			
			
		case 'plugin_sid':
		
			$plugin = explode('##', $new_linkname);
			
			ossim_valid($plugin[0],		OSS_DIGIT, 		'illegal:' . _('Plugin SID'));
			ossim_valid($plugin[1],		OSS_DIGIT,		'illegal:' . _('Plugin ID'));
			
		break;
			
			
		case 'host':
		case 'host_group':
		case 'net':
		case 'net_group':
		

			ossim_valid($new_linkname, 		OSS_HEX, 		'illegal:' . _('Asset ID'));
			
		break;
			
		case 'taxonomy':
			
			$tax = explode('##', $new_linkname);
			
			ossim_valid($tax[0], 		OSS_DIGIT, 		'illegal:' . _('Product Type'));
			ossim_valid($tax[1], 		OSS_DIGIT, 		'illegal:' . _('Category'));
			ossim_valid($tax[2], 		OSS_DIGIT, 		'illegal:' . _('Subcategory'));
			
		break;
			
		default:
		
			$return['error'] = TRUE;
			$return['msg']   = _('Invalid Link Type');
			
			return $return;
	
	
	}

	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	
	
	$result = Repository::insert_relationships($conn, $id_document, $link_type, $new_linkname);
	
	if(!empty($result))
	{
		$return['error'] = TRUE;
		$return['msg']   = $result;
		
		return $return;
	}
	
	if ($link_type == 'plugin_sid')
	{
		$result = Repository::insert_snort_references($conn, $id_document, $plugin[1], $plugin[0]);
		
		if(!empty($result))
		{
			$return['error'] = TRUE;
			$return['msg']   = $result;
			
			return $return;
		}
	}
	
	
	$info_item['key']  = $new_linkname;
	$info_item['id']   = $id_document;
	$info_item['type'] = $link_type;

	$item_html = build_item_list($conn, $info_item);
	
	$return['error'] = FALSE;
	$return['data']  = $item_html;
	$return['msg']   = _('Link inserted successfully');

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
	switch($action)
	{	
		case 1:			
			$response = get_link_select($conn, $data);			
		break;

		case 2:			
			$response = delete_link($conn, $data);			
		break;
			
		case 3:			
			$response = insert_link($conn, $data);			
		break;
						
		default:
			$response['error'] = TRUE;
			$response['msg']   = 'Wrong Option Chosen';
	}
	
	echo json_encode($response);

	$db->close();
}
