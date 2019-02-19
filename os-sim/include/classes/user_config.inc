<?php
/**
* user_config.inc
*
* File user_config.inc is used to:
*   - Manage the user config data in user_config table
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
* @package    ossim-framework\Configuration
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - User_config()
* - get()
* - set()
* - del()
* - copy_panel()
*/

/*
CREATE TABLE user_config (
login VARCHAR(64)  NOT NULL REFERENCES users (login),
category VARCHAR(64) NOT NULL DEFAULT 'main',
name VARCHAR(64) NOT NULL,
value TEXT,
PRIMARY KEY (login, category, name)
);

Usage:

$db = new ossim_db();
$conn = $db->connect();

$config = new User_config($conn);
$login = Session::get_session_user();

// Get stored var as simple text (default)

$var = $config->get($login, 'var_name', 'simple');

// Save a var into the database as a serialized PHP var

$config->set($login, 'var_name', $myvar, 'php');

// Removes a config option

$config->del($login, 'var_name');

*/

class User_config
{
    /**
     * Database connection
     * @var object
     */
    public $conn;
    
    /**
     * This function sets up the class object
     * 
     * @param object $conn
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    
    /**
     * This function returns the data list from user_config database table with some filters
     * 
     * @param $login string - The user's login name
     * @param $name string - What config entry name to retrieve
     * @param $type string - 'simple' for storing simple values (default) or
     *                       'php'  for storing PHP variables
     * @param $category - What's the category of the $name entry
     */
    public function get($login, $name, $type = 'simple', $category = 'main')
    {
        $query = "SELECT value
                FROM user_config
                WHERE login=? AND category=? AND name=?";
        
        $params = array(
            $login,
            $category,
            $name
        );

        $rs = $this->conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }

        if ($rs->EOF)
        {
            return NULL;
        }
        
        $value = $rs->fields['value'];

        if ($type == 'simple')
        {
            return $value;
        }
        elseif ($type == 'php')
        {
            return unserialize($value);
        }
        else
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid config type'));
        }
    }


    /**
     * This function returns the data list into user_config table
     * 
     * @param string $login
     * @param string $category
     * @return array
     */
    public function get_all($login, $category = 'main')
    {
        $ret = array();
        
        $query = "SELECT name
                FROM user_config
                WHERE login=? AND category=?";

        $params = array(
            $login,
            $category
        );

        $rs = $this->conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ret[] = $rs->fields['name'];
            $rs->MoveNext();
        }

        return $ret;
    }

    
    /**
     * This function updates some value in user_config database table
     * 
     * @param string $login
     * @param string $name
     * @param string $value
     * @param string $type
     * @param string $category
     * 
     * @return boolean
     */
    public function set($login, $name, $value, $type = 'simple', $category = 'main')
    {
        if ($type == 'simple')
        {
            $val = $value;
        }
        elseif ($type == 'php')
        {
            $val = serialize($value);
        }
        else
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid config type'));
        }

        $query    = "REPLACE INTO user_config (login, category, name, value) VALUES (?, ?, ?, ?)";
        $params = array(
                $login,
                $category,
                $name,
                $val
        );

        if (!$this->conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
     * This method changes the name of a config property
     * 
     * @param string $rename
     * @param string $login
     * @param string $name
     * @param string $category
     * 
     * @return boolean
     */
    public function rename($rename, $login, $name, $category = 'main')
    {
        $query  = "UPDATE user_config SET name=? WHERE login=? AND name=? AND category=?";
        $params = array(
            $rename,
            $login,
            $name,
            $category
        );
        
        if (!$this->conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }

        return TRUE;
    }
    
    /**
     * This function removes a config property in user_config table
     * 
     * @param string $login
     * @param string $name
     * @param string $category
     * 
     * @return boolean
     */
    public function del($login, $name, $category = 'main')
    {
        $query  = "DELETE FROM user_config WHERE login=? AND name=? AND category=?";
        $params = array($login, $name, $category);

        if (!$this->conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        
        return TRUE;
    }


    /**
     * This function removes all properties for an user
     * 
     * @param object $conn
     * @param string $login
     * 
     * @return boolean
     */
    public static function deleteall($conn, $login)
    {
        Ossim_db::check_connection($conn);

        $query  = "DELETE FROM user_config WHERE login=?";
        $params = array($login);
        
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return TRUE;
    }

    
    /**
     * This function copies a panel for a specific user
     * 
     * @param object $conn
     * @param string $login
     * 
     * @return boolean
     */
    public static function copy_panel($conn, $login)
    {
        Ossim_db::check_connection($conn);
        
        $pro = Session::is_pro();
        
        if($pro)
        {
            $query = "
            INSERT INTO `dashboard_tab_options` (`id`, `user`, `visible`, `tab_order`) VALUES
                (1, ?, 1, 12),
                (2, ?, 1, 11),
                (3, ?, 1, 10),
                (4, ?, 1, 9),
                (5, ?, 0, 8),
                (6, ?, 1, 7),
                (7, ?, 1, 6),
                (8, ?, 0, 5),
                (9, ?, 0, 4),
                (10, ?, 0, 3),
                (11, ?, 0, 2),
                (12, ?, 0, 1);";

            $params = array(
                $login, 
                $login,
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login,
                $login,
                $login
            );
         }
         else
         {
            $query = "
            INSERT INTO `dashboard_tab_options` (`id`, `user`, `visible`, `tab_order`) VALUES
                (1, ?, 1, 11),
                (2, ?, 1, 10),
                (3, ?, 1, 9),
                (4, ?, 1, 8),
                (5, ?, 0, 7),
                (6, ?, 1, 6),
                (8, ?, 0, 5),
                (9, ?, 0, 4),
                (10, ?, 0, 3),
                (11, ?, 0, 2),
                (12, ?, 0, 1);";    

            $params = array(
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login, 
                $login,
                $login,
                $login
            );
        }
        
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }

    
    /**
     * This function removes all panels for an user
     * 
     * @param object $conn
     * @param string $login
     * 
     * @return boolean
     */
    public static function delete_panels($conn, $login)
    {
        Ossim_db::check_connection($conn);

        $query  = "DELETE FROM dashboard_widget_config WHERE user=?";
        $params = array($login);

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $query  = "DELETE FROM dashboard_tab_config WHERE user=?";
        $params = array($login);
        
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $query  = "DELETE FROM dashboard_tab_options WHERE user=?";
        $params = array($login);

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }
}

/* End of file user_config.inc */
/* Location: ../include/classes/user_config.inc */
