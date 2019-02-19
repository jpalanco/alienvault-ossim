#!/usr/bin/env python
# -*- coding: utf-8 -*-
from distutils.core import setup
from distutils.command.install_lib import install_lib
import re, glob, py_compile, os.path

doc = [ ('/usr/share/doc/ossim_agent',
            ['src/doc/INSTALL', 'src/doc/LICENSE'] )]

man  = [ ('/usr/share/man/man8', ['src/doc/ossim-agent.8.gz']) ]

conf = [('/etc/ossim/agent', [f for f in glob.glob('src/etc/agent/*')])]
logrotate = [('/etc/logrotate.d', ['src/etc/logrotate.d/ossim-agent'])]
iptables = [('/etc/iptables', ['src/etc/iptables/rules010-agent.iptables'])]
monitrc = [('/etc/monit/alienvault', ['src/etc/monit/alienvault/avagent.monitrc'])]

data = conf + logrotate + doc + man + iptables + monitrc
changelog = open('debian/changelog').read()
agent_version = '1:' + re.findall('^ossim-agent\s\(1:(\d+\.\d+(?:\.\d+){0,1}(?:\.\d+){0,1})-\d+\)', changelog)[0]

class install_lib_alt (install_lib):
  def byte_compile (self, files):
    pass


setup(cmdclass = dict(install_lib=install_lib_alt),
      name            = "ossim-agent",
      version         = agent_version,
      description     = "Alienvault Unified SIEM Security Information Management (Alienvault-Agent)",
      author          = "Alienvault Development Team",
      author_email    = "devel@alienvault.com",
      url             = "http://www.alienvault.com",
      license         = "GPL",

      packages = ['ossim-agent'],
      scripts = ['src/ossim-agent'],
      package_dir = {'ossim-agent': 'src'},
      data_files=data
  )
