#!/usr/bin/env python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2013 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
import os
import sys

from setuptools import setup

current_dir = os.path.dirname(os.path.abspath(sys.argv[0]))

if sys.version_info < (2, 6) or sys.version_info>=(3,):
    print "This SDK runs with python 2.6 or upper but it not runs properly with python < 2.6 or python 3"
setup(name='alienvault-python-sdk',
      version='1.0.0',
      description='Official Alienvault REST API Client',
      author='Alienvault Devel Team',
      author_email='devel@alienvault.com',
      url='http://www.alienvault.com/',
      packages=['alienvault'],
      #install_requires=['needed packages'],
      #package_data={'alienvault': []}
     )
