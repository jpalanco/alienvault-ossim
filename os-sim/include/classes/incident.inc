<?php
/**
* Incident.inc
*
* File Incident.inc is used to:
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
* - Incident()
* - get_id()
* - get_title()
* - get_ref()
* - get_type()
* - get_type_keywords()
* - set_type_keywords()
* - set_type_descr()
* - get_type_descr()
* - get_submitter()
* - get_priority()
* - get_status()
* - get_in_charge()
* - get_tags()
* - get_event_start()
* - get_event_end()
* - get_in_charge_name()
* - get_last_modification()
* - get_date()
* - get_life_time()
* - get_ticket()
* - get_priority_bgcolor()
* - get_priority_fgcolor()
* - get_priority_string()
* - get_priority_in_html()
* - get_validations()
* - colorize_status()
* - get_list()
* - get_list_type_descr()
* - get_keywords_from_type()
* - search()
* - _build_sql()
* - _sort_by_life_time()
* - get_tickets()
* - get_alarms()
* - get_events()
* - get_metrics()
* - get_anomalies()
* - get_vulnerabilities()
* - get_users_list()
* - get_subscribed_users()
* - get_last_update()
* - user_incident_perms()
* - user_ticket_perms()
* - print_td_incident_type()
* - __insert()
* - insert_alarm()
* - insert_event()
* - insert_anomaly()
* - insert_vulnerability()
* - insert_metric()
* - insert_subscription()
* - __update()
* - update_alarm()
* - update_event()
* - update_metric()
* - update_anomaly()
* - update_vulnerability()
* - delete()
* - delete_subscriptions()
* - incident_search()
* - incidents_by_type()
* - incidents_by_type_descr()
* - tags_incidents()
* - incidents_by_tag()
* - incidents_by_status()
* - incidents_by_user()
* - incidents_by_class()
* - incidents_by_resolution_time()
* - incidents_closed_by_month()
* - incidents_by_priority()
* - chk_status()
* - insert_incident_tag()
* - delete_incident_tags()
* - incidents_by_type_per_month()
* - get_all_types()
* - get_id_by_tag()
*/

define('HIGH_PRIORITY', 7);
define('MEDIUM_PRIORITY', 4);
define('LOW_PRIORITY', 1);


class Incident
{
    public $id;


    public $uuid;


    public $ctx;


    public $title;


    public $date;


    public $ref;


    public $type;


    public $type_descr;


    public $type_keywords;


    public $submitter;


    public $priority;


    public $in_charge;


    public $status;


    public $last_update;


    public $event_start;


    public $event_end;


    public $src_ips;


    /*
    * array of TAG IDs related to this incident
    */


    public $tags;


    public $_life_time_diff;


    public function __construct($id, $uuid, $ctx, $title, $date, $ref, $type, $submitter, $priority, $in_charge, $status, $last_update,
        $tags, $life_time_diff, $event_start, $event_end, $src_ips = '')
    {
        $this->id              = intval($id);
        $this->uuid            = $uuid;
        $this->ctx             = $ctx;
        $this->title           = $title;
        $this->date            = $date;
        $this->ref             = $ref;
        $this->type            = $type;
        $this->submitter       = $submitter;
        $this->priority        = $priority;
        $this->in_charge       = $in_charge;
        $this->status          = $status;
        $this->last_update     = $last_update;
        $this->tags            = $tags;
        $this->_life_time_diff = $life_time_diff; /* Private property */
        $this->event_start     = $event_start;
        $this->event_end       = $event_end;
        $this->src_ips         = $src_ips;
    }


    public function get_id()
    {
        return intval($this->id);
    }


    public function get_uuid()
    {
        return intval($this->uuid);
    }


    public function get_ctx()
    {
        return intval($this->ctx);
    }


    public function get_title()
    {
        // Variable replacement in the ticket title (only event type, comming from a directive)
        // Similar to TranslateSignature in base_signature.inc.php
        if ($this->ref == 'Event' && preg_match('/SRC_IP|DST_IP/', $this->title))
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            $events = $this->get_events($conn);

            $src_ip = (is_object($events[0]) && $events[0]->get_src_ips() != '') ? $events[0]->get_src_ips() : 'SRC_IP';
            $dst_ip = (is_object($events[0]) && $events[0]->get_dst_ips() != '') ? $events[0]->get_dst_ips() : 'DST_IP';

            $this->title = preg_replace('/SRC_IP/', $src_ip, $this->title);
            $this->title = preg_replace('/DST_IP/', $dst_ip, $this->title);

            $db->close();
        }

        return Util::htmlentities($this->title);
    }


    public function get_ref()
    {
        return $this->ref;
    }


    public function get_type()
    {
        return $this->type;
    }


    public function get_type_keywords()
    {
        return $this->type_keywords;
    }


    public function set_type_keywords($keywds)
    {
        $this->type_keywords = $keywds;
    }


    public function set_type_descr($descr)
    {
        $this->type_descr = $descr;
    }


    public function get_type_descr()
    {
        return $this->type_descr;
    }


    public function get_submitter()
    {
        return $this->submitter;
    }


    public function get_priority()
    {
        return $this->priority;
    }


    public function get_status()
    {
        return $this->status;
    }


    public function get_in_charge()
    {
        return $this->in_charge;
    }


    public function get_tags()
    {
        return $this->tags;
    }


    public function get_event_start()
    {
        return $this->event_start;
    }


    public function get_event_end()
    {
        return $this->event_end;
    }


    public function get_src_ips()
    {
        return $this->src_ips;
    }


    public function get_in_charge_name($conn)
    {
        $in_charge_login = self::get_in_charge();

        $params = array($in_charge_login);
        $query  = "SELECT name FROM users WHERE login = ?";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->EOF)
        {
            $in_charge_name = $in_charge_login;
        }
        else
        {
            $in_charge_name = $rs->fields["name"];
        }

        return $in_charge_name;
    }


    public function get_last_modification($conn)
    {
        $tz = Util::get_timezone();

        list ($y,$m,$d,$h,$u,$s,$time) = Util::get_utc_from_date($conn, $this->last_update, $tz);
        $last_update = "$y-$m-$d $h:$u:$s";

        return Util::date_diff(gmdate("Y-m-d H:i:s"), $last_update);
    }


    public function get_last_update()
    {
        return  $this->last_update;
    }



    public function get_date()
    {
        return Util::timestamp2date($this->date);
    }


    /*
     * Returns the life time of an incident
     *
     * If the status is:
     *
     *  - Closed: Life time is the difference between incident creation date
     *            and the last time it was modified
     *  - Open: Life time is the difference between incident date and now
     */
    public function get_life_time($format = 'yMdhm')
    {
        $status      = $this->status;
        $start_date  = $this->date;
        $last_update = $this->last_update;

        if ($status == 'Closed')
        {
            $end_date = $last_update;
        }
        else
        {
            $end_date = time();
        }

        return Util::date_diff($end_date, $start_date, $format);
    }


    /*
     * @deprecated
     */
    public function get_ticket()
    {
        $id = ($this->id >= 10) ? $this->id : "0" . $this->id;
        return strtoupper(substr($this->ref, 0, 3)) . $id;
    }


    public static function get_priority_bgcolor($priority)
    {
        if ($priority > HIGH_PRIORITY) return "#FA0000";
        elseif ($priority > MEDIUM_PRIORITY) return "#FF8A00";
        elseif ($priority > LOW_PRIORITY) return "#94CF05";
        else return "white";
    }


    public static function get_priority_fgcolor($priority)
    {
        if ($priority > HIGH_PRIORITY) return "white";
        elseif ($priority > MEDIUM_PRIORITY) return "black";
        elseif ($priority > LOW_PRIORITY) return "white";
        else return "black";
    }


    public static function get_priority_string($priority)
    {
        if ($priority >= HIGH_PRIORITY) return _("High");
        elseif ($priority >= MEDIUM_PRIORITY) return _("Medium");
        else return _("Low");
    }

    /**
     * This function returns an array with information for a given risk factor
     *
     * @param integer $risk  Risk Factor
     * @return array
     */
    public static function get_risk_info($risk)
    {
        $bgcolor   = "fff";
        $fgcolor   = "000";
        $name_risk = "-";
        switch($risk)
        {
            case 1:
                $bgcolor = 'FF0000';
                $fgcolor = 'fff';
                $name_risk='Critical';
            break;

            case 2:
                $bgcolor = 'FF0000';
                $fgcolor = 'fff';
                $name_risk='High';
            break;

            case 3:
                $bgcolor = 'FFA500';
                $fgcolor = 'fff';
                $name_risk='Medium';
            break;

            case 4:
                $bgcolor = 'FFA500';
                $fgcolor = 'fff';
                $name_risk='Medium';
            break;

            case 5:
                $bgcolor = 'FFA500';
                $fgcolor = 'fff';
                $name_risk='Medium';
            break;

            case 6:
                $bgcolor = 'FFD700';
                $fgcolor = '000';
                $name_risk='Low';
            break;

            case 7:
                $bgcolor = 'F0E68C';
                $fgcolor = '000';
                $name_risk='Info';
            break;

            default:
                $bgcolor = 'fff';
                $fgcolor = '000';
                $name_risk='-';
            break;
        }

        return array($bgcolor, $fgcolor, $name_risk);
    }


    /*
     * Static function
     */
    public static function get_priority_in_html($priority, $link = '')
    {
        $bgcolor = self::get_priority_bgcolor($priority);
        $fgcolor = self::get_priority_fgcolor($priority);

        $html = "<div style='width:20px;margin:0 auto;height:20px;line-height:20px;border-radius:100%;text-align:center;background:$bgcolor'>";

        if ($link != '')
        {
              $html .= "<a href='$link' style='color:$fgcolor' target='topmenu'>$priority</a>";
        }
        else
        {
            $html .= "<span style='color:$fgcolor'>$priority</span>";
        }

        $html .= "</div>";

        return $html;
    }


    public static function get_risk_in_html($risk, $link = '')
    {
        list($bgcolor, $fgcolor, $risk) = self::get_risk_info($risk);

        if ($link != '')
        {
            $html = '<div style="background-color:#' . $bgcolor . ';width:60px;color:#' . $fgcolor . ';text-align:center;">
                        <strong>&nbsp;<a href="'.$link.'" style="color:#' . $fgcolor . '" target="main">' . $risk . '</a>&nbsp;</strong>
                     </div>';

        }
        else
        {
            $html = '<div style="background-color:#' . $bgcolor . ';width:60px;color:#' . $fgcolor . ';text-align:center;">
                        <strong>&nbsp;' . $risk . '&nbsp;</strong>
                     </div>';
        }

        return $html;
    }


    /*
     * @static
     */
    public static function get_validations($type)
    {
        $incident = array(
            'title' => array(
                OSS_ALPHA,
                OSS_SPACE,
                OSS_PUNC_EXT,
                '>',
                'illegal:' . _("Title")
            ) ,
            'type' => array(
                OSS_ALPHA, OSS_PUNC_EXT,
                OSS_SPACE, OSS_SCORE,
                'illegal:' . _("Type")
            ) ,
            'priority' => array(
                OSS_DIGIT,
                'illegal:' . _("Priority")
            ) ,
        );

        $incident_insert = array(
            'ref' => array(
                OSS_LETTER,
                'illegal:' . _("Incident Ref")
            )
        );

        $incident_update = array(
            'incident_id' => array(
                OSS_DIGIT,
                'illegal:' . _("Incident id")
            )
        );

        $event = array(
            'src_ips' => array(
                OSS_SEVERAL_IP_ADDRCIDR_0,
                OSS_NULLABLE,
                'illegal:' . _("Source Ips")
            ) ,
            'dst_ips' => array(
                OSS_SEVERAL_IP_ADDRCIDR_0,
                OSS_NULLABLE,
                'illegal:' . _("Dest Ips")
            ) ,
            'src_ports' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Source Ports")
            ) ,
            'dst_ports' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Dest Ports")
            )
        );

        $metric = array(
            'target' => array(
                OSS_TEXT,
                OSS_NULLABLE,
                'illegal:' . _("Target")
            ) ,
            'metric_type' => array(
                OSS_ALPHA,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Metric type")
            ) ,
            'metric_value' => array(
                OSS_ALPHA,
                OSS_SPACE,
                OSS_PUNC,
                OSS_NULLABLE,
                'illegal:' . _("Metric value")
            )
        );

        $vulnerability = array(
            'ip' => array(
                OSS_IP_ADDRCIDR_0,
                OSS_NULLABLE,
                'illegal:' . _("Ip")
            ) ,
            'port' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Port")
            ) ,
            'nessus_id' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Plugin ID")
            ) ,
            'risk' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Risk")
            )
        );

        $anomaly = array(
            'ip' => array(
                OSS_FQDN_IP,
                OSS_NULLABLE,
                'illegal:' . _("Host")
            ),

            'sensor' => array(
                OSS_FQDN_IP,
                OSS_NULLABLE,
                'illegal:' . _("Sensor")
            ),
            'port' => array(
                OSS_LETTER,
                OSS_DIGIT,
                OSS_PUNC,
                OSS_SPACE,
                OSS_NULLABLE,
                'illegal:' . _("Port")
            ),
            'new_mac'  => array(
                OSS_MAC,
                OSS_NULLABLE,
                'illegal:' . _("New MAC")
            ),
            'old_mac'  => array(
                OSS_MAC,
                OSS_NULLABLE,
                'illegal:' . _("Old MAC")
            ),
            'new_vendor'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("New Vendor")
            ),
            'old_vendor'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("Old Vendor")
            ),
            'new_version'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("New Version")
            ),
            'old_version'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("Old Version")
            ),
            'new_protocol'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("New Prococol")
            ),
            'old_protocol'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("Old Prococol")
            ),
            'when'  => array(
                OSS_ALPHA,
                OSS_PUNC_EXT,
                OSS_NULLABLE,
                'illegal:' . _("When")
            )
        );

        if ($type == 'incident_insert') return array_merge($incident, $incident_insert);
        if ($type == 'incident_update') return array_merge($incident, $incident_update);
        if ($type == 'event')           return $event;
        if ($type == 'metric')          return $metric;
        if ($type == 'vulnerability')   return $vulnerability;
        if ($type == 'anomaly')         return $anomaly;
    }


    public static function colorize_status($status, $return = FALSE, $type = 'normal')
    {
        $status = Util::htmlentities($status, ENT_COMPAT, "UTF-8");

        if ($type == 'normal')
        {
            $ticket_status = array( 'Open'     => array('color' => '#FF0000', 'label' => 'Open'),
                                    'Assigned' => array('color' => '#B8860B', 'label' => 'Assigned'),
                                    'Studying' => array('color' => '#A52A2A', 'label' => 'Studying'),
                                    'Waiting'  => array('color' => '#338E05', 'label' => 'Waiting'),
                                    'Testing'  => array('color' => '#697993', 'label' => 'Testing'),
                                    'Closed'   => array('color' => '#0000CD', 'label' => 'Closed'),
                                   );
        }
        else
        {
            $ticket_status = array( 'Open'     => array('color' => '#E9967A', 'label' => 'Open'),
                                    'Assigned' => array('color' => '#EAA228', 'label' => 'Assigned'),
                                    'Studying' => array('color' => '#AD7E7E', 'label' => 'Studying'),
                                    'Waiting'  => array('color' => '#579575', 'label' => 'Waiting'),
                                    'Testing'  => array('color' => '#697993', 'label' => 'Testing'),
                                    'Closed'   => array('color' => '#4BB2C5', 'label' => 'Closed'),
                                   );
        }

        $color = ($ticket_status[$status]['color'] == '') ? '#000000' : $ticket_status[$status]['color'];
        $label = ($ticket_status[$status]['label'] == '') ? $status   : _($ticket_status[$status]['label']);

        $formated_st = "<span style='color:".$color."'><strong>".$label."</strong></span>";

        if ($return == TRUE)
        {
            return $formated_st;
        }
        else
        {
            echo $formated_st;
        }
    }


    public function get_list_all($conn, $args = array())
    {
        $tz = Util::get_timezone();
        $where = isset($args["where"]) ? $args["where"] : "";
        $having = isset($args["having"]) ? " HAVING ".$args["having"] : "";
        $order = isset($args["order"]) ? $args["order"] : "";
        $list  = array();
        $query = "SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.ref, incident.type_id as type, incident.submitter as submitter,
                    incident.priority, incident.in_charge, incident.status,
                             DATE_ADD(incident.last_update, INTERVAL $tz HOUR) as last_update,
                             DATE_ADD(incident.event_start, INTERVAL $tz HOUR) as event_start,
                             DATE_ADD(incident.event_end, INTERVAL $tz HOUR) as event_end,
                             DATE_ADD(incident.date, INTERVAL $tz HOUR) as date
                    FROM incident 
		$where $having $order
		";

        $query = ossim_query($query);

        $rs  = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            if($rs->fields["ref"] == '')
            {
                $rs->fields["ref"] = "Custom";
            }

            $list[] = new self($rs->fields["id"], $rs->fields["uuid"], $rs->fields["ctx"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , NULL, $rs->fields["event_start"], $rs->fields["event_end"]);

            $rs->MoveNext();
        }

        return $list;
    }


    public function get_allowed_users($conn, $user_t)
    {
        $list    = array();

        $pro     = Session::is_pro();
        $myself  = Session::get_session_user();

        //PERMS
        $user_perms         = array();
        if(!Session::am_i_admin())
        {
            $users_to_assign    = Session::get_users_to_assign($conn);
            $entities_to_assign = Session::get_entities_to_assign($conn);

            foreach ($users_to_assign as $k => $user)
            {
                $user_perms[$user->get_login()] = $user->get_login();
            }

            foreach ($entities_to_assign as $entity => $value)
            {
                $user_perms[$entity] = $entity;
            }

            if (empty($user_perms[$myself]))
            {
                $user_perms[$myself]=$myself;
            } // add myself
        }


        //Admin or all assets
        if (empty($user_t) || Session::is_admin($conn, $user_t))
        {
            return $user_perms;
        }

        if (!$pro)
        {
            if(!empty($user_perms))
            {
                return array_intersect($user_perms, array($user_t => $user_t));
            }
            else
            {
                return array($user_t => $user_t);
            }
        }


        //Entity
        if (valid_hex32($user_t))
        {
            $list               = Acl::get_all_users_by_entity($conn, $user_t);
            $my_entities_childs = Acl::get_entity_childs($conn, $user_t);

            if (is_array($my_entities_childs))
            {
                foreach ($my_entities_childs as $entity_id)
                {
                    $list[$entity_id] = $entity_id;
                }
            }
        }
        else
        {
            // Entity Admin
            if (Acl::is_proadmin($conn, $user_t))
            {
                //Get my brothers

                $list = Acl::get_brothers($conn, $user_t);

                $my_entities = Acl::get_my_entities($conn, $user_t, FALSE);

                if (is_array($my_entities) && !empty($my_entities))
                {
                    foreach ($my_entities as $k => $v)
                    {
                        $list[$k] = $k;
                        foreach ($v['children'] as $child_e)
                        {
                            //Users child entity
                            $list[$child_e] = $child_e;
                            $entity_users = Acl::get_users_by_entity($conn, $child_e);

                            foreach ($entity_users as $e_users)
                            {
                                $list[$e_users['login']] = $e_users['login'];
                            }
                        }
                    }
                }
            }
            else
            {
                $my_entities = Acl::get_my_entities($conn, $user_t, FALSE);

                if(is_array($my_entities))
                {
                    foreach($my_entities as $id => $entity)
                    {
                        $list[$id] = $id;
                    }
                }
            }
        }

        $list[$user_t] = $user_t;

        if(!empty($user_perms) && !empty($list))
        {
            return array_intersect($user_perms, $list);
        }
        elseif(!empty($user_perms))
        {
            return $user_perms;
        }
        else
        {
            return $list;
        }
    }


    public static function get_object($conn, $id = '')
    {
        $tz  = Util::get_timezone();

        $query = "SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref,
                         incident.type_id as type, incident.submitter as submitter,
                         incident.priority, incident.in_charge, incident.status,
                         incident.last_update, incident.event_start, incident.event_end
                FROM incident
                WHERE incident.id = ?";

        $rs = $conn->Execute($query, array($id));

        if (!$rs)
        {
            return FALSE;
        }
        else
        {
            if($tz != 0)
            {
                $rs->fields["date"]        = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["date"])+3600*$tz);
                $rs->fields["last_update"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["last_update"])+3600*$tz);
                $rs->fields["event_start"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_start"])+3600*$tz);
                $rs->fields["event_end"]   = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_end"])+3600*$tz);
            }

            if($rs->fields["ref"] == '')
            {
                $rs->fields["ref"] = "Custom";
            }

            $ticket = new self(
                $rs->fields["id"],
                $rs->fields["uuid"],
                $rs->fields["ctx"],
                $rs->fields["title"],
                $rs->fields["date"],
                $rs->fields["ref"],
                $rs->fields["type"],
                $rs->fields["submitter"],
                $rs->fields["priority"],
                $rs->fields["in_charge"],
                $rs->fields["status"],
                $rs->fields["last_update"],
                array(),
                NULL,
                $rs->fields["event_start"],
                $rs->fields["event_end"]
            );
        }

        return $ticket;
    }


    public static function get_list($conn, $args = array(), $user = '')
    {
        $tz    = Util::get_timezone();
        $list  = array();
        $user  = (empty($user)) ? Session::get_session_user() : $user;
        $user_list = self::get_allowed_users($conn, $user);//Users that I can view their tickets
	$where = isset($args["where"]) ? $args["where"] : "";
        $having = isset($args["having"]) ? " HAVING ".$args["having"] : "";
        $order = isset($args["order"]) ? $args["order"] : "";
        $all_param_together = !empty($args) && empty($where) && empty($having) && empty($order) ? $args : "";

        if(empty($user_list))
        {
            $query   = "SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.ref,
                             incident.type_id as type, incident.submitter as submitter,
                             incident.priority, incident.in_charge, incident.status,
                             DATE_ADD(incident.last_update, INTERVAL $tz HOUR) as last_update,
                             DATE_ADD(incident.event_start, INTERVAL $tz HOUR) as event_start,
                             DATE_ADD(incident.event_end, INTERVAL $tz HOUR) as event_end,
                             DATE_ADD(incident.date, INTERVAL $tz HOUR) as date
                      FROM incident WHERE 1=1
                      $where $having $order $all_param_together
           ";
        }
        else
        {
            $user_where = "'".implode("', '", $user_list)."'";

            $query   = "SELECT DISTINCT incident.id, incident.uuid, incident.ctx, incident.title, incident.ref,
                             incident.type_id as type, incident.submitter as submitter,
                             incident.priority, incident.in_charge, incident.status,
                             DATE_ADD(incident.last_update, INTERVAL $tz HOUR) as last_update,
                             DATE_ADD(incident.event_start, INTERVAL $tz HOUR) as event_start,
                             DATE_ADD(incident.event_end, INTERVAL $tz HOUR) as event_end,
                             DATE_ADD(incident.date, INTERVAL $tz HOUR) as date
                      FROM incident
                      LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id
                      LEFT JOIN incident_subscrip ON incident_subscrip.incident_id = incident.id
                      WHERE 1=1 AND(
                        incident.in_charge in ($user_where) OR
                        incident.submitter in ($user_where) OR
                        incident_ticket.users in ($user_where) OR
                        incident_ticket.in_charge in ($user_where) OR
                        incident_ticket.transferred in ($user_where) OR
                        incident_subscrip.login in ($user_where)
                        )
			$where $having $order
                    ";

        }

        $query = ossim_query($query);

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        while (!$rs->EOF)
        {
            if($rs->fields["ref"] == '')
            {
                $rs->fields["ref"] = "Custom";
            }

            $list[] = new self($rs->fields["id"], $rs->fields["uuid"], $rs->fields["ctx"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , NULL, $rs->fields["event_start"], $rs->fields["event_end"]);

            $rs->MoveNext();
        }
        return $list;
    }


    public static function get_tickets_count($conn, $args)
    {
        $value = 0;

        $query = "SELECT count(*) as num FROM incident WHERE 1 $args";;
        $query = ossim_query($query);

        $rs  = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $value = $rs->fields['num'];
        }

        return $value;
    }


    public static function get_list_filtered($conn, $asset_filter, $args = '', $user = '')
    {
        $tz = Util::get_timezone();

        if ($user != '' || empty($asset_filter))
        {
            $list = self::get_list($conn, $args, $user);

            return $list;
        }
        // Network or Host filter
        $list = array();

        if (empty($asset_filter))
        {
            $assets = array();
        }
        else
        {
            if (is_array($asset_filter))
            {
                foreach ($asset_filter as $k => $v)
                {
                    // Long format
                    if (preg_match('/^\d+$/', $v))
                    {
                        $v = long2ip($v);
                        $assets[$k] = $v;
                    }

                    // CIDR format (one or multiple CIDRs)
                    elseif (preg_match('/^\d+\.\d+\.\d+\.\d+\/\d+/', $v))
                    {
                        $cidrs = explode(',', $v);
                        foreach ($cidrs as $cidr)
                        {
                                $_range     = CIDR::expand_cidr($cidr);
                                $v          = $_range[0].','.$_range[1];
                                $assets[$k] = $v;
                        }
                    }
                    // Other
                    else
                    {
                        $assets[$k] = $v;
                    }
                }
            }
            else
            {
                $assets[$asset_filter] = $asset_filter;
            }
        }

        $host_where = $host_where_2 = $host_where_3 = '';

        foreach ($assets as $k => $v)
        {
            //Filtered by the first network if there are multiples
            if (preg_match("/,/",$v))
            {
                $range         = explode(",",$v);
                $host_where   .= " OR (INET_ATON(SUBSTRING_INDEX(a.src_ips, ',', 1)) >= ".$range[0]." AND INET_ATON(SUBSTRING_INDEX(a.src_ips, ',', 1)) <= ".$range[1].") OR (INET_ATON(SUBSTRING_INDEX(a.dst_ips, ',', 1)) >= ".$range[0]." AND INET_ATON(SUBSTRING_INDEX(a.dst_ips, ',', 1)) <= ".$range[1].")";
                $host_where_2 .= " OR (INET_ATON(SUBSTRING_INDEX(a.ip, ',', 1)) >= ".$range[0]." AND INET_ATON(SUBSTRING_INDEX(a.ip, ',', 1)) <= ".$range[1].")";
                $host_where_3 .= " OR a.target LIKE '%$v%'";

                //$host_where   .= "a.src_ips LIKE '$asset_filter%' OR a.dst_ips LIKE '$asset_filter%' OR a.src_ips LIKE ',$asset_filter%' OR a.dst_ips LIKE ',$asset_filter%'";
                //$host_where_2 .= "a.ip LIKE '$asset_filter%'";
                //$host_where_3 .= "a.target LIKE '$asset_filter%'";
            }
            else
            {
                $host_where   .=  " OR a.src_ips LIKE '$v' OR a.src_ips LIKE '%,$v,%' OR a.src_ips LIKE '$v,%' OR a.src_ips LIKE '%,$v'
                                   OR a.dst_ips LIKE '$v' OR a.dst_ips LIKE '%,$v,%' OR a.dst_ips LIKE '$v,%' OR a.dst_ips LIKE '%,$v'";
                $host_where_2 .=  " OR a.ip LIKE '$v' OR a.ip LIKE '%,$v,%' OR a.ip LIKE '$v,%' OR a.ip LIKE '%,$v'";
                $host_where_3 .=  " OR a.target LIKE '$v' OR a.target LIKE '%,$v,%' OR a.target LIKE '$v,%' OR a.target LIKE '%,$v'";

                //$host_where   .= " OR INET_ATON(SUBSTRING_INDEX(a.src_ips, ',', 1)) = '$v' OR INET_ATON(SUBSTRING_INDEX(a.dst_ips, ',', 1)) = '$v'";
                //$host_where_2 .= " OR INET_ATON(SUBSTRING_INDEX(a.ip, ',', 1)) = '$v'";
                //$host_where_3 .= " OR a.target LIKE '%$v%'";
            }
        }

        $host_where   = preg_replace("/^ OR /", '', $host_where);
        $host_where_2 = preg_replace("/^ OR /", '', $host_where_2);
        $host_where_3 = preg_replace("/^ OR /", '', $host_where_3);

        if ($host_where   == '') $host_where  ="1=1";
        if ($host_where_2 == '') $host_where_2="1=1";
        if ($host_where_3 == '') $host_where_3="1=1";
	$qwhere = (isset($args["where"]) ? $args["where"] : " ") .
		(isset($args["having"]) ? " HAVING ". $args["having"] : " ") .
		(isset($args["order"]) ? $args["order"] : " ");
	$args = $qwhere;
        $query = "(SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge, incident.status, incident.last_update, incident.event_start, incident.event_end,a.src_ips
                 FROM incident,incident_alarm a
                 WHERE incident.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge, incident.status, incident.last_update, incident.event_start, incident.event_end,a.ip as src_ips
                  FROM incident,incident_vulns a
                  WHERE incident.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge, incident.status, incident.last_update, incident.event_start, incident.event_end,a.src_ips
                 FROM incident,incident_event a
                 WHERE incident.id=a.incident_id AND ($host_where) $args)
                UNION
                (SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge, incident.status, incident.last_update, incident.event_start, incident.event_end,a.ip as src_ips
                 FROM incident,incident_anomaly a
                 WHERE incident.id=a.incident_id AND ($host_where_2) $args)
                UNION
                (SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge, incident.status, incident.last_update, incident.event_start, incident.event_end,a.target as src_ips
                 FROM incident,incident_metric a
                 WHERE incident.id=a.incident_id AND ($host_where_3) $args)
                ";
        $query = ossim_query($query);

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            if($tz != 0)
            {
                $rs->fields["date"]        = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["date"])+3600*$tz);
                $rs->fields["last_update"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["last_update"])+3600*$tz);
                $rs->fields["event_start"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_start"])+3600*$tz);
                $rs->fields["event_end"]   = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_end"])+3600*$tz);
            }
            $allowed = self::get_users_list($conn, $rs->fields["id"], $rs->fields["submitter"], $rs->fields["in_charge"]);

            $user = Session::get_session_user();

            if (!empty($allowed[$user]))
            {
                if($rs->fields["ref"] == '')
                {
                    $rs->fields["ref"] = "Custom";
                }

                $list[] = new self($rs->fields["id"], $rs->fields["uuid"], $rs->fields["ctx"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , NULL, $rs->fields["event_start"], $rs->fields["event_end"], $rs->fields["event_end"], $rs->fields['src_ips']);
            }

            $rs->MoveNext();
        }

        return $list;
    }


    public function get_list_type_descr($conn, $args = '')
    {
        $tz = Util::get_timezone();

        $user = Session::get_session_user();
        $list = array();

        $query  = "SELECT incident.id, incident.uuid, incident.ctx, incident.title, incident.date, incident.ref, incident.type_id as type, incident.submitter as submitter, incident.priority, incident.in_charge, incident.status, incident.last_update, incident.event_start, incident.event_end, incident_type.descr FROM incident LEFT JOIN incident_type ON (incident.type_id = incident_type.id) $args";
        $query  = ossim_query($query);

        $rs   = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $allowed = self::get_users_list($conn, $rs->fields["id"], $rs->fields["submitter"], $rs->fields["in_charge"]);

            if (!empty($allowed[$user]))
            {
                if($tz != 0)
                {
                    $rs->fields["date"]        = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["date"])+3600*$tz);
                    $rs->fields["last_update"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["last_update"])+3600*$tz);
                    $rs->fields["event_start"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_start"])+3600*$tz);
                    $rs->fields["event_end"]   = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_end"])+3600*$tz);
                }

                if($rs->fields["ref"] == '')
                {
                    $rs->fields["ref"] = "Custom";
                }

                $my_incident = new self($rs->fields["id"], $rs->fields["uuid"], $rs->fields["ctx"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], array() , NULL, $rs->fields["event_start"], $rs->fields["event_end"]);

                $my_incident->set_type_descr($rs->fields["descr"]);

                $list[] = $my_incident;
            }

            $rs->MoveNext();
        }

        return $list;
    }


    public function get_keywords_from_type($conn)
    {
        $keywords = '';
        $id_type  = $this->get_type();

        $query = "SELECT keywords FROM incident_type WHERE id='$id_type'";
        $query = ossim_query($query);

        $rs  = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $keywords = $rs->fields["keywords"];
        }

        return $keywords;
    }


    public static function search($conn, $search_criteria, $order_by = 'life_time', $order_mode = 'DESC', $page = 1, $rows_per_page = 50)
    {
        $tz  = Util::get_timezone();
        $tzc = Util::get_tzc();

        $search_opts = array(
            'incident_id',
            'ref',
            'type',
            'title',
            'with_text',
            'status',
            'priority_str',
            'attach_name',
            'submitter',
            'in_charge',
            'related_to_user',
            'last_update',
            'creation_date',
            'tag'
        );

        foreach($search_opts as $o)
        {
            $opts[$o] = isset($search_criteria[$o]) ? $search_criteria[$o] : NULL;
        }

        $query = (!$opts['with_text']) ? "SELECT SQL_CALC_FOUND_ROWS DISTINCT incident.* " : "SELECT DISTINCT incident.* ";
        $where = $parms = array();

        if (is_array($opts['last_update']))
        {
            $where[] = "convert_tz(incident.last_update,'+00:00','".$tzc."') between ".$opts['last_update'][0]." AND ".$opts['last_update'][1];
        }
        elseif ($opts['last_update'])
        {
            $where[] = "convert_tz(incident.last_update,'+00:00','".$tzc."') >= ".$opts['last_update'];
        }

        if (is_array($opts['creation_date']))
        {
            $where[] = "convert_tz(incident.date,'+00:00','".$tzc."') between ".$opts['creation_date'][0]." AND ".$opts['creation_date'][1];
        }
        elseif ($opts['creation_date'])
        {
            $where[] = "convert_tz(incident.date,'+00:00','".$tzc."') >= ".$opts['creation_date'];
        }

        if ($opts['incident_id'])
        {
            $where[] = "incident.id = ?";
            $parms[] = $opts['incident_id'];
        }

        if ($opts['ref'])
        {
            $where[] = "incident.ref = ?";
            $parms[] = $opts['ref'];
        }

        if ($opts['type'])
        {
            $where[] = "incident.type_id = ?";
            $parms[] = $opts['type'];
        }

        if ($opts['title'])
        {
            $where[] = "UPPER(incident.title) LIKE ?";
            // XXX use that strange trick to overpass Adodb quoting limitations
            $parms[] = '%' . strtoupper($opts['title']) . '%';
        }

        if ($opts['status'])
        {
            if($opts['status'] == "not_closed")
            {
                $where[] = "incident.status != 'Closed'";
            }
            else
            {
                $where[] = "incident.status = ?";
                $parms[] = $opts['status'];
            }
        }

        if ($opts['priority_str'])
        {
            $p = $opts['priority_str'];

            if ($p == 'High')
            {
                $where[] = "incident.priority > 7";
            }
            elseif ($p == 'Medium')
            {
                $where[] = "incident.priority <= 7 AND incident.priority >= 5";
            }
            else
            {
                $where[] = "incident.priority <= 4";
            }
        }

        if ($opts['attach_name'])
        {
            $where[] = "UPPER(incident_file.name) LIKE ? AND incident_file.incident_id=incident.id";
            $parms[] = '%' . strtoupper($opts['attach_name']) . '%';
        }

        if ($opts['submitter'])
        {
            $where[] = "UPPER(incident.submitter) LIKE ?";
            $parms[] = '%' . strtoupper($opts['submitter']) . '%';
        }

        if ($opts['in_charge'])
        {
            $where[] = "incident.in_charge = ?";
            $parms[] = $opts['in_charge'];

        }

        if ($opts['tag'])
        {
            $where[] = "incident_tag.tag_id=? AND incident_tag.incident_id = incident.id";
            $parms[] = $opts['tag'];
        }

        // Big brother search
        if ($opts['with_text'])
        {
            $text      = '%' . strtoupper($opts['with_text']) . '%';
            $tmp_w     = $where;
            $all_parms = array();
            //
            // Search over tickets description and action
            //
            $t = "(UPPER(incident_ticket.description) LIKE ?) OR (UPPER(incident_ticket.action) LIKE ?)";
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));
            $tmp_w[]   = "($t) AND incident_ticket.incident_id = incident.id";
            $queries[] = self::_build_sql($query, $tmp_w);

            // search also inside the title if the user didn't specified
            // other specific title search
            if (!$opts['title'])
            {
                $tmp_w     = $where;
                $tmp_w[]   = "(UPPER(incident.title) LIKE ?)";
                $all_parms = array_merge($all_parms, $parms, array($text));
                $queries[] = self::_build_sql($query, $tmp_w);
            }

            //
            // search over the IPs in event
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_event.src_ips) LIKE ? OR UPPER(incident_event.dst_ips) LIKE ?) AND incident_event.incident_id = incident.id";
            $queries[] = self::_build_sql($query, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));

            //
            // search over the IPs in alarm
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_alarm.src_ips) LIKE ? OR UPPER(incident_alarm.dst_ips) LIKE ?) AND incident_alarm.incident_id = incident.id";
            $queries[] = self::_build_sql($query, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text,
                $text
            ));

           //
            // search over the IPs in alarm
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_metric.target) LIKE ?) AND incident_metric.incident_id = incident.id";
            $queries[] = self::_build_sql($query, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text
            ));

           //
            // Search over the IPs in vulnerabilities
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(UPPER(incident_vulns.ip) LIKE ?) AND incident_vulns.incident_id = incident.id";
            $queries[] = self::_build_sql($query, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                 $text
            ));

           //
            // Search over custom fields
            //
            $tmp_w     = $where;
            $tmp_w[]   = "(incident_custom.content like ?) AND incident_custom.incident_id = incident.id";
            $queries[] = self::_build_sql($query, $tmp_w);
            $all_parms = array_merge($all_parms, $parms, array(
                $text
            ));

            //
            // Build the final big UNION SQL and params
            //
            $query = implode("\nUNION\n", $queries);
            $parms = $all_parms;
            //printr($parms);
        }
        else
        {
            $query = self::_build_sql($query, $where);
        }


        // Order by
        if ($order_by == 'life_time')
        {
            $order_by = "date";
        }

        $order_by = ($order_by != '') ? "\nORDER BY $order_by $order_mode" : '';
        $query   .= $order_by;

        // limit
        if (!$opts['with_text'])
        {
            $query .= " LIMIT ".(abs($page-1)*$rows_per_page).",$rows_per_page";
        }

        // Gets tags
        $tags = array();

        $t_sql = "SELECT incident_tag.tag_id, incident_tag.incident_id FROM incident_tag";
        $rs    = $conn->Execute($t_sql);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $tags[$rs->fields["incident_id"]][] = $rs->fields["tag_id"];
            $rs->MoveNext();
        }

        $rs = $conn->Execute($query, $parms);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Caching total count
        $rf = $conn->Execute("SELECT FOUND_ROWS() AS total");
        if ($rf)
        {
            $_SESSION["_incident_search_count"] = $rf->fields["total"];
        }

        // Build the resultant list of incidents
        $list = array();
        while (!$rs->EOF)
        {
            // Life Time calculations:
            $life_time_diff = ($rs->fields["status"] == 'Open') ? time() - strtotime($rs->fields["date"]) : strtotime($rs->fields["date"]) - strtotime($rs->fields["last_update"]);

            $itags  = (isset($tags[$rs->fields["id"]])) ? $tags[$rs->fields["id"]] : array();

            if($tz != 0)
            {
                $rs->fields["date"]        = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["date"])+3600*$tz);
                $rs->fields["last_update"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["last_update"])+3600*$tz);
                $rs->fields["event_start"] = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_start"])+3600*$tz);
                $rs->fields["event_end"]   = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($rs->fields["event_start_end"])+3600*$tz);
            }

            if($rs->fields["ref"] == '')
            {
                $rs->fields["ref"] = "Custom";
            }

            $list[] = new self($rs->fields["id"], $rs->fields["uuid"], $rs->fields["ctx"], $rs->fields["title"], $rs->fields["date"], $rs->fields["ref"], $rs->fields["type_id"], $rs->fields["submitter"], $rs->fields["priority"], $rs->fields["in_charge"], $rs->fields["status"], $rs->fields["last_update"], $itags, $life_time_diff, $rs->fields["event_start"], $rs->fields["event_end"]);

            $rs->MoveNext();
        }

        return $list;
    }


    public static function search_count($conn)
    {
        if (isset($_SESSION["_incident_search_count"]))
        {
            return $_SESSION["_incident_search_count"];
        }

        $foundrows = 0;

        $rf = $conn->Execute("SELECT FOUND_ROWS() AS total");
        if ($rf)
        {
            $foundrows = $rf->fields["total"];
        }

        return $foundrows;
    }


    public function _build_sql($start_sql, $where)
    {
        $user_where = '';
        $user_tmp   = array();

        $w    = implode("\nAND ", $where);
        $from = '';

        // Scan needed tables
        if (count($where))
        {
            if (preg_match_all('/([a-z_]+)\./', $w, $m))
            {
                $arr = array_reverse(array_unique(array_merge(array('incident'),$m[1])));
                $from = implode(', ', $arr);
            }
        }

        if (!$from)
        {
            $from = 'incident';
        }

        $query   = $start_sql . "\nFROM $from";
        $myself  = Session::get_session_user();

        $dbaux  = new ossim_db();
        $dbconn = $dbaux->connect();

        $users_to_assign    = Session::get_users_to_assign($dbconn);
        $entities_to_assign = Session::get_entities_to_assign($dbconn);

        foreach ($users_to_assign as $user)
        {
            $user_tmp[$user->get_login()] = $user->get_login();
        }

        foreach ($entities_to_assign as $entity => $value)
        {
            $user_tmp[$entity] = $entity;
        }

        $dbaux->close();


        if (!Session::am_i_admin() && empty($user_tmp[$myself]))
        {
            $user_tmp[$myself] = $myself;
        }

        $user_where = "'".implode("', '", $user_tmp)."'";

        if (!Session::am_i_admin())
        { //  $user != ACL_DEFAULT_OSSIM_ADMIN
            if (preg_match("/incident_event/",$w) || preg_match("/incident_alarm/",$w)
                || preg_match("/incident_metric/",$w) || preg_match("/incident_vulns/",$w) || preg_match("/incident_custom/",$w))
            {
                $query .= " LEFT JOIN incident_subscrip ON incident_subscrip.incident_id=incident.id WHERE incident_subscrip.login in ($user_where)";
            }
            else
            {
                $incident_ticket = (!preg_match("/incident_ticket/",$from)) ? TRUE : FALSE;
                $query .= (($incident_ticket) ? " LEFT JOIN incident_ticket ON incident_ticket.incident_id = incident.id" : '')." LEFT JOIN incident_subscrip ON incident_subscrip.incident_id=incident.id WHERE ".((!$incident_ticket) ? "incident_ticket.incident_id=incident.id AND" : '');
                $query .= "(incident.in_charge in ($user_where) OR incident_ticket.users in ($user_where) OR incident_ticket.in_charge in ($user_where) OR incident_ticket.transferred in ($user_where) OR incident_subscrip.login in ($user_where))";
            }

            if (count($where))
            {
                $query.= "\n AND $w";
            }
        }
        else
        {
            if (count($where))
            {
                $query.= "\n WHERE $w";
            }
        }

        return $query;
    }


    /*
    * Used by the search() function
    */
    public function _sort_by_life_time($a, $b)
    {
        $diff1 = $a->_life_time_diff;
        $diff2 = $b->_life_time_diff;

        if ($diff1 == $diff2)
        {
            return 0;
        }

        return ($diff1 > $diff2) ? 1 : -1;
    }


    /*
     * @deprecated
     */
    public function get_tickets($conn)
    {
        return Incident_ticket::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY date");
    }


    public function get_alarms($conn)
    {
        return Incident_alarm::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }


    public function get_events($conn)
    {
        return Incident_event::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }


    function get_metrics($conn)
    {
        return Incident_metric::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }


    public function get_anomalies($conn)
    {
        return Incident_anomaly::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }


    public function get_vulnerabilities($conn)
    {
        return Incident_vulnerability::get_list($conn, "WHERE incident_id = " . $this->id . " ORDER BY id");
    }


    public function get_custom($conn)
    {
        $list = array();

        $query  = ossim_query("SELECT * FROM incident_custom WHERE incident_id=? ORDER BY id ASC");
        $params = array($this->id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields["id"]] = array($rs->fields["name"],$rs->fields["content"],$rs->fields["incident_custom_type_id"],$rs->fields["id"]);
                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function get_custom_content($conn,$id,$incident_id)
    {
        $query  = ossim_query("SELECT name,content FROM incident_custom WHERE id=? and incident_id=?");
        $params = array($id, $incident_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                return array($rs->fields["name"],$rs->fields["content"]);
            }
        }

        return array();
    }


    /* get users that are part of an incident (the main Admin included)
     * (user, in_charge, transferred and subscribed to incidents)
    */
    public static function get_users_list($conn, $incident_id, $submitter, $in_charge, $user = NULL)
    {
        $list = array();

        $pro   = Session::is_pro();
        $user  = ($user == '') ? Session::get_session_user() : $user;


        $submitter_data = explode("/", $submitter);

        //Admin
        if (Session::is_admin($conn, $user))
        {
            return array($user => $user);
        }

        //I'm the submmiter or I'm in charge
        if ($submitter_data[1] == $user || $in_charge == $user)
        {
            return array($user => $user);
        }

        if ($pro && valid_hex32($in_charge))
        {
            $e_users = Acl::get_users_by_entity($conn, $in_charge);

            if (is_array($e_users) && !empty($e_users))
            {
                foreach ($e_users as $e_user)
                {
                    $list[$e_user['login']] = $e_user['login'];
                }
            }

            $parents = Acl::get_entity_parents($conn, $in_charge);

            foreach ($parents as $i => $parent)
            {
                $entity  = Acl::get_entity($conn, $parent, FALSE, FALSE);

                if (!empty($entity['admin_user']))
                {
                    $list[$entity['admin_user']] = $entity['admin_user'];
                }
            }
        }
        elseif ($pro && !valid_hex32($in_charge))
        {
            if (Acl::is_proadmin($conn, $in_charge))
            {
                //Get brothers
                $list = Acl::get_brothers($conn, $in_charge);

                $my_entities = Acl::get_my_entities($conn, $in_charge, FALSE);

                if (is_array($my_entities) && !empty($my_entities))
                {
                    foreach ($my_entities as $k => $v)
                    {
                        $list[$k] = $k;
                        foreach ($v['children'] as $child_e)
                        {
                            //Users child entity
                            $list[$child_e] = $child_e;
                            $entity_users = Acl::get_users_by_entity($conn, $child_e);

                            foreach ($entity_users as $e_users)
                            {
                                $list[$e_users['login']] = $e_users['login'];
                            }
                        }
                    }
                }
            }
            else
            {
                $my_entities = Acl::get_my_entities($conn, $in_charge, FALSE);

                if(is_array($my_entities))
                {
                    foreach($my_entities as $id => $entity)
                    {
                        $list[$id] = $id;
                    }
                }
            }
        }

        $list[$in_charge] = $in_charge;

        $query = ossim_query("SELECT users, transferred, in_charge FROM incident_ticket WHERE incident_id = " . $incident_id);

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $users_it     = $rs->fields["users"];
                $transferred  = $rs->fields["transferred"];
                $in_charge    = $rs->fields["in_charge"];

                if (!empty($users_it))
                {
                    $list[$users_it] = $users_it;
                }

                if (!empty($transferred))
                {
                    if ($pro && valid_hex32($transferred))
                    {
                        $parents = Acl::get_entity_parents($conn, $transferred);

                        foreach ($parents as $i => $parent)
                        {
                            $entity_users  = Acl::get_users_by_entity($conn, $parent);

                            foreach ($entity_users as $user)
                            {
                                $list[$user['login']] = $user['login'];
                            }
                        }
                    }
                    else
                    {
                        $list[$transferred] = $transferred;
                    }
                }

                if (!empty($in_charge))
                {
                    if ($pro && valid_hex32($in_charge))
                    {
                        $parents = Acl::get_entity_parents($conn, $in_charge);

                        foreach ($parents as $i => $parent)
                        {
                            $entity_users = Acl::get_users_by_entity($conn, $parent);
                            foreach ($entity_users as $user)
                            {
                                $list[$user['login']] = $user['login'];
                            }
                        }
                    }
                    else
                    {
                        $list[$in_charge] = $in_charge;
                    }
                }

                $rs->MoveNext();
            }
        }

        $suscrib = self::get_subscribed_users($conn, $incident_id);


        foreach($suscrib as $s)
        {
            $list[$s['login']] = $s['login'];
        }

        return $list;
    }

    /*
    * @return array in the form:
    *              array(0 => array('login' => 'foo',
    *                               'name' => 'bar',
    *                               'email' => XXX,
    *                               'company' => XXX,
    *                               'department' => XXX))
    */
    public static function get_subscribed_users($conn, $incident_id, $entity_id = '')
    {
        $pro   = Session::is_pro();

        $list1 = array();

        //Getting subscribers
        $query = "SELECT u.login, u.name, u.email, u.company, u.department FROM users u, incident_subscrip s
            WHERE s.incident_id = ? AND u.login = s.login ORDER BY u.name";

        $rs = $conn->Execute($query, array($incident_id));

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list1[] = $rs->fields;

                $rs->MoveNext();
            }
        }
        //Getting entity users
        $users = null;
        if ($pro && valid_hex32($entity_id))
        {
            $users_entity = array();
            $users = Acl::get_all_users_by_entity($conn, $entity_id);
        } elseif ($entity_id !== '') {
            $users = array($entity_id);
        }
        if (is_array($users) && !empty($users))
        {
            foreach ($users as $user)
            {
                $users_entity[] = "'".$user."'";
            }

            $query = "SELECT u.login, u.name, u.email, u.company, u.department FROM users u
                WHERE u.login in (".implode(",",$users_entity).") ORDER BY u.name";

            $rs = $conn->Execute($query);
            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                    $list1[] = $rs->fields;
                    $rs->MoveNext();
                }
            }
        }
        return array_map("unserialize", array_unique(array_map("serialize", $list1)));
    }


    /*
     *  @return true if current user have permissions to edit, delete,
     *  subscribe and unsubscribe incidents
     */
    public static function user_incident_perms($conn, $incident_id, $action, $user = NULL)
    {
        $user = (empty($user)) ? Session::get_session_user() : $user;

        //I'm admin
        if (Session::am_i_admin())
        {
            return TRUE;
        }

        $pro = Session::is_pro();

        $query = ossim_query("SELECT submitter, in_charge FROM incident WHERE id = ".$incident_id);
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $submitter      = $rs->fields["submitter"];
            $submitter_data = explode("/", $submitter);
            $in_charge      = $rs->fields["in_charge"];
        }


        //I'm the submmiter

        if (!empty($submitter_data[1]))
        {
            if($submitter_data[1] == $user)
            {
                return TRUE;
            }
        }
        else
        {
            //If login doesn't exist in the submitter field, we use the username field
            $session_info = Session::get_user_info($conn, $user);

            if ($submitter_data[0] == $session_info->get_name())
            {
                return TRUE;
            }
        }


        if (($action == 'show' || $action == 'e_subscription' || $action == 'newticket'))
        {
            $allowed = self::get_users_list($conn, $incident_id, $submitter, $in_charge);

            if (!empty($allowed[$user]))
            {
                return TRUE;
            }
        }
        elseif ($action == "delincident" || $action == "editincident" || $action == 'closed')
        {
            if ($action == "delincident" && !Session::menu_perms("analysis-menu", "IncidentsDelete"))
            {
                return FALSE;
            }

            // The ticket is in charge of an entity

            if ($pro && valid_hex32($in_charge))
            {
                $my_entity = Acl::get_entity($conn, $in_charge, FALSE, FALSE);

                if ($user == $my_entity['admin_user'])
                {
                    return TRUE;
                }
                else
                {
                    $entities = Acl::get_entity_parents($conn, $in_charge);
                    foreach ($entities as $k => $v)
                    {
                        $entity = Acl::get_entity($conn, $v, FALSE, FALSE);

                        if ($entity['admin_user'] == $user)
                        {
                            return TRUE;
                        }
                    }
                }
            }
            elseif ($pro && !valid_hex32($in_charge))
            {
                $my_entities = Acl::get_my_entities($conn, $in_charge, FALSE);

                if (!empty($my_entities))
                {
                    foreach ($my_entities as $entity_id => $entity)
                    {
                        if ($entity['admin_user'] == $user)
                        {
                            return TRUE;
                        }
                        else
                        {
                            $entities  = Acl::get_entity_parents($conn, $entity_id);
                            foreach ($entities as $k => $v)
                            {
                                $entity = Acl::get_entity($conn, $v, FALSE, FALSE);

                                if ($entity['admin_user'] == $user)
                                {
                                    return TRUE;
                                }
                            }
                        }
                    }
                }
            }
        }

        return FALSE;
    }


    /*
     * @deprecated
     *
     * returns True if user is admin or is in charge of the ticket, instead returns False
     *
     */
    public function user_ticket_perms($conn, $user, $ticket_id)
    {
        if (Session::is_admin($conn, $user))
        {
            return TRUE;
        }

        $query = ossim_query("SELECT in_charge FROM incident_ticket WHERE id = $ticket_id");
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            return FALSE;
        }
        elseif (!$rs->EOF)
        {
            if ($rs->fields["in_charge"] == $user)
            {
                return TRUE;
            }
        }

        return FALSE;
    }


    public static function print_td_incident_type($conn, $selected = '')
    {
        $output = '<td class="left"><select name="type" id="type" class="vfield">';

        if ($list = Incident_type::get_list($conn))
        {
            foreach($list as $type)
            {
                if ($type->get_id() != '')
                {
                    $output.= "<option ";

                    if ($selected == $type->get_id())
                    {
                        $output.= " selected='selected' ";
                    }

                    $output.= " value=\"" . $type->get_id() . "\">";
                    $output.= $type->get_id();
                    $output.= "</option>";
                }
            }
        }

        $output.= '</select></td>';

        print $output;
    }


    public function __insert($conn, $title, $type, $submitter, $priority, $ref, $event_start, $event_end, $in_charge = '')
    {
        $user = Session::get_session_user();

        if($in_charge == '')
        {
            $in_charge = $user;
        }
        $tz = Util::get_timezone();

        if($tz != 0 )
        {
            $date = gmdate("Y-m-d H:i:s");

            if (empty($event_start))
            {
                $event_start = $date;
            }
            else
            {
                list ($y,$m,$d,$h,$u,$s,$time) = Util::get_utc_from_date($conn, $event_start, $tz);
                $event_start = "$y-$m-$d $h:$u:$s";
            }

            if (empty($event_end))
            {
                $event_end = $date;
            }
            else
            {
                list ($y,$m,$d,$h,$u,$s,$time) = Util::get_utc_from_date($conn, $event_end, $tz);
                $event_end = "$y-$m-$d $h:$u:$s";
            }
        }
        else
        {
            $date = date('Y-m-d H:i:s');

            if (empty($event_start))
            {
                $event_start = $date;
            }
            else
            {
                $event_start = date('Y-m-d H:i:s', strtotime($event_start));
            }
            if (empty($event_end))
            {
                $event_end   = $date;
            }
            else
            {
                $event_end   = date('Y-m-d H:i:s', strtotime($event_end));
            }
        }

        $query = "INSERT INTO incident (uuid, ctx, title, type_id, ref, submitter, priority, in_charge, status, date, last_update, event_start, event_end) VALUES (unhex(REPLACE(UUID(), '-', '')),unhex(?),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            Session::get_default_ctx(),
            $title,
            $type,
            $ref,
            $submitter,
            $priority,
            $in_charge,
            'Open',
            $date,
            $date,
            $event_start,
            $event_end
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        /* Get last inserted id */
        $query = ossim_query("SELECT LAST_INSERT_ID() as id FROM incident");

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $last_id = $rs->fields["id"];
        // Automatically subscribe incident creator
        self::insert_subscription($conn, $last_id, $user);
        if ($user != $in_charge) {
            self::insert_subscription($conn, $last_id, $in_charge);
        }

        return $last_id;
    }


    public static function insert_alarm($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end,
       $backlog_id, $event_id, $alarm_group_id, $transferred_user, $transferred_entity)
    {

        $in_charge = ($transferred_user != '')  ? $transferred_user : $transferred_entity;
        $in_charge = ($in_charge != '')         ? $in_charge : Session::get_session_user();

        $last_id = self::__insert($conn, $title, $type, $submitter, $priority, "Alarm", $event_start, $event_end, $in_charge);

        $query = "INSERT INTO incident_alarm (incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id) VALUES (?,?,?,?,?,unhex(?),unhex(?),unhex(?))";

        $params = array(
            $last_id,
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports,
            $backlog_id,
            $event_id,
            $alarm_group_id
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Send email automatically
        Incident_ticket::mail_subscription($conn, $last_id, '0', 'CREATE_INCIDENT');

        return $last_id;
    }


    public static function insert_event($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start,
        $event_end, $transferred_user, $transferred_entity)
    {

        $in_charge = ($transferred_user != '') ? $transferred_user : $transferred_entity;
        $in_charge = ($in_charge != '')        ? $in_charge : Session::get_session_user();

        $last_id   = self::__insert($conn, $title, $type, $submitter, $priority, "Event", $event_start, $event_end, $in_charge);

        $query = "INSERT INTO incident_event (incident_id, src_ips, dst_ips, src_ports, dst_ports) VALUES (?, ?, ? ,?, ?)";

        $params = array(
            $last_id,
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports
        );

        //$conn->debug = TRUE;
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Send email automatically
        Incident_ticket::mail_subscription($conn, $last_id, '0', 'CREATE_INCIDENT');

        return $last_id;
    }


    public static function insert_anomaly($conn, $title, $type, $submitter, $priority, $anom_type, $ip, $data_orig, $data_new, $transferred_user, $transferred_entity)
    {
        $s_data_orig = implode(',', $data_orig);
        $s_data_new  = implode(',', $data_new);

        $in_charge = ($transferred_user != '') ? $transferred_user : $transferred_entity;
        $in_charge = ($in_charge != '')        ? $in_charge : Session::get_session_user();

        $last_id = self::__insert($conn, $title, $type, $submitter, $priority, 'Anomaly', '', '', $in_charge);

        $query = "INSERT INTO incident_anomaly (incident_id, anom_type, ip, data_orig, data_new) VALUES (?, ?, ?, ?, ?)";

        $params = array(
            $last_id,
            $anom_type,
            $ip,
            $s_data_orig,
            $s_data_new
        );

        //$conn->debug = TRUE;
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Send email automatically
        Incident_ticket::mail_subscription($conn, $last_id, '0', 'CREATE_INCIDENT');

        return $last_id;
    }


    public static function insert_vulnerability($conn, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity)
    {
        $in_charge   = ($transferred_user != '') ? $transferred_user : $transferred_entity;
        $in_charge   = ($in_charge != '')        ? $in_charge : Session::get_session_user();

        $description = ( mb_detect_encoding($description." ",'UTF-8,ISO-8859-1') == 'ISO-8859-1') ? mb_convert_encoding($description, 'UTF-8', 'ISO-8859-1') : $description;
        $description = Util::htmlentities($description, ENT_QUOTES, 'UTF-8');

        $last_id = self::__insert($conn, $title, $type, $submitter, $priority, "Vulnerability", '', '', $in_charge);

        $query = "INSERT INTO incident_vulns
                (id, incident_id, ip, port, nessus_id, risk, description)
                VALUES (?,?, ?, ?, ?, ?, ?)";

        $vuln_entry_id = $conn->GenID('incident_vulns_seq');

        $params = array(
            $vuln_entry_id,
            $last_id,
            $ip,
            $port,
            $nessus_id,
            $risk,
            $description
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Send email automatically
        Incident_ticket::mail_subscription($conn, $last_id, '0', 'CREATE_INCIDENT');

        return $last_id;
    }


    public static function insert_custom($conn, $title, $type, $submitter, $priority, $transferred_user, $transferred_entity, $fields = array())
    {
        $in_charge = ($transferred_user != '') ? $transferred_user : $transferred_entity;
        $in_charge = ($in_charge != '')        ? $in_charge : Session::get_session_user();

        $last_id = self::__insert($conn, $title, $type, $submitter, $priority, 'Custom', '', '', $in_charge);

        foreach ($fields as $value)
        {
            if(is_array($value["content"]))
            {
                $value["content"] = serialize($value["content"]);
            }

            $query = "INSERT INTO incident_custom (incident_id, name, incident_custom_type_id, content) VALUES (?, ?, ?, ?)";

            $params = array(
                $last_id,
                $value["name"],
                $value["type"],
                $value["content"]
            );

            if (!$conn->Execute($query, $params))
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        // Send email automatically
        Incident_ticket::mail_subscription($conn, $last_id, '0', 'CREATE_INCIDENT');

        return $last_id;
    }


    public static function insert_metric($conn, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity)
    {
        $in_charge = ( $transferred_user != '' ) ? $transferred_user : $transferred_entity;
        $in_charge = ( $in_charge != '')         ? $in_charge : Session::get_session_user();

        $last_id   = self::__insert($conn, $title, $type, $submitter, $priority, "Metric", $event_start, $event_end, $in_charge);

        $query = "INSERT INTO incident_metric " . "(incident_id, target, metric_type, metric_value) " . "VALUES (?, ?, ?, ?)";
        $params = array(
            $last_id,
            $target,
            $metric_type,
            $metric_value
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Send email automatically
        Incident_ticket::mail_subscription($conn, $last_id, '0', 'CREATE_INCIDENT');

        return $last_id;
    }


    public static function insert_subscription($conn, $incident_id, $login)
    {
        ossim_valid($incident_id, OSS_DIGIT, 'illegal:' . _("ID"));
        ossim_valid($login, OSS_USER,        'illegal:' . _("Login"));

        if (ossim_error())
        {
            die(ossim_error());
        }

        // Check if he's already subscribed
        $query = "SELECT login FROM incident_subscrip WHERE login=? AND incident_id=?";

        if (!$rs = $conn->Execute($query, array($login, $incident_id)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return FALSE;
        }

        $query = "INSERT INTO incident_subscrip VALUES (?, ?)";

        if (!$conn->Execute($query, array($login, $incident_id)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    public function __update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge)
    {
        $tz = Util::get_timezone();

        if($tz != 0)
        {
            if(empty($event_start))
            {
                $event_start = gmdate("Y-m-d H:i:s");
            }
            else
            {
                list ($y,$m,$d,$h,$u,$s,$time) = Util::get_utc_from_date($conn, $event_start, $tz);

                $event_start = "$y-$m-$d $h:$u:$s";
            }

            if(empty($event_end))
            {
                $event_end = gmdate("Y-m-d H:i:s");
            }
            else
            {
                list ($y,$m,$d,$h,$u,$s,$time) = Util::get_utc_from_date($conn, $event_end, $tz);
                $event_end = "$y-$m-$d $h:$u:$s";
            }
        }
        else
        {
            if (empty($event_start))
            {
                $event_start = "now";
            }

            if (empty($event_end))
            {
                $event_end   = "now";
            }

            $event_start = date('Y-m-d H:i:s', strtotime($event_start));
            $event_end   = date('Y-m-d H:i:s', strtotime($event_end));
        }

        $query = "UPDATE incident SET " . "title = ?, type_id = ?, in_charge = ?, submitter = ?, priority = ?, date = date, event_start = ?, event_end = ? " . "WHERE id = ?";

        $params = array(
            $title,
            $type,
            $in_charge,
            $submitter,
            $priority,
            $event_start,
            $event_end,
            $incident_id
        );


        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public function update_alarm($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports,
        $event_start, $event_end, $transferred_user, $transferred_entity)
    {
        //Get user in charge
        $incident = self::get_list($conn, "AND incident.id='$incident_id'");

        if (count($incident) > 0)
        {
            $old_in_charge = $incident[0]->get_in_charge();
        }

        $in_charge = ($transferred_user != '') ? $transferred_user : $transferred_entity;

        self::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);

        $query = "UPDATE incident_alarm " . "SET src_ips = ?, dst_ips = ?, src_ports = ?, dst_ports = ? " . "WHERE incident_id = ?";

        $params = array(
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports,
            $incident_id
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Send email automatically if user (in charge) has changed
        if ($in_charge != $old_in_charge)
        {
            Incident_ticket::mail_subscription($conn, $incident_id, '0', 'UPDATE_INCIDENT');
        }

        return TRUE;
    }


    public function update_event($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports,
         $event_start, $event_end, $transferred_user, $transferred_entity)
    {

        //Get user in charge
        $incident = self::get_list($conn, "AND incident.id='$incident_id'");

        if (count($incident) > 0)
        {
            $old_in_charge = $incident[0]->get_in_charge();
        }

        $in_charge = ($transferred_user != '') ? $transferred_user : $transferred_entity;

        self::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);

        $query = "UPDATE incident_event " . "SET src_ips = ?, dst_ips = ?, src_ports = ?, dst_ports = ? " . "WHERE incident_id = ?";

        $params = array(
            $src_ips,
            $dst_ips,
            $src_ports,
            $dst_ports,
            $incident_id
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Send email automatically if user (in charge) has changed
        if ($in_charge != $old_in_charge)
        {
            Incident_ticket::mail_subscription($conn, $incident_id, '0', 'UPDATE_INCIDENT');
        }

        return TRUE;
    }


    public static function update_custom($conn, $incident_id, $title, $type, $submitter, $priority, $transferred_user, $transferred_entity, $fields = array())
    {
        $fields = (empty($fields)) ? array() : $fields;

        //Get user in charge
        $incident = self::get_list($conn, "AND incident.id='$incident_id'");

        if (count($incident) > 0)
        {
            $old_in_charge = $incident[0]->get_in_charge();
        }

        $in_charge  = ($transferred_user != '') ? $transferred_user : $transferred_entity;

        $event_start = date('Y-m-d H:i:s', strtotime('now'));
        $event_end   = date('Y-m-d H:i:s', strtotime('now'));

        self::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);


        foreach ($fields as $value)
        {
            $conn->Execute("UPDATE incident_custom set content = '' WHERE name = ? and incident_id = ?", array($value["name"], $incident_id));

            $query     = "UPDATE incident_custom set content = ? WHERE name = ? and incident_id = ?";
            $content = (is_array($value["content"])) ? serialize($value["content"]) : $value["content"];

            $params = array( $content, $value["name"], $incident_id);

            if (!$conn->Execute($query, $params))
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            if (!$conn->Affected_Rows()) // New custom field with value
            {
                $query  = "INSERT INTO incident_custom (incident_id, name, incident_custom_type_id, content) VALUES (?, ?, ?, ?)";
                $params = array(
                    $incident_id,
                    $value["name"],
                    $value["type"],
                    (is_array($value["content"])) ? serialize($value["content"]) : $value["content"]
                );

                if (!$conn->Execute($query, $params))
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
            }
        }

        //Send email automatically if user (in charge) has changed
        if ($in_charge != $old_in_charge)
        {
            Incident_ticket::mail_subscription($conn, $incident_id, '0', 'UPDATE_INCIDENT');
        }

        return TRUE;
    }


    public static function update_metric($conn, $incident_id, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value,
        $event_start, $event_end, $transferred_user, $transferred_entity)
    {
        //Get user in charge
        $incident = self::get_list($conn, "AND incident.id='$incident_id'");

        if (count($incident) > 0)
        {
            $old_in_charge = $incident[0]->get_in_charge();
        }

        $in_charge = ($transferred_user != '') ? $transferred_user : $transferred_entity;

        self::__update($conn, $incident_id, $title, $type, $submitter, $priority, $event_start, $event_end, $in_charge);

        $query = "UPDATE incident_metric " . "SET target = ?, metric_type = ?, metric_value = ? " . "WHERE incident_id = ?";

        $params = array(
            $target,
            $metric_type,
            $metric_value,
            $incident_id
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Send email automatically if user (in charge) has changed
        if ($in_charge != $old_in_charge)
        {
            Incident_ticket::mail_subscription($conn, $incident_id, '0', 'UPDATE_INCIDENT');
        }

        return TRUE;
    }


    public static function update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, $anom_type, $ip, $data_orig, $data_new,
        $transferred_user, $transferred_entity)
    {
        $s_data_orig = implode(",", $data_orig);
        $s_data_new  = implode(",", $data_new);

        //Get user in charge
        $incident = self::get_list($conn, "AND incident.id='$incident_id'");

        if (count($incident) > 0)
        {
            $old_in_charge = $incident[0]->get_in_charge();
        }

        $in_charge   = ($transferred_user != '') ? $transferred_user : $transferred_entity;

        self::__update($conn, $incident_id, $title, $type, $submitter, $priority, '', '', $in_charge);


        $query = "UPDATE incident_anomaly " . "SET anom_type = ?, ip = ?, data_orig = ?, data_new = ? " . "WHERE incident_id = ?";

        $params = array(
            $anom_type,
            $ip,
            $s_data_orig,
            $s_data_new,
            $incident_id
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Send email automatically if user (in charge) has changed
        if ($in_charge != $old_in_charge)
        {
            Incident_ticket::mail_subscription($conn, $incident_id, '0', 'UPDATE_INCIDENT');
        }

        return TRUE;
    }


    public static function update_vulnerability($conn, $incident_id, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk,
       $description, $transferred_user, $transferred_entity)
    {

        //Get user in charge
        $incident = self::get_list($conn, " AND incident.id='$incident_id'");

        if (count($incident) > 0)
        {
            $old_in_charge = $incident[0]->get_in_charge();
        }

        $in_charge   = ($transferred_user != '') ? $transferred_user : $transferred_entity;

        $description = (mb_detect_encoding($description." ",'UTF-8,ISO-8859-1') == 'ISO-8859-1') ? mb_convert_encoding($description, 'UTF-8', 'ISO-8859-1') : $description;
        $description = Util::htmlentities($description, ENT_QUOTES, 'UTF-8');

        self::__update($conn, $incident_id, $title, $type, $submitter, $priority, '', '', $in_charge);

        $query = "UPDATE incident_vulns " . "SET ip = ?, port = ?, nessus_id = ?, risk = ?, description = ? " . "WHERE incident_id = ?";

        $params = array(
            $ip,
            $port,
            $nessus_id,
            $risk,
            $description,
            $incident_id
        );

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Send email automatically if user (in charge) has changed
        if ($in_charge != $old_in_charge)
        {
            Incident_ticket::mail_subscription($conn, $incident_id, '0', 'UPDATE_INCIDENT');
        }

        return TRUE;
    }


    public static function delete($conn, $id)
    {
        if (!ossim_valid($id, OSS_DIGIT))
        {
            $exp_msg = _('Invalid ticket ID');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if (!is_array($id)) {
            $id = array($id);
        }

	    $in = implode(",",$id);
        $conn->StartTrans();

        $conn->Execute("DELETE FROM incident_alarm     WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_anomaly   WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_custom    WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_event     WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_file      WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_metric    WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_subscrip  WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_tag       WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_vulns     WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident_ticket    WHERE incident_id in ($in)");
        $conn->Execute("DELETE FROM incident           WHERE id in ($in)");
        $conn->Execute("DELETE FROM incident_tmp_email WHERE incident_id in ($in)");

        $_result = $conn->CompleteTrans();

        if (!$_result)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        $files = array();
        foreach ($id as $ticket_id) {
                $files = array_merge($files,glob("/usr/share/ossim/www/uploads/Incident-$ticket_id-*-*.*"));
        }
        array_map('unlink', $files);

        $user_name    = Session::get_session_user();
        $infolog = array($in,$user_name);
        Log_action::log(16, $infolog);
    }


    /*
     * Deletes all the subscriptions associated with an Incident Id.
     * If you supply the $login, only that subscription will be removed
     */
    public static function delete_subscriptions($conn, $incident_id, $login = '')
    {
        $params[] = $incident_id;
        $query = "DELETE FROM incident_subscrip WHERE incident_id = ?";

        if ($login)
        {
            $query   .= " AND login=?";
            $params[] = $login;
        }

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /*
     * @deprecated
     */
    public function incident_search($conn, $query_string)
    {
        $search_array = array(
            "incident_ticket" => array(
                "return_column" => "incident_id",
                "check_columns" => array(
                    "date",
                    "users",
                    "description",
                    "action",
                    "in_charge",
                    "transferred",
                    "copy"
                )
            ),
            "incident" => array(
                "return_column" => "id",
                "check_columns" => array(
                    "title",
                    "date",
                    "ref",
                    "type_id"
                )
            ),
            "incident_alarm" => array(
                "return_column" => "incident_id",
                "check_columns" => array(
                    "src_ips",
                    "dst_ips",
                    "src_ports",
                    "dst_ports"
                )
            ),
            "incident_event" => array(
                "return_column" => "incident_id",
                "check_columns" => array(
                    "src_ips",
                    "dst_ips",
                    "src_ports",
                    "dst_ports"
                )
            )
        );
        $qs = $query_string; //shorter

        foreach(array_keys($search_array) as $table)
        {
            $query = "SELECT " . $search_array[$table]["return_column"] . " FROM " . $table . " WHERE ";

            foreach($search_array[$table]["check_columns"] as $column)
            {
                $query.= $column . " LIKE \"%" . $qs . "%\" OR ";
            }

            $query = ossim_query(substr($query, 0, strlen($query) - 4));
            $rs    = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                    $list[] = $rs->fields[$search_array[$table]["return_column"]];
                    $rs->MoveNext();
                }
            }
        }

        if (is_array($list))
        {
            // Avoid duplicates, nice trick found on the array_unique() manual
            // page
            $list = array_flip(array_flip($list));
            $where = " AND incident.id = " . array_pop($list) . " ";

            foreach($list as $incident_id)
            {
                $where.= "OR incident.id = \"$incident_id\" ";
            }

            $incident_list = self::get_list($conn, $where);
        }

        return $incident_list;
    }


    /* Incident statistics */
    public static function incidents_by_type($conn, $assets = '', $user = '', $args = NULL)
    {
        $tp_accumulated = array();

        $args =  $args." AND incident.status!='Closed' ORDER BY incident.status DESC";

        $incident_list  = self::get_list_filtered($conn, $assets, $args, $user);

        if (is_array($incident_list) && !empty($incident_list))
        {
            foreach($incident_list as $incident)
            {
                $type                  = $incident->get_type();
                $tp_accumulated[$type] = (array_key_exists($type, $tp_accumulated)) ? $tp_accumulated[$type]+1 : 1;
            }
        }

        return $tp_accumulated;
    }


    public static function tags_incidents($conn)
    {
        $query = "SELECT id, name FROM incident_tag_descr";

        $rs = $conn->Execute(ossim_query($query));
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $tags = array();

        while (!$rs->EOF)
        {
            $tags[$rs->fields["id"]]=$rs->fields["name"];

            $rs->MoveNext();
        }

        return $tags;
    }


    public static function get_id_by_tag($conn, $name)
    {
        $query = "SELECT id FROM incident_tag_descr where name='$name'";
        $rs  = $conn->Execute(ossim_query($query));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            return $rs->fields["id"];
        }
    }


    public static function incidents_by_tag($conn, $assets = '', $user = '', $args = NULL, $tags = NULL)
    {
        $args           = $args." AND incident.status!='Closed' ";
        $incident_list  = self::get_list_filtered($conn, $assets, $args, $user);
        $ids            = '';

        if($tags != NULL && is_array($tags))
        {
            $tag_clausule = " AND incident_tag.tag_id in (";

            foreach($tags as $t)
            {
                $tag_clausule .= "$t,";
            }

            $tag_clausule = substr($tag_clausule , 0, -1);
            $tag_clausule .= ")";
        }
        else
        {
            $tag_clausule = '';
        }

        if (is_array($incident_list) && !empty($incident_list))
        {
            foreach($incident_list as $incident)
            {
                $ids .= $incident->get_id().", ";
            }
        }

        $ids = preg_replace('/\,\s$/','',$ids);
        $ids = empty($ids) ? "''": $ids;

        $query = "
            SELECT incident_tag_descr.name AS name, count(incident_tag.incident_id) AS count
            FROM
                incident_tag_descr
                    INNER JOIN incident_tag ON incident_tag_descr.id=incident_tag.tag_id
                    INNER JOIN incident ON incident_tag.incident_id=incident.id $tag_clausule
            WHERE incident.status != 'Close' AND
                  incident.id in (".$ids.")
            GROUP BY incident_tag.tag_id
            ORDER BY count DESC";

        $query = ossim_query($query);

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $tags = array();

        while (!$rs->EOF)
        {
            $tags[$rs->fields["name"]]=$rs->fields["count"];

            $rs->MoveNext();
        }

        return $tags;
    }


    public static function incidents_by_class($conn, $assets = '', $user = '', $args = NULL)
    {
        $class_accumulated  = array();

        $args =  $args." AND incident.status!='Closed' ORDER BY incident.status DESC";

        $incident_list = self::get_list_filtered($conn, $assets, $args, $user);

        if (is_array($incident_list) && !empty($incident_list))
        {
            foreach($incident_list as $incident)
            {
                $class                     = $incident->get_ref();
                $class_accumulated[$class] = (array_key_exists($class, $class_accumulated)) ? $class_accumulated[$class] + 1 : 1;
            }
        }

        return $class_accumulated;
    }


    public static function incidents_by_resolution_time($conn, $assets = '', $user = '', $args = NULL)
    {
        // Gets tags
        $tags    = array();
        $t_sql   = "SELECT incident_tag.tag_id, incident_tag.incident_id FROM incident_tag";

        if (!$rs = $conn->Execute($t_sql))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $tags[$rs->fields["incident_id"]][] = $rs->fields["tag_id"];
            $rs->MoveNext();
        }

        $args =  $args. " AND incident.status='Closed' ORDER BY incident.status DESC";

        $incident_list = self::get_list_filtered($conn, $assets, $args, $user);

        if (is_array($incident_list) && !empty($incident_list))
        {
            foreach($incident_list as $incident)
            {
                $incident->_life_time_diff = strtotime($incident->get_date()) - strtotime($incident->get_last_modification($conn));
                $incident->tags            = (isset($tags[$incident->get_id()])) ? $tags[$incident->get_id()] : array();
            }
        }

        return $incident_list;
    }


    public static function incidents_by_type_descr($conn, $args = NULL)
    {
        $tdescr_accumulated = array();
        $incident_list      = self::get_list_type_descr($conn, $args);

        if (is_array($incident_list) && !empty($incident_list))
        {
            foreach($incident_list as $incident)
            {
                $type_descr                      = $incident->get_type_descr();
                $tdescr_accumulated[$type_descr] = (array_key_exists($type_descr, $tdescr_accumulated)) ? $tdescr_accumulated[$type_descr]++ : 1;
            }
        }

        arsort($tdescr_accumulated);

        return $tdescr_accumulated;
    }


    public static function incidents_by_status($conn, $assets = '', $user = '', $args = NULL)
    {
        $st_accumulated = array();

        $args =  $args. " ORDER BY incident.status DESC";

        $incident_list  = self::get_list_filtered($conn, $assets, $args, $user);

        if (is_array($incident_list) && !empty($incident_list))
        {
            $st_accumulated = array();

            foreach($incident_list as $incident)
            {
                $status = $incident->get_status();
                if(!array_key_exists($status, $st_accumulated))
                {
                    $st_accumulated[$status]= 1;
                }
                else
                {
                    $st_accumulated[$status]+= 1;
                }
            }
        }

        return $st_accumulated;
    }


    public static function incidents_by_date($conn, $date_range, $assets = '', $user = '', $args = '')
    {
        $tzc = Util::get_tzc();

        $incident_list = array();

        if (is_array($date_range))
        {
            $args .= " AND convert_tz(incident.date,'+00:00','".$tzc."') between ".$date_range[0]." AND ".$date_range[1];
        }
        elseif (!empty($date_range))
        {
            $args .= " AND convert_tz(incident.date,'+00:00','".$tzc."') >= ".$date_range;
        }

        $incident_list = self::get_list_filtered($conn, $assets, $args, $user);

        return count($incident_list);
    }


    public static function incidents_by_user($conn, $open_only = TRUE, $assets = '', $user = '', $args = NULL)
    {
        $user_list  = array();
        $pro        = Session::is_pro();

        if ($open_only)
        {
            $args = $args." AND incident.status != 'Closed'";
        }

        $incident_list = self::get_list_filtered($conn, $assets, $args, $user);

        if (is_array($incident_list) && !empty($incident_list))
        {
            $list_aux = array();

            foreach($incident_list as $incident)
            {
                $in_charge  = $incident->get_in_charge();
                $list_aux[$in_charge]++;
            }

            foreach($list_aux as $name => $value)
            {
                $name             = ($pro && valid_hex32($name)) ? Acl::get_entity_name($conn, $name) : $name;
                $user_list[$name] = $value;
            }

            arsort($user_list);
        }

        return $user_list;
    }


    public static function incidents_by_priority($conn, $assets = '', $user = '', $args = NULL)
    {
        $prio_accumulated = array();

        $args =  $args. " AND incident.status!='Closed' ORDER BY status DESC";

        $incident_list = self::get_list_filtered($conn, $assets, $args, $user);

        if (is_array($incident_list) && !empty($incident_list))
        {
            $prio_accumulated = array(
                "0"  => 0,
                "1"  => 0,
                "2"  => 0,
                "3"  => 0,
                "4"  => 0,
                "5"  => 0,
                "6"  => 0,
                "7"  => 0,
                "8"  => 0,
                "9"  => 0,
                "10" => 0
            );

            foreach($incident_list as $incident)
            {
                $priority                    = $incident->get_priority();
                $prio_accumulated[$priority] = $prio_accumulated[$priority] + 1;
            }
        }

        return $prio_accumulated;
    }


    public static function get_all_types($conn, $all = FALSE)
    {
        $list = array();

        $query = ($all) ? "select id as type_id from incident_type" : "select distinct type_id from incident";

        if (!$rs = $conn->Execute(ossim_query($query)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $list[] = $rs->fields["type_id"];
            $rs->MoveNext();
        }

        return $list;
    }


    public static function incidents_by_type_per_month($conn, $assets = '', $user = '')
    {
        $et_by_month  = NULL;
        $months       = NULL;
        $event_types  = array();
        $list         = self::get_all_types($conn, TRUE);

        foreach($list as $type)
        {
            $event_types[$type]  = "incident.type_id='$type'";
        }

        $tzc = Util::get_tzc();

        for ($i = 11; $i>=0 ; $i--)
        {
            $time    = strtotime("-".$i." months");

            $m_l     = date("F", $time);
            $y       = date("Y", $time);

            $time    = strtotime("next month $m_l $y - 1 hour");

            $y_s     = date("y", $time);
            $m       = date("m", $time);
            $m_s     = date("M", $time);

            $months["$m_s-$y_s"] = "month(convert_tz(incident.date,'+00:00','$tzc'))=$m AND year(convert_tz(incident.date,'+00:00','$tzc'))=$y";
        }

        foreach($event_types as $et => $query_et)
        {
            $data  = NULL;

            foreach($months as $m => $m_query)
            {
                $args  = "AND incident.status!='Closed' AND $query_et AND $m_query";
                $incident_list = self::get_list_filtered($conn, $assets, $args, $user);
                $data[$m]      = count($incident_list);
            }

            $et_by_month[$et] = $data;
        }

        return $et_by_month;
    }


    public static function incidents_closed_by_month($conn, $assets = '', $user = '')
    {
        $et_by_month  = NULL;
        $months       = NULL;

        $event_types  = array(
            "Alarm"          => 'incident.ref="Alarm"',
            "Custom"         => 'incident.ref="Custom"',
            "Event"          => 'incident.ref="Event"',
            "Anomaly"        => 'incident.ref="Anomaly"',
            "Vulnerability"  => 'incident.ref="Vulnerability"'
        );

        $tzc = Util::get_tzc();

        for ($i = 11; $i>=0 ; $i--)
        {
            $time = strtotime("-".$i." months");

            $m_l  = date("F", $time);
            $y    = date("Y", $time);

            $time = strtotime("next month $m_l $y - 1 hour");

            $y_s  = date("y", $time);
            $m    = date("m", $time);
            $m_s  = date("M", $time);
            //$d       = date("d", $time);

            //$months["$m_s-$y_s"] = "date >= \"$y-$m-01 00:00:00\" and date <= \"$y-$m-$d 23:59:59\'';
            $months["$m_s-$y_s"] = "month(convert_tz(incident.date,'+00:00','$tzc'))=$m AND year(convert_tz(incident.date,'+00:00','$tzc'))=$y";
        }

        foreach($event_types as $et => $query_et)
        {
            $data = NULL;

            foreach($months as $m => $m_query)
            {
                $args  = array("where" => "AND incident.status='Closed' AND $query_et AND $m_query");

                $incident_list = self::get_list_filtered($conn, $assets, $args, $user);
                $data[$m]      = count($incident_list);
            }

            $et_by_month[$et] = $data;
        }

        return $et_by_month;
    }


    public static function chk_status($status)
    {
        return in_array((string)$status, array('Open', 'Assigned', 'Studying', 'Waiting', 'Testing', 'Closed'));
    }


    public static function format_custom_field($id, $incident_id, $value, $type)
    {
        switch ($type)
        {
            case "Map":
                $unique_id = md5(uniqid());

                $value = (empty($value)) ? _('Not available') : $value;

                $field  = "<span style='font-weight: bold; padding-right: 10px;'>Location:</span><span>".$value."</span>
                           <div id='".$unique_id."' style='margin: 10px auto; height:200px; width:100%;'></div>
                           <script type='text/javascript'>
                               av_map_".$unique_id." = new Av_map('".$unique_id."');

                               Av_map.is_map_available(function(conn)
                               {
                                   if(conn)
                                   {
                                        av_map_".$unique_id.".draw_map();

                                        av_map_".$unique_id.".map.setOptions({draggable: false});

                                        // Set drap property to false

                                        google.maps.event.addListener(av_map_".$unique_id.".map, 'bounds_changed', function(){

                                            //Set draggable property to false
                                            if (Object.keys(av_map_".$unique_id.".markers).length > 0)
                                            {
                                                av_map_".$unique_id.".markers[0].setDraggable(false);
                                                av_map_".$unique_id.".markers[0].setMap(av_map_".$unique_id.".map);

                                                av_map_".$unique_id.".set_zoom(4);

                                                //Remove event handler
                                                google.maps.event.clearListeners(av_map_".$unique_id.".map, 'bounds_changed');
                                            }
                                        });

                                        av_map_".$unique_id.".set_location_by_address('".$value."');
                                   }
                                   else
                                   {
                                        av_map_".$unique_id.".draw_warning();
                                   }
                                });
                           </script>";
            break;

            case "Asset":
                $field = $value;

                $conf    = $GLOBALS['CONF'];
                $version = $conf->get_conf('ossim_server_version');

                if (preg_match("/pro|demo/i",$version) && preg_match("/ENTITY:\s*([a-f0-9]{32})/i",$value,$found))
                {
                    $db          = new ossim_db();
                    $conn        = $db->connect();

                    $entity_name = Acl::get_entity_name($conn,$found[1]);
                    $field       = "ENTITY: ". $entity_name;

                    $db->close();
                }

            break;

            case "File":

                if (empty($value))
                {
                    $field = NULL;
                }
                else
                {
                    $field  = (preg_match('/^File not uploaded. Error:/', $value)) ? "<span style='color:red;'>$value</span>" : "<a href='incident_download_content.php?id=$id&incident_id=$incident_id'>"._("Download content")."</a>";
                }

            break;

            case "Checkbox":
                $field = (unserialize($value) == FALSE) ? $value : implode(", ", unserialize($value));
            break;

            default:
                $field = Util::htmlentities($value); //Do not delete the htmlentities!!
        }

        return $field;
    }


    public static function insert_incident_tag($conn, $incident_id, $tag_id)
    {
        $query  = "INSERT IGNORE INTO incident_tag(tag_id, incident_id) VALUES (?,?)";
        $params = array($tag_id, $incident_id);

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    public static function delete_incident_tags($conn, $incident_id)
    {
        $query  = "DELETE FROM incident_tag WHERE incident_id = ?";
        $params = array($incident_id);

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }
}

/* End of file incident.inc */
/* Location: ../include/classes/incident.inc */
