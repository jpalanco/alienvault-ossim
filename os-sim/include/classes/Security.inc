<?php
/**
* Security.inc
*
* File Security.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
*
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - clean_echars($data)
* - get_echars($data)
* - ids()
* - ids_valid($data)
* - html_vul_scan($string, $is_get)
* - htm($var)
* - strip($var)
* - required()
* - GET($param)
* - POST($param)
* - REQUEST($param) 
* - ossim_valid()
* - ossim_set_error($error)
* - ossim_clean_error()
* - ossim_get_error()
* - ossim_get_error_clean()
* - ossim_error($error_msg = NULL, $error_type = AV_ERROR, $styles = 'width: 90%; text-align:left; margin:auto;')
* - validate_form_fields($type, $validate)
* - valid_user($user)
* - valid_cidr($cidrs)
* - valid_port($ports)
* - valid_ip($ip)
* - valid_ip_2($ip)
* - valid_ipcidr($ip)
* - valid_ipcidr_2($ip)
* - valid_ipcidr_00($ip)
* - valid_ipcidr_3($ip)
* - valid_email($email)
* - valid_mac($mac)
* - valid_hostname($hostname)
* - valid_fqdns($fqdns)
* - valid_url($url)
* - valid_filename($filename)
* - valid_quotes($value)
* - valid_datetime($value)
* - valid_date($value)
* - valid_tzone($value)
* - valid_weekday($value)
* - is_ip($value)
* - is_cidr($value)
* - valid_fqdn_or_ip($value)
* - validate_sensor_perms($conn,$sensor_ip,$sql="")
* - valid_echars($value)
* - check_uniqueid($unique_id1,$unique_id2)
* - checkpass($conn, $current_pass, $pass1, $pass2, $user)
* - create_valid_hostname($hostname)
* - valid_hex32($value) 
*/

ini_set('session.bug_compat_warn','off');


require_once 'IDS/Init.php';
require_once 'classes/Util.inc'; 

define("MIN_ECHAR_VALUE", 1000);

define('OSS_DIGIT', '0-9');
define('OSS_BINARY', '0-1');
define('OSS_LETTER', 'A-Za-z');
define('OSS_HEX', '0-9A-Fa-f');
define('OSS_SPACE', " ".chr(160)); // Character 160: Non-breaking space [http://www.ascii.cl/htmlcodes.htm]
define('OSS_SCORE', '_\-');
define('OSS_DOT', '\.');
define('OSS_MIDDLEDOT', '\¬∑');
define('OSS_COLON', ':');
define('OSS_AT', '@');
define('OSS_MAC', 'func:valid_mac');
define('OSS_BRACKET', '\[\]\{\}');
define('OSS_PUNC_EXT', '\'"\.\!#\$%\^&\*_\-\=\+\:;,~@\[\]\{\}\|\?\\\(\)\/∫™∑' . OSS_SPACE);
define('OSS_PUNC', '\.,:@_\-\/\?&\=' . OSS_SPACE);
define('OSS_SLASH', '\/');
define('OSS_URL', '\.\/');
define('OSS_EALPHA_LOWER', '·ÈÌÛ˙˝‡ËÏÚ˘‰ÎÔˆ¸ˇ‚ÍÓÙ˚„Òı®ÂÊÁΩ¯˛ﬂ');
define('OSS_EALPHA_UPPER', '¡…Õ”⁄›¿»Ã“ŸƒÀœ÷‹æ¬ Œ‘€√—’¶≈∆«º–ÿﬁ');
define('OSS_NOECHARS', 'no_echars');
define('OSS_ALPHA', OSS_DIGIT . OSS_LETTER . OSS_EALPHA_LOWER . OSS_EALPHA_UPPER);
define('OSS_CRONTAB', OSS_DIGIT . OSS_LETTER . '\*\-,\/'); // Somewhat dangerous, if "/" is allowed don't allow spaces, tabs or anything similar.
define('OSS_NL', "\r\n");
define('OSS_INPUT', OSS_ALPHA . OSS_PUNC); // for most input text fields
define('OSS_TEXT', OSS_ALPHA . OSS_PUNC . OSS_NL . '\)\(\'"'); // for most textarea fields
define('OSS_USER', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT);  // for validating logins/users with login can be just numerical
define('OSS_CLOGIN', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT.'@\*\\');  // credential login for Openvas
define('OSS_USER_2', 'func:valid_user'); // for validating logins/users with login can not be just numerical
define('OSS_NET_NAME', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_SPACE . OSS_DOT . OSS_COLON); // for network names
define('OSS_NOTNULL', 'rule:OSS_NOTNULL'); // default for all vars
define('OSS_NULLABLE', 'rule:OSS_NULLABLE'); // value can be null, false, missing
define('OSS_PORT', 'func:valid_port');  //	Valid port number or pair port-protocol. Port range: [0 - 65535]; Separators allowed : -, _, #; Protocols allowed : tcp, udp, icmp
define('OSS_PROTOCOL', 'tcp | udp | icmp');
define('OSS_CCLASS', 'func:valid_cclass'); // C-class 0.0.0 and 255.255.255 allowed
define('OSS_IP_ADDR', 'func:valid_ip');
define('OSS_IP_ADDR_0', 'func:valid_ip_2'); // IP address 0.0.0.0. allowed)
define('OSS_IP_CIDR', 'func:valid_cidr'); // CIDR 0.0.0.0/xx allowed
define('OSS_IP_CIDR_0', 'func:valid_ipcidr_00'); // IP or CIDR format (0.0.0.0 and 0.0.0.0/xx allowed, included 0.0.0.0/0)
define('OSS_IP_ADDRCIDR', 'func:valid_ipcidr'); // IP OR CIDR format (CIDR 0.0.0.0/xx allowed)
define('OSS_IP_ADDRCIDR_0', 'func:valid_ipcidr_2'); // IP or CIDR format (0.0.0.0 and 0.0.0.0/xx allowed)
define('OSS_SEVERAL_IP_ADDRCIDR_0', 'func:valid_ipcidr_3'); // Several IPs OR CIDRs formats (0.0.0.0. allowed)
define('OSS_FQDNS', 'func:valid_fqdns');
define('OSS_QUOTES', 'func:valid_quotes');
define('OSS_DATE', 'func:valid_date');
define('OSS_DATETIME', 'func:valid_datetime');
define('OSS_DATETIME_DATE', 'func:valid_datetime_date');
define('OSS_MAIL_ADDR', 'func:valid_email');
define('OSS_MAIL_MESSAGE', OSS_ALPHA . OSS_PUNC . OSS_SCORE . OSS_AT . OSS_NL . '\>\<\!#');
define('OSS_MAIL_USER', 'regex:^[a-zA-Z0-9_\-\.+@]+$');
define('OSS_BASE64', 'regex:[a-zA-Z0-9\+\=\/]+');
define('OSS_TELEPHONE', 'regex:[+\-\s\d]+');
define('OSS_TIMING_TEMPLATE', 'regex:\-T[0-5]');
define('OSS_ERROR', _("Unknown error")); // default error message
define('OSS_SQL', 'illegal:(select |union |update |delete |insert)');
define('OSS_URL_ADDRESS', 'func:valid_url');
define('OSS_FILENAME', 'func:valid_filename');
define('OSS_WEEKDAY', 'func:valid_weekday');
define('OSS_SHA1', OSS_LETTER . OSS_DIGIT);
define('OSS_PASSWORD', OSS_NOECHARS . OSS_DIGIT . OSS_ALPHA . OSS_PUNC_EXT . OSS_SPACE . '\>\<');
define('OSS_HOST_NAME', 'func:valid_hostname');
define('OSS_HEXDIGIT', OSS_DIGIT.'A-Fa-f');
define('OSS_HEXCOLOR', '#'.OSS_HEXDIGIT);
define('OSS_TZONE', 'func:valid_tzone');
define('OSS_FQDN_IP', 'func:valid_fqdn_or_ip'); //IP (0.0.0.0 not allowed) OR FQDN
define('OSS_MAIL_SERVER_ADDRESS','func:valid_smtp_server_address');
define('OSS_UUID', 'func:valid_uuid');
define('OSS_SUPPORT_TICKET_NUMBER', 'func:valid_support_ticket_number');

// Directive editor
define('OSS_PLUGIN_SID_LIST', OSS_DIGIT . OSS_LETTER . '\[\]\+_,');
define('OSS_PLUGIN_SID', OSS_DIGIT . OSS_LETTER . OSS_COLON . ',');
define('OSS_GROUP_LIST', OSS_DIGIT . OSS_LETTER . OSS_SCORE . ',');
define('OSS_DIRECTIVE_NAME', OSS_DIGIT . OSS_LETTER . OSS_SCORE . OSS_SPACE . OSS_DOT . '\,\(\)');
define('OSS_GROUP_NAME', OSS_DIGIT . OSS_LETTER . OSS_SCORE . OSS_SPACE);
define('OSS_FROM', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_DOT . ',_\-\!\/');
define('OSS_TO', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_DOT . ',_\-\!\/');
define('OSS_PORT_FROM', OSS_DIGIT . OSS_LETTER . OSS_COLON . '_\!,');
define('OSS_PORT_TO', OSS_DIGIT . OSS_LETTER . OSS_COLON . '_\!,');
define('OSS_PORT_FROM_LIST', OSS_DIGIT . OSS_LETTER . OSS_COLON . '_\!,');
define('OSS_PORT_TO_LIST', OSS_DIGIT . OSS_LETTER . OSS_COLON . '_\!,');
define('OSS_SENSOR', OSS_DIGIT . OSS_DOT . OSS_LETTER . OSS_COLON . '\-,\!');
define('OSS_RULE_NAME', OSS_COLON . OSS_DIGIT . OSS_LETTER . OSS_SCORE . OSS_SPACE . OSS_DOT . OSS_SLASH . '\)\(');

// Don't use
$GLOBALS['ossim_last_error'] = FALSE; // track the latest error


//Remove special chars (chinese characters)
function clean_echars($data)
{
    if (is_array($data))
    {
        foreach ($data as $i => $d)
        {
            $data[$i] = clean_echars($d);
        }
    }
    else
    {
        if (preg_match_all('/&#(\d{4,5});/', $data, $e_chars))
        {
            foreach ($e_chars[1] as $e_char)
            {
                $data = str_replace('&#'.$e_char.';', '', $data);
            }
        }
    }

    return $data;
}


function get_echars($data)
{
	$echars = (preg_match_all('/&#(\d{4,5});/', $data, $match) != FALSE) ? $match[1] : array();
	
	return $echars;
}


function ids_callback(&$val, $key)
{
    $val = (mb_detect_encoding($val.' ','UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($val) : $val;
    $val = clean_echars($val);
}


function ids()
{
	try 
	{		
        $general_data = array($_GET, $_POST, $_FILES);
        		
        array_walk_recursive($general_data, 'ids_callback');
        
        $request = array(
        	'GET'    => $general_data[0],
        	'POST'   => $general_data[1],
        	'FILES'  => $general_data[2]
        );        
        
        /*$request = array(
        	'GET'     => $_GET,
        	'POST'    => $_POST,
        	'REQUEST' => $_REQUEST,
        	'COOKIE'  => $_COOKIE,
        	'FILES'   => $_FILES
        	'URI'     => rawurlencode($_SERVER["REQUEST_URI"])
       );*/
		
		$init = IDS_Init::init('/usr/share/ossim/include/php-ids.ini');
		$init->config['General']['scan_keys'] = TRUE;
		$ids = new IDS_Monitor($request, $init);
		$result = $ids->run();
		
		if (!$result->isEmpty()) 
		{
			//require_once 'IDS/Log/Database.php';
			require_once 'IDS/Log/Composite.php';
			require_once 'IDS/Log/File.php';

			$compositeLog = new IDS_Log_Composite();
			//$compositeLog->addLogger(IDS_Log_Database::getInstance($init));
			$compositeLog->addLogger(IDS_Log_File::getInstance($init));
			$compositeLog->execute($result);
			
			$error_msg = _('Sorry, operation was not completed due to security reasons. An attack attempt has been logged to the system').'<br/>'.utf8_encode($result);
			
			echo ossim_error($error_msg, AV_WARNING);
			exit();
		}
	} 
	catch (Exception $e) 
	{
		$error_msg = _('An error occured').': '.$e->getMessage();
		
		echo ossim_error($error_msg, AV_WARNING);		
		exit();
	}
}


function ids_valid($data)
{
	try 
	{
		$request = array('INPUT' => $data);
		
		$init = IDS_Init::init('/usr/share/ossim/include/php-ids.ini');
		$init->config['General']['scan_keys'] = TRUE;
		$ids = new IDS_Monitor($request, $init);
		$result = $ids->run();
		if (!$result->isEmpty()) 
		{
			require_once 'IDS/Log/Composite.php';
			require_once 'IDS/Log/File.php';

			$compositeLog = new IDS_Log_Composite();
			$compositeLog->addLogger(IDS_Log_File::getInstance($init));
			$compositeLog->execute($result);
						
			$error_msg = _('Sorry, operation was not completed due to security reasons. An attack attempt has been logged to the system').'<br/>'.utf8_encode($result);
			
			echo ossim_error($error_msg, AV_WARNING);			
			exit();			
		}
	} 
	catch (Exception $e) 
	{
		$error_msg = _('An error occured').': '.$e->getMessage();
		
		echo ossim_error($error_msg, AV_WARNING);		
		exit();
	}
}


function html_vul_scan($string, $is_get) 
{
   $scan_for = array(
        '/<\s*script[^>]*>/Ui'
   );
    
    if (is_array($string)) 
    {
        foreach($string as $string_piece) 
        {
            foreach($scan_for as $s) 
            {
                if ($is_get) 
                {
                    if (preg_match("/^(\<=|\>=|\>|\<|\<\>)$/",$string_piece)) 
                    {
                        continue;
                    }
                    
                    if ($string_piece != strip_tags($string_piece)) 
                    {
                        die(ossim_error('HTTP GET ' . _('param not allowed for security reasons'), AV_WARNING));
                    }
                }
                
                if (preg_match($s, $string_piece)) 
                {
                    die(ossim_error('HTTP POST ' . _('param not allowed for security reasons'), AV_WARNING));
                }
            }
        }
    } 
    else 
    {
        $string = html_entity_decode($string);
        foreach($scan_for as $s) 
        {
            if ($is_get) 
            {
                if (preg_match('/^<<./', $string) || preg_match("/^(\<=|\>=|\>|\<|\<\>)$/", $string)) 
                {
                    continue;
                }
                
                if ($string !== strip_tags($string)) 
                {
                    die(ossim_error('HTTP GET ' . _('param not allowed for security reasons'), AV_WARNING));
                }
            }
            
            if (preg_match($s, $string)) 
            {
                die(ossim_error('HTTP POST '._('param not allowed for security reasons'), AV_WARNING));
            }
        }
    }
}
/*
* Scan all HTTP vars disallowing common exploits like XSS
*/
$_html_vars_checked = TRUE;

if (!isset($_html_vars_checked)) 
{
    $_is_get = TRUE;
    
    foreach(array($_GET,$_POST) as $globalvar) 
    {
        foreach($globalvar as $key => $value) 
        {
            if (is_array($value)) 
            {
                foreach($value as $v) 
                {
                    html_vul_scan($v, $_is_get);
                }
            } 
            else 
            {
                html_vul_scan($value, $_is_get);
            }
        }
        
        $_is_get = FALSE;
    }    
}


/*
* Safe html escape. Always use that for printing vars comming
* from outside ($_GET, $_POST, databases, etc)
*/
function htm($var) 
{
	return nl2br(Util::htmlentities($var));
}


function strip($var) 
{
    return get_magic_quotes_gpc() ? strip_map($var) : $var;
}


function strip_map($value)
{
    $value = is_array($value) ? array_map('strip_map', $value) : stripslashes($value);

    return $value;
}


function required() 
{
    return '&nbsp;<span class="required" title="' . _('Required field') . '">*</span>';
}


/*
* WARNING: if you use this function, it's a MUST to correct quote
* the values before inserting in the DB. AdoDB provides that, ex:
*
* $get1 = GET('get1');
* $get2 = GET('get2');
* $sql = "INSET INTO foo VALUES (?, ?)";
* $conn->Execute($sql, array($get1, $get2));
*
* $get1 will be automagically quoted against the DB quoting function
* (ex: mysql_real_escape_string()) and the result (ex: 'It\'s tea time')
* will replace the first "?". The same for $get2.
*/
function GET($param) 
{
    return isset($_GET[$param]) ? strip($_GET[$param]) : NULL;
}


/*
* @see GET()
*/
function POST($param) 
{
    return isset($_POST[$param]) ? strip($_POST[$param]) : NULL;}


/*
* @see GET()
*/
function REQUEST($param) 
{
    return isset($_REQUEST[$param]) ? strip($_REQUEST[$param]) : NULL;
}

/*
Usage examples:

//--- Simple validation  ---//

// Note that by default ossim_valid() doesn't permit empty vars.
// It implicitly adds the rule OSS_NOTNULL. If the var is optional
// or can be an empty value, add the rule OSS_NULLABLE

if (!ossim_valid($id, OSS_DIGIT)) {
	echo ossim_error();
}

//--- Complex validations ---//

// The special rule 'error:BLAHBLAH', means that in case
// the var does not validate against the other rules,
// an error will be registered (internally using ossim_set_error()).
// You can later check and retrieve the error by calling ossim_error()

// The special rule 'illegal:Title' would create automatically an
// error message in the form:
// "Error for the 'Title' field (<what caused the error>)"

// You can add your custom validation rules too, read the comments
// at the ossim_valid() function

$vals = array(
	'id'   => array(OSS_DIGIT, 'error:Invalid ID, only numbers allowed'),
	'name' => array(OSS_LETTER, OSS_SPACE, 'error:Only letters and spaces')
);

$name = isset($_GET['name']) ? $_GET['name'] : null;

ossim_valid($id, $vals['id']);
ossim_valid($name, $vals['name']);

if (ossim_error()) {
	echo ossim_error();
}


* Function for validating variables against the given rules
*
* @param1 The var to check
*
* @param2 Array with a list of validations
*         Ex: ossim_valid($foo, array(OSS_AT, OSS_SPACE))
* or
*
* @param2 to @paramN Checks
*         Ex: ossim_valid($foo, OSS_AT, OSS_SPACE)
*
* A check could be:
*      1) a string that would be converted into a regex. Ex. '0-9' -> '/[^0-9]/'
*      2) func:<func_name> Will call <func_name> passing var as the only arg.
*                          Should return true or false.
*                          Func can also be in the form <Class::method>
*      3) regex:<regex>   Will check var against the regex.
*                         Ex. '^[0-9]+' -> '/^[0-9]+/'
*      4) rule:<rules>    Not suitable for users



//--- Tests ---//

function test($res, $expected)
{
	static $test_num = 0;
	echo '#' . ++$test_num;
	if ($res != $expected) { 
		echo " FAIL!\n"; return; 
	}
	echo " GOOD\n";
}

ini_set('include_path', '../');

test(ossim_valid('24', OSS_DIGIT), true); #1
test(ossim_valid('0.24', OSS_DIGIT), false); #2
test(ossim_valid('hello', OSS_ALPHA), true); #3
test(ossim_valid('hello dasd', OSS_ALPHA), false); #4
test(ossim_valid('', OSS_NOTNULL), false); #5
test(ossim_valid('', OSS_NULLABLE), true); #6
test(ossim_valid('3', OSS_LETTER), false); #7
test(ossim_valid('hello', OSS_LETTER), true); #8
test(ossim_valid('0.1.1.1', OSS_IP_ADDR), true); #9
test(ossim_valid('500.1.1.1', OSS_IP_ADDR), false); #10
test(ossim_valid('hello@hello.com', OSS_MAIL_ADDR), true); #11
test(ossim_valid('hello@hello', OSS_MAIL_ADDR), false); #12
test(ossim_valid('hello@he.llo.', OSS_MAIL_ADDR), false); #13
test(ossim_valid('hello@he.llo.9', OSS_MAIL_ADDR), false); #14
test(ossim_valid('hello@hello.com9', array(OSS_NOTNULL, OSS_MAIL_ADDR)), false); #15
test(ossim_valid('', array(OSS_NULLABLE, OSS_MAIL_ADDR)), true); #16
test(ossim_valid('', OSS_LETTER), false); #17
test(ossim_valid('ho la', OSS_LETTER, OSS_SCORE), false); #18
test(ossim_valid('', OSS_NULLABLE, OSS_IP_ADDR), true); #19
test(ossim_valid('', OSS_IP_ADDR, OSS_NULLABLE), true); #20
test(ossim_valid('', OSS_IP_ADDR), false); #21
test(ossim_valid('123.456.2.3', OSS_IP_ADDR), false); #22
test(ossim_valid('123.220.44.0', OSS_IP_ADDR), true); #23

test(ossim_valid('foo', 'func:no_exists'), true); #should die
*/

function ossim_valid() 
{
	require_once 'classes/Util.inc';
    
    static $cons = FALSE;
    
    if (func_num_args() < 2) 
    {
        die('Wrong number of params, usage: ' . __FUNCTION__ . '($var, $rules)');
    }
    
    if (!$cons)
    { 
		$cons = get_defined_constants();
	}
    
	$parms = func_get_args();
    $v_var = $parms[0]; // the var to be validated
                
	if (is_object($v_var)) 
	{
        return ossim_set_error(_('Wrong datatype passed, expected string'));
    }
	elseif (!is_array($v_var))
	{
		$subject[0] = $v_var;
	}
	else
	{
		$subject = $v_var;
	}
			
	array_shift($parms);
	
	
    // array of params instead of list of params
    // ej: ossim_valid($foo, array(OSS_DIGIT, OSS_FOO))
    if (func_num_args() == 2 && is_array($parms[0])) 
    {
        $parms = $parms[0];
    }
    
    $error_msg   = OSS_ERROR;
    $val_notnull = TRUE; // by default do not allow empty vars
    $count       = count($parms);
					
    for ($i = 0; $i < $count; $i++) 
    {
        //
        // error:
        //      
        
        if (preg_match('/^error:(.+)$/', $parms[$i], $m)) 
		{
            $m[1] = Util::htmlentities($m[1]);
			$m[1] = str_replace("&amp;", "&", $m[1]);
			$error_msg = $m[1];
            unset($parms[$i]);
            //
            // illegal:
            //
        } 
		elseif (preg_match('/^illegal:(.+)$/', $parms[$i], $m)) 
		{
            $m[1] = Util::htmlentities($m[1]);
			$m[1] = str_replace("&amp;", "&", $m[1]);
			$error_msg = sprintf(_("Error in the '%s' field") , $m[1]);
            unset($parms[$i]);
            //
            // rule: (NULL permitted or not)
            //
        } 
		elseif (preg_match('/^rule:(.+)$/', $parms[$i], $m)) 
		{
            if ($m[1] == 'OSS_NOTNULL') 
            {
                $val_notnull = TRUE;
            } 
			elseif ($m[1] == 'OSS_NULLABLE') 
			{
                $val_notnull = FALSE;
            }
            
            unset($parms[$i]);
        }
    }
	
	if (empty($subject))
	{
		$subject[0] = array();
	}
		
	$pass_field = FALSE;
	
	if (preg_match('/Password/i', $error_msg) || preg_match('/_key/i', $error_msg) || preg_match('/_pass/i', $error_msg))
	{
		$pass_field = TRUE;
	}	
	
	foreach ($subject as $k => $v)
	{	   	
	   	if (is_object($v) || is_array($v))
		{
            return ossim_set_error('Wrong datatype passed, expected string');
		}
					
		//
		// NULL check
		//
		if ($v === NULL || $v === '') 
		{
			if ($val_notnull) 
			{
				return ossim_set_error("$error_msg (" . _('missing required field') . ')');
			} 
			else 
			{
				continue; //If the val is null and it is allowed then we continue validating
			}
		}
		
			
		$v = (mb_detect_encoding($v.' ','UTF-8,ISO-8859-1') == 'UTF-8') ? mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8') : $v ;
		
		$val_str         = '';
		$oss_echars      = FALSE;
		$no_echars       = FALSE;
		$echars          = get_echars($v);
		$exists_echars   = (is_array($echars) && !empty($echars)) ? TRUE : FALSE;

		foreach($parms as $p) 
		{
			//
			// func:
			//
			if (preg_match('/^func:(.+)$/', $p, $m)) 
			{
				$func = $m[1];
				
				if (strpos($func, '::') !== FALSE) 
				{ 
				    // static Class::method()
					$func = explode('::', $func);
				}
				
				if (!is_callable($func)) 
				{
					die("The validation function '$func' doesn't exist");
				}
				
				if (!call_user_func($func, $v)) 
				{
					return FALSE;
				}
				
				continue;
			}
			//
			// regex:
			//
			if (preg_match('/^regex:(.+)$/', $p, $m)) 
			{				
				if (!preg_match('/^' . $m[1] . '$/', $v)) 
				{
					$v     = Util::htmlentities($v);
					$e_msg = ($pass_field == TRUE) ? $error_msg . ' (the introduced string is not in a valid format)' : $error_msg . " (the string '<strong>$v</strong>' is not in a valid format)";
					
					
					return ossim_set_error($e_msg);
				}
				
				continue;
			}
			//
			// OSS_* constants
			//
			
			if ($p == OSS_NOECHARS)
			{
				$no_echars = TRUE;
			}
			else
			{
				$val_str.= $p;
			}
		}
				
				
		if (stripos($val_str, OSS_LETTER) !== FALSE)
		{
			$oss_echars = TRUE;
		}
		
		$clean_v = $v;
		
		if ($exists_echars == TRUE && $val_str)
		{
			if ($oss_echars == FALSE)
			{
				$e_msg = ($pass_field == TRUE) ? $error_msg : $error_msg. '<br/>String: <strong>' . sprintf(_("\"%s\"")."</strong> "._("not allowed."), $v);
				
				return ossim_set_error($e_msg);
			}
			else
			{
				if ($no_echars == TRUE)
				{
					$e_msg = ($pass_field == TRUE) ? $error_msg : $error_msg. '<br/>String: <strong>' . sprintf(_("\"%s\"")."</strong> "._("not allowed."), $v);
					return ossim_set_error($e_msg);
				}
				else
				{
					foreach ($echars as $k => $char)
					{
						if ($char < MIN_ECHAR_VALUE)
						{
							$e_msg  = $error_msg. " " . sprintf(_("(\"%s\" not allowed)") , "&#".$char.";"); 
							$e_msg .= ($pass_field == TRUE) ? '' : "<br/>Introduced string: '<strong>$v</strong>'";
							
							return ossim_set_error($e_msg);
						}
					}
					
					$clean_v = clean_echars($v);
				}
			}
		}

		if (!empty($val_str) && @preg_match("/[^$val_str]/", $clean_v, $m))
		{
			$not = $m[0];
			if ($not == ' ')
			{ 
                $not = _('spaces');
			}
			
			if ($not == "\r" || $not == "\n") 
			{
			    $not = _('breaklines');
            }
            
            if ($not == "\t") 
            {
                $not = _('tabulator');
			}
			
			$v   = Util::htmlentities($v);
			$not = Util::htmlentities($not);
			
			
			if ($pass_field == TRUE)
			{
				return ossim_set_error($error_msg. " " . sprintf(_("(\"%s\" not allowed).") , $not)); 
			}
			else
			{ 
				return ossim_set_error($error_msg. " " . sprintf(_("(\"%s\" not allowed)") , $not) . ".<br/>Introduced string: '<strong>$v</strong>'");
			}                  			
		}
	}
	
	/*
	echo "<pre>";
	print_r(preg_match("/[^$val_str]/", $clean_v, $m));
	echo "<br/>";
	print_r($val_str."<=>".$clean_v);
	echo "</pre>";
	*/
	
    return TRUE;
}


function ossim_set_error($error) 
{
    $GLOBALS['ossim_last_error'] = $error;
    
    return FALSE;
}


function ossim_clean_error() 
{
    $GLOBALS['ossim_last_error'] = FALSE;
    
    return TRUE;
}


function ossim_get_error() 
{
    return $GLOBALS['ossim_last_error'];
}


function ossim_get_error_clean() 
{
    $error     = $GLOBALS['ossim_last_error'];
	$normalize = explode("<br/>", $error);
	
	if (count($normalize) == 2)
    {
		$error = trim($normalize[0]);
        
        if (substr($error, -1) != '.'){
            $error .= '.';
        }
		
        $error .= "&nbsp;&nbsp;".$normalize[1];
    }
		
	return $error;
}


function ossim_error($error_msg = NULL, $error_type = AV_ERROR, $styles = 'width: 90%; text-align:left; margin:auto;') 
{        
    $error_msg  = ($error_msg !== NULL) ? $error_msg : $GLOBALS['ossim_last_error'];
	
	if (!$error_msg) 
	{
        return FALSE;
    }
        
    switch ($error_type)
	{
		case AV_ERROR:
			$ossim_eclass = new Av_error($error_msg, NULL, 'noback');
		break;
		
		case AV_WARNING:
			$ossim_eclass = new Av_warning($error_msg, NULL, 'noback');
		break;
		
		case AV_INFO:
			$ossim_eclass = new Av_notice($error_msg, NULL, 'noback');
		break;
		
		default:
			$ossim_eclass = new Av_error($error_msg, NULL, 'noback');
	}
	
	return $ossim_eclass->get_formatted_message($styles);
}


function validate_form_fields($type, $validate)
{
		
	$validation_errors = array();
	
	$validate_rules    = array();
	
	$values            = array();
		
	if ($type == 'POST')
	{
		$validate_rules = $validate;
				
		foreach ($_POST as $k => $v)
		{
			if (!is_array($v))
			{
				$values[$k]= (mb_detect_encoding($v." ",'UTF-8,ISO-8859-1') == 'UTF-8')  ? Util::utf8entities(POST($k)) : POST($k);
			}
			else
			{
				$aux = array();
				foreach ($v as $i => $j){
					$aux[$i] = (mb_detect_encoding($v." ",'UTF-8,ISO-8859-1') == 'UTF-8')  ? Util::utf8entities($j) : $j;
				}
				
				//Special case: Arrays
				$name = $k.'[]';
				$values[$name] = $aux;
			}
		}
	}
	else if ($type == 'GET')
	{
		$k = $_GET['name'];
		
		if (isset($validate[$k]))
		{
			$validate_rules[$k] = $validate[$k];
			
			//Special case: Arrays
			$name = str_replace('[]', '', $_GET['name']);
			
			if (!is_array($_GET[$name]))
			{
				$values[$k]= (mb_detect_encoding($v.' ','UTF-8,ISO-8859-1') == 'UTF-8')  ? Util::utf8entities(GET($name)) : GET($name);
			}
			else
			{
				$aux = array();
				foreach ($_GET[$name] as $i => $j)
				{
					$aux[$i] = (mb_detect_encoding($v.' ','UTF-8,ISO-8859-1') == 'UTF-8')  ? Util::utf8entities($j) : $j;
				}
				
				$values[$k] = $aux;
			}
		}
	}
	else
	{
		// Special Case: Invalid Send Method
		$validation_errors['invalid_sm'] = _('Invalid send method');
		return $validation_errors;
	}
		
	foreach ($validate_rules as $k => $v)
	{
		if (!isset($values[$k]))
		{
			$value = NULL;
		}
		else
		{
			$value = $values[$k];
		}
		
		eval("ossim_valid(\$value, ".$validate_rules[$k]['validation'].", '".$validate_rules[$k]['e_message']."');");

		if ($GLOBALS['ossim_last_error'] !== FALSE)
		{
			$validation_errors[$k] = $GLOBALS['ossim_last_error'];
			
			ossim_clean_error();
		}
	}
		
	return $validation_errors;
}


function valid_user($user)
{
    $valid[0] = OSS_LETTER;
    $valid[1] = OSS_DIGIT;
    $valid[2] = OSS_SCORE;
    $valid[3] = OSS_DOT;

    $pattern = "/[^".implode("", $valid)."]/";
		
    if (preg_match_all($pattern, $user, $match))
    {
		ossim_set_error(_('Invalid login').'<br/>'. _('Entered login').": '<strong>".Util::htmlentities($user)."</strong>'");
		
		return FALSE;
    }

    $pattern = "/^[0-9]*$/";
    if (preg_match($pattern, $user, $match))
    {
       	ossim_set_error(_('Invalid login').'<br/>'._('Login can not be just numerical').": '<strong>".Util::htmlentities($user)."</strong>'");
       	
		return FALSE;
    }
	
	return TRUE;
    
    //define('OSS_USER', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT); // for validating logins/users
}


// CIDR 0.0.0.0/xx allowed
function valid_cidr($cidrs)
{
	$valid_sep   = array (',', '\n', ' ');
	$current_sep = '';
	
	foreach ($valid_sep as $v_sep)
	{
		if (preg_match("/$v_sep/", $cidrs))
		{
			$current_sep = $v_sep;

			break;
		}
	}
	
	$array_cidr = ($current_sep == '') ? array($cidrs) : explode($current_sep, $cidrs);
	
	foreach ($array_cidr as $v)
	{
		$v = trim($v);
		
		if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([1-9]|[1-2][0-9]|3[0-2])$/', $v))
		{ 
			continue;
		}
		else
		{
			ossim_set_error(_('Invalid IP/Mask address. Format allowed').': nnn.nnn.nnn.nnn/nn <br/>'. _('Entered CIDR'). ": '<strong>".Util::htmlentities($v)."</strong>'");

			return FALSE;
		}
	}
	
	return TRUE;
}

function append_protocol(&$item, $key, $protocol)
{
    $item = $item . ' - ' . $protocol;
}
	
function valid_port($ports)
{
    $ports_array = array();
    
    if (preg_match('/(\d+)-(\d+)\s*\-\s*(\w+)/', $ports, $found))
    {
         $ports = range($found[1], $found[2]);
         array_walk($ports, 'append_protocol', $found[3]);
    }

    if(!is_array($ports))
    {
        $ports_array[] = $ports;
	}
    else
    {
        $ports_array   = $ports;
    }

    foreach ($ports_array as $port)
    {
        $valid_protocol = array ('tcp', 'udp', 'icmp');
        $valid_sep      = array ('-', '_', '#');
        $current_sep    = '';
        
        foreach ($valid_sep as $v_sep)
        {
            if (preg_match("/$v_sep/", $port))
            {
                $current_sep = $v_sep;

                break;
            }
        }
        
        if (empty($current_sep))
        {
            $port = trim($port);
            
            if (!(preg_match('/^(0|[1-9][0-9]*)$/', $port) && $port >= 0 && $port <= 65535)) 
            {               
                ossim_set_error(_("Port number isn't valid").'<br/>'._('Entered port').": '<strong>".Util::htmlentities($port)."</strong>'");
                
                return FALSE;
            }
        
        }
        else
        {
            $pair_pp  = explode($current_sep, $port);
            $port     = trim($pair_pp[0]);
            $protocol = trim(strtolower($pair_pp[1]));
            
            if (!(preg_match('/^(0|[1-9][0-9]*)$/', $port) && $port >= 0 && $port <= 65535) && (in_array($protocol, $valid_protocol))) 
            {               
                ossim_set_error(_("Pair port - protocol isn't valid").'<br/>'._('Entered pair').": '<strong>".Util::htmlentities("$port-$protocol")."</strong>'");
                
                return FALSE;
            }
        }
    }
    
    return TRUE;
}


function valid_cclass($cclass)
{
    $pattern = '/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){2}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';
        
    if (preg_match($pattern, $cclass))
	{
		return TRUE;
	}
	
	ossim_set_error(_('Invalid C-class. Format allowed : nnn.nnn.nnn').': <br/>'. _('Entered C-class'). ": '<strong>".Util::htmlentities($cclass)."</strong>'");
	
	return FALSE;
}



function valid_ip($ip)
{
	if (!preg_match('/,/', $ip))
	{
		if ($ip != '0.0.0.0' && $ip != '255.255.255.255' && preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
		{
			return TRUE;
		}
		
		ossim_set_error(_('Invalid IP address. Format allowed').': nnn.nnn.nnn.nnn <br/>'. _('Entered IP'). ": '<strong>".Util::htmlentities($ip)."</strong>'");
		
		return FALSE;
	}
	else
	{
		$aux_ips = explode(',', $ip);

		foreach ($aux_ips as $ip)
		{
			if ($ip != '0.0.0.0' && $ip != '255.255.255.255' && preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
			{
				continue;
			}
			else
			{
				ossim_set_error(_('Invalid IP address. Format allowed').': nnn.nnn.nnn.nnn <br/>'. _('Entered IP'). ": '<strong>".Util::htmlentities($ip)."</strong>'");
				
				return FALSE;
			}
		}

		return TRUE;
	}
}


// Address 0.0.0.0 allowed
function valid_ip_2($ip)
{
	if (!preg_match('/,/', $ip))
	{
		if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
		{
			return TRUE;
		}
		
		ossim_set_error(_('Invalid IP address. Format allowed').': nnn.nnn.nnn.nnn <br/>'. _('Entered IP'). ": '<strong>".Util::htmlentities($ip)."</strong>'");
		
		return FALSE;
	}
	else
	{
		$aux_ips = explode(',', $ip);

		foreach ($aux_ips as $ip)
		{
			if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
			{
				continue;
			}
			else
			{
				ossim_set_error(_('Invalid IP address. Format allowed').': nnn.nnn.nnn.nnn <br/>'._('Entered IP').": '<strong>".Util::htmlentities($ip)."</strong>'");
				
				return FALSE;
			}
		}

		return TRUE;
	}
}


// CIDR 0.0.0.0/xx allowed
function valid_ipcidr($ip) 
{   	
	if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([0-9]|[1-2][0-9]|3[0-2])$/', $ip))
	{
        return TRUE;
    }
    else if ($ip != '0.0.0.0' && preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
    {
        return TRUE;
    }
    
    ossim_set_error(_('Invalid IP/CIDR address. Format allowed').': nnn.nnn.nnn.nnn/nn?<br/>'._('Entered IP/CIDR').": '<strong>".Util::htmlentities($ip)."</strong>'");
    
    return FALSE;
}


// IP address 0.0.0.0 and CIDR 0.0.0.0/xx allowed
function valid_ipcidr_2($ip) 
{   	
	if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([0-9]|[1-2][0-9]|3[0-2])$/', $ip))
	{
        return TRUE;
    }
    elseif (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
    {
        return TRUE;
    }
    
    ossim_set_error(_('Invalid IP/CIDR address. Format allowed').': nnn.nnn.nnn.nnn/nn?<br/>'._('Entered IP/CIDR').": '<strong>".Util::htmlentities($ip)."</strong>'");
    
    return FALSE;
}


// IP address 0.0.0.0 and CIDR 0.0.0.0/xx allowed, included 0.0.0.0/0
function valid_ipcidr_00($ip) 
{   	
    if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([0-9]|[1-2][0-9]|3[0-2])$/', $ip))
    {
        return TRUE;
    }
    elseif (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip))
    {
        return TRUE;
    }
    
    ossim_set_error(_('Invalid IP/CIDR address. Format allowed').': nnn.nnn.nnn.nnn/nn?<br/>'._('Entered IP/CIDR').": '<strong>".Util::htmlentities($ip)."</strong>'");
    
    return FALSE;
}


// IP address 0.0.0.0. and CIDR 0.0.0.0/xx allowed
function valid_ipcidr_3($ip) 
{    
	$ok        = TRUE;
    $ips_cidrs = explode(',', $ip);
    
	foreach ($ips_cidrs as $data) 
	{
		if ($ok)
		{
			if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([0-9]|[1-2][0-9]|3[0-2])$/', trim($data)))
			{
				continue;
			}
			elseif (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', trim($data)))
			{
				continue;
			}
			else
			{
				$ok = FALSE;
			}
		}
	}
    
    if(!$ok) 
	{
        ossim_set_error(_('Invalid IP/CIDR address. Format allowed').': nnn.nnn.nnn.nnn/nn? <br/>'. _('Entered IP/CIDR'). ": '<strong>".Util::htmlentities($ip)."</strong>'");
        
        return FALSE;
    }
    else
    {
        return TRUE;
    }
}


function valid_email($email)
{
    $emails = array();
    
    if (!is_array($email))
    {
        $emails = explode(";", $email);
    }
    else
    {
        $emails = $email;
    }
    
    foreach($emails as $data) 
	{
        if (!preg_match('/^[a-zA-Z0-9_\-\.+]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z]+$/', $data)) 
		{
            ossim_set_error(_('Invalid email.').'<br/>'._('Entered email').": '<strong>".Util::htmlentities($data)."</strong>'");
            
            return FALSE;
        }
    }
	
    return TRUE;
}


function valid_mac($mac)
{
	if (preg_match('/^[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}$/i',$mac))
	{
		return TRUE;
	}
		
	ossim_set_error(_('Invalid MAC address. Format allowed'). ': nn:nn:nn:nn:nn:nn, n=[a-f0-9] <br/>'. _('Entered MAC'). ": '<strong>".Util::htmlentities($mac)."</strong>'");
    
    return FALSE;
}


function valid_hostname($hostname)
{
    if (preg_match('/^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$/',$hostname) && strlen($hostname) <= 63)
    {
   		return TRUE;
    }
		
    if(strlen($hostname) > 63)
	{
        $hostname = Util::htmlentities($hostname);
		
		ossim_set_error(_('Hostname too long. <br/>Entered hostname').": '<strong>".$hostname."</strong>'");
	}
    else
	{
       	$hostname = (empty($hostname)) ? '<i>('._("empty hostname").')</i>' : Util::htmlentities($hostname);
		
		ossim_set_error(_('Invalid hostname. <br/>Entered hostname').": '<strong>".$hostname."'</strong>");
    }
    
    return FALSE;
}

function valid_fqdns($fqdns)
{
    $fqdns       = trim($fqdns);
    $array_fqdns = (empty($fqdns)) ? array() :  explode(",", $fqdns);
    
    foreach ($array_fqdns as $v)
    {
        $v =  trim($v);
        
        if(strlen($v) > 255) 
        {
            $max = 30;
            $v   = substr ($v, 0, $max)."[...]";
            
            ossim_set_error(_('Invalid FQDN/Alias').": ".Util::htmlentities($v).'. '._("It's too long"));
            
            return FALSE;
        }
        else 
        {
            if (!preg_match('/\./',$v))
            {
                if (!preg_match('/^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$/', $v))
                {
                    $max = 40;
                    $v = (strlen($v) > $max) ? substr ($v, 0, $max)."[...]" : $v;
					$v = (empty($v)) ? '<i>('._('empty fqdn').')</i>' : Util::htmlentities($v);
				    
				    ossim_set_error(_('Invalid FQDN/Alias').': '.$v);
                    
                    return FALSE;
                }
            }
            else
            {
                //Remove domain root (.)
                if (substr($v, -1) == '.')
                {
                    $v = substr($v, 0, -1);
                }
                
                $labels = explode('.', $v);
                                      
                foreach($labels as $label) 
                {
                    // Validate FQDN label
                    if (preg_match('/^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$/', $label)) 
                    {
                        if(strlen($label) > 63) 
                        {
                            $max = 30;
                            $label = substr ($label, 0, $max).'[...]';
							ossim_set_error(_('Invalid label').': '.Util::htmlentities($label).'. '._("It's too long"));
                            
                            return FALSE;
                        }
                    }
                    else
                    {
                        $max = 50;
                        $label = (strlen($label) > $max) ? substr ($label, 0, $max)."[...]" : $label;
						$label = (empty($label)) ? '<i>('._('empty string').')</i>' : $label;
                        
                        ossim_set_error(_('Invalid format label').': '.Util::htmlentities($label));
                        
                        return FALSE;
                    }                    
                }
            }
        }
    }
    
    return TRUE;
}


function valid_url($url)
{
	// Scheme
	//$pattern = "/^((https?|ftp)\:\/\/)?";
	$pattern = "/^((https?)\:\/\/)?";

	// User and pass (optional)
	$pattern .= "([a-zA-Z0-9+!*(),;?&=\$_.-]+(\:[a-zA-Z0-9+!*(),;?&=\$_.-]+)?@)?";

	// Hostname or ip
	$pattern .= "[a-zA-Z0-9+\$_-]+(\.[a-zA-Z0-9+\$_-]+)*"; // http://x = allowed (ex. http://localhost, http://routerlogin)
	//use only one of the above

	// Port (optional)
	$pattern .= "(\:[0-9]{2,5})?";
	// Path (optional)
	$pattern .= "(\/([a-zA-Z0-9+\$_~-]\.?)+)*\/?";
	// GET Query (optional)
	$pattern .= "(\?[a-zA-Z\+\&\$_.-][a-zA-Z0-9;:@\/\&%=\+\$_.-]*)?";
	// Anchor (optional)
	$pattern .= "(#[a-zA-Z_.-][a-zA-Z0-9+\$_.-]*)?$/";
	
	if (preg_match($pattern, $url))
	{
		return TRUE;
	}
	
	ossim_set_error(_('Invalid URL. Format allowed').': <http[s]|ftp> :// [user[:pass]@] hostname [port] [/path] [?getquery] [anchor]<br/>'. _('Entered URL').": '<strong>".Util::htmlentities($url)."</strong>'");
    
    return FALSE;
}


function valid_filename($filename)
{
    $pattern = "/^\.?\.?(\/([a-z0-9+\$_-]\.?)+)*\/?[-\w\.]+$/i";

    if (preg_match($pattern, $filename)) 
    {
        return TRUE;
    }
	
    ossim_set_error(_('Invalid filename.').'<br/>'._('Entered file').": '<strong>".Util::htmlentities($filename)."</strong>'");
    
    return FALSE;
}


function valid_quotes($value) 
{
    if (!preg_match('/\'|\"/', $value)) 
    {
        return TRUE;
    }
    
	ossim_set_error(_("Invalid Quotes. Don't use quotes ' or \""));
   
    return FALSE;
}

/**
 * This function is an UNION between valid_datetime and valid_date
 * 
 * @param string $value
 * @return boolean
 */
function valid_datetime_date($value)
{
    // valid_datetime cloned
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $value, $m))
    {
        $year    = $m[1];
        $month   = $m[2];
        $day     = $m[3];
        $hour    = $m[4];
        $minutes = $m[5];
        $seconds = $m[6];
    
        $cond_1 = (checkdate($month, $day, $year) === TRUE);
        $cond_2 = (($hour >= 0 && $hour <=23) && ($minutes >= 0 && $minutes <=59) && ($seconds >= 0 && $seconds <=59));
    
        if ($cond_1 && $cond_2)
        {
            return TRUE;
        }
    }
    
    // valid_date cloned
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m))
    {
        $year    = $m[1];
        $month   = $m[2];
        $day     = $m[3];
        	
        $cond_1 = (checkdate($month, $day, $year) === TRUE);
    
        if ($cond_1)
        {
            return TRUE;
        }
    }
    
    ossim_set_error(_('Invalid Date-time or Date. Format allowed').': YYYY-MM-DD HH:MM:SS or YYYY-MM-DD<br/>'._('Entered date').": '<strong>".Util::htmlentities($value)."</strong>'");
    
    return FALSE;
}


function valid_datetime($value)
{
	if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $value, $m))
	{
		$year    = $m[1];
		$month   = $m[2];
		$day     = $m[3];
		$hour    = $m[4];
		$minutes = $m[5];
		$seconds = $m[6];
		
		$cond_1 = (checkdate($month, $day, $year) === TRUE);
		$cond_2 = (($hour >= 0 && $hour <=23) && ($minutes >= 0 && $minutes <=59) && ($seconds >= 0 && $seconds <=59));
		
		if ($cond_1 && $cond_2)
		{
			return TRUE;
		}
	}
	
	ossim_set_error(_('Invalid Date-time. Format allowed').': YYYY-MM-DD HH:MM:SS <br/>'._('Entered date').": '<strong>".Util::htmlentities($value)."</strong>'");
	
	return FALSE;
}


function valid_date($value)
{
	if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m))
	{
		$year    = $m[1];
		$month   = $m[2];
		$day     = $m[3];
			
		$cond_1 = (checkdate($month, $day, $year) === TRUE);
				
		if ($cond_1)
		{
			return TRUE;
		}
	}

	ossim_set_error(_('Invalid Date. Format allowed').': YYYY-MM-DD <br/>'._('Entered date').": '<strong>".Util::htmlentities($value)."</strong>'");
	
	return FALSE;
}


function valid_tzone($value)
{	
	$tzlist = timezone_identifiers_list(4095);
	
	foreach($tzlist as $tz)
	{
		if ($value == $tz)
		{
			return TRUE;
		}
	}
	
	ossim_set_error(_('Invalid Time Zone').'.<br/>'._('Entered Time Zone').": '<strong>".Util::htmlentities($value)."</strong>'");
	
	return FALSE;
}


function valid_weekday($value)
{
	$weekdays = array (
	   'Sunday',
    	'Monday',
    	'Tuesday',
    	'Wednesday',
    	'Thursday',
    	'Friday',
    	'Saturday'
	);
	
	if (!in_array($value, $weekdays))
	{
		ossim_set_error(_('Invalid weekday').'.<br/>'._('Entered day').": '<strong>".Util::htmlentities($value)."</strong>'");
		
		return FALSE;
	}
	
	return TRUE;
}


function is_ip($value) 
{
    if (preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/',$value)) 
    { 
        return TRUE;
    }
    
	return FALSE;
}


function is_cidr($value) 
{
    if (preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/([1-9]|[1-2]\d|3[0-2]))$/',$value)) 
    { 
        return TRUE;
    }
    
	return FALSE;
}


function valid_fqdn_or_ip($value)
{
	if (valid_ip($value) || valid_fqdns($value))
	{
		ossim_clean_error();
		
		return TRUE;
	}
	
	ossim_set_error(_('Invalid FQDN/IP').'.<br/>'._('Entered value').": '<strong>".Util::htmlentities($value)."</strong>'");
	
	return FALSE;
}


function valid_smtp_server_address($value) 
{
	$data = parse_url($value);

	if($data['scheme'] == 'ssl') // For example: ssl://smtp.gmail.com
	{ 
		return valid_fqdn_or_ip($data['host']);
	}
	else if($data['scheme'] == '') // For example: smtp.gmail.com OR 173.194.66.109
	{
	   return valid_fqdn_or_ip($data['path']);
	}
	else
	{
		ossim_set_error(_('Invalid Server').'.<br/>'._('Entered value').": '<strong>".Util::htmlentities($data['scheme'])."</strong>'");
		
		return FALSE;
	}
}


/* Test perms in sensor_ip */
function validate_sensor_perms($conn, $sensor_ip, $args = '')
{
    $allowed = explode(',', Session::allowedSensors());
	$query   = ossim_query("SELECT sensor.*, HEX(sensor.id) as id, INET6_NTOP(ip) AS ip FROM sensor $args");
	
	$rs = $conn->Execute($query);

	if (!$rs)
	{
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
    } 
	else
	{
        while (!$rs->EOF)
		{
            $ip = $rs->fields['ip'];
            
			if ((Session::allowedSensors() == '' || in_array($ip, $allowed)) && $sensor_ip == $ip)
			{
               return TRUE;
            }

			$rs->MoveNext();
        }
    }
	
    return FALSE;
}


function valid_echars($value)
{
	if (preg_match_all('/&#(\d{4,5});/', $value, $e_chars))
	{
		foreach ($e_chars[1] as $e_char)
		{
			if ($e_char < 10000)
			{
				ossim_set_error(_('Invalid characters').'<br/>'._('Entered characters').": '<strong>".Util::htmlentities($value)."</strong>'");
			}
				
			$text = str_replace("&#".$e_char.';', '', $value);
		}
	}
	else
	{
		$text = $value;
	}	
		
	if ($text != '')
	{
		$latin_text[0] = "A-Za-z";
		$latin_text[1] = "·ÈÌÛ˙˝‡ËÏÚ˘‰ÎÔˆ¸ˇ‚ÍÓÙ˚„Òı®ÂÊÁΩ¯˛ﬂ";
		$latin_text[2] = "¡…Õ”⁄›¿»Ã“ŸƒÀœ÷‹æ¬ Œ‘€√—’¶≈∆«º–ÿﬁ";
		$latin_text[3] = "0-9";
		
		$pattern = "/[^".implode('', $latin_text)."]/";
		
		if (preg_match_all($pattern, $text))
		{
			ossim_set_error(_('Invalid characters').'<br/>'._('Entered characters').": '<strong>".Util::htmlentities($text)."</strong>'");
		}
	}

	return TRUE;
}


function check_uniqueid($unique_id1, $unique_id2)
{	
	if ($unique_id1 != '' && $unique_id2 != '' && $unique_id1 == $unique_id2) 
	{
		return 1;
	} 
	else
	{
		return 0;
	}
}


//Check security in passwords
function checkpass($conn, $current_pass, $pass1, $pass2, $user)
{
	require_once "ossim_db.inc";
	require_once "ossim_conf.inc";
	
	$conf         = $GLOBALS['CONF'];
	$conf         = (!$conf) ? new Ossim_conf() : $conf;
	$info_error   = TRUE;
	$error        = FALSE;
		
	$recent_pass = Log_action::get_last_pass($conn, $user);	
	
	$parameters['current_pass'] = $current_pass;
	$parameters['pass1'] 	    = $pass1;
	$parameters['pass2'] 	    = $pass2;
	
	
	$validate  = array (
		'current_pass'   => array('validation' => 'OSS_PASSWORD'  ,'e_message' => 'illegal:' . _('Current pass')),
		'pass1'          => array('validation' => 'OSS_PASSWORD'  ,'e_message' => 'illegal:' . _('New password')),
		'pass2'          => array('validation' => 'OSS_PASSWORD'  ,'e_message' => 'illegal:' . _('Rewrite password'))
	);


	foreach ($parameters as $k => $v)
	{
		eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
	
		if (ossim_error())
		{
			$info_error[] = ossim_get_error();
			$error        = TRUE;
			
			ossim_clean_error();
		}
	}

	if ($error == TRUE)
	{
		return $info_error;
	}
	
	$pass_length_min = ($conf->get_conf('pass_length_min')) ? $conf->get_conf('pass_length_min') : 7;
	$pass_length_max = ($conf->get_conf('pass_length_max')) ? $conf->get_conf('pass_length_max') : 255;
	
	
	if (empty($current_pass))
	{
		return array(_('Current password is empty'));
	}
	
	if (strcmp($pass1, $pass2) != '0')
	{ 
		return array(_('Passwords mismatches'));
	}
	
	if (strlen($pass1) < $pass_length_min)
	{ 
		return array(_('Password is long enough. The minimum is').' '.$pass_length_min.' '._('characters'));
	}
	
	if (strlen($pass1) > $pass_length_max)
	{ 
		return array(_('Password is too long. The maximum is').' '.$pass_length_max.' '._('characters'));
	}
	
	if (!Session::pass_check_complexity($pass1))
	{ 
		return array(_('Password is not complex enough'));
	}
	
	if (count($recent_pass) > 0 && (in_array(md5($pass1),$recent_pass) || in_array(hash('sha256', $pass1),$recent_pass)))
	{ 
		return array(_('This password is recently used. Try another'));	
	}
	
	$user_list = Session::get_list($conn, "WHERE login = '".$user."' AND (pass = '".md5($pass1)."' OR pass = '".hash('sha256', $pass1)."')");
	
	if (count($user_list) > 0)
	{ 
		return array(_('You must change your old password'));
	}	
	
	return TRUE;  
}


//Check hexadecimal string
function valid_hex32($value, $canonical = FALSE) 
{    
    if($value == '')
    {
        return FALSE;
    }

    if($canonical)
    {
        $regex = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i";
    }
    else
    {
        $regex = "/^[a-f0-9]{32}$/i";
    }

    if(preg_match($regex, $value))
    {
        return TRUE;
    }
    
    return FALSE;
}

//Check uuid
function valid_uuid($value) 
{    
    $regex = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i";

    if(preg_match($regex, $value))
    {
        return TRUE;
    }
    
    ossim_set_error(_("UUID isn't valid").'<br/>'._('Entered UUID').": '<strong>".Util::htmlentities("$value")."</strong>'");
    
    return FALSE;
}


function valid_support_ticket_number($value) 
{    
    $regex = "/^\d{8}$/i";

    if(preg_match($regex, $value))
    {
        return TRUE;
    }
    
    ossim_set_error(_("Invalid ticket number").". <br/>"._('Entered value').": '<strong>".Util::htmlentities("$value")."</strong>' "._("is not an 8-digit number"));
     
    return FALSE;
}


/* End of file Security.inc */
/* Location: ../include/classes/Security.inc */