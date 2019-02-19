<?php
/**
 * asset_host_properties.inc
 *
 * File asset_host_properties.inc is used to:
 *   - To manage host properties
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


/**
 * Asset_host_properties Class
 *
 * Class for managing host properties
 *
 * @package    ossim-framework\Asset
 * @autor      AlienVault INC
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Asset_host_properties
{
    /**
     * Host ID
     *
     * @var    string $host_id
     * @access private
     */
    private $host_id;

    /**
     * All host source references
     *
     * @var    array $host_sources
     * @access private
     */
    private $host_sources;

    /**
     * All property types
     *
     * @var    array $property_types
     * @access private
     */
    private $property_types;

    /**
     * Properties associated with the host (Users, Operating Systems, Departments, Memory, ...)
     *
     * @var    array $properties
     * @access private
     */
    private $properties;


    /**
     * Function __construct
     *
     * This function sets up the class
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     *
     * @access public
     */
    public function __construct($conn, $host_id)
    {
        $this->set_host_id($host_id);

        $p_obj                = new Properties($conn);
        $this->property_types = $p_obj->get_properties();

        $s_obj              = new Host_Sources($conn);
        $this->host_sources = $s_obj->get_host_sources();

        $this->properties = array();
    }


    /**
     * Function get_host_id
     *
     * This function returns the host ID
     *
     * @access public
     * @return string Host ID
     */
    public function get_host_id()
    {
        return $this->host_id;
    }


    /**
     * Function get_properties
     *
     * This function returns the host properties
     *
     * @access public
     * @return array  Host properties list
     */
    public function get_properties()
    {
        return $this->properties;
    }


    /**
     * Function get_property
     *
     * This function returns the property $p_type
     *
     * @param int $p_type Property Type
     *
     * @access public
     * @return array  Property info
     */
    public function get_property($p_type)
    {
        $property = array();

        if (array_key_exists($p_type, $this->property_types))
        {
            $property = $this->properties[$p_type];
        }

        return $property;
    }


    /**
     * Function get_host_sources
     *
     * This function returns all source references
     *
     * @access public
     * @return array  Host sources list
     */
    public function get_host_sources()
    {
        return $this->host_sources;
    }


    /**
     * Function set_host_id
     *
     * This function sets the host ID
     *
     * @param string $host_id Host ID
     *
     * @access public
     * @return void
     * @throws Av_exception If host ID is not valid
     */
    public function set_host_id($host_id)
    {
        if (!empty($host_id) && valid_hex32($host_id))
        {
            $this->host_id = strtoupper($host_id);
        }
        else
        {
            $exp_msg = _('Error! Asset ID is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
     * Function load_from_db
     *
     * This function sets the host properties data from database
     *
     * @param object $conn  Database access object
     * @param bool   $cache [Optional] Use cached information
     *
     * @access public
     * @return void
     * @throws Av_exception If host ID doesn't exists in the System or there is a connection error
     */
    public function load_from_db($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $query = 'SELECT *
            FROM host_properties
            WHERE host_id = UNHEX(?)
            ORDER BY property_ref, source_id, last_modified DESC';

        $params = array($this->host_id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $values = self::decode_values($rs->fields['value']);

            foreach ($values as $value)
            {
                $this->add_new($rs->fields['property_ref'], $value, '', $rs->fields['source_id']);
            }

            $rs->MoveNext();
        }
    }


    /**
     * Function add_new
     *
     * This function adds a new host property
     *
     * @param int    $p_type    Property Type
     * @param string $value     Property value
     * @param string $extra     [Optional] Extra information
     * @param int    $source_id [Optional] Source ID
     *
     * @access public
     * @return bool             TRUE if host property is correctly added
     */
    public function add_new($p_type, $value, $extra = '', $source_id = 1)
    {
        $p_type    = intval($p_type);
        $source_id = intval($source_id);

        $cnd_1 = array_key_exists($p_type, $this->property_types);
        $cnd_2 = array_key_exists($source_id, $this->host_sources);

        if ($cnd_1 && $cnd_2)
        {
            $this->properties[$p_type][] = array(
                'extra'       => $extra,
                'name'        => $this->property_types[$p_type]['name'],
                'description' => $this->property_types[$p_type]['description'],
                'source'      => array(
                    'id'      => $source_id,
                    'name'    => $this->host_sources[$source_id]['name']
                ),
                'value'       => $value
            );

            return TRUE;
        }

        return FALSE;
    }


    /**
     * Function delete
     *
     * This function deletes a host property
     *
     * @param int    $p_type Property Type
     * @param string $value  [Optional] Property value
     *
     * @access pulbic
     * @return boolean    TRUE if property is correctly deleted
     */
    public function delete($p_type, $value = '')
    {
        $p_type = intval($p_type);

        if (array_key_exists($p_type, $this->properties))
        {
            if ($value != '')
            {
                $i = -1;
                foreach ($this->properties[$p_type] as $i => $arr)
                {
                    if ($arr['value'] == $value)
                    {
                        break;
                    }
                }
                if ($i>=0)
                {
                    unset($this->properties[$p_type][$i]);
                }
            }
            else
            {
                unset($this->properties[$p_type]);
            }

            return TRUE;
        }

        return FALSE;
    }


    /**
     * Function save_all_in_db
     *
     * This function saves all properties into database
     *
     * @param object  $conn           Database access object
     * @param boolean $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool                   TRUE in correct deletion
     * @throws Av_exception           If an error occurred
     */
    public function save_all_in_db($conn, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host_id = $this->host_id;

        //Begin transaction
        $conn->StartTrans();

        self::delete_all_from_db($conn, $host_id);

        foreach ($this->properties as $p_type => $p_values)
        {
            foreach ($p_values as $p_data)
            {
                $value = $p_data['value'];

                $query = 'REPLACE INTO host_properties (host_id, property_ref, source_id, value) VALUES (UNHEX(?), ?, ?, ?)';

                $params = array($host_id, $p_type, $p_data['source']['id'], $value);

                $conn->Execute($query, $params);
            }
        }

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            if ($report_changes == TRUE)
            {
                try
                {
                    Asset_host::report_changes($conn, 'hosts');
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Asset properties could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /*************************************************
     **************** Static functions ***************
     *************************************************/


    /**
     * Function decode_values
     *
     * This function takes a JSON encoded string and converts it into array.
     *
     * @param string $data JSON encoded string
     *
     * @access public
     * @return array  Decoded json values
     */
    public static function decode_values($data)
    {
        $decoded_data = @json_decode($data, TRUE);

        return (json_last_error() == JSON_ERROR_NONE) ? $decoded_data : array($data);
    }


    /**
     * Function bulk_get_list
     *
     * This function returns the common properties for all hosts which match with current filter database grouped by reference and value
     *
     * @param object $conn    Database access object
     * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param bool   $cache   [Optional] Use cached information
     *
     * @access public
     * @return array          Common properties data
     * @throws Av_exception   If a connection error occurred
     */
    public static function bulk_get_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $total_selection  = Filter_list::get_total_selection($conn, 'asset');

        $properties       = array();

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $params = array(
            session_id(),
            $total_selection
        );

        $query_select = 'hp.property_ref, hp.value, count(distinct hp.host_id) AS total, hpr.name AS property_name,
                         hpr.description AS property_descr, hsf.id AS source_id, hsf.name AS source_name';

        $q_where = $host_perms_where;
        $q_order = '';
        $q_limit = '';

        $cnt     = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_order .= ' ORDER BY ' . $filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_limit .= ' LIMIT ' . $filters['limit'];
            $cnt = ' SQL_CALC_FOUND_ROWS ';
        }

        $query    = "SELECT $cnt $query_select
                     FROM host_properties hp, user_component_filter uf, host h, host_property_reference hpr, host_source_reference hsf
                     WHERE uf.session_id=? AND hp.host_id=uf.asset_id AND uf.asset_type='asset' AND hp.property_ref=hpr.id
                     AND hp.source_id = hsf.id AND h.id=uf.asset_id $q_where
                     GROUP BY hp.property_ref, hp.value
                     HAVING total = ?
                     $q_order
                     $q_limit";

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
            $properties[$rs->fields['property_ref']][$rs->fields['value']] = array(
                'value'       => $rs->fields['value'],
                'name'        => $rs->fields['property_name'],
                'description' => $rs->fields['property_descr'],
                'source'      => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                ),
            );

            $rs->MoveNext();
        }

        return array($properties, $total);
    }


    /**
     * Function get_property_from_db
     *
     * This function returns the property $p_type from db
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     * @param int    $p_type  Property Type
     *
     * @access public
     * @return array          Property values
     * @throws Av_exception   If a connection error occurred
     */
    public static function get_property_from_db($conn, $host_id, $p_type)
    {
        Ossim_db::check_connection($conn);

        $p_values = array();

        $query = 'SELECT value FROM host_properties
            WHERE property_ref = ? AND host_id = UNHEX(?)
            ORDER BY last_modified DESC';

        $params = array($p_type, $host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $values = self::decode_values($rs->fields['value']);
            foreach ($values as $value)
            {
                $p_values[] = $value;
            }

            $rs->MoveNext();
        }

        return $p_values;
    }


    /**
     * Function save_property_in_db
     *
     * This function saves one property into database
     *
     * @param object $conn           Database access object
     * @param string $host_id        Host ID
     * @param int    $p_type         Property Type
     * @param string $value          Property value
     * @param int    $source_id      [Optional] Property Type
     * @param bool   $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool                  TRUE If property is correctly saved
     * @throws Av_exception          If an error occurred
     */
    public static function save_property_in_db($conn, $host_id, $p_type, $value, $source_id = 1, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the device
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $host_properties = new self($conn, $host_id);

        if ($host_properties->add_new($p_type, $value, '', $source_id) == TRUE)
        {
            unset($host_properties);

            $query  = 'REPLACE INTO host_properties (host_id, property_ref, source_id, value) VALUES (UNHEX(?), ?, ?, ?)';
            $params = array($host_id, $p_type, $source_id, $value);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            if ($report_changes == TRUE)
            {
                try
                {
                    Asset_host::report_changes($conn, 'hosts');
                }

                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }

        return TRUE;
    }


    /**
     * Function bulk_save_property_in_db
     *
     * This function saves one property into database for the bulk edition
     *
     * @param object $conn           Database access object
     * @param int    $p_type         Property Type
     * @param string $value          Property value
     * @param int    $source_id      [Optional] Property Type
     * @param bool   $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool                  TRUE If property is correctly saved
     * @throws Av_exception          If an error occurred
     */
    public static function bulk_save_property_in_db($conn, $p_type, $value, $source_id = 1, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);


        $tmptable = Util::create_tmp_table($conn, 'host_id binary(16) NOT NULL, property_ref int(11) NOT NULL, source_id int(11) DEFAULT NULL, value text NOT NULL, PRIMARY KEY (host_id, property_ref, value (255))');

        $query  = "REPLACE INTO $tmptable (host_id, property_ref, source_id, value)
                        SELECT uf.asset_id, ?, ?, ? FROM user_component_filter uf, host h
                        WHERE uf.session_id=? AND uf.asset_type='asset' AND h.id=uf.asset_id $host_perms_where";

        $params = array($p_type, $source_id, $value, session_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "REPLACE INTO host_properties (host_id, property_ref, source_id, value)
                  SELECT host_id, property_ref, source_id, value FROM $tmptable";

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch (Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
     * Function delete_all_from_db
     *
     * This function deletes all properties from database
     *
     * @param object $conn           Database access object
     * @param string $host_id        Host ID
     * @param bool   $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool                  TRUE in correct deletion
     * @throws Av_exception          If an error occurred
     */
    public static function delete_all_from_db($conn, $host_id, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the device
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        //Delete all properties from database
        $query  = 'DELETE FROM host_properties WHERE host_id = UNHEX(?)';
        $params = array($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch (Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
     * Function bulk_delete
     *
     * This function deletes all properties from database for the bulk edition
     *
     * @param object $conn           Database access object
     * @param bool   $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool            TRUE in correct deletion
     * @throws Av_exception    If an error occurred
     */
    public static function bulk_delete_all_from_db($conn, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $params = array(
            session_id()
        );

        $tmptable = Util::create_tmp_table($conn, 'id binary(16) NOT NULL, PRIMARY KEY ( id )');

        $conn->Execute("REPLACE INTO $tmptable SELECT asset_id FROM user_component_filter uf, host h WHERE uf.session_id=? AND h.id=uf.asset_id AND uf.asset_type='asset' $host_perms_where", $params);

        $query  = "DELETE hp FROM host_properties hp, $tmptable tmp WHERE hp.host_id=tmp.id";

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch (Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
     * Function delete_property_from_db
     *
     * This function deletes one or more properties from database
     *
     * @param object $conn           Database access object
     * @param string $host_id        Host ID
     * @param int    $p_type         Property Type
     * @param string $value          Property value
     * @param bool   $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool                  TRUE if property/ies are correctly deleted
     * @throws Av_exception          If an error occurred
     */
    public static function delete_property_from_db($conn, $host_id, $p_type, $value = '', $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the device
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($value != '')
        {
            $query  = 'DELETE FROM host_properties WHERE value = ? AND host_id = UNHEX(?) AND property_ref = ?';
            $params = array($value, $host_id, $p_type);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            //Delete empty properties
            $query  = "DELETE FROM host_properties WHERE host_id = UNHEX(?) AND property_ref = ? AND value = ''";
            $params = array($host_id, $p_type);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        else
        {
            $query = 'DELETE FROM host_properties WHERE host_id = UNHEX(?) AND property_ref = ?';

            $params = array($host_id, $p_type);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch (Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
     * Function bulk_delete_property_from_db
     *
     * This function deletes one or more properties from database for the bulk edition
     *
     * @param object $conn            Database access object
     * @param int    $p_type          Property Type
     * @param string $value           [Optional]  Property value
     * @param bool   $report_changes  [Optional]  Report changes to other components
     *
     * @access public
     * @return bool                  TRUE if property/ies are correctly deleted
     * @throws Av_exception          If an error occurred
     */
    public static function bulk_delete_property_from_db($conn, $p_type, $value = '', $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $params = array(
            session_id(),
            $p_type
        );


        if ($value != '')
        {
            $params[] = $value;

            $tmptable = Util::create_tmp_table($conn, 'id binary(16) NOT NULL, property_ref int(11) NOT NULL, value text NOT NULL, PRIMARY KEY (id, property_ref, value (255))');

            $query = "REPLACE INTO $tmptable SELECT asset_id, property_ref, value FROM user_component_filter uf, host h, host_properties hp WHERE h.id=hp.host_id
                AND uf.session_id=? AND h.id=uf.asset_id AND uf.asset_type='asset' AND hp.property_ref=? AND hp.value=? $host_perms_where";

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $query  = "DELETE hp FROM host_properties hp, $tmptable tmp WHERE hp.host_id=tmp.id AND hp.property_ref=tmp.property_ref AND hp.value=tmp.value";

            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        else
        {
            $tmptable = Util::create_tmp_table($conn, 'id binary(16) NOT NULL, property_ref int(11) NOT NULL, PRIMARY KEY (id, property_ref)');

            $query = "REPLACE INTO $tmptable SELECT asset_id, property_ref FROM user_component_filter uf, host h, host_properties hp WHERE h.id=hp.host_id AND uf.session_id=?
                AND h.id=uf.asset_id AND uf.asset_type='asset' AND hp.property_ref=? $host_perms_where";

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $query = "DELETE hp FROM host_properties hp, $tmptable tmp WHERE hp.host_id=tmp.id AND hp.property_ref=tmp.property_ref";

            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        //Delete empty properties
        $query  = "DELETE FROM host_properties WHERE property_ref = ? AND value = ''";
        $params = array($p_type);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch (Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
     * Function get_os_by_host
     *
     * This function returns the OS associated with host $host_id
     *
     * @param object $conn       Database access object
     * @param string $host_id    Host ID
     * @param string $pixmap_dir [Optional] Image path
     * @param bool   $img_only   [Optional] Return image path o html image
     *
     * @access public
     * @return string
     * @throws Av_exception      If a connection error occurred
     */
    public static function get_os_by_host($conn, $host_id, $pixmap_dir = '../pixmaps', $img_only = FALSE)
    {
        Ossim_db::check_connection($conn);

        $os = NULL;

        $query = "SELECT h.value FROM host_properties h, host_property_reference r
            WHERE h.property_ref = r.id AND h.host_id = UNHEX(?)
            AND r.name = 'operating-system' ORDER BY h.last_modified DESC LIMIT 1";

        $params = array($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $value = self::decode_values($rs->fields['value']);

            if (!empty($value))
            {
                $os_str = array_pop($value);
                $os     = Properties::get_os_pixmap($os_str, $pixmap_dir, $img_only);
            }
        }

        return $os;
    }


    /**
     * Function get_status_by_host
     *
     * This function returns the machine state property (ID = 7)
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     *
     * @access public
     * @return string         up, down or unknown if not found
     * @throws Av_exception   If a connection error occurred
     */
    public static function get_status_by_host($conn, $host_id)
    {
        Ossim_db::check_connection($conn);

        $status = 'unknown';

        $query = 'SELECT value FROM host_properties
            WHERE property_ref = 7 AND host_id = UNHEX(?)
            ORDER BY last_modified DESC LIMIT 1';

        $params = array($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $values = self::decode_values($rs->fields['value']);

            if (is_array($values) && !empty($values))
            {
                $status = strtolower(array_pop($values));
            }
        }

        return $status;
    }


    /**
     * Functino get_status_by_group
     *
     * This function returns the machine state property (ID = 7)
     *
     * @param object $conn     Database access object
     * @param string $group_id Host Group ID
     *
     * @access public
     * @return array           up, down or unknown if not found for each host of the group
     * @throws Av_exception    If a connection error occurred
     */
    public static function get_status_by_group($conn, $group_id)
    {
        Ossim_db::check_connection($conn);

        $status = array();

        $query = 'SELECT value, HEX(host_id) AS id FROM host_properties
                    WHERE property_ref = 7
                    AND host_id IN (SELECT host_id FROM host_group_reference WHERE host_group_id = UNHEX(?))
                    ORDER BY last_modified DESC';

        $params = array($group_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $st     = 'unknown';
            $values = self::decode_values($rs->fields['value']);

            if (is_array($values) && !empty($values))
            {
                $st = array_pop($values);
            }

            $status[$rs->fields['id']] = $st;

            $rs->MoveNext();
        }

        return $status;
    }


    /**
     * Function get_users_by_host
     *
     * This function returns the user property (ID = 8)
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     * @param array  $filters [Optional] SQL conditions
     *
     * @access public
     * @return array          array[0] -> User list by host; array[1] -> Total
     * @throws Av_exception   If a connection error occurred
     */
    public static function get_users_by_host($conn, $host_id, $filters = array())
    {
        Ossim_db::check_connection($conn);

        $users = array();

        $q_where = '';
        $cnt     = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY ' . $filters['order_by'];
        }
        else
        {
            $q_where .= ' ORDER BY last_modified DESC';
        }

        if (!empty($filters['limit']))
        {
            $q_where .= ' LIMIT ' . $filters['limit'];
            $cnt = ' SQL_CALC_FOUND_ROWS ';
        }

        $query = "SELECT $cnt value, HEX(host_id) AS id, last_modified FROM host_properties
            WHERE property_ref = 8 AND host_id = UNHEX(?) $q_where";

        $params = array($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $values = self::decode_values($rs->fields['value']);

            if (is_array($values) && !empty($values))
            {
                foreach ($values as $value_item)
                {
                    list($username, $domain) = explode('@', $value_item);
                    $users[$rs->fields['id']][] = array('user' => $username, 'domain' => $domain, 'date' => $rs->fields['last_modified']);
                }
            }

            $rs->MoveNext();
        }

        return array($users, $total);
    }


    /**
     * Function get_users_by_net
     *
     * This function returns the user property (ID = 8)
     *
     * @param object $conn    Database access object
     * @param string $net_id  Network ID
     * @param array  $filters [Optional] Sql conditions
     *
     * @access public
     * @return array         array[0] -> Users list by net host; array[1] -> Total
     * @throws Av_exception  If a connection error occurred
     */
    public static function get_users_by_net($conn, $net_id, $filters = array())
    {
        Ossim_db::check_connection($conn);

        $users = array();

        $q_where = '';
        $cnt     = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY ' . $filters['order_by'];
        }
        else
        {
            $q_where .= ' ORDER BY last_modified DESC';
        }

        if (!empty($filters['limit']))
        {
            $q_where .= ' LIMIT ' . $filters['limit'];
            $cnt = ' SQL_CALC_FOUND_ROWS ';
        }

        $query = "SELECT $cnt value, HEX(host_id) as id, last_modified FROM host_properties
        WHERE property_ref = 8
        AND host_id IN (SELECT host_id FROM host_net_reference WHERE net_id = UNHEX(?)) $q_where";

        $params = array($net_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query);
        }

        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $values = self::decode_values($rs->fields['value']);

            if (is_array($values) && !empty($values))
            {
                foreach ($values as $value_item)
                {
                    list($username, $domain) = explode('@', $value_item);

                    $users[$rs->fields['id']][] = array(
                        'user'   => $username,
                        'domain' => $domain,
                        'date'   => $rs->fields['last_modified']
                    );
                }
            }

            $rs->MoveNext();
        }

        return array($users, $total);
    }


    /**
     * Function get_users_by_group
     *
     * This function returns the user property (ID = 8)
     *
     * @param object $conn     Database access object
     * @param string $group_id Host Group ID
     * @param array  $filters  [Optional] Sql conditions
     *
     * @access public
     * @return array           array[0] -> Users list by group hosts; array[1] -> Total
     * @throws Av_exception    If a connection error occurred
     */
    public static function get_users_by_group($conn, $group_id, $filters = array())
    {
        Ossim_db::check_connection($conn);

        $users = array();

        $q_where = '';
        $cnt     = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY ' . $filters['order_by'];
        }
        else
        {
            $q_where .= ' ORDER BY last_modified DESC';
        }

        if (!empty($filters['limit']))
        {
            $q_where .= ' LIMIT ' . $filters['limit'];
            $cnt = ' SQL_CALC_FOUND_ROWS ';
        }

        $query = "SELECT $cnt value, HEX(host_id) as id, last_modified FROM host_properties
                    WHERE property_ref = 8
                          AND host_id IN (SELECT host_id FROM host_group_reference WHERE host_group_id = UNHEX(?)) $q_where";

        $params = array($group_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $values = self::decode_values($rs->fields['value']);

            if (is_array($values) && !empty($values))
            {
                foreach ($values as $value_item)
                {
                    list($username, $domain) = explode('@', $value_item);

                    $users[$rs->fields['id']][] = array(
                        'user'   => $username,
                        'domain' => $domain,
                        'date'   => $rs->fields['last_modified']
                    );
                }
            }

            $rs->MoveNext();
        }

        return array($users, $total);
    }


    /**
     * Function get_latest
     *
     * This function returns latest host properties from database
     *
     * @param object $conn  Database access object
     * @param string $date  [Optional] Minimum date
     * @param int    $limit [Optional] Maximum results
     *
     * @access public
     * @return array        Properties list
     * @throws Av_exception If a connection error occurred
     */
    public static function get_latest($conn, $date = '', $limit = 50)
    {
        Ossim_db::check_connection($conn);

        $properties = array();

        $date = (!empty($date)) ? $date : date('Y-m-d H:i:s', strtotime('-1 week'));


        $limit = intval($limit);
        $limit = ($limit == 0) ? 10 : $limit;


        $query = 'SELECT *, HEX(host_id) AS host_id FROM host_properties
            WHERE last_modified >= ? LIMIT ?';

        $params = array($date, $limit);

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $properties[] = $rs->fields;

            $rs->MoveNext();
        }

        return $properties;
    }


    /**
     * Function count_all
     *
     * This function counts host properties from database
     *
     * @param object $conn    Database access object
     * @param string $host_id [Optional] Host ID
     *
     * @access public
     * @return array          Total number of each property by ref
     * @throws Av_exception   If a connection error occurred
     */
    public static function count_all($conn, $host_id = '')
    {
        Ossim_db::check_connection($conn);

        $t_properties = array();

        // Permissions
        $perms_where = Asset_host::get_perms_where('h.', TRUE);

        if (!empty($host_id))
        {
            $query = "SELECT DISTINCT property_ref, count(*) AS total
                  FROM host_properties hp, host h
                  WHERE hp.host_id = UNHEX(?) AND hp.host_id = h.id
                  $perms_where
                  GROUP BY property_ref";

            $params = array($host_id);
        }
        else
        {
            $query = "SELECT DISTINCT property_ref, count(*) AS total
                  FROM host_properties hp, host h
                  WHERE hp.host_id = h.id $perms_where
                  GROUP BY property_ref";

            $params = array();
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $t_properties[$rs->fields['property_ref']] = $rs->fields['total'];

            $rs->MoveNext();
        }

        return $t_properties;
    }


    /**
     * Function get_list
     *
     * This function returns a filtered host properties list
     *
     * @param object $conn    Database access object
     * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param bool   $cache   [Optional] Use cached information
     *
     * @access public
     * @return array          array[0] -> Property list by host and ref; array[1] -> Total
     * @throws Av_exception   If a connection error occurred
     */
    public static function get_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $properties = array();

        //Build SQL
        $perms_where = Asset_host::get_perms_where('h.', TRUE);

        $q_where = $perms_where;
        $cnt     = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY ' . $filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_where .= ' LIMIT ' . $filters['limit'];
            $cnt = ' SQL_CALC_FOUND_ROWS ';
        }

        $query = ossim_query("SELECT $cnt HEX(host_properties.host_id) AS h_id, hsr.name AS source_name,
           hpr.name AS property_name, hpr.description AS property_descr, host_properties.*, hpr.*
           FROM host h, host_properties, host_property_reference hpr, host_source_reference hsr
           WHERE host_properties.host_id = h.id
           AND host_properties.property_ref = hpr.id
           AND host_properties.source_id = hsr.id $q_where");

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

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

        while (!$rs->EOF)
        {
            $properties[$rs->fields['h_id']][$rs->fields['property_ref']][] = array(
                'extra'       => $rs->fields['extra'],
                'name'        => $rs->fields['property_name'],
                'description' => $rs->fields['property_descr'],
                'source'      => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                ),
                'date'        => $rs->fields['last_modified'],
                'value'       => $rs->fields['value']
            );

            $rs->MoveNext();
        }

        return array($properties, $total);
    }

    /**
     * Function get_all
     *
     * This function returns a filtered and unified host Properties/Software/Mac list
     *
     * @param object $conn    Database access object
     * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param bool   $cache   [Optional] Use cached information
     *
     * @access public
     * @return array          array[0] -> Property list by host and ref; array[1] -> Total
     * @throws Av_exception   If a connection error occurred
     */
    public static function get_all($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $properties = array();

        $total      = 0;

        //Build SQL

        $perms_where = Asset_host::get_perms_where('h_', TRUE);
        $q_where     = $perms_where;
        $q_limit     = '';


        if (!empty($filters['where']))
        {
            $q_where  .= ' AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_limit = ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);


        $query = "SELECT SQL_CALC_FOUND_ROWS *, HEX(h_id) AS h_id FROM (
                 SELECT
                    hp.host_id       AS h_id,
                    h.ctx            AS h_ctx,
                    hp.property_ref  AS property_ref,
                    hpr.description  AS property_descr,
                    hp.last_modified AS date,
                    hp.source_id     AS source_id,
                    hsr.name         AS source_name,
                    hp.value         AS value,
                    ''               AS extra
                  FROM host h, host_properties hp, host_property_reference hpr, host_source_reference hsr
                  WHERE hp.host_id = h.id
                    AND hp.property_ref = hpr.id
                    AND hp.source_id = hsr.id
                    AND hp.property_ref NOT IN (3, 14)
                  UNION
                 SELECT
                    h.id               AS h_id,
                    h.ctx              AS h_ctx,
                    50                 AS property_ref,
                    'MAC Address'      AS property_descr,
                    '-'                AS date,
                    1                  AS source_id,
                    '-'                AS source_name,
                    CONCAT(LEFT(hex(hip.mac), 2), ':', MID(hex(hip.mac), 3,2), ':', MID(hex(hip.mac), 5,2), ':', MID(hex(hip.mac), 7,2),
                        ':', MID(hex(hip.mac), 9,2), ':', RIGHT(hex(hip.mac), 2)) AS value,
                    INET6_NTOA(hip.ip) AS extra
                  FROM host h, host_ip hip
                  WHERE hip.host_id = h.id
                    AND hip.mac IS NOT NULL
                 ) properties WHERE 1 $q_where $q_limit";


        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $total = Ossim_db::get_found_rows($conn, $query, $cache);

        while (!$rs->EOF)
        {
            $_prop = array(
                'description' => $rs->fields['property_descr'],
                'date'        => $rs->fields['date'],
                'source'      => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                ),
                'value' => $rs->fields['value'],
                'extra' => $rs->fields['extra']
            );
            
            if ($filters['response_type'] == 'by_ref')
            {
                $properties[$rs->fields['property_ref']][$rs->fields['h_id']][] = $_prop;
            }
            else
            {
                $properties[$rs->fields['h_id']][$rs->fields['property_ref']][] = $_prop;
            }
            
            $rs->MoveNext();
        }

        return array($properties, $total);
    }

    /**
     * Function get_property_values
     *
     * This function returns a filtered host properties list
     *
     * @param object  $conn    Database access object
     * @param integer $id      Property id
     * @param array   $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param bool    $cache   [Optional] Use cached information
     *
     * @access public
     * @return array          array[0] -> Property list by host and ref; array[1] -> Total
     * @throws Av_exception   If a connection error occurred
     */
    public static function get_property_values($conn, $id, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $properties = array();
        $id         = intval($id);

        //Build SQL
        $perms_where = Asset_host::get_perms_where('h.', TRUE);

        $q_where = $perms_where;
        $cnt     = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND ' . $filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY ' . $filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_where .= ' LIMIT ' . $filters['limit'];
            $cnt = ' SQL_CALC_FOUND_ROWS ';
        }

        $query = ossim_query("SELECT $cnt DISTINCT host_properties.value FROM host h, host_properties WHERE
            host_properties.host_id = h.id AND host_properties.property_ref = $id $q_where");

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

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

        while (!$rs->EOF)
        {
            $properties[md5($rs->fields['value'])] = $rs->fields['value'];

            $rs->MoveNext();
        }

        return array($properties, $total);
    }
}

/* End of file asset_host_properties.inc */
/* Location: ../include/classes/asset_host_properties.inc */
