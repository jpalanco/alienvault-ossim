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


/*
* $caller: an auxiliary variable used to determine the how the search parameters were entered (i.e.
*          whether through a form or through another mechanism
*  - "stat_alerts" : display results based on the the Alert Listings
*  - "top_tcp" :
*  - "top_udp" :
*  - "top_icmp" :
*  - "last_tcp" :
*  - "last_udp" :
*  - "last_icmp" :
*
* $submit: used to determine the next action which should be taken when the form is submitted.
*  - gettext("Query DB")         : triggers a query into the database
*  - gettext("ADD TIME")         : adds another date/time row
*  - _ADDADDR         : adds another IP address row
*  - gettext("ADD IP Field")      : adds another IP field row
*  - gettext("ADD TCP Port")      : adds another TCP port row
*  - gettext("ADD TCP Field")     : adds another TCP field row
*  - gettext("ADD UDP Port")      : adds another UDP port row
*  - gettext("ADD UDP Field")     : adds another UDP field row
*  - gettext("ADD ICMP Field")    : adds another ICMP field row
*  - "#X-(X-X)"       : sid-cid keys for a packet lookup
*  - _SELECTED
*  - _ALLONSCREEN
*  - _ENTIREQUERY
*
* $layer4: stores the layer 4 protocol used in query
*
* $save_sql: the current sql string generating the query
*
* $save_criteria: HTML-human readable criteria of the $save_sql string
*
* $num_result_rows: rows in the entire record set retried under the current
*                   query
*
* $current_view: current view of the result set
*
* $sort_order: how to sort the output
*
* ----- Search Result Variables ----
* $action_chk_lst[]: array of check boxes to determine if an alert
*                    was selected for action
* $action_lst[]: array of (sid,cid) of all alerts on screen
*/
require ("base_conf.php");
require ("vars_session.php");
$_SESSION['norefresh'] = 1;
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");

include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_ag_common.php");
include_once ("$BASE_path/base_qry_common.php");

$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . gettext("Query+DB"));

$new = ImportHTTPVar("new", VAR_DIGIT);
$pag = ImportHTTPVar("pag", VAR_DIGIT);

/* This call can include many values. */
$submit = Util::htmlentities(ImportHTTPVar("submit", VAR_DIGIT | VAR_PUNC | VAR_LETTER, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    gettext("Delete Entire Query"),
    gettext("Query DB"),
    gettext("ADD TIME"),
    gettext("ADD Addr"),
    gettext("ADD IP Field"),
    gettext("ADD TCP Port"),
    gettext("ADD TCP Field"),
    gettext("ADD UDP Port"),
    gettext("ADD UDP Field"),
    _ADDICMPFIELD
)));

/* Search Box. DK */
/* For your own mental health, skip over until 20 or 30 lines below :P */

$_GET["search_str"] = Util::htmlentities(str_replace("\'","'",str_replace('\"','$$$$',str_replace('"','$$$$',$_GET["search_str"])))); // bypass quotes

if ($submit == gettext("Signature") && $_GET['search_str'] != '')
{
    $search_str = Util::htmlentities(ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_LETTER)); // htmlentities for fortify test
    $_GET['sig'][0] = "LIKE";
    $_GET['sig'][1] = str_replace('$$$$','"',$search_str);
    $_GET['sig'][2] = preg_match('/^\!/',$search_str) ? "!=" : "=";
    $_GET['sig_type'] = 0;
    $_GET['submit'] = $submit = gettext("Query DB");

    header("Location: base_stat_alerts.php?sort_order=occur_d&submit=Query+DB&sig_type=".$_GET['sig_type']."&sig%5B0%5D=".urlencode($_GET['sig'][0])."&sig%5B1%5D=".urlencode($_GET['sig'][1])."&sig%5B2%5D=".urlencode($_GET['sig'][2]));
    exit;

} elseif ($submit == "Payload") {

    //$search_str = ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_LETTER | VAR_AT);
    $search_str = $_GET['search_str'];
    $_GET["search"] = 1;
    $_GET["data_cnt"] = 1;
    $_GET["data"][0] = array("","LIKE",str_replace('$$$$','"',$search_str),"","");
    $_GET['submit'] = $submit = gettext("Query DB");  

} elseif (preg_match("/.*IP/",$submit)) {
	$ip_search = ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_FSLASH | VAR_LETTER);
	
	if (preg_match("/(\!?)(\d+\.\d+\.\d+\.\d+(\/\d+)?)\s*AND\s*(\!?)(\d+\.\d+\.\d+\.\d+(\/\d+)?)/",$ip_search,$fnd)) {
		// ip AND ip is treated as ip_src AND ip_dst filter
		$not1 = $fnd[1];
		$ip1 = $fnd[2];
		$not2 = Util::htmlentities($fnd[4]); // htmlentities for fortify test
		$ip2 = Util::htmlentities($fnd[5]); // htmlentities for fortify test		
		$mask1 = explode ("/",$ip1);
	    $ip_aux1 = explode (".",$mask1[0]);
		$mask2 = explode ("/",$ip2);
	    $ip_aux2 = explode (".",$mask2[0]);

        if (preg_match("/(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/",$mask1[0]) && $mask1[1]>=0 && $mask1[1]<=32 && preg_match("/(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/",$mask2[0]) && $mask2[1]>=0 && $mask2[1]<=32) {
    	    $ipfilter = array();
    	    $ipfilter[] = array ("(","ip_src",$not1."=",$ip_aux1[0],$ip_aux1[1],$ip_aux1[2],$ip_aux1[3],$ip1,"","AND",$mask1[1]);
    	    $ipfilter[] = array ("","ip_dst",$not2."=",$ip_aux2[0],$ip_aux2[1],$ip_aux2[2],$ip_aux2[3],$ip2,")","OR",$mask2[1]);
    	    $ipfilter[] = array ("(","ip_dst",$not1."=",$ip_aux1[0],$ip_aux1[1],$ip_aux1[2],$ip_aux1[3],$ip1,"","AND",$mask1[1]);
    	    $ipfilter[] = array ("","ip_src",$not2."=",$ip_aux2[0],$ip_aux2[1],$ip_aux2[2],$ip_aux2[3],$ip2,")","",$mask2[1]);    
        }
	    	    	    		
	} else {
	
		// ip OR ip
	    $ip_search = preg_replace("/\s*AND.*/","",$ip_search);
	    $ipsf = preg_split("/\s*OR\s*/",$ip_search);
	    $ipfilter = array();
	    $ipop = ($submit==_("Src IP")) ? "ip_src" : (($submit==_("Dst IP")) ? "ip_dst" : "ip_both");
	    for ($i=0;$i<count($ipsf);$i++) {
	        $ip_search = $ipsf[$i];
	        $mask = explode ("/",$ip_search);
	        $ip_aux = explode (".",Util::htmlentities($mask[0]));
	        $not = "";
	        if (preg_match("/^\!/",$ip_aux[0])) {
	        	$not = "!";
	        	$ip_aux[0] = substr($ip_aux[0],1);
	        }
	        if (count($ip_aux) == 2) {
	        	$ip_aux[2] = "0";
	        	$ip_aux[3] = "0";
	        	$mask[1] = "16";
	        	$ip_search .= ".0.0/16";
	        } elseif (count($ip_aux) == 2) {
	        	$ip_aux[3] = "0";
	        	$mask[1] = "24";
	        	$ip_search .= ".0/24";
	        }
	        $or = (($i+1)==count($ipsf)) ? "" : "OR";
	        if (preg_match("/(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/",$mask[0]) && $mask[1]>=0 && $mask[1]<=32) { 
	           $ipfilter[] = array ("",$ipop,$not."=",$ip_aux[0],$ip_aux[1],$ip_aux[2],$ip_aux[3],Util::htmlentities($ip_search),"",$or,Util::htmlentities($mask[1]));
	       }
	    }
	    
	}
    $_SESSION["ip_addr"] = $ipfilter;
    $_GET["ip_addr"] = $ipfilter;
    $_SESSION["ip_addr_cnt"] = count($ipfilter);
    $_GET["ip_addr_cnt"] = count($ipfilter);
    $_SESSION["ip_field"] = array (
        array ("","","=")
    );
    $_GET["ip_field"] = array (
        array ("","","=")
    );
    $_SESSION["ip_field_cnt"] = 1;
    $_GET["ip_field_cnt"] = 1;

} elseif (preg_match("/IDM.*/",$submit)) {
	// IDM
	$ip_search = Util::htmlentities(ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_FSLASH | VAR_LETTER));
	$var = (preg_match("/Username/",$submit)) ? "idm_username" : ( (preg_match("/Hostname/",$submit)) ? "idm_hostname" : "idm_domain" );
	$_SESSION[$var] = array ($ip_search,"both");
	$_GET[$var] = array ($ip_search,"both");
}
$_GET["search_str"] = Util::htmlentities(str_replace('$$$$','&quot;',$_GET["search_str"])); // restore quotes

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 0, 1);

// Always was ossim_risk_a != "" when QueryForm submit, better use 'search' param
//if ($_GET['sensor'] != "" || $_GET["ossim_risk_a"] != "") {
if ($_GET['search'] == "1") {
	unset($_GET['search']);
	$_GET['submit'] = gettext("Query DB");
	$submit = gettext("Query DB");
}

/* Code to correct 'interesting' (read: unexplained) browser behavior */
/* Something with Netscape 4.75 such that the $submit variable is no recognized
* under certain circumstances.  This one is a result of using HTTPS and
* clicking on TCP traffic profile from base_main.php
*/
if ($cs->criteria['layer4']->Get() != "" && $submit == "") $submit = gettext("Query DB");

// Set the sort order to the new sort order if one has been selected
$sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
if ($sort_order == "" || !isset($sort_order)) 
{
    // If one wasn't picked, try the prev_sort_order
    $sort_order = ImportHTTPVar("prev_sort_order", VAR_LETTER | VAR_USCORE);
    // If there was no previous sort order, default it to none.
    if (($sort_order == "" || !isset($sort_order)) && $submit == gettext("Query DB")) {
        //$sort_order = "none"; //default to none.
        $_GET["sort_order"] = "time_d";
        $sort_order = "time_d"; //if ($_GET['sensor'] != "") $sort_order = "time_d";
    }
}
/* End 'interesting' browser code fixes */

/* Totally new Search */
if (($new == 1) && ($submit == ""))
{
	// This is commented.
	// When you return to the search form, you must preserve all criteria. Lately only was reseting the _cnt vars
	// Now doesn't reset anything
    //$cs->InitState();
}
/* is this a new query, invoked from the SEARCH screen ? */
/* if the query string if very long (> 700) then this must be from the Search screen  */
$back = ImportHTTPVar("back", VAR_DIGIT);
if (($GLOBALS['maintain_history'] == 1) && ($back != 1) && ($submit == gettext("Query DB")) && (isset($_GET['search']) && $_GET['search'] == 1)) {
    !empty($_SESSION['back_list_cnt']) ? $_SESSION['back_list_cnt']-- : $_SESSION['back_list_cnt'] = 0; /* save on top of initial blank query screen   */
    $submit = ""; /*  save entered search criteria as if one hit Enter */
    $_POST['submit'] = $submit;
    $cs->ReadState(); /* save the search criteria       */
    // Solve error when payload is searched cnt = 1
//    if ($_GET{"data"} {
//        0
//    } {
//        2
//    } != "") $cs->criteria['data']->criteria_cnt = 1;

if ($_GET["data"][0][2] != "") $cs->criteria['data']->criteria_cnt = 1;
    $submit = gettext("Query DB"); /* restore the real submit value  */
    $_POST['submit'] = $submit;
}
$cs->ReadState();

$qs = new QueryState();
$qs->AddCannedQuery("last_tcp", $last_num_alerts, gettext("Last TCP Events"), "time_d");
$qs->AddCannedQuery("last_udp", $last_num_alerts, gettext("Last UDP Events"), "time_d");
$qs->AddCannedQuery("last_icmp", $last_num_alerts, gettext("Last ICMP Events"), "time_d");
$qs->AddCannedQuery("last_any", $last_num_alerts, gettext("Last Events"), "time_d");

$page_title = gettext("Query Results");

$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery())
{
    if (!array_key_exists("minimal_view", $_GET)) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
    else PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , "", 1);
}
else
{
    if (!array_key_exists("minimal_view", $_GET)) PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
    else PrintBASESubHeader($page_title, $page_title, "", 1);
}

if ($event_cache_auto_update == 1) UpdateAlertCache($db);

?>
<FORM METHOD="POST" name="PacketForm" id="PacketForm" ACTION="base_qry_main.php" style="margin:0 auto">
<input type='hidden' name="search" value="1" />
<input type="hidden" name="sort_order" value="<?php echo ($_GET['sort_order'] != "") ? Util::htmlentities($_GET['sort_order']) : Util::htmlentities($_POST['sort_order']) ?>">
<?php
/* Dump some debugging information on the shared state */
/* a browsing button was clicked -> increment view */
if (is_numeric($submit) || $pag!='')
{
    $pagn = (is_numeric($submit)) ? $submit : $pag;
    $qs->MoveView($pagn);
    $submit = gettext("Query DB");
}
//echo $submit." ".$qs->isCannedQuery()." ".$qs->GetCurrentSort()." ".$_SERVER["QUERY_STRING"];
/* Run the SQL Query and get results */

//print_r($criteria_clauses);
$from = "FROM acid_event " . $criteria_clauses[0];
$where = "";
if ($criteria_clauses[1] != "") $where = "WHERE " . $criteria_clauses[1];
$where = str_replace("::%", ":%:%", $where);
if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
    if ($matches[2] != $matches[3]) {
        //print "A";
        $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
    } else {
        //print "B";
        $where = $matches[1] . " AND timestamp >= '" . $matches[2] . "' " . $matches[4];
    }
}
//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
$qs->AddValidActionOp(gettext("Insert into DS Group"));
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->AddValidActionOp(gettext("Delete Entire Query"));
$qs->SetActionSQL("SELECT hex(acid_event.id) as id $from $where");
//$et->Mark("Initialization");

$qs->RunAction($submit, PAGE_QRY_ALERTS, $db);
$et->Mark("Alert Action");

//if ($debug_mode > 0) ErrorMessage("Initial/Canned Query or Sort Clicked");

require ("base_qry_sqlcalls.php");

$qs->SaveState();


echo "\n</FORM>\n";
if (!array_key_exists("minimal_view", $_GET)) {
    PrintBASESubFooter();
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
echo "</body>\r\n</html>";
?>
