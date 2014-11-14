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


//Forensics Report Data ( Assets - Reports) 
function get_freport_data($id = NULL)
{   
    $date_from = date('Y-m-d', strtotime('-10 year'));
    $date_to   = date('Y-m-d');
    
    $reports['Events_Report'] = array('report_name' => _('SIEM Events Report'),
        'report_id'     => 'Events_Report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'     => array('id'  => 'title_page',     'name' => _('Title Page'),         'report_file' => 'os_reports/Forensics/titlepage.php'),
            'Events_Report'  => array('id'  => 'Events_Report',  'name' => _('SIEM Events Report'), 'report_file' => 'os_reports/Forensics/Events_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Events'),
            
            array('name' => 'date_from',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'date_to',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['UniqueEvents_Report'] = array('report_name' => _('SIEM Unique Events Report'),
        'report_id'     => 'UniqueEvents_Report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'           => array('id'  => 'title_page',           'name' => _('Title Page'),                'report_file' => 'os_reports/Forensics/titlepage.php'),
            'UniqueEvents_Report'  => array('id'  => 'UniqueEvents_Report',  'name' => _('SIEM Unique Events Report'), 'report_file' => 'os_reports/Forensics/UniqueEvents_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Unique_Events'),
            
            array('name' => 'date_from',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'date_to',    
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['Sensors_Report'] = array('report_name' => _('SIEM Sensors Report'),
        'report_id'     => 'Sensors_Report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'      => array('id'  => 'title_page',      'name' => _('Title Page'),           'report_file' => 'os_reports/Forensics/titlepage.php'),
            'Sensors_Report'  => array('id'  => 'Sensors_Report',  'name' => _('SIEM Sensors Report'),  'report_file' => 'os_reports/Forensics/Sensors_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Sensors'),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['UniqueAddress_Report1'] = array('report_name' => _('SIEM Unique Source Addresses Report'),
        'report_id'     => 'UniqueAddress_Report1',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'             => array('id'  => 'title_page',              'name' => _('Title Page'),                           'report_file' => 'os_reports/Forensics/titlepage.php'),
            'UniqueAddress_Report1'  => array('id'  => 'UniqueAddress_Report1',   'name' => _('SIEM Unique Source Addresses Report'),  'report_file' => 'os_reports/Forensics/UniqueAddress_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Unique_Address'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 1),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['UniqueAddress_Report2'] = array('report_name' => _('SIEM Unique Destination Addresses Report'),
        'report_id'     => 'UniqueAddress_Report2',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'             => array('id'  => 'title_page',             'name' => _('Title Page'),                                'report_file' => 'os_reports/Forensics/titlepage.php'),
            'UniqueAddress_Report2'  => array('id'  => 'UniqueAddress_Report2',  'name' => _('SIEM Unique Destination Addresses Report'),  'report_file' => 'os_reports/Forensics/UniqueAddress_Report.php')
        ),
        'parameters'    => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Unique_Address'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 2),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['SourcePort_Report0'] = array('report_name' => _('SIEM Source Port Report (TCP/UDP)'),
        'report_id'     => 'SourcePort_Report0',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'          => array('id'  => 'title_page',           'name' => _('Title Page'),                         'report_file' => 'os_reports/Forensics/titlepage.php'),
            'SourcePort_Report0'  => array('id'  => 'SourcePort_Report0',   'name' => _('SIEM Source Port Report (TCP/UDP)'),  'report_file' => 'os_reports/Forensics/SourcePort_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Source_Port'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 0),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['SourcePort_Report1'] = array('report_name' => _('SIEM Source Port Report (TCP)'),
        'report_id'     => 'SourcePort_Report1',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'          => array('id'  => 'title_page',           'name' => _('Title Page'),                     'report_file' => 'os_reports/Forensics/titlepage.php'),
            'SourcePort_Report1'  => array('id'  => 'SourcePort_Report1',   'name' => _('SIEM Source Port Report (TCP)'),  'report_file' => 'os_reports/Forensics/SourcePort_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Source_Port'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 1),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );

    $reports['SourcePort_Report2'] = array('report_name' => _('SIEM Source Port Report (UDP)'),
        'report_id'     => 'SourcePort_Report2',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'          => array('id'  => 'title_page',           'name' => _('Title Page'),                     'report_file' => 'os_reports/Forensics/titlepage.php'),
            'SourcePort_Report2'  => array('id'  => 'SourcePort_Report2',   'name' => _('SIEM Source Port Report (UDP)'),  'report_file' => 'os_reports/Forensics/SourcePort_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Source_Port'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 2),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['DestinationPort_Report0'] = array('report_name' => _('SIEM Destination Port Report (TCP/UDP)'),
        'report_id'     => 'DestinationPort_Report0',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'               => array('id'  => 'title_page',               'name' => _('Title Page'),                              'report_file' => 'os_reports/Forensics/titlepage.php'),
            'DestinationPort_Report0'  => array('id'  => 'DestinationPort_Report0',  'name' => _('SIEM Destination Port Report (TCP/UDP)'),  'report_file' => 'os_reports/Forensics/DestinationPort_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Destination_Port'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 0),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['DestinationPort_Report1'] = array('report_name'   => _('SIEM Destination Port Report (TCP)'),
        'report_id'     => 'DestinationPort_Report1',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'               => array('id'  => 'title_page',               'name' => _('Title Page'),                          'report_file' => 'os_reports/Forensics/titlepage.php'),
            'DestinationPort_Report1'  => array('id' => 'DestinationPort_Report1',   'name' => _('SIEM Destination Port Report (TCP)'),  'report_file' => 'os_reports/Forensics/DestinationPort_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',                        
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',                        
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Destination_Port'),
            
            array('name' => 'Type',
                  'id'   => 'Type',                        
                  'type' => 'hidden', 
                  'default_value' => 1),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',                        
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',                        
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['DestinationPort_Report2'] = array('report_name'   => _('SIEM Destination Port Report (UDP)'),
        'report_id'     => 'DestinationPort_Report2',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'               => array('id'  => 'title_page',               'name' => _('Title Page'),                          'report_file' => 'os_reports/Forensics/titlepage.php'),
            'DestinationPort_Report2'  => array('id' => 'DestinationPort_Report2',   'name' => _('SIEM Destination Port Report (UDP)'),  'report_file' => 'os_reports/Forensics/DestinationPort_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Destination_Port'),
            
            array('name' => 'Type',
                  'id'   => 'Type',
                  'type' => 'hidden', 
                  'default_value' => 2),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['UniquePlugin_Report'] = array('report_name'   => _('SIEM Unique Data Sources Report'),
        'report_id'     => 'UniquePlugin_Report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'           => array('id' => 'title_page',            'name' => _('Title Page'),                       'report_file' => 'os_reports/Forensics/titlepage.php'),
            'UniquePlugin_Report'  => array('id' => 'UniquePlugin_Report',   'name' => _('SIEM Unique Data Sources Report'),  'report_file' => 'os_reports/Forensics/UniquePlugin_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Unique_Plugin'),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['UniqueCountryEvents_Report'] = array('report_name' => _('SIEM Unique Country Events Report'),
        'report_id'     => 'UniqueCountryEvents_Report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'                  => array('id'  => 'title_page',                  'name' => _('Title Page'),                         'report_file' => 'os_reports/Forensics/titlepage.php'),
            'UniqueCountryEvents_Report'  => array('id'  => 'UniqueCountryEvents_Report',  'name' => _('SIEM Unique Country Events Report'),  'report_file' => 'os_reports/Forensics/UniqueCountryEvents_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',
                  'type' => 'hidden', 
                  'default_value' => 'Security_DB_Unique_Country_Events'),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    $reports['UniqueIPLinks_Report'] = array('report_name' => _('SIEM Unique IP Links Report'),
        'report_id'     => 'UniqueIPLinks_Report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'            => array('id'  => 'title_page',           'name' => _('Title Page'),                   'report_file' => 'os_reports/Forensics/titlepage.php'),
            'UniqueIPLinks_Report'  => array('id'  => 'UniqueIPLinks_Report', 'name' => _('SIEM Unique IP Links Report'),  'report_file' => 'os_reports/Forensics/UniqueIPLinks_Report.php')
        ),
        'parameters' => array(
            array('name' => 'reportUser',
                  'id'   => 'reportUser',
                  'type' => 'hidden', 
                  'default_value' => $_SESSION['_user']),
            
            array('name' => 'reportUnit',
                  'id'   => 'reportUnit',
                  'type' => 'hidden', 
                  'default_value' => 'SIEM_Events_Unique_IP_Links'),
            
            array('name' => 'datefrom',
                  'id'   => 'date_from',
                  'type' => 'hidden', 
                  'default_value' => $date_from),
                  
            array('name' => 'dateto',
                  'id'   => 'date_to',
                  'type' => 'hidden', 
                  'default_value' => $date_to)
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 0
    );


    if ($id == NULL)
    {
        ksort($reports);
        return $reports;
    }
    else
    {
        return  (!empty($reports[$id]) ? $reports[$id] : array()); 
    }
}

//Report Data ( Assets - Reports)
function get_report_data($id = NULL)
{      
    $conf = $GLOBALS['CONF'];
    $conf = (!$conf) ? new Ossim_conf() : $conf;

    
    $y = strftime('%Y', time() - ((24 * 60 * 60) * 30));
    $m = strftime('%m', time() - ((24 * 60 * 60) * 30));
    $d = strftime('%d', time() - ((24 * 60 * 60) * 30));
    
    $reports['asset_report'] = array('report_name' => _('Asset Details'),
        'report_id'     => 'asset_report',
        'type'          => 'external',
        'link_id'       => 'link_ar_asset',
        'link'          => '',
        'parameters'    => array(
            array('name' => _('Host Name/IP/Network'),
                  'id'   => 'ar_asset',
                  'type' => 'asset', 
                  'default_value' => '')
        ),       
        'access'        => Session::menu_perms('environment-menu', 'PolicyHosts') || Session::menu_perms('environment-menu', 'PolicyNetworks'),
        'send_by_email' => 0
    );
    
    
    $status_values = array(
        'All'      => array ('text' => _('All')), 
        'Open'     => array ('text' => _('Open')),  
        'Assigned' => array ('text' => _('Assigned')),
        'Studying' => array ('text' => _('Studying')),
        'Waiting'  => array ('text' => _('Waiting')),
        'Testing'  => array ('text' => _('Testing')),
        'Closed'   => array ('text' => _('Closed'))
    );
                           
    $types_values =  array(
        'ALL'                     => array ('text' => _('ALL')), 
        'Expansion Virus'         => array ('text' => _('Expansion Virus')), 
        'Corporative Nets Attack' => array ('text' => _('Corporative Nets Attack')),
        'Policy Violation'        => array ('text' => _('Policy Violation')), 
        'Security Weakness'       => array ('text' => _('Security Weakness')), 
        'Net Performance'         => array ('text' => _('Net Performance')),
        'Applications and Systems Failures'  => array ('text' => _('Applications and Systems Failures')),
        'Anomalies'                          => array ('text' => _('Anomalies')),
        'Nessus Vulnerability'               => array ('text' => _('Nessus Vulnerability'))
    );



    $priority_values = array(
        'High'    => _('High'), 
        'Medium'  => _('Medium'), 
        'Low'     => _('Low')
    );


    $reports['tickets_report'] = array('report_name' => _('Tickets Report'),
        'report_id'     => 'tickets_report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'     => array('id' => 'title_page',    'name' => _('Title Page'),    'report_file' => 'os_reports/Common/titlepage.php'),                       
             'alarm'         => array('id' => 'alarm',         'name' => _('Alarm'),         'report_file' => 'os_reports/Tickets/Alarm.php'),
             'event'         => array('id' => 'event',         'name' => _('Event'),         'report_file' => 'os_reports/Tickets/Event.php'),
             'metric'        => array('id' => 'metric',        'name' => _('Metric'),        'report_file' => 'os_reports/Tickets/Metric.php'),
             'anomaly'       => array('id' => 'anomaly',       'name' => _('Anomaly'),       'report_file' => 'os_reports/Tickets/Anomaly.php'),
             'vulnerability' => array('id' => 'vulnerability', 'name' => _('Vulnerability'), 'report_file' => 'os_reports/Tickets/Vulnerability.php')
        ),
        'parameters'    => array(
            array('name'          => _('Date Range'),
                  'date_from_id'  => 'tr_date_from',
                  'date_to_id'    => 'tr_date_to',
                  'type'          => 'date_range',
                  'default_value' => array('date_from' => $y.'-'.$m.'-'.$d, 'date_to' => date('Y').'-'.date('m').'-'.date('d') )),          
            array('name'   => _('Status'),
                  'id'     => 'tr_status',
                  'type'   => 'select',
                  'values' => $status_values),
                  
            array('name'   => _('Type'),
                  'id'     => 'tr_type',
                  'type'   => 'select',
                  'values' => $types_values),
                  
            array('name'   => _('Priority'),
                  'id'     => 'tr_priority',
                  'type'   => 'checkbox',
                  'values' => $priority_values),
        ),
        'access'        => Session::menu_perms('analysis-menu', 'IncidentsIncidents'),
        'send_by_email' => 1
    );
    
        
    $reports['alarm_report'] = array('report_name' => _('Alarms Report'),
        'report_id'     => 'alarm_report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'        => array('id' => 'title_page',          'name' => _('Title Page'),              'report_file' => 'os_reports/Common/titlepage.php'),
            'top_attacker_host' => array('id' => 'top_attacker_host',   'name' => _('Top 10 Attacker Host'),    'report_file' => 'os_reports/Alarms/AttackerHosts.php'),
            'top_attacked_host' => array('id' => 'top_attacked_host',   'name' => _('Top 10 Attacked Host'),    'report_file' => 'os_reports/Alarms/AttackedHosts.php'),
            'used_port'         => array('id' => 'used_port',           'name' => _('Top 10 Used Ports'),       'report_file' => 'os_reports/Alarms/UsedPorts.php'),
            'top_events'        => array('id' => 'top_events',          'name' => _('Top 15 Alarms'),           'report_file' => 'os_reports/Alarms/TopAlarms.php'),
            'events_by_risk'    => array('id' => 'events_by_risk',      'name' => _('Top 15 Alarms by Risk'),   'report_file' => 'os_reports/Alarms/TopAlarmsByRisk.php')
        ),
        'parameters'    => array(
            array('name' => _('Date Range'),
                  'date_from_id' => 'ar_date_from',
                  'date_to_id' => 'ar_date_to',
                  'type' => 'date_range',
                  'default_value' => array('date_from' => $y.'-'.$m.'-'.$d, 'date_to' => date('Y').'-'.date('m').'-'.date('d') ))
        ),
        'access'        => Session::menu_perms('analysis-menu', 'ControlPanelAlarms'),
        'send_by_email' => 1
    );
        
                    
    $reports['bc_pci_report'] = array('report_name' => _('Business & Compliance ISO PCI Report'),
        'report_id'     => 'bc_pci_report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'        => array('id' => 'title_page',         'name' => _('Title Page'),                  'report_file' => 'os_reports/Common/titlepage.php'),
            'threat_overview'   => array('id' => 'threat_overview',    'name' => _('Threat overview'),             'report_file' => 'os_reports/BusinessAndComplianceISOPCI/ThreatOverview.php'),
            'bri_risks'         => array('id' => 'bri_risks',          'name' => _('Business real impact risks'),  'report_file' => 'os_reports/BusinessAndComplianceISOPCI/BusinessPotentialImpactsRisks.php'),
            'ciap_impact'       => array('id' => 'ciap_impact',        'name' => _('C.I.A Potential impact'),      'report_file' => 'os_reports/BusinessAndComplianceISOPCI/CIAPotentialImpactsRisks.php'),
            'pci_dss'           => array('id' => 'pci_dss',            'name' => _('PCI-DSS'),                     'report_file' => 'os_reports/BusinessAndComplianceISOPCI/PCI-DSS.php'),
            'trends'            => array('id' => 'trends',             'name' => _('Trends'),                      'report_file' => 'os_reports/BusinessAndComplianceISOPCI/Trends.php'),
            'iso27002_p_impact' => array('id' => 'iso27002_p_impact',  'name' => _('ISO27002 Potential impact'),   'report_file' => 'os_reports/BusinessAndComplianceISOPCI/ISO27002PotentialImpact.php'),
            'iso27001'          => array('id' => 'iso27001',           'name' => _('ISO27001'),                    'report_file' => 'os_reports/BusinessAndComplianceISOPCI/ISO27001.php')
        ),
        'parameters'    => array(
            array('name'          => _('Date Range'),
                  'date_from_id'  => 'bc_pci_date_from',
                  'date_to_id'    => 'bc_pci_date_to',
                  'type'          => 'date_range',
                  'default_value' => array('date_from' => $y.'-'.$m.'-'.$d, 'date_to' => date('Y').'-'.date('m').'-'.date('d')))
        ),

        
        'access'        => Session::menu_perms('report-menu', 'ReportsReportServer'),
        'send_by_email' => 1
    );
        
                    
    $reports['siem_report'] = array('report_name' => _('SIEM Events'),
        'report_id'     => 'siem_report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page'        => array('id' => 'title_page',          'name' => _('Title Page'),              'report_file' => 'os_reports/Common/titlepage.php'),
            'top_attacker_host' => array('id' => 'top_attacker_host',   'name' => _('Top 10 Attacker Host'),    'report_file' => 'os_reports/Siem/AttackerHosts.php'),
            'top_attacked_host' => array('id' => 'top_attacked_host',   'name' => _('Top 10 Attacked Host'),    'report_file' => 'os_reports/Siem/AttackedHosts.php'),
            'used_port'         => array('id' => 'used_port',           'name' => _('Top 10 Used Ports'),       'report_file' => 'os_reports/Siem/UsedPorts.php'),
            'top_events'        => array('id' => 'top_events',          'name' => _('Top 15 Events'),           'report_file' => 'os_reports/Siem/TopEvents.php'),
            'events_by_risk'    => array('id' => 'events_by_risk',      'name' => _('Top 15 Events by Risk'),   'report_file' => 'os_reports/Siem/TopEventsByRisk.php')
        ),
        'parameters'    => array(
            array('name'          => _('Date Range'),
                  'date_from_id'  => 'sr_date_from',
                  'date_to_id'    => 'sr_date_to',
                  'type'          => 'date_range',
                  'default_value' => array('date_from' => $y.'-'.$m.'-'.$d, 'date_to' => date('Y').'-'.date('m').'-'.date('d')))
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 1
    );
            
    
    $reports['vulnerabilities_report'] = array('report_name' => _('Vulnerabilities Report'),
        'report_id'     => 'vulnerabilities_report',
        'type'          => 'external',
        'target'        => '_blank',
        'link_id'       => 'link_vr',       
        'link'          => Menu::get_menu_url('../vulnmeter/lr_respdf.php?ipl=all&scantype=M', 'environment', 'vulnerabilities', 'overview'),
        'access'        => Session::menu_perms('analysis-menu', 'EventsVulnerabilities'),
        'send_by_email' => 0
    );
                     
    $reports['th_vuln_db'] = array('report_name' => _('Threats & Vulnerabilities Database'),
        'report_id'     => 'th_vuln_db',
        'type'          => 'external',
        'link_id'       => 'link_tvd',
        'link'          => Menu::get_menu_url('../vulnmeter/threats-db.php', 'environment', 'vulnerabilities', 'threat_database'),
        'access'        => Session::menu_perms('analysis-menu', 'EventsVulnerabilities'),
        'send_by_email' => 0
    );
    
                  
    $reports['ticket_status'] = array('report_name' => _('Tickets Status'),
        'report_id'     => 'ticket_status',
        'type'          => 'external',
        'link_id'       => 'link_tr',        
        'link'          => Menu::get_menu_url('../report/incidentreport.php', 'analysis', 'tickets', 'tickets'),
        'access'        => Session::menu_perms('analysis-menu', 'IncidentsIncidents'),
        'send_by_email' => 0
    );
    
        
    $db   = new ossim_db();
    $conn = $db->connect();
        
    $user = Session::get_session_user();
    
    $session_list = Session::get_list($conn, 'ORDER BY login');
        
    if (preg_match('/pro|demo/',$conf->get_conf('ossim_server_version')) && !Session::am_i_admin()) 
    {        
        $myusers = Acl::get_my_users($conn,Session::get_session_user());
        
        if (count($myusers) > 0) 
        {
            $is_pro_admin = 1;
        }
    }
            
    // User Log lists
    if (Session::am_i_admin()) 
    { 
        $user_values[''] = array ('text' => _('All'));
                
        if ($session_list)
        {
            foreach($session_list as $session) 
            {
                $login = $session->get_login();
                $user_values[$login] = ( $login == $user ) ? array ('text' => $login, 'selected' => TRUE) : array ('text' => $login);
            }
        }
    }
    elseif ($is_pro_admin) 
    {
        foreach ($myusers as $myuser)
        {
            $user_values[$myuser['login']] = array ('text' => $myuser['login']);
            $user_values[$user]            = array ('text' => $user, 'selected' => TRUE);
        }
    }
    else
    {
        $user_values[$user] = array ('text' => $user);
    }
    
   
    $code_list = Log_config::get_list($conn, 'ORDER BY descr');
    
    $action_values[''] = array ('text' => _('All'));
    
    if ($code_list) 
    {
        foreach($code_list as $code_log) 
        {
            $code_aux = $code_log->get_code();
            $action_values[$code_aux ] = array ('text' => '[' . sprintf("%02d", $code_aux) . '] ' . _(preg_replace('|%.*?%|', " ", $code_log->get_descr())));
        }
    }
    
    $reports['user_activity'] = array('report_name' => _('User Activity Report'),
        'report_id'     => 'user_activity',
        'type'          => 'external',
        'link_id'       => 'link_ua',
        'link'          => Menu::get_menu_url('../userlog/user_action_log.php', 'settings', 'settings', 'user_activity'),
        'parameters'    => array(
            array('name' => _('User'),
                  'id'   => 'ua_user',                        
                  'type' => 'select', 
                  'values' => $user_values),
            
            array('name' => _('Action'),
                  'id'   => 'ua_action',                        
                  'type' => 'select', 
                  'values' => $action_values)
        ),
        'access'        => Session::menu_perms('settings-menu', 'ToolsUserLog'),
        'send_by_email' => 0
    );
      
    
    $reports['geographic_report'] = array('report_name' => _('Geographic Report'),
        'report_id'     => 'geographic_report',
        'type'          => 'pdf',
        'subreports'    => array(
        'title_page'        => array('id'  => 'title_page',        'name' => _('Title Page'),        'report_file' => 'os_reports/Common/titlepage.php'),
        'geographic_report' => array('id' => 'geographic_report',  'name' => _('Geographic Report'), 'report_file' => 'os_reports/Various/Geographic.php')
        ),
        'parameters'    => array(
            array('name' => _('Date Range'),
                  'date_from_id' => 'gr_date_from',
                  'date_to_id' => 'gr_date_to',
                  'type' => 'date_range',
                  'default_value' => array('date_from' => $y.'-'.$m.'-'.$d, 'date_to' => date('Y').'-'.date('m').'-'.date('d') ))
        ),
        'access'        => Session::menu_perms('analysis-menu', 'EventsForensics'),
        'send_by_email' => 1
        );


    $reports['metric_report'] = array('report_name' => _('Metric Report'),
        'report_id'     => 'metric_report',
        'type'          => 'pdf',
        'subreports'    => array(
            'title_page' => array('id'  => 'title_page',  'name' => _('Title Page'),   'report_file' => 'os_reports/Common/titlepage.php'),
            'day'        => array('id' => 'day',          'name' => _('Day'),          'report_file' => 'os_reports/Metric/Day.php'),
            'week'       => array('id' => 'week',         'name' => _('Week'),         'report_file' => 'os_reports/Metric/Week.php'),
            'month'      => array('id' => 'month',        'name' => _('Month'),        'report_file' => 'os_reports/Metric/Month.php'),
            'year'       => array('id' => 'year',         'name' => _('Year'),         'report_file' => 'os_reports/Metric/Year.php')
         ),
        'parameters'    => array(),
        'access'        => Session::menu_perms('dashboard-menu', 'ControlPanelMetrics'),
        'send_by_email' => 1
    );

                
    //Sensor list 
    $sensor_values[''] = array('text' => ' -- '._('Sensors no found').' -- ');  
    
    
    $filters = array(
        'order_by' => 'name'
    );
    
    $sensor_list = Av_sensor::get_basic_list($conn, $filters);
    
    
    $filters = array(
        'order_by' => 'priority desc'
    );
    
    list($sensor_list, $sensor_total) = Av_sensor::get_list($conn, $filters);                  
    
    if ($sensor_total > 0)
    {
        $sensor_values = array();
        
        foreach($sensor_list as $s) 
        {            
            $properties = $s['properties'];

    		if ($properties['has_nagios']) 
    		{            
                $sensor_values[$s['ip']] = array('text' => $s['name']);  
            }                                           
        }
    }
    
    
    /* Nagios link */
    $nagios_link    = $conf->get_conf('nagios_link');
    $scheme         = (empty($_SERVER['HTTPS']))        ? 'http://' : 'https://';
    $path           = (!empty($nagios_link))            ? $nagios_link : '/nagios3/';
    $port           = (!empty($_SERVER['SERVER_PORT'])) ? ':'.$_SERVER['SERVER_PORT'] : "";
    
    $nagios         = $port.$path;  

    $section_values = array(
        urlencode($nagios.'cgi-bin/trends.cgi')           => array('text' => _('Trends')), 
        urlencode($nagios.'cgi-bin/avail.cgi')            => array('text' => _('Availability')),  
        urlencode($nagios.'cgi-bin/histogram.cgi')        => array('text' => _('Event Histogram')),  
        urlencode($nagios.'cgi-bin/history.cgi?host=all') => array('text' => _('Event History')),  
        urlencode($nagios.'cgi-bin/summary.cgi')          => array('text' => _('Event Summary')),  
        urlencode($nagios.'cgi-bin/notifications.cgi')    => array('text' => _('Notifications')),  
        urlencode($nagios.'cgi-bin/showlog.cgi')          => array('text' => _('Performance Info'))
    );          
               
                
    $reports['availability_report'] = array('report_name'   => _('Availability Report'),
        'report_id'     => 'availability_report',
        'type'          => 'external',
        'link_id'       => 'link_avr',
        'click'         => "nagios_link('avr_nagios_link', 'avr_sensor', 'avr_section');",
        'parameters'    => array(
            array('name' => _('Sensor'),
                  'id'   => 'avr_sensor',
                  'type' => 'select', 
                  'values' => $sensor_values),
                  
            array('name' => 'Nagioslink',
                  'id'   => 'avr_nagios_link',
                  'type' => 'hidden', 
                  'default_value' => urlencode($scheme)),
            
            array('name'  => _('Section'),
                  'id'    => 'avr_section',
                  'type'  => 'select', 
                  'values' => $section_values)
        ),
        'access'        => Session::menu_perms('environment-menu', 'MonitorsAvailability'),
        'send_by_email' => 0
    );

                
    $db->close();
                   
    if ($id == NULL)
    {
        ksort($reports);
        return $reports;
    }
    else
    {
        return (!empty($reports[$id]) ? $reports[$id] : array() ); 
    }
}

function draw_parameter($data)
{        
    switch ($data['type'])
    {
        case 'date_range':
            
            echo "<div class='fleft' style='padding-bottom:5px;'>" . $data['name'] . "
                  </div>
                  <div class='datepicker_range' style='clear:both !important;margin-left:18px;'>
                    <div class='calendar_from'>
                        <div class='calendar'>
                            <input name='" . $data['date_from_id'] . "' id='" . $data['date_from_id'] . "' class='date_filter' type='input' value='".$data['default_value']['date_from']."'>
                        </div>
                    </div>
                    <div class='calendar_separator'>
                        -
                    </div>
                    <div class='calendar_to'>
                        <div class='calendar'>
                            <input name='" . $data['date_to_id'] . "' id='" . $data['date_to_id'] . "' class='date_filter' type='input' value='".$data['default_value']['date_to']."'>
                        </div>
                    </div>
                </div>";
        break;
        
        case 'month':
            echo "<div style='padding-bottom:3px;'>".$data['name'].":</div>";
            echo "<input type='text' class='month' id='".$data['id']."' name='".$data['id']."' value='".$data['default_value']."'/>
                  <div class='widget'></div>";
        break;
        
        case 'year':
            echo "<div style='padding-bottom:3px;'>".$data['name'].":</div>";
            echo "<input type='text' class='year' id='".$data['id']."' name='".$data['id']."' value='".$data['default_value']."'/>
                  <div class='widget'></div>";
        break;
        
        case 'hidden':
            echo "<input type='hidden' id='".$data['id']."' name='".$data['name']."' value='".$data['default_value']."'/>";
        break;
        
        case 'asset':
            echo "<div style='padding-bottom:3px;'>".$data['name'].":</div>";
            echo "<input type='text' class='asset' id='".$data['id']."' name='".$data['id']."' value='".$data['default_value']."'/>";
            echo "<input type='hidden' id='h_".$data['id']."' name='h_".$data['id']."'/>";       
        break;
        
        case 'select';
            echo "<div style='padding-bottom:3px;clear:both;'>".$data['name'].":</div>";
            echo "<select name='".$data['id']."' id='".$data['id']."' style='min-width:200px; max-width:400px;'>";
            
            if (is_array($data['values']) && !empty($data['values']))
            {
                foreach ($data['values'] as $key => $value)
                {
                    $selected = ( !empty($value['selected']) ) ? "selected='selected'" : "";
                    echo "<option value='$key' $selected>"._($value['text'])."</option>";
                }
            }
            
            echo "</select><br/>";               
        break;
        
        case 'checkbox';
            echo "<div style='padding-bottom:3px;'>".$data['name'].":</div>";
            
            foreach ($data['values'] as $key => $value)
            {
                echo "<input type='checkbox' id='".$data['id']."_"."$key' name='".$data['id']."_".$value."'/><span style='margin-left: 3px'>"._($value)."</span><br/>";
            }            
        break;
    }
}


function validate_parameter($type, $parameter)
{
    $res = TRUE;
    
    ossim_clean_error();
    
    switch ($type)
    {
        case 'date':
           ossim_valid($parameter, OSS_DATE, 'illegal:' . _('Date'));
           
           if (ossim_error())
           {
                $res = _('Invalid Date-time. Format allowed: YYYY-MM-DD');
           }     
        break;
        
        case 'year':
            if ($parameter > 1970 && $parameter < 3000)
            {
                $res = _('Invalid Year. Format allowed: YYYY [1970-3000]');
            }
        case 'month':
            if ($parameter > 0 && $parameter < 13)
            {    
                $res = _('Invalid Month. Format allowed: MM [00-12]');
            }
        break;                      
    }
    
    return $res;
}


function check_parameters($report_id, $parameters, $section)
{
    $res['error']     = FALSE;
    $res['error_msg'] = NULL;
    
    $d_reports = ($section == 'forensics') ? get_freport_data($report_id) : get_report_data($report_id);
    
    if (empty($d_reports))
    {
        $res['error']       = TRUE;
        $res['error_msg'][] = _('The report has been removed');
        
        return $res;
    }
    
   
    if (!empty($d_reports['parameters'])) 
    {
        foreach ($d_reports['parameters'] as $data_p)
        {
            $type = $data_p['type'];
            $data = ( $type == 'asset' ) ? 'h_'.$parameters[$data_p['id']] : $parameters[$data_p['id']];
            $val  = validate_parameter($type, $data);
            
            if ($val !== TRUE)
            {            
                $res['error']       = TRUE;
                $res['error_msg'][] = $data_p['name'].': '.$val;
            }
        }
    }
    
    return $res;
}


function get_allowed_hosts($conn, $tables = '', $filters = array())
{    
    $filters['order_by'] = 'hostname';
    
    $hosts = Asset_host::get_list_tree($conn, $tables, $filters, FALSE, FALSE);
                
    return $hosts;
}


function get_allowed_nets($conn, $tables = '', $filters = array())
{     
    $filters['order'] = 'name ASC';
    
    $_net_list = Asset_net::get_list($conn, $tables, $filters);
    $nets      = $_net_list[0];
    
    return $nets;
}
?>