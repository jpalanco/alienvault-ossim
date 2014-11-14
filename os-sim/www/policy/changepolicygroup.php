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


$group = GET('group');
$order = GET('order');
$ctx   = GET('ctx');


ossim_valid($group, 	OSS_HEX, 				'illegal:' . _("group"));
ossim_valid($order, 	OSS_ALPHA, OSS_PUNC, 	'illegal:' . _("order"));
ossim_valid($ctx, 		OSS_HEX, 				'illegal:' . _("order"));

if (ossim_error()) 
{
    die(ossim_error());
}


//db connection
$db   = new ossim_db();
$conn = $db->connect();


$group1 = Policy_group::get_list($conn, $ctx, " AND id=UNHEX('$group')");

if ($group1[0]) 
{
	$ctx = $group1[0]->get_ctx();

    if ($order == "up") 
	{
		$pg_ord = Policy::get_pg_order($conn, $ctx, $group1[0]->get_order(), 'up');
        $group2 = Policy_group::get_list($conn, $ctx, " AND policy_group.order=$pg_ord");
		$pg_src = $group2[0];
		$pg_dst = $group1[0];		
	}
	elseif ($order == "down") 
	{
		$pg_ord = Policy::get_pg_order($conn, $ctx, $group1[0]->get_order(), 'down');
		$group2 = Policy_group::get_list($conn, $ctx, " AND policy_group.order=$pg_ord");
		$pg_src = $group1[0];
		$pg_dst = $group2[0];
	}
	
	if(is_object($pg_src) && is_object($pg_dst))
	{
		echo "Swapping: id1=" . $pg_dst->get_group_id() . ",order1=" . $pg_src->get_order() . ",id2=" . $pg_dst->get_group_id() . ",order2=" . $pg_dst->get_order() . "<br>\n";
		Policy_group::swap_orders($conn, $pg_src->get_ctx(), $pg_src->get_group_id() , $pg_src->get_order() , $pg_dst->get_group_id() , $pg_dst->get_order());
		$infolog = array(
			$pg_dst->get_name() . "(" . $pg_dst->get_group_id() .")",
			$pg_dst->get_name() . "(" . $pg_dst->get_group_id() .")"
		);
		
		Log_action::log(99, $infolog);
		Web_indicator::set_on("Reload_policies");
		Web_indicator::set_on("ReloadPolicy");	
	}
	
}

$db->close();
