<?php
/**
* plugin_sid.inc
*
* File plugin_sid.inc is used to:
*   - Manage and fetch info in plugin_sid table
*   - Get taxonomy info
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
* @package    ossim-framework\Plugins
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Plugin_sid()
* - get_plugin_id()
* - get_sid()
* - get_category_id()
* - get_class_id()
* - get_name()
* - get_reliability()
* - get_priority()
* - get_foundrows()
* - get_list()
* - get_last_id()
* - update()
* - GetPluginBySourceType()
* - GetPluginsByCategory()
*/

class Plugin_sid 
{
    /**
     * Plugin ID
     *
     * @var int
     * @access public
     */
    public $plugin_id;


    /**
     * Plugin SID
     *
     * @var int
     * @access public
     */
    public $sid;


    /**
     * Class ID
     *
     * @var int
     * @access public
     */
    public $class_id;


    /**
     * Plugin name
     *
     * @var string
     * @access public
     */
    public $name;


    /**
     * Plugin reliability
     *
     * @var int
     * @access public
     */
    public $reliability;


    /**
     * Plugin priority
     *
     * @var int
     * @access public
     */
    public $priority;


    /**
     * Category ID
     *
     * @var int
     * @access public
     */
    public $category_id;


    /**
     * Subcategory ID
     *
     * @var int
     * @access public
     */
    public $subcategory_id;


    /**
     * Class constructor
     *
     * This function sets up the class
     * 
     * @param int    $plugin_id
     * @param int    $sid
     * @param int    $category_id
     * @param int    $class_id
     * @param string $name
     * @param int    $reliability
     * @param int    $priority
     * @param int    $subcategory_id
     * @param int    $foundrows
     */
    function __construct($plugin_id, $sid, $category_id, $class_id, $name, $reliability, $priority, $subcategory_id, $foundrows = 0)
    {
        $this->plugin_id      = $plugin_id;
        $this->sid            = $sid;
        $this->category_id    = $category_id;
        $this->class_id       = $class_id;
        $this->name           = $name;
        $this->reliability    = $reliability;
        $this->priority       = $priority;
        $this->subcategory_id = $subcategory_id;
        $this->foundrows      = $foundrows;
    }


    /**
     * This function returns the plugin id
     *
     * @return int
     */
    function get_plugin_id()
    {
        return $this->plugin_id;
    }


    /**
     * This function returns the plugin sid
     *
     * @return int
     */
    function get_sid()
    {
        return $this->sid;
    }


    /**
     * This function returns the plugin category id
     *
     * @return int
     */
    function get_category_id()
    {
        return $this->category_id;
    }


    /**
     * This function returns the class id
     *
     * @return int
     */
    function get_class_id()
    {
        return $this->class_id;
    }


    /**
     * This function returns the plugin name
     *
     * @return string
     */
    function get_name()
    {
        return preg_replace("/<([^>]*)>/", "(\\1)", $this->name);
    }


    /**
     * This function returns the plugin reliability
     *
     * @return int
     */
    function get_reliability()
    {
        return $this->reliability;
    }


    /**
     * This function returns the plugin priority
     *
     * @return int
     */
    function get_priority()
    {
        return $this->priority;
    }


    /**
     * This function returns the plugin subcategory id
     *
     * @return int
     */
	function get_subcategory_id()
	{
        return $this->subcategory_id;
    }


    /**
     * This function returns the found rows
     *
     * @return int
     */
    function get_foundrows()
    {
        return $this->foundrows;
    }


    /**
     * This functions returns the plugin sid object list
     * 
     * @param object $conn
     * @param string $args
     * 
     * @return array
     */
    static function get_list($conn, $args = "")
    {
        Ossim_db::check_connection($conn);
        
        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;
        $select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query       = ossim_query("SELECT $select FROM plugin_sid $args");
        //echo "SELECT $select FROM plugin_sid $args<br>";
        
        $rs = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $foundrows = 0;
            if ($iffoundrows)
            {
                $rf = $conn->Execute("SELECT FOUND_ROWS() as total");
                if (!$rf)
                { 
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                else
                {
                    $foundrows = $rf->fields["total"];
                }
            }
            $list = array();
            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["plugin_id"], $rs->fields["sid"], $rs->fields["category_id"], $rs->fields["class_id"], $rs->fields["name"], $rs->fields["reliability"], $rs->fields["priority"], $rs->fields["subcategory_id"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }


    /**
     * This function returns the last valid sid for a plugin
     * 
     * @param object $conn
     * @param int    $plugin_id
     * 
     * @return int
     */
	static function get_last_id($conn, $plugin_id)
	{
	    Ossim_db::check_connection($conn);
	    
		$query = ossim_query("SELECT plugin_id, MAX(sid) AS last_id FROM `plugin_sid` WHERE plugin_id = ? AND sid < 20000000");
        
		$rs = $conn->Execute($query, array($plugin_id));
		
		if (!$rs)
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
		else
		{
			return $rs->fields['last_id'];
		}
	}


	/**
	 * This function updates the plugin_sid plugin_sid_changes plugin_sid_orig tables
	 * 
	 * @param object $conn
	 * @param int    $id
	 * @param int    $sid
	 * @param int    $priority
	 * @param int    $reliability
	 * @param int    $category_id
	 * @param int    $subcategory_id
	 * @param string $name
	 */
    static function update($conn, $id, $sid, $priority, $reliability, $category_id = NULL, $subcategory_id = NULL, $name = '')
    {
        Ossim_db::check_connection($conn);
        
        settype($priority, "int");
        settype($reliability, "int");
        $sql = $params = array();
		
        
        if ($name != '') // All parameters
		{
            $sql[]    = "UPDATE plugin_sid SET category_id = ?, subcategory_id = ?, priority = ?, reliability = ?, name = ? WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $category_id, $subcategory_id, $priority, $reliability, $name, $id, $sid );
          
            $sql[]    = "REPLACE INTO plugin_sid_changes SELECT * FROM plugin_sid WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $id, $sid );
           
            $sql[]    = "UPDATE plugin_sid_changes SET category_id = ?, subcategory_id = ?, priority = ?, reliability = ?, name = ? WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $category_id, $subcategory_id, $priority, $reliability, $name, $id, $sid );
        } 
		elseif ( $category_id != NULL )  // Without name
		{
            $sql[]    = "UPDATE plugin_sid SET category_id = ?, subcategory_id = ?, priority = ?, reliability = ? WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $category_id, $subcategory_id, $priority, $reliability, $id, $sid );
           
            $sql[]    = "REPLACE INTO plugin_sid_changes SELECT * FROM plugin_sid WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $id, $sid );
           
            $sql[]    = "UPDATE plugin_sid_changes SET category_id = ?, subcategory_id = ?, priority = ?, reliability = ? WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $category_id, $subcategory_id, $priority, $reliability, $id, $sid );
		} 
		else // Without name and taxonomy
		{
            $sql[]    = "UPDATE plugin_sid SET priority = ?, reliability = ? WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $priority, $reliability, $id, $sid );
           
            $sql[]    = "REPLACE INTO plugin_sid_changes SELECT * FROM plugin_sid WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $id, $sid );
           
            $sql[]    = "UPDATE plugin_sid_changes SET priority = ?, reliability = ? WHERE plugin_id = ? AND sid = ?";
            $params[] = array( $priority, $reliability, $id, $sid );
        }
        
        // To restore original table
        $orig_list = self::get_list($conn, "WHERE plugin_id=$id AND sid=$sid");
        $orig_data = $orig_list[0];
        
        if (is_object($orig_data)) 
        {
       		$sql[]    = "INSERT IGNORE INTO plugin_sid_orig (plugin_id, sid, category_id, subcategory_id, priority, reliability, name) VALUES (?, ?, ?, ?, ?, ?, ?)";
       		
       		$params[] = array($id, $sid, $orig_data->get_category_id(), $orig_data->get_subcategory_id(), $orig_data->get_priority(), $orig_data->get_reliability(), $orig_data->get_name());
        }
        
		foreach ($sql as $i => $str) 
		{
	        if ($conn->Execute($sql[$i], $params[$i]) === false)
	        {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
			}
	    }
	    
	    // Turn red the 'Apply' button
	    Web_indicator::set_on("Reload_plugins");
    }


    /**
     * This function restores the info in plugin_sid_orig table to plugin_sid
     * 
     * @param object $conn
     */
    static function restore_plugins($conn)
    {
        Ossim_db::check_connection($conn);
        
	    	$list = array();
    		// Get the original plugin data
	    	$query = ossim_query("SELECT * FROM plugin_sid_orig");
	    	
	    	$rs = $conn->Execute($query);
	    	
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = $rs->fields;
                $rs->MoveNext();
            }
        }
        // Update plugin_sid table
        foreach ($list as $row)
        {
        		$sql    = "UPDATE plugin_sid SET category_id = ?, subcategory_id = ?, priority = ?, reliability = ?, name = ? WHERE plugin_id = ? AND sid = ?";
        		$params = array($row['category_id'], $row['subcategory_id'], $row['priority'], $row['reliability'], $row['name'], $row['plugin_id'], $row['sid']);
        		if ($conn->Execute($sql, $params) === false)
        		{
        			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        		}
        }
    }


    /**
     * This function returns the event name by id/sid
     * 
     * @param object $conn
     * @param int    $id
     * @param int    $sid
     * 
     * @return string
     */
    static function get_name_by_idsid($conn, $id, $sid)
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT name FROM plugin_sid WHERE plugin_id = ? AND sid = ?");
        
        $rs = $conn->Execute($query, array($id, $sid));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            return trim($rs->fields["name"]);
        }
        
        return '';
    }


    /**
     * This function returns the SID list by plugin id
     * 
     * @param object $conn
     * @param int    $id
     * 
     * @return array
     */
    static function get_sids_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT sid FROM plugin_sid where plugin_id = ?");
        
        $rs = $conn->Execute($query, array($id));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();
            while (!$rs->EOF)
            {
                $list[] = $rs->fields["sid"];
                $rs->MoveNext();
            }
        }
        
        return $list;
    }


    /**
     * This function returns the number of SIDs for a plugin
     * 
     * @param object $conn
     * @param int    $id
     * 
     * @return int
     */
	static function get_sidscount_by_id($conn, $id)
	{
	    Ossim_db::check_connection($conn);
	    
        $query = ossim_query("SELECT count(sid) as num FROM plugin_sid where plugin_id=$id");
        $num   = 0;
        $rs    = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $num = $rs->fields["num"];
            }
        }
        return $num;
    }


    /**
     * This function returns the SIDs by event name
     * 
     * @param object $conn
     * @param string $query
     * 
     * @return array
     */
    static function search_sids($conn, $search)
    {
        Ossim_db::check_connection($conn);
        
        $query  = "SELECT plugin_sid.*,plugin.name as plugin_name FROM plugin_sid LEFT JOIN plugin ON plugin.id=plugin_sid.plugin_id WHERE plugin_sid.name like ? ORDER BY plugin_sid.plugin_id";
        
        $params = array('%'. $search .'%');
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();
            while (!$rs->EOF)
            {
                $list[] = $rs->fields;
                $rs->MoveNext();
            }
        }
        return $list;
    }


    /**
     * This function inserts a new row in plugin_sid table
     * 
     * @param object $conn
     * @param int    $plugin
     * @param string $name
     * @param int    $sid
     * @param int    $reliability
     * @param int    $priority
     * @param int    $category_id
     * @param int    $subcategory_id
     */
    static function insert($conn, $plugin, $name, $sid, $reliability, $priority, $category_id = NULL, $subcategory_id = NULL)
    {
        Ossim_db::check_connection($conn);
        
        settype($plugin, "int");
        settype($sid, "int");
        settype($reliability, "int");
        settype($priority, "int");

        $sql = "INSERT INTO plugin_sid (plugin_id, name, sid, reliability, priority, category_id, subcategory_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = array(
            $plugin,
            $name,
            $sid,
            $reliability,
            $priority,
            $category_id,
            $subcategory_id
        );

        if ($conn->Execute($sql, $params) === false)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        // Update to fill plugin_sid_changes table
        self::update($conn, $plugin, $sid, $priority, $reliability, $category_id, $subcategory_id, $name);
        
    }


    /**
     * This function deletes a row in plugin_sid table
     * 
     * @param object $conn
     * @param int    $plugin
     * @param int    $sid
     */
	static function delete($conn, $plugin, $sid)
	{
	    Ossim_db::check_connection($conn);
	    
        settype($plugin, "int");
        settype($sid, "int");
        
        $sql = "DELETE FROM plugin_sid WHERE plugin_id=? AND sid=?";
        
        $params = array(
            $plugin,
            $sid
        );

        if ($conn->Execute($sql, $params) === false)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $sql = "DELETE FROM plugin_sid_changes WHERE plugin_id=? AND sid=?";
        
        $params = array(
            $plugin,
            $sid
        );

        if ($conn->Execute($sql, $params) === false)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Turn red the 'Apply' button
        Web_indicator::set_on("Reload_plugins");

    }


    /**
     * This function returns the SIDs list by product type
     * 
     * @param object $conn
     * @param int    $type
     * 
     * @return array
     */
	static function get_plugins_by_type($conn, $type)
	{
	    Ossim_db::check_connection($conn);
	    
		$ids = array();
	    $sql = "SELECT id FROM plugin WHERE product_type = ?";
	    
	    $rs  = $conn->Execute($sql, array($type));
	    
	    if (!$rs)
	    {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
		    while (!$rs->EOF)
		    {
		        $ids[] = $rs->fields["id"];
		        $rs->MoveNext();
		    }
        }
	    return $ids;
	}


	/**
	 * This function returns the plugin list by taxonomy category/subcategory
	 * 
	 * @param object $conn
	 * @param int    $idcat
	 * @param int    $idsubcat
	 * 
	 * @return array
	 */
	static function get_plugins_by_category($conn, $idcat, $idsubcat = "")
	{
	    Ossim_db::check_connection($conn);
	    
	    $ids    = array();
	    $sql    = "SELECT DISTINCT plugin_id FROM plugin_sid WHERE category_id = ?";
	    $params = array($idcat);
        
        if ($idsubcat != "")
        {
            $sql     .= " AND subcategory_id = ?";
            $params[] = $idsubcat;
        }
        
        $rs = $conn->Execute($sql, $params);
        
	    if (!$rs)
	    {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
		    while (!$rs->EOF)
		    {
		        $ids[] = $rs->fields["plugin_id"];
		        $rs->MoveNext();
		    }
        }
	    return $ids;
	}


	/**
	 * This function returns the event SIDs list by plugin and taxonomy
	 * 
	 * @param object $conn
	 * @param int    $plugin_id
	 * @param int    $idcat
	 * @param int    $idsubcat
	 * 
	 * @return array
	 */
	static function get_sids_by_category($conn, $plugin_id, $idcat, $idsubcat = "")
	{
	    Ossim_db::check_connection($conn);
	    
	    $ids    = array();
	    $sql    = "SELECT DISTINCT sid FROM plugin_sid WHERE plugin_id = ? AND category_id = ?";
	    $params = array($plugin_id, $idcat);
        
        if ($idsubcat != "")
        {
            $sql     .= " AND subcategory_id = ?";
            $params[] = $idsubcat;
        }
        
        $rs = $conn->Execute($sql, $params);
        
	    if (!$rs)
	    {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
		    while (!$rs->EOF)
		    {
		        $ids[] = $rs->fields["sid"];
		        $rs->MoveNext();
		    }
        }
	    return $ids;
	}


	/**
	 * This function returns the SID list by some optional parameters
	 * 
	 * @param object $conn
	 * @param int    $plugin_id
	 * @param int    $source_type
	 * @param int    $category
	 * @param int    $subcategory
	 * @param string $dsgroup
	 * @param string $force_sql
	 * 
	 * @return array
	 */
	static function get_all_sids($conn, $plugin_id, $source_type, $category, $subcategory, $dsgroup, $force_sql = NULL)
	{
	    Ossim_db::check_connection($conn);
	    
		$list           = array();
		$final_list     = array();
		$final_list_min = array(); // Convert plugin_sids to '0' when there're all sids
		$filtered       = 0;
		
		// plugin_id
		if (!empty($plugin_id))
		{
			$filtered++;
		    $sql = "SELECT DISTINCT sid FROM plugin_sid WHERE plugin_id = ?";
		    
		    $rs  = $conn->Execute($sql, array($plugin_id));
		    
		    if (!$rs)
		    {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	        }
	        else
	        {
			    while (!$rs->EOF)
			    {
			        $list[$plugin_id][$rs->fields["sid"]]++;
			        $rs->MoveNext();
			    }
	        }
		}
		
		// source_type
		if (!empty($source_type))
		{
            $filtered++;
            $sql = "SELECT plugin_id, sid FROM plugin_sid 
                    left outer join alienvault.plugin on plugin.id=plugin_sid.plugin_id 
                    left outer join alienvault.product_type on product_type.id=plugin.product_type 
                    WHERE 1=1 AND product_type.name = ?";
            
            $rs  = $conn->Execute($sql,array($source_type));
            
            if (!$rs)
            {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	        }
	        else
	        {
			    while (!$rs->EOF)
			    {
			        $list[$rs->fields["plugin_id"]][$rs->fields["sid"]]++;
			        $rs->MoveNext();
			    }
	        }
		}
		// ds group
		if (!empty($dsgroup))
		{
			$filtered++; 
			$groups = Plugin_group::get_list($conn, "plugin_group.name='$dsgroup'");
			foreach($groups as $group)
			{
				foreach($group->get_plugins() as $p)
				{
					// Zero is all sids
					if ($p['sids'] == 0)
					{
						$sids = self::get_sids_by_id($conn, $p['id']);
					}
					// Specific sids
					else
					{
						$sids = explode(",",$p['sids']);
					}
					
					foreach ($sids as $sid)
					{
					    $list[$p['id']][$sid]++;
					}
				}
			}
		}
		
		// taxonomy
		if (!empty($category))
		{
			$filtered++; 
		    $sql    = "SELECT DISTINCT plugin_id,sid FROM plugin_sid WHERE category_id = ?";
		    $params = array($category);
		    
	        if (!empty($subcategory))
	        {
                $sql     .= " AND subcategory_id = ?";
                $params[] = $subcategory;
	        }
	        
	        $rs = $conn->Execute($sql, $params);
	        
		    if (!$rs)
		    {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		    }
		    else
		    {
			    while (!$rs->EOF)
			    {
                    $list[$rs->fields["plugin_id"]][$rs->fields["sid"]]++;
                    $rs->MoveNext();
			    }
	        }
		}
		// SQL Force
		if ($force_sql != "")
		{
			$filtered++;
			$sql = "SELECT plugin_id,sid FROM alienvault.plugin_sid 
			        left outer join alienvault.plugin on plugin.id=plugin_sid.plugin_id 
			        left outer join alienvault.product_type on product_type.id=plugin.product_type 
			        WHERE 1=1 ".$force_sql;
			//error_log("$sql\n",3,'/tmp/fetch');
			
			$rs = $conn->Execute($sql);
			
		    if (!$rs)
		    {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	        }
	        else
	        {
			    while (!$rs->EOF)
			    {
			        $list[$rs->fields["plugin_id"]][$rs->fields["sid"]]++;
			        $rs->MoveNext();
			    }
	        }
		}
		// Make the AND
		foreach ($list as $p_id=>$sid_list)
		{
			foreach ($sid_list as $sid=>$num)
			{
				if ($num == $filtered)
				{
					$final_list[$p_id][$sid] = 1;
				}
			}
		}
		if ($filtered && count($final_list) < 1)
		{
			$final_list_min['-1']['-1']++;
		}
		else
		{
			foreach ($final_list as $p_id=>$sid_list)
			{
				$count = 0;
				$sql   = "SELECT count(*) as num FROM plugin_sid WHERE plugin_id = ?";
				$rs    = $conn->Execute($sql, array($p_id));
				if (!$rs)
				{
		            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		        }
		        else
		        {
				    if (!$rs->EOF)
				    {
				        $count = $rs->fields['num'];
				    }
		        }
		        if ($count == count($sid_list))
		        {
                    $final_list_min[$p_id][0] = 1;
		        }
		        else
		        {
                    $final_list_min[$p_id] = $sid_list;
		        }
			}
		}
		// Print this for DEBUG
		//echo "<b>$filtered Filters loaded</b>: plugin_id:<b>$plugin_id</b>, source_type:<b>$source_type</b>, dsgroup:<b>$dsgroup</b>, category/subcategory:<b>$category</b>/<b>$subcategory</b>, force_sql:<b>$force_sql</b><br>";
		//print_r($final_list_min);
		
        return $final_list_min;
    }


    /**
     * This function validates a plugin SID list string
     * 
     *   Sample valid $str values:
     *      '0' => ALL SIDs
     *      '1' => only SID 1
     *      '1,2' => SIDs 1 and 2
     *      '1-4' => All SIDs between 1 and 4 (both inclusive)
     *      '1,3-5' => SID 1 and range 3 to 5
     *      '3-5,46,47,110-170' => Valid too
     * 
     * @param string $str
     * 
     * @return array (boolean, string)
     */
    static function validate_sids_str($str)
    {
        if ($str == '')
        {
            return array(
                FALSE,
                _("Signature ID can not be empty. Specify '0' if you want ALL sids")
            );
        }
        
        $values = preg_split('/(\s*,\s*)/', $str);
        $ret    = $m = array();
        
        foreach($values as $v)
        {
            if ($v == "ANY")
            {
                $v = 0;
            }
            if (preg_match('/^([1-9][0-9]*)-([1-9][0-9]*)$/', $v, $m))
            {
                list($start, $end) = array(
                    $m[1],
                    $m[2]
                );
                if ($start >= $end)
                {
                    return array(
                        FALSE,
                        _("Invalid range: '".Util::htmlentities($v)."'")
                    );
                }
                $ret[] = $v;
            }
            elseif (preg_match('/^[0-9]+$/', $v, $m))
            {
                $ret[] = $v;
            }
            else
            {
                return array(
                    FALSE,
                    _("Invalid sid: '".Util::htmlentities($str)."'")
                );
            }
        }
        // $str = '0,1,2'
        if (count($ret) > 1 && in_array(0, $ret))
        {
            return array(
                FALSE,
                _("'0' or 'ANY' should be alone and means ALL sids, sid: '".Util::htmlentities($str)."' not valid")
            );
        }
        // $str = '';
        if (!count($ret))
        {
            return array(
                FALSE,
                _("Signature ID can not be empty. Specify '0' or 'ANY' if you want ALL sids")
            );
        }
        
        return array(
            TRUE,
            implode(',', $ret)
        );
    }
}

/* End of file plugin_sid.inc */
/* Location: ../includes/plugin_sid.inc */
