<?php
/**
* Reputation.inc
*
* File Reputation.inc is used to:
*   - To retrieve reputation info
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
* @package    ossim-framework\Reputation
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Reputation
*
* Class for managing hosts
*
* @package    ossim-framework\Reputation
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*
*
* Usage:
*      list($ips, $cou, $order, $last) = get_data($rep_file [ , $filter ]);
*
*/

class Reputation
{
    /**
    * Reputation file
    *
    * @var string
    * @access public
    */
    public $rep_file;


    /**
    * Stat file
    *
    * @var string
    * @access public
    */
    public $rep_file_stats;


    /**
    * Cache to save reputation data
    *
    * @var array
    * @access public
    */
    public $rep_cache = array();


    /**
    * Max IPs by activity
    *
    * @var integer
    * @access public
    */
    public $limit = 50000;


    /**
    * Country codes
    *
    * @var array
    * @access public
    */
    public $country_codes = array(
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
    * @param string  $file   [Optional] Reputation file
    *
    */
    public function __construct($file = '')
    {
        if (isset($file) && trim($file) != '')
        {
            $this->rep_file = trim($file);
        }
        else
        {
            $this->rep_file = trim(Util::execute_command("grep 'reputation' /etc/ossim/server/config.xml | perl -npe 's/.*\"(.*)\".*/$1/'", FALSE, 'string'));
        }
        
        $this->rep_file_stats = $this->rep_file.'.stats';
	
        if (!$this->existReputation())
        {
            @touch($this->rep_file);
        }
        
        if (!$this->existReputationStats())
        {
            @touch($this->rep_file_stats);
        }
    }


    /**
    * This function checks if the file (reputation.data) exists
    *
    * @return boolean
    */  
    public function existReputation()
    {
        return file_exists($this->rep_file);
    }
    
    
    /**
    * This function checks if the file (reputation.data.stats) exists
    *
    * @return boolean
    */  
    public function existReputationStats()
    {
        return file_exists($this->rep_file_stats);
    }


    /**
    * This function checks if file with stats is updated
    *
    * @return boolean
    */ 
    public function upgradeStats()
    {        
        //If the reputation stats file is empty
        $empty_stats = filesize($this->rep_file_stats) == 0;
        //If the reputation stats date is lower or equal to the reputation file date
        $diff_dates  = filemtime($this->rep_file_stats) <= filemtime($this->rep_file);
        
        return $empty_stats || $diff_dates;
    }


    /**
    * This function saves reputation data in cache (To improve access to data)
    *    
    * @return boolean
    */
    private function _save_data_in_cache()
    {
        // Caching data
        $frep = fopen($this->rep_file, 'r');

        if ($frep === FALSE)
        {
            return FALSE;
        }

        while (!feof($frep))
        {
            $line = trim(fgets($frep));

            if (preg_match('/(\d+\.\d+\.\d+\.\d+)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)/', $line, $fnd))
            {
                $this->rep_cache[$fnd[1]] = array($fnd[5], $fnd[6], $fnd[7]);
            }
        }

        fclose($frep);

        return TRUE;
    }


    /**
    * This function returns the coordinates from IP
    *
    * @param string  $ip   IP address
    *
    * @return array
    */
    public function get_latlng_by_ip($ip)
    {
        $res = array('', '', '');
        
        if (empty($this->rep_cache))
        {
            $this->_save_data_in_cache();
        }

        if ($this->existReputation() && Asset_host_ips::valid_ip($ip) && is_array($this->rep_cache[$ip]))
        {
            $res = $this->rep_cache[$ip];
        }

        return $res;
    }


    /**
    * This function returns the reputation data from IP
    *
    * @param string  $ip   IP address
    *
    * @return array
    */
    public function get_data_by_ip($ip)
    {
        if (!$this->existReputation())
        {
            return array('', '', '');
        }

        $params = array("^$ip#", $this->rep_file);
        $line   = Util::execute_command('grep ? ?', $params, 'string');

        if (preg_match('/(\d+\.\d+\.\d+\.\d+)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)/', trim($line), $fnd))
        {
            return array($fnd[2], $fnd[3], $fnd[4]);
        }

        return array('', '', '');
    }


    /**
    * This function returns all reputation data (BBDD or reputation file)
    *
    * @param integer   $type        [Optional] Source data
    * @param string    $filter      [Optional] SIEM filter
    * @param boolean   $in_session  [Optional] Save data in session
    *
    * @return array
    */
    public function get_data($type = 0, $filter = 'All', $in_session = TRUE)
    {
        if (!$this->existReputation())
        {
            return array(array(), array(), array(), array());
        }

        if ($type == 1)
        {
            return $this->_get_data_from_siem($filter, $in_session);
        }
        else
        {
            return $this->_get_data_from_file();
        }
    }


    /**
    * This function returns all reputation data from SIEM DB
    *    
    * @param string    $filter      [Optional] SIEM filter
    * @param boolean   $in_session  [Optional] Save data from session
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    private function _get_data_from_siem($filter = 'All', $in_session = TRUE)
    {
        $ips   = array();
        $order = array();
        $cou   = array();
        $last  = array();

        if (isset($_SESSION["_repinfodb_$filter"]) && is_array($_SESSION["_repinfodb_$filter"]))
        {
            return $_SESSION["_repinfodb_$filter"];
        }

        $db     = new ossim_db();
        $conn   = $db->connect();
        
        $geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

        $filter_src = ($filter != 'All') ? " AND i.rep_act_src='$filter'" : " AND i.rep_act_src <> ''";
        $filter_dst = ($filter != 'All') ? " AND i.rep_act_dst='$filter'" : " AND i.rep_act_dst <> ''";

        $query = ossim_query("SELECT DISTINCT e.ip_src as ip, i.rep_act_src as act FROM alienvault_siem.acid_event e, alienvault_siem.reputation_data i
            WHERE i.event_id=e.id $filter_src 
            UNION SELECT DISTINCT e.ip_dst as ip, i.rep_act_dst as act FROM alienvault_siem.acid_event e, alienvault_siem.reputation_data i
            WHERE i.event_id=e.id $filter_dst");


        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ip = inet_ntop($rs->fields['ip']);

            if (empty($last[$ip]))
            {
                $iplatlon = $this->get_latlng_by_ip($ip);
                
                if ($iplatlon[$ip][0] != '')
                {
                    $ccode  = $iplatlon[$ip][0];
                    $city   = $iplatlon[$ip][1];
                    $coord  = $iplatlon[$ip][2];
                    if ($this->country_codes[$ccode] != '')
                    {
                        $ccode = $this->country_codes[$ccode];
                    }
                    
                    if ($ccode != '')
                    {
                        $cou[$ccode.';'.strtoupper($iplatlon[$ip][0])]++;
                    }
                }
                else 
                {
                    $record = $geoloc->get_location_from_file($ip);
                    $ccode  = strtoupper($record->country_code);
                    $city   = $record->city;
                    $coord  = $record->latitude.','.$record->longitude;
                    
                    if ($this->country_codes[$ccode] != '')
                    {
                        $ccode = $this->country_codes[$ccode];
                    }

                    if ($ccode != '')
                    {
                        $cou[$ccode.';'.strtoupper($record->country_code)]++;
                    }
                }

                $acts  = explode(';', $rs->fields['act']);
                foreach ($acts as $act) 
                {
                    $act = ucfirst(trim($act));
                    $ips[$act][$ip] = ($coord != ',') ? $coord : (($city != '') ? $city.', '.$ccode : $ccode);
                    $order[$act]++;
                }
            }

            $last[$ip]++;

            $rs->MoveNext();
        }


        arsort($order);
        arsort($cou);

        $geoloc->close();
        $db->close();
        
        if ($in_session)
        {
            $_SESSION["_repinfodb_$filter"] = array($ips, $cou, $order, count($last));
            
            return $_SESSION["_repinfodb_$filter"];
        }
        
        return array($ips, $cou, $order, count($last));
    }


    /**
    * This function returns all reputation data from reputation file
    *
    * @return array
    */
    private function _get_data_from_file()
    {
        $ips   = array();
        $order = array();
        $cou   = array();
        $last  = 0;

        if ($this->upgradeStats())
        {
            /*
            Format: 
              ip             #reliability #value #act1;ac2 #country_code #city      #lat,lon
              116.231.123.86 #5           #3     #C&C      #CN           #Shanghai  #31.0049991608,121.408599854
            */
            $frep = fopen($this->rep_file, 'r');

            while (!feof($frep))
            {
                $line = trim(fgets($frep));

                if (preg_match('/(\d+\.\d+\.\d+\.\d+)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)#([^#]*)/', $line, $fnd))
                {
                    $fnd[8] = $fnd[5];
                    if ($this->country_codes[$fnd[5]] != '')
                    { 
                        $fnd[5] = $this->country_codes[$fnd[5]];
                    }

                    if ($fnd[5] != '') 
                    {
                        $cou[$fnd[5].';'.$fnd[8]]++;
                    }

                    $last++; 

                    $acts = explode(';', $fnd[4]);

                    foreach ($acts as $act)
                    {
                        $act = ucfirst(trim($act));
                        
                        if (count($ips[$act]) < $this->limit)
                        {
                            $ips[$act][$fnd[1]] = ($fnd[7] != '') ? $fnd[7] : (($fnd[6] != '') ? $fnd[6].', '.$fnd[5] : $fnd[5]);
                        }
                        
                        $order[$act]++;
                    }
                }
            }

            fclose($frep);
            arsort($order);
            arsort($cou);

            file_put_contents($this->rep_file_stats, json_encode(array($ips, $cou, $order, $last)));

            return array($ips, $cou, $order, $last);
        }

        $rep_data = json_decode(file_get_contents($this->rep_file_stats), TRUE);
        
        return (empty($rep_data)) ? array($ips, $cou, $order, $last) : $rep_data;
    }


    /**
    * This function returns the reputation link with information about the IP
    *
    * @param string  $ip   IP address
    *    
    * @return string
    */
    public static function getlabslink($ip)
    {
        // OLD LINK  http://www.alienvault.com/apps/rep_monitor/ip/usm/$ip/
        return Otx::OTX_URL . "indicator/ip/$ip/";
    }


    /**
    * This function returns HTML information (images, links, ...) about reputation
    *
    * @param integer  $prio   Priority
    * @param integer  $rel    Reliability
    * @param string   $act    Activity
    * @param string   $ip     [Optional] IP address
    *
    * @return string
    */
    public static function getrepimg($prio, $rel, $act, $ip = '')
    {
        if (intval($prio) == 0)
        {
            return '';
        }

        if ($ip != '')
        {
            $link   = self::getlabslink($ip);
            $target = "target='lab'";
        }
        else
        {
            $link   = 'javascript:;';
            $target = '';
        }

        $reptxt = _('IP Priority').": <img src='".AV_MAIN_PATH."/forensics/bar2.php?value=".intval($prio).
                  "&max=9&range=1' border='0' align='absmiddle'  style='width:14mm'><br/>"._('IP Reliability').
                  ": <img src='".AV_MAIN_PATH."/forensics/bar2.php?value=".intval($rel).
                  "&max=9' border='0' align='absmiddle' style='width:14mm'><br/>".
                  _('IP Activity').': <b>'.Util::htmlentities(str_replace(';', ', ', $act)).'</b>';

        $reptxt .= "<p style='margin:0px;text-align:right;'><strong>"._('Click - More Info').'</strong></p>';

        $lnk = "<a $target href='$link' class='stop repinfo' style='text-decoration:none' txt=\"".
               $reptxt."\"><img style='margin:0px 2px 1px 2px' border='0' align='absmiddle'";
        
        if ($prio <= 2)
        {
            $lnk .= " src='".AV_MAIN_PATH."/reputation/images/green.png'";
        }

        elseif ($prio <= 6)
        {
            $lnk .= " src='".AV_MAIN_PATH."/reputation/images/yellow.png'";
        }

        else
        {
            $lnk .= " src='".AV_MAIN_PATH."/reputation/images/red.png'";
        }

        return $lnk.'/></a>';
    }


    /**
    * This function returns a tooltip with reputation information
    *
    * @param integer  $prio    Priority
    * @param integer  $rel     Reliability
    * @param string   $act     Activity
    * @param string   $plain   [Optional] URL
    *
    * @return string
    */ 
    public static function getreptooltip($prio, $rel, $act, $plain = '')
    {
        if (intval($prio) == 0)
        {
            return '';
        }

        if ($plain != '')
        {
              $reptxt = "<a href='$plain' target='_blank'><b>".Util::htmlentities(str_replace(';', ', ', $act)).
                        '</b></a> [ '._('Prio').": <img src='../forensics/bar2.php?value=".intval($prio).
                        "&max=9&range=1' border='0' align='absmiddle' style='width:14mm'> &nbsp; "._('Rel').
                        ": <img src='../forensics/bar2.php?value=".intval($rel)."&max=9' border='0' align='absmiddle' style='width:14mm'> ]";
        }

        else 
        {
              $reptxt = _('IP Priority').": <img src='../forensics/bar2.php?value=".intval($prio).
                        "&max=9&range=1' border='0' align='absmiddle'  style='width:14mm'><br/>"._('IP Reliability').
                        ": <img src='../forensics/bar2.php?value=".intval($rel)."&max=9' border='0' align='absmiddle' style='width:14mm'><br/>".
                        _('IP Activity').': <b>'.Util::htmlentities(str_replace(';', ', ', $act)).'</b>';
              
              $reptxt .= "<p style='margin:0px;text-align:right;'><strong>"._('Click - More Info').'</strong></p>';
        }

        return $reptxt;
    }


    /**
    * This function only returns the reputation image related to the priority
    *
    * @param integer  $prio   Priority
    * @param integer  $act    Activity
    *
    * @return string
    */
    public static function getreponlyimg($prio, $act, $without_title=FALSE)
    {
        if (intval($prio) == 0) 
        {
            return '';
        }

        $lnk = ($without_title) ? "<img align='absmiddle' style='margin:0px 5px 0px 0px' border='0'" : "<img align='absmiddle' style='margin:0px 5px 0px 0px' border='0' alt='".trim($act)."' title='".trim($act)."'";

        if ($prio <= 2)
        {
            $lnk .= " src='../reputation/images/green.png'";
        }

        elseif ($prio <= 6)
        {
            $lnk .= " src='../reputation/images/yellow.png'";
        }

        else
        {
            $lnk .= " src='../reputation/images/red.png'";
        }

        return $lnk.'/>';
    }


    /**
    * This function returns the background color related to the priority
    *
    * @param integer  $prio   Priority
    * @param integer  $where  [Optional] To distinguish the place where color will be displayed
    *
    * @return string
    */
    public static function getrepbgcolor($prio, $where = 1)
    {
        if ($where == 1)
        {
            if (intval($prio) == 0)
            {
                return '';
            }

            if ($prio <= 2)
            {
                return 'background-color:#fcefcc;';
            }

            elseif ($prio <= 6)
            {
                return 'background-color:#fde5d6;';
            }

            else
            {
                return 'background-color:#fccece;';
            }
        }
        else
        {
            if (intval($prio) == 0)
            { 
                return 'transparent';
            }

            if ($prio <= 2)
            {
                return '#fcefcc';
            }

            elseif ($prio <= 6)
            {
                return '#fde5d6';
            }

            else
            {
                return '#fccece';
            }
        }
    }


    /**
    * This function returns all reputation activities
    *
    * @param object   $conn        Database access object
    * @param string   $args        [Optional] SQL filter
    * @param integer  $cache_time  [Optional] Cache time
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_reputation_activities($conn, $args = '', $cache_time = 0)
    {
        $activities = array();
        $query = ossim_query("SELECT * FROM reputation_activities WHERE 1=1 $args");

        $rs = ($cache_time > 0) ? $conn->CacheExecute($cache_time, $query) : $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $activities[$rs->fields['id']] = $rs->fields['descr'];
            
            $rs->MoveNext();
        }

        return $activities;
    }
    
    
    /**
    * This function flush the reputation from session
    *
    * @return void
    */
    public static function flush_raputation_from_session()
    {
        foreach ($_SESSION as $k => $v) 
        {
            if (preg_match("/^_repinfodb/",$k)) 
            {
                unset($_SESSION[$k]);
            }
        }
    }
    
    
    
    
}

/* End of file Reputation.inc */
/* Location: ../include/classes/Reputation.inc */
