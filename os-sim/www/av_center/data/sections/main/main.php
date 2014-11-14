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


//Config File
require_once dirname(__FILE__) . '/../../../config.inc';

session_write_close();

if (GET('action') == 'autocomplete')
{
    $db       = new ossim_db();
    $conn     = $db->connect();
    
    $avc_list = Av_center::get_avc_list($conn);
    $db->close();

    $av_components = '';

    if (is_array($avc_list['data']) && $avc_list['status'] == 'success')
    {
        $cont = 0;
        foreach ($avc_list['data'] as $system_id => $data)
        {
            $av_components .= ($cont > 0) ? ",\n " : "\n";
            
            $hostname  = $data['name'];
            $host_ip   = $data['admin_ip'];
            $profiles  = $data['profile'];
            
            $av_components .= '{"txt" : "'.$hostname.' ['.$host_ip.']", "profiles": "'.$profiles.'", "id" :"'.$system_id.'" }';

            $cont++;
        }
    }

    echo json_encode($av_components);
    exit();
}
elseif (POST('action') == 'get_tree')
{
    $type     = POST('tree_type');
        
    $db       = new ossim_db();
    $conn     = $db->connect();
    
    $avc_tree = new Avc_tree($conn, $type);
    $db->close();
        
    if ($avc_tree->is_valid_order($type) == FALSE)
    {        
        $data['status']  = 'error';
        $data['data']    = _("Ordenation not valid");
    }
    else
    {
        session_start();
        $_SESSION['tree_object'] = $avc_tree;
        session_write_close();

        $json = $avc_tree->get_tree();
        
        $data['status']  = 'success';
        $data['data']    = $json['data'];
    }
    
    echo json_encode($data);
    exit();
}
elseif (POST('action') == 'new_branch')
{
    $type   = POST('type');
    $key    = POST('key');
    $page   = POST('page');
    
    session_start();    
    if (!isset($_SESSION['tree_object']) || !is_object($_SESSION['tree_object']))
    {
        $db       = new ossim_db();
        $conn     = $db->connect();
        
        $avc_tree = new Avc_tree($conn, $type);
        $db->close();
        
        if ($avc_tree->is_valid_order($type) == FALSE)
        {
            $t_load_error = utf8_encode(_('Load error'));
            echo '{"title" : "<span>'.$t_load_error.'</span>", "icon" : "", "addClass" : "bold_red dynatree-statusnode-error",  "key" : "error",  "noLink" : true}';
            exit();
        }
    }

    $avc_tree = $_SESSION['tree_object'];
    session_write_close();
    echo $avc_tree->get_branch($key, $page);
}
elseif (POST('action') == 'display_avc')
{
    $db       = new ossim_db();
    $conn     = $db->connect();
    $avc_list = Av_center::get_avc_list($conn);

    $db->close();

    if ($avc_list['status'] == 'error')
    {
        echo "error###"._("Error retrieving Alienvault Component");
        exit();
    }

    echo "success###";
    ?>
        <div id='avc_list_container'>
            <div id='header_avc_list'>
                <div id='l_hal'><?php echo _('Alienvault Components Information')?></div>
                <div id='r_hal'></div>
                <div id='c_hal'><div id='c_hal_content'></div></div>
            </div>
            <div id='body_avc_list'>
                <table id='t_avcl' class='table_data'>
                    <thead id='thead_avcl'>
                        <th id='th_nodename'><?php echo _('Node Name')?></th>
                        <th id='th_status'><?php echo _('Status')?></th>
                        <th id='th_ram'><?php echo _('RAM Usage')?></th>
                        <th id='th_swap'><?php echo _('Swap Usage')?></th>
                        <th id='th_cpu'><?php echo _('CPU Usage')?></th>
                        <th id='th_su'><?php echo _('New Updates')?></th>
                        <th id='th_mi'></th>
                    </thead>

                    <tbody id='tbody_avcl'>
                        <?php
                        $cont = 0;
                        
                        // Before calling to Util::get_default_uuid();
                        // default_uuid was reading 'admin_ip' into ossim_setup.conf and database system table
                        // Now it is unified to get the /usr/bin/alienvault-system-id
                        $local_system = strtolower(Util::get_system_uuid());

                        if (is_array($avc_list['data']))
                        {
                            foreach ($avc_list['data'] as $system_id => $avc_data)
                            {
                                $tr_class = ($cont % 2 == 0) ? 'avcl_odd' : 'avcl_even';

                                /*
                                  Default status
                                   - AJAX call will update each system status (Memory, CPU, Packages, ..)
                                */

                                if ($local_system != $system_id)
                                {
                                    $can_delete    = '' ;
                                    $trash_tooltip =  _('Delete System');
                                }
                                else
                                {
                                    $can_delete     = 'disabled';
                                    $trash_tooltip  =  _('Local System cannot be deleted');
                                }

                                //CSS classes
                                $tr_class   .= ' tr_unknown';
                                $st_class    = 'st_retrieving';
                                $st_class_2  = 'data_right';
                                $td_class    = 'td_unknown';
                                $st_text     = _('RETRIEVING');

                                $vp_class    = ' progress-grey';
                                $vp_percent  = '0.00';
                                

                                $node_name  = "<span class='bold'>".$avc_data['name']."</span>";

                                if (!empty($avc_data['vpn_ip']))
                                {
                                    $node_name .= '<span> ['._('IP').': '.$avc_data['admin_ip'].' - '._('VPN IP').': '.$avc_data['vpn_ip'].']</span>';
                                }
                                else
                                {
                                    $node_name .= ' ['.$avc_data['admin_ip'].']';
                                }

                                $profiles = array('Server'    => array(utf8_encode(_('Server'))       , AV_PIXMAPS_DIR.'/theme/host_os.png'),
                                                  'Sensor'    => array(utf8_encode(_('Sensor'))       , AV_PIXMAPS_DIR.'/theme/server.png'),
                                                  'Framework' => array(utf8_encode(_('Web Interface')), AV_PIXMAPS_DIR.'/theme/framework.png'),
                                                  'Database'  => array(utf8_encode(_('Database'))     , AV_PIXMAPS_DIR.'/theme/storage.png'));
                                ?>
                                
                                <tr id='row_<?php echo $system_id?>' class='<?php echo $tr_class;?>'>
                                    <td class='<?php echo $td_class?> td_nodename'>
                                        <div><a class='more_info'><?php echo $node_name?></a></div>
                                        
                                        <div style='padding-top: 10px; font-size: 10px;'>
                                            <?php
                                            $p_cont = 0;
                                            foreach ($profiles as $p_id => $p_data)
                                            {
                                                echo ($p_cont > 0) ? '<span> | </span>' : '';
                                                
                                                $st_profile = (preg_match("/$p_id/i", $avc_data['profile'])) ? '' : "class='disabled'";
                                                
                                                echo "<img src='".$p_data[1]."' align='absmiddle' style='margin-right: 3px' $st_profile/><span $st_profile>".$p_data[0]."</span>";
                                                $p_cont++;
                                            }
                                            ?>
                                        </div>
                                    </td>

                                    <td class='td_status <?php echo $td_class?>'>
                                        <div class='data_left'>
                                            <div class='<?php echo $st_class;?>'></div>
                                        </div>
                                        <div class='<?php echo $st_class_2;?>'>
                                            <img class='loading_status' src='/ossim/pixmaps/loading.gif'/>
                                            <?php echo $st_text?>
                                        </div>
                                        <div class='data_clear'></div>
                                    </td>

                                    <td class='<?php echo $td_class?> td_ram'>
                                        <?php
                                        echo Avc_utilities::create_vprogress_bar('mem_used_vpbar_'.$system_id, '', '17px', '35px', $vp_percent, $vp_class);
                                        ?>
                                    </td>

                                    <td class='<?php echo $td_class?> td_swap'>
                                        <?php
                                        echo Avc_utilities::create_vprogress_bar('swap_used_vpbar_'.$system_id, '', '17px', '35px', $vp_percent, $vp_class);
                                        ?>
                                    </td>

                                    <td class='<?php echo $td_class?> td_cpu'>
                                        <?php
                                        echo Avc_utilities::create_vprogress_bar('cpu_vpbar_'.$system_id, '', '17px', '35px', $vp_percent, $vp_class);
                                        ?>
                                    </td>
                                    
                                    <td class='<?php echo $td_class?> td_su'>  --  </td>
                                    
                                    <td class='<?php echo $td_class?> td_mi'>
                                        
                                        <a class='more_info' class='disabled' title='<?php echo _('System Detail')?>'>
                                            <img src='<?php echo AV_PIXMAPS_DIR?>/show_details.png'>
                                        </a>
                                        
                                        <a class='delete_system <?php echo $can_delete?>' title='<?php echo $trash_tooltip?>'>
                                            <img src='<?php echo AV_PIXMAPS_DIR?>/delete.png'>
                                        </a>
                                        
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        else
                        {
                            ?>
                            <tr>
                                <td class='td_no_av_components' colspan='7'>
                                    <?php
                                        $config_nt = array(
                                            'content' => '<div>'._('No Alienvault Components Found').'</div>',
                                            'options' => array (
                                                'type'          => 'nf_error',
                                                'cancel_button' => false
                                           ),
                                            'style'   => 'width: 800px; margin: auto; text-align:center;'
                                       ); 

                                        $nt = new Notification('nt_1', $config_nt);
                                        $nt->show();
                                    ?>
                                    
                                </td>
                            </tr>
                            <?php
                        }
                        
                        $cont++;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php
    if (is_array($avc_list['data']))
    {
        ?>
        <script type='text/javascript'>

            $('#t_avcl').dataTable({
                "iDisplayLength": 10,
                "sPaginationType": "full_numbers",
                "bPaginate": true,
                "bLengthChange": false,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "bJQueryUI": true,
                "aaSorting": [[ 1, "asc" ]],
                "aoColumns": [
                    { "bSortable": true },
                    { "bSortable": true },
                    { "bSortable": false },
                    { "bSortable": false },
                    { "bSortable": false },
                    { "bSortable": false },
                    { "bSortable": false }
                ],
                oLanguage : {
                    "sProcessing": "<?php echo _('Processing') ?>...",
                    "sLengthMenu": "Show _MENU_ entries",
                    "sZeroRecords": "<?php echo _('No matching records found') ?>",
                    "sEmptyTable": "<?php echo _('No Alienvault Components') ?>",
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
                "fnCreatedRow": function(nRow, aData, iDataIndex) {

                    var system_id = $(nRow).attr('id').replace('row_', '');
                    
                    if (system_id != null)
                    {
                        Main.update_system_information(system_id);
                    }
                },
                "fnDrawCallback": function(oSettings) {

                    $('.delete_system').click(function()
                    { 
                        if ($(this).hasClass('disabled'))
                        {
                            return false;
                        }
                        
                        Main.delete_system($(this).parents("tr").attr('id'));
                    });
                }
            });
        </script>
        <?php
    }
}
?>