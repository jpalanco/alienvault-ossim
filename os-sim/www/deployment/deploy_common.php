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



/*******************************************************
******             PERMS FUNCTIONS               ******
********************************************************/

function check_deploy_perms()
{
	
	if (!Session::am_i_admin())
	{
		 $config_nt = array(
			'content' => _("You do not have permission to see this section"),
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => false
			),
			'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		
		die();
	}
	
	return true;

}




/*******************************************************
******               COMMON FUNCTIONS             ******
********************************************************/

function get_total($conn, $query, $params=array())
{
	
	if (!$rs = $conn->CacheExecute($query, $params))
	{
		return 0;
	} 
	else 
	{
		return $rs->fields["total"];
		
	}

}


/*******************************************************
******            DEPLOYMENT FUNCTIONS            ******
********************************************************/

//GLOBAL VISIVILITY
//return array(percent of location without sensors, number of sensors with location, total of sensors)
function get_global_visibility($conn)
{
	
	/*
	//Total amount of sensors
	$query   = "SELECT COUNT(*) AS total FROM sensor;";
	$total_s = get_total($conn, $query);
	
	//Total amount of sensors with locations
	$query    = "select count(distinct sensor_id) as total from location_sensor_reference WHERE sensor_id <> UNHEX('00000000000000000000000000000000')";
	$total_sl = get_total($conn, $query);
	
	*/

	//Total amount of locations
	$query   = "SELECT COUNT(*) AS total FROM locations;";
	$total_l = get_total($conn, $query);
	
	if ($total_l == 0) return array(-1, 0, 0);
	
	
	//Total amount of locations with sensors  
	$query    = "select count(distinct location_id) as total from location_sensor_reference WHERE sensor_id <> UNHEX('00000000000000000000000000000000')";
	$total_ls = get_total($conn, $query);
	
	
	$percent = round(($total_ls/$total_l)*100);
	
	return array($percent, $total_ls, $total_l);
	

}



/*
ASSETS VISIVILITY
$conn,
$options = array(
	type     => '4(networks)|1(servers)'
	network  => 'id|empty'
	location => 'id|empty'
)
*/
function get_asset_visibility($conn, $options)
{
	
	$net_search = '';
	$loc_search = '';
	$params     = array();
	$tparams    = array();
	
	switch($options['type'])
	{	
		case 'asset_server':
			$params[]  = 1;
			$tparams[] = 1;
			break;
			
		case 'asset_network':
			$params[]  = 4;
			$tparams[] = 4;
			break;
			
		default:
			return 0;
			
	}
	
	if (!empty($options['network']))
	{
		$net_search = ' AND s.net_id=unhex(?) ' ;
		$params[]   = $options['network'];
		$tparams[]  = $options['network'];
	}
	
	if (!empty($options['location']))
	{
		$loc_search = ' AND l.location_id=unhex(?) ' ;
		$params[]   = $options['location'];
		$tparams[]  = $options['location'];
	}

	if (isset($params[2]))
	{
		$params[3] = $params[0];
		$params[4] = $params[1];
		$params[5] = $params[2];
	}
	elseif (isset($params[1]))
	{
		$params[2] = $params[0];
		$params[3] = $params[1];
	}
	else
	{
		$params[1] = $params[0];
	}
	
	$query = " 
			select distinct hex(src_host) as total 
				from alienvault_siem.ac_acid_event 
				where src_host in (
									select distinct h.host_id 
										from alienvault.host_net_reference c,alienvault.host_types t,alienvault.host_ip h,alienvault.net_sensor_reference s,alienvault.location_sensor_reference l 
										where s.sensor_id=l.sensor_id and c.net_id=s.net_id AND c.host_id=h.host_id AND h.host_id=t.host_id AND t.type=? $net_search $loc_search 
								  )
								  
			UNION
			
			select distinct hex(dst_host) as total 
				from alienvault_siem.ac_acid_event 
				where dst_host in (
									select distinct h.host_id 
										from alienvault.host_net_reference c,alienvault.host_types t,alienvault.host_ip h,alienvault.net_sensor_reference s,alienvault.location_sensor_reference l 
										where s.sensor_id=l.sensor_id and c.net_id=s.net_id AND c.host_id=h.host_id AND h.host_id=t.host_id AND t.type=? $net_search $loc_search 
								  )
	";
	
	if (!$rs = $conn->CacheExecute($query, $params))
	{
		$val = 0;
	} 
	else 
	{
		$val = $rs->_numOfRows;
		
	}
	
	
	$query_total =	"SELECT COUNT(distinct h.host_id) AS total 
						FROM alienvault.host_net_reference c,alienvault.host_types t,alienvault.host_ip h,alienvault.net_sensor_reference s,alienvault.location_sensor_reference l 
						WHERE s.sensor_id=l.sensor_id AND c.net_id=s.net_id AND c.host_id=h.host_id AND h.host_id=t.host_id AND t.type=? $net_search $loc_search";
	
	
	$total = get_total($conn, $query_total, $tparams);

	
	if ($total == 0) return array(-1, $val, $total);

	
	$percent = round(($val/$total)*100);
	
	return array($percent, $val, $total);

}


/*
NETWORK VISIVILITY
$conn,
$options = array(
	type     => 'ids,vulns,passive,active,netflow'
	percent  => 'true|false'
	network  => 'id|empty'
	location => 'id|empty'
)
*/
function get_network_visibility($conn, $options)
{
	
	$net_search = '';
	$loc_search = '';	
	$params_t   = array();
	
	if (!empty($options['network']))
	{
		$net_search = ' AND r.net_id=unhex(?) ' ;
		$params[]   = $options['network'];

	}
	
	if (!empty($options['location']))
	{
		$loc_search = ' AND l.location_id=unhex(?) ' ;
		$params[]   = $options['location'];
		$params_t[] = $options['location'];
	}
	
	switch($options['type'])
	{	
		case 'ids':
			
			$discar_disabled = "and l.location_id=j.id and (conv(HEX(j.checks), 2, 10) & conv('100000', 2, 10) = 32)";
			
			$query  = "select count(distinct HEX(r.net_id)) as total 
				from sensor s 
				left join sensor_properties sp on sp.sensor_id=s.id,
				location_sensor_reference l,net_sensor_reference r, locations j 
				where s.id=l.sensor_id AND r.sensor_id=l.sensor_id and sp.ids=1 $discar_disabled $net_search $loc_search ";

			$query_total = "SELECT COUNT(distinct r.net_id) AS total FROM 
				location_sensor_reference l,net_sensor_reference r, locations j 
				WHERE r.sensor_id=l.sensor_id  $discar_disabled $loc_search";
							
			break;
			
		case 'vulns':
		
			$discar_disabled = "and l.location_id=j.id and (conv(HEX(j.checks), 2, 10) & conv('010000', 2, 10) = 16)";
		
			$query  = "select count(distinct HEX(r.net_id)) as total
				from net n,vuln_job_schedule vjs,net_sensor_reference r,location_sensor_reference l, locations j 
				where r.sensor_id=l.sensor_id and n.id=r.net_id AND vjs.meth_TARGET like CONCAT('%', HEX(n.id) ,'%') $discar_disabled $net_search $loc_search";
			
			$query_total = "SELECT COUNT(distinct r.net_id) AS total FROM 
				location_sensor_reference l,net_sensor_reference r, locations j
				WHERE r.sensor_id=l.sensor_id $discar_disabled $loc_search";
				
			break;
			
		case 'passive':
		
			$discar_disabled = "and l.location_id=j.id and (conv(HEX(j.checks), 2, 10) & conv('001000', 2, 10) = 8)";
		
			$query  = "select distinct count(distinct HEX(r.net_id)) as total
				from sensor s 
				left join sensor_properties sp on sp.sensor_id=s.id,
				location_sensor_reference l,net_sensor_reference r, locations j
				where s.id=l.sensor_id AND r.sensor_id=l.sensor_id and sp.passive_inventory=1 $discar_disabled $net_search $loc_search";
			
			$query_total = "SELECT COUNT(distinct r.net_id) AS total FROM 
				location_sensor_reference l,net_sensor_reference r, locations j 
				WHERE r.sensor_id=l.sensor_id $discar_disabled $loc_search";
			
			break;
			
		case 'active':
		
			$discar_disabled = "and l.location_id=j.id and (conv(HEX(j.checks), 2, 10) & conv('000100', 2, 10) = 4)";
			
			$query  = "select count(distinct r.net_id) as total
				from task_inventory t, location_sensor_reference l, net_sensor_reference r, locations j  
				where t.task_sensor=l.sensor_id AND t.task_enable=1 AND t.task_type=5 AND r.sensor_id=l.sensor_id AND t.task_targets like CONCAT('%', HEX(r.net_id) ,'%') $discar_disabled $net_search $loc_search ";
			
			$query_total = "SELECT COUNT(distinct r.net_id) AS total FROM 
				location_sensor_reference l,net_sensor_reference r, locations j 
				WHERE r.sensor_id=l.sensor_id $discar_disabled $loc_search";
				
			break;
			
		case 'netflow':
		
			$discar_disabled = "and l.location_id=j.id and (conv(HEX(j.checks), 2, 10) & conv('000010', 2, 10) = 2)";
			
			$query  = "select count(distinct r.net_id) as total 
				from sensor s
				left join sensor_properties sp on sp.sensor_id=s.id,
				location_sensor_reference l,net_sensor_reference r, locations j 
				where s.id=l.sensor_id AND r.sensor_id=l.sensor_id and sp.netflows=1 $discar_disabled $net_search $loc_search ";
			
			$query_total = "SELECT COUNT(distinct r.net_id) AS total FROM 
				location_sensor_reference l,net_sensor_reference r, locations j 
				WHERE r.sensor_id=l.sensor_id $discar_disabled $loc_search";
				
			break;
						
	}
	
	if (!$rs = $conn->CacheExecute($query, $params))
	{
		$val = 0;
	} 
	else 
	{
		$val = $rs->fields["total"];
	}
	
	
	if ($options['percent'])
	{				
		$total = get_total($conn, $query_total, $params_t);
			
        if ($total == 0 ) 
        {
            return array(-1, $val, $total);
        }
		
		if ($total == 0 || $val == 0)
		{
    		return array(0, $val, $total);
		} 
		
		$percent = round(($val/$total)*100);
		
		return array($percent, $val, $total);		
	}
	else
	{
		if ($val > 0) return 1;
		
		return 0;
	}

}


/*******************************************************
******             COLORIZE FUNCTIONS             ******
********************************************************/

function colorize_location($conn, $id)
{
	$color     = 0;
	$ignored   = 0;
	$class     = '';
	
	$checks    = Locations::get_location_checks($conn, $id);
	
	$types     = array('ids', 'vulns', 'passive', 'active', 'netflow');
	
	foreach ($types as $t_pos => $t)
	{
		if (strlen($checks) == 5 && $checks[$t_pos] == 0)
		{
			$ignored++;
		}
		else
		{
			$options = array("type" => $t, "percent" => true, "location" => $id);
			list($var, $v, $t) = get_network_visibility($conn, $options);
			
			if ($var < 100) 
			{
				$color++;
			}
		}
	}
	
	$nerror = count($types) - $ignored;

	switch($color)
	{
		case 0:
			$class = 'l_success';
			break;
		
		case $nerror:
			$class = 'l_error';
			break;
		
		default:
			$class = 'l_warning';
	}
	
	
	if ($class == 'l_success')
	{
		$options = array("type" => 'asset_network', "location" => $id);
		$a_n    = get_asset_visibility($conn, $options);
		
		$options = array("type" => 'asset_server', "location" => $id);
		$a_s     = get_asset_visibility($conn, $options);
			
		$class   = (($a_n[0] + $a_s[0]) < 200) ? 'l_warning' : $class;
	}
	
	return $class;
}


function colorize_nets($conn, $id, $location)
{
	$color     = 0;
	$ignored   = 0;
	$class     = '';
	
	$checks    = Locations::get_location_checks($conn, $location);
	
	$types     = array('ids', 'vulns', 'passive', 'active', 'netflow');
	
	foreach ($types as $t_pos => $t)
	{
		if (strlen($checks) == 5 && $checks[$t_pos] == 0)
		{
			$ignored++;
		}
		else
		{
			$options = array("type" => $t, "percent" => false, "network" => $id);
			$var     = get_network_visibility($conn, $options);
			
			if (!$var) 
			{
				$color++;
			}
		}
	}

	$nerror = count($types) - $ignored;
	
	switch($color)
	{
		case 0:
			$class = 'l_success';
			break;
		
		case $nerror:
			$class = 'l_error';
			break;
		
		default:
			$class = 'l_warning';
	}
	
	
	if ($class == 'l_success')
	{
		$options = array("type" => 'asset_network', "network" => $id);
		$a_n    = get_asset_visibility($conn, $options);
		
		$options = array("type" => 'asset_server', "network" => $id);
		$a_s     = get_asset_visibility($conn, $options);
			
		$class   = (($a_n[0] + $a_s[0]) < 200) ? 'l_warning' : $class;
	}
	
	return $class;
}
