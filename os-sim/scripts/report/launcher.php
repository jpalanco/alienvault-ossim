<?php
/**
*
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* @package    alienvault-wizard\Reports
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


ini_set("max_execution_time", 0);
ob_implicit_flush();
ini_set('include_path', '/usr/share/ossim/include');

require_once 'av_init.php';


// Get timestamp to send email
function get_timestamp ($dbconn, $login, $datetime) {

    $user_timezone = $dbconn->GetOne("SELECT timezone FROM users WHERE login='".$login."'");

    $tz = Session::get_timezone($user_timezone);

    return gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($datetime)+(3600*$tz));
}

function getScheduler($conn)
{
    $return  = array();

    $rs = $conn->Execute("SELECT crs.* FROM custom_report_scheduler crs, users usr WHERE crs.user=usr.login AND usr.enabled=1 AND usr.expires > '".gmdate('Y-m-d H:i:s', gmdate('U'))."' ORDER BY id");

    if (!$rs)
    {
        print $conn->ErrorMsg();
        exit();
    }
    else
    {
        while (!$rs->EOF)
        {
            $user_name = $conn->GetOne("SELECT name FROM `users` WHERE login = '".$rs->fields['user']."'");

            if( $user_name != "" )
            {
                $return[]=array(
                        'id'                    =>$rs->fields['id'],
                        'schedule_type'         =>$rs->fields['schedule_type'],
                        'schedule_name'         =>$rs->fields['schedule_name'],
                        'schedule'              =>$rs->fields['schedule'],
                        'next_launch'           =>$rs->fields['next_launch'],
                        'id_report'             =>$rs->fields['id_report'],
                        'name_report'           =>$rs->fields['name_report'],
                        'user'                  =>$rs->fields['user'],
                        'email'                 =>$rs->fields['email'],
                        'date_from'             =>$rs->fields['date_from'],
                        'date_to'               =>$rs->fields['date_to'],
                        'date_range'            =>$rs->fields['date_range'],
                        'assets'                =>$rs->fields['assets'],
                        'save_in_repository'    =>$rs->fields['save_in_repository'],
                        'file_type'             =>$rs->fields['file_type']
                );
            }
            else
            {
                $params = array($rs->fields['user']);

                $sql    = "DELETE FROM custom_report_scheduler WHERE user = ?";

                if ($conn->Execute($sql, $params) === false)
                {
                    print $conn->ErrorMsg();
                    exit;
                }
            }

            $rs->MoveNext();
        }
    }

    return $return;
}

function reScheduleBypassed($conn)
{
    $rs = $conn->Execute( "SELECT crs.* FROM custom_report_scheduler crs, users usr WHERE crs.user=usr.login AND usr.enabled=1 AND usr.expires > '".gmdate('Y-m-d H:i:s', gmdate('U'))."' AND unix_timestamp(next_launch) < unix_timestamp('".gmdate('Y-m-d H:i:s', gmdate('U'))."') AND next_launch <> '0000-00-00 00:00:00'");

    if (!$rs)
    {
        print $conn->ErrorMsg();
        exit();
    }
    else
    {
        while (!$rs->EOF)
        {
            $schedule    = array(
                'type'        => $rs->fields['schedule_type'],
                'next_launch' => $rs->fields['next_launch']
            );

            // Reprogramme "Run Once" passed report
            if ('O' === $schedule['type'])
            {
                $schedule['type'] = 'OR';
            }

            $rid         = $rs->fields['id'];
            $next_launch = updateNextLaunch($conn,$schedule,$rid);
            echo "\tRe-scheduling bypassed $rid from ".$rs->fields['next_launch']." to $next_launch\n";
            $rs->MoveNext();
        }
    }
}

function getUserWeb($conn, $user)
{
    $return = "";

    $rs = $conn->Execute("SELECT * FROM users WHERE login='".$user."'");

    if (!$rs)
    {
        print $conn->ErrorMsg();
        exit();
    }
    else
    {
        if(!$rs->EOF) {
            $return = $rs->fields["pass"];
        }
    }

    return $return;
}

function getKeyEncript($conn)
{
    $uuid = Util::get_encryption_key();

    $return = "";

    $rs = $conn->Execute("select value, AES_DECRYPT(value, ?) as dvalue from config where conf='remote_key'", array($uuid));

    if (!$rs)
    {
        print $conn->ErrorMsg();
        exit();
    }
    else if(!$rs->EOF) {
            $return = ($rs->fields["dvalue"]) ? $rs->fields["dvalue"] : $rs->fields["value"];
    }

    return $return;
}

function checkTimeExecute($date)
{
    if( substr($date,0,13) == gmdate("Y-m-d H") )
        return true;
    else
        return false;
}

function completionDate($date){

    $date = ( strlen($date) < 2 ) ? '0'.$date : $date;

    return $date;
}

function clean($cookieName,$dirUser=null)
{
    if( $dirUser!==null )
    {
        foreach(scandir($dirUser) as $value)
        {
            if( $value !='.' && $value!='..' && !is_dir($dirUser.'/'.$value) )
                @unlink($dirUser.'/'.$value);
        }
    }

    if($cookieName!==null)
        @unlink($cookieName);

}

function searchString($output,$info_text)
{
    if ( is_array ($output) )
    {
        foreach ($output as $value)
        {
            $pattern = "/".$info_text."/";
            if( preg_match($pattern, $value) )
                return true;
        }
    }

    return false;

}

function newFolder($name)
{
    if ( file_exists($name) )
        return false;
    else
    {
        @mkdir($name, 0755, true);
        Util::execute_command("chown www-data:www-data ?", array(dirname($name)));
        Util::execute_command("chown www-data:www-data ?", array($name));
        return true;
    }
}

//Last Day of Month
function lastDayOfMonth($month = '', $year = ''){
   $month = ( empty($month) ) ? date('m') : $month;
   $year  = ( empty($year) )  ? date('Y') : $year;

   $result = strtotime("{$year}-{$month}-01");
   $result = strtotime('-1 second', strtotime('+1 month', $result));

   return date('d', $result);
}


function updateNextLaunch($conn, $schedule, $id){

    switch($schedule['type'])
    {
        case 'O':
            $next_launch = '0000-00-00 00:00:00';
        break;

        // Reprogrammed "Run Once" passed report
        case 'OR':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 HOUR");
        break;

        case 'D':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 DAY");
        break;

        case 'W':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 WEEK");
        break;

        case 'M':
            $next_launch = Util::get_utc_date_calc($conn, $schedule['next_launch'], "1 MONTH");
        break;

        default:
            $next_launch = '0000-00-00 00:00:00';
        break;
    }

    // Update DB
    if ($conn->Execute("UPDATE custom_report_scheduler SET next_launch='".$next_launch."' WHERE id='".$id."'") === false)
    {
        print 'Error updating: ' . $conn->ErrorMsg() . '<br/>';
        return false;
    }

    return $next_launch;
}
// end functions

// Get database connection
$db   = new ossim_db();
$conn = $db->connect();

//Errors text
$info_text  = array( _('Wrong User & Password'), _('Invalid address'), _('No assets found') );


$server     = trim(Util::execute_command('grep ^admin_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="', FALSE, 'string'));
if ($server == "")  {
    $server = trim(Util::execute_command('grep ^framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="', FALSE, 'string'));
}
$https      = "yes";
$urlPdf     = '/usr/share/ossim/www/tmp/scheduler';

$server     = 'http'.(($https=="yes") ? "s" : "").'://'.$server.'/ossim';

$cookieName = date('YmdHis').rand().'.txt';


Util::execute_command("clear");
$to_text .= "\n\n"._('Date (UTC)').': '.gmdate('Y-m-d H:i:s', gmdate('U'))."\n\n";
$to_text .= _('Starting Report Scheduler')."...\n\n";


// Run reports
$report_list = getScheduler($conn);
$scheduled_reports = array();

$text     = _('Searching scheduled reports')."...\n";
$to_text .= sprintf("\n%s", $text);

echo $to_text;

$i = 0;

foreach ( $report_list as $k => $value)
{
    $run = checkTimeExecute($value['next_launch']);

    if ($run)
    {
        $i++;

        $scheduled_reports[$k] = $value;

        $text     = _("Adding")." ".$value['name_report']." "._("to scheduled queue").'...';
        $to_text  = sprintf("\n\t%s", $text);

        echo $to_text;


        // Update next launch
        $schedule=array(
                'type'        => $value['schedule_type'],
                'next_launch' => $value['next_launch'],
                'data'        => unserialize($value['schedule'])
        );

        $current_launch = $value['next_launch'];
        $next_launch    = updateNextLaunch($conn,$schedule,$value['id']);

        if ( $next_launch === false )
        {
            $text     = _('Failed to update the next launch: ').
            $to_text  = sprintf("\n\t%s", $text);
            $scheduled_reports[$k]['current_launch'] = "";
        }
        else
        {
            $next_launch_txt = ( $next_launch == '0000-00-00 00:00:00' ) ? "-" : $next_launch;
            $text            = _('Next launch: ').$next_launch_txt;
            $to_text         = sprintf("\n\t%s\n", $text);
            $scheduled_reports[$k]['current_launch'] = $current_launch;
        }

        echo $to_text;

    }
}

reScheduleBypassed($conn);

if ($i == 0)
{
    $text     = _("No reports found");
    $to_text  = sprintf("\n\t%s\n\n", $text);
    echo $to_text;
    exit;
}

$db->close($conn);

echo "\n\n";
Util::execute_command("rm -f /var/tmp/logscheduler_err");

foreach ( $scheduled_reports as $value )
{
    $id_sched = $value['id'];
    $output   = null;
    $to_text  = null;



    // Login
    $user         = $value['user'];

    $conn         = $db->connect();

    $pass         = getUserWeb($conn, $value['user']);

    $pass_sha     = $pass; // Don't know if pass is in md5 or sha256, external_login will try both

    $passEncript  = getKeyEncript($conn);

    $login        = base64_encode(Util::encrypt($user.'####'.$pass.'####'.$pass_sha, $passEncript));

    $uuid         = Session::get_secure_id($user);

    $db->close($conn);

    $cmd_login    = 'wget -U ? -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --save-cookies=? --post-data=? ? -O - 2>> /var/tmp/logscheduler_err';
    $params_login = array(
            'AV Report Scheduler ['.$id_sched.']',
            $cookieName,
            'login='.$login,
            $server.'/index.php'
    );
    $cmd_logout    = 'wget -U ? -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies=? ? -O /dev/null 2>> /var/tmp/logscheduler_err';
    $params_logout = array(
            'AV Report Scheduler ['.$id_sched.']',
            $cookieName,
            $server.'/session/login.php?action=logout'
    );

    $output = Util::execute_command($cmd_login, $params_login, 'array');

    $result = searchString($output,$info_text[0]);

    if ( $result == true )
    {
        $to_text = sprintf("\n%-15s\n\n", _('ERROR: Wrong User & Password'));
        echo $to_text;
        clean($cookieName);
        exit();
    }


    $r_data   = base64_decode($value['id_report']);
    $r_data   = explode('###',$r_data);

    $text     = _('Scheduled Report').': '. $value['name_report'].' - Created by: '.$value['user']." - Time: ".gmdate("H:i:s")." (UTC)\n";
    $to_text  = sprintf("%-20s", $text);

    echo $to_text;

    $text     = _('Schedule Method').':'." ".$value['schedule_name']." [".$value['current_launch']."(UTC)]";
    $to_text  = sprintf("\n%-20s", $text);

    echo $to_text;

    // Path to save PDF

    $dirUser    = $uuid.'/'.$value['id'].'/';
    $dirUserPdf = $urlPdf.'/'.$dirUser;

    newFolder($dirUserPdf);

    if($value['save_in_repository'] == '0')
    {
        // Delete reports list
        echo "\n\tDelete reports list from: $dirUserPdf\n\n";
        clean(null,$dirUserPdf);
    }

    // Set name
    $str_to_replace = array(" ", ":", ".", "&", "/");

    //var_dump($value["assets"]);

    if ( preg_match("/ENTITY\:(\d+)/", $value["assets"], $fnd))
    {
        $conn    = $db->connect();
        $e_name  = Acl::get_entity_name($conn,$fnd[1]);
        $assets  = "ENTITY: ".$e_name;
        $db->close($conn);
    }
    else
    {
        $assets = $value['assets'];
    }


    $pdfNameEmail  = str_replace($str_to_replace, '_', $value['name_report']).'_'.str_replace($str_to_replace, '_', $assets);

    $conn = $db->connect();

    $run_at = get_timestamp($conn, $user, gmdate('Y-m-d H:i:s', gmdate('U')));
    $user_name = $conn->GetOne("SELECT name FROM users WHERE login='".$value["user"]."'");

    $db->close();


    if($assets == "ALL_ASSETS") {
        $assets = _("All Assets");
    }


    $subject_email = '';
    $hostname = Util::get_default_hostname();
    $admin_ip = Util::get_default_admin_ip();

    if (!empty($hostname) && !empty($admin_ip)) {
        $subject_email = $hostname." (".$admin_ip."): ";
    }

    $subject_email .= $value['name_report']." [".$assets."]";
    $pdfName = $pdfNameEmail."_".time();


    $body_email  = "<b>"._("Report name").":</b> ".$value['name_report']."<br />";
    $body_email .= "<b>"._("Assets").":</b> ".$assets."<br />";
    $body_email .= "<b>"._("Schedule type").":</b> ".$value["schedule_name"]."<br />";
    $body_email .= "<b>"._("Run as").":</b> ".$user_name."<br />";
    $body_email .= "<b>"._("Run at").":</b> ".$run_at."<br /><br />";

    $body_email .= _("Confidential Information - Do Not Distribute")."<br />"._("Copyright (c) AlienVault, LLC. All rights reserved.");

    $text    = _('Save to').':';
    $to_text = sprintf("\n%-16s", $text);
    $extension = $value['file_type'] == 'xls' ? 'xlsx' : 'pdf';
    $file = "$dirUserPdf$pdfName.$extension";
    $to_text .= $file."\n";

    echo $to_text;

    // Customize parameters
    $params  ='scheduler=1&assets='.$value['assets'];
    $params .= ( $value['date_range'] == 'NULL' ) ? '&date_from='.$value['date_from'].'&date_to='.$value['date_to'].'&date_range=custom' : '&date_range='.$value['date_range'];
    // Run Report
    $cmd    = 'wget -U ? -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies=? ? -O - 2>> /var/tmp/logscheduler_err';
    $params = array(
            'AV Report Scheduler ['.$id_sched.']',
            $cookieName,
            $server.'/report/wizard_run.php?run='.$value['id_report'].'&'.$params
    );
    $output = Util::execute_command($cmd, $params, 'array');
    // Generate PDF
    $text    = _('Generating').' '.strtoupper(_($extension)).'...';
    $to_text = sprintf("\n\t%s", $text);

    echo $to_text;

    $result = searchString($output,$info_text[2]);

    if ( $result == true )
    {
        $to_text = sprintf("\t%-15s", _('ERROR to generate PDF (Assets not found)'));
        echo $to_text;
    }
    else
    {
        Util::execute_command($cmd_logout, $params_logout, 'array');
        Util::execute_command($cmd_login,  $params_login,  'array');

        //Get Token
        $cmd    = 'wget -U ? -t 1 --timeout=43200 --no-check-certificate --cookies=on --post-data="f_name=report_list_actions" --keep-session-cookies --load-cookies=? ? -O - 2>> /var/tmp/logscheduler_err';
        $p = array(
            'AV Report Scheduler ['.$id_sched.']',
            $cookieName,
            $server.'/session/token.php'
        );
        $output = Util::execute_command($cmd, $p, 'array');
        $output = json_decode($output[0]);

        $cmd    = 'wget -U ? -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies=? ? -O ? 2>> /var/tmp/logscheduler_err';
        $params = array(
                'AV Report Scheduler ['.$id_sched.']',
                $cookieName,
                $server.'/report/wizard_run.php?'.$value['file_type'].'=true&extra_data=true&token='.$output->data.'&run='.$value['id_report'],
                $file
                );

        $output = Util::execute_command($cmd, $params, 'array');

        // Send PDF by email

        $listEmails = ( !empty($value['email']) ) ? explode(';',$value['email']) : null;
        $email_ko   = array();
        $email_ok   = array();

        if ( is_array($listEmails) && !empty($listEmails) )
        {
            $text     = _('Sending E-mails').'...';
            $to_text  = sprintf("\n\t%s\n", $text);

            echo $to_text;

            Util::execute_command($cmd_logout, $params_logout, 'array');
            Util::execute_command($cmd_login,  $params_login,  'array');

            foreach($listEmails as $value2)
            {
                $cmd = 'wget -U ? -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies=? --post-data=? ? -O - 2>> /var/tmp/logscheduler_err';
                $params = array(
                        'AV Report Scheduler ['.$id_sched.']',
                        $cookieName,
                        'email='.$value2.'&type='.$value['file_type'].'&pdfName='.$pdfName.'&pdfDir='.$dirUser.'&subject='.$subject_email.'&body='.$body_email,
                        $server.'/report/wizard_email_scheduler.php?format=email&run='.$pdfNameEmail
                );

                $output = Util::execute_command($cmd, $params, 'array');

                $result = searchString($output,$info_text[1]);

                if ( $result == false )
                    $email_ok[] = $value2;
                else
                    $email_ko[] = $value2;

                $output = null;
            }

            if( count($email_ko) > 0 )
                $to_text = sprintf("\t%s\n", _('Invalid address').': '.implode(",",$email_ko));

            if( count($email_ok) > 0 )
                $to_text = sprintf("\t%s\n", _('PDF sent OK by email to').': '.implode(",",$email_ok));

            echo $to_text;
        }

        // Set appropiate permissions
        Util::execute_command('chown -R "www-data" ?', array($dirUserPdf));

        $text     = _('Report processed');
        $to_text  = sprintf("\n%s", $text);

        echo $to_text;

        echo  "\n\n";

    }

    // Logout
    Util::execute_command($cmd_logout, $params_logout);

}


echo "\n"._('Report Scheduler completed')."\n\n";


// End
clean($cookieName);

?>
