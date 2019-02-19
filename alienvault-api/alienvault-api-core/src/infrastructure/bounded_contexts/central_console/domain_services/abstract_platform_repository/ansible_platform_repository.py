from ansiblemethods.system.about import get_alienvault_platform, get_threat_intelligence_version, \
    get_alienvault_appliance_type, get_local_ami_public_ip
from bounded_contexts.central_console.domain_services.abstract_platform_repository import AbstractPlatformRepository


class AnsiblePlatformRepository(AbstractPlatformRepository):

    def __init__(self, platform_constructor, config_repository):
        super(AnsiblePlatformRepository, self).__init__(platform_constructor, config_repository)

    def get_platform(self, ip_str):
        platform_success, platform_name = get_alienvault_platform(ip_str)
        version_success, intelligence_version = get_threat_intelligence_version(ip_str)
        type_success, appliance_type = get_alienvault_appliance_type(ip_str)
        public_ip_success, public_ip = get_local_ami_public_ip(ip_str)

        essential_entity_fields = (platform_success, version_success, type_success)
        if not all(essential_entity_fields):
            # Entity makes no sense without any of 'essential' fields, so return None
            return None

        return self._platform_constructor(
            platform_name,
            intelligence_version,
            appliance_type,
            public_ip if public_ip_success else None
        )
