<?php
/**
* plugin_group.inc
*
* File plugin_group.inc is used to:
*   - To manage plugin groups
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
* - Plugin_group()
* - get_id()
* - get_name()
* - get_description()
* - get_plugins()
* - get_list()
*/

class Plugin_group 
{
    /**
     * Plugin group ID
     *
     * @var int
     * @access public
     */
    public $id;


    /**
     * Plugin group name
     *
     * @var string
     * @access public
     */
    public $name;


    /**
     * Plugin group description
     *
     * @var string
     * @access public
     */
    public $description;


    /**
     * Plugin group plugins
     *
     * @var array
     * @access public
     */
    public $plugins;


    /**
     * Class constructor
     *
     * This function sets up the class
     * 
     * @param int    $id
     * @param string $ctx
     * @param string $name
     * @param string $description
     * @param array  $plugins
     */
    public function __construct($id, $ctx, $name, $description, $plugins) 
    {
        $this->id          = $id;
        $this->ctx         = $ctx;
        $this->name        = $name;
        $this->description = $description;
        $this->plugins     = $plugins;
    }


    /**
     * This function returns the plugin group id
     *
     * @return int
     */
    public function get_id() 
    {
        return $this->id;
    }


    /**
     * This function returns the plugin group ctx
     *
     * @return string
     */
    public function get_ctx() 
    {
        return $this->ctx;
    }


    /**
     * This function returns the plugin group name
     *
     * @return string
     */
    public function get_name() 
    {
        return $this->name;
    }


    /**
     * This function returns the plugin group description
     *
     * @return string
     */
    public function get_description($entities = TRUE) 
    {
        if ($entities)
        {
            return Util::htmlentities($this->description);
        }
        else
        {
            return $this->description;
        }
    }


    /**
     * This function returns the plugin group plugins
     *
     * Ordered array of plugins, each one in the form of an assoc array:
     *           0 => array(
     *                  ['id']    =>   // plugin id
     *                  ['name']  =>   // plugin name
     *                  ['sids']  =>   // plugin sids string
     *                  ['descr'] =>   // plugin description
     *                ),
     *
     * @return array
     */
    public function get_plugins() 
    {
        return $this->plugins;
    }


    /**
     * This function returns if any group contains directive plugins
     * 
     * @param object $conn
     * 
     * @return boolean
     */
    public function contains_directive_plugin($conn)
    {
        Ossim_db::check_connection($conn);
        
        $query  = "SELECT DISTINCT COUNT(*) AS total FROM plugin_group_descr WHERE group_id=UNHEX(?) AND plugin_id=1505";
        $params = array($this->id);

        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        else
        {
            return ($rs->fields['total'] > 0) ? TRUE : FALSE;
        }
    }


    /**
     * This function returns the plugin group object list
     * 
     * @param object $conn
     * @param string $where
     * @param string $order
     * 
     * @return multitype:Plugin_group
     */
    public static function get_list($conn, $where = '', $order = 'ID') 
    {
        Ossim_db::check_connection($conn);
        
        if ($where) 
        {
            $where = "AND $where";
        }
                
        $query = "SELECT
                    hex(plugin_group.group_id) as group_id,
                    hex(plugin_group.group_ctx) as group_ctx,
                    plugin_group.name as group_name,
                    plugin_group.descr as group_description,
                    plugin_group_descr.plugin_id,
                    plugin_group_descr.plugin_sid,
                    plugin.name as plugin_name,
                    plugin.description as plugin_description
                FROM
                    plugin_group, plugin_group_descr, plugin
                WHERE
                    plugin_group_descr.plugin_id = plugin.id AND
                    plugin_group_descr.plugin_ctx = plugin.ctx AND                    
                    plugin_group_descr.group_id = plugin_group.group_id AND
                    plugin_group_descr.group_ctx = plugin_group.group_ctx
                    $where
                ORDER BY " . (($order == 'ID') ? "plugin_group.group_id" : "plugin_group.name");
        
        $list = array();
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());       
        }
        
        
        $group = $plugins = array();
        
        $prev_id = NULL;
        
        
        while (!$rs->EOF) 
        {
            // new group started
            if ($prev_id != $rs->fields['group_id']) 
            {
                // first run, do not store yet
                if ($prev_id !== NULL) 
                {
                    $list[] = new self($group['id'], $group['ctx'], $group['name'], $group['descr'], $plugins);
                }
                
                // store the header info of the previous plugin group
                $group['id']    = $rs->fields['group_id'];
                $group['ctx']   = $rs->fields['group_ctx'];
                $group['name']  = $rs->fields['group_name'];
                $group['descr'] = $rs->fields['group_description'];
                $plugins        = array();
                $prev_id        = $rs->fields['group_id'];
            }
                        
            // Ignore context for the moment
            $plugins[$rs->fields['plugin_id']] = array(
                'id'    => $rs->fields['plugin_id'],
                'sids'  => $rs->fields['plugin_sid'],
                'name'  => $rs->fields['plugin_name'],
                'descr' => $rs->fields['plugin_description']
            );
            
            $rs->MoveNext();
        }
        
        // store the latest record if there where results
        if (count($group)) 
        {
            $list[] = new self($group['id'], $group['ctx'], $group['name'], $group['descr'], $plugins);
        }
        
        return $list;
    }


    /**
     * This function returns if a group can be deleted
     * 
     * @param object $conn
     * @param string $group_id
     * 
     * @return int
     */
    public static function can_delete($conn, $group_id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($group_id);
        $query  = "SELECT count(*) AS num FROM policy_plugin_group_reference WHERE plugin_group_id = UNHEX(?)"; // Ignore context for the moment
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        if ($rs->fields['num'] == '0') 
        {
            return 1;
        }
        else 
        {
            return 0;
        }
    }


    /**
     * This function checks if a group name is valid
     *
     * @param object $conn
     * @param string $group_name
     * @param string $group_id
     *
     * @return boolean
     */
    public static function is_valid_group_name($conn, $group_name, $group_id = NULL)
    {
        Ossim_db::check_connection($conn);

        $params = array($group_name);
        $filter = "name = ?";

        if ($group_id !== NULL)
        {
            $params[]  = $group_id;
            $filter   .= " AND group_id != UNHEX(?)";
        }

        $query  = "SELECT count(*) AS num FROM plugin_group WHERE " . $filter;

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['num']) == 0)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }


    /**
     * This function deletes a plugin from group
     * 
     * @param object $conn
     * @param string $group_id
     * @param int    $pid
     */
    public static function delete_plugin_id($conn, $group_id, $pid) 
    {
        Ossim_db::check_connection($conn);
        
        $query  = "DELETE FROM plugin_group_descr WHERE group_id = UNHEX(?) and plugin_id = ?"; // Ignore context for the moment
        $params = array($group_id, $pid);
                
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    /**
     * This function deletes a plugin group
     * 
     * @param object $conn
     * @param string $group_id
     * 
     * @return object
     */
    public static function delete($conn, $group_id) 
    {
        Ossim_db::check_connection($conn);
        
		$params = array($group_id);
		
		$conn->StartTrans();
		
		$conn->Execute("DELETE FROM plugin_group_descr WHERE group_id = UNHEX(?)", $params);
		$conn->Execute("DELETE FROM policy_plugin_group_reference WHERE plugin_group_id = UNHEX(?)", $params);
		$conn->Execute("DELETE FROM plugin_group WHERE group_id = UNHEX(?)", $params);
		
        if (!$conn->CompleteTrans())
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    /**
     * This function inserts a plugin group
     * 
     * @param object $conn
     * @param string $group_id
     * @param string $name
     * @param string $descr
     * @param array  $plugins
     * @param array  $imported_plugins
     * 
     * @return object
     */
	public static function insert($conn, $group_id, $name, $descr, $plugins, $imported_plugins = array()) 
	{
	    Ossim_db::check_connection($conn);
	    
	    // Ignore context for the moment        
        $conn->StartTrans();
        $query = "INSERT INTO plugin_group (group_id, group_ctx, name, descr) VALUES (unhex(?), 0x0, ?, ?)";
        
        $conn->Execute($query, array($group_id, $name, $descr));
        
        $query = "REPLACE INTO plugin_group_descr (group_id, group_ctx, plugin_id, plugin_ctx, plugin_sid) VALUES (unhex(?), 0x0, ?, 0x0, ?)";
        
        foreach($plugins as $plugin => $sids_str) 
        {
            if ($sids_str == 'ANY') 
            {
                $sids_str = '0';
            }
            
            $conn->Execute($query, array($group_id, $plugin, $sids_str));
        }
        
        
        foreach($imported_plugins as $plugin => $sids_arr) 
        {
            $sids_str = implode(',', array_unique($sids_arr));
            $conn->Execute($query, array($group_id, $plugin, $sids_str));
        }
        
        if (!$conn->CompleteTrans())
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
    }    


    /**
     * This function inserts a plugin into a group
     * 
     * @param object $conn
     * @param string $group_id
     * @param int    $pid
     * @param int    $sid
     * 
     * @return int
     */
	public static function insert_plugin_id_sid($conn, $group_id, $pid, $sid) 
	{
	    Ossim_db::check_connection($conn);
	    
	    //Sanitize group_id
	    $group_id = escape_sql($group_id, $conn);
	    
	    // Ignore context for the moment
        $insert   = FALSE;
        
		$list = self::get_list($conn, "plugin_group.group_id = UNHEX('$group_id')");
		
        if (count($list) == 1) 
        {
            $plugins = $list[0]->get_plugins();
            // Already has Plugin ID
            if ($plugins[$pid] != '') 
            {
                $sids = explode(',', $plugins[$pid]['sids']);
                if (!in_array($sid, $sids)) 
                {
                    $insert = TRUE;
                    $sids[] = $sid;
                    
                    $sid    = join(',', $sids);
                } 
                else 
                {
                    return 1;
                }
            }
            else 
            {
                $insert = TRUE;
            }
        }
        
        if ($insert == TRUE) 
        {
	        $query = "INSERT INTO plugin_group_descr (group_id, group_ctx, plugin_id, plugin_ctx, plugin_sid) 
	           VALUES (unhex(?), 0x0, ?, 0x0, ?) ON DUPLICATE KEY UPDATE plugin_sid=?"; // Ignore context for the moment
			
			$params = array($group_id, $pid, $sid, $sid);
			
			$rs = $conn->Execute($query, $params);
			
	        if (!$rs) 
	        {
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	        }
	        
	        return 0;
        }
    }


    /**
     * This function modifies a plugin group info
     * 
     * @param object $conn
     * @param string $group_id
     * @param string $name
     * @param string $descr
     * @param array  $plugins
     * @param array  $imported_plugins
     * 
     * @return object
     */
	public static function edit($conn, $group_id, $name, $descr, $plugins, $imported_plugins = array()) 
	{
	    Ossim_db::check_connection($conn);
	    
	    // Ignore context for the moment        
        $conn->StartTrans();
        
        $query = "UPDATE plugin_group SET name=?, descr=? WHERE group_id = UNHEX(?)";
        
        $conn->Execute($query, array($name, $descr, $group_id));       

        foreach($plugins as $plugin => $sids_str) 
        {
            if ($sids_str == 'ANY') 
            {
                $sids_str = '0';
            }
            
            $sids = explode(',', trim($sids_str));
            
            foreach ($sids as $sid) 
            {
                if ($sid == 0) 
                {
                    $imported_plugins[$plugin] = array($sid);
                }
                else 
                {
                    $imported_plugins[$plugin][] = $sid;
                }
            }
        }
        
        $conn->Execute("DELETE FROM plugin_group_descr WHERE group_id=unhex(?)", array($group_id));
        $query = "REPLACE INTO plugin_group_descr (group_id, group_ctx, plugin_id, plugin_ctx, plugin_sid) VALUES (UNHEX(?), 0x0, ?, 0x0, ?)";
        
        foreach($imported_plugins as $plugin => $sids_arr) 
        {
            $sids_str = implode(',', $sids_arr);
            
            $conn->Execute($query, array($group_id, $plugin, $sids_str ));
        }
        
	    
	    if (!$conn->CompleteTrans())
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    } 


    /**
     * This function changes the plugin group relations
     * 
     * @param object $conn
     * @param string $group_id
     * @param int    $plugin_id
     * @param int    $plugins_sid
     * 
     * @return boolean
     */
    public static function edit_plugin($conn, $group_id, $plugin_id, $plugins_sid) 
    {
        Ossim_db::check_connection($conn);
        
        $plugins_sid = trim($plugins_sid);

        if ($plugins_sid == "ANY")
        {
            $plugins_sid = "0";
        }

        $query_del = "DELETE FROM plugin_group_descr WHERE group_id=unhex(?) AND plugin_id=?";
        $params    =  array($group_id, $plugin_id);

        $rs = $conn->Execute($query_del, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $query = "REPLACE INTO plugin_group_descr (group_id, group_ctx, plugin_id, plugin_ctx, plugin_sid) VALUES (unhex(?), 0x0, ?, 0x0, ?)";
        
        $rs = $conn->Execute($query, array($group_id, $plugin_id, $plugins_sid));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
    } 


	/**
	 * This function returns the groups related to a plugin
	 * 
	 * @param object $conn
	 * @param int    $id
	 * 
	 * @return multitype:|multitype:Plugin_group
	 */
	public static function get_groups_by_plugin($conn, $id)
	{
	    Ossim_db::check_connection($conn);
	    
		$query = "SELECT a.*, HEX(a.group_id) as group_id FROM plugin_group a, plugin_group_descr b 
				WHERE a.group_id=b.group_id AND b.plugin_id=? AND b.group_id IN (
						SELECT group_id FROM plugin_group_descr GROUP BY group_id HAVING COUNT(plugin_id)=1
					) ORDER BY name";
		
        $list = array();

        $rs = $conn->Execute($query, array($id));
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return array();
        }

        while (!$rs->EOF) 
        {
			$list[$rs->fields['group_id']] = new self
            (
                $rs->fields['group_id'],
                $rs->fields['group_ctx'],
                $rs->fields['name'],
                $rs->fields['descr'],
                array()
            );
			
            $rs->MoveNext();
        }
	
		return $list;
	}


	/**
	 * This function restores the plugin group original data
	 * 
	 * @param object $conn
	 */
	public static function restore_plugins($conn)
	{
	    Ossim_db::check_connection($conn);
	    
		$conn->Execute('TRUNCATE TABLE alienvault.plugin_group_descr');
		$conn->Execute('TRUNCATE TABLE alienvault.plugin_group');

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1001, '2520000,2520001,2520002,2520003,2520004,2520005,2520006,2520007,2520008,2520009,2520010,2520011,2520012,2520013,2520014,2520015,2520016,2520017,2520018,2520019,2520020,2520021,2520022,2520023,2520024,2520025,2520026,2520027,2520028,2520029,2520030,2520031,2520032,2520033,2520034,2520035,2520036,2520037,2520038,2520039,2520040,2520041,2520042,2520043,2520044,2520045,2520046,2520047,2520048,2520049,2520050,2520051,2520052,2520053,2520054,2520055,2520056,2520057,2520058,2520059,2520060,2520061,2520062,2520063,2520064,2520065,2520066,2520067,2520068,2520069,2520070,2520071,2520072,2520073,2520074,2520075,2520076,2520077,2520078,2520079,2520080,2520081,2520082,2520083,2520084,2520085,2520086,2520087,2520088,2520089,2520090,2520091,2520092,2520093,2520094,2520095,2520096,2520097,2520098,2520099,2520100,2520101,2520102,2520103,2520104,2520105,2520106,2520107,2520108,2520109,2520110,2520111,2520112,2520113,2520114,2520115,2520116,2520117,2520118,2520119,2520120,2520121,2520122,2520123,2520124,2520125,2520126,2520127,2520128,2520129,2520130,2520131,2520132,2520133,2520134,2520135,2520136,2520137,2520138,2520139,2520140,2520141,2520142,2520143,2520144,2520145,2520146,2520147,2520148,2520149,2520150,2520151,2520152,2520153,2520154,2520155,2520156,2520157,2520158,2520159,2520160,2520161,2520162,2520163,2520164,2520165,2520166,2520167')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Tor network', 'Access from or to Tor network exit nodes')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1001, '16477,15463,13626,19166,15587,16478,13633,15294,12283,13473,20499,15464,18516,13629,20498,13630,15586,16476,20723,20795,20793,20796,20792,20855,20854,20983,20982,20986,20985,20984,20987,18548,18545,18549,18550,18546,18547,18683,19065,19066,19067,19070,19068,19069,20540,21674,21677,21675,21676,15361,15727,16354,16390,16523,17668,18684,18681,18682,19273,19271,19277,19280,19274,19278,19269,19275,19279,19272,19268,19270,19276,19648,19647,19646,20146,20151')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Document files', 'Microsoft Office or PDF documents detected in network transit')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1001, '2000419,2008576,2008547,2008509,2009019,2008341,2008285,2002773,2008557,2008575,2009080,2007671,2009033,2009034,2009035,16425,16436,16435,16434,15306,13797')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Executable files', 'Executables files detected in network transit')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1001, '2011409,2011410,2012826,2802860,2012900,2012901,2012902,2012903,2012956,2013124,2013172,2013847,2013848,2013849,2013850,2013851,2013852,2013853,2013854,2013855,2013856,2013857,2013858,2013859,2013860,2013861,2013862,2013970,2014285')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Suspicious DNS', 'DNS queries to suspicious TLDs')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1001, '2014264,2014265,2803924,2008985,2008986,2008987,2008988,2008989,2009020,2013217')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Get IP request', 'Get public IP request from external web service')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1138, '2,3,4,5,6')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Sensitive data', 'Sensitive data detected in network transit')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1001, 'ANY')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Snort IDS sigs', 'Snort IDS signatures')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1119, 'ANY')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Snort HTTP INSPECT', 'Snort HTTP Inspect preprocessor signatures')");

		$new_id = Util::uuid();
		$conn->Execute("REPLACE INTO alienvault.plugin_group_descr (group_id,plugin_id,plugin_sid) VALUES (UNHEX('$new_id'), 1116, 'ANY')");
		$conn->Execute("REPLACE INTO alienvault.plugin_group (group_id,name,descr) VALUES (UNHEX('$new_id'), 'Network anomalies', 'Network anomalies signatures')");	
	}	
}

/* End of file plugin_group.inc */
/* Location: ../include/classes/plugin_group.inc */
