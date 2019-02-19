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



function check_any($item)
{

	if(!strcasecmp($item, "any") || $item == "00000000000000000000000000000000" || $item=='')
	{
		return true;
	} 
	else 
	{
		return false;
	}
}


function get_filters_names($conn)
{

	$filter = array();
	
	//Reputation Activities
	$filter['act'] = Reputation::get_reputation_activities($conn);


	//Product types
	$product_types      = Product_type::get_list($conn);	
	$filter['ptype'][0] = _('ANY');

	foreach($product_types as $ptype) 
	{
		$filter['ptype'][$ptype->get_id()] = $ptype->get_name();
	}
	
	
	//Subcategories
	$subcategories       = Subcategory::get_list($conn);
	$filter['subcat'][0] = _('ANY');

	foreach($subcategories as $subc) 
	{
		$filter['subcat'][$subc->get_id()] = $subc->get_name();
	}
	
	
	//Categories
	$categories       = Category::get_list($conn);
	$filter['cat'][0] = _('ANY');

	foreach($categories as $cat) 
	{
		$filter['cat'][$cat->get_id()] = $cat->get_name();
	}
	
	return $filter;
}


function is_ctx_engine($conn, $ctx)
{
	$sql = "select entity_type FROM acl_entities WHERE id=UNHEX(?);";
					
    $rs = $conn->Execute($sql, array($ctx));
					
	if (!$rs)
	{
		return false;
	}
	else 
	{
		if($rs->fields["entity_type"] == 'engine')
		{
			return true;
		}
        else
        {
            return false;
        }
	}

}
