<?php
/**
* error.inc
*
* File error.inc is used to:
*   - To display custom error messages 
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
* Error Class
*
* Abstract class to create or display messages
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/
abstract class Error
{
    /**
    * Error severity
    */
    const ERROR    = 1;

    const WARNING  = 2;

    const NOTICE   = 3;

    /**
    * Default errors in the system
    *
    * @var array
    * @access private
    */
    protected $default_errors;

    /**
    * Action after an error occurred
    *
    * @var string
    * @access private
    */
    protected $action;

    /**
    * Current error
    *
    * @var string
    * @access private
    */ 
    protected $message;

    /**
    * Current error severity
    *
    * @var string
    * @access private
    */ 
    protected $severity;


    /**
    * This function set the a new action
    *
    * @param string  $action  New action after displaying error message
    *
    * @return void
    */
    public function set_action($action)
    {
        switch ($action)
        {
            case 'noback':
                $this->action = 'die();';
            break;

            case 'noaction':
                $this->action = '';
            break;

            default:

                $style_div = 'margin:auto; width:90%; text-align:center;';

                $this->action = 'echo "<br/>
                                 <div style=\"'.$style_div.'\">
                                      <input type=\"button\" class=\"button\" onclick=\"javascript:history.back();\" value=\"'._('Back').'\"/>
                                 </div>";';
         }
    }


    /**
    * This function returns the current action
    *
    * @return string
    */
    public function get_action()
    {
        return $this->action;
    }


    /**
    * This function set the a new error message
    *
    * @param string  $message     Error message (Custom message o error code)
    * @param string  $extra_info  Extra information for adding to the message
    *
    * @return void
    */
    public function set_message($message, $extra_info)
    {
        if (empty($message))
        {
            $message = 'DEFAULT';
        }

        if (array_key_exists($message, $this->default_errors))
        {
            $short_desc = $this->default_errors[$message]['short_descr'];
            $long_desc  = $this->default_errors[$message]['long_descr'];

            if (is_array($extra_info) && !empty($extra_info))
            {
                $n = 1;
                foreach($extra_info as $info)
                {
                    $long_desc = str_replace("%" . $n . "%", $info, $long_desc);
                    $n = $n + 1;
                }
            }
            else
            {
                $long_desc = preg_replace('/%\d+%/', '', $long_desc);
            }

            $this->message = "<strong>$short_desc</strong><br/>$long_desc";
        }
        else
        {
            $this->message = $message;
        }
    }


    /**
    * This function returns the current error message
    *
    * @return string
    */
    public function get_message()
    {
        return $this->message;
    }


    /**
    * This function returns an error message formatted
    *
    * @param string  $styles  [Optional] CSS styles for message
    *
    * @return string
    */
    public function get_formatted_message($styles = 'width: 90%; text-align:left; margin:auto;')
    {
        $sev_type = array(
            self::ERROR   => 'nf_error',
            self::WARNING => 'nf_warning',
            self::NOTICE  => 'nf_info'
        );

        $nf_type = (!empty($sev_type[$this->severity])) ? $sev_type[$this->severity] : 'nf_error';

        $config_nt = array(
            'content' => $this->message,
            'options' => array (
                'type'          => $nf_type,
                'cancel_button' => FALSE
                ),
            'style'   => $styles
        );

        $nt = new Notification('nt_oss_error', $config_nt);

        return "<div id='c_nt_oss_error' style='width:95%; margin: 20px auto;'>".$nt->show(FALSE)."</div>";
    }
    
    
    /**
    * This function displays a formatted error message
    *
    * @param string  $styles  [Optional] CSS styles for message
    *
    * @return string
    */
    public function display($styles = 'width: 90%; text-align:left; margin:auto;')
    {
        echo $this->get_formatted_message($styles);

        if (!empty($this->action))
        {
            eval($this->action);
        }
    }
}


/* End of file error.inc */
/* Location: ../include/error.inc */