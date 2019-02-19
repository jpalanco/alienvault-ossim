#!/usr/bin/env python
# -*- coding: utf-8 -*-
from distutils.core import setup

from sphinx.setup_command import BuildDoc

setup(
    cmdclass={'build_sphinx': BuildDoc},
    name='alienvault-api',
    version='5.2.0',
    description="The AlienVault API package",
    long_description="""The AlienVault API is an extensible and flexible way to access the platform data and services.
    It is meant to be the kernel of the AlienVault information system and a gate for developers to integrate new applications.
    This package provides the basic methods to access the API""",
    author="AlienVault API team",
    author_email="packages@alienvault.com",
    url="www.alienvault.com",
    license='LGPLv2',
    classifiers=[
        'Environment :: Web Environment',
        'Environment :: Console',
        'Development Status :: 5 - Production/Stable',
        'Intended Audience :: Developers',
        'Intended Audience :: System Administrators',
        'License :: OSI Approved :: GNU Lesser General Public License v2 (LGPLv2)'
        'Operating System :: POSIX',
        'Programming Language :: Python :: 2.7',
        'Topic :: Software Development :: Libraries :: Application Frameworks'
    ],
    packages=[
        'alienvault-api',
        'alienvault-api.api_i18n',
        'alienvault-api.blueprints',
        'alienvault-api.lib',
        'alienvault-api.blueprints.apps',
        'alienvault-api.blueprints.auth',
        'alienvault-api.blueprints.central_console',
        'alienvault-api.blueprints.data',
        'alienvault-api.blueprints.host',
        'alienvault-api.blueprints.job',
        'alienvault-api.blueprints.plugin',
        'alienvault-api.blueprints.sensor',
        'alienvault-api.blueprints.server',
        'alienvault-api.blueprints.status',
        'alienvault-api.blueprints.system',
        'alienvault-api.lib.monitors'
    ],
    package_dir={'alienvault-api': 'src'},
    # Notice that these files are installed using 'package_data'.
    # If it were installed with 'scripts', they would be set as executables.
    package_data={'alienvault-api': ['wwwroot/api.wsgi',
                                     'api_i18n/gen_locales.sh',
                                     'api_i18n/locales/alienvault_api.pot',
                                     'api_i18n/locales/en/LC_MESSAGES/alienvault_api.mo',
                                     'api_i18n/locales/en_US.po',
                                     'api_i18n/locales/es/LC_MESSAGES/alienvault_api.mo',
                                     'api_i18n/locales/es.po']},
)
