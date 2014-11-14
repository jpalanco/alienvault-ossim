<?php

if ( !ini_get('enable_dl') ) {
  exec("php -d enable_dl=On $argv[0]");
  exit;
}

require_once 'alienvault_api_sdk.php';

$x = 12;
$y = 105;
$g = alienvault_api_sdk::gcd($x,$y);

alienvault_api_sdk::sim_api_initialization();
$a = alienvault_api_sdk::sim_api_new();
alienvault_api_sdk::sim_api_login($a, "192.168.5.119", 40011, "admin", "alien4ever");
$b = alienvault_api_sdk::sim_api_request($a, "https://192.168.5.119:40011/av/api/1.0/config/sensors");

echo "$b\n";

?>
