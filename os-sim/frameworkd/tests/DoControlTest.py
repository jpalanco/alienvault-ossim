import unittest
import mock
from MySQLdb.connections import DatabaseError

from DoControl import ControlManager


class DoControlTest(unittest.TestCase):

    def setUp(self):
        self.sensor_ip = '111.222.333.444'
        self. conf = {
            'ossim_host': 'test',
            'ossim_base': 'test',
            'ossim_user': 'test',
            'ossim_pass': 'test'
        }
        self.alert_nf_db_result = (
            {'conf': 'agg_function', 'value': '1'},
            {'conf': 'inspection_window', 'value': '2'},
            {'conf': 'tcp_max_download', 'value': '100'},
            {'conf': 'tcp_max_upload', 'value': '10'},
            {'conf': 'udp_max_download', 'value': '100'},
            {'conf': 'udp_max_upload', 'value': '10'}
        )

    @mock.patch('DoControl.DoControl', autospec=True)
    @mock.patch('DoControl.OssimDB', autospec=True)
    def test_get_alert_netflow_setup_ok(self, ossim_db_mock, _):
        ossim_db_mock.return_value.exec_query.return_value = self.alert_nf_db_result
        control_mgr = ControlManager(self.conf)

        res = control_mgr.get_alert_netflow_setup()
        self.assertEqual(res, dict(((i['conf'], i['value']) for i in self.alert_nf_db_result)))

    @mock.patch('DoControl.DoControl', autospec=True)
    @mock.patch('DoControl.OssimDB', autospec=True)
    def test_get_alert_netflow_setup_db_err(self, ossim_db_mock, _):
        ossim_db_mock.return_value.exec_query.side_effect = DatabaseError('test err')
        control_mgr = ControlManager(self.conf)

        res = control_mgr.get_alert_netflow_setup()
        self.assertEqual(res, {})
        # check that it will return 0 by default
        self.assertEqual(0, res['udp_max_upload'])
        self.assertEqual(0, res['tcp_max_upload'])

    @mock.patch('DoControl.DoControl', autospec=True)
    @mock.patch('DoControl.OssimDB', autospec=True)
    def test_process_get_alert_nf_setup_response_ok(self, ossim_db_mock, _):
        ossim_db_mock.return_value.exec_query.return_value = self.alert_nf_db_result
        control_mgr = ControlManager(self.conf)
        ctrl_line = 'control action="get_alert_nf_setup"'
        expected_response = 'control get_alert_nf_setup ' \
                            'agg_function="1" inspection_window="2" tcp_max_download="100" tcp_max_upload="10" ' \
                            'udp_max_download="100" udp_max_upload="10" ackend\n'

        response = control_mgr.process(self.sensor_ip, '', ctrl_line)
        self.assertEqual(expected_response, response)

    @mock.patch('DoControl.DoControl', autospec=True)
    @mock.patch('DoControl.OssimDB', autospec=True)
    def test_process_get_alert_nf_setup_response_bad_command(self, ossim_db_mock, _):
        ossim_db_mock.return_value.exec_query.return_value = self.alert_nf_db_result
        control_mgr = ControlManager(self.conf)
        ctrl_line = 'control action="get_alert_nf_setup_err"'
        expected_response = 'control action="get_alert_nf_setup_err" errno="-1" error="No agents available." ackend\n'

        response = control_mgr.process(self.sensor_ip, '', ctrl_line)
        self.assertEqual(expected_response, response)


if __name__ == '__main__':
    unittest.main()
