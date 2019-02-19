<?php
/**
* Policy.inc
*
* File Policy.inc is used to:
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
* - Policy()
* - get_id()
* - get_priority()
* - get_active()
* - get_order()
* - get_group()
* - get_descr()
* - get_foundrows()
* - get_hosts()
* - get_nets()
* - get_host_groups()
* - get_net_groups()
* - get_sensors()
* - get_ports()
* - get_targets()
* - get_role()
* - get_policy_groups()
* - get_signatures()
* - get_plugingroups()
* - get_time()
* - get_list()
* - activate()
* - get_next_order()
* - swap_orders()
* - change_group()
* - reorder()
* - get_group_from_order()
* - get_order_from_group()
* - get_near_order()
* - insert()
* - update()
* - delete()
*/

class Policy 
{
    public $id;

    
    public $ctx;

    
    public $priority;

    
    public $active;

    
    public $order;

    
    public $group;

    
    public $descr;

    
    public $permissions;

    
    public $foundrows;
    
    
    public function __construct($id, $ctx, $priority, $active, $descr, $order, $group, $permissions, $foundrows = 0) 
    {
        $this->id          = $id;
        $this->ctx         = $ctx;
        $this->priority    = $priority;
        $this->active      = $active;
        $this->order       = $order;
        $this->group       = $group;
        $this->descr       = html_entity_decode($descr);
        $this->permissions = $permissions;
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
    
    
    public function get_priority() 
    {
        return $this->priority;
    }
    
    
    public function get_active() 
    {
        return $this->active;
    }
    
    
    public function get_order() 
    {
        return $this->order;
    }
    
    
    public function get_group() 
    {
        return $this->group;
    }
    
    
    public function get_descr() 
    {
        return $this->descr;
    }
    
    
    public function get_foundrows() 
    {
        return $this->foundrows;
    }
    
    
    public function get_hosts($conn, $direction) 
    {
        return Policy_host_reference::get_list($conn, $this->id, $direction);
    }
    
    
    public function get_nets($conn, $direction) 
    {
        return Policy_net_reference::get_list($conn, $this->id, $direction);
    }
    
    
    public function get_host_groups($conn, $direction) 
    {
        return Policy_host_group_reference::get_list($conn, $this->id, $direction);
    }
    
    
    public function get_net_groups($conn, $direction) 
    {
        return Policy_net_group_reference::get_list($conn, $this->id, $direction);
    }
    
    
    public function get_sensors($conn) 
    {
        return Policy_sensor_reference::get_list($conn, $this->id);
    }
    
    
    public function get_ports($conn, $direction) 
    {
        return Policy_port_reference::get_list($conn, $this->id, $direction);
    }
    
    
    public function get_taxonomy_conditions($conn) 
    {
        return Policy_taxonomy_reference::get_list($conn, " AND policy_id=UNHEX('". $this->id ."')");
    }
    
    
    public function get_reputation_conditions($conn) 
    {
        return Policy_reputation_reference::get_list($conn, " AND policy_id=UNHEX('". $this->id ."')");
    }
    
    
    public function get_event_conditions($conn) 
    {
        return Policy_risk_reference::get_list($conn, " AND policy_id=UNHEX('". $this->id ."')");
    }
    
    
    public function get_idm_conditions($conn) 
    {
        return Policy_idm_reference::get_list($conn, " AND policy_id=UNHEX('". $this->id ."')");
    }
    
    
    public function get_forward_conditions($conn) 
    {
        return Policy_forward_reference::get_list($conn, " AND policy_id=UNHEX('". $this->id ."')");
    }
    
    
    public function get_targets($conn) 
    {
        return Policy_target_reference::get_list($conn, $this->id);
    }
    
    
    public function get_role($conn) 
    {
        return Policy_role_reference::get_list($conn, $this->id);
    }
    
    
    public static function get_policy_groups($conn, $ctx, $engine = FALSE)
    {
        return Policy_group::get_list($conn, $ctx, " ORDER BY policy_group.order", $engine);
    }
    
    
    public function get_time($conn) 
    {
        return Policy_time::get_list($conn, $this->id);
    }
    
    
    /*
    * XXXX cox: Seems that the functionality of the $get_all_groups param is
    * broken. See Plugin_group::get_list() for a similar feature
    */
    public function get_plugingroups($conn, $policy_id, $get_all_groups = FALSE) 
    {
        $list = array();
        
        if (!$get_all_groups) 
        {
            $where  = " AND policy_plugin_group_reference.policy_id = UNHEX(?)";
            $params = array(
                $policy_id
            );
        }
        else 
        {
            $where  = "";
            $params = array();
        }

        $sql = "SELECT
                    HEX(plugin_group.group_id) AS group_id,
                    plugin_group.name,
                    HEX(policy_plugin_group_reference.policy_id) AS policy_id
                FROM
                    policy_plugin_group_reference
                LEFT JOIN plugin_group ON
                    policy_plugin_group_reference.plugin_group_id = plugin_group.group_id
                WHERE 1=1 $where 
                ORDER BY
                    plugin_group.name";  
        
        $rs = $conn->Execute($sql, $params);                        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF) 
        {
            $list[] = array(
                'id' => ($rs->fields['group_id']!="") ? $rs->fields['group_id'] : '00000000000000000000000000000000',
                'name' => ($rs->fields['name']!="") ? $rs->fields['name'] : _("ANY"),
                'policy_id' => $rs->fields['policy_id']
            );
            
            $rs->MoveNext();
        }
        
        return $list;        
    }
    
    
    public function exist_sensors($conn)
    {
        $list_sensor_reference = $this->get_sensors($conn);
        
        $list = array();
        
        // check sensor exist
        foreach($list_sensor_reference as $sensor)
        {
            if(count(Policy_sensor_reference::exist_sensors($conn, $sensor->get_sensor_id())) == 0)
            {
                if($sensor->get_sensor_id() == 'ANY'||$sensor->get_sensor_id() == 'any')
                {
                    $exist = 'true';
                }
                else
                {
                    $exist = 'false';
                }
            }
            else
            {
                $exist = 'true';
            }
            
            $list[$sensor->get_sensor_id()]=$exist;
        }

        return $list;
    }
   
   
    public static function get_list($conn, $args = "")
    {
        $list = array();
        $ctxs = Session::get_ctx_where();
        
        if ($ctxs != "")
        {
            $ctxs = " AND ctx in ($ctxs)";
        }
        
        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;
        
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *,HEX(id) as id, HEX(ctx) as ctx,HEX(policy.group) as `group`" : "HEX(id) as id, HEX(ctx) as ctx, priority, active, HEX(policy.group) as `group`, policy.order, descr, HEX(permissions) as permissions";
        $query  = ossim_query("SELECT $select FROM policy where 1=1 $ctxs $args");
        
        //var_dump($query);
        $rs = $conn->Execute($query);
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $foundrows = 0;
            
            $rf = $conn->Execute("SELECT FOUND_ROWS() as total");
            if ($iffoundrows) 
            {
                if (!$rf)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                else 
                {
                    $foundrows = $rf->fields["total"];
                }
            }
            
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields["id"], $rs->fields["ctx"], $rs->fields["priority"], $rs->fields["active"], $rs->fields["descr"], $rs->fields["order"], $rs->fields["group"], $rs->fields["permissions"], $foundrows);
                
                $rs->MoveNext();
            }
        }
        
        return $list;        
    }
    

    public static function activate($conn, $id, $action = 'null')
    {
        // Toggle active field
        if($action=='null')
        {
            $params = array($id);
            $sql    = 'UPDATE policy SET active= not active WHERE policy.id = UNHEX(?)';
        }
        else
        {
            $params = array((int)$action, $id);
            $sql    = 'UPDATE policy SET active= ? WHERE policy.id = UNHEX(?)';
        }

        if (!$conn->Execute($sql, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $get_policy_info = "SELECT  active, descr   FROM  policy  WHERE policy.id = UNHEX(?)";
        $policy__result = $conn->Execute($get_policy_info, $params);

        ($policy__result->fields['active'] ) ? $policy_state = 'enabled' :  $policy_state = 'disabled';
        $policy_name = $policy__result->fields['descr'];
        self::log_policy_activity(106, $policy_name, $policy_state);

    }
    
    
    public function get_new_order($conn, $ctx, $group) 
    {
        $where = " AND ctx=UNHEX('$ctx') AND policy.group=UNHEX('$group')";
        $sql   = "SELECT max(policy.order) as max FROM policy WHERE 1=1 $where";
        
        $rs = $conn->Execute($sql); 

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if (is_numeric($rs->fields["max"]))
        {
            return $rs->fields["max"] + 1;
        } 
        else 
        {
            return 0;
        }        
    }
    
    
    public static function get_pg_order($conn, $ctx, $order, $direction = 'up')
    {
        if($direction == 'up')
        {
            $where = " AND ctx=UNHEX('$ctx') AND policy_group.order<$order ";
            $sql = "SELECT max(policy_group.order) as max FROM policy_group WHERE 1=1 $where";
        }
        else
        {
            $where = " AND ctx=UNHEX('$ctx') AND policy_group.order>$order ";
            $sql = "SELECT min(policy_group.order) as max FROM policy_group WHERE 1=1 $where";
        }
        
        $rs = $conn->Execute($sql); 

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }  
        else 
        {
            if (is_numeric($rs->fields["max"]))
            {
                return $rs->fields["max"];
            } 
            else 
            {
                return -1;
            }
        }
    }
    
    
    public static function get_next_order($conn, $ctx, $group = 0)
    {       
        $group = Policy_group::get_list($conn, $ctx, " AND id=UNHEX('$group')");
        $group = $group[0];
        
        if(!is_object($group))
        {
            return;
        }
        
        $neworder = self::get_new_order($conn, $ctx, $group->get_group_id());
        $min_ord  = Policy_group::get_min_order_by_ctx($conn, $ctx);
                
        if($group->get_order() == $min_ord)
        {           
            return (($neworder == 0) ? 1 : $neworder);
            
        } 
        else 
        {           
            if($neworder == 0)
            {
                $pg_order   = self::get_pg_order($conn, $ctx, $group->get_order());
                $prev_group = Policy_group::get_list($conn, $ctx, " AND policy_group.order=$pg_order");
                
                if(!is_object($prev_group[0]))
                {
                    return;
                }
                
                $neworder = self::get_next_order($conn, $ctx, $prev_group[0]->get_group_id());
                
                return $neworder;
            } 
            else 
            {
                return $neworder;
            }
        }
    }
    
    
    public static function swap_simple_orders($conn, $src, $dst)
    {   
        if(!is_object($src) || !is_object($dst))
        {
            return FALSE;
        }
        
        $sql    = "UPDATE policy SET policy.order=? WHERE id=UNHEX(?)";
        $params = array($dst->get_order(), $src->get_id());
        $conn->Execute($sql, $params);
        
        $sql    = "UPDATE policy SET policy.order=? WHERE id=UNHEX(?)";
        $params = array($src->get_order(), $dst->get_id());
        $conn->Execute($sql, $params);      
    }
    
    
    public static function swap_orders($conn, $ord_src, $ord_dst, $group, $ctx, $change = "src")
    {
        $upt = array();
        
        if ($ord_src == $ord_dst) 
        {
            return;
        }
        
        if ($ord_src > $ord_dst) 
        {
            $tmp = $ord_src;
            $ord_src = $ord_dst;
            $ord_dst = $tmp;
        }
        
        $query = ossim_query("SELECT HEX(id) as id, policy.order, HEX(policy.group) as `group` FROM policy WHERE ctx=UNHEX('$ctx') AND policy.order>=$ord_src AND policy.order<=$ord_dst ORDER BY policy.order");
        
        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $id      = $rs->fields["id"];
                $ord     = $rs->fields["order"];
                $new_ord = ($ord == $ord_dst) ? $ord_src : $ord + 1;
                
                if ($change == "src" && $ord == $ord_src && $group >= 0) 
                {
                    $new_ord.= ", policy.group=UNHEX('$group')";
                }
                
                if ($change == "dst" && $ord == $ord_dst && $group >= 0) 
                {
                    $new_ord.= ", policy.group=UNHEX('$group')";
                }
                
                $upt[] = "UPDATE policy SET policy.order=$new_ord WHERE id=UNHEX('$id')";
                
                $rs->MoveNext();
            }
        }
        
        foreach($upt as $sql)
        {
            $conn->Execute($sql); //launch updating
        }        
    }
    
    
    public static function change_group($conn, $id, $group)
    {
        $query  = "UPDATE policy SET policy.group=UNHEX(?) WHERE policy.id=UNHEX(?)";
        $params = array($group, $id);
        
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
    
    
    public function reorder($conn, $ord, $ctx, $type = "insert") 
    {
        $params = array($ctx, $ord);
        
        if($type == "insert")
        {
            $conn->Execute("UPDATE policy SET policy.order=policy.order+1 WHERE ctx=UNHEX(?) AND policy.order>=?", $params);
            
        } 
        elseif($type == "delete")
        {
            $conn->Execute("UPDATE policy SET policy.order=policy.order-1 WHERE ctx=UNHEX(?) AND policy.order>?", $params);         
        }
    }
    
    
    public static function reassing_orders($conn, $ctx)
    {       
        //Adding new order to the policy groups
        $query  = "SELECT distinct HEX(pg.id) as id
                    FROM policy_group pg 
                    WHERE pg.ctx=UNHEX(?)
                    ORDER BY  pg.order";     
                
                
        $params = array($ctx);
        
        if (!$rs = $conn->Execute($query, $params)) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $order = 0;
            
            while (!$rs->EOF) 
            {
                $id = $rs->fields["id"];
                
                $params = array($order, $id);
                $conn->Execute("UPDATE policy_group SET policy_group.order=? WHERE id=UNHEX(?)", $params);
                
                $order++;
                
                $rs->MoveNext();
            }
        }

        //Adding new order to the policies
        $query  = "SELECT distinct HEX(p.id) as id 
                    FROM policy p, policy_group pg 
                    WHERE p.ctx=UNHEX(?) AND p.group=pg.id 
                    ORDER BY  pg.order, p.order";     
                
                
        $params = array($ctx);
        
        if (!$rs = $conn->Execute($query, $params)) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $order = 1;
            while (!$rs->EOF) 
            {
                $id = $rs->fields["id"];
                
                $params = array($order, $id);
                $conn->Execute("UPDATE policy SET policy.order=? WHERE id=UNHEX(?)", $params);
                
                $order++;
                $rs->MoveNext();
            }
        }
        
        $user   = Session::get_session_user();
        $sql    = "DELETE FROM `user_config` WHERE `category` = 'policy' AND `login`=?";
        $params = array($user);
        
        if ($conn->Execute($sql, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
    
    
    public static function get_group_from_id($conn, $id)
    {
        $query = ossim_query("SELECT HEX(policy.group) as `group` FROM policy WHERE id=UNHEX(?)");
        
        $rs = $conn->Execute($query, array($id));

        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            if (!$rs->EOF)
            {
                return $rs->fields["group"];
            } 
        }

        return 0;
    }
    
    
    public static function get_all_ctx($conn)
    {
        $list = array();
        $ctxs = Session::get_ctx_where();
        
        if ($ctxs != '') 
        {
            $ctxs = " AND ctx in ($ctxs)";
        }
        
        $query = "SELECT distinct(HEX(ctx)) as ctx FROM policy where 1=1 $ctxs";
        
        $rs = $conn->Execute($query);
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $list[] = $rs->fields["ctx"];
                
                $rs->MoveNext();
            }
        }

        return $list;
    }

    
    public static function insert($conn, $ctx, $priority, $active, $group, $order, $tzone, $b_month, $b_month_day, $b_week_day, $b_hour, $b_minute, $e_month, $e_month_day, $e_week_day, $e_hour, $e_minute, $descr, $host_source_list, $host_group_source_list, $host_dest_list, $host_group_dest_list, $net_source_list, $net_group_source_list, $net_dest_list, $net_group_dest_list, $portsrc, $portdst, $plug_groups, $sensor_list, $target_list, $taxonomy_list, $reputation_list, $event_list, $idm_list, $correlate, $cross_correlate, $store, $reputation, $qualify, $resend_alarm, $resend_event, $forward_list, $sign, $sem, $sim, $id = null)
    {
       
        $descr              = Util::htmlentities(mb_convert_encoding($descr,"UTF-8","ISO-8859-1"));
        $priority           = intval($priority);
        $correlate          = intval($correlate);
        $cross_correlate    = intval($cross_correlate);
        $store              = intval($store);
        $qualify            = intval($qualify);
        $resend_alarm       = intval($resend_alarm);
        $resend_event       = intval($resend_event);
        
        if (!$id) 
        {
            $id = Util::uuid();
        }
        
        // re-order before insert
        self::reorder($conn, $order, $ctx, "insert");
        
        $sql    = "INSERT INTO policy (`id`, `ctx`, `priority`, `active`, `group`, `order`, `descr`, `permissions`) VALUES (UNHEX(?), UNHEX(?), ?, ?, UNHEX(?), ?, ?, 0x0)";
        
        $params = array(
            $id,
            $ctx,
            $priority,
            $active,
            $group,
            $order,
            $descr
        );
        
        if (!$conn->Execute($sql, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        Policy_host_reference::insert($conn, $host_source_list, 'source', $id);
        Policy_host_reference::insert($conn, $host_dest_list, 'dest', $id);
        
        Policy_host_group_reference::insert($conn, $host_group_source_list, 'source', $id);        
        Policy_host_group_reference::insert($conn, $host_group_dest_list, 'dest', $id);
        
        Policy_net_reference::insert($conn, $net_source_list, 'source', $id);
        Policy_net_reference::insert($conn, $net_dest_list, 'dest', $id);
        
        Policy_net_group_reference::insert($conn, $net_group_source_list, 'source', $id);        
        Policy_net_group_reference::insert($conn, $net_group_dest_list, 'dest', $id);
        
        
        Policy_port_reference::insert($conn, $portsrc, 'source', $id);
        Policy_port_reference::insert($conn, $portdst, 'dest', $id);        
        
        
        $conn->Execute("DELETE FROM policy_plugin_group_reference WHERE policy_id= UNHEX(?)", array($id));
        $sql = "INSERT INTO policy_plugin_group_reference(policy_id, plugin_group_id) VALUES (UNHEX(?), UNHEX(?))";
        
        foreach($plug_groups as $group_id) 
        {
            $conn->Execute($sql, array($id, $group_id));
        }
        
        Policy_sensor_reference::insert($conn, $sensor_list, $id);
        
        Policy_role_reference::insert($conn, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $reputation, $id);
        
        Policy_target_reference::insert($conn, $target_list, $id);

        Policy_time::insert($conn, $id, $tzone, $b_minute, $e_minute, $b_hour, $e_hour, $b_week_day, $e_week_day, $b_month_day, $e_month_day, $b_month, $e_month );

        
        if(is_array($taxonomy_list) && !empty($taxonomy_list))
        {
            foreach($taxonomy_list as $tax)
            {
                Policy_taxonomy_reference::insert($conn, $id, $tax[0], $tax[1], $tax[2]);                   
            }
        }       
        
        if(is_array($reputation_list) && !empty($reputation_list))
        {
            foreach($reputation_list as $rep)
            {
                Policy_reputation_reference::insert($conn, $id, $rep[3], $rep[1], $rep[2], $rep[0]);            
            }
        }

        if(is_array($event_list) && !empty($event_list))
        {
            foreach($event_list as $event)
            {
                Policy_risk_reference::insert($conn, $id, $event[0], $event[1]);            
            }
        }       
        
        if(is_array($idm_list) && !empty($idm_list))
        {
            foreach($idm_list as $item)
            {
                Policy_idm_reference::insert($conn, $id, $item[4], $item[0], $item[1], $item[2], $item[3]);         
            }
        }
        
        if(is_array($forward_list) && !empty($forward_list))
        {
            foreach($forward_list as $serverid => $prio)
            {
                Policy_forward_reference::insert($conn, $id, $target_list[0], $serverid, $prio);            
            }
        }

        // don't log if it is an update
        $bt = debug_backtrace();
        
        if (!isset($bt[1]) || (isset($bt[1])) && ($bt[1]["function"] != "update")) 
        {
            self::log_policy_activity(46, $descr, 'created');
        }
        
        Web_indicator::set_on("Reload_policies");
        // ReloadPolicy key deprecated, now using Reload_policies always
        //Web_indicator::set_on("ReloadPolicy");        
                
        return $id;        
    }
    
    
    public static function update($conn, $id, $ctx, $priority, $active, $group, $order, $tzone, $b_month, $b_month_day, $b_week_day, $b_hour, $b_minute, $e_month, $e_month_day, $e_week_day, $e_hour, $e_minute, $descr, $host_source_list, $host_group_source_list, $host_dest_list, $host_group_dest_list, $net_source_list, $net_group_source_list, $net_dest_list, $net_group_dest_list, $portsrc, $portdst, $plug_groups, $sensor_list, $target_list, $taxonomy_list, $reputation_list, $event_list, $idm_list, $correlate, $cross_correlate, $store, $reputation, $qualify, $resend_alarm, $resend_event, $forward_list, $sign, $sem, $sim)
    
    {
        $descr              = Util::htmlentities(mb_convert_encoding($descr,"UTF-8","ISO-8859-1"));
        $correlate          = intval($correlate);
        $cross_correlate    = intval($cross_correlate);
        $store              = intval($store);
        $qualify            = intval($qualify);
        $resend_alarm       = intval($resend_alarm);
        $resend_event       = intval($resend_event);
       
        self::delete($conn, $id);
       
        self::insert($conn, $ctx, $priority, $active, $group, $order, $tzone, $b_month, $b_month_day, $b_week_day, $b_hour, $b_minute, $e_month, $e_month_day, $e_week_day, $e_hour, $e_minute, $descr, $host_source_list, $host_group_source_list, $host_dest_list, $host_group_dest_list, $net_source_list, $net_group_source_list, $net_dest_list, $net_group_dest_list, $portsrc, $portdst, $plug_groups, $sensor_list, $target_list, $taxonomy_list, $reputation_list, $event_list, $idm_list, $correlate, $cross_correlate, $store, $reputation, $qualify, $resend_alarm, $resend_event, $forward_list, $sign, $sem, $sim, $id);

        self::log_policy_activity(48, $descr, 'modified');
        
        Web_indicator::set_on("Reload_policies");
        // ReloadPolicy key deprecated, now using Reload_policies always
        //Web_indicator::set_on("ReloadPolicy");
    }
    
    
    public static function delete($conn, $id)
    {
        $policy = self::get_list($conn, " AND id=UNHEX('$id')");
        
        if (count($policy) != 1) 
        {
            return;
        }
            
        $current_order = $policy[0]->get_order();
        $descr         = $policy[0]->get_descr();
        
        Policy_host_reference::delete($conn, $id);
        Policy_net_reference::delete($conn, $id);
        Policy_host_group_reference::delete($conn, $id);
        Policy_net_group_reference::delete($conn, $id);
        Policy_port_reference::delete($conn, $id);
        Policy_sensor_reference::delete($conn, $id);
        Policy_target_reference::delete($conn, $id);
        Policy_role_reference::delete($conn, $id);
        Policy_time::delete($conn, $id);
        Policy_risk_reference::delete_by_policy($conn, $id);
        Policy_taxonomy_reference::delete_by_policy($conn, $id);    
        Policy_reputation_reference::delete_by_policy($conn, $id);  
        Policy_idm_reference::delete_by_policy($conn, $id);         
        Policy_forward_reference::delete_by_policy($conn, $id); 

        
        Policy_action::delete($conn, $id);
        
        $sql    = "DELETE FROM policy_plugin_group_reference WHERE policy_id = UNHEX(?)";
        $params = array($id);
        
        $conn->Execute($sql, $params);
        
        $sql = "DELETE FROM policy WHERE id = UNHEX(?)";
        $conn->Execute($sql, $params);
        
        // re-order after delete
        self::reorder($conn, $current_order, $policy[0]->get_ctx(), "delete");
        // don't log if it is an update'
        
        $bt = debug_backtrace();
        if (!isset($bt[1]) || (isset($bt[1])) && ($bt[1]["function"] != "update")) 
        {
            self::log_policy_activity(47, $descr, 'deleted');
        }
        
        Web_indicator::set_on("Reload_policies");
        // ReloadPolicy key deprecated, now using Reload_policies always
        //Web_indicator::set_on("ReloadPolicy");
    }
    
    
    public static function is_visible($conn, $id)
    {
        $ctxs = Session::get_ctx_where();
        
        if ($ctxs != "") 
        {
            $ctxs = " AND ctx in ($ctxs)";
        }

        $query  = "SELECT count(*) as total FROM policy where 1=1 $ctxs and id=UNHEX(?)";
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;            
        } 
        else 
        {           
            if($rs->fields["total"] > 0)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
    }
    
        
    public function get_policy_conditions($conn, $policy)
    {
    
        if(!is_object($policy))
        {
            return md5('');
        }
        
        $conds   = array();
        
        //Disabled properties
        $conds[] = $policy->get_active();

        //SOURCES
        $val     = array();
        
        if ($host_list = $policy->get_hosts($conn, 'source'))
        {
            foreach($host_list as $host) 
            {
                $val[] = $host->get_host_id();
            }
        }
                
        if ($net_list = $policy->get_nets($conn, 'source'))
        { 
            foreach($net_list as $net) 
            {
               $val[] = $net->get_net_id();
            }
        }
        
        if ($hg_list = $policy->get_host_groups($conn, 'source'))
        { 
            foreach($hg_list as $host_group) 
            {
                $val[] = $host_group->get_host_group_id();
            }
        }
        
        if ($ng_list = $policy->get_net_groups($conn, 'source')) 
        {
            foreach($ng_list as $net_group)
            {
                $val[] = $net_group->get_net_group_id();
            }
        }
        
        $conds[] = $val;
        
        //DESTINY
        $val = array();
        
        if ($host_list = $policy->get_hosts($conn, 'dest'))
        {
            foreach($host_list as $host) 
            {
                $val[] = $host->get_host_id();
            }
        }
        
        if ($net_list = $policy->get_nets($conn, 'dest'))
        { 
            foreach($net_list as $net) 
            {
               $val[] = $net->get_net_id();
            }
        }
        
        if ($hg_list = $policy->get_host_groups($conn, 'dest'))
        { 
            foreach($hg_list as $host_group) 
            {
                $val[] = $host_group->get_host_group_id();
            }
        }
        
        if ($ng_list = $policy->get_net_groups($conn, 'dest')) 
        {
            foreach($ng_list as $net_group)
            {
                $val[] = $net_group->get_net_group_id();
            }
        }
        
        $conds[] = $val;
        
        //PORTS
        
        //source
        $val = array();
        
        if ($port_list = $policy->get_ports($conn, 'source')) 
        {
            foreach($port_list as $port_group) 
            {
                $val[] = $port_group->get_port_id();
            }
        }   
            
        $conds[] = $val;
        
        //destiny
        $val = array();
        
        if ($port_list = $policy->get_ports($conn, 'dest')) 
        {
            foreach($port_list as $port_group) 
            {
                $val[] = $port_group->get_port_id();
            }
        }
        
        $conds[] = $val;
        
                
        //PLUGIN GROUPS
        $val = array();
        
        if ($plugin_list = $policy->get_plugingroups($conn, $policy->get_id())) 
        {
            foreach($plugin_list as $pgroup) 
            {
                $val[] = $pgroup['id'];
            }
        }
            
        $conds[] = $val;
        
        
        //TAXONOMY
        $val = array();
        
        if ($taxonomy_list = $policy->get_taxonomy_conditions($conn))
        {
            foreach($taxonomy_list as $tax) 
            {               
                $val[] = $tax->get_product_type_id() . "@" . $tax->get_category_id() . "@" . $tax->get_subcategory_id();
            }
        }
        
        $conds[] = $val;

        //SENSOR    
        $val = array();
        
        if ($sensor_list = $policy->get_sensors($conn)) 
        {
            $sensor_exist = $policy->exist_sensors($conn);
            foreach($sensor_list as $sensor) 
            {
                if($sensor_exist[$sensor->get_sensor_id()]!='false')
                {
                    $val[] = $sensor->get_sensor_id();
                }
            }
        }
        
        $conds[] = $val;
                
        //Time Filters
        
        if ($policy_time = $policy->get_time($conn))
        {
            $time_begin[0] = $policy_time->get_month_start();
            $time_begin[1] = $policy_time->get_month_day_start();
            $time_begin[2] = $policy_time->get_week_day_start();
            $time_begin[3] = $policy_time->get_hour_start();
            $time_begin[4] = $policy_time->get_minute_start();
            
            $time_end[0]   = $policy_time->get_month_end();
            $time_end[1]   = $policy_time->get_month_day_end();
            $time_end[2]   = $policy_time->get_week_day_end();
            $time_end[3]   = $policy_time->get_hour_end();
            $time_end[4]   = $policy_time->get_minute_end();    
            
            $conds[] = array($time_begin, $time_end);
        }
        
        //TARGETS
        $val = array();
        
        if ($target_list = $policy->get_targets($conn)) 
        {
            foreach($target_list as $target) 
            {
                $val[] = $target->get_target_id();
            }
        }
        
        $conds[] = $val;
    
        //REPUTATION
        $val = array();
        
        if ($reputation_list = $policy->get_reputation_conditions($conn))
        {
            foreach($reputation_list as $rep) 
            {               
                $val[] = $rep->get_activity_id() . "@" . $rep->get_priority() . "@" . $rep->get_reliability() . "@" . $rep->get_direction();

            }
        }
        
        $conds[] = $val;
        
        //Event Risk
        $val = array();
        
        if ($event_prio_list = $policy->get_event_conditions($conn))
        {
            foreach($event_prio_list as $event) 
            {               
                $val[] = $event->get_priority() . "@" . $event->get_reliability();              
            }
        }
        
        $conds[] = $val;
        
        return md5((serialize($conds)));
    }

    
    public static function get_conditions_hash($conn, $ctx)
    {
        $conds    = array();
        $policies = array();
        
        $policy_list = self::get_list($conn, "AND ctx=UNHEX('$ctx')");
        
        foreach($policy_list as $policy) 
        {
            $md5 = self::get_policy_conditions($conn, $policy);
            
            $conds[$md5][]               = html_entity_decode($policy->get_descr());
            $policies[$policy->get_id()] = $md5;
        }
        
        return array($conds, $policies);
    } 
    
    
    public static function is_allowed_sign_line($conn, $server = '')
    {
        if (empty($server))
        {
            $server = Server::get_default_server($conn, FALSE);
        }
        
        $sign_line  = FALSE;
        $role_list  = Role::get_list($conn, $server);
        $role       = $role_list[0];
	
    	if (is_object($role))
    	{
        	$sign_line = ($role->get_sign() == 1) ? TRUE : FALSE;
    	}
    	
    	return $sign_line;
    }  

    public function get_srcdst_items($list, $getid, $getname, $preffix, $decorator, $decorator_vars, $conn) {
                $source = array();
                if (!$list) return array();
                foreach($list as $item) {
                        $id = $getid($item);
                        if (check_any($id))
                                return array($id => $decorator(self::getANY(),$decorator_vars));
                        if ($id == self::getHOMENETKEY()) {
                                $source[$id] = $decorator(self::getHOMENET(),$decorator_vars);
                                continue;
                        }
                        if ($id == self::getNOTHOMENETKEY()) {
                                $source[$id] = $decorator(self::getNOTHOMENET(),$decorator_vars);
                                continue;
                        }
                        $source[$preffix.$id] = $decorator(call_user_func_array("$getname::get_name_by_id",array($conn, $id)),$decorator_vars);
                }
                return $source;
    }

    public function get_srcdst_cell($dir,$conn,$decorator,$decorator_vars) {
           return array_merge($this->get_srcdst_items($this->get_hosts($conn, $dir),function($item) {
                        return $item->get_host_id();
                },"Asset_host","host_", $decorator, $decorator_vars[0], $conn)
                ,$this->get_srcdst_items($this->get_nets($conn, $dir),function($item) {
                        return $item->get_net_id();
                }, "Asset_net","net_", $decorator, $decorator_vars[1], $conn)
                ,$this->get_srcdst_items($this->get_host_groups($conn, $dir),function($item) {
                        return $item->get_host_group_id();
                }, "Asset_group","hostgroup_", $decorator, $decorator_vars[2], $conn)
                ,$this->get_srcdst_items($this->get_net_groups($conn, $dir),function($item) {
                        return $item->get_net_group_id();
                }, "Net_group","netgroup_", $decorator, $decorator_vars[3], $conn));
    }

    public static function getHOMENET() {
        return "HOME_NET";
    }

    public static function getNOTHOMENET() {
        return "!HOME_NET";
    }

    public static function getANY() {
        return "ANY";
    }

    public static function getHOMENETKEY() {
        return "01000000000000000000000000000000";
    }

    public static function getNOTHOMENETKEY() {
        return "02000000000000000000000000000000";
    }

    public  function  log_policy_activity($code,$policy_name, $policy_state) {

        $user_name    = Session::get_session_user();
        Log_action::log($code,[$policy_name,$policy_state,$user_name]);

    }

}

/* End of file policy.inc */
/* Location: ../include/classes/policy.inc */
