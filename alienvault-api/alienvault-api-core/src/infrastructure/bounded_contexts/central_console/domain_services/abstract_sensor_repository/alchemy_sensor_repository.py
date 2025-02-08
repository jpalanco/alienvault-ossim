import api_log
import db

from apimethods.decorators import require_db
from apimethods.utils import get_uuid_string_from_bytes, get_ip_str_from_bytes
from bounded_contexts.central_console.domain_services.abstract_sensor_repository import AbstractSensorRepository
from db.models.alienvault import Sensor, Sensor_Properties, System


class AlchemySensorRepository(AbstractSensorRepository):

    def __init__(self, sensor_constructor, platform_repository):
        super(AlchemySensorRepository, self).__init__(sensor_constructor, platform_repository)

    @require_db
    def get_sensors(self):
        alchemy_sensors = db.session.query(System, Sensor, Sensor_Properties) \
            .filter(System.sensor_id == Sensor.id) \
            .filter(Sensor_Properties.sensor_id == Sensor.id).all()

        result_sensors = []
        for alchemy_sensor in alchemy_sensors:
            sensor = self.__build_sensor_from_alchemy_object(alchemy_sensor)

            if sensor is not None:
                result_sensors.append(sensor)

        return result_sensors

    def __build_sensor_from_alchemy_object(self, alchemy_sensor_object):
        try:
            system = alchemy_sensor_object[0]
            sensor = alchemy_sensor_object[1]
            sensor_properties = alchemy_sensor_object[2]

            sensor_id = get_uuid_string_from_bytes(sensor.id)

            sensor_ip = system.vpn_ip if system.vpn_ip else system.admin_ip
            sensor_ip = get_ip_str_from_bytes(sensor_ip)

            sensor_platform = self._platform_repository.get_platform(sensor_ip)

            sensor_ti_version = ''
            if sensor_platform and sensor_platform.threat_intelligence_version:
                sensor_ti_version = sensor_platform.threat_intelligence_version

            sensor_connected = sensor_platform is not None

            return self._sensor_constructor(
                sensor_id,
                sensor.name,
                sensor.descr if sensor.descr is not None else '',
                sensor_platform.name if sensor_platform and sensor_platform.name else '',
                sensor_ip,
                sensor_properties.version if sensor_properties.version is not None else '',
                sensor_ti_version,
                sensor_connected
            )
        except Exception as e:
            message = "An error occurred while retrieving sensor information for USM Central: %s" %str(e)
            api_log.warning(message)
            return None
