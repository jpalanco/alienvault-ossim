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


$db    = new ossim_db();
$conn  = $db->connect();

$plgid = GET('id');

ossim_valid($plgid, OSS_HEX, OSS_NULLABLE, 'illegal:ID');

if (ossim_error()) 
{
    die(ossim_error());
}

$groups = Plugin_group::get_list($conn);

foreach ($_SESSION as $key=>$val) 
{
    if (preg_match("/^pid/",$key)) 
    {
        unset($_SESSION[$key]);
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	
	<style type='text/css'>
		
		#t_ds_group
		{
    		margin: 10px auto 40px auto;	
		}
		
		.bracket_data > td
		{
    		background: rgba(255, 255, 255, 0.2) !important;
		}
		
		.bracket_td_padding
		{
    		padding: 8px 30px !important;
		}
		
	</style>
	
	<script type='text/javascript'>
	
    	function toggle_info(id) 
    	{
    		$('#plugins'+id).toggle($('#plugins'+id).css('display') == 'none');
    		var img = '#img'+id;
    		
    		if ($(img).attr('src').match(/minus/)) 
    		{
    			$(img).attr('src','../pixmaps/plus-small.png');
    		} 
    		else 
    		{
    		    $("#brace_"+id).height($("#ftd_"+id).height());
    		    var bkbg_height = Math.round(($("#ftd_"+id).height()-111)/2);
    		   
    		    $("#brace_1_"+id).height(bkbg_height);
    		    $("#brace_2_"+id).height(bkbg_height);
    			$(img).attr('src','../pixmaps/minus-small.png');
    		}
    	}
    
        function delete_dsgroup(id) 
        {
            if(confirm("<?php echo _('You are going to delete a DS Group. This action cannot be undone. Are you sure?') ?>")) 
            {
                document.location.href='modifyplugingroups.php?action=delete&id='+id;
            }
            
            return false;
        }
    	
    	$(document).ready(function(){
    				
    		$('.edit_ds_group').on('click', function(e)
    		{
        		var id = $(this).attr('id').replace('edit_', '');    
        				
        		e.stopPropagation();   		
        		
        		document.location.href='modifyplugingroupsform.php?action=edit&id='+id;    		
    		});
    		
    		$('.delete_ds_group').on('click', function(e)
    		{
        		var id = $(this).attr('id').replace('delete_', '');
        		
        		e.stopPropagation();
        		
        		delete_dsgroup(id);  
    		});
    
    
    		$('#t_ds_group tr.data_info').on('click', function(e)
    		{
        		e.stopPropagation();
        		 	
        		toggle_info($(this).attr('id').replace('tr_', ''));			
    			
    			return false;  		
    		});		
    	});
    	
	</script>
	
</head>

<body>

    <div id='c_lmenu'>	
        <div class='c_r_lmenu'>	    
            <a class='button av_b_main' href="<?php echo AV_MAIN_PATH ?>/policy/modifyplugingroupsform.php?action=new">
                <?php echo _("Add new group")?>
            </a>            
        </div>
    </div>
    
    <table id='t_ds_group' class="table_data">   
     
        <thead>            
            <tr>
                <th></th>
                <th><?php echo _("DS Group Name") ?></th>
                <th><?php echo _("Description") ?></th>
                <th><?php echo _("Actions") ?></th>
            </tr>
        </thead>
        
        <tbody>
            
            <?php

        	foreach($groups as $group) 
        	{
        		$id = $group->get_id();

        		if ($id == $plgid) 
        		{
            		$color = "lightyellow";
        		}
        		?>
                <tr class="noborder data_info" id='tr_<?php echo $id?>'>
                
                    <td width="50" class="pleft">
                        <a name="<?php echo $id?>"></a>
                        <img id="img<?php echo $id?>" src="../pixmaps/plus-small.png" align="absmiddle" border="none"/>
                    </td>
                    
                    <td style="padding-left:4px;padding-right:4px" width="200">
                        <b><?php echo htm($group->get_name()) ?></b>
                    </td>
                    
                    <td style="text-align:left;padding-left:5px">
                        <?php echo htm($group->get_description()) ?>
                    </td>
                    
                    <td width="130" class="pright" style="padding:2px">
        				
        				<a href='javascript:void(0);'><img class='edit_ds_group' id='edit_<?php echo $id?>' src='<?php echo AV_PIXMAPS_DIR."/pencil.png"?>' align='absmiddle' alt='<?php echo _("Edit")?>'/></a>
        				<a href='javascript:void(0);'><img class='delete_ds_group' id='delete_<?php echo $id?>' src='<?php echo AV_PIXMAPS_DIR."/delete.gif"?>' align='absmiddle' alt='<?php echo _("Delete")?>'/></a>		
                    </td>
                    
                </tr>
                
                <tr style='display:none'><td colspan="4"></td></tr>
                <tr id="plugins<?php echo $id ?>" class='bracket_data' style="display:none;">
                
                    <td class="noborder" id="ftd_<?php echo $id?>">
                    
                        <div id="brace_<?php echo $id?>">
                            <div style="height:29px;width:36px;"><img src="../pixmaps/bktop.gif" border="0"/></div>
                            <div id="brace_1_<?php echo $id?>" style="background: url('../pixmaps/bkbg.gif') repeat-y scroll 0% 0% transparent;"/></div>
                            <div style="height:50px;width:36px;"><img src="../pixmaps/bkcenter.gif" border="0"/></div>
                            <div id="brace_2_<?php echo $id?>" style="background: url('../pixmaps/bkbg.gif') repeat-y scroll 0% 0% transparent;"/></div>
                            <div style="height:30px;width:36px;"><img src="../pixmaps/bkdown.gif" border="0"/></div>
                        </div>
                        
                    </td>
                    
                    <td class="bracket_td_padding" colspan="3">
                    
                        <table class="table_data">
                            <thead>
                            
                            <tr>
                            
                                <th class="center">
                                    <?php echo _("Data Source") ?>
                                </th>
                                
            					<th class="center">
            					    <?php echo _("Data Source Name") ?>
            					</th>
            					
            					<th class="center">
            					    <?php echo _("Description") ?>
            					</th>
            					
            					<th class="center">
            					    <?php echo _("Event types") ?>
            					</th>
            					
                            </tr>
                            
                            </thead>
                            
                            <tbody>
                            <?php
            				foreach($group->get_plugins() as $p) 
            				{    				
            				    ?>
                                <tr>
                                
                                    <td class="center">
                                        <?php echo $p['id'] ?>
                                    </td>
                                    
                                    <td class="center">
                                        <?php echo $p['name'] ?>
                                    </td>
                                    
                                    <td class="center" nowrap>
                                        <?php echo $p['descr'] ?>
                                    </td>
                                    
                                    <td class="left" width="50%">
                                        <?php echo ($p['sids'] == "0") ? "ANY" : str_replace(",",", ",$p['sids']) ?>
                                    </td>
                                    
                                </tr>
                                <?php
            				} 
            				?>  
            				
                            </tbody>    
                        </table>
                    </td>
                    
                </tr>
                
            <?php 
            } 
            ?>
            
        </tbody>
        
    </table>
    
    <?php
    if ($plgid != "") 
    { 
        ?>
        <script type='text/javascript'>
            toggle_info('<?= $plgid ?>');document.location.href='#<?= $plgid ?>';
        </script>
        <?php
    } 
    ?>

</body>
</html>