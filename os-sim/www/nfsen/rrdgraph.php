<?php

require_once ('av_init.php');
Session::logcheck("environment-menu", "MonitorsNetflows");


require ("conf.php");
require ("nfsenutil.php");
//session_start();
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

function Initvars() {

// register 'get-detailsgraph' command for rrdgraph.php
        if ( !array_key_exists('rrdgraph_cmds', $_SESSION) ||
                 !array_key_exists('get-detailsgraph', $_SESSION['rrdgraph_cmds']) ) {
                $_SESSION['rrdgraph_cmds']['get-detailsgraph'] = 1;
                $_SESSION['rrdgraph_getparams']['profile'] = 1;
        }


}

Initvars();
OpenLogFile();

$command = urldecode($_GET['cmd']);
ReportLog("RRD graph command is '$command'");

if ( !array_key_exists('rrdgraph_cmds', $_SESSION) || 
	 !array_key_exists($command, $_SESSION['rrdgraph_cmds']) ) {
 	ReportLog("RRD command not found");

	header("Content-type: image/png");
	 exit;
} 

$opts = array();
foreach ($_SESSION['rrdgraph_getparams'] as $getparam => $dummy ) {
	if ( array_key_exists($getparam, $_GET) ) {
		$opts[$getparam] = $_GET[$getparam];
	}
}
$arglist = explode(' ', urldecode($_GET['arg']));
$opts['.silent'] = 1;
foreach ( $arglist as $arg ) {
	$opts['arg'][] = $arg;
}

header("Content-type: image/png");
nfsend_query("@$command", $opts, 1);
nfsend_disconnect();
unset($_SESSION['nfsend']);
CloseLogFile();

?>
