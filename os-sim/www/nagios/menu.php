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

Session::logcheck('environment-menu', 'MonitorsAvailability');

$db    = new ossim_db();
$conn  = $db->connect();


$conf            = $GLOBALS['CONF'];
$nagios_default  = parse_url($conf->get_conf('nagios_link'));


$filters = array(
    'order_by' => 'priority desc'
);

list($sensor_list, $sensor_total) = Av_sensor::get_list($conn, $filters);

$scheme         = isset($nagios_default['scheme']) ? $nagios_default['scheme'] : 'http';
$path           = isset($nagios_default['path']) ? $nagios_default['path'] : '/nagios3/';
$path           = str_replace('//', '/', $path);
$port           = isset($nagios_default['port']) ? ':' . $nagios_default['port'] : '';

$flag_opts      = TRUE;
$flag_login     = FALSE;

if ($path[0] != '/') 
{
    $path = '/' . $path;
}


$sensors_nagios = array();
$ng_selected    = -1;


//Retrieving the sensor list with nagios activated.
if (is_array($sensor_list)) 
{
	foreach($sensor_list as $sid => $s) 
	{
		$properties = $s['properties'];

		if ($properties['has_nagios']) 
		{
			$option = 0;
			
			if($sensor == $s['ip']) 
			{
				$option      = 1;
				
				$ng_selected = count($sensors_nagios);
			}

			$sensors_nagios[] = array(
				'id'       => $sid, 
				'ip'       => $s['ip'], 
				'name'     => $s['name'],
				'selected' => $option
			);
		}
	}
}


//If we have permissions to see any sensor...

if(!empty($sensors_nagios))
{
	if($ng_selected == -1)
	{
		$ng_selected = 0;
	}

	$_ip = $sensors_nagios[$ng_selected]['ip'];	
	
	//Remote ossim sensors and remote nagios...
	if ($_ip != '' && $_ip != 'localhost' && $_ip != Util::get_default_admin_ip() && $_ip != $_SERVER['SERVER_NAME']) 
	{	    	    
	    $_sensor        = new Av_sensor($sensors_nagios[$ng_selected]['id']);
	    
	    $_sensor->load_from_db($conn);
	    
	    $nagios_options = $_sensor->get_nagios_credentials($conn);
		$_s_user        = $nagios_options['user'];
		$_s_pass        = $nagios_options['password'];
				
		try
		{
        		$nagios_url  = $_sensor->get_nagios_url($_s_user, $_s_pass);
        		
        		$nagios      = $nagios_url['url'];
        		$nagios_opts = $nagios_url['s_context'];
        
        		if( preg_match('/^http:\/\//', $nagios) && $_s_user != '' && $_s_pass != '') 	//Remote nagios
        		{
        			$nagios = str_replace('http://', "http://$_s_user:$_s_pass@", $nagios);
        		}
        		elseif( preg_match('/^https:\/\//', $nagios) && $nagios_opts['http']['header'] != '')	//Remote ossim sensors
        		{
        			$flag_login = TRUE;
        			$_login     = base64_encode(Util::encrypt($_s_user."####".md5($_s_pass), $conf->get_conf('remote_key')));
        
        			$_SESSION['_remote_nagios_credential'] = array($_ip, $_login);
        		}
		}
		catch(Exception $e)
       	{
       	    $flag_opts = FALSE;
       	    // Exception message ignored, it will show messages.php?msg=2
       	}
	} 
	else 	//Empty, localhost, or the own ip address, the default nagios is loaded : https:Util::get_default_admin_ip()/nagios3
	{
		$nagios = $path;
	}

	if(empty($nagios))
	{
		$flag_opts = FALSE;
	}

}
else
{	//If we don't have any sensor and we are the admin, then we can see the 'default'
	if(Session::am_i_admin()) 
	{
		$sensors_nagios[] = array(
			'id'       => '', 
			'ip'       => '', 
			'name'     => _('default'),
			'selected' => $option
		);

		$nagios = $path;
	}
}

$db->close();

if(empty($sensors_nagios))
{
    ?>
    <script type='text/javascript'>	
    $(document).ready(function(){
        
        var iframe = document.getElementById('nagios_fr');
        				
		$(iframe).attr('src', 'message.php?msg=1');        
	});
    </script>
    <?php
}
else
{
    ?>
    <div id='c_nagios'>
        	<div class='c_nagios_left'>
    			<!-- change sensor -->
    			<form id='sensors_nagios' method="GET" action="index.php">
    				<input type="hidden" name="opc" value="<?php echo $opc?>">
    				<?php echo _('Sensor');?>:  
    				<select name="sensor" onChange="change_sensor()">
    
    				<?php	
    					foreach($sensors_nagios as $s) 
    					{
    						$option  = '<option ';
    						$option .= ($s['selected']) ? " selected='selected' " : '';
    						$option .= ' value="'.$s['ip'].'">'.$s['name'].'</option>';
    
    						echo "$option\n";
    					}
    				?>
    				</select>
    			</form>
    			<!-- end change sensor -->
    
    		</div>
		
    		<div id='nagios_opt_links' class='c_nagios_right'>
    			<?php
    			if($flag_opts)
    			{
    				if ($opc == '') 
    				{ 
    					?>    
    					  <a href="<?php echo "$nagios/cgi-bin/status.cgi?host=all"?>" target="nagios"><?php echo _('Service Detail')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=hostdetail"?>" target="nagios"><?php echo _('Host Detail')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all"?>" target="nagios"><?php echo _('Status Overview')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=grid"?>" target="nagios"><?php echo _('Status Grid')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/statusmap.cgi?host=all"?>" target="nagios"><?php echo _('Status Map')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/status.cgi?host=all&servicestatustypes=248"?>" target="nagios"><?php echo _('Service Problems')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=hostdetail&hoststatustypes=12"?>" target="nagios"><?php echo _('Host Problems')?></a><br/> 
    					  <a href="<?php echo "$nagios/cgi-bin/outages.cgi"?>" target="nagios"><?php echo _('Network Outages')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=3"?>" target="nagios"><?php echo _('Comments')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=6"?>" target="nagios"><?php echo _('Downtime')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=0"?>" target="nagios"><?php echo _('Process Info')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=4"?>" target="nagios"><?php echo _('Performance Info')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=7"?>" target="nagios"><?php echo _('Scheduling Queue')?></a>
    					<?php
    				} 
    
    				if ($opc == 'reporting') 
    				{ 
    					?>
    					  <a href="<?php echo "$nagios/cgi-bin/trends.cgi"?>" target="nagios"><?php  echo _('Trends')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/avail.cgi"?>"  target="nagios"><?php echo _('Availability')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/histogram.cgi"?>" target="nagios"><?php echo _('Event Histogram')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/history.cgi?host=all"?>" target="nagios"><?php echo _('Event History')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/summary.cgi"?>" target="nagios"><?php echo _('Event Summary')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/notifications.cgi?contact=all"?>" target="nagios"><?php echo _('Notifications')?></a> | 
    					  <a href="<?php echo "$nagios/cgi-bin/showlog.cgi"?>" target="nagios"><?php echo _('Performance Info')?></a> 
    					<?php
    				} 
    			}
    			?>    
    		</div>
    		
    		<div class='clear_layer'></div>
    </div>

    <script type='text/javascript'>
    
    	function change_sensor()
    	{
    		$('#nagios_opt_links').hide()
    		var iframe = document.getElementById('nagios_fr');
    		$(iframe).attr('src', 'message.php?msg=3');
    
    		setTimeout("$('#sensors_nagios').submit();", 500);
    	}
    
    	$(document).ready(function()
    	{
    		<?php 
    		if($flag_login) 
    		{
    			echo "var nagios_url = 'message.php?msg=4'";
    		}
    		elseif($flag_opts) 
    		{
            $_nagios_path = ($nagios_link != '') ? $nagios_link : $nagios.'/cgi-bin/status.cgi?hostgroup=all';
            echo "var nagios_url = '$_nagios_path'";
    		}
    		else 
    		{
    			echo "var nagios_url = 'message.php?msg=2'";
    		}
    		?>			
    
    		var iframe = document.getElementById('nagios_fr');
    		
    		$(iframe).attr('src', nagios_url);		
    	});
    </script>
    <?php
}
?>