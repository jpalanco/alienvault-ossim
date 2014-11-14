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


ob_implicit_flush();
require_once 'av_init.php';
Session::logcheck("configuration-menu", "ToolsBackup");

$conf = $GLOBALS["CONF"];

//$data_dir = $conf->get_conf("data_dir");
$data_dir     = "/usr/share/ossim";
$backup_dir   = $conf->get_conf("backup_dir");
$version      = $conf->get_conf("ossim_server_version", FALSE);


//$backup_dir = "/root/pruebas_backup";
$run       = intval(GET("run"));
$perform   = POST("perform");
$nomerge   = (POST("nomerge") != "") ? POST("nomerge") : "merge";
$filter_by = (POST('user') != "") ? POST('user') : (POST('entity') != "" ? POST('entity') : "");
$message   = "";

ossim_valid($perform, "insert", "delete", OSS_NULLABLE,    'illegal:' . _("perform"));
ossim_valid($nomerge, OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _("nomerge"));
ossim_valid($filter_by, OSS_NULLABLE, OSS_DIGIT, OSS_USER, 'illegal:' . _("filter_by"));

if (is_array(POST("insert"))) 
{
	foreach (POST("insert") as $insert_date) {
		ossim_valid($insert_date, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("insert date"));
	}
}

if (is_array(POST("delete"))) 
{
	foreach (POST("delete") as $delete_date) {
		ossim_valid($delete_date, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("delete date"));
	}
}

if (ossim_error()) {
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('Backup')?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<style type="text/css">
		.tag_cloud { padding: 3px; text-decoration: none; }
		.tag_cloud:link  { color: #17457C; }
		.tag_cloud:visited { color: #17457C; }
		.tag_cloud:hover { color: #ffffff; background: #17457C; }
		.tag_cloud:active { color: #ffffff; background: #ACFC65; }
		a { font-size:10px; }
	</style>
</head>

<body>
	<div id="loading" style="position:absolute;top:5px;left:5px">
		<?php
		if ($perform == "insert" && is_array(POST("insert"))) 
		{
			$message = Backup::Insert(POST("insert"),$filter_by,$nomerge);
			
			echo ($message==2) ? _("Insert action is running, please wait a few seconds.") : $message;
		} 
		elseif ($perform == "delete" && is_array(POST("delete"))) 
		{   
			$message = Backup::Delete(POST("delete"));
					
			echo ($message==2) ? _("Remove action is running, please wait a few seconds.") : $message;
		}
		?>
		<table class="noborder" style="background-color:white">
			<tr><td class="nobborder" id="restore_msg"><?php if ($run>0 || $message==2) { echo gettext("Current task in progress"); ?> <img src="../pixmaps/loading3.gif" border="0"> <?php } ?></td></tr>
		</table>
	</div>
	<?php
	// Get STATUS for all current backups. Void if none is running
	
    Backup::print_backup_process($message);

	?>
</body>
</html>
