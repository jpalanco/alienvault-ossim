# -*- coding: utf-8 -*-
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

from celery.utils.log import get_logger

from ansiblemethods.sensor.ossec import ossec_win_deploy as ansible_ossec_win_deploy
from celerymethods.tasks import celery_instance

logger = get_logger("celery")

@celery_instance.task
def ossec_win_deploy(sensor_ip, agent_name, windows_ip, windows_username, windows_domain, windows_password):
    ansible_result = ansible_ossec_win_deploy(sensor_ip, agent_name, windows_ip, windows_username, windows_domain, windows_password)

    success = ansible_result[sensor_ip]['unreachable'] == 0 and ansible_result[sensor_ip]['failures'] == 0

    if success:
        result = ''
    else:
        result = 'Couldn\'t complete windows OSSEC agent deploy, %s steps completed' % ansible_result[sensor_ip]['ok']
        logger.error('Couldn\'t complete windows OSSEC agent deploy: %s' % str(ansible_result))

    return (success, result)
