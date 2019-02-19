<?php
/**
* asset_filter.inc
*
* File Filter_group_list.inc is used to:
*   - Manage group filter collection
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
* Group Filter List Class
*
* Class for managing asset filters
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_group_list extends Filter_list
{

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
        $this->_valid_filters = array(
            1  => TRUE,     //asset_created
            2  => TRUE,     //asset_updated
            3  => TRUE,     //alarms
            4  => TRUE,     //events
            5  => TRUE,     //vulnerabilities
            6  => TRUE,     //asset_value
            8  => TRUE,     //device_type
            9  => TRUE,     //software
            10 => TRUE,     //port_service
            11 => TRUE,     //ip
            12 => TRUE,     //fqdn
            13 => TRUE,     //location
            14 => TRUE,     //sensor
            19 => TRUE,     //labels
            20 => TRUE,     //os
            21 => TRUE,     //model
            22 => TRUE,     //group name
            25 => TRUE,     //plugin
        );
        
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
                
                if ($this->_valid_filters[$f_id] && class_exists($filter_class))
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

        $proc   = 'CALL host_filter(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);';
        
        $params = array
        (
            session_id(),
            'group',
            $this->_force_reload,
            $filter_values[4],
            $filter_values[3],
            $filter_values[5]['from'],
            $filter_values[5]['to'],
            '',
            $filter_values[6]['from'],
            $filter_values[6]['to'],
            $filter_values[1]['from'],
            $filter_values[1]['to'],
            $filter_values[2]['from'],
            $filter_values[2]['to'],
            $filter_values[12],
            $filter_values[11],
            '',
            '',
            $filter_values[19],
            $filter_values[20],
            $filter_values[21],
            $filter_values[9],
            $filter_values[8],
            $filter_values[10],
            $filter_values[14],
            $filter_values[13],
            $filter_values[22],
            '',
            '',
            $filter_values[25],
            ''
        );

        /*  
            PROCEDURE PARAMS
            IN login VARCHAR(64), -- string like - 'admin'
            IN ftype VARCHAR(16), -- string - 'host' 'group' 'network'
            IN drop_table INT, -- boolean value - 0 or 1
            IN events_filter INT, -- boolean value - 0 or 1
            IN alarms_filter INT, -- boolean value - 0 or 1
            IN vulns_from INT, -- integer between 1 and 7
            IN vulns_to INT, -- integer between 1 and 7 >= vuln_from
            IN nagios CHAR, -- integer 0 => not configured, 1 => up, 2 => down
            IN asset_value_from CHAR, -- interger between 0 and 5
            IN asset_value_to CHAR, -- interger between 0 and 5 >= asset_value_from
            IN last_added_from VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
            IN last_added_to VARCHAR(19), -- datetime - '2013-07-15 08:00:00'
            IN last_updated_from VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
            IN last_updated_to VARCHAR(19), -- datetime - '2013-08-15 22:30:00'
            IN fqdn TEXT, -- free string (% is allowed)
            IN ip_range TEXT, -- ip ranges 192.168.1.1,192.168.1.255;192.168.1.2,192.168.1.2
            IN networks TEXT, -- network hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN agroups TEXT, -- asset group hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN labels TEXT, -- tag hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN os TEXT, -- unquoted string - windows vista,linux debian
            IN model TEXT, -- unquoted string - cisco asa,realtek x5
            IN cpe TEXT, -- unquoted string - cpe:/o:yamaha:srt100:10.00.46,cpe:/o:microsoft:virtual_machine_manager:2007
            IN device_types TEXT, -- unquoted string typeid,subtypeid - 1,0;4,404
            IN services TEXT, -- quoted string port,protocol,'service' - 80,6,'http';0,1,'PING'
            IN sensors TEXT, -- sensor hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN locations TEXT, -- location hex uuid value list - 0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D
            IN group_name TEXT, -- free string (% is allowed)
            IN net_name TEXT, -- free string (% is allowed)
            IN net_cidr TEXT, -- free string (% is allowed)
            IN plugins TEXT, -- unquoted string - 4003,1001,7001
            IN hids_filter CHAR -- integer 0 => not deployed, 1 => disconnected, 2 => connected
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

}
