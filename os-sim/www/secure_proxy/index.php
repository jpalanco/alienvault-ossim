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

Session::useractive();

// Security check
if (!preg_match("/^\/?(munin|nagios3|ossim)/",$_SERVER['REDIRECT_SCRIPT_URL']))
{
    exit(0);
}

// Curl init->configure redirect
$url     = "https://127.0.0.1".$_SERVER['REQUEST_URI'];
$session = curl_init($url);

if (is_array($_POST) && !empty($_POST))
{
    $postvars = '';
    while ($element = current($_POST))
    {
        $postvars .= urlencode(key($_POST)).'='.urlencode($element).'&';
        next($_POST);
    }
    curl_setopt($session, CURLOPT_POST, TRUE);
    curl_setopt($session, CURLOPT_POSTFIELDS, $postvars);
}
curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($session, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($session, CURLOPT_HEADER, FALSE);
curl_setopt($session, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);

$content      = curl_exec($session);
$content_type = curl_getinfo($session, CURLINFO_CONTENT_TYPE);

//error_log("$url\n$content_type\n",3,"/tmp/urls");

header_remove();
header("Content-type: $content_type");
echo $content;

curl_close($session);
