#!/usr/bin/env python

import glob, os
from distutils.core import setup

from ossimframework.__init__ import __version__


lib  = [ ('share/ossim-framework/ossimframework/', 
    glob.glob(os.path.join('ossimframework', '*.py')))
]

setup (
    name            = "ossim-framework",
    version         = __version__,
    description     = "OSSIM framework",
    author          = "OSSIM Development Team",
    author_email    = "ossim@ossim.net",
    url             = "http://www.ossim.net",
#    packages        = [ 'ossimframework' ],
    scripts         = [ 'ossim-framework' ],
    data_files      = lib
)

