<?php
/**
* ossim_sql.inc
*
* File ossim_sql.inc is used to:
*   - Utilities for SQL queries
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
* @package    ossim-framework\Database
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


require_once 'av_config.php';

/**
* This function escapes special characters in SQL query
*
* @param string  $sql     SQL query
* @param object  $conn    Database access object
* @param boolean $q_like  Escape special characters for LIKE condition
*
* @return string             
*/
function escape_sql($sql, $conn = NULL, $q_like = TRUE) 
{
    if ($sql == '') 
    {
		return $sql;
    }
	
	// Prevent double quoting 
    if (get_magic_quotes_gpc()) 
    {
		$sql = stripslashes($sql);
    }
	
	if (!is_numeric($sql)) 
	{
        if (function_exists('mysqli_real_escape_string') && $conn != NULL && $conn->databaseType == 'mysqli') 
        {
            $sql = @mysqli_real_escape_string($conn->_connectionID, $sql);
            
        }
        elseif (function_exists('mysql_real_escape_string') && $conn != NULL && $conn->databaseType == 'mysql') 
        {
            $sql = @mysql_real_escape_string($sql, $conn->_connectionID);
        
        }
		elseif (function_exists('mysql_escape_string'))  
		{
			$sql = @mysql_escape_string($sql);
		}
        elseif (function_exists('pg_escape_string'))  
        {
			$sql = @pg_escape_string($sql);
		}
        else
        {
			$sql = addslashes($sql);
		}
		
		if ($q_like == TRUE)
		{
			$sql = addcslashes($sql, '%_'); 
		}
	}
		 
	return $sql;
}


/**
* This function checks SQL query searching SQL injection
*
* @param string  $sql  SQL query
* @throws Exception  If bad characters have been detected
*
* @return string             
*/
function ossim_query($sql) 
{        
	$force_ending = array(
        ";",                                      // Attempt to do a 2nd SQL query        
        "--|#",                                   // Attempt to unused part of the SQL query        
        "((OR|AND|LIKE)\s*(\S+))\s*(=|<>)\s*\\4", // SQL injection like 1=1 or a=a 
    );
	
	//Chinese characters hack
	if (preg_match_all('/&#(\d{4,5});/', $sql, $match_ec))
	{
		foreach ($match_ec[1] as $k => $v)
		{
			$sql = str_replace("&#".$v.";", "ch".$v."pc", $sql);
		}	
	}
	
    foreach($force_ending as $limit) 
	{
        if (preg_match("/(.*?)$limit.*$/", $sql)) 
	    {
			$sql = preg_replace("/(.*?)$limit.*$/i", "\\1", $sql);           
           
            Av_exception::throw_error(Av_exception::USER_ERROR, _('For security reasons, bad characters have been skipped'));
        }
    }
			
	//Chinese characters Hack
	foreach ($match_ec[1] as $k => $v)
	{
		$sql = str_replace("ch".$v."pc", "&#".$v.";", $sql);
    }	
		
	return $sql;
}

/* End of file ossim_sql.inc */
/* Location: ../include/ossim_sql.inc */