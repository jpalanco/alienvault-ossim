from sqlalchemy.orm.exc import NoResultFound

import db
from apimethods.decorators import require_db
from db.models.alienvault import Config
from shared_kernel.config.domain_services.abstract_config_repository import AbstractConfigRepository,\
    ConfigAlreadyExistsError, ConfigNotFoundError


class AlchemyConfigRepository(AbstractConfigRepository):

    @require_db
    def get_config(self, conf_name):
        try:
            alchemy_config_entity = db.session.query(Config).filter(Config.conf == conf_name).one()
        except NoResultFound:
            return None

        return self._construct_config(alchemy_config_entity.conf, alchemy_config_entity.value)

    @require_db
    def add_config(self, config_entity):
        if self.get_config(config_entity.conf) is not None:
            raise ConfigAlreadyExistsError

        token_config = Config(conf=config_entity.conf, value=config_entity.value)
        db.session.begin()

        try:
            db.session.add(token_config)
            db.session.commit()
        except Exception:
            db.session.rollback()
            raise

        return None

    @require_db
    def delete_config(self, config_entity):
        try:
            token = db.session.query(Config).filter(Config.conf == config_entity.conf).one()
        except NoResultFound:
            raise ConfigNotFoundError

        db.session.begin()

        try:
            db.session.delete(token)
            db.session.commit()
        except Exception:
            db.session.rollback()
            raise

        return None
