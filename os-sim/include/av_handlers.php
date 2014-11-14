<?php
/**
* av_handlers.php
*
* File av_handlers.php is used to:
*   - To manage common handlers (Exceptions, autoloaded classes, ...)
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


//Constants 
require_once 'av_config.php';

/**
* This function manages generic exceptions
*
* @param object  $e  Generic exception object
*
* @return void
*/
function av_exception_handler($e)
{
    require_once 'classes/av_exception.inc';

    Av_exception::display($e);
}


/**
* This function loads the class in memory automatically
*
* @param string  $class_name  Class to load
*
* @return void
*/
function av_auto_load($class_name)
{
    $lower_class_name = strtolower($class_name);
    
    $base_path = AV_CLASS_PATH;
    
    //Some file names begin with a capital letter
    $paths[0] = $base_path."/classes/$lower_class_name.inc";
    $paths[1] = $base_path."/classes/$class_name.inc";
    
    //There are some classes in this path
    $paths[2] = $base_path."/$lower_class_name.inc";
              
    
    foreach($paths as $p_class)
    {        
        if (file_exists($p_class)) 
        {
            require_once $p_class;
            
            break;
        }
    }    
}


//Register exception handler
set_exception_handler('av_exception_handler');

//Register autoload handler
spl_autoload_register('av_auto_load');


/* End of file av_handlers.php */
/* Location: ../include/av_handlers.php */