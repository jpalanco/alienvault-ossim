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

require_once 'av_init.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$unique_id                    = uniqid("alrm_");
$prev_unique_id               = $_SESSION['alarms_unique_id'];

$_SESSION['alarms_unique_id'] = $unique_id;


$db   = new ossim_db(TRUE);
$conn = $db->connect();
/* GET VARIABLES FROM URL */
//$rows = 100;
$rows = 10;

$delete 		    = POST('delete');
$delete_group 	    = POST('delete_group');
$close 			    = POST('close');
$delete_day 	    = POST('delete_day');
$order 			    = POST('order');
$src_ip			    = POST('src_ip');
$dst_ip 		    = POST('dst_ip');
$backup_inf 	    = $inf = POST('inf');
$sup 			    = POST('sup');
$hide_closed 	    = (POST('hide_closed')!="" || POST('unique_id')!="") ? POST('hide_closed') : GET('hide_closed');
$hide_closed 	    = ($hide_closed == "1") ? 1 : 0;
$date_from 			= POST('date_from');
$date_to 			= POST('date_to');
$num_alarms_page 	= POST('num_alarms_page');
$disp 				= POST('disp');  // Telefonica disponibilidad hack
$group 				= POST('group'); // Alarm group for change descr
$new_descr 			= POST('descr');
$action 			= POST('action');
$show_options 		= POST('show_options');

$autorefresh        = "";
$refresh_time       = "";

if (isset($_POST['search']))
{
    unset($_SESSION['_grouped_alarm_autorefresh']);
    if (isset($_POST['autorefresh']))
    {
        $autorefresh  = (POST('autorefresh') != '1') ? 0 : 1;
        $refresh_time = POST('refresh_time');
        $_SESSION['_grouped_alarm_autorefresh'] = $refresh_time;
    }
}
else
{
    if ($_SESSION['_grouped_alarm_autorefresh'] != '')
    {
        $autorefresh  = 1;
        $refresh_time = $_SESSION['_grouped_alarm_autorefresh'];
    }
}


$alarm 				= POST('alarm');
$param_unique_id 	= POST('unique_id');
$group_type 		= POST('group_type') ? POST('group_type') : "name";
$query 				= (POST('query') != "") ? POST('query') : "";
$directive_id 		= POST('directive_id');
$intent 	    	= intval(POST('intent'));
$sensor_query 		= POST('sensor_query');
$num_events 		= POST('num_events');
$num_events_op 		= POST('num_events_op');
$no_resolv 			= intval(POST('no_resolv'));
$tag 				= POST('tag');

ossim_valid($param_unique_id, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,                      'illegal:' . _("Unique id"));
ossim_valid($disp,            OSS_DIGIT, OSS_NULLABLE, 						           'illegal:' . _("Disp"));
ossim_valid($order,           OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE,           'illegal:' . _("Order"));
ossim_valid($delete,          OSS_HEX, OSS_NULLABLE, 						           'illegal:' . _("Delete"));
ossim_valid($delete_group,    OSS_DIGIT, OSS_NULLABLE, 				                   'illegal:' . _("Delete group"));
ossim_valid($close,           OSS_HEX, OSS_NULLABLE, 						           'illegal:' . _("Close"));
ossim_valid($open,            OSS_HEX, OSS_NULLABLE, 						           'illegal:' . _("Open"));
ossim_valid($delete_day,      OSS_ALPHA, OSS_NULLABLE,					               'illegal:' . _("Delete_day"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE, 				           'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE, 				           'illegal:' . _("Dst_ip"));
ossim_valid($inf,             OSS_DIGIT, OSS_NULLABLE,						           'illegal:' . _("Inf"));
ossim_valid($sup,             OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Order"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE, 					               'illegal:' . _("Hide_closed"));
ossim_valid($autorefresh,     OSS_DIGIT, OSS_NULLABLE, 					               'illegal:' . _("Autorefresh"));
ossim_valid($date_from,       OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 		               'illegal:' . _("From date"));
ossim_valid($date_to,         OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 			           'illegal:' . _("To date"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE, 				                   'illegal:' . _("Field number of alarms per page"));
ossim_valid($new_descr,       OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("Descr"));
ossim_valid($show_options,    OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Show_options"));
ossim_valid($refresh_time,    OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Refresh_time"));
ossim_valid($alarm,           OSS_HEX, OSS_PUNC, OSS_NULLABLE,						   'illegal:' . _("Alarm"));
ossim_valid($sensor_query,    OSS_HEX, OSS_NULLABLE,                                   'illegal:' . _("Sensor_query"));
ossim_valid($query,           OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE, 	   'illegal:' . _("Query"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Directive_id"));
ossim_valid($intent,          OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Intent"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE, 								   'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE, 							       'illegal:' . _("Num_events_op"));
ossim_valid($no_resolv,       OSS_DIGIT, OSS_NULLABLE, 								   'illegal:' . _("No_resolv"));
ossim_valid($tag,             OSS_DIGIT, OSS_NULLABLE, 								   'illegal:' . _("Tag"));
ossim_valid($action,          OSS_ALPHA, OSS_NULLABLE, OSS_PUNC,                       'illegal:' . _("Action"));

if (ossim_error())
{
    die(ossim_error());
}

$tags      = Tags::get_list($conn);
$tags_html = Tags::get_list_html($conn);

if (empty($order))
{
	$order = " timestamp DESC";
}

if ($num_alarms_page)
{
    $rows = $num_alarms_page;
}

if (empty($inf) || $inf < 1)
{
	$inf = 0;
}

if (!$sup)
{
	$sup = $rows;
}

if (empty($show_options) || ($show_options < 1 || $show_options > 4))
{
    $show_options = 1;
}

if (empty($refresh_time) || ($refresh_time != 30000 && $refresh_time != 60000 && $refresh_time != 180000 && $refresh_time != 600000))
{
    $refresh_time = 60000;
}

//Options
$selected1 = $selected2 = $selected3 = $selected4 = "";

if ($show_options == 1)
{
    $selected1 = 'selected="selected"';
}

if ($show_options == 2)
{
    $selected2 = 'selected="selected"';
}

if ($show_options == 3)
{
    $selected3 = 'selected="selected"';
}

if ($show_options == 4)
{
    $selected4 = 'selected="selected"';
}

$hide_check = ($hide_closed) ? 'checked="checked"' : "";

$refresh_sel1 = $refresh_sel2 = $refresh_sel3 = $refresh_sel4 = "";

if ($refresh_time == 30000)
{
    $refresh_sel1 = 'selected="selected"';
}


if ($refresh_time == 60000)
{
    $refresh_sel2 = 'selected="selected"';
}


if ($refresh_time == 180000)
{
    $refresh_sel3 = 'selected="selected"';
}


if ($refresh_time == 600000)
{
    $refresh_sel4 = 'selected="selected"';
}

if (POST('take') != "")
{
	if (!ossim_valid(POST('take'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("take")))
	{
	   exit();
	}
	if (check_uniqueid($prev_unique_id,$param_unique_id))
	{
	   Alarm_groups::take_group ($conn, POST('take'), $_SESSION["_user"]);
	}
	else
	{
	   die(ossim_error("Can't do this action for security reasons."));
	}
}

if (POST('release') != "")
{
	if (!ossim_valid(POST('release'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("release")))
	{
	   exit();
	}
	if (check_uniqueid($prev_unique_id,$param_unique_id))
	{
	   Alarm_groups::release_group ($conn, POST('release'));
	}
	else
	{
	   die(ossim_error("Can't do this action for security reasons."));
	}
}

if ($group != "")
{

	if (!ossim_valid($new_descr,  OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("descr")))
	{
	   exit();
	}

	if (!ossim_valid($group, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("group")))
	{
	   exit();
	}


	Alarm_groups::change_descr ($conn, $new_descr, $group);
}

if (POST('close_group') != "")
{
	if (!ossim_valid(POST('close_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("close_group")))
	{
    	exit();
	}

	$group_ids = explode(',', POST('close_group'));

    if (check_uniqueid($prev_unique_id,$param_unique_id))
    {
	   foreach($group_ids as $group_id)
	   {
	       Alarm_groups::change_status($group_id, "closed");
	   }
    }
    else
    {
        die(ossim_error("Can't do this action for security reasons."));
    }
}

if (POST('open_group') != "")
{
	if (!ossim_valid(POST('open_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("open_group")))
	{
	   exit();
    }
    if (check_uniqueid($prev_unique_id,$param_unique_id))
    {
        Alarm_groups::change_status(POST('open_group'), "open");
    }
    else
    {
        die(ossim_error("Can't do this action for security reasons."));
    }
}

if (POST('delete_group') != "")
{
	if (!ossim_valid(POST('delete_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("delete_group")))
	{
	    exit();
	}

	$group_ids = explode(',', POST('delete_group'));
    if (check_uniqueid($prev_unique_id,$param_unique_id))
    {
    	foreach($group_ids as $group_id)
    	{
    	   Alarm_groups::delete_group ($conn, $group_id, $_SESSION["_user"]);
        }
    }
    else
    {
        die(ossim_error("Can't do this action for security reasons."));
    }
}

if (POST('action') == "open_alarm")
{
    if (check_uniqueid($prev_unique_id,$param_unique_id))
    {
        Alarm::open($conn, POST('alarm'));
    }
    else
    {
        die(ossim_error("Can't do this action for security reasons."));
    }
}

if (POST('action') == "close_alarm")
{
    if (check_uniqueid($prev_unique_id,$param_unique_id))
    {
        Alarm::close($conn, POST('alarm'));
    }
    else
    {
        die(ossim_error("Can't do this action for security reasons."));
    }
}

if (POST('action') == "delete_alarm")
{
    if (check_uniqueid($prev_unique_id,$param_unique_id))
    {
        Alarm::delete($conn, POST('alarm'));
    }
    else
    {
        die(ossim_error("Can't do this action for security reasons."));
    }
}



$sensors = Av_sensor::get_list($conn, array(), FALSE, TRUE);


//Autocompleted
$autocomplete_keys = array('hosts');
$hosts_str         = Autocomplete::get_autocomplete($conn, $autocomplete_keys);


$db_groups = Alarm_groups::get_dbgroups($conn);

list($alarm_group, $count) = Alarm_groups::get_grouped_alarms($conn, $group_type, $show_options, $hide_closed, $date_from, $date_to, $src_ip, $dst_ip, $sensor_query, $query, $directive_id, $intent, $num_events, $num_events_op, $tag, "LIMIT $inf, $rows",true);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("Control Panel")?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">

	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>

	<script type='text/javascript' src='/ossim/js/notification.js'></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>

	<!-- JQuery tipTip: -->
    <script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/alarm/console.css"/>    
    <link rel="stylesheet" type="text/css" href="/ossim/style/datepicker.css"/>

	<?php require '../host_report_menu.php';?>

	<script type="text/javascript">

		var open       = false;
		var is_toggled = 0; // Count of toggled groups (for disable autorefresh)
		var st_alarms  = new Array();
		var flah_bg    = false;
	
	
		function GB_onhide(url)
		{
    		if (url.match(/newincident/))
    		{
        		document.location.href="../incidents/index.php?m_opt=analysis&sm_opt=tickets&h_opt=tickets"
    		}
		}
		
		function greybox2()
    	{
            $("a.greybox2").click(function()
            {
                var t = this.title || $(this).text() || this.href;

                GB_show(t,this.href, 600,'85%');

                return false;
            });
        }

        function refresh_function()
        {
    		if (is_toggled < 1)
    		{
    			form_submit();
    		}
    	}


        <?php
        foreach ($alarm_group as $group)
        {
            echo "st_alarms['".$group['group_id']."'] = 0;\n";
        }
        ?>


        /* Groups */

		function toggle_group(group_id,ip_src,ip_dst,time,from,similar)
		{
			document.getElementById(group_id+from).innerHTML = "<div style='padding: 10px 0px'><img src='../pixmaps/loading3.gif'/><span style='margin-left: 5px;'><?php echo _("Loading alarms")?>...</span></div>";;

            var hide_closed = (st_alarms[group_id] == 1) ? "0" : "<?php echo $hide_closed?>";
            var src_ip = (ip_src!="") ? ip_src : "<?php echo $src_ip ?>";
            var dst_ip = (ip_dst!="") ? ip_dst : "<?php echo $dst_ip ?>";

            $.ajax({
				type: "GET",
				url: "alarm_group_response.php?tag=<?php echo $tag ?>&from="+from+"&group_id="+group_id+"&unique_id=<?php echo $unique_id ?>&name="+group_id+"&ip_src="+src_ip+"&ip_dst="+dst_ip+"&timestamp="+time+"&hide_closed=<?php echo $hide_closed ?>&sensor_query=<?php echo $sensor_query ?>&date_from=<?php echo $date_from ?>&date_to=<?php echo $date_to ?>&no_resolv=<?php echo $no_resolv ?>&similar="+similar+"&num_events=<?php echo $num_events ?>&num_events_op=<?php echo $num_events_op ?>&directive_id=<?php echo $directive_id ?>",
				data: "",
				success: function(msg){
					is_toggled++;
					//alert (msg);
					   
				    // Prevents table odd-event css inherits
					$("#"+group_id+from).css('background', 'none');
										
					document.getElementById(group_id+from).innerHTML = msg;

					plus = "plus"+group_id;

					document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','"+similar+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='1'/></a>";

					$(".auto_display").each(function(){
						var alarm_data = $(this).attr("id").replace("eventplus", "");
						var tmp = alarm_data.split("-")
						var backlog_id = tmp[0];
						var event_id   = tmp[1];
						toggle_alarm(backlog_id, event_id);
					});

					$('.repinfo').tipTip({attribute:"txt", defaultPosition: "left"});

					$('.alarm_netlookup').tipTip({
                        defaultPosition: "top",
                        maxWidth: "auto",
                        edgeOffset: 3,
                        content: function (e) {

                            var ip_data = $(this).attr('data-title');                               

                            $.ajax({
                                type: 'GET',
                                data: 'ip='+ip_data,
                                url: 'alarm_netlookup.php',
                                success: function (response) {

                                    e.content.html(response); // the var e is the callback function data (see above)
                                }
                            });

                            return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.
                        }
                    });

					greybox2();

					$('.tip').tipTip({maxWidth:'300px'});

					$('.td_alarm_name').each(function(key, value)
                    {
                        var content = $(this).find('div').html();

                        $(this).tipTip({content: content, maxWidth:'300px'});
                    });

                    if (typeof(load_contextmenu) != "undefined")
                    {
                        load_contextmenu();
                    }
                }
			});
		}

		function untoggle_group(group_id,ip_src,ip_dst,time,similar)
		{
			is_toggled--;
			plus = "plus"+group_id;

			document.getElementById(plus).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+ip_src+"','"+ip_dst+"','"+time+"','','"+similar+"');\"><strong><img src='../pixmaps/plus-small.png' border='0'/></strong></a>";

			document.getElementById(group_id).innerHTML = "";
		}

		function opencloseAll()
		{
			if (!open)
			{
				<?php
				foreach ($alarm_group as $group)
				{
				    ?>
				    toggle_group('<?=$group['group_id']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>','','<? echo ($group_type == "similar") ? "1" : "" ?>');
				    <?php
				}
				?>

				open = true;
				document.getElementById('expandcollapse').src='../pixmaps/minus.png';
			}
			else
			{
				<?php
				foreach ($alarm_group as $group)
				{
				    ?>
				    untoggle_group('<?=$group['group_id']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>','<? echo ($group_type == "similar") ? "1" : "" ?>');
				    <?php
				}
				?>
				open = false;
				document.getElementById('expandcollapse').src='../pixmaps/plus.png';
			}
		}


		function check_background_tasks(times){

        	var bg = false;
        	
        	var atoken = Token.get_token("alarm_operations");
        	
        	$.ajax({
                type: "POST",
                url: "alarm_ajax.php?token="+atoken,
                async: false,
                dataType: "json",
                data: {"action": 7 },
                success: function(data)
                {
                	if(typeof(data) == 'undefined' || data.error == true)
                   	{
                   		notify('<?php echo _("Unable to check background tasks") ?>', 'nf_error');
                   	}
                   	else
                   	{
                   		if(data.bg)
                   		{
                   			bg = true;
                   		}
                   	}
                },
                error: function(){
                	notify('<?php echo _("Unable to check background tasks") ?>', 'nf_error');
                }
            });

            if(bg)
            {
            	if(!flah_bg)
            	{
            		var h = $.getDocHeight();
            		h     = (h != '') ? h+'px' : '100%';
	            	var layer = "<div id='bg_container' style='width:100%;height:" + h + ";position:absolute;top:0px;left:0px;'></div>";
	            	$('body').append(layer);
	            	show_loading_box('bg_container', '<?php echo Util::js_entities(_("Alarm task running in background. This process could take a while.")) ?>', '');
	            	flah_bg     = true;
          		}

            	timeout = (times < 5) ? 2000 : 10000;
            	setTimeout('check_background_tasks('+ (times+1) +');',timeout);
            }
            else
            {
            	if(flah_bg)
            	{
            		form_submit();
            	}
            }
        }

        function change_descr(objname)
		{
			var descr;
			descr = document.getElementsByName(objname);
			descr = descr[0];

			document.getElementById('group').value = objname.replace("input","");
			document.getElementById('descr').value = descr.value;

            form_submit();
		}

		function send_descr(obj ,e)
		{
			var key;

			if (window.event)
			{
				key = window.event.keyCode;
			}
			else if (e)
			{
				key = e.which;
			}
			else
			{
				return;
			}

			if (key == 13)
			{
				change_descr(obj.name);
			}
		}

		function open_group(group_id)
		{
			// GROUPS
			$('#lock_'+group_id).html("<img src='../pixmaps/loading.gif' width='16'/>");

			$.ajax({
				type: "GET",
				url: "alarm_group_response.php?only_open=1&group1="+group_id,
				data: "",
				success: function(msg){
					form_submit();
					$('.repinfo').tipTip({attribute:"txt", defaultPosition: "left"});
                }
			});
		}

		function release_group(group_id, inf, sup)
		{
			$('#action').val('change_page');
			$('#inf').val(inf);
			$('#sup').val(sup);
			$('#release').val(group_id);
			form_submit();
			return false;
		}

		function take_group(group_id, inf, sup)
		{
			$('#action').val('change_page');
			$('#inf').val(inf);
			$('#sup').val(sup);
			$('#take').val(group_id);
			form_submit();
			return false;
		}

		function close_group(group_id)
		{
			// GROUPS
			$('#lock_'+group_id).html("<img src='../pixmaps/loading.gif' width='16'/>");

            $.ajax({
				type: "GET",
				url: "alarm_group_response.php?only_close=1&group1="+group_id,
				data: "",
				success: function(msg){
					form_submit();
                }
			});
		}

		function close_groups()
		{
            // FILTERS
            var filters = "";

            if($("#sensor_query").val()!="")
            {
                filters += "&sensor_query="+$("#sensor_query").val();
            }

            if($("#src_ip").val()!="")
            {
                filters += "&ip_src="+$("#src_ip").val();
            }

            if($("#dst_ip").val()!="")
            {
                filters += "&ip_dst="+$("#dst_ip").val();
            }

            if($("#date_from").val()!="")
            {
                filters += "&date_from="+$("#date_from").val();
            }

            if($("#date_to").val()!="") {
                filters += "&date_to="+$("#date_to").val();
            }

            if($("#tag").val()!="")
            {
                filters += "&tag="+$("#tag").val();
            }

            if($("#directive_id").val()!="")
            {
                filters += "&directive_id="+$("#directive_id").val();
            }

            if($("#alarm_name").val()!="")
            {
                filters += "&alarm_name="+$("#alarm_name").val();
            }

			// ALARMS
			var params = "";
			$(".alarm_check").each(function()
			{
				if ($(this).is(':checked')) {
					params += "&"+$(this).attr('name')+"=1";
				}
			});

			// GROUPS
			var selected_group = "";
			var group = document.getElementsByName("group");
			var index = 0;

			for(var i = 0; i < group.length; i++)
			{
				if(group[i].checked)
				{
					selected_group += "&group"+(index+1)+"="+group[i].value;
					index++;
				}
			}

			if (selected_group.length == 0 && params == "")
			{
				alert("<?php echo Util::js_entities(_("Please, select the groups or any alarm to close"));?>");
				return;
			}

			$('#delete_data').html('<?php echo _("Closing grouped alarms ...") ?>');
			$('#info_delete').show();

			if (params != "")
			{
                //alert("1");
				$.ajax({
					type: "POST",
					url: "alarms_check_delete.php",
					data: "background=1&only_close=1&unique_id=<?php echo $unique_id ?>"+params,
					success: function(msg){

						if (selected_group != "")
						{
							$.ajax({
								type: "GET",
								url: "alarm_group_response.php?only_close="+index+selected_group,
								data: "",
								success: function(msg){
									$('#delete_data').html('<?php echo _("Reloading data ...") ?>');
									form_submit();
								}
							});
						}

						form_submit();
					}
				});
			}
			else
			{
                //alert("alarm_group_response.php?only_close="+index+selected_group+filters);
				$.ajax({
					type: "GET",
					url: "alarm_group_response.php?only_close="+index+selected_group+filters,
					data: "",
					success: function(msg){
						$('#delete_data').html('<?php echo _("Reloading data ...") ?>');
						form_submit();
					}
				});
			}

			return false;
		}

		function bind_grouped_alarms_handlers()
        {
            if ($("#t_grouped_alarms tbody input[type=checkbox]:checked").length >= 1)
            {
                $('#b_delete_selected').removeAttr("disabled"); 
                $('#b_close_selected').removeAttr("disabled"); 

                <?php
                if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
                {
                    ?>
                    $('#b_delete_selected').off('click');
                    $('#b_delete_selected').click(function(){
                        var confirm_msg = '<?php echo Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to continue?"));?>';

                        if (confirm(confirm_msg))
                        {
                            bg_delete();
                        }
                    });
                    <?php
                }
                ?>
                $('#b_close_selected').off('click');
                $('#b_close_selected').click(function(){
                    close_groups();
                });
            }
            else
            {
                $('#b_delete_selected').off('click');
                $('#b_close_selected').off('click');

                $('#b_delete_selected').attr('disabled', 'disabled');
                $('#b_close_selected').attr('disabled', 'disabled');
            }
        }

		<?php
        if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
        {
            ?>
            function bg_delete() {

                // FILTERS
                var filters = "";

                if($("#sensor_query").val()!="")
                {
                    filters += "&sensor_query="+$("#sensor_query").val();
                }

                if($("#src_ip").val()!="")
                {
                    filters += "&ip_src="+$("#src_ip").val();
                }

                if($("#dst_ip").val()!="")
                {
                    filters += "&ip_dst="+$("#dst_ip").val();
                }

                if($("#date_from").val()!="")
                {
                    filters += "&date_from="+$("#date_from").val();
                }

                if($("#date_to").val()!="")
                {
                    filters += "&date_to="+$("#date_to").val();
                }
                if($("#tag").val()!="")
                {
                    filters += "&tag="+$("#tag").val();
                }

                if($("#directive_id").val()!="")
                {
                    filters += "&directive_id="+$("#directive_id").val();
                }

                if($("#alarm_name").val()!="")
                {
                    filters += "&alarm_name="+$("#alarm_name").val();
                }

                // ALARMS
                var params = "";
                $(".alarm_check").each(function()
                {
                    if ($(this).is(':checked')) {
                        params += "&"+$(this).attr('name')+"=1";
                    }
                });

                // GROUPS
                var selected_group = "";
                var group = document.getElementsByName("group");
                var index = 0;

                for(var i = 0; i < group.length; i++)
                {
                    if(group[i].checked)
                    {
                        selected_group += "&group"+(index+1)+"="+group[i].value;
                        index++;
                    }
                }

                if (selected_group == "" && params == "")
                {
                    alert("<?php echo Util::js_entities(_("Please, select the groups or any alarm to close"));?>");
                    return;
                }

                $('#info_delete').show();

                if (params != "")
                {
                    $.ajax({
                        type: "POST",
                        url: "alarms_check_delete.php",
                        data: "background=1&unique_id=<?php echo $unique_id ?>"+params,
                        success: function(msg){
                            if (selected_group != "")
                            {
                                $.ajax({
                                    type: "GET",
                                    url: "alarm_group_response.php?only_delete="+index+selected_group,
                                    data: "",
                                    success: function(msg){
                                        $('#delete_data').html('<?php echo _("Reloading data ...") ?>');
                                        form_submit();
                                    }
                                });
                            }

                            form_submit();
                        }
                    });
                }
                else
                {
                    $.ajax({
                        type: "GET",
                        url: "alarm_group_response.php?only_delete="+index+selected_group,
                        data: "",
                        success: function(msg){
                            $('#delete_data').html('<?php echo _("Reloading data ...") ?>');
                            form_submit();
                        }
                    });
                }
            }

            function delete_all_groups()
            {
                if ($('#no_groups').length >= 1)
                {
                    return;
                }

                if(confirm('<?php echo  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?"))?>'))
                {

                    var query_string = $('#queryform').serialize()+"&delete_all=1";
                        query_string = query_string.replace("&src_ip", "&ip_src");
                        query_string = query_string.replace("&dst_ip", "&ip_dst");

                    $.ajax({
                        type: "GET",
                        url: "alarm_group_response.php?"+query_string,
                        beforeSend: function(xhr) {
                            $('#info_delete').show();
                        },
                        success: function(msg){
                            $('#delete_data').html('<?php echo _("Reloading data ...") ?>');
                            form_submit();
                        }
                    });
                }
            }

            <?php
        }
        ?>


        /* Alarms */

		function toggle_alarm (backlog_id,event_id)
		{
			var td_id = "eventbox"+backlog_id+"-"+event_id;
			var plus  = "eventplus"+backlog_id+"-"+event_id;
			document.getElementById(td_id).innerHTML = "<img src='../pixmaps/loading.gif' width='16'/>";
			$.ajax({
				type: "GET",
				url: "events_ajax.php?backlog_id="+backlog_id,
				data: "",
				success: function(msg){
					//alert (msg);
					document.getElementById(td_id).innerHTML = msg;
					document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/minus-small.png' border='0' alt='plus'/></a>";

					GB_TYPE = 'w';
					$("a.greybox").click(function()
					{
						var t = this.title || $(this).text() || this.href;

						GB_show(t,this.href,450,'90%');

						return false;
					});


					$('.td_event_name').each(function(key, value)
                    {
                        var content = $(this).find('div').html();

                        if (typeof(content) != 'undefined' && content != '' && content != null)
                        {
                            $(this).tipTip({content: content, maxWidth:'300px'});
                        }
                    });

                    $('.td_date').each(function(key, value)
                    {
                        var content = $(this).find('div').html();

                        if (typeof(content) != 'undefined' && content != '' && content != null)
                        {
                            $(this).tipTip({content: content, maxWidth:'300px'});
                        }
                    });


					load_contextmenu();
				}
			});
		}

		function untoggle_alarm(backlog_id,event_id)
		{
			var td_id = "eventbox"+backlog_id+"-"+event_id;
			var plus  = "eventplus"+backlog_id+"-"+event_id;

			document.getElementById(td_id).innerHTML = "";
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"toggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/plus-small.png' border='0' alt='plus'/></a>";
		}


		/* Filters */

        function toggle_filters()
		{
            if($('#searchtable').css('display') == 'none')
            {
                $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_down.png');
            }
            else
            {
                $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_right.png');
            }

			$('#searchtable').toggle();

			if (!showing_calendar)
			{
    			calendar();
			}
		}

		var showing_calendar = false;

		function calendar()
		{
            showing_calendar = true;
            // CALENDAR
            
            $('.date_filter').datepicker({
                showOn: "both",
                buttonText: "",
                dateFormat: "yy-mm-dd",
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

		function remove_tag()
		{
    		document.filters.tag.value='';
    		document.filters.submit();
    		return false;
		}


		/* Various */


        function go_page(inf, sup)
        {
            $('#action').val('change_page');
            $('#inf').val(inf);
            $('#sup').val(sup);
            form_submit()
            return false;
        }

		function form_submit()
		{
			document.filters.submit();
		}


		function go(url)
		{
    		document.location.href=url;
    	}


        $(document).ready(function()
        {
        
            $('#clean_date_filter').on('click', function() 
            {
                $('#date_from').val('');
                $('#date_to').val('');
                form_submit();
                
                return false;
            });

        	check_background_tasks(0);

        	//Checkboxs

            $(".checkbox_info").tipTip({ defaultPosition: 'right', maxWidth: "300px" });

            $("#t_grouped_alarms tbody input[type=checkbox]").click(function()
            {
                bind_grouped_alarms_handlers();
            });

            //Filters by label
            $('.td_tag').on('mouseover', function()
            {
                 $(this).css('cursor', 'pointer');
            });

            $('.td_tag').on('mouseout', function()
            {
                 $(this).css('cursor', 'default');
            });

            $('.td_tag').on('click', function()
            {
                 var tag_id = $(this).attr('id').replace('tag_', '');
                 $('#tag').val(tag_id);
                 document.filters.submit()
            });

            //Greyboxs
            GB_TYPE = 'w';

            greybox2();

            $("a.greybox").click(function()
            {
                var t = this.title || $(this).text() || this.href;
                GB_show(t,this.href, 150, '40%');
                return false;
            });

            //Autocomplete
            var hosts = [<?php echo $hosts_str ?>];

            $("#src_ip").autocomplete(hosts, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: true,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#src_ip").val(item.ip);
            });

            $("#dst_ip").autocomplete(hosts, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: true,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#dst_ip").val(item.ip);
            });

            
            $('#allcheck').on('change', function()
            {
                var chk = $(this).prop('checked')

    			$("#t_grouped_alarms tbody input[type=checkbox]").each(function()
    			{
    				if (this.disabled == false)
    				{
    					$(this).prop('checked', chk)
    				}
    			});
    
                bind_grouped_alarms_handlers();
    		})


            <?php
            if ($src_ip != "" || $dst_ip != "" || $date_from != "" || $query != "" || $sensor_query != "" || $directive_id != "" || $num_events > 0)
            {
                ?>
                toggle_filters();
                <?php
            }
            if ($autorefresh)
            {
                ?>
                setInterval("refresh_function()",<?php echo $refresh_time?>);
                <?php
            }
            ?>

            //Tiptip
            $('.tip').tipTip({maxWidth:'300px'});
        });

	</script>
</head>

<body>

<div id='c_filters'>
<?php

//print_r($alarm_group);
//$count = count($alarm_group);
$tree_count = 0;
/* Filter & Action Console */
?>
<form name="filters" id="queryform" method="POST">
	<input type="hidden" name="unique_id" id="unique_id" value="<?php echo $unique_id ?>"/>
	<input type="hidden" name="group" id="group" value=""/>
	<input type="hidden" name="release" id="release" value=""/>
	<input type="hidden" name="take" id="take" value=""/>
	<input type="hidden" name="action" id="action" value=""/>
	<input type="hidden" name="descr" id="descr" value=""/>
	<input type="hidden" name="inf" id="inf" value=""/>
	<input type="hidden" name="sup" id="sup" value=""/>
	<input type="hidden" name="alarm" id="alarm" value=""/>
	<input type="hidden" name="tag" id="tag" value="<?php echo $tag ?>"/>


    <!-- Start Filter Box -->

    <table width="100%" align="center" class="transparent">
    	<tr>
    		<td class="filters">
    			<a href="javascript:;" onclick="toggle_filters()">
                    <span class='uppercase'><img id='search_arrow' src='/ossim/pixmaps/arrow_right.png' /><?php echo _("Search and filter") ?></span>
                </a>
    		</td>
    		<td class='noborder left'>
    			<div id='info_delete'>
    				<img src='../pixmaps/loading3.gif' alt='<?php echo _("Deleting grouped alarms")?>'/>
    				<span id='delete_data'><?php echo _("Deleting grouped alarms.  Please, wait a few seconds")?>...</span>
    			</div>
    		</td>
    	</tr>
    </table>


    <table id="searchtable">
    	<tr>
    		<th><?php echo _("Filter") ?></th>
    		<th width="300"><?php echo _("Options") ?></th>
    	</tr>

    	<?php
            if ($date_from != '' && $date_to != '')
            {
    	        $date_text  = '<a title="Clean date filter" href="javascript:void(0);" id="clean_date_filter" style="text-decoration: underline;font-weight: bold">' . _('Date') . '</a>';
    	    }
    	    else
    	    {
        	    $date_text  = '<strong>' . _('Date') . '</strong>';
    	    }
        ?>
    	<tr>
    		<td class="transparent">
    			<table class="transparent" style='width: 100%'>
    				<tr>
    					<td class='label_filter_l'><strong><?php echo _("Sensor")?></strong>:</td>
    				    <td class='noborder left' nowrap='nowrap'>
    						<select name="sensor_query" id='sensor_query'>
    							<option value=""></option>
    							<?php
    							foreach ($sensors[0] as $_sensor_id => $_sensor)
    							{
    								$selected = ($sensor_query == $_sensor_id) ? "selected='selected'" : "";
    								?>
    								<option value="<?php echo $_sensor_id ?>" <?php echo $selected?>><?php echo $_sensor["name"] ?> (<?php echo $_sensor["ip"] ?>)</option>
    								<?php
    							}
    							?>
    						</select>
    				    </td>
    					<td class='noborder left'>
        					<div style="margin-top:3px">
                                <strong><?php echo _("Intent") ?></strong>:
                                <select name="intent"><option value="0"></option>
                                <?php
                                    $intents = Alarm::get_intents($conn);
                                    foreach ($intents as $kingdom_id => $kingdom_name)
                                    {
                                        $selected = ($kingdom_id==$intent) ? "selected" : "";
                                        echo '<option value="'.$kingdom_id.'" '.$selected.'>'.Util::htmlentities($kingdom_name).'</option>';
                                    }
                                ?>
                                </select>
                            </div>
    					</td>
    				</tr>
    				<tr>
    					<td class='label_filter_l'><strong><?php echo _("Alarm name")?></strong>: </td>
    				    <td class='label_filter_l pl4' nowrap='nowrap'><input type="text" class='inpw_200' id="alarm_name" name="query" value="<?php echo Util::htmlentities($query) ?>"/></td>
    					<td class='noborder left'><span style='font-weight: bold;'><?php echo _("Directive ID")?></span>: <input type="text" class='inpw_200' id="directive_id" name="directive_id" value="<?=$directive_id?>"></td>
    				</tr>
    				<tr>
    					<td class='label_filter_l'><strong><?php echo _("IP Address") ?></strong>:</td>
    				    <td class='label_filter_l pl4' nowrap='nowrap'>
    						<div class='label_ip_s'>
    							<div style='width: 70px; float: left;'><?php echo _("Source") ?>:</div>
    							<div style='float: left;'><input type="text" id="src_ip" name="src_ip" value="<?php echo $src_ip ?>"/></div>
    						</div>
    						<div class='label_ip_d'>
    							<div style='width: 70px; float: left;'><?php echo _("Destination") ?>:</div>
    							<div style='float: left;'><input type="text" id="dst_ip" name="dst_ip" value="<?php echo $dst_ip ?>"/></div>
    						</div>
    					</td>
    					<td class='noborder left'>
    						<div style='padding-bottom: 3px;'>
    							<strong><?php echo _("Num. alarm groups per page") ?></strong>: <input type="text" size='3' name="num_alarms_page" value="<?php echo $rows ?>"/>
    						</div>
    						<div>
    							<strong><?php echo _("Number of events in alarm") ?></strong>:
    							<select name="num_events_op">
    								<option value="less" <?php if ($num_events_op == "less") echo "selected='selected'"?>>&lt;=</option>
    								<option value="more" <?php if ($num_events_op == "more") echo "selected='selected'"?>>&gt;=</option>
    							</select>
    							&nbsp;<input type="text" name="num_events" size='3' value="<?php echo $num_events ?>"/>
    						</div>
    					</td>
    				</tr>
    				<tr>
    				    <td class='label_filter_l width100' nowrap='nowrap'></td>
    				    <td class='label_filter_l pl4' nowrap='nowrap'></td>
    					<td class='noborder left'>
    				</td>
    				</tr>
    				<tr>    				
    					<td id="date_str" class='label_filter_l width100'><?php echo $date_text ?>:</td>
    					<td class="transparent">
                            <div class="datepicker_range">
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
    					<td class='noborder'>
    						<table class="transparent" width='100%'>
    							<?php
    							if (count($tags) < 1)
    							{
    								?>
    								<tr>
    									<td class="transparent">
    									   <span><?php echo _("No tags found")?></span>&nbsp;
    									   <a href="tags_edit.php"><?php echo _("Click here to create") ?></a>
    								    </td>
    								</tr>
    								<?php
    							}
    							else
    							{
    								?>
    								<tr>
    									<td class="transparent">
                                            <div style='text-align: left;width:100%;display:block'>
                                                <div style="float:left">
                                                    <a style='cursor:pointer' class='ndc uppercase' onclick="$('#tags_filter').toggle()">
                                                        <img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _("Filter by label") ?>
                                                    </a>
                                                </div>

                                                <?php
                                                if ($tag != "")
                                                {
                                                    ?>
                                                    <div style="float:left;margin-left:5px">
                                                        <?php echo preg_replace("/ <a(.*)<\/a>/", "", $tags_html[$tag])?>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <?php
                                            if ($tag != "")
                                            {
                                                ?>
                                                <div style='text-align: left;float:left;margin-left:16px;display:block;width:100%'>
                                                    <a href="javascript:remove_tag();"><?php echo _("Remove filter")?></a>
                                                </div>
                                                <?php
                                            }
                                            ?>
    									</td>
    								</tr>
    								<tr>
    									<td class="transparent">
    										<div style="position:relative; z-index: 10000000;">
    											<div id="tags_filter" style="display:none;border:0px;position:absolute">
    												<table cellpadding='0' cellspacing='0' align="center">
    													<tr>
    														<th colspan="2" valign='middle' style="border:none; padding: 2px;">
    															<div style='position: relative; margin:auto;'>
    																<div style='position: absolute; top: 2px; width: 90%;'><?php echo _("Labels")?></div>
    															</div>

    															<div style='float:right; width:18%; text-align: right;'>
    																<a style="cursor:pointer; text-align: right;" onclick="$('#tags_filter').toggle()">
    																	<img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/>
    																</a>
    															</div>
    														</th>
    													</tr>

    													<?php
    													foreach ($tags as $tg)
    													{
    														$tag_style[] = "background-color: #".$tg->get_bgcolor();
    														$tag_style[] = "color: #".$tg->get_fgcolor();
    														$tag_style[] = "font-weight: ".($tg->get_bold()) ? "bold" : "normal";
    														$tag_style[] = "font-style: ". ($tg->get_italic()) ? "italic" : "none";

    														$styles = implode(";", $tag_style);

    														?>
    														<tr>
    															<td class="transparent">
    																<table class="transparent" cellpadding="4">
    																    <tr>
        																    <td class='td_tag' id='tag_<?php echo $tg->get_id()?>' style="<?php echo $styles?>">
        																        <?php echo $tg->get_name()?>
        																    </td>
    																    </tr>
    																 </table>
    															</td>

    															<td class="transparent">
        															<?php
        															if ($tag == $tg->get_id())
        															{
        																?>
        																<a href="javascript:remove_tag();">
        																    <img src="../pixmaps/cross-small.png" alt="<?php echo _("Remove filter") ?>" title="<?php echo _("Remove filter") ?>"/>
        																</a>
        																<?php
        															}
        															?>
    															</td>
    														</tr>
    														<?php
    													}
    													?>
    												</table>
    											</div>
    										</div>
    									</td>
    									<td class="transparent"></td>
    								</tr>
    								<?php
    							}
    							?>
    						</table>
                        </td>
    				</tr>
    			</table>
    		</td>


    		<td style="text-align: left;">
    			<table style='width:100%' class='noborder'>
    				<tr>
    					<td class='noborder'>
    						<table style='width:100%'>
    							<tr>
    								<td class='noborder left'><strong><?php echo _("Show") ?>:</strong></td>
    								<td class='noborder left'>
    									<select name="show_options">
    										<option value="1" <?php echo $selected1 ?>><?php echo _("All Groups") ?></option>
    										<option value="2" <?php echo $selected2 ?>><?php echo _("My Groups") ?></option>
    										<option value="3" <?php echo $selected3 ?>><?php echo _("Groups Without Owner") ?></option>
    										<option value="4" <?php echo $selected4 ?>><?php echo _("My Groups & Without Owner") ?></option>
    									</select>
    								</td>
    							</tr>
    							<tr>
    								<td class='noborder left'>&nbsp;</td>
    								<td class='noborder left'>
    									<input style="border:none" name="no_resolv" type="checkbox" onclick="document.filters.submit()" value="1" <?php if ($no_resolv) echo " checked='checked' " ?> /><?php echo gettext("Do not resolve ip names"); ?><br/>
    									<input type="checkbox" name="hide_closed" value="1" onclick="document.filters.submit()" <?php echo $hide_check ?> /><?php echo gettext("Hide closed alarms") ?><br/>
    									<input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" <?php echo ($autorefresh) ? "checked='checked'" : "" ?> value='1'/><?php echo gettext("Autorefresh") ?>&nbsp;
    									<select name="refresh_time" <?php echo (!$autorefresh) ? "disabled='disabled'" : "" ?>>
    										<option value="30000" <?php echo $refresh_sel1 ?>><?php echo _("30 sec") ?></option>
    										<option value="60000" <?php echo $refresh_sel2 ?>><?php echo _("1 min") ?></option>
    										<option value="180000" <?php echo $refresh_sel3 ?>><?php echo _("3 min")?></option>
    										<option value="600000" <?php echo $refresh_sel4 ?>><?php echo _("10 min") ?></option>
    									</select>
    									&nbsp;<a href="" onclick="form_submit();return false">[<?php echo _("Refresh") ?>]</a>
    								</td>
    							</tr>
    						</table>
    					</td>
    				</tr>
    			</table>
    		</td>

    	</tr>

    	<tr>
    		<td colspan="4" style="padding:5px;" class='noborder'>
    			<input type="submit" name='search' value="<?php echo _("Search") ?>"/>
    			<div id="loading_div" style="display:inline"></div>
    		</td>
    	</tr>
    </table>

    <!-- End Filter Box -->
<br/>


<div id='header_ga'>

	<div id='header_lga'>
    	<table>
    		<tr>
    			<td class="td_header">
    				<?php echo _('Grouped by') ?>
    			</td>

    			<td>
    				<select name="group_type" id="group_type" onchange="document.filters.submit();">
    				    <?php
    				    $group_types = array(
    				        "all"      => _("Alarm name, Src/Dst, Date"),
    				        "namedate" => _("Alarm name, Date"),
    				        "name"     => _("Alarm name"),
    				        "similar"  => _("Similar alarms")
    				    );

    				    foreach($group_types as $gt_key => $gt_text)
    				    {
        				    $selected = ($gt_key == $group_type) ? "selected='selected'" : "";
        				    echo "<option value='$gt_key' $selected>$gt_text</option>".PHP_EOL;
    				    }

    				    ?>
    				</select>
    			</td>
    		</tr>
    	</table>
	</div>

	<div id='header_rga'>

        <button type="button" disabled='disabled' id='b_close_selected'>
            <img src='style/img/unlock.png' height="14px" align="absmiddle" style="padding-right:8px"/>
            <span><?php echo _("Close selected")?></span>
        </button>

        <?php
        if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
        {
            ?>

            <button type="button" disabled='disabled' id='b_delete_selected'>
                <img src='style/img/trash_fill.png' height="14px" align="absmiddle" style="padding-right:8px"/>
                <span><?php echo _("Delete selected")?></span>
            </button>
            <?php
        }
        ?>

	</div>
</div>


<div id='body_ga'>

    <table id='t_grouped_alarms' class='table_data'>
        <thead>
            <tr>
                <th id='th_chk_all'><input type='checkbox' id='allcheck'/></th>
                <th id='th_exp_all'>
                    <a href='javascript: opencloseAll();'>
        				<img src='../pixmaps/plus.png' id='expandcollapse' border='0' alt='<?=_("Expand/Collapse ALL")?>' title='<?=_("Expand/Collapse ALL")?>'>
        			</a>
                </th>
                <th id='th_group'><?php echo _("Group")?></th>
                <th id='th_owner'><?php echo _("Owner")?></th>
                <th id='th_risk'><?php echo _("Highest Risk")?></th>
                <th id='th_description'><?php echo _("Description")?></th>
                <th id='th_status'><?php echo _("Status")?></th>
                <th id='th_actions'><?php echo _("Action")?></th>
            </tr>
        </thead>

        <tbody>           
        <?php
    	if (count($alarm_group) == 0)
    	{
    		?>
            <tr>
                <td colspan='8' class='noborder center' id='no_groups'>
                    <?php echo _("No groups found")?>
                </td>
            </tr>
    		<?php
    	}
    	else
    	{
        	$cont    = 0;
        	$lastday = "";
        	foreach($alarm_group as $group)
    		{

    			$group_id            = $group['group_id'];
    			$_SESSION[$group_id] = $group['name'];
    			$ocurrences          = $group['group_count'];

    			$max_risk = $group['max_risk'];
    			$id_tag   = $group['id_tag'];

    			$show_day = 0;
    			if ($group['date'] != $lastday)
    			{
    				$lastday                  = $group['date'];
    				list($year, $month, $day) = explode("-", $group['date']);
    				$date                     = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));
    				$show_day                 = ($group_type == "name" || $group_type == "similar") ? 0 : 1;
    			}


    			$descr   = $db_groups[$group_id]['descr'];

                //Get group status dynamically


                if($group_type == "similar")
                {
                    $st_name = $group_id;
                }
                else
                {
                    $st_name = ($group['name'] == _('Unknown Directive')) ? '' : $group['name'];
                }

                if ($group_type == "name" || $group_type == "similar")
                {
                    $st_df = $date_from;
                    $st_dt = $date_to;
                }
                else
                {
                    $timestamp = preg_replace("/\s\d\d\:\d\d\:\d\d$/","", $group['date']);

                    $st_df = $timestamp." 00:00:00";
                    $st_dt = $timestamp;
                }

                $status = Alarm_groups::get_group_status($conn, $sensor_query, $src_ip, $dst_ip, $st_df, $st_dt, $st_name);

    			$incident_link  = "<img border='0' src='../pixmaps/script--pencil-gray.png'/>";
    			$group_box      = "";
    			$owner_take     = 0;
    			$av_description = "readonly='true'";

    			$ocurrence_text = ($ocurrences > 1) ? strtolower(_("Alarms")) : strtolower(_("Alarm"));


    			if ($db_groups[$group_id]['owner'] == $_SESSION["_user"])
    			{
        			$owner = "<a href=\"javascript:release_group('$group_id', '$inf', '$sup');\">"._("Release")."</a>";
    			}
    			else
    			{

        			$owner = "<a href=\"javascript:take_group('$group_id', '$inf', '$sup');\">"._("Take")."</a>";
    			}


    			if ($db_groups[$group_id]['owner'] != "")
    			{
    				if ($db_groups[$group_id]['owner'] == $_SESSION["_user"])
    				{
    					$owner_take = 1;

    					if ($status == 'open')
    					{
    						$owner = "<a href=\"javascript:release_group('$group_id', '$inf', '$sup');\">"._("Release")."</a>";
    					}

                        $group_box = "<input type='checkbox' id='check_".$group_id."' name='group' value='".$group_id."'/>";

                        //Create a new ticket for Group ID
                        if (Session::menu_perms("analysis-menu", "IncidentsOpen"))
                        {
                            $ticket_name   = preg_replace('/&mdash;/', '--', Util::signaturefilter($group['name']));
                    		$_st_df_aux    = (preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $st_df)) ? $st_df." ".date("H:i:s") : $st_df;
                    		$_st_dt_aux    = (preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $st_dt)) ? $st_dt." ".date("H:i:s") : $st_dt;
                    		$incident_link  = '<a class="greybox2" href="../incidents/newincident.php?ref=Alarm&title=' . urlencode($ticket_name) . "&" . "priority=$max_risk&" . "src_ips=$src_ip&" . "event_start=$_st_df_aux&" . "event_end=$_st_dt_aux&" . "src_ports=&" . "dst_ips=$dst_ip&" . "dst_ports=" . '" title="'._("New Ticket").'">' . '<img class="tip" border="0" title="'._("Add new ticket").'"  src="../pixmaps/script--pencil.png"/>' . '</a>';

    					}
    					else
    					{
                            $incident_link  = "<span class='disabled'>
                                                   <img src='../pixmaps/script--pencil.png' border='0'/>
                                               </span>";

                        }

                        $av_description = "";
    				}
    				else
    				{
    					$owner_take  = 0;

    					$description = "<input type='text' name='input" . $group_id . "' title='" . $descr . "' " . $av_description . " style='text-decoration: none; border: 0px; background: #FEE599' size='20' value='" . $descr . "' />";
    					$group_box   = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
    				}
    			}


    			if ($status == "open" && $owner_take)
    			{
        			$delete_link = "<a title='"._("Close")."' href=\"javascript:close_group('$group_id');return false;\"><img border=0 src='../pixmaps/cross-circle-frame.png'/></a>";
    			}
    			else
    			{
        			$delete_link = "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'/>";
    			}

    			if ($status == 'open')
    			{
    				if ($owner_take)
    				{
    				    $close_link = "<a href=\"javascript:close_group('$group_id');\"><img class='tip' src='../pixmaps/lock-unlock.png' alt='"._("Open, click to close group")."' title='"._("Open, click to close group")."' border='0'/></a>";
    				}
    				else
    				{
    				    $close_link = "<img src='../pixmaps/lock-unlock.png' class='tip' alt='"._("Open, take this group then click to close")."' title='"._("Open, take this group then click to close")."' border='0'/>";
    				}
    			}
    			else
    			{
    				if ($owner_take)
    				{
    				    $close_link = "<a href=\"javascript:open_group('$group_id');\"><img class='tip' src='../pixmaps/lock.png' alt='"._("Closed, click to open group")."' title='"._("Closed, click to open group")."' border='0'/></a>";
    				}
    				else
    				{
    				    $close_link = "<img class='tip' src='../pixmaps/lock.png' alt='"._("Closed, take this group then click to open")."' title='"._("Closed, take this group then click to open")."' border='0'/>";
    				}

    				$group_box = "<input type='checkbox' disabled = 'true' name='group' value='$group_id'/>";
    			}

    			if ($show_day)
    			{
    				?>
    				<tr>
    					<td colspan='8' class="sep_date">
    					   <span><?php echo $date?><span>
    					</td>
    				</tr>
    				<?php
    			}

    			$tr_class = ($owner_take == true) ? 'tr_take' : 'tr_no_take';
    			?>

    			<tr class='<?php echo $tr_class?>'>
    				<td>
                        <?php
                        if(!$owner_take)
                        {
                            ?>
                            <div class="checkbox_info" title="<?php echo _("You must take ownership first")?>">
                            <?php
                        }

                        $chk_val      = $group_id.'_'.$group['ip_src'].'_'.$group['ip_dst'].'_'.$group['date'];
                        $chs_status = (!$owner_take) ? "disabled='disabled'" : '';

                        ?>
                        <input type='checkbox' id='check_<?php echo $group_id?>' name='group' <?php echo $chk_status?> value='<?php echo $chk_val?>'/>

                        <?php if(!$owner_take)
                        {
                            ?>
                            </div>
                            <?php
                        }
                        ?>
                    </td>

    				<td id="plus<?php echo $group['group_id']?>">

    				    <?php
    				        $group_id   = $group['group_id'];
    				        $g_ip_src   = $group['ip_src'];
    				        $g_ip_dst   = $group['ip_dst'];
    				        $g_time     = ($group_type == "name" || $group_type == "similar") ? "" : $group['date'];
    				        $g_from     = '';
    				        $g_similar  = ($group_type == "similar") ? "1" : "";;
    				    ?>

        				<a href="javascript:toggle_group('<?php echo $group_id?>', '<?php echo $g_ip_src?>', '<?php echo $g_ip_dst?>', '<?php echo $g_time?>', '', '<?php echo $g_similar?>');">
        				    <img src='../pixmaps/plus-small.png' border='0'/>
        				</a>

    				</td>

    				<td>
    					<table class="transparent">
    						<tr>
    							<?php
    							if ($tags_html[$id_tag] != "")
    							{
    								?>
    								<td class="transparent">
    								    <?php echo preg_replace("/ <a(.*)<\/a>/", "", $tags_html[$id_tag])?>
    								</td>
    								<?php
    							}
    							?>
    							<td class="transparent">
        							<?php echo Util::signaturefilter(Alarm::transform_alarm_name($conn, $group['name']))?>
        							&nbsp;&nbsp;
        							<span style='font-size:xx-small;'>(<?php echo $ocurrences?> <?php echo $ocurrence_text?>)</span>
    							</td>
    						</tr>
    					</table>
    				</td>

    				<td><?php echo $owner?></td>

    				<td align="center">
						<?php
						if ($max_risk > 7)
						{
							?>
				            <span class='red'><?php echo $max_risk?></span>
							<?php
						}
						elseif ($max_risk > 4)
						{
							?>
				            <span class='orange'><?php echo $max_risk?></span>
							<?php
						}
						elseif ($max_risk > 2)
						{
							?>
							<span class='green'><?php echo $max_risk?></span>
							<?php
						}
						else
						{
							?>
						     <span class='black'><?php echo $max_risk?></span>
							<?php
						}
						?>
    				</td>

    				<td>
    					<table class='transparent'>
    						<tr>
    							<td class='transparent left'>
    								<input type='text' name='input<?=$group_id?>' title='<?php echo $descr?>' <?php echo $av_description?> size='30' style='height: 16px;' value='<?=$descr?>' onkeypress='send_descr(this, event);'/>
    							</td>

    							<td class='transparent'>
    							<?php
    							if ($owner_take)
    							{
                                    ?>
                                    <a href="javascript:change_descr('input<?=$group_id?>')">
                                        <img valign='absmiddle' border='0' src='../pixmaps/disk-black.png'/>
                                    </a>
                                    <?php
                                    }
    							?>
    							</td>
    						</tr>
    					</table>
    				</td>

    				<td id='lock_<?php echo $group_id?>'>
    				    <?php echo $close_link?>
    				</td>

    				<td>
    				    <?php echo $incident_link?>
    				</td>
    			</tr>

    			<tr class="hidden_row"><td></td></tr>
    			<tr>
    				<td class='noheight_row' colspan="8" id="<?php echo $group['group_id']?>"></td>
    			</tr>
    			<?php

    			$cont++;
			}
    	}
    	?>
        </tbody>
    </table>
    
    
    <div class='dt_footer'>
        <div class='t_entries'>
            <?php

            $from  = $inf + 1;
            $to    = (($inf + $rows)> $count) ? $count : $inf + $rows;
            $total = $count;

            echo "<span>"._("Showing $from to $to of $count groups")."</span>";
            ?>
        </div>

        <div class='t_paginate'>
           	<?php
			if ($inf >= $rows)
            {
                ?>
                <a href="javascript: go_page('<?php echo ($inf - $rows)?>', '<?php echo ($sup - $rows)?>');">&lt; <?php echo _("PREVIOUS")?></a>
                <?php
            }
            else
            {
                ?>
                <span>&lt; <?php echo _("PREVIOUS") ?></span>
                <?php
            }

            echo "&nbsp;&nbsp;&nbsp;&nbsp;"; 
            
            if ($sup < $count)
            {
                ?>
                <a href="javascript: go_page('<?php echo ($inf + $rows) ?>', '<?php echo ($sup + $rows)?>');"><?php echo _("NEXT") ?> &gt;</a>
                <?php
            }
            else
            {
                ?>
                <span><?php echo _("NEXT") ?> &gt;</span>
                <?php
            }

         	?>
        </div>
    </div>
</div>

<div id='footer_ga'>
    <?php
    if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
    {
        ?>
        <a href='javascript:delete_all_groups();'><?php echo _("Delete ALL");?></a>
        <?php
    }
    ?>
</div>

</form>

</div>

</body>
</html>
