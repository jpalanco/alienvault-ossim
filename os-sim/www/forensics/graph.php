<?php
/*******************************************************************************
** Copyright (C) 2008 Alienvault
********************************************************************************
** Authors:
********************************************************************************
** Jaime Blasci <jaime.blasco@alienvault.com>
**
********************************************************************************
*/

require_once 'av_init.php';

require ("base_conf.php");
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <TITLE>Forensics Console : Alert</TITLE>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>">
</head>
<body>

<div style="border:1px solid #AAAAAA;line-height:24px;width:100%;text-align:center;background:url('../pixmaps/fondo_col.gif') 50% 50% repeat-x;color:#222222;font-size:12px;font-weight:bold">&nbsp;Shellcode Analysis </div><br>
<?php
$file = '/tmp/shellcode.png';
if (file_exists($file))
{
    $img = 'data:image/png;base64,' . base64_encode(file_get_contents($file));
	echo '<img src="'.$img.'" style="border: 1px solid black; padding:5px;width:99%"/>';
}
else
{
	echo _("The Shellcode couldn't be analyzed");
}
?>
