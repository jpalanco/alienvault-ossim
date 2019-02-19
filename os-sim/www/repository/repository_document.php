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

require_once 'repository_common.php';


Session::logcheck("configuration-menu", "Osvdb");


$user        = $_SESSION["_user"];
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");
$go_back     = (GET('go_back')) ? 1 : 0;
$options     = (GET('options')) ? 1 : 0;

if (!ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("id_document"))) exit;


$db        = new ossim_db();
$conn      = $db->connect();

$document  = Repository::get_document($conn, $id_document);
$atch_list = Repository::get_attachments($conn, $id_document);
$rel_list  = Repository::get_relationships($conn, $id_document);

$text      = $document->get_text(FALSE);

if(!empty($text))
{
	$wiki = new Wikiparser();
	$text = $wiki->parse($text);
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	
	<?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                     'def_path' => TRUE),
            array('src' => 'tipTip.css',                        'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',                         'def_path' => TRUE),
            array('src' => '/fancybox/jquery.mousewheel-3.0.4.pack.js',     'def_path' => TRUE),
            array('src' => '/fancybox/jquery.fancybox-1.3.4.pack.js',       'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>
	
	
	<style type='text/css'>
		body { 
			margin: 0px;
		}
		.main_table {
			width: 98%;
			text-align: center;
			margin: 10px auto 5px auto;
			border: none;
		}
		
		#button_bar {
			width:200px;
			margin:10px auto;
			text-align:center;
		}
		
		#button_bar a, #button_bar a:hover{
			margin: 0 9px;
			text-decoration:none;
		}
		
	</style>
	
	<script>
		
		function do_action(op)
		{
			switch(op)
			{
				case 'edit':
					
					document.location.href='repository_editdocument.php?id_document=<?php echo $id_document ?>';
					
				break;
				
				case 'links':
				
					document.location.href="repository_links.php?go_back=1&id_document=<?php echo $id_document ?>";
					
				break;
				
				case 'attachs':

					document.location.href="repository_attachment.php?go_back=1&id_document=<?php echo $id_document ?>";
				
				break;
				
				case 'perms':
					
					document.location.href="change_user.php?go_back=1&id_document=<?php echo $id_document ?>";
					
				break;
				
				case 'delete':
					if (confirm("<?php echo _("Document with attachments will be deleted") . ". " . _("Are you sure") ?> ?")) 
					{
						document.location.href="repository_delete.php?id_document=<?php echo $id_document ?>";
					}
				
				break;
			
			
			}
			
			return false;
		
		}
	
		$(document).ready(function(){

			
			$(".view_attach").fancybox({
				'width'				: '85%',
				'height'			: '95%',
				'autoScale'			: false,
				'type'				: 'iframe'
			});	
			
			//TipTip
			$('.icon-help').tipTip();
			
			
		});
	</script>
</head>

<body>
<?php if ($options) 
{ 
?>

<div id='button_bar' class='thold'>

	<a href='javascript:;' onclick="do_action('edit');" class='icon-help' title='<?php echo _('Edit Document') ?>'>
		<img src='../pixmaps/pencil.png' border='0'/>
	</a>
	
	<a href='javascript:;' onclick="do_action('links');" class='icon-help' title='<?php echo _('Relationships') ?>'>
		<img src='images/linked2.gif' border=0 >
	</a>
	
	<a href='javascript:;' onclick="do_action('attachs');" class='icon-help' title='<?php echo _('Attachements') ?>'>	
		<img src='images/attach.gif' border=0/>
	</a>
	
	<a href='javascript:;' onclick="do_action('perms');" class='icon-help' title='<?php echo _('Permissions') ?>'>
		<img src='../pixmaps/group.png' border='0'/>
	</a>
	
	<a href='javascript:;' onclick="do_action('delete');" class='icon-help' title='<?php echo _('Delete Document') ?>'>
		<img src='../pixmaps/delete.gif' border='0' />
	</a>
</div>

<?php 
} 
?>

<table cellpadding='0' cellspacing='2' class="transparent main_table">

	<tr>
		<td class="nobborder">
			<table cellpadding='0' cellspacing='0' border='0' width='100%' style="border:none">
				<tr>
					<td class="nobborder" valign="top" width="250px" style="padding-right:10px">
						<table class='table_list'>
							<tr><th class="kdb"><?php echo _("Date")?></th></tr>
							<tr><td class="center" style="padding-left:5px"><?php echo $document->get_date() ?></td></tr>
							<tr><th class="kdb"><?php echo _("User")?></th></tr>
							<tr><td class="center" style="padding-left:5px"><?php echo $document->get_visibility() ?></td></tr>
							<tr><th class="kdb"><?php echo _("Keywords")?></th></tr>
							<tr>
							     <td class="center" style="padding-left:5px">
							     <?php 
							     if ($document->get_keywords() != '')
							     {
    							         echo $document->get_keywords();
							     }
							     else
							     {
							         echo "<span style='color:#696969;'>"._("No Keywords defined")."</span>";
							     } 
							     ?>
							     </td>
							</tr>
							<tr><th class="kdb"><?php echo _("Attachments")?></th></tr>
							<!-- Attachments -->
							<tr>
								<td class='nobborder center'>
									<table class="noborder" align="center">
										<?php
										if (count($atch_list) > 0)
										{
											foreach($atch_list as $f) 
											{
												$type     = ($f['type'] != "") ? $f['type'] : "unkformat";
												$img      = (file_exists("images/$type.gif")) ? "images/$type.gif" : "images/unkformat.gif";
												$filepath = "../uploads/$id_document/" . $f['id_document'] . "_" . $f['id'] . "." . $f['type'];
											?>
											<tr>
												<td align='center' class="nobborder"><img src="<?php echo $img?>"/></td>
												<td class="nobborder"><a href="view.php?id=<?php echo $f['id_document'] ?>_<?php echo $f['id'] ?>" class='view_attach' ><?php echo $f['name'] ?></a></td>
												<td class="nobborder"><a href="download.php?id=<?php echo $f['id_document'] ?>_<?php echo $f['id'] ?>"><img src="images/download.gif" border="0"></a></td>
											</tr>
											<?php
											}
										}
										else
											echo "<tr><td class='noborder center'><span style='color:#696969;'>"._("No attached files")."</span></td></tr>";
										
										?>
									</table>
								</td>
							</tr>
							<tr><th class="kdb"><?php echo _("Links")?></th></tr>
							<!-- Relationships -->
							<tr>
								<td class="nobborder">
									<table class="noborder" align="center">
										<?php
										if (count($rel_list) > 0)
										{
											foreach($rel_list as $rel) 
											{
												list($name, $url) = get_doc_info($conn, $rel);
												?>
												<tr>
                                                    <td class="nobborder" style='width:80px;font-weight:bold'>
                                                        <?php echo ($rel['type'] == "incident") ? "ticket" : $rel['type'] ?>
                                                    </td>
                                                    <td class="nobborder">
                                                        <a href="<?php echo $url ?>" target="main"><?php echo $name ?></a>
                                                    </td>
												</tr>
												<?php
											} 
										}
										else
											echo "<tr><td class='noborder center'><span style='color:#696969;'>"._("No related links")."</span></td></tr>";
									
										?>
									</table>
								</td>
							</tr>
						</table>
					</td>
					<td valign="top" class="noborder" style='border-left: solid 1px #CCCCCC; height:100%'>
						<table cellpadding='0' cellspacing='2' border='0' width='100%' height='100%' class="noborder">
							<tr>
								<td class="noborder left" style="padding-left:5px; font-size: 11px; vertical-align:top;">
									<div style='padding:5px;text-align:center;font-style:italic;font-weight:bold;'>
										<?php echo _('Displaying document without compiling.') ?>
									</div>
									
									<?php echo $text ?>
									
								</td>
							</tr>
                                <?php 
                                if ($go_back) 
                                {                                    
                                ?>
                                    <tr>
                                        <td class="noborder center" style="vertical-align:bottom;">
                                            <input class="small" type="button" value="<?php echo _("Go Back") ?>" onclick="history.back()" style="margin-bottom:10px;">
                                        </td>
                                    </tr>
                                <?php 
                                } 
                                ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php $db->close(); ?>
</body>
</html>
