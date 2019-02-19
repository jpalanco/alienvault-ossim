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

Session::logcheck("configuration-menu", "ToolsBackup");

$tz = Util::get_timezone();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('Backup')?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	
	<?php
	
	//CSS Files
	$_files = array(
        array('src' => 'av_common.css',                                            'def_path' => TRUE),
        array('src' => 'jquery-ui.css',                                            'def_path' => TRUE),
        array('src' => 'jquery.dataTables.css',                                    'def_path' => TRUE)
    );
	
	Util::print_include_files($_files, 'css');
	
	
	//JS Files
	$_files = array(
        array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
        array('src' => 'jquery.number.js.php',                          'def_path' => TRUE),
        array('src' => 'utils.js',                                      'def_path' => TRUE),
        array('src' => 'notification.js',                               'def_path' => TRUE),
        array('src' => 'jquery.dataTables.js',                          'def_path' => TRUE),
        array('src' => 'av_storage.js.php',                             'def_path' => TRUE),
        array('src' => 'av_table.js.php',                               'def_path' => TRUE)
    );
	
	Util::print_include_files($_files, 'js');
	
	?>
	
	<script type='text/javascript'>

	    var __dt = null;

        $(document).ready(function() {
            __dt = $("[data-bind='av_table_logs']").AV_table(
        	    {
        			"ajax_url"   : "<?php echo AV_MAIN_PATH . "/av_backup/providers/dt_logs.php" ?>",
        		    "dt_params"  :
        		    {
        			    "aoColumns": 
        			    [
                        { "bSortable": false, "sClass": "left", "sWidth": "130px" },
                        { "bSortable": false, "sClass": "left", "sWidth": "100px" },
                        { "bSortable": false, "sClass": "left", "sWidth": "130px" },
                        { "bSortable": false, "sClass": "left" }
        	            ],
        	            "bPaginate": false,
        	            "oLanguage": {
        	                "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
        	                "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Entries') ?>",
        	                "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
        	                "sEmptyTable": "&nbsp;<?php echo _('No entries found in the system') ?>",
        	                "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
        	                "sInfo": "",
        	                "sInfoEmpty": "",
        	                "sInfoFiltered": "",
        	                "sInfoPostFix": "",
        	                "sInfoThousands": ",",
        	                "sSearch": "<?php echo _('Search')?>",
        	                "sUrl": ""
        	            }
                },
                "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
                {
                    var _color = aData['dtRowData'].background_color;
                    $('td', nRow).css('background-color', _color);
                },
                "load_params": 
        		    [
        			    { "name": "top",    "value": function(){ return $('#top').val(); } },
        			    { "name": "status", "value": function(){ return $('#status').val(); } }
        		    ]
        	    });

            $("[data-bind='status_select']").on("change",function()
            {
                __dt.reload_table();
            });

            $("[data-bind='top_select']").on("change",function()
            {
                __dt.reload_table();
            });
		});
	</script>
</head>
<body>

<div class='backup_logs_container'>

<div>
    <?php echo _('Showing the latest') ?>
    <select name='top' id='top' data-bind='top_select'>
        <option value='100'>100</option>
        <option value='250'>250</option>
        <option value='500'>500</option>
    </select>
    <?php echo _('logs') ?>
</div>

<div data-name="logs" data-bind="av_table_logs">
	
    	<table class="table_data" id="table_data_logs">
    	    <thead>
            <tr>
                <th>
                    <?php echo _('Date') ?>
                </th>
                
                <th>
                    <?php echo _('Backup Type') ?>
                </th>
                
                <th>
                    <?php echo _('Status') ?>&nbsp;
                    <select name='status' id='status' data-bind='status_select'>
                        <option value=''><?php echo _('All') ?></option>
                        <option value='1'>INFO</option>
                        <option value='2'>WARNING</option>
                        <option value='3'>ERROR</option>
                    </select>
                </th>
                
                <th>
                    <?php echo _('Message') ?>
                </th>
            </tr>
        </thead>
    	    <tbody>
    	        <tr>
    	            <td colspan='4'></td>
    	        </tr>            
    	    </tbody>
    	</table>
	
</div>

</div>

</body>
</html>
