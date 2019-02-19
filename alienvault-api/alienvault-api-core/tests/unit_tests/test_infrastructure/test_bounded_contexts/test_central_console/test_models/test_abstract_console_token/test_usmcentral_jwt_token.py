import unittest

from mock import patch, MagicMock

from infrastructure.bounded_contexts.central_console.models.abstract_console_token import usmcentral_jwt_token


class TestUSMCentralJwtToken(unittest.TestCase):

    @patch.object(usmcentral_jwt_token, 'jwt', autospec=True)
    def test_token(self, jwt_mock):
        expected_raw_data = 'rawdata'
        jwt_mock.decode.return_value = jwt_decoded = MagicMock(spec=dict)
        jwt_decoded.get.return_value = expected_issuer = 'issuer'

        actual_token = usmcentral_jwt_token.USMCentralJwtToken(expected_raw_data)

        jwt_mock.decode.assert_called_once_with(expected_raw_data, verify=False)
        jwt_decoded.get.assert_called_once_with(usmcentral_jwt_token.ISSUER_FIELD_NAME)
        self.assertEqual(actual_token.raw_data, expected_raw_data)
        self.assertEqual(actual_token.issuer, expected_issuer)


if __name__ == '__main__':
    unittest.main()
