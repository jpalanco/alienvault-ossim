<?php

/**
 * asset_group.inc
 *
 * File asset_group.inc is used to:
 *   - To manage groups
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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
 * @package    ossim-framework\Asset
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_config.php';


/**
 * Asset_group Class
 *
 * Class for managing host groups
 *
 * @package    ossim-framework\Asset
 * @autor      AlienVault INC
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Asset_group
{
    /**
    * Host Group ID
    *
    * @var    string
    * @access protected
    */
    protected $id;


    /**
    * Host Group CTX
    *
    * @var    string
    * @access protected
    */
    protected $ctx;


    /**
    * Host Group Name
    *
    * @var    string
    * @access protected
    */
    protected $name;


    /**
    * Host Group Description
    *
    * @var    string
    * @access protected
    */
    protected $description;


    /**
    * Host Group Owner
    *
    * @var    string
    * @access protected
    */
    protected $owner;


    /**
    * Function __construct
    *
    * This function sets up the class
    *
    * @param string $id Host Group ID
    *
    * @access public
    */
    public function __construct($id)
    {
        $this->id          = strtoupper($id);
        $this->ctx         = '';
        $this->name        = '';
        $this->description = '';
        $this->owner       = '';
    }


    /**
    * Function get_id
    *
    * This function returns the id of the host group
    *
    * @access public
    *
    * @return string
    */
    public function get_id()
    {
        return $this->id;
    }


    /**
    * Function get_ctx
    *
    * This function returns the ctx of the host group
    *
    * @access public
    *
    * @return string
    */
    public function get_ctx()
    {
        return $this->ctx;
    }


    /**
    * Function get_name
    *
    * This function returns the name of the host group
    *
    * @access public
    *
    * @return string
    */
    public function get_name()
    {
        return $this->name;
    }


    /**
    * Function get_descr
    *
    * This function returns the description associated with the host group
    * @access public
    *
    * @param boolean  $entities  [Optional] Transform to HTML entities
    *
    * @return string
    */
    public function get_descr($entities = TRUE)
    {
        if ($entities)
        {
            return Util::htmlentities($this->description);
        }
        else
        {
            return $this->description;
        }
    }


    /**
    * Function get_owner
    *
    * This function returns the owner of the host group
    *
    * @access public
    *
    * @return string
    */
    public function get_owner()
    {
        return $this->owner;
    }


    /**
    * Function set_id
    *
    * This function sets the id of the host group
    *
    * @param string $id Host Group ID
    *
    * @access public
    *
    * @return void
    */
    public function set_id($id)
    {
        $this->id = strtoupper(trim($id));
    }

    /**
    * Function set_ctx
    *
    * This function sets the ctx of the host group
    *
    * @param string $ctx Host Group CTX
    *
    * @access public
    *
    * @return void
    */
    public function set_ctx($ctx)
    {
        $this->ctx = trim($ctx);
    }


    /**
    * Function set_name
    *
    * This function sets the name of the host group
    *
    * @param string $name Host Group Name
    *
    * @access public
    *
    * @return void
    */
    public function set_name($name)
    {
        $this->name = trim($name);
    }


    /**
    * Function set_descr
    *
    * This function sets the description associated with the host group
    *
    * @param string $description Host Group Description
    *
    * @access public
    *
    * @return void
    */
    public function set_descr($description)
    {
        $this->description = trim($description);
    }


    /**
    * Function set_owner
    *
    * This function sets the owner of the host group
    *
    * @param string $owner Host Group Owner
    *
    * @access public
    *
    * @return void
    */
    public function set_owner($owner)
    {
        $this->owner = stripslashes($owner);
    }

    /**
    * Function load_from_db
    *
    * This function loads asset group from db
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function load_from_db($conn)
    {
        Ossim_db::check_connection($conn);

        $query  = "SELECT g.*, HEX(g.id) AS id, HEX(g.ctx) AS ctx FROM host_group g WHERE g.id = UNHEX(?)";
        $query  = ossim_query($query);
        $params = array($this->id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $this->set_ctx($rs->fields['ctx']);
            $this->set_name($rs->fields['name']);
            $this->set_descr($rs->fields['descr']);
            $this->set_owner($rs->fields['owner']);
        }
    }


    /**
    * Function save_in_db
    *
    * This function save asset group in db
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function save_in_db($conn)
    {
        Ossim_db::check_connection($conn);

        $exist = self::is_in_db($conn, $this->id);

        if ($exist)
        {
            $this->can_i_edit($conn);

            $action = 'update';
        }
        else
        {
            $action = 'new';
        }
        // **** Warning: Execution flow never updating when $exists = TRUE because of this 'if' sentence ****
        if (self::is_group_name_duplicated($conn, $this->id, $this->name))
        {
            $agn_msg = sprintf(_('The group "%s" already exists. Please choose a different name for your group.'), Util::htmlentities($this->name));
            Av_exception::throw_error(Av_exception::USER_ERROR, $agn_msg);
        }

        $query = "REPLACE INTO host_group(id, ctx, name, descr, owner)
                    VALUES (UNHEX(?), UNHEX(?), ?, ?, ?)";

        $params = array(
            $this->id,
            $this->ctx,
            $this->name,
            $this->description,
            $this->owner
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        Util::memcacheFlush();

        $infolog = array
        (
            $this->name . ' (' . $this->id . ')'
        );

        $this->save_history($conn, $action);

        Log_action::log(73, $infolog);
    }


    /**
    * Function save_assets_from_search
    *
    * This function saves host_group assets from search in db
    *
    * @param object $conn                         Database access object
    * @param bool   $check_edit_perms [Optional]  Flag to bypass the perms checking
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function save_assets_from_search($conn, $check_edit_perms = TRUE)
    {
        Ossim_db::check_connection($conn);

        /* Checking if I see all the members of the group.
         * The case when creating a new group from asset list must allow
         */
        if ($check_edit_perms)
        {
            $this->can_i_edit($conn);
        }

        $perms = Asset_host::get_perms_where('h.', TRUE);

        $query = "REPLACE INTO host_group_reference (
                    SELECT UNHEX(?), f.asset_id
                        FROM user_component_filter f, host h
                        WHERE f.session_id=? AND f.asset_type='asset' AND h.id=f.asset_id $perms)";

        $params = array(
            $this->id,
            session_id()
        );

        Util::memcacheFlush();

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Get affected assets to update history
        $asset_list = array();

        $query = "SELECT HEX(f.asset_id) AS asset_id
                    FROM user_component_filter f, host h
                    WHERE f.session_id=? AND f.asset_type='asset' AND h.id=f.asset_id $perms";

        $params = array(session_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $asset_list[] = $rs->fields['asset_id'];

            $rs->MoveNext();
        }

        $this->save_history($conn, 'add', $asset_list);
    }


    /**
    * Function save_assets_from_list
    *
    * This function save host_group assets from list in db
    *
    * @param object $conn        Database access object
    * @param array  $asset_list  Asset list
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function save_assets_from_list($conn, $asset_list)
    {
        Ossim_db::check_connection($conn);

        $assets = "UNHEX('" . implode("'), UNHEX('", $asset_list) . "')";


        $perms = Asset_host::get_perms_where('h.', TRUE);


        $query = "REPLACE INTO host_group_reference (
                    SELECT UNHEX(?), h.id
                        FROM host h
                        WHERE h.id IN($assets) $perms)";

        $params = array($this->id);

        Util::memcacheFlush();

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $this->save_history($conn, 'add', $asset_list);
    }


    /**
    * Function add_host
    *
    * Add host or hosts to host group
    *
    * @param object $conn Database access object
    * @param array  $assets
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function add_host($conn, $assets)
    {
        Ossim_db::check_connection($conn);

        //Checking if I see all the members of the group.
        $this->can_i_edit($conn);

        $assets       = is_array($assets) ? $assets : array($assets);
        $assets_where = "UNHEX('" . implode("'), UNHEX('", $assets) . "')";

        $perms = Asset_host::get_perms_where('h.', TRUE);

        $query = "REPLACE INTO host_group_reference (host_group_id, host_id) SELECT UNHEX(?), h.id FROM host h WHERE h.id IN ($assets_where) $perms";

        $params = array($this->id);

        if ($conn->Execute($query, $params) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Set nagios of the new added hosts if enabled
        if (Asset_group_scan::is_plugin_in_group($conn, $this->id, 2007))
        {
            foreach ($assets as $asset_id)
            {
                if (!Asset_host_scan::is_plugin_in_host($conn, $asset_id, 2007))
                {
                    Asset_host_scan::save_plugin_in_db($conn, $asset_id, 2007);
                }
            }

            // Refresh .cfg file
            Asset_group_scan::save_plugin_in_db($conn, $this->id, 2007);
        }

        Util::memcacheFlush();

        $this->save_history($conn, 'add', $assets);
    }


    /**
    * Function delete
    *
    * This function deletes asset_group from db
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function delete($conn)
    {
        Ossim_db::check_connection($conn);

        $this->can_delete_group($conn);

        $queries = array();
        $params  = array($this->id);

        //Host Group
        $queries[] = 'DELETE FROM host_group WHERE id = UNHEX(?)';

        //KDB Relationships
        $queries[] = 'DELETE FROM repository_relationships WHERE keyname = ?';

        //Host Group Reference
        $queries[] = 'DELETE FROM host_group_reference WHERE host_group_id = UNHEX(?)';

        //bp_member_status
        $queries[] = 'DELETE FROM bp_member_status WHERE member_id = UNHEX(?)';

        //bp_asset_member
        $queries[] = "DELETE FROM bp_asset_member WHERE member = UNHEX(?) AND type = 'host_group'";

        //Risk Indicator
        $queries[] = "DELETE FROM risk_indicators WHERE type_name = ? AND (type='hostgroup' OR type='host_group')";

        //History
        $queries[] = 'DELETE FROM host_group_history WHERE host_group_id = UNHEX(?)';

        //Nagios reference
        $queries[] = 'DELETE FROM host_group_scan WHERE host_group_id = UNHEX(?)';


        //Begin transaction
        $conn->StartTrans();

        foreach ($queries as $query)
        {
            if ($conn->Execute($query, $params) === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        //End transaction
        $conn->CompleteTrans();

        Util::memcacheFlush();

        $infolog = array($this->name . ' (' . $this->id . ')');

        Log_action::log(75, $infolog);
    }


    /**
    * Function delete_host
    *
    * Delete host from host group
    *
    * @param object $conn Database access object
    * @param array  $assets
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function delete_host($conn, $assets)
    {
        Ossim_db::check_connection($conn);

        //Formatting the assets into array
        $assets = is_array($assets) ? $assets : array($assets);

        //Checking if I see all the members of the group.
        $this->can_i_edit($conn);

        //Checking if I can delete the host. (At least one host in the group).
        $this->can_delete_host($conn, $assets);

        $assets_where = "UNHEX('" . implode("'), UNHEX('", $assets) . "')";

        $perms = Asset_host::get_perms_where('h.', TRUE);

        $query = "DELETE FROM host_group_reference WHERE host_group_id = UNHEX(?) AND host_id IN(SELECT h.id FROM host h WHERE h.id IN($assets_where) $perms)";

        $params = array($this->id);

        if ($conn->Execute($query, $params) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Set nagios of the new added hosts if enabled (refresh .cfg file)
        if (Asset_group_scan::is_plugin_in_group($conn, $this->id, 2007))
        {
            Asset_group_scan::save_plugin_in_db($conn, $this->id, 2007);
        }

        Util::memcacheFlush();

        $this->save_history($conn, 'remove', $assets);
    }


    /**
    * Function delete_assets_from_search
    *
    * This function deletes selected assets (saved in db) from the group
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function delete_selected_assets($conn)
    {
        Ossim_db::check_connection($conn);

        //Checking if I see all the members of the group.
        $this->can_i_edit($conn);

        $perms = Asset_host::get_perms_where('h.', TRUE);

        $query = "DELETE hgr FROM host_group_reference hgr, user_component_filter f, host h
            WHERE hgr.host_group_id = UNHEX(?) AND hgr.host_id=f.asset_id AND f.session_id=? AND f.asset_type='asset'
            AND h.id=f.asset_id $perms";

        $params = array(
            $this->id,
            session_id()
        );

        Util::memcacheFlush();

        $rs = $conn->Execute($query, $params);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Get affected assets to update history
        $asset_list = array();

        $query = "SELECT HEX(f.asset_id) AS asset_id
                  FROM user_component_filter f, host h
                  WHERE f.session_id=? AND f.asset_type='asset' AND h.id=f.asset_id $perms";

        $params = array(session_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $asset_list[] = $rs->fields['asset_id'];

            $rs->MoveNext();
        }

        $this->save_history($conn, 'remove', $asset_list);
    }


    /**
    * Function can_i_edit
    *
    * This function check if the user has permissions to edit the group (It has to be able to see all the hosts)
    *
    * @param object $conn DB connection object
    *
    * @access public
    *
    * @throws Av_exception If the user does not see all the members from the group
    *
    * @return boolean   TRUE If user can edit host group
    */
    public function can_i_edit($conn)
    {
        if (Session::am_i_admin())
        {
            return TRUE;
        }

        Ossim_db::check_connection($conn);

        $total   = $this->get_num_host($conn, TRUE);
        $visible = $this->get_num_host($conn, FALSE);

        if ($total != $visible)
        {
            $exp_msg = _('You do not have enough permissions to modify this Group');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * Function can_delete_host
    *
    * This function check if the user has permissions to delete a member of the group
    *
    * @param object $conn   DB connection object
    * @param array  $assets Assets to delete
    *
    * @access public
    *
    * @throws Av_exception If the user try to delete all the members from the group
    *
    * @return void
    */
    public function can_delete_host($conn, $assets = array())
    {
        $to_delete = empty($assets) ? 1 : count($assets);

        if (!Session::am_i_admin())
        {
            $total = $this->get_num_host($conn, TRUE);

            if (($total - $to_delete) < 1)
            {
                $exp_msg = _('You cannot delete all the assets from the group. At least one asset is required');
                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
    }

    /**
     * Function can_delete_group
     *
     * This function check if the user has permissions to delete the group
     *
     * @param object $conn DB connection object
     *
     * @access public
     *
     * @throws Av_exception If the user does not see all the members from the group or any member belongs to a policy
     *
     * @return boolean
     */
    public function can_delete_group($conn)
    {
        Ossim_db::check_connection($conn);

        // Checking if I see all the members of the group.
        $this->can_i_edit($conn);

        $query  = "SELECT count(*) AS num FROM policy_host_group_reference WHERE host_group_id = UNHEX(?)";
        $params = array($this->id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['num']) > 0)
        {
            $exp_msg = _('This group belongs to a Policy');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * Function get_num_host
    *
    * This function gets the num of hosts from the group
    *
    * @param object  $conn  DB connection object
    * @param boolean $total Flag to indicate if we want the total with or without permissions.
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return integer  Number of host in group
    */
    public function get_num_host($conn, $total = FALSE)
    {
        Ossim_db::check_connection($conn);

        $perms = '';

        if (!$total)
        {
            $perms = Asset_host::get_perms_where('h.', TRUE);
        }

        $query = "SELECT count(hr.host_id) as num
                  FROM host h, host_group_reference hr
                  WHERE hr.host_id=h.id AND hr.host_group_id = UNHEX(?) $perms";

        $params = array($this->id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $rs->fields['num'];
    }


    /**
    * Function has_alarms
    *
    * This function checks if any group membership has alarms
    *
    * @param object $conn Dabatase access object
    *
    * @access public
    *
    * @return boolean  TRUE if host in group has alarms
    */
    public function has_alarms($conn)
    {
        Ossim_db::check_connection($conn);

        return Alarm::has_alarms($conn, 'group', $this->id);
    }


    /**
    * This function checks if any group membership has events
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @return boolean  TRUE if host in group has events
    */
    public function has_events($conn)
    {
        Ossim_db::check_connection($conn);

        return SIEM::has_events($conn, 'group', $this->id);
    }

    /**
     * Function get_vulnerabilities
     *
     * This function returns the asset group vulnerabilities
     *
     * @param object   $conn     Database access object
     * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
     * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param boolean  $cache    [Optional] Use cached information
     *
     * @access public
     * @return array          List of vulnerabilities
     * @throws Av_exception If a connection error occurred
     */
    public function get_vulnerabilities($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $ag_tables = ', host_group_reference hgr';

        if (!empty($tables))
        {
            $ag_tables .= $tables;
        }

        $where = " host_ip.host_id = hgr.host_id AND hgr.host_group_id = UNHEX('" . $this->id . "')";

        if (!empty($filters['where']))
        {
            $filters['where'] = $where . ' AND ' . $filters['where'];
        }
        else
        {
            $filters['where'] = $where;
        }

        return Vulnerabilities::get_vulnerabilities($conn, $ag_tables, $filters, $cache);
    }


    /**
    * Function get_hosts
    *
    * This function returns the hosts from the group
    *
    * @param object    $conn    Database access object
    * @param string    $tables  [Optional] Database tables separated by comma (Join with main table)
    * @param array     $filters [Optional]
    * @param boolean   $basic   [Optional]
    * @param bool      $cache   [Optional]
    *
    * @access public
    *
    * @return array  array[0] -> host list; array[1] -> total of hosts in group
    */
    public function get_hosts($conn, $tables = '', $filters = array(), $basic = FALSE, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $ag_tables = ', host_group_reference hr';

        if (!empty($tables))
        {
            $ag_tables .= $tables;
        }

        $where = " hr.host_id = host.id AND hr.host_group_id = UNHEX('" . $this->id . "') ";

        if (!empty($filters['where']))
        {
            $filters['where'] = $where . ' AND ' . $filters['where'];
        }

        else
        {
            $filters['where'] = $where;
        }

        if ($basic)
        {
            $hosts_basic = Asset_host::get_list_tree($conn, $ag_tables, $filters, $cache, FALSE);

            return array($hosts_basic, count($hosts_basic));
        }

        else
        {
            $hosts_full = Asset_host::get_full_list($conn, $ag_tables, $filters, $cache);

            return $hosts_full; //The format is array($host_list, $total)
        }
    }


    /**
    * Function get_sensors
    *
    * This function returns the sensors related to the asset group
    *
    * @param object  $conn  Database access object
    * @param boolean $cache [Optional] Cache Option
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return array   Sensor list
    */
    public function get_sensors($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $query = 'SELECT DISTINCT HEX(hs.sensor_id) AS sensor_id, s.name, inet6_ntoa(s.ip) AS sensor_ip
                      FROM sensor s, host_sensor_reference hs, host_group_reference hgr
                      WHERE hgr.host_id=hs.host_id AND hgr.host_group_id = UNHEX(?) AND s.id = hs.sensor_id
                      ORDER BY s.priority DESC, s.name ASC';


        $params = array($this->id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $sensors = array();

        while (!$rs->EOF)
        {
            $ctxs = Av_sensor::get_ctx_by_id($conn, $rs->fields['sensor_id']);

            $sensors[$rs->fields['sensor_id']] = array(
                'name' => $rs->fields['name'],
                'ip'   => $rs->fields['sensor_ip'],
                'ctxs' => $ctxs
            );

            $rs->MoveNext();
        }

        return $sensors;
    }


    /**
    * Function get_status
    *
    * This function returns the Machine State property (ID = 7) sum of each related host
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @return string up / down / unknown
    */
    public function get_status($conn)
    {
        Ossim_db::check_connection($conn);

        $status = array(
            'up'      => 0,
            'down'    => 0,
            'unknown' => 0
        );

        $status_list = Asset_host_properties::get_status_by_group($conn, $this->id);

        foreach ($status_list as $st)
        {
            $status[$st]++;
        }

        $status_str = array();

        foreach ($status as $key => $val)
        {
            $status_str[] = "$val $key";
        }

        return implode(' / ', $status_str);
    }


    /**
    * Function get_software
    *
    * This function returns the software related to group
    *
    * @param object $conn    Database access object
    * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @access public
    *
    * @return array List of software
    */
    public function get_software($conn, $filters = array())
    {
        $soft_where = "SELECT host_id FROM host_group_reference WHERE host_group_id = UNHEX('".$this->id."')";

        if (empty($filters['where']))
        {
            $filters['where']  = "h.id IN (".$soft_where.")";
        }
        else
        {
            $filters['where'] .= "AND h.id IN (".$soft_where.")";
        }

        $software = Asset_host_software::get_list($conn, $filters);

        return $software;
    }


    /**
    * Function get_users
    *
    * This function returns the user property (ID = 8) sum of each related hosts
    *
    * @param object $conn    Database access object
    * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @access public
    *
    * @return array
    */
    public function get_users($conn, $filters = array())
    {
        return Asset_host_properties::get_users_by_group($conn, $this->id, $filters);
    }


    /**
    * Function get_services
    *
    * This function returns the services related to host
    *
    * @param object $conn    Database access object
    * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @access public
    *
    * @return array  List of services
    */
    public function get_services($conn, $filters = array())
    {
        $serv_where = "SELECT host_id FROM host_group_reference WHERE host_group_id = UNHEX('" . $this->id . "')";

        if (empty($filters['where']))
        {
            $filters['where'] = 'h.id IN (' . $serv_where . ')';
        }
        else
        {
            $filters['where'] .= ' AND h.id IN (' . $serv_where . ')';
        }

        $services = Asset_host_services::get_list($conn, $filters);

        return $services;
    }


    /**
    * Function get_properties
    *
    * This function returns the properties related to host
    *
    * @param object $conn    Database access object
    * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @access public
    *
    * @return array List of properties
    */
    public function get_properties($conn, $filters = array())
    {
        $q_where = "SELECT host_id FROM host_group_reference WHERE host_group_id = UNHEX('" . $this->id . "')";

        if (empty($filters['where']))
        {
            $filters['where'] = 'h_id IN ('.$q_where.')';
        }
        else
        {
            $filters['where'] .= ' AND h_id IN ('.$q_where.')';
        }

        $properties = Asset_host_properties::get_all($conn, $filters);

        return $properties;
    }


    /**
     * Function get_hids_status
     *
     * This function returns the status of HIDS agents than belongs to asset group
     *
     *   0 --> GRAY:   Not HIDS agents deployed
     *   1 --> RED:    Some HIDS agents deployed but not connected  (Never Connected)
     *   2 --> YELLOW: Some HIDS agents deployed but not all active
     *   3 --> GREEN:  All HIDS agents deployed and active
     *
     * @param object $conn Database access object
     *
     * @throws Av_exception If a connection error occurred
     *
     * @access public
     *
     * @return integer
     */
    public function get_hids_status($conn)
    {
        Ossim_db::check_connection($conn);

        $hids_status = 0;

        $number_of_assets = $this->get_num_host($conn);

        $query = "SELECT agent_status FROM hids_agents
            INNER JOIN host_group_reference ON hids_agents.host_id=host_group_reference.host_id WHERE host_group_id = UNHEX(?)";

        $params = array($this->get_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        if (!$rs->EOF)
        {
            $active_agents       = 0;
            $disconnected_agents = 0;
            $no_connected_agents = 0;

            while (!$rs->EOF)
            {
                switch($rs->fields['agent_status'])
                {
                    case 0:
                    case 1:
                        $no_connected_agents++;
                    break;

                    case 2:
                        $disconnected_agents++;
                    break;

                    case 3:
                    case 4:
                        $active_agents++;
                    break;

                    default:
                        $no_connected_agents++;
                }

                //It doesn't make sense to iterate until the end
                if ($active_agents > 0 && ($no_connected_agents > 0 || $disconnected_agents > 0))
                {
                    break;
                }

                $rs->MoveNext();
            }

            $number_of_agents = $no_connected_agents + $disconnected_agents + $active_agents;

            if ($number_of_agents == $number_of_assets && $active_agents == $number_of_assets)
            {
                $hids_status = 3;
            }
            else
            {
                if ($number_of_agents == $no_connected_agents)
                {
                    $hids_status = 1;
                }
                else
                {
                    $hids_status = 2;
                }
            }
        }

        return $hids_status;
    }


    /**
    * Function is_nagios_enabled
    *
    * This function returns true if group is monitored with Nagios
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @return boolean  TRUE if enabled
    */
    public function is_nagios_enabled($conn)
    {
        return Asset_group_scan::is_plugin_in_group($conn, $this->get_id(), 2007);
    }

    /**
     * Function get_availability
     *
     * This function returns the Nagios status based on the UP status percent of the group
     *
     * @param object $conn Database access object
     *
     * @return Array
     */
    public function get_availability($conn)
    {
        $total         = 0;
        $monitored_up  = 0;
        $not_monitored = 0;

        $sql = "SELECT ha.status FROM alienvault.host h
                LEFT JOIN alienvault.host_scan ha ON ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0
                WHERE h.id IN (SELECT host_id AS id FROM host_group_reference WHERE host_group_id = UNHEX(?))";

        $rs = $conn->Execute($sql, array($this->get_id()));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                //0 pending, 1 down, 2 up
                if ($rs->fields['status'] == '2')
                {
                    $monitored_up++;
                }

                if ($rs->fields['status'] == '0' || $rs->fields['status'] == NULL)
                {
                    $not_monitored++;
                }

                $total++;

                $rs->MoveNext();
            }
        }

        $percent            = ($total > 0) ? $monitored_up * 100 / $total : 0;
        $not_monitored_perc = ($total > 0) ? $not_monitored * 100 / $total : 0;


        if ($total == 0 || $not_monitored_perc == 100)
        {
            // Grey
            $availability_level = 0;
        }
        elseif ($percent >= 95)
        {
            // Green
            $availability_level = 1;
        }
        elseif ($percent >= 75)
        {
            // Yellow
            $availability_level = 2;
        }
        else
        {
            // Red
            $availability_level = 3;
        }

        $availability_value = floor($percent).'%';

        return array($availability_value, $availability_level);
    }

    /**
    * Function is_autodetected
    *
    * This function returns Automatic Asset Discovery configuration.
    * Hosts from asset group are scanned with NMAP scheduled task
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return boolean   TRUE if is autodecte
    */
    public function is_autodetected($conn)
    {
        Ossim_db::check_connection($conn);

        $networks = array();

        //Getting host sensors
        $sensors = $this->get_sensors($conn);
        $sensors = array_keys($sensors);

        $q_sensors = implode("'), UNHEX('", $sensors);
        $q_sensors = "UNHEX('" . $q_sensors . "')";


        //Getting Inventory Tasks
        $target_param = " AND task_sensor IN($q_sensors) AND task_enable = 1";
        $task_list    = Inventory::get_list($conn, '', 5, $target_param);


        if (count($task_list) > 0)
        {
            //Getting networks from inventory tasks
            foreach ($task_list as $task_data)
            {
                list($_nets)  = Util::nmap_without_excludes($task_data['task_params']);

                foreach ($_nets as $net)
                {
                    if (Asset_net::valid_cidr($net))
                    {
                        $net_range = Asset_net::expand_cidr($net, 'SHORT', 'LONG');

                        $networks[$net] = $net_range[$net];
                    }
                }
            }

            if (is_array($networks) && !empty($networks))
            {
                $all_are_autodetected = TRUE; // All IPs are autodetected
                $any_is_autodetected  = FALSE; // True if some IP is autodetected


                // Compare Ips and CIDRs
                $_list_data = $this->get_hosts($conn, '', array(), TRUE);
                $hosts      = $_list_data[0];
                $host_ips   = array();

                foreach ($hosts as $host)
                {
                    $ips = explode(',', $host[2]);

                    foreach ($ips as $ip)
                    {
                        $host_ips[trim($ip)]++;
                    }
                }

                $ips = array_keys($host_ips);

                foreach ($ips as $ip)
                {
                    $is_autodetected = FALSE;

                    $ip_long = Asset_host_ips::ip2ulong($ip);

                    foreach ($networks as $net)
                    {
                        if ($ip_long >= $net[0] && $ip_long <= $net[1])
                        {
                            $is_autodetected = TRUE;

                            break;
                        }
                    }

                    if ($is_autodetected == TRUE)
                    {
                        $any_is_autodetected = TRUE;
                    }
                    else
                    {
                        $all_are_autodetected = FALSE;
                    }
                }

                if ($any_is_autodetected == TRUE)
                {
                    return ($all_are_autodetected) ? 1 : 2; // GREEN / YELLOW
                }
            }
        }

        return 0; // RED
    }


    /**
    * Function get_history
    *
    * This function returns a filtered host group history list
    *
    * @param object $conn    Database access object
    * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param bool   $cache   [Optional] Use cached information
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return array
    */
    public function get_history($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $history = array();

        //Build SQL
        $q_where  = '';
        $q_select = 'login, date, action';

        if (!empty($filters['where']))
        {
            $q_where .= 'AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY ' . $filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_select = 'SQL_CALC_FOUND_ROWS login, date, action';
            $q_where .= ' LIMIT ' . $filters['limit'];
        }

        $params = array($this->get_id());
        $query  = "SELECT $q_select FROM host_group_history WHERE host_group_history.host_group_id = UNHEX(?) $q_where";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $history[] = $rs->fields;
            $rs->MoveNext();
        }

        return array($history, $total);
    }


    /**
    * Function save_history
    *
    * This function log any membership action into host_group_history
    *
    * @param object $conn    Database access object
    * @param string $action  new, add or remove
    * @param array  $assets  [Optional] List of membership uuid
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return boolean   False if not correct action supplied
    */
    public function save_history($conn, $action, $assets = array())
    {
        Ossim_db::check_connection($conn);

        // Action
        if ($action == 'new' || $action == 'update')
        {
            $msg = ($action == 'new') ? _('Asset Group Created') : _('Asset Group Updated');

            $query = 'REPLACE INTO host_group_history (host_group_id,date,login,action) VALUES (UNHEX(?), utc_timestamp(), ?, ?)';

            $params = array(
                $this->id,
                Session::get_session_user(),
                $msg
            );

            if ($conn->Execute($query, $params) === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

        }

        elseif ($action == 'add' || $action == 'remove')
        {
            $_msg = ($action == 'add') ? _('Added') : _('Removed');

            // add a membership
            foreach ($assets as $asset_id)
            {
                $hostname = Asset_host::get_name_by_id($conn, $asset_id);
                $hostip   = Asset_host_ips::get_ips_to_string($conn, $asset_id);
                $msg      = $hostname . ' [' . $hostip . '] - ' . $_msg;

                $query = 'REPLACE INTO host_group_history (host_group_id,date,login,action) VALUES (UNHEX(?), utc_timestamp(), ?, ?)';

                $params = array(
                    $this->id,
                    Session::get_session_user(),
                    $msg
                );

                if ($conn->Execute($query, $params) === FALSE)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
            }
        }

        else
        {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Function get_plugins
     *
     * This function returns the plugins related to network
     *
     * @param object   $conn       Database access object
     * @param bool     $edit_mode  [Optional] Flag to know if return empty assets as well
     * @param string   $sensor     [Optional] Show only the plugins from this sensor
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array List of plugins
     */
    public function get_plugins($conn, $edit_mode = FALSE, $sensor_id = '')
    {
        $tables  = '';
        $filters = array();
        $sensors = $this->get_sensors($conn);

        // Get only plugins from one specified sensor
        if (valid_hex32($sensor_id))
        {
            $tables           = ', host_sensor_reference hsr';
            $filters['where'] = "hsr.host_id = host.id AND hsr.sensor_id = UNHEX('$sensor_id')";

            $_sensor_aux = $sensors[$sensor_id];
            $sensors     = array($sensor_id => $_sensor_aux);
        }


        $_asset_data = $this->get_hosts($conn, $tables, $filters);
        $assets      = $_asset_data[0];

        return Asset_host::get_plugins_by_sensor($conn, $sensors, $assets, $edit_mode);
    }


    /**
     * Function get_events
     *
     * This function returns the events related to asset group
     *
     * @param object $conn Database access object
     * @param number $from       [Optional]
     * @param number $maxrows    [Optional]
     * @param string $order      [Optional]
     * @param string $torder     [Optional]
     * @param string $search_str [Optional]
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array List of events
     */
    public function get_events($conn, $from = 0, $maxrows = 50, $order = 'timestamp', $torder = 'DESC', $search_str = '')
    {
        Ossim_db::check_connection($conn);

        $siem  = new Siem(TRUE); // Perms byPass TRUE

        // Create temporary table with inner assets
        $_tmp_table   = Util::create_tmp_table($siem->conn);

        // Using perms
        $_perms_where = (Session::get_host_where() != '') ? ' AND host_id IN ('.Session::get_host_where().')' : '';
        $insert_query = 'REPLACE INTO '.$_tmp_table.' SELECT host_id FROM alienvault.host_group_reference WHERE host_group_id = UNHEX(?) '.$_perms_where;

        $rs = $siem->conn->Execute($insert_query, array($this->id));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $siem->conn->ErrorMsg());
        }

        if ($search_str != '')
        {
            $search_str = escape_sql($search_str, $conn);
        }

        return $siem->get_events_sp($_tmp_table, 'group', $from, $maxrows, "$order $torder", $search_str);
    }


    /**
     * Function get_events_status
     *
     * This function returns the event count and level related to asset group
     *
     *
     * @param object $conn Database access object [Necessary only in asset_net]
     *
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array Event Count and Event Level values to details info
     */
    public function get_events_status($conn)
    {
        $siem         = new Siem(TRUE); // Perms byPass TRUE

        // Create temporary table with inner assets
        $_tmp_table   = Util::create_tmp_table($siem->conn);

        // Using perms
        $_perms_where = (Session::get_host_where() != '') ? ' AND host_id IN ('.Session::get_host_where().')' : '';
        $insert_query = 'REPLACE INTO '.$_tmp_table.' SELECT host_id FROM alienvault.host_group_reference WHERE host_group_id = UNHEX(?) '.$_perms_where;

        $rs = $siem->conn->Execute($insert_query, array($this->id));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $siem->conn->ErrorMsg());
        }

        // Get Total
        $siem->add_criteria('src_host', $_tmp_table.'.id');
        $event_count_src = $siem->get_events_total();

        $siem->clear_criteria();
        $siem->add_criteria('dst_host', $_tmp_table.'.id');
        $event_count_dst = $siem->get_events_total();

        $siem->clear_criteria();
        $siem->add_criteria('src_host', $_tmp_table.'.id');
        $siem->add_criteria('dst_host', $_tmp_table.'.id');
        $event_count = $event_count_src + $event_count_dst - $siem->get_events_total();

        $event_level = ($event_count > 0) ? 1 : 0;

        // Total with risk = 1
        $siem->clear_criteria();
        $siem->add_criteria(array('src_host', 'dst_host'), $_tmp_table.'.id');
        $siem->add_criteria('ossim_risk_a', 1);

        $event_count_medium = $siem->get_events_total();

        // Total with risk > 1
        $siem->clear_criteria();
        $siem->add_criteria(array('src_host', 'dst_host'), $_tmp_table.'.id');
        $siem->add_criteria('ossim_risk_a', 1, '>');

        $event_count_high = $siem->get_events_total();

        // Calculate level
        if ($event_count_high > 0)
        {
            $event_level = 3;
        }
        elseif ($event_count_medium > 0)
        {
            $event_level = 2;
        }

        return array($event_count, $event_level);
    }


    /**
     * Function get_suggestions
     *
     * This function returns the suggestions messages related to the group
     *
     * @param array   $filters   Filters to get the messages from the API
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array List of messages and total
     */
    public function get_suggestions($filters = array(), $pagination = array())
    {
        $status = new System_notifications();

        $filters['component_id'] = Util::uuid_format($this->id);

        if (empty($filters['level']))
        {
            $filters['level'] = 'info,warning,error';
        }

        if (empty($filters['order_by']))
        {
            $filters['order_by'] = 'creation_time';
        }

        if (empty($filters['order_desc']))
        {
            $filters['order_desc'] = 'false';
        }

        return $status->get_status_messages($filters, $pagination);
    }


    /**
     * Function get_all_locations
     *
     * This function returns the location of all the members
     *
     * @param object $conn    Database access object
     *
     * @access public
     * @return array
     */
    public function get_all_locations($conn)
    {
        Ossim_db::check_connection($conn);

        $locations   = array();
        $perms_where = '';

        $host_where = Session::get_host_where();

        if ($host_where != '')
        {
            $perms_where = " AND hgr.host_id in ($host_where)";
        }

        ;
        $query = "SELECT HEX(id) as id, h.hostname, h.lat, h.lon FROM host h, host_group_reference hgr
                    WHERE h.id=hgr.host_id AND hgr.host_group_id=UNHEX(?) AND h.lat IS NOT NULL AND h.lon IS NOT NULL $perms_where";

        $params = array($this->get_id());
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $lon = explode(';', $rs->fields['lon']);
            $locations[$rs->fields['id']] = array(
                'name' => $rs->fields['hostname'],
                'lat'  => $rs->fields['lat'],
                'lon'  => $lon[0],
                'zoom' => $lon[1]
            );

            $rs->MoveNext();
        }

        return $locations;

    }

    /************************************************************************************************/
    /************************************************************************************************/
    /************************************    STATIC FUNCTIONS    ************************************/
    /************************************************************************************************/
    /************************************************************************************************/


    /**
    * Function bulk_delete
    *
    * This function deletes all groups in database filtered by optional query search
    *
    * @param object $conn     Database access object
    * @param array  $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return boolean  TRUE if correct deletion
    */
    public static function bulk_delete($conn, $filters = array())
    {
        Ossim_db::check_connection($conn);

        Util::disable_perm_triggers($conn, TRUE);

        // Create tmp table
        $tmp_table = Util::create_tmp_table($conn, "group_id binary(16) NOT NULL, PRIMARY KEY (group_id)");
        $session   = session_id();

        //Populate tmp table adding filtered groups which are not included in policies
        $join    = 'LEFT JOIN policy_host_group_reference phgr ON phgr.host_group_id = g.id';
        $q_where = 'phgr.host_group_id IS NULL';

        //Group Selected
        $join    .= ', user_component_filter uc';
        $q_where .= " AND uc.asset_id = g.id AND uc.asset_type='group' AND uc.session_id = '$session'";

        $query = ossim_query("INSERT INTO $tmp_table (group_id) SELECT id
            FROM host_group g $join WHERE $q_where");

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Check if there are groups belong some policies
        $tables = ', policy_host_group_reference phgr';

        $_filters = array(
            'where' => 'phgr.host_group_id = g.id',
            'limit' => 1
        );

        if ($filters['where'] != '')
        {
            $_filters['where'] .= ' AND (' . $filters['where'] . ')';
        }

        $_group_list = self::get_list($conn, $tables, $_filters);

        $groups_belong_policies = ($_group_list[1] > 0) ? TRUE : FALSE;

        // Delete sequence
        // Begin transaction
        $conn->StartTrans();


        //Delete statements (Queries to delete)
        $queries   = array();
        $queries[] = "DELETE g.* FROM host_group g,               $tmp_table f WHERE f.group_id = g.id";
        $queries[] = "DELETE g.* FROM repository_relationships g, $tmp_table f WHERE f.group_id = UNHEX(g.keyname)";
        $queries[] = "DELETE g.* FROM host_group_reference g,     $tmp_table f WHERE f.group_id = g.host_group_id";
        $queries[] = "DELETE g.* FROM bp_member_status g,         $tmp_table f WHERE f.group_id = g.member_id";
        $queries[] = "DELETE g.* FROM bp_asset_member g,          $tmp_table f WHERE f.group_id = g.member AND g.type='host_group'";
        $queries[] = "DELETE g.* FROM risk_indicators g,          $tmp_table f WHERE f.group_id = UNHEX(g.type_name) AND (g.type='hostgroup' OR g.type='host_group')";
        $queries[] = "DELETE g.* FROM host_group_history g,       $tmp_table f WHERE f.group_id = g.host_group_id";
        $queries[] = "DELETE g.* FROM host_group_scan g,          $tmp_table f WHERE f.group_id = g.host_group_id";

        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Log action
            $infolog = array(_('Policy - Asset Groups deleted massively'));
            Log_action::log(92, $infolog);


            Util::disable_perm_triggers($conn, FALSE);

            Filter_list::clean_selection($conn, 'group');

            Util::memcacheFlush();

            // Groups belong a policy
            if ($groups_belong_policies == TRUE)
            {
                $exp_msg = _('Sorry, some groups could not be deleted because they belong to a policy');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        else
        {
            $exp_msg = _('Error! Groups could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * Function get_list
    *
    * Returns a list fo groups
    *
    * @param object   $conn    Database access object
    * @param string   $tables  [Optional] Table list
    * @param array    $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache   [Optional] Use cached information
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return array   List fo host groups
    */
    public static function get_list($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Build SQL
        $perms_where = Asset_host::get_perms_where('h.', TRUE);

        $q_select = '';
        $q_where  = '';
        $q_order  = '';
        $q_limit  = '';
        $p_tables = array();

        if (!empty($filters['where']))
        {
            $q_where .= 'AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_order = ' ORDER BY ' . $filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_select = 'SQL_CALC_FOUND_ROWS';
            $q_limit  = ' LIMIT ' . $filters['limit'];
        }

        //This is to avoid repeated tables
        $tables   = trim(preg_replace('/\s*,\s*/', ',', $tables));
        $tables   = array_filter(explode(',', $tables));
        //We need to copy the table for the first query
        $p_tables = $tables;
        
        /* Building the query */
        if ($perms_where != '')
        {
            //Permissions only appears in the first query
            $p_tables[]  = 'host h';
            $p_tables[]  = 'host_group_reference hr';
            $perms_where = ' AND h.id = hr.host_id AND g.id = hr.host_group_id ' . $perms_where;
        }

        //Formating tables to mysql string
        //Tables for first query in the union
        $p_tables = array_unique($p_tables);
        $p_tables = implode(', ', $p_tables);
        $p_tables = ($p_tables == '') ? '' : $p_tables . ', ';
        //Tables for second query in the union
        $tables   = array_unique($tables);
        $tables   = implode(', ', $tables);
        $tables   = ($tables == '') ? '' : $tables . ', ';
                    
        $query = "
        SELECT $q_select * FROM
        (
            SELECT DISTINCT g.*, HEX(g.id) AS g_id, HEX(g.ctx) AS g_ctx
            FROM $p_tables host_group g
            WHERE 1 $perms_where $q_where
            UNION
            SELECT DISTINCT g.*, HEX(g.id) AS g_id, HEX(g.ctx) AS g_ctx
            FROM $tables host_group g
            LEFT JOIN host_group_reference hre ON g.id = hre.host_group_id
            WHERE hre.host_group_id IS NULL $q_where
        ) AS g $q_order $q_limit";
                    
        $query = ossim_query($query);

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        $list = array();

        while (!$rs->EOF)
        {
            $_group = new Asset_group($rs->fields['g_id']);

            $_group->set_ctx($rs->fields['g_ctx']);
            $_group->set_name($rs->fields['name']);
            $_group->set_descr($rs->fields['descr']);
            $_group->set_owner($rs->fields['owner']);

            $list[$rs->fields['g_id']] = $_group;

            $rs->MoveNext();
        }

        return array($list, $total);
    }


    /**
     * Function is_allowed
     *
     * This function checks if the group exists and it is allowed
     *
     * @param object $conn Database access object
     * @param string $id   Group ID
     *
     * @access public
     * @return bool
     * @throws Av_exception If a connection error occurred
     */
    public static function is_allowed($conn, $id)
    {
        Ossim_db::check_connection($conn);

        //So far we always return true to avoid errors on polymorphic calls in the asset detail.
        return TRUE;
    }


    /**
    * Function get_objcet
    *
    * This function returns an asset group object
    *
    * @param object  $conn  Database access object
    * @param string  $id    Asset group ID
    * @param boolean $cache [Optional] Use cached information
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return Asset_group
    */
    public static function get_object($conn, $id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host = NULL;

        $params = array($id);
        $query  = 'SELECT HEX(id) AS id FROM host_group WHERE id = UNHEX(?)';

        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $host = new self($id);
            $host->load_from_db($conn);
        }

        return $host;
    }


    /**
    * Function is_in_db
    *
    * This function checks if asset group exists into database
    *
    * @param object $conn Database access object
    * @param string $id   Asset group ID
    *
    * @accesa public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return boolean  TRUE if group is in DB
    */
    public static function is_in_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $res = FALSE;

        if (!valid_hex32($id))
        {
            return $res;
        }

        $query  = 'SELECT count(*) AS found FROM host_group WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }

        return $res;
    }


    /**
    * Function is_host_in_group
    *
    * This function checks if host exists in group
    *
    * @param object $conn     Database access object
    * @param string $host_id  Host identifier
    * @param string $group_id Host group identifier
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return boolean   TRUE if host is in group
    */
    public static function is_host_in_group($conn, $host_id, $group_id = '')
    {
        Ossim_db::check_connection($conn);

        $params = array($host_id);

        $query = "SELECT host_group_id from host_group_reference where host_id = UNHEX(?)";

        if ($group_id != '')
        {
            $query .= " AND host_group_id = UNHEX(?)";
            $params[] = $group_id;
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
     * Function is_group_name_duplicated
     *
     * This function check if the given group name for a group is duplicated
     *
     * @param object $conn    DB connection object
     * @param string $id      Group ID
     * @param string $name    Group Name
     *
     * @access public
     *
     * @return boolean
     */
    public static function is_group_name_duplicated($conn, $id, $name)
    {
        Ossim_db::check_connection($conn);

        $num_groups = self::get_id_by_name($conn, $name);

        unset($num_groups[$id]);

        if (count($num_groups) > 0)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * Function get_name_by_id
    *
    * This function returns host group name by id
    *
    * @param object $conn         Database access object
    * @param string $id           Host group identifier
    * @param string $label_empty  Optional name if the field name is empty
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return string   Host group name
    */
    public static function get_name_by_id($conn, $id, $label_empty = '')
    {
        Ossim_db::check_connection($conn);

        $query  = "SELECT name FROM host_group WHERE id = UNHEX(?)";
        $params = array($id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (empty($rs->fields['name']))
            {
                return ($label_empty == '') ? _('Unknown') : $label_empty;
            }
            else
            {
                return $rs->fields['name'];
            }
        }
    }


    /**
    * Function get_id_by_name
    *
    * This function returns the Host group ID by name
    *
    * @param object $conn Database access object
    * @param string $name Host name
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return array   array[group_id] => group_id
    */
    public static function get_id_by_name($conn, $name)
    {
        Ossim_db::check_connection($conn);

        $hg_ids = array();

        $params = array($name);

        $query = 'SELECT HEX(id) AS id FROM host_group WHERE name = ?';

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $hg_ids[$rs->fields['id']] = $rs->fields['id'];

            $rs->MoveNext();
        }

        return $hg_ids;
    }


    /**
    * Function get_alarms
    *
    * This function returns the alarms related to the asset group
    *
    * @param object  $conn      Database access object
    * @param string  $id        Host uuid (Hex value)
    * @param integer $from      From offset
    * @param integer $max       Maximum elements per page
    * @param string  $date_from [Optional] Date from filter
    * @param string  $date_to   [Optional] Date to filter
    * @param string  $filter    [Optional] SQL query
    * @param string  $order     [Optional] SQL order statement
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return array  array[0] -> List of Alarm objects; array[1] -> Number of alarms
    */
    public static function get_alarms($conn, $id, $from, $max, $date_from = '', $date_to = '', $filter = '', $order = 'a.timestamp DESC')
    {
        Ossim_db::check_connection($conn);

        $criteria = array(
            'src_ip'        => '',
            'dst_ip'        => '',
            'hide_closed'   => 1,
            'order'         => "ORDER BY $order",
            'inf'           => $from,
            'sup'           => $from + $max,
            'date_from'     => $date_from,
            'date_to'       => $date_to,
            'query'         => $filter,
            'directive_id'  => '',
            'intent'        => 0,
            'sensor'        => '',
            'tag'           => '',
            'num_events'    => '',
            'num_events_op' => 0,
            'plugin_id'     => '',
            'plugin_sid'    => '',
            'ctx'           => '',
            'host'          => '',
            'net'           => '',
            'host_group'    => $id
        );

        return Alarm::get_list($conn, $criteria);
    }

    /**
     * Function get_highest_risk_alarms
     *
     * This function returns the highest alarm risk from the given asset group
     *
     * @param object $conn Database access object
     * @param string $id   Asset Group ID
     *
     * @return Hash
     */
    public static function get_highest_risk_alarms($conn, $id)
    {
        $criteria = array('host_group' => $id);

        return Alarm::get_highest_risk_by_asset($conn, $criteria);
    }
}

/* End of file asset_group.inc */
/* Location: ../include/classes/asset_group.inc */
