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


require_once '../deploy_common.php';


//Checking perms
check_deploy_perms();


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

$type = GET('type');
$id   = GET('id');


ossim_valid($id,		OSS_HEX,				'illegal:' . _("Network ID"));
ossim_valid($type,		"network", "server", 	'illegal:' . _("Asset Type"));

if ( ossim_error() )
{
	$error_msg = "Error: ".ossim_get_error();
	$error     = true;
	ossim_clean_error();
}


$type   = ($type == 'server') ? 1 : 4 ;

$sql    = "SELECT distinct HEX(h.id) as id, h.hostname, MAX(DATE(ac.timestamp)) as log
				FROM alienvault.host_types t, alienvault.host_net_reference hn, alienvault.host h  
				LEFT JOIN alienvault_siem.ac_acid_event ac ON ac.src_host = h.id
				WHERE h.id=hn.host_id AND h.id=t.host_id AND t.type=? AND hn.net_id=UNHEX(?)
				GROUP BY h.id
				";

$params = array(
		$type,
		$id	
	);


$asset_list= array();

if ($rs = $conn->Execute($sql, $params)) 
{
	while (!$rs->EOF) {
	
		try
        {
            $ips = Asset_host_ips::get_ips_to_string($conn, $rs->fields['id']);
        }
        catch(Exception $e)
        {
            $ips = '';
        }
        
		$asset_list[] = array(
							'id'   => $rs->fields['id'],
							'name' => $rs->fields["hostname"],
							'ip'   => $ips,
							'log'  => $rs->fields["log"]
						);

		$rs->MoveNext();
	}
}	

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>

	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<!-- JQuery -->
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	
	<!-- JQuery TipTip: -->
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>

	<!-- JQuery DataTables: -->
	<script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>

	<!-- Notification: -->
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	
	<script>

		var __cfg = <?php echo Asset::get_path_url() ?>;
		
		$(document).ready(function(){

			$('.datatable').dataTable( {
				"sScrollY": "325",
				"iDisplayLength": 8,
				"bLengthChange": false,
				"sPaginationType": "full_numbers",
				"bJQueryUI": true,
				"aaSorting": [[ 1, "asc" ]],
				"aoColumns": [
					{ "bSortable": true },
					{ "bSortable": true },
					{ "bSortable": false }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Processing') ?>...",
					"sLengthMenu": "",
					"sZeroRecords": "",
					"sEmptyTable": "<?php echo _('No assets available') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "",
					"sInfoEmpty": "",
					"sInfoFiltered": "",
					"sInfoPostFix": "",
					"sInfoThousands": ",",
					"sSearch": "",
					"sUrl": ""
				},
				fnDrawCallback : function(){
					$('.tip').tipTip();
					$('.odd, .even').off('click');
					$('.odd, .even').on('click', function()
					{
						var id = $(this).data('id');
		
						if(typeof(parent.GB_show) != 'function' || typeof(id) == 'undefined')
						{
							return false;
						}
		
						var url	  = __cfg.asset.views + "asset_form.php?id=" + id;
						var title = "<?php echo _('Edit Asset') ?>";
		
						parent.GB_show(title, url, "80%", "850", true);
		
					});
				}
				
			});
			
		});
		
	</script>
	
	<style>

		body 
		{
			background: transparent;
		}
		
		#container 
		{
			position:relative;
			overflow:hidden;
			padding: 5px;
		}
		
		.l_error, .l_error td, .l_error a
		{
			color: #D8000C; 
			font-weight:bold;
		}
		
	</style>
	
</head>

<body>

<?php
if ($error)
{
?>
	<div style='width:100%;margin:0 auto;'>
	
		<?php
		
		$config_nt = array(
			'content' => $error_msg,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => true
			),
			'style'   => 'width: 45%; margin: 20px auto; text-align: center;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		
		?>
		
	</div>
	
<?php
	die();
}
?>

<div id='container'>

	<div id='notification'></div>
	<div style='width:85%;margin:0 auto;padding-top:25px;'>
		<table id='dt_1' class='datatable table_list' width='100%' align="center">
			<thead>
				<tr>
					<th><?php echo _('Hostname') ?></th>
					<th><?php echo _('Ip') ?></th>
					<th><?php echo _('Latest Log') ?></th>
				</tr>
			</thead>
			<tbody>		
			<?php foreach($asset_list as $asset) 
			{ 
				echo "<tr data-id='".$asset['id']."'>";
				echo "<td>".$asset['name']."</td>";
				echo "<td>".$asset['ip']."</td>";
				echo "<td>". (($asset['log']) ? $asset['log'] : "<span class='l_error'>". _('Not received yet') ."</span>" ) ."</td>";
				echo "</tr>";
			}
			?>			
			</tbody>
		</table>
	</div>
</div>

</body>
</html>
<?php
$db->close();
?>
