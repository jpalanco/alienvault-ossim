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

Session::logcheck('environment-menu', 'PolicyHosts');

function is_allowed_format ($type_uf)
{
	$types = '/force-download|octet-stream|text|csv|plain|spreadsheet|excel|comma-separated-values/';
	
	if (preg_match ($types, $type_uf) == FALSE)
	{
		return FALSE;
	}
	else
	{
		return TRUE;
	}
}

$import_type  = POST('import_type');
$ctx          = POST('ctx');
$path         = '../tmp/';
$current_user = md5(Session::get_session_user());
$file_csv     = $path.$current_user.'_assets_import.csv';


if ($import_type != 'hosts' && $import_type != 'welcome_wizard_hosts')
{
    ?>
	<script type='text/javascript'>
		parent.show_error('<?php echo _('Error! Import Type not found')?>');
	</script>
	<?php
	exit();
}


if (!isset($_POST['ctx']) || empty($_POST['ctx']))
{
	?>
	<script type='text/javascript'>
		parent.show_error('<?php echo _('You must select an entity')?>');
	</script>
	<?php
}

		 
if (Session::is_pro())
{
	if (!valid_hex32($ctx) || Acl::entityAllowed($ctx) < 1)
    {
		$msg_error = (empty($ctx)) ? _('You must select an entity') : _('Entity not allowed');            
        ?>
        
        <script type='text/javascript'>
            parent.show_error('<?php echo $msg_error?>');
        </script>
        <?php
        exit();
    }
}
else
{
	$ctx = Session::get_default_ctx();
}


if (!empty ($_FILES['file_csv']['name']))
{
	if ($_FILES['file_csv']['error'] > 0)
	{
		$msg_error  = _('Unable to upload file. Return Code').': '.$_FILES['file_csv']['error'];
	}
	else
	{
		if (!is_allowed_format($_FILES['file_csv']['type']))
		{
			$msg_error  = _('File type \''.$_FILES['file_csv']['type'].'\' not allowed');
        }
		elseif (@move_uploaded_file($_FILES['file_csv']['tmp_name'], $file_csv) == FALSE)
		{
			$msg_error = (empty ($msg_error)) ? _('Unable to upload file') : $msg_error;
        }
    }
}
else
{
	$msg_error  = _('Filename is empty');
}


if (!empty($msg_error))
{
    ?>
    <script type='text/javascript'>
        parent.show_error('<?php echo $msg_error?>');
    </script>
    <?php
}
else
{
    $_SESSION['file_csv'] = $file_csv;
  
    ?>
    <script type='text/javascript'>
        parent.import_assets_csv('<?php echo $import_type?>');
    </script>
    <?php
}