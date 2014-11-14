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
if (!Session::logcheck_bool("dashboard-menu", "ControlPanelExecutive"))
{
    $response['error']  = TRUE;
    $response['msg']    = _('You do not have permissions to see this section');
    
    echo json_encode($response);
    exit -1;
}

require_once '../sections/widgets/widget_common.php';


function get_tabs_data_aux()
{
	$user = Session::get_session_user();
	$edit = ($_SESSION['_db_show_edit'] != '') ? $_SESSION['_db_show_edit'] : FALSE;

	return array($user, $edit);
}


/****************************************************************************************************************/
/*																												*/
/************************************************ TABS FUNTIONS *************************************************/
/*																												*/
/****************************************************************************************************************/

function load_tab($data)
{	
	$id = $data['id'];

	ossim_valid($id, OSS_DIGIT,	'illegal:' . _('Tab ID'));
		
	if ( ossim_error() )
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}

	list($user, $edit) = get_tabs_data_aux();

	try
	{
		$tab = new Dashboard_tab($id);
	}
	catch (Exception $e) 
	{
		$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();
		return $return;
	}

	$_SESSION['_db_panel_selected'] = $id;

	$data['tab']     = get_object_vars($tab);
	$data['widgets'] = Dashboard_widget::get_widgets_panel($id, FALSE);
	
	$return['error'] = FALSE;
	$return['data']  = $data;
	
	return $return;	
}


function change_title_tab($data)
{
	$panel = $data['panel'];
	$title = $data['title'];
	
	ossim_valid($panel      , OSS_DIGIT			, 'illegal:' . _('Tab'));
	ossim_valid($title		, OSS_INPUT			, 'illegal:' . _('Title'));

	if (ossim_error())
	{
		$info_error = _('Error').': '.ossim_get_error();
		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}

	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try 
	{
		$tab = new Dashboard_tab($panel);

		if($tab->is_locked())
		{
			$return['error'] = TRUE;
			$return['msg']   = _('You cannot modify this tab');
			return $return;
		}

		$tab->set_title($title);

		$tab->save_db();
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }

	$return['error'] = FALSE;
	$return['msg']   = _('Title Changed Successfully');

	return $return;
}


function save_tabs_order($conn, $data)
{ 
	$order = count($data);
	
	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");
		
		return $return;
	}

	foreach($data as $id)
	{
		ossim_valid($id		, OSS_DIGIT		, 'illegal:' . _("Tab ID"));
		
		if (ossim_error())
		{
			$info_error = "Error: ".ossim_get_error();
			ossim_clean_error();

			$return['error'] = TRUE;
			$return['msg']   = $info_error;

			return $return;
		}

		$sql = "UPDATE dashboard_tab_options SET tab_order=? WHERE id=? and user=?";
		
		$params = array(
			$order,
			$id,
			$user
		);				
						
		if ($conn->Execute($sql, $params) === FALSE) 
		{
			$return['error'] = TRUE;
			$return['msg']   = $conn->ErrorMsg() . '.';
			return $return;
		} 	 
		
		$order--;
	} 

	$return['error'] = FALSE;
	$return['msg']   = _('Tab Order saved');

	return $return;

}



function change_visibility($data)
{
	$panel = $data['panel'];
	
	ossim_valid($panel,  OSS_DIGIT,  'illegal:' . _("Tab"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}

	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try
	{
		$tab = new Dashboard_tab($panel);

		if($tab->is_visible() && $tab->is_default())
		{
			$return['error'] = TRUE;
			$return['msg']   = _("Default tab cannot be disabled");
			return $return;
		}
		
		$tab->set_visible(1 - intval($tab->is_visible()));

		$tab->save_db();

	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }

	$return['error'] = FALSE;
	$return['msg']   = _("Visibility Option Changed Successfully");

	return $return;

}


function set_default_tab($data)
{
	$panel = $data['panel'];

	ossim_valid($panel      , OSS_DIGIT			, 'illegal:' . _("Tab"));

	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}

	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try 
	{
		$tab = new Dashboard_tab($panel);
		$tab->set_default();
		$tab->save_db();

		$_SESSION['default_tab'] = $panel;
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }
	
	$return['error'] = FALSE;
	$return['msg']   = _("Default Tab Changed Successfully");

	return $return;
}


function set_layout($data)
{
	$panel    = $data['panel'];
	$new_cols = $data['new_col'];
	
	ossim_valid($panel      , OSS_DIGIT			, 'illegal:' . _("Panel"));
	ossim_valid($new_cols	, OSS_DIGIT			, 'illegal:' . _("Num Column"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}
	
	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try
	{
		$tab = new Dashboard_tab($panel);

		if($tab->is_locked())
		{
			$return['error'] = TRUE;
			$return['msg']   = _("You cannot modify this tab");
			return $return;
		}

		Dashboard_widget::reorder_widgets_position($tab, $new_cols);

		$tab->set_layout($new_cols);

		$tab->save_db();
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }
		 
	$return['error'] = FALSE;
	$return['msg']   = _("New Column Configuration Saved");

	return $return;
}


function clone_tab($data)
{ 
	$panel = $data['panel'];
	
	ossim_valid($panel,	OSS_DIGIT, 'illegal:' . _("Tab ID"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}
	
	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try
	{
		$tab     = new Dashboard_tab($panel);

		$_cloned = $tab->clone_tab();
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }
	
	$data            = array();

	$data['msg']     = _("Tab Cloned Successfully");
	$data['new_id']  = $_cloned->get_id();
	$data['title']   = $_cloned->get_title();

	$return['error'] = FALSE;
	$return['data']  = $data;

	return $return;
}


function add_tab($data)
{ 
	$title  = $data['title'];
	$layout = $data['layout'];
	
	ossim_valid($title,	 OSS_INPUT,	 'illegal:' . _("Tab Title")); 
	ossim_valid($layout, OSS_DIGIT,	 'illegal:' . _("Tab Layout")); 

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}
	
	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try
	{
		$tab = new Dashboard_tab();

		$tab->set_title($title);
		$tab->set_layout($layout);

		$tab->save_db();
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }
	
	$data            = array();

	$data['msg']     = _("Tab Created Successfully");
	$data['new_id']  = $tab->get_id();
	$data['title']   = $tab->get_title();

	$return['error'] = FALSE;
	$return['data']  = $data;

	return $return;	
} 


function delete_tab($data)
{ 
	$panel = $data['panel'];
	
	ossim_valid($panel,		OSS_DIGIT,		'illegal:' . _("Tab ID")); 

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}
	
	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try 
	{
		$tab = new Dashboard_tab($panel);

		if($tab->is_locked())
		{
			$return['error'] = TRUE;
			$return['msg']   = _("You cannot modify this tab");
			return $return;
		}

		$tab->delete();
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }

	$return['error'] = FALSE;
	$return['msg']   = _("Tab Deleted Successfully");

	return $return;
	
}

/****************************************************************************************************************/
/*																												*/
/**************************************************  WIDGETS  ***************************************************/
/*																												*/
/****************************************************************************************************************/


function delete_widget($data)
{
	$wid = $data['id'];

	ossim_valid($wid,  OSS_DIGIT,  'illegal:' . _("Widget ID"));
		
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}
	
	try 
	{
		$widget = new Dashboard_widget($wid);

		if (!$widget->is_widget_editable())
	    {
	    	$return['error'] = TRUE;
			$return['msg']   = _("You cannot delete this widget");
			return $return;
	    }

	    $widget->delete();

	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }
	
	$return['error'] = FALSE;
	$return['msg']   = _("Widget Deleted Successfully");
	
	return $return;	
} 



function save_widgets_order($conn, $data)
{ 
 	$panel    = $data['panel'];
	$widgets  = $data['widgets'];	
	
	ossim_valid($panel      , OSS_DIGIT			, 'illegal:' . _("Panel")); 

	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();

		ossim_clean_error();

		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}

	list($user, $edit) = get_tabs_data_aux();

	if(!$edit)
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You have to be in edit mode to achieve this action");

		return $return;
	}

	try 
	{
		$tab = new Dashboard_tab($panel);
	}
	catch (Exception $e)
    {
    	$return['error'] = TRUE;
		$return['msg']   = $e->getMessage();

		return $return;
    }

	if($tab->is_locked())
	{
		$return['error'] = TRUE;
		$return['msg']   = _("You cannot modify this tab");
		return $return;
	}

	if(is_array($widgets) && !empty($widgets))
	{
		foreach($widgets as $widget)
		{		
			ossim_valid($widget['col']      , OSS_DIGIT			, 'illegal:' . _("Widget Column"));
			ossim_valid($widget['fil']      , OSS_DIGIT			, 'illegal:' . _("Widget Row"));
			ossim_valid($widget['id']       , OSS_DIGIT			, 'illegal:' . _("Widget ID"));
			
			if ( ossim_error() )
			{
				$info_error = "Error: ".ossim_get_error();

				ossim_clean_error();

				$return['error'] = TRUE;
				$return['msg']   = $info_error;

				return $return;
			}
			
			$sql = "UPDATE dashboard_widget_config SET col = ?, fil = ? WHERE id = ? and panel_id=? and user=?";
			
			$params = array
			(
				$widget['col'],
				$widget['fil'],
				$widget['id'],
				$panel,
				$user
			);
				
			if ($conn->Execute($sql, $params) === FALSE)
			{
				$return['error'] = TRUE;
				$return['msg']   = $conn->ErrorMsg() . '.';
				return $return;
			}
		} 
	}

	$return['error'] = FALSE;
	$return['msg']   = _("Dashboard Configuration Saved");

	return $return;
}



/****************************************************************************************************************/
/*																												*/
/**************************************************  WIZARD   ***************************************************/
/*																												*/
/****************************************************************************************************************/

function delete_wizard_session()
{
	$user  = Session::get_session_user();

	$w_key = md5(base64_encode($user));

	unset($_SESSION[$w_key]);

	$return['error'] = FALSE;
	$return['msg']   = "";

	return $return;
}


/****************************************************************************************************************/
/*																												*/
/************************************************  BREAD CRUMB  *************************************************/
/*																												*/
/****************************************************************************************************************/
function build_crumb($data)
{ 	
	Session::logcheck("dashboard-menu", "ControlPanelExecutiveEdit");
	
	$type   = $data['type'];
	$step   = $data['step'];
	$titles = array();

	$pro    = Session::is_pro();

	ossim_valid($type,	 OSS_DIGIT,		'illegal:' . _("Breadcrumb"));
	ossim_valid($step,	 OSS_DIGIT, 	'illegal:' . _("Step"));
		
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE;
		$return['msg']   = $info_error;

		return $return;
	}
	
	switch($type)
	{
		case 1:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				2 => utf8_encode(_("Select Category")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);

			if($pro)
			{
				$titles[3] = utf8_encode(_("Select Assets"));
			}

		break;

		case 2:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				2 => utf8_encode(_("Insert Rss URL")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);

		break;

		case 3:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				2 => utf8_encode(_("Insert Image URL")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);

		break;

		case 4:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				2 => utf8_encode(_("Select Report")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);

		break;

		case 5:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				2 => utf8_encode(_("Select OSSIM URL")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);
			
		break;

		case 6:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);

			if($pro)
			{
				$titles[3] = utf8_encode(_("Select Assets"));
			}
			
		break;

		case 7:
		
			$titles = array(
				1 => utf8_encode(_("Select Type")),
				4 => utf8_encode(_("Customize Widget")),
				5 => utf8_encode(_("Save Widget"))
			);

		break;
	}
	
	$breadcrumb = "";
	ksort($titles);
	
	foreach ($titles as $i => $title)
	{	
		if($i > $step)
		{
		    break;
		}
		
		if($i == $step)
		{
			$class = "class='current'";
			$link  = "#";
		}
		else
		{
			$class = "";
			$link  = "wizard.php?backbc=1&step=$i";
		}

		$breadcrumb .= "<li id='step$i' $class>
							<a href='$link'>".$title."</a>
						</li>";
	
	}

	$return['error'] = FALSE;
	$return['msg']   = $breadcrumb;

	return $return;
}




/***************************************************************************************************/
/***************************************************************************************************/
/*                                                                                                 */
/***************************************************************************************************/
/***************************************************************************************************/

$action = POST("action");
$data   = POST("data");

ossim_valid($action,	OSS_INPUT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    $response['error'] = TRUE;
	$response['msg']   = ossim_error();
	ossim_clean_error();
}


$db                = new ossim_db();
$conn              = $db->connect();

$response['error'] = TRUE;
$response['msg']   = _('Unknown Error');


if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	if ( !Token::verify('tk_dashboard_ajax', GET('token')) )
	{		
		$response['error'] = TRUE;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
		switch($action)
		{
			case 'load_tab':
				$response = load_tab($data);
				break;

			case 'change_title_tab':
				$response = change_title_tab($data);
				break;

			case 'save_tabs_order':
				$response = save_tabs_order($conn, $data);
				break;

			case 'set_layout':
				$response = set_layout($data);
				break;

			case 'save_widgets':
				$response = save_widgets_order($conn, $data);
				break;

			case 'change_visibility':
				$response = change_visibility($data);
				break;

			case 'set_default_tab':
				$response = set_default_tab($data);
				break;
			
			case 'delete_widget':
				$response = delete_widget($data);
				break;

			case 'clone_tab':
				$response = clone_tab($data);
				break;

			case 'add_tab':
				$response = add_tab($data);
				break;

			case 'delete_tab':
				$response = delete_tab($data);
				break;

			case 'delete_wizard_session':
				$response = delete_wizard_session();
				break;

			case 'build_crumb':
				$response = build_crumb($data);
				break;
					
			default:
				$response['error'] = TRUE;
				$response['msg']   = _('Wrong Option Chosen');
		}
	}
}

echo json_encode($response);

$db->close();