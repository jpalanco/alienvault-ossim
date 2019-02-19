<?
ob_implicit_flush();
ini_set('include_path', '/usr/share/ossim/include');

require_once 'av_init.php';

$sched_id = intval($argv[1]);

$exceptions = array();

$db   = new ossim_db();
$conn = $db->connect();

//Getting host information
$query   = 'SELECT * FROM vuln_job_schedule WHERE id = ?';
$params  = array($sched_id);

$rs      = $conn->Execute($query, $params);

$targets = explode("\n", $rs->fields['meth_TARGET']);

foreach ($targets as $target)
{   
   if (preg_match('/^!\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $target))
   {
       $exceptions[] = $target;
   }
}

$exception_list = (!empty($exceptions)) ? ';' . implode(';', $exceptions) : '';

$output = Util::execute_command('/usr/bin/php /usr/share/ossim/www/vulnmeter/simulate.php ?', array($sched_id), 'array');

$data = @json_decode(implode('', $output), TRUE);

if (!empty($data))
{
    foreach ($data as $sensor => $job_data)
    {
        echo $sensor .'|' . implode(';', $job_data['ips']) . $exception_list . "\n";
    }
}
else
{
    echo "There is no data for the scheduled job\n";
}

$db->close($conn);

