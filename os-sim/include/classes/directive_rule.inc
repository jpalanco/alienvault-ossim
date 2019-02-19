<?php
/**
* directive_rule.inc
*
* File directive_rule.inc is used to:
*   - Directive_rule object in directive editor main class
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
* @package    ossim-framework\Directives
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
 * Directive_rule Class
 *
 * This class represents a Rule, a collection of attributes
 * and a rule node. Matches the rule node in XML
 * A class representing a rule, ie a collection of fields - the attributes of
 * the XML rule node - and an array of sub-rules.This rule is a node within
 * the rule tree model, and generates its own HTML code and its sub-rules code.
 * In term of graph theory, this model represents a labelled ordered n-ary tree.
 *
 * @package    ossim-framework\Directives
 * @autor      AlienVault INC
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
define("IP_PATT", "!?((25[0-4]|(2[0-4]|1[0-9]|[1-9]?)[0-9]\.){3}(25[0-4]|(2[0-4]|1[0-9]|[1-9]?)[0-9]))");
define("NAME_PATT", "!?[0-9a-zA-Z-_]+");
define("PORT_LIST_PATT", "(!?([0-9][0-9]*),)*(!?([0-9][0-9]*))");
define("LIST_PATT", "(" . IP_PATT . "|" . NAME_PATT . ")(,(" . IP_PATT . "|" . NAME_PATT . '))*');
define("SRC_IP_PATT", '^[[:digit:]]+\:SRC_IP$');
define("DEST_IP_PATT", '^[[:digit:]]+\:DST_IP$');
define("SRC_PORT_PATT", '^[[:digit:]]+\:SRC_PORT$');
define("DEST_PORT_PATT", '^[[:digit:]]+\:DST_PORT$');
define("SENSOR_PATT", '^[[:digit:]]+\:SENSOR$');

class Directive_rule {
    /**
     * The type of the rule (ie "monitor" or "detector").
     * @access private
     * @var string
     */
    public $plugin_type;
    /**
     * The name of the rule (ie an identificator).
     * @access private
     * @var string
     */
    public $name;
    /**
     * The priority of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $priority;
    /**
     * The reliability of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $reliability;
    /**
     * The time_out of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $time_out;
    /**
     * The occurrence of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $occurrence;
    /**
     * The from field of the rule (ie a list of IPs / network name, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_IP" OR "nb:DST_IP" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    public $from;
    /**
     * The to field of the rule (ie a list of IPs / network name, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_IP" OR "nb:DST_IP" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    public $to;
    /**
     * The port_from field of the rule (ie a list of ports, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_PORT" OR "nb:DST_PORT" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    public $port_from;
    /**
     * The port_to field of the rule (ie a list of ports, negated or not with
     * "!" separated by commas OR
     * the string "ANY" OR "nb:SRC_PORT" OR "nb:DST_PORT" where nb is a string representing
     * a number).
     * @access private
     * @var string
     */
    public $port_to;
    /**
     * The plugin_id field of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $plugin_id;
    /**
     * The plugin_sid field of the rule (ie a number list separated by commas).
     * @access private
     * @var string
     */
    public $plugin_sid;
    /**
     * The condition field of the rule (ie "eq" OR "ne" OR "lt" OR "gt" OR "ge" OR "le").
     * @access private
     * @var string
     */
    public $condition;
    /**
     * The value field of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $value;
    /**
     * The interval field of the rule (ie a string representing a number).
     * @access private
     * @var string
     */
    public $interval;
    /**
     * The absolute field of the rule (ie a "true" or "false").
     * @access private
     * @var string
     */
    public $absolute;
    /**
     * The protocol field of the rule (ie a protocol list - see the OSSIM doc for the
     *complete list, OR the string "ANY" OR "nb:PROTOCOL" OR "nb:PROTOCOL" where nb is a string representing
     * a number).).
     * @access private
     * @var string
     */
    public $protocol;
    /**
     * The sensor field of the rule.
     * @access private
     * @var string
     */
    public $sensor;
    /**
     * The sticky field of the rule (ie ).
     * @access private
     * @var string
     */
    public $sticky;
    /**
     * The sticky_different field of the rule (ie ).
     * @access private
     * @var string
     */
    public $sticky_different;
    /**
     * The userdata 1-9 field of the rule (ie ).
     * @access private
     * @var string
     */
    public $userdata1;
    public $userdata2;
    public $userdata3;
    public $userdata4;
    public $userdata5;
    public $userdata6;
    public $userdata7;
    public $userdata8;
    public $userdata9;
    /**
     * The filename field of the rule (ie ).
     * @access private
     * @var string
     */
    public $filename;
    /**
     * The username field of the rule (ie ).
     * @access private
     * @var string
     */
    public $username;
    /**
     * The password field of the rule (ie ).
     * @access private
     * @var string
     */
    public $password;
    /**
     * The groups field of the rule (ie ).
     * @access private
     * @var string
     */
    public $groups;
    /**
     * The level of the rule, ie the number of rules that are above this one (or
     * its depth in graph theory)
     * @access private
     * @var int
     */
    public $level;
    /**
     * The height of the whole rule tree that contains this rule, (useful for display)
     * @access private
     * @var int
     */
    public $ilevel;
    /**
     * The rule identificator, composed as follow : "dirID-RuleID-fatherID", where
     * 'dirID' is the identificator of the directive the rule belongs to,
     * 'ruleID' is a unique identifier attributed to each node of the rule tree
     * by a simple DFS and 'fatherID' the id of the direct father of this rule.
     * This numerotation is mostly done calling a method from a Directive
     * object and must be left untouched.
     * @access private
     * @var string
     */
    public $id;
    /**
     * The number of child of this rule
     * @access private
     * @var integer
     */
    public $nb_child;
    /**
     * The xml code of the rule
     * @access private
     * @var Dom element
     */
    public $rule;
    /**
     * The constructor.
     */
    public function __construct($id, $level, $rule, $attributes)
    {
        $this->id               = $id;
        $this->level            = $level;
        $this->rule             = $rule;
        $this->name             = $attributes['name'];
        $this->plugin_id        = $attributes['plugin_id'];
        $this->plugin_type      = $attributes['type'];
        $this->plugin_sid       = $attributes['plugin_sid'];
        $this->product          = $attributes['product'];
        $this->category         = $attributes['category'];
        $this->subcategory      = $attributes['subcategory'];
        $this->entity           = $attributes['entity'];
        $this->from             = ($attributes['from'] == "") ? "ANY" : $attributes['from'];
        $this->port_from        = ($attributes['port_from'] == "") ? "ANY" : $attributes['port_from'];
        $this->to               = ($attributes['to'] == "") ? "ANY" : $attributes['to'];
        $this->port_to          = ($attributes['port_to'] == "") ? "ANY" : $attributes['port_to'];
        $this->from_rep         = $attributes['from_rep'];
        $this->to_rep           = $attributes['to_rep'];
        $this->from_rep_min_pri = $attributes['from_rep_min_pri'];
        $this->to_rep_min_pri   = $attributes['to_rep_min_pri'];
        $this->from_rep_min_rel = $attributes['from_rep_min_rel'];
        $this->to_rep_min_rel   = $attributes['to_rep_min_rel'];
        $this->protocol         = ($attributes['protocol'] == "") ? "ANY" : $attributes['protocol'];
        $this->sensor           = ($attributes['sensor'] == "") ? "ANY" : $attributes['sensor'];
        $this->occurrence       = ($attributes['occurrence'] == "") ? "1" : $attributes['occurrence'];
        $this->time_out         = ($attributes['time_out'] == "") ? "None" : $attributes['time_out'];
        $this->reliability      = ($attributes['reliability'] == "") ? "0" : $attributes['reliability'];
        $this->condition        = ($attributes['condition'] == "") ? "Default" : $attributes['condition'];
        $this->value            = ($attributes['value'] == "") ? "Default" : $attributes['value'];
        $this->interval         = ($attributes['interval'] == "") ? "Default" : $attributes['interval'];
        $this->absolute         = ($attributes['absolute'] == "") ? "Default" : $attributes['absolute'];
        $this->sticky           = ($attributes['sticky'] == "") ? "None" : $attributes['sticky'];
        $this->sticky_different = ($attributes['sticky_different'] == "") ? "None" : $attributes['sticky_different'];
        $this->userdata1        = $attributes['userdata1'];
        $this->userdata2        = $attributes['userdata2'];
        $this->userdata3        = $attributes['userdata3'];
        $this->userdata4        = $attributes['userdata4'];
        $this->userdata5        = $attributes['userdata5'];
        $this->userdata6        = $attributes['userdata6'];
        $this->userdata7        = $attributes['userdata7'];
        $this->userdata8        = $attributes['userdata8'];
        $this->userdata9        = $attributes['userdata9'];
        $this->filename         = $attributes['filename'];
        $this->username         = $attributes['username'];
        $this->password         = $attributes['password'];
    }
    
    /**
     * This function prints the html code on the output. Should be called to render the current rule
     * (and all of its sub-rules) in a navigator. This method only display <tr> elements, and so
     * other markups (eg <table>) must be printed in order to obtain a valid HTML
     * code.
     * 
     * @param string $engine_id
     * @param int    $level
     * @param array  $rules
     * @param string $xml_file  [Optional] XML file
     */
    public function print_rule($engine_id, $level, &$rules, $xml_file = '')
    {
        $db = new ossim_db();
        $conn = $db->connect();

        // Get columns
        $columns = array(
            'name'             => _('Name'),
            'reliability'      => _('Reliability'),
            'time_out'         => _('Timeout'),
            'occurrence'       => _('Occurrence'),
            'from'             => _('From'),
            'to'               => _('To'),
            'plugin_id'        => _('Data Source'),
            'plugin_sid'       => _('Event Type'),
            'sensor'           => _('Sensor'),
            'protocol'         => _('Protocol'),
            'sticky_different' => _('Sticky Dif'),
            'filename'         => _('Filename'),
            'username'         => _('Username'),
            'password'         => _('Pass'),
            'userdata1'        => _('Userdata1'),
            'userdata2'        => _('Userdata2'),
            'userdata3'        => _('Userdata3'),
            'userdata4'        => _('Userdata4'),
            'userdata5'        => _('Userdata5'),
            'userdata6'        => _('Userdata6'),
            'userdata7'        => _('Userdata7'),
            'userdata8'        => _('Userdata8'),
            'userdata9'        => _('Userdata9')
        );

        $config = new User_config($conn);
        $columns_arr = $config->get(Session::get_session_user(), 'directive_editor_cols', 'php', 'directives');
        if (count($columns_arr) < 1) 
        {
            $columns_arr = array('name', 'reliability', 'time_out', 'occurrence', 'from', 'to', 'plugin_id', 'plugin_sid');
        }

        $_aux_id   = explode('-', $this->id);
        $id_dir    = $_aux_id[0];
        $id_father = $_aux_id[2];
        $newid     = $this->_new_id($this->id, $rules);


        // Aux variables for move links show/hide
        $level_count = 0;
        $first = '';
        $last  = '';

        foreach ($rules as $rule_aux)
        {
            if ($rule_aux->level == $level && preg_replace("/\d+\-\d+\-(\d+)/", "\\1", $rule_aux->id) == $id_father)
            {
                if ($first == '')
                {
                    $first = $rule_aux->id;
                }

                $last = $rule_aux->id;
                
                $level_count++;
            }
        }
        $is_first = ($first == $this->id) ? TRUE : FALSE;
        $is_last  = ($last  == $this->id) ? TRUE : FALSE;

        $editable = ($xml_file == 'user.xml') ? TRUE : FALSE;

        if ($this->level <= $level)
        {
            // This template has the code. It prevents Sonar alert DuplicateClassNameFound
            include(AV_MAIN_ROOT_PATH.'/directives/templates/rule_tpl.php');
        }
    }

    /**
     * This function prints a rule attribute in a html cell table
     * 
     * @param object  $conn
     * @param string  $col_label
     * @param object  $rule
     * @param int     $level
     * @param boolean $editable
     * @param string  $engine_id
     * @param int     $id_dir
     * @param string  $xml_file
     */
    public function print_rule_cell($conn, $col_label, $rule, $level, $editable, $engine_id, $id_dir, $xml_file)
    {
        // This template has the code. It prevents Sonar alert DuplicateClassNameFound
        include(AV_MAIN_ROOT_PATH."/directives/templates/rule_cell_tpl.php");
    }
    
    /**
     * This function generates a DomElement object representing the associated
     * XML Dom node.Returns NULL if the Rule object is not well-formed (ie if mandatory
     * tags are NULL or empty, see the OSSIM doc).
     * @return object a XML node, DomElement obj. if he Rule is well-formed, NULL otherwise.
     * 
     * @param object $dom
     * 
     * @return DOM Node Object
     */
    public function getXMLNode($dom)
    {
        //$node = new DomElement("rule");
        //$XML_FILE = get_directive_file($id_dir);
        //print $XML_FILE."<br />";
        //$dom = open_file($XML_FILE);
        $node = $dom->createElement('rule');
        if ($this->plugin_type != NULL && $this->plugin_type != "") $node->setAttribute("type", $this->plugin_type);
        if ($this->name != NULL && $this->name != "") $node->setAttribute("name", $this->name);
        if ($this->entity != NULL && $this->entity != "") $node->setAttribute("entity", $this->entity);
        if ($this->from != NULL && $this->from != "") $node->setAttribute("from", $this->from);
        if ($this->to != NULL && $this->to != "") $node->setAttribute("to", $this->to);
        if ($this->port_from != NULL && $this->port_from != "") $node->setAttribute("port_from", $this->port_from);
        if ($this->port_to != NULL && $this->port_to != "") $node->setAttribute("port_to", $this->port_to);
        if ($this->from_rep != NULL && $this->from_rep != "") $node->setAttribute("from_rep", $this->from_rep);
        if ($this->to_rep != NULL && $this->to_rep != "") $node->setAttribute("to_rep", $this->to_rep);
        if ($this->from_rep_min_pri != NULL && $this->from_rep_min_pri != "") $node->setAttribute("from_rep_min_pri", $this->from_rep_min_pri);
        if ($this->to_rep_min_pri != NULL && $this->to_rep_min_pri != "") $node->setAttribute("to_rep_min_pri", $this->to_rep_min_pri);
        if ($this->from_rep_min_rel != NULL && $this->from_rep_min_rel != "") $node->setAttribute("from_rep_min_rel", $this->from_rep_min_rel);
        if ($this->to_rep_min_rel != NULL && $this->to_rep_min_rel != "") $node->setAttribute("to_rep_min_rel", $this->to_rep_min_rel);
        if ($this->priority != NULL && $this->priority != "") $node->setAttribute("priority", $this->priority);
        if ($this->reliability != NULL && $this->reliability != "") $node->setAttribute("reliability", $this->reliability);
        if ($this->occurrence != "ANY" && $this->occurrence != "") $node->setAttribute("occurrence", $this->occurrence);
        if ($this->time_out != "None" && $this->time_out != "") $node->setAttribute("time_out", $this->time_out);
        
        // Plugin selected or taxonomy
        if ($this->product != NULL && $this->product != "" && $this->product > 0) {
            $node->setAttribute("product", $this->product);
        } elseif ($this->plugin_id != NULL && $this->plugin_id != "") {
            $node->setAttribute("plugin_id", $this->plugin_id);
        }
        if ($this->category != NULL && $this->category != "" && $this->category > 0) {
            $node->setAttribute("category", $this->category);
            if ($this->subcategory != NULL && $this->subcategory != "" && $this->subcategory > 0) $node->setAttribute("subcategory", $this->subcategory);
        } elseif ($this->plugin_sid != NULL && $this->plugin_sid != "") {
            $node->setAttribute("plugin_sid", $this->plugin_sid);
        }
        
        if ($this->condition != "Default" && $this->condition != "") $node->setAttribute("condition", $this->condition);
        if ($this->value != "Default" && $this->value != "") $node->setAttribute("value", $this->value);
        if ($this->absolute != "Default" && $this->absolute != "") $node->setAttribute("absolute", $this->absolute);
        if ($this->interval != "Default" && $this->interval != "") $node->setAttribute("interval", $this->interval);
        if ($this->protocol != "ANY" && $this->protocol != "") $node->setAttribute("protocol", $this->protocol);
        if ($this->sensor != "ANY" && $this->sensor != "") $node->setAttribute("sensor", $this->sensor);
        if ($this->sticky != "" && $this->sticky != "Default" && $this->sticky != "None") $node->setAttribute("sticky", $this->sticky);
        if ($this->sticky_different != "None" && trim($this->sticky_different) != "") $node->setAttribute("sticky_different", $this->sticky_different);
        if ($this->userdata1 != NULL && $this->userdata1 != "") $node->setAttribute("userdata1", $this->userdata1);
        if ($this->userdata2 != NULL && $this->userdata2 != "") $node->setAttribute("userdata2", $this->userdata2);
        if ($this->userdata3 != NULL && $this->userdata3 != "") $node->setAttribute("userdata3", $this->userdata3);
        if ($this->userdata4 != NULL && $this->userdata4 != "") $node->setAttribute("userdata4", $this->userdata4);
        if ($this->userdata5 != NULL && $this->userdata5 != "") $node->setAttribute("userdata5", $this->userdata5);
        if ($this->userdata6 != NULL && $this->userdata6 != "") $node->setAttribute("userdata6", $this->userdata6);
        if ($this->userdata7 != NULL && $this->userdata7 != "") $node->setAttribute("userdata7", $this->userdata7);
        if ($this->userdata8 != NULL && $this->userdata8 != "") $node->setAttribute("userdata8", $this->userdata8);
        if ($this->userdata9 != NULL && $this->userdata9 != "") $node->setAttribute("userdata9", $this->userdata9);
        if ($this->filename != NULL && $this->filename != "") $node->setAttribute("filename", $this->filename);
        if ($this->username != NULL && $this->username != "") $node->setAttribute("username", $this->username);
        if ($this->password != NULL && $this->password != "") $node->setAttribute("password", $this->password);
        if ($this->nb_child > 0) {
            $temp = $dom->createElement('rules');
            //$rulesNode = $node->appendChild($temp);
            $node->appendChild($temp);
        }
        return $node;
    }
    
    /**
     * This function calculates the id of the possible new child of the rule
     * 
     * @param string $id
     * @param array  $tab_rules
     * 
     * @return string
     */
    private function _new_id($id, &$tab_rules)
    {
            list($id_dir, $id_rule, $id_father) = explode("-", $id);
            $i = $id_rule;
            do {
                $i++;
                $_aux_id    = explode("-", $tab_rules[$i]->id);
                $id_father2 = $_aux_id[2];
            }
            while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
            $newid = $id_dir . "-" . $i . "-" . $id_rule;
            return $newid;
    }
}

/* End of file directive_rule.inc */
/* Location: ../includes/directive_rule.inc */
