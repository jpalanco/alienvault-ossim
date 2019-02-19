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


require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');

//Current sensor
$sensor_id = $_SESSION['ossec_sensor'];

$db     = new ossim_db();
$conn   = $db->connect();   

$s_data = Ossec_utilities::get_sensors($conn, $sensor_id);
$sensor_opt = $s_data['sensor_opt'];

$db->close();

//Check available sensors
if (!is_array($s_data['sensors']) || empty($s_data['sensors']))
{
    $styles = 'width: 90%; text-align:left; margin: 50px auto;';
    
    echo ossim_error(_('There is no sensor available'), AV_INFO, $styles);
    exit();
}

session_write_close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	<script type="text/javascript" src="/ossim/js/ajax_validator.js"></script>
	<script type="text/javascript" src="/ossim/js/messages.php"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>
	
	<!-- Jquery Dropdown: -->
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dropdown.css"/>
	<script type="text/javascript" src="/ossim/js/jquery.dropdown.js" charset="utf-8"></script>
	
	<!-- Jquery Elastic Source: -->
	<script type="text/javascript" src="/ossim/js/jquery.elastic.source.js" charset="utf-8"></script>
		
	<!-- Codemirror: -->
	<script type='text/javascript' src="/ossim/js/codemirror/codemirror.js"></script>
	
	<!-- Greybox: -->
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>	
	
    <!-- JQuery TablePagination: -->
    <script type="text/javascript" src="/ossim/js/jquery.tablePagination.js"></script>
        
	<!-- JQuery DataTable: -->
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.plugins.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
	
	<!-- JQuery tipTip: -->
    <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	
	<script type="text/javascript" src="/ossim/ossec/js/ossec_msg.php"></script>
	<script type="text/javascript" src="/ossim/ossec/js/common.js"></script>
	<script type="text/javascript" src="/ossim/ossec/js/agents.js"></script>
	
	
	<script type="text/javascript">
	    
	    var editor = null;
	    var timer  = null;

	    var ajax_requests = new Ajax_Requests(20);
	    
		$(document).ready(function() {
						
			$("ul.oss_tabs li:first").addClass("active");
				
			$("ul.oss_tabs li").click(function(event) { 
				event.preventDefault(); 
				show_tab_content(this); 
				load_agent_tab($(this).find("a").attr("href"));
			});
				
			load_agent_tab("#tab1");			
		});		
	</script>
	
</head>

<body>

<?php include_once AV_MAIN_ROOT_PATH.'/local_menu.php';?>

<div id='container_center'>
        		
	<table id='tab_menu'>
		<tr>
			<td id='oss_mcontainer'>
				<ul class='oss_tabs'>
				    <li id='litem_tab1'><a href="#tab1" id='link_tab1'><?php echo _('Agent Control')?></a></li>												
					<li id='litem_tab2'><a href="#tab2" id='link_tab2'><?php echo _('Syschecks')?></a></li>
					<li id='litem_tab3'><a href="#tab3" id='link_tab3'><?php echo ucfirst(basename(Ossec_agent::CONF_PATH))?></a></li>
				</ul>
			</td>
		</tr>
	</table>
	
	<table id='tab_container'>
		<tr>
			<td>							
				<div id='tabs'>	    
					
					<?php $s_class = (Session::is_pro() && count($s_data['sensors']) > 1) ? 's_show' : 's_hide';?>
		      
        			<div class='c_filter_and_actions'>						
                        <div class='c_filter'>
                            <label for='sensors'><?php echo _("Select sensor")?>:</label>
                            <select id='sensors' name='sensors' class='vfield <?php echo $s_class?>' disabled='disabled'>
                            	<?php echo $sensor_opt?>
                            </select>
                        </div>
                    </div>
                    
                    <div id='container_c_info'><div id='c_info'></div></div>
				
					<div id="tab1" class="tab_content"></div>
				
					<div id="tab2" class="tab_content" style='display:none;'></div>
																				
					<div id="tab3" class="tab_content" style='display:none;'></div>
			
				</div>
			</td>
		</tr>
		
		<tr>
			<td class='noborder'>
				<div class='notice'><div><span>(*) <?php echo _('You must restart HIDS for the changes to take effect')?></span></div></div>
			</td>
		</tr>
	</table>
</div>

</body>
</html>
