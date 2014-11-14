<?php
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
		$log_handle = @fopen("/var/tmp/nfsen-log", "a");
		$_d = date("Y-m-d-H:i:s");
		ReportLog("\n=========================\nPic run at $_d\n");
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

function GetProfilePic () {

	$profileswitch = $_GET['profileswitch'];
	$file = $_GET['file'];
	
	header("Content-type: image/png");
	
	if ( preg_match("/^(.+)\/(.+)/", $profileswitch, $matches) ) {
		$_profilegroup = $matches[1];
		$_profilename  = $matches[2];
	} else {
		$fp = fopen("icons/ErrorGraph.png", 'rb');
		fpassthru($fp);
		return 1;
	}
	
	if ( !preg_match("/^[A-Za-z0-9][A-Za-z0-9\-+_]+$/", $_profilename) ) {
		$fp = fopen("icons/ErrorGraph.png", 'rb');
		fpassthru($fp);
		return 1;
	}
	if ( $_profilegroup != '.' && !preg_match("/^[A-Za-z0-9][A-Za-z0-9\-+_]+$/", $_profilegroup) ) {
		$fp = fopen("icons/ErrorGraph.png", 'rb');
		fpassthru($fp);
		return 1;
	}
	
	if ( !preg_match("/^[A-Za-z0-9][A-Za-z0-9\-+_]+$/", $file) ) {
		$fp = fopen("icons/ErrorGraph.png", 'rb');
		fpassthru($fp);
		return 1;
	}
	
	$opts = array();
	$opts['.silent'] = 1;
	$opts['profile'] = $profileswitch;
	$opts['picture'] = $file . '.png';
	nfsend_query("@get-picture", $opts, 1);
	nfsend_disconnect();
	unset($_SESSION['nfsend']);
	CloseLogFile();

} // End of GetProfilePic

function GetAnyPic () {
	
	if ( !array_key_exists('picture', $_GET) ) {
		header("Content-type: image/png");
		$fp = fopen("icons/Error.png", 'rb');
		fpassthru($fp);
		return 1;
	}

	$picture = $_GET['picture'];
	if ( !preg_match("/^[A-Za-z0-9][A-Za-z0-9\-+_\.\/]+$/", $picture) ) {
		header("Content-type: image/png");
		$fp = fopen("icons/Error.png", 'rb');
		fpassthru($fp);
		return 1;
	}

	if ( preg_match("/\.png$/i", $picture) ) {
		$type = "png";
	} else if ( preg_match("/\.gif$/i", $picture )) {
		$type = "gif";
	} else if ( preg_match("/\.jpg$/i", $picture)) {
		$type = "jpg";
	} else if ( preg_match("/\.svg$/i", $picture)) {
		$type = "svg+xml";
	} else {
		$fp = fopen("icons/Error.png", 'rb');
		fpassthru($fp);
		return 1;
	}

	header("Content-type: image/" . $type);

	$opts = array();
	$opts['.silent'] = 1;
	$opts['picture'] = $picture;
	nfsend_query("@get-anypicture", $opts, 1);
	nfsend_disconnect();
	unset($_SESSION['nfsend']);
	CloseLogFile();

} // End of GetAnyPic

if ( array_key_exists('profileswitch', $_GET) ) 
	GetProfilePic();
else
	GetAnyPic();

?>
