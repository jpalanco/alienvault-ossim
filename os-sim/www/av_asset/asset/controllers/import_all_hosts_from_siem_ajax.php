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

set_time_limit(0);

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

ob_implicit_flush();

//Initialize variables
$mode         = 'init';
$num_hosts    = 0;
$assets_limit = 25000;
$new_hosts    = array();
$hosts_in_db  = array();
$errors       = array();

//Selecting mode
if (!empty($_POST['mode']))
{
    ossim_valid($mode, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Mode'));
        
    if (ossim_error()) 
    {
        echo ossim_error();
        exit();
    }    
    
    $mode = POST('mode');
}

//Number of hosts


if (!empty($_POST['num_hosts']))
{
    $num_hosts = intval($_POST['num_hosts']);
}


// Open Log file
$tmp_file = AV_UPLOAD_DIR . '/import_siem_hosts_'.$_SESSION['_user'].'.log';

if ($mode != 'insert') 
{
	$f = fopen($tmp_file, 'w');
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
	<meta http-equiv="Pragma" content="no-cache"/>
</head>
<body>
<?php
if (Session::can_i_create_assets() == FALSE)
{
    $e_msg = _("You don't have permission to create assets");
    
    ?>  
    <script type="text/javascript">
        parent.show_error("<?php echo $e_msg;?>");								
    </script>
    <?php
    exit();
}


if ($mode == 'init')
{
    ?>
    <script type="text/javascript"> 
        parent.$("#pbar").progressBar(0);    
    </script> 
    <?php
}
else
{
    ?>
    <script type="text/javascript">    
        parent.$("#pbar").progressBar();    
    </script> 
    <?php
}


// Get networks and already hosts
$db       = new ossim_db();
$db_aux   = new ossim_db();
$conn_aux = $db_aux->connect();
            
list($nets, $total) = Asset_net::get_list($conn_aux);
            
$i              = 1;
$total_hosts    = 0;
        
// Search new hosts by networks

foreach ($nets as $net) 
{
    session_write_close();
    usleep(500000);
    ob_flush();               	                	
    ?>
        	
    <script type="text/javascript">
        parent.$("#pbar").progressBar(<?php
            if ($total == 0) {
                echo 0;
            }else{
                echo floor($i*100/$total);
            }
            ?>);
                        
        var n_msg = "<?php echo ($mode == 'insert') ? _('Inserting assets from network') : _('Searching assets from network')?>";
        
        parent.$("#ptext").html(n_msg + "<?php echo ' <strong>'.$net['name'].'</strong>'?>");
	</script>
        	                	
    <?php                        
    $cidrs    = explode(',', $net['ips']);
    $net_ctx  = $net['ctx'];    
    
    $query = "SELECT DISTINCT INET6_NTOA(ip_src) AS ip, HEX(ctx) AS ctx, HEX(device.sensor_id) AS sensor_id
            FROM po_acid_event as acid_event, device 
            WHERE acid_event.device_id = device.id AND acid_event.device_id > 0 
            AND ip_src >= INET6_ATON(?) AND ip_src <= INET6_ATON(?) AND ctx = UNHEX(?) AND src_host=0x00000000000000000000000000000000
        UNION
        SELECT DISTINCT INET6_NTOA(ip_dst) AS ip, HEX(ctx) AS ctx, HEX(device.sensor_id) AS sensor_id 
            FROM po_acid_event as acid_event, device
            WHERE acid_event.device_id = device.id AND acid_event.device_id > 0 
            AND ip_dst >= INET6_ATON(?) AND ip_dst <= INET6_ATON(?) AND ctx = UNHEX(?) AND dst_host=0x00000000000000000000000000000000";
    
    foreach($cidrs as $cidr) 
    {
        $range      = Asset_net::expand_cidr($cidr, 'SHORT', 'IP');        
        $conn_snort = $db->snort_connect();                
                           
        $params = array(
            $range[$cidr][0],
            $range[$cidr][1],
            $net_ctx,
            $range[$cidr][0],
            $range[$cidr][1],
            $net_ctx
        );            
                
        //error_log($cidr."\n".$rs->sql."\n\n", 3, '/tmp/siem_host.txt');              
          	
        $rs = $conn_snort->Execute($query, $params);
        
        if (!$rs) 
        {                        
            ?>  
            <script type="text/javascript">
                parent.show_error("<?php echo $conn_snort->ErrorMsg();?>");								
            </script>
            <?php
            exit();                     
        }          
                              
        while (!$rs->EOF && $total_hosts < $assets_limit)
        {
            $ip  = $rs->fields['ip'];
            $ctx = $rs->fields['ctx'];
            
            $ids = Asset_host::get_id_by_ips($conn_aux, $ip, $ctx);
            
            if (empty($hosts_in_db[$ip][$ctx]) && empty($ids)) 
            {                
                if ($mode == 'insert') 
                {                    
                    try
                    {
                        $id       = Util::uuid();
                        $hostname = Asset_host::get_autodetected_name($ip);
                                                
                        $ips      = array();
                        $ips[$ip] = array(
                            'ip'  => $ip,
                            'mac' => NULL                        
                        );                        
                        $sensors  = array($rs->fields['sensor_id']);
                        
                                            
                        //$conn_aux = $db->connect();                    
                        $host     = new Asset_host($conn_aux, $id);

                        Util::disable_perm_triggers($conn_aux, TRUE);

                        $host->set_name($hostname);
                        $host->set_ctx($ctx);
                        $host->set_ips($ips);
                        $host->set_sensors($sensors);                  
                        $host->save_in_db($conn_aux, FALSE);
                        
                        $total_hosts++;
                    
                        $hosts_in_db[$ip][$ctx] = $ip;
                        
                        ?>
                        <script type="text/javascript">                                          
                             parent.$("#ptext").html("<?php echo _('Importing new asset').' <strong>'.$hostname.'</strong>'?>");
                        </script>                                  
                        <?php
                    }
                    catch(Exception $e)
                    {                    
                        $errors[] = $e->getMessage();                                  
                    }                 
                } 
                else 
                {          
                    $hostname = Asset_host::get_autodetected_name($ip);
                    
                    $h_key = $ip.';'.$ctx;                    
                    
                    if (array_key_exists($h_key, $new_hosts) == FALSE)
                    {
                        $new_hosts[$h_key] = array('hostname' => $hostname, 'ip' => $ip);
                    
                        fputs($f, $ip.' detected from Network '.$net['name']."\n");  
                    }                                                 
                }
            }
            
            $rs->MoveNext();
        }
        
        $db->close();
    }
    	
	$i++;
}

if ($mode == 'insert')
{
    Util::disable_perm_triggers($conn_aux, FALSE);
    
    if ($total_hosts > 0)
    {               
        try
        {
            Asset_host::report_changes($conn_aux, 'hosts');
        }
        catch(Exception $e)
        {
            Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());                   
        }
    }
    
    if (is_array($errors) && !empty($errors))
    {
        $errors_txt = implode('<br/>', $errors);
        ?>
        <script type="text/javascript">
            parent.show_error('<?php echo $errors_txt?>');
        </script>  
        <?php
    }
    else
    {
        ?>            
        <script type="text/javascript">
            top.frames['main'].show_notification('asset_notif', "<?php echo _('All assets have been inserted') ?>", 'nf_success', 15000, true);
            parent.GB_hide();
        </script>                                              
        <?php
    }  
}
else
{
    ?>
    <script type="text/javascript">            
        parent.$("#pbar").progressBar(100);
    </script>
    <?php
    
    if (count($new_hosts) > 0) 
    {   
        $new_hosts_message = sprintf(_('<strong>%s</strong> assets found.'), number_format(count($new_hosts), 0, ',', ','));
        
        $warning = (count($new_hosts) > $assets_limit) ? _("Only the first $assets_limit assets can be imported. Once the scan is complete, please return to the import menu to add the remaining assets.") : '';
        
       	?>                
        <script type="text/javascript">
            parent. $("#ptext").html("<?php echo $new_hosts_message . ' ' . $warning . " <a href='".AV_MAIN_PATH."/av_asset/asset/views/import_all_hosts_from_siem_log.php' class='uppercase' target='_blank'>["._('View Log')."]</a>" ?>");          
            
            parent.$("#import_button").removeAttr("disabled");
            
            
            parent.$("#import_button").click(function(){
                parent.$('#ish_form').submit();
                parent.$("#import_button").remove();                
            });
            
            parent.$('#cancel').off();
            parent.$('#cancel').on('click', function(){
                parent.GB_close();
            }); 
            
            parent.$("#num_hosts").val("<?php echo count($new_hosts);?>");
            
            parent.$("#mode").val("insert");
            
        </script>
        <?php
    } 
    else 
    { 
        ?>
        <script type="text/javascript">
            parent.$("#ptext").html("<?php echo _('No new assets found') ?>");
        </script>
        <?php 
    } 

    fclose($f);                
}

$db_aux->close();
?>
</body>
</html>
