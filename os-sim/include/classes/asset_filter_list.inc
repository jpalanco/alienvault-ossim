<?php
/**
* asset_filter.inc
*
* File asset_filter_list.inc is used to:
*   - Manage asset filter collection
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
* @link       https://www.alienvault.com/ Alienvault web page
*/
class Asset_filter_list
{
    
    /**
    * Set of filters
    * @var array
    *
    */ 
    private $_filters;
    
    
    /**
    * Flag to force the group to be recalculated. (Execute the procedure with clean_host=1)
    * @var boolean
    *
    */ 
    private $_force_reload;
    
    
 
    /*
    * Class constructor
    *
    * This function sets up the class.
    * By default it loads a list of filters from DB. The filters are the ones from the table
    * asset_filter_types but the value of the filters will be empty. 
    *
    * @param  object  $conn  DB Connection 
    *
    * @throws Exception  If there is a connection or query error or if the filter is invalid
    */  
    public function __construct($conn)
    {
        $this->_filters = array();
        $query          = 'SELECT * FROM asset_filter_types';
        
        /*
        As it is the first time we load the filter, we'll have to force this flag to
        populate the table user_host_filter.
        */
        $this->_force_reload = TRUE;
        
        if ($rs = $conn->Execute($query)) 
        {
            while (!$rs->EOF) 
            {
                $f_id   = $rs->fields['id'];
                $f_name = $rs->fields['filter'];
                
                $filter_class = 'Filter_'.$f_name;
                
                if (class_exists($filter_class))
                {
                    //Creating the object Filter
                    $filter = new $filter_class($f_id);
                    
                    //Adding the object to the list.               
                    $this->_filters[$f_id] = $filter;
                }
                
                $rs->MoveNext();
            } 
        }
        else
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
    
    
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
    private function _check_valid_filter_exist($id)
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
        $query  = 'DELETE FROM user_host_filter WHERE login=?';
        $params = array
        (
            Session::get_session_user()
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
    public function apply_filter_search($conn)
    {
        $filter_values = array();
        
        //We need to get the values of each filter of the list
        foreach ($this->_filters as $id => $filter)
        {
            /*
            If the force_reload flag is true, we need the value of each filter and the whole
            process to populate the table will be achieve within the procedure.
            If the force_reload flag is false, only the affected filters will be involved in the
            process, the others filter will use their default value.
            */
            $filter_values[$id] = $filter->get_filter_sql($this->_force_reload);
        }

        $proc   = 'CALL host_filter(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);';
        
        $params = array
        (
            Session::get_session_user(),
            $this->_force_reload,
            $filter_values[4],
            $filter_values[3],
            $filter_values[5]['from'],
            $filter_values[5]['to'],
            $filter_values[6]['from'],
            $filter_values[6]['to'],
            $filter_values[1]['from'],
            $filter_values[1]['to'],
            $filter_values[2]['from'],
            $filter_values[2]['to'],
            $filter_values[12],
            $filter_values[11],
            $filter_values[7],
            $filter_values[9],
            $filter_values[8],
            $filter_values[10],
            $filter_values[14],
            $filter_values[13]
        );
        
        /*  PROCEDURE PARAMS
            IN login VARCHAR(64), # string like - 'admin' 
            IN drop_table INT, # boolean value - 0 or 1 
            IN events_filter INT, # boolean value - 0 or 1 
            IN alarms_filter INT, # boolean value - 0 or 1 
            IN vulns_from INT, # integer between 1 and 7
            IN vulns_to INT, # integer between 1 and 7 >= vuln_from
            IN asset_value_from INT, # interger between 1 and 5 
            IN asset_value_to INT, # interger between 1 and 5 >= asset_value_from
            IN last_added_from VARCHAR(19), # datetime - '2013-07-15 08:00:00'
            IN last_added_to VARCHAR(19), # datetime - '2013-07-15 08:00:00' 
            IN last_updated_from VARCHAR(19), # datetime - '2013-08-15 22:30:00'
            IN last_updated_to VARCHAR(19), # datetime - '2013-08-15 22:30:00' 
            IN fqdn VARCHAR(128), # free string (% is allowed)
            IN ip_from VARCHAR(15), # ip from value - 192.168.1.1
            IN ip_to VARCHAR(15), # ip to value - 192.168.1.255
            IN networks TEXT, # network hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN cpe TEXT, # unquoted string - cpe:/o:yamaha:srt100:10.00.46,cpe:/o:microsoft:virtual_machine_manager:2007
            IN device_types TEXT, # unquoted string typeid,subtypeid - 1,0;4,404
            IN services TEXT, # quoted string port,protocol,'service' - 80,6,'http';0,1,'PING'
            IN sensors TEXT, # sensor hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN locations TEXT # location hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
        */
        
        $rs = $conn->Execute($proc, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            /*
            If everything was right, the force reload is set up to false because next call to the procedure 
            will query against user_host_filter if we don't delete a filter or we modify a range filter.
            */
            $this->_force_reload = FALSE;
            
            $rs->free();
        }
    }

    
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
        $_SESSION['_asset_current_filters'] = serialize($this);        
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
        unset($_SESSION['_asset_current_filters']);
        
        self::delete_filter_copy();       
    }
    
    
    /**
    * This function retrieves the object stored in session
    *
    * @return  object  The filter list object or FALSE.
    */ 
    public static function retrieve_filter_list_session()
    {
        //Getting the object with the filters. Unserialize needed.
        $filter_list = unserialize($_SESSION['_asset_current_filters']);
        
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
        if (empty($_SESSION['_asset_filters_copy']))
        {
            $filters = self::retrieve_filter_list_session();
            
            if ($filters !== FALSE)
            {
                $_SESSION['_asset_filters_copy'] = serialize($filters);
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
        unset($_SESSION['_asset_filters_copy']);
    }
    
    
    /**
    * This function restore the copy of the object
    *
    * @return  void.
    */ 
    public static function restore_filter_copy()
    {
        $filters = unserialize($_SESSION['_asset_filters_copy']);

        if ($filters instanceof self)
        {
            $filters->store_filter_list_session();
        }
        
        self::delete_filter_copy();
    }
}

/* End of file asset_filter_list.inc */
/* Location: ../include/classes/asset_filter_list.inc */ 