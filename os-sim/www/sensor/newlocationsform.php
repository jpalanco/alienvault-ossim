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

$locations_id     = (GET('id') != '') ? GET('id') : POST('locations_id');
$action           = ($locations_id != '') ? 'modifylocations.php' : 'newlocations.php';
$sensor_id        = POST('sensor');
$delete_id        = POST('delete');

$ip      = ''; 
$name    = '';
$status  = 1;
$zoom    = 1;
	
if ($locations_id != '')
{
	ossim_valid($locations_id, OSS_HEX,            'illegal:' . _('Location ID'));
	ossim_valid($sensor_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _('Sensor'));
	ossim_valid($delete_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _('Delete'));

	if (ossim_error())
	{ 
		die(ossim_error());
	}
		
	if ($locations_list = Locations::get_list($conn, " AND id = UNHEX('$locations_id')"))
	{
		$location       = $locations_list[0];
		$ctx            = $location->get_ctx();
		$name           = $location->get_name();
		$desc           = $location->get_desc();
		$latitude       = str_replace(',', '.', floatval($location->get_lat()));
		$longitude      = str_replace(',', '.', floatval($location->get_lon()));
		$zoom           = 4;
		$cou            = strtolower($location->get_country());
		$location       = $location->get_location();
	}
	
	// Insert related sensor
	if ($sensor_id != '')
	{
	   Locations::insert_related_sensor($conn,$locations_id,$sensor_id);
	   Util::memcacheFlush();
	}
	// Delete related sensor
	if ($delete_id != '')
	{
	   Locations::delete_related_sensor($conn,$locations_id,$delete_id);
	   Util::memcacheFlush();
	}
	
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>

    <script type="text/javascript" src=" https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>    
	<script type="text/javascript" src="../js/jquery.autocomplete_geomod.js"></script> 
	<script type="text/javascript" src="../js/geo_autocomplete.js"></script>
	<script type="text/javascript" src="../js/av_map.js.php"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>

	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>  
	<link rel="stylesheet" type="text/css" href="../style/tree.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>	
	
	<script type="text/javascript">
		$(document).ready(function(){
			
			Token.add_to_forms();
			
			$('textarea').elastic();
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'form_wi',
					url : '<?php echo $action?>'
				},
				actions: {
					on_submit:{
						id: 'send',
						success: '<?php echo _('Save')?>',
						checking: '<?php echo _('Saving')?>'
					}
				}
			};
		
			ajax_validator = new Ajax_validator(config);
		
		    $('#send').click(function() { 
				ajax_validator.submit_form();
			});
		
			
			/* Google Map */			   
            
            av_map = new Av_map('c_map');
                                                                                                                
            if(Av_map.is_map_available())
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
                        av_map.markers[0].setTitle('<?php echo _('Sensor Location')?>');
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
                $('#send, #search_location, #latitude, #longitude').attr('disabled', 'disabled');          
            }
            
            						
			// Entities tree
			<?php 
			if (Session::show_entities() && !$locations_id) 
			{ 
    			?>
    			$("#tree").dynatree({
    				initAjax: { url: "../tree.php?key=contexts&extra_options=local" },
    				clickFolderMode: 2,
    				onActivate: function(dtnode) {
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
			
			//Greybox options			
			
			if (parent.is_lightbox_loaded(window.name))
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
  
</head>
<body>
                                                                                
<div id='av_info'></div>

<div class="c_back_button">         
    <input type='button' class="av_b_back" onclick="document.location.href='locations.php';return false;"/> 
</div> 

<form name='form_wi' id='form_wi' method="POST" action="<?php echo ($locations_id != '') ? 'modifylocations.php' : 'newlocations.php' ?>" enctype="multipart/form-data">

	<input type="hidden" name="insert" value="insert"/>
	
	
	<div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>	
	
	<table align="center" id='table_form'>
		  
		<tr>
			<th>
				<label for='name'><?php echo _('Name') . required();?></label>
			</th>
			<td class="left">
				<input type="text" class='vfield' name="name" id="name" maxlength="64" value="<?php echo $name;?>"/>
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
				<input type="text" style="margin-top:2px; margin-left:2px; width:312px" class='vfield' name="search_location" id="search_location" maxlength="255" value="<?php echo $location?>"/>
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

		<?php 
		if ($locations_id != '') 
		{
            $sensors = Av_sensor::get_basic_list($conn);
            $related = Locations::get_related_sensors($conn, $locations_id);
            ?>
    		<input type="hidden" class='vfield' name="locations_id" id="locations_id" value="<?php echo $locations_id?>"/>
    		<input type="hidden" class='vfield' name="delete" id="delete"/>
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
    			    <select name="sensor" id="sensor" style="width:200px"><option value="0"> ---- <?php echo _("Select a sensor")?> ---- </option>
    			    <?php
    			        foreach ($sensors as $s_data) 
    			        {
        			        echo "<option value='".$s_data['id']."'>".$s_data['name']. " [".$s_data['ip']."]</option>";
    			        }
    			    ?>
    			    </select>
    			    &nbsp;
    			    <input type="button" class="small av_b_secondary" value="<?php echo _("Add sensor")?>" onclick="$('#form_wi').attr('action','newlocationsform.php');$('#form_wi').submit()">
    			</td>
    		</tr>	

		<tr>
			<td colspan="2" align="center">
			    <table cellpadding="0" cellspacing="2" class="noborder">
    			    <?php
    		        foreach ($related as $rel) 
    		        {
    			        echo "<tr>
        			        <td> <b>".$rel[1]. " [".$rel[2]."]</b> </td>
        			        <td> &nbsp; <input type='button' class='small av_b_secondary' value='"._("Delete")."'  onclick=\"$('#delete').val('".$rel[0]."');$('#form_wi').attr('action','newlocationsform.php');$('#form_wi').submit()\"> </td>
    			        </tr>";
    		        }
    			    ?>
			    </table>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" style="height:10px"></td>
		</tr>
		
		<?php 
		}
		?>

		<tr>
			<td colspan="2" align="center" style="padding: 10px;">
				<input type="button" id='send' name='send' value="<?php echo _('Save')?>"/>				
			</td>
		</tr>				
	</table>
</form>

</body>
</html>
<?php
$db->close();
?>
