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
require_once dirname(__FILE__) . '/../../../../config.inc';
require_once 'data/sections/configuration/utilities.php';

session_write_close();


if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/section.php')
{
    exit();
}


$system_id = POST('system_id');
ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));


if (ossim_error())
{
    $config_nt = array(
			'content' => ossim_get_error(),
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
			'style'   => 'margin: auto; width: 90%; text-align: center;'
		);


	$nt = new Notification('nt_1', $config_nt);
	$nt->show();

	exit();
}

//Framework URL
$url  = (empty($_SERVER['HTTPS'])) ?  'http://' : 'https://';
$url .= 'SERVER_IP/ossim/session/login.php?action=logout';


/**************************************************************
*****************  Network Configuraton Data  *****************
***************************************************************/

$network_cnf  = Av_center::get_network_configuration($system_id);


if ($network_cnf['status'] == 'error')
{
	$config_nt = array(
			'content' => _('Error retrieving information. Please, try again'),
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
			'style'   => 'margin: 100px auto; width: 550px; text-align: center;'
		);


	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
}
else
{
	$cnf_data = $network_cnf['data'];

	$yes_no = array ('no' => _('No'), 'yes' => _('Yes'));

	?>
	<div id='nc_notification'>
        <div id='nc_info' class='c_info'></div>
	</div>

	<div id='nc_container'>

		<div class="w_overlay" style="height:100%;"></div>

		<div class='cnf_header'><div class='cnf_h_title'><?php echo _('Network Configuration')?></div></div>

		<div class='cnf_body'>
    		<form id='f_nc' method='POST'>

    			<input type='hidden' id='server_addr' class='vfield' name='server_addr' value='<?php echo $cnf_data['admin_ip']['value']?>'/>
    			<input type='hidden' id='server_url'  class='vfield' name='server_url'  value='<?php echo $url?>'/>
    			<input type='hidden' id='hostname'    class='vfield' name='hostname'    value='<?php echo $cnf_data['hostname']['value']?>'/>

    			<table id='t_nc'>
    				<tr>
    					<th colspan='3' class='t_nsh'><?php echo _('General Settings')?></th>
    				</tr>

    				<tr>
    					<th class='_label'><?php display_label($cnf_data['admin_dns'])?></th>
    					<td class='_data' colspan='2'>
    						<?php
    						$id    = $cnf_data['admin_dns']['id'];
    						$name  = $id;
    						$value = $cnf_data['admin_dns']['value'];
    						?>
    						<input type='text' id='<?php echo $id?>' name='<?php echo $id?>' class='vfield' value='<?php echo $value?>'/>
    					</td>
    				</tr>

    				<tr>
    					<th class='_label'><?php display_label($cnf_data['firewall_active'])?></th>
    					<td class='_data' colspan='2'>
    						<?php
    						$id    = $cnf_data['firewall_active']['id'];
    						$name  = $id;
    						$value = $cnf_data['firewall_active']['value'];
    						?>
    						<select id='<?php echo $id?>' name='<?php echo $name?>' class='vfield'>
    							<?php
    								foreach ($yes_no as $key => $yn_value)
    								{
    									echo "<option value='$key'".(($value == $key) ? " selected='selected'": '').">$yn_value</option>\n";
    								}
    							?>
    						</select>
    					</td>
    				</tr>

    				<tr>
    					<th colspan='3' class='t_nsh'><?php echo _('Main Interface')?></th>
    				</tr>

    				<tr>
    					<th rowspan='3' class='_label td_iface'><?php echo $cnf_data['interface']['value']?></th>
    					<th class='_label'><?php display_label($cnf_data['admin_ip'])?></th>
    					<td class='_data'>
    						<?php
    						$id    = $cnf_data['admin_ip']['id'];
    						$name  = $id;
    						$value = $cnf_data['admin_ip']['value'];
    						?>
    						<input type='text' id='<?php echo $id?>' name='<?php echo $id?>' class='vfield' value='<?php echo $value?>'/>
    						<input type='hidden' id='h_<?php echo $id?>' name='h_<?php echo $id?>' class='vfield' value='<?php echo $value?>'/>
    					</td>
    				</tr>

    				<tr>
    					<th class='_label'><?php display_label($cnf_data['admin_gateway'])?></th>
    					<td class='_data' colspan='2'>
    						<?php
    						$id    = $cnf_data['admin_gateway']['id'];
    						$name  = $id;
    						$value = $cnf_data['admin_gateway']['value'];
    						?>
    						<input type='text' id='<?php echo $id?>' name='<?php echo $id?>' class='vfield' value='<?php echo $value?>'/>
    					</td>
    				</tr>

    				<tr>
    					<th class='_label'><?php display_label($cnf_data['admin_netmask'])?></th>
    					<td class='_data' colspan='2'>
    						<?php
    						$id    = $cnf_data['admin_netmask']['id'];
    						$name  = $id;
    						$value = $cnf_data['admin_netmask']['value'];
    						?>
    						<input type='text' id='<?php echo $id?>' name='<?php echo $id?>' class='vfield' value='<?php echo $value?>'/>
    					</td>
    				</tr>

    				<tr>
    					<td id='buttonpad' colspan='3'>
    						<input type='button' name='apply_changes' id='apply_changes' value='<?php echo _('Apply Changes')?>'/>
    					</td>
    				</tr>
    			</table>
    		</form>
        </div>
	</div>


	<script type='text/javascript'>

		var config = {
			validation_type: 'complete', // single|complete
			errors:{
				display_errors: 'all', //  all | summary | field-errors
				display_in: 'nc_info'
			},
			form : {
				id  : 'f_nc',
				url : "data/sections/configuration/network/save_changes.php"
			},
			actions: {
				on_submit:{
					id: 'apply_changes',
					success: '<?php echo _('Apply Changes')?>',
					checking: '<?php echo _('Applying Changes')?>'
				}
			}
		};

		ajax_validator = new Ajax_validator(config);


		// Redefine submit_form function
		ajax_validator.submit_form = function (){

			if (ajax_validator.check_form() == true)
			{
				Network_cnf.save_cnf('f_nc');
			}
			else
			{
				if ($(".invalid").length >= 1)
				{
					$(".invalid").get(0).focus();
				}

				return false;
			}
		}

		$('#apply_changes').click(function() {
			ajax_validator.submit_form();
		});

		var cc_config = {
			elem : {
				form_id: 'f_nc',
				submit_id : 'apply_changes'
			},
			changes : {
				display_in: 'nc_info',
				message: "<?php echo _('You have made changes, click <i>Apply Changes</i> to save')?>"
			}
		};


		change_control = new Change_control(cc_config);
		change_control.change_control();

		$(window).bind('unload', before_unload);

		//Check System Status (Reconfig in progress)
		Configuration.check_status();

		//DNS Server Information
		Js_tooltip.show('#admin_dns', { content: '<?php echo _('You can type one unique IP Address or an IP list separated by commas: IP1,IP2,IP3 (3 DNS Server IPs maximum allowed)')?>'});

	</script>
	<?php
}
?>
