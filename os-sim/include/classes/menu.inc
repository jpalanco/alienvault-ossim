<?php
/**
* menu.inc
*
* File menu.inc is used for:
*   - Manage menus from user interface
*
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
* @package    ossim-framework\Administration
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


require_once 'classes/Reputation.inc';
require_once 'classes/template.inc';
require_once 'av_config.php';


/**
* Menu Class
*
* Class for managing menus from user interface
*
* @package    ossim-framework\Administration
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Menu
{
    /**
    * Logged user
    *
    * @var string
    * @access private
    */
    private $user;

    /**
    * Menu version (Open Source or Professional)
    *
    * @var string
    * @access private
    */
    private $menu_version;


    /**
    * Menu perms
    *
    * @var array
    * @access private
    */
    private $allowed_menus;


    /**
    * Menu structure
    *
    * @var array
    * @access private
    */
    private $menus;

    /**
    * Selected menu option
    *
    * @var string
    * @access private
    */
    private $m_option;

    /**
    * Selected submenu option
    *
    * @var string
    * @access private
    */
    private $sm_option;


    /**
    * Selected hmenu (horizontal menu) option
    *
    * @var string
    * @access private
    */
    private $h_option;

    /**
    * Selected lmenu (local menu) option
    *
    * @var string
    * @access private
    */
    private $l_option;


    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param object $conn   Database access object
    * @param string $login  [Optional] Logged user
    *
    * @throws Exception  If logged user doesn't exists in the System or there is a connection error
    * @return void
    */
    public function __construct($conn, $login = '')
    {
        Ossim_db::check_connection($conn);

        // Getting user information
        $this->user = Session::get_user_info($conn, $login);

        if (empty($this->user))
        {
            $exp_msg = _('Error! Menu class not created.  Logged user not found in the System');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        /*
          Getting menu perm
            - Admin user has an empty array
        */

        $this->allowed_menus = array();

        if (!$this->is_user_admin())
        {
            $this->allowed_menus = array();

            $template = new Template($conn, $this->user->get_template_id());
            $template->load_from_db($conn);

            $template_perms = $template->get_perms();

            if (is_array($template_perms) && !empty($template_perms))
            {
                foreach($template_perms as $perm_data)
                {
                    $perm_data = explode('###', $perm_data);
                    $this->allowed_menus[$perm_data[0]][$perm_data[1]] = 1;
                }
            }
        }

        //Menu version
        $conf = new Ossim_conf();

        $version            = $conf->get_conf('ossim_server_version');
        $this->menu_version = (preg_match("/pro|demo/i", $version)) ? 'pro' : 'open';

        /* Menu Structure */


        /*
        + Menu:Settings (Out of menu)
            + Submenu:Settings
                + Hmenu:My profile
                + Hmenu:User Activity
                + Hmenu:Current Sessions
        + Menu:Support (Out of menu)
            + Submenu:Support
                + Hmenu:Downloads
                + Hmenu:Upgrade
        + Menu:Dashboards
            + Submenu:Overview
                + Hmenu:Overview
            + Submenu:Deployment Status
                + Hmenu:Dashboard
                + Hmenu:Warnings & Errors
            + Submenu:Risk Maps
                + Hmenu:Overview
                    + L_lmenu:(empty)
                    + R_lmenu:
                        + Option: Set Indicators
                + Hmenu:Manage Maps
            + Submenu: OTX (IP Reputation)
                + Hmenu:OTX

        + Menu:Analysis
            + Submenu:Alarms
                + Hmenu:List View
                + Hmenu:Group View
            + Submenu:Security Events (SIEM)
                + Hmenu:SIEM
                + Hmenu:Real-Time
                + Hmenu:External Databases
            + Submenu:Raw logs
                + Hmenu:Raw logs
            + Submenu:Tickets
                + Hmenu:Tickets

        + Menu:Environment
            + Submenu:Assets
                + Hmenu:Assets
                + Hmenu:Asset Discovery
            + Submenu:Groups & Networks
                + Hmenu:Group
                + Hmenu:Networks
                + Hmenu:Network Groups
            + Submenu:Vulnerabilities
                + Hmenu:Overview
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:Profiles
                        + Option:Settings
                + Hmenu:Scan Jobs
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:Profiles
                        + Option:Settings
                + Hmenu:Threat Database
            + Submenu:Profiles
                + Hmenu: Services
                + Hmenu: Global
                + Hmenu: Throughput
                + Hmenu: Matrix
            + Submenu:Netflow
                 + Hmenu: Overview
                 + Hmenu: Detail
                 + Hmenu: Graphs
            + Submenu:Traffic Capture
                 + Hmenu: Traffic Capture
            + Submenu:Availability
                + Option:Overview
                + Option:Reports
            + Submenu:Deteccion
                + Hmenu:HIDS
                    + L_lmenu:
                        + Option:Overview
                        + Option:Agents
                        + Option:Agentless
                        + Option:Edit rules
                        + Option:Config
                        + Option:Ossec Control
                    + R_lmenu:
                        + (empty)
                + Hmenu:Wireless
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:Setup

        + Menu:Reports (USM Version)
            + Submenu:USM Reports
                + Hmenu:Overview
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:New Custom Report
             + Hmenu:Modules
                + Hmenu:Layouts
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:New Layout
                + Hmenu:Scheduler
                     + L_lmenu:
                        + (empty)
                     + R_lmenu:
                        + Option:New Scheduler
            + Submenu:OSSIM Reports
                + Hmenu:Overview


        + Menu:Reports (Open Source Version)
            + Submenu:Overview
                + Hmenu:Overview

        + Menu:Configuration
            + Submenu:Administration
                + Hmenu:Users
                    + L_lmenu:
                        + Option:User Information
                        + Option:Activity
                        + Option:Templates
                        + Option:Structure
                    + R_lmenu:
                        + (empty)
                + Hmenu:Main
                + Hmenu:Backup
            + Submenu:Deployment
                + Hmenu:Scheduler (Alienvault Inventory)
                    + L_lmenu:
                        + Option:NMAP
                        + Option:OCS
                        + Option:WMI
                + Hmenu:Components
                     + L_lmenu:
                        + Option:Alienvault Center
                        + Option:Sensors
                        + Option:Servers
                        + Option:Remote Interfaces
                    + R_lmenu:
                        + (empty)
                + Hmenu:Locations
                + Hmenu:Smart Event Collection
            + Submenu:Threat Intelligence
                + Hmenu:Policy
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:Edit policy groups
                + Hmenu:Actions
                + Hmenu:Ports
                    + L_lmenu:
                        + Option:Ports
                        + Option:Port Groups
                + Hmenu:Directives
                + Hmenu:Compliance Mapping
                    + L_lmenu:
                        + Option:ISO 27001
                        + Option:PCI DSS 2.0
                    + R_lmenu:
                        + Option:Launch scripts
                + Hmenu:Cross Correlation
                + Hmenu:Data Source
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Data Source Groups
                + Hmenu:Taxonomy
                + Hmenu:Knowledgebase
                    + L_lmenu:
                        + (empty)
                    + R_lmenu:
                        + Option:New Document
        */


        /***********************************************************
        ------------------------- Settings -------------------------
        ************************************************************/

        /* Settings permissions */

        $perm_m_settings = TRUE;


        /* User Activity */
        $perm_user_activity = $this->check_perm("settings-menu", "ToolsUserLog");


        $this->menus['settings'] = array(
            "name"     => _("Settings"),
            "enabled"  => $perm_m_settings,
            "display"  => FALSE,
            "submenu"  => array(
                "settings" => array(
                    "name"    => _("Settings"),
                    "enabled" => TRUE,
                    "hmenu"   => array(
                        "my_profile"  => array(
                            "name"    => _("My Profile"),
                            "enabled" => TRUE,
                            "url"     => "/session/user_form.php?login=".$this->user->get_login()
                        ),

                        "current_sessions" => array(
                            "name"    => _("Current Sessions"),
                            "enabled" => TRUE,
                            "url"     => "/userlog/opened_sessions.php"
                        ),

                        "user_activity" => array(
                            "name"    => _("User Activity"),
                            "enabled" => $perm_user_activity,
                            "url"     => "/userlog/user_action_log.php"
                        )
                   )
               )
           )
       );


        /***********************************************************
        ------------------------- Support --------------------------
        ************************************************************/

        /* Support permissions */


        /* Support */
        $perm_help = TRUE;

        $perm_downloads = $this->check_perm("support-menu", "ToolsDownloads");

        $upgrade = new Upgrade();

        $perm_upgrade       = $this->is_user_admin() && $upgrade->needs_upgrade();

        $perm_support_tools = $this->is_user_admin() && $this->is_menu_pro();

        $perm_support       = $perm_help || $perm_support_tools || $perm_downloads || $perm_upgrade;

        $perm_m_support     = $perm_support;

        $this->menus['support'] = array(
            "name"     => _("Support"),
            "enabled"  => $perm_m_support,
            "display"  => FALSE,
            "submenu"  => array(
                "support" => array(
                    "name"    => _("Support"),
                    "enabled" => $perm_support,
                    "hmenu"   => array(
                        "help"  => array(
                            "name"    => _("Help"),
                            "enabled" => $perm_help,
                            "url"     => '/help/index.php'
                        ),

                        "support_tool"  => array(
                            "name"    => _("Support Tool"),
                            "enabled" => $perm_support_tools,
                            "url"     => '/support_tools/index.php'
                        ),

                        "downloads"  => array(
                            "name"    => _("Downloads"),
                            "enabled" => $perm_downloads,
                            "url"     => '/downloads/index.php'
                        ),

                        "upgrade"  => array(
                            "name"    => _("Upgrade"),
                            "enabled" => $perm_upgrade,
                            "url"     => '/upgrade/index.php'
                        )
                   )
               )
           )
       );


        /**********************************************************
        ------------------------ Dashboards -----------------------
        ***********************************************************/


        /* Dashboard permissions */

        /* Overview */
        $perm_dashboards = $this->check_perm("dashboard-menu", "ControlPanelExecutive");

        /* Deployment status */
        $perm_deployment_status = $this->is_user_admin();
        $perm_system_status     = $this->is_user_admin();

        /* Riskmaps */
        $perm_rm_si             = $this->check_perm("dashboard-menu", "BusinessProcessesEdit");
        $perm_rm_mm             = $this->check_perm("dashboard-menu", "BusinessProcessesEdit");

        $perm_risk_maps         = $this->check_perm("dashboard-menu", "BusinessProcesses");

        /* OTX */
        $ip_rep = new Reputation();

        $perm_otx = $this->check_perm("dashboard-menu", "IPReputation") && $ip_rep->existReputation();


        $perm_m_dasboards = $perm_dashboards || $perm_deployment_status || $perm_risk_maps || $perm_otx;


        $this->menus['dashboard'] = array(
            "name"    => _("Dashboards"),
            "enabled" => $perm_m_dasboards,
            "display" => TRUE,
            "submenu" => array(

                /* Overview */
                "overview" => array(
                    "name" => _("Overview"),
                    "enabled" => $perm_dashboards,
                    "hmenu"   => array(
                        "overview" => array(
                            "name"    => _("Overview"),
                            "enabled" => $perm_dashboards,
                            "url"     => "/dashboard/index.php"
                       )
                    )
                ),

                /* Deployment Status */
                "deployment_status" => array(
                    "name"    => _("Deployment Status"),
                    "enabled" => $perm_deployment_status,
                    "hmenu"   => array(
                        "deployment_status" => array(
                            "name"    => _("Dashboard"),
                            "enabled" => $perm_deployment_status,
                            "url"     => "/deployment/index.php"
                       ),
                       "system_status" => array(
                            "name"    => _("Warnings & Errors"),
                            "enabled" => $perm_system_status,
                            "url"     => "/system/index.php"
                       )
                    )
                ),

                /* Risk Maps */
                "riskmaps" => array(
                    "name"    => _("Risk Maps"),
                    "enabled" => $perm_risk_maps,
                    "hmenu"   => array(
                        "overview" => array(
                            "name"    => _("Overview"),
                            "enabled" => $perm_rm_si,
                            "url"     => "/risk_maps/view.php",
                            "r_lmenu" => array (
                               "set_indicators" => array(
                                    "name"       => _("Set Indicators"),
                                    "enabled"    => $perm_rm_si,
                                    "display_in" => "iframe",
                                    "url"        => "/risk_maps/index.php"
                                )
                            )
                        ),

                        "manage_maps" => array(
                            "name"    => _("Manage Maps"),
                            "enabled" => $perm_rm_mm,
                            "url"     => "/risk_maps/changemap.php"
                        )
                    )
                ),

                /* OTX */
                "otx" => array(
                    "name"    => _("OTX"),
                    "enabled" => $perm_otx,
                    "hmenu"   => array(
                        "otx" => array(
                            "name"    => _("OTX"),
                            "enabled" => $perm_otx,
                            "url"     => "/reputation/index.php"
                       )
                    )
                )
            )
        );


        /**********************************************************
        ------------------------- Analysis ------------------------
        ***********************************************************/

        /* Analysis permissions */

        /* Alarms */
        $perm_alarms = $this->check_perm("analysis-menu", "ControlPanelAlarms");

        /* Security Events */
        $perm_sec_events  = $this->check_perm("analysis-menu", "EventsForensics");
        $perm_realtime    = $this->check_perm("analysis-menu", "EventsRT");
        $perm_dbs         = $this->is_menu_pro() && $perm_sec_events;

		$perm_siem_events = $perm_sec_events || $perm_realtime;

        /* Raw logs */
        $perm_raw_logs = is_dir("/var/ossim/") && $this->check_perm("analysis-menu", "ControlPanelSEM");

        /* Tickets */
        $perm_tickets = $this->check_perm("analysis-menu", "IncidentsIncidents");

        $perm_m_analysis = $perm_alarms || $perm_sec_events || $perm_raw_logs || $perm_tickets;

        $tz        = Util::get_timezone();
        $timetz    = gmdate("U")+(3600*$tz);
        $date_from = gmdate("Y-m-d", strtotime("-30 day UTC",$timetz));
        $date_to   = gmdate("Y-m-d", $timetz);
        $tmp_month = gmdate("m", strtotime("-1 day UTC",$timetz));
        $tmp_day   = gmdate("d", strtotime("-1 day UTC",$timetz));
        $tmp_year  = gmdate("Y", strtotime("-1 day UTC",$timetz));
        $tmp_hour  = gmdate("H",$timetz);
        $today     = '&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=' . $tmp_month . '&time%5B0%5D%5B3%5D=' . $tmp_day . '&time%5B0%5D%5B4%5D=' . $tmp_year . '&time%5B0%5D%5B5%5D=' . $tmp_hour . '&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&time_range=day';

        $url_se = "/forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d".$today;

        $this->menus['analysis'] = array(
            "name"     => _("Analysis"),
            "enabled"  => $perm_m_analysis,
            "display" => TRUE,
            "submenu"  => array(

                /* Alarms */
                "alarms" => array(
                    "name"    => _("Alarms"),
                    "enabled" => $perm_alarms,
                    "hmenu"   => array(

                        "alarms" => array(
                            "name"    => _("List View"),
                            "enabled" => $perm_alarms,
                            "url"     => "/alarm/alarm_console.php?hide_closed=1&date_from=$date_from&date_to=$date_to",
                            "b_param" => "/alarm/alarm_detail.php?backlog="
                        ),
                        "alarm_groups" => array(
                            "name"    => _("Group View"),
                            "enabled" => $perm_alarms,
                            "url"     => "/alarm/alarm_group_console.php?hide_closed=1"
                        )
                    )
                ),

                /* Security Events (SIEM) */
                "security_events" => array(
                    "name"    => _("Security Events (SIEM)"),
                    "enabled" => $perm_siem_events,
                    "hmenu"   => array(
                        "security_events" => array(
                            "name"    => _("SIEM"),
                            "enabled" => $perm_sec_events,
                            "url"     => $url_se
                        ),
                        "real_time" => array(
                            "name"    => _("Real-time"),
                            "enabled" => $perm_realtime,
                            "url"     => "/control_panel/event_panel.php"
                        ),
                        "databases" => array(
                            "name"    => _("External Databases"),
                            "enabled" => $perm_dbs,
                            "url"     => "/server/dbs.php"
                        )
                    )
                ),

                /* Raw Logs */
                "raw_logs" => array(
                    "name"    => _("Raw Logs"),
                    "enabled" => $perm_raw_logs,
                     "hmenu"  => array(
                        "raw_logs" => array(
                            "name"    => _("Raw Logs"),
                            "enabled" => $perm_raw_logs,
                            "url"     => ($this->is_menu_pro()) ? "/sem/index.php" : "/ossem/index.php"
                        )
                    )
                ),

                /* Tickets */
                "tickets" => array(
                    "name"    => _("Tickets"),
                    "enabled" => $perm_tickets,
                    "hmenu"   => array(
                        "tickets" => array(
                            "name"    => _("Tickets"),
                            "enabled" => $perm_tickets,
                            "url"     => "/incidents/index.php"
                        )
                    )
                )
            )
        );


        /**********************************************************
        ----------------------- Environment -----------------------
        ***********************************************************/


        /* Environment permissions */

        /* Assets & Groups */
        $perm_hosts           = $this->check_perm("environment-menu", "PolicyHosts");
        $perm_networks        = $this->check_perm("environment-menu", "PolicyNetworks");
        $perm_asset_discovery = $this->check_perm("environment-menu", "ToolsScan");

        $perm_assets          = $perm_hosts || $perm_asset_discovery;

        $perm_asset_groups    = $perm_hosts || $perm_networks;


        /* Vulnerabilities */
        $perm_vulns           = $this->check_perm("environment-menu", "EventsVulnerabilities");


        /* Profiles */
        $perm_profiles        = $this->check_perm("environment-menu", "MonitorsNetwork");


        /* Netflows */
        $perm_netflow         = $this->check_perm("environment-menu", "MonitorsNetflows");


        /* Traffic Capture */
        $perm_traffic_capture = $this->check_perm("environment-menu", "TrafficCapture");


        /* Availability */
        $perm_availability    = $this->check_perm("environment-menu", "MonitorsAvailability");


        /* Detection */
        $perm_hids            = $this->check_perm("environment-menu", "EventsHids");
        $perm_config_hids     = $this->check_perm("environment-menu", "EventsHidsConfig");
        $perm_wireless        = $this->check_perm("environment-menu", "ReportsWireless");

        $perm_detection       = $perm_hids || $perm_wireless;


        $perm_m_environment   = $perm_assets || $perm_asset_groups || $perm_vulns || $perm_profiles || $perm_netflow || $perm_traffic_capture || $perm_availability || $perm_detection;

        $this->menus['environment'] = array(
            "name"     => _("Environment"),
            "enabled"  => $perm_m_environment,
            "display"  => TRUE,
            "submenu"  => array(

                /* Assets */
                "assets" => array(
                    "name"    => _("Assets"),
                    "enabled" => $perm_assets,
                    "hmenu"   => array(

                        "assets" => array(
                            "name"    => _("Assets"),
                            "enabled" => $perm_hosts,
                            "url"     => "/assets/index.php",
                            "b_param" => "/asset_details/index.php?id="
                        ),

                        "asset_discovery" => array(
                            "name"    => _("Asset Discovery"),
                            "enabled" => $perm_asset_discovery,
                            "url"     => "/netscan/index.php"
                        )
                    )
                ),


                /* Assets */
                "assets_groups" => array(
                    "name"    => _("Groups & Networks"),
                    "enabled" => $perm_asset_groups,
                    "hmenu"   => array(

                        "host_groups" => array(
                            "name"    => _("Groups"),
                            "enabled" => $perm_hosts,
                            "url"     => "/assets/list_view.php?type=group",
                            "b_param" => "/asset_details/index.php?id="
                        ),

                        "networks" => array(
                            "name"    => _("Networks"),
                            "enabled" => $perm_networks,
                            "url"     => "/assets/list_view.php?type=network",
                            "b_param" => "/asset_details/index.php?id="
                        ),

                        "network_groups" => array(
                            "name"    => _("Network Groups"),
                            "enabled" => $perm_networks,
                            "url"     => "/netgroup/netgroup.php"
                        )
                    )
                ),

                /* Vulnerabilities */
                "vulnerabilities" => array(
                    "name"    => _("Vulnerabilities"),
                    "enabled" => $perm_vulns,
                    "hmenu"   => array(
                        "overview" => array(
                            "name"    => _("Overview"),
                            "enabled" => $perm_vulns,
                            "url"     => "/vulnmeter/index.php",
                            "r_lmenu" => array (
                                "profiles" => array(
                                    "name"       => _("Profiles"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "b_class"    => "av_b_secondary",
                                    "url"        => "/vulnmeter/settings.php"
                               ),
                               "settings" => array(
                                    "name"       => _("Settings"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "b_class"    => "av_b_secondary",
                                    "url"        => "/vulnmeter/webconfig.php"
                               )
                           )
                       ),

                        "scan_jobs" => array(
                            "name"    => _("Scan Jobs"),
                            "enabled" => $perm_vulns,
                            "url"     => "/vulnmeter/manage_jobs.php",
                            "r_lmenu" => array (
                                "profiles" => array(
                                    "name"       => _("Profiles"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "b_class"    => "av_b_secondary",
                                    "url"        => "/vulnmeter/settings.php"
                               ),
                                "settings" => array(
                                    "name"       => _("Settings"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "b_class"    => "av_b_secondary",
                                    "url"        => "/vulnmeter/webconfig.php"
                               )
                            )
                        ),

                        "threat_database" => array(
                            "name"    => _("Threat Database"),
                            "enabled" => $perm_vulns,
                            "url"     => "/vulnmeter/threats-db.php"
                        )
                    )
               ),

                /* Profiles */
                "profiles" => array(
                    "name"   => _("Profiles"),
                    "enabled" => $perm_profiles,
                    "hmenu"   => array(
                        "services" => array(
                            "name"    => _("Services"),
                            "enabled" => TRUE,
                            "url"     => "/ntop/index.php?opc=services"
                        ),

                        "global" => array(
                            "name"    => _("Global"),
                            "enabled" => TRUE,
                            "url"     => "/ntop/index.php"
                        ),

                        "throughput" => array(
                            "name"    => _("Throughput"),
                            "enabled" => TRUE,
                            "url"     => "/ntop/index.php?opc=throughput"
                        ),

                        "matrix" => array(
                            "name"    => _("Matrix"),
                            "enabled" => TRUE,
                            "url"     => "/ntop/index.php?opc=matrix"
                        )
                    )
               ),

                /* Netflow */
                "netflow" => array(
                    "name"    => _("Netflow"),
                    "enabled" => $perm_netflow,
                    "hmenu"   => array(
                        "details" => array(
                            "name"    => _("Details"),
                            "enabled" => TRUE,
                            "url"     => "/nfsen/nfsen.php?tab=2"
                        ),

                        "overview" => array(
                            "name"    => _("Overview"),
                            "enabled" => TRUE,
                            "url"     => "/nfsen/nfsen.php?tab=0"
                        ),

                        "graph" => array(
                            "name"    => _("Graph"),
                            "enabled" => TRUE,
                            "url"     => "/nfsen/nfsen.php?tab=1"
                        )
                    )
                ),

                /* Traffic Capture */
                "traffic_capture" => array(
                    "name"    => _("Traffic Capture"),
                    "enabled" => $perm_traffic_capture,
                    "hmenu"   => array(
                        "traffic_capture" => array(
                            "name"    => _("Traffic Capture"),
                            "enabled" => TRUE,
                            "url"     => "/pcap/index.php"
                        )
                    )
                ),


                /* Availability */
                "availability" => array(
                    "name"    => _("Availability"),
                    "enabled" => $perm_availability,
                    "hmenu"   => array(
                        "monitoring" => array(
                            "name"    => _("Monitoring"),
                            "enabled" => TRUE,
                            "url"     => "/nagios/index.php"
                        ),

                        "reporting" => array(
                            "name"    => _("Reporting"),
                            "enabled" => TRUE,
                            "url"     => "/nagios/index.php?opc=reporting"
                        )
                    )
                ),

                /* Detection */
                "detection" => array(
                    "name"    => _("Detection"),
                    "enabled" => $perm_detection,
                    "hmenu"   => array(
                        "hids" => array(
                            "name"    => _("HIDS"),
                            "enabled" => $perm_hids,
                            "url"     => "/ossec/status.php",
                            "l_lmenu" => array (
                                "overview" => array(
                                    "name"    => _("Overview"),
                                    "enabled" => TRUE,
                                    "url"     => "/ossec/status.php"
                                ),

                                "agents" => array(
                                    "name"    => _("Agents"),
                                    "enabled" => $perm_config_hids,
                                    "url"     => "/ossec/agent.php"
                                ),

                                "agentless" => array(
                                    "name"    => _("Agentless"),
                                    "enabled" => $perm_config_hids,
                                    "url"     => "/ossec/agentless.php"
                                ),

                                "edit_rules" => array(
                                    "name"    => _("Edit Rules"),
                                    "enabled" => $perm_config_hids,
                                    "url"     => "/ossec/index.php"
                                ),

                                "config" => array(
                                    "name"    => _("Config"),
                                    "enabled" => $perm_config_hids,
                                    "url"     => "/ossec/config.php"
                                ),

                                "ossec_control" => array(
                                    "name"    => _("Ossec Control"),
                                    "enabled" => $perm_config_hids,
                                    "url"     => "/ossec/ossec_control.php"
                                )
                            )
                        ),

                        "wireless_ids" => array(
                            "name"         => _("Wireless IDS"),
                            "enabled"      => $perm_wireless,
                            "url"          => "/wireless/index.php",
                            "r_lmenu" => array (
                                "setup" => array(
                                    "name"       => _("Setup"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "url"        => "/wireless/setup.php"
                                )
                            )
                        )
                    )
                )
            )
        );


       /***********************************************************
        ------------------------- Reports -------------------------
        ***********************************************************/


        if ($this->is_menu_pro())
        {
            /* Report permissions */

            /* USM Reports */
            $perm_report_overview   = $this->check_perm("report-menu", "ReportsReportServer");
            $perm_report_modules    = $this->check_perm("report-menu", "ReportsReportServer");
            $perm_report_layouts    = $this->is_user_admin();
            $perm_report_scheduler  = $this->check_perm("report-menu", "ReportsScheduler");

            $perm_usm_reports        = $perm_report_overview || $perm_report_modules || $perm_report_scheduler;


            /* OSSIM Reports */
            $perm_ossim_reports      = $this->check_perm("report-menu", "ReportsReportServer");

            $perm_m_reports          = $perm_ossim_reports || $perm_usm_reports;

            $this->menus['reports'] = array(
                "name"     => _("Reports"),
                "enabled"  => $perm_m_reports,
                "display"  => TRUE,
                "submenu"  => array(

                    /* USM Reports */
                    "usm_reports" => array(
                        "name"    => _("USM Reports"),
                        "enabled" => $perm_usm_reports,
                        "hmenu"   => array(
                            "overview" => array(
                                "name"    => _("Overview"),
                                "enabled" => $perm_report_overview,
                                "url"     => "/report/wizard_custom_reports.php",
                                "r_lmenu" => array (
                                    "new_custom_report" => array(
                                        "name"       => _("New Custom Report"),
                                        "enabled"    => $this->check_perm("report-menu", "ReportsCreateCustom"),
                                        "display_in" => "iframe",
                                        "url"        => "/report/wizard.php"
                                    )
                                )
                            ),

                            "modules" => array(
                                "name"    => _("Modules"),
                                "enabled" => $perm_report_modules,
                                "url"     => "/report/wizard_subreports.php"
                            ),


                            "layouts" => array(
                                "name"    => _("Layouts"),
                                "enabled" => $perm_report_layouts,
                                "url"     => "/report/wizard_profiles.php",
                                "r_lmenu" => array (
                                    "new_layout" => array(
                                        "name"       => _("New Layout"),
                                        "enabled"    => TRUE,
                                        "display_in" => "greybox",
                                        "url"        => "/report/wizard_manage_profiles.php"
                                    )
                                )
                            ),

                            "scheduler" => array(
                                "name"    => _("Scheduler"),
                                "enabled" => $perm_report_scheduler,
                                "url"     => "/report/wizard_scheduler.php",
                                "r_lmenu" => array (
                                    "new_scheduler" => array(
                                        "name"       => _("Schedule a Report"),
                                        "enabled"    => TRUE,
                                        "display_in" => "iframe",
                                        "url"        => "/report/wizard_scheduler.php?ws_new=new"
                                    )
                                )
                            )
                        )
                    ),

                    /* OSSIM Reports */
                    "ossim_reports" => array(
                        "name"    => _("OSSIM Reports"),
                        "enabled" => $perm_ossim_reports,
                        "hmenu"   => array(
                            "overview" => array(
                                "name"    => _("OSSIM Reports"),
                                "enabled" => TRUE,
                                "url"     => "/report/os_report_list.php"
                            )
                        )
                    )
                )
            );
        }
        else
        {
             /* Report permissions */

             /* Reports */
             $perm_ossim_reports = $this->check_perm("report-menu", "ReportsReportServer");

             $perm_m_reports     = $perm_ossim_reports;


             $this->menus['reports'] = array(
                "name"     => _("Reports"),
                "enabled"  => $perm_m_reports,
                "display"  => TRUE,
                "submenu"  => array(

                    /* Overview */
                    "ossim_reports" => array(
                        "name"   => _("Overview"),
                        "enabled" => $perm_ossim_reports,
                        "hmenu"   => array(
                            "overview" => array(
                                "name"    => _("Overview"),
                                "enabled" => TRUE,
                                "url"     => "/report/os_report_list.php"
                            )
                        )
                    )
                )
            );
        }



       /***********************************************************
        ---------------------- Configuration ----------------------
        ***********************************************************/


        /* Configuration permissions */

        /* Administration */
        $perm_backup           = $this->check_perm("environment-menu", "ToolsBackup");

        $perm_user_information = $this->check_perm("configuration-menu", "ConfigurationUsers");
        $perm_templates        = $this->is_menu_pro() && ($this->is_user_admin() || $this->is_user_proadmin());
        $perm_user_activity    = $this->check_perm("configuration-menu", "ConfigurationUserActionLog");

        /* Asset Structure */
        $perm_hosts            = $this->check_perm("environment-menu", "PolicyHosts");
        $perm_networks         = $this->check_perm("environment-menu", "PolicyNetworks");

        $perm_structure        = $this->is_menu_pro() && $perm_hosts && $perm_networks;


        $perm_users            = $perm_user_information || $perm_templates || $perm_user_activity || $perm_structure;

        $perm_main             = $this->is_user_admin();

        $perm_administration   = $perm_backup || $perm_users || $perm_main;


        /* Deployment */

        $perm_av_center = $this->is_user_admin();
        $perm_sensors   = $this->check_perm("configuration-menu", "PolicySensors");
        $perm_servers   = $this->check_perm("configuration-menu", "PolicyServers");
        $perm_wi        = $this->is_menu_pro() && $this->check_perm("configuration-menu", "PolicyServers");

        $perm_components  = $perm_av_center || $perm_sensors || $perm_servers || $perm_wi;

        if ($perm_av_center)
        {
            $url_components = '/av_center/index.php';
        }
        elseif ($perm_sensors || $perm_servers)
        {
            $url_components = '/sensor/sensor.php';
        }

        $perm_scheduler   = $this->check_perm("configuration-menu", "AlienVaultInventory");
        $perm_sec         = $this->is_user_admin() && $this->is_menu_pro();

        $perm_location    = $this->is_user_admin();

        $perm_deployment  = $perm_components || $perm_scheduler || $perm_sec || $perm_location;


        /* Threat Intelligence */
        $perm_policy     = $this->check_perm("configuration-menu", "PolicyPolicy");
        $perm_actions    = $this->check_perm("configuration-menu", "PolicyActions");
        $perm_directives = $this->check_perm("configuration-menu", "CorrelationDirectives");
        $perm_cm         = $this->check_perm("configuration-menu", "ComplianceMapping");
        $perm_cc         = $this->check_perm("configuration-menu", "CorrelationCrossCorrelation");
        $perm_ds         = $this->check_perm("configuration-menu", "ConfigurationPlugins");
        $perm_taxonomy   = $this->check_perm("configuration-menu", "ConfigurationPlugins");
        $perm_kdb        = $this->check_perm("configuration-menu", "Osvdb");
        $perm_ports      = $this->check_perm("configuration-menu", "PolicyPorts");


        $perm_ti = $perm_policy || $perm_actions || $perm_directives || $perm_cm || $perm_cc || $perm_ds || $perm_taxonomy || $perm_kdb || $perm_ports;

        $perm_m_configuration = $perm_administration || $perm_deployment || $perm_ti;

        $this->menus['configuration'] = array(
            "name"     => _("Configuration"),
            "enabled"  => $perm_m_configuration,
            "display"  => TRUE,
            "submenu"  => array(

                /* Administration */
                "administration" => array(
                    "name"    => _("Administration"),
                    "enabled" => $perm_administration,
                    "hmenu"   => array(
                        "users" => array(
                            "name"    => _("Users"),
                            "enabled" => $perm_users,
                            "url"     => "/session/users.php",
                            "l_lmenu" => array(

                                "user_information" => array(
                                    "name"    => _("User Information"),
                                    "enabled" => $perm_user_information,
                                    "url"     => "/session/users.php"
                                ),

                                "activity" => array(
                                    "name"    => _("Activity"),
                                    "enabled" => $perm_user_activity,
                                    "url"     => "/conf/userlog.php"
                                ),

                                "templates" => array(
                                    "name"    => _("Templates"),
                                    "enabled" => $perm_templates,
                                    "url"     => "/acl/templates.php"
                                ),

                                "structure" => array(
                                    "name"    => _("Structure"),
                                    "enabled" => $perm_structure,
                                    "url"     => "/policy/entities.php"
                                )
                            )
                        ),

                        "main" => array(
                            "name"    => _("Main"),
                            "enabled" => $perm_main,
                            "url"     => "/conf/index.php"
                        ),

                        "backup" => array(
                            "name"    => _("Backup"),
                            "enabled" => $perm_backup,
                            "url"     => "/backup/index.php"
                        )
                    )
                ),


                /* Deployment */
                "deployment" => array(
                    "name"    => _("Deployment"),
                    "enabled" => $perm_deployment,
                    "hmenu"   => array(

                        "components"  => array(
                            "name"    => _("Components"),
                            "enabled" => $perm_components,
                            "url"     => $url_components,
                            "l_lmenu" => array(
                                "alienvault_center" => array(
                                    "name"    => _("Alienvault Center"),
                                    "enabled" => $perm_av_center,
                                    "url"     => "/av_center/index.php"
                                ),

                                "sensors" => array(
                                    "name"    => _("Sensors"),
                                    "enabled" => $perm_sensors,
                                    "url"     => "/sensor/sensor.php"
                                ),

                                "servers" => array(
                                    "name"    => _("Servers"),
                                    "enabled" => $perm_servers,
                                    "url"     => "/server/server.php"
                                ),

                                "remote_interfaces" => array(
                                    "name"    => _("Remote Interfaces"),
                                    "enabled" => $perm_wi,
                                    "url"     => "/remote_interfaces/index.php"
                                )
                            )
                        ),

                        "scheduler"  => array(
                            "name"    => _("Scheduler"),
                            "enabled" => $perm_scheduler,
                            "url"     => "/av_inventory/index.php",
                            "l_lmenu" => array(
                                "nmap" => array(
                                    "name"    => _("NMAP"),
                                    "enabled" => TRUE,
                                    "url"     => "/av_inventory/index.php?s_type=nmap"
                                ),

                                "ocs" => array(
                                    "name"    => _("OCS"),
                                    "enabled" => TRUE,
                                    "url"     => "/av_inventory/index.php?s_type=ocs"
                                ),

                                "wmi" => array(
                                    "name"    => _("WMI"),
                                    "enabled" => TRUE,
                                    "url"     => "/av_inventory/index.php?s_type=wmi"
                                )
                            )
                        ),

                        "smart_event_collection" => array(
                            "name"    => _("Smart Event Collection"),
                            "enabled" => $perm_sec,
                            "url"     => "/asec/index.php"
                        ),

                        "location" => array(
                            "name"    => _("Locations"),
                            "enabled" => $perm_location,
                            "url"     => "/sensor/locations.php"
                        )
                    )
                ),

                /* Threat Intelligence */
                "threat_intelligence" => array(
                    "name"   => _("Threat Intelligence"),
                    "enabled" => $perm_ti,
                    "hmenu"   => array(
                        "policy" => array(
                            "name"    => _("Policy"),
                            "enabled" => $perm_policy,
                            "url"     => "/policy/policy.php",
                            "r_lmenu"   => array(
                                "edit_policy_groups" => array(
                                    "name"       => _("Edit Policy Groups"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "url"        => "/policy/policygroup.php"
                                )
                            )
                        ),

                        "actions" => array(
                            "name"    => _("Actions"),
                            "enabled" => $perm_actions,
                            "url"     => "/action/action.php"
                        ),

                        "ports" => array(

                            "name"    => _("Ports"),
                            "enabled" => $perm_ports,
                            "url"     => "/port/port.php",
                            "l_lmenu" => array(

                                "port" => array(
                                    "name"    => _("Ports"),
                                    "enabled" => TRUE,
                                    "url"     => "/port/port.php"
                                ),

                                "port_groups" => array(
                                    "name"    => _("Port Groups"),
                                    "enabled" => TRUE,
                                    "url"     => "/port/portgroup.php"
                                )
                            )
                        ),

                        "directives" => array(
                            "name"    => _("Directives"),
                            "enabled" => $perm_directives,
                            "url"     => "/directives/index.php"
                        ),

                        "compliance_mapping" => array(
                            "name"    => _("Compliance Mapping"),
                            "enabled" => $perm_cm,
                            "url"     => "/compliance/iso27001.php",
                            "l_lmenu" => array(

                                "iso_27001" => array(
                                    "name"    => _("ISO 27001"),
                                    "enabled" => TRUE,
                                    "url"     => "/compliance/iso27001.php"
                                ),

                                "pci_dss_2" => array(
                                    "name"    => _("PCI DSS 2.0"),
                                    "enabled" => TRUE,
                                    "url"     => "/compliance/pci-dss.php"
                                )
                            ),

                            "r_lmenu" => array(
                                "launch_scripts" => array(
                                    "name"       => _("Run Scripts"),
                                    "enabled"    => TRUE,
                                    "display_in" => "greybox",
                                    "url"        => "/compliance/mod_scripts.php"
                               )
                            )
                        ),

                        "cross_correlation" => array(
                            "name"    => _("Cross Correlation"),
                            "enabled" => $perm_cc,
                            "url"     => "/conf/pluginref.php"
                        ),

                        "data_source" => array(
                            "name"    => _("Data Source"),
                            "enabled" => $perm_ds,
                            "url"     => "/conf/plugin.php",
                            "r_lmenu" => array(
                                "data_source_groups" => array(
                                    "name"       => _("Data Source Groups"),
                                    "enabled"    => $this->check_perm("configuration-menu", "PluginGroups"),
                                    "display_in" => "iframe",
                                    "url"        => "/policy/plugingroups.php"
                                )
                            )
                        ),

                        "taxonomy" => array(
                            "name"    => _("Taxonomy"),
                            "enabled" => $perm_taxonomy,
                            "url"     => "/conf/category.php"
                        ),

                        "knowledgebase" => array(
                            "name"    => _("Knowledge Base"),
                            "enabled" => $perm_kdb,
                            "url"     => "/repository/index.php",
                            "r_lmenu" => array(
                                "new_document" => array(
                                    "name"       => _("New Document"),
                                    "enabled"    => TRUE,
                                    "display_in" => "iframe",
                                    "url"        => "/repository/repository_newdocument.php"
                                )
                            )
                        )

                    )
                )
            )
        );


        //Set default options

        $this->set_default_options();
    }


    /**
    * This function returns the menu structure
    *
    * @return array
    */
    public function get_menus()
    {
        return $this->menus;
    }


    /**
    * This function returns the menu option selected
    *
    * @return string
    */
    public function get_m_option()
    {
        return $this->m_option;
    }


    /**
    * This function returns the submenu option selected
    *
    * @return string
    */
    public function get_sm_option()
    {
        return $this->sm_option;
    }


    /**
    * This function returns the hmenu option selected
    *
    * @return string
    */
    public function get_h_option()
    {
        return $this->h_option;
    }


    /**
    * This function returns the lmenu option selected
    *
    * @return string
    */
    public function get_l_option()
    {
        return $this->l_option;
    }


    /**
    * This function sets the current menu and submenu option
    *
    * @param string $m_option    Menu option
    * @param string $sm_option   Submenu option
    *
    * @return void
    */
    public function set_menu_option($m_option, $sm_option)
    {
        $submenu = $this->menus[$m_option]['submenu'][$sm_option];

        if (is_array($submenu) && !empty($submenu))
        {
            $this->m_option  = $m_option;
            $this->sm_option = $sm_option;
            $this->h_option  = NULL;

            $hmenu = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'];

            if (is_array($hmenu) && !empty($hmenu))
            {
                foreach($hmenu as $hmenu_id => $hmenu_data)
                {
                    $hmenu_enabled = $hmenu_data['enabled'];

                    if ($hmenu_enabled == TRUE)
                    {
                        $this->h_option = $hmenu_id;
                        break;
                    }
                }
            }

            $this->l_option  = NULL;
        }
    }


    /**
    * This function sets the current hmenu option
    *
    * @param string $h_option  Hmenu option
    *
    * @return void
    */
    public function set_hmenu_option($h_option)
    {
        $hmenu = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'][$h_option];

        if (is_array($hmenu) && !empty($hmenu))
        {
            $this->h_option  = $h_option;
        }
        else
        {
            $this->h_option  = NULL;
        }

        $this->l_option = NULL;
    }


    /**
    * This function sets the current lmenu option
    *
    * @param string $l_option Lmenu option
    *
    * @return void
    */
    public function set_lmenu_option($l_option)
    {
        $lmenu   = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'][$this->h_option];
        $l_lmenu = $lmenu['l_lmenu'][$l_option];

        if (is_array($l_lmenu) && !empty($l_lmenu))
        {
            $this->l_option  = $l_option;
        }
        else
        {
            $this->l_option  = NULL;
        }
    }


    /**
    * This function returns the menu
    *
    * @return string
    */
    public function render_menu()
    {
        $output = '';

        foreach($this->menus as $menu_id => $menu_data)
        {
            $display_menu = $menu_data['display'];
            $menu_enabled = $menu_data['enabled'];

            if ($menu_enabled == FALSE)
            {
              continue;
            }

            $class = ($menu_id == $this->m_option) ? 'active' : 'default';
            $m_id  = $menu_id;

            $output_li = array();

            foreach($menu_data['submenu'] as $submenu_id => $submenu_data)
            {
                $submenu_enabled = $submenu_data['enabled'];

                if ($submenu_enabled == FALSE)
                {
                    continue;
                }

                $class  = ($menu_id == $this->m_option && $submenu_id == $this->sm_option) ? 'active' : 'default';
                $sm_id  = $menu_id."-".$submenu_id;

                $output_li[] = "<li id='li_$sm_id'><a id='sm_opt_$sm_id' class='$class' href='javascript:void(0);'>".$submenu_data['name']."</a></li>";
            }

            $display = ($display_menu == FALSE) ? 'm_hide' : 'm_show';

            $output .= "<li class='$display' id='li_$m_id'>
                            <a href='javascript:void(0);' id='m_opt_$m_id' class='$class'>".$menu_data['name']."</a>
                            <ul id='sm_$m_id'>
                                ".implode(PHP_EOL, $output_li)."
                            </ul>
                        </li>";

       }

       $output = "<ul id='menu' class='flexnav'>
                      $output
                  </ul>";

       return $output;
    }


    /**
    * This function returns the horizonal menu
    *
    * @return string
    */
    public function render_hmenu()
    {
        $output = '';

        $hmenu  = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'];

        if (is_array($hmenu) && !empty($hmenu))
        {
            $output_li = array();

            foreach($hmenu as $hmenu_id => $hmenu_data)
            {
                $hmenu_enabled = $hmenu_data['enabled'];

                $h_id   = $hmenu_id;
                $c_h_id = $this->m_option."-".$this->sm_option;

                if ($hmenu_enabled == FALSE)
                {
                    continue;
                }

                $class = ($hmenu_id == $this->h_option) ? 'active' : 'default';
                $p_url = parse_url($hmenu_data['url']);


                $url  = (empty($p_url['query'])) ? $hmenu_data['url']."?" : $hmenu_data['url']."&";
                $url .= "m_opt=".$this->m_option."&sm_opt=".$this->sm_option."&h_opt=".$hmenu_id;


                $output_li[] = "<li id='li_$h_id'><a id='h_opt_$h_id' class='$class' href='".$url."'>".$hmenu_data['name']."</a></li>";
            }

            $output =  "<div id='c_hmenu_$c_h_id'>
                            <ul id='hmenu'>
                                ".implode(PHP_EOL, $output_li)."
                            </ul>
                        </div>";
        }

        return $output;
    }


    /**
    * This function returns information about horizontal menu:
    *
    *    - For each main menu option:
    *       - Horizontal menu HTML code
    *       - First option available
    *           - URL
    *           - Name
    *       - Local menu option for every hmenu option
    *       - Bookmark for every hemnu option
    *
    * @return array
    */
    public function get_hmenus_info()
    {
        $info       = array();

        $m_option   = $this->m_option;
        $sm_option  = $this->sm_option;
        $h_option   = $this->h_option;

        foreach ($this->menus as $menu_id => $menu_data)
        {
            $menu_enabled = $menu_data['enabled'];

            if ($menu_enabled == FALSE)
            {
                continue;
            }

            foreach($menu_data['submenu'] as $submenu_id => $submenu_data)
            {
                $submenu_enabled = $submenu_data['enabled'];

                if ($submenu_enabled == FALSE)
                {
                    continue;
                }


                $this->set_menu_option($menu_id, $submenu_id);

                $default_url = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'][$this->h_option]['url'];

                $p_url  = parse_url($default_url);

                $default_url  = (empty($p_url['query'])) ? $default_url."?" : $default_url."&";
                $default_url .= "m_opt=".$this->m_option."&sm_opt=".$this->sm_option;

                $info[$menu_id.'-'.$submenu_id]['default_url']    = $default_url;
                $info[$menu_id.'-'.$submenu_id]['default_option'] = $this->h_option;
                $info[$menu_id.'-'.$submenu_id]['html']           = $this->render_hmenu();
                $info[$menu_id.'-'.$submenu_id]['items']          = array();

                foreach($submenu_data['hmenu'] as $hmenu_id => $hmenu_data)
                {
                    $hmenu_enabled = $hmenu_data['enabled'];

                    if ($hmenu_enabled == FALSE)
                    {
                        continue;
                    }

                    $info[$menu_id.'-'.$submenu_id]['items'][$hmenu_id]['default_loption'] = '';

                    if (is_array($hmenu_data['l_lmenu']) && !empty($hmenu_data['l_lmenu']))
                    {
                        $info[$menu_id.'-'.$submenu_id]['items'][$hmenu_id]['default_loption'] = key($hmenu_data['l_lmenu']);
                    }

                    //Getting the bookmarks hash
                    $info[$menu_id.'-'.$submenu_id]['items'][$hmenu_id]['bookmark']['url']   = $hmenu_data['url'];
                    $info[$menu_id.'-'.$submenu_id]['items'][$hmenu_id]['bookmark']['param'] = $hmenu_data['b_param'];
                }
            }
        }


        $this->set_menu_option($m_option, $sm_option);
        $this->set_hmenu_option($h_option);

        return $info;
    }


    /**
    * This function returns the local menu
    *
    * @return string
    */
    public function render_lmenu()
    {
        $output = '';

        $l_lmenu = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'][$this->h_option]['l_lmenu'];
        $r_lmenu = $this->menus[$this->m_option]['submenu'][$this->sm_option]['hmenu'][$this->h_option]['r_lmenu'];


        // Local menu (Left)
        if (is_array($l_lmenu) && !empty($l_lmenu))
        {
            $l_output_li = array();

            foreach($l_lmenu as $l_lmenu_id => $l_lmenu_data)
            {
                $l_lmenu_enabled = $l_lmenu_data['enabled'];

                if ($l_lmenu_enabled == FALSE)
                {
                    continue;
                }

                //Set first option by default
                if ($this->l_option == NULL)
                {
                    $this->l_option = $l_lmenu_id;
                }

                $class = ($l_lmenu_id == $this->l_option) ? 'active' : 'default';
                $p_url = parse_url($l_lmenu_data['url']);

                $url  = (empty($p_url['query'])) ? $l_lmenu_data['url']."?" : $l_lmenu_data['url']."&";
                $url .= "l_opt=".$l_lmenu_id;


                $li   = "<a id='ll_opt_$l_lmenu_id' class='$class' href='javascript: parent.av_menu.load_content(\"".$url."\")'>"
                            .$l_lmenu_data['name']
                        ."</a>";

                if ($class == 'active')
                {
                    $li .= PHP_EOL."<div class='c_arrow_down'><div class='arrow_down'></div></div>";
                }

                $l_output_li[] = "<li id='li_$l_lmenu_id'>".PHP_EOL.$li.PHP_EOL."</li>";
            }

            if (is_array($l_output_li) && !empty($l_output_li))
            {
                $output .= "<div class='c_l_lmenu' id='c_l_lmenu_".$this->h_option."'>
                               <ul class='l_lmenu' id='l_lmenu_".$this->h_option."'>".
                                    implode("<li class='li_sep'>|</li>", $l_output_li).PHP_EOL.'
                               </ul>
                           </div>'.PHP_EOL;
            }
        }


        // Local menu (Right)
        if (is_array($r_lmenu) && !empty($r_lmenu))
        {
            $r_output_li = array();

            foreach($r_lmenu as $r_lmenu_id => $r_lmenu_data)
            {
                $r_lmenu_enabled = $r_lmenu_data['enabled'];

                if ($r_lmenu_enabled == FALSE)
                {
                    continue;
                }

                $class  = ($r_lmenu_data['display_in'] == 'greybox') ? 'button m_greybox' : 'button';
                $class .= ' ' . $r_lmenu_data['b_class'];
                $p_url  = parse_url($r_lmenu_data['url']);

                $url    = $r_lmenu_data['url'];

                $r_output_li[] = "<a id='li_$r_lmenu_id' class='$class' href='".$url."'>".$r_lmenu_data['name']."</a>";
            }

            if (is_array($r_output_li) && !empty($r_output_li))
            {
                $output .= "<div class='c_r_lmenu' id='c_r_lmenu_".$this->h_option."'>".PHP_EOL.implode(PHP_EOL, $r_output_li).PHP_EOL.'</div>'.PHP_EOL;
            }
        }

        if (!empty($output))
        {
            $output = "<div class='lmenu' id='lmenu_".$this->h_option."'>".PHP_EOL.$output.PHP_EOL.'</div>'.PHP_EOL;
        }

        return $output;
    }


    /**
    * This function checks if logged user has access privileges to the section
    *
    * @param string $perm_name    Section
    * @param string $perm_value   Subsection
    *
    * @return boolean
    */
    public function check_perm($perm_name, $perm_value)
    {
        if ($this->is_user_admin())
        {
            return TRUE;
        }

        if ($this->allowed_menus[$perm_name][$perm_value] == 1)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function checks if the menu is a professional menu
    *
    *
    * @return boolean
    */
    private function is_menu_pro()
    {
        return ($this->menu_version == 'pro') ? TRUE : FALSE;
    }


    /**
    * This function checks if logged user is an admin
    *
    * @return boolean
    */
    private function is_user_admin()
    {
        if ($this->user->get_login() == AV_DEFAULT_ADMIN || $this->user->get_is_admin() == 1)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function checks if logged user is a proadmin
    *
    * @return boolean
    */
    private function is_user_proadmin()
    {
        if ($this->menu_version == 'pro')
        {
            return Acl::am_i_proadmin();
        }

        return FALSE;
    }


    /**
    * This function sets the first available option from menu
    *
    * @return void
    */
    private function set_default_options()
    {
        foreach ($this->menus as $menu_id => $menu_data)
        {
            $menu_enabled = $menu_data['enabled'];

            if ($menu_enabled == FALSE || $menu_id == 'settings' || $menu_id == 'support')
            {
                continue;
            }

            foreach($menu_data['submenu'] as $submenu_id => $submenu_data)
            {
                $submenu_enabled = $submenu_data['enabled'];

                if ($submenu_enabled == FALSE)
                {
                    continue;
                }

                $this->set_menu_option($menu_id, $submenu_id);
                break 2;
            }
        }

        if ($this->m_option == NULL && $this->sm_option == NULL)
        {
            $this->set_menu_option('settings', 'settings');
        }
    }


    /**
    * This function returns an url that activates menu options
    *
    * @param string $url        URL
    * @param string $m_option   [Optional] Menu option
    * @param string $sm_option  [Optional] Submenu option
    * @param string $h_option   [Optional] Hmenu option
    * @param string $l_option   [Optional] Lmenu option
    *
    * @return string
    */
    public static function get_menu_url($url, $m_option = '', $sm_option = '', $h_option = NULL, $l_option = NULL)
    {
        if (empty($url))
        {
            return 'javascript:void(0)';
        }

        $p_url     = parse_url($url);
        $url_query = '';

        if (!empty($m_option) && !empty($sm_option))
        {
            $url = (empty($p_url['query'])) ? $url."?" :  $url."&";

            $url_query = "m_opt=".urlencode($m_option)."&sm_opt=".urlencode($sm_option);

            if(!empty($h_option))
            {
                $url_query .= "&h_opt=".urlencode($h_option);
            }

            if(!empty($l_option))
            {
                $url_query .= "&l_opt=".urlencode($l_option);
            }

            $url = $url.$url_query;
        }

        return $url;
    }
}

/* End of file menu.inc */
/* Location: ../include/classes/menu.inc */