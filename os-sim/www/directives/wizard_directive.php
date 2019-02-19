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


require_once ('av_init.php');
Session::logcheck("configuration-menu", "CorrelationDirectives");

$engine_id    = (GET('engine_id') != "") ? GET('engine_id') : POST('engine_id');
$directive_id = (GET('directive_id') != "") ? GET('directive_id') : POST('directive_id');
$file         = "user.xml"; // Always user.xml (can change in the future...)

ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _("Engine ID"));
ossim_valid($directive_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Directive ID"));

if ( ossim_error() ) { 
    die(ossim_error());
}

if (POST('mode') == "saveclose" || POST('mode') == "savecontinue")
{
	$name     = POST('name');
	$prio     = POST('priority');
	$intent   = POST('intent');
	$strategy = POST('strategy');
	$method   = POST('method');
	
	ossim_valid($name,     OSS_DIRECTIVE_NAME,     'illegal:' . _("Directive Name"));
	ossim_valid($prio,     OSS_DIGIT,              'illegal:' . _("New Priority"));
	ossim_valid($intent,   OSS_DIGIT,              'illegal:' . _("Intent"));
	ossim_valid($strategy, OSS_DIGIT,              'illegal:' . _("Strategy"));
	ossim_valid($method,   OSS_TEXT,               'illegal:' . _("Method"));
	
	if (ossim_error()) 
	{
	    die(ossim_error());
	}
	
	$directive_editor = new Directive_editor($engine_id);
	$filepath = $directive_editor->engine_path."/".$file;
	
	if (!file_exists($filepath)) 
	{
		die(ossim_error(_("User Contributed file not found in")." ".$directive_editor->engine_path.". "._("Please, create it first")));
	}
	
	// SAVE CURRENT
	if ($directive_id != "") 
	{
		$directive_editor = new Directive_editor($engine_id);
		$filepath         = $directive_editor->engine_path."/".$file;
		$dom              = $directive_editor->get_xml($filepath, "DOMXML");
		$directive        = $directive_editor->getDirectiveFromXML($dom, $directive_id);
		$node             = $directive->directive;
		$node->setAttribute('name', $name);
		$node->setAttribute('priority', $prio);
		$directive_editor->save_xml($filepath, $dom, "DOMXML");
		$directive_editor->update_directive_pluginsid($directive_id, 2, $prio, $name);
		$directive_editor->update_directive_taxonomy($directive_id, $intent, $strategy, $method);
		
		$infolog = array($directive_id, 'updated');
		Log_action::log(86, $infolog);
	
	} // INSERT NEW
	else 
	{
		if ($directive_editor->directive_exists($name, $filepath))
		{
			die(ossim_error(_("This directive name already exists")));
		}
		// Get new ID
		$id = $directive_editor->new_directive_id($file);
		if ($id < 1)
		{
			echo ossim_error(_("Unable to create a new directive in ")."<b>$file</b>");
		}
		// Create a Node (Do not create yet, at rule finish)
		// ...
	}
	
	// Back to MAIN
	if (POST('mode') == "saveclose") 
	{
		Util::memcacheFlush();
	?>
		<script type="text/javascript">
			var params          = new Array();
			params['xml']       = "<?php echo $file ?>";
            params['directive'] = "<?php echo $directive_id ?>";
            params['reload']    = true;
            params['edited']    = true; //This param is for the greybox in alarm detail

			parent.GB_hide(params);
		</script>
	<?php
	} 
	else // Jump to Rule Wizard
	{
		header("Location:wizard_rule.php?level=1&directive_id=$id&id=$id-1-0&engine_id=$engine_id&xml_file=$file&reloadindex=1&from_directive=1&directive_name=$name&directive_prio=$prio&directive_intent=$intent&directive_strategy=$strategy&directive_method=$method");
	}
	exit;
}

$directive_editor = new Directive_editor($engine_id);

if ($directive_id != "") {
	$filepath         = $directive_editor->engine_path."/".$file;
	$dom              = $directive_editor->get_xml($filepath, "DOMXML");
	$directive = $directive_editor->getDirectiveFromXML($dom, $directive_id);
	list($directive_intent, $directive_strategy, $directive_method) = $directive_editor->get_directive_intent($directive_id, "array");
}

$intent_list   = $directive_editor->get_intent_list();
$strategy_list = $directive_editor->get_strategy_list();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<style>
	
	/* Notification Styles */
	#av_msg_container
	{
    	height: auto !important;
	}
	#av_msg_info
	{
    	top:0px !important;
    	position:relative !important;
	}
	#nt_1
	{
    	margin-top: 8px !important;
	}
	
</style>
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/notification.js"></script>
<script type="text/javascript">
    function submitform(mode) 
    {
    	if ($('#name').val() == "") 
    	{
    		notify('<?php echo _("Required Directive Name") ?>', 'nf_error', true);
    	} 
    	else if ($('#intent').val() == "" || $('#strategy').val() == "" || $('#method').val() == "")
    	{
    		notify('<?php echo _("Required Directive Taxonomy Values") ?>', 'nf_error', true);
    	} 
    	else if ($('#name').val().match(/^[a-zA-Z0-9\s\,\-\_\(\)\.]+$/))
    	{
    		$('#mode').val(mode);
    		$('#fdirective').submit();
    	} 
    	else 
    	{
    		notify('<?php echo _("Type a valid name for the directive") ?>', 'nf_error', true);
    	}
    	
    }
    /* Call the "onChange()" handler if "Enter" is pressed. */
    function getEvent(event) 
    {	
    	if (window.event)
    	{
        	return window.event.keyCode;
    	} 
    	
    	if (event) 
    	{
    	   return event.which;
        }
    }
    function onKeyEnterWizard(elt, evt) 
    {
	    	if (getEvent(evt) == 13) 
	    	{
	    		<?php 
	    		if ($directive_id == "") 
	    		{
	    		?>
	    		    submitform('savecontinue');
	    		<?php 
	    		}
	    		else 
	    		{ 
	    		?>
	    		    submitform('saveclose');
	    		<?php 
	    		} 
	    		?>
	    	}
    }
</script>
</head>	
<body>
<form id="fdirective" method="post" style="height:100%">
<input type="hidden" name="mode" id="mode" value="" />
<input type="hidden" name="priority" id="priority" value="3" />
<input type="hidden" name="directive_id" id="directive_id" value="<?php echo $directive_id ?>" />
<table class="transparent" align="center">
	<tr>
		<td valign="middle">
			<table class="transparent">
				<tr>
					<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
						<?php echo gettext("Name for the directive"); ?>
					</th>
				</tr>
				<tr>
					<td style="text-align:center;padding:3px 1px 4px 2px;border:0px">
						<input type="text" style="width:95%;height:20px;font-size:13px" name="name" id="name" value="<?php echo str_replace("'", "", str_replace("\"", "", $directive->name)); ?>" title="<?php echo str_replace("'", "", str_replace("\"", "", $directive->name)); ?>" onkeyup="onKeyEnterWizard(this,event);" />
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
						<?php echo gettext("Taxonomy"); ?>
					</th>
				</tr>
				<tr>
					<td>
						<table class="transparent" style="width:100%">
							<tr>
								<td class="left">
									<?php echo _("Intent") ?>:
								</td>
								<td class="left">
									<select name="intent" id="intent" style="width:180px">
									<option value="">-</option>
									<?php foreach ($intent_list as $intent_id => $intent_name) { ?>
									<option value="<?php echo $intent_id ?>" <?php if ($intent_id == $directive_intent) echo "selected" ?>><?php echo $intent_name ?></option>
									<?php } ?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="left">
									<?php echo _("Strategy") ?>:
								</td>
								<td class="left">
									<select name="strategy" id="strategy" style="width:180px">
									<option value="">-</option>
									<?php foreach ($strategy_list as $strategy_id => $strategy_name) { ?>
									<option value="<?php echo $strategy_id ?>" <?php if ($strategy_id == $directive_strategy) echo "selected" ?>><?php echo $strategy_name ?></option>
									<?php } ?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="left">
									<?php echo _("Method") ?>:
								</td>
								<td class="left">
									<input type="text" name="method" id="method" value="<?php echo $directive_method ?>" style="width:180px"/>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<th style="white-space: nowrap; padding: 5px;font-size:12px">
						<?php echo gettext("Priority"); ?>
					</th>
				</tr>
				<?php for ($i = 0; $i <= 5; $i++) { ?>
				<tr><td class="center nobborder"><input type="button" style="width:100%" <?php echo (($directive_id != "" && $i == $directive->priority) || ($directive_id == "" && $i == 3)) ? "class='prio_button av_b_secondary'" : "class='prio_button'" ?> value="<?php echo $i ?>" onclick="document.getElementById('priority').value='<?php echo $i ?>';$('.prio_button').removeClass('av_b_secondary');$(this).addClass('av_b_secondary');"></input></td></tr>
				<?php } ?>
				<tr>
					<td style="padding-top:30px">
						<input type="button" class="av_b_secondary" onclick="parent.GB_close()" value="<?php echo _("Cancel") ?>" />		
						<?php if ($directive_id == "") { ?><input type="button" onclick="submitform('savecontinue');" value="<?php echo _("Next") ?>" style="width:50px" />
						<?php } else { ?>
						<input type="button" onclick="submitform('saveclose');" value="<?php echo _("Save") ?>" />
						<?php } ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</body>
</html>
