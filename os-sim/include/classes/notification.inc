<?php
/**
* notification.inc
*
* File notification.inc is used to:
*   - To manage HTML notifications
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

/**
* Notification Class
*
* Class for managing HTML notifications
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Notification
{
    /**
    * Wrapper ID
    *
    * @var string
    * @access private
    */
    private $wrapper_id;


    /**
    * Notification configuration
    *
    * @var array
    * @access private
    */
    private $config;


    /**
    * Notification CSS Style
    *
    * @var style
    * @access private
    */  
    private static $wrapper_style  = 'width: 300px;
                                    font-family:Arial, Helvetica, sans-serif;
                                    font-size:12px;
                                    text-align: left;
                                    position: relative;   
                                    border: 1px solid;
                                    border-radius: 5px;
                                    -moz-border-radius: 5px;
                                    -webkit-border-radius: 5px;
                                    box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);
                                    -webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);
                                    -moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);';
    
    
    /**
    * Notification object
    *
    * @var object
    * @access private
    */  
    private static $instance = NULL;


    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $wrapper_id  Wrapper ID
    * @param array  $ config      Config
    */  
    public function __construct($wrapper_id, $config)
    {       
        if (self::$instance != NULL) 
        {
            self::$instance->set_wrapper_id($wrapper_id);
            self::$instance->set_config($config);
            
            return self::$instance;
        }
                
        $this->set_config($config);
        
        $this->set_wrapper_id($wrapper_id);
    }


    /**
    * This function returns the wrapper ID 
    *
    * @return string
    */ 
    public function get_wrapper_id() 
    {
        return $this->wrapper_id;
    }


    /**
    * This function returns notification configuration 
    *
    * @return array
    */ 
    public function get_config() 
    {
        return $this->config;
    }       


    /**
    * This function sets wrapper ID
    *
    * @param string  $wrapper_id  Wrapper ID
    *
    * @return void
    */  
    public function set_wrapper_id($wrapper_id) 
    {
        $this->wrapper_id = (!empty($wrapper_id)) ? $wrapper_id : 'wrapper_nt';
    }


    /**
    * This function sets notification configuration 
    *
    * @param array  $config  Config
    *
    * @return void
    */  
    public function set_config($config) 
    {
        $this->config = $config;
        
        if (empty($this->config['content']))
        {
            $this->config['content'] = '';
        }
        
        if (empty($this->config['style']))
        {
            $this->config['style'] = '';
        }
        
        if (empty($this->config['options']['cancel_button']))
        {
            $this->config['options']['cancel_button'] = FALSE;
        }
    }


    /**
    * This function shows or returns HTML notification 
    *
    * @param boolean  $display  [Optional] Display on screen or return the notification 
    *
    * @return void|string
    */      
    public function show($display = TRUE)
    {
        $nf_style = self::$wrapper_style;
        $img      = 'nf_error.png';
         
        switch ($this->config['options']['type'])
        {
                
            case 'nf_error':
               $nf_style .= 'color: #D8000C; background-color: #FFBABA;';
               $img       = 'nf_error.png';
            break;
            
            case 'nf_info':
               $nf_style .= 'color: #00529B; background-color: #BDE5F8;';
               $img       = 'nf_info.png';
            break;
            
            case 'nf_success':
               $nf_style .= 'color: #4F8A10; background-color: #DFF2BF;';
               $img       = 'nf_success.png';
            break;
            
            case 'nf_warning':
               $nf_style .= 'color: #9F6000; background-color: #FEEFB3;';
               $img       = 'nf_warning.png';
            break;
            
            default:
                $nf_style .= 'color: #D8000C; background-color: #FFBABA;';
                $img       = 'nf_error.png';
        } 
        
        $nf_style .= $this->config['style'];
        
        $cancel_button = "";
        $c_pad         = 'padding: 5px 5px 5px 25px;';
        
        if ($this->config['options']['cancel_button'] == TRUE)
        {
            $c_pad         = 'padding: 10px 10px 8px 18px;';
            
            $cancel_button = "<a onclick=\"$('#".$this->wrapper_id."').hide()\"><img src='/ossim/pixmaps/nf_cross.png' style='position: absolute; top: 0px; right: 0px; cursor:pointer;'/></a>";
        }
        
                    
        $html = "<div id='".$this->wrapper_id."' style='".$nf_style."'>
                    <img src='/ossim/pixmaps/".$img."' style='position: absolute; top: -11px; left: -11px'/> 
                    <div style='$c_pad'>
                        <div class='".$this->config['options']['type']."'>".$this->config['content']."</div>
                    </div>   
                    $cancel_button 
                </div>";
        
        if ($display == TRUE)
        {   
            echo $html;
        }
        else
        {
            return $html;
        }   
    }
}

/* End of file notification.inc */
/* Location: ../include/classes/notification.inc */ 
