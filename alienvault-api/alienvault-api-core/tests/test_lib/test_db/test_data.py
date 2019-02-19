import sys
import unittest
from uuid import uuid4
from datetime import datetime

import mock
try:
    from freezegun import freeze_time
except ImportError:
    sys.exit("[ERROR] That test requires module 'freezegun' to be installed!")

from db.methods.data import db_insert_current_status_message


class TestData(unittest.TestCase):

    def setUp(self):
        self.message_id = str(uuid4())
        self.component_id = str(uuid4())
        self.additional_info = {'test': '123'}
        self.replace_flag = True
        self.data_time_now = datetime.utcnow()

    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_wrong_component_type(self, _):
        res = db_insert_current_status_message(
            self.message_id, self.component_id, 'wrong_component', self.additional_info, self.replace_flag)
        self.assertEqual((False, 'Invalid component_type'), res)

    @mock.patch('db.methods.data.get_status_message_from_id')
    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_no_message_id_in_db(self, _, get_status_msg_id_mock):
        get_status_msg_id_mock.return_value = (False, 'test to fail')
        res = db_insert_current_status_message(
            self.message_id, self.component_id, 'host', self.additional_info, self.replace_flag)
        self.assertEqual((False, "The given message_id doesn't exist"), res)

    @mock.patch('db.methods.data.get_status_message_from_id')
    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_message_id_is_none(self, db_session_mock, get_status_msg_id_mock):
        get_status_msg_id_mock.return_value = (True, None)
        res = db_insert_current_status_message(
            self.message_id, self.component_id, 'host', self.additional_info, self.replace_flag)
        self.assertEqual((False, "The given message_id doesn't exist. Message is None"), res)
        db_session_mock.return_value.begin.assert_not_called()

    @mock.patch('db.methods.data.get_bytes_from_uuid')
    @mock.patch('db.methods.data.get_status_message_from_id')
    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_invalid_component_id(self, db_session_mock, get_status_msg_id_mock,
                                                                   bytes_from_uuid_mock):
        get_status_msg_id_mock.return_value = (True, 'correct_id_here')
        bytes_from_uuid_mock.return_value = None
        res = db_insert_current_status_message(
            self.message_id, self.component_id, 'net', self.additional_info, self.replace_flag)
        self.assertEqual((False, "Invalid component_id"), res)
        db_session_mock.return_value.begin.assert_not_called()

    @mock.patch('db.methods.data.delete_current_status_messages')
    @mock.patch('db.methods.data.get_status_message_from_id')
    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_delete_failed(self, db_session_mock, get_status_msg_id_mock,
                                                            delete_msg_mock):
        get_status_msg_id_mock.return_value = (True, 'correct_id_here')
        delete_msg_mock.return_value = (False, 'test to fail')

        res = db_insert_current_status_message(
            self.message_id, self.component_id, 'net', self.additional_info, self.replace_flag)
        self.assertEqual((False, "Unable to remove previous messages for the given message ID."), res)
        db_session_mock.return_value.begin.assert_not_called()

    @mock.patch('db.methods.data.delete_current_status_messages')
    @mock.patch('db.methods.data.get_status_message_from_id')
    @mock.patch('db.methods.data.Current_Status', autospec=True)
    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_ok(self, db_session_mock, curr_status_mock,
                                                 get_status_msg_id_mock, delete_msg_mock):
        get_status_msg_id_mock.return_value = (True, 'correct_id_here')
        delete_msg_mock.return_value = (True, 'test ok')
        current_status = curr_status_mock.return_value

        with freeze_time(self.data_time_now):
            res = db_insert_current_status_message(
                self.message_id, self.component_id, 'net', self.additional_info, self.replace_flag)
            self.assertEqual((True, ''), res)
            self.assertEqual('net', current_status.component_type)
            self.assertEqual(self.data_time_now, current_status.creation_time)
            self.assertEqual(self.additional_info, current_status.additional_info)
            self.assertEqual(0, current_status.suppressed)
            self.assertEqual(0, current_status.viewed)

        db_session_mock.return_value.begin.assert_called()
        db_session_mock.return_value.add.assert_called()
        db_session_mock.return_value.commit.assert_called()

    @mock.patch('db.methods.data.delete_current_status_messages')
    @mock.patch('db.methods.data.get_status_message_from_id')
    @mock.patch('db.methods.data.Current_Status', autospec=True)
    @mock.patch('db.Session', autospec=True)
    def test_db_insert_current_status_message_fail_to_add(self, db_session_mock, _,
                                                          get_status_msg_id_mock, delete_msg_mock):
        get_status_msg_id_mock.return_value = (True, 'correct_id_here')
        delete_msg_mock.return_value = (True, 'test ok')
        db_session_mock.return_value.add.side_effect = IOError('test err')

        res = db_insert_current_status_message(
            self.message_id, self.component_id, 'net', self.additional_info, self.replace_flag)

        self.assertEqual(res, (False, 'test err'))
        db_session_mock.return_value.begin.assert_called()
        db_session_mock.return_value.add.assert_called()
        db_session_mock.return_value.commit.assert_not_called()
        db_session_mock.return_value.rollback.assert_called()


if __name__ == '__main__':
    unittest.main()
