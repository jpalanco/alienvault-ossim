from abc import ABCMeta, abstractmethod


class AbstractContactPersonRepository(object):
    __metaclass__ = ABCMeta

    def __init__(self, contact_person_constructor):
        self._contact_person_constructor = contact_person_constructor

    @abstractmethod
    def get_contact_person(self):
        pass
