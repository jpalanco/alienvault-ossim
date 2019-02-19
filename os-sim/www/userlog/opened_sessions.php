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

Session::useractive("../session/login.php");

require_once 'classes/DateDiff.inc';

$db         = new ossim_db();
$conn       = $db->connect();

$geoloc     = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

$version    = $conf->get_conf('ossim_server_version');
$pro        = Session::is_pro();

$my_session = session_id();


function get_country($ccode)
{
	if($ccode == '')
	{
	    $flag = '';
	}
	else
	{
	    if ($ccode == 'local')
	    {
		    $flag = '../forensics/images/homelan.png';
		}
	    else
	    {
		    $flag = '../pixmaps/flags/'.$ccode.'.png';
		}    
	}
	
	return $flag;
}


function get_user_icon($login, $pro)
{
    $pixmaps = '../pixmaps/user-green.png';

    $db    = new ossim_db();
    $conn  = $db->connect();
     
	$user  = Session::get_list($conn, "WHERE login='$login'");
					
	if ($pro)
	{
		// Pro-version
		if ($login == ACL_DEFAULT_OSSIM_ADMIN || $user[0]->get_is_admin())
		{
			$pixmaps = '../pixmaps/user-gadmin.png';
		}
		elseif (Acl::is_proadmin($conn,$user[0]->get_login()))
		{
			$pixmaps = '../pixmaps/user-business.png';
		}			
	} 
	else 
	{
		// Open Source
		if ($login == ACL_DEFAULT_OSSIM_ADMIN || $user[0]->get_is_admin())
		{
			$pixmaps = "../pixmaps/user-gadmin.png";
		}		
	}
	
	$db->close();
	
	return $pixmaps;
}

$where         = '';
$users         = array();
$allowed_users = array();


if  (Session::am_i_admin() || ($pro && Acl::am_i_proadmin()))
{
	if (Session::am_i_admin())
	{
		$users_list = Session::get_list($conn, 'ORDER BY login');
    }
	else
	{
		$users_list = Acl::get_my_users($conn,Session::get_session_user());
	}
	
	if ( is_array($users_list) && !empty($users_list) )
	{
		foreach($users_list as $v)
		{
			$users[] = (is_object($v) )? $v->get_login() : $v['login'];
		}
		
		$where = "WHERE login in ('".implode("','",$users)."')";
	}
}
else
{ 
	$where = "WHERE login = '".Session::get_session_user()."'";
}

$allowed_users = Session_activity::get_list($conn, $where.' ORDER BY activity DESC');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?php echo _('Opened Sessions')?> </title>
		<meta http-equiv="Pragma" content="no-cache"/>
	    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
	    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		
		<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    	<script type="text/javascript" src="/ossim/js/notification.js"></script>
    	<script type="text/javascript" src="/ossim/js/messages.php"></script>
    	<script type="text/javascript" src="/ossim/js/utils.js"></script>
    	    	
        <!-- JQuery DataTable: -->
        <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
        <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
    	
    	<!-- JQuery tipTip: -->
        <script src="/ossim/js/jquery.tipTip.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
				
		<script type="text/javascript">
		
            var msg = new Array();
				msg[0]  = '<span><?php echo _('Processing action')?>...</span>';
				
            
            var timer = null;
             
			
			function logout(id_session)
			{
				$.ajax({
					type: "POST",
					url: "forced_logout.php",
					data: "id="+ id_session,
					dataType: "json", 
					beforeSend: function(xhr){
						$('#c_info').html('');
						show_loading_box('container_center', msg[0], '');
					},
					error: function(data){
						hide_loading_box();
						
						var config_nt = {content: av_messages['unknown_error'], 
							options: {
								type: 'nf_error',
								cancel_button: true
							},
							style: 'width: 90%; margin: auto; text-align:center;'
						};
			
						nt            = new Notification('nt_1', config_nt);
						notification  = nt.show();
						
						$("#c_info").html(notification);
					},
					success: function(data){
						
						hide_loading_box();
												
						var cnd_1   = (typeof(data) == 'undefined' || data == null);
                		var cnd_2   = (typeof(data) != 'undefined' && data != null && data.status == 'error');		
                		                		               				
                        if (cnd_1 || cnd_2)
                        {
                            var error_msg = (cnd_1 == true) ? av_messages['unknown_error'] : data.data;
                            
                            var config_nt = {content: error_msg, 
                                options: {
                                    type: 'nf_error',
                                    cancel_button: true
                                },
                                style: 'width: 90%; margin: auto; text-align:center;'
                            };
                            
                            nt            = new Notification('nt_1', config_nt);
                            notification  = nt.show();
                            
                            $("#c_info").html(notification);
                            $("#c_info").fadeIn(4000);
                        }
                        else
                        {                    		                  		      
                            var config_nt = {content: data.data, 
                                options: {
                                    type: 'nf_success',
                                    cancel_button: true
                                },
                                style: 'width: 90%; margin: auto; text-align:center;'
                            };
                                
                            nt            = new Notification('nt_1', config_nt);
                            notification  = nt.show();					           
                           
                            var tr = document.getElementById(id_session);
                        								
                            if (tr != null)
                            {					           
                               $('#ops_table').dataTable().fnDeleteRow(tr, null, true);
                               
                               $("#c_info").html(notification);
                               $("#c_info").fadeIn(4000);
                            
                               clearTimeout(timer);	
                               window.scroll(0,0);
                               timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);
                            }
                            else
                            {
                               $("#c_info").html(notification);
                               $("#c_info").fadeIn(4000);
                               
                               document.location.href = '/ossim/userlog/opened_sessions.php';    
                            }							  
                        }    
					 }
				});
			}
			            
            
            $(document).ready(function(){                     
                
                $('#ops_table').dataTable( {
    				"iDisplayLength": 20,
    				"sPaginationType": "full_numbers",
    				"bPaginate": true,
    				"bLengthChange": false,
    				"bFilter": true,
    				"bSort": true,
    				"bInfo": true,
    				"bJQueryUI": true,
    				"aaSorting": [[ 7, "asc" ]],
    				"aoColumns": [
    					{ "bSortable": true },
    					{ "bSortable": true },
    					{ "bSortable": true },
    					{ "bSortable": true },
    					{ "bSortable": true },
    					{ "bSortable": true },
    					{ "bSortable": false }
    				],
    				oLanguage : {
    					"sProcessing": "<?php echo _('Processing') ?>...",
    					"sLengthMenu": "Show _MENU_ entries",
    					"sZeroRecords": "<?php echo _('No users found') ?>",
    					"sEmptyTable": "<?php echo _('No active sessions found') ?>",
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
    				},
    				"fnDrawCallback": function( oSettings ) 
					{  
                        $(".info_agent").tipTip({content: $(this).attr('title'), maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
                        $(".info_logout").tipTip({content: $(this).attr('title'), maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
    				},
					"fnInitComplete": function( oSettings ) 
					{  
						var _reload = "<div id='r_ul'><a href='opened_sessions.php'><img src='../pixmaps/refresh2.png' title='<?php echo _('Refresh');?>'/></a></div>";
						$('.dt_header').prepend(_reload);
    				}
    			});
            });
			
		</script>
		
        <style type="text/css">
           #r_ul
           {
               padding-top: 12px;
               padding-left: 0px;           
           }
        </style>
	</head>
	
	<body>
		
	<div id='container_center'>
	
	    <div id='container_c_info'><div id='c_info'></div></div>   
												  
		<div id='user_al'>		
									
    		<table id='ops_table' class='table_data'>
    			<thead>
    				<tr>
    					<th><?php echo _('Username')?></th>
    					<th><?php echo _('IP Address')?></th>
    					<th><?php echo _('Hostname')?></th>
    					<th><?php echo _('Agent')?></th>
    					<th><?php echo _('Logon')?></th>
    					<th><?php echo _('Last activity')?></th>
    					<th><?php echo _('Actions')?></th>
    				</tr>
    			</thead>
    						
    			<tbody>
    			<?php    				
				if (!empty($allowed_users) && is_array($allowed_users))
				{
					foreach ($allowed_users as $user)
					{
						$id_hashed = Util::encrypt($user->get_id(),  Util::get_system_uuid());

						if ($user->get_id() == $my_session)
						{
							$me = "style='font-weight: bold;'";
							
							$action = "<img class='info_logout dis_logout' src='../pixmaps/menu/logout.gif' alt='".$user->get_login()."' title='".$user->get_login()."'/>";
						}
						else
						{
							$action = "<a onclick=\"logout('".$id_hashed."');\">
							             <img class='info_logout' src='../pixmaps/menu/logout.gif' alt='"._('Logout')." ".$user->get_login()."' title='"._('Logout')." ".$user->get_login()."'/>
							           </a>";	
							
							$me = NULL;
						}						
						
						$_country_aux   = $geoloc->get_country_by_host($conn, $user->get_ip());
						$s_country      = strtolower($_country_aux[0]);
						$s_country_name = $_country_aux[1];
						
						$geo_code       = get_country($s_country);
						$flag           = (!empty($geo_code)) ?  "<img src='".$geo_code."' border='0' align='top'/>" : '';

						$logon_date     = gmdate('Y-m-d H:i:s', Util::get_utc_unixtime($user->get_logon_date())+(3600*Util::get_timezone()));
						$activity_date  = Util::get_utc_unixtime($user->get_activity());
						
						$background     = (Session_activity::is_expired($activity_date)) ? 'background:#FFD8D6;' : '';	
						$expired        = (Session_activity::is_expired($activity_date)) ? "<span style='color:red'>("._('Expired').")</span>" : "";
						$agent          = explode('###', $user->get_agent());

						if ($agent[1] == 'av report scheduler') 
						{
							$agent = array('AV Report Scheduler','wget');
						}
						
						$host = @array_shift(Asset_host::get_name_by_ip($conn, $user->get_ip()));						
						$host = ($host == '') ? $user->get_ip() : $host;
						echo "  <tr id='".$id_hashed."'>
									<td class='ops_user' $me><img class='user_icon' src='".get_user_icon($user->get_login(), $pro)."' alt='"._('User icon')."' title='"._('User icon')."' align='absmiddle'/> ".$user->get_login()."</td>
									<td class='ops_ip'>".$user->get_ip()."</td>
									<td class='ops_host'>".$host.$flag."</td>

									<td class='ops_agent'><a title='".Util::htmlentities(strip_tags($agent[1]))."' class='info_agent'>".Util::htmlentities($agent[0])."</a></td>
									<td class='ops_logon'>".$logon_date." $expired</td>
									<td class='ops_activity'>"._(TimeAgo($activity_date, gmdate('U')))."</td>
									<td class='ops_actions'>$action</td>	
								</tr>";
				    }
				}
                ?>
    			</tbody>
    		</table>
		</div>				
    </div>
    
    </body>
</html>

<?php
$db->close();
$geoloc->close();
?>