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

Session::logcheck("environment-menu", "EventsVulnerabilitiesScan");

$db      = new ossim_db();
$dbconn  = $db->connect();

$data       = REQUEST('credential');
$host_id_ip = ( POST('host_id_ip')!= "" ) ? POST('host_id_ip') : POST('searchBox');

list($name, $login) = explode(";", $data);

ossim_valid($name,  OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_DOT, OSS_AT, 'illegal:' . _("Name"));

$login_text = $login;

if ( $login != '0' )
{
	if ( !ossim_valid($login, OSS_HEX, 'illegal:' . _("Entity")) ) 
	{
		ossim_clean_error();
		ossim_valid($login, OSS_USER_2, 'illegal:' . _("login"));
	}
	else{
		$login_text = Session::get_entity_name($dbconn, $login);
	}
}
else{
	$login_text = _("All");	
}


if( preg_match("/#/", $host_id_ip) ) 
{
    list($host_id, $host_ip) = explode("#", $host_id_ip);
    ossim_valid($host_ip, OSS_IP_ADDR,  'illegal:' . _("Host IP"));
    ossim_valid($host_id, OSS_HEX,      'illegal:' . _("Host ID"));
}
else{ // only IP
    ossim_valid($host_id_ip, OSS_NULLABLE, OSS_IP_ADDR, 'illegal:' . _("Host IP"));
}

if ( ossim_error() ) {
    die(ossim_error());
}


$results = array();

//Check if it is allowed
$allowed = Vulnerabilities::is_allowed_credential($dbconn, $name, $login);

if( $allowed ) 
{
    //Autocomplete data
    $_hosts_data = Asset_host::get_basic_list($dbconn);
    $_hosts      = $_hosts_data[1];

    foreach ($_hosts as $_host_id => $_host_detail)
    {
        // get host IPs
        $hIPs = array();
        $hIPs = explode(",", trim($_host_detail['ips'])); 
        
		foreach($hIPs as $hIP)
		{
            $hIP = trim($hIP);
            $hosts .= '{ txt:"' . $_host_detail['name'] . ' (' . $hIP . ')", id: "'. $_host_id . '#' . $hIP . '" },';
        }
    }
}

//Check credentials
if( $host_id_ip != "" ){
    $results = Vulnerabilities::check_credential($dbconn, $host_id_ip, $name, $login);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
    <title><?php echo gettext("Vulnmeter Credentials");?></title>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>

    <?php
    if( $allowed ) 
	{ 
		?>
        <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
        <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
        <script type="text/javascript">
            // Autocomplete assets
            $(document).ready(function(){
                var assets = [ <?php echo preg_replace("/,$/", "", $hosts); ?> ];
                
                $("#searchBox").autocomplete(assets, {
                    minChars: 0,
                    width: 300,
                    max: 100,
                    matchContains: true,
                    autoFill: true,
                    formatItem: function(row, i, max) {
                        return row.txt;
                    }
                }).result(function(event, item) {
                    $("#host_id_ip").val(item.id);
                });
                
                $('#check_credential').submit(function() {
                    
                    $(".results").remove();
					$("#c_info").html('');
					
					var IP_regexp = /\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/
                    var searchBox = $("#searchBox").val();
															
                    if ( searchBox == '' || searchBox.search(IP_regexp) == -1 ) // Fail
					{ 
                        var error_msg = ( searchBox == '' ) ?  '<?php echo _("Error! You need to select a host");?>' : '<?php echo _("Error! IP entered is invalid")?>';
						
						var config_nt = { content: error_msg, 
							  options: {
								type:'nf_error',
								cancel_button: false
							  },
							  style: 'width: 100%; position: absolute; text-align:center; margin: 5px auto'
							};
			
						nt            = new Notification('nt_1', config_nt);
						notification  = nt.show();
						
						$('#c_info').html(notification);
						
						nt.fade_in(2000, '', '');
						setTimeout('nt.fade_out(4000, "", "");', 10000);
						
						$("#searchBox").val('');
						$("#host_id_ip").val('');
						
						return false;
					}					
                    $("#wait").show();
					$("#submit").val('<?php echo _("Checking...") ?>');
                    
                    return true;
                });
                
            });
        </script>
		<?php
    }
    ?>
    <style type='text/css'>

		#c_info{
			width: 80%;
			position: relative;
			height: 1px;
			margin: 5px auto;
		}
		.mainTableHeader {
            margin: 40px auto 0px auto;
            width: 80%;
        }
		.mainTableContent {
            margin: 0px auto;
            width: 80%;
        }
        .resultsTableHeader {
            margin: 10px auto 0px auto;
            width: 80%;
        }
        .resultsTableContent {
            margin: 0px auto;
            width: 80%;
        }

        .padding-tb {
            padding: 10px 0px 5px 0px;
        }
        .error_padding {
            padding: 10px 0px 10px 0px;
        }
        .td_padding {
            padding: 3px 0px;
        }  
    </style>
</head>
<body>

<?php
if( $allowed ) 
{
	?>
	
	<div id='c_info'></div>
	
    <form action="check_credential.php" id="check_credential" method="POST" >
        <input type="hidden" name="credential" value="<?php echo $name.';'.$login ?>"/>
        
		<table class="mainTableHeader transparent" cellpadding="0" cellspacing="0">
            <tr>
                <td class="headerpr_no_bborder"><?php echo "$name ($login_text)" ?></td>
            </tr>
        </table>
        <table class="mainTableContent">
            <tr>
                <td class="padding-tb">
                    <input id="searchBox" name="searchBox" type="text" style="width:200px;" placeholder="<?php echo _("Type here to search the host...")?>" />
                    <input type="hidden" name="host_id_ip" id="host_id_ip" />
                </td>
            </tr>
            <tr>
                <td class="padding-tb">
                    <table class="transparent" width="100%">
                        <tr>
                            <td width="55%" style="text-align:right;">
                                <input type="submit" id="submit" value="<?php echo _("Check"); ?>">
                            <td>
                            <td width="44%" style="text-align:left;">
                                <img id="wait" style="display:none;" title="<?php echo _("Please, wait a few seconds")?>" style="cursor:wait" src="../pixmaps/loading3.gif" />
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </form>
    
    <?php
    if( !empty($results) ) 
	{ 
		?>
        <table cellspacing="0" class="resultsTableHeader transparent">
            <tr><td class="sec_title"><?php echo _("Credential Check Results") ?></td></tr>
        </table>
		
        <table class="resultsTableContent table_list" cellspacing="0">
			<?php
			if( $results[0]["type"] != "error" ) 
			{
				?>
				<tr>
					<th><?php echo _("Message")?></th>
					<th><?php echo _("Sensor")?></th>
					<th> <?php echo _("Status");?></th>
				</tr>
				<?php
			}
			else 
			{
				?>
				<tr>
					<td class="error_padding"><?php echo $results[0]["message"];?></td>
				</tr>
				<?php
			}
			
			foreach($results as $result) 
			{
				if( $result["type"]== "test_ko" || $result["type"]=="test_ok" ) 
				{
					?>
					<tr>
						<td><?php echo $result["message"];?></td>
						<td><?php echo $result["sensor"];?></td>
						<td><?php echo $result["status"];?></td>
					</tr>
					<?php
				}
			}
			?>
		</table>
		<?php
    }
}
else 
{
    $config_nt = array(
            'content' => _("Credential not allowed"),
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => false
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: center;'); 
                        
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
}

$dbconn->disconnect();
?>
<br/><br/><br/>
</body>
</html>
