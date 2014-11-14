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

Session::logcheck("analysis-menu", "IncidentsTags");


function display_errors($info_error)
{
	$errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
	$error_msg    = "<div>"._("We found the following errors:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";
	
	$config_nt = array(
		'content' => $error_msg,
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => FALSE
		),
		'style'   => 'width: 90%; margin: 20px auto; padding: 10px 0px; text-align: left; font-style: italic'
	); 
	
	$nt = new Notification('nt_1', $config_nt);
	return $nt->show(FALSE);
}

if (!Session::menu_perms("analysis-menu", "IncidentsTags") && !Session::am_i_admin())
{  
    Session::unallowed_section(NULL);
}

// Avoid the browser resubmit POST data stuff

if (GET('redirect')) 
{
    header('Location: ' . $_SERVER['SCRIPT_NAME']);
    exit();
}

$db   		= new ossim_db();
$conn 		= $db->connect();
$tag  		= new Incident_tag($conn);
$parameters = NULL;
$info_error = NULL;
$error      = FALSE;

$action 	= $parameters['action'] = GET('action') ? GET('action') : 'list';
$id     	= $parameters['id']     = GET('id');


if ($action == 'mod1step' && is_numeric($id)) 
{
	$f      = $tag->get_list("WHERE td.id = $id");
	$name   = $f[0]['name'];
	$descr  = $f[0]['descr'];
}
elseif ($action == 'new2step' || $action == 'mod2step')
{
	$name   = $parameters['name']   = POST('name');
	$descr  = $parameters['descr']  = POST('descr');

	$validate  = array (
		"id"      => array("validation" => "OSS_DIGIT,OSS_NULLABLE"        , "e_message" => 'illegal:' . _("ID")),
		"name"    => array("validation" => "OSS_LETTER,OSS_PUNC,OSS_DIGIT" , "e_message" => 'illegal:' . _("Name")),
		"descr"   => array("validation" => "OSS_TEXT,OSS_NULLABLE"         , "e_message" => 'illegal:' . _("Description")),
		"action"  => array("validation" => "OSS_TEXT"                      , "e_message" => 'illegal:' . _("Action")),
	);

	foreach ($parameters as $k => $v)
	{
		eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

		if (ossim_error())
		{
			$info_error[] = ossim_get_error();
			ossim_clean_error();
			$error  = TRUE;
		}
	}
		
	if ($error == FALSE)
	{
		if ($action == 'new2step'){
			$tag->insert($name, $descr);
		}
		
		if ($action == 'mod2step'){
			$tag->update($id, $name, $descr);
		}
		
		header('Location: ' . $_SERVER['SCRIPT_NAME']);
	}
}
elseif ($action == 'delete')
{	
	ossim_valid($id, OSS_DIGIT, 'illegal:' . _("ID"));
	if (ossim_error()) 
	{
		$error = TRUE;
		$info_error[] = ossim_last_error();
		ossim_clean_error();
	}
	else
	{
		$tag->delete($id);
		header('Location: ' . $_SERVER['SCRIPT_NAME']);
	}
}
	
if ($error == TRUE)
{
	$action = str_replace('2', '1', $action);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type='text/javascript'>
		function delete_tag(num)
		{
			var msg =  '<?php echo _("There are")?> ' + num + ' <?php echo _("incidents using this tag. Do you really want to delete it?")?>';
			if (num >= 1)
			{
				return confirm(msg);
			} 
		}
		
		$(document).ready(function() {
			$('#descr').elastic();
			$('#name').focus();
		});
	</script>
	
	<style type='text/css'>
		#t_tags 
		{			
			margin: 50px auto 20px auto;	
		}
		
		.legend
		{
    		margin-top: 50px;   		
		}
				
		#t_ftags
		{
            width: 500px;
            margin: 10px auto 20px auto;	
		}
		
		#t_tags .odd td, #t_tags .even td
		{
			padding: 3px;
		}
		
		#descr, #name
		{
			width: 95%;
			height: 18px;
		}		
	</style>
	
</head>

<body>

<div class='c_back_button' style="display: block;">
    <input type='button' class="av_b_back" onclick="document.location.href='../incidents/index.php';return false;"/>
</div>

<?php
	
/*
 * FORM FOR NEW/EDIT TAG
 */
if ($action == 'new1step' || $action == 'mod1step') 
{
    if ($error == TRUE)
    {
		echo display_errors($info_error);
	}
	
	$action = str_replace('1', '2', $action);
	
	?>
	
	<div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>	
	
	<form method="post" action="?action=<?php echo $action ?>&id=<?php echo $id ?>" name="f">
		<table id='t_ftags'>
    		<tr>
    			<th class='headerpr_no_bborder' colspan="2"><?php echo gettext("Ticket Tags");?></th>
    		</tr>
			<tr>
				<th><?php echo _("Name") ?></th>
				<td class="left">
					<input type="text" name="name" id="name" value="<?php echo $name ?>"/>
					<span style="padding-left: 3px;">*</span>
				</td>
			</tr>
			<tr>
				<th><?php echo _("Description") ?></th>
				<td class="left"><textarea id='descr' name="descr" rows="5"><?php echo $descr?></textarea></td>
			</tr>
			<tr>
				<td colspan="2" class="nobborder center" style='padding:10px 0px;'>
					
					<input type="button" class="av_b_secondary" onClick="document.location = '<?php echo $_SERVER['SCRIPT_NAME'] ?>'" value="<?php echo _("Cancel") ?>"/>
					<input type="submit" value="<?php echo _("Save")?>"/>
				</td>
			</tr>
		</table>			
	</form>

	<?php
    /*
    * LIST TAGS
    */
} 
else 
{
	?>
	<table class='table_list' id="t_tags">
		<tr>
			<th><?php echo _("Id") ?></th>
			<th><?php echo _("Name") ?></th>
			<th><?php echo _("Description") ?></th>
			<th><?php echo _("Actions") ?></th>
		</tr>
		
		<?php
		$i = 0;
		
		foreach($tag->get_list() as $f) 
		{ 
			$class = ($i % 2 == 0) ? 'class="odd"' : 'class="even"';
			?>
			<tr <?php echo $class?>>
				<td valign="top"><strong><?php echo $f['id'] ?></strong></td>
				<td valign="top" style="text-align: left;" nowrap='nowrap'><?php echo htm($f['name']) ?></td>
				<td valign="top" style="text-align: left;"><?php echo htm($f['descr']) ?></td>
				<td nowrap='nowrap'> 
				<?php
					if (($f['id'] != '65001') && ($f['id'] != '65002')) 
					{ 
						?>
						<a href="?action=mod1step&id=<?php echo $f['id'] ?>"><img border="0" align="absmiddle" title="<?php echo _("Edit tag")?>" src="../vulnmeter/images/pencil.png"/></a>&nbsp;
						<a href="?action=delete&id=<?php echo $f['id'] ?>" onclick="delete_tag(<?php echo $f['num']?>)"><img border="0" align="absmiddle" title="<?php echo _("Delete tag")?>" src="../pixmaps/delete.gif"/></a>
						<?php
					} 
				?>
					&nbsp;
				</td>
			</tr>
			<?php
			$i++;
		} 
		?>
	</table>
	
	<div class='center' style='padding: 10px 0px;'>
        <input type="button" onClick="document.location = '<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=new1step'" value="<?php echo _("Add new tag") ?>"/>
    </div>
	
	<?php
}
?>