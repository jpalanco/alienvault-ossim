from traceback import format_exc

import api_log
import db
from apimethods.decorators import require_db
from apimethods.utils import get_uuid_string_from_bytes, get_ip_str_from_bytes
from bounded_contexts.central_console.domain_services.abstract_sensor_repository import AbstractSensorRepository
from db.methods.sensor import get_sensor_ip_from_sensor_id
from db.models.alienvault import Sensor, Sensor_Properties


class AlchemySensorRepository(AbstractSensorRepository):

    def __init__(self, sensor_constructor, platform_repository):
        super(AlchemySensorRepository, self).__init__(sensor_constructor, platform_repository)

    @require_db
    def get_sensors(self):
        alchemy_sensors = db.session.query(Sensor).all()
        result_sensors = [
            self.__build_sensor_from_alchemy_object(alchemy_sensor) for alchemy_sensor in alchemy_sensors
        ]

        return result_sensors

    def __build_sensor_from_alchemy_object(self, alchemy_sensor_object):
        sensor_id = get_uuid_string_from_bytes(alchemy_sensor_object.id)
        _, sensor_ip = get_sensor_ip_from_sensor_id(sensor_id)
        sensor_platform = self._platform_repository.get_platform(sensor_ip)
        sensor_connected = sensor_platform is not None
        return self._sensor_constructor(
            sensor_id,
            alchemy_sensor_object.name,
            alchemy_sensor_object.descr,
            sensor_platform and sensor_platform.name,
            sensor_ip,
            self.__get_software_version(alchemy_sensor_object.id),
            sensor_platform and sensor_platform.threat_intelligence_version,
            sensor_connected
        )

    @require_db
    def __get_software_version(self, sensor_id_bin):
        try:
            version = db.session.query(Sensor_Properties.version).filter(
                Sensor_Properties.sensor_id == sensor_id_bin
            ).one()[0]
        except Exception as exc:
            api_log.warning('Did not manage to get sensor software version: {}'.format(format_exc(exc)))
            return None

        return version
