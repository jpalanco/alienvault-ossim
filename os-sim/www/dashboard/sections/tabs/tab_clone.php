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

Session::logcheck("dashboard-menu", "ControlPanelExecutive");

$user = Session::get_session_user();
$pro  = Session::is_pro();
$db   = new ossim_db();
$conn = $db->connect();

$tabs = Dashboard_tab::get_tabs_by_user($user, true);

$db->close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	
	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                     'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',       'def_path' => TRUE)       
        );
        
        Util::print_include_files($_files, 'js');
    ?> 
	
	<style type='text/css'>
		
		body
		{
    		background: #333333 !important;
		}
		

		a, a:hover {
			text-decoration:none;

		}
		
		#db_tab_clone_list
		{
    		background: #333333 !important;
		}
		
		#db_tab_clone_list td
		{
    		padding: 3px 0;
    		background: #e7e7e7;
    		opacity: 1;
		}
		
		#db_tab_clone_list tr:nth-child(odd) td
		{
    		background: #f3f3f3 !important;
		}
		
	</style>
	
	<script type='text/javascript'>
	
		function clone_tab(id)
		{
            if (typeof(parent.clone_tab) == "function") 
            {
                parent.clone_tab(id);
            }		
		}
	
	</script>

</head>

<body>	
	<table align="center" class='transparent' style="margin-top:5px;height:220px;background: #333333;" width="95%">				
		<tr>
			<td class="nobborder"  style="text-align:center;">
				<div style='height:200px;overflow:auto;'>
				<?php 
				if (count($tabs) > 0) 
				{ 
				?>

					<table align='center' class='transparent' id='db_tab_clone_list' width='100%'>
    					<?php 
    					$color = 0;	
    					foreach ($tabs as $t) 
    					{    
    					?>
    						<tr>
    							<td class='center'>
    								<a href='javascript:;' onclick="clone_tab('<?php echo $t->get_id() ?>');">
    								    <?php echo $t->get_title() ?>
    								<a>
    							</td>
    						</tr>
    						
    					<?php
    					} 
    					?>					
					</table>

				<?php 
				} 
				else
				{
    				
				    ?>
				    <span><?php echo _("No Tabs Available") ?>.</span>
				    <?php 
				}  
				?>
				</div>				
			</td>
		</tr>		

		</tr>
		<tr>
			<td class='noborder' id='update' align="center" style="padding:10px 0px 5px 0px;color:white;">
				<?php echo _("Click on the tab to clone it.")?>
			</td>
		</tr>

		
	</table>		

</body>
</html>
