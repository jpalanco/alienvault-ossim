<?php
/**
* devices.inc
*
* File devices.inc is used to:
*   - To manage devices
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


class Devices
{     
    /**
    * Device
    *
    * @var array
    * @access private
    */ 
    private $devices;
    
    /**
    * Number of devices in DB
    *
    * @var integer
    * @access private
    */ 
    private $total;   
    
    /**
    * Devices filter
    *
    * @var array
    * @access private
    */ 
    private $filters;
    
    /**
    * BBDD Cache
    *
    * @var boolean
    * @access private
    */
    private $cache;
  
    
    /**
    * Class constructor
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information 
    *
    * @throws Exception  If there is a connection error
    *
    * This function sets up the class
    *
    */  
    public function __construct($conn, $filters = array(), $cache = TRUE)
    {                    
        $this->devices = array();
        
        $this->total   = 0;
        
        $this->cache   = $cache;
        
        $this->set_filters($conn, $filters);
    }
    
    
    /**
    * This function returns the devices
    *
    * @return array
    */  
    public function get_devices() 
    {
        return $this->devices;
    }
    
    
    /**
    * This function returns total of software in DB
    *
    * @return integer
    */  
    public function get_total() 
    {
        return $this->total;
    }
    
    
    /**
    * This function returns the devices filter
    *
    * @return array
    */  
    public function get_filters() 
    {
        return $this->filters;
    }
        
    
    /**
    * This function sets devices filter
    *
    * @param object  $conn     Database access object
    * @param array   $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @throws Exception  If there is a connection error
    *
    * @return void
    */  
    public function set_filters($conn, $filters = array()) 
    {
         $this->filters = $filters; 
        
         $this->load_from_db($conn);
    }
    
    
    /**
    * This function returns total of devices in DB
    * 
    * @param integer  $total   Total of devices in DB
    *
    * @return void
    */  
    private function set_total($total) 
    {
        return $this->total = intval($total);
    }


    /**
    * This function sets the devices from database
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If there is a connection error
    *
    * @return void
    */ 
    private function load_from_db($conn)
    {
        Ossim_db::check_connection($conn);
        
        $filters = $this->get_filters();
        
        $this->devices = array();
        
        
        //Build SQL                
                        
        $q_select = 'device_types.*';
        $q_where  = '';
        

        if (!empty($filters['where']))
        {
            $q_where  .= 'WHERE '.$filters['where'];
        }
        
        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }
        else
        {
            $q_where  .= ' ORDER BY class, id ASC';
        }
        
        if (!empty($filters['limit']))
        {
            $q_select  = 'SQL_CALC_FOUND_ROWS  device_types.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }
       
                       
        $query  = "SELECT $q_select FROM device_types $q_where";
       
        $rs = ($this->cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        // Has LIMIT      
        if (!empty($filters['limit']))  
        {                 
            $this->set_total(Ossim_db::get_found_rows($conn, $query, $this->cache));
        }
        else
        {
            $this->set_total($rs->RecordCount());
        }        
              
        while (!$rs->EOF)
        {
            if ($rs->fields['class'] == 0)
            {
                $this->devices[$rs->fields['id']] = array(
                    "name"     => $rs->fields['name'], 
                    'subtypes' => array()
                );
            }
            else
            {
                $this->devices[$rs->fields['class']]['subtypes'][$rs->fields['id']] = $rs->fields['name'];
            }
        
            $rs->MoveNext();
        } 
    }
    
    
    /**
    * This function translate a NMAP device to Alienvault device 
    *
    * @param string $nmap_device NMAP device type    
    *
    * @return array      
    */
    public static function translate_nmap_device($nmap_device_type)
    {
        $nmap_device_types = array(
            'telecom-misc'      => array('type' => 1, 'subtype' => 0),
            'proxy server'      => array('type' => 1, 'subtype' => 105),
            'PBX'               => array('type' => 1, 'subtype' => 106),
            'print server'      => array('type' => 1, 'subtype' => 107),
            'terminal server'   => array('type' => 1, 'subtype' => 108),
            'VoIP adapter'      => array('type' => 1, 'subtype' => 109),
            'phone'             => array('type' => 3, 'subtype' => 301),
            'PDA'               => array('type' => 3, 'subtype' => 304),
            'VoIP phone'        => array('type' => 3, 'subtype' => 305),
            'network device'    => array('type' => 4, 'subtype' => 0),
            'router'            => array('type' => 4, 'subtype' => 401),
            'switch'            => array('type' => 4, 'subtype' => 402),
            'WAP'               => array('type' => 4, 'subtype' => 404),
            'bridge'            => array('type' => 4, 'subtype' => 405),
            'broadband router'  => array('type' => 4, 'subtype' => 406),
            'remote management' => array('type' => 4, 'subtype' => 407),
            'storage-misc'      => array('type' => 4, 'subtype' => 408),
            'hub'               => array('type' => 4, 'subtype' => 409),
            'load balancer'     => array('type' => 4, 'subtype' => 410),
            'firewall'          => array('type' => 4, 'subtype' => 411),
            'printer'           => array('type' => 5, 'subtype' => 501),
            'webcam'            => array('type' => 5, 'subtype' => 502),
            'terminal'          => array('type' => 5, 'subtype' => 503),
            'security-misc'     => array('type' => 7, 'subtype' => 0),
            'media device'      => array('type' => 8, 'subtype' => 0),
            'game console'      => array('type' => 8, 'subtype' => 801),
            'specialized'       => array('type' => 9, 'subtype' => 0),
            'general purpose'   => array('type' => 9, 'subtype' => 0)
        );
        
        if (array_key_exists($nmap_device_type, $nmap_device_types))
        {
            return $nmap_device_types[$nmap_device_type];
        }
        
        return array('type' => 0, 'subtype' => 0);
    }
    
    
    /**
    * This function returns the device list as array of type-subtype
    *
    * @param object  $conn    Database access object
    * @param string  $where   SQL where
    * @param string  $limit   SQL limit
    * @param boolean $cache   [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array  list of devices and total of elements
    *
    * @extra  This functions is used in the asset search list
    */
    public static function get_all_for_filter($conn, $filters = array(), $cache = FALSE)
    {       
        Ossim_db::check_connection($conn);
        
        $where = '';
        $limit = '';
        
        
        if ($filters['where'] != '')
        {
            $where  = 'WHERE ' . $filters['where'];
        }
        
        if ($filters['limit'] != '')
        {
            $limit  = 'LIMIT ' . $filters['limit'];
        }
        
        $query  = "SELECT SQL_CALC_FOUND_ROWS * FROM
                   (
                       SELECT DISTINCT d1.id AS type_id, d1.name AS type_name, class AS subtype_id, '' AS subtype_name
                            FROM device_types d1
                            WHERE d1.class =0
                            
                       UNION DISTINCT
                       
                       SELECT DISTINCT d1.id AS type_id, d1.name AS type_name, d2.id AS subtype_id, d2.name AS subtype_name
                            FROM device_types d1, device_types d2
                            WHERE d1.id = d2.class
                            
                    ) AS q 
                    $where ORDER BY type_name, subtype_name $limit";


        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        $total = Ossim_db::get_found_rows($conn, $query, $cache);
        
        $devices = array();
        
        while (!$rs->EOF)
        {
            $devices[$rs->fields['type_id'].':'.$rs->fields['subtype_id']] = array(
                'type_id'      => $rs->fields['type_id'],
                'type_name'    => $rs->fields['type_name'],
                'subtype_id'   => $rs->fields['subtype_id'],
                'subtype_name' => $rs->fields['subtype_name']
            );
        
            $rs->MoveNext();
        }  
        
        return array($devices, $total);
    }     
    
    
    /**
    * This function returns the device name
    *
    * @param object  $conn    Database access object
    * @param string  $id      SQL where
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string  Device name or empty string
    *
    */
    public static function get_name_by_id($conn, $id)
    {       
        Ossim_db::check_connection($conn);    
        
        if ($id == '')
        {
            return '';
        }    
        
        $name   = '';
                                
        $query  = 'SELECT name FROM device_types WHERE id = ?';
                        
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if ($rs->fields['name'] != '') 
        {
            $name = $rs->fields['name'];
        }
     
        return $name;   
    }
    
}

/* End of file devices.inc */
/* Location: ../include/classes/devices.inc */