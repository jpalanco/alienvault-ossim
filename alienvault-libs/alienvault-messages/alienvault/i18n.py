#!/usr/bin/env python
import os
import locale
import gettext


class AlienvaultApps(object):
    """Alienvault API apps"""
    API = 1
    SERVER = 2
    LOGGER = 3
    AGENT = 4
    FORWARDER = 5
    DOCTOR = 6
    WEB = 7


class AlienvaultMessageLevel(object):
    """Error levels"""
    DEBUG = 1
    INFO = 2
    WARNING = 3
    ERROR = 4
    CRITICAL = 5


class AlienvaultMessageHandler(object):
    """This class implements a generic error handler with internationalization """
    # Error format:
    APP_NAME = None
    LOCALE_DIR = None
    LANGUAGES = []
    IS_SETUP = False
    CURRENT_LANGUAGE = None
    ERROR_MESSAGE_HASH = {}
    APP_CODE = 0

    @staticmethod
    def __get_languages():
        """Returns the list of language codes"""
        current_languages = os.environ.get('LANG', '').split(':')
        # By default we add English (US) language
        current_languages.extend(['en_US'])
        language_code = None
        try:
            language_code, encoding = locale.getdefaultlocale()
        except:
            pass

        if language_code is not None:
            current_languages.extend([language_code])
        return current_languages

    @staticmethod
    def __get_message_string(msg_code):
        """Returns the message string from a given message code"""
        try:
            msg = AlienvaultMessageHandler.CURRENT_LANGUAGE.gettext(AlienvaultMessageHandler.ERROR_MESSAGE_HASH.get(msg_code, ''))
        except:
            msg = AlienvaultMessageHandler.ERROR_MESSAGE_HASH.get(msg_code, '')
        return msg

    @staticmethod
    def setup(application_name, locale_dir, app_code, app_messages):
        """Install the error handler
        :param application_name: It's the application name. It's used to set the domain. 
        :param locale_dir: It's the folder where are located the .po files. Usually APP_FOLDER/i18n/
        :param app_messages: Your application messages
        :param app_code: Application code"""
        if application_name is None or application_name == "":
            return (False, 'Application name must be set')

        if locale_dir is None or locale_dir == "" or not os.path.exists(locale_dir):
            return (False, 'Locale dir doesn\'t exists')
        AlienvaultMessageHandler.ERROR_MESSAGE_HASH = app_messages
        AlienvaultMessageHandler.APP_NAME = application_name
        AlienvaultMessageHandler.LOCALE_DIR = locale_dir
        AlienvaultMessageHandler.LANGUAGES = AlienvaultMessageHandler.__get_languages()
        AlienvaultMessageHandler.APP_CODE = app_code

        gettext.install(True, localedir=None, unicode=True)
        current_language = 'en_US'
        for language in AlienvaultMessageHandler.LANGUAGES:
            if gettext.find(AlienvaultMessageHandler.APP_NAME, AlienvaultMessageHandler.LOCALE_DIR, languages=AlienvaultMessageHandler.LANGUAGES) is not None:
                current_language = language
        # Set the text domain
        gettext.textdomain(AlienvaultMessageHandler.APP_NAME)
        gettext.bind_textdomain_codeset(AlienvaultMessageHandler.APP_NAME, "UTF-8")
        AlienvaultMessageHandler.CURRENT_LANGUAGE = gettext.translation(AlienvaultMessageHandler.APP_NAME, AlienvaultMessageHandler.LOCALE_DIR,
                                                            languages=current_language, fallback=True)
        return (True, 'OK')

    @staticmethod
    def __build_error_code(level, msg_code):
        """Returns the error code in the alienvault format
        YYZZXXXXXX where:
        YY: Application code
        ZZ: Message Level
        XXXXXX: Message ID"""
        return "%02d%02d%06d" % (AlienvaultMessageHandler.APP_CODE, level, msg_code)

    @staticmethod
    def __get_message(level, msg_code, extra_info=None):
        """Returns an standarized message from a given code"""
        msg = "[%s] %s" % (AlienvaultMessageHandler.__build_error_code(level, msg_code),
                            AlienvaultMessageHandler.__get_message_string(msg_code))
        if extra_info is not None:
            msg += " %s" % str(extra_info)

        return msg
    @staticmethod
    def error(msg_code, extra_info=None):
        """Returns a standardized message from a given code"""
        return AlienvaultMessageHandler.__get_message(AlienvaultMessageLevel.ERROR,msg_code,extra_info)

    @staticmethod
    def critical(msg_code, extra_info=None):
        """Returns a standardized message from a given code"""
        return AlienvaultMessageHandler.__get_message(AlienvaultMessageLevel.CRITICAL,msg_code,extra_info)

    @staticmethod
    def warning(msg_code, extra_info=None):
        """Returns a standardized message from a given code"""
        return AlienvaultMessageHandler.__get_message(AlienvaultMessageLevel.WARNING,msg_code,extra_info)

    @staticmethod
    def info(msg_code, extra_info=None):
        """Returns a standardized message from a given code"""
        return AlienvaultMessageHandler.__get_message(AlienvaultMessageLevel.INFO,msg_code,extra_info)


    @staticmethod
    def debug(msg_code, extra_info=None):
        """Returns a standardized message from a given code"""
        return AlienvaultMessageHandler.__get_message(AlienvaultMessageLevel.DEBUG,msg_code,extra_info)
