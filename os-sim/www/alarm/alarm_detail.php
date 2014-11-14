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
require_once 'alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

// Do not delete, this var is used in single_ip.php
$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

$tz   = Util::get_timezone();

//Getting parameters
$backlog_id = GET('backlog');

ossim_valid($backlog_id, 	OSS_HEX,	'illegal:' . _("Backlog ID"));

if ( ossim_error() )
{
    die(ossim_error());
}

list ($alarm,$stats,$event) = Alarm::get_alarm_detail($conn, $backlog_id);

if(!is_array($stats) && !is_object($alarm))
{
	$error     = true;
	$error_msg = _('It was impossible to retrieve the alarm information');
}


if(!$error)
{
	//This is to force the alarms to remember the position of the datatables
	$_SESSION["_alarm_keep_pagination"] = TRUE;

	//Storing in session necessary alarm info
	$_SESSION['_alarm_stats']               = $stats;

	$event_info                             = Alarm::get_event($conn, $alarm->get_event_id());
	$_SESSION['_alarm_stats']['event_info'] = $event_info;

	//alarm source and detination
	$src  = $stats['src'];
	$dst  = $stats['dst'];

	//Retrieving the alarm info for the detail
	$plugin_id               = $alarm->get_plugin_id();
	$plugin_sid              = $alarm->get_plugin_sid();

	$ctx                     = $event['_CTX'];
    $engine                  = $alarm->get_ctx();
	$taxonomy_icon           = '/ossim/pixmaps/alarms.png';
	$alarm_name              = Util::translate_alarm($conn, $alarm->get_sid_name(), $alarm, 'array');
	$event_number            = $stats['events'];
    //$alarm_time            = get_alarm_life($stats['min_timestamp'], $stats['max_timestamp']);
    //$alarm_life            = get_alarm_life($stats['min_timestamp'], gmdate("Y-m-d H:i:s"), 'ago');
	$alarm_time              = get_alarm_life($alarm->get_since(), $alarm->get_last());
	$alarm_life              = get_alarm_life($alarm->get_last(), gmdate("Y-m-d H:i:s"), 'ago');
	list($risk, $risk_color) = colorize_risk($alarm->get_risk());

	/* Source */
    $_home_src = Asset_host::get_extended_name($conn, $geoloc, $alarm->get_src_ip(), $ctx, $event_info["src_host"], $event_info["src_net"]);
	$src_home  = ($_home_src['is_internal']) ? "<img src='/ossim/alarm/style/img/home24.png' class='home_img' /> " : '';

	/* Destination */
	$_home_dst = Asset_host::get_extended_name($conn, $geoloc, $alarm->get_dst_ip(), $ctx, $event_info["dst_host"], $event_info["dst_net"]);
	$dst_home  = ($_home_dst['is_internal']) ? "<img src='/ossim/alarm/style/img/home24.png' class='home_img' /> " : '';


	$promiscous_title = _(is_promiscous(count($src['ip']), count($dst['ip']), $_home_src['is_internal'], $_home_dst['is_internal']));

	if (count($src['ip']) > 1 || count($dst['ip']) > 1)
	{
		$promiscous_icon  = '/ossim/alarm/style/img/promiscuous.png';
	}
	else
	{
		$promiscous_icon  = '/ossim/alarm/style/img/npromiscuous.png';
	}

	$tooltip = '';

	//Tags related to the alarm
	$tags    = $alarm->get_tags();


	if(!empty($tags))
	{
		$tags_list = Tags::get_list($conn);
		$tlist     = array();

		foreach ($tags as $id_tag)
		{
			$tag = $tags_list[$id_tag];
			if(is_object($tag))
			{
				$tlist[] = "<div>".$tag->get_name()."</div>";
			}
		}

		$tooltip  = '<strong>' . _('Labels Applied') . ': </strong><br>' . implode('<br>', $tlist);
		$tooltip  = (!empty($plugin_sid)) ? '<br><br>' . $tooltip : $tooltip;
	}

	$directive_name = $alarm_name['name'];

	if ($alarm_name["id"] != '')
	{
	    $intent = (file_exists("/usr/share/ossim/www/alarm/style/img/".$alarm_name["id"].".png")) ? "<img src='style/img/".$alarm_name["id"].".png' border='0' class='img_intent' title='".$alarm_name["kingdom"]."'>" : $alarm_name["kingdom"]." &mdash;";
		$alarm_title = $intent." ".$alarm_name["category"]." &mdash; ".$alarm_name["subcategory"];
	}
	else
	{
		$alarm_title = $alarm_name['name'];
	}

	$class_gb_dir  = 'dgreybox';
	if ($plugin_id == 1505 && !empty($plugin_sid))
    {
		if ($plugin_sid > 500000 && $alarm_name["id"] == '')
		{
		    $tooltip = _('Add Intent & Strategy & Method Metadata') . $tooltip;
		}
		else
		{
		    $tooltip = _('Directive ID: ') . $plugin_sid . $tooltip;

		}

		if($plugin_sid > 500000)
		{
		    $directive_url = "/ossim/directives/wizard_directive.php?engine_id=".Util::uuid_format($engine)."&directive_id=$plugin_sid";
		}
		else
		{
		    $directive_url = "javascript:;";
		    $class_gb_dir  = 'alarm_name_nolink';
		}

    }
    else
    {
            $directive_url = "/ossim/directives/wizard_directive.php?engine_id=".Util::uuid_format($engine)."";
            $tooltip       = _('Create a Directive for this Alarm') . $tooltip;
    }

	$alarm_title     = "<a href='$directive_url' class='alarm-help $class_gb_dir' title=\"$tooltip\" style='font-size:16px;line-height:22px;'>$alarm_title</a>";

	$removable       = $alarm->get_removable();

	$new_ticket_url  = "/ossim/incidents/newincident.php";
    $new_ticket_url .= "?ref=Alarm&title=".urlencode($alarm->get_sid_name())."&priority=".$alarm->get_risk()."&src_ips=".$alarm->get_src_ip()."&event_start=".$alarm->get_since()."&event_end=".$alarm->get_last()."&src_ports=".$alarm->get_src_port()."&dst_ips=".$alarm->get_dst_ip()."&dst_ports=".$alarm->get_dst_port()."&backlog_id=$backlog_id&event_id=".$alarm->get_event_id();

	//dding the class box_correlating we add opacity to the boxes.
	$box_class       = ($removable) ? 'box_icon' : 'box_icon';

	$new_direct_link = Menu::get_menu_url("/ossim/directives/index.php", 'configuration', 'threat_intelligence', 'directives');
	
	
	/*   KDB   */
	$vars['_SENSOR']             = $event['_SENSOR']; 
    $vars['_SRCIP']              = $event['_SRCIP'];     
    $vars['_SRCMAC']             = $event['_SRCMAC'];           
    $vars['_DSTIP']              = $event['_DSTIP'];             
    $vars['_DSTMAC']             = $event['_DSTMAC'];         
    $vars['_SRCPORT']            = $event['_SRCPORT'];            
    $vars['_DSTPORT']            = $event['_DSTPORT'];           
    $vars['_SRCCRITICALITY']     = $event['_SRCCRITICALITY'];     
    $vars['_DSTCRITICALITY']     = $event['_DSTCRITICALITY'];    
    $vars['_SRCUSER']            = $event['_SRCUSER'];            
    $vars['_FILENAME']           = $event['_FILENAME'];          
    $vars['_USERDATA1']          = $event['_USERDATA1'];         
    $vars['_USERDATA2']          = $event['_USERDATA2'];         
    $vars['_USERDATA3']          = $event['_USERDATA3'];        
    $vars['_USERDATA4']          = $event['_USERDATA4'];
    $vars['_USERDATA5']          = $event['_USERDATA5']; 
    $vars['_USERDATA6']          = $event['_USERDATA6']; 
    $vars['_USERDATA7']          = $event['_USERDATA7']; 
    $vars['_USERDATA8']          = $event['_USERDATA8']; 
    $vars['_USERDATA9']          = $event['_USERDATA9'];
    $vars['_ALARMRISKSCORE']     = $alarm->get_risk();
    $vars['_ALARMRELIABILITY']   = $event['_RELIABILITY'];
    $vars['_SRCREPACTIVITY']     = $event['_SRCREPACTIVITY'];
    $vars['_DSTREPACTIVITY']     = $event['_DSTREPACTIVITY'];
    $vars['_SRCREPRELIABILITY']  = $event['_SRCREPRELIABILITY'];
    $vars['_DSTREPRELIABILITY']  = $event['_DSTREPRELIABILITY']; 

    
    $_SESSION['_kdb_alarm_vars'] = $vars;
    $_SESSION['_kdb_alarm_pid']  = $plugin_id;
    $_SESSION['_kdb_alarm_psid'] = $plugin_sid;

}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

	<!--[if lt IE 9]>
		<script type="text/javascript" src="/ossim/js/excanvas.js"></script>
		<script type="text/javascript" src="/ossim/js/html5shiv.js"></script>
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>

	<!-- JQuery -->
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>


	<script type="text/javascript" src="/ossim/js/greybox.js"></script>


	<!-- JQuery TipTip: -->
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
	<script src="../js/jquery.simpletip.js" type="text/javascript"></script>

	<!-- Canvas Tag Cloud -->
	<script type="text/javascript" src="/ossim/js/tagcloud/jquery.tagcloud.js"></script>

	<!-- JQuery easy-pie-chart -->
	<script type="text/javascript" src="/ossim/js/jquery.easy-pie-chart.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.easy-pie-chart.css" media="screen">


	<!-- JQuery DataTables: -->
	<script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>

	<!-- Spark Line: -->
    <script type="text/javascript" src="/ossim/js/jquery.sparkline.js"></script>

	<!-- JQuery Carrousel: -->
	<script type="text/javascript" src="/ossim/js/jcarousel.js"></script>

	<!-- Notification: -->
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	
	<!-- Token -->
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>

	<link rel="stylesheet" type="text/css" href="/ossim/style/alarm/detail.css"/>

	<?php require '../host_report_menu.php';?>

	<script type='text/javascript'>

		$.fn.scrollBottom = function()
		{
		  return $(top.document).height() - this.scrollTop() - this.height();
		};

		function GB_onhide(url, params)
        {
            if(typeof url == 'string' && url !='')
            {
                if(url.match(/wizard_directive/) && typeof params == 'object')
                {
                	 //We have edited a directive. Reload is necessary to show the changes.
                	if(params['edited'] == true)
                	{
                		document.location.reload();
                	}
                	else if(params['reload'] == true) //If we have created a new directive, we go to directive editor
                	{
                		var url = '<?php echo $new_direct_link ?>&engine_id=<?php echo Util::uuid_format($engine) ?>&toggled=&toggled_dir='+ params['directive'] +'&msg_success=1';
                		document.location.href = GB_makeurl(url);
                	}

                }
                else if(url.match(/newincident/))
        		{
            		document.location.href="../incidents/index.php?m_opt=analysis&sm_opt=tickets&h_opt=tickets"
        		}
            }
        }

		function show_events()
		{
			$('#tabs-list').tabs('select', 0);
		}

		function show_src()
		{
			$('#tabs-list').tabs('select', 1);
		}

		function show_dst()
		{
			$('#tabs-list').tabs('select', 2);
		}

		function show_notification(msg, type, hide){

			var cancel = false;
			if(typeof(hide) != 'undefined')
			{
				cancel = true;
			}

			var config_nt = { content: msg,
							  options: {
								type: type,
								cancel_button: cancel
							  },
							  style: 'width:45%;display:none;text-align:center;margin:10px auto;padding:0 5px;'
							};

			nt = new Notification('nt_js',config_nt);

			$('#notification').html(nt.show());
			nt.fade_in(1000);

			if(!cancel){
				setTimeout("nt.fade_out(1000);",2000);
			}
		}

		function modify_alarm(action)
		{
			if(confirm('<?php echo _("Are you sure you want to modify this alarm?") ?>'))
			{
			    var atoken = Token.get_token("alarm_operations");
			    
				$.ajax({
					data:  {"action": action, "data": {"id": '<?php echo $backlog_id ?>'}},
					type: "POST",
					url: "alarm_ajax.php?token="+atoken,
					dataType: "json",
					async: false,
					success: function(data){
						if(data.error)
						{
							show_notification(data.msg, 'nf_error');
						}
						else
						{
							show_notification(data.msg, 'nf_success');
							setTimeout("document.location.reload();",1000);
						}

					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						show_notification(textStatus, 'nf_error');
					}
				});
			}

		}

		

		function carrousel_lite(id, num){

			var aux = $("#jCarouselLite"+id).html();
			$("#jCarouselLite"+id).html(aux);

			$("#jCarouselLite"+id).jCarouselLite({
				btnNext: "#next"+id,
				btnPrev: "#prev"+id,
				speed: 200,
				mouseWheel: true,
				easing: "",
				visible: num,
				scroll: 1,
				//auto: 3000,
				circular: true
			});

			$("#jCButton"+id).css('visibility', 'visible');
			$('.flag_counter'+id).tipTip();

		}

		function go_back() 
		{

    		if (typeof(top.av_menu.load_content) == 'function')
			{
			    top.av_menu.load_content("/alarm/alarm_console.php?<?php echo $_SESSION["_alarm_criteria"] ?>");
            }
    		else
    		{
        		document.location.href = 'alarm_console.php?<?php echo $_SESSION["_alarm_criteria"] ?>';
    		}
    		
		}

		function set_button_position(win)
		{
			var b = $(win).scrollBottom();

		     	b =  ((b - 76) < 0) ? 0 : (b-76);

		    $('#buttons').css('bottom', b + 'px');
		}

		$(document).ready(function()
		{
    		if(typeof top.av_menu.set_bookmark_params == 'function')
    		{
        		top.av_menu.set_bookmark_params('<?php echo $backlog_id ?>');
    		}
    		
			//Jquery TABS
			$( "#tabs-list" ).tabs({
				ajaxOptions: {
					error: function( xhr, status, index, anchor ) {
						$( anchor.hash ).html(
							"<?php echo _('It was impossible to load this tab')?>." );
					}
				},
				selected: 0,
				cache: true
			});

			$.ajax({
				data:  {"plugin_id": <?php echo $plugin_id ?>, "plugin_sid": <?php echo $plugin_sid ?>},
				type: "GET",
				url: "alarm_trend.php",
				dataType: "json",
				success: function(data){

						var lines   = new Array();
						var tooltip = new Array();

						for (var key in data)
						{
							var open     = data[key]['open']
							var close    = data[key]['closed'];

							tooltip.push(key);
							
							lines.push(open + ':' + close);

						}

						$('#sparktristatecols').empty();

						//Sparkline
						$('#sparktristatecols').sparkline(lines, 
						{
							type: 'bar',
							stackedBarColor: ['#8CC63F', '#FF0000'],
							height: '30px',
							disableHighlight: false,
							highlightLighten: 1.1,
							barWidth: 10,
							barSpacing: 4,
							disableTooltips: false,
							tooltipFormatter: function(a,b,c) {

								var open   = c[1].value + "<span style='font-weight:bold;color:" + c[1].color + "'> open</span>";
								var closed = c[0].value + "<span style='font-weight:bold;color:" + c[0].color + "'> closed</span>";
								var date   = tooltip[c[0].offset];

							    var tag   = date + '<br>' + open + ' <br>' + closed;

								return tag;
							}
						});

				},
				error: function(XMLHttpRequest, textStatus, errorThrown)
					{
						$('#sparktristatecols').html('');
						$('.spark').display('none');
					}
			});

			

			//TipTip
			$('.alarm-help').tipTip();

			$("#kdb_window").on('click', function()
			{
				var title = "<?php echo _('Knowledge Base') ?>";
				GB_show(title, this.href, '70%','70%');

				return false;

			});

    		if (typeof parent.is_lightbox_loaded == 'function' && parent.is_lightbox_loaded(window.name))
			{
			    $('#bread_crumb_alarm').hide();
			    $('#buttons').css({"bottom":"0px", "position":"fixed"});
			}
			else
			{

			    $(top.window).scroll(function ()
			    {
			        
			        set_button_position(this);

			    });
			}


            $('.dgreybox').on('click', function()
            {
            	var t = '<?php echo _('Directive Editor') ?>';
				GB_show(t,this.href, 500,'75%');

				return false;
            });

		});

	</script>
</head>

<body>


<?php
if ($error)
{
?>
	<div style='width:100%margin:0 auto;'>

		<?php

		$config_nt = array(
			'content' => $error_msg,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => true
			),
			'style'   => 'width: 45%; margin: 20px auto; text-align: center;'
		);

		$nt = new Notification('nt_1', $config_nt);
		$nt->show();

		?>

	</div>

<?php
	die();
}
?>

<div id='container'>

	<div id='bread_crumb_alarm' class='breadcrumb_back'>
		<div class='breadcrumb_item'>
			<a href='javascript:;' onclick='go_back();'><?php echo _('Alarms') ?></a>
		</div>
		<div class='breadcrumb_separator'>
			<img src='/ossim/pixmaps/xbreadcrumbs/separator.gif' />
		</div>
		<div class='breadcrumb_item last'>
			<?php echo $directive_name ?>
		</div>
		<div style='clear:both;'>&nbsp;</div>
	</div>

	<div id='notification'></div>

	<div id='detail'>

		<div id='alarm_name'>
			<div style='margin-left:15px'>
				<?php

					if(!$removable)
					{
						$tip = _("This alarm is still being correlated and therefore it can not be modified");
						echo "<img src='/ossim/alarm/style/img/correlating.gif' class='alarm-help corr_img' title='$tip'>";

		    		}
					echo $alarm_title
				?>
			</div>
		</div>

		<div id='alarm_icons'>

			<div style='float:right;'>

				<div class='<?php echo $box_class ?> alarm-help' title='<?php echo _('Alarm Status') ?>'>
					<div class='box_icon_top' style='top:7px'>
						<img src='/ossim/alarm/style/img/<?php echo (($alarm->get_status() == 'open') ? 'unlockb' : 'lockb' ) ?>.png'  height='19px;'/>
					</div>
					<div class='box_icon_bottom'>
						<span><?php echo (($alarm->get_status() == 'open') ? 'Open' : 'Closed' ) ?></span>
					</div>
				</div>

				<div class='<?php echo $box_class ?> alarm-help' title='<?php echo _('Number of Events') ?>'>
					<div class='box_icon_top' style='top:7px'>
						<a style='font-size:18px;' href='javascript:;' onclick='show_events();'>
							<?php
								echo (!$removable) ? '>' : '';
								echo $event_number
							 ?>
						</a>
					</div>
					<div class='box_icon_bottom'>
						<span><?php echo _('Events') ?></span>
					</div>
				</div>

				<div class='<?php echo $box_class ?> alarm-help' title='<?php echo _('Alarm Risk') ?>'>
					<div class='box_icon_top' style='top:5px'>
						<span style='font-size:22px;color:<?php echo $risk_color ?>;'><?php echo $risk ?></span>
					</div>
					<div class='box_icon_bottom'>
						<span><?php echo _('Risk') ?></span>
					</div>
				</div>

				<div class='<?php echo $box_class ?> alarm-help' title='<?php echo $promiscous_title ?>' style=''>


					<?php
						echo ($src_home != '') ? "<img src='/ossim/alarm/style/img/home24.png' class='home_img_small home_l' />" : '';
						echo "<img src='$promiscous_icon' style='margin-top:11px;'/>" ;
						echo ($dst_home != '') ? "<img src='/ossim/alarm/style/img/home24.png' class='home_img_small home_r' />" : '';
					?>

				</div>

				<div class='<?php echo $box_class ?> alarm-help' title='<?php echo _('Time between first and last event') ?>'  style=''>
					<div class='box_icon_top' style='top:8px'>
						<img src='/ossim/alarm/style/img/clock.png' width='24px' height='18px;'/>
					</div>
					<div class='box_icon_bottom'>
						<span>
							<?php
								echo (!$removable) ? '> ' : '';
								echo $alarm_time
							?>
						</span>
					</div>
				</div>

				<div class='<?php echo $box_class ?> alarm-help' title='<?php echo _('Elapsed time since alarm created') ?>'>
					<div class='box_icon_top' style='top:5px'>
						<?php
						if (!$removable)
						{
							echo "<img src='/ossim/alarm/style/img/correlating.gif' class='alarm-help corr_img_box' title='$tip'>";
						}
						else
						{
							echo "<img src='/ossim/alarm/style/img/sand.png'  height='26px;'/>";
						}
						?>
					</div>
					<div class='box_icon_bottom'>
						<span>
						<?php
							echo (!$removable)? "" : "<span>$alarm_life</span>";
						?>
						</span>
					</div>
				</div>

				<div class='<?php echo $box_class ?> spark' style='position:relative'>
					<span id="sparktristatecols"></span>
				</div>

			</div>


		</div>

	</div>

	<div id='boxes'>

		<div class='box' style='width:30%'>

			<div id='box1'>

				<div class='header'>
				    <?php echo $src_home . _('Source') ?>
				</div>

				<div class='content'>
					<div style='width:100%;height:100%;position:absolute;overflow:auto;'>
						<div style='padding:0px 10px;'>
							<?php
								$prefix = '_src';
								$data   = $src;
								if(count($src['ip']) == 1)
								{
									include "boxes/single_ip.php";
								}
								else
								{
									include "boxes/multiple_ip.php";
								}
							?>
						</div>
					</div>
				</div>

			</div>

		</div>

		<div class='box' style='width:30%'>

			<div id='box2'>

				<div class='header'>
					<?php echo $dst_home . _('Destination') ?>
				</div>

				<div class='content'>
					<div style='width:100%;height:100%;position:absolute;overflow:auto;'>
						<div style='padding:0px 10px;'>
							<?php
								$prefix = '_dst';
								$data   = $dst;
								if(count($dst['ip']) == 1)
								{
									include "boxes/single_ip.php";
								}
								else
								{
									include "boxes/multiple_ip.php";
								}
							?>
						</div>
					</div>
				</div>

			</div>

		</div>

		<div class='box' style='width:40%'>

			<div id='box3'>

				<div class='header'>
                    <a href='boxes/kdb.php' id='kdb_window'>
                        <?php echo _('Knowledge Base') ?>
                    </a>
				</div>

				<div class='content' style='overflow:auto;'>
					<div id='kdb_box'>
						<?php require_once 'boxes/kdb.php' ?>
					</div>
				</div>

			</div>

		</div>

	</div>

	<div id='tabs'>
		<div id='tabs-list' style='min-height:150px;'>
			<ul>
    			<li><a href="tabs/events_detail.php?backlog_id=<?php echo $backlog_id ?>&show_all=2&box=1&hide=directive"><?php echo _('Event Detail') ?></a></li>
				<li><a href="tabs/ip_info.php?prefix=src"><?php echo $src_home . _('Source') . " (".count($src['ip']).")" ?></a></li>
				<li><a href="tabs/ip_info.php?prefix=dst"><?php echo  $dst_home ._('Destination') . " (".count($dst['ip']).")"?></a></li>
				
			</ul>
		</div>
	</div>

	<br>

</div>

<div id='buttons'>
	<div style='float:left;padding-left:10px;'>
		<?php
			if($conf->get_conf("open_threat_exchange") != 'yes')
			{
				$otx_attr  = "disabled";
			}
			else
			{
				$otx_attr  = "onclick=\"GB_show('". _("Send Threat Information") ."','../updates/otxsend.php',450,'70%');\"";
			}
		?>
		<button class="av_b_secondary" <?php echo $otx_attr?>>
    		<img src="/ossim/alarm/style/img/sun.png" height='14px' align="absmiddle" style="padding-right:8px"/>
    		<span><?php echo _("Feedback to OTX")?></span>
		</button>

	</div>

	<div style='float:right;padding-right:10px;'>


        <button onclick="GB_show('<?php echo _("New ticket for Alert") ?>','<?php echo  $new_ticket_url ?>',490,'90%');">
            <img src="/ossim/alarm/style/img/tag_fill.png" height='14px' align="absmiddle" style="padding-right:8px"/>
            <span><?php echo _("Open Ticket")?></span>
        </button>

		<?php
		if(!$removable)
		{
			?>
				<button disabled="disabled">
				    <img src="/ossim/alarm/style/img/lock.png" height='14px' align="absmiddle" style="padding-right:8px"/>
				    <span><?php echo _("Close Alarm")?></span>
				</button>
			<?php
		}
		else
		{
			if($alarm->get_status() == 'open')
			{
	    		?>
				<button class="av_b_secondary" onclick="modify_alarm(1);">
				    <img src="/ossim/alarm/style/img/lock.png" height='14px' align="absmiddle" style="padding-right:8px">
				    <span><?php echo _("Close Alarm")?></span>
				</button>
				<?php
	    	}
	    	else
	    	{
	    	    ?>
				<button class="av_b_secondary" onclick="modify_alarm(2);">
				    <img src="/ossim/alarm/style/img/unlock.png" height='14px' align="absmiddle" style="padding-right:8px">
				    <span style="display:inline;font-size:11px"><?php echo _("Open Alarm")?></span>
				</button>
				<?php
	    	}
    	}
    	?>
	</div>
</div>

</body>
</html>
<?php
$db->close();
$geoloc->close();
?>
