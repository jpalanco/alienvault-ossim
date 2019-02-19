from scenario.ossec import *
from scenario.server import *
scenarios_method_to_prepare_them = {
"Get the passlist when it doesn't exist in the remote system": [get_passlist_scenario1_prepare,
                                                                get_passlist_scenario1_restore],
"Get the modified registry modified entries for a known ossec-agent": [modified_registry_entries_prepare,
                                                                       modified_registry_entries_restore],
"Set the pass list when it doesn't exists on the local folder": [put_passfile_scenario1_prepare,
                                                                 put_passfile_scenario1_restore],
"Set the pass list when it exists":[put_passfile_scenario2_prepare,
                                    put_passfile_scenario2_restore],
"Test valid OSSEC Agent creation": [empty_ossec_keys_file,
                                    restore_ossec_keys_file],
"Test bad agent_ip parameter in OSSEC Agent creation": [empty_ossec_keys_file,
                                            restore_ossec_keys_file],
"Test bad agent_name parameter in OSSEC Agent creation": [empty_ossec_keys_file,
                                            restore_ossec_keys_file],
"Test error in OSSEC Agent creation": [add_fake_ossec_agent,
                                       restore_ossec_keys_file],
"Test PUT /server/<server_id>/nfsen/reconfigure": [prepare_nfsen_scenario1, restore_nfsen_scenario1],

"Test PUT /server/<server_id>/nfsen/reconfigure WITH WRONG FILE": [prepare_nfsen_scenario2, restore_nfsen_scenario2],
                                    }

def prepare_scenario(scenario_name):
    if not scenarios_method_to_prepare_them.has_key(scenario_name):
        return
    if scenarios_method_to_prepare_them[scenario_name][0] is None:
        return
    return scenarios_method_to_prepare_them[scenario_name][0]()

def restore_scenario(scenario_name):
    if not scenarios_method_to_prepare_them.has_key(scenario_name):
        return
    if scenarios_method_to_prepare_them[scenario_name][1] is None:
        return
    return scenarios_method_to_prepare_them[scenario_name][1]()
