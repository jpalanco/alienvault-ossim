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
Session::logcheck("configuration-menu", "ComplianceMapping");

function bbdd_exists($conn, $name)
{
    $exists = 0;
    
    $query = ossim_query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    
    $rs = $conn->Execute($query,array($name));
    if ($rs)
    {
        $exists = (!$rs->EOF) ? 1 : 0;
    }
    else
    {
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }

    return $exists;
}

$action = GET('action');
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("action"));
if (ossim_error())
{
    die(ossim_error());
}

if ($action == "launch") 
{
    $db = new ossim_db();  
    $conn = $db->connect();

    //Util::execute_command('/usr/bin/perl -I"/usr/share/ossim/compliance/scripts/datawarehouse/perl" "/usr/share/ossim/compliance/scripts/datawarehouse/OSSIM_ETL.job_ReportingETL.pl" --context=Default 2>&1 &');
    //Util::execute_command('/usr/bin/perl /usr/share/ossim/compliance/scripts/datawarehouse/iso27001sid.pl 2>&1 &');
    Util::execute_command('echo "CALL compliance_aggregate()" | ossim-db >/dev/null 2>&1 &');

    $db->close($conn);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Compliance </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

<form action="mod_scripts.php" style="margin-left:15px;">
<input type="hidden" name="action" value="<?php echo ($inprogress) ? "" : "launch"?>">
<table class="transparent">
	<?php if ($action == "launch") { ?>
	<tr><td class="nobborder"><?php echo _("The compliance scripts has been successfully launched") ?></td></tr>
	<?php } else { ?>
	<tr>
		<td class="nobborder"><?php echo _("Click here to launch now the compliance scripts") ?></td>
	</tr>
	<tr><td class="nobborder"><input type="submit" value="<?php echo _("Run") ?>"></td></tr>
	<?php } ?>
</table>
</form>

</body>
</html>
