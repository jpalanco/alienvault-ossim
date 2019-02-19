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

$action  = POST('action');
$data    = POST('data');


if ($action == 'check_file')
{
    $data = explode('###', base64_decode($data));
    
    $report_name = trim($data[0]);
    $filename    = trim($data[1]);
           
    ossim_valid($report_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _('Report name'));

    if (ossim_error()) 
    {
        echo 'error###'.ossim_get_error_clean();
        exit;
    }
    
    // Init PDF Report
    $pdfReport = new Pdf_report($report_name, 'P', 'A4', NULL, FALSE);
          
    if ( isset($filename) && !empty($filename) )
    {
        ossim_valid($filename, OSS_FILENAME, 'illegal:' . _('Filename'));
                      
        //Get complete path
        $path = $pdfReport->getpath().$filename;
                        
        $res = (!ossim_error() && file_exists($path) ) ? 1 : _('Unable to access to PDF Report');
        echo $res;
    }
}
elseif ($action == 'check_email')
{
    ossim_valid($data, OSS_MAIL_ADDR, 'illegal:' . _('Email address'));
    
    $res = (!ossim_error() ) ? 1 : ossim_get_error_clean();
    echo $res;
}
?>
