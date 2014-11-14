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

$db_path = '/usr/share/ossim/www/dashboard';
set_include_path(get_include_path() . PATH_SEPARATOR . $db_path);

require_once 'sections/wizard/wizard_common.php';
require_once 'sections/tabs/tab_common.php';
require_once 'sections/widgets/widget_common.php';



Session::logcheck("dashboard-menu", "ControlPanelExecutive");

// Connect BD
$db                = new ossim_db();
$dbconn            = $db->connect();


// Step
$step              = (POST("step") != "") ? POST("step") : GET("step");
$step              = ($step != "") ? $step :  1;


//Aux vars
$error             = false;
$parameters        = array();
$info_error        = null;


// User data
$current_user      = Session::get_session_user();
$wr_key            = md5(base64_encode($current_user));

// Version
$conf              = $GLOBALS["CONF"];
$version           = $conf->get_conf("ossim_server_version", FALSE);
$pro               = (preg_match("/pro|demo/i",$version) ) ? true : false;

// Tab
$tab               = (GET('tab_panel') != "") ? GET('tab_panel') : $_SESSION['_db_panel_selected'];


//modify
$modify            = $_SESSION[$wr_key]['modify'];


//back option with bread crumb
$backbc            = (GET('backbc') != "") ? GET('backbc') : 0;
$next			   = (POST("next")  != "") ? POST("next")  : GET("next");


ossim_valid($tab,		OSS_DIGIT,				'illegal:' . _("Tab Panel"));
ossim_valid($step,		'1-6',					'illegal:' . _("Step"));
ossim_valid($backbc,	"0,1",					'illegal:' . _("Option"));
ossim_valid($next,		"1",OSS_NULLABLE,		'illegal:' . _("Option"));

if (ossim_error())
{ 
	die(ossim_error());
}
	
if(!tab_editable($tab))
{
	die(_('You cannot add or edit widgets in this tab'));	
}
	
//Labels
$t_no_data_available = utf8_encode(_("No data available yet"));

$excluded = array('legend', 'position', 'columns', 'placement');	


define("W_H_SMALL",  240);
define("W_H_MEDIUM", 320);
define("W_H_BIG",    560);

switch($step)
{
	case "1":
			
		if ($backbc)
		{
			if(!is_array( $_SESSION[$wr_key]) || empty($_SESSION[$wr_key]))
			{
				$link = 'wizard.php?step=1';
				header ("Location: $link");
				
				exit();	
				
			} 
			else 
			{			
				$widget_type = $_SESSION[$wr_key]['widget_type'];
				$owner       = $_SESSION[$wr_key]['owner'];
			}
		}
		else
		{
			//To modify a saved report
			$modify = GET('modify');						
			ossim_valid($modify,	OSS_DIGIT,OSS_NULLABLE,		'illegal:' . _("Modify Option"));
			
			if (ossim_error())
            { 
                die(ossim_error());
            }
				
			if (!empty($modify))
			{
				//Widget's id
				$id_content = GET("id");
				
				
				unset($_SESSION[$wr_key]);
				
				ossim_valid($id_content, OSS_DIGIT, 'illegal:' . _("Widget ID"));
				if (ossim_error())
				{ 
					die(ossim_error());
				}
				
				$sql = "SELECT * FROM dashboard_widget_config where id=?";
		
				$params = array(
					$id_content
				);
					
				if ($result = & $dbconn->Execute($sql, $params))
				{
					$owner       = $_SESSION[$wr_key]['owner']       = $result->fields['user'];				
					$widget_type = $_SESSION[$wr_key]['widget_type'] = $result->fields['type']; //this id is the template's id, no the widget's id!!
										
					$_SESSION[$wr_key]['widget_refresh'] = $result->fields['refresh'];
					$_SESSION[$wr_key]['widget_height']  = $result->fields['height'];
					$_SESSION[$wr_key]['widget_title']   = $result->fields['title'];
					$_SESSION[$wr_key]['widget_help']    = $result->fields['help'];
					$_SESSION[$wr_key]['widget_color']   = $result->fields['color'];					
					$_SESSION[$wr_key]['widget_url']     = $result->fields['file'];							
					$_SESSION[$wr_key]['widget_id']      = $result->fields['type_id'];
					$_SESSION[$wr_key]['widget_asset']   = $result->fields['asset'];
					$_SESSION[$wr_key]['widget_media']   = $result->fields['media'];
					$widget_params                       = unserialize($result->fields['params']);
					$_SESSION[$wr_key]['widget_params']  = $widget_params;


					if($result->fields['type'] == 'url' || $result->fields['type'] == 'report')
					{
						$_SESSION[$wr_key]['widget_content'] = $widget_params['content'];
					} 
					else
					{
						$_SESSION[$wr_key]['widget_content'] = base64_decode($widget_params['content']);
					}
					
				} 
				else 
				{
					print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
					
					exit;	
				}
					
				$_SESSION[$wr_key]['id_content'] = $id_content;
				
			
			}
			else
			{			
				unset($_SESSION[$wr_key]);
				$owner = $_SESSION[$wr_key]['owner'] = $current_user;
				
			}
			
			$_SESSION[$wr_key]['modify']     = $modify;		
			
		}
		$_SESSION[$wr_key]['step'] = 1;
						
		
	break;
	
	case "2":
						
		if ($next && $_SESSION[$wr_key]['step'] == 1 )
		{
			$widget_type = $parameters['widget_type'] = POST('widget_type'); 
			$owner       = $_SESSION[$wr_key]['owner'];	
			
					
			$validate  = array (
				"widget_type" => array("validation" => "OSS_TEXT"  ,"e_message" => 'illegal:' . _("Widget Type"))
			);
					
				
			foreach ($parameters as $k => $v )
			{
				eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
			
				if ( ossim_error() )
				{
					$info_error[] = ossim_get_error();
					ossim_clean_error();
					$error = true;
				}
			}
									
			switch($widget_type)
			{						
				case 'rss':
					$widget_id      = 10001; 
					$widget_content = $_SESSION[$wr_key]['widget_content'];
					break;
					
				case 'image':
					$widget_id      = 11001; 
					$widget_content = $_SESSION[$wr_key]['widget_content'];
					$widget_media   = $_SESSION[$wr_key]['widget_media'];
					break;	
					
				case 'report':
					
					if(!$pro)
					{
						$info_error[] = _('Report section is only available in professional version');
						$error = true;
						break;
					}
					$widget_id      = 12001; 
					$widget_content = $_SESSION[$wr_key]['widget_content'];
					break;
					
				case 'url':
					$widget_id      = 13001; 
					$widget_content = $_SESSION[$wr_key]['widget_content'];
					break;
					
				case 'real_time':
					$_SESSION[$wr_key]['widget_id'] = $widget_id = 6001; 
					break;
					
				case 'flow':
					$_SESSION[$wr_key]['widget_id'] = $widget_id = 5001; 
					break;
					
				default:
					$widget_id      = $_SESSION[$wr_key]['widget_id'];					
			}	

		}
		else if ($backbc)
		{					
			if ( !is_array( $_SESSION[$wr_key]) || empty($_SESSION[$wr_key]) )
			{
				$link = 'wizard.php?step=1';
				header ("Location: $link");
				
				exit();	
				
			} 
			else 
			{			
				$widget_id 	    = $_SESSION[$wr_key]['widget_id']; 		
				$widget_type    = $_SESSION[$wr_key]['widget_type']; 
				$widget_content = str_replace('AV@@@', '', $_SESSION[$wr_key]['widget_content']);
				$widget_media   = $_SESSION[$wr_key]['widget_media'];
				$owner          = $_SESSION[$wr_key]['owner'];
			}
		
		}
		else
		{
			$link = 'wizard.php?step=1';
			header ("Location: $link");
			
			exit();
		}

		
		if ($error == false )
		{
			$_SESSION[$wr_key]['widget_type'] = $widget_type;	
			$_SESSION[$wr_key]['step'] = 2;
			
			if($widget_type == 'real_time' || $widget_type == 'flow')
			{
				$link = 'wizard.php?step=3&next=1';
				header ("Location: $link");		
				exit;
			}
		} 
		else
		{
			$step       = "1";
			$class      = "wr_show";
			$errors_txt = display_errors($info_error);
		}
		
		$step_back = 1;
														
	break;
	
	
	case "3":						
			
		if ($next && $_SESSION[$wr_key]['step'] == 2 )
		{		
			$widget_id   = $parameters['widget_id'] = POST('widget_id');			
			$widget_type = $_SESSION[$wr_key]['widget_type'];
			$owner       = $_SESSION[$wr_key]['owner'];
			
			if ($widget_type == 'real_time' || $widget_type == 'flow') 
			{
				$widget_id = $parameters['widget_id'] = $_SESSION[$wr_key]['widget_id'];
			}
			
			if (!empty ($modify))
			{
				$widget_asset = $_SESSION[$wr_key]['widget_asset'];
				
			} 
			else
			{
				$widget_asset = "ALL_ASSETS"; 
			}
			
			$validate  = array (					
				"widget_id" => array("validation" => "OSS_DIGIT"  ,"e_message" => 'illegal:' . _("Widget ID"))
			);
			
			
			
			if ($widget_type == 'rss')
			{		
				$widget_content              = $parameters['widget_content'] = POST('widget_content');
				$validate["widget_content"]  = array("validation" => "OSS_URL_ADDRESS" ,"e_message"	=> 'illegal:' . _("URL"));
				
			} 
			elseif ($widget_type == 'url')
			{		
				$widget_content              = $parameters['widget_content'] = POST('widget_content');
				$validate["widget_content"]  = array("validation" => "OSS_DIGIT" ,"e_message"	=> 'illegal:' . _("URL"));
				
			}
			elseif ($widget_type == 'report')
			{
				$widget_content              = $parameters['widget_content'] = POST('widget_content');
				$validate["widget_content"]  = array("validation" => "OSS_SCORE,OSS_NULLABLE,OSS_ALPHA,OSS_PUNC,'#'" ,"e_message"	=> 'illegal:' . _("URL"));
			
			} 
			elseif ($widget_type == 'image')
			{
				if (isset($_FILES['img_file']) && !empty($_FILES['img_file']['tmp_name']))
				{
					$image_val = getimagesize($_FILES['img_file']['tmp_name']);
					
					if (!empty($image_val))
					{
						if (filesize($_FILES['img_file']['tmp_name']) < 250000)
						{
							$name     = md5($current_user+ microtime(true));
							$name    .= str_replace("image/", ".", $image_val['mime']);
							$filename = AV_MAIN_ROOT_PATH . "/tmp/$name";
														
							move_uploaded_file($_FILES['img_file']['tmp_name'], $filename);
														
							$widget_media                = file_get_contents($filename);
							$widget_content              = $parameters['widget_content'] = "AV@@@".$name;
							$validate["widget_content"]  = array("validation" => "OSS_TEXT, '@'" ,"e_message"	=> 'illegal:' . _("File"));
						} 
						else 
						{
							$info_error[] = _("Invalid Size of Image.");
							$error = true;
						}
						
					} 
					else 
					{
					
						$info_error[] = _("Invalid Image.");
						$error = true;
						
					}
					
				}
				else
				{
					$widget_content = $parameters['widget_content'] = POST('widget_content');
					
					if (empty($widget_content) && !empty($_SESSION[$wr_key]['widget_media']))
					{
						$widget_media                = $_SESSION[$wr_key]['widget_media'];
						$widget_content              = $parameters['widget_content'] = "AV@@@".$_SESSION[$wr_key]['widget_content'];
						$validate["widget_content"]  = array("validation" => "OSS_TEXT, '@'" ,"e_message"	=> 'illegal:' . _("URL"));
						
					} 
					else
					{
						$validate["widget_content"]  = array("validation" => "OSS_URL_ADDRESS" ,"e_message"	=> 'illegal:' . _("URL"));
						
					}
					
				}				

			}
										
			foreach ($parameters as $k => $v )
			{
				eval("ossim_valid(\"\$v\", ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
			
				if (ossim_error())
				{
					$info_error[] = ossim_get_error();
					ossim_clean_error();
					$error = true;
				}
			}
						
			if ($error == true )
			{
				$step       = "2";
				$class      = "wr_show";
				$errors_txt = display_errors($info_error);
			}
		}
		else if ($backbc)
		{
			if (!is_array( $_SESSION[$wr_key]) || empty($_SESSION[$wr_key]))
			{				
				$link = 'wizard.php?step=1';
				header ("Location: $link");
				exit();
				
			}
			else 
			{			
				$widget_asset   = $_SESSION[$wr_key]['widget_asset'];	
				$widget_id      = $_SESSION[$wr_key]['widget_id'];
				$widget_type    = $_SESSION[$wr_key]['widget_type']; 
				// $widget_content = $_SESSION[$wr_key]['widget_content'];
				// $widget_media   = $_SESSION[$wr_key]['widget_media'];
			}
		
		}
		else
		{			
			$link = 'wizard.php?step=1';
			header ("Location: $link");
			
			exit();
		}
		
		if ($error == false )
		{
			$_SESSION[$wr_key]['widget_id']      = $widget_id;	
			$_SESSION[$wr_key]['widget_content'] = $widget_content;	
			$_SESSION[$wr_key]['widget_media']   = $widget_media;
			
			$_SESSION[$wr_key]['step'] = 3;
			
			if(!$pro || $widget_type == 'rss' || $widget_type == 'image' || $widget_type == 'report' || $widget_type == 'url' || $widget_type == 'flow')
			{
				$link = 'wizard.php?step=4&next=1';
				header ("Location: $link");		
				exit;
			}
			
			if ($widget_type == 'real_time')
			{
				$step_back = 1;
			}
			else
			{
    			$step_back = 2;
			}
						
		}
		else
		{
    		$step_back = 1;
		}
		
		
	break;
	
	
	case "4":
	
		if ($next && $_SESSION[$wr_key]['step'] == 3 )
		{
		
			if ($pro)
			{
				$widget_asset = $parameters['widget_asset'] = POST('widget_asset');	
			} 
			else
			{
				$widget_asset = "ALL_ASSETS"; 
			}
			
			$widget_id    = $_SESSION[$wr_key]['widget_id'];
			$widget_type  = $_SESSION[$wr_key]['widget_type'];
			$owner        = $_SESSION[$wr_key]['owner'];
			
			if (!empty ($modify))
			{
				$widget_refresh = $_SESSION[$wr_key]['widget_refresh'];
				$widget_height  = $_SESSION[$wr_key]['widget_height'];
				$widget_title   = $_SESSION[$wr_key]['widget_title'];
				$widget_help    = $_SESSION[$wr_key]['widget_help'];
				$widget_color   = $_SESSION[$wr_key]['widget_color'];
				$widget_params  = $_SESSION[$wr_key]['widget_params'];
				
			} 
			else
			{
				$widget_refresh = 0; 
				$widget_height  = W_H_MEDIUM; 
				$widget_color   = "db_color_1";
			}
		
			$validate  = array (				
				"widget_asset"    => array("validation" => "OSS_HEX,OSS_SCORE,OSS_ALPHA,OSS_USER,OSS_NULLABLE"  ,"e_message" => 'illegal:' . _("Widget Asset"))
			);
					
				
			foreach ($parameters as $k => $v)
			{
				eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
			
				if (ossim_error())
				{
					$info_error[] = ossim_get_error();
					ossim_clean_error();
					$error = true;
				}
			}
			
						
			if ($error == true )
			{
				$step       = "3";
				$class      = "wr_show";
				$errors_txt = display_errors($info_error);
			}
			
		}
		else if ($backbc)
		{

			if (!is_array( $_SESSION[$wr_key]) || empty($_SESSION[$wr_key]))
			{				
				$link = 'wizard.php?step=1';
				header ("Location: $link");
				exit();
				
			} 
			else 
			{
			
				$widget_refresh = $_SESSION[$wr_key]['widget_refresh'];
				$widget_height  = $_SESSION[$wr_key]['widget_height'];
				$widget_title   = $_SESSION[$wr_key]['widget_title'];
				$widget_help    = $_SESSION[$wr_key]['widget_help'];
				$widget_color   = $_SESSION[$wr_key]['widget_color'];
				$widget_params  = $_SESSION[$wr_key]['widget_params'];
				$widget_asset   = $_SESSION[$wr_key]['widget_asset'];
				$widget_id 	    = $_SESSION[$wr_key]['widget_id'];		
				$widget_type    = $_SESSION[$wr_key]['widget_type']; 
				$owner          = $_SESSION[$wr_key]['owner'];
			
			}
		
		}
		else
		{
			$link = 'wizard.php?step=1';
			header ("Location: $link");
			
			exit();
		}
	
		if ($error == false )
		{
			$_SESSION[$wr_key]['widget_asset']  = $widget_asset;	
			$_SESSION[$wr_key]['step'] = 4;

			$step_back = 3;

			if ($widget_type == 'flow')
			{
				$step_back = 1;
			}
			elseif (!$pro || $widget_type == 'rss' || $widget_type == 'image' || $widget_type == 'report' || $widget_type == 'url')
			{
				$step_back = 2;
			}
				
		}	
		else
		{
    		if ($widget_type == 'real_time')
			{
				$step_back = 1;
			}
			else
			{
    			$step_back = 2;
			}
		}
		
								
	break;
	
	case "5":		

		if ($next && $_SESSION[$wr_key]['step'] == 4)
		{			
			$widget_id      = $_SESSION[$wr_key]['widget_id'];
			$widget_type    = $_SESSION[$wr_key]['widget_type'];
			$widget_asset   = $_SESSION[$wr_key]['widget_asset'];
			$widget_content = $_SESSION[$wr_key]['widget_content'];
			$owner          = $_SESSION[$wr_key]['owner'];
			//Getting the Options
			
			$widget_title   = $parameters['widget_title']   = POST('widget_title');
			$widget_help    = $parameters['widget_help']    = POST('widget_help');
			$widget_refresh = $parameters['widget_refresh'] = POST('widget_refresh');
			$widget_height  = $parameters['widget_height']  = POST('widget_height');
			
			
			//Getting the Filters and the url of the iframe
			$sql = "SELECT params, file FROM dashboard_custom_type where id=?";
	
			$params = array(
				$widget_id
			);
				
			if ($result = & $dbconn->Execute($sql, $params))
			{
				$input_widget = $result->fields['params'];
				$widget_url   = $parameters['widget_url'] = trim($result->fields['file']);
			}
			else
			{
				print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
				exit;	
			}
			
			
			$validate  = array (
				"widget_title"   => array("validation" => "OSS_INPUT"				,"e_message"	=> 'illegal:' . _("Widget Title")),
				"widget_help"    => array("validation" => "OSS_TEXT, OSS_NULLABLE"	,"e_message" 	=> 'illegal:' . _("Widget Help")),
				"widget_refresh" => array("validation" => "OSS_DIGIT"  				,"e_message"	=> 'illegal:' . _("Widget Refresh")),
				"widget_height"  => array("validation" => "OSS_DIGIT"  				,"e_message"	=> 'illegal:' . _("Widget Height")),
				"widget_url"     => array("validation" => "OSS_URL_ADDRESS"         ,"e_message"	=> 'illegal:' . _("Widget File"))
			);
					
				
			foreach ($parameters as $k => $v)
			{
				eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
			
				if (ossim_error())
				{
					$info_error[] = ossim_get_error();
					ossim_clean_error();
					$error = true;
				}
			}
			
			if($widget_type == 'report' && !$pro)
			{
				$info_error[] = _('Report section is only available in professional version');
				$error = true;
			}
			
						
			//Checking the filetrs:			
			$list  = explode(";",$input_widget);
			$w_id  = 0;
			
			
			if (trim($list[0])!= "") 
			{
				foreach ($list as $input)
				{
					$oss_arr = array();
					$params  = explode(":",$input);
					
					if (in_array($params[1], $excluded))
					{
						continue;
					}
					
					$input_type = $params[2];
					$_oss_aux   = explode(".", $params[3]);
					
					foreach ($_oss_aux as $oss)
					{
						$oss_arr[] = constant($oss);
					}
					
					$oss_arr[]  = "illegal:" . _($params[0]);
					
					$value_key  = "i".$w_id."_".$params[1];
					$value      = POST($value_key);

					ossim_valid($value, $oss_arr);
			
					if (ossim_error())
					{
						$info_error[] = ossim_get_error();
						ossim_clean_error();
						$error = true;
					}

					$widget_params[$params[1]] = $value;

					$w_id++;		
										
				}
			}
			
			if ($widget_type == 'rss' || $widget_type == 'image')
			{
				$widget_params['content'] = base64_encode($widget_content);
			} 
			elseif ($widget_type == 'url')
			{
				$widget_params['content'] = $widget_content;
			} 
			elseif ($widget_type == 'report')
			{
				$widget_params['content'] = $widget_content;
				$widget_url               = $widget_url.add_url_character($widget_url)."mode=dashboard&widthDashboards=". $widget_height ."&run=".$widget_content;
			}

			if ($error == true)
			{
				$step       = "4";
				$class      = "wr_show";
				$errors_txt = display_errors($info_error);
			}
			
		}		
		else
		{
			$link = 'wizard.php?step=1';
			header ("Location: $link");
			
			exit();
		}
		
		if ($error == false )
		{
			$_SESSION[$wr_key]['widget_title']   = $widget_title;
			$_SESSION[$wr_key]['widget_help']    = $widget_help;
			$_SESSION[$wr_key]['widget_refresh'] = $widget_refresh;
			$_SESSION[$wr_key]['widget_height']  = $widget_height;
			$_SESSION[$wr_key]['widget_url']     = $widget_url;
			$_SESSION[$wr_key]['widget_params']  = $widget_params;	
			
			$_SESSION[$wr_key]['step'] = 5;

			$step_back = 4;

		}
		else
		{
    		$step_back = 3;

			if ($widget_type == 'flow')
			{
				$step_back = 1;
			}
			elseif (!$pro || $widget_type == 'rss' || $widget_type == 'image' || $widget_type == 'report' || $widget_type == 'url')
			{
				$step_back = 2;
			}
		}
		
												
	break;
	
	case "6":
						
		if ($next && $_SESSION[$wr_key]['step'] == 5)
		{			
			$widget_id      = $_SESSION[$wr_key]['widget_id'];
			$widget_type    = $_SESSION[$wr_key]['widget_type'];
			$widget_asset   = $_SESSION[$wr_key]['widget_asset'];
			$widget_title   = $_SESSION[$wr_key]['widget_title'];
		    $widget_help    = $_SESSION[$wr_key]['widget_help'];
			$widget_refresh = $_SESSION[$wr_key]['widget_refresh'];
		    $widget_height  = $_SESSION[$wr_key]['widget_height'];
			$widget_url     = $_SESSION[$wr_key]['widget_url'];			
			$owner          = $_SESSION[$wr_key]['owner'];
			$id_content     = $_SESSION[$wr_key]['id_content'];
			$widget_media   = $_SESSION[$wr_key]['widget_media'];
			$widget_params  = serialize($_SESSION[$wr_key]['widget_params']);

			if ($id_content != "")
			{
				$column     = getColumn($dbconn, $id_content);
				$order      = getOrder($dbconn, $id_content);
			} 
			else 
			{
				$id_content = getNewId($dbconn, $tab);
				$column     = 0;
				$order      = 0;
				$error 		= reorder_widgets($dbconn, $tab);
			}
						
			ossim_valid($column,	OSS_DIGIT,	'illegal:' . _("Widget Column"));
			ossim_valid($order,		OSS_DIGIT,	'illegal:' . _("Widget Row"));
						
			if (ossim_error())
			{
				$info_error[] = ossim_get_error();
				ossim_clean_error();
				$error = true;
			}
			
			if ($widget_type == 'report' && !$pro)
			{
				$info_error[] = _('Report section is only available in professional version');
				$error = true;
			
			}

			if ($error == true)
			{
				$step       = "5";
				$class      = "wr_show";
				$errors_txt = display_errors($info_error);
				
			} 
			else 
			{
				//Save into DB
				savetoDB($dbconn, $id_content, $widget_id, $tab, $owner, $column, $order, $widget_height, $widget_title, $widget_help, $widget_refresh, $widget_url, $widget_type, $widget_asset, $widget_media, $widget_params);

				?>
				<script>
					if(typeof(parent.GB_close) == 'function') 
					{
						parent.GB_close();
					}
				</script>
				<?php
			}

		}		
		else
		{
			$link = 'wizard.php?step=1';
			header ("Location: $link");
			
			exit();
		}
												
	break;

	
	default:
	
		$link = 'wizard.php?step=1';
		header ("Location: $link");
		
		exit();
	
}


//Load data to Step 1
switch ($step)
{
	case "1":
			
		$widget_types = array(
			"chart"     => _("Chart"), 
			"image"     => _("Image"), 
			"rss"       => _("RSS Feed"), 
			"tag_cloud" => _("Tag Cloud"),		
			"report"    => _("Reporting Modules"),
			"url"       => _("AlienVault URL"),
			"real_time" => _("Real Time"),
			"gauge"     => _("Gauge"),
			"flow"      => _("Network Flows"),
		);
						
		break;
	
	
	//Load data to Step 2
	case "2":
	
		if($widget_type == 'chart' || $widget_type == 'tag_cloud')
		{
			if($widget_id != "")
			{
				$sql = "SELECT category, name FROM dashboard_custom_type where id=?";
	
				$params = array(
					$widget_id
				);
					
				if ($rs = & $dbconn->Execute($sql, $params))
				{
					$widget_text = $rs->fields["category"] . ": " . $rs->fields["name"];
				} 
				else 
				{
					print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
					exit;	
				}
				
			} 
			else 
			{
				$widget_text = "";
			}
			
			$categories_list = array();
			$sql             = "SELECT category, name, id FROM dashboard_custom_type where type like ? group by category, name order by id";
	
			$params = array(
				'%'.$widget_type.'%'
			);
				
			if ($result = & $dbconn->Execute($sql, $params))
			{
				while (!$result->EOF)
				{
					if ($result->fields['name'] != "")
					{
						$categories_list[ $result->fields['category']][$result->fields['name']] = $result->fields['id'];
					}
					
					$result->MoveNext();
				}
				
			} 
			else 
			{
				print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
				exit;	
			}
			
		} 
		elseif ($widget_type == 'gauge')
		{
			if ($widget_id != "")
			{
				$sql = "SELECT category, name FROM dashboard_custom_type where id=?";
	
				$params = array(
					$widget_id
				);
					
				if ($rs = & $dbconn->Execute($sql, $params))
				{
					$widget_text = $rs->fields["category"] . ": " . $rs->fields["name"];
				} 
				else 
				{
					print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
					exit;	
				}
				
			} 
			else 
			{
				$widget_text = "";
			}
			
			$gauge_list = array();

			$sql = "SELECT category, name, id FROM dashboard_custom_type where type like ? group by category, name";
	
			$params = array(
				'%'.$widget_type.'%'
			);
				
			if ($result = & $dbconn->Execute($sql, $params))
			{
				while (!$result->EOF)
				{
					if( $result->fields['name'] != "" )
					{
						$gauge_list[$result->fields['category'] . " - " . $result->fields['name']] = $result->fields['id'];
					}
					
					$result->MoveNext();
				}
			} 
			else 
			{
				print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
				exit;	
			}
		}
						
		break;
		
	case "3":

		if($widget_asset == "" || $widget_asset == "ALL_ASSETS")
		{
			$dassets_text = _("All Assets");
		} 
		else 
		{
			$key    = (preg_match_all("/^([a-z]+)_(.+)/", $widget_asset, $found)) ? $found[1][0] : "";
			$table  = '';
			$aux_id = '';
			$where  = "id=UNHEX(?)";
			$select = 'name';
			
			switch($key)
			{
				case 'e':
					$table  	  = 'acl_entities';
					$aux_id 	  = $found[2][0];
					$dassets_text = _('Entity') . ": ";
					break;
					
				case 'host':
					$table  	  = 'host';
					$aux_id 	  = $found[2][0];
					$select       = 'hostname';
					$dassets_text = _('Host') . ": ";
					break;
					
				case 'hostgroup':
					$table  	  = 'host_group';
					$aux_id 	  = $found[2][0];
					$dassets_text = _('Host Group') . ": ";
					break;
					
				case 'net':
					$table  	  = 'net';
					$aux_id 	  = $found[2][0];
					$dassets_text = _('Network') . ": ";
					break;
					
				case 'netgroup':
					$table  	  = 'net_group';
					$aux_id 	  = $found[2][0];
					$dassets_text = _('Network Group') . ": ";
					break;
					
				case 'u':
					$table        = 'users';
					$aux_id       = $found[2][0];
					$where        = 'login=?';
					$dassets_text = _('User') . ": ";
					break;
					
				default:
					$table  = '';
					$aux_id = '';
			
			}
			
			if (!empty($table) && !empty($aux_id))
			{
				$sql = "SELECT $select as name FROM $table where $where";
	
				$params = array(
					$aux_id
				);
					
				if ($result = & $dbconn->Execute($sql, $params))
				{
					$dassets_text .= utf8_encode($result->fields['name']);								
				} 
				else 
				{
					$widget_asset = "ALL_ASSETS";
					$dassets_text = _("All Assets");
				}
				
			} 
			else 
			{
				$widget_asset = "ALL_ASSETS";
				$dassets_text = _("All Assets");
			}
			
		}

		//Autocomplete - JQuery

		$autocomplete_keys   = array('hosts', 'nets', 'host_groups', 'net_groups', 'users', 'entities');
		
		$autocomplete_assets = Autocomplete::get_autocomplete($dbconn, $autocomplete_keys);

		break;
	

	
	case "4":
		
		$refresh_min  = 0;
		$refresh_max  = 180;
		$refresh_step = 15;
		
		if($widget_id == 6001)
		{
			$refresh_min    = 2;
			$refresh_max    = 60;
			$refresh_step   = 2;
			$widget_refresh = (empty($widget_refresh))? 2 : $widget_refresh;
		}
		
 		$sql = "SELECT * FROM dashboard_custom_type where id=?";
	
		$params = array(
			$widget_id
		);
			
		if ($result = & $dbconn->Execute($sql, $params))
		{
			$name_widget  = $result->fields['name'];
			$input_widget = $result->fields['params'];
			$widget_title = ( $widget_title != "" ) ? $widget_title :  $result->fields['title_default'] ;
			$widget_help  = ( $widget_help  != "" ) ? $widget_help  :  $result->fields['help_default'] ;			
		} 
		else 
		{
			print 'Error retrieving widget info: ' . $dbconn->ErrorMsg() . '<br/>';
			exit;	
		}

		
		$appearance_list = array();
		$filters_list    = array();
		
		if (strlen($input_widget) > 0) 
		{ 
			$list = explode(";",$input_widget);
			
			if (trim($list[0])!="") 
			{
				foreach ($list as $input)
				{
					$params = explode(":",$input);
					$itype  = $params[1];
					
					if($itype == 'type' || $itype == 'legend' || $itype == 'position' || $itype == 'columns' || $itype == 'placement' )
					{
						$appearance_list[] = $params;
						
					} 
					else
					{
						//if($itype == 'range' && $params[4] == $params[5]) continue;
						
						$filters_list[] = $params;
					}
				}
			}
			
		}
		
		break;
		
		
	case "5":
		
		switch($widget_type)
		{
		
			case 'report':
				$preview_url = '/ossim/'.$widget_url;
				break;
				
			default:
				$preview_url = '/ossim/dashboard/sections/'.$widget_url.add_url_character($widget_url)."wtype=".$widget_type."&height=".$widget_height."&asset=".$widget_asset."&value=".serialize($widget_params);
		}
		
		$widget_config_json = array(
		    "title"  => $widget_title,
		    "help"   => $widget_help,
		    "height" => $widget_height,
		    "src"    => $preview_url,
		    
		);
		
		
		break;

		
}

//Breadcrumb and step options
switch($widget_type)
{	
	case 'rss':
		$bc_option = 2;
		break;
		
	case 'image':
		$bc_option = 3;
		break;	
		
	case 'report':
		$bc_option = 4;
		break;
		
	case 'url':
		$bc_option = 5;
		break;
		
	case 'real_time':
		$bc_option = 6;
		break;
		
	case 'flow':
		$bc_option = 7;
		break;
		
	default:
		$bc_option = 1;
}	

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _("OSSIM Framework - Widget Wizard ") ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	

	<?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',       		            'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',	            'def_path' => TRUE),
            array('src' => 'tree.css',         			            'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',		            'def_path' => TRUE),
            array('src' => 'tipTip.css',				            'def_path' => TRUE),
            array('src' => 'xbreadcrumbs.css',			            'def_path' => TRUE),
            array('src' => 'av_common.css',          	            'def_path' => TRUE),
            array('src' => 'dashboard/overview/dashboard.css',      'def_path' => TRUE),
            array('src' => 'dashboard/overview/wizard.css',         'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',						'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',       'def_path' => TRUE),        
            array('src' => 'utils.js',                          'def_path' => TRUE),
            array('src' => 'token.js',                          'def_path' => TRUE),
            array('src' => 'combo.js',                          'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js',                'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                  'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js', 				'def_path' => TRUE),
            array('src' => '/dashboard/js/xbreadcrumbs.js', 	'def_path' => FALSE),
            array('src' => '/dashboard/js/jcarousel.js',   		'def_path' => FALSE),
		    array('src' => '/dashboard/js/mousewheel.js',   	'def_path' => FALSE),
		    array('src' => '/dashboard/js/dashboard_widget.js', 'def_path' => FALSE)
		);
		
        Util::print_include_files($_files, 'js');


    ?>	

    <style type='text/css'>
    
        body
        {
            background-color:#FAFAFA;
        }
        
        .back_div img
        {
            cursor: pointer;            
        }
    
    </style>
	
	
	<script type="text/javascript">

		var src_ajax = "/ossim/dashboard/src/dashboard_ajax.php";

		function setStep(value)
		{
			$('#step').val(value);
		}
		  
		function get_breadCrumb(step, type)
		{
			var ctoken = Token.get_token("dashboard_ajax");
			$.ajax(
			{
				data:  {"action": "build_crumb", "data": {"step": step, "type": type}},
				type: "POST",
				url: src_ajax+"?&token="+ctoken,
				dataType: "json",
				success: function(data)
				{ 
					if(data.error == false) 
					{
						$("#breadcrumbs").html(data.msg);	

						$('#breadcrumbs').xBreadcrumbs({
							collapsible: true,
							collapsedWidth: 20
						});	
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) 
				{						
						
				}
			});
		
		}
		
		function next_step()
		{
			$('#next').val(1);
			$('#form_wizard').submit();
			
			return false;
		}
		
		function prev_step()
		{
			var step = <?php echo intval($step_back) ?>;
			document.location.href = "wizard.php?backbc=1&step="+step;
			
			return false;
		}
		
		function choose_option(id){
			
			$('#widget_content').val(id);
			next_step();
		}
		
		function choose_class(id){
			
			$("#widget_id").val(id);
			next_step();
		}
		
			
		function carrousel_lite(id, num){
	
			var aux = $("#jCarouselLite"+id).html();
			$("#jCarouselLite"+id).html(aux);		
										
			$("#jCarouselLite"+id).jCarouselLite({
				btnNext: "#next"+id,
				btnPrev: "#prev"+id,
				speed: 200,
				mouseWheel: true,
				easing: "",
				visible: num,
				scroll: 1
			});
			
			$("#jCButton"+id).css('visibility', 'visible');

		}

		function set_widget_class(id){				
			
			$('.widget_class').removeClass("carousel_selected");
			$('.widget_class').removeClass("carousel_unselected");
			$('.widget_class').addClass("carousel_unselected");

			$(".wclass"+id).switchClass("carousel_unselected", "carousel_selected", 0);
				
			choose_class(id);
		
		}
	
		$(document).ready(function(){	

			$('.table_data').dataTable( {
				"iDisplayLength": <?php echo ($widget_id == 10001) ? 9 : 11 ?>,
				"sPaginationType": "full_numbers",
				"bLengthChange": false,
				"bJQueryUI": true,
				"aaSorting": [[ 0, "asc" ]],
				"aoColumns": [
					{ "bSortable": true }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Processing') ?>...",
					"sLengthMenu": "Show _MENU_ entries",
					"sZeroRecords": "<?php echo _('No matching records found') ?>",
					"sEmptyTable": "<?php echo _('No data available in table') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
					"sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
					"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
					"sInfoPostFix": "",
					"sInfoThousands": ",",
					"sSearch": "<?php echo _('Search') ?>:",
					"sUrl": "",
					"oPaginate": {
						"sFirst":    "<?php echo _('First') ?>",
						"sPrevious": "<?php echo _('Previous') ?>",
						"sNext":     "<?php echo _('Next') ?>",
						"sLast":     "<?php echo _('Last') ?>"
					}
				},
				"fnHeaderCallback": function( nHead, aData, iStart, iEnd, aiDisplay ) {
					$(".scriptinfo").tipTip();
				}
			});
		
			var count = 0;
			$('.front_div').mouseover(function (e) {
				$(this).css('opacity', '0.3');
				<?php if(!$pro){ ?>
				
					if($(this).attr('id') == 'report' && count == 0)
					{
						$(this).css('opacity', '0.8');
						$('#os_report').text('<?php echo _('Reporting modules are only available in USM Server') ?>');
						count = 1;
					}
					
				<?php } ?>				
			}).mouseout(function (e) {
				$(this).css('opacity', '0.9');
				
				<?php if(!$pro){ ?>
				
					if($(this).attr('id') == 'report')
					{
						count = 0;
						$('#os_report').text('<?php echo _('Reporting Modules') ?>');	
					}
					
				<?php } ?>
			});
			
			$("#accordion").accordion({
				autoHeight: false,
				//navigation: true,
				collapsible: true,
				//active: false,
				create: function(){
					$(this).find("span").removeClass('ui-icon ui-icon-triangle-1-s');
				},
				change: function(event, ui) { 
				
						var id  = $(ui.newHeader).attr('id');						
						
						if(typeof id !='undefined'){
						
							var num = parseInt($(ui.newContent).find('ul').attr('id'));						
							num = (num > 4) ? 4 : num;
						
							carrousel_lite(id,num);
							
						}
											
				}
			});
			
			var num = parseInt($('#jCarouselLite1').find('ul').attr('id'));						
			num = (num > 4) ? 4 : num;
			
			carrousel_lite(1,num);
					
			
			$( ".portlet-header .ui-collapse" ).click(function() {
				$( this ).toggleClass( "ui-icon-minusthick" ).toggleClass( "ui-icon-plusthick" );
				$( this ).parents( ".portlet:first" ).find( ".portlet-content" ).slideToggle("slow");
			});
			
			<?php if($pro){ ?>
			
			var assets = [ <?php echo $autocomplete_assets; ?> ];
				
			$(".hosts").autocomplete(assets, {
				minChars: 0,
				width: 300,
				max: 100,
				matchContains: true,
				autoFill: true,
				formatItem: function(row, i, max) {
					return row.txt;
				}
			}).result(function(event, item) {
			
				$('#assets').val(item.prefix + '_' + item.id);
				$('#dassets').html(item.txt); 

				next_step();
				
			});
		
			$("#atree").dynatree({
				initAjax: { url: "/ossim/tree.php?key=<?php echo (($pro) ? 'entitiesassetsusers|any' : 'assets') ?>" },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					if(dtnode.data.key != '' )
					{
						var value  = dtnode.data.key.replace(/;.*/,"");
						if(value == 'ANY' || value == 'key1') value = 'ALL_ASSETS';
						$('#assets').val(value);
						
						var name = '';
						if (value.match(/^u_/) || dtnode.data.key == 'key1') {
							name = dtnode.data.title;
						} else{
							name = dtnode.data.val;
						}
															
						name  = html_entity_decode(name);						
						name = ( name.length > 50 ) ? name.substring(0, 50)+ "..." : name;
						$('#dassets').html(name);

						next_step();
													
					}
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "/ossim/tree.php",
						data: {"key": dtnode.data.key, "page": dtnode.data.page}
					});
				}
			});
			
			<?php } ?>		
			
			$('.ui-help').tipTip();
			
			$('.widget_type').click(function() {
			
				var id = $(this).attr('id');				
				$('.widget_type').switchClass("type_selected", "type_unselected", 0);
				$(this).switchClass("type_unselected", "type_selected", 0);
				
				$('#widget_type').val(id);
				next_step();	
				
			
			});
			
			
			if ($('#wizard_widget_preview').length > 0)
			{
    			$('#wizard_widget_preview').AVwidget(<?php echo json_encode($widget_config_json) ?>);
            }
							
		});
	</script>
		
</head>

<body>
	
    <div id='results_container' class='<?php echo $class ?>'>
        <div id='results'>
            <div>
                <?php echo $errors_txt ?>
            </div>
        </div>
    </div>
    
    <ul class="xbreadcrumbs <?php echo ($step == 1) ? 'db_wizard_hide' : ''?>" id="breadcrumbs">
		<script>get_breadCrumb(<?php echo $step ?>, <?php echo $bc_option ?>)</script>
	</ul>
	
	<form method='POST' enctype='multipart/form-data' id='form_wizard' action="wizard.php">
    
	<input type="hidden" name="step"   id="step"  value="<?php echo ($step + 1)?>"/>
	<input type="hidden" name='owner'  id='owner' value="<?php echo $owner?>"/>
	<input type="hidden" name='next'   id='next'  value=""/>
		
	<table class="db_w_main_table">
	
	<?php
	//Step 1
	
	switch ($step)
	{
		
		case "1":
		?>						
			
			</tr>
				<td class="nobborder"><br></td>
			<tr>
			
			<tr>
				<td class="nobborder">
					<input type="hidden" id="widget_type" name="widget_type" value="<?php echo $widget_type ?>"/>
					<?php		
					echo "<table align='center' class='transparent' width='90%'>";
					
					$fil      = 0;
					$flag_end = 0;
					$col      = 3;
	
					foreach ($widget_types as $tid=>$wt)
					{
						$w_selected = ($tid == $widget_type)? "type_selected" : "type_unselected";
						
						if($fil%$col == 0)
						{
							if($flag_end)
							{
								echo "	</tr>
										<tr>
											<td class='nobborder' colspan='4'><br></br></td>
										</tr>
									";
								//$flag_end = 0;
							}
							else
							{
								$flag_end = 1;
								echo "<tr>";
							}

						}
						
						if($tid=='report')
						{
							if($pro)
							{							
								echo "<td class='nobborder' style='text-align:center;'><table align='center' class='transparent' width='100%'><tr><td class='noborder' style='text-align:center;'><div id='$tid' class='ui-corner-all widget_type $w_selected'><div class='front_div ui-corner-all'><div style='height:110px;width:160px;vertical-align:middle;display:table-cell;'><a href='javascript:;' class='front_link'>"._($wt)."</a></div></div><div class='back_div'><img  src='/ossim/dashboard/pixmaps/thumbs/$tid.png' style='padding-top:5px;height:100px;width:150px'/></div></div></td></tr></table></td>";
							
							} 
							else 
							{
								echo "<td class='nobborder' style='text-align:center;'><table align='center' class='transparent' width='100%'><tr><td class='noborder' style='text-align:center;'><div class='ui-corner-all type_unselected'><div id='report' class='front_div ui-corner-all'><div style='height:110px;width:160px;vertical-align:middle;display:table-cell;'><b><a href='javascript:;' id='os_report' class='front_link'><b>". _('Reporting Modules') ."</a></b></div></div><div class='back_div'><img  src='/ossim/dashboard/pixmaps/thumbs/$tid.png' style='padding-top:5px;height:100px;width:150px'/></div></div></td></tr></table></td>";
							
							}
						} 
						else 
						{
							echo "<td class='nobborder' style='text-align:center;'><table align='center' class='transparent' width='100%'><tr><td class='noborder' style='text-align:center;'><div id='$tid' class='ui-corner-all widget_type $w_selected'><div class='front_div ui-corner-all'><div style='height:110px;width:160px;vertical-align:middle;display:table-cell;'><a href='javascript:;' class='front_link'>"._($wt)."</a></div></div><div class='back_div'><img  src='/ossim/dashboard/pixmaps/thumbs/$tid.png' style='padding-top:5px;height:100px;width:150px'/></div></div></td></tr></table></td>";
						}
						
						$fil++;
					}

					echo "</tr>";
					echo "</table>";
					?>
					
				</td>
			</tr>		
		
			<tr>
				<td class="nobborder" style='text-align:center;'><br><br></td>
			</tr>
			
		<?php
						
		break;
		
		case "2":
				
		?>
								
			</tr>
				<td class="nobborder"><br></td>
			<tr>
			<input type="hidden" id="widget_id" name="widget_id" value="<?php echo $widget_id;?>"/>
					
					
			<?php
				switch($widget_type)
				{
					case 'chart':					
					case 'tag_cloud':
							draw_accordion($categories_list, $widget_id, $widget_text);
							break;	
					
					case 'gauge':
							draw_gauge_list($gauge_list, $widget_text);
							break;
					
					case 'rss':
							draw_rss_url($widget_content);
							break;
							
					case 'image':
							draw_image_url($widget_content, $widget_media);
							break;	
							
					case 'report':
							draw_report_list($widget_content);
							break;
								
					case 'url':
							draw_custom_url($widget_content);
							break;
				
				}
			
			?>
					
			<tr>
				<td class='db_button_list'>
    				<button type="button" class='db_w_bb av_b_secondary' onclick='prev_step();'><?php echo _("Back")?></button>
    				<button type="button" class='db_w_nb' onclick='next_step();'><?php echo _("Next")?></button>
    			</td>
			</tr>

		<?php
		
		break;
		
		case "3":
		
			if($pro)
			{ 	
			
				if($widget_id == '9001' || $widget_id == '9002')
				{
					?>
					<script>
						$(document).ready(function(){
							$('#autocomplete_assets').hide();
							$("#atree").dynatree("disable");
							$('#assets_notif').text('<?php echo _('This configuration cannot be applied to this widget') ?>');
						});
					</script>
					<?php
				}
			
		?>			
			<tr>
				<td class="nobborder">						
					<div id='assets_notif' style='marfin:0 auto;text-align:center;font-size:12px;padding-top:10px;'></div>
					<input type="hidden" id="assets" name="widget_asset" value="<?php echo $widget_asset;?>"/>								
					
					<table border="0" width="100%" class="transparent">																	
                        <tr id='autocomplete_assets'>
                            <td class="center" width="100%">
                                
                                <label for="shost"><strong><?php echo _("Search:")?></strong></label>
                                <input id="shost" style="width:150px" class="hosts" type="text" name="shost" value="">
                            </td>
                        </tr>
						<tr>
							<td valign="top" class="nobborder" style="text-align:center;">								
								<div id="atree"></div>
							</td>
						</tr>
						
						<tr>
							<td valign="top" class="nobborder" style="text-align:center;padding:40px 0px 15px 0px;">								
								<strong><?php echo _("Selected:")?></strong>&nbsp;
								<span id='dassets'><?php echo $dassets_text ?></span>
							</td>
						</tr>
					</table>									

				</td>
			</tr>
			<tr>
				<td class='db_button_list'>
    				<button type="button" class='db_w_bb  av_b_secondary' onclick='prev_step();'><?php echo _("Back")?></button>
    				<button type="button" class='db_w_nb' onclick='next_step();'><?php echo _("Next")?></button>
    			</td>
			</tr>	
			
			
		<?php }
		
		break;
		
		
		case "4":					
			
			?>
			<tr>
			
				<td style='vertical-align:top'>
				
				<table class='db_w_table_form' align="center">
                    <tr>
                        <th class="header" colspan="2">
                            <?php echo $name_widget .": "._("General Configuration") ?>
                        </th>
                    </tr>	
                    
                    <tr>
                        <td class='db_w_label'>
                            <?php echo _('Title') ?>: 
                        </td>
                        
                        <td class='db_w_input'>
                            <input type='text' name='widget_title' value='<?php echo $widget_title ?>'>
                        </td>
                    
                    </tr>
                    
                    <tr>
                        <td class='db_w_label'>
                            <?php echo _('Help') ?>: 
                        </td>
                        <td class='db_w_input'>
                            <textarea class='wysiwyg' name="widget_help"><?php echo $widget_help ?></textarea>
                        </td>
                        
                    </tr>

				
					<tr>
                        <td class='db_w_label'>
                            <?php echo _('Refresh') ?>: 
                        </td>
                        
                        <td class='db_w_input'>
                        
                            <div id="slider_refresh" class="db_w_slider"></div>
                            <div id="amount_refresh" class="db_w_slider_legend">
                                <?php echo (($widget_refresh == 0) ? "Never" : ($widget_refresh." sec")) ?>
                            </div>
                            <input type="hidden" id="widget_refresh" name="widget_refresh" value="<?php echo $widget_refresh;?>"/>
                        
                        </td>		

						<script type='text/javascript'>
							$("#slider_refresh").slider({
								animate: true,
								range: "min",
								value: <?php echo $widget_refresh?>,
								min:   <?php echo $refresh_min ?>,
								max:   <?php echo $refresh_max ?>,
								step:  <?php echo $refresh_step ?>,
								slide: function(event, ui) {
									var value = (ui.value == 0) ? "Never" : (ui.value + " sec");
									$("#amount_refresh").html(value);
									$("#widget_refresh").val(ui.value);
								}
							});
						</script>

					</tr>
					

					<tr>
					   <th class="header" colspan="2">
                            <?php echo $name_widget .": "._("Appearance") ?>
                        </th>
					</tr>	
					
					<tr>
                        <td class='db_w_label'>
                            <?php echo _('Height') ?>: 
                        </td>
                        <td class='db_w_input db_w_left'>
                            <input type="radio" name="widget_height" value="<?php echo W_H_SMALL ?>" <?php echo ($widget_height == W_H_SMALL) ? "checked" : "" ?>/> Small<br>
							<input type="radio" name="widget_height" value="<?php echo W_H_MEDIUM ?>" <?php echo ($widget_height == W_H_MEDIUM) ? "checked" : "" ?>/> Medium<br>
							<input type="radio" name="widget_height" value="<?php echo W_H_BIG ?>" <?php echo ($widget_height == W_H_BIG) ? "checked" : "" ?>/> Large<br>
                        </td>
                        
                    </tr>
						
					<?php
					$input_id  = 0;
					
					if (!empty($appearance_list))
					{
						foreach ($appearance_list as $params)
						{
							if (in_array($params[1], $excluded))
							{
								continue;
							}
							
							$input_type     = $params[2];
							$selected_value = $widget_params[$params[1]];


							echo '<tr>';
							

							switch ($input_type)
							{
								case "text":
									draw_text($params, $input_id, $selected_value);
									break;

								case "select":
									draw_select($params, $input_id, $selected_value);
									break;	

								case "radiobuttons":
									draw_radiobutton($params, $input_id, $selected_value);
									break; 
							}	
							
							$input_id++;
							
							echo '</tr>';

						}					
					}
					
                    if(!empty($filters_list))
                    { 
                     
                    ?>
						<tr>
    					   <th class="header" colspan="2">
                                <?php echo $name_widget .": "._("Filters") ?>
                            </th>
    					</tr>

						<?php
						foreach ($filters_list as $params)
						{
							$input_type     = $params[2];
							$selected_value = $widget_params[$params[1]];

							echo '<tr>';
							
							switch ($input_type)
							{
								case "text":
									draw_text($params, $input_id, $selected_value);
									break;
	
								case "select":
									draw_select($params, $input_id, $selected_value);
									break;

								case "radiobuttons":
									draw_radiobutton($params, $input_id, $selected_value);
									break; 
							}	
							
							$input_id++;
							
							echo '</tr>';
						}

					} 
					?>				

				</table>
			</td>
			
		</tr>
		<tr>
			<td class='db_button_list'>
				<button type="button" class='db_w_bb av_b_secondary' onclick='prev_step();'><?php echo _("Back")?></button>
				<button type="button" class='db_w_nb' onclick='next_step();'><?php echo _("Next")?></button>
			</td>
		</tr>	
			
		<?php
		
		break;
		
		case "5":											
			
			$loading_div = "<div id='loadingWidget' style='margin:0 auto;height:".$widget_height ."px;width:100%;position:absolute;z-index:10;background-color:white;text-align:center'>
								<div style='margin:0 auto;padding-top:".(round($widget_height/2) - 25)."px;'>
									<img src='/ossim/pixmaps/loading.gif' height='18px'/><br>
									". _('Loading Widget')."
								</div>
							</div>";
			?>
			<tr>
				<td>

				<table align="center" width="90%" class="db_w_table_form">
					<tr>
						<td class="center">
												
							<div id='wizard_widget_preview'></div>
							<div class='clear_layer'></div>
						</td>
					</tr>
					
					<tr>
						<td class="center">
    						<br/>
							<button type="button" onclick='next_step();'><?php echo _("Save Widget")?></button>
							<br/>
						</td>
					</tr>
				
				</table>
				
			</td>
			
		</tr>
		
		<tr>
			<td class='db_button_list'>
				<button type="button" class='db_w_bb av_b_secondary' onclick='prev_step();'><?php echo _("Back")?></button>
			</td>
		</tr>
			
		<?php
		
		break;
		
	}
	
	$db->close($dbconn);
	
	?>

	</table>

	</form>
	
</body>
</html>
