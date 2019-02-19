from collections import namedtuple

Platform = namedtuple('Platform', [
    'name',
    'threat_intelligence_version',
    'appliance_type',
    'public_ip'
])
