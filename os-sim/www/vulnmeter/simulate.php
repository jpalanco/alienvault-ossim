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
ini_set('include_path', '/usr/share/ossim/include');
ini_set('max_execution_time', 300);
require_once 'av_init.php';
$test_ok = TRUE;
$conf        = $GLOBALS['CONF'];

$sched_id = intval($argv[1]);

if ($sched_id==0)
{
    Session::logcheck('environment-menu', 'EventsVulnerabilities');
}

$targets  = '';

$split_jobs = FALSE;

$db   = new ossim_db();
$conn = $db->connect();

if ($sched_id > 0)
{
    //Getting host information
    $query  = 'SELECT * FROM vuln_job_schedule WHERE id = ?';
    $params = array($sched_id);

    $rs = $conn->Execute($query, $params);

    $id_targets   = explode("\n", $rs->fields['meth_TARGET']);
    $hosts_alive  = intval($rs->fields['meth_CRED']);
    $scan_locally = intval($rs->fields['authorized']);
    $not_resolve  = ($rs->fields['resolve_names'] == '1') ? 0 : 1 ;
    $scan_server  = $rs->fields['email'];
    $user         = $rs->fields['fk_name'];
    
    $split_jobs = TRUE;
    
    // login the user
    $session = new Session($user, '', '');
    $session->login(TRUE);

    $dbpass = $conn->GetOne('SELECT pass FROM users WHERE login = ?', array($user));
    $client = new Alienvault_client($user);
    $client->auth()->login($user,$dbpass);
}
else
{
    foreach($_POST as $key => $value) {
        $$key = $value;
    }
    
    $hosts_alive  = intval($hosts_alive);
    $scan_locally = intval($scan_locally);
    $not_resolve  = intval($not_resolve);
    $id_targets   = explode(',', $targets);
}

$db->close($conn);

unset($_SESSION['_vuln_targets']);

$error_message   = '';
$assets_groups   = array();
$targets         = array();
$selected_ids    = array(); // This array will content all the selected sensor IDs
$local_sensor_id = NULL;
foreach ($id_targets as $id_target)
{
    if (trim($id_target) != '')
    {
        $id_target = trim($id_target);

        ossim_set_error(FALSE);

        if (!preg_match('/^!/', $id_target))
        {
            // ID && (hostgroup || netgroup)
            if (preg_match('/^([a-f\d]{32})#(hostgroup|netgroup)$/i', $id_target, $found))
            {
                $assets_groups[$found[1]] = $found[2];

                continue;
            }

            // ID && (IP || CIDR)
            else if (preg_match('/^[a-f\d]{32}#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$/i', $id_target, $found))
            {
                // Clean /32 mask to avoid "Error in host specification" OpenVAS error
                if (!empty($found[1]) && $found[1] == '/32')
                {
                    $id_target = substr($id_target, 0, -3);
                }

                list($asset_id, $ip_target) = explode('#', $id_target);

                ossim_valid($asset_id, OSS_HEX, OSS_NULLABLE, 'illegal: Asset id');

                if (ossim_error())
                {
                    $error_message .= _('Invalid target').': '.Util::htmlentities($id_target).'<br/>';
                }

                ossim_valid($ip_target, OSS_IP_CIDR_0, 'illegal:'._('Target'));

                if (ossim_error())
                {
                    $error_message .= _('Invalid target').': '.Util::htmlentities($id_target).'<br/>';
                }
            }

            // IP || CIDR
            else if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$/', $id_target, $found))
            {
                // Clean /32 mask to avoid "Error in host specification" OpenVAS error
                if (!empty($found[1]) && $found[1] == '/32')
                {
                    $id_target = substr($id_target, 0, -3);
                }

                ossim_valid($id_target, OSS_IP_CIDR_0, 'illegal:'._('Target'));

                if (ossim_error())
                {
                    $error_message .= _('Invalid target').': '.Util::htmlentities($id_target).'<br/>';
                }
            }

            // Hostname
            else
            {
                ossim_valid($id_target, OSS_FQDNS, 'illegal: Host name');

                if (ossim_error())
                {
                    $error_message .= _('Invalid target').': '.Util::htmlentities($id_target).'<br/>';
                }
            }

            if (!ossim_error())
            {
                $targets[$id_target] = array();
            }
        }
    }
}

if (empty($targets) && empty($assets_groups))
{
    $config_nt = array(
        'content' => _('Targets not found').((!empty($error_message)) ? '<br/>'.$error_message : ''),
        'options' => array(
            'type'          => 'nf_warning',
            'cancel_button' => FALSE
        ),
        'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
    );

    $nt = new Notification('nt_1', $config_nt);
    $nt->show();

    exit(1);
}

if ($split_jobs == FALSE)
{
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
}

$db   = new ossim_db();
$conn = $db->connect();

// get the groups members

foreach ($assets_groups as $asset_id => $asset_type)
{   
    if ($asset_type == 'hostgroup' && Asset_group::is_in_db($conn, $asset_id))
    {
        $host_group = Asset_group::get_object($conn, $asset_id);
        
        list($host_list, $total) = $host_group->get_hosts($conn);
        
        foreach ($host_list as $host_id => $host_data)
        {
            $host_ips = explode(',', $host_data['ips']);
            
            foreach ($host_ips as $host_ip)
            {
                $targets[$host_id . '#' . $host_ip] = array('hostgroup_id' => $asset_id);
            }
        }
    }
    else if ($asset_type == 'netgroup' && Net_group::is_in_db($conn, $asset_id))
    {
        $net_list = Net_group::get_networks($conn, $asset_id);
	
        foreach ($net_list as $net)
        {
            $net_id    = $net->get_net_id();
            
            $net_cidrs = Asset_net::get_ips_by_id($conn, $net_id);
            
            $cidrs = explode(',', $net_cidrs);
            
            foreach ($cidrs as $cidr)
            {
                $targets[$net_id . '#' . $cidr] = array('netgroup_id' => $asset_id);
            }
        }
    }
}

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

$message_pre_scan        = _('Pre-scan locally');
$message_force_pre_scan  = _('Error: Need to force pre-scan locally');
$ctest                   = array(); // to save connection test to servers
$ttargets                = array(); // to save check for targets
$sensor_error            = false;

// Getting available sensors (connected sensors)
$_list_data = Av_sensor::get_list($conn);
$all_sensors = $_list_data[0];

// Remote nmap

$ids = array();
$agents = Av_scan::get_scanning_sensors();

if (is_array($agents) && !empty($agents))
{
    $ids = array_keys($agents);
}

$withnmapforced = 0;
if (valid_hex32($scan_server) && !$hosts_alive && $sensor_id != '')
{
    $ids = array_merge(array($sensor_id), $ids);

    $withnmapforced = 1;
}

// targets

$total_host = 0; // count total targets to scan

foreach($targets as $target => $target_data)
{
    $sensors = array();

    if($scan_server != '')
    {
        $sensors = array($scan_server); // force sensor
    }

    if (preg_match("/^!/",$target))
    {
        continue;
    }
    
    if (!empty($target_data['hostgroup_id']))
    {
        $ttargets[$target]['hostgroup_id'] = $target_data['hostgroup_id'];
    }

    if (!empty($target_data['netgroup_id']))
    {
        $ttargets[$target]['netgroup_id'] = $target_data['netgroup_id'];
    }

    // target_id#cidr_or_ip
    $unresolved = (!preg_match("/\d+\.\d+\.\d+\.\d+/",$target) && $not_resolve) ? TRUE : FALSE;

    if(preg_match("/([a-f\d]+)#(.*)/i", $target, $found))
    {
        $asset_id   = $found[1];
        $ip_cidr    = $found[2];
    }
    else
    {
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
            else
            {
                $load[]      = 'N/A';

                $sensor_name = $all_sensors[strtoupper($sid)];

                $sclass      = '';

                $vsclass     = '';

                $nmsclass    = '';
            }

            if (!$selected && Session::sensorAllowed($sid) && valid_hex32($sid))
            {
                $selected = TRUE;

                $sensor_name = '<strong>' . $sensor_name . '</strong>';

                $selected_ids[$sid]++;

                $ttargets[$target]['sensor'] = $sid;

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
            $sname[] = $sensor_name;

            $sperm[] = "<img $sclass src='../pixmaps/".(Session::sensorAllowed($sid) ? "tick" : "cross").".png' border='0'>";
            $vs[]    = "<img $vsclass src='../pixmaps/".((valid_hex32($scan_server) && $sid==$sensor_id) ? "tick" : (($has_vuln_scanner) ? "tick" : "cross")).".png' border='0'>";

            if(!$hosts_alive)
            {
                // don't do a Nmap scan
                $snmap[] = '<span style="font-size:9px;color:gray">'._('No selected') . '</span>';
            }
            else
            {
                $snmap[] = "<img $nmsclass align='absmiddle' src='../pixmaps/".(($scan_locally || ($withnmap && $withnmapforced)) ? "tick": (($withnmap) ? "tick" : "cross")).".png' border='0'>".
                (($scan_locally || ($withnmap && $withnmapforced)) ? "<span style='font-size:9px;color:gray'>$message_pre_scan</span>": (($withnmap) ? "" : "<span style='font-size:9px;color:gray'>$message_force_pre_scan</span>"));
            }

            if($ttargets[$target]['sensor'] == $sid)
            {
                $ttargets[$target]['sperm'] = Session::sensorAllowed($sid) ? TRUE : FALSE;
                $ttargets[$target]['vs']    = (valid_hex32($scan_server) && $sid==$sensor_id) ? TRUE : (($has_vuln_scanner) ? TRUE : FALSE);

                if(!$hosts_alive)
                {
                    $ttargets[$target]['snmap'] = TRUE;
                }
                else
                {
                    $ttargets[$target]['snmap'] = ($scan_locally || ($withnmap && $withnmapforced)) ? TRUE: (($withnmap) ? TRUE : FALSE);
                }
            }
        }

        $snames = implode('<br><br>', $sname);
    }
    else
    {
        $snames = '<span style="font-weight:bold;color:#ff0000">' . _('Sensor not found') . '</span>';
    }
    
    // sensors names
    $ttargets[$target]['snames']        = $snames;
    
    // target name
    $ttargets[$target]['name']          = $name;
    
    // sensors permissions
    $ttargets[$target]['sensors_perms'] = $sperm;
    
    // sensors permissions
    $ttargets[$target]['vuln_scanner']  = $vs;
    
    // Nmap status
    $ttargets[$target]['nmap_scan']     = $snmap;
    
    // Load
    $ttargets[$target]['load']          = $load;
}

// group targets by group and sensors

$result = array();

foreach ($ttargets as $target => $target_data)
{   
    if (Av_sensor::is_in_db($conn, $target_data['sensor']) == TRUE)
    {
        if (!empty($target_data['hostgroup_id']))
        {
            $result_key = $target_data['hostgroup_id'] . '#hostgroup#' . $target_data['sensor'];
            $result[$result_key]['name'] = Asset_group::get_name_by_id($conn, $target_data['hostgroup_id']);
        }
        else if (!empty($target_data['netgroup_id']))
        {
            $result_key = $target_data['netgroup_id'] . '#netgroup#' . $target_data['sensor'];
            $result[$result_key]['name'] = Net_group::get_name_by_id($conn, $target_data['netgroup_id']);
        }
        else
        {
            $result_key = $target . '#' . $target_data['sensor'];
            $result[$result_key]['name'] = $target_data['name'];        
        }
        
        $result[$result_key]['ips'][]  = $target;
        $result[$result_key]['sensor'] = $target_data['sensor'];
       
        $result[$result_key]['sperm']  = (empty($result[$result_key]['sperm']) || $result[$result_key]['sperm'] == 1) ? $target_data['sperm'] : $result[$result_key]['sperm'];
        $result[$result_key]['perm']   = (empty($result[$result_key]['perm'])  || $result[$result_key]['perm'] == 1)  ? $target_data['perm']  : $result[$result_key]['perm'];
        $result[$result_key]['vs']     = (empty($result[$result_key]['vs'])    || $result[$result_key]['vs'] == 1)    ? $target_data['vs']    : $result[$result_key]['vs'];
        $result[$result_key]['snmap']  = (empty($result[$result_key]['snmap']) || $result[$result_key]['snmap'] == 1) ? $target_data['snmap'] : $result[$result_key]['snmap'];
        
        // this field is the same for all group components
        
        $result[$result_key]['snames']        = $target_data['snames'];
        $result[$result_key]['load']          = $target_data['load'];
        $result[$result_key]['sensors_perms'] = $target_data['sensors_perms'];
        $result[$result_key]['vuln_scanner']  = $target_data['vuln_scanner'];
        $result[$result_key]['nmap_scan']     = $target_data['nmap_scan'];
    }
}

// create the targets array for the sched.php script

$targets_by_sensor = array();

foreach ($result as $target_id => $target_data)
{
    foreach ($target_data['ips'] as $ip)
    {
        $_SESSION['_vuln_targets'][$ip] = $target_data['sensor'];
    }
    
    // fill the targets_by_sensor array
    
    preg_match("/(.*)#([a-f0-9]{32})$/i", $target_id, $found);
    
    $s_id = $found[2];
    $t_ip = $found[1];
    
    $targets_by_sensor[$s_id]['targets'][] = $t_ip;
    
    if (empty($targets_by_sensor[$s_id]['ips']))
    {
        $targets_by_sensor[$s_id]['ips'] = array();
    }

    $targets_by_sensor[$s_id]['ips'] = array_merge($targets_by_sensor[$s_id]['ips'], $target_data['ips']);
    
    if (empty($targets_by_sensor[$s_id]['sensor_status']))
    {
        $targets_by_sensor[$s_id]['sensor_status'] = ($ctest[$s_id] == '') ? 'OK' : 'KO';
    }
    
    if (empty($targets_by_sensor[$s_id]['nmap_check']) || $targets_by_sensor[$s_id]['nmap_check'] == 1)
    {
        $targets_by_sensor[$s_id]['nmap_check']   = intval($target_data['snmap']);
        $targets_by_sensor[$s_id]['nmap_message'] = $target_data['nmap_scan'][0];
    }
}

$db->close($conn);

if ($split_jobs == TRUE)
{
    echo json_encode($targets_by_sensor);

    exit(0);
}

$db   = new ossim_db();
$conn = $db->connect();

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

<br/>

<?php
if ($scan_type != 'adhoc')
{
    if(!empty($result))
    {  
    ?>
        <table align="center" class="transparent" cellpadding="0" cellspacing="0" width="80%">
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
        
        <?php
        
        foreach ($result as $target_id => $target_data)
        {
        ?>
            <tr>
                <td><?php echo ips2text($target_data['ips'], TRUE);?></td>
                <td style="padding-left:4px;padding-right:4px;" nowrap><?php echo $target_data['name'];?></td>
                <td><img class="<?php echo "perm_".md5($target_id);?>" src="../pixmaps/<?php echo ($target_data['perm']) ? 'tick' : 'cross'?>.png" border="0"></td>
                <td style="padding-left:4px;padding-right:4px" nowrap><?php echo $target_data['snames'];?></td>
                <td><?php echo array2text($target_data['sensors_perms']) ?></td>
                <td><?php echo array2text($target_data['vuln_scanner']) ?></td>
                <td style="text-align:center;" nowrap><?php echo array2text($target_data['nmap_scan']) ?></td>
                <td style="padding-left:4px;padding-right:4px" nowrap><?php echo array2text($target_data['load']) ?></td>
            </tr>
        <?
        }
        ?>
        </table>
    <?php
    }
    else
    {
    ?>
        <div style="margin:10px 0px 0px 0px"><?php echo _('No targets to scan') ?></div>
    <?php
    }
}
else
{?>
    <table class="table_list" align="center" style="width:90%">
        <tr>
            <th><?php echo _('Asset')?></th>
            <th><?php echo _('Sensor')?></th>
            <th><?php echo _('Available')?></th>
            <th><?php echo _('Nmap Scan')?></th>
        </tr>
    <?php
        
    foreach ($targets_by_sensor as $s_id => $job_data)
    {
        $sensor_object = Av_sensor::get_object($conn, $s_id);
        $sensor_data   = $sensor_object->get_name() . ' [' . $sensor_object->get_ip() . ']';
        $sensor_icon   = ($job_data['sensor_status'] == 'OK') ? '../pixmaps/tick.png' : '../pixmaps/cross.png';
    ?>
        <tr>
            <td><?php echo ips2text($job_data['ips'], TRUE) ?></td>
            <td><?php echo $sensor_data ?></td>
            <td><img src='<?php echo $sensor_icon ?>' border="0"></td>
            <td><?php echo $job_data['nmap_message'] ?></td>
        </tr>        
    <?php
    }
    ?>
    </table>
<?php
}

if ($scan_type != 'adhoc' && count($ctest) > 0)
{
?>
    <table class="transparent" align="center">
    <tr>
        <td style="padding-top:12px" colspan="8" id="sconnection">
            <?php
            if($total_host > 255)
            {
                ?>
                <div id="total_hosts">
                <?php
                    if (Filter_list::MAX_VULNS_ITEMS < $total_host) {
                        ?><span><?php
                        echo sprintf(_('You are about to scan a big number of hosts (<span id="thosts">%s</span> hosts).<br /> Vulnerability scans can only be performed on %s assets at a time. If you choose to proceed - the scan job will be parted.'),$total_host,Filter_list::MAX_VULNS_ITEMS);
                        ?></span><?php
                    } elseif ($hosts_alive === 1) {
                        $time_per_host = 0.34770202636719; // seconds
                        $total_minutes = ceil(($total_host*$time_per_host)/60);
                        $nmap_message  = _('You are about to scan a big number of hosts (<span id="thosts">#HOSTS#</span> hosts).<br /> This scan could take a long time depending on your network and the number of assets <br /> that are up.');
                        $nmap_message  =  str_replace("#HOSTS#", $total_host, $nmap_message);
                        echo $nmap_message;
                    }
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

                        if (preg_match("/Failed to acquire socket/", $v))
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
if ($test_ok) {
    foreach ($ttargets as $target_data)
    {
        // Check if all targets can be scanned
        if(!$target_data['perm'] || !$target_data['sperm'] || !$target_data['vs'] || !$target_data['snmap'])
        {
            $test_ok = FALSE;
            break;
        }
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

// If any sensor is remote the "pre-scan locally" must be disabled

$local_system_id  = Util::get_default_uuid();

$system_info      = Av_center::get_system_info_by_id($conn, $local_system_id);

if ($system_info['status'] == 'success')
{
   $local_sensor_id = $system_info['data']['sensor_id'];
}

$l_array   = array($local_sensor_id);

$s_array   = array_keys($selected_ids);

// nmap type required: Local or Remote

$nmap_type = (count( array_diff($s_array, $l_array)) ==0 ) ? 'local' : 'remote';

echo $total_host . '|' . $nmap_type;

$db->close($conn);

function ips2text($data, $clean_id = FALSE)
{
    $result = '';
    
    if ($clean_id)
    {
        $data = array_map('clean_id', $data);
    }
    
    if (count($data) > 2)
    {
        $first = $data[0];
        
        $last  = array_pop($data);
        
        $result = $first . ' ... ' . $last;
    }
    else
    {
        $result = implode(', ', $data);
    }
    
    return $result;
}

function array2text($data)
{
    return implode('<br>', $data);
}

function clean_id($element)
{
    return preg_replace('/[a-f0-9]{32}#/i', '', $element);
}

