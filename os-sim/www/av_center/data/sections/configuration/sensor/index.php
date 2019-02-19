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


/**************************************************************
*****************  Sensor Configuraton Data  *****************
***************************************************************/

$db           = new ossim_db();
$conn         = $db->connect();

$sensor_cnf   = Av_center::get_sensor_configuration($system_id);

if ($sensor_cnf['status'] == 'error')
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
	session_start();
	$cnf_data               = $sensor_cnf['data'];
	$_SESSION['sensor_cnf'] = $cnf_data;
	session_write_close();

    /*
	echo "<pre>";
		print_r($cnf_data);
	echo "</pre>";
	*/

	?>
	<div id='sc_notification'>
		<div id='sc_info' class='c_info'></div>
	</div>

	<div id='sc_container'>

		<div class='cnf_header'>
			<div class='cnf_h_title'><?php echo _('Sensor Configuration')?></div>
		</div>

		<div id='sc_menu'>
			<ul id='sc_ul'>
				<li><a id='output'><?php echo _('Output')?></a></li>
				<li><a id='detection'><?php echo _('Detection')?></a></li>
				<li><a id='collection'><?php echo _('Collection')?></a></li>
			</ul>
		</div>


		<form id='f_sc' name='f_sc'>

			<div id='sc_body'>

				<div id='sc_content'>

					<div class='sc_scontent' id='c_output'>
						<?php include ('output.php'); ?>
					</div>

					<div class='sc_scontent' id='c_detection'>
						<?php include ('detection.php'); ?>
					</div>

					<div class='sc_scontent' id='c_collection'>
						<?php include ('collection.php'); ?>
					</div>

				</div>

				<div id='sc_action'>
					<input type='button' name='apply_changes' id='apply_changes' value='<?php echo _('Apply Changes')?>'/>
				</div>
			</div>
		</form>
	</div>


	<script type='text/javascript'>

		//Activing link
		$('#sc_ul li a').click(function() {
			var id = $(this).attr('id');

			$('#sc_ul li a').removeClass('active');
			$('#'+id).addClass('active');

			$('.sc_scontent').hide();
			$('#c_'+id).show();
		});


		//Activing initial section
		<?php
		if ($sw['output'] == TRUE)
		{
			$initial_section = 'output';
		}
		elseif ($sw['detection'] == TRUE)
		{
			$initial_section = 'detection';
		}
		elseif ($sw['collection'] == TRUE)
		{
			$initial_section = 'collection';
		}
		else
		{
			$initial_section = 'output';
		}
		?>

		$('#<?php echo $initial_section ?>').trigger('click');


		var config = {
			validation_type: 'complete', // single|complete
			errors:{
				display_errors: 'all', //  all | summary | field-errors
				display_in: 'sc_info'
			},
			form : {
				id  : 'f_sc',
				url : "data/sections/configuration/sensor/save_changes.php"
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
				Sensor_cnf.save_cnf('f_sc');
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
				form_id: 'f_sc',
				submit_id : 'apply_changes',
				ignored: 'new_cidr, new_server, old_server, server_priority, admin_ip'
			},
			changes : {
				display_in: 'sc_info',
				message: "<?php echo _('You have made changes, click <i>Apply Changes</i> to save')?>"
			}
		};



		var change_control = new Change_control(cc_config);
			change_control.change_control();

		$(window).bind('unload', before_unload);


		//Check System Status (Reconfig in progress)
		Configuration.check_status();
	</script>

	<?php
}

$db->close();
?>
