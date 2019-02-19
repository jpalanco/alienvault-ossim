from apimethods.utils import get_bytes_from_uuid

CONFIG_SERVER_ID = 'server_id'
CONFIG_VERSION = 'ossim_server_version'


class ControlNodeRepository(object):

    def __init__(self, model_constructor, config_repo, system_repo, server_repo, contact_repo, platform_repo):
        self.__model_constructor = model_constructor
        self.__config_repository = config_repo
        self.__system_repository = system_repo
        self.__server_repository = server_repo
        self.__contact_person_repository = contact_repo
        self.__platform_repo = platform_repo

    def get_control_node(self):
        server_id = self.__config_repository.get_config(CONFIG_SERVER_ID).value
        software_version = self.__config_repository.get_config(CONFIG_VERSION).value
        system = self.__system_repository.get_system()
        server = self.__server_repository.get_server(get_bytes_from_uuid(server_id))
        contact_person = self.__contact_person_repository.get_contact_person()
        platform = self.__platform_repo.get_platform(system.admin_ip)

        # Public IP is configured on AMI systems only and should be used as a control node's IP for such systems.
        # For other systems, high availability configuration IP (if configured) has more priority than admin IP.
        control_node_ip = platform and platform.public_ip or system.ha_ip or system.admin_ip

        return self.__model_constructor(
            system.id,
            system.name,
            server.descr,
            platform and platform.name,
            platform and platform.appliance_type,
            software_version,
            platform and platform.threat_intelligence_version,
            contact_person and contact_person.email,
            contact_person and contact_person.name,
            control_node_ip,
            system.vpn_ip
        )
