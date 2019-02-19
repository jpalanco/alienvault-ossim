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

require_once 'av_init.php';


//First we check we have session active
Session::useractive();


//Then we check the permissions
if (!Session::am_i_admin())
{
    echo _('You do not have permissions to see this section');
    
    exit -1;
}

//Adding wizard step folder to the path
$step_path = '/usr/share/ossim/www/wizard/steps';
set_include_path(get_include_path() . PATH_SEPARATOR . $step_path);

//Getting the wizard object. THIS VARIABLE WILL BE USED IN THE FILES THAT ARE INCLUDED DOWN THESE LINES
$wizard = Welcome_wizard::get_instance(); 

//If we cannot retrieve it, we show an error
if ($wizard === FALSE)
{
    echo _("There was an error, the Welcome_wizard object doesn't exist. Try again later");
    
    exit -1;
}
//Otherwise we load the wizard step file
else
{
    //Getting the current step file
    $file = $wizard->get_step_file();
    
    //THe CONN OBJECT WILL BE USED IN THE FILES THAT ARE INCLUDED DOWN THESE LINES
    $db   = new ossim_db(TRUE);
    $conn = $db->connect();
    
    $path = $step_path . '/' . $file;
    
    //If the wizard step file exist, then we load it
    if (file_exists($path))
    {
        include $file;
    }
    //If not, an error is displayed
    else
    {
        echo _("There was an error, the wizard step file doesn't exist. Try again later");
    }
    
    $db->close();
}
