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
// Global variables
var backup_datatable;
var __job_interval;
var __current_search_value = '';


function load_backup_list()
{
    /* DataTable for backups */
    backup_datatable = $('.table_data').dataTable( 
    {
        "bProcessing": true,
        "bServerSide": true,
        "bDeferRender": true,
        "sAjaxSource": "<?php echo AV_MAIN_PATH . "/av_backup/providers/dt_backup.php" ?>",
        "iDisplayLength": 5,
        "bLengthChange": false,
        "sPaginationType": "full_numbers",
        "bFilter": false,
        "aLengthMenu": [[10, 20, 50], [10, 20, 50]],
        "bJQueryUI": true,
        "aaSorting": [[ 2, "desc" ]],
        "aoColumns": [
          { "bSortable": false, "sClass" : "td_backup_id", "sWidth": "30px"},
          { "bSortable": false, "sClass" : "td_system", "sWidth": "230px" },
          { "bSortable": true,  "sClass" : "td_date" },
          { "bSortable": false, "sClass" : "td_backup" },
          { "bSortable": false, "sClass" : "td_type" },
          { "bSortable": false, "sClass" : "td_version" },
          { "bSortable": true,  "sClass" : "td_size" },
          { "bSortable": false, "sClass" : "td_download", "sWidth": "100px"  }
        ],
        oLanguage : 
        {
            "sProcessing": "<?php echo _('Loading')?>...",
            "sLengthMenu": "Show _MENU_ entries",
            "sZeroRecords": "<?php echo _('No backup files found')?>",
            "sEmptyTable": "<?php echo _('No backup files found')?>",
            "sLoadingRecords": "<?php echo _('Loading') ?>...",
            "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ backup files')?>",
            "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries')?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries')?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>",
            "sUrl": "",
            "oPaginate": {
                "sFirst":    "<?php echo _('First') ?>",
                "sPrevious": "<?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?>",
                "sLast":     "<?php echo _('Last') ?>"
            }
        },
        "fnRowCallback": function(nRow, aData, iDrawIndex, iDataIndex)
        {
            var backup_id = aData['DT_RowId'];

            var input = $('<input>',
            {
                'type'  : 'checkbox',
                'value'  : backup_id ,
                'class'  : 'backup_check',
                'change' : function()
                {
                    if ($('.backup_check:checked').length > 0)
                    {
                        $('#delete_selection').removeClass('disabled');
                    }
                    else
                    {
                        $('#delete_selection').addClass('disabled');
                    }
                },
                'click'  : function(e)
                {
                    //To avoid to open the tray bar when clicking on the checkbox.
                    e.stopPropagation();
                }
            }).appendTo($("td:nth-child(1)", nRow));
            
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
                    
                    var error = '<?php echo _('An error occurred - unable to load backup information for this system.') ?>';
                    show_notification('backup_notif', error, 'nf_error', 5000, true);
                    
                    var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }');
                    
                    fnCallback( json );
                },
                "complete": function()
                {
                    $('#delete_selection').addClass('disabled');
                    $('[data-bind="chk-all-backups"]').prop('checked', false);
                    
                    $('.download_button').click(function()
                    {
                        var _parameters = {};
        
                        _parameters['action']      = 'download_backup';
                        _parameters['backup_file'] = $(this).attr('data-backup_file');
                        
                        backup_action(_parameters);
                        
                        return false;
                    });
                    
                    // ToolTips
                    $(".info").tipTip({maxWidth: '380px'});
                }
            });
        },
        "fnServerParams": function ( aoData )
        {
            aoData.push( { "name": "search",    "value": $('#search_filter').val() } );
            aoData.push( { "name": "system_id", "value": $('#system_id').val() } );
        }
    });
}

/*  Function to show loading message in datatables  */
function datatables_loading(loading)
{
    if (loading)
    {
        $('.table_data').css('min-height', '250px');
        $('.table_data').css('visibility', 'hidden');
    }
    else
    {
        $('.table_data').css('min-height', '0');
        $('.table_data').css('visibility', 'visible');
    }
}

/*
* This function is used to:
*  - Launch a backup. If success, it creates a Interval to backup_status(job_id)
*  - Delete a colection of selected backups from dataTable
*/
function backup_action(parameters)
{
    var ctoken = Token.get_token("backup_action");
    
    parameters['token']     = ctoken;
    parameters['system_id'] = $('#system_id').val();
    
    show_loading_msg(parameters['action']);
    
    $.ajax({
        type: 'POST',
        url: '<?php echo AV_MAIN_PATH . "/av_backup/controllers/backup_actions.php" ?>',
        dataType: 'json',
        data: parameters,
        success: function(data)
        {
            if (typeof(data) != 'undefined' && typeof(data.status) != 'undefined' && data.status == 'success')
            {
                //msg = (typeof(data.data.msg) != 'undefined' && data.data.msg != '') ? data.data.msg : '<?php echo _('Backup action successfully done')?>';
                //show_notification('backup_notif', msg, 'nf_success', 5000, true);
                
                // Refresh if deleted
                if (parameters['action'] == 'delete_backup')
                {
                    $('#action_info_' + parameters['action']).html('');
                    
                    backup_datatable.fnDraw();
                }
                
                // Returns Job_id (Launch)
                if (parameters['action'] == 'launch_backup' && typeof(data.data.job_id) != 'undefined' && data.data.job_id != '')
                {
                    $('#backup_now_button').prop('disabled', true);
                    
                    __job_interval = setInterval("backup_status('" + data.data.job_id + "')", 5000);
                }
                
                // Returns Job_id (Download)
                if (parameters['action'] == 'download_backup' && typeof(data.data.job_id) != 'undefined' && data.data.job_id != '')
                {
                    download_backup(parameters['backup_file'], data.data.job_id);
                }
            }
            else
            {
                msg = (typeof(data.data) != 'undefined' && data.data != '') ? data.data : '<?php echo _('Error performing backup action')?>';
                show_notification('backup_notif', msg, 'nf_error', 5000, true);
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            //Checking expired session
            var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            var error = XMLHttpRequest.responseText;
            show_notification('backup_notif', error, 'nf_error', 5000, true);
            
            $('#action_info_' + parameters['action']).html('');
        }
    });
}

function show_loading_msg(action)
{
    if (action == 'delete_backup')
    {
        $('#action_info_' + action).html("<img src='/ossim/pixmaps/loading3.gif'/> <?php echo _('Deleting backup files') ?>...");
    }
    else if (action == 'launch_backup')
    {
        $('#action_info_' + action).html("<img src='/ossim/pixmaps/loading3.gif'/> <?php echo _('Configuration backup in progress') ?>...");
    }
}

function launch_backup()
{
    <?php
    $tz = Util::get_timezone();
    ?>
    var _local_date = new Date();
    var _local_time = Math.floor(_local_date.getTime()/1000) + <?php echo 3600 * $tz ?>;
    
    $.ajax({
        type: 'POST',
        url: '<?php echo AV_MAIN_PATH . "/av_backup/providers/get_backup_last_date.php" ?>',
        dataType: 'json',
        data: 'system_id=' + $('#system_id').val(),
        success: function(data)
        {
            if (data.status == 'success')
            {
                var _last_backup_date = data.data;
                
                
                var _parameters = {};
                    
                _parameters['action'] = 'launch_backup';
                
                
                var diff = _local_time - _last_backup_date;
                
                if (diff < 43200)
                {
                    var _date               = new Date();
                    var _tz                 = _date.getTimezoneOffset()*60000;
                    var d                   = new Date(_last_backup_date * 1000 + _tz);
                    var _last_date_readable = ('0' + (d.getMonth()+1)).slice(-2) + '/' + ('0' + d.getDate()).slice(-2) + '/' + d.getFullYear() + ' ' + ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2) + ':' + ('0' + d.getSeconds()).slice(-2);
                    
                    var keys = {"yes": "<?php echo Util::js_entities(_('Yes')) ?>","no": "<?php echo Util::js_entities(_('No')) ?>"};
                    var msg  = "<?php echo _('This system was recently backed up on LAST_BACKUP_DATE. Are you sure you want to run a new backup') ?>?";
                    msg      = msg.replace('LAST_BACKUP_DATE', _last_date_readable);
                    
                    av_confirm(msg, keys).done(function()
                    {
                        backup_action(_parameters);
                    });
                }
                else
                {
                    backup_action(_parameters);
                }
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            //Checking expired session
            var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            var error = XMLHttpRequest.responseText;
            show_notification('backup_notif', error, 'nf_error', 5000, true);
        }
    });
}

function delete_backups()
{
    var _backups = [];
        
    $('.backup_check:checked').each(function(id, elem)
    {
        _backups.push($(elem).val());
    });

    var _parameters = {};
    
    _parameters['action']       = 'delete_backup';
    _parameters['backup_files'] = _backups;
    
    backup_action(_parameters);
}


function download_backup(backup_file, job_id)
{
    var ctoken = Token.get_token("backup_download");
    
    $('#download_form_system_id').val($('#system_id').val());
    $('#download_form_token').val(ctoken);
    $('#download_form_backup_file').val(backup_file);
    $('#download_form_job_id').val(job_id);
    
    $('#download_form').submit();
}


/*
* This function looks up for current running backups
* If a job is found creates a Interval to check the status of the job
* When a job is finished reloads the dataTable and enables the launch button
* This function is called from backup_action() as well, with the recently launched job_id
*/
function backup_status(job_id)
{
    <?php if (!$conf->get_conf('backup_conf_pass')) { ?>
        $('#action_info_launch_backup').html('<?php echo _("Please specify a new password to generate backups and being able to download them later encrypted.<br/> To generate a password to encrypt backups:<br/> 1) Go to the <a href=\"/ossim/#configuration/administration/main\" target=\"_top\">Configuration page</a>.<br/> 2) Then choose \"Backup\" section and set \"Password to encrypt backup files\".")?>');
        return false;
    <?php } ?>

    if (typeof(job_id) == 'undefined')
    {
        job_id = '';
    }
    
    $.ajax({
        type: 'POST',
        url: '<?php echo AV_MAIN_PATH . "/av_backup/providers/get_backup_status.php" ?>',
        dataType: 'json',
        data: 'job_id=' + job_id + '&system_id=' + $('#system_id').val(),
        success: function(data)
        {
            // Looking for running backups
            if (job_id == '')
            {
                if (typeof(data.data.jobs[0]) != 'undefined' && typeof(data.data.jobs[0].job_id) != 'undefined' && data.data.jobs[0].job_id != '')
                {
                    $('#backup_now_button').prop('disabled', true);
                
                    show_loading_msg('launch_backup');
                    
                    __job_interval = setInterval("backup_status('" + data.data.jobs[0].job_id + "')", 5000);
                }
                else
                {
                    $('#backup_now_button').prop('disabled', false);
                    
                    $('#action_info_launch_backup').html('');
                }
            }
            
            // Getting status from a running backup
            else
            {
                if (data.data.job_status == 'task-started' || data.data.job_status == 'task-sent')
                {
                    show_loading_msg('launch_backup');
                }
                else
                {
                    clearInterval(__job_interval);
                    
                    $('#backup_now_button').prop('disabled', false);
                    
                    $('#action_info_launch_backup').html('');
                    
                    backup_datatable.fnDraw();
                }
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            //Checking expired session
            var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            if (job_id != '')
            {
                var error = XMLHttpRequest.responseText;
                show_notification('backup_notif', error, 'nf_error', 5000, true);
                
                clearInterval(__job_interval);
                
                $('#backup_now_button').prop('disabled', false);
            }
            
            $('#action_info_launch_backup').html('');
        }
    });
}
