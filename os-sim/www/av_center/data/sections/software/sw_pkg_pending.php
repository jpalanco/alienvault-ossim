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
require_once (dirname(__FILE__) . '/../../../config.inc');

session_write_close();


$res_si        = array();
$packages_info = array();
$release_info  = array();
$error_msg     = NULL;

try
{
    //Get software information

    $no_cache = ($id_section == 'sw_pkg_checking') ? TRUE : FALSE;
    $res_si   = Av_center::get_system_status($system_id, 'software', $no_cache);

    $packages_info = Av_center::get_packages_pending($system_id, TRUE);
    $release_info  = Av_center::get_release_info($system_id);
}
catch (\Exception $e)
{
    $error_msg = $e->getMessage();
}



?>

<div id='cont_sw_av'>
    <?php
    if (is_array($release_info) && !empty($release_info))
    {
        $r_class = (preg_match('/patch/i', $release_info['type'])) ? 'r_patch' : 'r_upgrade';
        ?>
        <div id='c_release_info'>
            <div>
                <div id='r_title'>
                    <?php echo "v".$release_info['version'].' '._('available').'!'?><span id='r_type' class='<?php echo $r_class?>'><?php echo $release_info['type']?>
                </div>
                <div id='r_desc'><?php echo implode('<br/>', $release_info['description'])?></div>
            </div>
        </div>
        <?php
    }
    ?>
    
    <div id='c_latest_update'>
        <?php
        if (!empty($res_si['last_update']) && $res_si['last_update'] != 'unknown')
        {
            $last_update = gmdate('Y-m-d H:i:s', strtotime($res_si['last_update'].' GMT') + (3600 * Util::get_timezone()));

            echo "<span class='bold'>"._('Latest System Update').": <span style='color:#4F8A10'>".$last_update."</span></span>";
        }
        else
        {
            echo "<span class='bold'>"._('Latest System Update').": <span style='color:#00529B'> -- </span></span>";
        }
        ?>
    </div>


    <div id='c_info_pkg'>
        <table class='t_info_pkg table_data' cellspacing='0' cellpadding='0'>
        <?php
        if (is_array($packages_info) && !empty($packages_info))
        {
            ?>
            <thead>
                <tr>
                    <th><?php echo _('Package')?></th>
                    <th class='th_lv'><?php echo _('Latest version')?></th>
                    <th class='th_sz'><?php echo _('Size')?></th>
                </tr>
            </thead>

            <tbody id='tbody_info_pkg'>
                <?php
                foreach ($packages_info as $pkg_data)
                {
                    ?>
                    <tr class='sw_pkg_row'>
                        <td class='td_p'  valign='middle'><?php echo $pkg_data['package-name'];?></td>
                        <td class='td_lv' valign='middle'><?php echo $pkg_data['package-candidate-version'];?></td>
                        <td class='td_sz' valign='middle'><?php echo $pkg_data['package-size'];?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
            <?php
        }
        else
        {
            if ($error_msg != NULL)
            {
                $bulb_image = "<img src='".AVC_PIXMAPS_DIR."/light-bulb_red.png'/>";
                $info       = $error_msg;
            }
            else
            {
                $bulb_image = "<img src='".AVC_PIXMAPS_DIR."/light-bulb_green.png'/>";
                $info       = _('System Updated');
            }
            ?>

            <thead>
                <tr>
                    <th class='t_header'><?php echo _('Alienvault Package Information')?></th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td class='no_info_pkg'>
                        <div>
                            <div><?php echo $bulb_image?></div>
                            <div><?php echo $info?></div>
                        </div>
                    </td>
                </tr>
            </tbody>
            <?php
        }
        ?>
        </table>
    </div>

    <div id='cont_pkg_bottom'>
        <div class='cont_info_update'>
            <div class='info_update'>
                <div class='info_update'></div>
            </div>
        </div>

        <div id='cont_buttons'>
            <div class='lbtn'>
                <input type='button' class='av_b_secondary' id='check_updates' name='check_updates' value='<?php echo _('Check for new updates')?>'/>
            </div>

            <div class='rbtn'>
                <?php 
                if ($res_si['packages']['pending_updates'] == TRUE)
                {
                    if ($res_si['packages']['pending_feed_updates'] == TRUE)
                    {
                        ?>
                        <input type='button' id='install_rules' name='install_rules' class='av_b_secondary' value='<?php echo _('Update feed only')?>'/>
                        <?php
                    }
                    ?>

                    <input type='button' id='install_updates_1' name='install_updates_1' value='<?php echo _('Upgrade')?>'/>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script type='text/javascript'>

    $('#check_updates').bind('click', function() { Software.check_updates()});

    <?php
    if ($id_section == 'sw_pkg_checking' && $res_si['status'] !== 0)
    {
        ?>
        var config_nt = { content: '<?php echo _('Checking process was not executed')?>',
                            options: {
                            type:'nf_error',
                            cancel_button: false
                            },
                            style: 'width: 80%; margin: auto;'
                        };

        nt = new Notification('nt_1', config_nt);


        notification    = nt.show();

        $('.info_update').html(notification);

        nt.fade_in(2000, '', '');
        setTimeout('nt.fade_out(4000, "", "");', 15000);
        <?php
    }

    if (is_array($packages_info) && !empty($packages_info))
    {
        ?>
        $('#install_updates_1').on('click', function() { Software.install_updates('update_system', 'install_updates_1') });
                        
        $('#install_rules').on('click', function() { Software.install_updates('update_system_feed', 'install_rules') });

        $('.t_info_pkg').dataTable({
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bPaginate": true,
            "bLengthChange": false,
            "bFilter": false,
            "bSort": true,
            "bInfo": true,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "asc" ]],
            "aoColumns": [
                { "bSortable": true },
                { "bSortable": true },
                { "bSortable": true }
            ],
            "oLanguage" : {
                "sProcessing": "<?php echo _('Processing') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No matching records found') ?>",
                "sEmptyTable": "<?php echo _('No information found') ?>",
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
            "fnInitComplete": function(oSettings, json){
                //Hack to display footer correctly
                if ($.browser.webkit)
                {
                    var width = $('.t_info_pkg').width();
                    $('.dt_footer').css('width', width);
                }


                var title = "<div class='dt_title' style='top:10px;'><?php echo _('Alienvault Package Information') ?></div>";
                $('div.dt_header').prepend(title);
            }
        });

        <?php
    }
    ?>
</script>