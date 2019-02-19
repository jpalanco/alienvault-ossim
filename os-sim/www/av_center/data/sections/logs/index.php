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
require_once (dirname(__FILE__) . '/../../../config.inc');


$system_id = POST('system_id');
ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));

//Profiles enabled
$profiles = (empty($_POST['profiles'])) ? array() : array_flip(explode(',', $_POST['profiles']));


unset($profiles['database']);

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
*****************  Logs  *****************
***************************************************************/

$log_files = Av_center::get_available_logs();

$t_header = array(
		'sensor'    => array('id' => 'h_sensor',    'title' => _('AlienVault Sensor')),
		'server'    => array('id' => 'h_server',    'title' => _('AlienVault Server')),
		'framework' => array('id' => 'h_framework', 'title' => _('AlienVault Web'))
);

?>
<div id='log_container'>

    <div class='sec_title'><?php echo _('System Logs')?></div>
	<table id='t_logs'>
		<thead>			
			<tr>
				<td class='subheader_e'></td>
				
				<td class='subheader sh_selected' id='h_system'><?php echo _('System')?></td>
				<?php 
				$cont = 1;
				foreach ($profiles as $k_profiles => $d_profiles)
				{
					$cont++;
					$id    = $t_header[$k_profiles]['id'];
					$title = $t_header[$k_profiles]['title'];
					?>
					<td class='subheader' id='<?php echo $id?>'><?php echo $title?></td>
					<?php
				}
												
				if ($cont < 4)
				{
					for ($i = $cont; $i < 4; $i++)
					{
						?>
						<td class='subheader_nd'>&nbsp;</td>
						<?php
					}
				}
				?>
			</tr>
		</thead>
		
		<tbody>
			<?php
			foreach ($log_files as $key => $l_data)
			{
				if ($l_data['section'] == 'system' || array_key_exists($l_data['section'], $profiles))
				{
					$l_class   = "log_item sec_".strtolower($l_data['section']);
					$l_visible = ($l_data['section'] != 'system') ? 'display:none;' : '';
					?>
					
					<tr class='<?php echo $l_class?>' style='<?php echo $l_visible?>'>
						<td class='log_name' id='<?php echo $key ?>'><?php echo $log_files[$key]['name']?></td>
						<td class='log_desc' colspan='5'><?php echo $log_files[$key]['desc']?></td>
					</tr>
					<?php;
				}
			}
			?>
		</tbody>	
	</table>		
	
	<div id='container_li'></div>
	
	<div id='log_viewer'>
			
		<div class='header_viewer'>
			<div id='l_viewer'>
				<ul>
					<li id='li_search'><img class='disabled' src='<?php echo AVC_PIXMAPS_DIR.'/search.png'?>' title='<?php echo _('Search')?>'/></li>
					<li id='li_find_previous'><img class='disabled' src='<?php echo AVC_PIXMAPS_DIR.'/find_previous.png'?>' title='<?php echo _('Find Previous')?>'/></li>
					<li id='li_find_next'><img class='disabled' src='<?php echo AVC_PIXMAPS_DIR.'/find_next.png'?>' title='<?php echo _('Find Next')?>'/></li>
					<li id='li_clear_search'><img class='disabled' src='<?php echo AVC_PIXMAPS_DIR.'/clear_search.png'?>' title='<?php echo _('Clear Search')?>'/></li>
				</ul>
			</div>
			
			<div id='r_viewer'>
				<label for='num_rows' class='normal'><?php echo _('Lines per file')?>:</label>
				<select id='num_rows' name='num_rows'>
					<?php
					$num_rows = array(50, 100, 1000, 5000);
					foreach ($num_rows as $key => $value)
					{
						echo "<option value='$value'>$value</option>\n";
					}
					?>
				</select>
			</div>
		</div>
		<div id='code_mirror'>
			<textarea id='code'></textarea>
		</div>
	</div>
</div>
	
	
<script type='text/javascript'>
	
	Log.create_viewer('code');
	
	$('.subheader').click(function() { 
		var sec =  $(this).attr('id').replace('h_', '');
		
		$('.subheader').removeClass('sh_selected');
		$(this).addClass('sh_selected');
		
		$('.log_item').hide();
		$('.sec_'+sec).fadeIn(1000);
	});
					
	$('.log_item').click(function() { 
		var id =  $(this).find('.log_name').attr('id');
		$('#nt_1').remove();
		Log.view_logs(id);
	});
	
	$('#num_rows').change(function() { 
		if ( $('.log_name').hasClass('bold') )
		{
			var id = $('.bold').attr('id');
			Log.view_logs(id);
		}
	});
	
</script>	
