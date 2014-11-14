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

$m_perms  = array ("dashboard-menu", "analysis-menu", "analysis-menu");
$sm_perms = array ("ControlPanelExecutive", "IncidentsIncidents", "IncidentsReport");

if (!Session::menu_perms($m_perms, $sm_perms))
{
    Session::unallowed_section(false);
}

//Setting DB connection			
$db   = new ossim_db();
$conn = $db->connect();

$url  = GET("url");

ossim_valid($url, 	OSS_URL_ADDRESS, 	'illegal:' . _("Url"));

if (ossim_error())
{
    die(ossim_error());
}

header('Content-Type: text/xml');


$proxy = Util::get_proxy_params($conn);


if($proxy['type'] == '')
{
    //Checking it is an xml doctype before load the document
    $header = @get_headers($url, 1);		//Getting the header of the url.

	$header['Content-Type'] = ( !empty($header['Content-Type']) ) ? $header['Content-Type'] : $header['content-type'];

	if (is_array($header['Content-Type']))
	{
		$header['Content-Type'] = array_shift($header['Content-Type']);
	}
	
	//Loading the document if it is valid xml
	if(@preg_match('/xml/', $header['Content-Type']) != 0)	 
	{
    	readfile($url);
	}
	
}
else
{
    //Checking it is an xml doctype before load the document
    $p_opts['only_header']  = true;
	$header_aux             = Util::geturlproxy($url, $proxy, $p_opts);

	@preg_match("/Content-Type:(.*)/i", $header_aux, $found);
	$header['Content-Type'] =  $found[1];
		
	//Loading the document if it is valid xml
	if(@preg_match('/xml/', $header['Content-Type']) != 0)	 
	{
    	$file  = Util::geturlproxy($url, $proxy);
    	echo $file;
	}
	
}

$db->close();
