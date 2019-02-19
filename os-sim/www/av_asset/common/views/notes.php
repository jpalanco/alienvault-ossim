<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2015 AlienVault
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

$asset_id   = GET('asset_id');
$asset_type = GET('asset_type');

if ($asset_type == 'asset')
{
    Session::logcheck('environment-menu', 'PolicyHosts');
}
elseif ($asset_type == 'net_group')
{
    Session::logcheck('environment-menu', 'PolicyNetworks');
}
else
{
	ossim_error(_('Invalid asset type value'));
	
	exit();
}
	
ossim_valid($asset_id,     OSS_HEX,               'illegal:' . _('Asset ID'));
ossim_valid($asset_type,   OSS_LETTER, OSS_SCORE, 'illegal:' . _('Asset Type'));

if (ossim_error())
{
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'assets/asset_details.css',      'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                  'def_path' => TRUE),
            array('src' => 'notification.js',                'def_path' => TRUE),
            array('src' => 'jquery.scroll.js',               'def_path' => TRUE),
            array('src' => 'token.js',                       'def_path' => TRUE),
            array('src' => 'utils.js',                       'def_path' => TRUE),
            array('src' => 'jquery.editinplace.js',          'def_path' => TRUE),
            array('src' => 'av_note.js.php',                 'def_path' => TRUE),
        );
        
        Util::print_include_files($_files, 'js');
    ?>
	
	<script type='text/javascript'>
    		
		$(document).ready(function()
		{    		
    		$("[data-bind='detail_notes']").av_note({
                asset_type   : "<?php echo $asset_type ?>",
                asset_id     : "<?php echo $asset_id ?>",
                notif_div    : "notif_div"
            });
		});
	
	</script>
	
</head>

<body>
    <div class='mleft15 mright15'>
        <div id='notif_div' class='mtop15'> </div>
        <div id='detail_notes' data-bind='detail_notes'> </div>
    </div>			
</body>
</html>
