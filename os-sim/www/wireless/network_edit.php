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
$ssid   = base64_decode(GET('ssid'));
$sensor = GET('sensor');
$desc   = GET('desc');
$type   = GET('type');
$notes  = GET('notes');

ossim_valid($ssid, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC_EXT, '\<\>', 'illegal: ssid');
ossim_valid($sensor, OSS_IP_ADDR,                                 'illegal: sensor');
ossim_valid($desc, OSS_TEXT, OSS_NULLABLE, 						  'illegal: desc');
ossim_valid($type, OSS_TEXT, OSS_NULLABLE, 						  'illegal: type');
ossim_valid($notes, OSS_TEXT, OSS_NULLABLE, 					  'illegal: notes');

if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db();
$conn = $db->connect();

# sensor list with perm
if (!validate_sensor_perms($conn,$sensor,", sensor_properties WHERE sensor.id=sensor_properties.sensor_id AND sensor_properties.has_kismet=1")) 
{
    echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
        
    $db->close();
    exit();
}


if ($type != '' && $ssid != '') 
{
	Wireless::update_network_data($conn,$ssid,$sensor,$desc,$type,$notes);
	
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
	<input type="hidden" name="ssid" value="<?php echo base64_encode($ssid)?>">
	<input type="hidden" name="sensor" value="<?php echo $sensor?>">
			
	<table id='w_form'>
		<th><?php echo _("Description")?></th>
		<th><?php echo _("Type")?></th>
		<th><?php echo _("Notes")?></th>
		
		<?php $data = Wireless::get_network_data($conn, $ssid, $sensor);?>

		<tr>
			<td valign='top'>
				<textarea cols='40' rows='3' name="desc"><?php echo $data["description"]?></textarea>
			</td>
			<td valign='top'>
				<select name="type">
					<option value="Un-Trusted"<?php if ($data["type"]=="Un-Trusted") echo " selected='selected'" ?>>Un-Trusted</option>
					<option value="Trusted"<?php if ($data["type"]=="Trusted") echo " selected='selected'" ?>>Trusted</option>
				</select>
			</td>
			<td valign='top'>
				<textarea cols='50' rows='10' name="notes"><?php echo $data["notes"]?></textarea>
			</td>
		</tr>		
	</table>
	
	<div class='c_button'>
	    <input type="submit" value="<?php echo _('Save')?>"/>
	</div>
	
	<br>
</form>
</body>
</html>
<?php $db->close(); ?>
