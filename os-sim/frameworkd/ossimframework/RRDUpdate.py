#!/usr/bin/python
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
import threading
import rrdtool
import time
import os
import re

#
# LOCAL IMPORTS
#
import Const, Util
from OssimDB import OssimDB
from OssimConf import OssimConf

class RRDUpdate(threading.Thread):

    def __init__(self):
        self.__conf = OssimConf(Const.CONFIG_FILE)
        self.__db = OssimDB()
        threading.Thread.__init__(self)


    # get hosts c&a
    def __get_hosts(self):

        query = "SELECT * FROM host_qualification"
        return self.__db.exec_query(query)


    # get nets c&a
    def __get_nets(self):

        query = "SELECT * FROM net_qualification"
        return self.__db.exec_query(query)

    # get groups c&a
    def __get_groups(self):
    
        query = "SELECT net_group_reference.net_id AS net_id,\
          SUM(net_qualification.compromise) AS compromise,\
          SUM(net_qualification.attack) AS attack\
          FROM net_group_reference, net_qualification WHERE\
          net_group_reference.net_id = net_qualification.net_id GROUP BY\
          net_id"
        return self.__db.exec_query(query)


    # get ossim users
    def __get_users(self):
        
        query = "SELECT * FROM users"
        return self.__db.exec_query(query)


    # get users of incidents
    def __get_incident_users(self):
        
        query = "SELECT in_charge FROM incident_ticket GROUP BY in_charge;"
        return self.__db.exec_query(query)


    # get global c&a as sum of hosts c&a
    def get_global_qualification(self, allowed_nets):
        
        MIN_GLOBAL_VALUE = 0.0001 # set to 0.0001 by alexlopa (sure?)

        compromise = attack = MIN_GLOBAL_VALUE

        for host in self.__get_hosts():
            if Util.isIpInNet(host["host_ip"], allowed_nets) or \
               not allowed_nets:
                compromise += int(host["compromise"])
                attack += int(host["attack"])

        if compromise < MIN_GLOBAL_VALUE: compromise = MIN_GLOBAL_VALUE
        if attack < MIN_GLOBAL_VALUE: attack = MIN_GLOBAL_VALUE

        return (compromise, attack)


    # get level (0 or 100) using c&a and threshold
    def get_level_qualification(self, user, c, a):

        compromise = attack = 1

        if float(c) > float(self.__conf["threshold"]):
            compromise = 0
        if float(a) > float(self.__conf["threshold"]):
            attack = 0

        return (100*compromise, 100*attack)


    def get_incidents (self, user):
       
        status = {}
        query = "SELECT count(*) as count, status FROM incident_ticket WHERE in_charge = \"%s\" GROUP BY status" % user
        hash = self.__db.exec_query(query)
        for row in hash: # Should be only one anyway
            status[row["status"]] = row["count"] 
        return status


    # update rrd files with new C&A values
    def update(self, rrdfile, compromise, attack):

        timestamp = int(time.time())

        try:
            open(rrdfile)
        except IOError:
            print __name__, ": Creating %s.." % (rrdfile)
# This needs some checking, we don't need HWPREDICT here and I got some probs
# on MacosX (def update_simple) so I removed aberrant behaviour detection.
            rrdtool.create(rrdfile,
                           '-b', str(timestamp), '-s300',
                           'DS:ds0:GAUGE:600:0:1000000',
                           'DS:ds1:GAUGE:600:0:1000000',
                           'RRA:AVERAGE:0.5:1:800',
                           'RRA:HWPREDICT:1440:0.1:0.0035:288',
                           'RRA:AVERAGE:0.5:6:800',
                           'RRA:AVERAGE:0.5:24:800',
                           'RRA:AVERAGE:0.5:288:800',
                           'RRA:MAX:0.5:1:800',
                           'RRA:MAX:0.5:6:800',
                           'RRA:MAX:0.5:24:800',
                           'RRA:MAX:0.5:288:800')
        else:
            print __name__, ": Updating %s with values (C=%s, A=%s).." \
                % (rrdfile, compromise, attack)

            # RRDs::update("$dotrrd", "$time:$inlast:$outlast");
	    # It may fail here, I don't know if it only happens on MacosX but this
        # does solve it. (DK 2006/02)
        try:
            rrdtool.update(rrdfile, str(timestamp) + ":" + \
                            str(compromise) + ":" +\
                            str(attack))
        except Exception, e:
            print "Error updating %s: %s" % (rrdfile, e) 

    # update incident rrd files with a new incident count
    def update_simple(self, rrdfile, count):

        timestamp = int(time.time())

        try:
            open(rrdfile)
        except IOError:
            print __name__, ": Creating %s.." % (rrdfile)
            rrdtool.create(rrdfile,
                           '-b', str(timestamp), '-s300',
                           'DS:ds0:GAUGE:600:0:1000000',
                           'RRA:AVERAGE:0.5:1:800',
                           'RRA:AVERAGE:0.5:6:800',
                           'RRA:AVERAGE:0.5:24:800',
                           'RRA:AVERAGE:0.5:288:800',
                           'RRA:MAX:0.5:1:800',
                           'RRA:MAX:0.5:6:800',
                           'RRA:MAX:0.5:24:800',
                           'RRA:MAX:0.5:288:800')
        else:
            print __name__, ": Updating %s with value (Count=%s).." \
                % (rrdfile, count)
            try:
                rrdtool.update(rrdfile, str(timestamp) + ":" + \
                            str(count))
            except Exception, e:
                print "Error updating %s: %s" % (rrdfile, e) 



    def run(self):

        self.__db.connect(self.__conf['ossim_host'],
                          self.__conf['ossim_base'],
                          self.__conf['ossim_user'],
                          self.__conf['ossim_pass'])

        while 1:
            
            ### incidents
            try:
                rrdpath = self.__conf["rrdpath_incidents"]
                if not os.path.isdir(rrdpath):
                    os.makedirs(rrdpath, 0755)
                for user in self.__get_incident_users():
                    incidents = self.get_incidents(user["in_charge"])
                    for type in incidents:
                        filename = os.path.join(rrdpath, "incidents_" + user["in_charge"] + "_" +  type + ".rrd")
                        self.update_simple(filename, incidents[type])
            except OSError, e:
                print __name__, e

            ### hosts
            try:
                rrdpath = self.__conf["rrdpath_host"]
                if not os.path.isdir(rrdpath):
                    os.makedirs(rrdpath, 0755)
                for host in self.__get_hosts():
                    filename = os.path.join(rrdpath, host["host_ip"] + ".rrd")
                    self.update(filename, host["compromise"], host["attack"])
            except OSError, e:
                print __name__, e
            
            
            ### nets
            try:
                rrdpath = self.__conf["rrdpath_net"]
                if not os.path.isdir(rrdpath):
                    os.makedirs(rrdpath, 0755)
                for net in self.__get_nets():
                    filename = os.path.join(rrdpath, net["net_name"] + ".rrd")
                    self.update(filename, net["compromise"], net["attack"])
            except OSError, e:
                print __name__, e

            ### groups
            try:
                rrdpath = self.__conf["rrdpath_net"]
                if not os.path.isdir(rrdpath):
                    os.makedirs(rrdpath, 0755)
                for group in self.__get_groups():
                    filename = os.path.join(rrdpath, "group_" + group["group_name"] + ".rrd")
                    self.update(filename, group["compromise"], group["attack"])
            except OSError, e:
                print __name__, e



            ### global & level
            try:
                rrdpath = self.__conf["rrdpath_global"]
                if not os.path.isdir(rrdpath):
                    os.makedirs(rrdpath, 0755)
                rrdpath_level = self.__conf["rrdpath_level"]
                if not os.path.isdir(rrdpath_level):
                    os.makedirs(rrdpath_level, 0755)
                for user in self.__get_users():

                    # ** FIXME **
                    # allow all nets if user is admin
                    # it's ugly, I know..
                    if user['login'] == 'admin':
                        user['allowed_nets'] = ''

                    ### global
                    filename = os.path.join(rrdpath,
                                            "global_" + user["login"] + ".rrd")
                    (compromise, attack) = \
                        self.get_global_qualification(user["allowed_nets"])
                    self.update(filename, compromise, attack)

                    ### level
                    filename_level = os.path.join(rrdpath_level,
                                            "level_" + user["login"] + ".rrd")
                    (c_percent, a_percent) = \
                        self.get_level_qualification(user["login"], compromise, attack)
                    self.update(filename_level, c_percent, a_percent)
            except OSError, e:
                print __name__, e
                    
            time.sleep(float(Const.SLEEP))

        # never reached..
        self.__db.close()


if __name__ == "__main__":

    rrd = RRDUpdate()
    rrd.start()

# vim:ts=4 sts=4 tw=79 expandtab:
