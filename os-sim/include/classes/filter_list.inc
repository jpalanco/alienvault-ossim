<?php
/**
* asset_filter.inc
*
* File Filter_list.inc is used to:
*   - Manage filter collections
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

require_once 'av_init.php';

//WE DO NEED TO INCLUDE THE CLASS --> AUTOLOAD WON'T LOAD IT
require_once 'classes/asset_filters.inc';



/**
* Asset Filter List Class
*
* Class for managing asset filters
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
abstract class Filter_list
{
    /**
    * Set of valid filters
    * @var array
    *
    */
    protected $_valid_filters;


    /**
    * Set of filters
    * @var array
    *
    */
    protected $_filters;


    /**
    * Flag to force the group to be recalculated. (Execute the procedure with clean_host=1)
    * @var boolean
    *
    */
    protected $_force_reload;



    /*****    CONSTANT DEFINITIONS    *****/


    const MAX_NMAP_ITEMS  = 3500;
    const MAX_VULNS_ITEMS = 3500;


    /**
    * This function returns the list of filters
    *
    * @param  integer  $id  Filter ID
    *
    * @return  object
    */
    public function get_filter($id)
    {
        if ($this->_check_valid_filter_exist($id))
        {
            return $this->_filters[$id];
        }
        else
        {
            return FALSE;
        }
    }


    /**
    * This function returns all the filters
    *
    * @return  array
    */
    public function get_filters()
    {
        return $this->_filters;
    }


    /**
    * This function returns the valid filters
    *
    * @return  array
    */
    public function get_valid_filters()
    {
        return $this->_valid_filters;
    }


    /**
    * This function sets the force_reload option to true
    *
    * @return  void
    */
    public function force_reload()
    {
        $this->_force_reload = TRUE;
    }


    /**
    * This function checks if the id of the filter exists.
    *
    * With this function we make sure that the filter we are managing is one of the filter
    * stored in the table asset_filter_type which are the only filters which are valid.
    *
    * @param  integer  $id  Filter ID
    *
    * @return  void
    */
    protected function _check_valid_filter_exist($id)
    {
        if (is_object($this->_filters[$id]))
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function modify the value of a filter within the filter list
    *
    * @param  integer  $id       Filter ID
    * @param  string   $values   Filter Values
    * @param  boolean  $delete   Delete filter flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return  void
    */
    public function modify_filter($id, $values, $delete = FALSE)
    {
        //First we check the filter we wanna modify is a valid filter.
        if ($this->_check_valid_filter_exist($id))
        {
            //We get the particular filter
            $filter = $this->_filters[$id];

            //Trying to set the value to the filter. The complexity relys on the Filter class
            $filter->modify_value($values, $delete);

            //Setting the flag to force the reload group if the modification of the filter needs it.
            if ($filter->is_force_reload())
            {
                $this->_force_reload = TRUE;
            }
        }
    }


    /**
    * This function empty the user_host_filter table
    *
    * This function is called when we deselect all the filters.
    *
    * @param  object  $conn  DB Connection
    *
    * @throws Exception  If there is any error on the query
    *
    * @return  void
    */
    public function empty_filter_search($conn)
    {
        //Deleting the host of the user logged in
        $query  = 'DELETE FROM user_host_filter WHERE session_id=?';
        $params = array
        (
            session_id()
        );

        $rs = $conn->Execute($query, $params);

        //If there is an error, throwing exception
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        //If everything was right, return true
        else
        {
            return TRUE;
        }
    }


    /**
    * This function applies the filters selected in the group
    *
    * Once the filters are selected, these are passed to the procedure "add_filter"
    * and the table user_host_filter is populated with the assets that match the filters.
    *
    * @param  object  $conn  DB Connection
    *
    * @throws Exception  If there is any error on the procedure.
    *
    * @return  void
    */
    abstract public function apply_filter_search($conn);


    /**
    * This function gets the number of filters modified
    *
    * @return  integer  The number of filters modified.
    */
    public function get_num_filter_added()
    {
        $total = 0;

        //Going throught each filter of the filter list
        foreach ($this->_filters as $filter)
        {
            //Getting the value of the filter
            if ($filter->is_active())
            {
                $total ++;
            }
        }

        return $total;
    }


    /**
    * This function store the object in session
    *
    * @return  void.
    */
    public function store_filter_list_session()
    {
        $s_key = '_current_filter_list';
        $_SESSION[$s_key] = serialize($this);
    }


    /************************************************************************************************/
    /************************************************************************************************/
    /************************************    STATIC FUNCTIONS    ************************************/
    /************************************************************************************************/
    /************************************************************************************************/


    /**
    * This function deletes the object stored in session
    *
    * @return  void.
    */
    public static function delete_filters_from_session()
    {
        $s_key = '_current_filter_list';
        unset($_SESSION[$s_key]);

        self::delete_filter_copy();
    }


    /**
    * This function retrieves the object stored in session
    *
    * @return  object  The filter list object or FALSE.
    */
    public static function retrieve_filter_list_session()
    {
        $s_key = '_current_filter_list';

        //Getting the object with the filters. Unserialize needed.
        $filter_list = unserialize($_SESSION[$s_key]);

        if ($filter_list instanceof self)
        {
            return $filter_list;
        }
        else
        {
            return FALSE;
        }
    }


    /**
    * This function create a copy of the current filter object
    *
    * @return  void.
    */
    public static function create_filter_copy()
    {
        $s_key = '_filter_list_copy';

        if (empty($_SESSION[$s_key]))
        {
            $filters = self::retrieve_filter_list_session();

            if ($filters !== FALSE)
            {
                $_SESSION[$s_key] = serialize($filters);
            }

        }
    }


    /**
    * This function deletes the copy of the object stored in session
    *
    * @return  void.
    */
    public static function delete_filter_copy()
    {
        $s_key = '_filter_list_copy';
        unset($_SESSION[$s_key]);
    }


    /**
    * This function restore the copy of the object
    *
    * @return  void.
    */
    public static function restore_filter_copy()
    {
        $s_key   = '_filter_list_copy';
        $filters = unserialize($_SESSION[$s_key]);

        if ($filters instanceof self)
        {
            $filters->store_filter_list_session();
        }

        self::delete_filter_copy();
    }


    /************************************************************************************************/
    /*******************************          FILTER LEGENDS          *******************************/
    /************************************************************************************************/

    /**
    * This function restore the copy of the object
    *
    * @return  void.
    */
    public static function get_filter_legends($filter = '')
    {
        $legends = array();

        $legends['vulns'] = array(
            '7' => _('Info'),
            '6' => _('Low'),
            '3' => _('Medium'),
            '2' => _('High'),
            '1' => _('Serious')
        );


        $legends['asset_value'] = array(
            '0' => '0',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5'
        );

        $legends['date_ranges'] = array(
            'day'   => _('Last Day'),
            'week'  => _('Last Week'),
            'month' => _('Last Month'),
            'range' => _('Date Range')
        );

        $legends['availability'] = array(
            2 => _('Up'),
            1 => _('Down'),
            0 => _('Unconfigured')
        );

        $legends['hids'] = array(
            2 => _('Connected'),
            1 => _('Disconnected'),
            0 => _('Not Deployed')
        );


        if ($filter != '')
        {
            return is_array($legends[$filter]) ? $legends[$filter]: array();
        }
        else
        {
            return $legends;
        }
    }




    /************************************************************************************************/
    /*******************************    MANAGE SELECTION FUNCTIONS    *******************************/
    /************************************************************************************************/


    /**
    * This function returns the total number of asset selected from the table user_component_filter
    *
    * @param object  $conn     Database access object
    * @param string  $type     Filter Type: asset, network or group
    *
    * @throws Exception  If an error occurred
    *
    * @return integer  Total Number of assets selected
    */
    public static function get_total_selection($conn, $type = '')
    {
        Ossim_db::check_connection($conn);

        $query  = "SELECT COUNT(asset_id) AS total FROM user_component_filter WHERE session_id=? AND asset_type=?";

        $params = array(
            session_id(),
            $type
        );

        $rs = $conn->Execute($query, $params);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return intval($rs->fields['total']);
    }


    /**
    * This function deletes the asset selected from the table user_component_filter
    *
    * @param object  $conn     Database access object
    * @param string  $type     Filter Type: asset, network or group
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function clean_selection($conn, $type = '')
    {
        Ossim_db::check_connection($conn);

        $query  = "DELETE FROM user_component_filter WHERE session_id=? AND asset_type=?";

        $params = array(
            session_id(),
            $type
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


   /**
    * This function deletes assets from table user_component_filter by filter criteria
    *
    * @param object  $conn       Database access object
    * @param string  $criteria   Filter Criteria: not_windows_os|windows_os
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function clean_asset_by_criteria($conn, $criteria)
    {
        Ossim_db::check_connection($conn);

        //Create tmp table
        $tmp_table = Util::create_tmp_table($conn, "host_id binary(16) NOT NULL, PRIMARY KEY (host_id)");
        $session   = session_id();

        $q_criteria = NULL;

        switch ($criteria)
        {
            case 'windows_os':

                $q_criteria = 'SELECT f.asset_id FROM user_component_filter f
                    INNER JOIN host_properties hp ON hp.host_id = f.asset_id
                    WHERE f.asset_type="asset" AND f.session_id = "'.$session.'"
                    AND hp.property_ref=3 AND (hp.value LIKE "windows%" OR hp.value LIKE "microsoft%")';
            break;

            case 'not_windows_os':

                $q_criteria = 'SELECT f.asset_id FROM user_component_filter f
                    LEFT JOIN host_properties hp ON hp.host_id = f.asset_id AND hp.property_ref = 3
                    WHERE f.asset_type="asset" AND f.session_id = "'.$session.'" AND
                    ((hp.host_id IS NULL OR hp.value IS NULL OR hp.value LIKE "%unknown%") OR
                    (hp.value NOT LIKE "windows%" AND hp.value NOT LIKE "microsoft%"))';
            break;
        }

        if (!empty($q_criteria))
        {
            //Fill tmp table
            $conn->SetFetchMode(ADODB_FETCH_ASSOC);

            $query = ossim_query("INSERT INTO $tmp_table (host_id) $q_criteria");

            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            //Remove assets
            $query = "DELETE f FROM user_component_filter f INNER JOIN $tmp_table tmp ON tmp.host_id = f.asset_id";

            if ($conn->Execute($query) === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
    }


    /**
    * This function saves the asset members of a group or network into the table user_component_filter
    *
    * @param object  $conn          Database access object
    * @param string  $asset_id      Asset ID (Group or Network)
    * @param string  $asset_type    Filter Type:  Network or Group
    * @param string  $filter        Extra filter
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function save_items($conn, $asset_type, $assets)
    {
        Ossim_db::check_connection($conn);

        self::clean_selection($conn, $asset_type);

        if (empty($assets))
        {
            return 0;
        }

        $session = session_id();
        $sql     = array();

        foreach($assets as $asset)
        {
            $sql[] = "('$session', 0x$asset, '$asset_type')";
        }

        $sql    = implode(',', $sql);
        $query  = "REPLACE INTO user_component_filter VALUES $sql";
        $params = array();

        $rs = $conn->Execute($query, $params);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $conn->Affected_Rows();
    }


    /**
    * This function saves the asset members of a group or network into the table user_component_filter
    *
    * @param object  $conn          Database access object
    * @param string  $asset_id      Asset ID (Group or Network)
    * @param string  $asset_type    Filter Type:  Network or Group
    * @param string  $filter        Extra filter
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function save_members_from_asset($conn, $asset_id, $asset_type, $filter)
    {
        Ossim_db::check_connection($conn);

        $perms = Asset_host::get_perms_where('h.', TRUE);

        self::clean_selection($conn, 'asset');

        if ($asset_type == 'asset')
        {
            $query = "SELECT DISTINCT ?, host_group_id, 'group' FROM host_group_reference WHERE host_id=UNHEX(?)";
        }
        elseif ($asset_type == 'group')
        {
            if ($perms)
            {
                 $query = "SELECT DISTINCT ?, hr.host_id, 'asset' FROM host_group_reference hr, host h WHERE h.id=hr.host_id AND hr.host_group_id=UNHEX(?) $perms";
            }
            else
            {
                $query = "SELECT DISTINCT ?, host_id, 'asset' FROM host_group_reference WHERE host_group_id=UNHEX(?)";
            }
        }
        elseif ($asset_type == 'network')
        {
            if ($perms)
            {
                 $query = "SELECT DISTINCT ?, hn.host_id, 'asset' FROM host_net_reference hn, host h WHERE h.id=hn.host_id AND hn.net_id=UNHEX(?) $perms";
            }
            else
            {
                $query = "SELECT DISTINCT ?, host_id, 'asset' FROM host_net_reference WHERE net_id=UNHEX(?)";
            }
        }
        else
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "REPLACE INTO user_component_filter ($query)";

        $params = array(
            session_id(),
            $asset_id
        );

        $rs = $conn->Execute($query, $params);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $conn->Affected_Rows();
    }


    /**
    * This function saves the asset members of a group or network into the table user_component_filter
    *
    * @param object  $conn          Database access object
    * @param string  $asset_id      Asset ID (Group or Network)
    * @param string  $asset_type    Filter Type:  Network or Group
    * @param string  $filter        Extra filter
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function save_members_from_selection($conn, $asset_type)
    {
        Ossim_db::check_connection($conn);

        self::clean_selection($conn, 'asset');

        $perms = Asset_host::get_perms_where('h.', TRUE);

        if ($asset_type == 'group')
        {
            if ($perms)
            {
                $query = "SELECT DISTINCT ?, g.host_id, 'asset' FROM host_group_reference g, user_component_filter f, host h WHERE g.host_group_id=f.asset_id AND f.session_id=? AND f.asset_type='group' AND h.id=g.host_id $perms";
            }
            else
            {
                $query = "SELECT DISTINCT ?, g.host_id, 'asset' FROM host_group_reference g, user_component_filter f WHERE g.host_group_id=f.asset_id AND f.session_id=? AND f.asset_type='group'";
            }

        }
        elseif ($asset_type == 'network')
        {
            if ($perms)
            {
                $query = "SELECT DISTINCT ?, n.host_id, 'asset' FROM host_net_reference n, user_component_filter f, host h WHERE n.net_id=f.asset_id AND f.session_id=? AND f.asset_type='network' AND h.id=n.host_id $perms";
            }
            else
            {
                $query = "SELECT DISTINCT ?, n.host_id, 'asset' FROM host_net_reference n, user_component_filter f WHERE n.net_id=f.asset_id AND f.session_id=? AND f.asset_type='network'";
            }
        }
        else
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "REPLACE INTO user_component_filter ($query)";

        $params = array(
            session_id(),
            session_id()
        );

        $rs = $conn->Execute($query, $params);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $conn->Affected_Rows();
    }


    /**
    * This function saves the asset selected into the table user_component_filter
    *
    * @param object  $conn     Database access object
    * @param string  $type     Filter Type: asset, network or group
    * @param string  $filter   Whether or not save the entire filter or assets selected manually
    * @param string  $assets   Array of assets to save if $filter is FALSE
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function save_list_selection($conn, $type, $filter, $assets = array())
    {
        Ossim_db::check_connection($conn);

        $tables = array(
            'asset'   => 'host',
            'network' => 'net',
            'group'   => 'host_group'
        );

        $host_perms = Asset_host::get_perms_where('p.', TRUE);
        $net_where  = Asset_net::get_perms_where('p.', TRUE);

        $perms = array(
            'asset'   => "WHERE 1 $host_perms",
            'network' => "WHERE 1 $net_where",
            'group'   => ", host_group_reference r, host h WHERE p.id=r.host_group_id AND r.host_id=h.id ". str_replace("p.", "h.", $host_perms)
        );

        //Storing All the Assets from a Filter
        if ($filter)
        {
            $all = TRUE;

            self::clean_selection($conn, $type);

            $filter_list = self::retrieve_filter_list_session();

            if ($filter_list instanceof Filter_list && $filter_list->get_num_filter_added() > 0)
            {
                $all = FALSE;
            }

            if ($all)
            {
                $table  = $tables[$type];
                $perm   = $perms[$type];

                $query  = "REPLACE INTO user_component_filter ( SELECT ?, p.id, ? FROM $table p $perm )";

                $params = array(
                    session_id(),
                    $type
                );
            }
            else
            {
                $perm   = ($host_perms) ? ", host p WHERE t.session_id=? AND t.asset_id=p.id $host_perms" : " WHERE t.session_id=?";

                $query  = "REPLACE INTO user_component_filter ( SELECT ?, t.asset_id, ? FROM user_host_filter t $perm )";

                $params = array(
                    session_id(),
                    $type,
                    session_id()
                );
            }

            $rs = $conn->Execute($query, $params);

            if ($rs === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = $conn->Affected_Rows();


        }
        else  //Storing Selected Assets
        {
            $total = self::save_items($conn, $type, $assets);
        }

        return $total;
    }
}

