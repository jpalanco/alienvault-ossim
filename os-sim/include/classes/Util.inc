<?php

/**
 * Util.inc
 *
 * File Util.inc is used to:
 *   - Utilities for all sections
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */
define("LAST_UPDATE","/var/log/alienvault/update/");
define("UPDATE_LOG_PATTERN","/alienvault[0-9]_update-script-[0-9]+\.log/");
define("LAST_UPDATE_LOCK","/usr/share/ossim/www/tmp/last_system_update_message.lock");

class Util
{
    public static function update_error_codes() {
        return array(
            "SUCCESS",
            "ERROR_UNKNOWN_PARAM",
            "ERROR_NO_OSSIM_SETUP",
            "ERROR_NO_PROFILES_FOUND",
            "ERROR_PACKAGE_INDEX_RESYNCHRONIZATION",
            "ERROR_APT_GET_UPDATE",
            "ERROR_REMOVE_OCS_INVENTORY",
            "ERROR_CAN_BE_UPDATED",
            "ERROR_REWRITE_SOURCES_LIST",
            "ERROR_CHECKUNINSTALL_LIBIO_SOCKET_INET6_PERL",
            "ERROR_APT_GET_INSTALL_ALIENVAULT_LICENSE",
            "ERROR_APT_GET_INSTALL_ALIENVAULT_PROFESSIONAL",
            "ERROR_STOP_MONIT",
            "ERROR_APT_GET_REMOVE_LIBHTP1",
            "ERROR_APT_GET_INSTALL_DUMMY_SENSOR_SURICATA",
            "ERROR_APT_GET_REMOVE_PHP5_SUHOSIN",
            "ERROR_APT_GET_INSTALL_LIBMYSQLCLIENT16",
            "ERROR_UNKNOWN_LIBMYSQLCLIENT16_VERSION",
            "ERROR_HOLD_LIBMYSQLCLIENT16",
            "ERROR_UNHOLD_APACHE2",
            "ERROR_REINSTALL_DAQ",
            "ERROR_PERCONADBPASS",
            "ERROR_BUILDLOAD_PRESEED_CONF",
            "ERROR_DEBCONF_SELECTIONS_RECONFIGURE_DASH",
            "ERROR_APT_GET_INSTALL_DASH",
            "ERROR_CHECKINSTALL_BSD_MAILX",
            "ERROR_UPGRADE_DIST_DOWNLOAD_ONLY",
            "ERROR_UPGRADE_DIST",
            "ERROR_UPDATE_IGB_PFRING",
            "ERROR_UNINSTALL_SQUID2",
            "ERROR_APT_GET_AUTOREMOVE",
            "ERROR_APTITUDE_PURGE",
            "ERROR_PLATFORM_UPDATE_DISABLED",
            "ERROR_PLATFORM_UPDATE_INVALID_REPOS"
        );
    }

    public static function get_update_error($code) {
        $codes = self::update_error_codes();
        return $codes[$code];
    }


    /**
     * Function beautify
     *
     * Expects a string with underscore in it and substitutes with spaces.
     * Used for DB Integrity
     *
     * @param string $name
     *
     * @return string
     */
    public static function beautify($name)
    {
        return str_replace('_', ' ', $name);
    }

    /**
     * Function date_diff_min
     *
     * Returns the date difference between two dates
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return int
     */
    function date_diff_min($startDate, $endDate)
    {
        $to_time   = strtotime($startDate);
        $from_time = strtotime($endDate);

        if(abs($to_time - $from_time) == 0) {
            return 0;
        }

        return floor(abs($to_time - $from_time) / 60);
    }

    // Translates the string into human readable form
    public static function translate_alarm($conn, $name, $alarm, $format = 'string')
    {
        // Try to get taxonomy description
        if (is_object($alarm) && !empty($alarm))
        {
            $taxonomy = $alarm->get_taxonomy();
        }
        if (empty($taxonomy))
        {
            $taxonomy = array(
                'id'          => '',
                'kingdom'     => '',
                'category'    => '',
                'subcategory' => ''
            );
            if (is_object($alarm))
            {
                $params = array($alarm->get_plugin_sid());

                $query = "SELECT k.id,k.name AS kingdom,c.name AS category, t.subcategory
                    FROM alarm_taxonomy t, alarm_kingdoms k, alarm_categories c
                    WHERE t.kingdom = k.id AND t.category = c.id AND t.sid = ?";
            }
            else
            {
                $params = array($name);

                $query = "SELECT k.id, k.name AS kingdom, c.name AS category, t.subcategory
                    FROM alarm_taxonomy t, alarm_kingdoms k, alarm_categories c, plugin_sid s
                    WHERE t.kingdom = k.id AND t.category = c.id AND t.sid = s.sid AND s.plugin_id = 1505 AND s.name = ?";

            }

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $taxonomy = $rs->fields;
                    $name     = $rs->fields['kingdom'] . ' &mdash; ' . $rs->fields['category'] . ' &mdash; ' . $rs->fields['subcategory'];
                }
            }
        }
        else
        {
            if ($format == 'string' && $taxonomy['id'] != '')
            {
                $name = $taxonomy['kingdom'] . ' &mdash; ' . $taxonomy['category'] . ' &mdash; ' . $taxonomy['subcategory'];
            }
        }
        // If not, translate meta tags

        $translations = array(
            '/SRC_IP/'     => array('func' => 'get_src_ip',     'parameters' => array()),
            '/DST_IP/'     => array('func' => 'get_dst_ip',     'parameters' => array()),
            '/SRC_PORT/'   => array('func' => 'get_src_port',   'parameters' => array()),
            '/DST_PORT/'   => array('func' => 'get_dst_port',   'parameters' => array()),
            '/PROTOCOL/'   => array('func' => 'get_protocol',   'parameters' => array()),
            '/PULSE/'      => array('func' => '',               'parameters' => array()),
            '/PLUGIN_ID/'  => array('func' => 'get_plugin_id',  'parameters' => array()),
            '/PLUGIN_SID/' => array('func' => 'get_plugin_sid', 'parameters' => array()),
            '/FILENAME/'   => array('func' => 'get_event_data', 'parameters' => array($conn, 'filename')),
            '/USERNAME/'   => array('func' => 'get_event_data', 'parameters' => array($conn, 'username')),
            '/USERDATA/'   => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata')),
            '/USERDATA1/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata1')),
            '/USERDATA2/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata2')),
            '/USERDATA3/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata3')),
            '/USERDATA4/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata4')),
            '/USERDATA5/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata5')),
            '/USERDATA6/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata6')),
            '/USERDATA7/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata7')),
            '/USERDATA8/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata8')),
            '/USERDATA9/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'userdata9'))
        );

        foreach ($translations as $k => $v)
        {
            if (!preg_match($k, $name))
            {
                continue;
            }
            switch ($k)
            {
                case '/PROTOCOL/':

                    $d_param = call_user_func_array(array($alarm, $v['func']), $v['parameters']);

                    $parameters  = array($d_param);
                    $replacement = call_user_func_array('Protocol::get_protocol_by_number', $parameters);

                    if (empty($replacement))
                    {
                        $replacement = $d_param;
                    }

                    break;

                case '/PULSE/':
                    $replacement = '';
                    $pulse_id = $alarm->get_event_data($conn, 'filename');
                    if (!$pulse_id) {
                        $event_id     = $alarm->get_event_id();
                        $pulses = Siem::get_event_pulses($conn, $event_id, FALSE, TRUE);
                        if ($pulses) {
                            $pulse = array_shift($pulses);
                            $replacement = $pulse["name"];
                            break;
                        }
                    }
                    if ($pulse_id) {
                         try {
                            $otx         = new Otx();
                            $pulse_id    = array_shift(explode("_",$pulse_id,2));
                            $pulse       = $otx->get_pulse_detail($pulse_id, TRUE);
                            $replacement = $pulse['name'];
                            break;
                        } catch (Exception $e) {}
                    }
                    $replacement = _('No information available. You are no longer subscribed to this pulse.');
                    break;

                case '/SRC_IP/':

                    if ($alarm->get_src_host() != '')
                    {
                        $parameters  = array($conn, $alarm->get_src_host());
                        $replacement = call_user_func_array('Asset_host::get_name_by_id', $parameters);
                    }
                    else
                    {
                        $d_param = call_user_func_array(array($alarm, $v['func']), $v['parameters']);

                        $parameters  = array($conn, $d_param, $alarm->get_ctx());
                        $replacement = call_user_func_array('Asset_host::get_name_by_ip', $parameters);
                        $replacement = array_pop($replacement);

                        if (empty($replacement))
                        {
                            $replacement = $d_param;
                        }
                    }

                    break;

                case '/DST_IP/':

                    $d_param = call_user_func_array(array($alarm, $v['func']), $v['parameters']);

                    if ($alarm->get_dst_host() != '')
                    {
                        $parameters  = array($conn, $alarm->get_dst_host());
                        $replacement = call_user_func_array('Asset_host::get_name_by_id', $parameters);
                    }
                    else
                    {
                        $parameters  = array($conn, $d_param, $alarm->get_ctx());
                        $replacement = call_user_func_array('Asset_host::get_name_by_ip', $parameters);
                        $replacement = array_pop($replacement);

                        if (empty($replacement))
                        {
                            $replacement = $d_param;
                        }
                    }

                    break;

                default:
                    $replacement = call_user_func_array(array($alarm, $v['func']), $v['parameters']);
            }

            if (!empty($replacement))
            {
                $str = '$name = preg_replace("' . $k . '", $replacement, $name);';
                eval($str);
            }
        }

        if (empty($name))
        {
            $name = _('Unknown directive');
        }

        $taxonomy['name'] = $name;

        return ($format == 'string') ? $name : $taxonomy;
    }

    /**
     * Function timestamp2date
     *
     * Converts a string in timestamp format to string in date format
     *
     * @param string $timestamp
     *
     * @return string
     */
    public static function timestamp2date($timestamp)
    {
        if (!$timestamp)
        {
            return '';
        }

        $num = "[0-9]";

        if (preg_match("/^$num{14}$/", $timestamp))
        {
            /*
            MySQL < 4.1:
            TIMESTAMP is returned as a string in 'YYYYMMDDHHMMSS'
            */
            return (substr($timestamp, 0, 4) . '-' . // YYYY
                substr($timestamp, 4, 2) . '-' . // MM
                substr($timestamp, 6, 2) . ' ' . // DD
                substr($timestamp, 8, 2) . ':' . // HH
                substr($timestamp, 10, 2) . ':' . // MM
                substr($timestamp, 12, 2)); // SS

        }
        elseif (preg_match("/$num{4}\-$num{2}\-$num{2} $num{2}\:$num{2}\:$num{2}/", $timestamp))
        {
            /*
            MySQL >= 4.1:
            TIMESTAMP is returned as a string in 'YYYY-MM-DD HH:MM:SS'
            */
            return $timestamp;
        }
        else
        {
            return $timestamp;
        }
    }


    /*
    * Format the difference of two dates into human readable
    *
    * @param $dt1 A time string or unix timestamp
    * @param $dt2 A time string or unix timestamp
    * @param $format What to include, accepts:
    *               - "y" show years
    *               - "M" show months
    *               - "w" weeks
    *               - "d" days
    *               - "h" hours, "m" minutes, "s" seconds
    *               ie: "dhm" will return the diff only in
    *                   days, hours and minutes as: 4 Days 18:30
    *                   if days is 0, it won't be shown
    */
    public static function date_diff($dt1, $dt2, $format = 'yMdhm')
    {

        if (!is_numeric($dt1))
        {
            $date1 = (strtotime($dt1) > 0) ? strtotime($dt1) : $dt1;
        }
        else
        {
            $date1 = $dt1;
        }
        if (!is_numeric($dt2))
        {
            $date2 = (strtotime($dt2) > 0) ? strtotime($dt2) : $dt2;
        }
        else
        {
            $date2 = $dt2;
        }
        $diff  = $date1 - $date2;
        $min   = 60;
        $hour  = 60 * $min;
        $day   = 24 * $hour;
        $week  = 7 * $day;
        $month = 30 * $day;
        $year  = 365 * $day;
        $y     = $mo = $w = $d = $h = $m = $s = NULL;

        if ($diff > $year && (strpos($format, 'y') !== FALSE))
        {
            $y    = intval($diff / $year);
            $diff = $diff - ($y * $year);
        }
        if ($diff > $month && (strpos($format, 'M') !== FALSE))
        {
            $mo   = intval($diff / $month);
            $diff = $diff - ($mo * $month);
        }
        if ($diff > $week && (strpos($format, 'w') !== FALSE))
        {
            $w    = intval($diff / $week);
            $diff = $diff - ($w * $week);
        }
        if ($diff > $day && (strpos($format, 'd') !== FALSE))
        {
            $d    = intval($diff / $day);
            $diff = $diff - ($d * $day);
        }
        if (strpos($format, 'h') !== FALSE)
        {
            $h    = ($diff > $hour) ? intval($diff / $hour) : 0;
            $diff = $diff - ($h * $hour);
        }
        if (strpos($format, 'm') !== FALSE)
        {
            $m    = ($diff > $min) ? intval($diff / $min) : 0;
            $diff = $diff - ($m * $min);
        }
        if (strpos($format, 's') !== FALSE)
        {
            $s = $diff;
        }
        $dt = $tm = $diff_arr = array();

        if ($y)
        {
            $str  = ($y > 1) ? _('Years') : _('Year');
            $dt[] = "$y $str";
        }
        if ($mo)
        {
            $str  = ($mo > 1) ? _('Months') : _('Month');
            $dt[] = "$mo $str";
        }
        if ($w)
        {
            $str  = ($w > 1) ? _('Weeks') : _('Week');
            $dt[] = "$w $str";
        }
        if ($d)
        {
            $str  = ($d > 1) ? _('Days') : _('Day');
            $dt[] = "$d $str";
        }

        if (!is_null($h))
        {
            $tm[] = sprintf('%02d', $h);
        }

        if (!is_null($m))
        {
            $tm[] = sprintf('%02d', $m);
        }

        if (!is_null($s))
        {
            $tm[] = sprintf('%02d', $s);
        }

        if (count($dt))
        {
            $diff_arr[] = implode(', ', $dt);
        }

        if (count($tm))
        {
            $diff_arr[] = implode(':', $tm);
        }

        return implode(' ', $diff_arr);
    }

    /**
     * Function get_acid_date_link
     *
     * PRE: $date must be in format YYYY-MM-DD HH:MM
     *      $target must be ip_src or ip_dst
     *
     * @param string $date   Date string
     * @param string $ip     ip string
     * @param string $target target string
     *
     * @return string
     */
    public static function get_acid_date_link($date, $ip = '', $target = '')
    {
        $conf        = $GLOBALS['CONF'];
        $acid_link   = $conf->get_conf('acid_link');
        $acid_prefix = $conf->get_conf('event_viewer');
        $pattern     = "/(\d+)-(\d+)-(\d+) (\d+):(\d+)/";
        /*
        * regs[1] => year
        * regs[2] => month
        * regs[3] => day
        * regs[4] => hour
        * regs[5] => minute
        */
        preg_match($pattern, $date, $regs);
        /* 10 minutes before */
        if ($regs[5] >= 10)
        {
            // 3:45 -> 3:35
            $regs[5] -= 10;
        }
        else
        {
            $regs[5] = 60 - (10 - $regs[5]);
            if ($regs[4] > 0)
            {
                // 3:06 -> 2:56
                $regs[4] -= 1;
            }
            else
            {
                // 0:07 -> 23:57
                $regs[4] = 23;
            }
        }

        $link = "$acid_link" . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&time_range=today&time_cnt=1&time[0][1]=" . urlencode(
                ">="
            ) . "&time[0][2]=" . $regs[2] . "&time[0][3]=" . $regs[3] . "&time[0][4]=" . $regs[1] . "&time[0][5]=" . $regs[4] . "&time[0][6]=" . $regs[5];

        if ($target)
        {
            $link .= "&ip_addr[0][1]=$target&ip_addr[0][2]==&ip_addr[0][3]=$ip&";
        }

        $link .= "&sort_order=time_a&" . "submit=Query+DB&num_result_rows=-1&ip_addr_cnt=1";

        return str_replace("//", "/", $link);
    }

    /**
     * Function get_acid_single_event_link
     *
     * PRE: $date must be in format YYYY-MM-DD HH:MM
     *
     * @param string $event_id
     *
     * @return string
     */
    public static function get_acid_single_event_link($event_id)
    {
        $conf        = $GLOBALS['CONF'];
        $acid_link   = $conf->get_conf('acid_link');
        $acid_prefix = $conf->get_conf('event_viewer');

        $link = "$acid_link" . "/" . $acid_prefix . "_qry_alert.php?submit=%230-";
        $link .= "$event_id&clear_allcriteria=1";

        return str_replace("//", "/", $link);
    }

    /**
     * Function get_acid_pair_link
     *
     * PRE: $date must be in format YYYY-MM-DD HH:MM
     *
     * @param string $date Date string
     * @param string $ip   Ip string
     * @param string $ip2  Ip string
     *
     * @return string
     */
    public static function get_acid_pair_link($date, $ip = '', $ip2 = '')
    {
        $conf        = $GLOBALS['CONF'];
        $acid_link   = $conf->get_conf('acid_link');
        $acid_prefix = $conf->get_conf('event_viewer');
        $pattern     = "/(\d+)-(\d+)-(\d+) (\d+):(\d+)/";

        /*
        * regs[1] => year
        * regs[2] => month
        * regs[3] => day
        * regs[4] => hour
        * regs[5] => minute
        */
        preg_match($pattern, $date, $regs);

        /* 10 minutes before */
        if ($regs[5] >= 10)
        {
            // 3:45 -> 3:35
            $regs[5] -= 10;
        }
        else
        {
            $regs[5] = 60 - (10 - $regs[5]);
            if ($regs[4] > 0)
            {
                // 3:06 -> 2:56
                $regs[4] -= 1;
            }
            else
            {
                // 0:07 -> 23:57
                $regs[4] = 23;
            }
        }

        $link = "$acid_link" . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&time_range=today&time_cnt=1&time[0][1]=" . urlencode(
                ">="
            ) . "&time[0][2]=" . $regs[2] . "&time[0][3]=" . $regs[3] . "&time[0][4]=" . $regs[1] . "&time[0][5]=" . $regs[4] . "&time[0][6]=" . $regs[5];
        $link .= "&ip_addr[0][1]=ip_src&ip_addr[0][2]==&ip_addr[0][3]=$ip&ip_addr[0][9]=AND";
        $link .= "&ip_addr[1][1]=ip_dst&ip_addr[1][2]==&ip_addr[1][3]=$ip2";
        $link .= "&sort_order=time_a&submit=Query+DB&num_result_rows=-1&ip_addr_cnt=2";

        return str_replace("//", "/", $link);
    }

    /**
     * Function get_acid_events_link
     *
     * PRE: $since and $last must be in format YYYY-MM-DD HH:MM:SS
     *      $order must be "time_d" or "time_a"
     *      $target must be "ip_src", "ip_dst" or "ip_both"
     *
     * @param string $since  Date string
     * @param string $last   Date string
     * @param string $order  Order string
     * @param string $ip     Ip string
     * @param string $target Target string
     *
     * @return mixed
     */
    public static function get_acid_events_link($since, $last, $order = 'time_d', $ip = '', $target = 'ip_both')
    {
        $conf        = $GLOBALS['CONF'];
        $acid_link   = $conf->get_conf('acid_link');
        $acid_prefix = $conf->get_conf('event_viewer');
        $pattern     = "/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/";
        /*
        * regs[1] => year
        * regs[2] => month
        * regs[3] => day
        * regs[4] => hour
        * regs[5] => minute
        * regs[6] => secs
        */
        preg_match($pattern, $since, $regs_since);
        preg_match($pattern, $last, $regs_last);

        $link = "$acid_link" . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&time_range=today&time_cnt=2" . "&time[0][0]=&time[0][1]=" . urlencode(
                ">="
            ) . "&time[0][2]=" . $regs_since[2] . "&time[0][3]=" . $regs_since[3] . "&time[0][4]=" . $regs_since[1] . "&time[0][5]=" . $regs_since[4] . "&time[0][6]=" . $regs_since[5] . "&time[0][7]=" . $regs_since[6] . "&time[0][9]=AND" . "&time[1][0]=" . "&time[1][1]=" . urlencode(
                "<="
            ) . "&time[1][2]=" . $regs_last[2] . "&time[1][3]=" . $regs_last[3] . "&time[1][4]=" . $regs_last[1] . "&time[1][5]=" . $regs_last[4] . "&time[1][6]=" . $regs_last[5] . "&time[1][7]=" . $regs_last[6] . "&time[1][8]=&time[1][9]=";

        if ($ip)
        {
            $link .= "&ip_addr[0][1]=$target&ip_addr[0][2]==&ip_addr[0][3]=$ip";
        }

        $link .= "&sort_order=$order&" . "submit=Query+DB&num_result_rows=-1&ip_addr_cnt=1";

        return str_replace("//", "/", $link);
    }

    /**
     * Function get_number_mark
     *
     * @return string
     */
    public static function get_number_mark()
    {
        $locale = (isset($_COOKIE['locale']) ? $_COOKIE['locale'] : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $lang   = explode(",", $locale);
        $lang   = strtolower(substr($lang[0], 0, 2));

        $set_lang = array(
            'en' => 1,
            'ja' => 1,
            'zh' => 1,
            'ko' => 1
        );

        if ($set_lang[$lang])
        {
            $decimal   = '.';
            $thousands = ',';
        }
        else
        {
            $decimal   = ',';
            $thousands = '.';
        }

        return array(
            $decimal,
            $thousands
        );

        /*
            Another options to get the locale:
            setlocale(LC_NUMERIC, 0); //Get the server locale
            localeconv(); //Get the default numeric settings for the current locale
        */
    }

    /**
     * Function number_format_locale
     *
     * @param int|string $number
     * @param int        $decimals
     *
     * @return string
     */
    public static function number_format_locale($number, $decimals = 0)
    {
        list($decimal, $thousands) = self::get_number_mark();

        return number_format($number, $decimals, $decimal, $thousands);
    }

    /**
     * Function number_format_readable
     *
     * Returns human readable format number
     *
     * @param float|string $number
     *
     * @return string
     */
    public static function number_format_readable($number)
    {
        // human readable format -- powers of 1024
        //
        if($number == 0){
            return '0';
        }

        $power = 1000; // 1024
        $unit  = array('', 'K+', 'M+', 'G+', 'T+', 'P+', 'E+');

        return @floor(
                $number / pow($power, ($i = floor(log($number, $power))))
            ) . $unit[$i];
    }

    /**
     * Function bytes_to_size
     *
     * Returns human readable format number of bytes
     *
     * @param float|string $bytes
     *
     * @return string
     */
    public static function bytes_to_size($bytes, $precision = 2)
    {
        if (0 == $bytes)
        {
            return '0 B';
        }

        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $k = 1024;
        $i = floor(log($bytes) / log($k));

        return number_format(($bytes / pow($k, $i)), $precision).' '.$sizes[$i];
    }

    /**
     * Function make_form
     *
     * Draws a html form
     *
     * @param string $method
     * @param string $action
     */
    public static function make_form($method, $action, $target='', $button_name='Back')
    {
        echo "<div style='margin:auto; width:90%'>
                <form method='$method' action='$action' target='$target'>
                    <div style='margin:auto; text-align: center;'>
                        <input type='submit' name='send' id='send' class='button' value='" . _($button_name) . "'/>
                    </div>
                </form>
            </div>";
    }

    /**
     * Function clean_array
     *
     * Returns a clean version of an array
     *
     * @param array $array
     *
     * @return array|bool
     */
    public static function clean_array($array)
    {
        $aux = array();

        if (is_array($array))
        {
            foreach ($array as $k => $v)
            {
                if ($v != '' || is_array($v) || is_object($v))
                {
                    $aux[$k] = $v;
                }
            }

            return $aux;
        }

        return FALSE;
    }

    /**
     * Function print_error
     *
     * Prints an error message through Notification class
     * specifying the width property
     *
     * @param string $message
     */
    public static function print_error($message)
    {
        $config_nt = array(
            'content' => $message,
            'options' => array(
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }

    /**
     * Function print_successful
     *
     * Prints an error message through Notification class
     *
     * @param string $message
     * @param string $width
     */
    public static function print_successful($message, $width = "80%")
    {
        $config_nt = array(
            'content' => $message,
            'options' => array(
                'type'          => 'nf_success',
                'cancel_button' => FALSE
            ),
            'style'   => "width: $width; margin: 20px auto; text-align: left;"
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }


    /**
    * Function signaturefilter
    *
    * Removes from the string 'directive_event: ' and 'rrd_anomaly: '
    *
    * @param string $data
    *
    * @return string
    */
    public static function signaturefilter($data)
    {
        $out = str_replace('directive_event: ', '', $data);
        $out = str_replace('rrd_anomaly: ', '', $out);

        return $out;
    }


    /**
     * Function utf8_encode2
     *
     * Returns an utf8 encoded string
     *
     * @param string $string
     *
     * @return string
     */
    public static function utf8_encode2($string)
    {
        return (self::is_utf8($string)) ? $string : utf8_encode($string);
    }

    /**
     * Function utf8_decode2
     *
     * Returns an utf8 not encoded string
     *
     * @param string $string
     *
     * @return string
     */
    public static function utf8_decode2($string)
    {
        return (self::is_utf8($string)) ? utf8_decode($string) : $string;
    }


    /**
     * Function htmlentities
     *
     * @param string    $string   String to convert
     * @param integer   $flag     [Optional]  A bitmask which specify how to handle quotes, invalid code unit sequences, ...
     * @param string    $charset  [Optional]  Encoding used when converting characters.
     *
     * @return string
     */
    public static function htmlentities($string, $flag = ENT_QUOTES, $charset = 'ISO-8859-1', $double_encode = FALSE)
    {
        if (mb_detect_encoding($string.' ', 'UTF-8,ISO-8859-1') == 'ISO-8859-1')
        {
            $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
        }

        $string = mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');

        if ($double_encode)
        {
            $rpl    = array("&lt;" => "&amp;lt;", "&gt;" => "&amp;gt;");
            $string = strtr($string, $rpl);
        }

        return @htmlentities($string, $flag, $charset, FALSE);
    }


    /**
     * Function htmlentities_url
     *
     * This function is sensible with the complex URLs with parameters
     * It doesn't encode the ampersand character to &amp;
     *
     * @param string $string
     *
     * @return string
     */
    public static function htmlentities_url($string)
    {
        return str_replace("&amp;", "&", self::htmlentities($string));
    }


    /**
     * Function js_entities
     *
     * @param string $string
     *
     * @return string
     */
    public static function js_entities($string)
    {
        $str = $string;
        if (preg_match('/&#\d{4,5};|&#x[a-f0-9]{4};/i', $string))
        {
            $str = mb_convert_encoding($string, "UTF-8", "HTML-ENTITIES");
            $str = json_encode($str);
            $str = substr($str, 1, -1);
        }
        else
        {
            $str = mb_convert_encoding(html_entity_decode($string), "UTF-8", "ISO-8859-1");
            $str = json_encode($str);
            $str = substr($str, 1, -1);
        }

        return $str;
    }

    /**
     * Function is_utf8
     *
     * Checks if a string is in UTF-8 encoding
     *
     * @param string $str
     *
     * @return bool
     */
    public static function is_utf8($str)
    {
        $cur_encoding = mb_detect_encoding($str . ' ');

        if ($cur_encoding == "UTF-8" && mb_check_encoding($str . " ", "UTF-8"))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Function utf8entities
     *
     * PRE: $str must be in UTF-8 encoding
     *
     * @param string $str
     *
     * @return string
     */
    public static function utf8entities($str)
    {
        $str = @mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
        $str = preg_replace('/&#(\d{4,5});/', '&amp;#$1;', $str);

        return self::html_entities2utf8($str);

        //return html_entity_decode(preg_replace('/&#(\d{4,5});/', '&amp;#$1;', @mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8')));
    }

    /**
     * Function html_entities2utf8
     *
     * PRE: $str must be in html_entities encoding
     *
     * @param string $str
     *
     * @return string
     */
    public static function html_entities2utf8($str)
    {
        return mb_convert_encoding($str,  'UTF-8', 'HTML-ENTITIES');
    }


    /** TIMEZONE FUNCTIONS **/

    /**
     * Function timezone
     *
     * @param string $tz
     *
     * @return string
     */
    public static function timezone($tz)
    {
        $arr = array(
            '-12'   => 'GMT-12:00',
            '-11'   => 'GMT-11:00',
            '-10'   => 'GMT-10:00',
            '-9.5'  => 'GMT-9:30',
            '-9'    => 'GMT-9:00',
            '-8'    => 'GMT-8:00',
            '-7'    => 'GMT-7:00',
            '-6'    => 'GMT-6:00',
            '-5'    => 'GMT-5:00',
            '-4.5'  => 'GMT-4:30',
            '-4'    => 'GMT-4:00',
            '-3.5'  => 'GMT-3:30',
            '-3'    => 'GMT-3:00',
            '-2'    => 'GMT-2:00',
            '-1'    => 'GMT-1:00',
            '0'     => 'UTC',
            '1'     => 'GMT+1:00',
            '2'     => 'GMT+2:00',
            '3'     => 'GMT+3:00',
            '3.5'   => 'GMT+3:30',
            '4'     => 'GMT+4:00',
            '4.5'   => 'GMT+4:30',
            '5'     => 'GMT+5:00',
            '5.5'   => 'GMT+5:30',
            '5.75'  => 'GMT+5:45',
            '6'     => 'GMT+6:00',
            '6.5'   => 'GMT+6:30',
            '7'     => 'GMT+7:00',
            '8'     => 'GMT+8:00',
            '8.75'  => 'GMT+8:45',
            '9'     => 'GMT+9:00',
            '9.5'   => 'GMT+9:30',
            '10'    => 'GMT+10:00',
            '10.5'  => 'GMT+10:30',
            '11'    => 'GMT+11:00',
            '11.5'  => 'GMT+11:30',
            '12'    => 'GMT+12:00',
            '12.75' => 'GMT+12:45',
            '13'    => 'GMT+13:00',
            '14'    => 'GMT+14:00'
        );

        return $arr["$tz"];
    }

    /**
     * Function get_tzc
     *
     * Get +TZ.NN variable from timezone for MySQL
     *
     * @param NULL|string $tz
     *
     * @return string
     */
    public static function get_tzc($tz = NULL)
    {
        if (empty($tz))
        {
            $tz = self::get_timezone();
        }

        if (preg_match("/(.*)\.(.*)/", "$tz", $fnd))
        {
            $fnd[1] = ($fnd[1] > 0) ? "+" . $fnd[1] : $fnd[1];
            $fnd[2] = ($fnd[2] > 9) ? $fnd[2] : $fnd[2] . "0";

            $min = (intval($fnd[2]) * 60 / 100);
            $tzc = $fnd[1] . ":" . $min;
        }
        else
        {
            $tzc = ($tz >= 0) ? "+$tz:00" : "$tz:00";
        }

        return $tzc;
    }


    /**
    * Function get_utc_unixtime
    *
    * Convert date to UTC unixtime
    *
    * @param string $date Date string
    *
    * @return int
    */
    public static function get_utc_unixtime($date)
    {
        return strtotime($date . ' GMT');
    }

    public static function format_payload_external($binary) {
        $id = rand(0,999).time();
        $tmpfile = "/var/tmp/base_packet_" . $id . ".pcap";
        $cmd = "/usr/share/ossim/scripts/snortlogtopcap.py -u ? -p ?";
        self::execute_command("$cmd > /dev/null 2>&1", array($binary, $tmpfile));
        $bin = file_get_contents($tmpfile);
        unlink($tmpfile);
        return $bin;
    }

    /**
    * Function get_unixtime_utc
    *
    * Get UTC unixtime using date command
    *
    * @return int
    */
    public static function get_unixtime_utc()
    {
        return strtotime(trim(self::execute_command('date -u -R', FALSE, 'string')));
    }

    /**
     * Function utc_from_localtime
     *
     * @return string
     */
    public static function utc_from_localtime($localdate)
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $localdate, new DateTimeZone(trim(`head -1 /etc/timezone`)));
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Function get_datetime_utc
     *
     * @return string
     */
    public static function get_datetime_utc()
    {
        $db   = new ossim_db();
        $conn = $db->connect();

        $unix = gmdate('Y-m-d H:i:s');
        $conn->Execute("SET SESSION time_zone='+00:00'");

        $rs = $conn->Execute("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:%i:%s')");

        if (!$rs)
        {
            $db->close();

            return $unix;
        }

        if (!$rs->EOF)
        {
            $unix = $rs->fields[0];
        }


        $db->close();

        return $unix;
    }

    /**
     * Function get_utc_date_calc
     *
     * Get the next UTC date to schedule tasks
     *
     * @param object $conn DB object
     * @param string $date
     * @param string $interval
     *
     * @return mixed
     */
    public static function get_utc_date_calc($conn, $date, $interval)
    {
        $now = gmdate("U");
        $conn->Execute("SET SESSION time_zone='+00:00'");

        do
        {
            $rs = $conn->Execute("select DATE_ADD(\"$date\", INTERVAL $interval)");

            if (!$rs)
            {
                return $date;
            }

            if (!$rs->EOF)
            {
                $date = $rs->fields[0];
            }

            $time = strtotime("$date UTC");
        }
        while ($time < $now); // always a date in the future

        return $date;
    }


    // Convert date to UTC using mysql
    public static function get_utc_from_date($conn, $date, $tz)
    {
        $utc = $date;

        // Convert timezone to mysql format first
        $tz = self::get_tzc($tz);

        if (method_exists($conn, 'baseExecute'))
        {
            // Called from forensics
            $rs = $conn->baseExecute("SELECT CONVERT_TZ('$date','$tz','+00:00') AS utc");

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if ($row = $rs->baseFetchRow())
                {
                    $utc = $row['utc'];
                }
            }
        }
        else
        {
            // Called from other interface section
            $rs = $conn->Execute("SELECT CONVERT_TZ('$date','$tz','+00:00') AS utc");

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $utc = $rs->fields['utc'];
                }
            }
        }

        $time   = preg_split("/[\s\-\:]/", $utc);
        $time[] = $utc;

        // return $y,$m,$d,$h,$u,$s,$utc
        return $time;
    }


    /**
     * Function get_date_from_utc()
     *
     * @param string $date    Date to format
     * @param array  $params  Array of params (format and empty_msg)
     *
     * @return string
     */
    public static function get_date_from_utc($date, $params = array())
    {
        $format = ($params['format'])    ? $params['format']    : 'Y-m-d H:i:s';
        $msg    = ($params['empty_msg']) ? $params['empty_msg'] : '';

        if (!empty($date))
        {
            return gmdate($format, strtotime($date . ' GMT') + (3600 * self::get_timezone()));
        }
        else
        {
            return $msg;
        }
    }

    /**
     * Function get_timezone()
     *
     * @return float
     */
    public static function get_timezone()
    {
        return floatval($_SESSION['_timezone']);
    }

    /**
     * Function get_ossim_url
     *
     * Get current ossim complete url
     *
     * @return string
     */
    public static function get_ossim_url()
    {
        $ossim_link = AV_MAIN_PATH;
        if ($ossim_link == '')
        {
            $conf       = $GLOBALS['CONF'];
            $ossim_link = ($conf->get_conf('ossim_link') != '') ? $conf->get_conf('ossim_link') : "/ossim";
        }

        $ip          = self::get_default_admin_ip();
        $current_url = 'http' . ($_SERVER['HTTPS'] == 'on' ? 's' : '') . '://' . $ip . $ossim_link;

        return preg_replace("/\/$/", "", $current_url);
    }

    /**
     * Function hex2bin
     *
     * Convert hex to bin
     *
     * @param string $h
     *
     * @return null|string
     */
    public static function hex2bin($h)
    {
        if (!is_string($h))
        {
            return NULL;
        }

        $r = '';

        for ($a = 0; $a < strlen($h); $a += 2)
        {
            $r .= chr(hexdec($h{$a} . $h{($a + 1)}));
        }

        return $r;
    }


    /** FAKE PASSWORD FUNCTIONS **/

    /**
     * Function fake_pass
     *
     * @param string $pass
     *
     * @return string
     */
    public static function fake_pass($pass)
    {
        $fakepass = '';

        for ($i = 0; $i < strlen($pass); $i++)
        {
            $fakepass .= '?';
        }

        return $fakepass;
    }

    /**
     * Function is_fake_pass
     *
     * @param string $fakepass
     *
     * @return bool
     */
    public static function is_fake_pass($fakepass)
    {
        return preg_match("/^\?+$/", $fakepass);
    }


    public static function get_system_uuid()
    {
        $uuid = strtoupper(trim(self::execute_command('/usr/bin/alienvault-system-id', FALSE, 'string')));

        if (security_class::valid_uuid($uuid) !== TRUE)
        {
            $uuid = self::get_encryption_key();
        }

        return $uuid;
    }


    public static function get_encryption_key()
    {
        $db   = new ossim_db();
        $conn = $db->connect();

        $query = "SELECT value FROM config WHERE conf='encryption_key'";

        if ($rs = $conn->Execute($query))
        {
            $uuid = $rs->fields['value'];
        }

        if ($uuid == '')
        {
            if (file_exists("/etc/ossim/framework/db_encryption_key"))
            {
                $uuid = @trim(
                    self::execute_command('grep "^key=" /etc/ossim/framework/db_encryption_key | awk \'BEGIN { FS = "=" } ; {print $2}\'', FALSE, 'string')
                );
            }
            else
            {
                $uuid = @trim(self::execute_command('sudo dmidecode -s system-uuid', FALSE, 'string'));
            }
        }

        $db->close();

        return $uuid;
    }


    public static function uuid($format = 'int')
    {
        $chars = md5(uniqid(mt_rand(), TRUE));

        if ($format == "dashes")
        {
            $uuid = substr($chars, 0, 8) . '-' . substr($chars, 8, 4) . '-' . substr($chars, 12, 4) . '-' . substr($chars, 16, 4) . '-' . substr($chars, 20,12);
        }
        else
        {
            $uuid = strtoupper($chars);
        }

        return $uuid;
    }


    /**
     * Function uuid_format
     *
     * @param string $uuid
     *
     * @return string
     */
    public static function uuid_format($uuid)
    {
        $uuid = strtolower($uuid);

        if (preg_match("/(........)-(....)-(....)-(....)-(............)/", $uuid) == FALSE)
        {
            $uuid = preg_replace("/(........)(....)(....)(....)(............)/", "\\1-\\2-\\3-\\4-\\5", $uuid);
        }

        return $uuid;
    }

    /**
     * Function uuid_format_nc
     *
     * Format a canonical UUID into a non-canonical format
     *
     * @param string $uuid
     *
     * @return string
     */
    public static function uuid_format_nc($uuid)
    {

        return strtoupper(str_replace('-', '', $uuid));
    }

    /**
     * Function utf8_wordwrap
     *
     * @param string $str
     * @param int    $width
     * @param string $break
     * @param bool   $cut
     *
     * @return string
     */
    public static function utf8_wordwrap($str, $width, $break, $cut = FALSE)
    {
        if (!$cut)
        {
            $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . ',}\b#U';
        }
        else
        {
            $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . '}#';
        }

        if (function_exists('mb_strlen'))
        {
            $str_len = mb_strlen($str, 'UTF-8');
        }
        else
        {
            $str_len = preg_match_all('/[\x00-\x7F\xC0-\xFD]/', $str);
        }

        $while_what = ceil($str_len / $width);

        $i      = 1;
        $return = '';

        while ($i < $while_what)
        {
            preg_match($regexp, $str, $matches);

            $string = $matches[0];
            $return .= $string . $break;
            $str = substr($str, strlen($string));

            $i++;
        }

        return $return . $str;
    }


    public static function wordwrap($str, $width, $break, $cut = TRUE)
    {
        # Fix chinese entities
        $words = explode(' ', $str);
        $str   = '';
        foreach ($words as $w)
        {
            $w = self::utf8_wordwrap(mb_convert_encoding($w, "UTF-8", "HTML-ENTITIES"), $width, $break, $cut);
            $str .= " $w";
        }

        return mb_convert_encoding(ltrim($str), "HTML-ENTITIES", "UTF-8");
    }

    public static function wordwrap_iso($str, $width, $break, $cut = TRUE)
    {
        # Fix chinese entities
        $str = wordwrap($str, $width, $break, $cut);

        $str = preg_replace("/(;)(\s+|<br\/>)(&#\d+;)/", ";\\1\\3", $str);
        $str = preg_replace("/(&)(\s+|<br\/>)(#\d+;)/", "\\1\\3", $str);
        $str = preg_replace("/(&#)(\s+|<br\/>)(\d+;)/", "\\1\\3", $str);
        $str = preg_replace("/(&#\d+)(\s+|<br\/>)(\d+;)/", "\\1\\3", $str);
        $str = preg_replace("/(&#\d+)(\s+|<br\/>)(;)/", "\\1\\3", $str);
        $str = preg_replace("/(&#\d+;)(\s+|<br\/>)(&)/", "\\1\\3", $str);

        return $str;
    }

    /**
     * Function get_gettext_from_string
     *
     * @param string $delimiter
     * @param string $string
     *
     * @return string
     */
    public static function get_gettext_from_string($delimiter, $string)
    {
        $delimiter = ($delimiter == '') ? '-' : $delimiter;
        $odata     = array();
        $idata     = explode($delimiter, $string);

        foreach ($idata as $data)
        {
            $odata[] = _(trim($data));
        }

        return (implode(' ' . $delimiter . ' ', $odata));
    }


    /**
     * Function get_mail_params
     *
     * This function gets mail parameters (by default or mail server relay if it was configured)
     * from AlienVault Component
     *
     * @param object $conn Database access object
     * @param string $uuid [Optional] AlienVault Component UUID
     *
     * @return array
     * @throws Exception
     */
    public static function get_mail_params($conn, $uuid = '')
    {
        //Getting user and email params
        $conf = Config::factory();
        $from_user = $conf->get_conf('default_sender_email_address');
        $from_user_name = 'AlienVault';

        //Default SMTP params
        $data['default_smtp'] = array(
            'from_user'      => $from_user,
            'from_user_name' => $from_user_name,
            'server'         => 'localhost',
            'user'           => '',
            'pass'           => '',
            'port'           => '25'
        );

        //External SMTP params (Mail Relay)
        $data['external_smtp'] = array();


        if ($uuid == '')
        {
            // Get the first uuid in avcenter.current_local
            $avcenters = Av_center::get_avc_list($conn);

            if ($avcenters['status'] == 'success')
            {
                $av_data = array_shift($avcenters['data']);
                $uuid    = $av_data['system_id'];
            }

            // Get the system uuid
            if ($uuid == '')
            {
                $uuid = self::get_system_uuid();
            }
        }

        $uuid = trim($uuid);

        if ($uuid == '')
        {
            return $data;
        }

        $current_data = Av_center::get_config_data($uuid);

        if ($current_data['status'] == 'success')
        {
            if (!empty($current_data['data']['general_mailserver_relay']) && $current_data['data']['general_mailserver_relay'] != 'no')
            {

                $data['external_smtp'] = array(
                    'from_user'      => $from_user,
                    'from_user_name' => $from_user_name,
                    'server'         => $current_data['data']['general_mailserver_relay'],
                    'user'           => $current_data['data']['general_mailserver_relay_user'],
                    'pass'           => $current_data['data']['general_mailserver_relay_passwd'],
                    'port'           => $current_data['data']['general_mailserver_relay_port']
                );

                if ($data['external_smtp']['user'] == 'unconfigured')
                {
                    $data['external_smtp']['user'] = '';
                    $data['external_smtp']['pass'] = '';
                }
            }
        }

        return $data;
    }


    /**
     * This function sends email using PHPMailer library
     *
     * @param object $conn        Database access object
     * @param string $email       Email receptor
     * @param string $subject     Email subject
     * @param string $email_data  Email data
     * @param array  $attachments [Optional] Attached files
     *
     * @return boolean
     */
    public static function send_email($conn, $email, $subject, $email_data, $attachments = array())
    {
        $mail_params = self::get_mail_params($conn);

        $to      = $email;
        $to_name = $email;

        $body    = $email_data;

        require_once __DIR__ . '/PHPMailer.inc';
        require_once __DIR__ . '/PHPMailerSMTP.inc'; // Optional, gets called from within class.phpmailer.php if not already loaded

        $mail = new PHPMailer(TRUE); // the TRUE param means it will throw exceptions on errors, which we need to catch

        $mail->IsSMTP();
        $mail->CharSet = "UTF-8";

        try
        {
            $mail->SMTPDebug = 0; // enables SMTP debug information (for testing)

            $smtp_params    = $mail_params['default_smtp'];
            $mail->Host     = $smtp_params['server']; // SMTP server
            $mail->SMTPAuth = FALSE; // enable SMTP authentication
            $mail->Username = $smtp_params['user']; // SMTP account username
            $mail->Password = $smtp_params['pass']; // SMTP account password
            $mail->Port     = $smtp_params['port']; // SMTP port

            $array_to   = explode(';', $to);
            $array_name = explode(';', $to_name);


            foreach ($array_to as $at_key => $at_email)
            {
                $mail->AddAddress(trim($at_email), trim($array_name[$at_key]));
            }

            $mail->SetFrom($smtp_params['from_user'], $smtp_params['from_user_name']);
            $mail->Subject = $subject;
            $mail->AltBody = _(
                "To view the message, please use an HTML compatible email viewer!"
            ); // optional - MsgHTML will create an alternate automatically


            //Replace image src
            preg_match_all('/src="([^"]+)"/', $body, $match);

            $size = count($match[1]);
            for ($i = 0; $i < $size; $i++)
            {
                $pattern  = "/" . preg_quote($match[1][$i], "/") . "/";
                $key      = "et_img_" . $i;
                $new_path = "cid:$key";
                $mail->AddEmbeddedImage(
                    "/usr/share/ossim/www/uploads/" . basename($match[1][$i]),
                    $key,
                    basename($match[1][$i])
                );
                $body = preg_replace($pattern, $new_path, $body);
            }
            $mail->MsgHTML($body);

            if (is_array($attachments) && count($attachments) > 0)
            {
                foreach ($attachments as $attached)
                {
                    if (file_exists($attached['path']))
                    {
                        $mail->AddAttachment($attached['path'], $attached['name']);
                    }
                }
            }

            if ($mail->Send())
            {
                return TRUE;
            }
            else
            {
                //Try again with default SMTP Server (Local Postfix)
                if (is_array($mail_params['external_smtp']) && !empty($mail_params['external_smtp']))
                {
                    $smtp_params = $mail_params['external_smtp'];

                    if (!empty($smtp_params['user']))
                    {
                        $mail->SMTPAuth = TRUE; // Enable SMTP authentication
                        $mail->Username = $smtp_params['user']; // SMTP account username
                        $mail->Password = $smtp_params['pass']; // SMTP account password
                    }
                    else
                    {
                        $mail->SMTPAuth = FALSE; // Disable SMTP authentication
                        $mail->Username = ''; // SMTP account username
                        $mail->Password = ''; // SMTP account password
                    }


                    $mail->Host = $smtp_params['server']; // SMTP server
                    $mail->Port = $smtp_params['port']; // SMTP port

                    $mail->SetFrom($smtp_params['from_user'], $smtp_params['from_user_name']);

                    if ($mail->Send())
                    {
                        return TRUE;
                    }
                }
            }
            return FALSE;
        }
        catch (phpmailerException $e)
        {
            echo $e->errorMessage(); //Pretty error messages from PHPMailer

            return FALSE;
        }
        catch (Exception $e)
        {
            echo $e->getMessage(); //Boring error messages from anything else!

            return FALSE;
        }
    }


    // multiarray quicksort
    public static function qsort2(&$array, $column = 0, $order = SORT_ASC, $first = 0, $last = -2)
    {
        // $array  - the array to be sorted
        // $column - index (column) on which to sort can be a string if using an asso
        // $order  - SORT_ASC (default) for ascending or SORT_DESC for descending
        // $first  - start index (row) for partial array sort
        // $last  - stop  index (row) for partial array sort

        if ($last == -2)
        {
            $last = count($array) - 1;
        }


        if ($last > $first)
        {
            $alpha = $first;
            $omega = $last;
            $guess = $array[$alpha][$column];

            while ($omega >= $alpha)
            {
                #if($order == SORT_ASC) {
                #   while($array[$alpha][$column] < $guess) $alpha++;
                #   while($array[$omega][$column] > $guess) $omega--;
                #} else {
                #   while($array[$alpha][$column] > $guess) $alpha++;
                #   while($array[$omega][$column] < $guess) $omega--;
                #}
                if ($order == SORT_ASC)
                {
                    while (strcasecmp($array[$alpha][$column], $guess) < 0)
                    {
                        $alpha++;
                    }

                    while (strcasecmp($array[$omega][$column], $guess) > 0)
                    {
                        $omega--;
                    }
                }
                else
                {
                    while (strcasecmp($array[$alpha][$column], $guess) > 0)
                    {
                        $alpha++;
                    }

                    while (strcasecmp($array[$omega][$column], $guess) < 0)
                    {
                        $omega--;
                    }
                }

                if ($alpha > $omega)
                {
                    break;
                }

                $temporary       = $array[$alpha];
                $array[$alpha++] = $array[$omega];
                $array[$omega--] = $temporary;
            }

            self::qsort2($array, $column, $order, $first, $omega);
            self::qsort2($array, $column, $order, $alpha, $last);
        }
    }

    public static function encrypt($input_string, $key)
    {
        $method = 'AES-256-ECB';
        $h_key   = hash('sha256', $key, TRUE);

        return base64_encode(openssl_encrypt($input_string, $method, $h_key) ) ;
    }


    public static function decrypt($encrypted_input_string, $key)
    {
        $method = 'AES-256-ECB';
        $h_key   = hash('sha256', $key, TRUE);

        return trim(openssl_decrypt ( base64_decode($encrypted_input_string), $method, $h_key ) );
    }

    /**
     * Function get_month_name
     *
     * @param int    $month  Month number
     * @param string $length Values: long|short
     *
     * @return string
     */
    public static function get_month_name($month, $length = 'long')
    {
        $month_long = array(
            1  => _('January'),
            2  => _('February'),
            3  => _('March'),
            4  => _('April'),
            5  => _('May'),
            6  => _('June'),
            7  => _('July'),
            8  => _('August'),
            9  => _('September'),
            10 => _('October'),
            11 => _('November'),
            12 => _('December')
        );

        $month_short = array(
            1  => _('Jan'),
            2  => _('Feb'),
            3  => _('Mar'),
            4  => _('Apr'),
            5  => _('May'),
            6  => _('Jun'),
            7  => _('Jul'),
            8  => _('Aug'),
            9  => _('Sep'),
            10 => _('Oct'),
            11 => _('Nov'),
            12 => _('Dec')
        );

        if ($month > 0 && $month < 13)
        {
            switch ($length)
            {
                case 'short':
                    return $month_short[$month];
                default:
                    return $month_long[$month];
            }
        }
        else
        {
            return _('Wrong Month');
        }

    }

    /**
     * Function get_day_name
     *
     * @param int    $day    Day number
     * @param string $length Values: long|short
     *
     * @return string
     */
    public static function get_day_name($day, $length = 'long')
    {

        $day_long = array(
            1 => _('Monday'),
            2 => _('Tuesday'),
            3 => _('Wednesday'),
            4 => _('Thursday'),
            5 => _('Friday'),
            6 => _('Saturday'),
            7 => _('Sunday')
        );

        $day_short = array(
            1 => _('Mon'),
            2 => _('Tue'),
            3 => _('Wed'),
            4 => _('Thu'),
            5 => _('Fri'),
            6 => _('Sat'),
            7 => _('Sun')
        );

        if ($day > 0 && $day < 8)
        {
            switch ($length)
            {
                case 'short':
                    return $day_short[$day];
                default:
                    return $day_long[$day];
            }
        }
        else
        {
            return _('Wrong Day');
        }
    }

    /**
     * Function create_tmp_table
     *
     * Create MySQL tmp tables
     *
     * @param object $conn DB object
     * @param string $fields
     *
     * @return string
     */
    public static function create_tmp_table($conn, $fields = "id binary(16) NOT NULL, PRIMARY KEY (id)")
    {
        $length = 10;
        $chars  = "abcdefghijklmnopqrstuvwxyz0123456789";
        $size   = strlen($chars);
        $flag   = FALSE;

        do
        {
            for ($i = 0; $i < $length; $i++)
            {
                $str .= $chars[rand(0, $size - 1)];
            }

            $tmptable = "qry_tmp_" . $str;
            $rs       = $conn->Execute("SHOW TABLES LIKE '$tmptable'");
            $flag     = ($rs->RecordCount() == 0) ? TRUE : FALSE;
        }
        while (!$flag);

        $conn->Execute("CREATE TEMPORARY TABLE IF NOT EXISTS $tmptable ($fields)");

        return $tmptable;
    }

    /**
     * Function create_sql_tmp_table
     *
     * Create MySQL tmp tables
     *
     * @param string $fields
     *
     * @return array
     */
    public static function create_sql_tmp_table($fields = "id binary(16) NOT NULL, PRIMARY KEY (id)")
    {
        $length = 10;
        $chars  = "abcdefghijklmnopqrstuvwxyz0123456789";
        $size   = strlen($chars);
        $str = '';

        for ($i = 0; $i < $length; $i++)
        {
            $str .= $chars[rand(0, $size - 1)];
        }

        $tmptable = "qry_tmp_" . $str;

        return array($tmptable, "CREATE TEMPORARY TABLE IF NOT EXISTS $tmptable ($fields)");
    }


    // Memcache Flush
    public static function memcacheFlush($cache_files = TRUE)
    {
        self::execute_command('echo "flush_all" | /bin/nc -q 2 127.0.0.1 11211 > /dev/null 2>&1');

        if ($cache_files == TRUE)
        {
            /* connect to db */

            $db   = new ossim_db(TRUE);
            $conn = $db->connect();

            $conn->Execute("REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp())");

            $db->close();
        }
    }


    /**
     * Asset dump
     *
     * @codeCoverageIgnore
     *
     */
    public static function resend_asset_dump($what = '')
    {
        if ($what == 'servers')
        {
            self::execute_command("/usr/share/ossim/scripts/assets_sync.sh ? > /var/tmp/latest_assec_sync 2>&1 &", array('restart'));
        }
        else
        {
            self::execute_command("/usr/share/ossim/scripts/assets_sync.sh > /var/tmp/latest_assec_sync 2>&1 &");
        }
    }

    /**
     * Function set_default_os
     *
     * Set 'AlienVault OS'
     *
     * @param object  $conn
     *
     * @return void
     * @throws Exception If an error occurred
     */
    public static function set_default_os($conn)
    {
        Ossim_db::check_connection($conn);

        $rs = $conn->Execute("CALL _host_default_os()");

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $rs->Close();
    }


    /**
     * Function get_sanitize_query_string
     *
     * PRE: $data must be $_GET array
     *
     * @param array $data $_GET array
     * @param bool  $prefix
     *
     * @return string
     */
    public static function get_sanitize_query_string($data, $prefix = TRUE)
    {
        $tmpu = array();

        foreach ($data as $kget => $vget)
        {
            $tmpu[] = "$kget=" . urlencode($vget);
        }

        $url = implode('&', $tmpu);

        if ($url != '' && $prefix)
        {
            $url = '?' . $url;
        }

        return $url;
    }

    /**
     * Function get_sanitize_request_uri
     *
     * PRE: $request_uri must be $_SERVER['REQUEST_URI']
     *
     * @param string $request_uri $_SERVER['REQUEST_URI']
     *
     * @return string
     */
    public static function get_sanitize_request_uri($request_uri)
    {
        $query       = parse_url($request_uri, PHP_URL_QUERY);
        $request_uri = str_replace($query, '', $request_uri); // clean query string
        $result      = array();

        $arr = explode('&', urldecode($query));
        foreach ($arr as $data)
        {
            if (trim($data) != '')
            {
                $tmp = explode('=', $data);

                $result[] = $tmp[0] . '=' . urlencode($tmp[1]);
            }
        }

        return $request_uri . implode('&', $result);
    }

    /**
     * Function regex
     *
     * Patch dangerous regex when user input
     *
     * @param string $string
     *
     * @return string
     */
    public static function regex($string)
    {
        return quotemeta(substr($string, 0, 255));
    }

    /**
     * Function strong
     *
     * @param string $string
     *
     * @return string
     */
    public static function strong($string)
    {
        return '<strong>' . $string . '</strong>';
    }


    public static function clean_tags($matched)
    {
        $attribs =
            "onclick|ondblclick|onmousedown|onmouseup|onmouseover|" .
            "onmousemove|onmouseout|onkeypress|onkeydown|onkeyup|" .
            "onload|class|id";

        $quot        = "\"|\'|\`";
        $stripAttrib = "' ($attribs)\s*=\s*($quot)(.*?)(\\2)'i";
        $clean       = stripslashes($matched [0]);
        $clean       = preg_replace($stripAttrib, '', $clean);

        return $clean;
    }


    public static function clean_js($matched)
    {
        $quot        = "\"|\'|\`";
        $stripAttrib = "'($quot)javascript:(.*?)(\\1)'i";
        $clean       = stripslashes($matched [0]);
        $clean       = preg_replace($stripAttrib, '', $clean);

        return $clean;
    }

    /**
     * There is some malicious string to force xss or sql injection attacks
     *  this strings usually contains ' or " to break the normal execution process. To avoid this
     *  this chars should be protect with \ to ensure no attack is done.
     *
     *  Example: ");alert(/XSS/);a(" => \');alert(/XSS/);a(\'
     *
     * @param String text to protect
     *
     * @return String protected test
     *
     */
    public static function sanitize_quote_protect ($string) {
        return preg_replace("#\"|'#", "\\'", $string);
    }


    public static function sanitize_xss($source, $tags = NULL)
    {
        $allowedTags = '<br><b><i><strike><strong><hr><li><ol><u><ul><a><p><span><div><h1><h2><h3><img>';
        $allowedTags = (empty($tags)) ? $allowedTags : $tags;

        $clean = strip_tags($source, $allowedTags);
        $clean = preg_replace_callback('#<(.*?)>#', "self::clean_tags", $clean);
        $clean = preg_replace_callback('#<(.*?)>#', "self::clean_js", $clean);

        return $clean;
    }

    /**
     * Function debug_permissions
     */
    public static function debug_permissions()
    {
        echo "<style type='text/css'>
                .debug_info {
                    white-space: pre-wrap;       /* css-3 */
                    white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
                    white-space: -pre-wrap;      /* Opera 4-6 */
                    white-space: -o-pre-wrap;    /* Opera 7 */
                    word-wrap: break-word;       /* Internet Explorer 5.5+ */
                    font-family: sans-serif,courier,arial;
                    font-size:9px;
                    margin:30px;
                    background-color:#f2f2f2;
                    border:1px dashed gray;
                    padding:25px;
                    border-radius: 10px;
                    -moz-border-radius: 10px;
                    -webkit-border-radius: 10px;
                }
              </style>
                ";

        echo "<div class='debug_info'>";
        print_r($_SESSION['_user_vision']);
        echo "</div>";
    }

    /**
     * Function expand_cclass
     *
     * @param string $cclass
     *
     * @return array
     */
    public static function expand_cclass($cclass)
    {
        $_cclass_a = $cclass . '.0';
        $_cclass_b = $cclass . '.255';

        return array($_cclass_a, $_cclass_b);
    }


    /**
     * This function gets current session cookie from the headers retrieved from a curl request
     *
     * @param string $headers   Headers from a request in text plain
          *
     * @return string
     */

    public static function getCookieFromHeaders($headers){
        preg_match_all("/.*Set\-Cookie\: ([^\;]+)\;.*/", $headers, $output);

        return $output[1][0];
    }


    /**
     * This function deletes current session cookie
     *
     * @param string $ip        Host IP
     * @param string $s_context Stream context
     *
     * @return void
     */
    public static function delete_session_cookie($ip, $s_context)
    {
        $url = "https://$ip/ossim/session/login.php?action=logout";

        $context = stream_context_create($s_context);

        file_get_contents($url, FALSE, $context);
    }


    /**
     * Function getHeaders
     *
     * This function returns header information from URL
     *
     * @param string $url  URL
     * @param string $login [Optional] Logged user
     * @param string $pass [Optional] User password
     * @param boolean $encrypt_pass [Optional] indicate if the password should be sent encrypted or not
     *
     * @return string Request as text plain
     *
     */
    public static function getheaders($url, $login = '', $pass = '', $encrypt_pass=false)
    {
        $result = static::getheadersAsArray($url, $login, $pass, $encrypt_pass);

        return $result["headers"] . $result["content"];
    }

    /**
     * Function getHeaders
     *
     * This function returns header information from URL
     *
     * @param string $url  URL
     * @param string $login [Optional] Logged user
     * @param string $pass [Optional] User password
     * @param boolean $encrypt_pass [Optional] indicate if the password should be sent as signed or not
     *
     * @return array with two keys, headers and content in plain text
     */
    public static function getheadersAsArray($url, $login = '', $pass = '', $encrypt_pass=false)
    {
        $c = curl_init();

        if ($login != '' && $pass != '')
        {
            // OSSIM login
            if (preg_match("/^https\:\/\//", $url))
            {
                if($encrypt_pass){
                    $pass = md5($pass);
                }

                $conf = $GLOBALS['CONF'];

                if (!$conf)
                {
                    $conf            = new Ossim_conf();
                    $GLOBALS['CONF'] = $conf;
                }

                $data = array(
                    'login' => base64_encode(
                        self::encrypt($login . '####' . $pass, $conf->get_conf('remote_key'))
                    )
                );
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, $data);

            }
            else // Basic Auth
            {
                curl_setopt($c, CURLOPT_USERPWD, "$login:$pass");
                curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }
        }
        else
        {
            curl_setopt($c, CURLOPT_NOBODY, TRUE);
        }

        curl_setopt($c, CURLOPT_HEADER, TRUE);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($c, CURLOPT_URL, $url);
        $output = curl_exec($c);
        $header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        curl_close($c);

        $headers = substr($output, 0, $header_size);
        $body = substr($output, $header_size);

        return ["headers"=>$headers, "content"=>$body];
    }

    /**
    * Function get_proxy_params
    *
    * Get Proxy Params from ossim_setup.conf
    *
    * @param object $conn DB Object
    *
    * @return array
    */
    public static function get_proxy_params($conn)
    {
        $admin_ip = self::get_default_admin_ip();
        $res      = Av_center::get_system_info_by_ip($conn, $admin_ip);

        if ($res['status'] == 'success')
        {
            $system_id    = $res['data']['system_id'];
            $current_data = Av_center::get_config_data($system_id);

            if ($current_data['status'] == 'success')
            {
                $proxy_conf = $current_data['data']['update_update_proxy'];
                $proxy_url  = $current_data['data']['update_update_proxy_dns'];
                $proxy_user = $current_data['data']['update_update_proxy_user'];
                $proxy_pass = $current_data['data']['update_update_proxy_pass'];
                $proxy_port = $current_data['data']['update_update_proxy_port'];
            }
        }

        $params['type']  = (!isset($proxy_conf) || 'disabled' == $proxy_conf) ? '' : $proxy_conf;
        $params['ip']    = (!isset($proxy_url)  || 'disabled' == $proxy_url)  ? '' : $proxy_url;
        $params['port']  = (!isset($proxy_port) || 'disabled' == $proxy_port) ? '' : $proxy_port;
        $params['login'] = (!isset($proxy_user) || 'disabled' == $proxy_user) ? '' : $proxy_user;
        $params['pass']  = (!isset($proxy_pass) || 'disabled' == $proxy_pass) ? '' : $proxy_pass;

        return $params;
    }


    // Used for remote url check in
    public static function geturlproxy($url, $params, $opts = array())
    {
        $c = curl_init();

        $proxy_port = $params['port'];
        $proxy_ip   = $params['ip'];

        if ( $params['login'] != "" ) {
            $loginpassw = $params['login'] . ':' . $params['pass'];
            curl_setopt($c, CURLOPT_PROXYUSERPWD, $loginpassw);
        }


        if ($opts['only_header'])
        {
            curl_setopt($c, CURLOPT_HEADER, TRUE);
            curl_setopt($c, CURLOPT_NOBODY, TRUE);
        }

        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($c, CURLOPT_PROXYPORT, $proxy_port);
        curl_setopt($c, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($c, CURLOPT_PROXY, $proxy_ip);

        curl_setopt($c, CURLOPT_URL, $url);

        $output = curl_exec($c);

        curl_close($c);

        return $output;
    }


    public static function generate_tmp_file($dir, $prefix, $data)
    {
        if (!empty($data))
        {
            if ($file_name = tempnam($dir, $prefix))
            {
                $path = "$file_name";

                if (file_put_contents($path, $data))
                {
                    return $path;
                }
            }
        }

        return FALSE;
    }

    /**
     * Function get_default_admin_ip
     *
     * Returns default admin ip
     *
     * @return string
     */
    public static function get_default_admin_ip()
    {
        $admin_ip = $_SERVER['SERVER_ADDR'];

        if (file_exists('/etc/ossim/ossim_setup.conf'))
        {
            $command = 'grep ^admin_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | head -1';
            $output  = self::execute_command($command, FALSE, 'string');

            if (Asset_host_ips::valid_ip($output))
            {
                $admin_ip = trim($output);
            }
        }

        return $admin_ip;
    }

    /**
     * Function get_default_hostname
     *
     * Returns Default hostname
     *
     * @return string
     */
    public static function get_default_hostname()
    {
        $hostname = '';

        if (file_exists('/etc/ossim/ossim_setup.conf')) {
            $command = 'grep ^hostname /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | head -1';
            $output  = self::execute_command($command, FALSE, 'string');
            $hostname = trim($output);
        }

        return $hostname;
    }


    /**
     * This method returns local system UUID (Alias of Util::get_system_uuid() (Ticket ENG-95685))
     *
     * @return string
     */
    public static function get_default_uuid()
    {
        return self::get_system_uuid();
    }


    /**
     * Function get_default_ctx
     *
     * This function returns the system default context
     *
     * @return string
     */
    public static function get_default_ctx()
    {
        $conf = $GLOBALS['CONF'];

        if (!$conf)
        {
            $conf            = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }

        return str_replace('-', '', strtoupper($conf->get_conf('default_context_id')));
    }


    /**
     * Function clean_wiki_tags
     *
     * @param string $str
     *
     * @return string
     */
    public static function clean_wiki_tags($str)
    {
        $wiki = new Wikiparser();

        $str = preg_replace("/^(\s*<!--wiki-->)/", '', $str);

        if (!empty($str))
        {
            $str = $wiki->parse($str);

        }

        $str = preg_replace("/<br>/", "\n", $str);

        return strip_tags($str);
    }

    /**
     * Function host_in_net
     *
     * Calculates de number of host in net
     *
     * @param string $cidr
     *
     * @return int
     */
    public static function host_in_net($cidr)
    {
        $exp = Cidr::expand_CIDR($cidr, 'SHORT', 'LONG');

        return ($exp[1] - $exp[0] - 1);
    }

    /**
     * Function print_include_files
     *
     * Print links to css/js files
     *
     * @param array  $files
     * @param string $type
     */
    public static function print_include_files($files, $type)
    {
        $file_template = '';
        $file_label    = '';
        $file_list     = array();

        if ($type == 'js')
        {
            $file_template = '<script type="text/javascript" src="####"></script>';
            $file_label    = 'js';
        }
        elseif ($type == 'css')
        {
            $file_template = '<link rel="stylesheet" type="text/css" href="####"/>';
            $file_label    = 'style';
        }

        foreach ($files as $_file)
        {
            if (trim($_file['src']) == 'av_common.css')
            {
                $_file['src'] = 'av_common.css?t=' . self::get_css_id();
            }

            if ($_file['def_path'] == TRUE)
            {
                $file       = AV_MAIN_PATH . "/$file_label/" . $_file['src'];
                $file_check = AV_MAIN_ROOT_PATH . "/$file_label/" . $_file['src'];
            }
            else
            {
                $file       = AV_MAIN_PATH . $_file['src'];
                $file_check = AV_MAIN_ROOT_PATH . $_file['src'];
            }

            $file_check = preg_replace("/(\.css|\.js|\.js\.php)\?.*/", "\\1", $file_check);

            if (file_exists($file_check))
            {
                $file_list[] = str_replace('####', $file, $file_template);
            }

        }

        echo implode("\n", $file_list) . "\n";
    }


    /**
     * Function get_css_content
     *
     * This function gets the content of CSS file
     *
     * @param string $css_path CSS path
     *
     * @return string
     */
    public static function get_css_content($css_path)
    {
        $css_content = '';

        if (is_readable($css_path))
        {
            $css_content = file_get_contents($css_path);

            // Remove comments, tabs, spaces, newlines, etc
            $css_content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_content);

            // Remove tabs, spaces, newlines, etc. */
            $css_content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $css_content);

            //Strip PHP tags
            $css_content = preg_replace('/<(\?|\%)\=?(php)?[\s\S]*?(\%|\?)>/', '', $css_content);
        }

        return $css_content;
    }


    /**
     * Function get_css_id
     *
     * This function returns a CSS identifier
     *
     * @return string
     */
    public static function get_css_id()
    {
        $css_id = uniqid();

        //Getting selected menu options
        $av_menu = @unserialize($_SESSION['av_menu']);

        if (is_object($av_menu) && !empty($av_menu))
        {
            $m_option  = $av_menu->get_m_option();
            $sm_option = $av_menu->get_sm_option();
            $h_option  = $av_menu->get_h_option();
            $l_option  = $av_menu->get_l_option();

            if (!empty($m_option) && !empty($sm_option) && !empty($h_option))
            {
                $css_id = $m_option . '-' . $sm_option . '-' . $h_option;

                if (!empty($l_option))
                {
                    $css_id .= '-' . $l_option;
                }
            }
        }

        return md5($css_id);
    }


    /**
     * Function compare_av_version
     *
     * This function compare 2 AlienVault versions
     *
     * @param string $v1 First version to compare
     * @param string $v2 Second version to compare
     *
     * @return integer
     */
    public static function compare_av_version($v1, $v2)
    {
        //Formatting first version
        $v1 = trim($v1);
        $v1 = explode('.', $v1);
        $v1 = array_pad($v1, 5, 0);
        $v1 = implode('.', $v1);

        //Formatting second version
        $v2 = trim($v2);
        $v2 = explode('.', $v2);
        $v2 = array_pad($v2, 5, 0);
        $v2 = implode('.', $v2);

        //Once we have the versions in the same format, we compare using php function
        //Returns -1 if $v1 is lower than $v2
        //Returns 0  if both versions are equals.
        //Returns 1  if $v2 is lower than $v1
        return version_compare($v1, $v2);

    }


    /**
     * This function get the ossim-cd-tools packages version
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public static function get_packages_version()
    {

        $version = self::execute_command('dpkg -l | grep ossim-cd-tools | awk \'{print $3}\' | awk -F \'-\' \'{print $1}\'', FALSE, 'string');

        return $version;

    }

    /**
    * Function group_nums
    *
    * This functions returns a grouped list of numbers
    *
    * @param array List to group
    *
    * @return array
    */
    public static function group_nums($array) {

        $ret  = array();
        $temp = array();

        foreach($array as $val)
        {
            if (next($array) == ($val + 1))
            {
                $temp[] = $val;
            }
            else if (count($temp) > 0)
            {
                $temp[] = $val;
                $ret[]  = $temp[0].'-'.end($temp);
                $temp   = array();
            }
            else
            {
                $ret[] = $val;
            }
       }
       return $ret;
    }


    /**
     * This function set @disable_calc_perms variable to enable/disable user perm calculation
     *
     * @param object   $conn            Database access object
     * @param boolean  $enable          TRUE|FALSE
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function disable_perm_triggers($conn, $enable=TRUE)
    {
        Ossim_db::check_connection($conn);

        if ($enable)
        {
            $conn->Execute("SET @disable_calc_perms=1");
        }
        else
        {
            $conn->Execute("SET @disable_calc_perms=NULL");
            $rs = $conn->Execute("CALL update_all_users()");

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $rs->Close();
        }

        return $enable;

    }

    /**
     * This function response a bad request for AJAX requests.
     *
     * @param string   $msg     Error message
     * @param integer  $code    Error HTTP code
     *
     * @return void
     */
    public static function response_bad_request($msg, $code=400)
    {
        header("HTTP/1.0 $code $msg");
        echo $msg;

        die();
    }


    /**
     * Function calculate_pagination_page
     *
     * Returns page number to paginate
     *
     * @param integer  $from   Start registry
     * @param integer  $limit  Limit of registers
     *
     * @return integer  Page number
     */
    public static function calculate_pagination_page($from, $limit)
    {
        if($from == 0 || $limit == 0){
            return 1;
        }

        return floor($from / $limit) + 1;
    }

    /**
     * Function get_sess_cookie
     *
     * Returns value for sess cookie
     *
     * @return string
     */
    public static function get_sess_cookie()
    {
        $sess_id  = @substr(preg_replace("/[^a-zA-Z0-9]/",'',$_COOKIE['sess']),0,36);

        if (empty($sess_id))
        {
            $sess_id = 'default';
        }

        return $sess_id;
    }

    /**
     * Function to execute a bash command
     *
     * @param string           $cmd                          Command to execute
     * @param array|boolean    $inputarr        [Optional]   Parameters into the command
     * @param string|boolean   $output_mode     [Optional]   Type of output of the command execution
     * @param boolean          $throw_exception [Optional]   Enable or disable exception throwing
     * @param int|boolean      $return_var      [Optional]   Return code of execution when mode is 'array'
     *
     * @return Output of command execution depending on $output_mode
     *         - $output_mode = FALSE:    Last line. system() call
     *         - $output_mode = 'string': Entire output into a string. shell_exec() call
     *         - $output_mode = 'array':  Entire output into an array. exec() call
     */
    public static function execute_command($cmd, $inputarr = FALSE, $output_mode = FALSE, $throw_exception = TRUE, &$return_var = FALSE)
    {
        if ($inputarr)
        {
            if (!is_array($inputarr))
            {
                $inputarr = array($inputarr);
            }

            $_cmdarr  = explode('?', $cmd);
            $_nparams = sizeof($_cmdarr) - 1;

            if ($_nparams != count($inputarr))
            {
                Av_exception::throw_error(Av_exception::USER_ERROR, _('Unable to execute command'));
            }

            $cmd = $_cmdarr[0];

            for ($i = 0; $i < $_nparams; $i++)
            {
                $cmd .= escapeshellarg($inputarr[$i]);

                $cmd .= $_cmdarr[$i + 1];
            }
        }


        // Debug
        if (file_exists('/tmp/debug_cmd'))
        {
            file_put_contents("/tmp/cmd", "$cmd\n", FILE_APPEND);
        }


        // Command output in string
        if ($output_mode == 'string')
        {
            $output = shell_exec($cmd);
        }
        // Command output in array
        elseif ($output_mode == 'array')
        {
            exec($cmd, $output, $return_var);

            if ($return_var > 1)
            {
                if ($throw_exception)
                {
                    Av_exception::throw_error(Av_exception::USER_ERROR, _('Error executing command'));
                }
                else
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, _('Error executing command'));
                }
            }
            elseif ($return_var > 0)
            {
                if (file_exists('/tmp/debug_cmd'))
                {
                    file_put_contents("/tmp/cmd", "Last return value = 1\n", FILE_APPEND);
                }
                // Av_exception::write_log(Av_exception::USER_ERROR, _('Warning executing command, return value = 1'));
            }
        }
        // Only execute
        else
        {
            $output = system($cmd);

            if ($output === FALSE)
            {
                if ($throw_exception)
                {
                    Av_exception::throw_error(Av_exception::USER_ERROR, _('Error executing command'));
                }
                else
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, _('Error executing command'));
                }
            }
        }

        return $output;
    }


    public static function get_chart_colors()
    {
        return array('#FAC800','#7D71BD', '#2FC9E5', '#B722E8', '#FF8A00', '#F65DC9', '#1E2AD1', '#837B67', '#9455F8', '#1881FA');
    }

    public static function get_last_log_file() {
        $latest_ctime = 0;
        $latest_filename = '';
        $d = dir(LAST_UPDATE);
        while (false !== ($entry = $d->read())) {
            $filepath = LAST_UPDATE."/{$entry}";
            if (!is_file($filepath) || filectime($filepath) <= $latest_ctime || !preg_match(UPDATE_LOG_PATTERN,$entry)) {
                continue;
            }
            $latest_ctime = filectime($filepath);
            $latest_filename = $filepath;
        }
        return $latest_filename;
    }

    public function read_last_line($file) {
        if (!file_exists($file)) {
            return 0;
        }
        $fp = fopen($file, 'r');
        if (!$fp) {
            return 0;
        }
        $eol = "\n";
        $pos = -1; $line = ''; $c = '';
        do {
            $line = $c . $line;
            fseek($fp, $pos--, SEEK_END);
            $c = fgetc($fp);
        } while (trim($line) == "" || $c != $eol);
        return trim($line);
    }

    public static function get_last_update_log_result()
    {
        $file = self::get_last_log_file();
        $line = self::read_last_line($file);
//update correct
        if ($line === 0 || strpos($line, "finished")) {
            return 0;
        }
        preg_match("/[0-9]+$/",$line,$match);
        return self::get_update_error($match[0]);
    }

    public static function get_last_update_log_time()
    {
        $file = self::get_last_log_file();
        return filemtime($file);
    }

    public function check_update_message_lock_present($uuid) {
        if ($uuid == file_get_contents(LAST_UPDATE_LOCK)) {
            return false;
        }
        file_put_contents(LAST_UPDATE_LOCK,$uuid);
        return true;
    }
//0 for events
//1 for alarms
    public static function get_risk_rext($risk,$type = 1) {
        $rrisk=$risk - $type;
        $text = "high";
        if ($rrisk <= 0) $text = "low ";
        if ($rrisk == 1) $text = "med ";
        return "$text ($risk)";
    }

    public static function nmap_with_excludes($task_params,$nmap_options) {
        $res = array();
        $excludes = array();
        foreach ($task_params as $tp) {
            if (strpos($tp,"!") === 0) {
                $tp = str_replace("!","",$tp);
                $excludes[] = $tp;
            } else {
                $res[] = $tp;
            }
        }
        if ($excludes) {
            $nmap_options[] = "--exclude ".implode(",",$excludes);
        }
        return implode(" ",$res).'#'.implode(' ', $nmap_options);
    }

    public static function nmap_without_excludes($task_params) {
	$regexp = "/\s?--exclude\s([^\s]+)/";
	list($params,$options) = explode("#", $task_params);
	$params = explode(" ",$params);
	if (preg_match($regexp,$options,$res)) {
            $res = explode(",",$res[1]);
            foreach ($res as $item) {
                $params[] = "!$item";
            }
        }
	$options = preg_replace($regexp,"",$options);
	return array($params,$options);
    }

    public static function datediff($interval, $datefrom, $dateto, $using_timestamps = false) {
        /*
        $interval can be:
        yyyy - Number of full years
        q - Number of full quarters
        m - Number of full months
        y - Difference between day numbers
        (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
        d - Number of full days
        w - Number of full weekdays
        ww - Number of full weeks
        h - Number of full hours
        n - Number of full minutes
        s - Number of full seconds (default)
        */
        #
        if (!$using_timestamps) {
            $datefrom = strtotime($datefrom, 0);
            $dateto = strtotime($dateto, 0);
        }
        $difference = $dateto - $datefrom; // Difference in seconds
        #
        switch($interval) {
            #
            case 'yyyy': // Number of full years
                $years_difference = floor($difference / 31536000);
                if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                    $years_difference--;
                }
                if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                    $years_difference++;
                }
                $datediff = $years_difference;
                break;

            case "q": // Number of full quarters
                $quarters_difference = floor($difference / 8035200);
                while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                    $quarters_difference++;
                }
                $quarters_difference--;
                $datediff = $quarters_difference;
                break;

            case "m": // Number of full months
                $months_difference = floor($difference / 2678400);
                while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                    $months_difference++;
                }
                $months_difference--;
                $datediff = $months_difference;
                break;

            case 'y': // Difference between day numbers
                $datediff = date("z", $dateto) - date("z", $datefrom);
                break;

            case "d": // Number of full days
                $datediff = floor($difference / 86400);
                break;

            case "w": // Number of full weekdays
                $days_difference = floor($difference / 86400);
                $weeks_difference = floor($days_difference / 7); // Complete weeks
                $first_day = date("w", $datefrom);
                $days_remainder = floor($days_difference % 7);
                $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
                if ($odd_days > 7) { // Sunday
                    $days_remainder--;
                }
                if ($odd_days > 6) { // Saturday
                    $days_remainder--;
                }
                $datediff = ($weeks_difference * 5) + $days_remainder;
                break;

            case "ww": // Number of full weeks
                $datediff = floor($difference / 604800);
                break;

            case "h": // Number of full hours
                $datediff = floor($difference / 3600);
                break;

            case "n": // Number of full minutes
                $datediff = floor($difference / 60);
                break;

            default: // Number of full seconds (default)
                $datediff = $difference;
                break;
        }

        return $datediff;

    }

    public static function TimeAgo ($datefrom, $dateto=-1) {
        if ($datefrom<=0) {
            return _("A long time ago");
        }
        if ($dateto==-1) {
            $dateto = time();
        }

        $difference = $dateto - $datefrom;

        if ($difference < 60) {
            $interval = "s";
        }
        elseif ($difference >= 60 && $difference<60*60) {
            $interval = "n";
        }
        elseif ($difference >= 60*60 && $difference<60*60*24) {
            $interval = "h";
        }
        elseif ($difference >= 60*60*24 && $difference<60*60*24*7) {
            $interval = "d";
        }
        elseif ($difference >= 60*60*24*7 && $difference < 60*60*24*30) {
            $interval = "ww";
        }
        elseif ($difference >= 60*60*24*30 && $difference < 60*60*24*365) {
            $interval = "m";
        }
        elseif ($difference >= 60*60*24*365) {
            $interval = "y";
        }

        switch($interval) {
            case "m":
                $months_difference = floor($difference / 60 / 60 / 24 / 29);
                while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                    $months_difference++;
                }
                $datediff = $months_difference;
                if ($datediff==12) {
                    $datediff--;
                }
                $msg = _("NN month ago");
                $msg2 = _("NN months ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;

            case "y":
                $datediff = floor($difference / 60 / 60 / 24 / 365);
                $msg = _("NN year ago");
                $msg2 = _("NN years ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;

            case "d":
                $datediff = floor($difference / 60 / 60 / 24);
                $msg = _("NN day ago");
                $msg2 = _("NN days ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;

            case "ww":
                $datediff = floor($difference / 60 / 60 / 24 / 7);
                $msg = _("NN week ago");
                $msg2 = _("NN weeks ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;

            case "h":
                $datediff = floor($difference / 60 / 60);
                $msg = _("NN hour ago");
                $msg2 = _("NN hours ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;

            case "n":
                $datediff = floor($difference / 60);
                $msg = _("NN minute ago");
                $msg2 = _("NN minutes ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;

            case "s":
                $datediff = $difference;
                $msg = _("NN second ago");
                $msg2 = _("NN seconds ago");
                $res = ($datediff==1) ? str_replace("NN",$datediff,$msg) : str_replace("NN",$datediff,$msg2);
                break;
            default:
                $res = _("A long time ago");
        }
        return $res;
    }

    public static function get_migration_section_pop_up ($config)
    {
        $migration_section_pop_up = $config->get_conf('migration_section_pop_up');

        //The pop up only is shown in pro version
        if ($migration_section_pop_up && Session::is_pro()) {
            $html_pop_up = "<div id='summary_info'>";

            $config_nt = array('content' => "<b>How to monitor cloud assets</b> </br> </br>
                                    Did you know USM Anywhere&trade; allows you to detect and respond to threats, both on-premises and in your cloud environments? </br>USM Anywhere also allows you to integrate with cloud services like Office 365 and G Suite.</br></br>
                                     Click <a href='https://cybersecurity.att.com/products/usm-anywhere?utm_medium=InProduct&utm_source=USM&utm_campaign=USMAnywhere&utm_content=200629' target=\"_blank\" style='color: #00529B; font-weight: bold;'>here to learn more</a>&nbsp; about USM Anywhere! (If you don't want to see this banner again, click  <a href='javascript:;' onclick='cancel_section_migration_pop_up();' style='color: #00529B; font-weight: bold;'> here </a>)", 'options' => array('type' => 'nf_info', 'cancel_button' => TRUE), 'style' => 'width: 65%; margin: 20px auto; text-align: left;');

            $nt = Notification::factory('nt_sm', $config_nt);
            $html_pop_up = $html_pop_up.$nt->show(FALSE)."</div>";

            return $html_pop_up;
        }

        return "";
    }

    /**
     * Copy from all information keeping the folder structure from src_folder to dst_folder
     *
     * @param string $src_folder  source folder from where the data will be copied
     * @param string $dst_folder  destination folder where the data will be copied
     *
     *
     * @return Output of command execution depending on $output_mode
     *         - $output_mode = FALSE:    Last line. system() call
     *         - $output_mode = 'string': Entire output into a string. shell_exec() call
     *         - $output_mode = 'array':  Entire output into an array. exec() call
     */
    public static function  recursive_copy($src_folder, $dst_folder) {
        return Util::execute_command('cp -r ?/* ?', array($src_folder, $dst_folder));;
    }

    /**
     * Copy from all information keeping the folder structure from src_folder to dst_folder
     *
     * @param string $dir   folder or directory to be removed
     *
     *
     * @return Output of command execution depending on $output_mode
     *         - $output_mode = FALSE:    Last line. system() call
     *         - $output_mode = 'string': Entire output into a string. shell_exec() call
     *         - $output_mode = 'array':  Entire output into an array. exec() call
     */

    public static function  rrmdir($dir) {
        return Util::execute_command('rm -rf ?', array($dir));
    }

    /**
     * Set the default language for the web
     *
     * @param string $language   language to be set
     *
     * @return void
     *         - $output_mode = 'array':  Entire output into an array. exec() call
     */
    public static function ossim_set_lang($language = "en_GB"){

        $DEFAULT_LANGUAGE   = "en_GB";
        $DEFAULT_LOCALE_DIR = AV_LOCALE_DIR."/";

        $conf = $GLOBALS["CONF"];
        if (!$conf)
        {
            $conf = Ossim_conf::factory();
        }

        if (!$locale_dir = $conf->get_conf("locale_dir"))
        {
            $locale_dir = $DEFAULT_LOCALE_DIR;
        }

        if (preg_match("/\/graphs\//", $_SERVER['REQUEST_URI']))
        {
            $tmp_lang = "en_GB";
        }
        #if the param is set we use this as a language
        elseif ($language != ""){
            $tmp_lang = $language;
        }
        #if no param is set we try to retrieve from session
        elseif (isset($_SESSION['_user_language']))
        {
            $tmp_lang = $_SESSION['_user_language'];
        }
        #if no session is set, it will be retrieved from DB
        elseif ( $conf_lang = $conf->get_conf("language")){
            $tmp_lang = $conf_lang;
        }
        #if nothing applies use the default language
        else
        {
            $tmp_lang = $DEFAULT_LANGUAGE;
        }
        $language = $tmp_lang;

        // I18N support information here
        // try out some locale options:
        // 1) For debian works @euro or .UTF8 or .UTF-8
        // 2) Mac OS X needs putenv()
        $lang = explode("_", $language);

        $locale_all = setlocale(LC_ALL, "");
        $locale_messages = setlocale(LC_MESSAGES, "");

        if ($locale_all = setlocale(LC_ALL, $lang[0]))
        {
            $locale_messages = setlocale(LC_MESSAGES, $lang[0]);
        }
        elseif ($locale_all = setlocale(LC_ALL, $language))
        {
            $locale_messages = setlocale(LC_MESSAGES, $language);
        }
        elseif ($locale_all = setlocale(LC_ALL, $language . ".UTF-8"))
        {
            $locale_messages = setlocale(LC_MESSAGES, $language . ".UTF-8");
        }
        else
        {
            $language = $DEFAULT_LANGUAGE;
            $locale_all = setlocale(LC_ALL, $DEFAULT_LANGUAGE);
            $locale_messages = setlocale(LC_MESSAGES, $DEFAULT_LANGUAGE);
        }

        putenv("LANGUAGE=" . $language);
        putenv("LANG=" . $language);

        // Set the text domain as 'ossim'
        // Localization files must be called ossim.po & ossim.mo
        $domain = "ossim";
        $bindtextdomain = bindtextdomain($domain, $locale_dir);
        $textdomain = textdomain($domain);

        return [$locale_all, $locale_messages, $bindtextdomain, $textdomain ];
    }
}
/* End of file Util.inc */
/* Location: ../include/classes/Util.inc */
