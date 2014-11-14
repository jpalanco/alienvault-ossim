#!/usr/bin/env python
# -*- coding: utf-8 -*-
from virtualenv import create_bootstrap_script
import textwrap, virtualenv, re, os
import sys # for runtime version check

PACKAGE = 'api_core'
PACKAGE_DEBIAN_NAME = 'alienvault-' + PACKAGE.replace('_', '-')
BOOTSTRAP_SCRIPT = '_bootstrap.py'
BOOTSTRAP_BASE_SCRIPT = """
import subprocess, os

def after_install(options, home_dir):
    # Install distribute first (for some reason the setup would fail otherwise)
    subprocess.call([os.path.abspath(join(home_dir, 'bin', 'pip')), 'install', '-I', 'distribute==0.6.34'])

    # Install our packages.
    if sys.version_info < (2, 7, 0, 'final', 0):
        assert subprocess.call([os.path.abspath(join(home_dir, 'bin', 'pip')), 'install', '-r', 'requirements_squeeze.txt']) == 0, "pip fails to install requeriments.txt"
    else:
        assert subprocess.call([os.path.abspath(join(home_dir, 'bin', 'pip')), 'install', '-r', 'requirements_wheezy.txt']) == 0, "pip fails to install requeriments.txt"

    # Install git packages (hopefully this will be gone soon)
    #subprocess.call([os.path.abspath(join(home_dir, 'bin', 'pip')), 'install', '-I', 'git+git://github.com/hercules-team/python-augeas.git@fdb02831e2b3a362d0e60c8db7782bb6ac1967c7'])
    subprocess.call([os.path.abspath(join(home_dir, 'bin', 'pip')), 'install', '-I', 'git+git://github.com/alien-dev/python-augeas.git@0.0.1'])
"""

FILE_LIST_VAR = 'FILE_LIST'
DEBIAN_PRERM_SCRIPT = """#!/bin/sh

set -e

. /usr/share/debconf/confmodule

if [ "$OSSIM_DEBUG" = "TRUE" ];then
set -x
fi

# Tracked files
%s

# Delete files
for FILE in $%s
do
  if [ -e $FILE ]; then
    rm $FILE
  fi
done

exit 0
"""

def retrieve_all_files ():
    data_files = []

    # Create debian/dirs and fill with data.
    rules_files_data = open('debian/rules', 'r').read()
    [install_lib_path] = re.findall('\-\-install\-lib\=(\S+)\s', rules_files_data)
    file_list_dir = []
    for root, dirnames, filenames in os.walk(PACKAGE):
        for filename in filenames:
            f = os.path.join(root, filename)
            file_list_dir.append(install_lib_path + '/' + f)

    return file_list_dir

if __name__ == "__main__":
    print "** Configuring the environment"

    # Create a virtualenv and install packages.
    ## Create a bootstrap script to install additional packages.
    bstrap_script = open (BOOTSTRAP_SCRIPT, 'w')
    bstrap_output = create_bootstrap_script(textwrap.dedent(BOOTSTRAP_BASE_SCRIPT))
    bstrap_script.write (bstrap_output)
    bstrap_script.close()
    print "** Bootstrap script created"

    ## Create the virtualenv itself.
    import _bootstrap
    _bootstrap.create_environment (PACKAGE)
    _bootstrap.after_install (None, PACKAGE)
    init_file = open (PACKAGE + '/' + '__init__.py', 'w')
    init_file.close()
    print "** Virtual environment created"

    # Create the prerm debian script.
    prerm_fd = open ('debian/%s.prerm' % PACKAGE_DEBIAN_NAME, 'w')
    file_list = FILE_LIST_VAR + '=$(cat <<EOF\n' + str(retrieve_all_files ()).replace('[', '').replace(']', '').replace('\'', '').replace(',', '\n') + '\nEOF\n)'
    prerm_fd.write (DEBIAN_PRERM_SCRIPT % (file_list, FILE_LIST_VAR))
    prerm_fd.close ()
    print "** Debian prerm script created"
