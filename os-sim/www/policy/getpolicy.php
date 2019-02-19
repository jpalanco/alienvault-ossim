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


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";


require_once 'policy_common.php';


$ctx   = GET('ctx');
$group = GET('group');


$order = GET('sortname');
$order = ( empty($order) ) ? POST('sortname') : $order;


//Search item
$field  = POST('qtype');
$search = GET('query');

if (empty($search))
{ 
	$search = POST('query');
}

$page = (!empty($_POST['page'])) ? POST('page') : 1;
$rp   = (!empty($_POST['rp'])  ) ? POST('rp')   : 25;

$lsearch = $search;


ossim_valid($group,     OSS_HEX, 	                                    'illegal:' . _("group"));
ossim_valid($ctx, 	    OSS_HEX, 	                                    'illegal:' . _("ctx"));
ossim_valid($order, 	OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, 	'illegal:' . _("order"));
ossim_valid($page, 		OSS_DIGIT, OSS_NULLABLE, 						'illegal:' . _("page"));
ossim_valid($rp, 		OSS_DIGIT, OSS_NULLABLE, 						'illegal:' . _("rp"));
ossim_valid($search, 	OSS_TEXT, OSS_NULLABLE, 						'illegal:' . _("search"));
ossim_valid($field, 	OSS_ALPHA, OSS_NULLABLE, 						'illegal:' . _("field"));


if (ossim_error()) 
{
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}

if (!empty($order)) 
{
    $order.= (POST('sortorder') == "asc") ? "" : " desc";
}
else
{
	$order = "priority";
}

$where  = ($group != "0") ? "AND policy.group=UNHEX('$group')" : "AND policy.group=UNHEX('00000000000000000000000000000000')";

$start  = (($page - 1) * $rp);
$limit  = "LIMIT $start, $rp"; 

$db     = new ossim_db();
$conn   = $db->connect();

$xml    = "";

$filter = get_filters_names($conn);	


$policy_list = Policy::get_list($conn, "AND ctx=UNHEX('$ctx') $where ORDER BY policy.$order");

if ($policy_list[0]) 
{
    $total = $policy_list[0]->get_foundrows();
    
    if ($total == 0) 
    {
		$total = count($policy_list);
	}
} 
else
{
	$total = 0;
}

$engine = (is_ctx_engine($conn, $ctx)) ? 'engine' : 'ctx';

$xml .= "<rows>\n";
$xml .= "<page>$page</page>\n";
$xml .= "<total>$total</total>\n";

if($total > 0)
{
	list($conds_list, $pol_conds) = Policy::get_conditions_hash($conn, $ctx);
}

$server_sign_line = Policy::is_allowed_sign_line($conn);

$value_any = '<span style="color:#AAA;font-weight:bold">'. _('ANY') .'</span>';

foreach($policy_list as $policy) 
{
    $id     = $policy->get_id();
    $order  = $policy->get_order();
    $xml   .= "<row id='$id' col_order='$order' col_type='$engine'>";
    $tabla  = "<img src='../pixmaps/tables/cross.png' border='0'/>";
    $active = (!$policy->get_active()) ? $tabla : str_replace("cross", "tick", $tabla);
    $xml   .= "<cell><![CDATA[" . $active . "]]></cell>";
    $xml   .= "<cell><![CDATA[" . $order . "]]></cell>";
	$pname  = $policy->get_descr();
	$pname  = (empty($pname)) ? _("Unknown") : "<a href='newpolicyform.php?ctx=$ctx&id=$id'>$pname</a>";
	
	/*Warning Tooltip */
	$tooltip    = '';
	
	//Role list
	$role_list  = $policy->get_role($conn);
	
	if (is_object($role_list[0]))
	{
    	if ($role_list[0]->get_sign() == 1 && !$server_sign_line)
    	{
        	$tooltip .= _('- This policy is trying to use Log Line Sign and the AlienVault Server only allows Log Block Sign. In order to get the policy working, you can modify the Log Sign method in Deployment -> Servers.');
    	}
	}
	
	//Checking if the policy has a conflict 
	$md5        = $pol_conds[$id];
	$collisions = $conds_list[$md5];
    
	if(count($collisions) > 1)
	{
        $tooltip .= (empty($tooltip)) ? '' : '<br/><br/>';
 		$tooltip .= _('- A collision was found in the next policies') . ":<br/><ul id='policy_collision_list'>";
		
		foreach($collisions as $c)
		{
			$tooltip .= "<li>$c</li>";
		}
		$tooltip .= "</ul>";
	}	
	
	if (!empty($tooltip))
	{
	    $pname = '<a href="javascript:;" class="tiptip" title="'. Util::htmlentities($tooltip) .'"><img src="../pixmaps/tables/warning.png"/></a> ' . $pname;
	}
	
	
	/****************************************/
	
	$xml   .= "<cell><![CDATA[" . $pname . "]]></cell>";
    $source = "";




    if ($engine != 'engine')
    {
	$create_ico = function($text,$type) {
		return "<img src='../pixmaps/theme/$type.png' align=absbottom /> $text";
	};
	$decorator_vars = array("host","net","host_group","net_group");
	$get_cell = function($type) use ($conn,$create_ico,$decorator_vars,$policy) {
		return "<cell><![CDATA[" .implode("<br/>",$policy->get_srcdst_cell($type,$conn,$create_ico,$decorator_vars)). "]]></cell>";
	};
	$xml .= $get_cell("source").$get_cell("dest");
        //Ports source
        $ports = "";
        if ($port_list = $policy->get_ports($conn, 'source'))
        {
            foreach($port_list as $port_group) 
            {
                if(!check_any($port_group->get_port_id()))
                {
                     $ports.= ($ports == "" ? "" : "<br/>") . Port_group::get_name_by_id($conn, $port_group->get_port_id());
                }
            }
        }
        
        if (empty($ports)) 
        {
            $ports = $value_any;
        }
        
        $xml.= "<cell><![CDATA[" . $ports . "]]></cell>";
        

        //Ports destiny
        $ports = "";
        if ($port_list = $policy->get_ports($conn, 'dest')) 
        {
            foreach($port_list as $port_group) 
            {
                if(!check_any($port_group->get_port_id()))
                {
                     $ports.= ($ports == "" ? "" : "<br/>") . Port_group::get_name_by_id($conn, $port_group->get_port_id());
                }
            }
        }
        
        if (empty($ports)) 
        {
            $ports = $value_any;
        }

        $xml.= "<cell><![CDATA[" . $ports . "]]></cell>";

	}

	//Event Types
	$event_types = '';
	$flag_events = true;
	
	//DS Groups
    $plugingroups = "";
    
    if ($policy_pgroups = $policy->get_plugingroups($conn, $policy->get_id()))
    {
        foreach($policy_pgroups as $group) 
        {
            $plugingroups.= ($plugingroups == "" ? "" : "<br/>") . "<a href='javascript:;' onclick='GB_show(\""._("Plugin groups")."\",\"plugingroups.php?id=" . $group['id'] . "&collection=1#".$group['id']."\",500,\"90%\");return false;'>" . $group['name'] . "</a>";
        }
    }
    else
    {
        $plugingroups = $value_any;
    }
   
	//Taxonomy
	$taxonomy = '';
	
	if ($taxonomy_list = $policy->get_taxonomy_conditions($conn))
	{
		$tax_filters = array();
		
		foreach($taxonomy_list as $tax) 
		{				
			$taxonomy .= $filter['ptype'][$tax->get_product_type_id()] . " | " . $filter['cat'][$tax->get_category_id()] . " | " . $filter['subcat'][$tax->get_subcategory_id()]."<br>";
			
			$flag_events   = false;
		}
	}
   
    if($flag_events)
    {
    	$event_types = "<b>" . _('DS Groups') . ":</b><br>" . $plugingroups;
    } 
    else 
    {
    	$event_types = "<b>" . _('Taxonomy') . ":</b><br>"  . $taxonomy;
    }
   
    $xml.= "<cell><![CDATA[" . $event_types . "]]></cell>";

    if ($engine != 'engine')
    {
        $sensors = "";

        $sensor_exist=$policy->exist_sensors($conn);
        
        if ($sensor_list = $policy->get_sensors($conn)) 
        {
            foreach($sensor_list as $sensor) 
            {
        		if(!check_any($sensor->get_sensor_id()))
        		{
        			$sensors.= ($sensors == "" ? "" : "<br/>") . Av_sensor::get_name_by_id($conn, $sensor->get_sensor_id());
        			
        			if($sensor_exist[$sensor->get_sensor_id()]=='false')
        			{
        				$sensors  ='<div title="'._('sensor non-existent').'">' . $sensors;
        				$sensors .= '<a href="newpolicyform.php?id='.$id.'&sensorNoExist=true#tabs-5"><img style="vertical-align: middle" src="../pixmaps/tables/cross-small-circle.png" /></a></div>';
        			}
        		}
            }
        }
        
    	if (empty($sensors)) 
    	{
        	$sensors = $value_any;
        }
        
        $xml.= "<cell><![CDATA[" . $sensors . "]]></cell>";

    }
	
    if ($policy_time = $policy->get_time($conn)) 
    {
		$tzone        = $policy_time->get_timezone();
		$begin_range  = "";
		
		$day          = $policy_time->get_week_day_start();
		
		if(!empty($day))
		{
			$begin_range .= Util::get_day_name($day, 'short') .", ";
		}
		
		$day          = $policy_time->get_month_day_start();
		
		if(!empty($day))
		{
			$begin_range .= ($day == 1) ? $day."st, " : (($day==2)? $day."nd, " : $day."th, ");
		}
		
		$month        = $policy_time->get_month_start();
		
		if(!empty($month))
		{	
			$begin_range .= Util::get_month_name($month, 'short') .", ";
		}
		
		$hour         = $policy_time->get_hour_start();
		$min          = $policy_time->get_minute_start();
		

		if(isset ($hour) && isset ($min))
		{
			$begin_range .= $hour ."h : " . $min ."min";
		}

		$end_range  = "";
		$day        = $policy_time->get_week_day_end();
		
		if(!empty($day))
		{
			$end_range .= Util::get_day_name($day, 'short') .", ";
		}
		
		$day        = $policy_time->get_month_day_end();
		
		if(!empty($day))
		{
			$end_range .= ($day == 1) ? $day."st, " : (($day==2)? $day."nd, " : $day."th, ");
        }
		
		$month      = $policy_time->get_month_end();
		
		if(!empty($month))
		{
			$end_range .= Util::get_month_name($month, 'short') .", ";
		}
		
		
		$hour       = $policy_time->get_hour_end();
		$min        = $policy_time->get_minute_end();
		
		if(isset($hour) && isset($min))
		{
			$end_range .= $hour ."h : " . $min ."min";
		}
		
        $xml.= "<cell><![CDATA[". $tzone . "<br>" . $begin_range . "<br>" . $end_range . "]]></cell>";
        
    } 
    else 
    {
        $xml.= "<cell><![CDATA[$value_any]]></cell>";
    }
    
    $targets = "";
	
    if ($target_list = $policy->get_targets($conn)) 
    {
		foreach($target_list as $target) 
		{
			if(!check_any($target->get_target_id()))
			{
				$targets.= ($targets == "" ? "" : "<br/>") . Server::get_name_by_id($conn, $target->get_target_id());
			}
		}
	}
	
	if (empty($targets))
	{
    	$targets = $value_any;
    }
	
    $xml.= "<cell><![CDATA[" . $targets . "]]></cell>";
        
    if (count($role_list) < 1) 
    {
			$xml.= "<cell></cell>";
			$xml.= "<cell><![CDATA[" . (($policy->get_priority()==-1) ? "-":$policy->get_priority()) . "]]></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
			//$xml.= "<cell></cell>";
			
            if ($engine != 'engine')
            {
                $xml.= "<cell></cell>";
            }
            
			$xml.= "<cell></cell>";
			$xml.= "<cell></cell>";
	} 
	else 
	{
		foreach($role_list as $role) 
		{
			$xml.= "<cell><![CDATA[" . ($role->get_sim() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . (($policy->get_priority()==-1) ? "-" : $policy->get_priority()) . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_qualify() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_correlate() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_cross_correlate() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_store() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			//$xml.= "<cell><![CDATA[" . ($role->get_reputation() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			
			if ($engine != 'engine')
			{
    			$xml.= "<cell><![CDATA[" . ($role->get_sem() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
    		}
    		
			$xml.= "<cell><![CDATA[" . ($role->get_sign() ? _('Line') : _('Block')) . "]]></cell>";
			$xml.= "<cell><![CDATA[" . ($role->get_resend_event() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
			break;
		}
	}
	
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";

echo $xml;

$db->close();
