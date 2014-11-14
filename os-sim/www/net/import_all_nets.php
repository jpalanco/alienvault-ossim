<?php
/**
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
*/



/************************************************************************************/
/************************************************************************************/
/***                                                                              ***/
/***  IF YOU MODIFY THIS FILE, PLEASE CHECK IT WORKS RIGHT IN THE WELCOME WIZARD  ***/
/***                                                                              ***/
/************************************************************************************/
/************************************************************************************/



/**
* Function list:
* - function print_form($msg_errors = '')
* - function clean_iic($string)
* - function import_assets_from_csv($filename, $iic, $ctx, $import_type)
*/

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyNetworks');


//Functions 
function print_form($import_type)
{         
    $config = array(
        'networks' => array(
            'contexts' => array(
                'show_tree'     => Session::show_entities(),
                'default_value' => Session::get_default_ctx()                
            ),
            'chk_iic' => array(
                'show'    => TRUE,
                'checked' => FALSE                
            ),
            'help' => array(                
                'Version 4.x.x' => array(
                    'format'  => _('"Netname"*;"CIDRs(CIDR1,CIDR2,...)"*;"Description";"Asset value"*;"Net ID"'),
                    'example' => '"Net_1";"192.168.10.0/24,192.168.9.0/24";"'._('Short description').'";"2";"479D45C0BBF22B4458BD2F8EE09ECAC2"'
                ),
                'Version 3.x.x' => array(
                    'format'  => _('"Netname"*;"CIDRs(CIDR1,CIDR2,...)"*;"Description";"Asset value";"Sensors(Sensor1,Sensor2,...)"*'),
                    'example' => '"Net_1";"192.168.10.0/24,192.168.9.0/24";"'._('Short description').'";"2";"192.168.10.2,192.168.10.3"'
                )               
            )         
        ),
        'welcome_wizard_nets' => array(
            'contexts' => array(
                'show_tree'     => FALSE,
                'default_value' => Session::get_default_ctx()                
            ),
            'chk_iic' => array(
                'show'    => FALSE,
                'checked' => TRUE
            ),
            'help' => array(               
                'Version 4.x.x' => array(
                    'format'  => _('"Netname"*;"CIDRs(CIDR1,CIDR2,...)"*;"Description"'),
                    'example' => '"Net_1";"192.168.10.0/24,192.168.9.0/24";"'._('Short description').'"'
                )              
            )         
        )
    );
		
	
	if (!array_key_exists($import_type, $config))
	{
        echo ossim_error(_('Error! Import Type not found'));	
	}
	else
	{
    	?>
    	<div id='av_info'></div>
    	
    	<form name='form_csv' id='form_csv' method='POST' action='import_all_nets_ajax.php' enctype='multipart/form-data' target='iframe_upload'>        
			
			<input type="hidden" name="ctx" id="ctx" value="<?php echo $config[$import_type]['contexts']['default_value']?>"/>
			<input type="hidden" name="import_type" id="import_type" value="<?php echo $import_type?>"/>
            
            <table id='form_container'>
                                       
    			<?php
    			//Context    			 
    			if ($config[$import_type]['contexts']['show_tree'] == TRUE) 
    			{ 
    				?>
    				<tr class='left'>
    				    <td class="td_title">
						    <span><?php echo _('Select the Entity for the nets');?>:</span>
						</td>
    				</tr>        
    				
    				<tr>
    					<td class='td_content'>    											
    						<table id="t_tree">    							
    							<tr>
        							<td class='noborder'>
        							    <div id="tree"></div>
        							</td>
    							</tr>
    							
    							<tr>
            						<td id='td_es'>
            						    <span class='bold'><?php echo _('Entity selected')?>:</span> 
            						    <span id="entity_selected"> - </span>
            						</td>
            					</tr>  							
    						</table>    					
    					</td>    			
    				</tr>   				
    				<?php 
    			}			
			
    			$top_class = ($import_type == 'welcome_wizard_nets') ? 'td_top' : '';
    			//Input File
    			?>			
                <tr>
                    <td class='td_title <?php echo $top_class ?>'>
                        <span><?php echo _('Choose a CSV file')?>:</span>    
                    </td>
                </tr>
                
                <tr>    
                    <td class='td_content'>                    
                        <input name='file_csv' id='file_csv' type='file' size='38'/>                        
                        <?php			
                        if ($config[$import_type]['chk_iic']['show'] == TRUE) 
                        {
                            $checked_iic = ($config[$import_type]['chk_iic']['checked'] == TRUE) ? 'checked="checked"' : ''
                            
                            ?>
                            <span class='ignore_span'>
                                <input type='checkbox' name='iic' id='iic' <?php echo $checked_iic?> value='1'/>
                                <label for='iic' style='margin-left: 2px;'><?php echo _('Ignore invalid characters')?></label>
                            </span>
                            <?php
                        }
                        else
                        {
                        	$chk_iic_value = ($config[$import_type]['chk_iic']['checked'] == TRUE) ? '1' : '0';
                        	?>                
                            <input type='hidden' name='iic' id='iic' value="<?php echo $chk_iic_value;?>"/>                
                            <?php
                        }	 
                        ?>               
                    </td>
                </tr>
			
			    <tr>
        				<td class='td_content'>
        					<div id='c_send'>
        						<input type='button' name='send' id='send' value='<?php echo _('Import')?>'/>
        					</div>
        				</td>
        			</tr>
			
                <tr>
                    <td class='td_title'>
                        <span><?php echo _('Formats allowed')?>:</span>
                    </td>
                </tr>
                
                <tr>    
                    <td class='td_content'>
                        <table id='t_format'>   
                            <?php
                            //Help                                     
                            foreach($config[$import_type]['help'] as $version => $help_data)
                            {
                                ?>
                                <tr>
                				    <td class='td_version'>
                				        <?php echo $version?>:
                				    </td>      				    
                                </tr>
                                
                                <tr>
                				    <td class='td_format'>
                				        <?php echo $help_data['format']?>
                				    </td>      				    
                                </tr>
                                
                                <tr>
                				    <td class='td_example'>
                				       <?php echo $help_data['example']?>
                				    </td>      				    
                                </tr>
                                <?php	
                            }			
                            ?>
                        </table>
                    </td>
                </tr>           
			
    			<tr>
    				<td class='td_title'>
    				    <span><?php echo _('Notes')?>:</span>
    				</td> 	
        		</tr>		
                
                <tr>
                    <td class='td_content'>                           
                        <ul id='note_list'>
                            <li><?php echo _('Characters allowed: A-Z, a-z, 0-9, ., :, _ and -');?></li>
                            <li><?php echo _('Values marked with (*) are mandatory');?></li>
                        </ul>                        
                    </td>
    			</tr>
    			
    		</table>
        
            <div id='c_resume'></div>
        
            <iframe name="iframe_upload" id="iframe_upload" style="display:none;"></iframe>
        
        </form>
    	<?php
	}	
}

function clean_iic($string)
{
	$str  = strtr($string, "ٹŒژڑœ‍ں¥µہءآأؤإئابةتثجحخدذرزسشصضطظعغـفكàلâمنهوçèéêëىيîïًٌٍَôُِّùْûü‎ے","SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
	$size = strlen($str);
	
	$val_str = OSS_NET_NAME;
	$res     = NULL;
	
	for($i = 0; $i < $size; $i++)
	{
		if (!preg_match("/[^$val_str]/", $str[$i]))
		{
			$res .= $str[$i];
		}
	}
		
	return $res;
}


function import_assets_from_csv($filename, $iic, $ctx, $import_type)
{			
	//Process status      
    $summary = array(
        'general' => array(
            'status'      => '',
            'data'        => '',
            'statistics'  => array(
                'total'    => 0,
                'warnings' => 0,
                'errors'   => 0,
                'saved'    => 0
            )        
         ),
        'by_nets' => array()       
    );
	
	$db   = new ossim_db();
	$conn = $db->connect();

	$str_data = file_get_contents($filename);
			
	if ($str_data === FALSE)
	{
		$summary['general']['status'] = 'error';
		$summary['general']['data']['errors'] = _('Failed to read data from CSV file');
		$summary['general']['statistics']['errors'] = 1;
				
		return $summary;
	}
	
	
    $array_data = preg_split('/\n|\r/', $str_data);
    
    foreach ($array_data as $k => $v)
    {
        if (trim($v) != '')
        {
        	$data[] = explode('";"', trim($v));
        }	
    }
    
    set_time_limit(360);
	
    /*********************************************************************************************************************
     * From net section:
     *  - Version 4.x.x: "Netname"*;"CIDRs(CIDR1,CIDR2,...)"*;"Description";"Asset value"*;"Net ID"
     *  - Version 3.x.x: "Netname"*;"CIDRs(CIDR1,CIDR2,...)"*;"Description";"Asset value";"Sensors(Sensor1,Sensor2,...)"*
     *
     * From welcome wizard:
     *  - Version 4.x.x: "Netname"*;"CIDRs(CIDR1,CIDR2,...)"*;"Description"   
     *
     *********************************************************************************************************************/

	//Check file size
	if (count($data) <= 0 || (count($data) == 1 && preg_match('/Netname/',$data[0][0])))
	{				
		$summary['general']['status'] = 'error';
		$summary['general']['data']   = _('CSV file is empty');
		$summary['general']['statistics']['errors'] = 1;
				
		return $summary;
	}
		
		
	//Check importation type and headers
	$csv_headers = array();
	
	if ($import_type == 'networks')
	{
    	if (preg_match('/Net ID/',$data[0][4]) || preg_match('/Sensors/',$data[0][4]))
    	{
    		$csv_headers = array_shift($data); 		
    	}
    	else
    	{ 		
    		$summary['general']['status'] = 'error';
    		$summary['general']['data']   = _('Headers not found');
    		$summary['general']['statistics']['errors'] = 1;
    				
    		return $summary; 	
    	}    	
	}
	
	
	//Setting total nets to import
    $summary['general']['statistics']['total'] = count($data);
		
		
	//Allowed sensors		
	$filters = array(
	   'where' => "acl_sensors.entity_id = UNHEX('$ctx')"
	);
	
	$a_sensors  = Av_sensor::get_basic_list($conn, $filters);
	$sensor_ids = array_keys($a_sensors);
	
	
	if (count($sensor_ids) == 0)
	{				
		$summary['general']['status'] = 'error';
		
		$s_error_msg = (Session::is_pro()) ? _('There is no sensor for this context') : _('There is no sensor for this net');
		$summary['general']['data'] = $s_error_msg;
		$summary['general']['statistics']['errors'] = 1;
				
		return $summary;
	}

    Util::disable_perm_triggers($conn, TRUE);
					
	foreach ($data as $k => $v)
	{						
		//Clean previous errors
		ossim_clean_error();
				
		$num_line = $k + 1;
		
		//Set default status
		$summary['by_nets'][$num_line]['status'] = 'error';	
		
		//Check file format
		$cnd_1 = ($import_type == 'networks' && count($v) < 5);
		$cnd_2 = ($import_type == 'welcome_wizard_nets' && count($v) < 3);

		if ($cnd_1 || $cnd_2)
		{
    		$summary['by_nets'][$num_line]['errors']['Format'] = _('Number of fields is incorrect');
			$summary['general']['statistics']['errors']++;
			
			continue;
		}
		
		
		//Clean values
		$param = array();
		
		foreach ($v as $field)
		{
			$parameter = trim($field);
			$pattern = '/^\"|\"$|^\'|\'$/';
			$param[] = preg_replace($pattern, '', $parameter);
		}	
		
		//Values
		$is_in_db    = FALSE;
		$net_id      = '';
				
		$name        = $param[0];
		$cidrs       = preg_replace("/[\n\r\t]+/", '', $param[1]);	
		$descr       = $param[2];	
		$asset_value = ($param[3] == '') ? 2 : intval($param[3]);
		$sensors     = $sensor_ids;
	
		
		//Permissions
		$can_i_create_assets = Session::can_i_create_assets();
		$can_i_modify_ips    = TRUE; 
		
				
		//CIDRs		
		if (!ossim_valid($cidrs, OSS_IP_CIDR, 'illegal:' . _('CIDR')))
		{						
			$summary['by_nets'][$num_line]['errors']['CIDRs'] = ossim_get_error_clean();								
			$summary['general']['statistics']['errors']++;
			
            continue;
		}	
		
		
		//Check Net ID �Is there a net registered in the System?			
		$net_ids = Asset_net::get_id_by_ips($conn, $cidrs, $ctx);
        $net_id  = key($net_ids);
        
        if (!empty($net_id))
        {
            $is_in_db = TRUE;
        }
        else
        {
            $net_id = Util::uuid();
        }       
		
		// Special case: Forced Net ID [Version 4.x.x]
		if ($import_type == 'networks' && preg_match('/Net ID/', $csv_headers[4]))
		{    		
			$csv_net_id = strtoupper($param[4]);
			
			if ($is_in_db == TRUE && $csv_net_id != $net_id)
			{
    			$id_error_msg = _('Net is already registered in the System with another Net ID');
		    			
    			$summary['by_nets'][$num_line]['errors']['Net'] = $id_error_msg;
                $summary['general']['statistics']['errors']++;
                
                continue;        			
			} 
		}
				
			
		//Netname				
		if (!empty($iic))
		{
			$name = clean_iic($name);
		}
		
		if (!ossim_valid($name, OSS_NOECHARS, OSS_NET_NAME, 'illegal:' . _('Netname')))
		{
			ossim_clean_error();
			
			$name = clean_iic($name);
			$name = clean_echars($name);			
					
			$warning_msg = _('Netname has invalid characters').'<br/>'._('Netname will be replaced by').": <strong>$name</strong>";
			
			$summary['by_nets'][$num_line]['warnings']['Netname'] = $warning_msg;
			$summary['by_nets'][$num_line]['status'] = 'warning';
			$summary['general']['statistics']['warnings']++;	
		
		
			if (!ossim_valid($name, OSS_NOECHARS, OSS_NET_NAME, 'illegal:' . _('Netname')))
			{
				unset($summary['by_nets'][$num_line]['warnings']);
				$summary['general']['statistics']['warnings']--;
				
				$summary['by_nets'][$num_line]['status'] = 'error';				
				$summary['by_nets'][$num_line]['errors']['Netname'] = ossim_get_error_clean();				
				$summary['general']['statistics']['errors']++;
				                
                continue;
            }
		}
	
	
		//Description						
		if (!ossim_valid($descr, OSS_NULLABLE, OSS_AT, OSS_TEXT, '\t', 'illegal:' . _('Description')))
		{						
			$summary['by_nets'][$num_line]['errors']['Description'] = ossim_get_error_clean();			
			$summary['general']['statistics']['errors']++;
            
            continue;
		}
		else
		{
			if (mb_detect_encoding($descr.' ','UTF-8,ISO-8859-1') == 'UTF-8')
			{
			     $descr = mb_convert_encoding($descr,'HTML-ENTITIES', 'UTF-8');
			} 
		}	
		
		
		//Sensor
		if ($is_in_db == FALSE)
		{ 	
		    //Only update net sensors with unregistered nets
    		
    		if ($import_type == 'networks' && preg_match('/Sensors/', $csv_headers[4]))
    		{
        		//Special case: Sensors in CSV file //[Version 3.x.x]
        		$sensors  = array();
        		
        		$_sensors = explode(',', $param[4]);
			
                if (is_array($_sensors) && !empty($_sensors))
                {
                    $_sensors = array_flip($_sensors);
                
                    if (is_array($a_sensors) && !empty($a_sensors))
                    {
                        foreach ($a_sensors as $s_id => $s_data)
                        {	
                            if (array_key_exists($s_data['ip'], $_sensors))
                            {
                                $sensors[] = $s_id;
                            }
                        }
                    }
                }
                
                if (!is_array($sensors) || empty($sensors))
        		{    			
        			$s_error_msg = (Session::is_pro()) ? _('There is no sensors for this context') : _('There is no sensors for this IP');
        		    			
        			$summary['by_nets'][$num_line]['errors']['Sensors'] = $s_error_msg;
                    $summary['general']['statistics']['errors']++;
                    
                    continue;
        		}   
    		}    	   		
		}
		
		
		/***********************************************************
		 ********** Only for importation from net section **********
		 ***********************************************************/
		
		if ($import_type == 'networks')
		{ 
		    //Asset    		    					
			if (!ossim_valid($asset_value, OSS_DIGIT, 'illegal:' . _('Asset value')))
			{ 				
				$summary['by_nets'][$num_line]['errors']['Asset value'] = ossim_get_error_clean();
				$summary['general']['statistics']['errors']++;
				
                continue;
			}    		
		}			
		
		//Insert/Update net in database
		
        if (count($summary['by_nets'][$num_line]['errors']) == 0)
        {
            try
            {
                $net = new Asset_net($net_id);
                
                if($is_in_db == TRUE)
                {
                    $net->load_from_db($conn, $net_id);                
                    
                    $can_i_modify_ips = Asset_net::can_i_modify_ips($conn, $net_id);              
                }
                else
                {
                    if ($can_i_create_assets == FALSE)
                    {                     
                        $n_error_msg = _('Net').' '.$name.' '._("not allowed. You don't have permissions to import this net");
                        
                        $summary['by_nets'][$num_line]['errors']['Net'] = $n_error_msg;
                        $summary['general']['statistics']['errors']++;
                        
                        continue;
                    }
                }
                
                
                //Check CIDRs
                                
                if ($can_i_modify_ips == TRUE)
                {                                        
                    $aux_cidr = explode(',', $cidrs);
    
    				foreach ($aux_cidr as $cidr)
    				{                    			
                		$net_ids = Asset_net::get_id_by_ips($conn, $cidr, $ctx);
                	                                       
                		unset($net_ids[$net_id]);
                		                		                    
                		if (!empty($net_ids))
                        {             			                			
                			$c_error_msg = _('CIDR').' '.$cidrs.' '._("not allowed. CIDR $cidr already exists for this entity");
                			
                			$summary['by_nets'][$num_line]['errors']['CIDRs'] = $c_error_msg;
                			$summary['general']['statistics']['errors']++;
                			                			
                			break;
                		}		
                		else
                		{
                    		if (Session::get_net_where() != '')
                        	{
                        		if (!Asset_net::is_cidr_in_my_nets($conn, $cidr, $ctx))
                        		{                    			                        			
                        			$c_error_msg = _('CIDR').' '.$cidrs.' '._("not allowed. CIDR $cidr out of range. Check your asset filter");
                			
                        			$summary['by_nets'][$num_line]['errors']['CIDRs'] = $c_error_msg;
                        			$summary['general']['statistics']['errors']++;
                        			                        			                        			
                        			break;
                        		}
                        	}
                		}
                    }                                                
                }
                else
                {
                    $c_error_msg = _('Net').' '.$name.': '._("CIDRs not allowed. CIDRs wasn't be modified"); 
                    
                    $summary['by_nets'][$num_line]['status'] = 'warning';
                    $summary['general']['warnings']['errors']++;
                    
                    $summary['by_nets'][$num_line]['warnings']['CIDRs'] = $c_error_msg;                
                }
                             
                
                //Setting new values
                
                if (count($summary['by_nets'][$num_line]['errors']) == 0)
                {
                    $net->set_ctx($ctx);
                    $net->set_name($name);
                    $net->set_descr($descr);
                    
                    
                    if($is_in_db == FALSE)
                    {
                        if ($can_i_modify_ips == TRUE)
                        {
                            $net->set_ips($cidrs);
                        } 
                        
                        $net->set_sensors($sensors); 
                    }
                          
                    $net->set_asset_value($asset_value);                                                           
                    $net->save_in_db($conn, FALSE);
                    
                    $summary['general']['statistics']['saved']++;
                    $summary['by_nets'][$num_line]['data'] = ($is_in_db == TRUE) ? _('Net updated') : _('New new inserted'); 
                                                            
                    //Keep warnings
                    if ($summary['by_nets'][$num_line]['status'] != 'warning')
                    {
                        $summary['by_nets'][$num_line]['status'] = 'success';                        
                    }                             
                }                                   
            }
            catch(Exception $e)
            {             
                $summary['by_nets'][$num_line]['errors']['Database error'] = $e->getMessage();
                $summary['general']['statistics']['errors']++;
            }           
        } 
	}


	if ($summary['general']['statistics']['saved'] > 0)
	{
    	if ($summary['general']['statistics']['errors'] == 0)
    	{
        	$summary['general']['status'] = 'success';
        	$summary['general']['data']   = _('All nets have been imported successfully');
    	}
    	else
    	{
        	$summary['general']['status'] = 'warning';
        	$summary['general']['data']   = _('Some nets could not be imported successfully');        	
    	}      	
    	
    	Util::disable_perm_triggers($conn, FALSE);
    	
    	try
        {
            Asset_net::report_changes($conn, 'nets');
        }
        catch(Exception $e)
        {
            error_log($e->getMessage(), 0);                    
        }
	}
	else
	{	       
    	$summary['general']['statistics']['errors'] = count($data);    	   	
    	
    	//CSV file is not empty, but all lines are wrong    	
    	if (empty($summary['general']['status']))
    	{
        	$summary['general']['status'] = 'error';
        	$summary['general']['data']   = _('Nets could not be imported');
    	}
	}
	
	$db->close();	
	
	return $summary; 
}



/****************************************************
 ******************** Import data *******************
 ****************************************************/


$import_type = REQUEST('import_type');
$import_type = (empty($import_type)) ? 'networks' : $import_type;

if ($_POST['import_assets'] == 1)
{
    $data['status'] = 'error';
	$data['data']   = NULL;
    
    $file_csv = $_SESSION['file_csv'];
    unset($_SESSION['file_csv']);
    
    $iic = POST('iic');
    $ctx = POST('ctx');
       
    if (Session::is_pro())
	{
		if (!valid_hex32($ctx) || Acl::entityAllowed($ctx) < 1)
		{
			$data['data'] = (empty($ctx)) ? _('You must select an entity') : _('Entity not allowed');
            
            echo json_encode($data);
            exit();
        }
	}
	else
	{
		$ctx = Session::get_default_ctx();
	}
	     
    if (!empty($file_csv))
    {            
        $data['status'] = 'OK';
        $data['data']   = import_assets_from_csv($file_csv, $_POST['iic'], $ctx, $import_type);   
                  
                                   
        //@unlink($file_csv);    
    }
    else
    {
         $data['data'] = _('Failed to read data from CSV file. File is missing');
    }
    	    
    echo json_encode($data);
    exit();
}	

$container_class = ($import_type == 'welcome_wizard_nets') ? 'import_container_wide' : 'import_container';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <title><?php echo _('Import Nets from CSV')?></title>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/av_import_assets.js.php"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>	
	<?php 
	if (Session::show_entities()) 
	{ 
		?>
		<link rel="stylesheet" type="text/css" href="../style/tree.css" />
		<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
		<?php 
	} 
	?>    
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
    
	
	<style type="text/css">
		
		.import_container
		{ 
			width: 95%;
			text-align: center; 
			margin: 0 auto 20px auto; 
			position: relative;
		}
		.import_container_wide
		{
			width: 100%;
			text-align: center; 
			margin: 0 auto 20px auto; 
			position: relative;
		}
		
					
		#form_container
		{
			width: 100%;
			border-collapse: collapse;
			margin: 0px auto;
			background: none;
			border: none;
			text-align: center;
		}

		#t_tree
		{
    		margin-top: 0px;
		margin-bottom: 10px;
    		min-width: 300px;
    		background: none;
    		border: none;
    		text-align: left; 
    		border: 1px dotted black;   		
		}
		
		#t_tree td
		{
		    vertical-align: middle !important;
		    white-space: nowrap;		    
		}				
        				
		#tree
		{
			width: 100%;
			text-align: left !important;
			padding: 0px;
			margin: 0px;
		}
		
		#tree .dynatree-container
		{
    		margin: 2px;
    		border: none;
		}
				
		#td_es
		{
    		text-align: left;
    		
		}

		.td_title
		{
    		font-weight: bold;
    		text-align: left;
    		padding: 15px 0px 5px 0px;
        }
        .td_content
        {
        	    text-align: left;
        	    padding-left: 0px;
        }
        .td_top
        {
        	    padding-top: 0px;
        }
        
        #file_csv
        {
        	    margin: 3px 0px 0px 0px;
        }
		.ignore_span
		{
			margin-left: 15px;
		}
        
		#t_format
		{
    		text-align: left;
    		min-width: 600px;
    		padding: 5px 3px;
    		border: none;
    		background: #F2F2F2;
		margin-bottom: 15px;
    	}
		
		.td_version
		{
    		padding-top: 5px;
    		font-style: normal;
    		font-weight: bold;
		}
		
		.td_format, .td_example
		{ 
			padding: 5px 0px 0px 10px; 
			font-style: italic;		
		}
		
		#note_list
		{
    		margin: 0px 0px 0px 25px;
    		text-align: left;
		}
		
		#note_list li
		{
		    padding: 2px;
		}
		
		#c_send
		{ 
			padding: 10px 0px 15px 0px; 
			text-align: left;
		}
		             
        #av_info > div
        {
            margin: 20px auto 0px auto;
            width: 90%;
        }
        
        /* Summary */
        
        #sm_container
		{ 
			width: 90%; 
			text-align: center; 
			margin: auto; 
			position: relative;		
			display: none;
		}
		
		.error   
		{
		    color: #D8000C !important;
		}    		
		
		.warning 
		{
		    color: #9F6000 !important;
		}
		
		.success 
		{
		    color: #4F8A10 !important;
		}
		
		#c_sm_statistics
		{
    		width: 100%;
    		margin: auto;
    	}
		
		#t_sm_statistics
		{
    		width: 100%;
    		border-collapse: collapse;
    	}
    	
    	#t_sm_statistics tbody td
    	{        	
        	font-size: 12px;
    	}
		
		#t_sm_statistics thead th
    	{
            height: 20px;
    	}
		
		#c_sm_container
		{
    		width: 100%;
    		margin: 30px auto 0px auto;    		
		}	
			
		#t_sm_container
		{
    		width: 100%;
    		border-collapse: collapse;
    	}
						
		#t_sm_container th_details
        {
            width: 40px;
        }
        
        #t_sm_container .td_details
        {
            height: auto;
        }
        
        #t_sm_container .td_details img
        {
            cursor: pointer;
        }
        
        .sm_back_button
        {
        	    margin-top: 20px;
        }
        
        .dataTables_wrapper .dt_header div.dt_title 
        {                
            top:6px;
            left: 0px;
            right: 0px;
            margin: auto;
            text-align: center;
        }
        
        .details_info
        {
            display:none;
        }
        
        .asset_details_w, .asset_details_w:hover
        {
           color: #9F6000 !important;
           background-color: #FEEFB3 !important;            
        }
        
        .table_data  > tbody > tr:hover > td.asset_details_w
        {
        	background-color: #FEEFB3 !important; 
        }
        
        .asset_details_e, .asset_details_e:hover
        {
            background: #FFBABA !important;
            color: #D8000C !important;           
        }
        
        .table_data  > tbody > tr:hover > td.asset_details_e
        {
        	background: #FFBABA !important;
        }
                                
        .tray_container
        {   
            border: 0px;
            background-color: inherit;
            position:relative;
            height:100%;
            margin: 2px 5px;
        }      
                  
        .tray_triangle
        {
        	position: absolute;
            z-index: 99999999;
            top: -17px;
            left: 20px;
            width:0;
            height:0;
            border-color: transparent transparent #FFBABA transparent;
            border-style: solid;
            border-width: 7px;      
        }
                
        .tt_error
        {
             border-color: transparent transparent #FFBABA transparent;
        }
        
        .tt_warning
        {
             border-color: transparent transparent #FEEFB3 transparent;
        }
        
        .tray_container ul
        {
            text-align: left;
            padding: 20px 30px;
        }
        
        .tray_container ul li
        {
            text-align: left;
            list-style-type: square;
            color: inherit;
        }
        
        #summary_info
        {
            margin: 20px auto 0px auto;
            width: 90%;
        }
        
        #c_new_importation
        {
            text-align: center;
            margin: auto;
            padding: 20px 0px;
        }
                
	</style>
	
	<script type='text/javascript'>	
				
		$(document).ready(function(){
		
		    //Setting all handlers  
			bind_import_actions();
				
		});
	</script>
</head>

<body>

	<div id='container' class='<?php echo $container_class ?>'>	   	
    	<?php    		
    	print_form($import_type);	
    	?>	
	</div>
	
	<div id='sm_container'></div>
	
</body>
</html>