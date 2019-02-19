#
#  License:
#
#  Copyright (c) 2013 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

import os.path

from apimethods.utils import get_bytes_from_uuid
from db.methods.data import (get_current_status_messages,
                             set_current_status_message_as_viewed,
                             set_current_status_message_as_suppressed,
                             get_current_status_message_from_id,
                             get_current_status_messages_stats)
from db.methods.data import (load_mcserver_messages,
                             delete_messages,
                             db_insert_current_status_message,
                             load_messages_to_db)
from db.methods.system import get_system_id_from_local, db_get_hostname


def _format_handle_n_assets(message, additional_info):
    """
        message is pased by ref. I can modify it on fly
    """
    assets = additional_info['00000000000000000000000000010024']
    nassets = assets.get('over_assets', None)
    if nassets is not None:
        message['message_title'] = message['message_title'].replace('NUM_ASSETS',
                                                                    str(assets.get('exceeding_assets', 0)))


def _format_plugins_changed(message, additional_info):
    """
        Format the plugins
        The plugins_changes is a list with the full paths to plugins
    """
    plugin_names = [os.path.basename(base) for base in additional_info['plugins_changed']]
    message['message_title'] = message['message_title'].replace('PLUGINS_CHANGED', ", ".join(plugin_names))
    message['message_description'] = message['message_description'].replace('PATH_PLUGINS_CHANGED', ", ".join(
        additional_info['plugins_changed']))
    message['message_description'] = message['message_description'].replace('PLUGINS_CHANGED', ", ".join(plugin_names))


def _format_plugins_removed(message, additional_info):
    """
        Format remove plugins info
    """
    plugin_names = [os.path.basename(base) for base in additional_info['plugins_removed']]
    message['message_title'] = message['message_title'].replace('PLUGINS_REMOVED', ", ".join(plugin_names))
    message['message_description'] = message['message_description'].replace('PATH_PLUGINS_REMOVED', ", ".join(
        additional_info['plugins_removed']))
    message['message_description'] = message['message_description'].replace('PLUGINS_REMOVED', ", ".join(plugin_names))


def _format_rsyslog_files_removed(message, additional_info):
    """
        Format rsyslog_files_removed
    """
    rsyslog_names = [os.path.basename(base) for base in additional_info['rsyslog_files_removed']]
    message['message_title'] = message['message_title'].replace('RSYSLOG_FILES_REMOVED', ", ".join(rsyslog_names))
    message['message_description'] = message['message_description'].replace('PATH_RSYSLOG_FILES_REMOVED', ", ".join(
        additional_info['rsyslog_files_removed']))
    message['message_description'] = message['message_description'].replace('RSYSLOG_FILES_REMOVED',
                                                                            ", ".join(rsyslog_names))


def _format_rsyslog_files_changed(message, additional_info):
    """
        Format rsyslog_files_changed
    """
    rsyslog_names = [os.path.basename(base) for base in additional_info['rsyslog_files_changed']]
    message['message_title'] = message['message_title'].replace('RSYSLOG_FILES_CHANGED', ", ".join(rsyslog_names))
    message['message_description'] = message['message_description'].replace('PATH_RSYSLOG_FILES_CHANGED', ", ".join(
        additional_info['rsyslog_files_changed']))
    message['message_description'] = message['message_description'].replace('RSYSLOG_FILES_CHANGED',
                                                                            ", ".join(rsyslog_names))


def _format_enabled_plugins_limit(message, additional_info):
    """
        Format message for enabled plugins limit.
    """
    message['message_description'] = message['message_description'].replace('SYSTEM_IP',
                                                                            additional_info.get('system_ip',
                                                                                                '127.0.0.1'))
    message['message_description'] = message['message_description'].replace(
        'PLG_ENABLED',
        str(additional_info.get('plugins_enabled_total', 85))
    )
    plugins_allowed = additional_info.get('plugins_allowed_to_add', 15)
    # If we reached the limit -> get an absolute number of exceeded plugins
    disable_plg_count = str(abs(plugins_allowed)) if plugins_allowed < 0 else 'any'
    message['message_description'] = message['message_description'].replace('PLG_CAN_ADD', str(plugins_allowed))
    message['message_actions'] = message['message_actions'].replace('PLG_DISABLE', disable_plg_count)


def _format_feed_auto_updates(message, additional_info):
    """
        Format message for automatic feed updates.
    """
    system_name = 'USM SYSTEM'
    updated_ips = failed_ips = ''
    update_results = additional_info['update_results']
    failed_date = message['creation_time'].strftime("%Y-%m-%d %H:%M:%S")

    # Sort by updated_at date:
    sorted_result_ips = sorted(update_results, key=lambda x: update_results[x].get('updated_at'))

    for system_ip in sorted_result_ips:
        update_data = update_results[system_ip]
        name_success, name = db_get_hostname(update_data['system_id'])
        updated_ips += '* {} ({}) on {} UTC\n'.format(name if name_success else system_name,
                                                      system_ip,
                                                      update_data['updated_at'])
        if not update_data['result']:
            failed_ips += '* {} ({}) on {} - {} UTC\n'.format(name if name_success else system_name,
                                                              system_ip,
                                                              update_data['updated_at'],
                                                              update_data['message'])
            # replace the default with failed date of last update attempt.
            failed_date = update_data['updated_at']

    if additional_info['all_updated']:
        message['message_actions'] = updated_ips
    if additional_info['error_on_update']:
        message['message_actions'] = failed_ips
        message['message_title'] = message['message_title'].replace('DATE', failed_date)


def _format_system_name(message, additional_info):
    """
        Format system name
    """
    name_success, name = db_get_hostname(additional_info['system_id'])

    message['message_title'] = message['message_title'].replace('SYSTEM_NAME', 'Unknown' if not name_success else name)
    message['message_description'] = message['message_description'].replace('SYSTEM_NAME',
                                                                            'Unknown' if not name_success else name)


def format_messages(messages):
    """
        Format message
        directly ported from os-sim/include/classes/system_notifications.inc
        tz is the current tz for messages
        If email is True we don't format TIMESTAMP field
    """
    cases = {
        '00000000000000000000000000010024': _format_handle_n_assets,
        'plugins_changed': _format_plugins_changed,
        'plugins_removed': _format_plugins_removed,
        'plugins_enabled_total': _format_enabled_plugins_limit,
        'update_results': _format_feed_auto_updates,
        'rsyslog_files_removed': _format_rsyslog_files_removed,
        'rsyslog_files_changed': _format_rsyslog_files_changed,
        'system_id': _format_system_name
    }
    for message in messages:
        # First special case for message_id
        additional_info = message.get('additional_info', None)
        if additional_info is not None:
            for key in [x for x in additional_info.keys() if x in cases.keys()]:
                cases[key](message=message, additional_info=additional_info)
        # The message_creation is a datetime.datetime object
        message['message_description'] = message['message_description'].replace('TIMESTAMP',
                                                                                message['creation_time'].strftime(
                                                                                    "%Y-%m-%d %H:%M:%S") + " UTC")


def get_status_messages(component_id=None,
                        message_level=None,
                        order_by=None,
                        page=None,
                        page_row=None,
                        order_desc=None,
                        component_type=None,
                        message_id=None,
                        message_type=None,
                        search=None,
                        only_unread=None,
                        login_user=None,
                        is_admin=False):
    """Returns the list of current_status messages matching the given criteria.
    Args:
        component_id(UUID str): Component ID related with the message
        level([str]): Message level
        order_by(str): Current status field by which you want to sort the results.
        page(int): Page number
        page_row(int): Number of items per page
        order_desc(Boolean or None): Specify whether you want to sort the results in descendig order or not.
        component_type(str): Component Type related with the message
        message_id(UUID str): The message ID you are looking for
        message_type([str]): Kind of message you want to retrieve.
        search(str): It's a free text to search for message title
        only_unread(Boolean or None): If true, retrieve only unread messages
        login_user (admin): logged user on the system
    Returns:
        A tuple (boolean,data) where the first argument indicates whether the operation went well or not,
        and the second one contains the data, in case the operation went wll or an error string otherwise

    """
    success, data = get_current_status_messages(component_id, message_level, order_by, page, page_row, order_desc,
                                                component_type, message_id, message_type, search, only_unread,
                                                login_user, is_admin)
    if not success:
        return False, "Couldn't retrieve status messages from the database"
    if not success:
        return False, "Can't format message"
    format_messages(data['messages'])

    return True, {'messages': data['messages'], 'total': data['total']}


def get_status_messages_stats(search=None, only_unread=False, login_user=None, is_admin=False):
    """
    Retrieves a list of current status messages stats
    :return: (bool, {stats, total})
    """
    success, data = get_current_status_messages_stats(search, only_unread, login_user, is_admin)

    if not success:
        return False, "Couldn't retrieve status messages statistics from the database"
    stats = data['stats']
    return True, {'stats': stats, 'total': len(stats)}


def set_status_message_as_viewed(status_message_id, viewed):
    """Sets the given status message as viewed"""
    return set_current_status_message_as_viewed(status_message_id, viewed)


def set_status_message_as_suppressed(status_message_id, suppressed):
    """Sets the current status message as suppressed"""
    return set_current_status_message_as_suppressed(status_message_id, suppressed)


def get_status_message_by_id(message_id, is_admin=False):
    return get_current_status_message_from_id(message_id)


def load_external_messages_on_db(messages):
    """Loads the downloaded messages into the database"""
    message_list = []
    messages_to_be_removed = []
    for message in messages:
        msg_id_binary = get_bytes_from_uuid(str(message['msg_id']))
        if message['status'] == 'delete':
            messages_to_be_removed.append(msg_id_binary)
            continue
        message_list.append(message)
    success = True
    data = ""
    if len(message_list) > 0:
        success, data = load_mcserver_messages(message_list)
    success_remove = True
    data_remove = ""
    if len(messages_to_be_removed) > 0:
        success_remove, data_remove = delete_messages(messages_to_be_removed)
    return ((success and success_remove), {'loaded': data, 'removed': data_remove})


def insert_current_status_message(message_id, component_id, component_type, additional_info=None, replace=False):
    """Inserts a new notification on the system. The related message id should exists.
    Args:
        message_id (str:uuid string): Message id related with the notification
        component_id(str:uuid string): Component id related with the notification (Could be none for external messages)
        component_type(str): Component type. Allowed values: ('net','host','user','sensor','server','system','external')
        additional_info (str:json): Additional information you want to store.
    Returns:
        success(bool): True if the operation went well, False otherwise
        msg(str): A message string that will contain some kind of information in case of error"""

    if component_id == "local":
        success, component_id = get_system_id_from_local()
        if not success:
            return False, "Cannot retrieve the local system id"
    return db_insert_current_status_message(message_id, component_id, component_type, additional_info, replace)


def insert_custom_message(message, component_id, component_type, additional_info, replace, created=None):
    success, data = load_messages_to_db([message])
    if success:
        return db_insert_current_status_message(message.id, component_id, component_type, additional_info, replace,
                                                created)
    return (False, "error")
