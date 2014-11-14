<?php
/**
* av_error.inc
*
* File av_error.inc is used to:
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
* Av_error Class
*
* This class is used to create or display custom error messages
*
* <pre>
*  Usage:
*    $error = new Av_error();
*    $error->display();
*    
*    $error->set_message('NMAP_PATH');
*    echo $error->get_formatted_message();
*
*    $error->set_message('NOT_NUMERIC', array(_('Asset value')));
*    $error->display('width: 80%; text-align:center;');
*
*    $error->set_message(_('This is a custom message'));
*    $error->display();
* </pre>
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/
class Av_error extends Error
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
        /* Specific errors */

        /* PHP-adodb */
        $this->default_errors['DEFAULT'] = array(
            'short_descr' => _('Sorry, operation was not completed due to an unknown error'),
            'long_descr'  => '%1%'
        );

        $this->default_errors['ADODB_PATH'] = array(
            'short_descr' => _('Unable to load ADODB library'),
            'long_descr'  => _('Make sure that adodb_path is pointing to the right path')
        );

        /* PHP-domxml extension */
        $this->default_errors['PHP_DOMXML'] = array(
            'short_descr' => _('PHP-domxml not loaded'),
            'long_descr'  => _('Make sure that php-domxml is installed and loaded')
        );

        /* NMAP not found */
        $this->default_errors['NMAP_PATH'] = array(
            'short_descr' => _('NMAP not found'),
            'long_descr'  => _('Make sure that nmap is installed and nmap_path is pointing to the right path')
        );

        /* fpdf */
        $this->default_errors['FPDF_PATH'] = array(
            'short_descr' => _('Unable to load FPDF library'),
            'long_descr'  => _('Make sure that fpdf_path is pointing to the right path')
        );

        /* jpgraph */
        $this->default_errors['JPGRAPH_PATH'] = array(
            'short_descr' => _('Unable to load Jpgraph library'),
            'long_descr'  => _('Make sure that jpgraph_path is pointing to the right path')
        );

        $this->set_message($message, $extra_info);

        $this->set_action($action);

        $this->severity = self::ERROR;
    }
}


/* End of file av_error.inc */
/* Location: ../include/av_error.inc */