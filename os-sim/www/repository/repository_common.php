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


function show_notification($msg, $type = 'nf_error')
{
	echo "<div class='container' style='width:100%;height:30px;margin:10px auto;text-align:center'>";

	$config_nt = array(
		'content' => $msg,
		'options' => array (
			'type'          => $type,
			'cancel_button' => FALSE
		),
		'style'   => ' margin:0 auto;text-align:center;'
	); 
	

	$nt = new Notification('nt_panel', $config_nt);
	$nt->show();

	echo "</div>";
	
	die();
}


function plugin_select($conn)
{
	$plugins = Plugin::get_list($conn, 'ORDER BY name');

	$select  = "<select style='width:300px' onchange='change_type(\"sid\", $(this).val())'>";

	if(is_array($plugins) && count($plugins) > 0)
	{
		foreach ($plugins as $plugin) 
		{
			$plugin_title = (strlen($plugin->get_description()) > 0 ) ? $plugin->get_name() . ': ' . $plugin->get_description() : $plugin->get_name();
			$plugin_name  = ( strlen($plugin_title) > 36 ) ? substr($plugin_title, 0, 39).'...' : $plugin_title; 
			$select      .= "<option title='".$plugin_title."' value='".$plugin->get_id()."' >".$plugin_name."</option>";
		}
		
		$select .= "</select>";
		$select .= "<div id='sidselect'></div>";
	}
	else 
	{
		$select .= "<option value=''>"._('No items found')."</option>";
		$select .= "</select>";
	}
	
	
	return $select;	
}


function pluginsids_select($conn, $pid)
{
	$select = '';
	
	if ($pid != '' && $pid != '0') 
	{
		$sids = Plugin_sid::get_list($conn, "WHERE plugin_id=$pid");

		$select  = "<select id='linkname' name='newlinkname' style='width:300px'>";

		if(is_array($sids) && count($sids) > 0)
		{
			foreach ($sids as $sid) 
			{
				$select .= "<option value='".$sid->get_sid()."##$pid'>".$sid->get_name();
			}
		}
		else 
		{
			$select .= "<option value=''>"._('No items found')."</option>";
		}
		
		$select .= "</select>";
	}
		
	return $select;	
}


function directives_select()
{
	$conf      = $GLOBALS['CONF'];
	$engine_id = $conf->get_conf('default_engine_id');
	
	$directive_editor = new Directive_editor($engine_id);
	$dirs             = $directive_editor->get_categories();
	
	$select  = "<select id='linkname' name='newlinkname' style='width:300px'>";

	if(is_array($dirs) && count($dirs) > 0)
	{
		foreach ($dirs as $dir) 
		{
			$dir = $dir['directives'];
			$dir = (is_array($dir)) ? $dir : array();
			
			foreach ($dir as $did => $dname) 
			{
				$name    = (strlen($dname) > 60) ? substr($dname, 0, 57)."..." : $dname;
				$select .= "<option value='$did' title='$dname'>".$name;
			}
		}
	}
	else 
	{
		$select .= "<option value=''>"._('No items found')."</option>";
	}
	
	$select .= "</select>";
	
	return $select;	
}


function taxonomy_select($conn)
{
	$product_types = Product_type::get_list($conn);	

	$select  = "<div class='select_div_first' >";
	$select .= "<div style='float:left;'>". _('Product Type') .": </div>";
	$select .= "<div style='float:right;'><select id='ptypeselect' style='width:210px;text-align:left;'>";
	
	$select .= "<option title='". _('Any') ."' value='0' >". _('Any') ."</option>";
	
	if(is_array($product_types))
	{
	
		foreach($product_types as $ptype)
		{
			$ptype_name  = ( strlen($ptype->get_name()) > 36 ) ? substr($ptype->get_name(), 0, 39)."..." : $ptype->get_name(); 
			$select      .= "<option title='".$ptype->get_name()."' value='".$ptype->get_id()."' >".$ptype_name."</option>";
		}		
	}
	
	$select .= "</select></div></div><br>";
	
	
	$select .= "<div class='select_div'>";
	$select .= "<div style='float:left;'>". _('Category') .": </div>";
	$select .= "<div style='float:right;'><select id='catselect' style='width:210px;text-align:left;' onchange='change_type(\"subcategory\", $(this).val())'>";
	
	$select .= "<option title='". _('Any') ."' value='0' >". _('Any') ."</option>";
	$cat_list = Category::get_list($conn);
	
	if(is_array($product_types))
	{				
		foreach($cat_list as $cat)
		{
			$cat_name  = ( strlen($cat->get_name()) > 36 ) ? substr($cat->get_name(), 0, 39)."..." : $cat->get_name(); 
			$select      .= "<option title='".$cat->get_name()."' value='".$cat->get_id()."' >".$cat_name."</option>";
		}		
	}
	
	$select .= "</select></div></div><br>";
	
	$select .= "<div class='select_div'>";
	$select .= "<div style='float:left;'>". _('Subategory') .": </div>";
	$select .= "<div style='float:right;'><select id='subcatselect' style='width:210px;text-align:left;' >";
	$select .= "<option title='". _('Any') ."' value='0' >". _('Any') ."</option>";
	$select .= "</select></div></div><br>";
	
	return $select;	
}


function subcategory_select($conn, $id)
{
	$select = "<option title='". _('Any') ."' value='0' >". _('Any') ."</option>";
	
	$subcat_list = Subcategory::get_list($conn, " where cat_id='$id'");
	
	if(is_array($subcat_list))
	{	
		foreach($subcat_list as $subcat)
		{
			$subcat_name  = ( strlen($subcat->get_name()) > 36 ) ? substr($subcat->get_name(), 0, 39)."..." : $subcat->get_name(); 
			$select      .= "<option title='".$subcat->get_name()."' value='".$subcat->get_id()."' >".$subcat_name."</option>";
		}
		
	}
	
	return $select;	
}



function build_select($data)
{
	$select = "<select id='linkname' name='newlinkname' style='width:300px'>";
	
	if(is_array($data) && count($data) > 0)
	{
		foreach($data as $op)
		{
				
			$select .= "<option value='".$op['key']."'>".$op['name']."</value>";			
		}	
	}
	else 
	{
		$select .= "<option value=''>"._('No items found')."</value>";
	}
	
	$select .= "</select>";
	
	return $select;	
}


function get_doc_info($conn, $rel)
{
	$name = '';
	$url  = '';
	
	$url_links['host']        = Menu::get_menu_url('/ossim/asset_details/index.php?id=KKKK', 'environment', 'assets', 'assets');
	$url_links['net']         = Menu::get_menu_url('/ossim/assets/list_view.php?type=network', 'environment', 'assets', 'nets');
	$url_links['host_group']  = Menu::get_menu_url('/ossim/hostgroup/hostgroup.php', 'environment', 'assets', 'host_groups');
	$url_links['net_group']   = Menu::get_menu_url('/ossim/netgroup/netgroup.php', 'environment', 'assets', 'net_groups');
	$url_links['incident']    = Menu::get_menu_url('/ossim/incidents/incident.php?id=KKKK', 'analysis', 'tickets', 'tickets');
	$url_links['directive']   = Menu::get_menu_url('/ossim/directives/index.php?toggled_dir=KKKK&dir_info=1', 'configuration', 'threat_intelligence', 'directives');
	$url_links['plugin_sid']  = Menu::get_menu_url('/ossim/forensics/base_qry_main.php?clear_allcriteria=1&search=1&sensor=&sip=&plugin=&ossim_risk_a=+&submit=Signature&search_str=KKKK', 'analysis', 'security_events', 'security_events');	
	$url_links['taxonomy']    = "";	
	

	$key = $rel['key'];
	
	switch($rel['type'])
	{
		case 'directive':
		
			$name = $rel['key'];
	
		break;
		
		case 'incident':
		
			$sql    = "SELECT title from incident where id=?";
			$params = array ($rel['key']);
					
			if (!$rs = & $conn->Execute ($sql, $params)) 
			{
				$name = _('Unknown');
			} 
			elseif (!$rs->EOF) 
			{
				$name = $rs->fields["title"];
			}
	
		break;
		
		case 'plugin_sid':
		
			$plugin = explode('##', $rel['key']);
			$pid    = $plugin[1];
			$sid    = $plugin[0];
			
			if($pid != '' && $sid != '')
			{
				$name = Plugin_sid::get_name_by_idsid($conn, $pid, $sid);

				
				if (!preg_match('/:/', $name))
				{
					$name = Plugin::get_name_by_id($conn,$pid).": ".$name;
				}	

				$key = $name;
			}
			else
			{
				$name = _('Unknown, Please edit this relationship');
				$key  = '';
			}

			break;
			
			
		case 'host':
		case 'host_group':
		case 'net':
		case 'net_group':
		
			$field = ($rel['type'] == 'host') ? 'hostname' : 'name';
			
			$sql = "SELECT $field as name from ".$rel['type']." where id=UNHEX(?)";
			$params = array ($rel['key']);
					
			if (!$rs = &$conn->Execute ($sql, $params)) 
			{
				$name = _('Unknown');
			} 
			elseif (!$rs->EOF) 
			{
				$name = $rs->fields["name"];
			}

			break;
			
		case 'taxonomy':
			
			$tax = explode('##', $rel['key']);

			$ptype  = (intval($tax[0]) != 0) ? Product_type::get_name_by_id($conn, $tax[0]) : _('ANY');
			$cat    = (intval($tax[1]) != 0) ? Category::get_name_by_id($conn, $tax[1])    : _('ANY');
			$subcat = (intval($tax[2]) != 0) ? Subcategory::get_name_by_id($conn, $tax[2]) : _('ANY');
			
			
			$name   = _('Product Type') . ': ' . $ptype . ', ' . _('Category') . ': ' . $cat . ', ' . _('Subcategory') . ': ' . $subcat;
			
			break;
			
		default:
		
			$name = _('Unknown');
	}
	
	$url = $url_links[$rel['type']];
	$url = ($url != '') ? str_replace('KKKK', $key, $url) : 'javascript:;';	
	
	return array($name, $url);
}


function build_item_list($conn, $rel)
{		
	list($title_rel, $_url) = get_doc_info($conn, $rel);

	$rel_name = ($rel['type'] == "incident") ? "TICKET" : strtoupper(str_replace('_', ' ', $rel['type']));

	$item = "
		<tr id='link_".$rel['key']."'>
			<td class='left nobborder'>
				$rel_name
			</td>
			
			<td class='left nobborder'>
				$title_rel
			</td>
			
			<td class='action noborder'>
				<a href='javascript:;' onclick=\"delete_link('". $rel['id'] ."','". $rel['key'] ."','". $rel['type'] ."');\">
				<img src='images/del.gif' border='0'/></a>
			</td>
		</tr>";	

	return $item;
}