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
* - valid_usm_user($user)
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
* - check_pass($conn, $user, $c_pass, $pass_1, $pass_2)
* - create_valid_hostname($hostname)
* - valid_hex32($value)
*/

require_once __DIR__ . '/security_class.inc';
require_once __DIR__ . '/Util.inc';

//Remove special chars (chinese characters)
function clean_echars($data)
{
    return security_class::clean_echars($data);
}

function get_echars($data)
{
    return security_class::get_echars($data);
}

function ids_callback(&$val, $key)
{
    return security_class::ids_callback($val, $key);
}

function ids()
{
    require_once 'IDS/Log/Composite.php';
    require_once 'IDS/Log/File.php';

    $general_data = [$_GET, $_POST, $_FILES];
    array_walk_recursive($general_data, 'security_class::ids_callback');
    $request = array('GET' => $general_data[0], 'POST' => $general_data[1], 'FILES' => $general_data[2]);

    $init = IDS_Init::init('/usr/share/ossim/include/php-ids.ini');
    $init->config['General']['scan_keys'] = TRUE;

    $ids = new IDS_Monitor($request, $init);
    $ids = $ids->run();
    $compositeLog = new IDS_Log_Composite();

    $init = IDS_Log_File::getInstance($init);
    $ret = security_class::ids($init, $ids, $compositeLog);
    if (!$ret){
        echo $ret;
        exit();
    }
    return true;
}

/*
* Scan all HTTP vars disallowing common exploits like XSS
*/
$_html_vars_checked = TRUE;
$return = security_class::disallowingCommonExploits([$_GET, $_POST], $_html_vars_checked);
if($return !== TRUE)
{
    echo $return;
    die;
}

/*
* Safe html escape. Always use that for printing vars comming
* from outside ($_GET, $_POST, databases, etc)
*/
function htm($var)
{
	return security_class::htm($var);
}

function strip($var)
{
    return security_class::strip($var);
}

function strip_map($value)
{
    return security_class::strip_map($value);
}

function required()
{
    return security_class::required();
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
    return security_class::GET($param);
}

/*
* @see GET()
*/
function POST($param)
{
    return security_class::POST($param);
}

/*
* @see GET()
*/
function REQUEST($param)
{
    return security_class::REQUEST($param);
}

function ossim_valid()
{
    $parms = func_get_args();

	return security_class::ossim_valid(...$parms);
}

function ossim_set_error($error)
{
    return security_class::ossim_set_error($error);
}

function ossim_clean_error()
{
    return security_class::ossim_clean_error();
}

function ossim_get_error()
{
    return security_class::ossim_get_error();
}

function ossim_get_error_clean()
{
    return security_class::ossim_get_error_clean();
}

function ossim_error($error_msg = NULL, $error_type = AV_ERROR, $styles = 'width: 90%; text-align:left; margin:auto;')
{
    return security_class::ossim_error($error_msg, $error_type, $styles);
}

/*
 * small and elegant recursive function
 * to replace ugly and copy pasted validation
 */
function utfize($val) {
	return security_class::utfize($val);
}

function check_last_ossim_error(&$validation_errors) {
	return security_class::check_last_ossim_error($validation_errors);
}

//deprecated old and ugly validation function
//for backward compatibility
function validate_form_fields($type, $validate)
{
	return security_class::validate_form_fields($type, $validate);
}

/**
 * This function validates a user login
 *
 * @param string   $user  User login
 *
 * @return boolean
 */
function valid_usm_user($user)
{
    return security_class::valid_usm_user($user);
}

// CIDR 0.0.0.0/xx allowed
function valid_cidr($cidrs)
{
	return security_class::valid_cidr($cidrs);
}

//unused variable $key is required to specify third parameter here.
//Because this function is called from array_walk
function append_protocol(&$item, $key, $protocol)
{
    return security_class::append_protocol($item, $key, $protocol);
}

function valid_port($ports)
{
    return security_class::valid_port($ports);
}

function valid_cclass($cclass)
{
    return security_class::valid_cclass($cclass);
}

function valid_ip($ip)
{
	return security_class::valid_ip($ip);
}

// Address 0.0.0.0 allowed
function valid_ip_2($ip)
{
	return security_class::valid_ip_2($ip);
}

// CIDR 0.0.0.0/xx allowed
function valid_ipcidr($ip)
{
	return security_class::valid_ipcidr($ip);
}

// IP address 0.0.0.0 and CIDR 0.0.0.0/xx allowed
function valid_ipcidr_2($ip)
{
	return security_class::valid_ipcidr_2($ip);
}

// IP address 0.0.0.0 and CIDR 0.0.0.0/xx allowed, included 0.0.0.0/0
function valid_ipcidr_00($ip)
{
    return security_class::valid_ipcidr_00($ip);
}

// IP address 0.0.0.0. and CIDR 0.0.0.0/xx allowed
function valid_ipcidr_3($ip)
{
	return security_class::valid_ipcidr_3($ip);
}

/**
 * This function validates an email
 *
 * @param string   $email  Email
 *
 * @return boolean
 */
function valid_email($email)
{
    return security_class::valid_email($email);
}

function valid_mac($mac)
{
    return security_class::valid_mac($mac);
}

function valid_hostname($hostname)
{
    return security_class::valid_hostname($hostname);
}

function valid_fqdns($fqdns)
{
    return security_class::valid_fqdns($fqdns);
}

function valid_url($url)
{
	return security_class::valid_url($url);
}

function valid_filename($filename)
{
    return security_class::valid_filename($filename);
}

function valid_quotes($value)
{
    return security_class::valid_quotes($value);
}

/**
 * This function is an UNION between valid_datetime and valid_date
 *
 * @param string $value
 * @return boolean
 */
function valid_datetime_date($value)
{
    return security_class::valid_datetime_date($value);
}

function valid_datetime($value)
{
	return security_class::valid_datetime($value);
}

function valid_date($value)
{
	return security_class::valid_date($value);
}

function valid_tzone($value)
{
	return security_class::valid_tzone($value);
}

function valid_weekday($value)
{
	return security_class::valid_weekday($value);
}

function is_ip($value)
{
    return security_class::is_ip($value);
}

function is_cidr($value)
{
    return security_class::is_cidr($value);
}

function valid_fqdn_or_ip($value)
{
	return security_class::valid_fqdn_or_ip($value);
}

function valid_smtp_server_address($value)
{
	return security_class::valid_smtp_server_address($value);
}

/* Test perms in sensor_ip */
function validate_sensor_perms($conn, $sensor_ip, $args = '')
{
    return security_class::validate_sensor_perms($conn, $sensor_ip, $args);
}

function valid_echars($value)
{
	return security_class::valid_echars($value);
}

function check_uniqueid($unique_id1, $unique_id2)
{
	return security_class::check_uniqueid($unique_id1, $unique_id2);
}

/**
 * Function check_pass
 *
 * This function checks whether a password is valid
 *
 * @param object $conn     Database access object
 * @param string $user     Current user
 * @param string $c_pass   Current password
 * @param string $pass_1   New password
 * @param string $pass_2   New password (Rewrote password)
 *
 * @throws Av_exception If a connection error occurred
 *
 * @return string|boolean
 */
function check_pass($conn, $user, $c_pass, $pass_1, $pass_2)
{
    return security_class::check_pass($conn, $user, $c_pass, $pass_1, $pass_2);
}

//Check hexadecimal string
function valid_hex32($value, $canonical = FALSE)
{
    return security_class::valid_hex32($value, $canonical);
}

//Check uuid
function valid_uuid($value)
{
    return security_class::valid_uuid($value);
}

function valid_support_ticket_number($value)
{
    return security_class::valid_support_ticket_number($value);
}

/*
    This fuction is to allow any character in the validation.
    It is used to validated textareas, Make sure if you use this validation that ADO and HTMLENTITIES are used properly
*/
function valid_all($value)
{
    return security_class::valid_all($value);
}

/* End of file Security.inc */
/* Location: ../include/classes/Security.inc */
