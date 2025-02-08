import requests

from bounded_contexts.central_console.application_services.abstract_console_proxy import AbstractConsoleProxy

USMCENTRAL_HTTP_PROTOCOL = 'https://'
USMCENTRAL_HTTP_AUTH_HEADER = 'authorization'
USMCENTRAL_HTTP_OK = 200
USMCENTRAL_HTTP_CONNECT_PATH = '/mssp/appliance/deployments'
USMCENTRAL_HTTP_TOKEN_STATUS_PATH = '/mssp/appliance/deployments/status'
USMCENTRAL_HTTP_DISCONNECT_PATH = '/mssp/appliance/disconnect'
USMCENTRAL_ATTR_NOT_AVAILABLE = ''
USMCENTRAL_ATTR_NOT_AVAILABLE_INT = 0
USMCENTRAL_HTTP_TIMEOUT_SEC = 60

# The following values are predefined, please see USMCentral requirements, a.k.a. RAML
SENSOR_STATUS_CONNECTED = 'Connected'
SENSOR_STATUS_CONNECTION_LOST = 'Connection lost'
SENSOR_FALLBACK_PLATFORM = 'unknown'  # this very value is predefined, please see USMCentral requirements

# These values are not predefined, use something human readable here
SENSOR_FALLBACK_SOFTWARE_VERSION = 'Unknown'
SENSOR_FALLBACK_INTELLIGENCE_VERSION = 'Unknown'

PLATFORMS_MAP = {
    'alienvault-vmware': 'VMWare',
    'alienvault-hw': 'hardware',
    'alienvault-hyperv': 'hyperV',
    'alienvault-ami': "aws"
}
CONTROL_NODE_FALLBACK_PLATFORM = "hardware"  # predefined
CONTROL_NODE_FALLBACK_APPLIANCE_TYPE = "Unknown"  # any, but human readable
CONTROL_NODE_FALLBACK_INTELLIGENCE_VERSION = "Unknown"  # any, but human readable


def usmcentral_http_header(token):
    return {
        'Content-type': 'application/json',
        'Authorization': 'Bearer ' + str(token.raw_data),
        'USMC-API-Version': '1.0'
    }


def usmcentral_deployment_payload(deployment_info):

    if not hasattr(deployment_info, 'control_node'):
        return {}

    control_node = {
        'id': deployment_info.control_node.node_id,
        'name': deployment_info.control_node.name,
        'description': deployment_info.control_node.description,
        'fqdn': USMCENTRAL_ATTR_NOT_AVAILABLE,
        'platform': adapt_platform(deployment_info.control_node.platform) or CONTROL_NODE_FALLBACK_PLATFORM,
        'applianceType': deployment_info.control_node.appliance_type or CONTROL_NODE_FALLBACK_APPLIANCE_TYPE,
        'softwareVersion': deployment_info.control_node.software_version,
        'threatIntelligenceVersion':
            deployment_info.control_node.intelligence_version or CONTROL_NODE_FALLBACK_INTELLIGENCE_VERSION,
        'adminIpAddress': deployment_info.control_node.admin_ip_address,
        'vpnIpAddress': deployment_info.control_node.vpn_ip_address
    }

    # Add/skip control node optionals
    if deployment_info.control_node.contact_name is not None:
        control_node['contactName'] = deployment_info.control_node.contact_name
    if deployment_info.control_node.contact_email is not None:
        control_node['contactEmail'] = deployment_info.control_node.contact_email

    license = {
        'isTrial': deployment_info.license.is_trial
    }

    # Add/skip license optionals
    if deployment_info.license.expires_on is not None:
        license['expiresOn'] = deployment_info.license.expires_on
    if deployment_info.license.devices is not None:
        license['devices'] = deployment_info.license.devices

    return {
        'controlNode': control_node,
        'license': license,
        'sensors': [{
            'id': sensor.sensor_id,
            'name': sensor.name,
            'description': sensor.description,
            'platform': adapt_platform(sensor.platform) or SENSOR_FALLBACK_PLATFORM,
            'ipAddress': sensor.ip_address,
            'softwareVersion': sensor.software_version or SENSOR_FALLBACK_SOFTWARE_VERSION,
            'threatIntelligenceVersion': sensor.threat_intelligence_version or SENSOR_FALLBACK_INTELLIGENCE_VERSION,
            'connectionStatus': SENSOR_STATUS_CONNECTED if sensor.is_connected else SENSOR_STATUS_CONNECTION_LOST
        } for sensor in deployment_info.sensors]
    }


def adapt_platform(source_platform):
    return PLATFORMS_MAP.get(source_platform, None)


class USMCentralHttpProxy(AbstractConsoleProxy):
    def send_deployment_info(self, token, deployment_info):
        url_connect = '{}{}{}'.format(
            USMCENTRAL_HTTP_PROTOCOL,
            token.issuer,
            USMCENTRAL_HTTP_CONNECT_PATH
        )
        headers = usmcentral_http_header(token)
        payload = usmcentral_deployment_payload(deployment_info)

        response = requests.post(url=url_connect, json=payload, headers=headers, timeout=USMCENTRAL_HTTP_TIMEOUT_SEC)

        # response options:
        #   200 => everything is ok
        #   401 => token is not valid
        #   400 => token is valid but payload is not valid
        return True if response.status_code == USMCENTRAL_HTTP_OK else False

    def get_token_status(self, token):
        url_connect = '{}{}{}'.format(
            USMCENTRAL_HTTP_PROTOCOL,
            token.issuer,
            USMCENTRAL_HTTP_TOKEN_STATUS_PATH
        )
        headers = usmcentral_http_header(token)

        if 'Content-type' in headers:
            del headers['Content-type']

        response = requests.get(url=url_connect, headers=headers, timeout=USMCENTRAL_HTTP_TIMEOUT_SEC)

        return True if response.status_code == USMCENTRAL_HTTP_OK else False

    def send_disconnect_notification(self, token):
        url_disconnect = '{}{}{}'.format(
            USMCENTRAL_HTTP_PROTOCOL,
            token.issuer,
            USMCENTRAL_HTTP_DISCONNECT_PATH
        )
        headers = usmcentral_http_header(token)
        response = requests.post(url=url_disconnect, headers=headers, timeout=USMCENTRAL_HTTP_TIMEOUT_SEC)
        response.raise_for_status()
