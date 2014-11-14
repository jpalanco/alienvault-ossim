#!/usr/bin/env python
# -*- coding: utf-8 -*-
from distutils.core import setup
from distutils.command.build_py import build_py
import glob, os, py_compile, os.path, re

changelog = open('debian/changelog').read()
doctor_version = re.findall('^alienvault-doctor\s\((\d+\.\d+(?:\.\d+){0,1}(?:\.\d+){0,1}-\d+)\)', changelog)[0]
plugin_files = [f for f in glob.glob('doctor/plugins/*.plg')] + [f for f in glob.glob('doctor/plugins/*.list')]

class build_py_alt (build_py):
  def initialize_options (self):
    build_py.initialize_options(self)
    self.compile = True
    self.optimize = 2

  def byte_compile (self, files):
    build_py.byte_compile (self, files)

    try:
      build_dir = os.path.dirname(files[0])

      # Clean .py files.
      for f in files:
        os.unlink(f)

      # Clean .pyo files.
      for f in glob.glob(build_dir + '/*.pyo'):
        os.unlink(f)
    except Exception, msg:
      print 'Exception caught while byte compiling: ' + str(msg)

setup(cmdclass = dict(build_py=build_py_alt),
  name = "alienvault-doctor",
  version = doctor_version,
  description = "Check for configuration mistakes, hardware failures and others",
  long_description = """AlienVault Doctor helps AlienVault suite administrators detecting misconfigurations,
data inconsistencies or hardware failures, for example. It's customizable using a plugin approach.""",
  author = "Manuel Abeledo",
  author_email = "manuel@alienvault.com",
  url = "www.alienvault.com",
  license='GPL',
  classifiers=[
    "Programming Language :: Python",
    ],
  packages = ['doctor'],
  scripts = ['doctor/alienvault-doctor'],
  package_dir = {'doctor': 'doctor'},
  data_files=[('/usr/share/doc/alienvault/doctor', ['doctor/doc/TUTORIAL.pdf']),
              ('/usr/share/man/man8', ['doctor/man/alienvault-doctor.8.gz']),
              ('/etc/ossim/doctor', ['doctor/doctor.cfg', 'doctor/public_key.pem']),
              ('/etc/ossim/doctor/plugins', plugin_files)]
  )
