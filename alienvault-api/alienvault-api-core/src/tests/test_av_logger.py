"""
    Test the api.lib.av_config.clean_logger function
"""

from __future__ import print_function
from mock import patch
import unittest


from sqlalchemy.orm.exc import NoResultFound
import db
from db.models.alienvault import Config
from logger_maintenance import clean_logger


class TestAvLogger(unittest.TestCase):
    """
        I need a database connection to a MySQL test database to make this
        test to work
        # pylint: disable=R0904
    """

    # Private functions
    # Initdb

    def setUp(self):
        # Obtain the current key from config for logger_storage_days_lifetime
        # Backup the  logger_storage_days_lifetime
        try:
            qconfig = db.session.query(Config).filter(Config.conf == 'logger_storage_days_lifetime').one()
            self.logger_storage_days_lifetime = qconfig.serialize['value']
        except NoResultFound:
            print("Nothing to backup")

    def tearDown(self):
        if hasattr(self, 'logger_storage_days_lifetime'):
            config = Config()
            config.value = self.logger_storage_days_lifetime
            config.conf = 'logger_storage_days_lifetime'
            db.session.begin()
            db.session.merge(config)
            db.session.commit()

    def test_0001(self):
        """
            Verify clean logger disables
        """
        config = Config()
        config.conf = 'logger_storage_days_lifetime'
        config.value = 0
        db.session.begin()
        db.session.merge(config)
        db.session.commit()
        result = clean_logger()
        self.assertEqual(result, True)
    @patch('ansiblemethods.server.logger.delete_raw_logs')
    def test_0002(self, mock_delete_raw):
        """
            Verify correct exit after error getting conf
        """
        mock_delete_raw.return_value = True, "[]"
        config = Config()
        config.conf = 'logger_storage_days_lifetime'
        config.value = 10
        db.session.begin()
        db.session.merge(config)
        db.session.commit()
        result = clean_logger()
        self.assertEqual(result, True)
        self.assertTrue(mock_delete_raw.called)

    def test_0003(self):
        """
            Verify bad error stored in configuration database
        """
        config = Config()
        config.conf = 'logger_storage_days_lifetime'
        config.value = 'ascodevida'
        db.session.begin()
        db.session.merge(config)
        db.session.commit()
        result = clean_logger()
        self.assertEqual(result, False)

