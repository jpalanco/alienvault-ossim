<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/


require_once 'av_init.php';
require_once 'os_report_common.php';

Session::logcheck('report-menu', 'ReportsReportServer');

$_DEBUG = FALSE;

if ($_DEBUG) 
{
	$_POST = $_GET;
}


if (isset($_GET['data']) && !empty($_GET['data']))
{
    $data     = explode('###', base64_decode(GET('data')));
    
    $report_name = trim($data[0]);
    $filename    = trim($data[1]);
                
    ossim_valid($report_name,  OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _('Report name'));
    ossim_valid($filename, OSS_FILENAME, 'illegal:' . _('Filename'));
     
    $pdfReport = new Pdf_report($report_name, 'P', 'A4', NULL, FALSE);
        
    //Get complete path
    $path = $pdfReport->getpath().$filename;
            
    if (!ossim_error() && file_exists($path)) 
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        @unlink($path);
        exit();
    }
}
else
{
    /*echo "<pre>";
        print_r($_REQUEST);
    echo "</pre>";
    exit;
    */
    
    set_time_limit(0);
    ini_set('memory_limit', '2048M');
    ini_set('session.bug_compat_warn','off');
    
    $report_id = POST('report_id');
	$section   = POST('section');
    
    ossim_valid($report_id, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _('Report name'));
	ossim_valid($section,   'forensics, assets',                          'illegal:' . _('Report section'));

    if (ossim_error()) 
    {
        echo 'error###'.ossim_get_error_clean();
        exit;
    }
    
    $chk_parameters = check_parameters($report_id, $_REQUEST, $section);
    
    if ($chk_parameters['error'] == TRUE)
    {
        echo "error###<div style='padding-left: 15px; text-align:left;'>"._('We found the followings errors').':</div>';
        echo "<div style='padding-left: 15px; text-align:left;'>";
            echo "<div style='padding-left: 30px;'>".implode('</div><div style="padding-left: 30px;">', $chk_parameters['error_msg'])."</div>";
        echo "</div>";
        exit;
    }

    Session::logcheck('report-menu', 'ReportsReportServer');
                    
    $TBackground = "#7B7B7B"; 
    $TForeground = "#FFFFFF"; 
    $SBackground = "#8CC221"; 
    $SForeground = "#FFFFFF";  

    // Load css
    $styleCss = array(
        'Title'=>array(
            'Background'=> $TBackground,
            'Foreground'=> $TForeground,
        ),
        'Subtitle'=>array(
            'Background'=> $SBackground,
            'Foreground'=> $SForeground,
        )
    );

    
    // Make header - footer with replacements
    $footerContent = array(
        'left' =>'User: '.Session::get_session_user().' / [[date_y]]-[[date_m]]-[[date_d]] [[date_h]]:[[date_i]]:[[date_s]]',
        'right'=>'Page [[page_cu]] / [[page_nb]]'
    );
       
   
    $report_data = ($section == 'forensics') ? get_freport_data($report_id) : get_report_data($report_id);
          
    // Init PDF Report
    $pdfReport  = new Pdf_report($report_id, 'P', 'A4', NULL, FALSE);
            
    // Init html2pdf document
    //$header = ucwords(str_replace('_',' ',$pdfReport->getName()));
    $htmlPdfReport= new Pdf_html($report_id, $report_data['report_name'],'', '', $styleCss, $footerContent);
    
    // Include php per each sub-report
    $runorder = 1;

    // Close session to stop() feature
    $dDB['_shared'] = new DBA_shared($report_id);
    $dDB['_shared']->truncate(); 
    session_write_close();   
	
    foreach ($report_data['subreports'] as $r_key => $r_data)
    {
        //PDF Report with hidden modules
        if (!isset($_POST['sr_'.$r_data['id']]) && ( $report_id == $r_data["id"]) && file_exists($r_data['report_file']))
        {
            $subreport_id = $r_data['id'];
            
			if ($_DEBUG) 
			{
				echo $subreport_id.'='.$r_data['report_file']."<br>\n";
			}
			
            include($r_data['report_file']);
        }
        elseif (POST('sr_'.$r_data['id']) == 'on' && file_exists($r_data['report_file']))
        {
            sleep(1);
            $subreport_id = $r_data['id'];
            			
			if ($_DEBUG) 
			{
				echo $subreport_id.'='.$r_data['report_file']."<br>\n";
			}
			
            include($r_data['report_file']);
		}
        
        $runorder++;
    }   
    
    if ($_DEBUG) 
    {
        echo $htmlPdfReport->get();
    } 
    else 
    {
        // Generate pdf report
        $pdfReport->setHtml($htmlPdfReport->get());
        $pdfReport->getPdf('server');
    }
                  
    //Send email
    $email = $_POST['email'];
    if (isset($email) && !empty($email))
    {        		
		ossim_valid($_POST['email'], OSS_MAIL_ADDR, 'illegal:' . _('Email address'));

        if (ossim_error()) 
        {
            echo 'error###'.ossim_get_error_clean();
            exit;
        }
               
        $status = $pdfReport->sendPdfEmail($report_data['report_name'], $email);
		$file   = $pdfReport->getpath().$pdfReport->getNamePdf();
		@unlink($file);
                                                                        
        if($status != TRUE)
        {        
        	$message = _('Please check email configuration in Deployment -> AlienVault Center -> General Configuration and try again');
        	echo 'error###'._('Unable to send PDF report.').'<br/><br/>'.$message;        
        } 
        else
        {
            echo 'OK###'._('PDF Report has been sent successfully'); 
        }
    }
    else
    {
        echo $pdfReport->getNamePdf();
    }
}
?>