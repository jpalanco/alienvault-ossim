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

Session::logcheck("configuration-menu", "PolicyPolicy");


require_once 'policy_common.php';


function get_policy_groups($conn, $data)
{
	$ctx = (empty($data['ctx'])) ? Session::get_default_ctx() : $data['ctx'];
	$id  = $data['id'];
	
	ossim_valid($ctx	, OSS_HEX		            , 'illegal:' . _("CTX"));
	ossim_valid($id		, OSS_HEX, OSS_NULLABLE		, 'illegal:' . _("CTX"));
		
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}


	$result = '';
	
	if(is_ctx_engine($conn, $ctx))
	{
		$server      = Server::get_engine_server($conn, $ctx);
		$server_name = (empty($server[$ctx]['name']))? _('Unknown Server') : $server[$ctx]['name'];
		$server_name = _('Policies generated in') . ": <font style=\"font-weight:normal;font-style:italic\">" . $server_name . "</font>";
		
		$result      = "<option value='".$id."' selected='selected'>".$server_name."</option>";
	}
	else
	{
		$policy_groups = Policy::get_policy_groups($conn, $ctx);
		foreach($policy_groups as $group) 
		{
			$selected = ($group->get_group_id() == $id) ? "selected='selected'" : "";
			$result  .= "<option value='".$group->get_group_id()."' $selected>".$group->get_name()."</option>";
		}
	}
         
	$return['error'] = FALSE ;
	$return['data']  = $result;
	
	return $return;  
} 



function get_policy_actions($conn, $data)
{
	$ctx = (empty($data['ctx'])) ? Session::get_default_ctx() : $data['ctx'];
	$id  = $data['id'];
	
	ossim_valid($ctx	, OSS_HEX		            , 'illegal:' . _("CTX"));
	ossim_valid($id		, OSS_HEX, OSS_NULLABLE		, 'illegal:' . _("CTX"));
		
	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}

	$actions_saved = array();
	$result        = '';
	
	if ($action_list = Policy_action::get_list($conn, $id))
	{
		foreach($action_list as $action) 
		{
			$actions_saved[] = $action->get_action_id();
		}
	}
	
	$where = (Session::am_i_admin()) ? '': "AND ctx=UNHEX('$ctx')";
	
	if ($action_list2 = Action::get_list($conn, $where))
	{
		foreach($action_list2 as $act) 
		{ 
			$sel   = (in_array($act->get_id(),$actions_saved)) ? " selected='selected'" : ""; 
			$desc1 = Util::utf8_encode2((strlen($act->get_name())>48) ? substr($act->get_name(),0,48)."..." : $act->get_name());

			$result .="<option value='". $act->get_id() ."' $sel>$desc1</option>";

		}
	}
        
	$return['error'] = FALSE ;
	$return['data']  = $result;
	
	return $return; 
} 


function get_plugin_groups($conn, $data)
{
	$id  = $data['id'];
	$ctx = $data['ctx'];
	
	ossim_valid($id		, OSS_HEX, OSS_NULLABLE		, 'illegal:' . _("Policy ID"));
	ossim_valid($ctx	, OSS_HEX					, 'illegal:' . _("CTX"));
		
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	$is_engine = is_ctx_engine($conn, $ctx);

	$result = '';

	$result .= "
		<div style='height:150px;overflow:auto'>
		<table width='100%' class='transparent' cellspacing='0' cellpadding='0'>
			<tr>";

	$iplugin = 1;
	
	/* ===== plugin groups ==== */
	if($is_engine)
	{
		$pgroups = Plugin_group::get_groups_by_plugin($conn, 1505);
		$excluded = array();
	}
	else
	{
		$pgroups  = Plugin_group::get_list($conn, "", "name");
		$excluded = Plugin_group::get_groups_by_plugin($conn, 1505);
	}
	
	foreach($pgroups as $g) 
	{
        if(isset($excluded[$g->get_id()])) 
        {
            continue;
        }
																			
		$result .= "<td class='nobborder' style='text-align:left;padding-right:10px'>";

        $checked  = (in_array($g->get_id() , $pgroups)) ? "checked='checked'" : "";
        $mixed    = ($is_engine) ? FALSE : $g->contains_directive_plugin($conn);

        if($mixed)
        {
        	$tip = _('This plugin group cannot be applied because contains the plugin 1505');
            $result .= "<input type='checkbox' class='disabled' disabled='disabled' id='plugin_" . $g->get_id() ."' pname='". $g->get_name() ."'>";
            $result .= "<a href='modifyplugingroupsform.php?action=edit&id=". $g->get_id() ."' class='greybox gray italic tiptip' title='". _('View DS Group') ."'>". $g->get_name() ."</a>";
        	$result .= " <img src='/ossim/pixmaps/warnin_icon.png' id='dg_locked' class='tiptip_dg' title='". $tip ."'/>";
        }
        else
        {
            $result .= "<input type='checkbox' id='plugin_" . $g->get_id() ."' pname='". $g->get_name() ."' onclick='drawpolicy()' name='plugins[". $g->get_id() ."]' $checked/>";
        
            $result .= "<a href='modifyplugingroupsform.php?action=edit&id=". $g->get_id() ."' class='greybox' title='". _('View DS Group') ."'>". $g->get_name() ."</a>";

        }

        if($iplugin++ % 4==0) 
        { 
        	$result .= "<tr></tr>"; 
        }
	}
	
	$result .= "
			</tr>
		</table></div>";
        

	$return['error'] = FALSE;
	$return['data']  = $result;
	
	return $return;  
} 


function get_targets($conn, $data)
{ 
	$id  = $data['id'];
	
	ossim_valid($id		, OSS_HEX, OSS_NULLABLE		, 'illegal:' . _("CTX"));
		
	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		return $return;
	}

	$result      = "";
	$servers     = array();
	$targets     = array();
	
	//Getting all the serves:
	$server_list = Server::get_list($conn);
	
	foreach ($server_list as $server) 
	{
		$servers[$server->get_id()] = $server->get_name();
	}
	
	if ($policies = Policy::get_list($conn, "AND id=UNHEX('$id')")) 
	{
        $policy   = $policies[0];
		
		if ($target_list = $policy->get_targets($conn))
		{ 
			foreach($target_list as $target) 
			{
				$targets[$target->get_target_id()] = ($servers[$target->get_target_id()] == "") ? _("ANY") : $servers[$target->get_target_id()];
			}
		}
		
	}
	
	$i = 1;
	if ($server_list = Server::get_list($conn, "ORDER BY name"))
	{
		foreach($server_list as $server)
		{
			$server_name = $server->get_name();
			$server_id   = $server->get_id();
			$server_ip   = $server->get_ip();
			
			if ($i == 1) 
			{
				$result .= "<input type='hidden' name='targetserver' value='".count($server_list)."'/>";
			}
			
			$name = "targboxserver" . $i;

			$result .=	"<input type='checkbox' onclick='drawpolicy()' id='target_$server_ip ($server_name)' name='$name' value='$server_id'". (($targets[$server_id] != '') ? "checked='checked'" : "") ."/>$server_ip($server_name)<br/>";
				

			$i++;
		}
	}
	/* == ANY target == */

	$return['error'] = FALSE;
	$return['data']  = $result;
	
	return $return;
  
} 


function get_categories($conn, $data)
{	
	$ctx = (empty($data['ctx'])) ? Session::get_default_ctx() : $data['ctx']; //the ctx will be ignored in this version but the function is built thinking of the future...
	
	ossim_valid($ctx	, OSS_HEX		            , 'illegal:' . _("CTX"));
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}


	$result = "<option value='0' selected='selected'>". _("ANY") ."</option>";

	$query = "SELECT id, name FROM category";
	
	$rs = $conn->Execute($query);
	
	if (!$rs) 
	{
		$return['error'] = TRUE;
		$return['msg']   = $conn->ErrorMsg();
		
		return $return;	
	} 
	else 
	{		
		while (!$rs->EOF) 
		{
			$result .= "<option value='".$rs->fields["id"]."'>". Util::utf8_encode2($rs->fields["name"]) ."</option>\n";
			$rs->MoveNext();
		}
	}
        
	$return['error'] = false;
	$return['data']  = $result;
	
	return $return;
} 


function get_subcategories($conn, $data)
{
	$ctx = (empty($data['ctx'])) ? Session::get_default_ctx() : $data['ctx'];
	$id  = $data['id'];
	
	ossim_valid($ctx	, OSS_HEX		            , 'illegal:' . _("CTX"));
	ossim_valid($id		, OSS_HEX, OSS_NULLABLE		, 'illegal:' . _("Category ID"));
		
	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE;
		$return['msg']   = $info_error;
		
		return $return;
	}

	$result = "<option value='0' selected='selected'>". _("ANY") ."</option>";

	$query  = "SELECT id, name FROM subcategory where cat_id = ?";
	$params = array($id);
	
	$rs = $conn->Execute($query, $params);
	
	if (!$rs) 
	{
		$return['error'] = TRUE;
		$return['msg']   = $conn->ErrorMsg();
		
		return $return;	
		
	} 
	else 
	{
		while (!$rs->EOF) 
		{
			$result .= "<option value='".$rs->fields["id"]."'>". Util::utf8_encode2($rs->fields["name"]) ."</option>\n";
			
			$rs->MoveNext();
		}
	}
        
	$return['error'] = FALSE;
	$return['data']  = $result;
	return $return;	
  
} 



$login = Session::get_session_user();
$db    = new ossim_db();
$conn  = $db->connect();

$action = POST("action");
$data   = POST("data");

ossim_valid($action,	OSS_DIGIT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    die(ossim_error());
}

if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	switch($action)
	{
		case 1:			
			$response = get_policy_groups($conn, $data);			
			break;
	
		case 2:			
			$response = get_policy_actions($conn, $data);			
			break;
		
		case 3:			
			$response = get_targets($conn, $data);			
			break;
		
		case 4:			
			$response = get_plugin_groups($conn, $data);			
			break;

		case 5:			
			$response = get_categories($conn, $data);			
			break;
			
		case 6:			
			$response = get_subcategories($conn, $data);			
			break;
			
									
		default:
			$response['error'] = TRUE;
			$response['msg']   = 'Wrong Option Chosen';
	}
	
	echo json_encode($response);

	$db->close();
}
