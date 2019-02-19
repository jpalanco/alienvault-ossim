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
Session::logcheck('environment-menu', 'PolicyNetworks');


$db   = new ossim_db();
$conn = $db->connect();

$id   = GET('id');
$msg  = GET('msg');


ossim_valid($id, OSS_ALPHA, OSS_NULLABLE,'illegal:' . _('Network Group ID'));

if (ossim_error()) 
{
    die(ossim_error());
}

$networks  = array();

$descr       = '';

if ($id != '')
{
	if ($net_group_list = Net_group::get_list($conn, " g.id = UNHEX('$id')")) 
	{
		$net_group = $net_group_list[0];

		$ngname       = $net_group->get_name();
		$ctx          = $net_group->get_ctx();
		$descr        = $net_group->get_descr();
		$obj_networks = Net_group::get_networks($conn, $net_group->get_id());
			
		foreach($obj_networks as $net) 
		{
			$net_id = $net->get_net_id();
			
			$filters = array(
			     'where' => "id = UNHEX('".$net_id."')"
			);
												
			$_aux_net_list = Asset_net::get_list($conn, '', $filters);			
			
			$networks[$net->get_net_id()] = $_aux_net_list[0][$net_id];
		}
																			
		$rrd_profile = $net_group->get_rrd_profile();
		
		if (!$rrd_profile)
		{ 
			$rrd_profile = 'None';
		}															
	}
} 

if (GET('id') != '' || GET('clone') == 1)
{
	$action = 'modifynetgroup.php';
}
else
{
	$action = 'newnetgroup.php';
}

$paths = Asset::get_path_url(FALSE);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _('OSSIM Framework'); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <script type="text/javascript" src="../js/combos.js"></script>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
    
    <link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    
    <script type="text/javascript">
               
        var layer = null;
		var nodetree = null;
		var i=1;
		var addnodes = false;
	
		function load_tree(filter, entity) 
		{			
			combo = 'nets';
			
			if (nodetree!=null) 
			{
				nodetree.removeChildren();
				$(layer).remove();
			}
			
			layer = '#srctree'+i;
			
			if (entity != "") 
			{
				$('#container').html("");
				$('#container').append('<div id="srctree'+i+'" style="width:100%"></div>');
				$('.filterdiv').show();
				$(layer).dynatree({
					initAjax: { url: "../tree.php?key=e_"+entity+"_net", data: {filter: filter} },
					clickFolderMode: 2,
					onActivate: function(dtnode) {
						if (dtnode.data.key.match(/net_/)) 
						{
							var k = dtnode.data.key.replace("net_", "");
							
							addto(combo,dtnode.data.val,k);
						}
					},
					onDeactivate: function(dtnode) {;},
					onLazyRead: function(dtnode){
						// load nodes on-demand
						dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page},
							success: function(options,selfnode) {
								if (addnodes) 
								{
									addnodes = false;
									
									var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
									
									for (c=0;c<children.length; c++)
									{
										addto(combo,children[c].data.url,children[c].data.url);
									}
								}
							}
						});
					}
				});
				nodetree = $(layer).dynatree("getRoot");
				i = i + 1;
			} 
			else 
			{
				$('#container').html("<br/><i><?php echo _("Select first a context to add networks") ?></i>");
			}
		}

		function GB_notes(id) 
		{
			url = '../av_asset/common/views/notes.php?asset_type=net_group&asset_id=' + id;
			GB_show("<?php echo _("Asset Notes")?>", url, "460", "700");
			return false;
		}
		
		function check_num_assets() 
		{
			$('#num_assets_check').val('');
			var myselect=document.getElementById("nets");
			
			for (var i=0; i<myselect.options.length; i++) 
			{
				if (myselect.options[i].selected == true) 
				{
					$('#num_assets_check').val('1');
				}
			}
		}	

		function toggle_section(id)
		{			
			var section = '.'+id.replace('_arrow', '');
				
			$(section).toggle();
			
			if($(section).is(':visible'))
			{ 
				$('#'+id).attr('src','../pixmaps/arrow_green_down.gif'); 
			}
			else
			{ 
				$('#'+id).attr('src','../pixmaps/arrow_green.gif');
			}
		}		
		
        $(document).ready(function(){
            Token.add_to_forms();
			
			$('.section').click(function() { 
				var id = $(this).find('img').attr('id');
				toggle_section(id);
			});
			
			$('#apply').click(function() { 
				load_tree($('#filter').val(), $('#ctx').val())
			});
			
						
			// Entities tree
			<?php 			
			if (Session::show_entities()) 
			{ 
				?>
				$("#tree").dynatree({
					initAjax: { url: "../tree.php?key=contexts" },
					clickFolderMode: 2,
					onActivate: function(dtnode) {
						var key = dtnode.data.key;
						if (key.match(/^e_/)) 
						{
							var id_key = key.replace("e_", "");
							$('#ctx').val(id_key);
							$('#entity_selected').html("<?php echo _("Context selected") ?>: <br> <div style='text-align:center'> <b>"+dtnode.data.val+"</b> </div>");
							load_tree("", id_key);
							deleteall('nets');
						}
					},
					onDeactivate: function(dtnode) {}
				});
				<?php 
			} 
			else 
			{ 
				?>
				$("#tree").html("<div style='width:180px; height: 25px; text-align:center;'><?php echo _("No entities found")?></div>");
				<?php 
			}		
			?>

			// Networks tree
			load_tree("", '<?php echo ($ctx != "") ? $ctx : ((Session::show_entities()) ? "" : Session::get_default_ctx()) ?>');
			
            $('textarea').elastic();
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'ng_form',
					url : '<?php echo $action?>'
				},
				actions: {
					on_submit:{
						id: 'send',
						success: '<?php echo _("OK")?>',
						checking: '<?php echo _("Updating")?>'
					}
				}
			};
		
			ajax_validator = new Ajax_validator(config);
		
			$('#send').click(function() { 
				selectall('nets');
				check_num_assets();
				ajax_validator.submit_form();
			});   

			//Greybox options			
			
			if (!parent.is_lightbox_loaded(window.name))
			{	
    			$('.c_back_button').show();
    		}
    		else
    		{
        		$('#ng_container').css('margin', '10px auto 20px auto');
    		}         
        });

    </script>
	
	<style type='text/css'>
		a 
		{
		    cursor: pointer;
		}
		
		input[type='text'], input[type='hidden'], select 
		{
            width: 98%; 
            height: 18px;
		}
		
		textarea 
		{
    		width: 97%; 
    		height: 45px !important;
		}  
					
		#ng_container 
		{		  
		    width: 780px;
		    margin: 40px auto 20px auto;
		    padding-bottom: 10px;
		}
			
		#ng_container #table_form
		{ 
            margin: auto;
            width: 100%;
            background: none;
            border: none; 
		}
		
		#table_form th 
		{
		    width: 200px;
		    white-space: nowrap;
		    padding: 3px 20px; 		
		}
		
		#t_c_tree
		{
    		width: 98%;
    		vertical-align: top;
		}
		
		#t_c_tree .td_tree 
		{
    		min-width: 185px;
    		padding: 3px 10px 3px 0px;
    		vertical-align: top;
		}
		
		#t_tree
		{
    		background: transparent;
    		text-align: left;
    		border: none;
    		vertical-align: top;    		
		}
		
		#tree
		{
    		width: 100%;
		}
		
		#entity_selected
		{
    		width: 100%;
		}
		
		#ctx_txt
		{
    		padding-left: 5px; 
    		font-weight: bold;
		}	
		
		.legend 
		{ 
    		font-style: italic;
    		text-align: center; 
    		padding: 0px 0px 5px 0px;
    		margin: auto;
    		width: 400px;
		}
		      		
		.text_ngname
		{
			cursor: default !important;
			font-style: italic !important;
			opacity: 0.5 !important;
		}       
		        
		#del_selected 
		{
            width: 98%;
            padding-top: 5px; 
            text-align: right;
        }
        
        #del_selected input
        {
            margin-right: 0px !important;
        }
		
        .section
        {
        	    text-transform: uppercase;
        }
        
        .filterdiv
        {
            min-width: 350px;             
        }
        
        .filterdiv div
        {
            padding: 5px 0px 2px 0px;                
        }
        
		#filter
		{ 
			float: left;
			width: 250px;
		}
        
		#apply
		{
			margin-left: 5px; 
			float: left;
		}
		
		#nets
		{ 
    		width:98%; 
    		min-height: 87px;    		
		}
		
		#av_info 
		{
            width: 600px; 
            margin: 10px auto;
		}       
		
	</style>
</head>

<body>
	
	<div class="c_back_button">         
        <input type='button' class="av_b_back" onclick="document.location.href='netgroup.php';return false;"/> 
    </div> 
	
	<div id='av_info'>
		<?php
		if ($msg == 'saved') 
		{
			$config_nt = array(
				'content' => _('Network Group saved successfully'),
				'options' => array (
					'type'          => 'nf_success',
					'cancel_button' => true
				),
				'style'   => 'width: 100%; margin: auto; text-align:center;'
			); 
							
			$nt = new Notification('nt_1', $config_nt);
			$nt->show();
		}
		?>
	</div>	
	
    <div id='ng_container'>
    
        <div class='legend'>
             <?php echo _('Values marked with (*) are mandatory');?>
        </div>

    	<form name='ng_form' id='ng_form' method="POST" action="<?php echo $action;?>">
        	
    		<input type="hidden" name="id" id="id" class='vfield' value="<?php echo $id ?>"/>
    		<input type="hidden" name="insert" value="insert"/>
    		<input type="hidden" name="rrd_profile" value=""/>
    		
    		<?php $ctx = (Session::show_entities()) ? $ctx : Session::get_default_ctx();?>
			<input type="hidden" name="ctx" id="ctx" value="<?php echo $ctx ?>" class="vfield"/> 
    		
    		<table align="center" id='table_form'>
				<tr>
					<th><label for='ngname'><?php echo _('Name') . required();?></label></th>
					<td class="left"><input type='text' name='ngname' id='ngname' class='vfield' value="<?php echo $ngname?>"/></td>
				</tr>
				
				<tr>
					<th rowspan="2"> 
						<?php
                        $n_url = $paths['network']['views'] . 'net_form.php';
						$n_url = Menu::get_menu_url($n_url, 'environment', 'assets', 'networks');
						
						?>
						<label for='nets'><?php echo _('Networks of this group') . required();?></label><br/>
						<span><a href="<?php echo $n_url?>"><?php echo _('Insert new network');?>?</a></span>
					</th>
					<td>
					    <table id='t_c_tree' class="left transparent">
                        	<tr>
                                <?php 
                                if (Session::show_entities()) 
                                { 
                                    ?>  
                        			<td class='td_tree'> 											  											
                        				<table id="t_tree">
                        					<tr>
                        						<td class="left noborder">
                        							<div id="entity_selected">
                        							     <span><?php echo _('Context selected').":"?></span>
                        							     <span id='ctx_txt'>
                        							          <?php echo (empty($ctx) ? _('None') : Session::get_entity_name($conn, $ctx))?>
                        							     </span>
                        							</div>													
                        						</td>
                        					</tr>
                        					<tr>
                        					   <td class="left noborder">
                        					       <div id="tree"></div>
                        					   </td>
                        					</tr>
                        				</table>     											  											
                        			</td>
                        			<?php 
                        		}	 
                        		?>   										
                        		
                        		<td class="left noborder" valign="top">
                        			<table class="transparent">
                        				<tr><td class="nobborder"><?php echo _('Select <strong>networks</strong> below') ?>:</td></tr>
                        				<tr>
                        					<td class="nobborder">
                        						<div id="container" style="clear: both;"></div>
                        						<div class="filterdiv" style="display:none">
                        							<div><?php echo _('Filter')?>:</div>
                        							<input type="text" id="filter" name="filter"/>
                        							<input type="button" id="apply" value="<?php echo _('Apply')?>" class="small av_b_secondary"/>
                        						</div>								   
                        					</td>
                        				</tr> 
                        			</table>
                        		</td>
                        	</tr>
                        </table>
					</td> 
				</tr>
				
				<tr>				
					<td class="left nobborder">
						<input type="hidden" name="num_assets_check" id="num_assets_check" value=""/>
						<select name="nets[]" id="nets" class="vfield" size="7" multiple="multiple">
						<?php
						/* ===== Networks ==== */
						
							foreach($networks as $net) 
							{
								$net_id   = $net['id'];
								$net_name = $net['name'];
								$net_ips  = $net['ips'];
																
								echo "<option value='$net_id'>$net_name ($net_ips)</option>";
							}
						?>
						</select>
						<div id='del_selected'><input type="button" value=" [X] " onclick="deletefrom('nets')" class="small av_b_secondary"/></div>
					</td>
				</tr>
				
				<tr>
					<th><label for='descr'><?php echo _('Description');?></label><br/>
					<td class="left"><textarea name="descr" id='descr' class='vfield'><?php echo $descr;?></textarea></td>
				</tr>			

				<tr>
					<td colspan="2" align="center" style="padding: 10px;" class='noborder'>
						<input type="button" id='send' name='send' value="<?php echo _('SAVE')?>"/>    								
					</td>
				</tr>
				
    		</table>   		
    		
    	</form>
    </div>	
	</body>
</html>

<?php $db->close(); ?>
