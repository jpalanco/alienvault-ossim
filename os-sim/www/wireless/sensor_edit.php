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

$location = base64_decode(GET('location'));
$sensor   = GET('sensor');
$model    = GET('model');
$serial   = GET('serial');
$mounting = GET('mounting');

ossim_valid($model, OSS_TEXT, OSS_NULLABLE, '#',  'illegal: model');
ossim_valid($serial, OSS_TEXT, OSS_NULLABLE, '#', 'illegal: serial');
ossim_valid($mounting, OSS_TEXT, OSS_NULLABLE,    'illegal: mounting location');
ossim_valid($location, OSS_ALPHA, OSS_PUNC,       'illegal: location');
ossim_valid($sensor, OSS_ALPHA, OSS_PUNC,         'illegal: sensor');

if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db();
$conn = $db->connect();


if ($location != ''  && $sensor != '' && GET('action') == 'update') 
{
	Wireless::add_locations_sensor($conn,$location,$sensor,$model,$serial,$mounting);
	?>
    <script type="text/javascript">
        parent.GB_close();
    </script>
	<?php		
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

	<form>
		<input type="hidden" name="location" value="<?php echo base64_encode($location)?>">
		<input type="hidden" name="sensor" value="<?php echo $sensor?>">
		<input type="hidden" name="action" value="update">
		
		<table id='w_form'>
		    <tr>  
    			<th><?php echo _("Model #")?></th>
    			<th><?php echo _("Serial #")?></th>
    			<th><?php echo _("Mounting Location")?></th>
		    </tr>
		    	
			<?php
			$locations = Wireless::get_locations($conn, $location);
			
			$i = 0;
			
			if (is_array($locations[0]['sensors']) && !empty($locations[0]['sensors']))
			{			
    			foreach ($locations[0]['sensors'] as $data)
    			{ 
    				if ($data['sensor'] != $sensor)
    				{ 
    					break;
    				}
    				?>
    				<tr>
    					<td valign='top'><input type='text' size='30' name='model' value="<?php echo $data["model"]?>"></td>
    					<td valign='top'><input type='text' size='30' name='serial' value="<?php echo $data["serial"]?>"></td>
    					<td valign='top'><textarea cols='50' rows='3' name="mounting"><?php echo $data["mounting_location"]?></textarea></td>
    				</tr>
    				<?php 
    			}
    		}
    		else
    		{
        		?>
        		<tr><td colspan="3"><?php echo _("No sensor found")?></td></tr>
        		<?php
    		}
			?>			
		</table>
		
		<div class='c_button'>
		    <input type="submit" value="<?php echo _('Save')?>"/>
		</div>
		
		<br>
	</form>

</body>

</html>

<?php $db->close(); ?>