<?php
/**
* av_notice.inc
*
* File av_notice.inc is used to:
*   - To display notice messages
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
* Av_notice Class
*
* This class is used to create or display custom notice messages
*
* <pre>
*  Usage:
*    $notice = new Av_notice();
*    $notice->display();
*    
*    $notice->set_message('NMAP_PATH');
*    echo $notice->get_formatted_message();
*
*    $notice->set_message('NOT_NUMERIC', array(_('Asset value')));
*    $notice->display('width: 80%; text-align:center;');
*
*    $notice->set_message(_('This is a custom message'));
*    $notice->display();
* </pre>
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Av_notice extends Error
{
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $message     [Optional] Error message (Custom message or error code)
    * @param string  $extra_info  [Optional] Extra information for adding to the message
    * @param string  $action      [Optional] Default action after displaying error message
    */
    public function __construct($message = 'DEFAULT', $extra_info = NULL, $action = 'noback')
    {
        $this->default_errors['DEFAULT'] = array(
            'short_descr' => _('Notice!') ,
            'long_descr'  => '%1%'
        );

        $this->default_errors['LOGOUT'] = array(
            'short_descr' => _('Notice!') ,
            'long_descr'  => _("You don't have permission to see this page").'%1%'
        );

        $this->set_message($message, $extra_info);

        $this->set_action($action);

        $this->severity = self::NOTICE;
    }
}


/* End of file av_notice.inc */
/* Location: ../include/av_notice.inc */