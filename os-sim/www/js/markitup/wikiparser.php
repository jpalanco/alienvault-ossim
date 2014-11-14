<?php

//
// Simple WikiRetriever usage example
//
require_once 'av_init.php';

Session::useractive();

	
$wiki = new Wikiparser();

$txt = POST('doctext');

if(!empty($txt))
{
	$output = $wiki->parse($txt);
	
}

echo $output;

?>
