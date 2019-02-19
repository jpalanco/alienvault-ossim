from collections import namedtuple

Sensor = namedtuple('Sensor', [
    'sensor_id',
    'name',
    'description',
    'platform',
    'ip_address',
    'software_version',
    'threat_intelligence_version',
    'is_connected'
])
