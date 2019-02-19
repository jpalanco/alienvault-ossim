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
            $exp_msg = _('Error! Asset ID is not valid');

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
            $exp_msg = _('Error! Asset software could not be saved');

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
    * @param object  $conn            Database access object
    * @param string  $host_id         Host ID
    * @param array   $s_data          Software data
    * @param boolean $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function save_software_in_db($conn, $host_id, $s_data, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the software
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

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


            if ($report_changes == TRUE)
            {
                try
                {
                    //For now, it is only necessary to refresh cache
                    Util::memcacheFlush();
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }

        return TRUE;
    }

    /**
    * This function saves one software into database for the bulk edition
    *
    * @param object  $conn            Database access object
    * @param array   $s_data          Software data
    * @param boolean $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function bulk_save_software_in_db($conn, $s_data, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $tmptable = Util::create_tmp_table($conn, 'host_id binary(16) NOT NULL, last_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, cpe varchar(255) NOT NULL, banner text, source_id int(11) DEFAULT NULL, extra text, PRIMARY KEY (host_id, cpe)');

        $query = "REPLACE INTO $tmptable (host_id, cpe, banner, last_modified, source_id, extra)
                        SELECT uf.asset_id, ?, ?, ?, ?, ? FROM user_component_filter uf, host h
                        WHERE uf.session_id=? AND uf.asset_type='asset' AND h.id=uf.asset_id $host_perms_where";

        $extra = (is_array($s_data['extra'])) ? json_encode($s_data['extra']) : '';

        $params = array(
            $s_data['cpe'],
            $s_data['banner'],
            gmdate('Y-m-d H:i:s'),
            $s_data['source_id'],
            $extra,
            session_id()
        );

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "REPLACE INTO host_software (host_id, cpe, banner, last_modified, source_id, extra)
                  SELECT host_id, cpe, banner, last_modified, source_id, extra FROM $tmptable";

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        if ($report_changes == TRUE)
        {
            try
            {
                //For now, it is only necessary to refresh cache
                Util::memcacheFlush();
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
    * This function deletes one software from database
    *
    * @param object  $conn             Database access object
    * @param string  $host_id          Host ID
    * @param string  $cpe              Common Platform Enumeration
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_software_from_db($conn, $host_id, $cpe, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the software
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query  = 'DELETE FROM host_software WHERE host_id = UNHEX(?) AND cpe = ?';
        $params = array ($host_id, $cpe);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                //For now, it is only necessary to refresh cache
                Util::memcacheFlush();
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
    * This function deletes one software from database for the bulk edition
    *
    * @param object  $conn             Database access object
    * @param string  $cpe              Common Platform Enumeration
    * @param boolean $report_changes   [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function bulk_delete_software_from_db($conn, $cpe, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $params = array(
            $cpe,
            session_id()
        );

        $tmptable = Util::create_tmp_table($conn, 'id binary(16) NOT NULL, cpe varchar(255) NOT NULL, PRIMARY KEY ( id, cpe )');

        $conn->Execute("REPLACE INTO $tmptable SELECT asset_id, hs.cpe FROM user_component_filter uf, host h, host_software hs WHERE h.id=hs.host_id AND hs.cpe = ? AND uf.session_id=? AND h.id=uf.asset_id AND uf.asset_type='asset' $host_perms_where", $params);

        $query  = "DELETE hs FROM host_software hs, $tmptable tmp WHERE hs.host_id=tmp.id AND hs.cpe=tmp.cpe";

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                //For now, it is only necessary to refresh cache
                Util::memcacheFlush();
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
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
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

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
    * This function deletes all software from database for the bulk edition
    *
    * @param object  $conn     Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function bulk_delete_all_from_db($conn)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $params = array(
            session_id()
        );

        $tmptable = Util::create_tmp_table($conn, 'id binary(16) NOT NULL, PRIMARY KEY ( id )');

        $conn->Execute("REPLACE INTO $tmptable SELECT asset_id FROM user_component_filter uf, host h WHERE uf.session_id=? AND h.id=uf.asset_id AND uf.asset_type='asset' $host_perms_where", $params);

        $query  = "DELETE hs FROM host_software hs, $tmptable tmp WHERE hs.host_id=tmp.id";

        $rs = $conn->Execute($query);

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
            host_source_reference.id AS source_id, host_source_reference.name AS source_name, host_source_reference.relevance AS source_relevance';

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
            $_cpe = array(
                'cpe'     => $rs->fields['cpe'],
                'name'    => $rs->fields['banner'],
                'banner'  => $rs->fields['banner'],
                'extra'   => self::decode_extra($rs->fields['extra']),
                'source'  => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                ),
                'date' => $rs->fields['last_modified']
            );
            
            if ($filters['response_type'] == 'by_cpe')
            {
                $software[$rs->fields['cpe']][$rs->fields['h_id']] = $_cpe;
            }
            else
            {
                $software[$rs->fields['h_id']][$rs->fields['cpe']] = $_cpe;
            }
            
            $rs->MoveNext();
        }

        return array($software, $total);
    }


    /**
    * This function returns the common software for all hosts which match with current filter database grouped by cpe
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function bulk_get_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $total_selection = Filter_list::get_total_selection($conn, 'asset');
        $software        = array();
        $total           = 0;

        //Build SQL
        $host_perms_where = Asset_host::get_perms_where('h.', TRUE);

        $params = array(
            session_id(),
            $total_selection
        );

        $cnt      = '';
        $q_where  = $host_perms_where;
        $q_order  = '';
        $q_limit  = '';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_order .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $cnt      = 'SQL_CALC_FOUND_ROWS ';
            $q_limit .= ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $query = ossim_query("SELECT $cnt hs.cpe, hs.banner, hs.extra, count(distinct hs.host_id) as total, hsf.id AS source_id, hsf.name AS source_name
                              FROM host_software hs, user_component_filter uf, host h, host_source_reference hsf
                              WHERE h.id=uf.asset_id AND uf.session_id=? AND hs.host_id=uf.asset_id AND hsf.id=hs.source_id AND uf.asset_type='asset' $q_where
                              GROUP BY hs.cpe
                              HAVING total = ?
                              $q_order
                              $q_limit");

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
            $software[$rs->fields['cpe']] = array(
                'cpe'     => $rs->fields['cpe'],
                'banner'  => $rs->fields['banner'],
                'extra'   => self::decode_extra($rs->fields['extra']),
                'source'  => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                )
            );

            $rs->MoveNext();
        }

        return array($software, $total);
    }


    /**
     * This function gets the software readable name by CPE
     *
     * @param object   $conn     Database access object
     * @param string   $cpe      CPE String
     * @param boolean  $cache    [Optional] Use cached information
     *
     * @return string
     */
    public static function get_software_name_by_cpe($conn, $cpe, $cache = TRUE)
    {
        $software_name = '';

        $query  = "SELECT name, line, vendor FROM software_cpe WHERE cpe LIKE ? LIMIT 1";
        $params = array($cpe.'%');

        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            if (substr_count($cpe, ':') == 3) // vendor y model
            {
                $software_name = $rs->fields['vendor'] . ' ' . $rs->fields['name'];
            }
            elseif (substr_count($cpe, ':') == 2) // only vendor
            {
                $software_name = $rs->fields['vendor'];
            }
            else
            {
                $software_name = $rs->fields['line'];
            }
        }
        else
        {
            $_cpe             = explode(':', $cpe);
            $_vendor          = urldecode($_cpe[2]);
            $_model           = str_replace('_', ' ', urldecode($_cpe[3]));
            $_version         = urldecode($_cpe[4]);

            $software_name    = trim(ucwords($_vendor) . ' ' . ucwords($_model) . ' ' . $_version);
        }

        return $software_name;
    }
}

/* End of file asset_host_software.inc */
/* Location: ../include/classes/asset_host_software.inc */
