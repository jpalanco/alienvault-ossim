import db
from apimethods.decorators import require_db
from bounded_contexts.central_console.domain_services.abstract_system_repository import AbstractSystemRepository
from db.methods.system import get_system_id_from_local
from apimethods.utils import get_ip_str_from_bytes, get_bytes_from_uuid
from db.models.alienvault import System

class AlchemySystemRepository(AbstractSystemRepository):

    @require_db
    def get_system(self):
        _, system_id = get_system_id_from_local()
        system_id_bin = get_bytes_from_uuid(system_id)
        system_info = db.session.query(
            System.name,
            System.admin_ip,
            System.vpn_ip,
            System.ha_ip
        ).filter(System.id == system_id_bin).one()

        system_name = system_info[0]
        system_admin_ip = get_ip_str_from_bytes(system_info[1])
        system_vpn_ip = get_ip_str_from_bytes(system_info[2])
        system_ha_ip = get_ip_str_from_bytes(system_info[3])

        return self._system_constructor(
            system_id,
            system_name,
            system_admin_ip,
            system_vpn_ip,
            system_ha_ip
        )
