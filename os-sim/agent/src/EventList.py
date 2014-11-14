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
import mutex

#
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class EventList(list):
    """Wrapper arround list object with mutual exclusion in append/remove methods."""

    MAX_SIZE = 5000

    def __init__(self):
        self.mutex = mutex.mutex()
        list.__init__(self)


    def appendRule(self, item):
        "Append with mutual exclusion"

        logger.debug("Appending object %s, list has %d elements" % \
                     (type(item), len(self)+1))
        self.mutex.lock(self.append, item)
        self.mutex.unlock()


    def removeRule(self, item):
        "Remove with mutual exclusion"

        logger.debug("Removing object %s, list has %d elements" % \
                     (type(item), len(self)-1))

        self.mutex.lock(self.remove, item)
        self.mutex.unlock()
        if hasattr(item, 'close'):
            item.close()

        del item


if __name__ == "__main__":

    m = EventList()
    m.appendRule("a")
    print m
    m.appendRule("b")
    print m
    m.removeRule(m[0])
    print m

