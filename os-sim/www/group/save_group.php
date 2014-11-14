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


require_once 'av_init.php';
 
Session::logcheck('environment-menu', 'PolicyHosts');

$validate = array (
	'id'          => array('validation'=>'OSS_HEX',                            'e_message' => 'illegal:' . _('ID')),	
	'ag_name'     => array('validation'=>'OSS_ALPHA, OSS_PUNC',                'e_message' => 'illegal:' . _('Asset group name')),
	'owner'       => array('validation'=>'OSS_ALPHA, OSS_PUNC, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Owner')),	
	'descr'       => array('validation'=>'OSS_NULLABLE, OSS_AT, OSS_TEXT',     'e_message' => 'illegal:' . _('Description')),
	'threshold_a' => array('validation'=>'OSS_DIGIT',                          'e_message' => 'illegal:' . _('Threshold A')),
	'threshold_c' => array('validation'=>'OSS_DIGIT',                          'e_message' => 'illegal:' . _('Threshold C')),
    'nagios'      => array('validation'=>'OSS_DIGIT, OSS_NULLABLE',            'e_message' => 'illegal:' . _('Nagios'))
);


/****************************************************
************** Checking field to field **************
*****************************************************/

//Cleaning GET array
	
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

//Checking form token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if (Token::verify('tk_ag_form', POST('token')) == FALSE)
	{
		Token::show_error();
		
		exit();
	}
}


$id           = POST('id');
$name         = POST('ag_name');
$owner        = POST('owner');
$descr        = POST('descr');
$threshold_a  = POST('threshold_a');
$threshold_c  = POST('threshold_c');
$nagios       = POST('nagios');


$validation_errors = validate_form_fields('POST', $validate);

$data['status']    = 'OK';
$data['data']      = $validation_errors;
	
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
                           
            $asset_group = new Asset_group($id);
            $asset_group->load_from_db($conn);
            
            $asset_group->set_name($name);
            $asset_group->set_owner($owner);            
            $asset_group->set_descr($descr);
            $asset_group->set_threshold($threshold_a, 'a');
            $asset_group->set_threshold($threshold_c, 'c');
                                     
            $asset_group->save_in_db($conn);
            
            $_hosts_data_aux = $asset_group->get_hosts($conn, '', TRUE);
            $hosts           = array_keys($_hosts_data_aux[0]);
            
            if (!empty($nagios))
            {
                if (Asset_group_scan::is_plugin_in_group($conn, $id, 2007))
                {
                    Asset_group_scan::delete_plugin_from_db($conn, $id, 2007);
                }
            
                Asset_group_scan::save_plugin_in_db($conn, $id, 2007);
            
                foreach ($hosts as $host_id)
                {
                    if (!Asset_host_scan::is_plugin_in_host($conn, $host_id, 2007))
                    {
                        Asset_host_scan::save_plugin_in_db($conn, $host_id, 2007);
                    }
                }
            }
            else
            {
                if (Asset_group_scan::is_plugin_in_group($conn, $id, 2007))
                {
                    Asset_group_scan::delete_plugin_from_db($conn, $id, 2007);
                }
            
                foreach ($hosts as $host_id)
                {
                    if (Asset_host_scan::is_plugin_in_host($conn, $host_id, 2007))
                    {
                        Asset_host_scan::delete_plugin_from_db($conn, $host_id, 2007);
                    }
                }
            }
                         
            $data['status'] = 'OK';
            $data['data']   = _('Asset group saved successfully');        
        	    	
            $db->close();        
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
            	document.location.href='group_form.php?id=<?php echo $id?>&msg=saved';
                window.scrollTo(0, 0);
                parent.window.scrollTo(0, 0);                  
            }
            else
            {
                document.location.href='../assets/list_view.php?type=group&msg=saved';
            }      
        </script>
        <?php
	}  		
    ?>    
    </body>
</html>