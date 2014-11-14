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

$group_id   = (POST('group_id') != '') ? POST('group_id') : GET('group_id');
$num_assets = POST('num_assets');

$error_msg  = '';

ossim_valid($group_id,      OSS_HEX,                    'illegal: Group ID');
ossim_valid($num_assets,    OSS_DIGIT, OSS_NULLABLE,    'illegal: Num of assets');

if (ossim_error())
{
    die(ossim_error());
}

// Database Object
$db    = new ossim_db();
$conn  = $db->connect();

$group = Asset_group::get_object($conn, $group_id);

$group->can_i_edit($conn);

// Form is submited: Add to group
if ($num_assets > 0)
{
    for ($i = 0; $i < $num_assets; $i++)
    {
        if (valid_hex32(POST('host'.$i)))
        {
            $assets[] = POST('host'.$i);
        }
    }
    
    try
    {
        $group->add_host($conn, $assets); 
    }
    catch(Exception $e)
    {
        $error_msg = $e->getMessage(); 
    }
    
    
    if ($error_msg == '')
    {
        $msg = 'saved';
        ?>
        <script>
        if(typeof(top.frames['main'].force_reload) != 'undefined')
	    {
	        top.frames['main'].force_reload = 'snapshot,alarms,events';
	    }
        </script>
        <?php
    }
    else
    {
        $msg = 'error';
    }
}

$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('Asset Details')?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/top.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/assets/asset_details.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <script type='text/javascript'>

        <?php 
        require_once 'js/asset_details.js.php' 
        ?>

        var selected_hosts = new Array;
    
    	/**
    	* DOCUMENT READY
    	*/
    	$(document).ready(function()
        {
    		$('.table_data').dataTable( {
    			"bProcessing": true,
    			"bServerSide": true,
    			"sAjaxSource": "ajax/get_hosts.php?group_id=<?php echo $group_id ?>&asset_type=othergroups",
    			"iDisplayLength": 10,
    			"sPaginationType": "full_numbers",
    			"bLengthChange": false,
    			"bJQueryUI": true,
    			"aaSorting": [[ 1, "desc" ]],
    			"aoColumns": [
                    { "bSortable": false, sWidth: "30px" },
    				{ "bSortable": true },
    				{ "bSortable": false },
    				{ "bSortable": false },
    				{ "bSortable": false },
    				{ "bSortable": false }
    			],
    			oLanguage : {
    				"sProcessing": "<?php echo _('Loading') ?>...",
    				"sLengthMenu": "Show _MENU_ entries",
    				"sZeroRecords": "<?php echo _('No hosts found for this asset') ?>",
    				"sEmptyTable": "<?php echo _('No hosts found') ?>",
    				"sLoadingRecords": "<?php echo _('Loading') ?>...",
    				"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ hosts') ?>",
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
    				$('#hosts_loading').hide();
    				$('#hosts_list').show();
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
    						refresh_checks();
    					},
    					"error": function(){
    						//Empty table if error
    						var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
    						fnCallback( json );
    					}
    				} );
    			}
    		});
    
    		$('#cancel_button').click(function(e)
    	    {
    		    parent.GB_close();
    	    });
    
    	    $('#add_button').click(function(e)
    	    	{
    	        submit_assets_form();
    	    	});
        });
    
    </script>
    </head>
<body>

    <form id='assets_form' method='post'>
        <input type='hidden' name='group_id' value='<?php echo $group_id ?>'/>
        <input type='hidden' name='num_assets' id='num_assets' value='0'/>
    </form>
    
    <div class='loading_panel' id='hosts_loading'>
    	<div style='padding: 5px; overflow: hidden;'>
    		<?php echo _('Loading asset hosts')?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
    	</div>
    </div>
    
    <div id="av_info">
        <?php            
        if ($msg == 'saved')
        {
            $config_nt = array(
                'content' => _('Host added successfully'),
                'options' => array (
                    'type'          => 'nf_success',
                    'cancel_button' => TRUE
               ),
                'style'   => 'width: 80%; margin: auto; text-align:center;'
            ); 
                            
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        elseif ($msg == 'error')
        {
        	$config_nt = array(
                'content' => _('There was an error adding the assets selected'),
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => TRUE
               ),
                'style'   => 'width: 80%; margin: auto; text-align:center;'
           ); 
                            
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        ?>
    </div>
    
    <div id='add_assets_msg'></div>
    
    <!-- HOSTS LIST -->
    <div class='container_95'>
        <div style='display:none' id='hosts_list'>
            <table class='table_data' width='100%'>
            	<thead>
            		<tr>
            		    <th id="chkall"><input type="checkbox" name="allcheck" id="allcheck" onclick="checkall(this)"/></th>
            		    
            			<th>
            				<?php echo _('Host Name');?>
            			</th>
            			
            			<th>
            				<?php echo _('Group');?>
            			</th>
            			
            			<th>
            				<?php echo _('IP');?>
            			</th>
            			
            			<th>
            				<?php echo _('Device Type');?>
            			</th>
            			
            			<th>
            				<?php echo _('FQDN / Alias');?>
            			</th>
            		</tr>
            	</thead>
            	<tbody>
            	</tbody>
            </table>
        </div>
        
        <!-- BUTTONS -->
        <div class='ad_g_popup_buttons'>
        
            <div class='detail_header_left'><input type='button' class="av_b_secondary" id='cancel_button' value='<?php echo _('Cancel') ?>'/></div>
            
            <div class='detail_header_right'><input type='button' id='add_button' value='<?php echo _('Add') ?>'/></div>
            
        </div>
    </div>

</body>

</html>

<?php
/* End of file add_to_group.php */
/* Location: ./asset_details/modules/add_to_group.php */