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


Session::logcheck("configuration-menu", "PolicyPolicy");


$db     = new ossim_db();
$conn   = $db->connect();

//Version
$pro   = Session::is_pro();


$id    = POST('id');
$name  = POST('name');
$descr = POST('descr');

if($pro)
{
	$ctx = POST('ctx');
} 
else 
{
	$ctx = Session::get_default_ctx();
}

ossim_valid($id,		OSS_HEX,OSS_NULLABLE,							'illegal:' . _("Policy group id"));
ossim_valid($name,		OSS_ALPHA, OSS_PUNC, OSS_SPACE,					'illegal:' . _("Policy group name"));
ossim_valid($descr,		OSS_ALL, OSS_NULLABLE,                          'illegal:' . _("Description"));
ossim_valid($ctx, 		OSS_HEX,										'illegal:' . _("CTX"));
	
if (ossim_error()) 
{
	die(ossim_error());
}


?>

<html>
	<head>
		<title><?php echo _("OSSIM Framework")?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<META HTTP-EQUIV="Pragma" content="no-cache">
		<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	</head>
	
	<body>
                                                                                
		<h1> <?php echo _("New policy group") ?> </h1>

		<?php
		if (empty($id)) 
		{					
			Policy_group::insert($conn, $ctx, $name, $descr);			
			
		} 
		else
		{
			if($id == '00000000000000000000000000000000')
			{
				echo ossim_error(_("You cannot modify the default group."), AV_NOTICE);
				exit;
			}
			
			Policy_group::update($conn, $id, $name, $descr);			
		}
		
		$db->close();
		?>
		<p><?php echo _("Policy group successfully inserted")?></p>		
		
		<script>document.location.href="policygroup.php"</script>		

	</body>
</html>
