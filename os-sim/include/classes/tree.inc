<?php
/**
* tree.inc
*
* File tree.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
*
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Tree()
* - draw()
* - get_cclass_hosts()
* - get_xclass_nets()
* - draw_hosts()
* - draw_nets()
* - toggle_net()
* - draw_entity_assets()
* - draw_hostgroups()
* - toggle_hostgroup()
* - draw_netgroups()
* - toggle_netgroup()
* - draw_assets()
* - draw_sensors()
* - draw_alienvault_components()
* - draw_users()
* - draw_all_ausers();
* - draw_servers()
* - draw_databases()
* - draw_portgroups()
* - toggle_portgroup()
* - draw_entities()
* - echochildrens()
*/

define('MAX_RESULTS', 200);
define('LENGTH_NAME', 50);
define('LOW_LIMIT', 10);
define('HIGH_LIMIT', 100);

class Tree
{
    public $conn;


    public $keys;


    public $page;


    public $section;


    public $filter;


    public $length_name;


    public $extra_options;


    public $external_ctxs;


    /* Constructor: creates a tree object
     * Parameters:
     * - keys: elements of the tree
     *         "assets|entities|alienvaultcomponents" ->
     *         "e_UUID_host" -> Hosts of an entity
     *         "s_UUID_assets" -> Assets of a sensor
     *         "netgroup" -> All Networks Groups
     *         "host_192.168.10" -> Hosts by that CCLASS
     *
     * - page: for tree pagination
     *
     */
    public function __construct($keys, $page = 1, $filter = '', $section = '', $length_name = 0, $extra_options = '')
    {
        /* Connect to db */
        $db             = new ossim_db(TRUE);
        $this->conn     = $db->connect();
        $this->keys          = explode("|", $keys);
        $this->section       = $section;
        $this->page          = $page;
        $this->filter        = $filter;
        $this->length_name   = (empty($length_name)) ? LENGTH_NAME : $length_name;
        $this->extra_options = $extra_options;
        $this->external_ctxs = Session::get_external_ctxs($this->conn);
    }

    /* Draw the tree object as defined in his key field
     * KEY POSSIBLE VALUES:
     * - assets
     * - entities
     * - contexts
     * - entitiesassets
     * - entitiesusers
     * - entitiesassetsusers
     * - alienvaultcomponents
     * - host
     * - net
     * - hostgroup
     * - netgroup
     * - servers
     * - databases
     * - sensors
     * - ports
     * - properties
     * - property_ID
     * - e_UUID_property_ID
     * - properties_HostID
     * - e_UUID_properties
     * - services_HostID
     * - anomalies_HostID
     * - e_UUID_assets: Assets by Entity ID
     * - e_UUID_assetsup: Assets by Entity ID UP the tree
     * - s_UUID_assets: Assets by Sensor ID
     * - e_UUID_host: Hosts by Entity ID
     * - s_UUID_host: Hosts by Sensor ID
     * - *host_n.n.n: Toggle hosts by CCLASS
     * - e_UUID_net: Networks by Entity ID
     * - s_UUID_net: Networks by Sensor ID
     * - *net_n.n?.n?: Toggle a Network CLASS
     * - net_UUID: Toggle Network in host list
     * - e_UUID_hostgroup: Hostgroups by Entity ID
     * - hostgroup_UUID: Toggle Hostgroup
     * - e_UUID_netgroup: Netgroups by Entity ID
     * - netgroup_UUID: Toggle Netgroup
     * - ae_UUID: Assets by Entity ID
     * - ue_UUID: Users by Entity ID
     * - se_UUID: Sensors by Entity ID
     * - engineservers
     */
    public function draw()
    {
        $filter = $this->filter;
        echo "[";
        $flag = false;
        foreach ($this->keys as $key)
        {
            $entity_id = (preg_match("/^u?e_([^_]+)/", $key, $found)) ? $found[1] : '';
            $sensor_id = (preg_match("/^s_([^_]+)/", $key, $found))   ? $found[1] : '';

            // Note: entity_id could be a list of IDs
            ossim_valid($entity_id, OSS_NULLABLE, OSS_HEX, ',', 'illegal:' . _('Entity ID'));
            ossim_valid($sensor_id, OSS_NULLABLE, OSS_HEX,      'illegal:' . _('Sensor ID'));

            if (ossim_error())
            {
                die(ossim_error());
            }

            if ($flag)
            {
                echo ",";
            }

            if (preg_match("/(_|^)assets$/", $key)) { // Assets
                $this->draw_assets($entity_id, $sensor_id);

            }
            elseif (preg_match("/(_|^)assetsup$/", $key)) { // Assets UP
                $this->draw_assets_up($entity_id);
            }
            elseif ($key == "entities") { // Entities
                $this->draw_entities();
            }
            elseif ($key == "contexts") { // Context
                $this->draw_entities(FALSE, FALSE, TRUE);
            }
            elseif (preg_match("/^engineservers_(.*)/", $key, $found)){
                $server_id = $found[1];
                ossim_valid($server_id, OSS_HEX, '\,', 'illegal:' . _("Server ID"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                $this->draw_engines_server($server_id);
            }
            elseif ($key == "entitiesassets") { // With Assets
                $this->draw_entities(TRUE);
            }
            elseif ($key == "entitiesusers") { // With Users
                $this->draw_entities(FALSE, TRUE);
            }
            elseif ($key == "entitiesassetsusers") { // With Assets and Users
                $this->draw_entities(TRUE, TRUE);
            }
            elseif ($key == "alienvaultcomponents") { // AlienVault Components
                $this->draw_alienvault_components();
            }
            elseif ($key == "host") { // HOSTS
                $this->draw_hosts('', '', '', $filter);
            }
            elseif (preg_match("/^e_[^_]+_host$/", $key)) { // By Entity
                $this->draw_hosts($entity_id, '', '', $filter);

            }
            elseif (preg_match("/^e_[^_]+_hostup$/", $key)) { // By Entity UP (for entities_edit.php)
                $this->draw_hosts($entity_id, '', '', $filter, "up");

            }
            elseif (preg_match("/^s_[^_]+_host$/", $key)) { // By Sensor
                $this->draw_hosts('', $sensor_id, '', $filter);
            }
            elseif (preg_match("/^(e_.+_)?p_(.+:.*)_host(_.+)?$/", $key, $found)) // By Property
            {
                $property_key = $found[2];
                $cclass       = preg_replace("/^_/", '', $found[3]);
                ossim_valid($cclass,       OSS_DIGIT, OSS_DOT, OSS_NULLABLE,      'illegal:' . _("cclass"));
                ossim_valid($property_key, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Property Key"));

                if (ossim_error()) {
                    die(ossim_error());
                }

                // Search in new hostgroup form
                if ($cclass == '' && $filter != '')
                {
                    $cclass = $filter;
                }

                $this->draw_hosts($entity_id, '', $property_key, $cclass);
            }
            elseif (preg_match("/(_|^)host(up)?_(.*)/", $key, $found)) // By Class
            {
                $up     = $found[2];
                $cclass = $found[3];
                ossim_valid($cclass, OSS_DIGIT, OSS_DOT, 'illegal:' . _("cclass"));

                if (ossim_error()) {
                    die(ossim_error());
                }

                $this->draw_hosts($entity_id, $sensor_id, '', $cclass, $up);
            }
            elseif ($key == "net") { // All Networks
                $this->draw_nets('', '', $filter);
            }
            elseif (preg_match("/e_.+_net$/", $key)) { // By Entity
                $this->draw_nets($entity_id, '', $filter);
            }
            elseif (preg_match("/e_.+_netup$/", $key)) { // By Entity UP (for entities_edit.php)
                $this->draw_nets($entity_id, '', $filter, "up");
            }
            elseif (preg_match("/^s_.+_net$/", $key)) { // By Sensor
                $this->draw_nets('', $sensor_id, $filter);
            }
            elseif (preg_match("/^net(up)?_(\d+\.?\d*\.?\d*)$/", $key, $found) || preg_match("/^[es]_.+_net(up)?_(\d+\.?\d*\.?\d*)/", $key, $found)) // By Class
            {
                $up    = $found[1];
                $class = $found[2];
                ossim_valid($class, OSS_DIGIT, OSS_DOT, 'illegal:' . _("Network class CIDR"));

                if (ossim_error()) {
                    die(ossim_error());
                }

                $this->draw_nets($entity_id, $sensor_id, $class, $up);
            }
            elseif (preg_match("/^net_(.*)/",$key,$found)) // Toggle a Network (host list)
            {
                $net_id = $found[1];
                ossim_valid($net_id, OSS_HEX, 'illegal:' . _("Network ID"));
                if (ossim_error())
                {
                    die(ossim_error());
                }

                $this->toggle_net($net_id);
            }

            elseif ($key == "hostgroup")
            {
                //All hostgroups
                $this->draw_hostgroups();
            }
            elseif (preg_match("/^e_.+_hostgroup$/", $key)) { // By entity

                $filter = array(
                    'type'  => 'entity',
                    'value' => $entity_id
                );

                $this->draw_hostgroups($filter);
            }
            elseif (preg_match("/^s_[^_]+_hostgroup$/", $key))
            {
                // By sensor
                $filter = array(
                    'type'  => 'sensor',
                    'value' => $sensor_id
                );

                $this->draw_hostgroups($filter);
            }
            elseif (preg_match("/hostgroup_(.*)/", $key, $found))
            {
                // Toggle a host group

                $filter = array();
                $found  = explode(';', $found[1]);

                $hostgroup_id = $found[0];
                $filter       = $found[1];

                ossim_valid($hostgroup_id,  OSS_HEX, 'illegal:' . _('Hostgroup ID'));


                if (!empty($filter))
                {
                    if (preg_match("/^s_filter:/", $filter))
                    {
                        $sensor_id = str_replace('s_filter:', '', $filter);

                        $filter = array(
                            'type'  => 'sensor',
                            'value' => $sensor_id
                        );

                        ossim_valid($sensor_id, OSS_HEX, 'illegal:' . _('Sensor ID'));
                    }
                    else
                    {
                        $entity_id = str_replace('e_filter:', '', $filter);

                        $filter = array(
                            'type'  => 'entity',
                            'value' => $entity_id
                        );

                        ossim_valid($entity_id,  OSS_HEX, 'illegal:' . _('Entity ID'));
                    }
                }

                $max_nodes = (preg_match('/^\d+$/', $found[2])) ? $found[2] : MAX_RESULTS;

                if (ossim_error())
                {
                    die(ossim_error());
                }

                $this->toggle_hostgroup($hostgroup_id, $filter, $max_nodes);
            }
            elseif ($key == "netgroup") { //All netgroups
                $this->draw_netgroups();
            }
            elseif (preg_match("/^e_.+_netgroup$/", $key)) { //Netgroups by entity
                $this->draw_netgroups($entity_id);
            }
            elseif (preg_match("/^netgroup_(.*)/",$key, $found)) // Toggle a netgroup
            {
                $found       = explode(';', $found[1]);
                $netgroup_id = $found[0];
                $entity_id   = $found[1];

                ossim_valid($netgroup_id,   OSS_HEX,                'illegal:' . _("Netgroup ID"));
                ossim_valid($entity_id,     OSS_HEX, OSS_NULLABLE,  'illegal:' . _("Entity ID"));

                if (ossim_error()) {
                    die(ossim_error());
                }

                $this->toggle_netgroup($netgroup_id, $entity_id);
            }
            elseif (preg_match("/^ae_(.*)/", $key, $found)) { // Entity assets
                $entity_id = $found[1];
                ossim_valid($entity_id, OSS_HEX, 'illegal:' . _("Entity ID"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                $this->draw_assets($entity_id);
            }
            elseif(preg_match("/^ue_(.+)/",$key, $found)) // Entity users
            {
                $entity_id = $found[1];
                ossim_valid($entity_id, OSS_HEX, 'illegal:' . _("Entity ID"));
                if (ossim_error()) {
                    die(ossim_error());
                }

                $this->draw_users($entity_id);
            }
            elseif($key == "ou"){ // Other Users
                $this->draw_users();
            }
            elseif($key == 'all_users'){ //All users
                $this->draw_all_users();
            }
            else if(preg_match("/servers/",$key))
            {
                $this->draw_servers();
            }
            else if(preg_match("/databases/",$key))
            {
                $this->draw_databases();
            }
            elseif ($key == "sensors")
            {
                // Sensors
                $this->draw_sensors();
            }
            elseif (preg_match("/^sensor_(.*)/", $key, $found))  // Toggle Sensor Assets
            {
                $sensor_id = $found[1];
                ossim_valid($sensor_id, OSS_HEX, 'illegal:' . _("Sensor ID"));

                if (ossim_error())
                {
                    die(ossim_error());
                }

                $this->draw_assets('', $sensor_id);
            }
            elseif (preg_match("/^se_(.*)/", $key, $found))
            {
                $entity_id = $found[1];
                ossim_valid($entity_id, OSS_HEX, '\,', 'illegal:' . _("Entity ID"));

                if (ossim_error())
                {
                    die(ossim_error());
                }

                $this->draw_sensors($entity_id);
            }
            elseif ($key == "ports")
            {
                //Ports
                $this->draw_portgroups();
            }
            elseif (preg_match("/asec_pg(_[df]_)?(.*)/", $key, $found))
            {
                //  //Asec - Plugins generated
                $this->draw_asec_plugins($found[2]);
            }
            elseif ($key == "any")
            {
                // Any
                $this->draw_any_option();
            }
            elseif ($key == "home") {
		$this->draw_home_options();
            }
            $flag = TRUE;
        }

        echo "]";
    }


    public static function compare_ip($a, $b)
    {
        $ip_a = preg_replace("/;.*/", '', $a);
        $ip_b = preg_replace("/;.*/", '', $b);

        return (ip2long($ip_a) > ip2long($ip_b));
    }


    public function get_cclass_host_where($conn, $entity_id = '', $property_key = '', $entity_direction = "down", $cclass = '')
    {
        $_tables          = array();
        $_conds           = array();
        $entity_direction = (empty($entity_direction)) ? "down" : $entity_direction;

        if($cclass != '')
        {
            list($_cclass_a, $_cclass_b) = Util::expand_cclass($cclass);

            //Acotando a la clase seleccionada.
            $_cclass_a = bin2hex(inet_pton(trim($_cclass_a)));
            $_cclass_b = bin2hex(inet_pton(trim($_cclass_b)));

            $_conds[]           = " hi.ip >= UNHEX('$_cclass_a') and hi.ip <= UNHEX('$_cclass_b') ";
        }

        if(!empty($entity_id) && Session::is_pro())
        {
            if (preg_match("/\,/", $entity_id))
            {
                $cond_array    = array();
                $_entities_list = explode(',', $entity_id);

                foreach($_entities_list as $_entity)
                {
                    if(Acl::is_logical_entity($conn, $_entity))
                    {
                        $cond_array[] = Acl::get_host_entity_perms($conn, $_entity);
                    }
                    else
                    {

                        if(($entity_direction == "up"))
                        {
                            $_contexts = Acl::get_entity_parents($conn, $_entity, FALSE, 'context');
                        }
                        else
                        {
                            $_contexts = Acl::get_entity_childs($conn, $_entity, 'context', FALSE);
                        }

                        $_contexts[] = $_entity;
                        $_contexts   = array_unique($_contexts);


                        $cond_aux = array();
                        foreach($_contexts as $c)
                        {
                            if(empty($c)) continue;

                            $cond_aux[] = "host.ctx = UNHEX('$c')";
                        }

                        if(count($cond_aux) > 0)
                        {
                             $cond_array[] = ' (' . implode(' OR ', $cond_aux). ')';
                        }
                    }
                }

                if(!empty($cond_array))
                {
                    $_conds[]  = ' (' . implode(' OR ', $cond_array) . ') ';
                }
            }
            else // Unique entity ID
            {
                if(Acl::is_logical_entity($conn, $entity_id))
                {
                    $_conds[]  = Acl::get_host_entity_perms($conn, $entity_id);
                }
                else
                {
                    if(($entity_direction == "up"))
                    {
                        $_contexts = Acl::get_entity_parents($conn, $entity_id, FALSE, 'context');
                    }
                    else
                    {
                        $_contexts = Acl::get_entity_childs($conn, $entity_id, 'context', FALSE);
                    }

                    $_contexts[] = $entity_id;
                    $_contexts   = array_unique($_contexts);


                    $cond_aux = array();
                    foreach($_contexts as $c)
                    {
                        if(empty($c)) continue;

                        $cond_aux[] = "host.ctx=UNHEX('$c')";
                    }

                    $_conds[] = ' (' . implode(' OR ', $cond_aux). ')';
                }

            }

        }

        //In case of properties tree, we look for host with properties instead of go through all the host.
        $property_id  = preg_replace("/\:.*/", '', $property_key);

        if($property_id != '')
        {
            switch($property_id)
            {
                case 50:    //Host MAC
                    $_conds[]           = " host.id = hi.host_id AND hi.mac IS NOT NULL  ";

                    break;

                case 40:    //Host Services

                    $_tables['host_services']   = 'host_services hs';
                    $_conds[]                   = " host.id = hs.host_id ";

                    break;

                case 60:    //Host Software
                    $_tables['host_software']   = 'host_software hsf';
                    $_conds[]                   = " host.id = hsf.host_id ";
                    break;

                case 0: //All

                    break;

                default:    //Host Properties
                    $_tables['host_properties'] = 'host_properties hp';
                    $_conds[]                   = " host.id = hp.host_id ";
            }
        }


        //Extra options: Hack to only show assets not linked to HIDS agents
        if($this->extra_options == 'only_unlinked_to_hids_agents')
        {
            $_conds[] = "NOT exists (select 1 FROM hids_agents ha WHERE ha.host_id = host.id)";
        }


        $_tables = (count($_tables) > 0) ? (', ' . implode(', ', $_tables)) : '';

        $_conds  = implode(' AND ', $_conds);

        //$where   = $_tables . ' WHERE ' . $_conds;

        return array($_tables, $_conds);

    }


    /* Get a list with cclass hosts
     * Parameters:
     * - conn: database conection
     * - entity_id: ctx filter, will return all entity hosts and child entities hosts
     * - sensor_id: sensor filter
     * - property_id: property filter
     * - filter: for searching by hostname or IP address
     * - entity_direction: get the hosts up or down from the entities tree
     * Return:
     * - $all_cclass_hosts['192.168.10'] = array(host_object_1, host_object_2...)
     */
    public function get_cclass_hosts($conn, $entity_id = '', $sensor_id = '', $property_key = '', $filter = '', $entity_direction = "down")
    {
        $entity_direction     = (empty($entity_direction)) ? "down" : $entity_direction;
        $aux_all_cclass_hosts = array();
        $all_cclass_hosts     = array();
        $cclass_keys          = array();

        list($tables, $where) = $this->get_cclass_host_where($conn, $entity_id, $property_key, $entity_direction);

        $host_list            = Asset_host::get_list_tree($conn, $tables, array('where' => $where), TRUE, TRUE);

        if (count($host_list) > 0)
        {
            foreach($host_list as $host)
            {
                $host_id   = $host[0];
                $host_ip   = $host[2];
                $host_name = $host[3];

                // Entity and search filter
                if ($filter == '' || ($filter != '' && (preg_match("/$filter/i", $host_ip || preg_match("/$filter/i", $host_name)))))
                {
                    // Sensor filter
                    if ($sensor_id != '')
                    {
                        $sensor_filtered = TRUE;
                        $sensors = Asset_host_sensors::get_sensors_by_id($conn, $host_id);
                        foreach ($sensors as $_sensor_id => $sensor_reference)
                        {
                            if ($sensor_id == $_sensor_id)
                            {
                                $sensor_filtered = FALSE;
                            }
                        }

                        if ($sensor_filtered)
                        {
                            continue;
                        }
                    }


                    /***********************************************
                    *
                    * Deprecated filter: See av_tree.inc
                    *
                    ***********************************************/
                    if ($property_key != '' && $property_key != "0:")
                    {
                        continue;
                    }

                    $cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", trim($host_ip));

                    $cclass_keys[$cclass] = $cclass;
                    $aux_all_cclass_hosts[$cclass][$host_id] = $host;
                }
            }

            natsort($cclass_keys);

            foreach($cclass_keys as $cckey)
            {
                $all_cclass_hosts[$cckey] = $aux_all_cclass_hosts[$cckey];
            }

            unset($aux_all_cclass_hosts);
            unset($cclass_keys);
        }

        return $all_cclass_hosts;
    }


    public function toggle_cclass($conn, $cclass, $entity_id = '', $sensor_id = '', $property_key = '', $filter = '', $entity_direction = "down")
    {
        $toggled_host        = array();
        $entity_direction    = (empty($entity_direction)) ? "down" : $entity_direction;

        list($tables, $where) = $this->get_cclass_host_where($conn, $entity_id, $property_key, $entity_direction, $cclass);
        $host_list            = Asset_host::get_list_tree($conn, $tables, array('where' => $where), FALSE, TRUE);

        if (count($host_list) > 0)
        {
            foreach($host_list as $host)
            {
                $host_id   = $host[0];
                $host_ip   = $host[2];
                $host_name = $host[3];

                // Entity and search filter
                if ($filter == '' || ($filter != '' && (preg_match("/$filter/i", $host_ip) || preg_match("/$filter/i", $host_name))))
                {
                    // Sensor filter
                    if ($sensor_id != '')
                    {
                        $sensor_filtered = TRUE;
                        $sensors         = Asset_host_sensors::get_sensors_by_id($conn, $host_id);

                        foreach ($sensors as $_sensor_id => $sensor_reference)
                        {
                            if ($sensor_id == $_sensor_id)
                            {
                                $sensor_filtered = FALSE;
                            }
                        }

                        if ($sensor_filtered)
                        {
                            continue;
                        }
                    }


                    /***********************************************
                    *
                    * Deprecated filter: See av_tree.inc
                    *
                    ***********************************************/
                    if ($property_key != '' && $property_key != "0:")
                    {
                        continue;
                    }

                    $h_key                = $host_ip . ";" . $host_id;
                    $toggled_host[$h_key] = $host;
                }
            }

            uksort($toggled_host, "Tree::compare_ip");
        }

        return $toggled_host;
    }


    /**
    * Draw a host tree list (usually toggled from a cclass host)
    *   Parameters:
    *       - conn: database conection
    *       - entity_id: ctx filter, will return all entity hosts and child entities hosts
    *       - filter: for searching by hostname or IP address, CCLASS filter must be here
    *       - entity_direction: get the hosts up or down from the entities tree
    * @return void
    */
    public function draw_hosts($entity_id = '', $sensor_id = '', $property_key = '', $filter = '', $entity_direction = '')
    {
        $conn = $this->conn;
        $page = $this->page;
        $prefilter = '';

        // Check CCLASS filter
        $cclass_filter = (preg_match("/^\d+\.\d+\.\d+$/", $filter)) ? $filter : '';

        if ($property_key != '')
        {
            $prefilter = 'p_'.$property_key.'_';
        }

        if ($sensor_id != '')
        {
            $prefilter = 's_'.$sensor_id.'_';
        }

        if ($entity_id != '' && Session::is_pro())
        {
            // Get all entities for specify a children host
            list($entities, $children, $num_rows) = Acl::get_entities($conn, '', '', TRUE, FALSE);
            $prefilter = 'e_'.$entity_id.'_';
        }


        $html     = '';
        $j        = 0;
        $i        = 0;
        $h        = '680';
        $to       = $page * MAX_RESULTS;
        $from     = $to - MAX_RESULTS;
        $nextpage = $page + 1;


        if ($filter == '') // Show hosts grouped by CCLASS
        {
            $all_cclass_hosts = $this->get_cclass_hosts($conn, $entity_id, $sensor_id, $property_key, $filter, $entity_direction);

            foreach($all_cclass_hosts as $cclass => $hg)
            {
                if ($j>=$from && $j<$to)
                {
                    $title   = $cclass." <span style='font-weight:normal;font-size:80%'>(".count($hg)." "._("hosts").")</span>";
                    $li = '"key" :"'.$prefilter.'host'.$entity_direction.'_'.$cclass.'", "isLazy" : true, "isFolder" : true, "icon" :"../../pixmaps/theme/host_add.png", "title" : "'.$title.'"'."\n";
                    $buffer .= (($j > $from) ? "," : '') . "{ $li }\n";
                }
                $j++;
            }

            if ($j>$to)
            {
                $li       = '"key" : "'.$prefilter.'host'.$entity_direction.'", "page" :"'.$nextpage.'", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host_add.png", "title" : "'._("next").' '.MAX_RESULTS.' c-class"';
                $buffer  .= ",{ $li }\n";
            }


        }
        else  // Toggle a host CCLASS
        {
            $all_toggled_hosts = $this->toggle_cclass($conn, $cclass_filter, $entity_id, $sensor_id, $property_key, $filter, $entity_direction);

            foreach($all_toggled_hosts as $ip_id => $host)
            {
                if ($i>=$from && $i<$to)
                {
                    $host_name = $host[3];
                    $ip        = preg_replace("/;.*/", '', $ip_id);
                    $id        = preg_replace("/.*;/", '', $ip_id);
                    $ctx_name  = (Session::is_pro()) ? Acl::get_entity_name($conn, $host[1], TRUE) : '';

                    $aux_hname = (strlen($host_name) > $this->length_name) ? substr($host_name, 0, $this->length_name)."..." : $host_name;

                    $title     = ($host_name == '') ? $ip : "$ip <span style='font-size:80%'>(".Util::utf8_encode2($aux_hname).")</span>";

                    if ($entity_id != '' && $host[1] != $entity_id)
                    {
                        $title .= " <span style='font-size:80%;font-weight:bold'>[".$entities[$host[1]]['name']."]</span>";
                    }

                    $tooltip    = ($host_name == '') ? $ip : $ip." (".Util::utf8_encode2($host_name).")";

                    $_url       = "/ossim/av_asset/common/views/detail.php?asset_id=$id";
                    $url_link   = Menu::get_menu_url($_url,"environment", "assets", "assets");


                    $html      .= '{ "key" : "host_'.$id.'", "val" : "'.$host_name.' ('.$ip.')", "h" : "'.$h.'", "ctx" : "'.$host[1].'", "ctx_name" : "'.$ctx_name.'", "url" : "'. $url_link .'", "icon" : "../../pixmaps/theme/host.png", "title" : "'. $title.'", "tooltip" : "'.$tooltip.'" },'."\n";
                }
                $i++;
            }


            if ($i>$to)
            {
                $html .= '{ "key" : "'.$prefilter.'host'.$entity_direction.'_'.$filter.'", "page" :"'.$nextpage.'", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host_group.png", "title" : "'._("Next").' '.MAX_RESULTS.' '._("hosts").'"} ';
            }

            if ($html != '')
            {
                $buffer .= preg_replace("/,$/", '', $html);
            }
        }


        if ($buffer == '' || $buffer == "[]")
        {
            $buffer = '{"title" : "'._("No Hosts Found").'", "noLink" : true, "key" : ""}';
        }

        echo $buffer;
    }


    public static function compare_c_cidr($a, $b)
    {
        $ip_a = $a . '.0';
        $ip_b = $b . '.0';

        return (ip2long($ip_a) > ip2long($ip_b));
    }


    public static function compare_b_cidr($a, $b)
    {
        $ip_a = $a . '.0.0';
        $ip_b = $b . '.0.0';

        return (ip2long($ip_a) > ip2long($ip_b));
    }


    public static function compare_a_cidr($a, $b)
    {
        $ip_a = $a . '.0.0.0';
        $ip_b = $b . '.0.0.0';

        return (ip2long($ip_a) > ip2long($ip_b));
    }


    /* Get a list with cclass networks
     * Parameters:
     * - conn: database conection
     * - entity_id: ctx filter, will return all entity nets and child entities nets
     * - sensor_id: sensor filter
     * - filter: for searching by net name or CIDR address
     * - entity_direction: get the nets up or down from the entities tree
     * Return:
     * - $all_cclass_nets['192.168.10._/_'] = array(net_object_1, net_object_2...)
     */
    public static function get_xclass_nets($conn, $entity_id = '', $sensor_id = '', $filter = '', $entity_direction = "down")
    {
        $all_cclass_nets = array();
        $cclasses        = array();
        $bclasses        = array();
        $aclasses        = array();
        $already         = array();
        $where           = '';
        $_conds          = array();

        if ($entity_direction == '')
        {
            $entity_direction = "down";
        }

        // We must consider if $filter is a number, filter must begin by this number
        if (preg_match("/^\d+/", $filter))
        {
            $filter_class = '^'. quotemeta($filter."."); // filter class begins by this ip
            $filter_name  = "^\s";                      // always false condition
        }
        else
        {
            $filter_name  = quotemeta($filter);
            $filter_class = $filter_name;
        }

        if (!empty($entity_id)  && Session::is_pro())
        {
            if (preg_match("/\,/", $entity_id))
            {
                $cond_array    = array();
                $_entities_list = explode(',', $entity_id);

                foreach($_entities_list as $_entity)
                {
                    if(Acl::is_logical_entity($conn, $_entity))
                    {
                        $cond_array[] = Acl::get_net_entity_perms($conn, $_entity, 'net');
                    }
                    else
                    {

                        if(($entity_direction == "up"))
                        {
                            $_contexts = Acl::get_entity_parents($conn, $_entity, FALSE, 'context');
                        }
                        else
                        {
                            $_contexts = Acl::get_entity_childs($conn, $_entity, 'context', FALSE);
                        }

                        $_contexts[] = $_entity;
                        $_contexts   = array_unique($_contexts);


                        $cond_aux = array();
                        foreach($_contexts as $c)
                        {
                            if(empty($c)) continue;

                            $cond_aux[] = "net.ctx = UNHEX('$c')";
                        }

                        if(count($cond_aux) > 0)
                        {
                             $cond_array[] = ' (' . implode(' OR ', $cond_aux). ')';
                        }
                    }
                }

                if(!empty($cond_array))
                {
                    $_conds[]  = ' (' . implode(' OR ', $cond_array) . ') ';
                }
            }
            else // Unique entity ID
            {
                if(Acl::is_logical_entity($conn, $entity_id))
                {
                    $_conds[]  = Acl::get_net_entity_perms($conn, $entity_id, 'net');
                }
                else
                {
                    if(($entity_direction == "up"))
                    {
                        $_contexts = Acl::get_entity_parents($conn, $entity_id, FALSE, 'context');
                    }
                    else
                    {
                        $_contexts = Acl::get_entity_childs($conn, $entity_id, 'context', FALSE);
                    }

                    $_contexts[] = $entity_id;
                    $_contexts   = array_unique($_contexts);


                    $cond_aux = array();
                    foreach($_contexts as $c)
                    {
                        if(empty($c)) continue;

                        $cond_aux[] = "net.ctx = UNHEX('$c')";
                    }

                    $_conds[] = ' (' . implode(' OR ', $cond_aux). ')';
                }
            }
        }

        $where  = implode(' AND ', $_conds);

        $net_list = Asset_net::get_list_tree($conn, '', array('order_by' => 'name', 'where' => $where), TRUE);

        foreach ($net_list as $net)
        {
            $net_id   = $net[0];
            $cidrs    = $net[2];
            $net_name = $net[3];

            $acidrs   = explode(",", $cidrs);

            // Entity and search filter
            if ($filter != '')
            {
                $_flag  = TRUE;
                foreach($acidrs as $_cidr)
                {
                    if (preg_match("/$filter_name/i", $net_name) || preg_match("/$filter_class/i", $_cidr))
                    {
                        $_flag = FALSE;
                    }
                }

                if ($_flag)
                {
                    continue;
                }
            }

            // Sensor filter
            if ($sensor_id != '')
            {
                $sensor_filtered = TRUE;
                $sensors = Asset_net_sensors::get_sensors_by_id($conn, $net_id);

                foreach ($sensors as $_sensor_id => $sensor_reference)
                {
                    if ($sensor_id == $_sensor_id)
                    {
                        $sensor_filtered = FALSE;
                    }
                }

                if ($sensor_filtered) continue;

            }

            foreach($acidrs as $cidr)
            {
                $data = explode(".", $cidr);

                if ($filter != '')
                {
                    if (!$already[$net_id] && (preg_match("/$filter_class/i", $cidr) || preg_match("/$filter_name/i", $net_name)))
                    {
                        $already[$net_id]++;

                        $all_cclass_nets[$filter][] = $net;

                        $cclasses[$data[0].".".$data[1].".".$data[2]]++;
                        $bclasses[$data[0].".".$data[1]]++;
                        $aclasses[$data[0]]++;
                    }
                }
                else
                {
                    $all_cclass_nets[$data[0].".".$data[1].".".$data[2]][] = $net;

                    $cclasses[$data[0].".".$data[1].".".$data[2]]++;
                    $bclasses[$data[0].".".$data[1]]++;
                    $aclasses[$data[0]]++;
                }
            }
        }

        uksort($all_cclass_nets, "Tree::compare_c_cidr");
        uksort($cclasses, "Tree::compare_c_cidr");
        uksort($bclasses, "Tree::compare_b_cidr");
        uksort($aclasses, "Tree::compare_a_cidr");

        return array($all_cclass_nets, $cclasses, $bclasses, $aclasses);
    }



    /* Draw a tree of nets by the best class of grouping (aclass, bclass or cclass)
     * Parameters:
     * - conn: database conection
     * - entity_id: ctx filter, will return all entity nets and child entities nets
     * - filter: for searching by net name or CIDR address
     * - entity_direction: get the nets up or down from the entities tree
     * Return: void
     */
    public function draw_nets($entity_id = '', $sensor_id = '', $filter = '', $entity_direction = '')
    {
        $conn = $this->conn;

        $prefilter = '';

        if ($entity_id != '')
        {
            $prefilter = 'e_'.$entity_id.'_';
        }
        if ($sensor_id != '')
        {
            $prefilter = 's_'.$sensor_id.'_';
        }


        list($all_cclass_nets, $cclasses, $bclasses, $aclasses) = self::get_xclass_nets($conn, $entity_id, $sensor_id, $filter, $entity_direction);

        if (count($all_cclass_nets) > 0)
        {
            // Already toggled: draw by filter ACLASS, BCLASS, CCLASS
            if ($filter != '') {
                // aclass: toggle in bclass
                if (preg_match("/^\d+$/", $filter))
                {
                    foreach($bclasses as $bclass => $v)
                        $buffer .= '{ "key" :"'.$prefilter.'net'.$entity_direction.'_'.$bclass.'", "isFolder" : true, "isLazy" : true, "icon" :"../../pixmaps/theme/net.png", "title" : "'.$bclass.'.---.---./--"},';
                    $buffer = preg_replace("/,$/", '', $buffer);
                }
                // bclass: toggle in cclass
                elseif (preg_match("/^\d+\.\d+$/", $filter))
                {
                    foreach($cclasses as $cclass => $v)
                        $buffer .= '{ "key" :"'.$prefilter.'net'.$entity_direction.'_'.$cclass.'", "isFolder" : true, "isLazy" : true, "icon" :"../../pixmaps/theme/net.png", "title" : "'.$cclass.'.---/--"},';
                    $buffer = preg_replace("/,$/", '', $buffer);
                }
                // cclass: toggle in entire networks
                else
                {
                    foreach($all_cclass_nets[$filter] as $net)
                    {
                        $net_id   = $net[0];
                        $net_ctx  = $net[1];
                        $net_ips  = $net[2];
                        $net_name = $net[3];
                        $ctx_name = (Session::is_pro()) ? Acl::get_entity_name($conn, $net_ctx, TRUE) : '';


                        $net_title = Util::utf8_encode2($net_name);

                        $net_key   = "net_".$net_id;

                        $ips       = "<span style='font-size:80%'>(".$net_ips.")</span>";

                        $title     = (strlen($net_name) > $this->length_name) ? substr($net_name, 0, $this->length_name)."..." : $net_name;
                        $title     = Util::utf8_encode2($title)." ".$ips;

                        if ($entity_id != '' && $net_ctx != $entity_id) {
                            $title .= " <span style='font-size:80%;font-weight:bold'>[".$ctx_name."]</span>";
                        }

                        $tooltip   = $net_title;

                        if($urltype == 0)
                        {
                            $_url = "/ossim/av_asset/common/views/detail.php?asset_id=$net_id";
                            $url  = Menu::get_menu_url($_url,"environment", "assets", "networks");
                        }
                        else if ($urltype == 1)
                        {
                            $url = $ips;
                        }
                        else if ($urltype == 2)
                        {
                            $url = "NET:".Util::utf8_encode2($net_name);
                        }

                        // Do not toggle if we are in entities
                        if ($entity_id != '')
                        {
                            $buffer .= '{ "key" : "'.$net_key.'", "val" : "'.$net_name.' ('.$net_ips.')", "h" :"'.$h.'", "ctx" : "'.$net_ctx.'", "ctx_name" : "'.$ctx_name.'", "url" : "'.$url.'", "icon" : "../../pixmaps/theme/net.png", "title" : "'.$title.'", "tooltip" : "'.$tooltip.'" },';
                        }
                        else
                        {
                            $buffer .= '{ "key" : "'.$net_key.'", "val" : "'.$net_name.' ('.$net_ips.')", "isLazy" : true, "h" :"'.$h.'", "ctx" : "'.$net_ctx.'", "ctx_name" : "'.$ctx_name.'", "url" : "'.$url.'", "icon" : "../../pixmaps/theme/net.png", "title" : "'.$title.'", "tooltip" : "'.$tooltip.'" },';
                        }
                    }

                    $buffer = preg_replace("/,$/", '', $buffer);
                }

            // Filter is empty, draw CLASS by the best usability about number of networks
            }
            else
            {
                if (count($bclasses) <= LOW_LIMIT)
                {
                    foreach($cclasses as $cclass => $v) {
                        $buffer .= '{ "key" : "'.$prefilter.'net'.$entity_direction.'_'.$cclass.'", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net.png", "title" : "'.$cclass.'.---/--"},';
                    }
                }
                else if (count($bclasses) > LOW_LIMIT && count($bclasses) <= HIGH_LIMIT)
                {
                    foreach($bclasses as $bclass => $v)
                        $buffer .= '{ "key" :"'.$prefilter.'net'.$entity_direction.'_'.$bclass.'", "isFolder" : true, "isLazy" : true, "icon" :"../../pixmaps/theme/net.png", "title" : "'.$bclass.'.---.---./--"},';
                }
                else
                {
                    foreach ($aclasses as $aclass => $v)
                        $buffer .= '{ "key" :"'.$prefilter.'net'.$entity_direction.'_'.$aclass.'", "isFolder" : true, "isLazy" : true, "icon" :"../../pixmaps/theme/net.png", "title" : "'.$aclass.'.---.---.---/--"},';
                }

                $buffer = preg_replace("/,$/", '', $buffer);
            }
        }

        if ($buffer == '' || $buffer == "[]")
            $buffer = '{"title" : "'._("No Networks found").'", "noLink" : true, "key" : "" }';

        echo $buffer;
    }

    /* Draw a tree with hosts belongs to a network
     * Parameters:
     * - net_id: Network ID
     */
    public function toggle_net($net_id)
    {
        $conn = $this->conn;
        $page = $this->page;

        $to         = $page * MAX_RESULTS;
        $from       = $to - MAX_RESULTS;
        $nextpage   = $page + 1;

        $hostin = array();

        $q_filters = array(
            'where' => "hnr.host_id = host.id AND hnr.net_id=UNHEX('$net_id')"
        );


        //Extra options: Hack to only show assets not linked to HIDS agents
        if($this->extra_options == 'only_unlinked_to_hids_agents')
        {
            $q_filters['where'] .= " AND NOT exists (select 1 FROM hids_agents ha WHERE ha.host_id = hnr.host_id)";
        }


        $hostin = Asset_host::get_list_tree($conn, ', host_net_reference hnr', $q_filters, TRUE);

        foreach($hostin as $host)
        {
            if ($k>=$from && $k<$to)
            {
                $id         = $host[0];
                $ip         = $host[2];
                $host_name  = $host[3];

                $hname      = ($host_name == $ip) ? '' : $host_name;

                $aux_hname  = (strlen($hname) > $this->length_name) ? substr($hname, 0, $this->length_name)."..." : $hname;

                $title      = ($hname == '') ? $ip : "$ip <span style='font-size:80%'>(".Util::utf8_encode2($aux_hname).")</span>";
                $tooltip    = ($hname == '') ? $ip : $ip." (".Util::utf8_encode2($host_name).")";

                $h          = '580';

                $_url       = "/ossim/av_asset/common/views/detail.php?asset_id=$id";
                $url_link   = Menu::get_menu_url($_url,"environment", "assets", "assets");

                $html      .= '{ "key" : "host_'.$id.'", "val" : "'.$host_name.' ('.$ip.')", "h" : "'.$h.'", "url" : "'. $url_link .'", "icon" : "../../pixmaps/theme/host.png", "title" : "'. $title.'", "tooltip" : "'.$tooltip.'" },'."\n";
            }
            $k++;
        }

        if ($html != '')
            $buffer .= preg_replace("/,$/", '', $html);

        if ($k>$to)
        {
            $li      = '"key" : "net_'.$net_id.'", "page" : "'.$nextpage.'", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host.png", "title" : "'._("Next").' '.MAX_RESULTS.' '._("Hosts").'"';
            $buffer .= ",{ $li }\n";
        }

        if ($buffer == '' || $buffer == "[]")
            $buffer = '{"title" : "'._("No Hosts Found").'", "noLink" : true}';

        echo $buffer;
    }


    /**
     * This function draws a tree with host groups by filter criteria
     *
     * @param array  $filter  [Optional] Filter criteria
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public function draw_hostgroups($filter = array())
    {
        $conn = $this->conn;
        $page = $this->page;

        $to         = $page * MAX_RESULTS;
        $from       = $to - MAX_RESULTS;
        $nextpage   = $page + 1;


        $prefilter = '';
        $tables    = '';
        $q_where   = '';


        if (!empty($filter))
        {
            switch ($filter['type'])
            {
                case 'entity':

                    $entity_id = $filter['value'];
                    $prefilter = 'e_'.$entity_id.'_';
                    $q_tables  = '';

                    if(Session::is_pro())
                    {
                        // Entity list
                        if (preg_match("/\,/", $entity_id))
                        {
                            $cond_array = array();

                            $_entities_list = explode(',', $entity_id);

                            foreach($_entities_list as $_entity)
                            {
                                if(Acl::is_logical_entity($conn, $_entity))
                                {
                                    $cond_array[] = Acl::get_host_entity_perms($conn, $_entity, 'h');
                                }
                                else
                                {
                                    $cond_array[] = ($_entity != '') ? " h.ctx = UNHEX('$_entity') " : '';
                                }
                            }

                            if(!empty($cond_array))
                            {
                                $q_tables = ', host h, host_group_reference hr';
                                $q_where  = '(' . implode(' OR ', $cond_array) . ') ';
                            }
                        }
                        else // Unique entity ID
                        {
                            if(Acl::is_logical_entity($conn, $entity_id))
                            {
                                $q_where = Acl::get_host_entity_perms($conn, $entity_id, 'h');
                            }
                            else
                            {
                                $q_where = ($entity_id != '') ? "h.ctx = UNHEX('$entity_id')" : '';
                            }

                            $q_tables  = ', host h, host_group_reference hr';

                            $_log_key  = ";e_filter:$entity_id";
                        }
                    }

                break;

                case 'sensor':
                    $sensor_id = $filter['value'];
                    $prefilter = 's_'.$sensor_id.'_';
                    $q_tables  = ', host h, host_group_reference hr, host_sensor_reference hsr';
                    $q_where   = 'h.id = hsr.host_id AND hsr.sensor_id = UNHEX("'.$sensor_id.'")';
                    $_log_key  = ";s_filter:$sensor_id";
                break;
            }
        }


        if (!empty($q_where))
        {
            $tables   = $q_tables;
            $q_where .= ' AND h.id = hr.host_id AND g.id = hr.host_group_id';


            //Extra options: Hack to only show assets not linked to HIDS agents
            if($this->extra_options == 'only_unlinked_to_hids_agents')
            {
                $q_where .= " AND NOT exists (select 1 FROM hids_agents ha WHERE ha.host_id = h.id)";
            }
        }

        $q_filters = array(
            'where'    => $q_where,
            'order_by' => 'name'
        );

        list($hg_list, $hg_total) = Asset_group::get_list($conn, $tables, $q_filters, TRUE);

        if ($hg_total > 0)
        {
            $j = 0;
            $h = '550';

            foreach($hg_list as $hg_id => $hg)
            {
                if($j >= $from && $j < $to)
                {
                    $hg_name  = $hg->get_name();
                    $hg_key   = "hostgroup_".$hg_id.$_log_key;
                    $hg_title = Util::utf8_encode2($hg_name);

                    $title    = (strlen($hg_name) > $this->length_name) ? substr($hg_name, 0, $this->length_name)."..." : $hg_name;
                    $title    = Util::utf8_encode2($title);
                    $tooltip  = $hg_title;

                    $h        = '585';

                    $_url     = "/ossim/av_asset/common/views/detail.php?asset_id=". $hg_id;
                    $url_link = Menu::get_menu_url($_url,'environment', 'assets', 'asset_groups');

                    $li       = '"key" : "'.$hg_key.'", "val" : "'.Util::js_entities($hg_name).'", "isLazy" : true, "h" : "'.$h.'", "url" : "'. $url_link .'",
                        "icon" : "../../pixmaps/theme/host_group.png", "title" : "'.$title.'", "tooltip" : "'.$tooltip.'"'."\n";

                    $buffer .= (($j > $from) ? "," : '') . "{ $li }\n";
                }

                $j++;
            }

            if ($j > $to)
            {
                $li      = '"key" : "'.$prefilter.'hostgroup", "page" :"'.$nextpage.'", "isFolder" : true, "isLazy" : true,
                    "icon" : "../../pixmaps/theme/host_group.png", "title" : "'._("Next").' '.MAX_RESULTS.' '._("Host groups").'"';

                $buffer .= ",{ $li }\n";
            }
        }

        if ($buffer == '' || $buffer == "[]")
        {
            $buffer = '{"title" : "'._('No Asset Groups found').'", "noLink" : true, "key" : ""}';
        }

        echo $buffer;
    }


    /**
      * This function draws a tree with hosts belongs to a group by filter criteria
      *
      * @param  string    $hostgroup_id  Host Group ID
      * @param  array     $filter        [Optional] Filter configuration
      * @param  integer   $max           [Optional] Max number of nodes
      *
      * @throws Exception  If an error occurred
      *
      * @return string
      */
    public function toggle_hostgroup($hostgroup_id, $filter = array(), $max = MAX_RESULTS)
    {
        $conn = $this->conn;
        $page = $this->page;

        $to         = $page * $max;
        $from       = $to - $max;
        $nextpage   = $page + 1;


        $tables    = '';
        $q_where   = '';

        if (!empty($filter))
        {
            switch ($filter['type'])
            {
                case 'entity':
                    $entity_id = $filter['value'];
                    $q_tables  = '';

                    if(Session::is_pro())
                    {
                        if(Acl::is_logical_entity($conn, $entity_id))
                        {
                            $q_where = Acl::get_host_entity_perms($conn, $entity_id, 'h');
                        }
                        else
                        {
                            $q_where = " host.ctx = UNHEX('$entity_id')";
                        }

                        $q_where = (empty($q_where)) ? '' : "$q_where AND ";
                    }

                break;

                case 'sensor':
                    $sensor_id = $filter['value'];
                    $q_tables  = ', host_sensor_reference hsr';

                    $q_where   = 'host.id = hsr.host_id AND hsr.sensor_id = UNHEX("'.$sensor_id.'") AND ';

                break;
            }
        }


        $tables   = $q_tables;
        $q_where  = "$q_where host.id IN (SELECT host_id from host_group_reference where host_group_id = UNHEX('$hostgroup_id')) ";


        //Extra options: Hack to only show assets not linked to HIDS agents
        if($this->extra_options == 'only_unlinked_to_hids_agents')
        {
            $q_where .= " AND NOT exists (select 1 FROM hids_agents ha WHERE ha.host_id = host.id)";
        }


        $q_filters = array(
            'where' => $q_where,
        );


        list ($hg_hosts, $hg_total) = Asset_host::get_list($conn, $tables, $q_filters, TRUE);

        if ($hg_total > 0)
        {
            $k         = 0;
            $html      = '';
            $h         = '585';

            foreach($hg_hosts as $id => $host)
            {
                if ($k >= $from && $k < $to)
                {
                    $ip         = $host['ips'];
                    $host_name  = $host['name'];

                    $hname      = ($host_name != '') ? $host_name : '';

                    $aux_hname  = (strlen($hname) > $this->length_name) ? substr($hname, 0, $this->length_name)."..." : $hname;

                    $title      = ($host_name == '') ? $ip : "$ip <span style='font-size:80%'>(".Util::utf8_encode2($aux_hname).")</span>";
                    $tooltip    = ($host_name == '') ? $ip : $ip." (".Util::utf8_encode2($hname).")";

                    $_url       = "/ossim/av_asset/common/views/detail.php?asset_id=$id";
                    $url_link   = Menu::get_menu_url($_url,"environment", "assets", "assets");

                    $html   .= '{ "key" : "host_'.$id.'", "val" : "'.$host_name.' ('.$ip.')", "h" : "'.$h.'", "url" : "'. $url_link .'",
                        "icon" : "../../pixmaps/theme/host.png", "title" : "'. $title.'", "tooltip" : "'.$tooltip.'" },'."\n";
                }

                $k++;
            }

            if ($html != '')
            {
                $buffer .= preg_replace("/,$/", '', $html);
            }

            if ($k > $to)
            {
                $li      = '"key" : "hostgroup_'.$hostgroup_id.'", "page" :"'.$nextpage.'", "isFolder" : true, "isLazy" : true,
                    "icon" : "../../pixmaps/theme/host_group.png", "title" : "'._("Next").' '.$max.' '._("Hosts").'"';

                $buffer .= ",{ $li }\n";
            }
        }

        if ($buffer == '' || $buffer == "[]")
        {
            $buffer = '{"title" : "'._('No Hosts Found').'", "noLink" : true}';
        }

        echo $buffer;
    }


    /* Draw a tree with network groups
     * Parameters:
     * - entity_id: ctx filter, will return all entity assets
     */
    public function draw_netgroups($entity_id = '')
    {
        $conn = $this->conn;
        $page = $this->page;

        $to         = $page * MAX_RESULTS;
        $from       = $to - MAX_RESULTS;
        $nextpage   = $page + 1;
        $prefilter  = '';

        if ($entity_id != '')
        {
            $prefilter = "e_".$entity_id."_";
        }

        if(Session::is_pro())
        {
            // Entity list
            if (preg_match("/\,/", $entity_id))
            {
                $cond_array    = array();
                $_entities_list = explode(',', $entity_id);

                foreach($_entities_list as $_entity)
                {
                    if(Acl::is_logical_entity($conn, $_entity))
                    {
                        $cond_array[] = Acl::get_net_entity_perms($conn, $_entity);
                    }
                    else
                    {
                        $cond_array[] = ($_entity != '') ? " n.ctx=UNHEX('$_entity') " : '';
                    }
                }

                if(!empty($cond_array))
                {
                    $condition = ' (' . implode(' OR ', $cond_array) . ') ';
                }
            // Unique entity ID
            }
            else
            {
                if(Acl::is_logical_entity($conn, $entity_id))
                {
                    $condition = Acl::get_net_entity_perms($conn, $entity_id);

                }
                else
                {
                    $condition = ($entity_id != '') ? "n.ctx=UNHEX('$entity_id')" : '';
                }

                $_log_key  = ';' . $entity_id;
            }
        }

        $net_group_list = Net_group::get_list($conn, $condition, "ORDER BY name", TRUE);

        if (count($net_group_list) > 0)
        {
            $j = 0;
            $h = '550';

            foreach($net_group_list as $net_group)
            {
                if ($j>=$from && $j<$to)
                {
                    $ng_name  = $net_group->get_name();
                    $ng_key   = "netgroup_" . $net_group->get_id() . $_log_key;
                    $ng_title = Util::utf8_encode2($ng_name);

                    $title    = (strlen($ng_name) > $this->length_name) ? substr($ng_name, 0, $this->length_name)."..." : $ng_name;
                    $title    = Util::utf8_encode2($title);
                    $tooltip  = $ng_title;

                    $_url     = "/ossim/netgroup/netgroup_form.php?id=". $net_group->get_id();
                    $url_link = Menu::get_menu_url($_url,"environment", "assets", "network_groups");

                    $li      = '"key" : "'.$ng_key.'", "val" : "'.$ng_name.'", "isLazy" : true, "h" : "'.$h.'", "url" : "'. $url_link .'", "icon" : "../../pixmaps/theme/host.png", "title" : "'. $title.'", "tooltip" : "'.$tooltip.'" '."\n";
                    $buffer .= (($j > $from) ? "," : '') . "{ $li }\n";
                }
                $j++;
            }

            if ($j>$to)
            {
                $li      = '"key" : "'.$prefilter.'netgroup", "page" :"'.$nextpage.'", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net_group.png", "title" : "'._("Next").' '.MAX_RESULTS.' '._("network groups").'"';
                $buffer .= ",{ $li }\n";
            }

        }

        if ($buffer == '' || $buffer == "[]")
        {
            $buffer = '{"title" : "'._("No Network Groups Found").'", "noLink" : true, "key" : ""}';
        }

        echo $buffer;
    }


    /* Draw a tree with networks belongs to a group
     * Parameters:
     * - netgroup_id: Network Group ID
     */
    public function toggle_netgroup($netgroup_id, $entity_id='')
    {
        $conn = $this->conn;
        $page = $this->page;

        $to         = $page * MAX_RESULTS;
        $from       = $to - MAX_RESULTS;
        $nextpage   = $page + 1;
        $html       = array();
        $buffer     = '';

        $k = 1;
        $j = 0;
        $h = '550';


        if($entity_id != '' && Session::is_pro())
        {

            if(Acl::is_logical_entity($conn, $entity_id))
            {
                $condition = Acl::get_net_entity_perms($conn, $entity_id, 'net');

            }
            else
            {
                $condition = "net.ctx=UNHEX('$entity_id')";
            }

            $condition = (empty($condition)) ? '' : "$condition AND ";
        }
        else
        {
            $condition = '';
        }

        $query = "$condition net.id IN (Select net_id from net_group_reference where net_group_id=UNHEX('$netgroup_id')) ";
        list($nets, $net_total) = Asset_net::get_list($conn, '', array('where' => $query), TRUE);

        //$nets = Net_group::get_networks($conn, $netgroup_id);

        foreach($nets as $net_obj) {
            $net_id    = $net_obj['id'];
            $net_name  = $net_obj['name'];
            $net_title = Util::utf8_encode2($net_name);

            $ips_data  = $net_obj['ips'];
            $ips       = "<span style='font-size:80%'>(".$ips_data.")</span>";

            $title     = (strlen($net_name) > $this->length_name) ? substr($net_name, 0, $this->length_name)."..." : $net_name;
            $title     = Util::utf8_encode2($title)." ".$ips;

            $tooltip   = $net_title." (".$ips_data.")";

            $_url       = "/ossim/av_asset/common/views/detail.php?asset_id=$net_id";
            $url_link   = Menu::get_menu_url($_url,"environment", "assets", "networks");

            if ($j>=$from && $j<$to)
            {
                $html[] = '{ "key" : "net_'.$net_id.'", "val" : "'.$net_name.' ('.$ips_data.')", "h" : "'.$h.'", "url" : "'. $url_link .'", "icon" : "../../pixmaps/theme/net.png", "title" : "'. $title.'", "tooltip" : "'.$tooltip.'" }'."\n";

                $k++;
            }
            $j++;
        }

        if (count($html) > 0)
        {
            $buffer = implode(', ', $html);
        }

        if ($j>$to)
        {
            $li      = '"key" : "netgroup_'.$netgroup_id.'", "page" :"'.$nextpage.'", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net.png", "title" : "'._("Next").' '.MAX_RESULTS.' '._("networks").'"';
            $buffer .= ",{ $li }\n";
        }

        if ($buffer == '' || $buffer == "[]")
            $buffer = '{"title" : "'._("No Networks Found").'", "noLink" : true}';

        echo $buffer;
    }


    /* Draw an entity tree element with its assets
     * Parameters:
     * - entity_id
     * - sensor_id
     */
    public function draw_assets_complete($entity_id = '', $sensor_id = '')
    {
        $conn = $this->conn;

        // Entity list
        if (preg_match("/\,/", $entity_id)) {
            $condition = "ctx IN (UNHEX('".str_replace(",", "'),UNHEX('", $entity_id)."'))";
        // Unique entity ID
        } else {
            $condition = ($entity_id != '') ? "ctx=UNHEX('$entity_id')" : '';
        }

        $all_cclass_hosts                = $this->get_cclass_hosts($conn, $entity_id);
        list($all_hostgroups, $hg_total) = Asset_group::get_list($conn, '', array("order_by" => "name", "where" => $condition), TRUE);
        list($all_cclass_nets, $cclasses, $bclasses, $aclasses) = self::get_xclass_nets($conn, $entity_id);
        $all_netgroups                   = Net_group::get_list($conn, $condition, "ORDER BY name", TRUE);
        $_node_name                      = _("All Assets");

        if ($entity_id != '')
        {
            $uoe        = "e_".$entity_id."_";
            $_e_name    = Util::utf8_encode2(Session::get_entity_name($conn, $entity_id));
            $_node_name = ($_e_name != _('Unknown entity')) ? _("Assets from") . ' ' . $_e_name : _("Assets");
        }
        elseif ($sensor_id != '')
        {
            $uoe = "s_".$sensor_id."_";
        }
        else
        {
            $uoe = '';
        }

        $buffer = '';

        if (count($all_cclass_hosts) > 0 || count($all_hostgroups) > 0 || count($all_cclass_nets) > 0 || count($all_netgroups) > 0) {

            $buffer .= '{"title" : "'. $_node_name .'", "key" : "key1", "icon" : "../../pixmaps/theme/any.png", "noLink" : true, "expand" : true, "children" : ['."\n";
            $aux_children = array();
            if (count($all_cclass_hosts) > 0) {
                $aux_children[] = '{ "key" : "'.$uoe.'host", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host.png", "title" : "'._("Assets").'"}';
            }
            if (count($all_hostgroups) > 0 && $sensor_id == '') {
                $aux_children[] = '{ "key" : "'.$uoe.'hostgroup", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host_group.png", "title" :"'._("Asset Groups").'"}';
            }
            if (count($all_cclass_nets) > 0) {
                $aux_children[] = '{ "key" : "'.$uoe.'net", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net.png", "title" : "'._("Networks").'"}';
            }
            if (count($all_netgroups) > 0 && $sensor_id == '') {
                $aux_children[] = '{ "key" : "'.$uoe.'netgroup", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net_group.png", "title" : "'._("Network Groups").'"}';
            }

            $buffer .= implode(',', $aux_children) . ']}';

        }
        else {
            $buffer = '{"title" : "'._("No Assets filter selected").'", "key" : "key1", "noLink" : false}';
        }

        echo $buffer;
    }


    public function draw_assets($entity_id = '', $sensor_id = '')
    {
        $conn = $this->conn;

        $_node_name = _('All Assets');

        if ($entity_id != '')
        {
            $uoe        = 'e_'.$entity_id.'_';
            $_e_name    = Util::utf8_encode2(Session::get_entity_name($conn, $entity_id));
            $_node_name = ($_e_name != _('Unknown entity')) ? _('Assets from') . ' ' . $_e_name : _('Assets');

        }
        elseif ($sensor_id != '')
        {
            $uoe = "s_".$sensor_id.'_';
        }
        else
        {
            $uoe = '';
        }

        $buffer = '{"title" : "'. $_node_name .'", "key" : "key1", "icon" : "../../pixmaps/theme/any.png", "noLink" : true, "expand" : true, "children" : ['."\n";

        $aux_children = array();
        $aux_children[] = '{ "key" : "'.$uoe.'host", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host.png", "title" : "'._("Assets").'"}';
        $aux_children[] = '{ "key" : "'.$uoe.'hostgroup", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host_group.png", "title" :"'._("Asset Groups").'"}';
        $aux_children[] = '{ "key" : "'.$uoe.'net", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net.png", "title" : "'._("Networks").'"}';
        $aux_children[] = '{ "key" : "'.$uoe.'netgroup", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net_group.png", "title" : "'._("Network Groups").'"}';
        $buffer .= implode(',', $aux_children) . ']}';

        echo $buffer;
    }


    /* Draw an entity tree element with its assets UP the tree
     * Parameters:
     * - entity_id
     */
    public function draw_assets_up($entity_id = '')
    {
        //$all_cclass_hosts = $this->:get_cclass_hosts($conn, $entity_id, '', '', '', "up");
        //list($all_cclass_nets, $cclasses, $bclasses, $aclasses) = self::get_xclass_nets($conn, $entity_id, '', '', "up");

        $prefilter = ($entity_id != '') ? "e_".$entity_id."_" : '';

        $buffer = '';

        $buffer .= '{"title" : "'._("All Assets").'", "key" : "key1", "icon" : "../../pixmaps/theme/any.png", "expand" : true, "children" : ['."\n";
        $buffer .= '{ "key" : "'.$prefilter.'hostup", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host.png", "title" : "'._("Assets").'"}';
        $buffer .= ", ";
        $buffer .= '{ "key" : "'.$prefilter.'netup", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/net.png", "title" : "'._("Networks").'"}';
        $buffer .= ']}';

        echo $buffer;
    }


    /* Draw a tree with sensors */
    public function draw_sensors($entity_id = '')
    {
        $conn = $this->conn;

        $q_where = '';

        // Entity list
        $from_others_ctx = ($entity_id != '') ? TRUE : FALSE;
        if (preg_match("/\,/", $entity_id))
        {
            $q_where = "sensor.id = acl_sensors.sensor_id AND acl_sensors.entity_id IN (UNHEX('".str_replace(",", "'), UNHEX('", $entity_id)."'))";
        // Unique entity ID
        }
        else
        {
            $q_where = ($entity_id != '') ? "sensor.id = acl_sensors.sensor_id AND acl_sensors.entity_id = UNHEX('$entity_id')" : '';
        }


        $j = 0;
        $h = '550';

        $q_filters = array(
            'where'    => $q_where,
            'order_by' => 'name'
        );

        list($sensors, $s_total) = Av_sensor::get_list($conn, $q_filters, TRUE, $from_others_ctx);

        if ($s_total == 0)
        {
            $buffer = '{"title" : "'._("No Sensors Found").'", "noLink" : true, "key" : ""}';
        }
        else
        {
            $buffer  = '{"title": "'._("Sensor list").'", "key":"key1", "icon":"../../pixmaps/theme/ports.png", "expand":true'."\n";
            $buffer .= ', "children":[';

            $already = array(); // Repeats when several entities selected

            foreach ($sensors as $sensor_id => $sensor)
            {
                $icon = "../../pixmaps/server.png";

                $sensor_name = $sensor['name'];
                $sensor_ip   = $sensor['ip'];

                $_url        = "/ossim/sensor/newsensorform.php?id=$sensor_id";
                $sensor_url  = Menu::get_menu_url($_url, "configuration", "deployment", "components");

                if ($already[$sensor_id])
                {
                    continue;
                }

                $s_title  = Util::utf8_encode2($sensor_name);

                $title    = (strlen($sensor_name) > $this->length_name) ? substr($sensor_name, 0, $this->length_name)."..." : $sensor_name;
                $title    = Util::utf8_encode2($title);

                $tooltip  = $s_title;

                $li = '"title" : "'.$title.'", "tooltip" : "'.$tooltip.'", "icon" : "'.$icon.'", "h" : "'.$h.'", "key" : "sensor_'.$sensor_id.'" , "val" : "'.utf8_encode($sensor_name).'", "ip" : "'.$sensor_ip.'" , "url" : "'.$sensor_url.'"';

                $buffer .= (($j > 0) ? "," : '') . "{ $li }";

                $j++;
                $already[$sensor_id] = TRUE;
            }

            $buffer .= "] }";
        }

        echo $buffer;
    }


    /* Draw a tree with alienvault components
     */
    public function draw_alienvault_components()
    {
        $conn = $this->conn;

        echo '{"title" : "<span style=\'font-weight:normal\'>'._("AlienVault Components").'</span>", "isFolder" : true, "icon" : "../../pixmaps/theme/server_role.png", "expand" : true, "children" :[';

        if (Session::am_i_admin())
        {
            echo '{ "key" : "servers", "page" : "", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/theme/host_os.png", "title" : "'._("Servers").'" },';
            $databases = Databases::get_list($conn, "ORDER BY name");
            if (count($databases) > 0) {
                echo '{ "key" : "databases", "page" : "", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/databases.png", "title" : "'._("Databases").'" },';
            }
        }

        echo '{ "key" : "sensors", "page" : "", "isFolder" : true, "isLazy" : true, "icon" : "../../pixmaps/server.png", "title" :"'._("Sensors").'" }';
        echo "]}";
    }


    /* Draw a tree with users
     * Parameters:
     * - entity_id: ctx filter, will draw all entity users
     */
    public function draw_users($entity_id = '')
    {
        $conn = $this->conn;

        $admin_users   = array();
        $entities      = Acl::get_entities($conn, '', '', TRUE, FALSE);
        $entities_list = $entities[0];

        foreach ($entities_list as $entity) {
            $admin_users[$entity["id"]] = $entity["admin_user"];
        }

        if ($entity_id != ''){
            $user_list = Acl::get_users_by_entity($conn, $entity_id);
        } else {
            $user_list = Acl::get_orph_users($conn);
        }


        $j = 0;

        foreach ($user_list as $user)
        {
            $login = $user['login'];

            $user_allowed = Session::userAllowed($login);
            if ($user_allowed < 1){
                continue;
            }

            $name  = $user['name'];

            if (Session::is_admin($conn, $login)){
                $icon = "../../pixmaps/user-gadmin.png";
            }
            elseif($admin_users[$entity_id] == $login){
                $icon = "../../pixmaps/user-business.png";
            }
            else{
                $icon = "../../pixmaps/user-green.png";
            }


            $u_title = Util::utf8_encode2($login);

            $title   = (strlen($name) > $this->length_name) ? substr($name, 0, $this->length_name)."..." : $name;
            $title   = Util::utf8_encode2($title);
            $tooltip = $u_title;

            if ($user_allowed == 2 && $this->section != 'assets' && $this->section != 'entities')
            {
                $u_link = '"noLink" : false';
                $u_key  = '"u_'.$login.'"';
            }
            else
            {
                $u_link = '"noLink" : true';
                $u_key  = '""';
            }


            $li  = '"title" : "'.$title.'", '.$u_link.', "icon" : "'.$icon.'", "tooltip" : "'.$tooltip.'", "key" : '.$u_key;

            $buffer .= (($j > 0) ? "," : '') . "{ $li }";
            $j++;
        }

        if ($buffer == "[]"){
            $buffer = '{"title" : "'._("No Users Found").'", "noLink" : true}';
        }

        echo $buffer;
    }


    /* Draw a tree with users
     *
     */
    public function draw_all_users()
    {
        $conn          = $this->conn;
        $pro           = Session::is_pro();
        $user_list     = Session::get_users_to_assign($conn);


        foreach ($user_list as $user)
        {
            $login = $user->login;

            $user_allowed = Session::userAllowed($login);

            if ($user_allowed < 2){
                continue;
            }

            $name = $user->name;

            if (Session::is_admin($conn, $login)){
                $icon = "../../pixmaps/user-gadmin.png";
            }
            elseif($pro && Acl::is_proadmin($conn, $login)){
                $icon = "../../pixmaps/user-business.png";
            }
            else{
                $icon = "../../pixmaps/user-green.png";
            }

            $u_key   = "u_".$login;


            $u_title = Util::utf8_encode2($login);

            $title   = (strlen($name) > $this->length_name) ? substr($name, 0, $this->length_name)."..." : $name;
            $title   = Util::utf8_encode2($title);
            $tooltip = $u_title;


            $li  = '"title" : "'.$title.'", "noLink" : false, "icon" : "'.$icon.'", "tooltip" : "'.$tooltip.'", "key" : "'.$u_key.'"';

            $buffer .= (($j > 0) ? "," : '') . "{ $li }";
            $j++;
        }

        if ($buffer == "[]"){
            $buffer = '{"title" : "'._("No Users Found").'", "noLink" : true}';
        }

        echo $buffer;
    }


    /* Draw a tree with servers */
    public function draw_servers()
    {
        $conn = $this->conn;

        if (Session::am_i_admin())
        {
            $h = '600';
            $j = 0;

            $servers = Server::get_list($conn, "ORDER BY name");

            if (count($servers) == 0)
            {
                $buffer .= '{"title" : "'._("No Servers Found").'", "noLink" : true}';
            }
            else
            {
                foreach ($servers as $server)
                {
                    $icon = "../../pixmaps/theme/host.png";

                    $server_name = $server->get_name();
                    $serv_title  = Util::utf8_encode2($server_name);
                    $_url        = "/ossim/server/modifyserverform.php?id=".$server->get_id();
                    $server_url  = Menu::get_menu_url($_url, "configuration", "deployment", "components");

                    $title   = (strlen($server_name) > $this->length_name) ? substr($server_name, 0, $this->length_name)."..." : $server_name;
                    $title   = Util::utf8_encode2($title);

                    $tooltip = $serv_title;

                    $li      = '"title" : "'.$title.'", "tooltip" : "'.$tooltip.'", "icon" : "'.$icon.'", "h" : "'.$h.'", "url" : "'.$server_url.'"';
                    $buffer .= (($j > 0) ? "," : '') . "{ $li }";
                    $j++;
                }
            }
        }
        else
        {
            $buffer .= '{"title" : "'._("No Servers Found").'", "noLink" : true}';
        }

        echo $buffer;
    }


    /* Draw a tree with databases
     */
    public function draw_databases()
    {
        $conn = $this->conn;

        if ( Session::am_i_admin())
        {
            $j = 0;
            $h = '550';

            $databases = Databases::get_list($conn, "ORDER BY name");

            if (count($databases) == 0)
            {
                $buffer .= '{"title" : "'._("No Databases Found").'", "noLink" : true}';
            }
            else
            {
                foreach ($databases as $database)
                {
                    $icon     = "../../pixmaps/database.png";
                    $db_name  = $database->get_name();
                    $db_title = Util::utf8_encode2($db_name);

                    $title    = (strlen($db_name) > $this->length_name) ? substr($db_name, 0, $this->length_name)."..." : $db_name;
                    $title    = Util::utf8_encode2($title);

                    $tooltip  = $db_title;

                    $_url       = '../server/newdbsform.php?id='.$database->get_id();
                    $url_link   = Menu::get_menu_url($_url, "analysis", "security_events", "databases");

                    $li       = '"title" : "'.$title.'", "tooltip" : "'.$tooltip.'", "icon" : "'.$icon.'", "h" : "'.$h.'", "url" : "'.$url_link.'"';

                    $buffer  .= (($j > 0) ? "," : '') . "{ $li }";
                    $j++;
                }
            }
        }
        else
        {
            $buffer .= '{"title" : "'._("No Databases Found").'", "noLink" : true}';
        }

        echo $buffer;
    }


    public function draw_portgroups()
    {
        $conn = $this->conn;

        $port_groups = array();
        $buffer      = '';

        if ($port_group_list = Port_group::get_list($conn, "ORDER BY name"))
        {
            foreach($port_group_list as $pgroup)
            {
                $pg_name    = $pgroup->get_name();
                $pg_id      = $pgroup->get_id();
                $pg_descr   = $pgroup->get_descr();

                if (!preg_match("/ANY/i", $pg_name))
                {
                    $port_groups[] = array($pg_id, $pg_name, $pg_descr);
                }
                else
                {
                    $port_groups[] = array($pg_id, $pg_name, '');
                }
            }
        }


        $buffer .= '{"title": "'._("Port Groups").'", "key":"key1", "isFolder":true, "icon":"../../pixmaps/theme/ports.png", "expand":true'."\n";
        if (count($port_groups) > 0)
        {
            $j = 1;

            $buffer .= ', "children":[';
            foreach($port_groups as $pg)
            {
                $pg_name   = utf8_encode($pg[1]);
                $pg_key    = "pg_".$pg[0];
                $tooltip   = ($pg[2] != '') ? ',"tooltip":"'.$pg[2].'"' : '';

                $html     = '';
                if ($pg_name == "ANY")
                    $li = '"key":"ANY", "url":"'.$pg_name.'", "icon":"../../pixmaps/theme/ports.png", "title":"'.$pg_name.'", "tooltip":"'.$pg_name.'"'."\n";
                else
                    $li = '"key":"'.$pg_key.'", "url":"'.$pg_name.'", "icon":"../../pixmaps/theme/ports.png", "title":"'.$pg_name.'" '.$tooltip."\n";

                if ($html != '')
                    $buffer .= (($j > 1) ? "," : '') . '{ '.$li.', "children":[ ' . preg_replace("/,$/", '', $html) . ' ] }'."\n";

                else $buffer .= (($j > 1) ? "," : '') . "{ $li }\n";

                $j++;
            }
            $buffer .= "]";
        }
        $buffer .= "}";

        echo $buffer;
    }


    /* Return if we ca draw an external ctxs or not
     * Parameters:
     * - e_id: entity uuid
     */
    public function can_see_external_ctx($e_id)
    {
        if ($this->extra_options == '')                                             return TRUE;
        if ($this->extra_options =="remote" && !empty($this->external_ctxs[$e_id])) return TRUE;
        if ($this->extra_options =="local"  &&  empty($this->external_ctxs[$e_id])) return TRUE;

        return FALSE;
    }


    /* Draw a recursive tree with entities
     * Parameters:
     * - with_assets: can toggle assets by each entity
     * - with_users: can toggle users by each entity
     */
    public function draw_entities($with_assets = FALSE, $with_users = FALSE, $only_contexts=FALSE)
    {
        $conn          = $this->conn;
        $entities      = Acl::get_entities($conn, '', '', TRUE, FALSE);
        $my_entities   = Acl::get_my_entities($conn, '', FALSE);
        $num_entities  = count($my_entities);
        $entities_root = Acl::get_entities_root($conn);

        if (isset($_SESSION['_user_vision']['entity'])){
            $entities_visibility = $_SESSION['_user_vision']['entity'];
        }
        else
        {
            $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
            $entities_visibility = $_SESSION['_user_vision']['entity'];
        }

        if ($num_entities > 0 || (Session::am_i_admin() && count($entities[0])> 0))
        {
             echo '{"title" : "<span style=\'font-weight:normal\'>'. _("Visibility") .'</span>", "isFolder" : true, "icon" : "../../pixmaps/company.png", "expand" : true, "children" :[';

            $flag = FALSE;
            foreach ($entities_root as $e_id => $e_data){

                if ($entities_visibility[$e_id] > 0 && self::can_see_external_ctx($e_id))
                {
                    if ($flag){
                        echo ",";
                    }

                    $flag = TRUE;

                    $icon         = "../../pixmaps/gear.png";

                    $entity_name  = (preg_match('/&#(\d{4,5});/',$e_data['name'])) ? mb_convert_encoding($e_data['name'],'UTF-8','HTML-ENTITIES') : $e_data['name'] ;
                    $e_sn         = (strlen($entity_name) > $this->length_name)   ? substr($entity_name, 0, $this->length_name)."..." : $entity_name;
                    $e_name       = Util::utf8_encode2($e_sn);

                    if ($entities_visibility[$e_id] == 2)
                    {
                        $e_link = '"noLink" : false';
                        $e_url  = '"../acl/entities_edit.php?id='.$e_id.'&entity_type='.$e_data['type'].'"';
                    }
                    else
                    {
                        $e_link = '"noLink" : true';
                        $e_url  = '""';
                    }

                    $e_key  = "e_".$e_id;

                    $title   = "<span style='font-weight:bold;'>".$e_name."</span>";
                    $tooltip = $e_name;
                    $h       = "700";

                    echo '{"title" : "'.$title.'", "h" : "'.$h.'", '.$e_link.', "tooltip" : "'.$tooltip.'", "key" : "'.$e_key.'", "val" : "'.$e_name.'", "icon" : "'.$icon.'", "expand" : true,  "url" : '.$e_url.', "name" : "'.$e_name.'"';
                            self::echochildrens($entities, $e_id, $with_assets, $with_users, $only_contexts);
                    echo "}";

                }
            }

            echo "]}";

            if ($with_users && Session::am_i_admin())
            {
                echo ',{"title" : "<span style=\'font-weight:normal\'>'._("Other Users").'</span>", "isFolder" : true, "icon" : "../../pixmaps/users.png", "isLazy" : true, "key" :"ou"}';
            }
        }
        else
        {
            echo '{"title": "'._("No contexts found in your system").'", "icon":"../../pixmaps/company.png"}';
        }
    }


    public function draw_engines_server($server)
    {
        $conn    = $this->conn;
        $engines = Acl::get_engines_by_server($conn,$server);

        if(count($engines) > 0)
        {
            $buffer_engine = array();
            $buff_eng_aux  = '';

            foreach($engines as $eng)
            {
                $buff_eng_aux = '{"key":"'.$eng['id'].'", "title" : "'. $eng['name'] .'", "isFolder" : true, "isEngine" : true, "icon" : "../../pixmaps/server--gear.png", "expand" : true, "children" :[';

                $contexts = Acl::get_contexts_by_engine($conn,$eng['id']);

                if(count($contexts) > 0)
                {
                    $buffer_context = array();
                    foreach($contexts as $ctx)
                    {
                        $buffer_context[] = '{"key":"'.$ctx['id'].'", "isContext" : true, "url":"", "icon":"../../pixmaps/gear.png", "title":"'.$ctx['name'].'"}'."\n";
                    }

                    $buff_eng_aux .= implode(',', $buffer_context);

                }
                else
                {
                    if(Session::get_default_engine($conn) == $eng['id'])
                    {
                        $buff_eng_aux .= '{"title" : "'._("No Contexts Found").'", "noLink" : true}';
                    }
                    else
                    {
                        $buff_eng_aux .= '{"key":"'.$eng['id'].'", "title" : "'._("No Contexts Found").' - <span class=\'del_eng\' data-id=\''.$eng['id'].'\'>'._("Delete Engine").'</span>", "noLink" : true}';
                    }
                }

                $buff_eng_aux .= "]}";

                $buffer_engine[] = $buff_eng_aux;
            }

            echo implode(',', $buffer_engine);
        }
        else
        {

            echo '{"title" : "'._("No Engines Found").'", "noLink" : true}';
        }
    }


    public function draw_asec_plugins($directory)
    {
        $buffer      = '';
        $plugin_path = '/var/lib/asec/plugins/';

        $directory   = (!empty($directory)) ? base64_decode($directory)."/" : '';
        $path        = $plugin_path.$directory;

        if (empty($directory))
        {
            $buffer   = '{ "title": "'._("Plugins generated").'", "key": "ASEC_PLUGINS", "icon":"../../pixmaps/theme/any.png", "noLink" : true, "isFolder" : true, "expand" : true , "children" : ['."\n";
        }

        if(is_dir($path))
        {
            $files = scandir($path);

            natcasesort($files);

            if(count($files) > 2) /* The 2 accounts for . and .. */
            {
                $d_buffer = array();
                $f_buffer = array();

                foreach($files as $file) if (preg_match("/\.cfg$/",$file))
                {
                    if(file_exists($path . $file) && $file != '.' && $file != '..')
                    {
                        if (is_dir($path . $file))
                        {
                            $key     = "asec_pg_d_".base64_encode($directory."/".$file);
                            $icon    = '../../pixmaps/theme/folder.png';
                            $title   = "<span class='cursive'>".Util::htmlentities($file)."</span>";
                            $tooltip = Util::htmlentities($file);

                            $d_buffer[] = '{ "key" : "'.$key.'", "icon" : "'.$icon.'", "title" : "'. $title.'", "isLazy" : true, "tooltip" : "'.$tooltip.'", "isFolder" : true}';
                        }
                        else
                        {
                            $key     = "asec_pg_f_".base64_encode($directory."/".$file);
                            $icon    = '../../asec/images/file_tree/db.png';
                            $title   = "<span class='cursive'>".Util::htmlentities($file)."</span>";
                            $tooltip = Util::htmlentities($file);

                            $f_buffer[] = '{ "key" : "'.$key.'", "icon" : "'.$icon.'", "title" : "'. $title.'", "isLazy" : false, "tooltip" : "'.$tooltip.'", "isFolder" : false}';
                        }
                    }
                }

                $buffer .= implode(",\n", $d_buffer);

                $buffer .= (!empty($d_buffer)) ? ",\n" : '';
                $buffer .= implode(",\n", $f_buffer);
            }
            else
            {
                $buffer .= '{"title" : "'._("No entries found").'", "noLink" : true, "expand" : false}';
            }
        }
        else
        {
            $buffer .= '{"title" : "'._("No entries found").'", "noLink" : true, "expand" : false}';
        }


        if (empty($directory))
        {
            $buffer .= '] }';
        }

        echo $buffer;
    }


    /* Recursive draw for draw_entities
     * Parameters:
     * - entities: Array with all entities
     * - parent_id: Parent Entity ID
     * - with_assets: can toggle assets by each entity
     * - with_users: can toggle users by each entity
     */
    public function echochildrens($entities, $parent_id, $with_assets, $with_users, $only_contexts=false)
    {
        echo ',"children" : [';

        /* Connect to db */
        $db   = new ossim_db(TRUE);
        $conn = $db->connect();

        if (isset($_SESSION['_user_vision']['entity'])){
            $entities_visibility = $_SESSION['_user_vision']['entity'];
            $entities_to_assign  = $_SESSION['_user_vision']['entities_to_assign'];
        }
        else
        {
            $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
            $entities_visibility = $_SESSION['_user_vision']['entity'];
            $entities_to_assign  = $_SESSION['_user_vision']['entities_to_assign'];
        }


        $flag = FALSE;

        if (isset($entities_to_assign[$parent_id]) && $with_assets)
        {
            $this->draw_assets($parent_id);
            $flag = TRUE;
        }

        if (isset($entities_to_assign[$parent_id]) && $with_users)
        {
            $user_list = Acl::get_users_by_entity($conn, $parent_id);
            if (count($user_list) > 0)
            {
                if ($flag){
                    echo ", ";
                }

                echo '{"title" : "<span style=\'font-weight:normal\'>'._("Users").'</span>", "isFolder" : true, "icon" : "../../pixmaps/users.png", "isLazy" : true, "key" :"ue_'.$parent_id.'"}';

                $flag = TRUE;
            }
        }

        $children = Acl::get_entity_childs($conn,$parent_id, '', TRUE);

        if (!empty($children))
        {
            foreach ($children as $child_id)
            {
                if ($entities_visibility[$child_id] == 0 || ($entities[0][$child_id]['type'] != 'context' && $only_contexts == TRUE))
                {
                    continue;
                }

                $child      = $entities[0][$child_id];
                $icon       = ($child['type'] == "logical") ? "../../pixmaps/theme/any.png" : "../../pixmaps/gear.png";

                $visibility = ($this->section == 'reports') ? 1 : 2;

                if (isset($entities_to_assign[$child_id]) && $entities_visibility[$child_id] >= $visibility)
                {
                    $child_link  = '"noLink" : false';
                    $child_url   = '"../acl/entities_edit.php?id='.$child_id.'&entity_type='.$child['type'].'"';
                }
                else
                {
                    $child_link  = '"noLink" : true';
                    $child_url   = '""';
                }

                $child_key  = "e_".$child_id;

                $child_name = (preg_match('/&#(\d{4,5});/',$child['name'])) ? mb_convert_encoding($child['name'],'UTF-8','HTML-ENTITIES') : $child['name'] ;
                $child_sn   = (strlen($child_name) > $this->length_name)  ? substr($child_name, 0, $this->length_name)."..." : $child_name;

                $ch_name    = Util::utf8_encode2($child_sn);

                if ($child['parent_id'] == $parent_id && self::can_see_external_ctx($child_id))
                {
                    $title   = "<span style='font-weight:bold;'>".$ch_name."</span>";
                    $tooltip = $ch_name;

                    if ($flag) { echo ", "; }
                    $flag = TRUE;

                    $h    = "700";

                    echo '{"title" : "'.$title.'", "h" :"'.$h.'", '.$child_link.', "url" : '.$child_url.', "tooltip" : "'.$tooltip.'", "key" : "'.$child_key.'", "val" : "'.$ch_name.'", "icon" : "'.$icon.'", "name" : "'.$ch_name.'"';
                        self::echochildrens($entities, $child_id, $with_assets, $with_users, $only_contexts);
                    echo "}";
                }
            }
        }
        echo "]";

        $db->close();
    }


    public function draw_any_option()
    {
        echo '{"title": "'._("ANY").'", "key":"ANY", "icon":"../../pixmaps/theme/host.png", "val": "'._("ANY").'"}';
    }

    public function draw_home_options()
    {
        echo '{"title": "'._("HOME_NET").'", "key":"'.Policy::getHOMENETKEY().'", "icon":"../../pixmaps/theme/host.png", "val": "'._("HOME_NET").'"},
            {"title": "'._("!HOME_NET").'", "key":"'.Policy::getNOTHOMENETKEY().'", "icon":"../../pixmaps/theme/host.png", "val": "'._("!HOME_NET").'"}';
    }


}


/* End of file tree.inc */
/* Location: ../include/classes/tree.inc */
