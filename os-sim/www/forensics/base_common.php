<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/

/**
* Function list:
* - GetSensorIDs()
* - GetSensorName()
* - GetSensorSids()
* - GetSensorPluginSids()
* - BuildIPFormVars()
* - BuildSrcIPFormVars()
* - BuildDstIPFormVars()
* - BuildUniqueAddressLink()
* - BuildUniqueAlertLink()
* - BuildAddressLink()
* - AddCriteriaFormRow()
* - PrintPayloadChar()
* - PrintBase64PacketPayload()
* - PrintAsciiPacketPayload()
* - PrintHexPacketPayload()
* - PrintCleanHexPacketPayload()
* - PrintCleanPayloadChar()
* - PrintPacketPayload()
* - base_header()
*/


require_once 'av_init.php';

Session::logcheck($ossim_acid_aco_section, $ossim_acid_aco, $ossim_login_path);

function GetPerms($alias = "acid_event") {
    $perms_sql = "";
    $domain = Session::get_ctx_where();
    if ($domain != "") {
        $perms_sql .= " AND $alias.ctx in ($domain)";
    }
    // Asset filter
    $host_perms = Session::get_host_where();
    $net_perms = Session::get_net_where();

    if ($host_perms != "") {
        $perms_sql .= " AND ($alias.src_host in ($host_perms) OR $alias.dst_host in ($host_perms)";
        if ($net_perms != "") $perms_sql .= " OR $alias.src_net in ($net_perms) OR $alias.dst_net in ($net_perms))";
        else                  $perms_sql .= ")";
    }
    elseif ($net_perms != "") {
        $perms_sql .= " AND ($alias.src_net in ($net_perms) OR $alias.dst_net in ($net_perms))";
    }
    return $perms_sql;
}

function GetEntityName($ctx) {
    GLOBAL $entities;
    return (!empty($entities[$ctx])) ? $entities[$ctx] : _("Unknown");
}

function GetSensorIDs($db) {
    $sensor_ids = array();
    $result = $db->baseExecute("SELECT hex(sensor_id) FROM alienvault_siem.device");
    while ($myrow = $result->baseFetchRow()) {
        $sensor_ids[] = $myrow[0];
    }
    $result->baseFreeRows();
    return $sensor_ids;
}

function GetSensorName($sid, $db, $withip=true) {
    $name = "Unknown";
    $multiple = (preg_match("/\,/", $sid)) ? true : false;
    if ($multiple) $sid = preg_replace("/\s*\,.*/", "", $sid);
    $tmp_sql = (is_numeric($sid)) ? "SELECT ase.name,ifnull(inet6_ntoa(avs.device_ip),inet6_ntoa(ase.ip)) as ip,avs.interface FROM alienvault_siem.device avs LEFT JOIN alienvault.sensor ase ON avs.sensor_id=ase.id WHERE avs.id=$sid" : "SELECT name,inet6_ntoa(ip) as ip,'' as interface FROM alienvault.sensor WHERE id=UNHEX('$sid')";
    $tmp_result = $db->baseExecute($tmp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $name = ($myrow["name"]!="") ? $myrow["name"].($withip ? ' - '.$myrow["ip"] : '') : "N/A";
    }
    $tmp_result->baseFreeRows();
    return $name;
}

function GetSensorSids($db) {


    $sensors = array();
    $temp_sql = "
      SELECT GROUP_CONCAT(d.id separator ',') AS sensor_id_devices, hex(s.id) as sensor_id
      FROM alienvault_siem.device d, alienvault.sensor s
      WHERE d.sensor_id=s.id
      GROUP BY sensor_id
      ORDER BY d.id ASC;
    ";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow())
    {
        if (!isset($sensors[$myrow["sensor_id"]])) {
            $sensors[$myrow["sensor_id"]] = array();
        }
        $sensors[$myrow["sensor_id"]][] = $myrow["sensor_id_devices"];
    }
    $tmp_result->baseFreeRows();

    $temp_sql = "
      SELECT GROUP_CONCAT(d.id separator ',') AS sensor_ip_devices, INET6_NTOA(s.ip) as sensor_ip
      FROM alienvault_siem.device d, alienvault.sensor s
      WHERE d.sensor_id=s.id
      GROUP BY sensor_ip
      ORDER BY d.id ASC;
    ";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow())
    {
        if (!isset($sensors[$myrow["sensor_id"]])) {
            $sensors[$myrow["sensor_id"]] = array();
        }
        $sensors[$myrow["sensor_id"]][] = $myrow["sensor_id_devices"];
    }

    $tmp_result->baseFreeRows();
    return $sensors;
}

function GetSensorSidsNames($db) {
    $sensors = array();
    $temp_sql = "SELECT d.id,s.ip as sensor_ip FROM alienvault_siem.device d, alienvault.sensor s WHERE d.sensor_id=s.id";
    //echo $temp_sql;
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $sensors[$myrow["id"]] = @inet_ntop($myrow["sensor_ip"]);
    }
    $tmp_result->baseFreeRows();

    return $sensors;
}

function GetDeviceIPs($db) {
    $ips = array();
    $temp_sql = "SELECT * FROM alienvault_siem.device";
    //echo $temp_sql;
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $ips[$myrow["id"]] = @inet_ntop($myrow["device_ip"]);
    }
    $tmp_result->baseFreeRows();

    return $ips;
}

function GetSourceTypes($db) {
    $srctypes = array();
    $temp_sql = "SELECT * FROM alienvault.product_type ORDER BY name";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $srctypes[$myrow["id"]] = $myrow["name"];
    }
    $tmp_result->baseFreeRows();
    return $srctypes;
}

function GetSourceType($pid,$db) {
    $sourcetype = _("Unknown type");
    $temp_sql = "SELECT name FROM alienvault.product_type WHERE id=$pid";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $sourcetype = $myrow["name"];
    }
    $tmp_result->baseFreeRows();
    return $sourcetype;
}

function GetSourceTypeFromPluginID($pid,$db) {
    $sourcetype = _("Unknown type");
    $temp_sql = "SELECT product_type.name FROM alienvault.product_type,alienvault.plugin WHERE plugin.product_type=product_type.id AND plugin.id=$pid";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $sourcetype = $myrow["name"];
    }
    $tmp_result->baseFreeRows();
    return $sourcetype;
}

function GetPluginCategories($db) {
    $categories = array();
    $temp_sql = "select * from alienvault.category order by name";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $categories[$myrow["id"]] = str_replace("_"," ",$myrow["name"]);
    }
    $tmp_result->baseFreeRows();
    return $categories;
}

function GetPluginCategoryName($idcat, $db) {
    $name = $idcat;
    $temp_sql = "SELECT name FROM alienvault.category WHERE id=$idcat";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $name = str_replace("_"," ",$myrow[0]);
    }
    $tmp_result->baseFreeRows();
    return $name;
}

function GetPluginSubCategories($db,$categories) {
    $subcategories = array();
    foreach ($categories as $idcat => $namecat) {
        $temp_sql = "select * from alienvault.subcategory where cat_id=$idcat order by name";
        $tmp_result = $db->baseExecute($temp_sql);
        while ($myrow = $tmp_result->baseFetchRow()) {
            $subcategories[$idcat][$myrow["id"]] = str_replace("_"," ",$myrow["name"]);
        }
        $tmp_result->baseFreeRows();
    }
    return $subcategories;
}

function GetPluginSubCategoryName($idcat, $db) {
    $name = $idcat[1];
    $temp_sql = "SELECT name FROM alienvault.subcategory WHERE id=".$idcat[1]." and cat_id=".$idcat[0];
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $name = str_replace("_"," ",$myrow[0]);
    }
    $tmp_result->baseFreeRows();
    return $name;
}

function GetCategorySubCategory($pid,$sid,$db) {
    static $cachescat;
    if (isset($cachescat[$pid][$sid])) return $cachescat[$pid][$sid];
    $temp_sql = "SELECT c.name as cname,sc.name as scname FROM alienvault.plugin_sid p LEFT JOIN alienvault.category c ON p.category_id=c.id LEFT JOIN alienvault.subcategory sc ON p.subcategory_id=sc.id AND sc.cat_id=p.category_id WHERE p.plugin_id=$pid and p.sid=$sid";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $myrow[0] = $myrow["cname"] = str_replace("_"," ",$myrow["cname"]);
        $myrow[1] = $myrow["scname"] = str_replace("_"," ",$myrow["scname"]);
        $cachescat[$pid][$sid] = $myrow;
    } else {
        $cachescat[$pid][$sid] = array("","");
    }
    $tmp_result->baseFreeRows();
    return $cachescat[$pid][$sid];
}

function GetClosestNets($db,$id,$ip,$ctx,$limit) {
    $nets = array();
    $temp_sql = "SELECT hex(n.id) as id,n.name FROM alienvault.host_net_reference hn, alienvault.net n, alienvault.host h WHERE n.id=hn.net_id AND hn.host_id=h.id AND n.ctx=h.ctx AND h.id=UNHEX('$id') LIMIT $limit";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $nets[$myrow["id"]] = $myrow["name"];
    }
    if (empty($nets))
    {
        $temp_sql = "SELECT hex(nc.net_id), n.name  FROM alienvault.net_cidrs AS nc, alienvault.net AS n WHERE n.id = nc.net_id AND inet6_aton('$ip') >= nc.begin AND inet6_aton('$ip') <= nc.end AND n.ctx = UNHEX('$ctx') LIMIT $limit";
        $tmp_result = $db->baseExecute($temp_sql);
        while ($myrow = $tmp_result->baseFetchRow()) {
            $nets[$myrow["id"]] = $myrow["name"];
        }
    }
    $tmp_result->baseFreeRows();
    return $nets;
}

function GetAssetGroups($db,$id,$ip,$ctx,$limit) {
    $groups = array();
    $temp_sql = "SELECT hex(g.id) as id,g.name FROM alienvault.host_group_reference gr, alienvault.host_group g, alienvault.host h WHERE g.id=gr.host_group_id AND gr.host_id=h.id AND h.id=UNHEX('$id') LIMIT $limit";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $groups[$myrow["id"]] = $myrow["name"];
    }
    if (empty($groups))
    {
        $temp_sql = "SELECT hex(g.id) as id,g.name FROM alienvault.host_group_reference gr, alienvault.host_group g, alienvault.host h, alienvault.host_ip hi WHERE g.id=gr.host_group_id AND gr.host_id=h.id AND h.id=hi.host_id AND hi.ip=INET6_ATON('$ip') AND h.ctx=UNHEX('$ctx') LIMIT $limit";
        $tmp_result = $db->baseExecute($temp_sql);
        while ($myrow = $tmp_result->baseFetchRow()) {
            $groups[$myrow["id"]] = $myrow["name"];
        }
    }
    $tmp_result->baseFreeRows();
    return $groups;
}

function GetPluginGroups($db) {
    $pg = array();
    $temp_sql = "SELECT hex(group_id) as id,name FROM alienvault.plugin_group ORDER BY name";
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow()) {
        $pg[$myrow["id"]] = $myrow["name"];
    }
    $tmp_result->baseFreeRows();
    return $pg;
}

function GetPluginGroupName($pgid, $db) {
    $name = $pgid;
    $temp_sql = "SELECT name FROM alienvault.plugin_group WHERE group_id=unhex('$pgid')";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $name = $myrow[0];
    }
    $tmp_result->baseFreeRows();
    return $name;
}

function GetActivityName($aid, $db) {
    if ($aid==0) return _("ANY OTX IP Reputation");
    $tmp_sql = "SELECT descr FROM alienvault.reputation_activities WHERE id=$aid";
    $tmp_result = $db->baseExecute($tmp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $name = ($myrow["descr"]!="") ? $myrow["descr"] : "Unknown";
    }
    $tmp_result->baseFreeRows();
    return $name;
}

function GetPlugins($db) {
    $plugins  = array();
    #$temp_sql = "SELECT distinct plugin_id,name FROM ac_acid_event LEFT JOIN alienvault.plugin ON ac_acid_event.plugin_id=plugin.id WHERE 1 ".GetPerms('ac_acid_event');
    $temp_sql = "SELECT distinct plugin_id FROM ac_acid_event WHERE 1 ".GetPerms('ac_acid_event');
    $tmp_result = $db->baseExecute($temp_sql);
    while ($myrow = $tmp_result->baseFetchRow())
    {
        $plg = GetPluginName($myrow[0], $db);
        $plg = ($plg=="") ? $myrow[0] : $plg;

        $condition1 = OSSEC_MIN_PLUGIN_ID <= $myrow[0] && OSSEC_MAX_PLUGIN_ID >= $myrow[0];
        $condition2 = SNORT_MIN_PLUGIN_ID <= $myrow[0] && SNORT_MAX_PLUGIN_ID >= $myrow[0];

        if ($condition1 || $condition2)
        {
            $plg = preg_replace('/-[^\-]+$/','',$plg);
        }

        $plugins[$plg][] = $myrow[0];
    }
    $tmp_result->baseFreeRows();
    return $plugins;
}

function GetPluginName($pid, $db) {
    $name = $pid;
    $fpid = preg_replace("/,.*/","",$pid);
    if (preg_match("/(\d+)\-(\d+)/",$fpid,$match))
        $temp_sql = "SELECT name FROM alienvault.plugin WHERE id in (".$match[1].",".$match[2].")";
    else
        $temp_sql = "SELECT name FROM alienvault.plugin WHERE id=$fpid";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($myrow = $tmp_result->baseFetchRow()) {
        $name = $myrow[0];

    $pid_list = explode(',', $pid);

    $condition1 = OSSEC_MIN_PLUGIN_ID <= min($pid_list) && OSSEC_MAX_PLUGIN_ID >= max($pid_list);
    $condition2 = SNORT_MIN_PLUGIN_ID <= min($pid_list) && SNORT_MAX_PLUGIN_ID >= max($pid_list);

    if ($condition1 || $condition2)
    {
        $name = preg_replace('/-[^\-]+$/','',$name);
    }

    }
    $tmp_result->baseFreeRows();
    return $name;
}

function GetOssimNetworkGroups()
{
    $db     = new ossim_db(true);
    $conn   = $db->connect();

    $pg     = array();

    $groups = Net_group::get_list($conn, "", " ORDER BY name", TRUE);

    foreach ($groups as $ng)
    {
        $pg[$ng->get_id()] = $ng->get_name();
    }

    $db->close($conn);

    return $pg;
}

function GetNetworkGroupName($id,$db)
{
    $name       = _("Unknown");
    $temp_sql   = "SELECT name FROM alienvault.net_group WHERE id=unhex('$id')";
    $tmp_result = $db->baseExecute($temp_sql);

    if ($myrow = $tmp_result->baseFetchRow())
    {
        $name = $myrow[0];
    }

    $tmp_result->baseFreeRows();

    return $name;
}

function GetOssimHostGroups()
{
    $db     = new ossim_db(true);
    $conn   = $db->connect();

    $pg     = array();

    try
    {
        list($groups, $t) = Asset_group::get_list($conn, '', array("order_by" => "name"), TRUE);
    }
    catch (Exception $e)
    {
        return $pg;
    }

    foreach ($groups as $group_id => $hg)
    {
        $pg[$group_id] = $hg->get_name();
    }

    $db->close($conn);

    return $pg;

}

function GetOssimHostsFromHostGroups($hostgroup)
{
    $db   = new ossim_db(true);
    $conn = $db->connect();

    $pg   = array();

    try
    {
        $asset_group = new Asset_group($hostgroup);
        $asset_group->load_from_db($conn);

        $_hosts = $asset_group->get_hosts($conn, '', array(), TRUE);
        $hosts  = $_hosts[0];
    }
    catch (Exception $e)
    {
        echo $e->getMessage();

        return $pg;
    }

    foreach ($hosts as $hg)
    {
        $pg[] = $hg[2]; //  Array ( [0] => ID [1] => CTX [2] => IP [3] => Name )
    }

    $db->close();

    return $pg;
}

function GetPulses()
{
    $pulses = array();
    try
    {
        $otx                  = new Otx();
        list($total, $p_list) = $otx->get_pulse_list(array('page' => 0, 'page_rows' => -1));
        foreach ($p_list as $pulse)
        {
            array_walk_recursive($pulse['tags'],'Util::htmlentities');
            $pulse['id']          = strtoupper(preg_replace("/[^a-fA-F0-9]/",'',$pulse['id']));
            $pulses[$pulse['id']] = array(
                "name" => Util::htmlentities(trim($pulse['name']), ENT_NOQUOTES),
                "desc" => Util::htmlentities(trim($pulse['description'])),
                "tags" => $pulse['tags']
            );
        }
        asort($pulses);
    }
    catch(Exception $e) {}

    unset($p_list);
    return $pulses;
}

function GetPulseName($pulse_id)
{
    if (!isset($_SESSION['_pulse_names'])) $_SESSION['_pulse_names'] = array();
    if ($_SESSION['_pulse_names'][$pulse_id] != '')
    {
        return $_SESSION['_pulse_names'][$pulse_id];
    }
    global $otx_unknown;
    $name = $otx_unknown;

    if (empty($pulse_id))
    {
        return $name;
    }

    try
    {
        $otx   = new Otx();
        $pulse = $otx->get_pulse_detail(strtolower($pulse_id), TRUE);
        if (!empty($pulse['name']))
        {
            $name = Util::htmlentities(trim($pulse['name']), ENT_NOQUOTES);
        }
    }
    catch(Exception $e) {}

    $_SESSION['_pulse_names'][$pulse_id] = $name;
    return $name;
}

function BuildIPFormVars($ipaddr) {
    return '' . '&amp;ip_addr%5B0%5D%5B0%5D=+&amp;ip_addr%5B0%5D%5B1%5D=ip_src&amp;ip_addr%5B0%5D%5B2%5D=%3D' . '&amp;ip_addr%5B0%5D%5B3%5D=' . $ipaddr . '&amp;ip_addr%5B0%5D%5B8%5D=+&amp;ip_addr%5B0%5D%5B9%5D=OR' . '&amp;ip_addr%5B1%5D%5B0%5D=+&amp;ip_addr%5B1%5D%5B1%5D=ip_dst&amp;ip_addr%5B1%5D%5B2%5D=%3D' . '&amp;ip_addr%5B1%5D%5B3%5D=' . $ipaddr . '&amp;ip_addr%5B1%5D%5B8%5D=+&amp;ip_addr%5B1%5D%5B9%5D=+&amp;ip_addr_cnt=2';
}

function BuildSrcIPFormVars($ipaddr) {
    $url = "&amp;ip_addr%5B0%5D%5B0%5D=+&amp;ip_addr%5B0%5D%5B1%5D=ip_src&amp;ip_addr%5B0%5D%5B2%5D=%3D&amp;ip_addr%5B0%5D%5B3%5D=" . $ipaddr . "&amp;ip_addr%5B0%5D%5B8%5D=+";
    $url .= "&amp;ip_addr%5B0%5D%5B9%5D=+&amp;ip_addr_cnt=1";

    return $url;
}

function BuildDstIPFormVars($ipaddr) {
    $url = "&amp;ip_addr%5B0%5D%5B0%5D=+&amp;ip_addr%5B0%5D%5B1%5D=ip_dst&amp;ip_addr%5B0%5D%5B2%5D=%3D&amp;ip_addr%5B0%5D%5B3%5D=" . $ipaddr . "&amp;ip_addr%5B0%5D%5B8%5D=+";
    $url .= "&amp;ip_addr%5B0%5D%5B9%5D=+&amp;ip_addr_cnt=1";

    return $url;
}

function BuildUniqueAddressLink($addr_type, $raw = "", $style = "", $class = "") {
    return '<A HREF="base_stat_uaddr.php?sort_order=occur_d&addr_type=' . $addr_type . $raw . '" style="'.$style.'" class="'.$class.'">';
}

function BuildUniqueAlertLink($raw) {
    return '<A HREF="base_stat_alerts.php' . $raw . '">';
}

function BuildAddressLink($ipaddr, $netmask) {
    return '<A HREF="base_stat_ipaddr.php?ip=' . rawurlencode($ipaddr) . '&amp;netmask=' . $netmask . '">';
}

function BuildIDMVars($idmvalue, $field, $source="both") {
    $idm = "";
    if (preg_match("/userdomain|\@/",$field)) {
        $vals = explode("@",$idmvalue);
        if ($vals[0]!="") $idm .= '&idm_username%5B1%5D='.$source.'&idm_username%5B0%5D='.urlencode($vals[0]);
        if ($vals[1]!="") $idm .= '&idm_domain%5B1%5D='.$source.'&idm_domain%5B0%5D='.urlencode($vals[1]);
    } else {
        $idm .= '&idm_'.preg_replace("/^dst_|^src_/","",$field).'%5B1%5D='.$source.'&idm_'.preg_replace("/^dst_|^src_/","",$field).'%5B0%5D='.urlencode($idmvalue);
    }
    return $idm;
}

function BuildIDMLink($idmvalue, $field, $source="both") {
    require_once 'classes/menu.inc';

    $url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1'.BuildIDMVars($idmvalue, $field, $source), 'analysis', 'security_events', 'security_events');

    return '<a style="color:navy;" href="'.$url.'"></a>';
}

/* Adds another blank row to a given criteria element */
function AddCriteriaFormRow(&$submit, $submit_value, &$cnt, &$criteria_array, $max)
{
    $submit = $submit_value;
    ++$cnt;
    InitArray($criteria_array[$cnt - 1], $max, 0, "");
}

function PrintPayloadChar($char, $output_type) {
    if ($char >= 32 && $char <= 127) {
        if ($output_type == 2) return chr($char);
        else return Util::htmlentities(chr($char));
    } else return '.';
}

function PrintBase64PacketPayload($encoded_payload, $output_type) {
    /* strip out the <CR> at the end of each block */
    $encoded_payload = str_replace("\n", "", $encoded_payload);
    $payload = base64_decode($encoded_payload);
    $len = strlen($payload);
    $s = " " . gettext("length") . " = " . strlen($payload) . "\n";
    for ($i = 0; $i < strlen($payload); $i++) {
        if ($i % 16 == 0) {
            /* dump the ASCII characters */
            if ($i != 0) {
                $s = $s . '  ';
                for ($j = $i - 16; $j < $i; $j++) $s = $s . PrintPayloadChar(ord($payload[$j]) , $output_type);
            }
            $s = $s . sprintf("\n%03x : ", $i);
        }
        $s = $s . sprintf("%s ", bin2hex($payload[$i]));
    }
    /* print the remained of any ASCII chars */
    if (($i % 16) != 0) {
        for ($j = 0; $j < 16 - ($i % 16); $j++) $s = $s . '   ';
        $s = $s . '  ';
        for ($j = $len - ($i % 16); $j < $len; $j++) $s = $s . PrintPayloadChar(ord($payload[$j]) , $output_type);
    } else {
        $s = $s . '  ';
        for ($j = $len - 16; $j < $len && $j > 0; $j++) $s = $s . PrintPayloadChar(ord($payload[$j]) , $output_type);
    }
    return $s;
}

function json_readable_encode($in, $indent = 0, Closure $_escape = null)
{
    $schar = '    '; // \t

    if (__CLASS__ && isset($this))
    {
        $_myself = array($this, __FUNCTION__);
    }
    elseif (__CLASS__)
    {
        $_myself = array('self', __FUNCTION__);
    }
    else
    {
        $_myself = __FUNCTION__;
    }

    if (is_null($_escape))
    {
        $_escape = function ($str)
        {
            return str_replace(
                array('\\', '"', "\n", "\r", "\b", "\f", "\t", '/', '\\\\u'),
                array('\\\\', '\\"', "\\n", "\\r", "\\b", "\\f", "\\t", '\\/', '\\u'),
                $str);
        };
    }

    $out = '';

    foreach ($in as $key=>$value)
    {
        $out .= str_repeat($schar, $indent + 1);
        $out .= "\"".$_escape((string)$key)."\": ";

        if (is_object($value) || is_array($value))
        {
            $out .= "\n";
            $out .= call_user_func($_myself, $value, $indent + 1, $_escape);
        }
        elseif (is_bool($value))
        {
            $out .= $value ? 'true' : 'false';
        }
        elseif (is_null($value))
        {
            $out .= 'null';
        }
        elseif (is_string($value))
        {
            $out .= "\"" . $_escape($value) ."\"";
        }
        else
        {
            $out .= $value;
        }

        $out .= ",\n";
    }

    if (!empty($out))
    {
        $out = substr($out, 0, -2);
    }

    $out = str_repeat($schar, $indent) . "{\n" . $out;
    $out .= "\n" . str_repeat($schar, $indent) . "}";

    return $out;
}

function PrintAsciiPacketPayload($encoded_payload, $output_type, $json=false) {
    $ret_text = Util::htmlentities(wordwrap($encoded_payload, 144));
    if ($json)
    {
        $decoded_data = @json_decode($encoded_payload, TRUE);
        if (json_last_error() == JSON_ERROR_NONE)
        {
            $ret_text = Util::htmlentities(json_readable_encode($decoded_data));
        }
        else
        {
            $ret_text = Util::htmlentities($encoded_payload);
        }
    }
    $ret_text = str_replace("&amp;quot;", "\"", $ret_text);
    $ret_text = str_replace("&amp;#039;", "'", $ret_text);
    $ret_text = str_replace("&amp;gt;", "&gt;", $ret_text);
    $ret_text = str_replace("&amp;lt;", "&lt;", $ret_text);
    $ret_text = str_replace("&#039;", "'", $ret_text);
    //$ret_text = str_replace("&gt;", ">", $ret_text);
    //$ret_text = str_replace("&lt;", "<", $ret_text);
    $ret_text = preg_replace("/\&amp;(.)tilde;/", "\\1", $ret_text);
    return $ret_text;
}

function PrintHexPacketPayload($encoded_payload, $output_type) {
    /* strip out the <CR> at the end of each block */
    $encoded_payload = str_replace("\n", "", $encoded_payload);
    $payload = $encoded_payload;
    $len = strlen($payload);
    $s = " " . gettext("length") . " = " . (strlen($payload) / 2) . "\n";
    for ($i = 0; $i < strlen($payload); $i+= 2) {
        if ($i % 32 == 0) {
            /* dump the ASCII characters */
            if ($i != 0) {
                $s = $s . '  ';
                for ($j = $i - 32; $j < $i; $j+= 2) {
                    $t = hexdec($payload[$j] . $payload[$j + 1]);
                    $s = $s . PrintPayloadChar($t, $output_type);
                }
            }
            $s = $s . sprintf("\n%03x : ", $i / 2);
        }
        $s = $s . sprintf("%s%s ", $payload[$i], $payload[$i + 1]);
    }
    /* space through to align end of hex dump */
    if ($i % 32) for ($j = 0; $j < 32 - ($i % 32); $j+= 2) $s = $s . '   ';
    $s = $s . '  ';
    /* print the ASCII decode */
    if ($i % 32) $start = $len - ($i % 32);
    else $start = $len - 32;
    for ($j = $start; $j < $i; $j+= 2) {
        $t = hexdec($payload[$j] . $payload[$j + 1]);
        $s = $s . PrintPayloadChar($t, $output_type);
    }
    return $s;
}

// ************************************************************************************
function PrintCleanHexPacketPayload($encoded_payload, $output_type) {
    $len = strlen($encoded_payload);
    $s = '';
    $count = 0;
    for ($i = 0; $i < $len; $i+= 2) {
        /* dump the ASCII characters */
        $t = hexdec($encoded_payload[$i] . $encoded_payload[$i + 1]);
        $s_tmp = PrintCleanPayloadChar($t, $output_type);
        /* Join together several sequential non-ASCII characters displaying their count
        * in one line. It makes easyer to look through payload in plain display mode.
        * If current character is '<br>' and this is not last character of payload
        * increment counter, else output non-ASCII count and flush counter.
        */
        if (($s_tmp == '<br>') && !($i + 2 == $len)) {
            $count++;
        } else {
            if ($count > 1) $s.= '<DIV class="nonascii">[' . $count . ' non-ASCII characters]</DIV>';
            elseif ($count == 1) $s.= '<br>';
            $s.= $s_tmp;
            $count = 0;
        }
    }
    return $s;
}

function PrintCleanPayloadChar($char, $output_type) {
    if ($char >= 32 && $char <= 127) {
        if ($output_type == 2) return chr($char);
        else return Util::htmlentities(chr($char));
    } else return '<br>';
}

// ************************************************************************************
function PrintPacketPayload($data, $encode_type, $output_type, $json=false) {
    if ($output_type == 1) printf("\n<PRE class='nowrapspace'>\n");
    /* print the packet based on encoding type */;
    if ($encode_type == "1") $payload = PrintBase64PacketPayload($data, $output_type);
    else if ($encode_type == "0") {
        if (isset($_GET['asciiclean']) && ($_GET['asciiclean'] == 1) || ((isset($_COOKIE['asciiclean']) && $_COOKIE['asciiclean'] == "clean") && (!isset($_GET['asciiclean'])))) {
            // Print clean ascii display
            $payload = PrintCleanHexPacketPayload($data, $output_type);
        } else {
            $payload = PrintHexPacketPayload($data, $output_type);
        }
    } else if ($encode_type == "2") $payload = PrintAsciiPacketPayload($data, $output_type, $json);
    if ($output_type == 1) echo "$payload\n</PRE>\n";
    return $payload;
}

function GetNewResultID($submit, &$seq, &$id) {
    /* extract the sid and cid from the $submit variable of the form
    #XX-XX
    |   |
    |   |------ hex id
    |---------- sequence number of DB lookup
    */
    preg_match("/.*#(\d+)-(.*)/",$submit,$tmp);
    /* Since the submit variable is not cleaned do so here: */
    $seq = CleanVariable($tmp[1], VAR_DIGIT);
    $id  = CleanVariable($tmp[2], VAR_DIGIT | VAR_LETTER);
}

function base_header($url) {
    header($url);
    exit;
}

function get_utc_unixtime($db,$date) {
  $unix = strtotime($date);
  $db->baseExecute("SET SESSION time_zone='+00:00'");
  $res = $db->baseExecute("select TO_SECONDS('$date')-62167219200+TO_SECONDS(UTC_TIMESTAMP())-TO_SECONDS(NOW())");
  //$res = $db->baseExecute("select UNIX_TIMESTAMP('$date')");
  if ($row = $res->baseFetchRow()) {
    $unix = $row[0];
    $res->baseFreeRows();
  }
  return $unix;
}

// number format
function format_cash($cash) {
    // strip any commas
    $cash = (0 + str_replace('.', '', str_replace(',', '', $cash)));

    // make sure it's a number...
    if(!is_numeric($cash)){ return $cash;}

    // filter and format it
    if($cash>1000000){
        return round(($cash/1000000),1).' M';
    }elseif($cash>1000){
        return round(($cash/1000),1).' K';
    }
    return number_format($cash);
}

// rep color char
function getreptooltip($prio,$rel,$act,$ip="") {
    if (intval($prio)==0) return "";
    $reptxt = _("IP Priority").": <img src='../forensics/bar2.php?value=$prio&max=9&range=1' border='0' align='absmiddle' style='width:14mm'><br>"._("IP Reliability").": <img src='../forensics/bar2.php?value=$rel&max=9' border='0' align='absmiddle' style='width:14mm'><br>"._("IP Activity").": <b>".str_replace(";", ", ", $act)."</b>";
    return $reptxt;
}

// rep color char
function getrepimg($prio,$rel,$act,$ip="") {
    if (intval($prio)==0) return gettext("N/A");
    $reptxt = _("IP Priority").": <img src='../forensics/bar2.php?value=$prio&max=9&range=1' border='0' align='absmiddle' style='width:14mm'><br>"._("IP Reliability").": <img src='../forensics/bar2.php?value=$rel&max=9' border='0' align='absmiddle' style='width:14mm'><br>"._("IP Activity").": <b>".str_replace(";", ", ", $act)."</b>";
    $reptxt .= "<p style='margin:0px;text-align:right;'><strong>"._("Click - More Info")."</strong></p>";
    if ($ip!="") {
        $link   = Reputation::getlabslink($ip);
        $class  = "riskinfo trlnk";
        $target = "target='lab'";
    }
    else {
        $link   = "javascript:;";
        $class  = "riskinfo";
        $target = "";
    }
    $lnk = "<a href='$link' $target class='$class' style='text-decoration:none' txt='".Util::htmlentities($reptxt)."'><img class='otx' align='absmiddle' border='0' src='../pixmaps/rep_icon.png'/></a>";
    return $lnk;
}

function getrepbgcolor($prio,$style=0) {
    if (intval($prio)==0) return "";
    if ($prio<=2)     return ($style) ? "style='background-color:#fcefcc'" : "bgcolor='#fcefcc'";
    elseif ($prio<=6) return ($style) ? "style='background-color:#fde5d6'" : "bgcolor='#fde5d6'";
    else              return ($style) ? "style='background-color:#fccece'" : "bgcolor='#fccece'";
}

function formatUUID($uuid) {
    return (!preg_match("/-/",$uuid)) ? strtolower(preg_replace("/(........)(....)(....)(....)(.*)/","\\1-\\2-\\3-\\4-\\5",$uuid)) : strtolower(str_replace("-","",$uuid));
}

function formatMAC($mac) {
    if (empty($mac)) return $mac;
    return preg_replace("/(..)(..)(..)(..)(..)(..)/","\\1:\\2:\\3:\\4:\\5:\\6",str_pad(strtoupper(bin2hex($mac)), 12, "0", STR_PAD_LEFT));
}

// Used in plot graph
function thousands_locale() {
    $locale = ( isset($_COOKIE['locale']) ?
            $_COOKIE['locale'] :
            $_SERVER['HTTP_ACCEPT_LANGUAGE']
    );
    $languages = explode(",",$locale);
    switch($languages[0]) {
        case 'es-es':
        case 'de-de':
        case 'es-mx':
            $thousands = '.';
            break;
        default:
            $thousands = ',';
    }
    return $thousands;
}
