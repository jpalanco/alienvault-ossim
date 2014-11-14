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


require_once (dirname(__FILE__) . '/../../conf/config.inc');

Session::logcheck('environment-menu', 'EventsHidsConfig');

unset($_SESSION['_ossec_os_type']);

$_POST = $_GET;
$_POST['agent_ip'] = base64_decode($_POST['agent_ip']);

$agent_ip  = POST('agent_ip');
$type      = POST('os_type');
$sensor_id = POST('sensor_id');


$validate = array (
    'sensor_id'   => array('validation' => "OSS_HEX",              'e_message' => 'illegal:' . _('Sensor ID')),
    'agent_ip'    => array('validation' => 'OSS_IP_CIDR_0',        'e_message' => 'illegal:' . _('Agent IP')),
    'os_type'     => array('validation' => "'regex:unix|windows'", 'e_message' => 'illegal:' . _('OS Type'))
);


$validation_errors = validate_form_fields('POST', $validate);



//Get Sensor IP for selected sensor
if (empty($validation_errors))
{
    $db   = new ossim_db();
    $conn = $db->connect();

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $validation_errors["sensor_id"] = _("Sensor not allowed");
    }
    else
    {
        $system_ids = Av_center::get_system_id_by_component($conn, $sensor_id);

        $res = Av_center::get_system_info_by_id($conn, $system_ids['non-canonical']);

        if ($res['status'] == 'success')
        {
            if (!empty($res['data']['vpn_ip']) && Ossec_utilities::get_default_sensor_id() != $sensor_id)
            {
                $sensor_ip     = $res['data']['vpn_ip'];
                $sensor_ip_txt = $res['data']['admin_ip']." [".$res['data']['vpn_ip']."]";
            }
            else
            {
                $sensor_ip     = $res['data']['admin_ip'];
                $sensor_ip_txt = $sensor_ip;
            }
            
        }

        $_SESSION['_ossec_os_type'] = $type;
    }

    $db->close();
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>	

	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	<script type="text/javascript" src="/ossim/js/ajax_validator.js"></script>
	<script type="text/javascript" src="/ossim/js/messages.php"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>

	<!-- Jquery Elastic Source: -->
	<script type="text/javascript" src="/ossim/js/jquery.elastic.source.js" charset="utf-8"></script>
		
	<!-- Greybox: -->
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>	

    <!-- JQuery tipTip: -->
    <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>

	<script type="text/javascript" src="/ossim/ossec/js/ossec_msg.php"></script>
	<script type="text/javascript" src="/ossim/ossec/js/common.js"></script>
	<script type="text/javascript" src="/ossim/ossec/js/agents.js"></script>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>	
	
	<script type="text/javascript">

		var timer = null;

		$(document).ready(function() {
			var config = {   
                validation_type: 'complete', // single|complete
                errors:{
                	display_errors: 'all', //  all | summary | field-errors
                	display_in: 'c_info'
                },
                form : {
                	id  : 'form_a_deployment',
                	url : 'ajax/a_deployment.php'
                },
                actions: {
                	on_submit:{
                		id: 'send',
                		success:  av_messages['submit_checking'],
                		checking: av_messages['submit_text']
                	}
                }
            };
                
            ajax_validator = new Ajax_validator(config);

            $('#send').off('click')
            $('#send').click(function() 
            {
                if (ajax_validator.check_form() == true)
                {
                    deployment_agent();
                }
            });
			
			Token.add_to_forms();
		});

	</script>


	<style type='text/css'>
        .dis_input
        {
            filter:alpha(opacity=50);
            -moz-opacity:0.5;
            -khtml-opacity: 0.5;
            opacity: 0.5;
            font-style: italic;
            cursor: default;
        }

        input[type='text'], input[type='hidden'], input[type='password'], select
        {
            width: 98%;
            height: 18px;
        }

        .legend
        {
            font-size: 10px;
            font-style: italic;
            text-align: center;
            padding: 0px 0px 5px 0px;
            margin: 20px auto 5px auto;
            width: 400px;
        }

        #c_deployment
        {
            width: 800px;
            min-height: 400px;
            border: none !important;
            margin: auto;
        }

        #t_a_deployment
        {
            width: 80%;
            border: none !important;
            border-collapse: collapse;
            margin: auto;
        }
        
        #t_a_deployment table
        {
            width: 100%;
            margin: auto;
            border: solid 1px #CCCCCC;
        }
		
        #t_a_deployment #table_container th
        {
            width: 200px;
        }
		
        #container_center
        {
            height: 100%;
            margin: 20px 0px;
            position: relative;
        }

        #c_info
        {
            width: 800px;
            margin: auto;
            min-height: 40px;
        }

        #c_help
        {
            width: 800px;
            margin: 50px auto 0px auto;
            padding-top: 20px;
            text-align: left;
        }
	</style>
	
</head>

<body>



<div id='container_center'>
    
    <?php        
    
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $errors = implode( "<br/>", $validation_errors);
        $errors = str_replace('"', '\"', $errors);
    
    
        $content = "<div style='text-align: left; padding-left:5px;'>"._('We found the following errors').":</div>
                    <div style='padding-left:15px; text-align: left;'>$errors</div>";
        
        
        $config_nt = array(
			'content' => $content,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
			'style'   => 'width: 80%; margin: 50px auto 100px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		
		$nt->show();      
    }
    else
    {
        /*
        echo "<pre>";
            print_r($_POST);
        echo "</pre>";
        */
        ?>
        <div id='c_deployment'>
            
            <div id='c_info'></div>
            
            <div id='c_help'></div>
            
            <div class="legend">
                <?php echo _('Values marked with (*) are mandatory');?>
            </div>	
        
            <table id='t_a_deployment'>
    			<tr>
    				<td class="nobborder" valign="top">
    					<form id='form_a_deployment' name='form_a_deployment' method="POST">
    					
                            <input type="hidden" name="agent_ip"  id="agent_ip"  class='vfield' value="<?php echo $agent_ip?>"/>             
                            <input type="hidden" name="sensor_ip" id="sensor_ip" class='vfield' value="<?php echo $sensor_ip?>"/>
                                                  
                            <table id='table_container'>
                                
                                <tr>
                                    <th>
                                        <label for='sensor_ip_txt'><?php echo _('OSSEC Server IP');?></label>
                                    </th>
                                    <td class="left">
                                        <input type="text" class='dis_input' disabled='disabled' readonly='readonly' name="sensor_ip_txt" id="sensor_ip_txt" value="<?php echo $sensor_ip_txt;?>"/>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>
                                        <label for='agent_ip_txt'><?php echo _('Agent IP');?></label>
                                    </th>
                                    <td class="left">
                                        <input type="text" class='dis_input' disabled='disabled' readonly='readonly' name="agent_ip_txt" id="agent_ip_txt" value="<?php echo $agent_ip;?>"/>
                                    </td>
                                </tr>
                                
                                
                                <?php 
                                if ($type == 'windows')
                                {
                                    ?>                         
                                    <tr>
                                        <th>
                                            <label for='domain'><?php echo _('Domain')?></label>
                                        </th>
                                        <td class="left">
                                            <input type="text" class='info vfield' name="domain" id="domain" value="<?php echo $domain;?>"/>
                                        </td>
                                    </tr>
                                
                                    <tr>
                                        <th>
                                            <label for='user'><?php echo _('User') . required();?></label>
                                        </th>
                                        <td class="left">
                                            <input type="text" class='vfield' name="user" id="user"/>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th>
                                            <label for='pass'><?php echo _('Password') . required();?></label>
                                        </th>
                                        <td class="left">
                                            <input type="password" class='vfield' name="pass" id="pass" autocomplete="off"/>
                                        </td>
                                    </tr>
                                    
                                    <?php
                                }
                                ?>
                                                            
                                <tr>
        							<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
            						     <input type="button" id='send' value="<?php echo _('Save')?>"/>  								
        							</td>
        						</tr>    						                          
                            </table>					
    					</form>			
    				</td>
    			</tr>
    	    </table>
	    
	    </div>            
        <?php               
    }       
    ?> 
</div>

</body>
</html>