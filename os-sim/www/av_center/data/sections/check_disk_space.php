<?php
define("GB",1024*1024*1024);
define("ERROR_LIMIT",5);//5GB
define("WARNING_LIMIT",15);//15GB
require_once (dirname(__FILE__) . '/../../config.inc');
$data = array();
$system_id = POST("system_id");

try
{
    $st = Av_center::get_system_status($system_id, 'general', true);
}
catch (\Exception $e)
{
    echo 'error###' . $e->getMessage();
    exit();
}

if (!empty($st["disk"]))
{
        $disk = array_shift($st["disk"]);
	$data["limit"] = ERROR_LIMIT;
	$data["percent_avail"] = round(100 - $disk['percent_used'],1);
//in GB
	$data["size_avail"] = round($disk['free'] / GB, 1);
//text should be verified in ./locale/es/LC_MESSAGES/ossim.po it may be modified
	if ($data["size_avail"]<ERROR_LIMIT) {
		$data["message"] = sprintf(_("You need at least 1%% (5GB) of free disk space to perform this update. You currently have %s%% (%sGB) available. To free up more disk space, you can clear old system logs and/or archive old event logs. For additional help, please contact AlienVault Support"), ERROR_LIMIT, $data["size_avail"]);
//5GB
	} elseif ($data["size_avail"]<WARNING_LIMIT) {
		$data["message"] = sprintf(_("You need at least 1%% (5GB) of free disk space to perform this update. You currently have %s%% (%sGB) available. To prevent potential upgrade issues, we recommend that you free up some additional space by clearing old system logs and/or archiving old event logs. For additional help, please contact AlienVault Support."),WARNING_LIMIT, $data["size_avail"]);
	}
}

echo json_encode($data);
