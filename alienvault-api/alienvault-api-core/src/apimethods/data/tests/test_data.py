from __future__  import print_function
import unittest
from datetime import datetime
from apimethods.data.status import format_messages
from mock import patch
class TestFormatMessages(unittest.TestCase):
    """ Class yo test format_messages """
    def setUp(self):
        pass
   
    def tearDown(self):
        pass
    
    @classmethod
    def setUpClass(cls):
        pass
    
    @classmethod
    def tearDownClass(cls):
        pass

    def test_0001(self):
        """
            Test Timestamp
        """
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test',
             'message_title': 'Test 1',
             'creation_time': ts}
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        self.assertEqual(messages[0]['message_description'],"%s UTC test" % ts.strftime("%Y-%m-%d %H:%M:%S"))

    def test_0002(self):
        """
            To much assets
        """
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test',
             'message_title': 'Test 1 NUM_ASSETS',
             'creation_time': ts,
             'additional_info':
                {
                '00000000000000000000000000010024':
                    {
                        'over_assets': 15,
                        'exceeding_assets': 5 
                    }
                }
             }
        ]
        #
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1 5')
        self.assertEqual(messages[0]['message_description'],"%s UTC test" % ts.strftime("%Y-%m-%d %H:%M:%S"))
    def test_0003(self):
        """
            Plugin changed
        """
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test\nPLUGINS_CHANGED\nPATH_PLUGINS_CHANGED',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'plugins_changed':
                        [
                            '/etc/ossim/plugin1.cfg',
                            '/etc/ossim/plugin2.cfg',
                            '/etc/ossim/plugin3.cfg'
                        ]
                }
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        lines = messages[0]['message_description'].split("\n")
        self.assertEqual(len(lines), 3)
        self.assertEqual(lines[0], "%s UTC test" % ts.strftime("%Y-%m-%d %H:%M:%S"))
        self.assertEqual(lines[1], "plugin1.cfg, plugin2.cfg, plugin3.cfg")
        self.assertEqual(lines[2], "/etc/ossim/plugin1.cfg, /etc/ossim/plugin2.cfg, /etc/ossim/plugin3.cfg")

    def test_0004(self):
        """
            Plugin changed
        """
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test\nPLUGINS_REMOVED\nPATH_PLUGINS_REMOVED',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'plugins_removed':
                        [
                            '/etc/ossim/plugin1.cfg',
                            '/etc/ossim/plugin2.cfg',
                            '/etc/ossim/plugin3.cfg'
                        ]
                }
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        lines = messages[0]['message_description'].split("\n")
        self.assertEqual(len(lines), 3)
        self.assertEqual(lines[0], "%s UTC test" % ts.strftime("%Y-%m-%d %H:%M:%S"))
        self.assertEqual(lines[1], "plugin1.cfg, plugin2.cfg, plugin3.cfg")
        self.assertEqual(lines[2], "/etc/ossim/plugin1.cfg, /etc/ossim/plugin2.cfg, /etc/ossim/plugin3.cfg")
    
    def test_0005(self):
        """
            check rsyslog_files_removed
        """
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test\nRSYSLOG_FILES_REMOVED\nPATH_RSYSLOG_FILES_REMOVED',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'rsyslog_files_removed':
                        [
                            '/etc/ossim/plugin1.cfg',
                            '/etc/ossim/plugin2.cfg',
                            '/etc/ossim/plugin3.cfg'
                        ]
                }
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        lines = messages[0]['message_description'].split("\n")
        self.assertEqual(len(lines), 3)
        self.assertEqual(lines[0], "%s UTC test" % ts.strftime("%Y-%m-%d %H:%M:%S"))
        self.assertEqual(lines[1], "plugin1.cfg, plugin2.cfg, plugin3.cfg")
        self.assertEqual(lines[2], "/etc/ossim/plugin1.cfg, /etc/ossim/plugin2.cfg, /etc/ossim/plugin3.cfg")

    def test_0006(self):
        """
            check rsyslog_files_removed
        """
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test\nRSYSLOG_FILES_CHANGED\nPATH_RSYSLOG_FILES_CHANGED',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'rsyslog_files_changed':
                        [
                            '/etc/ossim/plugin1.cfg',
                            '/etc/ossim/plugin2.cfg',
                            '/etc/ossim/plugin3.cfg'
                        ]
                }
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        lines = messages[0]['message_description'].split("\n")
        self.assertEqual(len(lines), 3)
        self.assertEqual(lines[0], "%s UTC test" % ts.strftime("%Y-%m-%d %H:%M:%S"))
        self.assertEqual(lines[1], "plugin1.cfg, plugin2.cfg, plugin3.cfg")
        self.assertEqual(lines[2], "/etc/ossim/plugin1.cfg, /etc/ossim/plugin2.cfg, /etc/ossim/plugin3.cfg")
    
    @patch('apimethods.data.status.db_get_hostname')
    def test_0007(self,mock):
        """ 
            Check system
        """
        mock.return_value = True, "menzoberrazan"
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test SYSTEM_NAME',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'system_id': 'menzoberrazan'
                }
              
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        self.assertEqual(messages[0]['message_description'],"%s UTC test menzoberrazan" % ts.strftime("%Y-%m-%d %H:%M:%S"))

    @patch('apimethods.data.status.db_get_hostname') 
    def test_0008(self,mock):
        """ 
            Check system
        """
        mock.return_value = False, ""
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test SYSTEM_NAME',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'system_id': 'menzoberrazan'
                }
              
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        self.assertEqual(messages[0]['message_description'],"%s UTC test Unknown" % ts.strftime("%Y-%m-%d %H:%M:%S"))
    @patch('apimethods.data.status.db_get_hostname') 
    def test_0009(self,mock):
        """ 
            Check system
        """
        mock.return_value = True, "menzoberrazan"
        ts = datetime.utcnow() 
        messages = [
            {'message_description':'TIMESTAMP test SYSTEM_NAME\nRSYSLOG_FILES_CHANGED\nPATH_RSYSLOG_FILES_CHANGED',
             'message_title': 'Test 1',
             'creation_time': ts,
             'additional_info':
                {
                    'system_id': 'menzoberrazan',
                    'rsyslog_files_changed':
                        [
                            '/etc/ossim/plugin1.cfg',
                            '/etc/ossim/plugin2.cfg',
                            '/etc/ossim/plugin3.cfg'
                        ]

                }

              
            }
        ]
        format_messages(messages)
        self.assertEqual(messages[0]['message_title'],'Test 1')
        lines = messages[0]['message_description'].split("\n")
        self.assertEqual(len(lines), 3)
        self.assertEqual(lines[0],"%s UTC test menzoberrazan" % ts.strftime("%Y-%m-%d %H:%M:%S"))
        self.assertEqual(lines[1], "plugin1.cfg, plugin2.cfg, plugin3.cfg")
        self.assertEqual(lines[2], "/etc/ossim/plugin1.cfg, /etc/ossim/plugin2.cfg, /etc/ossim/plugin3.cfg")


        

        

        
