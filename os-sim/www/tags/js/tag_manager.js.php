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

// Get type
$tag_type = GET('tag_type');

?>

/****************************************************
 ********************** Actions *********************
 ****************************************************/

function do_action(data, $msg_box, callbacks)
{
    data['token'] = Token.get_token('tag_form');
    var url = data.tag_type == "incident" ? "/ossim/incidents/incidenttag.php" : '../controllers/tag_actions.php';
    $.ajax({
        type: 'POST',
        url: url,
        data: data,
        dataType: 'json',
        beforeSend: function (xhr)
        {

        },
        error: function (XMLHttpRequest, textStatus, errorThrown)
        {
            //Checking expired session
            var session = new Session(XMLHttpRequest, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            var error_msg = XMLHttpRequest.responseText;

            $msg_box.html(notify_error(error_msg)).fadeIn(2000);
        },
        success: function (data)
        {
            var cnd_1 = (typeof(data) == 'undefined' || data == null);
            var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_1 == true) ? av_messages['unknown_error'] : JSON.stringify(data.data);
                error_msg = "<div style='padding-left: 10px;'>" + error_msg + "</div>";

                $msg_box.html(notify_error(error_msg)).fadeIn(2000);
            }
            else
            {
                if (0 != callbacks.length)
                {
                    for (i = 0; i < callbacks.length; i++)
                    {
                        if (typeof callbacks[i] == 'function')
                        {
                            callbacks[i]();
                        }
                    }
                }
            }
        }
    });
}

$(document).ready(function ()
{
    /***************************************************
     *********************** Vars **********************
     ***************************************************/

    // Form
    var $tag_id = $('#tag_id');
    var $tag_name = $('#tag_name');
    var $tag_class = $('#tag_class');
    var $tag_type = $('#tag_type');
    var $tag_description = $('#tag_description');
    // Preview tag
    var $tag_preview = $('#tag_preview');
    var default_preview_class = 'av_tag_1';
    var default_preview_name = 'Label';

    // Message box
    var $av_info = $('#av_info');


    /****************************************************
     ************ Ajax Validator Configuration **********
     ****************************************************/

    var av_config = {
        validation_type: 'complete',    // single|complete
        errors: {
            display_errors: 'summary',      // all | summary | field-errors
            display_in: 'av_info'
        },
        form: {
            id: 'tag_form',
            url: $('#tag_form').attr('action') + "?action=save_tag"
        },
        actions: {
            on_submit: {
                id: 'send',
                success: '<?php echo _('Save')?>',
                checking: '<?php echo _('Saving')?>'
            }
        }
    };

    var ajax_validator = new Ajax_validator(av_config);


    /**********************************************************
     ******************** TAGS INTERACTION ********************
     **********************************************************/

    // Manage preview
    function manage_preview(tag_name, tag_class)
    {
        if (tag_name)
        {
            $tag_preview.text(tag_name);
        }

        $tag_preview.attr('class', '');
        $tag_preview.addClass(tag_class);
        $tag_class.val(tag_class);
    }

   // Fill form fields
    function fill_form_fields(tag_id, tag_name, tag_class, tag_description)
    {
        $tag_id.val(tag_id);
        $tag_name.val(tag_name);
        $tag_class.val(tag_class);
        $tag_description.val(tag_description);
        manage_preview(tag_name, tag_class);
    }

    // Clear form fields
    function clear_form_fields()
    {
        $tag_id.val('');
        $tag_name.val('');
        $tag_class.val('');
        manage_preview(default_preview_name, default_preview_class);
    }

    // Edit tag style
    $('.tag_style').on('click', function ()
    {
        var tag_class = $(this).attr('class').split(' ').pop();

        if (!$tag_preview.hasClass(tag_class))
        {
            manage_preview('', tag_class);
        }
    });

    // Fill tag name
    $tag_name.on('keyup', function ()
    {
        if (!$(this).val())
        {
            $tag_preview.text(default_preview_name);
        }
        else
        {
            $tag_preview.text($tag_name.val());
        }

        $tag_preview.attr('title', $tag_name.val());
        $tag_preview.tipTip({defaultPosition: 'bottom'});
    });


    /****************************************************
     *************** DataTable Configuration ************
     ****************************************************/
    var sAjaxSource = $tag_type.val() == "incident" ? "/ossim/incidents/incidenttag.php" : '../providers/get_tags.php?tag_type='+$tag_type.val();
    var tag_table = $('#tag_table').dataTable({
        'bProcessing': true,
        'bServerSide': true,
        'sAjaxSource': sAjaxSource,
        'iDisplayLength': 5,
        'bPaginate': true,
        'bLengthChange': false,
        "bSearchInputType": "search",
        'bJQueryUI': true,
        'aaSorting': [[0, "ASC"]],
        'aoColumns': [
            {'bSortable': true},
            {'bSortable': false}
        ],
        oLanguage: {
            'sProcessing': '<?php echo _('Loading') ?>...',
            'sLengthMenu': 'Show _MENU_ entries',
            'sZeroRecords': '<?php echo _('No labels found') ?>',
            'sEmptyTable': '<?php echo _('No labels found') ?>',
            'sLoadingRecords': '<?php echo _('Loading') ?>...',
            'sInfo': '<?php echo _('Showing _START_ to _END_ of _TOTAL_ labels') ?>',
            'sInfoEmpty': '<?php echo _('Showing 0 to 0 of 0 labels') ?>',
            'sInfoFiltered': '(<?php echo _('filtered from _MAX_ total labels') ?>)',
            'sInfoPostFix': '',
            'sInfoThousands': ',',
            'sSearch': '<?php echo _('Search by name') ?>:',
            'sUrl': '',
            'oPaginate': {
                'sFirst': '<?php echo _('First') ?>',
                'sPrevious': '<?php echo _('Previous') ?>',
                'sNext': '<?php echo _('Next') ?>',
                'sLast': '<?php echo _('Last') ?>'
            }
        },
        'fnRowCallback': function (nRow, aData)
        {
            var $row = $(nRow);
            var tag_id = $row.attr('id');
            var data = {};
            var remove_row = '';
            $('td:eq(0)', nRow).html(
		'<span class="av_tag ' + aData[0] + '" title="' + aData[1] + '">' + aData[1] + '</span>'+
                '<input type="hidden" value="'+aData[2]+'"/>'
	    );
            $('td:eq(0)', nRow).find('span').tipTip({defaultPosition: 'right'});
            $('td:eq(0)', nRow).find('span').on('click', function ()
            {
                var tag_name = $(this).text();
                var tag_class = $(this).attr('class').split(' ').pop();
                var tag_description = $(this).next().val();
                if ($row.hasClass('selected'))
                {
                    $row.removeClass('selected');
                    clear_form_fields();
                }
                else
                {
                    tag_table.$('tr.selected').removeClass('selected');
                    $row.addClass('selected');
                    fill_form_fields(tag_id, tag_name, tag_class, tag_description);
                }
            });

            $('td:eq(1)', nRow).html('<img class="delete" src="/ossim/pixmaps/delete.png" border="0" height="15px" alt="" />');
            $('td:eq(1)', nRow).find('.delete').on('click', function ()
            {
                data = {
                    'tag_type': $tag_type.val(),
                    'tag_id': tag_id,
                    'action': 'delete_tag'
                };

                var remove_row = function() {
                    tag_table.fnDeleteRow(nRow);
                };

                var msg_confirm = '<?php echo _('Are you sure you would like to delete this label?') ?>';
                var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};

                av_confirm(msg_confirm, keys).done(function () {
                    do_action(data, $av_info, [clear_form_fields, remove_row])
                });
            });
        },
        "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) 
        {
            oSettings.jqXHR = $.ajax( 
            {
                "dataType": 'json',
                "type"    : "POST",
                "url"     : sSource,
                "data"    : aoData,
                "success" : function (json) 
                {                            
                    $(oSettings.oInstance).trigger('xhr', oSettings);
                    //This is for keeping pagination whe the page is back from alarm detail.
                    oSettings.iInitDisplayStart = oSettings._iDisplayStart;
                    if (json.iDisplayStart !== undefined) 
                    {
                        oSettings.iInitDisplayStart = json.iDisplayStart;
                    }
    
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
                    fnCallback({"sEcho": aoData[0].value, "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": ""});
                }
            });
        }
    });


    /****************************************************
     ********************* ACTIONS **********************
     ****************************************************/

    // Set default tag class
    manage_preview(default_preview_name, default_preview_class);

    // Send form
    $('#send').click(function ()
    {
        if (ajax_validator.check_form() == true)
        {
            var data = {
                'tag_id': $tag_id.val(),
                'tag_name': $tag_name.val(),
                'tag_class': $tag_class.val(),
                'tag_type': $tag_type.val(),
                'action': 'save_tag'
            };

            var redraw_table = function()
            {
                tag_table.fnDraw();
            };

            do_action(data, $av_info, [clear_form_fields, redraw_table]);
        }
    });

    // Cancel form
    $('#cancel').click(function ()
    {
        if (typeof parent.GB_close == 'function')
        {
            parent.GB_close();
        }
    });
});
