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
* - BaseCriteria()
* - Init()
* - Import()
* - Clear()
* - Sanitize()
* - SanitizeElement()
* - PrintForm()
* - AddFormItem()
* - GetFormItemCnt()
* - SetFormItemCnt()
* - Set()
* - Get()
* - ToSQL()
* - Description()
* - isEmpty()
* - Import()
* - Sanitize()
* - GetFormItemCnt()
* - Set()
* - Get()
* - isEmpty()
* - MultipleElementCriteria()
* - Init()
* - Import()
* - Sanitize()
* - SanitizeElement()
* - GetFormItemCnt()
* - SetFormItemCnt()
* - AddFormItem()
* - Set()
* - Get()
* - isEmpty()
* - PrintForm()
* - Compact()
* - SanitizeElement()
* - Description()
* - SignatureCriteria()
* - Init()
* - Import()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - IPAddressCriteria()
* - Import()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - IPFieldCriteria()
* - PrintForm()
* - ToSQL()
* - Description()
* - TCPPortCriteria()
* - PrintForm()
* - ToSQL()
* - Description()
* - TCPFieldCriteria()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - isEmpty()
* - UDPPortCriteria()
* - PrintForm()
* - ToSQL()
* - Description()
* - UDPFieldCriteria()
* - PrintForm()
* - ToSQL()
* - Description()
* - ICMPFieldCriteria()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - DataCriteria()
* - Init()
* - Import()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
* - Init()
* - Clear()
* - SanitizeElement()
* - PrintForm()
* - ToSQL()
* - Description()
*
* Classes list:
* - BaseCriteria
* - SingleElementCriteria extends BaseCriteria
* - MultipleElementCriteria extends BaseCriteria
* - ProtocolFieldCriteria extends MultipleElementCriteria
* - SignatureCriteria extends SingleElementCriteria
* - SignatureClassificationCriteria extends SingleElementCriteria
* - SignaturePriorityCriteria extends SingleElementCriteria
* - AlertGroupCriteria extends SingleElementCriteria
* - SensorCriteria extends SingleElementCriteria
* - TimeCriteria extends MultipleElementCriteria
* - IPAddressCriteria extends MultipleElementCriteria
* - IPFieldCriteria extends ProtocolFieldCriteria
* - TCPPortCriteria extends ProtocolFieldCriteria
* - TCPFieldCriteria extends ProtocolFieldCriteria
* - TCPFlagsCriteria extends SingleElementCriteria
* - UDPPortCriteria extends ProtocolFieldCriteria
* - UDPFieldCriteria extends ProtocolFieldCriteria
* - ICMPFieldCriteria extends ProtocolFieldCriteria
* - Layer4Criteria extends SingleElementCriteria
* - DataCriteria extends MultipleElementCriteria
* - OssimPriorityCriteria extends SingleElementCriteria
* - OssimRiskACriteria extends SingleElementCriteria
* - OssimRiskCCriteria extends SingleElementCriteria
* - OssimReliabilityCriteria extends SingleElementCriteria
* - OssimAssetSrcCriteria extends SingleElementCriteria
* - OssimAssetDstCriteria extends SingleElementCriteria
* - OssimTypeCriteria extends SingleElementCriteria
*/


require_once 'classes/Util.inc';

defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
class BaseCriteria {
    var $criteria;
    var $export_name;
    var $db;
    var $cs;
    var $param;
    function BaseCriteria(&$db, &$cs, $name) {
        $this->db = & $db;
        $this->cs = & $cs;
        $this->export_name = $name;
        $this->criteria = NULL;
        $this->param = NULL;
    }
    function Init() {
    }
    function Import() {
        /* imports criteria from POST, GET, or the session */
    }
    function Clear() {
        /* clears the criteria */
    }
    function Sanitize() {
        /* clean/validate the criteria */
    }
    function SanitizeElement() {
        /* clean/validate the criteria */
    }
    function PrintForm() {
        /* prints the HTML form to input the criteria */
    }
    function AddFormItem() {
        /* adding another item to the HTML form  */
    }
    function GetFormItemCnt() {
        /* returns the number of items in this form element  */
    }
    function SetFormItemCnt() {
        /* sets the number of items in this form element */
    }
    function Set($value) {
        /* set the value of this criteria */
    }
    function Get() {
        /* returns the value of this criteria */
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        /* generate human-readable description of this criteria */
    }
    function isEmpty() {
        /* returns if the criteria is empty */
    }
};
class SingleElementCriteria extends BaseCriteria {
    function Import() {
        //$this->criteria = SetSessionVar($this->export_name);
    	// Secure assing to criteria (fix XSS issue)

    	$criteria_aux = SetSessionVar($this->export_name);

        // Array mode (time, ip_fields...)
    	if (is_array($criteria_aux)) {
        	foreach ($criteria_aux as $i => $val_aux) {
        		// Array is bi-dimensional
        		if (is_array($criteria_aux[$i])) {
        			foreach ($criteria_aux[$i] as $j => $val_aux2) {
        				if ($criteria_aux[$i][$j] == ">=") {
        					$this->criteria[$i][$j] = ">=";
        				} elseif ($criteria_aux[$i][$j] == "<=") {
        					$this->criteria[$i][$j] = "<=";
        				} elseif ($criteria_aux[$i][$j] == ">") {
        					$this->criteria[$i][$j] = ">";
        				} elseif ($criteria_aux[$i][$j] == "<") {
        					$this->criteria[$i][$j] = "<";
        				} else {
        					$this->criteria[$i][$j] = Util::htmlentities($criteria_aux[$i][$j]);
        				}
        			}
        		} else {
        			$this->criteria[$i] = Util::htmlentities($criteria_aux[$i]);
        		}
        	}
        // String mode (signature, payload...)
        } else {
        	$this->criteria = Util::htmlentities($criteria_aux);
        }
        $_SESSION[$this->export_name] = & $this->criteria;
    }
    function Sanitize() {
    	$this->SanitizeElement();
    }
    function GetFormItemCnt() {
        return -1;
    }
    function Set($value) {
        $this->criteria = $value;
    }
    function Get() {
        return $this->criteria;
    }
    function isEmpty() {
        if ($this->criteria == "") return true;
        else return false;
    }
};
class MultipleElementCriteria extends BaseCriteria {
    var $element_cnt;
    var $criteria_cnt;
    var $valid_field_list = Array();
    function MultipleElementCriteria(&$db, &$cs, $export_name, $element_cnt, $field_list = Array()) {
        $tdb = & $db;
        $cs = & $cs;
        $this->BaseCriteria($tdb, $cs, $export_name);
        $this->element_cnt = $element_cnt;
        $this->criteria_cnt = 0;
        $this->valid_field_list = $field_list;
    }
    function Init() {
        InitArray($this->criteria, $GLOBALS['MAX_ROWS'], $this->element_cnt, "");
        $this->criteria_cnt = 1;
        $_SESSION[$this->export_name . "_cnt"] = & $this->criteria_cnt;
    }
    function Import() {
        //$this->criteria = SetSessionVar($this->export_name);
	    // Secure assing to criteria (fix XSS issue)
    	$criteria_aux = SetSessionVar($this->export_name);

        // Array mode (time, ip_fields...)
    	if (is_array($criteria_aux)) {
        	for ($i = 0; $i < count($criteria_aux); $i++) {
        		// Array is bi-dimensional
        		if (is_array($criteria_aux[$i])) {
        			foreach ($criteria_aux[$i] as $j => $val_aux) {
        				if ($criteria_aux[$i][$j] == ">=") {
        					$this->criteria[$i][$j] = ">=";
        				} elseif ($criteria_aux[$i][$j] == "<=") {
        					$this->criteria[$i][$j] = "<=";
        				} elseif ($criteria_aux[$i][$j] == ">") {
        					$this->criteria[$i][$j] = ">";
        				} elseif ($criteria_aux[$i][$j] == "<") {
        					$this->criteria[$i][$j] = "<";
        				} else {
        					$this->criteria[$i][$j] = Util::htmlentities($criteria_aux[$i][$j]);
        				}
        			}
        		} else {
        			$this->criteria[$i] = Util::htmlentities($criteria_aux[$i]);
        		}
        	}
        // String mode (signature, payload...)
        } else {
        	$this->criteria = Util::htmlentities($criteria_aux);
        }
        $this->criteria_cnt = Util::htmlentities(SetSessionVar($this->export_name . "_cnt"));
        $_SESSION[$this->export_name] = & $this->criteria;
        $_SESSION[$this->export_name . "_cnt"] = & $this->criteria_cnt;
    }
    function Sanitize() {
        if (in_array("criteria", array_keys(get_object_vars($this)))) {
            for ($i = 0; $i < $this->element_cnt; $i++) {
                if (isset($this->criteria[$i])) $this->SanitizeElement($i);
            }
        }
    }
    function SanitizeElement($i) {
    }
    function GetFormItemCnt() {
        return $this->criteria_cnt;
    }
    function SetFormItemCnt($value) {
        $this->criteria_cnt = $value;
    }
    function AddFormItem(&$submit, $submit_value) {
        $this->criteria_cnt = & $this->criteria_cnt;
        AddCriteriaFormRow($submit, $submit_value, $this->criteria_cnt, $this->criteria, $this->element_cnt);
    }
    function Set($value) {
        $this->criteria = $value;
    }
    function Get() {
        return $this->criteria;
    }
    function isEmpty() {
        if ($this->criteria_cnt == 0) return true;
        else return false;
    }
    function PrintForm($field_list, $blank_field_string, $add_button_string) {
        for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if (!is_array($this->criteria[$i])) $this->criteria = array();
            echo '    <SELECT NAME="' . htmlspecialchars($this->export_name) . '[' . $i . '][0]">';
            echo '      <OPTION VALUE=" " ' . chk_select($this->criteria[$i][0], " ") . '>__</OPTION>';
            echo '      <OPTION VALUE="(" ' . chk_select($this->criteria[$i][0], "(") . '>(</OPTION>';
            echo '    </SELECT>';
            echo '    <SELECT NAME="' . htmlspecialchars($this->export_name) . '[' . $i . '][1]">';
            echo '      <OPTION VALUE=" "      ' . chk_select($this->criteria[$i][1], " ") . '>' . $blank_field_string . '</OPTION>';
            reset($field_list);
            foreach($field_list as $field_name => $field_human_name) {
                echo '   <OPTION VALUE="' . Util::htmlentities($field_name) . '" ' . chk_select($this->criteria[$i][1], $field_name) . '>' . Util::htmlentities($field_human_name) . '</OPTION>';
            }
            echo '    </SELECT>';
            echo '    <SELECT NAME="' . htmlspecialchars($this->export_name) . '[' . $i . '][2]">';
            echo '      <OPTION VALUE="="  ' . chk_select($this->criteria[$i][2], "=") . '>=</OPTION>';
            echo '      <OPTION VALUE="!=" ' . chk_select($this->criteria[$i][2], "!=") . '>!=</OPTION>';
            echo '      <OPTION VALUE="<"  ' . chk_select($this->criteria[$i][2], "<") . '><</OPTION>';
            echo '      <OPTION VALUE="<=" ' . chk_select($this->criteria[$i][2], "<=") . '><=</OPTION>';
            echo '      <OPTION VALUE=">"  ' . chk_select($this->criteria[$i][2], ">") . '>></OPTION>';
            echo '      <OPTION VALUE=">=" ' . chk_select($this->criteria[$i][2], ">=") . '>>=</OPTION>';
            echo '    </SELECT>';
            echo '    <INPUT TYPE="text" NAME="' . htmlspecialchars($this->export_name) . '[' . $i . '][3]" SIZE=5 VALUE="' . $this->criteria[$i][3] . '">';
            echo '    <SELECT NAME="' . htmlspecialchars($this->export_name) . '[' . $i . '][4]">';
            echo '      <OPTION VALUE=" " ' . chk_select($this->criteria[$i][4], " ") . '>__</OPTION';
            echo '      <OPTION VALUE="(" ' . chk_select($this->criteria[$i][4], "(") . '>(</OPTION>';
            echo '      <OPTION VALUE=")" ' . chk_select($this->criteria[$i][4], ")") . '>)</OPTION>';
            echo '    </SELECT>';
            echo '    <SELECT NAME="' . htmlspecialchars($this->export_name) . '[' . $i . '][5]">';
            echo '      <OPTION VALUE=" "   ' . chk_select($this->criteria[$i][5], " ") . '>__</OPTION>';
            echo '      <OPTION VALUE="OR" ' . chk_select($this->criteria[$i][5], "OR") . '>' . gettext("OR") . '</OPTION>';
            echo '      <OPTION VALUE="AND" ' . chk_select($this->criteria[$i][5], "AND") . '>' . gettext("AND") . '</OPTION>';
            echo '    </SELECT>';
            if ($i == $this->criteria_cnt - 1) echo '    <INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="' . htmlspecialchars($add_button_string) . '">';
            echo '<BR>';
        }
    }
    function Compact() {
        if ($this->isEmpty()) {
            $this->criteria = "";
            $_SESSION[$this->export_name] = & $this->criteria;
        }
    }
};
class ProtocolFieldCriteria extends MultipleElementCriteria {
    function SanitizeElement($i) {
        // Make a copy of the element array
        $curArr = $this->criteria[$i];
        // Sanitize the element
        $this->criteria[$i][0] = @CleanVariable($curArr[0], VAR_OPAREN);
        $this->criteria[$i][1] = @CleanVariable($curArr[1], "", array_keys($this->valid_field_list));
        $this->criteria[$i][2] = @CleanVariable($curArr[2], "", array(
            "=",
            "!=",
            "<",
            "<=",
            ">",
            ">="
        ));
        $this->criteria[$i][3] = @CleanVariable($curArr[3], VAR_DIGIT);
        $this->criteria[$i][4] = @CleanVariable($curArr[4], VAR_OPAREN | VAR_CPAREN);
        $this->criteria[$i][5] = @CleanVariable($curArr[5], "", array(
            "AND",
            "OR"
        ));
        // Destroy the copy
        unset($curArr);
    }
    function Description($human_fields) {
        $tmp = "";
        for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if ($tmp != "") $tmp .= " ";
        	if (is_array($this->criteria[$i])) if ($this->criteria[$i][1] != " " && $this->criteria[$i][3] != "") $tmp = $tmp . $this->criteria[$i][0] . $human_fields[($this->criteria[$i][1]) ] . ' ' . $this->criteria[$i][2] . ' ' . $this->criteria[$i][3] . $this->criteria[$i][4] . ' ' . $this->criteria[$i][5];
        }
        //if ($tmp != "") $tmp = $tmp . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}
class SignatureCriteria extends SingleElementCriteria {
    /*
    * $sig[3]: stores signature
    *   - [0] : exactly, roughly
    *   - [1] : signature
    *   - [2] : =, !=
    */
    var $sig_type;
    var $criteria = array(
        0 => '',
        1 => ''
    );
    function SignatureCriteria(&$db, &$cs, $export_name) {
        $tdb = & $db;
        $cs = & $cs;
        $this->BaseCriteria($tdb, $cs, $export_name);
        $this->sig_type = "";
    }
    function Init() {
        InitArray($this->criteria, 3, 0, "");
        $this->sig_type = "";
    }
    function Import() {
        parent::Import();
        $this->sig_type = Util::htmlentities(SetSessionVar("sig_type"));
        $_SESSION['sig_type'] = & $this->sig_type;
    }
    function Clear() {
    }
    function SanitizeElement() {
        if (!isset($this->criteria[0]) || !isset($this->criteria[1])) {
            $this->criteria = array(
                0 => '',
                1 => ''
            );
        }
        $this->criteria[0] = CleanVariable(@$this->criteria[0], "", array(
            " ",
            "=",
            "LIKE"
        ));
        $this->criteria[1] = filterSql(@$this->criteria[1]); /* signature name */
        $this->criteria[2] = CleanVariable(@$this->criteria[2], "", array(
            "=",
            "!="
        ));
    }
    function PrintForm() {
        if (!@is_array($this->criteria)) $this->criteria = array();
        echo '<SELECT NAME="sig[0]"><OPTION VALUE=" "  ' . chk_select(@$this->criteria[0], " ") . '>' . gettext("{ signature }");
        echo '                      <OPTION VALUE="="     ' . chk_select(@$this->criteria[0], "=") . '>' . gettext("exactly");
        echo '                      <OPTION VALUE="LIKE" ' . chk_select(@$this->criteria[0], "LIKE") . '>' . gettext("roughly") . '</SELECT>';
        echo '<SELECT NAME="sig[2]"><OPTION VALUE="="  ' . chk_select(@$this->criteria[2], "=") . '>=';
        echo '                      <OPTION VALUE="!="     ' . chk_select(@$this->criteria[2], "!=") . '>!=';
        echo '</SELECT>';
        echo '<INPUT TYPE="text" NAME="sig[1]" SIZE=40 VALUE="' . htmlspecialchars(@$this->criteria[1]) . '"><BR>';
        if ($GLOBALS['use_sig_list'] > 0) {
            $temp_sql = "SELECT DISTINCT sig_name FROM signature";
            if ($GLOBALS['use_sig_list'] == 1) {
                $temp_sql = $temp_sql . " WHERE sig_name NOT LIKE '%SPP\_%'";
            }
            $temp_sql = $temp_sql . " ORDER BY sig_name";
            $tmp_result = $this->db->baseExecute($temp_sql);
            echo '<SELECT NAME="sig_lookup"
                       onChange=\'PacketForm.elements[4].value =
                         this.options[this.selectedIndex].value;return true;\'>
                <OPTION VALUE="null" SELECTED>{ Select Signature from List }';
            if ($tmp_result) {
                while ($myrow = $tmp_result->baseFetchRow()) echo '<OPTION VALUE="' . $myrow[0] . '">' . $myrow[0];
                $tmp_result->baseFreeRows();
            }
            echo '</SELECT><BR>';
        }
    }
    function ToSQL() {
    }
    
    function Description_light()
    {
        $tmp = $tmp_human = "";
        if ((isset($this->criteria[0])) && ($this->criteria[0] != " ") && (isset($this->criteria[1])) && ($this->criteria[1] != "")) {
            $search = $this->criteria[1];
            if ($this->criteria[0] == '=' && $this->criteria[2] == '!=') {
                $tmp_human = '!=';
                $search = preg_replace("/^\!/","",$this->criteria[1]);
            }
            else if ($this->criteria[0] == '=' && $this->criteria[2] == '=') $tmp_human = '=';
            else if ($this->criteria[0] == 'LIKE' && $this->criteria[2] == '!=') {
                $tmp_human = ' ' . gettext("does not contain") . ' ';
                $search = preg_replace("/^\!/","",$this->criteria[1]);
            }
            else if ($this->criteria[0] == 'LIKE' && $this->criteria[2] == '=') $tmp_human = ' ' . gettext("contains") . ' ';
            $tmp = $tmp . gettext("Signature") . ' ' . $tmp_human . ' "';
            $pidsid = explode(";",$search);
            
            if ($this->sig_type == 1) $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . $tmp . html_entity_decode(preg_replace("/.*##/","",BuildSigByPlugin(intval($pidsid[0]),intval($pidsid[1]), $this->db))) . '" ';
            else $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . $tmp . Util::htmlentities($search, ENT_COMPAT, "UTF-8") . '"';
        }
        return $tmp;
    }
    
    function Description() {
        $tmp = $tmp_human = "";
        if ((isset($this->criteria[0])) && ($this->criteria[0] != " ") && (isset($this->criteria[1])) && ($this->criteria[1] != "")) {
        	$search = $this->criteria[1];
            if ($this->criteria[0] == '=' && $this->criteria[2] == '!=') {
            	$tmp_human = '!=';
            	$search = preg_replace("/^\!/","",$this->criteria[1]);
            }
            else if ($this->criteria[0] == '=' && $this->criteria[2] == '=') $tmp_human = '=';
            else if ($this->criteria[0] == 'LIKE' && $this->criteria[2] == '!=') {
            	$tmp_human = ' ' . gettext("does not contain") . ' ';
            	$search = preg_replace("/^\!/","",$this->criteria[1]);
            }
            else if ($this->criteria[0] == 'LIKE' && $this->criteria[2] == '=') $tmp_human = ' ' . gettext("contains") . ' ';
            $tmp = $tmp . gettext("Signature") . ' ' . $tmp_human . ' "';
            $pidsid = explode(";",$search);
            if ($this->sig_type == 1) $tmp = $tmp . html_entity_decode(preg_replace("/.*##/","",BuildSigByPlugin(intval($pidsid[0]),intval($pidsid[1]), $this->db))) . '" ' . $this->cs->GetClearCriteriaString($this->export_name);
            else $tmp = $tmp . Util::htmlentities($search, ENT_COMPAT, "UTF-8") . '"' . $this->cs->GetClearCriteriaString($this->export_name);
            $tmp = $tmp . '<BR>';
        }
        return $tmp;
    }
}; /* SignatureCriteria */
class SignatureClassificationCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT);
    }
    function PrintForm() {
        if ($this->db->baseGetDBversion() >= 103) {
            echo '<SELECT NAME="sig_class">
              <OPTION VALUE=" " ' . chk_select($this->criteria, " ") . '>' . gettext("{ any Classification }") . '
              <OPTION VALUE="null" ' . chk_select($this->criteria, "null") . '>-' . gettext("unclassified") . '-';
            $temp_sql = "SELECT sig_class_id, sig_class_name FROM sig_class";
            $tmp_result = $this->db->baseExecute($temp_sql);
            if ($tmp_result) {
                while ($myrow = $tmp_result->baseFetchRow()) echo '<OPTION VALUE="' . $myrow[0] . '" ' . chk_select($this->criteria, $myrow[0]) . '>' . $myrow[1];
                $tmp_result->baseFreeRows();
            }
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBversion() >= 103) {
            if ($this->criteria != " " && $this->criteria != "") {
                if ($this->criteria == "null") $tmp = $tmp . gettext("Signature Classification") . ' = ' . '<I>' . gettext("unclassified") . '</I><BR>';
                else $tmp = $tmp . gettext("Signature Classification") . ' = ' . Util::htmlentities(GetSigClassName($this->criteria, $this->db, ENT_COMPAT, "UTF-8")) . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
            }
        }
        return $tmp;
    }
}; /* SignatureClassificationCriteria */
class SignaturePriorityCriteria extends SingleElementCriteria {
    var $criteria = array();
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        if (!isset($this->criteria[0]) || !isset($this->criteria[1])) {
            $this->criteria = array(
                0 => '',
                1 => ''
            );
        }
        $this->criteria[0] = CleanVariable(@$this->criteria[0], "", array(
            "=",
            "!=",
            "<",
            "<=",
            ">",
            ">="
        ));
        $this->criteria[1] = CleanVariable(@$this->criteria[1], VAR_DIGIT);
    }
    function PrintForm() {
        if ($this->db->baseGetDBversion() >= 103) {
            if (!@is_array($this->criteria)) $this->criteria = array();
            echo '<SELECT NAME="sig_priority[0]">
                <OPTION VALUE=" " ' . @chk_select($this->criteria[0], "=") . '>__</OPTION>
                <OPTION VALUE="=" ' . @chk_select($this->criteria[0], "=") . '>==</OPTION>
                <OPTION VALUE="!=" ' . @chk_select($this->criteria[0], "!=") . '>!=</OPTION>
                <OPTION VALUE="<"  ' . @chk_select($this->criteria[0], "<") . '><</OPTION>
                <OPTION VALUE=">"  ' . @chk_select($this->criteria[0], ">") . '>></OPTION>
                <OPTION VALUE="<=" ' . @chk_select($this->criteria[0], "><=") . '><=</OPTION>
                <OPTION VALUE=">=" ' . @chk_select($this->criteria[0], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="sig_priority[1]">
                <OPTION VALUE="" ' . @chk_select($this->criteria[1], " ") . '>' . gettext("{ any Priority }") . '</OPTION>
 	        <OPTION VALUE="null" ' . @chk_select($this->criteria[1], "null") . '>-' . gettext("unclassified") . '-</OPTION>';
            $temp_sql = "select DISTINCT sig_priority from signature ORDER BY sig_priority ASC ";
            $tmp_result = $this->db->baseExecute($temp_sql);
            if ($tmp_result) {
                while ($myrow = $tmp_result->baseFetchRow()) echo '<OPTION VALUE="' . $myrow[0] . '" ' . chk_select(@$this->criteria[1], $myrow[0]) . '>' . $myrow[0];
                $tmp_result->baseFreeRows();
            }
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if (!isset($this->criteria[1])) {
            $this->criteria = array(
                0 => '',
                1 => ''
            );
        }
        if ($this->db->baseGetDBversion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . gettext("Signature Priority") . ' = ' . '<I>' . gettext("none") . '</I>';
                else $tmp = $tmp . gettext("Signature Priority") . ' ' . Util::htmlentities($this->criteria[0], ENT_COMPAT, "UTF-8") . " " . Util::htmlentities($this->criteria[1], ENT_COMPAT, "UTF-8");
            }
        }
        return $tmp;
    }
}; /* SignaturePriorityCriteria */
class AlertGroupCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT);
    }
    function PrintForm() {
        echo '<SELECT NAME="ag">
             <OPTION VALUE=" " ' . chk_select($this->criteria, " ") . '>' . gettext("{ any Event Group }");
        $temp_sql = "SELECT ag_id, ag_name FROM acid_ag";
        $tmp_result = $this->db->baseExecute($temp_sql);
        if ($tmp_result) {
            while ($myrow = $tmp_result->baseFetchRow()) echo '<OPTION VALUE="' . $myrow[0] . '" ' . chk_select($this->criteria, $myrow[0]) . '>' . '[' . $myrow[0] . '] ' . $myrow[1];
            $tmp_result->baseFreeRows();
        }
        echo '</SELECT>&nbsp;&nbsp;';
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . gettext("Event Group") . ' = [' . Util::htmlentities($this->criteria, ENT_COMPAT, "UTF-8") . '] ' . GetAGNameByID($this->criteria, $this->db) . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* AlertGroupCriteria */
class PluginCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT | VAR_PUNC);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . GetPluginName($this->criteria, $this->db);
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . _("Data Source") . ' = (' . GetPluginName($this->criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* PluginCriteria */
class SourceTypeCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . GetSourceType($this->criteria, $this->db);
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . _("Product Type") . ' = (' . GetSourceType($this->criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* SourceTypeCriteria */
class CategoryCriteria extends SingleElementCriteria {
	var $criteria = array();
    function Init() {
        $this->criteria = array(0,0);
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_DIGIT);
        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_DIGIT);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light()
    {
        $tmp = "";
        if ($this->criteria[0] != 0)
        {
            $tmp = $this->cs->GetClearCriteriaString2($this->export_name);
            if ($this->criteria[1] != 0)
            {
                $tmp .= GetPluginCategoryName($this->criteria[0], $this->db) . '/' . GetPluginSubCategoryName($this->criteria, $this->db);
            }
            else
            {
                $tmp .= GetPluginCategoryName($this->criteria[0], $this->db);
            }
        }
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ($this->criteria[0] != 0) {
        	if ($this->criteria[1] != 0)
        		$tmp .= _("Event Category/SubCategory") . ' = (' . GetPluginCategoryName($this->criteria[0], $this->db) . '/' . GetPluginSubCategoryName($this->criteria, $this->db) .')';
        	else
        		$tmp .= _("Event Category") . ' = (' . GetPluginCategoryName($this->criteria[0], $this->db) .')';
        	$tmp .= $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        }
        return $tmp;
    }
}; /* CategoryCriteria */
class ReputationCriteria extends SingleElementCriteria {
	var $criteria = array();
    function Init() {
        $this->criteria = array("","");
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_DIGIT);
        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_ALPHA | VAR_SPACE | VAR_PUNC);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light()
    {
        $tmp = "";
        if ( ($this->criteria[0] != " " && $this->criteria[0] != "") || ($this->criteria[1] != " " && $this->criteria[1] != "") )
        {
            $tmp .= $this->cs->GetClearCriteriaString2($this->export_name);
            if ($this->criteria[0] != " " && $this->criteria[0] != "")  $tmp .= GetActivityName($this->criteria[0], $this->db) .' - ';
            if ($this->criteria[1] != " " && $this->criteria[1] != "")  $tmp .= $this->criteria[1];
            $tmp = preg_replace("/\-\s*$/","",$tmp);
        }
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ( ($this->criteria[0] != " " && $this->criteria[0] != "") || ($this->criteria[1] != " " && $this->criteria[1] != "") ) {
        	$tmp .= _("Reputation").": ";
        	if ($this->criteria[0] != " " && $this->criteria[0] != "")  $tmp .= _("Activity") . ' = (' . GetActivityName($this->criteria[0], $this->db) .'), ';
        	if ($this->criteria[1] != " " && $this->criteria[1] != "")  $tmp .= _("Severity") . ' = (' . $this->criteria[1] .')';
        	$tmp = preg_replace("/\, $/","",$tmp) . $this->cs->GetClearCriteriaString($this->export_name) . "<br>";
        }
        return $tmp;
    }
}; /* ReputationCriteria */
class PluginGroupCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_ALPHA);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light()
    {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . GetPluginGroupName($this->criteria, $this->db);
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . _("DS Group") . ' = (' . GetPluginGroupName($this->criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* PluginGroupCriteria */
class NetworkGroupCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_ALPHA | VAR_SPACE | VAR_PUNC);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light()
    {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . GetNetworkGroupName($this->criteria, $this->db);
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . _("Network group") . ' = (' . GetNetworkGroupName($this->criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* NetworkGroupCriteria */
class HostCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = array("","");
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_ALPHA | VAR_COMA); // IDs
        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_ALPHA | VAR_PUNC); // Host by name
        $this->criteria[2] = CleanVariable($this->criteria[2], "", array("src", "dst", "both"));
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    function Description() {
        $tmp = "";
        $tmp1 = ($this->criteria[2] == "both") ? " " : " ".ucfirst($this->criteria[2])." ";
        if ($this->criteria[0] != "" && $this->criteria[1] != "") $tmp = $tmp . _("Host") . ' '.$tmp1.' = (' . $this->criteria[1] .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
    function Description_light() {
        $tmp = "";
        $tmp1 = ($this->criteria[2] == "both") ? " " : str_replace("src", "Source", str_replace("dst", "Destination", $this->criteria[2]))."=";
        if ($this->criteria[0] != "" && $this->criteria[1] != "") $tmp = $tmp . $tmp1 . $this->criteria[1];
        return $tmp;
    }
}; /* HostCriteria */
class NetCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = array("","");
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_ALPHA | VAR_PUNC); // IDs
        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_ALPHA | VAR_PUNC); // Net by name
        $this->criteria[2] = CleanVariable($this->criteria[2], "", array("src", "dst", "both"));
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    function Description() {
        $tmp = "";
        $tmp1 = ($this->criteria[2] == "both") ? " " : " ".ucfirst($this->criteria[2])." ";
        if ($this->criteria[0] != "" && $this->criteria[1] != "") $tmp = $tmp . _("Network") . ' '.$tmp1.' = (' . $this->criteria[1] .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* HostCriteria */
class CtxCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_ALPHA); // Ctx by uuid
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    function Description() {
        $tmp = "";
        //if (trim($this->criteria)!="") $tmp = $tmp . _("Context") . ' = (' . GetEntityName($this->criteria) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        if (trim($this->criteria)!="") $tmp = GetEntityName($this->criteria);
        return $tmp;
    }
}; /* CtxCriteria */
class DeviceCriteria extends SingleElementCriteria {
	function Init() {
		$this->criteria = "";
	}
	function Clear() {
	}
	function SanitizeElement() {
		$this->criteria = CleanVariable($this->criteria, VAR_DIGIT | VAR_PUNC);
		// Validate as IP
		if (!preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/", $this->criteria, $matches)) {
			$this->criteria = "";
		} else {
			if ($matches[1] > 255 || $matches[2] > 255 || $matches[3] > 255 || $matches[4] > 255) {
				$this->criteria = "";
			}
		}
	}
	function PrintForm() {
	}
	function ToSQL() {
	}
	function Description() {
		$tmp = "";
		if (trim($this->criteria)!="") $tmp = $tmp . _("Device") . ' = ' . $this->criteria . $this->cs->GetClearCriteriaString2($this->export_name) . '<BR>';
		return $tmp;
	}
}; /* DeviceCriteria */
class IDMUsernameCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = array("","");
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_ALPHA | VAR_SPACE | VAR_PUNC);
        $this->criteria[1] = CleanVariable($this->criteria[1], "", array("src", "dst", "both"));
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    function Description_light() {
        $tmp = "";
        if ($this->criteria[0] != " " && $this->criteria[0] != "") $tmp = $tmp . _("IDM")." "._("Username") . '=' . $this->criteria[0];
        return $tmp;
    }
    function Description() {
        $tmp = "";
        $tmp1 = ($this->criteria[1]!="both") ? " " : " ".ucfirst($this->criteria[1])." ";
        if ($this->criteria[0] != " " && $this->criteria[0] != "") $tmp = $tmp . _("IDM").$tmp1._("Username") . ' = (' . $this->criteria[0] .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* IDMUsernameCriteria */
class IDMHostnameCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = array("","");
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_ALPHA | VAR_SPACE | VAR_PUNC);
        $this->criteria[1] = CleanVariable($this->criteria[1], "", array("src", "dst", "both"));
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    function Description_light() {
        $tmp = "";
        if ($this->criteria[0] != " " && $this->criteria[0] != "") $tmp = $tmp . _("IDM")." "._("Hostname") . '=' . $this->criteria[0];
        return $tmp;
    }
    function Description() {
        $tmp = "";
        $tmp1 = ($this->criteria[1]!="both") ? " " : " ".ucfirst($this->criteria[1])." ";
        if ($this->criteria[0] != " " && $this->criteria[0] != "") $tmp = $tmp . _("IDM").$tmp1._("Hostname") . ' = (' . $this->criteria[0] .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* IDMHostnameCriteria */
class IDMDomainCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = array("","");
    }
    function Clear() {
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], VAR_ALPHA | VAR_SPACE | VAR_PUNC);
        $this->criteria[1] = CleanVariable($this->criteria[1], "", array("src", "dst", "both"));
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    function Description_light() {
        $tmp = "";
        if ($this->criteria[0] != " " && $this->criteria[0] != "") $tmp = $tmp . _("IDM")." "._("Domain") . '=' . $this->criteria[0];
        return $tmp;
    }
    function Description() {
        $tmp = "";
        $tmp1 = ($this->criteria[1]!="both") ? " " : " ".ucfirst($this->criteria[1])." ";
        if ($this->criteria[0] != " " && $this->criteria[0] != "") $tmp = $tmp . _("IDM").$tmp1._("Domain") . ' = (' . $this->criteria[0] .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* IDMDomainCriteria */
class UserDataCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = array("","","");
    }
    function Clear() {
    }
    function SanitizeElement() {
	    $this->criteria[0] = CleanVariable($this->criteria[0], "", array(
	            "userdata1","userdata2","userdata3","userdata4",
	            "userdata5","userdata6","userdata7","userdata8",
	            "userdata9","filename","username","password",
	        ));
	    $this->criteria[1] = CleanVariable($this->criteria[1], "", array("EQ","NE","LT","LOE","GT","GOE","like"));
	    if ($this->criteria[1]=="") $this->criteria[1] = "like";
        $this->criteria[2] = CleanVariable($this->criteria[2], VAR_ALPHA | VAR_SPACE | VAR_PUNC | VAR_AT);
    }
    function PrintForm() {
    }
    function ToSQL() {
    }
    
    function Description_light()
    {
        $tmp = "";
        $rpl = array('EQ'=>'=','NE'=>'!=','LT'=>'<','LOE'=>'<=','GT'=>'>','GOE'=>'>=');
        if ($this->criteria[2] != " " && $this->criteria[2] != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . $this->criteria[0] .' '. strtr($this->criteria[1],$rpl) . ' ' . $this->criteria[2];
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        $rpl = array('EQ'=>'=','NE'=>'!=','LT'=>'<','LOE'=>'<=','GT'=>'>','GOE'=>'>=');
        if ($this->criteria[2] != " " && $this->criteria[2] != "") $tmp = $tmp . _("Extra data") . ' = (' . $this->criteria[0] .' '. strtr($this->criteria[1],$rpl) . ' ' . $this->criteria[2] . ')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* UserDataCriteria */
class SensorCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
        $this->param = false;
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
    	$this->param = preg_match("/^\!/",$this->criteria) ? true : false;
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT | VAR_PUNC);
        if ( $this->criteria!="" && !preg_match("/(\d+,)*\d/",$this->criteria) )
        {
	        $this->criteria = "0";
        }
    }
    function PrintForm() {
        GLOBAL $db;
	    echo '<SELECT NAME="sensor" id="sensor">
             <OPTION VALUE="" ' . chk_select($this->criteria, " ") . '>' . gettext("{ any Sensor }");
        // Filter by user perms if no criteria
		$where_sensor = "";
		if (Session::allowedSensors() != "") {
			$user_sensors = explode(",",Session::allowedSensors());
			$snortsensors = GetSensorSids($db);
			$sensor_str = "";
			foreach ($user_sensors as $user_sensor)
				if (count($snortsensors[$user_sensor]) > 0) $sensor_str .= ($sensor_str != "") ? ",".implode(",",$snortsensors[$user_sensor]) : implode(",",$snortsensors[$user_sensor]);
			if ($sensor_str == "") $sensor_str = "0";
			$where_sensor = " AND d.id in (" . $sensor_str . ")";
		}
		$temp_sql = "SELECT d.id,s.name,s.ip FROM alienvault_siem.device d,alienvault.sensor s WHERE d.sensor_id=s.id $where_sensor";
		$tmp_result = $this->db->baseExecute($temp_sql);
        $varjs = "var sensortext = Array(); var sensorvalue = Array();\n";
        $sensor_sid_names = array();
		if ($tmp_result->row) {
            $i = 0;
            while ($myrow = $tmp_result->baseFetchRow()) {
                //$sname = GetSensorName($myrow["sid"], $this->db);
                $sname = $myrow["name"];
                $sensor_sid_names[$sname] .= (($sensor_sid_names[$sname] != "") ? "," : "").$myrow["id"];
            }
			foreach ($sensor_sid_names as $name=>$sids) {
				echo '<OPTION VALUE="' . $sids . '" ' . chk_select($this->criteria, $sids) . '>' . $name;
                $varjs.= "sensortext[$i] = '$name';\n";
                $varjs.= "sensorvalue[$i] = '" . $sids . "';\n";
				$i++;
			}
            $tmp_result->baseFreeRows();
        }
        echo '</SELECT><script>' . $varjs . ' var num_sensors=' . $i . ';</script>&nbsp;&nbsp;';
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    
    function Description_light() {
        $tmp = "";
        //if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . gettext("Sensor") . ' = [' . Util::htmlentities($this->criteria, ENT_COMPAT, "UTF-8") . '] (' . GetSensorName($this->criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        $criteria = ($this->param) ? substr($this->criteria,1) : $this->criteria;
        if ($criteria != " " && $criteria != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . ($this->param ? "<b>NOT</b> " : "") . GetSensorName($criteria, $this->db);
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        //if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . gettext("Sensor") . ' = [' . Util::htmlentities($this->criteria, ENT_COMPAT, "UTF-8") . '] (' . GetSensorName($this->criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        $criteria = ($this->param) ? substr($this->criteria,1) : $this->criteria;
		if ($criteria != " " && $criteria != "") $tmp = $tmp . gettext("Sensor") . ' = (' . ($this->param ? "<b>NOT</b> " : "") . GetSensorName($criteria, $this->db) .')'. $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* SensorCriteria */
class TimeCriteria extends MultipleElementCriteria {
    /*
    * $time[MAX][10]: stores the date/time of the packet detection
    *  - [][0] : (                           [][5] : hour
    *  - [][1] : =, !=, <, <=, >, >=         [][6] : minute
    *  - [][2] : month                       [][7] : second
    *  - [][3] : day                         [][8] : (, )
    *  - [][4] : year                        [][9] : AND, OR
    *
    * $time_cnt : number of rows in the $time[][] structure
    */
    function GetUTC() {
    	/* convert to UTC time for sql */
    	require_once("classes/Util.inc");
    	$tz = Util::get_timezone();
    	$this->Sanitize();
    	$utc_criteria = $this->criteria;
        for ($i = 0; $i < $this->criteria_cnt; $i++) if ($this->criteria[$i][4] != " " && $this->criteria[$i][4] != "") {
        	$y = $this->criteria[$i][4];
        	$m = ($this->criteria[$i][2] != " " && $this->criteria[$i][2] != "") ? $this->criteria[$i][2] : "01";
        	$d = ($this->criteria[$i][3] != " " && $this->criteria[$i][3] != "") ? $this->criteria[$i][3] : "01";
        	$h = ($this->criteria[$i][5] != " " && $this->criteria[$i][5] != "") ? $this->criteria[$i][5] : "00";
        	$u = ($this->criteria[$i][6] != " " && $this->criteria[$i][6] != "") ? $this->criteria[$i][6] : "00";
        	$s = ($this->criteria[$i][7] != " " && $this->criteria[$i][7] != "") ? $this->criteria[$i][7] : "00";
        	///$time = gmmktime($h,$u,$s,$m,$d,$y)+(3600*$tz);
        	//echo "$y-$m-$d $h:$u:$s =";
        	list ($y,$m,$d,$h,$u,$s,$time) = Util::get_utc_from_date($this->db,"$y-$m-$d $h:$u:$s",$tz);
        	//echo "$y-$m-$d $h:$u:$s == $time\n<br>";
        	$utc_criteria[$i][4] = $y;
        	$utc_criteria[$i][2] = $m;
        	$utc_criteria[$i][3] = $d;
        	$utc_criteria[$i][5] = $h;
        	$utc_criteria[$i][6] = $u;
        	$utc_criteria[$i][7] = $s;
        }
        return $utc_criteria;
    }
    function Clear() {
        $this->criteria = array();
        $this->criteria_cnt = 0;
        /* clears the criteria */
    }
    function SanitizeElement($i) {
        // Make copy of element array.
        $curArr = $this->criteria[$i];
        // Sanitize the element
        $this->criteria[$i][0] = @CleanVariable($curArr[0], VAR_OPAREN);
        $this->criteria[$i][1] = @CleanVariable($curArr[1], "", array(
            "=",
            "!=",
            "<",
            "<=",
            ">",
            ">="
        ));
        $this->criteria[$i][2] = @CleanVariable($curArr[2], VAR_DIGIT);
        $this->criteria[$i][3] = @CleanVariable($curArr[3], VAR_DIGIT);
        $this->criteria[$i][4] = @CleanVariable($curArr[4], VAR_DIGIT);
        $this->criteria[$i][5] = @CleanVariable($curArr[5], VAR_DIGIT);
        $this->criteria[$i][6] = @CleanVariable($curArr[6], VAR_DIGIT);
        $this->criteria[$i][7] = @CleanVariable($curArr[7], VAR_DIGIT);
        $this->criteria[$i][8] = @CleanVariable($curArr[8], VAR_OPAREN | VAR_CPAREN);
        $this->criteria[$i][9] = @CleanVariable($curArr[9], "", array(
            "AND",
            "OR"
        ));
        // Destroy the old copy
        unset($curArr);
    }
    function PrintForm() {
        // add default criteria => today
        if ($this->criteria_cnt == 0) {
            $this->criteria = array();
            $this->criteria[0] = array(
                " ",
                ">=",
                date("m") ,
                date("d") ,
                date("Y") ,
                "",
                "",
                "",
                " ",
                " "
            );
            //$this->criteria_cnt = 1;

        }
        $this->criteria_cnt = 2;
        for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if (!@is_array($this->criteria[$i])) $this->criteria = array();
            echo '<SELECT NAME="time[' . $i . '][0]"><OPTION VALUE=" " ' . chk_select(@$this->criteria[$i][0], " ") . '>__';
            echo '                               <OPTION VALUE="("  ' . chk_select(@$this->criteria[$i][0], "(") . '>(</SELECT>';
            echo '<SELECT NAME="time[' . $i . '][1]"><OPTION VALUE=" "  ' . chk_select(@$this->criteria[$i][1], " ") . '>' . gettext("{ time }");
            echo '                               <OPTION VALUE="="  ' . chk_select(@$this->criteria[$i][1], "=") . '>=';
            echo '                               <OPTION VALUE="!=" ' . chk_select(@$this->criteria[$i][1], "!=") . '>!=';
            echo '                               <OPTION VALUE="<"  ' . chk_select(@$this->criteria[$i][1], "<") . '><';
            echo '                               <OPTION VALUE="<=" ' . chk_select(@$this->criteria[$i][1], "<=") . '><=';
            echo '                               <OPTION VALUE=">"  ' . chk_select(@$this->criteria[$i][1], ">") . '>>';
            echo '                               <OPTION VALUE=">=" ' . chk_select(@$this->criteria[$i][1], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="time[' . $i . '][2]"><OPTION VALUE=" "  ' . chk_select(@$this->criteria[$i][2], " ") . '>' . gettext("{ month }");
            echo '                               <OPTION VALUE="01" ' . chk_select(@$this->criteria[$i][2], "01") . '>' . gettext("Jan");
            echo '                               <OPTION VALUE="02" ' . chk_select(@$this->criteria[$i][2], "02") . '>' . gettext("Feb");
            echo '                               <OPTION VALUE="03" ' . chk_select(@$this->criteria[$i][2], "03") . '>' . gettext("Mar");
            echo '                               <OPTION VALUE="04" ' . chk_select(@$this->criteria[$i][2], "04") . '>' . gettext("Apr");
            echo '                               <OPTION VALUE="05" ' . chk_select(@$this->criteria[$i][2], "05") . '>' . gettext("May");
            echo '                               <OPTION VALUE="06" ' . chk_select(@$this->criteria[$i][2], "06") . '>' . gettext("Jun");
            echo '                               <OPTION VALUE="07" ' . chk_select(@$this->criteria[$i][2], "07") . '>' . gettext("Jly");
            echo '                               <OPTION VALUE="08" ' . chk_select(@$this->criteria[$i][2], "08") . '>' . gettext("Aug");
            echo '                               <OPTION VALUE="09" ' . chk_select(@$this->criteria[$i][2], "09") . '>' . gettext("Sep");
            echo '                               <OPTION VALUE="10" ' . chk_select(@$this->criteria[$i][2], "10") . '>' . gettext("Oct");
            echo '                               <OPTION VALUE="11" ' . chk_select(@$this->criteria[$i][2], "11") . '>' . gettext("Nov");
            echo '                               <OPTION VALUE="12" ' . chk_select(@$this->criteria[$i][2], "12") . '>' . gettext("Dec") . '</SELECT>';
            echo '<INPUT TYPE="text" NAME="time[' . $i . '][3]" SIZE=2 VALUE="' . htmlspecialchars(@$this->criteria[$i][3]) . '">';
            echo '<SELECT NAME="time[' . $i . '][4]">' . dispYearOptions(@$this->criteria[$i][4]) . '</SELECT>';
            echo '<INPUT TYPE="text" NAME="time[' . $i . '][5]" SIZE=2 VALUE="' . htmlspecialchars(@$this->criteria[$i][5]) . '"><B>:</B>';
            echo '<INPUT TYPE="text" NAME="time[' . $i . '][6]" SIZE=2 VALUE="' . htmlspecialchars(@$this->criteria[$i][6]) . '"><B>:</B>';
            echo '<INPUT TYPE="text" NAME="time[' . $i . '][7]" SIZE=2 VALUE="' . htmlspecialchars(@$this->criteria[$i][7]) . '">';
            echo '<SELECT NAME="time[' . $i . '][8]"><OPTION VALUE=" " ' . chk_select(@$this->criteria[$i][8], " ") . '>__';
            echo '                               <OPTION VALUE="(" ' . chk_select(@$this->criteria[$i][8], "(") . '>(';
            echo '                               <OPTION VALUE=")" ' . chk_select(@$this->criteria[$i][8], ")") . '>)</SELECT>';
            if ($i == 0) {
                echo '<br><SELECT NAME="time[' . $i . '][9]">';
                echo '                               <OPTION VALUE="AND" ' . chk_select(@$this->criteria[$i][9], "AND") . '>' . gettext("AND");
                echo '                               <OPTION VALUE="OR" ' . chk_select(@$this->criteria[$i][9], "OR") . '>' . gettext("OR") . '</SELECT>';
            }
            echo '<BR>';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description_light()
    {
        $clear = $this->cs->GetClearCriteriaString2($this->export_name);
        
        if ($_SESSION['time_range'] == "month")
        {
            return $clear._("Last Month");
        }
        elseif ($_SESSION['time_range'] == "week")
        {
            return $clear._("Last Week");
        }
        elseif ($_SESSION['time_range'] == "day")
        {
            return $clear._("Last Day");
        }
        elseif ($_SESSION['time'])
        {
            $tmp = "";
            for ($i = 0; $i < $this->criteria_cnt; $i++) {
                if (isset($this->criteria[$i][1]) && $this->criteria[$i][1] != " ") {
                    $tmp = $tmp . '' . htmlspecialchars($this->criteria[$i][0]) . ' time ' . htmlspecialchars($this->criteria[$i][1]) . ' [ ';
                    /* date */
                    if ($this->criteria[$i][2] == " " && $this->criteria[$i][3] == "" && $this->criteria[$i][4] == " ") $tmp = $tmp . " <I>".gettext("any date")."</I>";
                    else $tmp = $tmp . (($this->criteria[$i][2] == " ") ? "* / " : $this->criteria[$i][2] . " / ") . (($this->criteria[$i][3] == "") ? "* / " : $this->criteria[$i][3] . " / ") . (($this->criteria[$i][4] == " ") ? "*  " : $this->criteria[$i][4] . " ");
                    $tmp = $tmp . '] [ ';
                    /* time */
                    if ($this->criteria[$i][5] == "" && $this->criteria[$i][6] == "" && $this->criteria[$i][7] == "") $tmp = $tmp . " <I>".gettext("any time")."</I>";
                    else $tmp = $tmp . (($this->criteria[$i][5] == "") ? "* : " : $this->criteria[$i][5] . " : ") . (($this->criteria[$i][6] == "") ? "* : " : $this->criteria[$i][6] . " : ") . (($this->criteria[$i][7] == "") ? "*  " : $this->criteria[$i][7] . " ");
                    $tmp = $tmp . $this->criteria[$i][8] . '] ' . $this->criteria[$i][9];
                    $tmp = $tmp;
                }
            }
            return $clear._("Date Range").":".$tmp;
        }
        else
        {
            return "";
        }
    }
    function Description() {
        $tmp = "";
        for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if (isset($this->criteria[$i][1]) && $this->criteria[$i][1] != " ") {
                $tmp = $tmp . '' . htmlspecialchars($this->criteria[$i][0]) . ' time ' . htmlspecialchars($this->criteria[$i][1]) . ' [ ';
                /* date */
                if ($this->criteria[$i][2] == " " && $this->criteria[$i][3] == "" && $this->criteria[$i][4] == " ") $tmp = $tmp . " <I>".gettext("any date")."</I>";
                else $tmp = $tmp . (($this->criteria[$i][2] == " ") ? "* / " : $this->criteria[$i][2] . " / ") . (($this->criteria[$i][3] == "") ? "* / " : $this->criteria[$i][3] . " / ") . (($this->criteria[$i][4] == " ") ? "*  " : $this->criteria[$i][4] . " ");
                $tmp = $tmp . '] [ ';
                /* time */
                if ($this->criteria[$i][5] == "" && $this->criteria[$i][6] == "" && $this->criteria[$i][7] == "") $tmp = $tmp . " <I>".gettext("any time")."</I>";
                else $tmp = $tmp . (($this->criteria[$i][5] == "") ? "* : " : $this->criteria[$i][5] . " : ") . (($this->criteria[$i][6] == "") ? "* : " : $this->criteria[$i][6] . " : ") . (($this->criteria[$i][7] == "") ? "*  " : $this->criteria[$i][7] . " ");
                $tmp = $tmp . $this->criteria[$i][8] . '] ' . $this->criteria[$i][9];
                $tmp = $tmp;
            }
        }
        if ($tmp != "") $tmp = $tmp . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
	function Description_full() {
        $tmp = "";
        if (isset($this->criteria[1][1]) && $this->criteria[1][1] != " ") {
        	$tmp = $tmp . '' . htmlspecialchars($this->criteria[0][0]) . ' Date of events between <b>';
            /* date */
        	$i = 0;
            if ($this->criteria[$i][2] == " " && $this->criteria[$i][3] == "" && $this->criteria[$i][4] == " ") $tmp = $tmp . " <I>".gettext("any date")."</I>";
            else $tmp = $tmp . (($this->criteria[$i][2] == " ") ? "* / " : $this->criteria[$i][2] . " / ") . (($this->criteria[$i][3] == "") ? "* / " : $this->criteria[$i][3] . " / ") . (($this->criteria[$i][4] == " ") ? "*  " : $this->criteria[$i][4] . " ");
            $tmp = $tmp . ' ';
            /* time */
            if ($this->criteria[$i][5] == "" && $this->criteria[$i][6] == "" && $this->criteria[$i][7] == "") $tmp = $tmp;
            else $tmp = $tmp . (($this->criteria[$i][5] == "") ? "* : " : $this->criteria[$i][5] . " : ") . (($this->criteria[$i][6] == "") ? "* : " : $this->criteria[$i][6] . " : ") . (($this->criteria[$i][7] == "") ? "*  " : $this->criteria[$i][7] . " ");
            $tmp = $tmp . $this->criteria[$i][8] . ' ' . $this->criteria[$i][9];
            $tmp = $tmp." ";
            /* date */
            $i = 1;
            if ($this->criteria[$i][2] == " " && $this->criteria[$i][3] == "" && $this->criteria[$i][4] == " ") $tmp = $tmp . " <I>".gettext("any date")."</I>";
            else $tmp = $tmp . (($this->criteria[$i][2] == " ") ? "* / " : $this->criteria[$i][2] . " / ") . (($this->criteria[$i][3] == "") ? "* / " : $this->criteria[$i][3] . " / ") . (($this->criteria[$i][4] == " ") ? "*  " : $this->criteria[$i][4] . " ");
            $tmp = $tmp . ' ';
            /* time */
            if ($this->criteria[$i][5] == "" && $this->criteria[$i][6] == "" && $this->criteria[$i][7] == "") $tmp = $tmp;
            else $tmp = $tmp . (($this->criteria[$i][5] == "") ? "* : " : $this->criteria[$i][5] . " : ") . (($this->criteria[$i][6] == "") ? "* : " : $this->criteria[$i][6] . " : ") . (($this->criteria[$i][7] == "") ? "*  " : $this->criteria[$i][7] . " ");
            $tmp = $tmp . $this->criteria[$i][8] . ' ' . $this->criteria[$i][9];
        } elseif (isset($this->criteria[0][1]) && $this->criteria[0][1] != " ") {
        	$i = 0;
        	$op = $this->criteria[$i][1];
            $op = str_replace(">=","after",$op);
            $op = str_replace("<=","before",$op);
            $tmp = $tmp . '' . htmlspecialchars($this->criteria[$i][0]) . ' Date of events ' . $op . ' <b>';
            /* date */
            if ($this->criteria[$i][2] == " " && $this->criteria[$i][3] == "" && $this->criteria[$i][4] == " ") $tmp = $tmp . " <I>".gettext("any date")."</I>";
            else $tmp = $tmp . (($this->criteria[$i][2] == " ") ? "* / " : $this->criteria[$i][2] . " / ") . (($this->criteria[$i][3] == "") ? "* / " : $this->criteria[$i][3] . " / ") . (($this->criteria[$i][4] == " ") ? "*  " : $this->criteria[$i][4] . " ");
            $tmp = $tmp . ' ';
            /* time */
            if ($this->criteria[$i][5] == "" && $this->criteria[$i][6] == "" && $this->criteria[$i][7] == "") $tmp = $tmp;
            else $tmp = $tmp . "(" . (($this->criteria[$i][5] == "") ? "* : " : $this->criteria[$i][5] . " : ") . (($this->criteria[$i][6] == "") ? "00 : " : $this->criteria[$i][6] . " : ") . (($this->criteria[$i][7] == "") ? "00  " : $this->criteria[$i][7] . " ")."h)";
            $tmp = $tmp . $this->criteria[$i][8] . ' ' . $this->criteria[$i][9];
            $tmp = $tmp."</b>";
        }
        if ($tmp != "") $tmp = $tmp . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* TimeCriteria */
class IPAddressCriteria extends MultipleElementCriteria {
    /*
    * $ip_addr[MAX][10]: stores an ip address parameters/operators row
    *  - [][0] : (                          [][5] : octet3 of address
    *  - [][1] : source, dest               [][6] : octet4 of address
    *  - [][2] : =, !=                      [][7] : network mask
    *  - [][3] : octet1 of address          [][8] : (, )
    *  - [][4] : octet2 of address          [][9] : AND, OR
    *
    * $ip_addr_cnt: number of rows in the $ip_addr[][] structure
    */
    function IPAddressCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::MultipleElementCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "ip_src" => gettext("Source"),
            "ip_dst" => gettext("Destination"),
            "ip_both" => _SORD
        ));
    }
    function Import() {
        parent::Import();
        include (dirname(__FILE__) . '/../base_conf.php');
        $vals = NULL;
        $empty = 1;
        /* expand IP into octets */
        $this->criteria = $_SESSION['ip_addr'];
		$this->criteria_cnt = $_SESSION['ip_addr_cnt'];

		for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if ((isset($this->criteria[$i][3])) && (ereg("([0-9]*)\.([0-9]*)\.([0-9]*)\.([0-9]*)", $this->criteria[$i][3]))) {
                // The code below is deprecated and is giving functionality errors
                // There's no need to filter here by allowed nets, the main query will do it
                /*
                if (($use_ossim_session) && (Session::allowedNets())) {
                    $domain = Session::allowedNets();
                    if ($domain != "") {
                        $tmp_myip = $this->criteria[$i][3];
                        $myip = strtok($tmp_myip, "/");
                        if (Asset_host::is_ip_in_nets($myip, $domain)) {
                            $tmp_ip_str = $this->criteria[$i][7] = $this->criteria[$i][3];
                            $this->criteria[$i][2] = "=";
                            $this->criteria[$i][3] = strtok($tmp_ip_str, ".");
                            $this->criteria[$i][4] = strtok(".");
                            $this->criteria[$i][5] = strtok(".");
                            $this->criteria[$i][6] = strtok("/");
                            $this->criteria[$i][10] = strtok("");
                            $empty = 0;
                            $vals[] = $this->criteria[$i];
                        }
                    }
                } else {
                */
                    $tmp_ip_str = $this->criteria[$i][7] = $this->criteria[$i][3];
                    $this->criteria[$i][3] = strtok($tmp_ip_str, ".");
                    $this->criteria[$i][4] = strtok(".");
                    $this->criteria[$i][5] = strtok(".");
                    $this->criteria[$i][6] = strtok("/");
                    $this->criteria[$i][10] = strtok("");
                    $empty = 0;
                    $vals[] = $this->criteria[$i];
                //}
            } elseif (is_array($this->criteria[$i]) && array_key_exists(7, $this->criteria[$i]) && ereg("([0-9]*)\.([0-9]*)\.([0-9]*)\.([0-9]*)", $this->criteria[$i][7])) {
                $empty = 0;
                $vals[] = $this->criteria[$i];
            }
        }
        //print_r ($this->criteria);
        $this->criteria = $vals;
        $this->criteria_cnt = count($vals);
        /*if (($use_ossim_session) && ($empty)) {
            $domain = Session::allowedNets();
            if ($domain != "") {
                $nets = explode(",", $domain);
                $this->criteria = Array();
                for ($i = 0; $i < count($nets); $i++) {
                    $tmp_ip_str = $tmp[7] = $nets[$i];
                    $tmp[0] = " ";
                    $tmp[1] = "ip_both";
                    $tmp[2] = "=";
                    $tmp[3] = strtok($tmp_ip_str, ".");
                    $tmp[4] = strtok(".");
                    $tmp[5] = strtok(".");
                    $tmp[6] = strtok("/");
                    $tmp[10] = strtok("");
                    $tmp[8] = " ";
                    if ($i == (count($nets) - 1)) $tmp[9] = " ";
                    else $tmp[9] = "OR";
                    $this->criteria[$this->criteria_cnt] = $tmp;
                    $this->criteria_cnt++;
                }
            }
        }*/
        $new = ImportHTTPVar("new", VAR_DIGIT);
        $submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE);
        if (($new == 1) && ($submit == "")) {
        	// This is commented.
        	// When you return to the search form, you must preserve all criteria. Lately only was reseting the _cnt vars
        	// Now doesn't reset anything
            //$this->criteria = NULL;
            //$this->criteria_cnt = 1;
        }
        if ($this->criteria_cnt == "") {
            $this->criteria_cnt = 1;
        }
        //print_r ($this->criteria);
        $_SESSION['ip_addr'] = & $this->criteria;
        $_SESSION['ip_addr_cnt'] = & $this->criteria_cnt;
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement($i) {
        // Make copy of old element array
        $curArr = $this->criteria[$i];
        // Sanitize element
        $this->criteria[$i][0] = @CleanVariable($curArr[0], VAR_OPAREN);
        $this->criteria[$i][1] = @CleanVariable($curArr[1], "", array_keys($this->valid_field_list));
        $this->criteria[$i][2] = @CleanVariable($curArr[2], "", array(
            "=",
            "!=",
            "<",
            "<=",
            ">",
            ">="
        ));
        if ($this->criteria[$i][2]=="")
        {
        	$this->criteria[$i][2]="=";
        }
        $this->criteria[$i][3] = @CleanVariable($curArr[3], VAR_DIGIT);
        $this->criteria[$i][4] = @CleanVariable($curArr[4], VAR_DIGIT);
        $this->criteria[$i][5] = @CleanVariable($curArr[5], VAR_DIGIT);
        $this->criteria[$i][6] = @CleanVariable($curArr[6], VAR_DIGIT);
        $this->criteria[$i][7] = @CleanVariable($curArr[7], VAR_DIGIT | VAR_PERIOD | VAR_FSLASH);
        $this->criteria[$i][8] = @CleanVariable($curArr[8], VAR_OPAREN | VAR_CPAREN);
        $this->criteria[$i][9] = @CleanVariable($curArr[9], "", array(
            "AND",
            "OR"
        ));
        $this->criteria[$i][10] = @CleanVariable($curArr[10], VAR_DIGIT);
        // Destroy copy
        unset($curArr);
    }
    function PrintForm() {
		//print_r(@$this->criteria);
		for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if (!is_array(@$this->criteria[$i])) $this->criteria = array();
            echo '    <SELECT NAME="ip_addr[' . $i . '][0]"><OPTION VALUE=" " ' . chk_select(@$this->criteria[$i][0], " ") . '>__';
            echo '                                      <OPTION VALUE="(" ' . chk_select(@$this->criteria[$i][0], "(") . '>(</SELECT>';
            echo '    <SELECT NAME="ip_addr[' . $i . '][1]">
                    <OPTION VALUE=" "      ' . chk_select(@$this->criteria[$i][1], " ") . '>' . gettext("{ address }") . '
                    <OPTION VALUE="ip_src" ' . chk_select(@$this->criteria[$i][1], "ip_src") . '>' . gettext("Source") . '
                    <OPTION VALUE="ip_dst" ' . chk_select(@$this->criteria[$i][1], "ip_dst") . '>' . gettext("Dest") . '
                    <OPTION VALUE="ip_both" ' . chk_select(@$this->criteria[$i][1], "ip_both") . '>' . gettext("Src or Dest") . '
                   </SELECT>';
            echo '    <SELECT NAME="ip_addr[' . $i . '][2]">
                    <OPTION VALUE="="  ' . chk_select(@$this->criteria[$i][2], "=") . '>=
                    <OPTION VALUE="!=" ' . chk_select(@$this->criteria[$i][2], "!=") . '>!=
                   </SELECT>';
            if ($GLOBALS['ip_address_input'] == 2) echo '    <INPUT TYPE="text" NAME="ip_addr[' . $i . '][3]" SIZE=16 VALUE="' . htmlspecialchars(@$this->criteria[$i][7]) . '">';
            else {
                echo '    <INPUT TYPE="text" NAME="ip_addr[' . $i . '][3]" SIZE=3 VALUE="' . htmlspecialchars(@$this->criteria[$i][3]) . '"><B>.</B>';
                echo '    <INPUT TYPE="text" NAME="ip_addr[' . $i . '][4]" SIZE=3 VALUE="' . htmlspecialchars(@$this->criteria[$i][4]) . '"><B>.</B>';
                echo '    <INPUT TYPE="text" NAME="ip_addr[' . $i . '][5]" SIZE=3 VALUE="' . htmlspecialchars(@$this->criteria[$i][5]) . '"><B>.</B>';
                echo '    <INPUT TYPE="text" NAME="ip_addr[' . $i . '][6]" SIZE=3 VALUE="' . htmlspecialchars(@$this->criteria[$i][6]) . '"><!--<B>/</B>';
                echo '    <INPUT TYPE="text" NAME="ip_addr[' . $i . '][7]" SIZE=3 VALUE="' . htmlspecialchars(@$this->criteria[$i][7]) . '">-->';
            }
            echo '    <SELECT NAME="ip_addr[' . $i . '][8]"><OPTION VALUE=" " ' . chk_select(@$this->criteria[$i][8], " ") . '>__';
            echo '                                      <OPTION VALUE="(" ' . chk_select(@$this->criteria[$i][8], "(") . '>(';
            echo '                                      <OPTION VALUE=")" ' . chk_select(@$this->criteria[$i][8], ")") . '>)</SELECT>';
            if ($i < $this->criteria_cnt-1) {
				echo '    <SELECT NAME="ip_addr[' . $i . '][9]"><OPTION VALUE=" "   ' . chk_select(@$this->criteria[$i][9], " ") . '>__';
				echo '                                      <OPTION VALUE="OR" ' . chk_select(@$this->criteria[$i][9], "OR") . '>' . gettext("OR");
				echo '                                      <OPTION VALUE="AND" ' . chk_select(@$this->criteria[$i][9], "AND") .'>' . gettext("AND") . '</SELECT>';
			}
            if ($i == $this->criteria_cnt - 1) echo '    <INPUT TYPE="submit" class="button av_b_secondary" onclick="adv_search_autosubmit()" NAME="submit" VALUE="' . gettext("ADD Addr") . '">';
            echo '<BR>';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
	function PrintElement($i,$clear=true) {
		$human_fields["ip_src"] = gettext("Source");
		$human_fields["ip_dst"] = gettext("Destination");
		$human_fields["ip_both"] = gettext("Src or Dest");
		$human_fields[""] = "";
		$human_fields["LIKE"] = gettext("contains");
		$human_fields["="] = "=";
		$tmp = "";
		if (isset($this->criteria[$i][3]) && $this->criteria[$i][3] != "") {
			$tmp = $tmp . $this->criteria[$i][3];
			if ($this->criteria[$i][4] != "") {
				$tmp = $tmp . "." . $this->criteria[$i][4];
				if ($this->criteria[$i][5] != "") {
					$tmp = $tmp . "." . $this->criteria[$i][5];
					if ($this->criteria[$i][6] != "") {
						if (($this->criteria[$i][3] . "." . $this->criteria[$i][4] . "." . $this->criteria[$i][5] . "." . $this->criteria[$i][6]) == NULL_IP) $tmp = " unknown ";
						else $tmp = $tmp . "." . $this->criteria[$i][6];
					} else $tmp = $tmp . '.*';
				} else $tmp = $tmp . '.*.*';
			} else $tmp = $tmp . '.*.*.*';
		}
		/* Make sure that the IP isn't blank */
		if ($tmp != "") {
			$mask = "";
			if ($this->criteria[$i][10] != "") $mask = "/" . $this->criteria[$i][10];
			$tmp = $this->criteria[$i][0] . $human_fields[($this->criteria[$i][1]) ] . $this->criteria[$i][2] . $tmp . $mask . ' ' . $this->criteria[$i][8] . ' ' . $this->criteria[$i][9]  . ($clear==true ? $this->cs->GetClearCriteriaString($this->export_name) : "") . "<BR>";
		}
        return $tmp;
    }
    
    function PrintElement2($i,$clear=true) {
        $human_fields["ip_src"] = gettext("Source");
        $human_fields["ip_dst"] = gettext("Destination");
        $human_fields["ip_both"] = gettext("Src or Dest");
        $human_fields[""] = "";
        $human_fields["LIKE"] = gettext("contains");
        $human_fields["="] = "=";
        $tmp = "";
        if (isset($this->criteria[$i][3]) && $this->criteria[$i][3] != "") {
            $tmp = $tmp . $this->criteria[$i][3];
            if ($this->criteria[$i][4] != "") {
                $tmp = $tmp . "." . $this->criteria[$i][4];
                if ($this->criteria[$i][5] != "") {
                    $tmp = $tmp . "." . $this->criteria[$i][5];
                    if ($this->criteria[$i][6] != "") {
                        if (($this->criteria[$i][3] . "." . $this->criteria[$i][4] . "." . $this->criteria[$i][5] . "." . $this->criteria[$i][6]) == NULL_IP) $tmp = " unknown ";
                        else $tmp = $tmp . "." . $this->criteria[$i][6];
                    } else $tmp = $tmp . '.*';
                } else $tmp = $tmp . '.*.*';
            } else $tmp = $tmp . '.*.*.*';
        }
        /* Make sure that the IP isn't blank */
        if ($tmp != "") {
            $mask = "";
            if ($this->criteria[$i][10] != "") $mask = "/" . $this->criteria[$i][10];
            $tmp = ($clear==true ? $this->cs->GetClearCriteriaString2($this->export_name) : "") . $this->criteria[$i][0] . $human_fields[($this->criteria[$i][1])] . $this->criteria[$i][2] . $tmp . $mask . ' ' . $this->criteria[$i][8] . ' ' . $this->criteria[$i][9]. ' ';
        }
        return $tmp;
    }
    function Description_light()
    {
        $tmp2 = "";
        if ($this->criteria_cnt > 0)
        {
            $tmp2 = $this->PrintElement2(0, TRUE);
            if ($this->criteria_cnt > 2)
            {
                $tmp2 .= " <font class='grisclaro'>[ ".($this->criteria_cnt - 2)." more ... ]</font>";
            }
            else
            {
                $tmp2 .= $this->PrintElement2(1, FALSE);
            }
        }
        return $tmp2;
    }
    
    function Description() {
		$tmp2 = "";
		if ($this->criteria_cnt > 0) {
			$tmp2 = $this->PrintElement(0,($this->criteria_cnt>1 ? false : true));
			if ($this->criteria_cnt > 2)
				$tmp2 .= "<font class='grisclaro'>[ ".($this->criteria_cnt-2)." more ... ]</font><br>".$this->PrintElement($this->criteria_cnt-1);
			else
				$tmp2 .= $this->PrintElement(1);
		}
        return $tmp2;
    }
	function Description_full() {
		$tmp2 = "";
		if ($this->criteria_cnt > 0) {
			for ($i = 0; $i < $this->criteria_cnt; $i++) {
				$tmp2 .= $this->PrintElement($i,(($i < $this->criteria_cnt-1) ? false : true));
			}
		}
        return $tmp2;
    }
}; /* IPAddressCriteria */
class IPFieldCriteria extends ProtocolFieldCriteria {
    /*
    * $ip_field[MAX][6]: stores all other ip fields parameters/operators row
    *  - [][0] : (                            [][3] : field value
    *  - [][1] : TOS, TTL, ID, offset, length [][4] : (, )
    *  - [][2] : =, !=, <, <=, >, >=          [][5] : AND, OR
    *
    * $ip_field_cnt: number of rows in the $ip_field[][] structure
    */
    function IPFieldCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::ProtocolFieldCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "ip_tos" => "TOS",
            "ip_ttl" => "TTL",
            "ip_id" => "ID",
            "ip_off" => "offset",
            "ip_csum" => "chksum",
            "ip_len" => "length"
        ));
    }
    function PrintForm() {
        parent::PrintForm($this->valid_field_list, gettext("{ field }"), gettext("ADD IP Field"));
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        return parent::Description(array_merge(array(
            "" => "",
            "LIKE" => gettext("contains"),
            "=" => "="
        ) , $this->valid_field_list));
    }
};
class TCPPortCriteria extends ProtocolFieldCriteria {
    /*
    * $tcp_port[MAX][6]: stores all port parameters/operators row
    *  - [][0] : (                            [][3] : port value
    *  - [][1] : Source Port, Dest Port       [][4] : (, )
    *  - [][2] : =, !=, <, <=, >, >=          [][5] : AND, OR
    *
    * $tcp_port_cnt: number of rows in the $tcp_port[][] structure
    */
    function TCPPortCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::ProtocolFieldCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "layer4_sport" => gettext("source port"),
            "layer4_dport" => _DESTPORT
        ));
    }
    function PrintForm() {
        parent::PrintForm($this->valid_field_list, gettext("{ port }"), gettext("ADD TCP Port"));
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        return parent::Description(array_merge(array(
            "" => "",
            "=" => "="
        ) , $this->valid_field_list));
    }
}; /* TCPPortCriteria */
class TCPFieldCriteria extends ProtocolFieldCriteria {
    /*
    * TCP Variables
    * =============
    * $tcp_field[MAX][6]: stores all other tcp fields parameters/operators row
    *  - [][0] : (                            [][3] : field value
    *  - [][1] : windows, URP                 [][4] : (, )
    *  - [][2] : =, !=, <, <=, >, >=          [][5] : AND, OR
    *
    * $tcp_field_cnt: number of rows in the $tcp_field[][] structure
    */
    function TCPFieldCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::ProtocolFieldCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "tcp_win" => "window",
            "tcp_urp" => "urp",
            "tcp_seq" => "seq #",
            "tcp_ack" => "ack",
            "tcp_off" => "offset",
            "tcp_res" => "res",
            "tcp_csum" => "chksum"
        ));
    }
    function PrintForm() {
        parent::PrintForm($this->valid_field_list, gettext("{ field }"), gettext("ADD TCP Field"));
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        return parent::Description(array_merge(array(
            "" => ""
        ) , $this->valid_field_list));
    }
}; /* TCPFieldCriteria */
class TCPFlagsCriteria extends SingleElementCriteria {
    /*
    * $tcp_flags[7]: stores all other tcp flags parameters/operators row
    *  - [0] : is, contains                   [4] : 8     (RST)
    *  - [1] : 1   (FIN)                      [5] : 16    (ACK)
    *  - [2] : 2   (SYN)                      [6] : 32    (URG)
    *  - [3] : 4   (PUSH)
    */
    function Init() {
        InitArray($this->criteria, $GLOBALS['MAX_ROWS'], TCPFLAGS_CFCNT, "");
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT);
    }
    function PrintForm() {
        if (!is_array($this->criteria[0])) $this->criteria = array();
        echo '<TD><SELECT NAME="tcp_flags[0]"><OPTION VALUE=" " ' . chk_select($this->criteria[0], " ") . '>' . gettext("{ flags }");
        echo '                              <OPTION VALUE="is" ' . chk_select($this->criteria[0], "is") . '>' . gettext("is");
        echo '                              <OPTION VALUE="contains" ' . chk_select($this->criteria[0], "contains") . '>' . gettext("contains") . '</SELECT>';
        echo '   <FONT>';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[8]" VALUE="128" ' . chk_check($this->criteria[8], "128") . '> [RSV1] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[7]" VALUE="64"  ' . chk_check($this->criteria[7], "64") . '> [RSV0] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[6]" VALUE="32"  ' . chk_check($this->criteria[6], "32") . '> [URG] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[5]" VALUE="16"  ' . chk_check($this->criteria[5], "16") . '> [ACK] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[3]" VALUE="8"   ' . chk_check($this->criteria[4], "8") . '> [PSH] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[4]" VALUE="4"   ' . chk_check($this->criteria[3], "4") . '> [RST] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[2]" VALUE="2"   ' . chk_check($this->criteria[2], "2") . '> [SYN] &nbsp';
        echo '    <INPUT TYPE="checkbox" NAME="tcp_flags[1]" VALUE="1"   ' . chk_check($this->criteria[1], "1") . '> [FIN] &nbsp';
        echo '  </FONT>';
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $human_fields["1"] = "F";
        $human_fields["2"] = "S";
        $human_fields["4"] = "R";
        $human_fields["8"] = "P";
        $human_fields["16"] = "A";
        $human_fields["32"] = "U";
        $human_fields["64"] = "[R0]";
        $human_fields["128"] = "[R1]";
        $human_fields["LIKE"] = gettext("contains");
        $human_fields["="] = "=";
        $tmp = "";
        if (isset($this->criteria[0]) && ($this->criteria[0] != " ") && ($this->criteria[0] != "")) {
            $tmp = $tmp . 'flags ' . $this->criteria[0] . ' ';
            for ($i = 8; $i >= 1; $i--) {
				if ($this->criteria[$i] == "") $tmp = $tmp . '-';
				elseif(!is_array($this->criteria[$i])) $tmp = $tmp . $human_fields[$this->criteria[$i]];
			}
            $tmp = $tmp . $this->cs->GetClearCriteriaString("tcp_flags") . '<BR>';
        }
        return $tmp;
    }
    function isEmpty() {
        if (count($this->criteria) > 0 && ($this->criteria[0] != "") && ($this->criteria[0] != " ")) return false;
        else return true;
    }
}; /* TCPFlagCriteria */
class UDPPortCriteria extends ProtocolFieldCriteria {
    /*
    * $udp_port[MAX][6]: stores all port parameters/operators row
    *  - [][0] : (                            [][3] : port value
    *  - [][1] : Source Port, Dest Port       [][4] : (, )
    *  - [][2] : =, !=, <, <=, >, >=          [][5] : AND, OR
    *
    * $udp_port_cnt: number of rows in the $udp_port[][] structure
    */
    function UDPPortCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::ProtocolFieldCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "layer4_sport" => gettext("source port"),
            "layer4_dport" => _DESTPORT
        ));
    }
    function PrintForm() {
        parent::PrintForm($this->valid_field_list, gettext("{ port }"), gettext("ADD UDP Port"));
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        return parent::Description(array_merge(array(
            "" => "",
            "=" => "="
        ) , $this->valid_field_list));
    }
}; /* UDPPortCriteria */
class UDPFieldCriteria extends ProtocolFieldCriteria {
    /*
    * $udp_field[MAX][6]: stores all other udp fields parameters/operators row
    *  - [][0] : (                            [][3] : field value
    *  - [][1] : length                       [][4] : (, )
    *  - [][2] : =, !=, <, <=, >, >=          [][5] : AND, OR
    *
    * $udp_field_cnt: number of rows in the $udp_field[][] structure
    */
    function UDPFieldCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::ProtocolFieldCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "udp_len" => "length",
            "udp_csum" => "chksum"
        ));
    }
    function PrintForm() {
        parent::PrintForm($this->valid_field_list, gettext("{ field }"), gettext("ADD UDP Field"));
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        return parent::Description(array_merge(array(
            "" => ""
        ) , $this->valid_field_list));
    }
}; /* UDPFieldCriteria */
class ICMPFieldCriteria extends ProtocolFieldCriteria {
    /*
    * $icmp_field[MAX][6]: stores all other icmp fields parameters/operators row
    *  - [][0] : (                            [][3] : field value
    *  - [][1] : code, length                 [][4] : (, )
    *  - [][2] : =, !=, <, <=, >, >=          [][5] : AND, OR
    *
    * $icmp_field_cnt: number of rows in the $icmp_field[][] structure
    */
    function ICMPFieldCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::ProtocolFieldCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "icmp_type" => "type",
            "icmp_code" => "code",
            "icmp_id" => "id",
            "icmp_seq" => "seq #",
            "icmp_csum" => "chksum"
        ));
    }
    function PrintForm() {
        parent::PrintForm($this->valid_field_list, gettext("{ field }"), gettext("ADD ICMP Field"));
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        return parent::Description(array_merge(array(
            "" => ""
        ) , $this->valid_field_list));
    }
}; /* ICMPFieldCriteria */
class Layer4Criteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, "", array(
            "UDP",
            "TCP",
            "ICMP",
            "RawIP"
        ));
    }
    function PrintForm() {
        if ($this->criteria != "") echo '<INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="' . gettext("no layer4") . '"> &nbsp';
        if ($this->criteria == "TCP") echo '
           <INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="UDP">';/* &nbsp
           <INPUT TYPE="submit" class="button" NAME="submit" VALUE="ICMP">';*/
        else if ($this->criteria == "UDP") echo '
           <INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="TCP">';/* &nbsp
           <INPUT TYPE="submit" class="button" NAME="submit" VALUE="ICMP">';*/
        /*
        else if ($this->criteria == "ICMP") echo '
           <INPUT TYPE="submit" class="button" NAME="submit" VALUE="TCP"> &nbsp
           <INPUT TYPE="submit" class="button" NAME="submit" VALUE="UDP">';
        */
        else echo '
           <INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="TCP"> &nbsp
           <INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="UDP">';/*
           <INPUT TYPE="submit" class="button" NAME="submit" VALUE="ICMP">';*/
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        if ($this->criteria == "TCP") return gettext("TCP Criteria");
        else if ($this->criteria == "UDP") return gettext("UDP Criteria");
        else if ($this->criteria == "ICMP") return gettext("ICMP Criteria");
        else return gettext("Layer 4 Criteria");
    }
}; /* Layer4Criteria */
class DataCriteria extends MultipleElementCriteria {
    /*
    * $data_encode[2]: how the payload should be interpreted and converted
    *  - [0] : encoding type (hex, ascii)
    *  - [1] : conversion type (hex, ascii)
    *
    * $data[MAX][5]: stores all the payload related parameters/operators row
    *  - [][0] : (                            [][3] : (, )
    *  - [][1] : =, !=                        [][4] : AND, OR
    *  - [][2] : field value
    *
    * $data_cnt: number of rows in the $data[][] structure
    */
    var $data_encode;
    function DataCriteria(&$db, &$cs, $export_name, $element_cnt) {
        $tdb = & $db;
        $cs = & $cs;
        parent::MultipleElementCriteria($tdb, $cs, $export_name, $element_cnt, array(
            "LIKE" => gettext("has"),
            "NOT LIKE" => _HASNOT
        ));
        $this->data_encode = array();
    }
    function Init() {
        parent::Init();
        InitArray($this->data_encode, 2, 0, "");
    }
    function Import() {
        parent::Import();
        // Secure assignment to data_encode
        $data_encode_aux = SetSessionVar("data_encode");
        if (is_array($data_encode_aux)) {
	        for ($i = 0; $i < count($data_encode_aux); $i++) {
	        	$this->data_encode[$i] = Util::htmlentities($data_encode_aux[$i]);
	        }
        } else {
        	$this->data_encode = Util::htmlentities($data_encode_aux);
        }
        $_SESSION['data_encode'] = & $this->data_encode;
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement($i) {
        $this->data_encode[0] = CleanVariable($this->data_encode[0], "", array(
            "hex",
            "ascii"
        ));
        $this->data_encode[1] = CleanVariable($this->data_encode[1], "", array(
            "hex",
            "ascii"
        ));
        // Make a copy of the element array
        $curArr = $this->criteria[$i];
        // Sanitize the array

        $curArr[2] = str_replace("\"",'$$$$',$curArr[2]);

        $this->criteria[$i][0] = CleanVariable($curArr[0], VAR_OPAREN);
        $this->criteria[$i][1] = CleanVariable($curArr[1], "", array_keys($this->valid_field_list));
        $this->criteria[$i][2] = CleanVariable($curArr[2], VAR_FSLASH | VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER | VAR_AT);
        $this->criteria[$i][3] = CleanVariable($curArr[3], VAR_OPAREN | VAR_CPAREN);
        $this->criteria[$i][4] = CleanVariable($curArr[4], "", array(
            "AND",
            "OR"
        ));

        $this->criteria[$i][2] = str_replace('$$$$','"',$this->criteria[$i][2]);

        // Destroy the copy
        unset($curArr);
    }
    function PrintForm() {
        if (!is_array(@$this->criteria[0])) $this->criteria = array();
        if ($this->criteria_cnt < 1) $this->criteria_cnt = 1;
        echo '<B>' . gettext("Input Criteria Encoding Type") . ':</B>';
        echo '<SELECT NAME="data_encode[0]"><OPTION VALUE=" "    ' . @chk_select($this->data_encode[0], " ") . '>' . gettext("{ encoding }");
        echo '                              <OPTION VALUE="hex"  ' . @chk_select($this->data_encode[0], "hex") . '>hex';
        echo '                              <OPTION VALUE="ascii"' . @chk_select($this->data_encode[0], "ascii") . '>ascii</SELECT>';
        echo '<B>' . gettext("Convert To (when searching)") . ':</B>';
        echo '<SELECT NAME="data_encode[1]"><OPTION VALUE=" "    ' . @chk_select(@$this->data_encode[1], " ") . '>' . gettext("{ Convert To }");
        echo '                              <OPTION VALUE="hex"  ' . @chk_select(@$this->data_encode[1], "hex") . '>hex';
        echo '                              <OPTION VALUE="ascii"' . @chk_select(@$this->data_encode[1], "ascii") . '>ascii</SELECT>';
        echo '<BR>';
        for ($i = 0; $i < $this->criteria_cnt; $i++) {
            echo '<SELECT NAME="data[' . $i . '][0]"><OPTION VALUE=" " ' . chk_select(@$this->criteria[$i][0], " ") . '>__';
            echo '                               <OPTION VALUE="("  ' . chk_select(@$this->criteria[$i][0], "(") . '>(</SELECT>';
            echo '<SELECT NAME="data[' . $i . '][1]"><OPTION VALUE=" "  ' . chk_select(@$this->criteria[$i][1], " ") . '>' . gettext("{ payload }");
            echo '                               <OPTION VALUE="LIKE"     ' . chk_select(@$this->criteria[$i][1], "LIKE") . '>' . gettext("has");
            echo '                               <OPTION VALUE="NOT LIKE" ' . chk_select(@$this->criteria[$i][1], "NOT LIKE") . '>' . gettext("has not") . '</SELECT>';
            echo '<INPUT TYPE="text" NAME="data[' . $i . '][2]" SIZE=45 VALUE="' . htmlspecialchars(@$this->criteria[$i][2]) . '">';
            echo '<SELECT NAME="data[' . $i . '][3]"><OPTION VALUE=" " ' . chk_select(@$this->criteria[$i][3], " ") . '>__';
            echo '                               <OPTION VALUE="(" ' . chk_select(@$this->criteria[$i][3], "(") . '>(';
            echo '                               <OPTION VALUE=")" ' . chk_select(@$this->criteria[$i][3], ")") . '>)</SELECT>';
            echo '<SELECT NAME="data[' . $i . '][4]"><OPTION VALUE=" "   ' . chk_select(@$this->criteria[$i][4], " ") . '>__';
            echo '                               <OPTION VALUE="OR" ' . chk_select(@$this->criteria[$i][4], "OR") . '>' . gettext("OR");
            echo '                               <OPTION VALUE="AND" ' . chk_select(@$this->criteria[$i][4], "AND") . '>' . gettext("AND") . '</SELECT>';
            if ($i == $this->criteria_cnt - 1) echo '    <INPUT TYPE="submit" class="button av_b_secondary" NAME="submit" onclick="adv_search_autosubmit()" VALUE="' . gettext("ADD Payload") . '">';
            echo '<BR>';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    
    function Description_light()
    {
        $human_fields["LIKE"] = gettext("contains");
        $human_fields["NOT LIKE"] = gettext("does not contain");
        $human_fields[""] = "";
        $tmp = "";
        if ($this->data_encode[0] != " " && $this->data_encode[1] != " ")
        {
            $tmp = $tmp . ' (' . gettext("data encoded as") . ' ' . $this->data_encode[0];
            $tmp = $tmp . ' => ' . (($this->data_encode[1] == "") ? "ascii" : $this->data_encode[1]);
            $tmp = $tmp . ')<BR>';
        }
        else
        {
        $tmp = $tmp . ' ' . gettext("(no data conversion, assuming criteria in DB native encoding)") . '<BR>';
        }
        for ($i = 0; $i < $this->criteria_cnt; $i++)
        {
            if ($this->criteria[$i][1] != " " && $this->criteria[$i][2] != "") $tmp = $tmp . $this->criteria[$i][0] . $human_fields[$this->criteria[$i][1]] . ' "' . $this->criteria[$i][2] . '" ' . $this->criteria[$i][3] . ' ' . $this->criteria[$i][4] . ' ';
        }
        if ($tmp != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . $tmp;
        return $tmp;
    }
    
    function Description() {
        $human_fields["LIKE"] = gettext("contains");
        $human_fields["NOT LIKE"] = gettext("does not contain");
        $human_fields[""] = "";
        $tmp = "";
        if ($this->data_encode[0] != " " && $this->data_encode[1] != " ") {
            $tmp = $tmp . ' (' . gettext("data encoded as") . ' ' . $this->data_encode[0];
            $tmp = $tmp . ' => ' . (($this->data_encode[1] == "") ? "ascii" : $this->data_encode[1]);
            $tmp = $tmp . ')<BR>';
        } else $tmp = $tmp . ' ' . gettext("(no data conversion, assuming criteria in DB native encoding)") . '<BR>';
        for ($i = 0; $i < $this->criteria_cnt; $i++) {
            if ($this->criteria[$i][1] != " " && $this->criteria[$i][2] != "") $tmp = $tmp . $this->criteria[$i][0] . $human_fields[$this->criteria[$i][1]] . ' "' . $this->criteria[$i][2] . '" ' . $this->criteria[$i][3] . ' ' . $this->criteria[$i][4] . ' ';
        }
        if ($tmp != "") $tmp = $tmp . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
};
class OssimPriorityCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], "", array(
            "=",
            "!=",
            "&lt;",
            "&lt;=",
            "&gt;",
            "&gt;="
        ));
        $this->criteria[0] = preg_replace("/\&lt\;/", "<", $this->criteria[0]);
        $this->criteria[0] = preg_replace("/\&gt\;/", ">", $this->criteria[0]);

        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_DIGIT, array(
            "null"
        ));
    }
    function PrintForm() {
        if ($this->db->baseGetDBVersion() >= 103) {
            echo '<SELECT NAME="ossim_priority[0]">
                <OPTION VALUE=" " ' . chk_select($this->criteria[0], "=") . '>__</OPTION>
                <OPTION VALUE="=" ' . chk_select($this->criteria[0], "=") . '>==</OPTION>
                <OPTION VALUE="!=" ' . chk_select($this->criteria[0], "!=") . '>!=</OPTION>
                <OPTION VALUE="<"  ' . chk_select($this->criteria[0], "<") . '><</OPTION>
                <OPTION VALUE=">"  ' . chk_select($this->criteria[0], ">") . '>></OPTION>
                <OPTION VALUE="<=" ' . chk_select($this->criteria[0], "<=") . '><=</OPTION>
                <OPTION VALUE=">=" ' . chk_select($this->criteria[0], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="ossim_priority[1]">
                <OPTION VALUE="" ' . chk_select($this->criteria[1], " ") . '>{ any Priority }</OPTION>
                <OPTION VALUE="0" ' . chk_select($this->criteria[1], "0") . '>0</OPTION>
                <OPTION VALUE="1" ' . chk_select($this->criteria[1], "1") . '>1</OPTION>
                <OPTION VALUE="2" ' . chk_select($this->criteria[1], "2") . '>2</OPTION>
                <OPTION VALUE="3" ' . chk_select($this->criteria[1], "3") . '>3</OPTION>
                <OPTION VALUE="4" ' . chk_select($this->criteria[1], "4") . '>4</OPTION>
                <OPTION VALUE="5" ' . chk_select($this->criteria[1], "5") . '>5</OPTION>';
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBVersion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . 'Ossim Priority = ' . '<I>none</I>';
                else $tmp = $tmp . 'priority ' . $this->criteria[0] . " " . $this->criteria[1];
            }
        }
        return $tmp;
    }
}; /* OssimPriorityCriteria */
class OssimRiskACriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT | VAR_LETTER);
    }
    function PrintForm() {
        echo '<SELECT NAME="ossim_risk_a">
             <OPTION VALUE=" " ' . chk_select($this->criteria, " ") . '>{ any risk }</OPTION>
	     <OPTION VALUE="low" ' . chk_select($this->criteria, "low") . '>Low</OPTION>
             <OPTION VALUE="medium" ' . chk_select($this->criteria, "medium") . '>Medium</OPTION>
	     <OPTION VALUE="high" ' . chk_select($this->criteria, "high") . '>High</OPTION>';
        echo '</SELECT>&nbsp;&nbsp';
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    
    function Description_light()
    {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $this->cs->GetClearCriteriaString2($this->export_name) . $this->criteria . ' risk';
        return $tmp;
    }
    
    function Description() {
        $tmp = "";
        if ($this->criteria != " " && $this->criteria != "") $tmp = $tmp . 'risk = [' . $this->criteria . '] ' . "" . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
        return $tmp;
    }
}; /* OssimRiskACriteria */
class OssimRiskCCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], "", array(
            "=",
            "!=",
            "<",
            "<=",
            ">",
            ">="
        ));
        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_DIGIT, array(
            "null"
        ));
    }
    function PrintForm() {
        if ($this->db->baseGetDBVersion() >= 103) {
            echo '<SELECT NAME="ossim_risk_c[0]">
                <OPTION VALUE=" " ' . chk_select($this->criteria[0], "=") . '>__</OPTION>
                <OPTION VALUE="=" ' . chk_select($this->criteria[0], "=") . '>==</OPTION>
                <OPTION VALUE="!=" ' . chk_select($this->criteria[0], "!=") . '>!=</OPTION>
                <OPTION VALUE="<"  ' . chk_select($this->criteria[0], "<") . '><</OPTION>
                <OPTION VALUE=">"  ' . chk_select($this->criteria[0], ">") . '>></OPTION>
                <OPTION VALUE="<=" ' . chk_select($this->criteria[0], "><=") . '><=</OPTION>
                <OPTION VALUE=">=" ' . chk_select($this->criteria[0], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="ossim_risk_c[1]">
                <OPTION VALUE="" ' . chk_select($this->criteria[1], " ") . '>{ any Risk }</OPTION>
                <OPTION VALUE="0" ' . chk_select($this->criteria[1], "0") . '>0</OPTION>
                <OPTION VALUE="1" ' . chk_select($this->criteria[1], "1") . '>1</OPTION>
                <OPTION VALUE="2" ' . chk_select($this->criteria[1], "2") . '>2</OPTION>
                <OPTION VALUE="3" ' . chk_select($this->criteria[1], "3") . '>3</OPTION>
                <OPTION VALUE="4" ' . chk_select($this->criteria[1], "4") . '>4</OPTION>
                <OPTION VALUE="5" ' . chk_select($this->criteria[1], "5") . '>5</OPTION>
                <OPTION VALUE="6" ' . chk_select($this->criteria[1], "6") . '>6</OPTION>
                <OPTION VALUE="7" ' . chk_select($this->criteria[1], "7") . '>7</OPTION>
                <OPTION VALUE="8" ' . chk_select($this->criteria[1], "8") . '>8</OPTION>
                <OPTION VALUE="9" ' . chk_select($this->criteria[1], "9") . '>9</OPTION>
                <OPTION VALUE="10" ' . chk_select($this->criteria[1], "10") . '>10</OPTION>';
            echo '</;SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBVersion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . 'risk = ' . '<I>none</I><BR>';
                else $tmp = $tmp . 'risk ' . $this->criteria[0] . " " . $this->criteria[1] . $this->cs->GetClearCriteriaString($this->export_name) . '<BR>';
            }
        }
        return $tmp;
    }
}; /* OssimRiskCCriteria */
class OssimReliabilityCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], "", array(
            "=",
            "!=",
            "&lt;",
            "&lt;=",
            "&gt;",
            "&gt;="
        ));
        $this->criteria[0] = preg_replace("/\&lt\;/", "<", $this->criteria[0]);
        $this->criteria[0] = preg_replace("/\&gt\;/", ">", $this->criteria[0]);

        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_DIGIT, array(
            "null"
        ));
    }
    function PrintForm() {
        if ($this->db->baseGetDBVersion() >= 103) {
            echo '<SELECT NAME="ossim_reliability[0]">
                <OPTION VALUE=" " ' . chk_select($this->criteria[0], "=") . '>__</OPTION>
                <OPTION VALUE="=" ' . chk_select($this->criteria[0], "=") . '>==</OPTION>
                <OPTION VALUE="!=" ' . chk_select($this->criteria[0], "!=") . '>!=</OPTION>
                <OPTION VALUE="<"  ' . chk_select($this->criteria[0], "<") . '><</OPTION>
                <OPTION VALUE=">"  ' . chk_select($this->criteria[0], ">") . '>></OPTION>
                <OPTION VALUE="<=" ' . chk_select($this->criteria[0], "<=") . '><=</OPTION>
                <OPTION VALUE=">=" ' . chk_select($this->criteria[0], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="ossim_reliability[1]">
                <OPTION VALUE="" ' . chk_select($this->criteria[1], " ") . '>{ any Reliability }</OPTION>
                <OPTION VALUE="0" ' . chk_select($this->criteria[1], "0") . '>0</OPTION>
                <OPTION VALUE="1" ' . chk_select($this->criteria[1], "1") . '>1</OPTION>
                <OPTION VALUE="2" ' . chk_select($this->criteria[1], "2") . '>2</OPTION>
                <OPTION VALUE="3" ' . chk_select($this->criteria[1], "3") . '>3</OPTION>
                <OPTION VALUE="4" ' . chk_select($this->criteria[1], "4") . '>4</OPTION>
                <OPTION VALUE="5" ' . chk_select($this->criteria[1], "5") . '>5</OPTION>
                <OPTION VALUE="6" ' . chk_select($this->criteria[1], "6") . '>6</OPTION>
                <OPTION VALUE="7" ' . chk_select($this->criteria[1], "7") . '>7</OPTION>
                <OPTION VALUE="8" ' . chk_select($this->criteria[1], "8") . '>8</OPTION>
                <OPTION VALUE="9" ' . chk_select($this->criteria[1], "9") . '>9</OPTION>
                <OPTION VALUE="10" ' . chk_select($this->criteria[1], "10") . '>10</OPTION>';
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBVersion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . 'reliability = ' . '<I>none</I>';
                else $tmp = $tmp . 'reliability ' . $this->criteria[0] . " " . $this->criteria[1];
            }
        }
        return $tmp;
    }
}; /* OssimReliabilityCriteria */
class OssimAssetSrcCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], "", array(
            "=",
            "!=",
            "&lt;",
            "&lt;=",
            "&gt;",
            "&gt;="
        ));
        $this->criteria[0] = preg_replace("/\&lt\;/", "<", $this->criteria[0]);
        $this->criteria[0] = preg_replace("/\&gt\;/", ">", $this->criteria[0]);

        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_DIGIT, array(
            "null"
        ));
    }
    function PrintForm() {
        if ($this->db->baseGetDBVersion() >= 103) {
            echo '<SELECT NAME="ossim_asset_src[0]">
                <OPTION VALUE=" " ' . chk_select($this->criteria[0], "=") . '>__</OPTION>
                <OPTION VALUE="=" ' . chk_select($this->criteria[0], "=") . '>==</OPTION>
                <OPTION VALUE="!=" ' . chk_select($this->criteria[0], "!=") . '>!=</OPTION>
                <OPTION VALUE="<"  ' . chk_select($this->criteria[0], "<") . '><</OPTION>
                <OPTION VALUE=">"  ' . chk_select($this->criteria[0], ">") . '>></OPTION>
                <OPTION VALUE="<=" ' . chk_select($this->criteria[0], "><=") . '><=</OPTION>
                <OPTION VALUE=">=" ' . chk_select($this->criteria[0], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="ossim_asset_src[1]">
                <OPTION VALUE="" ' . chk_select($this->criteria[1], " ") . '>{ any Asset }</OPTION>
                <OPTION VALUE="0" ' . chk_select($this->criteria[1], "0") . '>0</OPTION>
                <OPTION VALUE="1" ' . chk_select($this->criteria[1], "1") . '>1</OPTION>
                <OPTION VALUE="2" ' . chk_select($this->criteria[1], "2") . '>2</OPTION>
                <OPTION VALUE="3" ' . chk_select($this->criteria[1], "3") . '>3</OPTION>
                <OPTION VALUE="4" ' . chk_select($this->criteria[1], "4") . '>4</OPTION>
                <OPTION VALUE="5" ' . chk_select($this->criteria[1], "5") . '>5</OPTION>';
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBVersion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . 'asset = ' . '<I>none</I>';
                else $tmp = $tmp . 'asset ' . $this->criteria[0] . " " . $this->criteria[1];
            }
        }
        return $tmp;
    }
}; /* OssimAssetSrcCriteria */
class OssimAssetDstCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria[0] = CleanVariable($this->criteria[0], "", array(
            "=",
            "!=",
            "&lt;",
            "&lt;=",
            "&gt;",
            "&gt;="
        ));
        $this->criteria[0] = preg_replace("/\&lt\;/", "<", $this->criteria[0]);
        $this->criteria[0] = preg_replace("/\&gt\;/", ">", $this->criteria[0]);

        $this->criteria[1] = CleanVariable($this->criteria[1], VAR_DIGIT, array(
            "null"
        ));
    }
    function PrintForm() {
        if ($this->db->baseGetDBVersion() >= 103) {
            echo '<SELECT NAME="ossim_asset_dst[0]">
                <OPTION VALUE=" " ' . chk_select($this->criteria[0], "=") . '>__</OPTION>
                <OPTION VALUE="=" ' . chk_select($this->criteria[0], "=") . '>==</OPTION>
                <OPTION VALUE="!=" ' . chk_select($this->criteria[0], "!=") . '>!=</OPTION>
                <OPTION VALUE="<"  ' . chk_select($this->criteria[0], "<") . '><</OPTION>
                <OPTION VALUE=">"  ' . chk_select($this->criteria[0], ">") . '>></OPTION>
                <OPTION VALUE="<=" ' . chk_select($this->criteria[0], "<=") . '><=</OPTION>
                <OPTION VALUE=">=" ' . chk_select($this->criteria[0], ">=") . '>>=</SELECT>';
            echo '<SELECT NAME="ossim_asset_dst[1]">
                <OPTION VALUE="" ' . chk_select($this->criteria[1], " ") . '>{ any Asset }</OPTION>
 	        <OPTION VALUE="0" ' . chk_select($this->criteria[1], "0") . '>0</OPTION>
		<OPTION VALUE="1" ' . chk_select($this->criteria[1], "1") . '>1</OPTION>
		<OPTION VALUE="2" ' . chk_select($this->criteria[1], "2") . '>2</OPTION>
		<OPTION VALUE="3" ' . chk_select($this->criteria[1], "3") . '>3</OPTION>
		<OPTION VALUE="4" ' . chk_select($this->criteria[1], "4") . '>4</OPTION>
		<OPTION VALUE="5" ' . chk_select($this->criteria[1], "5") . '>5</OPTION>';
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBVersion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . 'asset = ' . '<I>none</I>';
                else $tmp = $tmp . 'asset ' . $this->criteria[0] . " " . $this->criteria[1];
            }
        }
        return $tmp;
    }
}; /* OssimAssetDstCriteria */
class OssimTypeCriteria extends SingleElementCriteria {
    function Init() {
        $this->criteria = "";
    }
    function Clear() {
        /* clears the criteria */
    }
    function SanitizeElement() {
        $this->criteria = CleanVariable($this->criteria, VAR_DIGIT);
    }
    function PrintForm() {
        if ($this->db->baseGetDBVersion() >= 103) {
            echo '<SELECT NAME="ossim_type[1]">
                <OPTION VALUE="" ' . chk_select($this->criteria[1], " ") . '>{ any }</OPTION>
                <OPTION VALUE="2" ' . chk_select($this->criteria[1], "2") . '>Alarm</OPTION>';
            echo '</SELECT>&nbsp;&nbsp';
        }
    }
    function ToSQL() {
        /* convert this criteria to SQL */
    }
    function Description() {
        $tmp = "";
        if ($this->db->baseGetDBVersion() >= 103) {
            if ($this->criteria[1] != " " && $this->criteria[1] != "") {
                if ($this->criteria[1] == null) $tmp = $tmp . 'type = ' . '<I>none</I>';
                else $tmp = $tmp . 'type ' . $this->criteria[0] . " " . $this->criteria[1];
            }
        }
        return $tmp;
    }
}; /* OssimTypeCriteria */
?>
