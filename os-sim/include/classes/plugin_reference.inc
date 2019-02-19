<?php
/**
* plugin_reference.inc
*
* File plugin_reference.inc is used to:
*   - Manage the section of manage references
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
* - Plugin_reference()
* - get_plugin_id()
* - get_plugin_sid()
* - get_reference_id()
* - get_reference_sid()
* - get_count()
* - get_list()
*/

class Plugin_reference 
{
    /**
     * Plugin ID
     *
     * @var    int
     * @access public
     */
    public $plugin_id;


    /**
     * Plugin SID
     *
     * @var    int
     * @access public
     */
    public $plugin_sid;


    /**
     * Reference ID
     *
     * @var    int
     * @access public
     */
    public $reference_id;


    /**
     * Reference SID
     *
     * @var    int
     * @access public
     */
    public $reference_sid;


    /**
     * Class constructor
     *
     * This function sets up the class
     * 
     * @param unknown_type $plugin_id
     * @param unknown_type $plugin_sid
     * @param unknown_type $reference_id
     * @param unknown_type $reference_sid
     * @param unknown_type $foundrows
     */
    function __construct($plugin_id, $plugin_sid, $reference_id, $reference_sid,  $foundrows=0) 
    {
        $this->plugin_id     = $plugin_id;
        $this->plugin_sid    = $plugin_sid;
        $this->reference_id  = $reference_id;
        $this->reference_sid = $reference_sid;
        $this->foundrows     = $foundrows;
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
    function get_plugin_sid()
    {
        return $this->plugin_sid;
    }
    
    /**
     * This function returns the reference id
     *
     * @return int
     */
    function get_reference_id()
    {
        return $this->reference_id;
    }
    
    /**
     * This function returns the reference sid
     *
     * @return int
     */
    function get_reference_sid()
    {
        return $this->reference_sid;
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
     * This function returns the total count of references
     * 
     * @param  object $conn
     * 
     * @return int
     */
    function get_count($conn)
    {
        Ossim_db::check_connection($conn);
        
        $sql = "SELECT count(*) FROM plugin_reference";
        
        $rs = $conn->Execute($sql);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            return $rs->fields["count(*)"];
        }
    }


    /**
     * This function returns the list of references
     *
     * @param  object $conn
     * @param  string $args
     * 
     * @return int
     */
    static function get_list($conn, $args = "")
    {
        Ossim_db::check_connection($conn);
        
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $cfrows      = ( $iffoundrows ) ? "SQL_CALC_FOUND_ROWS " : "";
        $query       = ossim_query("SELECT $cfrows plugin_reference.plugin_id,
                            plugin_reference.plugin_sid,
                            plugin_reference.reference_id,
                            plugin_reference.reference_sid 
                            FROM plugin_reference $args");
        
        $list = array();
        
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
        
            while (!$rs->EOF)
            {
                $list[] = new self(
                    $rs->fields["plugin_id"], 
                    $rs->fields["plugin_sid"], 
                    $rs->fields["reference_id"], 
                    $rs->fields["reference_sid"], 
                    $foundrows
                );
                $rs->MoveNext();
            }
        }
        return $list;
    }

    
    /**
     * This function inserts a new row in plugin_reference table
     * 
     * @param object $conn
     * @param int    $plugin_id1
     * @param int    $plugin_id2
     * @param int    $plugin_sid1
     * @param int    $plugin_sid2
     * 
     * @return int
     */
    static function new_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2)
    {
        Ossim_db::check_connection($conn);

        if (!self::check_rule($conn, $plugin_id1, $plugin_sid1))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Unable to set cross-correlation rule. Selected "Data Source" and "Event Type" are included in another rule'));
        }

        $query = ossim_query("INSERT INTO plugin_reference (plugin_id,plugin_sid,reference_id,reference_sid) 
                VALUES (?, ?, ?, ?)");
        
        $params = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
        
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {
            $infolog = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
            Log_action::log(90, $infolog);
            
            return 0;
        }
    }


    /**
     * This function deletes a row from the plugin_reference table
     * 
     * @param object $conn
     * @param int    $plugin_id1
     * @param int    $plugin_id2
     * @param int    $plugin_sid1
     * @param int    $plugin_sid2
     * 
     * @return int
     */
    static function delete_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2)
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("DELETE FROM plugin_reference WHERE 
                plugin_id = ? AND plugin_sid = ? AND reference_id = ? AND reference_sid = ?");
        
        $params = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $infolog = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
            Log_action::log(91, $infolog);
            return 0;
        }
    }


    /**
     * This function modifies a rule in plugin_reference table
     * 
     * @param object $conn
     * @param int    $plugin_id1
     * @param int    $plugin_id2
     * @param int    $plugin_sid1
     * @param int    $plugin_sid2
     * @param int    $id1
     * @param int    $id2
     * @param int    $sid1
     * @param int    $sid2
     * 
     * @return int
     */
    static function change_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2, $id1, $id2, $sid1, $sid2)
    {
        Ossim_db::check_connection($conn);

        if (!self::check_rule($conn, $plugin_id1, $plugin_sid1))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Unable to set cross-correlation rule. Selected "Data Source" and "Event Type" are included in another rule'));
        }

        $query = ossim_query("UPDATE plugin_reference SET plugin_id = ?, plugin_sid = ?, reference_id = ?, reference_sid = ? 
                WHERE plugin_id = ? AND reference_id = ? AND plugin_sid = ? AND reference_sid = ?");
        
        $params = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2, $id1, $id2, $sid1, $sid2);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $infolog = array($id1, $sid1, $id2, $sid2);
            Log_action::log(91, $infolog);
            
            $infolog = array($plugin_id1, $plugin_sid1, $plugin_id2, $plugin_sid2);
            Log_action::log(90, $infolog);

            return 0;
        }
    }


    /**
     * This function check if exists another rule for given id/sid pair
     * 
     * @param object $conn
     * @param int    $plugin_id
     * @param int    $plugin_sid
     * 
     * @return int
     */
    static function check_rule($conn, $plugin_id, $plugin_sid)
    {
        Ossim_db::check_connection($conn);

        $query = ossim_query("SELECT count(*) as total FROM plugin_reference WHERE plugin_id = ? AND plugin_sid = ?");

        $params = array($plugin_id, $plugin_sid);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (intval($rs->fields['total']) > 1)
            {
                return FALSE;
            }
        }

        return TRUE;
    }
}

/* End of file plugin_reference.inc */
/* Location: ../includes/plugin_reference.inc */
