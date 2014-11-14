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
Session::logcheck("environment-menu", "ReportsWireless");

require_once 'Wireless.inc';

$location = GET('location');
$desc     = GET('desc');
$action   = GET('action');
$sensor   = GET('sensor');
$model    = GET('model');
$serial   = GET('serial');
$mounting = GET('mounting');
$layer    = GET('layer');

ossim_valid($location, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal: location');
ossim_valid($desc, OSS_TEXT, OSS_NULLABLE,                            'illegal: desc');
ossim_valid($sensor, OSS_TEXT, OSS_NULLABLE, OSS_SCORE,               'illegal: sensor');
ossim_valid($model, OSS_TEXT, OSS_NULLABLE, OSS_SPACE, '#',           'illegal: model');
ossim_valid($serial, OSS_TEXT, OSS_NULLABLE, OSS_SPACE, '#',          'illegal: serial');
ossim_valid($mounting, OSS_TEXT, OSS_NULLABLE, OSS_SPACE,             'illegal: mounting');
ossim_valid($action, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,              'illegal: action');
ossim_valid($layer, OSS_DIGIT, OSS_NULLABLE,                          'illegal: layer');

if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db();
$conn = $db->connect();

if ($action == "add" && $location != "") 
{
	Wireless::add_location($conn,$location,$desc);
} 

if ($action == "del" && $location != "") 
{
	Wireless::del_location($conn,$location);
} 

if ($action == "add_sensor" && $location != "" && $sensor!= "") 
{
	Wireless::add_locations_sensor($conn,$location,$sensor,$model,$serial,$mounting);
} 

if ($action == "del_sensor" && $location != ""  && $sensor!= "") 
{
	Wireless::del_locations_sensor($conn,$location,$sensor);
} 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo _("OSSIM Framework");?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.watermarkinput.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript">
		var max = 0;
		
		$(document).ready(function () {
			<?php 
			if ($layer != "") 
			{ 
				?>
				showhide('#cell<?php echo $layer?>','#img<?php echo $layer?>')
				<?php 
			} 
			?>
			
			$("#location").Watermark('<?php echo html_entity_decode(_("Location"))?>', '#AAAAAA');
			$("#desc").Watermark('<?php echo html_entity_decode(_("Description"))?>', '#AAAAAA');
			
			for (var i=1; i<=max; i++) 
			{
				$("#model"+i).Watermark('<?php echo html_entity_decode(_("Model"))?>');
				$("#serial"+i).Watermark('<?php echo html_entity_decode(_("Serial"))?>');
				$("#mounting"+i).Watermark('<?php echo html_entity_decode(_("Mounting Location"))?>');
			}
		});

		function showhide(layer,img)
		{
			$(layer).toggle();
			
			if ($(img).attr('src').match(/plus/))
			{
				$(img).attr('src','../pixmaps/minus-small.png')
			}
			else
			{
				$(img).attr('src','../pixmaps/plus-small.png')
			}
		}
	</script>
	
	<style type='text/css'>
		input[type='text'] 
		{ 
		    height: 16px;
		}
		
		#t_data 
		{
		    margin: 30px auto;
		    width: 90%;
		    border: none;
		}
	</style>
	
</head>
<body>

<table id="t_data">
	<tr>
		<td class="noborder" style='text-align:left;'>
			<form>
				<input type="hidden" name="action" value="add"/>
			
				<table class="noborder">
					<tr>
						<td class="noborder"><input type="text" size="30" id="location" name="location"></td>
						<td class="noborder"><input type="text" size="60" id="desc" name="desc"></td>
						<td class="noborder"><input type="submit" value="<?php echo _("Add New Location"); ?>" class="small"></td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
	
	<tr>
		<td class="noborder">
			<table style='width:100%' class="noborder" cellspacing='1' cellpadding='0'>
				<tr>
					<th width="25%" ><?php echo _("Location")?></th>
					<th><?php echo _("Description")?></th>
					<?php  
					if (Session::am_i_admin()) 
					{ 
						?>
						<th><?php echo _("User")?></th>
						<?php 
					} 
					?>
					<th><?php echo _("Actions")?></th>
					<?php
					
					$locations                 = Wireless::get_locations($conn); 
					list($all_sensors,$_total) = Av_sensor::get_list($conn);
					$ossim_sensors = array();
					?>
				</tr>
				
				<?php
	
				foreach ($all_sensors as $_sid => $sen)
				{ 
					if ($sen['properties']['has_kismet'] == 1) 
					{
						$ossim_sensors[]=$sen; 
					}
				}

				$sensors_list = "";
				
				foreach ($ossim_sensors as $sensor) 
				{
					$sensors_list .= "<option value='".$sensor['name']."'>".$sensor['name']." [".$sensor['ip']."]";
				}

				$c=0;
				
				$colspan = (Session::am_i_admin()) ? 4 : 3;
				
				if (is_array($locations) && !empty($locations))
				{
					foreach ($locations as $data) 
					{
						$color = ($c % 2 == 0) ? 'odd' : 'even';
						$c++;
						?>
						<tr>
							<td class='<?php echo $color?>' style='text-align:left; padding-left: 3px;' valign='top'>
								<span style='margin-right: 3px;'>
									<a href="javascript:;" onclick="showhide('#cell<?php echo $c?>','#img<?php echo $c?>')"><img align='absmiddle' src='../pixmaps/plus-small.png' id='<?php echo 'img'.$c?>' border='0'/></a>
								</span>
								<?php echo $data["location"]?>
							</td>
							<td class='<?php echo $color?>' style='text-align:left;padding-left:10px'><?php echo $data['description']?></td>
							<?php
							if (Session::am_i_admin())
							{
								?>
								<td class='<?php echo $color?>'><?php echo $data["user"]?></td>
								<?php 
							}
							?>
						
							<td class='<?php echo $color?>'style='width: 20px;'>
								<a href='?action=del&location=<?php echo urlencode($data["location"])?>'><img src='../vulnmeter/images/delete.gif' border='0'/></a>
							</td>
						</tr>
						
						<tr>
							<td class='<?php echo $color?> noborder'  colspan='<?php echo $colspan?>' style='padding:10px 5px 10px 5px;display:none;' id='<?php echo 'cell'.$c?>'>
								<table class='transparent' width='100%'>
									<tr>
										<td colspan='7' class="noborder" style='padding: 5px 0px;'>
											<form>
												<input type='hidden' name='action'    value='add_sensor'>
												<input type='hidden' name='layer'     value='<?php echo $c?>'>
												<input type='hidden' name='location'  value='<?php echo $data["location"]?>'>
												<table class='noborder'>
													<tr>
														<td class='noborder'><select name='sensor'><?php echo $sensors_list?></select></td>
														<td class='noborder'><input type='text' size='15' name='model' id='<?php echo 'model'.$c?>'/></td>
														<td class='noborder'><input type='text' size='15' name='serial' id='<?php echo 'serial'.$c?>'/></td>
														<td class='noborder'><input type='text' size='25' name='mounting' id='<?php echo 'mounting'.$c?>'/></td>
														<td class='noborder'><input type='submit' value='<?php echo _("Add Sensor")?>' class='small'></td>
													</tr>
												</table>
											</form>
										</td>
									</tr>
								
									<tr>
										<th style='white-space: nowrap;'><?php echo _("Sensor")?></th>
										<th style='white-space: nowrap;'><?php echo _("IP Addr")?></th>
										<th style='white-space: nowrap;'><?php echo _("Mac Address")?></th>
										<th style='white-space: nowrap;'><?php echo _("Model #")?></th>
										<th style='white-space: nowrap;'><?php echo _("Serial #")?></th>
										<th style='white-space: nowrap;'><?php echo _("Mounting Location")?></th>
										<th style='white-space: nowrap; width: 20px;'><?php echo _("Actions")?></th>
									</tr>
									<?php
									
									$i=0;	
									
									if (is_array($data["sensors"]) && !empty($data["sensors"]))
									{
										foreach ($data["sensors"] as $sensors) 
										{
											$color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
											?>
											<tr <?php echo $color?>>
												<td><?php echo $sensors["sensor"]?></td>
												<td><?php echo $sensors["ip"]?></td>
												<td><?php echo preg_replace("/(..)(..)(..)(..)(..)(..)/","\\1:\\2:\\3:\\4:\\5:\\6",$sensors["mac"])?></td>
												<td><?php echo $sensors["model"]?></td>
												<td><?php echo $sensors["serial"]?></td>
												<td style='text-align:left;padding-left:10px'><?php echo $sensors["mounting_location"]?></td>
												<td style='width: 20px;'>
													<a href='?action=del_sensor&location=<?php echo urlencode($data["location"])."&sensor=".urlencode($sensors["sensor"])."&layer=$c"?>'>
														<img src='../vulnmeter/images/delete.gif' border='0'/>
													</a>
												</td>
											</tr>
											<?php
										}
									}
									else
									{
										?>
										<tr><td colspan='7' style='padding: 10px 0px; text-align: center; border:none;'><?php echo _("Sensors not found")?></td></tr>
										<?php
									}
									?>
								</table>
							</td>
						</tr>
						<?php
					}
				}
				else
				{
					?>
					<tr><td colspan='<?php echo $colspan?>' style='padding: 10px 0px; text-align: center; border:none;'><?php echo _("Locations not found")?></td></tr>
					<?php
				}
				?>
			</table>
		</td>
	</tr>
</table>

<script type='text/javascript'>max=<?php echo $c?>;</script>

<?php $db->close(); ?>

</body>
</html>