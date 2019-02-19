<?php
/**
* dashboard_tab.inc
*
* File dashboard_tab.inc is used to:
*   - This class manage the classes for the section Dashboard.
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
* @package    ossim-framework\Dashboard
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Dashboard_tab Class
*
* This class is used to manage dashboard tabs
*
*
* @package    ossim-framework\Dashboard
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Dashboard_tab
{       
    /**#@+
    * Attributes
    */
    
    /**
    * @var int  Tab ID
    * @access public 
    */
    public $id;
    
    /**
    * @var string  User owner of the Tab.
    * @access public 
    */
    public $user;

    /**
    * @var string  Title of the Tab.
    * @access public 
    */
    public $title;    
    
    /**
    * @var int  Number of columns of the Tab.
    * @access public 
    */
    public $layout;

    /**
    * @var int  Visible property of the Tab.
    * @access public 
    */
    public $visible;

    /**
    * @var int  Order of the Tab.
    * @access public 
    */
    public $order;

    /**
    * @var int  Default property of the Tab.
    * @access public 
    */
    public $default;

    /**
    * @var int  Locked property of the Tab.
    * @access public 
    */
    public $locked;
    
            
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id    Tab id
    */

    public function __construct($id = '', $user = '')
    {
        $user = (empty($user)) ? Session::get_session_user() : $user;

        if ($id != '')
        {
            self::load_from_db($id, $user);
        }
        else
        {
            $this->user    = $user;
            $this->title   = '';
            $this->layout  = 1;
            $this->visible = TRUE;

            $this->get_new_id();
            $this->get_new_order();
        }
    }

    public function load_from_db($id, $user)
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $user   = (empty($user)) ? Session::get_session_user() : $user;
        $query  = "SELECT c.id, c.user, c.title, c.layout, o.user, o.visible, o.tab_order 
                    FROM dashboard_tab_config c, dashboard_tab_options o 
                    WHERE c.id=o.id AND c.id=? AND o.user=? AND (c.user=? OR c.user='0')";      

        $params = array($id, $user, $user);

        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {
            if($rs->fields['id'] == '')
            {
                $exp_msg = _('Error! Tab ID not valid');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $this->id      = $id;
            $this->user    = $user;
            $this->title   = $rs->fields['title'];
            $this->layout  = $rs->fields['layout'];
            $this->visible = $rs->fields['visible'];
            $this->order   = $rs->fields['tab_order'];

            //Retrieving default tab info.
            $config        = new User_config($conn);
            $tabdefault    = $config->get($user, 'panel_default', 'simple', "main");
            $tabdefault    = (empty($tabdefault)) ? 1 : $tabdefault;
            $this->default = ($tabdefault == $id);
            $this->locked  = ($id < 1000);
        }

        $db->close();
        
        unset($db);    
    }


    /**
    * This function delete a tab from db
    *
    * @return void             
    */
    public function delete()
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $params = array($this->id, $this->user); 
        
        $query = "DELETE from dashboard_widget_config WHERE panel_id = ? and user = ?";        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "DELETE from dashboard_tab_options WHERE id = ? and user = ?";
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    
        $query = "DELETE from dashboard_tab_config WHERE id = ? and user=?";         
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Iw we are trying to delete the default tab, we set the tab 1 as new default tab,
        // otherwise the dashboard gets crazy...
        if($this->default)
        {
            $default = new self(1);
            $default->set_visible(1);
            $default->set_default();
            $default->save_db();
        }
        

        $db->close();
        unset($db);    
    }

    
    /**
    * This function returns the Tab id
    *
    * @return int             
    */
    public function get_id() 
    {
        return $this->id;
    }

    /**
    * This function sets the Tab id
    *
    * @return int             
    */
    public function set_id($id) 
    {
        $this->id = $id;
    }


    /**
    * This function returns the user of the tab.
    *
    * @return int             
    */
    public function get_user() 
    {
        return $this->user;
    }

    /**
    * This function sets the user of the tab.
    *
    * @return int             
    */
    public function set_user($user) 
    {
        $this->user = $user;
    }


    /**
    * This function returns the title to the tab
    *
    * @return int             
    */
    public function get_title() 
    {
        return $this->title;
    }

    /**
    * This function sets the title to the tab
    *
    * @return int             
    */
    public function set_title($title) 
    {
        $this->title = $title;
    }


    /**
    * This function returns the layout of the tab.
    *
    * @return int             
    */
    public function get_layout() 
    {
        return $this->layout;
    }

    /**
    * This function sets the layout of the tab.
    *
    * @return int             
    */
    public function set_layout($layout) 
    {
        $this->layout = $layout;
    }


    /**
    * This function returns if the tab is visible.
    *
    * @return int             
    */
    public function is_visible() 
    {
        return $this->visible;
    }

    /**
    * This function sets if the tab is visible.
    *
    * @return int             
    */
    public function set_visible($v) 
    {
        $this->visible = $v;
    }


    /**
    * This function returns the order of the tab.
    *
    * @return int             
    */
    public function get_order() 
    {
        return $this->order;
    }

    /**
    * This function sets the order of the tab.
    *
    * @return int             
    */
    public function set_order($order) 
    {
        $this->order = $order;
    }




    /**
    * This function returns if the tab is the default tab.
    *
    * @return boolean             
    */
    public function is_default() 
    {
        return $this->default;
    }


     /**
    * This function returns set the tab as the default tab.
    *
    * @return void             
    */
    public function set_default() 
    {
        $db     = new ossim_db();
        $conn   = $db->connect();
        
        $config = new User_config($conn);
        
        //setting it in the config file
        $config->set($this->user, 'panel_default', $this->id, 'simple', 'main');

        $this->default = TRUE;

        $db->close();
        unset($db); 
    }


    /**
    * This function returns if the tab is protected.
    *
    * @return boolean             
    */
    public function is_locked() 
    {
        return $this->locked;
    }


    public function clone_tab($user_dest = '')
    {
        $db   = new ossim_db();
        $conn = $db->connect();

        if($user_dest == '')
        {
            $user_dest = $this->user;
        }

        $user_src = ($this->locked )? '0' : $this->user;

        $_cloned = new self('', $user_dest);

        $_cloned->set_title($this->title . ' ' . _('Copy'));
        $_cloned->set_layout($this->layout);

        $_cloned->save_db();        

        $params = array(
            $_cloned->get_id(),
            $_cloned->get_user(),
            $this->id,
            $user_src
        );

        $query  = "INSERT INTO dashboard_widget_config (panel_id, user, col, fil, height, title, help, refresh, color, file, params, 
            type_id, type, asset, media) 
            SELECT ?, ?, col, fil, height, title, help, refresh, color, file, params, type_id, type, asset, media 
            FROM dashboard_widget_config WHERE panel_id = ? and user = ?";        

        $rs = $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg()); 
        }

        $db->close();
        unset($db);

        return $_cloned;
    }


    public function save_db()
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $params = array(
            $this->id,
            $this->user,
            $this->title,
            $this->layout
        );
        
        $query  = "REPLACE INTO dashboard_tab_config (id, user, title, layout) values (?, ?, ?, ?)";
        
        $rs = $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg()); 
        }
        else
        {
            $query  = "REPLACE INTO dashboard_tab_options (id, user, visible, tab_order) values (?, ?, ?, ?)";
            $params = array(
                $this->id,
                $this->user,
                $this->visible,
                $this->order
            );

            $rs = $conn->Execute($query, $params);

            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        
        $db->close();
        unset($db);
    }


    public function get_new_id()
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $query  = "SELECT max(id) as new_id FROM dashboard_tab_options WHERE id >= 1000 and user=?";
        
        $params = array
        (
            $this->user           
        );
            
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $id       = $rs->fields['new_id'];        
            $this->id = (($id != "") ? $id + 1 : 1000);
        }        
    }
    
    
    public function get_new_order()
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $query  = "SELECT max(tab_order) as new_order FROM dashboard_tab_options WHERE user=?";
        
        $params = array
        (
            $this->user           
        );
        
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else
        {
            $order       = $rs->fields["new_order"];      
            $this->order = (($order != "") ? $order + 1 : 0);
        }

        $db->close();
        unset($db);
    }


    public function print_tab($edit = FALSE)
    {   
        $title_class = 'db_tab_text';

        if($edit)
        {
            $tab_opts = '<div class="db_tab_opts">
                            <div data-url="sections/tabs/tab_menu.php?id='. $this->id .'" title="' . _('Tab Options') . '" class="tab-options ui-icon ui-icon-plus"></div>
                        </div>';

            
            if(!$this->is_locked())
            {
                $title_class  .= ' editInPlace';
            }

            if(!$this->visible)
            {
                $title_class  .= ' db_tab_text_disabled';
            }
        }

        $tab = "<div class='db_tab_tab' id='db_tab_". $this->id ."' data-id='". $this->id ."'>";


        $tab_title = "  <div class='db_tab_title'>
                            <a href='javascript:;' class='$title_class'>
                                ". $this->title."
                            </a>
                        </div>";


        $tab .= $tab_title . $tab_opts ;

        $tab .= "</div>";

        return $tab;
    }


    public function get_new_widget_oder($column)
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $params = array(
            $this->id,
            $this->user,
            intval($column)
        );
        
        $query  = "SELECT max(fil) as fil FROM dashboard_widget_config WHERE panel_id=? and user=? and col=?";
                   
        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $order = $rs->fields['fil'];     

            $db->close();
            unset($db);   

            return (($order != '') ? $order + 1 : 0);
        }

    }


    public function check_tab_perms()
    {    
        if ($this->id == '6'  && !Session::menu_perms('analysis-menu', 'EventsVulnerabilities'))   //Vulnerabilities Tab: ID 6
        {
            return FALSE;
        }             
        elseif ($this->id == '2'  && !Session::menu_perms('analysis-menu', 'IncidentsIncidents'))    //Tickets Tab: ID 2    
        {
            return FALSE;
        }            
        elseif ($this->id == '7'  && (!Session::menu_perms('configuration-menu', 'ComplianceMapping') || !Session::is_pro()) )    //Compliance Tab: ID 7
        {
            return FALSE;
        }         
        
        return TRUE;
    }

    /*******************************************************************************************************************************/
    /******************************************************** STATICS METHOD *******************************************************/
    /*******************************************************************************************************************************/   
    
    public static function get_tabs_by_user($user, $edit=FALSE)
    {
        $tab_list = array();

        $db       = new ossim_db();
        $conn     = $db->connect();

        $query    = "SELECT DISTINCT id FROM dashboard_tab_options tbo 
                     WHERE user = ? ";   

        $order    = " ORDER BY tab_order DESC";

        if(!$edit) 
        {
            $query .= " AND visible=1";
        }

        $query .= $order;


        $params = array($user);

        $rs = $conn->Execute($query, $params);

        if (!$rs) 
        {            
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {
            while (!$rs->EOF)
            {
                $id   = $rs->fields['id'];

                try
                {
                    $_tab = new self($id, $user);
                    
                    if ($_tab->check_tab_perms())
                    {
                        $tab_list[$id] = $_tab;
                        
                        unset($_tab);
                    }
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());
                }

                $rs->MoveNext();
            }
        }

        $db->close();
        unset($db);  

        return $tab_list;
    }

}

/* End of file dashboard_tab.inc */
/* Location: ../include/classes/dashboard_tab.inc */
