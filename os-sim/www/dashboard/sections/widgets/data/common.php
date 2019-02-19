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

/**
 * Function: SIEM_trends_week
 * 		Get the SIEM trends in days
 *
 * Author: XXX
 * Creation Date: XXX
 * Modification Date: 14/03/2012 - Javier Martinez Navajas (fjmnav@alienvault.com)
 *
 *
 * A more detailed description goes here. All these descriptions must be full filled properly.
 *
 *
 * @param $param
 *   To do...
 *		
 * @param $d
 *   Number of days of the trend.
 *
 * @return
 *   An array with the result of the query that contains the data of the trend
 *
 */		 
function SIEM_trends_week($param = '', $d = 7, $assets_filters = '') 
{
	global $tz;
	
	$db           = new ossim_db(TRUE);
	$dbconn       = $db->connect();
	$tzc          = Util::get_tzc($tz);
	$data         = array();
	$plugins      = '';
	$plugins_sql  = '';
	$tax_join     = '';
	
	if (preg_match("/taxonomy\=(.+)/", $param, $found))
	{
		if ($found[1] == 'honeypot')
		{
			$tax_join = "alienvault.plugin_sid p, ";
			$tax_where = "AND acid_event.plugin_id = p.plugin_id AND acid_event.plugin_sid = p.sid AND p.category_id=19";
		}
		
		$param = '';
	} 
	elseif ($param != '') 
	{
		$plugins_sql   = "AND acid_event.plugin_id between " . OSSEC_MIN_PLUGIN_ID . " AND " . OSSEC_MAX_PLUGIN_ID;
	}
	
	//Filters of assets.
	if(empty($assets_filters))
	{
		$assets_filters['assets'] = array();
		$assets_filters['ctxs']   = array();
	}
	
	$query_where = Security_report::make_where($dbconn, gmdate("Y-m-d 00:00:00",gmdate("U")-86400*$d), gmdate("Y-m-d 23:59:59"), array(), $assets_filters);
	
	
	$sqlgraph = "SELECT SUM(acid_event.cnt) AS num_events, day(convert_tz(timestamp,'+00:00','$tzc')) AS intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) AS suf 
        FROM $tax_join alienvault_siem.ac_acid_event as acid_event
        WHERE 1=1 $plugins_sql $query_where $tax_where 
        GROUP BY suf, intervalo 
        ORDER BY suf, intervalo";
    
    $rg = $dbconn->CacheExecute($sqlgraph);
    
	if (!$rg)
	{
	    print $dbconn->ErrorMsg();
	} 
	else 
	{
	    while (!$rg->EOF) 
	    {
	        $hours = $rg->fields['intervalo'].' '.substr($rg->fields['suf'], 0, 3);
	        $data[$hours] = $rg->fields['num_events'];
	        
	        $rg->MoveNext();
	    }
	}
	$db->close();
	
    if ($param != '')
    {
        $data = array($data, $plugins);
    }
    
	return $data;
}


/**
 * This function gets the SIEM trends in hours
 *
 * @param  $h                Number of hours of the trend
 * @param  $assets_filters   [Optional] Asset filter applied
 *
 * @return  An array with the result of the query that contains the data of the trend
 */
function SIEM_trends($h = 24, $assets_filters = '', $first_date = '')
{
    global $tz;

    //Cache file
    $file  = '_siem_events_' . Session::get_session_user() . '_';
    $file .= md5($h . '_' . serialize($assets_filters));

    $data  = Cache_file::get_asset_data($file, 300);

    if (is_array($data))
    {
        return $data;
    }

    $db     = new ossim_db(TRUE);
    $dbconn = $db->connect();

    $tzc    = Util::get_tzc($tz);
    $data   = array();

    //Filters of assets
    if(empty($assets_filters))
    {
        $assets_filters['assets'] = array();
        $assets_filters['ctxs']   = array();
    }

    $query_where  = Security_report::make_where($dbconn, gmdate("Y-m-d H:00:00",gmdate("U")-(3600*$h)), gmdate("Y-m-d H:59:59"), array(), $assets_filters);
    
    $sqlgraph     = "SELECT SUM(cnt) AS num_events, hour(convert_tz(timestamp,'+00:00','$tzc')) AS intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) AS suf 
        FROM alienvault_siem.ac_acid_event as acid_event WHERE 1=1 $query_where GROUP BY suf,intervalo";

    if ($first_date)
    {
        // Test if we have enough data in ac_acid_event
        $query = "select cnt from alienvault_siem.ac_acid_event where timestamp between '$first_date:00:00' and '$first_date:59:59' limit 1";
        $rg = $dbconn->CacheExecute($query);
        if (!$rg)
        {
            print $dbconn->ErrorMsg();
        }
        if ($rg->EOF)
        {
            // Test if we have enough data in acid_event
            $query = "select hex(id) from alienvault_siem.acid_event where timestamp between '$first_date:00:00' and '$first_date:59:59' limit 1";
            $rg = $dbconn->CacheExecute($query);
            if (!$rg)
            {
                print $dbconn->ErrorMsg();
            }
            if (!$rg->EOF)
            {
                $sqlgraph = "SELECT COUNT(acid_event.id) AS num_events, hour(convert_tz(timestamp,'+00:00','$tzc')) AS intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) AS suf 
                    FROM alienvault_siem.acid_event WHERE 1=1 $query_where GROUP BY suf,intervalo";
            }
        }
    }

    $rg = $dbconn->CacheExecute($sqlgraph);

    if (!$rg)
    {
        print $dbconn->ErrorMsg();
    }
    else
    {
        while (!$rg->EOF)
        {
            $data[$rg->fields['suf'].' '.$rg->fields['intervalo'].'h'] = $rg->fields['num_events'];

            $rg->MoveNext();
        }
    }

    $db->close();

    Cache_file::save_file($file, $data);

    return $data;
}


/**
 * Function: Logger_trends
 * 		Get the Logger trends in hours
 *
 * Author: XXX
 * Creation Date: XXX
 * Modification Date: 14/03/2012 - Javier Martinez Navajas (fjmnav@alienvault.com)
 *
 *
 * A more detailed description goes here. All these descriptions must be full filled properly.
 *
 *
 * @param $param
 *   To do...
 *		
 * @param $h
 *   Number of hours of the trend.
 *
 * @return
 *   An array with the result of the query that contains the data of the trend
 *
 */
function Logger_trends()
{
	global $tz;
	// Round to floor value: wcl and topgraph show different values with .5 timezones
	if (preg_match("/\d+[\.\,]\d+/", $tz)) 
	{
		$tz = floatval($tz) + 0.5;
	}
	
	$timetz    = gmdate("U")+(3600*$tz); // time to generate dates with timezone correction
	
	$data      = array();
	$last_date = gmdate("YmdHis", $timetz);
	
	$logger = new Logger();
	
	if ($logger->statsAllowed()) 
	{
		$date_from = gmdate("Y-m-d H:i:s",$timetz-(3600*24));
		$date_to   = gmdate("Y-m-d H:i:s", $timetz);
		
		$csv       = $logger->get_csv_range($logger->selected_servers, $date_from, $date_to, $tz);
		
		foreach ($csv as $chart_data)
		{
			foreach ($chart_data as $key => $value)
			{
				if (preg_match("/(\d+)\/\d+\/\d+ at (\d\d)\(h\)/", $key, $found))
				{
					$data[(int)$found[1].' '.((int)$found[2]).'h'] += $value;
				}
			}
		}
		
		// Get last index date
		$result = $logger->get_wcl($logger->selected_servers, $date_from, $date_to, 'lastupdate');
		
		if (trim($result[0]) != '')
		{
		    $last_date = gmdate("YmdHis",strtotime(trim($result[0]))+(3600*$tz));
		}
	}

	return array($data, $last_date);
}
