<?php
header("Content-type: text/javascript");

/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2015 AlienVault
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

(function ($)
{
    $.fn.av_note = function (options)
    {
        var total_notes = 0;
        
        // Asset Data
        var defaults = {
            "asset_type"   : "",
            "asset_id"     : "",
            "notif_div"    : "",
			afterAdd : function() {
				return true;
			},
            afterDelete : function() {
				return true;
			}
        };
        
        var __keys  = {"yes": "<?php echo Util::js_entities(_('Yes')) ?>","no": "<?php echo Util::js_entities(_('No')) ?>"};
        
        var options = $.extend(defaults, options);

        this.each(function()
        {
            
            __create_container.call(this);
            
            __load_notes.call(this);

            __load_handlers.call(this);

            return this;
        });


        /****************
        /** Functions **
        ****************/

        /*
         *  Function to create the html code
         */
        function __create_container()
        {   
            var note_text_div = $('<div></div>').appendTo(this);
            
            var note_text = $('<textarea></textarea>',
            {
                'id'        : 'note_text',
                'data-bind' : 'note_text'
            }).appendTo(note_text_div);
            
            var note_save = $('<input>',
            {
                'type'      : 'button',
                'id'        : 'save_note',
                'data-bind' : 'save_note',
                'value'     : '<?php echo _("Add Note")?>'
            }).appendTo(note_text_div);
            
            $('<div></div>',
            {
                'class'        : 'clear_layer'
            }).appendTo(note_text_div);
            
            $('<div></div>',
            {
                'id'        : 'note_list',
                'data-bind' : 'note_list'
            }).appendTo(this);
        }
        
        /*
         *  Function to Load the handlers.
         */
        function __load_handlers()
        {
            $('#save_note').on('click', function()
            {
                __add_note.call(this);
            });
        }
        
        /*
         *  Function to Load the Notes.
         */
        function __load_notes()
        {
            $('#note_list').scrollLoad(
            {
                url: "<?php echo AV_MAIN_PATH . '/av_asset/common/providers/get_asset_notes.php' ?>",
                notif_div: 'asset_notif',
                getData : function()
                {
                    var data   = {};
    
                    data["asset_id"]   = options.asset_id;
                    data["asset_type"] = options.asset_type;
                    data["max_notes"]  = 5;
                    data["from"]       = $(".note_row").length;
    
                    return data;
                },
                start : function()
                {
                    $('<img id="notes_loading" src="/ossim/pixmaps/loading3.gif"/>').appendTo("#note_list");
                },
                ScrollAfterHeight: 95,          //this is the height in percentage
                onload : function(data)
                {
                    $('#notes_loading').remove();
                    $.each(data.data, function(i, n)
                    {   
                        __initialize_note.call(this, n, 'append');
                    });
                },
                continueWhile : function(resp)
                {
                    if($('.note_row').length == total_notes)
                    {
                        return false;
                    }
                    return true;
                }
            });
    
            $('#note_list').empty();
    
            var data   = {};
    
            data["asset_id"]   = options.asset_id;
            data["asset_type"] = options.asset_type;
            data["max_notes"]  = 5;
            data["from"]       = 0;
    
            return $.ajax(
            {
                   data: data,
                   type: "POST",
                   url: "<?php echo AV_MAIN_PATH . '/av_asset/common/providers/get_asset_notes.php' ?>",
                   dataType: "json",
                   success: function(data)
                   {
                        total_notes = data.total;
    
                        $.each(data.data, function(i, n)
                        {                            
                            __initialize_note.call(this, n, 'append');
                        });
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

                        if (typeof show_notification == 'function' && options.notif_div != '')
                        {
                            var msg_error = XMLHttpRequest.responseText;
    
                            show_notification(options.notif_div, msg_error, 'nf_error', 5000, true);
                        }
                   }
            });
        }

        /*
         *  Function to initialize a note.
         */
        function __initialize_note(n, append_type)
        {   
            var note_row = $('<div></div>',
            {
                'class'    :'note_row',
                'id'       : 'note_' + n.id,
                'mouseover': function()
                {
                    $('#delete_link_' + n.id).show();
                    $(this).css('background-color', '#E7E7E7');
                },
                'mouseout': function()
                {
                    $('#delete_link_' + n.id).show();
                    $(this).css('background-color', 'transparent');
                }
            });
    
            if (append_type == 'append')
            {
                note_row.appendTo('#note_list');
            }
            else
            {
                note_row.prependTo('#note_list');
            }
    
            var detail_header = $('<div></div>').appendTo(note_row);
    
            $('<div></div>',
            {
                'class': 'fleft',
                'html' : n.date + ' <?php echo _('by'); ?> <strong>' + n.user + '</strong>',
            }).appendTo(detail_header);
    
            $('<div></div>',
            {
                'id'   : 'delete_link_' + n.id,
                'class': 'fright delete_links',
                'html' : '<img class="notes_delete" src="/ossim/pixmaps/delete.png">',
                'click': function()
                {
                    var msg = "<?php echo _('Are you sure you want to delete this note?') ?>";
                    
                    av_confirm(msg, __keys).done(function()
                    {
                        __delete_note.call(this, n.id);
                    });
                    
                }
            }).appendTo(detail_header);
    
            $('<div></div>',
            {
                'class': 'clear_layer'
            }).appendTo(detail_header);
            
            n.note = htmlentities(n.note, "ENT_QUOTES");
            n.note = n.note.replace(/(?:\r\n|\r|\n)/g, '<br />');
    
            var note_text = $('<div></div>',
            {
                'class': 'note_txt',
                'note' : n.id,
                'html' : n.note,
                'title': "<?php echo _('Click to edit this Note') ?>",
                'mouseover' : function()
                {
                    $('#edit_tip_' + n.id).show();
                },
                'mouseout' : function()
                {
                    $('#edit_tip_' + n.id).hide();
                }
            }).appendTo(note_row);
    
            if (n.editable)
            {
                note_text.editInPlace(
                {
                    callback: function(unused, enteredText, prevtxt)
                    {
                        var id  = $(this).attr('note');
    
                        if(__edit_note.call(this, id, enteredText))
                        {
                            return enteredText;
                        }
                        else
                        {
                            return prevtxt;
                        }
                    },
                    preinit: function(node)
                    {
                        var txt = $(node).html().replace(/<br>/g, "\n").replace(/\n+/g, "\n");
                        $(node).html(txt);
                    },
                    postclose: function(node)
                    {
                        var txt = $(node).html().replace(/\n/g, '<br>');
                        $(node).html(txt);
                        $('#edit_tip').hide();
                        $('.note_row').css('background-color', 'transparent');
                    },
                    text_size     : 14,
                    bg_over       : 'transparent',
                    field_type    : "textarea",
                    on_blur       : 'save',
                    value_required: true,
                    show_buttons  :   true,
                    cancel_button : '<button class="editInPlaceCancel small av_b_secondary"><?php echo _('Cancel')?></button>',
                    save_button   : '<button class="editInPlaceSave small"><?php echo _('Save')?></button>'
                });
            }
        }
        
        /*
         *  Function to add a note.
         */
        function __add_note()
        {
            var note_text = $('#note_text').val();
            
            if (note_text != '')
            {
                $('#note_text').val('');
                
                //AJAX data
            
                var data = 
                {
                    "txt"       : note_text,
                    "asset_type": options.asset_type,
                    "asset_id"  : options.asset_id, 
                    "token"     : Token.get_token("add_note"),
                    "action"    : "add_note",
                };
            
                return $.ajax(
                {
                    type: "POST",
                    url: "<?php echo AV_MAIN_PATH ?>/notes/controllers/note_actions.php",
                    data: data,
                    dataType: "json",
                    success: function(data)
                    {
                        __initialize_note.call(this, data, 'prepend');
                        
                        total_notes++;
                        
                        defaults.afterAdd.call(this);
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
                        
                        if (typeof show_notification == 'function' && options.notif_div != '')
                        {
                            var _msg  = XMLHttpRequest.responseText;
            
                            show_notification(options.notif_div, _msg, 'nf_error', 15000, true);
                        }
                    }
                });
            }
        }
        
        /*
         *  Function to Delete a Note.
         */
        function __delete_note(id)
        {
            //AJAX data
    
            var data =
            {
                "asset_type": options.asset_type,
                "token"     : Token.get_token("delete_note"),
                "action"    : "delete_note",
                "note_id"   : id
            };
    
            return $.ajax(
            {
                type: "POST",
                url: "<?php echo AV_MAIN_PATH ?>/notes/controllers/note_actions.php",
                data: data,
                dataType: "json",
                success: function(data)
                {
                    $('#note_' + id).remove();
                    
                    total_notes--;
                    
                    defaults.afterDelete.call(this);
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

                    if (typeof show_notification == 'function' && options.notif_div != '')
                    {
                        var _msg  = XMLHttpRequest.responseText;
    
                        show_notification(options.notif_div, _msg, 'nf_error', 15000, true);
                    }
                }
            });
        }
        
        /*
         *  Function to Edit a Note.
         */        
        function __edit_note(id, txt)
        {
            //AJAX data
    
            var data =
            {
                "asset_type" : options.asset_type,
                "note_id"    : id,
                "txt"        : txt,
                "token"      : Token.get_token("edit_note"),
                "action"     : "edit_note"
            };
    
            return $.ajax(
            {
                type: "POST",
                url: "<?php echo AV_MAIN_PATH ?>/notes/controllers/note_actions.php",
                data: data,
                dataType: "json",
                error: function(XMLHttpRequest, textStatus, errorThrown)
                {
                    //Checking expired session
                    var session = new Session(XMLHttpRequest, '');
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
    
                    if (typeof show_notification == 'function' && options.notif_div != '')
                    {
                        var _msg  = XMLHttpRequest.responseText;
    
                        show_notification(options.notif_div, _msg, 'nf_error', 15000, true);
                    }
                }
            });
        }
      
    }
})(jQuery);
