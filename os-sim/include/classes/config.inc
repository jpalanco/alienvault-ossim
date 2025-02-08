<?php
/**
* Config.inc
*
* File Config.inc is used to:
*   - To manage user configuration
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

require_once __DIR__ . '/Security.inc';


/**
* Config Class
*
* Class for getting and setting Framework settings from database
*
* @package    ossim-framework\Configuration
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Config
{
    /**
    * @var string  User UUID (Unique ID for user)
    * @access private
    */
    private $uuid;


    /**
    * @var array Configuration array
    * @access private
    *
    */
    private $confs;


    /**
    * @var object Database object
    * @access private
    *
    */
    private $db;


    /**
     * Class constructor
     *
     * This function sets up the class
     */
    public function __construct()
    {
        $this->confs = array();

        $this->db    = new ossim_db();
        $conn        = $this->db->connect();

        $this->uuid  = Util::get_encryption_key();
        $query       = "SELECT *, AES_DECRYPT(value,'".$this->uuid."') AS value_decrypt FROM config";

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            $this->db->close();

            Av_exception::throw_error(Av_exception::USER_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $key   = $rs->fields["conf"];
            if (preg_match("/_pass$/", $rs->fields['conf']) || preg_match("/_key$/", $rs->fields['conf']))
            {
                $value = (empty($rs->fields['value_decrypt'])) ? $rs->fields['value'] : $rs->fields['value_decrypt'];
            }
            else
            {
                $value = $rs->fields['value'];
            }

            $this->confs[$key] = $value;

            $rs->MoveNext();
        }

        $this->db->close();
    }


    /**
     * This function creates an object instance
     *
     * @return Config
     */
    public static function factory(){
        return new Config();
    }


    /**
     * This function returns TRUE if configuration key exists
     *
     * @param string $key  Key to search
     *
     * @return boolean
     */
    public function has_conf($key)
    {
        return array_key_exists($key, $this->confs);
    }


    /**
     * This function returns the value for key $key
     *
     * @param string $key  Key to search
     *
     * @return string
     */
     public function get_conf($key)
     {
        if ($this->has_conf($key))
        {
            return $this->confs[$key];
        }
        else
        {
            return NULL;
        }
    }


    /**
     * This function returns all configuration values available
     *
     * @return array
     */
	public function get_list()
	{
        return $this->confs;
    }


    /**
    * This function inserts a new configuration value (Pair key-value)
    *
    * @param string $key    New key
    * @param string $value  Value of key
    *
    * @throws Exception  If a connection error occurred
    */
    public function insert($key, $value)
    {
        ossim_valid($key,    OSS_LETTER, OSS_SCORE, OSS_DIGIT, 'illegal:' . _($key));

        if (ossim_error())
        {
            $exp_msg = ossim_get_error_clean();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if (preg_match('/_pass$/', $key) || preg_match('/_key$/', $key))
        {
            $q_pass  = (!empty($value)) ? "AES_ENCRYPT(?,'".$this->uuid."')" : '?';
            $query = 'INSERT INTO config (conf, value) VALUES (?, '.$q_pass.')';
        }
        else
        {
            $query    = "INSERT INTO config (conf, value) VALUES (?, ?)";
        }


        $params = array($key, $value);

        $conn   = $this->db->connect();

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $this->db->close();
    }


    /**
     * This function updates a configuration key
     *
     * @param string $key    key to search
     * @param string $value  New value
     *
     * @throws Exception  If a connection error occurred
     */
    public function update($key, $value)
    {
        ossim_valid($key, OSS_LETTER, OSS_SCORE, OSS_DIGIT, 'illegal:' . _($key));

        if (ossim_error())
        {
            $exp_msg = ossim_get_error_clean();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query  = "DELETE FROM config WHERE conf = ?";
        $params = array($key);

        $conn   = $this->db->connect();

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $this->db->close();

        $this->insert($key, $value);
    }
}

/* End of file config.inc */
/* Location: ../include/classes/config.inc */
