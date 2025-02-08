<?php
/**
* server.inc
*
* File server.inc is used to:
*   - To manage AlienVault Servers
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
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Server 
{
    public $id;


    public $name;


    public $ip;


    public $port;


    public $descr;


    public $remoteadmin;


    public $remotepass;


    public $remoteurl;
    

    public function __construct($id, $name, $ip, $port, $descr, $remoteadmin, $remotepass, $remoteurl, $foundrows = 0) 
    {
        $this->id          = $id;
        $this->name        = stripslashes($name);
        $this->ip          = $ip;
        $this->port        = $port;
        $this->descr       = $descr;
        $this->remoteadmin = $remoteadmin;
        $this->remotepass  = $remotepass;
        $this->remoteurl   = $remoteurl;
        $this->foundrows   = $foundrows;
    }


    public function get_id() 
    {
        return $this->id;
    }


    public function get_name() 
    {
        return $this->name;
    }


    public function get_ip() 
    {
        return $this->ip;
    }
    
    
    public function get_port() 
    {
        return $this->port;
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
    
    
    public function get_remoteadmin() 
    {
        return $this->remoteadmin;
    }

    
    public function get_remotepass() 
    {
        return $this->remotepass;
    }
    
    
    public function get_remoteurl() 
    {
        return $this->remoteurl;
    }


    public function get_foundrows() 
    {
        return $this->foundrows;
    }


    public function set_foundrows($val) 
    {
        $this->foundrows = $val;
    }


    public static function get_object($conn, $id) 
    {
        Ossim_db::check_connection($conn);

        $object = NULL;
        $uuid   = Util::get_encryption_key();
                
        $query = "SELECT *, HEX(id) AS id, HEX(ip) AS ip, AES_DECRYPT(remotepass, '$uuid') AS pass_decrypt 
            FROM server WHERE id = UNHEX(?)";
        
        $rs = $conn->Execute($query, array($id));
        
        if (!$rs) 
        {
            return $object;
        }

        if (!$rs->EOF) 
        {
            $object = new self($rs->fields['id'],
                $rs->fields['name'], 
                inet_ntop (pack ('H*', $rs->fields ['ip'])),
                $rs->fields['port'], 
                $rs->fields['descr'], 
                $rs->fields['remoteadmin'], 
                (empty($rs->fields['pass_decrypt']) ) ? $rs->fields['remotepass'] : $rs->fields['pass_decrypt'], 
                $rs->fields['remoteurl'], 
                1);
        }

        return $object;
    }


    public static function get_list($conn, $args = '') 
    {
        Ossim_db::check_connection($conn);

        $list = array();

        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;
        $uuid        = Util::get_encryption_key();

        if ($iffoundrows == TRUE)
        {
            $select = "SQL_CALC_FOUND_ROWS *, HEX(id) AS id, HEX(ip) AS ip, AES_DECRYPT(remotepass,'$uuid') AS pass_decrypt";
        }
        else
        {
            $select = "*, HEX(id) AS id, HEX(ip) AS ip, AES_DECRYPT(remotepass,'$uuid') AS pass_decrypt";
        }

        $query = ossim_query("SELECT $select FROM server $args");

        $rs = $conn->Execute($query);

        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $foundrows = 0;
            if ($iffoundrows == TRUE) 
            {
                $rf = $conn->Execute('SELECT FOUND_ROWS() AS total');
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
                $list[] = new self($rs->fields['id'],
                    $rs->fields['name'],
                    @inet_ntop(pack('H*', $rs->fields ['ip'])),
                    $rs->fields['port'], 
                    $rs->fields['descr'], 
                    $rs->fields['remoteadmin'], 
                    (empty($rs->fields['pass_decrypt'])) ? $rs->fields['remotepass'] : $rs->fields['pass_decrypt'], 
                    $rs->fields['remoteurl'], 
                    $foundrows);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function get_role($conn, $args = '') 
    {
        Ossim_db::check_connection($conn);

        $list = array();

        $query = ossim_query("SELECT server_role.*,ip FROM server_role LEFT JOIN server ON server.id=server_role.server_id $args");

        $rs = $conn->Execute($query);

        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $properties = array(
                    'correlate'        => $rs->fields["correlate"],
                    'cross_correlate'  => $rs->fields["cross_correlate"],
                    'store'            => $rs->fields["store"],
                    'qualify'          => $rs->fields["qualify"],
                    'resend_alarm'     => $rs->fields["resend_alarm"],
                    'resend_event'     => $rs->fields["resend_event"],
                    'sign'             => $rs->fields["sign"],
                    'sem'              => $rs->fields["sem"],
                    'sim'              => $rs->fields["sim"],
                    'reputation'       => $rs->fields["reputation"],
                    'alarms_to_syslog' => $rs->fields["alarms_to_syslog"]
                );

                $list[] = new Role($rs->fields['name'], $properties);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function get_my_hierarchy($conn, $uuid) 
    {
        Ossim_db::check_connection($conn);

        $list   = array();

        $params = array($uuid);
        $query  = "SELECT HEX(sfr.server_dst_id) AS id, sfr.priority, s.name, s.ip 
            FROM server_forward_role sfr, server s 
            WHERE sfr.server_dst_id = s.id AND sfr.server_src_id = UNHEX(?)";

        $rs = $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF) 
        {
            $list[$rs->fields['id']] = array(
                $rs->fields['name'],
                $rs->fields["ip"],
                $rs->fields["priority"]
            );

            $rs->MoveNext();
        }

        return $list;
    }


    public static function server_hierarchy($conn, &$exclude, $uuid) 
    {
        Ossim_db::check_connection($conn);

        $exclude[$uuid] = $uuid;    

        $params = array($uuid);

        //get parents with uuid like a child
        $query = "SELECT HEX(child_id) AS id FROM server_hierarchy WHERE parent_id = UNHEX(?)";

        if (!$rs = $conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF) 
        {
            if($exclude[$rs->fields['id']])
            {
                $rs->MoveNext();
                
                continue;
            }

            $exclude[$rs->fields['id']] = $rs->fields['id'];

            self::server_hierarchy($conn, $exclude, $rs->fields['id']);
                        
            $rs->MoveNext();
        }
    }


    public static function get_child_servers($conn, $uuid) 
    {
        Ossim_db::check_connection($conn);

        $list    = array();
        $query   = "SELECT HEX(child_id) AS id FROM server_hierarchy WHERE parent_id = UNHEX(?) AND child_id != UNHEX(?)";
        $params  = array($uuid, $uuid);

        if (!$rs = $conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF) 
        {        
            $server = self::get_object($conn, $rs->fields['id']);

            if (is_object($server) && $server->get_name()!='')
            {
                $list[$rs->fields['id']] = $server->get_name();
            }

            $rs->MoveNext();
        }

        return $list;
    }

    public static function get_server_by_ctx($conn, $uuid) 
	{
        Ossim_db::check_connection($conn);
        
        $server  = FALSE;
		$query   = "SELECT HEX(server_id) AS id FROM acl_entities WHERE id=UNHEX(?)";
		$params  = array($uuid);
		
		$rs = $conn->Execute($query, $params);
		if (!$rs) 
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}		
		else
		{
    		$server = self::get_object($conn, $rs->fields['id']);
		}    		
		
        return $server;		
    }


    public static function get_server_hierarchy($conn, $uuid) 
    {
        Ossim_db::check_connection($conn);

        $list    = array();
        $exclude = array();
        
        self::server_hierarchy($conn, $exclude, $uuid);
        
        $exclude = array_map(function($v){ return "UNHEX('$v')";}, $exclude);
        
        $all     = self::get_list($conn, ' WHERE id NOT IN ('.implode(',', $exclude).')');

        foreach($all as $s)
        {
            $list[$s->get_id()] = array(
                'id'   => $s->get_id(),
                'name' => $s->get_name(),
                'ip'   => $s->get_ip()
            );
        }
        return $list;
    }


    public static function get_all($conn) 
    {
        Ossim_db::check_connection($conn);

        $list = array();
        $query = 'SELECT *, HEX(id) AS id FROM server ORDER BY name ASC';
        
        if (!$rs = $conn->Execute($query)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $list[] = new Server($rs->fields['id'], $rs->fields['name'], $rs->fields['ip'], $rs->fields['port'], $rs->fields['descr']);
            
            $rs->MoveNext();
        }

        return $list;
    }


    public static function set_remote_sshkey($remoteadmin, $remotepass, $remoteurl)
    {
        $data = array(
            'status' => 'success',
            'data'   => ''
        );

        $conf = $GLOBALS['CONF'];
        $conf = (!$conf) ? new Ossim_conf() : $conf;
        $remotekey = $conf->get_conf('remote_key');
        if (!$remotekey) {
            return $data = array(
                'status' => 'error',
                'data'   => sprintf(_("Remote key can't be empty, please set it in ADMINISTRATION/MAIN/LOGIN METHODS/OPTIONS/REMOTE LOGIN KEY."), $remoteurl)
            );
        }

        // Create public key in local machine
        if (!file_exists("/etc/ossim/framework/ssh/remotelogger.pub"))
        {
            Util::execute_command("ssh-keygen -q -t rsa -N '' -C remotelogger -f /etc/ossim/framework/ssh/remotelogger");
        }

        $publickey_output = file("/etc/ossim/framework/ssh/remotelogger.pub");
        $publickey = trim($publickey_output[0]);
        $keyparam  = base64_encode(Util::encrypt($remoteadmin.'####'.$remotepass.'####'.$publickey, $remotekey));

        // Check the remote authorized_keys
        $opts = array(
            'ssl'  => array(
                'verify_peer'      => FALSE,
                'verify_peer_name' => FALSE,
            )
        );

        $context = stream_context_create($opts);
        $output  = file($remoteurl.'/sem/insertsshkey.php?key='.$keyparam,0,$context);

        if (is_array($output))
        {
            if ($output[0] == 'USERERROR')
            {
                $data = array(
                    'status' => 'error',
                    'data'   => _('Unable to configure Remote Logger. Remote admin user is incorrect.')
                );
            }
            elseif ($output[0] == 'VALIDERROR')
            {
                $data = array(
                    'status' => 'error',
                    'data'   => _('Unable to configure Remote Logger. Remote admin user or remote password is incorrect.')
                );
            }
            elseif ($output[0] != 'OK')
            {
                $data = array(
                    'status' => 'error',
                    'data'   => _('Unable to configure Remote Logger. SSH public key is incorrect.')
                );
            }
        }
        else
        {
            $data = array(
                'status' => 'error',
                'data'   => sprintf(_('Unable to connect to %s. SSH public key is incorrect.'), $remoteurl)
            );
        }

        return $data;
    }


    public static function insert($conn, $id, $name, $ip, $port) 
    {
        Ossim_db::check_connection($conn);

        $query = "INSERT INTO server (id, name, ip, port)
                VALUES (UNHEX(?), ?, UNHEX(?), ?)";

        $params = array(
            $id,
            $name,
            bin2hex(inet_pton($ip)),
            $port,
        );
        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );

        Log_action::log(76, $infolog);

        Web_indicator::set_on("Reload_servers");

        Util::set_default_os($conn);

        return $id;
    }


    public static function update($conn, $id, $name, $ip, $port, $descr, $r_correlate, $r_cross_correlate, $r_store, 
        $r_reputation, $r_qualify, $r_resend_alarm, $r_resend_event, $r_sign, $r_sem, $r_sim, $r_alarm_to_syslog, $remoteadmin, 
        $remotepass, $remoteurl, $setssh = FALSE, $rservers = array())
    {
        Ossim_db::check_connection($conn);

        if (Util::is_fake_pass($remotepass))
        {
            $query = "UPDATE server SET name = ?, ip = UNHEX(?), 
                    port = ?, descr = ?, remoteadmin = ?, remoteurl = ? 
                    WHERE id = UNHEX(?)";
            
            $params = array(
                $name,
                bin2hex(inet_pton($ip)),
                $port,
                $descr,
                $remoteadmin,
                $remoteurl,
                $id
            );
        }
        else
        {
            $uuid   = Util::get_encryption_key();
            $q_pass = (!empty($remotepass)) ? "AES_ENCRYPT(?,'$uuid')" : "?";
            
            $query = "UPDATE server SET name = ?, ip = UNHEX(?), 
                    port = ?, descr = ?, remoteadmin = ?, remotepass = $q_pass, remoteurl = ? 
                    WHERE id = UNHEX(?)";

            $params = array(
                $name,
                bin2hex(inet_pton($ip)),
                $port,
                $descr,
                $remoteadmin,
                $remotepass,
                $remoteurl,
                $id
            );
        }

        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $params = array(
            $r_correlate,
            $r_cross_correlate,
            $r_store,
            $r_reputation,
            $r_qualify,
            $r_resend_alarm,
            $r_resend_event,
            $r_sign,
            $r_sem,
            $r_sim,
            $r_alarm_to_syslog,
            $id
        );

        $query = "UPDATE server_role SET correlate = ?, cross_correlate = ?, store = ?, reputation = ?, qualify = ?, resend_alarm = ?, 
            resend_event = ?, sign = ?, sem = ?, sim = ?, alarms_to_syslog = ?
            WHERE server_id = UNHEX(?)";


        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($remoteadmin != '' && $remotepass != '' && $remoteurl != '' && $setssh)
        {
            $data = self::set_remote_sshkey($remoteadmin, $remotepass, $remoteurl);

            if ($data['status'] == 'error')
            {
                $params = array($id);
                $query  = "UPDATE server SET remoteadmin = '', remotepass = '', remoteurl = '' WHERE id = UNHEX(?)";

                if (!$conn->Execute($query, $params))
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                Av_exception::throw_error(Av_exception::USER_ERROR, $data['data']);
            }
        }

        self::update_rservers($conn,$id,$ip,$rservers);

        $infolog = array($name, '[' . $ip . ']');
        Log_action::log(77, $infolog);

        Web_indicator::set_on('Reload_servers');

        Util::set_default_os($conn);
    }

    
    public static function set_role_sem($conn, $id, $r_sem)
    {
        Ossim_db::check_connection($conn);

        $params = array(
            $id,
            $r_sem,
            $r_sem
        );

        $query = "INSERT INTO server_role (server_id, correlate, cross_correlate, store, reputation, qualify, resend_alarm, 
            resend_event, sign, sem, sim, alarms_to_syslog) VALUES (UNHEX(?), 0, 0, 0, 0, 0, 0, 0, 0, ?, 0, 0)
            ON DUPLICATE KEY UPDATE sem = ?";

        if (!$conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }

    
    public static function delete($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $query = 'DELETE FROM server WHERE id = UNHEX(?)';
        
        if (!$conn->Execute($query, array($id))) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
        
        $query = 'DELETE FROM server_role WHERE server_id = UNHEX(?)';

        if (!$conn->Execute($query, array($id))) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = 'DELETE FROM server_hierarchy WHERE child_id = UNHEX(?) OR parent_id = UNHEX(?)';

        if (!$conn->Execute($query, array($id, $id))) 
        {
           Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = 'DELETE FROM server_forward_role WHERE server_src_id = UNHEX(?) OR server_dst_id = UNHEX(?)';
        
        if (!$conn->Execute($query, array($id, $id))) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Delete component
        Av_center::delete_component($conn, $id, 'server');

        // Delete orphan systems
        Av_center::delete_orphan_systems($conn);

        $infolog = array($id);

        Log_action::log(78, $infolog);

        Web_indicator::set_on('Reload_servers');
        // ReloadPolicy key deprecated, now using Reload_policies always
        //Web_indicator::set_on('ReloadPolicy');
    }


    public static function update_rservers($conn, $id, $ip, $rservers)
    {
        Ossim_db::check_connection($conn);
        
        $forwarder    = $forw_updated = array();
        $forwarder    = self::get_my_hierarchy($conn,$id);
        $forw_updated = array_keys($forwarder);

        if(is_array($rservers) && count($rservers)>0)
        {
            $forw_updated = array_diff($forw_updated, array_keys($rservers));
        }

        if(!empty($forw_updated))
        {
            foreach($forw_updated as $fid)
            {
                Policy_forward_reference::delete_by_parent($conn,$id, $fid);
            }
        }   

            $conn->Execute('DELETE FROM server_forward_role WHERE server_src_id = UNHEX(?)',array($id));

        if (is_array($rservers) && count($rservers) > 0)
        {
            $conn->Execute('DELETE FROM server_hierarchy WHERE child_id = UNHEX(?)', array($id));
            
            // Get center uuid from ip
            $data         = Av_center::get_system_info_by_ip($conn, $ip);
            $local_system = $data['data']['system_id'];
            
            foreach ($rservers as $rid => $rprio)
            {
                $conn->Execute("REPLACE INTO server_hierarchy (child_id, parent_id) VALUES (UNHEX(?), UNHEX(?))", array($id, $rid));
                $conn->Execute("REPLACE INTO server_forward_role (server_src_id, server_dst_id, priority) 
                    VALUES (UNHEX(?), UNHEX(?), ?)", array($id, $rid, $rprio));
        
                // Get $rip from $rid
                $rip    = self::get_server_ip($conn, $rid);
                $alienvault_conn = new Alienvault_conn(Session::get_session_user(), $rip);
                $provider_registry = new Provider_registry();
                $client = new Alienvault_client($alienvault_conn, $provider_registry);

                $client->auth()->set_certificate($local_system);
            }
        }
    }


    public static function get_server_ip($conn, $id = '') 
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT INET6_NTOA(ip) AS ip FROM server WHERE id=UNHEX(?)");
        
        $rs = $conn->Execute($query,array($id));
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        elseif (!$rs->EOF) 
        {
            return $rs->fields['ip'];
        }
        return $id;
    }

    public static function server_ip_exists($conn, $ip, $server_id = '')
    {
        $query  = 'SELECT count(*) FROM server WHERE ip = UNHEX(?)';
        $params = array (bin2hex(inet_pton($ip)));

        if ($server_id != '') 
        {
            $query     .= ' AND id != UNHEX(?)';
            $params[] = $server_id;
        }

        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            return FALSE;           
        }
        else
        {           
            //There are two o more server with same IP
                                                
            if ($rs->fields[0] > 0)
            {
                return TRUE;
            }
        }

        return FALSE;
    }


    public static function get_default_server($conn, $canonical = TRUE)
    {
        Ossim_db::check_connection($conn);

        $server_id = '';        
        $query     = "SELECT value as server_id FROM config where conf='server_id'";

        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {
            $server_id = $rs->fields['server_id'];
        }
        
        return ($canonical) ? $server_id : Util::uuid_format_nc($server_id); 
    }


    public static function get_engine_server($conn, $id = '')
    {
        Ossim_db::check_connection($conn);
        
        $result = array();
        
        $where  = (empty($id))? '' : " AND e.id = UNHEX('$id') ";
        
        $query = "SELECT HEX(e.id) AS engine_id, s.name, INET6_NTOA(s.ip) AS ip 
                    FROM server s, acl_entities e 
                    WHERE 1=1 $where AND s.id = e.server_id AND e.entity_type='engine';";
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            return $result;
        }
        else 
        {
            while (!$rs->EOF) 
            {
                $result[$rs->fields['engine_id']] = array(
                    'id'   => $rs->fields['engine_id'],
                    'name' => $rs->fields['name'],
                    'ip'   => $rs->fields['ip']
                );
                            
                $rs->MoveNext();
            }
        }
        
        return $result; 
    }
    
    
    public static function get_name_by_id($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $name   = _('Unknonwn');
        
        $params = array($id);
        $query  = 'SELECT name FROM server WHERE id = UNHEX(?)';
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {
            $name = (empty($rs->fields['name']) ? $name : $rs->fields['name']);
        }

        return $name;        
    }   
    
    public static function is_in_a_system($conn, $server_id) 
    {
        Ossim_db::check_connection($conn);

        $query = 'SELECT 1 FROM system WHERE server_id=UNHEX(?)';
        
        if (!$rs = $conn->Execute($query, array($server_id)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return TRUE;
        }

        return FALSE;
    }


    public static function kali_connect($token) {
        $alienvault_conn = new Alienvault_conn();
        $provider_registry = new Provider_registry();
        $client = new Alienvault_client($alienvault_conn, $provider_registry);
        $status = $client->central_console()->connect($token);
        return self::proceedResponse($status);
    }
    public static function kali_disconnect() {
        $alienvault_conn = new Alienvault_conn();
        $provider_registry = new Provider_registry();
        $client = new Alienvault_client($alienvault_conn, $provider_registry);
        $status = $client->central_console()->disconnect();
        return self::proceedResponse($status);
    }
    public static function get_kali_status() {
        $alienvault_conn = new Alienvault_conn();
        $provider_registry = new Provider_registry();
        $client = new Alienvault_client($alienvault_conn, $provider_registry);
        return json_decode($client->central_console()->status());
    }
    public static  function  services_restart() {
        ob_start();
        Util::execute_command('/usr/bin/sudo /etc/init.d/alienvault-forward restart');
        Util::execute_command('/usr/bin/sudo /etc/init.d/ossim-server restart');
        ob_clean();
    }
    public static function proceedResponse($datax) {
        $data = json_decode($datax);
        //console_status_denied
        if ($data->status == 3) {
            http_response_code(500);
            Av_exception::throw_error(Av_exception::USER_ERROR,_("Console Status Denied"));
        }
        if ($data->status == "error") {
            http_response_code(500);
            Av_exception::throw_error(Av_exception::USER_ERROR,$data->message);
        }
        if($data->status == 1 || $data->status == 4) {
            self::services_restart();
        }
        return $datax;
    }

}

/* End of file server.inc */
/* Location: ../include/classes/server.inc */

