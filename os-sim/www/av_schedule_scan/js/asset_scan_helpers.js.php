<?php
header('Content-type: text/javascript');

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
?>


// Fill autocomplete with networks
function fill_autocomplete(networks)
{
    $("#task_params").autocomplete(networks, {
        minChars: 0,
        width: 350,
        matchContains: "word",
        autoFill: false,
        formatItem: function(row, i, max) {
            return row.txt;
        }
    }).result(function(event, item) {

        if (typeof(item.id) != 'undefined')
        {
            $("#task_params").val(item.id);
        }
    });
}


function get_sensor_by_nets(sid, s_type)
{
    if (typeof(sid) == 'undefined' || sid == '')
    {
        return false;
    }

    $.ajax(
    {
        type: "GET",
        url: "/ossim/av_schedule_scan/providers/get_nets_by_sensor.php",
        data: { sensor_id: sid , s_type: s_type},
        dataType: "json",
        cache: false,
        async: false,
        beforeSend: function(xhr) {
            $('.r_loading').html('<img src="/ossim/pixmaps/loading.gif" align="absmiddle" width="13" alt="<?php echo _('Loading')?>"/>');
        },
        success: function(msg)
        {
            $('.r_loading').empty();

            if(typeof(msg) != 'undefined' && msg != null && msg.status == 'success')
            {
                // Autocomplete networks
                fill_autocomplete(msg.data);

                return true;
            }
            else
            {
                notify('<?php echo _('An error occurred when trying to retrieve nets')?>', 'nf_error');

                return false;
            }
        }
    });
}


function get_target_number()
{
    var target_counter = 0;
    var targets = $('#task_params').val();

    if(targets != '')
    {
        var target_counter = 0;

        try
        {
            var targets = targets.split(",");

            for (i = 0; i < targets.length; i++)
            {
                if (targets[i].match(/#/))
                {
                    var ip_cidr = targets[i].split('#')
                        ip_cidr = ip_cidr[1];
                }
                else
                {
                    var ip_cidr = targets[i];
                }

                if (ip_cidr.match(/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))$/))
                {
                    var res = ip_cidr.split('/');
                    target_counter += 1 << (32 - res[1]);
                }
                else
                {
                    target_counter++;
                }
            }
        }
        catch(Err){}
    }

    return target_counter;
}