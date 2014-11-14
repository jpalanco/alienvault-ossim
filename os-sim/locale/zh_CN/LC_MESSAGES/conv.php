<?php
$f = @file($argv[1]);
if (!$f) die;
foreach ($f as $line) {
  echo mb_convert_encoding($line,'HTML-ENTITIES','UTF-8');
}
?>
