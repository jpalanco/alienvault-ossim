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


require_once dirname(__FILE__) . '/../../conf/config.inc';

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
    ?>

    <table class='table_data' id='agent_table'>
        <thead>
            <tr>
                <th class='th_mi'></th>
                <th class='th_id'><?php echo _('ID')?></th>
                <th class='th_name'><?php echo _('Agent name')?></th>
                <th class='th_name'><?php echo _('Asset')?></th>
                <th class='th_ip'><?php echo _('IP/CIDR')?></th>
                <th class='th_ci'><?php echo _('Current IP')?></th>
                <th class='th_cu'><?php echo _('Current User')?></th>
                <th class='th_status'><?php echo _('Status')?></th>
                <th style='text-align: center;'>
                    <?php echo _('Trend')?>&nbsp;<span style='position: relative; margin: 0px;font-size:10px;font-weight:normal'><?php echo '['._('Time UTC').']'?></span>
                </th>
            </tr>
        </thead>

        <tbody>
        </tbody>
    </table>

    <script type='text/javascript'>
        $('#agent_table').dataTable( {
            "bProcessing": true,
            "bServerSide": false,
            "bDeferRender": false,
            "sAjaxSource": "/ossim/ossec/providers/agents/dt_agents.php",
            "fnServerParams": function (aoData){
                aoData.push({"name": "sensor_id", "value": '<?php echo $sensor_id?>'});
            },
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
                { "bSortable": true  },
                { "bSortable": true  },
                { "bSortable": true  },
                { "bSortable": true  },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": true, },
                { "bSortable": false, "sWidth": "350px"}
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Processing') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No matching agents found') ?>",
                "sEmptyTable": "<?php echo _('No agents found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ agents') ?>",
                "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
                "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total agents') ?>)",
                "sInfoPostFix": "",
                "sInfoThousands": ",",
                "sSearch": "<?php echo _('Search') ?>",
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
            "fnRowCallback" : function(nRow, aData, iDrawIndex, iDataIndex)
            {
                //Bind Agent information handler
                $("td:nth-child(1)", nRow).addClass('td_mi');

                var help_icon = '<img class="info" src="<?php echo OSSEC_IMG_PATH.'/information.png'?>"/>';
                $("td:nth-child(1)", nRow).html(help_icon);

                get_agent_info($("td:nth-child(1)", nRow));

                $("td:nth-child(2)", nRow).attr('id', 'agent_'+ aData['DT_RowData']['agent_key']);

                //IDM data
                $("td:nth-child(6)", nRow).addClass('td_c_ip');
                $("td:nth-child(7)", nRow).addClass('td_c_ud');

                if ($("td:nth-child(8)", nRow).text().match(/active/i))
                {
                    get_idm_data(nRow, aData);
                }

                //Agent actions
                $('td:last', nRow).addClass('agent_actions');
                $('td:last', nRow).off('click').click(function() {
                    var id = $(this).attr("id");
                    get_action(id);
                });


                //SIEM trends
                $("td:last", nRow).attr('id', 'cont_plot_'+ aData['DT_RowData']['agent_key']);
                $("td:last", nRow).addClass('td_cp');

                var loading_icon = "<div class='cont_plot'><img class='loading_plot'  src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/></div>";
                $("td:last", nRow).html(loading_icon);

                load_hids_trend(nRow, aData);
            },
            "fnServerData": function (sSource, aoData, fnCallback, oSettings)
            {
                oSettings.jqXHR = $.ajax(
                {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json)
                    {
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

                        var json = $.parseJSON('{"sEcho": 0, "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback(json);
                    }
                });
            }
        });
    </script>
    <?php
}
?>
