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
ini_set('memory_limit','1024M');

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
    
    if ( !$rs = & $conn->Execute( "SELECT crs.* FROM custom_report_scheduler crs, users usr WHERE crs.user=usr.login AND usr.enabled=1 AND usr.expires > '".gmdate("Y-m-d H:i:s")."' ORDER BY id" ) ) 
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
						'id'					=>$rs->fields['id'],
						'schedule_type'			=>$rs->fields['schedule_type'],
						'schedule_name'			=>$rs->fields['schedule_name'],
						'schedule'				=>$rs->fields['schedule'],
						'next_launch'			=>$rs->fields['next_launch'],
						'id_report'				=>$rs->fields['id_report'],
						'name_report'			=>$rs->fields['name_report'],
						'user'					=>$rs->fields['user'],
						'email'					=>$rs->fields['email'],
						'date_from'				=>$rs->fields['date_from'],
						'date_to'				=>$rs->fields['date_to'],
						'date_range'			=>$rs->fields['date_range'],
						'assets'				=>$rs->fields['assets'],
						'save_in_repository'    =>$rs->fields['save_in_repository']
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
    if ( !$rs = & $conn->Execute( "SELECT crs.* FROM custom_report_scheduler crs, users usr WHERE crs.user=usr.login AND usr.enabled=1 AND usr.expires > '".gmdate("Y-m-d H:i:s")."' AND unix_timestamp(next_launch) < unix_timestamp()" ) ) 
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
    if (!$rs = & $conn->Execute("SELECT * FROM users WHERE login='".$user."'")) 
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

    if (!$rs = & $conn->Execute("select value, AES_DECRYPT(value, ?) as dvalue from config where conf='remote_key'", $uuid)) 
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
		system("chown www-data:www-data ".dirname($name));
		system("chown www-data:www-data $name");
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


$server  	= trim(`grep ^admin_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
if ($server == "")  {
    $server = trim(`grep ^framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
}
$https   	= "yes";
$urlPdf  	= '/usr/share/ossim/www/tmp/scheduler';

$server     = 'http'.(($https=="yes") ? "s" : "").'://'.$server.'/ossim';

$cookieName = date('YmdHis').rand().'.txt';


system("clear");
$to_text .= "\n\n"._('Date (UTC)').': '.gmdate("Y-m-d H:i:s")."\n\n";
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
system("rm -f /tmp/logscheduler_err");

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
    
	$step1 = exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --save-cookies='.$cookieName.' --post-data="login='.$login.'" "'.$server.'/index.php" -O - 2>> /tmp/logscheduler_err',$output);
	
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
	
	if( $value['save_in_repository'] == '0' )
	{
		// Delete reports list
        echo "\n\tDelete reports list from: $dirUserPdf\n\n";
		clean(null,$dirUserPdf);
	}

	// Set name
	$str_to_replace = array(" ", ":", ".", "&");
	
	//var_dump($value["assets"]);
	
	if ( preg_match("/ENTITY\:(\d+)/", $value["assets"], $fnd)) 
	{
		$conn    = $db->connect();
		$e_name  = Acl::get_entity_name($conn,$fnd[1]);
		$assets  = "ENTITY: ".$e_name;
		$db->close($conn);
	}
	else{
		$assets  = $value['assets'];
	}
	
	
	$pdfNameEmail  = str_replace($str_to_replace, "_", $value['name_report'])."_".str_replace($str_to_replace, "_", $assets);
    
    $conn = $db->connect();
    
    $run_at = get_timestamp($conn, $user, gmdate("Y-m-d H:i:s"));
    $user_name = $conn->GetOne("SELECT name FROM users WHERE login='".$value["user"]."'");
    
    $db->close($conn);
	
    $subject_email = _("Report").": ".$value["schedule_name"]." ".$value['name_report']." "._("run at")." ".$run_at;
	$pdfName       = $pdfNameEmail."_".time();
    

    $body_email  = _("Run as").": ".$user_name."<br />";
    $body_email .= _("Run at").": ".$run_at."<br />";
    if($assets=="ALL_ASSETS") {
        $assets = _("All Assets");
    }
    $body_email .= _("Assets").": ".$assets."<br /><br />";
    $body_email .= _("Confidential Information - Do Not Distribute")."<br />"._("Copyright (c) AlienVault, LLC. All rights reserved.");
			
	$text    = _('Save to').':';
	$to_text = sprintf("\n%-16s", $text);
	
	$to_text .= $dirUserPdf.$pdfName.".pdf\n";		
					
	echo $to_text;
	
	// Customize parameters
	$params  ='scheduler=1&assets='.$value['assets'];
	$params .= ( $value['date_range'] == 'NULL' ) ? '&date_from='.$value['date_from'].'&date_to='.$value['date_to'].'&date_range=custom' : '&date_range='.$value['date_range'];
	  		
	// Run Report
	$output  = null;
	$step2   = exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?run='.$value['id_report'].'&'.$params.'" -O - 2>> /tmp/logscheduler_err', $output);
	
      	
	// Generate PDF
	$text    = _('Generating PDF').'...';
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
		exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/session/login.php?action=logout" -O /dev/null 2>> /tmp/logscheduler_err');
		exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --save-cookies='.$cookieName.' --post-data="login='.$login.'" "'.$server.'/index.php" -O - 2>> /tmp/logscheduler_err');
    
		$step3 = exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/report/wizard_run.php?pdf=true&extra_data=true&run='.$value['id_report'].'" -O '.$dirUserPdf.$pdfName.'.pdf 2>> /tmp/logscheduler_err', $output);
		
		// Send PDF by email
		
		$listEmails = ( !empty($value['email']) ) ? explode(';',$value['email']) : null;
		$email_ko   = array();
		$email_ok   = array();
						
		if ( is_array($listEmails) && !empty($listEmails) )
		{
			$text     = _('Sending E-mails').'...';
			$to_text  = sprintf("\n\t%s\n", $text);
			
			echo $to_text;
			
			$output   = null;
			
            exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/session/login.php?action=logout" -O /dev/null 2>> /tmp/logscheduler_err');
            exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --save-cookies='.$cookieName.' --post-data="login='.$login.'" "'.$server.'/index.php" -O - 2>> /tmp/logscheduler_err');
            
			foreach($listEmails as $value2)
			{
				$step4  = exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' --post-data="email='.$value2.'&pdfName='.$pdfName.'&pdfDir='.$dirUser.'&subject='.$subject_email.'&body='.$body_email.'" "'.$server.'/report/wizard_email_scheduler.php?format=email&run='.$pdfNameEmail.'" -O - 2>> /tmp/logscheduler_err',$output);
				
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
		$step5    = exec('chown -R "www-data" '.$dirUserPdf);
		
		$text     = _('Report processed');
		$to_text  = sprintf("\n%s", $text);
			
		echo $to_text;
  
		echo  "\n\n";
	
	}

	// Logout
	exec('wget -U "AV Report Scheduler ['.$id_sched.']" -t 1 --timeout=43200 --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='.$cookieName.' "'.$server.'/session/login.php?action=logout" -O /dev/null 2>> /tmp/logscheduler_err');
					
}


echo "\n"._('Report Scheduler completed')."\n\n";


// End
clean($cookieName);

?>
