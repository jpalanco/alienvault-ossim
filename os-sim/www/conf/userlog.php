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
Session::logcheck("configuration-menu", "ConfigurationUserActionLog");

$update = ( isset($_POST['update']) && POST('update') != '' ) ? true : false;

/* connect to db */
$db       = new ossim_db();
$conn     = $db->connect();
$status   = true;

$ua_items      = array();
$ua_logged     = array();
$ua_not_logged = array();
	
if ($log_conf_list = Log_config::get_list($conn, "")) 
{
	foreach($log_conf_list as $log_conf) 
	{
		$descr           = preg_replace('|%.*?%|', " ", $log_conf->get_descr());
		$descr           = ( trim($descr) == '' ) ? _("Various") : $descr;
		$code    		 = $log_conf->get_code();
		$ua_items[$code] = array("descr" => $descr, "log" => $log_conf->get_log()); 
		
		if ( $log_conf->get_log() )
			$ua_logged[$code] = $descr;
		else
			$ua_not_logged[$code] = $descr;
	}
}

//Update User Activity items
if ($update == TRUE) 
{
	$ua_logged     = array();
	$ua_not_logged = array();
	$select_ua     = ( is_array($_POST['select_ua']) && count($_POST['select_ua']) > 0 ) ? $_POST['select_ua'] : array(); 
	
	foreach ($ua_items as $k => $v)
	{
		if ( in_array($k, $select_ua) ) 
		{
			$res = Log_config::update_log($conn, $k, '1');
			$ua_logged[$k] = $v['descr'];
		}
		else
		{
			$res = Log_config::update_log($conn, $k, '0');
			$ua_not_logged[$k] = $v['descr'];
		}
	}
	
	if ($res !== TRUE)
	{ 
		$status = FALSE;
	}
}

asort($ua_logged, SORT_STRING);
asort($ua_not_logged, SORT_STRING);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("User logging Configuration"); ?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>	
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css"/>	

	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>	

	
	<style type='text/css'>
		
		#ua_cont{
			width: 80%;
			margin: auto;
			text-align: center;
			position:relative;
		}
		
		#av_info{
			width: 100%;
			margin: auto;
			padding: 10px 0px;
			text-align: center;
			height: 50px;
		}
		
		#select_ua{
			display:none;
		}
						
		.ua_table{
			width: 100%;
			margin: auto;
			text-align: center;
			border: none !important;
			background: none !important;
		}	
			
		.ua_title { 
			height: 20px;
			color:#222222;
			font-size: 12px;
			font-weight: bold;
			border: solid 1px #000000;
			border-bottom: none;
		}
								
		/*Multiselect loading styles*/        
		#ms_body {
			height: 347px;
			width: 100%;
		}
        
        #load_ms {
            margin:auto; 
            padding-top: 105px; 
            text-align:center;
        }
		
		.multiselect {
			width: 100%;
			height: 350px;
			text-align: left;
		}
		
		.ui-multiselect ul.selected li {
			white-space: normal !important;
		}
		
	</style>
	
	<script type='text/javascript'>
	
		$(document).ready(function() {

			$(".multiselect").multiselect({
								searchDelay: 500,
								nodeComparator: function (node1,node2){ return 1 },
								dividerLocation: 0.5
							});
			
			<?php 
			if ( $update == true) 
			{ 
				?>	
				setTimeout('$("#nt_1").fadeOut(4000);', 25000);	
				<?php 
				} 
			?>	
		});
	</script>
</head>

<body>

    <?php 
    //Local menu		      
    include_once '../local_menu.php';
    ?>
	
	<div id='ua_cont'>
		
		<div id='av_info'>
			<?php
			if ( $update == true ) 
			{
				if ( $status == true )
				{
					$config_nt = array(
						'content' => _("User activity successfully updated"),
						'options' => array (
							'type'          => 'nf_success',
							'cancel_button' => false
						),
						'style'   => 'width: 80%; margin: auto; text-align: left;'
					); 
				}
				else
				{
					$config_nt = array(
						'content' => _("Error! Update failed"),
						'options' => array (
							'type'          => 'nf_error',
							'cancel_button' => false
						),
						'style'   => 'width: 80%; margin: auto; text-align: left;'
					); 
				}
							
				$nt = new Notification('nt_1', $config_nt);
				$nt->show();
			}
			?>
		</div>
	
	
		<form method="POST" name='form_ua' id='form_ua' action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" />
			<table class='ua_table' cellspacing='0' cellpadding='0'>
				<tr>
					<td class='sec_title'>
						<div style='float: left; width: 48%'><?php echo _("Actions logged")?></div>
						<div style='float: right; width: 48%'><?php echo ("Actions not logged")?></div>
					</td>
				</tr>
				<tr>
					<td class='center'>
						<div id='ms_body'>
							<div id='load_ms'><img src='../pixmaps/loading.gif'/></div>
							<select id='select_ua' class='multiselect' multiple='multiple' name='select_ua[]'>
							<?php
								foreach($ua_logged as $k => $v)
								{
									$text = (strlen($v) > 63 ) ? substr($v, 0, 63)." [...]" : $v;
									echo "<option value='$k' title='$v' selected='selected'>$text</option>";
								}
						
								foreach($ua_not_logged as $k => $v)
								{
									$text = (strlen($v) > 63 ) ? substr($v, 0, 63)." [...]" : $v;
									echo "<option value='$k' title='$v'>$text</option>";
								}
							?>
							</select>
						</div>
					</td>
				</tr>
				<tr>
					<td style='padding: 8px 0px 6px 0px;' class='transparent noborder'>
						<span>(*) <?php echo _('Drag & Drop the item you want to add/remove or use [+] and [-] links')?></span>
					</td>
				</tr>
				
				<tr>
					<td style='padding:20px 0px;' class='noborder center'>
						<input type='submit' id='update' name='update' value='<?php echo _('Update Configuration')?>'/>
					</td>
				</tr>
			</table>
		</form>
	</div>
</body>
</html>

<?php $db->close(); ?>