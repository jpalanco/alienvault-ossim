<?php
/**
* dashboard_widget.inc
*
* File dashboard_widget.inc is used to:
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
* Widget Class
*
* This class is used to manage dashboard widgets
*
*
* @package    ossim-framework\Dashboard
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Dashboard_widget
{       
    /**#@+
    * Attributes
    */
    
    /**
    * @var int  Widget ID
    * @access public 
    */
    public $id;
    
    /**
    * @var int  Panel ID where the widget belong to.
    * @access public 
    */
    public $panel_id;

    /**
    * @var int  ID of the template associated to the widget.
    * @access public 
    */
    public $type_id;
    
    /**
    * @var string  User owner of the widget.
    * @access public 
    */
    public $user;

    /**
    * @var int  Number of the col where the widget is placed.
    * @access public 
    */
    public $col;
    
    
    /**
    * @var int  Number of the row where the widget is placed.
    * @access public 
    */
    public $fil;
    
    
    /**
    * @var string  Height of the widget.
    * @access public 
    */
    public $height;
    
    
    /**
    * @var string  Title of the widget.
    * @access public 
    */
    public $title;
    
    
    /**
    * @var string  Help of the widget.
    * @access public 
    */
    public $help;
    
    
    /**
    * @var int  Refresh in seconds of the widget.
    * @access public 
    */
    public $refresh;


    /**
    * @var string  Color of the header of the widget.
    * @access public 
    */
    public $color;


    /**
    * @var string  File that is going to be loaded by the widget.
    * @access public 
    */
    public $file;


    /**
    * @var string  Type of the widget.
    * @access public 
    */
    public $type;


    /**
    * @var string  Assets restriction for the widget.
    * @access public 
    */
    public $assets;


    /**
    * @var string  Serialized array with the parameters of the widget.
    * @access public 
    */
    public $params;


    /**
    * @var string  Media content of the widget (Only in image widgets).
    * @access public 
    */
    public $media;
    
            
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id    Widget id
    */

    public function __construct($id = '')
    {
        if($id == '')
        {
            $this->id       = 0;
            $this->panel_id = 0;
            $this->type_id  = 0;
            $this->user     = 0;
            $this->col      = 0;
            $this->fil      = 0;
            $this->height   = 200;
            $this->title    = '';
            $this->help     = '';
            $this->refresh  = 0;
            $this->color    = 'db_color_1';
            $this->file     = '';
            $this->type     = '';
            $this->asset    = 'ALL_ASSETS';
            $this->params   = '';
            $this->media    = NULL;
        }
        else
        {
            self::load_widget_from_db($id);
        }        
    }

    /**
    * Class constructor
    *
    * This function loads a widget from DB
    *
    * @param string  $id    Widget id
    */

    private function load_widget_from_db($id)
    {
        $db     = new ossim_db();
        $conn   = $db->connect();

        $query  = "SELECT * FROM dashboard_widget_config WHERE id=?";
        $params = array($id);

        $rs = $conn->Execute($query,$params);
        if (!$rs) 
        {
            $exp_msg = _('Error! Widget ID not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else 
        {
            $this->id       = $id;
            $this->panel_id = $rs->fields['panel_id'];
            $this->type_id  = $rs->fields['type_id'];
            $this->user     = $rs->fields['user'];
            $this->col      = $rs->fields['col'];
            $this->fil      = $rs->fields['fil'];
            $this->height   = $rs->fields['height'];
            $this->title    = mb_convert_encoding($rs->fields['title'], 'UTF-8', 'HTML-ENTITIES');
            $this->help     = $rs->fields['help'];
            $this->refresh  = $rs->fields['refresh'];
            $this->color    = $rs->fields['color'];
            $this->file     = $rs->fields['file'];
            $this->type     = $rs->fields['type'];
            $this->asset    = $rs->fields['asset'];
            $this->params   = $rs->fields['params'];
            $this->media    = $rs->fields['media'];
        }

        $db->close();
        unset($db);        
    }

    
    /**
    * This function returns the Widget id
    *
    * @return int             
    */
    function get_id() 
    {
        return $this->id;
    }


    /**
    * This function returns the ID of the panel where the widget belongs to.
    *
    * @return int             
    */
    function get_panel_id() 
    {
        return $this->panel_id;
    }


    /**
    * This function returns the ID of the template associated to the widget
    *
    * @return int             
    */
    function get_type_id() 
    {
        return $this->type_id;
    }


    /**
    * This function returns the user owner of the widget.
    *
    * @return string             
    */
    function get_user() 
    {
        return $this->user;
    }


    /**
    * This function returns the col where the widget is located.
    *
    * @return int             
    */
    function get_col() 
    {
        return $this->col;
    }


    /**
    * This function returns the fil where the widget is located.
    *
    * @return int             
    */
    function get_fil() 
    {
        return $this->fil;
    }


    /**
    * This function returns the height of the widget.
    *
    * @return int             
    */
    function get_height() 
    {
        return $this->height;
    }


    /**
    * This function returns the title of the widget.
    *
    * @return string             
    */
    function get_title() 
    {
        return $this->title;
    }


    /**
    * This function returns the help of the widget.
    *
    * @return string             
    */
    function get_help() 
    {
        return $this->help;
    }


    /**
    * This function returns the refresh of the widget.
    *
    * @return int             
    */
    function get_refresh() 
    {
        return $this->refresh;
    }


    /**
    * This function returns the color of the widget.
    *
    * @return string             
    */
    function get_color() 
    {
        return $this->color;
    }


    /**
    * This function returns the file of the widget.
    *
    * @return string             
    */
    function get_file() 
    {
        return $this->file;
    }


    /**
    * This function returns the file of the widget.
    *
    * @return string             
    */
    function get_file_url() 
    {
        $concat   = (preg_match('/\?/', $this->file) ) ? "&" : "?";
        $url_base = ($this->type_id == 12001) ? '/ossim/' : '/ossim/dashboard/sections/';
 
        $url      = $url_base . $this->file . $concat . "id=" . $this->id;

        return $url;
    }


    /**
    * This function returns the type of the widget.
    *
    * @return string             
    */
    function get_type() 
    {
        return $this->type;
    }


    /**
    * This function returns the asset of the widget.
    *
    * @return string             
    */
    function get_asset() 
    {
        return $this->asset;
    }


    /**
    * This function returns the params of the widget
    *
    * @return array             
    */
    function get_params() 
    {
        $params = unserialize($this->params);

        if($params === FALSE)
        {
            return array();
        }
        else
        {
           return $params; 
        }

    }


    /**
    * This function returns the media of the widget.
    *
    * @return string             
    */
    function get_media() 
    {
        return $this->media;
    }


    /**
    * This function sets the Widget id
    *
    * @return void             
    */
    function set_id($id) 
    {
        $this->id = $id;
    }


    /**
    * This function sets the ID of the panel where the widget belongs to.
    *
    * @return void             
    */
    function set_panel_id($pid) 
    {
        $this->panel_id = $pid;
    }


    /**
    * This function sets the ID of the template associated to the widget
    *
    * @return void             
    */
    function set_type_id($tid) 
    {
        $this->type_id = $tid;
    }


    /**
    * This function sets the user owner of the widget.
    *
    * @return void             
    */
    function set_user($user) 
    {
        $this->user = $user;
    }


    /**
    * This function sets the col where the widget is located.
    *
    * @return void             
    */
    function set_col($col) 
    {
        $this->col = $col;
    }


    /**
    * This function sets the fil where the widget is located.
    *
    * @return void             
    */
    function set_fil($fil) 
    {
        $this->fil = $fil;
    }


    /**
    * This function sets the height of the widget.
    *
    * @return void             
    */
    function set_height($h) 
    {
        $this->height = $h;
    }


    /**
    * This function sets the title of the widget.
    *
    * @return void             
    */
    function set_title($t) 
    {
        $this->title = $t;
    }


    /**
    * This function sets the help of the widget.
    *
    * @return void             
    */
    function set_help($h) 
    {
        $this->help = $h;
    }


    /**
    * This function sets the refresh of the widget.
    *
    * @return void             
    */
    function set_refresh($rf) 
    {
        $this->refresh = $rf;
    }


    /**
    * This function sets the color of the widget.
    *
    * @return void             
    */
    function set_color($c) 
    {
        $this->color = $c;
    }


    /**
    * This function sets the file of the widget.
    *
    * @return void             
    */
    function set_file($f) 
    {
        $this->file = $f;
    }


    /**
    * This function sets the type of the widget.
    *
    * @return void             
    */
    function set_type($t) 
    {
        $this->type = $t;
    }


    /**
    * This function sets the asset of the widget.
    *
    * @return void             
    */
    function set_asset($a) 
    {
        $this->asset = $a;
    }


    /**
    * This function sets the params of the widget
    *
    * @return void             
    */
    function set_params($p) 
    {
        $this->params = serialize($p);
    }


    /**
    * This function sets the media of the widget.
    *
    * @return string             
    */
    function set_media($m) 
    {
        $this->media = $m;
    }

    
    function delete()
    {
        $db     = new ossim_db();
        $conn   = $db->connect();


        $query  = "DELETE from dashboard_widget_config WHERE id = ?";
        $params = array($this->id);

        $rs = $conn->Execute($query,$params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $db->close();
        unset($db);
        
    }


    function is_widget_editable()
    {
        try 
        {
            $tab = new Dashboard_tab($this->panel_id);
        }
        catch (Exception $e)
        {
            return FALSE;
        }

        if($tab->is_locked())
        {
            return FALSE;
        }

        return TRUE;
        
    }

    function get_widgets_panel($panel, $object = TRUE)
    {
        $db       = new ossim_db();
        $conn     = $db->connect();

        $response = array('status' => 1, 'data' => array());
        $widgets  = array();

        $user     = ($panel < 1000)? '0' : Session::get_session_user();

        $query    = "SELECT * FROM dashboard_widget_config w where w.panel_id = ? and w.user = ? order by w.col, w.fil";        
        

        
        $params   = array(
            $panel,
            $user
        );

        if (!$rs = $conn->Execute($query, $params))
        {
            $response['status'] = 1 ;
            $response['data']   = array();
        }   
        else
        {
            while ( !$rs->EOF )
            {
                if($rs->fields['id'] != '')
                {
                    $w = new Dashboard_widget(); 
                    
                    $w->set_id($rs->fields['id']);
                    $w->set_type_id($rs->fields['type_id']);         
                    $w->set_title(mb_convert_encoding($rs->fields['title'], 'UTF-8', 'HTML-ENTITIES'));
                    $w->set_file($rs->fields['file']);
                    $w->set_help($rs->fields['help']);
                    $w->set_color($rs->fields['color']);
                    $w->set_height($rs->fields['height']);
                    $w->set_fil($rs->fields['fil']);
                    $w->set_col($rs->fields['col']);

                    if($object)
                    {
                        $widgets[$rs->fields['col']][$rs->fields['fil']] = $w;
                    }
                    else
                    {
                        $widgets[$rs->fields['col']][$rs->fields['fil']] = $w->get_widget_js_parameter();
                    }
                    
                }

                $rs->MoveNext();
            }

            $response['status'] = 0;
            $response['data']   = $widgets;
        }

        $db->close();

        return $response['data'];
    }


    function reorder_widgets_position($tab, $layout)
    {
        $db   = new ossim_db();
        $conn = $db->connect();

        if(!is_object($tab))
        {            
            $exp_msg = _('It was not possible to change the column layout');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $cols = $tab->get_layout();
    
        if($cols > $layout)
        {   
            $set_col = $layout -1;
            $order   = $tab->get_new_widget_oder($set_col);

            for ($i = $layout; $i < $cols; $i++)
            {              
                $params = array(
                    $tab->get_id(),
                    $i,
                    Session::get_session_user()
                );
                
                $query = "SELECT id FROM dashboard_widget_config WHERE panel_id = ? and col=? and user=? ORDER BY fil ASC";
                    
                if (!$rs = $conn->Execute($query, $params))
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                } 
                else 
                {
                    $list_id = array();
                    while (!$rs->EOF)
                    {                   
                        $list_id[]  = $rs->fields['id'];                        
                        $rs->MoveNext();
                    }
                
                    foreach($list_id as $id)
                    {                        
                        $params = array(
                            $set_col,
                            $order,
                            $id
                        );
                        
                        $query = "UPDATE dashboard_widget_config SET col = ?, fil = ? WHERE id = ?";

                        if($conn->Execute($query, $params) === FALSE)
                        {
                            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());                 
                        }

                        $order ++;
                    }           
                }       
            }   
        } 

        $db->close();
        unset($db);
    }

    function get_widget_js_parameter()
    {
        $widget['id']     = $this->get_id();
        $widget['color']  = $this->get_color();
        $widget['title']  = $this->get_title();
        $widget['help']   = $this->get_help();
        $widget['height'] = $this->get_height();
        $widget['src'   ] = $this->get_file_url();

        return $widget;
    }

}

/* End of file dashboard_widget.inc */
/* Location: ../include/classes/dashboard_widget.inc */