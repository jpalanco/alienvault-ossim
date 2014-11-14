#!/usr/bin/env python
# -*- coding: utf-8 -*-
from distutils.core import setup
from sphinx.setup_command import BuildDoc
import glob, os, re

PACKAGE = 'api'
NAME = 'alienvault-api'
VERSION = '4.4.0'
REQUIRES = ['api_core']
PROVIDES = [NAME.replace('-', '_')]
INSTALL_DOC_DIR = '/usr/share/alienvault/api/static/apidocs'
DEFAULT_SCRIPTS = [f for f in glob.glob('api/etc/default/*')]
INIT_SCRIPTS = [f for f in glob.glob('api/etc/init.d/*')]
MONIT_SCRIPTS = [f for f in glob.glob('api/etc/monit/alienvault/*')]
API_CONFIGURATIONS = [f for f in glob.glob('api/etc/alienvault/api/*')]
ANSIBLE_SCRIPTS = [f for f in glob.glob('api/etc/ansible/*')]
SPECIAL_DATA_DIRS = ['api/etc', 'api/apidocs']
EXCLUDE_FILES=[]
PATHS_TO_EXCLUDE = ['api/lib/test','api/features']
def retrieve_data_files ():
    data_files = []

    # Create debian/dirs and fill with data.
    rules_files_data = open('debian/rules', 'r').read()
    [install_lib_path] = re.findall('\-\-install\-lib\=(\S+)\s', rules_files_data)
    for root, dirnames, filenames in os.walk(PACKAGE):
        if [x for x in PATHS_TO_EXCLUDE if root.startswith(x)]:
            continue
        if [x for x in SPECIAL_DATA_DIRS if root.startswith(x)] == []:
            file_list_dir = []
            for filename in filenames:
                f = os.path.join(root, filename)
                file_list_dir.append(f)

            data_files.append((install_lib_path + '/' + root, file_list_dir))

    return data_files
setup (
    cmdclass = {'build_sphinx': BuildDoc},
    name = NAME,
    version = VERSION,
    description = "The AlienVault API package",
    long_description = """The AlienVault API is an extensible and flexible way to access the platform data and services.
    It is meant to be the kernel of the AlienVault information system and a gate for developers to integrate new applications.
    This package provides the basic methods to access the API""",
    author = "AlienVault API team",
    author_email = "packages@alienvault.com",
    url = "www.alienvault.com",
    license='LGPLv2',
    classifiers=[
        'Environment :: Web Environment',
        'Environment :: Console',
        'Development Status :: 5 - Production/Stable',
        'Intended Audience :: Developers',
        'Intended Audience :: System Administrators',
        'License :: OSI Approved :: GNU Lesser General Public License v2 (LGPLv2)'
        'Operating System :: POSIX',
        'Programming Language :: Python :: 2.6',
        'Topic :: Software Development :: Libraries :: Application Frameworks'
    ],
    packages = [PACKAGE],
    package_dir = {PACKAGE: PACKAGE},
    requires = REQUIRES,
    provides = PROVIDES,
    data_files = [
        ('/etc/default', DEFAULT_SCRIPTS),
        ('/etc/ansible', ANSIBLE_SCRIPTS),
        ('/etc/init.d', INIT_SCRIPTS),
        ('/etc/monit/alienvault', MONIT_SCRIPTS),
        ('/etc/alienvault/api', API_CONFIGURATIONS)] +
    retrieve_data_files()
)
