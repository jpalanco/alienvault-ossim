<?php

//
// Common functions
//
require_once 'av_init.php';

Session::logcheck("environment-menu", "MonitorsNetflows");


function av_debug_nfsen($msg)
{
    if (file_exists('/tmp/debug_nfsen'))
	{
    	file_put_contents("/tmp/nfsen", "$msg \n", FILE_APPEND);
    }
}


function nfsend_connect ( ) {
	global $COMMSOCKET;

	if ( isset($_SESSION['nfsend']) ) {
		$nfsend = $_SESSION['nfsend'];
	} else {
		$sock = $COMMSOCKET;
		$nfsend = socket_create(AF_UNIX, SOCK_STREAM, 0);
		// $nfsend = fsockopen( $sock, 0, $errno, $errstr, 10 );
		socket_set_option($nfsend, SOL_SOCKET, SO_REUSEADDR,1);

		if (!$nfsend) {
			$errstr = socket_strerror(socket_last_error());
			SetMessage('error', "nfsend connect() error: $errstr");
			unset($_SESSION['nfsend']);
			return FALSE;
		}
		if ( ! @socket_connect($nfsend, $sock) ) {
			$errstr = socket_strerror(socket_last_error($nfsend));
			socket_close($nfsend);
			SetMessage('error', "nfsend connect() error: $errstr");
			unset($_SESSION['nfsend']);
			return FALSE;
		}

		$timeout = array('sec' => 2, 'usec' => 0); 
		@socket_set_option($nfsend, SOL_SOCKET,SO_RCVTIMEO,$timeout); 

		$hello_string = @socket_read($nfsend, 256, PHP_NORMAL_READ);
		if ( ! $hello_string ) {
			$errno = socket_last_error($nfsend);
			if ( errno ) {
				$errstr = socket_strerror(socket_last_error($nfsend));
			} else {
				$errstr = "timeout";
			}
			socket_close($nfsend);
			SetMessage('error', "nfsend read() error: $errstr");
			unset($_SESSION['nfsend']);
			return FALSE;
		}

// print "<h3>string: $hello_string</h3>";
		list($status, $extra) = explode (' ', $hello_string, 2);
// print "<h3>HELLO Status: $status Extra: $extra</h3>";
		if ( $status != 220 ) {
			SetMessage('error', "nfsend connect error: $extra");
			socket_close($nfsend);
			unset($_SESSION['nfsend']);
			return FALSE;
		}
	}

	$_SESSION['nfsend'] = $nfsend;
	return TRUE;

} // End of nfsend_connect

function nfsend_disconnect ( ) {

	if ( !isset($_SESSION['nfsend']) ) {
		return;
	}
	$nfsend = $_SESSION['nfsend'];

	// try to quit politely
	if ( @socket_write($nfsend, "quit\n.\n") == FALSE ) {
		@socket_close($nfsend);
		unset($_SESSION['nfsend']);
		return;
	}

	// read answer - even if not important at this stage
	do {
		$confirmed = @socket_read($nfsend, 256, PHP_NORMAL_READ);
	}	while ($confirmed && (strncasecmp($confirmed, ".", 1) == 0 ));

	@socket_close($nfsend);
	unset($_SESSION['nfsend']);

} // End of nfsend_disconnect

function nfsend_query ( $command, $cmd_opts ) {
	global $DEBUG;

	av_debug_nfsen("CMD: $command \nPARAMS: " . json_encode($cmd_opts) . "\n");
	
	if ( !isset($_SESSION['nfsend']) ) {
ReportLog("nfsend No socket - open connection first");
		nfsend_connect();
	}

	if ( !isset($_SESSION['nfsend']) ) {
		SetMessage('error', "nfsend - connection failed!");
		return FALSE;
	}

	$nfsend = $_SESSION['nfsend'];

	$is_binary = preg_match("/^@/", $command);
	if ( $DEBUG == 1 && !$is_binary ) {
ReportLog("nfsend INTERNAL '.debug=1'");
		@socket_write($nfsend, ".debug=1\n");
	}
ReportLog("nfsend COMMAND '$command' binary: $is_binary");

	// Socket may have timouted since last query
	// check for errors while sending command, and reopenn socket in case of an error
	if ( @socket_write($nfsend, "$command\n") == FALSE ) {
		$errstr = socket_strerror(socket_last_error($nfsend));
ReportLog("nfsend 1st write() failed: reason: " . socket_strerror(socket_last_error($nfsend)));
		nfsend_connect();
		$nfsend = $_SESSION['nfsend'];
						
		if ( @socket_write($nfsend, "$command\n") == FALSE ) 
		{
			$errstr = socket_strerror(socket_last_error($nfsend));
			SetMessage('error', "nfsend socket_write() communication error: $errstr");
			@socket_close($nfsend);
			unset($_SESSION['nfsend']);
			return FALSE;
		}
	}

	// the socket is established and ready - just send the opts
	foreach ( $cmd_opts as $key => $value ) {
		if ( is_array($value) ) {
			foreach ( $value as $val ) {
ReportLog("nfsend WRITE: '_$key'='$val'");
				@socket_write($nfsend, "_$key=$val\n");			}
		} else {
ReportLog("nfsend WRITE: '$key'='$value'");
			@socket_write($nfsend, "$key=$value\n");
		}
	}
	// send EODATA
ReportLog("nfsend EODATA");
	@socket_write($nfsend, ".\n");

	$out_list = array();
	$debug  = array();
	$done   = 0;
	$EODATA = 0;
	$error_occured = 0;
	while ( !$done ) {
		if ( $is_binary ) 
			$line = @socket_read($nfsend, 1024, PHP_BINARY_READ);
		else
			$line = @socket_read($nfsend, 1024, PHP_NORMAL_READ);

		if ( $line == FALSE ) {
			$errno = socket_last_error($nfsend);
			if ( $errno ) {
				$errstr = socket_strerror(socket_last_error($nfsend));
				$ret = FALSE;
				SetMessage('error', "nfsend socket_read() communication error: $errstr");
ReportLog("nfsend connection error '$errno' '$errstr'");
			} else {
				// connection closed in binary mode
				$ret = TRUE;
			}

			@socket_close($nfsend);
			unset($_SESSION['nfsend']);
			return $ret;
		}
		if ( $is_binary ) {
			print "$line";
			continue;
		}

		$line = rtrim($line);
		
		if ( preg_match("/^$/", $line) ) {
			continue;
		}
				
		// was last line EODATA?
		if ( $EODATA ) {
			// if so, $line contains the status message
			$done 	= 1;
			$EODATA = 0;

ReportLog("nfsend STATUS '$line'");

			// parse status line for various messages			
			if ( strncasecmp($line, "ok ", 3) == 0 ) {
				continue;
			}

			if ( strncasecmp($line, "err ", 4) == 0 ) {
				$msg = substr($line, 4);
				$error_occured = 1;
				SetMessage('error', "nfsend: $msg");
				continue;
			}
			
			if ( strncasecmp($line, "warn ", 5) == 0 ) {
				$msg = substr($line, 5);
				SetMessage('warning', "nfsend: $msg");
				continue;
			}

			if ( strncasecmp($line, "alert ", 6) == 0 ) {
				$msg = substr($line, 6);
				SetMessage('alert', "nfsend: $msg");
				continue;
			}

			// not needed, but catch it anyway
			continue;
		}
		
		if ( preg_match("/^\..+/", $line) ) {
ReportLog("nfsend Skip line '$line'");
			$debug[] = $line;
			continue;
		}

		if ( preg_match("/^INFO /", $line) ) {
ReportLog("nfsend Skip info line '$line'");
			continue;
		}
		
		// EODATA received
		if ( preg_match("/^\.$/", $line) ) {
			$EODATA = 1;
			continue;
		}

		if ( !preg_match("/=/", $line) ) {
ReportLog("nfsend Skip buggy line '$line' Expected key=value pair");
			continue;
		}

ReportLog("nfsend Process line '$line'");
		// parse regular output lines
		list($key, $value) = explode ('=', $line, 2);

		// check for multiline output
		if ( preg_match("/^\_(.+)/", $key, $matches) ) {
			$key = $matches[1];
			$out_list[$key][] = $value;
		} else {
			$out_list[$key] = $value;
		}
	}

	return $is_binary ? TRUE : ( $error_occured ? FALSE : $out_list);

} // End of nfsend_query

function UNIX2ISO ($time) {

	$tmp = localtime($time);
	$tstring  = $tmp[5] + 1900;
	$tmp[4]  += 1;
	$tstring .= $tmp[4] < 10 ? "0" . $tmp[4] : $tmp[4];
	$tstring .= $tmp[3] < 10 ? "0" . $tmp[3] : $tmp[3];
	$tstring .= $tmp[2] < 10 ? "0" . $tmp[2] : $tmp[2];
	$tstring .= $tmp[1] < 10 ? "0" . $tmp[1] : $tmp[1];

	return $tstring;

} // End of UNIX2ISO

function ISO2UNIX ($time) {

	// 2004 02 13 12 45 /
	preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})/", $time, $matches);
	$unixtime = mktime ( $matches[4], $matches[5], 0, $matches[2], $matches[3], $matches[1]);

	return $unixtime;

} // End of ISO2UNIX

function DISPLAY2UNIX($str) {

	$_tmp = preg_replace("/-/", "", $str);

	$_len = strlen($_tmp);
	if ( $_len < 12 || $_len > 12 )
		return -1;

	if ( preg_match("/[^\d]+/", $_tmp ))
		return -1;

	if ( is_numeric($_tmp) && $_tmp > 197001010000 && $_tmp < 203801191414 ) {
		$_tmp = ISO2UNIX($_tmp);
	} else
		$_tmp = -1;

	return $_tmp;

} // End of DISPLAY2UNIX

function UNIX2DISPLAY($time) {
	// converts any UNIX time in display readable format:
	// 2004-02-13-12-45
	
	$iso = UNIX2ISO($time);

	return preg_replace("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})/", "$1-$2-$3-$4-$5", $iso);

} // End of UNIX2DISPLAY

function ScaleValue ( $value, $rateval ) {

	if ( $rateval != 1 ) {
		$value = $value / $rateval;
		$rate_label = '/s';	// make bits
	} else {
		$rate_label = '';
	}

	$_1KB = 1000.0;
	$_1MB = 1000.0 * $_1KB;
	$_1GB = 1000.0 * $_1MB;
	$_1TB = 1000.0 * $_1GB;

	$scaled = 0;
	if ( $value >= $_1TB ) {
		$scaled = $value / $_1TB;
		$unit = "T";
	} elseif ( $value >= $_1GB ) {
		$scaled = $value / $_1GB;
		$unit = "G";
	} elseif ( $value >= $_1MB ) {
		$scaled = $value / $_1MB;
		$unit = "M";
	} elseif ( $value >= $_1KB ) {
		$scaled = $value / $_1KB;
		$unit = "k";
	} else {
		$scaled = $value;
		$unit = " ";
	}
	if ( $scaled > 0 )
		$str = sprintf("%5.1f %s%s", $scaled, $unit, $rate_label);
	else
		$str = "$value ${unit}${rate_label}";

	return preg_replace("/^\s+/", "", $str);
	

} // End of ScaleValue


function ScaleBytes ( $value, $rateval, $bs ) {

	if ( $rateval != 1 ) {
		$value = $value / $rateval * 8;
		$rate_label = '/s';	// make bits
		$Bit_Byte   = 'b';
	} else {
		$rate_label = '';
		$Bit_Byte   = 'B';
	}

	// Scale Bytes: For traffic $bs = 1000 for diskspace $bs 1024
	$_1KB = 1.0 * $bs;
	$_1MB = $bs * $_1KB;
	$_1GB = $bs * $_1MB;
	$_1TB = $bs * $_1GB;

	$scaled = 0;
	if ( $value >= $_1TB ) {
		$scaled = $value / $_1TB;
		$unit = "T";
	} elseif ( $value >= $_1GB ) {
		$scaled = $value / $_1GB;
		$unit = "G";
	} elseif ( $value >= $_1MB ) {
		$scaled = $value / $_1MB;
		$unit = "M";
	} elseif ( $value >= $_1KB ) {
		$scaled = $value / $_1KB;
		$unit = $bs == 1000 ? "k" : "K";
	} else {
		$scaled = $value;
		$unit = " ";
	}
	if ( $scaled > 0 )
		$str = sprintf("%5.1f %s%s%s", $scaled, $unit, $Bit_Byte, $rate_label);
	else
		$str = "$value ${unit}${Bit_Byte}${rate_label}";

	return preg_replace("/^\s+/", "", $str);
	
} // End of ScaleBytes

function SubdirHierarchy( $t ) {
	global $SUBDIRLAYOUT;

	if ( $SUBDIRLAYOUT == 0 ) 
		return '';

	$subdir_def = array (
		"",
		"%Y/%m/%d",
		"%Y/%m/%d/%H",
		"%Y/%W/%u",
		"%Y/%W/%u/%H",
		"%Y/%j",
		"%Y/%j/%H",
		"%Y-%m-%d",		# %F not supported by PHP
		"%Y-%m-%d/%H"
	);

	return strftime ($subdir_def[$SUBDIRLAYOUT], $t);

} // End of SubdirHierarchy

//
// Plugin related functions
//

function Plugin_Error() 
{	
	print "<h3>Plugin Error! Can't run plugin</h3>\n";
} // End of Plugin_Error

function GetPlugins () {

	if ( array_key_exists('PluginList', $_SESSION ) ) {
		if ( array_key_exists('PluginListUpdate', $_SESSION ) && 
		( time() - $_SESSION['PluginListUpdate'] < 600 ) ) {
			return $_SESSION['PluginList'];
		}
	}

	$out_list = nfsend_query("get-frontendplugins", array(), 0);
	if ( is_array($out_list) ) {
		$plugins = array_key_exists('frontendplugins', $out_list) ? $out_list['frontendplugins'] : array();
	} else {
		$plugins = array();
	}
	$_SESSION['PluginList'] = $plugins;
	$_SESSION['PluginListUpdate'] = time();

	return $plugins;

} // End of GetPlugins

//
// Profile related functions
//

function GetProfiles () {

	if ( array_key_exists('ProfileList', $_SESSION ) ) {
		if ( array_key_exists('ProfileListUpdate', $_SESSION ) && 
		( time() - $_SESSION['ProfileListUpdate'] < 600 ) ) {
			return $_SESSION['ProfileList'];
		}
	}

	$out_list = nfsend_query("get-profilelist", array(), 0);
	if ( is_array($out_list) ) {
		$profiles = $out_list['profiles'];
		$profiles[] = "New Profile ...";
	} else {
		$profiles = array();
		$profiles[] = "&lt;No profiles available&gt;";
	}
	$_SESSION['ProfileList'] = $profiles;
	$_SESSION['ProfileListUpdate'] = time();

	return $profiles;

} // End of GetProfiles

function ReadProfile ($profileswitch) {

	$opts['profile'] 	  = $profileswitch;

	$profileinfo = nfsend_query("get-profile", $opts, 0);
	if ( !is_array($profileinfo) ) {
		return false;
	}

	if ( !array_key_exists('description', $profileinfo ) )
		$profileinfo['description'] = array();

	$channels = array();
	// in case it's a new profile with no channels associated yet
	if ( !array_key_exists('channel', $profileinfo ) )
		$profileinfo['channel'] = $channels;

	$profileinfo['all_channels'] = array();

	require_once('ossim_db.inc');
	$db   = new ossim_db();
	$conn = $db->connect();

	// Decode channel information
	foreach ( $profileinfo['channel'] as $channel ) {

		list($id, $sign, $colour, $order, $sourcelist) = explode(":", $channel);

		$profileinfo['all_channels'][$id] = $id;

		if(!Av_sensor::is_channel_allowed($conn, $id)) continue;
		
		$_tmp               = array();
		$_tmp['id']         = $id;
		$_tmp['name']       = Av_sensor::get_nfsen_channel_name($conn, $id);
		$_tmp['sign']       = $sign;
		$_tmp['colour']     = $colour;
		$_tmp['order']      = $order;
		$_tmp['sourcelist'] = $sourcelist;
		
		$channels[$id] = $_tmp;
	}

	$db->close();

	$profileinfo['channel'] = $channels;

	return $profileinfo;



} // End of ReadProfile

function allowed_nfsen_section()
{
	if(Session::am_i_admin()) return true;

	require_once('ossim_db.inc');
	$db   = new ossim_db();
	$conn = $db->connect();
	$flag = true;

	// Decode channel information
	foreach ($_SESSION['profileinfo']['all_channels'] as $channel ) 
	{
		if(!Av_sensor::is_channel_allowed($conn, $channel)) 
		{
			$flag = false;
			break;
		} 		
	}

	$db->close();

	return $flag;
}

function ShowMessages () {

	if ( array_key_exists('error', $_SESSION ) ) {
		foreach ( $_SESSION['error'] as $msg ) {
			print "<h3 class='errstring'>ERROR: ".Util::htmlentities($msg)."!</h3>\n";
		}
		unset($_SESSION['error']);
	}

	if ( array_key_exists('warning', $_SESSION ) ) {
		foreach ( $_SESSION['warning'] as $msg ) {
			print "<h3 class='warnstring'>WARNING: ".Util::htmlentities($msg)."!</h3>\n";
		}
		unset($_SESSION['warning']);
	}

	if ( array_key_exists('alert', $_SESSION ) ) {
		foreach ( $_SESSION['alert'] as $msg ) {
			print "<h3 class='alertstring'>ALERT: ".Util::htmlentities($msg)."!</h3>\n";
		}
		unset($_SESSION['alert']);
	}

	if ( array_key_exists('info', $_SESSION ) ) {
		foreach ( $_SESSION['info'] as $msg ) {
			print "<h3 class='infostring'>".Util::htmlentities($msg)."!</h3>\n";
		}
		unset($_SESSION['info']);
	}

} // End of ShowMessages

function SetMessage ($type, $msg) {

	$message = Util::htmlentities($msg);

	if ( $type != 'info' && $type != 'alert' && $type != 'warning' && $type != 'error' ) {
		$type = 'error';
		$message = 'Internal error, setting message';
	}
	if ( !array_key_exists($type, $_SESSION ) ) {
		$_SESSION[$type] = array();
	}
	$_SESSION[$type][] = $message;

} // End of SetMessage

function ClearMessages () {

	if ( array_key_exists('info', $_SESSION ) ) {
		unset($_SESSION['info']);
	}

	if ( array_key_exists('warning', $_SESSION ) ) {
		unset($_SESSION['warning']);
	}

	if ( array_key_exists('error', $_SESSION ) ) {
		unset($_SESSION['error']);
	}
	if ( array_key_exists('alert', $_SESSION ) ) {
		unset($_SESSION['alert']);
	}

} // End of ClearMessages

function NumMessages( $type ) {
	if ( $type != 'alert' && $type != 'warning' && $type != 'error' )
		return 0;

	if ( array_key_exists($type, $_SESSION ) )
		return count($_SESSION[$type]);
	else 
		return 0;

} // End of NumMessages

function ReadStat($profile, $profilegroup, $channel) {

	$opts['profile'] 	  = $profile;
	$opts['profilegroup'] = $profilegroup;
	$opts['channel'] 	  = $channel;

	$opts['tstart'] = UNIX2ISO($_SESSION['tleft']);
	if ( $_SESSION['tleft'] != $_SESSION['tright'] ) {
		$opts['tend'] = UNIX2ISO($_SESSION['tright']);
	} 

	$statinfo = nfsend_query("get-statinfo", $opts, 0);
	if ( !is_array($statinfo) ) {
		return NULL;
	}

	return $statinfo;

} // End of ReadStat 

function FindMaxValue() {

	$profileswitch = $_SESSION['profileswitch'];
	$detail_opts   = $_SESSION['detail_opts'];

	$type = $detail_opts['type'] . '_' . $detail_opts['proto'];
	$channellist = $detail_opts['channellist'];

	$tslot = UNIX2ISO($_SESSION['tleft']);

	$cmd_opts['profile']		= $profileswitch;
	$cmd_opts['channellist'] 	= $channellist;
	$cmd_opts['tinit']			= $tslot;
	$cmd_opts['type'] 			= $type;

	$tmp = nfsend_query("get-peek", $cmd_opts, 0);
	if ( !is_array($tmp) ) {
		return 0;
	}
	return ISO2UNIX($tmp['tpeek']);

} // End of FindMaxValue

function DefaultFilters () {

	if ( array_key_exists('DefaultFilters', $_SESSION ) ) {
		if ( array_key_exists('DefaultFiltersUpdate', $_SESSION ) && 
		( time() - $_SESSION['DefaultFiltersUpdate'] < 600 ) ) {
			return $_SESSION['DefaultFilters'];
		}
	}

	$out_list = nfsend_query("get-filterlist", array(), 0);
	if ( !is_array($out_list) ) {
		$out_list = array();
	}

	$_SESSION['DefaultFilters'] = array_key_exists('list', $out_list) ? $out_list['list'] : array();
	$_SESSION['DefaultFiltersUpdate'] = time();

	return $out_list;

} // End of DefaultFilters

/*
 * Parse the expire input string and set new_expire as the number of hours
 * for this profiles new expire value.
 * valid form:
 *	<num>				number of hours
 * 	<num> h|hour|hours	number of hours
 *	<num> d|day|days	number of days
 *	<num> d|day|days <num> h|hour|hours	combination days and hours
 * returns the number of hours of new expire or -1 if string unparsable
 */
function ParseExpire ($str) {

	$new_expire = 0;
	$valid		= 0;	// we found a valid string

	$str = preg_replace( array( '/^\s+/s', '/\s+$/s'), array( '', ''), $str);
	// a single number: add 'h'
	if ( is_numeric($str) ) {
		$str = (int)$str;
		$str .= "h";
	}

	// normalize the input string. Replace 'never' with 0h
	$str  = preg_replace("/never/i", "0h", $str);

	// normalize the input string. Replace all day, days with 'd'
	$str  = preg_replace("/days/i", "d", $str);
	$str  = preg_replace("/day/i", "d", $str);

	// normalize the input string. Replace all hour, hours with 'h'
	$str  = preg_replace("/hours/i", "h", $str);
	$str  = preg_replace("/hour/i", "h", $str);

	// now parse the string
	preg_match("/(\d+\.{0,1}\d*)\s*d/", $str, $matches);
	if ( count($matches) == 2 ) { 	// at least 2 entries are required
		$new_expire = (int)($matches[1] * 24);
		$valid = 1;
	}
	preg_match("/(\d+)\s*h/", $str, $matches);
	if ( count($matches) == 2 ) {	// at least 2 entries are required
		$new_expire += $matches[1];
		$valid = 1;
	}
	
	return $valid ? $new_expire : NULL;

} // End of ParseExpire

/*
 * parse new max size.
 * valid form:
 *	<num>	defaults to num MB
 * 	<num><scale>	<scale> : K[B], M[B], G[B], and T[B]
 * returns the number of bytes of new max size or -1 if string unparsable
 */
function ParseMaxSize ($str) {

	$valid		 = 0;	// we found a valid string

	$str = preg_replace( array( '/^\s+/s', '/\s+$/s'), array( '', ''), $str);
	if ( $str == 'unlimited' ) 
		$str = "0";

	// a single number: add 'M'
	if ( is_numeric($str) ) {
		$str = (int)$str;
		$str .= "M";
	}

	// normalize the input string. Strip 'B'
	$str  = preg_replace("/B/", "", $str);

	preg_match("/(\d+\.{0,1}\d*)\s*([K|M|G|T])/", $str, $matches);
	if ( count($matches) == 3 ) {	// at least 3 entries are required
		if ( is_numeric($matches[1]) ) {
			$valid = 1;
		}
	}

	$str = preg_replace("/\s+/", '', $str);
	
	return $valid ? $str : NULL;

} // End of ParseMaxSize

function GetDataFromSingleIp($ip, $host_arr)
{
    if (empty($host_arr[$ip]))   return array($ip,'',''); // not found
    if (count($host_arr[$ip])>1) return array($ip,'',''); // more that one ip with different contexts => return ip
    foreach ($host_arr[$ip] as $ctx => $host) return array($host["name"],$ctx,$host["id"]);
}
?>
