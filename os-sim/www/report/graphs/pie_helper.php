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

class pieHelper {
    public static function create_percentage_labels($data) {
        //create array counter for minor values (minor is counted up to 2%)
	$total = array_sum($data);
        $labels = array();
        foreach($data as $value) {
            $labels[] = round(($value/$total)*100,1);
        }
        return $labels;
    }

    public static function draw_plot(
        $data,$colors,$legend=null,$legend_columns=null,$width=350,$height=600,$left=0.5,$top=0.25,$size=0.3,$maincolor="#fafafa",$legend_vertical="bottom",$legend_horizontal="center",$legend_left=0.5,$legend_top=0.95) {
        $labels = self::create_percentage_labels($data);
        $conf = $GLOBALS["CONF"];
        $jpgraph = $conf->get_conf("jpgraph_path");
        require_once "$jpgraph/jpgraph.php";
        require_once "$jpgraph/jpgraph_pie.php";
        // Setup graph
        $graph = new PieGraph($width, $height, "auto");
        $graph->SetAntiAliasing();
        $graph->SetMarginColor($maincolor);
        $graph->title->SetFont(FF_FONT1, FS_BOLD);
        // Create pie plot
        $p1 = new PiePlot($data);
        $p1->SetSize($size);
        if (count($labels)<=1) {
            $left+=0.07;
        }
        $p1->SetCenter($left,$top);
        if ($legend) {
            $p1->SetLegends($legend);
            $graph->legend->SetPos($legend_left,$legend_top,$legend_horizontal,$legend_vertical);
            $graph->legend->SetFrameWeight(0);
            $graph->legend->SetFillColor($maincolor);
            $graph->legend->SetShadow(FALSE);
       }
       if ($legend_columns) {
           $graph->legend->SetColumns($legend_columns);
       }
       $p1->SetLabels($labels);
       $p1->SetLabelPos(1);
       $graph->SetFrame(false);
       $p1->SetSliceColors($colors);
       $graph->Add($p1);
       $graph->Stroke();
       unset($graph);
    }
}

