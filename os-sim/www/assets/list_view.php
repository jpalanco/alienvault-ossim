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

//Getting parameters
$type = GET('type');

ossim_valid($type, OSS_LETTER, 'illegal:' . _('List Type'));

if (ossim_error())
{
    die(ossim_error());
}

//Network Configuration
if ($type == 'network')
{
    Session::logcheck('environment-menu', 'PolicyNetworks');
    
    $op['list_title']      = _('Networks');
    $op['button_title']    = _('Add Network') . ' &nbsp;&#x25be;';
    $op['button_dropdown'] = '#dropdown-1';
    $op['button_export']   = TRUE;
    $op['js_file']         = '/net/js/net_list.js.php';
    $op['delete_all_msg']  = Util::js_entities(_("You are about to delete networks. This is something that cannot be undone. Are you sure you would like to delete these networks?"));
    $op['dt_ajax_url']     = AV_MAIN_PATH . '/assets/ajax/load_nets_result.php';
    $op['dt_item']         = 'networks';
    $op['dt_col_names']    = array (
        _('Network Name'),
        _('Owner(s)'),
        _('CIDR'),
        _('Sensors'),
        _('Alarms'),
        _('Vulnerabilities'),
        _('Events'),
        _('Detail')
    );
                                            
    $op['dt_col_config'] = array (
        array('bSortable' => TRUE,  'sClass' => 'left'),
        array('bSortable' => FALSE, 'sClass' => 'left'),
        array('bSortable' => FALSE, 'sClass' => 'left', "sWidth" => "150px"),
        array('bSortable' => FALSE, 'sClass' => 'left'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center', "sWidth" => "80px")
    );
}
//Asset Group Configuration
elseif ($type == 'group')
{
    Session::logcheck('environment-menu', 'PolicyHosts');
    
    //Asset Group Configuration
    $op['list_title']      = _('Asset Groups');
    $op['button_title']    = _('Add Group');
    $op['button_dropdown'] = '';
    $op['button_export']   = FALSE;
    $op['js_file']         = '/group/js/group_list.js.php';
    $op['delete_all_msg']  = Util::js_entities(_("You are about to delete asset groups. This is something that cannot be undone. Are you sure you would like to delete these groups?"));
    $op['dt_ajax_url']     = AV_MAIN_PATH . '/assets/ajax/load_groups_result.php';
    $op['dt_item']         = 'groups';
    $op['dt_col_names']    = array (
        _('Group Name'),
        _('Owner(s)'),
        _('Hosts'),
        _('Alarms'),
        _('Vulnerabilities'),
        _('Events'),
        _('Detail')
    );
                                            
    $op['dt_col_config'] = array (
        array('bSortable' => TRUE,  'sClass' => 'left'),
        array('bSortable' => FALSE, 'sClass' => 'left'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center'),
        array('bSortable' => FALSE, 'sClass' => 'center', "sWidth" => "80px")
    );
}
//Launch notifitcaion with error
else
{
    throw new Exception(_('Invalid Option Chosen'));
}

$db   = new ossim_db();
$conn = $db->connect();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _('AlienVault USM'); ?> </title>
        <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
    
        <?php
    
            //CSS Files
            $_files = array();
    
            $_files[] = array('src' => 'av_common.css',                 'def_path' => TRUE);
            $_files[] = array('src' => 'jquery-ui.css',                 'def_path' => TRUE);
            $_files[] = array('src' => 'tipTip.css',                    'def_path' => TRUE);
            $_files[] = array('src' => 'jquery.dataTables.css',         'def_path' => TRUE);
            $_files[] = array('src' => 'jquery.dropdown.css',           'def_path' => TRUE);
            $_files[] = array('src' => '/assets/assets_list_view.css',  'def_path' => TRUE);

            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array();
    
            $_files[] = array('src' => 'jquery.min.js',                  'def_path' => TRUE);
            $_files[] = array('src' => 'jquery-ui.min.js',               'def_path' => TRUE);
            $_files[] = array('src' => 'utils.js',                       'def_path' => TRUE);
            $_files[] = array('src' => 'notification.js',                'def_path' => TRUE);
            $_files[] = array('src' => 'token.js',                       'def_path' => TRUE);
            $_files[] = array('src' => 'jquery.tipTip.js',               'def_path' => TRUE);
            $_files[] = array('src' => 'greybox.js',                     'def_path' => TRUE);
            $_files[] = array('src' => 'jquery.dataTables.js',           'def_path' => TRUE);
            $_files[] = array('src' => 'jquery.dropdown.js',             'def_path' => TRUE);
            $_files[] = array('src' => '/assets/js/list_view.js.php',    'def_path' => FALSE);
            $_files[] = array('src' => $op['js_file'],                   'def_path' => FALSE);

            Util::print_include_files($_files, 'js');
    
        ?>
        
        <script type='text/javascript'>
            
            //Value stored in the search input
            var __search_val = '';
            
            
            $(document).ready(function() 
            {
                //Adding functionallity to the botton Add
                $('#button_add').on('click', function()
                {
                    add_button_action(this);
                });

                //Exportation button
                $('#export').on('click', function()
                {
                    export_net(); 
                });
                
                /* DATA TABLES */
                datatables_assets = $('.table_data').dataTable( 
                {
                    "bProcessing": true,
                    "bServerSide": true,
                    "bDeferRender": true,
                    "sAjaxSource": "<?php echo $op['dt_ajax_url'] ?>",
                    "iDisplayLength": 20,
                    "bLengthChange": true,
                    "sPaginationType": "full_numbers",
                    "bFilter": false,
                    "aLengthMenu": [[10, 20, 50], [10, 20, 50]],
                    "bJQueryUI": true,
                    "aaSorting": [[ 0, "desc" ]],
                    "aoColumns": <?php echo json_encode($op['dt_col_config']) ?>,
                    oLanguage : 
                    {
                        "sProcessing": "&nbsp;<?php echo _('Loading '.$op['dt_item']) ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
                        "sLengthMenu": "&nbsp;Show _MENU_ entries",
                        "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
                        "sEmptyTable": "&nbsp;<?php echo _('No '. $op['dt_item'] .' found in the system') ?>",
                        "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                        "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_') ?>",
                        "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 '.$op['dt_item']) ?>",
                        "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total '.$op['dt_item']) ?>)",
                        "sInfoPostFix": "",
                        "sInfoThousands": ",",
                        "sSearch": "<?php echo _('Search') ?>:",
                        "sUrl": "",
                        "oPaginate": 
                        {
                            "sFirst":    "",
                            "sPrevious": "&lt; <?php echo _('Previous') ?>",
                            "sNext":     "<?php echo _('Next') ?> &gt;",
                            "sLast":     ""
                        }
                    },         
                    "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) 
                    {
                        oSettings.jqXHR = $.ajax( 
                        {
                            "dataType": 'json',
                            "type": "POST",
                            "url": sSource,
                            "data": aoData,
                            "beforeSend": function()
                            {
                                datatables_loading(true);
                            },
                            "success": function (json) 
                            {
                                datatables_loading(false);

                                <?php
                                // Modify the 'Delete' button status
                                // This option will be disable if the user has host or net permissions
                                $host_perm_where = Asset_host::get_perms_where();
                                $net_perm_where  = Asset_net::get_perms_where();
                                
                                if (empty($host_perm_where) && empty($net_perm_where))
                                {
                                ?>
                                
                                if (json.iTotalDisplayRecords > 0)
                                {
                                    $('#delete_all').removeClass('disabled');
                                }
                                else
                                {
                                    $('#delete_all').addClass('disabled');
                                }
                                
                                <?php
                                }
                                ?>
                                
                                $(oSettings.oInstance).trigger('xhr', oSettings);

                                fnCallback(json);

                            },
                            "error": function(data)
                            {
                                //Check expired session
                                var session = new Session(data, '');
                                
                                if (session.check_session_expired() == true)
                                {
                                    session.redirect();
                                    return;
                                }
                                
                                datatables_loading(false);

                                $('#delete_all').addClass('disabled');
                                
                                var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }');
                                
                                fnCallback( json );
                            }
                        });
                    },
                    "fnServerParams": function ( aoData )
                    {
                        aoData.push( { "name": "search", "value": __search_val } );
                    }
                });
                
                //Events for the datatables row
                $(document).on('click', '.table_data tr', function()
                {
                    $(this).disableTextSelect();
                    
                    n_clicks++;  //count clicks
                
                    var row = this;
                    
                    //Single Click Event
                    if(n_clicks === 1) 
                    {
                        click_timer = setTimeout(function() 
                        {
                            $(this).enableTextSelect();
                            
                            n_clicks = 0;             //reset counter
                            tr_click_function(row);  //perform single-click action    
            
                        }, click_delay);
                    } 
                    else //Double Click Event
                    {
                        clearTimeout(click_timer);  //prevent single-click action
                        n_clicks = 0;               //reset counter
                        tr_dblclick_function(row);  //perform double-click action
                    }
                    
                }).on('dblclick', '.table_data tr', function(e)
                {
                    e.preventDefault();
                });
                
                //Event for the detail img --> Go to the detail
                $(document).on('click', '.detail_img', function(e)
                {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var id = $(this).parents('tr').attr('id');
                    
                    link_to(id);
                });

                // Search by ENTER key
                $("#list_search").keyup(function (e) 
                {
                    if (e.keyCode == 13) 
                    {
                        __search_val = $(this).val();
                        
                        datatables_assets.fnDraw();
                    }
                });
                
                //Event on the clear input seacrch
                $("#clear_search_input").on('click', function()
                {
                    $("#list_search").val('');
                    
                    if (__search_val != '')
                    {
                        __search_val = '';
                        datatables_assets.fnDraw();
                    }
                    
                });

                // Delete all action
                $("#delete_all").on('click', function()
                {
                    if ($('#delete_all').hasClass("disabled"))
                    {
                         return false;
                    }
                
                    var msg_confirm = '<?php echo $op['delete_all_msg'] ?>';
                    var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
                   
                    av_confirm(msg_confirm, keys).done(delete_all);
                });
                
            });
            // End of document.ready

        </script>
    </head>
    
    
    <body>
    
        <div id="main_container">
        
            <div class="left_side">
                <div id='search_wrapper'>
                    <input id='list_search' type="text" value="" placeholder="Search">
                    <div id='clear_search_input'></div>
                </div>
            </div>
            
            <div class="content">
                
                <div id="asset_notif"></div>
                
                <div id='content_header'>
                    <div id='list_title'>
                        <?php echo $op['list_title'] ?>
                    </div>
                    
                    <div id='list_button'>                                                               
                       
            			<a href='javascript:;' id='button_add' class='button' data-dropdown="<?php echo $op['button_dropdown'] ?>">
                            <?php echo $op['button_title'] ?>
                        </a>
                		            			
            			<?php
            			if ($op['button_export'] === TRUE)
                        {
                            ?>
                            <img id='export' src="../pixmaps/forensic_download.png" border="0">
                            <?php
                        }
                        ?>

                    <img id='delete_all' class='disabled' src="/ossim/pixmaps/delete.png" border="0"/>
                        
                    </div>
                </div>
                
                <div id='content_result'>
                    <table class='noborder table_data'>
                        <thead>
                            <tr>
                                <?php 
                                foreach ($op['dt_col_names'] as $col)
                                {
                                    echo "<th>$col</th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <!-- Do not delete, this is to show the first "Loading" message -->
                            <tr><td></td></tr>
                        </tbody>
                        
                    </table>

                </div>  
            </div>
            
        </div>
        
        <div id="dropdown-1" class="dropdown dropdown-close dropdown-tip dropdown-anchor-right">
    		<ul class="dropdown-menu">
    			<?php
    			if (Session::can_i_create_assets() == TRUE)
    			{
        			?>
        			<li><a href="#1" onclick='add_net();'><?php echo _('Add Network') ?></a></li>
        			<?php
    			}
    			?>
    			<li><a href="#2" onclick='import_csv();'><?php echo _('Import CSV') ?></a></li>
    		</ul>
        </div>
        
    </body>

</html>
<?php 
$db->close();