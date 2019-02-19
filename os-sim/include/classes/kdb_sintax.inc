<?php
/**
* KDB_Sintax.inc
*
* File KDB_Sintax.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
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
* @package    ossim-framework\KDB
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class KDB_Sintax
{
    public $_control_line;

    public $_generic_variable;
    public $_generic_value;
    public $_condition_elements;
    public $_operations_elements;
    public $_variable_list; 

    public $_functions_list;


    public $_binary_operators;
    public $_tenary_operators;
    public $_sintax_messages;


    public $_labels_variables;
    public $_labels_operators;
    public $_labels_actions;
    public $_labels_condition;



    //functions
    public function __construct()
    {
    
        //Reg Exp for control line
        $this->_control_line     = '^{(.+)}$';

        //Reg Exp for a generic variable
        $this->_generic_variable = '(\$[A-Za-z_\d?]+)';

        //Reg Exp for a generic value
        $this->_generic_value    = '"(.*)"';

        //Reg Exp for a variable inside the text
        $this->_inline_variable  = '\(\('. $this->_generic_variable .'\)\)';

        //Reg Exp for a link inside the text
        $this->_inline_link      = '\[\['. $this->_generic_variable .']\]';



        /** Conditions Elements **/

        $this->_condition_elements = array(
            'IF'      => 'IF',
            'ENDIF'   => 'ENDIF',
            'ELSE'    => 'ELSE',
            'ENDELSE' => 'ENDELSE'
        );


        $this->_labels_condition = array(
            'IF' => array(
                'help' => _("Begin a Conditional Block Content") ,
                'sample' => '{ IF $SRCUSER IS "administrator" }'
            ) ,
            'ELSE' => array(
                'help' => _("Begin an Alternate Block of Content if all prior conditions tests as false") ,
                'sample' => '{ELSE}'
            ),
            'ENDIF' => array(
                'help' => _("End a Conditional Block") ,
                'sample' => '{ENDIF}'
            ) ,
            'ENDELSE' => array(
                'help' => _("End an Alternative Block") ,
                'sample' => '{ENDELSE}'
            ) 
        );
        
        /** End of Conditions Elements **/



        /** Actions Elements **/

        $this->_action_elements = array(
            '\$INCLUDE'     => '\$INCLUDE\b',
            'SECTION'       => '\bSECTION\b',
            'ENDSECTION'    => '^ENDSECTION$',
        );


        $this->_labels_actions = array(
            '$INCLUDE ID' => array(
                'help' => _("Include a KDB document") ,
                'sample' => '{INCLUDE 43001}'
            ),
            '$INCLUDE ID SECTION' => array(
                'help' => _("Include a section of a KDB Document") ,
                'sample' => '{INCLUDE 43001 INTRODUCTION}'
            ),
            'SECTION' => array(
                'help' => _("Tag for the beginning of a section") ,
                'sample' => '{SECTION INTRODUCTION}'
            ),
            'ENDSECTION' => array(
                'help' => _("Tag for the end of a section") ,
                'sample' => '{ENDSECTION}'
            ),
            'TEXT' => array(
                'help' => _("Text to display in the document") ,
                'sample' => 'In general, Attacks are launched from a source machine against a destination machine'
            ) ,
            '((VARIABLE))' => array(
                'help' => _("Variable inside text") ,
                'sample' => 'In general, Attacks are launched from a source machine (($SRCIP)) against a destination machine (($DSTIP))'
            )
            /*'[[LINK]]' => array(
                'help' => _("Link inside text") ,
                'sample' => ''
            )*/
        );


        $this->_sections = array(
            "INTRODUCTION"  => "INTRODUCTION",
            "ANALYSIS"      => "ANALYSIS",
            "RECTIFICATION" => "RECTIFICATION"
        );


        $this->_labels_sections = array(
            'INTRODUCTION' => array(
                'help' => _("Section to describe the introduction of the document") ,
                'sample' => '{SECTION INTRODUCTION}'
            ) ,
            'ANALYSIS' => array(
                'help' => _("Section to describe the analysis of the document") ,
                'sample' => '{SECTION ANALYSIS}'
            ),
            'RECTIFICATION' => array(
                'help' => _("Section to describe the rectification of the document") ,
                'sample' => '{SECTION RECTIFICATION}'
            )
        );

        /** End of Actions Elements **/



        /** Variable Elements **/

        $this->_variable_list  = array(
            '$SENSOR'            => '\$SENSOR',
            '$SRCIP'             => '\$SRCIP',
            '$SRCMAC'            => '\$SRCMAC',
            '$DSTIP'             => '\$DSTIP',
            '$DSTMAC'            => '\$DSTMAC',
            '$SRCPORT'           => '\$SRCPORT',
            '$DSTPORT'           => '\$DSTPORT',
            '$SRCCRITICALITY'    => '\$SRCCRITICALITY',
            '$DSTCRITICALITY'    => '\$DSTCRITICALITY',
            '$SRCUSER'           => '\$SRCUSER',
            '$FILENAME'          => '\$FILENAME',
            '$USERDATA1'         => '\$USERDATA1',
            '$USERDATA2'         => '\$USERDATA2',
            '$USERDATA3'         => '\$USERDATA3',
            '$USERDATA4'         => '\$USERDATA4',
            '$USERDATA5'         => '\$USERDATA5',
            '$USERDATA6'         => '\$USERDATA6',
            '$USERDATA7'         => '\$USERDATA7',
            '$USERDATA8'         => '\$USERDATA8',
            '$USERDATA9'         => '\$USERDATA9',
            '$ALARMRISKSCORE'    => '\$ALARMRISKSCORE',
            '$ALARMRELIABILITY'  => '\$ALARMRELIABILITY',
            '$SRCREPACTIVITY'    => '\$SRCREPACTIVITY',
            '$DSTREPACTIVITY'    => '\$DSTREPACTIVITY',
            '$SRCREPRELIABILITY' => '\$SRCREPRELIABILITY',
            '$DSTREPRELIABILITY' => '\$DSTREPRELIABILITY',
            '$HOST_NAME'         => '\$HOST_NAME',
            '$HOST_IP'           => '\$HOST_IP',
            '$HOST_FQDN'         => '\$HOST_FQDN',
            '$HOST_DESC'         => '\$HOST_DESC',
            '$NET_CIDR'          => '\$NET_CIDR',
            '$NET_NAME'          => '\$NET_NAME',
            '$HG_NAME'           => '\$HG_NAME',
            '$NG_NAME'           => '\$NG_NAME'
        );



        $this->_labels_variables = array(
        
            '$SENSOR' => array(
                'help' => _("The Sensor the log originated from") ,
                'sample' => 'sensor'
            ),
            '$SRCIP' => array(
                'help' => _("Source IP Address field in Event") ,
                'sample' => '192.168.1.1'
            ),
            '$SRCMAC' => array(
                'help' => _("Source MAC field in Event or Alarm") ,
                'sample' => 'DE:AD:BE:EF:DE:AD'
            ),
            '$DSTIP' => array(
                'help' => _("Destination IP field in Event or Alarm") ,
                'sample' => '192.168.1.2'
            ),
            '$DSTMAC' => array(
                'help' => _("Destination MAC field in Event or Alarm") ,
                'sample' => 'CA:FE:D0:0D:CA:FE'
            ),
            '$SRCPORT' => array(
                'help' => _("Connection Source Port Number") ,
                'sample' => '4813'
            ),
            '$DSTPORT' => array(
                'help' => _("Connection Destination Port Number") ,
                'sample' => '22'
            ),
            '$SRCCRITICALITY' => array(
                'help' => _("Source Host Criticality") ,
                'sample' => '0'
            ),
            '$DSTCRITICALITY' => array(
                'help' => _("Destination Host Criticality") ,
                'sample' => '7'
            ),
            '$SRCUSER' => array(
                'help' => _("Source User field in Event or Alarm") ,
                'sample' => 'bob'
            ) ,
            '$FILENAME' => array(
                'help' => _("Filename Field in Alarm") ,
                'sample' => 'c:\users\bob\tmp\87sjsm.exe'
            ),
            '$USERDATA(1-9)' => array(
                'help' => _("UserData field in Event") ,
                'sample' => 'Custom Event Field'
            ),
            '$ALARMRISKSCORE' => array(
                'help' => _("Alarm Risk value") ,
                'sample' => '{ IF $ALARMRISKSCORE LESS THAN "3" }'
            ),
            '$ALARMRELIABILITY' => array(
                'help' => _("Alarm Risk Reliability value") ,
                'sample' => '{ IF $DSTREPACTIVITY IS "Scanning" }'
            ),
            '$SRCREPACTIVITY' => array(
                'help' => _("Source IP Address Reputation Activity field contents") ,
                'sample' => '{ IF $SRCREPACTIVITY IS "Spam" }'
            ),
            '$DSTREPACTIVITY' => array(
                'help' => _("Destination IP Address Reputation Activity field contents") ,
                'sample' => '{ IF $DSTREPACTIVITY IS "Scanning" }'
            ),
            '$SRCREPRELIABILITY' => array(
                'help' => _("Source IP Address Reputation Reliability value") ,
                'sample' => '{ IF $SRCREPRELIABILITY MORE THAN "5" }'
            ),
            '$HOST_NAME' => array(
                'help' => _("Host Name") ,
                'sample' => 'AlienVault4'
            ),
            '$HOST_IP' => array(
                'help' => _("Host IP") ,
                'sample' => '192.168.1.1'
            ),
            '$HOST_FQDN' => array(
                'help' => _("Host FQDN") ,
                'sample' => 'www.alienvault.com'
            ),
            '$HOST_DESC' => array(
                'help' => _("Host Description") ,
                'sample' => 'This is a description for AlienVault host.'
            ),
            '$NET_CIDR' => array(
                'help' => _("Network CIDR") ,
                'sample' => '192.168.10.0/24'
            ),
            '$NET_NAME' => array(
                'help' => _("Network Name") ,
                'sample' => 'Pvt_010'
            ),
            '$HG_NAME' => array(
                'help' => _("Host Group Name") ,
                'sample' => 'Developers'
            ),
            '$NG_NAME' => array(
                'help' => _("Network Group Name") ,
                'sample' => 'NetGroupAV'
            )
            
        );
        /** End of Variable Elements **/


        /** Functions Elements **/

        $this->_operations_elements = array(
            'IS IN INVENTORY'      => '\bIS IN INVENTORY\b',
            'NOT IN INVENTORY'     => '\bNOT IN INVENTORY\b',
            'IS IN ASSETOS'        => '\bIS IN ASSETOS\b',
            'NOT IN ASSETOS'       => '\bNOT IN ASSETOS\b',
            'IS IN ASSETSERVICES'  => '\bIS IN ASSETSERVICES\b',
            'NOT IN ASSETSERVICES' => '\bNOT IN ASSETSERVICES\b',
            'IS IN HOSTS'          => '\bIS IN HOSTS\b',
            'NOT IN HOSTS'         => '\bNOT IN HOSTS\b',
            'IS IN NETWORKS'       => '\bIS IN NETWORKS\b',
            'NOT IN NETWORKS'      => '\bNOT IN NETWORKS\b',
            'IS IN HOSTGROUP'      => '\bIS IN HOSTGROUP\b',
            'NOT IN HOSTGROUP'     => '\bNOT IN HOSTGROUP\b',
            'IS IN NETGROUP'       => '\bIS IN NETGROUP\b',
            'NOT IN NETGROUP'      => '\bNOT IN NETGROUP\b',
            'IS IN HOSTGROUPS'     => '\bIS IN HOSTGROUPS\b',
            'NOT IN HOSTGROUPS'    => '\bNOT IN HOSTGROUPS\b',
            'IS IN NETGROUPS'      => '\bIS IN NETGROUPS\b',
            'NOT IN NETGROUPS'     => '\bNOT IN NETGROUPS\b',
            'NOT EMPTY'            => '\bNOT EMPTY\b',
            'EMPTY'                => '\bEMPTY\b',
            'NOT CONTAINS'         => '\bNOT CONTAINS\b',
            'CONTAINS'             => '\bCONTAINS\b',
            'MORE THAN'            => '\bMORE THAN\b',
            'LESS THAN'            => '\bLESS THAN\b',
            'IS NOT'               => '\bIS NOT\b',
            'IS'                   => '\bIS\b'
        );


        $this->_binary_operators = array(
            "IS IN INVENTORY"      => 1,
            "NOT IN INVENTORY"     => 1,
            "IS IN ASSETOS"        => 1,
            "NOT IN ASSETOS"       => 1,
            "IS IN ASSETSERVICES"  => 1,
            "NOT IN ASSETSERVICES" => 1,
            "IS IN HOSTS"          => 1,
            "NOT IN HOSTS"         => 1,
            "IS IN NETWORKS"       => 1,
            "NOT IN NETWORKS"      => 1,
            "IS IN HOSTGROUPS"     => 1,
            "NOT IN HOSTGROUPS"    => 1,
            "IS IN NETGROUPS"      => 1,
            "NOT IN NETGROUPS"     => 1,
            "NOT EMPTY"            => 1,
            "EMPTY"                => 1
        );


        $this->_tenary_operators = array(
            "IS IN HOSTGROUP"  => 1,
            "NOT IN HOSTGROUP" => 1,
            "IS IN NETGROUP"   => 1,
            "NOT IN NETGROUP"  => 1,
            "NOT CONTAINS"     => 1,
            "CONTAINS"         => 1,
            "MORE THAN"        => 1,
            "LESS THAN"        => 1,
            "IS NOT"           => 1,
            "IS"               => 1
        );
        
        
        $this->_functions_list = array(
            'IS IN INVENTORY'      => 'is_in_inventory',
            'NOT IN INVENTORY'     => 'not_in_inventory',
            'IS IN ASSETOS'        => 'is_in_assetos',
            'NOT IN ASSETOS'       => 'not_in_assetos',
            'IS IN ASSETSERVICES'  => 'is_in_assetservices',
            'NOT IN ASSETSERVICES' => 'not_in_assetservices',
            'IS IN HOSTS'          => 'is_in_hosts',
            'NOT IN HOSTS'         => 'not_in_hosts',
            'IS IN NETWORKS'       => 'is_in_networks',
            'NOT IN NETWORKS'      => 'not_in_networks',
            'IS IN HOSTGROUP'      => 'is_in_hostgroup',
            'NOT IN HOSTGROUP'     => 'not_in_hostgroup',
            'IS IN NETGROUP'       => 'is_in_netgroup',
            'NOT IN NETGROUP'      => 'not_in_netgroup',
            'IS IN HOSTGROUPS'     => 'is_in_hostgroups',
            'NOT IN HOSTGROUPS'    => 'not_in_hostgroups',
            'IS IN NETGROUPS'      => 'is_in_netgroups',
            'NOT IN NETGROUPS'     => 'not_in_netgroups',
            'EMPTY'                => 'is_empty',
            'NOT EMPTY'            => 'not_empty',
            'CONTAINS'             => 'contains',
            'NOT CONTAINS'         => 'not_contains',
            'MORE THAN'            => 'more_than',
            'LESS THAN'            => 'less_than',
            'IS'                   => 'is',
            'IS NOT'               => 'is_not'
        );


        $this->_labels_operators = array(
            'IS IN INVENTORY' => array(
                'help' => _("Checks if the variable belongs to the inventory.") ,
                'sample' => '{ IF $SRCIP IS IN INVENTORY }'
            ),
            'NOT IN INVENTORY' => array(
                'help' => _("Checks if the variable does not belong to the inventory.") ,
                'sample' => '{ IF $SRCIP NOT IN INVENTORY }'
            ),
            'IS IN ASSETOS' => array(
                'help' => _('Compare as String to all software names defined in Inventory (case insensitive)') ,
                'sample' => '{ IF $USERDATA1 IS IN ASSETOS }'
            ),
            'NOT IN ASSETOS' => array(
                'help' => _('Compare as String to software names defined not in Inventory (case insensitive)') ,
                'sample' => '{ IF $USERDATA1 NOT IN ASSETOS }'
            ),
            'IS IN ASSETSERVICES' => array(
                'help' => _('Compare as String to all Service names or Port Numbers defined in Inventory (case insensitive)') ,
                'sample' => '{ IF $SRCPORT IS IS IN ASSETSERVICES }'
            ),
            'NOT IN ASSETSERVICES' => array(
                'help' => _('Compare as String to Service names or Port Numbers defined not in Inventory (case insensitive)') ,
                'sample' => '{ IF $SRCPORT IS IN ASSETSERVICES }'
            ),
            'IS IN HOSTS' => array(
                'help' => _('Compare $VALUE as IP address or hostname, against all Host entries listed in Inventory') ,
                'sample' => '{ IF $SRCIP IS IS IN HOSTS }'
            ),
            'NOT IN HOSTS' => array(
                'help' => _('Compare $VALUE as IP address or hostname, against Host entries not listed in Inventory') ,
                'sample' => '{ IF $SRCIP NOT IN HOSTS }'
            ),
            'IS IN NETWORKS' => array(
                'help' => _('Compare $VALUE as IP address, for membership in all inventory defined CIDR blocks') ,
                'sample' => '{ IF $SRCIP IS IN NETWORKS }'
            ),
            'NOT IN NETWORKS' => array(
                'help' => _('Compare $VALUE as IP address, for membership not in any inventory defined CIDR blocks') ,
                'sample' => '{ IF $SRCIP NOT IN NETWORKS }'
            ),
            'IS IN HOSTGROUP' => array(
                'help' => _('Compare $VALUE as IP address or hostname, against all Host entries listed in Inventory with Named Grouping') ,
                'sample' => '{ IF $SRCIP IS IN HOSTGROUP "Developers"}'
            ),
            'NOT IN HOSTGROUP' => array(
                'help' => _('Compare $VALUE as IP address or hostname, against Host entries not listed in Inventory with Named Grouping') ,
                'sample' => '{ IF $SRCIP NOT IN HOSTGROUP "Developers"}'
            ),
            'IS IN NETGROUP' => array(
                'help' => _('Compare $VALUE as IP address, for membership in all inventory defined CIDR blocks with Named groupings') ,
                'sample' => '{ IF $SRCIP IS IN NETGROUP "Developers"}'
            ),
            'NOT IN NETGROUP' => array(
                'help' => _('Compare $VALUE as IP address, for membership not in any inventory defined CIDR blocks with Named groupings') ,
                'sample' => '{ IF $SRCIP NOT IN NETGROUP "Developers" }'
            ),
            'IS IN HOSTGROUPS' => array(
                'help' => _('Compare $VALUE as IP address or hostname, against all Host entries listed in Inventory') ,
                'sample' => '{ IF $SRCIP IS IN HOSTGROUPS }'
            ),
            'NOT IN HOSTGROUPS' => array(
                'help' => _('Compare $VALUE as IP address or hostname, against Host entries not listed in Inventory') ,
                'sample' => '{ IF $SRCIP NOT IN HOSTGROUPS }'
            ),
            'IS IN NETGROUPS' => array(
                'help' => _('Compare $VALUE as IP address, for membership in all inventory defined CIDR blocks') ,
                'sample' => '{ IF $SRCIP IS IN NETGROUPS }'
            ),
            'NOT IN NETGROUPS' => array(
                'help' => _('Compare $VALUE as IP address, for membership not in any inventory defined CIDR blocks') ,
                'sample' => '{ IF $SRCIP NOT IN NETGROUPS }'
            ),
            'EMPTY' => array(
                'help' => _("Checks if the variable is empty") ,
                'sample' => '{ IF $USERDATA1 EMPTY }'
            ),
            'NOT EMPTY' => array(
                'help' => _("Checks if the variable is not empty") ,
                'sample' => '{ IF $USERDATA1 NOT EMPTY }'
            ),
            'CONTAINS' => array(
                'help' => _("Checks if the variable contains a string") ,
                'sample' => '{ IF $USERDATA1 CONTAINS "text" }'
            ),
            'NOT CONTAINS' => array(
                'help' => _("Checks if the variable does not contain a string") ,
                'sample' => '{ IF $USERDATA1 NOT CONTAINS "text" }'
            ),
            'MORE THAN' => array(
                'help' => _("Numeric Comparison for greater than (should accept value aliases - ie 'low' for 1-3)") ,
                'sample' => '{ IF $ALARMRISKSCORE MORE THAN "3" }'
            ) ,
            'LESS THAN' => array(
                'help' => _("Numeric Comparison for greater than (should accept value aliases - ie 'low' for 1-3)") ,
                'sample' => '{ IF $ALARMRISKSCORE LESS THAN "7" }'
            ) ,
            'IS' => array(
                'help' => _("Checks if the variable is equal to a string") ,
                'sample' => '{ IF $DSTREPACTIVITY IS "Scanning" }'
            ),
            'IS NOT' => array(
                'help' => _("Checks if the variable is not equal to a string") ,
                'sample' => '{ IF $DSTREPACTIVITY IS NOT "Scanning" }'
            )
        );
        
        /** End of Functions Elements **/

        
        /** Sintax Messages **/
        
        $this->_sintax_messages  = array(
            'cond1' => _("Invalid condition item."),
            'cond2' => _("'ENDIF' was found but it doesn't match with any 'IF' tag."),
            'cond3' => _("'ELSE' was found but it doesn't match with any 'IF - ENDIF' tag."),
            'cond4' => _("'ENDELSE' was found but it doesn't match with any 'ELSE' tag."),
            'cond5' => _("TAG unclosed"),
            'oper1' => _("This operator expects one variable as follows -variable-operator-"),
            'oper2' => _("This operator expects one variable and one value as follows -variable-operator-value-"),
            'var1'  => _("Expectig variable and I got an unexpected item. "),
            'val1'  => _("Expectig value and I got an unexpected item. "),
            'act1'  => _("Invalid action"),
            'act2'  => _("Invalid KB Document ID."),
            'act3'  => _("The KB Document ID does not exist."),
            'act4'  => _("Invalid parameters for action \$INCLUDE."),
            'sec1'  => _("Invalid Section."),
            'sec2'  => _("You are trying to add a section inside a section. This action is not permitted."),
            'sec3'  => _("ENDSECTION tag has to be placed after a SECTION tag."),
            'sec4'  => _("Missing ENDSECTION tag.")
        );

        /** End of Sintax Messages **/
    }



    //SIntax Functions

    public function get_sintax_error($code)
    {
        return $this->_sintax_messages[$code];
    }


    public function get_type_operator($op)
    {
        if($this->is_binary_operator($op))
        {
            return 2;
        }
        elseif($this->is_ternary_operator($op))
        {
            return 3;
        } 
        else
        {
            return 0;
        }
    }


    public function get_function($f)
    {
        return $this->_functions_list[$f];
    }


    public function build_regexp_from_array($items)
    {
        if(!is_array($items))
        {
            return "";
        }

        $regex = implode('|',$items);

        return $regex;
    }


    protected function is_binary_operator($op)
    {
        return $this->_binary_operators[$op];
    }


    protected function is_ternary_operator($op)
    {
        return $this->_tenary_operators[$op];
    }
}

/* End of file kdb_sintax.inc */
/* Location: ../include/classes/kdb_sintax.inc */
