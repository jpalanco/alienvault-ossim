<?php
/**
 * notes.php
 * 
 * File notes.php is used to:
 * - Be included by index.php as module of asset details
 * - Response ajax call from index.php by javascript init_notes()   function (notes_ajax_mode       = true)
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

// Ajax mode
if ($_GET['notes_ajax_mode'] != '')
{
	require_once 'av_init.php';
		
	$id              = GET('id');
	$_asset_type_aux = GET('type');
	
	ossim_valid($id,              OSS_HEX,   'illegal:' . _('Asset ID'));
	ossim_valid($_asset_type_aux, OSS_ALPHA, 'illegal:' . _('Asset Type'));
	
	if (ossim_error()) 
	{
		die(ossim_error());
	}
	
	// Patch for incoherences
	if ($_asset_type_aux == 'group')
	{
	    $_asset_type_aux = 'host_group';
	}
	
	$db = new ossim_db();
	$conn = $db->connect();
	
	$notes = Notes::get_list($conn," AND type='".$_asset_type_aux."' AND asset_id = UNHEX('$id') ORDER BY date DESC");
	
	$db->close();
	
	if (count($notes) > 0)
	{
		foreach ($notes as $note)
		{
		?>
		<div class="note_row" onmouseover="$('#delete_link_<?php echo $note->get_id() ?>').show();$(this).css('background-color', '#C7C7C7');" onmouseout="$('#delete_link_<?php echo $note->get_id() ?>').hide();$(this).css('background-color', 'transparent');">
			
			<div>
				<div class='detail_header_left'>
					<?php echo $note->get_date().' '._('by').' <b>'.$note->get_user().'</b>' ?> 
				</div>
				
				<div class='detail_header_right delete_links' id="delete_link_<?php echo $note->get_id() ?>">
					<a href="" onclick="delete_note(<?php echo $note->get_id() ?>);return false">[<?php echo _('delete') ?>]</a>
				</div>
				
				<div class='detail_clear'></div>
				
			</div>
				
				
			<div class='note_txt <?php echo (Session::get_session_user()==$note->get_user()) ? 'editInPlace' : ''?>' 
				note='<?php echo $note->get_id() ?>' 
				onmouseover="$('#edit_tip').show()"
				onmouseout="$('#edit_tip').hide()">
				<?php
					echo nl2br(Util::htmlentities($note->get_txt()));
				?>
			</div>
			
		</div>
		<?php
        }
	}
	
	exit();
}

// Exit if the script is called by URL. It has to be included by index
if ($_SERVER['SCRIPT_NAME'] != AV_MAIN_PATH."/asset_details/index.php")
{
    exit();
}

?>
<div id="notes_list"></div>

<div class='detail_notes_add'>
    <b><?php echo _('Add note') ?></b>
    <span id="edit_tip"><b><?php echo _('Click to edit this Note') ?></b></span>
</div>
<div><textarea name="note_txt" id="note_txt" class='detail_notes_textarea'></textarea></div>
<div class='detail_header_right'><input class='notes_save_button av_b_secondary' type="button" value="<?php echo _('Save') ?>"/></div>

<?php
/* End of file notes.php */
/* Location: ./asset_details/modules/notes.php */