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


Session::logcheck("dashboard-menu", "ControlPanelExecutive");


function exit_radar()
{

	echo " 
	<table style='height:100%;width:100%;vertical-align:middle;text-align:center'>
		<tr>
			<td><span style='font-family:arial;color:#888888;font-size:12px;font-weight:bold' >". _('No data available yet') . "</span></td>
		</tr>
	</table>
	";
		
	exit -1;
}

$db   = new ossim_db();
$conn = $db->connect();


// Getting correspondence between sensor ip and device
$ip_device = array();
$device_ip = array();
$ip_name   = array();
$dev_perms = array();

$query1    = "SELECT d.id,s.ip as sensor_ip, s.name, HEX(s.id) as sensor_id FROM alienvault_siem.device d, alienvault.sensor s WHERE d.sensor_id=s.id";

if (!$rs = & $conn->Execute($query1)) 
{
    print $conn->ErrorMsg();
    exit();
}

while (!$rs->EOF) 
{
	$_sip = inet_ntop($rs->fields["sensor_ip"]);
	$_dev = $rs->fields["id"];
	$_sid = $rs->fields["sensor_id"];

	if (Session::sensorAllowed($_sid)) 
	{
		$device_ip[$_dev]        = $_sip;
		$ip_device[$_sip][$_dev] = $_dev; 
		$ip_name[$_sip]          = $rs->fields["name"];
		$dev_perms[]             = $_dev; 
	}

    $rs->MoveNext();
}


// Allowed Sensors filter
$criteria_sql = "WHERE plugin.id=acid_event.plugin_id AND device_id IN ('". implode("','", $dev_perms ) ."')";

//$query_where
$query_where = Security_report::make_where($conn, '', '', array(),  array());
$query_where = preg_replace('/AND \(timestamp.*/', '', $query_where);


$query = "SELECT DISTINCT device_id, plugin_id, name, sum( acid_event.cnt ) as event_cnt FROM alienvault.plugin, alienvault_siem.ac_acid_event as acid_event $criteria_sql $query_where GROUP BY device_id, plugin_id ORDER BY event_cnt DESC";
//print_r($query);


if (!$rs = & $conn->Execute($query)) 
{
    print $conn->ErrorMsg();
    exit();
}

$s              = 0;
$p              = 0;
$data           = array();
$already_plugin = array();
$already_sensor = array();
$plugin_ids     = array();
$header         = array();

while (!$rs->EOF) 
{
	$plugin = $rs->fields["name"];
	$plugin = preg_replace("/ossec-.*/", "ossec", $plugin);
	$sip    = $device_ip[$rs->fields["device_id"]];

    // Post limit: 10 sensors / 10 plugins
    if (($s < 10 && $p < 10) || $data[$sip][$plugin] > 0) 
    {
            $data[$sip][$plugin]+= $rs->fields["event_cnt"];

            $plugin_ids[$plugin] = ($plugin == 'ossec') ? '7000-7999' : $rs->fields["plugin_id"];

            if (!$already_plugin[$plugin]) { 
            	$p++; 
            }

            if (!$already_sensor[$sip]) { 
            	$s++; 
            }

            $already_plugin[$plugin]++;
            $already_sensor[$sip]++;

            $header[$plugin] = $plugin;
    }

    $rs->MoveNext();
}

$legend = array();
$label  = array();
$s_ips  = array();
$s_devs = array();
$events = '';
$i      = 1;

if(is_array($data) && !empty($data)) 
{
	
	foreach ($data as $sip => $values) 
	{
		$sensor  = $ip_name[$sip];

		if (is_array($ip_device[$sip])) 
		{
			$devices = implode(',', $ip_device[$sip]);
		}
		
        if ($sensor == "")
        {
            continue;
        }
		
		ksort($values);
		$arr = array();
		
		foreach ($header as $plugin) 
		{
            if ($plugin == "")
            {
                continue;
            }
			
			$id    = $plugin_ids[$plugin];
			$arr[] = "['$id',". (($values[$plugin] > 0) ? $values[$plugin] : 0) ."]";
			
			if ($i == 1) 
			{
				$label[] = "{label: '".strtoupper($plugin)."'}";
			}
		}
			
		$legend[]  = "{ label: '$sensor',	data: d$i, spider: {show: true} }"; 
		$events   .= "var d$i = [ ".implode(",",$arr) ."];\n";
		
		$s_ips[]   = "'$sensor': '$sip'"; 
		$s_devs[]  = "'$sensor': '$devices'"; 
		 
		$i++;
	}

	if( empty($legend) ) 
	{
		exit_radar();
	}
	
	$legend = implode(",\n",$legend);
	$label  = implode(",\n",$label);
	$s_ips  = implode(",",$s_ips);
	$s_devs = implode(",",$s_devs);

} 
else 
{
	exit_radar();
}

session_write_close();

$forensic_url = Menu::get_menu_url("/ossim/forensics/base_qry_main.php?&hmenu=Forensics&smenu=Forensics&clear_allcriteria=1&sort_order=time_d&plugin=PPPP&sensor=SSSS&sip=IIII", 'analysis', 'security_events');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo _("Radar Chart")?></title>
	
    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                             'def_path' => TRUE),
            array('src' => '/dashboard/js/jquery.flot.js',              'def_path' => FALSE),
            array('src' => '/dashboard/js/jquery.flot.highlighter.js',  'def_path' => FALSE),
            array('src' => '/dashboard/js/jquery.flot.spider.js',       'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>
	
	<style>
	
    	td.r_legend
    	{
        	vertical-align:middle;
        	width:35%;
        	text-align:left;
        	padding-left:10px;
    	}
    	
    	td.r_chart
    	{
        	vertical-align:middle;
        	width:65%;
        	text-align:center;
    	}
    	
        .legendLabel
		{
			padding: 2px;
		}
		.legendLabel a 
		{
			color: #666666 !important;
		    padding-left: 0px;
			text-decoration: none;
		}
		.legendColorBox
		{
			vertical-align: middle;
			padding: 2px;
		}
		
		/* CLONED FROM jquery.jqplot.css */
        .jqplot-table-legend-swatch 
        {
            width: 12px;
            height: 12px;
            -moz-border-radius: 6px;
            -webkit-border-radius: 6px;
            border-radius: 6px;
        }
	
	</style>
	
	<script id="source" language="javascript" type="text/javascript">
	
		var plot, data, options;

		
		
		var s_ips  = {<?php echo $s_ips ?>};
		var s_devs = {<?php echo $s_devs ?>};

		var forensic_link = "<?php echo $forensic_url ?>";
				
		$(function () {
			
			<?php echo $events ?>

			options = { 
				series:{
					spider:{ 
						active: true,
						highlight: {
							mode: "area"
						},
						legs: { 
							data: 
							[
								<?php echo $label ?>
							],
							legScaleMax: 1,
							legScaleMin:0.8,
							font: "12px Helvetica",
							fillStyle: "#999999",
						},
						spiderSize: 0.8,
						pointSize: 3,
						scaleMode: 'others'								
					}
				},
				grid:{
					hoverable: true,
					clickable: true,
					tickColor: "rgba(0,0,0,0.2)",
					mode: "radar",
				},
				legend: {
					show: true,
					noColumns: 1,
					position: "ne",
					labelFormatter: function(label, series) 
					{
						var sdev = encodeURIComponent(s_devs[label]);
						var sip  = encodeURIComponent(s_ips[label]);
				
						var link = forensic_link.replace('PPPP', '').replace('SSSS', sdev).replace('IIII', sip);

						return '<a href="javascript:;" onclick=\'click_handler("'+ link +'");\' style="font:12px Arial, Sans-Serif;">' + label + '</a>';
					},
					container: $('#legend')
				}
			};

			data = [ 
				<?php echo $legend ?>
			];
			
			
			plot = $.plot($("#placeholder"), data , options);

		});

		
		function showTooltip(x, y, contents) 
		{
			$('<div id="tooltip">' + contents + '</div>').css(
			{
				position: 'absolute',
				display: 'none',
				top: y + 5,
				left: x + 5,
				border: 'none',
				font: '11px Arial, Sans-Serif',
				padding: '5px',
				'background-color': '#88C557',
				opacity: 0.80,
				'-moz-border-radius': '8px',
				'-khtml-border-radius': '8px', 
				'-webkit-border-radius': '8px',
				'border-radius': '8px',
			}).appendTo("body").fadeIn(200);
		}
		
		function click_handler(url)
    	{
        	if(url != '')
        	{
            	if (typeof top.av_menu.load_content == 'function')
				{
					top.av_menu.load_content(url);
				}
				else
				{
					top.frames['main'].location.href = url;
				}
        	}
    	}
		
		$(document).ready(function() 
		{
			$("#placeholder").bind("plotclick", function (event, pos, item) 
			{
				if(item)
				{
					var data      = item.series.data;
					var index     = item.dataIndex;
					var sensor    = item.series.label;	
					
					var plugin_id = data[index][0];
					var sdev      = s_devs[sensor];
					var sip       = s_ips[sensor];			

					var link = forensic_link.replace('PPPP', plugin_id).replace('SSSS', sdev).replace('IIII', sip);

                    click_handler(link);
				}

			});
			
			$("#placeholder").bind("plothover", function (event, pos, item) 
			{
				if(item)
				{
					$('body').css('cursor','pointer'); 
					
					var data   = item.series.data;
					var label  = item.series.spider.legs.data;
					var index  = item.dataIndex

					var value  = data[index][1];
					var label  = label[index].label;
					
					var message = "<b>Sensor:</b> " + item.series.label + "<br><b>Source:</b> " + label + "<br><b>Value:</b> " + value;

					$("#tooltip").remove();
					
					showTooltip(pos.pageX, pos.pageY, message);
					
				}
				else
				{
					$("#tooltip").remove();
					$('body').css('cursor','default');
				}
				
			});
		});
		
	</script>
	
</head>
<body>
	<table class='transparent container d_chart_container' align='center' width='100%' height='<?php echo $height ?>px' >
		<tr>
			<td class='r_legend'>
				<div id='legend' style="overflow:auto;"></div>
			</td>
			<td class='r_chart'>
				<div id="placeholder" style="width:100%;height:<?php echo $height ?>px;text-align:center;margin:0 auto"></div>
			</td>
		</tr>
	</table>
</body>
</html>
<?php
$db->close();