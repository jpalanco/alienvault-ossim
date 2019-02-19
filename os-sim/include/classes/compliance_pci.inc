<?php
/**
* compliance_pci.inc
*
* File compliance_pci.inc is used to:
*   - Manage the data in pci database
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
* @package    ossim-framework\Compliance
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Compliance_pci 
{	
    
    protected static $PCI_DB = 'PCI';
    
    
    
    public static function set_pci_version($pci)
    {
        if (intval($pci) == 3)
        {
            self::$PCI_DB = 'PCI3';
        }
        else
        {
            self::$PCI_DB = 'PCI';
        }
        
    }
    
    
    public static function get_requirement_names() 
    {
        if (self::$PCI_DB == 'PCI3')
        {
            $sections = array(
            	"R01 FW Config"         => "Requirement 1: Install and maintain a firewall configuration to protect cardholder data",
            	"R02 Vendor default"    => "Requirement 2: Do not use vendor-supplied defaults for system passwords and other security parameters",
            	"R03 Stored cardholder" => "Requirement 3: Protect stored cardholder data",
            	"R04 Data encryption"   => "Requirement 4: Encrypt transmission of cardholder data across open, public networks",
            	"R05 Antivirus"         => "Requirement 5: Protect all systems against malware and regularly update anti-virus software or programs",
            	"R06 System app"        => "Requirement 6: Develop and maintain secure systems and applications",
            	"R07 Access control"    => "Requirement 7: Restrict access to cardholder data by business need to know",
            	"R08 UniqueID"          => "Requirement 8: Identify and authenticate access to system components",
            	"R09 Physical Access"   => "Requirement 9: Restrict physical access to cardholder data",
            	"R10 Monitoring"        => "Requirement 10: Track and monitor all access to network resources and cardholder data",
            	"R11 Security test"     => "Requirement 11: Regularly test security systems and processes",
            	"R12 IS Policy"         => "Requirement 12: Maintain a policy that addresses information security for all personnel"
            );
        }
        else
        {
            $sections = array(
            	"R01 FW Config"         => "Requirement 1: Install and maintain a firewall configuration to protect cardholder data",
            	"R02 Vendor default"    => "Requirement 2: Do not use vendor-supplied defaults for system passwords and other security parameters",
            	"R03 Stored cardholder" => "Requirement 3: Protect stored cardholder data",
            	"R04 Data encryption"   => "Requirement 4: Encrypt transmission of cardholder data across open, public networks",
            	"R05 Antivirus"         => "Requirement 5: Use and regularly update anti-virus software or programs",
            	"R06 System app"        => "Requirement 6: Develop and maintain secure systems and applications",
            	"R07 Access control"    => "Requirement 7: Restrict access to cardholder data by business need to knows",
            	"R08 UniqueID"          => "Requirement 8: Assign a unique ID to each person with computer access",
            	"R09 Physical Access"   => "Requirement 9: Restrict physical access to cardholder data",
            	"R10 Monitoring"        => "Requirement 10: Track and monitor all access to network resources and cardholder data",
            	"R11 Security test"     => "Requirement 11: Regularly test security systems and processes",
            	"R12 IS Policy"         => "Requirement 12: Maintain a policy that addresses information security for all personnel"
            );
        }
        
        return $sections;
    }
    
    
	public static function get_groups($conn, $args = '') 
    {
		Ossim_db::check_connection($conn);
        
        $groups = array();
        $tables = array(
			'R01_FW_Config',
			'R02_Vendor_default',
			'R03_Stored_cardholder',
			'R04_Data_encryption',
			'R05_Antivirus',
			'R06_System_app',
			'R07_Access_control',
			'R08_UniqueID',
			'R09_Physical_Access',
			'R10_Monitoring',
			'R11_Security_test',
			'R12_IS_Policy'
		);		
    
            
		foreach ($tables as $table) 
        {
			$query = "SELECT * FROM ". self::$PCI_DB .".$table $args";
			
            $rs = $conn->Execute($query);
        
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            
			if (!$rs->EOF) 
            {
				$title = str_replace('_', ' ', $table);
				$gr    = preg_replace("/\s.*/", '', $title);
                
				$groups[$gr]  = array('title' => $title, 'subgroups' => array());
				$order_groups = array();
				$keys_hash    = array();
                
				while (!$rs->EOF) 
                {
					$ref = $rs->fields['x1'].'.'.$rs->fields['x2'];
					
					if ($rs->fields['x3'] != '')
                    {
                        $ref .= '.'.$rs->fields['x3'];
					}
                    if ($rs->fields['x4'] != '')
                    {
                        $ref .= '.'.$rs->fields['x4'];
					}
					if ($rs->fields['x5'] != '')
                    {
                        $ref .= '.'.$rs->fields['x5'];
					}
                    
                    $key_aux = array(
                        str_pad($rs->fields['x2'], 2, '0', STR_PAD_LEFT),
                        str_pad($rs->fields['x3'], 2, '0', STR_PAD_LEFT),
                        str_pad($rs->fields['x4'], 2, '0', STR_PAD_LEFT),
                        str_pad($rs->fields['x5'], 2, '0', STR_PAD_LEFT)
                    );
                    $key = implode('',$key_aux);
                    
                    $keys_hash[$key] = $ref;

					$order_groups[$key] = array(
                        'Ref'                => $ref,
                        'Security_controls'  => $rs->fields['Security_control'],
                        'operational'        => $rs->fields['operational'],
                        'not_operational'    => $rs->fields['not_operational'],
                        'comments'           => $rs->fields['comments'],
                        'SIDSS_Ref'          => $rs->fields['SIDSS_ref'],
                        'testing_procedures' => $rs->fields['testing_procedures'],
                        'table'              => $table
                    );
                        
					$rs->MoveNext();
				}
				
				ksort($order_groups);
				
				foreach ($order_groups as $k => $item) 
                {
					$key = $keys_hash[$k];
					$groups[$gr]['subgroups'][$key] = $item;
				}

			}
		}
		        
		return $groups;
	}
	
	
	private static function _translate_ref($ref)
	{
    	$vals = explode('.', $ref);
    	
    	$sql  = array();
    	
    	for ($i = 1; $i <= 5; $i++)
    	{
            if ($vals[$i-1] != '')
            {
                $sql[] = "x$i='". $vals[$i-1] . "'";
            }	
            else
            {
                $sql[] = "x$i=''";
            }
    	}    
    	
    	return 'AND ' . implode(' AND ', $sql);	
	}
    
    
	public static function update_sids($conn, $table, $ref, $sids) 
    {
		Ossim_db::check_connection($conn);
        
        $ref_sql = self::_translate_ref($ref);

        $query  = "UPDATE ". self::$PCI_DB .".$table SET SIDSS_Ref=? WHERE 1 $ref_sql";
		$params = array($sids);

		$rs = $conn->Execute($query, $params);
		
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
	
	public static function update_attr($conn, $table, $ref) 
    {
		Ossim_db::check_connection($conn);
		
		$ref_sql = self::_translate_ref($ref);
		
		$query = "UPDATE ". self::$PCI_DB .".$table SET operational=0 WHERE 1 $ref_sql AND operational is NULL";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		$query = "UPDATE ". self::$PCI_DB .".$table SET not_operational=0 WHERE 1 $ref_sql AND not_operational is NULL";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		// Update
		$query = "UPDATE ". self::$PCI_DB .".$table SET operational=NOT(operational), not_operational=NOT(not_operational) WHERE 1 $ref_sql";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
	
	public static function get_text($conn, $table, $ref) 
    {
		Ossim_db::check_connection($conn);
        
        $ref_sql = self::_translate_ref($ref);
        
        $query = "SELECT comments FROM ". self::$PCI_DB .".$table WHERE 1 $ref_sql";
			
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		return $rs->fields['comments'];
	}
		
		
	public static function save_text($conn, $table, $ref, $text) 
    {
		Ossim_db::check_connection($conn);
		
		$ref_sql = self::_translate_ref($ref);
        $query   = "UPDATE ". self::$PCI_DB .".$table SET comments = ? WHERE 1 $ref_sql";
        $params  = array($text);
        
		$rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
}

/* End of file compliance_pci.inc */
/* Location: ../include/classes/compliance_pci.inc */
