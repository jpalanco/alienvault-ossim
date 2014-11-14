<?php
/**
* software.inc
*
* File software.inc is used to:
*   - To manage software
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


class Software
{
    /**
    * Software
    *
    * @var array
    * @access private
    */
    private $software;

    /**
    * Number of software in DB
    *
    * @var integer
    * @access private
    */
    private $total;

    /**
    * Software filter
    *
    * @var array
    * @access private
    */
    private $filters;


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
        $this->software = array();

        $this->total = 0;

        $this->set_filters($conn, $filters, $cache);
    }


    /**
    * This function returns the software
    *
    * @return array
    */
    public function get_software()
    {
        return $this->software;
    }


    /**
    * This function returns total of devices in DB
    *
    * @return integer
    */
    public function get_total()
    {
        return $this->total;
    }


    /**
    * This function returns the software filter
    *
    * @return array
    */
    public function get_filters()
    {
        return $this->filters;
    }


    /**
    * This function sets software filter
    *
    * @param object  $conn     Database access object
    * @param array   $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @throws Exception  If there is a connection error
    *
    * @return void
    */
    public function set_filters($conn, $filters = array(), $cache = TRUE)
    {
         $this->filters = $filters;

         $this->load_from_db($conn, $cache);
    }


    /**
    * This function returns total of software in DB
    *
    * @param integer  $total   Total of software in DB
    *
    * @return void
    */
    private function set_total($total)
    {
        return $this->total = intval($total);
    }


    /**
    * This function sets the software from database
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If there is a connection error
    *
    * @return void
    */
    private function load_from_db($conn, $cache = TRUE)
    {
        Ossim_db::check_connection($conn);

        $filters = $this->get_filters();

        $this->software = array();

        //Build SQL

        $q_select = 'software_cpe.*';
        $q_where  = '';


        if (!empty($filters['where']))
        {
            $q_where  .= 'WHERE '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_select  = 'SQL_CALC_FOUND_ROWS  software_cpe.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }


        $query  = "SELECT $q_select FROM software_cpe $q_where";

        $rs     = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
        	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Has LIMIT
        if (!empty($filters['limit']))
        {
            $this->set_total(Ossim_db::get_found_rows($conn, $query, $cache));
        }
        else
        {
            $this->set_total($rs->RecordCount());
        }

        while (!$rs->EOF)
        {
        	$this->software[$rs->fields['cpe']] = array(
        	    'cpe'    => $rs->fields['cpe'],
                'banner' => $rs->fields['banner'],
                'name'   => $rs->fields['name'],
                'line'   => $rs->fields['line'],
                'plugin' => $rs->fields['plugin']
            );

        	$rs->MoveNext();
        }
    }


    /**
    * This function returns the value of field from CPE
    *
    * @param object  $conn   Database access object
    * @param string  $cpe    Common Platform Enumeration
    * @param string  $field  Field to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_info($conn, $cpe, $field = 'line')
    {
        Ossim_db::check_connection($conn);

        $allowed_fields = array(
            'name',
            'version',
            'line',
            'plugin'
        );

        if (!in_array($field, $allowed_fields))
        {           
            $exp_msg = _('Error! Field not allowed');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query  = "SELECT $field FROM software_cpe WHERE cpe LIKE ?";

        $params = array ($cpe);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $rs->fields[$field];
    }


    /**
    * This function returns distinct available vendors from software_cpe table
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_hardware_vendors($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $vendors = array();

        $sql  = "SELECT DISTINCT substring_index(cpe, ':', 3) as cpe, vendor FROM software_cpe WHERE plugin!='' GROUP BY vendor ORDER BY vendor";

        $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $vendors[$rs->fields['cpe']] = $rs->fields['vendor'];

            $rs->MoveNext();
        }

        return $vendors;
    }


    /**
    * This function returns distinct available models by vendor cpe from software_cpe table
    *
    * @param object  $conn   Database access object
    * @param string  $cpe    Vendor cpe
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_models_by_cpe($conn, $cpe, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $models  = array();

        $sql   = "SELECT DISTINCT substring_index(cpe, ':', 4) as mcpe, name FROM software_cpe WHERE cpe LIKE ? AND plugin!='' GROUP BY mcpe ORDER BY name";
        $param = array( preg_replace("/\/[aho]/",'/_',$cpe) . '%' );

        $rs = ($cache) ? $conn->CacheExecute($sql, $param) : $conn->Execute($sql, $param);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        /*
        if (!$rs->EOF)
        {
            // Fill Unknown
            $unknown_cpe          = $conn->GetOne("SELECT substring_index(?, ':', 3) as cpe", $rs->fields['cpe']);
            $models[$unknown_cpe] = _('Unknown');
        }
        */

        while (!$rs->EOF)
        {
            $models[$rs->fields['mcpe']] = $rs->fields['name'];

            $rs->MoveNext();
        }

        return $models;
    }


    /**
    * This function returns distinct available cersions by vendor cpe from software_cpe table
    *
    * @param object  $conn   Database access object
    * @param string  $cpe    Model cpe
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_versions_by_cpe($conn, $cpe, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $versions  = array();

        $sql   = "SELECT DISTINCT cpe, version FROM software_cpe WHERE cpe LIKE ? AND plugin!='' ORDER BY version";
        $param = array( preg_replace("/\/[aho]/",'/_',$cpe) . '%' );

        $rs = ($cache) ? $conn->CacheExecute($sql, $param) : $conn->Execute($sql, $param);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $versions[$rs->fields['cpe']] = (preg_match('/%\d/',$rs->fields['version'])) ? urldecode($rs->fields['version']) : $rs->fields['version'];

            $rs->MoveNext();
        }

        return $versions;
    }

    /**
    * This function return documentation link based on vendor and model
    *
    * @param object  $conn   Database access object
    * @param string  $vendor Vendor name
    * @param string  $model  Model name
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_documentation_link($conn, $vendor, $model, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $link = '';

        $sql   = "SELECT link FROM software_cpe_links WHERE vendor=? AND ? like CONCAT(model,'%')";
        $param = array($vendor,$model);

        $rs = ($cache) ? $conn->CacheExecute($sql, $param) : $conn->Execute($sql, $param);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
        	$link    = $rs->fields['link'];
        	$default = FALSE;
        }
        else
        {
        	// Get default documentation link
	        $sql = "SELECT link FROM software_cpe_links WHERE vendor='' AND model=''";

	        $rs  = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);

	        if (!$rs)
	        {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	        }
	        if (!$rs->EOF)
			{
	        	$link    = $rs->fields['link'];
	        	$default = TRUE;
	        }
        }

        return array($link, $default);
    }
    
    
    /**
    * This function returns a filtered software list 
    *
    * @param object   $conn    Database access object
    * @param string   $cpe     Model cpe
    * @param boolean  $cache   [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_vmv_by_cpe($conn, $cpe, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
                       
        $vmv = array();
        
        $query  = "SELECT * FROM software_cpe WHERE cpe LIKE ? ORDER BY cpe DESC LIMIT 1";
        $params = array($cpe."%");
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if (is_array($rs->fields)) 
        {
            $_name    = (substr_count($cpe, ':') < 3) ? '' : $rs->fields['name'];
            $_version = (substr_count($cpe, ':') < 4) ? '' : $rs->fields['version'];
                     
            $vmv = array(            
                'name'    => $_name,
                'line'    => $rs->fields['line'],
                'vendor'  => $rs->fields['vendor'],
                'version' => $_version,
                'plugin'  => $rs->fields['plugin']
            );          
        } 
               
        return $vmv; 
    }
        
}

/* End of file software.inc */
/* Location: ../include/classes/software.inc */