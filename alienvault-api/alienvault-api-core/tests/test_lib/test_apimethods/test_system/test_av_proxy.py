import httplib
import unittest
import urllib2

import mock
from apimethods.system.proxy import AVProxy


class TestAVProxy(unittest.TestCase):
    """
        Tests for av_proxy module
    """
    simple_proxy = "proxy = http://testproxy.com"
    proxy_with_port = "proxy = http://testproxy.com:1234"
    proxy_with_port_and_auth = "proxy = http://testproxy.com:1234\nproxy-user = user:password"
    bad_proxy = "bad proxy content\nproxo = aaa"

    def setUp(self):
        self.fake_proxy_file = 'proxy_file_with_correct_data'
        # Mock
        patcher = mock.patch('apimethods.system.proxy.read_file', return_value=(False, 'no file found'))
        self.mock_read_file = patcher.start()
        self.addCleanup(patcher.stop)

    def get_proxy_url(self, proxy_file_content):
        return proxy_file_content.split(" = ")[1].replace("http://", "").split("\n")[0]

    def test_constructor_1(self):
        """ AVProxy: No authentication required """
        self.mock_read_file.return_value = (True, self.simple_proxy)

        proxy = AVProxy(proxy_file=self.fake_proxy_file)
        self.mock_read_file.assert_called_once_with('127.0.0.1', self.fake_proxy_file)
        self.assertEqual(proxy.get_proxy_url(), self.get_proxy_url(self.simple_proxy))
        self.assertFalse(proxy.need_authentication())

    def test_constructor_2(self):
        """ AVProxy: Check proxy with port """
        self.mock_read_file.return_value = (True, self.proxy_with_port)

        proxy = AVProxy(proxy_file=self.fake_proxy_file)
        self.assertEqual(proxy.get_proxy_url(), self.get_proxy_url(self.proxy_with_port))
        self.assertFalse(proxy.need_authentication())

    def test_constructor_3(self):
        """ AVProxy: Check auth proxy """
        self.mock_read_file.return_value = (True, self.proxy_with_port_and_auth)

        proxy = AVProxy(proxy_file=self.fake_proxy_file)
        self.assertEqual(proxy.get_proxy_url(), self.get_proxy_url(self.proxy_with_port_and_auth))
        self.assertTrue(proxy.need_authentication())

    def test_constructor_4(self):
        """ AVProxy: Check bad proxy """
        self.mock_read_file.return_value = (True, self.bad_proxy)

        proxy = AVProxy(proxy_file=self.fake_proxy_file)
        self.assertEqual(None, proxy.get_proxy_url())
        self.assertFalse(proxy.need_authentication())

    def test_constructor_5(self):
        """ AVProxy: Check non-existent proxy file """
        self.mock_read_file.return_value = (False, 'not exist')

        proxy = AVProxy(proxy_file=self.fake_proxy_file)
        self.assertEqual(None, proxy.get_proxy_url())
        self.assertFalse(proxy.need_authentication())

    @mock.patch.object(AVProxy, '_AVProxy__build_opener')
    def test_no_proxy_connect_with_url(self, mock_opener):
        """ AVProxy: Open connection without proxy using url """
        expected_response = 'response OK'
        mock_opener.return_value.open.return_value = expected_response

        proxy = AVProxy()
        response = proxy.open("http://python.org", timeout=2)
        self.assertEqual(expected_response, response)
        call_args, call_kwargs = mock_opener.return_value.open.call_args
        req_obj_from_call = call_args[0]
        self.assertEqual(AVProxy.USER_AGENT, req_obj_from_call.get_header('User-agent'))
        self.assertIsInstance(req_obj_from_call, urllib2.Request)
        self.assertEqual(call_kwargs, {'timeout': 2})

    @mock.patch.object(AVProxy, '_AVProxy__build_opener')
    def test_no_proxy_connect_with_request(self, mock_opener):
        """ AVProxy: Open connection without proxy using request """
        expected_response = 'response OK'
        mock_opener.return_value.open.return_value = expected_response

        proxy = AVProxy()
        request = urllib2.Request("http://python.org")
        response = proxy.open(request)
        self.assertEqual(expected_response, response)
        call_args, _ = mock_opener.return_value.open.call_args
        req_obj_from_call = call_args[0]
        self.assertEqual({}, req_obj_from_call.headers)
        self.assertIsInstance(req_obj_from_call, urllib2.Request)

    def test_no_proxy_connect_url_aut(self):
        """ AVProxy: Bad Proxy with retries """
        self.mock_read_file.return_value = (True, self.proxy_with_port_and_auth)

        proxy = AVProxy(proxy_file='auth_proxy')
        self.assertRaises((urllib2.URLError, IOError, httplib.HTTPException),
                          proxy.open, "http://python.org", timeout=0.5, retries=1)
