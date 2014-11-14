# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.
import unittest2 as unittest
import os
import sys
import glob
sys.path.insert(0,os.path.dirname(os.path.abspath(os.path.join(__file__, os.pardir))))

import time
import filecmp
from ossimsetupconfig import AVOssimSetupConfigHandler,BACKUP_FOLDER
from configparsererror import AVConfigParserErrors
from  utils import *
TEST_FILES_PATH = os.path.abspath(os.path.join(__file__, os.pardir))+"/test_data/"


"""
Test files description:
ossim_setup1.conf: Well formatted file.
ossim_setup2.conf: Float number
ossim_setup3.conf: Invalid file
ossim_setup4.conf:
ossim_setup5.conf: Invalid values on load
"""
class TestAVOssimSetupConfigHandlerPublicAPI(unittest.TestCase):
    def setUp(self):
        pass
    
    def test_load_config(self):
        #1 - File doesn't exists!
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup12.conf")
        #load config it's a private function. We can't access to it directly
        self.assertEqual(config.get_general_admin_dns(),None)
        self.assertTrue(config.has_errors())
        error_list =  config.get_error_list()
        self.assertTrue(error_list.has_key("FILE_ERRORS"))
        self.assertTrue(error_list["FILE_ERRORS"].has_key("file"))
        self.assertEqual(error_list["FILE_ERRORS"]["file"][0],2000)
        del config
        #2 - Invalid File - Duplicated section
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup4.conf")
        #load config it's a private function. We can't access to it directly
        self.assertEqual(config.get_general_admin_dns(),None)
        self.assertTrue(config.has_errors())
        error_list =  config.get_error_list()
        self.assertTrue(error_list.has_key("FILE_ERRORS"))
        self.assertTrue(error_list["FILE_ERRORS"].has_key("file"))
        self.assertEqual(error_list["FILE_ERRORS"]["file"][0],2001)
        del config
        #3 - Invalid File -(invalid syntax) 
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup3.conf")
        #load config it's a private function. We can't access to it directly
        self.assertEqual(config.get_general_admin_dns(),None)
        self.assertTrue(config.has_errors())
        error_list =  config.get_error_list()
        self.assertTrue(error_list.has_key("FILE_ERRORS"))
        self.assertTrue(error_list["FILE_ERRORS"].has_key("file"))
        self.assertEqual(error_list["FILE_ERRORS"]["file"][0],2001)
        del config
        
        #4 - Correct File
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        #load config it's a private function. We can't access to it directly
        #self.assertEqual(config.get_general_admin_dns(),"8.8.8.8")
        error_list =  config.get_error_list()
        print error_list
        self.assertFalse(config.has_errors())
        self.assertEqual(len(error_list),0)
        del config


    def test_save_ossim_setup_file(self):
        config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
        tmpfile = "/tmp/tmpossimsetup.cfg.%s" %(int(time.time()))
        print config.get_error_list()
        self.assertEqual(config.save_ossim_setup_file(tmpfile),AVConfigParserErrors.ALL_OK)


    def test_make_backup(self):
        if os.path.isdir(BACKUP_FOLDER):
            config = AVOssimSetupConfigHandler(TEST_FILES_PATH+"ossim_setup1.conf")
            config.make_backup()
            #check that there is almost on backup file
            backup_filter = "%s%s*"% (BACKUP_FOLDER,"ossim_setup1.conf")
            backup_files = glob.glob(backup_filter)
            nbackupfiles = len(backup_files)
            self.assertTrue(nbackupfiles>0)#Almost one file
            self.assertTrue(nbackupfiles<6)#no more than 5 files
        else:
            print "make backup tests couldn't be executed. %s not exists!" % BACKUP_FOLDER
