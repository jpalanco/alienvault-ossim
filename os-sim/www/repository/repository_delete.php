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


Session::logcheck("configuration-menu", "Osvdb");


$user        = $_SESSION["_user"];
$error       = false;
$id_document = GET('id_document');


ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("Id_document"));

if ( ossim_error() ) 
{
   $error_txt =  ossim_get_error();
   $error     = true;
}
else
{
	$db   = new ossim_db();
	$conn = $db->connect();
	
	Repository::delete($conn, $id_document);
	
	$db->close();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<style type='text/css'>
		.cont_delete {
			width: 80%;
			text-align:center;
			margin: 10px auto;
		}
		
		.ossim_error, .ossim_success { width: auto;}
		
		body { margin: 0px;}
	</style>
</head>

<body>
	<?php 
		if ( $error == true ) 
		{ 
			?>
			<div class='cont_delete'>
				<?php
				$config_nt = array(
					'content' => $error_txt,
					'options' => array (
						'type'          => 'nf_error',
						'cancel_button' => false
					),
					'style'   => 'width: 90%; margin: 20px auto; text-align: left;'
				); 
								
				$nt = new Notification('nt_1', $config_nt);
				$nt->show();
				?>
			</div>
			<?php 
		} 
		else 
		{ 
			?>
			<div class='cont_delete'>
				<?php
				$config_nt = array(
					'content' => _("Deleting Repository document id").": <strong>$id_document</strong><br/>"._("Document successfully deleted"),
					'options' => array (
						'type'          => 'nf_success',
						'cancel_button' => false
					),
					'style'   => 'width: 90%; margin: 20px auto; text-align: left;'
				); 
								
				$nt = new Notification('nt_1', $config_nt);
				$nt->show();
				?>
			</div>
			<?php 
		} 
		?>
				
		<div class='cont_delete'>
			<input type="button" value="<?php echo _("OK")?>" onclick="parent.GB_hide();"/>
		</div>
</body>
</html>
