<?php
header("Content-type: text/javascript");
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


require_once 'classes/Util.inc';

echo "var avc_messages = new Array();
          avc_messages['retrieve_data']    = '"._("Retrieving data")." ...';
          avc_messages['refreshing']       = '"._("Refreshing")." ...';
          avc_messages['loading_pkg_info'] = '"._("Loading package information")." ...';
     ";

echo "var labels  = new Array();
    labels['error_found']      = '"._("The following errors occurred").":';
    labels['host_no_found']    = '"._("Hostname or IP not found.  Please, use another search term")."';
    labels['cached_info']      = '"._("Your system is down.  Information displayed is cached")."';
    labels['show_tree']        = '"._("Show tree")."';
    labels['hide_tree']        = '"._("Hide tree")."';
    labels['pkg_pending']      = '"._("Packages pending updates")."';
    labels['new_updates']      = '"._("New update available")."';
    labels['sw_pending']       = '"._("Retrieving information about available updates")."';
    labels['sw_update']        = '"._("Checking software updates. This process might take several minutes")."';
    labels['view_details']     = '"._("Click here to view details")."';
    labels['install_item']     = '"._("Install item")."';
    labels['install_items']    = '"._("Install items")."';
    labels['show_details']     = '"._("Show details")."';
    labels['hide_details']     = '"._("Hide details")."';
    labels['av_view']          = '"._("AlienVault Package Information")."';
    labels['deb_view']         = '"._("Debian Package Information")."';
    labels['rem_update']       = '"._("Remote Update")."';
    labels['update_progress']  = '"._("Update in progress, please wait")."';
    labels['update_error']     = '"._("Update error")."';
    labels['cancel']           = '"._("Cancel")."';
    labels['update_ok']        = '"._("System updated successfully")."';
    labels['check']            = '"._("Check for new updates")."';
    labels['install']          = '"._("Install")."';
    labels['upgrade']          = '"._("Upgrade")."';
    labels['upgrading']        = '"._("Upgrading")."';
    labels['upgrade_system']   = '".Util::js_entities(_("Please read the release notes before upgrading your system. \nWould you like to proceed?"))."';
    labels['only_rules']       = '"._("Only Rules")."';
    labels['loading']          = '"._("Loading")."';
    labels['checking']         = '"._("Checking")."';
    labels['update_manager']   = '"._("Update Manager")."';
    labels['search']           = '"._("Search by hostname or IP")."';
    labels['error_search']     = '"._("Error retrieving AlienVault Component")."';
    labels['error_ret_info']   = '"._("Error! AlienVault Components information not found")."';
    labels['error_section']    = '"._("Access failed, please try it again later")."';
    labels['apply_cnf']        = '"._("Applying configuration, please wait")."';
    labels['ret_log']          = '"._("Retrieving log information, please wait")."';
    labels['ret_info']         = '"._("Retrieving information, please wait")."';
    labels['ret_info_sec']     = '"._("Retrieving information from remote server, please wait")."';
    labels['start_searching']  = '"._("Start searching")."';
    labels['find_next']        = '"._("Find next")."';
    labels['find_previous']    = '"._("Find previous")."';
    labels['replace']          = '"._("Replace")."';
    labels['replace_all']      = '"._("Replace all")."';
    labels['clear_search']     = '"._("Clear Search")."';
    labels['invalid_action']   = '"._("This action is not valid. Please try again")."';
    labels['delete']           = '"._("Delete")."';
    labels['save_changes']     = '"._("You have made any changes without clicking Apply Changes, your changes will be lost.")."';
    labels['save']             = '"._("Do you want to save them?")."';
    labels['server_found']     = '"._("This server already exists")."';
    labels['special_changes']  = '"._("This new configuration requires logging in again.  Please, wait <span id =\"c_time\"></span> seconds until you get the login page <span id =\"c_new_ip\">or click <a id =\"link_new_ip\">here<a></span>")."';
    labels['sc_reboot_system'] = '"._("System restart required. You will be logged out after <span id =\"c_time\"></span> seconds.  Please, go to the console menu and select \"Reboot Appliance\"")."';
    labels['reboot_system']    = '"._("System restart required. Please, select \"Reboot Appliance\" in the console menu")."';
    labels['st_up']            = '"._("UP")."';
    labels['st_down']          = '"._("DOWN")."';
    labels['st_unknown']       = '"._("RETRIEVING")."';
    labels['active_ha']        = '["._("Active HA")."]';
    labels['passive_ha']       = '["._("Passive HA")."]';
    labels['no_remove_system'] = '"._("System can not be removed")."';
    labels['remove_system']    = '"._("Remove System")."';
    labels['npkg_unknown']     = '"._("Unknown")."';
    labels['stop_service']     = '"._("Stopping service")."';
    labels['check_service']    = '"._("Checking status")."';
    labels['check_status']     = '"._("Checking system status. This process can take a few seconds, please wait")."';
    labels['rcnfg_executed']   = '"._("Reconfig is running, wait a moment until this process has been completed")."';
    labels['deleting']         = '"._("Deleting the system, please wait until the process ends").".';
    labels['delete_msg']       = '".Util::js_entities(_("This action will remove the system, related sensor, server and assets, and disable all related policies. Are you sure you want to remove __SYSTEM__?")).".';
    labels['delete_msg_down']  = '".Util::js_entities(_("The system you are trying to remove is not reachable so we will not be able to purge the remote configuration. Are you sure you want to remove __SYSTEM__?")).".';
    labels['delete_yes']       = '"._("Yes").".';
    labels['delete_no']        = '"._("No").".';
    labels['unknown_error']    = '"._("Sorry, operation was not completed due to an error when processing the request")."';
    ";
