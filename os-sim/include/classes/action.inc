<?php
/**
* Action.inc
*
* File Action.inc is used to:
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
* - Action()
* - get_id()
* - get_action_type()
* - get_descr()
* - get_foundrows()
* - get_action_by_id()
* - get_action()
* - get_list()
* - insert()
* - insertEmail()
* - insertExec()
* - update()
* - updateEmail()
* - updateExec()
* - delete()
*/

class Action
{
    public $id;

    public $ctx;

    public $name;

    public $action_type;

    public $cond;

    public $on_risk;

    public $descr;

    public $foundrows;


    public function __construct($id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $foundrows = 0)
    {
        $this->id          = $id;
        $this->ctx         = $ctx;
        $this->name        = $name;
        $this->action_type = $action_type;
        $this->cond        = $cond;
        $this->on_risk     = $on_risk;
        $this->descr       = Util::htmlentities($descr);
        $this->foundrows   = $foundrows;
    }


    public function get_id()
    {
        return $this->id;
    }


    public function get_ctx()
    {
        return $this->ctx;
    }


    public function get_name()
    {
        return $this->name;
    }


    public function get_action_type()
    {
        return $this->action_type;
    }


    public function get_cond()
    {
        return $this->cond;
    }


    public function is_on_risk()
    {
        return $this->on_risk;
    }


    public function get_descr($entities = TRUE)
    {
        $data = preg_replace("/##@##.*/s","",$this->descr);
          
        if ($entities)
        {
            return Util::htmlentities($data);
        }
        else
        {
            return $data;
        }
    }


    public function get_foundrows()
    {
        return $this->foundrows;
    }


    public function get_action_by_id($conn, $id)
    {
        $query  = "SELECT *, HEX(id) AS id, HEX(ctx) AS ctx FROM action WHERE id = UNHEX(?)";
        $params = array($id);
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $action = new self($rs->fields["id"], $rs->fields["ctx"], $rs->fields["name"], $rs->fields["action_type"], $rs->fields["cond"], $rs->fields["on_risk"], $rs->fields["descr"]);
            }
        }

        return $action;
    }


    /* get an action object of email or exec classes */
    public function get_action($conn)
    {
        $id   = $this->id;
        $type = intval($this->action_type);

        if ($type == 1)
        {
            $action_list = Action_email::get_list($conn, " AND action_id = UNHEX('$id') ");

            if (is_array($action_list))
            {
                return $action_list[0];
            }
        }
        elseif ($type == 2)
        {
            $action_list = Action_exec::get_list($conn, " AND action_id = UNHEX('$id') ");

            if (is_array($action_list))
            {
                return $action_list[0];
            }
        }
        elseif ($type == 3)
        {
        
            return preg_replace("/.*##@##/s","",$this->descr);
        }

        return NULL;
    }


    public function get_action_type_text($conn)
    {
         list($action_type) = Action_type::get_list($conn, "AND type = ".$this->action_type);

         $type_name = 'Unknown';

         if (is_object($action_type))
         {
             $type_name = $action_type->get_name();
         }

         return $type_name;
    }


    public static function get_list($conn, $args = '')
    {
        $list = array();

        $q_where = '';

        $ctxs = Session::get_ctx_where();
        if ($ctxs != '')
        {
            $q_where .= " AND ctx IN ($ctxs)";
        }

        $q_where .= ' '.$args;


        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;

        $select      = "*, HEX(id) AS id, HEX(ctx) AS ctx";
        $select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS ". $select : $select;
        $query       = ossim_query("SELECT $select FROM action WHERE 1=1 $q_where");

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $foundrows = 0;

            if ($iffoundrows)
            {
                $rf = $conn->Execute("SELECT FOUND_ROWS() AS total");

                if (!$rf)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                else
                {
                    $foundrows = $rf->fields['total'];
                }
            }

            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields['id'], $rs->fields['ctx'], $rs->fields['name'], $rs->fields['action_type'], $rs->fields['cond'], $rs->fields['on_risk'], $rs->fields['descr'], $foundrows);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr)
    {
        $id    = Util::uuid();
        $query = "INSERT INTO action (id, ctx, name, action_type, cond, on_risk, descr) VALUES (UNHEX(?),UNHEX(?), ?, ?, ?, ?, ?)";

        $params = array(
            $id,
            $ctx,
            $name,
            $action_type,
            $cond,
            $on_risk,
            $descr
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array($action_type, "(".$descr.")");
        Log_action::log(82, $infolog);

        if (Policy_action::is_action_in_policies($conn, $id))
        {
            Web_indicator::set_on('Reload_policies');
        }

        return $id;
    }


    public static function insertEmail($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr, $from, $to, $subject, $message, $message_suffix)
    {
        $id = self::insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr);

        Action_email::insert($conn, $id, $from, $to, $subject, $message, $message_suffix);
    }


    public static function insertExec($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr, $command)
    {
        $id = self::insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr);

        Action_exec::insert($conn, $id, $command);
    }


    public static function update($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr)
    {
        // Delete old data
    
        if ($action_type == '1' || $action_type == '3')
        {
            $exec_list = Action_exec::get_list($conn, 'AND action_id = UNHEX("' . $id . '")');
        
            if (count($exec_list) > 0)
            {
                Action_exec::delete($conn, $id);
            }
        }
        
        if ($action_type == '2' || $action_type == '3')
        {
            $email_list = Action_email::get_list($conn, 'AND action_id = UNHEX("' . $id . '")');
        
            if (count($email_list) > 0)
            {
                Action_email::delete($conn, $id);
            }
        }
    
        $query = "UPDATE action SET ctx = UNHEX(?), name = ?, action_type = ?, cond = ?, on_risk = ?, descr = ? WHERE id = UNHEX(?)";

        $params = array(
            $ctx,
            $name,
            $action_type,
            $cond,
            $on_risk,
            $descr,
            $id
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array($action_type, "(".$descr.")");
        Log_action::log(83, $infolog);

        if (Policy_action::is_action_in_policies($conn, $id))
        {
            Web_indicator::set_on("Reload_policies");
        }
    }


    public static function updateEmail($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $from, $to, $subject, $message, $message_suffix)
    {
        self::update($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr);

        Action_email::update($conn, $id, $from, $to, $subject, $message, $message_suffix);
    }


    public static function updateExec($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $command)
    {
        self::update($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr);

        Action_exec::update($conn, $id, $command);
    }


    public static function delete($conn, $id)
    {
        $desc = $conn->GetOne("SELECT descr FROM action WHERE id=UNHEX('$id')");

        $action = self::get_action_by_id($conn, $id);
        $type   = $action->get_action_type();
        $query  = "DELETE FROM action WHERE id = UNHEX(?)";

        $params = array($id);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        switch(intval($type))
        {
            case 1:
                Action_email::delete($conn, $id);
            break;

            case 2:
                Action_exec::delete($conn, $id);
            break;
        }

        if (Policy_action::is_action_in_policies($conn, $id))
        {
            Policy_action::delete_by_action($conn, $id);
            Web_indicator::set_on("Reload_policies");
        }

        $infolog = array($desc);

        Log_action::log(84, $infolog);
    }


    public static function check_duplicated($conn, $name)
    {
        $ctxs = Session::get_ctx_where();

        if ($ctxs != '')
        {
            $ctxs = " AND ctx IN ($ctxs)";
        }

        $query = ossim_query("SELECT count(*) as names FROM action WHERE 1=1 $ctxs AND UPPER(TRIM(?)) = UPPER(TRIM(name))");

        $params = array($name);
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            return FALSE;
        }

        if ($rs->fields['names'] == 0)
        {
            return TRUE;
        }

        return FALSE;
    }
}

/* End of file action.inc */
/* Location: ../include/classes/action.inc */
