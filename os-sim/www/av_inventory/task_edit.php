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

Session::logcheck('configuration-menu', 'AlienVaultInventory');


$db   = new ossim_db();
$conn = $db->connect();

$s_type_ids = array(
	'nmap' => 5,
	'ocs'  => 3,
    'wmi'  => 4
);

$frequencies = array(
    'Hourly'  => 3600, 
    'Daily'   => 86400, 
    'Weekly'  => 604800, 
    'Monthly' => 2419200
);

$scan_modes = array(
    'ping'   => _('Ping'),
    'fast'   => _('Fast Scan'),
    'normal' => _('Normal'), 
    'full'   => _('Full Scan'),
    'custom' => _('Custom')
);

$time_templates = array(
    '-T0' => _('Paranoid'),
    '-T1' => _('Sneaky'), 
    '-T2' => _('Polite'), 
    '-T3' => _('Normal'), 
    '-T4' => _('Aggressive'),
    '-T5' => _('Insane')
);


$validate = array (
	's_type'      => array('validation' => 'nmap,ocs,wmi',                     'e_message' => 'illegal:' . _('Scheduler Type')),
	'task_name'   => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_SCORE',  'e_message' => 'illegal:' . _('Name')),
	'task_sensor' => array('validation' => 'OSS_HEX',                          'e_message' => 'illegal:' . _('Sensor')),
	'task_period' => array('validation' => 'OSS_DIGIT',                        'e_message' => 'illegal:' . _('Period')),
	'task_enable' => array('validation' => 'OSS_DIGIT',                        'e_message' => 'illegal:' . _('Enable'))
);


if ($_SESSION['av_inventory_type'] == 'nmap') 
{
	if (GET('task_params') != '') 
	{
		$_GET['task_params'] = str_replace(' ', '', GET('task_params'));
	}
	
	if (POST('task_params') != '') 
	{
		$_POST['task_params'] = str_replace(' ', '', POST('task_params'));
	}

	$validate['task_params']     = array('validation' => 'OSS_IP_CIDR',                                        'e_message' => 'illegal:' . _('Network'));
	$validate['scan_mode']       = array('validation' => 'OSS_ALPHA, OSS_SCORE, OSS_NULLABLE',                 'e_message' => 'illegal:' . _('Scan type'));
    $validate['timing_template'] = array('validation' => 'OSS_TIMING_TEMPLATE',                                'e_message' => 'illegal:' . _('Timing_template'));
    $validate['custom_ports']    = array('validation' => "OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, ','", 'e_message' => 'illegal:' . _('Custom Ports'));
    $validate['rdns']            = array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Reverse DNS resolution option'));
    $validate['autodetect']      = array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Autodetect services and OS option'));
} 
elseif ($_SESSION['av_inventory_type'] == 'wmi')
{
	$validate['task_params'] = array('validation' => 'OSS_PASSWORD',  'e_message' => 'illegal:' . _('Credentials'));
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
	else
	{
		if ($_GET['name'] == 'task_params')
		{
			if ($_SESSION['av_inventory_type'] == 'nmap')
			{
				$task_params = GET($_GET['name']);
				
				if (!Asset_net::is_cidr_in_my_nets($conn, $task_params))
				{
					$data['status']              = 'error';
					$data['data'][$_GET['name']] = _('Network not allowed').". Check your asset filter.<br/>"._('Entered value').": <strong>'".Util::htmlentities($task_params)."</strong>'";
				}
			}
			elseif ($_SESSION['av_inventory_type'] == 'wmi')
			{
				//Format example: wmihost:ip_address;wmiuser:user;wmipass:pass
				$task_params = GET($_GET['name']);
				$pattern     = '/\s*wmihost:(.*);wmiuser:(.*);wmipass:(.*)\s*/';
                
				preg_match($pattern, $task_params, $matches);
                $wmi_host = trim($matches[1]);
                $wmi_user = trim($matches[2]);
                $wmi_pass = trim($matches[3]);
               	
				if (!ossim_valid($wmi_host, OSS_IP_ADDR, 'illegal:' . _('WMI Credentials')))
				{
					ossim_clean_error();
					ossim_valid($wmi_host, OSS_HOST_NAME, 'illegal:' . _('WMI Credentials'));
				}
				
				ossim_valid($wmi_user, OSS_USER . '\\\/', 'illegal:' . _('WMI Credentials'));	
				ossim_valid($wmi_pass, OSS_PASSWORD, 'illegal:' . _('WMI Credentials'));
				
				if (ossim_error())
				{
					$data['status']              = 'error';
					$data['data'][$_GET['name']] = _('Credential format not allowed').'. <br/>'._('Entered value').": '<strong>".Util::htmlentities($task_params)."</strong>'";
				}
			}
		}
				
		if ($_GET['name'] == 'task_period')
		{
			$task_period = intval(GET($_GET['name']));
			
			if ($task_period < 1800)
			{
				$data['status'] = 'error';
				$data['data'][$_GET['name']] = _('Invalid time between scans').'. <br/>'._('Entered value').": '<strong>".Util::htmlentities($task_period)."</strong>' (1800(s) "._("minimum").")";
			}
		}
	}
	
	echo json_encode($data);	
	exit();
}
else
{
	if (POST('mode') == 'insert' || POST('mode') == 'update' || GET('mode') == 'delete') 
	{
		//Check Token
		if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
		{
			if (!Token::verify('tk_form_task', REQUEST('token')))
			{
				Token::show_error();
				exit();
			}
		}
	} 
	
	$validation_errors = validate_form_fields('POST', $validate);
	
	if (empty($validation_errors['task_params']))
	{
		if ($_SESSION['av_inventory_type'] == 'nmap')
		{
			$task_params = POST('task_params');
			$task_sensor = POST('task_sensor');
						
			if (!Asset_net::is_cidr_in_my_nets($conn, $task_params))
			{
				$validation_errors['task_params'] = _('Network not allowed').'. Check your asset filter. <br/>'._('Entered value').": <strong>'".Util::htmlentities($task_params)."</strong>'";
			}
			else if(!Asset_net::check_cidr_by_sensor($conn, $task_params, $task_sensor))
			{
    			$validation_errors['task_params'] = _("You can't scan the specified network using this sensor");
			}
		}
		elseif ($_SESSION['av_inventory_type'] == 'wmi')
		{
			//Format example: wmihost:ip_address;wmiuser:user;wmipass:pass
			$task_params = POST('task_params');
			$pattern     = '/\s*wmihost:(.*);wmiuser:(.*);wmipass:(.*)\s*/';
			
			preg_match($pattern, $task_params, $matches);
			$wmi_host = trim($matches[1]);
			$wmi_user = trim($matches[2]);
			$wmi_pass = trim($matches[3]);
			
			ossim_clean_error();
			
			if (!ossim_valid($wmi_host, OSS_IP_ADDR, 'illegal:' . _('WMI Credentials')))
			{
				ossim_clean_error();
				ossim_valid($wmi_host, OSS_HOST_NAME, 'illegal:' . _('WMI Credentials'));
			}
			
			ossim_valid($wmi_user, OSS_USER . '\\\/', 'illegal:' . _('WMI Credentials'));	
			ossim_valid($wmi_pass, OSS_PASSWORD, 'illegal:' . _('WMI Credentials'));
			
			if (ossim_error())
			{
				$validation_errors['task_params'] = _('Credential format not allowed').'. <br/>'._('Entered value').": '<strong>".Util::htmlentities($task_params)."</strong>'";
			}
		}
	}
		
	
	if (empty($validation_errors['task_period']))
	{
		if (POST('task_period') < 1800)
		{
			$validation_errors['task_period'] = _('Invalid time between scans').'. <br/>'._('Entered value').": '<strong>".Util::htmlentities(POST('task_period'))."</strong>' (1800(s) "._('minimum').')';
		}
	}
	
	$data['status'] = 'OK';
	$data['data']   = $validation_errors;

	
	if (POST('ajax_validation_all') == TRUE)
	{
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
		}
		
		echo json_encode($data);
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
}


if (GET('mode') == 'delete')
{
	$delete = intval(GET('delete'));
	ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Delete'));
	
	if (ossim_error()) 
	{
		$data['status'] = 'error';
		$data['data']   = ossim_get_error_clean();
	}
	else if ($delete < 0)
	{
		$data['status'] = 'error';
		$data['data']   = _("Invalid Task ID");
	
	}
	else
	{
		Inventory::delete($conn, $delete);
		$data['status'] = 'OK';
		$data['data']   = _('Task removed successfully');
	}
	
	echo json_encode($data);
	exit();
}
else if (POST('mode') == 'insert' || POST('mode') == 'update') 
{
	$s_type       = POST('s_type');
	$id           = intval(POST('id'));
	$name         = POST('task_name');
	$sensor_id    = POST('task_sensor');
	$params       = POST('task_params');
	
	if ($s_type == 'nmap') 
	{
	
		$nets = str_replace(' ','', $params);
		$nets = str_replace("\n", ' ', $nets);
		$nets = str_replace(',', ' ', $nets);
	
		$nmap_options     = array();
	
		$scan_mode        = POST('scan_mode');
	    $timing_template  = POST('timing_template');
	    $custom_ports     = POST('custom_ports');
	    $rdns             = (POST('rdns') == '1') ? 1 : 0;
	    $autodetect       = (POST('autodetect') == '1') ? 1 : 0;
	    
	    $nmap_options[]   = $timing_template;
	    
	    // Append Autodetect
	    if ($autodetect) 
	    {
	    	$nmap_options[] = '-A';
	    }
	    // Append RDNS
	    if (!$rdns) 
	    {
	    	$nmap_options[] = '-n';
	    }
	    
	    if ($scan_mode == 'fast') 
		{
	        $nmap_options[] = '-sS -F';
	    } 
		elseif ($scan_mode == 'custom')
	    {
	    	$nmap_options[] = "-sS -p $custom_ports";
	    }
		elseif ($scan_mode == 'normal') 
		{
	    	$nmap_options[] = '-sS';
	    }
		elseif ($scan_mode == 'full') 
		{
	    	$nmap_options[] = '-sS -p 1-65535';
	    }
	    else 
		{
	    	$nmap_options[] = '-sn -PE';
	    }
	    
	    $params = $nets.'#'.implode(' ', $nmap_options);
    }
    else if ($s_type == 'wmi') 
    {
    	preg_match('/wmipass:(.*)/', $params, $found);
		
		if ($found[1] != '' && preg_match('/^\*+$/', $found[1]) && $_SESSION['wmi_pass'] != '') 
		{
			$params = preg_replace('/wmipass:(.*)/', '', $params);
			$params = $params . 'wmipass:' . $_SESSION['wmi_pass'];
		}
    }
    
	$period = POST('task_period');
	$enable = (POST('task_enable') >= 1) ? 1 : 0;
	
	
	ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Task ID'));
	
	if (ossim_error()) 
	{
		$data['status']      = 'error';
		$data['data']['id']  = ossim_get_error_clean();
	}
	    		
	if ($data['status'] == 'error')
	{
		$txt_error = '<div>'._('We Found the following errors').":</div>
					  <div style='padding: 2px 10px 5px 10px;'>".implode('<br/>', $validation_errors).'</div>';				
				
		$config_nt = array(
			'content' => $txt_error,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => false
			),
			'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();

		Util::make_form("POST", "index.php");
		exit();
	}
	else
	{
		if (POST('mode') == 'insert') 
		{
			$id = Inventory::insert($conn, $sensor_id, $name, $s_type_ids[$s_type], $period, $params, $enable, $nets);
			Web_indicator::set_on('Reload_tasks');
			?>
			<script type="text/javascript">document.location.href = "index.php?s_type=<?php echo $s_type ?>&msg=saved"</script>
			<?php
			exit();
		} 
		elseif (POST('mode') == 'update') 
		{						
			Inventory::modify($conn, $id, $sensor_id, $name, $s_type_ids[$s_type], $period, $params, $enable, $nets);
			Web_indicator::set_on('Reload_tasks');
			?>
			<script type="text/javascript">document.location.href = "index.php?s_type=<?php echo $s_type ?>&msg=saved"</script>
			<?php
			exit();
		}
	}
}

//Get Data

$id     = intval(GET('id'));
$s_type = GET('s_type');

ossim_valid($id,     OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Task ID'));
ossim_valid($s_type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Scheduler type'));

if (ossim_error())
{
	ossim_error();
	exit();
}

if ($id != '')
{	
	if ($task_obj = Inventory::get_object($conn, $id)) 
	{
		$name      = $task_obj['task_name'];
		$sensor_id = $task_obj['task_sensor'];
		$params    = $task_obj['task_params'];
		$period    = $task_obj['task_period'];
		$enable    = $task_obj['task_enable'];
	}
}

//Sensors
$sensors = Av_sensor::get_basic_list($conn);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<?php		
	if ($s_type == 'nmap')
	{ 
        ?> 
        <script type="text/javascript" src="../js/av_scan.js.php"></script>
        <?php
    }
    ?>
    
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css"/>	
	<link rel="stylesheet" type="text/css" href="../style/alarm/detail.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css" />
	
	<style type='text/css'>
		
		input[type='text'], input[type='hidden'], select 
		{
    		width: 99%; 
    		height: 18px;
		}
		
		textarea 
		{
    		width: 99%; 
    		height: 45px;
		}
		
		.legend 
		{
		    font-size: 10px;
    		font-style: italic;
    		text-align: center; 
    		padding: 0px 0px 5px 0px;
    		margin: auto;
    		width: 400px;
		}				
		
		#avi_container
		{
			width: 600px;
			margin: 10px auto;
		}
		
		#av_msg_info
		{
    		top: -7px !important;
		}
		
		#avi_info
		{
			width: 600px;
			top: 3px;
			margin: 5px auto 10px auto;
			min-height: 40px;
		}
		
		#t_avi
		{
			width: 600px;
			margin:auto;
		}
		
		#t_avi th
		{ 
    		padding: 3px; 
    		width: 150px;
		}
		
		#t_avi td
		{ 
    		padding: 3px; 
		}
		
		#t_avi input[type='text']
		{ 
    		height: 16px; 
		}			

		.c_back_button 
		{
			left:6px;
			top:6px;
		}
		
		.r_loading
        { 
            position:absolute; 
            right: 0px; 
            top: 3px;     		
        }		
		
		<?php 
		if ($s_type == 'nmap') 
		{   
            ?>         
            .greyfont
            {
                color: #666666;
            }            
            
            #t_adv_options
    		{
        		width: 100%;
        		background: none;
        		border: none;
    		}
    		
    		#t_adv_options td
    		{
        		text-align: left;
        		border: none; 		
    		}
    		
    		#t_adv_options .td_label
    		{
        		text-align: left;
        		white-space: nowrap;
        		width: 100px;  		
    		}
    		
    		#t_adv_options .nmap_select
    		{
        		width: 90px;		
    		}
    		
    		#t_adv_options #custom_ports
    		{
        		width: 200px;  		
    		}
    		    		    		    			
    		#t_adv_options img 
    		{
    		  display: none;
    		  cursor: pointer;
    		}         
	        <?php
        }
        ?>
	</style>
	
	<script type="text/javascript">

        function go_back()
        {
            document.location.href='index.php?s_type=<?php echo $s_type?>';
            
            return false;
        }
		
		<?php		
		if ($s_type == 'nmap')
		{ 
    		?>    		
    		// Fill autocomplete with networks 
    		function fill_autocomplete(networks) 
            {
                $("#task_params").autocomplete(networks, {
                	minChars: 0,
                	width: 225,
                	matchContains: "word",
                	autoFill: false,
                	formatItem: function(row, i, max) {
                		return row.txt;
                	}
                }).result(function(event, item) {
                	
                    if (typeof(item.id) != 'undefined')
                    {
                        $("#task_params").val(item.id);
                    }
                });				
            }
            
            function get_sensor_by_nets(sid)
            {
                $('.r_loading').html('<img src="../pixmaps/loading.gif" align="absmiddle" width="13" alt="<?php echo _('Loading')?>">');

        		$.ajax(
        		{
                    type: "GET",
					url: "get_nets_by_sensor.php",
					data: { sensor_id: sid },
        			dataType: "json",
        			cache: false,
        			async: false,
					success: function(msg) 
					{
						if(typeof(msg) != 'undefined' && msg != null && msg.status == 'OK')
						{
                            // Autocomplete networks
                            fill_autocomplete(msg.data);
                            
                            return true;
						}
						else 
						{
    						notify('<?php echo _('An error occurred when trying to retrieve nets')?>', 'nf_error');
    						
    						return false;
						}
					}
                });
                
                $('.r_loading').empty();
            }
    		<?php
    	}
    	?>
	
		
		$(document).ready(function()
		{			
            /****************************************************
             ****************** AJAX Validator ******************
             ****************************************************/
                 
			var config = {
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'avi_info'
				},
				form : {
					id  : 'form_task',
					url : "task_edit.php"
				},
				actions: {
					on_submit:{
						id: 'send',
						success:  '<?php echo _('Save')?>',
						checking: '<?php echo _('Saving')?>'
					}
				}
			};
			ajax_validator = new Ajax_validator(config);

			$('#send').click(function() {
				ajax_validator.submit_form();
			});
				
				
						
			/****************************************************
             ********************* Tooltips *********************
             ****************************************************/
             			
			$(".info").tipTip({maxWidth: 'auto'});
						
			
			
			/****************************************************
             ********************** Token ***********************
             ****************************************************/
			
			Token.add_to_forms();
			
			
			/****************************************************
             **************** Autocomplete Nets *****************
             ****************************************************/
						
			$("#task_sensor").change(function() 
			{
                $('#task_params').flushCache();
                $('#task_params').val('');
			    
			    var sid = $("#task_sensor").val();
			    
			    get_sensor_by_nets(sid);

			});
			
            <?php		
            if ($s_type == 'nmap')
            { 
                ?>
                bind_nmap_actions();
                
                var sid = $("#task_sensor").val();
                
			    get_sensor_by_nets(sid);
                <?php
            }
            ?>
									
			//Greybox options			
			if (typeof parent.is_lightbox_loaded == 'function' && !parent.is_lightbox_loaded(window.name))
			{ 			
    			$('.c_back_button').show();
    		}			
		});
	</script>
</head>

<body>

    <div class='c_back_button breadcrumb_back'>
		<div class='breadcrumb_item'>
			<a href='javascript:;' onclick='go_back();'><?php echo _('Scheduler') ?></a>
		</div>
		<div class='breadcrumb_separator'>
			<img src='/ossim/pixmaps/xbreadcrumbs/separator.gif' />
		</div>
		<div class='breadcrumb_item last'>
			<?php echo strtoupper($s_type) ?>
		</div>
		<div style='clear:both;'>&nbsp;</div>
	</div>

	<div id='avi_container'>

		<div id='avi_info'></div>
		
		
		<div class="legend">
            <?php echo _('Values marked with (*) are mandatory');?>
        </div>	
		
		<form name="form_task" id="form_task" method='POST'>
			<input type="hidden" name="s_type" value="<?php echo $s_type?>" class='vfield'/>
			<input type="hidden" name="mode" value="<?php echo ($id != '') ? "update" : "insert"?>"/>
			<input type="hidden" name="id" id="id" value="<?php echo $id?>"/>
			
			<table id='t_avi'>
				<tr>
					<th>
					    <label for="task_name"><?php echo _('Name') . required();?></label>
					</th>
					<td class="left">
						<input type='text' name='task_name' id='task_name' class='vfield' value="<?php echo $name?>"/> 
					</td>
				</tr>
			
				<tr>
					<th>
					    <label for="task_sensor"><?php echo _('Sensor') . required();?></label>
					</th>					
					<td class="left">
						<select name="task_sensor" id="task_sensor" class='vfield'>
							<?php 
							foreach ($sensors as $s_id => $s_data) 
							{ 
								$selected = ($s_id == $sensor_id) ? 'selected="selected"' : '';
    											
    							echo "<option value='$s_id' $selected>".$s_data['name']."</option>";				
							} 
							?>
						</select>
					</td>
				</tr>
				
				<?php 
				if ($s_type == 'nmap')
				{ 
					$title = _('You can type one unique CIDR (x.x.x.x/xx) or a CIDR list separated by coma: CIDR1, CIDR2, CIDR3...');
					
					// Default values
					$ttemplate        = '-T3';
					$aggressive_scan  = TRUE;
					$rdns             = TRUE;
					$scan_mode        = 'fast';
					$scan_ports       = '';
					
					if($params != '') 
					{					
						$tmp_data  = explode('#', $params);
						
						// get timing template
					
						preg_match('/(\-T[0-5])/', $tmp_data[1], $found);
					
						$ttemplate = ($found[1] != '') ? $found[1]: '';
						
						// aggresive scan
						
						preg_match('/\s(\-A)\s/', $tmp_data[1], $found);
						
						$aggressive_scan = ($found[1] != '') ? TRUE : FALSE;
						
						// reverse DNS resolution
						
						preg_match('/\s(\-n)\s/', $tmp_data[1], $found);
						
						$rdns = ($found[1] != '') ? FALSE : TRUE; 
						
						// scan type
						
						if(preg_match('/-sS -F/', $tmp_data[1])) 
						{
							$scan_mode = 'fast'; 							    
	        			}
						elseif (preg_match('/\-sS \-p 1\-65535/', $tmp_data[1])) 
						{
							$scan_mode = 'full';
					    }
						elseif (preg_match('/\-sS \-p (\d+\-\d+)/', $tmp_data[1], $found))
					    {
					    	$scan_mode  = 'custom';
					    	$scan_ports = $found[1];
					    }
						elseif (preg_match('/\-sS/', $tmp_data[1])) 
						{
							$scan_mode = 'normal';
					    }
					    else 
						{
							$scan_mode = 'ping';
							$aggressive_scan = FALSE;
					    } 
			
					
						$nets = $tmp_data[0];
					}
					else 
					{
						$nets = '';
					}
					
					$nets = str_replace(' ', ', ', $nets);
					?>
					<tr>
						<th>						
    						<label for="task_params"><?php echo _('Network to scan') . required();?></label>    
						</th>
						<td class="left">
							<div style="position:relative; width: 99%;">
                                <div class="r_loading"></div>
                            </div>							
							<input type='text' name='task_params' id='task_params' title='<?php echo $title ?>' class='vfield info' value="<?php echo $nets?>"/>
						</td>
					</tr>
					<tr>
						<th>
						    <?php echo _('Advanced Options')?>
						</th>
						<td class="left">
							
                            <table id='t_adv_options'>
                                
                                <!-- Full scan -->
                                <tr>
                                    <td class='td_label'>
                                        <label for="scan_mode"><?php echo _('Scan type')?>:</label>        
                                    </td>
                                    <td>                                       
                                        <select id="scan_mode" name="scan_mode" class="nmap_select vfield">
    										<?php
    										foreach ($scan_modes as $sm_v => $sm_txt)
    										{
    											$selected = ($scan_mode == $sm_v) ? 'selected="selected"' : '';
    											
    											echo "<option value='$sm_v' $selected>$sm_txt</option>";								
    										}
    										?>								
    									</select>
    									<span id="scan_mode_info"><img class='img_help_info' src="../pixmaps/helptip_icon.gif"/></span>							
                                    </td>                       
                                </tr>                           
                                
                                <!-- Specific ports -->
                                <tr id='tr_cp'>                                    
                                    <td class='td_label'>
                                        <label for="custom_ports"><?php echo _('Specify Ports')?>:</label>        
                                    </td>
                                    <td>
                                        <?php 
                                            $scan_ports = ($scan_ports == '') ? '1-65535' : $scan_ports;
                                        ?>
                                        <input class="greyfont vfield" type="text" id="custom_ports" name="custom_ports" value="<?php echo $scan_ports?>"/>      
                                    </td>
                                </tr>
                                
                                <!-- Time template -->
                                <tr>
                                    <td class='td_label'>
                                        <label for="timing_template"><?php echo _('Timing template')?>:</label>        
                                    </td>
                                    <td>                                      
                                        <select id="timing_template" name="timing_template" class="nmap_select vfield">			
											<?php
											foreach ($time_templates as $tt_v => $tt_txt)
											{
												$selected = ($ttemplate == $tt_v) ? 'selected="selected"' : '';
												
												echo "<option value='$tt_v' $selected>$tt_txt</option>";								
											}
											?>													
										</select>
										<span id="timing_template_info"><img class='img_help_info' src="../pixmaps/helptip_icon.gif"/></span>
                                    </td>                         
                                </tr>
                                                                 
                                <tr>
                                    <td colspan="2">
                                                             
                                        <?php $ad_checked = ($aggressive_scan == TRUE) ? 'checked="checked"' : '';?>
                                        
                                        <input type="checkbox" id="autodetect" name="autodetect" class='vfield' <?php echo $ad_checked?> value="1"/>
                                        <label for="autodetect"><?php echo _('Autodetect services and Operating System')?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <?php $rdns_checked = ($rdns == TRUE) ? 'checked="checked"' : '';?>
                                        
                                        <input type="checkbox" id="rdns" name="rdns" class='vfield' <?php echo $rdns_checked?>  value="1"/>
                                        <label for="rdns"><?php echo _('Enable reverse DNS Resolution')?></label>                                    
                                    </td>                                    
                                </tr>                               
                                
                            </table>
						</td>
					</tr>					
					<?php 
				} 
				elseif ($s_type == 'wmi') 
				{ 
					preg_match('/wmipass:(.*)/', $params, $found);

					if ($found[1] != '') {
						$params               = preg_replace('/wmipass:(.*)/', '', $params);
						$_SESSION['wmi_pass'] = $found[1];
						$params               = $params . 'wmipass:' . preg_replace('/./', '*', $found[1]);
					}

					$title = _('Format example').': wmihost:<i>ip_address</i>;wmiuser:<i>user</i>;wmipass:<i>pass</i>';
					
					?>
					<tr>
						<th>
						    <label for="task_params"><?php echo _('Credentials') . required()?></label>
						</th>
						<td class="left">
							<input type='text' name='task_params' title="<?php echo $title?>" id='task_params' class='vfield info' value="<?php echo $params?>"/>
						</td>
					</tr>
					<?php 
				} 
				?>
				
				<tr>
					<th>
					    <label for="task_period"><?php echo _('Frequency')?></label>
					</th>
					<td class="left">
						<select name="task_period" id="task_period" class='vfield'>
							<?php 
							foreach ($frequencies as $fname => $fseconds) 
							{
								?>
								<option value="<?php echo $fseconds?>" <?php if ($period == $fseconds) echo "selected='selected'"?>><?php echo $fname?></option>
								<?php 
							}
							?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th>
					    <label for="task_enable"><?php echo _('Enabled')?></label>
					</th>
					<td class="left">
						<select name="task_enable" id="task_enable" class='vfield'>
							<option value="0" <?php if (!$enable) echo 'selected="selected"'?>><?php echo _('No')?></option>
							<option value="1" <?php if ($enable) echo 'selected="selected"'?>><?php echo _('Yes')?></option>
						</select>
					</td>
				</tr>

				<tr>
					<td colspan="2" align="center" style="padding: 10px;" class='noborder'>
						<input type="button" name='send' id='send' value="<?php echo _('Save')?>"/>											
					</td>
				</tr>
				
			</table>
		</form>
	</div>
</body>
</html>

<?php $db->close();?>