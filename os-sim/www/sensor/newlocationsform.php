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

if (!Session::am_i_admin()) 
{
	Session::unallowed_section(NULL, 'noback');
}

/****************************************************
 **************** Configuration Data ****************
 ****************************************************/

$conf = $GLOBALS['CONF'];

if (!$conf)
{
    $conf = new Ossim_conf();
    $GLOBALS['CONF'] = $conf;
}

//Google Maps Key
$map_key = $conf->get_conf('google_maps_key');

if ($map_key == '') 
{
    $map_key = 'ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBTIx7cuHpcaq3fYV4NM0BaZl8OxDxS9pQpgJkMv0RxjVl6cDGhDNERjaQ';
}

$db    = new ossim_db();
$conn  = $db->connect();

$locations_id = (GET('id') != '') ? GET('id') : POST('locations_id');
$name         = '';
$status       = 1;
$zoom         = 1;
$sensors      = Av_sensor::get_basic_list($conn);
$r_sensors	  = array();
	
if ($locations_id != '')
{
	ossim_valid($locations_id,  OSS_HEX,    'illegal:' . _('Location ID'));

	if (ossim_error())
	{ 
		die(ossim_error());
	}
		
	if ($locations_list = Locations::get_list($conn, " AND id = UNHEX('$locations_id')"))
	{
		$location       = $locations_list[0];
		$ctx            = $location->get_ctx();
		$name           = Util::htmlentities($location->get_name());
		$desc           = Util::htmlentities($location->get_desc());
		$latitude       = str_replace(',', '.', floatval($location->get_lat()));
		$longitude      = str_replace(',', '.', floatval($location->get_lon()));
		$zoom           = 4;
		$cou            = strtolower($location->get_country());
		$location       = Util::htmlentities($location->get_location());
        
        $_related       = Locations::get_related_sensors($conn, $locations_id);
        $r_sensors      = array();
        foreach ($_related as $_s)
        {
            $r_sensors[$_s[0]] = $_s[1] . ' [' . $_s[2] . ']';
        }
	}
}

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

	
	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',       'def_path' => TRUE),
            array('src' => 'tipTip.css',                    'def_path' => TRUE),
            array('src' => 'jquery.tree.css',               'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                     'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'utils.js',                          'def_path' => TRUE),
            array('src' => 'notification.js',                   'def_path' => TRUE),
            array('src' => 'token.js',                          'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                  'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                 'def_path' => TRUE),
            array('src' => 'messages.php',                      'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',          'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js',                'def_path' => TRUE),
            array('src' => 'jquery.autocomplete_geomod.js',     'def_path' => TRUE),
            array('src' => 'geo_autocomplete.js',               'def_path' => TRUE),
            array('src' => 'av_map.js.php',                     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'js');
    ?>
	
	<style type='text/css'>
						
		input[type='text'], input[type='hidden'], select 
		{
            width: 98%; 
            height: 18px;
		}
		
		input[type='file'] 
		{
            width: 90%; 
            border: solid 1px #CCCCCC;
        }
		
		textarea 
		{
    		width: 98%; 
    		height: 45px;
		}
		
		.legend
		{
    		margin-top: 40px;
		}
		
		.text_wi
		{
			cursor: default !important;
			font-style: italic !important;
			opacity: 0.5 !important;
		}			
				
		div.bold 
		{
		    line-height: 18px;
		}
				
		#table_form 
		{
		    width: 500px;
		    margin: 5px auto;
		}
		
		#table_form th 
		{
		    width: 150px;
		}
		
		#av_info 
		{
            width: 580px; 
            margin: 10px auto;
        }
		
	</style>
	
	<script type="text/javascript">
    	
    	var __is_in_lightbox   = false;
    	var __location_sensors = <?php echo json_encode($r_sensors, JSON_FORCE_OBJECT) ?>;
    	

    	function add_sensor()
    	{
        	var s_id   = $('#sensor_list').val();
        	var s_name = $("#sensor_list option:selected").text();
        	
        	if (typeof s_id == 'string' && s_id != '0')
        	{
            	__location_sensors[s_id] = s_name;
                draw_selected_sensors();
        	}
    	}
    	
    	function delete_sensor(s_id)
    	{
        	delete __location_sensors[s_id];
        	draw_selected_sensors();
    	}
    	
    	function draw_selected_sensors()
    	{
        	var $s_list = $('#sensor_selected_list').empty();
        	
        	$.each(__location_sensors, function(id, name)
        	{ 
            	var $tr  = $('<tr/>').appendTo($s_list);
            	
            	var $td1 = $('<td/>',
            	{
                	'text': name 
            	}).appendTo($tr);
            	
            	var $td2 = $('<td/>').appendTo($tr);
            	$('<button/>',
            	{
                	'class':"small av_b_secondary",
                	'html' : "<?php echo _('Delete') ?>",
                	'click': function()
                	{
                    	delete_sensor(id);
                	}
            	}).appendTo($td2);
        	});
    	}
    	
    	function save_location()
    	{
        	var f_params = $('#form_wi').serializeArray();
        	var params   = {};
        	
        	$.each(f_params, function(i, p)
        	{
            	params[p.name] = p.value;
        	});
        	
        	params['token']       = Token.get_token('form_wi');
        	params['sensor_list'] = Object.keys(__location_sensors)

            $.ajax(
            {
                data: params,
                type: "POST",
                url: 'modifylocations.php',
                dataType: "json",
                success: function(data)
                {
                    if (__is_in_lightbox && typeof parent.GB_close == 'function')
                    {
                        parent.GB_close();
                    }
                    else
                    {
                        document.location.href = 'locations.php';
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

                    if (typeof(XMLHttpRequest.responseText) != 'undefined' && XMLHttpRequest.responseText != '')
                    {
                        __error_msg = XMLHttpRequest.responseText;
                    }

                    show_notification('av_info', __error_msg, 'nf_error', 20000, true);
                }
            });
    	}
    	
		$(document).ready(function()
		{
    		__is_in_lightbox = parent.is_lightbox_loaded(window.name);
    		
			Token.add_to_forms();
			
			$('textarea').elastic();
			
			var config = 
			{   
				validation_type: 'complete', // single|complete
				errors:
				{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : 
				{
					id  : 'form_wi',
					url : 'modifylocations.php'
				},
				actions: 
				{
					on_submit:
					{
						id: 'send',
						success: '<?php echo _('Save')?>',
						checking: '<?php echo _('Saving')?>'
					}
				}
			};
			
			ajax_validator = new Ajax_validator(config);
		
		    $('#send').off('click').on('click', function(e)
		    { 
    		    e.preventDefault();
    		    
    		    if (ajax_validator.check_form() == true)
                {
                    save_location();
                }
			});
		
            draw_selected_sensors();
			
			/* Google Map */			   
            av_map = new Av_map('c_map');
            
            Av_map.is_map_available(function (conn)
            {                                                                                        
                if(conn)
                {             
                    av_map.set_location('<?php echo $latitude?>', '<?php echo $longitude?>');                    
                    av_map.set_zoom(<?php echo $zoom?>);
                    av_map.set_address('<?php echo $location?>');  
                    
                    av_map.draw_map();
                        
                    if(av_map.get_lat() != '' && av_map.get_lng() != '')
                    {                        
                        av_map.add_marker(av_map.get_lat(), av_map.get_lng());
                                            
                        // Change title
                        av_map.markers[0].setTitle('<?php echo _('Sensor Location')?>');
                        av_map.markers[0].setMap(av_map.map);                    
                    }
                                                
                    $('#search_location').geo_autocomplete(new google.maps.Geocoder, {
    					mapkey: '<?php echo $map_key?>', 
    					selectFirst: true,
    					minChars: 3,
    					cacheLength: 50,
    					width: 300,
    					scroll: true,
    					scrollHeight: 330,					                  				
    				}).result(function(_event, _data) {
    																	
    					if (_data)
    					{ 						    						
    						//Set map coordenate
                            av_map.map.fitBounds(_data.geometry.viewport);
                                                        
                            var aux_lat = _data.geometry.location.lat();
                            var aux_lng = _data.geometry.location.lng();   
                            
                            //console.log(aux_lat);
                            //console.log(aux_lng);
                            
                            av_map.set_location(aux_lat, aux_lng);                            
                                                        
                            $('#latitude').val(av_map.get_lat());
                            $('#longitude').val(av_map.get_lng());
    
                            //Save address
                            
                            av_map.set_address(_data.formatted_address);
                                                    
                            //Save country
    						
    						var country = '';
                            var i       = _data.address_components.length-1;
                                                                            
                            for(i; i >= 0; i--)
                            {
                                var item = _data.address_components[i];
                                
                                if(item.types[0] == 'country')
                                {
                                    country = item.short_name;
                                    
                                    break;
                                }
                            }
                            
                            $('#country').val(country);
                            
                            // Marker (Add or update)
                            
                            av_map.remove_all_markers();
                            av_map.add_marker(av_map.get_lat(), av_map.get_lng());
                            
                            // Change title
                            av_map.markers[0].setTitle("<?php echo _('Sensor Location')?>");
                            av_map.markers[0].setMap(av_map.map);  
                                                                                                            
                            av_map.map.setZoom(8);											
    					}
    				});
    															
    				//Latitude and Longitude (Handler Onchange)
    				av_map.bind_pos_actions();
    				
    				//Search box (Handler Keyup and Blur)
    				av_map.bind_sl_actions();				
    								           
                }
                else
                {
                    av_map.draw_warning();
                    $('#search_location').val("<?php echo _('Unknown')?>");
                    $('#latitude, #longitude').attr('disabled', 'disabled');
                }
            });
            
    	
			// Entities tree
			<?php 
			if (Session::show_entities() && !$locations_id) 
			{ 
    			?>
    			$("#tree").dynatree(
    			{
    				initAjax: { url: "../tree.php?key=contexts&extra_options=local" },
    				clickFolderMode: 2,
    				onActivate: function(dtnode) 
    				{
    					var key = dtnode.data.key.replace(/e_/, "");
    					
    					if (key != "") 
    					{
    						$('#ctx').val(key);
    						
    						$('#entity_selected').html("<?php echo _("Context selected") ?>: <b>"+dtnode.data.val+"</b>");
    						
    						//Trigger change event for AJAX Validator
    						$('input[name^="sboxs"]').trigger('change');
    						$('#ctx').trigger('change');
    					}
    				},
    				onDeactivate: function(dtnode) {}
    			});
            <?php 
			} 
			?>
			
			$('#add_sensor').on('click', add_sensor);

			//Greybox options			
			if (__is_in_lightbox)
			{ 			
    			$('#table_form').css("width", "400px");
    			$('#table_form th').css("width", "150px");
    			$('#av_info').css({"width": "480px", "margin" : "10px auto"});        			
    		}
			else
			{
    			$('.c_back_button').show();        			
    		}		
		});
	</script>
</head>
<body>
                                                                                
<div id='av_info'></div>

<div class="c_back_button">         
    <input type='button' class="av_b_back" onclick="document.location.href='locations.php';return false;"/> 
</div> 

<form name='form_wi' id='form_wi' method="POST" action="modifylocations.php" enctype="multipart/form-data">
	<input type="hidden" class='vfield' name="locations_id" id="locations_id" value="<?php echo $locations_id?>"/>
	
	<div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>	
	
	<table align="center" id='table_form'>
		  
		<tr>
			<th>
				<label for='name'><?php echo _('Name') . required();?></label>
			</th>
			<td class="left">
				<input type="text" class='vfield' name="l_name" id="name" maxlength="64" value="<?php echo $name;?>"/>
			</td>
		</tr>
  
		<tr>
			<th>
				<label for='desc'><?php echo _('Description')?></label>
			</th>
			<td class="left">
				<textarea class='vfield' name="desc" id="desc" maxlength="255"><?php echo $desc?></textarea>
			</td>
		</tr>

		<input type="hidden" name="ctx" id="ctx" value="<?php echo Session::get_default_ctx() ?>" class="vfield"/>
							
		<tr>
			<th>
				<label for='search_location'><?php echo _('Location') . required();?></label>
			</th>
			<td class="left">
				<img src="../pixmaps/search_icon.png" border="0" align="top"/>
				<input type="text" style="margin-top:2px; margin-left:2px; width:312px" class='vfield' name="search_location" id="search_location" maxlength="255" value="<?php echo $location?>">
				<br>
				<input type="hidden" class='vfield' name="country" id="country" value="<?php echo $cou?>"/>
				<div id='c_map' style='margin-top:5px; height:200px; width:340px;'></div>				
			</td>
		</tr>

		<tr>
			<th>
				<label for='lat'><?php echo _('Latitude') . required();?></label>
			</th>
			<td class="left">
				<input type="text" class='vfield' name="latitude" id="latitude" maxlength="25" value="<?php echo $latitude?>"/>
			</td>
		</tr>

		<tr>
			<th>
				<label for='lon'><?php echo _('Longitude') . required();?></label>
			</th>
			<td class="left">
				<input type="text" class='vfield' name="longitude" id="longitude" maxlength="25" value="<?php echo $longitude?>"/>
			</td>
		</tr>

		<tr>
			<td colspan="2" style="height:20px"></td>
		</tr>	
		
		<tr>
			<th colspan="2" align="center">
				<label for='sensor'><?php echo _('Sensors in this Location')?></label>
			</th>
		</tr>	
		<tr>
			<td colspan="2" align="center">
			    <select name="sensor" id="sensor_list" style="width:200px">
    			    <option value="0"><?php echo _("Select a sensor")?></option>
			    <?php
		        foreach ($sensors as $s_data) 
		        {
			        echo "<option value='".$s_data['id']."'>".$s_data['name']. " [".$s_data['ip']."]</option>";
		        }
			    ?>
			    </select>
			    &nbsp;
			    <input type="button" id="add_sensor" class="small av_b_secondary" value="<?php echo _("Add sensor")?>">
			</td>
		</tr>	

		<tr>
			<td colspan="2" align="center">
			    <table id='sensor_selected_list' class="noborder"></table>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" style="height:10px"></td>
		</tr>
		
		<tr>
			<td colspan="2" align="center" style="padding: 10px;">
				<button id='send'><?php echo _('Save') ?></button>				
			</td>
		</tr>				
	</table>
</form>

</body>
</html>
