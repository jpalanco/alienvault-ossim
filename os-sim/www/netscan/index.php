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

Session::logcheck('environment-menu', 'ToolsScan');

$conf       = $GLOBALS['CONF'];
$nmap_path  = $conf->get_conf('nmap_path');

$nmap_exists  = (file_exists($nmap_path)) ? 1 : 0;


$keytree = 'assets';

$scan_modes = array(
    'ping'   => _('Ping'),
    'fast'   => _('Fast Scan'),
    'normal' => _('Normal'),  
    'full'   => _('Full Scan'),
    'custom' => _('Custom')
);

$time_templates = array(
    '-T0' => _('Paranoid'),
    '-T1' => _('Sneaky'), 
    '-T2' => _('Polite'), 
    '-T3' => _('Normal'), 
    '-T4' => _('Aggressive'),
    '-T5' => _('Insane')
);


//Database connection
$db   = new ossim_db();
$conn = $db->connect();


/****************************************************
********************* Sensors ***********************
****************************************************/


$ext_ctxs = Session::get_external_ctxs($conn);

$filters = array(    
    'order_by' => 'sensor.name'
);

$sensor_list = Av_sensor::get_basic_list($conn, $filters);

/****************************************************
******************** Search Box ********************
****************************************************/
             
$autocomplete_keys = array('hosts', 'nets', 'host_groups', 'sensors');
$assets            = Autocomplete::get_autocomplete($conn, $autocomplete_keys);



/****************************************************
******************** Clear Scan ********************
****************************************************/

$clearscan = (!empty($_GET['clearscan']) && $_GET['clearscan'] == 1) ? 1 : 0;

if ($clearscan == 1)
{ 
	$scan = new Scan();
	$scan->delete_data();
}



/****************************************************
******************** Custom scan ********************
****************************************************/


$host_id      = '';
$sensor       = 'local';
$scan_mode    = 'fast';
$ttemplate    = '-T3';
$scan_ports   = '1-65535';
$autodetected = 1;
$rdns         = 1;

if ($_POST['action'] == 'custom_scan')
{
    $validate = array (
    	'host_id'         => array('validation' => 'OSS_HEX',                    'e_message' => 'illegal:' . _('Host ID')),
    	'sensor'          => array('validation' => 'OSS_LETTER',                 'e_message' => 'illegal:' . _('Sensor')),	
    	'scan_mode'       => array('validation' => 'OSS_LETTER',                 'e_message' => 'illegal:' . _('Scan Mode')),
    	'timing_template' => array('validation' => '"-",OSS_LETTER, OSS_DIGIT',  'e_message' => 'illegal:' . _('Timing Template')),
    	'autodetected'    => array('validation' => 'OSS_BINARY',                 'e_message' => 'illegal:' . _('Autodected services and OS')),
    	'rdns'            => array('validation' => 'OSS_BINARY',                 'e_message' => 'illegal:' . _('Reverse DNS '))
    	
    );

    $validation_errors = validate_form_fields('POST', $validate);
    
    //Extra validations
    
    if (empty($validation_errors))
    {	
    	if (!array_key_exists(POST('scan_mode'), $scan_modes))
    	{
        	$validation_errors['status']    = 'error';
    		$validation_errors['scan_mode'] = _('Error! Scan mode not allowed');
    	}
    	
    	if (!array_key_exists(POST('timing_template'), $time_templates))
    	{
        	$validation_errors['status']    = 'error';
    		$validation_errors['timing_template'] = _('Error! Timing template not allowed');
    	}
    
    	if (empty($validation_errors))
    	{
        	$host_id      = POST('host_id');    	
        	$_host_ips    = Asset_host_ips::get_ips_to_string($conn, $host_id);        	
            $sensor       = POST('sensor');
            $scan_mode    = POST('scan_mode');
            $ttemplate    = POST('timing_template');        
            $autodetected = POST('autodetected');
            $rdns         = POST('rdns');    	
    	}
    }
}

// From Suggestions link
if ($_GET['action'] == 'custom_scan')
{
    $validate = array (
    	'host_id'         => array('validation' => 'OSS_HEX',                    'e_message' => 'illegal:' . _('Host ID')),
    	'sensor'          => array('validation' => 'OSS_LETTER',                 'e_message' => 'illegal:' . _('Sensor')),	
    	'scan_mode'       => array('validation' => 'OSS_LETTER',                 'e_message' => 'illegal:' . _('Scan Mode'))    	
    );

    $validation_errors = validate_form_fields('GET', $validate);
    
    //Extra validations
    
    if (empty($validation_errors))
    {	
    	if (!array_key_exists(GET('scan_mode'), $scan_modes))
    	{
        	$validation_errors['status']    = 'error';
    		$validation_errors['scan_mode'] = _('Error! Scan mode not allowed');
    	}
    	
    	if (empty($validation_errors))
    	{
        	$host_id      = GET('host_id');    	
        	$_host_ips    = Asset_host_ips::get_ips_to_string($conn, $host_id);  
        	$sensor       = GET('sensor');      	
            $scan_mode    = GET('scan_mode');
    	}
    }
}

//Close DB connection
$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/av_scan.js.php"></script>
	<script type="text/javascript" src="../js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<link rel="stylesheet" type="text/css" href="../style/tree.css"/>
	<link rel="stylesheet" type="text/css" href="../style/progress.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
	<link rel="stylesheet" type="text/css" href="../style/fancybox/jquery.fancybox-1.3.4.css"/>
	
	
	<script type='text/javascript'>
		var timer = null;
		
		function show_notification (msg, container, nf_type, style){
	
			var nt_error_msg = (msg == '')   ? '<?php echo _('Sorry, operation was not completed due to an unknown error')?>' : msg; 
			var style        = (style == '' ) ? 'width: 80%; text-align:center; padding: 5px 5px 5px 22px; margin: 20px auto;' : style; 
				
			var config_nt = { content: nt_error_msg, 
					options: {
						type: nf_type,
						cancel_button: true
					},
					style: style
				};
			
			var nt_id         = 'nt_ns';
			var nt            = new Notification(nt_id, config_nt);
			var notification  = nt.show();
			
			$('#'+container).html(notification);
			parent.window.scrollTo(0,0);
		}		
		
		
		
		/****************************************************
         ****************** Scan functions ******************
         ****************************************************/
        
        function show_process_status() 
        {        			
			$.ajax({
                type: 'GET',
				url: 'get_state.php',
				dataType: 'json',
                success: function(data){        
                    $('#scan_result').html('');
                    
                    var s_array = ['remote_scan_in_progress', 'local_scan_in_progress', 'local_search_in_progress', 'launching_local_scan'];
                    
                    if (typeof(data) == 'undefined' || data == null)
				    {
				        clearTimeout(timer);
				        $.fancybox.close();
				        show_notification('<?php echo _('Error retrieving state')?>', 'c_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
				    }					
					else if (jQuery.inArray(data.state, s_array) != -1)
					{
    				    show_state_box(data.state, data.message, data.progress);
    				    timer = setTimeout('show_process_status()', 6000);
    				    
    				    if(data.state == 'launching_local_scan')
    				    {
    				        timer = setTimeout('show_process_status()', 4000);
    				    }
					}
					else if (data.state == 'finished')
					{
					    clearTimeout(timer);
					    
                        get_results();
						
						$('.sel_nmap_info').remove(); // clean old info
						
						if (typeof(data.debug_info) != 'undefined') 
						{
							$('body').append("<div class='sel_nmap_info' data-nmap='" + data.debug_info + "'></div>");
						}
												
						var offset = $("#scan_result").offset();
						parent.window.scrollTo(0, offset.top);
					}
					else
					{
						clearTimeout(timer);
						$.fancybox.close();
					}
				}
            });
        }
        
        function get_results()
        {
            $.ajax({
                type: 'GET',
	            url: 'get_results.php',
	            dataType: 'html',
                success: function(data)
                {
                    $.fancybox.close();
                    
                                       
                    if (typeof(data) == 'undefined' || data == null)
				    {
				        show_notification('<?php echo _('Error retrieving results')?>', 'c_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
				    }					
					else
					{               
                        $('#scan_result').html(data);
                        
                        if ($('#t_sresults').length == 0)
                        {
                            show_notification("<?php echo _("The scan has completed. We couldn't find any host within the selected networks")?>", 'c_info', 'nf_warning', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
                        }
					}
		        }
		   });
            
        }
        
        
        function check_target_number()
        {
            var num_host = 0;
            
            parent.window.scrollTo(0, 0);
        
            if(getcombotext("assets").length < 1)
			{
				av_alert('<?php echo Util::js_entities(_('You must choose at least one asset'))?>');
				
				return false;
			}
			else
			{
			    var ip_count = 0;
			    
			    selectall("assets");
			    
			    var targets = $('#assets').val();
			    
			    for (i = 0; i < targets.length; i++)
			    {
    			    if (targets[i].match(/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))$/))    			    
    			    {
        			    var res = targets[i].split('/');
                        ip_count += 1 << (32 - res[1]);    			    
    			    }
    			    else
    			    {
        			    ip_count++;
    			    }
			    }
			
			    if (ip_count > 256)
			    {   
                    var msg_confirm = '<?php echo Util::js_entities(_("You are about to scan a big number of hosts (#HOSTS# hosts). This scan could take a long time depending on your network and the number of assets that are up, are you sure you want to continue?"))?>';
                    
                    msg_confirm = msg_confirm.replace("#HOSTS#", ip_count);
                                             
                    var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
                                    
                    av_confirm(msg_confirm, keys).fail(function(){
                        return false; 
                    }).done(function(){
                        start_scan(); 
                    });
                }
                else
                {
                    start_scan();
                }
            }
        }
        
		
		function start_scan()
		{		
            selectall("assets");
            		
			$.ajax({
				type: "GET",
				url: 'do_scan.php',
				data: $('#assets_form').serialize(),
				dataType: 'json',
                async: false,
				beforeSend: function( xhr ) {							
					$('#c_info').html('');
					$('#scan_result').html('');
				},
				error: function(bc_data){
							
					//Check expired session
					var session = new Session(bc_data, '');
													
					if (session.check_session_expired() == true)
					{
						session.redirect();
						return;
					} 
					
					show_notification('', 'c_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
				
				},
				success: function(data){
					
					var cnd_1  = ( typeof(data) == 'undefined' || data == null );
					var cnd_2  = ( typeof(data) != 'undefined' && data != null && data.status == 'error');	
											
					if (cnd_1 || cnd_2)
					{
						var error_msg = ( cnd_1 == true ) ? '' : data.data;
						show_notification(error_msg, 'c_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
					}
					else						{									
													
						if (data.status == 'warning')
						{
							show_notification(data.data, 'c_info', 'nf_warning', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
						}
						
						show_process_status();
					}
				}
			});
		}
				
		function stop_nmap()
		{			
			$.ajax({
				type: "GET",
				url: "do_scan.php?only_stop=1",
				dataType: 'json',
				error: function(bc_data){
								
					//Check expired session
					var session = new Session(bc_data, '');
													
					if (session.check_session_expired() == true)
					{
						session.redirect();
						return;
					}
					show_notification('', 'c_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');

				},
				success: function(msg){
															
					$('#c_info').html('');
					
					get_results();
					clearTimeout(timer);
					
					$.fancybox.close();
				}
			});
		}
		
		/****************************************************
         *************** Searchbox functions ****************
         ****************************************************/  
         
        function add_asset(ips)
        {                     
            if (typeof(ips) != 'undefined')
            {
                var ips = ips.split(',');
            
                var size = ips.length;
                
                for (var i=0; i<size; i++)
                {                                    
                    if ($('#assets option[value $="'+ips[i]+'"]').length == 0)
                    {
                        addto ("assets", ips[i], ips[i]);
                    }                
                }
                
                $("#searchbox").val('');
            }         
        }
     

		$(document).ready(function(){
            /****************************************************
             *********************** Tree  **********************
             ****************************************************/ 
            
            $("#atree").dynatree({
                initAjax: { url: "../tree.php?key=<?php echo $keytree ?>" },
                clickFolderMode: 2,
                onActivate: function(dtnode) {
                    
                    if(dtnode.data.url!='' && typeof(dtnode.data.url) != 'undefined') 
                    {
                        var Regexp = /.*_(\w+)/;
                        var match  = Regexp.exec(dtnode.data.key);
                        var id     = "";

                        id = match[1];

                        // Split for multiple IP/CIDR
						var keys = dtnode.data.val.split(",");

						for (var i = 0; i < keys.length; i++) 
						{
							var item   = keys[i];
							var value  = "";
	                        var text   = "";
	                        
	                        if (item.match(/\d+\.\d+\.\d+\.\d+\/\d+/) !== null) 
	                        { 
	                            //CIDR
	                            Regexp = /(\d+\.\d+\.\d+\.\d+\/\d+)/;
	                            match  = Regexp.exec(item);
	                            
	                            value = match[1];
	                            text  = match[1];
	                        }
	                        else if (item.match(/\d+\.\d+\.\d+\.\d+/) !== null) 
	                        { 
	                            //IP
	                            Regexp = /(\d+\.\d+\.\d+\.\d+)/;
	                            match  = Regexp.exec(item);
	                            
	                            value = match[1];
	                            text  = match[1] + "/32";
	                        }
	                     
	                        if(value != '' && text != '') 
	                        {
	                            addto ("assets", text, value);
	                        }
						}
                    }
                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode){
                    dtnode.appendAjax({
                        url: "../tree.php",
                        data: {key: dtnode.data.key, page: dtnode.data.page}
                    });
                }
			});
	
	
			            
            /****************************************************
             ******************** Search Box ********************
             ****************************************************/            
            
            $("#assets_form").keypress(function(e) {
                if (e.which == 13 )
                {
					return false;
                }
			});
            
            
            $("#searchbox").click(function() {
                $("#searchbox").removeClass('greyfont');
                $("#searchbox").val('');
                });
            
            
			$("#searchbox").blur(function() {
                $("#searchbox").addClass('greyfont');
                $("#searchbox").val('<?php echo _('Type here to search assets')?>');
            });    
                    
            
			$('#searchbox').keydown(function(event) {
                                                
                if (event.which == 13) 
                {
                    targetRegex = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))?$/;
                                                           
                    if($("#searchbox").val().match(targetRegex)) 
                    {                                            
                        add_asset($("#searchbox").val());             
                    }
                }
                                
            });            
            
            $("#delete_all").click(function() {
                selectall('assets');
                deletefrom('assets');
            });  
            
            
            $("#delete").click(function() {                
                deletefrom('assets');            
    		}); 
    		
    		
    		$("#lnk_ss").click(function() {                
                
                $('#td_sensor').toggle(); 

                if($('#td_sensor').is(':visible'))
                { 
                    $('#sensors_arrow').attr('src','../pixmaps/arrow_green_down.gif');
                } 
                else
                { 
                    $('#sensors_arrow').attr('src','../pixmaps/arrow_green.gif'); 
                } 
        
                return false;           
    		});    	
    		    		            

            // Autocomplete assets
            var assets = [ <?php echo preg_replace("/,$/","",$assets); ?> ];
            
            $("#searchbox").autocomplete(assets, {
                minChars: 0,
                width: 300,
                max: 100,
                matchContains: true,
                autoFill: false,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {            
                
                  add_asset(item.ip);     
            });				
			
			
			
			/****************************************************
             ********************* Tooltips *********************
             ****************************************************/   
						
			if ($(".more_info").length >= 1)
			{
				$(".more_info").tipTip({maxWidth: "auto"});
			}
			
			
			
			
			/****************************************************
             ****************** NMAP functions  *****************
             ****************************************************/ 
						
			bind_nmap_actions();
			
			<?php
			//Adding custom assets
			
			if ($_REQUEST['action'] == 'custom_scan' && empty($validation_errors))
			{
    			?>
    			add_asset('<?php echo $_host_ips?>');
    			<?php
			}
			?>
			
			show_process_status();
			
		});
	</script>
  
	<style type='text/css'>
    	.box_title
    	{
    	    padding: 15px 0 5px 0;
    	    font-size: 17px;
    	    line-heigh: 18px;
    	}
    	.box_subtitle
    	{
    	    font-size: 13px;
    	    padding:0 0 5px 0;
    	    line-height: 17px;
    	}
    	#progressbar, #activitybar
    	{
    	    width: 400px;
    	    margin: 40px auto 35px auto;
    	}
    	.bar-label
    	{
    	    position: absolute;
    	    right:0;
    	    left:0;
    	    top:3px;
    	    font-size: 14px;
    	    text-align: center;
    	    color: rgba(0,0,0,0.6);
    	    text-shadow: rgba(255,255,255, 0.45) 0 1px 0px;
    	    white-space: nowrap;
    	}
    	#progress_legend
    	{
    	    position: absolute;
    	    right: 0;
    	    left: 0;
    	    top: 27px;
    	    font-size: 11px;
    	    color: #A8A8A8;
    	    text-align: center;
    	}
		.box_single_button
        {
            text-align: center;
            position: absolute;
            bottom:10px;
            left:0;
            right:0;
            
        }
		a:hover 
		{
			text-decoration: none !important;
		}
		
		.greyfont
        {
            color: #666666;
        }	
				
		.small 
		{ 
    		color: grey;
    		font-size: 9px;
		}
		
        #fancybox-wrap {
          top: 60px !important;
        }
		
		#c_asset_discovery
		{
			position: relative;
			margin: 20px auto 5px auto;
			width: 98%;
		}
		
		#c_info 
		{
			width: 750px;
			margin: 10px auto;
		}
				
		.error_item 
		{ 
			padding-left: 25px; 
			text-align: left;
		}		
		
		#t_ad
		{ 
			width: 650px; 
			margin:0px auto
		}
		
		th 
        {
            padding: 3px 0px;
        }
		
		.container 
		{
			margin:auto; 
			padding: 10px 10px;
		}		
			
		.loading_nmap
		{
			padding: 2px;
		}
					
		#searchbox
		{
    		width:260px;
    		margin-left:3px;
		}
		
		#tree 
		{
    		margin-top: 15px; 
    		text-align: left;
		}
		
		.atree
		{
    		text-align:left;
    		width:100%;
    		margin-left:2px;
		}		
		
		.cidr_info 
		{
			cursor:pointer; 
			text-decoration: none;
			outline: none;
		}
		
		.cidr_info div 
		{
			text-decoration: none;
			outline: none;
		}	
		
		#td_sensor
		{
    		display: none;
		}
		            
        #assets 
        {
            width:270px;
            height:180px;
        }
        
        #t_adv_options
		{
    		width: 100%;
    		background: none;
    		border: none;
		}
		
		#t_adv_options td
		{
    		text-align: left;
    		border: none; 		
		}
		
		#t_adv_options .td_label
		{
    		text-align: left;
    		white-space: nowrap;
    		width: 100px;  		
		}
		
		#t_adv_options .nmap_select
		{
    		width: 90px;		
		}
		
		#t_adv_options #custom_ports
		{
    		width: 90px;  		
		}
		    		    		    			
		#t_adv_options img 
		{
		  display: none;
		  cursor: pointer;
		}         
   		
		.more_info img
		{
			cursor: pointer;
		}        
    		
		#scan_result
		{
			width: 100%;
			margin: auto;
		}		
		
		#t_sresults img
		{		 
		   vertical-align: middle;
		}
				
		#t_sresults .td_chk_hosts, #t_sresults .th_chk_hosts
		{
		   width: 20px !important;	
		}
		
		#t_sresults .td_ip, #t_sresults .th_ip
		{
		   width: 130px !important;		
		}	
						
		#t_sresults .td_hostname, #t_sresults .td_fqdn, #t_sresults .td_device_types
		{
		   width: 120px !important;		
		}
		
		#t_sresults .th_hostname, #t_sresults .th_fqdn, #t_sresults .th_device_types
		{
		   width: 120px !important;	
		}
		
		#t_sresults .td_mac, #t_sresults .td_os, #t_sresults .th_mac, #t_sresults .th_os
		{
		   width: 120px !important;	
		}
		
		#t_sresults .td_services, #t_sresults .th_services
		{		  
		   width: 180px !important;
		   
		}
		
		#t_sresults .td_services
		{ 
		   white-space: normal !important;			
		}
		
		#t_sresults .td_chk_fqdns, #t_sresults .th_chk_fqdns
		{		  
		   width: 100px;		
		}

	</style>
  
</head>

<body>

<!-- Asset form -->

<div id='c_info'>
    <?php
    if (!$nmap_exists) 
    {   
        $error = new Av_error();
        $error->set_message('NMAP_PATH');
        $error->display();
    }
    
    
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $txt_error = '<div>'._('We Found the following errors').":</div>
					  <div style='padding: 10px;'>".implode('<br/>', $validation_errors).'</div>';				
				
		$config_nt = array(
			'content'  =>  $txt_error,
			'options'  =>  array (
				'type'           =>  'nf_error',
				'cancel_button'  =>  FALSE
			),
			'style'    =>  'width: 80%; margin: 20px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();        
    } 
    ?>
</div>

<div id='c_asset_discovery'>
	
	<form name="assets_form" id="assets_form">	
        
        <div class='scan_title'><?php echo _('New Scan') ?></div>

		<table align="center" id='t_ad'>
			
			<tbody>
    			<tr>
    				<th colspan="2"><?php echo _('Target selection') ?></th>
    			</tr>
    			
    			<tr>
    				<td>
    					<span> <?php echo _('Please, select the assets you want to scan:');?></span>
    				</td>
    			</tr>
			
    			<tr>
    				<td class='container nobborder'>
    					<table class="transparent" cellspacing="0">
    						<tr>
    							<td class="nobborder" style="vertical-align:top" class="nobborder">
    								<table class="transparent" cellspacing="0">
    									<tr>
    										<td class="nobborder">
    										    <select id="assets" name="assets[]" multiple="multiple"></select>
    										</td>
    									</tr>
    									<tr>
    										<td class="nobborder" style="text-align:right;padding-top:5px;">
    											<input type="button" name='deletel' id='delete' class="small av_b_secondary" value=" [X] "/>
    											<input type="button" name='delete_all' id='delete_all' class="small av_b_secondary" style="margin-right:0px;" value="<?php echo _('Delete all')?>"/>
    										</td>
    									</tr>
    								</table>
    							</td>
    							<td class="nobborder" width="300px;" style="vertical-align: top;padding-left:15px;">
    								<input class="greyfont" type="text" name="searchbox" id="searchbox" value="<?php echo _('Type here to search assets'); ?>" />
    								<div id="atree"></div>
    							</td>
    						</tr>
    					</table>
    				</td>
    			</tr>
    			
    			<tr>
    				<th colspan="2"> <?php echo _('Sensor selection')?></th>
    			</tr>
    			
    			<tr>
    				<td class="nobborder">
    					<table class="transparent">
    						<tr>
                                <td class="nobborder">
                                    
                                    <?php $sl_checked = ($sensor == 'automatic') ? 'checked="checked"' : '';?>
                                    
                                    <input type="radio" name="sensor" id="asensor" <?php echo $sl_checked?> value="auto"/>
                                    <label for="asensor">
                                        <span><span class="bold"><?php echo _('Automatic')?></span> <?php echo _('sensor')?></span>
                                        <span class="small"> <?php echo _('Launch scan from the first available sensor')?></span>    
                                    </label>						
                                </td>
    						</tr>
    						
    						<tr>
                                <td class="nobborder">
                                    
                                    <?php $sl_checked = ($sensor == 'local') ? 'checked="checked"' : '';?>
                                    
                                    <input type="radio" name="sensor" id="lsensor" <?php echo $sl_checked?> value="local"/>
                                    <label for="lsensor">
                                        <span><span class="bold"><?php echo _('Local')?></span> <?php echo _('sensor')?></span>                                         
                                        <span class="small"> <?php echo _('Launch scan from the framework machine')?></span>    
                                    </label>						
                                </td>
    						</tr>    
    					</table>
    				</td>
    			</tr>
    			
    			<tr>
    				<td style="text-align: left; border:none; padding:3px 0px 3px 8px">
    					<a href="javascript:void(0);" id='lnk_ss'>
    					<img id="sensors_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/>
    					<span><span class="bold"><?php echo _('Select an')?></span> <?php echo _('specific sensor')?></span>
    					</a>
    				</td>
    			</tr>   			
    			
    			
    			<tr id="td_sensor">
    				<td style="padding-left:30px;">
    					<table class="transparent">
    						<?php
    						$sensor_id = 0;
    						
    						foreach ($sensor_list as $_sensor_id => $sensor) 
    						{    						    
    						    ?>
                                <tr>
                                    <td class="nobborder">
                                        <input type="radio" name="sensor" id="sensor<?php echo $sensor_id;?>" value="<?php echo $_sensor_id ?>">
                                        <label for="sensor<?php echo $sensor_id;?>"><?php echo $sensor['name'] . " [" . $sensor['ip'] . "]"?></label>
                                    </td>
                                </tr>
    						    <?php 
    						    $sensor_id++;    						    
    						}
    						?>
    					</table>
    				</td>
    			</tr>
    			
    			<tr>
    				<th colspan="2"><?php echo _('Advanced Options')?></th>
    			</tr>
    
    			<!-- Full scan -->
    			<tr>
    				<td colspan="2" style="padding:7px 0px 0px 10px">
    				
    				    <table id='t_adv_options'>                                
                            <!-- Full scan -->
                            <tr>
                                <td class='td_label'>
                                    <label for="scan_mode"><?php echo _('Scan type')?>:</label>        
                                </td>
                                <td>                                       
                                    <select id="scan_mode" name="scan_mode" class="nmap_select vfield">
										<?php
										foreach ($scan_modes as $sm_v => $sm_txt)
										{
											$selected = ($scan_mode == $sm_v) ? 'selected="selected"' : '';
											
											echo "<option value='$sm_v' $selected>$sm_txt</option>";								
										}
										?>								
									</select>
                                </td>
                                <td style='padding-left: 20px;'>	
									<span id="scan_mode_info"></span>							
                                </td>                       
                            </tr>                           
                                
                            <!-- Specific ports -->
                            <tr id='tr_cp'>                                    
                                <td class='td_label'>
                                    <label for="custom_ports"><?php echo _('Specify Ports')?>:</label>        
                                </td>
                                <td colspan="2">
                                    <?php 
                                        $scan_ports = ($scan_ports == '') ? '1-65535' : $scan_ports;
                                    ?>
                                    <input class="greyfont vfield" type="text" id="custom_ports" name="custom_ports" value="<?php echo $scan_ports?>"/>      
                                </td>
                            </tr>
                                
                            <!-- Time template -->
                            <tr>
                                <td class='td_label'>
                                    <label for="timing_template"><?php echo _('Timing template')?>:</label>        
                                </td>
                                <td>                                      
                                    <select id="timing_template" name="timing_template" class="nmap_select vfield">			
										<?php
										foreach ($time_templates as $ttv => $tt_txt)
										{
											$selected = ($ttemplate == $ttv) ? 'selected="selected"' : '';
											
											echo "<option value='$ttv' $selected>$tt_txt</option>";								
										}
										?>													
									</select>
								</td>
                                <td style='padding-left: 20px;'>	
									<span id="timing_template_info"></span>
                                </td>                         
                            </tr>
                                                                 
                            <tr>
                                <td colspan="3">
                                                         
                                    <?php $ad_checked = ($autodetected == 1) ? 'checked="checked"' : '';?>
                                    
                                    <input type="checkbox" id="autodetect" name="autodetect" class='vfield' <?php echo $ad_checked?> value="1"/>
                                    <label for="autodetect"><?php echo _('Autodetect services and Operating System')?></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">                                                                       
                                    
                                    <?php $rdns_checked = ($rdns == 1) ? 'checked="checked"' : '';?>
                                    
                                    <input type="checkbox" id="rdns" name="rdns" class='vfield' c<?php echo $rdns_checked?> value="1"/>
                                    <label for="rdns"><?php echo _('Enable reverse DNS Resolution')?></label>                                    
                                </td>                                    
                            </tr>    
    				    </table> 				
    				</td>
    			</tr>	
    
    			<!-- do scan -->
    			<tr>
    				<td colspan="2" class="nobborder center" style='padding: 10px;'>
    					<input type="button" id="scan_button" onclick="check_target_number();" value="<?php echo _('Start Scan') ?>"/>
    				</td>
    			</tr>
			</tbody>			
		</table>
		
		<br/>		
		
		<div id='scan_result'></div>
		
		<br/>
		
	</form>
</div>

<!-- end of Asset form -->
</body>
</html>