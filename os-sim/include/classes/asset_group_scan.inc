<?php
/**
* Asset_group_scan.inc
*
* File Asset_group_scan.inc is used to:
*   - To manage group plugins (Nagios)
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
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Asset_group_scan Class
*
* Class for managing group plugins
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Asset_group_scan
{
    /**
    * Group ID
    *
    * @var string
    * @access private
    */
    private $group_id;

    /**
    * Plugins associated with the group
    *
    * @var array
    * @access private
    */
    private $plugins;



    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $group_id  Group ID
    */
    public function __construct($group_id, $plugins = [])
    {
        $this->set_group_id($group_id);
        $this->plugins = $plugins;
    }


    /**
    * This function returns the group ID
    *
    * @return string
    */
    public function get_group_id()
    {
        return $this->group_id;
    }


    /**
    * This function returns the plugins associated with the group
    *
    * @return array
    */
    public function get_plugins()
    {
        return $this->plugins;
    }


    /**
    * This function sets the group ID
    *
    * @param string $group_id  Group ID
    *
    * @throws Exception  If group ID is not valid
    *
    * @return void
    */
    public function set_group_id($group_id)
    {
        if (!empty($group_id) && security_class::valid_hex32($group_id))
        {
            $this->group_id = strtoupper($group_id);
        }
        else
        {
            $exp_msg = _('Error! Group ID is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
    * This function sets group plugins
    *
    * @param array $plugins  Group plugins
    *
    * @return void
    */
    public function set_plugins($plugins)
    {
        $this->plugins = array();

        if (is_array($plugins) && !empty($plugins))
        {
            foreach($plugins as $p_data)
            {
                $this->add_new($p_data['plugin_id'], $p_data['plugin_sid']);
            }
        }
    }


    /**
    * This function sets the group plugins from database
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If group ID doesn't exists in the System or there is a connection error
    *
    * @return void
    */
    public function load_from_db($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);


        $query  = 'SELECT plugin_id, plugin_sid FROM host_group_scan WHERE host_group_id = UNHEX(?)';
        $params = array($this->group_id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $this->add_new($rs->fields['plugin_id'], $rs->fields['plugin_sid']);

            $rs->MoveNext();
        }
    }


    /**
    * This function adds a new plugin to group
    *
    * @param integer  $plugin_id    Plugin ID
    * @param integer  $plugin_sid  [Optional] Datasource
    *
    * @return boolean
    */
    public function add_new($plugin_id, $plugin_sid = 0)
    {
        $plugin_id   = intval($plugin_id);
        $plugin_sid  = intval($plugin_sid);

        if ($plugin_id > 0 && $plugin_sid >= 0)
        {
            $this->plugins[$plugin_id][$plugin_sid] = $plugin_id.'-'.$plugin_sid;

            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function deletes a plugin from the group
    *
    * @param integer  $plugin_id   Plugin ID
    * @param integer  $plugin_sid  [Optional] Datasource
    *
    * @return boolean
    */
    public function delete($plugin_id, $plugin_sid = 0)
    {
        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);

        if (!empty($this->plugins[$plugin_id][$plugin_sid]))
        {
             unset($this->plugins[$plugin_id][$plugin_sid]);

             if (!count($this->plugins[$plugin_id]))
             {
                 unset($this->plugins[$plugin_id]);
             }

             return TRUE;
        }

        return FALSE;
    }


    /**
    * This function saves all plugins into database
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public function save_all_in_db($conn)
    {
        Ossim_db::check_connection($conn);

        $enabled_nagios = $this->is_plugin_in_group_obj($conn,  2007);


        //Begin transaction
        $conn->StartTrans();

        $this->delete_all_from_db_obj($conn);

        foreach($this->plugins as $plugin_id => $plugin_sids)
        {
            foreach($plugin_sids as $plugin_sid => $_aux)
            {
                $query  = 'REPLACE INTO host_group_scan (host_group_id, plugin_id, plugin_sid) VALUES (UNHEX(?), ?, ?)';
                $params = array($this->group_id, $plugin_id, $plugin_sid);

                $conn->Execute($query, $params);
            }
        }

        //Finish transaction
        if (!$conn->CompleteTrans())
        {
            $exp_msg = _('Error! Group plugins could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else
        {
            foreach($this->plugins as $plugin_id => $plugin_sids)
            {
                $infolog = array($this->group_id . ':' . $plugin_id);

                Log_action::log(10, $infolog);
            }
        }

        /*
        Enable Nagios
            - Case 1: Nagios must be enabled now
            - Case 2: Nagios was enabled and the transaction wasn't completed.
                      We have to enable Nagios again
        */
        if (array_key_exists(2007, $this->plugins) || $enabled_nagios == TRUE)
        {
            $this->add_group_to_nagios_obj($conn);
        }

        return TRUE;
    }



    /*************************************************
     **************** Static functions ***************
     *************************************************/



    /**
    * This function adds the group to Nagios
    *
    * @param object  $conn       Database connection
    * @param string  $group_id   Group ID
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    private static function add_group_to_nagios($conn, $group_id)
    {
        $obj = new Asset_group_scan($group_id);
        return $obj->add_group_to_nagios_obj($conn);
    }

    public function add_group_to_nagios_obj($conn)
    {
        $group_id = $this->get_group_id();
        $q = new Nagios_adm();

        $asset_group     = Asset_group::get_object($conn, $group_id);
        $_hosts_data_aux = $asset_group->get_hosts($conn, '', array(), TRUE);
        $hosts           = array_keys($_hosts_data_aux[0]);

        foreach ($hosts as $host_id)
        {
            $query  = 'REPLACE INTO host_scan (host_id, plugin_id, plugin_sid) VALUES (UNHEX(?), 2007, 0)';
            $params = array($host_id);
            $rs     = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        $q->addNagiosHostGroup(new Nagios_group($group_id, $hosts));
        $q->close();
    }


    /**
    * This function deletes the group from Nagios
    *
    * @param string  $group_id   Group ID
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    private static function delete_group_from_nagios($conn, $group_id)
    {
        $obj = new Asset_group_scan($group_id);
        return $obj->delete_group_from_nagios_obj($conn);
    }

    public function delete_group_from_nagios_obj($conn)
    {
        $group_id = $this->get_group_id();
        $q = new Nagios_adm();

        $asset_group     = Asset_group::get_object($conn, $group_id);
        $_hosts_data_aux = $asset_group->get_hosts($conn, '', array(), TRUE);
        $hosts           = array_keys($_hosts_data_aux[0]);

        foreach ($hosts as $host_id)
        {
            $query  = 'DELETE FROM host_scan WHERE host_id = UNHEX(?) AND plugin_id = 2007 AND plugin_sid = 0';
            $params = array($host_id);
            $rs     = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        $q->delNagiosHostGroup(new Nagios_group($group_id, array()));
        $q->close();
    }


    /**
    * This function saves one plugin into database
    *
    * @param object   $conn        Database access object
    * @param string   $group_id    Group ID
    * @param integer  $plugin_id   Plugin ID
    * @param integer  $plugin_sid  [Optional] Datasource
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function save_plugin_in_db($conn, $group_id, $plugin_id, $plugin_sid = 0)
    {
        $obj = new Asset_group_scan($group_id);
        return $obj->save_plugin_in_db_obj($conn, $plugin_id, $plugin_sid);
    }

    public function save_plugin_in_db_obj($conn, $plugin_id, $plugin_sid = 0)
    {
        Ossim_db::check_connection($conn);

        $group_id = $this->get_group_id();

        if ($this->add_new($plugin_id, $plugin_sid) == TRUE)
        {

            $query  = 'REPLACE INTO host_group_scan (host_group_id, plugin_id, plugin_sid) VALUES (UNHEX(?), ?, ?)';
            $params = array($group_id, $plugin_id, $plugin_sid);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $infolog = array($group_id . ':' . $plugin_id);
            Log_action::log(10, $infolog);

            //Enable Nagios
            if ($plugin_id == 2007)
            {
                $this->add_group_to_nagios_obj($conn);
            }
        }

        return TRUE;
    }


    /**
    * This function deletes one plugin from database
    *
    * @param object   $conn        Database access object
    * @param string   $group_id    Group ID
    * @param integer  $plugin_id   Plugin ID
    * @param integer  $plugin_sid  [Optional] Datasource
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_plugin_from_db($conn, $group_id, $plugin_id, $plugin_sid = 0)
    {
        $obj = new Asset_group_scan($group_id);
        return $obj->delete_plugin_from_db_obj($conn, $plugin_id, $plugin_sid);
    }

    public function delete_plugin_from_db_obj($conn, $plugin_id, $plugin_sid = 0)
    {
        Ossim_db::check_connection($conn);
        $group_id = $this->get_group_id();

        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);

        $query  = 'DELETE FROM host_group_scan WHERE host_group_id = UNHEX(?)
            AND plugin_id = ? AND plugin_sid = ?';

        $params = array ($group_id, $plugin_id, $plugin_sid);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Disable Nagios
        if ($plugin_id == 2007)
        {
            $this->delete_group_from_nagios_obj($conn);
        }

        return TRUE;
    }


    /**
    * This function deletes all plugins from database
    *
    * @param object  $conn      Database access object
    * @param string  $group_id  Group ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_all_from_db($conn, $group_id)
    {
        $obj = new Asset_group_scan($group_id);
        return $obj->delete_all_from_db_obj($conn);
    }

    public function delete_all_from_db_obj($conn)
    {
        Ossim_db::check_connection($conn);

        $group_id = $this->get_group_id();

        $enabled_nagios = $this->is_plugin_in_group_obj($conn,2007);

        //Delete all plugins
        $query  = 'DELETE FROM host_group_scan WHERE host_group_id = UNHEX(?)';
        $params = array($group_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Disable Nagios
        if ($enabled_nagios == TRUE)
        {
            $this->delete_group_from_nagios_obj($conn);
        }

        return TRUE;
    }

    /**
     * This function enables Nagios to bulk selected assets
     *
     * @param object   $conn        Database access object
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function bulk_enable_monitoring($conn)
    {
        Ossim_db::check_connection($conn);

        $session = session_id();

        $ids     = array();

        $query   = "SELECT HEX(asset_id) AS group_id FROM user_component_filter
                    WHERE asset_type = 'group' AND session_id = ?
                    AND asset_id NOT IN (SELECT host_group_id FROM host_group_scan)";

        $rs = $conn->Execute($query, array($session));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ids[] = $rs->fields['group_id'];

            $rs->MoveNext();
        }

        if (count($ids) < 1)
        {
            return FALSE;
        }

        foreach ($ids as $id)
        {
            self::save_plugin_in_db($conn, $id, 2007);
        }

        return TRUE;
    }


    /**
     * This function disables Nagios to bulk selected assets
     *
     * @param object   $conn        Database access object
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function bulk_disable_monitoring($conn)
    {
        Ossim_db::check_connection($conn);

        $session = session_id();
        $ids     = array();

        $query   = "SELECT HEX(asset_id) AS group_id FROM user_component_filter
                    WHERE asset_type = 'group' AND session_id = ?
                    AND asset_id IN (SELECT host_group_id FROM host_group_scan)";

        $rs = $conn->Execute($query, array($session));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ids[] = $rs->fields['group_id'];

            $rs->MoveNext();
        }

        if (empty($ids))
        {
            $assetIds  =  self::get_enable_asset_AM_ids($conn);

            if (empty($assetIds)) {
                return FALSE;
            }

            foreach ($assetIds as $id)
            {
                Asset_host_scan::delete_plugin_from_db($conn, $id, 2007);
            }
            return TRUE;
        }

        foreach ($ids as $id)
        {
            self::delete_plugin_from_db($conn, $id, 2007);
        }

        return TRUE;
    }


    /**
    * This function checks if plugin ($plugin_id, $plugin_sid) is enabled in the group
    *
    * @param object   $conn         Database access object
    * @param string   $group_id     Group ID
    * @param integer  $plugin_id    Plugin ID
    * @param integer  $plugin_sid   [Optional] Datasource
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_plugin_in_group($conn, $group_id, $plugin_id, $plugin_sid = 0)
    {
        $obj = new Asset_group_scan($group_id);
        return $obj->is_plugin_in_group_obj($conn, $plugin_id, $plugin_sid);
    }

    public function is_plugin_in_group_obj($conn, $plugin_id, $plugin_sid = 0)
    {
        Ossim_db::check_connection($conn);
        $group_id = $this->get_group_id();

        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);

        if ($plugin_id > 0)
        {
            $query  = 'SELECT HEX(host_group_id) as host_group_id FROM host_group_scan
                WHERE host_group_id = UNHEX(?)
                AND plugin_id = ? AND plugin_sid = ?';

            $params = array($group_id, $plugin_id, $plugin_sid);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            if ($rs->fields['host_group_id'] == $group_id)
            {
                return TRUE;
            }
        }

        return FALSE;
    }

    public  static  function  is_group_has_unique_assets ($conn) {

        Ossim_db::check_connection($conn);

        $session = session_id();

        $query = "SELECT count(host_group_id) AS cnt FROM host_group_reference 
                      WHERE host_id IN (SELECT host_id FROM host_group_reference WHERE hex(host_group_id) IN (SELECT HEX(asset_id) AS group_id FROM user_component_filter
                      WHERE asset_type = 'group'  AND session_id = ?)) GROUP  BY  host_id HAVING cnt > 1;";
        $rs = $conn->Execute($query, array($session));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return (isset($rs->fields['cnt'])) ?  FALSE :  TRUE;
    }

    public  static  function get_enable_asset_AM_ids($conn){

        Ossim_db::check_connection($conn);

        $session = session_id();
        $ids     = array();

        $query   = "SELECT  hex(host_id) as asset_id FROM host_scan WHERE  hex(host_id) IN 
                        (select  hex(host_id ) as  host_id  from host_group_reference 
                        WHERE hex(host_group_id)  = (SELECT HEX(asset_id) AS group_id FROM user_component_filter
                        WHERE asset_type = 'group' AND session_id = ? ))";

        $rs = $conn->Execute($query, array($session));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ids[] = $rs->fields['asset_id'];

            $rs->MoveNext();
        }
        
        return $ids;
    }
}

/* End of file asset_group_scan.inc */
/* Location: ../include/classes/asset_group_scan.inc */ 
