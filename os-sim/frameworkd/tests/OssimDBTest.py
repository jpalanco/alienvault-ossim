import unittest
import mock
from threading import Lock
from MySQLdb.connections import Connection, DatabaseError, OperationalError
from MySQLdb.cursors import Cursor
from OssimDB import OssimDB


class OssimDBTest(unittest.TestCase):

    def setUp(self):
        self.__db = "test"
        self.__db_user = "user"
        self.__db_pass = "pass"
        self.__db_host = "localhost"
        self.__db_query = "select"
        self.__db_query_result = ["test_row1", "test_row2", "test_row3"]
        self.__db_query_empty_result = []
        self.__db_autocommit = True
        self.__db_autocommit_default = False
        self.__db_error_code = 2006
        self.__db_param_query = "select where a = %s and b = %s"
        self.__db_params = (1, 2)
        self.__ossim_db = OssimDB(self.__db_host, self.__db, self.__db_user, self.__db_pass)

    def prepare_multiple_failing_queries(self, number):
        mock_connections = [mock.create_autospec(Connection) for _ in xrange(number)]
        mock_cursors = [mock.create_autospec(Cursor) for _ in xrange(number)]
        for idx in xrange(number):
            mock_cursors[idx].fetchall.return_value = self.__db_query_empty_result
            mock_cursors[idx].execute.side_effect = OperationalError(self.__db_error_code, "error")
            mock_connections[idx].cursor.return_value = mock_cursors[idx]
        return mock_connections, mock_cursors

    def assert_multiple_failing_queries(self, mock_connections, mock_cursors, failing_attempts):
        for idx in xrange(failing_attempts):
            self.assertTrue(mock_connections[idx].cursor.called)
            self.assertTrue(mock_cursors[idx].execute.called)
            self.assertFalse(mock_cursors[idx].fetchall.called)
            self.assertTrue(mock_connections[idx].close.called)

    @mock.patch('OssimDB.Lock', autospec=True)
    def test_create(self, mock_lock):
        self.setUp()
        self.assertTrue(mock_lock.called)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_connect(self, mock_mysqldb):
        self.setUp()
        mock_connection = mock.create_autospec(Connection)
        mock_mysqldb.connect.return_value = mock_connection

        connect_rv = self.__ossim_db.connect()

        mock_mysqldb.connect.assert_called_with(
            cursorclass=mock_mysqldb.cursors.DictCursor,
            db=self.__db,
            host=self.__db_host,
            passwd=self.__db_pass,
            user=self.__db_user
        )
        mock_connection.autocommit.assert_called_with(True)
        assert connect_rv is True

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_connect_connected(self, mock_mysqldb):
        self.setUp()
        self.__ossim_db.connect()

        connect_rv = self.__ossim_db.connect()

        assert connect_rv is None

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_reconnect_fail(self, mock_mysqldb):
        self.setUp()
        mock_mysqldb.connect.side_effect = DatabaseError()

        connect_rv = self.__ossim_db.connect()

        self.assertFalse(connect_rv)
        self.assertTrue(mock_mysqldb.connect.call_count, 5)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_reconnect_success(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        self.setUp()
        mock_mysqldb.connect.side_effect = [DatabaseError(), mock_connection]

        connect_rv = self.__ossim_db.connect()

        self.assertTrue(connect_rv)
        self.assertTrue(mock_mysqldb.connect.call_count, 2)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_query_autoconnect(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        mock_mysqldb.connect.return_value = mock_connection
        self.setUp()

        self.__ossim_db.exec_query(self.__db_query)

        mock_mysqldb.connect.assert_called_with(
            cursorclass=mock_mysqldb.cursors.DictCursor,
            db=self.__db,
            host=self.__db_host,
            passwd=self.__db_pass,
            user=self.__db_user
        )
        mock_connection.autocommit.assert_called_with(True)

    @mock.patch('OssimDB.time', autospec=True)
    @mock.patch('OssimDB.Lock', autospec=True)
    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_query(self, mock_mysqldb, mock_lock, mock_time):
        mock_connection = mock.create_autospec(Connection)
        mock_cursor = mock.create_autospec(Cursor)
        mock_lock_obj = mock.create_autospec(Lock())
        mock_lock.return_value = mock_lock_obj
        mock_mysqldb.connect.return_value = mock_connection
        mock_connection.cursor.return_value = mock_cursor
        mock_cursor.fetchall.return_value = self.__db_query_result
        self.setUp()
        self.__ossim_db.connect()

        exec_query_rv = self.__ossim_db.exec_query(self.__db_query)

        self.assertTrue(mock_lock_obj.acquire.called)
        mock_cursor.execute.assert_called_with(self.__db_query, None)
        self.assertTrue(mock_cursor.fetchall.called)
        self.assertTrue(set(exec_query_rv) == set(self.__db_query_result))
        self.assertTrue(mock_cursor.close.called)
        self.assertTrue(mock_lock_obj.release.called)
        self.assertFalse(mock_time.sleep.called)
        self.assertFalse(mock_connection.close.called)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_query_parameterized(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        mock_cursor = mock.create_autospec(Cursor)
        mock_mysqldb.connect.return_value = mock_connection
        mock_connection.cursor.return_value = mock_cursor
        mock_cursor.fetchall.return_value = self.__db_query_result
        self.setUp()
        self.__ossim_db.connect()

        exec_query_rv = self.__ossim_db.exec_query(self.__db_param_query, self.__db_params)
        mock_cursor.execute.assert_called_with(self.__db_param_query, self.__db_params)
        self.assertTrue(set(exec_query_rv) == set(self.__db_query_result))


    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_query_empty_result(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        mock_cursor = mock.create_autospec(Cursor)
        mock_cursor.fetchall.return_value = self.__db_query_empty_result
        mock_connection.cursor.return_value = mock_cursor
        mock_mysqldb.connect.return_value = mock_connection
        self.setUp()
        self.__ossim_db.connect()

        exec_query_rv = self.__ossim_db.exec_query(self.__db_query)
        self.assertTrue(set(exec_query_rv) == set(self.__db_query_empty_result))

    @mock.patch('OssimDB.time', autospec=True)
    @mock.patch('OssimDB.Lock', autospec=True)
    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_query_retry_success(self, mock_mysqldb, mock_lock, mock_time):
        failing_attempts = 2
        total_attempts = failing_attempts + 1
        mock_lock_obj = mock.create_autospec(Lock())
        mock_connections, mock_cursors = self.prepare_multiple_failing_queries(failing_attempts)
        # Last try should not throw an exception but return expected data
        mock_cursors.append(mock.create_autospec(Cursor))
        mock_connections.append(mock.create_autospec(Connection))
        mock_cursors[total_attempts - 1].fetchall.return_value = self.__db_query_result
        mock_connections[total_attempts - 1].cursor.return_value = mock_cursors[total_attempts - 1]
        mock_lock.return_value = mock_lock_obj
        mock_mysqldb.connect.side_effect = mock_connections
        self.setUp()
        self.__ossim_db.connect()

        exec_query_rv = self.__ossim_db.exec_query(self.__db_query)

        self.assertTrue(mock_lock_obj.acquire.called)
        self.assert_multiple_failing_queries(mock_connections, mock_cursors, failing_attempts)
        self.assertEqual(mock_time.sleep.call_count, failing_attempts)
        self.assertTrue(mock_lock_obj.release.called)
        self.assertTrue(set(exec_query_rv) == set(self.__db_query_result))

    @mock.patch('OssimDB.time', autospec=True)
    @mock.patch('OssimDB.Lock', autospec=True)
    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_query_retry_fail(self, mock_mysqldb, mock_lock, mock_time):
        failing_attempts = 4
        mock_lock_obj = mock.create_autospec(Lock())
        mock_connections, mock_cursors = self.prepare_multiple_failing_queries(failing_attempts)
        mock_lock.return_value = mock_lock_obj
        mock_mysqldb.connect.side_effect = mock_connections
        self.setUp()
        self.__ossim_db.connect()

        exec_query_rv = self.__ossim_db.exec_query(self.__db_query)

        self.assertTrue(mock_lock_obj.acquire.called)
        self.assert_multiple_failing_queries(mock_connections, mock_cursors, failing_attempts)
        self.assertEqual(mock_time.sleep.call_count, failing_attempts - 1)  # last attempt without sleep()
        self.assertTrue(mock_lock_obj.release.called)
        self.assertTrue(set(exec_query_rv) == set(self.__db_query_empty_result))

    @mock.patch('OssimDB.time', autospec=True)
    @mock.patch('OssimDB.Lock', autospec=True)
    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_exec_non_query(self, mock_mysqldb, mock_lock, mock_time):
        mock_connection = mock.create_autospec(Connection)
        mock_cursor = mock.create_autospec(Cursor)
        mock_lock_obj = mock.create_autospec(Lock())
        mock_lock.return_value = mock_lock_obj
        mock_mysqldb.connect.return_value = mock_connection
        mock_connection.cursor.return_value = mock_cursor
        mock_cursor.fetchall.return_value = self.__db_query_result
        self.setUp()
        self.__ossim_db.connect()

        exec_non_query_rv = self.__ossim_db.execute_non_query(self.__db_query, self.__db_autocommit)

        self.assertTrue(mock_lock_obj.acquire.called)
        mock_connection.autocommit.assert_called_with(self.__db_autocommit)
        self.assertTrue(mock_connection.cursor.called)
        mock_cursor.execute.assert_called_with(self.__db_query, None)
        self.assertTrue(mock_cursor.close.called)
        self.assertTrue(mock_lock_obj.release.called)
        self.assertTrue(exec_non_query_rv)
        self.assertFalse(mock_connection.commit.called)
        self.assertFalse(mock_time.sleep.called)
        self.assertFalse(mock_connection.close.called)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_execute_non_query_parameterized(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        mock_cursor = mock.create_autospec(Cursor)
        mock_mysqldb.connect.return_value = mock_connection
        mock_connection.cursor.return_value = mock_cursor
        self.setUp()
        self.__ossim_db.connect()

        exec_non_query_rv = self.__ossim_db.execute_non_query(self.__db_param_query, params=self.__db_params)

        mock_cursor.execute.assert_called_with(self.__db_param_query, self.__db_params)
        self.assertTrue(exec_non_query_rv)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_execute_non_query_autoconnect(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        mock_mysqldb.connect.return_value = mock_connection
        self.setUp()

        execute_nq_rv = self.__ossim_db.execute_non_query(self.__db_query, self.__db_autocommit)

        mock_mysqldb.connect.assert_called_with(
            cursorclass=mock_mysqldb.cursors.DictCursor,
            db=self.__db,
            host=self.__db_host,
            passwd=self.__db_pass,
            user=self.__db_user
        )
        mock_connection.autocommit.assert_called_with(self.__db_autocommit)
        self.assertTrue(execute_nq_rv)

    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_execute_non_query_default_autocommit(self, mock_mysqldb):
        mock_connection = mock.create_autospec(Connection)
        mock_mysqldb.connect.return_value = mock_connection
        self.setUp()
        self.__ossim_db.connect()

        execute_nq_rv = self.__ossim_db.execute_non_query(self.__db_query)

        mock_connection.autocommit.assert_called_with(self.__db_autocommit_default)
        self.assertTrue(execute_nq_rv)

    @mock.patch('OssimDB.time', autospec=True)
    @mock.patch('OssimDB.Lock', autospec=True)
    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_execute_non_query_retry_success(self, mock_mysqldb, mock_lock, mock_time):
        failing_attempts = 2
        total_attempts = failing_attempts + 1
        mock_lock_obj = mock.create_autospec(Lock())
        mock_connections, mock_cursors = self.prepare_multiple_failing_queries(failing_attempts)
        # Last attempt should not throw an exception
        mock_cursors.append(mock.create_autospec(Cursor))
        mock_connections.append(mock.create_autospec(Connection))
        mock_connections[total_attempts - 1].cursor.return_value = mock_cursors[total_attempts - 1]
        mock_lock.return_value = mock_lock_obj
        mock_mysqldb.connect.side_effect = mock_connections
        self.setUp()
        self.__ossim_db.connect()

        exec_non_query_rv = self.__ossim_db.execute_non_query(self.__db_query)

        self.assertTrue(mock_lock_obj.acquire.called)
        self.assert_multiple_failing_queries(mock_connections, mock_cursors, failing_attempts)
        self.assertEqual(mock_time.sleep.call_count, failing_attempts)
        # Assert last attempt (successful)
        self.assertTrue(mock_connections[total_attempts - 1].cursor.called)
        self.assertTrue(mock_cursors[total_attempts - 1].execute.called)
        self.assertTrue(mock_cursors[total_attempts - 1].close.called)
        self.assertTrue(mock_lock_obj.release.called)
        self.assertTrue(exec_non_query_rv)

    @mock.patch('OssimDB.time', autospec=True)
    @mock.patch('OssimDB.Lock', autospec=True)
    @mock.patch('OssimDB.MySQLdb', autospec=True)
    def test_execute_non_query_retry_fail(self, mock_mysqldb, mock_lock, mock_time):
        failing_attempts = 4
        mock_lock_obj = mock.create_autospec(Lock())
        mock_connections, mock_cursors = self.prepare_multiple_failing_queries(failing_attempts)
        mock_lock.return_value = mock_lock_obj
        mock_mysqldb.connect.side_effect = mock_connections
        self.setUp()
        self.__ossim_db.connect()

        exec_non_query_rv = self.__ossim_db.execute_non_query(self.__db_query)

        self.assertTrue(mock_lock_obj.acquire.called)
        self.assert_multiple_failing_queries(mock_connections, mock_cursors, failing_attempts)
        self.assertEqual(mock_time.sleep.call_count, failing_attempts - 1) # last attempt without sleep()
        self.assertTrue(mock_lock_obj.release.called)
        self.assertFalse(exec_non_query_rv)

    @mock.patch('OssimDB.escape', autospec=True)
    @mock.patch('OssimDB.conversions', autospec=True)
    def test_format_query_tuple(self, mock_conversions, mock_escape):
        query = 'dummy query with params %s and %s'
        params = ('dummy param', 1)
        mock_escape_returns = ['\'%s\'' % param for param in params]
        mock_escape.side_effect = mock_escape_returns
        expected_rv = query % tuple((escape_rv for escape_rv in mock_escape_returns))

        rv = self.__ossim_db.format_query(query, params)

        mock_escape.assert_has_calls([
            mock.call(param, mock_conversions) for param in params
        ])
        self.assertEqual(rv, expected_rv)

    @mock.patch('OssimDB.escape', autospec=True)
    @mock.patch('OssimDB.conversions', autospec=True)
    def test_format_query_dict(self, mock_conversions, mock_escape):
        query = 'dummy query with params %(one)s and %(two)s'
        params = {'two': 'param_2', 'one': 'param_1'}

        def mock_escape_side_effect(param, conversions):
            return '\'%s\'' % param

        mock_escape.side_effect = mock_escape_side_effect
        expected_rv = query % dict(
            (key, mock_escape_side_effect(value, mock_conversions)) for key, value in params.iteritems()
        )

        rv = self.__ossim_db.format_query(query, params)

        mock_escape.assert_has_calls([
            mock.call(param, mock_conversions) for _, param in params.iteritems()
        ])
        self.assertEqual(rv, expected_rv)


if __name__ == '__main__':
    unittest.main()
