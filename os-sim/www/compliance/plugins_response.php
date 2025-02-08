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

$db   = new ossim_db();
$conn = $db->connect();


$ref        = explode ("_",GET('ref'));
$version    = intval(GET('pci_version'));
$compliance = (GET('compliance') != "") ? GET('compliance') : POST('compliance');

switch($compliance)
{
	case "PCI":
	    Compliance_pci::set_pci_version($version);
		$groups = Compliance_pci::get_groups($conn);
		break;
	case "ISO27001":
		$groups = Compliance_iso27001::get_groups($conn);
		break;
}

$sids = $groups[$ref[0]]['subgroups'][$ref[1]]['SIDSS_Ref'];
$sids = preg_replace("/,$/", "", $sids);

ossim_valid($sids, OSS_DIGIT, ',', 'illegal:' . _("sids"));

if (ossim_error()) 
{
    $db->close();
	die(ossim_error());
}

$plugin_list = Plugin_sid::get_list($conn,"WHERE plugin_id = 1505 AND sid in ($sids)");

if (count($plugin_list) > 0) 
{
?>
    <table width="100%" align="center" class="data_sources">
        <?php
        $i    = 0;
        $size = count($plugin_list);
        foreach ($plugin_list as $p) 
        { 
            $class   = ( $i%2 == 0 ) ? "odd" : "even";
            $class  .= ( $i == $size -1 ) ? " noborder left" : " left";
            ?>
            <tr>
                <td class='<?php echo $class?>'>
                    <?php echo $p->get_name()?>
                </td>
            </tr>
            <?php
            $i++;
        } 
        ?>
    </table>
<?php 
} 
else 
{ 
?>
    <table width="100%">
        <tr>
            <td class="nobborder" style="padding:10px;text-align:center">
                <b><?php echo _("No directives found")?></b>
            </td>
        </tr>
    </table>
<?php 
} 

$db->close();
