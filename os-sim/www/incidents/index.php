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


require_once 'incident_common.php';

Session::logcheck("analysis-menu", "IncidentsIncidents");

//DB connection

$db   = new ossim_db();
$conn = $db->connect();

//Tags
$incident_tag = new Incident_tag($conn);
$tag_list     = $incident_tag->get_list();

//Load users and entities (Autocomplete)

$autocomplete_keys  = array('users', 'entities');
$users_and_entities = Autocomplete::get_autocomplete($conn, $autocomplete_keys);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>


    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>



	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
        <link rel="stylesheet" type="text/css" href="../style/lightbox.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.tag-it.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_dropdown_tag.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/av_table.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/tags/tags.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/av_tags.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dropdown.css"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script> 
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.dropdown.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/av_tags.js.php"></script>
    <script type="text/javascript" src="../js/av_dropdown_tag.js"></script>

	   <script type="text/javascript">
        var fn;
        var mcu = '<?php echo AV_MAIN_PATH?>/incidents/incidenttag.php';
	$(document).ready(function() {
            var options =
            {
                'load_tags_url'         : '<?php echo AV_MAIN_PATH?>/incidents/incidenttag.php?action=tags',
                'manage_components_url' : mcu,
                'allow_edit'            : true,
                'tag_type'              : "incident",
                'show_tray_triangle'    : true,
                'components_check_class': "tchbx",
            };

            fn = $('#label_selection').av_dropdown_tag(options);


	    $('#act-close').click(function() {
                $('#filter').append('<input type="hidden" name="close" value="Close selected"/>').submit();
	    });
            $('#act-delete').click(function() {
                $('#filter').append('<input type="hidden" name="delete" value="Delete selected"/>').submit();
            });
            $('.tchbx').change(function() {
		$('#allaction').attr('checked', false);
		$("#selectall").hide();
                if ($('.tchbx:checked').length) {
                    $('#label_selection img,#button_action').removeClass("disabled").removeClass("av_b_disabled");
		    $('#button_action').prop("disabled",false);
                } else {
                    $('#label_selection img,#button_action').addClass("disabled").addClass("av_b_disabled");
		    $('#button_action').prop("disabled",true);
                }
            }).change();
            $('#label_selection').on('click','img', function(e) {
		if ($(this).hasClass("disabled")) {
                	e.stopPropagation();
		}
            });
            $('body').on('click','.av_dropdown_tag_checkbox',function() {
                var url = mcu;
                if ($('#allaction:checked').length) {
                    url += "?"+$("#filter").serialize();
                }
                fn.setMCU(url);
            });
	});


        function checkall()
        {
            if ($('#ticket0').attr('checked'))
            {
                $('.tchbx').attr('checked', true).change();
		$("#selectall").show();
            }
            else
            {
                $('.tchbx,#allaction').attr('checked', false).change();
		$("#selectall").hide();
            }
        }


        function get_chk_selected()
        {
            var size = $("input[type='checkbox']:checked").length;

            if (size > 0)
            {
                var selected = new Array();
                $("input[type='checkbox']:checked").each(function (index) {

                    var id   = parseInt($(this).val());

                    if (!isNaN(id))
                    {
                        selected[selected.length] = id;
                    }
                });

                return selected;
            }
        }


        function execute_action(action, tag)
        {
            var selected = get_chk_selected();

            if (typeof(selected) == 'undefined')
            {
                return;
            }

            var msg_action = '';

            if (action == 'apply_tags')
            {
                msg_action = "<?php echo _("Applying tags to selected tickets")?>";
            }
            else if (action == 'remove_tags')
            {
                msg_action = "<?php echo _("Removing tags to selected tickets")?>";
            }
            else
            {
                return ;
            }

            var loading    = "<div>"
                                + "<img src='../pixmaps/loading3.gif' alt='<?php echo _("Loading")?>'/>"
                                + "<span style='margin-left: 5px;'>" + msg_action + ", <?php echo _("please wait")?> ...</span>"
                           + "</div>";

            $.ajax({
                type: "POST",
                url: "manage_incident_tags.php",
                data: "action="+action+"&selected_incidents="+selected.join(",")+"&tag="+tag,
                beforeSend: function(xhr) {
                    $('#left_ct').html(loading);
                },
                success: function(html){
                    
                    $('#left_ct').html('');

                    var status = html.split("###");

                    if (status[0] == "error")
                    {
                        var content = "<div class='cont_info'>"+status[1]+"</div>";

                        var config_nt = {
                            content: content,
                            options: {
                                type: 'nf_error',
                                cancel_button: false
                            },
                            style: 'width: 90%;'
                        };

                        nt = new Notification('nt_mi',config_nt);
                        
                        $("#middle_ct").html(nt.show());
                        $("#middle_ct").fadeIn(2000);
                    }
                    else 
                    {
                        if (status[0] == "OK")
                        {
                            if (status[1] == "No incidents")
                            {
                                return;
                            }

                            if (status[1] == "DB Error")
                            {
                                selected = status[2].split(",");
                            }
                            
                            if (action == 'apply_tags')
                            {
                                var html_tag = (status[1] == "DB Error") ? status[3] : status[2];

                                for (var i=0; i<selected.length; i++)
                                {
                                    // Append only if not exists
                                    var _title = $(html_tag).children().attr('title');

                                    if (!$('#tags_'+selected[i]).find('label[title="'+_title+'"]').length)
                                    {
                                        $('#tags_'+selected[i]).append(html_tag);
                                    }
                                }
                            }
                            else
                            {
                                for (var i=0; i<selected.length; i++)
                                {
                                    $('#tags_'+selected[i]).html('');
                                }
                            }
                        }
                        else
                        {
                            var content = "<?php echo _('You do not have permission to perform this action')?>";

                            var config_nt = {
                                content: content,
                                options: {
                                    type: 'nf_error',
                                    cancel_button: false
                                },
                                style: 'width: 90%;'
                            };

                            nt = new Notification('nt_mi',config_nt);
                            
                            $("#middle_ct").html(nt.show());
                            $("#middle_ct").fadeIn(2000);
                        }
                    }

                    setTimeout('$("#middle_ct div").fadeOut(4000);', 25000);
                }
            });
        }

        $(document).ready(function(){
            var token = Token.get_token("close_incident");
            $("#filter").submit(function () {
                $(this).append("<input type='hidden' name='token' value='"+token+"'/>");
            });
            $('.tiptip').tipTip();

            $('.td_tags').click(function() {
                var tag = $(this).attr("id").replace("tag_", '');
                execute_action('apply_tags', tag);
            });

            $('#link_rm_tags').click(function(){
                execute_action('remove_tags', '');
            });

            //Autocomplete
            var users_and_entities = [<?php echo $users_and_entities;?>];

            $("#text_in_charge").autocomplete(users_and_entities, {
                minChars: 0,
                width: 300,
                max: 150,
                matchContains: true,
                mustMatch: true,
                autoFill: false,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                if (typeof(item) != 'undefined' && item != null)
                {
                    $('#in_charge').val(item.id);
                }
                else
                {
                    $("#in_charge").val($('#text_in_charge').val());
                }
            });

            $('#text_in_charge').blur(function() {

                if ($('#text_in_charge').val() == '')
                {
                    $("#in_charge").val('');
                }
            });
        });

	</script>
	<style type='text/css'>
		        
        #table_3 
        {
            margin-top: 5px;
            border:none;
        }
		
        .nobborder 
        { 
			border-bottom: none;
			text-align: center !important;
		}
		
		.topborder
		{
			border: none;
			border-top: solid 1px #CBCBCB;
		}
		
		.f_header span
		{    		
    		margin-right:3px
    	}
    	
    	.f_header a
		{    		
    		vertical-align: bottom !important;
    	}
		
		.f_header a img
		{
    		vertical-align: bottom !important;
		}
		
		.alp
		{
    		text-align:left;
    		padding-left: 5px;
		}
        .av_table_wrapper_td {
	    position: relative;
	    min-width: 185px;
	    top: -5px;
	}
        
        #left_ct
        {
            float: left;
            width: 48%;
            height: 20px;
            text-align: left;
        }
        
        #left_ct div
        {
            font-weight: normal;
        }
        
        #middle_ct
        {
            width: 70%;
            height: 15px;
            text-align:center;
            margin: auto;
            position: relative;
        }
        
        #right_ct
        {
            float: right;
            width: 48%;
            height: 20px;
        }
        
        #theader_left
        {
            float:left; 
            width:55%; 
            text-align: right;
            padding: 6px 3px 3px 3px;
        }
    
        #theader_right
        {
            float:right; 
            width:18%; 
            text-align: right;
            padding:3px;   
        }
        
        #notags
        {
            width: 165px;
            padding: 5px;
        }
        
        #link_rm_tags
        {
            text-align: right;
        }
        
        .td_tags
        {
            padding: 5px;
            text-align: left;
            color: black;
        }
        
        .td_tags label
        {
            border: none;
            cursor: pointer;
        }
        
        .td_rm_tags
        {
            padding: 5px;
            text-align: center;    
        }
        
        .cont_info
        {
            position:absolute; 
            width: 100%; 
            top: -4px; 
            left: 0px;
            margin:auto;
        }
        
        .ticket_tr 
        {
            height: 30px;
        }
        .srch-btn {
            float: right;
            line-height: 16px;
        }
	</style>
	</head>
<body>
<?php 

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version");
$order_by = GET("order_by");
if (!$order_by)
{
    $order_by   = 'life_time';
    $order_mode = 'DESC';
}
$page = GET("page");
if ($page == '' || $page <= 0)
{
    $page = 1;
}
if (GET('status') === NULL)
{
    $status = 'Open';
}

$criteria = get_criteria();
extract($criteria);
// Close selected tickets
if (GET('close') == 'Close selected' || GET('delete') == 'Delete selected')
{
    if (!Token::verify('tk_close_incident', GET('token')))
    {
        $config_nt = array(
            'content' => _('Action not allowed'),
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
         );
         $nt = new Notification('c_nt_oss_error', $config_nt);
         $nt->show();
    }
    else
    {
        $map = isset($_GET["allaction"]) && GET("allaction") == "on"
            ? get_ids($conn,$criteria)
            :  array_filter(
                array_map(function($k) use ($_GET) {return preg_match("/^ticket\d+/", $k) && GET($k) != '' ? GET($k) : false;},array_keys($_GET))
            );
        $todelete = array();
        foreach ($map as $cst_inc_id) {
            if (!Incident::user_incident_perms($conn, $cst_inc_id, 'closed')) continue;
            if (GET('delete') == 'Delete selected') {
                 $todelete[] = $cst_inc_id;
                 continue;
            }
            list ($cst_incident) = Incident::search($conn, array('incident_id' => $cst_inc_id));

            if (is_object($cst_incident) && !empty($cst_incident))
            {
                //Incident is not already closed
                    $cst_prev_status = $cst_incident->get_status();

                    if ($cst_prev_status != 'Closed' && Incident::user_incident_perms($conn, $cst_inc_id, 'closed'))
                    {
                    $cst_status      = 'Closed';
                    $cst_priority    = $cst_incident->get_priority();
                    $cst_user        = Session::get_session_user();
                    $cst_description = sprintf(_('Ticket automatically closed by %s'), $cst_user);
                    $cst_action      = sprintf(_('Change ticket status from %s to Closed'), ucfirst($cst_incident->get_status()));
                    $cst_transferred = NULL;
                    $cst_tags        = $cst_incident->get_tags();

                    Incident_ticket::insert($conn, $cst_inc_id, $cst_status, $cst_priority, $cst_user, $cst_description, $cst_action, $cst_transferred, $cst_tags);
                    }
            }
    }
    if ($todelete) {
	Incident::delete($conn,$todelete);
    }
    }
}

$global_rpp = $rows_per_page   = 50;
$incident_list   = Incident::search($conn, $criteria, $order_by, $order_mode, $page, $rows_per_page);
$total_incidents = Incident::search_count($conn);
?>
<!-- filter -->
<form name="filter" id="filter" method="GET" action="<?php echo $_SERVER["SCRIPT_NAME"] ?>">
<input type="hidden" name="page" id="page" value=""/>
    <?php
	if ($advanced_search)
	{ 
		?>
		<input type="hidden" name="advanced_search" value="1"/>
		<?php
	}
	?>
	<br>
	<table align="center" width="100%" style="border:none" cellpadding="2">
		<tr>
			<th colspan="7" class="headerpr f_header">
    			<span>
    			<?php    			
    			$change_to = _("switch to ");
    			
    			if ($advanced_search) 
    			{
    				$label     = _("Advanced Filters");
    				$change_to.= ' ' . _("Simple");
    				
    				echo " $label <a href=\"" . $_SERVER["SCRIPT_NAME"] . "\" title=\"$change_to\">[$change_to]</a>";
    			}
    			else 
    			{
    				$label     = _("Simple Filters");
    				$change_to.= ' ' . _("Advanced");
    				echo " $label <a href=\"" . $_SERVER["SCRIPT_NAME"] . "?advanced_search=1\" title=\"$change_to\">[$change_to]</a>";
    			}
    			?>
    			</span>    			
    			<a href="../report/incidentreport.php" class="tiptip" title="<?php echo _("Ticket Report")?>">
                    <img src="../pixmaps/menu/reports_menu.png" width="13" border="0" align="absmiddle"/>
    			</a>
			</th>
		</tr>
		<tr>
			<td class="noborder alp"> <?php echo gettext("Class"); /* ref */ ?> </td>
			<td class="noborder alp"> <?php echo gettext("Type"); /* type */ ?> </td>
			<td class="noborder alp"> <?php echo gettext("Search text"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("Assignee"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("Status"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("Priority"); ?> </td>
			<td class="noborder alp"></td>
		</tr>
		<tr>
			<td class="alp" style="border-width: 0px;">
				<select name="ref" onChange="document.forms['filter'].submit()">
				<?php
					$ref_types = array (
						""     		    => _("ALL"),
						"Alarm"   		=> _("Alarm"),
						"Event"   		=> _("Event"),
						"Anomaly" 		=> _("Anomaly"),
						"Vulnerability" => _("Vulnerability")
					);
					
					foreach ($ref_types as $k => $v)
					{
						$selected = ($k == $ref) ? "selected='selected'" : "";
						echo "<option value='$k' $selected>$v</option>";
					}
				?>
				</select>
			</td>
		
			<td class="alp" style="border-width: 0px;">
				<select name="type" onChange="document.forms['filter'].submit()">
					<option value="" <?php if (!$type) echo "selected" ?>><?php echo gettext("ALL"); ?></option>
					<?php
						$customs = array();
						
						foreach(Incident_type::get_list($conn) as $itype) 
						{
							$id = $itype->get_id();
							if (preg_match("/custom/",$itype->get_keywords()))
							{
								$customs[] = $itype->get_id();
							}
							?>
							<option <?php if ($type == $id) echo "selected" ?> value="<?php echo $id ?>"><?php echo $id?></option>
							<?php
						} 
					?>
				</select>
			</td>
		
			<td class="alp" style="border-width: 0px;">
				<input type="text" name="with_text" value="<?php echo $with_text?>"/>
				<?php if (preg_match("/^\d+\.\d+\.\d+\.\d+,\s*\d+\.\d+\.\d+\.\d+/", $with_text)) { ?><br><i><?php echo _("Are you searching by multiples IPs? Try searching one by one") ?></i><?php } ?>
			</td>
			
			<td class="alp" style="border-width: 0px;">
				<input type="text"   id="text_in_charge" name="text_in_charge" value="<?php echo $text_in_charge ?>"/>
                <input type="hidden" id="in_charge" name="in_charge" value="<?php echo $in_charge ?>"/>
			</td>
			
			<td class="alp" style="border-width: 0px;">
                <?php $ticket_status = array('Open', 'Assigned', 'Studying', 'Waiting', 'Testing', 'Closed'); ?>
				<select name="status" onChange="document.forms['filter'].submit()">
					<option value=""><?php echo gettext("ALL"); ?></option>
					<option value="not_closed" <?php echo (($status == 'not_closed') ? "selected='selected'" : "") ?>><?php echo gettext("ALL [Not Closed]"); ?></option>
					<?php
                        foreach ($ticket_status as $st)
                        {
                            $selected = ($status == $st) ? "selected='selected'" : "";
                            echo "<option value='$st' $selected>".gettext($st)."</option>";
                        }
                    ?>
                </select>
			</td>
            					
			<td class="alp" style="border-width: 0px;">
				<select name="priority" onChange="document.forms['filter'].submit()">
					<option value=""><?php echo gettext("ALL"); ?></option>
					<option <?php if ($priority == "High") echo "selected='selected'" ?> value="High"><?php echo gettext("High"); ?></option>
					<option <?php if ($priority == "Medium") echo "selected='selected'" ?> value="Medium"><?php echo gettext("Medium"); ?> </option>
					<option <?php if ($priority == "Low") echo "selected='selected'" ?> value="Low"><?php echo gettext("Low"); ?></option>
				</select>
			</td>
			<td class="av_table_wrapper_td">
				<div class="av_table_actions" data_bind="table_actions">
					<a id="label_selection" class="avt_action" data-selection="avt_action">
					<img class="avt_img" src="/ossim/pixmaps/label.png"/>
					</a>
<input type="submit" class="button avt_action small srch-btn" name="filter" value="Search">
					<button id="button_action" class="button avt_action small" href="javascript:;" data-dropdown="#dropdown-actions" data-selection="avt_action">Actions &nbsp;▾</button>
				</div>
			</td>
		</tr>
		
		<?php
		if ($advanced_search) 
		{
		?>

        <tr>
			<td class="noborder alp"><?php echo _("with Submitter") ?> </td>
			<td class="noborder alp"><?php echo _("with Title") ?></td>
			<td class="noborder alp"><?php echo _("with Attachment Name") ?></td>
			<td class="noborder alp" colspan="4"><?php echo _("with Tag") ?></td>
		</tr>
		<tr>
			<td class="alp" style="border-width: 0px;"><input type="text" name="submitter" value="<?php echo $submitter ?>" /></td>
			<td class="alp" style="border-width: 0px;"><input type="text" name="title" value="<?php echo $title ?>" /></td>
			<td class="alp" style="border-width: 0px;"><input type="text" name="attachment" value="<?php echo $attachment ?>" /></td>
			<td class="alp" style="border-width: 0px;" colspan="4">
				<select name="tag" onChange="document.forms['filter'].submit()">
					<option value=""></option>
					<?php
					foreach($tag_list as $t) 
					{ 
						$selected = ($tag == $t['id']) ? "selected='selected'" : ''; 
						?>
						<option value="<?php echo $t['id'] ?>" <?php echo $selected ?>><?php echo $t['name'] ?></option>
						<?php
					} 
					?>
				</select>
			</td>
		</tr>
		<?php
		}
		if ($global_rpp < $total_incidents) {
		?>

                        <tr>
                        <td colspan="11" class="hidden" id="selectall">
                                <input type="checkbox" class="hidden" name="allaction" id="allaction"/>
                                <?=sprintf(_("You have selected %s tickets on this page."),count($incident_list))?>
                                <a href="#" onclick="$('#allaction').attr('checked','checked'); $('#selectall').hide(); return false;"><?=sprintf(_("Select all %s tickets."),$total_incidents)?></a>
                        </td>
                        </tr>
		<?php } ?>

	</table>
	
	<!-- end filter -->
	
    <?php
    if (count($incident_list) >= $total_incidents)
    {
        $total_incidents = count($incident_list);
        if ($total_incidents > 0)
        {
            $rows_per_page = $total_incidents;
        }
    }


    $filter = '';

	foreach($criteria as $key => $value)
	{
		$filter.= "&$key=" . urlencode($value);
	}
	
	if ($advanced_search)
	{
		$filter.= "&advanced_search=" . urlencode($advanced_search);
	}
	
	// Next time reverse the order of the column
	// XXX it reverses the order of all columns, should only
	//     reverse the order of the column previously sorted
	
	if ($order_mode)
	{
		$order_mode = $order_mode == 'DESC' ? 'ASC' : 'DESC';
		$filter.= "&order_mode=$order_mode";
	}
	?>
    <div style='clear:both;'>
		<table class='table_list'>
			<tr>
				<th><input type="checkbox" id="ticket0" onclick="checkall()"/></th>
				<th nowrap='nowrap'><a href="?order_by=id<?php echo $filter?>"><?php echo _("Ticket") . order_img('id') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=title<?php echo $filter ?>"><?php echo _("Title") . order_img('title') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=priority<?php echo $filter ?>"><?php echo _("Priority") . order_img('priority') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=date<?php echo $filter ?>"><?php echo _("Created") . order_img('date') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=life_time<?php echo $filter ?>"><?php echo _("Life Time") . order_img('life_time') ?></a></th>
				<th><?php echo _("Assignee") ?></th>
				<th><?php echo _("Submitter") ?></th>
				<th><?php echo _("Type") ?> <a href="incidenttype.php" style="font-weight:normal"><img align="absmiddle" src="../vulnmeter/images/pencil.png" border="0" title="Edit Types"></a></th>
				<th><?php echo _("Status") ?></th>
				<th><?php echo _("Labels") ?></th>
			</tr>
			<?php
			$row = 1;
			
			if ($total_incidents > 0)
			{
				foreach($incident_list as $incident)
                {                    
			$id = $incident->get_id();
                    ?>

                    <tr valign="middle" class="ticket_tr">
                        <td>
                            <input type="checkbox" class="tchbx" id="tchbx_<?=$id?>" name="ticket<?= $row ?>" data-id="<?=$id?>" value="<?=$id?>"/>
                        </td>
                                    
                        <td>
                            <a href="incident.php?id=<?php echo $incident->get_id()?>&edit=1"><?php echo $incident->get_ticket(); ?></a>
                        </td>
                        
                        <td>
                            <strong><a href="incident.php?id=<?php echo $incident->get_id()?>&edit=1"><?php echo $incident->get_title(); ?></a></strong>
                            <?php
                                if ($incident->get_ref() == "Vulnerability") 
                                {
                                    $vulnerability_list = $incident->get_vulnerabilities($conn);
                                    
                                    // Only use first index, there shouldn't be more
                                    $vl = NULL;
                                    
                                    $vl     = $vulnerability_list[0]->get_ip();
                                    $v_port = $vulnerability_list[0]->get_port();
                                    
                                    if (!empty($v_port))
                                    {
                                        $vl .= ":".$vulnerability_list[0]->get_port();
                                    }
                                    
                                    if (!empty($vl))
                                    {    
                                        echo " <font color='grey' size='1'>($vl)</font>";
                                    }
                                    
                                }
                            ?>
                        </td>
					
                        <?php $priority = $incident->get_priority(); ?>
                        <td><?php echo Incident::get_priority_in_html($priority) ?></td>
                        <td nowrap='nowrap'><?php echo $incident->get_date() ?></td>
                        <td nowrap='nowrap'><?php echo $incident->get_life_time() ?></td>
                            <?php
							if (preg_match("/pro|demo/i",$version) && valid_hex32($incident->get_in_charge())) 
                            {
								$in_charge_name = Acl::get_entity_name($conn, $incident->get_in_charge());
                            }
                            else
                                $in_charge_name = $incident->get_in_charge_name($conn);
                              
                            ?>
                        <td><?php echo $in_charge_name ?></td>
                        <?php 
                            $submitter          = $incident->get_submitter();
                            $submitter_data     = explode("/", $submitter);
                        ?>
                        <td><?php echo $submitter_data[0]?>&nbsp;</td>
                        <td><?php echo $incident->get_type() ?></td>
                                               
                        <td>
                            <?php 
                            Incident::colorize_status($incident->get_status()); 
                            if ($incident->get_status() == 'Closed')
                                echo " <span style='font-size: 9px;'>[".$incident->get_last_update()."]</span>";
                            ?>
                        </td>
                        
                        <td id='tags_<?php echo $incident->get_id()?>'>
                        <?php
                        foreach($incident->get_tags() as $tag_id) 
                        {
                            echo "<div style='color:grey; font-size: 10px; padding: 0 5px 3px 5px;'>" . $incident_tag->get_html_tag($tag_id) . "</div>\n";
                        }
                        ?>
                        </td>
                    </tr>
                    <?php
                    
                    $row++;
                }
				
			}
			else
			{
				?>
				<tr><td colspan='11' class='tl_empty'><?php echo _("No tickets found")?></td></tr>
				<?php
			}
		?>	
			
        </table> 
        <?php
		$db->close();
		?>
    </div>
	
        <!-- Pagination -->
		<table align="center" width="100%" id='table_3'>
			<?php if ($total_incidents > 0) {?>
			<tr>
				<td colspan="11" align="right" class='bborder'>
					<table align="right" style="border:none">
						<tr>
							<td style="padding:3px 2px 3px 2px" class="noborder"><strong><?php echo _("Pag")?>. </strong></td>
					<?php 
						// Pagination variables
						$maxpags = 10;
						$maximo  = ($total_incidents % $rows_per_page == 0) ? ($total_incidents/$rows_per_page) : floor($total_incidents/$rows_per_page)+1;
						if ($page>$maximo) 
							$page=$maximo;
						
						$bloque    = ($page % $maxpags==0) ? ($page/$maxpags) : floor($page / $maxpags)+1;
						$hasta_pag = $maxpags * $bloque;
						$desde_pag = $hasta_pag - $maxpags + 1;
						
						if ($desde_pag<=0) 
							$desde_pag=1;
						
						if ($hasta_pag>$maximo) 
							$hasta_pag=$maximo;

						
						if ($bloque>1) 
							echo "<td class='noborder'><a href='#' onclick=\"$('#page').val('".($desde_pag-1)."');$('#filter').submit()\"><<</a></td>";
						
						for ($i = $desde_pag; $i <= $hasta_pag; $i++) {
							if ($i == $page) echo "<td class='noborder'><b>$i</b></td>";
							else echo "<td class='noborder'><a href='#' onclick=\"$('#page').val('$i');$('#filter').submit()\">$i</a></td>";
						}
						
						if ($hasta_pag<$maximo) echo "<td class='noborder'><a href='#' onclick=\"$('#page').val('".($hasta_pag+1)."');$('#filter').submit()\">>></a></td>";
					?>
						</tr>
					</table>
				</td>
			</tr>
			
			<?php } 
			
			if (Session::menu_perms("analysis-menu", "IncidentsOpen")) 
			{
				?>
				<tr>
					<td colspan="11" align="center" class='noborder'>

						<!-- new incident form -->
						<form id="formnewincident" method="GET">
				   
						<table valign="absmiddle" align="center" class="noborder">
							<tr>
								<td class="noborder" valign="middle" align="center" style="padding-right:15px;">
								   <span><?php echo _("Open a new ticket manually: ")?></span>
								</td>
								<td class="noborder" valign="middle" align="center">
									<select id="selectnewincident">
										<optgroup label="<?=_('Generic')?>">
											 <option value="newincident.php?ref=Alarm&title=<?=urlencode(_("New Alarm incident"))?>&priority=1&src_ips=&src_ports=&dst_ips=&dst_ports="><?=_("Alarm")?></option>
											 <option value="newincident.php?ref=Event&title=<?=urlencode(_("New Event incident"))?>&priority=1&src_ips=&src_ports=&dst_ips=&dst_ports="><?=_("Event")?></option>
											 <option value="newincident.php?ref=Vulnerability&title=<?=urlencode(_("New Vulnerability incident"))?>&priority=1&ip=&port=&nessus_id=&risk=&description="><?=_("Vulnerability")?></option>
										</optgroup>
										<optgroup label="<?=_('Anomalies')?>">
											 <option value="newincident.php?ref=Anomaly&title=<?=urlencode(_("New Mac Anomaly incident"))?>&priority=1&anom_type=mac"><?=_("Mac")?></option>
											 <option value="newincident.php?ref=Anomaly&title=<?=urlencode(_("New OS Anomaly incident"))?>&priority=1&anom_type=os"><?=_("OS")?></option>
											 <option value="newincident.php?ref=Anomaly&title=<?=urlencode(_("New Service Anomaly incident"))?>&priority=1&anom_type=service"><?=_("Services")?></option>
										</optgroup>
										<?php
										if (count($customs)>0) 
										{ 
											?>
											<optgroup label="<?=_('Custom')?>">
											<?php 
											foreach ($customs as $custom) 
											{ 
												?>
												<option value="newincident.php?ref=Custom&title=<?=urlencode(_("New ".$custom." ticket"))?>&type=<?=urlencode($custom)?>&priority=1"><?=$custom?></option>
												<?php
											} 
											?>
											</optgroup> 
											<?php 
										} 
										?>
									</select>
									<input type="button" style="margin-left:5px" class="av_b_secondary small" value="<?php echo _("Create")?>" onclick='javascript: self.location.href=this.form.selectnewincident.options[this.form.selectnewincident.selectedIndex].value;'/>
								</td>
							</tr>
						</table>
						
						</form><!-- end of new incident form -->
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		
		</form>
	<br/>
<div id="dropdown-actions" data-bind="dropdown-actions" class="dropdown dropdown-close dropdown-tip dropdown-anchor-right hidden">
        <ul class="dropdown-menu">
        <li><a href="#" id="act-close">Close</a></li>
        <li><a href="#" id="act-delete">Delete</a></li>
        </ul>
</div>

</body>
</html>

