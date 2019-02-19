import db
from apimethods.decorators import require_db
from bounded_contexts.central_console.domain_services.abstract_contact_person_repository import \
    AbstractContactPersonRepository
from db.models.alienvault import Users

USERS_IS_ADMIN_FILTER = 1
USERS_ENABLED_FILTER = 1


class AlchemyContactPersonRepository(AbstractContactPersonRepository):

    @require_db
    def get_contact_person(self):
        user_info = db.session.query(Users.email, Users.name)\
            .filter(
            Users.is_admin == USERS_IS_ADMIN_FILTER,
            Users.enabled == USERS_ENABLED_FILTER
        ).first()

        return user_info and self._contact_person_constructor(user_info[0], user_info[1])
