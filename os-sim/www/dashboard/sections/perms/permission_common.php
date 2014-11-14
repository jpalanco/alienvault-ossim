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


function draw_carrousel($conn, $user_list)
{		
	$perms = "";
	
	$perms .= "	
		<table valign='middle' align='center' height='100%' class='transparent'>
			<tr>
				<td class='noborder'>
					<button id='prev' class='bc_prev button av_b_secondary'><<</button>						
				</td>
				<td class='noborder'>
					<div id='jCarouselLite' class='jCarouselLite' >
						
						<ul>
	";
	
	foreach ($user_list as $user)
	{

		$perms .= "<li id='col_". $user->login ."'>"; 

		$perms .= draw_users_list($conn, $user);
	
		$perms .= " </li>\n";
	}
	
	$perms .= 
		"					</ul>
							
					</div>		
				</td>
				<td class='noborder'>
					<button id='next' class='bc_next button av_b_secondary'>>></button>
				</td>
			</tr>
		</table>
	";
	
	
	return $perms;
	
}


function draw_user_header($conn, $user) 
{
	$entities = "";

	foreach ($user->ctx as $entity)
	{
		$entities .= (Acl::get_entity_name($conn, $entity))."<br>";
	}

	if(empty($entities) && Session::am_i_admin())
	{
		$entities = _("Global Admin");
	}
	
	$header = "
	       <div class='column_header'>
	           <div class='db_perm_header_title'>
	               ". $user->name ."
	           </div>
	           <div class='db_perm_header_opts' onclick='toggle_default_tabs(this,\"".$user->login."\");'>
	               "._("Show Default Tabs")."
	           </div>
	           <div title='$entities' class='db_perm_header_icon ui-icon ui-icon-help tooltip'></div>
	       </div>";

	return $header;
}


function draw_users_list($conn, $user) 
{		
	$columns  =  "<div class='column'>";
				
	$columns .= draw_user_header($conn, $user);
					
	
	$columns .= "<div id='col_".$user->login."' class='column_body tab_list'>";
	
	$tabs = Dashboard_tab::get_tabs_by_user($user->login, true);

	foreach ($tabs as $t)
	{
		$display   = ($t->is_visible()) ? '' : 'tab_hidden' ;

		if ($t->is_locked())
		{
			$class_tab = 'tab_protected tab'.$user->login;
			$classmenu = 'menuPermsProtected';

		} 
		else 
		{
			$class_tab = 'tab_unprotected';
			$classmenu = 'menuPerms';
		}
		
		$title    = (strlen($t->get_title()) > 20) ? substr($t->get_title(), 0, 17) . "..." : $t->get_title();
		
		$columns .= "<div id='". $user->login ."#". $t->get_id() ."' tab_id='". $t->get_id() ."' class='tab $class_tab $display'>";

		
		$columns .= "<div class='db_perm_tab_title' title=\"" . $t->get_title() . "\">". $title ."</div>";
			
		$columns .= "<div title='". _("Click to see Tab Options") ."' id='". ($user->login.";".$t->get_id()) ."' class='db_perm_tab_icon ui-icon ui-icon-wrench $classmenu'></div>";
			

		$columns .=  "</div>";

	}
	
	$columns .= "</div>";

			
	return $columns;

}
