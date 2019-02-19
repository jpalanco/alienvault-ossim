<?php

/**
 * otx.inc
 *
 * File otx.inc is used to:
 *   - To manage otx operations.
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_config.php';

/**
 * Otx Class
 *
 * Class for managing OTX configurations
 *
 * @autor      AlienVault INC
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Otx
{
    const OTX_URL		= 'https://otx.alienvault.com/';
    const OTX_URL_NEW_LOGIN	= 'https://otx.alienvault.com/accounts/signup/?next=/accounts/%s/&utm_medium=inproduct&utm_source=%s';
    const OTX_URL_EXISTING_LOGIN= 'https://otx.alienvault.com/accounts/login/?next=/accounts/%s/';
    const OTX_URL_UPGRADE_LOGIN	= 'https://otx.alienvault.com/accounts/usm/?exists=true';
        
    /**
    * OTX Token
    *
    * @var    string
    * @access protected
    */
    protected $token;
    
    /**
    * OTX Username
    *
    * @var    string
    * @access protected
    */
    protected $username;
    
    /**
    * OTX User ID
    *
    * @var    string
    * @access protected
    */
    protected $user_id;
    
    /**
    * OTX Contribute flag
    *
    * @var    boolean
    * @access protected
    */
    protected $is_contributing;
    
    /**
    * OTX Last Contribution Date
    *
    * @var    string
    * @access protected
    */
    protected $latest_contribution;
    
    
    /**
    * OTX Last Update Date
    *
    * @var    string
    * @access protected
    */
    protected $latest_update;
    
    
    /**
    * OTX Key Version
    *
    * @var    string
    * @access protected
    */
    protected $key_version;
    
    
    /**
    * OTX Last Contribution Date
    *
    * @var    string
    * @access protected
    */
    protected $api;
    

    /**
    * Function __construct
    *
    * This function sets up the class
    *
    * @access public
    */
    public function __construct()
    {
        $this->set_token('');
        $this->set_username('');
        $this->set_contribution(FALSE);
        $this->set_latest_update('');
        $this->set_latest_contribution('');
        $this->set_key_version(0);
        
        //API Connection
        $this->api = new Alienvault_client();
    }
    
    
    /**
    * Function get_anchor
    *
    * This function gets the anchor
    *
    * @access public
    *
    * @return string
    */
    public static function get_anchor($prefix = '?')
    {
        GLOBAL $conf;
        $version = $conf->get_conf("ossim_schema_version");

        $av_menu = @unserialize($_SESSION['av_menu']);
        $medium  = '';
        if (is_object($av_menu))
        {
            $medium = urlencode($av_menu->get_m_option().'/'.$av_menu->get_sm_option().'/'.$av_menu->get_h_option());
        }

        $url     = $prefix . 'utm_source=' . (Session::is_pro() ? 'USM' : 'OSSIM');
        $url    .= '&utm_medium=' . urlencode($medium);
        $url    .= '&utm_campaign=' . urlencode($version);

        return $url;
    }
    
    
    /**
    * Function get_token
    *
    * This function gets the token
    *
    * @access public
    *
    * @return string
    */
    public function get_token()
    {
        return $this->token;
    }
    
    
    /**
    * Function get_username
    *
    * This function gets the username
    *
    * @access public
    *
    * @return string
    */
    public function get_username()
    {
        return $this->username;
    }
    
    
    /**
    * Function get_user_id
    *
    * This function gets the user id
    *
    * @access public
    *
    * @return string
    */
    public function get_user_id()
    {
        return $this->user_id;
    }
    
    
    /**
    * Function get_key_version
    *
    * This function gets the key version
    *
    * @access public
    *
    * @return integer
    */
    public function get_key_version()
    {
        return intval($this->key_version);
    }
    
    
    /**
    * Function is_contributing
    *
    * This function check whether the OTX contribution is enabled or not.
    *
    * @access public
    *
    * @return boolean
    */
    public function is_contributing()
    {
        return $this->is_contributing;
    }
    
    
    /**
    * Function get_latest_contribution
    *
    * This function gets the last contribution to OTX.
    *
    * @access public
    *
    * @return date
    */
    public function get_latest_contribution()
    {
        return Util::get_date_from_utc($this->latest_contribution, array('empty_msg' => _('Never')));
    }
    
    
    /**
    * Function get_latest_update
    *
    * This function gets the last contribution to OTX.
    *
    * @access public
    *
    * @return date
    */
    public function get_latest_update()
    {
        return Util::get_date_from_utc($this->latest_update, array('empty_msg' => _('Never')));
    }
    
    
    /**
    * Function set_token
    *
    * This function sets the OTX token.
    *
    * @access public
    *
    * @return void
    */
    public function set_token($value)
    {
        $token = ($value) ? $value : '';
        $this->token = $token;
    }
    
    
    /**
    * Function set_username
    *
    * This function sets the OTX username.
    *
    * @access public
    *
    * @return void
    */
    public function set_username($value)
    {
        $username = ($value) ? $value : '';
        $this->username = $username;
    }
    
    
    /**
    * Function set_user_id
    *
    * This function sets the OTX user id.
    *
    * @access public
    *
    * @return void
    */
    public function set_user_id($value)
    {
        $user_id = ($value) ? $value : '';
        $this->user_id = $user_id;
    }
    
    
    /**
    * Function set_contribution
    *
    * This function sets wheter an account is contributing or not.
    *
    * @access public
    *
    * @return void
    */
    public function set_contribution($value)
    {
        $c1 = $value === TRUE;
        $c2 = strcasecmp($value, "true") === 0;
        $c3 = strcasecmp($value, "yes") === 0;
        
        $this->is_contributing = ($c1 || $c2 || $c3) ? TRUE : FALSE;
    }
    
    
    /**
    * Function set_key_version
    *
    * This function sets the key version
    *
    * @access public
    *
    * @return void
    */
    public function set_key_version($value)
    {
        $this->key_version = intval($value);
    }
    
    
    /**
    * Function set_latest_update
    *
    * This function sets the last update date to OTX.
    *
    * @access public
    *
    * @return void
    */
    public function set_latest_update($value)
    {
        $date = ($value) ? $value : '';
        $this->latest_update = $date;
    }
    
    
    /**
    * Function set_latest_contribution
    *
    * This function sets the last contribution date to OTX.
    *
    * @access public
    *
    * @return void
    */
    public function set_latest_contribution($value)
    {
        $date = ($value) ? $value : '';
        $this->latest_contribution = $date;
    }
    
    
    /**
    * Function load
    *
    * This function load the OTX config
    *
    * @access public
    */
    public function load()
    {
        $response = $this->api->system()->get_otx_info();
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('Unable to retrieve your OTX details');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        $this->set_token($data['token']);
        $this->set_username($data['username']);
        $this->set_user_id($data['user_id']);
        $this->set_contribution($data['contributing']);
        $this->set_key_version($data['key_version']);
        $this->set_latest_update($data['latest_update']);
        $this->set_latest_contribution($data['latest_contribution']);  
    }
    
    
    /**
    * Function register_token
    *
    * This function register an OTX token
    *
    * @access public
    */
    public function register_token($token)
    {
        $response = $this->api->system()->activate_otx($token);
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            switch ($data)
            {
                case 'ERROR_NOT_REGISTERED_TOKEN':
                    $error_msg = _('The OTX key you have entered is not valid. Please try again.');
                    break;
                    
                case 'ERROR_BAD_REQUEST':
                    $error_msg = _('An error occurred when processing the request: ') . $data . '.' . _('Please try again.');
                    break;
                
                //case 'ERROR_CONNECTION':
                default:
                    $error_msg = _('Limited or no connectivity. Please check your network settings and try again.');
            }

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        $this->set_token($data['token']);
        $this->set_username($data['username']);
        $this->set_user_id($data['user_id']);
        $this->set_contribution(TRUE);
        $this->set_key_version($data['key_version']);
        $this->set_latest_update($data['latest_update']);
        $this->set_latest_contribution($data['latest_contribution']);
    }
    
    
    /**
    * Function remove_account
    *
    * This function remove the OTX account config
    *
    * @access public
    */
    public function remove_account()
    {
        $response = $this->api->system()->remove_otx();
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('Unable to disconnect OTX account');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        $this->set_token('');
        $this->set_username('');
        $this->set_user_id('');
        $this->set_contribution(FALSE);
        $this->set_key_version(0);
        $this->set_latest_update('');
        $this->set_latest_contribution('');     
    }
    
    
    /**
    * Function enable_account
    *
    * This function enables OTX config var to start contributing in OTX.
    *
    * @access public
    */
    public function enable_contribution()
    {
        $response = $this->api->system()->enable_contribution();
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg  = _('Unable to contribute to OTX');
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
         
        $this->set_contribution(TRUE);      
    }
    
    
    /**
    * Function disable_account
    *
    * This function disables OTX config var to start contributing in OTX.
    *
    * @access public
    */
    public function disable_contribution()
    {
        $response = $this->api->system()->disable_contribution();
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('Unable to cancel OTX contribution');
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
       
        $this->set_contribution(FALSE); 
    }
    
    
    /***********************************************************************************************/
    /************************                 PULSE METHODS                 ************************/
    /***********************************************************************************************/
    
    
    /**
    * Function get_pulse_list
    *
    * This function gets a list of pulses.
    *
    * @param  $filters   array    Filters
    *
    * @return array 
    *
    * @access public
    */
    public function get_pulse_list($filters)
    {
        $response = $this->api->system()->get_pulse_list($filters);
        
        list($success, $data) = $this->api->check_response($response);
        
        if (!$success)
        {
            $error_msg = _('There was an error retrieving the pulse list');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        $total      = intval($data['total']);
        $pulse_list = array();
        
        if ($total > 0 && is_array($data['pulses']))
        {
            foreach ($data['pulses'] as $p)
            {
                $pulse = self::format_pulse($p, TRUE);
                $pulse_list[$pulse['id']] = $pulse;
            }
        }
        
        return array($total, $pulse_list);
    }
    
    
    /**
    * Function get_pulse_detail
    *
    * This function gets the pulse detail.
    *
    * @param  $pulse     string   Pulse ID
    *
    * @return array
    *
    * @access public
    */
    public function get_pulse_detail($pulse, $hide_ioc = FALSE)
    {
        $params   = array('hide_ioc' => $hide_ioc);
        $response = $this->api->system()->get_pulse_detail($pulse, $params);
        
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('There was an error retrieving the pulse detail');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }

        return self::format_pulse($data, FALSE);   
    }
    
    
    /**
    * Function get_pulse_stats
    *
    * This function gets the pulse statistics.
    *
    * @return array
    *
    * @access public
    */
    public function get_pulse_stats()
    {
        $response = $this->api->system()->get_pulse_stats();
        
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('There was an error retrieving the Pulse information');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        $date  = Util::get_date_from_utc($data['last_updated'], array('empty_msg' => '-'));
        $stats = array(
            'pulses'       => intval($data['pulses']),
            'iocs'         => intval($data['iocs']),
            'last_updated' => $date,
            'alarms'       => intval($data['alarms']),
            'events'       => intval($data['events']),
        );
        
        return $stats;   
    }
    
    
    /**
    * Function get_top_pulses
    *
    * This function gets the top pulses from the system.
    *
    * @param  $params  array  Trend Params (Top Pulses and Period of Time)
    *
    * @return array
    *
    * @access public
    */
    public function get_top_pulses($params)
    {
        $data = array();
    
        $response = $this->api->system()->get_top_pulses($params);
        
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('There was an error retrieving the Top Pulses');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        return is_array($data) ? $data : array();   
    }
    
    
    /**
    * Function get_pulse_trend_by_time
    *
    * This function gets the events from all OTX pulses by date.
    *
    * @param  $params  array  Trend Params (Period of time)
    *
    * @return array
    *
    * @access public
    */
    public function get_events_from_all_pulses($params)
    {
        $data = array();
    
        $response = $this->api->system()->get_events_from_all_pulses($params);
        
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('There was an error retrieving the Events from All OTX Pulses');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        return is_array($data) ? $data : array();   
    }
    
    
    /**
    * Function get_pulse_top_by_time
    *
    * This function gets the events from top OTX pulses by pulse and date.
    *
    * @param  $params  array  Trend Params (Period of time)
    *
    * @return array
    *
    * @access public
    */
    public function get_events_from_top_pulses($params)
    {
        $data = array();
          
        $response = $this->api->system()->get_events_from_top_pulses($params);
        
        list($success, $data) = $this->api->check_response($response);

        if (!$success)
        {
            $error_msg = _('There was an error retrieving the Events from Top OTX Pulses');
            
            $error_msg .= empty($data) ? '.' : ': ' . $data;

            Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
        }
        
        return is_array($data) ? $data : array();   
    }
    
       
    /***********************************************************************************************/
    /************************                STATIC METHODS                 ************************/
    /***********************************************************************************************/
    
    
    /**
    * Function format_pulse
    *
    * This function format a pulse.
    *
    * @param  $pulse     array    Pulse Data
    * @param  $summary   boolean  Wheter or not the pulse is in summary format
    *
    * return array
    *
    * @access public
    */
    public static function format_pulse($pulse, $summary = TRUE)
    {
        $f_pulse = array(
            'id'          => $pulse['id'],
            'name'        => strval($pulse['name']),
            'description' => strval($pulse['description']),
            'author_name' => strval($pulse['author_name']),
            'created'     => Util::get_date_from_utc($pulse['created'], array('empty_msg' => '-')),
            'modified'    => Util::get_date_from_utc($pulse['modified'], array('empty_msg' => '-')),
            'tags'        => is_array($pulse['tags']) ? $pulse['tags'] : array(),
        ); 
        
        if (!$summary)
        {
            $f_pulse['indicators'] = $pulse['indicators'];
        }
        
        return $f_pulse;    
    }

    public static function getLoginURL($isnew = false) {
    	$text = Session::is_pro() ? "usm" : "ossim";
    	return sprintf($isnew ? self::OTX_URL_NEW_LOGIN : self::OTX_URL_EXISTING_LOGIN, $text, strtoupper($text));
    }
}
