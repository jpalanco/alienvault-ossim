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

ini_set("display_errors", 'off');

require_once 'av_init.php';
require_once 'functions.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$dbtype  = $GLOBALS["CONF"]->get_conf("ossim_type");
$dbhost  = $GLOBALS["CONF"]->get_conf("ossim_host");
$dbname  = $GLOBALS["CONF"]->get_conf("ossim_base");
$dbuname = $GLOBALS["CONF"]->get_conf("ossim_user");
$dbpass  = $GLOBALS["CONF"]->get_conf("ossim_pass");


$db = new ossim_db();
$dbconn = $db->connect();


$query = "SELECT settingName, settingValue FROM vuln_settings";
$result = $dbconn->GetArray($query);
if($result === false) {
   die("Unable to get configuration settings from DB - exiting");
} else {
   foreach($result as $setting) {

      if(strpos($setting['settingName'],"Array")) {
         $$setting['settingName'] = explode(",",$setting['settingValue']);
         $$setting['settingName'] = explode(",",$setting['settingValue']);
         array_walk($$setting['settingName'], 'trim_value');
      } else {
         $$setting['settingName'] = $setting['settingValue'];
      }
   }
}

function trim_value(&$value) {
    $value = trim($value);
}

// default user
$username = $_SESSION["_user"];

$uroles = array (
	"admin"          => 1,
	"uadmin"         => 1,
	"nessus"         => 1,
	"nmap"           => 1,
	"scanRequest"    => 1,
	"reports"        => 1,
	"zones"          => 1,
	"eapprove"       => 1,
	"esubmit"        => 1,
	"eview"          => 1,
	"profile"        => 1,
	"infrastructure" => 1,
	"selfservice"    => 1,
	"credscans"      => 1,
	"debug"          => 1,
	"sys_access"     => 1,
	""               => 1,
	"vpnadmin"       => 1,
	"compAudit"      => 1,
	"auditAll"       => 1,
	"isvmView"       => 1,
	"isvmAdmin"      => 1,
	"plugoverride"   => 1,
	"investigate"    => 1
);
