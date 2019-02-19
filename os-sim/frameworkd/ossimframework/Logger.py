# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
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

#
# GLOBAL IMPORTS
#
import string, logging, os, sys



class Logger:
    '''
        Static class for logging purposes
         Use from other classes:
        
           from Logger import Logger
           logger = Logger.logger
        
           logger.debug("Some debug")
           logger.info("Some info")
           logger.error("Error")
        More info at http://docs.python.org/lib/module-logging.html
        
    '''

    logger = logging.getLogger('frameworkd')
    logger.setLevel(logging.INFO)

    DEFAULT_FORMAT = '%(asctime)s %(module)s [%(levelname)s]: %(message)s'
    SYSLOG_FORMAT = 'ossim-frameword: %(asctime)s %(module)s [%(levelname)s]: %(message)s'
    __formatter = logging.Formatter(DEFAULT_FORMAT)
    __streamhandler = None

    # load the console handler by default
    # it will be removed in daemon mode
    __streamhandler = logging.StreamHandler()
    __streamhandler.setFormatter(__formatter)
    logger.addHandler(__streamhandler)

    # Removes the stream handler
    # (Useful when agent starts in daemon mode)
    def remove_console_handler():
        if Logger.__streamhandler:
            Logger.logger.removeHandler(Logger.__streamhandler)

    remove_console_handler = staticmethod(remove_console_handler)

    # log to file (file should be log->file in configuration)
    def _add_file_handler(file, log_level = None):

        dir = file.rstrip(os.path.basename(file))
        if not os.path.isdir(dir):
            try:
                os.makedirs(dir, 0755)
            except OSError, e:
                print "Logger: Error adding file handler,", \
                    "can not create log directory (%s): %s" % (dir, e)
                return

        try:
            handler = logging.FileHandler(file)
        except IOError, e:
            print "Logger: Error adding file handler: %s" % (e)
            return

        handler.setFormatter(Logger.__formatter)
        if log_level: # modify log_level
            handler.setLevel(log_level)
        Logger.logger.addHandler(handler)

    _add_file_handler = staticmethod(_add_file_handler)


    def add_file_handler(file):
        Logger._add_file_handler(file)

    add_file_handler = staticmethod(add_file_handler)

    # Error file handler
    # the purpouse of this handler is to only log error and critical messages
    def add_error_file_handler(file):
        Logger._add_file_handler(file, logging.ERROR)

    add_error_file_handler = staticmethod(add_error_file_handler)

    # send events to a remote syslog
    def add_syslog_handler(address):
        from logging.handlers import SysLogHandler
        handler = SysLogHandler(address)
        handler.setFormatter(logging.Formatter(Logger.SYSLOG_FORMAT))
        Logger.logger.addHandler(handler)

    add_syslog_handler = staticmethod(add_syslog_handler)

    # show DEBUG messages or not
    # modifying the global (logger, not handler) threshold level
    def set_verbose(verbose = 'info'):
        if verbose.lower() == 'debug':
            Logger.logger.setLevel(logging.DEBUG)
        elif verbose.lower() == 'info':
            Logger.logger.setLevel(logging.INFO)
        elif verbose.lower() == 'warning':
            Logger.logger.setLevel(logging.WARNING)
        elif verbose.lower() == 'error':
            Logger.logger.setLevel(logging.ERROR)
        elif verbose.lower() == 'critical':
            Logger.logger.setLevel(logging.CRITICAL)
        else:
            Logger.logger.setLevel(logging.INFO)

    set_verbose = staticmethod(set_verbose)

    def next_verbose_level(verbose):
        levels = ['debug', 'info', 'warning', 'error', 'critical']
        if verbose in levels:
            index = levels.index(verbose)

            if index > 0:
                return levels[index-1]

        return verbose

    next_verbose_level = staticmethod(next_verbose_level)

# vim:ts=4 sts=4 tw=79 expandtab:
