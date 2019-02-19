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

Session::logcheck('environment-menu', 'EventsVulnerabilities');

function hosts_fqdns_to_insert($conn, $report_id, $plugins) 
{
    $ips = array();
    
    $conn->SetFetchMode(ADODB_FETCH_ASSOC);

    $result = $conn->Execute("SELECT distinct v.hostIP, HEX(v.ctx) AS ctx
                                FROM vuln_nessus_results v
                                WHERE v.report_id='$report_id' AND v.hostIP NOT IN (SELECT distinct inet6_ntoa(ip) FROM host_ip,host WHERE host_ip.host_id=host.id AND host.ctx=v.ctx)");

    while (!$result->EOF) 
    {
        if(Session::hostAllowed_by_ip_ctx($conn, $result->fields['hostIP'], $result->fields['ctx'])) 
        {
            $tmp = array();
            
            if(count($plugins) > 0) 
            {
                $resultf = $conn->Execute("SELECT distinct msg, scriptid
                                                FROM vuln_nessus_results v,host h 
                                                WHERE v.report_id='$report_id'
                                                AND v.ctx=UNHEX('".$result->fields['ctx']."')
                                                AND v.hostIP LIKE '".$result->fields["hostIP"]."'
                                                AND v.scriptid IN ('".implode("','", $plugins)."')");

                while (!$resultf->EOF) 
                {
                    if($resultf->fields["scriptid"] == "46180") 
                    {
                        /*
                            Plugin output:

                            - www.liquidity-analyzer.com             <---  FQDN

                                Info   Mark as false positive   i   	Family name: General
                        */
                        
                        $resultf->fields['msg'] = preg_replace("/\n/", "#", $resultf->fields["msg"]);
                        $resultf->fields['msg'] = preg_replace("/#\s*#/", "##", $resultf->fields["msg"]);
                        
                        $tokens = explode('##', $resultf->fields['msg']);

                        $save_fqdn = FALSE;
                        
                        foreach ($tokens as $data) 
                        {

                            if($save_fqdn) 
                            {
                                $fqdns = explode("#", $data);
                                
                                foreach ($fqdns as $fqdn) {
                                    $fqdn  = preg_replace("/^-/", "", $fqdn);
                                    $tmp[] = trim($fqdn);
                                }
                                
                                $save_fqdn = FALSE;
                            }
                            
                            if(preg_match("/.*plugin output:.*/i",$data)) 
                            {  
                                $save_fqdn = TRUE;
                            }
                        }
                    }
                    else if($resultf->fields["scriptid"] == "12053") 
                    {
                        /*
                            Plugin output:

                            194.174.175.47 resolves as p-1-48-047.proxy.bdc-services.net.

                                Info   Mark as false positive   i   	Family name: General
                        */
                        $resultf->fields["msg"] = preg_replace("/\n/", "#", $resultf->fields["msg"]);
                        $resultf->fields["msg"] = preg_replace("/#\s*#/", "##", $resultf->fields["msg"]);
                        
                        $tokens = explode("##", $resultf->fields["msg"]);

                        $save_fqdn = FALSE;
                        
                        foreach ($tokens as $data) 
                        {
                            if($save_fqdn) 
                            {
                                $fqdns = explode("#", $data);
                                foreach ($fqdns as $fqdn) 
                                {
                                    if(preg_match("/resolves as (.*)/",$fqdn,$found)) 
                                    {
                                        $found[1] = preg_replace("/\.$/", "", trim($found[1]));
                                        $tmp[]    = $found[1];
                                    }
                                }
                                
                                $save_fqdn = FALSE;
                            }
                            
                            if(preg_match("/.*plugin output:.*/i",$data)) 
                            {  
                                $save_fqdn = TRUE;  
                            }
                        }
                    }
                    
                    $resultf->MoveNext();
                }
            }
            
            $ips[$result->fields["ctx"]."#".$result->fields["hostIP"]] = implode ("," , $tmp);
        }
        
        $result->MoveNext();
    }
    
    return $ips;
}


function display_errors($info_error) 
{
    $errors    = implode ("</div><div style='padding-top: 3px;'>", $info_error);
    $error_msg = "<div>"._("The following errors occurred:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";

    return ossim_error($error_msg);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo gettext("OSSIM Framework");?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    <style type='text/css'>
        #fqdn_info
        {
            cursor: pointer;
        }
    </style>
    <script type="text/javascript">
        $(document).ready(function(){       
            
            $("#fqdn_info").tipTip({maxWidth: 'auto', content: $("#fqdn_info").attr('data-title'), edgeOffset: 10});
        });
    </script>
</head>
<body>

<?php

$action    = POST('action');
$report_id = (GET('report_id') != '') ? GET('report_id') : POST('report_id');

ossim_valid($report_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _('Report id'));
ossim_valid($action, OSS_NULLABLE, 'insert',     'illegal:' . _('Action'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();

Util::disable_perm_triggers($conn, TRUE);

$save       = 0;
$info_error = array();

if($action == 'insert') 
{    
    $data = array();

    foreach ($_POST as $key => $value) 
    {
        if(preg_match("/^ip(.+)/", $key, $found)) 
        {
            ossim_valid(POST("$key"), OSS_IP_ADDR, 'illegal:' . _('Ip'));
            $num = $found[1];
            
            if(POST("ctx$num") == '')
            {
                $ctx = Session::get_default_ctx();
            }
            else 
            {
                $ctx = POST("ctx$num");
                ossim_valid($ctx, OSS_HEX, 'illegal:' . _('Ctx'));
            }
            
            list($sensor_list, $total) = Av_sensor::get_list($conn, array('where' => "sensor.id = acl_sensors.sensor_id AND acl_sensors.entity_id = UNHEX('$ctx')"));
            
            $sensors = array_keys($sensor_list);
            
            if(POST("name$num") == '')
            {
                $hostname = POST("$key");
            }
            else 
            {
                $hostname = POST("name$num");
                
                ossim_valid($hostname, OSS_HOST_NAME, 'illegal:' . _('Hostname'));
            }
            
            $fqdns = '';
            
            if(POST("fqdn$num") != '') 
            { 
                $fqdns = POST("fqdn$num");
                ossim_valid($fqdns, OSS_FQDNS, OSS_NULLABLE, 'illegal:' . _('FQDN'));
            }
            
            $data[POST("$key")] = array('hostname' => $hostname, 'fqdns' => $fqdns);
            
            if (ossim_error()) 
            {
                $info_error[] = ossim_get_error();
                
                ossim_clean_error();
            }
            else
            {
                $id = Util::uuid();
            
                $host = new Asset_host($conn, $id);
                $host->set_name($hostname);
                $host->set_ctx($ctx);
               
                $host_ip = array();
                $host_ip[POST("$key")] = array(
                   'ip'   =>  POST("$key"),
                   'mac'  =>  NULL,
                );
                
                $host->set_ips($host_ip);
                $host->set_sensors($sensors);
                $host->set_fqdns($fqdns);
                $host->save_in_db($conn, FALSE);
                
                $save++;
            }
        }
    }
    
    if ($save>0)
    {
        Util::disable_perm_triggers($conn, FALSE);
    
        try
        {   
            Asset_host::report_changes($conn, 'hosts');
        }
        catch (Exception $e)
        {
            Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
        }
    }
    
    if(count($info_error) == 0) 
    {        
        ?>
        <script type="text/javascript">
            parent.GB_close();
        </script>
        <?php
    }
}

if(count($info_error) > 0) 
{
    echo display_errors($info_error);
}

$ips = hosts_fqdns_to_insert($conn, $report_id, array("46180", "12053"));  // the third parameter are plugins to get the aliases

if (count($ips) > 0)
{
    ?>
    <form action="add_hosts.php" method="post">
        <input type="hidden" name="action" value="insert" />
        <input type="hidden" name="report_id" value="<?php echo $report_id;?>"/>   
        <table class="transparent" width="95%" align="center">
            <tr>
                <th><?php echo _('IP')?></th>                         
                <th><?php echo _('Hostname')?></th>
                <th><?php echo _('FQDN/Aliases')?>                    
                    <img id="fqdn_info" data-title="<?php echo _("Comma-separated FQDN or aliases")?>" src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                </th>
            </tr>
            <?php 
            $i = 1;
            foreach($ips as $ctx_ip => $fqdn)
            {
                list($ctx, $ip) = explode('#', $ctx_ip);
                ?>
                <tr>
                    <?php
                    if(count($data) == 0 || $data[$ip] != '') 
                    {
                        $checked = "checked=\"checked\"";
                    }
                    else 
                    {
                        $checked = "";
                    }
                    ?>
                    <td width="28%" style="text-align:left;" class="nobborder">
                        <input name="ctx<?php echo $i;?>" type="hidden" value="<?php echo $ctx; ?>" />
                        <input name="ip<?php echo $i;?>" value="<?php echo $ip;?>" type="checkbox" <?php echo $checked;?> /><?php echo $ip;?>
                    </td>
                    <td width="36%" style="text-align:center;" class="nobborder">
                       <input name="name<?php echo $i;?>" value="<?php echo Util::htmlentities($data[$ip]["hostname"]) ?>" style="width: 200px;" type="text" />
                    </td>
                    <td width="36%" style="text-align:center;" class="nobborder">
                        <input name="fqdn<?php echo $i;?>" value="<?php echo (($data[$ip]["fqdns"] != '') ? Util::htmlentities($data[$ip]['fqdns']) : Util::htmlentities($fqdn)) ?>" style="width: 200px;" type="text" />
                    </td>
                </tr>
                <?php
                $i++;
            } 
            ?>
            <tr>
                <td colspan="3" class="nobborder" style="text-align:center;padding-top:10px;">
                    <input type="submit" value="<?php echo _('Save')?>">
                </td>
            </tr>
            <tr>
                <td colspan="3" class="nobborder" height="20">
                    &nbsp;
                </td>
            </tr>
        </table>
        </center>
    </form>
    <?php
}
else
{
	$config_nt = array(
		'content' => _('Asset has been saved successfully'),
		'options' => array (
    		'type'          => 'nf_success',
    		'cancel_button' => FALSE
    	),
    	'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
	);
	
	$nt = new Notification('nt_1', $config_nt);
    $nt->show();
}

$db->close();
?>
</body>
</html>
