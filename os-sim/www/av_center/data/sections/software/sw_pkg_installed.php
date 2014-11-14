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

$packages_info = array();
$error_msg     = NULL;

try
{
    $packages_info = Av_center::get_packages_installed($system_id);
}
catch(Exception $e)
{
    $error_msg = $e->getMessage();
}
?>


<div id='cont_sw_av'>
    <table class='table_data t_info_pkg'>
    <?php

    if (is_array($packages_info) && !empty($packages_info))
    {
        ?>
        <thead>
           <tr>
                <th><?php echo _('Package')?></th>
                <th class='th_iv'><?php echo _('Installed version')?></th>
                <th><?php echo _('Description')?></th>
            </tr>
        </thead>

        <tbody id='tbody_info_pkg'>
            <?php
            foreach ($packages_info as $package => $pkg_data)
            {
                ?>
                <tr>
                    <td class='td_p'  valign='middle'><?php echo $package;?></td>
                    <td class='td_iv' valign='middle'><?php echo $pkg_data['version'];?></td>
                    <td valign='middle'><?php echo $pkg_data['description'];?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        <?php
    }
    else
    {
        ?>
        <thead>
            <tr>
                <th class='t_header'><?php echo _('Packages Installed')?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class='no_info_pkg'>
                    <div>
                        <div><img src='<?php echo AVC_PIXMAPS_DIR?>/light-bulb_red.png'/></div>
                        <div>
                            <?php echo $error_msg?>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
        <?php
    }
    ?>
    </table>
</div>

<script type='text/javascript'>

    <?php
    if (is_array($packages_info) && !empty($packages_info))
    {
        ?>
        $('.t_info_pkg').dataTable( {
            "iDisplayLength": 25,
            "sPaginationType": "full_numbers",
            "bPaginate": true,
            "bLengthChange": false,
            "bFilter": true,
            "bSort": true,
            "bInfo": true,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "asc" ]],
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

                var title = "<div class='dt_title' style='top:10px;'><?php echo _('Packages Installed') ?></div>";
                $('div.dt_header').prepend(title);
            }
        });
        <?php
    }
    ?>
</script>