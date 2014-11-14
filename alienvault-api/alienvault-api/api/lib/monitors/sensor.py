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

from api.lib.monitors.monitor import Monitor, MonitorTypes, ComponentTypes
#from api.lib.messages import *
#from ansiblemethods.system.system import get_service_status
from ansiblemethods.sensor.network import get_pfring_stats

#from db.methods.data import get_snort_suricata_events_in_the_last_24_hours
from db.methods.system import get_systems
from apimethods.utils import get_uuid_string_from_bytes
import celery.utils.log
logger = celery.utils.log.get_logger("celery")


class MonitorSensorIDS(Monitor):
    """This class is to monitor whether a specified sensor has an IDS enabled"""

    def __init__(self):
        Monitor.__init__(self, MonitorTypes.MONITOR_SENSOR_IDS_ENABLED)
        self.message = 'Sensor Services Enabled'
    def start(self):
        """
        Starts the monitor activity

        monitor_data { 'suricata_enabled':True|False,
                       'snort_enabled': True|False,
                       'snort_running': True|False,
                       'suricata_running':True|False,
                    }
        :return: True on success, False otherwise

        """
        rt = True

        # TODO: This code is not being used currently. It should be refactorized
        # before use it
        # self.remove_monitor_data()
        # sensor_list = []
        # try:
        #     sensor_list = get_sensor_from_avcenter()
        # except Exception, e:
        #     logger.error("Error getting information from the database: %s" % str(e))
        # try:
        #     for sensor in sensor_list:
        #         monitor_data = {}
        #         suricata_enabled = True
        #         snort_enabled = True
        #
        #         sensor_ip = sensor.vpn_ip if sensor.vpn_ip else sensor.admin_ip
        #         sensor_id = sensor.uuid
        #         logger.info("%s Checking... %s" % (self.get_monitor_id(), sensor_ip))
        #         plugins = sensor.sensor_detectors.split(',')
        #         # Check 1 - plugins enabled
        #         if "suricata" not in plugins:
        #             suricata_enabled = False
        #         if not  "snort" not in plugins:
        #             snort_enabled = False
        #         #IDS running
        #         snort_running = get_service_status(sensor_ip, "snort")
        #         suricata_running = get_service_status(sensor_ip, "suricata")
        #
        #         #Events?
        #         data = get_snort_suricata_events_in_the_last_24_hours()
        #         count = len(data)
        #
        #         monitor_data['suricata_enabled'] = suricata_enabled
        #         monitor_data['snort_enabled'] = snort_enabled
        #         monitor_data['snort_running'] = snort_running
        #         monitor_data['suricata_running'] = suricata_running
        #         monitor_data['n_ids_events'] = count
        #
        #         if not self.save_data(sensor_id, ComponentTypes.SENSOR, self.get_json_message(monitor_data)):
        #             logger.error("Can't save monitor info")
        #
        # except Exception, e:
        #     rt = False
        #     logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
        #                                                                                    str(e)))
        return rt


class MonitorVulnerabilityScans(Monitor):
    """Class to monitor whether exists or not a vulnerability scan scheduled.
    """

    def __init__(self):
        """
        Constructor
        """
        Monitor.__init__(self, MonitorTypes.MONITOR_SENSOR_VULNERABILITY_SCANS)
        self.message = 'Monitor Sensor Scan Jobs'
    def start(self):
        """
        Starts the monitor activity

        :return: True on success, False otherwise
        """
        rt = True

        # TODO: This code is not being used currently. It should be refactorized
        # before use it
        # try:
        #     self.remove_monitor_data()
        #     logger.info("Monitor %s Working..." % self.monitor_id)
        #     sensor_list = Alienvault_Sensor.query.all()
        #     #Gets all the scan jobs scheduled.
        #     scan_jobs = Alienvault_Vuln_Job_Schedule.query.all()
        #     scan_jobs_sensor_ids = [scan.email.upper() for scan in scan_jobs]
        #     #NOTE: See ticket 8876.
        #     #Don't know why, but the sensor_id is stored in the email field.
        #     for sensor in sensor_list:
        #         monitor_data = {}
        #         sensor_ip = get_ip_str_from_bytes(sensor.ip)
        #         sensor_id = get_uuid_string_from_bytes(sensor.id)
        #         sensor_id = (sensor_id.replace('-', '')).upper()
        #         sensor_has_scan_job = True
        #         if sensor_id not in scan_jobs_sensor_ids:
        #             sensor_has_scan_job = False
        #         monitor_data['has_scans_scheduled'] = sensor_has_scan_job
        #         if not self.save_data(sensor_id, ComponentTypes.SENSOR, self.get_json_message(monitor_data)):
        #             logger.error("Can't save monitor info")
        #
        #
        # except Exception, e:
        #     db.session.rollback()
        #     logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
        #         str(e)))
        #     rt = False
        return rt


class MonitorNetflowEnabled(Monitor):
    def __init__(self):
        '''
        Constructor
        '''
        Monitor.__init__(self, MonitorTypes.SENSOR_NETFLOW_ENABLED)

    def start(self):
        """
        Start the monitor

        :return: True on success, False otherwise
        """
        #TODO
        pass


class MonitorAvailabilityMonitoringEnabled(Monitor):
    def __init__(self):
        '''
        Constructor
        '''
        Monitor.__init__(self, MonitorTypes.SENSOR_AVAILABILITY_MONITORING_ENABLED)

    def start(self):
        """
        Start the monitor

        :return: True on success, False otherwise
        """
        #TODO
        pass


class MonitorAvailabilityMonitoringEnabled(Monitor):
    def __init__(self):
        '''
        Constructor
        '''
        Monitor.__init__(self, MonitorTypes.SENSOR_AVAILABILITY_MONITORING_ENABLED)

    def start(self):
        pass


class MonitorOssecAgentsReports(Monitor):
    def __init__(self):
        Monitor.__init__(self, MonitorTypes.SENSOR_HAS_OSSEC_AGENTS_REPORTING)

    def start(self):
        """
        Start the monitor

        :return: True on success, False otherwise
        """
        #TODO
        pass


class MonitorSensorLocation(Monitor):
    """
    Monitors sensors without locations
    """

    def __init__(self):
        """
        Constructor
        """
        Monitor.__init__(self, MonitorTypes.MONITOR_SENSOR_LOCATION)
        self.message = 'Monitor Sensor without Location'

    def start(self):
        """
        Start the monitor

        :return: True on success, False otherwise
        """
        rt = True

        # TODO: This code is not being used currently. It should be refactorized
        # before use it
        # try:
        #     self.remove_monitor_data()
        #     logger.info("Monitor %s Working..." % self.monitor_id)
        #     sensor_list = get_sensor_from_avcenter()
        #     for sensor in sensor_list:
        #         monitor_data = {}
        #         sensor_ip = sensor.vpn_ip if sensor.vpn_ip else sensor.admin_ip
        #         sensor_id = sensor.uuid
        #         sensor_has_location = True
        #         # Get sensor from alienvault.sensor table
        #         sensors = get_sensor_from_alienvault(sensor_ip)
        #         if len(sensors) == 1:
        #             if sensors[0].location_sensor_reference.count() == 0:
        #                 sensor_has_location = False
        #             monitor_data['has_location'] = sensor_has_location
        #             if not self.save_data(get_uuid_string_from_bytes(sensors[0].uuid), ComponentTypes.SENSOR, self.get_json_message(monitor_data)):
        #                 logger.error("Can't save monitor info")
        # except Exception, e:
        #     db.session.rollback()
        #     logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
        #         str(e)))
        #     rt = False
        return rt


class MonitorSensorDroppedPackages(Monitor):
    """Class to monitor dropped packets on every sensor in the system"""
    def __init__ (self):
        Monitor.__init__(self, MonitorTypes.MONITOR_DROPPED_PACKAGES)
        self.message = 'Sensor Dropped Packets monitor started'

    def start(self):
        """
            Starts the monitor activity
        """
        rt = True
        try:
            self.remove_monitor_data()
            logger.info("Monitor %s Working..." % self.monitor_id)
            rc, sensor_list = get_systems(system_type="Sensor")
            if not rc:
                logger.error("Can't retrieve sensor list: %s" % str(sensor_list))
                return False
            for (sensor_id,sensor_ip) in sensor_list:
                if sensor_id == '':
                    logger.warning("Sensor (%s) ID not found" % sensor_ip)
                    continue
                logger.info("Getting dropped packets for sensor_ip %s" % sensor_ip)
                sensor_stats = get_pfring_stats(sensor_ip)
                # print sensor_stats
                try:
                    packet_lost_average = sensor_stats["contacted"][sensor_ip]["stats"]["packet_lost_average"]
                    monitor_data = {'packet_loss':packet_lost_average}
                    logger.info("Lost packet average for sensor: %s =  %s" %(sensor_ip,packet_lost_average))
                    #Save data component_id = canonical uuid
                    if not self.save_data(sensor_id, ComponentTypes.SENSOR, self.get_json_message(monitor_data)):
                        logger.error("Can't save monitor info")
                except KeyError:
                    pass

        except Exception, e:
            logger.error("Something wrong happen while running the monitor..%s, %s" % (self.get_monitor_id(),
                str(e)))
            rt = False

        return rt



