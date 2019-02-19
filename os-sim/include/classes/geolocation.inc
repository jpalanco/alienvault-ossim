<?php
/**
* geolocation.inc
*
* File geolocation.inc is used to:
*   - To manage host geolocation
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


require_once 'geoipcity.inc';
require_once 'classes/Security.inc';

/**
* Geolocation Class
*
* Class for managing host geolocation
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Geolocation 
{
    /**
    * Geolocation provider
    *
    * @var array
    * @access private
    */
    private $provider;
      
    /**
    * Country codes
    *
    * @var array
    * @access private
    */
    private $country_codes = array(
        'AC' => 'Ascension Island',
        'AD' => 'Andorra',
        'AE' => 'United Arab Emirates',
        'AF' => 'Afghanistan',
        'AG' => 'Antigua and Barbuda',
        'AI' => 'Anguilla',
        'AL' => 'Albania',
        'AM' => 'Armenia',
        'AN' => 'Netherlands Antilles',
        'AO' => 'Angola',
        'AQ' => 'Antarctica',
        'AR' => 'Argentina',
        'AS' => 'American Samoa',
        'AT' => 'Austria',
        'AU' => 'Australia',
        'AW' => 'Aruba',
        'AZ' => 'Azerbaijan',
        'BA' => 'Bosnia and Herzegovina',
        'BB' => 'Barbados',
        'BD' => 'Bangladesh',
        'BE' => 'Belgium',
        'BF' => 'Burkina Faso',
        'BG' => 'Bulgaria',
        'BH' => 'Bahrain',
        'BI' => 'Burundi',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BN' => 'Brunei Darussalam',
        'BO' => 'Bolivia',
        'BR' => 'Brazil',
        'BS' => 'Bahamas',
        'BT' => 'Bhutan',
        'BV' => 'Bouvet Island',
        'BW' => 'Botswana',
        'BY' => 'Belarus',
        'BZ' => 'Belize',
        'CA' => 'Canada',
        'CC' => 'Keeling Islands',
        'CF' => 'Central African Republic',
        'CG' => 'Congo',
        'CH' => 'Switzerland',
        'CI' => 'Ivory Coast',
        'CK' => 'Cook Islands',
        'CL' => 'Chile',
        'CM' => 'Cameroon',
        'CN' => 'China',
        'CO' => 'Colombia',
        'CR' => 'Costa Rica',
        'CU' => 'Cuba',
        'CV' => 'Cape Verde',
        'CX' => 'Christmas Island',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DE' => 'Germany',
        'DJ' => 'Djibouti',
        'DK' => 'Denmark',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'DZ' => 'Algeria',
        'EC' => 'Ecuador',
        'EE' => 'Estonia',
        'EG' => 'Egypt',
        'EH' => 'Western Sahara',
        'ER' => 'Eritrea',
        'ES' => 'Spain',
        'ET' => 'Ethiopia',
        'EU' => 'Europe',
        'FI' => 'Finland',
        'FJ' => 'Fiji',
        'FK' => 'Falkland Islands (Malvinas)',
        'FM' => 'Micronesia',
        'FO' => 'Faroe Islands',
        'FR' => 'France',
        'FX' => 'France, Metropolitan',
        'GA' => 'Gabon',
        'GB' => 'United Kingdom',
        'GD' => 'Grenada',
        'GE' => 'Georgia',
        'GF' => 'French Guiana',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GL' => 'Greenland',
        'GM' => 'Gambia',
        'GN' => 'Guinea',
        'GP' => 'Guadeloupe',
        'GQ' => 'Equatorial Guinea',
        'GR' => 'Greece',
        'GS' => 'S. Georgia and S. Sandwich Isls.',
        'GT' => 'Guatemala',
        'GU' => 'Guam',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HK' => 'Hong Kong',
        'HM' => 'Heard and McDonald Islands',
        'HN' => 'Honduras',
        'HR' => 'Croatia',
        'HT' => 'Haiti',
        'HU' => 'Hungary',
        'ID' => 'Indonesia',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IN' => 'India',
        'IO' => 'British Indian Ocean Territory',
        'IQ' => 'Iraq',
        'IR' => 'Iran',
        'IS' => 'Iceland',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JO' => 'Jordan',
        'JP' => 'Japan',
        'KE' => 'Kenya',
        'KG' => 'Kyrgyzstan',
        'KH' => 'Cambodia',
        'KI' => 'Kiribati',
        'KM' => 'Comoros',
        'KN' => 'Saint Kitts and Nevis',
        'KP' => 'North Korea',
        'KR' => 'South Korea',
        'KW' => 'Kuwait',
        'KY' => 'Cayman Islands',
        'KZ' => 'Kazakhstan',
        'LA' => 'Laos',
        'LB' => 'Lebanon',
        'LC' => 'Saint Lucia',
        'LI' => 'Liechtenstein',
        'LK' => 'Sri Lanka',
        'LR' => 'Liberia',
        'LS' => 'Lesotho',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'LY' => 'Libya',
        'MA' => 'Morocco',
        'MC' => 'Monaco',
        'MD' => 'Moldova',
        'MG' => 'Madagascar',
        'MH' => 'Marshall Islands',
        'MK' => 'Macedonia',
        'ML' => 'Mali',
        'MM' => 'Myanmar',
        'MN' => 'Mongolia',
        'MO' => 'Macau',
        'MP' => 'Northern Mariana Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MS' => 'Montserrat',
        'MT' => 'Malta',
        'MU' => 'Mauritius',
        'MV' => 'Maldives',
        'MW' => 'Malawi',
        'MX' => 'Mexico',
        'MY' => 'Malaysia',
        'MZ' => 'Mozambique',
        'NA' => 'Namibia',
        'NC' => 'New Caledonia',
        'NE' => 'Niger',
        'NF' => 'Norfolk Island',
        'NG' => 'Nigeria',
        'NI' => 'Nicaragua',
        'NL' => 'Netherlands',
        'NO' => 'Norway',
        'NP' => 'Nepal',
        'NR' => 'Nauru',
        'NT' => 'Neutral Zone (Saudia Arabia/Iraq)',
        'NU' => 'Niue',
        'NZ' => 'New Zealand',
        'OM' => 'Oman',
        'PS' => 'Palestine',
        'PA' => 'Panama',
        'PE' => 'Peru',
        'PF' => 'French Polynesia',
        'PG' => 'Papua New Guinea',
        'PH' => 'Philippines',
        'PK' => 'Pakistan',
        'PL' => 'Poland',
        'PM' => 'St. Pierre and Miquelon',
        'PN' => 'Pitcairn',
        'PR' => 'Puerto Rico',
        'PT' => 'Portugal',
        'PW' => 'Palau',
        'PY' => 'Paraguay',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'SA' => 'Saudi Arabia',
        'SB' => 'Solomon Islands',
        'SC' => 'Seychelles',
        'SD' => 'Sudan',
        'SE' => 'Sweden',
        'SG' => 'Singapore',
        'SH' => 'St. Helena',
        'SI' => 'Slovenia',
        'SJ' => 'Svalbard and Jan Mayen Islands',
        'SK' => 'Slovakia',
        'SL' => 'Sierra Leone',
        'SM' => 'San Marino',
        'SN' => 'Senegal',
        'SO' => 'Somalia',
        'SR' => 'Suriname',
        'ST' => 'Sao Tome and Principe',
        'SU' => 'Soviet Union',
        'SV' => 'El Salvador',
        'SY' => 'Syria',
        'SZ' => 'Swaziland',
        'TC' => 'Turks and Caicos Islands',
        'TD' => 'Chad',
        'TF' => 'French Southern Territories',
        'TG' => 'Togo',
        'TH' => 'Thailand',
        'TJ' => 'Tajikistan',
        'TK' => 'Tokelau',
        'TM' => 'Turkmenistan',
        'TN' => 'Tunisia',
        'TO' => 'Tonga',
        'TP' => 'East Timor',
        'TR' => 'Turkey',
        'TT' => 'Trinidad and Tobago',
        'TV' => 'Tuvalu',
        'TW' => 'Taiwan',
        'TZ' => 'Tanzania',
        'UA' => 'Ukraine',
        'UG' => 'Uganda',
        'UK' => 'United Kingdom',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VA' => 'Vatican City State',
        'VC' => 'Saint Vincent and The Grenadines',
        'VE' => 'Venezuela',
        'VG' => 'Virgin Islands',
        'VI' => 'Virgin Islands',
        'VN' => 'Viet Nam',
        'VU' => 'Vanuatu',
        'WF' => 'Wallis and Futuna Islands',
        'WS' => 'Samoa',
        'YE' => 'Yemen',
        'YT' => 'Mayotte',
        'YU' => 'Yugoslavia',
        'ZA' => 'South Africa',
        'ZM' => 'Zambia',
        'ZR' => 'Zaire',
        'ZW' => 'Zimbabwe'
    );
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $provider_path  [Optional] Geolocation provider path
    */  
    public function __construct($provider_path = '/usr/share/geoip/GeoIP.dat') 
    {        
        $this->set_provider($provider_path);             
    }
    
    
    /**
    * Class destructor
    *
    * This function destructs the class
    */  
    public function __destruct()
    {
        $this->close();        
    }
    
    
    /**
    * This function returns the file descriptor 
    *
    * @return resource
    */      
    private function get_fd()
    {
        return $this->provider['fd'];
    }
    
    
    /**
    * This function returns true if geolocation provider has city information
    *
    * @return boolean
    */      
    private function has_city_info()
    {
        return $this->provider['has_city_info'];
    }
    
    /**
    * This function sets geolocation provider
    *
    * @param string  $provider_path  Geolocation provider path
    *
    * @throws Exception  If a connection error occurred    
    *
    * @return void
    */      
    public function set_provider($provider_path)
    {
        if (file_exists($provider_path))
        {
            $fd = @geoip_open($provider_path, GEOIP_STANDARD);
            
            if ($fd !== FALSE)
            {
                $this->provider = array(
                    'file'          => $provider_path,
                    'fd'            => $fd,
                    'has_city_info' => (preg_match('/City/i', $provider_path)) ? TRUE : FALSE
                );  
            }
            else
            {
                $exp_msg = _('Unable to get geolocation data');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        else
        {
            $exp_msg = _('Geolocation provider not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function returns the country name from code
    *
    * @param string  $code  Country code
    *
    * @return string
    */  
    public function get_country_name($code) 
    {
        $country_name = '';
        
        if (array_key_exists($code, $this->country_codes))
        {
            $country_name = $this->country_codes[$code];
        }
                
        return $country_name;
    }
        
    
    /**
    * This function returns the country (code and name) by host
    *
    * @param string   $conn    Database access object
    * @param string   $host    Host IP|Hostname
    * @param string   $id      [Optional] Host ID
    *
    * @throws Exception  If a connection error occurred   
    *
    * @return string
    */
    public function get_country_by_host($conn, $host, $id = '')
    {
        Ossim_db::check_connection($conn);
        
        $country = array('', '');
        
        //First, we try to get country from database    
        if ($id != '')
        {
            $data = $this->get_location_from_db($conn, $id);
            
            $code = $data['country']['code'];
            $name = $data['country']['name'];
            
            if ($code != '')
            {                                
                return array($code, $name);
            }
        }      
        
        if ($host != '')
        {          
            $country = $this->get_country_from_file($host);           
        }    
                     
        return $country;                     
    }
    
                    
    /**
    * This function returns the country code by host
    *
    * @param string   $conn    Database access object
    * @param string   $host    Host IP|Hostname
    * @param string   $id      [Optional] Host ID
    *
    * @throws Exception  If a connection error occurred   
    *
    * @return string
    */
    public function get_country_code_by_host($conn, $host, $id = '')
    {
        Ossim_db::check_connection($conn);
        
        $code = '';
        
        //First, we try to get country code from database    
        if ($id != '')
        {
            $data = $this->get_location_from_db($conn, $id);
            
            $code = $data['country']['code'];
            
            if ($code != '')
            {
                return $code;
            }
        }      
        
        if ($host != '')
        {
            $code = $this->get_country_code_from_file($host);
        }    
                     
        return $code;                     
    }
    
    
    /**
    * This function returns the country name by host (Host IP|Hostname)
    *
    * @param string  $conn   Database access object
    * @param string  $host   Host IP|Hostname
    * @param string  $id     [Optional] Host ID
    *
    * @throws Exception  If there is a connection error    
    *
    * @return string
    */
    public function get_country_name_by_host($conn, $host, $id = '')
    {
        Ossim_db::check_connection($conn);
        
        $name = '';
        
        //First, we try to get country name from database 
        if ($id != '')
        {
            $data = $this->get_location_from_db($conn, $id);
            
            $name = $data['country']['name'];
            
            if ($name != '')
            {
                return $name;
            }
        }
        
        if ($host != '')
        {
            $name = $this->get_country_name_from_file($host);
        }            
      
        return $name; 
    }
     
     
    /**
    * This function returns organization information (Enterprise, ISP provider, ...) 
    * from geolocation file by host (IP or hostname)
    *
    * @param string  $host  Host IP|Hostname
    *
    * @return string
    */ 
    public function get_organization_by_host($host) 
    {
        return geoip_org_by_addr($this->get_fd(), $host);
    }    
    
    
    /**
    * This function closes geolocation file
    *
    * @return boolean
    */ 
    public function close() 
    {
        return @geoip_close($this->get_fd());
    }
    
    
    
    /*************************************************        
     **************** GeoIP functions ****************      
     *************************************************/
   
    
        
    /**
    * This function returns geolocation info (latitude, longitude and country (code and name)) 
    * from geolocation file by host (IP or hostname)
    *
    * @param string  $host  Host IP|Hostname
    *
    * @return object
    */ 
    public function get_location_from_file($host) 
    {
        return geoip_record_by_addr($this->get_fd(), $host);
    }
    
    
    /**
    * This function returns the country code from geolocation file
    *
    * @param string  $host  Host IP|Hostname
    *
    * @return string
    */
    public function get_country_code_from_file($host) 
    {
        if ($this->has_city_info())
        {
            $record = $this->get_location_from_file($host);
            $code   = $record->country_code; 
        }
        
        if ($code != '')
        {
            return $code;
        }
        
        return geoip_country_code_by_addr($this->get_fd(), $host);        
    }
    
    
    /**
    * This function returns the country name from geolocation file
    *
    * @param string  $host  Host IP|Hostname
    *
    * @return string
    */
    public function get_country_name_from_file($host) 
    {
        if ($this->has_city_info())
        {
            $record = $this->get_location_from_file($host);
            $name   = $record->country_name; 
        }
        
        if ($name != '')
        {
            return $name;
        }
        
        return geoip_country_name_by_addr($this->get_fd(), $host);     
    }
    
    
    /**
    * This function returns the country (name and code) from geolocation file
    *
    * @param string  $host  Host IP|Hostname
    *
    * @return string
    */
    public function get_country_from_file($host) 
    {                
        if ($this->has_city_info())
        {
            $record = $this->get_location_from_file($host);
            
            $code = $record->country_code;
            $name = $record->country_name;
        }
        
        if ($code != '')
        {
            return array($code, $name);
        }
        
        $code = geoip_country_code_by_addr($this->get_fd(), $host); 
        $name = geoip_country_name_by_addr($this->get_fd(), $host);
                    
        return array($code, $name);
    }
    
    
    
    /*************************************************        
     ************** Database functions ***************      
     *************************************************/
        
    
    /**
    * This function returns geolocation info (Latitude, longitude and country (code and name)) 
    * from database
    *
    * @param string   $conn     Database access object
    * @param string   $host_id  Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */   
    public function get_location_from_db($conn, $host_id)
    {        
        Ossim_db::check_connection($conn);
        
        $data = array(
            'lat',
            'lon',
            'country' => array(
                'code',
                'name'
            )
        );     
               
        $query   = "SELECT INET6_ATON(hi.ip) AS ip, h.lat AS lat, h.lon AS lon, 
                REPLACE(SUBSTRING(SUBSTRING_INDEX(h.country, ';', 1), LENGTH(SUBSTRING_INDEX(h.country, ';', 1 - 1)) + 1), ';', '') code, 
                REPLACE(SUBSTRING(SUBSTRING_INDEX(h.country, ';', 2), LENGTH(SUBSTRING_INDEX(h.country, ';', 2 - 1)) + 1), ';', '') name   
            FROM host h, host_ip hi 
            WHERE h.id = hi.host_id 
            AND h.id = UNHEX(?) 
            AND h.external_host = 1";

        
        $params = array($host_id);
        
        $rs = $conn->Execute($query, $params);		
			
		if (!$rs) 
		{			
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}		
		
		if (!$rs->EOF) 
        {
            $cnd_1 = ($rs->fields['lat'] != '' && $rs->fields['lon'] != '');
            $cnd_2 = ($rs->fields['code'] != '' && $rs->fields['name'] != '');
            
            if ($cnd_1 || $cnd_2)
            {
                $lat   = $rs->fields['lat'];
                $lon   = $rs->fields['lon'];
                $lon   = explode(';', $lon);
                $lon   = $lon[0];
                
                $code  = $rs->fields['code'];
                $name  = $rs->fields['name'];
                                
                $data = array(
                    'lat' => $lat,
                    'lon' => $lon,
                    'country' => array(
                        'code' => $code,
                        'name' => $name
                    )
                );  
            }   
        }
        
        return $data;	
    }

     
    
    /*************************************************        
     ************ Google Maps functions **************      
     *************************************************/
    
    
    
    /**
    * This function returns country info (code and name) by latitude and longitude 
    *
    * @param string  $lat   Latitude
    * @param string  $lon   Longitude
    *
    * @return array
    */   
    public function get_country_by_coordinates($lat, $lon)
    {        
        $code = '';
        $name = '';
        
        $lon  = explode(';', $lon);
        $lon  = $lon[0];

        $s_context = stream_context_create(array(
            'http' => array(
                'timeout' => 5      // Timeout in seconds
            )
        ));

        $json = @file_get_contents('https://maps.google.com/maps/api/geocode/json?latlng='.$lat.','.$lon.'&sensor=false&language=EN', 0, $s_context);

        $decoded_json = json_decode($json, TRUE);
        
        if (json_last_error() == JSON_ERROR_NONE)
        {
            $index = count($decoded_json['results'])-1;
            
            $name = $decoded_json['results'][$index]['address_components'][0]['long_name'];
            $code = $decoded_json['results'][$index]['address_components'][0]['short_name'];
        }
                       
        return array($code, $name);
    }
   
    
    /**
    * This function returns country coordinates (latitude and longitude)
    *
    * @param string  $country  Country name
    *
    * @return array
    */   
    public function get_coordinates_by_country($country)
    {    
        $lat = '';
        $lon = '';
        
        $s_context = stream_context_create(array(
            'http' => array(
                'timeout' => 5      // Timeout in seconds
            )
        ));

        $json = @file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$country.'&sensor=false&language=EN', 0, $s_context);

        $decoded_json = json_decode($json, TRUE);
        
        if (json_last_error() == JSON_ERROR_NONE)
        {
            $index = count($decoded_json['results'])-1;
            
            $lat = $decoded_json['results'][$index]['geometry']['location']['lat'];
            $lon = $decoded_json['results'][$index]['geometry']['location']['lng'];
        }
    
        return array($lat, $lon);
    }   
}

/* End of file geolocation.inc */
/* Location: ../include/classes/geolocation.inc */ 
