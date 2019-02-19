import unittest

import imp

from mock import MagicMock, patch, call
from sqlalchemy.exc import ArgumentError, InvalidRequestError
from sqlalchemy.orm import Query
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound

from shared_kernel.config.models.config import Config
from infrastructure.shared_kernel.config.domain_services.abstract_config_repository import alchemy_config_repository


class TestAlchemyConfigRepository(unittest.TestCase):

    def setUp(self):
        def kill_patches():
            patch.stopall()
            imp.reload(alchemy_config_repository)
        self.addCleanup(kill_patches)

        self.require_db_deco_mock = MagicMock(side_effect=lambda x: x)
        patch('apimethods.decorators.require_db', self.require_db_deco_mock).start()
        imp.reload(alchemy_config_repository)

        self.config_model_constructor = MagicMock(spec=Config)
        self.alchemy_repository = alchemy_config_repository.AlchemyConfigRepository(self.config_model_constructor)

    def test_get_config_decorated(self):
        get_config = alchemy_config_repository.AlchemyConfigRepository.get_config.__func__
        self.assertTrue(
            call(get_config) in self.require_db_deco_mock.call_args_list,
            '@require_db is missing'
        )

    @patch.object(alchemy_config_repository, 'Config', autospec=True)
    @patch('db.session', autospec=True)
    def test_get_config(self, session_mock, alchemy_config_mock):
        query_mock = session_mock.query.return_value = MagicMock(spec=Query)
        query_mock_filtered = query_mock.filter.return_value = MagicMock(spec=Query)
        query_mock_filtered.one.return_value = alchemy_config_mock
        alchemy_config_mock.conf = expected_conf_name = MagicMock()
        alchemy_config_mock.value = MagicMock()

        actual_config = self.alchemy_repository.get_config(expected_conf_name)

        session_mock.query.assert_called_once_with(alchemy_config_mock)
        query_mock.filter.assert_called_once()
        alchemy_config_mock.conf.__eq__.assert_called_once_with(expected_conf_name)
        query_mock_filtered.one.assert_called_once_with()
        self.config_model_constructor.assert_called_once_with(alchemy_config_mock.conf, alchemy_config_mock.value)
        self.assertEqual(actual_config, self.config_model_constructor.return_value)

    @patch.object(alchemy_config_repository, 'Config', autospec=True)
    @patch('db.session', autospec=True)
    def test_get_config_no_result_found(self, session_mock, alchemy_config_mock):
        query_mock = session_mock.query.return_value = MagicMock(spec=Query)
        query_mock_filtered = query_mock.filter.return_value = MagicMock(spec=Query)
        query_mock_filtered.one.side_effect = NoResultFound()
        expected_token = None

        actual_token = self.alchemy_repository.get_config(MagicMock())

        self.assertEqual(expected_token, actual_token)

    def test_add_config_decorated(self):
        add_config = alchemy_config_repository.AlchemyConfigRepository.add_config.__func__
        self.assertTrue(
            call(add_config) in self.require_db_deco_mock.call_args_list,
            '@require_db is missing'
        )

    @patch.object(alchemy_config_repository, 'Config', autospec=True)
    @patch('db.session', autospec=True)
    def test_add_config(self, session_mock, alchemy_config_mock):
        self.alchemy_repository.get_config = get_config_mock = MagicMock()
        get_config_mock.return_value = None
        new_config_entity = MagicMock(spec=Config)
        alchemy_config_mock.return_value = alchemy_config = MagicMock()

        self.alchemy_repository.add_config(new_config_entity)

        get_config_mock.assert_called_once_with(new_config_entity.conf)
        alchemy_config_mock.assert_called_once_with(
            conf=new_config_entity.conf,
            value=new_config_entity.value
        )
        session_mock.begin.assert_called_once_with()
        session_mock.add.assert_called_once_with(alchemy_config)
        session_mock.commit.assert_called_once_with()

    @patch('db.session', autospec=True)
    def test_add_config_add_exception(self, session_mock):
        self.alchemy_repository.get_config = get_config_mock = MagicMock(name='get_config')
        get_config_mock.return_value = None
        new_config_entity = MagicMock(spec=Config)
        expected_err_message = 'error'
        session_mock.add.side_effect = Exception(expected_err_message)

        self.assertRaises(
            Exception,
            self.alchemy_repository.add_config,
            new_config_entity
        )
        session_mock.rollback.assert_called_once_with()

    @patch('db.session', autospec=True)
    def test_add_config_commit_exception(self, session_mock):
        self.alchemy_repository.get_config = get_config_mock = MagicMock(name='get_config')
        get_config_mock.return_value = None
        new_config_entity = MagicMock(spec=Config)
        expected_err_message = 'error'
        session_mock.commit.side_effect = Exception(expected_err_message)

        self.assertRaises(
            Exception,
            self.alchemy_repository.add_config,
            new_config_entity
        )
        session_mock.rollback.assert_called_once_with()

    @patch.object(alchemy_config_repository, 'Config', autospec=True)
    @patch('db.session', autospec=True)
    def test_add_config_already_exists(self, session_mock, config_model_mock):
        self.alchemy_repository.get_config = get_config_mock = MagicMock(name='get_config')
        get_config_mock.return_value = MagicMock(spec=Config)
        new_config_entity = MagicMock(spec=Config)

        self.assertRaises(
            alchemy_config_repository.ConfigAlreadyExistsError,
            self.alchemy_repository.add_config,
            new_config_entity
        )

        config_model_mock.assert_not_called()
        session_mock.begin.assert_not_called()
        session_mock.add.assert_not_called()

    def test_delete_config_decorated(self):
        delete_config = alchemy_config_repository.AlchemyConfigRepository.delete_config.__func__
        self.assertTrue(
            call(delete_config) in self.require_db_deco_mock.call_args_list,
            '@require_db is missing'
        )

    @patch.object(alchemy_config_repository, 'Config', autospec=True)
    @patch('db.session', autospec=True)
    def test_delete_config(self, session_mock, alchemy_config_mock):
        session_mock.query.return_value = query_mock = MagicMock(spec=Query)
        query_mock.filter.return_value = filtered_query_mock = MagicMock(spec=Query)
        filtered_query_mock.one.return_value = alchemy_config_mock.return_value
        config_to_delete = MagicMock(spec=Config)

        self.alchemy_repository.delete_config(config_to_delete)

        session_mock.query.assert_called_once_with(alchemy_config_mock)
        query_mock.filter.assert_called_once()
        alchemy_config_mock.conf.__eq__.assert_called_once_with(config_to_delete.conf)
        filtered_query_mock.one.assert_called_once_with()
        session_mock.begin.assert_called_once_with()
        session_mock.delete.assert_called_once_with(alchemy_config_mock.return_value)
        session_mock.commit.assert_called_once_with()

    @patch('db.session', autospec=True)
    def test_delete_config_one_not_found_exception(self, session_mock):
        session_mock.query.return_value = query_mock = MagicMock(spec=Query)
        query_mock.filter.return_value = filtered_query_mock = MagicMock(spec=Query)
        filtered_query_mock.one.side_effect = NoResultFound

        self.assertRaises(
            alchemy_config_repository.ConfigNotFoundError,
            self.alchemy_repository.delete_config,
            MagicMock(spec=Config)
        )

    @patch('db.session', autospec=True)
    def test_delete_config_delete_exception(self, session_mock):
        expected_err_message = 'error'
        session_mock.delete.side_effect = Exception(expected_err_message)

        self.assertRaises(
            Exception,
            self.alchemy_repository.delete_config,
            MagicMock(spec=Config)
        )
        session_mock.rollback.assert_called_once_with()

    @patch('db.session', autospec=True)
    def test_delete_config_commit_exception(self, session_mock):
        session_mock.commit.side_effect = Exception()

        self.assertRaises(
            Exception,
            self.alchemy_repository.delete_config,
            MagicMock(spec=Config)
        )
        session_mock.rollback.assert_called_once_with()


if __name__ == '__main__':
    unittest.main()
