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
require_once 'server_get_servers.php';
require_once '../conf/layout.php';


Session::logcheck('configuration-menu', 'PolicyServers');

$db         = new ossim_db();
$conn       = $db->connect();

if (Session::is_pro()) {
    $kali_statuses = array(
        1 => _("CONNECT TO USM CENTRAL"),
        2 => "",
        3 => _("DISABLE CONNECTION"),
        4 => _("DISABLE CONNECTION"),
        5 => _("DISABLE CONNECTION")
    );

    $extra_statuses = array(
        1 => _("Connected to "),
        2 => _("Request has been sent"),
        3 => _("Token denied by "),
        4 => _("Connected to "),
        5 => _("Failed to reach ")
    );

}

$browser    = new Browser(); //For checking the browser

$servers    = array();
$servers    = Server::get_list($conn);
$msg_text  = '';
$msg_class = '';

list($total_servers, $active_servers) = server_get_servers($servers);

$active_servers = ($active_servers == 0) ? "<font color=red><b>$active_servers</b></font>" : "<font color=green><b>$active_servers</b></font>";
$total_servers  = "<b>$total_servers</b>";

function getSpinnerForKali() {
    return "{name: ' <img id=\"spinner_kali\" src=\"/ossim/pixmaps/loading3.gif\" ', bclass: 'kali-sharing'}";
}

function getKaliJSON($status,$statuses,$kali_url) {
        return json_encode(["name"=>"<i>{$kali_url}&nbsp;</i>{$statuses[$status]}", "bclass"=>"kali-status-{$status}"]);
}

/*********  Arbor Info  *********/
$nodes = array();
$edges = array();

foreach($servers as $server)
{
    $nodes[$server->get_id()] = array(
        'color' => 'green',
        'shape' => 'rectangle',
        'label' => $server->get_name() . ' (' . $server->get_ip() . ')'
    );

	// get childs with uuid like a parent
	$sql = "SELECT distinct(HEX(server_dst_id)) as id FROM server_forward_role WHERE server_src_id=UNHEX(?)";

	if (!$rs = $conn->Execute($sql, array($server->get_id())))
	{
	    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	}

	$aux = array();

	while (!$rs->EOF)
	{
	    $aux[$rs->fields["id"]] = array(
	       'directed' => TRUE,
	       'length'   => 5,
	       'weight'   => 2,
	       'color'    => '#999999'
	    );

	    $rs->MoveNext();
	}

	$edges[$server->get_id()] = $aux;
}



/*********  Layout  *********/
$layout = array(
	"ip" => array(
		_('IP'),
		80,
		'true',
		'center',
		false
	) ,
	"name" => array(
		_('Name'),
		180,
		'true',
		'center',
		false
	) ,
	"port" => array(
		_('Port'),
		30,
		'true',
		'center',
		false
	) ,
	"sim" => array(
		_('SIEM'),
		40,
		'false',
		'center',
		false
	) ,
	"qualify" => array(
		_('Risk Assessment'),
		65,
		'false',
		'center',
		false
	) ,
	"correlate" => array(
		_('Correlation'),
		75,
		'false',
		'center',
		false
	) ,
	"cross correlate" => array(
		_('Cross correlation'),
		75,
		'false',
		'center',
		false
	) ,
	"store" => array(
		_('SQL Storage'),
		50,
		'false',
		'center',
		false
	) ,
	"alarm to syslog" => array(
		_('Alarm Syslog'),
		50,
		'false',
		'center',
		false
	) ,
	"reputation" => array(
		_('IP Rep'),
		30,
		'false',
		'center',
		false
	) ,
	"sem" => array(
		_('Logger'),
		50,
		'false',
		'center',
		false
	) ,
	"sign" => array(
		_('Sign'),
		45,
		'false',
		'center',
		false
	) ,
	"resend_alarms" => array(
		_('Forward Alarms'),
		50,
		'false',
		'center',
		false
	) ,
	"resend_events" => array(
		_('Forward Events'),
		50,
		'false',
		'center',
		false
	) ,
	"desc" => array(
		_('Description'),
		210,
		'false',
		'left',
		false
	)
);

list($colModel, $sortname, $sortorder, $height) = print_layout(array(), $layout, "name", "asc", 300);



/*********  Message  *********/

$msg = GET('msg');

switch ($msg)
{
    case 'created':
        $msg_text  = _('The Server has been created successfully');
        $msg_class = 'nf_success';
        break;

    case 'updated':
        $msg_text  = _('The Server has been updated successfully');
        $msg_class = 'nf_success';
        break;

    case 'deletesystemfirst':
        $msg_text  = _('Removing the server from this page is not allowed. To remove the server, please go to AlienVault Center and delete the system from the component list.');
        $msg_class = 'nf_error';
        break;

    case 'nodeleteremote':
        $msg_text  = _('Unable to delete a parent server. Go to Configuration->Deployment->AlienVault Center and delete the system');
        $msg_class = 'nf_error';
        break;

    case 'nodelete':
        $msg_text  = _('Unable to delete the local server');
        $msg_class = 'nf_error';
        break;

    case 'unknown_error':
        $msg_text  = _('Invalid action - Operation cannot be completed');
        $msg_class = 'nf_error';
        break;
    case 'unallowed':
        $msg_text  = _('Sorry, action not allowed');
        $msg_class = 'nf_error';
        break;
}

$db->close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<meta http-equiv="X-UA-Compatible" content="IE=7" />

	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',         'def_path' => TRUE),
            array('src' => 'flexigrid.css',         'def_path' => TRUE),
            array('src' => 'tree.css',              'def_path' => TRUE),
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',             'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',          'def_path' => TRUE),
            array('src' => 'jquery.flexigrid.js',       'def_path' => TRUE),
            array('src' => 'jquery.tmpl.1.1.1.js',      'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js',        'def_path' => TRUE),
            array('src' => 'utils.js',                  'def_path' => TRUE),
            array('src' => 'notification.js',           'def_path' => TRUE),
            array('src' => 'token.js',                  'def_path' => TRUE),
            array('src' => 'arbor/arbor.js',            'def_path' => TRUE),
            array('src' => 'arbor/graphics.js',         'def_path' => TRUE),
            array('src' => 'arbor/renderer.js',         'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');
    ?>

    <style>

        #toggle_hierarchy
        {
            margin: 15px 0 5px 0;
            font-size: 13px;
            text-transform: uppercase;
            cursor: pointer;
        }

        #toggle_hierarchy img
        {
            vertical-align: bottom;
        }

	.kali-status-2 {
		white-space: nowrap;
		display: inline-block;
	}

	.kali-sharing {
		position:relative;
	}

	.kali-sharing::before {
		cursor: default;
	}

	.kali-sharing::before {
		content: "";
		background-size: 20px 20px !important;
		width: 20px;
		height: 20px;
		position: absolute;
		left: 0px;
		top: -3px;
	}

	.kali-sharing i {
                text-transform: none;
                color: #99A2A9;
                float: left;
                text-indent: 22px;
        }

        .kali-status-2::before {
		background: url(/ossim/pixmaps/theme/loading2.gif) no-repeat center left;
		-webkit-transform:scale(1.2);
		-webkit-transform-origin:top left;
		-moz-transform:scale(1.2);
		-moz-transform-origin:top left;
		-ms-transform:scale(1.2);
		-ms-transform-origin:top left;
		-o-transform:scale(1.2);
		-o-transform-origin:top left;
		transform:scale(1.2);
		transform-origin:top left;
        }

        .kali-status-3::before , .kali-status-5::before {
                background: url(/ossim/pixmaps/denied.png) no-repeat center left;
        }

        .kali-status-4::before {
                background: url(/ossim/pixmaps/link2.png) no-repeat center left;
        }

    </style>

	<script type='text/javascript'>

    	var msg_no_select = "<?php echo Util::js_entities(_('You must select a server')) ?>";
    	var msg_delete    = "<?php echo Util::js_entities(_('Are you sure you want to delete the server?')) ?>";
    	var confirm_keys  = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};
	    var kalistatuses = {<?php echo implode(",",array_map(function($v,$k) {return "$k : '$v'";},$kali_statuses,array_keys($kali_statuses)));?>};

        var url = "controllers/server_actions.php";

        function setKaliStatus(status,extra) {
            var kali = $('.kali-sharing');
            var text = kalistatuses[status];
            if (extra != undefined) {
                text = '<i>'+extra+'</i>&nbsp'+text;
            }
            kali.attr('class','kali-sharing kali-status-'+status).html(text);
        }

	function share_server() {
		var item = $("<?php echo sprintf("<div class='kali-popup'><div>%s <a href='%s' target='_blank'>%s ></a></div><hr/><div>%s</div><div><textarea placeholder='%s'></textarea></div><hr/></div>",
                        _("Connect to USM Central to securely send alarms"),
                        "https://cybersecurity.att.com/documentation/usm-central/connect-deployment.htm?cshid=2006#ConnectAppliance",
                        _("Learn more"),
                        _("Enter Token from USM"),
                        _("Examples: API-token-goes-here--123")
                        );?>");
		av_confirm(item,{"yes": "<?php echo _('Connect') ?>", "no": "<?php echo _('Cancel') ?>"}).done(function() {
			setKaliStatus(2,'<?php echo $extra_statuses[2]?>');
			$.post(url,{"token":$(item).find('textarea').val(),"action":"connect"}).done(function(data) {
				data = $.parseJSON(data);
				setKaliStatus(4, '<?php echo $extra_statuses[1]?>' + data.url);
				notify("<?php echo _("USM Central sharing started")?>",'nf_success');
                $(".kali-sharing").unbind('click');
                $(".kali-sharing").click(unshare_server);
			}).fail(function(jqXHR, textStatus, errorThrown) {
				setKaliStatus(1);
				notify($.trim($(jqXHR.responseText).text()),'nf_error');
			});
		});
		top.vex.getAllVexes().width(651).find(".vex-dialog-message").height("220px");
	}

    function unshare_server() {
        var text = $('.kali-sharing i').text().replace("<?php echo $extra_statuses[$kalistatus]?>","");
		var popup = "<?php echo sprintf("<div class='kali-popup'><div>%s</div><hr/><div>",_("Are you sure you want to disconnect from %SERVER%?"));?>";
		popup = popup.replace("%SERVER%",text);

        av_confirm(popup,{"yes": "<?php echo _('Yes, Disconnect') ?>", "no": "<?php echo _('Cancel') ?>"}).done(function() {
           	var clone = $('.kali-sharing').clone();
            setKaliStatus(2,'<?php echo $extra_statuses[2]?>');
            $.post(url,{"action":"disconnect"}).done(function() {
                setKaliStatus(1);
				notify("<?php echo _("USM Central sharing stopped")?>",'nf_success');
                $(".kali-sharing").unbind('click');
                $(".kali-sharing").click(share_server);
            }).fail(function(jqXHR, textStatus, errorThrown) {
              	$('.kali-sharing').replaceWith(clone);
				notify($.trim($(jqXHR.responseText).text()),'nf_error');
            });
        });
        top.vex.getAllVexes().width(651).find(".vex-dialog-message").height("40px");
    }

    	function edit_server(id)
    	{
        	if (typeof id == 'string' && id != '')
			{
				document.location.href = 'modifyserverform.php?id=' + id
			}
			else
			{
			    av_alert(msg_no_select);
			}
    	}

    	function delete_server(id)
    	{
        	if (typeof id == 'string' && id != '')
			{

				av_confirm(msg_delete, confirm_keys).done(function()
				{
    				var dtoken = Token.get_token("delete_server");
    				document.location.href = "<?php echo AV_MAIN_PATH ?>/server/deleteserver.php?confirm=yes&id=" + id + "&token=" + dtoken;
				});
			}
			else
			{
			    av_alert(msg_no_select);
			}
    	}

    	function new_server()
    	{
        	document.location.href = 'newserverform.php';
    	}


    	function action(com, grid)
		{
			com = stripGridButtonExtratext(com);
			var items = $('.trSelected', grid);
			try
			{
    			var id = items[0].id.substr(3)
			}
			catch(Err)
			{
    			var id = ''
			}
			switch (com) {
				case '<?php echo _('Delete selected') ?>': delete_server(id);  break;
                                case '<?php echo _('Modify') ?>': edit_server(id);  break;
                                case '<?php echo _('Add Server') ?>': new_server();  break;
                                case kalistatuses[2]: break;
				//this is required since "com" is never changed by dom manipulation.
				//so we need to track real time status, to determine changes made to DOM
                                case kalistatuses[1]:
                                case kalistatuses[3]:
                                case kalistatuses[4]:
					var kalitext = stripGridButtonExtratext($(grid).find('.kali-sharing').html());
					kalitext == kalistatuses[1] ? share_server(grid) : unshare_server(grid);
					break;
			}
		}

		function stripGridButtonExtratext(text) {
			return text.replace(/<i>.*<\/i>\s*(&nbsp;)*/ig,"");
		}

		function menu_action(com, id, fg, fp)
		{
			if (com == 'delete')
			{
				delete_server(id)
			}

			if (com == 'modify')
			{
				edit_server(id)
			}

			if (com == 'new')
			{
			    new_server()
			}
		}

		function apply_changes()
        {
			<?php $back = preg_replace ('/([&|\?]msg\=)(\w+)/', '\\1', $_SERVER["REQUEST_URI"]);?>
			document.location.href = '../conf/reload.php?what=servers&back=<?php echo urlencode($back);?>'
        }

        function getKaliJSON(){
            $.ajax(
                {
                    type: "POST",
                    async: true,
                    url: "controllers/server_actions.php",
                    data: { action: "status"},
                    success: function(response)
                    {
                        var obj = JSON.parse(response);
                        //There is a error
                        if(obj.status == "error") {
                            notify(obj.data);
                        } else {
                            $(".kali-sharing").addClass(obj.data.bclass);
                            $("#spinner_kali").after(obj.data.name);
                            //It's not connected to USM Central
                            if(obj.data.bclass == "kali-status-1"){
                                $(".kali-sharing").click(share_server);
                            }else if (obj.data.bclass == "kali-status-4" || obj.data.bclass == "kali-status-3" || obj.data.bclass == "kali-status-5") {
                                // kali-status-4 = 'ok', kali-status-3 = unable to connect to USM Central
                                // We want to disconnect from Central always, including when the connection is lost with Central
                                $(".kali-sharing").click(unshare_server);
                            }
                        }
                        $("#spinner_kali").remove();
                    },
                    error: function(){
                        notify('Unable to check sensors status', 'nf_error');
                        $("#spinner_kali").remove();
                    }
                });
        }


		$(document).ready(function()
		{
			<?php
			if ($msg_text != '' && $msg_class != '')
			{
				echo 'notify("'. $msg_text .'", "'. $msg_class .'");';
			}

			if (Session::is_pro() && $browser->name !='msie')
			{
			?>
				var sys = arbor.ParticleSystem({friction:0.5, stiffness:500, repulsion:700, dt:<?php echo (count($nodes) > 1)? '0.009' : 0 ?>})
				    sys.parameters({gravity:true});
				    sys.renderer = Renderer("#viewport");

				var data =
				{
					nodes: <?php echo json_encode($nodes) ?>,
					edges: <?php echo json_encode($edges) ?>
				};

				sys.graft(data);

				setTimeout(function()
				{
					sys.parameters({dt:0});

				}, 7500);

			<?php
			}
			?>

			$("#flextable").flexigrid(
			{
				url: 'getserver.php',
				dataType: 'xml',
				colModel : [<?php echo $colModel; ?>],
				buttons :
				[
					<?php
					if (Session::is_pro())
					{
					echo getSpinnerForKali();
					?>
                    ,
					{separator: true},
    					{name: '<?php echo _("Add Server") ?>', bclass: 'add', onpress : action},
    					{separator: true},
    					{name: '<?php echo _("Delete selected") ?>', bclass: 'delete', onpress : action},
    					{separator: true},
					<?php
					}
					?>
					{name: '<?php echo _("Modify") ?>', bclass: 'modify', onpress : action},
					{separator: true},
					{name: '<?php echo _("Active Servers") ?>: <?php echo $active_servers ?>', bclass: 'info', iclass: 'ibutton'},
					{name: '<?php echo _("Total Servers") ?>: <?php echo $total_servers ?>', bclass: 'info', iclass: 'ibutton'}
				],
				sortname: "<?php echo $sortname ?>",
				sortorder: "<?php echo $sortorder ?>",
				usepager: true,
				pagestat: '<?php echo _("Displaying") ?> {from} <?php echo _("to") ?> {to} <?php echo _("of") ?> {total} <?php echo _("servers") ?>',
				nomsg: '<?php echo _("No servers found in the system") ?>',
				useRp: true,
				rp: 20,
				contextMenu: 'myMenu',
				onContextMenuClick: menu_action,
				showTableToggleBtn: false,
				singleSelect: true,
				width: get_flexi_width(),
				height: 'auto',
				onDblClick: edit_server,
			});


			$('#toggle_hierarchy').on('click', function()
			{
    			var graph = $('#server_hierarchy')
    			var that  = this

    			if (graph.is(':visible'))
    			{
        		    $('img', that).attr('src', '../pixmaps/arrow_green.gif');
        			graph.slideUp(300);
    			}
    			else
    			{
        			$('img', that).attr('src', '../pixmaps/arrow_green_down.gif');
        			graph.slideDown(300);
    			}

			});

            getKaliJSON();

		});

	</script>

</head>
<body style="margin:0">

    <?php
    //Local menu
    include_once '../local_menu.php';
    ?>

	<table id="flextable" style="display:none"></table>
    <?php
        if (Web_indicator::is_on("Reload_servers"))
        {
            echo "<button class='button' onclick='apply_changes()'>"._("Apply Changes")."</button>";
        }
    ?>

	<?php
	if (Session::is_pro())
	{
	    ?>

		<div id='toggle_hierarchy' class='av_link'>
			<img src="../pixmaps/arrow_green.gif"/>
			<?php echo _("Server Hierarchy") ?>
		</div>

		<div id='server_hierarchy'>
			<?php
			if ($browser->name =='msie')
			{
                ?>
                <div style='font-weight:bold;'><?php echo _('Server Hierarchy Graph is not available in Internet Explorer') ?></div>
                <?php
            }
            ?>
			<canvas id="viewport" width='800' height="250"></canvas>
		</div>
		<br>
		<?php
    }
    ?>

	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu">
        <li class="hostreport">
            <a href="#modify" class="greybox" style="padding:3px">
                <img src="../pixmaps/tables/table_edit.png" align="absmiddle"/>
                <?php echo _("Modify") ?>
            </a>
        </li>
        <li class="hostreport">
            <a href="#delete" class="greybox" style="padding:3px">
                <img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/>
                <?php echo _("Delete") ?>
            </a>
        </li>
        <li class="hostreport">
            <a href="#new" class="greybox" style="padding:3px">
                <img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/>
                <?php echo _("Add Server") ?>
            </a>
        </li>
    </ul>

</body>
</html>
