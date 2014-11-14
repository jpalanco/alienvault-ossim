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


//
// $Id: sched.php,v 1.17 2010/04/21 15:22:39 josedejoses Exp $
//

/***********************************************************/
/*                    Inprotect                            */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                            */
/*                                                         */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                                    */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                       */
/*                                                         */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                    */
/*                                                         */
/* Contact Information:                                    */
/* inprotect-devel@lists.sourceforge.net                   */
/* http://inprotect.sourceforge.net/                       */
/***********************************************************/
/* See the README.txt and/or help files for more           */
/* information on how to use & config.                     */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                                         */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items         */
/* discovered with this program's use.                     */
/***********************************************************/

require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';

Session::logcheck("environment-menu", "EventsVulnerabilitiesScan");

define("EXCLUDING_IP",  "^!(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$");
define("EXCLUDING_IP2", "!(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])");

$db   = new ossim_db();
$conn = $db->connect();

// key to display asset tree

if(Session::is_pro()) {
    $keytree = "assets|entitiesassets";
}
else {
    $keytree = "assets";
}

$force           = FALSE;

$asset_to_select = array();
$_hosts          = array();
$hosts           = "";

$autocomplete_keys = array('hosts', 'nets');
$assets            = Autocomplete::get_autocomplete($conn, $autocomplete_keys);

$back_url          = (preg_match("/manage_jobs/", $_SERVER['HTTP_REFERER'])) ? "manage_jobs.php" : Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/index.php', 'environment', 'vulnerabilities','overview');

$myhostname="";

$getParams = array( 'disp', 'vuln_op', 'rid', 'sname', 'notify_email', 'tarSel', 'targets', 'ip_start',
                    'ip_end', 'named_list', 'subnets',  'schedule_type', 'cred_type', 'job_id', 'sched_id','hosts_alive','scan_locally','smethod', 'net_id'
                   );


$postParams = array( 'disp','vuln_op', 'rid', 'sname', 'notify_email', 'schedule_type', 'ROYEAR', 'ROMONTH', 'ROday',
                    'time_hour', 'time_min', 'dayofweek', 'dayofmonth', 'timeout', 'SVRid', 'sid', 'tarSel',
                     'targets', 'ip_start', 'ip_end', 'named_list', 'subnet', 'system', 'cred_type', 'credid', 'acc',
                     'domain', 'accpass', 'acctype', 'passtype', 'passstore', 'job_id','wpolicies', 'wfpolicies', 
                     'upolicies', 'cidr', 'custadd_type', 'cust_plugins', 'sched_id', 'is_enabled', 'submit', 'process',
                     'isvm', 'sen', 'hostlist', 'pluginlist','user','entity','hosts_alive','scan_locally','nthweekday', 'nthdayofweek', 'time_interval',
                     'biyear', 'bimonth', 'biday', 'not_resolve', 'semail', 'ssh_credential', 'smb_credential');

 $daysMap = array ( 
     "0" => "NONE", 
    "Su" => "Sunday",
    "Mo" => "Monday",
    "Tu" => "Tuesday",
    "We" => "Wednesday",
    "Th" => "Thursday",
    "Fr" => "Friday", 
    "Sa" => "Saturday"
          );
 $wdaysMap = array ( 
    "Su" => "0",
    "Mo" => "1",
    "Tu" => "2",
    "We" => "3",
    "Th" => "4",
    "Fr" => "5", 
    "Sa" => "6"
          );        
          
$schedOptions = array( "N" => "Immediately",
                     "O" => "Run Once", 
                     "D" => "Daily", 
                     "W" => "Weekly", 
                     "M" => "Monthly" );

$pluginOptions = array( "N" => "No Additional Plugins",
                     "A" => "In Addition to ( selected Profile Plugins)", 
                     "R" => "In Replace of ( selected Profile Plugins)" );

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach ($getParams as $gp) {
      if (isset($_GET[$gp])) { 
         if(is_array($_GET[$gp])) {
            foreach ($_GET[$gp] as $i=>$tmp) {
               ${$gp}[$i] = sanitize($tmp);
            }
         } else {
            $$gp = sanitize($_GET[$gp]);
         }
      } else { 
         $$gp=""; 
      }
   }
   break;
case "POST" :
//   echo "<pre>"; print_r($_POST); echo "</pre>";
   foreach ($postParams as $pp) {
      if (isset($_POST[$pp])) { 
         if(is_array($_POST[$pp])) {
            foreach($_POST[$pp] as $i=>$tmp) {
               ${$pp}[$i] = sanitize($tmp);
            }
         } else {
            $$pp = sanitize($_POST[$pp]);
         }
      } else { 
         $$pp=""; 
      }
   }
   break;
}

$save_scan = (intval($_POST['save_scan'])==1) ? TRUE : FALSE;

if ($schedule_type=="NW") {
    $dayofweek = $nthdayofweek;
}

$error_message="";

if ($sname=="") {
    $error_message .= _("Invalid Job name")."<br/>";
    $force          = TRUE;
}

if ($timeout=="") {
    $error_message .= _("Invalid Timeout")."<br/>";
}

ossim_valid(html_entity_decode($sname), OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("Job name"));
if (ossim_error()) {
    $force = TRUE;
    $error_message .= _("Invalid Job name")."<br/>";
}
ossim_set_error(false);
ossim_valid($entity, OSS_NULLABLE, OSS_HEX, 'illegal:' . _("Entity"));
if (ossim_error()) {
    $error_message .= _("Invalid entity")."<br/>";
}
ossim_set_error(false);
ossim_valid($net_id, OSS_NULLABLE, OSS_HEX, 'illegal:' . _("Net ID"));
if (ossim_error()) {
    $error_message .= _("Invalid Net ID")."<br/>";
}

ossim_set_error(false);
ossim_valid($user, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, '\.', 'illegal:' . _("User"));
if (ossim_error()) {
    $error_message .= _("Invalid user")."<br/>";
}

ossim_set_error(false);
ossim_valid($timeout, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Timeout"));
if (ossim_error()) {
    $error_message .= _("Invalid timeout")."<br/>";
}

$ip_exceptions_list = array();
$tip_target         = array();

if(empty($targets)) { $targets = array(); }

foreach($targets as $target) {
    $target_error = false;
    $target = trim($target);
    
    if (preg_match("/^\!\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d+)?$/",$target)) {
        $ip_exceptions_list[] = $target;
    }
    else if(!preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d+)?$/",$target)) {
        ossim_valid($target, OSS_FQDNS , 'illegal: Host name'); // asset id

        if (ossim_error()) {
            $target_error   = true;
            $error_message .= _("Invalid asset id").": $asset_id<br/>";
        }
        else {
            $tip_target[] = $target;
        }
    }
    else if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$/", $target)){
        $tip_target[] = $target;
    }
    else {
        list($asset_id, $ip_target) = explode("#", $target);
            
        ossim_set_error(false);
        ossim_valid($asset_id, OSS_HEX, OSS_NULLABLE , 'illegal: Asset id'); // asset id
        
        if (ossim_error()) {
            $target_error   = true;
            $error_message .= _("Invalid asset id").": $asset_id<br/>";
        }

        ossim_set_error(false);
        ossim_valid($ip_target, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, '\.\,\/\!', 'illegal:' . _("Target"));
        if (ossim_error()) {
            $target_error   = true;
            $error_message .= _("Invalid target").": $ip_target<br/>";
        }
        if(!$target_error) {
            $tip_target[] = str_replace("!","",$target);
        }
    }
}

$ip_list = $tip_target;

if (count($tip_target)==0) { // validated targets
    $error_message .= _("Invalid Targets")."<br/>";
}

$hosts_alive  = intval($hosts_alive);
$scan_locally = intval($scan_locally);
$not_resolve  = intval($not_resolve);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("Vulnmeter"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.autocomplete.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tree.css" />
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="/ossim/js/combos.js"></script>
	<script type="text/javascript" src="/ossim/js/vulnmeter.js"></script>
    <script type="text/javascript" src="/ossim/js/notification.js"></script>
	<?php require ("../host_report_menu.php") ?>
	<script type="text/javascript">
		function postload() {
		
		    _excluding_ip = /<?php echo EXCLUDING_IP; ?>/;
		
			var filter      = "";
			var length_name = 45;
			
			$("#vtree").dynatree(
			{
                initAjax: { url: "../tree.php?key=<?php echo $keytree ?>" },
                clickFolderMode: 2,
                onActivate: function(dtnode) 
                {
                    if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined') 
                    {
                        var Regexp     = /.*_(\w+)/;
                        var match      = Regexp.exec(dtnode.data.key);
                        var id         = "";
                        var asset_name = "";
                    
                        id = match[1];

                        Regexp = /^(.*)\s*\(/;
	                    match  = Regexp.exec(dtnode.data.val);
	                            
	                    asset_name = match[1];
                        
                        // Split for multiple IP/CIDR
						var keys = dtnode.data.val.split(",");

						for (var i = 0; i < keys.length; i++) 
						{
							var item   = keys[i];
							var value  = "";
	                        var text   = "";
	                        
	                        if (item.match(/\d+\.\d+\.\d+\.\d+\/\d+/) !== null) // net
	                        { 
	                            Regexp = /(\d+\.\d+\.\d+\.\d+\/\d+)/;
	                            match  = Regexp.exec(item);
	                            
	                            value = id + "#" + match[1];
	                            text  = asset_name + " (" + match[1] + ")";
	                        }
	                        else if (item.match(/\d+\.\d+\.\d+\.\d+/) !== null) // host
	                        { 
	                            Regexp = /(\d+\.\d+\.\d+\.\d+)/;
	                            match  = Regexp.exec(item);
	                            
	                            value = id + "#" + match[1];
	                            text  = asset_name + " (" + match[1] + ")";
	                        }
	                     
	                        if(value != '' && text != '' && !exists_in_combo('targets', text, value, true))
	                        {
	                            addto ("targets", text, value);
	                        }
						}
						
						simulation();
						
						dtnode.deactivate()
						
                    }
                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode)
                {
                    dtnode.appendAjax(
                    {
                        url: "../tree.php",
                        data: {key: dtnode.data.key, page: dtnode.data.page}
                    });
                }
			});
			
            $("#delete_all").on( "click", function() {
                $("#hosts_alive").attr('disabled', false);
                
                $("#scan_locally").attr('disabled', false);
            
                selectall('targets');
                
                deletefrom('targets');
                
                disable_button();
                
                $("#sresult").hide();
                
            });
            
            $("#delete_target").on( "click", function() {
            
                deletefrom('targets');
                
                // check targets to enable host_alive check
                
                var all_targets = getcombotext('targets');
                
                if (all_targets.length == 0)
                {
                    $("#hosts_alive").attr('disabled', false);
                        
                    $("#scan_locally").attr('disabled', false);
                
                    disable_button();
                
                    $("#sresult").hide();
                }
                else
                {
                    var found = false;
                    
                    var i = 0;
                    
                    var num_targets = 0;
                    
                    while (i < all_targets.length && found == false)
                    {
                        if (all_targets[i].match( _excluding_ip ))
                        {
                            found = true;
                        }
                        else
                        {
                            num_targets++;    
                        }
                        
                        i++;
                    }
                    
                    if (found == false)
                    {
                        $("#hosts_alive").attr('disabled', false);
                        
                        $("#scan_locally").attr('disabled', false);
                    }
                    
                    if ( num_targets > 0 )
                    {
                        simulation();    
                    }
                    else
                    {
                        disable_button();
                
                        $("#sresult").hide();
                    }
                }
            }); 
            
            $("#searchBox").click(function() {
                $("#searchBox").removeClass('greyfont');
                $("#searchBox").val('');
                });
            
            $("#searchBox").blur(function() {
                $("#searchBox").addClass('greyfont');
                $("#searchBox").val('<?php echo _("Type here to search assets (Hosts/Networks)")?>');
            });
            
            $('#searchBox').keydown(function(event) {
                if (event.which == 13)
                {
                   var target = $('#searchBox').val().replace(/^\s+|\s+$/g, '');
                   
                   targetRegex   = /\[.*\]/;
                   
                   if( target != '' && !target.match( targetRegex ) && !exists_in_combo('targets', target , target, true) ) // is a valid target?
                   {
                        addto ("targets", target , target );
                                                
                        // force pre-scan

                        if (target.match( _excluding_ip ) )
                        {
                            show_notification('v_info', '<?php echo _('We need to know all network IPs to exclude one of them, so the "Only hosts that are alive" option must be enabled.')?>' , 'nf_info', false, true, 'padding: 3px; width: 80%; margin: 12px auto 12px auto; text-align: center;');
                        
                            $("#hosts_alive").attr('checked', true);
                            $("#hosts_alive").attr('disabled', true);
                            $("#scan_locally").attr('disabled', false);
                        }
                        
                        $("#searchBox").val("");
                        
                        simulation();
                    }
               }
            });

            // Autocomplete assets
            var assets = [ <?php echo $assets ?> ];
            
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
            
            	var keys = item.ip.split(",");

				for (var i = 0; i < keys.length; i++) 
				{
					var ip   = keys[i].replace(/[\s\t\n\r ]+/g,"");
						
					var value  = item.id + "#" + ip;                   
					var text   = item.name + " (" +ip + ")";
					
					if(!exists_in_combo('targets', text, value, true))
					{
					    addto ("targets", text, value);
					}
				}
				simulation();
                $("#searchBox").val("");
                                
            });
			
			$('#scheduleM').change(function() {
				var type = $(this).attr('value');
				var id;
			
				switch(type)
				{
					case "N":
						id = 1;
						break;
					case "O":
						id = 3;
						break;
					case "D":
						id = 2;
						break;
					case "W":
						id = 4;
						break;
					case "M":
						id = 5;
						break;
					case "NW":
						id = 6;
						break;
				}
		    if(id==1) {
				$("#smethodtr").hide();
			}
			else {
				$("#smethodtr").show();
			}
			showLayer('idSched', id)
			});
			
			// Confirm new job with a lot of targets
			
			$('#mjob').on("click", function(event){
				if( $('#thosts').html() > 255 && $("#hosts_alive").is(":checked")) {
                    var msg_confirm = '<?php echo Util::js_entities(_("You are about to scan a big number of hosts (#HOSTS# hosts). This scan could take a long time depending on your network and the number of assets that are up, are you sure you want to continue?"))?>';
                    
                    msg_confirm = msg_confirm.replace("#HOSTS#", $('#thosts').html());
                                             
                    var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
                                    
                    av_confirm(msg_confirm, keys).fail(function(){
                        return false; 
                    }).done(function(){
                        $('#msgform').submit(); 
                    });
				}
				else {
				    $("#hosts_alive").attr('disabled', false);
				    
					$('#msgform').submit();
				}
  			});
			
			$('.section').click(function() { 
				var id = $(this).find('img').attr('id');
				
				toggle_section(id);
			});
            
            $('#SVRid').change(function() {
                simulation();
            });
            
            $('#scan_locally').click(function() { 
                simulation();
			});
            $('#not_resolve').click(function() {
                simulation();
			});
            
            <?php

            if(((GET("disp") == "edit_sched" || GET("disp") == "rerun" || !empty($ip_list) || GET("net_id") != "") && !$save_scan) || $force)
            {
                ?> 
                simulation();
                <?php
            }
            ?>
            
            
            $('#ssh_credential, #smb_credential').change(function() {

                var select_name = $(this).attr('name');

                switch_credentials(select_name);
			});

		}
		
		function toggle_section(id){
			var section_id = id.replace('_arrow', '');
			var section    = '.'+section_id;

			if ($(section).is(':visible')){ 
				$('#'+id).attr('src','../pixmaps/arrow_green.gif'); 
			}
			else{ 
				$('#'+id).attr('src','../pixmaps/arrow_green_down.gif');
			}
			$(section).toggle();
		}

		function switch_user(select) {
			if(select=='entity' && $('#entity').val()!=''){
				$('#user').val('');
			}
			else if (select=='user' && $('#user').val()!=''){
				$('#entity').val('');
			}
		}

        function enable_button() 
        {            
            $("#mjob").removeAttr("disabled");
        }

	    function toggle_scan_locally(){
            if($("#hosts_alive").is(":checked")) {
                $("#scan_locally").removeAttr("disabled");
            }
            else {
                if($("#scan_locally").is(":checked")) {
                    $('#scan_locally').trigger('click');
                }

                $("#scan_locally").attr("disabled","disabled");
            }
            
            simulation();
        }
        var flag = 0;
        
		function simulation() {
            $('#sresult').html('');
            
            selectall('targets');
            
            var stargets = getselectedcombovalue('targets');
            
            var num_targets = 0;
            
            for (var i=0; i<stargets.length; i++)
            {   
                if (stargets[i].match( _excluding_ip ) == null)
                {       
                    num_targets++;  
                }
            }

		   if( !flag  && num_targets > 0 ) {
                if (num_targets > 0) 
				{
                    var targets = $('#targets').val().join(',');
                    disable_button();
                    $('#loading').show();
                    flag = 1;
                    $.ajax({
                        type: "POST",
                        url: "simulate.php",
                        data: { 
                            hosts_alive: $('input[name=hosts_alive]').is(':checked') ? 1 : 0,
                            scan_locally: $('input[name=scan_locally]').is(':checked') ? 1 : 0,
                            not_resolve: $('input[name=not_resolve]').is(':checked') ? 1 : 0,
                            scan_server: $('select[name=SVRid]').val(),
                            targets: targets
                        },
                        success: function(msg) {
                            $('#loading').hide();
                            var data = msg.split("|");
                            $('#sresult').html("");
                            $('#sresult').html(data[0]);
                            $('#sresult').show();
                                                                                
                            if(data[1]=="1") {
                                enable_button();
                            }
                            
                            //var h = document.body.scrollHeight || 1000000;window.scrollTo(0,document.body.scrollHeight);
                            //window.scrollTo(0,h);
                            flag = 0;
                        },
                        error: function (request, status, error) {
                            flag = 0;
                        }
                    });
                }
				else {
                    alert("<?php echo Util::js_entities(_("At least one target needed!"))?>");
                }
            }
            //else { alert("already running");}
		}
        
		function disable_button() 
		{                       
			$("#mjob").attr("disabled","disabled");
        }


        function switch_credentials(select_name)
        {
            if (select_name == 'ssh_credential')
            {
                if ($('#ssh_credential').val() != '')
                {
                    $('#smb_credential').val('');
                    $('#smb_credential').prop('disabled', true);
                    $('#ssh_credential').prop('disabled', false);
                }
                else
                {
                    $('#smb_credential').prop('disabled', false);
                }
            }
            else if (select_name == 'smb_credential')
            {
                if($('#smb_credential').val() != '')
                {
                    $('#ssh_credential').val('');
                    $('#ssh_credential').prop('disabled', true);
                    $('#smb_credential').prop('disabled', false);
                }
                else
                {
                    $('#ssh_credential').prop('disabled', false);
                }
            }
        }
	</script>
	
	<style type='text/css'>
		#user,#entity { width: 220px;}        
        
        .c_back_button {
            display: block;
            top:10px;
            left:10px;
        }
       
        .greyfont{
            color: #666666;
        }
        #targets {
            width:300px;
            height:200px;
        }
		#searchBox {
			width:298px;
		}
		.advanced {display: none;}
		.job_option {
			text-align:left;
			padding: 0px 0px 0px 70px;
		}
		.madvanced {
			text-align:left;
			padding: 0px 0px 4px 59px;
		}
		#user, #entity {
			width: 159px;
		}
	</style>
</head>

<body>

<div id='v_info'>
</div>

<?php

$pageTitle = "Nessus Scan Schedule";

//echo "<pre>";
//print_r($hosts);
//echo $hosts;
//print_r($postParams);
//echo "</pre>";

global $dbconn, $username;

$query = "select count(*) from vuln_nessus_plugins";

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$result=$dbconn->execute($query);
list($pluginscount)=$result->fields;

if ($pluginscount==0) {
   logAccess( "NO PLUGINS IN THE DB - USER NEED LAUNCH UPDATEPLUGINS" );

   die ("<h2>Please run updateplugins.pl script first before using web interface.</h2>");
}

$component = getComponent( $username );  

function main_page( $job_id, $vuln_op ){
   
	global  $editdata, $scheduler, $defaultVSet, $credAudit, $enComplianceChecks, $profileid, $isvm, $sen, $hostlist, $pluginlist,
           $timeout, $uroles, $username, $useremail, $dbconn, $disp,
	   $enDetailedScanRequest, $enScanRequestImmediate, $enScanRequestRecur, $smethod, $back_url;

    $query = "SELECT pn_email, defProfile 
               FROM vuln_users 
	       WHERE pn_uname='$username' LIMIT 1";
	       
     $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
     
     $result=$dbconn->execute($query);
     list($useremail, $user_defsid )=$result->fields;

     $request = "";

     if ( $isvm != "" && $hostlist != "" ) {
     	$editdata['name'] = "ISVM SCAN - $isvm";
     	$editdata['meth_TARGET'] = str_replace( "&lt;br&gt;", "\n" , $hostlist );
     	$editdata['meth_CPLUGINS'] = str_replace( "&lt;br&gt;", "\n" , $pluginlist );
     }
     if ( $sen != "" && $hostlist != "" ) {
     	$editdata['name'] = "INVESTIGATE SCAN - $sen";
     	$editdata['meth_TARGET'] = str_replace( "&lt;br&gt;", "\n" , $hostlist );
     	$editdata['meth_CPLUGINS'] = str_replace( "&lt;br&gt;", "\n" , $pluginlist );
     }     
     
     
     if ( $vuln_op == "reoccuring" ) {
        $scheduler = "1";
        $title = "Create Recurring Job";
        $txt_submit = _("New Job");
     } elseif ( $vuln_op == "editreocurring" ) {
        $scheduler = "1";
        $title = "Edit Recurring Job";
        $txt_submit = _("Save Changes");
     } else {
     	 $scheduler = "0";
        if ( !($uroles['nessus']) ) {
	   #Users without nessus role can only submit scan request
           $request = " Request";   
        }
        /*if ( $op != "rerun" ) { #ADD SOME CONTROLS AROUND SETTING/SELECTING SOME IMPORTANT DEFAULTS
           if ( is_numeric($user_defsid) && $user_defsid > 0 ) {
           	   $editdata['meth_VSET'] = "$user_defsid";
           }
           if ( is_numeric($credAudit) && $credAudit > 0 ) {
              $editdata['meth_CRED'] = "$credAudit";
           }
        }*/
        if ($disp=="edit_sched")
            $title = _("Modify Scan Job$request");
        else
            $title = _("Create Scan Job$request");
        $txt_submit = _("New Job");
     }

    $profileid = $defaultVSet;          #DEFAULT PROFILE

    if($timeout=="") {
        $timeout = "28800"; // 8 horas
    }

echo "<center><table style=\"margin-top:10px;\" class=\"transparent\" width=\"80%\" cellspacing=\"0\" cellpadding=\"0\">";
echo "<tr><td class=\"headerpr_no_bborder\">";
echo "        <div class='c_back_button'>";
echo "	          <input type='button' class='av_b_back' onclick=\"document.location.href='$back_url';return false;\"/>";
echo "	      </div>";
echo "        $title";
echo "</td></tr></table></center>";
echo <<<EOT
<div>
     <form method="post" action="sched.php" name="msgform" id='msgform'>
	 <input type="hidden" name="disp" value="create">
EOT;
     if ( $vuln_op == "editrecurring" ) {
        $sched_id = $editdata['id'];
        echo <<<EOT
     <input type="hidden" name="vuln_op" value="editrecurring">
     <input type="hidden" name="sched_id" value="$sched_id">
EOT;
     }

     $tabs = array( "discovery" => "Target");
     if($uroles['nessus'] || $enDetailedScanRequest) {
        $tabs['settings'] = "Scan";
        $tabs['credentials'] = "Credentials";
        if ($enComplianceChecks ) {
           $tabs['compliance'] = "Compliance";
        }
     }

    echo "<center>".tab_discovery()."</center>";
    ?>
    <center>
    <br />
    
    <?php
if ( $disp=="edit_sched" )
    echo "<input type=\"button\" id=\"mjob\" value=\""._("Update Job")."\" disabled=\"disabled\" />";
else if($smethod=="inmediately")
    echo "<input type=\"button\" id=\"mjob\" value=\""._("Run Now")."\" disabled=\"disabled\" />";
else
    echo "<input type=\"button\" id=\"mjob\" value=\"$txt_submit\" disabled=\"disabled\" />";
?>
    
        <span id="loading" style="display:none;margin:0px 0px 0px 10px;" ><?php echo _("Checking Job...") ?></span>
<?php
   // echo "&nbsp;&nbsp;<input type=\"button\" name=\"simulate\" value=\""._("Simulate")."\" onClick=\"simulation();\" class=\"button\">&nbsp;<span id='ld'></span>";
    echo "<br><br><div id='sresult'></div></center></form></div>";


   require_once 'footer.php';

}

function tab_reporting () {
   $reporting = <<<EOT
<table><tr valign="top"><td>
<table class="noborder" >
        <tr><td>No Advanced / Customized Reporting exists (At this Time).</td>
        </tr>
     </table></td></tr></table>
EOT;
   return $reporting;
}

function tab_discovery () {
	 
	 global $component, $uroles, $editdata, $scheduler, $username, $useremail, $dbconn, $disp,
          $enScanRequestImmediate, $enScanRequestRecur, $timeout, $smethod,$SVRid, $sid, $ip_list, $ip_exceptions_list,
          $schedule_type, $ROYEAR, $ROday, $ROMONTH, $time_hour, $time_min, $dayofweek, $dayofmonth,
          $sname,$user,$entity,$hosts_alive,$scan_locally,$version,$nthweekday,$semail,$not_resolve,$time_interval,$ssh_credential,$smb_credential,$net_id;
          
     global $pluginOptions, $enComplianceChecks, $profileid;
     
     $conf = $GLOBALS["CONF"];
	 
	 $users    = Session::get_users_to_assign($dbconn);
	 $entities_to_assign = Session::get_entities_to_assign($dbconn);
     
     $pre_scan_locally_status = $conf->get_conf("nessus_pre_scan_locally");

     $user_selected = $user;
     $entity_selected = $entity;
          
     $SVRid_selected = $SVRid;
     
     $sid_selected = ($sid!="") ? $sid : $editdata['meth_VSET'];

     $timeout_selected = $editdata["meth_TIMEOUT"];
     $ip_list_selected = str_replace("\\r\\n", "\n", str_replace(";;", "\n", $ip_list));
     if(count($ip_exceptions_list)>0)
        $ip_list_selected .= "\n".implode("\n",$ip_exceptions_list);
     $ROYEAR_selected = $ROYEAR;
     $ROday_selected = $ROday;
     $ROMONTH_selected = $ROMONTH;
     $time_hour_selected = $time_hour;
     $time_min_selected = $time_min;
     $dayofweek_selected = $dayofweek;
     $dayofmonth_selected = $dayofmonth;
     $sname_selected = $sname;

    if(preg_match("/^[a-f\d]{32}$/i", $net_id)) { // Autofill new scan job from deployment
        if (Asset_net::is_in_db($dbconn, $net_id)) {
            $sname_selected  = Asset_net::get_name_by_id($dbconn, $net_id);
            
            $schedule_type   = "M";
            
            $ip_list         = array();

            $nips = explode(",", Asset_net::get_ips_by_id($dbconn, $net_id));
            foreach($nips as $nip) {
                $ip_list []  = $net_id."#".trim($nip);
            }
        }
    }

     if($schedule_type!=""){
        $editdata['schedule_type'] = $schedule_type;
     }

     $cquery_like = "";
     if ( $component != "" ) { $cquery_like = " AND component='$component'"; }      
     
     $today=date("Ymd");
     $tyear=substr($today,0,4);
     $nyear=$tyear+1;
     $tmonth = substr($today,4,2);
     $tday = substr($today,6,2);

     #SET VALUES UP IF EDIT SCHEDULER
     if ( isset($editdata['notify'] )) { $enotify = $editdata['notify']; } else { $enotify = "$useremail"; }
     if ( isset($editdata['time'] )) {
        list( $time_hour, $time_min, $time_sec) = split(':', $editdata['time'] );
        $tz = Util::get_timezone();
        $time_hour = $time_hour + $tz;
    }

     $arrTypes = array( "N", "O", "D", "W", "M" , "NW");
     foreach ( $arrTypes as $type ) {
         $sTYPE[$type] = "";
     }

     $arrJobTypes = array( "C", "M", "R", "S" );
     foreach ( $arrJobTypes as $type ) {
         $sjTYPE[$type] = "";
     }
     
     if ( isset($editdata['schedule_type'] )) {  
        $sTYPE[$editdata['schedule_type']] = "selected='selected'"; 
        if ($editdata['schedule_type']=='D') $ni=2;
        elseif ($editdata['schedule_type']=='O') $ni=3;
        elseif ($editdata['schedule_type']=='W') $ni=4;
        elseif ($editdata['schedule_type']=='NW') $ni=6;
        else $ni=5;
        $show = "<br><script language=javascript>showLayer('idSched', $ni);</script>";
     } else { 
        if($enScanRequestImmediate) {
           $sTYPE['N']  = "selected='selected'";
           $show = "<br><script language=javascript>showLayer('idSched', 1);</script>";
        } else {
           $sTYPE['O'] = "selected='selected'";
           $show = "<br><script language=javascript>showLayer('idSched', 3);</script>";
        }
     }
     
     if($schedule_type!="" ){
        if ($schedule_type=="N") {
             $show .= "<br><script language=javascript>showLayer('idSched', 1);</script>";
            }
        if ($schedule_type=="O") {
             $show .= "<br><script language=javascript>showLayer('idSched', 3);</script>";
            }
        if ($schedule_type=="D") {
             $show .= "<br><script language=javascript>showLayer('idSched', 2);</script>";
            }
        if ($schedule_type=="W") {
             $show .= "<br><script language=javascript>showLayer('idSched', 4);</script>";
            }
        if ($schedule_type=="M") {
             $show .= "<br><script language=javascript>showLayer('idSched', 5);</script>";
            }
        if ($schedule_type=="NW") {
             $show .= "<br><script language=javascript>showLayer('idSched', 6);</script>";
            }
     }

     if ( isset($editdata['job_TYPE'] )) {
        $sjTYPE[$editdata['job_TYPE']] = "SELECTED";
     } ELSE { 
        $sjTYPE['M'] = "SELECTED";
     }

     if ( isset($editdata['day_of_month'] )) { $dayofmonth = $editdata['day_of_month']; }
     if ( isset($editdata['day_of_week'])) { $day[$editdata['day_of_week']] = "SELECTED"; }
     if ($dayofweek_selected!="") { $day[$dayofweek_selected] = "SELECTED";}
     if (!$uroles['nessus']) {
        $name = "sr-" . substr($username,0,6) . "-" . time();
        $name = ($editdata['name'] == "") ? $name : $editdata['name'];
	    $nameout = $name . "<input type=hidden style='width:210px' name='sname' value='$name'>";
     } else {
        $nameout = "<input type=text style='width:210px' name='sname' value='".(($sname_selected!="")? "$sname_selected":"$editdata[name]")."'>";
     }
    $discovery = "<input type=\"hidden\" name=\"save_scan\" value=\"1\">";
    $discovery.= "<input type=\"hidden\" name=\"cred_type\" value=\"N\">";
    $discovery.= "<table width=\"80%\" cellspacing=\"4\">";
    $discovery.= "<tr>";
    $discovery.= "<input type=\"hidden\" name=\"smethod\" value=\"$smethod\">";
    $discovery.= "<td width=\"25%\" class='job_option'>".Util::strong(_("Job Name").":")."</td>";
    $discovery.= "<td style=\"text-align:left;\">$nameout</td>";
    $discovery.= "</tr>";
    
    list($sensor_list, $total) = Av_sensor::get_list($dbconn);         

    $discovery .= "<tr>";
    $discovery .= "<td class='job_option'>".Util::strong(_("Select Server").":")."</td>";
    $discovery .= "<td style='text-align:left;'><select id='SVRid' style='width:212px' name='SVRid'>";
    $discovery .= "<option value='Null'>"._("First Available Server-Distributed")."</option>";

    foreach ($sensor_list as $_sensor_id => $sensor_data)
    {
        if( intval($sensor_data['properties']['has_vuln_scanner']) == 1)
        {
            $discovery .= "<option value=\"$_sensor_id\" ";
          
            if ($editdata['email'] == $_sensor_id || $editdata['scan_ASSIGNED'] == $_sensor_id ) { $discovery .= " SELECTED"; }
      
            if ($SVRid_selected == $_sensor_id) $discovery .= " SELECTED";
      
            $discovery .= ">" . strtoupper($sensor_data['name']) . " [" . $sensor_data['ip'] . "] </option>";
        }
    }

   $discovery .= <<<EOT
      </select>
    </td>
  </tr>
  <tr>
EOT;
    $discovery .="<td class='job_option'>".Util::strong(_("Profile").":")."</td>";
    $discovery .="<td style='text-align:left;'><select name='sid'>";

   $query = "";

    if ($username == "admin" || Session::am_i_admin()) {
        $query = "SELECT distinct(t1.id), t1.name, t1.description 
                 FROM vuln_nessus_settings t1 WHERE deleted='0'
                 ORDER BY t1.name";
    }
    else if( Session::is_pro() )
    {
        $users_and_entities = Acl::get_entities_to_assign($dbconn);  
        
        if ( Acl::am_i_proadmin() ) 
        {
            $users = Acl::get_my_users($dbconn, Session::get_session_user());
            foreach ($users as $us) {
                $users_and_entities[$us->get_login()] = $us->get_login();
            }
            
            $owner_list['0'] = '0';
            $owner_list      = array_keys($users_and_entities);
            $owner_list      = implode("','", $owner_list);
            
            
            $query = "SELECT distinct(t1.id), t1.name, t1.description FROM vuln_nessus_settings t1
                      WHERE deleted = '0' and (name='Default' or owner in ('".$owner_list."')) ORDER BY t1.name";
        }
        else 
        {
            $owner_list['0']       = '0';
            $owner_list[$username] = $username;
            $owner_list            = array_keys($users_and_entities);
            $owner_list[]          = Session::get_session_user();
            $owner_list            = implode("','", $owner_list);
            
            $user_where = "owner in ('".$owner_list."')";
            
            $query = "SELECT distinct(t1.id), t1.name, t1.description FROM vuln_nessus_settings t1
                      WHERE deleted = '0' and (name='Default' or $user_where) ORDER BY t1.name";
        }
    } 
    else 
    {
        $query = "SELECT distinct(t1.id), t1.name, t1.description FROM vuln_nessus_settings t1
                     WHERE deleted = '0' and (name='Default' or owner in ('0','$username')) ORDER BY t1.name";
    }                          
    
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
   $result=$dbconn->execute($query);

   $job_profiles = array();
   $id_found = false;
   $ipr = 0;
   while (!$result->EOF) {
        list($sid, $sname, $sdescription)=$result->fields;
        
        if($sid_selected==$sid) {
            $id_found = true;
        }
        $job_profiles[$ipr]["sid"]           = $sid;
        $job_profiles[$ipr]["sname"]         = $sname;
        $job_profiles[$ipr]["sdescription"]  = $sdescription;

        $ipr++;
        $result->MoveNext();
    }
    
    foreach($job_profiles as $profile_data) {
        
        $sid          = $profile_data["sid"];
        $sname        = $profile_data["sname"];
        $sdescription = $profile_data["sdescription"];
        
        $discovery .= "<option value=\"$sid\" ";
      
        if ( $sid_selected == $sid ){
            if ($sdescription!="")
                $discovery .= "selected>$sname - $sdescription</option>";
            else
                $discovery .= "selected>$sname</option>";
        }
        else {
            if ($sdescription!="")
                $discovery .= ((preg_match("/default/i", $sname) && !$id_found) ? 'selected="selected"': "").">$sname - $sdescription</option>";
            else
                $discovery .= ((preg_match("/default/i", $sname) && !$id_found) ? 'selected="selected"': "").">$sname</option>";
        }
    }
    
    $discovery .="</select>&nbsp;&nbsp;&nbsp<a href=\"".Menu::get_menu_url('settings.php', 'environment', 'vulnerabilities', 'scan_jobs')."\">["._("EDIT PROFILES")."]</a></td>";
    $discovery .="</tr>";
	
        $discovery .= "<tr>";
        $discovery .= "<td class='job_option' style='vertical-align: top;'><div>".Util::strong(_("Schedule Method").":")."</div></td>";
		$discovery .= "<td style='text-align:left'><div><select name='schedule_type' id='scheduleM'>";
        $discovery .= "<option value='N' $sTYPE[N]>"._("Immediately")."</option>";
        $discovery .= "<option value='O' $sTYPE[O]>"._("Run Once")."</option>";
        $discovery .= "<option value='D' $sTYPE[D]>"._("Daily")."</option>";
        $discovery .= "<option value='W' $sTYPE[W]>"._("Day of the Week")."</option>";
        $discovery .= "<option value='M' $sTYPE[M]>"._("Day of the Month")."</option>";
        $discovery .= "<option value='NW' $sTYPE[NW]>"._("N<sup>th</sup> weekday of the month")."</option>";
		$discovery .= "</select></div></tr>";
        
        $smethods = array("O", "D", "W", "M", "NW");

        $smethodtr_display = (in_array($editdata['schedule_type'], $smethods)) ? "" : "style='display:none'";
        $discovery .= "<tr $smethodtr_display id='smethodtr'><td>&nbsp;</td>";
     $discovery .= <<<EOT
    </td>
    <td><div>
      <div id="idSched1" class="forminput">
      </div>
EOT;
     // div to select start day
     $discovery .= "<div id=\"idSched8\" class=\"forminput\">";
     $discovery .= "<table cellspacing=\"2\" cellpadding=\"0\" width=\"100%\">";
     $discovery .= "<tr><th width='35%'>"._("Begin in")."</th><td class='noborder' nowrap='nowrap'>".gettext("Year")."&nbsp;<select name='biyear'>";
     $discovery .= "<option value=\"$tyear\" selected>$tyear</option>";
     $discovery .= "<option value=\"$nyear\" >$nyear</option>";

     $discovery .="</select>&nbsp;&nbsp;&nbsp;".gettext("Month")."&nbsp;<select name='bimonth'>";

     for ($i=1;$i<=12;$i++) {
        $discovery .= "<option value=\"$i\" ";
        if ($i==$tmonth) $discovery .= "selected";
        $discovery .= ">$i</option>";
     }

     $discovery .= "</select>&nbsp;&nbsp;&nbsp;".gettext("Day")."&nbsp;<select name=\"biday\">";
     for ($i=1;$i<=31;$i++) {
        $discovery .= "<option value=\"$i\" ";
        if ($i==$tday) $discovery .= "selected";
         $discovery .= ">$i</option>";
     }
     $discovery .= "</select></td>";
     $discovery .= "</tr>";
     $discovery .= "</table>";
     $discovery .= "</div>";
      
     $discovery .= <<<EOT
      <div id="idSched3" class="forminput">
        <table cellspacing="2" cellpadding="0" width="100%">
EOT;
            $discovery .="<tr><th width='35%'>"._("Day")."</th><td colspan='6' class='noborder' nowrap='nowrap'>".gettext("Year")."&nbsp;<select name='ROYEAR'>";

            $discovery .="<option value=\"$tyear\" ".(($ROYEAR_selected==""||$ROYEAR_selected==$tyear)? "selected" : "").">$tyear</option>";
            $discovery .="<option value=\"$nyear\" ".(($ROYEAR_selected==$nyear)? "selected" : "").">$nyear</option>";

            $discovery .="</select>&nbsp;&nbsp;&nbsp;".gettext("Month")."&nbsp;<select name='ROMONTH'>";

   for ($i=1;$i<=12;$i++) {
      $discovery .= "<option value=\"$i\" ";
      if (($i==$tmonth && $ROMONTH_selected=="") || $ROMONTH_selected==$i) $discovery .= "selected";
      $discovery .= ">$i</option>";
   }
   $discovery .= "</select>&nbsp;&nbsp;&nbsp;".gettext("Day")."&nbsp;<select name=\"ROday\">";
   for ($i=1;$i<=31;$i++) {
      $discovery .= "<option value=\"$i\" ";
      if (($i==$tday && $ROday_selected=="") || $ROday_selected==$i) $discovery .= "selected";
         $discovery .= ">$i</option>";
   }
            $discovery .= <<<EOT
            </select></td>
          </tr>
        </table>
      </div>
      <div id="idSched4" class="forminput" > 
        <table width="100%">
          <tr>
EOT;
            $discovery .= "<th align=\"right\" width=\"35%\">"._("Weekly")."</th><td colspan=\"2\" class=\"noborder\">";
            $discovery .= "<select name=\"dayofweek\">";
            $discovery .= "<option value=\"Su\" SELECTED >".gettext("Select week day to run")."</option>";
            $discovery .= "<option value=\"Su\" $day[Su] >".gettext("Sunday")."</option>";
            $discovery .= "<option value=\"Mo\" $day[Mo] >".gettext("Monday")."</option>";
            $discovery .= "<option value=\"Tu\" $day[Tu] >".gettext("Tuesday")."</option>";
            $discovery .= "<option value=\"We\" $day[We] >".gettext("Wednesday")."</option>";
            $discovery .= "<option value=\"Th\" $day[Th] >".gettext("Thursday")."</option>";
            $discovery .= "<option value=\"Fr\" $day[Fr] >".gettext("Friday")."</option>";
            $discovery .= "<option value=\"Sa\" $day[Sa] >".gettext("Saturday")."</option>";
            $discovery .= "</select>";
            $discovery .= "</td>";
            $discovery .= <<<EOT
          </tr>
        </table>
      </div>
      <div id="idSched5" class="forminput">
        <table width="100%">
          <tr>
EOT;
            $discovery .= "<th width='35%'>".gettext("Select Day")."</td>";
            $discovery .= <<<EOT
            <td colspan="2" class="noborder"><select name="dayofmonth">"
EOT;
   for ($i=1;$i<=31;$i++) {
      $discovery .= "<option value=\"$i\"";
      if (($dayofmonth==$i && $dayofmonth_selected=="") || $dayofmonth_selected==$i) $discovery .= " selected";
      $discovery .= ">$i</option>";
   }

            $discovery .= <<<EOT
            </select></td>
          </tr>
        </table>
      </div>
      <div id="idSched6" class="forminput">
        <table width="100%">
          <tr>
EOT;
            $discovery .= "<th width=\"35%\">".gettext("Day of week")."</th><td colspan=\"2\" class=\"noborder\">";
            $discovery .= "<select name=\"nthdayofweek\">";
            $discovery .= "<option value=\"Su\" SELECTED >".gettext("Select week day to run")."</option>";
            $discovery .= "<option value=\"Su\" $day[Su] >".gettext("Sunday")."</option>";
            $discovery .= "<option value=\"Mo\" $day[Mo] >".gettext("Monday")."</option>";
            $discovery .= "<option value=\"Tu\" $day[Tu] >".gettext("Tuesday")."</option>";
            $discovery .= "<option value=\"We\" $day[We] >".gettext("Wednesday")."</option>";
            $discovery .= "<option value=\"Th\" $day[Th] >".gettext("Thursday")."</option>";
            $discovery .= "<option value=\"Fr\" $day[Fr] >".gettext("Friday")."</option>";
            $discovery .= "<option value=\"Sa\" $day[Sa] >".gettext("Saturday")."</option>";
            $discovery .= "</select>";
            $discovery .= "</td>";
            $discovery .= <<<EOT
          </tr>
        </table>
        <br>
        <table width="100%">
          <tr>
EOT;
                $discovery .="<th align='right'>".gettext("N<sup>th</sup> weekday")."</th><td colspan='2' class='noborder'>";
                $discovery .="<select name='nthweekday'>";
                $discovery .="<option value='1'>".gettext("Select nth weekday to run")."</option>";
                $discovery .="<option value='1'".(($dayofmonth==1) ? " selected":"").">".gettext("First")."</option>";
                $discovery .="<option value='2'".(($dayofmonth==2) ? " selected":"").">".gettext("Second")."</option>";
                $discovery .="<option value='3'".(($dayofmonth==3) ? " selected":"").">".gettext("Third")."</option>";
                $discovery .="<option value='4'".(($dayofmonth==4) ? " selected":"").">".gettext("Fourth")."</option>";
                $discovery .="<option value='5'".(($dayofmonth==5) ? " selected":"").">".gettext("Fifth")."</option>";
                $discovery .="<option value='6'".(($dayofmonth==6) ? " selected":"").">".gettext("Sixth")."</option>"; 
                $discovery .="<option value='7'".(($dayofmonth==7) ? " selected":"").">".gettext("Seventh")."</option>"; 
                $discovery .="<option value='8'".(($dayofmonth==8) ? " selected":"").">".gettext("Eighth")."</option>"; 
                $discovery .="<option value='9'".(($dayofmonth==9) ? " selected":"").">".gettext("Ninth")."</option>";
                $discovery .="<option value='10'".(($dayofmonth==10) ? " selected":"").">".gettext("Tenth")."</option>"; 
            $discovery .= <<<EOT
              </select>
            </td>
          </tr>
        </table>
      </div>
EOT;
      $discovery .= "<div id='idSched7' class='forminput' style=margin-bottom:3px;>";
      $discovery .= "<table width='100%'>";
      $discovery .= "<tr>";
      $discovery .= "<th width='35%'>"._("Frequency")."</th>";
      $discovery .= "<td width='100%' style='text-align:center;' class='nobborder'>";
      $discovery .= "<span style='margin-right:5px;'>"._("Every")."</span>";
      $discovery .= "<select name='time_interval'>";
      for ($itime = 1; $itime <= 30; $itime++) {
        $discovery .= "<option value='".$itime."'".(($editdata['time_interval']==$itime) ? " selected":"").">".$itime."</option>";
      }
      $discovery .= "</select>";
      $discovery .= "<span id='days' style='margin-left:5px'>"._("day(s)")."</span><span id='weeks' style='margin-left:5px'>"._("week(s)")."</span>";
      $discovery .= "</td>";
      $discovery .= "</tr>";
      $discovery .= "</table>";
      $discovery .= "</div>";
$discovery .= <<<EOT
      <div id="idSched2" class="forminput">
        <table width="100%">
EOT;
        $discovery .=  "<tr>";
        $discovery .=  "<th rowspan='2' align='right' width='35%'>".gettext("Time")."</td>";
        $discovery .=  "<td align='right'>".gettext("Hour")."</td>";
            $discovery .= <<<EOT
            <td align="left" class="noborder"><select name="time_hour">
EOT;

   for ($i=0;$i<=23;$i++){
      $discovery .=  "<option value=\"$i\"";
      if (($time_hour==$i && $time_hour_selected=="") || $time_hour_selected==$i) $discovery .= " selected";
      $discovery .= ">$i</option>";
   };
            $discovery .= "</select></td><td align='right'>".gettext("Minutes")."</td>
            <td class='noborder' align='left'><select name='time_min'>";
               for ($i=0;$i<60;$i=$i+15){
                    $discovery .= "<option value=\"$i\"";
                    if (($time_min == $i && $time_min_selected=="") || $time_min_selected==$i) $discovery .= " selected";
                    $discovery .= ">$i</option>";
               };
            $discovery .= <<<EOT
            </select></td>
          </tr>
        </table>
      </div>
    </tr>
    
EOT;
	$discovery .= "<tr>";
	$discovery .= "		<td class='madvanced'><a class='section'><img id='advanced_arrow' border='0' align='absmiddle' src='../pixmaps/arrow_green.gif'>"._("ADVANCED")."</a></td>";
	$discovery .= "		<td>&nbsp;</td>";
	$discovery .= "</tr>";
	
	if ($_SESSION["scanner"]=="omp") {
        $credentials = Vulnerabilities::get_credentials($dbconn, 'ssh');
        
        preg_match ("/(.*)\|(.*)/", $editdata["credentials"], $found);
        
        $discovery .= "<tr class='advanced'>";
        $discovery .= "<td class='job_option'>".Util::strong(_("SSH Credential:"))."</td>";
        $discovery .= "<td style='text-align:left'><select id='ssh_credential' name='ssh_credential'>";
        $discovery .= "<option value=''>--</option>";
        foreach ($credentials as $cred) 
		{			
			$login_text = $cred["login"];

			if ( $cred["login"] == '0' ){
				$login_text = _("All");	
			}
			elseif ( valid_hex32($cred["login"]) ) {
				$login_text = Session::get_entity_name($dbconn, $cred["login"]);
			}
						
            $selected = ( ($found[1] == $cred["name"]."#".$cred["login"]) || $cred["name"]."#".$cred["login"] == $ssh_credential ) ? " selected='selected'" : "";
            $discovery .="<option value='".$cred["name"]."#".$cred["login"]."' $selected>".$cred["name"]." (".$login_text.")</option>";
        }
        $discovery .= "</select></td>";
        $discovery .= "</tr>";
        
        $credentials = Vulnerabilities::get_credentials($dbconn, 'smb');
        
        $discovery .= "<tr class='advanced'>";
        $discovery .= "<td class='job_option'>".Util::strong(_("SMB Credential:"))."</td>";
        $discovery .= "<td style='text-align:left'><select id='smb_credential' name='smb_credential'>";
        $discovery .= "<option value=''>--</option>";
        
		foreach ($credentials as $cred) 
		{            
			$login_text = $cred["login"];

			if ( $cred["login"] == '0' ){
				$login_text = _("All");	
			}
			elseif ( valid_hex32($cred["login"]) ) {
				$login_text = Session::get_entity_name($dbconn, $cred["login"]);
			}
			
			$selected   = ( $found[2] == $cred["name"]."#".$cred["login"] || $cred["name"]."#".$cred["login"] == $smb_credential ) ? " selected='selected'" : "";
            $discovery .= "<option value='".$cred["name"]."#".$cred["login"]."' $selected>".$cred["name"]." (".$login_text.")</option>";
        }
		
        $discovery .= "</select></td>";
        $discovery .= "</tr>";
    }
    
    $discovery .="<tr class='job_option advanced'>";
    $discovery .="<td class='job_option'>".Util::strong(_("Timeout:"))."</td>";
    $discovery .="<td style=\"text-align:left;\" nowrap><input type='text' style='width:80px' name='timeout' value='".(($timeout_selected=="")? "$timeout":"$timeout_selected")."'>";
    $discovery .="<font color='black'>&nbsp;&nbsp;&nbsp;"._("Max scan run time in seconds")."&nbsp;&nbsp;&nbsp;</font></td>";
    $discovery .="</tr>";
	
	$discovery .= "<tr class='advanced'><td class='job_option'>".Util::strong(_("Send an email notification:"));
	$discovery .= "</td>";
	$discovery .= "<td style=\"text-align:left;\">";
	$discovery .= "<input type=\"radio\" name=\"semail\" value=\"0\"".(((count($editdata)<=1 && intval($semail)==0) || intval($editdata['meth_Wfile'])==0)? " checked":"")."/>"._("No");
	$discovery .= "<input type=\"radio\" name=\"semail\" value=\"1\"".(((count($editdata)<=1 && intval($semail)==1) || intval($editdata['meth_Wfile'])==1)? " checked":"")."/>"._("Yes");
	$discovery .= "</td></tr>";
	
	$discovery .= "<tr class='advanced'>
						<td class='job_option'>".Util::strong(_("Scan job visible for:"))."</td>
						<td style='text-align: left'>
							<table cellspacing='0' cellpadding='0' class='transparent' style='margin: 5px 0px;'>
								<tr>
									<td class='nobborder'><span style='margin-right:3px'>"._('User:')."</span></td>	
									<td class='nobborder'>				
										<select name='user' id='user' onchange=\"switch_user('user');return false;\">";
										
										$num_users = 0;
										foreach( $users as $k => $v )
										{
											$login = $v->get_login();
											
											$selected = ( $editdata["username"] == $login || $user_selected == $login ) ? "selected='selected'": "";
											$options .= "<option value='".$login."' $selected>$login</option>\n";
											$num_users++;
										}
										
										if ($num_users == 0)
											$discovery .= "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
										else
										{
											$discovery .= "<option value='' style='text-align:center !important;'>- "._("Select one user")." -</option>\n";
											$discovery .= $options;
										}
											
				
	$discovery .= "						</select>
									</td>";
								
	if ( !empty($entities_to_assign) )
	{ 
		
	$discovery .= "	    			<td style='text-align:center; border:none; !important'><span style='padding:5px;'>"._("OR")."<span></td>
									<td class='nobborder'><span style='margin-right:3px'>"._("Entity:")."</span></td>
									<td class='nobborder'>	
										<select name='entity' id='entity' onchange=\"switch_user('entity');return false;\">
											<option value='' style='text-align:center !important;'>-"._("Select one entity")."-</option>";
						
												foreach ( $entities_to_assign as $k => $v ) 
												{
													$selected = ( ( $editdata["username"] == $k || $entity_selected == $k ) ) ? "selected='selected'": "";
													$discovery .= "<option value='$k' $selected>$v</option>";
												}
		
		$discovery .= "					</select>
									</td>";
	}
	
	$discovery .= " 	    	</tr>
							</table>
						</td>
					</tr>";
	
	$discovery .= "<tr><td valign=\"top\" width=\"15%\" class=\"job_option noborder\"><br>";
	
	// conditions to exclude IPs
	
	$condition1 = (count($editdata)<=1 && intval($hosts_alive)==1) ? TRUE : FALSE;
	
	$condition2 = preg_match('/'.EXCLUDING_IP2.'/', trim($editdata["meth_TARGET"]));
	
	$condition3 = (intval($editdata['meth_CRED']) == 1) ? TRUE : FALSE;
	
	$condition4 = (count($ip_exceptions_list) > 0) ? TRUE : FALSE;
	
	$host_alive_check  = ( $condition1 || $condition2 || $condition3  || $condition4 ) ? ' checked' : '';
	
	$host_alive_status = ($condition2 || $condition4) ? ' disabled=\"disabled\"' : '';
	
	$discovery .= "<input onclick=\"toggle_scan_locally()\" type=\"checkbox\" id=\"hosts_alive\" name=\"hosts_alive\" value=\"1\"" . $host_alive_check . $host_alive_status . ">".Util::strong(_("Only scan hosts that are alive"))."<br>(".Util::strong(_("greatly speeds up the scanning process")).")<br><br>";
	$discovery .= "<input type=\"checkbox\" id=\"scan_locally\" name=\"scan_locally\" value=\"1\"".
	  (($pre_scan_locally_status==0) ? " disabled=\"disabled\"":"").
	  (($pre_scan_locally_status==1 && ( intval($editdata['authorized'])==1 || intval($scan_locally) == 1 ))? " checked":"").">".Util::strong(_("Pre-Scan locally")).
	  "<br>(".Util::strong(_("do not pre-scan from scanning sensor")).")<br><br>";
    $discovery .="<input type=\"checkbox\" id=\"not_resolve\" name=\"not_resolve\" value=\"1\" ".(($editdata['resolve_names']==="0" || $not_resolve=="1") ? "checked=\"checked\"":"")."/>".Util::strong(_("Do not resolve names"));

	$discovery .= <<<EOT
        </td>
EOT;
    $discovery .= '     <td class="noborder" valign="top">';
    $discovery .= '         <table width="100%" class="transparent" cellspacing="0" cellpadding="0">';
    $discovery .= '              <tr>';
    $discovery .= '                  <td class="nobborder" style="vertical-align: top;text-align:left;padding:10px 0px 0px 0px;">';
    $discovery .= '                     <table class="transparent" cellspacing="4">';
    $discovery .= '                         <tr>';
    $discovery .= '                             <td class="nobborder" style="text-align:left;"><input class="greyfont" type="text" id="searchBox" value="'._("Type here to search assets (Hosts/Networks)").'" /></td>';
    $discovery .= '                         </tr>';
    $discovery .= '                         <tr>';
    $discovery .= '                             <td class="nobborder"><select id="targets" name="targets[]" multiple="multiple">';

    if(!empty($editdata["meth_TARGET"])) {
        $ip_list = explode("\n", trim($editdata["meth_TARGET"]));
    }
    if(!empty($ip_list)) {
        foreach($ip_list as $asset) {
            if(preg_match("/([a-f\d]+)#(.*)/i",$asset, $found)) {
            	if(Asset_host::is_in_db($dbconn, $found[1]))
            	{
	            	$_asset_name = Asset_host::get_name_by_id($dbconn, $found[1]) ." (" .$found[2]. ")";
            	}
            	else
            	{
	            	$_asset_name = Asset_net::get_name_by_id($dbconn, $found[1]) ." (" .$found[2]. ")";
            	}
                $discovery .= '<option value="'.$asset.'">'.$_asset_name.'</option>';
            }
            else {
                $discovery .= '<option value="'.$asset.'">'.$asset.'</option>';
            }
        }
        
        foreach($ip_exceptions_list as $asset)
        {
            $discovery .= '<option value="'.$asset.'">'.$asset.'</option>';   
        }
    }
    $discovery .= '                             </select></td>';
    $discovery .= '                         </tr>';
    $discovery .= '                         <tr>';
    $discovery .= '                             <td class="nobborder" style="text-align:right"><input type="button" value=" [X] " id="delete_target" class="av_b_secondary small"/>';
    $discovery .= '                             <input type="button" style="margin-right:0px;"value="Delete all" id="delete_all" class="av_b_secondary small"/></td>';
    $discovery .= '                         </tr>';
    $discovery .= '                         </table>';
    $discovery .= '                  </td>';
    $discovery .= '                  <td class="nobborder" width="450px;" style="vertical-align: top;padding:0px 0px 0px 5px;">';
    $discovery .= '                    <div id="vtree" style="text-align:left;width:100%;"></div>';
    $discovery .= '                  </td>';
    $discovery .= '              </tr>';
    $discovery .= '         </table>';
    $discovery .= '    </td>';
    $discovery .= '</tr>';
    $discovery .= '</table>';
    $discovery .= '</tr></td></table>';
   $discovery .= $show;
   return $discovery;
}

function edit_schedule ( $sched_id ) {
    global $uroles, $editdata, $scheduler, $username, $useremail, $dbconn;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    logAccess( "USER $username CHOSE EDIT SCHEDULE $sched_id" );

    $sql_access = "";
    if ( ! $uroles['admin'] ) { $sql_access = "AND username='$username'"; }

    $query = "SELECT id, name, username, fk_name, job_TYPE, schedule_type, day_of_week, 
                     day_of_month, time, email, meth_TARGET, meth_CRED, 
                     meth_VSET, meth_Wcheck, meth_Wfile, meth_Ucheck, 
		     meth_TIMEOUT, scan_ASSIGNED, resolve_names, time_interval, credentials
              FROM vuln_job_schedule 
	      WHERE id = '$sched_id' $sql_access";
    $result = $dbconn->execute($query);
    $editdata = $result->fields;
    $editdata['authorized'] = $editdata['meth_Ucheck'];

    if ( $editdata['id'] == $sched_id ) {
       main_page( $job_id, "editrecurring" );
    } else {
  //logAccess( "INVALID SCHEDULE $sched_id" );
    }
}

function rerun ( $job_id ) {
   global $uroles, $editdata, $scheduler, $username, $useremail, $dbconn;
   
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    logAccess( "USER $username CHOSE TO RERUN SCAN $job_id" );

    $sql_access = "";
    if ( ! $uroles['admin'] ) { $sql_access = "AND username='$username'"; }    

    $query = "SELECT * FROM vuln_jobs WHERE id = '$job_id' $sql_access";
    $result = $dbconn->execute($query);
    #list( $sname, $notify_email, $job_type, $schedule_type, $timeout, $SVRid, $sid, $targetlist ) = $result->fields;
    $editdata = $result->fields;

    if ( $editdata['id'] == $job_id ) {
       main_page( $job_id, "rerun" );
    } else {
  //logAccess( "INVALID JOBID $job_id" );
       echo "<p><font color=red>INVALID JOB ID</font></p>";
    }

}

function getCredentialId ( $cred_type, $passstore, $credid, $acc, $domain, $accpass, $acctype, $passtype ) {
   global $scheduler, $allowscan, $uroles, $username, $schedOptions, $adminmail, $mailfrom, $dbk, $dbconn;
   
   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    if ( $cred_type == "E" ) {
      if ( $acc != "" && $accpass != "" && $acctype != "" && $passstore != "" ) {
         if ( $domain == "" ) { $sdomain = "Null"; } else { $sdomain = "'$domain'"; }
         $insert_time =  date("YmdHis");
         if ($accpass!="" && !strstr($accpass,'ENC{')) {  // not encrypted
            $cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
            mcrypt_generic_init($cipher, $dbk,substr($dbk,12, 8));
            $encrypted_val = mcrypt_generic($cipher,$accpass);
            $accpass = "ENC{" . base64_encode($encrypted_val) . "}";
            mcrypt_generic_deinit($cipher);
         }

         if ( $passstore == "O" ) {
            $query = "SELECT t1.org_code 
	              FROM vuln_orgs t1
                        LEFT JOIN vuln_org_users t2 ON t1.id = t2.orgID
                      WHERE t2.pn_uname = '$username'";
                      
            $result = $dbconn->execute($query);
            list( $org ) = $result->fields;
         }
         $query = "INSERT INTO vuln_credentials ( pn_uname, account, password, domain, password_type, ACC_TYPE,
              STORE_TYPE, ORG, select_key ) VALUES ( '$username', '$acc', '$accpass', $sdomain, 'Password',
              '$acctype', '$passstore', '$org', '$insert_time' ) ";

         if ($dbconn->execute($query) === false) {
            echo "Error creating scan job: " .$dbconn->ErrorMsg();
       //logAccess( "Error saving credentials $auname:" . $dbconn->ErrorMsg() );
            $error = 1;
            exit;
         } else {
            $query2 = "SELECT id FROM vuln_credentials WHERE pn_uname='$username' AND select_key='$insert_time'";
            $result2 = $dbconn->execute($query2);
            list( $tmpID ) = $result2->fields;
            return "'$tmpID'";
         }

      }

   } 

   if ( $cred_type == "S" ) {
      if ( $credid != "" ) {
         return "'$credid'";
      }
   }

   return;

}
 
function submit_scan( $vuln_op, $sched_id, $sname, $notify_email, $schedule_type, $ROYEAR, $ROMONTH, $ROday,
     $time_hour, $time_min, $dayofweek, $dayofmonth, $timeout, $SVRid, $sid, $tarSel, $ip_list, $ip_exceptions_list,
     $ip_start, $ip_end,  $named_list, $cidr, $subnet, $system, $cred_type, $credid, $acc, $domain,
     $accpass, $acctype, $passtype, $passstore, $wpolicies, $wfpolicies, $upolicies, $custadd_type, $cust_plugins,
     $is_enabled, $hosts_alive, $scan_locally, $nthweekday, $semail, $not_resolve, $time_interval, $biyear, $bimonth, $biday, $ssh_credential="", $smb_credential="") {

     
     global $wdaysMap, $daysMap, $allowscan, $uroles, $username, $schedOptions, $adminmail, $mailfrom, $dbk, $dbconn;
     
     // credentials
     
     $credentials = $ssh_credential."|".$smb_credential;
     
     $btime_hour = $time_hour;  // save local time
     $btime_min  = $time_min;
     
     $bbiyear    = $biyear;
     $bbimonth   = $bimonth;
     $bbiday     = $biday;
     
     $tz = Util::get_timezone();

     if( $schedule_type == "O") {
         // date and time for run once
         if (empty($ROYEAR))  $ROYEAR   = gmdate("Y");
         if (empty($ROMONTH)) $ROMONTH = gmdate("m");
         if (empty($ROday))   $ROday     = gmdate("d");
         
         list ($_y,$_m,$_d,$_h,$_u,$_s,$_time) = Util::get_utc_from_date($dbconn,"$ROYEAR-$ROMONTH-$ROday $time_hour:$time_min:00",$tz);
         
         $ROYEAR    = $_y;
         $ROMONTH   = $_m;
         $ROday     = $_d;
         $time_hour = $_h;
         $time_min  = $_u;
     }
     else if($schedule_type == "D" || $schedule_type == "W" || $schedule_type == "M" || $schedule_type == "NW") {
         // date and time for Daily, Day of Week, Day of month, Nth weekday of month
         list ($b_y,$b_m,$b_d,$b_h,$b_u,$b_s,$b_time) = Util::get_utc_from_date($dbconn,"$biyear-$bimonth-$biday $time_hour:$time_min:00",$tz);
         
         $biyear    = $b_y;
         $bimonth   = $b_m;
         $biday     = $b_d;
         $time_hour = $b_h;
         $time_min  = $b_u;
     }
         
     if($not_resolve=="1")  $resolve_names = 0;
     else                   $resolve_names = 1;
     
     $notify_email = str_replace( ";", ",", $notify_email );
     $requested_run = "";
     $jobType="M";
     $recurring = False;
     $targets = array();
     $time_value = "";
     $profile_desc =  getProfileName( $sid );
     $target_list = "";
     $need_authorized = "";
     $request="";
     $plugs_list="NULL";
     $fk_name="NULL";
     $target_list="NULL";
     $tmp_target_list="";
     $jobs_names = array();
     $sjobs_names = array();
     

     $I3crID = "";
     
	 if ( $hosts_alive == "1" ) { // option: Only scan hosts that are alive
        $I3crID = "1";
     }
     else
        $I3crID = "0";

     // if ( $custadd_type == "" ) { $custadd_type = "N"; }
     // if ( $custadd_type != "N" && $cust_plugins != "" ) {
     	  // $plugs_list="";
          // $vals=preg_split( "/\s+|\r\n|,|;/", $cust_plugins );
          // foreach($vals as $v) {
               // $v=trim($v);
               // if ( strlen($v)>0 ) {
                    // $plugs_list .= $v . "\n";
               // }
          // }
          // $plugs_list = "'".$plugs_list."'";
     // }

    if($schedule_type != "N") {
       // current datetime in UTC
       
       $arrTime = explode(":",gmdate('Y:m:d:w:H:i:s'));
       
       $year = $arrTime[0];
       $mon  = $arrTime[1];
       $mday = $arrTime[2];
       $wday = $arrTime[3];
       $hour = $arrTime[4];
       $min  = $arrTime[5];
       $sec  = $arrTime[6];
       
       $timenow = $hour.$min.$sec;
       
       $run_wday = $wdaysMap[$dayofweek];

       $run_time = sprintf("%02d%02d%02d",  $time_hour, $time_min, "00" );
       $run_mday = $dayofmonth;     
       $time_value = "$time_hour:$time_min:00";  

       $ndays = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
       
       $begin_in_seconds   = mktime ( $bihour, $bimin, 0, $bimonth, $biday, $biyear);     // selected datetime by user in UTC
       $current_in_seconds = mktime ( $hour, $min, 0, $mon, $mday, $year);                // current datetime in UTC
       
       if(strlen($bimonth)==1) $bimonth = "0".$bimonth;
       if(strlen($biday)==1)   $biday   = "0".$biday;
    }

   switch($schedule_type) {
   case "N":

          $requested_run = gmdate("YmdHis");
          $sched_message = "No reccurring Jobs Necessary";

      break;
   case "O":
   
          $requested_run = sprintf("%04d%02d%02d%06d", $ROYEAR, $ROMONTH, $ROday, $run_time );
          
          //error_log("O-> $requested_run\n" ,3,"/tmp/sched.log");
          
          $sched_message = "No reccurring Jobs Necessary";

          $recurring = True;
          $reccur_type = "Run Once";

      break;
   case "D":
          if( $begin_in_seconds > $current_in_seconds ) {
                $next_day = $biyear.$bimonth.$biday;  // selected date by user
          }
          else {
                if ( $run_time > $timenow )
                    $next_day = $year.$mon.$mday; // today
                else
                    $next_day = gmdate("Ymd", strtotime("+1 day GMT",gmdate("U"))); // next day
          }
          
          $requested_run = sprintf("%08d%06d", $next_day, $run_time );
          
          //error_log("D-> $requested_run\n" ,3,"/tmp/sched.log");

          $recurring = True;
          $sched_message = "Schedule Reccurring";
          $reccur_type = "Daily";
          
      break;
   case "W":

            if( $begin_in_seconds > $current_in_seconds ) { // if it is a future date
                $wday  = date("w",mktime ( 0, 0, 0, $bimonth, $biday, $biyear)); // make week day for begin day
                if ($run_wday == $wday) {
                    $next_day = $biyear.$bimonth.$biday;  // selected date by user
                }
                else {
                    $next_day = gmdate("Ymd", strtotime("next ".$ndays[$run_wday]." GMT",mktime ( 0, 0, 0, $bimonth, $biday, $biyear)));
                }
            }
            else {
                if (($run_wday == $wday && $run_time > $timenow) || ($run_wday > $wday)) 
                    $next_day = $year.$mon.$mday; // today
                else
                    $next_day = gmdate("Ymd", strtotime("next ".$ndays[$run_wday]." GMT",gmdate("U"))); // next week
            }
          
            preg_match("/(\d{4})(\d{2})(\d{2})/", $next_day, $found);
 
            list ($b_y,$b_m,$b_d,$b_h,$b_u,$b_s,$b_time) = Util::get_utc_from_date($dbconn,$found[1]."-".$found[2]."-".$found[3]." $btime_hour:$btime_min:00",$tz);
            $requested_run = sprintf("%04d%02d%02d%02d%02d%02d", $b_y, $b_m, $b_d, $b_h, $b_u, "00");
          
            //error_log("W-> $requested_run\n" ,3,"/tmp/sched.log");
            
            $recurring = True;
            $sched_message = "Schedule Reccurring";
            $reccur_type = "Weekly";
          
      break;
   case "M":
          if( $begin_in_seconds > $current_in_seconds ) { // if it is a future date
              if ( $run_mday >= $biday) {
                  $next_day =  $biyear.$bimonth.($run_mday<10 ? "0" : "").$run_mday; // this month
              } else {
                  $next_day = sprintf("%06d%02d", gmdate("Ym", strtotime("next month GMT",mktime ( 0, 0, 0, $bimonth, $biday, $biyear))), $run_mday ) ;
              }
          }
          else {
              if ( $run_mday > $mday || ( $run_mday == $mday && $run_time > $timenow )) {
                  $next_day = $year.$mon.($run_mday<10 ? "0" : "").$run_mday; // this month
              } else {
                  $next_day = sprintf("%06d%02d", gmdate("Ym", strtotime("next month GMT",gmdate("U"))), $run_mday ) ;
              }
          }
          
          preg_match("/(\d{4})(\d{2})(\d{2})/", $next_day, $found);

          list ($b_y,$b_m,$b_d,$b_h,$b_u,$b_s,$b_time) = Util::get_utc_from_date($dbconn,$found[1]."-".$found[2]."-".$found[3]." $btime_hour:$btime_min:00",$tz);
          $requested_run = sprintf("%04d%02d%02d%02d%02d%02d", $b_y, $b_m, $b_d, $b_h, $b_u, "00");
              
          //error_log("M-> $requested_run $begin_in_seconds $current_in_seconds\n" ,3,"/tmp/sched.log");
          
          $recurring = True;
          $sched_message = "Schedule Reccurring";
          $reccur_type = "Montly";
          
      break;
   case "NW":
        if( $begin_in_seconds > $current_in_seconds ) { // if it is a future date
            $array_time = array('month'=> $bbimonth, 'day' => $bbiday, 'year' => $bbiyear);
            $requested_run = weekday_month(strtolower($daysMap[$dayofweek]), $nthweekday, $btime_hour, $btime_min, $array_time);
        }
        else {
            $requested_run = weekday_month(strtolower($daysMap[$dayofweek]), $nthweekday, $btime_hour, $btime_min);
        }
        
          preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $requested_run, $found);

          list ($b_y,$b_m,$b_d,$b_h,$b_u,$b_s,$b_time) = Util::get_utc_from_date($dbconn,$found[1]."-".$found[2]."-".$found[3]." ".$found[4].":".$found[5].":00",$tz);
          $requested_run = sprintf("%04d%02d%02d%02d%02d%02d", $b_y, $b_m, $b_d, $b_h, $b_u, "00");
        
        
        //error_log("NW-> $requested_run\n" ,3,"/tmp/sched.log");
        
        $dayofmonth = $nthweekday;
        
        $recurring = True;
        $sched_message = "Schedule Reccurring";
        $reccur_type = "Nth weekday of the month";
          
      break;
   default:

      break;
   }
   
    $insert_time = gmdate("YmdHis");

    if(!empty($_SESSION["_vuln_targets"]) && count($_SESSION["_vuln_targets"])>0) {
        $arr_ctx = array();
        $sgr = array();

        foreach( $_SESSION["_vuln_targets"] as $target_selected => $server_id ) {
            $sgr[$server_id][] = $target_selected;

            if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/i", $target_selected)) {
                $related_ctxs      = array_values(Asset_net::get_id_by_ips($dbconn, $target_selected));

                if(is_array($related_ctxs) && count ($related_ctxs)>0) {
                    $arr_ctx[$target_selected] = key(array_shift($related_ctxs));
                }
            }
            else if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i", $target_selected)) {

                $related_ctxs      = array_values(Asset_host::get_id_by_ips($dbconn, $target_selected));
                
                if(is_array($related_ctxs) && count ($related_ctxs)>0) {
                    $arr_ctx[$target_selected] = key(array_shift($related_ctxs)); // to assign a ctx for a IP
                }
            }
            else if(valid_hostname($target_selected) || valid_fqdns($target_selected)) {
            
                $filters   = array('where' => "hostname like '$target_selected' OR fqdns like '$target_selected'");
            
                $_hosts_data = Asset_host::get_basic_list($dbconn, $filters);
                $host_list   = $_hosts_data[1];                

                if( count($host_list) > 0) {
                    
                    $first_host = array_shift($host_list);
                    
                    $hips = explode(",", $first_host['ips']);
                    
                    foreach ($hips as $hip) {
                        
                        $hip = trim($hip);
                        
                        $arr_ctx[$hip] = $first_host['ctx'];
                    }
                }
            }
        }
        ossim_clean_error();
        
        unset($_SESSION["_vuln_targets"]); // clean scan targets
        
        $query = array();
        
        $IP_ctx = array();

        foreach($arr_ctx as $aip => $actx) {
            $IP_ctx[] = $actx."#".$aip;
        }
        
        if ( $vuln_op == "editrecurring" && $sched_id > 0 ) {
            $query[] = "DELETE FROM vuln_job_schedule WHERE id='$sched_id'";
           
            $i = 1;
            foreach ($sgr as $notify_sensor => $targets) {
                $target_list = implode("\n",$targets);
                $target_list .= "\n".implode("\n",$ip_exceptions_list);
                $query[] = "INSERT INTO vuln_job_schedule ( name, username, fk_name, job_TYPE, schedule_type, day_of_week, day_of_month, 
                            time, email, meth_TARGET, meth_CRED, meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wfile, 
                            meth_Ucheck, meth_TIMEOUT, next_CHECK, createdate, enabled, resolve_names, time_interval, IP_ctx, credentials) VALUES ( '$sname', '$username', '".Session::get_session_user()."', '$jobType',
                            '$schedule_type', '$dayofweek', '$dayofmonth', '$time_value', '$notify_sensor', '$target_list',
                            $I3crID, '$sid', '$custadd_type', $plugs_list, $semail, '$scan_locally',
                            '$timeout', '$requested_run', '$insert_time', '1', '$resolve_names' ,'$time_interval', '".implode("\n", $IP_ctx)."', '$credentials') ";
                $sjobs_names [] = $sname.$i;
                $i++;
            }
        }
        elseif ( $recurring ) {
                $i = 1;
                foreach ($sgr as $notify_sensor => $targets) {
                    $target_list = implode("\n",$targets);
                    $target_list .= "\n".implode("\n",$ip_exceptions_list);
                   $query[] = "INSERT INTO vuln_job_schedule ( name, username, fk_name, job_TYPE, schedule_type, day_of_week, day_of_month, 
                                time, email, meth_TARGET, meth_CRED, meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wfile, 
                                meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED, next_CHECK, createdate, enabled, resolve_names, time_interval, IP_ctx, credentials) VALUES ( '$sname', '$username', '".Session::get_session_user()."', '$jobType',
                                '$schedule_type', '$dayofweek', '$dayofmonth', '$time_value', '$notify_sensor', '$target_list',
                                $I3crID, '$sid', '$custadd_type', $plugs_list, $semail, '$scan_locally',
                                '$timeout', '$SVRid', '$requested_run', '$insert_time', '1', '$resolve_names' , '$time_interval', '".implode("\n", $IP_ctx)."', '$credentials') ";
                    
                    $sjobs_names [] = $sname.$i;
                    $i++;
                }
        } 
        else {
                $i = 1;
                foreach ($sgr as $notify_sensor => $targets) {

                    $target_list = implode("\n",$targets);
                    $target_list .= "\n".implode("\n",$ip_exceptions_list);
                    $query[] = "INSERT INTO vuln_jobs ( name, username, fk_name, job_TYPE, meth_SCHED, meth_TARGET,  meth_CRED,
                        meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wfile, meth_TIMEOUT, scan_ASSIGNED,
                        scan_SUBMIT, scan_next, scan_PRIORITY, status, notify, authorized, author_uname, resolve_names, credentials ) VALUES ( '$sname',
                        '$username', '".Session::get_session_user()."', '$jobType', '$schedule_type', '$target_list', $I3crID, '$sid', '$custadd_type', $plugs_list,
                         $semail, '$timeout', '$SVRid', '$insert_time', '$requested_run', '3',
                        'S', '$notify_sensor', '$scan_locally', '".implode("\n",$IP_ctx)."', '$resolve_names' , '$credentials') ";
                        
                        // echo "$query1";
                        // die();
                    $jobs_names [] = $sname.$i;
                    $i++;
                }
        }

        $query_insert_time = gen_strtotime( $insert_time, "" );
        foreach ($query as $sql) {
            $error_updating = false;
            $error_inserting = false;

            if ($dbconn->execute($sql) === false) {
                echo _("Error creating scan job").": " .$dbconn->ErrorMsg();
                if($vuln_op == "editrecurring")
                    $error_updating = true;
                else
                    $error_creating = true;
            }
            else {
                $config_nt = array(
                    'content' => "",
                    'options' => array (
                    'type'          => "nf_success",
                    'cancel_button' => false),
                    'style'   => 'width: 40%; margin: 20px auto; text-align: center;'
                );
                if ( $vuln_op == "editrecurring" && !$error_updating ) {
                    $config_nt["content"] = _("Successfully Updated Recurring Job");
					$nt = new Notification('nt_1', $config_nt);
                    $nt->show();
                }
                elseif ( !$error_creating ) {
                    $config_nt["content"] = _("Successfully Submitted Job");
					$nt = new Notification('nt_1', $config_nt);
                    $nt->show();
                    //logAccess( "Submitted Job [ $jid ] $request" );
                    
                    foreach ($jobs_names as $job_name){
                        $infolog = array($job_name);
                        Log_action::log(66, $infolog);
                    }
                    foreach ($sjobs_names as $job_name){
                        $infolog = array($job_name);
                        Log_action::log(67, $infolog);
                    }
                }
                else {
                    echo "<br><center>"._("Failed Job Creation")."</center>";
                }
                ?>
                <script type="text/javascript">
                //<![CDATA[                    
                document.location.href='<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/manage_jobs.php', 'environment', 'vulnerabilities', 'scan_jobs');?>'; 
                //]]>
                </script>
                <?php
            }
        }
    } // count($_SESSION["_vuln_targets"])>0
   echo "</b></center>";

}

function process_requests($submit, $rids) {
    global $uroles;

//    echo "<Pre>Processing Request\n";
//    echo "\$submit = $submit\n";
//    echo "\$rids = ";
//    print_r($rids);
    if( $uroles['admin'] || $uroles['scanRequest']) {
       if($submit == "Approve Requests") { 
//          echo "approving requests\n";
          $sub = "accept_request";
       } elseif ($submit == "Reject Requests") {
//          echo "rejecting requests\n";
          $sub = "reject_request";
       }
       foreach ($rids as $rid) {
//          echo "$sub($rid)\n";
          $sub($rid);
       }
    }
//    echo "</pre>";
}

function delete_scan( $job_id ) {
     global $uroles, $username, $useremail, $mailfrom, $dbconn;
     
     $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

     if ( $uroles['admin'] ) {
        $term_status = "Allowed";
        //echo "Scan Terminated";
        //echo "<br>";
        $query = "SELECT name, id, scan_SERVER, report_id, status FROM vuln_jobs WHERE id='$job_id' LIMIT 1";
        $result = $dbconn->execute($query);
        list($job_name, $kill_id, $nserver_id, $report_id, $status) = $result->fields;

        if($status=="R"){
            $query = "UPDATE vuln_nessus_servers SET current_scans=current_scans-1 WHERE id='$nserver_id' and current_scans>0 LIMIT 1";
            $result = $dbconn->execute($query);
        }
        //$query = "UPDATE vuln_jobs SET status='C' WHERE id='$kill_id' LIMIT 1";
        //$result = $dbconn->execute($query);
        
        $query = "DELETE FROM vuln_jobs WHERE id='$kill_id'";
        $result = $dbconn->execute($query);

        $query = "DELETE FROM vuln_nessus_reports WHERE report_id='$report_id'";
        $result = $dbconn->execute($query);
        
        $query = "DELETE FROM vuln_nessus_report_stats WHERE report_id='$report_id'";
        $result = $dbconn->execute($query);

        $query = "DELETE FROM vuln_nessus_results WHERE report_id='$report_id'";
        $result = $dbconn->execute($query);
        
        $infolog = array($job_name);
        Log_action::log(65, $infolog);
        
        ?><script type="text/javascript">
        //<![CDATA[
        document.location.href='<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/manage_jobs.php', 'environment', 'vulnerabilities', 'scan_jobs') ?>';
       //]]>
        </script><?
     } else {
        $term_status = "Denied";
     }
}


switch($disp) {

   //case "auth_request":
   //   auth_request ( $op, $submit, $process );
   //break;

   case "create":
    if($error_message!=""){
        $config_nt = array(
                'content' => $error_message,
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => false
                ),
                'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
            ); 
                            
        $nt = new Notification('nt_1', $config_nt);
        $nt->show();

        main_page( $job_id, $vuln_op );
    }
    else 
	{
        if($entity!="" && $entity!="none") $username = $entity;
        if($user!="" && $user!="none")    $username  = $user;
        
        submit_scan( $vuln_op, $sched_id, $sname, $notify_email, $schedule_type, $ROYEAR,$ROMONTH, $ROday,
        $time_hour, $time_min, $dayofweek, $dayofmonth, $timeout, $SVRid, $sid, $tarSel, $ip_list, $ip_exceptions_list,
        $ip_start, $ip_end,  $named_list, $cidr, $subnet, $system, $cred_type, $credid, $acc, $domain,
        $accpass, $acctype, $passtype, $passstore, $wpolicies, $wfpolicies, $upolicies, $custadd_type, $cust_plugins,
        $is_enabled, $hosts_alive, $scan_locally, $nthweekday, $semail, $not_resolve, $time_interval, $biyear, $bimonth, $biday, $ssh_credential, $smb_credential);
    }
   break;

   case "edit_sched":
      edit_schedule ( $sched_id );
   break;

   case "delete_scan":
      delete_scan ( $job_id );
   break;

   case "rerun":
      rerun ( $job_id );
   break;
   
   default:
      main_page( $job_id, $vuln_op );
      break;
}

$db->close($conn);

function createHiddenDiv($name, $num, $data) {
   $text = "";
   $style = "";
   if($num == 0) {
      $style = "style='display: block;'";
   }
   else { $style = "style='display: none;'"; }
   $text = "<div id='section" . $num . "' name='$name' class='settings' $style>\n";
   $text .= $data;
   $text .= "</div>";
   return $text;
}

//day   = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'
//$nth  = 'first' | 'second' | 'third' | 'fourth' | 'fifth' | 'sixth' | 'seventh' | 'eighth' | 'ninth' | 'tenth' 
//$h    = Local hours
//$m    = Local minutes

function weekday_month($day, $nth, $h, $m, $start_date = array())
{
    $current_year  = ($start_date['year']!="")  ? $start_date['year']  : date('Y');
    $current_month = ($start_date['month']!="") ? $start_date['month'] : date('m');
    $current_day   = ($start_date['day']!="")   ? $start_date['day']   : date('d');
    
    if(empty($start_date)) {
        //Current timestamp
        $today  = mktime(date('H'), date('i'), 0, $current_month, $current_day, $current_year);
    }
    else {
        $today  = mktime(0, 0, 0,  $current_month, $current_day, $current_year);
    }
    //Last day of previous month 
    $date   = strtotime("-1 day", mktime($h, $m, 0, $current_month, 1, $current_year));
    
    //Search date
    for ($i=0; $i<$nth; $i++){
        $date = strtotime("next $day", $date);
    }
    
    $date = $date + (($h*3600) + ($m*60));
                            
    //If date is less than current, we search in next month
    if ( $date < $today )
    {
        $month = (int)$current_month + 1;
        $date  = strtotime("-1 day", mktime ($h, $m, 0, $month, 1, $current_year));
        
        for ($i=0; $i<$nth; $i++){
            $date = strtotime("next $day", $date);
        }
        
        $date = $date + (($h*3600) + ($m*60));
    }

    return date('YmdHi', $date)."00";
}

?>
