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


require_once ('av_init.php');
Session::logcheck("environment-menu", "MonitorsNetflows");

require ("conf.php");
require ("nfsenutil.php");
session_start();
unset($_SESSION['nfsend']);

function OpenLogFile () {
	global $log_handle;
	global $DEBUG;

	if ( $DEBUG ) {
		$log_handle = fopen("/var/tmp/nfsen-log", "a");
		$_d = date("Y-m-d-H:i:s");
		ReportLog("\n=========================\nDetails Graph run at $_d\n");
	} else 
		$log_handle = null;

} // End of OpenLogFile

function CloseLogFile () {
	global $log_handle;

	if ( $log_handle )
		fclose($log_handle);

} // End of CloseLogFile

function ReportLog($message) {
	global $log_handle;

	if ( $log_handle )
		fwrite($log_handle, "$message\n");
} // End of ReportLog

OpenLogFile();

$lookup = urldecode($_GET['lookup']);
$opts = array();
$opts['lookup'] = $lookup;

header("Content-type: text/html; charset=ISO-8859-1");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Lookup: '<?php echo Util::htmlentities($lookup);?>'</title>
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/nfsen/lookup.css">
</head>
<body>

<?php

nfsend_query("@lookup", $opts, 1);
nfsend_disconnect();
unset($_SESSION['nfsend']);
CloseLogFile();

?>

</body>
</html>
