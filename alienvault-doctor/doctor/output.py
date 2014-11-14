#
#  License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2013 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

import sys
import time
from itertools import cycle

BLUE = '\033[1m\x1B[34m%s\033[0m'
GREEN = '\033[1m\x1B[32m%s\033[0m'
YELLOW = '\033[1m\x1B[33m%s\033[0m'
RED = '\033[1m\x1B[31m%s\033[0m'
EMPH = '\033[1m\x1B[37m%s\033[0m'
__enabled = True

'''
Output class.
Prints output messages using different colors.
'''
class Output (object):
  enabled = True

  @classmethod
  def set_std_output (cls, enable):
    cls.enabled = enable

  # Prints a debug message in blue.
  @staticmethod
  def debug (str = ''):
    if Output.enabled:
      print ('[' + BLUE + '] % s') % ('Debug', str)

  # Prints a info message in green.
  @staticmethod
  def info (str = ''):
    if Output.enabled:
      print ('[' + GREEN + '] % s') % ('Info', str)

  # Prints a warning message in yellow.
  @staticmethod
  def warning (str = ''):
    if Output.enabled:
      print ('[' + YELLOW + '] % s') % ('Warning', str)

  # Prints an error message in red.
  @staticmethod
  def error (str = ''):
    if Output.enabled:
      print ('[' + RED + '] % s') % ('Error', str)

  # Prints a message emphasizing a set of words.
  @staticmethod
  def emphasized (string = '', words = [], colors = [EMPH], newline = True):
    if Output.enabled:
      new_string = string
      cycle_colors = cycle(colors)
      for word in words:
        new_string = new_string.replace (word, (cycle_colors.next() % word))
      sys.stdout.write (new_string + ('\n' if newline else ''))

class Progress:
  @staticmethod
  def repeat (symbols):
    i = 1
    symbols_len = len (symbols)
    while (i < symbols_len):
      sys.stdout.write("%s%s" % (('\b' * len(symbols[i - 1])), symbols[i]))
      i += 1
      sys.stdout.flush()
      time.sleep(.2)
    sys.stdout.write("%s" % ('\b' * len(symbols[i - 1])))

  @staticmethod
  def dots ():
    if Output.enabled:
      Progress.repeat (['', '.  ', '.. ', '...'])
