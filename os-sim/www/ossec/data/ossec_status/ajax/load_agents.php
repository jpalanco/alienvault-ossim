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


require_once dirname(__FILE__) . '/../../../conf/config.inc';

$m_perms  = array('environment-menu', 'environment-menu');
$sm_perms = array('EventsHids', 'EventsHidsConfig');

if (Session::menu_perms($m_perms, $sm_perms))
{
    $sensor_id = POST('sensor_id'); 

    ossim_valid($sensor_id, OSS_HEX,  'illegal:' . _('Sensor ID'));
    
    if (!ossim_error())
    {
        $db    = new ossim_db();
        $conn  = $db->connect();
                        
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            ossim_set_error(_('Error! Sensor not allowed'));
        }
        
        $db->close();
    }
        
    
    if (empty($sensor_id) || ossim_error())
    {
        echo "<table id='agent_table'><tr><td><div style='margin: auto; padding:20px 0px; text-align:center;'>"._('No agents found')."</div></td></tr></table>";
        exit();
    }
    
    //Current sensor
    $_SESSION['ossec_sensor'] = $sensor_id;
    
    $idm_enabled = (isset($_SESSION['_idm']) && !empty($_SESSION['_idm'])) ? TRUE : FALSE;
    ?>
    
    <table class='table_data' id='agent_table'>
        <thead>
            <tr>
                <th class='th_mi'></th>
                <th class='th_id'><?php echo _('ID')?></th>
                <th class='th_name'><?php echo _('Name')?></th>
                <th class='th_ip'><?php echo _('IP/CIDR')?></th>
                <?php 
                if ($idm_enabled)
                { 
                    ?>
                    <th class='th_ci'><?php echo _('Current IP')?></th>
                    <th class='th_cu'><?php echo _('Current User@Domain')?></th>
                    <?php 
                } 
                ?>
                <th class='th_status'><?php echo _('Status')?></th>
                <th style='text-align: center;'>
                    <?php echo _('Trend')?>&nbsp;<span style='position: relative; margin: 0px;font-size:10px;font-weight:normal'><?php echo '['._('Time UTC').']'?></span>
                </th>
            </tr>
        </thead>

        <tbody>
            <?php
            $_SESSION['agent_info'] = array();

            try
            {
                $agents = Ossec_agent::get_list($sensor_id);
                
                if (is_array($agents) && !empty($agents))
                {
                    foreach ($agents as $agent_id => $a_data)
                    {
                        if (empty($a_data))
                        {
                            continue;
                        }

                        $a_unique_id = md5($agent_id);
                        $_SESSION['_agent_info'][$a_unique_id] = $a_data;

                        $agent_actions = Ossec_agent::get_actions($agent_id, $a_data);
                        ?>

                        <tr id="<?php echo 'cont_agent_'.$agent_id?>">
                            <td><img class="info" src="<?php echo OSSEC_IMG_PATH.'/information.png'?>"/></td>
                            <td id='agent_<?php echo $a_unique_id?>'><?php echo $agent_id?></td>
                            <td><?php echo $a_data['name']?></td>
                            <td><?php echo $a_data['ip']?></td>
                            <?php
                            if ($idm_enabled)
                            {
                                ?>
                                <td>
                                    <div style='text-align: center !important'> - </div>
                                </td>

                                <td>
                                    <div style='text-align: center !important'> - </div>
                                </td>
                                <?php
                            }
                            ?>
                            <td><?php echo $a_data['status']?></td>
                            <td class='td_cp' id='cont_plot_<?php echo $a_unique_id?>'>
                                <div class='cont_plot'>
                                    <img class='loading_plot'  src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>
                                </div> 
                            </td>
                        </tr>
                        <?php
                    }
                }

                session_write_close();
            }
            catch(Exception $e)
            {
                ;
            } 
        ?>
        </tbody>
    </table>
    
    <script type='text/javascript'>
        $('#agent_table').dataTable({
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
                { "bSortable": false },
                { "bSortable": true },
                { "bSortable": true },
                <?php
                if ($idm_enabled)
                { 
                    ?>
                    { "bSortable": false },
                    { "bSortable": false },
                    <?php
                }
                ?>
                { "bSortable": false },
                { "bSortable": true },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Processing') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No matching records found') ?>",
                "sEmptyTable": "<?php echo _('No agents found') ?>",
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
            "fnInitComplete": function(oSettings)
            {
                var title = "<div class='dt_title' style='top:10px;'><?php echo _('Agent Information') ?></div>";

                $('div.dt_header').append(title);

                $('#sensors').removeAttr('disabled');

                $('#sensors').off('change');
                $('#sensors').change(function(){
                    load_agent_information();
                });
            },
            "fnRowCallback": function(nRow, aData, iDrawIndex, iDataIndex)
            {
                //Load trend graphs

                if ($(nRow).find("td:last img").hasClass('loading_plot'))
                {
                    load_SIEM_trends(iDataIndex, nRow);
                }

                //IDM data
                if ($(nRow).find("td").length > 6)
                {
                    if(!$(nRow).find("td:nth-child(5)").hasClass('td_c_ip') && !$(nRow).find("td:nth-child(6)").hasClass('td_c_ud'))
                    {
                        $(nRow).find("td:nth-child(5)").addClass('td_c_ip');
                        $(nRow).find("td:nth-child(6)").addClass('td_c_ud');

                        get_idm_data(nRow);
                    }
                }

                $(nRow).find("td:nth-child(1)").addClass('td_mi');

                if ($(nRow).find("td:nth-child(1) img").hasClass('info'))
                {
                    get_agent_info($(nRow).find("td:nth-child(1)"));
                }
            }
        });
    </script>
    <?php
}
?>