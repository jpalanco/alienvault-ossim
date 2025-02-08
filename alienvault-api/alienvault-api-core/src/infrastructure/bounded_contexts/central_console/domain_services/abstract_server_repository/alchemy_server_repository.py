import db
from apimethods.decorators import require_db
from bounded_contexts.central_console.domain_services.abstract_server_repository import AbstractServerRepository
from apimethods.utils import get_ip_str_from_bytes
from db.models.alienvault import Server

class AlchemyServerRepository(AbstractServerRepository):

    @require_db
    def get_server(self, server_id):
        server_record = db.session.query(Server.descr, Server.ip).filter(Server.id == server_id).one()

        description = server_record[0]
        ip_str = get_ip_str_from_bytes(server_record[1])

        return self._server_constructor(description, ip_str)
