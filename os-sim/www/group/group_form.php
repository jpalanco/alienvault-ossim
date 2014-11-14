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


Session::logcheck('environment-menu', 'PolicyHosts');


/****************************************************
 ******************** Host Data *********************
 ****************************************************/

//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


$id  = GET('id');
$msg = GET('msg');

ossim_valid($id, OSS_HEX, 'illegal:' . _('Asset group ID'));

if (ossim_error())
{ 
    echo ossim_error(_('Error! Asset group not found'));
    
    exit();
}


$asset_group = new Asset_group($id);

$asset_group->can_i_edit($conn);

$asset_group->load_from_db($conn);

//Getting group data
$id          = $asset_group->get_id();
$name        = $asset_group->get_name(); 
$owner       = $asset_group->get_owner();
$descr       = $asset_group->get_descr(); 
$threshold_a = $asset_group->get_threshold('a');
$threshold_c = $asset_group->get_threshold('c');
$nagios      = Asset_group_scan::is_plugin_in_group($conn, $id, 2007);


//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>    
	<script type="text/javascript" src="../js/jquery.min.js"></script>	
    <script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	
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
    		height: 45px !important;    		
		}  
				
		#ag_container 
		{		  
		    width: 550px;
		    margin: 40px auto 20px auto;
		    padding-bottom: 10px;
		}
		
		#ag_container #t_container
		{ 
		   margin: auto;
		   width: 100%;
		   background: none;
           border: none;
		}		
			  
		#t_container th 
		{
		    width: 150px;		        		
		}			
      
        .legend 
		{ 
    		font-style: italic;
    		text-align: center; 
    		padding: 0px 0px 5px 0px;
    		margin: auto;
    		width: 400px;
		}    	
				
        #av_info 
        {
            width: 600px; 
            margin: 10px auto;            
        }                           
	</style>	
	
	<script type="text/javascript">
   		
		$(document).ready(function(){              
                                              
            /***************************************************
             *********************** Token *********************
             ***************************************************/
            
            Token.add_to_forms();
            

						
			/****************************************************
             ************ Ajax Validator Configuration **********
             ****************************************************/

			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'ag_form',
					url : "save_group.php"
				},
				actions: {
					on_submit:{
						id: 'send',
						success: '<?php echo _('Save')?>',
						checking: '<?php echo _('Saving')?>'
					}
				}
			};
		
			ajax_validator = new Ajax_validator(config);					  
			
            $('#send').click(function() { 
            	ajax_validator.submit_form();
            });
            
            $('#cancel').click(function() { 
            	parent.GB_close();
            });
			
			
						
			/****************************************************
             ***************** Elastic Textarea *****************
             ****************************************************/
            
			$('textarea').elastic();
			
			  
			      			
			/****************************************************
             ******************* Greybox Options ****************
             ****************************************************/
                			
			if (!parent.is_lightbox_loaded(window.name))
			{ 			
    			$('.c_back_button').show();
    		}
    		else
		{
    			$('#ag_container').css('margin', '10px auto 20px auto');

    			// Loaded from details and some data changed
    			<?php
    			if ($msg == 'saved')
    			{
    			?>
    		    if(typeof(top.frames['main'].force_reload) != 'undefined')
    		    {
    		        top.frames['main'].force_reload = 'info';
    		    }
    		    <?php
            }
    		    ?>
		}      
      	});
	</script>
</head>

<body>
	
	<div class="c_back_button">
        <input type='button' class="av_b_back" onclick="javascript:history.go(-1);"/>   
    </div>        
    
    <div id="av_info">
        <?php        
        if ($msg == 'saved')
        {
            $config_nt = array(
                'content' => _('Asset group saved successfully'),
                'options' => array (
                    'type'          => 'nf_success',
                    'cancel_button' => TRUE
               ),
                'style'   => 'width: 80%; margin: auto; text-align:center;'
            ); 
                            
            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        ?>
    </div>
  
		
<div id='ag_container'>

    <div class='legend'>
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>	

    <form method="POST" name="ag_form" id="ag_form" action="save_group.php">
        
        <input type="hidden" class='vfield' name="id" id="id" value="<?php echo $id?>"/>
	    
		<table id='t_container'>
			<tr>
				<th>
				    <label for="ag_name"><?php echo _('Name') . required();?></label>
				</th>
				
				<td>
				    <input type='text' name='ag_name' id='ag_name' class='vfield' value="<?php echo $name?>"/>
				</td>   
			</tr>
				
			<tr>
				<th>
				    <label for="owner"><?php echo _('Owner')?></label>
				</th>
				
				<td>
				    <input type='text' name='owner' id='owner' class='vfield' value="<?php echo $owner?>"/>
				</td>   
			</tr> 					

			<tr>
				<th>
				   <label for='descr'><?php echo _('Description');?></label>
				</th>
				<td>
					<textarea name="descr" id='descr' class='vfield'><?php echo $descr;?></textarea>
				</td>
			</tr>        						

			<tr>
				<th>
				    <label for='threshold_c'><?php echo _('Threshold C') . required();?></label>
				</th>
				<td>
					<input type="text" name="threshold_c" id="threshold_c" class='vfield' value="<?php echo $threshold_c?>"/>
				</td>
			</tr>

			<tr>
				<th>
				    <label for='threshold_a'><?php echo _('Threshold A') . required();?></label>
				</th>
				<td>
					<input type="text" name="threshold_a" id="threshold_a" class='vfield' value="<?php echo $threshold_a?>"/>
				</td>
			</tr>
			
			<tr>
				<th>
				    <label for='nagios'><?php echo gettext("Scan options");?></label>
				</th>
				<td class="left">
					<input type="checkbox" name="nagios" id='nagios' value="1" <?php echo( $nagios == 1) ? "checked='checked'" : ""; ?>/>
					<?php echo gettext("Enable Nagios for all hosts within this Group"); ?> 
				</td>
			</tr>
						 
			<!-- Save and Cancel buttons -->
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 10px;">
                    <input type="button" name="cancel" class="av_b_secondary" id="cancel" value="<?php echo _('Cancel')?>"/>
                    <input type="button" name="send" id="send" value="<?php echo _('Save')?>"/>
                </td>
            </tr>  
		</table>  
    </form>
</div>
</body>
</html>