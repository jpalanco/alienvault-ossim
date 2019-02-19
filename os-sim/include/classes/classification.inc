<?php
/**
* classification.inc
*
* File classification.inc is used to:
*   - Manage the data in classification table
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
* - Classification()
* - get_id()
* - get_name()
* - get_description()
* - get_priority()
* - get_list()
*/

class Classification
{
    /**
     * Classification ID
     *
     * @var int
     * @access public
     */
    public $id;
    
    /**
     * Classification name
     *
     * @var string
     * @access public
     */
    public $name;
    
    /**
     * Classification description
     *
     * @var string
     * @access public
     */
    public $description;
    
    /**
     * Classification priority
     *
     * @var int
     * @access public
     */
    public $priority;
    
    /**
     * Class Constructor
     * 
     * This function sets up the class
     * 
     * @param int    $id
     * @param string $name
     * @param string $description
     * @param int    $priority
     */
    public function __construct($id, $name, $description, $priority)
    {
        $this->id          = $id;
        $this->name        = $name;
        $this->description = $description;
        $this->priority    = $priority;
    }
    
    /**
     * This function returns the classification id
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }
    
    /**
     * This function returns the classification name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }
    
    /**
     * This function returns the classification description
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }
    
    /**
     * This function returns the classification priority
     *
     * @return int
     */
    public function get_priority()
    {
        return $this->priority;
    }


    /**
     * This function returns the classification table data list
     * 
     * @param object $conn
     * @param string $args
     * 
     * @return array
     */
    public static function get_list($conn, $args = '')
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT * FROM classification $args");
        
        $rs = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $list = array();
        
        while (!$rs->EOF)
        {
            $list[] = new self(
                $rs->fields["id"],
                $rs->fields["name"],
                $rs->fields["description"],
                $rs->fields["priority"]);

            $rs->MoveNext();
        }

        return $list;
    }
}

/* End of file classification.inc */
/* Location: ../includes/classification.inc */
