#!/usr/bin/env python
# -*- coding: utf-8 -*-
from distutils.core import setup
import re, os, sys

PACKAGE = 'api_core'
NAME = 'alienvault-api-core'
VERSION = '4.4.0'
REQUIRES = []
PROVIDES = [NAME.replace('-', '_')]

def is_package(path):
    return (
        os.path.isdir(path) and
        os.path.isfile(os.path.join(path, '__init__.py'))
        )

def find_packages(path, base="" ):
    """ Find all packages in path """
    packages = {}

    if not os.path.exists (path):
        return {}

    for item in os.listdir(path):
        dir = os.path.join(path, item)
        if is_package( dir ):
            if base:
                module_name = "%(base)s.%(item)s" % vars()
            else:
                module_name = item
            packages[module_name] = dir
            packages.update(find_packages(dir, module_name))
    return packages

def retrieve_data_files ():
    data_files = []

    # Create debian/dirs and fill with data.
    rules_files_data = open('debian/rules', 'r').read()
    [install_lib_path] = re.findall('\-\-install\-lib\=(\S+)\s', rules_files_data)
    for root, dirnames, filenames in os.walk(PACKAGE):
        file_list_dir = []
        for filename in filenames:
            f = os.path.join(root, filename)
            file_list_dir.append(f)

        data_files.append((install_lib_path + '/' + root, file_list_dir))

    return data_files

setup(
    name = NAME,
    version = VERSION,
    description = "The AlienVault API Core applications package",
    long_description = """The AlienVault API is an extensible and flexible way to access the platform data and services.
    It is meant to be the kernel of the AlienVault information system and a gate for developers to integrate new applications.
    The AlienVault API Core provides all the applications needed to run the API.""",
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
    packages = find_packages ('api_core'),
    package_dir = {PACKAGE: PACKAGE},
    requires = REQUIRES,
    provides = PROVIDES,
    data_files = retrieve_data_files (),
  )
