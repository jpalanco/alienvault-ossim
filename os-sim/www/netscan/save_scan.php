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



/****************************************************
****************** AJAX Validation ******************
*****************************************************/



//Validation array

$validate = array (
	'group_name'    => array('validation' => 'OSS_SCORE, OSS_INPUT, OSS_NULLABLE',         'e_message' => 'illegal:' . _('Group name')),	
	'descr'         => array('validation' => 'OSS_TEXT, OSS_NULLABLE, OSS_AT',             'e_message' => 'illegal:' . _('Description')),
	'asset_value'   => array('validation' => 'OSS_DIGIT',                                  'e_message' => 'illegal:' . _('Asset value')),
	'external'      => array('validation' => 'OSS_DIGIT',                                  'e_message' => 'illegal:' . _('External Asset')),
	'sboxs[]'       => array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT',     'e_message' => 'illegal:' . _('Sensors')),
	'threshold_a'   => array('validation' => 'OSS_DIGIT',                                  'e_message' => 'illegal:' . _('Threshold A')),
	'threshold_c'   => array('validation' => 'OSS_DIGIT',                                  'e_message' => 'illegal:' . _('Threshold C')),
	'nagios'        => array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                    'e_message' => 'illegal:' . _('Nagios'))
);


/****************************************************
************** Checking field to field **************
*****************************************************/

if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	else
	{
    	if ($_GET['name'] == 'group_name' && !empty($_GET['group_name']))
    	{    	
        	//Database connection
            $db   = new ossim_db();
            $conn = $db->connect();	  
        	
        	//Checking group name
        	$_hostgroups = Asset_group::get_id_by_name($conn, $group_name);        	
        	
        	if (is_array($_hostgroups) && !empty($_hostgroups))
        	{ 
            	$data['status'] = 'error';
            	$data['data']['group_name'] = _('Error! The group name already exists');
        	}
        	
        	$db->close();
        }
	}
	
	echo json_encode($data);	
	
	exit();
}



/****************************************************
**************** Checking all fields ****************
*****************************************************/


if (isset($_POST['sboxs']) && !empty ($_POST['sboxs'])) 
{
    $_POST['sboxs'] = Util::clean_array(POST('sboxs'));
}

//Checking form token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if (Token::verify('tk_scan_form', POST('token')) == FALSE)
	{
		Token::show_error();
		
		exit();
	}
}


$ips           = POST('ips');
$group_name    = POST('group_name');
$descr	       = POST('descr');
$asset_value   = POST('asset_value');
$external      = POST('external');
$sensors       = POST('sboxs');
$nagios        = POST('nagios');	
$threshold_a   = POST('threshold_a');
$threshold_c   = POST('threshold_c');



$validation_errors = validate_form_fields('POST', $validate);

//Extra validations

if (empty($validation_errors))
{	
	//Database connection
    $db   = new ossim_db();
    $conn = $db->connect();
    
	//Validating Sensors
	
	if (is_array($sensors) && !empty($sensors))
	{
    	foreach($sensors as $sensor)
    	{
    		if (!Av_sensor::is_allowed($conn, $sensor)) 
    		{
    			$validation_errors['sboxs[]'] = _('Error! Net could not be saved because there are unallowed sensors');
    		}
    	}    	
	}
	else
	{
    	$validation_errors['sboxs[]'] = _("Error in the 'Sensors' field (missing required field)");
	}
	
	if (!empty($group_name))
	{
    	//Checking group name
    	$_hostgroups = Asset_group::get_id_by_name($conn, $group_name);        	
    	
    	if (is_array($_hostgroups) && !empty($_hostgroups))
    	{ 
        	$validation_errors['group_name'] = _('Error! The group name already exists');
    	}
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



/****************************************************
******************** Show Results *******************
*****************************************************/

?>    
<div class="c_back_button">         
    <input type='button' class="av_b_back" onclick="document.location.href='index.php'"/> 
</div>
<?php
    
    
//There are validation errors

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
    $db   = new ossim_db();
    $conn = $db->connect();                
            
    //Scan results
    
    $scan         = new Scan();
    $scan_results = $scan->get_results();
    
    //Sensor context
    $ctx = $scan_results['sensor']['ctx'];   
        
    session_write_close();
            
    $data = Scan::save_hosts_in_db($conn, $scan_results, $_POST);  
           
    //Check general status
   
    if (count($data['general']['hosts_in_group']) == 0)
    {
        $data['general']['status'] = 'error';
        $data['general']['data']   = _('Error! Hosts no saved'); 
    }
    else
    {
        if (count($data['general']['hosts_in_group']) == $data['general']['total_hosts'])
        {
            $data['general']['status'] = 'success';
            $data['general']['data']   = _('Hosts saved successfully');
            
            
            foreach($data['by_host'] as $h_key => $h_data)
            {
                if ($h_data['status'] == 'warning')
                {
                    $data['general']['status'] = 'warning';
                    $data['general']['data']   = _('Hosts saved with warnings');
                    
                    break;                    
                }
            }                 
        }
        else
        {
            $data['general']['status'] = 'warning';
            $data['general']['data']   = _('Warning! Some hosts could not be saved'); 
        }
        
        //Create a Asset Group        
                    
        if (!empty($group_name))
        {
            $new_group_id = Util::uuid();
        
            $group = new Asset_group($new_group_id);        
            $group->set_name($group_name);
            $group->set_ctx($ctx);
           
            $group->save_in_db($conn);
                                 
            $group->save_assets_from_list($conn, $data['general']['hosts_in_group']);
        }
    }
    
    
    /*
    echo '<pre style="white-space: pre;">';
        print_r($data);
        print_r($scan_results);   
    echo '</pre>';         
    */
    
    //Showing scan results        
    ?>
       
    <div id="summary_container">
        
        <div id='av_info' style='width: 100%;'>
            <?php
            $config_nt = array(
    			'content'  =>  $data['general']['data'],
    			'options'  =>  array (
    				'type'           =>  'nf_'.$data['general']['status'],
    				'cancel_button'  =>  FALSE
    			),
    			'style'    =>  'width: 80%; margin: 20px auto; text-align: left;'
    		); 
    						
    		$nt = new Notification('nt_1', $config_nt);
    		$nt->show();
    		?>
        </div>

    
        <table class='table_data' id="t_sm_container">
            <thead>                   
                <tr>
                    <th class='th_ip'><?php echo _('IP')?></th>
                    <th class='th_hostname'><?php echo _('Hostname')?></th>
                    <th class='th_status'><?php echo _('Status')?></th>
                    <th class='th_details'><?php echo _('Details')?></th>
                </tr>                    
            </thead>
            
            <tbody>
            <?php
            $host_info = $scan_results['scanned_ips'];                
                          
            foreach($data['by_host'] as $host_key => $host_data)
            {
                ?>                 
                <tr>
                    <td class='td_ip'>
                        <?php echo $host_info[$host_key]['ip'];?>
                    </td>  
                    <td class='td_hostname'>
                        <?php                                
                            $hostname = '';
                            $id       = $data['general']['hosts_in_group'][$host_key];                                                     
                                                          
                            if (!empty($id))
                            {
                                $hostname = Asset_host::get_name_by_id($conn, $id);                               
                            }                                       
                                                                                          
                            if (empty($hostname))
                            {
                                $hostname = $host_info[$host_key]['hostname'];
                            }
                            
                            echo $hostname;                                
                        ?>
                    </td>  
                    <td class='td_status'>
                        <span class="<?php echo $host_data['status']?>"><?php echo ucfirst($host_data['status'])?></span>                        
                    </td>  
                    <td class='td_details'>
                        <?php                            
                        if ($host_data['status'] != 'success')
                        {
                            ?>
                            <img src="/ossim/pixmaps/show_details.png"/>
                                                            
                            <div class="details_info">                                    
                                <ul>                                        
                                <?php
                                foreach($host_data['data'] as $error_data)
                                {
                                    ?>
                                    <li><?php echo $error_data?></li>
                                    <?php
                                }
                                ?>
                                </ul>                       
                            </div>                                
                            
                            <?php
                        }
                        else
                        {
                            echo '-';   
                        }                           
                        ?>
                    </td>                
                </tr>                                                             
                <?php             
            }                   
            ?>
            </tbody>
        </table>   
    </div>
    
    <script type="text/javascript">   
        
        
        /****************************************************
         ******************* Greybox Options ****************
         ****************************************************/
        
        		
        if (!parent.is_lightbox_loaded(window.name))
        { 			
            $('.c_back_button').show();
        }  
        
        
        var dt = $('#t_sm_container').dataTable({                
            "iDisplayLength": 10,
            "bLengthChange": true,
            "sPaginationType": "full_numbers",
            "bFilter": false,
            "bJQueryUI": true,
            "bSort": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
                { "bSortable": true, "sClass": "center" },
                { "bSortable": true, "sClass": "center" },
                { "bSortable": true, "sClass": "center" },
                { "bSortable": false,"sClass": "center" }                         
            ],
            oLanguage : 
            {
                "sProcessing": "&nbsp;<?php echo _('Loading results') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
                "sLengthMenu": "&nbsp;Show _MENU_ entries",
                "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
                "sEmptyTable": "&nbsp;<?php echo _('No assets found in the system') ?>",
                "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ assets') ?>",
                "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 assets') ?>",
                "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total assets') ?>)",
                "sInfoPostFix": "",
                "sInfoThousands": ",",
                "sSearch": "<?php echo _('Search') ?>:",
                "sUrl": "",
                "oPaginate": 
                {
                    "sFirst":    "<?php echo _('First') ?>",
                    "sPrevious": "<?php echo _('Previous') ?>",
                    "sNext":     "<?php echo _('Next') ?>",
                    "sLast":     "<?php echo _('Last') ?>"
                }
            },                
            "fnDrawCallback": function(oSettings) {
    
                //Bind handlers
                var title = "<div class='dt_title'><?php echo _('Scan Summary') ?></div>";
    			$('div.dt_header').prepend(title); 
    			        				
    			if ($('#t_sm_container .dataTables_empty').length == 0)
    			{
    				$('#t_sm_container tbody tr .td_details img').each(function(index){				
        				
        				//Handler
        				$(this).click(function(){
            		      				
                            var nTr = this.parentNode.parentNode;
                            
                            if ($(this).hasClass('show'))
                            {
                                //Close details
                                $(this).removeClass('show').addClass('hide')                           			
                                dt.fnClose(nTr);
                            }
                            else
                            {
                                /* Open this row */ 
                                $(this).removeClass('hide').addClass('show')
                                
                                                                        
                                var details = $(this).next('div').html();                                    
                                var status  = $(this).parents('tr').find('.td_status span').text();  
                                
                                
                                if (status  == 'Warning')
                                {
                                    var tt_class = 'tt_warning';
                                    var hd_class = 'host_details_w';
                                }
                                else
                                {
                                    var tt_class = 'tt_error';
                                    var hd_class = 'host_details_e';
                                }
                                
                                                                                                                  			
                                var html_details = '<div class="tray_container">' +
                                   '<div class="tray_triangle ' + tt_class +'"></div>' +
                                        details +               
                                   '</div>';           			
                                                                 
                                                                                                                          			
                                dt.fnOpen(nTr, html_details, hd_class);
                            }                  				            				
        				});            				                								
        			});	
    			}                                     		    
    		}                
        });  	
    </script> 
   
    <?php
            
    //Delete scan results
    
    if (count($data['general']['hosts_in_group']) > 0)
    {
        $scan_object = new Scan();
        
        $scan_object->delete_data();       
    }
    
    $db->close();
}