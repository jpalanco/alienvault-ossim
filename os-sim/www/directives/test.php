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

set_time_limit(0);
ob_implicit_flush();

require_once 'av_init.php';

Session::logcheck("configuration-menu", "CorrelationDirectives");

$engine_id    = (GET('engine_id') != '') ? GET('engine_id') : POST('engine_id');
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _('Engine ID'));
if (ossim_error())
{
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
</head>	
<body>
<br/>
<table class="transparent" align="center">
	<tr>
		<td><b><?php echo _('Test Results') ?></b>: <span id="status"></span></td>
	</tr>
	<tr>
		<td id="msg"></td>
	</tr>
	<tr>
		<td colspan="3"></td>
	</tr>
	<tr>
		<td colspan="3" align="center"><input type="button" value="<?php echo _('Close') ?>" onclick="parent.GB_close()"/></td>
	</tr>
</table>

</body>
</html>
<?php

$directive_editor = new Directive_editor($engine_id);
$conf = $GLOBALS["CONF"];
if (Session::is_pro() && $conf->get_conf("alienvault_mssp", false) == "1" && count($available_engines = $directive_editor->get_available_engines()) > 1)
{
    $engines = $available_engines;
}
else
{
    $engines = array(_MAIN_PATH."/$engine_id" => "Default");
}

$errors = array();

foreach ($engines as $engine_dir => $engine_name)
{
    $engine_id = preg_replace("/.*\/([a-f0-9\-]+)/", "\\1", $engine_dir);
    
    if (count($engines) > 1)
    {
        echo "<script type='text/javascript'>$('#msg').html('$engine_name');</script>";
    }
    
    $_errors = $directive_editor->test($engine_id);
    foreach ($_errors as $error)
    {
        $errors[] = $error;
    }
}

if (count($errors) > 0)
{
    echo "<script type='text/javascript'>
                    $('#msg').html(\"".count($errors)." errors found. [<a href='' onclick='$(\\\"#details\\\").toggle();return false'>View Details</a>]<div style='display:none' id='details'><br/>".implode("<br/>", $errors)."</div>\");
                    $('#status').html(\"<img src='../pixmaps/warning.png' align='absmiddle'/>\");
                  </script>";
}
else
{
    echo "<script type='text/javascript'>
                    $('#msg').html(\""._("All OK")."\");
                    $('#status').html(\"<img src='../pixmaps/tick.png' align='absmiddle'/>\");
                  </script>";
}

/* End of file test.php */
/* Location: ./directives/test.php */
