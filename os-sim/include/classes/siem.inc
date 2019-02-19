<?php
/**
* siem.inc
*
* File siem.inc is used to:
*   - Access to alienvault_siem database siem events
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
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
 * Function list:
 * - SIEM()
 * - set_filter()
 * - get_events()
 * 
 */

class Siem 
{   
    const OTX_PULSE_SID = 29998;
    const OTX_PULSE_ID  = 1701;
    const OTX_UNKNOWN   = 'No information available. You are no longer subscribed to this pulse.';
    
    public $conn;


    public $geoloc;


    public $filters;
    
    
    public $tables;
    
    
    public $joinable_tables;
    
    
    public $perms_bypass;
    
    /**
    * Class constructor
    *
    * This function sets up the class
    */
    public function __construct($perms_bypass = FALSE)
    {
        $db            = new ossim_db();
        $this->conn    = $db->snort_connect();
        $this->av_conn = $db->connect();
        $this->geoloc  = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');
        $this->filters = array();
        $this->tables  = array();
        
        $this->joinable_tables = '/(alienvault\.plugin|alienvault\.plugin_sid|idm_data|extra_data|qry_tmp_[a-z0-9]+)/';
        $this->left_join_rules = array(
            'alienvault.plugin_sid' => 'LEFT JOIN alienvault.plugin_sid 
                ON plugin_sid.plugin_id = acid_event.plugin_id AND plugin_sid.sid = acid_event.plugin_sid',
            
            'alienvault.plugin'     => 'LEFT JOIN alienvault.plugin
                ON plugin.id = acid_event.plugin_id',
                
            'idm_data'              => 'LEFT JOIN idm_data
                ON idm_data.event_id = acid_event.id'
        );
        
        $this->perms_bypass = $perms_bypass;
        
        // Add asset perm filters for all queries
        if (!$this->perms_bypass)
        {
            $this->process_perms();
        }
    }
    
    /**
    * This function adds a new criteria to filters array, for example a signature search
    * Each filter component will be an AND clause into the final WHERE sentence
    * To implement the OR operator we must use this method once, with multiple fields
    *
    * @param string or array $field
    * @param string or array $value
    * @param string $operator
    */
    public function add_criteria($field, $value, $operator = "=")
    {
        $filter = array();
        
        // Multiple
        if (is_array($field))
        {
            for ($i = 0; $i < count($field); $i++)
            {
                $_field   = $field[$i];
                $_value   = (is_array($value)) ? $value[$i] : $value;
                $_op      = (is_array($operator)) ? $operator[$i] : $operator;
                $filter[] = $this->get_where_clause($_field, $_value, $_op);
            }
        }
        // Single
        else
        {
            $filter[] = $this->get_where_clause($field, $value, $operator);
        }
        
        $this->filters[] = $filter;
    }
    
    /**
     * This function clears the current criteria, and reload the perms criteria if necessary
     */
    public function clear_criteria()
    {
        $this->filters = array();
        
        if (!$this->perms_bypass)
        {
            $this->process_perms();
        }
    }

    
    /**
     * This function processes a filter to get the SQL string
     *
     * @param string $field
     * @param string $value
     * @param string $operator
     */
    public function get_where_clause($field, $value, $operator = "=")
    {
        // Set the JOIN table if needed
        $this->set_join_table($field);
        
        // Joined fields
        if ($this->is_joined($value))
        {
            $this->set_join_table($value);
        }
        // Subquery
        elseif ($operator == 'IN')
        {
            $value = '('.$value.')';
        }
        // When have to use binary conversion
        elseif ($this->is_binary($field))
        {
            $value = "UNHEX('$value')";
            // Common string
        } 
        else 
        {
            $value = "\"$value\"";
        }
        return "$field $operator $value";
    }
    
    /**
     * This function processes a criteria field or value to set the SQL joining tables
     *
     * @param string $field
     */
    public function set_join_table($field)
    {
        if (preg_match('/\./', $field))
        {
            $_field_sources = explode('.', $field);
            $_database      = (count($_field_sources) > 2) ? $_field_sources[0] : '';
            $_table         = (count($_field_sources) > 2) ? $_field_sources[1] : $_field_sources[0];
            $join_table     = ($_database != '') ? $_database.'.'.$_table : $_table;
            
            if (preg_match($this->joinable_tables, $join_table))
            {
                if (!in_array($join_table, $this->tables))
                {
                    $this->tables[] = $join_table;
                }
            }
            else
            {
                $_msg = _('The table is not allowed to join').': '.$join_table;
                
                Av_exception::throw_error(Av_exception::DB_ERROR, $_msg);
            }
        }
    }
    
    /**
     * This function returns if an acid_event field is a binary(16)
     *
     * @param string $field
     *
     * @return bool
     */
    public function is_binary($field)
    {
        if ($field == "src_host"
        ||  $field == "dst_host"
        ||  $field == "src_net"
        ||  $field == "dst_net"
        ||  $field == "ctx")
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    
    /**
     * This function returns if the field is a joined value
     *
     * @param string $field
     *
     * @return bool
     */
    public function is_joined($value)
    {
        if (preg_match('/^qry_tmp/', $value))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    
    /**
     * This function processes all filters (AND/OR) to get the SQL where sentence string
     *
     * @return string
     */
    public function get_filters_where()
    {
        $ors = array();
        for ($i = 0; $i < count($this->filters); $i++)
        {
            $ors[] = "(".implode(" OR ", $this->filters[$i]).")";
        }

        return (count($ors) > 0) ? " AND ".implode(" AND ", $ors) : "";
    }
    
    /**
     * This function processes all 'where' filters to get the SQL 'from' sentence string
     *
     * @return string
     */
    public function get_filters_from()
    {
        $join_string      = '';
        $left_join_string = '';
        
        foreach ($this->tables as $_table)
        {
            if ($this->left_join_rules[$_table] != '')
            {
                $left_join_string .= ' '.$this->left_join_rules[$_table];
            }
            else
            {
                $join_string .= ', '.$_table;
            }
        }
        
        return $left_join_string.$join_string;
    }
    
    
    private function process_perms()
    {
        $ctx_perms = Session::get_ctx_where();
        
        if ($ctx_perms != '')
        {
            $this->add_criteria('ctx', $ctx_perms, 'IN');
        }
        
        $host_perms = Session::get_host_where();
        $net_perms  = Session::get_net_where();
        $_cr_keys   = array();
        $_cr_vals   = array();
        
        if ($host_perms != '')
        {
            $_cr_keys[] = 'src_host';
            $_cr_keys[] = 'dst_host';
            $_cr_vals[] = $host_perms;
            $_cr_vals[] = $host_perms;
        }
        
        if ($net_perms != '')
        {
            $_cr_keys[] = 'src_net';
            $_cr_keys[] = 'dst_net';
            $_cr_vals[] = $net_perms;
            $_cr_vals[] = $net_perms;
        }
        
        if (!empty($_cr_keys))
        {
            $this->add_criteria($_cr_keys, $_cr_vals, 'IN');
        }
    }

    
    /**
     * This function executes the query to acid_event DB table and gets the events array and total
     *
     * @param int    $from   [Optional]  Start limit of the query
     * @param int    $max    [Optional]  To limit of the query
     * @param string $order  [Optional]  Database field to order by
     * @param string $torder [Optional]  Sorting direction
     *
     * @return array Event list and total of events in the query
     */
    public function get_events($from = 0, $max = 50, $order = "timestamp", $torder = "DESC")
    {
        // Look for special order fields and JOIN needed
        if ($order != 'timestamp')
        {
            $_order_fields = explode(',', $order);
            foreach ($_order_fields as $_order)
            {
                $this->set_join_table(trim($_order));
            }
        }
        
        $where = $this->get_filters_where();
        $joins = $this->get_filters_from();
    
        $sql = "SELECT *, acid_event.id AS eid
                FROM acid_event $joins
                WHERE 1 $where 
                ORDER BY $order $torder 
                LIMIT $from, $max";
        
        if (file_exists('/tmp/debug_siem'))
        {
            file_put_contents("/tmp/siem", "Siem::get_events():$sql\n", FILE_APPEND);
        }
        
        $list = array();
    
        if (!$rs = $this->conn->Execute($sql))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = $this->get_event_fields($rs->fields);

                $rs->MoveNext();
            }
        }
        
        $total = $this->get_events_total();
        
        return array($list, $total);
    }

    /**
     * This function get events from stored procedure
     *
     * @param string $id          asset uuid or tmp table name
     * @param string $type        host, group or network
     * @param int    $from        from to limit query
     * @param int    $max         number of record to limit the query
     * @param string $order       Database field to order by
     * @param string $search_str  plugin sid name filter
     *
     * @return array
     */
    public function get_events_sp($id, $type, $from, $max, $order, $search_str)
    {
        $list = array();
        
        $total = $this->get_events_total_sp($id, $type, $search_str);
        
        if ($total == 0)
        {
            return array($list, 0);
        }

        $params = array(
            $type,
            $id,
            $from,
            $max,
            $order,
            $search_str
        );
        $rs = $this->conn->Execute("CALL alienvault_siem.get_events(?, ?, ?, ?, ?, ?)", $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            $rows = array();
            while (!$rs->EOF)
            {
                $rows[] = $rs->fields;
                $rs->MoveNext();
            }
            $rs->free();
            
            foreach ($rows as $row)
            {
                $list[] = $this->get_event_fields($row);
            }
        }
        
        $total = $this->get_events_total_sp($id, $type, $search_str);

        return array($list, $total);
    }
    
    
    /**
     * This function get the total number of events from stored procedure
     *
     * @param string $id          asset uuid or tmp table name
     * @param string $type        host, group or network
     * @param string $search_str  plugin sid name filter
     *
     * @return integer
     */
    public function get_events_total_sp($id, $type, $search_str)
    {
        $total = 0;
        
        $params = array(
            $type,
            $id,
            $search_str
        );
        $rs = $this->conn->Execute("CALL alienvault_siem.get_event_count(?, ?, ?)", $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $total = intval($rs->fields['events']);
            }
            $rs->free();
        }
        
        return $total;
    }
    
    /**
     * This function executes the query to acid_event DB table and gets the events array and the total count [grouped by a field]
     *
     * @param string $groupby
     * @param int $from
     * @param int $max
     *
     * @return array(array, int)
     */
     /*****************************************************************************
     *********************** This function is not being used yet ******************
     *****************************************************************************/
    public function get_events_grouped($groupby = "signature", $from = 0, $max = 50)
    {
        $where = $this->get_filters_where();
    
        $sql = "SELECT SQL_CALC_FOUND_ROWS $groupby, count(*)
            FROM acid_event FORCE INDEX (timestamp)
            LEFT JOIN alienvault.plugin_sid
            ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
            LEFT JOIN idm_data
            ON idm_data.event_id=acid_event.id
            WHERE 1 $where GROUP BY $groupby ORDER BY timestamp DESC LIMIT $from, $max";
    
        //echo "<br>QUERY: <b>$sql</b><br>";
    
        $foundrows = 0;
        $list      = array();
    
        if (!$rs = $this->conn->Execute($sql))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            if (!$rf = $this->conn->Execute("SELECT FOUND_ROWS() AS total"))
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
            }
            else
            {
                $foundrows = $rf->fields["total"];
            }
    
            while (!$rs->EOF)
            {
                $list[] = $rs->fields;
                $rs->MoveNext();
            }
        }
    
        return array($list, $foundrows);
    }
    
    /**
     *
     * @param unknown_type $conn
     * @param unknown_type $filters
     * @return number
     */
    public function get_events_total()
    {
        $count  = 0;
        $where  = '';
        $use_ac = TRUE;
        
        $where  = $this->get_filters_where();
        $joins  = $this->get_filters_from();
    
        if (preg_match('/ossim_risk_a/', $where))
        {
            $use_ac = FALSE;
        }
    
        if ($use_ac)
        {
            $sql = "SELECT ifnull(sum(cnt),0) as total FROM ac_acid_event AS acid_event $joins WHERE 1 $where";
        }
        else
        {
            $sql = "SELECT count(acid_event.id) as total FROM acid_event $joins WHERE 1 $where";
        }
        
        
        if (file_exists('/tmp/debug_siem'))
        {
            file_put_contents("/tmp/siem", "Siem::get_events_total():$sql\n", FILE_APPEND);
        }
        
        
        $rs = $this->conn->Execute($sql);
    
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            $count = $rs->fields["total"];
        }
        
        return $count;
    }

    
    /**
     * This function detect the current timestamp filter and gets the date_from and date_to separated values
     * Used internally from self::get_graph_date, to know the range interval to use in the plot
     * 
     * @return Array: [$date_from, $date_to]
     */
    public function get_dates_by_filter()
    {
        // Default values
        $date_from = '1970-01-01';
        $date_to   = date("Y-m-d");
        
        foreach ($this->filters as $filter)
        {
            foreach ($filter as $criteria)
            {
                // Found Date To
                if (preg_match('/timestamp \<\=? "([\d\s\:\-]+)"/', $criteria, $matches))
                {
                    $date_to   = $matches[1];
                }
                
                // Found Date From
                if (preg_match('/timestamp \>\=? "([\d\s\:\-]+)"/', $criteria, $matches))
                {
                    $date_from = $matches[1];
                }
            }
        }

        return array($date_from, $date_to);
    }

    
    /**
     * This function gets the data for plot graphic in siem console and reports
     * Group automatically by the timestamp filter in the Siem object (by hours OR by days OR by months)
     *
     * @return Array plot graph data ('0h' => n, '1h' => n, ...) OR ('2 May' => n, '3 May' => n, ...) OR ...
     * 
     * WARNING: $tmpconn and $where parameters must be removed
     */
    public function get_graph_data($tmpconn, $where, $ejoin='')
    {
        // Get the current timestamp filter in the Siem object
        list($date_from, $date_to) = $this->get_dates_by_filter();
        
        // Timezone
        $tz  = Util::get_timezone();
        $tzc = Util::get_tzc($tz);
        
        // Ignore future date_to limit, force to current time
        if (strtotime($date_to) > time())
        {
            $date_to = date("Y-m-d H:i:s", time() - (3600*$tz));
        }
        
        /** Take this where in the future
         *  By now will use the Security_report::make_where
         */
        //$where = $this->get_filters_where();
        
        // Get the labels
        $diff = strtotime($date_to) - strtotime($date_from);
        
        // Left join needed for taxonomy filters
        $tax_join = (preg_match('/plugin_sid\./', $where)) ? 'LEFT JOIN alienvault.plugin_sid 
                ON acid_event.plugin_id=plugin_sid.plugin_id 
                AND acid_event.plugin_sid=plugin_sid.sid' : '';
        $tax_join .= ($ejoin != '') ? $ejoin : '';
        
        // Join needed for reputation filters
        $rep_join = (preg_match('/reputation\_data\./', $where)) ? ', alienvault_siem.reputation_data' : '';
        
        // Join needed for extra_data filters
        $extra_join = (preg_match('/extra\_data\./', $where)) ? ', alienvault_siem.extra_data' : '';

        // Join needed for reputation filters
        $otx_join = (preg_match('/otx\_data\./', $where)) ? ', alienvault_siem.otx_data' : '';

        // Database selector to fix the 'device' filter
        $where = preg_replace('/FROM device/', 'FROM alienvault_siem.device', $where);

        if ($extra_join == '' && $rep_join == '' && $otx_join == '' && !preg_match("/idm_data|ip_proto|layer4|ossim_|_hostname|_mac/",$where))
        {
            $counter = 'SUM(acid_event.cnt)';
            if (!preg_match("/ip_src|ip_dst/",$where))
            {
                $tables  = "alienvault_siem.ac_acid_event acid_event $tax_join";
            }
            else
            {
                $tables  = "alienvault_siem.po_acid_event acid_event $tax_join";
            }
        }
        else
        {
            $counter = 'COUNT(acid_event.id)';
            $tables  = "alienvault_siem.acid_event $tax_join $rep_join $otx_join $extra_join";
        }
        
        // WARNING: remove alienvault_siem database selection from query when be posible
        // more than 1 month
        if ($diff > 2678400)
        {
            $jump = 0;
            $key  = "M Y";
            
            $sqlgraph = "SELECT $counter as num_events,
            DATE_FORMAT(convert_tz(timestamp,'+00:00','$tzc'), '%b %Y') as label
            FROM $tables WHERE 1 $where GROUP BY label";
        }
        // more than 1 day
        elseif ($diff >= 86400)
        {
            $jump = 86400;
            $key  = "d M";
            
            $sqlgraph = "SELECT $counter as num_events,
            DATE_FORMAT(convert_tz(timestamp,'+00:00','$tzc'), '%d %b') as label
            FROM $tables WHERE 1 $where GROUP BY label";
        }
        // less than 1 day
        elseif ($diff < 86400)
        {
            $jump = 3600;
            $key  = "G\h";
            
            $sqlgraph = "SELECT $counter as num_events,
            CONCAT(DATE_FORMAT(convert_tz(timestamp,'+00:00','$tzc'), '%k'), 'h') as label 
            FROM $tables WHERE 1 $where GROUP BY label";
        }
        
        // Initialize the graph data
        $data   = array();
        $d      = strtotime($date_from) + (3600*$tz);
        while ($d <= strtotime($date_to) + (3600*$tz))
        {
            // Initially all values to zero
            $now            = gmdate($key, $d + (3600*$tz));
            $data["$now"]   = 0;
            
            // Next tick label
            if ($jump == 0)
            {
                $d += (date("t", $d) * 86400); // case year, get the current month days
            }
            else
            {
                $d += $jump; // next date
            }
        }

        if (file_exists('/tmp/debug_siem'))
        {
            file_put_contents("/tmp/siem", "Siem::get_graph_data():$sqlgraph\n", FILE_APPEND);
        }

        $rs = $tmpconn->Execute($sqlgraph);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $tmpconn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $data[$rs->fields['label']] = $rs->fields['num_events'];
                $rs->MoveNext();
            }
        }
        
        return $data;
    }

    
    public function get_event_fields($row)
    {
        $fields = array();
        $eid    = strtoupper(bin2hex($row['eid']));
        
        $fields["eid"] = $eid;
        
        // Extra Data
        $sql   = "SELECT * FROM extra_data WHERE event_id = UNHEX(?)";
        $rs    = $this->conn->Execute($sql, array($eid));
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $row['filename']  = $rs->fields['filename'];
                $row['username']  = $rs->fields['username'];
                $row['password']  = $rs->fields['password'];
                $row['userdata1'] = $rs->fields['userdata1'];
                $row['userdata2'] = $rs->fields['userdata2'];
                $row['userdata3'] = $rs->fields['userdata3'];
                $row['userdata4'] = $rs->fields['userdata4'];
                $row['userdata5'] = $rs->fields['userdata5'];
                $row['userdata6'] = $rs->fields['userdata6'];
                $row['userdata7'] = $rs->fields['userdata7'];
                $row['userdata8'] = $rs->fields['userdata8'];
                $row['userdata9'] = $rs->fields['userdata9'];
            }
        }
        
        
        // IDM Data
        $srcud = array();
        $dstud = array();
        $sql   = "SELECT * FROM idm_data WHERE event_id = UNHEX(?)";
        $rs    = $this->conn->Execute($sql, array($eid));
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $row["idm_username"] = trim($rs->fields["username"]);
                $row["idm_domain"]   = trim($rs->fields["domain"]);
                
                if (!empty($row["idm_username"]))
                {
                    $idm_u = $row["idm_username"];
                    $idm_d = ($row["idm_domain"] != '') ? "@".$row["idm_domain"] : '';
                
                    if (intval($row["from_src"]))
                    {
                        $srcud[] = Util::htmlentities($idm_u.$idm_d);
                    }
                    else
                    {
                        $dstud[] = Util::htmlentities($idm_u.$idm_d);
                    }
                }
            }
        }
        
        $fields["src_userdomain"] = implode(", ",$srcud);
        $fields["dst_userdomain"] = implode(", ",$dstud);

        // OTX Data
        $row['pulse'] = _(self::OTX_UNKNOWN);
        if ($row['plugin_id'] == self::OTX_PULSE_ID || $row['plugin_sid'] == self::OTX_PULSE_SID)
        {
            $sql   = "SELECT hex(pulse_id) as pulse_id FROM otx_data WHERE event_id = UNHEX(?) limit 1";
            $rs    = $this->conn->Execute($sql, array($eid));
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $otx   = new Otx();
                    $pulse = $otx->get_pulse_detail(strtolower($rs->fields['pulse_id']), TRUE);
                    if (!empty($pulse['name']))
                    {
                        $row['pulse'] = Util::htmlentities(trim($pulse['name']), ENT_NOQUOTES);
                    }
                }
            }
        }

        // Signature
        $signature = _("Unknown Event");
        if ($row['plugin_id'] != "" && $row['plugin_sid'] != "")
        {
            $sql = "SELECT name FROM alienvault.plugin_sid WHERE plugin_id = ? AND sid = ?";
            $rs  = $this->conn->Execute($sql, array($row['plugin_id'], $row['plugin_sid'])); 
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $signature = self::translate_signature($rs->fields['name'], $row);
                }
            }
        }

        $fields['signature'] = $signature;
        
        // Data Source
        $ds = _("Unknown DS");
        if ($row['plugin_id'] != "")
        {
            $sql = "SELECT name FROM alienvault.plugin WHERE id = ?";
            $rs  = $this->conn->Execute($sql, array($row['plugin_id']));
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $ds = $rs->fields['name'];
                }
            }
        }

        $fields['datasource'] = $ds;
        
        // Sensor
        $name     = "Unknown";
        $sid      = $row['device_id'];

        $multiple = (preg_match("/\,/", $sid)) ? TRUE : FALSE;
        if ($multiple)
        {
            $sid = preg_replace("/\s*\,.*/", "", $sid);
        }
        
        $sql = "SELECT ase.name FROM alienvault_siem.device avs LEFT JOIN alienvault.sensor ase ON avs.sensor_id=ase.id WHERE avs.id = ?";
        $rs  = $this->conn->Execute($sql, array($sid));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $name = ($rs->fields['name'] != "") ? $rs->fields['name'] : "N/A";
            }
        }

        $fields['sensor'] = $name;

        // Date
        $tz = Util::get_timezone();
        if ($tz != 0 && !empty($row['timestamp']))
        {
            $timestamp_utc  = strtotime($row['timestamp'].' GMT');
            $fields['date'] = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));
        }
        else
        {
            $fields['date'] = $row['timestamp'];
        }
        
        // Context
        $ctx               = strtoupper(bin2hex($row['ctx']));
        $ctx_name          = (Session::is_pro()) ? Acl::get_entity_name($this->av_conn, $ctx, TRUE) : "-";
        $fields['context'] = ($ctx_name != "") ? $ctx_name : _("Unknown");
        
        // Source
        $fields['src_ip']   = inet_ntop($row["ip_src"]);
        $fields['src_host'] = strtoupper(bin2hex($row['src_host']));
        $fields['src_net']  = strtoupper(bin2hex($row['src_net']));
        
        $src_output         = Asset_host::get_extended_name($this->av_conn, $this->geoloc, $fields['src_ip'], $ctx, $fields['src_host'], $fields['src_net']);
        $fields['src_name'] = $src_output['name'];
        $src_img            = $src_output['html_icon'];
        
        if ($row["src_userdomain"] != "") // IDM
        {
            $source = $row['src_userdomain'];
        }
        else // Hostname/IP
        {
            $source = $fields['src_name'];
        }
        $source = Util::wordwrap($source, 30, '<br/>');
        
        $fields['source']   = "<div class='HostReportMenu' id='".$fields['src_ip'].";".$fields['src_name'].";".$fields['src_host']."' style='".(($src_output['is_internal']) ? "font-weight:bold" : "")."'>"
                            .(($src_img != "") ? $src_img." ".$source : $source)."</div>";
        
        // Destination
        $fields['dst_ip']      = inet_ntop($row["ip_dst"]);
        $fields['dst_host']    = strtoupper(bin2hex($row['dst_host']));
        $fields['dst_net']     = strtoupper(bin2hex($row['dst_net']));
        
        $dst_output            = Asset_host::get_extended_name($this->av_conn, $this->geoloc, $fields['dst_ip'], $ctx, $fields['dst_host'], $fields['dst_net']);
        $fields['dst_name']    = $dst_output['name'];
        $dst_img               = $dst_output['html_icon'];
        
        if ($row["dst_userdomain"] != "") // IDM
        {
            $destination = $row['dst_userdomain'];
        }
        else // Hostname/IP
        {
            $destination = $fields['dst_name'];
        }
        $destination = Util::wordwrap($destination, 30, '<br/>');
        
        $fields['destination'] = "<div class='HostReportMenu' id='".$fields['dst_ip'].";".$fields['dst_name'].";".$fields['dst_host']."' style='".(($dst_output['is_internal']) ? "font-weight:bold" : "")."'>"
                                .(($dst_img != "") ? $dst_img." ".$destination : $destination)."</div>";        
        
        // Risk
        $maxrisk = ($row['ossim_risk_a'] > $row['ossim_risk_c']) ? $row['ossim_risk_a'] : $row['ossim_risk_c'];
        
        if ($maxrisk > 5)
        {
            $risk = "H";
        }
        elseif ($maxrisk > 3)
        {
            $risk = "M";
        }
        else
        {
            $risk = "L";
        }
        
        $fields['risk']    = $risk;
        $fields['maxrisk'] = $maxrisk;
        
        // Username@domain
        $fields['username'] = $row['idm_username'];
        $fields['domain']   = $row['idm_domain'];
        
        return $fields;
    }
    
    public static function translate_signature($name, $arr)
    {
        $translations = array(
            "/SRC_IP/"     => 'inet_ntop($arr["ip_src"])',
            "/DST_IP/"     => 'inet_ntop($arr["ip_dst"])',
            "/SRC_PORT/"   => '$arr["layer4_sport"]',
            "/DST_PORT/"   => '$arr["layer4_dport"]',
            "/PULSE/"      => '$arr["pulse"]',
            "/PROTOCOL/"   => 'Protocol::get_protocol_by_number($arr["ip_proto"], TRUE)',
            "/PLUGIN_ID/"  => '$arr["plugin_id"]',
            "/PLUGIN_SID/" => '$arr["plugin_sid"]',
            "/FILENAME/"   => 'Util::htmlentities($arr["filename"])',
            "/USERNAME/"   => 'Util::htmlentities($arr["username"])',
            "/USERDATA1/"  => 'Util::htmlentities($arr["userdata1"])',
            "/USERDATA2/"  => 'Util::htmlentities($arr["userdata2"])',
            "/USERDATA3/"  => 'Util::htmlentities($arr["userdata3"])',
            "/USERDATA4/"  => 'Util::htmlentities($arr["userdata4"])',
            "/USERDATA5/"  => 'Util::htmlentities($arr["userdata5"])',
            "/USERDATA6/"  => 'Util::htmlentities($arr["userdata6"])',
            "/USERDATA7/"  => 'Util::htmlentities($arr["userdata7"])',
            "/USERDATA8/"  => 'Util::htmlentities($arr["userdata8"])',
            "/USERDATA9/"  => 'Util::htmlentities($arr["userdata9"])'
        );

        foreach($translations as $k => $replacement)
        {
            $pattern = '$name = preg_replace("' . $k . '", %s, $name);';
            $str = sprintf($pattern, $replacement);
            eval($str);
        }

        return $name;
    }
    
    
    /********************************************************************
     *********************** STATIC METHODS *****************************
     *******************************************************************/
    
    /**
     * This function returns join and where with a user perms
     *
     * @return string
     */
    public static function make_perms_where()
    {
        $where  = "";
        $domain = Session::get_ctx_where();
        // Asset filter
        if ($domain != "")
        {
            $where .= " AND acid_event.ctx in ($domain)";
        }
    
        // Asset filter
        $host_perms = Session::get_host_where();
        $net_perms  = Session::get_net_where();
    
        if ($host_perms != "")
        {
            $where .= " AND (acid_event.src_host in ($host_perms) OR acid_event.dst_host in ($host_perms)";
            if ($net_perms != "") $where .= " OR acid_event.src_net in ($net_perms) OR acid_event.dst_net in ($net_perms))";
            else                  $where .= ")";
        }
        elseif ($net_perms != "")
        {
            $where .= " AND (acid_event.src_net in ($net_perms) OR acid_event.dst_net in ($net_perms))";
        }
    
        return $where;
    }
    
    /**
     * This function returns true if current asset type has events
     *
     * @param object   $conn     Database access object
     * @param string   $type     Asset type (host, network, group)
     * @param string   $id       Host uuid (Hex value)
     *
     * @throws Exception  If a connection error occurred
     *
     * @return boolean
     */
    public static function has_events($conn, $type, $id)
    {
        Ossim_db::check_connection($conn);

        $params = array($id, $id);

        if ($type == "host")
        {
            $sql = "SELECT EXISTS(SELECT src_host as id FROM alienvault_siem.ac_acid_event WHERE src_host=UNHEX(?) UNION SELECT dst_host as id FROM alienvault_siem.ac_acid_event WHERE dst_host=UNHEX(?)) as bool";
        }
        elseif ($type == "net")
        {
            $sql = "SELECT EXISTS(SELECT n1.net_id FROM alienvault_siem.ac_acid_event ac, net ne, net_cidrs n, net ne1, net_cidrs n1 WHERE ne.id = n.net_id AND ne1.id = n1.net_id AND n.begin <= n1.begin AND n1.end <= n.end AND ne.ctx = ne1.ctx AND n.net_id = UNHEX(?) AND n1.net_id=ac.src_net UNION SELECT n1.net_id FROM alienvault_siem.ac_acid_event ac, net ne, net_cidrs n, net ne1, net_cidrs n1 WHERE ne.id = n.net_id AND ne1.id = n1.net_id AND n.begin <= n1.begin AND n1.end <= n.end AND ne.ctx = ne1.ctx AND n.net_id = UNHEX(?) AND n1.net_id=ac.dst_net) as bool";
            $params = array($id, $id);
        }
        elseif ($type == "group")
        {
            // Using perms
            $where = self::make_perms_where();
            $sql   = "SELECT EXISTS(SELECT acid_event.src_host as id FROM alienvault_siem.ac_acid_event acid_event, alienvault.host_group_reference r WHERE acid_event.src_host=r.host_id AND r.host_group_id = UNHEX(?) $where UNION SELECT acid_event.dst_host as id FROM alienvault_siem.ac_acid_event acid_event, alienvault.host_group_reference r WHERE acid_event.dst_host=r.host_id AND r.host_group_id = UNHEX(?) $where) as bool";
        }

        $rs = $conn->Execute($sql, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        // Total rows
        return ($rs->fields["bool"]) ? TRUE : FALSE;
    }
    
    
    /**
    * This function gets the pulses for a given event. 
    *
    * @param object   $conn         Database access object
    * @param string   $event_id     Event ID.
    * @param string   $alarm_event  Alarm ID of the event or FALSE.
    * @param string   $cache        Wheter or not cache the query
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_event_pulses($conn, $event_id, $alarm_event = FALSE, $cache = FALSE)
    {
        $pulse_list = array();
        $params     = array($event_id);
        
        if ($alarm_event === FALSE)
        {
            $sql = "SELECT DISTINCT HEX(otx_data.pulse_id) AS pulse_id 
                    FROM alienvault_siem.otx_data 
                    WHERE otx_data.event_id=UNHEX(?)";
        }
        else
        {
            $sql = "SELECT DISTINCT HEX(o.pulse_id) AS pulse_id 
                    FROM otx_data o FORCE INDEX (pulse), alarm a, otx_data o2 
                    WHERE o.event_id=UNHEX(?) AND a.backlog_id=UNHEX(?) AND o2.event_id=a.event_id AND o2.pulse_id=o.pulse_id";
            
            $params[] = $alarm_event;
        }
        
        $rs  = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF)
        {
            $p_id  = $rs->fields['pulse_id'];

            try
            {
                $otx        = new Otx();
                $pulse_data = $otx->get_pulse_detail($p_id, TRUE);  //Set hide_iocs to true because we only want the name and descr of the pulse.           
            }
            catch(Exception $e)
            {
                $rs->MoveNext();
                continue;
            } 
            
            $pulse_list[$p_id] = array(
                'id'    => $p_id,
                'name'  => $pulse_data['name'],
                'descr' => $pulse_data['description'],
            );

            $rs->MoveNext();
        }
        
        return $pulse_list;
    }
    
    
    /**
    * This function gets the pulse info of a given pulse id from an event. 
    *
    * @param object   $conn        Database access object
    * @param string   $event_id    Event ID.
    * @param string   $pulse_id    Pulse ID.
    * @param string   $from_alarm  Wheter or not the event is in event or acid_event table.
    * @param string   $cache       Wheter or not cache the query
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_pulse_data_from_event($conn, $event_id, $pulse_id, $from_alarm = FALSE, $cache = FALSE)
    {
        $indicators = array();
        $params     = array($event_id, $pulse_id);    
        
        if (!$from_alarm)
        {
            $sql = "SELECT DISTINCT otx_data.ioc_hash, otx_data.ioc_value 
                        FROM alienvault_siem.otx_data 
                        WHERE otx_data.event_id=UNHEX(?) AND otx_data.pulse_id=UNHEX(?)";
        }
        else
        {
            $sql = "SELECT DISTINCT otx_data.ioc_hash, otx_data.ioc_value 
                        FROM otx_data 
                        WHERE otx_data.event_id=UNHEX(?) AND otx_data.pulse_id=UNHEX(?)";
        }

        $rs  = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        try
        {
            $otx    = new Otx();
            $p_data = $otx->get_pulse_detail($pulse_id);
        }
        catch(Exception $e)
        {
            return $pulse;
        }
        
        $indicators = array();
        while (!$rs->EOF)
        {
            $ioc_h = $rs->fields['ioc_hash'];
            $ioc_v = $rs->fields['ioc_value'];
            
            $indicators[$ioc_h] = array(
                'hash'  => $ioc_h,
                'type'  => $p_data['indicators'][$ioc_h]['type'], 
                'value' => $ioc_v
            );

            $rs->MoveNext();
        }
        
        $pulse = array(
            'name'  => $p_data['name'],
            'descr' => $p_data['description'],
            'iocs'  => $indicators
        );
        
        return $pulse;        
    }
    
    
    /**
    * This function gets the reputation for a given event. 
    *
    * @param object   $conn        Database access object
    * @param string   $event_id    Event ID.
    * @param string   $from_alarm  Wheter or not the event is in event or acid_event table.
    * @param string   $cache       Wheter or not cache the query
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_event_reputation($conn, $event_id, $from_alarm = FALSE, $cache = FALSE)
    {
        $rep_list = array();
        $params   = array($event_id);    
        
        if (!$from_alarm)
        {
            $sql = "SELECT DISTINCT INET6_NTOA(r.rep_ip_src) AS src_ip, r.rep_prio_src, r.rep_rel_src, r.rep_act_src, INET6_NTOA(r.rep_ip_dst) AS dst_ip, r.rep_prio_dst, r.rep_rel_dst, r.rep_act_dst 
                FROM alienvault_siem.reputation_data r, alienvault_siem.acid_event 
                WHERE r.event_id=UNHEX(?) AND r.event_id = acid_event.id AND (r.rep_prio_src > 0 OR r.rep_prio_dst > 0)";
        }
        else
        {
            $sql = "SELECT DISTINCT INET6_NTOA(src_ip) AS src_ip, rep_prio_src, rep_rel_src, rep_act_src, INET6_NTOA(dst_ip) AS dst_ip, rep_prio_dst, rep_rel_dst, rep_act_dst 
                FROM event 
                WHERE id=UNHEX(?) AND (rep_prio_src > 0 OR rep_prio_dst > 0)";
        }

        $rs  = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF)
        {
            if ($rs->fields['rep_prio_src'] > 0)
            {
                $rep_list[] = array(
                    'origin'      => _('Source'),
                    'value'       => $rs->fields['src_ip'],
                    'activity'    => str_replace(';', ', ',$rs->fields['rep_act_src']),
                    'reliability' => $rs->fields['rep_rel_src'],
                    'priority'    => $rs->fields['rep_prio_src'],
                );
            }
            elseif ($rs->fields['rep_prio_dst'] > 0)
            {
                
                $rep_list[] = array(
                    'origin'      => _('Destination'),
                    'value'       => $rs->fields['dst_ip'],
                    'activity'    => str_replace(';', ', ',$rs->fields['rep_act_dst']),
                    'reliability' => $rs->fields['rep_rel_dst'],
                    'priority'    => $rs->fields['rep_prio_dst'],
                );
            }

            $rs->MoveNext();
        }
        
        return $rep_list;
    }

    /**
     * This function executes the query to otx_data db table and gets unique pulse ids
     *
     * @param object   $conn        Database access object
     *
     * @return array Pulse ID list
     */
    public static function get_unique_pulses($conn)
    {
        $sql = "SELECT DISTINCT HEX(pulse_id) AS pulse FROM alienvault_siem.otx_data";
        
        $list = array();
    
        if (!$rs = $conn->Execute($sql))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields['pulse']] = $rs->fields['pulse'];
                $rs->MoveNext();
            }
        }

        return $list;
    }
}
