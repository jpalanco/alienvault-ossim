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

function otx_summary_dashboard(p)
{
    var perms = $.extend(
    {
        "admin" : true,
        "alarms": true,
        "events": true,
        "pro"   : true
    }, p || {});
    
    //Pulse Resume Vars
    this.p_pulses       = '-';
    this.p_indicators   = '-';
    this.p_last_updated = '-';
    this.p_alarms       = '-';
    this.p_events       = '-';
    
    //Top Pulse Vars
    this.top_pulses     = {};
    
    //Tren Pulse Vars
    this.trend_pulses   = {}; 
    
    //Reputation Vars
    this.r_points       = [];
    this.r_top          = [];
    this.r_chart        = [];
    this.r_activities   = {};
    this.r_type         = 1;
    this.r_act          = 'All';
    this.r_total        = 0;
    this.r_last_updated = '';
    
    //Url Vars
    var url_provider    = "<?php echo AV_MAIN_PATH ?>/otx/providers/";
    var otx_url         = "<?php echo Reputation::getlabslink('XXXX') ?>";
    
    
    
    //This copy
    var self = this;
    
    
    /*********      Class Methods      *********/
    
    this.init = function()
    {
        $.ajaxSetup(
        {
            error: function(XMLHttpRequest, textStatus, errorThrown) 
            {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
                
                var msg  = XMLHttpRequest.responseText;
                
                if (msg && !msg.match('OTX_NOT_ACTIVE'))
                {
                    show_notification('otx_notif', msg, "nf_error", 15000, true);
                }
            }
        });
        
        if (!perms.pro)
        {
            $('[data-bind="ipr-data"]').addClass('p_chart');
            $('[data-bind="ipr-options"]').addClass('p_chart');
        }
                
        self.bind_handlers();
        //Loading Pulses Summary
        self.load_pulse_summary();
        //Loading Top Pulses Section
        self.load_top_pulses();
        //Loading Trend Pulses Section
        self.load_trend_pulses();
        //Loading Reputation Section
        setTimeout(self.load_reputation_summary, 350);
    }
    
        
    /**********          Top Pulses Load          **********/
    
    this.load_pulse_summary = function()
    {
        var $loading = create_loading("<?php echo Util::js_entities(_('Loading Pulse Summary Data')) ?>");
            $loading.appendTo($('[data-bind="p-summary"]'));

        return $.ajax(
        {
            data    : {"action": "pulse"},
            type    : "POST",
            url     : url_provider + "reputation_dashboard.php",
            dataType: "json"
        }).done(function(data)
        {
            self.p_pulses       = data.pulses;
            self.p_indicators   = data.iocs;
            self.p_last_updated = data.last_updated;
            self.p_alarms       = data.alarms;
            self.p_events       = data.events;
            
            self.draw_pulse_summary_data();

        }).fail(function(xhr)
        {
            var msg = xhr.responseText;
            
            if (msg.match('OTX_NOT_ACTIVE'))
            {
                var $sum = $('[data-bind="p-summary"]').empty();
                
                var $d = $('<div/>',
                {
                    "text": "<?php echo Util::js_entities(_('Connect your OTX account to get more insight into emerging threats in your environment.')) ?>",
                    "class": "p_sec_msg",
                }).appendTo($sum);
                
                
                if (perms.admin)
                {
                    var msg = "<?php echo Util::js_entities(_('Connect Account')) ?>"
                    $('<button/>',
                    {
                        'text'     : msg,
                        'class'    : 'p_not_connected_button',
                        'data-link': "otx-config",
                        'click'    : go_to
                    }).appendTo($d);
                }
                
                $('#pulse_summary, .p_chart').addClass('chart_disabled');
            }
             
        }).always(function()
        {
            $loading.remove();
        });
    }

    
    /**********          Top Pulses Load          **********/
    
    this.load_top_pulses = function()
    {
        var $loading = create_loading("<?php echo Util::js_entities(_('Loading Top Pulse Graph')) ?>");
            $loading.appendTo($('[data-bind="p-top-pulses"]'));

        return $.ajax(
        {
            data    : {"action": "top_pulses"},
            type    : "POST",
            url     : url_provider + "reputation_dashboard.php",
            dataType: "json"
        }).done(function(data)
        {
            self.top_pulses = data || {};
            
        }).always(function()
        {
            $loading.remove();
            self.draw_top_pulses();
        });
    }
    
    
    /**********         Trend Pulses Load         **********/
    
    this.load_trend_pulses = function()
    {
        var $loading = create_loading("<?php echo Util::js_entities(_('Loading Trend Pulse Graph')) ?>");
            $loading.appendTo($('[data-bind="p-trend-pulses"]'));

        return $.ajax(
        {
            data    : {"action": "trend_pulses"},
            type    : "POST",
            url     : url_provider + "reputation_dashboard.php",
            dataType: "json"
        }).done(function(data)
        {
            self.trend_pulses = data || {};
            
        }).always(function()
        {
            $loading.remove();
            self.draw_trend_pulses();
        });
    }
    
    
    /**********          Reputation Load          **********/
    
    this.load_reputation_summary = function()
    {
        self.r_act          = 'All';
        self.r_points       = [];
        self.r_top          = [];
        self.r_chart        = [];
        self.r_activities   = [];
        self.r_total        =  0;
        self.r_last_updated = "<?php echo _('Unknown') ?>";
        
        var $loading = create_loading("<?php echo Util::js_entities(_('Loading IP Reputation Data')) ?>");
            $loading.appendTo($('[data-bind="ipr-data"]'));
            

        return $.ajax(
        {
            data    : {"action": "reputation", "data": {"type": self.r_type}},
            type    : "POST",
            url     : url_provider + "reputation_dashboard.php",
            dataType: "json"
        }).done(function(data)
        {
            self.r_total        = data.total || 0;
            self.r_last_updated = data.last_updated || '';
            
            self.r_activities   = Object.keys(data.ips) || [];
            
            $.each(data.ips, function(act, ip_data)
            {
                $.each(ip_data, function(ip, latlng)
                {
        			if(latlng.match(/-?\d+(\.\d+)?,-?\d+(\.\d+)?/)) 
        			{
        				tmp  = latlng.split(",");
        				self.r_points.push(
        				{
            				'ip' : ip,
            				'act': act,
        				    'lat': tmp[0],
        				    'lng': tmp[1]
        				});
        			}
        		});
            });
            
            self.r_top   = data.top_countries || [];
            self.r_chart = data.ip_by_activity || [];

        }).always(function()
        {
            $loading.remove();
            
            //Drawing the activity combo box
            self.draw_rep_activities();
            //Drawing the reputation map
            self.draw_rep_map();
            //Drawing the IPs by activity chart
            self.draw_rep_chart();
            //Drawing the general statistic table
            self.draw_rep_general_statistics();
            //Drawing the top countries table
            self.draw_rep_top_countries();
            //Adjust the height of the summary
            self.adjust_rep_summary_height();
        });

    }
    
    /**********         Binding Handlers         **********/
    
    this.bind_handlers = function()
    {
        $('[data-bind="act-filter"]').off('change').on('change', function()
        {
            self.r_act = $(this).val();
            self.draw_rep_map()
        });
        
        $('[data-bind="rep-type"]').off('change').on('change', function()
        {
            self.r_type = $(this).val();
            self.load_reputation_summary()
        });
        
        $.jqplot.config.enablePlugins = true;
        $.jqplot.eventListenerHooks.push(['jqplotClick', function(ev, gridpos, datapos, neighbor, plot) 
		{
            if (neighbor) 
            {
        		activity = neighbor.data[0].replace(/ \[.*/,'');
                $('[data-bind="act-filter"]').val(activity).trigger('change');
    		}
        }]); 
    }
    
    
    /**************************************************************/
    /************         Pulse Summary Section        ************/
    /**************************************************************/
    
    this.draw_pulse_summary_data = function()
    {
        $('[data-bind="p-pulses"]').text($.number(self.p_pulses)).off('click').on('click', go_to);
        $('[data-bind="p-iocs"]').text($.number(self.p_indicators)).off('click').on('click', go_to);
        $('[data-bind="p-update-date"]').text(self.p_last_updated).off('click').on('click', go_to);
        $('[data-bind="p-alarms"]').text($.number(self.p_alarms)).off('click').on('click', go_to);
        $('[data-bind="p-events"]').text($.number(self.p_events)).off('click').on('click', go_to);
        
        if (!perms.admin)
        {
            $('[data-bind="p-pulses"]').off('click').removeClass('p_link');
            $('[data-bind="p-iocs"]').off('click').removeClass('p_link');
            $('[data-bind="p-update-date"]').off('click').removeClass('p_link');
        }
        if (!perms.alarms)
        {
            $('[data-bind="p-alarms"]').text('-').off('click').removeClass('p_link');
        }
        if (!perms.events)
        {
            $('[data-bind="p-events"]').text('-').off('click').removeClass('p_link');
        }
    }
    
    
    /**************************************************************/
    /************          Top Pulses Section          ************/
    /**************************************************************/
    
    this.draw_top_pulses = function()
    {
        if (Object.keys(self.top_pulses).length == 0)
        {
            empty_layer($('[data-bind="p-top-pulses"]'));
            return false;
        }
        
        var legend = self.top_pulses[0]['values'] || [];
        var $this  = $('#chart_top');
        
        
        /*******       Header Section       *******/
        
        $header = $('<div/>',
		{
    		'class': 'g_top_header g_cell'
		}).appendTo($this);
		
		$('<div/>',
		{
    		'class': 'g_header_legend g_left g_cell',
		}).appendTo($header);
		
		$xaxis = $('<div/>',
		{
    		'class': 'g_header_elems g_right',
		}).appendTo($header);
		
		$.each(legend, function(i, v)
		{
			$('<div/>',
    		{
        		'class': 'g_header_item g_cell',
        		'text' : v.date
    		}).appendTo($xaxis);
    		
		});
		
		/*******       Body Section       *******/
		
		$body = $('<div/>',
		{
    		'class': 'g_body'
		}).appendTo($this);
		
		
		$.each(self.top_pulses, function(_index, _pulse)
		{
			$row = $('<div/>',
			{
				'class': 'g_row g_cell'
			}).appendTo($body);
			
			$('<div/>',
    		{
        		'class': 'g_body_legend g_cell g_left',
        		'text' : _pulse.name,
        		'title': _pulse.name,
        		'click': function()
        		{
            		var fl   = forensic_link(_pulse.id, '');
            		link(fl['url'], fl['p_menu'], fl['s_menu'], fl['t_menu']);
        		}
    		}).appendTo($row).tipTip();
    		
    		var $dots = $('<div/>',
    		{
        		'class': 'g_body_elems g_right'
    		}).appendTo($row);
			
			$.each(_pulse.values, function(_pi, _pval)
			{
				$('<div/>',
				{
					'class'      : 'g_bubble g_cell',
					'text'       : _pval.value,
					'data-events': _pval.value,
					'data-date'  : _pval.date,
					'data-pulse' : _pulse.id
				}).appendTo($dots);
			});
		});
        
        var w = $this.find('.g_body_elems').width() / Math.max(1, legend.length);
		var h = $row.height() || 35;
		
		$('.g_header_item, .g_bubble', $this).width(w);
		
		$this.find('div.g_bubble').graphup({
			painter: 'bubbles',
			bubblesDiameter: h -2, // px
			bubbleMinSize: 6,
			bubbleCellSize: w,
			callBeforePaint: function() 
			{
			    this.empty();
			}
		});
		
		$this.find('div.g_bubble .bubble').tipTip(
		{
    		"content": function()
    		{
        		var $dot = $(this).parents('.g_bubble').first();
        		return $dot.data('date') + ': ' + $dot.data('events') + " <?php echo _('Events') ?>";
    		}
		}).on('click', function()
		{
    		var $dot  = $(this).parents('.g_bubble').first();
    		var pulse = $dot.data('pulse');
    		var date  = $dot.data('date');
    		
    		var fl    = forensic_link(pulse, date);
    		link(fl['url'], fl['p_menu'], fl['s_menu'], fl['t_menu']);
		});
		
    }
    
    
    /**************************************************************/
    /************         Trend Pulses Section         ************/
    /**************************************************************/
    
    this.draw_trend_pulses = function()
    {
        if (Object.keys(self.trend_pulses).length == 0)
        {
            empty_layer($('[data-bind="p-trend-pulses"]'));
            return false;
        }
        
        var labels = [];
        var data   = [];
        
        $.each(self.trend_pulses, function(i, line)
        {
           labels.push(line.date)
           data.push(line.value)
        });
        
		var lineChartData = 
		{
			labels  : labels,
			datasets: 
			[
				{
					fillColor           : "rgba(151,187,205,0.2)",
					strokeColor         : "rgba(151,187,205,1)",
					pointColor          : "rgba(151,187,205,1)",
					pointStrokeColor    : "#fff",
					pointHighlightFill  : "#fff",
					pointHighlightStroke: "rgba(151,187,205,1)",
					data                : data
				}
			]
		}
		
        var ctx    = document.getElementById("chart_trend").getContext("2d");
		var $chart = new Chart(ctx).Line(lineChartData, 
		{
			responsive: true,
			tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= $.number(value) %> <?php echo _('Events') ?>",
		});
		

		$('#chart_trend').on('click', function(e)
		{
    		var point = $chart.getPointsAtEvent(e);
    		var date  = point[0]['label'];
    		
    		var fl    = forensic_link(0, date);
    		link(fl['url'], fl['p_menu'], fl['s_menu'], fl['t_menu']);
    		
		});
    }
    
    
    
    /**************************************************************/
    /************          Reputation Section          ************/
    /**************************************************************/
    
    
    this.draw_rep_activities = function()
    {
        var $select = $('[data-bind="act-filter"]');
        
        $select.html('<option value="All"><?php echo _("All") ?></option>');
        
        $.each(self.r_activities, function(i, v)
        {
           $('<option/>',
           {
               'value'  : v,
               'text'   : v
           }).appendTo($select);
        });
        
        $("[data-bind='ipr-options']").show();
    }
    
    
    this.draw_rep_general_statistics = function()
    {
        var data =
        {
            "<?php echo Util::js_entities(_('Unique IPs in the database')) ?>": self.r_total,
            "<?php echo Util::js_entities(_('Last Updated')) ?>": self.r_last_updated
        }
        
        $table = $('[data-bind="r-summary"]').empty();
        
        $.each(data, function(i, v)
        {
            $table.append('<tr><td class="left">'+ i +'</td><td>'+ v +'</td></tr>');
        });
    }
    
    
    this.draw_rep_top_countries = function()
    {
        var title1 = "<?php echo Util::js_entities(_('Country')) ?>";
        var title2 = "<?php echo Util::js_entities(_('Unique IPs')) ?>";
        
        $table = $('[data-bind="r-top"]').html('<tr><td class="left">'+ title1 +'</td><td>'+ title2 +'</td></tr>');
        
        $.each(self.r_top, function(i, v)
        {
            $table.append('<tr><td class="left">'+ v.flag + v.name +'</td><td>'+ v.occurrences +'</td></tr>');
        });
    }
    
    
    this.draw_rep_chart = function()
    {
        var $chart = $('[data-bind="r-chart"]');
        
        $chart.empty().removeClass('r_chart_empty');
        
        if (self.r_chart.length == 0)
        {
            $chart.html("<?php echo Util::js_entities(_('No data available')) ?>").addClass('r_chart_empty');
            return false;
        }
        
        $.jqplot('r_chart', [self.r_chart], 
        {
    		grid: {
    			drawBorder: false, 
    			drawGridlines: false,
    			background: 'rgba(255,255,255,0)',
    			shadow:false
    		},
            seriesColors: ["#FAC800","#7D71BD","#2FC9E5", "#B722E8", "#FF8A00", "#F65DC9", "#1E2AD1", "#837B67", "#9455F8", "#1881FA"],
    		axesDefaults: {},
    		seriesDefaults:
    		{
                padding:10,
    			renderer:$.jqplot.PieRenderer,
    			rendererOptions: 
    			{
    				diameter: '170',
    				showDataLabels: true,
    				dataLabels: "value",
    				dataLabelFormatString: '%d'
    			}					
    		},
    		legend: 
    		{
    			show: true,
    			rendererOptions: 
    			{
    				numberCols: 2
    			},
    			location:'e'
    		}
    	});
    }
    
    
    this.draw_rep_map = function()
    {
    	av_map = new Av_map('ipr_map');
    	
    	//First cleaning the map layer.
        $('#ipr_map').empty();
    	
        Av_map.is_map_available(function(conn)
        {
            if(conn)
            {   
    			av_map.set_zoom(3);
                av_map.set_location(37.1833, -3.6141);
                av_map.set_center_zoom(false);
                av_map.set_scroll_wheel(false);
                
                av_map.draw_map();
                
                var markers = [];
    			$.each(self.r_points, function(_i, data)
    			{        	
        			
        			if (self.r_act == 'All' || self.r_act == data.act)
        			{
        				var pos    = new google.maps.LatLng(data.lat, data.lng);
        				var marker = new google.maps.Marker(
        				{
        					position: pos,
        					title: data.ip + ' [' + data.act + ']'
        				});
        				
        				google.maps.event.addListener(marker, 'click', function() 
        				{
                            try
                            {
                                var ip  = this.title.match(/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/);
                                var url = otx_url.replace('XXXX', ip)
        
                                var win = window.open(url, '_blank')
                                win.focus()
                            }
                            catch(Err){}  
                        });
                        
                        markers.push(marker);
                    }
    			});
    			
    			var mcOptions     = {gridSize: 80, maxZoom: 15};
    			var markerCluster = new MarkerClusterer(av_map.map, markers, mcOptions);
            }
            else
            {
                av_map.draw_warning();
            }
        });
    }
    
    
    this.adjust_rep_summary_height = function()
    {
        var h1 = $('[data-bind="r-summary"]').height();
        var h2 = $('[data-bind="r-chart"]').height();
        var h3 = $('[data-bind="r-top"]').height();
        
        $('[data-bind="r-chart"]').height(Math.max(h1, h2, h3));
    }
    
    
    function empty_layer($elem)
    {
        $elem.html("<div class='p_sec_msg'><?php echo _('No recent OTX activity') ?></div>");
    }
    
    
    function create_loading(msg)
    {
        return $('<div/>',
        {
           'class': 'otx_dashboard_sec_loading',
           'html' : "<div>" + msg + " <img src='/ossim/pixmaps/loading.gif'/></div>" 
        });
    }
    
    
    function go_to()
    {
        var $el = $(this);
                
        var sec = $el.data('link');
        var url, p_menu, s_menu, t_menu = '';
        
        switch (sec)
        {
            case 'otx-config':
                url    = '/otx/index.php?section=config';
                p_menu = 'configuration';
                s_menu = 'otx';
                t_menu = 'otx';
            break;
            
            case 'otx-alarms':
                url    = '/alarm/alarm_console.php?hide_closed=1&otx_activity=1';
                p_menu = 'analysis';
                s_menu = 'alarms';
                t_menu = 'alarms';
            break;
            
            case 'otx-events':
                f_link = forensic_link(0, '');
                url    = f_link['url'];
                p_menu = f_link['p_menu'];
                s_menu = f_link['s_menu'];
                t_menu = f_link['t_menu'];
            break;
            
            default: 
                url = '';
        }
        
        if (url != '')
        {
            link(url, p_menu, s_menu, t_menu);
        }
    }
    
    function link(url, p_menu, s_menu, t_menu)
    {
        try
        {
            url = top.av_menu.get_menu_url(url, p_menu, s_menu, t_menu);
            top.av_menu.load_content(url);
        }
        catch(Err)
        {
            document.location.href = url
        }
    }
    
    function forensic_link(pulse, date)
    {
        var url = '/forensics/base_qry_main.php?clear_allcriteria=1';
        
        if (pulse == 0)
        {
            url += '&otx[1]=1';
        }
        else
        {
            url += '&otx[0]=' + pulse;
        }
        
        if (date != '')
        {
            var f_date = date.split('-');
            var y = f_date[0];
            var m = f_date[1];
            var d = f_date[2];
            
            url += '&time_range=range&time_cnt=2&time[0][0]=+&time[0][1]=%3E%3D&time[0][8]=+&time[0][9]=AND&time[1][1]=%3C%3D&time[0][2]='+ m +'&time[0][3]='+ d +'&time[0][4]='+ y +'&time[0][5]=00&time[0][6]=00&time[0][7]=00&time[1][2]='+ m +'&time[1][3]='+ d +'&time[1][4]='+ y +'&time[1][5]=23&time[1][6]=59&time[1][7]=59&submit=Query+DB&num_result_rows=-1&sort_order=time_d';
        }

        return {'url'   : url, 
                'p_menu': 'analysis', 
                's_menu': 'security_events', 
                't_menu': 'security_events'}  
    }
    
    this.init();
}