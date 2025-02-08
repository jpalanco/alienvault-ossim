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

function create_autocomplete(type) {
    var id = '#ac_' + type;
    var hidden_id = '#' + type;

    var matchContains = true;
    var selectFirst = true;

    if  (type == 'plugin'){
        matchContains = false;
        selectFirst = false;
    }

    $(id).autocomplete('get_plugins_autocomplete.php?type=' + type, {
        minChars: 1,
        width: 300,
        max: 50,
        matchContains: matchContains,
        selectFirst: selectFirst,
        autoFill: false,
        scroll: true,
        formatItem: function(row, i, max, value)
        {
            return (value.split('###'))[1];
        },
        formatResult: function(data, value)
        {
            return (value.split('###'))[1];
        }
    }).result(function(event, item)
    {
        if (typeof(item) != 'undefined' && item != null)
        {
            var _aux_item = item[0].split('###');
            $(hidden_id).val(_aux_item[0]);
        }
    });

    $(id).on('keyup', function(){
        if ($(this).val() == ''){
            $(hidden_id).val('');
        }
    });

    if (type == 'plugin'){
        $(id).on('blur', function(){
            //To allow free text
            if ($(id).val() != '' && $(hidden_id).val() == ''){
                $(hidden_id).val($(id).val());
            }
        });
    } else {
        $(id).on('blur', function(){
            if ($(id).val() != '' && $(hidden_id).val() == ''){
                $(id).val('');
            }
        });
    }
}


function set_dropdown_action(action) {
    if (action == 'enable_all'){
        gvm_plugins_db.set_action('enable_all', 1);
        gvm_plugins_db.set_action('disable_all', 0);
        $('#select-all-plugins').prop('checked', true);
        $(".plugin-enabled:not(:checked)").prop('checked', true);
    } else {
        gvm_plugins_db.set_action('enable_all', 0);
        gvm_plugins_db.set_action('disable_all', 1);

        $('#select-all-plugins').prop('checked', false);
        $(".plugin-enabled:checked").prop('checked', false);
    }

    //A bulk update will be performed, it's not necessary to apply the change plugin by plugin
    gvm_plugins_db.remove_plugins();
}

function set_profile_stats(enabled, total){
    var html_p_enabled = "<span id='c_total_plugins_enabled'>" + enabled + "</span>"
    var html_total_plugins = "<span id='c_total_plugins'>" + total + "</span>"

    $('#p_info').html(html_p_enabled + " <?php echo _("PLUGINS ENABLED OF")?>  " + html_total_plugins);
}

function bind_save_plugins_actions(){
    $('#save_plugins').removeClass("av_b_f_processing").prop("disabled", false);
    $('#search_plugins').prop("disabled", false);
    $('#dd_actions').prop("disabled", false);
}

function toggle_plugin(id) {
    var id = '#' + id;
    var script_id = $(id).attr('data-script-id');

    if ($(id).prop('checked')) {
        gvm_plugins_db.enable_plugin(script_id);
    } else {
        gvm_plugins_db.disable_plugin(script_id);
    }

    //Check if header checkbox should be checked/unchecked
    var checked = ($(".plugin-enabled:not(:checked)").length == 0) ? true : false
    $('#select-all-plugins').prop('checked', checked);
}


function toggle_all_plugins() {
    var id = '';
    var script_id = '';

    if ($('#select-all-plugins').prop('checked')) {
        $(".plugin-enabled:not(:checked)").each(function(){
            id = '#' + $(this).attr('id');
            script_id = $(id).attr('data-script-id');
            gvm_plugins_db.enable_plugin(script_id);
            $(id).prop('checked', true);
        });
    } else {
        $(".plugin-enabled:checked").each(function(){
            id = '#' + $(this).attr('id');
            script_id = $(id).attr('data-script-id');
            gvm_plugins_db.disable_plugin(script_id);
            $(id).prop('checked', false);
        });
    }
}

function get_plugins_info (sid) {
    $.ajax({
        type: 'POST',
        url: 'profiles_ajax.php',
        dataType: 'json',
        data: { type: 'plugins_available', sid: sid },
        beforeSend: function(){
            //Reset stats, checked or unchecked plugins and 'Enable/Disable All' config
            var msg = '<?php echo _("Loading, please wait a few seconds")?> ...';
            var title = '<?php echo _("Loading")?>';
            var img = "<img width='16' align='absmiddle' id='loading_plugins' src='../pixmaps/loading3.gif' alt='" + title + "' title='" + title + "'/>";

            $('#p_info').html(img + msg);

            gvm_plugins_db.remove_actions();
            gvm_plugins_db.remove_plugins();
        },
        success: function(data) {
            if (data.status == 'success') {
                //Set profile stats
                set_profile_stats(data.message.enabled, data.message.total);

            } else {
                $('#p_info').addClass('error').html(data.message);
            }
        },
        error: function(){
            $('#p_info').html('');
        }
    });
}

function reload_plugins(){
    //Show plugin stats
    var s_filters = {
        'family_id' : $('#family').val(),
        'category_id' : $('#category').val(),
        'cve' : $('#cve').val(),
        'plugin' : $('#plugin').val()
    };

    gvm_plugins_db.set_filters(s_filters);
    get_plugins_info(sid);

    try
    {
        $('#table_data_plugins').dataTable().fnDraw();
    }
    catch (Err){}
}


function load_plugins() {
    //Show plugin stats
    get_plugins_info(sid);

    $('#table_data_plugins').dataTable({
        "bProcessing": true,
        "bServerSide": true,
        "sAjaxSource": 'get_plugins.php',
        "fnServerParams": function ( aoData )
        {
            var s_filters = gvm_plugins_db.get_filters();
            var profile_id = gvm_plugins_db.get_profile();

            aoData.push( { "name": "profile_id",  "value": profile_id } );
            aoData.push( { "name": "family_id",   "value": s_filters['family_id'] } );
            aoData.push( { "name": "category_id", "value": s_filters['category_id'] } );
            aoData.push( { "name": "cve",         "value": s_filters['cve'] } );
            aoData.push( { "name": "plugin",      "value": s_filters['plugin'] } );
        },
        "sServerMethod" : "POST",
        "iDisplayLength" : 10,
        "sPaginationType" : "full_numbers",
        "bLengthChange" : false,
        "bFilter": false,
        "bJQueryUI" : true,
        "aoColumns" : [
            {"bSortable": false, "sClass": "center", "sWidth" : "50px"},
            {"bSortable": true,  "sClass": "left", "sWidth" : "100px"},
            {"bSortable": true,  "sClass": "left"},
            {"bSortable": false, "sClass": "center", "sWidth" : "100px"},
            {"bSortable": true,  "sClass": "left"},
            {"bSortable": true,  "sClass": "left", "sWidth" : "100px"}
        ],
        "oLanguage" : {
            "sProcessing": "<?php echo _('Loading')?>...",
            "sLengthMenu": "Show _MENU_ entries",
            "sZeroRecords": "<?php echo _('No plugins found for this family')?>",
            "sEmptyTable": "<?php echo _('No plugins found')?>",
            "sLoadingRecords": "<?php echo _('Loading') ?>...",
            "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ plugins')?>",
            "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries')?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries')?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
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

        },
        "fnCreatedRow" : function(nRow, aData, iDataIndex)
        {

        },
        "fnRowCallback" : function(nRow, aData, iDrawIndex, iDataIndex)
        {
            //Check or uncheck checkboxes if 'Enable All' or 'Disable All' actions were executed
            var enable_all = gvm_plugins_db.get_action('enable_all');
            var disable_all = gvm_plugins_db.get_action('disable_all');

            if (gvm_plugins_db.is_enabled(aData['DT_RowId']) || (enable_all && !gvm_plugins_db.is_disabled(aData['DT_RowId']))) {
                $('#plugin-enabled-' + aData['DT_RowId'], nRow).prop('checked', true);
            } else if (gvm_plugins_db.is_disabled(aData['DT_RowId']) || (disable_all && !gvm_plugins_db.is_enabled(aData['DT_RowId']))) {
                $('#plugin-enabled-' + aData['DT_RowId'], nRow).prop('checked', false);
            }
        },
        "fnServerData": function (sSource, aoData, fnCallback, oSettings)
        {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "beforeSend": function(xhr)
                {

                },
                "success": function (json)
                {
                    //DataTables Stuffs
                    $(oSettings.oInstance).trigger('xhr', oSettings);
                    fnCallback(json);

                    if (oSettings._iRecordsTotal == 0){
                        $('#select-all-plugins').prop('disabled', true).prop('checked', false);
                        $('#dd_actions').prop('disabled', true);
                    } else {

                        //Check if all plugins in the page are checked
                        var checked = false;
                        if (gvm_plugins_db.get_action('enable_all') == 1 || $(".plugin-enabled:not(:checked)").length == 0) {
                            checked = true;
                        }

                        //Enable header checkbox
                        $('#select-all-plugins').prop('disabled', false).prop('checked', checked);

                        //Enable dropdown
                        $('#dd_actions').prop('disabled', false);
                        
                        $('#act-enable-all').off().on('click', function(){
                            set_dropdown_action('enable_all');
                        });

                        $('#act-disable-all').off().on('click', function(){
                            set_dropdown_action('disable_all');
                        });

                        //Handler to enable/disable plugins per page
                        $('#select-all-plugins').on('click', function(){
                            toggle_all_plugins();
                        });

                        //Handler to enable or disable a plugin
                        $('.plugin-enabled').on('click', function(){
                            toggle_plugin($(this).attr('id'));
                        });
                    }
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

                    //DataTables Stuffs
                    var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }');
                    fnCallback(json);

                    //Disable checkbox that checks all rows
                    $('#select-all-plugins').prop('disabled', true).prop('checked', false);
                    $('#dd_actions').prop('disabled', true);
                },
                "complete": function()
                {
                    $('.plugin_info').tipTip({
                        defaultPosition:"right",
                        delay_load: 100,
                        maxWidth: "400px",
                        edgeOffset: 3,
                        keepAlive: true,
                        content: function (e) {
                            var id = $(this).attr('data-script-id');

                            $.ajax({
                                type: 'GET',
                                data: 'id='+id,
                                url: 'lookup.php',
                                success: function (response) {
                                    e.content.html(response); // the var e is the callback function data (see above)
                                }
                            });

                            return 'Searching...';
                        }
                    });
                }
            });
        }
    });
}


function confirm_delete(action, id){
    var msg  = "<?php echo Util::js_entities(_('Are you sure you want to delete this entry?')) ?>";
    var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};

    av_confirm(msg, opts).fail(function(){
        return false;
    }).done(function(){

        var url = '';

        switch (action) {
            case 'deleteSchedule':
                url = '<?php echo AV_MAIN_PATH?>/vulnmeter/manage_jobs.php?disp=' + action + '&job_id=' + id;
            break;

            case 'delete_scan':
                url = '<?php echo AV_MAIN_PATH?>/vulnmeter/sched.php?action=' + action + '&job_id=' + id;
            break;
        }

        if (url != '') {
            document.location.href = url;
        }
    });
}


function showLayer(theSel, number) {
    $("#smethodtr .forminput,#smethodtr .forminput-label").hide();
    if (number == undefined || number == 1) {
        return;
    }
    $("#idSched8,#idSched2").show();
    (number == 3 ? $("#fl-run-once") : $("#fl-run-many")).show();
    if (number == 2 || number == 4) {
        $("#idSched7").show();
        (number == 2 ? $("#fl-days") : $("#fl-weeks")).show();
    }
    $("#idSched"+number).show();
}
