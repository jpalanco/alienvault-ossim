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
	public static function get_groups($conn, $args = '') 
    {
		Ossim_db::check_connection($conn);
        
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
			$query = "SELECT * FROM PCI.$table $args";
			
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
					$key = $rs->fields['x1'].$rs->fields['x2'].$rs->fields['x3'].$rs->fields['x4'];
					$ref = $rs->fields['x1'].'.'.$rs->fields['x2'];
					if ($rs->fields['x3'] != '')
                    {
                        $ref .= '.'.$rs->fields['x3'];
					}
                    if ($rs->fields['x4'] != '')
                    {
                        $ref .= '.'.$rs->fields['x4'];
					}
                    
                    $intkey = $rs->fields['x2']*1000 + $rs->fields['x3']*100 + $rs->fields['x4'];
					$keys_hash[$intkey] = $key;
                    
					$order_groups[$intkey] = array(
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
				foreach ($order_groups as $intkey => $item) 
                {
					$key = $keys_hash[$intkey];
					$groups[$gr]['subgroups'][$key] = $item;
				}
			}
		}
        
		return $groups;
	}
	
    
	public static function update_sids($conn, $table, $ref, $sids) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "UPDATE PCI.$table SET SIDSS_Ref='$sids' WHERE concat(x1,x2,x3,x4) = '$ref'";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
	public static function update_attr($conn, $table, $ref) 
    {
		Ossim_db::check_connection($conn);
		
		$query = "UPDATE PCI.$table SET operational=0 WHERE concat(x1,x2,x3,x4) = '$ref' AND operational is NULL";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		$query = "UPDATE PCI.$table SET not_operational=0 WHERE concat(x1,x2,x3,x4) = '$ref' AND not_operational is NULL";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		// Update
		$query = "UPDATE PCI.$table SET operational=NOT(operational), not_operational=NOT(not_operational) WHERE concat(x1,x2,x3,x4) = '$ref'";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
	public static function get_text($conn, $table, $ref) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "SELECT comments FROM PCI.$table WHERE concat(x1,x2,x3,x4)='$ref'";
			
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
        
        $query = "UPDATE PCI.$table SET comments = ? WHERE concat(x1,x2,x3,x4) = ?";
		
        $params = array($text, $ref);
        
		$rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
}

/* End of file compliance_pci.inc */
/* Location: ../include/classes/compliance_pci.inc */