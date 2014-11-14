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

require_once 'deploy_common.php';

//Checking perms
check_deploy_perms();


$db     = new ossim_db(TRUE);
$conn   = $db->connect();

$id     = GET("location");

ossim_valid($id,	OSS_HEX,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    die(ossim_error());
}

$checks = Locations::get_location_checks($conn, $id);


$elems = array(
	'0'  => _('IDS'),
	'1'  => _('Vulnerability Scans'),
	'2'  => _('Passive Inventory'),
	'3'  => _('Active Inventory'),
	'4'  => _('Netflow Monitoring')
);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<?php
        //CSS Files
        $_files = array();

        $_files[] = array('src' => 'av_common.css',             'def_path' => true);
        $_files[] = array('src' => 'jquery-ui.css',             'def_path' => true);
        $_files[] = array('src' => '/js/ibutton/style.css',     'def_path' => false);

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array();

        $_files[] = array('src' => 'jquery.min.js',                                 'def_path' => true);
        $_files[] = array('src' => 'jquery-ui.min.js',                              'def_path' => true);
        $_files[] = array('src' => 'utils.js',                                      'def_path' => true);
        $_files[] = array('src' => 'notification.js',                               'def_path' => true);
        $_files[] = array('src' => 'token.js',                                      'def_path' => true);
        $_files[] = array('src' => 'ibutton/iphone-style-checkboxes.js',            'def_path' => true);

        Util::print_include_files($_files, 'js');
    ?>

	<style type='text/css'>
		body 
		{
			background: #FFF;
		}
		
		#container
		{
    		position: relative;
    		margin: 10px auto 40px auto;
		}
		
		.title 
		{
    		margin: 10px auto 7px auto;
			font-weight: bold;
			text-align:center;
		}
		
		.switches 
		{
			padding-top: 10px;
			width:100%;
			text-align:center;
		}
		
		#notification
		{
			position: relative;
			margin: 10px auto 10px auto;
			width: 90%;

		}
		
		
	</style>

	<script type='text/javascript'>

		
		function change_service(elem, val, obj)
		{
			var service = $(elem).attr('id');
			var ctoken  = Token.get_token("deploy_ajax");
			
			$.ajax(
			{
				data:  {"action": 2, "data": {"id": '<?php echo $id ?>', "service": service, "value": ((val) ? 1 : 0)}},
				type: "POST",
				url: "deploy_ajax.php?&token="+ctoken, 
				dataType: "json",
				async: false,
				success: function(data)
				{ 
					if ( data.error )
					{	
						show_notification('notification', data.msg, 'nf_error', 5000);			
					} 
					else
					{
						show_notification('notification', data.data, 'nf_success', 5000);	
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) 
				{
					show_notification('notification', textStatus, 'nf_error', 5000);
					
					$(elem).attr('checked', ((val) ? '' : 'checked'));
					$(elem).iOSCheckbox("refresh");
				}
			});

		
		}
		
		$(document).ready(function()
		{
			$('.on_off :checkbox').iOSCheckbox(
			{
                onChange: function(elem, value) 
                { 
                    change_service(elem, value, this);
                }
            });
            
		});
		
	</script>
	
</head>

<body>
    
    <div id='container'>
    
    	<div id='notification'></div>
    	
    	<div class='title'>
    		<?php echo _('Select the right configuration for each service') ?>
    	</div>
    
    	<div class='switches'>
    		<table class='transparent' width='85%' align='center'>
    			<?php 
    			$i = 0;
    			
    			foreach ($elems as $id => $name) 
    			{
    				$checked = ($checks[$i] == '1') ? "checked=checked" : '';
    				$i++;
    			?>
    			
    				<tr class="on_off">
    					<td class='noborder left' width='95%'>
    						<label for="on_off">
    							<?php echo $name ?>
    						</label>
    					</td>
    					<td class='noborder' width='5%' style='text-align:left;padding-right:15px;'>
    						<input type="checkbox" id='<?php echo $id ?>' <?php echo $checked ?>/>
    					</td>
    				</tr>
    				
    			<?php
    			}
    			?>
    		 </table>
    	</div>
    	
    </div>
</body>
</html>
<?php
$db->close();
