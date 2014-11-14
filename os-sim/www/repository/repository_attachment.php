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

// DB connect
$db   = new ossim_db();
$conn = $db->connect();

// Get upload dir from ossim config file

$user         = $_SESSION["_user"];
$conf         = $GLOBALS["CONF"];
$uploads_dir  = $conf->get_conf("repository_upload_dir");
$id_document  = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
$go_back      = intval(REQUEST("go_back"));

$error        = FALSE;
$info_error   = NULL;

if (empty($_FILES) && empty($_POST) && empty($_GET))
{
	$msg  = "The server was unable to handle that much POST data (".$_SERVER['CONTENT_LENGTH']." bytes) due to its current configuration";
	echo ossim_error(_($msg));
	exit();
}
	
	
if (!ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("Document ID"))) 
{
	echo ossim_error(_("Document ID not allowed"));
	exit();
}

if (!is_dir ($uploads_dir)) 
{
	echo ossim_error(_("Upload directory does not exist")." <strong>$uploads_dir</strong><br>"._("Please, Check OSSIM configuration options."), AV_WARNING);
	exit();
}

//Delete a file
if (GET('id_delete') != "") 
{
	Repository::delete_attachment($conn, GET('id_delete') , $uploads_dir);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

	<?php
	
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',          'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'css');


    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',         'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'js');
        
    ?>
	
	<style type='text/css'>		
	
		#r_container
		{
    		margin-top: 10px;
		}
		
		.c_back_button
		{
    		top: 12px;
 		}
 		
	</style>
	
	<script type="text/javascript">
    	
    	
    	function go_back()
        {
            document.location.href="repository_document.php?id_document=<?php echo $id_document ?>&options=1";
            
            return false;
        }
        
		$(document).ready(function()
		{		
			$('.c_back_button').show();		
		});
				
	</script>
</head>

<body>
<?php

list($title, $doctext, $keywords) = Repository::get_document($conn, $id_document);

if (is_uploaded_file($_FILES['atchfile']['tmp_name'])) 
{
    // Correct format xxxxxxx.yyy
    if (preg_match("/\.(...?.?)$/", $_FILES['atchfile']['name'])) 
	{
        // Insert file row in DB
        $filename = Repository::attach($conn, $id_document, $_FILES['atchfile']['name']);
        
		if ($filename[0] == TRUE)
		{
			// Copy uploaded file to filesystem
			$updir  = $uploads_dir . "/" . $id_document;
			$upfile = $updir . "/" . $filename[1];
			
			if (!is_dir($updir)) 
				mkdir("$updir");
			
			copy($_FILES['atchfile']['tmp_name'], $upfile);
		}
		else
		{
			$error      = TRUE;
			$info_error = $filename[1];
		}
		
	}
    // Incorrect format, can't get file type without extension
    else 
	{
        $error        = TRUE;
		$info_error   = _("File type not allowed");
    }
}
else
{
	$info_error   = _("'No file was uploaded");
}


?>

<div id='r_container'>

    <?php 
    if ($go_back) 
    {
        ?>    
        <div class="c_back_button" style="padding-left:15px">         
            <input type="button" class="av_b_back" onclick="go_back();"/> 
        </div>     
        <?php
    }
    ?>
    
    <form name="repository_insert_form" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">
        <input type="hidden" name="id_document" value="<?php echo $id_document ?>"/>
        <input type="hidden" name="go_back"     value="<?php echo $go_back ?>"/>
    
        <table class="transparent" align="center">
    	
        	<tr>
        	   <td class="center nobborder headerpr">
        	       <?php echo _("Select a file to upload")?>
        	   </td>
            </tr>   	
        	
    		<?php
    		if ($error == TRUE) 
    		{ 
    			?>
    			<tr>
    			     <td class="nobborder">
        			     <?php
        			     echo ossim_error($info_error);
        			     ?>     
    			     </td>
    			 </tr>
    			<?php
    		} 
    		?> 		
    		<!-- Attachments -->
    		<tr>
    			<td class="center nobborder">
    				<table class="noborder" align="center">
    					<?php
    						$atch_list = Repository::get_attachments($conn, $id_document);
    						$db->close();
    						
    						foreach($atch_list as $f) 
    						{
    							$type     = ($f['type'] != "") ? $f['type'] : "unkformat";
    							$img      = (file_exists("images/$type.gif")) ? "images/$type.gif" : "images/unkformat.gif";
    							$filepath = "../uploads/$id_document/" . $f['id_document'] . "_" . $f['id'] . "." . $f['type'];
    							$del_url  = $_SERVER['SCRIPT_NAME'] . "?id_document=$id_document&id_delete=". $f['id'] ."&go_back=$go_back";
    							?>
    							<tr>
    								<td align='center' class="nobborder">
    								    <img src="<?php echo $img?>"/>
    								</td>
    								
    								<td class="nobborder">
    								    <a href="view.php?id=<?php echo $f['id_document'] ?>_<?php echo $f['id'] ?>" target="_blank">
    								        <?php echo $f['name'] ?>
    								    </a>
    								</td>
    								
    								<td class="nobborder">
    								    <a href="<?php echo $del_url ?>" title="<?php echo _("Delete") ?>">
    								        <img src="images/del.gif" border="0"/>
    								    </a>
    								</td>
    								
    								<td class="nobborder">
    								    <a href="download.php?id=<?php echo $id_document ?>_<?php echo $f['id'] ?>"  title="<?php echo _("Download") ?>">
    								        <img src="images/download.gif" border="0"/>
    								    </a>
    								</td>
    							</tr>
    						<?php
    						} ?>
    				</table>
    			</td>
    		</tr>
    		
    		<tr>
    			<td class="center nobborder">
    				<input type='file' name="atchfile" size="45" />
    				<input type="submit" name='upload' id='upload' value="<?php echo _("Upload")?>" onclick="$('#upload').attr('value', '<?php echo _("Uploading...")?>')"/>
    			</td>
    		</tr>
    		
    		<tr><td class="center nobborder">&nbsp;</td></tr>
    		
            <tr>
                <td class="center nobborder" style="padding:10px">
                  <input type="button" onclick="parent.GB_hide();document.location.href='index.php';" value="<?php echo _("Finish")?>">
                </td>
            </tr>    	
        </table>
    </form>    
</div>

</body>
</html>
