<?php
/**
* ossec_agentless.inc
*
* File ossec_agentless.inc is used to:
*   - To manage Agentless
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
* @package    ossim-framework\Ossec
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Ossec_agentless Class
*
* Class for managing Agentless
*
* @package    ossim-framework\Ossec
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Ossec_agentless 
{
    
    /**
     * Passlist Path
     *
     */  
    const PASSLIST_PATH = 'ossec/agentless/.passlist';


    /**
     * @var string  Agentless's IP
     * @access private 
     */
    private $ip;


    /**
     * @var string  Agentless's sensor
     * @access private 
     */
    private $sensor;


    /**
     * @var string  Agentless's hostname
     * @access private 
     */
    private $hostname;


    /**
     * @var string  User to connect to the agentless
     * @access private 
     */
    private $user;


    /**
     * @var string  Password to connect to the agentless
     * @access private 
     */
    private $pass;


    /**
     * @var string  Agentless's Privileged Password
     * @access private 
     */
    private $ppass;
    
    
    /**
     * @var string  Agentless's "use su" property
     * @access private 
     */
    private $use_su;


    /**
     * @var int  Current status of the agentless
     * @access private 
     */
    private $status;


    /**
     * @var string  Agentless's description
     * @access private 
     */
    private $descr;
        

    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $ip         Ip of the agentless
    * @param string  $sensor     Agentless's sensor
    * @param string  $hostname   Agentless's hostname
    * @param string  $user       User to connect to the agentless
    * @param string  $pass       Password to connect to the agentless
    * @param string  $descr      Agentless's description   
    * @param int     $status     [Optional] Current status of the agentless
    * @param string  $ppass      [Optional] Agentless's privileged password
    * @param boolean $use_su     [Optional] Agentless's "use su" property
    */
    public function __construct($ip, $sensor, $hostname, $user, $pass, $descr, $status = 1, $ppass = NULL, $use_su = FALSE)
    {
        $this->ip        = $ip;
        $this->sensor    = $sensor;
        $this->hostname  = Util::htmlentities($hostname);
        $this->user      = $user;
        $this->pass      = $pass;
        $this->ppass     = $ppass;
        $this->use_su    = $use_su;
        $this->status    = $status;
        $this->descr     = $descr;
    }
    
    
    /**
     * This function returns the IP of the agentless
     *
     * @return string
     */
    public function get_ip()
    {
        return $this->ip;
    }


    /**
    * This function returns the sensor of the agentless
    *
    * @return string
    */
    public function get_sensor()
    {
        return $this->sensor;
    }
    

    /**
     * This function returns the hostname of the agentless
     *
     * @return string
     */
    public function get_hostname()
    {
        return $this->hostname;
    }


    /**
    * This function returns the user used to connect to the agentless
    *
    * @return string
    */
    public function get_user()
    { 
        return $this->user;
    }


    /**
    * This function returns the password used to connect to the agentless
    *
    * @return string
    */
    public function get_pass()
    { 
        return $this->pass;
    }


    /**
     * This function returns the "use su" option of the agentless
     *
     * @return boolean
     */
    public function get_use_su()
    { 
        return $this->use_su;
    }


    /**
     * This function returns the privileged password of the agentless
     *
     * @return string
     */
    public function get_ppass()
    { 
        return $this->ppass;
    }


    /**
     * This function returns the status of the agentless
     *
     * @return integer
     */
    public function get_status()
    {
        return $this->status;
    }


    /**
    * This function returns the description of the agentless
    *
    * @return string
    */
    public function get_descr()
    {
        return $this->descr;
    }


    /**
     * This function change the status of an agentless
     *
     * @param object  $conn     Database access object
     * @param int     $status   Status to set up
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public function set_status($conn, $status)
    {
        Ossim_db::check_connection($conn);

        $ip        = $this->get_ip();
        $sensor    = $this->get_sensor();
        $hostname  = $this->get_hostname();
        $user      = $this->get_user();
        $pass      = $this->get_pass();
        $ppass     = $this->get_ppass();
        $use_su    = $this->get_use_su();
        $descr     = html_entity_decode($this->get_descr());

        $res = self::save_in_db($conn, $ip, $sensor, $hostname, $user, $pass, $ppass, $use_su, $descr, $status);

        return $res;
    }



    /*************************************************
     *************** Static functions ****************
     *************************************************/


    /**
     * This function returns the text associated to agentless entry type
     *
     * @param string $type Agentless entry type
     *
     * @return string  Text associated to agentless entry type
     */
    public static function get_type($type)
    {
        $array_types = array (
            'ssh_integrity_check_bsd'    => 'Integrity Check BSD',
            'ssh_integrity_check_linux'  => 'Integrity Check Linux',
            'ssh_generic_diff'           => 'Generic Command Diff',
            'ssh_pixconfig_diff'         => 'Cisco Config Check',
            'ssh_foundry_diff'           => 'Foundry Config Check',
            'ssh_asa-fwsmconfig_diff'    => 'ASA FWSMconfig Check'
           );

        $txt_type = (!empty($array_types[$type])) ? $array_types[$type] : _('Unknown');

        return $txt_type;
    }


    /**
     * Function is_in_db
     *
     * This function checks if the agentless exists into database
     *
     * @param object  $conn        Database access object
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string  $ip          Agentless IP
     *
     * @access public
     *
     * @throws Av_exception If a connection error occurred
     *
     * @return boolean
     */
    public static function is_in_db($conn, $sensor_id, $ip)
    {
        Ossim_db::check_connection($conn);

        $res = FALSE;

        if (!valid_hex32($sensor_id) && Asset_host_ips::valid_ip($ip))
        {
            return $res;
        }

        $query  = "SELECT count(*) AS found FROM host_agentless WHERE ip = ? AND sensor_id = ?";
        $params = array($ip, $sensor_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }

        return $res;
    }


    /**
     * This function returns an array of agentless
     *
     * @param object $conn       Database access object
     * @param string $sensor_id  Sensor where we want to get the agentless list
     * @param string $args       [Optional] SQL condition
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_list($conn, $sensor_id, $extra = '')
    {
        Ossim_db::check_connection($conn);

        $list  = array();
        $total = 0;

        $iffoundrows = preg_match("/LIMIT/i", $extra) ? TRUE : FALSE;
        $uuid        = Util::get_encryption_key();

        if ($iffoundrows)
        {
            $select = "SQL_CALC_FOUND_ROWS *, AES_DECRYPT(pass,'$uuid') AS pass_decrypt, AES_DECRYPT(ppass,'$uuid') AS ppass_decrypt";
        }
        else
        {
            $select = "*, AES_DECRYPT(pass,'$uuid') AS pass_decrypt, AES_DECRYPT(ppass,'$uuid') AS ppass_decrypt";
        }

        $params = array($sensor_id);
        $query = ossim_query("SELECT $select FROM host_agentless WHERE sensor_id = ? $extra");

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
       
        if ($iffoundrows)
        {
            $total = Ossim_db::get_found_rows($conn, $query, FALSE);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $ip = $rs->fields['ip'];

            $list[$ip] = new self(
                $ip,
                $rs->fields['sensor_id'],
                $rs->fields['hostname'],
                $rs->fields['user'],
                (empty($rs->fields['pass_decrypt'])) ? $rs->fields['pass'] : $rs->fields['pass_decrypt'],
                $rs->fields['descr'],
                $rs->fields['status'],
                (empty($rs->fields['ppass_decrypt'])) ? $rs->fields['ppass'] : $rs->fields['ppass_decrypt'],
                $rs->fields['use_su']
            );
            
            $rs->MoveNext();
        }

        return array($list, $total);
    }
    

    /**
    * This function returns an agentless
    *
    * @param object $conn       Database access object
    * @param string $sensor_id  Sensor of the agentless
    * @param string $ip         Ip of the agentless
    *
    * @throws Exception  If an error occurred
    *
    * @return object
    */
    public static function get_object($conn, $sensor_id, $ip)
    {
        Ossim_db::check_connection($conn);
        
        $agentless = NULL;

        $uuid      = Util::get_encryption_key();
        
        $params    = array($ip, $sensor_id);
        $query     = "SELECT *, AES_DECRYPT(pass,'$uuid') AS pass_decrypt, AES_DECRYPT(ppass,'$uuid') AS ppass_decrypt
                        FROM host_agentless
                        WHERE ip = ? AND sensor_id = ?";

        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if (!$rs->EOF)
        {
            $agentless = new self(
                $rs->fields['ip'],
                $rs->fields['sensor_id'],
                $rs->fields['hostname'],
                $rs->fields['user'], 
                (empty($rs->fields['pass_decrypt'])) ? $rs->fields['pass'] : $rs->fields['pass_decrypt'], 
                $rs->fields['descr'],
                $rs->fields['status'],
                (empty($rs->fields['ppass_decrypt'])) ? $rs->fields['ppass'] : $rs->fields['ppass_decrypt'],
                $rs->fields['use_su']
            );
        }

        return $agentless;
    }
 


    /****************************************************************************************/
    /*                                                                                      */
    /*                                HOST DATA FUNCTIONS                                   */
    /*                                                                                      */
    /****************************************************************************************/
    
    /**
    * This function saves an agentless into DB.
    *
    * @param object  $conn          Database access object
    * @param string  $ip            Ip of the agentless
    * @param string  $sensor_id     Sensor of the agentless
    * @param string  $hostname      Hostname of the agentless
    * @param string  $user          User used to connect to the agentless
    * @param string  $pass          Password used to connect to the agentless
    * @param string  $ppass         Privileged password of the agentless
    * @param string  $descr         Description of the agentless
    * @param int     $status        [Optional] Status of the agentless
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    
    public static function save_in_db($conn, $ip, $sensor_id, $hostname, $user, $pass, $ppass, $use_su, $descr, $status = 1)
    {
        Ossim_db::check_connection($conn);
        
        $is_in_db = self::is_in_db($conn, $sensor_id, $ip);
        $uuid     = Util::get_encryption_key();

        //Begin transaction
        $conn->StartTrans();
        
        
        if ($is_in_db == FALSE)
        {
            $q_pass  = (!empty($pass))  ? "AES_ENCRYPT(?,'$uuid')" : "?";
            $q_ppass = (!empty($ppass)) ? "AES_ENCRYPT(?,'$uuid')" : "?";
            
            $params = array(
                $ip,
                $sensor_id,
                $hostname,
                $user,
                $pass,
                $ppass,
                intval($use_su),
                Util::utf8_decode2($descr),
                intval($status)
            );
            
            $query = "REPLACE INTO host_agentless (ip, sensor_id, hostname, user, pass, ppass, use_su, descr, status)
                      VALUES (?, ?, ?, ?, ".$q_pass.", ".$q_ppass.", ?, ?, ?)";
        }
        else
        {
            if (Util::is_fake_pass($pass))
            {
                $q_pass = '';
            }
            else
            {
                $q_pass = (!empty($pass)) ? "pass = AES_ENCRYPT(?,'$uuid')," :  "pass = ?,";
                
            }
            
            if (Util::is_fake_pass($ppass))
            {
                $q_ppass  = '';
            }
            else
            {
                $q_ppass  = (!empty($ppass)) ? "ppass = AES_ENCRYPT(?,'$uuid')," : "ppass = ?,";
            }      
            
            $params[] = $hostname;
            $params[] = $user;
            
            if (!Util::is_fake_pass($pass))
            {
                $params[] = $pass;
            }
            
            if (!Util::is_fake_pass($ppass))
            {
                $params[] = $ppass;
            }
            
            $params[] = Util::utf8_decode2($descr);
            $params[] = intval($status);
            $params[] = intval($use_su);
            $params[] = $ip;
            $params[] = $sensor_id;

            
            $query = "UPDATE host_agentless SET hostname=?, user=?, ".$q_pass." ".$q_ppass." descr = ?, status = ?, use_su = ? 
                WHERE ip = ? AND sensor_id = ?;";
        }
        

        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        
        //Finish transaction
        if ($conn->CompleteTrans())
        {
            if($status != 2)
            {
                exec("touch /var/tmp/.reload_$sensor_id");
            }
        }
        else
        {
            $exp_msg = _('Error! Agentless could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }
    
    
    /**
    * This function delete an agentless from DB.
    *
    * @param object  $conn        Database access object    
    * @param string  $sensor_id   Sensor of the agentless
    * @param string  $ip          Agentless IP
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_from_db($conn, $sensor_id, $ip)
    {
        Ossim_db::check_connection($conn);
        
        //Begin transaction
        $conn->StartTrans();
        
        $params = array($ip, $sensor_id);
        $query  = "DELETE FROM host_agentless_entries WHERE ip=? AND sensor_id=?;";
               
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        $query = "DELETE FROM host_agentless WHERE ip = ? AND sensor_id = ?;";
        
        $rs = $conn->Execute($query, $params);
                    
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        if ($conn->CompleteTrans())
        {
            
            self::delete_from_config($sensor_id, $ip);
            
            exec("touch /var/tmp/.reload_$sensor_id");
        }
       
        return TRUE;
    }
    


    /****************************************************************************************/
    /*                                                                                      */
    /*                           OSSEC CONFIGURATION FUNCTIONS                              */
    /*                                                                                      */
    /****************************************************************************************/

    /**
    * This function returns an array of agentless stored in the ossec configuration file 'passlist'
    *
    * @param object  $conn       Database access object
    * @param string  $sensor_id  Sensor where we want to get the agentless list
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_list_ossec($conn, $sensor_id)
    {
        Ossim_db::check_connection($conn);

        $list  = array();
        $total = 0;

        //Getting agentless list

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->list_ossec_agentless();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $available_hosts = $response['data']['agents'];
        
        if (is_array($available_hosts) && !empty($available_hosts))
        {
            $total = count($available_hosts);
            
            foreach ($available_hosts as $agent_id => $a_data)
            {
                //Checking if the configuration lines is right
                if(count($a_data) != 2) 
                {
                    continue;    //invalid passlist line: user@ip|pass|ppass
                }

                $add       = TRUE;
                $user_host = explode('@', $agent_id);
                $user      = $user_host[0];
                $pass      = $a_data['pass'];
                $ppass     = (empty($a_data['ppass'])) ? NULL : $a_data['ppass'];
                $descr     = NULL;
                $use_su    = FALSE;
                
                if (!empty($ppass))
                {
                    $use_su = TRUE;
                }
    
                // Getting hostname
                if (Asset_host_ips::valid_ip($user_host[1]))
                {
                    $ip        = $user_host[1];
                    $hostnames = Asset_host::get_name_by_ip($conn, $ip);
                    
                    if (empty($hostnames) || count($hostnames) > 1)
                    {
                        $hostname = $ip;
                    }
                    else
                    {
                        $hostname = array_pop($hostnames);
                    }
                }
                else
                {
                    $hostname = $user_host[1];
                    $ips      = Asset_host::get_ips_by_name($conn, $hostname);
                    $add      = (empty($ips) || count($ips) > 1) ? FALSE : TRUE;
                }
                
                if ($add == TRUE)
                {
                    $list[$ip] = new self ($ip, $sensor_id, $hostname, $user, $pass, $descr, 2, $ppass, $use_su);
                }
            }
        }

        return array($list, $total);
    }
    

    /**
    * This function syncronize agentless from configuration files into DB
    *
    * @param object  $conn       Database access object
    * @param string  $sensor_id  Sensor where we want to get the agentless list
    *
    * @throws Exception  If an error occurred
    *
    * @return void
    */
    public static function syncronize_ossec_agentless($conn, $sensor_id)
    {
        Ossim_db::check_connection($conn);
        
        $info_error            = '';
        $agentless_entry_ossec = array();

        try
        {
            //First we get the agentless from DB and the agentless from the configuration files to compare them.
            $_ossec_list = self::get_list_ossec($conn, $sensor_id);
            $ossec_list  = $_ossec_list[0];
            
            $_agentless_list = self::get_list($conn, $sensor_id);
            $agentless_list  = $_agentless_list[0];

            //Walking through the agentless from the configuration list
            foreach ($ossec_list as $ip => $v)
            {
                //Checking the ip is valid, otherwise we skip it
                if(!Asset_host_ips::valid_ip($ip)) 
                {
                    continue;
                }
                 
                //Trying to get a correspondence between configuration file and DB
                $w = $agentless_list[$ip];
                
                //If the agentless does not exist in DB, we add it
                if (!is_object($w)) 
                {
                    try
                    {
                        self::save_in_db($conn, $v->get_ip(), $sensor_id, $v->get_hostname(), $v->get_user(), $v->get_pass(), $v->get_ppass(), $v->get_use_su(), '', $v->get_status());
                    }
                    catch(Exception $e)
                    {
                        $info_error .= _('Impossible to add agentless found in config files') . "($ip) \n";

                        continue;
                    }
                  
                    //If the agentless is added we'll have to add the agentless entries later!!
                    $agentless_entry_ossec[$v->get_ip()] = 1;
                }
                else
                {   
                    //If the agentless already exists in DB, we modified it to keep it up to date_add
                    
                    if($w->get_status() == 2) //We modify it only if the one stored in DB has status = 2
                    {
                        if($w->get_user() != $v->get_user() || $w->get_pass() != $v->get_pass() || $w->get_ppass() != $v->get_ppass())
                        {
                            try
                            {
                                self::save_in_db($conn, $ip, $sensor_id, $w->get_hostname(), $v->get_user(), $v->get_pass(), $v->get_ppass(), $w->get_use_su(), $w->get_descr(), 2);
                            }
                            catch(Exception $e)
                            {
                                $info_error .= _('Impossible to syncronize agentless values from db and found in config files') . "($ip) \n";

                                continue;
                            }
                        }
                    }
                }
            }

            //If we have the agentless stored in DB with status ok (everything ok) and we can't find it in the configuration file we change the status to 1.
            foreach ($agentless_list as $ip => $v)
            {
                if(!$ossec_list[$ip]) 
                {
                    if($v->get_status() == 2)
                    {
                        $v->set_status($conn, 1);
                    }
                }
            }

            //If we have added agentless, we have to add the entries too
            if (!empty($agentless_entry_ossec))
            {
                //Getting the ossec.conf to retrieve the agentless entries.
                $conf_file = NULL;
                
                try
                {
                    $conf_file = Ossec::get_configuration_file($sensor_id);
                    
                    //We need the xml file under a unique tag for being able to process it.
                    $conf_file =  '<av_doc_root>' . $conf_file . '</av_doc_root>';
                    $xml_conf  = new DOMDocument();
                    
                    if($xml_conf->loadXML($conf_file) !== FALSE)
                    {
                        $oc = $xml_conf->getElementsByTagName('ossec_config');
                        
                        foreach ($oc as $tag)
                        {
                            $x = $tag->getElementsByTagName('agentless');
    
                            foreach ($x as $item)
                            {
                                $_host = $item->getElementsByTagName('host')->item(0)->nodeValue;
                                $ip    = trim(preg_replace("/.*@/",'', $_host));
    
                                if($agentless_entry_ossec[$ip])
                                {
                                    $arguments   = $item->getElementsByTagName('arguments')->item(0)->nodeValue;
                                    $frequency   = $item->getElementsByTagName('frequency')->item(0)->nodeValue;
                                    $state       = $item->getElementsByTagName('state')->item(0)->nodeValue;
                                    $type        = $item->getElementsByTagName('type')->item(0)->nodeValue;
                                    
                                    try
                                    {
                                        self::add_monitoring_entry($conn, $ip, $sensor_id, $type, $frequency, $state, $arguments, FALSE);
                                    }
                                    catch(Exception $e)
                                    {
                                        $info_error .= _('Impossible to add agentless entries found in config files') . "($ip) \n";
                                        
                                        continue;    
                                    }                      
                                }
                            }                    
                        }
                    }                              
                }
                catch(Exception $e)
                {
                    $info_error .= _('Impossible to parse ossec.conf file') . "\n"; 
                }               
            }         
        }
        catch(Exception $e)
        {
            $info_error = $e->getMessage();
        }     

        //If we got any error, we store them in a temp file.
        if($info_error != '')
        {         
            //Logged user
            $user = Session::get_session_user();
            
            //Error file
            $agenteless_error_log = "/tmp/_agentless_error_$user".'.log';
            
            $info_error = '<b>' . _('Error trying to syncronize ossec conf files and agentless values stored in db') . ": </b>\n" . $info_error;
            @file_put_contents($agenteless_error_log, $info_error);
        }
    }


    /**
    * This function modify the agentless specified from configuration files into DB
    *
    * @param object  $conn            Database access object
    * @param string  $sensor_id       Sensor where we want to modify the agentless list
    * @param array   $agentless_list  Agentless list to modify
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function save_in_config($conn, $sensor_id, $agentless_list)
    {
        Ossim_db::check_connection($conn);

        //First we get the current configuration file
        $conf_data = Ossec::get_configuration_file($sensor_id);
        $conf_file = $conf_data['data'];

        //We have to retrieve also the passlist file
        $passlist_data = self::get_passlist_file($sensor_id);
        $passlist_file = $passlist_data['data'];

        //We walk through the agentless we want to modify
        foreach ($agentless_list as $agentless)
        {
            //Agentless data
            $ip        = $agentless->get_ip();
            $sensor_id = $agentless->get_sensor();
            $user      = $agentless->get_user();
            $pass      = $agentless->get_pass();
            $ppass     = $agentless->get_ppass();
            $use_su    = $agentless->get_use_su();
            $host      = '';

            if ($use_su && !empty($ppass))
            {
                $host = "use_su ";
            }
 
            $host .= $user."@".$ip;

            /******************** Modifying the entries of the agentless ********************/

            //First we delete the current configuration from the configuration file
            $conf_file = self::delete_host_entries_ossec($conf_file, $ip);

            if($conf_file === FALSE)
            {
                $exp_msg = _('Impossible to parse ossec.conf file');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            } 

            $extra   = " AND ip = '$ip'";
            $entries = self::get_list_m_entries($conn, $sensor_id, $extra);

            if (is_array($entries))
            {
                $_entries_xml = array();

                foreach ($entries as $entry)
                {
                    $_entry_xml              = array();
                    $_entry_xml['type']      = $entry['id_type'];
                    $_entry_xml['frequency'] = $entry['frequency'];
                    $_entry_xml['host']      = $host;
                    $_entry_xml['state']     = $entry['state'];

                    if($entry['arguments'] != '') 
                    {
                        $_entry_xml['arguments'] = html_entity_decode($entry['arguments']);
                    }

                    $_entries_xml[] = $_entry_xml;
                }

                //Adding the new entrie into the configuration file
                $conf_file = self::add_host_entries_ossec($conf_file, $_entries_xml);

                if($conf_file === FALSE)
                {
                    $exp_msg = _('Impossible to parse ossec.conf file');

                    Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                }
            }


            /****************************** Modifying the passlist ******************************/

            //First we delete the current agentless configuration from the passlist
            $passlist_file = self::delete_host_passlist($passlist_file, $ip);

            //Then we add the new configuration to the passlist file
            $host          = $user."@".$ip;
            $passlist_line = array($host,$pass,$ppass);
            $passlist_file = self::add_host_host_passlist($passlist_file, $passlist_line);
        }


        /****************************** SAVING CONFIGURATIONS ******************************/

        //Once we have modified the configuration file and the pass list, we set them up
        self::set_passlist_file($passlist_file, $sensor_id);
        Ossec::set_configuration_file($sensor_id, $conf_file);


        //Modifying status once the configuration files have been updated
        foreach ($agentless_list as $agentless)
        {
            $agentless->set_status($conn, 2);
        }

        return TRUE;
    }
    
    
    /**
    * This function delete an agentless from the passlist file
    *
    * @param string  $passlist   Passlist content
    * @param string  $entries    Ip of the agentless
    *
    * @return string  Passlist file modified without the entry of the selected agentless
    */
    public static function delete_host_passlist($passlist, $ip)
    {
        $pattern  = "/@$ip/";
        $new_file = '';

        $passlist = explode("\n", $passlist);

        foreach ($passlist as $k => $v)
        {
            if(preg_match($pattern, $v) || $v == '')
            {
                unset($passlist[$k]);
            }
        }

        if(!empty($passlist))
        {
            $new_file = implode("\n", $passlist);
        }

        return $new_file;
    }


    /**
    * This function add an agentless to the passlist file
    *
    * @param string  $passlist   Passlist file
    * @param array   $elements   Elements to add to the passlist line
    *
    * @return string  Passlist file modified with the new entry of the selected agentless
    */
    public static function add_host_host_passlist($passlist, $elements)
    {
        $line = implode('|', $elements);

        //A passlist line has the arguments separated by '|'
        if($line != '')
        {
            if(!preg_match("/\n$/", $passlist))
            {
               $passlist .= "\n";
            }

            $passlist .= $line . "\n";
        }

        return $passlist;
    }
    

    /**
    * This function delete an agentless from the configuration files
    *     
    * @param string  $sensor_id  Sensor where we want to get the agentless list
    * @param string  $ip         Ip of the agentless
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_from_config($sensor_id, $ip)
    {
        //Remove agentless from .passlist
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->delete_ossec_agentless($ip);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        //Getting the configuration file 
        $conf_file = Ossec::get_configuration_file($sensor_id);
                
        //Removing agentless entries from configuration file
        $new_conf = self::delete_host_entries_ossec($conf_file['data'], $ip);
        
        if($new_conf === FALSE)
        {
            $exp_msg = _('Impossible to parse ossec.conf file');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        //Setting up the configuration file once the agentless entries have been removed
        Ossec::set_configuration_file($sensor_id, $new_conf);

        return TRUE;
    }


    /**
    * This function retrieves the passlist file of a sensor
    *
    * @param string $sensor_id  Sensor where we want to retrieve the passlist (Table alienvault.sensor)
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_passlist_file($sensor_id)
    {
        $data = array();
       
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->get_passlist();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $passlist_file = $response['data']['local_path'];        

        $data['path'] = $passlist_file;
        $data['data'] = @file_get_contents($passlist_file);

        if ($data['data'] === FALSE)
        {
            $exp_msg = _('Error! Failed to get the passlist file. You do not have enough access privileges');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $data;
    }


    /**
    * This function set a new configuration of the passlist file of a sensor
    *
    * @param string $sensor_id  Sensor where we want to retrieve the passlist. (Table alienvault.sensor)
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function set_passlist_file($new_passlist, $sensor_id)
    {
        $data['status']  = 'success';
        $data['msg']     = _('passlist file updated successfully');
        $data['data']    = $new_passlist;

        $pass_file = Ossec_utilities::get_real_path($sensor_id, self::PASSLIST_PATH);

        if (@file_put_contents($pass_file, $new_passlist, LOCK_EX) === FALSE)
        {
            $exp_msg = _('Error! Failed to set passlist file. You do not have enough access privileges');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->set_passlist();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $data;
    }



    /****************************************************************************************/
    /*                                                                                      */
    /*                          MONITORING ENTRIES FUNCTIONS                                */
    /*                                                                                      */
    /****************************************************************************************/


    /**
     * This function delete the agentless entries from the configuration file
     *
     * @param string  $conf_file     Configuration file (ossec.conf)
     * @param string  $ip            Ip of the agentless
     *
     * @throws Exception  If an error occurred
     *
     * @return string  Configuration file modified without the entries of the selected agentless
     */
    public static function delete_host_entries_ossec($conf_file, $ip)
    {
        //We need the xml file under a unique tag for being able to process it.
        $conf_file       =  '<av_doc_root>' . $conf_file . '</av_doc_root>';
        
        $items_to_delete = array();
        $xml             = new DOMDocument();  //Doom XML object to manipulate config file

        //Setting up flags to preverse xml format 
        $xml->preserveWhiteSpace = FALSE;
        $xml->formatOutput = TRUE;

        //Loading the XML file into the Doom XML Object
        if($xml->loadXML($conf_file) === FALSE)
        {            
            $exp_msg = _('Impossible to parse ossec.conf file');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        //In order to delete we have to search inside all the xml tags 'ossec_config' and find the elements under the tag 'agentless'
        $oc = $xml->getElementsByTagName('ossec_config');
        
        foreach ($oc as $tag)
        {
            $x = $tag->getElementsByTagName('agentless');

            foreach ($x as $item)
            {
                $_host = $item->getElementsByTagName('host')->item(0)->nodeValue;
                //If the  attribute <host> from the element 'agentless' matches with the ip of the agentless we want to delete, we store this agentless.
                if(preg_match("/@$ip/",$_host))
                {
                    $items_to_delete[] = $item;
                }
            }
        }

        //All the agentless entries found previously are deleted from the XML config file
        foreach ($items_to_delete as $item)
        {
            $item->parentNode->removeChild($item);
        }

        //Saving the new XML
        $new_conf = $xml->saveXML($xml->documentElement, LIBXML_NOEMPTYTAG);

        //Deleting the tags used for parse the XML and the xml version
        $pattern     = array('/<\?xml version=.*\?>/', '/<\/?av_doc_root>/', '/\-\-\&gt;/');
        $replacement = array('', '', '-->');
       
        $new_conf    = preg_replace($pattern, $replacement, $new_conf);

        return trim($new_conf);
    }


    /**
     * This function add agentless entries to the configuration file
     *
     * @param string  $conf_file     Configuration file (ossec.conf)
     * @param string  $entries       Ip of the agentless
     *
     * @throws Exception  If an error occurred
     *
     * @return string  Configuration file modified without the entries of the selected agentless
     */
    public static function add_host_entries_ossec($conf_file, $entries)
    {
        //We need the xml file under a unique tag for being able to process it.
        $conf_file =  '<av_doc_root>' . $conf_file . '</av_doc_root>';

        $xml = new DOMDocument();   //Doom XML object to manipulate config file

        //Setting up flags to preverse xml format 
        $xml->preserveWhiteSpace = FALSE;
        $xml->formatOutput = TRUE;

        //Loading the XML file into the Doom XML Object
        if($xml->loadXML($conf_file) === FALSE)
        {            
            $exp_msg = _('Impossible to parse ossec.conf file');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $oc = $xml->getElementsByTagName('ossec_config');

        //If there are elements 'ossec_config' we append the entries to the last one
        if($oc->length > 0)
        {
            $node = $oc->item($oc->length -1);
        }
        //If there is not element 'ossec_config' we have to create it
        else
        {
            $node = $xml->createElement('ossec_config');
            $aux  = $xml->getElementsByTagName('av_doc_root');
            $aux  = $aux->item(0);
            $node = $aux->appendChild($node);
        }

        //Appending the entries
        foreach($entries as $entry)
        {
            $_agentless = $xml->createElement('agentless');
            $_agentless = $node->appendChild($_agentless);

            foreach ($entry as $key => $value) 
            {
                $_aux = $xml->createElement($key, $value);
                $_aux = $_agentless->appendChild($_aux);
            }
        }

        //Saving the new XML
        $new_conf = $xml->saveXML($xml->documentElement, LIBXML_NOEMPTYTAG);

        //Deleting the tags used for parse the XML and the xml version
        $pattern     = array('/<\?xml version=.*\?>/', '/<\/?av_doc_root>/', '/\-\-\&gt;/');
        $replacement = array('', '', '-->');
       
        $new_conf    = preg_replace($pattern, $replacement, $new_conf);

        return trim($new_conf);
    }

    
    
    /**
     * This function returns the agentless related to an agentless entry
     *
     * @param object $conn  Database access object
     * @param string $id    Agentless entry ID
     *
     * @throws Exception  If an error occurred
     *
     * @return object
     */
    public static function get_agentless_by_m_entry($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        $query     = "SELECT ip, sensor_id FROM host_agentless_entries WHERE id =?;";

        $params    = array($id);
        $agentless = NULL;
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
                
        $_ip       = $rs->fields['ip'];
        $_sensor   = $rs->fields['sensor_id'];

        $agentless = self::get_object($conn, $_sensor, $_ip);
        
        return $agentless;
    }
    
    
    /**
     * This function returns a list of agentless entries (Data stored in array)
     *
     * @param object $conn       Database access object
     * @param string $sensor_id  Sensor where we want to get the agentless entries list
     * @param string $extra      [Optional] SQL condition
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_list_m_entries($conn, $sensor_id, $extra = '')
    {
        Ossim_db::check_connection($conn);
        
        $list  = array();
        
        $array_types = array ('ssh_integrity_check_bsd'    => 'Integrity Check BSD',
                              'ssh_integrity_check_linux'  => 'Integrity Check Linux',
                              'ssh_generic_diff'           => 'Generic Command Diff',
                              'ssh_pixconfig_diff'         => 'Cisco Config Check',
                              'ssh_foundry_diff'           => 'Foundry Config Check',
                              'ssh_asa-fwsmconfig_diff'    => 'ASA FWSMconfig Check');
                        
                
        $query = ossim_query("SELECT * FROM host_agentless_entries WHERE sensor_id='$sensor_id' $extra");
                
        $rs = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF)
        {
           $list[] = array(
                'id'        => $rs->fields['id'],
                'ip'        => $rs->fields['ip'],
                'sensor'    => $rs->fields['sensor_id'],
                'type'      => $array_types[$rs->fields['type']],
                'id_type'   => $rs->fields['type'],
                'frequency' => $rs->fields['frequency'],
                'state'     => $rs->fields['state'],
                'arguments' => Util::htmlentities($rs->fields['arguments'])
           );
           
           $rs->MoveNext();
        }
        
        return $list;
    }
    

    /**
    * This function add a new agentless into DB.
     *
     * @param object   $conn         Database access object
     * @param string   $ip           Ip of the agentless
     * @param string   $sensor_id    Sensor of the agentless
     * @param string   $type         Type of the entry
     * @param string   $frequency    Frequency of the entry
     * @param string   $state        State of the entry
     * @param string   $arguments    Arguments of the entry
     * @param boolean  $flag_apply   [Optional] Flag for 'apply configuration'
     *
     * @throws Exception  If an error occurred
     *
     * @return integer                
     */
    public static function add_monitoring_entry($conn, $ip, $sensor_id, $type, $frequency, $state, $arguments, $flag_apply = TRUE)
    {
        Ossim_db::check_connection($conn);
        
        $arguments = preg_replace("/[\r?\n]+/", " ", $arguments);
        
        $params = array(
            NULL,
            $ip,
            $sensor_id,
            $type,
            $frequency,
            $state,
            Util::utf8_decode2($arguments)
        );
        
        $query = "REPLACE INTO host_agentless_entries (id, ip, sensor_id, type, frequency, state, arguments) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {            
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        $params = array();
        $query  = "SELECT last_insert_id() AS id FROM host_agentless_entries";
        
        $rs = $conn->Execute($query);
        
        if (!$rs)
        { 
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        if($flag_apply)
        {
            exec("touch /var/tmp/.reload_$sensor_id");
        } 
        
        $agentless = self::get_object($conn, $sensor_id, $ip);
        
        //Modifying agentless status and changing 'apply configuration' flag
        if($agentless)
        {
            if ($agentless->get_status() != 0 && $flag_apply)
            {
                $agentless->set_status($conn, 1);
            }
        }
        
        return $rs->fields['id'];
    }
    
    
    /**
    * This function add a new agentless into DB.
    *
    * @param object   $conn         Database access object
    * @param string   $type         Type of the entry
    * @param string   $frequency    Frequency of the entry
    * @param string   $state        State of the entry
    * @param string   $arguments    Arguments of the entry
    * @param string   $id           Id of the agentless entry
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function modify_monitoring_entry($conn, $type, $frequency, $state, $arguments, $id)
    {  
        Ossim_db::check_connection($conn);
        
        $arguments = preg_replace("/[\r?\n]+/", " ", $arguments);
        
        $params = array(
            $type,
            $frequency,
            $state,
            Util::utf8_decode2($arguments),
            $id
        );
        
        $query = "UPDATE host_agentless_entries SET type=?, frequency=?, state=?, arguments=? WHERE id=?;";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                
        $agentless = self::get_agentless_by_m_entry($conn, $id);
        
        //Modifying agentless status and changing 'apply configuration' flag
        if($agentless)
        {
            $sensor_id = $agentless->get_sensor();
            
            exec("touch /var/tmp/.reload_$sensor_id"); 
            
            if ($agentless->get_status() != 0)
            {
                $agentless->set_status($conn, 1);
            }
        }
        
        return TRUE;
    }


    /**
    * This function deletes an agentless entry
     *
     * @param object $conn  Database access object
     * @param string $id    Agentless entry ID
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function delete_monitoring_entry($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        //We need to get the related agentless before deleting the entry or we won't be able to get it
        $agentless = self::get_agentless_by_m_entry($conn, $id);
                
        $params = array($id);
        $query  = "DELETE FROM host_agentless_entries WHERE id = ?";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        //Modifying agentless status and changing 'apply configuration' flag
        if($agentless)
        {
            $sensor_id = $agentless->get_sensor();
            
            exec("touch /var/tmp/.reload_$sensor_id");
            
            if ($agentless->get_status() != 0)
            {
                $agentless->set_status($conn, 1);
            }
        }

        return TRUE;
    }
}

/* End of file ossec_agentless.inc */
/* Location: ../include/classes/ossec_agentless.inc */
