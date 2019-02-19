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

verbose = 0
doctor_cfg_file = '/etc/ossim/doctor/doctor.cfg'
ossim_setup_file = '/etc/ossim/ossim_setup.conf'
ossim_license_file = '/etc/ossim/ossim.lic'
plugin_list = 'all'
category_list = 'all'
severity_list = 'all'
appliance_type_list = 'current'
plugin_dir = '/etc/ossim/doctor/plugins'
output_type = 'none'
valid_output_types = ['none', 'file', 'ansible', 'support']
output_path = '/var/ossim/doctor'
output_file_prefix = 'data'
output_raw = False
version = 'VERSION'
nickname = 'Mewes'
ko = False
ignore_dummy = False
version_string = 'AlienVault Doctor version %s\n' % version

error_codes = {'invalid_dir': -1,
               'undef_software_profile': -2,
               'invalid_hardware_profile': -3,
               'missing_network_config': -4,
               'invalid_network_config': -5,
               'missing_mysql_config': -6,
               'undef_support_prefix': -7,
               'diff_versions_essential_packages': -8,
               'missing_sensor_config': -9,
               'cannot_connect_db': -10, }

exit_codes = {'all_good': 0,
              'ftp_upload_failed': 1}

severity = ['Emerg', 'Critical', 'Alert', 'Error', 'Warning', 'Notice', 'Info', 'Debug']
