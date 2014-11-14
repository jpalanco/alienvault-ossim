<?php
/**
 * get_service_try.php
 * 
 * File get_service_vulns.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details Software tab (Toggled when Vulns = Yes)
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';
Session::logcheck("environment-menu", "PolicyHosts");

// Close session write for real background loading
session_write_close();

$asset_id = GET('asset_id');
$service  = GET('service');
$port     = GET('port');

ossim_valid($asset_id, OSS_HEX,                 'illegal: ' . _('Asset ID'));
ossim_valid($service,  OSS_ALPHA, OSS_PUNC_EXT, 'illegal: ' . _('Service name'));
ossim_valid($port,     OSS_DIGIT,               'illegal: ' . _('Port number'));

if (ossim_error())
{
    throw new Exception(ossim_get_error_clean());
}


$db   = new ossim_db();
$conn = $db->connect();


$filters = array(
    'where' => "h.id = UNHEX('$asset_id') AND host_services.port = $port AND host_services.service = '".escape_sql($service, $conn)."'"
);

$_list_data = Asset_host_services::get_list($conn, $filters);
$services   = $_list_data[0];

if (empty($services[$asset_id][0]))
{
    $db->close();
    throw new Exception(_('Service not found'));
}

$service_data = $services[$asset_id][0];
$_host_aux    = Asset_host::get_object($conn, $asset_id);
$_ips_aux     = array_keys($_host_aux->get_ips()->get_ips());
$_ctx_aux     = $_host_aux->get_ctx();

$vulns        = Asset_host_services::get_vulns_by_service($conn, $_ips_aux, $_ctx_aux, $service, $port);

// Not matching with software_cpe, but cpe found in version field
if ($service_data['cpe'] == '' && preg_match('/cpe\:/', $service_data['version']))
{
    $service_data['cpe']     = $service_data['version'];
    $service_data['version'] = '';
}

?>
<div id="tray_container">
    <div class="tray_triangle"></div>
    
    <div id="tray_table">
    
        <div>
        
            <div class='detail_header_left detail_2column'>
                <div class='tray_title'><?php echo _('CPE') ?></div>
                <div class='tray_content'>
                    <?php echo ($service_data['cpe'] != '') ? $service_data['cpe'] : '-' ?>
                </div>
            </div>
            
            <div class='detail_header_left detail_2column'>
                <div class='tray_title'><?php echo _('Source') ?></div>
                <div class='tray_content'>
                    <?php echo $service_data['source']['name'] ?>
                </div>
            </div>
            
            <div class='detail_clear'></div>
            
        </div>
        
        <div class='tray_title'><?php echo _('Description') ?></div>
        <div class='tray_content'>
            <?php echo ($service_data['version'] != '') ? $service_data['version'] : '-' ?>
        </div>
    
        <div class='tray_title'><?php echo _('Vulnerabilities') ?></div>
        <div class='tray_content'>
            <?php
            if (count($vulns) > 0)
            {
                ?><ul class='tray_list_vulns'><?php
                foreach ($vulns as $vuln)
                {
                    $risk_data = Incident::get_risk_info($vuln['risk']);
                    $risk_name = $risk_data[2];
                    
                    ?>
                    <li>
                        <div class='vuln_<?php echo $risk_name ?>'>&nbsp;</div>
                        <?php echo $vuln['msg'] ?>
                    </li>
                    <?php
                }
                ?></ul><?php
            }
            else
            {
                echo '-';
            }
            ?>
        </div>
    
    </div>
</div>
<?php

/* End of file get_service_tray.php */
/* Location: ./asset_details/ajax/get_service_try.php */