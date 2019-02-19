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


Session::logcheck("configuration-menu", "PluginGroups");


$db   = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin::get_list($conn, "ORDER BY name");

$plugins = array();
$farray  = array();

foreach ($plugin_list as $p) 
{
	$plugins[$p->get_id()] = array($p->get_name(),$p->get_description());
}

$nump = intval(GET('nump'));

if ($nump == 0) 
{
    $nump = 500;
}

if (GET('action') == 'edit') 
{
    $group_id  = GET('id');
    $delete_id = GET('delete');
    
    ossim_valid($group_id,  OSS_HEX,                  'illegal:ID');
    ossim_valid($delete_id, OSS_DIGIT, OSS_NULLABLE,  'illegal:delete');
    
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    
    if ($delete_id!="") 
    {
         Plugin_group::delete_plugin_id($conn, $group_id, $delete_id);
    }
    
    $where = "plugin_group.group_id=unhex('$group_id')";
    $list  = Plugin_group::get_list($conn, $where);
    
    if (count($list) != 1) 
    {
        die(ossim_error(_("Empty DS Group ID")));
    }
    
    $plug_ed = $list[0];
    $name    = $plug_ed->get_name();
    $descr   = $plug_ed->get_description();
    $plugs   = $plug_ed->get_plugins();
} 
else 
{
    $group_id = $name = $descr = null;
    
    $name  = GET('pname');
    $descr = GET('pdesc');
    
    ossim_valid($name,  OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE,   'illegal:Name');
    ossim_valid($descr, OSS_ALL, OSS_NULLABLE,                          'illegal:Description');
    
    if (ossim_error()) 
    {    
        die(ossim_error());
    }
    
    
    $descr = Util::htmlentities($descr);
    $plugs = array();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	
    <script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
    <script type="text/javascript" src="../js/urlencode.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>	
	
	<script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
	

    <style type="text/css">
    
        img.delete_dsg
        {
            cursor: pointer;
        }
        .bwhite 
        { 
            background: #FFFFFF !important;             
        }
        
        .bgrey 
        { 
            background: #F2F2F2 !important;
        }
        
        .bgreen 
        { 
            background: #28BC04 !important;             
        }
        
        .fwhite 
        { 
            color:white !important;            
        }
        
        .fblack 
        { 
            color:black !important;            
        }
		
				
		.c_back_button
		{
    		position: relative;
            clear: both;
            height: 40px;
            margin-top: 10px;
		}
		
		#c_ds
		{
    		margin: 5px auto 15px auto;
    		width: 100%;
    		position: relative;
		}
		
		#c_ds #tabs
		{
    		width:100%;
    		text-align:center;
    		margin:0 auto;
    		padding:0; 
    		min-height: 120px;
		}
		
		#c_ds_info
		{
    		margin: 20px auto;
    		width: 100%;
    		position: relative;
		}
		
		.input_search
		{
    		color: #333333 !important;
		}
		
		#content tbody tr
		{
    		cursor: pointer;
		}		
		
		#pname, #pdesc
		{
    		width: 95%;
    		padding: 5px 3px;
    		
		}
		
    </style>


	<script type='text/javascript'>
	
		var field_id    = null;
		var table_exist = false;
		
		function getfield(txt) 
		{  
		
    		if (field_id!=null) 
    		{
    		    return $("#"+field_id).val(); 
		    }
		}
		
		function changefield(txt,field_id) 
		{  
			if (field_id==null) 
			{	
				return false;
			}
			
			$("#"+field_id).val(txt); 				

			if(txt.length > 0 && txt != 'ANY')
			{
				text = txt.length;
			}
			else 
			{
				text = '<?php echo _('ANY') ?>';
			}
			
			$("#name"+field_id).text(text); 	
			$('#pluginid').val('');
			$('#redirec').val(1);
			$("#myform").submit();		
		}
		
		//Deprecated! Is not being used
		function validate_sids_str(id)
		{
			var sids_str = $("#sid"+id).val();
			
			$.ajax({
				type: "GET",
				url: "modifyplugingroups.php?interface=ajax&method=validate_sids_str&sids_str="+sids_str+"&pid="+id,
				data: "",
				success: function(msg) {
					if (msg) 
					{
						$("#errorsid"+id).show();
						$("#errorsid"+id).html(msg+'<br/>');
					} 
					else 
					{
						$("#errorsid"+id).hide();
					}
				}
			});
			
			return false;
		}

		function chk() 
		{
			if ( jQuery.trim($('#pname').val()) == "") 
			{
				var pname = '';
					pname = prompt("<?php echo Util::js_entities(_("Please enter a DS Group name:"))?>", "");
			   
				if (pname != '' && pname != null)
				{
					$('#pname').val(pname);
				}
				else
				{
    				return false;
				}
			}
			
			if ($('#pluginid').val()=="0") 
			{
				var autofillp = plist[$('#filter').val()];
				if (typeof autofillp == 'undefined') 
				{
					alert("<?php echo Util::js_entities(_("You must select a DS Group"))?>");
					$('#filter').focus();
					return false;
				} 
				else 
				{
					$('#pluginid').val(autofillp);
					return true;
				}
			}
			
			return true;
		}

		function pluginsid_search() 
		{
			var q = $("#sidsearch").val();
			
			if (q != "" && q.length>3) 
			{
				$("#loading").toggle();
				$("#addall").hide();
				$("#trsearchresults").hide();
				$.ajax({
					type: "GET",
					url: "pluginsidsearch.php",
					data: { q: q },
					success: function(msg) {
						
						if(table_exist)
						{
							table_exist.fnDestroy();
						}
						
						$("#searchresults").html(msg);
						$("#trsearchresults").show();
						$("#loading").toggle();
						$("#addall").show();						

						table_exist = $('.table_data_et').dataTable( {
							"sScrollY": "300px",
							"bPaginate": false,
							"bJQueryUI": true,
							oLanguage : {
								"sProcessing": "<?php echo _('Processing') ?>...",
								"sLengthMenu": "Show _MENU_ entries",
								"sZeroRecords": "<?php echo _('No matching records found') ?>",
								"sEmptyTable": "<?php echo _('No data available in table') ?>",
								"sLoadingRecords": "<?php echo _('Loading') ?>...",
								"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries matching your selection') ?>",
								"sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
								"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
								"sInfoPostFix": "",
								"sInfoThousands": ",",
								"sSearch": "<?php echo _('Search') ?>:",
								"sUrl": "",
								"oPaginate": {
									"sFirst":    "<?php echo _('First') ?>",
									"sPrevious": "<?php echo _('Previous') ?>",
									"sNext":     "<?php echo _('Next') ?>",
									"sLast":     "<?php echo _('Last') ?>"
								}
							}
						});

					}
				});
			} 
			else 
			{
				alert("<?php echo Util::js_entities(_("At least 4 chars"))?>")
			}
		}

		function chkall() 
		{
			if ($('#selunsel').attr('checked'))
			{
				$('input[type=checkbox]').attr('checked',true)
			}
			else
			{
				$('input[type=checkbox]').attr('checked',false)
			}
		}

		$(document).ready(function(){
		    $(".delete_dsg").on("click", function(event){
		        if(confirm('<?php echo  Util::js_entities(_("Are you sure you want to delete this data source?"))?>'))
		        {
    		        location.href='modifyplugingroupsform.php?action=<?php echo Util::htmlentities(GET('action')) ?>&id=<?php echo $group_id ?>&delete=' + $(this).attr("data-id");
		        }
		    });
		   
			GB_TYPE = 'w';
			
			$(".greybox").click(function(){
				form_id = $(this).attr('name');
				$('#f'+form_id).submit();
				return false;
			});
			
			$("a.greyboxe").click(function(){
				field_id = $(this).attr('txt');
				sids     = $('#'+field_id).val();
				document.location.href = this.href+"&pgid=<?php echo $group_id?>&sids="+urlencode(sids);
				return false;
			});
							
			$('#sidsearch').bind('keypress', function(e){
				if ((e.keyCode || e.which) == 13) 
				{
					pluginsid_search();
					return false;
				}
			});
			
			$('.table_data .blank, .table_data .lightgray').disableTextSelect().click(function(event) {
		        field_id = $(this).attr('pid');
		        $("#pluginid").val(field_id);
		        if (chk())
		        {
		            $("#myform").submit();
		        }
		        
		        return false;
		    });
			
			$('#result .blank, #result .lightgray').disableTextSelect().dblclick(function(event) {
				form_id = $(this).attr('txt');
				$('#f'+form_id).submit();
				return false;
			});
			
			$('.tiptip').tipTip();			
			
			$('#tabs').hide();
			$('#tabs').tabs({
				selected: -1,
				collapsible: true,
				create: function( event, ui ) {
    				$('#tabs').show();
				}
            });			
			
			$('.table_data').dataTable( {
				"iDisplayLength": 15,
				"sPaginationType": "full_numbers",
				"bLengthChange": false,
				"bJQueryUI": true,
				"aaSorting": [[ 0, "asc" ]],
				"aoColumns": [
					{ "bSortable": true },
					{ "bSortable": true },
					{ "bSortable": true }
				],
				oLanguage : {
					"sProcessing": "<?php echo _('Processing') ?>...",
					"sLengthMenu": "Show _MENU_ entries",
					"sZeroRecords": "<?php echo _('No matching records found') ?>",
					"sEmptyTable": "<?php echo _('No data available in table') ?>",
					"sLoadingRecords": "<?php echo _('Loading') ?>...",
					"sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
					"sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
					"sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
					"sInfoPostFix": "",
					"sInfoThousands": ",",
					"sSearch": "<?php echo _('Search') ?>:",
					"sUrl": "",
					"oPaginate": {
						"sFirst":    "<?php echo _('First') ?>",
						"sPrevious": "<?php echo _('Previous') ?>",
						"sNext":     "<?php echo _('Next') ?>",
						"sLast":     "<?php echo _('Last') ?>"
					}
				}
			});
			
			if (!parent.is_lightbox_loaded(window.name))
			{    			
    			$('.c_back_button').show();		
    		}		
		});

	</script>
</head>
<body>   
    		
    <div class="c_back_button">         
        <input type='button' class="av_b_back" onclick="document.location.href='plugingroups.php';"/> 
    </div>

	<form id="myform" name="myform" action="modifyplugingroups.php?action=<?php echo Util::htmlentities(GET('action')) ?>&id=<?= $group_id?>" method="POST" onsubmit="return chk()">            
	
	<div id='c_ds'>
                       
        <input type="hidden" id="redirec" name="redirec" value="0"/>
        
        
        <table align="center" width="100%" style="border:none" cellspacing="1" cellspacing="0">
            <tr>
                <th width="50%" class="headerpr"><span><?php echo _("Group Name")?></span></th>
                <th width="50%" class="headerpr"><span><?php echo _("Description")?></span></th>
            </tr>
            
            <tr>
                <td class="noborder w50" style='vertical-align:top'>
                	<input type="text" name="name" id="pname" value="<?php echo $name?>">
                </td>
                <td class="noborder w50">
                	 <textarea name="descr" rows="2" id="pdesc" wrap="on"><?php echo $descr ?></textarea>
                </td>
            </tr>
            
            
            <tr>
                <td class="noborder" colspan='2'>
                    <br>
                    <div style='width:98%;text-align:left;padding:0 0 5px 10px;'><b><?php echo _('Add events to the DS Group') ?></b></div>
                    
                    <div id="tabs">                    		
                		<ul>
                			<li><a href="#tabs-1" class="ptab"><?php echo _("Add by Data Source") . required() ?></a></li>
                			<li><a href="#tabs-2" class="ptab"><?php echo _("Add by Event Type") . required() ?></a></li>
                		</ul>
                		
                		<!-- Tab 1 -->
                		<div id="tabs-1" style='padding:20px 0 30px 0;'>
                            <input type="hidden" id="pluginid" name="pluginid" value="0">
                            <span style='text-align:center;font-weight:bold;font-style:italic;font-size:11px;'><?php echo _('Click on the data source to add to the list') ?></span>
                            <table align="center" width="100%" cellspacing="1" cellspacing="0" class="noborder" id="content" style='margin-top:5px;'>
                                <tr>
                                    <td class='noborder'>
                                        <table class='noborder table_data' width='100%' align="center">
                                            <thead>
                                                <tr>
                                                    <th width="15%"><?php echo _("Data Source") ?></th>
                                                    <th width="22%"><?php echo _("Data Source Name") ?></th>
                                                    <th width="68%"><?php echo _("Data Source Description") ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $plugin_list = Plugin::get_list($conn, "ORDER BY name");
                                                foreach ($plugin_list as $p) 
                                                {
                                                    $bgclass = ($color++ % 2 != 0) ? "blank" : "lightgray";
                                                    ?>
                                                    <tr class="<?=$bgclass?>" pid="<?=$p->get_id()?>" >
                                                        <td width="15%"><b><?= $p->get_id() ?></b></td>
                                                        <td width="22%">
                                                            <?=$p->get_name()?>
                                                        </td>
                                                        <td width="68%">
                                                            <?=$p->get_description()?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </table>						
                        </div>
                		
                		<!-- End Tab 1 -->
                		
                		<!-- Tab 2 -->
                		
                		<div id="tabs-2" style='padding:20px 0 30px 0;'>
                            <span><b><?php echo _('Event Type')?>: </b></span><input type="text" id="sidsearch" name="sidsearch" size="19" value="">&nbsp;&nbsp;<input type="button" value="<?=_("Search")?>" class="small" onclick="pluginsid_search()">
                            <span id="loading" style="display:none"><img src="../pixmaps/theme/loading.gif" border="0"></span>
                            <br><br>
                            <div id="trsearchresults" style="display:none;margin:0 auto;width:100%;">
                                <table class="noborder" align='center' style='width:100%;'>
                                    <tr>
                                        <td class='noborder'>										
                                            <table class='noborder table_data_et' width='100%' align="center">
                                                <thead>
                                                    <tr>
                                                        <th width="20%"><?php echo _("Data Source")?><input type="checkbox" id="selunsel" onclick="chkall();event.stopPropagation();"></th>
                                                        <th width="15%"><?php echo _("DS Name")?></th>
                                                        <th width="15%"><?php echo _("Event Type")?></th>
                                                        <th width="50%"><?php echo _("Event Type Name") ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id='searchresults' width='100%'></tbody>
                                            </table>										
                                        </td>
                                    </tr>
                                </table>
                            </div>						
                            <br>
                            <span id="addall" style="display:none;text-align:center"><input type="submit" onclick="$('#pluginid').val('');$('#redirec').val(1)" value="<?=_("Add Selected")?>" class="small"/></span>						
                            </div>
                        </div>	
                		
                		<!-- End Tab 2 -->
                		                    		      					
                    </div>                 
                </td>
            </tr>     		
        </table>       	
	</div>

	<div id='c_ds_info'>
		<table id='result' align="noborder transparent center" width="100%" style="border:none" cellspacing="1" cellspacing="0">
			<tr>
				<th width="10%" class="headerpr" style='white-space: nowrap;'><?= _("Data Source") ?></th>
				<th width="22%" class="headerpr"><?php echo  _("Data Source Name") ?></th>
				<th width="63%" class="headerpr"><?php echo  _("Data Source Description / Event types") ?></th>
			</tr>
			
			<?php
            $color = 0;
            if(is_array($plugs) && !empty($plugs))
            {
                foreach ($plugs as $id => $pdata) 
                {
                    $sids = $pdata['sids'];
                    
                    if ($sids == "0") 
                    {
                        $sids = "ANY";
                    }
                
                    $bgclass = ($color++ % 2 != 0) ? "blank" : "lightgray";
                    $bbottom = ($pdata==end($plugs)) ? "pbottom" : "";
                ?>
                <tr class="<?=$bgclass?>" txt="sid<?=$id?>">    
                	<td class="noborder pleft <?=$bbottom?>" style='white-space: nowrap;'>
                		<table class="noborder" style="background:transparent">
                			<tr>
                				<td class="nobborder">
                					<?php 
                					if (count($plugs)>1) 
                					{    						
                						?>
                						<img class="delete_dsg tiptip" src="../vulnmeter/images/delete.gif" align="absmiddle" border="0" title="<?php echo _("Delete data source from group");?>" data-id="<?php echo $id;?>"/>
                						<?php 
                					} 
                					else 
                					{    						
                						?>
                						<a href="javascript:;" onclick="alert('<?=_("Add another data source before deleting this")?>');" title="<?=_("Add another data source before deleting this")?>" class="tiptip"><img src="../vulnmeter/images/delete.gif" align="absmiddle" class="disabled" border="0"/></a>
                						<?php
                					} 
                					?>
                				</td>
                				<td class="nobborder"><?= $id ?></td>
                			</tr>
                		</table>
                	</td>	
                	<td class="noborder pleft pright <?=$bbottom?>"><?= $plugins[$id][0] ?></td>
                	<td class="noborder pright <?=$bbottom?>">
                		<?php
                		$farray[$id] = $sids;
                		?>
                		<table class="noborder" style="background:transparent" cellpadding='0' cellspacing='0' width="100%">
                			<tr>
                				<td class="nobborder" style="padding-right:10px">&nbsp;<?= $plugins[$id][1] ?></td>
                				<td class="nobborder right" style="padding-right:10px" NOWRAP>
                					<input id="sid<?=$id?>" type="hidden" name="sids[<?=$id?>]" value="<?=$sids?>">
                					<div id="editsid<?= $id ?>" style='white-space: nowrap;'>
                						<span>
                						<?php
                							echo _($plugins[$id][0] . ' events type selected: ');
                							if($sids == "ANY")
                							{
                								$msg = "ANY";
                							} 
                							else 
                							{
                								$aux   = count(explode(',',$sids));
                								$total = Plugin_sid::get_sidscount_by_id($conn,$id);
                								$msg   = ($aux == $total)? "ANY" : $aux;
                							}
                						
                						?>						
                						</span>	
                						<span id="namesid<?=$id?>" style='padding-right:10px;font-weight:bold'><?php echo $msg ?></span>					
                						<a href="javascript:;" name="sid<?=$id?>" class="greybox tiptip" title="<?=_("Add/Edit event types selection")?>"><img src="../vulnmeter/images/pencil.png" height='18' border=0 align="absmiddle"></a>&nbsp;&nbsp;<a href="allpluginsids.php?id=<?=$id?>" txt="sid<?= $id ?>" class="greyboxe tiptip" title="<?=_("Explore selected event types")?>"><img src="../vulnmeter/images/info.png" align="absmiddle" height='18' border="0"></a>
                					</div>
                				</td>
                			</tr>
                		</table>
                	</td>
                </tr>             
                <?php 
                }				
            } 
            else 
            {
                echo "<tr><td class='nobborder center' colspan='3' style='padding: 10px 0px;'>". _('No Data Source added yet') ."</td></tr>";
            }
            ?>
            
            <tr>
                <td colspan='3' style='text-align:center; padding:10px;'>               
                    <input type='button' onclick="$('#pluginid').val(''); $('#myform').submit();" value="<?php echo _("Update")?>"/>		
                </td>
        	</tr>
                  	
    	</table>
	</div>
	
	<br>
	</form>
	<?php

	foreach ($farray as $key => $value) 
	{
    	?>
		<form action="pluginsids.php" method="POST" id="fsid<?=$key?>"/>
			<input type="hidden" name="field" value="<?php echo $value ?>"/>
			<input type="hidden" name="id" value="<?php echo $key ?>"/>
			<input type="hidden" name="name" value="<?php echo $name ?>"/>
			<input type="hidden" name="pgid" value="<?php echo $group_id ?>"/>
		</form>
		<?php
	}
	?>

	</body>
</html>
<?php
$db->close();
?>
