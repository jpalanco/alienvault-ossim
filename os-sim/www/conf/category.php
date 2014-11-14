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

Session::logcheck('configuration-menu', 'ConfigurationPlugins');



$db   = new ossim_db();
$conn = $db->connect();

// Actions
$action = POST('action');

if(empty($action))
{
	$action = GET('action');
}

ossim_valid($action, 'add_subcategory','add_category','delete_subcategory','delete_category', 'expand', 'rename_category', 'rename_subcategory', OSS_NULLABLE, 'illegal:' . _("Action"));

if (ossim_error()) 
{
	$data['status'] = 'error';
	$data['data']   = _('Action not allowed');
}
else
{
	if($action == 'add_subcategory')
	{
		$cat_id  = POST('cat_id');
		$sc_name = (POST('sc_name') != '') ? str_replace(' ', '_', POST('sc_name')) : POST('sc_name');
		
		ossim_valid($sc_name, OSS_SCORE, OSS_ALPHA, 'illegal:' . _('Subcategory Name'));
		ossim_valid($cat_id, OSS_DIGIT,             'illegal:' . _('Category'));
		
		$data['status'] = 'error';
		$data['data']   = _('Error! Subcategory not added');
		
		if (!ossim_error()) 
		{
			if (!Subcategory::exist($conn, $cat_id, $sc_name))
			{
				if(Subcategory::insert($conn, $cat_id, $sc_name))
				{
					$data['status'] = 'OK';
					$data['data']   = _('Subcategory added successfully');
				}
			}
			else
			{
				$data['data'] = _('Error! This subcategory already exists');
			}
		}
		else
		{
			$data['data'] = ossim_get_error_clean();
		}
	}
	elseif($action == 'add_category')
	{
		$c_name = (POST('c_name') != '') ? str_replace(' ', '_', POST('c_name')) : POST('c_name');
				
		ossim_valid($c_name, OSS_SCORE, OSS_ALPHA, 'illegal:' . _('Category Name'));
		
		$data['status'] = 'error';
		$data['data']   = _('Error! Category not added');
						
		if (!ossim_error()) 
		{			
			if (!Category::exist($conn, $c_name))
			{
				if(Category::insert($conn, $c_name)){
					$data['status'] = 'OK';
					$data['data']   = _('Category added successfully');
				}
			}
			else
			{
				$data['data']   = _('Error! This category already exists');
			}
		}
		else
		{
			$data['data'] = ossim_get_error_clean();
		}
	}
	elseif($action == 'delete_category')
	{
		$cat_id = GET('cat_id');
		
		ossim_valid($cat_id, OSS_DIGIT, 'illegal:' . _('Category'));
		
		
		$data['status'] = 'error';
		$data['data']   = _('Error! Category not deleted');
		
		if (!ossim_error()) 
		{
			if(Category::delete($conn, $cat_id)){
				$data['status'] = 'OK';
				$data['data']   = _('Category deleted successfully');
			}
		}
		else
		{
			$data['data'] = ossim_get_error_clean();
		}
	}
	elseif($action == 'delete_subcategory')
	{
		$subcat_id = GET('subcat_id');
		$cat_id    = GET('cat_id');
		
		ossim_valid($cat_id, OSS_DIGIT,      'illegal:' . _('Category'));
		ossim_valid($subcat_id, OSS_DIGIT,   'illegal:' . _('Subcategory'));
		
		
		$data['status'] = 'error';
		$data['data']   = _('Error! Subcategory not deleted');
		
		if (!ossim_error()) 
		{
			if(Subcategory::delete($conn, $cat_id, $subcat_id))
			{
				$data['status'] = 'OK';
				$data['data']   = _('Subcategory deleted successfully');
			}
		}
		else
		{
			$data['data'] = ossim_get_error_clean();
		}
	}
	elseif($action == 'expand')
	{
		$cat_id = POST('cat_id');
		ossim_valid($cat_id, OSS_DIGIT, 'illegal:' . _('Category'));
		
		if (ossim_error()) 
		{	
			$data['status'] = 'error';
			$data['data']   = _('Error! Category ID not allowed');
		}
	}
	elseif($action == 'rename_category')
	{
		$cat_id = POST('cat_id');
		$c_name = POST('c_name');
		
		ossim_valid($c_name, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Category Name'));
		ossim_valid($cat_id, OSS_DIGIT,                          'illegal:' . _('Category'));
			
		$data['status'] = 'error';
		$data['data']   = _('Error! Category not renamed');
		
		if (!ossim_error()) 
		{
			if (!Category::exist($conn, $c_name))
			{
				if(Category::edit($conn, $cat_id, $c_name))
				{
					$data['status'] = 'OK';
					$data['data']   = _('Category renamed successfully');
				}
			}
			else
			{
				$data['data']   = _('Error! This category already exists');
			}
		}
		else
		{
			$data['data'] = ossim_get_error_clean();
		}
	}
	elseif($action == 'rename_subcategory')
	{
		$cat_id      = POST('cat_id');
		$subcat_id   = POST('subcat_id');
		$sc_name     = POST('sc_name');
		
		ossim_valid($sc_name, OSS_SCORE, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Subcategory Name'));
		ossim_valid($cat_id, OSS_DIGIT,                           'illegal:' . _('Category'));
		ossim_valid($subcat_id, OSS_DIGIT,                        'illegal:' . _('Subcategory'));
		
		$data['status'] = 'error';
		$data['data']   = _('Error! Subcategory not renamed');
		
		if (!ossim_error()) 
		{
			if (!Subcategory::exist($conn, $cat_id, $sc_name))
			{
				if(Subcategory::edit($conn, $subcat_id, $sc_name)){
					$data['status'] = 'OK';
					$data['data']   = _('Subcategory renamed successfully');
				}
			}
			else
			{
				$data['data']   = _('Error! This subcategory already exists');
			}
		}
		else
		{
			$data['data'] = ossim_get_error_clean();
		}
	}
	
	ossim_clean_error();
}


if ($action != '' && $action != 'expand') 
{
	Category::clone_data($conn);
	Subcategory::clone_data($conn);
}

$list_categories = Category::get_list($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo _('Priority and Reliability configuration');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv=="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>

	<script type="text/javascript">
		
		function show_datasources(category_name, cat_id, subcat_id)
		{
			
			var title = '<?php echo _("Data Sources")." -> " ?> ' + category_name;
			var url   = 'plugin.php?&category_id=' + cat_id + '&subcategory_id=' + subcat_id;
			
			GB_show(title, url, '500px', '85%');
			return false;
		}
		
		
		function toggle_tr(id)
		{
			var img_id = '#img_'+id;
			var id     = '.'+id;
									
			if ($(id).css('display') == 'none')
			{
				$(id).show();
				$(img_id).attr('src', '../pixmaps/minus-small.png');
				$(img_id).attr('title', '<?php echo _("Hide details");?>');
			}
			else
			{
				$(id).hide();
				$(img_id).attr('src', '../pixmaps/plus-small.png');
				$(img_id).attr('title', '<?php echo _("Show details");?>');
			}
		}
				
				
		function cleanEditInput()
		{
			jQuery.each($('input[name="idAjax"]'), function(index, value2) {
				$('#'+value2.value).html('');
			});
		}
		
	
		function edit(type, cat_id, subcat_id, name)
		{
			
			var p_id = (type == 'category') ? 'category' : 'subcategory';
			var id   = (type == 'category') ? cat_id : subcat_id;
			
			if($('input[name="idAjax"]',$('#'+p_id+'_ajax_'+id)).html() == null)
			{
				cleanEditInput();
				
				if(type == 'category')
				{
					action    = 'rename_category';
					nameInput = 'c_name';
				}
				else if(type == 'subcategory')
				{
					action    = 'rename_subcategory';
					nameInput = 'sc_name';
				}
				else
				{
					alert('<?php echo Util::js_entities(_("File not editable"))?>');
					return false;
				}
				
				var content = '<form action="category.php" method="POST">' +
								'<input type="hidden" name="idAjax" value="'+p_id+'_ajax_'+id+'"/>' +
								'<input type="hidden" name="action" value="'+action+'" />' +
								'<input type="hidden" name="cat_id" value="'+cat_id+'" />' +
								'<input type="hidden" name="subcat_id" value="'+subcat_id+'" />' +
								'<input type="text" name="'+nameInput+'" value="'+name+'" />' +
								'<input type="submit" value="<?php echo _('Rename'); ?>" class="small"/>' +								
							  '</form>';
				
				$('#'+p_id+'_ajax_'+id).html(content);
			}
			else
			{
				cleanEditInput();
			}

			return true;
		}
	
	
		function confirmDelete(type, cat_id, subcat_id)
		{					
			var item = (type == 'category') ? 'category' : 'subcategory';
			
			var ans = confirm('<?php echo  Util::js_entities(_('Are you sure to delete this'));?> '+item+'?');
			
			if (ans)
			{
				if(type == 'category')
				{
					var url = 'category.php?action=delete_category&cat_id='+cat_id;
				}
				else if(type == 'subcategory')
				{
					var url = 'category.php?action=delete_subcategory&cat_id='+cat_id+'&subcat_id='+subcat_id;
				}
				else
				{
					alert('<?php echo Util::js_entities(_("Error, select a category or subcategory"))?>');
					return false;
				}
				
				
				if(typeof(url) != 'undefined' && url != null)
				{
					document.location.href = url;
				}
			}
		}
		
		$(document).ready(function(){
			
			<?php	
			if(($action == 'add_subcategory' || $action == 'delete_subcategory') && empty($data))
			{ 
				?>
				toggle_tr('family_<?php echo $cat_id;?>');
				<?php	
			} 
			?>
						
			//setTimeout("$('#nt_1').fadeOut()", 5000);
		});

	</script>
</head>
<body>

	<div id='c_av_info'>
		<div id='av_info'>
		<?php
		if (is_array($data) && !empty($data))
		{				
			
			if ($data['status'] == 'error')
			{
				$nf_type = 'nf_error';
				$msg     = $data['data'];
			}
			else
			{
				$msg     = $data['data'];
				$nf_type = 'nf_success';
			}	
			
			$config_nt = array(
				'content' => $msg,
				'options' => array (
					'type'          => $nf_type,
					'cancel_button' => FALSE
				),
				'style'   => 'width: 90%; margin: auto; text-align: left;'
			); 
							
			$nt = new Notification('nt_1', $config_nt);
			
			$nt->show();
		}
		?>
		</div>
	</div>
	
	<table id="main_table">
		<tr>
			<td class="sec_title"><?php echo _('Taxonomy')?></td>
		</tr>
		
		<tr>
			<td class="headerpr title_padding"><?php echo _('Categories')?></td>
		</tr>
		<?php
		$i = 1;

		foreach ($list_categories as $category) 
		{
			$i++;
			$color = ($i % 2 == 0) ? " class='odd'" : " class='even'";
			
			$cat_id = $category->get_id();
			$c_name = $category->get_name();
			
			?>
			<tr <?php echo $color;?>>
				<td style='text-align: left; font-size: 12px; font-weight: bold;' valign='middle'>
					<div style="float:left">
						<a href="javascript:void(0);" class='link_icon' onclick="toggle_tr('family_<?php echo $cat_id;?>');">
							<img id='img_family_<?php echo $cat_id;?>' title='<?php echo _("Show details");?>' src='../pixmaps/plus-small.png' align='absmiddle'/>
						</a>
						
						<span style='margin-left:8px;'>
							<a href="javascript:void(0);" class='link_icon' onclick="edit('category', '<?php echo $cat_id."', '', '".$c_name."'";?>)" title="<?php echo _("Edit")?>">
								<img border="0" align="absmiddle" src="../vulnmeter/images/pencil.png"/>
							</a>
							<?php 
							if(!$category->get_inUse())
							{
								?>
								<a href="javascript:void(0);" class='link_icon' onclick="confirmDelete('category', '<?php echo $cat_id;?>', '')" title="<?php echo _("Delete")?>">
									<img border="0" align="absmiddle" style='width:12px; height: 12px;' src="../vulnmeter/images/delete.gif"/>
								</a>
								<?php 
							}
							else
							{
								?>
								<img border="0" align="absmiddle" src="../vulnmeter/images/delete.gif" style='width:12px; height: 12px;' class='disabled'/>
								<?php
							}
							?>
						</span>
						
						<span style='margin-left:5px;padding-right: 5px'>
							<a href="javascript:void(0);" class='link_icon' onclick="show_datasources('<?php echo $c_name?>', '<?php echo $cat_id?>', '')"><?php echo $c_name;?></a>
						</span>
					</div>
					
					<div class='new_item' id="category_ajax_<?php echo $category->get_id(); ?>"></div>
				</td>
			</tr>
				
			<tr class='family_<?php echo $cat_id;?>' style='display: none;'>
					<td valign='middle'>
						<table width='98%' class='noborder' cellpadding='0' style='margin:5px;'>
							<?php
							$list_subcategories = Subcategory::get_list($conn,'WHERE cat_id='.$cat_id.' ORDER BY name');
							
							foreach ($list_subcategories as $subcategory) 
							{
								$subcat_id = $subcategory->get_id();
								$sc_name   = $subcategory->get_name();
			
								?>
								<tr>
									<td class="nobborder" style="padding-left:40px">
										
										<div style="float:left">
											<span style='margin-right:8px;'>
												<a href="javascript:void(0);" class='link_icon' onclick="edit('subcategory', '<?php echo $cat_id."', '".$subcat_id."', '".$sc_name."'";?>)" title="<?php echo _("Edit")?>">
													<img border="0" align="absmiddle" src="../vulnmeter/images/pencil.png" height="12" />
												</a>
																
												<?php 
												if(!$subcategory->get_inUse())
												{
													?>
													<a href="javascript:void(0);" class='link_icon' onclick="confirmDelete('subcategory', <?php echo $cat_id;?>, <?php echo $subcat_id;?>)" title="<?php echo _("Delete")?>">
														<img border="0" align="absmiddle" style='width:12px; height: 12px;' src="../vulnmeter/images/delete.gif"/>
													</a>
													<?php 
												}
												else
												{
													?>
													<img border="0" align="absmiddle" src="../vulnmeter/images/delete.gif" style='width:12px; height: 12px;' class='disabled'/>
													<?php
												}
												?>
											</span>
											<strong>
											<a href="javascript:void(0);" class='link_icon' onclick="show_datasources('<?php echo $sc_name?>', '<?php echo $cat_id?>', '<?php echo $subcat_id?>')"><?php echo $sc_name;?></a>
																				
										</div>
										
										<div class='new_item' id="subcategory_ajax_<?php echo $subcat_id; ?>"></div>
									</td>				
								</tr>
								<?php 
							} 
							?>
							<tr>
								<td class="nobborder" style="padding-left:40px">
									<form action="category.php" method="POST">
										<input type="hidden" name="action" value="add_subcategory" />
										<input type="hidden" name="cat_id" value="<?php echo $cat_id;?>" />
										<input type="text" name="sc_name" value=""/>
										<input type="submit" class="small" style='margin-top: 5px;' value="<?php echo _('Add Subcategory');?>"/>
									</form>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?php
			}
			?>
		
			<tr>
				<td class='left' style="padding:10px">
					<form action="category.php" method="POST">
						<input type="hidden" name="action" id="action" value="add_category"/>
						<input type="text" name="c_name" id="c_name" value=""/>
						<input type="submit" class="small"  name='send' id='send' value="<?php echo _('Add Category');?>"/>
					</form>
				</td>
			</tr>
		</table>
    </body>
</html>

<?php $db->close();?>