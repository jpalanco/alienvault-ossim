<?php
/**
* product_type.inc
*
* File product_type.inc is used to:
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
* Class and Function List:
* Function list:
* - get_id()
* - get_name()
* - get_list()
* - insert()
* - exist()
* - delete()
* - edit()
*/

require_once 'av_init.php';

class Product_type
{
    public $id;
    public $name;

    function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    function get_id()
    {
        return $this->id;
    }

    function get_name()
    {
        return $this->name;
    }

    function get_list($conn, $args = "ORDER BY name")
    {
        $query = ossim_query("SELECT * FROM product_type $args");

        if (!$rs = & $conn->Execute($query))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();
            while (!$rs->EOF)
            {
                $list[] = new Product_type($rs->fields["id"], $rs->fields["name"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }

    function newID($conn)
    {
        $query = ossim_query('SELECT MAX(id) as id FROM product_type');

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

	function insert($conn, $name)
	{
    	if (self::exist($conn, $name))
    	{
    		echo _('Error: This product_type already exists');
    		return 0;
    	}
    	else
    	{
			$newId = self::newID($conn);
    		$sql = 'INSERT INTO product_type (id,name) VALUES (?,?)';
			$params = array($newId,$name);

    		if ($conn->Execute($sql, $params) === FALSE)
    		{
            	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        	}
    		return $newId;
    	}
    }

    function exist($conn, $name)
    {
    	$query  = 'SELECT * FROM product_type WHERE name=?';
		$params = array($name);
        if (!$rs = & $conn->Execute($query, $params))
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
        		return TRUE;
        	}
        }
    }

	function delete($conn, $id)
	{
		$query  = 'DELETE FROM product_type WHERE id = ?';
		$params = array($id);
		if (!$conn->Execute($query, $params))
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
		return TRUE;

    }

	function edit($conn, $id, $name)
	{
    	$sql    = 'UPDATE product_type SET name=? WHERE id=?';
		$params = array($name, $id);
    	if ($conn->Execute($sql, $params) === FALSE)
    	{
           	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

    	return TRUE;
    }

	function get_name_by_id($conn, $id)
	{
    	$query  = 'SELECT name FROM product_type WHERE id=?';
		$params = array($id);
        if (!$rs = & $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        return ($rs->fields["name"] != '') ? $rs->fields["name"] : _('Unknown');;
    }

	function get_product_type_by_plugin($conn, $pid)
	{
		$ptype  = array();
		$query  = 'SELECT product_type.name, product_type.id FROM alienvault.product_type, alienvault.plugin WHERE plugin.product_type=product_type.id AND plugin.id=?';
		$params = array($pid);

		if (!$rs = & $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
			$ptype['id']   = $rs->fields["id"];
			$ptype['name'] = $rs->fields["name"];
		}

		return $ptype;
	}

}

/* End of file product_type.inc */
/* Location: ../include/classes/product_type.inc */