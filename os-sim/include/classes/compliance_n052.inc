<?php
/**
* compliance_n052.inc
*
* File compliance_n052.inc is used to:
*   - Manage the data in iso and pci databases
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

class Compliance_n052 
{	
	public static function get_cat($conn, $args = '') 
    {
		Ossim_db::check_connection($conn);
        
        $query = "SELECT * FROM N052.categories $args";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
		if (!$rs->EOF) 
        {
			while (!$rs->EOF) 
            {
				$categories[preg_replace("/\s.*/", '', $rs->fields['name'])] = $rs->fields['name'];
				$rs->MoveNext();
			}
		}
	
		return $categories;
	}
	
    
	public static function get_groups($conn) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "SELECT points.id, points.id_category, points.security_control, points.ref, points.selected, points.applies, 
            points.comments, points.SIDSS_Ref, points.plugin_group_id, categories.name
			FROM N052.points inner join N052.categories ON points.id_category = categories.id 
			ORDER BY points.id_category";
		
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		$title = '';
		
		if (!$rs->EOF) 
        {
			while (!$rs->EOF) 
            {
				if ($rs->fields['name'] != $title)
				{
					$title = $rs->fields['name'];
					$gr = preg_replace("/\s.*/", '', $title);
					$groups[$gr] = array('title' => $title, 'subgroups' => array());
				}

				$key = $rs->fields['ref'];
				$ref = $rs->fields['ref'];
                
				$groups[$gr]['subgroups'][$key] = array(
                    'ref'              => $ref,
                    'Security_controls'=> $rs->fields['security_control'],
                    'comments'         => $rs->fields['comments'],
                    'SIDSS_Ref'        => $rs->fields['SIDSS_Ref'],
                    'category'         => $key,
                    'selected'         => $rs->fields['selected']
                );
				
                $rs->MoveNext();
			}
		}
		return $groups;
	}
	
    
	public static function update_sids($conn, $ref, $sids) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "UPDATE N052.points SET SIDSS_Ref='$sids' WHERE ref = '$ref'";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
    
	public static function update_attr($conn, $ref) 
    {
		Ossim_db::check_connection($conn);
		
		$query = "UPDATE N052.points SET selected=0 WHERE ref = '$ref' AND selected is NULL";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		// Update
		$query = "UPDATE N052.points SET selected=NOT(selected) WHERE ref = '$ref'";
		
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
	
    
	public static function get_text($conn, $ref) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "SELECT comments FROM N052.points WHERE ref='$ref'";
        
		$rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
		return $rs->fields['comments'];
	}
    
	
	public static function save_text($conn, $ref, $text) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "UPDATE N052.points SET comments = ? WHERE ref = ?";
        
        $params = array($text, $ref);
        
		$rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
	}
}

/* End of file compliance_n052.inc */
/* Location: ../include/classes/compliance_n052.inc */
