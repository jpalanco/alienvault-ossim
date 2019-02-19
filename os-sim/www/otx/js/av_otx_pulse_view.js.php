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
    $.fn.AV_otx_pulse_view = function(o) 
    {       
        var otx_info = $.extend(
        {
            "type"      : "",
            "id"        : "",
            "pulse_list": {},
            "rep_list"  : []
        }, o || {});
        
        var __dt_lg = 
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
            "oPaginate": {
                "sFirst":    "<?php echo _('First') ?>",
                "sPrevious": "<?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?>",
                "sLast":     "<?php echo _('Last') ?>"
            }
        };
        
        var __otx_url = "<?php echo Otx::OTX_URL ?>";
        var __rep_url = "<?php echo Reputation::getlabslink('XXXX') ?>";
        
        var __pulse_object = this.each(function()
        {
            var __this = this;
            var __self = $(this);
            var dt     = null;
            
            
            this.init = function()
            {             
                __self.empty();
                
                var t_pulses = Object.keys(otx_info.pulse_list).length;
                var t_rep    = Object.keys(otx_info.rep_list).length;
                
                if (t_pulses == 0 && t_rep == 0)
                {
                    var msg = "<?php echo _('OTX information is not available. You are no longer subscribed to the pulse associated with this ####.') ?>";
                    msg = msg.replace('####', (otx_info.type == 'alarm') ? "<?php echo _('alarm') ?>" : "<?php echo _('event') ?>");
                    
                    show_notification('ioc_view_notif', msg, 'nf_info', 0 , true);
                }
                else 
                {
                    __self.addClass('with_iocs');
                    
                    if (t_pulses > 0)
                    {
                        __this.load_pulse_template();
                    }
                    
                    if (t_rep > 0)
                    {
                        __this.load_reputation_template();
                    }
                }
            };
            
            
            this.update_indicators = function()
            {
                var id      = $(this).val() || '';
                var p_info  =
                {
                    'name'  : '',
                    'descr' : '',
                    'iocs'  : []
                }
                
                $.ajax(
                {
                    data    : {"action": "pulse_info", "pulse": id, "type": otx_info.type, "id": otx_info.id},
                    type    : "POST",
                    url     : "<?php echo AV_MAIN_PATH ?>/otx/providers/otx_reputation_info.php",
                    dataType: "json",
                    success : function(data)
                    {
                        p_info.name  = data.name  || '';
                        p_info.descr = data.descr || '';
                        p_info.iocs  = data.iocs  || [];
                        redraw_pulse_section(p_info);
                    },
                    error: function(XMLHttpRequest)
                    {
                        var msg = XMLHttpRequest.responseText
                        show_notification('ioc_view_notif', msg, 'nf_error');
                        
                        redraw_pulse_section(p_info);
                    }
                });
                
                function redraw_pulse_section(pulse)
                {
                    $('<a/>',
                    {
                        'html': pulse.name,
                        'href': __otx_url + 'pulse/' + id + '<?php echo Otx::get_anchor() ?>',
                        'target': '_blank'
                    }).appendTo($('[data-ioc="pulse-name"]', __self).empty());
                    
                    if (pulse.descr != '')
                    {
                        $('[data-ioc="pulse-descr"]', __self).html(pulse.descr).show();
                    }
                    else
                    {
                        $('[data-ioc="pulse-descr"]', __self).hide();
                    }
                    
                    if (dt)
                    {
                        dt.fnClearTable();
                        dt.fnAddData(pulse.iocs);
                        dt.fnAdjustColumnSizing();
                    }
                }
            }
                 
                       
            this.load_pulse_template = function()
            {
                var html = ' \
                <div class="data_header"> \
                    <div class="pulse_h_title"><?php echo _('OTX Pulse') ?></div> \
                    <div class="pulse_h_select"> \
                        <select data-ioc="pulse_select"></select> \
                    </div> \
                </div> \
                <div id="pulse_list_wrap" class="data_wrapper"> \
                    <div class="pulse_name" data-ioc="pulse-name"></div> \
                    <div class="pulse_descr" data-ioc="pulse-descr"></div> \
                    <div class="i_title"><?php echo _('OTX Indicators of Compromise:') ?></div> \
                    <table data-ioc="pulse-table" class="table_data"> \
                        <thead> \
                            <th><?php echo _('Type') ?></th> \
                            <th><?php echo _('Indicator') ?></th> \
                            <th></th> \
                        </thead> \
                        <tbody> \
                            <td colspan="3"></td> \
                        </tbody> \
                    </table> \
                </div>';
                
                __self.append(html);
            
                $select = $('[data-ioc="pulse_select"]', __self).on('change', __this.update_indicators);
                $.each(otx_info.pulse_list, function(p_id, p)
                {
                    $('<option/>',
                    {
                        'value' : p_id,
                        'html'  : p.name,
                    }).appendTo($select);
                });
                
                $select.select2(
                {
                    hideSearchBox: true
                });
                
                dt = $('[data-ioc="pulse-table"]', __self).dataTable(
                {
                    "iDisplayLength": 5,
                    "bLengthChange": true,
                    "aLengthMenu": [5, 10, 20],
                    "bDeferRender": true,
                    "bSearchInputType": "search",
                    "sPaginationType": "full_numbers",
                    "bJQueryUI": true,
                    "bFilter": true,
                    "aaData": [],
                    "aoColumns": [
                        {"mDataProp": "type",  "bSortable": true,  "sClass": "left"},
                        {"mDataProp": "value", "bSortable": true,  "sClass": "left"},
                        {"mDataProp": null,    "bSortable": false, "sClass": "center", "sWidth": "30px"},
                    ],
                    oLanguage : __dt_lg,
                    "fnRowCallback" : function(nRow, aData)
                    {
                        var cell = $('td:last-child', nRow).empty();
                        
                        $('<a/>', 
                        {
                            "href"  : __otx_url + "indicator/"+ aData.type +"/"+ aData.value + '<?php echo Otx::get_anchor() ?>',
                            "target": "_blank",
                            "html"  : '<img src="/ossim/pixmaps/show_details.png" height="16px"/>'
                        }).appendTo(cell);
                    }                    
                });
                        
                $select.trigger('change');
            }
            
            
            this.load_reputation_template = function()
            {
                var html = ' \
                <div class="data_header"><?php echo _('OTX IP Reputation') ?></div> \
                <div class="data_wrapper"> \
                    <table data-ioc="reputation-table" class="table_data"> \
                        <thead> \
                            <th><?php echo _('Type') ?></th> \
                            <th><?php echo _('Indicator') ?></th> \
                            <th><?php echo _('Activity') ?></th> \
                            <th><?php echo _('Reliability') ?></th> \
                            <th><?php echo _('Priority') ?></th> \
                            <th></th> \
                        </thead> \
                        <tbody> \
                            <td colspan="6"></td> \
                        </tbody> \
                    </table> \
                </div>';
                
                __self.append(html);
            
                $('[data-ioc="reputation-table"]', __self).dataTable(
                {
                    "iDisplayLength": 5,
                    "bLengthChange": true,
                    "aLengthMenu": [5, 10, 20],
                    "bDeferRender": true,
                    "bSearchInputType": "search",
                    "sPaginationType": "full_numbers",
                    "bJQueryUI": true,
                    "bFilter": true,
                    "aaData": otx_info.rep_list,
                    "aoColumns": [
                        {"mDataProp": "origin",      "bSortable": true,  "sClass": "left"},
                        {"mDataProp": "value",       "bSortable": true,  "sClass": "left"},
                        {"mDataProp": "activity",    "bSortable": true,  "sClass": "left"},
                        {"mDataProp": "reliability", "bSortable": true,  "sClass": "left"},
                        {"mDataProp": "priority",    "bSortable": true,  "sClass": "left"},
                        {"mDataProp": null,          "bSortable": false, "sClass": "center", "sWidth": "30px"},
                    ],
                    oLanguage : __dt_lg,
                    "fnRowCallback" : function(nRow, aData)
                    {
                        var cell = $('td:last-child', nRow).empty();
                        var url  = __rep_url.replace('XXXX', aData.value);
                        
                        $('<a/>', 
                        {
                            "href"  : url + '<?php echo Otx::get_anchor() ?>',
                            "target": "_blank",
                            "html"  : '<img src="/ossim/pixmaps/show_details.png" height="16px"/>'
                        }).appendTo(cell);
                    }                  
                });
            }

            this.init();
            
        });
        

        return __pulse_object;
        
    }
})(jQuery);
