<?php
/**
* Policy_action.inc
*
* File Policy_action.inc is used to:
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
* - Response_action()
* - get_response_id()
* - get_action_id()
* - get_list()
* - insert()
* - is_action_in_policies()
*/

class Policy_action
{
    public $policy_id;

    public $action_id;

    public function __construct($policy_id, $action_id)
    {
        $this->policy_id = $policy_id;
        $this->action_id = $action_id;
    }


    public function get_policy_id()
    {
        return $this->policy_id;
    }


    public function get_action_id()
    {
        return $this->action_id;
    }


    public static function get_list($conn, $policy_id)
    {
        $list      = array();
        $query     = "SELECT HEX(policy_id) AS policy_id, HEX(action_id) AS action_id FROM policy_actions WHERE policy_id = UNHEX(?)";
        $params    = array($policy_id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();

            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["policy_id"], $rs->fields["action_id"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $action_id, $policy_id)
    {
        $query  = "INSERT INTO policy_actions (policy_id, action_id) VALUES  (UNHEX(?), UNHEX(?))";
        $params = array(
            $policy_id,
            $action_id
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete($conn, $policy_id)
    {
        $query  = "DELETE FROM policy_actions WHERE policy_id=UNHEX(?)";
        $params = array(
            $policy_id
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete_by_action($conn, $action_id)
    {
        $query  = "DELETE FROM policy_actions WHERE action_id=UNHEX(?)";
        $params = array(
            $action_id
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function is_action_in_policies($conn, $action_id)
    {
        $query  = "SELECT 1 FROM policy_actions pa,policy p WHERE p.id=pa.policy_id AND p.active=1 AND pa.action_id = UNHEX(?)";
        $params = array($action_id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                return TRUE;
            }
        }

        return FALSE;
    }
}

/* End of file policy_action.inc */
/* Location: ../include/classes/policy_action.inc */
