import unittest

import os

from infrastructure.domain.central_console.model.abstract_console_token import usmcentral_jwt_token


USMCENTRAL_TOKEN_FILE_PARAM = 'USMCENTRAL_TOKEN_FILE'


class TestUSMCentralJwtToken(unittest.TestCase):

    def test_token(self):
        token_file_name = os.getenv(USMCENTRAL_TOKEN_FILE_PARAM)
        if not token_file_name:
            self.skipTest('Test environment parameter not set: {}'.format(USMCENTRAL_TOKEN_FILE_PARAM))
        with open(token_file_name) as token_file:
            expected_raw_data = token_file.read()
            expected_issuer = 'rtaylor.aveng.us'

        actual_token = usmcentral_jwt_token.USMCentralJwtToken(expected_raw_data)

        self.assertEqual(actual_token.raw_data, expected_raw_data)
        self.assertEqual(actual_token.issuer, expected_issuer)


if __name__ == '__main__':
    unittest.main()
