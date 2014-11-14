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


set_time_limit(180);
ini_set('memory_limit', '1024M');
ini_set('session.bug_compat_warn','off');

require_once 'av_init.php';


Session::logcheck("analysis-menu", "IncidentsReport");

$by   = GET('by');

ossim_valid($by, OSS_ALPHA, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Target"));

if (ossim_error()) {
    die(ossim_error());
}

// Define colors
$color_list= array(
                        '#D6302C',
                        '#3933FC',
                        'green',
                        'yellow',
                        'pink',
                        '#40E0D0',
                        '#00008B',
                        '#800080',
                        '#FFA500',
                        '#A52A2A',
                        '#228B22',
                        '#D3D3D3'
);

$conf    = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require "$jpgraph/jpgraph.php";
require "$jpgraph/jpgraph_bar.php";

$db      = new ossim_db();
$conn    = $db->connect();


$shared  = new DBA_shared(GET('shared'));

if ($by == "ticketsByTypePerMonth")
{
     
        $titley       = _("Month") . '-' . _("Year");
        $titlex       = _("Num. Tickets");
        $title        = '';
        $width        = 650;
        
        $user         = $shared->get("TicketsStatus4_user");
        $assets       = $shared->get("TicketsStatus4_assets");
                
        $final_values = array();        
        
        $ticket_by_type_per_month = Incident::incidents_by_type_per_month($conn, $assets, $user);       
        if( is_array($ticket_by_type_per_month) && !empty($ticket_by_type_per_month) )
                {
                        foreach($ticket_by_type_per_month as $event_type => $months)
                        {
                                $final_values[$event_type] = implode(",", $months);
                        }
                        
                        $event_types = array_keys($ticket_by_type_per_month);
                }       
        

        $labelx = array_keys($ticket_by_type_per_month[$event_types[0]]);

		$final_values = array_slice($final_values, 0, 18);
}
else
{
    die(_("Invalid Graph"));
}

        $background = "white";
        $color      = "navy";
        $color2     = "navy";


        // Setup graph
        $graph = new Graph($width, 350, "auto");
        $graph->SetScale("textlin");
        $graph->SetMarginColor($background);
        $graph->img->SetMargin(40, 30, 20, 160);

        //Setup Frame
        $graph->SetFrame(true, "#ffffff");

        // Setup graph title
        //$graph->title->Set($title);
        //$graph->title->SetFont(FF_FONT1, FS_BOLD);

        $bar_array = array();
        $i = 0;

        foreach($final_values as $title => $values){

			$i %= count($color_list);
	
			$datay = explode(",", $values);
			$bplot = new BarPlot($datay);
			
			$bplot->SetWidth(0.7);

			$bplot->SetFillColor($color_list[$i]."@0.5");
			$bplot->SetColor($color_list[$i]."@1");
			
			$bplot->SetLegend($title);


			$bar_array[] = $bplot;
			
			$i++;
        }
		
        // Create the grouped bar plot
        $gbplot = new AccBarPlot($bar_array);
        
        $gbplot->SetShadow($color."@0.9",6,5);
        $gbplot->SetWidth(0.6);
        
        $graph->Add($gbplot);

        $graph->xaxis->SetTickLabels($labelx);
        $graph->xaxis->title->Set($titley);
        $graph->yaxis->title->Set($titlex);
        
        // Adjust the legend position
		$graph->legend->SetColumns(3);
		$graph->legend->SetPos(0.5,0.95,'center','bottom');
		$graph->legend->SetShadow('#fafafa',0);
		$graph->legend->SetFrameWeight(0);
		$graph->legend->SetFillColor('#fafafa');
		

        $graph->Stroke();
		unset($graph);
?>