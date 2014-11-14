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

Session::logcheck('analysis-menu', 'EventsForensics');
Session::logcheck('report-menu', 'ReportsReportServer');

require_once '../report/os_report_common.php';


$url       = $_POST['url']        = GET('url');
$id        = $_POST['id']         = GET('data');
$type      = $_POST['type']       = GET('type');
$date_from = $_POST['date_from']  = GET('date_from');
$date_to   = $_POST['date_to']    = GET('date_to');


$validate = array(
	'url'          => array('validation'=>'OSS_URL_ADDRESS',        'e_message' => 'illegal:' . _('Url')),
	'id'           => array('validation'=>'OSS_TEXT,OSS_PUNC_EXT',  'e_message' => 'illegal:' . _('Report')),
	'type'         => array('validation'=>'OSS_ALPHA',              'e_message' => 'illegal:' . _('Type')),
	'date_from'    => array('validation'=>'OSS_DATETIME',           'e_message' => 'illegal:' . _('Date From')),
	'date_to'  	   => array('validation'=>'OSS_DATETIME',           'e_message' => 'illegal:' . _('Date To'))
);

$validation_errors = validate_form_fields('GET', $validate);

$data['status'] = 'OK';
if (is_array($validation_errors) && !empty($validation_errors))
{
	$data['status'] = 'error';
	$data['data']   = $validation_errors;
}
else
{
	$d_reports = get_freport_data($id);

	if (empty($d_reports))
	{
		$data['status'] = 'error';
		$validation_errors['id'] = _('Invalid Report ID');
	}
	else
	{
		if ($date_from == '') 
		{
		  $date_from = date('Y-m-d', strtotime('-10 year'));
	    }
	    
	    if ($date_to == '')
	    {   
	       $date_to   = date('Y-m-d');
	    }
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="pragma" content="no-cache"/>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7"/>

	<title><?php echo _('Forensics Console: Report Launcher')?></title>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>

	<style type='text/css'>
		.f_report 
		{
    		margin:0px; 
    		display:inline;    		
		}

		.numrows
		{ 
		    margin-right: 5px;
    	}

		#info_action 
		{
			width: 80%;
			margin: 10px auto;
			height: 30px;
		}

		table 
		{ 
		    background: transparent;
		}

		input[type='checkbox'] 
		{ 
    		display: none; 
		}

		#closebtn
		{ 
		    display:none;     		
		}

	</style>

	<?php
		if ($data['status'] != 'error')
		{
			?>
			<script type='text/javascript'>

				function close_wnd() 
				{
					if (typeof parent.GB_hide == 'function') 
					{
					     parent.GB_hide();
					}
				}

				function launch_form() 
				{
			        var type = '<?php echo Util::htmlentities($type) ?>';

					$('#closebtn').show();

					if (type != 'pdf')
					{
						$('#info_action').html('<?php echo _('Please wait for report download!')?><br/>');

						var address_type = (type == 40) ? '<?php echo intval(preg_replace("/UniqueAddress\_Report(\d)/","\\1", $id)) ?>': '';

						var action  = 'csv.php?rtype='+type;
							action += ( address_type != '' ) ? '&addr_type='+address_type : '';

						$('form').attr("action", action);
						$('form').submit();
					}
					else
					{
						download_pdf('<?php echo Util::htmlentities($id) ?>');
					}
				}


				function generate() 
				{
					var num = $('#numrows option:selected').val();

					$('#forensics_plot').attr('src','/ossim/forensics/base_plot.php');
					
					if ( num > 50 || '<?php echo Util::htmlentities($id) ?>' == 'Sensors_Report' || '<?php echo Util::htmlentities($id) ?>' == 'UniqueEvents_Report')
					{
						var url = '<?php echo str_replace("&amp;", "&", Util::htmlentities($url)) ?>&numevents='+num;
						$('#info_action').html("<img src='../pixmaps/loading.gif' style='border: none; width: 16px; margin-right: 5px' align='absmiddle'/><?php echo _("Loading data, please wait a few seconds.")?><br/>");
						$('#forensics').attr('src',url);
					}
					else
					{
						launch_form();
					}
				}
				

				function check_data(action, data)
				{
					var ret = $.ajax({
						url: "/ossim/report/os_report_actions.php",
						global: false,
						type: "POST",
						data: "action="+action+ "&data="+data,
						dataType: "text",
						async:false
						}
					).responseText;

					return ret;
				}


				function download_pdf(id)
				{
					var data  =  $('#fr_forensics').serialize();

					$.ajax({
						type: "POST",
						url: "/ossim/report/os_report_run.php",
						data: "report_id="+ id + "&section=forensics&" + data,
						beforeSend: function( xhr ) {
							$('#info_action').html("<div style='position: relative;'><div style='position:absolute; top: 10px; left: 5px;'><img src='../pixmaps/loading.gif' style='border: none; width: 16px; margin-right: 5px' align='absmiddle'/><span><?php echo _("Generating report. This may be take a few minutes, please wait...")?></span></div></div>");
						},
						success: function(data){
							$("#info_action").html('');

							var status = data.replace("\n", "");
								status = status.split('###');


							if (status[0] != '' && status[0] == 'error')
							{
								var config_nt = { content: status[1],
									options: {
										type:'nf_error',
										cancel_button: false
									},
									style: 'width: 90%; margin: auto; text-align:center;'
									};

								nt            = new Notification('nt_1', config_nt);
								notification  = nt.show();

								$('#info_action').html(notification);

								nt.fade_in(2000, '', '');

								return;
							}


							var data   = id+'###'+data
								data   = Base64.encode(data);

							var st_chk = check_data('check_file', data);

							if (st_chk == 1)
							{
								document.location.target = '_blank';
								document.location.href   = '/ossim/report/os_report_run.php?data='+data;
							}
							else
							{
								var config_nt = { content: st_chk,
									options: {
										type:'nf_error',
										cancel_button: false
									},
									style: 'width: 90%; margin: auto; text-align:center;'
									};

								nt            = new Notification('nt_1', config_nt);
								notification  = nt.show();

								$('#info_action').html(notification);

								nt.fade_in(2000, '', '');

								$('#closebtn').hide();
							}
						}
					});
				}

				$(document).ready(function() {

					$('#generate').click(function(){ generate(); });

					$('#closebtn').click(function(){ close_wnd(); });

				});

			</script>
			<?php
		}
	?>
</head>
<body>
	<?php


	if ($data['status'] == 'error')
	{
		$txt_error = '<div>'._('We Found the following errors').":</div>
					  <div style='padding:10px;'>".implode('<br/>', $validation_errors).'</div>';

		$config_nt = array(
				'content' => $txt_error,
				'options' => array (
					'type'          => 'nf_error',
					'cancel_button' => FALSE
				),
				'style'   => 'margin: 20px auto; width: 80%; text-align: center;'
			);


		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
    }
	else
	{
		?>
		<form id='fr_forensics' name='fr_forensics' method='POST'>

			<input type="hidden" name="reportUser" value="<?php echo $_SESSION["_user"]?>"/>
			<input type="hidden" name="reportUnit" value="<?php echo $d_reports['parameters'][1]['default_value']?>"/>

			<?php
				if (count($d_reports['parameters']) == 5)
				{
					$id    = $d_reports['parameters'][2]['id'];
					$name  = $d_reports['parameters'][2]['name'];
					$value = $d_reports['parameters'][2]['default_value'];
					?>
					<input type="hidden" name="<?php echo $id?>" id="<?php echo $name?>" value="<?php echo $value?>"/>

					<?php
				}
			?>

			<input type="hidden" name="date_from" value="<?php echo Util::htmlentities($date_from)?>"/>
			<input type="hidden" name="date_to" value="<?php echo Util::htmlentities($date_to)?>"/>


			<?php
			//Subreports

			if (count($d_reports["subreports"]) > 0)
			{
				foreach ($d_reports["subreports"] as $sr_key => $sr_data)
				{
					if ($sr_data['id'] != $r_data['report_id'])
					{
						echo "<input type='checkbox' name='sr_".$sr_data["id"]."' id='".$sr_data['id']."' checked='checked'/>";
					}
				}
			}
			?>

			<div id='info_action'></div>

			<table align="center" class="noborder">
				<tr>
					<td class="nobborder">
						<?php echo _('Rows #')?>
						<select name="numrows" id="numrows">

							<?php
							//Special Case
							if ($id == 'UniqueCountryEvents_Report')
							{
								?>
								<option value="99999" selected='selected'>All</option>
								<?php
							}
							//Events report crashes with too much events
							elseif ($id == 'Events_Report')
							{
								?>
								<option value="50" selected='selected'>50</option>
								<option value="100">100</option>
								<option value="250">250</option>
								<option value="500">500</option>
								<?php
								if ($type == 33) 
								{ // 33 is csv
    								?>
    								<option value="1000">1000</option>
    								<option value="2500">2500</option>
    								<option value="5000">5000</option>
    								<?php
								}
							}
							else
							{
								?>
								<option value="50" selected='selected'>50</option>
								<option value="100">100</option>
								<option value="250">250</option>
								<option value="500">500</option>
								<option value="1000">1000</option>
								<option value="2500">2500</option>
								<option value="5000">5000</option>
								<option value="99999">All</option>
								<?php
							}
							?>
						</select>
					</td>
					<td class="nobborder">
						<?php $txt_type = ( $type == "pdf" ) ? "PDF" : "CSV"; ?>
						<input type="button" class="small"  name="generate" id='generate' value="<?php echo $txt_type?>"/>
						<input type="button" class="small"  name="closebtn" id="closebtn" value="<?php echo _("Close")?>"/>
					</td>
				</tr>
			</table>
		</form>


		<iframe id="forensics"      style="display:none"></iframe>
		<iframe id="forensics_plot" style="display:none"></iframe>
		<?php
	}
?>

</body>
</html>
