#!/usr/bin/env python

from unittest import TestCase

#
# I need to import the 
# ../../../api_core/bin/rawlogscleaner
#
import os
import sys
import nose
import mock
import subprocess
from optparse import Values

# Load rawlogscleaner module here
execfile('/usr/share/python/alienvault-api-core/bin/rawlogscleaner')


class TestRawLogsCleaner(TestCase):

    @mock.patch('ansiblemethods.server.logger.delete_raw_logs', return_value=(True, {'dirsdeleted': range(0, 10)}))
    @mock.patch('optparse.OptionParser.parse_args')
    def test_001(self, mock_parse_args, mock_delete_raw_logs):
        """ Positive case """
        # Ugly hack to replace function with a mock
        globals()['delete_raw_logs'] = mock_delete_raw_logs

        cli_args = Values(defaults={'start': "2011/11/12", 'end': '2011/11/15', 'debug': None, 'path': None})
        mock_parse_args.return_value = (cli_args, '')

        self.assertEqual(logclean(), 0)
        mock_parse_args.assert_called_once_with()
        mock_delete_raw_logs.assert_called_once_with('127.0.0.1', cli_args.start, cli_args.end, cli_args.debug)

    @mock.patch('ansiblemethods.server.logger.delete_raw_logs', return_value=(False, {'dirsdeleted': []}))
    @mock.patch('optparse.OptionParser.parse_args')
    def test_002(self, mock_parse_args, mock_delete_raw_logs):
        """ Negative case no results """
        globals()['delete_raw_logs'] = mock_delete_raw_logs

        cli_args = Values(defaults={'start': "2011/11/12", 'end': None, 'debug': None, 'path': None})
        mock_parse_args.return_value = (cli_args, '')

        self.assertEqual(logclean(), -1)
        mock_parse_args.assert_called_once_with()
        mock_delete_raw_logs.assert_called_once_with('127.0.0.1', cli_args.start, cli_args.end, cli_args.debug)

    @mock.patch('optparse.OptionParser.parse_args')
    def test_003(self, mock_parse_args):
        """ Negative case bad options were provided """
        cli_args = Values(defaults={'start': "Not_a_date", 'end': None, 'debug': None, 'path': None})
        mock_parse_args.return_value = (cli_args, '')

        self.assertEqual(logclean(), -1)
        mock_parse_args.assert_called_once_with()