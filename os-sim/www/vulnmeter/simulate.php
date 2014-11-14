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

$conf        = $GLOBALS['CONF'];

$nessus_path = $conf->get_conf('nessus_path');

Session::logcheck('environment-menu', 'EventsVulnerabilities');
//
$targets      = '';

foreach($_POST as $key => $value) {
    $$key = $value;
}

$hosts_alive  = intval($hosts_alive);
$scan_locally = intval($scan_locally);
$not_resolve  = intval($not_resolve);

$id_targets   = explode(',', $targets);

unset($_SESSION['_vuln_targets']);

$error_message = '';
$targets       = array();

foreach($id_targets as $id_target) if (trim($id_target) != '')
{
    $id_target = trim($id_target);
    
    ossim_set_error(false);
    
    if (preg_match("/^!/",$target))
    {
        continue;
    }
    
    else if(preg_match("/^[a-f\d]{32}#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/i", $id_target))
    {
        list($asset_id, $ip_target) = explode("#", $id_target);
            
        ossim_valid($asset_id, OSS_HEX, OSS_NULLABLE , 'illegal: Asset id'); // asset id
        
        if (ossim_error())
        {
            $error_message .= _("Invalid target").": " . Util::htmlentities($id_target) . "<br/>";
        }

        ossim_valid($ip_target, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, '\.\,\/\!', 'illegal:' . _("Target"));
        
        if (ossim_error())
        {
            $error_message .= _("Invalid target").": " . Util::htmlentities($id_target) ."<br/>";
        }
    }
    else if(!preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d+)?$/",$id_target))
    {
        ossim_valid($id_target, OSS_FQDNS , 'illegal: Host name'); // asset id
        
        if (ossim_error())
        {
            $error_message .= _("Invalid target").": " . Util::htmlentities($id_target) . "<br/>";
        }
    }

    if (!ossim_error())
    {
        $targets[] = $id_target;
    }
}

if(empty($targets))
{
    $config_nt = array(
            'content' => _("Targets not found").((!empty($error_message)) ? "<br/>".$error_message : ""),
            'options' => array (
                'type'          => 'nf_warning',
                'cancel_button' => false
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
        ); 
                        
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();

    exit(1);
}

ossim_set_error(false);

ossim_valid($scan_server, OSS_HEX, 'illegal:' . _("Sensor id"));

if (ossim_error() && $scan_server != 'Null')
{
    $error_message .= _('Sensor id') . ': ' . Util::htmlentities($scan_server) . '<br>';
}

if(!empty($error_message)) {

    $config_nt = array(
            'content' => $error_message,
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => false
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
        ); 
                        
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();

    exit(1);
}

$db                      = new ossim_db();
$conn                    = $db->connect();

$tsensors   = explode(',', Session::allowedSensors());
$sensor_ids = array();

$conn->SetFetchMode(ADODB_FETCH_BOTH);

foreach($tsensors as $s_ip) {
    $sensor_ids[$s_ip] = $conn->GetOne("SELECT HEX(id) FROM sensor WHERE INET_NTOA( CONV( HEX( ip ) , 16, 10 ) ) LIKE '$s_ip'");
}

// check permissions for selected server
if( !(valid_hex32($scan_server) && (Session::allowedSensors()=="" || in_array($scan_server, array_values($sensor_ids)))) ) {
    $scan_server = "";
}

$message_pre_scan        = _("Pre-scan localy");
$message_force_pre_scan  = _("Error: Need to force pre-scan locally");
$ctest                   = array(); // to save connection test to servers
$ttargets                = array(); // to save check for targets
$sensor_error            = false;
?>

<style type="text/css">
    
    .sstatus{
        text-align:center;
        padding:5px 10px;
        vertical-align:text-top;
    }
    
    #total_hosts{
	    text-align:left;
	    color:rgba(195,195,195,0.98);
	    float:left;
    }
    
    .pright{
	    float:right;
    }
    
    .pcenter {
	    margin: 0px auto;
	    width: 100px;
    }
    
    .stable {
        width: 250px;
    }
    
    #tminutes, #thosts {
	    color:rgba(128,128,128,0.98);
    }
    
    table.gray_border2 {
        border-left: 1px solid #DFDFDF;
        border-right: 1px solid #DFDFDF;
        border-bottom: 1px solid #DFDFDF;
        border-top: 1px solid #C4C0BB;
    }
    
    #sconnection:hover {
        background-color: transparent !important;   
    }
    
</style>

<table class="transparent" cellpadding="0" cellspacing="0" width="80%">
<tr><td class="sec_title"><?=_("Configuration Check Results")?></td></tr>
</table>


<table class="table_list" align="center" style="width:80%">
<tr>
    <th><?=_("Target")?></th>
    <th><?=_("Inventory")?></th>
    <th><?=_("Target Allowed")?></th>
    <th><?=_("Sensors")?></th>
    <th><?=_("Sensor Allowed")?></th>
    <th><?=_("Vuln Scanner")?></th>
    <th><?=_("Nmap Scan")?></th>
    <th><?=_("Load")?></th>
</tr>
    <?
    
    // get available sensors
    
    $_list_data = Av_sensor::get_list($conn);
    $all_sensors = $_list_data[0];
    
    // remote nmap
    $rscan = new Remote_scan('', '');
    $rscan->available_scan();
    
    $ids = array();

    if ( is_array($rscan->get_sensors()) && count(array_keys($rscan->get_sensors())) > 0 )
    {
        $agents = $rscan->get_sensors();
        
        foreach ($agents as $asid => $agent)
        {
            $ids[] = $asid;
        }
    }

    $withnmapforced = 0;
    if (valid_hex32($scan_server) && !$hosts_alive && $sensor_id!="")
    {
        $ids = array_merge(array($sensor_id), $ids);
        
        $withnmapforced = 1;
    }

    // targets
    
    $total_host = 0; // count total targets to scan
    
    
    foreach($targets as $target)
    {
        $sensors = array();
        
        if($scan_server != '')
        {
            $sensors = array($scan_server); // force sensor
        }
    
        if (preg_match("/^!/",$target)) continue;
        
        // target_id#cidr_or_ip
        
        $unresolved = (!preg_match("/\d+\.\d+\.\d+\.\d+/",$target) && $not_resolve) ? true : false;
        
        if(preg_match("/([a-f\d]+)#(.*)/i", $target, $found)) {
            $asset_id   = $found[1];
            $ip_cidr    = $found[2];
        }
        else {
            $asset_id   = '';
            $ip_cidr    = $target;
        }
        
        $net_id = $host_id = '';
        
        if(!empty($asset_id))
        {
            if(preg_match("/\//", $ip_cidr))
            {
                if(Asset_net::is_in_db($conn, $asset_id) === TRUE)
                {
                    $net_id = $asset_id;
                }
            }
            else
            {
                if(Asset_host::is_in_db($conn, $asset_id) === TRUE)
                {
                    $host_id = $asset_id;
                }
            }
        }
        
        
        if (!empty($net_id))
        {   
            // Net with ID
        	$total_host += Util::host_in_net($ip_cidr);
        	
            $name = Asset_net::get_name_by_id($conn, $net_id);
            
            $perm = Session::netAllowed($conn, $net_id);
            
            if (count($sensors) == 0)
            {
                $sensors = array_keys(Asset_net_sensors::get_sensors_by_id($conn, $net_id));
            }
        } 
        else if (!empty($host_id))
        {   
            // Host with ID
        	$total_host++;
                        
            $name = Asset_host::get_name_by_id($conn, $host_id);
            
            $perm = ($unresolved) ? TRUE : Session::hostAllowed($conn, $host_id);
            
            if(count($sensors)==0)
            {
                $sensors = array_keys(Asset_host_sensors::get_sensors_by_id($conn, $host_id));
            }
        }
        else if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}?$/",$ip_cidr))
        { 
            // Net without ID
            $total_host += Util::host_in_net($ip_cidr);
            
            $name = $target;
            
            $perm = TRUE;
        }
        else if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",$ip_cidr))
        {
            // Host without ID
            $total_host++;
            
            $name = $target;
            
            $perm = TRUE;
            
            if (count($sensors)==0)
            {
                $closetnet_id = key(Asset_host::get_closest_net($conn, $ip_cidr));
                
                $sensors      = array_keys(Asset_net_sensors::get_sensors_by_id($conn, $closetnet_id));
            }
        }
        else if ($unresolved)
        { 
            // the target is a hostname
        	
        	$total_host++;
            
            $perm = true;
            
            $name = '-';
            
            
            
            if(count($sensors) == 0)
            {
                $sensors = $ids;
            }
        }

        $sname = $vs = $sperm = $snmap = $load = array();
        
        $selected = FALSE;
        
        // reorder sensors with load
        
        if (!valid_hex32($scan_server))
        {
            $sensors    = Av_sensor::reorder_vs_by_load($conn, $sensors);
            
            array_unique($sensors);
        }
        else
        {
            // User has selected a sensor 
            
            $sensor_obj = Av_sensor::get_object($conn, $scan_server);
            
            $sensors    = array($scan_server => $sensor_obj->get_vs_load($conn));
        }

        // info per each related sensor
        
        $ttargets[$target]['perm'] = $perm;
        
        
        
        if(!empty($sensors) && preg_match("/[\da-f]{32}/i", key($sensors)))
        {
            foreach ($sensors as $sid => $sload)
            { 
                $withnmap         = in_array($sid, $ids) || $unresolved;

                $sensor_object    = Av_sensor::get_object($conn, $sid);
                
                $has_vuln_scanner = 0;
                
                if($sensor_object !== NULL)
                {
                    $load[]      = $sload;
                   
                    $sensor_ip   = $sensor_object->get_ip();
                    
                    $has_vuln_scanner = $sensor_object->get_property('has_vuln_scanner');
                    
                    $sensor_name = $sensor_ip . ' [' . $sensor_object->get_name() . ']';
                    
                    $sclass      = 'class="sensor_' . md5($sensor_ip) . '"'; // Sensor allowed for Selenium tests
                    
                    $vsclass     = 'class="vs_' . md5($sensor_ip).'"';       // Vuln scanner for Selenium tests
                    
                    $nmsclass    = 'class="nms_' . md5($sensor_ip).'"';      // Nmap Scan for Selenium tests
                }
                else {
                    $load[]      = 'N/A';
                    
                    $sensor_name = $all_sensors[strtoupper($sid)];
                    
                    $sclass      = '';
                    
                    $vsclass     = '';
                    
                    $nmsclass    = '';
                }
                
                if (!$selected && (Session::sensorAllowed($sid) || valid_hex32($scan_server)) && ($has_vuln_scanner || valid_hex32($scan_server)))
                {
                    $selected = TRUE;
                    
                    $_SESSION['_vuln_targets'][$target] = $sid;
                    
                    $sensor_name = '<strong>' . $sensor_name . '</strong>';
                    
                    $ttargets[$target]['sensor'] = $sid;
                
                    if(preg_match("/omp\s*$/i", $nessus_path)) {
                        // check connection
                        
                        $connection = (!array_key_exists($sid, $ctest)) ? $sensor_object->check_vs_connection($conn): $ctest[$sid];

                        if( $connection == '')
                        {
                            // connection ok
                            $ctest[$sid] = '';
                        }
                        else
                        {
                            // connection ko
                            $ctest[$sid]  = $connection;
                            
                            $sensor_error = TRUE;
                        }
                    }
                }
                $sname[] = $sensor_name;

                $sperm[] = "<img $sclass src='../pixmaps/".(Session::sensorAllowed($sid) ? "tick" : "cross").".png' border='0'>";
                $vs[]    = "<img $vsclass src='../pixmaps/".((valid_hex32($scan_server) && $sid==$sensor_id) ? "tick" : (($has_vuln_scanner) ? "tick" : "cross")).".png' border='0'>";
                
                if(!$hosts_alive)
                { 
                    // don't do a Nmap scan
                    $snmap[] = '<span style="font-size:9px;color:gray">'._('No selected') . '</span>';
                }
                else {
                    
                    $snmap[] = "<img $nmsclass align='absmiddle' src='../pixmaps/".(($scan_locally || ($withnmap && $withnmapforced)) ? "tick": (($withnmap) ? "tick" : "cross")).".png' border='0'>".
                    (($scan_locally || ($withnmap && $withnmapforced)) ? "<span style='font-size:9px;color:gray'>$message_pre_scan</span>": (($withnmap) ? "" : "<span style='font-size:9px;color:gray'>$message_force_pre_scan</span>"));
                }
                
                if( $ttargets[$target]['sensor'] == $sid ) {
                    $ttargets[$target]['sperm'] = Session::sensorAllowed($sid) ? TRUE : FALSE;
                    $ttargets[$target]['vs']    = (valid_hex32($scan_server) && $sid==$sensor_id) ? TRUE : (($has_vuln_scanner) ? TRUE : FALSE);
                    
                    if(!$hosts_alive)
                    {
                        $ttargets[$target]['snmap'] = TRUE;
                    }
                    else {
                        $ttargets[$target]['snmap'] = ($scan_locally || ($withnmap && $withnmapforced)) ? TRUE: (($withnmap) ? TRUE : FALSE);
                    }
                }
            }
            $snames = implode('<br><br>', $sname);
        }
        else {
            $snames = '<span style="font-weight:bold;color:#ff0000">' . _('Sensor not found') . '</span>';
        }
        
        $sperms = implode('<br>', $sperm);
        $vulns  = implode('<br>', $vs);
        $nmaps  = implode('<br>', $snmap);
        $load   = implode('<br><br>', $load);
    ?>
    <tr>
        <?php
        $target = preg_replace("/.*#/", "",$target);
        ?>
        <td><?php echo $target;?></td>
        <td style="padding-left:4px;padding-right:4px;" nowrap><?php echo $name;?></td>
        <td><img class="<?php echo "perm_".md5($target);?>" src="../pixmaps/<?=($perm) ? "tick" : "cross"?>.png" border="0"></td>
        <td style="padding-left:4px;padding-right:4px" nowrap><?=$snames?></td>
        <td><?php echo $sperms ?></td>        
        <td><?php echo $vulns ?></td>
        <td style="text-align:center;" nowrap><?php echo $nmaps ?></td>
        <td style="padding-left:4px;padding-right:4px" nowrap><?php echo $load ?></td>
    </tr>
    <?
    }
    ?>
    </table>
    <?php
    
    if(count($ctest) > 0) {
    ?>
    <table class="transparent">
    <tr>
    	<td style="padding-top:12px" colspan="8" id="sconnection">
    		<?php
    		if($total_host>255 && $hosts_alive===1) {
    		?>
	    		 <div id="total_hosts">
		    		 <?php
		    		 
		    		 $time_per_host = 0.34770202636719; // seconds
		    		 
		    		 $total_minutes = ceil(($total_host*$time_per_host)/60);
		    		 
		    		 $nmap_message  = _('You are about to scan a big number of hosts (<span id="thosts">#HOSTS#</span> hosts).<br /> This scan could take a long time depending on your network and the number of assets <br /> that are up.');
		    		 
		    		 $nmap_message  =  str_replace("#HOSTS#", $total_host, $nmap_message);
		    		 
		    		 //$nmap_message  =  str_replace("#MINUTES#", $total_minutes, $nmap_message);
		    		 
		    		 echo $nmap_message;
		    		 ?>
	    		 </div>
    		 <?php
    		 }
    		 ?>
    		 <div <?php echo ($total_host>255 && $hosts_alive===1) ? 'class="pright"': '' ?>>
    		    <table class="table_list stable" <?php echo ($total_host>255 && $hosts_alive===1) ? '': 'align="center"' ?> cellpadding="0" cellspacing="0">
                   <tr>
                      <th>
                         <strong><?php echo _('Scanner IP');?></strong>
                      </th>
                      <th>
                         <strong><?php echo _('Scanner connection');?></strong>
                      </th>
                  </tr>
                  <?php
                  foreach ($ctest as $k => $v)
                  {
                     $sensor_ip = Av_sensor::get_ip_by_id($conn, $k);
                  
                     if ($v == '')
                     {
                        echo '<tr><td class="nobborder" style="text-align:center;padding:0px 10px;">' . $sensor_ip . '</td>';
                        echo '<td class="nobborder" style="text-align:center;padding:0px 10px;"><img class="vcheck_'.md5($sensor_ip).'" src="../pixmaps/tick.png" border="0" /></td></tr>';
                     }
                     else
                     {
                        $nf_type = "nf_error";

                        echo "<tr><td class='nobborder sstatus' >".$sensor_ip."</td>";
                        echo "<td width='300' class='nobborder sstatus' >";

                        if ( preg_match("/Failed to acquire socket/", $v) )
                        {
                           $v       = 'Unable to connect to vulnerability scanner. If the system has been updated recently the vulnerability scanner is rebuilding its database. Please wait a few minutes.';
                           $nf_type = 'nf_warning'; 
                        }

                        $config_nt = array(
                            'content' => _($v),
                            'options' => array (
                                'type'          => $nf_type,
                                'cancel_button' => false
                            ),
                            'style'   => 'width: 80%; margin: 10px auto; text-align: left;'); 
                                        
                        $nt = new Notification('nt_2', $config_nt);
                        $nt->show();

                        echo '</td></tr>';
                     }
                  }
               ?>
                </table>
              </div>
           </td>
       </tr>
    </table>
    <?php
    }

$test_ok = TRUE;

foreach ($ttargets as $target_data)
{ 
    // Check if all targets can be scanned
    if($target_data['perm'] && $target_data['sperm'] && $target_data['vs'] && $target_data['snmap'])
    {
        $test_ok = TRUE;
    }
    else
    {
        $test_ok = FALSE;
    }
    
    if(!$test_ok)
    {
        break;
    }
}
?>
<br><br>

<?
if($test_ok && !$sensor_error)
{ 
// we can enable button to run job
    echo '|1|';
}
else 
{
    echo '|0|';
    unset($_SESSION['_vuln_targets']); // clean scan targets
}

echo $total_host;

$db->close($conn);
?>
