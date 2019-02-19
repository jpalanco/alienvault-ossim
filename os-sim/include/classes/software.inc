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
    * This function returns distinct available vendors from API
    *
    * @param string  $sensor_id   Sensor to get the plugins
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_hardware_vendors($sensor_id = 'local')
    {
        $plugins = Plugin::get_plugins_from_api(Util::uuid_format($sensor_id), array('per_asset' => TRUE));

        // Consolidate Vendors
        foreach ($plugins as $plugin_data)
        {
            $vendors[$plugin_data['vendor']] = ucfirst($plugin_data['vendor']);
        }
        
        asort($vendors);

        return $vendors;
    }


    /**
    * This function returns distinct available models by vendor from API
    *
    * @param string  $vendor      Vendor
    * @param string  $sensor_id   Sensor to get the plugins
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_models_by_vendor($vendor, $sensor_id = 'local')
    {
        $plugins = Plugin::get_plugins_from_api(Util::uuid_format($sensor_id), array('per_asset' => TRUE));
        
        // Get models by vendor from total list
        foreach ($plugins as $plugin_data)
        {
            if ($plugin_data['vendor'] == $vendor)
            {
                $models[$vendor.':'.$plugin_data['model']] = $plugin_data['model'];
            }
        }

        return $models;
    }


    /**
    * This function returns distinct available versions by vendor and model
    *
    * @param string  $vendor_model  Vendor and Model
    * @param string  $sensor_id     Sensor to get the plugins
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_versions_by_model($vendor_model, $sensor_id = 'local')
    {
        list($vendor, $model) = explode(':', $vendor_model);
        
        $plugins = Plugin::get_plugins_from_api(Util::uuid_format($sensor_id), array('per_asset' => TRUE));
        
        // Get models by vendor from total list
        foreach ($plugins as $plugin_data)
        {
            if ($plugin_data['vendor'] == $vendor && $plugin_data['model'] == $model)
            {
                $versions[$vendor.':'.$plugin_data['model'].':'.$plugin_data['version']] = $plugin_data['version'];
            }
        }

        return $versions;
    }


    /**
    /**
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

        $query = "SELECT link FROM software_cpe_links WHERE vendor=? AND ? like CONCAT(model,'%')";
        $param = array($vendor,$model);

        $rs = ($cache) ? $conn->CacheExecute($query, $param) : $conn->Execute($query, $param);

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
            $query = "SELECT link FROM software_cpe_links WHERE vendor='' AND model=''";

            $rs  = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

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
    
}

/* End of file software.inc */
/* Location: ../include/classes/software.inc */
