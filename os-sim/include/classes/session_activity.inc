<?php
/**
* session_activity.inc
*
* File session_activity.inc is used to:
*   - Manage user activity in Open Source and PRO version.
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
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
require_once 'classes/locale.inc';
require_once 'av_config.php';

/**
* Session_activity Class
*
* This class is used to manage user activity in Open Source and PRO version
*
*
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/
class Session_activity 
{
    /**#@+
    * Attributes
    */
    
    /**
    * @var string Session ID
    * @access private 
    */
    private $id;
    
    /**
    * @var string User login
    * @access private 
    */
    private $login;
    
    /**
    * @var string User IP
    * @access private 
    */
    private $ip;
    
    
    /**
    * @var string User agent (Browser)
    * @access private 
    */
    private $agent;
    
    
    /**
    * @var string Last logon date
    * @access private 
    */
    private $logon_date;
    
    
    /**
    * @var string  Last activity date
    * @access private 
    */
    private $activity;
    
         
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id          Session ID
    * @param string  $login       User login
    * @param string  $ip          Session IP
    * @param string  $agent       User agent (Browser)
    * @param string  $logon_date  Last logon date (Date Format: YYYY-mm-dd HH:ii:ss)
    * @param string  $activity    Last activity date (Date Format: YYYY-mm-dd HH:ii:ss)
    */
    public function __construct($id, $login, $ip, $agent, $logon_date, $activity)
    {
        $this->id            = $id;
        $this->login         = $login;
        $this->ip            = $ip;
        $this->agent         = $agent;
        $this->logon_date    = $logon_date;
        $this->activity      = $activity;
    }
    
   
    /**
    * This function returns the session ID
    *
    * @return string             
    */
    public function get_id()
    { 
        return $this->id;
    }
    
    
    /**
    * This function sets the session ID
    *
    * @param string $id  Session ID
    *
    * @return void         
    */
    public function set_id($id)
    { 
        $this->id = $id;
    }
    
    
    /**
    * This function returns the user login
    *
    * @return string             
    */
    public function get_login()
    { 
        return $this->login;
    }
    
    
    /**
    * This function sets the user login
    *
    * @param string $login  User login
    *
    * @return void        
    */
    public function set_login($login) 
    { 
        $this->login = $login;
    }
    
    
    /**
    * This function returns the user IP
    *
    * @return string
    */
    public function get_ip()
    { 
        return $this->ip;
    }
    
    /**
    * This function sets the user IP
    *
    * @param string  $ip  User IP
    *
    * @return void         
    */
    public function set_ip($ip)
    { 
        $this->ip = $ip;
    }
    
    
    /**
    * This function returns the user agent
    *
    * @return string             
    */
    public function get_agent()
    { 
        return $this->agent;
    }
    
    
    /**
    * This function sets the user agent
    *
    * @param string $agent  User agent
    *
    * @return void      
    */
    public function set_agent($agent)
    {
        $this->agent = $agent;
    }
    
    
    /**
    * This function returns the last logon date
    *
    * @return string         
    */
    public function get_logon_date()
    { 
        return $this->logon_date;
    }
    
    
    /**
    * This function sets the last logon date
    *
    * @param string $logon_date  Last logon date        
    *
    * @return void
    */
    public function set_logon_date($logon_date)
    { 
        $this->logon_date = $logon_date;
    }
    
    
    /**
    * This function returns the last activity date
    *
    * @return string         
    */
    public function get_activity()
    {
        return $this->activity;
    }
    
    
    /**
    * This function sets the last activity date
    *
    * @param string $activity  Last activity date
    *
    * @return void
    */
    public function set_activity($activity)
    { 
        $this->activity= $activity;
    }    
            
     
    /**
    * This function returns the real IP address associated to current user
    *
    * @return string         
    */
    public function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {   
            //Check ip from is shared in Internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {   
            //Check IP is passed from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
          $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    
    /**
    * This function returns the external IP address associated to current user
    *
    * @return string         
    */
    public function getExtIpAddr()
    {
        $ip     = '';
        $output = file('http://cualesmiip.com');
        foreach ($output as $line) 
        {
            if (preg_match("/Tu IP real es (\d+\.\d+\.\d+\.\d+)/",$line,$found)) 
            {
                $ip = $found[1];
            }
        }
        
        return $ip;
    }
    
    
    /**
    * This function returns the user agent associated to current user
    *
    * @return string         
    */
    private function get_ua()
    {                
        $browser = new Browser();
        $agent   = NULL;
        
        $agent .= (!empty($browser->name)) ? ucfirst($browser->name) : '';
        $agent .= (!empty($browser->ver))  ? ' '.ucfirst($browser->ver) : '';
        
        if (!empty ($browser->platform) || !empty ($browser->platform))
        {
            $agent .=  ' / ';
            
            $agent .= (!empty ($browser->platform)) ? ucfirst($browser->platform).' ' : '';
            $agent .= (!empty ($browser->os))       ? ucfirst($browser->os)       : '';
        }
        
        $agent  = (empty($agent)) ? _('Unknown') : $agent;
        $agent .= '###'.$browser->str;
        
        return $agent;
    }
    
    
    /**
    * This function destroys user session, delete the row from 'Sessions' Table associated to user $login and that user is disconnected
    *
    * @param string $conn  Database access object
    * @param string $login   [Optional] User login
    *
    * @return void
    */
    public function force_user_logout($conn, $login = '')
    {
        $my_session = session_id();
        
        $users = array();
        
        if($login == '') 
        {
            $login = Session::get_session_user();
        }

        $users = self::get_list($conn, "WHERE login = '$login' ORDER BY activity desc");

        if (!empty($users))
        {
            foreach ($users as $user)
            {
                if ($user->get_id() != $my_session)
                {
                    self::delete($user->get_id());
                    self::delete_session($user->get_id());
                }
            }
        }
    }

    
    /**
    * This function returns an array of objects 'Session_activity' that satisfy SQL condition
    *
    * @param object $conn   Database access object
    * @param string $args   [Optional] SQL condition
    *
    * @return array                
    */
    public function get_list($conn, $args = '')
    {
        $list  = array();
        $query = ossim_query("SELECT * FROM sessions $args");
                
        $rs = $conn->Execute($query);  
                      
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {                       
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields['id'], $rs->fields['login'], $rs->fields['ip'], $rs->fields['agent'], 
                    $rs->fields['logon_date'], $rs->fields['activity']);
                    
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
             

    /**
    * This function inserts a new row in 'Sessions' table for logged user
    *
    * @param object $conn  Database access object
    *
    * @return boolean
    */
    public function insert($conn)
    {
        $exceptions[]  = 'av report scheduler';
        $pattern_exp   = '/'.implode('|', $exceptions).'/';
                
        $id            = session_id();
        $login         = Session::get_session_user();
        $ip            = self::getRealIpAddr();
        $agent         = self::get_ua();
        
        
        if (preg_match($pattern_exp, $agent) == TRUE)
        {
            return TRUE;
        }
                        
        $logon_date = $activity = gmdate("Y-m-d H:i:s");
                
        $conf = $GLOBALS['CONF'];
        
        if (!$conf) 
        {           
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        
        $expired_timeout = intval($conf->get_conf('session_timeout')) * 60;
        
        // Session Timeout = 0 (Unlimited) => Delete sessions of the last day (24 x 60 x 60= 86400 s)
        
        $expired_timeout = ($expired_timeout == 0) ? 86400 : $expired_timeout;
        
        if ($expired_timeout > 0)
        {
            $time = gmdate('U') - $expired_timeout;
            $date = gmdate('Y-m-d H:i:s', $time);
            
            $query  = "DELETE FROM sessions WHERE activity < '$date'";
            $params = array($activity);
                                    
            if ($conn->Execute($query, $params) === FALSE) 
            {                   
                $config_nt = array(
                    'content' => _('Error deleting from database').': '.$conn->ErrorMsg(),
                    'options' => array (
                        'type'          => 'nf_error',
                        'cancel_button' => FALSE
                  ),
                    'style'   => 'font-size:12px; width: 90%; margin: 20px auto; text-align: left;'
              ); 
                                
                $nt = new Notification('nt_1', $config_nt);
                $nt->show();
                   
                return FALSE;
            }
        }
                
        $query = "INSERT INTO sessions (id, login, ip, agent, logon_date, activity) VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = array(
            $id,
            $login,
            $ip,
            $agent,
            $logon_date,
            $activity,
        );
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            $config_nt = array(
                'content' => _('Error inserting into database').': '.$conn->ErrorMsg(),
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
                ),
                'style'   => 'font-size:12px; width: 90%; margin: 20px auto; text-align: left;'
            );
                            
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
           
            return FALSE;
        }
        
        return TRUE;
    }
   

    /**
    * This function updates 'activity' field for logged user
    *
    * @param string $time  [Optional] Last activity date
    *
    * @return boolean
    */
    public function update($time = '')
    {               
        $db     = new ossim_db();
        $conn   = $db->connect();
                        
        $id       = session_id();
        $login    = Session::get_session_user();
        $activity = (empty($time)) ? gmdate('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s', $time);
        
        $query = "UPDATE sessions SET activity = ? WHERE id=? AND login=?";
        
        $params = array(
            $activity,
            $id,
            $login
        );
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            $config_nt = array(
                'content' => _('Error updating database').': '.$conn->ErrorMsg(),
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
                ),
                'style'   => 'font-size:12px; width: 90%; margin: 20px auto; text-align: left;'
            ); 
           
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();

            $db->close();
            
            return FALSE;
        }
        
        $db->close();
        
        return TRUE;
    }
    
    
    /**
    * This function deletes a row from 'Sessions' table
    *
    * @param string $id  [Optional] Session ID
    *
    * @return boolean
    */
    public function delete($id = '')
    {        
        $db     = new ossim_db();
        $conn   = $db->connect();
        
        $id     = (empty($id)) ? session_id() : $id;
                                    
        $query  = "DELETE FROM sessions WHERE id = ?";
        $params = array($id);
                
        
        if ($conn->Execute($query, $params) === FALSE) 
        {            
            $config_nt = array(
                'content' => _('Error deleting from database').': '.$conn->ErrorMsg(),
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
              ),
                'style'   => 'font-size:12px; width: 90%; margin: 20px auto; text-align: left;'
          ); 
            
            
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
            
            $db->close();
            
            return FALSE;
        }
        
        $db->close();
        return TRUE;
    }
    
    
    /**
    * This function destroys an user session for logged user
    *
    * @param string $id  [Optional] Session ID
    *
    * @return boolean
    */
    public function delete_session($id = '')
    {
        $id         = (empty($id)) ? session_id() : $id;
        $my_session = session_id();
        
        if ($id == $my_session) 
        {
            session_destroy();
            
            return TRUE;
        }
        else
        {
            session_commit();
            session_id($id);
            session_start();
        
            $_SESSION = array();
            session_destroy();
            session_commit();
        
            session_id($my_session);
            session_start();
            
            return TRUE;
        }
    }
    
    
    /**
    * This function destroys all open user session for user $login except the current session
    *
    * @param string $conn  Database access object
    * @param string $login   User login
    *
    * @return boolean
    */
    public function expire_my_others_sessions($conn, $login)
    {
        $other_sessions = self::get_list($conn, "WHERE login='$login'");
        
        $my_session = session_id();
        session_commit();
        
        foreach ($other_sessions as $v)
        {
            $id = $v->get_id();
            
            if ($my_session != $id)
            {
                self::delete($id);
                
                session_id($id);
                session_start();
            
                $_SESSION = array();
                session_destroy();
                session_commit();
            }
        }
        
        session_id($my_session);
        session_start();
        
        return TRUE;
    }
     

    /**
    * This function checks if the date $activity is considered expired
    *
    * @param string $activity  Last activity date
    *
    * @return boolean
    */     
    public function is_expired($activity)
    {
        $conf = $GLOBALS['CONF'];
        
        if (!$conf) 
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
            
        }
            
        $expired_timeout = intval($conf->get_conf('session_timeout')) * 60;
        
        if ($expired_timeout == 0)
        {
            return FALSE;
        }
        
        $expired_date = $activity + $expired_timeout;
        $current_date = gmdate('U');
        
        if ($expired_date < $current_date)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
}

/* End of file session_activity.inc */
/* Location: ../include/classes/session_activity.inc */