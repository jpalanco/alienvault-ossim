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


require_once 'classes/Security.inc';
require_once 'classes/notification.inc';

require      ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");


/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs = new QueryState();

$msg_error  = null;
$show_form  = false;
	

if ( isset($_POST['send']) && !empty($_POST['send']) )
{
	$id   = POST('id');
	$name = POST('name');
    $url  = POST('url');
		
	ossim_valid($id, OSS_DIGIT,                                                    'illegal:' . _("Id"));
	ossim_valid($name, OSS_DIGIT, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE,              'illegal:' . _("Name"));
	ossim_valid($url,  OSS_ALPHA, OSS_DIGIT, OSS_URL, OSS_PUNC, '%', OSS_NULLABLE, 'illegal:' . _("Url"));
	
	if (ossim_error()) {
		die(ossim_error());
	}
	
	if ( $name != "" )
	{
		$icon = "";
		if (is_uploaded_file($_FILES['icon']['tmp_name']))
		{
			if (preg_match("/image\//", $_FILES["icon"]["type"])) 
			{
				$icon = bin2hex(file_get_contents($_FILES['icon']['tmp_name']));
				$sql  = "UPDATE reference_system SET ref_system_name=\"$name\",url=\"$url\",icon=unhex('$icon') WHERE ref_system_id=$id";
			} 
			else{
				die(ossim_error("Invalid icon file"));
			}
		} 
		else{
			$sql = "UPDATE reference_system SET ref_system_name=\"$name\",url=\"$url\" WHERE ref_system_id=$id";
		}
		
		$result_update = $qs->ExecuteOutputQueryNoCanned($sql, $db);
	}
}
else
{
	$show_form = true;
	$id        = GET('id');
	ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Id"));

	if (ossim_error()) {
		die(ossim_error());
	}
	
	
	$sql    = "SELECT * FROM reference_system WHERE ref_system_id=$id";
	$result = $qs->ExecuteOutputQuery($sql, $db);
	$myrow  = $result->baseFetchRow();
		
	if ( empty($myrow) ){
		$msg_error = _("Error to get reference type");
	}
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- <?php echo gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION; ?> -->
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<?php
	$archiveDisplay = (isset($_COOKIE['archive']) && $_COOKIE['archive'] == 1) ? "-- ARCHIVE" : "";
	echo ('<title>' . gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION . $archiveDisplay . '</title>');
	?>
	
	<script type="text/javascript">
		$(document).ready(function(){
			$('textarea').elastic();
		});
	</script>
	
	<style type='text/css'>
		
		input[type='text'], input[type='hidden'], select {
			width: 98%; 
			height: 18px;
		}
		
		input[type='text']{
			border: solid 1px #CCCCCC;
		}
			
		textarea {
			width: 97%; 
			height: 45px;
			border: solid 1px #CCCCCC;
		}
		
		
		#t_ref{
			margin: 10px auto;
			white-space: nowrap;
			width: 400px;
			background: transparent;
			border: solid 1px #CCCCCC;
		}	
		
	</style>
		
</head>

<body>

<?php
	
	if ( $show_form == false )
	{
		$config_nt = array(
			'content' => _("Reference updated successfully"),
			'options' => array (
				'type'          => 'nf_success',
				'cancel_button' => false
			),
			'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		
		?>
		<script type='text/javascript'>top.frames['main'].document.location.href='manage_references.php'</script>
		<?php
		exit();
	}
	else
	{	
	
		if (!empty($msg_error) )
		{
			$config_nt = array(
				'content' => $msg_error,
				'options' => array (
					'type'          => 'nf_success',
					'cancel_button' => false
				),
				'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
			); 
							
			$nt = new Notification('nt_1', $config_nt);
			$nt->show();
		
		}
		else
		{
			?>
			<form name="ref_form" method="post" enctype="multipart/form-data">
				<input type="hidden" name="id" value="<?php echo $id?>"/>
				
				<table id='t_ref'>
					<tr>
						<th><?php echo _("Name")?></th>
						<td class="nobborder"><input type="text" name="name" value="<?php echo $myrow[1]?>"/></td>
					</tr>
					
					<tr>
						<th><?php echo _("Icon")?></th>
						<td class="nobborder">
							<img style='margin-right: 10px;' src="manage_references_icon.php?id=<?php echo $myrow[0]?>"/>
							<input type="file" name="icon" size='35'/>
						</td>
					</tr>
					
					<tr>
						<th><?php echo _("URL")?></th>
						<td class="nobborder"><textarea name="url" rows="2" cols="40"><?php echo $myrow[3]?></textarea></td>
					</tr>
					
					<tr>
						<td colspan="2" class="center" style='padding: 10px;'>
							<input type="submit" name='send' id='send' value="<?php echo _("Update")?>"/>
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
?>
</body>
</html>
