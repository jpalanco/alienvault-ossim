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


Session::logcheck("analysis-menu", "IncidentsReport");

$by   = GET('by');

ossim_valid($by, OSS_ALPHA, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Target"));

if (ossim_error()) {
    die(ossim_error());
}


$conf    = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require "$jpgraph/jpgraph.php";
require "$jpgraph/jpgraph_bar.php";

$db      = new ossim_db();
$conn    = $db->connect();


$shared  = new DBA_shared(GET('shared'));

if ($by == "ticketsClosedByMonth")
{
     
        $titley       = _("Month") . '-' . _("Year");
        $titlex       = _("Num. Tickets");
        $title        = '';
        $width        = 650;
        
        $user         = $shared->get("TicketsStatus4_user");
        $assets       = $shared->get("TicketsStatus4_assets");
                
                
        $ticket_closed_by_month = Incident::incidents_closed_by_month($conn, $assets, $user);
                                
        if( is_array($ticket_closed_by_month) && !empty($ticket_closed_by_month) )
        {
                foreach($ticket_closed_by_month as $event_type => $months)
                {
                        foreach ($months as $month => $occurrences)
                                $data[$month] =  (int)$data[$month] + (int)$occurrences;
                }
        }
        
        
        
        $labelx = array_keys($data);
        $datay  = array_values($data);
        
}
elseif ($by == "resolution_time") 
{
        
        $user   = $shared->get("TicketsStatus5_user");
        $assets = $shared->get("TicketsStatus5_assets");
        $args   = $shared->get("TicketsStatus5_args");
        
        $list   = Incident::incidents_by_resolution_time($conn, $assets, $user, $args);
                
        $ttl_groups = array("1"=>0, "2"=>0, "3"=>0, "4"=>0, "5"=>0, "6"=>0);
        
        $total_days = 0;
        $day_count  = null;
                        
        foreach ($list as $incident) 
        {
                        $ttl_secs = $incident->get_life_time('s');
                        $days = round($ttl_secs/60/60/24);
                        $total_days += $days;
                        $day_count++;
                        if ($days < 1) $days = 1;
                        if ($days > 6) $days = 6;
                        @$ttl_groups[$days]++;
        }

        $datay  = array_values($ttl_groups);

        $labelx = array( _("1 Day"), _("2 Days"), _("3 Days"), _("4 Days"), _("5 Days"), _("6+ Days")); 
                
    $title = '';
    if ($day_count < 1) $day_count = 1;
    $titley = _("Duration in days.") . " " . _("Average:") . " " . $total_days / $day_count;
    $titlex = _("Num. Tickets");
   
    $width = 650;
} 
else
{
    die(_("Invalid Graph"));
}

$background = "white";
$color      = "#FAC800";

// Setup graph
$graph = new Graph($width, 250, "auto");
$graph->SetScale("textlin");
$graph->SetMarginColor($background);
$graph->img->SetMargin(40, 30, 20, 40);
//$graph->SetShadow();
//Setup Frame
$graph->SetFrame(true, "#ffffff");
// Setup graph title
$graph->title->Set($title);
$graph->title->SetFont(FF_FONT1, FS_BOLD);
$bplot = new BarPlot($datay);
$bplot->SetWidth(0.6);

// color@transparencia
$bplot->SetFillColor(array($color."@0.3"));
//
$bplot->SetColor(array($color."@1"));
//
$graph->Add($bplot);
$graph->xaxis->SetTickLabels($labelx);
//$graph->xaxis->SetLabelAngle(40); // only with TTF fonts
$graph->title->Set($title);
$graph->xaxis->title->Set($titley);
$graph->yaxis->title->Set($titlex);
$graph->Stroke();
?>
