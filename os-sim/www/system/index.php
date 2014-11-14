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


// Check permissions
if (!Session::am_i_admin())
{
	 $config_nt = array(
		'content' => _("You do not have permission to see this section"),
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => false
		),
		'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
	);

	$nt = new Notification('nt_1', $config_nt);
	$nt->show();

	die();
}

$level = intval(GET('level'));
if (!$level)
{
    $level = 1; // Default level: Info, Warning & Errors
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework") ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>

	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	<script type="text/javascript" src="/ossim/js/urlencode.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>

	<script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>

	<script type="text/javascript">

		GB_TYPE = 'w';
		function GB_onclose()
		{
			document.location.reload();
		}

		var click_delay  = 250, n_clicks = 0, click_timer = null;

		$(document).ready(function()
		{

			$('.table_data').dataTable( {
				"bProcessing": true,
				"bServerSide": true,
				"bDeferRender": true,
				"sAjaxSource": "status.php?level=<?php echo $level ?>",
				"iDisplayLength": 10,
				"sPaginationType": "full_numbers",
                "bLengthChange": true,
                "bFilter": false,
                "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
				"bJQueryUI": true,
				"aaSorting": [], // "aaSorting": [[ 0, "desc" ]],
				"aoColumns": [
					{ "bSortable": true  },
					{ "bSortable": true  },
					{ "bSortable": true  },
					{ "bSortable": false  },
					{ "bSortable": false  },
					{ "bSortable": false, "sClass": "left" }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Loading') ?>...",
					"sLengthMenu": "Show _MENU_ entries",
					"sZeroRecords": "<?php echo _('No matching entries found') ?>",
					"sEmptyTable": "<?php echo _('No entries available') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
					"sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
					"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
					"sInfoPostFix": "",
					"sInfoThousands": ",",
					"sSearch": "<?php echo _('Search') ?>:",
					"sUrl": "",
					"oPaginate": {
						"sFirst":    "<?php echo _('First') ?>",
						"sPrevious": "<?php echo _('Previous') ?>",
						"sNext":     "<?php echo _('Next') ?>",
						"sLast":     "<?php echo _('Last') ?>"
					}
				},
				"fnInitComplete": function() {

					$('#system_loading').hide();
					$('#system_list').show();

				},
				"fnDrawCallback" : function(oSettings) {

                    $.each(oSettings.aoData, function(index, row) {
                        if (row._aData.viewed == false)
                        {   
                            $('#'+row._aData.DT_RowId).addClass('bold');
                        }
                    });

                    $('.table_data tbody tr').on('click', function ()
                    {
                        n_clicks++;  //count clicks

                        var row = this;

                        $(this).disableTextSelect();

                        if(n_clicks === 1)
                        {
                            click_timer = setTimeout(function()
                            {
                                $(this).enableTextSelect();

                                n_clicks = 0;             //reset counter
                                
                                //perform single-click action
                                var ctime = oSettings.aoData[$('.table_data tbody tr').index(row)]._aData.ctime;
                                GB_show('<?php echo _("Message Detail") ?>','detail.php?id=' + $(row).attr('id') + '&date=' + encodeURIComponent(ctime), 600, '900');

                            }, click_delay);
                        }
                        else
                        {
                            clearTimeout(click_timer);  //prevent single-click action
                            n_clicks = 0;               //reset counter
                            //perform double-click action
                        }

                    }).on('dblclick', function(event)
                    {
                        event.preventDefault();
                    });

                },
				"fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
					oSettings.jqXHR = $.ajax( {
						"dataType": 'json',
						"type": "POST",
						"url": sSource,
						"data": aoData,
						"success": function (json) {
							$(oSettings.oInstance).trigger('xhr', oSettings);
							if (typeof(json.error) != 'undefined' && json.error != '')
							{
    						    show_notification('w_notif', json.error, 'nf_error', 5000);	
							}
							fnCallback( json );
						},
						"error": function(){
							//Empty table if error
							var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
							fnCallback( json );
						}
					} );
				}
			})

		});

  </script>

</head>

<body>

	<?php
       //Local menu
       include_once '../local_menu.php';
   	?>

	<div style='width:100%'>

        <div id='w_notif'></div>
        
		<div>

			<div class='loading_panel' id='system_loading'>
				<div style='padding: 5px; overflow: hidden;'>
					<?php echo _("Loading system status messages") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
				</div>
			</div>

            <div class="level">
                <form>
                <?php echo _("Filter By") ?><br/>
                    <select name="level" onchange="this.form.submit()">
                        <option value="1" <?php if ($level == 1) echo "selected" ?>><?php echo _('Info, Warning & Errors') ?></option>
                        <option value="2" <?php if ($level == 2) echo "selected" ?>><?php echo _('Warning & Errors') ?></option>
                        <option value="3" <?php if ($level == 3) echo "selected" ?>><?php echo _('Errors') ?></option>
                    </select>
                </form>
            </div>

			<div style='display:none;margin-top:15px' id='system_list'>
				<table class='noborder table_data' width="100%" align="left">
					<thead>
						<tr>

							<th>
								<?php echo _("Date"); ?>
							</th>

					        <th>
								<?php echo _("Level"); ?>
							</th>

							<th>
								<?php echo _("Type"); ?>
							</th>

							<th>
								<?php echo _("Component name"); ?>
							</th>

							<th>
								<?php echo _("Component IP"); ?>
							</th>

							<th>
								<?php echo _("Message");?>
							</th>

						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</body>
</html>
