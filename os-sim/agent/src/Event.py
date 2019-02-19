#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2015 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

from time import mktime, strptime, time
from base64 import b64encode
from bson.binary import Binary
from uuid import UUID, uuid1
from bson import BSON
#
# LOCAL IMPORTS
#
from command import Command
from Logger import Logger
logger = Logger.logger
# TODO It could be great to refactor this class as a command.


class Event(Command):
    EVENT_BSON = {
        'type': 'str',
        'date': 'int64',
        'sensor': 'str',
        'device': 'str',
        'interface': 'str',
        'plugin_id': 'int32',
        'plugin_sid': 'int32',
        'priority': 'int32',
        'protocol': 'str',
        'src_ip': 'str',
        'dst_ip': 'str',
        'src_port': 'int32',
        'dst_port': 'int32',
        'username': 'str',
        'password': 'str',
        'filename': 'str',
        'userdata1': 'str',
        'userdata2': 'str',
        'userdata3': 'str',
        'userdata4': 'str',
        'userdata5': 'str',
        'userdata6': 'str',
        'userdata7': 'str',
        'userdata8': 'str',
        'userdata9': 'str',
        'occurrences': 'int32',
        'log': 'binary',
        'snort_sid': 'int32',
        'snort_cid': 'int32',
        'fdate': 'str',
        'tzone': 'double',
        'ctx': 'uuid',
        'sensor_id': 'uuid',
        'event_id': 'uuid',
        'binary_data': 'str',
        'domain': 'str',
        'mail': 'str',
        'os': 'str',
        'cpu': 'str',
        'video': 'str',
        'service': 'str',
        'software': 'str',
        'ip': 'str',
        'mac': 'str',
        'inventory_source': 'int32',
        'login': 'bool',
        'pulses': 'object'
    }
    EVENT_BASE64 = [
        'username',
        'password',
        'filename',
        'userdata1',
        'userdata2',
        'userdata3',
        'userdata4',
        'userdata5',
        'userdata6',
        'userdata7',
        'userdata8',
        'userdata9',
        # 'binary_data',
        'log',
        'domain',
        'mail',
        'os',
        'cpu',
        'video',
        'service',
        'software',
    ]
    EVENT_TYPE = 'event'
    EVENT_ATTRS = [
        "type",
        "date",
        "sensor",
        "device",
        "interface",
        "plugin_id",
        "plugin_sid",
        "priority",
        "protocol",
        "src_ip",
        "src_port",
        "dst_ip",
        "dst_port",
        "username",
        "password",
        "filename",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "occurrences",
        "log",
        "snort_sid",  # snort specific
        "snort_cid",  # snort specific
        "fdate",
        "tzone",
        "ctx",
        "sensor_id",
        "event_id",
        "binary_data",
        "pulses"
    ]

    def __init__(self):
        self.event = {}
        self.event["event_type"] = self.EVENT_TYPE
        self.normalized = False
        self.is_idm = (self.EVENT_TYPE == "idm-event")

    def __setitem__(self, key, value):
        if key in ["sensor", "device"] and self.is_idm:
            return
        # Those fields were added to handle plugins by device.
        if key in ['pid', 'cpe', 'device_id']:
            return

        if isinstance(value, basestring) and key not in self.EVENT_BASE64:
            value = value.rstrip('\n')
        if key == "sensor":  # Back compatibility
            if 'device' in self.event:
                device_data = self.event['device']
                if device_data != "":
                    return
            key = "device"

        if key in self.EVENT_ATTRS:
            self.event[key] = value  # self.sanitize_value(value)
            if key == "date" and not self.normalized:
                # Fill with a default date.
                date_epoch = int(time())
                # Try first for string dates.
                try:
                    date_epoch = int(mktime(strptime(value, "%Y-%m-%d %H:%M:%S")))
                    self.event["fdate"] = value
                    self.event["date"] = date_epoch
                    self.normalized = True
                except ValueError:
                    logger.error("There was an error parsing a string date (%s)" % value)
        elif key != 'event_type' and not isinstance(self, EventIdm):
            logger.error("Bad event attribute: %s" % key)

    def __getitem__(self, key):
        return self.event.get(key, None)

    def __repr__(self):
        """Event representation.
        Return a string containing a printable representation of an object
        https://docs.python.org/2/library/functions.html#repr
        """
        return self.to_string()

    def to_string(self):
        event = self.__class__.EVENT_TYPE.encode('utf-8')
        for attr in self.EVENT_ATTRS:
            if self[attr]:
                value = self.event[attr]
                if attr in self.EVENT_BASE64:
                    value = b64encode(value)
                event += ' %s="%s"' % (attr, value)

        if not self.is_idm:
            event += ' event_id="%s"' % Event.__get_uuid()

        return event + "\n"

    def dict(self):
        # return the internal hash
        return self.event

    def sanitize_value(self, string):
        return str(string).strip().replace("\"", "\\\"").replace("'", "")

    def is_idm_event(self):
        return self.is_idm

    def to_bson(self):
        event_data = {}
        for (attr, t) in self.EVENT_BSON.items():
            if self[attr]:
                data = self[attr]
                # Now code the data
                if t == 'str':
                    event_data[attr] = str(data)
                elif t == 'uuid':
                    event_data[attr] = UUID(data)
                elif t == 'int32':
                    # Well, this ONLY WORKS in PY2!!!! 
                    event_data[attr] = int(data)
                elif t == 'int64':
                    event_data[attr] = long(data)
                elif t == 'binary':
                    event_data[attr] = Binary(bytes(data))
                elif t == 'double':
                    event_data[attr] = float(data)
                elif t == 'bool':
                    event_data[attr] = data.lower() in ('yes', 'y', 'true', 't', '1')
                elif t == 'object':
                    event_data[attr] = data
        if not self.is_idm:
            event_data['event_id'] = Event.__get_uuid()
        return BSON.encode({self.EVENT_TYPE: event_data})

    def get(self, key, default_value):
        return self.event.get(key, default_value)

    @staticmethod
    def __get_uuid():
        ev_uuid = uuid1()
        return UUID(int=(((ev_uuid.int & 0x00000000ffffffffffffffffffffffff) << 32) + ev_uuid.time_low))


class WatchRule(Event):
    EVENT_TYPE = 'event'

    EVENT_BASE64 = [
        'username',
        'password',
        'filename',
        'userdata1',
        'userdata2',
        'userdata3',
        'userdata4',
        'userdata5',
        'userdata6',
        'userdata7',
        'userdata8',
        'userdata9',
        # 'binary_data',
        'log',
        'domain',
        'mail',
        'os',
        'service'
    ]

    EVENT_ATTRS = [
        "type",
        "date",
        "fdate",
        "tzone",
        "sensor",
        "device",
        "interface",
        "src_ip",
        "dst_ip",
        "protocol",
        "plugin_id",
        "plugin_sid",
        "condition",
        "value",
        "port_from",
        "src_port",
        "port_to",
        "dst_port",
        "interval",
        "from",
        "to",
        "absolute",
        "log",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "filename",
        "username",
        "ctx",
        "sensor_id",
        "event_id"
    ]


class HostInfoEvent(Event):
    EVENT_TYPE = 'idm-event'
    EVENT_BSON = {
        'device': 'str',
        'username': 'str',
        'password': 'str',
        'filename': 'str',
        'userdata1': 'str',
        'userdata2': 'str',
        'userdata3': 'str',
        'userdata4': 'str',
        'userdata5': 'str',
        'userdata6': 'str',
        'userdata7': 'str',
        'userdata8': 'str',
        'userdata9': 'str',
        'ctx': 'uuid',
        # 'log',
        'domain': 'str',
        'mail': 'str',
        'organization': 'str',
        'service': 'str',
        'software': 'str',
        'hostname': 'str',
        'os': 'str',
        'cpu': 'str',
        'memory': 'int32',
        'video': 'str',
        'state': 'str',
        'ip': 'str',
        'mac': 'str',
        'logon': 'bool',
        'logoff': 'bool',
        'reliability': 'str',
        'inventory_source': 'int32',
        'rule': 'str'
    }
    EVENT_ATTRS = [
        'device',
        'username',
        'password',
        'filename',
        'userdata1',
        'userdata2',
        'userdata3',
        'userdata4',
        'userdata5',
        'userdata6',
        'userdata7',
        'userdata8',
        'userdata9',
        'ctx',
        # 'log',
        'domain',
        'mail',
        'organization',
        'service',
        'software',
        'hostname',
        'os',
        'cpu',
        'memory',
        'video',
        'state',
        'ip',
        'mac',
        'logon',
        'logoff',
        'reliability',
        'inventory_source',
        'rule'
    ]

    def __init__(self):
        super(HostInfoEvent, self).__init__()


class EventIdm(HostInfoEvent):
    def __init__(self):
        super(EventIdm, self).__init__()
