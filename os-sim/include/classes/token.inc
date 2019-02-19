<?php
/**
* token.inc
*
* File token.inc is used to:
*   - Manage token form to prevent attacks and mistakes
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


/**
* Token Class
*
* Class for managing token forms
*
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Token
{
    /**#@+
    * Attributes
    */
    
    /**
    * @var integer  Expiration time
    * @access private 
    */
    private static $expiration_time = 600;
    
      
    /**
    * This function generates a token ID
    *
    * @param string $form_id  Token ID
    *
    * @return string
    */  
    public static function generate($form_id)
    {        
        //Generate random token
        $token = sha1($form_id.microtime());
                
        // Generate token creation date
        $token_time = time();
        
        $_SESSION['csrf'][$form_id][$token] = $token_time;

        return $token;
    }
        
    
    /**
    * This function checks if the token is valid (It matches with token stored in PHP Session and expiration time was not exceeded)
    *
    * @param string  $form_id          Form ID
    * @param string  $token            Token ID
    * @param integer $expiration_time  [Optional] Expiration time
    *
    * @return boolean
    */
    public static function verify($form_id, $token, $expiration_time = '')
    {
        $exp_time = ($expiration_time == '') ? self::$expiration_time : $expiration_time;
        
        //Checks if token is correct
        if(empty($_SESSION['csrf'][$form_id][$token])) 
        {
            return FALSE;
        }

        if($exp_time > 0)
        {
            $token_age = time() - $_SESSION['csrf'][$form_id][$token];

            if($token_age >= $exp_time)
            {
                unset($_SESSION['csrf'][$form_id][$token]);
                
                return FALSE;
            }
        }
        
        unset($_SESSION['csrf'][$form_id][$token]);

        return TRUE;
    }
    
    
    /**
    * This function shows or returns the error related to token 
    *
    * @param string  $msg      [Optional] Error message
    * @param string  $style    [Optional] CSS styles
    * @param boolean $display  [Optional] Display on screen or return the error
    *
    * @return void|string
    */
    public static function show_error($msg = '', $style = '', $display = TRUE)
    {
        $msg   = self::create_error_message($msg);

        $style = (!empty($style)) ? $style : "width: 80%; margin: 20px auto; text-align: left;";

        $config_nt = array(
            'content' => $msg,
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
           ),
            'style'   => $style
       ); 

        $nt = new Notification('c_nt_oss_error', $config_nt);

        if ($display == TRUE)
        {
            $nt->show($display);
        }
        else
        {
            return $nt->show($display);
        }
    }
    
    /**
    * This function creates an error message 
    *
    * @param string  $msg   [Optional] Error message
    *
    * @return string
    */
    public static function create_error_message($msg = '')
    {
        if (empty($msg))
        {
            $msg  = _('Error!').'<br/>';
            $msg .= _('A Cross-Site Request Forgery attempt has been detected or the form token has expired');
        }

        return $msg;
    }
}

/* End of file token.inc */
/* Location: ../include/classes/token.inc */
