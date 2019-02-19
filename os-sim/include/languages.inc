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

if (!Session::is_pro())
{
    $all_languages = array(
        "zh_CN" => _("Chinese simplified") ,
        "zh_TW" => _("Chinese traditional") ,
        "en_GB" => _("English") ,
        "fr_FR" => _("French") ,
        "de_DE" => _("German") ,
        "pt_PT" => _("Portuguese portugal") ,
        "es_ES" => _("Spanish")
    );
}
else
{
    $all_languages = array(
        "en_GB" => _("English") ,
        "es_ES" => _("Spanish")
    );
}

asort($all_languages);
$languages = array(
    "type" => $all_languages,
    "help" => gettext("")
);

$system_lang = trim(`/usr/bin/locale|grep LANG|cut -f 2 -d "="|head -1`);

$config_languages = $all_languages;
$config_languages[$system_lang] = gettext("Default Language"); 
?>
