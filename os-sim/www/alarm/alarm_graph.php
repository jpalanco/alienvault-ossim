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

set_time_limit(0);
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once 'av_init.php';
require_once 'alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$db      = new ossim_db(TRUE);
$conn    = $db->connect();

$intents                     = Alarm::get_intents($conn);
$strategies                  = Alarm::get_strategies($conn);
list($graph,$tooltip,$dates) = Alarm::get_alarm_graph_by_taxonomy($conn);

$intents_order               = array(5,3,1,4,2);

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<title><?php echo _("Alarm Graph")?> </title>

    <link rel="stylesheet" href="../style/alarm/graph.css"/>

    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery.graphup.js"></script>
	<script type="text/javascript">

	var id = 0;

    function clean_dates(date_string)
    {
        var date_range = '';
        var dates      = date_string.split(';');
        var dayfrom    = dates[0].replace(/\s.*/,'');
        var dayto      = dates[1].replace(/\s.*/,'');
        if ((dates[0] == dates[1]) || (dayfrom == dayto && dates[0].match(/00:00:00/) && dates[1].match(/23:59:59/)))
        {
            date_range = dayfrom;                
        }
        else
        {
            date_range = dates[0].replace(':00:00','h')+' &mdash; '+dates[1].replace(':59:59','h');
        }        
        return date_range;
    }

	function handlers() 
	{
	    $(".tr-triangle").hide();

		// First table
		$('#table1 td').graphup({
		    colorMap: [[38, 177, 210], [38, 177, 210]],
			painter: 'bubbles',
			bubblesDiameter: 62, // px
			bubbleMinSize: 8,
			bubbleCellSize: 24,
			callBeforePaint: function() 
			{
			    $(this).attr("id", "td_" + id);
			    $(this).addClass("table_td");
			    $(this).data("alarms", $(this).html());

				// Hide all values under 50%
				if (this.data('percent') < 9999999) 
				{
					this.text('');
				}

                id++;
			}
		});

        // Bubble tooltip
		$(".table_td").hover(
    		function () 
    		{
    		    if($(this).data("alarms")>0)
    		    {
                    var ox,oy = 0;
                    var w     = 90; /* alarm-info class with=180px / 2 = 90 */
                    if (typeof(parent.get_container_offset) == 'function')
                    {
                        var pos = parent.get_container_offset();
                        ox      = pos.left;
                        oy      = pos.top;
                    }
    
                    var strat = $(this).data("stats").split(';');
                    var dates = clean_dates($(this).data("range"));
                	var pos   = $(this).find("div.bubble").offset();
                	
                	if (pos == null)
                	{
                	    var pos   = $(this).find("div.bubble_on").offset();
                        if (pos == null)
                        {
                	        return;
                	    }
                	    else
                	    {
                        	ox  += pos.left + ( $(this).find("div.bubble_on").outerWidth() / 2) - w;
                            oy  += pos.top  + $(this).find("div.bubble_on").outerHeight() + 18;
                	    }
                	}
                	else
                	{
                    	ox  += pos.left + ( $(this).find("div.bubble").outerWidth() / 2) - w;
                    	oy  += pos.top  + $(this).find("div.bubble").outerHeight() + 18;
                    	
                	}
    
                	var a_div = "<div class='alarm-info' style='top:" + oy + "px;left:" + ox + "px'>";
                    a_div    += "    <div class='alarm-info-triangle'></div>";
                    a_div    += "    <div class='alarms-number'>" + $(this).data("alarms") + "</div>";
            		a_div    += "    <div class='alarms-date'>"+dates+"</div>";
            		a_div    += "        <table class='alarms-table'>";
            		a_div    += "            <tr><td class='alarms-top'><?php echo _("TOP 5 STRATEGIES") ?></td></tr>";
            		
            		for (var i = 0, len = strat.length; i < len; i++)
            		{
            		    a_div += "           <tr><td class='alarms-td'>"+strat[i]+"</td></tr>";
            		}
            		
            		a_div    += "        </table>";
            		a_div    += "</div>"
    
                    //console.log(a_div);
    
                    if (typeof(parent.draw_tooltip) == 'function')
                    {
                        parent.draw_tooltip(a_div);
                    }
                }
                
                $(this).addClass('td_bubble_hover');
                $(".bubble", this).addClass('bubble_hover');
                
            },
            function () 
            {
               if (typeof(parent.remove_tooltip) == 'function')
               {
                   parent.remove_tooltip();
               }
               
               $(this).removeClass('td_bubble_hover');
               $(".bubble, .bubble_on", this).removeClass('bubble_hover');
               
            }
        );

        // Over intent
		$('#table1 tr').mouseover(function() 
		{
            $(".th-highlighted").removeClass("th-highlighted");
            $(".tr-highlighted").removeClass("tr-highlighted");

            var tr_id = $(this).attr("id");
            var intent = $("#" + tr_id + " th").attr("intent");

            $("#" + tr_id).addClass("tr-highlighted");
            $("#" + tr_id + " th").addClass("th-highlighted");
            
        });

        // Click and filter
		$('#table1 td').click(function() 
		{
            var range      = $(this).data("range");
            var dates      = clean_dates(range);
            var tr_id      = $(this).closest('tr').attr("id");
            var intent     = $("#" + tr_id + " th").attr("intent");
            var intent_txt = $("#" + tr_id + " th").attr("intent_txt");
            
            $('.bubble_on').addClass('bubble').removeClass('bubble_on');        
            $('.bubble',$(this)).addClass('bubble_on').removeClass('bubble');        
            $('.tr_intent').hide();
            $(this).closest('tr').show();
            
            if (typeof(parent.set_graph_height) == 'function')
	    	{
	    	    parent.set_graph_height('<?php echo 76 + 50 ?>'); // th height + header height
	        }       
	                 
            if (typeof(parent.filter_by_intent) == 'function')
            {
                var bc_text = intent_txt + ' ('+ dates+')';
                parent.filter_by_intent(intent, bc_text,range);
            }
            
        });

        $('#table1').mouseout(function() 
        {
             $(".tr-highlighted").removeClass("tr-highlighted");
             $(".th-highlighted").removeClass("th-highlighted");
             $(".tr-triangle").hide();
        });

    }
	</script>

    <?php
        if ($dates["range"] == "days")
        {
    ?>
    <style tyle="text/css">
        #table_header td   { width: 159px; }
    </style>          
    <?php 
        }
        elseif ($dates["range"] == "month")
        {
    ?>
    <style tyle="text/css">
        #table_header td   { width: 279px; }
    </style>
    <?php        
        }
    ?>
</head>
<body>

    <div class="outer" id="outer">
        <div class="inner" id="inner">
        <?php
        //
        // HOURS
        //
        if ($dates["range"] == "hours")
        {
            // Day template
            $max_days  = $dates["diff"];
            $from      = gmdate("U",strtotime($dates["min"].' GMT'));
            $to        = gmdate("U",strtotime($dates["max"].' GMT'));
            
            if ($max_days<7)
            {
                $from    -= (7-$max_days)*86400;
                $max_days = 7; // I dont like, but it's an adjust to fill content
            }
            
            $max_hours = 6;
            
            for ($i=0;$i<$max_hours;$i++)
            {
            	$hour_range[] = 0;
            	$str_range[]  = '';
            }
    
        ?>
    	    <table id="table_header">
                <tr id="table_header_row">
                    <th class="todd">
                        <?php echo $max_days." "._("DAYS") ?>
                    </th>
                    <td align='center' style='width:5px'></td>
            		<?php
            		// Days
            		for ($d=0;$d<=$max_days;$d++)
            	    {
            	        $day   = gmdate("y-m-d",$from+(86400*$d));
            	        $class = ($d%2 == 0) ? "class='todd'" : '';
            	        echo "<td align='center' $class>$day</td>";
            	    }
                    ?>
                    <td class='end_column'></td>
                </tr>
    	    </table>
    
    	    <table id="table1">
    	    <?php
    		// Data
    		$howmany_intents = 0;
    		$class           = "teven";
            for ($ii=0;$ii<5;$ii++) // Intents
            {
                $day_class = "teven";
                
                $i = $intents_order[$ii];
    		    
    		    if ( empty($graph[$i]) ) // Always shows all
    		    {
        		    $graph[$i] = array();
    		    }
    		    
    			$howmany_intents++;
    			
    			$class = ($class=="todd") ? "teven" : "todd";
    			?>
    			
                <tr id="tr<?php echo $i ?>" class="tr_intent">
                    <th id="tr<?php echo $i ?>_th" class="<?php echo $class ?>_intent" intent="<?php echo $i ?>" intent_txt="<?php echo $intents[$i] ?>" style="padding:0 10px">
                        <div style="position:relative;top:5px">
                            <img src='style/img/<?php echo $i ?>.png' border='0'><br/>
                            <?php echo $intents[$i] ?><div class="tr-triangle"></div>
                        </div>
                    </th>
                    <td align='center' style='width:5px'></td>
                    
        	        <?php
                	for ($d=0;$d<=$max_days;$d++)
                	{
        	        	$day   = gmdate("Y-m-d",$from+(86400*$d));
        	        	$data  = $hour_range;
        	        	$data1 = $str_range;
        	        	
        	        	// Switch column style
        	        	$day_class = ($day_class=="todd") ? "teven" : "todd";
        	        	
        	        	if (!empty($graph[$i][$day]))
        	        	{
        		        	foreach ($graph[$i][$day] as $hour => $occurrences)
        		        	{
        			        	$data[$hour] = $occurrences;
        			        	$categories  = $tooltip[$i][$day][$hour];
        			        	
        			        	arsort($categories);
        			        	
        			        	$str = array();
        			        	foreach ($categories as $cn => $occ)
        			        	{
        				        	$str[] = $strategies[$cn];
        				        	
                                    if (count($str)>=5) 
                                    {
                                       break;
                                    }
                                    
        			        	}
        			        	$data1[$hour] = implode(";",$str);
        		        	}
        	        	}
        	        	
        	        	// Draw alarm occurrences
        	        	$inter = 24/$max_hours;
        	        	
        	        	for ($h=0;$h<$max_hours;$h++)
        	        	{
        	        	            $border = ($h+1==$max_hours) ? 'right-border' : '';
                            $from_h = $inter*$h;
                            $to_h   = ($inter*$h)+$inter-1;
                            
                            if ($from_h<10)
                            {
                                $from_h = '0'.$from_h;
                            }
                            
                            if ($to_h<10)
                            {
                                $to_h = '0'.$to_h;
                            }
                            
                            echo '<td class="'.$day_class.' '.$border.'" data-stats="'.$data1[$h].'" data-range="'.$day.' '.$from_h.':00:00;'.$day.' '.$to_h.':59:59">'.$data[$h].'</td>';		        	
        	        	}
                	}
        	        ?>
    	            <td class='end_column'></td>
    	        </tr>

    	        <?php
    	        $scroll_left = 120 + ($d*$h*24) + 30; // tr width + (tds * td width) + latest td width
    		}
    		?>
            </table>
        <?php
        }
        //
        // DAYS
        //
        elseif ($dates["range"] == "days")
        {
            // Day template
            $max_days  = $dates["diff"];
            $from      = gmdate("U",strtotime($dates["min"].' GMT'));
            $to        = gmdate("U",strtotime($dates["max"].' GMT'));
            
            $week_from = gmdate("N",$from);
            if ($week_from>1)
            {
                $from     -= 86400*($week_from-1);
                $max_days += $week_from-1;
            }
            
            if ($max_days<42)
            {
                $from    -= (42-$max_days)*86400;
                $max_days = 42; // I dont like, but it's an adjust to fill content
            }
        ?>
    	    <table id="table_header">
                <tr>
                    <th class="todd">
                        <?php echo floor($dates["diff"] / 7) + (($dates["diff"] % 7 == 0) ? 0 : 1)." "._("WEEKS") ?>
                    </th>
                    <td align='center' style='width:5px'></td>
            		<?php
            		// Days
            		$i = 0;
            		for ($d=0;$d<=$max_days;$d+=7)
            	    {
            	        $day_from = gmdate("y-m-d",$from+(86400*$d));
            	        $day_to   = gmdate("y-m-d",$from+(86400*($d+6)));
            	        $class = (++$i%2 == 0) ? "class='todd'" : '';
            	        echo "<td align='center' $class>$day_from &mdash; $day_to</td>";
            	    }
                    ?>
                    <td class='end_column'></td>
                </tr>
    	    </table>
    
    	    <table id="table1">
    	    <?php
    		// Data
    		$howmany_intents = 0;
    		$class           = "teven";
    		for ($ii=0;$ii<5;$ii++) // Intents
    		{
    		    $i = $intents_order[$ii];
    		    if (empty($graph[$i])) // Always shows all
    		    {
        		    $graph[$i] = array();
    		    }
    			$howmany_intents++;
    			
    			$class = ($class=="todd") ? "teven" : "todd";
    			$range_class = "teven";
    			?>
    			
                <tr id="tr<?php echo $i ?>" class="tr_intent">
                    <th id="tr<?php echo $i ?>_th" class="<?php echo $class ?>_intent" intent="<?php echo $i ?>" intent_txt="<?php echo $intents[$i] ?>" style="padding:0 10px">
                        <div style="position:relative;top:5px">
                            <img src='style/img/<?php echo $i ?>.png' border='0'><br/>
                            <?php echo $intents[$i] ?><div class="tr-triangle"></div>
                        </div>
                    </th>
                    <td align='center' style='width:5px' id='last_td'></td>
        	        <?php	
        	        $var_border = 1;
        	        
                	for ($d=0;$d<=$max_days;$d++)
                	{
        	        	$day   = gmdate("Y-m-d",$from+(86400*$d));
        	        	$data  = 0;
        	        	$data1 = '';
        	        	
        	        	if ( !empty($graph[$i][$day]) )
        	        	{
        		        	$data       = $graph[$i][$day];
        		        	$categories = $tooltip[$i][$day];
        		        	
        		        	arsort($categories);
        		        	
        		        	$str = array();
        		        	foreach ($categories as $cn => $occ)
        		        	{
        			        	$str[] = $strategies[$cn];
        			        	
        			        	if (count($str)>=5) 
        			        	{
            			        	break;
        			        	}
        		        	}
        		        	
        		        	$data1 = implode(";", $str);
        	        	}
        	        	
        	        	if ($var_border == 7)
        	        	{
            	        	$border     = 'right-border';
            	        	$var_border = 0;
        	        	}
        	        	else
        	        	{
            	        	$border = '';
        	        	}
        	        	
        	        	// Draw alarm occurrences
        		        echo '<td class="'.$range_class.' '.$border.'" data-stats="'.$data1.'" data-range="'.$day.';'.$day.'">'.$data.'</td>';
        		        
        		        // Switch column style
        		        if ($var_border == 0)
        		        {
        		            $range_class = ($range_class=="todd") ? "teven" : "todd";
        		        }
        		        
        		        $var_border++;
                	}
                	
                	$td_left = 7 - ($var_border - 1);
                	
                	if ($td_left > 0 && $td_left < 7)
                	{
                    	echo "<td align='center' class='right-border' style='width:" . $td_left * 23 . "px'></td>";
                	}
                    
        	        ?>
                    <td class='end_column'></td>
                </tr>
                <?php
                $scroll_left = 120 + ($d*24) + 30; // tr width + (tds * td width) + latest td width
    		}
		?>
        </table>

    <?php
     }
     //
     // MONTHS
     //
     else
     {
        // Year/Months template
        $from       = gmdate("Y",strtotime($dates["min"].' GMT'));
        $to         = gmdate("Y",strtotime($dates["max"].' GMT'));
        $months     = 12;
        $max_years  = $to-$from+1;
       
        if ($max_years<4)
        {
            $from     -= (4-$max_years);
            $max_years = 4; // I dont like, but it's an adjust to fill content
        }
        
        for ($i=0;$i<$months;$i++)
        {
        	$month_range[] = 0;
        	$str_range[]   = '';
        }

    ?>
	    <table id="table_header">
            <tr>
                <th class="todd">
                    <?php echo $max_years." "._("MONTHS") ?>
                </th>
                <td align='center' style='width:5px'></td>
                <?php
                // Years
                for ($d=0;$d<$max_years;$d++)
                {
                    $year  = $from+$d;
                    $class = ($d%2 == 0) ? "class='todd'" : '';
                    echo "<td align='center' $class>$year</td>";
                }
                ?>
                <td class='end_column'></td>
            </tr>
        </table>

	    <table id="table1">
	    <?php
		// Data
		$howmany_intents = 0;
		$class           = "teven";
		
		for ($ii=0;$ii<5;$ii++) // Intents
		{
		    $i = $intents_order[$ii];
		    
		    if ( empty($graph[$i]) ) // Always shows all
		    {
    		    $graph[$i] = array();
		    }
		    
			$howmany_intents++;
			$class = ($class=="todd") ? "teven" : "todd";
			$year_class = "todd";
			?>
            <tr id="tr<?php echo $i ?>" class="tr_intent">
                <th id="tr<?php echo $i ?>_th" class="<?php echo $class ?>_intent" intent="<?php echo $i ?>" intent_txt="<?php echo $intents[$i] ?>" style="padding:0 10px">
                    <div style="position:relative;top:5px">
                        <img src='style/img/<?php echo $i ?>.png' border='0'><br/>
                        <?php echo $intents[$i] ?><div class="tr-triangle"></div>
                    </div>
                </th>
                <td align='center' style='width:5px'></td>
    	        <?php
            	for ($d=0;$d<$max_years;$d++)
            	{
    	        	$year  = $from+$d;
    	        	$data  = $month_range;
    	        	$data1 = $str_range;
    	        	
    	        	if (!empty($graph[$i][$year]))
    	        	{
    		        	foreach ($graph[$i][$year] as $month => $occurrences)
    		        	{
    			        	$data[$month] = $occurrences;
    			        	$categories   = $tooltip[$i][$year][$month];
    			        	
    			        	arsort($categories);
    			        	
    			        	$str = array();
    			        	foreach ($categories as $cn => $occ)
    			        	{
    				        	$str[] = $strategies[$cn];
    				        	
    				        	if (count($str)>=5)
    				        	{
        				            break;	
    				        	}
    			        	}
    			        	
    			        	$data1[$month] = implode(";",$str);
    		        	}
    	        	}
    	        	// Draw alarm occurrences
    	        	for ($m=0;$m<$months;$m++)
    	        	{
        	        	$border = ($m+1==$months) ? 'right-border' : '';
        	        	$cm     = $m+1;
        	        	
        	        	if ($cm<10)
        	        	{
            	        	$cm = '0'.$cm;
        	        	}
        	        	
        	        	$from_d = $year.'-'.$cm.'-01';
        	        	$to_d   = gmdate('Y-m-d', strtotime('last day of '.$year.'-'.$cm)+86400);
                        echo '<td class="'.$year_class.' '.$border.'" data-stats="'.$data1[$m].'" data-range="'.$from_d.';'.$to_d.'">'.$data[$m].'</td>';

                // Switch column style
                if ($m + 1 == $months)
                {
                    $year_class = ($year_class=="todd") ? "teven" : "todd";
                }
    	        	}
            	}
            	?>
	            <td class='end_column'></td>
	        </tr>
	        <?php
	        $scroll_left = 120 + ($d*$m*24) + 30; // tr width + (tds * td width) + latest td width
		}
		?>
        </table>


    <?php
    }
    ?>

        </div>
    </div>

    <script type="text/javascript">
    
    	$(document).ready(function() 
    	{
    	    handlers();
    	    
            if (typeof(parent.set_graph_height) == 'function')
            {
                parent.set_graph_height('<?php echo ($howmany_intents*76)+ 50 ?>');
            }
            
            $('#inner').scrollLeft(<?php echo $scroll_left ?>);
            
        });
        
	</script>

</body>
</html>
