<?php
/**
* Action_email.inc
*
* File Action_email.inc is used to:
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
* - Action_email()
* - get_action_id()
* - get_from()
* - get_to()
* - get_subject()
* - get_message()
* - get_list()
* - insert()
* - update()
*/


class Action_email
{
    public $action_id;

    public $from;

    public $to;

    public $subject;

    public $message;


    public function __construct($action_id, $from, $to, $subject, $message, $message_suffix)
    {
        $this->action_id = $action_id;
        $this->from      = $from;
        $this->to        = $to;
        $this->subject   = $subject;
        $this->message   = $message;
        $this->message_suffix   = $message_suffix;
    }


    public function get_action_id()
    {
        return $this->action_id;
    }


    public function get_from()
    {
        return $this->from;
    }


    public function get_to()
    {
        return $this->to;
    }


    public function get_subject()
    {
        return $this->subject;
    }


    public function get_message()
    {
        return $this->message;
    }

    public function get_message_suffix()
    {
        return $this->message_suffix;
    }


    public static function get_list($conn, $args = '')
    {
        $query = ossim_query("SELECT *, HEX(action_id) as action_id FROM action_email where 1=1 $args");

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();

            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["action_id"], $rs->fields["_from"], $rs->fields["_to"], $rs->fields["subject"], $rs->fields["message"],$rs->fields["message_suffix"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $id, $from, $to, $subject, $message,$message_suffix)
    {
        $query = "INSERT INTO action_email (action_id, _from, _to, subject, message, message_suffix)
                    VALUES (UNHEX(?), ?, ?, ?, ?, ?)";
        $params = array(
            $id,
            $from,
            $to,
            $subject,
            $message,
            $message_suffix
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function update($conn, $id, $from, $to, $subject, $message, $message_suffix)
    {
        $query = "REPLACE INTO action_email (action_id, _from, _to, subject, message, message_suffix) VALUES (UNHEX(?), ?, ?, ?, ?, ?)";

        $params = array(
            $id,
            $from,
            $to,
            $subject,
            $message,
            $message_suffix
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete($conn, $id)
    {
        $query = "DELETE FROM action_email WHERE action_id = UNHEX(?)";
        $params = array(
            $id
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file action_email.inc */
/* Location: ../include/classes/action_email.inc */
