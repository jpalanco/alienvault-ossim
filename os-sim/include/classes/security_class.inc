<?php
/**
* security_class.inc
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

require_once __DIR__ . '/../IDS/Init.php';

define("MIN_ECHAR_VALUE", 1000);

define('OSS_DIGIT', '0-9');
define('OSS_BINARY', '0-1');
define('OSS_LETTER', 'A-Za-z');
define('OSS_HEX', '0-9A-Fa-f');
define('OSS_SPACE', " ".chr(160)); // Character 160: Non-breaking space [http://www.ascii.cl/htmlcodes.htm]
define('OSS_DASH', '\-');
define('OSS_UNDERSCORE', '_');
define('OSS_SCORE', OSS_UNDERSCORE . OSS_DASH);
define('OSS_DOT', '\.');
define('OSS_MIDDLEDOT', '\·');
define('OSS_COLON', ':');
define('OSS_AT', '@');
define('OSS_MAC', 'func:security_class::valid_mac');
define('OSS_BRACKET', '\[\]\{\}');
define('OSS_PUNC_EXT', '\'"\.\!¡#\$£%\^&\*\=\+\:;,~@\[\]\{\}\|\?¿\\\(\)\/ºª·¨' . OSS_SPACE . OSS_SCORE);
define('OSS_PUNC', '\.,:@\/\?&\=' . OSS_SPACE . OSS_SCORE);
define('OSS_SLASH', '\/');
define('OSS_URL', '\.\/');
define('OSS_EALPHA_LOWER', 'áéíóúýàèìòùäëïöüÿâêîôûãñõ¨åæç½ðøþß');
define('OSS_EALPHA_UPPER', 'ÁÉÍÓÚÝÀÈÌÒÙÄËÏÖÜ¾ÂÊÎÔÛÃÑÕ¦ÅÆÇ¼ÐØÞ');
define('OSS_NOECHARS', 'no_echars');
define('OSS_ALPHA', OSS_DIGIT . OSS_LETTER . OSS_EALPHA_LOWER . OSS_EALPHA_UPPER);
define('OSS_CRONTAB', OSS_DIGIT . OSS_LETTER . OSS_DASH . '\*,\/'); // Somewhat dangerous, if "/" is allowed don't allow spaces, tabs or anything similar.
define('OSS_NL', "\r\n");
define('OSS_INPUT', OSS_ALPHA . OSS_PUNC); // for most input text fields
define('OSS_TEXT', OSS_ALPHA . OSS_PUNC . OSS_NL . '\)\(\'"'); // for most textarea fields
define('OSS_ALL', 'func:security_class::valid_all'); // for most textarea fields
define('OSS_USER', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT);  // for validating logins/users with login can be just numerical
define('OSS_USER_2', 'func:security_class::valid_usm_user'); // for validating logins/users with login can not be just numerical
define('OSS_CLOGIN', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT.'@\*\\\\');  // credential login for Openvas
define('OSS_WIN_OSSEC', 'regex:^[^\/\\[\]:;|=,+\*\?<>]*$');  // credential login for ossec windows agents
define('OSS_NET_NAME', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_SPACE . OSS_DOT . OSS_COLON); // for network names
define('OSS_NOTNULL', 'rule:OSS_NOTNULL'); // default for all vars
define('OSS_NULLABLE', 'rule:OSS_NULLABLE'); // value can be null, false, missing
define('OSS_PORT', 'func:security_class::valid_port');  //	Valid port number or pair port-protocol. Port range: [0 - 65535]; Separators allowed : -, _, #; Protocols allowed : tcp, udp, icmp
define('OSS_PORT_RANGE', 'func:security_class::valid_port_range');  // Valid port number. Port range: [0 - 65535]
define('OSS_PROTOCOL', 'func:security_class::valid_protocol');
define('OSS_PROTOCOL_SERVICE', 'func:security_class::valid_protocol_service');
define('OSS_CCLASS', 'func:security_class::valid_cclass'); // C-class 0.0.0 and 255.255.255 allowed
define('OSS_IP_ADDR', 'func:security_class::valid_ip');
define('OSS_IP_ADDR_0', 'func:security_class::valid_ip_2'); // IP address 0.0.0.0 allowed
define('OSS_IP_CIDR', 'func:security_class::valid_cidr'); // CIDR 0.0.0.0/xx allowed
define('OSS_IP_CIDR_0', 'func:security_class::valid_ipcidr_00'); // IP or CIDR format (0.0.0.0 and 0.0.0.0/xx allowed, included 0.0.0.0/0)
define('OSS_IP_ADDRCIDR', 'func:security_class::valid_ipcidr'); // IP OR CIDR format (CIDR 0.0.0.0/xx allowed)
define('OSS_IP_ADDRCIDR_0', 'func:security_class::valid_ipcidr_2'); // IP or CIDR format (0.0.0.0 and 0.0.0.0/xx allowed)
define('OSS_SEVERAL_IP_ADDRCIDR_0', 'func:security_class::valid_ipcidr_3'); // Several IPs OR CIDRs formats (0.0.0.0. allowed)
define('OSS_FQDNS', 'func:security_class::valid_fqdns');
define('OSS_QUOTES', 'func:security_class::valid_quotes');
define('OSS_DATE', 'func:security_class::valid_date');
define('OSS_DATETIME', 'func:security_class::valid_datetime');
define('OSS_DATETIME_DATE', 'func:security_class::valid_datetime_date');
define('OSS_MAIL_ADDR', 'func:security_class::valid_email');
define('OSS_MAIL_MESSAGE', OSS_ALPHA . OSS_PUNC . OSS_SCORE . OSS_AT . OSS_NL . '\>\<\!#');
define('OSS_MAIL_USER', 'regex:^[a-zA-Z0-9_\-\.+@]+$');
define('OSS_BASE64', 'regex:[a-zA-Z0-9\+\=\/]+');
define('OSS_TELEPHONE', 'regex:[+\-\s\d]+');
define('OSS_TIMING_TEMPLATE', 'regex:T[0-5]');
define('OSS_URL_ADDRESS', 'func:security_class::valid_url');
define('OSS_URI', 'func:security_class::valid_uri');
define('OSS_FILENAME', 'func:security_class::valid_filename');
define('OSS_WEEKDAY', 'func:security_class::valid_weekday');
define('OSS_SHA1', OSS_LETTER . OSS_DIGIT);
define('OSS_PASSWORD', OSS_NOECHARS . OSS_DIGIT . OSS_ALPHA . OSS_PUNC_EXT . OSS_SPACE . '\>\<');
define('OSS_HOST_NAME', 'func:security_class::valid_hostname');
define('OSS_HEXDIGIT', OSS_DIGIT.'A-Fa-f');
define('OSS_HEXCOLOR', '#'.OSS_HEXDIGIT);
define('OSS_TZONE', 'func:security_class::valid_tzone');
define('OSS_FQDN_IP', 'func:security_class::valid_fqdn_or_ip'); //IP (0.0.0.0 not allowed) OR FQDN
define('OSS_MAIL_SERVER_ADDRESS','func:security_class::valid_smtp_server_address');
define('OSS_UUID', 'func:security_class::valid_uuid');
define('OSS_SUPPORT_TICKET_NUMBER', 'func:security_class::valid_support_ticket_number');
define('OSS_CVE_ID', 'regex:CVE\-\d{4}\-\d{4,}+');

// Directive editor
define('OSS_PLUGIN_SID_LIST', OSS_DIGIT . OSS_LETTER . OSS_UNDERSCORE . '\[\]\+,');
define('OSS_PLUGIN_SID', OSS_DIGIT . OSS_LETTER . OSS_COLON . ',');
define('OSS_GROUP_LIST', OSS_ALPHA . OSS_SPACE . OSS_SCORE . '\.:@\/,');
define('OSS_DIRECTIVE_NAME', OSS_DIGIT . OSS_LETTER . OSS_SCORE . OSS_SPACE . OSS_DOT . '\,\(\)');
define('OSS_GROUP_NAME', OSS_ALPHA . OSS_SPACE . OSS_SCORE . '\.:@\/');
define('OSS_FROM', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_DOT . OSS_SCORE . ',\!\/');
define('OSS_TO', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_DOT . OSS_SCORE . ',\!\/');
define('OSS_PORT_FROM', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_UNDERSCORE . '\!,');
define('OSS_PORT_TO', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_UNDERSCORE . '\!,');
define('OSS_PORT_FROM_LIST', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_UNDERSCORE . '\!,');
define('OSS_PORT_TO_LIST', OSS_DIGIT . OSS_LETTER . OSS_COLON . OSS_UNDERSCORE . '\!,');
define('OSS_SENSOR', OSS_DIGIT . OSS_DOT . OSS_LETTER . OSS_COLON . OSS_DASH . ',\!');
define('OSS_RULE_NAME', OSS_COLON . OSS_DIGIT . OSS_LETTER . OSS_SCORE . OSS_SPACE . OSS_DOT . OSS_SLASH . '\)\(');

//Reports
define('OSS_REPORT_NAME', OSS_SCORE . OSS_ALPHA . OSS_PUNC . '\;\#\|');

define('OSS_LT','func:security_class::valid_lower_than');
define('OSS_LET','func:security_class::valid_lower_or_equal_than');

define ('OSS_IP_FORMAT', '(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])');

//ERROR messages
define('OSS_ERROR', _("Validation error")); // default error message
define('OSS_SQL', 'illegal:(select |union |update |delete |insert)');

// Don't use
$GLOBALS['ossim_last_error'] = FALSE; // track the latest error


class security_class
{
    //Remove special chars (chinese characters)
    public static function clean_echars($data)
    {
        if (is_array($data)) {
            foreach ($data as $i => $d) {
                $data[$i] = static::clean_echars($d);
            }
        } else {
            if (preg_match_all('/&#(\d{4,5});/', $data, $e_chars)) {
                foreach ($e_chars[1] as $e_char) {
                    $data = str_replace('&#' . $e_char . ';', '', $data);
                }
            }
        }

        return $data;
    }


    public static function get_echars($data)
    {
        $echars = (preg_match_all('/&#(\d{4,5});/', $data, $match) != FALSE) ? $match[1] : array();

        return $echars;
    }


    public static function ids_callback(&$val, $key)
    {
        $val = (mb_detect_encoding($val . ' ', 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($val) : $val;
        $val = static::clean_echars($val);

        return true;
    }

    public static function ids($init, $ids, $compositeLog)
    {
        try {
            if (!$ids->isEmpty()) {
                $compositeLog->addLogger($init);
                $compositeLog->execute($ids);

                $error_msg = _('Sorry, operation was not completed due to security reasons. An attack attempt has been logged to the system') . '<br/>' . utf8_encode($result);

                return  static::ossim_error($error_msg, AV_WARNING);
            }
        } catch(Exception $e) {
            $error_msg = _('An error occured') . ': ' . $e->getMessage();
            return static::ossim_error($error_msg, AV_WARNING);
        }

        return true;
    }


    public static function html_vul_scan($string, $is_get)
    {
        $scan_for = array('/<\s*script[^>]*>/Ui');

        if (is_array($string)) {
            foreach ($string as $string_piece) {
                foreach ($scan_for as $s) {
                    if ($is_get) {
                        if (preg_match("/^(\<=|\>=|\>|\<|\<\>)$/", $string_piece)) {
                            continue;
                        }

                        if ($string_piece != strip_tags($string_piece)) {
                            return static::ossim_error('HTTP GET ' . _('param not allowed for security reasons'), AV_WARNING);
                        }
                    }

                    if (preg_match($s, $string_piece)) {
                        return static::ossim_error('HTTP POST ' . _('param not allowed for security reasons'), AV_WARNING);
                    }
                }
            }
        } else {
            $string = html_entity_decode($string);
            foreach ($scan_for as $s) {
                if ($is_get) {
                    if (preg_match('/^<<./', $string) || preg_match("/^(\<=|\>=|\>|\<|\<\>)$/", $string)) {
                        continue;
                    }

                    if ($string !== strip_tags($string)) {
                        return static::ossim_error('HTTP GET ' . _('param not allowed for security reasons'), AV_WARNING);
                    }
                }

                if (preg_match($s, $string)) {
                    return static::ossim_error('HTTP POST ' . _('param not allowed for security reasons'), AV_WARNING);
                }
            }
        }

        return true;
    }

    public static function disallowingCommonExploits ($_html_vars_checked = true)
    {
        /*
        * Scan all HTTP vars disallowing common exploits like XSS
        */

        if (!isset($_html_vars_checked)) {
            $_is_get = TRUE;

            foreach (array($_GET, $_POST) as $globalvar) {
                foreach ($globalvar as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            return static::html_vul_scan($v, $_is_get);
                        }
                    } else {
                        return static::html_vul_scan($value, $_is_get);
                    }
                }

                $_is_get = FALSE;
            }
        }
        return true;
    }

    /*
    * Safe html escape. Always use that for printing vars comming
    * from outside ($_GET, $_POST, databases, etc)
    */
    public static function htm($var)
    {
        return nl2br(Util::htmlentities($var));
    }


    public static function strip($var)
    {
        return get_magic_quotes_gpc() ? static::strip_map($var) : $var;
    }


    public static function strip_map($value)
    {
        $value = is_array($value) ? array_map('security_class::strip_map', $value) : stripslashes($value);

        return $value;
    }


    public static function required()
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
    public static function GET($param)
    {
        return isset($_GET[$param]) ? static::strip($_GET[$param]) : NULL;
    }


    /*
    * @see POST()
    */
    public static function POST($param)
    {
        return isset($_POST[$param]) ? static::strip($_POST[$param]) : NULL;
    }


    /*
    * @see REQUEST()
    */
    public static function REQUEST($param)
    {
        return isset($_REQUEST[$param]) ? static::strip($_REQUEST[$param]) : NULL;
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

    public static function ossim_valid()
    {

        static $cons = FALSE;

        if (func_num_args() < 2)
        {
            return 'Wrong number of params, usage: ' . __FUNCTION__ . '($var, $rules)';
        }

        if (!$cons)
        {
            $cons = get_defined_constants();
        }

        $parms = func_get_args();
        $v_var = $parms[0]; // the var to be validated

        if (is_object($v_var))
        {
            return static::ossim_set_error(_('Wrong datatype passed, expected string'));
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

        $error_msg = OSS_ERROR;
        $val_notnull = TRUE; // by default do not allow empty vars
        $count = count($parms);
        $extra_params = [];

        for ($i = 0; $i < $count; $i++) {
            //
            // error:
            //

            if (preg_match('/^error:(.+)$/', $parms[$i], $m)) {
                $m[1] = Util::htmlentities($m[1]);
                $m[1] = str_replace("&amp;", "&", $m[1]);
                $error_msg = $m[1];
                unset($parms[$i]);
                //
                // illegal:
                //
            } elseif (preg_match('/^illegal:(.+)$/', $parms[$i], $m)) {
                $m[1] = Util::htmlentities($m[1]);

                $m[1] = str_replace("&amp;", "&", $m[1]);
                $error_msg = sprintf(_("Error in the '%s' field"), $m[1]);
                unset($parms[$i]);
                //
                // rule: (NULL permitted or not)
                //
            } elseif (preg_match('/^rule:(.+)$/', $parms[$i], $m)) {
                if ($m[1] == 'OSS_NOTNULL') {
                    $val_notnull = TRUE;
                    unset($parms[$i]);
                } elseif ($m[1] == 'OSS_NULLABLE') {
                    $val_notnull = FALSE;
                    unset($parms[$i]);
                }
                else{
                    $parms[$i] = constant($m[1]);
                }
            }elseif (preg_match('/^params:(.+)$/', $parms[$i], $m))
            {
                $extra_params = explode("__", $m[1]);
                unset($parms[$i]);
            }
            else{
                ;
            }
        }

        if (empty($subject)) {
            $subject[0] = array();
        }

        $pass_field = FALSE;

        if (preg_match('/Password/i', $error_msg) || preg_match('/_key/i', $error_msg) || preg_match('/_pass/i', $error_msg)) {
            $pass_field = TRUE;
        }

        foreach ($subject as $k => $v) {
            if (is_object($v) || is_array($v)) {
                return static::ossim_set_error('Wrong datatype passed, expected string');
            }

            //
            // NULL check
            //
            if ($v === NULL || $v === '') {
                if ($val_notnull) {
                    return static::ossim_set_error("$error_msg (" . _('missing required field') . ')');
                } else {
                    continue; //If the val is null and it is allowed then we continue validating
                }
            }

            $v = (mb_detect_encoding($v . ' ', 'UTF-8,ISO-8859-1') == 'UTF-8') ? mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8') : $v;

            $val_str = '';
            $oss_echars = FALSE;
            $no_echars = FALSE;
            $echars = static::get_echars($v);
            $exists_echars = (is_array($echars) && !empty($echars)) ? TRUE : FALSE;

            foreach ($parms as $p) {
                //
                // func:
                //
                if (preg_match('/^func:(.+)$/', $p, $m)) {
                    $func = $m[1];
                    $func_name = $m[1];

                    $params = array_merge([$v, $error_msg] , $extra_params);

                    if (strpos($func, '::') !== FALSE) {
                        // static Class::method()
                        $func = explode('::', $func);
                    }

                    if (!is_callable($func)) {
                        return static::ossim_set_error("The validation function '$func_name' doesn't exist");
                    }

                    if (!call_user_func_array($func, $params)) {
                        return false;
                    }

                    continue;
                }
                //
                // regex:
                //
                if (preg_match('/^regex:(.+)$/', $p, $m)) {
                    if (!preg_match('/^' . $m[1] . '$/', $v)) {
                        $v = Util::htmlentities($v);
                        $e_msg = ($pass_field == TRUE) ? $error_msg . ' (the introduced string is not in a valid format)' : $error_msg . " (the string '<strong>$v</strong>' is not in a valid format)";
                        return static::ossim_set_error($e_msg);
                    }

                    continue;
                }
                //
                // OSS_* constants
                //

                if ($p == OSS_NOECHARS) {
                    $no_echars = TRUE;
                } else {
                    $val_str .= $p;
                }
            }

            if (stripos($val_str, OSS_LETTER) !== FALSE) {
                $oss_echars = TRUE;
            }

            $clean_v = $v;

            if ($exists_echars == TRUE && $val_str) {

                if ($oss_echars == FALSE) {
                    $e_msg = ($pass_field == TRUE) ? $error_msg : $error_msg . '.  <br/>String: <strong>' . sprintf(_("'%s'") . "</strong> " . _("not allowed."), $v);
                    return static::ossim_set_error($e_msg);
                } else {

                    if ($no_echars == TRUE) {
                        $e_msg = ($pass_field == TRUE) ? $error_msg : $error_msg . '.  <br/>String: <strong>' . sprintf(_("'%s'") . "</strong> " . _("not allowed."), $v);
                        return static::ossim_set_error($e_msg);
                    } else {

                        foreach ($echars as $k => $char) {
                            if ($char < MIN_ECHAR_VALUE) {
                                $e_msg = $error_msg . " " . sprintf(_("('%s' not allowed)"), "&#" . $char . ";");
                                $e_msg .= ($pass_field == TRUE) ? '' : ".  <br/>Introduced string: '<strong>$v</strong>'";
                                return static::ossim_set_error($e_msg);
                            }
                        }

                        $clean_v = static::clean_echars($v);
                    }
                }
            }

            $val_str = (mb_detect_encoding($val_str . ' ', 'UTF-8,ISO-8859-1') == 'UTF-8') ? mb_convert_encoding($val_str, 'ISO-8859-1', 'UTF-8') : $val_str;
            if (!empty($val_str) && @preg_match("/[^$val_str]/", $clean_v, $m)) {

                $not = $m[0];

                if ($not == ' ') {
                    $not = _('spaces');
                }

                if ($not == "\r" || $not == "\n") {
                    $not = _('breaklines');
                }

                if ($not == "\t") {
                    $not = _('tabulator');
                }

                $v = Util::htmlentities($v);
                $not = Util::htmlentities($not);

                if ($pass_field == TRUE) {

                    return static::ossim_set_error($error_msg . " " . sprintf(_("('%s' not allowed)."), $not));
                } else {
                    return static::ossim_set_error($error_msg . " " . sprintf(_("('%s' not allowed)"), $not) . ".<br/>Introduced string: '<strong>$v</strong>'");
                }
            }
        }

        return TRUE;
    }


    public static function ossim_set_error($error)
    {
        $GLOBALS['ossim_last_error'] = $error;

        return FALSE;
    }


    public static function ossim_clean_error()
    {
        $GLOBALS['ossim_last_error'] = FALSE;

        return TRUE;
    }


    public static function ossim_get_error()
    {
        return $GLOBALS['ossim_last_error'];
    }


    public static function ossim_get_error_clean()
    {
        $error = $GLOBALS['ossim_last_error'];
        $normalize = explode("<br/>", $error);

        if (count($normalize) == 2) {
            $error = trim($normalize[0]);

            if (substr($error, -1) != '.') {
                $error .= '.';
            }

            $error .= "&nbsp;&nbsp;" . $normalize[1];
        }

        return $error;
    }


    public static function ossim_error($error_msg = NULL, $error_type = AV_ERROR, $styles = 'width: 90%; text-align:left; margin:auto;')
    {
        $error_msg = ($error_msg !== NULL) ? $error_msg : $GLOBALS['ossim_last_error'];

        if (!$error_msg) {
            return FALSE;
        }

        switch ($error_type) {
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

    /*
     * small and elegant recursive function
     * to replace ugly and copy pasted validation
     */
    public static function utfize($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = static::utfize($v);
            }
        } elseif (mb_detect_encoding($val . " ", 'UTF-8,ISO-8859-1') == 'UTF-8') {
            $val = trim(Util::utf8entities($val));
        }
        return $val;
    }

    public static function check_last_ossim_error(&$validation_errors)
    {
        if (isset($GLOBALS['ossim_last_error']) && $GLOBALS['ossim_last_error'] !== FALSE) {
            $last_error = $GLOBALS['ossim_last_error'];
            if (mb_detect_encoding($last_error . ' ', 'UTF-8,ISO-8859-1') == 'ISO-8859-1') {
                $last_error = mb_convert_encoding($last_error, 'UTF-8', 'ISO-8859-1');
            }
            $validation_errors[] = $last_error;
            static::ossim_clean_error();
        }

        return true;
    }

    //deprecated old and ugly validation function
    //for backward compatibility
    public static function validate_form_fields($type, $validate)
    {
        $validation_errors = array();
        $validate_rules = array();
        $values = array();

        if ($type == 'POST') {
            $validate_rules = $validate;

            foreach ($_POST as $k => $v) {
                if (!is_array($v)) {
                    $values[$k] = (mb_detect_encoding($v . " ", 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities(static::POST($k)) : static::POST($k);
                } else {

                    $aux = array();
                    foreach ($v as $i => $j) {
                        $aux[$i] = (mb_detect_encoding($v . " ", 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($j) : $j;
                    }

                    //Special case: Arrays
                    $name = $k . '[]';
                    $values[$name] = $aux;
                }
            }
        } else if ($type == 'GET') {
            $k = $_GET['name'];

            if (isset($validate[$k])) {
                $validate_rules[$k] = $validate[$k];

                //Special case: Arrays
                $name = str_replace('[]', '', $_GET['name']);

                if (!is_array($_GET[$name])) {
                    $values[$k] = (mb_detect_encoding(static::GET($name) . ' ', 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities(static::GET($name)) : static::GET($name);
                } else {
                    $aux = array();
                    foreach ($_GET[$name] as $i => $j) {
                        $aux[$i] = (mb_detect_encoding($j . ' ', 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($j) : $j;
                    }

                    $values[$k] = $aux;
                }
            }
        } else {
            // Special Case: Invalid Send Method
            $validation_errors['invalid_sm'] = _('Invalid send method');
            return $validation_errors;
        }

        foreach ($validate_rules as $k => $v) {
            if (!isset($values[$k])) {
                $value = NULL;
            } else {
                $value = is_string($values[$k]) ? trim($values[$k]) : $values[$k];
            }

            eval("security_class::ossim_valid(\$value, ".$validate_rules[$k]['validation'].", '".$validate_rules[$k]['e_message']."');");

            static::check_last_ossim_error($validation_errors);
        }

        return $validation_errors;
    }

    /**
     * This function validates a user login
     *
     * @param string $user User login
     *
     * @return boolean
     */
    public static function valid_usm_user($user)
    {
        //Checking length
        if (strlen($user) > 64) {
            return static::ossim_set_error(_("Error in the 'User login' field (User cannot be longer than 64 characters)"));

        }

        $valid[0] = OSS_LETTER;
        $valid[1] = OSS_DIGIT;
        $valid[2] = OSS_SCORE;
        $valid[3] = OSS_DOT;

        $pattern = "/[^" . implode("", $valid) . "]/";

        if (preg_match_all($pattern, $user, $_matches)) {
            return static::ossim_set_error(_("Invalid 'User login' field.") . '<br/>' . _('Entered User login') . ": '<strong>" . Util::htmlentities($user) . "</strong>'");
        }

        $pattern = "/^[0-9]*$/";
        if (preg_match($pattern, $user, $_matches)) {
            return static::ossim_set_error(_("Error in the 'User login' field (User can not be just numerical)"));
        }

        return TRUE;
    }


    // CIDR 0.0.0.0/xx allowed
    public static function valid_cidr($cidrs)
    {
        $valid_sep = array(',', '\n', ' ');
        $current_sep = '';

        foreach ($valid_sep as $v_sep) {
            if (preg_match("/$v_sep/", $cidrs)) {
                $current_sep = $v_sep;

                break;
            }
        }

        $array_cidr = ($current_sep == '') ? array($cidrs) : explode($current_sep, $cidrs);

        foreach ($array_cidr as $v) {
            $v = trim($v);

            if (preg_match('/^'.OSS_IP_FORMAT.'\/([1-9]|[1-2][0-9]|3[0-2])$/', $v)) {
                continue;
            } else {
                static::ossim_set_error(_('Invalid IP/Mask address. Format allowed') . ': nnn.nnn.nnn.nnn/nn <br/>' . _('Entered CIDR') . ": '<strong>" . Util::htmlentities($v) . "</strong>'");

                return FALSE;
            }
        }

        return TRUE;
    }

    //unused variable $key is required to specify third parameter here.
    //Because this function is called from array_walk
    public static function append_protocol(&$item, $key, $protocol)
    {
        $item = $item . ' - ' . $protocol;
        return $item;
    }

    public static function valid_port_range($port){

        if (! (preg_match('/^(0|[1-9][0-9]*)$/', $port) && $port >= 0 && $port <= 65535) ) {
            return static::ossim_set_error(_("Port number isn't valid") . '<br/>' . _('Entered port') . ": '<strong>" . Util::htmlentities($port) . "</strong>'");
        }
        return TRUE;
    }

    public static function valid_port($ports)
    {
        $ports_array = array();

        if (preg_match('/(\d+)-(\d+)\s*\-\s*(\w+)/', $ports, $found)) {
            $ports = range($found[1], $found[2]);
            array_walk($ports, 'static::append_protocol', $found[3]);
        }

        if (!is_array($ports)) {
            $ports_array[] = $ports;
        } else {
            $ports_array = $ports;
        }

        foreach ($ports_array as $port) {
            $valid_protocol = array('tcp', 'udp', 'icmp');
            $valid_sep = array('-', '_', '#');
            $current_sep = '';

            foreach ($valid_sep as $v_sep) {
                if (preg_match("/$v_sep/", $port)) {
                    $current_sep = $v_sep;

                    break;
                }
            }

            if (empty($current_sep)) {
                $port = trim($port);

                if (! static::valid_port_range($port)) {
                    return FALSE;
                }

            } else {
                $pair_pp = explode($current_sep, $port);
                $port = trim($pair_pp[0]);
                $protocol = trim(strtolower($pair_pp[1]));

                if (!(static::valid_port_range($port) && in_array($protocol, $valid_protocol))) {
                    return static::ossim_set_error(_("Pair port - protocol isn't valid") . '<br/>' . _('Entered pair') . ": '<strong>" . Util::htmlentities("$port-$protocol") . "</strong>'");
                }
            }
        }

        return TRUE;
    }


    public static function valid_cclass($cclass)
    {
        $pattern = '/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){2}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

        if (preg_match($pattern, $cclass)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid C-class. Format allowed : nnn.nnn.nnn') . ': <br/>' . _('Entered C-class') . ": '<strong>" . Util::htmlentities($cclass) . "</strong>'");

    }

    /**
     * @param $protocol
     * @return bool
     *
     */
    public static function valid_protocol($protocol)
    {
        $allowed_potocols = ["tcp", "udp", "icmp"];
        return in_array($protocol, $allowed_potocols);
    }

    /**
     * @param $service
     * @return bool
     *
     */
    public static function valid_protocol_service($service)
    {
        $allowed_services = ["6", "17"];
        return in_array($service, $allowed_services);

    }


    public static function valid_ip($ip)
    {
        if (!preg_match('/,/', $ip)) {
            if ($ip != '0.0.0.0' && $ip != '255.255.255.255' && preg_match('/^'.OSS_IP_FORMAT.'$/', $ip)) {
                return TRUE;
            }

            return static::ossim_set_error(_('Invalid IP address. Format allowed') . ': nnn.nnn.nnn.nnn <br/>' . _('Entered IP') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

        } else {
            $aux_ips = explode(',', $ip);

            foreach ($aux_ips as $ip) {
                if ($ip != '0.0.0.0' && $ip != '255.255.255.255' && preg_match('/^'.OSS_IP_FORMAT.'$/', $ip)) {
                    continue;
                } else {
                    return static::ossim_set_error(_('Invalid IP address. Format allowed') . ': nnn.nnn.nnn.nnn <br/>' . _('Entered IP') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

                }
            }

            return TRUE;
        }
    }


    // Address 0.0.0.0 allowed
    public static function valid_ip_2($ip)
    {
        if (!preg_match('/,/', $ip)) {
            if (preg_match('/^'.OSS_IP_FORMAT.'$/', $ip)) {
                return TRUE;
            }

            return static::ossim_set_error(_('Invalid IP address. Format allowed') . ': nnn.nnn.nnn.nnn <br/>' . _('Entered IP') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

        } else {
            $aux_ips = explode(',', $ip);

            foreach ($aux_ips as $ip) {
                if (preg_match('/^'.OSS_IP_FORMAT.'$/', $ip)) {
                    continue;
                } else {
                    return static::ossim_set_error(_('Invalid IP address. Format allowed') . ': nnn.nnn.nnn.nnn <br/>' . _('Entered IP') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

                }
            }

            return TRUE;
        }
    }


    // CIDR 0.0.0.0/xx allowed
    public static function valid_ipcidr($ip)
    {
        if (preg_match('/^'.OSS_IP_FORMAT.'\/([0-9]|[1-2][0-9]|3[0-2])$/', $ip)) {
            return TRUE;
        } else if ($ip != '0.0.0.0' && preg_match('/^'.OSS_IP_FORMAT.'$/', $ip)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid IP/CIDR address. Format allowed') . ': nnn.nnn.nnn.nnn/nn?<br/>' . _('Entered IP/CIDR') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

    }

    // IP address 0.0.0.0 and CIDR 0.0.0.0/xx allowed
    public static function valid_ipcidr_2($ip)
    {
        if (preg_match('/^'.OSS_IP_FORMAT.'\/([0-9]|[1-2][0-9]|3[0-2])$/', $ip)) {
            return TRUE;
        } elseif (preg_match('/^'.OSS_IP_FORMAT.'$/', $ip)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid IP/CIDR address. Format allowed') . ': nnn.nnn.nnn.nnn/nn?<br/>' . _('Entered IP/CIDR') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

    }


    // IP address 0.0.0.0 and CIDR 0.0.0.0/xx allowed, included 0.0.0.0/0
    public static function valid_ipcidr_00($ip)
    {
       return static::valid_ipcidr_2($ip);

    }


    // IP address 0.0.0.0. and CIDR 0.0.0.0/xx allowed
    public static function valid_ipcidr_3($ip)
    {
        $ok = TRUE;
        $ips_cidrs = explode(',', $ip);

        foreach ($ips_cidrs as $data) {
            if ($ok) {
                if (preg_match('/^'.OSS_IP_FORMAT.'\/([0-9]|[1-2][0-9]|3[0-2])$/', trim($data))) {
                    continue;
                } elseif (preg_match('/^'.OSS_IP_FORMAT.'$/', trim($data))) {
                    continue;
                } else {
                    $ok = FALSE;
                }
            }
        }

        if (!$ok) {
            return static::ossim_set_error(_('Invalid IP/CIDR address. Format allowed') . ': nnn.nnn.nnn.nnn/nn? <br/>' . _('Entered IP/CIDR') . ": '<strong>" . Util::htmlentities($ip) . "</strong>'");

        } else {
            return TRUE;
        }
    }


    /**
     * This function validates an email
     *
     * @param string $email Email
     *
     * @return boolean
     */
    public static function valid_email($email)
    {
        $emails = array();

        if (!is_array($email)) {
            $emails = explode(";", $email);
        } else {
            $emails = $email;
        }

        foreach ($emails as $email_addr) {
            if (!preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email_addr)) {
                return static::ossim_set_error(_("Invalid 'User email' field") . '.<br/>' . _('Entered User email') . ": '<strong>" . Util::htmlentities($email_addr) . "</strong>'");
            }
        }

        return TRUE;
    }


    public static function valid_mac($mac)
    {
        if (preg_match('/^[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}$/i', $mac)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid MAC address. Format allowed') . ': nn:nn:nn:nn:nn:nn, n=[a-f0-9] <br/>' . _('Entered MAC') . ": '<strong>" . Util::htmlentities($mac) . "</strong>'");

    }


    public static function valid_hostname($hostname)
    {
        if (preg_match('/^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$/', $hostname) && strlen($hostname) <= 63) {
            return TRUE;
        }

        if (strlen($hostname) > 63) {
            $hostname = Util::htmlentities($hostname);

            $out = static::ossim_set_error(_('Hostname too long. <br/>Entered hostname') . ": '<strong>" . $hostname . "</strong>'");
        } else {
            $hostname = (empty($hostname)) ? '<i>(' . _("empty hostname") . ')</i>' : Util::htmlentities($hostname);

            $out = static::ossim_set_error(_('Invalid hostname. <br/>Entered hostname') . ": '<strong>" . $hostname . "'</strong>");
        }

        return $out;
    }

    public static function valid_fqdns($fqdns)
    {
        $fqdns = trim($fqdns);
        $array_fqdns = (empty($fqdns)) ? array() : explode(",", $fqdns);

        foreach ($array_fqdns as $v) {
            $v = trim($v);

            if (strlen($v) > 255) {
                $max = 30;
                $v = substr($v, 0, $max) . "[...]";

                return static::ossim_set_error(_('Invalid FQDN/Alias') . ": " . Util::htmlentities($v) . '. ' . _("It's too long"));

            } else {
                if (!preg_match('/\./', $v)) {
                    if (!preg_match('/^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$/', $v)) {
                        $max = 40;
                        $v = (strlen($v) > $max) ? substr($v, 0, $max) . "[...]" : $v;
                        $v = (empty($v)) ? '<i>(' . _('empty fqdn') . ')</i>' : Util::htmlentities($v);

                        return static::ossim_set_error(_('Invalid FQDN/Alias') . ': ' . $v);

                    }
                } else {
                    //Remove domain root (.)
                    if (substr($v, -1) == '.') {
                        $v = substr($v, 0, -1);
                    }

                    $labels = explode('.', $v);

                    foreach ($labels as $label) {
                        // Validate FQDN label
                        if (preg_match('/^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$/', $label)) {
                            if (strlen($label) > 63) {
                                $max = 30;
                                $label = substr($label, 0, $max) . '[...]';
                                return static::ossim_set_error(_('Invalid label') . ': ' . Util::htmlentities($label) . '. ' . _("It's too long"));

                            }
                        } else {
                            $max = 50;
                            $label = (strlen($label) > $max) ? substr($label, 0, $max) . "[...]" : $label;
                            $label = (empty($label)) ? '<i>(' . _('empty string') . ')</i>' : $label;

                            return static::ossim_set_error(_('Invalid format label') . ': ' . Util::htmlentities($label));

                        }
                    }
                }
            }
        }

        return TRUE;
    }


    public static function valid_url($url)
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

        if (preg_match($pattern, $url)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid URL. Format allowed') . ': <http[s]|ftp> :// [user[:pass]@] hostname [port] [/path] [?getquery] [anchor]<br/>' . _('Entered URL') . ": '<strong>" . Util::htmlentities($url) . "</strong>'");

    }


    public static function valid_uri($uri)
    {
        // Path (mandatory)
        $pattern = "/^((\.\.|\.)?(\/\.\.)*)?(\/([a-zA-Z0-9+\$_~-]\.?)+)+\/?";
        // GET Query (optional)
        $pattern .= "(\?[a-zA-Z\+\&\$_.-][a-zA-Z0-9;:@\/\&%=\+\$_.-]*)?";
        // Anchor (optional)
        $pattern .= "(#[a-zA-Z_.-][a-zA-Z0-9+\$_.-]*)?$/";

        if (preg_match($pattern, $uri)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid URL. Format allowed') . ': [/path] [?getquery] [anchor]<br/>' . _('Entered URL') . ": '<strong>" . Util::htmlentities($uri) . "</strong>'");
    }


    public static function valid_filename($filename)
    {
        $pattern = "/^\.?\.?(\/([a-z0-9+\$_-]\.?)+)*\/?[-\w\.]+$/i";

        if (preg_match($pattern, $filename)) {
            return TRUE;
        }

        return static::ossim_set_error(_('Invalid filename.') . '<br/>' . _('Entered file') . ": '<strong>" . Util::htmlentities($filename) . "</strong>'");

    }


    public static function valid_quotes($value)
    {
        if (!preg_match('/\'|\"/', $value)) {
            return TRUE;
        }

        return static::ossim_set_error(_("Invalid Quotes. Don't use quotes ' or \""));

    }

    /**
     * This function is an UNION between valid_datetime and valid_date
     *
     * @param string $value
     * @return boolean
     */
    public static function valid_datetime_date($value)
    {
        if (static::valid_datetime($value) || static::valid_date($value)) {
            static::ossim_clean_error();

            return TRUE;
        }

        return static::ossim_set_error(_('Invalid Date-time or Date. Format allowed') . ': YYYY-MM-DD HH:MM:SS or YYYY-MM-DD<br/>' . _('Entered date') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");

    }


    public static function valid_datetime($value)
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $value, $m)) {
            $year = $m[1];
            $month = $m[2];
            $day = $m[3];
            $hour = $m[4];
            $minutes = $m[5];
            $seconds = $m[6];

            $cond_1 = (checkdate($month, $day, $year) === TRUE);
            $cond_2 = (($hour >= 0 && $hour <= 23) && ($minutes >= 0 && $minutes <= 59) && ($seconds >= 0 && $seconds <= 59));

            if ($cond_1 && $cond_2) {
                return TRUE;
            }
        }

        return static::ossim_set_error(_('Invalid Date-time. Format allowed') . ': YYYY-MM-DD HH:MM:SS <br/>' . _('Entered date') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");

    }


    public static function valid_date($value)
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            $year = $m[1];
            $month = $m[2];
            $day = $m[3];

            $cond_1 = (checkdate($month, $day, $year) === TRUE);

            if ($cond_1) {
                return TRUE;
            }
        }

        return static::ossim_set_error(_('Invalid Date. Format allowed') . ': YYYY-MM-DD <br/>' . _('Entered date') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");

    }


    public static function valid_tzone($value)
    {
        $tzlist = timezone_identifiers_list(4095);

        foreach ($tzlist as $tz) {
            if ($value == $tz) {
                return TRUE;
            }
        }

        return static::ossim_set_error(_('Invalid Time Zone') . '.<br/>' . _('Entered Time Zone') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");

    }


    public static function valid_weekday($value)
    {
        $weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

        if (!in_array($value, $weekdays)) {
            return static:: ossim_set_error(_('Invalid weekday') . '.<br/>' . _('Entered day') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");

        }

        return TRUE;
    }


    public static function is_ip($value)
    {
        if (preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $value)) {
            return TRUE;
        }

        return FALSE;
    }


    public static function is_cidr($value)
    {
        if (preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/([1-9]|[1-2]\d|3[0-2]))$/', $value)) {
            return TRUE;
        }

        return FALSE;
    }


    public static function valid_fqdn_or_ip($value)
    {
        if (static::valid_ip($value) || static::valid_fqdns($value)) {
            static::ossim_clean_error();

            return TRUE;
        }

        return static::ossim_set_error(_('Invalid FQDN/IP') . '.<br/>' . _('Entered value') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");

    }


    public static function valid_smtp_server_address($value)
    {

        $data = parse_url($value);

        if ($data['scheme'] == 'ssl') // For example: ssl://smtp.gmail.com
        {
            return static::valid_fqdn_or_ip($data['host']);
        } else if ($data['scheme'] == '') // For example: smtp.gmail.com OR 173.194.66.109
        {
            return static::valid_fqdn_or_ip($data['path']);
        } else {
            return static::ossim_set_error(_('Invalid Server') . '.<br/>' . _('Entered value') . ": '<strong>" . Util::htmlentities($data['scheme']) . "</strong>'");

        }
    }


    /* Test perms in sensor_ip */
    public static function validate_sensor_perms($conn, $sensor_ip, $args = '')
    {
        $allowed_original = Session::allowedSensors();
        $allowed = explode(',', $allowed_original);
        $query = ossim_query("SELECT sensor.*, HEX(sensor.id) as id, INET6_NTOA(ip) AS ip FROM sensor $args");

        $rs = $conn->Execute($query);

        if (!$rs) {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } else {
            while (!$rs->EOF) {

                $ip = $rs->fields['ip'];

                if (($allowed_original == '' || in_array($ip, $allowed)) && $sensor_ip == $ip) {
                    return TRUE;
                }

                $rs->MoveNext();
            }
        }

        return FALSE;
    }


    public static function valid_echars($value)
    {
        if (preg_match_all('/&#(\d{4,5});/', $value, $e_chars)) {
            foreach ($e_chars[1] as $e_char) {
                if ($e_char < 10000) {
                    return static::ossim_set_error(_('Invalid characters') . '<br/>' . _('Entered characters') . ": '<strong>" . Util::htmlentities($value) . "</strong>'");
                }

                $text = str_replace("&#" . $e_char . ';', '', $value);
            }
        } else {
            $text = $value;
        }
        if ($text != '') {
            $latin_text[0] = "A-Za-z";
            $latin_text[1] = "ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½";
            $latin_text[2] = "ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½Ü¾ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½Õ¦ï¿½ï¿½Ç¼ï¿½ï¿½ï¿½";
            $latin_text[3] = "0-9";

            $pattern = "/[^" . implode('', $latin_text) . "]/";

            if (preg_match_all($pattern, $text)) {
                return static::ossim_set_error(_('Invalid characters') . '<br/>' . _('Entered characters') . ": '<strong>" . Util::htmlentities($text) . "</strong>'");
            }
        }

        return TRUE;
    }


    public static function check_uniqueid($unique_id1, $unique_id2)
    {
        if ($unique_id1 != '' && $unique_id2 != '' && $unique_id1 == $unique_id2) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Function check_pass
     *
     * This function checks whether a password is valid
     *
     * @param object $conn Database access object
     * @param string $user Current user
     * @param string $c_pass Current password
     * @param string $pass_1 New password
     * @param string $pass_2 New password (Rewrote password)
     *
     * @throws Av_exception If a connection error occurred
     *
     * @return string|boolean
     */
    public static function check_pass($conn, $user, $c_pass, $pass_1, $pass_2)
    {
        require_once 'ossim_conf.inc';

        $conf = $GLOBALS['CONF'];
        $conf = (!$conf) ? new Ossim_conf() : $conf;

        $parameters['c_pass'] = $c_pass;
        $parameters['pass_1'] = $pass_1;

        $validate = array('c_pass' => array('validation' => 'OSS_NULLABLE, OSS_PASSWORD', 'e_message' => _('Current password is not allowed')), 'pass_1' => array('validation' => 'OSS_NULLABLE, OSS_PASSWORD', 'e_message' => _('New password is not allowed')));

        foreach ($parameters as $k => $v) {
            eval("security_class::ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

            if (static::ossim_error()) {
                return $validate[$k]['e_message'];
            }
        }

        $pass_length_min = $conf->get_conf('pass_length_min');
        $pass_length_min = intval($pass_length_min);
        $pass_length_min = ($pass_length_min < 7 || $pass_length_min > 255) ? 7 : $pass_length_min;

        $pass_length_max = $conf->get_conf('pass_length_max');
        $pass_length_max = intval($pass_length_max);
        $pass_length_max = ($pass_length_max > 255 || $pass_length_max < $pass_length_min) ? 255 : $pass_length_max;


        //SQL statements to get the user password
        $q_where_1 = Session::get_login_pass_where($user, $c_pass);
        $q_where_2 = Session::get_login_pass_where($user, $pass_1);


        if (empty($c_pass)) {
            return _('Current password cannot be empty');
        }

        $user_list = Session::get_list($conn, $q_where_1, '', TRUE);

        if (count($user_list) < 1) {
            return _('Current password does not match');
        }

        if (strcmp($pass_1, $pass_2) != '0') {
            return _('Passwords mismatches');
        }

        if (mb_strlen($pass_1) < $pass_length_min) {
            return sprintf(_('Password is not long enough [Minimum password size is %s]'), $pass_length_min);
        }

        if (mb_strlen($pass_1) > $pass_length_max) {
            return sprintf(_('Password is too long [Maximum password size is %s]'), $pass_length_max);
        }

        $user_list = Session::get_list($conn, $q_where_2, '', TRUE);

        if (count($user_list) > 0) {
            return _('You must change your old password');
        }

        if (Session::pass_check_complexity($pass_1) == FALSE) {
            return _("The password does not meet the password complexity requirements [Password should contain lowercase and uppercase letters, digits and special characters]");
        }

        if (Log_action::recent_pass_exists($conn, $user, $pass_1)) {
            return _('This password is recently used. Try another');
        }

        return TRUE;
    }


    //Check hexadecimal string
    public static function valid_hex32($value, $canonical = FALSE)
    {
        if ($value == '') {
            return FALSE;
        }

        if ($canonical) {
            $regex = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i";
        } else {
            $regex = "/^[a-f0-9]{32}$/i";
        }

        if (preg_match($regex, $value)) {
            return TRUE;
        }

        return FALSE;
    }

    //Check uuid
    public static function valid_uuid($value)
    {
        $regex = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i";

        if (preg_match($regex, $value)) {
            return TRUE;
        }

        return static::ossim_set_error(_("UUID isn't valid") . '<br/>' . _('Entered UUID') . ": '<strong>" . Util::htmlentities("$value") . "</strong>'");

    }

    //check if value is lower than other value
    function valid_lower_than($value, $error_msg, $max)
    {
        if(!isset($max) || !is_numeric($max)){
            static::ossim_set_error(_("There is no limit set"));
            return FALSE;
        }

        if(intval($value) < intval($max)){
            return TRUE;
        }

        static::ossim_set_error(sprintf($error_msg, $max , $value));

        return FALSE;
    }

    //check if value is lower than other value
    function valid_lower_or_equal_than($value, $error_msg, $max)
    {
        if(!isset($max) || !is_numeric($max)){
            static::ossim_set_error(_("There is no limit set"));
            return FALSE;
        }

        if(intval($value) <= intval($max) ){
            return TRUE;
        }

        static::ossim_set_error(sprintf($error_msg, $max, $value));

        return FALSE;
    }

    public static function valid_support_ticket_number($value)
    {
        $regex = "/^\d{8}$/i";

        if (preg_match($regex, $value)) {
            return TRUE;
        }

        return static::ossim_set_error(_("Invalid ticket number") . ". <br/>" . _('Entered value') . ": '<strong>" . Util::htmlentities("$value") . "</strong>' " . _("is not an 8-digit number"));

    }


    /*
        This fuction is to allow any character in the validation.
        It is used to validated textareas, Make sure if you use this validation that ADO and HTMLENTITIES are used properly
    */
    public static function valid_all($value)
    {
        return TRUE;
    }
}

/* End of file Security.inc */
/* Location: ../include/classes/secutiry_class.inc */
