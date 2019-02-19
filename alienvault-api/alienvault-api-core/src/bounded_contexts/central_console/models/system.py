from collections import namedtuple

System = namedtuple('System', [
    'id',
    'name',
    'admin_ip',
    'vpn_ip',
    'ha_ip'
])
