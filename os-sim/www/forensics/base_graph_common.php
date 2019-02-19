<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/


/**
* Function list:
* - FindGraphingLib()
* - VerifyGraphingLib()
* - ProcessChartTimeConstraint()
* - StoreAlertNum()
* - GetTimeDataSet()
* - GetIPDataSet()
* - GetPortDataSet()
* - GetClassificationDataSet()
* - GetSensorDataSet()
* - ReadGeoIPfreeFileAscii()
* - GeoIPfree_IP2Country()
* - run_ip2cc()
* - IncreaseCountryValue()
* - GetCountryDataSet()
*/


include_once ("base_conf.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/includes/base_signature.inc.php");
include_once ("$BASE_path/includes/base_iso3166.inc.php");
// Some colors to be used in graphs.
$named_colors = array(
    'aliceblue',
    'antiquewhite',
    'aqua',
    'aquamarine',
    'azure',
    'beige',
    'bisque',
    'black',
    'blanchedalmond',
    'blue',
    'blueviolet',
    'brown',
    'burlywood',
    'cadetblue',
    'chartreuse',
    'chocolate',
    'coral',
    'cornflowerblue',
    'cornsilk',
    'crimson',
    'cyan',
    'darkblue',
    'darkcyan',
    'darkgoldenrod',
    'darkdray',
    'darkgreen',
    'darkhaki',
    'darkorange',
    'darkolivegreen',
    'darkmagenta',
    'darkorchid',
    'darkred',
    'darksalmon',
    'darkseagreen',
    'darkviolet',
    'deeppink',
    'deepskyblue',
    'dimgray',
    'dodgerblue',
    'firebrick',
    'floralwhite',
    'forestgreen',
    'fuchsia',
    'gainsboro',
    'ghostwhite',
    'gold',
    'goldenrod',
    'gray',
    'green',
    'greenyellow',
    'indianred',
    'indigo',
    'ivory'
);
function FindGraphingLib($libfile) {
    $found = false;
    // Will search in Path
    $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
    foreach($paths as $path) {
        $fullpath = $path . DIRECTORY_SEPARATOR . $libfile;
        if (file_exists($fullpath)) {
            $found = true;
            break;
        }
    }
    return $found;
}
function VerifyGraphingLib() {
    GLOBAL $debug_mode;
    /* Check if GD is compiled into PHP */
    if (!(function_exists("imagedestroy"))) {
        echo "<FONT COLOR=\"#FF0000\">" . gettext("PHP ERROR") . "</FONT>:
            <B>PHP build incomplete</B>: <FONT>
            the prerequisite GD support required to
            generate graphs was not built into PHP.
            Please recompile PHP with the necessary library
            (<CODE>--with-gd</CODE>)</FONT>";
        die();
    }
    // PHP will search the default path and try to include the file
    $file = "Image/Graph.php";
    $fileIncluded = @include_once ($file);
    // We have to locate Image/Graph.php -- Alejandro
    if (!$fileIncluded) { // Will search in Path
        $found = false;
        $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach($paths as $path) {
            $fullpath = $path . DIRECTORY_SEPARATOR . $file;
            if (file_exists($fullpath)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            // Cool, file was found, so you have Image_Graph installed. -- Alejandro
            include_once ($file);
            return true;
        } else {
            // Sorry dude, you haven't finished your home work. -- Alejandro
            echo "<P><B>Error loading the Graphing library: </B>" . "<P>Check your Pear::Image_Graph installation!" . "<P><UL>" . "<LI>Image_Graph can be found here:" . "at <A HREF=\"http://pear.veggerby.dk/\">http://pear.veggerby.dk/</A>.  Without this " . "library no graphing operations can be performed.<BR>" . "<LI>Make sure PEAR libraries can be found by php at all:<BR>" . "<PRE>" . "pear config-show | grep &quot;PEAR directory&quot;<BR>" . "PEAR directory      php_dir     /usr/share/pear" . "</PRE>" . "This path must be part of the include path of php (cf. /etc/php.ini):<BR>" . "<PRE>" . "php -i | grep &quot;include_path&quot;<BR>" . "include_path => .:/usr/share/pear:/usr/share/php => .:/usr/share/pear:/usr/share/php" . "</PRE><BR>";
            $rv = ini_get("safe_mode");
            if ($rv == 1) {
                print "<LI>In &quot;safe_mode&quot; it must also be part of safe_mode_include_dir in /etc/php.ini";
            }
            echo "</UL>\n";
            die();
        }
    }
}
/* Generates the required SQL from the chart time criteria */
function ProcessChartTimeConstraint($start_hour, $start_day, $start_month, $start_year, $stop_hour, $stop_day, $stop_month, $stop_year) {
    /* if any of the hour, day criteria is blank ' ', set it to NULL */

    $start_hour = trim($start_hour);
    $stop_hour = trim($stop_hour);
    $start_day = trim($start_day);
    $stop_day = trim($stop_day);
    $tmp_sql = "";
    if ($start_month == "" && $start_day == "" && $start_year == "") {
        $tmp_time = array(
            array(
                " ",
                " ",
                "",
                "",
                "",
                "",
                "",
                "",
                " ",
                " "
            ) ,
            array(
                " ",
                "<=",
                $stop_month,
                $stop_day,
                $stop_year,
                $stop_hour,
                "",
                "",
                " ",
                " "
            )
        );
    } else if ($stop_month == "" && $stop_day == "" && $stop_year == "") {
        $tmp_time = array(
            array(
                " ",
                ">=",
                $start_month,
                $start_day,
                $start_year,
                $start_hour,
                "",
                "",
                " ",
                " "
            ) ,
            array(
                " ",
                " ",
                "",
                "",
                "",
                "",
                "",
                "",
                " ",
                " "
            )
        );
    } else {
        $tmp_time = array(
            array(
                " ",
                ">=",
                $start_month,
                $start_day,
                $start_year,
                $start_hour,
                "",
                "",
                " ",
                "AND"
            ) ,
            array(
                " ",
                "<=",
                $stop_month,
                $stop_day,
                $stop_year,
                $stop_hour,
                "",
                "",
                " ",
                " "
            )
        );
    }
    DateTimeRows2sql($tmp_time, 2, $tmp_sql);
    return $tmp_sql;
}
function StoreAlertNum($sql, $label, &$xdata, &$cnt, $min_threshold) {
    GLOBAL $db, $debug_mode;
    //if ($debug_mode > 0) echo $sql . "<BR>";
    $result = $db->baseExecute($sql);
    if ($myrow = $result->baseFetchRow()) {
        if ($myrow[0] >= $min_threshold) {
            $xdata[$cnt][0] = $label;
            $xdata[$cnt][1] = $myrow[0];
            $cnt++;
        }
        $result->baseFreeRows();
    }
}
function GetTimeDataSet(&$xdata, $chart_type, $data_source, $min_threshold, $criteria) {
    GLOBAL $db, $debug_mode;
    // if ($debug_mode > 0) {
        // echo "chart_type = $chart_type<BR>
            // data_source = $data_source<BR>";
    // }
    $sql = "SELECT min(timestamp), max(timestamp) FROM acid_event " . $criteria[0] . " WHERE " . $criteria[1];
    $result = $db->baseExecute($sql);
    $myrow = $result->baseFetchRow();
    $start_time = $myrow[0];
    $stop_time = $myrow[1];
    $result->baseFreeRows();
    $year_start = date("Y", strtotime($start_time));
    $month_start = date("m", strtotime($start_time));
    $day_start = date("d", strtotime($start_time));
    $hour_start = date("H", strtotime($start_time));
    $year_end = date("Y", strtotime($stop_time));
    $month_end = date("m", strtotime($stop_time));
    $day_end = date("d", strtotime($stop_time));
    $hour_end = date("H", strtotime($stop_time));
    // using the settings from begin_xyz and end_xyz
    // minutes are not supported actually
    // begin
    global $chart_begin_year;
    global $chart_begin_month;
    global $chart_begin_day;
    global $chart_begin_hour;
    if (strcmp($chart_begin_year, " ") and ($year_start < $chart_begin_year)) {
        $year_start = $chart_begin_year;
        $month_start = "01";
        $day_start = "01";
        $hour_start = "00";
    }
    if (strcmp($chart_begin_month, " ") and ($month_start < $chart_begin_month)) {
        $month_start = $chart_begin_month;
        $day_start = "01";
        $hour_start = "00";
    }
    if (strcmp($chart_begin_day, " ") and ($day_start < $chart_begin_day)) {
        $day_start = $chart_begin_day;
        $hour_start = "00";
    }
    if (strcmp($chart_begin_hour, " ") and ($hour_start < $chart_begin_hour)) {
        $hour_start = $chart_begin_hour;
    }
    //end
    global $chart_end_year;
    global $chart_end_month;
    global $chart_end_day;
    global $chart_end_hour;
    if (strcmp($chart_end_year, " ") and ($year_end < $chart_end_year)) {
        $year_end = $chart_end_year;
        $month_end = "01";
        $day_end = "01";
        $hour_end = "00";
    }
    if (strcmp($chart_end_month, " ") and ($month_end < $chart_end_month)) {
        $month_end = $chart_end_month;
        $day_end = "01";
        $hour_end = "00";
    }
    if (strcmp($chart_end_day, " ") and ($day_end < $chart_end_day)) {
        $day_end = $chart_end_day;
        $hour_end = "00";
    }
    if (strcmp($chart_end_hour, " ") and ($hour_end < $chart_end_hour)) {
        $hour_end = $chart_end_hour;
    }
    switch ($chart_type) {
        case 1: // hour
            {
                    // if ($debug_mode > 0) {
                        // print "chart_begin_hour = \"$chart_begin_hour\", hour_start = \"$hour_start\"<BR>\n";
                        // print "chart_end_hour = \"$chart_end_hour\", hour_end = \"$hour_end\"<BR>\n";
                    // }
                    if (!strcmp($chart_end_hour, " ") || $chart_end_hour == "") {
                        // hour_start = -1 is NOT possible, because with chart_type == 1
                        // each hour is to be queried. We want bars hour by hour.
                        $hour_end = 23;
                    }
                    break;
            }
        case 2: // day
            {
                $hour_start = - 1;
                break;
            }
        case 4: // month
            {
                $day_start = - 1;
                $hour_start = - 1;
                break;
            }
        }
        // if ($debug_mode > 0) {
            // echo '<TABLE BORDER="1">
            // <TR>
              // <TD>year_start<TD>year_end<TD>month_start<TD>month_end
              // <TD>day_start<TD>day_end<TD>hour_start<TD>hour_end
            // <TR>
              // <TD>' . $year_start . '<TD>' . $year_end . '<TD>' . $month_start . '<TD>' . $month_end . '<TD>' . $day_start . '<TD>' . $day_end . '<TD>' . $hour_start . '<TD>' . $hour_end . '</TABLE>';
        // }
        $cnt = 0;
        $ag = $criteria[0];
        $ag_criteria = $criteria[1];
        for ($i_year = $year_start; $i_year <= $year_end; $i_year++) {
            // removed AND below
            // !!! AVN !!!
            // to_date() must used!
            $sql = "SELECT count(*) FROM acid_event " . $ag . " WHERE $ag_criteria AND " . $db->baseSQL_YEAR("timestamp", "=", $i_year);
            if ($month_start != - 1) {
                if ($i_year == $year_start) $month_start2 = $month_start;
                else $month_start2 = 1;
                if ($i_year == $year_end) $month_end2 = $month_end;
                else $month_end2 = 12;
                for ($i_month = $month_start2; $i_month <= $month_end2; $i_month++) {
                    $sql = "SELECT count(*) FROM acid_event $ag WHERE $ag_criteria AND" . $db->baseSQL_YEAR("timestamp", "=", $i_year) . " AND " . $db->baseSQL_MONTH("timestamp", "=", FormatTimeDigit($i_month));
                    if ($day_start != - 1) {
                        if ($i_month == $month_start) $day_start2 = $day_start;
                        else $day_start2 = 1;
                        if ($i_month == $month_end) $day_end2 = $day_end;
                        else $day_end2 = 31;
                        for ($i_day = $day_start2; $i_day <= $day_end2; $i_day++) {
                            if (checkdate($i_month, $i_day, $i_year)) {
                                $sql = "SELECT count(*) FROM acid_event $ag WHERE $ag_criteria AND " . $db->baseSQL_YEAR("timestamp", "=", $i_year) . " AND " . $db->baseSQL_MONTH("timestamp", "=", FormatTimeDigit($i_month)) . " AND " . $db->baseSQL_DAY("timestamp", "=", FormatTimeDigit($i_day));
                                if ($hour_start != - 1) {
                                    // jl: The condition "i_hour <= hour_end"
                                    // is correct ONLY if the first day is equal
                                    // to the last day of the query.
                                    // Otherwise we want 24 hours of
                                    // all the days preceding the last day of the query.
                                    // Analogously for hour_start.
                                    if ($i_day == $day_start2) $hour_start2 = $hour_start;
                                    else $hour_start2 = 0;
                                    if ($i_day == $day_end2) $hour_end2 = $hour_end;
                                    else $hour_end2 = 23;
                                    for ($i_hour = $hour_start2; $i_hour <= $hour_end2; $i_hour++) {
                                        //if($i_hour < 10 && strlen($i_hour) == 1)
                                        //   $i_hour = "0".$i_hour;
                                        $i_hour = FormatTimeDigit($i_hour);
                                        $sql = "SELECT count(*) FROM acid_event $ag WHERE $ag_criteria AND " . $db->baseSQL_YEAR("timestamp", "=", $i_year) . " AND " . $db->baseSQL_MONTH("timestamp", "=", FormatTimeDigit($i_month)) . " AND " . $db->baseSQL_DAY("timestamp", "=", FormatTimeDigit($i_day)) . " AND " . $db->baseSQL_HOUR("timestamp", "=", $i_hour);
                                        StoreAlertNum($sql, FormatTimeDigit($i_month) . "/" . FormatTimeDigit($i_day) . "/" . $i_year . " " . $i_hour . ":00:00 - " . $i_hour . ":59:59", $xdata, $cnt, $min_threshold);
                                    } // end hour

                                } else StoreAlertNum($sql, FormatTimeDigit($i_month) . "/" . FormatTimeDigit($i_day) . "/" . $i_year, $xdata, $cnt, $min_threshold);
                            }
                        } // end day

                    } else StoreAlertNum($sql, FormatTimeDigit($i_month) . "/" . $i_year, $xdata, $cnt, $min_threshold);
                } // end month

            } else StoreAlertNum($sql, $i_year, $xdata, $cnt, $min_threshold);
        } // end year
        return $cnt;
    }
    function GetIPDataSet(&$xdata, $chart_type, $data_source, $min_threshold, $criteria) {
        GLOBAL $db, $debug_mode;
        if ($chart_type == 6) $sql = "SELECT DISTINCT ip_src, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " AND ip_src is NOT NULL " . "GROUP BY ip_src ORDER BY ip_src";
        else if ($chart_type == 7) $sql = "SELECT DISTINCT ip_dst, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " AND ip_dst is NOT NULL " . "GROUP BY ip_dst ORDER BY ip_dst";
        //if ($debug_mode > 0) echo $sql . "<BR>";
        $result = $db->baseExecute($sql);
        $cnt = 0;
        while ($myrow = $result->baseFetchRow()) {
            if ($myrow[1] >= $min_threshold) {
                $xdata[$cnt][0] = baseLong2IP($myrow[0]);
                $xdata[$cnt][1] = $myrow[1];
                ++$cnt;
            }
        }
        $result->baseFreeRows();
        return $cnt;
    }
    function GetPortDataSet(&$xdata, $chart_type, $data_source, $min_threshold, $criteria) {
        GLOBAL $db, $debug_mode;
        if (($chart_type == 8) || ($chart_type == 9)) $sql = "SELECT DISTINCT layer4_dport, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " AND layer4_dport is NOT NULL " . "GROUP BY layer4_dport ORDER BY layer4_dport";
        else if (($chart_type == 10) || ($chart_type == 11)) $sql = "SELECT DISTINCT layer4_sport, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " AND layer4_sport is NOT NULL " . "GROUP BY layer4_sport ORDER BY layer4_sport";
        //if ($debug_mode > 0) echo $sql . "<BR>";
        $result = $db->baseExecute($sql);
        $cnt = 0;
        while ($myrow = $result->baseFetchRow()) {
            if ($myrow[1] >= $min_threshold) {
                $xdata[$cnt][0] = $myrow[0];
                $xdata[$cnt][1] = $myrow[1];
                ++$cnt;
            }
        }
        $result->baseFreeRows();
        return $cnt;
    }
    function GetClassificationDataSet(&$xdata, $chart_type, $data_source, $min_threshold, $criteria) {
        GLOBAL $db, $debug_mode;
        $sql = "SELECT DISTINCT sig_class_id, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " GROUP BY sig_class_id ORDER BY sig_class_id";
        //if ($debug_mode > 0) echo $sql . "<BR>";
        $result = $db->baseExecute($sql);
        $cnt = 0;
        while ($myrow = $result->baseFetchRow()) {
            if ($myrow[1] >= $min_threshold) {
                $xdata[$cnt][0] = strip_tags(GetSigClassName($myrow[0], $db));
                $xdata[$cnt][1] = $myrow[1];
                ++$cnt;
            }
        }
        $result->baseFreeRows();
        return $cnt;
    }
    function GetSensorDataSet(&$xdata, $chart_type, $data_source, $min_threshold, $criteria) {
        GLOBAL $db, $debug_mode;
        $sql = "SELECT DISTINCT acid_event.sid, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " GROUP BY acid_event.sid ORDER BY acid_event.sid";
        //if ($debug_mode > 0) echo $sql . "<BR>";
        $result = $db->baseExecute($sql);
        $cnt = 0;
        while ($myrow = $result->baseFetchRow()) {
            if ($myrow[1] >= $min_threshold) {
                $result2 = $db->baseExecute("SELECT * FROM alienvault_siem.sensor where sid=" . $myrow[0]);
                $sensor_name = $result2->baseFetchRow();
                $xdata[$cnt][0] = ($sensor_name["sensor"]!="") ? $sensor_name["sensor"] : $sensor_name["hostname"];
                $result2->baseFreeRows();
                $xdata[$cnt][1] = $myrow[1];
                ++$cnt;
            }
        }
        $result->baseFreeRows();
        return $cnt;
    }
    // xxx jl
    function ReadGeoIPfreeFileAscii(&$Geo_IPfree_array) {
        GLOBAL $Geo_IPfree_file_ascii, $db, $debug_mode, $iso_3166;
        if (empty($Geo_IPfree_file_ascii) || !is_file($Geo_IPfree_file_ascii) || !is_readable($Geo_IPfree_file_ascii)) {
            return 0;
        }
        $lines = file($Geo_IPfree_file_ascii);
        if ($lines == FALSE) {
            print "WARNING: " . $Geo_IPfree_file_ascii . " could not be opened.<BR>\n";
            return 0;
        }
        foreach($lines as $line_num => $line) {
            $line_array[$line_num] = preg_split('/\s/', rtrim($line));
            $index = rtrim($line_array[$line_num][0], ':');
            $begin = sprintf("%u", ip2long($line_array[$line_num][1]));
            $end = sprintf("%u", ip2long($line_array[$line_num][2]));
            if (!isset($iso_3166)) {
                ErrorMessage("<BR>ERROR: \$iso_3166 has not been defined.<BR>\n");
                return 0;
            } else {
                if (!array_key_exists($index, $iso_3166)) {
                    $estr = "ERROR: index \"" . $index . "\" = ascii codes ";
                    $estr.= ord($index[0]) . ", " . ord($index[1]) . " ";
                    $estr.= "does not exist. Ignoring.<BR>\n";
                    ErrorMessage($estr);
                } else {
                    // if ($debug_mode > 1) {
                        // print "Full name of " . $index . " = \"" . $iso_3166[$index] . "\"<BR>\n";
                    // }
                    $index.= " (" . $iso_3166[$index] . ")";
                }
                if (!isset($Geo_IPfree_array) || !array_key_exists($index, $Geo_IPfree_array)) {
                    $Geo_IPfree_array[$index][0] = array(
                        $begin,
                        $end
                    );
                } else { {
                        array_push($Geo_IPfree_array[$index], array(
                            $begin,
                            $end
                        ));
                    }
                }
            }
        }
    }
    /**
     * First method how to look up the country corresponding to an ip address:
     * http://search.cpan.org/CPAN/authors/id/G/GM/GMPASSOS/Geo-IPfree-0.2.tar.gz
     * Requires the transformation of the included database into human readable
     * ASCII format, similarly to:
     *          cd /usr/lib/perl5/site_perl/5.8.8/Geo/
     *          perl ipct2txt.pl ./ipscountry.dat /tmp/ips-ascii.txt
     * $Geo_IPfree_file_ascii must contain the absolute path to
     * ips-ascii.txt. The Web server needs read access to this file.
     *
     */
    function GeoIPfree_IP2Country($Geo_IPfree_array, $address_with_dots, &$country) {
        GLOBAL $db, $debug_mode;
        if (empty($Geo_IPfree_array) || empty($address_with_dots)) {
            return 0;
        }
        $address = sprintf("%u", ip2long($address_with_dots));
        while (list($key, $val) = each($Geo_IPfree_array)) {
            $nelements = count($val);
            if (count($val) > 0) {
                while (list($key2, $val2) = each($val)) {
                    // if ($debug_mode > 1) {
                        // if ($val2[0] > $val2[1]) {
                            // print "WARNING: Inconsistency with $key array element no. " . $key2 . ": " . long2ip($val2[0]) . " - " . long2ip($val2[1]) . "<BR>\n";
                        // }
                    // }
                    if (($address >= $val2[0]) && ($address <= $val2[1])) {
                        // if ($debug_mode > 0) {
                            // print "Found: " . $address_with_dots . " belongs to " . $key;
                            // print ": " . long2ip($val2[0]) . " - " . long2ip($val2[1]);
                            // print "<BR>\n";
                        // }
                        $country = $key;
                        return 1;
                    }
                }
            }
        }
    }
    /**
     * Second method how to lookup the country corresponding to an ip address:
     * Makes use of the perl module IP::Country
     * http://search.cpan.org/dist/IP-Country/
     * The web server needs permission to execute "ip2cc".
     * Quoting from the php manual:
     * "Note: When safe mode is enabled, you can only execute executables within the safe_mode_exec_dir. For practical reasons it is currently not allowed to have .. components in the path to the executable."
     *
     * $IP2CC must contain the absolute path to this executable.
     *
     *
     */
    function run_ip2cc($address_with_dots, &$country) {
        GLOBAL $db, $debug_mode, $IP2CC, $iso_3166;
        if (empty($address_with_dots)) {
            ErrorMessage("ERROR: \$address_with_dots is empty<BR>\n");
            return 0;
        }
        if ((!is_file($IP2CC)) || (!is_executable($IP2CC))) {
            ErrorMessage("ERROR: with \$IP2CC = \"" . $IP2CC . "\"<BR>\n");
            return 0;
        }
        $cmd = $IP2CC . " ?";

        unset($output);

        try
        {
            $output = Util::execute_command($cmd, array($address_with_dots), 'array');
        }
        catch (Exception $e)
        {
            ErrorMessage("ERROR with " . $cmd . "<BR>\n");
            return 0;
        }
        $result = explode(" ", $output[6]);
        $max = count($result);
        $country = "";
        for ($i = 3; $i < $max; $i++) {
            $country.= $result[$i] . " ";
        }
        // if ($debug_mode > 0) {
            // print "Found: " . $address_with_dots . " belongs to " . $country . "<BR>\n";
        // }
        return 1;
    }
    function IncreaseCountryValue(&$countries, $to_search, $number_of_alerts) {
        GLOBAL $db, $debug_mode;
        $php_version = phpversion();
        $ver = $php_version[0];
        // PHP Version 5.x and above
        if ($ver >= 5) {
            if (count($countries) == 0) {
                $countries[$to_search] = $number_of_alerts;
                return;
            }
            if (array_key_exists($to_search, $countries)) {
                // if ($debug_mode > 1) {
                    // print $to_search . " does exist.<BR>\n";
                // }
                $countries[$to_search]+= $number_of_alerts;
            } else {
                // if ($debug_mode > 1) {
                    // print $to_search . " does NOT exist.<BR>\n";
                // }
                $countries[$to_search] = $number_of_alerts;
            }
        } else
        // PHP Version 4.x (and below)
        {
            if (count($countries) == 0) {
                $countries[$to_search] = $number_of_alerts;
                return;
            }
            if (array_key_exists($to_search, $countries)) {
                // if ($debug_mode > 1) {
                    // print $to_search . " does exist.<BR>\n";
                // }
                $countries[$to_search]+= $number_of_alerts;
            } else {
                // if ($debug_mode > 1) {
                    // print $to_search . " does NOT exist.<BR>\n";
                // }
                $countries[$to_search] = $number_of_alerts;
            }
        }
    }
    function GetCountryDataSet(&$xdata, $chart_type, $data_source, $min_threshold, $criteria) {
        GLOBAL $db, $debug_mode, $Geo_IPfree_file_ascii, $IP2CC;
        $country_method = 0;
        if (($chart_type == 14) || ($chart_type == 15))
        // 14 =  Src Countries vs. Num Alerts
        // 15 = dto., but on worldmap
        {
            $sql = "SELECT DISTINCT ip_src, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " AND ip_src is NOT NULL " . "GROUP BY ip_src ORDER BY ip_src";
        } else if (($chart_type == 16) || ($chart_type == 17))
        // 16 = Dst Countries vs. Num Alerts
        // 17 = dto., but on worldmap
        {
            $sql = "SELECT DISTINCT ip_dst, COUNT(acid_event.cid) " . "FROM acid_event " . $criteria[0] . "WHERE " . $criteria[1] . " AND ip_dst is NOT NULL " . "GROUP BY ip_dst ORDER BY ip_dst";
        }
        // if ($debug_mode > 0) echo $sql . "<BR>";
        $result = $db->baseExecute($sql);
        if (!isset($Geo_IPfree_file_ascii) && !isset($IP2CC)) {
            ErrorMessage("ERROR: Neither \$Geo_IPfree_file_ascii nor \$IP2CC has been configured in base_conf.php.<BR>\n");
            return 0;
        } else {
            if (isset($Geo_IPfree_file_ascii)) {
                if (empty($Geo_IPfree_file_ascii)) {
                    ErrorMessage("ERROR: \$Geo_IPfree_file_ascii is an empty string.<BR>\n");
                    return 0;
                } else {
                    if (!is_file($Geo_IPfree_file_ascii)) {
                        ErrorMessage("ERROR: " . $Geo_IPfree_file_ascii . " could not be found. Wrong path, perhaps?<BR>\n");
                        return 0;
                    } else {
                        if (!is_readable($Geo_IPfree_file_ascii)) {
                            ErrorMessage("ERROR: " . $Geo_IPfree_file_ascii . " does exist, but is not readable. Wrong permissions, perhaps?<BR>\n");
                            return 0;
                        } else {
                            $country_method = 1;
                            // if ($debug_mode > 0) {
                                // print "<BR>\ncountry method 1: We use the database of Geo::IPfree<BR>\n<BR>\n";
                            // }
                            // Read in database with country data for ip addresses
                            ReadGeoIPfreeFileAscii($Geo_IPfree_array);
                        }
                    }
                }
            } else if (isset($IP2CC)) {
                if (empty($IP2CC)) {
                    ErrorMessage("ERROR: \$IP2CC is an empty string.<BR>\n");
                    return 0;
                } else {
                    if (!is_file($IP2CC)) {
                        ErrorMessage("ERROR: " . $IP2CC . " could not be found. Wrong path, perhaps?<BR>\n");
                        $rv = ini_get("safe_mode");
                        if ($rv == 1) {
                            print "In &quot;safe_mode&quot; &quot; the file " . $Geo_IPfree_file_ascii . "&quot; must be owned by the user under which the web server is running. Adding it to both safe_mode_exec_dir and to include_path in /etc/php.ini does NOT seem to be sufficient.<BR>\n";
                        }
                        return 0;
                    } else {
                        if (!is_executable($IP2CC)) {
                            ErrorMessage("ERROR: " . $IP2CC . " does exist, but is not executable. Wrong permissions, perhaps?<BR>\n");
                            $rv = ini_get("safe_mode");
                            if ($rv == 1) {
                                ErrorMessage("In &quot;safe_mode&quot; the path &quot;" . dirname($IP2CC) . "&quot; must also be part of safe_mode_exec_dir in /etc/php.ini:<BR><BR>\n" . "safe_mode_exec_dir = &quot;" . dirname($IP2CC) . "&quot;<BR><BR>" . "It seems that not more than ONE SINGLE directory may be assigned to safe_mode_exec_dir.<BR>\n");
                            }
                            return 0;
                        } else {
                            // if ($debug_mode > 0) {
                                // print "<BR>\ncountry_method 2: We make use of ip2cc<BR>\n<BR>\n";
                            // }
                            $country_method = 2;
                        }
                    }
                }
            }
        }
        if ($country_method == 0) {
            // should not be reached
            ErrorMessage("ERROR: No \$country_method available.<BR>\n");
            return 0;
        }
        // Loop through all the ip addresses returned by the sql query
        $cnt = 0;
        while ($myrow = $result->baseFetchRow()) {
            if ($myrow[1] >= $min_threshold) {
                $addresses[$cnt][0] = baseLong2IP($myrow[0]);
                $addresses[$cnt][1] = $myrow[1];
                // xxx jl
                // Which country belongs this ip address to?
                switch ($country_method) {
                    case 1:
                        GeoIPfree_IP2Country($Geo_IPfree_array, $addresses[$cnt][0], $mycountry);
                        break;

                    case 2:
                        run_ip2cc($addresses[$cnt][0], $mycountry);
                        break;

                    default:
                        print "WARNING: country_method no. " . $country_method . " is not supported.<BR>\n";
                        return 0;
                }
                // if ($debug_mode > 0) {
                    // print $mycountry . ": " . $addresses[$cnt][1] . " alerts<BR>\n";
                // }
                // Increase number of alerts for this country
                IncreaseCountryValue($countries, $mycountry, $addresses[$cnt][1]);
                ++$cnt;
            }
        }
        if (!isset($mycountry) || empty($mycountry)) {
            ErrorMessage("ERROR: \$mycountry has not been set as expected.<BR>\n");
            return 0;
        }
        // if ($debug_mode > 1) {
            // print "<pre>############\n";
            // //var_dump($countries);
            // print_r($countries);
            // print "###########</pre>\n";
        // }
        // Now setup the chart array:
        reset($countries);
        $cnt2 = 0;
        while (list($key, $val) = each($countries)) {
            $xdata[$cnt2][0] = $key;
            $xdata[$cnt2][1] = $val;
            $cnt2++;
        }
        $result->baseFreeRows();
        // return number of countries rather than number of addresses!
        return $cnt2;
    }
    // vim: shiftwidth=2:tabstop=2:expandtab
