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
* - CriteriaState()
* - InitState()
* - ReadState()
* - GetBackLink()
* - GetClearCriteriaString()
* - ClearCriteriaStateElement()
* - ClearAllCriteria()
* - PopHistory()
* - PushHistory()
* - PrintBackButton()
*
* Classes list:
* - CriteriaState
*/


defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/includes/base_state_common.inc.php");
include_once ("$BASE_path/includes/base_state_citems.inc.php");
class CriteriaState {
    var $clear_criteria_name;
    var $clear_criteria_element;
    var $clear_url;
    var $clear_url_params;
    var $criteria;
    function CriteriaState($url, $params = "") {
        $this->clear_url = $url;
        $this->clear_url_params = $params;
        /* XXX-SEC */
        GLOBAL $db;
        $tdb = & $db;
        $obj = & $this;
        $this->criteria['sig'] = new SignatureCriteria($tdb, $obj, "sig");
        //$this->criteria['sig_class'] = new SignatureClassificationCriteria($tdb, $obj, "sig_class");
        //$this->criteria['sig_priority'] = new SignaturePriorityCriteria($tdb, $obj, "sig_priority");
        //$this->criteria['ag'] = new AlertGroupCriteria($tdb, $obj, "ag");
        $this->criteria['sensor'] = new SensorCriteria($tdb, $obj, "sensor");
        $this->criteria['plugin'] = new PluginCriteria($tdb, $obj, "plugin");
        $this->criteria['sourcetype'] = new SourceTypeCriteria($tdb, $obj, "sourcetype");
        $this->criteria['category'] = new CategoryCriteria($tdb, $obj, "category");
        $this->criteria['rep'] = new ReputationCriteria($tdb, $obj, "rep");
        $this->criteria['otx'] = new OTXCriteria($tdb, $obj, "otx");
        $this->criteria['plugingroup'] = new PluginGroupCriteria($tdb, $obj, "plugingroup");
        $this->criteria['networkgroup'] = new NetworkGroupCriteria($tdb, $obj, "networkgroup");
        $this->criteria['userdata'] = new UserDataCriteria($tdb, $obj, "userdata");
        $this->criteria['idm_username'] = new IDMUsernameCriteria($tdb, $obj, "idm_username");
        $this->criteria['idm_hostname'] = new IDMHostnameCriteria($tdb, $obj, "idm_hostname");
        $this->criteria['idm_domain'] = new IDMDomainCriteria($tdb, $obj, "idm_domain");
        $this->criteria['time'] = new TimeCriteria($tdb, $obj, "time", TIME_CFCNT);
        $this->criteria['ip_addr'] = new IPAddressCriteria($tdb, $obj, "ip_addr", IPADDR_CFCNT);
        $this->criteria['hostid'] = new HostCriteria($tdb, $obj, "hostid");
        $this->criteria['netid'] = new NetCriteria($tdb, $obj, "netid");
		$this->criteria['ctx'] = new CtxCriteria($tdb, $obj, "ctx");
        $this->criteria['layer4'] = new Layer4Criteria($tdb, $obj, "layer4");
        $this->criteria['ip_field'] = new IPFieldCriteria($tdb, $obj, "ip_field", PROTO_CFCNT);
        $this->criteria['tcp_port'] = new TCPPortCriteria($tdb, $obj, "tcp_port", PROTO_CFCNT);
        // DEPRECATED TCP UDP Flags Field ICMP
        //$this->criteria['tcp_flags'] = new TCPFlagsCriteria($tdb, $obj, "tcp_flags");
        //$this->criteria['tcp_field'] = new TCPFieldCriteria($tdb, $obj, "tcp_field", PROTO_CFCNT);
        $this->criteria['udp_port'] = new UDPPortCriteria($tdb, $obj, "udp_port", PROTO_CFCNT);
        //$this->criteria['udp_field'] = new UDPFieldCriteria($tdb, $obj, "udp_field", PROTO_CFCNT);
        //$this->criteria['icmp_field'] = new ICMPFieldCriteria($tdb, $obj, "icmp_field", PROTO_CFCNT);
        $this->criteria['rawip_field'] = new TCPFieldCriteria($tdb, $obj, "rawip_field", PROTO_CFCNT);
        $this->criteria['data'] = new DataCriteria($tdb, $obj, "data", PAYLOAD_CFCNT);
        $this->criteria['ossim_type'] = new OssimTypeCriteria($db, $this, "ossim_type");
        $this->criteria['ossim_priority'] = new OssimPriorityCriteria($db, $this, "ossim_priority");
        $this->criteria['ossim_reliability'] = new OssimReliabilityCriteria($db, $this, "ossim_reliability");
        $this->criteria['ossim_asset_src'] = new OssimAssetSrcCriteria($db, $this, "ossim_asset_src");
        $this->criteria['ossim_asset_dst'] = new OssimAssetDstCriteria($db, $this, "ossim_asset_dst");
        $this->criteria['ossim_risk_c'] = new OssimRiskCCriteria($db, $this, "ossim_risk_c");
        $this->criteria['ossim_risk_a'] = new OssimRiskACriteria($db, $this, "ossim_risk_a");
        $this->criteria['device'] = new DeviceCriteria($db, $this, "device");
        /*
        * For new criteria, add a call to the appropriate constructor here, and implement
        * the appropriate class in base_stat_citems.inc.
        */
    }
    function InitState() {
        RegisterGlobalState();
        $valid_criteria_list = array_keys($this->criteria);
        foreach($valid_criteria_list as $cname) $this->criteria[$cname]->Init();
    }
    function ReadState() {
        RegisterGlobalState();
        /*
        * If the BACK button was clicked, shuffle the appropriate
        * criteria variables from the $back_list (history) array into
        * the current session ($_SESSION)
        */
        if (($GLOBALS['maintain_history'] == 1) && (ImportHTTPVar("back", VAR_DIGIT) == 1)) {
            PopHistory();
        }
        /*
        * Import, update and sanitize all persistant criteria variables
        */
        $valid_criteria_list = array_keys($this->criteria);
        foreach($valid_criteria_list as $cname) {
        	$this->criteria[$cname]->Import();
            //$this->criteria[$cname]->Sanitize();
        }
        /*
        * Check whether criteria elements need to be cleared
        */
        $this->clear_criteria_name = Util::htmlentities(ImportHTTPVar("clear_criteria", "", array_keys($this->criteria)));
        // Use htmlentities for Fortify test
        $this->clear_criteria_element = Util::htmlentities(ImportHTTPVar("clear_criteria_element", "", array_keys($this->criteria)));
        $this->clear_allcriteria = Util::htmlentities(ImportHTTPVar("clear_allcriteria","1"));
        if ($this->clear_criteria_name != "") $this->ClearCriteriaStateElement($this->clear_criteria_name, $this->clear_criteria_element);
        if ($this->clear_allcriteria != "") $this->ClearAllCriteria();
        
        foreach($valid_criteria_list as $cname) {
        	//$this->criteria[$cname]->Import();
            $this->criteria[$cname]->Sanitize();
        }
        
        /*
        * Save the current criteria into $back_list (history)
        */
        if ($GLOBALS['maintain_history'] == 1) PushHistory();
    }
    function GetBackLink() {
        return PrintBackButton();
    }
    function GetClearCriteriaString($name, $element = "") {
        return '&nbsp;&nbsp;<A HREF="' . $this->clear_url . '?clear_criteria=' . $name . '&amp;clear_criteria_element=' . str_replace('&amp;new=1','',$element.$this->clear_url_params) . '">...' . gettext("Clear") . '...</A>';
    }
    function GetClearCriteriaString2($name, $element = "") 
    {
        return '';
        //return '(<A HREF="' . $this->clear_url . '?clear_criteria=' . $name . '&amp;clear_criteria_element=' . str_replace('&amp;new=1','',$element.$this->clear_url_params) . '">x</A>) ';
    }
    
    function GetClearCriteriaUrl($name) 
    {
        return $this->clear_url . '?clear_criteria=' . $name . '&amp;clear_criteria_element=' . str_replace('&amp;new=1','',$this->clear_url_params);
    }
    
    function ClearCriteriaStateElement($name, $element) {
        $valid_criteria_list = array_keys($this->criteria);
        if (in_array($name, $valid_criteria_list)) {
            //ErrorMessage(gettext("Removing") . " '$name' " . gettext("from criteria"));
            $this->criteria[$name]->Init();
            $this->criteria[$name]->Import();
        } else ErrorMessage(gettext("Invalid criteria element"));
		if ($name=="ip_addr")
			$_SESSION["_hostgroup"]="";
    }
    function ClearAllCriteria() {
        $valid_criteria_list = array_keys($this->criteria);
        foreach($valid_criteria_list as $name) {
            //ErrorMessage(gettext("Removing") . " '$name' " . gettext("from criteria"));
            $this->criteria[$name]->Init();
            $this->criteria[$name]->Import();
        }
        if (empty($_GET["time_range"])) {
            $this->criteria["time"]->Clear();
            $_SESSION['time_range'] = "all";
            $_GET["time_range"]     = "all";
        }
		$_SESSION["_hostgroup"]="";
    }
}
/* ***********************************************************************
* Function: PopHistory()
*
* @doc Remove and restore the last entry of the history list (i.e.,
*      hit the back button in the browser)
*
* @see PushHistory PrintBackButton
*
************************************************************************/
function PopHistory() {
    if ($_SESSION['back_list_cnt'] >= 0) {
        /* Remove the state of the page from which the back button was
        * just hit
        */
        unset($_SESSION['back_list'][$_SESSION['back_list_cnt']]);
        /*
        * save a copy of the $back_list because session_destroy()/session_decode() will
        * overwrite it.
        */
        $save_back_list = $_SESSION['back_list'];
        $save_back_list_cnt = $_SESSION['back_list_cnt'] - 1;
        /* Restore the session
        *   - destroy all variables in the current session
        *   - restore proper back_list history entry into the current variables (session)
        *       - but, first delete the currently restored entry and
        *              decremement the history stack
        *   - push saved back_list back into session
        */
        session_unset();
        //if ($GLOBALS['debug_mode'] > 2) ErrorMessage("Popping a History Entry from #" . $save_back_list_cnt);
        session_decode($save_back_list[$save_back_list_cnt]["session"]);
        unset($save_back_list[$save_back_list_cnt]);
        --$save_back_list_cnt;
        $_SESSION['back_list'] = $save_back_list;
        $_SESSION['back_list_cnt'] = $save_back_list_cnt;
    }
}
/* ***********************************************************************
* Function: PushHistory()
*
* @doc Save the current criteria into the history list ($back_list,
*      $back_list_cnt) in order to support the BASE back button.
*
* @see PopHistory PrintBackButton
*
************************************************************************/
function PushHistory() {
    // if ($GLOBALS['debug_mode'] > 1) {
        // ErrorMessage("Saving state (into " . $_SESSION['back_list_cnt'] . ")");
    // }
    /* save the current session without the $back_list into the history
    *   - make a temporary copy of the $back_list
    *   - NULL-out the $back_list in $_SESSION (so that
    *       the current session is serialized without these variables)
    *   - serialize the current session
    *   - fix-up the QUERY_STRING
    *       - make a new QUERY_STRING that includes the temporary QueryState variables
    *       - remove &back=1 from any QUERY_STRING
    *   - add the current session into the $back_list (history)
    */
    if (isset($_SESSION['back_list'])) {
        $tmp_back_list = $_SESSION['back_list'];
    } else {
        $tmp_back_list = '';
    }
    if (isset($_SESSION['back_list_cnt'])) {
        $tmp_back_list_cnt = $_SESSION['back_list_cnt'];
    } else {
        $tmp_back_list_cnt = '';
    }
    $_SESSION['back_list'] = NULL;
    $_SESSION['back_list_cnt'] = - 1;
    $full_session = session_encode();
    $_SESSION['back_list'] = $tmp_back_list;
    $_SESSION['back_list_cnt'] = $tmp_back_list_cnt;
    $query_string = CleanVariable(str_replace("&amp;", "&", Util::htmlentities($_SERVER["QUERY_STRING"])), VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    if (isset($_POST['caller'])) $query_string.= "&amp;caller=" . Util::htmlentities($_POST['caller']);
    if (isset($_POST['num_result_rows'])) $query_string.= "&amp;num_result_rows=" . Util::htmlentities($_POST['num_result_rows']);
    if (isset($_POST['sort_order'])) $query_string.= "&amp;sort_order=" . Util::htmlentities($_POST['sort_order']);
    if (isset($_POST['current_view'])) $query_string.= "&amp;current_view=" . Util::htmlentities($_POST['current_view']);
    if (isset($_POST['submit'])) $query_string.= "&amp;submit=" . Util::htmlentities($_POST['submit']);
    //$query_string .= "&amp;time_range=".$_GET['time_range'];
    $query_string = preg_replace("/back=1&/", "", CleanVariable($query_string, VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER));
    ++$_SESSION['back_list_cnt'];
    $_SESSION['back_list'][$_SESSION['back_list_cnt']] = array(
        "SCRIPT_NAME" => $_SERVER["SCRIPT_NAME"],
        "QUERY_STRING" => $query_string,
        "session" => $full_session
    );
    // if ($GLOBALS['debug_mode'] > 1) {
        // ErrorMessage("Insert session into slot #" . $_SESSION['back_list_cnt']);
        // echo "Back List (Cnt = " . $_SESSION['back_list_cnt'] . ") <PRE style='font-size:9px'>";
        // print_r($_SESSION);
        // echo "</PRE>";
    // }
}
/* ***********************************************************************
* Function: PrintBackButton()
*
* @doc Returns a string with the URL of the previously viewed
*      page.  Clicking this link is equivalent to using the browser
*      back-button, but all the associated BASE meta-information
*      propogates correctly.
*
* @see PushHistory PopHistory
*
************************************************************************/
function PrintBackButton() {
    if ($GLOBALS['maintain_history'] == 0) return "&nbsp;";
    $criteria_num = $_SESSION['back_list_cnt'] - 1;
    if (isset($_SESSION['back_list'][$criteria_num]["SCRIPT_NAME"])) return "<FONT><A HREF=\"" . $_SESSION['back_list'][$criteria_num]["SCRIPT_NAME"] . "?back=1&" . $_SESSION['back_list'][$criteria_num]["QUERY_STRING"] . "\">" . gettext("Back") . "</A></FONT>&nbsp;|&nbsp;";
    else return "&nbsp;";
}
?>
