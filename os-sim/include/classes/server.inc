<?php
/**
* server.inc
*
* File server.inc is used to:
*   - To manage Alienvault Servers
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
    
    
    public function get_descr() 
    {
        return $this->descr;
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
			$object = new Server($rs->fields['id'],
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
                if (!$rf = & $conn->Execute('SELECT FOUND_ROWS() AS total'))
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
                $list[] = new Server($rs->fields['id'],
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
		$query = "SELECT HEX(server_src_id) AS id FROM server_forward_role WHERE server_dst_id = UNHEX(?)";
		
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

	public static function get_parent_servers($conn, $uuid) 
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
		$conf = $GLOBALS['CONF'];
		$conf = (!$conf) ? new Ossim_conf() : $conf;
		
    	// Create public key in local machine 
		if (!file_exists("/etc/ossim/framework/ssh/remotelogger.pub")) 
		{
        	system("ssh-keygen -q -t rsa -N '' -C remotelogger -f /etc/ossim/framework/ssh/remotelogger");
        }
        
        $publickey_output = file("/etc/ossim/framework/ssh/remotelogger.pub");
        $publickey = trim($publickey_output[0]);
		$keyparam  = base64_encode(Util::encrypt($remoteadmin.'####'.$remotepass.'####'.$publickey, $conf->get_conf('remote_key')));
		
        // Check the remote authorized_keys
        ini_set('display_errors', 0);
                        
        if (is_array($output = file($remoteurl.'/sem/insertsshkey.php?key='.$keyparam))) 
        {
        	$s_url = Menu::get_menu_url('/ossim/server/server.php', 'configuration', 'deployment', 'components', 'servers');
        	
        	if ($output[0] == 'USERERROR') 
        	{     		
        		echo "<center>". _("Login error in remote machine. Remote logger is not configured...")." <a href='$s_url'>"._("Continue with Server config")."</a></center>";
        		        		
        		ini_set('display_errors', 1);
                
        		return FALSE;
        	} 
        	elseif ($output[0] == 'VALIDERROR') 
        	{
        		echo "<center>"._("Invalid user or wrong remote_key configuration parameter. Remote logger is not configured...")." <a href='$s_url'>"._("Continue with Server config")."</a></center>";
        		
        		ini_set('display_errors', 1);
                
        		return FALSE;
        	} 
        	elseif ($output[0] != 'OK') 
        	{
        		echo "<center>"._("Error writting SSH public key in remote machine. Remote logger is not configured...")." <a href='$s_url'>"._("Continue with Server config")."</a></center>";
        		
        		ini_set( "display_errors", 1);
                
        		return FALSE;
        	}
        }
        else 
        {
        	echo "<center>"._("Error connecting to $remoteurl. Remote logger is not configured...")." <a href='$s_url'>"._("Continue with Server config")."</a></center>";
        	
        	ini_set('display_errors', 1);
            
        	return FALSE;
        }
        
        ini_set('display_errors', 1);
        
        return TRUE;
    }
	
    
    public static function insert($conn, $name, $ip, $port, $descr, $r_correlate, $r_cross_correlate, $r_store, $r_reputation, 
        $r_qualify, $r_resend_alarm, $r_resend_event, $r_sign, $r_sem, $r_sim, $r_alarm_to_syslog, $remoteadmin, $remotepass, 
        $remoteurl, $rservers, $bypass_credential = TRUE, $new_id='') 
    {
        
        Ossim_db::check_connection($conn);
        
        $uuid = Util::get_encryption_key();
        
        // Get new ID if needed
    	if ($new_id == '')
    	{
    	    $new_id = Util::uuid();
        }
        
		$q_pass = (!empty($remotepass)) ? "AES_ENCRYPT(?,'$uuid')" : "?";
        
        $query = "INSERT INTO server (id, name, ip, port, descr, remoteadmin, remotepass, remoteurl)
                VALUES (UNHEX(?), ?, UNHEX(?), ?, ?, ?, $q_pass, ?)";
        
        $params = array(
            $new_id,
        	$name,
            bin2hex(inet_pton($ip)),
            $port,
            $descr,
            $remoteadmin,
            $remotepass,
            $remoteurl
        );
        
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $query = 'INSERT INTO server_role (server_id, correlate, cross_correlate, store, reputation, qualify, resend_alarm, 
            resend_event, sign, sem, sim, alarms_to_syslog) 
            VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $params = array(
            $new_id,
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
            $r_alarm_to_syslog
        );
        
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
		//$bypass_credential is used only for migration from 3.1 to 4.0
        if ( $remoteadmin != '' && $remotepass != '' && $remoteurl != '' && $bypass_credential) 
        {
        	if ( !self::set_remote_sshkey($remoteadmin, $remotepass, $remoteurl) ) 
            {
        		$query = "UPDATE server SET remoteadmin='', remotepass='', remoteurl='' WHERE id = UNHEX(?)";
		        $params = array($new_id);
        		
                if (!$conn->Execute($query, $params)) 
                {
		        	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		        }
		        
		        sleep(5);
		   	}
        }
        
        self::update_rservers($conn,$new_id,$ip,$rservers);
        
        $infolog = array(
            $name,
            "[" . $ip . "]"
        );
        Log_action::log(76, $infolog);
		
        Web_indicator::set_on("Reload_servers");
        Web_indicator::set_on("ReloadPolicy");
        
        return $new_id;
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
        	if (!self::set_remote_sshkey($remoteadmin, $remotepass, $remoteurl)) 
            {
        		$params = array($name);
                $query  = "UPDATE server SET remoteadmin = '', remotepass = '', remoteurl = '' WHERE name = ?";
		        
        		if (!$conn->Execute($query, $params)) 
                {
		        	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		        }
                
		        sleep(5);
		   	}
        }
        
        self::update_rservers($conn,$id,$ip,$rservers);

        $infolog = array($name, '[' . $ip . ']');
        Log_action::log(77, $infolog);
        
        Web_indicator::set_on('Reload_servers');
        Web_indicator::set_on('ReloadPolicy');        
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
        Web_indicator::set_on('ReloadPolicy');
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
		
        if (is_array($rservers) && count($rservers) > 0)
        {           

    		$conn->Execute('DELETE FROM server_hierarchy WHERE child_id = UNHEX(?)', array($id));
    		$conn->Execute('DELETE FROM server_forward_role WHERE server_src_id = UNHEX(?)',array($id));
            
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
				$client = new Alienvault_client(Session::get_session_user(), $rip);
				$client->auth()->set_certificate($local_system);
			}
        }
    }
    
	
    public static function get_server_ip($conn, $id = '') 
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT INET6_NTOP(ip) AS ip FROM server WHERE id=UNHEX(?)");
        
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
    
        
    public static function server_exists($conn, $server) 
    {
		$server_list = self::get_list($conn, 'ORDER BY name');
		
		foreach ($server_list as $s) 
		{
			if ($s->get_name() == $server || $s->get_ip() == $server) 
			{
			     return TRUE;
			}
		}
		
		return FALSE;
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
		
		if (!$rs = & $conn->Execute($query, $params)) 
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
	
	
    public static function get_deafault_server($conn)
	{
		Ossim_db::check_connection($conn);
		
		$id    = '';		
		$query = "SELECT value as server_id FROM config where conf='server_id'";
        
        $rs = $conn->Execute($query);
        
		if (!$rs) 
		{
			print $conn->ErrorMsg();
		}
		else 
		{
			$id = $rs->fields ['server_id'];
		}
		
		return $id;	
	}
	
    
	public static function get_engine_server($conn, $id = '')
    {				
		Ossim_db::check_connection($conn);
        
        $result = array();
		
		$where  = (empty($id))? '' : " AND e.id = UNHEX('$id') ";
		
		$query = "SELECT HEX(e.id) AS engine_id, s.name, INET6_NTOP(s.ip) AS ip 
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
    
    /**
     * This function gets the vpn_ip by admin_ip in 'system' table
     * Not using 'server' table but It's related with logger server selection
     *
     * @param object $conn
     * @param string $admin_ip
     * @return string
     */
    public static function get_vpnip_by_adminip($conn, $admin_ip)
    {
        Ossim_db::check_connection($conn);
    
        $vpn_ip = '';
    
        $params = array($admin_ip);
        $query  = 'SELECT INET6_NTOP(vpn_ip) AS ip FROM system WHERE admin_ip = INET6_PTON(?)';
    
        $rs = $conn->Execute($query, $params);
    
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $vpn_ip = $rs->fields['ip'];
            }
        }
    
        return $vpn_ip;
    }
}

/* End of file server.inc */
/* Location: ../include/classes/server.inc */
