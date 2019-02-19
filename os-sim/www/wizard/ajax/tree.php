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
if (!Session::am_i_admin())
{
    echo json_encode($empty_tree);
    exit -1;
}


//Default Empty Tree
$empty_tree = array(
    'title'        => _("No Assets Found"),
    'noLink'       => TRUE,
    'expand'       => FALSE,
    'icon'         => FALSE,
    'hideCheckbox' => TRUE
);


/*
 * <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------> 
 */


/*
* This function get a tree of networks from a given OS. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Tree info: OS 
*
* @return array
*
*/
function draw_nets_by_os($conn, $data)
{
    global $empty_tree;
    
    $tree = array();
    $os   = $data['os'];
    
    ossim_valid($os,    "windows|linux",    'illegal:' . _("Operating System"));

    if (ossim_error())
    {
    	ossim_clean_error();

    	return $empty_tree;
    }


    if ($os == 'windows')
    {
        $os_sql = 'AND (hp.value LIKE "windows%" OR hp.value LIKE "microsoft%")';
    }
    else
    {
        $os_sql = 'AND (hp.value LIKE "%linux%" OR hp.value LIKE "%alienvault%")';
    }

    $sql = "SELECT DISTINCT hex(n.id) AS id, n.name AS name, n.ips as cidr
                FROM host_properties hp, host h
                LEFT JOIN host_net_reference hn ON hn.host_id=h.id
                LEFT JOIN net n ON n.id=hn.net_id
                WHERE h.id=hp.host_id AND hp.property_ref=3 $os_sql";


    //Always cached
    $rs = $conn->CacheExecute($sql, $params);
    
    if (!$rs || $rs->EOF) 
    {
        return $empty_tree;
    }
    
    while (!$rs->EOF)
    {
        $id      = (is_null($rs->fields['id'])) ? '0' : $rs->fields['id'];
        
        $name    = (is_null($rs->fields['name'])) ? _('Others Hosts') : $rs->fields['name'];
        
        $tooltip = (is_null($rs->fields['cidr'])) ? _('Others Hosts') : 'CIDRs: ' . $rs->fields['cidr'];
        
        $_aux = array(
            'key'          => 'net_' . $id,
            'title'        => $name,
            'isFolder'     => TRUE,
            'isLazy'       => TRUE,
            'icon'         => FALSE,
            'hideCheckbox' => TRUE,
            'type'         => 'net',
            'net_id'       => $id,
            'tooltip'      => $tooltip
        );
        
        $tree[] = $_aux;
        
        $rs->MoveNext();
    }
    
    return $tree;

}


/*
* This function get a tree of host from a given OS under a given network. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Tree info: OS and net ID
*
* @return array
*
*/
function draw_hosts_by_nets_os($conn, $data)
{
    global $empty_tree;
    
    $tree = array();
    $prm  = array();
    
    $os   = $data['os'];
    $id   = $data['net'];
    
    ossim_valid($os,    "windows|linux",    'illegal:' . _("Operating System"));
    ossim_valid($id,    OSS_HEX,            'illegal:' . _("Network"));

    if (ossim_error()) 
    {
    	ossim_clean_error();
    	
    	return $empty_tree;
    }


    if ($os == 'windows')
    {
        $os_sql = 'AND (hp.value LIKE "windows%" OR hp.value LIKE "microsoft%")';
    }
    else
    {
        $os_sql = 'AND (hp.value LIKE "%linux%" OR hp.value LIKE "%alienvault%")';
    }

    
    if ($id == '0')
    {
        $id_sql = ' AND n.id IS NULL';
    }
    else
    {
        $id_sql = ' AND n.id = UNHEX(?)';
        $prm[]  = $id;
    }
    
    $sql  = "SELECT DISTINCT hex(h.id) AS id , h.hostname AS name
                FROM host_properties hp, host h
                LEFT JOIN host_net_reference hn ON hn.host_id=h.id
                LEFT JOIN net n ON n.id=hn.net_id
                WHERE h.id=hp.host_id AND hp.property_ref=3 $os_sql $id_sql";

    //Always cached
    $rs = $conn->CacheExecute($sql, $prm);
    
    if (!$rs || $rs->EOF) 
    {
        return $empty_tree;
    }
    
    while (!$rs->EOF)
    {
        $tooltip = 'IPs: ' . Asset_host_ips::get_ips_to_string($conn, $rs->fields['id']);
        
        $_aux = array(
                'key'      => 'host_' . $rs->fields['id'],
                'title'    => $rs->fields['name'],
                'isFolder' => FALSE,
                'icon'     => FALSE,
                'type'     => 'host',
                'host_id'  => $rs->fields['id'],
                'tooltip'  => $tooltip
        );
        
        $tree[] = $_aux;
        
        $rs->MoveNext();
    }
    
    return $tree;
    
}

/*
 * <------------------------   END OF THE FUNCTIONS   ------------------------> 
 */
 
 



/*
 * <-------------------------   BODY OF THE SCRIPT   -------------------------> 
 */ 

$action = POST("action");   //Action to perform.
$data   = POST("data");     //Data related to the action.

ossim_valid($action,	OSS_INPUT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
	ossim_clean_error();
	
	echo json_encode($empty_tree);
	
	die();
}


//checking if it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Always cached
    $db   = new ossim_db(TRUE);
    $conn = $db->connect();
            
    if ($action == 'nets')
    {
        $tree = draw_nets_by_os($conn, $data);
        
        echo json_encode($tree);
    }
    elseif ($action == 'hosts')
    {
        $tree = draw_hosts_by_nets_os($conn, $data);
        
        echo json_encode($tree);
    }
    else
    {
        echo json_encode($empty_tree);
    }
    
    $db->close();

}
else
{
    echo json_encode($empty_tree);
}
