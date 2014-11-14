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


Session::logcheck("configuration-menu", "Osvdb");


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework") ?> </title>	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

    <?php
    
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',                 'def_path' => TRUE),
        array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
        array('src' => 'jquery.dataTables.css',         'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => 'greybox.js',                    'def_path' => TRUE),
        array('src' => 'jquery.dataTables.js',          'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'js');
        
    ?>
    
    <style type='text/css'>
        
        #noti_Container 
        {
        	position:relative;     /* This is crucial for the absolutely positioned element */
        	border:none; /* This is just to show you where the container ends */
        	width:18px;
        	height:18px;
        	font-family: verdana;
        }
        
        .noti_bubble 
        {
        	position:absolute;    /* This breaks the div from the normal HTML document. */
        	top: -7px;
        	right: -8px;
        	padding:0px 2px 1px 2px;
        	background-color: #6698E2; /* you could use a background image if you'd like as well */
        	color:white;
        	font-weight: bold;
        	font-size: 8px;
        	text-align: center;
        	border-radius: 2px;
        	box-shadow:1px 1px 1px #aaa;
        }
        
        .title_kdb
        {
        	cursor:pointer;
        }
        
    </style>
	

	<script type="text/javascript">
		
		function deletesubmit(id) 
		{
			if (confirm("<?php echo _("Document with attachments will be deleted") . ". " . _("Are you sure") ?> ?")) 
			{
				GB_show("<?php echo _("Delete Document")?>","repository_delete.php?id_document="+id,"200","550");
			}	
		}
		
	
		function GB_onhide() 
		{ 
			document.location.reload();
		}
	
		// GreyBox
		$(document).ready(function()
		{
    		$('.table_data').css('min-height', '200px');
    		
			$('.table_data').dataTable( 
			{
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "documents_load.php",
				"iDisplayLength": 20,
				"sPaginationType": "full_numbers",
				"bLengthChange": false,
				"bJQueryUI": true,
				"aaSorting": [[ 2, "desc" ]],
				"aoColumns": [
					{ "bSortable": true },
					{ "bSortable": true, "sClass": "title_kdb" },
					{ "bSortable": true },
					{ "bSortable": false },
					{ "bSortable": false, sWidth: "50px" },
					{ "bSortable": false, sWidth: "50px" },
					{ "bSortable": false, sWidth: "100px" }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Loading') ?>...",
					"sLengthMenu": "Show _MENU_ entries",
					"sZeroRecords": "<?php echo _('No matching records found') ?>",
					"sEmptyTable": "<?php echo _('No data available in table') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ documents') ?>",
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
				"fnInitComplete": function() 
				{			
    				$('.table_data').css('min-height', '0px');
				},
				"fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
					oSettings.jqXHR = $.ajax( {
						"dataType": 'json',
						"type": "POST",
						"url": sSource,
						"data": aoData,
						"success": function (json) {
    						
							$(oSettings.oInstance).trigger('xhr', oSettings);
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
			
			
			GB_TYPE = 'w';
			
			//Attachements and Relationships GB
			$(document).on("click", "a.greybox", function()
			{
				var t = this.title || $(this).text() || this.href;
				
				GB_show(t, this.href, "80%", "600");
				
				return false;
			});

			$(document).on("click", "a.greyboxw", function()
			{
				var t = this.title || $(this).text() || this.href;
				
				GB_show(t, this.href, '80%', '1100');
				
				return false;
				
			});
			

			//View Document GB
			$(document).on("click", "td.title_kdb", function()
			{
				that = $(this).find('.greyboxw');
				
				$(that).trigger('click');
				
				return false;
			});
			
			//Change Owner GB
			$(document).on("click", "a.greyboxo", function()
			{
				var t = this.title || $(this).text() || this.href;
				
				GB_show(t, this.href, 180, 400);
				
				return false;
			});

		});
  
  </script>
     
</head>

<body>

	<?php 
       //Local menu      
       include_once '../local_menu.php';
   	?>
								
	<table id='kdb_list' class='noborder table_data' width='100%' align="center">
		<thead>
			<tr>
				<th>
					<?php echo _("ID"); ?>
				</th>
				
				<th>
					<?php echo _("Title"); ?></a>
				</th>
				
				<th>
					<?php echo _("Date"); ?>
				</th>
				
		        <th>
					<?php echo _("Owner"); ?></a>
				</th>
											
				<th>
					<?php echo _("Attachments");?>
				</th>
				
				<th>
					<?php echo _("Links"); ?>
				</th>
				
				<th>
					<?php echo _("Actions")?>
				</th>
			</tr>
		</thead>
		
		<tbody>
    		<tr><td></td></tr>
		</tbody>

	</table>

</body>
</html>
