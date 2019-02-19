<?php
header("Content-type: text/javascript");
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


/*  Global Variables  */
var __al_table = {};

function init_agentless_form(data)
{
    $('#ppass').on('blur', function()
    {
        var val = $(this).val();

        if(val == '')
        {
            $('#use_su').prop('checked', false);
        }
        else
        {
            $('#use_su').prop('checked', true);
        }
    });
    
    $('#send').on('click', function() 
    {
        modify_host_data();
    });


    $('#add').on('click', function() 
    {
        add_monitoring();
    });

    $('#type').on('change', function() 
    {
        change_type('');
        change_arguments();
    });


    var config = 
    {
        validation_type: 'complete',
        errors:
        {
            display_errors: 'all',
            display_in: 'info_error'
        },
        form : 
        {
            id  : 'al_save_form',
            url : '/ossim/ossec/controllers/agentless/al_save.php'
        },
        actions: 
        {
            on_submit:
            {
                id: 'send',
                success:  $('#send').val(),
                checking: '<?php echo _('Saving')?>'
            }
        }
    };

    ajax_validator = new Ajax_validator(config);

    
    $('textarea').elastic();
    
    $("#arguments").tipTip({maxWidth: 'auto'});

    __al_table = $('#monitoring_table').dataTable(
    {
        "bFilter"       : false,
        "bLengthChange" : false,
        "iDisplayLength": 5,
        "bJQueryUI"     : true,
        "aaData"        : data['entries'],
        "aoColumns"     :
        [
            {"mDataProp": "id_type"},
            {"mDataProp": "frequency"},
            {"mDataProp": "state"},
            {"mDataProp": "arguments"},
            {"mDataProp": null}
        ],
        "fnRowCallback" : function(nRow, aData, iDrawIndex, iDataIndex)
        {
            var $cell = $('td:last-child', $(nRow)).empty();
            
            $('<img/>',
            {
                'src'   : '<?php echo AV_PIXMAPS_DIR ?>/delete.png',
                'class' : 'entry_action pointer',
                'click' : function()
                {
                    delete_monitoring(nRow);
                }
            }).appendTo($cell);
        }
    });
}

function add_monitoring()
{
    var data =
    {
        'action'   : 'verify_monitoring_entry',
        'token'    : Token.get_token('al_entries'),
        'id_type'  : $('#type').val(),
        'frequency': $('#frequency').val(),
        'state'    : $('#state').val(),
        'arguments': $('#arguments').val()
    }

    $.ajax(
    {
        type: "POST",
        url: "/ossim/ossec/controllers/agentless/actions.php",
        data: data,
        dataType: "json",
        error: function(xhr)
        {
            var msg = xhr.responseText;
            show_notification('al_notif', msg, "nf_error", 15000, true);
        },
        success: function(html)
        {
            delete data['action'];
            delete data['token'];
            
            // Encode HTML Entities
            data['arguments'] = data['arguments'].replace(/[\u00A0-\u9999<>\&]/gim, function(i)
            {
               return '&#'+i.charCodeAt(0)+';';
            });
            
            __al_table.fnAddData(data);
        }
    });
}


function delete_monitoring(row)
{
    __al_table.fnDeleteRow(row);

}


function modify_host_data()
{
    var data = 
    {
        'ip'      : $('#ip').val(),
        'sensor'  : $('#sensor').val(),
        'hostname': $('#hostname').val(),
        'user'    : $('#user').val(),
        'pass'    : $('#pass').val(),
        'passc'   : $('#passc').val(),
        'ppass'   : $('#ppass').val(),
        'ppassc'  : $('#ppassc').val(),
        'use_su'  : ~~($('#use_su').prop('checked')),
        'descr'   : $('#descr').val(),
        'entries' : __al_table.fnGetData(),
        'token'   : Token.get_token('al_entries')
    }

    $.ajax(
    {
        type    : "POST",
        url     : "/ossim/ossec/controllers/agentless/al_save.php",
        data    : data,
        dataType: "json",
        success : function(data)
        {
            document.location.href='/ossim/ossec/views/agentless/agentless.php';
        },
        error   : function(xhr)
        {
            var msg = xhr.responseText;
            show_notification('al_notif', msg, "nf_error", 15000, true);  
        }
    });
}


function change_type(t_value)
{
    if (t_value != '')
    {
        var type = t_value;
    }
    else
    {
        var type = $('#type').val();
    }

    if (type.match("_diff") != null)
    {
        $('#state_txt').text("Periodic_diff");
        $('#state').val("periodic_diff");
    }
    else
    {
        if (type.match("_integrity") != null)
        {
            $('#state_txt').html("Periodic");
            $('#state').val("periodic");
        }
    }
}

function change_arguments()
{
    var type = $('#type').val();

    if (type.match("_diff") != null)
    {
        $('#arguments').text("");
    }
    else if (type.match("_integrity") != null)
    {
        $('#arguments').text("/bin /etc /sbin");
    }
}
