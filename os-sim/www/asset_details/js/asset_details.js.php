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

?>

/**
* Reload from greybox action
*/
var current_section = "<?php echo ($load_section != '') ? $load_section : '' ?>";
var current_tab     = "<?php echo ($load_tab != '') ? $load_tab : '' ?>";
var force_reload    = '';
var __main_timer    = false;
    
function GB_onclose() 
{
    /**
    * Must reload the session serialized object (used in ajax scripts)
    * Then call the ajax to view the changes
    */
    if (force_reload != '')
    {
        $.ajax({
            type: "GET",
            dataType: 'json',
            url: "ajax/reload_session_object.php?asset_id=<?php echo $id ?>&asset_type=<?php echo $asset_type ?>",
            success: function(json)
            {
                if (typeof(json.session_updated) != 'undefined' && json.session_updated)
                {
                    // General info, map and nagios led
                    if (force_reload.match(/info/))
                    {
                       load_info();
                    }
                    
                    // Snapshot values
                    if (force_reload.match(/snapshot/))
                    {
                       load_snapshot();
                    }
                    
                    // Other options ('software', 'properties', etc.)
                    // When 'info' or 'snapshot' must reload, perhaps the hostname may be changed into an event
                    reload_sections();

                    force_reload = '';
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
            
            }
        });
    }
}


/**
*
* Local scan action
*
*/ 
function local_scan()
{ 
    GB_hide();
      
    scan_host('<?php echo $id?>');
}


/**
* Show or hide the 'More Info' div
*/
function toggle_more_info()
{
    $('#details_info').toggle();
    if ($('#details_info').is(':visible'))
    {
        $('.view_details').html('- <?php echo _('Less Details') ?>');
    }
    else
    {
        $('.view_details').html('+ <?php echo _('More Details') ?>');
    }
}


/**
* Go back to the listing
*/
function go_back() 
{
    <?php
    if ($asset_type == 'host')
    {
        ?>
        var url = '<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/assets/index.php?back=1', 'environment', 'assets', 'assets') ?>';
        <?php
    }
    elseif ($asset_type == 'net')
    {
        ?>
        var url = '<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/assets/list_view.php?type=network', 'environment', 'assets_groups', 'networks') ?>';
        <?php
    }
    elseif ($asset_type == 'group')
    {
        ?>
        var url = '<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/assets/list_view.php?type=group', 'environment', 'assets_groups', 'host_groups') ?>';
        <?php
    }
    ?>
    
    if (typeof(top.av_menu.load_content) == 'function')
    {
        top.av_menu.load_content(url);
    }
    else
    {
        document.location.href = url;
    }
}


// [General, Activity, Notes]
function draw_selected_layer(id)
{
    $('.section').hide();
    $('#section_'+id).show();
    
    var pos_init  = $('#db_tab_'+id).position();
        pos_init  = (typeof(pos_init) == 'undefined' || pos_init == null ) ? 0 : pos_init.left; 
    
    var width     = $('#db_tab_'+id).outerWidth();
     
    $('#db_tab_blob').animate(
    {
        left  : pos_init,
        width : width
    },
    {
        duration : 400,
        easing   : 'easeOutExpo',
        queue    : false,
        complete : function()
        {
            
        }
    }); 
}
    

// This function change the tab option and then load the content
function load_section(section, tab)
{
    try
    {
        var old_index = $("#tabs-list").tabs("option", "active");
        var new_index = $('#tabs-list a[data-id="'+ section +'"]').parent().index();

        if (typeof new_index == 'number' && old_index != new_index)
        {
            $("#tabs-list").tabs('select', new_index);
        }
    }
    catch(Err){}
    
    load_section_content(section, tab);
}

// This function load the content
/*
    Sections:
        - General
            Software, Users, Properties, Plugins (Only for hosts)
        - Location (Only for hosts)
        - Activity
            Alarms, Events, Netflows
        - Assets (Only for nets and groups)
        - History (Only for groups)
        - Notes    
*/
function load_section_content(section, tab)
{  
    clearTimeout(__main_timer);
    
    draw_selected_layer(section);
    
    current_section = section;
    current_tab = tab;
    
    $('.div_subcontent_'+section).hide();
    $('.c_arrow_down_'+section).hide();
    $('#div_'+tab).show();
    $('.active_'+section).removeClass('active').addClass('default');
    $('#ll_opt_'+tab).removeClass('default').addClass('active');
    $('#arrow_down_'+tab).show();

    
    switch (section)
    {
        case 'general':

            $('.general_edit').hide();

            if (tab == 'software')
            {                
                load_software();
                $('#edit_avail_button').show();
            }      
            else if (tab == 'users')
            {
                load_users();
            }    
            else if (tab == 'properties')
            {
                load_properties();
                
                $('#edit_properties_button').show();
            }
            else if (tab == 'plugins')
            {
                <?php
                if ($asset_type == 'host') 
                {
                    ?>
                    load_plugins();
                    
                    $('#edit_plugins_button').show();
                    <?php
                }
                ?>
            }
            
        break;

        case 'activity':
            if (tab == 'events')
            {
                load_events();
            }
            else if (tab == 'alarms')
            {
                load_alarms();
            }
            else if (tab == 'netflows')
            {
                load_netflows();
            }  
        break;

        case 'location':

            <?php
            if ($asset_type == 'host') 
            {
                ?>
                av_map = new Av_map('detail_map');
                    
                if(Av_map.is_map_available())
                {
                    av_map.set_location($('#detail_map').data('lat'), $('#detail_map').data('lng'));
                    av_map.set_zoom($('#detail_map').data('zoom'));
                    
                    av_map.draw_map();
                    av_map.map.setOptions({draggable: false});
                    
                    if(av_map.get_lat() != '' && av_map.get_lng() != '')
                    {
                        av_map.add_marker(av_map.get_lat(), av_map.get_lng());
                        av_map.markers[0].setDraggable(false);
                        av_map.markers[0].setTitle($('#detail_map').data('marker_title'));
                        av_map.markers[0].setMap(av_map.map);
                    }
                }
                else
                {
                    av_map.draw_warning();
                }
                <?php
            }
            ?>

        break;

        case 'assets':

            <?php
            if ($asset_type == 'net' || $asset_type == 'group')
            {
                ?>
                load_hosts();
                <?php
            }
            ?>

        break;

        case 'history':
        
            <?php
            if ($asset_type == 'group')
            {
                ?>
                load_history();
                <?php
            }
            ?>

        break;

        case 'notes':
            //No dynamic content
        break;
    }
}


/**
*
* Notes actions
*
*/
function change_note(id, txt)
{
    var flag_change = false;
    var note_action = (id > 0) ? "update" : "new_ajax";
    $.ajax({
        data:  {action: note_action, id_note: id, txt: txt},
        type: "POST",
        url: "ajax/view_notes.php?type=<?php echo str_replace('group', 'host_group', $asset_type) /* Patch for groups */ ?>&id=<?php echo $id ?>",
        dataType: "json",
        async: false,
        success: function(msg) {
            if (msg.state=="OK")  
            {
                flag_change = true;
            }
            
            if (msg.state=="ERR") 
            {
                flag_change = false;
            }
            
            if (note_action == "new_ajax")
            {
                init_notes();
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {                      
            flag_change = false;
        }
    });
    
    return flag_change;
}


/**
*
* Notes delete
*
*/
function delete_note(id)
{
    var note_action = ""; // Empty action is delete in view_notes.php :S
    
    $.ajax({
        url: "ajax/view_notes.php?type=<?php echo str_replace('group', 'host_group', $asset_type) ?>&id=<?php echo $id ?>&id_note="+id,
        async: false,
        success: function(msg) {
            init_notes();
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {                      
            
        }
    });
}


/**
*
* Get asset notes by ajax and initialize editInPlace jquery plugin
*
*/
function init_notes()
{
    // Get notes
    $.ajax({
        type: "GET",
        async: false,
        url: "modules/notes.php?notes_ajax_mode=1&type=<?php echo $asset_type ?>&id=<?php echo $id ?>",
        success: function(msg) {
            $('#notes_list').html(msg);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {                      
            alert("<?php echo _('There was an error') ?>");
        }
    });

    // Init edit in place plugin (notes)
    $(".editInPlace").editInPlace(
    {
        callback: function(unused, enteredText, prevtxt) {
            var id  = $(this).attr('note');
            if(change_note(id, enteredText))
            {
                return enteredText;
            } 
            else
            {
                return prevtxt;
            }                       
        },
        preinit: function(node) {
            var txt = $(node).html();
            txt = txt.replace(/<br>/g, "\n");
            txt = txt.replace(/\n+/g, "\n");
            $(node).html(txt);
        },
        postclose: function(node) {
            var txt = $(node).html();
            txt = txt.replace(/\n/g, '<br>');
            $(node).html(txt);
            $('#edit_tip').hide();
            $('.delete_links').hide();
            $('.note_row').css('background-color', 'transparent');
        },
        text_size: 14,
        bg_over: '#fff',
        field_type: "textarea",
        on_blur : 'save',
        value_required: true,
        show_buttons:   true,
        save_button:   '<button class="small" style="margin:2px"><?php echo _('Save') ?></button>',
        cancel_button: '<button class="small av_b_secondary" style="margin:2px"><?php echo _('Cancel') ?></button>' 
    });
}


/**
* Load General Info values in background
*/
function load_info()
{
    $.ajax({
        type: "GET",
        dataType: 'json',
        url: "ajax/get_info.php?asset_id=<?php echo $id?>&asset_type=<?php echo $asset_type?>",
        success: function(json)
        {
            var session = new Session(json, '');                                                                               
                                
            if (session.check_session_expired() == true || typeof(json) == 'undefined' || json == null)
            {
                session.redirect();
                return;
            }
            
            // Some fields may not exist depending the asset type
            $("#info_icon"       ).html(json.icon);
            $("#info_title"      ).html(json.title);
            $("#info_subtitle"   ).html(json.subtitle);
            $("#info_networks"   ).html(json.networks);
            $("#info_os"         ).html(json.os);
            $("#info_sensors"    ).html(json.sensors);
            $("#info_asset_type" ).html(json.asset_type);
            $("#info_cidr"       ).html(json.cidr);
            $("#info_description").html(json.description);
            $("#info_owner"      ).html(json.owner);
            $(".info_asset_value").removeClass('asset_value_selected');
            $("#info_asset_value_"+json.asset_value).addClass('asset_value_selected');
            
            // Availability monitoring
            $("#info_nagios").removeClass();
            $("#info_nagios").addClass('detail_led').addClass(json.nagios_class);
            
            // Map: Only for host type, another will be ignored
            $('#detail_map').data('lat', json.lat);
            $('#detail_map').data('lng', json.lng);
            $('#detail_map').data('zoom',json.zoom);
            $('#detail_map').data('marker_title', json.title +' <?php echo _('Location')?>');
            
            // If map is already loaded, then update
            if (typeof(av_map) != 'undefined' && av_map != null)
            {
                av_map.set_location(json.lat, json.lng);
                av_map.set_zoom(json.zoom);
                av_map.map.setZoom(av_map.get_zoom());
                                
                av_map.remove_all_markers();
               
                if (av_map.get_lat() != '' && av_map.get_lng() != '')
                {
                    av_map.add_marker(av_map.get_lat(), av_map.get_lng());
                    
                    // Change title
                    av_map.markers[0].setTitle(json.title +' <?php echo _('Location')?>');
                }
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        
        }
    });
}


/**
* Load Snapshot values in background
*/
function load_snapshot()
{
    $.ajax({
        type: "GET",
        dataType: 'json',
        url: "ajax/get_snapshot.php?asset_id=<?php echo $id?>&asset_type=<?php echo $asset_type?>",
        success: function(json) {
            $("#snap_hosts").html(json.hosts);
            $("#snap_software").html(json.software);
            $("#snap_users").html(json.users);
            $("#snap_vulns").html(json.vulns);
            $("#snap_alarms").html(json.alarms);
            $("#snap_events").html(json.events);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        
        }
    });
}


/**
* 
* Load HIDS value in background
*
*/
function load_hids()
{
    $.ajax({
        type: "GET",
        url: "ajax/get_hids.php?asset_id=<?php echo $id?>&asset_type=<?php echo $asset_type?>",
        success: function(msg) {
            $("#hids_led").html('');
            $("#hids_led").addClass('detail_led').addClass('led_'+msg);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            $("#hids_led").html('');
            $("#hids_led").addClass('detail_led').addClass('led_gray');
        }
    });
}
    
    
/**
*
* Load Services (Software) in dataTable
*
*/
var software_loaded = false;
var software_dataTable;

function load_software()
{
    if (!software_loaded)
    {
        software_loaded = true;
        // Ajax Software Load
        software_dataTable = $('.table_data_software').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_services.php?asset_id=<?php echo $id ?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
                { "bSortable": false, "sClass": "left" },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No software found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No software found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ software services') ?>",
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
            "fnInitComplete": function() {
                $('#software_loading').hide();
                $('#software_list').show();
            },
            "fnDrawCallback" : function(oSettings) {
                $('.table_data_software tbody tr').on('click', function () 
                {
                    toggle_service_tray(this);  // perform single-click action: toggle service vulns
                });
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}
    
    
/**
*   
* Load Users in dataTable
* 
*/
var users_loaded = false;
var users_dataTable;

function load_users()
{
    if (!users_loaded)
    {
        users_loaded = true;
        // Ajax users Load
        users_dataTable = $('.table_data_users').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_users.php?asset_id=<?php echo $id ?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
                <?php if ($asset_type == 'net' || $asset_type == 'group') { ?>
                { "bSortable": false, "sClass": "left" },
                <?php } ?>
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No users found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No users found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ users') ?>",
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
            "fnInitComplete": function() {
                $('#users_loading').hide();
                $('#users_list').show();
            },
            "fnDrawCallback" : function(oSettings) {
                
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}


/**
*
* Load Properties in dataTable
*
*/
var properties_loaded = false;
var properties_dataTable;

function load_properties()
{
    if (!properties_loaded)
    {
        properties_loaded = true;
        // Ajax properties Load
        properties_dataTable = $('.table_data_properties').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_properties.php?asset_id=<?php echo $id ?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
            { "bSortable": false, "sClass": "left" },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No properties found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No properties found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ properties') ?>",
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
            "fnInitComplete": function() {
                $('#properties_loading').hide();
                $('#properties_list').show();
            },
            "fnDrawCallback" : function(oSettings) {
                
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}


/**
*
* Load Properties in dataTable
*
*/

var plugins_loaded = false;
var plugins_dataTable = null;


function load_plugins()
{    
    if (!plugins_loaded)
    {
        plugins_loaded = true;

        var aoColumns = [
            {"bSortable": false, "sClass": "left"},
            {"bSortable": false, "sClass": "left"},
            {"bSortable": false, "sClass": "left"},
            {"bSortable": false, "sClass": "left"},
            {"bSortable": false, "sClass": "left"},
            {"bSortable": false, "sClass": "center"}
        ];

        plugins_dataTable = $('.table_data_plugins').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_plugins.php?asset_id=<?php echo $id?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": aoColumns,
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No plugins found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No plugins found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ plugins') ?>",
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
            "fnInitComplete": function() {
                $('#plugins_loading').hide();
                $('#plugins_list').show();
            },
            "fnDrawCallback" : function(oSettings) {

                if ($('.table_data_plugins .dataTables_empty').length == 0)
                {
                    get_devices_activity();
                }
            },
            "fnCreatedRow": function( nRow, aData, iDataIndex )
            {
                var led = $('<div>', {
                    "style" : 'position:relative'
                });

                var span = $('<span>', {
                    'class' : 'plugin_led led_gray',
                    'html'  : "&nbsp;" 
                }).appendTo(led);

                $('td:last', nRow).html(led);

            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {

                        /*
                        if(typeof(json.show_sensors) != 'undefined' && typeof(plugins_dataTable) != null)
                        {
                             plugins_dataTable.fnSetColumnVis(4, json.show_sensors);
                        }
                        */

                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );

                    },
                    "error": function(){

                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        }); 
    }
    else
    {
        get_devices_activity();
    }
}


function show_plugin_message()
{
    av_alert("<?php echo sprintf(_('To activate plugins for the local asset, please set it up at %s, then click in local system and add a new plugin in Sensor Configuration link'),'<a href=\"'.AV_MAIN_PATH.'/#configuration/deployment/components\" onclick=\"$(this).closest(\'form\').submit()\">'._('CONFIGURATION').'->'._('DEPLOYMENT').'->'._('ALIENVAULT-CENTER').'</a>') ?>");
}

function get_devices_activity()
{
    var av_info  = 'messages_box';
    var asset_id = "<?php echo $id?>";
    
    //removing previous timeout to avoid queues
    clearTimeout(__main_timer);
    
    var ctoken = Token.get_token("plugin_select");

	$.ajax(
	{
		url: "ajax/plugin_ajax.php?token="+ctoken,
		data: {"action": "plugin_activity", "data": {"asset": asset_id}},
		type: "POST",
		dataType: "json",
		success: function(data)
		{    		    
    		$('.plugin_led').removeClass('led_green').addClass('led_gray');
    		 
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{        		    
            		show_notification(av_info, data.msg, 'error', 0, true);
            		
            		return false;
        		}
        		
                var active_plugins = data.data.plugins;  
                var total_plugins  = data.data.total_p;
                     
                               
                $('.table_data_plugins tbody tr').each(function()
                {
                    var id  = $(this).attr('id');           
                         
                    if (typeof(active_plugins[id]) != 'undefined' && active_plugins[id] == true)
                    {
                        $('.plugin_led',this).removeClass('led_gray').addClass('led_green');
                    }
                    else
                    {
                        $('.plugin_led',this).addClass('led_gray');
                    }
                                        
                });
                
                //If there is no plugin activated then we do not reload again
                if (total_plugins > 0)
                { 
                    __main_timer = setTimeout(get_devices_activity, 30000);
                }
                
                

            }
            else
            {
                $('.plugin_led').removeClass('led_green').addClass('led_gray');
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
            
            $('.plugin_led').removeClass('led_green').addClass('led_gray');

            show_notification(av_info, errorThrown, 'error', 0, true);            
		}
	});    
}



/**
*
* Load Hosts, when Network mode
*
*/

var hosts_loaded = false;
var assets_dataTable;
    
function load_hosts()
{
    if (!hosts_loaded)
    {
        hosts_loaded = true;
        // Ajax Host list for network details
        assets_dataTable = $('.table_data_hosts').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_hosts.php?group_id=<?php echo $id ?>&asset_type=<?php echo $asset_type ?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
                { "bSortable": true },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                <?php if ($asset_type == 'group') { ?>
                { "bSortable": false },
                <?php } ?>
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No hosts found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No hosts found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ hosts') ?>",
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
            "fnInitComplete": function(oSettings, json) 
            {
                $('#hosts_loading').hide();
                $('#hosts_list').show();
                
                <?php
                //Popup to Add Assets
                if ($asset_type == "group") 
                { 
                ?>
                    var scope = oSettings.nTableWrapper;
                    
                    if ( $('.dt_header .greybox_assets', scope).length < 1 )
                    {
                        var add_b = '<input type="button" <?php echo $button_disabled ?> class="greybox_assets av_b_secondary" value="<?php echo _("Add Assets") ?>">';
                        
                        $('.dt_header', scope).prepend(add_b);  
                    }
                    
                <?php 
                }
                ?>

            },
            "fnDrawCallback" : function(oSettings) 
            {
                $('.tipinfo').tipTip(
                {
                    content: function (e) 
                    {
                        return $(this).attr('txt')
                    }
                });                
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) 
                    {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}
    

/**
*
* Load Events in background
*
*/
var events_loaded = false;
var events_dataTable;

function load_events()
{
    if (!events_loaded)
    {
        events_loaded = true;
        // Ajax SIEM Events Load
        events_dataTable = $('.table_data_events').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_events.php?asset_id=<?php echo $id ?>&asset_type=<?php echo $asset_type ?>",
            "iDisplayLength": 10,
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 2, "desc" ]],
            "aoColumns": [
                { "bSortable": true, "sClass": "left" },
                { "bSortable": true },
                { "bSortable": true },
                { "bSortable": false },
                { "bSortable": false, "sClass": "nowrap" },
                <?php if ($asset_type == "net") { ?>
                { "bSortable": false, "sClass": "nowrap" },
                <?php } ?>
                { "bSortable": false },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No events found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No events found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ events') ?>",
                "sInfoEmpty": "<?php echo _('Showing 0 entries') ?>",
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
            "fnInitComplete": function() {
                $('#events_loading').hide();
                $('#events_list').show();
            },
            "fnDrawCallback" : function(oSettings) {
                if (typeof(load_contextmenu) == 'function')
                {
                    load_contextmenu();
                }
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}


/**
*
* Load Alarms in dataTable
*
*/
var alarms_loaded = false;
var alarms_dataTable;

function load_alarms()
{
    if (!alarms_loaded)
    {
        alarms_loaded = true;
        // Ajax Alarms Load
        alarms_dataTable = $('.table_data_alarms').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_alarms.php?asset_id=<?php echo $id ?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 4, "desc" ]],
            "aoColumns": [
            { "bSortable": true },
                { "bSortable": false },
                { "bSortable": true, "sClass": "left" },
                { "bSortable": true, "sClass": "left" },
                { "bSortable": false },
                { "bSortable": false, "sClass": "left" },
                { "bSortable": false, "sClass": "left" }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No alarms found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No alarms found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ alarms') ?>",
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
            "fnInitComplete": function() {
                $('#alarms_loading').hide();
                $('#alarms_list').show();
            },
            "fnDrawCallback" : function(oSettings) {
                if (typeof(load_contextmenu) == 'function')
                {
                    load_contextmenu();
                }
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}


/**
*
* Load Netflows
*
*/
var netflows_loaded = false;
var netflows_dataTable;

function load_netflows()
{
    if (!netflows_loaded)
    {
        netflows_loaded = true;
        // Ajax Netflows load
        netflows_dataTable = $('.table_data_netflows').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_netflows.php?asset_id=<?php echo $id ?>&asset_type=<?php echo $asset_type ?>",
            "iDisplayLength": 10,
            "bPaginate": true,
            "bFilter": false,
            "bLengthChange": true,
            "bJQueryUI": true,
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No netflows found for this asset') ?>",
                "sEmptyTable": "<?php echo _('No netflows found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ flows') ?>",
                "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
                "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
                "sInfoPostFix": "",
                "sInfoThousands": ",",
                "sSearch": "<?php echo _('Search') ?>:",
                "sUrl": "",
                "sLengthMenu": '<?php echo _("Display") ?> <select>'+
                            '<option value="10">10</option>'+
                            '<option value="50">50</option>'+
                            '<option value="100">100</option>'+
                            '<option value="-1"><?php echo _("All") ?></option>'+
                            '</select> <?php echo _("flows") ?>'
            },
            "fnInitComplete": function() {
                $('#netflows_loading').hide();
                $('#netflows_list').show();
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                    // Netflows Error (empty table)
                        if (json.Error != "")
                        {
                            var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                            fnCallback( json );
                        }
                        // Success
                        else
                        {
                            $(oSettings.oInstance).trigger('xhr', oSettings);
                            fnCallback( json );
                        }
                    },
                    // JSON Error
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}


/**
*
* Load History
*
*/
var history_loaded = false;
var history_dataTable;

function load_history()
{
    if (!history_loaded)
    {
        history_loaded = true;
        // Ajax History load
        history_dataTable = $('.table_data_history').dataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "ajax/get_history.php?group_id=<?php echo $id ?>",
            "iDisplayLength": 10,
            "sPaginationType": "full_numbers",
            "bLengthChange": false,
            "bJQueryUI": true,
            "aaSorting": [[ 4, "desc" ]],
            "aoColumns": [
            { "bSortable": true },
                { "bSortable": false },
                { "bSortable": false }
            ],
            oLanguage : {
                "sProcessing": "<?php echo _('Loading') ?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No history found for this group') ?>",
                "sEmptyTable": "<?php echo _('No history found') ?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ history events') ?>",
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
            "fnInitComplete": function() {
                $('#history_loading').hide();
                $('#history_list').show();
            },
            "fnDrawCallback" : function(oSettings) {
                
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                oSettings.jqXHR = $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": function (json) {
                        $(oSettings.oInstance).trigger('xhr', oSettings);
                        fnCallback( json );
                    },
                    "error": function(){
                        //Empty table if error
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                        fnCallback( json );
                    }
                } );
            }
        });
    }
}


/**
*
* Export button handle (Nets and Groups)
*
*/
function export_hosts()
{
    <?php
    if ($asset_type == 'net')
    {
        ?>
        document.location.href = '/ossim/net/export_net.php?net_id=<?php echo $id ?>';
        <?php
    }
    elseif ($asset_type == 'group')
    {
        ?>
        document.location.href = '/ossim/group/export_group.php?group_id=<?php echo $id ?>';
        <?php
    }
    ?>
}


/**
*
* Delete button handle
*
*/
function delete_asset(asset_type, asset_id)
{
    if (confirm("<?php echo _('Are you sure to delete this element?') ?>"))
    {
        remove_asset(asset_type, asset_id);
    }
}


/**
*
* Remove the entire Host, Network or Group
*
*/
function remove_asset(asset_type, asset_id)
{                           
    var av_info = 'messages_box';
    
    //AJAX data
    
    if (asset_type == 'host')
    {
        var action    = 'delete_host';
        var a_url     = '/ossim/host/host_actions.php';
        var l_message = '<?php echo _('Deleting host')?>...';
        var back_url  = '/ossim/assets/index.php?type=network&msg=saved';
        var token     = Token.get_token('host_form');
    }
    else if (asset_type == 'net')
    {
        var action    = 'delete_net';
        var a_url     = '/ossim/net/net_actions.php';
        var l_message = '<?php echo _('Deleting network')?>...';
        var back_url  = '/ossim/assets/list_view.php?type=network&msg=saved';
        var token     = Token.get_token('net_form');
    }
    else if (asset_type == 'group')
    {
        var action    = 'delete_group';
        var a_url     = '/ossim/group/ajax/group_actions.php';
        var l_message = '<?php echo _('Deleting group')?>...';
        var back_url  = '/ossim/assets/list_view.php?type=group&msg=saved'
        var token     = Token.get_token('ag_form');
    }
    
     
    var a_data = {
        "action"   : action,
        "asset_id" : asset_id,
        "token"    : token
    };  
    
    
    $.ajax({
        type: "POST",
        url:  a_url,
        data: a_data,
        dataType: 'json',
        beforeSend: function(xhr){
            
            // Clean previous messages
            $('#'+av_info).empty();
            
            // Show loading box
            show_loading_box('main', l_message, '');
            
        },
        error: function(data){

            //Check expired session
            var session = new Session(data, '');
                        
            if (session.check_session_expired() == true)
            {
                session.redirect();
                
                return;
            }  
            
            hide_loading_box();
            
            show_notification(av_info, av_messages['unknown_error'], 'error', 0, true);

        },
        success: function(data){
            
            //Check expired session                
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                
                return;
            } 
                                                        
            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');
                                
            if (!cnd_1 && !cnd_2)
            {
                document.location.href = back_url;
            }
            else
            {
                hide_loading_box();
                
                show_notification(av_info, data.data, 'error', 0, true);
            }
            
        }
    });
}


/**
*
* Remove one asset from a Group
*
*/
function del_asset_from_group(asset_id)
{
    var av_info = 'messages_box';
    
    if (confirm('<?php echo _('Are you sure to remove this asset from the group?') ?>'))
    {
        $.ajax({
            type: "GET",
            dataType: 'json',
            url: "ajax/remove_group_asset.php?group_id=<?php echo $id ?>&asset_id="+asset_id,
            beforeSend: function(xhr)
            {
                // Clean previous messages
                $('#'+av_info).empty();
            },
            success: function(json) 
            {
                if (json.success)
                {
                    // Refresh the dataTables info (now we have less data and it maybe still showing)
                    load_snapshot();
                    
                    reload_sections();
                }
                else
                {
                    var error = json.msg;
                    
                    if (typeof error == 'undefined' || error == null)
                    {
                        error = "<?php echo _('Error removing this asset from the group') ?>";
                    }
                    
                    show_notification(av_info, error, 'error', 0, true);
                    
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) 
            {
                show_notification(av_info, av_messages['unknown_error'], 'error', 0, true);
            }
        });
    }
}

/**
* Reload the data that is already loaded
* Used when a greybox is closed after a change, or group host removed...
*/
function reload_sections()
{
    if (hosts_loaded)
    {
        assets_dataTable.fnDraw();
    }

    if (software_loaded)
    {
        software_dataTable.fnDraw();
    }

    if (users_loaded)
    {
        users_dataTable.fnDraw();
    }

    if (properties_loaded)
    {
        properties_dataTable.fnDraw();
    }

    if (alarms_loaded)
    {
        alarms_dataTable.fnDraw();
    }

    if (events_loaded)
    {
        events_dataTable.fnDraw();
    }

    if (netflows_loaded)
    {
        netflows_dataTable.fnDraw();
    }
    
    if (plugins_loaded)
    {                
        plugins_dataTable.fnDraw();
    }
}

/**
*
* Check all assets in add assets greybox list
*
*/
function checkall(that) 
{
    var status = that.checked;

    $("input[type=checkbox]").each(function() 
    {
        if (this.id.match(/^check_[0-9A-Z]+/)) 
        {
            if(!$(this).prop('disabled'))
            {
                $(this).prop('checked', status);
                $(this).trigger('change');
            }
        }
    });
}


/**
*
* Handle host checkbox click event
*
*/
function select_host(host_id, val)
{
    if (typeof val == 'undefined')
    {
        delete selected_hosts[host_id];
    }
    else if (val == 'checked')
    {
        selected_hosts[host_id] = val;
    }
    
    // Update message
    $('#add_assets_msg').html(Object.keys(selected_hosts).length+" <?php echo _("Assets selected") ?>");
}


function submit_assets_form()
{
    $('#num_assets').val(Object.keys(selected_hosts).length);
    
    var i = 0;
    for (var k in selected_hosts)
    {
        $('#assets_form').append('<input type="hidden" name="host'+i+'" value="'+k+'" />');
        i++;
    }
    
    $('#assets_form').submit();
}


/**
*
* Refresh host checkbox selection
* Needed when we change the dataTable, we must save the selected items
*
*/
function refresh_checks()
{
    // Deselect the top main checkbox
    $('#allcheck').attr('checked', false);

    // Bind onselect event handler
    $('.check_host').change(function () {
        
        var check = $(this).attr('checked');
        var id    = $(this).attr('id').replace('check_', '');
        
        // This will save the host as selected
        select_host(id, check);
    });
    
    // Reselect hosts selected previously
    for (var k in selected_hosts)
    {
        $('#check_'+k).attr('checked', true);
    }
    
}


function get_service_tray(sData)
{
    var asset_id = sData[0].match(/hostid_([a-f\d]{32})/i); // Get the host ID
    var port     = sData[1];
    var service  = sData[2];
    
    return $.ajax(
    {
        type: 'GET',
        url:  'ajax/get_service_tray.php?asset_id='+asset_id[1]+'&service='+service+'&port='+port,
    });  
}


function toggle_service_tray(row)
{
    var nTr  = $(row)[0];
    var that = $(row);
    
    if (software_dataTable.fnIsOpen(nTr))
    {
        software_dataTable.fnClose(nTr);
    }
    else
    {
        var sData = software_dataTable.fnGetData(nTr);
        if (sData != null)
        {
            that.addClass('tray_wait');
            var data = get_service_tray(sData);
            $.when(data).then(function(theData){
                that.removeClass('tray_wait');
                software_dataTable.fnOpen(nTr, theData, 'tray_details');
            });
        }
    }
}

<?php
/* End of file asset_details.js.php */
/* Location: ./asset_details/js/asset_details.js.php */