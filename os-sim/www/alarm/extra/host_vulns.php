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

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$ip = GET('ip');

ossim_valid($ip,   OSS_IP_ADDR, OSS_NULLABLE,         'illegal:' . _("Ip"));

if ( ossim_error() )  die(ossim_error());



/* connect to db */
$db     = new ossim_db();
$conn   = $db->connect();

$vulns = Vulnerabilities::get_latest_vulns_data($conn, $ip);

$images   = array ("1" => "risk1.gif", "2" => "risk2.gif", "3" => "risk3.gif", "6" => "risk6.gif", "7" => "risk7.gif");
$levels   = array("1" => "Serious", "2" => "High", "3" => "Medium", "6" => "Low", "7" => "Info");
$bgcolors = array ("1" => "#FFCDFF", "2" => "#FFDBDB", "3" => "#FFF283", "6" => "#FFFFC0", "7" => "#FFFFE3");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>

	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	
	<!-- JQuery DataTables: -->
	<script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>

	<style>

		
	</style>
	
	<script>

	
	$(document).ready(function() {
		
		$('#table_vulns').dataTable( {
				"bFilter": true,
				"iDisplayLength": 10,
				//"sScrollY": "270px",
				"sPaginationType": "full_numbers",
				"bLengthChange": false,
				"bJQueryUI": true,
				"aaSorting": [[ 4, "desc" ]],
				"aoColumns": [
					{ "bSortable": true },
					{ "bSortable": true },
					{ "bSortable": true },
					{ "bSortable": true },
					{ "bSortable": true }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Processing') ?>...",
					"sLengthMenu": "Show _MENU_ entries",
					"sZeroRecords": "<?php echo _('No matching records found') ?>",
					"sEmptyTable": "<?php echo _('No data available in table') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "<?php echo _('Total: _TOTAL_ Vulns') ?>",
					"sInfoEmpty": "<?php echo _('Total: 0 Vulns') ?>",
					"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
					"sInfoPostFix": "",
					"sInfoThousands": ",",
					"sUrl": "",
					"oPaginate": {
						"sFirst":    "<?php echo _('First') ?>",
						"sPrevious": "<?php echo _('Previous') ?>",
						"sNext":     "<?php echo _('Next') ?>",
						"sLast":     "<?php echo _('Last') ?>"
					}
				},
				"fnInitComplete": function() {
				
					$('div.dt_header').remove();
					
				}
			});
			
	});
	</script>
	
</head>

<body id='body_scroll'>

	<div style='margin: 10px auto;text-align:center;width:95%'>
		<table id='table_vulns' class='noborder table_data' width='100%' align="center">
			<thead>
				<tr>

					<th>
						<?php echo _("Scan Time"); ?>
					</th>		
										
					<th>
						<?php echo _("Vuln Name"); ?>
					</th>
					
					<th>
						<?php echo _("VulnID"); ?>
					</th>
					
					<th>
						<?php echo _("Service"); ?>
					</th>
					
					<th>
						<?php echo _("Severity"); ?>
					</th>
					
				</tr>
			</thead>
			<tbody>
			<?php 
				foreach($vulns as $v)
				{
					$date      = $v['date'];
					$vname     = $v['plugin'];
					$vid       = $v['pluginid'];
					$vservice  = $v['service'];
					
					$vseverity = $levels[$v['risk']] . " <img align='absmiddle' src='/ossim/vulnmeter/images/". $images[$v['risk']] ."' style='border: 1px solid ; width: 25px; height: 10px;'>";

			?>
					<tr style="background:<?php echo $bgcolors[$v['risk']] ?>">
						<td nowrap>
							<?php echo $date ?>
						</td>

						<td style='text-align:left;'>
							<?php echo $vname ?>
						</td>
						
						<td>
							<?php echo $vid ?>
						</td>
						
						<td>
							<?php echo $vservice ?>
						</td>
						
						<td style="text-align:right" nowrap>
							<?php echo $vseverity ?>
						</td>
						
					</tr>
				
			<?php
			
				}
			?>
			</tbody>
		</table>
	</div>

</body>

</html>
<?php
$db->close($conn);
?>


