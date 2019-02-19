import unittest

import imp

import os

import MySQLdb
from mock import MagicMock, patch
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from db.models.alienvault import Base


class AlchemyTestCase(unittest.TestCase):

    def assert_parameters(self):
        self.db_params = {
            'TEST_DB_HOST': None,
            'TEST_DB_USER': None,
            'TEST_DB_PASSWD': None,
            'NOT_PRODUCTION_DB': None
        }
        for param_name in self.db_params.keys():
            self.db_params[param_name] = os.getenv(param_name)
            if self.db_params.get(param_name, None) is None:
                self.skipTest(
                    'Test environment parameter not set: {}. Never set it on production system!'.format(param_name)
                )

    def set_up_alchemy(self, repository_module):
        engine = create_engine('mysql://{}:{}@{}/alienvault'.format(
            self.db_params['TEST_DB_USER'],
            self.db_params['TEST_DB_PASSWD'],
            self.db_params['TEST_DB_HOST']
        ))
        Base.metadata.bind = engine
        DBSession = sessionmaker(bind=engine, autocommit=True)
        session = DBSession()

        require_db_deco_mock = MagicMock(side_effect=lambda x: x)
        patch('apimethods.decorators.require_db', require_db_deco_mock).start()
        imp.reload(repository_module)
        repository_module.db.session = session
        repository_module.api_log = MagicMock(side_effect=lambda x: x)

    def set_up_db_deco_only(self, repository_module):
        require_db_deco_mock = MagicMock(side_effect=lambda x: x)
        patch('apimethods.decorators.require_db', require_db_deco_mock).start()
        imp.reload(repository_module)

    def set_up_db(self):
        self.db = MySQLdb.connect(
            host=self.db_params.get('TEST_DB_HOST'),
            user=self.db_params.get('TEST_DB_USER'),
            passwd=self.db_params.get('TEST_DB_PASSWD'),
            db='',
            cursorclass=MySQLdb.cursors.DictCursor
        )
        self.cursor = self.db.cursor()
        self.cursor.execute('DROP DATABASE IF EXISTS alienvault')
        self.cursor.execute('CREATE DATABASE IF NOT EXISTS alienvault')
        self.cursor.execute('USE alienvault')



if __name__ == '__main__':
    unittest.main()
