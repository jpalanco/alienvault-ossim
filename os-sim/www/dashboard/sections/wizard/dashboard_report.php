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

Session::logcheck("report-menu", "ReportsReportServer");

if(!Session::is_pro())
{
	die(_('Report section is only available in professional version'));
}

$me           = Session::get_session_user();
$db           = new ossim_db();
$dbconn       = $db->connect();

$creports     = array();
$result       = $dbconn->Execute("SELECT login, name, value FROM user_config where category LIKE 'custom_report%' ORDER BY name ASC");
//Wizard Perms
$wizard_perms = Av_report::get_report_permissions($dbconn);
		
while (!$result->EOF)
{
	$available         = false;
		
	$unserializedata   = unserialize($result->fields["value"]);
   
	$user_perm         = $unserializedata["user"];
	$entity_perm       = $unserializedata["entity"];
	
	$available         = Av_report::check_report_availability($result->fields["login"], $user_perm, $entity_perm, $wizard_perms);

	if ($available == true)
	{
		$creports[] = $result->fields;
	}

	$result->MoveNext();
}


?>    		
<table border="0" class="noborder" width="90%" align="center" cellspacing="0" cellpadding="0">			
	<?php
	if (count($creports) > 0)
	{
	?>
		<tr>
			<td class="nobborder"><br>			
				<div style='width:90%;margin:0 auto;text-align:center;'>
				<table width="100%" align="center" class='dataTable table_data'>
					<thead>
							<th><?php echo _('Available Reports') ?></th>
						</thead>
					<tbody>
					<?php
					$color = 0;
													
					foreach ($creports as $report)
					{
						$value = unserialize($report["value"]);
						
						if ($value["entity"] != "-1" && $value["entity"] != "") 
						{ 
							$permissions = Session::get_entity_name($dbconn, $value["entity"]);
						}
						elseif ($value["user"]!="-1")
						{ 
							$permissions = ( $value["user"]=="0" ) ? _("All user") : $value["user"];  
				        }
				        
						$tooltip  = "<span style='font-weight:bold'>"._("Owner:")."</span> <span style='font-weight:normal'>".$report["login"]."</span>";
						$tooltip .= "<br><span style='font-weight:bold'>"._("Available for:")."</span> <span style='font-weight:normal'>".$permissions."</span>";
						$tooltip .= "<br><span style='font-weight:bold'>"._("Creation date:")."</span> <span style='font-weight:normal'>".$value["cdate"]."</span>";
						
						if( $value["cdate"]== $value["mdate"] ) 
						{
							$tooltip .= "<br><span style='font-weight:bold'>"._("Modification date:")."</span> <span style='font-weight:normal'>-</span>";
						}
						else 
						{
							$tooltip .= "<br><span style='font-weight:bold'>"._("Modification date:")."</span> <span style='font-weight:normal'>".$value["mdate"]."</span>";
						}
						
						$tooltip  .= "<br><span style='font-weight:bold'>"._("Assets:")."</span> <span style='font-weight:normal'>".$value["assets"]."</span>";

						// Dates
						if ($value["date_range"] == "custom" || $value["date_range"] == "" ) 
						{
							$tooltip  .= "<br><span style='font-weight:bold'>"._("Date from:")."</span> <span style='font-weight:normal'>".$value["date_from"]."</span>";
							$tooltip  .= "<br><span style='font-weight:bold'>"._("Date to:")."</span> <span style='font-weight:normal'>".$value["date_to"]."</span>";
						} 
						else 
						{
							$tooltip  .= "<br><span style='font-weight:bold'>"._("Date range:")."</span> <span style='font-weight:normal'></span>";
							$tooltip  .= Av_report::get_date_name_range($value["date_range"]);
							$tooltip  .= "</span>";										
						}
						
						//Row styles
						$class      = ( $color%2 == 0 ) ? "lightgray" : "blank"; 
						$_report_id = base64_encode($report["name"]."###".$report["login"]);
						?>
						<tr class="<?php echo $class?>" idcolor='<?php echo $color?>' onclick="choose_option('<?php echo $_report_id ?>');" >
							
							<td class='left'>
								<a id='sel_<?php echo $_report_id ?>' href="javascript:void(0);" title="<?php echo $tooltip?>" class="scriptinfo"><?php echo $report["name"]?></a>
							</td>
						</tr>		
						<?php									
						$color++;
					} ?>
					</tbody>
					</table>
					</div>
				</td>
			</tr>
				
	<?php 
	}
	else
	{
		?>
		<tr>
			<td class="nobborder" style="text-align:center;padding:2px 5px 2px 5px;"><?php echo _("No custom report available")?></td>
		</tr>
		<?php
	}	
	?>
</table>	

<?php 
$db->close($dbconn);
