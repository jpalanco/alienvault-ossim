<?php
/**
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
*/


/***************************************************************
 ******************* AlienVault Directories ********************
 ***************************************************************/

//AlienVault Main Path
define("AV_MAIN_PATH", '/ossim');

//AlienVault Root Path
define("AV_MAIN_ROOT_PATH", '/usr/share/ossim/www');

//AlienVault Class Path
define("AV_CLASS_PATH", '/usr/share/ossim/include');

//Directory for temporary files
define("AV_TMP_DIR", '/var/tmp');

//Directory for uploads 
define("AV_UPLOAD_DIR", '/usr/share/ossim/uploads');

//Directory for logs
define("AV_LOG_DIR", '/var/ossim/logs');

//Directory for cache files
define("AV_CACHE_DIR", '/var/ossim/sessions');

//Regional settings
define("AV_LOCALE_DIR", '/usr/share/locale');

//Framework settings
define('AV_CONF_FILE', '/etc/ossim/framework/ossim.conf');

//Images 
define("AV_PIXMAPS_DIR", AV_MAIN_PATH.'/pixmaps');

//Javascript files
define("AV_JS_DIR", AV_MAIN_PATH.'/js');

//CSS files
define("AV_CSS_DIR", AV_MAIN_PATH.'/styles');


/***************************************************************
 ********************* AlienVault Plugins **********************
 ***************************************************************/

define('OSSEC_MIN_PLUGIN_ID', 7001);
define('OSSEC_MAX_PLUGIN_ID', 8005);

define('SNORT_MIN_PLUGIN_ID', 1001);
define('SNORT_MAX_PLUGIN_ID', 1500);


/***************************************************************
 ********************** Debug Information **********************
 ***************************************************************/
 
// OFF      = 0   // Log nothing at all
// ERROR    = 1;  // PHP Errors
// INFO     = 2;  // Informational: informational messages
// DEBUG_1  = 3;  // Debug (Level 1): debug messages (SQL queries)
// DEBUG_2  = 4;  // Debug (Level 2): debug messages (variables, arrays, objects, ...)

//Debug Level
define("AV_DEBUG", 0);

//Debug file
define("AV_DEBUG_FILE", AV_TMP_DIR.'/ui.log');


/***************************************************************
 ********** Error severity for notification messages ***********
 ***************************************************************/
  
define("AV_ERROR",   1);
define("AV_WARNING", 2);
define("AV_INFO",    3);


/***************************************************************
 *************************** Users *****************************
 ***************************************************************/
  
define("AV_DEFAULT_ADMIN", 'admin');
  
/* End of file av_config.inc */
/* Location: ../include/av_config.php */
