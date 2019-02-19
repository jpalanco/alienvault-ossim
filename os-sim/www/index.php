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

Session::useractive('session/login.php');

$pro = Session::is_pro();

/* Remote interfaces */
$url = POST('url');

if (isset($url) && !empty($url))
{
    $url = base64_decode($url);
    
    ossim_valid($url, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _('URL'));
    
    if (ossim_error())
    {
        echo ossim_error();
        exit();         
    }
        
    $url_check = preg_replace("/\.php.*/", ".php", $url);
    $url_check = preg_replace("/^\/ossim/", AV_MAIN_ROOT_PATH, $url_check);
            
    if (!file_exists($url_check)) 
    {
    	$url_check  = htmlentities($url_check);
    	$error_msg  = _("Can't access to $url_check for security reasons");    	
    	
    	echo ossim_error($error_msg);
        exit();  
    }
                                
    $p_url = parse_url($url);
        
    if(!empty($p_url['query']))
    {               
        parse_str($p_url['query'], $qs);
                
        $m_opt  = $qs['m_opt'];
        $sm_opt = $qs['sm_opt'];
        $h_opt  = $qs['h_opt'];
        $l_opt  = $qs['l_opt'];
        
                   
        ossim_valid($m_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE,                'illegal:' . _('Menu option'));
        ossim_valid($sm_opt, OSS_LETTER, OSS_DIGIT, OSS_SCORE,                'illegal:' . _('Submenu option'));
        ossim_valid($h_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE,                'illegal:' . _('Hmenu option'));
        ossim_valid($l_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,  'illegal:' . _('Lmenu option'));
                  
        if (!ossim_error())
        {
            $_SESSION['ri']['url']    = $url;
            $_SESSION['ri']['m_opt']  = $m_opt;
            $_SESSION['ri']['sm_opt'] = $sm_opt;
            $_SESSION['ri']['h_opt']  = $h_opt;
        }
        else
        {
            if (empty($m_opt) && empty($sm_opt) && empty($h_opt))
            {
               $error_msg = _('Error! Remote interface not available. You need update to version 4.3.0 or above');   
            }
            else
            {
               $error_msg = ossim_get_error_clean();
            }           
        
            echo ossim_error($error_msg);
            exit();
        }
    } 
}

//Getting the html content
require AV_MAIN_ROOT_PATH . '/home/index.php';
