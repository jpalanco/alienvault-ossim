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
Session::logcheck("settings-menu", "ToolsUserLog");


/* Connect to db */
$db   = new ossim_db();
$conn = $db->connect();
 

/* number of logs per page */
$ROWS        = 50;

$order       = GET('order');
$order         = ( empty($order) ) ? "date DESC" : $order;

$inf         = intval(GET('inf'));
$inf         = ( empty($inf) ) ? 0 : $inf;
$sup         = GET('sup');
$sup         = ( empty($sup) ) ? $ROWS : $sup;

$user        = GET('user');
$code        = GET('code');
$action      = GET('action');

$date_from   = GET('date_from');
$date_to     = GET('date_to');


ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE,      'illegal:' . _("order"));
ossim_valid($inf, OSS_DIGIT,                            'illegal:' . _("inf"));
ossim_valid($sup, OSS_DIGIT,                		      'illegal:' . _("order"));
ossim_valid($user, OSS_USER, OSS_NULLABLE,                'illegal:' . _("hide_closed"));
ossim_valid($code, OSS_ALPHA, OSS_NULLABLE,               'illegal:' . _("hide_closed"));
ossim_valid($action, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE,  'illegal:' . _("action"));
ossim_valid($date_from, OSS_DIGIT, OSS_NULLABLE, "\-",    'illegal:' . _("Date from"));
ossim_valid($date_to, OSS_DIGIT, OSS_NULLABLE, "\-",      'illegal:' . _("Date to"));

if (ossim_error()) 
{
    die(ossim_error());
}


$filter = '';
$usersf = array();

$users  = Session::get_users_to_assign($conn);
foreach($users as $k => $v)
{
	$usersf[$v->get_login()] = "'".$v->get_login()."'";
}


//User filter
if (empty($user))
{

	if (!Session::am_i_admin())
	{
		if (is_array($usersf) && !empty($usersf))
		{
			$filter .= " AND log_action.login in (". implode(",", $usersf) .")";
		}
		
	}
}
else
{
	if (!empty($usersf[$user]))
	{
		$filter .= " AND log_action.login = '$user'";
	}
	else
	{
		if (is_array($usersf) && !empty($usersf))
		{
			$filter .= " AND log_action.login in (". implode(",", $usersf) .")";
		}
	}
}


//Code filter
if (!empty($code))
{ 
	$filter.= " AND log_action.code = '$code'";
}

//Date filter
if (!empty($date_from) && !empty($date_to))
{
    $tzc = Util::get_tzc();
    $filter.= " AND convert_tz(log_action.date,'+00:00','".$tzc."') between '".$date_from." 00:00:00' AND '".$date_to. " 23:59:59'";
}

$count    = Log_action::get_count($conn, "WHERE 1=1".$filter);			
$log_list = Log_action::get_list($conn, $filter, " ORDER by $order", $inf, $sup);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("User action logs")?> </title>
    <meta http-equiv="refresh" content="150"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <?php require "../host_report_menu.php" ?>
  
    <script type="text/javascript">
  
    	<?php
    	if ($date_from != '') 
    	{
    		$aux = preg_split("/\-/",$date_from);
    		$y = $aux[0]; $m = $aux[1]; $d = $aux[2];
    	} 
    	else
    	{
    		$y = strftime("%Y", time() - ((24 * 60 * 60) * 30));
    		$m = strftime("%m", time() - ((24 * 60 * 60) * 30));
    		$d = strftime("%d", time() - ((24 * 60 * 60) * 30));
    		$date_from = "$y-$m-$d";
    	}
    	if ($date_to != '') 
    	{
    		$aux = preg_split("/\-/",$date_to);
    		$y2 = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
    	} 
    	else 
    	{
    		$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
    		$date_to = "$y2-$m2-$d2";
    	}
    	?>
  
        function calendar()
    	{
    		// CALENDAR
    		
    		$('.date_filter').datepicker({
                showOn: "both",
                dateFormat: "yy-mm-dd",
                buttonText: "",
                buttonImage: "/ossim/pixmaps/calendar.png",
                onClose: function(selectedDate)
                {
                    // End date must be greater than the start date
                    
                    if ($(this).attr('id') == 'date_from')
                    {
                        $('#date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else
                    {
                        $('#date_from').datepicker('option', 'maxDate', selectedDate );
                    }
                }
                
    		});
    	}	
	
        function postload() 
        { 
            calendar(); 
        }
	
    
    	$(document).ready(function() {
    		$('#view').bind('click', function() { document.forms['logfilter'].submit(); });
    	});		
		
	</script>  
</head>

<body>

 
<?php

if ( Session::am_i_admin() )
{
	?>
    <!-- filter -->
		<form name="logfilter" id="logfilter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"] ?>">
								
    		<table id='filter'>
    			
    			<thead>
        			<tr><td class="sec_title" colspan="4"><?php echo _("User Activity Filter"); ?></td></tr>
        			
        			<tr>
        				<th><?php echo _("Date range"); ?></th>
        				<th><?php echo _("User"); ?></th>
        				<th colspan='2'><?php echo _("Action"); ?></th>
        			</tr>
    			</thead>
			
    			<tbody>			
        			<tr>
        				<td style="padding:5px;">
        				    <div class="datepicker_range" style="width:220px;margin:0px auto;padding-left:20px;">
                                <div class='calendar_from'>
                                    <div class='calendar'>
                                        <input name='date_from' id='date_from' class='date_filter' type="input" value="<?php echo $date_from ?>">
                                    </div>
                                </div>
                                <div class='calendar_separator'>
                                    -
                                </div>
                                <div class='calendar_to'>
                                    <div class='calendar'>
                                        <input name='date_to' id='date_to' class='date_filter' type="input" value="<?php echo $date_to ?>">
                                    </div>
                                </div>
                            </div>
        				</td>
        		
        				<td class="center" style="padding:5px;">
        					<select name="user">
        						<?php 
        						$selected = ( $user == "" ) ? "selected='selected'" : ""; 
        						echo "<option $selected value=''>"._("All")."</option>";
        										
        						if ($session_list = Session::get_list($conn, "ORDER BY login"))
        						{
        							foreach($session_list as $session)
        							{
        								$login    = $session->get_login();
        								$selected = ( $login == $user ) ? "selected='selected'" : "";
        								echo "<option $selected value='$login'>$login</option>";
        							}
        						}
        						?>
        					</select>
        				</td>
        				
        				<td class="center" style="padding:5px;">
        					<select name="code" id='code'>
        						<?php 
        							$selected = ( $code == "" ) ? "selected='selected'" : ""; 
        							echo "<option $selected value=''>"._("All")."</option>";
        											
        							if ($code_list = Log_config::get_list($conn, "ORDER BY descr"))
        							{
        								foreach($code_list as $code_log)
        								{
        									$code_aux = $code_log->get_code();
        									$selected = ( $code_aux == $code ) ? "selected='selected'" : ""; 
        									echo "<option $selected value='$code_aux'>[". sprintf("%02d", $code_aux) . "] " . preg_replace('|%.*?%|', "?", $code_log->get_descr())."</option>";
        								 
        								}
        							}
        						?>
        					</select>
        				</td>
        			
        				<td id="view_td"><input type='button' id='view' class='small' value='<?php echo _("View")?>'/></td>
        			</tr>
             </tbody>  
        </table>
	</form>
	
	<?php
} 
?>	    
	<table class='table_data' id='log_list'>
		<thead>
			<tr>
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("date", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo _("Date"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("login", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo _("User"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("ipfrom", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo _("Source IP"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("code", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo _("Code"); ?></a>
				</th>
				
				<th>
					<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("info", $order); ?><?=(($user!="")?"&user=$user":"")?><?=(($code!="")?"&code=$code":"")?>">
					<?php echo _("Action"); ?></a>
				</th>
			</tr>
		</thead>
		
		<tbody>
		<?php
				
		if (is_array($log_list) && !empty($log_list))
		{
			foreach($log_list as $log)
			{
				?>		
				<tr>
					<td class="cell_padding"><?php echo $log->get_date();?></td>
					<td class="cell_padding"><?php echo $log->get_login(); ?></td>
					<td class="cell_padding">
						<div id="<?php echo $log->get_from();?>;<?php echo $log->get_from(); ?>" class="HostReportMenu" style="display:inline"><?php echo $log->get_from(); ?></div>
					</td>
					<td class="cell_padding"><?php echo $log->get_code(); ?></td>
					<td class="cell_padding"><?php echo (preg_match('/^[A-Fa-f0-9]{32}$/',$log->get_info())) ? preg_replace('/./','*',$log->get_info()) : $log->get_info(); ?></td>
				</tr>
				<?php
			} 
		}
		else
		{
			echo "<tr><td colspan='5' class='td_empty'>"._("No data was found for this filter")."</td></tr>";
		}
		?>
		</tbody>
	</table>
	
	<div class='dt_footer'>
	    
	    <div class='t_entries'>   
		<?php
		
        $inf_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup - $ROWS) . "&inf=" . ($inf - $ROWS). "&user=" .
                              $user . "&code=" . $code . "&date_from=" . $date_from . "&date_to=" . $date_to;
        
        $sup_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup + $ROWS) . "&inf=" . ($inf + $ROWS). "&user=" .
                               $user . "&code=" . $code . "&date_from=" . $date_from . "&date_to=" . $date_to;		
					
		if ($sup < $count)
		{    			
			printf("</span>"._("Showing %d to %d of %d entries")."</span>", $inf, $sup, $count);
			 			
		}
		else
		{
			printf("</span>"._("Showing %d to %d of %d entries")."</span>", $inf, $count, $count);
		}
		?>
	    </div>		
		
		<div class='t_paginate'>  
		<?php							
		if ($inf >= $ROWS)
		{
			echo "<a href='$inf_link'>&lt; "._("Previous")."</a>";
		}
		else
		{
			echo "<span>&lt; "._("Previous")."</span>";
		}
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;";
		
		if ($sup < $count)			
		{
			echo "<a href='$sup_link'>"._("Next")." &gt;</a>";		
		}
		else
		{
			echo "<span>"._("Next")." &gt;</span>";
		}	
		?>
		</div>
	</div>		
</body>
</html>

<?php	
$db->close();
?>
