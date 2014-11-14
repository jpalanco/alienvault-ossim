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


require_once 'av_config.php';
require_once 'ossim_conf.inc';

function ossim_set_lang($language = "en_GB") 
{
    $DEFAULT_LANGUAGE   = "en_GB";
    $DEFAULT_LOCALE_DIR = AV_LOCALE_DIR."/";
    
    $conf = $GLOBALS["CONF"];
    if (!$conf) 
    {
		$conf = new Ossim_conf();
    }
        
    if (!$locale_dir = $conf->get_conf("locale_dir")) 
    {  
        $locale_dir = $DEFAULT_LOCALE_DIR;
    }
    
    if (preg_match("/\/graphs\//", $_SERVER['REQUEST_URI'])) 
    {
        $language = "en_GB";
    }
    elseif (isset($_SESSION['_user_language'])) 
    {
        $language = $_SESSION['_user_language'];
    }
    elseif (!isset($_SESSION['_user_language']) && (!$language = $conf->get_conf("language"))) 
    {
        $language = $DEFAULT_LANGUAGE;
    }
    
        
    // I18N support information here
    // try out some locale options:
    // 1) For debian works @euro or .UTF8 or .UTF-8
    // 2) Mac OS X needs putenv()
    $lang = explode("_", $language);
    
    setlocale(LC_ALL, "");
    setlocale(LC_MESSAGES, "");
    
    if (setlocale(LC_ALL, $lang[0])) 
    {
        setlocale(LC_MESSAGES, $lang[0]);
    }
    elseif (setlocale(LC_ALL, $language)) 
    {
        setlocale(LC_MESSAGES, $language);
    }
    elseif (setlocale(LC_ALL, $language . ".iso88591")) 
    {
        setlocale(LC_MESSAGES, $language . ".iso88591");
    }
    elseif (setlocale(LC_ALL, $language . "@euro")) 
    {
        setlocale(LC_MESSAGES, $language . "@euro");
    }
    elseif (setlocale(LC_ALL, $language . ".UTF-8")) 
    {
        setlocale(LC_MESSAGES, $language . ".UTF-8");
    }
    elseif (setlocale(LC_ALL, $language . ".UTF8")) 
    {
        setlocale(LC_MESSAGES, $language . ".UTF8");
    }
    else 
    {
        $language = "en_GB";
        setlocale(LC_ALL, $DEFAULT_LANGUAGE);
        setlocale(LC_MESSAGES, $DEFAULT_LANGUAGE);
    }
    
    putenv("LANGUAGE=" . $language);
    putenv("LANG=" . $language);
    
    // Set the text domain as 'ossim'
    // Localization files must be called ossim.po & ossim.mo
    $domain = "ossim";
    bindtextdomain($domain, $locale_dir);
    textdomain($domain);
}

/* End of file locale.inc */
/* Location: ../include/classes/locale.inc */