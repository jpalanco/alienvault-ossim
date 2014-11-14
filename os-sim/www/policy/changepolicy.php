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


//Version
$pro        = Session::is_pro();

$policy_src = "";
$order_src  = "";
$group_src  = "";


$policy_dst = "";
$order_dst  = "";
$group_dst  = "";

$policy_src = GET('src');
$policy_dst = GET('dst');


$log_dst    = "";

ossim_valid($policy_src, OSS_HEX, 'illegal:' . _("Src"));
ossim_valid($policy_dst, OSS_HEX,OSS_PUNC,OSS_ALPHA, 'illegal:' . _("Dst")); //this can be either a policy id or a group_id:group_name

if (ossim_error()) 
{
    die(ossim_error());
}

$db         = new ossim_db();
$conn       = $db->connect();

$group_src  = Policy::get_group_from_id($conn, $policy_src);
$policy_src = Policy::get_list($conn, " AND id=UNHEX('$policy_src')");



if(is_array($policy_src) && !empty($policy_src))
{
	$policy_src = array_shift($policy_src);
	$order_src  = $policy_src->get_order();
	
} 
else 
{
	die('Source was an incorrect parameter.');
	
}

//Context
if($pro)
{
	$ctx = $policy_src->get_ctx();
} 
else 
{
	$ctx = Session::get_default_ctx();
}


//Checking if the destiny is a policy group or a policy

if (preg_match("/\:/", $policy_dst))  //Policy Group
{	

    $group_dst = (preg_replace("/\:.*/", "", $policy_dst)) ;
    $log_dst   = (preg_replace("/.*\:/", "", $policy_dst)) ;
    ossim_valid($group_dst, OSS_HEX, 'illegal:' . _("Dst"));
    
	if (ossim_error()) 
	{
	    die(ossim_error());
	}
	
	if($group_dst != $group_src)
	{
		$order_dst = Policy::get_next_order($conn, $ctx, $group_dst);
	}
	else
	{
		$where      = " AND ctx=UNHEX('$ctx') AND policy.group=UNHEX('$group_dst') ORDER BY policy.order DESC LIMIT 1";
		$policy_dst = Policy::get_list($conn, $where);
		
		if(is_array($policy_dst) && !empty($policy_dst))
		{
			$policy_dst = array_shift($policy_dst);
		}
	}

} 
else  //Policy
{	

	ossim_valid($policy_dst, OSS_HEX, 'illegal:' . _("Dst"));
	
	if (ossim_error()) 
	{
	    die(ossim_error());
	}
	
    $group_dst  = Policy::get_group_from_id($conn, $policy_dst);    
    $policy_dst = Policy::get_list($conn, " AND id=UNHEX('$policy_dst')");
    
    if(is_array($policy_dst) && !empty($policy_dst))
    {
		$policy_dst = array_shift($policy_dst);
		$order_dst  = $policy_dst->get_order();
		
	} 
	else 
	{
		die('Source was an incorrect parameter.');
	}
	
    $log_dst = $order_dst." (". $policy_dst->get_id() .")" ;
}


if ($group_src == $group_dst) 
{
    // same group => swap
	Policy::swap_simple_orders($conn, $policy_src, $policy_dst);
	
} 
else 
{

    // different group => especial swap
    if ($order_src < $order_dst) 
    {
        // Only change group (do not change order value)
		if ($order_src == $order_dst - 1) 
		{
		  Policy::change_group($conn,$policy_src->get_id(),$group_dst);
		}
		else  // Else change orders and group
		{
			for ($i = $order_src; $i < $order_dst-1; $i++) 
			{
				Policy::swap_orders($conn, $i, $i + 1, $group_dst, $ctx, "src");
			}
		}
		
    } 
    else 
    {
        if ($order_src == $order_dst) 
        {
            Policy::change_group($conn, $policy_src->get_id(), $group_dst);
        }
		
        for ($i = $order_src; $i > $order_dst; $i--) 
        {
			Policy::swap_orders($conn, $i - 1, $i, $group_dst, $ctx, "dst");
        }
    }
}

$infolog = array(
	$order_src." (". $policy_src->get_id() .")",
	$log_dst
);

Log_action::log(98, $infolog);
Web_indicator::set_on("Reload_policies");
Web_indicator::set_on("ReloadPolicy");

$db->close();

