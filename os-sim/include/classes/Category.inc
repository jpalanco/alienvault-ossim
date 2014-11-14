<?php
/**
* Category.inc
*
* File Category.inc is used to:
*   - Manage the data of category table in database
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
* - Category()
* - get_id()
* - get_name()
* - get_list()
* - insert()
* - exist()
* - newId()
* - inUse()
* - delete()
* - edit()
* - clone_data()
*/
require_once 'av_init.php';

class Category
{

    /**
     * Category ID
     * @var int
     *
     */
    public $id;

    /**
     * Category name
     * @var string
     *
     */
    public $name;

    /**
     * Is in use
     * @var int
     *
     */
    public $inUse;

    /**
     * Class constructor
     *
     * This function sets up the class
     */
    function __construct($id, $name, $inUse)
    {
        $this->id    = $id;
        $this->name  = $name;
        $this->inUse = $inUse;
    }

    /**
     * This function returns the category ID
     *
     * @return int
     */
    function get_id()
    {
        return $this->id;
    }

    /**
     * This function returns the category name
     *
     * @return string
     */
    function get_name()
    {
        return $this->name;
    }

    /**
     * This function returns if the category is in use
     *
     * @return int
     */
	function get_inUse()
	{
        return $this->inUse;
    }

    /**
     * This function returns the list of categories
     *
     * @param object $conn
     * @param string $args
     *
     * @return multitype:Category
     */
    function get_list($conn, $args = "ORDER BY name")
    {
        $query = ossim_query("SELECT * FROM category $args");

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();
            while (!$rs->EOF)
            {
                $inUse  = self::inUse($conn,$rs->fields["id"]);
                $list[] = new Category($rs->fields["id"], $rs->fields["name"], $inUse);
                $rs->MoveNext();
            }
        }
        return $list;
    }

	function insert($conn, $name)
	{
    	if (self::exist($conn, $name))
    	{
            echo _('Error: This category already exists');
            return 0;
	    }
	    else
	    {
    		$newId = self::newID($conn);

    		$sql = 'INSERT INTO category (id,name) VALUES (?,?)';

    		if ($conn->Execute($sql, array($newId,$name)) === FALSE)
    		{
    		    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        	}

    		return $newId;
    	}
    }

    function exist($conn, $name)
    {
        $query = ossim_query('SELECT * FROM category WHERE name=?');

        $rs = & $conn->Execute($query,array($name));

        if (!$rs)
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

	function get_category_id_byname($conn, $name)
	{
        $query = ossim_query('SELECT id FROM category WHERE name = ?');

        $rs = & $conn->Execute($query, array($name));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                return $rs->fields['id'];
            }
            else
            {
                return 0;
            }
        }
    }

	function get_subcategory_id_byname($conn, $cat_id, $name)
	{
        $query = ossim_query("SELECT id FROM subcategory WHERE cat_id = ? AND name = ?");

        $rs = & $conn->Execute($query, array($cat_id, $name));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
        		return $rs->fields['id'];
            }
            else
            {
        		return 0;
            }
        }
    }

    function newID($conn)
    {
        $query = ossim_query('SELECT MAX(id) as id FROM category');

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

    function inUse($conn, $id)
    {
        $query = ossim_query('SELECT COUNT(*) AS total FROM plugin_sid WHERE category_id='.$id);

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
                return ($rs->fields["total"]>0) ? TRUE : FALSE;
            }
        }
    }

	function delete($conn, $id)
	{
        if (!self::inUse($conn,$id))
        {
            $query = ossim_query('DELETE FROM category WHERE id = ?');

            $rs = & $conn->Execute($query,array($id));

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
        		// clean subcategory
        		require_once 'classes/Subcategory.inc';
        		$list_subcategories = SubCategory::get_list($conn,'WHERE cat_id='.$id.' ORDER BY name');
				foreach ($list_subcategories as $subcategory)
				{
					SubCategory::delete($conn, $id, $subcategory->get_id());
				}
        		return TRUE;
            }
        }

        return FALSE;
    }

	function edit($conn, $id, $name)
    {
        $sql = 'UPDATE category SET name=? WHERE id=?';

        if ($conn->Execute($sql,array($name,$id)) === FALSE)
        {
           	Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }

	function clone_data($conn)
	{
        $sql = "TRUNCATE TABLE category_changes";
    	$conn->Execute($sql);
    	$sql = "REPLACE INTO category_changes SELECT * FROM category";
    	$conn->Execute($sql);
    }

	function get_name_by_id($conn, $id)
	{
        $query  = 'SELECT name FROM category WHERE id=?';
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


	function get_category_subcategory_by_plugin($conn, $pid, $sid)
	{

		$data['cname']  = '';
		$data['cid']    = '';
		$data['scname'] = '';
		$data['scid']   = '';

		$query  = 'SELECT c.name as cname, c.id as cid, sc.name as scname, sc.id as scid FROM alienvault.plugin_sid p LEFT JOIN alienvault.category c ON p.category_id=c.id LEFT JOIN alienvault.subcategory sc ON p.subcategory_id=sc.id AND sc.cat_id=p.category_id WHERE p.plugin_id=? and p.sid=?';
		$params = array($pid, $sid);

		if (!$rs = & $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
			$data['cname']  = str_replace("_"," ",$rs->fields["cname"]);
			$data['cid']    = $rs->fields["cid"];
			$data['scname'] = str_replace("_"," ",$rs->fields["scname"]);
			$data['scid']   = $rs->fields["scid"];
        }

		return $data;
	}

}

/* End of file category.inc */
/* Location: ../includes/category.inc */