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


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");


$type_widget    = $winfo['wtype'];		//Widget's type.
$height         = $winfo['height'];		//Widget's height.
$widget_refresh = $winfo['refresh'];	//Widget's refresh.
$tooltip        = json_encode(is_array($tooltip) ? $tooltip : array());
$nodata_text    = ($nodata_text) ? $nodata_text : _('No data available yet.') ;
/*
    $hide_x_axis is specified in each type of pod.
*/

//If the widget is empty bcz there is no data, a message will be displayed to inform about it.
if (!is_array($data) || empty($data))
{
	echo "<table align='center' height='". ($height-20) ." px' width='100%'><tr><td style='border-bottom:none;text-align:center;font-family:arial;color:#888888;font-size:14px;font-weight:bold' valign='middle'>$nodata_text</td></tr></table>";
	
	exit;
}

//Depending on the type, we are gonna load different files and we are gonna transforms the data in different ways.
switch ($type_widget)
{
	//If the widget is a chart...
	case 'chart': 		
		
		//Getting a valid link representation for jqplot
		if ($chart_info['type'] != 'table' && is_array($links))
		{
			$links = json_encode($links);
		}

		//Depending on the chart type, we are gonna load different files and we are gonna transforms the data in different ways.
		switch ($chart_info['type'])
		{
			//Pie chart
			case 'pie':              

				$data_new = array();
				
				foreach ($data as $i=>$ocurrences)
				{
					$data_new[]  = array($label[$i], $ocurrences);
				}
				
				$data = json_encode($data_new, JSON_NUMERIC_CHECK);					

				$legend_columns   = 2;
				
				unset($serie);
				
				include '../draw/pie.php';
	   
				break;
			
			//Vertical Bar
			case 'vbar':                	
			     
			    if ($limit > 0)
			    {
    			    $data  = array_pad($data, $limit, 0);
    			    $label = array_pad($label, $limit, ' ');
			    }
			    
				$data  = json_encode($data, JSON_NUMERIC_CHECK);
				$label = json_encode($label);
						
				$legend_columns = 2;
				$serie          = ($serie != "") ? $serie : 'Serie';
				
				include '../draw/vbar.php';
	   
				break;
			
			//Horizontal Bar
			case 'hbar':                	
			
			    if ($limit > 0)
			    {
    			    $data  = array_pad($data, $limit, 0);
    			    $label = array_pad($label, $limit, ' ');
			    }
			    
				$data_new = array();
				$i        = 1;
				
				foreach ($data as $d)
				{
					$data_new[]  = array($d, $i);
					$i++;
				}
							    
				$data  = json_encode($data_new, JSON_NUMERIC_CHECK);
				$label = json_encode($label);

				$legend_columns = 2;
				
				include '../draw/hbar.php';
	   
				break;			
			
			
			//Stacked Bar
			case 'stackedbar':
			
				$ticksValue = strtoupper(json_encode($xaxis_text));
				$label      = implode(",", $label);
								
				foreach ($data as $key => $value)
				{
					$line_values  .= sprintf("line_%s = [%s]; ", $key, $value);
					$line_names[]  = sprintf("line_%s", $key);
				}

				$line_names     = "[" . implode(", ",$line_names) . "]";
			
				$legend_columns = 2;
				
			
				include '../draw/stacked.php';
				
				break;
				
			
			//Dual Vertical Bar
			case 'dual_vbar':                	
				
				$data1  = json_encode($data[0], JSON_NUMERIC_CHECK);
				$data2  = json_encode($data[1], JSON_NUMERIC_CHECK);
				$label  = json_encode($label);
				
				$serie1 = ($serie1 != "") ? $serie1 : 'Serie1';
				$serie2 = ($serie2 != "") ? $serie2 : 'Serie2';
				
				$legend_columns = 2;
				

				$colors = "'rgba(148, 207, 5, 1)', 'rgba(9, 145, 209, 1)'";	

				include '../draw/dual_vbar.php';
	   
				break;
				
			
			//Raphael
			case 'raphael':
			
				$empty        = true;
						
				$logger_url   = (is_array($logger_url) && !empty($logger_url)) ? $logger_url : array();
				$siem_url     = (is_array($siem_url) && !empty($siem_url)) ? $siem_url : array();

				//$colors       = "'#444444'";	

				if ($js == "analytics")
				{
					$trend1 = $data;				
				} 
				else
				{
					$trend1 = $data[0];
					$trend2 = $data[1];
				}
				
				include '../draw/raphael.php';				
				break;
				
				
			case 'table':
	
				include '../draw/table.php';
	
				break;
				
		}
		
		break;
		

	//Gauge
	case 'gauge':
	
		$data = $data[0];
		$v    = ($max - $min)/5;
		
		// Value in chart is 0-100 ranged, perhaps 5 must be 50 for the graph
		if ($max == 10)
		{
		    $data_angle = $data * 10;
		}
		else
		{
		    $data_angle = $data;
		}
		
		include '../draw/gauge.php';
		
		break;
	
	//If the widget is a tag cloud...
	case 'tag_cloud':
	
		$cloud = array();
		$type  = $chart_info['type'];
				
		for ($i=0; $i < count($data); $i++)
		{
			$cloud[$i]['object'] = $label[$i];
			$cloud[$i]['num']    = $data[$i];
			$cloud[$i]['title']  = $label[$i] . ' ' . _("returned a count of") . ' ' . Util::number_format_locale($data[$i]);
			$cloud[$i]['url']    = $links[$label[$i]];	
		
		} 
	
		include '../draw/tag_cloud.php';
		
		break;
}

?>
