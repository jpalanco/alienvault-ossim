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

Session::logcheck("analysis-menu", "ControlPanelAlarms");
if (!Session::am_i_admin()) {
	echo ossim_error(_("You don't have permission to see this page"));
	exit();
}

$db = new ossim_db();
$conn = $db->connect();

$id      = GET('id');
$delete  = GET('delete');
$name    = GET('newname');
$bgcolor = GET('newbgcolor');
$fgcolor = GET('newfgcolor');
$italic  = (GET('newitalic') != "") ? 1 : 0;
$bold    = (GET('newbold') != "") ? 1 : 0;


ossim_valid($id, OSS_DIGIT, OSS_NULLABLE,                         'illegal:' . _("id"));
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE,                     'illegal:' . _("delete"));
ossim_valid($name, OSS_DIGIT, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("name"));
ossim_valid($bgcolor, OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE,         'illegal:' . _("bgcolor"));
ossim_valid($fgcolor, OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE,         'illegal:' . _("fgcolor"));
ossim_valid($italic, OSS_DIGIT, OSS_NULLABLE,                     'illegal:' . _("italic"));
ossim_valid($bold, OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _("bold"));
if (ossim_error()) {
    die(ossim_error());
}

if (GET('mode') == "insert") {
	if ($name == "") {
		$msg = _("You must type a name for the tab.");
	} else {
		$id = Tags::insert($conn,$name,$bgcolor,$fgcolor,$italic,$bold);
		$msg = _("Tag successfully created.");
	}
}
elseif (GET('mode') == "update" && $id != "")
{
	if ($name == "") {
		$msg = _("You must type a name for the tab.");
	} else {
		Tags::update($conn,$id,$name,$bgcolor,$fgcolor,$italic,$bold);
		$msg = _("Tag successfully saved.");
	}
	$id = "";
}

if (GET('delete') != "") {
	Tags::delete($conn,$delete);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?=_("Control Panel")?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<style type='text/css'>
		#t_alabels{
			margin: 20px auto;
			border:solid 1px #CCCCCC;
			width: 500px;
			border-collapse: collapse;
		}

		th{
			text-align: left !important;
		}

		#t_alabels_data{
			width: 100%;
			border: none;
			background: transparent;
		}

		#td_alabels_data{
			border-top: solid 1px #CCCCCC;
			padding-top: 10px;
		}

		#t_tags{
			margin: 5px;
			border: none;
		}

		#newname{
			width: 250px;
		}

		img.light {
			opacity: 0.5;
		}

	</style>


	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript">
	  function set_hand_cursor() {
		document.body.style.cursor = 'pointer';
	  }

	  function set_pointer_cursor() {
		document.body.style.cursor = 'default';
	  }

	  function set_id(id) {
		var aux = id.split(/_/);
		document.getElementById('newbgcolor').value = aux[0];
		document.getElementById('newfgcolor').value = aux[1];
		$('.preset').css('text-decoration','none');
		$('#'+id).css('text-decoration','underline');
		$('#preview').css('background-color','#'+aux[0]);
		$('#preview').css('color','#'+aux[1]);
		var text = $('#newname').val();
		$('#preview').html(text);
	  }

	  function change_preview() {
		  var text = $('#preview').html();
		  //$('.preset').html(text);
		  $('#preview').html(text);
		  if ($('#newitalic').attr('checked')) $('#preview').css('font-style','italic');
		  else $('#preview').css('font-style','normal');
		  if ($('#newbold').attr('checked')) $('#preview').css('font-weight','bold');
		  else $('#preview').css('font-weight','normal');
	  }
  </script>
</head>
<body>

<div class='c_back_button' style='display:block;'>
     <input type='button' class="av_b_back" onclick="document.location.href='../alarm/alarm_console.php?hide_closed=1';return false;"/>
</div>

<br/><br/>
<table id='t_alabels' class="transparent" align="center">
	<tr>
		<th><?php echo _("Alarms labels")?></th>
	</tr>

	<?php
	if ($msg != "")
	{
		?>
		<tr><td class="nobborder"><?php echo $msg ?></td></tr>
		<?php
	}

	$tags = Tags::get_list($conn);

	if ($id != "") {
		$aux_tag = Tags::get_list($conn,"WHERE id=$id");
		$tag_selected = $aux_tag[$id]->get_bgcolor()."_".$aux_tag[$id]->get_fgcolor();
		$bgcolor = "#".$aux_tag[$id]->get_bgcolor();
		$fgcolor = "#".$aux_tag[$id]->get_fgcolor();
		$tag_name = $aux_tag[$id]->get_name();
		$italic = $aux_tag[$id]->get_italic();
		$bold = $aux_tag[$id]->get_bold();
	}
	else
	{
		$tag_selected = "dee5f2_5a6986";
		$aux = explode("_",$tag_selected);
		$bgcolor = "#".$aux[0];
		$fgcolor = "#".$aux[1];
		$tag_name = "";
		$italic = "";
		$bold = "";
	}

	if (count($tags) < 1)
	{
		?>
		<tr><td valign='middle' class="center nobborder" style="height: 30px;"><?php echo _("No labels created")?></td></tr>
		<?php
	}
	else
	{
		?>
		<tr>
			<td>
				<table id='t_tags'>
					<?php
					foreach ($tags as $tag)
					{
						?>
						<tr>
							<td class="nobborder"><a href="tags_edit.php?id=<?php echo $tag->get_id() ?>"><img src="../vulnmeter/images/pencil.png" border="0" alt="<?php echo _("Modify") ?>" title="<?php echo _("Modify") ?>"></img></a></td>
						<? if ($tag->get_ctx() != '0') { ?>
							<td class="nobborder"><img src="../vulnmeter/images/delete.gif" class="light" border="0"/></td>
						<? } else { ?>
							<td class="nobborder"><a href="tags_edit.php?delete=<?php echo $tag->get_id() ?>" onclick="if(!confirm('<?php echo  Util::js_entities(_("Are you sure?"))?>')) return false;"><img src="../vulnmeter/images/delete.gif" border="0" alt="<?php echo _("Delete") ?>" title="<?php echo _("Delete") ?>"></img></a></td>
						<? } ?>
							<td class="nobborder"><table class="transparent" cellpadding="4"><tr><td style="font-size:10px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:<?php echo '#'.$tag->get_bgcolor()?>;color:<?php echo '#'.$tag->get_fgcolor()?>;font-weight:<?php echo ($tag->get_bold()) ? "bold" : "normal" ?>;font-style:<?php echo ($tag->get_italic()) ? "italic" : "none" ?>"><?php echo $tag->get_name()?></td></tr></table></td>
						</tr>
						<?php
					}
					?>
				</table>
			</td>
		</tr>
		<?php
	}
	?>

	<tr>
		<td class="nobborder">&nbsp;</td>
	</tr>

	<form>
		<input type="hidden" name="id" value="<?php echo $id ?>"></input>
		<input type="hidden" name="mode" value="<?php echo ($id != "") ? "update" : "insert" ?>"></input>
		<input type="hidden" id="newbgcolor" name="newbgcolor" value="<?php echo str_replace("#","",$bgcolor) ?>"></input>
		<input type="hidden" id="newfgcolor" name="newfgcolor" value="<?php echo str_replace("#","",$fgcolor) ?>"></input>

	<tr>
		<td id='td_alabels_data'>
			<table id='t_alabels_data' cellspacing='2' cellpadding='2'>
				<tr>
					<th valign="middle"><?php echo _("New label name") ?></th>
					<td class="nobborder"><input type="text" id="newname" name="newname" onkeyup="change_preview()" value="<?php echo $tag_name ?>"></input></td>
				</tr>
				<tr>
					<th valign="middle"><?php echo _("Italic") ?></th>
					<td class="nobborder"><input type="checkbox" value="1" id="newitalic" name="newitalic" onclick="change_preview()" <?php if ($italic) echo "checked" ?>></input></td>
				</tr>
				<tr>
					<th valign="middle"><?php echo _("Bold") ?></th>
					<td class="nobborder"><input type="checkbox" value="1" id="newbold" name="newbold" onclick="change_preview()" <?php if ($bold) echo "checked" ?>></input></td>
				</tr>
				<tr>
					<th valign="middle"><?php echo _("Style color") ?></th>
					<td class="nobborder">
						<table class="transparent" cellspacing="4" cellpadding="2">
							<tr>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="dee5f2_5a6986" style="font-size:10px;border:1px solid #888888;background-color:#dee5f2;color:#5a6986;text-decoration:<?php echo ($tag_selected == "dee5f2_5a6986") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="e0ecff_206cff" style="font-size:10px;border:1px solid #888888;background-color:#e0ecff;color:#206cff;text-decoration:<?php echo ($tag_selected == "e0ecff_206cff") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="dfe2ff_0000cc" style="font-size:10px;border:1px solid #888888;background-color:#dfe2ff;color:#0000cc;text-decoration:<?php echo ($tag_selected == "dfe2ff_0000cc") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="e0d5f9_5229a3" style="font-size:10px;border:1px solid #888888;background-color:#e0d5f9;color:#5229a3;text-decoration:<?php echo ($tag_selected == "e0d5f9_5229a3") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="fde9f4_854f61" style="font-size:10px;border:1px solid #888888;background-color:#fde9f4;color:#854f61;text-decoration:<?php echo ($tag_selected == "fde9f4_854f61") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="ffe3e3_cc0000" style="font-size:10px;border:1px solid #888888;background-color:#ffe3e3;color:#cc0000;text-decoration:<?php echo ($tag_selected == "ffe3e3_cc0000") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
							</tr><tr>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="5a6986_dee5f2" style="font-size:10px;border:1px solid #888888;background-color:#5a6986;color:#dee5f2;text-decoration:<?php echo ($tag_selected == "5a6986_dee5f2") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="206cff_e0ecff" style="font-size:10px;border:1px solid #888888;background-color:#206cff;color:#e0ecff;text-decoration:<?php echo ($tag_selected == "206cff_e0ecff") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="0000cc_dfe2ff" style="font-size:10px;border:1px solid #888888;background-color:#0000cc;color:#dfe2ff;text-decoration:<?php echo ($tag_selected == "0000cc_dfe2ff") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="5229a3_e0d5f9" style="font-size:10px;border:1px solid #888888;background-color:#5229a3;color:#e0d5f9;text-decoration:<?php echo ($tag_selected == "5229a3_e0d5f9") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="854f61_fde9f4" style="font-size:10px;border:1px solid #888888;background-color:#854f61;color:#fde9f4;text-decoration:<?php echo ($tag_selected == "854f61_fde9f4") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="cc0000_ffe3e3" style="font-size:10px;border:1px solid #888888;background-color:#cc0000;color:#ffe3e3;text-decoration:<?php echo ($tag_selected == "cc0000_ffe3e3") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
							</tr><tr>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="fff0e1_ec7000" style="font-size:10px;border:1px solid #888888;background-color:#fff0e1;color:#ec7000;text-decoration:<?php echo ($tag_selected == "fff0e1_ec7000") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="fadcb3_b36d00" style="font-size:10px;border:1px solid #888888;background-color:#fadcb3;color:#b36d00;text-decoration:<?php echo ($tag_selected == "fadcb3_b36d00") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="f3e7b3_ab8b00" style="font-size:10px;border:1px solid #888888;background-color:#f3e7b3;color:#ab8b00;text-decoration:<?php echo ($tag_selected == "f3e7b3_ab8b00") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="ffffd4_636330" style="font-size:10px;border:1px solid #888888;background-color:#ffffd4;color:#636330;text-decoration:<?php echo ($tag_selected == "ffffd4_636330") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="f9ffef_64992c" style="font-size:10px;border:1px solid #888888;background-color:#f9ffef;color:#64992c;text-decoration:<?php echo ($tag_selected == "f9ffef_64992c") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="f1f5ec_006633" style="font-size:10px;border:1px solid #888888;background-color:#f1f5ec;color:#006633;text-decoration:<?php echo ($tag_selected == "f1f5ec_006633") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
							</tr><tr>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="ec7000_f8f4f0" style="font-size:10px;border:1px solid #888888;background-color:#ec7000;color:#f8f4f0;text-decoration:<?php echo ($tag_selected == "ec7000_f8f4f0") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="b36d00_fadcb3" style="font-size:10px;border:1px solid #888888;background-color:#b36d00;color:#fadcb3;text-decoration:<?php echo ($tag_selected == "b36d00_fadcb3") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="ab8b00_f3e7b3" style="font-size:10px;border:1px solid #888888;background-color:#ab8b00;color:#f3e7b3;text-decoration:<?php echo ($tag_selected == "ab8b00_f3e7b3") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="636330_ffffd4" style="font-size:10px;border:1px solid #888888;background-color:#636330;color:#ffffd4;text-decoration:<?php echo ($tag_selected == "636330_ffffd4") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="64992c_f9ffef" style="font-size:10px;border:1px solid #888888;background-color:#64992c;color:#f9ffef;text-decoration:<?php echo ($tag_selected == "64992c_f9ffef") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
								<td class="preset" onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="set_id(this.id)" id="006633_f1f5ec" style="font-size:10px;border:1px solid #888888;background-color:#006633;color:#f1f5ec;text-decoration:<?php echo ($tag_selected == "006633_f1f5ec") ? "underline" : "none" ?>"><?php echo _("Label") ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th valign="middle" style='height: 30px;'><?php echo _("Preview")?></th>
					<td valign="middle" class="left nobborder">
						<table class="transparent" cellpadding="4">
							<tr><td id="preview" style="border:0px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;font-size:10px;background-color:<?php echo $bgcolor ?>;color:<?php echo $fgcolor ?>;font-style:<?php echo ($italic) ? "italic" : "normal" ?>;font-weight:<?php echo ($bold) ? "bold" : "normal" ?>"><?php echo $tag_name ?></td></tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr>
		<td class="center nobborder" style='padding: 10px;'>
			<input type="submit" value="<?php echo ($id != "") ? _("Modify") : _("Create")?>"></input>
		</td>
	</tr>

	</form>
</table>
</body>
</html>
