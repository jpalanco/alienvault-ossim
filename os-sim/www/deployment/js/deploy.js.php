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

function GB_onclose() 
{		
	document.location.reload();					
}

function GB_onhide() 
{		
	var iframe = document.getElementById('siframe');
	iframe.src = iframe.src;			
}

function show_notification(msg, type, id, hide)
{

	var cancel = false;
	if(typeof(hide) != 'undefined'){
		cancel = true;
	}
	var config_nt = { content: msg, 
					  options: {
						type: type,
						cancel_button: cancel
					  },
					  style: 'display:none; text-align:center;margin: 10px auto;'
					};
	
	nt = new Notification('nt_js',config_nt);
	
	$(id).html(nt.show());
	nt.fade_in(1000);
	
	if(!cancel){
		setTimeout("nt.fade_out(1000);",2000);
	}
}

function goto_help(id) {
	
	var link    = '';
	var caption = '';
	
	switch(id){
	
		case 'service_passive':
		case 'service_ids':
			
			caption = '<?php echo _("AlienVault Center")?>';
			link    = '<?php echo Menu::get_menu_url("../av_center/index.php", "configuration", "deployment", "components")?>';
			
			break;

		case 'service_vulns':
			
			caption = '<?php echo _("Vulnerabilities")?>';
			
			_net_id = $('#net_id_selected').val();
			_opts   = '&action=create_scan&hosts_alive=1&scan_locally=1&net_id='+_net_id;			
			link    = '<?php echo Menu::get_menu_url("../vulnmeter/sched.php", "environment", "vulnerabilities", "scan_jobs")?>'+_opts;
			
			break;	
			
		case 'service_active':
			
			caption = '<?php echo _("Schedule Scan - Asset Discovery")?>';
			link = '<?php echo Menu::get_menu_url("../av_schedule_scan/views/list.php?s_type=nmap", "environment", "assets", "scheduler", "asset_discovery")?>';
			
			break;
				
		case 'service_netflow':
				
			caption = '<?php echo _("Sensors")?>';
			
			link = '<?php echo Menu::get_menu_url("../sensor/sensor.php", "configuration", "deployment", "components", "sensors")?>';
			
			break;
				
	}
	
	if(link != '')
	{
		$('#'+id).on('click', function() 
		{		
            var height  = '70%';
            var width   = '65%';    
            
            GB_show(caption, link, height, width);
		});	
	}
	
	return false;
}

function check_service(id, val, time)
{
	var service_class = '';
	var service_img   = '';
	if(val == 'ok')
	{
		service_class = 'r_success';
		service_img   = '/ossim/pixmaps/status/tick.png';
	} 
	else if(val == 'warning')
	{
		service_class = 'r_warning';
		service_img   = '/ossim/pixmaps/status/warning.png';
	}
	else if(val == 'error')
	{
		service_class = 'r_error';
		service_img   = '/ossim/pixmaps/status/cross.png';
		
		goto_help(id);
	}
	else if(val == 'info')
	{
		service_class = 'r_info';
		service_img   = '/ossim/pixmaps/status/unlink.png';
	}
	else
	{
		service_class = 'r_error';
		service_img   = '/ossim/pixmaps/status/cross.png';
	}
	
	setTimeout(function() {
			$('#'+id).find('.item_result').addClass(service_class);
			$('#'+id).find('img').attr('src', service_img);
		},
		time
	);

	return false;
}


function check_gauge_service(id_gauge, id_txt, data)
{
	count = data[1] + '/' + data[2];
	val   = data[0];
	
	$(id_gauge+'_count').text(count);
	update_circle(id_gauge, val);
	
	if(val < 100 && val != -1)
	{
		goto_help(id_txt);
		$('#'+id_txt).addClass('l_error');
		
	}
}

function colorize_circle(val){

	var color = new Array();
	
	
	if(val >= 80)
	{
		color[0] = "#D6E5C0";
		color[1] = "#8CC63F";
	}
	else if(val >= 60)
	{
		color[0] = "#e0ecff";
		color[1] = "#87CEEB";
	}
	else if(val >= 40)
	{
		color[0] = "#F2E8DE";
		color[1] = "#ec7000";
	}
	else if(val >= 20)
	{
		color[0] = "#ffe3e3";
		color[1] = "#cc0000";
		
	}
	else if(val >= 0)
	{
		color[0] = "#F2E6EC";
		color[1] = "#854f61";
	}
	else
	{
		color[0] = "#E5E3E3";
		color[1] = "#E5E3E3";
	}
	return color;
	
}

function load_circle(id, val)
{
	var cval = val;
	    val  = (val < 0) ? 0 : val;
	
	$(id).data('percent', val);
	$(id+'_label').text(val+' %');
	var color = colorize_circle(cval);
	
	$(id+'_label').css('color', color[1]);
	
	$(id).easyPieChart({
		barColor: color[1],
		trackColor: color[0],
		scaleColor: false,
		lineCap: 'butt',
		lineWidth: 8,
		animate: 1500,
		size:85
	});	
}

function update_circle(id, val)
{
	var cval = val;
	    val  = (val < 0) ? 0 : val;
	
	var color = colorize_circle(cval);
	
	$(id).data('easyPieChart').options.barColor   = color[1];
	$(id).data('easyPieChart').options.trackColor = color[0];
	
	$(id).data('easyPieChart').update(val);
	

	$(id+'_label').text(val + ' %');	
	$(id+'_label').css('color', color[1]);
}


function load_slider_panel(type)
{
	$('#slidep').show("slide", { direction: "right" }, 500, function(){
	
		var id     = $('#net_id_selected').val();
		var iframe = "<iframe id='siframe' name='siframe' onload='$(\"#if_loading\").hide();' src='data/assets_configured.php?type="+type+"&id="+id+"' height='100%' width='100%' frameborder='0' scrolling='no' marginwidth='0' marginheight='0'></iframe>";
		
		$('#slidep').append(iframe);
		$('#siframe').css('overflow', 'hidden');
	});
}

function hide_slider_panel(type)
{
	$('#siframe').remove();
	$('#if_loading').show();
	
	if(typeof(type) != 'undefined' && type==true)
	{
		$('#slidep').hide();
	}
	else
	{
		$('#slidep').hide("slide", { direction: "right" }, 400);

		update_assets_values();
	}
}


function load_fade_panel()
{
	$('#fade_loading .text').html("<?php echo _("Loading asset details") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>");

	$('#device_assets_list').show("slide", { direction: "right" }, 750, function(){
		
		var id     = $('#net_id_selected').val();
		var iframe = "<iframe id='asset_iframe' name='asset_iframe' onload=\"$('#fade_loading').hide();\" src='data/assets_unconfigured.php?id="+id+"'></iframe>";
		
		$('#device_assets_list').append(iframe);
		$('#cat_container').hide();
	});
}

function hide_fade_panel()
{
	$('#asset_iframe').remove();

	$('#fade_loading .text').html("<?php echo _("Reloading Assets Visibility") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>");
	$('#fade_loading').show();
	
	update_assets_values();

	setTimeout(function() {

		$('#device_assets_list').hide("slide", { direction: "right" }, 750, function(){
			$('#cat_container').show();
			
			
		});
	}, 800);
}

function update_assets_values(){

	var _net_id     = $('#net_id_selected').val();
	load_net_services(_net_id);

	var ctoken = Token.get_token("deploy_ajax");
	$.ajax({
		data:  {"action": 3},
		type: "POST",
		url: "deploy_ajax.php?&token="+ctoken, 
		dataType: "json",
		async: false,
		success: function(data)
		{ 
			if(data.error)
			{
				show_notification(data.msg, 'nf_error', '#net_notif');
			} 
			else
			{
				//Updating network devices
				var _network = data.data.network;
				_net_percent = _network.percent;
				_net_count   = _network.count;
				_net_total   = _network.total;
				_net_text    = _net_count + '/' + _net_total + " <?php echo _('Configured') ?>";

				$('#counter_net_devices').text(_net_text);
				update_circle('#c_net', _net_percent);

				//Updating server devices
				var _server = data.data.server;
				_server_percent = _server.percent;
				_server_count   = _server.count;
				_server_total   = _server.total;
				_server_text    = _server_count + '/' + _server_total + " <?php echo _('Configured') ?>";

				$('#counter_server_devices').text(_server_text);					
				update_circle('#c_server', _server_percent);
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) 
		{
			show_notification(textStatus, 'nf_error', '#net_notif');
		}
	});

}

function load_location_list() 
{

	$('.locations').dataTable( {
		"sScrollY": "420",
		"iDisplayLength": 10,
		"bLengthChange": false,
		"bJQueryUI": true,
		"aaSorting": [[ 1, "asc" ]],
		"aoColumns": [
			{ "bSortable": false }
		],
		oLanguage : {
			"sProcessing": "<?php echo _('Processing') ?>...",
			"sLengthMenu": "",
			"sZeroRecords": "",
			"sEmptyTable": "<?php echo _('No locations available') ?>",
			"sLoadingRecords": "<?php echo _('Loading') ?>...",
			"sInfo": "",
			"sInfoEmpty": "",
			"sInfoFiltered": "",
			"sInfoPostFix": "",
			"sInfoThousands": ",",
			"sSearch": "",
			"sUrl": ""
		},
		"fnInitComplete": function() {
		
			var link = "<a class='g_loc box_help' id='add_location' href='/ossim/sensor/newlocationsform.php' title='<?php _('New Location') ?>'><?php echo _('Add Location') ?></a>";
			$('#location_list').find('.dt_footer').prepend(link);
	
		}
	});
}

function load_net_list(id) 
{
	$('#net_list').hide();	
		
	if(table_exist){
		table_exist.fnDestroy();
	}

	table_exist = $('.networks').dataTable( {
		"bProcessing": true,
		"bServerSide": true,
		"sAjaxSource": "net_deferred.php?location="+id,
		"sScrollY": "420",
		"iDisplayLength": 10,
		"bLengthChange": false,
		"bJQueryUI": true,
		"aaSorting": [[ 1, "asc" ]],
		"aoColumns": [
			{ "bSortable": false }
		],
		oLanguage : {
			"sProcessing": "<?php echo _('Processing') ?>...",
			"sLengthMenu": "",
			"sZeroRecords": "",
			"sEmptyTable": "<?php echo _('No networks available') ?>",
			"sLoadingRecords": "<?php echo _('Loading') ?>...",
			"sInfo": "",
			"sInfoEmpty": "",
			"sInfoFiltered": "",
			"sInfoPostFix": "",
			"sInfoThousands": ",",
			"sSearch": "",
			"sUrl": "",
			"oPaginate": {
				"sFirst":    "<?php echo _('First') ?>",
				"sPrevious": "<?php echo _('Previous') ?>",
				"sNext":     "<?php echo _('Next') ?>",
				"sLast":     "<?php echo _('Last') ?>"
			}
		},
		"fnInitComplete": function() {
		
			if ( $('#net_list .dt_footer .input_search').length < 1 ){
				$('#net_list').find('.dataTables_filter').clone(true).prependTo($('#net_list').find('.dt_footer'));	
			}
			
			$('#net_list').show();				
			
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
					
					$('.cidr_help').tipTip();
					
				},
				"error": function(){
					//Empty table if error
					var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
					fnCallback( json );
				}
			});
		}
		
	});
}


					
function load_net_services(net_id)
{
	var ctoken = Token.get_token("deploy_ajax");
	$.ajax({
		data:  {"action": 1, "data": {"id": net_id, "location":current_location}},
		type: "POST",
		url: "deploy_ajax.php?&token="+ctoken, 
		dataType: "json",
		async:false,
		beforeSend: function(){
		
			$('#net_data').hide();
			$('.item_result').removeClass('r_success').removeClass('r_error').removeClass('r_warning').removeClass('r_info');
			$('.item_result').find('img').attr('src', '/ossim/pixmaps/status/quiz.png');
			$('#net_id_selected').val('');
		},
		success: function(data){ 
		
				if(data.error)
				{
					show_notification(data.msg, 'nf_error', '#net_notif');
				} 
				else
				{
				
					$('#net_id_selected').val(net_id);
					$('#net_name').html(data.data.net_name);
					$('#net_owner').text(data.data.net_owner);
					$('#net_descr').text(data.data.net_descr);
					
					check_service('service_ids', data.data.ids, 150);
					check_service('service_vulns', data.data.vulns, 300);
					check_service('service_passive', data.data.passive, 450);
					check_service('service_active', data.data.active, 600);
					check_service('service_netflow', data.data.netflow, 750);

				
					check_gauge_service('#n_devices', 'service_net', data.data.net_devices);
					check_gauge_service('#n_servers', 'service_server', data.data.servers);
					
					
					$('.cidr_help').tipTip();
					
					$('#net_data').show();
				}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) 
			{
				show_notification(textStatus, 'nf_error', '#net_notif');
			}
	});
}
