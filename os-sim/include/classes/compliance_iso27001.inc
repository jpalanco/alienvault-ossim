<?php
/**
* compliance_iso27001.inc
*
* File compliance_iso27001.inc is used to:
*   - Manage the data in iso database
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

class Compliance_iso27001 
{
    public static function get_groups($conn, $args = '') 
    {		
        Ossim_db::check_connection($conn);
        
        if (!function_exists('knatsort')) 
        {
            function knatsort(&$karr)
            {
                $kkeyarr = array_keys($karr);
                natsort($kkeyarr);
                $ksortedarr = array();
                foreach($kkeyarr as $kcurrkey)
                {
                    $ksortedarr[$kcurrkey] = $karr[$kcurrkey];
                }
                $karr = $ksortedarr;
                return true;
            }
        }
        
        $groups = array();
        
        $tables = array(
			'A05_Security_Policy',
			'A06_IS_Organization',
			'A07_Asset_Mgnt',
			'A08_Human_Resources',
			'A09_Physical_security',
			'A10_Com_OP_Mgnt',
			'A11_Acces_control',
			'A12_IS_acquisition',
			'A13_IS_incident_mgnt',
			'A14_BCM',
			'A15_Compliance'
		);
        
        foreach ($tables as $table) 
        {
			$query = "SELECT * FROM ISO27001An.$table WHERE Security_controls LIKE '<-----%'";
                                    
            $rs = $conn->Execute($query);
        
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            
            $subgroups = array();
            while (!$rs->EOF) 
            {
                $gr             = str_replace("\n", '', trim($rs->fields['Ref']));
                $subgroups[$gr] = $table;
				
                if ($args == '') 
                {
					$groups[$gr] = array('title' => $rs->fields['Security_controls'], 'subgroups' => array());
				}
                
                $rs->MoveNext();
            }            
                        
            if (count($subgroups) > 0)
            {
                knatsort($subgroups);
                
                foreach ($subgroups as $ref => $table)
                {
                    $where = ($args == '') ? "WHERE ref LIKE '$ref.%'" : $args." AND ref LIKE '$ref.%'";
                    $query   = "SELECT * FROM ISO27001An.$table $where ORDER BY ref";
                                    
                    $rs = & $conn->Execute($query);
        
                    if (!$rs) 
                    {
                        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                    }
                    
                    while (!$rs->EOF) 
                    {
                        $s_ref = str_replace("\n", '', trim($rs->fields['Ref']));
                        
                        $groups[$ref]['subgroups'][$s_ref] = array( 'Ref'               => $s_ref,
                                                                    'Security_controls' => $rs->fields['Security_controls'],
                                                                    'Selected'          => $rs->fields['Selected'],
                                                                    'Excluded'          => $rs->fields['Excluded'],
                                                                    'Implemented'       => $rs->fields['Implemented'],
                                                                    'Justification'     => $rs->fields['Justification'],
                                                                    'SIDSS_Ref'         => $rs->fields['SIDSS_Ref'],
                                                                    'table'             => $table);
                        $rs->MoveNext();
                    }
                }
            }
        }
                     
        knatsort($groups);
        
		return $groups;
	}
	
	public static function update_sids($conn, $table, $ref, $sids) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "UPDATE ISO27001An.$table SET SIDSS_Ref='$sids' WHERE Ref='$ref'";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
	public static function update_attr($conn, $table, $ref, $attr) 
    {
		Ossim_db::check_connection($conn);
        
        if ($attr == 'Implemented') 
        {
          $query = "UPDATE ISO27001An.$table SET `Implemented`=NOT(`Implemented`) WHERE Ref='$ref'";
        }
        
        if ($attr == 'Selected') 
        {
          $query = "UPDATE ISO27001An.$table SET `Selected`=NOT(`Selected`), Excluded=NOT(Excluded) WHERE Ref='$ref'";
        }

        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "UPDATE ISO27001An.$table SET `Implemented`='0' WHERE `Ref`='$ref' AND `Selected`='0'";

        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

	}
	
	public static function get_text($conn, $table, $ref) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "SELECT Justification FROM ISO27001An.$table WHERE Ref='$ref'";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		return $rs->fields['Justification'];
	}
	
	public static function save_text($conn, $table, $ref, $text) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "UPDATE ISO27001An.$table SET Justification = ? WHERE Ref = ?";
		
        $params = array($text, $ref);
        
		$rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
}

/* End of file compliance_iso27001.inc */
/* Location: ../include/classes/compliance_iso27001.inc */