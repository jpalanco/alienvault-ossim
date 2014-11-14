<?php
/**
* asset_host_software.inc
*
* File asset_host_software.inc is used to:
*   - To manage host software
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


class Asset_host_software
{
    /**
    * Host ID
    *
    * @var string
    * @access private
    */ 
    private $host_id;    
        
    /**
    * Software associated with the host
    *
    * @var array
    * @access private
    */ 
    private $software;
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $host_id  Host ID
    */  
    public function __construct($host_id)
    {              
        $this->set_host_id($host_id);
        $this->software = array();  
    }
    
        
    /**
    * This function returns the host ID
    *
    * @return string
    */      
    public function get_host_id()
    {
        return $this->host_id;
    }
      
        
    /**
    * This function returns the software associated with the host
    *
    * @return array
    */  
    public function get_software() 
    {
        return $this->software;
    }
    
    
    /**
    * This function sets the host ID
    *
    * @param string $host_id  Host ID
    *
    * @throws Exception  If host ID is not valid
    *
    * @return void
    */
    public function set_host_id($host_id)
    {
        if (!empty($host_id) && valid_hex32($host_id))
        {
            $this->host_id = strtoupper($host_id);
        }
        else
        {
            $exp_msg = _('Error! Host ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets host software
    *
    * @param array $software  Host software
    *
    * @return void
    */  
    public function set_software($software) 
    {
        $this->software = array();
        
        if (is_array($software) && !empty($software))
        {                        
            foreach($software as $s_data)
            {
                $this->add_new($s_data);
            }
        }
    }
    
    
    /**
    * This function sets the host software from database
    *
    * @param object  $conn   Database access object     
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If host ID doesn't exists in the System or there is a connection error
    *
    * @return void
    */      
    public function load_from_db($conn, $cache = FALSE) 
    {
        Ossim_db::check_connection($conn);
                    
        $query = 'SELECT host_software.*, software_cpe.line, software_cpe.name, software_cpe.version,
            host_source_reference.name AS source_name, HEX(host_software.host_id) AS host_id
            FROM host_software LEFT JOIN software_cpe ON software_cpe.cpe = host_software.cpe 
            LEFT JOIN host_source_reference ON host_software.source_id = host_source_reference.id
            WHERE host_id = UNHEX(?) ORDER BY last_modified DESC';
            
        $params = array($this->host_id);
        
        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
        
        while (!$rs->EOF) 
        {               
            $s_data['cpe']         = $rs->fields['cpe'];
            $s_data['banner']      = $rs->fields['banner'];
            $s_data['date']        = $rs->fields['last_modified'];
            $s_data['source_id']   = $rs->fields['source_id'];
            $s_data['source_name'] = $rs->fields['source_name'];
            $s_data['extra']       = self::decode_extra($rs->fields['extra']);
            $s_data['name']        = $rs->fields['name'];
            $s_data['version']     = $rs->fields['version'];
            $s_data['line']        = $rs->fields['line'];
                        
            $this->add_new($s_data);
                           
            $rs->MoveNext();
        }   
    }
    
    
    /**
    * This function adds a new software to host
    *
    * @param array  $s_data  Software data
    *
    * @return boolean
    */ 
    public function add_new($s_data)
    {                
        if (!empty($s_data['cpe']))
        {
            $s_data['source_id'] = intval($s_data['source_id']);
        
            if (empty($s_data['source_id']))
            {
                $s_data['source_id']   = 2;
                $s_data['source_name'] = 'MANUAL';
            }

            $this->software[$s_data['cpe']] = array(
                'cpe' => $s_data['cpe'],
                'banner' => $s_data['banner'],
                'date'   => $s_data['date'],
                'source' => array(
                   'id'   => $s_data['source_id'],
                   'name' => $s_data['source_name'],
                ),
                'extra'   => $s_data['extra'],
                'name'    => $s_data['name'],
                'version' => $s_data['version'],
                'line'    => $s_data['line']
            );
            
            return TRUE;
        }
        
        return FALSE;
   }
    
    
    /**
    * This function deletes software from the host
    *
    * @param string  $cpe   Common Platform Enumeration
    *
    * @return boolean
    */ 
    public function delete($cpe)
    {               
        if (array_key_exists($cpe, $this->software))
        {
            unset($this->software[$cpe]);
            
            return TRUE;
        }
        
        return FALSE;
    }
        
    
    /**
    * This function saves all software into database
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
               
        //Begin transaction
        $conn->StartTrans();
                           
        self::delete_all_from_db($conn, $this->host_id);
        
        foreach($this->software as $cpe => $s_data)
        {                       
            $query  = 'REPLACE INTO host_software (host_id, cpe, banner, last_modified, source_id, extra) VALUES (UNHEX(?), ?, ?, ?, ?, ?)';
                                
            $params = array(
                $this->host_id,
                $cpe,
                $s_data['banner'],
                gmdate('Y-m-d H:i:s'),
                $s_data['source']['id'],
                json_encode($s_data['extra'])
            );
                        
            $conn->Execute($query, $params);
        }
        
        //Finish transaction
        if (!$conn->CompleteTrans())
        {         
            $exp_msg = _('Error! Host software could not be saved');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }       
            
        return TRUE;
    }
            
    
    /*************************************************
     **************** Static functions ***************
     *************************************************/


  
    /**
    * This function takes a JSON encoded string and converts it into array.
    *
    * @param string  $data  JSON encoded string
    *
    * @return array
    */ 
    public static function decode_extra($data) 
    {
        $decoded_data = array();
        
        if (!empty($data))
        {
            $decoded_data = @json_decode($data, TRUE);
            $decoded_data = (json_last_error() == JSON_ERROR_NONE) ? $decoded_data : array();
        }
                
        return $decoded_data;
    }
    
              
    /**
    * This function saves one software into database
    *
    * @param object  $conn      Database access object
    * @param string  $host_id   Host ID
    * @param array   $s_data    Software data
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */ 
    public static function save_software_in_db($conn, $host_id, $s_data)
    {
        Ossim_db::check_connection($conn); 
        
        //Check if we can delete the software
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        } 
                           
        $host_software = new self($host_id);
        
        if ($host_software->add_new($s_data) == TRUE)
        {          
            unset($host_software);
            
            $query  = 'REPLACE INTO host_software (host_id, cpe, banner, last_modified, source_id, extra) VALUES (UNHEX(?), ?, ?, ?, ?, ?)';
            
            
            $extra = (is_array($s_data['extra'])) ? json_encode($s_data['extra']) : '';
                                
            $params = array(
                $host_id,
                $s_data['cpe'],
                $s_data['banner'],
                gmdate('Y-m-d H:i:s'),
                $s_data['source_id'],
                $extra
            );
                    
            $rs = $conn->Execute($query, $params);
        
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }    
        }
                
        return TRUE;
    }
    
    
    /**
    * This function deletes one software from database
    *
    * @param object  $conn     Database access object
    * @param string  $host_id  Host ID
    * @param string  $cpe      Common Platform Enumeration
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */ 
    public static function delete_software_from_db($conn, $host_id, $cpe)
    {
        Ossim_db::check_connection($conn);        
        
        //Check if we can delete the software
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }        
                            
        $query  = 'DELETE FROM host_software WHERE host_id = UNHEX(?) AND cpe = ?';
        $params = array ($host_id, $cpe);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 

        return TRUE;  
    }
    
    
    /**
    * This function deletes all software from database
    *
    * @param object  $conn     Database access object
    * @param string  $host_id  Host ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */    
    public static function delete_all_from_db($conn, $host_id)
    {
        Ossim_db::check_connection($conn);
        
        //Check if we can delete the software
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        $query  = 'DELETE FROM host_software WHERE host_id = UNHEX(?)';
        $params = array ($host_id);            
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                            
        return TRUE;  
    }
    
    
    /**
    * This function returns all host software from database (Grouped by cpe)
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array     
    */    
    public static function get_all($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
        
        $software    = array();
        $total       = 0;
        
        // Permissions
        $perms_where = Asset_host::get_perms_where('h.', TRUE);
        
        $from        = ($perms_where != '') ? 'host h, host_software hs' : 'host_software hs'; 
        $perms_where = ($perms_where != '') ? "WHERE hs.host_id = h.id $perms_where " : 'WHERE 1 ';
                
        $q_where     = $perms_where;
        $q_select    = 'hs.cpe, hs.banner, s.line, s.name';
        
        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }
        
        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }
        
        if (!empty($filters['limit']))
        {
            $q_select  = "SQL_CALC_FOUND_ROWS $q_select";
            $q_where  .= ' LIMIT '.$filters['limit'];
        }
        

        $query  = "SELECT DISTINCT $q_select FROM $from
                 LEFT JOIN software_cpe s ON s.cpe = hs.cpe
                 $q_where";        
                         
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
            $cpe    = $rs->fields['cpe'];
            $banner = $rs->fields['banner'];
            $name   = $rs->fields['name'];
            $line   = $rs->fields['line'];
            
            if (empty($name) || empty($line))
            {
                $_cpe     = explode(':', $cpe);
                
                $_vendor  = urldecode($_cpe[2]);
                $_model   = str_replace('_', ' ', urldecode($_cpe[3]));
                $_version = urldecode($_cpe[4]);
                
                $_name    = trim(ucwords($_vendor) . ' ' . ucwords($_model) . ' ' . $_version);
            }
            
            $name  = empty($name) ? $_name : $name;
            $line  = empty($line) ? $_name : $line;

            $software[$cpe] = array(
                'cpe'    => $cpe,
                'banner' => $banner,
                'name'   => $name,
                'line'   => $line
            );

            
            $rs->MoveNext();
        }

        return array($software, $total);
    }
          
    
    /**
    * This function returns a filtered software list 
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
        
        $software = array();
        $total    = 0;
        
        $q_select = 'HEX(h.id) AS h_id, host_software.*,
            software_cpe.name, software_cpe.version, software_cpe.line, software_cpe.vendor, software_cpe.plugin, 
            host_source_reference.id AS source_id, host_source_reference.name AS source_name';
        //Build SQL
                
        $perms_where = Asset_host::get_perms_where('h.', TRUE);
       
        $q_where  = $perms_where;
        
        
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
            $q_select  = 'SQL_CALC_FOUND_ROWS '.$q_select;
            $q_where  .= ' LIMIT '.$filters['limit'];
        }


        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $query = ossim_query("SELECT $q_select
            FROM host h, host_source_reference, host_software
            LEFT JOIN software_cpe ON software_cpe.cpe LIKE CONCAT(host_software.cpe,'%') 
            WHERE host_software.host_id = h.id AND host_source_reference.id = host_software.source_id $q_where");


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
            // If exists change "line" depends on partial cpe
            if (is_array($software[$rs->fields['h_id']][$rs->fields['cpe']]))
            {
                if (substr_count($rs->fields['cpe'], ':') == 3) // vendor y model
                {
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['line']    = $rs->fields['vendor'] . ' ' . $rs->fields['name'];
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['name']    = $rs->fields['name'];
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['version'] = '';
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['plugin']  = $rs->fields['plugin'];
                }
                elseif (substr_count($rs->fields['cpe'], ':') == 2) // only vendor
                {
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['line']    = $rs->fields['vendor'];
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['name']    = '';
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['version'] = '';
                    $software[$rs->fields['h_id']][$rs->fields['cpe']]['plugin']  = $rs->fields['plugin'];
                }
            }
            else
            {
                $_name    = (substr_count($rs->fields['cpe'], ':') < 3) ? '' : $rs->fields['name'];
                $_version = (substr_count($rs->fields['cpe'], ':') < 4) ? '' : $rs->fields['version'];
                
                $software[$rs->fields['h_id']][$rs->fields['cpe']] = array(
                    'cpe'     => $rs->fields['cpe'],
                    'banner'  => $rs->fields['banner'],
                    'name'    => $_name,
                    'line'    => $rs->fields['line'],
                    'vendor'  => $rs->fields['vendor'],
                    'version' => $_version,
                    'plugin'  => $rs->fields['plugin'],
                    'extra'   => self::decode_extra($rs->fields['extra']),
                    'source'  => array(
                        'id'   => $rs->fields['source_id'],
                        'name' => $rs->fields['source_name'],
                    ),
                    'date' => $rs->fields['last_modified']
                );
            }
                        
            $rs->MoveNext();
        }   
              
        return array($software, $total); 
    }
        
}

/* End of file asset_host_software.inc */
/* Location: ../include/classes/asset_host_software.inc */