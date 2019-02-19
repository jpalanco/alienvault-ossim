# -*- coding: utf-8 -*-
#
# © AlienVault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from AlienVault Inc. or its
# authorised licensees.

from __init__ import __version__
from uuid import UUID
from bson import BSON
from bson.codec_options import CodecOptions
from bson.binary import STANDARD


class Command(object):
    """Command Parent abstract class"""

    def __init__(self):
        pass

    def to_string(self):
        """Abstract method, must be overwritten"""
        pass

    def to_bson(self):
        """Abstract method, must be overwritten"""
        pass

    def is_idm_event(self):
        return False


class AgentDateCommand(Command):
    """WatchDogCommand agent-date command"""

    def __init__(self, tzone, agent_date):
        super(AgentDateCommand, self).__init__()
        self.tzone = tzone
        self.agent_date = agent_date

    def to_bson(self):
        data = {'agent-date': {'timestamp': float(self.agent_date), 'tzone': float(self.tzone)}}
        return BSON.encode(data)

    def to_string(self):
        return "agent-date agent_date=\"%s\" tzone=\"%s\"\n" % (self.agent_date, self.tzone)


class PluginUnknownState(Command):
    def __init__(self, plugin_id):
        super(PluginUnknownState, self).__init__()
        self.plugin_id = plugin_id

    def to_bson(self):
        data = {'plugin-process-unknown': {'plugin_id': int(self.plugin_id)}}
        return BSON.encode(data)

    def to_string(self):
        return "plugin-process-unknown plugin_id=\"%s\"\n" % self.plugin_id


class PluginStartState(Command):
    def __init__(self, plugin_id):
        super(PluginStartState, self).__init__()
        self.plugin_id = plugin_id

    def to_bson(self):
        data = {'plugin-process-started': {'plugin_id': int(self.plugin_id)}}
        return BSON.encode(data)

    def to_string(self):
        return "plugin-enabled plugin_id=\"%s\"\n" % self.plugin_id


class PluginStopState(Command):
    def __init__(self, plugin_id):
        super(PluginStopState, self).__init__()
        self.plugin_id = plugin_id

    def to_bson(self):
        data = {'plugin-process-stopped': {'plugin_id': int(self.plugin_id)}}
        return BSON.encode(data)

    def to_string(self):
        return "plugin-process-stopped plugin_id=\"%s\"\n" % self.plugin_id


class PluginEnableState(Command):
    def __init__(self, plugin_id):
        super(PluginEnableState, self).__init__()
        self.plugin_id = plugin_id

    def to_bson(self):
        data = {'plugin-enable': {'plugin_id': int(self.plugin_id)}}
        return BSON.encode(data)

    def to_string(self):
        return "plugin-enable plugin_id=\"%s\"\n" % self.plugin_id


class PluginDisableState(Command):
    def __init__(self, plugin_id):
        super(PluginDisableState, self).__init__()
        self.plugin_id = plugin_id

    def to_bson(self):
        data = {'plugin-disable': {'plugin_id': int(self.plugin_id)}}
        return BSON.encode(data)

    def to_string(self):
        return "plugin-disable plugin_id=\"%s\"\n" % self.plugin_id


class AppendPlugin(Command):
    MSG = 'session-append-plugin id="{0}" plugin_id="{1}" enabled="{2}" state="{3}"\n'
    PLUGIN_STARTED = 1
    PLUGIN_STOPPED = 2

    def __init__(self, plugin_id, sequence_id, state, enabled):
        super(AppendPlugin, self).__init__()
        self.plugin_id = plugin_id
        self.sequence_id = sequence_id
        self.state = state
        self.enabled = enabled

    def to_bson(self):
        state = int(AppendPlugin.PLUGIN_STARTED) if self.state == "start" else int(AppendPlugin.PLUGIN_STOPPED)
        data = {
            'session-append-plugin': {'plugin_id': self.plugin_id,
                                      'id': self.sequence_id,
                                      'enabled': bool(True),
                                      'state': state}}
        return BSON.encode(data)

    def to_string(self):
        return AppendPlugin.MSG.format(self.sequence_id,
                                       self.plugin_id,
                                       self.enabled,
                                       self.state)


class AgentServerConnectionMessage(Command):
    MSG = 'connect id="{0}" type="sensor" version="' + __version__ + '" sensor_id="{1}"\n'
    SESSION_TYPE_SENSOR = 3

    def __init__(self, sequence_id, sensor_id):
        super(AgentServerConnectionMessage, self).__init__()
        self.sequence_id = sequence_id
        self.sensor_id = sensor_id

    def to_bson(self):
        return BSON.encode({'connect': {'version': __version__,
                                        'id': int(self.sequence_id),
                                        'sensor_id': UUID(self.sensor_id),
                                        'type': int(AgentServerConnectionMessage.SESSION_TYPE_SENSOR)}},
                           codec_options=CodecOptions(uuid_representation=STANDARD))

    def to_string(self):
        return AgentServerConnectionMessage.MSG.format(self.sequence_id, self.sensor_id)


class AgentFrameworkConnectionMessage(Command):
    MSG = 'control id="{0}" action="connect" version="' + __version__ + ' sensor_id="{1}" \n'

    def __init__(self, connection_id, sensor_id):
        super(AgentFrameworkConnectionMessage, self).__init__()
        self.connection_id = connection_id
        self.sensor_id = sensor_id

    def to_bson(self):
        # Framework Daemon doesn't implement BSON protocol
        return self.to_string()

    def to_string(self):
        return AgentFrameworkConnectionMessage.MSG.format(self.connection_id, self.sensor_id)


class AgentServerCommandPong(Command):
    def __init__(self, timestamp=None):
        super(AgentServerCommandPong, self).__init__()
        self.timestamp = timestamp

    def to_bson(self):
        return BSON.encode({'pong': {'timestamp': self.timestamp}})

    def to_string(self):
        return "pong\n"


class AgentFrameworkCommandPong(Command):
    def __init__(self):
        super(AgentFrameworkCommandPong, self).__init__()

    def to_bson(self):
        return self.to_string()

    def to_string(self):
        return "pong\n"


class AgentFrameworkCommand(Command):
    def __init__(self, command):
        super(AgentFrameworkCommand, self).__init__()
        self.command = command

    def to_bson(self):
        return self.to_string()

    def to_string(self):
        return self.command

# vim:ts=4 sts=4 tw=79 expandtab:
