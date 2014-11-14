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
**/

/**
* Function list:
* - QueryState()
* - AddCannedQuery()
* - PrintCannedQueryList()
* - isCannedQuery()
* - GetCurrentCannedQuery()
* - GetCurrentCannedQueryCnt()
* - GetCurrentCannedQueryDesc()
* - GetCurrentCannedQuerySort()
* - isValidCannedQuery()
* - GetCurrentView()
* - GetCurrentSort()
* - GetDisplayRowCnt()
* - AddValidAction()
* - AddValidActionOp()
* - SetActionSQL()
* - RunAction()
* - GetNumResultRows()
* - GetCalcFoundRows()
* - MoveView()
* - ExecuteOutputQuery()
* - ExecuteOutputQueryNoCanned()
* - PrintResultCnt()
* - PrintBrowseButtons()
* - PrintAlertActionButtons()
* - ReadState()
* - SaveState()
* - SaveStateGET()
* - DumpState()
*
* Classes list:
* - QueryState
*/


defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/includes/base_db.inc.php");
require_once ('classes/Util.inc');
include_once ("$BASE_path/includes/base_constants.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");
// include_once("$BASE_path/includes/base_capabilities.php"); //Commented out by Kevin for testing

require_once 'classes/Util.inc';

class QueryState {
    var $canned_query_list = NULL;
    var $num_result_rows = - 1;
    var $num_query_rows = 0;
    var $current_canned_query = "";
    var $current_sort_order = "";
    var $current_view = - 1;
    var $show_rows_on_screen = - 1;
    var $num_acid_event_rows = 0;
    var $valid_action_list = NULL;
    var $action;
    var $valid_action_op_list = NULL;
    var $action_arg;
    var $action_lst;
    var $action_chk_lst = NULL;
    var $action_sql;
    function QueryState() {
        $this->ReadState();
        if ($this->num_result_rows == "") $this->num_result_rows = - 1;
        if ($this->current_view == "") $this->current_view = - 1;
    }
    function AddCannedQuery($caller, $caller_num, $caller_desc, $caller_sort) {
        $this->canned_query_list[$caller] = array(
            $caller_num,
            $caller_desc,
            $caller_sort
        );
    }
    function PrintCannedQueryList() {
        echo "<BR><B>" . gettext("Valid Canned Query List") . "</B>\n<PRE>\n";
        print_r($this->canned_query_list);
        echo "</PRE>\n";
    }
    function isCannedQuery() {
        return ($this->current_canned_query != "");
    }
    /* returns the name of the current canned query (e.g. "last_tcp") */
    function GetCurrentCannedQuery() {
        return $this->current_canned_query;
    }
    function GetCurrentCannedQueryCnt() {
        return $this->canned_query_list[$this->current_canned_query][0];
    }
    function GetCurrentCannedQueryDesc() {
        return $this->canned_query_list[$this->current_canned_query][0] . " " . $this->canned_query_list[$this->current_canned_query][1];
    }
    function GetCurrentCannedQuerySort() {
        if ($this->isCannedQuery()) return $this->canned_query_list[$this->current_canned_query][2];
        else return "";
    }
    function isValidCannedQuery($potential_caller) {
        if ($this->canned_query_list == NULL) return false;
        return in_array($potential_caller, array_keys($this->canned_query_list));
    }
    function GetCurrentView() {
        return $this->current_view;
    }
    function GetCurrentSort() {
        return $this->current_sort_order;
    }
    /* returns the number of rows to display for a single screen of the
    * query results
    */
    function GetDisplayRowCnt() {
        return $this->show_rows_on_screen;
    }
    function AddValidAction($action) {
        if (($action == "archive_alert" || $action == "archive_alert2") && isset($_COOKIE['archive']) && $_COOKIE['archive'] == 1) {
            // We do nothing here because we are looking at the archive tables
            // We do not want to add the archive actions to this list -- Kevin
            
        } else {
            $this->valid_action_list[count($this->valid_action_list) ] = $action;
        }
    }
    function AddValidActionOp($action_op) {
        if (!(!Session::menu_perms("analysis-menu","EventsForensicsDelete") and (($action_op==gettext("Delete Selected")) or ($action_op==gettext("Delete ALL on Screen")) or ($action_op==gettext("Delete Entire Query")))))
            $this->valid_action_op_list[count($this->valid_action_op_list) ] = $action_op;
    }
    function SetActionSQL($sql) {
        $this->action_sql = $sql;
    }
    function RunAction($submit, $which_page, $db) {
        GLOBAL $show_rows;
        require_once("av_init.php");
        if ($this->action != "del_alert") {
        	ActOnSelectedAlerts($this->action, $this->valid_action_list, $submit, $this->valid_action_op_list, $this->action_arg, $which_page, $this->action_chk_lst, $this->action_lst, $show_rows, $this->num_result_rows, $this->action_sql, $this->current_canned_query,$db);
        } else {
        	 if (!Session::menu_perms("analysis-menu","EventsForensicsDelete") and (($submit==gettext("Delete Selected")) or ($submit==gettext("Delete ALL on Screen")) or ($submit==gettext("Delete Entire Query"))))
        	 	echo "<span style='color:red'>"._("You don't have required permissions to delete events.")."</span>";
        	 else
        	 	ActOnSelectedAlerts($this->action, $this->valid_action_list, $submit, $this->valid_action_op_list, $this->action_arg, $which_page, $this->action_chk_lst, $this->action_lst, $show_rows, $this->num_result_rows, $this->action_sql, $this->current_canned_query,$db);
        }
    }
    function GetNumResultRows($cnt_sql = "", $db = NULL) {
        if (!($this->isCannedQuery()) && ($this->num_result_rows == - 1)) {
            $this->current_view = 0;
            $result = $db->baseExecute($cnt_sql);
            if ($result) {
                $rows = $result->baseFetchRow();
                $this->num_result_rows = $rows[0];
                $result->baseFreeRows();
            } else {
                $this->num_result_rows = 0;
            }
        } else {
            if ($this->isValidCannedQuery($this->current_canned_query)) {
                reset($this->canned_query_list);
                while ($tmp_canned = each($this->canned_query_list)) {
                    if ($this->current_canned_query == $tmp_canned["key"]) {
                        $this->current_view = 0;
                        $this->num_result_rows = $tmp_canned["value"][0];
                    }
                }
            }
        }
    }
    // Optimization Update: faster than GetNumResultRows()
    function GetCalcRows($where = "", $count = 0, $db = NULL, $sql = "") {
    	$this->num_result_rows = 0;
    	$this->num_query_rows = $count;
    	if ($this->num_query_rows>0) {
    	    $sql = ($sql=="") ? "SELECT sum(cnt) FROM ac_acid_event as acid_event WHERE 1=1 $where" : $sql;
	        $result = $db->baseExecute($sql);
	        if ($result) {
	            $rows = $result->baseFetchRow();
	            $result->baseFreeRows();
	            $this->num_result_rows = $rows[0];
	        }
	    }
        return $this->num_result_rows;
    }    
    // Optimization Update: faster than GetNumResultRows()
    function GetCalcFoundRows($cnt_sql = "", $count = 0, $db = NULL) {
        $this->num_query_rows  = $count;
        $this->num_result_rows = 0;
        if ($count) {
            // Only if needed
            $this->num_query_rows = $count;
            $result = $db->baseExecute("SELECT FOUND_ROWS()");
            if ($result) {
                $rows = $result->baseFetchRow();
                $this->num_result_rows = $rows[0];
            } elseif ($cnt_sql!="") { // if not execute $cnt_sql
                $result = $db->baseExecute($cnt_sql);
                if ($result) {
                    $rows = $result->baseFetchRow();
                    $this->num_result_rows = $rows[0];
                    $result->baseFreeRows();
                }
            }
            
        }
        return $this->num_result_rows;
    }
    function MoveView($submit) {
        if (is_numeric($submit)) $this->current_view = $submit;
    }
    function ExecuteOutputQuery($sql, $db) {
        GLOBAL $show_rows;
        if ($this->isCannedQuery()) {
			$this->show_rows_on_screen = $this->GetCurrentCannedQueryCnt();
            return $db->baseExecute($sql, 0, $this->show_rows_on_screen);
        } else {
			// Pagination updated (03/02/2009 - Granada)
            $this->show_rows_on_screen = $show_rows;
            $this->current_view = ($_POST['submit'] != 'Query DB') ? intval($_POST['submit']) : 0;
			//echo "Current view: ".$this->current_view;
            return $db->baseExecute($sql, ($this->current_view * $show_rows) , ($show_rows+1));
        }
    }
    function ExecuteOutputQueryNoCanned($sql, $db) {
        return $db->baseExecute($sql);
		//print_r($sql);
    }
    function PrintResultCnt($sqlgraph = "", $tr = array(), $displaying="") {
        GLOBAL $show_rows, $db;
        echo "<table class='container' style='height:30px'><tr><td>";
        if($displaying=="") {
            $displaying = gettext("Displaying events %d-%d of <b>%s</b> matching your selection.");
        }
        if ($this->num_result_rows > 0) {
            if ($this->isCannedQuery()) {
                echo "<div class='siem_display_msg' style='text-align:left;margin:auto;padding:3px 0px'>" . gettext("Displaying") . " " . Util::htmlentities($this->GetCurrentCannedQueryDesc()) . "</div>";
            } else {
                if (Session::am_i_admin()) {
                    // Total rows
                    $rt = $db->baseExecute("SELECT sum(cnt) from ac_acid_event"); // from ac_ table => faster way
                    if ($rt) {
                        $rows = $rt->baseFetchRow();
                        $this->num_acid_event_rows = $rows[0];
                    }
                    $rt->baseFreeRows();
                }
                printf ( "<div class='siem_display_msg' style='float:left;margin:auto;padding:4px 0px'>". $displaying . "</div>\n", ($this->current_view * $show_rows) + 1, (($this->current_view * $show_rows) + $show_rows - 1) < $this->num_result_rows ? (($this->current_view * $show_rows) + $show_rows) : $this->num_result_rows, Util::number_format_locale($this->num_result_rows,0) );
                
                if (Session::am_i_admin()) printf ( "<div class='siem_display_msg' style='float:right;margin:auto'>". gettext(" <b>%s</b> total events in database.") . "</div>\n", Util::number_format_locale($this->num_acid_event_rows,0) );
                           
                //printf("<div style='text-align:left;margin:auto'><table><tr><td><img src='../pixmaps/arrow_green.gif'></td><td>". $displaying . "</td>\n", ($this->current_view * $show_rows) + 1, (($this->current_view * $show_rows) + $show_rows - 1) < $this->num_result_rows ? (($this->current_view * $show_rows) + $show_rows) : $this->num_result_rows, Util::number_format_locale($this->num_result_rows,0), Util::number_format_locale($this->num_acid_event_rows,0));
                if ($sqlgraph != "") {
                    GLOBAL $db, $graph_report_type;
                    list($x, $y, $xticks, $xlabels) = range_graphic($tr);
                    //echo "SQLG:$sqlgraph -->";
                    $res = $this->ExecuteOutputQueryNoCanned($sqlgraph, $db);
                    //echo " COUNT:".$res->baseRecordCount()."<br>";
                    while ($rowgr = $res->baseFetchRow()) {
						//print_r($rowgr);
						$label = trim($rowgr[1] . " " . $rowgr[2]);
                        if (isset($y[$label]) && $y[$label] == 0) $y[$label] = $rowgr[0];
                        //echo "$label = $rowgr[0] <br>";
                    }
                    // Report data
                    $gdata = array();
                    foreach ($y as $label => $val) {
                        $gdata[] = array ($label,"","","","","","","","","","",$val,0,0);
                    }
                    $this->SaveReportData($gdata,$graph_report_type);
                    //print_r($xlabels);
                    //print_r($xticks);
                    //print_r ($x);
                    //print_r ($y);
                    $plot = plot_graphic("plotareaglobal", 50, 400, $x, $y, $xticks, $xlabels, true);
                    //echo "PLOT:".Util::htmlentities($plot).".";
                    echo "<td class=axis>$plot</td>";
                }
                echo "</tr></table></div>\n";
            }
        } else printf("<B>" . _("No events matching your search criteria have been found. Try fewer conditions.") . "</B>&nbsp;<a style='color:white' href='base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d'>[..." . _("Clear All Criteria") . "...]</a>\n");
        //printf("<P style='color:white;font-size:10px'><B>" . _("No events matching your search criteria have been found. Try fewer conditions.") . "</B>&nbsp;<a href='base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d'>[..." . _("Clear All Criteria") . "...]</a><P>\n");
        echo "</td></tr></table>";
    }
    function EstimateNumber($n,$count,$show,$rows) {
        if ($count<=$show) return $rows;
	    if ($n>1999999) return _("millons");
	    elseif ($n>999999) return _("a millon");
	    elseif ($n>199999) return _("hundred thousands");
	    elseif ($n>99999) return _("a hundred thousand");
	    elseif ($n>1999) return _("thousands");
	    elseif ($n>999) return _("a thousand");
	    elseif ($n>199) return _("hundreds");
	    elseif ($n>99) return _("a hundred");
	    elseif ($n>99) return _("a hundred");
	    elseif ($n<=$show) return $n;
	    else return _("a few");
    }
    function PrintEstimatedResultCnt($displaying="") {
        GLOBAL $show_rows, $db;
        echo "<table class='container' style='height:30px'><tr><td>";
        if($displaying=="") {
            $displaying = gettext("Displaying events %d-%d of about <b>%s</b> matching your selection.");
        }
        if ($this->num_result_rows != 0) {
            if ($this->isCannedQuery()) {
                echo "<div class='siem_display_msg' style='text-align:left;margin:auto;padding:3px 0px'>" . gettext("Displaying") . " " . $this->GetCurrentCannedQueryDesc() . "</div>";
            } else {
                // Total rows
                if (Session::am_i_admin()) {
                    $rt = $db->baseExecute("SELECT sum(cnt) from ac_acid_event"); // from ac_ table => faster way
                    if ($rt) {
                        $rows = $rt->baseFetchRow();
                        $this->num_acid_event_rows = $rows[0];
                    }
                    $rt->baseFreeRows();
                }
                $from = ($this->current_view * $show_rows) + 1;
                $to = (($this->current_view * $show_rows) + $show_rows - 1) < $this->num_result_rows ? (($this->current_view * $show_rows) + $show_rows) : $this->num_result_rows;          
                $rows = ($this->num_query_rows <= $show_rows && $this->current_view==0) ? $this->num_query_rows : ( ($to>($from+$this->num_query_rows)) ? $from+$this->num_query_rows-1 : $to );
                printf ( "<div class='siem_display_msg' style='text-align:left;float:left;margin:auto;padding:4px 0px'>". $displaying . "</div>\n", $from, $rows, $this->EstimateNumber($this->num_result_rows, $this->num_query_rows, $show_rows, $rows) );
                
                if (Session::am_i_admin()) {
                	$pinfo = "PG:".$this->current_view.",RR:".$this->num_result_rows.",QR:".$this->num_query_rows.",SR:".$show_rows;
                	printf ( "<div class='siem_display_msg' style='float:right;margin:auto;padding:4px 0px'>".gettext(" <b>%s</b> total events in database.") . "</div>\n", Util::number_format_locale($this->num_acid_event_rows,0) );
                	}
            }
        } else printf("<B>" . _("No events matching your search criteria have been found. Try fewer conditions.") . "</B>&nbsp;<a style='color:white' href='base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d'>[..." . _("Clear All Criteria") . "...]</a>\n");
        echo "</td></tr></table>";
    }
    function SaveReportData($data,$type=0) {
        GLOBAL $db;
        $this->ExecuteOutputQueryNoCanned("DELETE FROM datawarehouse.report_data WHERE id_report_data_type=$type and user='".$_SESSION["_user"]."'", $db);
        foreach ($data as $arr) {
            $more = "";
            foreach ($arr as $val) $more .= ",'".str_replace("'","\'",str_replace('\\','\\\\',$val))."'";
            $cell_data = count($arr)>14 ? ",cell_data" : ""; // For custom view export, we need all columns
            $sql = "INSERT INTO datawarehouse.report_data (id_report_data_type,user,dataV1,dataV2,dataV3,dataV4,dataV5,dataV6,dataV7,dataV8,dataV9,dataV10,dataV11,dataI1,dataI2,dataI3$cell_data) VALUES ($type,'".$_SESSION["_user"]."'".$more.")";
            //echo $sql."<br>";
            $this->ExecuteOutputQueryNoCanned($sql, $db);
        }
    }
    function PrintBrowseButtons() {
        GLOBAL $show_rows, $max_scroll_buttons;
        /* Don't print browsing buttons for canned query */
        if ($this->isCannedQuery()) return;
        /*if (($this->num_result_rows > 0) && ($this->num_result_rows > $show_rows)) {
            echo "<!-- Query Result Browsing Buttons -->\n" . "<P><CENTER>\n" . "<TABLE cellpadding=6 cellspacing=0 BORDER=0 style='border:1px solid #CACACA'>\n" . "   <TR><TD ALIGN=CENTER style='background:url(\"../pixmaps/fondo_hdr2.png\") repeat-x;font-size:12px;font-weight:bold;padding-bottom:10px'>" . gettext("Query Results") . "<BR><br>&nbsp\n";
            $tmp_num_views = ($this->num_result_rows / $show_rows);
            $tmp_top = $tmp_bottom = $max_scroll_buttons / 2;
            if (($this->current_view - ($max_scroll_buttons / 2)) >= 0) $tmp_bottom = $this->current_view - $max_scroll_buttons / 2;
            else $tmp_bottom = 0;
            if (($this->current_view + ($max_scroll_buttons / 2)) <= $tmp_num_views) $tmp_top = $this->current_view + $max_scroll_buttons / 2;
            else $tmp_top = $tmp_num_views;
            // Show a '<<' symbol of have scrolled beyond the 0 view 
            if ($tmp_bottom != 0) echo ' << ';
            for ($i = $tmp_bottom; $i < $tmp_top; $i++) {
                if ($i != $this->current_view) echo '<INPUT TYPE="submit" class="button" NAME="submit" id="pag'.$i.'" VALUE="' . $i . '">' . "\n";
                else echo '<INPUT TYPE="submit" name="submit" style="display:none" id="pag' . $i . '" value="' . $i . '">[' . $i . '] ' . "\n";
            }
            echo '<script> function pag_reload(){ $("#pag'.$this->current_view.'").click(); } </script>';
            echo "\n";
            // Show a '>>' symbol if last view is not visible
            if (($tmp_top) < $tmp_num_views) echo ' >> ';
            echo "  </TD></TR>\n</TABLE>\n</CENTER>\n\n";
        }*/
        if ($this->num_query_rows > 0) {
            echo "<!-- Query Result Browsing Buttons -->\n<TABLE class='transparent' cellpadding=0 cellspacing=4 BORDER=0 align=right><TR>\n";

            if ($this->current_view > 0) { // Previous button
                $i = $this->current_view - 1;
                echo '<TD><INPUT TYPE="submit" name="submit" style="display:none" id="pag' . $i . '" value="' . $i . '">
                      <a href="" onclick="$(\'#pag' . $i . '\').click()">&lt; ' . _("PREVIOUS") . '</a>' . "\n</TD>";
            } else {
                echo '<TD><a href="" class="link_paginate_disabled" onclick="return false">&lt; ' . _("PREVIOUS") . '</a>' . "\n</TD>";
            }
            echo '<TD width="10px"><INPUT TYPE="submit" name="submit" style="display:none" id="pag' . intval($this->current_view) . '" value="' . intval($this->current_view) . '"></TD>';
        	if ($this->num_query_rows > $show_rows) { // Next
         	    $i = $this->current_view + 1;
                echo '<TD><INPUT TYPE="submit" name="submit" style="display:none" id="pag' . $i . '" value="' . $i . '">
                      <a href="" onclick="$(\'#pag' . $i . '\').click();return false">' . _("NEXT") . ' &gt;</a>' . "\n</TD>";        	   
        	} else {
                echo '<TD><a href="" class="link_paginate_disabled" onclick="return false">' . _("NEXT") . ' &gt;</a>' . "\n</TD>";            	
        	}
            echo '<script> function pag_reload(){ $("#pag'.intval($this->current_view).'").click(); } </script>';
            echo "\n</TR></TABLE>\n";
        }     
        else {
            echo '<script> function pag_reload(){ document.location.reload() } </script>';
        }
    }
    function PrintAlertActionButtons() {
		GLOBAL $BASE_urlpath;
		$conf = $GLOBALS["CONF"];
		$server_logger_if_priority = $conf->get_conf("server_logger_if_priority", FALSE);
		$backup_events = $conf->get_conf("backup_events", FALSE);
		$backup_day = $conf->get_conf("backup_day", FALSE);
        if ($this->valid_action_list == NULL || $this->valid_action_op_list == NULL || $this->num_result_rows <= 0) return;
        echo "\n\n<!-- Alert Action Buttons -->\n" . "<br>\n" . " <TABLE class='transparent' BORDER=0 cellpadding=6 cellspacing=0>\n" . "  <TR>\n" . "   <TD ALIGN=CENTER class='box' style='padding-bottom:10px;padding-top:10px;'>";
        //echo  gettext("ACTION") . "<BR><br>\n<SELECT NAME=\"action\">\n" . '      <OPTION VALUE=" "         ' . chk_select($this->action, " ") . '>' . gettext("{ action }") . "\n";
        echo "<SELECT style='display:none' NAME=\"action\">";
        reset($this->valid_action_list);
        while ($current_action = each($this->valid_action_list)) {
            echo '    <OPTION VALUE="' . $current_action["value"] . '" ' . chk_select($this->action, $current_action["value"]) . '>' . GetActionDesc($current_action["value"]) . "\n";
        }
        echo "    </SELECT>\n" ;
        if ($this->action_arg!="") echo "    <INPUT TYPE=\"text\" NAME=\"action_arg\" VALUE=\"" . $this->action_arg . "\">\n";
        reset($this->valid_action_op_list);
        $bt = 1;
        while ($current_op = each($this->valid_action_op_list)) {
            if ($current_op["value"] == gettext("Insert into DS Group")) { // Exceptional case: execute a javascript function, do not submit
            	echo "    <INPUT TYPE=\"button\" class=\"action_button av_b_secondary\" onclick=\"dsgroup_for_selected()\" VALUE=\"" . $current_op["value"] . "\">\n";
            } elseif ($current_op["value"] == gettext("Delete ALL on Screen")) {
        		echo "    <input type=\"submit\" style=\"display:none\" id=\"eqbtn".$bt."\" NAME=\"submit\" VALUE=\"" . $current_op["value"] . "\"/><INPUT TYPE=\"button\" class=\"action_button av_b_secondary\" onclick=\"if (confirm('". Util::js_entities(_("Are you sure?"))."')) click_all('".$bt."')\" VALUE=\"" . $current_op["value"] . "\">\n";
			} else {
        		echo "    <input type=\"submit\" style=\"display:none\" id=\"eqbtn".$bt."\" NAME=\"submit\" VALUE=\"" . $current_op["value"] . "\"/><INPUT TYPE=\"button\" class=\"action_button av_b_secondary\" onclick=\"if (confirm('". Util::js_entities(_("Are you sure?"))."')) $('#eqbtn".$bt."').click()\" VALUE=\"" . $current_op["value"] . "\">\n";
            }
            //echo "    <input type=\"submit\" class=\"button\" NAME=\"submit\" VALUE=\"" . $current_op["value"] . "\"/>\n";
            $bt++;
        }
        //echo "   </TD>\n" . "  </TR>\n" . " </TABLE>\n" . "</CENTER>\n\n";
		echo "   </TD><TD WIDTH=5>&nbsp;</TD>";
        
        require_once 'av_init.php';
        
        echo "<TD WIDTH=5>&nbsp;</TD>\n" ;

        echo '<td align="right" class="box" style="font-size:11px;padding-bottom:10px;vertical-align:bottom">
                <table class="transparent" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="font-size:11px;color:gray;line-height:13px" align="right">'.
                            _("Priority threshold").': &nbsp; <a href="#" style="text-decoration:none" class="scriptinf" txt="'._("Logs with prioty lower than threshold will be archived but not processed as security events as they are no considered to provide security information").'" onclick="GB_show(\'Configuration\',\'/'.Menu::get_menu_url('/conf/index.php?section=metrics', 'configuration', 'administration', 'main').'\' ,480,\'80%\');return false">'.$server_logger_if_priority.'</a><br/>'.
                            _("Active Event Window (days)").': &nbsp; <a href="#" style="text-decoration:none" class="scriptinf" txt="'._("Security events older than number of days will be erased from SQL database").'"  onclick="GB_show(\'Configuration\',\''.Menu::get_menu_url('/conf/index.php?section=siem', 'configuration', 'administration', 'main').'\' ,480,\'80%\');return false">'.$backup_day.'</a><br/>'.
                            _("Active Event Window (events)").': &nbsp; <a href="#" style="text-decoration:none" class="scriptinf" txt="'._("Older security events will be erase when total number of events in database will reach this number").'" onclick="GB_show(\'Configuration\', \''.Menu::get_menu_url('/conf/index.php?section=siem', 'configuration', 'administration', 'main').'\' ,480,\'80%\');return false">'.format_cash($backup_events).'</a><br/>
                        </td>
                    </tr>
                </table>
            </td>';
        echo "  </TR>\n" . " </TABLE>\n" . "\n\n";
    }


    function ReadState()
    {
        $this->current_canned_query = ImportHTTPVar("caller", VAR_LETTER | VAR_USCORE);
        $this->num_result_rows = ImportHTTPVar("num_result_rows", VAR_DIGIT | VAR_SCORE);
        $this->current_sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
        $this->current_view = ImportHTTPVar("current_view", VAR_DIGIT);
        //echo "CURRENT VIEW: ".$this->current_view;
        // New CALC_FOUND_ROWS current_view = 0 initially
        //$this->current_view         = 1;
        $this->action_arg = ImportHTTPVar("action_arg", VAR_ALPHA | VAR_PERIOD | VAR_USCORE | VAR_SCORE | VAR_AT);
        $this->action_chk_lst = ImportHTTPVar("action_chk_lst", VAR_ALPHA | VAR_PUNC); /* array */
        $this->action_lst = ImportHTTPVar("action_lst", VAR_ALPHA | VAR_PUNC | VAR_SCORE); /* array */
        $this->action = ImportHTTPVar("action", VAR_ALPHA | VAR_USCORE);
    }


    function SaveState()
    {
        echo "<!-- Saving Query State -->\n";
        ExportHTTPVar("caller", $this->current_canned_query);
        ExportHTTPVar("num_result_rows", $this->num_result_rows);
        // The below line is commented to fix bug #1761605 please verify this doesnt break anything else -- Kevin Johnson
        ExportHTTPVar("sort_order", $this->current_sort_order);
        ExportHTTPVar("current_view", $this->current_view);
    }


    function SaveStateGET()
    {
        return "?caller=" . $this->current_canned_query . "&amp;num_result_rows=" . $this->num_result_rows . "&amp;current_view=" . $this->current_view;
    }


    function DumpState()
    {
        echo "<B>" . gettext("Query State") . "</B><BR>
          caller = '$this->current_canned_query'<BR>
          num_result_rows = '$this->num_result_rows'<BR>
          sort_order = '$this->current_sort_order'<BR>
          current_view = '$this->current_view'<BR>
          action_arg = '$this->action_arg'<BR>
          action = '$this->action'<BR>";
    }
}
?>
