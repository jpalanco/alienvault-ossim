<?php
/**
 * location.php
 * 
 * File activity.php is used to:
 * - Be included by index.php as module of asset details
 * - Show Google Map for location of a Host in asset details
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

// Exit if the script is called by URL. It has to be included by index
if ($_SERVER['SCRIPT_NAME'] != "/ossim/asset_details/index.php")
{
	exit();
}

?>
<div>
    <div id="detail_map"></div>
    
    <div class='detail_edit_button button_location'>
        <input type="button" class="greybox button_location_input av_b_secondary" value="<?php echo _('Edit Location')?>"/>
    </div>
</div>
<?php
/* End of file location.php */
/* Location: ./asset_details/modules/location.php */