# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2017 AlienVault
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
import db
import api_log
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from sqlalchemy import text

from db.models.alienvault import (
    PluginData,
    Plugin_Sid,
    Plugin,
    Host_Scan,
    Plugin_Sid_Changes,
    Plugin_Sid_Orig,
    Plugin_Reference,
    Plugin_Group_Descr,
)

from apimethods.decorators import require_db


@require_db
def update_plugin_data(plugin_id, **kwargs):
    """ Updates custom plugin data in the DB.

    Args:
        plugin_id: (int) unique plugin ID.
    Keyword Args:
        plugin_name: (str) plugin name.
        vendor: (str) vendor name.
        model: (str) model name.
        version: (str) plugin version.
        ctx: (str) TBD.
        nsids: (int) Number of rules in plugin (number of Event Types).
        plugin_type: (int) plugin type: 1 - custom plugin.
        product_type: (int) ID of product data source.

    Returns:
        tuple with two elements:
            (bool) True on success or False otherwise;
            error message in case of fail
    """
    try:
        db.session.begin()
        db.session.query(PluginData).filter(PluginData.plugin_id == plugin_id).update(kwargs)
        db.session.commit()
    except Exception as err:
        api_log.error("[update_plugin_data] Failed due to: {}".format(str(err)))
        db.session.rollback()
        return False, 'Failed to update plugin data for "{}": {}'.format(plugin_id, str(err))
    return True, ""


@require_db
def insert_plugin_data(plugin_id, plugin_name, vendor, model, version,
                       ctx="", nsids=0, plugin_type=1, product_type=None):
    """ Inserts custom plugin data into the DB.

    Args:
        plugin_id: (int) unique plugin ID.
        plugin_name: (str) plugin name.
        vendor: (str) vendor name.
        model: (str) model name.
        version: (str) plugin version.
        ctx: (str)
        plugin_type: (int) plugin type: 1 - custom plugin.
        product_type: (int) ID of product data source.

    Returns:
        tuple with two elements:
            (bool) True on success or False otherwise;
            error message in case of fail
    """
    try:
        db.session.begin()
        plugin_data = PluginData()
        plugin_data.ctx = ctx
        plugin_data.plugin_id = plugin_id
        plugin_data.plugin_name = plugin_name
        plugin_data.vendor = vendor
        plugin_data.model = model
        plugin_data.version = version
        plugin_data.nsids = nsids
        plugin_data.plugin_type = plugin_type
        plugin_data.product_type = product_type
        db.session.add(plugin_data)
        db.session.commit()
    except Exception as err:
        api_log.error("[insert_plugin_data] Failed due to: {}".format(str(err)))
        db.session.rollback()
        return False, 'Failed to insert plugin data for "{}-{}": {}'.format(plugin_id, plugin_name, str(err))

    return True, ""


@require_db
def remove_plugin_data(plugin_id):
    """Removes the plugin information from the database
    Args:
        plugin_id(int):  The plugin id which you want to remove
    Returns:
        - A PluginData object or null
    """
    rc = True
    try:
        db.session.begin()
        db.session.query(PluginData).filter(PluginData.plugin_id == plugin_id).delete()
        db.session.query(Plugin_Sid).filter(Plugin_Sid.plugin_id == plugin_id).delete()
        db.session.query(Plugin).filter(Plugin.id == plugin_id).delete()
        db.session.query(Host_Scan).filter(Host_Scan.plugin_id == plugin_id).delete()
        db.session.query(Plugin_Sid_Changes).filter(Plugin_Sid_Changes.plugin_id == plugin_id).delete()
        db.session.query(Plugin_Sid_Orig).filter(Plugin_Sid_Orig.plugin_id == plugin_id).delete()
        db.session.query(Plugin_Reference).filter(Plugin_Reference.plugin_id == plugin_id).delete()
        db.session.query(Plugin_Group_Descr).filter(Plugin_Group_Descr.plugin_id == plugin_id).delete()
        db.session.commit()
    except Exception as e:
        api_log.error("[remove_plugin_data] {}".format(str(e)))
        db.session.rollback()
        rc = False
    return rc


@require_db
def save_plugin_from_raw_sql(plugin_sql):
    """ Stores plugin and related sids into the DB.

    Args:
        plugin_sql: (str) plugin sql statements.

    Returns:
        status: (bool) True if success or False otherwise.
        msg: (str) Error message in case of failure.
    """
    status, msg = True, 'ok'
    try:
        db.session.begin()
        db.session.connection(mapper=Plugin).execute(text(plugin_sql))
        db.session.commit()
    except Exception as e:
        api_log.error("[save_plugin_from_file] {}".format(str(e)))
        db.session.rollback()
        status, msg = False, 'Failed to save plugin into the DB.'

    return status, msg


@require_db
def get_plugin_list_from_plugin_data():
    """Returns a list of plugins from the table alienvault.plugin_data
    Args:
        - void
    Returns:
        - A list of PluginData objects
    """
    try:
        data = db.session.query(PluginData).all()
    except NoResultFound:
        db.session.rollback()
        data = []
    return data


@require_db
def get_plugin_data_for_plugin_id(plugin_id):
    """Returns the plugin data information for the given plugin id
    Args:
        plugin_id(int):  The plugin id of which you want to get the information
    Returns:
        - A PluginData object or null
    """
    try:
        data = db.session.query(PluginData).filter(PluginData.plugin_id == plugin_id).one()
    except (NoResultFound, MultipleResultsFound):
        db.session.rollback()
        data = None
    return data


@require_db
def get_plugin_sids_for_plugin_id(plugin_id):
    """Returns a list of plugin sids for a given plugin id.

    Args:
        plugin_id(int):  The plugin id of which you want to get the plugin sids
    Returns:
        a list of PluginSids Objects
    """
    try:
        data = db.session.query(Plugin_Sid).filter(Plugin_Sid.plugin_id == plugin_id).all()
    except NoResultFound:
        db.session.rollback()
        data = []
    return data

