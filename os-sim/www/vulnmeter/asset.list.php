<?php 
require_once 'config.php';
$term = GET("term");
$autocomplete_keys = array('hosts_ips', 'nets_cidrs', 'sensors');
echo Autocomplete::get_autocomplete_jquery($dbconn, $autocomplete_keys, $term, 100);
?>

