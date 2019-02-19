<?php
/**
* template.inc
*
* File template.inc is used for:
*   - Manage menu templates
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
* @package    ossim-framework\Administration
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Template Class
*
* Class for managing menu templates
*
* @package    ossim-framework\Administration
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Template
{                
    /**
    * Template ID
    *
    * @var string
    * @access private
    */ 
    private $id;
    
    /**
    * Template name
    *
    * @var string
    * @access private
    */ 
    private $name;
    
    /**
    * General template structure
    *
    * @var array
    * @access private
    */ 
    private $structure;
    
    /**
    * Template permissions
    *
    * @var array
    * @access private
    */ 
    private $perms;    
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param object  $conn   Database access object
    * @param string  $id     Host ID  
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */  
    public function __construct($conn, $id)
    {    
        Ossim_db::check_connection($conn);
        
        $this->set_id($id);
        $this->name = NULL;
        
        //Getting template structure    
        $query = "SELECT id, name, value, description, ord
            FROM acl_perm
            WHERE enabled = 1
            ORDER BY ord";
        
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            $exp_msg = _('Error! Permission structure is not available');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }       

        while (!$rs->EOF) 
        {
            $this->structure[$rs->fields['name']][$rs->fields['value']] = array(
                'id'          => $rs->fields['id'],
                'description' => $rs->fields['description'],
                'ord'         => $rs->fields['ord']                
            );
        
            $rs->MoveNext();
        }
        
        
        $this->perms = array();             
    }
        
    /**
    * This function returns the template ID
    *
    * @return string
    */      
    public function get_id()
    {
        return $this->id;
    }
    
    
    /**
    * This function returns the template name
    *
    * @return string
    */ 
    public function get_name()
    {
        return $this->name;
    }
    
    
    /**
    * This function sets the new template name
    *
    * @param string $name   New template name
    *
    * @throws Exception  If template name is empty
    *
    * @return void
    */ 
    public function set_name($name)
    {
        if (!empty($name))
        {
            $this->name = $name;
        }
        else
        {
            $exp_msg = _('Error! Template name can not be empty');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }

    
    /**
    * This function sets the new template ID
    *
    * @param string $id   New template ID
    *
    * @throws Exception  If template ID is empty or invalid
    *
    * @return void
    */ 
    public function set_id($id)
    {               
        if (!empty($id) && valid_hex32($id))
        {
            $this->id = $id;
        }
        else
        {           
            $exp_msg = _('Error! Template ID is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
    * This function returns the template structure
    *
    * @return void
    */    
    public function get_structure()
    {
        return $this->structure;        
    }
    
    
    /**
    * This function returns the template perms
    *
    * @return void
    */
    public function get_perms()
    {
        return $this->perms;       
    }


    /**
    * This function sets the template data from database
    *
    * @param object $conn Database access object
    *
    * @throws Exception  If template ID doesn't exists in the System or there is a connection error    
    *
    * @return void
    */ 
    public function load_from_db($conn)
    {
        Ossim_db::check_connection($conn);
                        
        //Getting template information
        $query = "SELECT HEX(id) AS id, name FROM acl_templates WHERE id = UNHEX(?)";
        
        $params = array($this->get_id());
        
        $rs = $conn->Execute($query, $params);            
        
        if (empty($rs->fields['id']))
        {            
            $exp_msg = _('Error! Template not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
                                                    
        $this->name = $rs->fields['name'];
                    
        //Getting template permissions                     
        $query = "SELECT p.id, p.name, p.value, p.description, p.ord
                  FROM acl_templates_perms tp, acl_perm p
                  WHERE tp.ac_templates_id = UNHEX(?)
                  AND tp.ac_perm_id = p.id
                  AND p.enabled = 1
                  ORDER BY p.ord";

        $rs = $conn->Execute($query, array($this->id));

    if (!$rs)
    {
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }

        while (!$rs->EOF) 
        {
            $this->perms[$rs->fields['id']] = $rs->fields['name']."###".$rs->fields['value'];
            
            $rs->MoveNext();
        }
    }


    /**
    * This function sets the new template perms
    *
    * @param string $name   New template name
    *
    * @throws Exception  If template name is empty or perms array is invalid
    *
    * @return void
    */ 
    public function set_perms($perms)
    {
        if (!is_array($perms))
        {
            $exp_msg = _('Error! Permission template is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
            
        if(!empty($perms))
        {
            foreach ($perms as $perm_id => $perm_data)
            {            
                list($m_name, $sm_name) = explode('###', $perm_data);
                
                if(!$this->add_perm($perm_id, $m_name, $sm_name))
                {
                    $exp_msg = _('Error! Permission template is not valid');

                    Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                }
            }
        }
        else
        {
            $this->perms = array();
        }
    }
    
    /**
    * This function adds a new permission
    *
    * @param string  $perm_id   Permission ID
    * @param string  $m_name    Menu name  
    * @param string  $sm_name   Submenu name
    *
    * @return boolean      
    */ 
    public function add_perm($perm_id, $m_name, $sm_name)
    {
        $res = FALSE;
        
        if (is_array($this->structure[$m_name][$sm_name]) && $this->structure[$m_name][$sm_name]['id'] == $perm_id)
        {
            $this->perms[$perm_id] = $m_name.'###'.$sm_name;
            
            $res = TRUE;
        }
                
        return $res;
    }


    /**
    * This function enables all perms of template
    *
    * @return void
    */            
    public function enable_all_perms()
    {
        $this->perms = array();
        
        foreach ($this->structure as $m_name => $m_data)
        {
            foreach ($m_data as $sm_name => $sm_data)
            {
                $this->perms[$sm_data['id']] = $m_name.'###'.$sm_name;                
            }         
        }
    }      


    /**
    * This function saves the template from database
    *
    * @param object $conn Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */  
    public function save_in_db($conn)
    {        
        Ossim_db::check_connection($conn);  
        
        $id   = $this->get_id();
        $name = $this->get_name();
        
        $params = array($name, $id);
        
        if (self::is_in_db($conn, $id)) 
        {
            $query = "UPDATE acl_templates SET name = ? WHERE id = UNHEX(?)";
                        
            //Delete old permissions        
            $this->delete_perms($conn, $id);
        } 
        else 
        {
            //We are using 'Save As' option from GUI
            $query = "INSERT INTO acl_templates (name, id) VALUES (?, UNHEX(?))";
        }
             
        $rs = $conn->Execute($query, $params);
                    
        if (!$rs)
        {          
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 

        foreach ($this->perms as $perm_id => $val)
        {
            $q_perms[] = "UNHEX('".$this->id."'), '".$perm_id."'";
        }
        
        if (is_array($q_perms) && !empty($q_perms))
        {
            $query = 'INSERT INTO acl_templates_perms (ac_templates_id, ac_perm_id) VALUES ('.implode('),(', $q_perms).');';            
                                   
            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
    }


    /**
    * This function deletes a template from database
    *
    * @param object  $conn   Database access object
    * @param string  $id     Template ID  
    *
    * @throws Exception  If a connection error occurred 
    *
    * @return void
    */ 
    public static function delete_from_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $params = array($id);
        $query  = "SELECT name, 'users' AS type FROM users 
            WHERE template_id = UNHEX(?)";

        $rs = $conn->Execute($query, $params);
        
        $num_rows = $conn->Affected_Rows();

        if ($num_rows >= 1) 
        {
            $users = array();

            while (!$rs->EOF) 
            {
                $users[] = utf8_encode($rs->fields['name']);
                
                $rs->MoveNext();
            }
            
            $exp_msg  = _("Error! You can't delete this template. Some users are using it");
            
            if (!empty($users))
            {        
                $exp_msg .= ': '.implode(', ', $users);
            }

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        //Delete permission template
        self::delete_perms($conn, $id);       
                
        $query = "DELETE FROM acl_templates WHERE id = UNHEX(?)";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {            
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    /**
    * This function checks if template exists
    *
    * @param object  $conn   Database access object
    * @param string  $id     Template ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void      
    */ 
    public static function is_in_db($conn, $id)
    {   
        Ossim_db::check_connection($conn);

        $res = FALSE;

        $query  = 'SELECT count(*) AS found FROM acl_templates WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }
        
        return $res;    
    }


    /**
    * This function deletes a template permissions from database (only permissions) 
    *
    * @param string  $id     Template ID
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred or template ID doesn't exists in the System 
    *
    * @return boolean
    */ 
    private static function delete_perms($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        $params = array($id);
        
        $query  = "DELETE FROM acl_templates_perms WHERE ac_templates_id = UNHEX(?)";
                       
        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {            
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return TRUE;
    }


    /**
    * This function returns an array of templates that satisfies the filter
    *
    * @param object   $conn    Database access object
    * @param integer  $from    [Optional] First row of range (For SQL query)
    * @param integer  $max     [Optional] Number of rows
    * @param string   $filter  [Optional] SQL condition.  For example: " WHERE name like '%$search%' "
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_list($conn, $from = 0, $max = 20, $filter = '')
    {
        Ossim_db::check_connection($conn);

        $list      = array();
        $num_rows  = 0;

        //Proadmin visibility
        $perms_where   = '';
        $users_allowed = array();

        if (Session::is_pro() && Acl::am_i_proadmin())
        {
            if (is_array($_SESSION['_user_vision']['users_to_assign']))
            {
                foreach ($_SESSION['_user_vision']['users_to_assign'] as $user => $perm)
                {
                    if ($perm && $user != Session::get_session_user())
                    {
                        $users_allowed[] = $user;
                    }
                }
            }

            $perms_where = "WHERE id NOT IN (SELECT DISTINCT template_id FROM users)";

            if (count($users_allowed) > 0)
            {
                $perms_where = "WHERE (id NOT IN (SELECT DISTINCT template_id FROM users)
                    OR id IN (SELECT DISTINCT template_id FROM users WHERE login IN ('".implode("', '", $users_allowed)."')))";
            }
        }

        //Search filter
        if($filter != '' && preg_match('/where/i', $perms_where))
        {
            $filter = preg_replace('/where/i', 'AND', $filter);
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS HEX(id) AS id, name
            FROM acl_templates
            $perms_where $filter
            LIMIT $from, $max";

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Getting total number of templates
        $rs_total = $conn->Execute('SELECT FOUND_ROWS() AS counter');

        if (!$rs_total->EOF)
        {
            $num_rows = $rs_total->fields['counter'];
        }

        while (!$rs->EOF)
        {
            // Getting users which using each template
            $params = array($rs->fields['id']);
            $query  = "SELECT login FROM users WHERE template_id = UNHEX(?)";

            $rs_2 = $conn->Execute($query, $params);

            if (!$rs_2)
            {
                $exp_msg = _('Error! Template users not found. Unable to connect to Database');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $users = array();

            while (!$rs_2->EOF)
            {
                $users[] = $rs_2->fields['login'];

                $rs_2->MoveNext();
            }

            // Getting permission coverage

            $query = "SELECT count(*) AS total FROM acl_perm
                WHERE type = 'MENU' AND description != '' AND enabled = 1";

            $rs_total = $conn->Execute($query);

            if (!$rs_total->EOF)
            {
                $total_perms = $rs_total->fields['total'];
            }

            $total_perms = ($total_perms < 1) ? 1 : $total_perms;

            $params   =  array($rs->fields['id']);
            $query    = "SELECT count(*) AS num FROM acl_templates_perms
                WHERE ac_templates_id = UNHEX(?)";

            $rs_total = $conn->Execute($query, $params);

            if (!$rs_total->EOF)
            {
                $count_perms = $rs_total->fields['num'];
                $count_perms = ($count_perms > $total_perms) ? $total_perms : $count_perms;
            }

            $perc = round($count_perms * 100 / $total_perms);

            $list[] = array(
                'id'      => $rs->fields['id'],
                'name'    => $rs->fields['name'],
                'users'   => implode(', ', $users),
                'percent' => $perc
            );

            $rs->MoveNext();
        }

        return array($list, $num_rows);
    }

    /**
    * This function returns an array of templates that satisfies the filter
    *
    * @param object   $conn    Database access object
    * @param string   $user    User name
    * @param string   $menu    Menu name
    * @param string   $submenu Submenu name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function menu_perms($conn, $user, $menu, $submenu)
    {
        Ossim_db::check_connection($conn);

        $is_admin = @$conn->GetOne("SELECT is_admin FROM users WHERE login = ?", array($user));
        if ($user == AV_DEFAULT_ADMIN || $is_admin)
        {
            return TRUE;
        }
        $perm_id = @$conn->GetOne("select p.id from acl_perm p,acl_templates_perms t,users u where p.id=t.ac_perm_id and t.ac_templates_id=u.template_id and u.login=? and p.name=? and p.value=?", array($user, $menu, $submenu));
        if ($perm_id>0)
        {
            return TRUE;
        }
        return FALSE;
    }

}

/* End of file template.inc */
/* Location: ../include/classes/template.inc */
