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

Session::logcheck("configuration-menu", "PolicyPolicy");


//DB
$db   = new ossim_db();
$conn = $db->connect();

//Version
$pro = Session::is_pro();

$id   = GET('id');
ossim_valid($id, OSS_HEX,OSS_NULLABLE, 'illegal:' . _("Policy Group ID"));

if (ossim_error()) 
{
    die(ossim_error());
}

//Policygroup Variables
$name     = '';
$ctx      = '';
$ctx_name = _('Select an Entity'); 
$descr    = '';

//If the id is not empty, we are gonna edit a policy group, otherwise we're gonna create a new one
if(!empty($id))
{
	if($id == '00000000000000000000000000000000')
	{
		echo ossim_error(_("You cannot modify the default group."), AV_NOTICE);
		exit();
	}
	
	//retrieving policygroup info...
	$policy_groups = Policy_group::get_list($conn, '', " AND id=UNHEX('$id')");

	if (!isset($policy_groups[0])) 
	{
	   exit();
	}
	
	$name     = $policy_groups[0]->get_name();
	$ctx      = $policy_groups[0]->get_ctx();
	$descr    = $policy_groups[0]->get_descr();
	
	if($pro)
	{
		$ctx_name = Acl::get_entity_name($conn,$ctx);  
	}
		
}	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title> <?php echo _("OSSIM Framework") ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		<link rel="stylesheet" type="text/css" href="../style/tree.css" />
		<script type="text/javascript" src="../js/jquery.min.js"></script>
		<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
		
		<style type='text/css'>
		  
            a 
            {
                cursor:pointer;
            }
            
            input[type='text'], input[type='hidden'], select 
            {
                width: 98%; 
                height: 18px;
            }
            
            textarea 
            {
                width: 98%; 
                height: 45px;    		
            }
		  
            #c_ds
            {
                margin: 20px auto;
                width: 500px;
            }
            
            .legend 
    		{ 
        		font-style: italic;
        		text-align: center; 
        		padding: 0px 0px 5px 0px;
        		margin: auto;
        		width: 400px;
    		}	
            
            #t_ds
            {
                width: 100%;
                margin: auto;  	  
            }
            
            #t_ds th
            {
                width: 150px;
                white-space: nowrap; 	  
            }
                       
            #td_tree
            {
                vertical-align: top; 
            }
            
            #ctx_txt
            {
                padding-top: 2px;    
            }
            
            #containerctx
            {
                width: 99% !important;               
            }
            
            .ctx_info
            {
                font-weight: bold;
                margin: 0px 3px 0px 0px;
            }
            
            #ctx_txt
            {
                font-style: italic;
                font-weight: normal;    
            }
            
            .dynatree-container
            {
                padding-top: 0px !important;
            }
		  
		</style>
		
		<script type="text/javascript">		
		<?php 
		if($pro && empty($id)) 
		{ 
    	   ?>
    		$(document).ready(function()
    		{
    			$('#containerctx').dynatree({
    				initAjax: { url: "../tree.php?key=contexts" },
    				clickFolderMode: 2,
    				onActivate: function(dtnode) {
    					if (dtnode.data.key.match(/^e_/)) {
    						dtnode.deactivate();
    						var ctx    = dtnode.data.key.replace("e_","");
    						var entity = dtnode.data.val;
    						$('#ctx').val(ctx);
    						$('#ctx_txt').html(entity);
    					} 
    				},
    				onDeactivate: function(dtnode) {},
    				onLazyRead: function(dtnode){
    					dtnode.appendAjax({
    						url: "../tree.php",
    						data: {key: dtnode.data.key}
    					});
    				}
    			});
    			
    			//console.log($('#fsid4003').attr('action'));
    			
    		});		
    		<?php 
        } 
        ?>	
        </script>		
		
	</head>

	<body>
		
		<div id='c_ds'>
		      
		    <div class='legend'>
        	     <?php echo _("Values marked with (*) are mandatory");?>
        	</div>  																		
		
    		<form method="POST" action="newpolicygroup.php">
    		
    		    <input type="hidden" name="id" value="<?php echo $id ?>"/>
    		      
    			<table id='t_ds' class='transparent'>    		
                    
                    <tr>
                        <th> <?php echo _("Name").required();?> </th>
                        <td class="left nobborder"><input type="text" name="name" value="<?php echo $name?>"/></td>
                    </tr>
    							
					<?php
					if($pro)
					{ 					
    					if(empty($id)) 
    					{     					
                            ?>
                            <tr>
                                <th rowspan="2"> <?php echo _("CTX").required(); ?> </th>                                
                                <td class='left noborder' id='td_tree'>
                                    <div id="containerctx" class='container_ptree'></div>
                                    
                                    <div>
                                         <input type="hidden" id='ctx' name="ctx" value="<?php echo $ctx ?>"/>
                                         <span class='ctx_info'><?php echo _("Entity selected")?>:</span>
                                         <span id='ctx_txt'><?php echo $ctx_name ?></span>
                                    </div>
                                </td>
                            <tr>          
                            <?php 
    					}
    					else
    					{
        					?>
        					<tr>
                                <th> <?php echo _("CTX").required(); ?> </th>                                
                                <td class='left noborder'>
                                    <input type="hidden" id='ctx' name="ctx" value="<?php echo $ctx ?>"/>
                                    <span id='ctx_txt'><?php echo $ctx_name ?></span>
                                </td>
                            <tr>
                            <?php        					
    					}  	
    					?>
    							
						
																		
						<?php				    					
					}		
					?>
					<tr>
						<th> <?php echo _("Description") ?> </th>
						<td class="left nobborder">
							<textarea name="descr" rows="2" cols="18"><?php echo $descr?></textarea>
						</td>
					</tr>
					
					<tr>
						<td colspan="2" class="nobborder" style="text-align:center; padding-top: 10px;">
							<input type="submit" value="<?=_("OK")?>"/>
						</td>
					</tr>
				</table>    					
    		</form>
		</div>
	</body>
</html>
<?php 
$db->close();
