<?php
/**
* Form_builder.inc
*
* File Form_builder.inc is used to:
*   - Create dynamic forms
*
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* - set_attributes()
* - get_scripts()
* - add_script()
* - get_def_funcs()
* - add_def_func()
* - exists_def_func()
* - draw_element()
* - draw_asset()
* - draw_radioexc()
* - draw_checkbox()
* - draw_date()
* - draw_map()
* - draw_radiob()
* - draw_selectbox()
* - draw_slider()
* - draw_textarea()
* - draw_textbox()
*/

class Form_builder 
{
    /*Attributes*/

    private $params;

    private $scripts;

    private $def_funcs;

    //private static $types = array("Asset", "Check Yes/No", "Check True/False", "Checkbox", "Date", "Date Range", "Map", "Radio button", "Select box", "Slider", "Textarea", "Textbox", "File");

    /*Methods*/
	 
	public function __construct()
    {
		$this->scripts   = array();
		$this->def_funcs = array();
	}
	
	public function set_attributes($params) 
    { 
		unset($params['validation']);
		$this->params = $params;
	}
    
	
	public function get_scripts() 
    { 
        return implode("\n", $this->scripts);
    }
    
    
    public function add_script($script) 
    { 
        $this->scripts[] = $script;
    }
    
	
	public function get_def_funcs() 
    { 
        return implode("\n", $this->def_funcs);
    }
    
	
    public function add_def_func($name, $def_func) 
    { 
        $this->def_funcs[$name] = $def_func;
    }
    
	
	private function exists_def_func($func_name)
    {	
		if (in_array($func_name, $this->def_funcs))
        {
			return TRUE;
        }
		else
        {
			return FALSE;
        }	
	}	
			
	public function draw_element($type, $default_value = NULL)
    { 
		switch ($type)
        {
			case "Asset":
				$element= $this->draw_asset($default_value);
			break;
			
			case "Check Yes/No":
				$element= $this->draw_radioexc("y/n", $default_value);
				
			break;
			
			case "Check True/False":
				$element= $this->draw_radioexc("t/f", $default_value);
			break;
			
			case "Checkbox":
				$element= $this->draw_checkbox($default_value);
			break;
			
			case "Date":
				$element= $this->draw_date($default_value);
			break;
			
			case "Date Range":
				$element= $this->draw_daterange($default_value);
			break;
			
			case "Map":
				$element= $this->draw_map($default_value);
			break;
			
			case "Radio button":
				$element= $this->draw_radiob($default_value);	
			break;
			
			case "Select box":
				$element= $this->draw_selectbox($default_value);
			break;
			
			case "Slider":
				$element= $this->draw_slider($default_value);
			break;
						
			case "Textarea":
				$element= $this->draw_textarea($default_value);
			break;
			
			case "Textbox":
				$element= $this->draw_textbox($default_value);
			break;
			
			case "File":
				$element= $this->draw_file();
			break;
						
		}
		
		return $element;
	}
	
    
    public function draw_asset($default_value)
    {		
        foreach ($this->params as $k => $v) 
        {
            $attributes .= "$k=\"$v\" ";
        }

        $asset = $default_value;
        
        if($asset == '' || $asset== 'ALL_ASSETS')
        {
            $txt_asset   =  "All Assets";  
            $asset       =  "ALL_ASSETS";  
        }
        else
        {
            $txt_asset = $asset;
            $asset = $default_value;
        }
        
        $element .= "<script type='text/javascript' src='/ossim/js/utils.js'></script>";
        $element .= "<div class='ct_assets field_fix'><b>"._('Selected:')."</b>&nbsp;<span id='dassets_".$this->params["id"]."' style='background-color:#EFEBDE;padding:2px 5px 2px 5px;border:1px dotted #cccccc;font-size:12px'>\n";
        $element .= "$txt_asset</span>";
        $element .= "<div id='tree_".$this->params["id"]."' style='text-align:left;padding:5px 0px 0px 0px;'></div>\n";
        $element .= "<input type='hidden' $attributes value='$asset'/>\n";
        $element .= "</div>\n";
        
        $keytree = ( Session::is_pro() ) ? "assets|entitiesassets" : "assets";        
        
        $script = "
            $('#tree_".$this->params["id"]."').dynatree({
            initAjax: { url: '../tree.php?key=".$keytree."' },
            clickFolderMode: 2,
            onActivate: function(dtnode) {
                if(dtnode.data.key!='') {              
                    var value  = dtnode.data.key;	
                    
                    var name = '';
                    
                    if(dtnode.data.key == 'ANY')
                    {
                        name = 'All Assets';
                    }
                    else if (value.match(/^u_/)) 
                    {
                        name = 'User: ' + dtnode.data.title;
                    } 
                    else if (value.match(/^e_/)) 
                    {
                        name = 'Entity: ' + dtnode.data.val;
                    } 
                    else if (value.match(/^host_/)) 
                    {
                        name = 'Host: ' + dtnode.data.val;
                    } 
                    else if (value.match(/^hostgroup_/)) 
                    {
                        name = 'Host Group: ' + dtnode.data.val;
                    } 
                    else if (value.match(/^net_/)) 
                    {
                        name = 'Net: ' + dtnode.data.val;
                    } 
                    else if (value.match(/^netgroup_/)) 
                    {
                        name = 'Net Group: ' + dtnode.data.val;
                    }                     
                    else
                    {
                        name = dtnode.data.val;
                    }
                                                        
                    name = html_entity_decode(name);
                    $('#".$this->params["id"]."').val(name);
                    name = ( name.length > 50 ) ? name.substring(0, 50)+ '...' : name;
                    $('#dassets_".$this->params["id"]."').html(name);
                }
            },
            onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: '../tree.php',
                    data: {key: dtnode.data.key, page: dtnode.data.page}
                });
            }
        });\n\n";
        
        $this->add_script($script);
         
        return $element;
    }
    

    public function draw_radioexc($subtype, $default_value)
    {        
        $values = ($subtype == "y/n") ? array(_("Yes"), _("No")) : array(_("True"), _("False"));
            
        $aux = "<input type='radio' ";
        
        foreach ($this->params as $k => $v)
        {
            if ($k != "id")
            {
                $aux .= "$k=\"$v\" ";
            }
        }
        
        $checked_1 = ($default_value ==  $values[0])  ? "checked='checked'": ""; 
        $checked_2 = ($default_value ==  $values[1])  ? "checked='checked'": ""; 
        
        
        $element  = $aux." id='".$this->params["id"]."_1' value='".$values[0]."' $checked_1/><span style='margin-right: 3px;'>".$values[0]."</span>\n";
        $element .= $aux." id='".$this->params["id"]."_2' value='".$values[1]."' $checked_2/><span>".$values[1]."</span>\n";
                
        return $element;
    }


    public function draw_checkbox($default_value)
    {	
        $element = '';
        
        $default_value = ( unserialize($default_value) != FALSE ) ? unserialize($default_value) : array($default_value);
        $default_value = ( empty($default_value) ) ? array() : $default_value;
        
            
        foreach ($this->params as $k => $v)
        {
            if ($k != "id" && $k != "name" && $k != "values")
            {
                $attributes .= "$k=\"$v\" ";
            }
        }	
        
        if (is_array($this->params['values']))
        {
            foreach ($this->params['values'] as $k => $v)
            {
                $v         = trim($v);
                $value     = explode(":", $v);
                $num_value = count($value);
                
                $checked   = (in_array($value[0], $default_value) || ($default_value == '' && $num_value > 2)) ? "checked='checked'": ""; 
            
                if (!empty($v))
                {
                    if ($num_value == 1)
                    {
                        $values []= "value='".Util::htmlentities($value[0])."' $checked/><span style='padding-left: 5px;'>".Util::htmlentities($value[0])."</span>\n ";
                    }
                    else if ($num_value == 2)
                    {
                        $values []= "value='".Util::htmlentities($value[0])."' $checked/><span style='padding-left: 5px;'>".Util::htmlentities($value[1])."</span>\n ";
                    }
                    else 
                    {
                        if ($num_value > 2)
                        {
                            $values []= "value='".Util::htmlentities($value[0])."' $checked/><span style='padding-left: 5px;'>".Util::htmlentities($value[1])."</span>\n ";
                        }
                    }
                }
            }		
            
            for ($i=0; $i<count($values); $i++)     
            {        
                $element .= "<div><input type='checkbox' name='".$this->params['name']."' id='".$this->params['id'][$i]."' ".$attributes." ".$values[$i]."</div>\n";
            }    
            
        }
        else
        {
            $element .= "<div style='padding: 5px 0px 0px 5px;'><span style='color: #D8000C; font-weight: bold;'>"._("Error: Option not introduced")."</span></div>\n";
        }
        
        return $element;
    }
    

    public function draw_date($default_value)
    {
        foreach ($this->params as $k => $v)
        {
            $attributes .= "$k=\"$v\" ";
        }
        
        $element = "<div id='cont_".$this->params['id']."' class='datepicker_date' style='position: relative;margin:0px 0px 0px 20px;'>\n
                        <input type='text' ".$attributes." value='$default_value'/>\n
                    </div>\n";
        
        if ($this->exists_def_func('create_date') == FALSE)
        {
            $def_func = "
                
                function create_date(id, date)
                {
                    var id_cont  = '#cont_'+id;
                    var id       = '#'+id;			
                    
                    $(id_cont + ' .date_filter').datepicker({
                        showOn: 'both',
                        buttonText: '',
                        dateFormat: 'yy-mm-dd',
                        buttonImage: '../pixmaps/calendar.png'
                    });
                }
            ";
            
            $this->add_def_func("create_date", $def_func);
        }
        
        $script = "create_date('".$this->params['id']."', '$default_value');";
        $this->add_script($script);
        
        return $element;
    }


    public function draw_daterange($default_value)
    {
        foreach ($this->params as $k => $v)
        {
            $attributes .= "$k=\"$v\" ";
        }			
        
        list($date_from, $date_to) = split(',', $default_value);
        
        $element = "<div id='cont_".$this->params['id']."' class='datepicker_range' style='position: relative;padding: 0px 0px 0px 20px;'>\n
                        <div class='calendar_from'>\n
                            <div class='calendar'>\n
                                <input id='date_from_".$this->params['id']."' class='date_filter' type='input' value='$date_from'>\n
                            </div>\n
                        </div>\n
                        <div class='calendar_separator'>\n
                            -
                        </div>\n
                        <div class='calendar_to'>\n
                            <div class='calendar'>\n
                                <input id='date_to_".$this->params['id']."' class='date_filter' type='input' value='$date_to'>\n
                                <input type='hidden' ".$attributes." readonly='readonly' style='width:150px;' value='$default_value'/>\n
                            </div>
                        </div>
            </div>\n";
        
        
        if ($this->exists_def_func('create_daterange') == FALSE)
        {
            $def_func = "
                            
                function create_daterange(id, date_range)
                {        
                    var id_cont  = '#cont_'+id;
                    var id_input = '#'+id;
                    
                    $(id_cont + ' .date_filter').datepicker({   
                    
                        showOn: 'both',
                        dateFormat: 'yy-mm-dd',
                        buttonText: '',
                        buttonImage: '../pixmaps/calendar.png',
                        onClose: function(selectedDate)
                        {
                            // End date must be greater than the start date
                            
                            if ($(this).attr('id') == 'date_from_' + id)
                            {
                                $('#date_to_' + id).datepicker('option', 'minDate', selectedDate );
                            }
                            else
                            {
                                $('#date_from_' + id).datepicker('option', 'maxDate', selectedDate );
                            }
                            
                            if ($('#date_from_' + id).val() != '' && $('#date_to' + id).val() != '' )
                            {
                                $(id_input).val($('#date_from_' + id).val() + ', ' + $('#date_to_' + id).val());
                            }
                        }
                    });
                    
                }\n\n";
                    
            $this->add_def_func('create_daterange', $def_func);
        }
        
        $script = "create_daterange('".$this->params['id']."', '$default_value');";
        $this->add_script($script);
        
        return $element;	
    }	


    public function draw_map($default_value)
    {        
        foreach ($this->params as $k => $v)
        {
            if ($k != 'values')
            {
                $attributes .= "$k=\"$v\" ";
            }
        }
        
        $map_key = ($this->params["values"][0] != '' ) ? $map_key : "ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBTIx7cuHpcaq3fYV4NM0BaZl8OxDxS9pQpgJkMv0RxjVl6cDGhDNERjaQ";
        
        $element .= "
            <div id='c_map_".$this->params["id"]."'>
                <div style='float: left; width:15%; font-weight: bold; padding: 15px 0px 10px 6px;'><span>Location:</span></div>
                <div style='float: right; width:82%; font-weight: bold; padding: 10px 0px 10px 0px;'>
                <input type='text' ".$attributes." value='$default_value'/></div>
            </div>
            <div id='map_".$this->params["id"]."' class='field_fix' style='height:300px; display: none; clear: both; margin-left: 6px;'></div>";
        
        $script = "	
            
            av_map_".$this->params["id"]." = new Av_map('map_".$this->params["id"]."');
            
            if(Av_map.is_map_available())
            {                                       
                //Set inputs IDs
                                              
                av_map_".$this->params["id"].".set_options(
                    {lat_id : 'latitude_".$this->params["id"]."', 
                     lng_id : 'longitude_".$this->params["id"]."', 
                     country_id : 'country_".$this->params["id"]."', 
                     sl_id : '".$this->params["id"]."', 
                     zoom_id : 'zooom_".$this->params["id"]."'}
                );         
                           
                                                
                sl_".$this->params["id"]." = null;
                                        
                $('#".$this->params["id"]."').geo_autocomplete(new google.maps.Geocoder, {
                    mapkey: '".$map_key."', 
                    selectFirst: true,
                    minChars: 3,
                    cacheLength: 50,
                    width: 300,
                    scroll: true,
                    scrollHeight: 330
                }).result(function(_event, _data) {
                    if (_data) 
                    {
                        $('#map_".$this->params["id"]."').show();                   						
    						
						av_map_".$this->params["id"].".draw_map();
						
						//Set map coordenates
                        av_map_".$this->params["id"].".map.fitBounds(_data.geometry.viewport);
                                                    
                        var aux_lat = _data.geometry.location.lat();
                        var aux_lng = _data.geometry.location.lng();   
                        
                        //console.log(aux_lat);
                        //console.log(aux_lng);
                        
                        av_map_".$this->params["id"].".set_location(aux_lat, aux_lng);                            
                                                    
                        $('#latitude').val(av_map_".$this->params["id"].".get_lat());
                        $('#longitude').val(av_map_".$this->params["id"].".get_lng());

                        //Save address
                        
                        av_map_".$this->params["id"].".set_address(_data.formatted_address);
                        
                        // Marker (Add or update)
                        
                        av_map_".$this->params["id"].".remove_all_markers();
                        av_map_".$this->params["id"].".add_marker(av_map_".$this->params["id"].".get_lat(), av_map_".$this->params["id"].".get_lng());
                        
                        // Change title
                        av_map_".$this->params["id"].".markers[0].setTitle('"._('Location')."');                        
                        av_map_".$this->params["id"].".markers[0].setMap(av_map_".$this->params["id"].".map);                                                    
                                                                                                              
                        av_map_".$this->params["id"].".map.setZoom(8);                                                         
                     }
                });
            }
            else
            {            
                $('#c_map_".$this->params["id"]."').hide();
                
                av_map_".$this->params["id"].".draw_warning();
                $('#map_".$this->params["id"]."').show();
                $('#map_".$this->params["id"]."').css('height', '100px');            
            }";
            
        $this->add_script($script);        
            
        return $element;
    }	


    public function draw_radiob($default_value)
    {        
        $element    = '';
        $attributes = '';
        
        foreach ($this->params as $k => $v)
        {
            if ($k != "id" && $k != "name" && $k != "values")
            {
                $attributes .= "$k=\"$v\" ";
            }
        }	
                
        if (is_array($this->params['values']))
        {		
            foreach ($this->params['values'] as $k => $v)
            {
                $v = trim($v);
                $value = explode(":", $v);
                $num_value = count($value);
                
                $checked = ($value[0] == $default_value || ($default_value == '' && $num_value > 2)) ? "checked='checked'": ""; 
                
                if (!empty($v))
                {
                    if ($num_value == 1)
                    {
                        $values []= "value='".Util::htmlentities($value[0])."' $checked/><span style='padding-left: 5px;'>".Util::htmlentities($value[0])."</span> ";
                    }
                    else if ($num_value == 2)
                    {
                        $values []= "value='".Util::htmlentities($value[0])."' $checked/><span style='padding-left: 5px;'>".Util::htmlentities($value[1])."</span> ";
                    }
                    else 
                    {
                        if ($num_value > 2)
                        {
                            $values []= "value='".Util::htmlentities($value[0])."' $checked/><span style='padding-left: 5px;'>".Util::htmlentities($value[1])."</span> ";
                        }
                    }
                }
            }	
            
            for ($i=0; $i<count($values); $i++)
            {
                $element .= "<div><input type='radio' name='".$this->params['name']."' id='".$this->params['id'][$i]."' ".$attributes." ".$values[$i]."</div>\n";
            }
        }
        else
        {
            $element .= "<div style='padding: 5px 0px 0px 5px;'><span style='color: #D8000C; font-weight: bold;'>"._("Error: Not option introduced")."</span></div>\n";
        }
        
        return $element;
    }	

    
    public function draw_selectbox($default_value)
    {            
        $attributes = '';
        $element    = '';
        
        $values = $this->params['values'];
        unset($this->params['values']);
        
        foreach ($this->params as $k => $v)
        {
            $attributes .="$k=\"$v\" ";
        }
        
        if (is_array($values))
        {
            foreach ($values as $k => $v)
            {
                $v         = trim($v);
                $value     = explode(":", $v);
                $num_value = count($v);
                
                $selected  = ($value[0] == $default_value) ? "selected='selected'": ""; 
                            
                if (!empty($v))
                {
                    if ($num_value == 1)
                    {
                        $options.= "<option value='".Util::htmlentities($value[0])."' $selected>".Util::htmlentities($value[0])."</option>\n";
                    }
                    else if ($num_value == 2)
                    {                     
                        $options.= "<option value='".Util::htmlentities($value[0])."' $selected>".Util::htmlentities($value[1])."</option>\n";
                    }
                    else 
                    {
                        if ($num_value > 2)
                        {
                            $options.= "<option value='".Util::htmlentities($value[0])."' $selected>".Util::htmlentities($value[1])."</option>\n";
                        }
                    }
                }	
            }
            
            $element = "<select ".$attributes.">".$options."</select>";
        }
        else
        {
            $element = "<select ".$attributes."><option style='text-align:center;' value=''>"._("Not options introduced")."</option></select>";
        }	
        
        
        return $element;
    }

    public function draw_slider($default_value)
    {
        $values = $this->params['values'];
        unset($this->params['values']);
        
        foreach ($this->params as $k => $v)
        {
            $attributes .= "$k=\"$v\" ";
        }
            
        $min_value = (is_numeric($values[0])) ? $values[0] : "0";
        $max_value = (is_numeric($values[1])) ? $values[1] : "10";
        $step      = (is_numeric($values[2])) ? $values[2] : "1";
        
        $default_value = ($default_value != '') ? $default_value : $min_value;
        
        $element = "<div class='ct_slider'>
                        <div id='cont_".$this->params['id']."' style='height:5px;'></div>
                    </div>
                    
                    <div style='float:left; padding-top: 3px; width:30px; heigth: 25px;'>
                        <span id='dial_".$this->params['id']."' style='font-weight: bold; padding: 0px 6px'>".$default_value."</span>
                    </div>
                    
                    <input type='hidden' ".$attributes." value='".$default_value."'/>";
        

        $script = "
            $('#cont_".$this->params['id']."').slider({
                animate: false,
                range: 'min',
                value: $default_value,
                min: $min_value,
                max: $max_value,
                step: $step,
                slide: function(event, ui) {
                    \$('#dial_".$this->params['id']."').html(ui.value);
                    \$('#".$this->params['id']."').val(ui.value);
                }
            });\n\n";
        
        $this->add_script($script);
        
        return $element;
    }


    public function draw_textarea($default_value)
    {        
        $attributes = '';

        foreach ($this->params as $k => $v)
        {
            $attributes .= "$k=\"$v\" ";
        }
        
        $element = "<textarea $attributes>$default_value</textarea>";
        
        return $element;
    }
    

    public function draw_textbox($default_value)
    {        
        $attributes = '';

        foreach ($this->params as $k => $v)
        {
            $attributes .= "$k=\"$v\" ";
        }
        
        $element = "<input type='text' $attributes value='$default_value'/>";
        
        return $element;
    }
    
			
    public function draw_file()
    {        
        $attributes = '';

        foreach ($this->params as $k => $v)
        {
            $attributes .= "$k=\"$v\" ";
        }
        
        $element = "<input type='file' $attributes/>";
        
        return $element;
    }	
}

/* End of file Form_builder.inc */
/* Location: ../include/classes/Form_builder.inc */ 
