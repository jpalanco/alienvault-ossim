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

<div style="border:1px solid #AAAAAA;line-height:24px;width:100%;text-align:center;background:url('../pixmaps/fondo_col.gif') 50% 50% repeat-x;color:#222222;font-size:12px;font-weight:bold">&nbsp;Shellcode Analysis </div>
<?php
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) {
#    base_header("Location: " . $BASE_urlpath . "/index.php");
#    exit();
#}
$id = ImportHTTPVar("id", VAR_DIGIT | VAR_LETTER);
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
/* Get the Payload from the database: */
$sql2 = "SELECT data_payload,binary_data FROM extra_data WHERE event_id=unhex('$id')";
$result2 = $db->baseExecute($sql2);
$myrow2 = $result2->baseFetchRow();
$result2->baseFreeRows();
//print $myrow2[0]."<br>";
$payload = str_replace("\n", "", $myrow2[0]);
$len = strlen($payload);
$counter = 0;
$tmp = tempnam("/tmp", "bin");
$fh = fopen($tmp, "w");
for ($i = 0; $i < ($len + 32); $i+= 2) {
    $counter++;
    if ($counter > ($len / 2)) {
        break;
    }
    $byte_hex_representation = ($payload[$i] . $payload[$i + 1]);
    //echo chr(hexdec($byte_hex_representation));
    fwrite($fh, chr(hexdec($byte_hex_representation)));
    //$bin = $bin + chr(hexdec($byte_hex_representation));
    
}
fclose($fh);
//file_put_contents($tmp, bin2hex($myrow2[1]));

$salida = shell_exec('sctest -Sgs 1000000000 < ' . $tmp);
$types = array(
    "int",
    "short",
    "long",
    "float",
    "double",
    "char"
);
//$salida = shell_exec('cat test1.txt');
$lines = split("\n", $salida);
//echo $lines[1];
if (preg_match("/failed/i", $lines[1]))
{
    echo _("The Shellcode couldn't be analyzed");
}
else
{
    print "<p><div class=code><pre>";
    for ($i = 1; $i < count($lines); $i++)
    {
        $l = $lines[$i];
        $l = str_replace("host=", "<b><font color = \"red\">host=</font></b>", $l);
        $l = str_replace("port=", "<b><font color = \"red\">port=</font></b>", $l);
        foreach($types as $t) {
            $l = str_replace($t, "<b><font color = \"blue\">" . $t . "</font></b>", $l);
        }
        print $l . "<br>";
    }
    print "</pre></div></p>";
	$tmp2 = tempnam("/tmp", "dot");
	@unlink('/tmp/shellcode.png');
	$salida2 = shell_exec('sctest -Sgs 1000000 -G ' . $tmp2 . ' < ' . $tmp);
	$salida3 = shell_exec('dot -Tpng -Gsize="400,300" ' . $tmp2 . ' -o /tmp/shellcode.png');
	echo "<a href=\"graph.php\"><center><img src=\"graphviz.png\"/><br><br><b>View Graph</b></center></a>";
	@unlink($tmp2);
}
@unlink($tmp);
?>
</body>
</html>
