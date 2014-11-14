<?php
/**
* directive.inc
*
* File directive.inc is used to:
*   - Directive object in directive editor main class
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
 * Directive Class
 *
 * Class for managing directives attributes
 * This package defines a model to represent a directive, based on the
 * correlation rules specification.
 * This class represents a Directive, a collection of a name, an id, a priority
 * and a rule, root of the rule tree. Matches the directive node in XML
 *
 * @package    ossim-framework\Directives
 * @autor      AlienVault INC
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/ Alienvault web page
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

class Directive {
    /**
     * The directive id (matches the XML attribute)
     * @access private
     * @var string
     */
    public $id;
    /**
     * The directive name (matches the XML attribute)
     * @access private
     * @var string
     */
    public $name;
    /**
     * The directive prority (matches the XML attribute)
     * @access private
     * @var string
     */
    public $priority;
    /**
     * The rule associated with the directive (matches the XML node <rule>)
     * @access private
     * @var array
     */
    public $rules;
    /**
     * The directive (matches the XML node <directive>)
     * @access private
     * @var Dom element
     */
    public $directive;
    /**
     * The constructor.
     */
    public function __construct($id, $name, $priority, &$rules, &$directive)
    {
        $this->id = $id;
        $this->name = $name;
        $this->priority = $priority;
        $this->rules = & $rules;
        $this->directive = & $directive;
    }   
}

/* End of file directive.inc */
/* Location: ../includes/directive.inc */