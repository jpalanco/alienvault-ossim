# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.
import sys,os
sys.path.insert(0,os.path.dirname(os.path.abspath(os.path.join(__file__, os.pardir))))

import unittest2 as unittest
from avconfigparsererror import AVConfigParserErrors
class TestAVConfigParserErrors(unittest.TestCase):
    """Tests for AVConfigParserErrors.
    exit's methods won't be tested. 
    """
    def setUp(self):
        pass
    def test_get_str(self):
        #Test invalid code:
        self.assertEqual(AVConfigParserErrors.get_str("unknowcode"),AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE])
        self.assertEqual(AVConfigParserErrors.get_str(None),AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE])
        self.assertEqual(AVConfigParserErrors.get_str(1000),AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.FILE_NOT_EXIST])
        self.assertEqual(AVConfigParserErrors.get_str(0),AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.SUCCESS])


    def test_get_str_on_exception(self):
        self.assertEqual(AVConfigParserErrors.get_str_on_exception(None,None),"Exception (KeyError), Invalid error code Exception: None")
        self.assertEqual(AVConfigParserErrors.get_str_on_exception(0,None),"Success. Exception: None")
        self.assertEqual(AVConfigParserErrors.get_str_on_exception(0,KeyError),"Success. Exception: <type 'exceptions.KeyError'>")
        self.assertEqual(AVConfigParserErrors.get_str_on_exception(0,ValueError),"Success. Exception: <type 'exceptions.ValueError'>")


    def test_get_error(self):
        t = (None,AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE])
        self.assertEqual(AVConfigParserErrors.get_error(None),t)
        t = (999999,AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE])
        self.assertEqual(AVConfigParserErrors.get_error(999999),t)
        t = ("999999",AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE])
        self.assertEqual(AVConfigParserErrors.get_error("999999"),t)
        t = (0,AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.SUCCESS])
        self.assertEqual(AVConfigParserErrors.get_error(0),t)


    def test_get_error_msg(self):
        t = (None,AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE]+"<TTT>")
        self.assertEqual(AVConfigParserErrors.get_error_msg(None,"TTT"),t)
        
        t = (999999,AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE]+"<TTT>")
        self.assertEqual(AVConfigParserErrors.get_error_msg(999999,"TTT"),t)
        
        t = ("999999",AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE]+"<TTT>")
        self.assertEqual(AVConfigParserErrors.get_error_msg("999999","TTT"),t)
        
        t = (0,AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.SUCCESS]+"<TTT>")
        self.assertEqual(AVConfigParserErrors.get_error_msg(0,"TTT"),t)


    def tearDown(self):
        pass
