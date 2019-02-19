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
* - PrintCriteriaState()
* - FieldRows2sql()
* - FormatTimeDigit()
* - addSQLItem()
* - array_count_values_multidim()
* - DateTimeRows2sql()
* - FormatPayload()
* - DataRows2sql()
* - PrintCriteria()
* - QuerySignature()
* - ProcessCriteria()
*/


require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/includes/base_signature.inc.php");
function PrintCriteriaState() {
    GLOBAL $layer4, $new, $submit, $sort_order, $num_result_rows, $current_view, $caller, $action, $action_arg, $sort_order;
    // if ($GLOBALS['debug_mode'] >= 2) {
        // echo "<PRE>";
        // echo "<B>" . gettext("Sensor") . ":</B> " . $_SESSION['sensor'] . "<BR>\n" . "<B>AG:</B> " . $_SESSION['ag'] . "<BR>\n" . "<B>" . gettext("signature") . "</B>\n";
        // print_r($_SESSION['sig']);
        // echo "<BR><B>time struct (" . $_SESSION['time_cnt'] . "):</B><BR>";
        // print_r($_SESSION['time']);
        // echo "<BR><B>" . gettext("IP addresses") . " (" . $_SESSION['ip_addr_cnt'] . "):</B><BR>";
        // print_r($_SESSION['ip_addr']);
        // echo "<BR><B>" . gettext("IP fields") . " (" . $_SESSION['ip_field_cnt'] . "):</B><BR>";
        // print_r($_SESSION['ip_field']);
        // echo "<BR><B>" . gettext("TCP ports") . " (" . $_SESSION['tcp_port_cnt'] . "):</B><BR>";
        // print_r($_SESSION['tcp_port']);
        // echo "<BR><B>" . gettext("TCP flags") . "</B><BR>";
        // print_r($_SESSION['tcp_flags']);
        // echo "<BR><B>" . gettext("TCP fields") . " (" . $_SESSION['tcp_field_cnt'] . "):</B><BR>";
        // print_r($_SESSION['tcp_field']);
        // echo "<BR><B>" . gettext("UDP ports") . " (" . $_SESSION['udp_port_cnt'] . "):</B><BR>";
        // print_r($_SESSION['udp_port']);
        // echo "<BR><B>" . gettext("UDP fields") . " (" . $_SESSION['udp_field_cnt'] . "):</B><BR>";
        // print_r($_SESSION['udp_field']);
        // echo "<BR><B>" . gettext("ICMP fields") . " (" . $_SESSION['icmp_field_cnt'] . "):</B><BR>";
        // print_r($_SESSION['icmp_field']);
        // echo "<BR><B>RawIP field (" . $_SESSION['rawip_field_cnt'] . "):</B><BR>";
        // print_r($_SESSION['rawip_field']);
        // echo "<BR><B>" . gettext("Data") . " (" . $_SESSION['data_cnt'] . "):</B><BR>";
        // print_r($_SESSION['data']);
        // echo "</PRE>";
    // }
    // if ($GLOBALS['debug_mode'] >= 1) {
        // echo "<PRE>
            // <B>new:</B> '$new'
            // <B>submit:</B> '$submit'
            // <B>sort_order:</B> '$sort_order'
            // <B>num_result_rows:</B> '$num_result_rows'  <B>current_view:</B> '$current_view'
            // <B>layer4:</B> '$layer4'  <B>caller:</B> '$caller'
            // <B>action:</B> '$action'  <B>action_arg:</B> '$action_arg'
            // </PRE>";
    // }
}
function FieldRows2sql($field, $cnt, &$s_sql) {
    $tmp2 = "";
    if (!is_array($field)) $field = array();
    for ($i = 0; $i < $cnt; $i++) {
        $tmp = "";
        if ($field[$i][3] != "" && $field[$i][1] != " " && $field[$i][1] != "") {
            $op_aux = ($cnt > 1) ? (($field[$i][5] != "") ? $field[$i][5] : "OR") : "";
            $tmp = $field[$i][0] . " " . $field[$i][1] . " " . $field[$i][2] . " '" . $field[$i][3] . "' " . $field[$i][4] . " " . $op_aux;
        } else {
            if ($field[$i][3] != "" && ($field[$i][1] == " " || $field[$i][1] == "")) ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("A value of") . " '" . $field[$i][3] . "' " . gettext(" was entered for a protocol field, but the particular field was not specified."));
            if (($field[$i][1] != " " && $field[$i][1] != "") && $field[$i][3] == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("A field of") . " '" . $field[$i][1] . "' " . gettext("was selected indicating that it should be a criteria, but no value was specified on which to match."));
        }
        $tmp2 = $tmp2 . $tmp;
        if ($i > 0 && ($field[$i - 1][5] == ' ' || $field[$i - 1][5] == '')) ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("Multiple protocol field criteria entered without a boolean operator (e.g. AND, OR) between them."));
    }
    if ($tmp2 != "") {
        BalanceBrackets($tmp2);
        $s_sql = $s_sql . " AND ( " . $tmp2 . " )";
        return 1;
    }
    BalanceBrackets($s_sql);
    return 0;
}
function FormatTimeDigit($time_digit) {
    if (strlen(trim($time_digit)) == 1) $time_digit = "0" . trim($time_digit);
    return $time_digit;
}
function addSQLItem(&$sstring, $what_to_add) {
    $sstring = (strlen($sstring) == 0) ? "($what_to_add" : "$sstring AND $what_to_add";
}
function array_count_values_multidim($a, $out = false) {
    if ($out === false) $out = array();
    if (is_array($a)) {
        foreach($a as $e) $out = array_count_values_multidim($e, $out);
    } else {
        if (array_key_exists($a, $out)) $out[$a]++;
        else $out[$a] = 1;
    }
    return $out;
}
function DateTimeRows2sql($field, $cnt, &$s_sql) {
    GLOBAL $db;
    $tmp2 = "";
    $allempty = FALSE;
    $time_field = array(
        "mysqli" => ":",
        "mysql"  => ":",
        "mssql"  => ":"
    );
    $minsec = array(
        ">=" => "00",
        "<=" => "59"
    );
    //print_r($field)."<br><br>";
    if ($cnt >= 1 && count($field) == 0) return 0;
    for ($i = 0; $i < $cnt; $i++) {
        $tmp = "";
        if (isset($field[$i]) && $field[$i][1] != " " && $field[$i][1] != "") {
            //echo "entrando $i\n";
            $op = $field[$i][1];
            $t = "";
            /* Build the SQL string when >, >=, <, <= operator is used */
            if ($op != "=") {
                /* date */
                if ($field[$i][4] != " ") {
                    /* create the date string */
                    $t = $field[$i][4]; /* year */
                    if ($field[$i][2] != " ") {
                        $t = $t . "-" . $field[$i][2]; /* month */
                        //echo "<!-- \n\n\n\n\n\n\n dia: -" . $field[$i][3] . "- -->\n\n\n\n\n\n";
                        if ($field[$i][3] != "") $t = $t . "-" . FormatTimeDigit($field[$i][3]); /* day */
                        else $t = (($i == 0) ? $t . "-01" : $t = $t . "-31");
                    } else $t = $t . "-01-01";
                }
                /* time */
                // For MSSQL, you must have colons in the time fields.
                // Otherwise, the DATEDIFF function will return Arithmetic Overflow
                if ($field[$i][5] != "") {
                    $t = $t . " " . FormatTimeDigit($field[$i][5]); /* hour */
                    if ($field[$i][6] != "") {
                        $t = $t . $time_field[$db->DB_type] . FormatTimeDigit($field[$i][6]); /* minute */
                        if ($field[$i][7] != "") $t = $t . $time_field[$db->DB_type] . FormatTimeDigit($field[$i][7]);
                        else $t = $t . $time_field[$db->DB_type] . $minsec[$op];
                    } else $t = $t . $time_field[$db->DB_type] . $minsec[$op] . $time_field[$db->DB_type] . $minsec[$op];
                }
                /* fixup if have a > by adding an extra day */
                else if ($op == ">" && $field[$i][4] != " ") $t = $t . " 23:59:59";
                /* fixup if have a <= by adding an extra day */
                else if ($op == "<=" && $field[$i][4] != " ") $t = $t . " 23:59:59";
                /* neither date or time */
                if ($field[$i][4] == " " && $field[$i][5] == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("An operator of") . " '" . $field[$i][1] . "' " . gettext("was selected indicating that some date/time criteria should be matched, but no value was specified."));
                /* date or date/time */
                else if (($field[$i][4] != " " && $field[$i][5] != "") || $field[$i][4] != " ") {
                    if ($db->DB_type == "oci8") {
                        $tmp = $field[$i][0] . " timestamp " . $op . "to_date( '$t', 'YYYY-MM-DD HH24MISS' )" . $field[$i][8] . ' ' . $field[$i][9];
                    } else {
                        if (count($field) > 1) {
                            // Better fix for bug #1199128
                            // Number of values in each criteria line
                            //print_r($field[$i]);
                            $count = array_count_values_multidim($field[$i]);
                            // Number of empty values
                            $empty = $count[""];
                            // Total number of values in the criteria line (empty or filled)
                            $array_count = count($count);
                            // Check to see if any fields were left empty
                            //if(isset($count[""]))
                            // If the number of empty fields is greater than (impossible) or equal to (possible) the number of values in the array, then they must all be empty
                            //if ($empty >= $array_count)
                            //$allempty = TRUE;
                            // Trim off white space
                            $field[$i][9] = trim($field[$i][9]);
                            // And if the certain line was empty, then we dont care to process it
                            if ($allempty)
                            // So move on
                            continue;
                            else {
                                // Otherwise process it
                                if ($i < $cnt - 1) $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8] . ' ' . CleanVariable($field[$i][9], VAR_ALPHA);
                                else $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8];
                            }
                        } else {
                            // If we just have one criteria line, then do with it what we must
                            if ($i < $cnt - 1) $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8] . ' ' . CleanVariable($field[$i][9], VAR_ALPHA);
                            else $tmp = $field[$i][0] . " timestamp " . $op . "'$t'" . $field[$i][8];
                        }
                    }
                }
                /* time */
                else if (($field[$i][5] != " ") && ($field[$i][5] != "")) {
                    ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("(Invalid Hour) No date criteria were entered with the specified time."));
                }
            }
            /* Build the SQL string when the = operator is used */
            else {
                $query_str = "";
                $query_str = $field[$i][4] . "-";
                $query_str.= $field[$i][2] . "-";
                $query_str.= $field[$i][3] . " ";
                $query_str.= $field[$i][5] . ":";
                $query_str.= $field[$i][6] . ":";
                $query_str.= $field[$i][7] . "";
                $query_str = preg_replace("/\s*\:+\s*$/", "", $query_str);
                addSQLItem($tmp, "timestamp like \"$query_str%\"");
                /* neither date or time */
                if ($tmp == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("An operator of") . " '" . $field[$i][1] . "' " . gettext("was selected indicating that some date/time criteria should be matched, but no value was specified."));
                else if ($i < $cnt - 1) $tmp = $field[$i][0] . $tmp . ') ' . $field[$i][8] . CleanVariable($field[$i][9], VAR_ALPHA);
                else $tmp = $field[$i][0] . $tmp . ') ' . $field[$i][8];
            }
        } else {
            if (isset($field[$i])) {
                if (($field[$i][2] != "" || $field[$i][3] != "" || $field[$i][4] != "") && $field[$i][1] == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("A date/time value of") . " '" . $field[$i][2] . "-" . $field[$i][3] . "-" . $field[$i][4] . " " . $field[$i][5] . ":" . $field[6] . ":" . $field[7] . "' " . gettext("was entered but no operator was selected."));
            }
        }
        if ($i > 0 && $field[$i - 1][9] == ' ' && $field[$i - 1][4] != " ") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("Multiple Date/Time criteria entered without a boolean operator (e.g. AND, OR) between them."));
        $tmp2 = (preg_match("/\s+(AND|OR)\s*$/", $tmp2) || $i == 0) ? $tmp2 . $tmp : $tmp2 . " AND " . $tmp;
    }
    $tmp2 = trim(preg_replace("/(\s*(AND|OR)\s*)+$/", "", $tmp2));
    if ($tmp2 != "" && $tmp2 != "AND" && $tmp2 != "OR") {
        BalanceBrackets($tmp2);
        $s_sql = $s_sql . " AND ( " . $tmp2 . " ) ";
        return 1;
    }
    BalanceBrackets($s_sql);
    return 0;
}
function BalanceBrackets(&$s_sql) {
    $opened = substr_count($s_sql,"(");
    $closed = substr_count($s_sql,")");
    if ($opened>$closed) {
        $diff = $opened-$closed;
        for ($i=0;$i<$diff;$i++) $s_sql = $s_sql.")";
    } elseif ($opened<$closed) {
        $diff = $closed-$opened;
        for ($i=0;$i<$diff;$i++) $s_sql = "(".$s_sql;
    }
    $s_sql = parenthesis_decode($s_sql);
}

/**
 * This function decides if the Grouped view can use the ac_acid_event table instead of acid_event
 * We can only use it when the timestamp criteria is by entire days, or if it's not present at all
 *
 * @return boolean
 */
function time_can_use_ac($field)
{
    $use_ac = TRUE;

    if (is_array($field))
    {
        foreach ($field as $time_criteria)
        {
            $operator = $time_criteria[1];
            $hour     = $time_criteria[5];
            $minute   = $time_criteria[6];
            $second   = $time_criteria[7];

            if (($operator == '>' || $operator == '>=' || $operator == '=' || $operator == '!=')
                    && ($minute > 0 || $second > 0))
            {
                $use_ac = FALSE;
            }

            if (($operator == '<' || $operator == '<=')
                    && ($minute != 59 || $second != 59))
            {
                $use_ac = FALSE;
            }
        }
    }

    return $use_ac;
}

/**
 * This function replaces the parenthesis characters in a string by literals
 *
 * @param string $str
 * @return string
 */
function parenthesis_encode($str)
{
    $str = str_replace('(', 'PARENTHESIS#ESCAPE#LEFT',  $str);
    $str = str_replace(')', 'PARENTHESIS#ESCAPE#RIGHT', $str);

    return $str;
}
/**
 * This function restores the parenthesis characters in a string by literals
 *
 * @param string $str
 * @return string
 */
function parenthesis_decode($str)
{
    $str = str_replace('PARENTHESIS#ESCAPE#LEFT', '(',  $str);
    $str = str_replace('PARENTHESIS#ESCAPE#RIGHT', ')', $str);

    return $str;
}

function FormatPayload($payload_str, $data_encode)
/* Accepts a payload string and decides whether any conversion is necessary
to create a sql call into the DB.  Currently we only are concerned with
hex <=> ascii.
*/ {
    /* if the source is hex strip out any spaces and \n */
    if ($data_encode == "hex") {
        $payload_str = str_replace("\n", "", $payload_str);
        $payload_str = str_replace(" ", "", $payload_str);
    }
    /* If both the source type and conversion type are the same OR
    no conversion type is specified THEN return the plain string */
    if (($data_encode[0] == $data_encode[1]) || $data_encode[1] == " ") {
        return $payload_str;
    } else {
        $tmp = "";
        /* hex => ascii */
        if ($data_encode[0] == "hex" && $data_encode[1] == "ascii") for ($i = 0; $i < strlen($payload_str); $i+= 2) {
            $t = hexdec($payload_str[$i] . $payload_str[$i + 1]);
            if ($t > 32 && $t < ord("z")) $tmp = $tmp . chr($t);
            else $tmp = $tmp . '.';
        }
        /* ascii => hex */
        else if ($data_encode[0] == "ascii" && $data_encode[1] == "hex") for ($i = 0; $i < strlen($payload_str); $i++) $tmp = $tmp . dechex(ord($payload_str[$i]));
        return strtoupper($tmp);
    }
    return ""; /* should be unreachable */
}
function DataRows2sql($field, $cnt, $data_encode, &$s_sql, $conn_aux) {
    $tmp2 = "";
    //print "cnt para $field: $cnt<br>";
    for ($i = 0; $i < $cnt; $i++) {
        $tmp = "";
        if ($field[$i][2] != "" && $field[$i][1] != " ") {
            //$tmp = $field[$i][0]." data_payload ".$field[$i][1]." '%".FormatPayload($field[$i][2], $data_encode).
            //       "%' ".$field[$i][3]."".$field[$i][4]." ".$field[$i][5];
            $data_encode1 = array(
                "ascii",
                "hex"
            );

            /*
             * Prepare search string:
             * - html_entity_decode() The string here is with htmlentities, chars like &quot; must be "
             * - escape_sql()
             */
            $search_str = FormatPayload($field[$i][2], $data_encode);
            $search_str = html_entity_decode($search_str, ENT_QUOTES, 'ISO-8859-1');
            $search_str = escape_sql($search_str, $conn_aux);

            $and_str = preg_split("/\s+AND\s+/",$search_str);
            $ands = array();
            foreach ($and_str as $and) { // apply AND logic
                $or_str = preg_split("/\s+OR\s+/",$and);
                $ors = array();
                foreach ($or_str as $or) {
                    // apply ! and OR operators
                    if (preg_match("/^\!(.*)/",$or,$fnd)) {
                        // Negated as AND
                        //$encoded = FormatPayload($fnd[1], $data_encode1);
                        //$ors[]   = "(data_payload NOT LIKE '%".$fnd[1]."%' AND data_payload NOT LIKE '%".$encoded."%')";
                        $ors[]   = "(data_payload NOT LIKE '%".$fnd[1]."%')";
                    } elseif ($field[$i][1] == "NOT LIKE") {
                        // Negated as AND
                        //$encoded = FormatPayload($or, $data_encode1);
                        //$ors[]   = "(data_payload NOT LIKE '%".$or."%' AND data_payload NOT LIKE '%".$encoded."%')";
                        $ors[]   = "(data_payload NOT LIKE '%".$or."%')";
                    } else {
                        //$encoded = FormatPayload($or, $data_encode1);
                        //$ors[]   = "(data_payload LIKE '%".$or."%' OR data_payload LIKE '%".$encoded."%')";
                        $ors[]   = "(data_payload LIKE '%".$or."%')";
                    }
                }
                $ands[] = "(".implode(" OR ",$ors).")";
            }

            $tmp = " acid_event.id=extra_data.event_id AND (".implode(" AND ",$ands).")";

        } else {
            if ($field[$i][2] != "" && $field[$i][1] == " ") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("A payload value of") . " '" . $field[$i][2] . "' " . gettext("was entered for a payload criteria field, but an operator (e.g. has, has not) was not specified."));

            // Warning message commented to be the same as signature
            //if (($field[$i][1] != " " && $field[$i][1] != "") && $field[$i][2] == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("An operator of") . " '" . $field[$i][1] . "' " . gettext("was selected indicating that payload should be a criteria, but no value on which to match was specified."));
        }
        $union = ($i > 0) ? (($field[$i - 1][4] == "AND" || $field[$i - 1][4] == "OR") ? " ".$field[$i - 1][4]." " : " OR ") : "";

        if ($tmp != '')
        {
            $tmp2 = $tmp2 . $union . $tmp;
        }

        if ($i > 0 && ($field[$i - 1][4] == ' ' || $field[$i - 1][4] == '')) ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("Multiple Data payload criteria entered without a boolean operator (e.g. AND, OR) between them."));
    }
    if ($tmp2 != "") {
        $s_sql = $s_sql . " AND ( " . $tmp2 . " )";
        return 1;
    }
    return 0;
}
function PrintCriteria($caller) {
    GLOBAL $db, $cs, $last_num_alerts, $save_criteria;
    /* Generate the Criteria entered into a human readable form */
    $criteria_arr = array();
    /* If printing any of the LAST-X stats then ignore all the other criteria */
    if ($caller == "last_tcp" || $caller == "last_udp" || $caller == "last_icmp" || $caller == "last_any") {
        $save_criteria = $save_criteria . '&nbsp;&nbsp;';
        if ($caller == "last_tcp") $save_criteria.= gettext("Last") . ' ' . intval($last_num_alerts) . ' TCP ' . gettext("Event");
        else if ($caller == "last_udp") $save_criteria.= gettext("Last") . ' ' . intval($last_num_alerts) . ' UDP ' . gettext("Event");
        else if ($caller == "last_icmp") $save_criteria.= gettext("Last") . ' ' . intval($last_num_alerts) . ' ICMP ' . gettext("Event");
        else if ($caller == "last_any") $save_criteria.= gettext("Last") . ' ' . intval($last_num_alerts) . ' ' . gettext("Event");
        $save_criteria.= '&nbsp;&nbsp;</TD></TR></TABLE>';
        echo $save_criteria;
        return;
    }
    $tmp_len = strlen($save_criteria);
    //$save_criteria .= $cs->criteria['sensor']->Description();
    //$save_criteria .= $cs->criteria['sig']->Description();
    //$save_criteria .= $cs->criteria['sig_class']->Description();
    //$save_criteria .= $cs->criteria['sig_priority']->Description();
    //$save_criteria .= $cs->criteria['ag']->Description();
    //$save_criteria .= $cs->criteria['time']->Description();
    //$criteria_arr['meta'] = preg_replace ("/\[\d+\,\d+.*\]\s*/","",$cs->criteria['sensor']->Description());
    $criteria_arr['meta'] = $cs->criteria['sensor']->Description();
    $criteria_arr['meta'].= $cs->criteria['plugin']->Description();
    $criteria_arr['meta'].= $cs->criteria['plugingroup']->Description();
    $criteria_arr['meta'].= $cs->criteria['userdata']->Description();
    $criteria_arr['meta'].= $cs->criteria['sourcetype']->Description();
    $criteria_arr['meta'].= $cs->criteria['category']->Description();
    $criteria_arr['meta'].= $cs->criteria['sig']->Description();
    //$criteria_arr['meta'].= $cs->criteria['sig_class']->Description();
    //$criteria_arr['meta'].= $cs->criteria['sig_priority']->Description();
    //$criteria_arr['meta'].= $cs->criteria['ag']->Description();
    $criteria_arr['meta'].= $cs->criteria['time']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_risk_a']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_priority']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_reliability']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_asset_dst']->Description();
    $criteria_arr['meta'].= $cs->criteria['ossim_type']->Description();
    $criteria_arr['meta'].= $cs->criteria['device']->Description();
    $criteria_arr['meta'].= $cs->criteria['otx']->Description();
    if ($criteria_arr['meta'] == "") {
        $criteria_arr['meta'].= '<I> ' . gettext("any") . ' </I>';
        $save_criteria.= '<I> ' . gettext("any") . ' </I>';
    }
    $save_criteria.= '&nbsp;&nbsp;</TD>';
    $save_criteria.= '<TD>';
    if (!$cs->criteria['ctx']->isEmpty() || !$cs->criteria['ip_addr']->isEmpty() || !$cs->criteria['ip_field']->isEmpty() || !$cs->criteria['networkgroup']->isEmpty() || !$cs->criteria['idm_username']->isEmpty() || !$cs->criteria['idm_hostname']->isEmpty() || !$cs->criteria['idm_domain']->isEmpty()  || !$cs->criteria['rep']->isEmpty() || !$cs->criteria['hostid']->isEmpty() || !$cs->criteria['netid']->isEmpty()) {
        $criteria_arr['ip'] = $cs->criteria['ctx']->Description();
        $criteria_arr['ip'].= $cs->criteria['idm_username']->Description();
        $criteria_arr['ip'].= $cs->criteria['idm_hostname']->Description();
        $criteria_arr['ip'].= $cs->criteria['idm_domain']->Description();
        $criteria_arr['ip'].= $cs->criteria['networkgroup']->Description();
        $criteria_arr['ip'].= $cs->criteria['hostid']->Description();
        $criteria_arr['ip'].= $cs->criteria['netid']->Description();
        $criteria_arr['ip'].= $cs->criteria['ip_addr']->Description();
        $criteria_arr['ip'].= $cs->criteria['ip_field']->Description();
        $criteria_arr['ip'].= $cs->criteria['rep']->Description();
        $save_criteria .= $cs->criteria['ip_addr']->Description();
        $save_criteria .= $cs->criteria['ip_field']->Description();
        if ($criteria_arr['ip']=="") {
            $criteria_arr['ip'] = '<I> ' . gettext("any") . ' </I>';
        }
    } else {
        $save_criteria.= '<I> &nbsp;&nbsp; ' . gettext("any") . ' </I>';
        $criteria_arr['ip'] = '<I> ' . gettext("any") . ' </I>';
    }
    $save_criteria.= '&nbsp;&nbsp;</TD>';
    $save_criteria.= '<TD CLASS="layer4title">';
    $save_criteria.= $cs->criteria['layer4']->Description();
    $save_criteria.= '</TD><TD>';
    if ($cs->criteria['layer4']->Get() == "TCP") {
        //if (!$cs->criteria['tcp_port']->isEmpty() || !$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) {
        if (isset($cs->criteria['tcp_port']) && !$cs->criteria['tcp_port']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['tcp_port']->Description();
            //$criteria_arr['layer4'].= $cs->criteria['tcp_flags']->Description();
            //$criteria_arr['layer4'].= $cs->criteria['tcp_field']->Description();
            $save_criteria.= $cs->criteria['tcp_port']->Description();
            //$save_criteria.= $cs->criteria['tcp_flags']->Description();
            //$save_criteria.= $cs->criteria['tcp_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . gettext("any") . ' </I>';
            $save_criteria.= '<I> &nbsp;&nbsp; ' . gettext("any") . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else if ($cs->criteria['layer4']->Get() == "UDP") {
        //if (!$cs->criteria['udp_port']->isEmpty() || !$cs->criteria['udp_field']->isEmpty()) {
        if (isset($cs->criteria['udp_port']) && !$cs->criteria['udp_port']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['udp_port']->Description();
            //$criteria_arr['layer4'].= $cs->criteria['udp_field']->Description();
            $save_criteria.= $cs->criteria['udp_port']->Description();
            //$save_criteria.= $cs->criteria['udp_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . gettext("any") . ' </I>';
            $save_criteria.= '<I> &nbsp;&nbsp; ' . gettext("any") . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else if ($cs->criteria['layer4']->Get() == "ICMP") {
        if (!$cs->criteria['icmp_field']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['icmp_field']->Description();
            $save_criteria.= $cs->criteria['icmp_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . gettext("any") . ' </I>';
            $save_criteria.= '<I> &nbsp;&nbsp; ' . gettext("any") . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else if ($cs->criteria['layer4']->Get() == "RawIP") {
        if (!$cs->criteria['rawip_field']->isEmpty()) {
            $criteria_arr['layer4'] = $cs->criteria['rawip_field']->Description();
            $save_criteria.= $cs->criteria['rawip_field']->Description();
        } else {
            $criteria_arr['layer4'] = '<I> ' . gettext("any") . ' </I>';
            $save_criteria.= '<I> &nbsp&nbsp ' . gettext("any") . ' </I>';
        }
        $save_criteria.= '&nbsp;&nbsp;</TD>';
    } else {
        $criteria_arr['layer4'] = '<I> ' . gettext("none") . ' </I>';
        $save_criteria.= '<I> &nbsp;&nbsp; ' . gettext("none") . ' </I></TD>';
    }
    /* Payload ************** */
    $save_criteria.= '
        <TD>';
    if (!$cs->criteria['data']->isEmpty()) {
        $criteria_arr['payload'] = $cs->criteria['data']->Description();
        $save_criteria.= $cs->criteria['data']->Description();
    } else {
        $criteria_arr['payload'] = '<I> ' . gettext("any") . ' </I>';
        $save_criteria.= '<I> &nbsp;&nbsp; ' . gettext("any") . ' </I>';
    }
    $save_criteria.= '&nbsp;&nbsp;</TD>';
    if (!setlocale(LC_TIME, gettext("eng_ENG.ISO8859-1"))) if (!setlocale(LC_TIME, gettext("eng_ENG.utf-8"))) setlocale(LC_TIME, gettext("english"));

    // Report Data
    // Only event listings will store in datawarehouse report data
    if ($_SERVER['SCRIPT_NAME'] != "/ossim/forensics/base_stat_ipaddr.php")
    {
        $report_data = array();
        $r_meta = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;|,\s+$/i","",preg_replace("/\<br\>/i",", ",$criteria_arr['meta']));
        $r_payload = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['payload']);
        $r_ip = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['ip']);
        $r_l4 = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['layer4']);
        $report_data[] = array (_("META"),strip_tags($r_meta),"","","","","","","","","",0,0,0);
        $report_data[] = array (_("PAYLOAD"),strip_tags($r_payload),"","","","","","","","","",0,0,0);
        $report_data[] = array (_("IP"),strip_tags($r_ip),"","","","","","","","","",0,0,0);
        $report_data[] = array (_("LAYER 4"),strip_tags($r_l4),"","","","","","","","","",0,0,0);
        SaveCriteriaReportData($report_data);
    }
?>
<TABLE class="transparent" BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
    <TR>
        <TD style="padding-top:10px;padding-bottom:10px">
            <TABLE class="transparent" BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
                <TR><td align="center" class="headerpr" style="border-bottom:none">
                    <table class="transparent" width="100%" style="border:none">
                        <tr>
                            <td style="text-align:center;color:white;font-size:14px">&nbsp;<?php echo _("Current Search Criteria")?>&nbsp;&nbsp; [<a href="base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d" style="font-weight:normal;color:white">...<?php echo _("Clear All Criteria") ?>...</a>]</td>
                            <td width="150" nowrap><a href="base_view_criteria.php" style="color:white" onclick="GB_show('<?=_("Current Search Criteria")?>','/forensics/base_view_criteria.php',420,600);return false"><?php echo _("Show full criteria")?> <img src="../pixmaps/ui-scroll-pane-detail.png" border="0" alt="<?php echo _("View entire current search criteria") ?>" title="<?php echo _("View entire current search criteria") ?>"></img></a></td>
                        </tr>
                    </table>
                    </td>
                </TR>
                <TR>
                    <TD style="border:1px solid #C4C0BB">
                        <table class="transparent" cellpadding=0 cellspacing=0 border=0 WIDTH="100%">
                            <tr>
                                <th style="border:none;border-right:1px solid #C4C0BB;border-bottom:1px solid #C4C0BB;background:none;background-color:#C4C0BB"><?=_("META")?></th>
                                <th style="border:none;padding-left:5px;padding-right:5px;border-right:1px solid #C4C0BB;background:none;border-bottom:1px solid #C4C0BB;background-color:#C4C0BB"><?=_("PAYLOAD")?></th>
                                <th style="border:none;border-right:1px solid #C4C0BB;border-bottom:1px solid #C4C0BB;background:none;background-color:#C4C0BB">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?=($_SESSION["_idm"]) ? _("IDM") : _("IP")?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                                <th style="border:none;padding-left:5px;padding-right:5px;border-bottom:1px solid #C4C0BB;background:none;background-color:#C4C0BB" nowrap><?=_("LAYER 4")?></th>
                            </tr>
                            <tr>
                                <td align=center valign="top" style="border-right:1px solid #C4C0BB;padding:3px;font-weight:bold"><?php echo $criteria_arr['meta'] ?></td>
                                <td align=center valign="top" style="border-right:1px solid #C4C0BB;padding:3px;font-weight:bold"><?php echo $criteria_arr['payload'] ?></td>
                                <td align=center valign="top" style="border-right:1px solid #C4C0BB;padding:3px;font-weight:bold"><?php echo $criteria_arr['ip'] ?></td>
                                <td align=center valign="top" style="padding:3px;font-weight:bold"><?php echo $criteria_arr['layer4'] ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?php
}

function get_criteria_main_type($type)
{
    if ($type == "idm_username"
    || $type == "idm_hostname"
    || $type == "idm_domain"
    || $type == "hostid"
    || $type == "netid"
    || $type == "ip_addr"
    || $type == "ip_field"
    || $type == "networkgroup"
    || $type == "ctx"
    || $type == "rep")
    {
        return "ip";
    }
    elseif ($type == "tcp_port" || $type == "udp_port")
    {
        return "layer4";
    }
    elseif ($type == "data")
    {
        return "payload";
    }
    else
    {
        return "meta";
    }
}

function PrintCriteria2() {
    GLOBAL $db, $cs, $last_num_alerts, $save_criteria;
    /* Generate the Criteria entered into a human readable form */
    $criteria_arr = array();

    $tmp_len = strlen($save_criteria);

    $meta_keys = array("sensor",
            "plugin",
            "plugingroup",
            "userdata",
            "sourcetype",
            "category",
            "sig",
            "time",
            "ossim_risk_a",
            "ossim_priority",
            "ossim_reliability",
            "ossim_asset_dst",
            "ossim_type",
            "device",
            "ctx",
            "idm_username",
            "idm_hostname",
            "idm_domain",
            "networkgroup",
            "hostid",
            "netid",
            "ip_addr",
            "ip_field",
            "rep",
            "otx",
            "tcp_port",
            "udp_port"
    );

    // data encoded as ascii exception
    if (!$cs->criteria['data']->isEmpty())
    {
        $meta_keys[] = "data";
    }

    foreach ($meta_keys as $key)
    {
        if ($cs->criteria[$key]->Description() != "")
        {
            if (method_exists($cs->criteria[$key], "Description_light"))
            {
                $name =  $cs->criteria[$key]->Description_light();
            }
            else
            {
                $name =  $cs->criteria[$key]->Description();
            }

            $c_type = get_criteria_main_type($key);
            $criteria_report[$c_type] .= ($criteria_report[$c_type] != "") ? ", ".$name : $name;

            $crit_name      = $cs->criteria[$key]->export_name;
            $url            = $cs->GetClearCriteriaUrl($crit_name);
            $criteria_arr[] = '<li data-info="'. $url .'">'. $name .'</li>';
        }
    }

    if (!setlocale(LC_TIME, gettext("eng_ENG.ISO8859-1"))) if (!setlocale(LC_TIME, gettext("eng_ENG.utf-8"))) setlocale(LC_TIME, gettext("english"));

    // Report Data
    // Only event listings will store in datawarehouse report data
    if ($_SERVER['SCRIPT_NAME'] != "/ossim/forensics/base_stat_ipaddr.php")
    {
        $report_data = array();
        $r_meta = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;|,\s+$/i","",preg_replace("/\<br\>/i",", ",$criteria_report['meta']));
        $r_payload = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_report['payload']);
        $r_ip = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_report['ip']);
        $r_l4 = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_report['layer4']);
        $report_data[] = array (_("META"),strip_tags($r_meta),"","","","","","","","","",0,0,0);
        $report_data[] = array (_("PAYLOAD"),strip_tags($r_payload),"","","","","","","","","",0,0,0);
        $report_data[] = array (_("IP"),strip_tags($r_ip),"","","","","","","","","",0,0,0);
        $report_data[] = array (_("LAYER 4"),strip_tags($r_l4),"","","","","","","","","",0,0,0);
        SaveCriteriaReportData($report_data);
    }
    ?>
<div>

                    <div>
                            <div class='siem_form_clear'>
                                <a href="base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d" class="uppercase"><?php echo _("Clear Filters") ?></a>
                                <a href="" onclick="re_load();return false"><img src="../pixmaps/forensic_refresh.png" border="0" class='siem_refresh_img'/></a>
                            </div>
                            <div class='clear_layer'></div>
                    </div>

                    <div>
                        <ul id="criteria_tagit">
                        <?php
                        echo implode('', $criteria_arr);
                        ?>
                        </ul>
                    </div>
</div>
<?php
}

function SaveCriteriaReportData($data) {
    GLOBAL $db, $criteria_report_type;
    $db->baseExecute("DELETE FROM datawarehouse.report_data WHERE id_report_data_type=$criteria_report_type and user='".$_SESSION["_user"]."'");
    foreach ($data as $arr) {
        $more = "";
        foreach ($arr as $val) $more .= ",'".str_replace("'","\'",$val)."'";
        $sql = "INSERT INTO datawarehouse.report_data (id_report_data_type,user,dataV1,dataV2,dataV3,dataV4,dataV5,dataV6,dataV7,dataV8,dataV9,dataV10,dataV11,dataI1,dataI2,dataI3) VALUES ($criteria_report_type,'".$_SESSION["_user"]."'".$more.")";
        //echo $sql."<br>";
        $db->baseExecute($sql, $db);
    }
}
function QueryOssimSignature($q, $cmd, $cmp) {
    global $db;
    $ids = "";

    /*
     * Prepare search string:
    * - html_entity_decode() The string here is with htmlentities, chars like &quot; must be "
    * - escape_sql()
    */
    $q = html_entity_decode($q, ENT_QUOTES, 'ISO-8859-1');
    $q = escape_sql($q, $db->DB);

    if (preg_match("/.* OR .*|.* AND .*/",$q)) {
        $or_str = ($cmd == "=") ? "' OR plugin_sid.name = '" : "%' OR plugin_sid.name LIKE '%";
        $and_str = ($cmd == "=") ? "' AND plugin_sid.name = '" : "%' AND plugin_sid.name LIKE '%";
        $q = str_replace(" OR ",$or_str,$q);
        $q = str_replace(" AND ",$and_str,$q);
    }

    $q = parenthesis_encode($q);

    $op = ($cmd == "=") ? "plugin_sid.name = '$q'" : "plugin_sid.name LIKE '%" . $q . "%'";
    // apply ! operator
    $op = str_replace(" = '!"," != '",$op);
    $op = str_replace(" LIKE '%!"," NOT LIKE '%",$op);
    return $op;
    /*
    $sql = "SELECT plugin_id,sid FROM alienvault.plugin_sid WHERE $op";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow())
            if ($cmp == "!=")
                $ids.= "(plugin_id<>".$row[0]." AND plugin_sid<>".$row[1].")AND";
            else
                $ids.= "(plugin_id=".$row[0]." AND plugin_sid=".$row[1].")OR";
    }
    $ids = preg_replace("/(OR|AND)$/", "", $ids);
    $result->baseFreeRows();
    return trim($ids);*/
}
function QueryOssimSignatureTmpTable($q, $cmd, $cmp) {
    global $db;
    $ids = "";
    /*
     * Prepare search string:
    * - html_entity_decode() The string here is with htmlentities, chars like &quot; must be "
    * - escape_sql()
    */
    $q = html_entity_decode($q, ENT_QUOTES, 'ISO-8859-1');
    $q = escape_sql($q, $db->DB);

    if (preg_match("/.* OR .*|.* AND .*/",$q)) {
        $or_str = ($cmd == "=") ? "' OR plugin_sid.name = '" : "%' OR plugin_sid.name LIKE '%";
        $and_str = ($cmd == "=") ? "' AND plugin_sid.name = '" : "%' AND plugin_sid.name LIKE '%";
        $q = str_replace(" OR ",$or_str, $q);
        $q = str_replace(" AND ",$and_str, $q);
    }

    $q = parenthesis_encode($q);

    $op = ($cmd == "=") ? "plugin_sid.name = '$q'" : "plugin_sid.name LIKE '%" . $q . "%'";
    // apply ! operator
    $op = str_replace(" = '!"," != '",$op);
    $op = str_replace(" LIKE '%!"," NOT LIKE '%",$op);

    $_user = Session::get_session_user();
    $db->DB->Execute('CREATE TABLE IF NOT EXISTS alienvault_siem.plugins_join (id int(11) NOT NULL, sid int(11) NOT NULL, login VARCHAR(64) NOT NULL, PRIMARY KEY (id,sid,login)) ENGINE=MEMORY');
    $db->DB->Execute('DELETE FROM alienvault_siem.plugins_join WHERE login=?',array($_user));
    $sql = "INSERT IGNORE INTO alienvault_siem.plugins_join SELECT plugin_id,sid,? FROM alienvault.plugin_sid WHERE $op";
    if (file_exists('/tmp/debug_siem'))
    {
        file_put_contents("/tmp/siem", "TMP TABLE:$sql\n", FILE_APPEND);
    }
    $db->DB->Execute($sql,array($_user));

    $plugin_join = " INNER JOIN alienvault_siem.plugins_join ON acid_event.plugin_id=plugins_join.id AND acid_event.plugin_sid=plugins_join.sid AND plugins_join.login='$_user'";

    return $plugin_join;
}
/********************************************************************************************/
function QueryOssimPluginGroup($pgid) {
    GLOBAL $db;
    $ids = "";
    $sql = "SELECT plugin_id,plugin_sid FROM alienvault.plugin_group_descr WHERE group_id=unhex('$pgid')";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow()) {
            if ($row["plugin_sid"] == "0" || $row["plugin_sid"] == "ANY")
                $ids.= "(acid_event.plugin_id=".$row["plugin_id"].")OR";
            else {
                $sids = explode(",",$row["plugin_sid"]);
                foreach ($sids as $sid)
                    $ids.= "(acid_event.plugin_id=".$row["plugin_id"]." AND acid_event.plugin_sid=".$sid.")OR";
            }
        }
    }
    $ids = preg_replace("/(OR|AND)$/", "", $ids);
    $result->baseFreeRows();
    return trim($ids);
}
/********************************************************************************************/
function QueryOssimNetworkGroup($ngid) {
    GLOBAL $db;

    $ids = "";
    $sql = "SELECT hex(net_id) as id FROM alienvault.net_group_reference WHERE net_group_id=unhex('$ngid')";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow()) {
            $ids.= "acid_event.src_net=unhex('".$row["id"]."') OR acid_event.dst_net=unhex('".$row["id"]."') OR ";
        }
    }
    $ids = preg_replace("/ OR $/", "", $ids);
    $result->baseFreeRows();
    return trim($ids);
}
/********************************************************************************************/
function GetPluginListBySourceType($sourcetype) {
    GLOBAL $db;
    $ids = array(0);
    if ($sourcetype==_("Unknown type"))
        $sql = "SELECT id FROM alienvault.plugin WHERE product_type=0 OR product_type is NULL";
    else
        $sql = "SELECT id FROM alienvault.plugin WHERE product_type=$sourcetype";
    if ($result = $db->baseExecute($sql)) {
        while ($row = $result->baseFetchRow())
            $ids[] = $row["id"];
    }
    $result->baseFreeRows();
    return implode(",",$ids);
}
/********************************************************************************************/
function GetPluginListByCategory($category,$byidsid=false) {
    GLOBAL $db;
    //
    $ids = "";
    if ($byidsid) { // plugin_id,sid list
        $sql = "SELECT plugin_id,sid FROM alienvault.plugin_sid WHERE category_id=".$category[0];
        if ($category[1]!=0) $sql .= " and subcategory_id=".$category[1];
        if ($result = $db->baseExecute($sql)) {
            while ($row = $result->baseFetchRow())
                $ids.= "(acid_event.plugin_id=".$row["plugin_id"]." AND acid_event.plugin_sid=".$row["sid"].")OR";
        }
        if ($ids!="")
            $ids = " AND (".preg_replace("/(OR|AND)$/", "", $ids).")";
        else
            $ids = " AND (acid_event.plugin_id=0 AND acid_event.plugin_sid=0)";
        $result->baseFreeRows();
    }
    else { // where on plugin_sid table
        $ids = " AND plugin_sid.category_id=".$category[0];
        if ($category[1]!=0) $ids .= " AND plugin_sid.subcategory_id=".$category[1];
    }
    return $ids;
}
/********************************************************************************************/
function ProcessCriteria() {
    GLOBAL $db, $join_sql, $perms_sql, $where_sql, $criteria_sql, $sql, $debug_mode, $caller, $DBtype;
    /* XXX-SEC */
    GLOBAL $cs,$timetz;

    /* the JOIN criteria */
    $ip_join_sql = " LEFT JOIN iphdr ON acid_event.sid=iphdr.sid AND acid_event.cid=iphdr.cid ";

    // *************** DEPRECATED: TCP UDP ICMP join *********************
    //$tcp_join_sql = " LEFT JOIN tcphdr ON acid_event.sid=tcphdr.sid AND acid_event.cid=tcphdr.cid ";
    //$udp_join_sql = " LEFT JOIN udphdr ON acid_event.sid=udphdr.sid AND acid_event.cid=udphdr.cid ";
    //$icmp_join_sql = " LEFT JOIN icmphdr ON acid_event.sid=icmphdr.sid AND acid_event.cid=icmphdr.cid ";

    $rawip_join_sql = " LEFT JOIN iphdr ON acid_event.sid=iphdr.sid AND acid_event.cid=iphdr.cid ";
    $sig_join_sql= " LEFT JOIN alienvault.plugin_sid ON acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid ";
    $sig_join = false;
    $sig_join_tmp = "";
    $data_join_sql = "";

    //SQL_CALC_FOUND_ROWS
    $sql = "SELECT acid_event.*, HEX(acid_event.ctx) AS ctx, HEX(acid_event.src_host) AS src_host, HEX(acid_event.dst_host) AS dst_host, HEX(acid_event.src_net) AS src_net, HEX(acid_event.dst_net) AS dst_net FROM acid_event";
    $where_sql = " WHERE ";
    //$where_sql = "";
    // $criteria_sql = " acid_event.sid > 0";
    // Initially show last 24hours events
    if ($_GET['time_range'] == "") $criteria_sql = " ( timestamp >='" . gmdate("Y-m-d",$timetz) . "' ) ";
    else $criteria_sql = " 1 ";
    //$criteria_sql = " ( timestamp <= CURDATE() ) ";
    //$criteria_sql = " 1 ";
    $join_sql = "";
    $use_ac = true; // Use ac_acid_event or not
    $sfilter = false;
    $criteria_sql_ac = $criteria_sql;
    /* ********************** Meta Criteria ******************************************** */
    $sig = $cs->criteria['sig']->criteria;
    $sig_type = $cs->criteria['sig']->sig_type;
    $sig_class = $cs->criteria['sig_class']->criteria;
    $sig_priority = $cs->criteria['sig_priority']->criteria;
    $ag = $cs->criteria['ag']->criteria;
    $sensor = $cs->criteria['sensor']->criteria;
    $sensor_op = ($cs->criteria['sensor']->param) ? "not in" : "in";
    $plugin = $cs->criteria['plugin']->criteria;
    $plugingroup = $cs->criteria['plugingroup']->criteria;
    $networkgroup = $cs->criteria['networkgroup']->criteria;
    $userdata = $cs->criteria['userdata']->criteria;
    $idm_username = $cs->criteria['idm_username']->criteria;
    $idm_hostname = $cs->criteria['idm_hostname']->criteria;
    $idm_domain = $cs->criteria['idm_domain']->criteria;
    $sourcetype = $cs->criteria['sourcetype']->criteria;
    $category = $cs->criteria['category']->criteria;
    $rep = $cs->criteria['rep']->criteria;
    $otx = $cs->criteria['otx']->criteria;
    $time = $cs->criteria['time']->GetUTC();
    $real_time = $cs->criteria['time']->criteria;
    //print_r($time);
    $time_cnt = $cs->criteria['time']->GetFormItemCnt();
    $hostid = $cs->criteria['hostid']->criteria;
    $netid = $cs->criteria['netid']->criteria;
    $ctx = $cs->criteria['ctx']->criteria;
    $device = $cs->criteria['device']->criteria;
    $ip_addr = $cs->criteria['ip_addr']->criteria;
    $ip_addr_cnt = $cs->criteria['ip_addr']->GetFormItemCnt();
    $layer4 = $cs->criteria['layer4']->criteria;
    $ip_field = $cs->criteria['ip_field']->criteria;
    $ip_field_cnt = $cs->criteria['ip_field']->GetFormItemCnt();
    $tcp_port = $cs->criteria['tcp_port']->criteria;
    $tcp_port_cnt = $cs->criteria['tcp_port']->GetFormItemCnt();

    // DEPRECATED tcp flags
    //$tcp_flags = $cs->criteria['tcp_flags']->criteria;
    //$tcp_field = $cs->criteria['tcp_field']->criteria;
    //$tcp_field_cnt = $cs->criteria['tcp_field']->GetFormItemCnt();

    $udp_port = $cs->criteria['udp_port']->criteria;
    $udp_port_cnt = $cs->criteria['udp_port']->GetFormItemCnt();

    // DEPRECATED udp field icmp field
    //$udp_field = $cs->criteria['udp_field']->criteria;
    //$udp_field_cnt = $cs->criteria['udp_field']->GetFormItemCnt();
    //$icmp_field = $cs->criteria['icmp_field']->criteria;
    //$icmp_field_cnt = $cs->criteria['icmp_field']->GetFormItemCnt();

    $rawip_field = $cs->criteria['rawip_field']->criteria;
    $rawip_field_cnt = $cs->criteria['rawip_field']->GetFormItemCnt();
    $data = $cs->criteria['data']->criteria;
    $data_cnt = $cs->criteria['data']->GetFormItemCnt();
    $data_encode = $cs->criteria['data']->data_encode; //$data_encode[0] = "ascii"; $data_encode[1] = "hex";
    /* OSSIM */
    $ossim_type = $cs->criteria['ossim_type']->criteria;
    $ossim_priority = $cs->criteria['ossim_priority']->criteria;
    $ossim_reliability = $cs->criteria['ossim_reliability']->criteria;
    $ossim_asset_dst = $cs->criteria['ossim_asset_dst']->criteria;
    $ossim_risk_a = $cs->criteria['ossim_risk_a']->criteria;
    $tmp_meta = "";

    /* Sensor */
    if ($sensor != "" && $sensor != " ") $tmp_meta = $tmp_meta . " AND acid_event.device_id $sensor_op ( ".preg_replace("/^\!/","",$sensor)." )";
    else {
        $cs->criteria['sensor']->Set("");
    }

    /* Device */
    if ($device != "") {
        $_ip = bin2hex(inet_pton($device));
        $tmp_meta .= " AND acid_event.device_id IN (SELECT id FROM device WHERE device_ip=UNHEX('".$_ip."'))";
    }

    /* Plugin */
    if ($plugin != "" && $plugin != " ") {
        if (preg_match("/(\d+)\-(\d+)/",$plugin,$match))
            $tmp_meta = $tmp_meta . " AND acid_event.plugin_id between " . $match[1] . " and ". $match[2];
        else
            $tmp_meta = $tmp_meta . " AND acid_event.plugin_id in (" . $plugin . ")";
        $sfilter = true;
    }
    /* Plugin Group */
    if ($plugingroup != "" && $plugingroup != " ") {
        $pg_ids = QueryOssimPluginGroup($plugingroup);
        if ($pg_ids != "")
            $tmp_meta = $tmp_meta . " AND ($pg_ids) ";
        else
            $tmp_meta = $tmp_meta." AND (acid_event.plugin_id=-1 AND acid_event.plugin_sid=-1)";
        $sfilter = true;
    }

    /* Network Group */
    if ($networkgroup != "" && $networkgroup != " ") {
        $ng_ids = QueryOssimNetworkGroup($networkgroup);
        if ($ng_ids!="") {
            $tmp_meta = $tmp_meta . " AND ($ng_ids) ";
            $use_ac = false;
        }
    }

    /* User Data */
    //echo "User Data:$userdata";
    $rpl = array('EQ'=>'=','NE'=>'!=','LT'=>'<','LOE'=>'<=','GT'=>'>','GOE'=>'>=');
    if (trim($userdata[2]) != "")
    {
        $q_like         = ($userdata[1] == 'like') ? TRUE : FALSE;

        $_q             = parenthesis_encode(escape_sql($userdata[2], $db->DB, $q_like));

        $sql            = "SELECT acid_event.*, HEX(acid_event.ctx) AS ctx, HEX(acid_event.src_host) AS src_host,
                                  HEX(acid_event.dst_host) AS dst_host, HEX(acid_event.src_net) AS src_net,
                                  HEX(acid_event.dst_net) AS dst_net,extra_data.*
                           FROM acid_event";
        $data_join_sql .= ",extra_data ";
        $_nq            = (is_numeric($_q)) ? $_q : "'".$_q."'";
        $flt            = "extra_data.".$userdata[0]." ".strtr($userdata[1],$rpl)." ".(($userdata[1]=="like") ? "'%".$_q."%'" : $_nq);
        $tmp_meta      .= " AND acid_event.id=extra_data.event_id AND ($flt)";
        $use_ac         = FALSE;
    }

    /* IDM */
    if (trim($idm_username[0]) != '' || trim($idm_domain[0]) != '')
    {
        $data_join_sql .= ",idm_data ";
        $tmp_meta      .= " AND acid_event.id=idm_data.event_id";
        $use_ac         = FALSE;
    }
    if ($idm_username[0] != '') // username in idm_data
    {
        $_q = parenthesis_encode(escape_sql($idm_username[0], $db->DB));

        if ($idm_username[1] == "both")
        {
            $tmpcrit = "idm_data.username='".$_q."'";
        }
        else
        {
            $tmpcrit = "(idm_data.username='".$_q."' AND idm_data.from_src=".(($idm_username[1]=="src") ? "1" : "0").")";
        }
        $tmp_meta .= " AND $tmpcrit";
    }
    if ($idm_domain[0] != '') // domain in idm_data
    {
        $_q = parenthesis_encode(escape_sql($idm_domain[0], $db->DB));

        if ($idm_domain[1] == "both")
        {
            $tmpcrit = "idm_data.domain='".$_q."'";
        }
        else
        {
            $tmpcrit = "(idm_data.domain='".$_q."' AND idm_data.from_src=".(($idm_domain[1]=="src") ? "1" : "0").")";
        }
        $tmp_meta .= " AND $tmpcrit";
    }
    if ($idm_hostname[0] != '') // hostname in acid_event
    {
        $_q = parenthesis_encode(escape_sql($idm_hostname[0], $db->DB));

            if ($idm_hostname[1] == "both")
        {
            $tmpcrit = "(acid_event.src_hostname='".$_q."' OR acid_event.dst_hostname='".$_q."')";
        }
        else
        {
            $tmpcrit = "acid_event.".$idm_hostname[1]."_hostname='".$_q."'";
        }
        $tmp_meta .= " AND $tmpcrit";
        $use_ac    = FALSE;
    }

    /* OTX */
    $otx_data = (trim($otx[0])!="" || trim($otx[1])!="") ? true : false;
    if ($otx_data)
    {
        $data_join_sql .= ",otx_data";
        $tmp_meta .= " AND acid_event.id=otx_data.event_id";
        $use_ac = false;
    }
    # Pulse id
    if (trim($otx[0])!="")
    {
        $tmp_meta .= " AND otx_data.pulse_id=unhex('".$otx[0]."')";
    }

    /* Reputation */
    $rep_data = (trim($rep[0])!="" || trim($rep[1])!="") ? true : false;
    if ($rep_data)
    {
        $data_join_sql .= ",reputation_data";
        $tmp_meta .= " AND acid_event.id=reputation_data.event_id";
        $use_ac = false;
    }
    # Reputation Activity
    if (intval($rep[0]))
    {
        $aname = GetActivityName(intval($rep[0]), $db);
        $tmp_meta .= " AND (reputation_data.rep_act_src like '%".str_replace("'","\'",$aname)."%' OR reputation_data.rep_act_dst like '%".str_replace("'","\'",$aname)."%')";
    }
    # Reputation Severity
    if (trim($rep[1])!="")
    {
        switch ($rep[1])
        {
            case "High":
                $tmpcrit = "(reputation_data.rep_prio_src>6 OR reputation_data.rep_prio_dst>6)";
                break;

            case "Medium":
                $tmpcrit = "(reputation_data.rep_prio_src in (3,4,5,6) OR reputation_data.rep_prio_dst in (3,4,5,6))";
                break;

            case "Low":
                $tmpcrit = "(reputation_data.rep_prio_src in (0,1,2) OR reputation_data.rep_prio_dst in (0,1,2))";
                break;

            default:
                $tmpcrit = "(reputation_data.rep_prio_src>0 OR reputation_data.rep_prio_dst>0)";
        }
        $tmp_meta .= " AND $tmpcrit";
    }

    /* Source Type */
    if (trim($sourcetype) != "") $tmp_meta = $tmp_meta . " AND acid_event.plugin_id in (" . GetPluginListBySourceType($sourcetype) . ")";

    /* Category */
    if ($category[0] != 0) {
        $sig_join = true;
        $tmp_meta = $tmp_meta . GetPluginListByCategory($category);
    }

    /* Signature */
    if ((isset($sig[0]) && $sig[0] != " " && $sig[0] != "") && (isset($sig[1]) && $sig[1] != "")) {
        if ($sig_type==1) { // sending sig[1]=plugin_id;plugin_sid
            $sfilter = true;
            $pidsid = preg_split("/[\s;]+/",$sig[1]);
            $tmp_meta = $tmp_meta." AND (acid_event.plugin_id=".intval($pidsid[0])." AND acid_event.plugin_sid=".intval($pidsid[1]).")";
        } else { // free string
            //$sig_join_tmp = QueryOssimSignatureTmpTable($sig[1], $sig[0], $sig[2]);
            $sig_ids = QueryOssimSignature($sig[1], $sig[0], $sig[2], $db->DB);
            $sig_join = true;
            $tmp_meta = $tmp_meta . " AND ($sig_ids)";
        }
    } else $cs->criteria['sig']->Set("");

    /*
    * OSSIM Code
    */
    /* OSSIM Type */
    if ($ossim_type[1] != " " && $ossim_type[1] != "" && $ossim_type[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_type = '" . $ossim_type[1] . "'";
        $use_ac = false;
    } else if ($ossim_type[1] == "0") {
        $tmp_meta = $tmp_meta . " AND (acid_event.ossim_type is null OR acid_event.ossim_type = '0')";
        $use_ac = false;
    } else $cs->criteria['ossim_type']->Set("");
    /* OSSIM Priority */
    if ($ossim_priority[1] != " " && $ossim_priority[1] != "" && $ossim_priority[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_priority  " . $ossim_priority[0] . " '" . $ossim_priority[1] . "'";
        $use_ac = false;
    } else if ($ossim_priority[1] == "0") {
        $use_ac = false;
        $tmp_meta = ($ossim_priority[0] == "=") ? $tmp_meta . " AND (acid_event.ossim_priority is null OR acid_event.ossim_priority = '0')" : $tmp_meta = $tmp_meta . " AND acid_event.ossim_priority  " . $ossim_priority[0] . " '" . $ossim_priority[1] . "'";
    } else $cs->criteria['ossim_priority']->Set("");
    /* OSSIM Reliability */
    if ($ossim_reliability[1] != " " && $ossim_reliability[1] != "" && $ossim_reliability[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_reliability " . $ossim_reliability[0] . " '" . $ossim_reliability[1] . "'";
        $use_ac = false;
    } else if ($ossim_reliability[1] == "0") {
        $tmp_meta = ($ossim_reliability[0] == "=") ? $tmp_meta . " AND (acid_event.ossim_reliability is null OR acid_event.ossim_reliability = '0')" : $tmp_meta . " AND acid_event.ossim_reliability " . $ossim_reliability[0] . " '" . $ossim_reliability[1] . "'";
        $use_ac = false;
    } else $cs->criteria['ossim_reliability']->Set("");
    /* OSSIM Asset DST */
    if ($ossim_asset_dst[1] != " " && $ossim_asset_dst[1] != "" && $ossim_asset_dst[1] != "0") {
        $tmp_meta = $tmp_meta . " AND acid_event.ossim_asset_dst " . $ossim_asset_dst[0] . " '" . $ossim_asset_dst[1] . "'";
        $use_ac = false;
    } else if ($ossim_asset_dst[1] == "0") {
        $tmp_meta = ($ossim_asset_dst[0] == "=") ? $tmp_meta . " AND (acid_event.ossim_asset_dst is null OR acid_event.ossim_asset_dst = '0')" : $tmp_meta . " AND acid_event.ossim_asset_dst " . $ossim_asset_dst[0] . " '" . $ossim_asset_dst[1] . "'";
        $use_ac = false;
    } else $cs->criteria['ossim_asset_dst']->Set("");
    /* OSSIM Risk A */
    if ($ossim_risk_a != " " && $ossim_risk_a != "" && $ossim_risk_a != "0") {
        if ($ossim_risk_a == "low") {
            //$tmp_meta = $tmp_meta." AND ossim_risk_a >= 1 AND ossim_risk_a <= 4 ";
            $tmp_meta = $tmp_meta . " AND acid_event.ossim_risk_a = 0 ";
            $use_ac = false;
        } else if ($ossim_risk_a == "medium") {
            //$tmp_meta = $tmp_meta." AND ossim_risk_a >= 5 AND ossim_risk_a <= 7 ";
            $tmp_meta = $tmp_meta . " AND acid_event.ossim_risk_a = 1 ";
            $use_ac = false;
        } else if ($ossim_risk_a == "high") {
            //$tmp_meta = $tmp_meta." AND ossim_risk_a >= 8 AND ossim_risk_a <= 10 ";
            $tmp_meta = $tmp_meta . " AND acid_event.ossim_risk_a > 1 ";
            $use_ac = false;
        }
    } else $cs->criteria['ossim_risk_a']->Set("");
    /* Date/Time */
    $time_meta = "";
    $real_time_meta = "";
    DateTimeRows2sql($real_time, $time_cnt, $real_time_meta); // Time without utc conversion
    if (DateTimeRows2sql($time, $time_cnt, $time_meta) == 0) $cs->criteria['time']->SetFormItemCnt(0);
    $criteria_sql = $criteria_sql . $tmp_meta;

    $criteria_sql_ac .= ($use_ac && !$sig_join) ? preg_replace("/( \d\d):\d\d:\d\d/","\\1:00:00",$tmp_meta) : preg_replace("/( \d\d):\d\d:\d\d/","\\1:00:00",$time_meta);

    $use_ac = (time_can_use_ac($real_time)) ? $use_ac : FALSE;


    /* ********************** PERMS ************************ */
    // Allowed CTX's y Asset Filter
    $perms_sql     = GetPerms();
    $idfilter      = (!empty($perms_sql)) ? true : false;
    $criteria_sql .= $perms_sql;
    $criteria_sql_ac .= $perms_sql;

    /* Host ID */
    $op       = ($hostid[3] != '') ? $hostid[3] : 'IN';
    $and_or   = ($op == 'NOT IN') ? 'AND' : 'OR';
    // src_host, dst_host fields
    if ($hostid[0] != "")
    {
        $hostwhere = "UNHEX('".implode("',UNHEX('",explode(",",$hostid[0]))."')";
        if ($hostid[2] == "both")
        {
            $criteria_sql .= " AND (acid_event.src_host $op ($hostwhere) $and_or acid_event.dst_host $op ($hostwhere))";
            $criteria_sql_ac .= " AND (acid_event.src_host $op ($hostwhere) $and_or acid_event.dst_host $op ($hostwhere))";
        }
        else
        {
            $criteria_sql .= " AND acid_event.".$hostid[2]."_host $op ($hostwhere)";
            $criteria_sql_ac .= " AND acid_event.".$hostid[2]."_host $op ($hostwhere)";
        }
        $idfilter = true;
    }

    /* Network ID */
    // src_net, dst_net fields
    if ($netid[0]!="")
    {
        $netwhere = "UNHEX('".implode("',UNHEX('",explode(",",$netid[0]))."')";
        if ($netid[2]=="both")
        {
            $criteria_sql .= " AND (acid_event.src_net in ($netwhere) OR acid_event.dst_net in ($netwhere))";
            $criteria_sql_ac .= " AND (acid_event.src_net in ($netwhere) OR acid_event.dst_net in ($netwhere))";
        }
        else
        {
            $criteria_sql .= " AND acid_event.".$netid[2]."_host in ($netwhere)";
            $criteria_sql_ac .= " AND acid_event.".$netid[2]."_host in ($netwhere)";
        }
        $idfilter = true;
    }

    /* ********************** IP Criteria ********************************************** */
    /* IP Addresses */
    $ipfilter = false;
    $tmp2 = "";
    for ($i = 0; $i < $ip_addr_cnt; $i++) {
        $tmp = "";
        if (isset($ip_addr[$i][3]) && $ip_addr[$i][1] != " " && $ip_addr[$i][1] != "") {
            if (($ip_addr[$i][3] != "") && ($ip_addr[$i][4] != "") && ($ip_addr[$i][5] != "") && ($ip_addr[$i][6] != "")) {
                /* if use illegal 256.256.256.256 address then
                *  this is the special case where need to search for portscans
                */
                if (($ip_addr[$i][3] == "256") && ($ip_addr[$i][4] == "256") && ($ip_addr[$i][5] == "256") && ($ip_addr[$i][6] == "256")) {
                    $tmp = $tmp . " acid_event." . $ip_addr[$i][1] . " IS NULL" . " ";
                } else {
                    if ($ip_addr[$i][10] == "") {
                        $tmp = $tmp . " acid_event." . $ip_addr[$i][1] . $ip_addr[$i][2] . "unhex('" . baseIP2hex($ip_addr[$i][3] . "." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6]) . "') ";
                    } else {
                        $mask = getIPMask($ip_addr[$i][3] . "." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6], $ip_addr[$i][10]);
                        if ($ip_addr[$i][2] == "!=") $tmp_op = " NOT ";
                        else $tmp_op = "";
                        $tmp = $tmp . $tmp_op . " acid_event." . $ip_addr[$i][1] . ">= unhex('" . baseIP2hex($mask[0]) . "') AND acid_event." . $ip_addr[$i][1] . "<= unhex('" . baseIP2hex($mask[1]) . "')";
                    }
                }
            }
            /* if have chosen the address type to be both source and destination */
            if (preg_match("/ip_both/", $tmp)) {
                $tmp_src = preg_replace("/ip_both/", "ip_src", $tmp);
                $tmp_dst = preg_replace("/ip_both/", "ip_dst", $tmp);
                if ($ip_addr[$i][2] == '=') $tmp = "(" . $tmp_src . ') OR (' . $tmp_dst . ')';
                else $tmp = "(" . $tmp_src . ') AND (' . $tmp_dst . ')';
            }
            $aux_op = ($ip_addr_cnt > 0) ? (($ip_addr[$i][9] == "AND" || $ip_addr[$i][9] == "OR") ? $ip_addr[$i][9] : "AND") : "";
            if ($tmp != "") $tmp = $ip_addr[$i][0] . "(" . $tmp . ")" . $ip_addr[$i][8] . $aux_op;
        } else if ((isset($ip_addr[$i][3]) && $ip_addr[$i][3] != "") || ($ip_addr[$i][1] != " " && $ip_addr[$i][1] != "")) {
            /* IP_addr_type, but MALFORMED IP address */
            if ($ip_addr[$i][1] != " " && $ip_addr[$i][1] != "" && $ip_addr[$i][3] == "" && ($ip_addr[$i][4] != "" || $ip_addr[$i][5] != "" || $ip_addr[$i][6] != "")) ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("Invalid IP address criteria") . " ' *." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6] . " '");
            /* ADDRESS, but NO IP_addr_type was given */
            if (isset($ip_addr[$i][3]) && $ip_addr[$i][1] == " " && $ip_addr[$i][1] == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("A IP address of") . " '" . $ip_addr[$i][3] . "." . $ip_addr[$i][4] . "." . $ip_addr[$i][5] . "." . $ip_addr[$i][6] . "' " . gettext("was entered for as a criteria value, but the type of address (e.g. source, destination) was not specified."));
            /* IP_addr_type IS FILLED, but no ADDRESS */
            if (($ip_addr[$i][1] != " " && $ip_addr[$i][1] != "" && $ip_addr[$i][1] != "") && $ip_addr[$i][3] == "") ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("An IP address of type") . " '" . $ip_addr[$i][1] . "' " . gettext("was selected (at #") . $i . ") " . gettext("indicating that an IP address should be a criteria, but no address on which to match was specified."));
        }
        $tmp2 = $tmp2 . $tmp;
        if (($i > 0 && ($ip_addr[$i - 1][9] != 'OR' && $ip_addr[$i - 1][9] != 'AND') && $ip_addr[$i - 1][3] != "")) ErrorMessage("<B>" . gettext("Criteria warning:") . "</B> " . gettext("Multiple IP address criteria entered without a boolean operator (e.g. AND, OR) between IP Criteria") . " #$i and #" . ($i + 1) . ".");
    }
    if ($tmp2 != "") {
        BalanceBrackets($tmp2);
        $criteria_sql = $criteria_sql . " AND ( " . $tmp2 . " )";
        $ipfilter = true;
        //$use_ac = false;
    }
    else {
        $cs->criteria['ip_addr']->SetFormItemCnt(0);
    }
    /* IP Fields */
    if (FieldRows2sql($ip_field, $ip_field_cnt, $criteria_sql) == 0) $cs->criteria['ip_field']->SetFormItemCnt(0);
    else $use_ac = false;

    /* CTX */
    if ($ctx != "") {
        $criteria_sql .= " AND acid_event.ctx = UNHEX('$ctx')";
        $criteria_sql_ac .= " AND acid_event.ctx = UNHEX('$ctx')";
    }

    /* Layer-4 encapsulation */
    if ($layer4 == "TCP") {
        $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '6'";
        $use_ac = false;
    } else if ($layer4 == "UDP") {
        $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '17'";
        $use_ac = false;
    } else if ($layer4 == "ICMP") {
        $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '1'";
        $use_ac = false;
    } else if ($layer4 == "RawIP") {
        $criteria_sql = $criteria_sql . " AND acid_event.ip_proto= '255'";
        $use_ac = false;
    } else $cs->criteria['layer4']->Set("");
    /* Join the iphdr table if necessary */
    if (!$cs->criteria['ip_field']->isEmpty()) $join_sql = $ip_join_sql . $join_sql;
    /* ********************** TCP Criteria ********************************************** */
    if ($layer4 == "TCP") {
        $proto_tmp = "";
        /* TCP Ports */
        if (FieldRows2sql($tcp_port, $tcp_port_cnt, $proto_tmp) == 0) $cs->criteria['tcp_port']->SetFormItemCnt(0);
        $criteria_sql = $criteria_sql . $proto_tmp;
        $proto_tmp = "";

        // ****************** DEPRECATED: TCP Flags TCP Fields ********************
        /* TCP Flags */
        /*
        if (isset($tcp_flags) && sizeof($tcp_flags) == 8) {
            if ($tcp_flags[0] == "contains" || $tcp_flags[0] == "is") {
                $flag_tmp = $tcp_flags[1] + $tcp_flags[2] + $tcp_flags[3] + $tcp_flags[4] + $tcp_flags[5] + $tcp_flags[6] + $tcp_flags[7] + $tcp_flags[8];
                if ($tcp_flags[0] == "is") $proto_tmp = $proto_tmp . ' AND tcp_flags=' . $flag_tmp;
                else if ($tcp_flags[0] == "contains") $proto_tmp = $proto_tmp . ' AND (tcp_flags & ' . $flag_tmp . ' = ' . $flag_tmp . " )";
                else $proto_tmp = "";
            }
        }
        */
        /* TCP Fields */
        //if (FieldRows2sql($tcp_field, $tcp_field_cnt, $proto_tmp) == 0) $cs->criteria['tcp_field']->SetFormItemCnt(0);

        /* TCP Options
        *  - not implemented
        */
        //if (!$cs->criteria['tcp_port']->isEmpty() || !$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) {
        //************************************************************************

        if (!$cs->criteria['tcp_port']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;

            // DEPRECATED tcp_join_sql
            //if (!$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) $join_sql = $tcp_join_sql . $join_sql;

        }
    }
    /* ********************** UDP Criteria ********************************************* */
    if ($layer4 == "UDP") {
        $proto_tmp = "";
        /* UDP Ports */
        if (FieldRows2sql($udp_port, $udp_port_cnt, $proto_tmp) == 0) $cs->criteria['udp_port']->SetFormItemCnt(0);
        $criteria_sql = $criteria_sql . $proto_tmp;
        $proto_tmp = "";

        // ********************** DEPRECATED UDP Fields *************************
        /* UDP Fields */
        //if (FieldRows2sql($udp_field, $udp_field_cnt, $proto_tmp) == 0) $cs->criteria['udp_field']->SetFormItemCnt(0);
        //if (!$cs->criteria['udp_port']->isEmpty() || !$cs->criteria['udp_field']->isEmpty()) {
        // **********************************************************************

        if (!$cs->criteria['udp_port']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;

            // DEPRECATED udp_join_sql
            //if (!$cs->criteria['udp_field']->isEmpty()) $join_sql = $udp_join_sql . $join_sql;

        }
    }
    // DEPRECATED: ICMP
    /* ********************** ICMP Criteria ******************************************** */
    /*
    if ($layer4 == "ICMP") {
        $proto_tmp = "";
        // ICMP Fields
        if (FieldRows2sql($icmp_field, $icmp_field_cnt, $proto_tmp) == 0) $cs->criteria['icmp_field']->SetFormItemCnt(0);
        if (!$cs->criteria['icmp_field']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;
            $join_sql = $icmp_join_sql . $join_sql;
        }
    }
    */

    /* ********************** Packet Scan Criteria ************************************* */
    if ($layer4 == "RawIP") {
        $proto_tmp = "";
        /* RawIP Fields */
        if (FieldRows2sql($rawip_field, $rawip_field_cnt, $proto_tmp) == 0) $cs->criteria['rawip_field']->SetFormItemCnt(0);
        if (!$cs->criteria['rawip_field']->isEmpty()) {
            $criteria_sql = $criteria_sql . $proto_tmp;
            $join_sql = $rawip_join_sql . $join_sql;
        }
    }
    /* ********************** Payload Criteria ***************************************** */
    //$tmp_payload = "";
    if (DataRows2sql($data, $data_cnt, $data_encode, $tmp_payload, $db->DB) == 0) $cs->criteria['data']->SetFormItemCnt(0);
    else $use_ac = false;
    //echo "<br><br><br>";
    //print_r($data);
    //print_r("data_cnt: [".$data_cnt."]");
    //print_r($cs->criteria['data']->isEmpty());
    //print_r("criteria_ sql: [".$criteria_sql."]");
    //print_r("tmp_payload: [".$tmp_payload."]");
    //print_r($data);
    if (!$cs->criteria['data']->isEmpty()) {
        $sql = "SELECT acid_event.*, HEX(acid_event.ctx) AS ctx, HEX(acid_event.src_host) AS src_host, HEX(acid_event.dst_host) AS dst_host, HEX(acid_event.src_net) AS src_net, HEX(acid_event.dst_net) AS dst_net, extra_data.* FROM acid_event";
        if (!preg_match("/extra_data/",$data_join_sql)) $data_join_sql .= ",extra_data ";
        $criteria_sql = $criteria_sql . $tmp_payload;
        $use_ac = false;
    }

    if ($sig_join) $join_sql = $join_sql . $sig_join_sql;
    $join_sql = $join_sql . $data_join_sql;
    $csql[0] = $join_sql;

    // special distinct for idm_username
    if ($otx_data || preg_match("/idm_data/",$join_sql))
    {
        $sql = preg_replace("/^SELECT/","SELECT DISTINCT",$sql);
    }

    // Ready to ac_acid_event
    //$criteria1_sql = $criteria_sql . preg_replace("/ \d\d:\d\d:\d\d/","",str_replace("timestamp","day",$real_time_meta));
    $criteria1_sql = $criteria_sql . $real_time_meta;
    $criteria1_sql = preg_replace("/AND\s+\)/"," )",preg_replace("/OR\s+\)/"," )",$criteria1_sql));

    // Ready to ac_acid_event next day
    //$criteria2_sql = $criteria_sql . preg_replace("/ \d\d:\d\d:\d\d/","",str_replace("timestamp","day",$time_meta));
    $criteria2_sql = $criteria_sql . $time_meta;
    $criteria2_sql = preg_replace("/AND\s+\)/"," )",preg_replace("/OR\s+\)/"," )",$criteria2_sql));

    // to acid_event
    $criteria_sql = $criteria_sql . $time_meta;
    $criteria_sql = preg_replace("/AND\s+\)/"," )",preg_replace("/OR\s+\)/"," )",$criteria_sql));

    $csql[1] = $criteria_sql;
    //$csql[2] = $perms_sql . preg_replace("/ \d\d:\d\d:\d\d/","",str_replace("timestamp","day",$time_meta)); // $real_time_criteria
    $csql[2] = $perms_sql . $time_meta;
    $csql[3] = $use_ac; // true if we use ac_acid_event instead acid_event
    $csql[4] = $criteria1_sql;
    $csql[5] = $criteria2_sql;
    $csql[6] = $sfilter;
    $csql[7] = $ipfilter;
    $csql[8] = $idfilter;
    $csql[9] = $criteria_sql_ac;

    //print_r($csql);
    return $csql;
}
