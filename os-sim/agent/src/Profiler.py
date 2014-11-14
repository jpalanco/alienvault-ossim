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
import time

class TimeProfiler:
    """ A utility class for profiling execution time for code """

    def __init__(self):
        # dictionary with times in seconds
        self.timedict = {}


    def mark(self, slot=''):
        """ Mark the current time into the slot 'slot' """

        # Note: 'slot' has to be string type
        # we are not checking it here.

        self.timedict[slot] = time.time()


    def unmark(self, slot=''):
        """ Unmark the slot 'slot' """

        # Note: 'slot' has to be string type
        # we are not checking it here.

        if self.timedict.has_key(slot):
            del self.timedict[slot]


    def lastdiff(self):
        """ Get time difference between now and the latest marked slot """

        # To get the latest slot, just get the max of values
        return time.time() - max(self.timedict.values())


    def elapsed(self, slot=''):
        """ 
        Get the time difference between now and a previous
        time slot named 'slot'
        """

        # Note: 'slot' has to be marked previously
        return time.time() - self.timedict.get(slot)


    def diff(self, slot1, slot2):
        """
        Get the time difference between two marked time
        slots 'slot1' and 'slot2'
        """

        return self.timedict.get(slot2) - self.timedict.get(slot1)


    def maxdiff(self):
        """
        Return maximum time difference marked
        """

        # difference of max time with min time
        times = self.timedict.values()
        return max(times) - min(times)

    
    def timegap(self):
        """
        Return the full time-gap since we started marking
        """
    
        # Return now minus min
        times = self.timedict.values()
        return time.time() - min(times)

    
    def cleanup(self):
        """
        Cleanup the dictionary of all marks
        """

        self.timedict.clear()
