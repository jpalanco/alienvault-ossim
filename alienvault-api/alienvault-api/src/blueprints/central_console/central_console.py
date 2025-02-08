# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
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

from api.lib.auth import logged_permission
from api.lib.common import make_ok, make_error
from api.lib.utils import accepted_url
from flask import Blueprint, current_app, jsonify, request

from bounded_contexts.central_console.application_services.central_console_service import CentralConsoleService, \
    CONSOLE_CONNECTION_OK, CONSOLE_CONNECTION_DENIED, CONSOLE_CONNECTION_FAILED, CONSOLE_CONNECTION_NOT_CONFIGURED, \
    CONSOLE_TOKEN_ISSUER_NOT_REACHABLE, CONSOLE_TOKEN_REJECTED
from bounded_contexts.central_console.domain_services.control_node_repository import ControlNodeRepository
from bounded_contexts.central_console.domain_services.deployment_info_repository import DeploymentInfoRepository
from bounded_contexts.central_console.domain_services.license_repository import LicenseRepository
from bounded_contexts.central_console.domain_services.token_repository import TokenRepository
from bounded_contexts.central_console.models.contact_person import ContactPerson
from bounded_contexts.central_console.models.control_node import ControlNode
from bounded_contexts.central_console.models.deployment_info import DeploymentInfo
from bounded_contexts.central_console.models.license import License
from bounded_contexts.central_console.models.platform import Platform
from bounded_contexts.central_console.models.sensor import Sensor
from bounded_contexts.central_console.models.server import Server
from bounded_contexts.central_console.models.system import System
from infrastructure.bounded_contexts.central_console.application_services.abstract_console_proxy import \
    usmcentral_http_proxy
from infrastructure.bounded_contexts.central_console.domain_services.abstract_contact_person_repository.alchemy_contact_person_repository import \
    AlchemyContactPersonRepository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_platform_repository.ansible_platform_repository import \
    AnsiblePlatformRepository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_sensor_repository.alchemy_sensor_repository import \
    AlchemySensorRepository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_server_repository.alchemy_server_repository import \
    AlchemyServerRepository
from infrastructure.bounded_contexts.central_console.domain_services.abstract_system_repository.alchemy_system_repository import \
    AlchemySystemRepository
from infrastructure.bounded_contexts.central_console.models.abstract_console_token.usmcentral_jwt_token import \
    USMCentralJwtToken
from infrastructure.shared_kernel.config.domain_services.abstract_config_repository.alchemy_config_repository import \
    AlchemyConfigRepository
from shared_kernel.config.models.config import Config
from apimethods.system.system import (set_usm_central_status)

blueprint = Blueprint(__name__, __name__)

PUBLIC_CODE_OK = 4
PUBLIC_CODE_DENIED = 3
PUBLIC_CODE_NOT_CONFIGURED = 1
PUBLIC_CODE_FAILED = 5

console_status_to_public_codes_map = {
    CONSOLE_CONNECTION_OK: PUBLIC_CODE_OK,
    CONSOLE_CONNECTION_DENIED: PUBLIC_CODE_DENIED,
    CONSOLE_CONNECTION_FAILED: PUBLIC_CODE_FAILED,
    CONSOLE_CONNECTION_NOT_CONFIGURED: PUBLIC_CODE_NOT_CONFIGURED
}


def translate_to_public_status_code(console_status, fallback_code=PUBLIC_CODE_DENIED):
    return console_status_to_public_codes_map.get(console_status, fallback_code)


def build_console_service():
    config_repository = AlchemyConfigRepository(Config)
    token_repository = TokenRepository(USMCentralJwtToken, config_repository)
    platform_repository = AnsiblePlatformRepository(Platform, config_repository)

    system_repository = AlchemySystemRepository(System)
    server_repository = AlchemyServerRepository(Server)
    contact_repository = AlchemyContactPersonRepository(ContactPerson)
    control_node_repository = ControlNodeRepository(
        ControlNode,
        config_repository,
        system_repository,
        server_repository,
        contact_repository,
        platform_repository
    )

    license_repository = LicenseRepository(License, config_repository)
    sensor_repository = AlchemySensorRepository(Sensor, platform_repository)
    deployment_repository = DeploymentInfoRepository(
        DeploymentInfo,
        control_node_repository,
        license_repository,
        sensor_repository
    )
    console_proxy = usmcentral_http_proxy.USMCentralHttpProxy()

    return CentralConsoleService(token_repository, deployment_repository, console_proxy)


@blueprint.route('/status', methods=['GET'])
@logged_permission.require(http_exception=401)
def get_console_status():
    try:
        console_status = build_console_service().get_console_status()
    except Exception:
        current_app.logger.exception('get_console_status failed')
        return make_error('Did not manage to get the connection status. Please check log files for details.', 500)

    url = console_status.token and console_status.token.issuer or ''
    return jsonify(status=translate_to_public_status_code(console_status.status), url=url)


@blueprint.route('/connect', methods=['POST'])
@logged_permission.require(http_exception=401)
@accepted_url({'token': {'type': str, 'optional': False}})
def connect_console():
    raw_token = request.form.get('token')
    try:
        token = USMCentralJwtToken(raw_token)
    except Exception:
        current_app.logger.exception('Did not manage to decode raw token data "%s".', raw_token)
        return make_error('Invalid token', 500)

    try:
        console_status = build_console_service().register_console(token)
    except Exception:
        current_app.logger.exception('register_console failed')
        return make_error('Unable to connect to USM Central.  Please check log files for details.', 500)

    url = token and token.issuer or ''

    if console_status.status == CONSOLE_CONNECTION_FAILED:
        return make_error('Failed to reach the token issuer.', 500)
    elif console_status.status == CONSOLE_CONNECTION_DENIED:
        return make_error('Token denied by {}'.format(url), 500)

    set_usm_central_status(enabled=True)

    return jsonify(status=translate_to_public_status_code(console_status.status), url=url)


@blueprint.route('/disconnect', methods=['POST'])
@logged_permission.require(http_exception=401)
def disconnect_console():
    try:
        set_usm_central_status(enabled=False)
        console_status = build_console_service().unregister_console()
    except Exception:
        current_app.logger.exception('unregister_console failed')
        return make_error('Did not manage to disable the connection. Please check log files for details', 500)

    return jsonify(status=translate_to_public_status_code(console_status.status))
