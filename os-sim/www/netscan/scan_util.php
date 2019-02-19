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

Session::logcheck('environment-menu', 'ToolsScan');


function scan2html($conn, $scan) 
{
    $count = 0;    
    
    $text_hostname = "<div>"._('A valid hostname must satisfy the following rules (according RFC 1123)').":</div>
                    <div>
                        <ul class='ul_tiptip'>
                            <li>"._("Hostname may contain ASCII letters a-z (not case sensitive), digits, and/or hyphens ('-')")."</li>
                            <li>"._("Hostname <strong>MUST NOT</strong> contain a '.' (period) or '_' (underscore)")."</li>
                            <li>"._("Hostname <strong>MUST NOT</strong> contain a space")."</li>
                            <li>"._("Hostname can be up to 63 characters")."</li>
                        </ul>
                    </div>";

    $text_fqdnrfc  = "<div>"._('A valid FQDN must satisfy the following rules (according RFC 952, 1035, 1123 and 2181)').":</div>
                    <div>
                        <ul class='ul_tiptip'>
                            <li>"._("Hostnames are composed of a series of labels concatenated with dots. Each label is 1 to 63 characters long.")."</li>
                            <li>"._("It may contain the ASCII letters a-z (in a case insensitive manner), the digits 0-9, and the hyphen ('-').")."</li>
                            <li>"._("Labels cannot start or end with hyphens (RFC 952).")."</li>
                            <li>"._("Labels can start with numbers (RFC 1123).")."</li>
                            <li>"._("Max length of ascii hostname including dots is 253 characters (not counting trailing dot).")."</li>
                            <li>"._("Underscores ('_') are not allowed in hostnames")."</li>
                        </ul>
                    </div>";

    $text_fqdn     = "<div>"._('If a FQDN contains any dot, only the first label will be used')."</div>";
    
    $text_mac      = "<div>"._('Place the pointer over the MAC address to show MAC vendor')."</div>";
    
    $text_services = "<div>"._('Place the pointer over the service name to show more information')."</div>";
    
    $text_os       = "<div>"._('Place the pointer over the OS to show more information')."</div>";
    
    
    $html = '';
    
    
    $s_ctx = $scan['sensor']['ctx'];
    
    foreach($scan['scanned_ips'] as $ip => $host) 
	{    
        $w_msg  = '';
        $w_html = '';
        
        $external_ctxs = Session::get_external_ctxs($conn);
        
        //Checking forwarded hosts   
        if (empty($external_ctxs[$s_ctx]))
        {
            $can_i_modify_elem = TRUE;
            $md_check =  "<input id='icheckbox".$count."' type='checkbox' checked='checked' class='mc' value='".$ip."' name='ip_".$count."'/>";
        }
        else
        {
            $can_i_modify_elem = FALSE;
            $md_check =  "<input id='icheckbox".$count."' type='checkbox' disabled='disabled' class='mc' name='ip_".$count."'/>";
        }
           
           
        $host_name   = $host['hostname'];
        $fqdn        = $host['fqdn'];  
        
        $ids = Asset_host::get_id_by_ips($conn, $ip, $s_ctx);
        $id  = key($ids);
        
        
        //Host already exists
        if (!empty($id))
        {
            $host_object = Asset_host::get_object($conn, $id);            
            
            if (is_object($host_object) && !empty($host_object))
            {
                $host_name   = $host_object->get_name();
                $h_fqdn      = $host_object->get_fqdns();
                $fqdn        = (!empty($fqdn)) ? $fqdn : $h_fqdn;    
            }                              
        }      
        
        //FQDN
        if (!empty($fqdn))
        {
            $fqdn_check =  "<input id='fcheckbox".$count."' type='checkbox' class='fqdn' value='".$ip."' name='fqdn_".$count."'/>";
        }
        else
        {
            $fqdn       = '-';
            $fqdn_check =  "<input id='fcheckbox".$count."' type='checkbox' disabled='disabled' class='fqdn' value='".$ip."' name='fqdn_".$count."'/>";
        }  
                
        //Devices types
        $devices_types = (count($host['device_types']) > 0) ? implode(', ', $host['device_types']) : '-';
        
        //MAC
        $mac = (!empty($host['mac']) != '') ? "<a class='more_info' data-title='".$host['mac_vendor']."'>".$host['mac']."</a>" : '-';
        
        //Operating System
        $os  = (!empty($host['os']) != '') ? Properties::get_os_pixmap($host['os']).' '.$host['os'] : '-';
        
        
        //Services
        
        $services = array();
        
        if (is_array($host['services']))
        {
            foreach($host['services'] as $port_and_proto => $s_data)
            {                
                $service_name = ($s_data['service'] != 'unknown') ? $s_data['service'] : $port_and_proto;                
                
                $version      =  $s_data['version'];
                if(preg_match('/^cpe:\/a/', $s_data['version']))
                {
                    $version  = Software::get_info($conn, $s_data['version']);               
                }
                
                $version      = (!empty($version) && !preg_match("/^cpe/",$version)) ? $version : $s_data['service'];            
                $title        =  $port_and_proto." (".$version.')';              
                
                $html_data    = "<a class='more_info' data-title='$title'>$service_name</a>";
                
                $services[] = $html_data;
            }
        }
        
        $services = implode(', ', $services);

                
        if ($can_i_modify_elem == FALSE) 
        {        	
        	$w_msg = _('The properties of this asset can only be modified at the USM').": <strong>".$external_ctxs[$s_ctx].'</strong>';
        	
        }        
        
        if (!empty($w_msg))
        {
            $w_html = "<a class='more_info' data-title='".$w_msg."'>
                            <img src='../pixmaps/warning.png' border='0'/>
                       </a>";
        }        
        
        $html .= "
            <tr>
                <td class='td_chk_hosts'>".$md_check."</td>\n
                <td class='td_ip' id='ip".$count."'>$w_html ".$host['ip']."</td>\n
                <td class='td_hostname' id='hostname".$count."'>".$host_name."</td>\n
                <td class='td_fqdn' id='fqdn".$count."'>".$fqdn."</td>\n
                <td class='td_device_types' id='device_types".$count."'>".ucwords($devices_types)."</td>\n
                <td class='td_mac' id='mac".$count."'>".$mac."</td>\n
                <td class='td_os' id='os".$count."'>".$os."</td>\n
                <td class='td_services' id='services".$count."'>".$services."</td>\n
                <td class='td_chk_fqdns'>".$fqdn_check."</td>\n
            </tr>";  
            
        $count++;
    }	
?>
   	
	<form method="POST" action="scan_form.php" name="scan_form" id="scan_form">		
		<input type="hidden" name="sensor_ctx" value='<?php echo $s_ctx?>'/>
		<input type="hidden" name="ips" value='<?php echo $count?>'/>
		
		<div class='results_title'><?php echo _('Scan Results')?></div>
		
		<table class='table_data' id='t_sresults'>
		    <thead>
               
    			</tr>				
    				<th class="th_chk_hosts">    				    
    				    <input type='checkbox' name='chk_all_hosts' id='chk_all_hosts' checked="checked" value="1"/>    			
    				</th>
    				
    				<th class="th_ip"><?php echo _('Host')?></th>
    				<th class="th_hostname"><?php echo _('Hostname')?>
                        <a class="more_info" data-title="<?php echo $text_hostname?>">
                            <img src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                        </a>
                    </th>
                    <th class="th_fqdn"><?php echo _('FQDN')?>
                        <a class="more_info" data-title="<?php echo $text_fqdnrfc?>">
                            <img src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                        </a>
                    </th>
                    <th class="th_devices_types"><?php echo _('Device types')?></th>
    				<th class="th_mac"><?php echo _('Mac')?>
                        <a class="more_info" data-title="<?php echo $text_mac?>">
                            <img src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                        </a>
                    </th>
    				<th class="th_os"><?php echo _('OS')?>
                        <a class="more_info" data-title="<?php echo $text_os?>">
                            <img src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                        </a>
                    </th>
    				<th class="th_services"><?php echo _('Services')?>
                        <a class="more_info" data-title="<?php echo $text_services?>">
                            <img src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                        </a>
                    </th>
    				
    			    <th class="th_chk_fqdns">                        
                        <input type='checkbox' name='chk_all_fqdns' id='chk_all_fqdns' value="1"/>                        
                        <span><?php echo _('FQDN as Hostname')?></span>
                        <a class="more_info" data-title="<?php echo $text_fqdn?>">
                            <img src="../pixmaps/helptip_icon.gif" border="0" align="absmiddle"/>
                        </a>                
                    </th>
                </tr>
            </thead>
            <tbody>
    			<?php echo $html?>    	
            </tbody>
        </table> 
        
        <div style='text-align:center; padding: 10px 0px;'>
			<input type="button" style='margin-left: 10px;' class="av_b_secondary" onclick="document.location.href='index.php?clearscan=1'" value='<?php echo _('Clear scan result')?>'/>
			<input type='submit' name='send' id='send' value="<?php echo _('Update managed assets')?>"/>
        </div>            		
	</form>
	
	<script type='text/javascript'>	

		$(".more_info").tipTip({maxWidth: "auto", attribute: 'data-title'});
		
		$("#chk_all_hosts").click(function(){    	    		
    		if ($(this).prop("checked"))
    		{
        		$(".mc:not(:disabled)").prop("checked", true);
    		}
    		else
    		{
        		$(".mc:not(:disabled)").prop("checked", false);
    		}    		
		});	
		
		$(".mc:not(:disabled)").click(function(){     		
    		
    		if($('.mc:checked').length == 0) 
    		{
        		$("#chk_all_hosts").prop("checked", false);
    		}    			
		});	
		
				
		if ($(".fqdn:not(:disabled)").length > 0)
		{
    		$("#chk_all_fqdns").click(function(){    	    		
        		if ($(this).prop("checked"))
        		{
            		$(".fqdn:not(:disabled)").prop("checked", true);
        		}
        		else
        		{
            		$(".fqdn:not(:disabled)").prop("checked", false);
        		}    		
    		});	
		}
		else
		{
    		$("#chk_all_fqdns").prop("disabled", true);
		}
		
		
		$(".mc:not(:disabled)").click(function(){     		
    		
    		if($('.fqdn:checked').length == 0) 
    		{
        		$("#chk_all_fqdns").prop("checked", false);
    		}    			
		});	
		
		
		/***************************************************
        *********************** Token *********************
        ***************************************************/
        
        Token.add_to_forms();
	</script>
	<?php
    }
?>
