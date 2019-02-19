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



$db   = new ossim_db();
$conn = $db->connect();

$user        = Session::get_session_user();
$error       = FALSE;
$id_document = (GET('id_document') != "") ? GET('id_document') : ((POST('id_document') != "") ? POST('id_document') : "");

ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("Id_document"));

if (ossim_error()) 
{
   $info_error[] =  ossim_get_error();
   $error        = true;
}

$vuser    = POST('user');
$ventity  = POST('entity');
$title    = POST('title');
$doctext  = POST('doctext');
$keywords = POST('keywords');

if (isset($title) || isset($doctext))  
{
	ossim_valid($vuser, 		OSS_USER, OSS_NULLABLE, 	'illegal:' . _("User"));
	ossim_valid($ventity, 		OSS_HEX, OSS_NULLABLE,		'illegal:' . _("Entity"));
	ossim_valid($title, 		OSS_TEXT,					'illegal:' . _("Tittle"));
	ossim_valid($keywords, 		OSS_TEXT, OSS_NULLABLE,    	'illegal:' . _("Keywords"));
	
	if (ossim_error())
	{
		$info_error[] = ossim_get_error();
		
		ossim_clean_error();
		
		$error = true;
	}
	
	if ($doctext == "") 
	{
		$info_error[] = _("Error in the 'text' field (missing required field)");
		$error        = true;
	}
	
	if ($error == false) 
	{
		$parser = new KDB_Parser();
		$parser->proccess_file($doctext, $id_document);
	
		$info_error = $parser->is_valid();
				
		if(count($info_error) > 0)
		{
			$error = true;
		}
	}
	
	
	if ($error == false) 
	{
		Repository::update($conn, $id_document, $title , $doctext , $keywords);
	}
}

$sintax           = new KDB_Sintax();

$labels_condition = $sintax->_labels_condition;
$labels_actions   = $sintax->_labels_actions;
$labels_operators = $sintax->_labels_operators;
$labels_variables = $sintax->_labels_variables;

$help_msgs        = array();



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<!-- JQuery -->
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	
	<!-- LightBox -->
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	
	<!-- markItUp!  -->
	<link rel="stylesheet" type="text/css" href="/ossim/js/markitup/skins/simple/style.css">
	<link rel="stylesheet" type="text/css" href="/ossim/js/markitup/sets/kdb/style.css">
	<script type="text/javascript" src="/ossim/js/markitup/jquery.markitup.js"></script>
	<script type="text/javascript" src="/ossim/js/markitup/sets/kdb/set.js.php"></script>
	
	

	<script type="text/javascript">
		var help = new Array;
		var exam = new Array;
		
		function finish_edit()
		{

    		if (typeof top.is_lightbox_loaded == 'function' && top.is_lightbox_loaded(window.name))
    		{
        		parent.GB_hide();
    		}
    		else
    		{
        		document.location.href='index.php';
    		}
    		
    		return false;
    		
		}
				
		$(document).ready(function(){
			
			$('#markItUp').markItUp(mySettings);
			
			$('.view_doc').on('click', function()
            {
            	var title = '<?php echo _('Full Reference') ?>';
            	
				GB_show(title, this.href, 600, 850);

				return false;
            });
			
			$('.odd, .even').on('click', function(){
			
				var index = $(this).data('id');
				
				$('.nf_info').text(help[index-1]);
			
			});
			
			$('.c_back_button').show();
			
		});
			
	</script>

	<style type='text/css'>
		
		select{
			width:140px;
		}
		
		.cont_delete {
			width: 80%;
			text-align:center;
			margin: 10px auto;
		}
		
		html, body { margin: 0px; padding: 0px; }
		
		
		.error_item {
			padding:2px 0px 0px 20px; 
			text-align:left;
		}
		
		.ossim_success {
			width: auto;			
		}
		
		.ossim_error {
			width: auto;
			padding: 10px 10px 10px 40px;
			font-size: 12px;
		}
						
		
		table { 
			margin: auto; 
			background: transparent;
			border:none !important;
		}
		
		#back_icon {
			position:absolute;
			top:15px;
			left:15px;
		}
		
		.instruction{
			padding: 7px 0 4px 0;
			text-align:center;
			font-weight:bold;
		}
		
		#instruction_table{
			width:100%;
			height:100%;
			border:1px solid #CCC !important;
		}
		
		.ins_cont{
			height:110px;
			overflow:auto;
			border:1px solid #CCC;
			text-align:left;
			width:75%;
			margin:0 auto;
		}
		
		.odd, .even{
			cursor:pointer;
			padding:2px;
		}
		
				
	</style>

</head>
<body>

<?php
if ( (isset($title) || isset($doctext)) && $error == false )
{
	?>
	<div style='width:60%;margin:30px auto;text-align:center;'>
		<?php
		$config_nt = array(
			'content' => _("Document successfully updated with id").": $id_document",
			'options' => array (
				'type'          => 'nf_success',
				'cancel_button' => false
			),
			'style'   => 'width: 90%; margin: 20px auto; text-align: center;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		?>
		
		<a href="javascript:;" class="button" onclick="finish_edit();"><?php echo _('Finish') ?></a>

	</div>
	<?php
	
	$db->close();
	die();
}

	
if (isset($title) || isset($doctext) || isset($keywords))
{
	$title       = Util::htmlentities($title);
	$text        = Util::htmlentities($doctext, ENT_NOQUOTES);
	$keywords    = Util::htmlentities($keywords);
}
elseif ($error == false)
{
	$document = Repository::get_document($conn, $id_document);
	$title    = $document->get_title();
	$text     = $document->get_text(FALSE);
	$text     = preg_replace("/<br>/", "\n", $text);
	$text     = preg_replace("/<b>|<\/b>/", "'''", $text);
	$text     = Util::htmlentities($text, ENT_NOQUOTES);
	$keywords = $document->get_keywords();
}

$back_button = ($_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : 'index.php';

$db->close();
?>

<div class="c_back_button">         
    <input type='button' class="av_b_back" onclick="document.location.href='<?php echo Util::htmlentities_url($back_button) ?>';return false;"/> 
</div> 


<div style='width:95%;margin:25px auto 10px auto;'>

	<?php
	
	if ( $error == true ) 
	{ 
		$error_msg = "<div class='error_item' style='padding-left: 5px;'>"._("The following errors occurred").":</div>
				  <div class='error_item'>".implode($info_error, "</div><div class='error_item'>")."</div>";
	?>
		<div style='width:60%;margin:0 auto;'>
			<?php
			$config_nt = array(
				'content' => $error_msg,
				'options' => array (
					'type'          => 'nf_error',
					'cancel_button' => true
				),
				'style'   => 'width: 90%; margin: 0px auto 25px auto; text-align: left;'
			); 
							
			$nt = new Notification('nt_1', $config_nt);
			$nt->show();
			?>
		</div>
	<?php
	}
	?>
	
	<form name="repository_insert_form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">
	<input type="hidden" name="id_document" value="<?php echo Util::htmlentities($id_document) ?>">
	<table width="100%" border="0" align="center">	
		<tr valign="top">		
			<td class='nobborder center' style='width:58%;'>
				<table style='border:none;' align="center" width='98%'>
					<tr>
						<th width="10%"><?php echo _("Title") ?></th>
						<td class='left nobborder'>
							<input type="text" name="title" value="<?php echo $title ?>" style="padding:2px 0;width:476px;"/>
						</td>
					</tr>
					
					<tr>
						<th id='style_body' valign="top" width="10%"><?php echo _("Text"); ?></th>
						<td style="text-align: left;padding-left:5px;" class='nobborder'>
							<textarea id="markItUp" name="doctext" style="width:468px;" ><?php echo $text ?></textarea>
						</td>
					</tr> 

					<tr>
						<th width="10%"><?php echo _("Keywords") ?></th>
						<td class="left nobborder">
							<textarea name="keywords" style="width:476px;" id='keywords'><?php echo $keywords ?></textarea>
						</td>
					</tr>
				</table>
			</td>
			
			<td class='nobborder center' style='width:42%;'>
				
				<table id='instruction_table'>
					<tr>
						<th colspan=2>
							<div><?php echo _('Language Definitions Help') ?></div>
						</th>
					</tr>
					
					<tr>
						<td class='nobborder center' width='50%'>
						
							<div class='instruction'><?php echo _("Conditions") ?></div>
							<div class='ins_cont'>							
							<?php
							$i=0;
							foreach($labels_condition as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>
							
						</td>	
						
						<td class='nobborder center' width='50%'>
						
							<div class='instruction'><?php echo _("Actions") ?></div>
							<div class='ins_cont'>							
							<?php
							foreach($labels_actions as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>
							
						</td>	
					</tr>	
					
					<tr>	
						<td class='nobborder center' width='50%'>
						
							<div class='instruction'><?php echo _("Operators") ?></div>
							<div class='ins_cont' style='height:135px'>							
							<?php
							foreach($labels_operators as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>
							
						</td>
						
						<td class='nobborder center' width='50%'>
							
							<div class='instruction'><?php echo _("Variables") ?></div>
							<div class='ins_cont' style='height:135px'>							
							<?php
							foreach($labels_variables as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>							
						</td>
					</tr>
					
					<tr>
						<td class='nobborder center' colspan='2' height='100px'>
							<div id="help">
								<?php
								$config_nt = array(
									'content' => _("Select a language element from the lists to see its meaning"),
									'options' => array (
										'type'          => 'nf_info',
										'cancel_button' => false
									),
									'style'   => 'width: 90%; margin: 25px auto 10px auto; padding: 10px 0px; text-align: left; font-style: italic'
								); 
													
								$nt = new Notification('nt_1', $config_nt);
								$nt->show();
								?>
							</div>
						</td>
					</tr>
				</table>
				<div style='float:right;'>
					<a class='view_doc' href='repository_doc.php' ><?php echo _('Full References') ?></a>
				</div>
							
			</td>
		</tr>
	</table>

		
	<div style='text-align:center;padding:15px 0;'>
		<input type="submit" name="save" value="<?php echo _('Save Document') ?>"/>
	</div>
	
	</form>
</div>
	
	
<script>		
	<?php 
	foreach($help_msgs as $key => $text) 
	{
		echo "help[$key] = '".$text[0]."';";
		echo "exam[$key] = '".$text[1]."';";
	} 
	?>
</script>

</body>
</html>
