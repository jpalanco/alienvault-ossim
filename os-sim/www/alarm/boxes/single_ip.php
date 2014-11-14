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

// right click context menu info
if ($src && $dst)
{
    foreach($src['ip'] as $src_ip => $info_ip);
    foreach($dst['ip'] as $dst_ip => $info_ip);
}

// Get ip data
foreach($data['ip'] as $ip => $info_ip);

$count = $info_ip['count'];
$uuid  = strtoupper(preg_replace('/^0x|NULL/i', '', $info_ip['uuid']));

//Geolocation
$location = explode(';', $info_ip['country']);
$flag     = strtolower($location[0]);
$location = empty($location[1]) ? ( empty($location[0]) ? _("N/A") : $location[0]) : $location[1];


if (strlen($location) == 2 && $location != $geoloc->get_country_name($location) )
{
	$location = $geoloc->get_country_name($location);
}

if(!file_exists("/usr/share/ossim/www/pixmaps/flags/$flag.png"))
{    
    //Empty geo-data, try with Geoloc        
    $country  = $geoloc->get_country_by_host($conn, $ip);
        
    $flag     = strtolower($country[0]);
    $location = $country[1]; 
    
    
    if(!file_exists("/usr/share/ossim/www/pixmaps/flags/$flag.png")) 
	{
        $location = _('Unknown');
        $flag     = '';
    }   
}

// HOME IP
$vulns    = -1;
$_ctx     = $ctx;
$homelan  = '';
$hostname = '';

$_net_id  = (preg_match('/src/', $prefix)) ? $event_info['src_net'] : $event_info['dst_net'];

$host_obj = Asset_host::get_object($conn, $uuid, TRUE);

if(is_object($host_obj))
{
	$hostname    = $host_obj->get_name();
	$_ctx        = $host_obj->get_ctx();
}
$host_output = Asset_host::get_extended_name($conn, $geoloc, $ip, $_ctx, $uuid, $_net_id);
$homelan     = ($host_output['is_internal']) ? 'bold' : '';


if ($homelan)
{
	$location = '<strong>'._('UNKNOWN').'</strong>';        
	$vulns    = Vulnerabilities::get_latest_vulns($conn,$ip);
}

$net = array_shift(Asset_host::get_closest_net($conn, $ip, $_ctx));

if (is_array($net) && !empty($net)) 
{
	$location = '';
	
	if ($net['icon'] != '')
    {
		$location = "<img src='data:image/png;base64,".base64_encode($net['icon'])."' border='0'> ";
	}

	$location .= '<strong>'.strtoupper($net['name']).'</strong> ('.$net['ips'].')';
}

// OTX / Vulns
$subfix       = str_replace('_t', '_', $prefix);
$rep_tooltip  = Reputation::getreptooltip($event_info['rep_prio'.$subfix], $event_info['rep_rel'.$subfix], $event_info['rep_act'.$subfix], FALSE);


if ($info_ip['rep'] == 1)
{
    $labs = Reputation::getlabslink($ip);
    $otx  = '<a href="'.$labs.'" class="otx" target="_blank" data-title="'.$rep_tooltip.'">'._('Yes').'</a>';
}
else
{
    $otx = _('No');
}




$nports = ($complete) ? 20 : 10;

//Ports
$ports = $data['port'];
arsort($ports );

$ports = array_slice($ports, 0 ,$nports, true);
ksort($ports);
?>

<script type="text/javascript">

    function go_vulns(prefix) 
    {
        var prefix = prefix.match(/dst/) ? 'dst' : 'src';
        
        var iframe = '#if_detail_'+prefix;
        
        try
        {
            $(iframe)[0].contentWindow.set_tab(2);
        }
        catch(Err){}
    }
    
	$(document).ready(function()
	{

		//Tag Cloud Configuration
		$.fn.tagcloud.defaults = {
		  size: {start: 12, end: 20, unit: 'px'},
		  color: {start: '#666666', end: '#333333'}
		};

		$('#cloud_port<?php echo $prefix ?> a').tagcloud();		
		
		$('.otx').tipTip({defaultPosition:'right'});
		
	});
	
</script>
            
<div class='midbig line HostReportMenu <?php echo $homelan ?>' id='<?php echo "$ip;$hostname;$uuid" ?>' ctx='<?php echo $_ctx ?>' id2='<?php echo "$src_ip;$dst_ip" ?>'>
	<?php 

    //Hostname
    $hostname = ($hostname == '')? $ip : $hostname . " ($ip)" ;
	
    if (!$complete) 
    { 
        echo "<a href='javascript:void(0);' class='$homelan' onclick='show$prefix();'>";
    }
    if(!empty($flag)) 
	{ 
		echo "<img src='/ossim/pixmaps/flags/$flag.png' width='20px' height='14px' align='absmiddle' style='padding-bottom:5px;'/> ";
	}

	echo "$hostname";

	if (!$complete) 
    { 
        echo "</a>";
    } 
    
    ?>
</div>


<div class='medium line'>	
	<img src='/ossim/alarm/style/img/location.png' height='17' align='absmiddle' style='padding-bottom:4px;'/> <span class='gray_tittle'><?php echo _('Location') ?>:</span> <?php echo $location ?>
</div>


<div class='medium line'>	
    <?php 
    if ($vulns >= 0) 
    {         
        ?>
        <img src='/ossim/alarm/style/img/target.png' height='13' align='absmiddle' style='padding-bottom:4px;'/><span class='gray_tittle'> <?php echo _('Vulnerabilities') ?>:</span> 
	   
        <?php 
        if ($complete) 
        { 
            ?>
            <a href='javascript:;' onclick='go_vulns("<?php echo $prefix ?>")'><?php echo $vulns ?></a>
            <?php 
        } 
        else 
        { 
            echo $vulns; 
        }    
    } 
    else 
    {          
        ?>
        <img src='/ossim/alarm/style/img/sun_black.png' height='16' align='absmiddle' style='padding-bottom:4px;'/><span class='gray_tittle'> <?php echo _('OTX') ?>:</span> <?php echo $otx ?>
        <?php 
    } 
    ?>
</div>


<div class='medium line'>	
	<table class='transparent' width='100%' cellpadding="0" cellspacing="0">
		<tr>
			<td class='medium' style='vertical-align:top;white-space:nowrap;text-align:left;width:1%;'>
				<img src='/ossim/alarm/style/img/port.png' height='15' align='absmiddle' style='padding-bottom:3px;'/> <span class='gray_tittle'><?php echo _('Ports') ?></span>
			</td>
			<td style='white-space:normal;'>
				<div id="cloud_port<?php echo $prefix ?>" class='div_port'>
					<?php
					foreach($ports as $port => $np) 
					{
						$tooltip = _('Port') . " $port - $np " . (($np == 1) ? _('Occurrence') : _('Occurrences') );
						$port    = ($port == 0) ? _('Unknown') : strtoupper(Port::port2service($conn, $port));
						
						?>
						<a href="#" class='alarm-help' style='text-decoration:none' title='<?php echo $tooltip ?>' rel="<?php echo $np ?>"><?php echo $port ?></a>
						<?php
					}
					?>
				</div>
			</td>
		</tr>
	</table>
</div>