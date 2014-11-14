<?php
/**
* session.inc
*
* File session.inc is used to:
*   - Manage user sessions in Open Source and PRO version.
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
* Session Class
*
* This class is used to manage user sessions in Open Source and PRO version
*
*
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/
class Session
{       
    /**#@+
    * Attributes
    */
    
    /**
    * @var string  User login 
    * @access public 
    */
    public $login;    
    
    /**
    * @var string  SHA256 user password
    * @access public 
    */
    public $pass;    
    
    /**
    * @var string  User UUID (Unique ID for user)
    * @access public 
    */
    public $uuid;    
    
    /**
    * @var array  Contexts associated to user
    * @access public 
    */
    public $ctx;    
    
    /**
    * @var array  Sensor restriction (Sensors that user can see)
    * @access public 
    */
    public $sensors;    
    
    /**
    * @var string  Asset restriction (Assets that user can see)
    * @access public 
    */
    public $assets;    
    
    /**
    * @var string  Template ID associated to user (Hexadecimal ID)
    * @access public 
    */
    public $template_id;    
    
    /**
    * @var string  Username
    * @access public 
    */
    public $name;    
    
    /**
    * @var string  User email
    * @access public 
    */
    public $email;    
    
    /**
    * @var string  User company (Only in Open Source version)
    * @access public 
    */
    public $company;    
    
    /**
    * @var string  User departament (Only in Open Source version)
    * @access public 
    */
    public $department;    
    
    /**
    * @var string  User language (User Interface Language)
    * @access public 
    */
    public $language;    
          
    /**
    * User status
    * 
    * <pre>
    * Values:
    *    -1: User is locked (Only an admin user can unlock the user)
    *     0: User doesn't have access to UI (temporarily)
    *     1: User has access to UI (enabled)
    * </pre>
    *
    * @var int  User status 
    * @access public 
    */
    public $enabled;    
    
    /**
    * User type
    * 
    * <pre>
    * Values:
    *     1: Admin User
    *     0: Non-Admin User
    * </pre>
    *
    * @var int  User type
    * @access public 
    */
    public $is_admin;    
    
    /**
    * @var object  Database access object
    * @access public 
    */
    public $conn;    
    
    /**
    * @var string  User Timezone
    * @access public 
    */
    public $timezone;    
    
    /**
    * @var string  Last Logon (Date Format: YYYY-mm-dd HH:ii:ss)
    * @access public 
    */
    public $last_logon;    
    
    /**
    * @var string  External access (Used by Remote Interface or Wizard Report Scheduler)
    * @access public 
    */
    public $external;    
    
    /**
    * @var string  Login method ('password', 'ldap' or empty)
    * @access public 
    */
    public $login_method;
        
           
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $login         User login
    * @param string  $pass          SHA256 user password
    * @param string  $uuid          [Optional] User UUID (Unique ID for user)
    * @param array   $ctx           [Optional] Contexts associated to user
    * @param array   $sensors       [Optional] Sensor restriction (Sensors that user can see)
    * @param array   $assets        [Optional] Asset restriction (Assets that user can see)
    * @param string  $template_id   [Optional] Template ID associated to user
    * @param string  $name          [Optional] Username
    * @param string  $email         [Optional] User email
    * @param string  $company       [Optional] User company (Only in Open Source version)
    * @param string  $department    [Optional] User departament (Only in Open Source version)
    * @param string  $language      [Optional] User language (User Interface Language)
    * @param int     $enabled       [Optional] User status (-1, 0 or 1)
    * @param int     $is_admin      [Optional] User type (Admin User (1) or Non-Admin User(0))
    * @param boolean $conn          [Optional] Create Database object
    * @param string  $timezone      [Optional] User Timezone
    * @param string  $last_logon    [Optional] Last Logon (Date Format: YYYY-mm-dd HH:ii:ss)
    * @param int     $foundrows     [Optional] Number of users returned by function get_list()
    * @param string  $login_method  [Optional] Login method ('pass' or 'ldap' or empty)
    */
    public function __construct($login, $pass, $uuid = '', $ctx = array(), $sensors = array(), $assets = array(), $template_id = '', 
        $name = '', $email = '', $company = '', $department = '', $language = '', $enabled = 1, $is_admin = 0, $conn = 1, 
        $timezone = '', $last_logon = '', $foundrows = 0, $login_method = '')
    {
             
        if ($conn) 
        {
            $db = new ossim_db();
            $this->conn = $db->connect();
        }
        
        $this->login         = strtolower($login);
        $this->pass          = $pass;
        $this->uuid          = $uuid;
        $this->ctx           = $ctx;
        $this->assets         = $assets;
        $this->sensors       = $sensors;
        $this->template_id   = $template_id;
        $this->name          = $name;
        $this->email         = $email;
        $this->company       = $company;
        $this->department    = $department;
        $this->language      = $language;
        $this->enabled       = $enabled;
        $this->is_admin      = $is_admin;
        $this->timezone      = $timezone;
        $this->last_logon    = $last_logon;
        $this->external      = FALSE;
        $this->foundrows     = $foundrows;
        $this->login_method  = $login_method;
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
    * This function returns the user password
    *
    * @return string             
    */
    public function get_pass() 
    {
        return $this->pass;
    }
    
    
    /**
    * This function returns the user UUID
    *
    * @return string             
    */
    public function get_uuid() 
    {
        return $this->uuid;
    }
    
    
    /**
    * This function returns the user contexts
    *
    * @return array             
    */
    public function get_ctx() 
    {
        return $this->ctx;
    }
    
    
    /**
    * This function returns the asset restriction (Assets that user can see)
    *
    * @return array             
    */
    public function get_assets() 
    {
        return $this->assets;
    }
    
    
    /**
    * This function returns the sensor restriction (Sensors that user can see)
    *
    * @return array             
    */
    public function get_sensors() 
    {
        return $this->sensors;
    }
    
    
    /**
    * This function returns user template ID
    *
    * @return string             
    */
    public function get_template_id() 
    {
        return $this->template_id;
    }
    
    /**
    * This function returns the username
    *
    * @return string             
    */
    public function get_name() 
    {
        return $this->name;
    }
    
    
    /**
    * This function returns the user email
    *
    * @return string             
    */
    public function get_email() 
    {
        return $this->email;
    }
    
    
    /**
    * This function returns the user company
    *
    * @return string             
    */
    public function get_company() 
    {
        return $this->company;
    }
    
    
    /**
    * This function returns the user department
    *
    * @return string             
    */
    public function get_department() 
    {
        return $this->department;
    }
    
        
    /**
    * This function returns the user lenguage
    *
    * @return string             
    */
    public function get_language() 
    {
        return $this->language;
    }
        
    
    /**
    * This function returns the access status
    *
    * @return boolean             
    */
    public function get_enabled() 
    {
        return $this->enabled;
    }
        
    /**
    * This function returns the user type (Admin User or Non-Admin User)
    *
    * @return boolean             
    */
    public function get_is_admin() 
    {
        return $this->is_admin;
    }
    
    
    /**
    * This function returns the user timezone
    *
    * @return string             
    */
    public function get_tzone() 
    {
        return $this->timezone;
    }
    
    
    /**
    * This function returns the user timezone
    *
    * <pre>
    * For backward compatibility, parameter $tz can be:
    *  - Valid timezone string. For example: Europe/Madrid, Europe/Berlin, ..
    *  - Time difference (in hours) between GMT and local time.  For example: 1, -1, 2.5, ...
    * </pre>
    *
    * @param string|int|float  User timezone
    *
    *
    * @return int  Time difference (in hours) between GMT and local time            
    */
    public function get_timezone($tz = '') 
    {        
        if ($tz == '') 
        {
            $tz = $this->timezone;
        }
        
        if (is_numeric($tz)) 
        {
            return $tz; // Old stored numeric format
        }
        
        try 
        {
            $this_tz = new DateTimeZone($tz);
        }
        catch(Exception $e) 
        {
            return 0;
        }
        
        $offset = $this_tz->getOffset(new DateTime('now', $this_tz));
        
        if ($offset != 0)
        {
            $offset /= 3600;
        }
        
        return $offset;
    }
    
    
    /**
    * This function returns last logon date of user
    *
    * @return string  Date Format: YYYY-mm-dd HH:ii:ss          
    */
    public function get_last_logon() 
    {
        return $this->last_logon;
    }
    
    
    /**
    * This function returns the number of users returned by function get_list()
    *
    * @return int             
    */
    public function get_foundrows() 
    {
        return $this->foundrows;
    }
    
    
    /**
    * This function returns login method of user
    *
    * @return string  Valid values are 'pass' or 'ldap'
    */
    public function get_login_method() 
    {
        return $this->login_method;
    }
      
      
    /**
    * This function returns TRUE if system version is a PRO version, otherwise it returns FALSE
    *
    * @return boolean                
    */  
    public static function is_pro() 
    {
        if($_SESSION['_version'] == '')
        {                        
            $conf                 = new Ossim_conf();
            $version              = $conf->get_conf('ossim_server_version');
            $_SESSION['_version'] = (preg_match('/pro|demo/i',$version)) ? 'pro' : 'opensource';
        }
        
        return ($_SESSION['_version'] == 'pro') ? TRUE : FALSE;        
    }
    
    
    /**
    * This function returns the UUID of user $user
    *
    * @param string  $user User login to search
    *
    * @return string 
    */
    public static function get_secure_id($user = '') 
    {
        if (empty($user) || $user == self::get_session_user())
        {
            return @$_SESSION['_secureid'];
        }                
        
        $db     = new ossim_db();
        $conn   = $db->connect();
        
        $query  = 'SELECT *, HEX(uuid) as uuid FROM `users` WHERE login = ?';
        $rs     = $conn->Execute($query, array($user));
                                        
        $secure_id = '00000000000000000000000000000000';
                        
        if (is_array($rs->fields) && !empty($rs->fields))
        {
            $secure_id = (valid_hex32($rs->fields['uuid']) && $rs->fields['uuid'] != '0x0') ? $rs->fields['uuid'] : sha1($user.'#'.$rs->fields['pass']);
        }
                    
        $db->close();
        
        return $secure_id;
    }
          
    
    /**
    * This function updates the UUID in Database of user $login
    *
    * @param object $conn      Database access object
    * @param string $login     User login to search
    * @param string $new_uuid  New user UUID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean 
    */
    public static function set_secure_id($conn, $login, $new_uuid)
    {    
        Ossim_db::check_connection($conn);
        
        $params = array($new_uuid, $login);
        $query  = "UPDATE users SET uuid = UNHEX(?) WHERE login = ?";
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            return FALSE;
        } 
        
        return TRUE;
    }
        
    
    /**
    * This function returns an object with all information of user $user
    *
    * @param object $conn  Database access object
    * @param object $user  [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object                
    */
    public static function get_user_info($conn, $user = '', $noperms = FALSE, $with_assets = TRUE) 
    {
        Ossim_db::check_connection($conn);
        
        $me   = NULL;
        $user = (empty($user)) ? self::get_session_user() : $user;
        
        if (!empty($user))
        {
            list($me) = self::get_list($conn, "WHERE login='$user'", '', $noperms, $with_assets);
        }
        
        return $me;
    }
    
        
    /**
    * This function returns the login of current logged user
    *
    * @return string                
    */
    public static function get_session_user() 
    {
        return @$_SESSION['_user'];
    }   
    
    
    /**
    * This function returns TRUE if logged user is an admin user
    *
    * @return boolean                
    */
    public static function am_i_admin() 
    {
        return ($_SESSION['_user'] == AV_DEFAULT_ADMIN || $_SESSION['_is_admin']);
    }
    
    
    /**
    * This function returns TRUE if logged user is an admin user
    *
    * @param object $conn  Database access object
    * @param string $user  User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function is_admin($conn, $user) 
    {        
        Ossim_db::check_connection($conn);
        
        $users = self::get_list($conn, "WHERE login='$user'");
                        
        if (is_array($users) && count($users) >= 1)
        {
            $user = array_shift($users);
            
            return ($user->get_login() == AV_DEFAULT_ADMIN || $user->get_is_admin());
        }
        else
        {
            return FALSE;
        }
    }
    
    
    /**
    * This function checks if session user of logged user is active.
    *
    * If session user is inactive, logged user will be redirected to $login_location
    *
    * @param boolean|string $login_location  [Optional] FALSE (Redirection to login page), otherwise redirection to $login_location (URL)
    *
    * @return void
    */
    public static function useractive($login_location = FALSE) 
    {        
        self::external_login();

        if (!$login_location) 
        {
            $conf           = $GLOBALS['CONF'];
            $ossim_link     = $conf->get_conf('ossim_link');
            $login_location = $ossim_link . '/session/login.php';
        }
        
        if (!isset($_SESSION['_user']) || $_SESSION['_user'] == '') 
        {
            header("Location: $login_location");
            
            exit();
        }
    }
    
    
    /**
    * This function disables logged user definitely
    *
    * @return boolean                
    */    
    public function disable_user() 
    {
        $conn   = $this->conn;
        $login  = $this->login;
        
        $params = array($login);
        $query  = "UPDATE users SET enabled = -1 WHERE login = ?";
                
        $rs = $conn->Execute($query, $params);
               
        if (!$rs) 
        {        
            return FALSE;
        }
        
        $infolog = array($login);
        Log_action::log(93, $infolog);
        
        return TRUE;
    }
    
    
    /**
    * This function enables/disables the account of user $user
    *
    * @param object $conn  Database access object
    * @param string $user  User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public function toggle_enabled_user($conn, $user) 
    {        
        Ossim_db::check_connection($conn);
        
        $params = array($user);
        $query  = "SELECT enabled FROM users WHERE login = ?";
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {                        
            return FALSE;
        } 
       
        $enabled = ($rs->fields['enabled'] <= 0) ? 1 : 0;
        
        if($enabled == 1) 
        {
            $clogin = $conn->GetOne("SELECT login FROM users WHERE login = '".$user."' AND expires > '".gmdate('Y-m-d H:i:s')."'");
    
            if ($clogin == '') 
            {
                if ($conn->Execute("UPDATE users SET expires = '2200-01-01 00:00:00' WHERE login = '".$user."'") === FALSE) 
                {                    
                    return FALSE;
                }
            }
        }

        $query = "UPDATE users SET enabled = $enabled WHERE login = ?";
        $rs    = $conn->Execute($query, $params);
        
        if (!$rs) 
        {            
            return FALSE;
        }
        
        return TRUE;        
    }

    
    /**
    * This function returns an integer(0, 1 or 2) if user $user is within user scope (Logged user)
    *
    * The integer returned means the following:
    * 
    * <br/>
    *     <pre>
    *     0 - Logged user can not view or edit user $user
    *     1 - Logged user can only view, but he can not edit to user $user
    *     2 - Logged user can view and edit to user $user
    *     </pre>
    * <br/>
    *
    * @param string $user  User login to check
    *
    * @return int                
    */
    public static function userAllowed($user) 
    {
        if (self::am_i_admin() || $user == self::get_session_user())
        { 
            return 2;
        }
        else
        { 
            return $_SESSION['_user_vision']['user'][$user];
        }
    }
    

    /**
    * This function returns an array with non-editable contexts resent from others servers
    *
    * @param object $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array                
    */
    public static function get_external_ctxs($conn, $server_id = '') 
    {
        Ossim_db::check_connection($conn);
        
        $ctxs = array();   
               
        $params = array();
        
        $conf         = $GLOBALS['CONF'];
        $local_server = str_replace('-', '', $conf->get_conf('server_id'));
	
        $params[]     = $local_server;

        $query        = "SELECT HEX(id) AS id, name FROM acl_entities WHERE server_id <> UNHEX(?)";
	
    	if($server_id != '') 
    	{
    	    $params[] = $server_id;
    	    $query   .= ' AND server_id = UNHEX(?)';
    	} 
        
    	$query = ossim_query($query);	
    	$rs    = $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());         
        }
        else
        {       
            while (!$rs->EOF)
            {	
                $ctxs[$rs->fields['id']] = $rs->fields['name'];
                
                $rs->MoveNext();
            }   
        }
        
        return $ctxs;
    }
          
          
    /**
    * This function returns an array of users
    *
    * @param object $conn         Database access object
    * @param string $args         [Optional] SQL condition
    * @param string $order        [Optional] ORDER BY clause
    * @param boolean $noperms     [Optional] If $noperms is TRUE, the functions only returns users within user scope, otherwise it returns all users returned by SQL query
    * @param string $with_assets  [Optional] Add asset information for each user
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array                
    */
    public static function get_list($conn, $args = '', $order = '', $noperms = FALSE, $with_assets = FALSE) 
    {
        Ossim_db::check_connection($conn);        
        
        $list = array();
        
        $iffoundrows = preg_match ('/LIMIT/i', $order) ? TRUE : FALSE;
        $countrows   = ($iffoundrows) ? ' SQL_CALC_FOUND_ROWS ' : '';
        $query       = ossim_query("SELECT $countrows users.*, HEX(uuid) AS uuid, HEX(template_id) AS template_id FROM users $args $order");
       
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $rs = $conn->Execute($query);
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return $list;
        } 
        
        $foundrows = 0;

        if($iffoundrows == TRUE)
        {
            $rf = $conn->Execute('SELECT FOUND_ROWS() AS total');
            
            if (!$rf) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return $list;
            }                    
            
            $foundrows = $rf->fields['total'];
        }
        else
        {
            $foundrows = $rs->RecordCount();
        }

            
        while (!$rs->EOF) 
        {
            if ($noperms || self::userAllowed($rs->fields['login']) > 1) 
            {
                $entities = (self::is_pro()) ? array_keys(Acl::get_all_entities_by_user($conn, $rs->fields['login'])) : array();
                
                $assets   = ($with_assets) ? self::get_user_assets($conn, $rs->fields['login']) : array();
                $sensors  = ($with_assets) ? self::get_user_sensors($conn, $rs->fields['login']) : array();
                
                $list[]   = new Session($rs->fields['login'], $rs->fields['pass'], $rs->fields['uuid'], $entities, $sensors, $assets, 
                    $rs->fields['template_id'], $rs->fields['name'], $rs->fields['email'], $rs->fields['company'], $rs->fields['department'], 
                    $rs->fields['language'], $rs->fields['enabled'], $rs->fields['is_admin'], 0, $rs->fields['timezone'], $rs->fields['last_logon_try'], 
                    $foundrows, $rs->fields['login_method']);
            }
            
            $rs->MoveNext();
        }
                
        return $list;
    }
    
    
    /**
    * This function returns an array with all users that belong to user scope
    *
    * @param object $conn  Database access object
    * @param object $user  [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_users_to_assign($conn, $user = NULL)
    {
        Ossim_db::check_connection($conn);
        
        $users = array();
        $user  = (empty($user)) ? self::get_session_user() : $user;
                    
        if (empty($user) && !empty($_SESSION['_user_vision']['users_to_assign']))
        {
            foreach ($_SESSION['_user_vision']['users_to_assign'] as $_user => $_perm)
            {
                 $users[$_user] = $_user;
            }
        }
        elseif (self::is_admin($conn, $user))
        {
            $users_list = self::get_list($conn);
                                    
            foreach($users_list as $u)
            {
                $users[$u->get_login()] = $u->get_login();
            }
        }
        elseif (self::is_pro())
        {
            $my_entities = Acl::get_my_entities($conn, '', FALSE);
                        
            if (!empty($my_entities))
            {    
                foreach ($my_entities as $entity_id => $entity) 
                {
                    $all_users = Acl::get_all_users_by_entity($conn, $entity_id);
                    
                    foreach ($all_users as $entity_users)
                    {
                        $users[$entity_users] = $entity_users; 
                    }
                }
            }
            else
            {
                $orph_users =  Acl::get_orph_users($conn);
                
                foreach ($orph_users as $orph_user)
                {
                    $users[$orph_user['login']] = $orph_user['login']; 
                }
            }
        }
        
        if (empty($users))
        {
            $users[$user] = $user; // myself
        }

        $where = "WHERE login in ('".implode("','",$users)."')";
        
        return self::get_list($conn, $where, ' ORDER BY login ASC', TRUE);        
    }
    
    
    /**
    * This functions returns user visibility for logged user or user passed as parameter
    *
    * This function returns the user visibility array. This array contains the following
    * keys:
    * 
    *     <pre>
    *     "user"               => array()    - Visibility of users within user scope
    *     "users_to_assign"    => array()    - Users belongs to user scope
    *     "ctx_where"          => ''         - Empty value (Open Source version),
    *     "sensor"             => array()    - Visibility of sensors within user scope,
    *     "asset"              => array()    - Assets (host and nets) belong to user filters
    *     "host_where"         => TRUE|FALSE - TRUE if user has host filter otherwise FALSE,
    *     "net_where"          => TRUE|FALSE - TRUE if user has net  filter otherwise FALSE,
    *     "host_where_ff"      => TRUE|FALSE - TRUE if user only has either host filter or net filter or sensor filter
    *                                          and he has not host and net filter, otherwise FALSE
    *     "net_where_ff"       => TRUE|FALSE - TRUE if user only has either host filter or net filter or sensor filter
    *                                          and he has not host and net filter, otherwise FALSE
    *     </pre>
    *
    * @param object $conn    Database access object
    * @param string $c_user  [Optional] User login to calculate visibility
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array  User visibility array
    */
    public static function get_user_vision($conn, $c_user = NULL) 
    {
        Ossim_db::check_connection($conn);
        
        define('NO_PERMS', 0);
        define('VISION_PERMS', 1);
        define('EDITING_PERMS', 2);
        
        $save_perm_in_tables = TRUE;        
       
        $conf        = $GLOBALS['CONF'];
        $default_ctx = str_replace('-', '', strtoupper($conf->get_conf('default_context_id')));
        
        if (empty($default_ctx)) 
        { 
            $default_ctx = '00000000000000000000000000000000';
        }
                
        $user_vision = array('user'               => array(), 
                             'users_to_assign'    => array(),
                             'ctx_where'          => '',
                             'sensor'             => array(),
                             'asset'              => array(),
                             'host_where'         => FALSE,
                             'net_where'          => FALSE,
                             'host_where_ff'      => FALSE,
                             'net_where_ff'       => FALSE
                          );
                             
        if (!empty($c_user))
        {
            ossim_valid($c_user, OSS_USER, 'illegal:' . _('User'));
                
            if (!ossim_error())
            {
                $c_users = self::get_list($conn, "WHERE login='$c_user'");
                $c_user  = array_shift($c_users);
                
                if  (is_object($c_user) && !empty($c_user))
                {
                    $user = $c_user->get_login();
                    
                    // To load all assets in memory, and skip live perms tables updates
                    $save_perm_in_tables = FALSE;                   
                }
            }
        }
                
        $user = (empty($user)) ? self::get_session_user() : $user;
                        
        
        $am_i_admin = self::is_admin($conn, $user);

               
        $user_vision['ctx'] = $default_ctx;        
        
        if (!$am_i_admin) 
        {         
            /************************* Asset Restriction (User level) *************************
            *
            * We'r gonna load user's assets bcz these are more restrictive
            *
            **********************************************************************************/
            
            
            // Asset restriction (User level)
            $user_asset_restriction = FALSE;
            
                                
            //User Restriction
            $assets  = self::get_user_assets($conn, $user);
            $sensors = self::get_user_sensors($conn, $user); 
                       
            if (is_array($assets) && !empty($assets)) // User has asset restrictions
            {
                $user_asset_restriction = TRUE;
                
                foreach ($assets as $asset_id => $asset) 
                {
                    if ($asset->get_asset_type() == 'host')
                    {
                        $user_vision['asset']['host'][$asset_id] = EDITING_PERMS;
                    } 
                    else
                    {
                        $user_vision['asset']['net'][$asset_id] = EDITING_PERMS;
                    }
                }
            }

            
            if (is_array($sensors) && !empty($sensors)) // User has sensor restrictions
            {                
                $user_asset_restriction = TRUE;
                               
                foreach ($sensors as $sensor_id => $sensor_obj) 
                {
                    $user_vision['sensor'][$sensor_id]            = VISION_PERMS;
                    $user_vision['sensor'][$sensor_obj->get_ip()] = VISION_PERMS;
                }
                             
                //Get assets related to sensors                
               
                foreach ($user_vision['sensor'] as $sensor_key => $perm) 
                {
                    if (valid_hex32($sensor_key)) 
                    {
                        list($sensor_hosts, $sensor_nets) = Av_sensor::get_related_assets($conn, $sensor_key, $user_vision['ctx_where']);
                        
                        foreach ($sensor_hosts as $h_id) 
                        {
                            $user_vision['asset']['host'][$h_id] = EDITING_PERMS;
                        }
                        
                        foreach ($sensor_nets as $n_id) 
                        {
                            $user_vision['asset']['net'][$n_id] = EDITING_PERMS;
                        }
                    }
                }
            }
            
            
           /************************************************************************************
            *
            * Sensors related to assets
            *
            *************************************************************************************/
            
            
            // Assets related to sensors
            if (empty($user_vision['sensor']) && is_array($user_vision['asset']['host']))
            {
                foreach ($user_vision['asset']['host'] as $aid => $_perms)
                {
                    $sensors = Asset_host_sensors::get_sensors_by_id($conn, $aid);
                    
                    foreach ($sensors as $id => $s_data) 
                    {
                        $user_vision['sensor'][$id] = VISION_PERMS;
                        $user_vision['sensor'][$s_data['ip']] = VISION_PERMS;
                    }
                }
            }
            
            if (empty($user_vision['sensor']) && is_array($user_vision['asset']['net']))
            {
                foreach ($user_vision['asset']['net'] as $aid => $_perms)
                {
                    $sensors = Asset_net_sensors::get_sensors_by_id($conn, $net_id);
                    
                    foreach ($sensors as $id => $s_data) 
                    {
                        $user_vision['sensor'][$id] = VISION_PERMS;
                        $user_vision['sensor'][$s_data['ip']] = VISION_PERMS;
                    }
                }
            }
                                    
                
            if ($save_perm_in_tables) 
            {
                $params = array($user);
                
                // Clean live perms tables
                $query = "DELETE FROM user_host_perm WHERE login = ?";
                $rs = $conn->Execute($query, $params);
       
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                
                $query = "DELETE FROM user_net_perm WHERE login=?";
                $rs = $conn->Execute($query, $params);
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                
                $query = "DELETE FROM user_sensor_perm WHERE login=?";
                $rs = $conn->Execute($query, $params);
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }                                 
                
                // Fill live perms tables
                foreach ($user_vision['asset'] as $asset_type=>$assets_arr) 
                {
                    foreach ($assets_arr as $asset_id => $perm) 
                    {
                        $query = "INSERT IGNORE INTO user_".$asset_type."_perm (login, asset_id) VALUES (?, UNHEX(?))";
                        
                        $rs = $conn->Execute($query, array($user, $asset_id));
                        
                        if (!$rs) 
                        {
                            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                        }                  
                    }
                }
                
                // Subnets of Allowed Nets
                if (is_array($user_vision['asset']['net']))
                {
                    foreach ($user_vision['asset']['net'] as $net_id => $perm)
                    {
                        //My subnets (To display alarms, events, ...)        
                        $my_subnets = Asset_net::get_my_subnets($conn, $net_id);
                        
                        foreach ($my_subnets as $net_id => $net_data)
                        {                                                
                            // Fill live perms tables
                            $params = array($user, $net_id);
                            $query  = "INSERT IGNORE INTO user_net_perm (login, asset_id) VALUES (?, UNHEX(?))";
                            
                            $rs = $conn->Execute($query, $params);
                            
                            if (!$rs) 
                            {
                                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                            }                      
                        }
                    }
                }
                
                // Allowed Sensors
                if (is_array($user_vision['sensor']))
                {
                    foreach ($user_vision['sensor'] as $sensor_id => $perm)
                    {
                        if (preg_match("/^[A-F0-9]{32}$/", $sensor_id)) 
                        {
                            // Fill live perms tables
                            $params = array($user, $sensor_id);
                            $query  = "INSERT IGNORE INTO user_sensor_perm (login, sensor_id) VALUES (?, UNHEX(?))";
                            
                            $rs = $conn->Execute($query, $params);
                            
                            if (!$rs) 
                            {
                                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                            }
                        }
                    }
                }
                            
            }                
            
           
           
            /**********************************************************************************
            *
            * Set control variables for assets restriction
            *
            **********************************************************************************/            
            
            
            if (count($user_vision['asset']['host']) > 0) 
            {
                $user_vision['host_where'] = TRUE;
            }
            
            if (count($user_vision['asset']['net']) > 0) 
            {
                $user_vision['net_where'] = TRUE;
            }
            
            if (!$user_vision['host_where'] && $user_vision['net_where']) //Only Nets are allowed
            { 
                $params = array($user, 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF');
                $query  = "INSERT IGNORE INTO user_host_perm (login, asset_id) VALUES (?, UNHEX(?))";
                
                $rs = $conn->Execute($query, $params);
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                
                $user_vision['host_where']    = TRUE;                
                $user_vision['host_where_ff'] = TRUE;
            }
            elseif ($user_vision['host_where'] && !$user_vision['net_where']) //Only Hosts are allowed
            { 
                $params = array($user, 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF');
                $query  = "INSERT IGNORE INTO user_net_perm (login, asset_id) VALUES (?, UNHEX(?))";
                
                $rs = $conn->Execute($query, $params);
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                
                $user_vision['net_where']    = TRUE;
                $user_vision['net_where_ff'] = TRUE;
            }
            elseif(!$user_vision['host_where'] && !$user_vision['net_where'])
            {                
                
                $user_vision['host_where']    = TRUE;
                $user_vision['host_where_ff'] = TRUE;
                
                $user_vision['net_where']    = TRUE;
                $user_vision['net_where_ff'] = TRUE;
                
                $params = array($user, 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF');
                $query  = "INSERT IGNORE INTO user_host_perm (login, asset_id) VALUES (?, UNHEX(?))";
                
                $rs = $conn->Execute($query, $params);                             
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                
                $query = "INSERT IGNORE INTO user_net_perm (login, asset_id) VALUES (?, UNHEX(?))";
                
                $params = array($user, 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF');
                $rs     = $conn->Execute($query, $params);
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                                
                if ($user_asset_restriction)
                {                   
                    $user_vision['host_where_ff'] = FALSE;
                    $user_vision['net_where_ff']  = FALSE;
                }                             
            }
                                       
                             
            if ($save_perm_in_tables)
            {                            
                // Clean memory for session perms (in another calls to user_vision like reports we need the asset array)
                unset($user_vision['asset']);
                
                
                /**********************************************************************************
                *
                * Asset Restriction Optimization for Non-admin users
                *
                **********************************************************************************/  
                
                
                $query = "SELECT id FROM host 
                          WHERE ctx in (".$user_vision['ctx_where'].") AND id NOT IN (SELECT asset_id FROM user_host_perm WHERE login = '".$user."') 
                          LIMIT 1";
                
                $rs = $conn->Execute($query);
                
                if ($rs !== FALSE && count($rs->fields['id']) == 0)
                {
                    // Clean live perms tables
                    $query = 'DELETE FROM user_host_perm WHERE login = ?';
                    $conn->Execute($query, array($user));
                    
                    $user_vision['host_where']    = FALSE;
                    $user_vision['host_where_ff'] = FALSE;           

                    if ($user_vision['net_where_ff'])
                    {
                        $user_vision['net_where'] = FALSE;
                    }
                              
                }
                
                           
                
                $query = "SELECT id FROM net 
                          WHERE ctx in (".$user_vision['ctx_where'].") AND id NOT IN (SELECT asset_id FROM user_net_perm WHERE login = '".$user."') 
                          LIMIT 1";
                
                $rs = $conn->Execute($query);           
                
                if ($rs !== FALSE && count($rs->fields['id']) == 0)
                {
                    // Clean live perms tables
                    $query = 'DELETE FROM user_net_perm WHERE login = ?';
                    $conn->Execute($query, array($user));
                    
                    $user_vision['net_where']    = FALSE;
                    $user_vision['net_where_ff'] = FALSE;                      

                    if ($user_vision['host_where_ff'])
                    {
                        $user_vision['host_where'] = FALSE;
                    }

                }                
            }          
        }                  
                 
        return $user_vision;
    }
    
        
    /**
    * This function returns an array with asset restrictions (host and nets) associated to user (Assets that user can see) 
    *
    * @param object $conn   Database access object
    * @param string $login  [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array                
    */
    public static function get_user_assets($conn, $login) 
    {
        Ossim_db::check_connection($conn);
        
        $assets  = array();
        
        $params  = array($login);
        $query   = ossim_query("SELECT HEX(asset_id) AS asset_id FROM acl_assets WHERE login = ?");        
        
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return $assets;            
        } 
        
        while (!$rs->EOF) 
        {
            // Asset is a host or a net
            if (($obj = Asset_net::get_object($conn, $rs->fields['asset_id'], TRUE)) != NULL)
            {
                $assets[$obj->get_id()] = $obj;
            } 
            elseif (($obj = Asset_host::get_object($conn, $rs->fields['asset_id'], TRUE)) != NULL) 
            {
                $assets[$obj->get_id()] = $obj;
            }
            
            $rs->MoveNext();
        }
        
        return $assets;
    }
    
    
    /**
    * This function returns an array that contains sensor restrictions associated to user (Sensors that user can see) 
    *
    * @param object $conn   Database access object
    * @param string $login  [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array                
    */
    public static function get_user_sensors($conn, $login) 
    {
        Ossim_db::check_connection($conn);
        
        $sensors = array();
        
        $params = array($login);
        $query  = ossim_query("SELECT HEX(sensor_id) AS sensor_id FROM acl_login_sensors WHERE login = ?");
               
        $rs = $conn->Execute($query, $params);        
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return $sensors;
        }        
        
        while (!$rs->EOF) 
        {
            $obj = Av_sensor::get_object($conn, $rs->fields['sensor_id'], TRUE); 
            
            if (is_object($obj) && !empty($obj)) 
            {
                $sensors[$obj->get_id()] = $obj;
            }
            
            $rs->MoveNext();
        }
        
        return $sensors;
    }
    
    
    /**
    * This function disables all users whose account has expired
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function check_all_user_accounts($conn) 
    {
        Ossim_db::check_connection($conn); 
        
        $query = "SELECT login FROM users WHERE login != 'admin'";
        
        $rs = $conn->Execute($query);        
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 
        
        while (!$rs->EOF) 
        {
            $clogin = $conn->GetOne("SELECT login FROM users WHERE login = '".$rs->fields['login']."' AND expires > '".gmdate('Y-m-d H:i:s')."'");
            
            if ($clogin == '') 
            {
                $conn->Execute("UPDATE users SET enabled=0 WHERE login= '".$rs->fields['login']."'");
            }
            
            $rs->MoveNext();
        }
        
        return TRUE;
    }
    
        
    /**
    * This function returns TRUE if it's first time that user is logged in the system
    *
    * @return boolean             
    */   
    public function get_first_login() 
    {
		$conn     = $this->conn;
		$pass     = md5($this->pass);
		$pass_sha = hash('sha256', $this->pass);
        $login    = $this->login;
		
		$params = array($login, $pass, $pass_sha);
        $query  = "SELECT * FROM users WHERE login = ? AND (pass = ? OR pass = ?)";
		
		$rs = $conn->Execute($query, $params);
		
		if(!$rs)
		{
    		Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
    		
    		return FALSE;
		}		
                
        if (!$rs->EOF) 
        {
			$first_login = $rs->fields['first_login'];
            
            return ($first_login) ? TRUE : FALSE;
        }
	}
    
    
    /**
    * This function disables first login interface for user $user
    *
    * @param object $conn   Database access object
    * @param string $login  User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean             
    */   
    public static function disable_first_login($conn, $login) 
    {        
        Ossim_db::check_connection($conn);
        
        $params = array($login);
        $query  = "UPDATE users SET first_login = 0 WHERE login = ?";
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }   
        
        return TRUE;
    }
    
    
    /**
    * This function checks if login credentials are valid
    *
    * @return boolean                
    */ 
    public function login() 
    {       
        $conf = $GLOBALS['CONF'];
        
        if (!$conf)
        {           
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }         
               
        $version      = $conf->get_conf('ossim_server_version');
        $login_method = ($conf->get_conf('login_enable_ldap') == 'yes') ? 'ldap' : 'pass'; // Login method is LDAP or PASSWORD
        
        $conn      = $this->conn;
        $orig_pass = $this->pass;
        
        $pass      = (preg_match('/^[A-Fa-f0-9]{32}$/',$this->pass) && $this->external) ? $this->pass : md5($this->pass); //Used in Wizard Scheduler and Remote Interfaces
        $pass_sha  = (preg_match('/^[A-Fa-f0-9]{64}$/',$this->pass) && $this->external) ? $this->pass : hash('sha256', $this->pass); //Used in Wizard Scheduler and Remote Interfaces
        $login     = $this->login;
        
        
        $params1 = array($login);        
        $query_1 = 'SELECT * FROM users WHERE login = ?';        
        
        $rs_1 = $conn->Execute($query_1, $params1);
        
        if (!$rs_1->EOF) 
        {
            // Specific login method for Admin
            if ($rs_1->fields['login_method'] == 'pass' || $login == AV_DEFAULT_ADMIN) 
            { 
                $login_method = 'pass'; 
            }
        }
        
        unset($query_1, $rs_1, $params1);        
                
        $query  = "SELECT *, HEX(uuid) AS uuid FROM users WHERE login = ? AND (pass = ? OR pass = ?)";
        $params = array($login, $pass, $pass_sha);
        $rs     = $conn->Execute($query, $params);        
        
        //Case 1: Login method is 'pass' or external process(AV Report Scheduler or Remote Interface) are trying to log in the system
        if (($login_method != 'ldap' 
                || preg_match('/^[A-Fa-f0-9]{32}$/',$this->pass)
                || preg_match('/^[A-Fa-f0-9]{64}$/',$this->pass)) && (!$rs->EOF))  
        {                        
            //Generate a new session identifier
            session_regenerate_id(); 
            $_SESSION['_user_language'] = $rs->fields['language'];
            $_SESSION['_is_admin']      = $rs->fields['is_admin'];
            $_SESSION['_timezone']      = self::get_timezone($rs->fields['timezone']);
            
            //Get secure_id
            if (valid_hex32($rs->fields['uuid']) && $rs->fields['uuid'] != '0x0')
            {
                $_SESSION['_secureid'] = $rs->fields['uuid'];
            }
            else
            {
                self::set_secure_id($conn, $login, sha1($login.'#'.$pass));
                $_SESSION['_secureid'] = sha1($login."#".$pass);
            }
                                    
            ossim_set_lang($rs->fields['language']);
            $this->allowed_menus        = $this->allowedMenus($login);
            
            $_SESSION['_user']          = $login;
            $_SESSION['_remote_login']  = base64_encode(Util::encrypt($login.'####'.$pass.'####'.$pass_sha, $conf->get_conf('remote_key')));
            $_SESSION['_allowed_menus'] = $this->allowed_menus;
            
            if (preg_match('/pro|demo/i',$version)) 
            {
                $_SESSION['_version']     = 'pro';
                $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
            } 
            else 
            {
                $_SESSION['_version']     = 'opensource';
                $_SESSION['_user_vision'] = self::get_user_vision($conn);
            }

            //License info 
            $_license                 = self::get_system_license();
            $_SESSION['_exp_date']    = $_license['appliance']['expire'];
            $_SESSION['_max_devices'] = $_license['appliance']['devices'];


            //Update Last login date
            self::update_logon_try($login);            
            
            Session_activity::insert($conn);
            
            if($login == 'admin' || intval($rs->fields['is_admin']) == 1)
            {
                $this->check_all_user_accounts($conn);
            }

            return TRUE;
        }
        
                
        //Case 2: LDAP Login
        if ($login_method == 'ldap' && self::login_ldap($login, $orig_pass)) 
        {
            $params = array($login);
            $query  = 'SELECT *, HEX(uuid) AS uuid FROM users WHERE login = ?';
                                            
            //Logged user exists in Database
            if (($rs = $conn->Execute($query, $params)) && ($rs->EOF)) 
            {                                                           
                if($conf->get_conf('login_ldap_require_a_valid_ossim_user') == 'no')
                {
                    //Create the user with default perms
                    $timezone = trim(`head -1 /etc/timezone`);
                                                            
                    if (preg_match('/pro|demo/i',$version))
                    {
                        $perms      = $conf->get_conf('login_create_not_existing_user_menu');
                        $entities[] = $conf->get_conf('login_create_not_existing_user_entity');
                        
                        $error = self::insert($conn, $login, 'ldap', hash('sha256', $orig_pass), $login, '', $perms, $entities, array(), array(), NULL , NULL, $conf->get_conf('language') ,0, $timezone, 0);
                    }
                    else
                    {                                                              
                        list($menu_perms, $perms_check) = self::get_default_perms($conn);
                        $perms = array();
                                                                    
                        foreach($menu_perms as $menus) 
                        {
                            foreach($menus as $key => $menu)
                            {
                                $perms[$key] = TRUE;
                            }
                        }
                                                
                        $template_id = self::update_template($conn, $login.'_perms', $perms);
                        
                        $error       = self::insert($conn, $login, 'ldap', hash('sha256', $orig_pass), $login, '', $template_id, '', array(), array() , '', '', $conf->get_conf('language') ,0, $timezone, 0);
                    }              
                    
                    User_config::copy_panel($conn, $login);
                    
                    $rs = $conn->Execute($query, $params); //Get information for created user
                }
                else
                {
                    return FALSE;
                }
            }
            else
            {
                //Password update. Necessary for AV Report Scheduler and Remote Interfaces (save last password in Database)
                self::change_pass($conn, $login, $orig_pass, NULL, FALSE);
            }

            $_SESSION['_user_language'] = $rs->fields['language'];
            $_SESSION['_is_admin']      = $rs->fields['is_admin'];
            $_SESSION['_timezone']      = self::get_timezone($rs->fields['timezone']);
            
            //Get secure_id
            if (valid_hex32($rs->fields['uuid']) && $rs->fields['uuid'] != '0x0')
            {
                $_SESSION['_secureid'] = $rs->fields['uuid'];
            }
            else
            {
                self::set_secure_id($conn, $login, sha1($login.'#'.$pass));
                $_SESSION['_secureid'] = sha1($login.'#'.$pass);
            }
            
            ossim_set_lang($rs->fields['language']);
            $this->allowed_menus = $this->allowedMenus($login);
            
            //Generate a new session identifier
            session_regenerate_id(); 
            $_SESSION['_user']          = $login;
            $_SESSION['_remote_login']  = base64_encode(Util::encrypt($login.'####'.$pass.'####'.$pass_sha, $conf->get_conf('remote_key')));
            $_SESSION['_allowed_menus'] = $this->allowed_menus;
            
            if (preg_match('/pro|demo/i',$version)) 
            {
                $_SESSION['_version']     = 'pro';
                $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
            } 
            else 
            {
                $_SESSION['_version']     = 'opensource';
                $_SESSION['_user_vision'] = self::get_user_vision($conn);
            }

            //License info 
            $_license                 = self::get_system_license();
            $_SESSION['_exp_date']    = $_license['appliance']['expire'];
            $_SESSION['_max_devices'] = $_license['appliance']['devices'];

            
            //Update last login date
            self::update_logon_try($login);            
            
            Session_activity::insert($conn);
            
            if($login == 'admin' || intval($rs->fields['is_admin']) == 1)
            {
                $this->check_all_user_accounts($conn);
            }
            
            return TRUE;
        }
                        
        return FALSE;
    }

    
    /**
    * This function returns TRUE if user $login has logged in three times or more
    *
    * This functionality is used by OSSIM in cloud mode
    *
    * @param string $login  [Optional] User login to check
    *
    * @return boolean             
    */
    public static function login_three_times($login) 
    {
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $query   = "SELECT COUNT(*) 
                    FROM log_action 
                    WHERE info LIKE  'User ".$login." logged in' 
                    AND date > (SELECT MAX(date) FROM log_action WHERE info LIKE  '%User ".$login." created' LIMIT 1)";

        $clogins = $conn->GetOne($query);
        
        $db->close();
        
        if (intval($clogins) > 3) 
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    
    
    /**
    * This function checks if login credentials are valid using LDAP Authentication
    *
    * @param string $login     [Optional] User login to check
    * @param string $password  [Optional] User password    
    *
    * @return boolean                
    */    
    public static function login_ldap($login = 'none', $password = 'none') 
    {
        if ($login == '')
        {
            $login = 'none';
        }
        
        if ($password == '')
        {
            $password = 'none';
        }

        ossim_valid($login, OSS_USER, OSS_NULLABLE, 'illegal:' . _('User name'));
        ossim_valid($password, OSS_PASSWORD       , 'illegal:' . _('Password'));

        if (ossim_error()) 
        {
            Av_exception::write_log(Av_exception::USER_ERROR, ossim_get_error_clean());
            
            return FALSE;
        }

        $conf = $GLOBALS['CONF'];
        
        $ldap_server = $conf->get_conf('login_ldap_server');
        $ldap_ssl    = $conf->get_conf('login_ldap_ssl');
        $ldap_port   = $conf->get_conf('login_ldap_port');
        $ldap_baseDN = $conf->get_conf('login_ldap_baseDN');
        $ldap_filter = $conf->get_conf('login_ldap_filter_to_search');
        $ldap_bindDN = $conf->get_conf('login_ldap_bindDN');
        $ldap_pass   = $conf->get_conf('login_ldap_valid_pass');

        
        $password1 = utf8_encode($password);
        
        if (!function_exists('ldap_connect')) 
        {       
            $log_msg = _('LDAP authentication enabled in the configuration, but the php ldap extension is not available');
            
            Av_exception::write_log(Av_exception::USER_ERROR, $log_msg);
            
            return FALSE;
        }
               
        $serverconn = ($ldap_ssl == 'yes')? 'ldaps://'.$ldap_server : $ldap_server;
        $ldapconn   = ldap_connect($serverconn, intval($ldap_port));
        
        if ($ldapconn == FALSE)
        {            
            $log_msg = _('Could not connect to LDAP server');
            
            Av_exception::write_log(Av_exception::USER_ERROR, $log_msg);
            
            return FALSE;
        }    
        
        ldap_set_option($ldapconn,LDAP_OPT_PROTOCOL_VERSION,3);
        ldap_set_option($ldapconn,LDAP_OPT_REFERRALS,0);
            
        if ($ldapconn) 
        {
            //Authentication
            $ldapbind = @ldap_bind($ldapconn, $ldap_bindDN, $ldap_pass);
            
            //Check bind
            if (!$ldapbind) 
            {                             
                $log_msg = _('Unable bind to LDAP');
            
                Av_exception::write_log(Av_exception::USER_ERROR, $log_msg);
            
                return FALSE;
            }
                                    
            $ldap_filter = preg_replace('/%u/',$login,$ldap_filter);
            $search      = ldap_search($ldapconn,$ldap_baseDN,$ldap_filter);
            $info        = ldap_get_entries($ldapconn,$search);
            
            if ($info[0]['dn'] == '') 
            {
                return FALSE;
            }
            
            $ldapbind = @ldap_bind($ldapconn, $info[0]["dn"], $password1);
                                    
            if ($ldapbind) 
            {
                $ret = TRUE;
            } 
            else
            {
                $ret = FALSE;
            }
        }
                
        return $ret;
    }
    
        
    /**
    * This function is used when an external process (AV Report Scheduler or Remote Interface) wants to log into the system
    *
    * @return boolean                
    */            
    public static function external_login()
    {                       
        if (!empty($_SESSION['_user'])) 
        {
            return FALSE;
        }                           
                 
        if (array_key_exists('user', $_POST) && array_key_exists('pass', $_POST))
        {
            $user = $_POST['user'];
            $pass = $_POST['pass'];
        }     
              
        if (array_key_exists('login', $_POST)) 
        {            
            $conf = $GLOBALS['CONF'];
            
            if (!$conf)
            {                   
                $conf = new Ossim_conf();                
                $GLOBALS['CONF'] = $conf;
            }
                       
            $remote_key  = $conf->get_conf('remote_key'); 
             
            list($user,$pass,$pass_sha) = explode("####",Util::decrypt(base64_decode($_POST["login"]), $remote_key), 3);
        }
        
        // Temporaly compatible with both md5 and sha256
        if (isset($user) && 
            ((isset($pass) && preg_match('/^[A-Fa-f0-9]{32}$/',$pass))
             || (isset($pass_sha) && preg_match('/^[A-Fa-f0-9]{64}$/',$pass_sha)))) 
        {
            // First try with sha256
            $sess = new Session($user, $pass_sha);
            
            $sess->external = TRUE;
            
            if (!$sess->login())
            {
                // If it fails, try with md5
                $sess = new Session($user, $pass);
                
                $sess->external = TRUE;
                
                $sess->login();
            }
        }    
        
        return FALSE;
    }
    
    
    /**
    * This function checks if logged user exists in Database
    *
    * @return boolean                
    */ 
    public function is_logged_user_in_db() 
    {
        $conn   = $this->conn;
        $login  = $this->login;
        
        $params = array($login);
        $query  = 'SELECT * FROM users WHERE login = ?';
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        if (!$rs->EOF) 
        {
            return TRUE;
        }     
    }
    
    
    /**
    * This function returns TRUE if logged user is disabled, otherwise it returns FALSE
    *
    * @return boolean                
    */  
    public function is_user_disabled() 
    {
        $conn  = $this->conn;
        $login = $this->login;
        
        $clogin = $conn->GetOne("SELECT login FROM users WHERE login = '".$login."' AND expires > '".gmdate('Y-m-d H:i:s')."'");
        
        if ($clogin == '') 
        {
            $this->conn->Execute("UPDATE users SET enabled=0 WHERE login= '".$login."'");
        }
        
        $conf             = $GLOBALS['CONF'];
        $lockout_duration = intval($conf->get_conf("unlock_user_interval")) * 60;        
        
        
        $params = array($login);
        $query  = "SELECT * FROM users WHERE login = ? AND enabled <= 0";
               
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        if (!$rs->EOF) 
        {
            // User must be unlocked by admin
            if ($lockout_duration == 0 || $rs->fields['enabled'] == 0) 
            { 
                return TRUE; 
            }
                                    
            //Auto-enable if account lockout duration expires
            if ($rs->fields['last_logon_try'] != '0000-00-00 00:00:00' 
                && time() - strtotime($rs->fields['last_logon_try']) >= $lockout_duration) 
            {
                $conn->Execute('UPDATE users SET enabled = 1 WHERE login=?', $params);
                
                return FALSE;
            }
            
            return TRUE;
        }
        
        return FALSE;
    }
    
        
    /**
    * This function destroys current user session and disconnects the current user
    *
    * @return void 
    */
    public function logout()
    {        
        Session_activity::delete();
        
        Session_activity::delete_session();
        
        $conf           = $GLOBALS['CONF'];
        $ossim_link     = $conf->get_conf('ossim_link');
        $login_location = preg_replace("/(\/)+/","/", $ossim_link.'/session/login.php');
        
        unset($_SESSION);
        header("Location: $login_location");
        
        exit();
    }
    
    /**
    * This function updates the last login date of user $login
    *
    * @param string $login  User login
    */
    public function update_logon_try($login) 
    {
        $this->conn->Execute("UPDATE users SET last_logon_try = NOW() WHERE login = ?", array($login));
    }
       
      
    /**
    * This function inserts a new user into Database
    *
    * @param object  $conn           Database access object
    * @param string  $login          User login
    * @param string  $login_method   Login method ('pass' or 'ldap')
    * @param string  $pass           SHA256 User Password
    * @param string  $name           Username
    * @param string  $email          User email
    * @param string  $template_id    Template ID associated to user
    * @param array   $entities       Entities related to user
    * @param array   $sensors        Sensor restriction (Sensors that user can see)
    * @param array   $assets         Asset restriction (Assets that user can see)
    * @param string  $company        User company (Only in Open Source version)
    * @param string  $department     User departament (Only in Open Source version)
    * @param string  $language       User language (User Interface Language)
    * @param int     $first_login    Enable/disable first login interface for next login
    * @param string  $timezone       User Timezone
    * @param int     $is_admin       User type (Admin User or Non-Admin User)
    *
    * @throws Exception  If a connection error occurred
    *    
    * @return boolean 
    */        
    public static function insert($conn, $login, $login_method, $pass, $name, $email, $template_id, $entities, $sensors, $assets, 
        $company, $department, $language, $first_login, $timezone, $is_admin) 
    {
        Ossim_db::check_connection($conn); 
        
        $pass  = hash('sha256', $pass);
        $login = strtolower($login);        
        
        $params = array(
            $login,
            $login_method,
            $name,
            $pass,
            $email,
            $company,
            $department,
            $template_id,
            $language,
            $first_login,
            $timezone,
            $is_admin,
            sha1($login.'#'.$pass)
        );
        
        
        /* Insert into OSSIM database */
        $query = "INSERT INTO users (login, login_method, name, pass, email, company, department, template_id, language, first_login, timezone, is_admin, uuid) VALUES (?, ?, ?, ?, ?, ?, ?, UNHEX(?), ?, ?, ?, ?, UNHEX(?))";
        
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        $conf    = $GLOBALS['CONF'];
        $version = $conf->get_conf('ossim_server_version');
        
        if (preg_match('/pro|demo/i',$version) && is_array($entities)) 
        {
            foreach ($entities as $entity_id) 
            {
                $params = array($login, $entity_id);
                $query  = "REPLACE INTO acl_entities_users (login, entity_id) VALUES (?, UNHEX(?))";
                
                $rs = $conn->Execute($query, $params);             
                
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                    
                    return FALSE;
                }                
            }
        }
        
        /* Set allowed nets and hosts */
        foreach ($assets as $asset_id) 
        {
            $query  = "INSERT IGNORE INTO acl_assets (login, asset_id) VALUES (?, UNHEX(?))";
            $params = array($login, $asset_id);
            
            $rs = $conn->Execute($query, $params);             
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;
            } 
        }
        
        /* Set allowed sensors */
        foreach ($sensors as $sensor_id) 
        {
            $query  = "INSERT IGNORE INTO acl_login_sensors (login, sensor_id) VALUES (?, UNHEX(?))";
            $params = array($login, $sensor_id);
                                          
            $rs = $conn->Execute($query, $params);             
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;
            }
        }
        
        $conn->Execute("CALL acl_user_permissions(?)", array($login));
        
        $infolog = array($login);
        Log_action::log(4, $infolog);
        
        return TRUE;
    }
        
        
    /**
    * This function updates an user into Database
    *
    * @param object  $conn           Database access object
    * @param string  $login          User login
    * @param string  $login_method   Login method ('pass' or 'ldap')
    * @param string  $name           Username
    * @param string  $email          User email
    * @param string  $template_id    Template ID associated to user
    * @param array   $entities       Entities related to user
    * @param array   $sensors        Sensor restriction (Sensors that user can see)
    * @param array   $assets         Asset restriction (Assets that user can see)
    * @param string  $company        User company (Only in Open Source version)
    * @param string  $department     User departament (Only in Open Source version)
    * @param string  $language       User language (User Interface Language)    
    * @param int     $first_login    Enable/disable first login interface for next login
    * @param string  $timezone       User Timezone
    * @param int     $is_admin       [Optional] User type (Admin User or Non-Admin User) 
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean 
    */   
    public static function update($conn, $login, $login_method, $name, $email, $template_id, $entities, $sensors, $assets, 
        $company, $department, $language, $first_login, $timezone, $is_admin = 0) 
    {
        /* modify OSSIM database */
                
        Ossim_db::check_connection($conn); 
        
        $params = array(
            $name,
            $login_method,
            $email,
            $company,
            $department,
            $language,
            $template_id,
            $first_login,
            $is_admin,
            $timezone,
            $login
        );
        
        $query = "UPDATE users SET name = ?, login_method = ?, email = ?, company = ?, department = ?, language = ?, template_id = UNHEX(?), first_login = ?, is_admin = ?, timezone = ? WHERE login = ?";
        
        ossim_set_lang($language);
        
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        if (self::is_pro()) 
        {           
            $params = array($login);
            $query  = "DELETE FROM acl_entities_users WHERE login = ?";
            
            $rs = $conn->Execute($query, $params);             
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;
            }
            
            if (is_array($entities)) 
            {
                foreach ($entities as $entity_id) 
                {
                    $query  = "REPLACE INTO acl_entities_users (login, entity_id) VALUES (?, UNHEX(?))";
                    $params = array($login, $entity_id);
                                       
                    $rs = $conn->Execute($query, $params);             
                
                    if (!$rs) 
                    {
                        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                        
                        return FALSE;
                    }
                }
            }
        }
        
        /* Set allowed assets */
        $query  = "DELETE FROM acl_assets WHERE login = ?";
        $params = array($login);
       
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        foreach ($assets as $asset_id) 
        {
            $params = array($login, $asset_id);
            $query  = "INSERT IGNORE INTO acl_assets (login, asset_id) VALUES (?, UNHEX(?))";
                                       
            $rs = $conn->Execute($query, $params);             
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;
            }
        }
        
        /* Set allowed sensors */
        $params = array($login);
        $query  = "DELETE FROM acl_login_sensors WHERE login = ?";
                
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }

        
        foreach ($sensors as $sensor_id) 
        {
            $params = array($login, $sensor_id);
            $query  = "INSERT IGNORE INTO acl_login_sensors (login, sensor_id) VALUES (?, UNHEX(?))";                                     
            
            $rs = $conn->Execute($query, $params);             
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;
            }
        }
        
        $conn->Execute("CALL acl_user_permissions(?)", array($login));

        $infolog = array($login);
        Log_action::log(6, $infolog);
    }
    
        
    /**
    * This function updates some data of an user into Database
    *
    * This functions is used by Non-privileged users to update their own data
    *
    * @param object  $conn           Database access object
    * @param string  $login          User login
    * @param string  $login_method   Login method ('pass' or 'ldap')
    * @param string  $name           Username
    * @param string  $email          User email
    * @param string  $company        User company (Only in Open Source version)
    * @param string  $department     User departament (Only in Open Source version)
    * @param string  $language       User language (User Interface Language)
    * @param int     $first_login    Enable/disable first login interface for next login
    * @param int     $is_admin       [Optional] User type (Admin User or Non-Admin User)  
    * @param string  $timezone       [Optional] User Timezone 
    *
    * @throws Exception  If a connection error occurred
    *    
    * @return int 
    */   
    public static function update_user_light($conn, $login, $login_method, $name, $email, $company, $department, 
        $language, $first_login, $is_admin = 0, $timezone = '') 
    {
        Ossim_db::check_connection($conn);
        
        $params = array(
            $login_method,
            $name,
            $email,
            $company,
            $department,
            $language,
            $first_login,
            $is_admin
        );
        
        if ($timezone != '')
        {
            $params[] = $timezone;
        }
        
        //print_r($params);
        $query = "UPDATE users SET login_method=?,name=?,email=?,company=?,department=?,language=?,first_login=?,is_admin=?".(($timezone != '') ? ",timezone=?" : '')." WHERE login='$login'";
                     
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return -1;
        }         
        
        return 0;
    }
    
        
    /**
    * This function deletes the user $user
    *
    * @param object $conn   Database access object
    * @param string $login  User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean 
    */
    public static  function delete_user($conn, $login) 
    {
        Ossim_db::check_connection($conn);                 
                
        $user_list   = self::get_list($conn, "WHERE login='$login'");
        $template_id = $user_list[0]->get_template_id();
        
        $params = array($login);
        $query  = "DELETE FROM users WHERE login = ?";        
        
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }   
        
        if (!self::is_pro())
        {
            self::delete_template($conn, $template_id);
        }
        
        $query = "DELETE FROM acl_assets WHERE login = ?";
        
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 
        
        $query = "DELETE FROM acl_entities_users WHERE login = ?";
        
        $rs = $conn->Execute($query, $params);             
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 

        $query = "DELETE FROM acl_login_sensors WHERE login = ?";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 
                        
        $query = "DELETE FROM custom_report_scheduler WHERE  user = ?";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 

        $conn->Execute("CALL acl_user_permissions(?)", array($login));        
                        
        User_config::deleteall($conn,$login);

        $infolog = array($login);
        Log_action::log(3, $infolog);
        
        return TRUE;
    }
    
    
    /**
    * This function updates the user language
    *
    * @param object $conn      Database access object
    * @param string $login     User login to search
    * @param string $language  New user language
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean 
    */
    public static function change_user_language($conn, $login, $language) 
    {        
        Ossim_db::check_connection($conn); 
                
        $params = array($language, $login);
        $query  = "UPDATE users SET language = ? WHERE login = ?";
        
        $rs = $conn->Execute($query, $params);
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 
        
        return TRUE;
    }
    

    /**
    * This function updates the user password
    *
    * @param object $conn          Database access object
    * @param string $login         User login to search
    * @param string $pass          New SHA256 user pass
    * @param string $current_pass  [Optional] Current user pass
    * @param boolean $log          [Optional] Log action in the system
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean 
    */
    public static function change_pass($conn, $login, $pass, $current_pass = NULL, $log = TRUE) 
    {
        Ossim_db::check_connection($conn); 
        
        $pass   = hash('sha256', $pass);        
        $params = array($pass, $login);
        
        $query  = 'UPDATE users SET pass = ?';
        $query .= ($log == TRUE) ? ', last_pass_change=CURRENT_TIMESTAMP()' : '';    
        $query .= " WHERE login = ?";
        $query .= (!empty($current_pass)) ? " AND (pass='".md5($current_pass)."' OR pass='".hash('sha256', $current_pass)."')" : '';
        
        $rs = $conn->Execute($query, $params);
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }         
        
        //Log = FALSE is used when an user accesses to the system using LDAP authentication
        if ($log == TRUE)
        {
            $infolog = array($login);
            Log_action::log(5, $infolog);
        }
        
        return TRUE;
    }
    
    
    /**
    * This function returns the date of last password change for logged user
    *
    * @return string 
    */
    public function last_pass_change() 
    {
        global $_SESSION;
        
        $date = date('Y-m-d H:i:s');
        
        $db = new ossim_db();
        $conn = $db->connect();
        
        $pass     = md5($this->pass);
        $pass_sha = hash('sha256', $this->pass);
        $login    = $this->login;
        
        $params = array($login, $pass, $pass_sha);
        $query  = 'SELECT * FROM users WHERE login = ? AND (pass = ? OR pass = ?)';
       
        $rs = $conn->Execute($query, $params);
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());       
        } 
        else
        {
            if (!$rs->EOF) 
            {                        
                $date = $rs->fields['last_pass_change'];
            }
        }
             
        $db->close();
        
        return $date;
    }
    
    
    /**
    * This function checks password complexity
    *
    * @param string $pass  User password
    *
    * @return boolean 
    */
    public static function pass_check_complexity($pass) 
    {       
        $conf = $GLOBALS['CONF'];
        
        if ($conf->get_conf('pass_complex') == 'yes') 
        {
            $counter = 0;
            
            if (preg_match('/[a-z]/',$pass))
            {
                $counter++; 
            }
            
            if (preg_match('/[A-Z]/',$pass))
            {
                $counter++; 
            }
            
            if (preg_match('/[0-9]/',$pass))
            {
                $counter++; 
            }
            
            if (preg_match('/[\!\'\$\%\&\/\(\)\|\#\~\.\,\?\=\-\_\<\>]/', $pass))
            { 
                $counter++; 
            }
            
            return ($counter < 3) ? FALSE : TRUE;
        } 
        
        return TRUE;        
    }
    
    
    /**
    * This function adds the new password to password history (Pass_history table)
    *
    * @param string $user  User login to search
    * @param string $pass  User password
    *
    * @return boolean 
    */
    public static function log_pass_history($user, $pass) 
    {        
        $res  = TRUE;
        
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $hist_number = 1;
        
        $params = array($user);
        $query  = 'SELECT MAX(hist_number) AS num FROM pass_history WHERE user = ?';
            
        $rs = $conn->Execute($query, $params);
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 
                
        if (!$rs->EOF) 
        {
            $hist_number = $rs->fields['num'] + 1;
        }
        
        $params = array($user, $pass, $hist_number);
        $query  = "INSERT INTO pass_history (user, pass, hist_number) VALUES (?, ?, ?)";        
        
        $rs = $conn->Execute($query, $params);               
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());         
        }
        else
        {
            if (!$rs->EOF) 
            {  
                $res = FALSE;
            } 
        }
        
        $db->close();
        
        return $res;    
    }
        
        
    /* 
    *******************************************
    ************ Asset Restrictions  **********
    *******************************************
    */
    
      
    /**
    * This function returns an array with all nets allowed within user scope
    *
    * @param string $user  [Optional] User login to search
    *
    * @return string  Network IDs separated by comma                
    */
    public static function allowedNets($user = '') 
    {
        # Get network allowed from ctx's
                
        $db   = new ossim_db();
        $conn = $db->connect();
        
        if ($user == '')
        { 
            $user = self::get_session_user();
        }
        
        if (self::is_admin($conn, $user))
        {     
            return '';
        }
        
        $nets  = '';
        $query = 'SELECT HEX(id) AS id FROM net';
        
        if (self::get_ctx_where($user) != '') 
        {
            $query .= " WHERE ctx in (".self::get_ctx_where($user).")";
        }              
        
        $rs = $conn->Execute($query);          
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return $nets;        
        }        
        
        while (!$rs->EOF) 
        {
            $nets .= ($nets != '') ? ','.$rs->fields['id'] : $rs->fields['id'];
            
            $rs->MoveNext();
        }
               
        $db->close();
        
        return $nets;
    }
      

    /**
    * This function returns an array with all sensors allowed within user scope
    *
    * @param string $user  [Optional] User login to search
    *
    * @return Sensor  IDs separated by comma               
    */
    public static function allowedSensors($user = '') 
    {        
        $sensors = '';
        
        if (self::am_i_admin()) 
        {
            return '';
        }
        
        if ($user == '')
        { 
            $user = self::get_session_user();
        }
        
        if ($user == self::get_session_user() && is_array($_SESSION['_user_vision']) && array_key_exists('sensor',$_SESSION['_user_vision'])) 
        {
            $allowed_sensors = '';
            
            foreach ($_SESSION['_user_vision']['sensor'] as $sensor_id_ip => $perm) 
            {    
                if (preg_match("/^\d+\.\d+\.\d+\.\d+$/", $sensor_id_ip)) 
                {
                    $allowed_sensors .= ($allowed_sensors != '') ? ",".$sensor_id_ip : $sensor_id_ip;
                }
            }
            
            return $allowed_sensors;
        }
                
        if ($user != self::get_session_user()) 
        {            
            /* Get sensors allowed from network and hosts related sensors an ctx's */           
            $db    = new ossim_db();
            $conn  = $db->connect();            
            
            $ctxs   = self::get_ctx_where($user);
            
            $query = "SELECT sensor.ip FROM net_sensor_reference, net, sensor WHERE sensor.id=net_sensor_reference.sensor_id AND net_sensor_reference.net_id=net.id";            
            
            if ($ctxs != '') 
            {
                $query .= " AND net.ctx in ($ctxs)";
            }            
            
            $query .= " UNION SELECT sensor.ip FROM host_sensor_reference, host, sensor WHERE sensor.id=host_sensor_reference.sensor_id AND host_sensor_reference.host_id=host.id";
            
            if ($ctxs != '') 
            {
                $query .= " AND host.ctx in ($ctxs)";
            }
            
            $rs = $conn->Execute($query);
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
            else
            {
                while (!$rs->EOF) 
                {
                    $sensors .= ($sensors != '') ? ','.inet_ntop($rs->fields['ip']) : inet_ntop($rs->fields['ip']);
                    $rs->MoveNext();
                }
            }
            
            $db->close();         
        }
        
        return $sensors;    
    }
       
           
    /**
    * This function returns TRUE if an asset with ID $asset_id belongs to user scope ($user)
    *
    * @param object $conn      Database access object
    * @param string $asset_id  Asset ID (Hexadecimal ID) to search
    * @param string $type      Asset type (host or net)
    * @param string $user      [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function is_asset_allowed($conn, $asset_id, $type, $user = '') 
    {
        Ossim_db::check_connection($conn);
        
        $user = ($user == '') ? self::get_session_user() : $user;
        
        if (self::is_admin($conn, $user)) 
        {
            return TRUE;
        }
                        
        // Logical level
        $params = array($user, $asset_id);   
        $query  = "SELECT p.asset_id FROM alienvault.user_".$type."_perm p WHERE p.login = ? AND p.asset_id = UNHEX(?)";
                     
        $rs = $conn->Execute($query, $params);               
                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;        
        }      
                
        if (!$rs->EOF) 
        {
            return TRUE;
        } 
                        
        // CTX level
        $asset_perms = ($type == 'host') ? self::get_host_where($user) : self::get_net_where($user);
                        
        if ($asset_perms == '' && self::get_ctx_where($user) != '') 
        {        
            $params = array($asset_id);
            $query  = "SELECT id FROM $type WHERE ctx IN (".self::get_ctx_where($user).") AND id = UNHEX(?)";            
                                    
            $rs = $conn->Execute($query, $params);               
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;        
            }  
            
            if (!$rs->EOF) 
            {
                return TRUE;
            }
        } 
        
        return FALSE;
    }
                
    
    /**
    * This function returns TRUE if a host with ID $host_id is within user scope ($user)
    *
    * @param object $conn     Database access object
    * @param string $host_id  Host ID (Hexadecimal ID) to search
    * @param string $user     [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function hostAllowed($conn, $host_id, $user = '') 
    {        
        Ossim_db::check_connection($conn);
            
        $cnd_1 = (!$_SESSION['_user_vision']['host_where'] || self::only_ff_host());
        $cnd_2 = ($_SESSION['_user_vision']['net_where'] && !self::only_ff_net());
        
        if ($cnd_1 && $cnd_2) 
        {
            return Asset_host::is_in_allowed_nets($conn, $host_id); // if host_id is in allowed nets
        }
                
        return self::is_asset_allowed($conn, $host_id, 'host', $user);
    }
             
    
    /**
    * This function checks if host IP in a context is allowed for logged user
    *
    * @param object $conn     Database access object
    * @param string $host_ip  Host IP to search
    * @param string $ctx      Context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function hostAllowed_by_ip_ctx($conn, $host_ip, $ctx) 
    {        
        Ossim_db::check_connection($conn);
        
        if(self::am_i_admin())
        {
            return TRUE;
        }
        
        $host_id = '';
        
        if ($_SESSION['_user_vision']['host_where'] && !self::only_ff_host()) 
        {
            // Search the host ID            
            $host_ids = Asset_host::get_id_by_ips($conn, $host_ip, $ctx); 
            $host_id  = key($host_ids);
        }
        
        if(!empty($host_id)) 
        {
            return self::hostAllowed($conn, $host_id);
        }
        else
        { 
            return Asset_host::is_ip_in_cache_cidr($conn, $host_ip, $ctx, TRUE);
        }
    }
    
    
    /**
    * This function returns TRUE if net with ID $net_id is within user scope ($user)
    *
    * @param object $conn     Database access object
    * @param string $net_id   Host ID (Hexadecimal ID) to search
    * @param string $user     [Optional] User login to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function netAllowed($conn, $net_id, $user = '') 
    {
        Ossim_db::check_connection($conn);

        if (!$_SESSION['_user_vision']['net_where']) 
        {
           return TRUE; // all nets
        }
        
        return self::is_asset_allowed($conn, $net_id, 'net', $user);
    }
    
    
    /**
    * This function returns TRUE if net with ID $net_id belongs to user scope ($user)
    *
    * @param string $sensor  Sensor ID (Hexadecimal ID) or sensor IP to search
    *
    * @return boolean                
    */
    public static function sensorAllowed($sensor)
    {        
        if (count($_SESSION['_user_vision']['sensor']) > 0) 
        {
            return (@$_SESSION['_user_vision']['sensor'][$sensor]) ? TRUE : FALSE;
        } 
        
        return TRUE;
    }
    
        
    /* 
    **********************************
    ************ Risk Maps  **********
    **********************************
    */
       

    /**
    * This function returns TRUE if all nets of network group with ID $group_id belong to user scope (logged user)
    *
    * It is used to checks if an indicator is allowed in risk maps
    *
    * @param object $conn      Database access object
    * @param string $group_id  Netgroup ID (Hexadecimal ID) to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function groupAllowed($conn, $group_id) 
    {        
        // TODO: Review this function, functionality has been changed
        static $groupAllowedCache;
        
        Ossim_db::check_connection($conn);
        
        $user = self::get_session_user();
                    
        if (isset($groupAllowedCache["$user"]["$group_id"])) 
        {
            return $groupAllowedCache["$user"]["$group_id"];
        }
        
        $networks = Net_group::get_networks($conn, $group_id);
        
        if (empty($networks))
        {
            $groupAllowedCache["$user"]["$group_id"] = FALSE;
            
            return FALSE;
        }
        else
        {
            $groupAllowedCache["$user"]["$group_id"] = TRUE;
            
            return TRUE;
        }
    }
    
    
    /**
    * This function returns TRUE if all hosts of host group with ID $group_id belong to user scope (logged user)
    *
    * It is used to checks if an indicator is allowed in risk maps
    *
    * @param object $conn      Database access object
    * @param string $group_id  Hostgroup ID (Hexadecimal ID) to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function groupHostAllowed($conn, $group_id) 
    {        
        // TODO: Review this function, functionality has been changed
        static $groupAllowedCache;
        
        Ossim_db::check_connection($conn);        
               
        $user = self::get_session_user();
                    
        if (isset($groupAllowedCache["$user"]["$group_id"]))
        {
            return $groupAllowedCache["$user"]["$group_id"];
        }
        
        $hosts = Asset_group::get_object($conn, $group_id);
        
        if (is_object($hosts) && !empty($hosts) && $hosts->get_num_host($conn) > 0)
        {
            $groupAllowedCache["$user"]["$group_id"] = FALSE;
            
            return FALSE;
        }
        else
        {
            $groupAllowedCache["$user"]["$group_id"] = TRUE;
            
            return TRUE;
        }
    }
    
    
    /**
    * This function checks if logged user can create a new asset (host or net) 
    *
    * An user can create assets if he doesn´t have any asset restriction (host or net filter)
    *
    * @return boolean                
    */
    public static function can_i_create_assets() 
    {     
        if(self::am_i_admin()) 
        {
            return TRUE;
        }
        
        $cnd_1 = ($_SESSION['_user_vision']['host_where'] && !self::only_ff_host());
        $cnd_2 = (!$_SESSION['_user_vision']['net_where'] || self::only_ff_net()); 

        if ($cnd_1 && $cnd_2) 
        {
            return FALSE;
        }
        
        return TRUE;
    }
        
     
    /**
    * This function returns TRUE if host with ID $host_id belong to host user filter (logged user)
    *
    * @param object $conn     Database access object
    * @param string $host_id  Host ID (Hexadecimal ID) to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function is_in_host_where($conn, $host_id)
    {
        Ossim_db::check_connection($conn);
        
        if (empty($host_id))
        {
            return FALSE;
        }
        
        $query = self::get_host_where();
        
        if (!empty($query))
        {
            $params = array($host_id);
            
            $query .= ' AND asset_id = UNHEX(?)';
                                    
            $rs = $conn->Execute($query, $params);               
                
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;        
            }  
            
            if ($rs->RecordCount() >= 1)
            {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    /**
    * This function returns TRUE if logged user can edit the ip of host with ID $host_id
    *
    * @param object $conn     Database access object
    * @param string $host_id  Host ID (Hexadecimal ID) to search
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean                
    */
    public static function is_host_ip_editable($conn, $host_id)
    {        
        Ossim_db::check_connection($conn);
        
        if (!self::get_host_where() && !self::get_net_where())
        {    
            return TRUE;
        }
            
        if (self::is_in_host_where($conn, $host_id))
        {
            return FALSE;
        }
        
        return Asset_host::is_in_allowed_nets($conn, $host_id);
    }
            
            
    /**
    * This function returns a string with all context filters (for use an SQL statement) separated by commas
    *
    * <pre>For example: 'UNHEX("2c8850ae4eb511e2beb1001a990a3e1b"), UNHEX("2c5856aefeb511e2b1b10019990a3e1b")'</pre>
    * 
    * @param string $user  [Optional] User login to search
    *
    * @return string                
    */        
    public static function get_ctx_where($user = '')
    {        
        if (!self::is_pro()) 
        { 
            return ''; 
        }
        
        if (!empty($user) && $user != self::get_session_user()) 
        {                        
            $db   = new ossim_db();
            $conn = $db->connect();
            
            $entities = Acl::get_entities_to_assign($conn, $user);
            
            $db->close();
            
            $ctx_where = '';
            
            foreach ($entities as $entity_id => $entity_name) 
            {
                $ctx_where .= ($ctx_where != '') ? ", UNHEX(\"$entity_id\")" : "UNHEX(\"$entity_id\")";
            }
            
            return $ctx_where;
        }
        
        return $_SESSION['_user_vision']['ctx_where'];
    }
    
    
    /**
    * This function returns a SQL query used in asset filter (hosts and nets) for logged user
    * 
    * @param string $user  [Optional] User login to search
    *
    * @return string                
    */   
    public static function get_asset_where($user = '') 
    {
        $user = (empty($user)) ? self::get_session_user() : $user;
        
        $where = '';
        
        if (count($_SESSION['_user_vision']['asset']['host']) > 0) 
        {
            $where = "SELECT asset_id FROM alienvault.user_host_perm WHERE login = '".$user."'";
        }
        
        if (count($_SESSION['_user_vision']['asset']['net']) > 0) 
        {
            if ($where != '') 
            { 
                $where .= ' UNION '; 
            }
            
            $where .= "SELECT asset_id FROM alienvault.user_net_perm WHERE login = '".$user."'";
        }
    }
    
    
    /**
    * This function returns a SQL query used in host filter for logged user
    * 
    * @param string $user  [Optional] User login to search
    *
    * @return string                
    */   
    public static function get_host_where($user = '') 
    {        
        $user = (empty($user)) ? self::get_session_user() : $user;
        
        return ($_SESSION['_user_vision']['host_where']) ? "SELECT asset_id FROM alienvault.user_host_perm WHERE login = '".$user."'" : '';
    }
    
    /**
     * This function returns filtered hosts for logged user
     *
     * @param object $conn  Database connection object
     * @param string $user  [Optional] User login to search
     *
     * @return string
     */
    public static function get_host_ids($conn, $user = '')
    {
        $user  = (empty($user)) ? self::get_session_user() : $user;
        $hosts = array();
        
        $query  = 'SELECT HEX(asset_id) AS asset_id FROM alienvault.user_host_perm WHERE login = ?';
        $params = array($user);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF)
        {
            $hosts[] = $rs->fields['asset_id'];
            $rs->MoveNext();
        }
        
        return $hosts;
    }
    
    /**
    * This function returns a SQL query used in net filter for logged user
    * 
    * @param string $user  [Optional] User login to search
    *
    * @return string                
    */ 
    public static function get_net_where($user = '')
    {
        $user = (empty($user)) ? self::get_session_user() : $user;
        
        return ($_SESSION['_user_vision']['net_where']) ? "SELECT asset_id FROM alienvault.user_net_perm WHERE login = '".$user."'" : '';
    }
    
    /**
     * This function returns filtered nets for logged user
     *
     * @param object $conn  Database connection object
     * @param string $user  [Optional] User login to search
     *
     * @return string
     */
    public static function get_net_ids($conn, $user = '')
    {
        $user = (empty($user)) ? self::get_session_user() : $user;
        $nets = array();
    
        $query  = 'SELECT HEX(asset_id) AS asset_id FROM alienvault.user_net_perm WHERE login = ?';
        $params = array($user);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[] = $rs->fields['asset_id'];
            $rs->MoveNext();
        }
    
        return $nets;
    }
    
    
    /**
    * This function returns a string with all sensor filters (for use an SQL statement) separated by commas
    *
    * <pre>For example: 'UNHEX("2c8850ae4eb511e2beb1001a990a3e1b"), UNHEX("2c5856aefeb511e2b1b10019990a3e1b")'</pre>
    * 
    * @return string                
    */ 
    public static function get_sensor_where() 
    {
        $where = '';
        
        if(isset($_SESSION['_user_vision']['sensor'])) 
        {
            foreach ($_SESSION['_user_vision']['sensor'] as $key => $perm) 
            {
                if (preg_match("/^[A-F0-9]{32}$/", $key)) 
                {
                    if ($where != '') 
                    { 
                        $where .= ', '; 
                    }
                    
                    $where .= "UNHEX('$key')";
                }
            }
        }
        
        return $where;
    }
    
    
    /**
    * This function returns the value of $_SESSION['_user_vision']['host_where_ff']
    *
    * @see self::get_user_vision()  function get_user_vision($conn)
    *
    * @return boolean                
    */ 
    public static function only_ff_host() 
    {
        return $_SESSION['_user_vision']['host_where_ff'];
    }
    
    
    /**
    * This function returns the value of $_SESSION['_user_vision']['net_where_ff']
    *
    * @see self::get_user_vision()  function get_user_vision($conn)
    *
    * @return boolean                
    */ 
    public static function only_ff_net() 
    {
        return $_SESSION['_user_vision']['net_where_ff'];
    }
      
    
    /**
    * This function returns the intersection between user context and context passed as parameter. used by report section
    *
    * <pre>For example: 'UNHEX("2c8850ae4eb511e2beb1001a990a3e1b"), UNHEX("2c5856aefeb511e2b1b10019990a3e1b")'</pre>
    * 
    * @param array $ctxs  [Optional] Array of contexts
    *
    * @see self::get_ctx_where()  function get_ctx_where($user = '')
    *
    * @return string                
    */    
    public static function get_ctx_where_intersect($ctxs = array()) 
    {        
        $perms = array();
        
        if (self::get_ctx_where()) 
        {
            $tmps = explode(', ',self::get_ctx_where());
            
            foreach ($tmps as $ctx) 
            {
                $perms[] = str_replace('UNHEX("','',str_replace('")','',$ctx));
            }
        }
        
        // Nothing passed as parameter
        if (count($perms) > 0 && count($ctxs) == 0) 
        {
            return "UNHEX('".implode("'), UNHEX('",$perms)."')";
        }
        
        $ctxs = array_unique((count($perms)>0) ? array_intersect($perms,$ctxs) : $ctxs);
        
        // Intersect is empty but there's some perms
        if (count($perms) > 0 && count($ctxs) == 0) 
        {
            return '0x0';
        }
        
        return (count($ctxs) > 0) ? "UNHEX('".implode("'), UNHEX('",$ctxs)."')" : '';
    }
    
    
    /**
    * This function returns the SQL condition obtained from intersection between host filter and 
    * hosts passed as parameter (used by report section)
    *
    *
    * @param string $hosts  [Optional] Array of hosts
    *
    * @return string                
    */
    public static function get_host_where_intersect($hosts = array()) 
    {        
        if (count($hosts) > 0) 
        { 
            // Merge            
            return (self::get_host_where()) ? self::get_host_where()." AND asset_id IN (UNHEX('".implode("'), UNHEX('",$hosts)."'))" : "UNHEX('".implode("'), UNHEX('",$hosts)."')";
        } 
        else
        {
            return self::get_host_where();
        }
    }        

       
    /**
    * This function returns the SQL condition obtained from intersection between net filter and 
    * nets passed as parameter (used by report section)
    *
    * @param string $nets  [Optional] Array of nets
    *
    * @return string                
    */
    public static function get_net_where_intersect($nets = array()) 
    {
        if (count($nets) > 0) 
        {
            // Merge
            return  (self::get_net_where()) ? self::get_net_where()." AND asset_id IN (UNHEX('".implode("'), UNHEX('",$nets)."'))" : "UNHEX('".implode("'), UNHEX('",$nets)."')";
        } 
        else 
        {
            // Only allowed
            return self::get_net_where();
        }
    }
    
   
    /**
    * This function returns the value of $_SESSION['_user_vision']['ctx']
    *
    * @see self::get_user_vision()  function get_user_vision($conn)
    *
    * @return string                
    */ 
    public static function get_default_ctx() 
    {
        return $_SESSION['_user_vision']['ctx'];
    }

    /**
    * This function returns new context cloning default ctx
    *
    * @param object $conn      Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string                
    */ 
    public static function clone_default_ctx($conn, $name) 
    {
        Ossim_db::check_connection($conn);
        
        $id          = Util::uuid();
        $default_ctx = self::get_default_ctx();
        $default_eng = self::get_default_engine($conn);
        
        $query       = "INSERT INTO acl_entities (id,server_id,entity_type,admin_user,name,address,timezone,parent_id) SELECT UNHEX(?),server_id,entity_type,admin_user,?,address,timezone,parent_id FROM acl_entities WHERE id=UNHEX(?)";
        
        $query_2     = "REPLACE INTO corr_engine_contexts (engine_ctx,event_ctx,descr) VALUES (UNHEX(?),UNHEX(?),'')";
        
        $rs   = $conn->Execute($query, array($id, $name, $default_ctx));
        $rs_2 = $conn->Execute($query_2, array($default_eng, $id));
        
        if (!$rs || !$rs_2) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return 0;
        }
        
        $_SESSION['_user_vision']['show_entities'] = TRUE;
        
        Util::memcacheFlush(TRUE);
                
        return $id;
    }    
    
    
    
    /* 
    *******************************************
    ************* Menus & Templates ***********
    *******************************************
    */
    
    /**
    * This function returns an array of templates that satisfies the given paramters
    *
    * @param object $conn    Database access object
    * @param int $from       [Optional] First row of range (For SQL query)
    * @param int $max        [Optional] Number of rows
    * @param string $search  [Optional] Search filter (Valid SQL statement).  For example: " WHERE name like '%$search%' "
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */    
    public static function get_templates($conn, $from = 0, $max = 20, $search = '') 
    {
        Ossim_db::check_connection($conn);
        
        $list = array();
        
        $num_rows = 0;
        
        // Filter if am i proadmin
        $perms_where   = '';
        $users_allowed = array();
        
        if (self::is_pro() && Acl::am_i_proadmin()) 
        {
            if (is_array($_SESSION['_user_vision']['users_to_assign'])) 
            {
                foreach ($_SESSION['_user_vision']['users_to_assign'] as $user => $perm) 
                {
                    if ($perm && $user != self::get_session_user())
                    { 
                        $users_allowed[] = $user; 
                    }
                }
            }
            
            if (count($users_allowed) > 0) 
            {
                $perms_where = "WHERE (id NOT IN (SELECT DISTINCT template_id FROM users) OR id IN (SELECT DISTINCT template_id FROM users WHERE login IN ('".implode("', '", $users_allowed)."')))";
            } 
            else 
            {
                $perms_where = "WHERE id NOT IN (SELECT DISTINCT template_id FROM users)";
            }
        }
        
        //Search filter
        if($search != '' && preg_match('/where/i', $perms_where)) 
        {
            $search = preg_replace('/where/i', 'AND', $search);
        }
        
        $query = "SELECT SQL_CALC_FOUND_ROWS HEX(id) as id, name FROM acl_templates $perms_where  $search LIMIT $from, $max";
        
        $rs = $conn->Execute($query);
        
                                
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $total = $conn->Execute('SELECT FOUND_ROWS() AS counter');
            
            if (!$total->EOF) 
            {
                $num_rows = $total->fields['counter'];
            }
            
            while (!$rs->EOF) 
            {
                // Get users assigned
                $users = array();
                $query = 'SELECT login FROM users WHERE template_id = UNHEX(?)';
                
                $rs_2 = $conn->Execute($query, array($rs->fields['id']));
                
                if (!$rs_2) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                } 
                else 
                {
                    while (!$rs_2->EOF) 
                    {
                        $users[] = $rs_2->fields['login'];
                        
                        $rs_2->MoveNext();
                    }
                }
                
                // Get sections allowed
                $total = $conn->Execute("SELECT count(*) AS total FROM acl_perm WHERE type = 'MENU' AND description != '' AND enabled = 1");
                
                $conf        = $GLOBALS['CONF'];
                $av_features = ($conf->get_conf('advanced_features') == 1) ? TRUE : FALSE;
                
                
                // av_features = 1 Enable advanced features
                // 50 = Analysis -> Detection -> Anomalies
                // 80 = Configuration -> Network Discovery
                                
                if (!$total->EOF)
                {
                    $total_perms = $total->fields['total'];
                    $total_perms = ($av_features == TRUE) ? $total_perms : $total_perms-2;
                }
                
                $total_perms = ($total_perms < 1) ? 1 : $total_perms;
                            
                $total = $conn->Execute("SELECT count(*) AS num FROM acl_templates_perms WHERE ac_templates_id = UNHEX(?)", array($rs->fields['id']));
                
                if (!$total->EOF) 
                {
                    $count_perms = $total->fields['num'];
                    $count_perms = ($count_perms > $total_perms) ? $total_perms : $count_perms;
                }
                                
                $perc = round($count_perms * 100 / $total_perms);
                
                $list[] = array(
                    "id"      => $rs->fields['id'],
                    "name"    => $rs->fields['name'],
                    "users"   => implode(', ', $users),
                    "percent" => $perc
              );
                
                $rs->MoveNext();
            }
        }
        
        return array($list, $num_rows);
    }
    
    
    /**
    * This function returns an array with all menu options of the template with ID $template_id
    *
    * @param string $template_id  Template ID to search
    * @param string $property     Property type to search (Only keyword 'menus' is allowed)
    
    * @return array  (Pair Name-Value)      
    */ 
    public static function get_template_property($template_id, $property) 
    {                
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        $menus = array();
        
        // Menu sections perms
        if ($property == 'menus') 
        {
            $params = array($template_id);
            $query  = "SELECT acl_perm.* FROM acl_perm,acl_templates_perms 
                WHERE acl_templates_perms.ac_templates_id=UNHEX(?) 
                AND acl_templates_perms.ac_perm_id = acl_perm.id 
                AND acl_perm.type = 'MENU'";
            
            $rs = $conn->Execute($query, $params);
            
            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
            else 
            {
                while (!$rs->EOF) 
                {
                    $menus[$rs->fields['name']][] = $rs->fields['value'];
                    
                    $rs->MoveNext();
                }
            }
        }
        
        $db->close();
        
        return $menus;
    }
    
    
    /**
    * This function returns an array with all menu options of the template with ID $template_id
    *
    * @param object $conn         Database access object
    * @param string $template_id  Template ID to search
    *
    * @see self::get_template_property()  function get_template_property($template_id, $property)
    *
    * @throws Exception  If an error occurred
    *
    * @return array (ID-Name-Value)     
    */ 
    public static function get_template_by_id($conn, $template_id)
    {       
        Ossim_db::check_connection($conn);
        
        $template_data = array();
        
        $rs    = $conn->Execute($query, array($template_id));
        $query = 'SELECT HEX(id) AS id, name FROM acl_templates WHERE id = UNHEX(?)';        
        
        $rs = $conn->Execute($query, array($template_id));
        
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            if (!$rs->EOF) 
            {
                //Menu permissions
                $query_2 = "SELECT p.* FROM acl_templates_perms tp, acl_perm p 
                    WHERE tp.ac_templates_id = UNHEX(?) AND tp.ac_perm_id = p.id";
                
                $rs_2 = $conn->Execute($query_2, array($template_id));
                
                if (!$rs_2) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                } 
                else 
                {
                    while (!$rs_2->EOF) 
                    {
                        $perms[$rs_2->fields['id']] = $rs_2->fields['value'];
                        
                        $rs_2->MoveNext();
                    }
                }
                
                $template_data = array(
                    'id'    => $rs->fields['id'],
                    'name'  => $rs->fields['name'],
                    'perms' => $perms
              );
            }
        }
        
        return $template_data;
    }
    
    
    /**
    * This function returns an array with all menu options of the template with name $template_name
    *
    * @param object $conn            Database access object
    * @param string $template_name   Template name to search   
    *
    * @throws Exception  If an error occurred
    *
    * @return array (ID-Name-Value)     
    */ 
    public static function get_template_by_name($conn, $template_name)
    {       
        Ossim_db::check_connection($conn);
        
        $template_data = array();
        
        $query = "SELECT HEX(id) as id, name FROM acl_templates WHERE name = ?";        
        
        $rs_1 = $conn->Execute($query, array($template_name));
                
        if (!$rs_1)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {            
            while (!$rs_1->EOF) 
            {
                $template_id = $rs_1->fields['id'];
                
                //Menu permissions
                $query_2 = "SELECT p.* FROM acl_templates_perms tp, acl_perm p WHERE tp.ac_templates_id=UNHEX(?) AND tp.ac_perm_id=p.id";
                
                $rs_2 = $conn->Execute($query_2, array($template_id));
                
                if (!$rs_2) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                } 
                else 
                {
                    $perms = array();
                    
                    while (!$rs_2->EOF) 
                    {
                        $perms[$rs_2->fields['id']] = $rs_2->fields['value'];
                        
                        $rs_2->MoveNext();
                    }
                    
                    $template_data[$template_id] = array(
                        'id'    => $template_id,
                        'name'  => $rs_1->fields['name'],
                        'perms' => $perms
                   );
                }
                             
                $rs_1->MoveNext();
            }
        }
        
        return $template_data;
    }
       
     
    /**
    * This function updates a template
    *
    * @param object $conn         Database access object
    * @param string $name         Template name
    * @param array  $perms        New menu options
    * @param string $template_id  [Optional] Template ID to search
    *
    * @throws Exception  If an error occurred
    *
    * @return int|string  1 if there is an error, otherwise the template ID
    */ 
    function update_template($conn, $name, $perms, $template_id = '') 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($name);
        
        if ($template_id != '') 
        {
            $params[] = $template_id;
            $query    = "UPDATE acl_templates SET name=? WHERE id=UNHEX(?)";
        } 
        else 
        {
            //We are using 'Save As' option from GUI
            $template_id = Util::uuid();
            $params[]    = $template_id;
            $query       = "INSERT INTO acl_templates (name, id) VALUES (?, UNHEX(?))";
        }
        
        //print_r($params);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return 1;
        } 
        else 
        {
            //Menu permission
            $query = "DELETE FROM acl_templates_perms WHERE ac_templates_id=UNHEX(?)";
            
            $rs = $conn->Execute($query, array($template_id));
            
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return 1;
            }
                        
            foreach ($perms as $perm_id => $val) 
            {
                $query = "INSERT INTO acl_templates_perms (ac_templates_id, ac_perm_id) VALUES (UNHEX(?), ?)";
                
                
                $rs = $conn->Execute($query, array($template_id, $perm_id));
            
                if (!$rs) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                    
                    return 1;
                }            
            }
            
            return $template_id;
        }
    }
    
    
    /**
    * This function deletes a template
    *
    * @param object $conn         Database access object
    * @param string $template_id  Template ID to search
    *
    * @throws Exception  If an error occurred
    *
    * @return string
    */ 
    public static function delete_template($conn, $template_id) 
    {
        Ossim_db::check_connection($conn);
        
        $query = "SELECT name, 'users' AS type FROM users WHERE template_id = UNHEX(?)";
                
        $rs = $conn->Execute($query, array($template_id));
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return _('Error while deleting the template');
        }
                     
        $num_rows = $conn->Affected_Rows();
        
        if ($num_rows >= 1) 
        {
            $users = array();
            
            while (!$rs->EOF) 
            {
                $users[] = utf8_encode($rs->fields['name']);
                
                $rs->MoveNext();
            }
            
            $msg  = _("You can't delete this template. Some users are using it");
            
            if (!empty($users))
            {        
                $msg .= ': '.implode(', ', $users);
            }
            
            return $msg;
        }
                
        $query = "DELETE FROM acl_templates_perms WHERE ac_templates_id = UNHEX(?)";        
        
        $rs = $conn->Execute($query, array($template_id));
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return _('Error while deleting the template perms');
        }
                
       
        $query = "DELETE FROM acl_templates WHERE id=UNHEX(?)";
        
        $rs = $conn->Execute($query, array($template_id));
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return _('Error while deleting the template');
        } 
        
        return '';
    }
       
    
    /**
    * This function checks if menu option/s is/are allowed for logged user
    *
    * @param array|string $menu     Menu option
    * @param array|string $submenu  Submenu option
    *
    * @return boolean 
    */
    public static function menu_perms($menu, $submenu) 
    {        
        if (self::am_i_admin())
        { 
            return TRUE;
        }
        
        //You need to be logged in the system!!
        if (!isset($_SESSION['_user']))
        {
            return FALSE;
        }
        
        if (is_array($menu) && is_array($submenu))    
        {
            $i  = 0; 
            $sm = count($submenu);
            
            foreach ($menu as $menu_value)
            {
                if (is_array($_SESSION['_allowed_menus'][$menu_value]) && (!$sm || in_array($submenu[$i],$_SESSION['_allowed_menus'][$menu_value])))
                { 
                    return TRUE;
                }
                
                $i++;
            }
        }
        elseif (!is_array($menu))
        {
            if ($_SESSION['_allowed_menus'][$menu] == '')
            {
                return FALSE;
            }
            
            if (in_array($submenu, $_SESSION['_allowed_menus'][$menu]))
            { 
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    /**
    * This function returns an array with all menu option allowed within user scope
    *
    * @param string $user  [Optional] User login to search
    *
    * @return array                
    */
    public function allowedMenus($user) 
    {        
        $conn  = $this->conn;
        $menus = array();
        
        if ($user == '') 
        {
            $user = $_SESSION['_user'];
        }
        
        $query  = "SELECT HEX(template_id) AS template_id FROM users WHERE login = ?";
        $params = array($user);
                
        $rs = $conn->Execute($query, $params);
            
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return $menus;
        }         
  
        if (!$rs->EOF) 
        {
            $menus = self::get_template_property($rs->fields['template_id'], 'menus');
        }
                
        return $menus;
    }
      
    
    /**
    * This function returns an array with all menu options allowed by default
    *
    * It used when a new LDAP user is created automatically
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return array                
    */
    public static function get_default_perms($conn)
    {
        Ossim_db::check_connection($conn);
        
        $list        = array();
        $perms_check = array();
        
        $query = "SELECT * FROM acl_perm WHERE acl_perm.type = 'MENU' AND enabled = 1 ORDER BY ord ASC";
        
        $rs = $conn->Execute($query);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return array($list, $perms_check);
        }                 
        
        if (!$rs->EOF) 
        {
            $conf        = $GLOBALS['CONF'];
            $av_features = ($conf->get_conf('advanced_features') == 1) ? TRUE : FALSE;
            
            // av_features = 1 Enable Advanced features
            // 50 = Analysis -> Detection -> Anomalies
            // temporally disabled options
            // 80 = Configuration -> Network Discovery
                                    
            while (!$rs->EOF) 
            {
                $cnd_1 = (!in_array($rs->fields['id'],array(80)));
                
                $cnd_2 = ($av_features || (!$av_features && !in_array($rs->fields['id'],array(50))));
                
                if ($cnd_1 && $cnd_2)
                {
                    $list[$rs->fields['name']][$rs->fields['id']] = ($rs->fields['description'] != '') ? $rs->fields['description'] : "<font color='#444444'>[".$rs->fields['value']."]</font>";
                    
                    $perms_check[$rs->fields['name']][$rs->fields['id']] = 1;
                }
                                                
                $rs->MoveNext();
            }
        }
        
        return array($list, $perms_check);
    }
        
    
    /**
    * This function returns an array with all menu options allowed for logged user
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return array                
    */
    public static function get_menu_perms($conn) 
    {
        Ossim_db::check_connection($conn);
        
        $list        = array();
        $perms_check = array();
        
        $query = "SELECT * FROM acl_perm WHERE acl_perm.type = 'MENU' AND enabled = 1 ORDER BY ord ASC";
        
        $rs = $conn->Execute($query);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return array($list, $perms_check);
        }                   
        
        if (!$rs->EOF) 
        {                               
            while (!$rs->EOF) 
            {                            
                if (self::menu_perms($rs->fields['name'], $rs->fields['value']))
                {
                    $list[$rs->fields['name']][$rs->fields['id']] = ($rs->fields['description'] != '') ? $rs->fields['description'] : "<span style='color:#444444'>[".$rs->fields['value']."]</span>";
                    
                                     
                    $perms_check[$rs->fields['name']][$rs->fields['id']] = (self::menu_perms($rs->fields['name'], $rs->fields['value'])) ? 1 : 0;
                }
                                        
                $rs->MoveNext();
            }
        }
        
        return array($list, $perms_check);
    }
    
    
    /**
    * Alias of menu_perms($menu, $submenu)
    *
    * @param array|string $menu     Menu option
    * @param array|string $submenu  Submenu option
    *
    * @see self::menu_perms()  function menu_perms($menu, $submenu)
    *
    * @return boolean 
    */
    public static function logcheck_bool($menu, $submenu)
    {
        return self::menu_perms($menu, $submenu);
    }
    
    
    /**
    * This function checks if menu option/s is/are allowed for logged user, otherwise it print a info message
    *
    * If session user is inactive, logged user will be redirected to $login_location
    *
    * @param array|string $menu              Menu option
    * @param array|string $submenu           Submenu option
    * @param boolean|string $login_location  [Optional] FALSE (Redirection to login page), otherwise redirection to $login_location (URL)
    *
    * @return void 
    */
    public static function logcheck($menu, $submenu, $login_location = FALSE) 
    {
        
    	// Scheduler exception:
    	// For some long taking reports, the session could be expired from the command line
    	// It is causing empty graph images in scheduled PDF reports
    	// Important: do not move this lines down
    	if (preg_match('/AV Report Scheduler/', $_SERVER['HTTP_USER_AGENT']))
    	{
    		return TRUE;
    	}
    	
		self::external_login();  		
        
        if (!$login_location) 
        {
            $conf           = $GLOBALS['CONF'];
            $ossim_link     = $conf->get_conf('ossim_link');
            $login_location = $ossim_link . '/session/login.php';
        }
        
        if (!isset($_SESSION['_user']) && !self::am_i_admin()) 
        {
            header("Location: $login_location");
            
            exit();
        }
        
        if (self::menu_perms($menu, $submenu) == FALSE)
        { 
            self::unallowed_section(NULL, 'noback', $menu, $submenu);
        }
    }
    
   
    /**
    * This function print a predefined message for unallowed sections
    *
    * Notes:
    *   - $link:
    *       - NULL or ''      -> Nothing      will be added to message 
    *       - 'logout'        -> Back button  will be added to message 
    *       - HTML Code       -> HTML code    will be added to message    
    *   - $action:
    *       - 'noback'        -> die() function is evaluated, PHP program terminates
    *       - valid PHP code  -> PHP code to evaluate
    *       - FALSE or ''     -> No action evaluated
    *       
    *
    * @param string $link     [Optional] Link to go back ('logout', NULL, '' or HTML code)
    * @param string $action   [Optional] Action to perform ('noback', FALSE, '' or PHP code)
    * @param string $menu     [Optional] Menu option to check
    * @param string $submenu  [Optional] Submenu option to check
    */
    public static function unallowed_section($link = NULL, $action = 'noback', $menu = '', $submenu = '')
    {
        // Show the menu section that has been blocked
        $section = '';
        
        if ($menu != '' && $submenu != '')
        {           
            $db     = new ossim_db();
            $conn   = $db->connect();
            
            $params = array($menu, $submenu);
            $query  = "SELECT description FROM acl_perm WHERE type='MENU' AND name=? AND value=?";
                        
            $rs = $conn->Execute($query, $params);        
        
            if (!$rs) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF) 
                {
                    $section = ' [<strong>'.$rs->fields['description'].'</strong> menu permission]';
                }
            }  
                      
            $db->close();
        }
               
        if (empty($link))
        {
            $link = NULL;
        }
        elseif ($link == 'logout')
        {
            $link =  "<a class='color: #00529B !important' href='/ossim/session/login.php?action=logout' title='"._('Logout')."'><span class='notice_link'>[ "._('Logout')." ]</span></a>";
        }
                       
        //CSS styles
        
        echo "<style type='text/css'>
                
            a {text-decoration:none !important; outline: none !important;}
            
            .notice_link, .notice_link:link, .notice_link:hover, .notice_link:active, .notice_link:visited  { 
                color: #00529B !important; 
                font-weight: bold; 
                margin-left: 5px;
            }
                                                    
           
            input[type='button'], input[type='submit'], .button{
                color: white;
                font-size: 11px;
                font-family: \"Lucida Sans\",\"Lucida Grande\",Lucida,Verdana,sans-serif;
                text-decoration: none;
                border:none;
                padding: 5px 10px;
                position: relative;    
                background: #34495E;
                display: inline-block;    
            }
            
            input[type='button']:hover, input[type='submit']:hover, .button:hover{
                background: #3B5876;
                cursor: pointer;
            }          
                
            </style>";
                      
        if ($section != '')
        {
            $data = $section;
        }
        
        if ($link != '')
        {    
            $data.= $link;
        }                      
        
        $error = new Av_notice('LOGOUT', array($data), $action);
        $error->display('width: 95%; font-size: 12px; margin: auto;');       
    }
    
    
    /* 
    **********************************
    ************* Engines  ***********
    **********************************
    */
    
    
    /**
    * This function returns the default engine of system
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return string
    */
     public static function get_default_engine($conn)
     {
        Ossim_db::check_connection($conn);
                
        $conf           = $GLOBALS['CONF'];
        $default_engine = str_replace('-', '', strtoupper($conf->get_conf('default_engine_id')));
        
        return $default_engine;
    }
    
    
    /**
    * This function returns an array with all engines defined in the system
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_engines($conn) 
    {
        Ossim_db::check_connection($conn);
        
        $engines = array();
        
        $query = "SELECT HEX(id) AS id FROM acl_entities WHERE entity_type = 'engine'";        
        
        $rs = $conn->Execute($query);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF) 
            {
                $engines[$rs->fields['id']] = TRUE;
                $rs->MoveNext();
            }
        }  
        
        return $engines;
    }
    

    /**
    * This function returns an array with all entities defined in the system
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_all_entities($conn) 
    {
        Ossim_db::check_connection($conn);
        
        $entities = array();
        $ctxs     = self::get_ctx_where();
        
        $query = "SELECT HEX(id) AS id, name FROM acl_entities";
        
        if ($ctxs) 
		{
			$query .= " WHERE id in ($ctxs)";
		}        
        
        $rs = $conn->Execute($query);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF) 
            {
                $entities[$rs->fields['id']] = $rs->fields['name'];
                
                $rs->MoveNext();
            }            
        }
        
        return $entities;
    }
    
        
    /**
    * This function returns an array with all engines associated to context $ctx
    *
    * @param object $conn  Database access object
    * @param string $ctx   Context
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_engines_by_ctx($conn, $ctx) 
    {
        Ossim_db::check_connection($conn);
        
        $engines = array();
        
        $params  = array($ctx, $ctx);
        $query   = "SELECT HEX(c.engine_ctx) AS id 
                    FROM corr_engine_contexts c, acl_entities a1, acl_entities a2
                    WHERE c.event_ctx = UNHEX(?) AND c.engine_ctx = a1.id AND a2.id = UNHEX(?) 
                    AND a1.server_id = a2.server_id";                
        
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return $engines;
        }
        
        while (!$rs->EOF) 
        {
            $engines[] = $rs->fields['id'];
            
            $rs->MoveNext();
        }
                
        return $engines;
    }
    
    
    /* 
    **********************************
    ************ Entities  ***********
    **********************************
    */  
    
    
    /**
    * This function returns TRUE if system uses multitenancy structure (entities), otherwise it returns FALSE
    *
    * Notes: In Open Source version, this function always returns FALSE.
    *
    * @return boolean
    */
    public static function show_entities() 
    {
        return $_SESSION['_user_vision']['show_entities'];
    }
    
    
    /**
    * This function returns an array with all entities within user scope (logged user)
    *
    * @param object $conn  Database access object
    *
    * @return array
    */
    public static function get_entities_to_assign ($conn) 
    {
        return (self::is_pro()) ? Acl::get_entities_to_assign($conn) : array();
    }
    
    
    /**
    * This function updates the name of default entity in the system
    *
    * @param object $conn  Database access object
    * @param string $name  New entity name
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
	public static function update_default_entity_name($conn, $name)
	{
	    Ossim_db::check_connection($conn);
	   
        $params = array($name);
        $query  = "UPDATE acl_entities SET name = ? WHERE name='My Company'";
                                
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        $params = array($name.' Engine');
        $query  = "UPDATE acl_entities SET name = ? WHERE name='Default Engine'";
                
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
    * This function returns the entity name of entity passed as parameter.
    *
    * If the entity doesn't exists, it returns 'Unknown entity'
    * 
    * @param object $conn       Database access object
    * @param string $entity_id  Entity ID (Hexadecimal ID) to search
    * @param boolean $cache     [Optional] Use cached information
    *
    * @throws Exception  If an error occurred
    *
    * @return string Entity name          
    */   
    public static function get_entity_name($conn, $entity_id, $cache = FALSE) 
    {
        Ossim_db::check_connection($conn);
        
        $name = _('Unknown entity');
        /* 
        VERY IMPORTANT:
        The return value "Unknown entity" is used to compare results, so if you modify this value you 
        must change it in the places where it is used. For instance, it is used in the class Tree.
        */
        
        if (empty($entity_id))
        {
            return $name;
        }
    
        $query = 'SELECT name FROM acl_entities WHERE id = UNHEX(?)';        
    
        $params = array($entity_id);                   
        
        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);        
                               
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else
        {
            $name = (empty($rs->fields['name'])) ? _('Unknown entity') : $rs->fields['name']; 
        }
        
        return $name;
    }
    
    
    /**
    * This function returns the entity type of entity passed as parameter.
    *
    * If the entity doesn't exists, it returns 'Unknown Type'
    * 
    * @param object $conn       Database access object
    * @param string $entity_id  Entity ID (Hexadecimal ID) to search
    * @param boolean $cache     [Optional] Use cached information
    *
    * @throws Exception  If an error occurred
    *
    * @return string Entity type          
    */  
    public static function get_entity_type($conn, $entity_id, $cache = FALSE) 
    {
        Ossim_db::check_connection($conn);
        
        $type = _('Unknown Type');
        
        if (empty($entity_id))
        {
            return $type;
        }
    
        $query = 'SELECT entity_type FROM acl_entities WHERE id = UNHEX(?)';        
    
        $params = array($entity_id);  
               
        $rs = ($cache) ? $conn->CacheExecute ($query, $params) : $conn->Execute ($query, $params);        
        
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else
        {
            $type = (empty($rs->fields['entity_type'])) ? _('Unknown Type') : $rs->fields['entity_type']; 
        }
                
        return $type;
    }
    
    
    /**
    * This function returns an array with all entity types defined in the system
    *
    * @param object $conn    Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_entity_types($conn, $cache = FALSE) 
    {
        Ossim_db::check_connection($conn);
        
        $types = array();
        $query = "SELECT HEX(id) AS id, entity_type FROM acl_entities";        
    
        $rs = ($cache) ? $conn->CacheExecute ($query) : $conn->Execute ($query);   
             
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return array();
        } 
       
        while (!$rs->EOF) 
        {
            $types[$rs->fields['id']] = $rs->fields['entity_type'];
            
            $rs->MoveNext();
        }       
                
        return $types;
    }
        
    
    /* 
    **********************************
    **********    LICENSE    *********
    **********************************
    */ 


    /**
    * This function retrieves the system license from db.
    *
    * @return string
    */  
    public static function get_system_license()
    {
        $license = FALSE;
        $conf    = $GLOBALS['CONF'];
        
        if (!$conf)
        {            
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }

        $_license = $conf->get_conf('license');

        if (!empty($_license)) 
        {
            $_license = trim($_license) . "\n";
            $license  = parse_ini_string($_license, TRUE, INI_SCANNER_RAW);
        }

        return $license;        
    } 


    /**
    * This function checks if the trial days have run out.
    *
    * Cases:
    *
    *  Open Source: Returns high value -> 999999
    *
    *  Trial Empty License or error: Returns 0
    *
    *  Trial Expired: Returns -1
    *
    *  Trial Available: Returns the number of days to expire.
    *
    * @return integer
    */     
    public static function trial_days_to_expire()
    {
        //There is no trial in Open Source.
        if (!self::is_pro()) 
        {
            return 999999;
        }

        $license = self::get_system_license();
        
        //If empty license or invalid license file.
        if ($license === FALSE) 
        {
            return 0;
        }

        $time_exp = strtotime($license['appliance']['expire']);
        
        //If the field time to expire in the license file is not set up, the returns 0.
        if(empty($time_exp)) 
        {
            return 0;
        }

        $timeutc = gmdate('U');
        
        //If the license is expired, then return -1.
        if($time_exp <= $timeutc)
        {
            return -1;
        } 
        else
        {
            //The license is not expired yet, then returns the numbers of days to expire.
            return ceil(($time_exp - $timeutc) / 86400);
        }    
    }
    
    
    /**
    * This function checks if user session has expired (PCI compliance requirement)
    *
    * If user session has expired, logged user will be redirected to login page, 
    * otherwise expiration time will be updated to current time
    *
    * 
    */   
    public static function is_expired()
    {        
        $conf = $GLOBALS['CONF'];
        
        if (!$conf)
        {           
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        
        $expired_timeout = intval($conf->get_conf('session_timeout')) * 60;
                                    
        if ($expired_timeout != 0)
        { 
            $time = gmdate('U');
        
            if (isset($_SESSION['_expiration_time']) && intval($_SESSION['_expiration_time']) + $expired_timeout < $time) 
            {
                $ossim_link     = $conf->get_conf('ossim_link');
                $login_location = preg_replace("/(\/)+/","/",$ossim_link . '/session/login.php?action=logout');    
                
                header("Location: $login_location");
            }
            
            //Only update if not exists bypass => header ajax responses
            if (intval(GET('bypassexpirationupdate')) != 1 && intval(POST('bypassexpirationupdate')) != 1) 
            {
                $_SESSION['_expiration_time'] = $time;
                
                Session_activity::update($_SESSION['_expiration_time']);
            }
        }
    }
}      


/* End of file session.inc */
/* Location: ../includes/classes/session.inc */