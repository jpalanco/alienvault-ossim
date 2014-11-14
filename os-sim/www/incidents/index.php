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

$autocomplete_keys   = array('users', 'entities');
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
	<script type="text/javascript" src="../js/jquery.min.js"></script> 
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>    
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>	

        
	<script type="text/javascript">
		        
        function checkall()
        {
            if ($('input[name="ticket0"]').attr('checked'))
            { 
                $('input[type="checkbox"]').attr('checked',true);
            }
            else
            {
                $('input[type="checkbox"]').attr('checked',false);
            }
        }
                
        function get_chk_selected()
        {
            var size = $("input[type='checkbox']:checked").length;
            
            if (size > 0)
            {
                var selected = new Array();            
                $("input[type='checkbox']:checked").each(function (index) {
                    
                    var data = $(this).val().split("_");
                    var id   = parseInt(data[0]);
                    
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
                
            var msg_action = "";
            
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
                                      
                    var status    = html.split("###");
                                       
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
                                    $('#tags_'+selected[i]).append(html_tag);
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
                            var content = "<?php echo _('You do not have permission to realize this action')?>";
                                  
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
            
            $('.tiptip').tipTip();
            
            $('#link_tags,#link_tbox').bind('click', function()  {$('#tag_list').toggle(); });
            
            $('.td_tags').bind('click', function() {   
                var tag = $(this).attr("id").replace("tag_", "");
                execute_action('apply_tags', tag); 
            });
            
            $('#link_rm_tags').bind('click', function()  {
                execute_action('remove_tags', '');
            }); 

            //Autocomplete    
            var users_and_entities = [ <?php echo $users_and_entities; ?> ];
								        
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
        
        #cont_tags
        {
            text-align: right;
            height: 20px;
            padding-top: 5px;
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
        
        #cont_tags #header_tags
        {
            float: right;
            width: 98%;
            margin: 3px 5px 1px 0px;
        }
        
        #cont_tags
        {
            font-weight:bold;
        }
        
        #link_tags
        {
            font-weight:bold;
            font-size:11px;
            text-transform:uppercase;
        }
        
        #cont_tags #tag_list
        {
            clear:both;
            float: right;
            width: 40%;
            margin: 3px 2px 5px 0px;
            position: relative;
            display: none;
            height: 1px;
        }
        
        #tag_box
        {
            position:absolute;
            right:0px;
            top:0px;
            text-align: right;            
        }
        
        #tag_box #theader_tag_box
        {          
            background: white;
        }    
        
        #theader_tag_box th
        {
            padding-right:3px;
            border-top:0px;
            border-right:0px;
            border-left:0px;
            background: #D3D3D3;
            color: #555555;
            font-weight: bold;
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
        
	</style>
	</head>
<body>
<?php 

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version");
$vars = array(
    'order_by'		  => OSS_LETTER . OSS_SCORE,
    'order_mode'	  => OSS_LETTER,
    'ref' 			  => OSS_LETTER,
    'type' 			  => OSS_ALPHA . OSS_SPACE . OSS_SCORE ,
    'title' 		  => OSS_ALPHA . OSS_SCORE . OSS_PUNC,
    'related_to_user' => OSS_LETTER,
    'with_text'       => OSS_ALPHA . OSS_PUNC . OSS_SCORE . OSS_SPACE,
    'action'          => OSS_ALPHA . OSS_PUNC . OSS_SCORE . OSS_SPACE,
    'attachment'      => OSS_ALPHA . OSS_SPACE . OSS_PUNC,
    'advanced_search' => OSS_DIGIT,
    'priority' 	      => OSS_LETTER,
    'submitter' 	  => OSS_ALPHA . OSS_SCORE . OSS_PUNC . OSS_SPACE,
    'text_in_charge'  => OSS_ALPHA . OSS_SCORE . OSS_PUNC . OSS_SPACE . OSS_BRACKET,
    'in_charge' 	  => OSS_ALPHA . OSS_SCORE . OSS_PUNC . OSS_SPACE,
    'status' 		  => OSS_LETTER . OSS_SCORE,
    'tag' 			  => OSS_DIGIT,
    'page' 			  => OSS_DIGIT,
    'close' 		  => OSS_ALPHA . OSS_SPACE
);

foreach($vars as $var => $validate) 
{
    $$var = GET("$var");
    if (!ossim_valid($$var, array($validate, OSS_NULLABLE))) 
    {
        die(ossim_error());
    }
}

if (empty($in_charge) && empty($text_in_charge)){
    $in_charge = null;
    $text_in_charge = null;
}


if (!$order_by) {
    $order_by = 'life_time';
    $order_mode = 'DESC';
}

if ($page=="" || $page<=0) 
    $page=1;
    
// First time we visit this page, show by default only Open incidents
// when GET() returns NULL, means that the param is not set
if (GET('status') === null) 
    $status = 'Open';


// Close selected tickets
if (GET('close') == _("Close selected")) 
{
    foreach ($_GET as $k => $v) 
    {
        if (preg_match("/^ticket\d+/",$k) && $v != "") 
        {
            $idprio = explode("_",$v);
            if (is_numeric($idprio[0]) && is_numeric($idprio[1]) && Incident::user_incident_perms($conn, $idprio[0], 'closed'))
            {
                Incident_ticket::insert($conn, $idprio[0], "Closed", $idprio[1], Session::get_session_user(), " ", "", "", array(), null);
            }
        }
    }
}

$criteria = array(
    'ref'             => $ref,
    'type'            => $type,
    'title'           => $title,
    'submitter'       => $submitter,
    'in_charge'       => $in_charge,
    'with_text'       => $with_text,
    'status'          => $status,
    'priority_str'    => $priority,
    'attach_name'     => $attachment,
    'related_to_user' => $related_to_user,
    'tag'             => $tag
);

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
    			    <img src="../pixmaps/pie_chart.png" border="0" align="absmiddle"/>
    			</a>
			</th>
		</tr>
		<tr>
			<td class="noborder alp"> <?php echo gettext("Class"); /* ref */ ?> </td>
			<td class="noborder alp"> <?php echo gettext("Type"); /* type */ ?> </td>
			<td class="noborder alp"> <?php echo gettext("Search text"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("In charge"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("Status"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("Priority"); ?> </td>
			<td class="noborder alp"> <?php echo gettext("Actions"); ?> </td>
		</tr>
		<tr>
			<td class="alp" style="border-width: 0px;">
				<select name="ref" onChange="document.forms['filter'].submit()">
				<?php
					$ref_types = array (
						""     		    => _("ALL"),
						"Alarm"   		=> _("Alarm"),
						"Event"   		=> _("Event"),
						"Metric"  		=> _("Metric"),
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
							if (preg_match("/custom/",$itype->get_keywords())) {
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
			
			<td class="alp" nowrap='nowrap' style="border-width: 0px;">
			    <input type="submit" class="av_b_secondary small" name="close" value="<?php echo _("Close selected")?>"/>
				<input type="submit" class="small" name="filter" value="<?php echo _("Search")?>"/>
				
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
		?>
	</table>
	
	<!-- end filter -->
	
    <?php
    $rows_per_page   = 50;
    $incident_list   = Incident::search($conn, $criteria, $order_by, $order_mode, $page, $rows_per_page);
    $total_incidents = Incident::search_count($conn);
    
    if (count($incident_list)>=$total_incidents) 
    {
        $total_incidents = count($incident_list);
        if ($total_incidents > 0)
        {
            $rows_per_page = $total_incidents;
        }
    }
            
    
    $filter = '';
        
	foreach($criteria as $key => $value){
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
        
	<div id='cont_tags'>
		<div id='middle_ct'></div>
		<div id='left_ct'></div>
		<div id='right_ct'>
			<div id='header_tags'><a id='link_tags'><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/><?php echo _("Apply tags to selected tickets")?></a></div>
			<div id='tag_list'><?php echo show_tag_box($tag_list);?></div>
		</div>
	</div>
        
    <div style='clear:both;'>
		<table class='table_list'>
			<tr>
				<th><input type="checkbox" name="ticket0" onclick="checkall()"/></th>
				<th nowrap='nowrap'><a href="?order_by=id<?php echo $filter?>"><?php echo _("Ticket") . order_img('id') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=title<?php echo $filter ?>"><?php echo _("Title") . order_img('title') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=priority<?php echo $filter ?>"><?php echo _("Priority") . order_img('priority') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=date<?php echo $filter ?>"><?php echo _("Created") . order_img('date') ?></a></th>
				<th nowrap='nowrap'><a href="?order_by=life_time<?php echo $filter ?>"><?php echo _("Life Time") . order_img('life_time') ?></a></th>
				<th><?php echo _("In charge") ?></th>
				<th><?php echo _("Submitter") ?></th>
				<th><?php echo _("Type") ?> <a href="incidenttype.php" style="font-weight:normal"><img align="absmiddle" src="../vulnmeter/images/pencil.png" border="0" title="Edit Types"></a></th>
				<th><?php echo _("Status") ?></th>
				<th><?php echo _("Extra") ?> <a href="incidenttag.php" style="font-weight:normal"><img align="absmiddle" src="../vulnmeter/images/pencil.png" border="0" title="Edit Tags"></a></th>
			</tr>
			
			<?php
			$row = 1;
			
			if ($total_incidents > 0)
			{
				foreach($incident_list as $incident)
                {                    
                    ?>

                    <tr valign="middle" class="ticket_tr">
                        <td>
                            <input type="checkbox" name="ticket<?php echo $row ?>" value="<?php echo $incident->get_id()."_".$incident->get_priority()?>"/>
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
											 <option value="newincident.php?ref=Metric&title=<?=urlencode(_("New Metric incident"))?>&priority=1&target=&metric_type=&metric_value=0"><?=_("Metric")?></option>
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
</body>
</html>

