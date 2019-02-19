from collections import namedtuple

ControlNode = namedtuple('ControlNode', [
    'node_id',
    'name',
    'description',
    'platform',
    'appliance_type',
    'software_version',
    'intelligence_version',
    'contact_email',
    'contact_name',
    'admin_ip_address',
    'vpn_ip_address'
])
