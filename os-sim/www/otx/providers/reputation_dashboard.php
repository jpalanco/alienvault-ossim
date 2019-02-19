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


Session::logcheck_ajax("dashboard-menu", "IPReputation");

/*
* This function retrieves the IP Reputation Summary Information.
*
* @return array
*
*/
function get_ip_reputation_summary()
{
    $data  = POST('data');
    $type  = intval($data['type']);
    
    //Initialization of Vars
    $ips   = array();
    $top   = array();
    $chart = array();
    $total = 0;
    $date  = _('Unknown');
    
    $Reputation = new Reputation();

    if ($Reputation->existReputation()) 
    {
    	list($ips, $cou, $order, $total) = $Reputation->get_data($type, 'All');
    	session_write_close();
    	
    	//Getting IPs by Country
    	$cou = array_splice($cou, 0, 10);			
        foreach ($cou as $c => $value)
        { 
            $info = explode(";", $c);
            $flag = '';
        
            if ($info[1] != '') 
            {
                $flag = "<img src='/ossim/pixmaps/".($info[1]=="1x1" ? "" : "flags/") . strtolower($info[1]).".png'>";
            }

            $top[] = array(
                'flag'        => $flag,
                'name'        => $info[0],
                'occurrences' => Util::number_format_locale($value,0)
            );
        }
                     
        //Getting IPs by Activity                  
        $order = array_splice($order, 0, 10);
    	foreach($order as $type => $ocurrences) 
    	{
    	   $chart[] = array(
        	   $type . ' [' . Util::number_format_locale($ocurrences,0) . ']',
        	   $ocurrences 
    	   );
    	}
    
    	//Getting total of IPs
    	$total = Util::number_format_locale($total, 0);
    	
    	//Getting Date of the last Update.
        $date  = gmdate("Y-m-d H:i:s", filemtime($Reputation->rep_file) + (3600 * Util::get_timezone()));
	}
    
    return array(
        'ips'            => $ips,
        'top_countries'  => $top,
        'ip_by_activity' => $chart,
        'total'          => $total,
        'last_updated'   => $date
    );
}


function get_pulse_summary()
{
    session_write_close();
    
    $otx = new Otx();

    //This exception is an special exception to handle when OTX is not registered.
    try
    {
        $stats = $otx->get_pulse_stats();
    }
    catch (Exception $e)
    {
        if (preg_match('/OTX is not activated/', $e->getMessage()))
        {
            Util::response_bad_request('OTX_NOT_ACTIVE');
        }
        else
        {
            Util::response_bad_request($e->getMessage());
        }
    }
    
    return $stats;
}


function get_top_pulses()
{
    session_write_close();
    
    $params = array('range' => 7, 'top' => 25);
    $graph  = array();
    
    $otx    = new Otx();
    $top    = $otx->get_events_from_top_pulses($params);
    
    if (is_array($top) && count($top) > 0)
    {
        $legend = build_legend(7);
        
        foreach ($top as $p_id => $pulse)
        {   
            $p_top = array(
                'id'    => $p_id,
                'name'  => $pulse['name'],
                'total' => $pulse['total']
            );
            
            foreach ($legend as $l)
            {
                $p_top['values'][] = array(
                    'date' => $l,
                    'value'=> intval($pulse['values'][$l]['value'])
                );
            }
            
            $graph[] = $p_top;
        }
    }
    
    return $graph;
}


function get_trend_pulses()
{
    session_write_close();
    
    $params = array('range' => 7);
    $graph  = array();
    
    $otx    = new Otx();
    $trend  = $otx->get_events_from_all_pulses($params);
    
    if (is_array($trend) && count($trend) > 0)
    {
        $legend = build_legend(7);
        
        foreach ($legend as $l)
        {
            $graph[] = array(
                'date' => $l,
                'value'=> intval($trend[$l]['value'])
            );
        }
    }

    return $graph;
}

function build_legend($days)
{
    if ($days < 1)
    {
        return array();
    }
    
    $tz     = Util::get_timezone();
    $legend = array();
    
    for ($i = ($days - 1); $i >= 0; $i--)
    {
        $legend[] = gmdate('Y-m-d',time() + (3600 * $tz) - (86400 * $i));
    }
    
    return $legend;
}

//Checking the action to perform.
$action = POST('action'); 
$result = array();

try
{
    switch($action)
    {
        case 'reputation':
            $result = get_ip_reputation_summary();
        break;
        
        case 'pulse':
            $result = get_pulse_summary();
        break;
        
        case 'top_pulses':
            $result = get_top_pulses();
        break;
        
        case 'trend_pulses':
            $result = get_trend_pulses();
        break;
        
        default:
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid Action.'));
    }
}
catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}


echo json_encode($result);