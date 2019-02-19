<?php
/**
* incident_custom.inc
*
* File incident_custom.inc is used to:
*   - Manage the data in incident_custom database table
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
* - get_list()
* - get_custom_types()
* - insert_custom()
* - update_custom()
* - delete_custom()
* - update_ord()
* - get_next_ord()
*/

class Incident_custom
{
    public $id;


    public $incident_id;


    public $name;


    public $type;


    public $content;


    public function get_id()
    {
        return $this->id;
    }


    public function get_incident_id()
    {
        return $this->incident_id;
    }


    public function get_name()
    {
        return $this->name;
    }


    public function get_type()
    {
        return $this->type;
    }


    public function get_content()
    {
        return Util::htmlentities($this->content);
    }


    public function __construct($id, $incident_id, $name, $type, $content)
    {
        $this->id          = $id;
        $this->incident_id = $incident_id;
        $this->name        = $name;
        $this->type        = $type;
        $this->content     = $content;
    }


    public static function get_list($conn, $args)
    {
        $list  = array();
        $query = ossim_query("SELECT * FROM incident_custom $args");
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields["name"]] = new self($rs->fields["id"], $rs->fields["incident_id"], $rs->fields["name"], $rs->fields["incident_custom_type_id"], $rs->fields["content"]);
                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function get_custom_types($conn, $id)
    {
        $list = array();

        $params = array($id);
        $query  = ossim_query("SELECT * FROM incident_custom_types WHERE id=? ORDER BY ord ASC");

        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = array("id"       => $rs->fields['id'],
                                "name"     => $rs->fields['name'],
                                "type"     => $rs->fields['type'],
                                "options"  => $rs->fields['options'],
                                "required" => $rs->fields['required'],
                                "ord"      => $rs->fields['ord']);
                $rs->MoveNext();
            }
        }

        return $list;
    }

    static function insert_custom($conn, $params)
    {
        $query = "INSERT IGNORE into incident_custom_types (id, name, type, options, required, ord) VALUES (?, ?, ?, ?, ?, ?)";

        if ($conn->Execute($query, $params) == FALSE)
        {
             Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete_custom($conn, $id, $name)
    {
        $query = "DELETE FROM incident_custom_types WHERE id=? AND name=?";
        $params = array($id, $name);

        if ($conn->Execute($query, $params) == FALSE)
        {
             Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function update_custom($conn, $custom_name, $custom_type, $custom_options, $custom_required,  $inctype_id, $custom_old_name)
    {
        $query = "UPDATE incident_custom_types SET name= ?, type= ?, options= ?, required=? WHERE id = ? and name = ?";

        $params = array(
            $custom_name,
            $custom_type,
            $custom_options,
            $custom_required,
            $inctype_id,
            $custom_old_name
        );

        if ($conn->Execute($query, $params) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function update_ord($conn, $oldpos, $newpos, $inctype_id)
    {
        if ($oldpos < 0)
        {
            $oldpos = 0; // safe check
        }

        if ($newpos < 0)
        {
            $newpos = 0;
        }

        $query = "UPDATE incident_custom_types SET ord=-1 WHERE id = ? AND ord = ?";

        if ($conn->Execute($query, array($inctype_id,$newpos)) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "UPDATE incident_custom_types SET ord=? WHERE id = ? AND ord = ?";
        if ($conn->Execute($query, array($newpos,$inctype_id,$oldpos)) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "UPDATE incident_custom_types SET ord=? WHERE id = ? AND ord = -1";
        if ($conn->Execute($query, array($oldpos,$inctype_id)) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function get_next_ord($conn, $inctype_id)
    {
        $query  = ossim_query("SELECT max(ord) as max FROM incident_custom_types WHERE id = ?");
        $params = array($inctype_id);

        $rs    = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $max = $rs->fields['max'] + 1;
        }
        else
        {
            $max = 1;
        }

        return $max;
    }
}

/* End of file incident_custom.inc */
/* Location: ../include/classes/incident_custom.inc */
