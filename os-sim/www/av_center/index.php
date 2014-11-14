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

//Config File
require_once 'config.inc';
require_once 'data/breadcrumb.php';

$pro = Session::is_pro();

//Access to section from an external link
$external_access = FALSE;

$section = GET('section');
$ip      = GET('ip');

ossim_valid($section, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _('Section'));

if (ossim_error())
{
	die(ossim_error());
}

if (!empty($ip) && !empty($section))
{
	ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _('IP Address'));
					
	if (!ossim_error() && array_key_exists($section, $sections))
	{
		$external_access = TRUE;        
	}
}

$db       = new ossim_db();
$conn     = $db->connect();
          
$avc_list = Av_center::get_avc_list($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("OSSIM Framework");?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<!--<script type="text/javascript" src="../js/jquery-1.4.2.min.js"></script>-->
	
	<!-- Code Mirror -->
	<script type='text/javascript' src='js/codemirror/codemirror.js' ></script>
	<script type='text/javascript' src="js/codemirror/mode/xmlpure/xmlpure.js"></script>
	<script type='text/javascript' src="js/codemirror/mode/properties/properties.js"></script>
	<script src="js/codemirror/util/dialog.js"></script>
	<script src="js/codemirror/util/searchcursor.js"></script>
	<script src="js/codemirror/util/search.js"></script>
	<link rel="stylesheet" type="text/css" href="js/codemirror/codemirror.css"/>
			
	<!-- Dynatree libraries: -->
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
			
	<link type="text/css" rel="stylesheet" href="/ossim/style/tree.css" />

	<!-- Autocomplete libraries: -->
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.autocomplete.css"/>
	
	<!-- Elastic textarea: -->
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
        
    <!-- Progress Bar: -->
    <script type="text/javascript" src="js/progress_bar.js"></script>   
        
    <!-- Vertical Progress Bar: -->
    <script type="text/javascript" src="js/vprogress_bar.js"></script>    
    
    <!-- Spark Line: -->
    <script type="text/javascript" src="../js/jquery.sparkline.js"></script>
    
    <!-- JQplot: -->
    <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
    <link rel="stylesheet" type="text/css" href="../js/jqplot/jquery.jqplot.css" />
    <script language="javascript" type="text/javascript" src="../js/jqplot/jquery.jqplot.min.js"></script>
    <script language="javascript" type="text/javascript" src="../js/jqplot/plugins/jqplot.pieRenderer.js"></script>
        
    <!-- Xbreadcrumbse: -->
    <script type="text/javascript" src="js/xbreadcrumbs.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/xbreadcrumbs.css"/>

    <!-- JQuery tipTip: -->
    <script src="js/jquery.tipTip.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
            
    <!-- JQuery DataTable: -->
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
        
    <!-- JQuery MultiSelect: -->
    <script type="text/javascript" src="/ossim/js/jquery.tmpl.1.1.1.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/ui.multiselect.css"/>
		
	<!-- JQuery Context Menu: -->
	<script type="text/javascript" src="../js/jquery.contextMenu.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.contextMenu.css"/>
       
    <!-- AV Activity Bar plugin -->
    <script type="text/javascript" src="/ossim/js/av_progress_bar.js.php"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/progress.css"/>
	

    <!-- Own libraries: -->
	<script type="text/javascript" src="js/config.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type='text/javascript' src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="js/avc_msg.php"></script>
	<script type="text/javascript" src="js/common.js"></script>
	<script type="text/javascript" src="js/change_control.js"></script>
	<script type="text/javascript" src="js/av_center.js"></script>
    <script type="text/javascript" src="js/av_tree.js"></script>	
        
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>   
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>   
        
	<script type='text/javascript'>                                 
									
		$(document).ready(function(){
		
    		//JQplot
    		$.jqplot.config.enablePlugins = true;
    					
    		//Ajax Request
    		ajax_requests = new Ajax_Requests(20);
    		
    		//Action in progress: Saving data in forms.
    		action_in_progress = false;
    
    		$('#breadcrumbs').xBreadcrumbs();
    		
    		tree = new Tree('profile');                             
    			
    		if (tree.tree_status == '')
    		{
    			<?php
    			if ($avc_list['status'] == 'error')
    			{
    				?>
    				$('.avc_hmenu').remove();
    				display_sec_errors(labels['error_ret_info']);
    				<?php
    			}
    			else
    			{
    				?>
    				tree.load_tree();
    				   				
    				$('#avtc_container').tipTip({maxWidth: 'auto', content: labels['show_tree']});  				    				
    				    				
    				$('#avtc_container').click(function() { 
    				    toggle_tree(); 
    				}); 
    				
    				//Change Tree ordenation
    				$('#tree_ordenation').change(function() {
						var type = $('#tree_ordenation').val();
						tree.change_tree(type);
    				}); 
    				
    				$('#search').click(function() {Main.pre_search_avc();});
    													
    				<?php
    				//Alienvault Components (Autocomplete)
    				if (is_array($avc_list['data']))
    				{
    					$cont = 0;
    					foreach ($avc_list['data'] as $system_id => $data)
    					{
    						$av_components .= ($cont > 0) ? ", " : "";
    						
    						$hostname = $data['name'];
    						$host_ip  = $data['admin_ip'];
    						
    						$av_components .= '{"txt" : "'.$hostname.' ['.$host_ip.']", "id" :"'.$system_id.'" }'; 
    								
    						$cont++;
    					}
    				}
    				?>
    				var av_components = [ <?php echo $av_components?> ];
    				Main.autocomplete_avc(av_components);
    												
    				$('#go').click(function() { Main.search(); }); 
    														
    				<?php
    				if ($external_access == TRUE && count($avc_list['data']) == 1)
    				{
    					$ip_data = Av_center::get_system_info_by_ip($conn, $ip);
    					
    					if($ip_data['status'] == 'error') 
    					{
    						if(is_array($avc_list['data']) && !empty($avc_list['data'])) 
    						{
    							$system_ids = array_keys($avc_list['data']) ;
    							$ip_data    = Av_center::get_system_info_by_id($conn, $system_ids[0]);
    						}
    					}											
    					
    					if ($ip_data['status'] == 'success')
    					{						
    						?>
    						Main.display_avc_info(false);
    																				
    						var data = {
    							system_id: '<?php echo $ip_data['data']['system_id']?>', 
    							profiles:  '<?php echo $ip_data['data']['profile']?>', 
    							name:      '<?php echo $ip_data['data']['name']?>', 
    							admin_ip:  '<?php echo $ip_data['data']['admin_ip']?>' 
    						};
    						
    						var id_section = '<?php echo Util::htmlentities($section) ?>';
    						Main.external_access(data, id_section, true); 
    						<?php
    					}
    					else
    					{
    						?>
    						display_sec_errors(labels['error_ret_info']);
    						<?php
    					}
    				}
    				else
    				{
    					?>
    					Main.display_avc_info(true);
    					<?php
    				}
    			}
    			?>
    		}
    		else
    		{
    			$('.avc_hmenu').remove();
    			display_sec_errors(tree.tree_status);
    		}				
	});
	</script>
</head>

<body>
    
	<?php
	
	$db->close();
	 
    //Local menu             
    include_once '../local_menu.php';
    session_write_close();
    ?>
    <div id='main'>
        
        <div class='c_back_button'>
            <input type='button' class="av_b_back" id='lnk_go_back'/>
        </div>
        
        <div id='container_center'>
            <div id="avc_actions"></div>
            
            <table id='container_bc'>
                <tr>
                    <td id='bc_data'>
                        <ul class="xbreadcrumbs" id="breadcrumbs">
                            <li class='current'><a href='index.php' class="home"><?php echo _('Alienvault Center')?></a></li>
                        </ul>
					</td>
                </tr>
            </table>
            
            <table id='section_container'>                
				<tr class='avc_hmenu'>
					<td id='avc_clcontainer'>
						<div id='search_container'>					    
						      
							<div id='l_sc'>
								<label id='lbl_search' for='search'><?php echo _('Search')?>:</label>
								<input type='text' id='search' name='search' value='<?php echo _('Search by hostname or IP')?>'/>
								<input type='hidden' id='h_search' name='h_search'/>
								<input type='button' id='go' name='go' class='small' value='<?php echo _('Go')?>'/>
								
								<div id='search_results'>
    						        <div></div>
    						    </div>								
							</div>
							<div id='r_sc'>
    							<label id='lbl_to' for='tree_ordenation'><?php echo _('Order By')?>:</label>
    							<select id='tree_ordenation' name='tree_ordenation'>
    								<option value='profile' selected='selected'><?php echo _('profile')?></option>
    								<option value='hostname'><?php echo _('hostname')?></option>
    							</select>
							</div>
						</div>
						<div id='tree_container_top'>							
						</div>
						<div id='tree_container_bt'></div>
					</td>
				</tr>
				<tr class='avc_hmenu'>        
					<td id='avc_cmcontainer'>
                        <div id='avtc_container'>                         
                            <div id='avc_arrow' class='arrow_bottom'></div>
                        </div>
					</td>
				</tr>			
			                
                <tr>
                    <td id='avc_crcontainer'>
                        <div class="avc_content">
                            <div id="avc_data">
                                <div id='load_avc_data'></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>         
        </div>
    </div>
</body>

</html>