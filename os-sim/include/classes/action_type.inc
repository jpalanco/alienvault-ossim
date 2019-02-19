<?php
/**
* Action_type.inc
*
* File Action_type.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
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
* @package    ossim-framework\Policy&Actions
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Action_type()
* - get_type()
* - get_descr()
* - get_list()
*/

class Action_type
{
    public $type;

    public $descr;

    public $name;


    public function __construct($type, $descr, $name)
    {
        $this->type = $type;
        $this->descr = Util::htmlentities($descr);
        $this->name  = Util::htmlentities($name);
    }


    public function get_type()
    {
        return $this->type;
    }


    public function get_name()
    {
        return $this->name;
    }


    public function get_descr()
    {
        return $this->descr;
    }


    public static function get_list($conn, $args = '')
    {
        $query = ossim_query("SELECT * FROM action_type where 1=1 $args");
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();

            while (!$rs->EOF)
            {
                if ($rs->fields['name'] != 'exec' || ($rs->fields['name'] == 'exec' && Session::am_i_admin()))
                {
                    $list[] = new self($rs->fields['type'], $rs->fields['descr'], $rs->fields['name']);
                }

                $rs->MoveNext();
            }
        }

        return $list;
    }
}

/* End of file action_type.inc */
/* Location: ../include/classes/action_type.inc */
