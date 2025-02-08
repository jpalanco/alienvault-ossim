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
 * @package    ossim-framework\Vulnerabilities
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';


Session::logcheck("environment-menu", "EventsVulnerabilities");

// Close session write for real background loading
session_write_close();

$db   = new ossim_db();
$conn = $db->connect();

$maxrows     = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 10;
$from        = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;

$order       = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder      =  POST('sSortDir_0');
$sec         =  POST('sEcho');

$profile_id  = POST('profile_id');
$family_id   = POST('family_id');
$category_id = POST('category_id');
$cve         = POST('cve');
$plugin      = POST('plugin');

ossim_valid($profile_id,    OSS_SHA1,                                               'illegal: '._('Profile ID'));
ossim_valid($family_id,     OSS_SHA1, OSS_NULLABLE,                                 'illegal: '._('Family ID'));
ossim_valid($category_id,   OSS_SHA1, OSS_NULLABLE,                                 'illegal: '._('Category ID'));
ossim_valid($cve,           OSS_CVE_ID, OSS_NULLABLE,                               'illegal: '._('CVE'));
ossim_valid($plugin,        OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, '`', OSS_NULLABLE,  'illegal: '._('Vuln Name'));
ossim_valid($maxrows,       OSS_DIGIT,                                              'illegal: iDisplayLength');
ossim_valid($from,          OSS_DIGIT,                                              'illegal: iDisplayStart');
ossim_valid($order,         OSS_ALPHA, OSS_PUNC,                                    'illegal: iSortCol_0');
ossim_valid($torder,        OSS_LETTER,                                             'illegal: sSortDir_0');
ossim_valid($sec,           OSS_DIGIT,                                              'illegal: sEcho');

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


// Order by column
$orders_by_columns = array(
    '1' => "id $torder",                       // Order by Vulnerability ID
    '2' => "vuln_name $torder",                // Order by Vulnerability Name
    '4' => "family_name $torder, id ASC",      // Order by Family
    '5' => "category_name $torder, id ASC",    // Order by Category
);

if (array_key_exists($order, $orders_by_columns))
{
    $order = $orders_by_columns[$order];
}
else
{
    $order = 'enabled DESC, family_name ASC, id ASC';
}

$params = array($profile_id);

// Property filter
$filters = array(
    'where'    => "",
    'limit'    => "$from, $maxrows",
    'order_by' => "$order"
);

if (!empty($family_id)){
    $filters['where'] .= " AND t2.family = '$family_id'";
}

if (!empty($category_id)){
    $filters['where'] .= " AND t2.category = '$category_id'";
}

if (!empty($cve)){
    $filters['where'] .= " AND t1.cve_id LIKE '%$cve%'";
}

if (!empty($plugin)){
    $plugin = escape_sql($plugin, $conn);
    $filters['where'] .= " AND t1.name LIKE '%$plugin%'";
}

// Plugin data
$data    = array();
$p_list  = array();
$p_total = 0;

list($p_list, $p_total) = Vulnerabilities::get_plugins_by_profile($conn, $profile_id, $filters);

foreach ($p_list as $p_id => $p_values)
{
    $cve_list = explode(",", $p_values['cve']);
    $cves = '';
    foreach($cve_list as $cve_id){
        $cve_id = trim($cve_id);
        $cve_link = Vulnerabilities::get_cve_link($cve_id);

        if ($cve_link){
            $cves .= "<a href='".$cve_link."' target='_blank'>$cve_id</a><br/>";
        }
    }

    if (empty($cves)){
        $cves = '-';
    }

    $checked = ($p_values['enabled'] == 'Y') ? "checked='checked'" : "";
    $is_enabled = "<input class='plugin-enabled' data-script-id='".$p_values['id']."' type='checkbox' id='plugin-enabled-".$p_values['id']."' name='plugin-enabled-".$p_values['id']."' $checked/>";

    $_p_data = array(
        "DT_RowId"   => $p_values['id'],
        "DT_RowData" => array(
            'id'            => $p_values['id'],
            'cve'           => $p_values['cve'],
            'vuln_name'     => Util::htmlentities($p_values['vuln_name']),
            'family_name'   => Util::htmlentities($p_values['family_name']),
            'category_name' => Util::htmlentities($p_values['category_name']),
            'enabled'       => $p_values['enabled']
        ),
        $is_enabled,
        $p_values['id'],
        "<span data-script-id='".$p_values['id']."' class='plugin_info'>".$p_values['vuln_name']."</span>",
        $cves,
        $p_values['family_name'],
        $p_values['category_name']
    );

    $data[] = $_p_data;
}

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $p_total;
$response['iTotalDisplayRecords'] = $p_total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file get_plugins.php */
/* Location: /vulnmeter/get_plugins.php */
