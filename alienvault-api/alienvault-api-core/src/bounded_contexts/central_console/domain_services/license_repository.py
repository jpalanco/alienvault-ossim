import re
from datetime import datetime

import api_log

CONFIG_LICENSE_NAME = 'license'
CONFIG_LICENSE_TRIAL_INDICATOR = 'email'
LICENSE_IS_TRIAL = 'True'
LICENSE_IS_NOT_TRIAL = 'False'


class LicenseRepository(object):

    def __init__(self, license_constructor, config_repository):
        self._license_constructor = license_constructor
        self._config_repository = config_repository

    def get_license(self):
        license_info = self._config_repository.get_config(CONFIG_LICENSE_NAME)

        is_trial = LICENSE_IS_TRIAL if CONFIG_LICENSE_TRIAL_INDICATOR in license_info.value else LICENSE_IS_NOT_TRIAL

        try:
            expires_on = re.findall(r'expire=(.*?)$', license_info.value, re.M)[0]
            expires_on = int((
                datetime.strptime(expires_on, '%Y-%m-%d') - datetime.strptime('1970-01-01', '%Y-%m-%d')
            ).total_seconds())
        except Exception as exc:
            # License expiration is not mandatory in the central_console bounded context
            api_log.info('Did not manage to get license expiration, ignoring: {}'.format(str(exc)))
            expires_on = None

        try:
            devices = int(re.findall(r'devices=(.*?)$', license_info.value, re.M)[0])
        except Exception as exc:
            # License devices number is not mandatory in the central_console bounded context
            api_log.info('Did not manage to get license devices number, ignoring: {}'.format(str(exc)))
            devices = None

        return self._license_constructor(is_trial, expires_on, devices)
