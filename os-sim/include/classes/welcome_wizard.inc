<?php
/**
* welcome_wizard.inc
*
* File welcome_wizard.inc is used to:
*   - Manage USM Welcome Wizard
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

require_once 'av_init.php';


/**
* Welcome Wizard Class
*
* Class for managing the USM Welcome Wizard
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Welcome_wizard
{ 
    /**
     * File path where the object is stored
     * @const string
     *
     */
    const WIZARD_SAVE_FILE = '/var/tmp/wizard_status';
             
    /**
     * Step Number
     * @var integer
     *
     */
    private $_step;
    
    
    /**
     * Last Complete Step Number
     * @var integer
     *
     */
    private $_completed_step;
        
    
    /**
     * Step Info
     * @var array
     *
     */
    private $_step_data;
    
    
    /**
     * Flag finish
     * @var boolean
     *
     */
    private $_finished;
    
    
    /**
     * Amount of Steps
     * @var array
     *
     */
    private $_number_steps;
    

    /**
     * Class constructor
     *
     * This function sets up the class
     *
     * @param integer  $type  Wizard Path
     *
     */
    public function __construct()
    {
        $this->_step           = 1;
        $this->_finished       = FALSE;
        $this->_number_steps   = 5;
        $this->_step_data      = array();
        $this->_completed_step = 1;
        
        $this->save_status();
    }


    /**
     * This function save the status of the object
     *
     * @return  void
     */
    public function save_status()
    {
        //Serializing the object
        $wizard = serialize($this);
        
        //Removing the previous object stored in session
        unset($_SESSION['_welcome_wizard_object']);
        
        //Saving in session the object
        $_SESSION['_welcome_wizard_object'] = $wizard;

        //Saving in disk the object
        @file_put_contents(self::WIZARD_SAVE_FILE, $wizard);
    }
    
    
    
    /**********************************************/
    /************    STEP FUNCTIONS    ************/
    /**********************************************/
    
    
    /**
     * This function goes to the next step of the wizard
     *
     * @param integer  Step number
     *
     * @throws Exception  If we want to go over the maximum number of steps
     *
     * @return  string
     */
    protected function _next_step($step)
    {
        for ($i = $this->_step; $i < $step; $i++)
        {
            //Before increasing the step, the current step is validated.
            //If the validation is not successful, an expection will arise 
            $this->_validate_wizard();
            
            //If we are already in the last step, then we finish the wizard
            if ($this->_step == $this->_number_steps)
            {  
                $this->_finished = TRUE; 
                break;
            }
            //Otherwise we increase the current step.
            else
            {
                $this->_step++;
            } 
        }
    }


    /**
     * This function goes to the previous step of the wizard
     *
     * @return  void
     */
    protected function _prev_step($step)
    {
        //We change the step number
        $this->_step = $step;
        
        //Then we clean all the date stored related to steps that are greater than the current step
        $this->_clean_data();

    }
    
    
    /**
     * This function goes to the previous step of the wizard
     *
     * @param integer  Step number
     *
     * @return  void
     *
     */
    public function change_step($step)
    {
        $step = intval($step);
        
        $cond_1 = $step < 1; // We cannot go to step 0
        $cond_2 = $step > $this->_number_steps + 1; // We cannot go over the number steps
        $cond_3 = $step > $this->_completed_step + 1;  // We cannot go over the last activated step
        
        if ($cond_1 || $cond_2 || $cond_3)
        {
            $exp_msg = _('Invalid Wizard Step');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        //If we are not in the first step, the we can go back
        if ($step > $this->_step)
        {
            $this->_next_step($step);
            
            if ($this->_step > $this->_completed_step)
            {
                $this->_completed_step = $this->_step;
            }
        }
        elseif ($step < $this->_step)
        {
            $this->_prev_step($step);
        }
        else
        {
            //We dont want to save the status here because it is the same.
            return $this->is_wizard_finish();
        }
        
        $this->save_status();
        
        return $this->is_wizard_finish();

    }


    /**
     * This function returns the current step
     *
     * @return  integer
     */
    public function get_current_step()
    {
        return $this->_step;
    }


    /**
     * This function returns the last actived step
     *
     * @return  integer
     */
    public function get_last_completed_step()
    {
        /*
        This is to avoid failures when we update from 4.7 to 4.8.
        The object stored in file might not have defined _completed_step.
        */
        if ($this->_step > $this->_completed_step)
        {
            $this->_completed_step = $this->_step;
            
            $this->save_status();
            
        }
        return $this->_completed_step;
    }


    /**
     * This function get the file step to load
     *
     * @return  string
     */
    public function get_step_file()
    {

        $file = 'step_' . $this->_step . '.php';

        return $file;
    }


    /***************************************************/
    /************    STEP DATA FUNCTIONS    ************/
    /***************************************************/
    
    
    /**
     * This function gets the stored data related to a step
     *
     * @param  integer  $id    Id of the data you want to retrieve
     * @param  integer  $step  [Optional] Step where youw ant to retrieve the data
     *
     * @return  mixed
     */
    public function get_step_data($id, $step = '')
    {
        //If $step is not specified we search on the current step
        $step = ($step != '') ? $step : $this->_step;
        
        //Returning the data specified by the id.
        return $this->_step_data[$step][$id];
    }


    /**
     * This function set data to the data related to the step
     *
     * @param  integer  $id     Id of the data you want to set
     * @param  mixed    $value  The data itself
     * @param  boolean  $del    Flag to indicate if you want to delete that data
     *
     * @return  void
     */
    public function set_step_data($id, $value, $del = FALSE)
    {
        //If del is true, we delete the data related to that id in the current step
        if ($del)
        {
            unset($this->_step_data[$this->_step][$id]);
        }
        //Storing the value in the current step under the id given
        else
        {
            $this->_step_data[$this->_step][$id] = $value;
        }
    }


    /**
     * This function remove all the data related to the steps greater than the current step
     *
     * @return  void
     */
    private function _clean_data()
    {
        //We want to delete all the data from step greater than the current step
        $step = $this->_step + 1;
        
        for ($step; $step <= $this->_number_steps; $step ++)
        {
            unset($this->_step_data[$step]);
        }
        
    }


    /**
     * This function remove all the data related to the current step
     *
     * @param integer  [Optional] Step number
     *
     * @return  void
     */
    public function clean_step_data($step = 0)
    {
        //If step is empty or 0, then we use the current step
        $step = empty($step) ? $this->_step : $step;
        
        unset($this->_step_data[$step]);  
    }
    
    

    /*******************************************************/
    /************    FINISH WIZARD FUNCTIONS    ************/
    /*******************************************************/


    /**
     * This function indicates if the wizard is over
     *
     * @return  boolean
     */
    public function is_wizard_finish()
    {
        return $this->_finished;
    }



    /****************************************************/
    /************    VALIDATION FUNCTIONS    ************/
    /****************************************************/
    
    
    /**
     * This function validates the wizard step and perform actions related to the step if the validation was ok
     *
     * @return  void
     *
     * @throws Exception  If there is a validation error
     *
     */
    protected function _validate_wizard()
    {

        $function = '_validate_step_' . $this->_step;
        
        //If the validation function is defined then we validate
        if (method_exists('Welcome_wizard', $function))
        {
            call_user_func(array(self, $function));
        }
    }


    /**
     * This function validates the step 1
     *
     * @throws Exception  If there is validation error
     *
     * @return  void
     */
    protected function _validate_step_1()
    {
        try
        {
            $prev_interfaces = self::get_interfaces(FALSE);
        }
        catch (Exception $e)
        {
            $prev_interfaces = array();
        }
        
        $interfaces = is_array($_SESSION['_wizard_interfaces']) ? $_SESSION['_wizard_interfaces'] : array();
        
        foreach ($interfaces as &$nic)
        {
            if ($nic['role'] == 'admin')
            {
                continue;
            }
            elseif($nic['role'] == 'log_management')
            {
                ossim_valid($nic['ip'],         OSS_IP_ADDR_0,      'illegal:' . _("NIC IP Address"));
                ossim_valid($nic['netmask'],    OSS_IP_ADDR_0,      'illegal:' . _("NIC Netmask"));

                if (ossim_error())
                {
                    $error = ossim_get_error();

                    ossim_clean_error();

                    Av_exception::throw_error(Av_exception::USER_ERROR, $error);
                }
            }
            else
            {
                $nic['ip']      = '';
                $nic['netmask'] = '';
            }
            
        }

        //Flag to know if we have to delete the default networks or if they have already been deleted
        $delete_networks = $this->get_step_data('delete_default_networks');
        
         //Setting the interfaces
        self::set_role_interfaces($prev_interfaces, $interfaces);
        
        //Adding the networks related to the interfaces
        self::modify_default_networks($interfaces, $delete_networks);
        
        //We only delete the default networks once
        $this->set_step_data('delete_default_networks', FALSE);
    }


    /**
     * This function validates the step 2
     *
     * @throws Exception  If there is validation error
     *
     * @return  void
     */
    protected function _validate_step_2()
    {

        //Getting the current nmap scan status
        $step = intval($this->get_step_data('scan_step'));
        
        //If the nmap is doing a ping or a scan we have to wait or cancel before moving forward
        if ($step == 1 || $step == 2)
        {
            $exp_msg = _('There is a NMAP scan running, you have to wait until it finish');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else
        {
            //If the validation is ok we clean the data related to the nmap scan
            $this->clean_step_data();
        }
    }


    /**
     * This function validates the step 3
     *
     * @throws Exception  If there is validation error
     *
     * @return  void
     */
    protected function _validate_step_3()
    {
        //Getting the current deploy status
        $step = intval($this->get_step_data('deploy_step'));
        
        //If we are deploying, we have to wait
        if ($step == 2)
        {
            $exp_msg = _('There is a Deployment running, you have to wait until it finish');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else
        {
            //If the validation is ok we clean the data related to the deployment
            $this->clean_step_data();
        }
    }



    /*********************************************************************************/
    /*********************************************************************************/
    /*********************           Static functions           **********************/
    /*********************************************************************************/
    /*********************************************************************************/


    /**
     * This function removes the wizard data and set the configuration variables related to wizard
     *
     * @param  integer  $status  [Optional] Wizard finish status: 
     *                               0 --> Wizard is over. We cannot come back again
     *                               1 --> Wizard is new. We haven't started it yet
     *                               2 --> Wizard is skipped. We can come back through the orange bar
     *
     * @return  void
     */
    public static function clean_wizard($status = 0)
    {
        $status = intval($status);
        
        $conf   = new Config();
        //Saving the status of the wizard in the table config
        $conf->update('start_welcome_wizard', $status);
        
        //Cleaning the wizard from session
        unset($_SESSION['_welcome_wizard_object']);
        
        //If status is 0, this means the wizard is totally over and we cannont come back to it ever
        if ($status == 0)
        {   
            //Removing the file where the object is stored
            @unlink(self::WIZARD_SAVE_FILE);
            unset($_SESSION['_welcome_wizard_bar']);
        }
        //If status is 2, this means we can come back to the wizard because it is not finished yet.
        elseif($status == 2)
        {
            //Updating the date of the last time we went into the wizard
            $conf->update('welcome_wizard_date', gmdate('U'));
            
            //Activating flag to show the wizard status bar.
            $_SESSION['_welcome_wizard_bar'] = TRUE;
        }
    }


    /**
     * This function get the wizard variables stored in the config
     *
     * @return  array
     *
     */
    public static function get_wizard_config_vars()
    {
        $info = array();
        $conf = new Config();
        
        //Status of the wizard
        $info['op']   = $conf->get_conf('start_welcome_wizard');
        //Last time we went into the wizard
        $info['date'] = $conf->get_conf('welcome_wizard_date');
        
        return $info;
    }


    /**
     * This function checks if we have to start the wizard
     *
     * @return  boolean
     *
     */
    public static function run_welcome_wizard()
    {
        //Getting wizard config vars
        $info   = self::get_wizard_config_vars();
        //Checking if I am admin
        $admin  = Session::am_i_admin();
        
        //Checking wizard status
        $status = $info['op'];
        
        //Wizard has to be started if and only if the user is admin and the wizard status is 1
        return ($admin && $status == 1) ? TRUE : FALSE;
    }


    /**
     * This function checks if the wizard status bar has to be shown
     *
     * @return  boolean
     */
    public static function show_wizard_status_bar()
    {
        //Getting wizard config vars
        $info   = self::get_wizard_config_vars();
        
        $status = $info['op'];
        $date   = $info['date'];
        
        //If the wizard status is not 2, we don't have to show it
        if ($status != 2)
        {
            return FALSE;
        }
        //If it is 2 then...
        else
        {
            //Checking when was the last time we went into the wizard
            $diff = (gmdate('U') - $date) / 86400;
            
            //If we din'd go into the wizard for less than 7 days, we have to show the bar
            if ($diff <= 7)
            {
                return TRUE;
            }
            //If we din'd go into the wizard for more than 7 days, we have to clean the wizard
            else
            {
                self::clean_wizard();

                return FALSE;
            }
        }
    }


    /**
     * This function get the wizard object.
     *
     * @return  mixed  Object is everything is OK or false is something went wrong.
     *
     * @extra  IT IS VERY IMPORTANT TO RETURN FALSE IN CASE WE CANNOT GET THE OBJECT because we compare using ===
     */
    public static function get_instance()
    {   
        //First we try to retrieve the object from session
        $object = unserialize($_SESSION['_welcome_wizard_object']);
        
        //If it is not an object instance of Welcome_Wizard then we try to retrieve it from file
        if (! $object instanceof Welcome_wizard)
        {
            //If the file does not exist, then return FALSE
            if (!file_exists(self::WIZARD_SAVE_FILE))
            {
                return FALSE;
            }
            
            //Getting the content of the file
            $file   = file_get_contents(self::WIZARD_SAVE_FILE);
            $object = unserialize($file);
            
            //If it is not an object instance of Welcome_Wizard then we return FALSE
            if (! $object instanceof Welcome_wizard)
            {
                return FALSE;
            }
            
            //If it was ok, then we store the object in session because it wasn't stored previously
            $_SESSION['_welcome_wizard_object'] = $file;
            
        }
        //Returning the object if everything was fine.
        return $object;
    }



    /**********************************************/
    /************    TIME FUNCTIONS    ************/
    /**********************************************/
    
    
    /**
     * This function gets the nmap scan results and process and format them
     *
     * @param  object  $conn          DB Connection Object
     * @param  array   $scan_results  Scan Results
     *
     * @return  array
     */
    public static function format_result_scan($conn, $scan_results)
    {
        Ossim_db::check_connection($conn);
        
        //Getting the sensor id
        $s_ctx = $scan_results['sensor']['ctx'];
        $s_ip  = $scan_results['sensor']['ip'];
        $s_id  = Av_sensor::get_id_by_ip($conn, $s_ip, $s_ctx);
        
        //Setting the hosts params
        $host_data['descr']       = '';
        $host_data['asset_value'] = 2;
        $host_data['external']    = 0;
        $host_data['sboxs'][]     = $s_id;
        $host_data['nagios']      = 0;
        $host_data['threshold_a'] = 30;
        $host_data['threshold_c'] = 30;

        //Getting the time elapsed during the scan
        $time   = $scan_results['nmap_data']['elapsed'];
        //Getting the hosts discovered with the scan
        $hosts  = $scan_results['scanned_ips'];
        
        //Formatting the time to a human readable format
        $time   = self::format_time($time);
        $net    = 0;
        $server = 0;
        
        //Insertting the discovered hosts
        foreach ($hosts as $ip => $data)
        {
            $host_data['ips'][] = $ip;
            
            $_s = 0;
            $_n = 0;
            
            $dt = is_array($data['device_types']) ? $data['device_types'] : array();
            
            //Checking the device type found
            foreach ($dt as $nmap_device => $_extra)
            {
                $ossim_device = Devices::translate_nmap_device($nmap_device);
                
                //Server type
                if ($ossim_device['type'] == 1)
                {
                    $_s = 1;
                }
                //Network device type
                elseif ($ossim_device['type'] == 4)
                {
                    $_n = 1;
                }
            }
            
            $server += $_s;
            $net    += $_n;
        }
        
        //Saving the hosts
        $data = Scan::save_hosts_in_db($conn, $scan_results, $host_data);
        
        //Returning the stadistic info to show in the wizard:
        //Time elapsed, #server devices, #network devices
        $info = array(
            'time'   => $time,
            'net'    => $net,
            'server' => $server
        );
        
        return $info;
    }
    
    
    /**
     * This function formats the time to a human readable format
     *
     * @param  integer  $t  Timestamp
     *
     * @return  string
     *
     */
    public static function format_time($t)
    {
        //Getting the days
        $d = round($t/86400);
        $t = $t%86400;
        
        //Getting the hours
        $h = round($t/3600);
        $t = $t%3600;
        
        //Getting the minutes
        $m = round($t/60);
        $t = $t%60;
        
        //Getting the seconds
        $s = round($t);
        
        $time = array();
        
        if ($d > 0)
        {
            $time[] = $d . ' ' . (($d == 1) ? _('day') : _('days'));
        }
        
        if ($h > 0)
        {
            $time[] = $h . ' ' . (($h == 1) ? _('hour') : _('hours'));
        }
        
        if ($m > 0)
        {
            $time[] = $m . ' ' . (($m == 1) ? _('minute') : _('minutes'));
        }
        
        if ($s > 0)
        {
            $time[] = $s . ' ' . (($s == 1) ? _('second') : _('seconds'));
        }
        
        $time = implode(' ', $time);
        
        return ($time == '') ? '0 ' . _('seconds') : $time;
    }


    /****************************************************/
    /************    INTERFACES FUNCTIONS    ************/
    /****************************************************/
    
    
    /**
     * This function get a list of the interfaces of the system
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return  array
     */
    public static function get_interfaces($reload_session = TRUE)
    {
        $interfaces = Av_sensor::get_interfaces('local', FALSE);
          
        if ($reload_session)
        {
            $_SESSION['_wizard_interfaces'] = $interfaces;
        }
        
        return $interfaces;
    }
    
    
    /**
     * This function set the prosmisc mode of a NIC
     *
     * @param  string   $interface  Interface
     * @param  boolean  $mode       [Optional] Flag to describe wheter the interface is promiscuos or not
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return  void
     */
    public static function set_promisc_mode($interface, $mode = TRUE)
    {
        // API CALL
        $client   = new Alienvault_client();
        $response = $client->system()->set_promisc_mode($interface, $mode);

        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {  
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
     * This function get the trafic of the NIC in the system
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return  array
     */
    public static function get_traffic_stats()
    {
        $stats  = array();
        
        //Getting the interdaces in the system
        $nics = $_SESSION['_wizard_interfaces'];
 
        // API CALL
        $client = new Alienvault_client();
        $response = $client->system()->get_traffic_stats();
        
        $response = @json_decode($response, TRUE);
        
        if (!$response || $response['status'] == 'error')
        {                      
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else
        {
            //Going throught the trafic of each NIC
            foreach ($response['data']['stats'] as $iface => $idata)
            {
                if ($nics[$iface]['role'] == 'monitoring')
                {
                    //Getting the amount of traffic
                    if ($_SESSION['_stats'][$iface])
                    {
                        $stats[$iface]              = ($idata['RX'] - $_SESSION['_stats'][$iface] > 0) ? TRUE : FALSE;
                        $_SESSION['_stats'][$iface] = $idata['RX'];
                    }
                    else
                    {
                        $stats[$iface]              = FALSE;
                        $_SESSION['_stats'][$iface] = $idata['RX'];
                    }
                }
            }
        }
        
        return $stats;
    }


    /**
     * This function sets all the interfaces by reconfig
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return  array
     */
    public static function set_role_interfaces($prev_interfaces, $current_interfaces)
    {          
        $api_nic = array();
        
        //We ae going to set only the interfaces that are in promiscuos mode
        foreach ($current_interfaces as $iface => $nic)
        {
            $prev_nic = $prev_interfaces[$iface];
            
            //Admin interface cannot be modified and we cannot add another admin interface.
            $admin_cond = $prev_nic['role'] == 'admin' || $nic['role'] == 'admin';
            //Checking that both interface data are arrays
            $array_cond = !is_array($prev_nic) || !is_array($nic);
            
            if ($array_cond || $admin_cond)
            {
                continue;
            }
            
            //If the NIC configs are the same, ignore it
            if (count(array_diff($prev_nic, $nic)) == 0)
            {
                continue;
            }

            //Formating JSON to api call
            if ($nic['role'] == 'log_management')
            {                
                $api_nic[$iface] = array(
                    'role'      => $nic['role'],
                    'ipaddress' => $nic['ip'],
                    'netmask'   => $nic['netmask']
                );
            }
            else
            {
                $api_nic[$iface] = array(
                    'role'      => $nic['role']
                );
            }
            
        }
        
        if (count($api_nic) > 0)
        {
            $api_nic = json_encode($api_nic);
                       
            // API CALL
            $client   = new Alienvault_client();
            $response = $client->system()->set_role_interfaces($api_nic);
            
            $response = @json_decode($response, TRUE);
            
            if (!$response || $response['status'] == 'error')
            {
                $exp_msg = $client->get_error_message($response);
    
                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }

        unset($_SESSION['_wizard_interfaces']);
    }
    
    
    public static function modify_default_networks($interfaces, $delete_networks = TRUE)
    {
        $db   = new ossim_db();
        $conn = $db->connect();


        if ($delete_networks !== FALSE)
        {
            self::delete_default_networks($conn);
        }

        //Getting sensor info
        $s_ip   = Util::get_default_admin_ip();
        $sensor = Av_sensor::get_id_by_ip($conn, $s_ip);

        // Now create networks for each interface
        foreach ($interfaces as $nic)
        {
            
            if ($nic['ip'] == '' || $nic['netmask'] == '')
            {
                continue;
            }
            
            $subnet = Cidr::netmask2cidr($nic['netmask']);
            $net    = Cidr::cidr2network($nic['ip'], $subnet);
            
            $cidr   = $net . '/' . $subnet;
            $name   = 'Local_' . preg_replace('/[\.\/]/', '_', $cidr);
            
            try
            {
                $uuid   = Util::uuid();
                $net    = new Asset_net($uuid);

                $net->set_ips($cidr);
                $net->set_name($name);
                $net->set_sensors(array($sensor));

                //Insert the New Net
                $net->save_in_db($conn);
            
            }
            catch (Exception $e)
            {

            }

        }

        $db->close();
    }
    
    
    public static function delete_default_networks($conn)
    {
        $params = array('where' => 'name in ("Pvt_192","Pvt_172","Pvt_010")');
        
        try
        {
            list($default_nets, $_total) = Asset_net::get_list($conn, '', $params);
            
            foreach ($default_nets as $net_id => $_net)
            {
        	    Asset_net::delete_from_db($conn, $net_id, FALSE);
            }
            
            Asset::report_changes($conn, 'net');
            
        }
        catch (Exception $e){}
    }



    /************************************************/
    /************    DEPLOY FUNCTIONS    ************/
    /************************************************/


    /**
     * This function launchs an ossec deployment
     *
     * @param  string  $name      Agent Name
     * @param  string  $ip        Agent IP
     * @param  string  $username  Agent User Name
     * @param  string  $domain    Agent Domain
     * @param  string  $pass      Agent Password
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return  array
     */
    public static function launch_ossec_deploy($name, $ip, $username, $domain, $pass)
    {
        // API CALL
        $client = new Alienvault_client();

        $response = $client->sensor()->launch_ossec_deploy($name, $ip, $username, $domain, $pass);
        $response = @json_decode($response, TRUE);
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
    * This function check the the status of a task
    *
    * @param  integer  $job_id  [Optional] Job ID
    *
    * @throws Exception  If there is a connection or API error
    *
    * @return  array
    */
    public static function current_jobs($job_id = '')
    {
        // API CALL
        $client = new Alienvault_client();

        $response = $client->jobs()->get_job_status($job_id);
        $response = @json_decode($response, TRUE);
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }
}

/* End of file welcome_wizard.inc */
/* Location: ../include/classes/welcome_wizard.inc */