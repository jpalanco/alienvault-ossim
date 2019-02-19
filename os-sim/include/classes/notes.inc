<?php
/**
* notes.inc
*
* File notes.inc is used to:
*   - Manage the info in database notes table
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



class Notes
{
    /**
     * Note ID
     * 
     * @var number
     */
    public $id;


    /**
     * Type (host, net, group, etc.)
     * 
     * @var string
     */
    public $type;


    /**
     * Date
     * 
     * @var string
     */
    public $date;


    /**
     * User
     * 
     * @var string
     */
    public $user;


    /**
     * Asset ID
     * 
     * @var string
     */
    public $asset_id;


    /**
     * Txt
     * 
     * @var string
     */
    public $txt;


    /**
     * This function sets up the class object
     * 
     * @param number $id
     * @param string $type
     * @param string $date
     * @param string $user
     * @param string $asset_id
     * @param string $txt
     */
    public function __construct($id, $type, $date, $user, $asset_id, $txt) 
    {
        $this->id       = $id;
        $this->type     = $type;
        $this->date     = $date;
        $this->user     = $user;
        $this->asset_id = $asset_id;
        $this->txt      = $txt;
    }


    /**
     * This function returns the id
     * 
     * @return number
     */
    public function get_id()
    {
        return $this->id;
    }
    

    /**
     * This function returns the type
     * 
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }


    /**
     * This function returns the date
     * 
     * @return string
     */
    public function get_date()
    {
        return $this->date;
    }


    /**
     * This function returns the user
     * 
     * @return string
     */
    public function get_user()
    {
        return $this->user;
    }
    

    /**
     * This function returns the asset id
     * 
     * @return string
     */
    public function get_asset_id()
    {
        return $this->asset_id;
    }
    

    /**
     * This function returns the txt
     * 
     * @return string
     */
    public function get_txt()
    {
        return $this->txt;
    }


    /**
     * This function returns the notes list
     * 
     * @param object $conn
     * @param string $where
     * 
     * @return multitype:Notes
     */
    public static function get_list($conn, $filters)
    {
        Ossim_db::check_connection($conn);

        $list   = array();

        $tz     = Util::get_timezone();
        
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
            $q_where  .= ' LIMIT '.$filters['limit'];
        }

        $query = ossim_query("SELECT SQL_CALC_FOUND_ROWS notes.*, id, date, user, note, HEX(asset_id) AS asset_id FROM notes WHERE 1=1 $q_where");

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else
        {
            while (!$rs->EOF)
            {
                $rs->fields['date'] = gmdate('Y-m-d H:i:s', Util::get_utc_unixtime($rs->fields['date'])+3600*$tz);
                
                $list[] = new self($rs->fields['id'], $rs->fields['type'], $rs->fields['date'], $rs->fields['user'], 
                    $rs->fields['asset_id'], $rs->fields['note']);
                
                $rs->MoveNext();
            }
        }

        $total = intval(Ossim_db::get_found_rows($conn, $query));

        return array($list, $total);
    }

    
    /**
     * This function inserts a note into database
     * 
     * @param object $conn
     * @param string $type
     * @param string $date
     * @param string $asset_id
     * @param string $txt
     * 
     * @return boolean
     */
    public static function insert($conn, $type, $date, $asset_id, $txt) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array(
            $type,
            $date,
            Session::get_session_user(),
            $asset_id,
            strip_tags($txt)
        );
        
        $query = 'INSERT INTO notes (type, date, user, asset_id, note) VALUES (?, ?, ?, UNHEX(?), ?)';
        
        if (!$conn->Execute($query, $params))
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            return 0;
        }

        // get lastid
        $res = $conn->query('SELECT LAST_INSERT_ID() as lastid');
        
        if ($rw = $res->fetchRow())
        {
            $note_id = intval($rw['lastid']);
        }
        
        return $note_id;
    }

    /**
     * This function inserts a note into database
     * 
     * @param object $conn
     * @param string $type
     * @param string $date
     * @param string $txt
     * 
     * @return boolean
     */
    public static function bulk_insert($conn, $type, $date, $txt) 
    {
        Ossim_db::check_connection($conn);
        
        $ntype = ($type == 'asset') ? 'host' : (($type == 'group') ? 'host_group' : (($type == 'network') ? 'net' : $type));

        $params = array(
            $ntype,
            $date,
            Session::get_session_user(),
            strip_tags($txt),
            $type,
            session_id()
        );
        
        $query = "INSERT INTO notes (type, date, user, asset_id, note) SELECT ?, ?, ?, asset_id, ? FROM user_component_filter WHERE asset_type=? AND session_id=?";

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            return FALSE;
        }
        return TRUE;
    }

    /**
     * This function updates a note in database
     * 
     * @param object $conn
     * @param number $id
     * @param string $date
     * @param string $txt
     * 
     * @return boolean
     */
    public static function update($conn, $id, $date, $txt)
    {
        Ossim_db::check_connection($conn);
        
        if (self::allowed($conn, $id)) 
        {
            $params = array($date, strip_tags($txt), $id);
            $query  = 'UPDATE notes SET date = ?, note = ? WHERE id = ?';

            if (!$conn->Execute($query, $params))
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }

        return FALSE;
    }


    /**
     * This function deletes a note in database
     * 
     * @param object $conn
     * @param number $id
     * 
     * @return boolean
     */
    public static function delete($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        if (self::allowed($conn, $id))
        {
            $params = array($id);
            $query  = "DELETE FROM notes WHERE id = ?";
            
            if (!$conn->Execute($query, $params))
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }

        return FALSE;
    }

    
    /**
     * This function returns the count of notes by asset
     * 
     * @param object $conn
     * @param string $asset_id
     * @param string $type
     * 
     * @return number
     */
    public static function howmanynotes($conn, $asset_id, $type)
    {
        Ossim_db::check_connection($conn);

        $params = array($type, $asset_id);
        $query  = "SELECT count(*) FROM notes WHERE type=? AND asset_id=unhex(?)";

        return $conn->getone($query, $params);
    }


    /**
     * This function returns if the user is allowed to see a note
     * 
     * @param object $conn
     * @param number $id
     * 
     * @return boolean
     */
    public static function allowed($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        $filters  = array();

        $filters['where'] = "id = ".intval($id);
        
        // Allowed to view/edit user note
        list($notes, $total) = self::get_list($conn, $filters);
        
        if ($total != 1)
        {
            return FALSE;
        }

        $users = Session::get_users_to_assign($conn);

        foreach ($users as $user)
        {
            if ($user->get_login() == $notes[0]->get_user())
            {
                return TRUE;
            }
        }
        
        return FALSE;
    }
}

/* End of file notes.inc */
/* Location: ../include/classes/notes.inc */
