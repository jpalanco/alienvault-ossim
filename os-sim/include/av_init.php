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

//Create session
session_start();

//AlienVault Constants
require_once 'av_config.php';

//Setting Class Path
set_include_path(get_include_path() . PATH_SEPARATOR . AV_CLASS_PATH);

//Generic handerls (exceptions, classes, ..)
require_once 'av_handlers.php';

//DB management
require_once 'ossim_db.inc';

//Utilities for SQL queries
require_once 'ossim_sql.inc';


//Get global configuration
$conf = new Ossim_conf();
$GLOBALS['CONF'] = $conf;


//PHP IDS
require_once 'IDS/Init.php';

//Control Access List
define("ACL_DEFAULT_OSSIM_ADMIN", "admin");


//Regional settings
require_once 'classes/locale.inc';

//Set language
ossim_set_lang();

//Sessions (users, activity, permissions, etc)
require_once 'classes/session.inc';


//Security functions
require_once 'classes/Security.inc';


//Check IDS Security
ids();


//Check session status

//No check in these cases (Scheduled reports and migration)
if (!preg_match('/AV Report Scheduler/', $_SERVER['HTTP_USER_AGENT']) && !(preg_match('/migration/', $_SERVER['REQUEST_URI'])))
{
    Session::is_expired();
}


if (Session::get_session_user() != '')
{
    //Set menu options
    
    $m_opt  = REQUEST('m_opt');
    $sm_opt = REQUEST('sm_opt');
    $h_opt  = REQUEST('h_opt');
    $l_opt  = REQUEST('l_opt');
    
    ossim_valid($m_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,  'illegal:'. _('Menu option'));
    ossim_valid($sm_opt, OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,  'illegal:'. _('Submenu option'));
    ossim_valid($h_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,  'illegal:'. _('Hmenu option'));
    ossim_valid($l_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,  'illegal:'. _('Lmenu option'));
    
    
    //Chenck menu options
    if (ossim_error())
    {
        header('Location: '.AV_MAIN_PATH.'/session/login.php?action=logout'); 
    }
    
    
    $av_menu = @unserialize($_SESSION['av_menu']);

    //Check menu object
    if (!is_object($av_menu) || empty($av_menu))
    {
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $av_menu = new Menu($conn);
        
        $db->close();
    }
        
    //Set menu and hmenu options
    if (!empty($m_opt) && !empty($sm_opt))
    {
         $av_menu->set_menu_option($m_opt, $sm_opt);
         
         if(!empty($h_opt))
         {
            $av_menu->set_hmenu_option($h_opt);
         }
    }

    //Set local menu option
    if (!empty($l_opt))
    {
        $av_menu->set_lmenu_option($l_opt);
    }
    
    $_SESSION['av_menu'] = serialize($av_menu);    

    /*
    echo "<pre>";
        print_r($av_menu);
    echo "</pre>";
    */    
}

/* End of file av_init.php */
/* Location: ../include/av_init.php */
