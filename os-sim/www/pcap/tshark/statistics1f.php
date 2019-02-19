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


require_once ('av_init.php');
Session::logcheck("environment-menu", "TrafficCapture");


require_once("includes/tshark.inc");

$tshark = unserialize($_SESSION['tshark']);
$filter = $tshark->get_filter();

$values=$tshark->get_data_for_graphs("All");
$nvalues = 'nvalues = [[';
foreach($values as $value){
    $nvalues.=$value[6].',';
}
$nvalues = preg_replace("/,$/","],[",$nvalues);
foreach($values as $value){
    $nvalues.=$value[7].',';
}
$nvalues = preg_replace("/,$/","],[",$nvalues);
foreach($values as $value){
    $nvalues.=$value[7].',';
}
$nvalues = preg_replace("/,$/","",$nvalues);
$nvalues .= ']],';

$realdata = 'realdata = [[';
foreach($values as $value){
    $realdata.=$value[3].',';
}
$realdata = preg_replace("/,$/","],[",$realdata);
foreach($values as $value){
    $realdata.=$value[5].',';
}
$realdata = preg_replace("/,$/","],[",$realdata);
foreach($values as $value){
    $realdata.=$value[5].',';
}
$realdata = preg_replace("/,$/","",$realdata);
$realdata .= ']],';

$labelx = 'labelx = ["';
foreach($values as $value){
    $labelx.=$value[0].'","';
}
$labelx = preg_replace("/,$/","",$labelx);
$labelx .= '"],';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <link rel="stylesheet" href="style/demo.css" type="text/css" media="screen">
        <style type="text/css" media="screen">
            #holder {
                height: 230px;
                margin: -200px 0 0 -380px;
                width: 770px;
            }
        </style>
        <script src="js/raphael/raphael.js" type="text/javascript" charset="utf-8"></script>
        <script src="js/raphael/popup.js"></script>	
        <script type="text/javascript" charset="utf-8">
            Raphael.fn.drawGrid = function (x, y, w, h, wv, hv, color) {
                color = color || "#000";
                var path = ["M", Math.round(x) + .5, Math.round(y) + .5, "L", Math.round(x + w) + .5, Math.round(y) + .5, Math.round(x + w) + .5, Math.round(y + h) + .5, Math.round(x) + .5, Math.round(y + h) + .5, Math.round(x) + .5, Math.round(y) + .5],
                    rowHeight = h / hv,
                    columnWidth = w / wv;
                for (var i = 1; i < hv; i++) {
                    path = path.concat(["M", Math.round(x) + .5, Math.round(y + i * rowHeight) + .5, "H", Math.round(x + w) + .5]);
                }
                for (i = 1; i < wv; i++) {
                    path = path.concat(["M", Math.round(x + i * columnWidth) + .5, Math.round(y) + .5, "V", Math.round(y + h) + .5]);
                }
                return this.path(path.join(",")).attr({stroke: color});
            };
            window.onload = function () {
                var r = Raphael("holder", 790, 300),
                    e = [],
                    clr = [],
                    color = ["#8cc221","#123456"],
                    values = [],
                    now = 0,
                    grid = r.drawGrid(10, 40, 724, 210, 10, 8, "#EEE"),
                    c = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
					c2 = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
                    bg = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
					bg2 = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
                    txt = {font: '12px Helvetica, Arial', fill: "#666"},
                    txt1 = {font: '12px Helvetica, Arial', fill: "#666"},
                    width = 790,
                    blanket = r.set(),
                    label = r.set(),
                    is_label_visible = false,
                    leave_timer,
                    dotsy = [];
                    
                    label.push(r.text(60, 12, "XXXXXX Security events").attr(txt1).attr({fill: color[0]}));
                    label.push(r.text(60, 27, "31 January 2011").attr(txt).attr({fill: color[1]}));
                    label.hide();
                    var frame = r.popup(100, 100, label, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
                    
                    var x = 0 ;
                    for (var i = 0; i < labelx.length-1; i++) {
                        x+=25;
                        t = r.text(x, 272, labelx[i]).attr(txt).rotate(60).toBack();
                    }
                    
                function randomPath(length, j) {
                    var path = "",
                        x = 10,
                        y = 0;
                    dotsy[j] = dotsy[j] || [];
                    for (var i = 0; i < length; i++) {
                        dotsy[j][i] = Math.round(nvalues[j][i]);// * 20);
                        if (i) {
                            path += "C" + [x + 10, y, (x += 25) - 10, (y = 248 - dotsy[j][i]), x, y];
                        } else {
                            path += "M" + [10, (y = 248 - dotsy[j][i])];
                        }
                        
                        
                        var dot = r.circle(x, y, 0).attr({fill: "#F2F2F2", stroke: color[j], "stroke-width": 2});
                        blanket.push(r.rect(25 * i, 245 - dotsy[j][i], 25, 300).attr({stroke: "none", fill: "#fff", opacity: 0}));
                        var rect = blanket[blanket.length - 1];
                        (function (x, y, data, lbl, dot, data2) {
                            var timer, i = 0;
                            rect.hover(function () {
                                clearTimeout(leave_timer);
                                var side = "right";
                                if (x + frame.getBBox().width > width) {
                                    side = "left";
                                }
                                var ppp = r.popup(x, y, label, side, 1);
                                frame.show().stop().animate({path: ppp.path}, 200 * is_label_visible);
                                label[0].attr({text: data + " Packets"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                                label[1].attr({text: data2 + " Filter Packets"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                                dot.attr("r", 6);
                                is_label_visible = true;
                            }, function () {
                                dot.attr("r", 0);
                                leave_timer = setTimeout(function () {
                                    frame.hide();
                                    label[0].hide();
                                    label[1].hide();
                                    is_label_visible = false;
                                }, 1);
                            });
                        })(x, y, realdata[0][i], label[i], dot, realdata[1][i]);
                    }
                    return path;
                }
				
				
                
                for (var i = 0; i < nvalues.length-1; i++) {
                    values[i] = randomPath(30, i);
                    clr[i] = color[i]; //Raphael.getColor(1);
                }
				
                c.attr({path: values[0], stroke: clr[0]});
                c2.attr({path: values[1], stroke: clr[1]});
                bg.attr({path: values[0] + "L735,250 10,250z", fill: clr[0]});
                bg2.attr({path: values[1] + "L735,250 10,250z", fill: clr[1]});
				
                var animation = function () {
                    var time = 500;
                    c.animate({path: values[0], stroke: clr[0]}, time, "<>");
                    c2.animate({path: values[1], stroke: clr[1]}, time, "<>");
                    bg.animate({path: values[0] + "L735,250 10,250z", fill: clr[0]}, time, "<>");
                    bg2.animate({path: values[1] + "L735,250 10,250z", fill: clr[1]}, time, "<>");

                };
            };
        </script>
    </head>
    <body>
        <div id="holder"></div>
    </body>
</html>
