<?php
/**
* Policy_taxonomy_reference.inc
*
* File Policy_taxonomy_reference.inc is used to:
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
* @package    ossim-framework\Policy&Actions
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Policy_taxonomy_reference()
* - get_id()
* - get_policy_id()
* - get_product_type_id()
* - get_category_id()
* - get_subcategory_id()
* - get_list()
* - insert()
* - delete()
*/

class Policy_taxonomy_reference 
{
	public $id;


    public $policy_id;


    public $product_type_id;


	public $category_id;


	public $subcategory_id;
		
		
    public function __construct($id, $policy_id, $product_type_id, $category_id, $subcategory_id) 
    {	
		$this->id              = $id;		
        $this->policy_id       = $policy_id;
        $this->product_type_id = $product_type_id;
		$this->category_id     = $category_id;
		$this->subcategory_id  = $subcategory_id;       
    }
    
		
    public function get_id() 
    {
        return $this->id;
    }
	
	
    public function get_policy_id() 
    {
        return $this->policy_id;
    }
    
	
    public function get_product_type_id() 
    {
        return $this->product_type_id;
    }
	
	
	public function get_category_id() 
	{
        return $this->category_id;
    }
    
	
	public function get_subcategory_id() 
	{
        return $this->subcategory_id;
    }	
	
	
    public static function get_list($conn, $args)
    {
        $list  = array();
        $query = ossim_query("SELECT *, HEX(policy_id) AS policy_id from policy_taxonomy_reference WHERE 1=1 $args");

        $rs = $conn->Execute($query);       
        if (!$rs) 
		{
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }    
        else 
        {
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields["id"], $rs->fields["policy_id"], $rs->fields["product_type_id"], $rs->fields["category_id"], $rs->fields["subcategory_id"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;        
    }
	
	
    public static function insert($conn, $policy_id, $product_type_id, $category_id, $subcategory_id)
    {
		$params = array($policy_id, $product_type_id, $category_id, $subcategory_id);
		$query  = "INSERT INTO policy_taxonomy_reference (policy_id, product_type_id, category_id, subcategory_id) VALUES (UNHEX(?), ?, ?, ?)";		
		
		if ($conn->Execute($query, $params) === FALSE) 
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
    }
    

	public static function delete($conn, $id) 
	{
        $params = array($id);
        $query  = "DELETE FROM policy_taxonomy_reference WHERE id = ?";
               
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
	
	
    public static function delete_by_policy($conn, $policy_id)
    {
        $params = array($policy_id);
        $query  = "DELETE FROM policy_taxonomy_reference WHERE policy_id = UNHEX(?)";
                
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file policy_taxonomy_reference.inc */
/* Location: ../include/classes/policy_taxonomy_reference.inc */
