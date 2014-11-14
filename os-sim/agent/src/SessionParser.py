#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
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

#
# GLOBAL IMPORTS
#
import re
from sets import Set
import sgmllib
import socket



class SessionParser(sgmllib.SGMLParser):

    def parse(self, s):
        "Parse the given string 's'."
        self.feed(s)
        self.close()


    def __init__(self, verbose=0):

        sgmllib.SGMLParser.__init__(self, verbose)
        self.starting_description = 0
        self.inside_table_element = 0
        self.inside_table_row = 0
        self.in_session = 0
        self.td_count = 0
        self.seen  = 0

        self.sessions = []
        self.session_num = 0

        self.queried_ip = ""

        self.dst_port_group = {}
        self.src_port_group = {}

        self.source_ip = ""
        self.source_port = ""
        self.dest_ip = ""
        self.dest_port = ""
        self.data_sent = 0
        self.data_rcvd = 0
        self.active_since = ""
        self.last_seen = ""
        self.duration = 0
        self.inactive = 0


    def tosecs(self, data):

        # example: 27 sec
        if data.endswith('sec'):
            data = data.split(' ', 1)[0]

        # examples: 1 day  1:52:27
        #           2 days 4:56:23
        elif data.__contains__('day'):
            result = re.findall('(\d+) days? (\S+)', data)
            (days, time) = result[0]
            seconds = int(days) * 86400

            dt = time.split(':')
            seconds += int(dt.pop())

            try:
                seconds += seconds + int(dt.pop()) * 60
                seconds += seconds + int(dt.pop()) * 3600

            except IndexError:
                pass
            data = str(seconds)

        # example: 1:52:27
        else:
            dt = data.split(':')
            seconds = int(dt.pop())

            try:
                seconds += int(dt.pop()) * 60
                seconds += int(dt.pop()) * 3600

            except IndexError:
                pass
            data = str(seconds)

        return data


    def start_td(self, attributes):
        self.td_count += 1
        self.inside_table_element = 1


    def end_td(self):
        self.inside_table_element = 0
        self.seen = 0


    def start_tr(self, attributes):
        self.inside_table_row = 1


    def end_tr(self):
        self.inside_table_row = 0
        self.td_count = 0

        if self.in_session and self.source_ip:
            self.session_num += 1
            tmp = "%s:%s --> %s:%s (%f %f) duration: %s" % (self.source_ip,self.source_port,self.dest_ip,self.dest_port, self.data_sent, self.data_rcvd, self.duration)
            self.sessions.append(tmp)


    def start_a(self, attributes):
        self.inside_table_row = 1
        for name, value in attributes:
            if name == "href" and self.in_session:
                matches = re.findall("\d+\.\d+\.\d+\.\d+",value)

                if len(matches) > 0:
                    if self.td_count is 1:
                        self.source_ip = matches[0]

                    elif self.td_count is 2:
                        self.dest_ip = matches[0]


    def handle_data(self, data):
        if self.queried_ip is "":
            matches = re.findall("(\d+\.\d+\.\d+\.\d+)",data)

            if len(matches) > 0:
                self.queried_ip = matches[0]

        if data.__contains__("Active TCP/UDP Sessions"):
            self.in_session = 1

        if data.__contains__("The color of the host"):
            self.sessions.append("NumSessions:%d" % int(self.session_num))
            self.in_session = 0
            source_sessions = 0
            dest_sessions = 0

            for sess in self.sessions:
                src_str = "^%s:.*" % self.queried_ip
                dst_str = "^\d+\.\d+\.\d+\.\d+:\S+\s+-->\s+%s:.*" % self.queried_ip
                src_sess = "^%s:\S+\s+-->\s+(\d+\.\d+\.\d+\.\d+):(\S+)" % self.queried_ip
                dst_sess = "^(\d+\.\d+\.\d+\.\d+):\S+\s+-->\s+%s:(\S+)" % self.queried_ip

                if re.findall(src_str, sess):
                    source_sessions += 1

                if re.findall(dst_str, sess):
                    dest_sessions += 1
                matches = re.findall(src_sess, sess)

                if len(matches) > 0:
                    if matches[0][1] in self.src_port_group:
                        self.src_port_group[matches[0][1]].add(matches[0][0])

                    else:
                        self.src_port_group[matches[0][1]] = Set()
                        self.src_port_group[matches[0][1]].add(matches[0][0])
                matches = re.findall(dst_sess, sess)

                if len(matches) > 0:
                    if matches[0][1] in self.dst_port_group:
                        self.dst_port_group[matches[0][1]].add(matches[0][0])

                    else:
                        self.dst_port_group[matches[0][1]] = Set()
                        self.dst_port_group[matches[0][1]].add(matches[0][0])

            self.sessions.append("SessionsAsSource:%s" % source_sessions)
            self.sessions.append("SessionsAsDest:%s" % dest_sessions)

            for port in self.src_port_group:
                self.sessions.append("UniqPort%sAsSourceSessions:%d" % (port, len(self.src_port_group[port])))

            for port in self.dst_port_group:
                self.sessions.append("UniqPort%sAsDestSessions:%d" % (port, len(self.dst_port_group[port])))

        if self.inside_table_element and self.in_session:
            if self.td_count <= 2:
                matches = re.findall(":(\w+)",data)

                if len(matches) > 0:
                    try:
                        port = socket.getservbyname(matches[0])

                    except:
                        port = matches[0]

                    if self.td_count is 1:
                        self.source_port = port

                    elif self.td_count is 2:
                        self.dest_port = port

            elif self.td_count is 3:
                if self.seen == 0:
                    self.data_sent = float(data)
                    self.seen = 1

                else:
                    if data.__contains__("KB"):
                        self.data_sent *= 1024

                    elif data.__contains__("MB"):
                        self.data_sent *= 1024 * 1024

                    elif data.__contains__("GB"):
                        self.data_sent *= 1024 * 1024 * 1024

                    self.seen = 0

            elif self.td_count is 4:
                if self.seen == 0:
                    self.data_rcvd = float(data)
                    self.seen = 1

                else:
                    if data.__contains__("KB"):
                        self.data_rcvd *= 1024

                    elif data.__contains__("MB"):
                        self.data_rcvd *= 1024 * 1024

                    elif data.__contains__("GB"):
                        self.data_rcvd *= 1024 * 1024 * 1024

                    self.seen = 0

            elif self.td_count is 5:
                self.active_since = data

            elif self.td_count is 6:
                self.last_seen = data

            elif self.td_count is 7:
                self.duration = self.tosecs(data)

            elif self.td_count is 8:
                self.inactive = self.tosecs(data)


    def get_sessions(self):
        return self.sessions

