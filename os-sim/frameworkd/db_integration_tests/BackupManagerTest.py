import random
from datetime import datetime, timedelta
import os

import MySQLdb
import mock
from _mysql import escape
from MySQLdb.converters import conversions

from BackupManager import BackupManager
from OssimDB import OssimDB
from db_integration_tests import basetestcase
from Logger import Logger
import fakedb


class FakeBackupManager(BackupManager):
    """ Class to test BackupManager functionality and skip the original __init__ (since it's too heavy to mock).
    """
    def __init__(self):
        self._BackupManager__myDB = mock.MagicMock(spec=OssimDB)


class TestBackupManager(basetestcase.BaseTestCase):

    def setUp(self):
        db_params = {
            'TEST_DB_HOST': None,
            'TEST_DB_USER': None,
            'TEST_DB_PASSWD': None
        }
        for param_name in db_params.keys():
            db_params[param_name] = os.getenv(param_name)
            if not db_params.get(param_name):
                self.skipTest('No database parameters set')

        self.db = MySQLdb.connect(
            host=db_params.get('TEST_DB_HOST'),
            user=db_params.get('TEST_DB_USER'),
            passwd=db_params.get('TEST_DB_PASSWD'),
            db='',
            cursorclass=MySQLdb.cursors.DictCursor
        )
        self.cursor = self.db.cursor()

        def exec_query(query, params=None):
            cursor = self.db.cursor()
            cursor.execute(query, params)
            result = cursor.fetchall()
            cursor.close()

            return result if result else []

        def format_query(query, params):
            if isinstance(params, dict):
                return query % dict((key, escape(param, conversions)) for key, param in params.iteritems())
            else:
                return query % tuple((escape(param, conversions) for param in params))

        def error(message):
            raise Exception(message)

        self.backup_manager = FakeBackupManager()
        self.mock_attr_strict(self.backup_manager._BackupManager__myDB, name='exec_query', side_effect=exec_query)
        self.mock_attr_strict(self.backup_manager._BackupManager__myDB, name='format_query', side_effect=format_query)
        self.mock_attr_strict(Logger.logger, name='error', side_effect=error)

        self.set_up_db()

    def tearDown(self):
        self.tear_down_db()

    def set_up_db(self):
        self.cursor.execute(fakedb.drop_database())
        self.cursor.execute(fakedb.create_database())
        self.cursor.execute(fakedb.use_database())

        self.cursor.execute(fakedb.create_fill_tables_proc())
        self.cursor.execute(fakedb.create_table_ac_acid_event())
        self.cursor.execute(fakedb.create_table_po_acid_event())
        self.cursor.execute(fakedb.create_delete_events_proc())
        self.cursor.execute(fakedb.create_table_acid_event())

        self.cursor.close()
        self.cursor = self.db.cursor()

    def tear_down_db(self):
        self.cursor.close()
        self.cursor = self.db.cursor()

        self.cursor.execute(fakedb.drop_fill_tables_proc())
        self.cursor.execute(fakedb.drop_table_ac_acid_event())
        self.cursor.execute(fakedb.drop_table_po_acid_event())
        self.cursor.execute(fakedb.drop_delete_events_proc())
        self.cursor.execute(fakedb.drop_table_acid_event())

    def test_delete_events_older_than_timestamp_null_timerange(self):
        start_date = limit_date = datetime.now()
        self.mock_attr_strict(
            self.backup_manager,
            name='_BackupManager__get_oldest_event_in_database_datetime',
            return_value=start_date
        )

        self.backup_manager._BackupManager__delete_events_older_than_timestamp(limit_date)

    def test_delete_events_older_than_timestamp_no_events(self):
        limit_date = datetime.now()
        start_date = limit_date - timedelta(days=1)
        self.mock_attr_strict(
            self.backup_manager,
            name='_BackupManager__get_oldest_event_in_database_datetime',
            return_value=start_date
        )

        self.backup_manager._BackupManager__delete_events_older_than_timestamp(limit_date)

    def test_delete_events_older_than_timestamp(self):
        limit_date = datetime.now()
        start_date = limit_date - timedelta(days=7)
        self.mock_attr_strict(
            self.backup_manager,
            name='_BackupManager__get_oldest_event_in_database_datetime',
            return_value=start_date
        )
        ctx_binary = bytearray(random.getrandbits(8) for _ in xrange(16))
        events_to_insert = 5
        for _ in xrange(events_to_insert):
            params = {
                'id': bytearray(random.getrandbits(8) for _ in xrange(16)),
                'device_id': 1,
                'ctx': ctx_binary,
                'timestamp': start_date + timedelta(hours=3),
                'tzone': 10.1
            }
            self.cursor.execute('INSERT INTO alienvault_siem.acid_event VALUES(%(id)s, %(device_id)s, %(ctx)s, '
                                '%(timestamp)s,' + 13 * ' NULL,' + ' %(tzone)s,' + 8 * ' NULL, ' + 'NULL)', params)

        self.backup_manager._BackupManager__delete_events_older_than_timestamp(limit_date)
