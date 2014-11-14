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
class Util
{
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

                    if ($format == 'string')
                    {
                        return $name;
                    }
                }
            }
        }
        else
        {
            if ($format == 'string' && $taxonomy['id'] != '')
            {
                return $taxonomy['kingdom'] . ' &mdash; ' . $taxonomy['category'] . ' &mdash; ' . $taxonomy['subcategory'];
            }
        }
        // If not, translate meta tags

        $translations = array(
            '/SRC_IP/'     => array('func' => 'get_src_ip', 'parameters' => array()),
            '/DST_IP/'     => array('func' => 'get_dst_ip', 'parameters' => array()),
            '/SRC_PORT/'   => array('func' => 'get_src_port', 'parameters' => array()),
            '/DST_PORT/'   => array('func' => 'get_dst_port', 'parameters' => array()),
            '/PROTOCOL/'   => array('func' => 'get_protocol', 'parameters' => array()),
            '/PLUGIN_ID/'  => array('func' => 'get_plugin_id', 'parameters' => array()),
            '/PLUGIN_SID/' => array('func' => 'get_plugin_sid', 'parameters' => array()),
            '/FILENAME/'   => array('func' => 'get_event_data', 'parameters' => array($conn, 'filename')),
            '/USERNAME/'   => array('func' => 'get_event_data', 'parameters' => array($conn, 'username')),
            '/USERNAME1/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username1')),
            '/USERNAME2/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username2')),
            '/USERNAME3/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username3')),
            '/USERNAME4/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username4')),
            '/USERNAME5/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username5')),
            '/USERNAME6/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username6')),
            '/USERNAME7/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username7')),
            '/USERNAME8/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username8')),
            '/USERNAME9/'  => array('func' => 'get_event_data', 'parameters' => array($conn, 'username9'))
        );

        foreach ($translations as $k => $v)
        {
            switch ($k)
            {
                case '/PROTOCOL/':

                    $d_param = call_user_func_array(array($alarm, $v['func']), $v['parameters']);

                    $parameters  = array($d_param);
                    $replacement = call_user_func_array('getprotobynumber', $parameters);

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
                    }

                    break;

                default:
                    $replacement = call_user_func_array(array($alarm, $v['func']), $v['parameters']);
            }

            $str = '$name = preg_replace("' . $k . '", $replacement, $name);';

            eval($str);
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
     * Function graph_image_link
     *
     * @param string $ip
     * @param string $type
     * @param string $what
     * @param string $start
     * @param string $end
     * @param string $zoom
     * @param string $range
     *
     * @return string
     */
    public static function graph_image_link($ip, $type, $what, $start, $end, $zoom, $range)
    {
        $cgi_link = 'show_image.php';

        return "$cgi_link?range=$range&id=" . urlencode(
            $ip
        ) . "&what=$what&start=$start&end=$end&type=$type&zoom=$zoom";
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
        $locale    = (isset($_COOKIE['locale']) ? $_COOKIE['locale'] : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $languages = explode(",", $locale);

        switch ($languages[0])
        {
            case 'es-es':
            case 'de-de':
            case 'es-mx':
                $decimal   = ',';
                $thousands = '.';
                break;
            default:
                $decimal   = '.';
                $thousands = ',';
        }

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
        $power = 1000; // 1024
        $unit  = array('', 'K+', 'M+', 'G+', 'T+', 'P+', 'E+');

        return @floor(
                $number / pow($power, ($i = floor(log($number, $power))))
            ) . $unit[$i];
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
     * @param string $string
     * @param int    $flag
     * @param string $charset
     *
     * @return string
     */
    public static function htmlentities($string, $flag = ENT_QUOTES, $charset = 'ISO-8859-1')
    {
        $string = (mb_detect_encoding($string . " ", 'UTF-8,ISO-8859-1') == 'ISO-8859-1') ? mb_convert_encoding(
            $string,
            'UTF-8',
            'ISO-8859-1'
        ) : $string;
        $string = mb_convert_encoding($string, "HTML-ENTITIES", "UTF-8");

        $string = preg_replace('/&#(\d{4,5});/', '#_#$1;', $string);
        $string = html_entity_decode($string, $flag, 'ISO-8859-1');
        $string = @htmlentities($string, $flag, $charset, FALSE);

        return preg_replace('/#_#/', '&#', $string);
    }

    /**
     * Function htmlentities_url
     *
     * This function is sensible with the complex URLs with parameters
     * It doesn't encode de ampersand character to &amp;
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
     * PRE: $str must be in UTF-8 enconding
     *
     * @param string $str
     *
     * @return string
     */
    public static function utf8entities($str)
    {
        return html_entity_decode(
            preg_replace('/&#(\d{4,5});/', '&amp;#$1;', @mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8'))
        );
    }
    
    /**
     * Function html_entities2utf8
     *
     * PRE: $str must be in html_entities enconding
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


    /**
    * Function get_unixtime_utc
    *
    * Get UTC unixtime using date command
    *
    * @return int
    */
    public static function get_unixtime_utc()
    {
        return strtotime(trim(`date -u -R`));
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
            $rs = $conn->baseExecute("SELECT CONVERT_TZ('$date','$tz','+00:00')");

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if ($row = $rs->baseFetchRow())
                {
                    $utc = $row[0];
                }
            }
        }
        else
        {
            // Called from other interface section
            $rs = $conn->Execute("SELECT CONVERT_TZ('$date','$tz','+00:00')");

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $utc = $rs->fields[0];
                }
            }

        }

        $time   = preg_split("/[\s\-\:]/", $utc);
        $time[] = $utc;

        // return $y,$m,$d,$h,$u,$s,$utc
        return $time;
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
        $uuid = strtoupper(trim(`/usr/bin/alienvault-system-id`));
        
        if (!valid_uuid($uuid))
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
                    `grep "^key=" /etc/ossim/framework/db_encryption_key | awk 'BEGIN { FS = "=" } ; {print $2}'`
                );
            }
            else
            {
                $uuid = @trim(`sudo dmidecode -s system-uuid`);
            }
        }

        $db->close();
        
        return $uuid;
    }


    public static function uuid($format = 'int')
    {
        $chars = md5(uniqid(mt_rand(), TRUE));

        $uuid = ($format == "dashes")
            ? substr($chars, 0, 8) . '-' . substr($chars, 8, 4) . '-' . substr(
                $chars, 12, 4) . '-' . substr($chars, 16, 4) . '-' . substr($chars, 20, 12)
            : "$chars";

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
     * This function gets mail parameters (by default or mailserver relay if it was configured)
     * from Alienvault Component
     *
     * @param object $conn Database access object
     * @param string $uuid [Optional] Alienvault Component UUID
     *
     * @return array
     */
    public static function get_mail_params($conn, $uuid = '')
    {
        //Getting user and email params

        $from_user      = 'no-reply@alienvault.com';
        $from_user_name = 'Alienvault';

        $user_info = Session::get_user_info($conn);

        if (is_object($user_info) && !empty($user_info))
        {
            $email = $user_info->get_email();

            ossim_valid($email, OSS_MAIL_ADDR, 'illegal:' . _('E-mail'));

            if (!ossim_error())
            {
                $from_user_name = $user_info->get_name();
                $from_user      = $user_info->get_email();
            }
        }

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
                require_once 'classes/Security.inc';

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

        require_once 'classes/PHPMailer.inc';
        require_once 'classes/PHPMailerSMTP.inc'; // Optional, gets called from within class.phpmailer.php if not already loaded

        $mail = new PHPMailer(TRUE); // the TRUE param means it will throw exceptions on errors, which we need to catch

        $mail->IsSMTP();

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
                #	while($array[$alpha][$column] < $guess) $alpha++;
                #	while($array[$omega][$column] > $guess) $omega--;
                #} else {
                #	while($array[$alpha][$column] > $guess) $alpha++;
                #	while($array[$omega][$column] < $guess) $omega--;
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
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv      = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $h_key   = hash('sha256', $key, TRUE);

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv));
    }


    public static function decrypt($encrypted_input_string, $key)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv      = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $h_key   = hash('sha256', $key, TRUE);

        return trim(
            mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv)
        );
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
        system('echo "flush_all" | /bin/nc -q 2 127.0.0.1 11211 > /dev/null 2>&1');

        if ($cache_files == TRUE)
        {
            /* connect to db */

            $db   = new ossim_db(TRUE);
            $conn = $db->connect();

            $conn->Execute("REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp())");

            $db->close();
        }
    }


    // Asset dump
    public static function resend_asset_dump($what = '')
    {
        $param = ($what == 'servers') ? 'restart' : '';

        @system("/usr/share/ossim/scripts/assets_sync.sh $param > /var/tmp/latest_assec_sync 2>&1 &");
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
     * This function gets current session cookie
     *
     * @param string $ip   Host IP
     * @param string $user Logged user
     * @param string $pass User password
     *
     * @return string
     */
    public static function get_session_cookie($ip, $user, $pass)
    {
        $headers = self::getheaders("https://$ip/ossim/index.php", $user, $pass);

        preg_match_all("/.*Set\-Cookie\: ([^\;]+)\;.*/", $headers, $output);

        return $output[1][1];
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
     *
     * @return array
     */
    public static function getheaders($url, $login = '', $pass = '')
    {
        $c = curl_init();

        if ($login != '' && $pass != '')
        {
            // OSSIM login
            if (preg_match("/^https\:\/\//", $url))
            {
                $conf = $GLOBALS['CONF'];

                if (!$conf)
                {
                    $conf            = new Ossim_conf();
                    $GLOBALS['CONF'] = $conf;
                }

                $data = array(
                    'login' => base64_encode(
                        self::encrypt($login . '####' . md5($pass), $conf->get_conf('remote_key'))
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
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, TRUE);
        curl_setopt($c, CURLOPT_URL, $url);
        $output = curl_exec($c);
        curl_close($c);

        return $output;
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
                $proxy_conf = $current_data['data']['update_proxy'];
                $proxy_url  = $current_data['data']['update_proxy_dns'];
                $proxy_user = $current_data['data']['update_proxy_user'];
                $proxy_pass = $current_data['data']['update_proxy_pass'];
                $proxy_port = $current_data['data']['update_proxy_port'];
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
        $loginpassw = $params['login'] . ':' . $params['pass'];


        if ($opts['only_header'])
        {
            curl_setopt($c, CURLOPT_HEADER, TRUE);
            curl_setopt($c, CURLOPT_NOBODY, TRUE);
        }

        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, TRUE);
        curl_setopt($c, CURLOPT_PROXYPORT, $proxy_port);
        curl_setopt($c, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($c, CURLOPT_PROXY, $proxy_ip);
        curl_setopt($c, CURLOPT_PROXYUSERPWD, $loginpassw);
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
     * Returns defautl admin ip
     *
     * @return string
     */
    public static function get_default_admin_ip()
    {
        $admin_ip = $_SERVER['SERVER_ADDR'];

        if (file_exists('/etc/ossim/ossim_setup.conf'))
        {
            $command = 'grep ^admin_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | head -1';
            $ret     = 0;

            exec($command, $output, $ret);

            if ($ret === 0 && Asset_host_ips::valid_ip($output[0]))
            {
                $admin_ip = trim($output[0]);
            }
        }

        return $admin_ip;
    }

    /**
    * Function get_default_uuid
    *
    * Returns the default uuid
    *
    * @return string
    */
    // DEPRECATED: now using Util::get_system_uuid() (Ticket ENG-95685)
    public static function get_default_uuid()
    {
        $uuid = '';

        $db   = new ossim_db();
        $conn = $db->connect();

        $admin_ip = self::get_default_admin_ip();
        $res      = Av_center::get_system_info_by_ip($conn, $admin_ip);

        if ($res['status'] == 'success')
        {
            $uuid = $res['data']['system_id'];
        }

        $db->close();

        return $uuid;
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

            $file_check = preg_replace("/(\.css|\.js)\?.*/", "\\1", $file_check);

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
    function compare_av_version($v1, $v2)
    {
        //Formating first version
        $v1 = trim($v1);
        $v1 = explode('.', $v1);
        $v1 = array_pad($v1, 5, 0);
        $v1 = implode('.', $v1);

        //Formating second version
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
     * @return string
     */
    function get_packages_version()
    {

        $version = @`dpkg -l | grep ossim-cd-tools | awk '{print $3}' | awk -F '-' '{print $1}'`;

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
    function group_nums($array) { 
        
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
     * This function try to activate a OTX user
     *
     * @param string $token UUID
     *
     * @return string
     */
    public static function get_otx_username($token)
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL,             'https://www.alienvault.com/apps/API/V1/get_username/'.$token);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER,  FALSE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST,  FALSE);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION,  TRUE);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER,  TRUE);   
        curl_setopt($handle, CURLOPT_SSLVERSION,      1); 
        curl_setopt($handle, CURLOPT_SSL_CIPHER_LIST, 'TLSv1'); 
        curl_setopt($handle, CURLOPT_TIMEOUT,         30); //timeout in seconds

        $response = curl_exec($handle);
        $error    = curl_error($handle);
        
        curl_close($handle);
        
        // Error?
        if ($error)
        {
            return $error;
        }
        
        // Check answer        
        $user = @json_decode( $response, TRUE );
        if (!$user || $user['username'] == '')
        {
            return _('Unable to activate user or Invalid OTX auth-token');
        }
        
        // Set token & username
        $config = new Config();
        $config->update("open_threat_exchange", "yes");
        $config->update("open_threat_exchange_username", $user['username']);
        $config->update("open_threat_exchange_key", $token);
        $config->update("open_threat_exchange_last", '');

        return "";
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
            $rs->Close();
        }
        
        return $enable;
        
    }    
    
}


/* End of file Util.inc */
/* Location: ../include/classes/Util.inc */ 
