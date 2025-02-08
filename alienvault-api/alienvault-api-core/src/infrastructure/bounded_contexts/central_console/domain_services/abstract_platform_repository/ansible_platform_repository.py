from ansiblemethods.system.about import get_platform_info
from bounded_contexts.central_console.domain_services.abstract_platform_repository import AbstractPlatformRepository


class AnsiblePlatformRepository(AbstractPlatformRepository):

    def __init__(self, platform_constructor, config_repository):
        super(AnsiblePlatformRepository, self).__init__(platform_constructor, config_repository)

    def get_platform(self, ip_str):
        success, info = get_platform_info(ip_str)

        if len(info) == 4:
            platform_name, intelligence_version, appliance_type, public_ip = info
        else:
            platform_name, intelligence_version, appliance_type, public_ip = None, None, None, None

        essential_entity_fields = (platform_name, intelligence_version, appliance_type)
        if not all(essential_entity_fields):
            # Entity makes no sense without any of 'essential' fields, so return None
            return None

        return self._platform_constructor(
            platform_name,
            intelligence_version,
            appliance_type,
            public_ip if public_ip != 'no ami' else None
        )