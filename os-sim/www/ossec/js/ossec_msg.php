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


require_once (dirname(__FILE__) . '/../conf/config.inc');

echo "var ossec_msg = new Array();
	      ossec_msg['loading']              = '"._("Loading data")." ...';
		  ossec_msg['reloading']            = '"._("Reloading data")." ...';
          ossec_msg['saving']               = '"._("Saving data")." ...';
		  ossec_msg['no_data_tree']         = '"._("No data found")."';
		  ossec_msg['no_rules']             = '"._("Action not allowed. The rule file must have one rule at least")."';
		  ossec_msg['rule_file_not_found']  = '"._("Rule file not found")."';
		  ossec_msg['f_save_changes']       = '"._("Please, save the pending changes before editing")."';
		  ossec_msg['reloading_in']         = '"._("Re-loading in")."';
		  ossec_msg['unknown_error']        = '"._("Sorry, operation was not completed due to an unknown error")."';
		  ossec_msg['add_agent']            = '"._("Adding agent")." ...';
		  ossec_msg['p_action']             = '"._("Processing action")." ...';
		  ossec_msg['cnf_a_error']          = '"._("Error! OSSEC configuration not updated successfully")."';
		  ossec_msg['delete_row']           = '".Util::js_entities(_("Are you sure to delete this row"))."?';
		  ossec_msg['delete_file']          = '".Util::js_entities(_("Are you sure to delete this file"))."?';
		  ossec_msg['i_action']             = '"._("Illegal action")."';
		  ossec_msg['add_m_entry']          = '"._("Adding Monitoring entry")." ...';
		  ossec_msg['delete_m_entry']       = '"._("Deleting Monitoring entry")." ...';
		  ossec_msg['update_m_entry']       = '"._("Updating Monitoring entry")." ...';
		  ossec_msg['load_agent']           = '"._("Loading agent information")." ...';
		  ossec_msg['d_oc_action_w']        = '"._("Generating preconfigured agent for Windows")." ...';
		  ossec_msg['d_oc_action_u']        = '"._("Generating preconfigured agent for UNIX")." ...';
		  ossec_msg['a_deployment_w']       = '"._("Automatic deployment for Windows")."';
		  ossec_msg['deploying_agent']      = '"._("Deploying agent")." ...';
		  "; 		  

echo "var labels = new Array();
          labels['view_errors']          = '"._("View errors")."';
		  labels['add']                  = '"._("Add")."';
		  labels['update']               = '"._("Update")."';
		  labels['updating']             = '"._("Updating")." ...';
		  labels['save']                 = '"._("Save")."';
		  labels['saving']               = '"._("Saving")." ...';
		  labels['seconds']              = '"._("second(s)")."';
		  labels['attribute']            = '"._("Attribute")."';
	      labels['txt_node']             = '"._("Text Node")."';
		  labels['adding']               = '"._("Adding ")." ...';
	      labels['delete']               = '"._("Delete")."';
		  labels['arrow']                = '"._("Arrow")."';
		  labels['clone']                = '"._("Clone")."';
	      labels['show_at']              = '"._("Show Attributes")."';
		  labels['hide_at']              = '"._("Hide Attributes")."';
		  labels['txt_node_at']          = '"._("Text Node Attributes")."';
		  labels['name']                 = '"._("Name")."';
		  labels['value']                = '"._("Value")."';
		  labels['actions']              = '"._("Actions")."';
	      ";		  

?>