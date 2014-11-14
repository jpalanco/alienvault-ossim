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


//Config File
require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

//Validation array

$validate = array(
	'id'            =>  array('validation' => 'OSS_HEX',				                       'e_message'  =>  'illegal:' . _('Host ID')),
	'ctx'           =>  array('validation' => 'OSS_HEX',				                       'e_message'  =>  'illegal:' . _('Entity ID')),
	'h_name'        =>  array('validation' => 'OSS_HOST_NAME',                                 'e_message'  =>  'illegal:' . _('Host name')),
	'ip'            =>  array('validation' => 'OSS_SEVERAL_IP_ADDRCIDR_0',                     'e_message'  =>  'illegal:' . _('IP')),
	'fqdns'         =>  array('validation' => 'OSS_FQDNS, OSS_NULLABLE',                       'e_message'  =>  'illegal:' . _('FQDN/Aliases')),
	'external'      =>  array('validation' => 'OSS_DIGIT',                              	   'e_message'  =>  'illegal:' . _('External Asset')),
	'descr'         =>  array('validation' => 'OSS_TEXT, OSS_NULLABLE, OSS_AT',                'e_message'  =>  'illegal:' . _('Description')),
	'asset_value'   =>  array('validation' => 'OSS_DIGIT',                                     'e_message'  =>  'illegal:' . _('Asset value')),
	'sboxs[]'       =>  array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT',        'e_message'  =>  'illegal:' . _('Sensors')),
	'threshold_a'   =>  array('validation' => 'OSS_DIGIT',                                     'e_message'  =>  'illegal:' . _('Threshold A')),
	'threshold_c'   =>  array('validation' => 'OSS_DIGIT',                                     'e_message'  =>  'illegal:' . _('Threshold C')),
	'devices[]'     =>  array('validation' => 'OSS_DIGIT, OSS_PUNC, OSS_NULLABLE',             'e_message'  =>  'illegal:' . _('Devices')),
	'nagios'        =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                       'e_message'  =>  'illegal:' . _('Nagios')),
	'latitude'      =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC',  'e_message'  =>  'illegal:' . _('Latitude')),
	'longitude'     =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC',  'e_message'  =>  'illegal:' . _('Longitude')),
	'zoom'          =>  array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                       'e_message'  =>  'illegal:' . _('Zoom'))
);


/****************************************************
************** Checking field to field **************
*****************************************************/

//Cleaning GET array

if (GET('ip') != '') 
{
	$_GET['ip'] = str_replace(' ', '', GET('ip'));
}

if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	
	echo json_encode($data);	
	
	exit();
}



/****************************************************
**************** Checking all fields ****************
*****************************************************/

//Cleaning POST array

$_POST['ip'] = str_replace(' ', '', POST('ip'));


if (isset($_POST['sboxs']) && !empty($_POST['sboxs'])) 
{
    $_POST['sboxs'] = Util::clean_array(POST('sboxs'));
}

//Checking form token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if (Token::verify('tk_host_form', POST('token')) == FALSE)
	{
		Token::show_error();
		
		exit();
	}
}

$id		      = POST('id');
$ips_string   = $_POST['ip'];
$ctx	      = POST('ctx');
$name         = POST('h_name');
$ip           = POST('ip');
$external     = POST('external');
$fqdns        = POST('fqdns');
$descr	      = POST('descr');
$asset_value  = POST('asset_value');
$latitude     = POST('latitude');
$longitude    = POST('longitude');
$zoom         = POST('zoom');
$nagios       = POST('nagios');	
$threshold_a  = POST('threshold_a');
$threshold_c  = POST('threshold_c');
$sensors      = $_POST['sboxs'];
$devices      = POST('devices');


$validation_errors = validate_form_fields('POST', $validate);

//Extra validations

if (empty($validation_errors))
{
	$db   = new ossim_db();
	$conn = $db->connect();			
	
	//Validating icon format and size

	$icon = '';
	
	if (is_uploaded_file($_FILES['icon']['tmp_name']))
	{
	   $icon = file_get_contents($_FILES['icon']['tmp_name']);
	}

	if ($icon != '') 
	{ 
		$image = @imagecreatefromstring($icon);
		
		if (!$image || imagesx($image) > 16 || imagesy($image) > 16)
		{
			$validation_errors['icon'] = _('Image format is not allowed. Allowed only 16x16 PNG images');
		}
	}
			
	//Validating IPs 
    $aux_ips = explode(',', $ips_string);
    
    foreach($aux_ips as $ip)
    { 			
		$host_ids = Asset_host::get_id_by_ips($conn, $ip, $ctx);
                       
		unset($host_ids[$id]);
    
		if (!empty($host_ids))
        {		
			$validation_errors['ip'] = _('Error! IP not allowed.')." IP $ip "._('already exists for this entity');
			
			break;
		}			
		else
		{
    		$cnd_1 = Session::get_net_where() != '' && !Session::only_ff_net();
    		$cnd_2 = Asset_host::is_ip_in_cache_cidr($conn, $ip, $ctx, TRUE);
    		
    		if ($cnd_1 && !$cnd_2) 
    		{		
    			$validation_errors['ip'] = _("Error! IP $ip not allowed.  Check your asset filter");
    			
    			break;
    		}
		}	
    }
    
    
	//Validating Sensors	
	
	if (is_array($sensors) && !empty($sensors))
	{
    	foreach($sensors as $sensor)
    	{
    		if (!Av_sensor::is_allowed($conn, $sensor)) 
    		{
    			$validation_errors['sboxs[]'] = _('Error! Host could not be saved because there are unallowed sensors');
    		}
    	}    	
	}
	else
	{
    	$validation_errors['sboxs[]'] = _("Error in the 'Sensors' field (missing required field)");
	}		

	$db->close();
}

$data['status'] = 'OK';
$data['data']   = $validation_errors;
				
if (POST('ajax_validation_all') == TRUE)
{
	if (is_array($validation_errors) && !empty($validation_errors))
	{
		$data['status'] = 'error';
		echo json_encode($data);
	}
	else
	{		
		echo json_encode($data);
	}
	
	exit();
}
else
{
	if (is_array($validation_errors) && !empty($validation_errors))
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
    	<title><?php echo _('OSSIM Framework');?></title>
    	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    	<meta http-equiv="Pragma" content="no-cache">
    	<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    </head>
    
    <body>
    <?php
    if ($data['status'] != 'error')
	{    	        
        try
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            $is_in_db = Asset_host::is_in_db($conn, $id);
            $host     = new Asset_host($conn, $id);
            
            if ($is_in_db == TRUE)
            {
                $can_i_modify_ips    = Asset_host::can_i_modify_ips($conn, $id);            
                $can_i_create_assets = TRUE;
                
                $host->load_from_db($conn, $id);             
            }
            else
            {
                $can_i_modify_ips    = TRUE;            
                $can_i_create_assets = Session::can_i_create_assets();
            }
            
            
            if ($can_i_create_assets == TRUE)
            {            
                $host->set_ctx($ctx);
                $host->set_name($name);                
                
                if ($can_i_modify_ips == TRUE)
                {                      
                    if (is_array($aux_ips) && !empty($aux_ips)) 
                    {
                		foreach ($aux_ips as $ip) 
                        { 
                            $ips[$ip] = array(
                			    'ip'   =>  $ip,
                			    'mac'  =>  NULL,
            			    );
                        }
                        
                        $host->set_ips($ips);          
                    }            
                }                                
                            
                $host->set_descr($descr);
                
                if ($icon != '')
                {
                    $host->set_icon($icon);
                }
                
                $host->set_fqdns($fqdns);
                $host->set_external($external);                
                              
                $host->set_location($latitude, $longitude, $zoom);
                
                $host->set_asset_value($asset_value);
                $host->set_threshold_c($threshold_c);
                $host->set_threshold_a($threshold_a);
                         
                $host->set_devices($devices);                              
                
                $host->set_sensors($sensors);                
                               
                $host->save_in_db($conn);                                                                                      
                                    
                Asset_host_scan::delete_plugin_from_db($conn, $id, 2007);
                
                if (!empty($nagios))
            	{
                    Asset_host_scan::save_plugin_in_db($conn, $id, 2007);
               	}                               
                
                $data['status'] = 'OK';
                $data['data']   = _('Host saved successfully');        
        	    	
               	$db->close();                       
            }
            else
            {
                $data['status'] = 'error';
                $data['data']   = array ('no_create_asset' => _("Sorry, you don't have permissions to create assets"));                  
            }
        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
            $data['data']   = array ('php_exception' => $e->getMessage());
        }        
    }
    	
    	
	if ($data['status'] == 'error')
	{
    	$txt_error = '<div>'._('We Found the following errors').":</div>
					  <div style='padding: 10px;'>".implode('<br/>', $data['data']).'</div>';				
				
		$config_nt = array(
			'content'  =>  $txt_error,
			'options'  =>  array (
				'type'           =>  'nf_error',
				'cancel_button'  =>  FALSE
			),
			'style'    =>  'width: 80%; margin: 20px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
	}
	else
	{    	            	
    	?>
    	<script type='text/javascript'>
            if (parent.is_lightbox_loaded(window.name))
            {        	
            	document.location.href='host_form.php?id=<?php echo $id?>&msg=saved';
                window.scrollTo(0, 0);                  
            }
            else
            {
                document.location.href='../assets/index.php?msg=saved';
            }      
        </script>
        <?php
	}  		
    ?>    
    </body>
</html>
