<?php
/**
* Subcategory.inc
*
* File Subcategory.inc is used to:
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
* @package    ossim-framework\Plugins
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Subcategory()
* - get_id()
* - get_name()
* - get_list()
* - insert()
* - exist()
* - inUse()
* - delete()
* - edit()
* - clone_data()
*/

require_once 'av_init.php';

class Subcategory {
    public $id;
    public $name;
    public $inUse;

    function __construct($id, $name,$inUse)
    {
        $this->id = $id;
        $this->name = $name;
        $this->inUse = $inUse;
    }

    function get_id()
    {
        return $this->id;
    }

    function get_name()
    {
        return $this->name;
    }

	function get_inUse()
	{
        return $this->inUse;
    }

    function get_list($conn, $args = "")
    {
        $query = ossim_query("SELECT * FROM subcategory $args");
        if (!$rs = & $conn->Execute($query))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();
            while (!$rs->EOF)
            {
            	$inUse  = self::inUse($conn,$rs->fields["cat_id"],$rs->fields["id"]);
                $list[] = new Subcategory($rs->fields["id"], $rs->fields["name"],$inUse);
                $rs->MoveNext();
            }
        }
        return $list;
    }

    function insert($conn, $cat_id, $name)
    {
    	if(self::exist($conn, $cat_id, $name))
    	{
    		echo _('Error: This subcategory already exists');
    		return 0;
    	}
    	else
    	{
    		$newId = self::newID($conn);

    		$sql = 'INSERT INTO subcategory (id, cat_id, name) VALUES (?, ?, ?)';

    		if ($conn->Execute($sql,array($newId,$cat_id,$name)) === FALSE)
    		{
            	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        	}

    		return $newId;
    	}
    }

    function exist($conn, $cat_id, $name)
    {
    	$query = ossim_query('SELECT * FROM subcategory WHERE cat_id=? AND name=?');
        if (!$rs = & $conn->Execute($query,array($cat_id,$name)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
        	if($rs->EOF)
        	{
        		return FALSE;
        	}
        	else
        	{
        		return TRUE;
        	}
        }
    }

    function newID($conn)
    {
        $query = ossim_query('SELECT MAX(id) as id FROM subcategory');

        $rs = & $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if ($rs->EOF)
            {
                return FALSE;
            }
            else
            {
                return (int)$rs->fields['id']+1;
            }
        }
    }

	function inUse($conn, $cat_id, $id)
	{
    	$query = ossim_query('SELECT COUNT(*) AS total FROM plugin_sid WHERE category_id=? AND subcategory_id=?');
        if (!$rs = & $conn->Execute($query,array($cat_id,$id)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
        	if($rs->EOF)
        	{
        		return FALSE;
        	}
        	else
        	{
        		return ($rs->fields["total"]>0) ? TRUE : FALSE;
        	}
        }
    }

    function delete($conn, $cat_id, $id)
    {
    	if(!self::inUse($conn, $cat_id, $id))
    	{
    		$query = ossim_query('DELETE FROM subcategory WHERE id = ?');
    		if (!$conn->Execute($query, array($id)))
    		{
            	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        	}
        	return TRUE;
    	}
    	return FALSE;
    }

	function edit($conn, $id, $name)
	{
    	$sql = 'UPDATE subcategory SET name=? WHERE id=?';

    	if ($conn->Execute($sql,array($name,$id)) === FALSE)
    	{
           	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

    	return TRUE;
    }

    function clone_data($conn)
    {
    	$sql = "TRUNCATE TABLE subcategory_changes";
    	$conn->Execute($sql);
    	$sql = "REPLACE INTO subcategory_changes SELECT * FROM subcategory";
    	$conn->Execute($sql);
    }

	function get_name_by_id($conn, $id)
	{
    	$query  = 'SELECT name FROM subcategory WHERE id=?';
		$params = array($id);
        $rs = & $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            return ($rs->fields["name"] != '') ? $rs->fields["name"] : _('Unknown');
        }
    }
}
?>