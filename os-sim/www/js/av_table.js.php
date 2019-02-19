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

(function($) 
{    
    $.fn.AV_table = function(opt) 
    {
        var __self = this;
        
        opt = $.extend(
        {
	        "selectable"      : false,
	        "num_rows"        : 10,
	        "language"        : "default",
	        "pagination"      : "default",
	        "search"          : false,
	        "length_change"   : true,
	        "dt_params"       : {},
            "load_params"     : [],
            "with_tray"       : false,
            "on_before_ajax"  : function(){},
            "on_complete_ajax": function(){},
            "on_success_ajax" : function(){},
            "on_error_ajax"   : function(){},
            "on_draw_row"     : function(){},
            "on_finish_draw"  : function(){},
            "on_row_click"    : function(){},
            "on_row_dbl_click": function(){},
            "on_open_tray"    : function(){},
            "on_close_tray"   : function(){},
            "on_action_status_change": function(){}
        }, opt || {});
       	
		
		
		this.init = function()
		{
    		this.addClass('av_table_wrapper');
            this.selection_type = 'manual';
            
            var t_name = $(this).data('name');
            this.db    = new av_session_db(t_name + '_db_datatables');
            this.db.clean_checked();
            
    		
    		this.dt_actions = $('<div></div>',
            {
    	       "class"    : "av_table_actions",
    	       "data_bind": "table_actions"
            }).prependTo(this);
            
            this.dt_msg_sel = $('<div></div>',
            {
    	       "class": "av_table_msg_selection",
    	       "data_bind": "msg-selection"
            }).prependTo(this).hide();
            
            
            this.sel_msg = translate_language(dt_selection_msg, opt.language);
            
            var dt_cfg =
            {
                "iDisplayLength": opt.num_rows,
                "bLengthChange": opt.length_change,
                "sPaginationType": "full_numbers",
                "bFilter": opt.search,
                "aLengthMenu": [10, 20, 50, 100, 250, 500],
                "bJQueryUI": true,
                "aaSorting": [[0, "desc"]],
                "oLanguage": get_dt_languages(opt.language, opt.pagination),
                "fnRowCallback": function(nRow, aData, iDrawIndex, iDataIndex)
                {
    				if (__self.is_selectable())
    				{
        				var input_val = aData['DT_RowId'];
        				
        				var input_disabled = (typeof aData['DT_RowData'] != 'undefined' && typeof aData['DT_RowData'].editable != 'undefined' && aData['DT_RowData'].editable == false) ? true : false;
        				
        				var input = $('<input>',
                        {
                            'type'  : 'checkbox',
                            'value'  : input_val,
                            'class'  : 'item_check ' + __self.check_class,
                            'data-id': input_val,
                            'change' : function()
                            {
                                __self.manage_check(this)
                            },
                            'click'  : function(e)
                            {
                                //To avoid to open the tray bar when clicking on the checkbox.
                                e.stopPropagation();
                            },
                            'disabled' : input_disabled 
                        }).appendTo($("td:nth-child(1)", nRow))
            
                        if (__self.db.is_checked(input_val) || __self.selection_type == 'all')
                        {
                            input.prop('checked', true)
                        }
    				}
    				
    				__self.bind_row_click(nRow);
    
    				opt.on_draw_row(__self, nRow, aData, iDrawIndex, iDataIndex);
                },
                "fnInitComplete": function(oSettings, json) 
                {			
                    opt.on_finish_draw(__self, oSettings, json);
	    
    	            if (__self.is_selectable())
    				{
    		            $("[data-bind='chk-all-rows']", __self).on('change', function()    
    		            {            
    		                $('.item_check:enabled').prop('checked', $(this).prop('checked')).trigger('change');
    		            });
    		            
    		            $("[data-bind='msg-selection']", __self).on('click', function()    
    		            {            
    		                __self.check_all_items();
    		            });
    		            
    		            __self.manage_item_selection();
    	            }
                },            
                "fnServerData": function (sSource, aoData, fnCallback, oSettings)
                {
                    $.each(opt.load_params, function(i, v)
                    {
    	                aoData.push(v);
                    });
                                    
                    oSettings.jqXHR = $.ajax(
                    {
                        "dataType": 'json',
                        "type": "POST",
                        "url": sSource,
                        "data": aoData,
                        "beforeSend": function(jqXHR, settings)
                        {
                            __self.show_search_loading();
                            
    						opt.on_before_ajax(__self, jqXHR, settings);
                        },
                        "success": function (json)
                        {
                            //DataTables Stuffs
                            $(oSettings.oInstance).trigger('xhr', oSettings);
                            fnCallback(json);
                            
    						opt.on_success_ajax(__self, json);
                        },
                        "error": function(jqXHR, textStatus, errorThrown)
                        {
                            //Check expired session
                            var session = new Session(jqXHR, '');
        
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                return;
                            }
                            //DataTables Stuffs
                            var json =
                            {
                                "sEcho": aoData[0].value,
                                "iTotalRecords": 0,
                                "iTotalDisplayRecords": 0,
                                "aaData": ""
                            }
                            
                            fnCallback(json);
                            
    						opt.on_error_ajax(__self, jqXHR, textStatus, errorThrown);
                        },
                        "complete": function(jqXHR, textStatus)
                        {				
                            __self.hide_search_loading();
                            		
    						if (__self.is_selectable())
                            {
                                __self.manage_item_selection();
    						}
    						
    						opt.on_complete_ajax(__self, jqXHR, textStatus);	
                        }
                    });
                },
                "fnPreDrawCallback": function ()
                {
                    if (typeof $.fn.select2 == 'function')
                    {
                        $('.dataTables_length select', __self).select2(
                        {
                            hideSearchBox: true
                        });
                    }
                },
            }
            
            dt_cfg = $.extend(dt_cfg, opt.dt_params);
            
            
            if (opt.ajax_url)
            {
    	        dt_cfg["bProcessing"]  = true;
                dt_cfg["bServerSide"]  = true;
                dt_cfg["bDeferRender"] = true;
                dt_cfg["sAjaxSource"]  = opt.ajax_url;
            }
            
            this.dt = $('.table_data', this).dataTable(dt_cfg);
		
		}
				
		this.is_selectable = function()
		{
    		return (opt.selectable === true);
		}
				
		this.reload_ajax = function()
		{
			try
			{
				this.dt._fnAjaxUpdate();
			}
			catch (Err){}
		}
		
		
		this.reload_table = function()
		{
			try
			{
				this.dt.fnDraw();
			}
			catch (Err){}
		}	
		
		
		this.show_search_loading = function()
        {
            $('.table_data tbody', __self).prepend('<div class="dt_list_loading"><div/>');
            $('.dataTables_processing', __self).css('visibility', 'visible');
            $('.table_data input', __self).prop('disabled', true);
            $('.dataTables_length select', __self).prop('disabled', true);
            $('.dt_footer', __self).hide();
        }
        
        
        this.hide_search_loading = function()
        {
            $('.table_data .dt_list_loading', __self).remove();
            $('.dataTables_processing', __self).css('visibility', 'hidden');  
            $('.table_data input', __self).prop('disabled', false);
            $('.dataTables_length select', __self).prop('disabled', false);
            $('.dt_footer', __self).show();
        }
		
		
		this.bind_row_click = function(row)
        {
            var click_delay = 300
            var n_clicks    = 0
            var click_timer = null;
            
            var __self      = this;
            
            $(row).on('click', function()
            {
                $(this).disableTextSelect();
    
                n_clicks++;  //count clicks
    
                if(n_clicks === 1) //Single click event
                {
                    click_timer = setTimeout(function()
                    {
                        $(this).enableTextSelect();
    
                        n_clicks = 0; //reset counter
    
                        //Executing the single click callback
                        if (opt.with_tray)
        				{
            				__self.open_tray(row)
                        }
                        
                        opt.on_row_click(__self, $(row));
    
                    }, click_delay);
                }
                else //Double click event
                {
                    clearTimeout(click_timer);  //prevent single-click action
                    n_clicks = 0;               //reset counter
    
                    //Executing the double click callback
                    opt.on_row_dbl_click(__self, $(row));
                }
    
            }).off('dblclick').on('dblclick', function(e)
            {
                e.preventDefault();
            });
        }
		
				
        this.open_tray = function(row)
        {
            var __self = this;
            
            if (__self.dt.fnIsOpen(row))
            {
                $(row).next('tr').find('#tray_container').slideUp(300, function()
                {
                    opt.on_close_tray(__self, $(this));
                    __self.dt.fnClose(row);
                });
            }
            else
            {
                var wrapper = $('<div></div>',
                {
                    'id'   : 'tray_container',    
                    'class': 'list_tray'
                }).css('visibility', 'hidden');
            
                $('<div></div>',
                {
                    'class': 'tray_triangle clear_layer'
                }).appendTo(wrapper);
                
                

                opt.on_open_tray(__self, $(row), wrapper);
                
                        
                __self.dt.fnOpen(row, wrapper, 'tray_details');
                
                wrapper.slideDown(300, function()
                {
                    $(this).css('visibility', 'visible');
                });
            }
        }
		
        
    
	    /**************************************************************************/
	    /*************************  SELECTION FUNCTIONS  **************************/
	    /**************************************************************************/
	    
	    this.manage_check = function(input)
	    {
	        var __self = this;
	        
	        if($(input).prop('checked'))
	        {
	            __self.db.save_check($(input).val());
	        }
	        else
	        {
	            __self.db.remove_check($(input).val());
	        }
	        
	        if (__self.selection_type == 'all')
	        {
	            __self.db.clean_checked();
	            $('.item_check:checked', __self).each(function()
	            {
	                __self.db.save_check($(this).val());
	            });
	        }
	        
	        __self.selection_type = 'manual';
	        __self.manage_item_selection();
	    }
	    
	        
	    this.manage_item_selection = function()
	    {
    	    var __self  = this;
    	    
	        var c_all   = $('.item_check:enabled', __self).length;
	        var c_check = $('.item_check:checked', __self).length;
	        var f_all   = this.get_total_items();
	                
	        $('[data-bind="chk-all-rows"]', __self).prop('checked', (c_all > 0 && c_all == c_check));
	        
	        if (this.selection_type == 'manual' && c_all > 0 && c_all == c_check && f_all > c_all)
	        {   
	            var msg_select = __self.sel_msg['select'].replace('___SELECTED___', c_all);
	            var msg_all    = __self.sel_msg['all'].replace('___ALL___', $.number(f_all));
	            
	            __self.dt_msg_sel.empty();
	            
	            $('<span></span>',
	            {
	                'text': msg_select + ' '
	            }).appendTo(__self.dt_msg_sel);
	            
	            $('<a></a>',
	            {
	                'class': 'av_link',
	                'text' : msg_all,
	                'click': function()
	                {
    	                __self.check_all_items()
    	            }
	            }).appendTo(__self.dt_msg_sel);
	            
	            
	            __self.dt_msg_sel.show();
	        }
	        else
	        {
	            __self.dt_msg_sel.hide();
	        }
	                             
	        this.update_action_status();
	    }
	    
	       
	    this.check_all_items = function()
	    {
	        __self.dt_msg_sel.hide();
	        this.selection_type = 'all';
	    }
	    
	    
	    this.get_total_items = function()
	    {
	        try
	        {
	            return this.dt.fnSettings()._iRecordsTotal;
	        }
	        catch(Err)
	        {
	            return 0;
	        }
	    }
	    
	    
	    this.get_selected_items = function()
        {
            var __self = this;
            
            if (__self.selection_type == 'all')
            {
                return __self.get_total_items();
            }
            else
            {
                return $('.item_check:checked', __self).length;
            }
        }
	    
	    
	    this.update_action_status = function()
	    {
    	    var __self = this;
    	    var num_hosts = this.get_selected_items();
    	    
    	    if (num_hosts == 0)
            {
                $('[data-selection="avt_action"]', __self).prop('disabled', true).addClass('disabled av_b_disabled');
            }
            else
            {
                $('[data-selection="avt_action"]', __self).prop('disabled', false).removeClass('disabled av_b_disabled');
            }
            
            opt.on_action_status_change(num_hosts);
    	    
	    }
	    
	    
	    this.reset_selection = function()
		{
    		this.db.clean_checked();
    		this.selection_type = 'manual';
    		
		}
		
	    
	    this.get_selection = function()
	    {
    	    var __self       = this;
		    var select_all   = 1;
	        var items        = {};
	        var input_search = '';
	        
	        if (this.selection_type == 'manual')
	        {
	            select_all = 0;
	            
	            $('.item_check:checked', __self).each(function(id, elem)
	            {
		            var id  = $(elem).data('id');
		            var val = $(elem).val();
		            
	                items[id] = val;
	            });
	        }
	        else
	        {
    	        input_search = $('.input_search', __self).val();
	        }
	        
	        var selection = 
	        {
		        "all"   : select_all,
		    	"items" : items,
		    	"search": input_search
		    }
		    
	        return selection
		}
		
		this.init();
		
	    return this;
    }
    
    /*******************************************************************************************/
    /*************************           PRIVATE FUNCTIONS            **************************/
    /*******************************************************************************************/
        
    function get_dt_languages(l_id, p_id)
    {
        var lang = (dt_lng[l_id]) ? dt_lng[l_id] : dt_lng["default"];
        var pag  = (dt_pag[p_id]) ? dt_pag[p_id] : dt_pag["default"];
        
        lang["oPaginate"] = pag;
        
        return lang;
    }
    
    
    function translate_language(lang, key)
    {
        return (lang[key]) ? lang[key] : lang["default"];
    }
    
    
    
    
    /*******************************************************************************************/
    /**************************           CONFIG VARIABLES            **************************/
    /*******************************************************************************************/
    
    var dt_lng =
    {
        "default":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Entries') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No entries found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 entries') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>",
            "sUrl": ""
        },
        "assets":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Assets') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching assets found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No assets found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ assets') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 assets') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total assets') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>",
            "sUrl": ""
        },
        "networks":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Networks') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching networks found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No networks found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ networks') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 networks') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total networks') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>:",
            "sUrl": ""
        },
        "groups":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Groups') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching groups found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No groups found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ groups') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 groups') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total groups') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search by name')?>",
            "sUrl": ""
        },
        "events":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Events') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching events found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No events found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ events') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 events') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total events') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>:",
            "sUrl": ""
        },
        "alarms":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Alarms') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching alarms found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No alarms found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ alarms') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 alarms') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total alarms') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search by name')?>",
            "sUrl": ""
        },
        "vulnerabilities":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Vulnerabilities') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching vulnerabilities found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No vulnerabilities found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ vulnerabilities') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 vulnerabilities') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total vulnerabilities') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>:",
            "sUrl": ""
        },
        "netflows":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Netflows') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching netflows found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No netflows found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ netflows') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 netflows') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total netflows') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search')?>:",
            "sUrl": ""
        },
        "services":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Services') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching services found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No services found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ services') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 services') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total services') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search by service')?>",
            "sUrl": ""
        },
        "properties":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Properties') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching properties found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No properties found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ properties') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 properties') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total properties') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search by property')?>",
            "sUrl": ""
        },
        "plugins":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Plugins') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching plugins found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No custom plugins found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ plugins') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 plugins') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total plugins') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search') ?>:",
            "sUrl": ""
        },
        "ports":
        {
            "sProcessing": "&nbsp;<?php echo _('Loading') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;_MENU_ <?php echo _('Ports') ?>",
            "sZeroRecords": "&nbsp;<?php echo _('No matching ports found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No ports found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ ports') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 ports') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total ports') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search') ?>:",
            "sUrl": ""
        }
        
    }

    var dt_pag =
    {
        "default":
        {
            "sFirst":    "",
            "sPrevious": "&lt; <?php echo _('Previous') ?>",
            "sNext":     "<?php echo _('Next') ?> &gt;",
            "sLast":     ""
        }
    }
    
    
    var dt_selection_msg =
    {
        "defalt":
        {
            "select": "<?php echo _('You have selected ___SELECTED___ items on this page.') ?>",
            "all"   : "<?php echo _('Select all ___ALL___ items.') ?>"
        },
        "assets":
        {
            "select": "<?php echo _('You have selected ___SELECTED___ assets on this page.') ?>",
            "all"   : "<?php echo _('Select all ___ALL___ assets.') ?>"
        },
        "networks":
        {
            "select": "<?php echo _('You have selected ___SELECTED___ networks on this page.') ?>",
            "all"   : "<?php echo _('Select all ___ALL___ networks.') ?>"
        },
        "groups":
        {
            "select": "<?php echo _('You have selected ___SELECTED___ groups on this page.') ?>",
            "all"   : "<?php echo _('Select all ___ALL___ groups.') ?>"
        }
    }
          
})(jQuery);
