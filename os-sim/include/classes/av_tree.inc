<?php
/**
* av_tree.inc
*
* File av_tree.inc is used to:
*   - To draw all kind of trees
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Av_tree Class
*
* Class for drawing all kind of trees
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Av_tree
{   
    /**
    * Max results by branch
    *
    * @const integer
    */ 
    const MAX_RESULTS = 200;
    
    /**
    * Pixmaps path
    *
    * @const string
    */ 
    const PIXMAPS_PATH = '../../pixmaps/theme';
           
    
    /**
    * Tree Key
    *
    * @var string
    * @access private
    */ 
    private $key;
    
    /**
    * Tree Pagination 
    *
    * @var integer
    * @access private
    */
    private $page;
    
    /**
    * Tree filters
    *
    * @var array
    * @access private
    */
    private $filters;


    /**
    * Configuration options
    *
    * @var array
    * @access private
    */
    private $config;
    
    /**
    * Tree
    *
    * @var array
    * @access private
    */ 
    private $tree;
    
        
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string   $key       [Optional] Tree key
    * @param integer  $page      [Optional] Tree page
    * @param array    $filters   [Optional] Tree filters
    * @param array    $config    [Optional] Tree configuration
    */  
    public function __construct($key, $page = 1, $filters = array(), $config = array()) 
    {     
        $this->set_key($key);     
        
        $this->set_page($page);
                                
        $this->set_filters($filters);
        
        $this->set_config($config);
                
        $this->tree = array();
    }
    
    
    /**
    * This function returns tree keys
    *
    * @return string
    */ 
    private function get_key()
    {
        return $this->key;
    }
    
    
    /**
    * This function returns tree filters
    *
    * @return array
    */ 
    private function get_filters()
    {
        return $this->filters;
    }
    
    
    /**
    * This function returns maximum length of text (Title)
    *
    * @return integer
    */ 
    private function get_max_text_length()
    {
        return $this->config['max_text_length'];
    }
    
    
    /**
    * This function returns extra options
    *
    * @return string
    */ 
    private function get_extra_options()
    {
        return $this->config['extra_options'];
    }
    
    
    /**
    * This function returns current tree page
    *
    * @return integer
    */ 
    private function get_page()
    {
        return $this->page;
    }
    
    
    /**
    * This function returns the tree
    *
    * @return array
    */ 
    private function get_tree()
    {
        return $this->tree;
    }
    
    
    /**
    * This function sets tree key
    *
    * @param string  $key  Tree key
    *
    * @throws Exception  If tree key is empty
    *
    * @return void
    */ 
    private function set_key($key)
    {
        if (!empty($key))
        {
            $this->key = $key;
        }
        else
        {
            $exp_msg = _('Error! Tree key is empty');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function generates a node key based on data set
    *
    * @param string   $prefix     Prefix
    * @param array    $key_data   Data set to generate the key
    *
    * @return string
    */ 
    private function generate_key_by_filter($prefix, $key_data)
    {
        $key = $prefix;
                        
        if (is_array($key_data) && !empty($key_data))
        {
            $key .= '_'.base64_encode(serialize($key_data));
        }
        
        return $key;
    }
    
    /**
    * This function generates a node random key
    *
    * @param string   $prefix   Prefix
    *
    * @return string
    */ 
    private function generate_random_key($prefix)
    {
        $key = uniqid($prefix.'_');
                     
        return $key;
    }
    
        
    /**
    * This function sets tree page
    *
    * @param integer  $page  Tree page
    *
    * @return void
    */ 
    private function set_page($page)
    {
        $page = intval($page);
        
        $this->page = ($page < 1) ? 1 : $page;
    }
    
        
    /**
    * This function sets tree filters
    *
    * @param array  $filters  Tree filters
    *
    * @return void
    */ 
    private function set_filters($filters)
    {
        $this->filters = array();
        
        if (is_array($filters) && !empty($filters))
        {
            foreach ($filters as $f_key => $f_data)
            { 
                $this->add_new_filter($f_key, $f_data);
            }  
        }
    }
    
    
    /**
    * This function adds new filter
    *
    * @param string  $f_key  Filter key
    * @param string  $f_data Filter data
    *
    * @throws Exception  If $f_data is unllowed
    *
    * @return void
    */ 
    private function add_new_filter($f_key, $f_data)
    {     
        if ($this->check_filter($f_key, $f_data) == TRUE)
        {         
            $this->filters[$f_key] = $f_data;
        }
        else
        {           
            $exp_msg = _("Error! Filter $f_key has unallowed value");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
        
    
    
    /**
    * This function checks filter
    *
    * @param string  $f_key  Filter key
    * @param string  $f_data Filter data
    *
    * @return boolean
    */ 
    private function check_filter($f_key, $f_data)
    {
       ossim_clean_error();
       
       $res = TRUE;

       $validate = array(
            'host_id'     =>  array('validation' => array(OSS_HEX),                          'e_message'  =>  'illegal:' . _('Host ID')),
            'sensor_id'   =>  array('validation' => array(OSS_HEX),                          'e_message'  =>  'illegal:' . _('Sensor ID')),
            'entity_id'   =>  array('validation' => array(OSS_HEX),                          'e_message'  =>  'illegal:' . _('Entity ID')),
            'ip'          =>  array('validation' => array(OSS_IP_ADDR),                      'e_message'  =>  'illegal:' . _('Host IP')),
            'cclass'      =>  array('validation' => array(OSS_CCLASS),                       'e_message'  =>  'illegal:' . _('C-class')),
            'p_id'        =>  array('validation' => array(OSS_DIGIT),                        'e_message'  =>  'illegal:' . _('Property ID')),
            'p_value'     =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT),          'e_message'  =>  'illegal:' . _('Property value')),
            'mac'         =>  array('validation' => array(OSS_MAC),                          'e_message'  =>  'illegal:' . _('MAC Address')),
            'cpe'         =>  array('validation' => array(OSS_ALPHA, OSS_PUNC, OSS_BRACKET), 'e_message'  =>  'illegal:' . _('Software CPE')),
            'service'     =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT),          'e_message'  =>  'illegal:' . _('Service')),
            'port'        =>  array('validation' => array(OSS_PORT),                         'e_message'  =>  'illegal:' . _('Port')),
            'protocol'    =>  array('validation' => array(OSS_DIGIT),                        'e_message'  =>  'illegal:' . _('Protocol')),
            'e_direction' =>  array('validation' => array('up|down'),                        'e_message'  =>  'illegal:' . _('Entity Direction'))
       );
                  
       if (is_array($f_data))
       {                   
           array_pop($f_data);                      
                                                    
           foreach($f_data as $r_key => $r_data)
           {
               $res = $this->check_filter($r_key, $r_data);
               
               if ($res == FALSE)
               {
                   break;
               }
           }
       }
       else
       {
           $parameters = $validate[$f_key]['validation'];
       
           array_unshift($parameters, $f_data);
           array_push($parameters, $validate[$f_key]['e_message']);

           call_user_func_array('ossim_valid', $parameters);

           if (ossim_error())
           { 
               $res = FALSE;
           }   
       }  
                 
       return $res;
    }
        
    
    /**
    * This function sets the tree
    *
    * @param array  $tree  Tree
    *
    * @return void
    */ 
    private function set_tree($tree)
    {
        if (is_array($tree) && !empty($tree))
        {
            $this->tree = $tree;
        }
        else
        {
            $this->tree = array();
        }
    }
    
    
    /**
    * This function sets tree configuration
    *
    * @param array  $config  Tree config
    *
    * @return void
    */ 
    private function set_config($config)
    {
        if (is_array($config) && !empty($config))
        {
            $max_text_length = intval($config['max_text_length']);
            $max_text_length = ($max_text_length < 20) ? 20 : $max_text_length;
            
            $extra_options   = $config['extra_options'];
            
            $this->config  = array(
                'max_text_length'   => $max_text_length,
                'extra_options'     => $extra_options
            );
        }
    }
    
    
    /**
    * This function returns or displays the JSON tree
    *
    * @param object   $conn      Database access object
    * @param boolean  $display   [Optional] Display on screen or return the tree
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string|void
    */ 
    public function draw($conn, $display = FALSE)
    {
        $tree = array();
        
        Ossim_db::check_connection($conn);
        
        $tree = $this->draw_by_key($conn);
                      
        $this->set_tree($tree);       
             
        $json_encode = @json_encode($this->get_tree());
        
        if (json_last_error() != JSON_ERROR_NONE)
        {
            $tree = array(
                'title'        => '',
                'key'          => 'tree_error',
                'isFolder'     => TRUE,
                'icon'         => self::PIXMAPS_PATH.'/any.png',
                'hideCheckbox' => TRUE, 
                'expand'       => TRUE,
                'noLink'       => TRUE,
                'hideCheckbox' => TRUE,
                'addClass'     => 'size12',
                'children'     => array(
                    'title'        => '<span>'.self::utf8_encode(_('Load error')).'</span>',
                    'key'          => 'load_error',
                    'icon'         => '',
                    'hideCheckbox' => TRUE,
                    'noLink'       => TRUE,
                    'hideCheckbox' => TRUE,
                    'addClass'     => 'bold_red dynatree-statusnode-error'
                )
            );
            
            $json_encode = @json_encode($tree);
        }
        
        if ($display == FALSE)
        {
            return $json_encode;
        }
        else
        {
            echo $json_encode;
        }
    }
    
    
    /**
    * This function returns the tree according to current key
    *
    * @param object   $conn  Database access object
    *
    * @return array
    */ 
    public function draw_by_key($conn)
    {        
        $key = $this->get_key();
                        
        if (preg_match('/^(inventory_tree|property_tree|inventory_branch|property_branch)/', $key))
        {
            return $this->draw_properties($conn);
        }
        elseif (preg_match('/^(cclass_by_inventory_branch|hosts_by_cclass)/', $key))
        {
            return $this->draw_hosts($conn);
        }
        elseif (preg_match('/^service_tree/', $key))
        {
            return $this->draw_services($conn);
        }
    }
                
    
    /**
    * This function returns property tree
    *
    * @param object   $conn   Database access object
    *
    * @return array
    */ 
    private function draw_properties($conn)
    {
        $tree = array();
                
        //Retrieving filters
        $filters = $this->get_filters();
        
        $host_id = $filters['host_id'];
        $p_id    = $filters['p_id'];
                        
        //Inventory Tree or Property Tree
        if ($p_id == '')
        {
            $title = (!empty($host_id)) ? _('Properties') : _('Assets by Property');
            $title = self::utf8_encode($title);            
            
            $tree = array(
                'title'        => $title,
                'key'          => $this->get_key(),
                'isFolder'     => TRUE,
                'icon'         => self::PIXMAPS_PATH.'/any.png',
                'expand'       => TRUE,
                'noLink'       => TRUE,
                'hideCheckbox' => TRUE,
                'children'     => array()
            );
            
            //Standard properties
            $tree['children']   = $this->draw_host_properties($conn);

            //MAC property
            $tree['children'][] = $this->draw_macs($conn);
            
            //Service property                       
            $tree['children'][] = $this->draw_services($conn);
            
            //Software Property
            $tree['children'][] = $this->draw_software($conn);
            
            
            //Only for inventory
            if (empty($host_id))
            {
                //Filters for AJAX call (see parameter isLazy)
                $filters = array('p_id' => 0);
                                
                //Key
                $key = $this->generate_random_key('cclass_by_inventory_branch');
                            
                //All hosts with properties
                $tree['children'][] = array(
                    'title'        => self::utf8_encode(_('All hosts')),
                    'key'          => $key,
                    'filters'      => $filters,
                    'isFolder'     => TRUE,
                    'icon'         => self::PIXMAPS_PATH.'/host_add.png',
                    'expand'       => FALSE,
                    'hideCheckbox' => TRUE,
                    'isLazy'       => TRUE
                );
            }
        }
        else
        {           
            //Specific branch
            
            switch($p_id)
            {
                //Services
                case '40':
                    $tree = $this->draw_services($conn);
                break;
                
                //MACs Address
                case '50':
                    $tree = $this->draw_macs($conn);
                break;
                
                //Software
                case '60':
                    $tree = $this->draw_software($conn);
                break;
                
                //Host properties
                default:
                    $tree = $this->draw_host_properties($conn);
                break;
            }
        }
             
        return $tree;
    }
             
    
    /**
    * This function returns host property tree (Properties from table Host_properties)
    *
    * @param object   $conn   Database access object
    *
    * @return array
    */ 
    private function draw_host_properties($conn)
    {        
        //Retrieving filters
        $filters = $this->get_filters();
        
        $host_id = $filters['host_id'];
        $p_id    = $filters['p_id'];
        
        $icons = Properties::get_pixmaps();  
                     
        // Draw Property tree or Inventory tree
               
        if ($p_id == '')
        {
            $p_obj      = new Properties($conn);
            $properties = $p_obj->get_properties();
                     
            if (!empty($host_id))
            {
                //Property Tree  
                
                $t_properties = Asset_host_properties::count_all($conn, $host_id);
                              
                foreach ($properties as $p_id => $p_data) 
                {
                    //Getting icon
                    $icon = (array_key_exists($p_data["name"], $icons)) ? $icons[$p_data['name']] : 'folder.png';
                    
                    //Hosts with the property
                    $has_property = array_key_exists($p_id, $t_properties);
                    
                    //Filters
                    
                    //Add new filter for recursive call
                    $this->add_new_filter('p_id', $p_id);
                    
                    
                    //Filters (No AJAX call)                   
                    $filters = array('p_id' => $p_id, 'host_id' => $host_id);

                    //Key
                    $key = $this->generate_random_key('property_branch');
                    
                    $t_children = $this->draw_host_properties($conn);
                                                          
                    $tree[] = array(
                        'title'        => $p_data['description'],
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => ($has_property == TRUE) ? TRUE : FALSE,
                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                        'expand'       => TRUE,
                        'hideCheckbox' => TRUE,
                        'isLazy'       => FALSE,
                        'children'     => $t_children
                    );
                }
            }
            else
            {
                //Inventory tree
                
                $t_properties = Asset_host_properties::count_all($conn);
                
                foreach ($properties as $p_id => $p_data) 
                {
                    //Getting icon
                    $icon = (array_key_exists($p_data["name"], $icons)) ? $icons[$p_data['name']] : 'folder.png';
                    
                    //Hosts with the property
                    $has_property = array_key_exists($p_id, $t_properties); 
                    
                    //Filters for AJAX call (see parameter isLazy)
                    $filters = array('p_id' => $p_id);
                    
                    //Key
                    $key = $this->generate_random_key('inventory_branch');
                    
                    $tree[] = array(
                        'title'        => $p_data['description'],
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => ($has_property == TRUE) ? TRUE : FALSE,
                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                        'expand'       => FALSE,
                        'hideCheckbox' => TRUE,
                        'isLazy'       => ($has_property == TRUE) ? TRUE : FALSE
                    );
    
                }
            }
        }
        else
        {
            $q_where = "host_properties.property_ref = $p_id";

            if (!empty($host_id))
            {
                //Property Tree

                $q_where .= " AND host_properties.host_id = UNHEX('$host_id')";
                                
                $filters = array(
                    'where' => $q_where      
                );
                                
                list($p_values, $p_total) = Asset_host_properties::get_list($conn, $filters);
                                                                               
                if ($p_total > 0)
                {
                    //Property tree
                    $p_values    = array_pop($p_values);
                    
                    $p_name      = $p_values[$p_id]['name'];
                    $p_date      = $p_values[$p_id]['date'];
                    $p_source    = $p_values[$p_id]['source']['name'];
                    $p_source_id = $p_values[$p_id]['source']['id'];                                        
                    $p_values    = $p_values[$p_id]['values'];
                                        
                    foreach($p_values as $p_data)
                    {
                        $p_value = $p_data;

                        //User logged

                        if ($p_id == 8)
                        {
                            $user_domain = explode("|", $p_data);
                            $p_value  = $user_domain[0];
                            $p_value .= ($user_domain[1] != '') ? '@'.$user_domain[1] : '';
                        }
                        
                        //Title
                        $title = $p_value;
                        
                        if (strlen($p_value) > $this->get_max_text_length())
                        {
                            $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                        }
                        
                        //Icon
                        if ($p_id == 3)
                        {
                            $icon = Properties::get_os_pixmap($p_value, '../', TRUE);
                        }
                        else
                        {
                            $icon = (array_key_exists($p_name, $icons)) ? $icons[$p_name] : 'folder.png';
                        }   


                        //Data
                                    
                        $t_children = array();
                        $d_title = "<span class='size12n'>".self::utf8_encode(_('Date')).": </span><span class='ml3 size12b'>".$p_date."</span>";
                        
                                            
                        $t_children[] = array(
                            'title'        => $d_title,
                            'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                            'hideCheckbox' => TRUE,
                            'noLink'       => TRUE
                        );
                        
                        
                        $d_title = "<span class='size12n'>".self::utf8_encode(_('Source')).": </span><span class='ml3 size12b'>".$p_source."</span>";

                        $t_children[] = array(
                            'title'        => $d_title,
                            'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                            'hideCheckbox' => TRUE,
                            'noLink'       => TRUE
                        ); 
                         
                        //Add children to tree
                        

                        //Filters (No AJAX call)
                        $filters = array(
                            'p_id' => $p_id, 
                            'host_id' => $host_id, 
                            'p_value' => array(
                                'hp_value'  => $p_data,
                                'source_id' => $p_source_id
                             )    
                        );
                        
                        //Key
                        $key = $this->generate_random_key('values_by_property_branch');

                        $tree[] = array(
                            'title'        => $title,
                            'key'          => $key,
                            'filters'      => $filters,
                            'isFolder'     => TRUE,
                            'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                            'tooltip'      => $p_value,
                            'expand'       => FALSE,
                            'hideCheckbox' => FALSE,
                            'isLazy'       => FALSE,
                            'children'     => $t_children
                        );                                
                    }
                }                          
            }
            else
            {
                //Inventory Tree
                
                //Values already inserted
                $b_values = array();
                
                $q_filters = array(
                    'where' => $q_where      
                );

                
                list($all_p_values, $p_all_total) = Asset_host_properties::get_list($conn, $q_filters);
                
                //Values from all host
                if ($p_all_total > 0)
                {                                      
                    foreach($all_p_values as $p_data)
                    {                        
                        //Values for each host
                                                
                        $p_values = $p_data[$p_id];
                        $p_name   = $p_data[$p_id]['name'];
                                            
                        if (is_array($p_values) && !empty($p_values))
                        {
                            foreach($p_values['values'] as $p_data)
                            {
                                $pd_key = md5($p_data);                                
                                
                                //If property value is unique, we add this value to tree
                                if (!array_key_exists($pd_key, $b_values))
                                {
                                    $b_values[$pd_key] = $pd_key;
                                    
                                    $p_value = $p_data;
                        
                                    //User logged  
                                    if ($p_id == 8)
                                    {
                                        $user_domain = explode("|", $p_data);
                                        $p_value  = $user_domain[0];
                                        $p_value .= ($user_domain[1] != '') ? '@'.$user_domain[1] : '';
                                    }
                                    
                                    //Title
                                    $title = $p_value;
                                    
                                    if (strlen($p_value) > $this->get_max_text_length())
                                    {
                                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                                    }

                                    //Icon
                                    if ($p_id == 3)
                                    {
                                        $icon = Properties::get_os_pixmap($p_value, '../', TRUE);  
                                    }
                                    else
                                    {                                                                               
                                        $icon = (array_key_exists($p_name, $icons)) ? $icons[$p_name] : 'folder.png';
                                    }
                                    
                                    //Filters for AJAX call (see parameter isLazy)
                                    $filters = array(
                                        'p_id' => $p_id, 
                                        'p_value' => array(
                                            'hp_value' => $p_data
                                         )    
                                    );

                                    //Key
                                    $key = $this->generate_random_key('cclass_by_inventory_branch');
                                    
                                    //Inventory tree
                                    $tree[] = array(
                                        'title'        => $title,
                                        'key'          => $key,
                                        'filters'      => $filters,
                                        'isFolder'     => TRUE,
                                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                                        'tooltip'      => $p_value,
                                        'expand'       => FALSE,
                                        'hideCheckbox' => TRUE,
                                        'isLazy'       => TRUE
                                    ); 
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tree;        
    }
        
    
    /**
    * This function returns MAC tree
    *
    * @param object   $conn   Database access object
    *
    * @return array
    */ 
    private function draw_macs($conn)
    {
        //Retrieving filters
        $filters = $this->get_filters();
        
        $host_id = $filters['host_id'];
        
        $icons = Properties::get_pixmaps();
                
        $icon  = (array_key_exists('macaddress', $icons)) ? $icons['macaddress'] : 'folder.png'; 
                     
        $tree = array(
            'title'        => self::utf8_encode(_('MAC Address')),
            'icon'         => self::PIXMAPS_PATH.'/'.$icon,
            'hideCheckbox' => TRUE,
            'children'     => array()
        );        
        
                    
        if (empty($host_id))
        {
            //All MACs for Inventory Tree
            
            list($ips, $p_values) = Asset_host_ips::get_list($conn);  
            
            //Filters for AJAX call (see parameter isLazy)
            $filters = array('p_id' => 50);
            
            $tree['filters'] = $filters;               
                        
            if (is_array($p_values) && !empty($p_values))
            {                                
                //Key
                $key = $this->generate_random_key('inventory_branch');              
                
                $tree['key'] = $key;          
                
                //More tree options
                $tree['isFolder'] = TRUE;
                $tree['isLazy']   = TRUE;                
                
                $p_values = array_pop($p_values);                      
                
                foreach($p_values as $p_data)
                {               
                    //Title
                    $title = $p_data['mac'];
                    
                    if (strlen($p_value) > $this->get_max_text_length())
                    {
                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                    }
                    
                    $title = "<span class='size12n'>$title</span>";
                                                        
                    //Filters for AJAX call (see parameter isLazy)
                    $filters = array(
                        'p_id' => 50, 
                        'p_value' => array(
                            'mac' => $p_data['mac']
                        )
                    );
                                       
                    //Key
                    $key = $this->generate_random_key('cclass_by_inventory_branch');
                                 
                    
                    $tree['children'][] = array (
                        'title'        => $title,
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => TRUE,
                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                        'tooltip'      => $tooltip,
                        'hideCheckbox' => TRUE,
                        'isLazy'       => TRUE
                    );
                }
            }         
        }
        else
        {
            //All MACs for Property Tree
            
            $q_filters = array(
                'where' => "host_ip.host_id = UNHEX('$host_id')"      
            );        
            
            list($ips, $p_values) = Asset_host_ips::get_list($conn, $q_filters);
            
            //Filters (No AJAX call)
            $filters = array('p_id' => 50, 'host_id' => $host_id);
            
            $tree['filters'] = $filters;
            
            if (is_array($p_values) && !empty($p_values))
            {
                $p_values = array_pop($p_values);                                          
                
                //Key
                $key = $this->generate_random_key('property_branch');              
                
                $tree['key'] = $key;                
              
                //More tree options
                $tree['isFolder'] = TRUE;
                                    
                foreach ($p_values as $p_data)
                {
                    //Title
                    $title = $p_data['mac'].' ['.$p_data['ip'].']';
                    
                    if (strlen($p_value) > $this->get_max_text_length())
                    {
                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                    }
                    
                    $title = "<span class='size12b'>$title</span>";  

                    //Filters (No AJAX call)
                    $filters = array(
                        'p_id'    => 50, 
                        'host_id' => $host_id, 
                        'p_value' => array(
                            'ip'  => $p_data['ip'],
                            'mac' => $p_data['mac']
                        ));              
                    
                    //Key
                    $key = $this->generate_random_key('values_by_property_branch');
                    
                    $tree['children'][] = array (
                        'title'        => $title,
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => FALSE,
                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                        'tooltip'      => $tooltip,
                        'hideCheckbox' => FALSE
                    );
                }
            }
        }
        
        
        if (empty($tree['children']))
        {
            $tree['children'][] = array (
                'title'        => self::utf8_encode(_('No MACs found')),
                'hideCheckbox' => TRUE,
                'isLink'       => FALSE
            );
        }        
        
        
        return $tree;
    }
    
    
    
    /**
    * This function returns service tree
    *
    * @param object   $conn  Database access object
    *
    * @return array
    */ 
    private function draw_services($conn)
    {
        //Retrieving filters
        $filters = $this->get_filters();
        
        $host_id = $filters['host_id'];
        
        
        $icons = Properties::get_pixmaps();            
                
        $icon  = (array_key_exists('service', $icons)) ? $icons['service'] : 'folder.png'; 
                     
        $tree = array(
            'title'        => self::utf8_encode(_('Services')),           
            'icon'         => self::PIXMAPS_PATH.'/'.$icon,
            'hideCheckbox' => TRUE,
            'children'     => array()
        );
        
                      
        if (empty($host_id))
        {
             //Filters for AJAX call (see parameter isLazy)
            $filters = array('p_id' => 40);
            
            $tree['filters'] = $filters;
                
            //All services for Inventory Tree
            
            $p_values = Asset_host_services::get_all($conn); 
            
            if (is_array($p_values) && !empty($p_values))
            {              
                //Key
                $key = $this->generate_random_key('inventory_branch');              
                
                $tree['key'] = $key;                
               
                //More tree options
                $tree['isFolder'] = TRUE;
                $tree['isLazy']   = TRUE;

                foreach ($p_values as $p_data)
                {                    
                    $p_value = $p_data['service'];
                    
                    //Service                    
                    $service  = $p_value;                    
                    
                    //Port and Protocol
                    $port           = $p_data['port'];
                    $protocol_name  = $p_data['protocol']['name'];
                    $protocol_id    = $p_data['protocol']['id'];                      
                    
                    $port_and_protocol = ($port > 0) ? $port."/".$protocol_name : $protocol_name;             
                                        
                    //Title
                    $title = $p_value;
                    
                    if (strlen($p_value) > $this->get_max_text_length())
                    {
                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                    }
                    
                    $title = "<span class='size12n nagios'>$title</span><span class='size10n ml3 nagios'> ($port_and_protocol)</span>";
                    
                    //Tooltip
                    $tooltip = $p_value." ($port_and_protocol)";

                    //Filters for AJAX call (see parameter isLazy)
                    $filters = array(
                        'p_id' => 40, 
                        'p_value' => array(
                            'service'   => $service, 
                            'port'      => $port, 
                            'protocol'  => $protocol_id                    
                        )
                    );
                    
                    //Key
                    $key = $this->generate_random_key('cclass_by_inventory_branch');
                    
                    
                    $tree['children'][] = array (
                        'title'        => $title,
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => TRUE,
                        'icon'         => self::PIXMAPS_PATH.'/ports.png',
                        'tooltip'      => $tooltip,                    
                        'hideCheckbox' => TRUE,
                        'isLazy'       => TRUE                                           
                    );
                }
            }
        }
        else
        {
            //All services for Property Tree            
            
            $q_filters = array(
                'where' => "host_services.host_id = UNHEX('$host_id')"    
            );      
                   
            $_data_list = Asset_host_services::get_list($conn, $q_filters);
            $p_values   = $_data_list[0];
            
            //Filters (No AJAX call)
            $filters = array('p_id' => 40, 'host_id' => $host_id);
            
            $tree['filters'] = $filters;
            
            if (is_array($p_values) && !empty($p_values))
            {                                
                //Key
                $key = $this->generate_random_key('property_branch');              
                
                $tree['key'] = $key;
             
                //More tree options
                $tree['isFolder'] = TRUE;
                
                $p_values = array_pop($p_values);           
                
                foreach ($p_values as $p_data)
                {                    
                    $p_value = $p_data['service'];
                    
                    //Service
                    $service  = $p_value;
                    
                    //IP
                    $ip = $p_data['ip'];
                    
                    //Port and Protocol
                    $port          = $p_data['port'];
                    $protocol_name = $p_data['protocol']['name'];
                    $protocol_id   = $p_data['protocol']['id'];
                    
                    $port_and_protocol = ($port > 0) ? $port."/".$protocol_name : $protocol_name;
                    
                    //Version
                    $version = $p_data['version'];
                    
                    //Date
                    $date = $p_data['date'];
                    
                    //Source
                    $source_name = $p_data['source']['name'];
                    $source_id   = $p_data['source']['id'];
                    
                    //Nagios
                    $nagios = $p_data['nagios']['enabled'];
        
                    
                    //Title
                    $title = $p_value;
                    
                    if (strlen($p_value) > $this->get_max_text_length())
                    {
                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                    }


                    $title  = "<span class='size12n nagios'>$title</span><span class='size10n ml3 nagios'> ($port_and_protocol)</span>";
                    $title .= " <span>[".$ip."]</span>";
                                
                    //Tooltip
                    $tooltip  = $service." ($port_and_protocol) [".$ip."]";                                                          
                                                   
                    //Filters for AJAX call (enable or disable Nagios)
                    $filters = array(
                        'p_id' => 40, 
                        'p_value' => array(
                            'ip'        => $ip,
                            'service'   => $service, 
                            'port'      => $port, 
                            'protocol'  => $protocol_id,
                            'nagios'    => $nagios,
                            'version'   => $version,
                            'source_id' => $source_id
                        )
                    );
                    
                    //Key
                    $key = $this->generate_random_key('values_by_property_branch');
                                   
                    //Data 
                                        
                    $t_children = array();
                                                            
                    if ($nagios == 1) 
                    {
                        $nagios_status = "<img class='update_nagios' src='/ossim/pixmaps/theme/tick.png'/>";
                    }
                    else
                    {
                        $nagios_status = "<img class='update_nagios' src='/ossim/pixmaps/theme/cross.png'/>";
                    }
                 
                    $v_txt   = (!empty($version)) ? $version : ' - ';
                    $d_title = "<span class='size12n'>".self::utf8_encode(_('Version')).": </span><span class='ml3 size12b'>".$v_txt."</span>";
                                                                                
                    $t_children[] = array(
                        'title'        => $d_title,                        
                        'isFolder'     => FALSE,
                        'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                        'hideCheckbox' => TRUE,
                        'noLink'       => TRUE
                    );
                    
                    
                    $d_title = "<span class='size12n'>".self::utf8_encode(_('Date')).": </span><span class='ml3 size12b'>".$date."</span>";
                                                           
                    $t_children[] = array(
                        'title'        => $d_title,
                        'isFolder'     => FALSE,
                        'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                        'hideCheckbox' => TRUE,
                        'noLink'       => TRUE
                    );
                    
                    
                    $d_title = "<span class='size12n'>".self::utf8_encode(_('Source')).": </span><span class='ml3 size12b'>".$source_name."</span>";
                                                           
                    $t_children[] = array(
                        'title'        => $d_title,                                              
                        'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                        'hideCheckbox' => TRUE,
                        'noLink'       => TRUE
                    );


                    $d_title = "<span class='size12n'>".self::utf8_encode(_('Nagios')).": </span><span class='ml3 size12b'>".$nagios_status."</span>";                                   
                                        
                    $t_children[] = array(
                        'title'        => $d_title,
                        'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                        'hideCheckbox' => TRUE,
                        'noLink'       => FALSE
                    );
                    
                    
                    //Add children to tree
                    
                    $tree['children'][] = array (
                        'title'        => $title,
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => TRUE,
                        'icon'         => self::PIXMAPS_PATH.'/ports.png',
                        'tooltip'      => $tooltip,
                        'hideCheckbox' => FALSE,
                        'isLazy'       => FALSE,
                        'children'     => $t_children
                    );                
                }
            }
        }
        
        
        if (empty($tree['children']))
        {
            $tree['children'][] = array (
                'title'        => self::utf8_encode(_('No services found')),
                'hideCheckbox' => TRUE,
                'isLink'       => FALSE
            );
        }   
   
        return $tree;
    }
    
    
    /**
    * This function returns software tree
    *
    * @param object   $conn  Database access object
    *
    * @return array
    */ 
    private function draw_software($conn)
    {
        //Retrieving filters
        $filters = $this->get_filters();
        
        $host_id = $filters['host_id'];

        $icons = Properties::get_pixmaps();            
        
        $icon  = (array_key_exists('software', $icons)) ? $icons['software'] : 'folder.png'; 
        
        $tree = array(
            'title'        => self::utf8_encode(_('Software')),
            'icon'         => self::PIXMAPS_PATH.'/'.$icon,
            'hideCheckbox' => TRUE,
            'children'     => array()
        );


        if (empty($host_id))
        {
            //All software for Inventory Tree
            
            list($p_values, $total) = Asset_host_software::get_all($conn);
            
            //Filters for AJAX call (see parameter isLazy)
            $filters = array('p_id' => 60);
            
            $tree['filters'] = $filters; 
            
            if (is_array($p_values) && !empty($p_values))
            {                                
                //Key
                $key = $this->generate_random_key('inventory_branch');              
                
                $tree['key'] = $key;
                             
                //More tree options
                $tree['isFolder'] = TRUE;
                $tree['isLazy']   = TRUE;

                foreach ($p_values as $p_data)
                {
                    //Sometimes line and/or banner are empty
                    $p_value = (!empty($p_data['line'])) ? $p_data['line'] : $p_data['banner'];
                    
                    if (empty($p_value))
                    {
                        $p_value = $p_data['cpe'];
                    }
                    
                    //Title
                    $title = $p_value;
                    
                    if (strlen($p_value) > $this->get_max_text_length())
                    {
                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                    }
                    
                    $title =  "<span class='size12n'>$title</span>";
                    
                    //Tooltip
                    $tooltip = $p_value;
                    
                    //Filters for AJAX call (see parameter isLazy)
                    $filters = array(
                        'p_id' => 60, 
                        'p_value' => array(
                            'cpe' => $p_data['cpe']
                        )
                    );
                    
                    //Key
                    $key = $this->generate_random_key('cclass_by_inventory_branch');       
                    
                    $tree['children'][] = array (
                        'title'        => $title,
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => TRUE,
                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                        'tooltip'      => $tooltip,
                        'hideCheckbox' => TRUE,
                        'isLazy'       => TRUE
                    );
                }
            }
        }
        else
        {
            //All software for Property tree
            
            $q_filters = array(
                'where' => "host_software.host_id = UNHEX('$host_id')"
            ); 


            $aux_p_values = Asset_host_software::get_list($conn, $q_filters);
            $p_values     = $aux_p_values[0];

            //Filters (No AJAX call)
            $filters = array('p_id' => 60, 'host_id' => $host_id);
            
            $tree['filters'] = $filters;
            
            if (is_array($p_values) && !empty($p_values))
            {
                //Key
                $key = $this->generate_random_key('property_branch');
                
                $tree['key'] = $key;
                
                //More tree options
                $tree['isFolder'] = TRUE;
                
                $p_values = array_pop($p_values);

                foreach ($p_values as $p_data)
                {
                    //Sometimes line and/or banner are empty
                    $p_value = (!empty($p_data['line'])) ? $p_data['line'] : $p_data['banner'];
                    
                    if (empty($p_value))
                    {
                        $p_value = $p_data['cpe'];
                    }

                    //Title
                    $title = $p_value;
                    
                    if (strlen($p_value) > $this->get_max_text_length())
                    {
                        $title = substr($p_value, 0, $this->get_max_text_length()).'...';
                    }
                    
                    $title = "<span class='size12n'>$title</span>";
                    
                    //Tooltip
                    $tooltip = $p_value;
               
                    //Filters (No AJAX call)
                    $filters = array(
                        'p_id' => 60,
                        'host_id' => $host_id, 
                        'p_value' => array(
                            'cpe' => $p_data['cpe']
                        )
                    );
                    
                    //Key
                    $key = $this->generate_random_key('values_by_property_branch');
                    
                    //Data 
                    
                    $t_children = array();
                    
                    
                    $sw_name        = $p_data['name'];
                    $sw_version     = $p_data['version'];
                    $sw_date        = $p_data['date'];
                    $sw_cpe         = $p_data['cpe'];
                    $sw_source_name = $p_data['source']['name'];                         
                                   
                    if (!empty($sw_name))
                    {                
                        if (strlen($sw_name) > $this->get_max_text_length())
                        {
                            $sw_name = substr($sw_name, 0, $this->get_max_text_length()).'...';
                        }
                        
                        $t_name = "<span class='size12n'>".self::utf8_encode(_('Name')).": </span><span class='ml3 size12b'>".$sw_name."</span>";
                                         
                                            
                        $t_children[] = array(
                            'title'        => $t_name,
                            'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                            'hideCheckbox' => TRUE,
                            'noLink'       => TRUE,
                            'tooltip'      => $p_data['name']
                        );
                    }
                    
                    if (!empty($sw_version))
                    {
                        $t_version = "<span class='size12n'>".self::utf8_encode(_('Version')).": </span><span class='ml3 size12b'>".$sw_version."</span>";                                 
                                            
                        $t_children[] = array(
                            'title'        => $t_version,
                            'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                            'hideCheckbox' => TRUE,
                            'noLink'       => TRUE
                        );
                    }
                         
                    $t_date = "<span class='size12n'>".self::utf8_encode(_('Date')).": </span><span class='ml3 size12b'>".$sw_date."</span>";
                                                     
                    $t_children[] = array(
                        'title'        => $t_date,
                        'icon'         => self::PIXMAPS_PATH.'/notebook.png',  
                        'hideCheckbox' => TRUE,
                        'noLink'       => TRUE
                    );
                    
                    
                    if (preg_match("/^cpe\:/i", $sw_cpe))
                    {
                        if (strlen($sw_cpe) > $this->get_max_text_length())
                        {
                            $sw_cpe = substr($sw_cpe, 0, $this->get_max_text_length()).'...';
                        }
                        
                        $t_cpe = "<span class='size12n'>".self::utf8_encode(_('Cpe')).": </span><span class='ml3 size12b'>".$sw_cpe."</span>";                                        
                                            
                        $t_children[] = array(
                            'title'        => $t_cpe,
                            'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                            'hideCheckbox' => TRUE,
                            'noLink'       => TRUE,
                            'tooltip'      => $p_data['cpe']
                        );
                    }
                    
                    
                    if (!empty($sw_source_name))
                    {
                        if (strlen($sw_source_name) > $this->get_max_text_length())
                        {
                            $sw_source_name = substr($sw_source_name, 0, $this->get_max_text_length()).'...';
                        }
                        
                        $t_source = "<span class='size12n'>".self::utf8_encode(_('Source')).": </span><span class='ml3 size12b'>".$sw_source_name."</span>";                                       
                                            
                        $t_children[] = array(
                            'title'        => $t_source,                       
                            'icon'         => self::PIXMAPS_PATH.'/notebook.png',
                            'hideCheckbox' => TRUE,
                            'noLink'       => TRUE,
                            'tooltip'      => $p_data['source']['name']
                        );
                    }                                      
                    
                    $tree['children'][] = array (
                        'title'        => $title,
                        'key'          => $key,
                        'filters'      => $filters,
                        'isFolder'     => TRUE,
                        'icon'         => self::PIXMAPS_PATH.'/'.$icon,
                        'tooltip'      => $tooltip,
                        'noLink'       => FALSE,
                        'hideCheckbox' => FALSE,
                        'children'     => $t_children
                    );             
                }
            }
        }
        
        
        if (empty($tree['children']))
        {
            $tree['children'][] = array (
                'title'        => self::utf8_encode(_('No software found')),
                'hideCheckbox' => TRUE,
                'isLink'       => FALSE
            );
        } 

        return $tree;
    }
    
    
    /**
    * This function returns host tree (Any filter can be applied)
    *
    * @param object   $conn   Database access object 
    *
    * @return array
    */ 
    private function draw_hosts($conn)
    {        
        //Retrieving filters
        $filters = $this->get_filters();    
             
        //C-class filter (Only hosts from that C-class)
        $cclass_filter = $filters['cclass'];        
               
        //Entity filter
        $e_filter = $filters['entity_id'];
        
        //Page options
        $page      = $this->get_page();
        $next_page = $page + 1;
        
        //Max results by branch
        $to    = $page * self::MAX_RESULTS;
        $from  = $to - self::MAX_RESULTS;
                
        //Tree
        $tree = array();
        
        $hosts = $this->get_hosts($conn);
        
        $total = count($hosts);
                      
        if (empty($cclass_filter))
        {
            //All C-class
            if ($from > 0 || $to < $total)
            {
                $hosts = array_slice($hosts, $from, $to, TRUE);
            }
                
            foreach ($hosts as $h_cclass => $h_data)
            {
                $filters['cclass'] = $h_cclass;
                
                //Title
                $title = $h_cclass."<span style='font-weight:normal; font-size:80%'> (".count($h_data)." ".self::utf8_encode(_('hosts')).")</span>";

                //Key
                $key = $this->generate_random_key('hosts_by_cclass');  
                
                $tree[] = array (
                    'title'        => $title,
                    'key'          => $key,
                    'filters'      => $filters,
                    'isFolder'     => TRUE,
                    'icon'         => self::PIXMAPS_PATH.'/host_add.png',                    
                    'hideCheckbox' => TRUE,
                    'isLazy'       => TRUE
                );                
            }        
            
            //Use tree pagination
            if ($total > $to)
            {
                //Filters
                unset($filters['cclass']);
                
                //Title
                $title = self::utf8_encode(_('Next')).' '.self::MAX_RESULTS.' C-class';
                                
                //Key                
                $key = $this->generate_random_key('hosts_by_cclass');       
                
                $tree[] = array (
                    'title'        => $title,
                    'key'          => $key.'_page_'.$next_page,
                    'filters'      => $filters,
                    'isFolder'     => TRUE,
                    'icon'         => self::PIXMAPS_PATH.'/host_add.png',
                    'tooltip'      => $tooltip,
                    'hideCheckbox' => TRUE,
                    'page'         => $next_page,
                    'isLazy'       => TRUE
                );
            }
        }
        else
        {        
            //Hosts by one C-class
                     
            //Getting CTX information
                        
            if (Session::is_pro() == TRUE) 
            {
                $aux_ctxs = Acl::get_entities($conn, '', '', FALSE, FALSE, TRUE);
                
                $ctxs = $aux_ctxs[0];
            }
            else
            {
                $default_ctx = Session::get_default_ctx();
                
                $aux_ctxs = Acl::get_entities($conn, $default_ctx, '', FALSE, FALSE, TRUE);
                
                $ctxs = $aux_ctxs[0];
            }
            
            
            //All C-class
            if ($from > 0 || $to < $total)
            {
                $hosts = array_slice($hosts, $from, $to, TRUE);
            }
                
            foreach ($hosts as $h_data)
            {             
                $id        = $h_data[0];
                $ctx       = $h_data[1];
                $ip        = $h_data[2];
                $hostname  = $h_data[3];                                           
                
                //Filters
                $filters['host_id'] = $id;
                                                
                //Title & Tooltip                               
                if (strlen($hostname) > $this->get_max_text_length())
                {
                    $hostname = substr($hostname, 0, $this->get_max_text_length()).'...';
                }
                
                $title = $ip." <span style='font-size:80%'>(".$hostname.")</span>"; 
                
                $tooltip = $ip." (".$h_data[3].")";
                
                if (Session::is_pro() == TRUE && empty($e_filter) && count($ctxs) > 1) 
                {
                    $ctx_name = $ctxs[$ctx]['name'];
                    
                    $title   .= " <span style='font-size:80%; font-weight:bold'>[".$ctx_name."]</span>";
                    
                    $tooltip .= ' ['.$ctx_name.']';
                }
                            
                //URL                
                $base_url = "/ossim/asset_details/index.php?id=$id";
                $url      = Menu::get_menu_url($base_url,'environment', 'assets', 'hosts');
                                
                //Host Information               
                $host_info = array(
                    'id'          => $id,
                    'ip'          => $ip,
                    'name'        => $hostname,
                    'ctx'         => array(
                        'id'   => $ctx, 
                        'name' => $ctx_name
                    ),
                    'form_heigth' => 680
                );

                //Key
                $key = $this->generate_random_key('host');  
                
                $tree[] = array (
                    'title'        => $title,
                    'key'          => $key,
                    'filters'      => $filters,
                    'host_info'    => $host_info,
                    'url'          => $url,
                    'isFolder'     => FALSE,
                    'icon'         => self::PIXMAPS_PATH.'/host.png',
                    'tooltip'      => $tooltip,
                    'hideCheckbox' => TRUE,              
                );                            
            }        
            
            //Use tree pagination
            if ($total > $to)
            {
                //Filters
                unset($filters['host_id']);
                
                //Title
                $title = self::utf8_encode(_('Next')).' '.self::MAX_RESULTS.' hosts';
                                
                //Key                
                $key = $this->generate_random_key('hosts_by_cclass');       
                
                $tree[] = array (
                    'title'        => $title,
                    'key'          => $key.'_page_'.$next_page,
                    'filters'      => $filters,
                    'isFolder'     => TRUE,
                    'icon'         => self::PIXMAPS_PATH.'/host_group.png',
                    'tooltip'      => $tooltip,
                    'hideCheckbox' => TRUE,
                    'page'         => $next_page,
                    'isLazy'       => TRUE
                );
            }
        }
        
        
        //No hosts found
        if (empty($tree))
        {       
            $tree = array (
                'title'        => self::utf8_encode(_('No hosts found')),
                'hideCheckbox' => TRUE,
                'isLink'       => FALSE
            );
        }
                
        return $tree;
    }
    
    
    /**
    * This function returns host list (ungrouped or grouped by C-class)
    *
    * @param object   $conn   Database access object
    *
    * @return array
    */ 
    private function get_hosts($conn)
    {
        //Retrieving filters
        $filters = $this->get_filters();
        
        $cclass_filter = '';
        
        //Tables and conditions 
        $tables  = '';       
    
        if (!empty($filters))
        {
            //C-class filter (Only hosts from one C-class)
            $cclass_filter = $filters['cclass'];
                   
            //Tables and conditions        
            list($tables, $q_filters) = $this->get_host_where($conn);                 
        }
             
        //Hosts by C-class
        $h_cclass = array();
            
        $hosts = Asset_host::get_list_tree($conn, $tables, $q_filters, FALSE, TRUE);
        
        //Filter by one C-class
        if (!empty($cclass_filter))
        {
            foreach($hosts as $host)
            {                              
                $ctx = $host[1];
                $ip  = $host[2];
                
                $h_cclass[$ip.';'.$ctx] = $host;
            }
        }
        else
        {
            //No filter, all C-class
            foreach($hosts as $host)
            {
                $id = $host[0];
                $ip = $host[2];

                $cclass = preg_replace("/(\d+\.)(\d+\.)(\d+)\.\d+/", "\\1\\2\\3", trim($ip));
    
                $h_cclass[$cclass][$id] = $host;        
            }
        }

        return $h_cclass;
    }
    
    
    /**
    * This function returns SQL condition to filter hosts 
    *  
    * Allowed filters
    *   - By c-class  --> cclass
    *   - By property --> p_id and p_value
    *   - By sensor   --> sensor_id
    *   - By entity   --> entity_id and e_direcion
    *
    * @param object   $conn   Database access object
    *
    * @return array
    */ 
    private function get_host_where($conn)
    {
        $tables  = array();

        $q_where = array();
        
        $q_order = '';
        
        //Retrieving filters
        $filters = $this->get_filters();
        
        
        //Filter by C-class (Only hosts from one C-class)
        $cclass_filter = $filters['cclass'];
               
        if($cclass_filter != '')
        {
            list($_cclass_a, $_cclass_b) = Util::expand_cclass($cclass_filter);    
            
            //Acotando a la clase seleccionada.
            $_cclass_a = bin2hex(inet_pton(trim($_cclass_a)));
            $_cclass_b = bin2hex(inet_pton(trim($_cclass_b)));

            $q_where[] = "hi.ip >= UNHEX('$_cclass_a') AND hi.ip <= UNHEX('$_cclass_b')";
        }        
        
        if ($filters['p_id'] != '')
        {
            //Filter by property
            switch($filters['p_id'])
            {
                //Services
                case '40':
                    $tables[]  = 'host_services hs';
                                      
                    $port     = $filters['p_value']['port']; 
                    $protocol = $filters['p_value']['protocol'];                        
                    $service  = $filters['p_value']['service'];                                        

                    $q_where[] = 'host.id = hs.host_id';
                    $q_where[] = "hs.service = '$service' AND hs.port = '$port' AND hs.protocol = '$protocol'";
                    
                    $q_order   = 'hs.host_ip ASC';    
                break;
                
                //MAC Address
                case '50':
                
                    $mac  = str_replace(':', '', $filters['p_value']['mac']);
                                            
                    $q_where[] = "host.id = hi.host_id AND hi.mac = UNHEX('$mac')";  
                    
                    $q_order   = 'hi.ip ASC';                                  
                break;

                //Software
                case '60':
                    $tables[]  = 'host_software hs';
                    
                    $cpe = $filters['p_value']['cpe'];
                    
                    $q_where[] = 'host.id = hs.host_id';
                    $q_where[] = "hs.cpe = '$cpe'";
                break;

                default:    
                    $tables[]  = 'host_properties hp';
                                        
                    $q_where[] = 'host.id = hp.host_id';
                    
                    
                    //$p_id = 0 --> All properties
                    if ($filters['p_id'] > 0)
                    {
                        $hp_value  = $filters['p_value']['hp_value'];

                        
                        $q_where[] = "hp.property_ref = '".$filters['p_id']."'";
                        $q_where[] = "hp.value LIKE \"%".escape_sql('"'.$hp_value.'"', $conn, TRUE)."%\""; 
                    }
                                     
                    $q_order   = 'hi.ip ASC';
                break;
            }
        }
        elseif (!empty($filters['sensor_id']))
        {
            //Filter by entity
            
            /*
                TO DO
            */
        }
        elseif (Session::is_pro() && !empty($filters['entity_id']))
        {
            //Filter by entity
            
            /*
                TO DO
            */
        }
        
        
        $tables   = (count($tables) > 0) ? (', '.implode(', ', $tables)) : '';

        $q_where  = implode(' AND ', $q_where);
        
        
        $filters = array(
            'where'    => $q_where,
            'order_by' => $q_order
        );
        
         
        return array($tables, $filters);
    }        
    
    
    /**
    * This function encodes the string to UTF-8
    *  
    * @param string   $string   String to encode
    *
    *
    * @return string
    */  
    private static function utf8_encode($string)
    {
        return Util::utf8_encode2($string);
    }
    
    /**
    * This function gets the tree from cache 
    *  
    * @param object   $conn   Database access object
    * @param string   $file   File where tree was saved 
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */  
    public static function get_from_cache($conn, $file)
    {
        Ossim_db::check_connection($conn);

        $user = Session::get_session_user();
        
        $file = trim($file);
        $file = 'tree_'. md5("$file-$user");

        return Cache_file::get_asset_data($file);
    }
    
    
    /**
    * This function saves the tree in cache 
    *
    * @param string   $file   File where tree will be saved
    * @param string   $data   JSON Tree
    *
    * @return boolean
    */  
    public static function save_in_cache($file, $data)
    {
        $res  = FALSE;

        @json_decode($data);

        if (json_last_error() == JSON_ERROR_NONE)
        { 
            $user  = Session::get_session_user();

            $file = trim($file);
            $file = 'tree_'. md5("$file-$user");
                        
            $res = Cache_file::save_file($file, $data);
        }

        return $res;
    }
}

/* End of file av_tree.inc */
/* Location: ../include/classes/av_tree.inc */ 