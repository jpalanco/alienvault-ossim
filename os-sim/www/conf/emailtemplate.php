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


if ( !Session::menu_perms("analysis-menu", "ConfigurationEmailTemplate") && !Session::am_i_admin() )
{ 
	Session::unallowed_section(null);
}

Session::logcheck('analysis-menu', 'ConfigurationEmailTemplate');

$db   = new ossim_db();
$conn = $db->connect();

foreach(array('preview', 'subject_tpl','body_tpl', 'save') as $var)
 {
    $$var = POST($var);
}
// User wants the default template
if (GET('reset')) 
{
    $save = true;
    $subject_tpl = $body_tpl = '';
}
// Save values in the "config" table

if ($save) {
    Incident_ticket::save_email_template($subject_tpl, $body_tpl);
    header("Location: " . $_SERVER['SCRIPT_NAME']);
    exit();
}
// First time, get the default templates. They are defined
// inside the function: Incident_ticket::get_email_template()
if (!$subject_tpl) $subject_tpl = Incident_ticket::get_email_template('subject');
if (!$body_tpl) $body_tpl = Incident_ticket::get_email_template('body');

$labels = array(
    'ID' => array(
        'help' => _("The Ticket database ID") ,
        'sample' => '63'
    ) ,
    'INCIDENT_NO' => array(
        'help' => _("The ticket human-oriented reference") ,
        'sample' => 'ALA63'
    ) ,
    'TITLE' => array(
        'help' => _("The ticket resume") ,
        'sample' => _("Detected MAC change in DMZ")
    ) ,
    'EXTRA_INFO' => array(
        'help' => _("Related ticket information") ,
        'sample' => "Source IPs: 10.10.10.10\n" . "Source Ports: 2267\n" . "Dest. IPs: 10.10.10.11\n" . "Dest. Ports: 22\n"
    ) ,
    'IN_CHARGE_NAME' => array(
        'help' => _("The person currently in charge of solving the ticket") ,
        'sample' => 'John Smith'
    ) ,
    'IN_CHARGE_LOGIN' => array(
        'help' => _("The login of the person currently in charge of solving the ticket") ,
        'sample' => 'jsmith'
    ) ,
    'IN_CHARGE_EMAIL' => array(
        'help' => _("The email of the person currently in charge of solving the ticket") ,
        'sample' => 'jsmith@example.com'
    ) ,
    'IN_CHARGE_DPTO' => array(
        'help' => _("The department of the person currently in charge of solving the ticket") ,
        'sample' => 'Tech Support'
    ) ,
    'IN_CHARGE_COMPANY' => array(
        'help' => _("The company of the person currently in charge of solving the ticket") ,
        'sample' => 'Example Inc.'
    ) ,
    'PRIORITY_NUM' => array(
        'help' => _("The priority of the ticket in numbers from 1 (low) to 10 (high)") ,
        'sample' => 8
    ) ,
    'PRIORITY_STR' => array(
        'help' => _("The priority in string format: Low, Medium or High") ,
        'sample' => 'High'
    ) ,
    'TAGS' => array(
        'help' => _("The extra labels of information attached to the ticket") ,
        'sample' => "NEED_MORE_INFO, FALSE_POSITIVE"
    ) ,
    'CREATION_DATE' => array(
        'help' => _("When was the ticket created") ,
        'sample' => '2005-10-18 19:30:53'
    ) ,
    'STATUS' => array(
        'help' => _("What's the current status: Open or Close") ,
        'sample' => 'Open'
    ) ,
    'CLASS' => array(
        'help' => _("The type of ticket: Alarm, Event, Metric...") ,
        'sample' => 'Alarm'
    ) ,
    'TYPE' => array(
        'help' => _("The ticket category or group") ,
        'sample' => 'Policy Violation'
    ) ,
    'LIFE_TIME' => array(
        'help' => _("The time passed since the creation of the ticket") ,
        'sample' => '1 Day, 10:13'
    ) ,
    'TICKET_DESCRIPTION' => array(
        'help' => _("The description filled by the ticket author") ,
        'sample' => 'Detected a MAC change on dmz1.int host'
    ) ,
    'TICKET_ACTION' => array(
        'help' => _("The action filled by the ticket author") ,
        'sample' => 'Investigate the ticket asap'
    ) ,
    'TICKET_AUTHOR_NAME' => array(
        'help' => _("The person who just created a new ticket") ,
        'sample' => 'Sam Max'
    ) ,
    'TICKET_AUTHOR_EMAIL' => array(
        'help' => _("The email of the ticket author") ,
        'sample' => 'smax@example.com'
    ) ,
    'TICKET_AUTHOR_DPTO' => array(
        'help' => _("The department of the ticket author") ,
        'sample' => 'Network Operations'
    ) ,
    'TICKET_AUTHOR_COMPANY' => array(
        'help' => _("The company of the ticket author") ,
        'sample' => 'Same Example Inc.'
    ) ,
    'TICKET_EMAIL_CC' => array(
        'help' => _("Who (Name and Email) received this email too") ,
        'sample' => "\"John Smith\" <jsmith@example.com>\n\"Sam Max\" <smax@example.com>"
    ) ,
    'TICKET_HISTORY' => array(
        'help' => _("The complete list of tickets related to this ticket") ,
        'sample' => '-- Here goes the list of tickets --'
    ) ,
    'TICKET_INVERSE_HISTORY' => array(
        'help' => _("The complete list of tickets related to this ticket") . " (" . _("reverse order") . ")",
        'sample' => '-- Here goes the reversed list of tickets --'
    )
);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.cleditor.css"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
    <script type="text/javascript" src="../js/CLeditor/jquery.cleditor.min.js"></script>
    <script type="text/javascript" src="../js/CLeditor/jquery.cleditor.emailtemplatetag.js"></script>
	<script type="text/javascript" src="../js/CLeditor/jquery.cleditor.extimage.js"></script>	
	<script type="text/javascript">
		
		$.cleditor.buttons.image.uploadUrl = 'template_upload_image.php';
		$.cleditor.buttons.image.prefix = '';

		function confirm_reset(text)
		{
			ret = confirm(text);
			if (ret) {
				document.location = '<?php echo $_SERVER['SCRIPT_NAME'] ?>?reset=1';
			}
			return ret
		}

		// Precious code from Dokuwiki! (dokuwiki/lib/scripts/script.js)
		function insertAtCarret(field,value)
		{
		  //IE support
		  if (document.selection) {
			field.focus();
			if (opener == null) {
			  var sel = document.selection.createRange();
			} else {
			  var sel = opener.document.selection.createRange();
			}
			sel.text = value;
		  //MOZILLA/NETSCAPE support
		  } else if (field.selectionStart || field.selectionStart == '0') {
			var startPos  = field.selectionStart;
			var endPos    = field.selectionEnd;
			var scrollTop = field.scrollTop;
			field.value = field.value.substring(0, startPos)
						  + value
						  + field.value.substring(endPos, field.value.length);
		
			field.focus();
			var cPos=startPos+(value.length);
			field.selectionStart=cPos;
			field.selectionEnd=cPos;
			field.scrollTop=scrollTop;
		  } else {
			field.value += "\n"+value;
		  }
		  // reposition cursor if possible
		  if (field.createTextRange) field.caretPos = document.selection.createRange().duplicate();
		}

		// Interface to insertAtCarret()
		function insertAtCursor(myField)
		{
			var tags    = document.myform.tags;
			var index   = tags.selectedIndex;
			if (index >= 0 )
			{
				var myValue = tags.options[index].text;
				insertAtCarret(myField, myValue);
			}
			else
				alert('<?php echo Util::js_entities(_("You have to select a tag"))?>');
		}
		
		$(document).ready(function(){
			$(".to_add").simpletip({
					position: 'left',
					baseClass: 'btooltip',
					onBeforeShow: function() {
						this.update('<?php echo _("Select a tag to add") ?>');
					}
			});
            $(".to_add_body").simpletip({
					position: 'left',
					baseClass: 'btooltip',
					onBeforeShow: function() {
						this.update('<?php echo _("Please use email button (@) which is in editor, to insert a tag") ?>');
					}
			});
            $("textarea").cleditor({
				height:  360, // height not including margins, borders or padding
				
				controls:     // controls to add to the toolbar
				"bold italic underline strikethrough style | mail | font color highlight removeformat image | bullets numbering | outdent " +
				"indent | alignleft center alignright justify | undo redo | " + 
                "cut copy | source"
			});
		});
		
</script>

<style type='text/css'>
	#help {margin-bottom: 10px;}
    
	#style_body {background: #E3E3E3;}
	
	#b_back{
		margin-top: 15px;
	}
	
</style>

</head>
<body>

<div class="c_back_button">         
    <input type='button' class="av_b_back" onclick="document.location.href='index.php?open=2&adv=1'; return false;"/> 
</div> 

  
<div id="help">
	<?php
	$config_nt = array(
		'content' => _("Select a TAG to see its meaning. Use email button (@) which is in editor, to insert this TAG"),
		'options' => array (
			'type'          => 'nf_info',
			'cancel_button' => false
		),
		'style'   => 'width: 90%; margin: 20px auto; padding: 10px 0px; text-align: left; font-style: italic'
	); 
						
	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
	?>
</div>

<form name="myform" method="POST">

<table width="90%" border="0" align="center">
	<tr valign="top">
		<td class='nobborder left'>
			<div style='padding: 0px 0px 3px 0px'><?php echo _("Template Labels") ?></div>
			<select name="tags" size=" <?php echo count($labels)+1; ?> " onChange="javascript: show_help(this);">
				<?php
				$i = 0;
				foreach($labels as $label => $data) 
				{
					$help_msgs[$i++] = addslashes($data['help']);
					?>
					<option name="<?php echo $label?>"><?php echo $label?></option>
					<?php
				} ?>
			</select>
		</td>
		
		<td class='nobborder'>
			<table width="100%">
				<tr>
					<td style="text-align: center;" class="nobborder">
						<!--<input type="button" value="->" onClick="javascript: insertAtCursor(document.myform.subject_tpl);" class="small"/>-->
						<a style='cursor:pointer;' class='to_add' onClick="javascript: insertAtCursor(document.myform.subject_tpl);"><img src='../pixmaps/play_blue.png' align='absmiddle'/></a>
					</td>
					<th width="10%"><?php echo _("Subject") ?></th>
					<td class='left nobborder'>
						<input type="text" name="subject_tpl" value="<?php echo Util::htmlentities($subject_tpl) ?>" size="80" style="font-family: mono-space, mono;"/>
					</td>
				</tr>
				
				<tr>
					<td class='nobborder' style="text-align: center !important;">
                        &nbsp;
					</td>
					<th id='style_body' valign="top" width="10%"><?php echo gettext("Body"); ?></th>
					<td style="text-align: left;padding-left:5px;" class='nobborder'>
						<textarea name="body_tpl" rows="25" cols="80" wrap='hard' style="font-family: mono-space, mono;"><?php echo str_replace('</textarea>', Util::htmlentities('</textarea>'), $body_tpl) ?></textarea>
					</td>
				</tr>                
			</table>
		</tr>
	</table>

	<script type='text/javascript'>
    function show_help(select_el)
    {
        var selected = select_el.selectedIndex;
        var help = new Array;
		<?php
		foreach($help_msgs as $key => $text) { 
			?>
			help[<?php echo $key?>] = '<?php echo $text?>';
				<?php
		} 
		?>
		$('.nf_info').text(help[selected]);
        return false;
    }

	</script>
	
	<p align="center">
		<input type="button" name="reset" value="<?php echo _('Reset to Defaults') ?>" onclick="javscript: return confirm_reset('<?php echo addslashes( Util::js_entities(_("All changes will be lost. Continue anyway?"))) ?>')"/>
		&nbsp;<input type="submit" name="save" value="<?php echo _('Save Template') ?>"/>
	</p>
</form>
</body>
</html>
