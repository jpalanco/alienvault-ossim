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
Session::logcheck("configuration-menu", "ComplianceMapping");


$table      = GET('table');
$ref        = GET('ref');
$field      = GET('field');
$compliance = GET('compliance');
$text       = GET('text');
$version    = intval(GET('pci_version'));


ossim_valid($field,         OSS_ALPHA,                                              'illegal:' . _("Field value"));
ossim_valid($table,         OSS_ALPHA, OSS_SCORE,                                   'illegal:' . _("Table value"));
ossim_valid($ref,           OSS_ALPHA, OSS_SCORE, OSS_DOT, '-',                     'illegal:' . _("Ref value"));
ossim_valid($compliance,    OSS_ALPHA, OSS_DIGIT,                                   'illegal:' . _("Compliance value"));
ossim_valid($text,          OSS_TEXT, OSS_SPACE, OSS_PUNC_EXT, OSS_NULLABLE, '-',   'illegal:' . _("Text"));

if (ossim_error()) 
{
	die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect(); 

$update_data = false;

if ($compliance=="PCI") 
{
    Compliance_pci::set_pci_version($version);
}

if (GET('save') == "1") 
{
	if ($compliance=="PCI") 
	{
    	Compliance_pci::save_text($conn,$table,$ref,$text);
	}
	elseif($compliance=="ISO27001") 
	{
    	Compliance_iso27001::save_text($conn,$table,$ref,$text);
	}

	$update_data = true;
}

if ($compliance=="PCI") 
{
    $text = Compliance_pci::get_text($conn,$table,$ref);
}
elseif($compliance=="ISO27001") 
{
    $text = Compliance_iso27001::get_text($conn,$table,$ref);
}

?>

<html>
    
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> - <?php echo _("Compliance")?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv=="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>

<body>
    <br/>
    <table class="noborder" align="center" style="background-color:white">
    	<form name="ffield" method="get">
    		<input type="hidden" value="1" name="save">
    		<input type="hidden" value="<?php echo $compliance?>" name="compliance"/>
    		<input type="hidden" value="<?php echo $table?>" name="table"/>
    		<input type="hidden" value="<?php echo $ref?>" name="ref"/>
    		<input type="hidden" value="<?php echo $field?>" name="field"/>
    		<input type="hidden" value="<?php echo $version?>" name="pci_version"/>
    		
    		<tr>
        		<th>
            		<?php echo _("Insert the text for")." '"._("$field")."'"?>
                </th>
            </tr>
    		<tr>
    			<td class="nobborder" style="text-align:center">
    				<textarea name="text" cols="50" rows="10"><?php echo $text?></textarea>
    			</td>
    		</tr>
    		<tr>
        		<td class="nobborder" style="text-align:center">
            		<input type="submit" value="<?php echo _("Update")?>">
        		</td>
            </tr>
    		
    	</form>
    </table>
</body>

</html>

<?php
$db->close();
