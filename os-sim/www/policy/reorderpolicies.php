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


$db   = new ossim_db();
$conn = $db->connect();

//Getting the policies…

$ctxs = array();
$ctxs = Policy::get_all_ctx($conn);

foreach($ctxs as $ctx)
{
	$neworder      = 1;
	$policy_groups = Policy_group::get_list($conn, " AND ctx=UNHEX('$ctx')", true);
	
	foreach($policy_groups as $group) 
	{
		$policy_list = Policy::get_list($conn, "AND ctx=UNHEX('$ctx') AND policy.group=UNHEX('".$group->get_group_id()."') ORDER BY policy.order, priority");
		
		foreach($policy_list as $policy) 
		{
			$id = $policy->get_id();
			
			$conn->Execute("UPDATE policy SET policy.order=$neworder WHERE id=UNHEX('$id')");
			
			$neworder++;
		}
	}
	
	$reorderpolicygrps = Policy_group::get_list($conn, " AND ctx=UNHEX('$ctx')", FALSE);
	$neworder          = 1;
	
	foreach($reorderpolicygrps as $policy) 
	{
        $conn->Execute("UPDATE policy_group SET policy_group.order=$neworder WHERE id=UNHEX('".$policy->get_group_id()."')"); 
        
        $neworder++;
	}
}

$db->close();

header("Location: ".Menu::get_menu_url('/ossim/policy/policy.php', 'configuration', 'threat_intelligence', 'policy'));
