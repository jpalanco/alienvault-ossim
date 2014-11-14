<?php
/**
* avc_tree.inc
*
* File avc_tree.inc is used to:
*   - To manage Alienvault Components using Alienvault Center
*
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
* @package    ossim-framework\Alienvaut_Center
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* 
*   - get_tree()
*   - get_avc_list($conn)
*   - get_branch($page)
*/

/**
* Avc_tree Class
*
* Class for managing Alienvault Components Tree
*
* @package    ossim-framework\Alienvaut_Center
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Avc_tree 
{    
    private static $order_types = array('hostname' => 'hostname', 'profile' => 'profile');
	
	private $avc_list;
	
	private $current_order;
	
	private $max_leafs;
	
		    
    public function __construct($conn, $order_type = 'hostname', $max_leafs_per_branch = 50)
    {
        $this->set_current_order($order_type);
		
		$this->avc_list  = $this->get_avc_list($conn);
		
		$this->max_leafs = $max_leafs_per_branch;
    }
	
	
	public function get_current_order()
	{
		return $this->current_order;
	}
	
	
	public function is_valid_order($order_type)
	{
		return(empty(self::$order_types[$order_type])) ? FALSE : TRUE;
    }
	
	
	public function set_current_order($order_type)
	{
		$this->current_order = (empty(self::$order_types[$order_type])) ? 'hostname' : $order_type;
    }

    
    public function get_avc_list($conn)
    {
        $res['status']    = TRUE;
        $res['data']      = NULL;
        $res['error_msg'] = NULL;

        $system_list = Av_center::get_avc_list($conn);

        if ($system_list['status'] == 'success')
        {
            if ($this->current_order == 'hostname')
            {
                $res['data'] = $system_list['data'];
            }
            elseif ($this->current_order == 'profile')
            {
                $res['data']['Server']    = array();
                $res['data']['Sensor']    = array();
                $res['data']['Framework'] = array();
                $res['data']['Database']  = array();

                foreach ($system_list['data'] as $s_id => $s_data)
                {
                    if (preg_match('/Server/i', $s_data['profile']))
                    {
                        $res['data']['Server'][$s_id] = $s_data;
                    }

                    if (preg_match('/Sensor/i', $s_data['profile']))
                    {
                        $res['data']['Sensor'][$s_id] = $s_data;
                    }

                    if (preg_match('/Framework/i', $s_data['profile']))
                    {
                        $res['data']['Framework'][$s_id] = $s_data;
                    }

                    if (preg_match('/Database/i', $s_data['profile']))
                    {
                        $res['data']['Database'][$s_id] = $s_data;
                    }
                }
            }
        }
        else
        {
            $res['status']    = FALSE;
            $res['data']      = NULL;
            $res['error_msg'] = (!empty($system_list['data'])) ? $system_list['data'] : _('Error retrieving Alienvault Components');
        }

        return $res;
    }


	
	public function show_load_error()
	{
		$t_av_componets = utf8_encode(_('Alienvault Components'));
		$t_load_error   = utf8_encode(_('Load error'));
		
		return  '{
					"title" : "<span>'.$t_av_componets.'</span>", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/any.png", "addClass" : "size12", "key" : "", "isFolder" : true, "expand" : TRUE, "noLink" : true, "profiles" : "", 
					"children" : [{"title" : "<span>'.$t_load_error.'</span>", "icon" : "", "addClass" : "bold_red dynatree-statusnode-error",  "key" : "error",  "noLink" : true}] 
				}';
	
	}
	
	
	public function show_empty_tree()
	{
		$t_av_componets           = utf8_encode(_('Alienvault Components'));
		$t_av_componets_no_found  = utf8_encode(_('No Alienvault Components Found')); 
		
		return '{
				"title" : "<span>'.$t_av_componets.'</span>", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/any.png", "addClass" : "size12", "key" : "", "isFolder" : true, "expand" : true, "noLink" : true, "profiles" : "", 
					"children" : [{"title" : "<span>'.$t_av_componets_no_found.'</span>", "icon" : "", "key" : "",  "noLink" : true}] 
				}';  
	
	}
	
	
	public function get_tree()
	{        
        $cont = 0;
        		
		//Labels
		$t_next                   = utf8_encode(_('Next'));
		$t_av_componets           = utf8_encode(_('Alienvault Components'));
		$t_av_componets_no_found  = utf8_encode(_('No Alienvault Components Found'));
			
        
        $json['status']    = $this->avc_list['status'];
        $json['error_msg'] = $this->avc_list['error_msg'];
        $json['data']      = '';
        
		        
		//JSON error
        if ($json['status'] == FALSE)
		{
			$json['data'] = $this->show_load_error();
			    
        	return $json;
        }
		
		//No Alienvault componentes found
		if (!is_array($this->avc_list['data']))
		{
			$json['data'] = $this->show_empty_tree();
			
        	return $json;
		}		
				
		//Alienvault Components
		
		$tree  = '{"title" : "<span>'.$t_av_componets.'</span>", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/any.png", "addClass" : "size12", "key" : "", "isFolder" : true, "expand" : true, "noLink" : true, "profiles" : "", "children" : ['; 
		if ($this->current_order == 'hostname') //Order by hostname
		{
			foreach ($this->avc_list['data'] as $system_id => $data)
			{
				$from = 0;
				$to   = $this->max_leafs;
				
				if($cont >= $from && $cont < $to) 
				{
					$tree .= ($cont > 0) ? ", " : "";
					
					$hostname = $data['name'];
					$host_ip  = $data['admin_ip'];
					
					$hname    = $hostname;    
					
					$title    = Util::htmlentities($hname)." <span class='t_label'>[".Util::htmlentities($host_ip)."]</span>";
					$tooltip  = $hostname." [".$host_ip."]";
					
					$tree   .= '{"title" : "'.$title.'", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png",  "key" : "'.$system_id.'", "tooltip" : "'.$tooltip.'", "profiles" : "'.$data['profile'].'"}'; 
					$cont++;
				}
				else
				{
					break;
			    }
			}
			
			if  ($cont >= $to) 
			{
				$tree .= ',{"title" : "'.$t_next.' '.$this->max_leafs.' '.$t_av_componets.'", "key": "hostname_next_page", "addClass": "t_next_branch", "page": "2", "isFolder" : true, "isLazy" : true, "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png"}';
			}
		}
		elseif ($this->current_order == 'profile') //Order by profile (default)
		{			
			$profiles = array('Server'    => array(utf8_encode(_('Server'))        , DYNATREE_PIXMAPS_DIR.'/theme/host_os.png'), 
							  'Sensor'    => array(utf8_encode(_('Sensor'))        , DYNATREE_PIXMAPS_DIR.'/theme/server.png'), 
							  'Framework' => array(utf8_encode(_('Web Interface')) , DYNATREE_PIXMAPS_DIR.'/theme/framework.png'), 
							  'Database'  => array(utf8_encode(_('Database'))      , DYNATREE_PIXMAPS_DIR.'/theme/storage.png'));
			
	
			foreach ($profiles as $p_id => $p_data)
			{
				$tree .= '{"title" : "<span>'.$p_data[0].'</span>", "icon" : "'.$p_data[1].'", "addClass" : "size12", "key" : "", "isFolder" : true, "expand" : false, "noLink" : true, "profiles" : "", "children" : ['; 
				
				if (count($this->avc_list['data'][$p_id]) == 0)
				{
					$tree .= '{"title" : "'.$t_av_componets_no_found.'", "key" : "", "noLink" : true}';					
				}
				else
				{
					$cont = 0;
					$from = 0;
					$to   = $this->max_leafs;
					
					foreach ($this->avc_list['data'][$p_id] as $system_id => $data)
					{
						if($cont >= $from && $cont < $to) 
						{
							$tree .= ($cont > 0) ? ', ' : '';
					
							$hostname = $data['name'];
							$host_ip  = $data['admin_ip'];
							
							$hname    = $hostname;    
							
							$title    = Util::htmlentities($hname)." <span class='t_label'>[".Util::htmlentities($host_ip)."]</span>";
							$tooltip  = $hostname." [".$host_ip."]";
							
							$tree    .= '{"title" : "'.$title.'", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png",  "key" : "'.$system_id.'", "tooltip" : "'.$tooltip.'", "profiles" : "'.$data['profile'].'"}'; 
							$cont++;
						}
						else
						{
							break;
						}
					}
					
					if  ($cont >= $to) 
					{
						$tree .= ',{"title" : "'.$t_next.' '.$this->max_leafs.' '.$profiles[$p_id][0].'(s)", "key": "'.$p_id.'_next_page", "addClass": "t_next_branch", "page": "2", "isFolder" : true, "isLazy" : true, "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png"}';
					}
				}
				
				$tree .= "] },";  
			}
			
			$tree = preg_replace("/,$/", '', $tree);
		
		}
	
	
		$tree .= "] }";   

		$json['data'] = $tree;		
	
		return $json;
	}
	
	
	public function get_branch($key, $page)
	{
		//Labels
		$t_next          = utf8_encode(_('Next'));
		$t_av_componets  = utf8_encode(_('Alienvault Components'));
		
				
		if ($this->current_order == 'hostname')
		{
			if (intval($page) < 2 || $key != 'hostname_next_page')
			{
				$t_load_error = utf8_encode(_('Load error!'));
				return '{"title" : "<span>'.$t_load_error.'</span>", "icon" : "", "addClass" : "bold_red dynatree-statusnode-error",  "key" : "error",  "noLink" : true}';
        	}
			
			$offset = (($page-1)*$this->max_leafs);
			$length = ($offset + $this->max_leafs < count($this->avc_list['data'])) ? $offset + $this->max_leafs : count($this->avc_list['data']);
			
			$aux_avc_list = array_slice($this->avc_list['data'], $offset, $length);
			
			$cont = 0;
			
			foreach ($aux_avc_list as $system_id => $data)
			{
				$from = 0;
				$to   = $this->max_leafs;
				
				if($cont >= $from && $cont < $to) 
				{
					$tree .= ($cont > 0) ? ", " : "";
					
					$hostname = $data['name'];
					$host_ip  = $data['admin_ip'];
					
					$hname    = $hostname;    
					
					$title    = Util::htmlentities($hname)." <span class='t_label'>[".Util::htmlentities($host_ip)."]</span>";
					$tooltip  = $hostname." [".$host_ip."]";
					
					$tree    .= '{"title" : "'.$title.'", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png",  "key" : "'.$system_id.'", "tooltip" : "'.$tooltip.'", "profiles" : "'.$data['profile'].'"}'; 
					$cont++;
				}
				else
				{
					break;
			    }
			}
			
			if  ($offset + $this->max_leafs < count($this->avc_list['data']))
			{
				$tree .= ',{"title" : "'.$t_next.' '.$this->max_leafs.' '.$t_av_componets.'", "key": "next_page", "addClass": "t_next_branch", "page": "'.($page+1).'", "isFolder" : true, "isLazy" : true, "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png"}';
			}
		}
		elseif($this->current_order == 'profile')
		{
			$keys   = array('Server_next_page'    => array('Server',    _('Server')), 
					        'Sensor_next_page'    => array('Sensor',    _('Sensor')), 
						    'Framework_next_page' => array('Framework', _('Web Interface')), 
						    'Database_next_page'  => array('Database',  _('Database')));						
			
		    if (intval($page) < 2 || empty($keys[$key]))
			{
				$t_load_error             = utf8_encode(_("Load error!"));
				return '{"title" : "<span>'.$t_load_error.'</span>", "icon" : "", "addClass" : "bold_red dynatree-statusnode-error",  "key" : "error",  "noLink" : true}';
        	}
			
			$offset = (($page-1)*$this->max_leafs);
			
			$id_p   = $keys[$key][0];
									
			$length = ($offset + $this->max_leafs < count($this->avc_list['data'][$id_p])) ? $offset + $this->max_leafs : count($this->avc_list['data'][$id_p]);
			
			$aux_avc_list = array_slice($this->avc_list['data'][$id_p], $offset, $length);
			
						
			$cont = 0;	
			foreach ($aux_avc_list as $system_id => $data)
			{
				$from = 0;
				$to   = $this->max_leafs;
				
				if($cont >= $from && $cont < $to) 
				{
					$tree .= ($cont > 0) ? ", " : "";
					
					$hostname = $data['name'];
					$host_ip  = $data['admin_ip'];
					
					$hname    = $hostname;    
					
					$title    = Util::htmlentities($hname)." <span class='t_label'>[".Util::htmlentities($host_ip)."]</span>";
					$tooltip  = $hostname." [".$host_ip."]";
					
					$tree    .= '{"title" : "'.$title.'", "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png",  "key" : "'.$system_id.'", "tooltip" : "'.$tooltip.'", "profiles" : "'.$data['profile'].'"}'; 
					$cont++;
				}
				else
				{
					break;
			    }
			}
			
			if  ($offset + $this->max_leafs < count($this->avc_list['data'][$id_p]))
			{
				$tree .= ',{"title" : "'.$t_next.' '.$this->max_leafs.' '.$this->$keys[$key][1].'(s)", "key": "'.$id_p.'_next_page", "addClass": "t_next_branch", "page": "2", "isFolder" : true, "isLazy" : true, "icon" : "'.DYNATREE_PIXMAPS_DIR.'/theme/host.png"}';
			}
		}
		
		return $tree;
	}
}

/* End of file avc_tree.inc */
/* Location: ../include/classes/avc_tree.inc */