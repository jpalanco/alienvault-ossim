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


require_once('av_init.php');


$conf = $GLOBALS["CONF"];

Session::logcheck("dashboard-menu", "BusinessProcesses");

if (!Session::menu_perms("dashboard-menu", "BusinessProcessesEdit") ) 
{
	echo ossim_error(_("You don't have permissions to see this page"));
	exit();
}



$name = POST('name');

ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, ".,%", 'illegal:'._("name"));

if (ossim_error()) 
{
	die(ossim_error());
}



$name 		     = str_replace("..","",$name);
$uploaded_icon   = false;
$allowed_formats = array(IMAGETYPE_JPEG => 1, IMAGETYPE_GIF => 1, IMAGETYPE_PNG => 1);
$filename        = '';
$msg             = '';

if (is_uploaded_file($_FILES['fichero']['tmp_name'])) 
{
	if ( $allowed_formats[exif_imagetype ($_FILES['fichero']['tmp_name'])] == 1 )
	{
		$size = getimagesize($_FILES['fichero']['tmp_name']);
        if ($size[0] < 400 && $size[1] < 400)
		{
			$uploaded_icon = true;
			$filename      = "pixmaps/uploaded/" . $name . ".jpg";
			move_uploaded_file($_FILES['fichero']['tmp_name'], $filename);
			$msg           = _("Icon uploaded successfully");
		}	 
		else
        {
            $msg = _("The file uploaded is too big (Max image size 400x400 px");
        }
    }
	else
    {
        $msg = _("The image format should be JPG, GIF or PNG");
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	

	
	<script>
	function display_icons(){
	
		var option = $('#icon_browser').val();
		
		if (option == 1){
			$('#default_icon').show();
			$('#flag_icon').hide();
			$('#own_icon').hide();
		} else if (option == 2){
			$('#default_icon').hide();
			$('#flag_icon').show();
			$('#own_icon').hide();
		
		} else if (option == 3){
			$('#default_icon').hide();
			$('#flag_icon').hide();
			$('#own_icon').show();
		
		} else{
			$('#default_icon').show();
			$('#flag_icon').show();
			$('#own_icon').show();
		
		}
	
	}
	
	function upload(flag){
		
		if (flag){
			$('#icon_list').hide();
			$('#upload_form').show();
			$('#GB_window', parent.document).css({'height':'195px', 'width':'500px'});
			$('#GB_frame', parent.document).css({'height':'160px', 'width':'500px'});
		} else{
			$('#icon_list').show();
			$('#upload_form').hide();
			$('#GB_window', parent.document).css({'height':'435px', 'width':'500px'});
			$('#GB_frame', parent.document).css({'height':'400px', 'width':'500px'});
		}
	
	}
	
	$(document).ready(function() 
	{

		$(".iconclick").click(function(e)
		{
				
			var icon = $(this).attr("src");
			
			var params = new Array();
			
			params['icon'] = icon;
						
			parent.GB_hide(params);
			
		});
		
		<?php 
		if (!empty($name)) 
		{ 
		?>
            var params = new Array();
            
            <?php
            if (!$uploaded_icon)
            { 
            ?>   
                params['error'] = "<?php echo $msg ?>";
                
            <?php
            }
            ?>

            params['icon'] = "<?php echo $filename ?>";
            
            parent.GB_hide(params);
			
		<?php 
		} 
		?>
	
	
	});
	
	</script>
	
	<style>
	
		table, th{
			border-radius:0;
		}
	</style>

</head>
<body class="transparent" style="background-color:white">
<div id='icon_list' style='padding:10px 5px;'>
	<div style='width:100%;margin:0 auto;padding-bottom:25px;'>
		<div style='float:left;padding-left:5px;'>
			<a href='javascript:;'onclick='upload(true);'><?php echo _('Upload your own icon') ?></a>
		</div>
		
		<div style='float:right;padding-right:5px;'>
			<span><?php echo _('Browse')?>:</span>
			<select id='icon_browser' onchange="display_icons();">
				<option value="0"><?php echo _("All")?></option>
				<option value="1"><?php echo _("Default Icons")?></option>
				<option value="2"><?php echo _("Country Flags")?></option>
				<option value="3"><?php echo _("Own Uploaded")?></option>
			</select>
		</div>
	</div>
	<br>
<?php
		
		
		/***************************************************************/
		
		
		$preview = "<div id='default_icon'>";
		$col     = 10;
		$uploaded_dir  = "pixmaps/standard/";
		
		$icons = explode("\n",`ls -1 '$uploaded_dir'`);
		
		$preview .= "<table align='center' class='transparent' width='100%'>";
		
		$fil = 0;
		$flag_end = 0;

		foreach ($icons as $ico)
		{
			if (!$ico)
            {
                continue;
            }

            if (is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico)) 
            {
                continue;
            }
            
			if ($fil%$col == 0)
			{
				if ($flag_end)
				{
					$preview .= "</tr>";
					$flag_end = 0;
				}
				else
				{
					$flag_end = 1;
				}
				
				$preview .= "<tr>";		
			}
			
			$preview .= "<td class='nobborder' style='text-align:center;'><a href='javascript:;'><img class='iconclick' src='$uploaded_dir/$ico' height='25' /></a></td>";
			
			$fil++;
		}
		
		$preview .= "</table>";
		
		$preview .= "</div>";
		
		
				
		
		/***************************************************************/
		
		
		
		$uploaded_dir  = "pixmaps/flags/";

		$preview .= "<div id='flag_icon'>";

		$icons = explode("\n",`ls -1 '$uploaded_dir'`);
		
		$preview .= "<table align='center' class='transparent' width='100%'>";
		
		$fil = 0;
		$flag_end = 0;

		foreach ($icons as $ico)
		{
            if (!$ico)
            {
                continue;
            }
            
            if (is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico)) 
            {
                continue;
            }
				
			if ($fil%$col == 0)
			{
				if ($flag_end)
				{
					$preview .= "</tr>";
					$flag_end = 0;
				}
				else
				{
					$flag_end = 1;
				}
				$preview .= "<tr>";		
			}
			
			$preview .= "<td class='nobborder' style='text-align:center;'><a href='javascript:;'><img class='iconclick' src='$uploaded_dir/$ico' height='15' /></a></td>";
			
			$fil++;
		}
		
		$preview .= "</table>";
		
		$preview .= "</div>";
		
		
		
	
	/***************************************************************/
		
		
			
		$uploaded_dir  = "pixmaps/uploaded/";

		$preview .= "<div id='own_icon'>";

		$icons = explode("\n",`ls -1 '$uploaded_dir'`);
		
		$preview .= "<table align='center' class='transparent' width='100%'>";
		
		$fil = 0;
		$flag_end = 0;

		foreach ($icons as $ico)
		{
            if (!$ico)
            {
                continue;
            }

			if (is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico)) 
			{
				continue;
			}
			
			if ($fil%$col == 0)
			{
				if ($flag_end)
				{
					$preview .= "</tr>";
					$flag_end = 0;
				}
				else
				{
					$flag_end = 1;
				}
				
				$preview .= "<tr>";		
			}
			
			$preview .= "<td class='nobborder' style='text-align:center;'><a href='javascript:;'><img class='iconclick' src='$uploaded_dir/$ico' height='20' /></a></td>";
			
			$fil++;
		}
		
		$preview .= "</table>";
		
		$preview .= "</div><br>";
		
		echo $preview;
?>

</div>
<div id='upload_form' style='display:none;padding:15px 5px 5px 5px;'>
	<form action="indicator_icon.php" method='post' enctype='multipart/form-data'>
		<table id='rm_up_icon' align='center' width='420px'>
			<tr>
				<th><?php echo _("Name Icon")?>:</th>
				<td  class='left'>
					<div style='padding-left:10px;'>
						<input type='text' class='ne1' name='name'/>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php echo _("Upload icon file")?>:</th>
				<td class='left'>
					<div style='padding-left:10px;'>
						<input type='file' class='ne1' size='27' name='fichero'/>
						<input type='hidden' value="<?php echo $map ?>" name='map'>
					</div>
				</td>
			</tr>
			<tr>
				<td class='cont_submit noborder' colspan='2'>
					<input type='submit' value="<?php echo  _("Upload") ?>" class="small"/>
				</td>
			</tr>
		</table>

		<div style='padding-top:15px;margin:0 auto;text-align:center;width:100%;font-size:12px;'>
			<span onclick='upload(false);'>
				<a href='javascript:;'><?php echo _('Or go back and select an existing icon') ?></a>
			</span>
		</div>
	</form>
</div>
</body>

</html>

