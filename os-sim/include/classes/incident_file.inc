<?php
/**
* Incident_file.inc
*
* File Incident_file.inc is used to:
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
* @package    ossim-framework\Incidents
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Incident_file()
* - get_id()
* - get_incident_id()
* - get_incident_ticket()
* - get_name()
* - get_type()
* - get_content()
* - get_list()
* - insert()
* - delete()
*/

class Incident_file
{
    public $id;


    public $incident_id;


    public $incident_ticket;


    public $name;


    public $type;


    public $content;


    public function __construct($id, $incident_id, $incident_ticket, $name, $type, $content)
    {
        $this->id              = intval($id);
        $this->incident_id     = intval($incident_id);
        $this->incident_ticket = intval($incident_ticket);
        $this->name            = $name;
        $this->type            = $type;
        $this->content         = $content;
    }


    public function get_id()
    {
        return $this->id;
    }


    public function get_incident_id()
    {
        return $this->incident_id;
    }


    public function get_incident_ticket()
    {
        return $this->incident_ticket;
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
        return $this->content;
    }


    public static function get_list($conn, $args)
    {
        $list  = array();

        $query = ossim_query("SELECT * FROM incident_file $args");
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["id"], $rs->fields["incident_id"], $rs->fields["incident_ticket"], $rs->fields["name"], $rs->fields["type"], $rs->fields["content"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $incident_id, $incident_ticket, $name, $type, $content)
    {
        $incident_id     = intval($incident_id);
        $incident_ticket = intval($incident_ticket);

        $query = "INSERT into incident_file (incident_id, incident_ticket, name, type, content)
                VALUES (?, ?, ?, ?, ?)";

        $params = array(
            $incident_id,
            $incident_ticket,
            $name,
            $type,
            $content
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public function delete($conn, $id, $incident_id, $incident_ticket)
    {
        $id              = intval($id);
        $incident_id     = intval($incident_id);
        $incident_ticket = intval($incident_ticket);

        $query = "DELETE FROM incident_file WHERE id = ? AND incident_id = ? AND incident_ticket = ?";

        $params = array(
            $id,
            $incident_id,
            $incident_ticket
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file incident_file.inc */
/* Location: ../include/classes/incident_file.inc */
