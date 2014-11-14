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
import os
import time
import commands
#
# LOCAL IMPORTS
#
from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
from DBConstantNames import *

logger = Logger.logger


class ApacheNtopProxyManager:
    def __init__(self, conf):
        self.__newConfigFileTemplateName = "/etc/apache2/conf.d/ntop-%s.conf"
        self.__sensorVar = "$(SENSOR_IP)"
        self.__myDB = OssimDB(conf[VAR_DB_HOST],
                              conf[VAR_DB_SCHEMA],
                              conf[VAR_DB_USER],
                              conf[VAR_DB_PASSWORD])
        self.__myDB_connected = False
        self.__myconf = conf
        self.__sensors = {} #ip = name
        self.__firstValidSensorConnected = False        

    def __reloadApache(self):
        logger.info("Reloading apache...")
        status, output = commands.getstatusoutput('/etc/init.d/apache2 reload')
        if status == 0:
            logger.info ("Reloading apache  .... OK")
        else:
            logger.error("Reloading apache  .... FAIL ..status code:%s" % status)

    def __getSensorList(self):
        if not self.__myDB_connected:
            if self.__myDB.connect():
                self.__myDB_connected = True
            else:
                logger.error("Can't connect to database")
                return
        #read sensor list.
        query = 'select inet6_ntop(sensor.ip) as ip, sensor.name  as name from sensor join sensor_properties where sensor.id = sensor_properties.sensor_id and sensor_properties.has_ntop=1;'
        tmp = None
        tmp = self.__myDB.exec_query(query)
        self.__sensors.clear()
        if tmp is not None:
            for sensor in tmp:
                self.__sensors[sensor['ip']] = sensor['name'] 
#        self.__close()

    def getNtopLink(self):
        query = "select value from config where conf like 'ntop_link';"
        ntop_link = ""
        if not self.__myDB_connected:
            if self.__myDB.connect():
                self.__myDB_connected = True
            else:
                logger.error("Can't connect to database")
                return
        tmp = self.__myDB.exec_query(query)        
        if tmp is not None:
            for row in tmp:
                ntop_link = row['value']
#        self.__close()
        return ntop_link

#    def __close(self):
#        self.__myDB.close()
#        self.__myDB_connected = False

    def __buildNtopConfigurationForSensor(self, sensor_ip):
        #Create a the new file:
        newfile_name = self.__newConfigFileTemplateName % sensor_ip
        logger.info("Creating ntop proxy configuration %s" % newfile_name)
        new_config_file = open(newfile_name, 'w')
        template = open(self.__myconf[VAR_NTOP_APACHE_PROXY_TEMPLATE])
        for line in template:
            new_config_file.write(line.replace(self.__sensorVar, sensor_ip))
        new_config_file.close()
        os.chmod(newfile_name,0644)
        template.close()


    def refreshConfiguration(self):
        #remove old configuration
        status, output = commands.getstatusoutput(' rm /etc/apache2/conf.d/ntop-*.conf')
        if not os.path.isfile(self.__myconf[VAR_NTOP_APACHE_PROXY_TEMPLATE]):
            logger.error("I can't create Ntop proxy configurations. Template file: %s not exist!" % self.__myconf[VAR_NTOP_APACHE_PROXY_TEMPLATE])
        else:
            self.__getSensorList()
            time.sleep(1)
            logger.info("Sensors are loaded")
            for sensorip, sensorname in self.__sensors.items():
                self.__buildNtopConfigurationForSensor(sensorip)            
        self.refreshDefaultNtopConfiguration()
        self.__reloadApache()

    def refreshDefaultNtopConfiguration(self, first_sensor_name=None, must_reload=False):
        if self.__firstValidSensorConnected and first_sensor_name is not None:
            return
        self.__getSensorList()
        ntop_link = self.getNtopLink()
        time.sleep(1)
        valid_ntop_link = False
        valid_sensor = False
        valid_sensor_ip = ""
        for sensor_ip, sensor_name in self.__sensors.items():
            if ntop_link == sensor_name:
                valid_ntop_link = True
                break
        
        if not valid_ntop_link:
            logger.warning("Invalid ntop_link --> %s <-- readed from config table. This value is not a valid active sensor." % ntop_link)
        if not first_sensor_name:
            logger.warning("There are not sensors available yet")
            first_sensor_name = ""
        else:
            for sensor_ip, sensor_name in self.__sensors.items():
                if sensor_name == first_sensor_name:                    
                    valid_sensor = True
                    valid_sensor_ip = sensor_ip
                    break
        if not valid_ntop_link and first_sensor_name is None:
            logger.warning("Invalid ntop_link value and there are not sensors available, it can't create default's ntop rewrite configuration")
            return
        #Default-sensor...
        # select ip from sensor_properties where ip like (select ip from sensor where name 
        # like (select value from config where conf like 'ntop_link')) and has_ntop = 1;
        #if not os.path.isfile(Const.NTOP_REWRITE_DEFUALT_CONFIG_FILE):
        #logger.warning("Default's ntop configuration file does not exist. It'll make a new one.")
        default_sensor_config_file = open(self.__myconf[VAR_NTOP_REWRITE_CONF_FILE], 'w')
        sensor_ip = ""
        line = ""
        if valid_ntop_link:
            logger.info("Using ntop_link value to write default's ntop rewrite configuration")
            line = "Redirect /ntop/ /ntop_%s/\n" % ntop_link
        elif valid_sensor and not self.__firstValidSensorConnected:
            self.__firstValidSensorConnected = True
            logger.info("Using first available sensor (%s = %s) value to write default's ntop rewrite configuration" % (first_sensor_name, valid_sensor_ip))
            line = "Redirect /ntop/ /ntop_%s/\n" % valid_sensor_ip
        else:
            must_reload = False
            if not valid_sensor:
                logger.info("'%s' is not valid active sensor" % first_sensor_name)  
            if not self.__firstValidSensorConnected:
                logger.info("Fist valid sensor not connect...")
                      
        default_sensor_config_file.write(line)
        default_sensor_config_file.close()
        os.chmod(self.__myconf[VAR_NTOP_REWRITE_CONF_FILE],0644)
        if must_reload:
            self.__reloadApache()
        


