#
#  License:
#
#  Copyright (c) 2013 AlienVault
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

import re
from db.methods.system import get_system_ip_from_system_id

from ansiblemethods.system.system import get_doctor_data
from ansiblemethods.helper import fetch_file

def get_support_info (system_id, ticket):
    args = {}
    args['output_type'] = 'support'
    args['output_raw'] = 'True'
    args['verbose'] = 2
    args['output_file_prefix'] = ticket

    (success, ip) = get_system_ip_from_system_id(system_id)
    if not success:
        return (False, "Bad system_id '%s'" % system_id)

    if not ticket.isdigit() or len(ticket) != 8:
        return (False, "Bad ticket id format: %s" % ticket)

    file_uploaded = False
    file_name = ''

    data = get_doctor_data ([ip], args)
    if ip in data['dark']:
        return (False, data['dark'][ip]['msg'])

    if data['contacted'][ip]['rc'] == 0:
        file_uploaded = True
    elif data['contacted'][ip]['rc'] == 1:
        file_name = data['contacted'][ip]['data'].replace('\n', '')
        # Clean to extract the filename
        file_name = re.sub(r'.*\/var\/ossim', '/var/ossim', file_name)
        file_name = re.sub(r'\.doctor.*', '.doctor', file_name)
    else:
        return (False, "Error Calling support tool")

    if not file_uploaded:
        (success, data) = fetch_file(ip, file_name, file_name)

    return (True, {'file_uploaded': file_uploaded, 'file_name': file_name})
