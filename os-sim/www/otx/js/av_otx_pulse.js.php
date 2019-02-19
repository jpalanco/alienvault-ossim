<?php
header('Content-type: text/javascript');

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

(function($) 
{    
    $.fn.AV_otx_pulse_list = function(o) 
    {
        var opt = $.extend(
        {
            "notif_id"  : "pulse_notif"
        }, o || {});
        
        var __ajax_url_pulse = "<?php echo AV_MAIN_PATH ?>/otx/providers/otx_pulse_list.php";
        var __otx_base_url   = "<?php echo Otx::OTX_URL ?>";
        
        var __pulse_list_object = this.each(function()
        {
            var __this       = this;
            var __self       = $(this);
            var __pulse_list = {};
            
            __self.addClass('pulse_list_wrapper');
            
            //Append Table
            create_pulse_table();
            
            //Append Detail Layer
            create_detail_layer();
            
            __pulse_list = $('[data-pulse-list="table"]', __self).dataTable(
            {
                "bProcessing": true,
                "bServerSide": true,
                "sAjaxSource": __ajax_url_pulse,
                "iDisplayLength": 10,
                "bLengthChange": false,
                "sPaginationType": "full_numbers",
                "bFilter": false,
                "aoColumns": [
                    {"bSortable": false}
                ],
                oLanguage : 
                {
                    "sProcessing": "&nbsp;<?php echo _('Loading Pulses') ?> <img src='<?php echo AV_PIXMAPS_DIR ?>/loading3.gif'/>",
                    "sLengthMenu": "&nbsp;Show _MENU_ pulses",
                    "sZeroRecords": "&nbsp;<?php echo _('No pulses found') ?>",
                    "sEmptyTable": "&nbsp;<?php echo _('No pulses found') ?>",
                    "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                    "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ pulses') ?>",
                    "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 pulses') ?>",
                    "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total pulses') ?>)",
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
                "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) 
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
                            
                            var json = $.parseJSON({"sEcho": aoData[0].value, "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" })
                            fnCallback(json);
                        }
                    });
                },
                "fnRowCallback" : function(nRow, aData)
                {
                    var $cell = $(nRow).find('td');
                    var pulse = aData[0];

                    var code = format_pulse(pulse);
                    
                    $cell.html(code);
                },
                "fnDrawCallback" : function()
                {
                    $('[data-pulse-list="detail"], [data-pulse-list="detail-close"]').hide();
                    $('[data-pulse-list="count-pulse"]').text(__pulse_list.fnSettings()._iRecordsTotal);
                },
            });
            
            
            
            function format_pulse(pulse)
            {
                var $cell = $('<div>',
                {
                    'class': 'pulse_list_elem',
                    'click': function()
                    {
                        show_detail_layer(this, pulse);
                    },
                    'data-pulse-list': 'list-elem'
                });
                
                $cell.append('<div data-pulse-list="name" class="p_text pulse_name">'+ pulse.name +'</div>');
                $cell.append('<span data-pulse-list="c-date" class="pulse_c_date">'+ pulse.created +'</span> by ');
                $cell.append('<span data-pulse-list="author" class="pulse_author">'+ pulse.author_name +'</span>');
                $cell.append('<div data-pulse-list="descr" class="pulse_descr">'+ pulse.description +'</div>');
                
                var $tag_div = $('<div data-pulse-list="tag_list" class="pulse_tag_list">').appendTo($cell);
                $.each(pulse.tags, function(i, v)
                {
                    $('<div/>',
                    {
                        "class": "pulse_tag",
                        "text" : v,
                        "click": function(e)
                        {
                            e.stopPropagation();
                            e.preventDefault();
                            
                            window.open(__otx_base_url + 'browse/pulses/?q=tag:' + v + '<?php echo Otx::get_anchor('&') ?>', '_blank');
                        },
                        "data-pulse-list": "tag-list"
                    }).appendTo($tag_div);
                });
                
                $('<button/>',
                {
                    "class": "pulse_detail_link av_b_secondary",
                    "text" : "<?php echo _('View in OTX') ?>",
                    "click": function(e)
                    {
                        e.stopPropagation();
                        e.preventDefault();
                        
                        window.open(__otx_base_url +'pulse/'+ pulse.id + '<?php echo Otx::get_anchor() ?>', '_blank');
                    },
                    "data-pulse-list": "view_account",
                }).appendTo($cell);
                
                return $cell;
            }
            
            
            function create_pulse_table()
            {
                __self.append("<div id='pulse_notif'></div><table data-pulse-list='table'><thead><tr><th class='pulse_header'><?php echo _('OTX Subscriptions') ?> (<span data-pulse-list='count-pulse'></span>)</th></tr></thead><tbody><tr><td></td></tr></tbody></table>");
            }
            
            
            function create_detail_layer()
            {
                $('<div>', 
                {
                    'class'          : 'pulse_detail',
                    'data-pulse-list': 'detail'
                }).appendTo(__self);
                
                $('<div>', 
                {
                    'class'          : 'pulse_detail_close',
                    'data-pulse-list': 'detail-close'
                }).appendTo(__self).on('click', function(e)
                {
                    hide_detail_layer();
                });
            }
            
            /* to do: Remove css classes for jquery binding  */
            function show_detail_layer(elem, pulse)
            {
                //Removing all the selected class in pulse list
                $('[data-pulse-list="list-elem"]', __self).removeClass('pulse_selected');
                //Adding the selected class to the selected pulse
                $(elem).addClass('pulse_selected');
                
                //Add class to the wrapper for list with detail opened 
                __self.addClass('pulse_elem_detail');
                //Add class to the the pulses for list with detail opened
                $('[data-pulse-list="list-elem"]', __self).addClass('pulse_elem_detail');
                
                //Hide fields for list with detail opened 
                $('[data-pulse-list="descr"], [data-pulse-list="tag-list"], [data-pulse-list="view_account"]', __self).hide();
                
                //Hidding datatables pagination
                var dt = __pulse_list.fnSettings();
                if (dt._iRecordsTotal <= dt._iDisplayLength)
                {
                    $('.dataTables_info, .dataTables_paginate', __self).hide();
                }

                
                //Showing the close detail button
                $('[data-pulse-list="detail-close"]', __self).show();
                
                //Displaying the layer tho show the pulse detail and create the pulse object.
                $('[data-pulse-list="detail"]', __self).show().AV_otx_pulse(pulse.id);
                
                //Scrolling to the header of the table
                scroll_to($('[data-pulse-list="wrapper"]'));
            }
            
            
            function hide_detail_layer()
            {
                //Remove all the selected class in pulse list
                $('[data-pulse-list="list-elem"]', __self).removeClass('pulse_selected');
                
                //Remove class to the the pulses for list with detail opened
                $('[data-pulse-list="list-elem"]', __self).removeClass('pulse_elem_detail');
                
                //Show fields for list with detail closed 
                $('[data-pulse-list="descr"], [data-pulse-list="tag-list"], [data-pulse-list="view_account"]', __self).show();
                
                //Hidding datatables pagination
                $('.dataTables_info, .dataTables_paginate', __self).show();
                
                //Showing the close detail button
                $('[data-pulse-list="detail-close"]', __self).hide();
                
                //Displaying the layer tho show the pulse detail and create the pulse object.
                $('[data-pulse-list="detail"]', __self).hide();
            }
            
            
            this.reload = function()
            {
                try
                {
                    __pulse_list.fnDraw();
                }
                catch (Err){console.log(Err)}
            }

        });

        return __pulse_list_object;
    }
})(jQuery);





(function($) 
{    
    $.fn.AV_otx_pulse = function(id, o) 
    {
        var opt = $.extend(
        {
            "pulse"     : {},
            "load_ajax" : true,
            "notif_id"  : "pulse_notif"
        }, o || {});


        var __ajax_url_pulse = "<?php echo AV_MAIN_PATH ?>/otx/providers/otx_pulse.php"
        var __otx_base_url   = "<?php echo Otx::OTX_URL ?>";
        
        var __pulse_object = this.each(function()
        {
            var __this = this;
            var __self = $(this);
            
            this.id          = id;
            this.name        = opt.pulse.name || "";
            this.author_name = opt.pulse.author_name || "";
            this.description = opt.pulse.description || "";
            this.created     = opt.pulse.created || "";
            this.modified    = opt.pulse.modified || "";
            this.tags        = opt.pulse.tags || [];
            this.indicators  = opt.pulse.indicators || [];
            
            
            this.init = function()
            {
                __self.empty();
                
                if (opt.load_ajax)
                {
                    __this.show_loading();
                    
                    __this.load_detail()
                    .done(__this.format_pulse)
                    .always(__this.hide_loading);
                }
                else
                {
                    __this.format_pulse();
                }
                
                //Do Not Delete!! Removing the binding for the pulse detail loading scroll 
                $(window).on('unload', function()
                {
                    $(top.window).off('scroll.pulse_loading');
                    
                    return true;
                });
            };
            
            this.load_detail = function()
            {
                var params = 
                {
                    "action": "detail",
                    "data"  :
                    {
                        "pulse_id": __this.id
                    }
                };
        
                return $.ajax(
                {
                    data    : params,
                    type    : "POST",
                    url     : __ajax_url_pulse,
                    dataType: "json",
                    success : function(data)
                    {
                        try
                        {
                            __this.name        = data.name || "";
                            __this.author_name = data.author_name || "";
                            __this.description = data.description || "";
                            __this.created     = data.created || "";
                            __this.modified    = data.modified || "";
                            __this.tags        = data.tags || [];
                            __this.indicators  = data.indicators || [];
                            
                            $('[data-pulse="wrapper"]').show();
                        }
                        catch (Err)
                        {
                            var msg = "<?php echo Util::js_entities(_('Cannot Load the Pulse Detail at this moment.')) ?>";
                            p_notif(msg, 'nf_error');
                            
                            $('[data-pulse="wrapper"]').hide();
                        }
                    },
                    error: function(XMLHttpRequest)
                    {
                        //Checking expired session
                        var session = new Session(XMLHttpRequest, '');
                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }
                        
                        var msg = XMLHttpRequest.responseText;
                        p_notif(msg, 'nf_error');

                        $('[data-pulse="wrapper"]').hide();
                    }
                });
            };
            
            
            this.format_pulse = function()
            {
                var $cell = $('<div/>',
                {
                    "class"     :"pulse_detail_wrapper",
                    "data-pulse": "wrapper"
                }).appendTo(__self);
                
                
                $('<a/>',
                {
                    "class"     :"p_text pulse_name",
                    "html"      : __this.name,
                    "data-pulse": "name",
                    "href"      : __otx_base_url + 'pulse/' + __this.id + '<?php echo Otx::get_anchor() ?>',
                    "target"    : "_blank"
                }).appendTo($cell);
                
                $cell.append('<div class="pulse_name_sec"> \
                                <div data-pulse="c-date"><span><?php echo _("Last Updated:") ?></span> '+ __this.modified +'</div>\
                                <div data-pulse="c-date"><span><?php echo _("Created:") ?></span> '+ __this.created +'</div>\
                                <div data-pulse="author"><span><?php echo _("Author:") ?></span> '+ __this.author_name +'</div>\
                              </div>');
                
                $cell.append('<div data-pulse="descr" class="pulse_descr">'+ __this.description +'</div>');
                
                var tag_div = $('<div data-pulse="tag-list" class="pulse_tag_list"></div>')
                $.each(__this.tags, function(i, v)
                {
                    $('<div/>',
                    {
                        "class": "pulse_tag",
                        "text" : v,
                        "click": function(e)
                        {
                            e.stopPropagation();
                            e.preventDefault();
                            
                            window.open(__otx_base_url + 'browse/pulses/?q=tag:' + v + '<?php echo Otx::get_anchor('&') ?>', '_blank');
                        }
                    }).appendTo(tag_div);
                });
                
                $cell.append(tag_div);
                
                $cell.append("<div class='ioc_table_wrapper'> \
                            <table data-pulse='ioc-table' class='ioc_table table_data'> \
                                <thead><tr> \
                                    <th><?php echo _('Type') ?></th> \
                                    <th><?php echo _('Indicator') ?></th> \
                                    <th></th> \
                                </tr></thead> \
                                <tbody><tr> \
                                    <td></td> \
                                    <td></td> \
                                    <td></td> \
                                </tr></tbody> \
                             </table> \
                             </div> \
                             <div class='clear_layer'></div>");
                        
    
                $('.ioc_table', $cell).dataTable(
                {
                    "iDisplayLength": 10,
                    "bLengthChange": true,
                    "aLengthMenu": [5, 10, 20],
                    "bDeferRender": true,
                    "bSearchInputType": "search",
                    "sPaginationType": "full_numbers",
                    "bFilter": true,
                    "aaData": __this.indicators,
                    "aoColumns": 
                    [
                        {"mDataProp": "type", "bSortable": true, "sClass": "left"},
                        {"mDataProp": "indicator", "bSortable": true, "sClass": "left"},
                        {"mDataProp": null, "bSortable": false, "sClass": "center", "sWidth": "30px"},
                    ],
                    oLanguage : 
                    {
                        "sProcessing": "&nbsp;<?php echo _('Loading Indicators') ?> <img src='<?php echo AV_PIXMAPS_DIR ?>/loading3.gif'/>",
                        "sLengthMenu": "&nbsp;Show _MENU_ Indicators",
                        "sZeroRecords": "&nbsp;<?php echo _('No Indicators found') ?>",
                        "sEmptyTable": "&nbsp;<?php echo _('No Indicators found') ?>",
                        "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                        "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ Indicators') ?>",
                        "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 Indicators') ?>",
                        "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total Indicators') ?>)",
                        "sInfoPostFix": "",
                        "sInfoThousands": ",",
                        "sSearch": "<?php echo _('Search') ?>:",
                        "sUrl": "",
                        "oPaginate": 
                        {
                            "sFirst":    "<?php echo _('First') ?>",
                            "sPrevious": "<?php echo _('Previous') ?>",
                            "sNext":     "<?php echo _('Next') ?>",
                            "sLast":     "<?php echo _('Last') ?>"
                        }
                    },
                    "fnRowCallback" : function(nRow, aData)
                    {
                        var cell = $('td:last-child', nRow).empty();
                        
                        $('<a/>', 
                        {
                            "href"  : __otx_base_url + "indicator/"+ aData.type +"/"+ aData.indicator + '<?php echo Otx::get_anchor() ?>',
                            "target": "_blank",
                            "html"  : '<img src="/ossim/pixmaps/show_details.png" height="16px"/>'
                        }).appendTo(cell);
                    }
                });
            }
            
            
            this.show_loading = function()
            {
                var $loading = $('<div>',
                {
                    'class'     : 'pulse_loading',
                    'html'      : "<?php echo _('Loading Pulse Detail') ?> <img src='/ossim/pixmaps/loading.gif' align='absmiddle'/>",
                    'data-pulse': 'loading'
                }).appendTo(__self);
                
                var min = __self.offset().top + 100;
                var max = __self.offset().top + __self.height();
                                
                $(top.window).off('scroll.pulse_loading').on('scroll.pulse_loading', function()
                {
                    var pos = $(this).scrollTop();
                    
                    if (pos > min && pos < max)
                    {
                        $loading.offset({"top": pos});
                    }
                    
                });
            }
            
            this.hide_loading = function()
            {
                $(top.window).off('scroll.pulse_loading');
                
                $('[data-pulse="loading"]', __self).remove();
            }
            
            this.init();
            
        });
        
        
        function p_notif(msg, type)
        {
            show_notification(opt.notif_id, msg, type, 10000, true);
        }
        
        
        return __pulse_object;
    }
})(jQuery);
