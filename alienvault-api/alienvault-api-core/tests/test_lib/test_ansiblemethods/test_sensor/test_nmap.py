import mock
import unittest

from ansiblemethods.sensor.nmap import ansible_run_nmap_scan


class TestNMAP(unittest.TestCase):
    """ Class to test NMAP related functions executed by Ansible.
    """

    def setUp(self):
        self.sensor_ip = '10.11.12.14'
        self.target_system_ip = '10.11.12.15'
        self.scan_type = 'test'
        self.reverse_dns = True
        self.scan_timing = '10'
        self.autodetect = True
        self.port_range = '100-200'
        self.job_id = '1'

    @mock.patch('ansiblemethods.sensor.nmap.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.nmap.ansible')
    def test_get_ossec_rule_filenames_ok_all_params_passed(self, ans_mock, ans_response_mock):
        """ Tests `get_ossec_rule_filenames`: exception raised.
        """
        result_msg = 'test ok'
        ans_mock.run_module.return_value = {
            'contacted': {
                self.sensor_ip: {
                    'data': result_msg
                }
            }
        }
        ans_response_mock.return_value = (True, '')
        args_string = 'target={} scan_type={} rdns={} scan_timming={} autodetect={} scan_ports={} job_id={}'.format(
            self.target_system_ip, self.scan_type, str(self.reverse_dns).lower(),
            self.scan_timing, str(self.autodetect).lower(), self.port_range, self.job_id)

        status, result = ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_type, self.reverse_dns,
                                               self.scan_timing, self.autodetect, self.port_range, self.job_id)
        ans_mock.run_module.assert_called_once_with([self.sensor_ip], 'av_nmap', args_string)
        self.assertEqual((True, result_msg), (status, result))

    @mock.patch('ansiblemethods.sensor.nmap.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.nmap.ansible', return_value={})
    def test_get_ossec_rule_filenames_args_no_scan_type(self, ans_mock, _):
        """ Tests `get_ossec_rule_filenames`: check that no scan_types is set.
        """
        self.scan_types = None
        args_string = 'target={} rdns={} scan_timming={} autodetect={} scan_ports={} job_id={}'.format(
            self.target_system_ip, str(self.reverse_dns).lower(),
            self.scan_timing, str(self.autodetect).lower(), self.port_range, self.job_id)

        ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_types, self.reverse_dns,
                              self.scan_timing, self.autodetect, self.port_range, self.job_id)
        ans_mock.run_module.assert_called_once_with([self.sensor_ip], 'av_nmap', args_string)

    @mock.patch('ansiblemethods.sensor.nmap.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.nmap.ansible', return_value={})
    def test_get_ossec_rule_filenames_args_no_reverse_dns(self, ans_mock, _):
        """ Tests `get_ossec_rule_filenames`: check that no reverse_dns is set.
        """
        self.reverse_dns = None
        args_string = 'target={} scan_type={} scan_timming={} autodetect={} scan_ports={} job_id={}'.format(
            self.target_system_ip, self.scan_type, self.scan_timing, str(self.autodetect).lower(),
            self.port_range, self.job_id)

        ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_type, self.reverse_dns,
                              self.scan_timing, self.autodetect, self.port_range, self.job_id)
        ans_mock.run_module.assert_called_once_with([self.sensor_ip], 'av_nmap', args_string)

    @mock.patch('ansiblemethods.sensor.nmap.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.nmap.ansible', return_value={})
    def test_get_ossec_rule_filenames_args_no_scan_timing(self, ans_mock, _):
        """ Tests `get_ossec_rule_filenames`: check that no scan_timing is set.
        """
        self.scan_timing = None
        args_string = 'target={} scan_type={} rdns={} autodetect={} scan_ports={} job_id={}'.format(
            self.target_system_ip, self.scan_type, str(self.reverse_dns).lower(),
            str(self.autodetect).lower(), self.port_range, self.job_id)

        ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_type, self.reverse_dns,
                              self.scan_timing, self.autodetect, self.port_range, self.job_id)
        ans_mock.run_module.assert_called_once_with([self.sensor_ip], 'av_nmap', args_string)

    @mock.patch('ansiblemethods.sensor.nmap.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.nmap.ansible', return_value={})
    def test_get_ossec_rule_filenames_args_no_autodetect(self, ans_mock, _):
        """ Tests `get_ossec_rule_filenames`: check that no autodetect is set.
        """
        self.autodetect = None
        args_string = 'target={} scan_type={} rdns={} scan_timming={} scan_ports={} job_id={}'.format(
            self.target_system_ip, self.scan_type, str(self.reverse_dns).lower(), self.scan_timing,
            self.port_range, self.job_id)

        ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_type, self.reverse_dns,
                              self.scan_timing, self.autodetect, self.port_range, self.job_id)
        ans_mock.run_module.assert_called_once_with([self.sensor_ip], 'av_nmap', args_string)

    @mock.patch('ansiblemethods.sensor.nmap.ansible_is_valid_response', return_value=(True, ''))
    @mock.patch('ansiblemethods.sensor.nmap.ansible', return_value={})
    def test_get_ossec_rule_filenames_args_no_scan_ports(self, ans_mock, _):
        """ Tests `get_ossec_rule_filenames`: check that no scan_ports is set.
        """
        self.port_range = None
        args_string = 'target={} scan_type={} rdns={} scan_timming={} autodetect={} job_id={}'.format(
            self.target_system_ip, self.scan_type, str(self.reverse_dns).lower(),
            self.scan_timing, str(self.autodetect).lower(), self.job_id)

        ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_type, self.reverse_dns,
                              self.scan_timing, self.autodetect, self.port_range, self.job_id)
        ans_mock.run_module.assert_called_once_with([self.sensor_ip], 'av_nmap', args_string)

    @mock.patch('ansiblemethods.sensor.nmap.ansible')
    def test_get_ossec_rule_filenames_run_time_error(self, ans_mock):
        """ Tests `get_ossec_rule_filenames`: exception raised.
        """
        err_msg = 'test err'
        ans_mock.run_module.side_effect = IOError(err_msg)

        status, result = ansible_run_nmap_scan(self.sensor_ip, self.target_system_ip, self.scan_type, self.reverse_dns,
                                               self.scan_timing, self.autodetect, self.port_range, self.job_id)
        self.assertEqual((False, err_msg), (status, result))
