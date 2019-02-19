<?php
/**
* directive_editor.inc
*
* File directive_editor.inc is used to:
*   - Manage directive XML files by a visual interface
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

// Display XML parse errors
libxml_use_internal_errors(true);

define('_MAIN_PATH', "/etc/ossim/server");

/**
 * Directive_editor Class
 *
 * Class for visual managing directives into XML files
 *
 * @package    ossim-framework\Directives
 * @autor      AlienVault INC
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Directive_editor {
    
    /**
     * Path of the XML files
     * @var string
     *
     */
    public $main_path = _MAIN_PATH;
    
    /**
     * Path of the engine XML files _MAIN_PATH/UUID
     * @var string
     *
     */
    public $engine_path;
    
    /**
     * Engine UUID
     * @var string
     *
     */
    public $engine_id;
    
    /**
     * Connection to DB
     * @var string
     *
     */
    public $conn;
    
    /**
     * Class constructor
     *
     * This function sets up the class
     *
     * @throws Exception  If directives.xml doesn't exist, even when user.xml doesn't exist
     */
    public function __construct($engine_id)
    {
        $db                = new ossim_db();
        $this->conn        = $db->connect();
        $this->engine_id   = $engine_id;
        $this->engine_path = $this->main_path."/".$engine_id;
        
        if (!file_exists($this->engine_path."/directives.xml")) 
        {
            $exp_msg = _('XML Directives file not found in directory').' '.$this->engine_path;

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        if (!file_exists($this->engine_path."/user.xml")) 
        {
            $exp_msg = _('XML with User Contributed directives file not found in directory').' '.$this->engine_path;

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    /**
     * This function returns the categories of directives with some info for each one
     * 
     * @param string $query
     * 
     * @return array
     */
    public function get_categories($query = '')
    {
        // Load Categories
        $categories = array();

        // get directives for active true/false
        $aux_actives = array();
        
        // 1-. Feed directives (alienvault-*.xml)
        if (file_exists($this->main_path."/directives.xml"))
        {
            $f = fopen($this->main_path."/directives.xml", "r");
            while (!feof($f))
            {
                $line = trim(fgets($f));
                if (preg_match("/\<\!ENTITY (.*) SYSTEM '(.*)'>/", $line, $found)) {
                    $aux_actives[$found[1]] = 0;
                }
                if (preg_match("/^\s*\&(.*);/", $line, $found)) {
                    $aux_actives[$found[1]] = 1;
                }
            }
            fclose($f);
        }
        
        // 2-. Selected engine directives (user.xml)
        if (file_exists($this->engine_path."/directives.xml"))
        {
            $f = fopen($this->engine_path."/directives.xml", "r");
            while (!feof($f)) {
                $line = trim(fgets($f));
                if (preg_match("/\<\!ENTITY (.*) SYSTEM '(.*)'>/", $line, $found)) {
                    $aux_actives[$found[1]] = 0;
                }
                if (preg_match("/^\s*\&(.*);/", $line, $found)) {
                    $aux_actives[$found[1]] = 1;
                }
            }
            fclose($f);
        }
        
        $categories_xml = $this->get_xml($this->main_path."/categories.xml");
        if ($categories_xml)
        {
            foreach ($categories_xml->category as $category)
            {
                if (!isset($aux_actives[str_replace(".xml", '', (string) $category['xml_file'])]))
                {
                    continue;
                }
                // Get directives
                $directives = array();
                $file  = (file_exists($this->engine_path."/".(string)$category['xml_file'])) ? $this->engine_path."/".(string)$category['xml_file'] : $this->main_path."/".(string)$category['xml_file'];
                // User Contributed must be in engine folder
                if ((string)$category['xml_file'] == "user.xml")
                {
                    $file = $this->engine_path."/user.xml";
                }
                if (file_exists($file))
                {
                    $lines = file($file);
                    foreach ($lines as $line)
                    {
                        if (preg_match("/directive id\=\"(\d+)\"\s+name\=\"([^\"]+)\"\s+priority\=\"(\d+)\"/",$line,$found))
                        {
                            if ($query != '')
                            {
                                $pattern = preg_quote($query, "/");
                                if (!preg_match("/$pattern/i",$found[2]))
                                {
                                    continue;
                                }
                            }
                            $directives[$found[1]] = array('name' => $found[2], 'priority' => $found[3]);
                        }
                    }
                }
                $categories[] = array(
                    "name" => (string) $category['name'],
                    "xml_file" => (string) $category['xml_file'],
                    "mini" => (int) $category['mini'],
                    "maxi" => (int) $category['maxi'],
                    "active" => $aux_actives[str_replace(".xml", '', (string) $category['xml_file'])],
                    "directives" => $directives
                );
            }
        }
        // Put User Contributed at first
        $flag = 0;
        $user_category = array();
        for ($i = 0; $i < count($categories); $i++)
        {
            if ($categories[$i]['xml_file'] == "user.xml")
            {
                $flag = $i;
                $user_category = $categories[$i];
            }
        }
        if ($flag)
        {
            for ($i = $flag; $i > 0; $i--)
            {
                $categories[$i] = $categories[$i-1];
            }
            $categories[0] = $user_category;
        }
                
        return $categories;
    }
    
    /**
     * This function returns the next free directive id
     *
     * @param string $file
     * 
     * @return int
     */
    public function new_directive_id($file)
    {
        $categories = $this->get_categories();
        $ids = array();
        $ind = $mini = 0;
        foreach ($categories as $category) {
            if ($category['xml_file'] == $file) {
                $ind = $category['mini'];
                $mini = $category['mini'];
                foreach ($category['directives'] as $directive_id => $directive_name) {
                    $ids[] = $directive_id;
                }
            }
        }
        sort($ids);
        foreach ($ids as $id) {
            if ($ind == 0) $ind = $id;
            elseif ($id == $ind+1) $ind = $id;
        }
        
        return ($ind > 0) ? $ind+1 : $mini;
    }
    
    /**
     * This function reads the xml code to create a new directive object
     * 
     * @param object $dom
     * @param int    $directive_id
     * 
     * @return Directive
     */
    public function getDirectiveFromXML($dom, $directive_id)
    {
        $directive    = NULL;
        $_POST['ind'] = 0;
        $tab_rules    = array();
        $dir_node     = NULL;
        //$order = $this->findorder($dom, $directive_id);
        if ($directive_id)
        {
            foreach ($dom->getElementsByTagName('directive') as $dir) {
                if ($dir->getAttribute('id') == $directive_id) {
                    $dir_node = $dir;
                    $directive = new Directive($dir->getAttribute('id') , $dir->getAttribute('name') , $dir->getAttribute('priority') , $tab_rules, $dir_node);
                }
            }
        }
        
        if ($directive != NULL)
        {
            $_POST['dir_id'] = $directive->id;
            $this->_init_directive($dir_node, $tab_rules, 1, 0);
            $nb_child = 0;
            /* count the number of child of each rule */
            for ($i = 1; $i <= count($tab_rules); $i++) {
                list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$i]->id);
                if ($id_father == 0) $nb_child++;
            }
            $directive->nb_child = $nb_child;
            for ($i = 1; $i <= count($tab_rules); $i++) {
                list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$i]->id);
                $nb_child = 0;
                for ($j = 1; $j <= count($tab_rules); $j++) {
                    $_id_aux    = explode("-", $tab_rules[$j]->id);
                    $id_father2 = $_id_aux[2];
                    if ($id_rule == $id_father2) {
                        $nb_child++;
                    }
                }
                $tab_rules[$i]->nb_child = $nb_child;
            }
        }
    
        return $directive;
    }
    
    /**
     * This function creates a new directive object composed by all rules of the directive
     * 
     * @param object $directive
     * @param array  $tab_rules
     * @param int    $level
     * @param int    $father
     */
    private function _init_directive($directive, &$tab_rules, $level, $father)
    {
        if ($directive && $directive->hasChildNodes()) {
            $rules = $directive->childNodes;
            foreach($rules as $rule) {
                if (is_object($rule) && get_class($rule) == 'DOMElement' && $rule->nodeName == 'rule' && $rule->hasAttribute('name')) {
                    $_POST['ind']++;
                    $ind = $_POST['ind'];
                    $dir_id = $_POST['dir_id'];
                    $id = $dir_id . '-' . $ind . '-' . $father;
                    $attributes = array(
                        "name"             => $rule->getAttribute('name'),
                        "plugin_id"        => $rule->getAttribute('plugin_id'),
                        "type"             => $rule->getAttribute('type'),
                        "plugin_sid"       => $rule->getAttribute('plugin_sid'),
                        "product"          => $rule->getAttribute('product'),
                        "category"         => $rule->getAttribute('category'),
                        "subcategory"      => $rule->getAttribute('subcategory'),
                        "entity"           => $rule->getAttribute('entity'),
                        "from"             => $rule->getAttribute('from'),
                        "port_from"        => $rule->getAttribute('port_from'),
                        "to"               => $rule->getAttribute('to'),
                        "port_to"          => $rule->getAttribute('port_to'),
                        "from_rep"         => $rule->getAttribute('from_rep'),
                        "to_rep"           => $rule->getAttribute('to_rep'),
                        "from_rep_min_pri" => $rule->getAttribute('from_rep_min_pri'),
                        "to_rep_min_pri"   => $rule->getAttribute('to_rep_min_pri'),
                        "from_rep_min_rel" => $rule->getAttribute('from_rep_min_rel'),
                        "to_rep_min_rel"   => $rule->getAttribute('to_rep_min_rel'),
                        "protocol"         => $rule->getAttribute('protocol'),
                        "sensor"           => $rule->getAttribute('sensor'),
                        "occurrence"       => $rule->getAttribute('occurrence'),
                        "time_out"         => $rule->getAttribute('time_out'),
                        "reliability"      => $rule->getAttribute('reliability'),
                        "condition"        => $rule->getAttribute('condition'),
                        "value"            => $rule->getAttribute('value'),
                        "interval"         => $rule->getAttribute('interval'),
                        "absolute"         => $rule->getAttribute('absolute'),
                        "sticky"           => $rule->getAttribute('sticky'),
                        "sticky_different" => $rule->getAttribute('sticky_different'),
                        "userdata1"        => $rule->getAttribute('userdata1'),
                        "userdata2"        => $rule->getAttribute('userdata2'),
                        "userdata3"        => $rule->getAttribute('userdata3'),
                        "userdata4"        => $rule->getAttribute('userdata4'),
                        "userdata5"        => $rule->getAttribute('userdata5'),
                        "userdata6"        => $rule->getAttribute('userdata6'),
                        "userdata7"        => $rule->getAttribute('userdata7'),
                        "userdata8"        => $rule->getAttribute('userdata8'),
                        "userdata9"        => $rule->getAttribute('userdata9'),
                        "filename"         => $rule->getAttribute('filename'),
                        "username"         => $rule->getAttribute('username'),
                        "password"         => $rule->getAttribute('password')
                    );
                    $temp = new Directive_rule($id, $level, $rule, $attributes);
                    $tab_rules[$ind] = $temp;
                    if ($rule->hasChildNodes()) {
                        $rules = $rule->childNodes;
                        foreach($rules as $rule) {
                            $this->_init_directive($rule, $tab_rules, $level + 1, $ind);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * This function returns the array of a directive rules
     * 
     * @param int    $directive_id
     * @param string $file
     * 
     * @return array
     */
    public function get_rules($directive_id, $file) 
    {
        $filepath = (file_exists($this->engine_path."/".$file)) ? $this->engine_path."/".$file : $this->main_path."/".$file;
        $dom = self::get_xml($filepath, "DOM");
        $direct = $this->getDirectiveFromXML($dom, $directive_id);
        return $direct->rules;
    }
    
    /**
     * This function returns the rule object by ID
     * 
     * @param int    $directive_id
     * @param string $file
     * @param int    $rule_id
     * 
     * @return object|NULL
     */
    public function get_rule($directive_id, $file, $rule_id)
    {
        $filepath = (file_exists($this->engine_path."/".$file)) ? $this->engine_path."/".$file : $this->main_path."/".$file;
        $dom = self::get_xml($filepath, "DOM");
        $direct = $this->getDirectiveFromXML($dom, $directive_id);
        foreach ($direct->rules as $rule) {
            if ($rule->id == $rule_id) return $rule;
        }
        return NULL;
    }
    
    /**
     * This function updates a rule attribute
     * 
     * @param string       $rule_key
     * @param int          $directive_id
     * @param string       $file
     * @param string|array $attrib
     * @param string|array $value
     * 
     * @return boolean
     */
    public function save_rule_attrib($rule_key, $directive_id, $file, $attrib, $value)
    {
        $filepath = $this->engine_path."/".$file;
        $dom = self::get_xml($filepath, "DOM");
        $directive = $this->getDirectiveFromXML($dom, $directive_id);
        $tab_rules = $directive->rules;
        foreach ($tab_rules as $rule) {
            if ($rule->id == $rule_key) {
                // Array mode: multiple attributes
                if (is_array($attrib)) {
                    for ($i = 0; $i < count($attrib); $i++) {
                        $attrib_str = $attrib[$i];
                        $rule->$attrib_str = $value[$i];
                    }
                // Single mode
                } else {
                    $rule->$attrib = $value;
                }
            }
        }
        $direct = $directive->directive;
        if ($direct) {
            $rules = $direct->childNodes;
               foreach($rules as $rule) {
                    if (is_object($rule) && get_class($rule) == 'DOMElement' && $rule->nodeName == 'rule' && $rule->hasAttribute('name')) {
                        $direct->removeChild($rule);
                    }
               }
        }
        $this->_do_directive($directive, $tab_rules, $dom);
        return self::save_xml($filepath, $dom, "DOMXML");
    }
    
    /**
     * This function checks if we can remove a directive
     *
     * @param int    $dir_id
     *
     * @return Array
     */
    public function can_delete_directive($dir_id)
    {
        // Check if it belongs to a DS Group
        
        $msg    = _('Unable to delete directive. Please remove directive from the following DS Groups and try again. %GROUPS');
        
        $sql    = "SELECT g.name FROM plugin_group_descr gd, plugin_group g 
                   WHERE gd.group_id = g.group_id AND 
                         gd.plugin_id   = ? AND 
                        (gd.plugin_sid  = ? OR gd.plugin_sid LIKE ? OR gd.plugin_sid LIKE ? OR gd.plugin_sid LIKE ?)";
        $params = array(
                1505,
                $dir_id,
                "%,$dir_id",
                "$dir_id,%",
                "%,$dir_id,%"
        );
        
        $rs = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            $ds_groups = array();
            
            while (!$rs->EOF)
            {
                $ds_groups[] = $rs->fields['name'];
                
                $rs->MoveNext();
            }
            
            if (count($ds_groups) > 0)
            {
                $msg = str_replace('%GROUPS', implode(', ', $ds_groups), $msg);
                return array(FALSE, $msg);
            }
        }
        
        return array(TRUE, '');
    }
    
    /**
     * This function deletes a directive
     * 
     * @param int    $dir_id
     * @param string $file
     * 
     * @return boolean
     */
    public function delete_directive($dir_id, $file)
    {
        $filepath = (file_exists($this->engine_path."/".$file)) ? $this->engine_path."/".$file : $this->main_path."/".$file;
        $directive = NULL;
        $dom = self::get_xml($filepath, "DOMXML");
        
        $tab_directive = $dom->getElementsByTagName('directive');

        foreach($tab_directive as $lign) if ($lign->getAttribute('id') == $dir_id) $directive = $lign;
        
        if ($directive != NULL) {
            $parent = $directive->parentNode;
            $parent->removeChild($directive);
            if (self::save_xml($filepath, $dom, "DOMXML")) {
                $this->remove_directive_pluginsid($dir_id);
                $this->remove_directive_taxonomy($dir_id);
                $this->enable_directive($file, $dir_id);
                
                $infolog = array($dir_id);
                Log_action::log(87, $infolog);
                
                return TRUE; }} // These brakets are here because a coverture analyzer issue
        return FALSE;
    }
    
    /**
     * This function clones a directive
     * 
     * @param int    $dir_id
     * @param string $file
     * 
     * @return boolean
     */
    public function clone_directive($dir_id, $file)
    {
        $directive = NULL;
        
        $filepath = (file_exists($this->engine_path."/".$file)) ? $this->engine_path."/".$file : $this->main_path."/".$file;
        $dom = self::get_xml($filepath, "DOMXML");
        
        $directive = $this->getDirectiveFromXML($dom, $dir_id); 
        
        if ($directive != NULL)
        {
            $new_id = $this->new_directive_id($file);
            $new_directive = $dom->createElement('directive');
            $new_directive->setAttribute('id', $new_id);
            $new_directive->setAttribute('name', "Copy of " . $directive->name);
            $new_directive->setAttribute('priority', $directive->priority);
            $tab_rules = $directive->rules;
            for ($ind = 1; $ind <= count($tab_rules); $ind++)
            {
                $rule = $tab_rules[$ind];
                $_aux_id   = explode("-", $rule->id);
                $id_rule   = $_aux_id[1];
                $id_father = $_aux_id[2];
                if ($id_father == 0) {
                    $new_node = $tab_rules[$id_rule]->getXMLNode($dom);
                    $new_node = $new_directive->appendChild($new_node);
                    $tab_rules[$id_rule]->rule = $new_node;
                    if ($tab_rules[$id_rule]->nb_child > 0) {
                        $this->_do_rules($id_rule, $tab_rules, $dom);
                    }
                }
            }
            $parent = $directive->directive->parentNode;
            $parent->appendChild($new_directive);
            if (self::save_xml($filepath, $dom, "DOMXML")) {
                    $this->update_directive_pluginsid($new_id, 2, $directive->priority, $directive->name);
                    list($directive_intent, $directive_strategy, $directive_method) = $this->get_directive_intent($dir_id, "array");
                    $this->update_directive_taxonomy($new_id, $directive_intent, $directive_strategy, $directive_method);
                    return TRUE; }}
        return FALSE;
    }
    
    /**
     * This function clones a directive to user.xml
     * 
     * @param int    $dir_id
     * @param string $file
     * 
     * @return boolean
     */
    public function clone_directive_touser($dir_id, $file)
    {    
        $filepath = (file_exists($this->engine_path."/".$file)) ? $this->engine_path."/".$file : $this->main_path."/".$file;
        $dom_av = self::get_xml($filepath, "DOMXML");
        $directive = $this->getDirectiveFromXML($dom_av, $dir_id);
        
        $user_file = (file_exists($this->engine_path."/user.xml")) ? $this->engine_path."/user.xml" : $this->main_path."/user.xml";
        $dom = self::get_xml($user_file, "DOMXML");
        
        if ($directive != NULL) {
            $new_id = $this->new_directive_id("user.xml");
            $new_directive = $dom->createElement('directive');
            $new_directive->setAttribute('id', $new_id);
            $new_directive->setAttribute('name', $directive->name);
            $new_directive->setAttribute('priority', $directive->priority);
            $tab_rules = $directive->rules;
            for ($ind = 1; $ind <= count($tab_rules); $ind++) {
                $rule      = $tab_rules[$ind];
                $_id_aux   = explode("-", $rule->id);
                $id_rule   = $_id_aux[1];
                $id_father = $_id_aux[2];
                if ($id_father == 0) {
                    $new_node = $tab_rules[$id_rule]->getXMLNode($dom);
                    $new_node = $new_directive->appendChild($new_node);
                    $tab_rules[$id_rule]->rule = $new_node;
                    if ($tab_rules[$id_rule]->nb_child > 0) {
                        $this->_do_rules($id_rule, $tab_rules, $dom);
                    }
                }
            }
                $dom->appendChild($new_directive);
            if (self::save_xml($user_file, $dom, "DOMXML")) {
                $this->disable_directive($file, $dir_id);
                $this->update_directive_pluginsid($new_id, 2, $directive->priority, $directive->name);
                list($directive_intent, $directive_strategy, $directive_method) = $this->get_directive_intent($dir_id, "array");
                $this->update_directive_taxonomy($new_id, $directive_intent, $directive_strategy, $directive_method);
                
                $infolog = array($new_id);
                Log_action::log(85, $infolog);
                
                return TRUE; }}
        return FALSE;
    }
    
    /**
     * This function creates a directive recursive
     * 
     * @param object $directive
     * @param array  $tab_rules
     * @param object $dom
     */
    private function _do_directive($directive, &$tab_rules, $dom)
    {
        $direct = $directive->directive;
        for ($ind = 1; $ind <= count($tab_rules); $ind++) {
            $rule = $tab_rules[$ind];
            $_aux_id   = explode("-", $rule->id);
            $id_rule   = $_aux_id[1];
            $id_father = $_aux_id[2];
            if ($id_father == 0) {
                $node = $tab_rules[$id_rule]->getXMLNode($dom);
                $node = $direct->appendChild($node);
                $tab_rules[$id_rule]->rule = $node;
                if ($tab_rules[$id_rule]->nb_child > 0) {
                    $this->_do_rules($id_rule, $tab_rules, $dom);
                }
            }
        }
    }
    
    /**
     * This function creates a rule and children recursive
     * 
     * @param int    $id_rule
     * @param array  $tab_rules
     * @param object $dom
     */
    private function _do_rules($id_rule, &$tab_rules, $dom)
    {
        $father = $tab_rules[$id_rule]->rule;
        $rules = $father->childNodes;
        foreach($rules as $rule) {
            if (($rule->nodeType == XML_ELEMENT_NODE) && ($rule->tagName == 'rules')) {
                for ($i = 1; $i <= count($tab_rules); $i++) {
                    $lign = $tab_rules[$i];
                    $_aux_id   = explode("-", $lign->id);
                    $id_rule2  = $_aux_id[1];
                    $id_father = $_aux_id[2];
                    if ($id_father == $id_rule) {
                        $node = $tab_rules[$id_rule2]->getXMLNode($dom);
                        $node = $rule->appendChild($node);
                        $tab_rules[$id_rule2]->rule = $node;
                        if ($tab_rules[$id_rule2]->nb_child > 0) {
                            $this->_do_rules($id_rule2, $tab_rules, $dom);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * This function inserts a row into plugin_sid table, used in properties
     * 
     * @param int    $directive_id
     * @param int    $rel
     * @param int    $prio
     * @param string $name
     */
    public function update_directive_pluginsid($directive_id, $rel = 2, $prio = 2, $name = "directive_alert: Generic event")
    {
        $sql = "REPLACE INTO plugin_sid (plugin_ctx, plugin_id, sid, name, reliability, priority) VALUES (UNHEX(?), ?, ?, ?, ?, ?)";
        $params = array(
            strtoupper(str_replace("-", '', $this->engine_id)),
            1505,
            $directive_id,
            $name,
            $rel,
            $prio
        );
        
        $rs = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
    }
    
    /**
     * This function updates the directive taxonomy
     * 
     * @param int $directive_id
     * @param int $kingdom
     * @param int $category
     * @param int $subcategory
     */
    public function update_directive_taxonomy($directive_id, $kingdom, $category, $subcategory)
    {
        $sql = "REPLACE INTO alarm_taxonomy (sid, engine_id, kingdom, category, subcategory) VALUES (?, UNHEX(?), ?, ?, ?)";
        $params = array(
                $directive_id,
                str_replace("-", '', $this->engine_id),
                $kingdom,
                $category,
                $subcategory
        );
        
        $rs = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
    }
    
    /**
     * This function removes a row from plugin_sid table
     * 
     * @param int $directive_id
     */
    public function remove_directive_pluginsid($directive_id)
    {
        $sql = "DELETE FROM plugin_sid WHERE plugin_id = ? AND sid = ?";
        $params = array(
            1505,
            $directive_id
        );
        
        $rs = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        $sql = "DELETE FROM datawarehouse.category WHERE sid = ?";
        $params = array(
            $directive_id
        );
        
        $rs = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
    }
    
    /**
     * This function removes a directive taxonomy data
     * 
     * @param int $directive_id
     */
    public function remove_directive_taxonomy($directive_id)
    {
        $sql = "DELETE FROM alarm_taxonomy WHERE sid = ? AND engine_id = UNHEX(?)";
        $params = array(
                $directive_id,
                str_replace("-", '', $this->engine_id)
        );
        
        $rs = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
    }
    
    /**
     * This function enables a directive
     * 
     * @param string $file
     * @param int    $dir_id
     */
    public function enable_directive($file, $dir_id)
    {
        $disabled_file = $this->engine_path.'/disabled_directives.data';
        $lines = (file_exists($disabled_file)) ? file($disabled_file) : array();
        $f = fopen($disabled_file,"w");
        foreach ($lines as $line) if (preg_match("/(.+)\;(.+)/",trim($line),$found)) {
            if ($found[1] == $dir_id && $found[2] == $file) { continue; }
            fputs($f,"$line");
        }
        fclose($f);

        Web_indicator::set_on( "Reload_directives" );
    }
    
    /**
     * This function disables a directive
     * 
     * @param string $file
     * @param int    $dir_id
     */
    public function disable_directive($file, $dir_id)
    {
        $disabled_file = $this->engine_path.'/disabled_directives.data';
        $lines = (file_exists($disabled_file)) ? file($disabled_file) : array();
        $f = fopen($disabled_file,"w");
        $add = true;
        foreach ($lines as $line) if (preg_match("/(.+)\;(.+)/",trim($line),$found)) {
            if ($found[1] == $dir_id && $found[2] == $file) { $add = false; }
            fputs($f,"$line");
        }
        if ($add) {
            fputs($f,"$dir_id;$file\n");
        }
        fclose($f);

        Web_indicator::set_on( "Reload_directives" );
    }
    
    /**
     * This function inserts a new rule into the XML file
     * 
     * @param object $rule
     * @param int    $directive_id
     * @param string $XML_FILE
     * 
     * @return boolean
     */
    function insert($rule, $directive_id, $XML_FILE)
    {
        list($id_dir, $id_rule, $id_father) = explode("-", $rule->id);
        $dom = $this->get_xml($XML_FILE, "DOMXML");
        $directive = $this->getDirectiveFromXML($dom, $directive_id);
        $tab_rules = $directive->rules;
        if ($id_father != 0) $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child + 1;
        
        $tab_rules[$id_rule] = $rule;
        
        /* Deprecated code
        if ($rule->id == $tab_rules[$id_rule]->id || $id_rule > count($tab_rules)) {
            $tab_rules[$id_rule] = $rule;
        } else {
            for ($i = count($tab_rules); $i >= $id_rule; $i--) {
                $_aux_id    = explode("-", $tab_rules[$i]->id);
                $id_dir     = $_aux_id[0];
                $id_father2 = $_aux_id[2];
                $new_id_rule = $i + 1;
                if ($id_father2 >= $id_rule) $new_id_father = $id_father2 + 1;
                else $new_id_father = $id_father2;
                $newid = $id_dir . "-" . $new_id_rule . "-" . $new_id_father;
                $tab_rules[$i]->id = $newid;
                $tab_rules[$i + 1] = $tab_rules[$i];
            }
            $tab_rules[$id_rule] = $rule;
        }
        */
        
        $direct = $directive->directive;
        $rules = array();
        $myDOMNodeList = $direct->childNodes;
        $i = 0; if (isset($myDOMNodeList)) while ($node = $myDOMNodeList->item($i++)) {
            $node->myOwnerDocument = $direct->myOwnerDocument;
            $rules[] = $node;
            //$rules[] = new DOMElement($node, $direct->myOwnerDocument);
        }
        foreach($rules as $rule) {
            $direct->removeChild($rule);
        }
        
        $this->_do_directive($directive, $tab_rules, $dom);
        return $this->save_xml($XML_FILE, $dom, "DOMXML");
    }
    
    /**
     * This function removes a rule from the DOM object
     * 
     * @param int   $id
     * @param array $tab_rules
     */
    public function delrule($id, &$tab_rules)
    {
        list($id_dir, $id_rule, $id_father) = explode("-", $id);
        $rule = $tab_rules[$id_rule]->rule;
        
        $nb = 0;
        $i = $id_rule + 1;
        do {
            $_aux_id    = explode("-", $tab_rules[$i]->id);
            $id_father2 = $_aux_id[2];
            $nb++;
            $i++;
        }
        while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
        for ($i = $id_rule; $i < count($tab_rules) - $nb; $i++) {
            $tab_rules[$i] = $tab_rules[$i + $nb];
        }
        for ($j = 0; $j < $nb; $j++) {
            array_pop($tab_rules);
        }
        for ($j = 1; $j <= count($tab_rules); $j++) {
            list($id_dir, $id_rule, $id_father) = explode("-", $tab_rules[$j]->id);
            $new_id_rule = $j;
            $newid = $id_dir . "-" . $new_id_rule . "-" . $id_father;
            $tab_rules[$j]->id = $newid;
        }
        $sup = 1;
        foreach($tab_rules as $lign) {
            if ($lign->level >= $sup) {
                $sup = $lign->level;
            }
        }
         
        $parent = $rule->parentNode;
        //$res = $parent->removeChild($rule);
        $parent->removeChild($rule);
         
        // Special case: removed all rules of a level. It rests <rules>, </rules> as childs. We must remove them too...
        // This code leaves <rules/> tag, in method release_file will be removed finally
        if ($parent->childNodes->length == 2) {
            $nodesToDelete = array();
            foreach($parent->childNodes as $child) {
                $nodesToDelete[] = $child;
            }
            foreach($nodesToDelete as $node)
            {
                $node->parentNode->removeChild($node);
            }
        }
    }
    
    /**
     * This function moves a rule to the left
     * 
     * @param object $dom
     * @param int    $id
     * @param array  $tab_rules
     * @param object $directive
     */
    public function left($dom, $id, &$tab_rules, $directive)
    {
        list($id_dir, $id_rule, $id_father) = explode("-", $id);
        $rule = $tab_rules[$id_rule]->rule;
        if ($tab_rules[$id_rule]->level > 2)
        {
            $tab_rules[$id_rule]->level = $tab_rules[$id_rule]->level - 1;
            list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$id_father]->id);
            $newid = $id_dir . "-" . $id_rule . "-" . $id_father2;
            $tab_rules[$id_rule]->id = $newid;
            $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child - 1;
            $i = $id_rule + 1;
            do {
                $_aux_id    = explode("-", $tab_rules[$i]->id);
                $id_rule2   = $_aux_id[1];
                $id_father2 = $_aux_id[2];
                if ($id_father2 > $id_father) $tab_rules[$i]->level = $tab_rules[$i]->level - 1;
                $i++;
            }
            while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
            $direct = $directive->directive;
            $rules = array();
            $myDOMNodeList = $direct->childNodes;
            $i = 0; if (isset($myDOMNodeList)) while ($node = $myDOMNodeList->item($i++)) {
                $node->myOwnerDocument = $direct->myOwnerDocument;
                $rules[] = $node;
                //$rules[] = new DOMElement($node, $direct->myOwnerDocument);
            }
            foreach($rules as $rule) 
            {
                $direct->removeChild($rule);
            }
            $this->_do_directive($directive, $tab_rules, $dom);
        }
        else
        {            
            $exp_msg = _('Not allowed for this rule');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    /**
     * This function moves a rule to the right
     * 
     * @param object $dom
     * @param int    $id
     * @param array  $tab_rules
     * @param object $directive
     */
    public function right($dom, $id, &$tab_rules, $directive)
    {
        list($id_dir, $id_rule, $id_father) = explode("-", $id);
        if ($id_father == 0) $nbc = $directive->nb_child;
        else $nbc = $tab_rules[$id_father]->nb_child;
        $ind = 0;
        if (($id_rule > 1) && ($nbc > 1)) {
            $stock = array();
            for ($i = 1; $i <= count($tab_rules); $i++) {
                list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
                if ($id_father2 == $id_father) $stock[] = $tab_rules[$i]->id;
            }
            while ($id != $stock[$ind]) {
                $ind++;
            }
        }
        if ($ind > 0)
        {
            list($id_dir, $id_rule2, $id_father2) = explode("-", $stock[$ind - 1]);
            $rule = $tab_rules[$id_rule]->rule;
            $tab_rules[$id_rule]->level = $tab_rules[$id_rule]->level + 1;
            
            /* Deprecated id_father 0 never moves to right
            if ($id_father != 0) $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child - 1;
            else $directive->nb_child = $directive->nb_child - 1;
            */
            $tab_rules[$id_father]->nb_child = $tab_rules[$id_father]->nb_child - 1;
            
            $tab_rules[$id_rule2]->nb_child = $tab_rules[$id_rule2]->nb_child + 1;
            $newid = $id_dir . "-" . $id_rule . "-" . $id_rule2;
            $tab_rules[$id_rule]->id = $newid;
            if ($id_rule < count($tab_rules)) {
                $i = $id_rule + 1;
                do {
                    $_aux_id    = explode("-", $tab_rules[$i]->id);
                    $id_rule2   = $_aux_id[1];
                    $id_father2 = $_aux_id[2];
                    if ($id_father2 > $id_father) $tab_rules[$i]->level = $tab_rules[$i]->level + 1;
                    $i++;
                }
                while (($id_father2 > $id_father) && ($i <= count($tab_rules)));
            }
            $direct = $directive->directive;
    
            $rules = array();
            $myDOMNodeList = $direct->childNodes;
            $i = 0; if (isset($myDOMNodeList)) while ($node = $myDOMNodeList->item($i++)) {
                $node->myOwnerDocument = $direct->myOwnerDocument;
                $rules[] = $node;
                //$rules[] = new DOMElement($node, $direct->myOwnerDocument);
            }
            foreach($rules as $rule) {
                $direct->removeChild($rule);
            }
    
            $this->_do_directive($directive, $tab_rules, $dom);
        }
        else
        {            
            $exp_msg = _('Not allowed for this rule');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    /**
     * This function moves a rule to up
     * 
     * @param object $dom
     * @param int    $id
     * @param array  $tab_rules
     * @param object $directive
     */
    public function up($dom, $id, &$tab_rules, $directive)
    {
        list($id_dir, $id_rule, $id_father) = explode("-", $id);
        if ($id_father == 0) $nbc = $directive->nb_child;
        else $nbc = $tab_rules[$id_father]->nb_child;
        if (($id_rule > 1) && ($nbc > 1)) {
            $stock = array();
            for ($i = 1; $i <= count($tab_rules); $i++) {
                list($id_dir, $id_rule2, $id_father2) = explode("-", $tab_rules[$i]->id);
                if ($id_father2 == $id_father) $stock[] = $tab_rules[$i]->id;
            }
            $i = 0;
            while ($i < count($stock)) {
                if ($stock[$i] == $id) $ind = $i;
                $i++;
            }
            if ($ind > 0)
            {
                list($id_dir, $id_rule2, $id_father2) = explode("-", $stock[$ind - 1]);
                $prof = 0;
                if ($id_rule < count($tab_rules)) {
                    $i = $id_rule + 1;
                    do {
                        list($id_dir, $id_rule3, $id_father3) = explode("-", $tab_rules[$i]->id);
                        if ($id_father3 > $id_father) $prof++;
                        $i++;
                    }
                    while (($id_father3 > $id_father) && ($i <= count($tab_rules)));
                }
                $tab_rules[$id_rule]->id = $stock[$ind - 1];
                list($id_dir, $id_rule2, $id_father2) = explode("-", $stock[$ind - 1]);
                $newniv = $id_rule2 + $prof + 1;
                $newid = $id_dir . "-" . $newniv . "-" . $id_father2;
                $tab_rules[$id_rule2]->id = $newid;
                $temp = array();
                $diff = $id_rule - $id_rule2;
                for ($i = 0; $i < $diff; $i++) {
                    $temp[$i] = $tab_rules[$id_rule2 + $i];
                }
                $tab_rules[$id_rule2] = $tab_rules[$id_rule];
                for ($i = 1; $i <= $prof; $i++) {
                    $j = $id_rule + $i - $diff;
                    $tab_rules[$j] = $tab_rules[$j + $diff];
                    list($id_dir, $id_rule3, $id_father3) = explode("-", $tab_rules[$j]->id);
                    $idf = $id_father3 - $diff;
                    $newid = $id_dir . "-" . $j . "-" . $idf;
                    $tab_rules[$j]->id = $newid;
                }
                $tab_rules[$id_rule2 + $prof + 1] = $temp[0];
                for ($i = 1; $i < count($temp); $i++) {
                    $j = $id_rule2 + $prof + 1 + $i;
                    $tab_rules[$j] = $temp[$i];
                    list($id_dir, $id_rule3, $id_father3) = explode("-", $temp[$i]->id);
                    $idf = $id_father3 + $prof + 1;
                    $newid = $id_dir . "-" . $j . "-" . $idf;
                    $tab_rules[$j]->id = $newid;
                }
                $direct = $directive->directive;
                $rules = array();
                $myDOMNodeList = $direct->childNodes;
                $i = 0; if (isset($myDOMNodeList)) while ($node = $myDOMNodeList->item($i++)) {
                    $node->myOwnerDocument = $direct->myOwnerDocument;
                    $rules[] = $node;
                    //$rules[] = new DOMElement($node, $direct->myOwnerDocument);
                }
                foreach($rules as $rule) {
                    $direct->removeChild($rule);
                }
                $this->_do_directive($directive, $tab_rules, $dom);
            }
            else
            {
                $exp_msg = _('Not allowed for this rule');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        else
        {
            $exp_msg = _('Not allowed for this rule');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    /**
     * This function moves a rule to down
     * 
     * @param object $dom
     * @param int    $id
     * @param array  $tab_rules
     * @param object $directive
     */
    public function down($dom, $id, &$tab_rules, $directive)
    {
        $_id_aux   = explode("-", $id);
        $id_rule   = $_id_aux[1];
        $id_father = $_id_aux[2];
        if ($id_father == 0) $nbc = $directive->nb_child;
        else $nbc = $tab_rules[$id_father]->nb_child;
        if (($id_rule < count($tab_rules)) && ($nbc > 1)) {
            $stock = array();
            for ($i = 1; $i <= count($tab_rules); $i++) {
                $_aux_id    = explode("-", $tab_rules[$i]->id);
                $id_father2 = $_aux_id[2];
                if ($id_father2 == $id_father) $stock[] = $tab_rules[$i]->id;
            }
            $i = 0;
            while ($i < count($stock)) 
            {
                if ($stock[$i] == $id) $ind = $i;
                $i++;
            }
            if ($ind < count($stock) - 1) 
            {
                $this->up($dom, $stock[$ind + 1], $tab_rules, $directive);
            }
            
        }
        else
        {
            $exp_msg = _('Not allowed for this rule');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    /**
     * This function gets the directive intent data
     * 
     * @param int    $dir_id
     * @param string $format
     * 
     * @return string|array
     */
    public function get_directive_intent($dir_id, $format = "string")
    {
        $sql = "SELECT alarm_kingdoms.name as intent, alarm_categories.name as strategy, alarm_taxonomy.subcategory as method,
                alarm_kingdoms.id as intent_id, alarm_categories.id as strategy_id 
                FROM alarm_taxonomy, alarm_kingdoms, alarm_categories 
                WHERE alarm_taxonomy.kingdom = alarm_kingdoms.id AND alarm_taxonomy.category = alarm_categories.id AND alarm_taxonomy.sid = ?
                AND alarm_taxonomy.engine_id = UNHEX(?)";
        
        $params = array($dir_id, strtoupper(str_replace("-", '', $this->engine_id)));
        
        $rs     = $this->conn->Execute($sql, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                return ($format == "string") ? $rs->fields['intent'].", ".$rs->fields['strategy'].", ".$rs->fields['method'] : array($rs->fields['intent_id'], $rs->fields['strategy_id'], $rs->fields['method']);
            }
        }
        
        return ($format == 'string') ? '' : array(0, 0, '');
    }
    
    /**
     * This function gets the intent data list
     * 
     * @return array
     */
    public function get_intent_list()
    {
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $list = array();
        $sql  = "SELECT * FROM alarm_kingdoms";
    
        $rs   = $conn->Execute($sql);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields['id']] = $rs->fields['name'];
                $rs->MoveNext();
            }
        }
        
        $db->close();
        
        return $list;
    }
    
    /**
     * This function gets the strategy data list
     * 
     * @return array
     */
    public function get_strategy_list()
    {
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $list = array();
        $sql  = "SELECT * FROM alarm_categories";
    
        $rs   = $conn->Execute($sql);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields['id']] = $rs->fields['name'];
                $rs->MoveNext();
            }
        }
    
        $db->close();
        
        return $list;
    }
    
    /**
     * This function gets the SimpleXML or DOM Object from a XML file
     * 
     * @param string $file
     * @param string $mode
     * 
     * @return boolean|SimpleXMLElement|DOMDocument
     */
    public function get_xml($file, $mode="SimpleXML")
    {
        if (file_exists($file)) 
        {
            $string = file_get_contents($file);
            // Add '<directives>' root node (patch for simplexml_load_string FALSE returns)
            if (preg_match("/\<directive id/", $string)) 
            {
                $string = preg_replace("/(\<\?xml version[^\>]+\>)/", "\\1\n<directives>", $string)."</directives>";
            // Empty file with xml header
            } 
            elseif ($string != '' && preg_match("/(\<\?xml version[^\>]+\>)/", $string) 
                && !preg_match("/\<\?xml version[^\>]+\>.*\</", $string) && !preg_match("/categories\.xml/", $file)) 
            {
                $string = preg_replace("/(\<\?xml version[^\>]+\>)/", "\\1\n<directives>", $string)."</directives>";
            // Empty file
            } 
            elseif ($string == '') 
            {
                $string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<directives>\n</directives>";
            }
            // SimpleXML
            if ($mode == "SimpleXML") 
            {
                $xml = simplexml_load_string($string);
                if (!$xml) 
                {
                    $exp_msg = _("Error loading XML from file").": <b>$file</b><br>";
                    foreach(libxml_get_errors() as $error) 
                    {
                        $exp_msg .= $error->message."<br>";
                    }
                    Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                } 
                else 
                {
                    return $xml;
                }
            // DOMXML
            } 
            else 
            {
                $xml = new DOMDocument();
                if (!$xml->loadXML($string)) 
                {
                    $exp_msg = _("Error loading XML from file").": <b>$file</b><br>";
                    foreach(libxml_get_errors() as $error) 
                    {
                        $exp_msg .= $error->message."<br>";
                    }
                    
                    Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                } 
                else 
                {
                    return $xml;
                }
            }
        } 
        else 
        {            
            $exp_msg = _('File not found').': '.$file;
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
     * This function saves the SimpleXML or DOM Object as string into a XML file
     * 
     * @param string  $file
     * @param object  $xml
     * @param string  $mode
     * @param boolean $dtdvalid
     * 
     * @return boolean
     */
    public function save_xml($file, $xml, $mode = "SimpleXML", $dtdvalid = true)
    {
        // SimpleXML mode:
        if ($mode == "SimpleXML")
        {
            $xml_string = $xml->asXML();
            if ($xml_string != '')
            {
                $xml_string = str_replace("<directives>", '', $xml_string);
                $xml_string = str_replace("</directives>", '', $xml_string);
                
                try
                {
                    $f = fopen($file, "w");
                    fputs($f, $xml_string);
                    fclose($f);
                    Web_indicator::set_on( "Reload_directives" );
                    return TRUE;
                }
                catch (Exception $e)
                {
                    return FALSE; 
                }
            }
        } // Brakets like that because a phpUnit coverage issue
        // DOMXML mode:
        else
        {
            // Get original to restore if there's any error
            $xml_backup = self::get_xml($file, "DOMXML");
            // Save
            $xml->save($file);
            self::release_file($file);
            // Check DTD
            if ($dtdvalid && file_exists($this->main_path."/directives.dtd"))
            {
                $dtd_lines = file($this->main_path."/directives.dtd");
                // Prepare xml for validation
                $string = file_get_contents($file);
                if (preg_match("/\<directive id/", $string)) 
                {
                    $string = preg_replace("/(\<\?xml version[^\>]+\>)/", "\\1\n<directives>", $string)."</directives>";
                }
                elseif ($string != '' && preg_match("/(\<\?xml version[^\>]+\>)/", $string) && !preg_match("/\<\?xml version[^\>]+\>.*\</", $string) && !preg_match("/categories\.xml/", $file))
                {
                    $string = preg_replace("/(\<\?xml version[^\>]+\>)/", "\\1\n<directives>", $string)."</directives>";
                }
                /* File is never empty
                elseif ($string == '')
                {
                    $string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<directives>\n</directives>";
                }
                */
                
                $doc = new DOMDocument;
                $xml_lines = explode("\n", $string);
                $new_lines = array();
                // Add DTD lines
                foreach ($xml_lines as $x) {
                    $new_lines[] = $x;
                    if (preg_match('/^\<\?xml/', $x)) {
                        $new_lines[] = '<!DOCTYPE directives SYSTEM "'.$this->main_path.'/directives.dtd">';
                        foreach ($dtd_lines as $dtd_line) if (!preg_match("/^\<\?xml/", $dtd_line)) {
                            //$new_lines[] = $dtd_line;
                        }
                    }
                }
                 
                $doc->loadXML(implode("\n", $new_lines));
                // Validate with DTD
                if (!@$doc->validate()) {
                    // Discomment for debug:
                    //echo _("Unable to write the file, DTD validation failed on")." '$file' Parsed XML: ".implode("\n", $new_lines);
                    // Restore saved backup xml
                    $xml_backup->save($file);
                    self::release_file($file);
                    return FALSE;
                }
            }
            Web_indicator::set_on( "Reload_directives" );
            return TRUE; }} // Brakets like that because a phpUnit coverage issue
    
    /**
     * This function gets the available engines having directives
     * 
     * @return array
     */
    public function get_available_engines()
    {
        $engines = array();
        if (Session::is_pro()) {
            $dir = opendir($this->main_path);
            if ($dir) {
                while (false !== ($subdir = readdir($dir))) {
                    if (preg_match("/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/", $subdir) && Acl::entityAllowed(str_replace("-", '', strtoupper($subdir)))) {
                        $entity = Acl::get_entity($this->conn, str_replace("-", '', strtoupper($subdir)), false, false);
                        if ($entity['type'] == "engine" && $entity['name'] != '' && $entity['name'] != "Unknown entity") {
                            $engines[$subdir] = $entity['name'];
                        }
                    }
                }
                closedir($dir);
            }
        } else {
            require_once ('ossim_conf.inc');
            $conf = $GLOBALS["CONF"];
            $default_engine = $conf->get_conf("default_engine_id", false);
            $engines[$this->main_path."/".$default_engine] = _("Default");
        }
        return $engines;
    }
    
    /**
     * This function initializes the XML files for a new engine
     * 
     * @param string $uuid
     */
    public static function init_engine($uuid)
    {    
        mkdir(_MAIN_PATH."/$uuid");
        
        // User directives
        $f = fopen(_MAIN_PATH."/$uuid/user.xml", "w");
        fclose($f);
        
        $path = (Session::is_pro()) ? "/usr/share/alienvault-directives-pro/d_clean/templates/" : "/usr/share/alienvault-directives-free/d_clean/templates/";
        
        if (file_exists($path."directives.xml") && file_exists($path."user.xml"))
        {
                copy($path."directives.xml", _MAIN_PATH."/$uuid/directives.xml");
                copy($path."user.xml", _MAIN_PATH."/$uuid/user.xml");
        }
        else
        {
            // Create clean directives.xml
                $f = fopen(_MAIN_PATH."/$uuid/directives.xml", "w");
                fputs($f, '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE directives
SYSTEM \''._MAIN_PATH.'/directives.dtd\'
[
<!ENTITY user SYSTEM \'user.xml\'>
]>
<directives>

&user;

<groups>
<group name="GroupTest1">
<append-directive directive_id="1"/>
</group>
</groups>

</directives>');
            fclose($f);
            $f = fopen(_MAIN_PATH."/$uuid/user.xml", "w");
            fputs($f, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
            fclose($f);
        }
    }
    
    /**
     * This function removes a engine with its XML files
     * 
     * @param string $uuid
     */
    public static function remove_engine($uuid)
    {
        $files = scandir(_MAIN_PATH."/$uuid");
        foreach ($files as $_file) if (preg_match("/\.xml$/", $_file))
        {
            unlink(_MAIN_PATH."/$uuid/$_file");
        }
        rmdir(_MAIN_PATH."/$uuid");
    }
    
    /**
     * This function returns the directives disabled
     * 
     * @return array
     */
    public function get_disabled_directives()
    {
        $ret = array();
        $file = (file_exists($this->main_path.'/'.$this->engine_id.'/disabled_directives.data')) ? $this->main_path.'/'.$this->engine_id.'/disabled_directives.data' : $this->main_path.'/disabled_directives.data';
        $lines = (file_exists($file)) ? file($file) : array();
        foreach ($lines as $line) {
            if (preg_match("/(.+)\;(.+)/",$line,$found)) {
                $ret[trim($found[2])][trim($found[1])]++;
            }
        }
        return $ret;
    }
    
    /**
    ******************************** DOM Methods *********************************
     * Imported from version 3 of Ossim. Needed for moving rules and other features
     * The php4 to php5 conversion has been removed 
    ###########################################################################*/
    
    /**
     * This function initializes the file by adding xml tags at the beginning and at the end
     * 
     * @param string $xml_file
     */
    public function init_file($xml_file)
    {
        $tab = file($xml_file);
        if (file_exists($xml_file)) unlink($xml_file);
        $fic = fopen($xml_file, 'w');
        if ($fic) {
            fwrite($fic, $tab[0]);
            fwrite($fic, "<directives>");
            for ($i = 1; $i < count($tab); $i++) {
                fwrite($fic, $tab[$i]);
            }
            fwrite($fic, "</directives>");
            fclose($fic);
        }
    }
    
    /**
     * This function removes xml tags and indent the file
     * 
     * @param string $xml_file
     */
    public function release_file($xml_file)
    {
        $tab = file($xml_file);
        unlink($xml_file);
        $fic = fopen($xml_file, 'w');
        $max = count($tab) - 1;
        while ((trim($tab[$max]) == '' || trim($tab[$max]) == "</directives>") && $max >= 0) {
            $max--;
        }
        if ($fic) {
            //$nb = count($tab);
            for ($i = 0; $i <= $max; $i++) {
                if (trim($tab[$i]) != "</directives>") {
                    $string = str_replace("<directives>", '', $tab[$i]);
                    $string = str_replace("</directives>", '', $string);
                    $string = str_replace("><", ">\n<", $string);
                    fwrite($fic, $string);
                }
            }
        }
        fclose($fic);
        $tab = file($xml_file);
        unlink($xml_file);
        $fic = fopen($xml_file, 'w');
        $nb_space = 0;
        $previous = '';
        for ($i = 0; $i < count($tab); $i++) {
            
            // This is a patch, there's no way to delete all <rules>...</rules> when all rules are deleted into a level (see delrule method)
            $tab[$i] = str_replace("<rules/>", '', $tab[$i]);
            
            if (strcmp("<rules>", trim($tab[$i])) == 0) $nb_space++;
            elseif (strncmp("<rule ", trim($tab[$i]) , 6) == 0 && strncmp("<rule ", $previous, 6) != 0 && strcmp("</rule>", $previous) != 0) $nb_space++;
            elseif (strcmp("</rule>", trim($tab[$i])) == 0 && strncmp("<rule ", $previous, 6) != 0) $nb_space--;
            elseif (strcmp("</rules>", trim($tab[$i])) == 0 && strcmp("<rules>", $previous) != 0) $nb_space--;
            elseif (strcmp("</directive>", trim($tab[$i])) == 0 || strncmp("<directive ", $previous, 11) == 0) $nb_space = 0;
            $previous = trim($tab[$i]);
            $space = '';
            for ($j = 0; $j < $nb_space; $j++) $space = $space . "   ";
            if (trim($tab[$i]) != '') {
                $string = $space . trim($tab[$i]) . "\n";
                if (strcmp("</directive>", trim($string)) == 0 || strncmp("<?xml ", trim($string) , 6) == 0) $string = $string . "\n";
                fwrite($fic, $string);
            }
        }
        fclose($fic);
    }
    
    /**
     * This function returns if a directive exists into a XML file
     * 
     * @param string $directive_name
     * @param string $filepath
     * 
     * @return boolean
     */
    public function directive_exists($directive_name, $filepath)
    {
        $exists = false;
        $lines = (file_exists($filepath)) ? file($filepath) : array();
        foreach ($lines as $line) 
        {
            if (preg_match("/\<directive .* name\=\"".quotemeta($directive_name)."\"/i",$line)) 
            {
                $exists = true;
            }
        }
        return $exists;
    }
    
    /**
     * This function restart server directives configuration
     * 
     * @return array Error msg if there was an error
     */
    // DEPRECATED: Now using /conf/reload.php?what=directives
    static function restart_directives()
    {
        Util::execute_command('sudo /etc/init.d/ossim-server restart > /dev/null 2>&1 &');
        
        Web_indicator::set_off("Reload_directives");
        
        $return['error'] = FALSE;
        $return['msg']   = '';
        
        return $return;
    }
    
    /**
     * This function tests the XML integrity by a DTD file
     * 
     * @param string $default_engine
     */
    public function test($engine_id)
    {
        $errors = array();
        
        $dtd_file = (file_exists(_MAIN_PATH."/directives.dtd")) ? _MAIN_PATH."/directives.dtd" : '';
        
        $directive_editor = new self($engine_id);
        $categories = $directive_editor->get_categories();
        foreach ($categories as $category) {
            $file = (file_exists($directive_editor->engine_path."/".$category['xml_file'])) ? $directive_editor->engine_path."/".$category['xml_file'] : $directive_editor->main_path."/".$category['xml_file'];
            $string = file_get_contents($file);
            if (preg_match("/\<directive id/", $string)) {
                $string = preg_replace("/(\<\?xml version[^\>]+\>)/", "\\1\n<directives>", $string)."</directives>";
            } elseif ($string != '' && preg_match("/(\<\?xml version[^\>]+\>)/", $string) && !preg_match("/\<\?xml version[^\>]+\>.*\</", $string) && !preg_match("/categories\.xml/", $file)) {
                $string = preg_replace("/(\<\?xml version[^\>]+\>)/", "\\1\n<directives>", $string)."</directives>";
            } elseif ($string == '') {
                $string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<directives>\n</directives>";
            }
            // SimpleXML
            $xml = simplexml_load_string($string);
            $error_str = '';
            if (!$xml) {
                foreach(libxml_get_errors() as $error) {
                    $error_str .= str_replace("\n", "<br>", $error->message)."<br>";
                }
                $errors[] = _("XML Parsing error from file").": <b>$file</b><br>".$error_str;
            }
            // DTD
            if ($dtd_file != '') {
                $dtd_lines = file($dtd_file);
                $doc = new DOMDocument;
                $xml_lines = explode("\n", $string);
                $new_lines = array();
                foreach ($xml_lines as $x) {
                    $new_lines[] = $x;
                    if (preg_match('/^\<\?xml/', $x)) {
                        $new_lines[] = '<!DOCTYPE directives SYSTEM "'.$dtd_file.'">';
                        foreach ($dtd_lines as $dtd_line) if (!preg_match("/^\<\?xml/", $dtd_line)) {
                            //$new_lines[] = $dtd_line;
                        }
                    }
                }
                
                $doc->loadXML(implode("\n", $new_lines));

                if (@$doc->validate()) {
                    
                } else {
                    $error_str = '';
                    foreach (libxml_get_errors() as $error) {
                        $error_str .= str_replace("\n", "<br>", $error->message)."<br>";
                    }
                    $errors[] = _("Validating with DTD failed in file").": <b>$file</b><br>".$error_str;
                }
            }
        }
        return $errors;
    }
    
    /**
     * This function validates the directive editor port inputs
     * The possible values are:
     * - ANY
     * - 22,!80,...
     * - 1:SRC_PORT
     * - !2:DST_PORT
     *
     * @param string $ports
     */
    public static function valid_directive_port($ports)
    {
        if ($ports == 'ANY' || $ports == ''
        || preg_match('/^\!?\d+(,\!?\d+)*$/', $ports)
        || preg_match('/^\!?\d+\:(SRC|DST)\_PORT$/', $ports))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
}

/* End of file directive_editor.inc */
/* Location: ../include/classes/directive_editor.inc */
