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
<body style="padding:20px">

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
//$tmp = "/tmp/out";
//file_put_contents($tmp, bin2hex($myrow2[1]));

$tmpout = tempnam("/tmp", "bin");

Util::execute_command("sctest -Sgs 1000000 < ? > ?", array($tmp, $tmpout));

$types = array(
    "int",
    "short",
    "long",
    "float",
    "double",
    "char"
);
$lines = explode("\n",@file_get_contents($tmpout));
if (preg_match("/failed/i", $lines[1]))
{
?>
    <div id="errmsg"></div>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script>
        var __style = 'width: 800px; text-align:center; margin:0px auto';
        show_notification("errmsg", "<?php echo _("The shellcode analysis returned no data.<br>This could mean that the shellcode event is a false positive or that the payload does not contain enough information to analyse the shellcode") ?>", 'nf_info', 0, false, __style);
    </script>
<?php
}
else
{
    $maxlines = 100;
    print "<p><div class=code><pre>";
    $total = (count($lines)>$maxlines) ? $maxlines : count($lines);
    for ($i = 1; $i < $total; $i++)
    {
        $l = $lines[$i];
        $l = str_replace("host=", "<b><font color = \"red\">host=</font></b>", $l);
        $l = str_replace("port=", "<b><font color = \"red\">port=</font></b>", $l);
        foreach($types as $t) {
            $l = str_replace($t, "<b><font color = \"blue\">" . $t . "</font></b>", $l);
        }
        print $l . "<br>";
    }
    if ($total==$maxlines) print "[...]<br>";
    print "</pre></div></p>";
    $output_file = '/tmp/shellcode.png';
    $tmp2 = tempnam("/tmp", "dot");
    @unlink($output_file);
    Util::execute_command('sctest -Sgs 1000000 -G ? < ?', array($tmp2, $tmp));
    Util::execute_command('dot -Tpng -Gcharset=latin1 -Gsize="400,300" ? -o ?', array($tmp2, $output_file));
    if (file_exists($output_file))
    {
        $img = 'data:image/png;base64,' . base64_encode(file_get_contents($output_file));
        echo '<img src="'.$img.'" style="border: 1px solid #333333; padding:5px;width:99%"/>';
    }
	@unlink($tmp2);
	@unlink($output_file);
}
@unlink($tmp);
@unlink($tmpout);
?>
</body>
</html>
