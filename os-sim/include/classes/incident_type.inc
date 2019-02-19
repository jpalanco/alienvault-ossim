<?php
/**
* incident_type.inc
*
* File incident_type.inc is used to:
*   - Manage the data in incident_type database table
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
* @package    ossim-framework\Incidents
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* - get_id()
* - get_descr()
* - get_list()
* - insert()
* - update()
* - delete()
*/

class Incident_type
{
    public $id;


    public $descr;


    public function __construct($id, $descr, $keywords = '')
    {
        $this->id = $id;
        $this->descr = $descr;
        $this->keywords = $keywords;
    }


    public function get_id()
    {
        return $this->id;
    }


    public function get_descr($entities = TRUE)
    {
        if ($entities)
        {
            return Util::htmlentities($this->descr);
        }
        else
        {
            return $this->descr;
        }
    }


    public function get_keywords()
    {
        return $this->keywords;
    }


    public static function get_list($conn, $args = '')
    {
        $list = array();
        $query = ossim_query("SELECT * FROM incident_type $args");
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["id"], $rs->fields["descr"], $rs->fields["keywords"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $id, $descr, $keywords = '')
    {
        $query  = "INSERT into incident_type ( id, descr, keywords) VALUES   (?, ?, ?)";
        $params = array($id, $descr, $keywords);

        if ($conn->Execute($query, $params) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array($id);
        Log_action::log(53, $infolog);

        return TRUE;
    }


    public static function update($conn, $id, $descr, $keywords = '')
    {
        if ($keywords == 'custom')
        {
            $query  = "UPDATE incident_type SET descr= ?,keywords=TRIM(CONCAT(keywords,' custom')) WHERE id = ?";
            $params = array($descr, $id);
        }
        else
        {
            $query  = "UPDATE incident_type SET descr= ?,keywords=TRIM(REPLACE(keywords,'custom','')) WHERE id = ?";
            $params = array($descr, $id);
        }

        if ($conn->Execute($query, $params) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array($id);
        Log_action::log(52, $infolog);
    }


    public static function delete($conn, $id)
    {
        $query  = "DELETE FROM incident_type WHERE id = ?";
        $params = array($id);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query    = "UPDATE incident SET type_id = 'Generic' WHERE type_id = ?";
        $params = array($id);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array($id);
        Log_action::log(54, $infolog);
    }
}

/* End of file incident_type.inc */
/* Location: ../include/classes/incident_type.inc */
