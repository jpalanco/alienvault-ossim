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


/****************************************************
 *************** Geolocation functions **************
 ****************************************************/
          

function Av_map(map_id)
{
    var _map_id       = '#' + map_id;    
    var _lat          = null;
    var _lng          = null;
    var _address      = ''; 
    var _zoom         = 1;
    var _scroll_wheel = true;
    
    //Google Maps Objects
    this.map          = null;
    this.markers      = [];
    this.lat_lng      = null;
    
    
    //Inputs
    var _lat_id       = '#latitude';
    var _lng_id       = '#longitude';
    var _country_id   = '#country';
    var _sl_id        = '#search_location';
    var _zoom_id      = '#zoom';    
    var _center_zoom  = true;
        
    // Set options
    this.set_options = function(options)
    {
        /*
        console.log('Type of options: ' + typeof(options));
        console.log('Lat ID: ' + options.lat_id);
        console.log('Lng ID: ' + options.lng_id); 
        console.log('Country ID: ' + options.country_id); 
        console.log('Sl ID: ' + options.sl_id);
        console.log('Zoom ID: ' + options.zoom_id);             
        */
        
        if (typeof(options) == 'object' && options != null)
        {
            //Latitude
            if (typeof(options.lat_id) != 'undefined' && options.lat_id != null)
            {
                _lat_id = '#' + options.lat_id;
            }
            
            //Longitude
            if (typeof(options.lng_id) != 'undefined' && options.lng_id != null)
            {
                _lng_id = '#' + options.lng_id;
            }

            //Country
            if (typeof(options.country_id) != 'undefined' && options.country_id != null)
            {
                _country_id = '#' + options.country_id;
            }
            
            //Location box
            if (typeof(options.zoom_id) != 'undefined' && options.zoom_id != null)
            {
                _zoom_id = '#' + options.zoom_id;
            }
            
            //Location box
            if (typeof(options.sl_id) != 'undefined' && options.sl_id != null)
            {
                _sl_id = '#' + options.sl_id;
            }
        }       
    };
    
    // Get Map ID
    this.get_map_id = function()
    {
        return _map_id;
    };
    // Get Latitude
    this.get_lat = function()
    {
        return _lat;
    };
    
    
    // Set Latitude
    this.set_lat = function(lat)
    {
        _lat = Av_map.format_coordenate(lat);
    };
    
    
    //Set center on zoom option
    this.set_center_zoom = function(opt)
    {
        _center_zoom = (opt === false) ? false : true;
    };
    
    
    //Set Scroll Wheel Zoom option
    this.set_scroll_wheel = function(opt)
    {
        _scroll_wheel = (opt === false) ? false : true;
    };
    
    // Get Longitude
    this.get_lng = function()
    {
        return _lng;
    };
    
    
    // Set Longitude
    this.set_lng = function(lng)
    {
        _lng = Av_map.format_coordenate(lng);  
    };
          
          
    // Get Latitude ID
    this.get_lat_id = function()
    {
        return _lat_id;
    };


    // Get Longitude ID
    this.get_lng_id = function()
    {
        return _lng_id;
    };


    // Get Country ID
    this.get_country_id = function()
    {
        return _country_id; 
    };


    // Get Search Location ID
    this.get_sl_id = function()
    {
        return _sl_id;
    };


    // Get Zoom ID
    this.get_zoom_id = function()
    {
        return _zoom_id;
    };
    
    // Get Zoom ID
    this.get_center_zoom = function()
    {
        return _center_zoom;
    };
        
    
    // Draw warning message if system doesn't have internet connection        
    this.draw_warning = function()
    {      
        var config_nt = { 
            content: '<?php echo _('Maps not available, you need Internet connection')?>', 
            options: {
                type:'nf_warning',
                cancel_button: false
            },
            style: 'width: 90%; margin: 30px auto 10px auto; padding: 5px 0px; font-size: 11px; text-align: center;'
        };
        
        
        var nt_id = 'nt_'+ this.get_map_id().replace('#', '');
                
        nt = new Notification(nt_id, config_nt);
                
        $(this.get_map_id()).html(nt.show());
        
        this.hide_loading();
    };
    
    // Draw Map
    this.draw_map = function()
    {
        var that = this;
                
        var map_obj = document.getElementById(this.get_map_id().replace('#', ''));        
                                           
        if(typeof(map_obj) == 'undefined' || map_obj == null)
        {
            return false;
        }
                          
        this.lat_lng = new google.maps.LatLng(this.get_lat(), this.get_lng());      
                        
        var map_options = {
            zoom: this.get_zoom(),
            center: this.lat_lng,
            scrollwheel: _scroll_wheel,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            panControl: false,
            streetViewControl: false,
            navigationControl: true,
            navigationControlOptions: {
                style: google.maps.NavigationControlStyle.SMALL
            }
        };
        
        this.map = new google.maps.Map(map_obj, map_options);
        
        google.maps.event.addListener(this.map, 'zoom_changed', function(){
            
            var zoom_id = that.get_zoom_id();
                
            that.set_zoom(this.getZoom());
            
            if (that.get_center_zoom())
            {
                this.setCenter(that.lat_lng);
            }

            $(zoom_id).val(that.get_zoom());
        });
        
        that.hide_loading();
        
        google.maps.event.trigger(this.map, 'resize');
    };
    
    
    // Add new marker
    this.add_marker = function(lat, lng)
    {                
        //console.log('Lat: ' + lat);
        //console.log('Lng: ' + lng);   
        
        var that = this;        
        
        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(lat, lng),
            draggable: true,
            animation: google.maps.Animation.DROP,
            map: this.map, 
            title: "<?php echo _('Host Location')?>"
        });  

        this.markers.push(marker);
        
        var m_index = Object.keys(this.markers).length - 1;
                                     
        google.maps.event.addListener(this.markers[m_index], 'click', function(){
                        
            if (this.getAnimation() != null)
            { 
                this.setAnimation(null);
            }
            else
            { 
                this.setAnimation(google.maps.Animation.BOUNCE);
            }
        });
        
        google.maps.event.addListener(this.markers[m_index], 'dragend', function(){                    
                        
            var lat = this.getPosition().lat();
            var lng = this.getPosition().lng();            
                       
            //Only update address, latitude and longitude with one marker
            if (Object.keys(that.markers).length <= 1)
            {
                that.set_location(lat, lng);

                $(that.get_lat_id()).val(that.get_lat());
                $(that.get_lng_id()).val(that.get_lng());
                            
                that.set_address_by_coordenates(this.getPosition());
            }      
        });
    };
    
    
    //Set a new marker position
    this.set_marker_position = function(marker, lat, lng)
    {        
        try
        {
            var lat_lng = new google.maps.LatLng(lat, lng);
        
            marker.setPosition(lat_lng); 
        }
        catch(err)
        {
            console.log('<?php echo _("Marker not found")?>');   
        }      
    };    
        
    
    //Remove all markers
    this.remove_all_markers = function()
    {     
        if (typeof(this.markers) != 'undefined' && this.markers != null)
        {
            var num_markers = Object.keys(this.markers).length;
            
            for (var i = 0; i < num_markers; i++) 
            {
                this.markers[i].setMap(null);
            }
            
            this.markers = [];               
        }     
    };
    
    
    //Set map location
    this.set_location = function (lat, lng)
    {        
        if (typeof(lat) != 'undefined' && typeof(lng) != 'undefined')
        {           
            //console.log('Lat: ' + lat);
            //console.log('Lng: ' + lng);           
            
            this.set_lat(lat);
            this.set_lng(lng);
            
            //console.log('Lat: ' + that.get_lat());
            //console.log('Lng: ' + that.get_lng());  
                                    
            this.lat_lng = new google.maps.LatLng(lat, lng);
            
            // Center map and set zoom                                          
            if (typeof(this.map) == 'object' && this.map != null)
            {                                
                var z = (this.get_lat() != '' && this.get_lng() != '') ? this.get_zoom() : 1;                                       
                                               
                this.map.setZoom(z);
            }
        }
    };
    
    
    //Set location by address
    this.set_location_by_address = function(address) 
    {                             
        var that = this;
        var lat = null;
        var lng = null;
                    
        new google.maps.Geocoder().geocode({
            address: address
        }, 
        function(results, status) 
        {                     
            if (status == google.maps.GeocoderStatus.OK) 
            {                                             
                // Set Latitude and Longitude
                
                lat = results[0].geometry.location.lat();
                lng = results[0].geometry.location.lng();
                
                that.set_location(lat, lng);
                
                $(that.get_lat_id()).val(that.get_lat());
                $(that.get_lng_id()).val(that.get_lng());
                
                // Set minimum zoom
                var z = (that.get_zoom() < 8) ? 8: that.get_zoom();
                                
                that.map.setZoom(z);
                    
                // Set address                
                that.set_address(results[0].formatted_address);                
                
                /*
                console.log('New formatted address: ' + results[0].formatted_address);
                console.log('Current Lat: ' + that.get_lat());
                console.log('Current Lng: ' + that.get_lng());
                console.log('Current Address: ' + that.get_address());
                */
                                
                // Marker (Add or update)
                if (Object.keys(that.markers).length <= 1)
                {
                    that.remove_all_markers();
                    that.add_marker(that.get_lat(), that.get_lng());
                }                                             
            } 
            
            // Update address in search box
            $(that.get_sl_id()).val(that.get_address());                          
        });
    };
    
    
    // Get Address
    this.get_address = function()
    {
        return _address;
    };
    
    //Set map address
    this.set_address = function (addr)
    {
        if (typeof(addr) != 'undefined')
        {
            _address = addr;
        }
    };
            
    
    this.set_address_by_coordenates = function(lat_lng)
    {
        var that = this;
        var address = ''
        
        new google.maps.Geocoder().geocode({
            latLng: lat_lng
        }, 
        function(results, status) 
        {                     
            if (status == google.maps.GeocoderStatus.OK) 
            {                
                address = results[0].formatted_address;                 
            } 
            else 
            {
                address = '<?php echo _('Undetermined location')?>';
            }            
                                               
            that.set_address(address);
                                  
            $(that.get_sl_id()).val(address);         
        });
    };
    
    
    // Get Zoom
    this.get_zoom = function()
    {
        return _zoom;
    };
    
    
    // Set map zoom
    this.set_zoom = function(zoom)
    {    
        var z = parseInt(zoom);

        if(!isNaN(z))
        {
            _zoom = z;                        
        } 
    };
    
    
    /* Common handlers*/

    // Latitude and longitude inputs
    this.bind_pos_actions = function()
    {    
        var that = this;
        var lat_id = that.get_lat_id();
        var lng_id = that.get_lng_id();
                                      
        $(lat_id + ', ' + lng_id).on('change', function() {
    
            //Set map location
            var lat = $(lat_id).val();
            var lng = $(lng_id).val();
            
            that.set_location(lat, lng);
            
            //Latitude and longitude are empty
            if (that.get_lat() == '' && that.get_lng() == '')
            {
                that.reset_data();
            }
            else
            {
                that.set_address_by_coordenates(new google.maps.LatLng(lat, lng));            
                        
                if (Object.keys(that.markers).length <= 1)
                {
                    that.remove_all_markers();
                    that.add_marker(lat, lng);
                    
                    //Change zoom with both fields filled
                    if (lat != '' && lng != '')
                    {
                        that.map.setZoom(4);
                    }                
                }   
            }              
        });
    };

    // Search box
    this.bind_sl_actions = function(map)
    {
        var that  = this;
        var sl_id = that.get_sl_id();
        
        //Save last right address
        $(sl_id).on('blur', function() {
            
            var current_addr = $(sl_id).val();
            
            /*            
            console.log('Current address: ' + current_addr);
            console.log('Last address: ' + that.get_address());            
            */
                        
            // Address is wrong                         
            if (current_addr != '' && current_addr != that.get_address())
            {            
                that.set_location_by_address(current_addr);                
            }
        });    
    
        //Clear last address when search box is empty
        $(sl_id).on('keyup', function() {
            
            if ($(this).val() == '')
            {
                that.reset_data();
            }
        }); 
    };
    
    
    this.show_loading = function()
    {
        var $map    = $(_map_id);
        var pos     = $map.css('position');
        
        if (pos != 'absolute')
        {
            $map.css('position', 'relative');
        }
        
        var loading = '<img style="height:14px;" src="<?php echo AV_PIXMAPS_DIR ?>/loading.gif"/>';
        var style   = "position:absolute;top:50%;margin-top:-14px;left:0;right:0;text-align:center;"
        
        $('<div></div>',
        {
           "html" : "<?php echo _('Loading Map') ?> " + loading,
           "id"   : "loading_map",
           "style": style
        }).appendTo($map);
        
    }
    
    this.hide_loading = function()
    {
        $(_map_id).find('#loading_map').remove();
    }
    
    // Clear coordenates and address (Javascript object and inputs)
    this.reset_data = function()
    {        
        var lat_id     = this.get_lat_id();
        var lng_id     = this.get_lng_id();
        var country_id = this.get_country_id();
        var sl_id      = this.get_sl_id();
        var zoom_id    = this.get_zoom_id();
        
        $(lat_id).val('');
        $(lng_id).val('');
        $(country_id).val('');
        $(sl_id).val('');
            
        this.set_location(null, null);
        this.set_address('');
        this.remove_all_markers();
    };
    
    
    this.show_loading();
}

/****************************************************
 *************** Geolocation utilities **************
 ****************************************************/

var __maps_callback = null;

// Format coordenate (5 decimal)
Av_map.format_coordenate = function(coordenate)
{
    var c = '';
    
    if (!isNaN(parseFloat(coordenate)))
    {
        c = Math.round(coordenate*10000)/10000;
    }
    
    return c;
}


Av_map.is_map_available = function(callback)
{     
    __maps_callback = callback
    
    if (typeof is_internet_available == 'function')
    {
        if (is_internet_available() && (typeof(google) == 'undefined' || google == null))
        {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = "<?=Av_map_helper::getUrl()?>";
            document.body.appendChild(script);

            return false;
        }
    }

    Av_map.load_map_callback()

}

Av_map.load_map_callback = function()
{
    var load = false;
    
    if (typeof(google) != 'undefined' && google != null)
    {
        load = true;
    }

    if (typeof __maps_callback == 'function')
    {
        __maps_callback(load)
    }
}


