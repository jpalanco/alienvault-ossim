from unittest import TestCase
import nose
from tempfile import mkdtemp
from os import removedirs
import os.path
from shutil import copyfile, rmtree
import inspect
import ansible.runner
import sys
import random


class TestAvConfig(TestCase):
    @classmethod
    def setUpClass(cls):
        cls.__tempdir = mkdtemp(prefix="nose.temp", dir="/tmp")
        cls.__confpath = os.path.join(os.path.dirname(os.path.abspath(inspect.getfile(inspect.currentframe()))), "conf")

    @classmethod
    def tearDownClass(cls):
        rmtree(cls.__tempdir)

    def get_iface_list(self, conffile, debugfile, inventory):
        module_args = "sensor_interfaces=True conffile=%s debugfile=%s makebackup=False op=get" % (conffile, debugfile)
        i = ansible.inventory.Inventory(inventory)
        runner = ansible.runner.Runner(module_name='av_config', module_args=module_args, pattern='*', inventory=i)
        response = runner.run()
        nose.tools.ok_(response['contacted'].get('127.0.0.1'), msg="System doesn't return data")
        ifaces = response['contacted']['127.0.0.1']['data']
        return ifaces

    # Todo: Should be refactored using mocks since it modifies real host configs.
    # def test1(self):
    #     # Correctly load / modify / write cycle
    #     # Load a conf1
    #     copyfile(os.path.join(self.__class__.__confpath, "c1.conf"), os.path.join(self.__class__.__tempdir, "c1.conf"))
    #     path_load = os.path.join(self.__class__.__tempdir, "c1.conf")
    #     debug_file = os.path.join(self.__class__.__tempdir, "av.log")
    #     inventory = os.path.join(self.__class__.__confpath, "hosts")
    #
    #     i = ansible.inventory.Inventory(inventory)
    #     # Call the setup module to obtain the interfaces
    #     runner = ansible.runner.Runner(module_name='setup', module_args="filter=ansible_interfaces", pattern='*',
    #                                    inventory=i)
    #     # Live interfeaces
    #     response_live = runner.run()
    #     nose.tools.ok_(response_live['contacted'].get('127.0.0.1'), msg="Can't obtain system interfaces")
    #
    #     # Loaded file
    #     ifaces = self.get_iface_list(path_load, debug_file, inventory)
    #     #print ifaces
    #     # for iface in ifaces:
    #     #     nose.tools.ok_(iface in ['gamusino', 'silvermoon', 'waterdeep'],
    #     #                    msg="The ossim_setup.conf don't loaded correctly")
    #     nose.tools.ok_(response_live['contacted']['127.0.0.1'].get('ansible_facts'), msg="Can't obtain ansible facts")
    #     nose.tools.ok_(response_live['contacted']['127.0.0.1']['ansible_facts'].get('ansible_interfaces'),
    #                    msg="Can't get ansible interfaces")
    #     ifacelive = response_live['contacted']['127.0.0.1']['ansible_facts'].get('ansible_interfaces')
    #     nose.tools.ok_(len(ifacelive) >= 1, msg="We need at least a interface in the system")
    #
    #     # I don't want the "lo" interface
    #     if 'lo' in ifacelive:
    #         ifacelive.remove('lo')
    #
    #     clist = random.sample(ifacelive, random.randint(1, len(ifacelive)))
    #     c = ",".join(clist)
    #     module_args = "sensor_interfaces= conffile=%s debugfile=%s makebackup=False op=set" % (c, path_load, debug_file)
    #     runner = ansible.runner.Runner(module_name='av_config', module_args=module_args, pattern='*', inventory=i)
    #     response_set = runner.run()
    #     # Verify
    #     #print response_set
    #     nose.tools.ok_(response_set['contacted'].get('127.0.0.1'), msg="System doesn't return data")
    #     nose.tools.ok_(response_set['contacted']['127.0.0.1'].get('data'), msg="System doesn't return data")
    #     nose.tools.ok_(response_set['contacted']['127.0.0.1']['data'] == 'OK', msg="System doesn't return data")
    #     # Reverify
    #     ifacelist = self.get_iface_list(path_load, debug_file, inventory)
    #     s1 = set(ifacelist)
    #     s2 = set(clist)
    #     nose.tools.ok_(len(s1) == len(s2), msg="Different interfaces list in ossim_setup.conf bad written")
    #     nose.tools.ok_(s1.issubset(s2) == True, msg="Different interfaces list in ossim_setup.conf bad written")

    def test2(self):
        """ Should return empty interfaces when they are missing in conf. """
        copyfile(os.path.join(self.__class__.__confpath, "c2.conf"), os.path.join(self.__class__.__tempdir, "c2.conf"))
        path_load = os.path.join(self.__class__.__tempdir, "c2.conf")
        debug_file = os.path.join(self.__class__.__tempdir, "av.log")
        inventory = os.path.join(self.__class__.__confpath, "hosts")
        module_args = "sensor_interfaces=True conffile=%s debugfile=%s makebackup=False op=get" % (path_load,
                                                                                                   debug_file)
        i = ansible.inventory.Inventory(inventory)
        # Call the setup module to obtain the interfaces
        runner = ansible.runner.Runner(module_name='av_config', module_args=module_args, pattern='*', inventory=i)
        response = runner.run()
        nose.tools.ok_(response['contacted'].get('127.0.0.1'), msg="System doesn't return data")
        ifaces = response['contacted']['127.0.0.1']['data']['sensor_interfaces']
        nose.tools.ok_(ifaces is None, "System return a not empty ifaces list")

    def test3(self):
        """ Test 3 Trying to use an unknown interface """
        copyfile(os.path.join(self.__class__.__confpath, "c1.conf"), os.path.join(self.__class__.__tempdir, "c1.conf"))
        pathload = os.path.join(self.__class__.__tempdir, "c1.conf")
        debug_file = os.path.join(self.__class__.__tempdir, "av.log")
        inventory = os.path.join(self.__class__.__confpath, "hosts")
        i = ansible.inventory.Inventory(inventory)
        module_args = "sensor_interfaces=%s conffile=%s debugfile=%s makebackup=False op=set" % ('gamusino',
                                                                                                 pathload,
                                                                                                 debug_file)
        runner = ansible.runner.Runner(module_name='av_config', module_args=module_args, pattern='*', inventory=i)
        response = runner.run()
        nose.tools.ok_(response['contacted'].get('127.0.0.1'), msg="System doesn't return data")
        nose.tools.ok_(response['contacted']['127.0.0.1'].get('failed'), msg="System doesn't return data")

        # Obtain the ifaces list via ip
