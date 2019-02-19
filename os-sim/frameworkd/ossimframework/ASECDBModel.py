# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

from sqlalchemy import create_engine, Table, Column, MetaData, ForeignKey, func, and_
from sqlalchemy.orm import sessionmaker, relationship
from sqlalchemy.types import INT
from sqlalchemy.dialects.mysql import ENUM, VARCHAR, TIMESTAMP, BINARY, VARBINARY, TINYINT, FLOAT, TEXT, SMALLINT,MEDIUMINT,DATETIME
from sqlalchemy.interfaces import PoolListener
import sqlalchemy, _mysql_exceptions

from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.ext.serializer import loads, dumps

from uuid import UUID
from threading import Lock

import traceback
import sys
import socket
import struct
import copy
import time

from datetime import datetime
# Local imports
from Logger import Logger

logger = Logger.logger

# Play with this value to adjust timeouts
MAX_RETRIES = 3

# Make SQLAlchemy table mapping declarative
Base = declarative_base()



'''
Suggestion class.
Mapped to asec.suggestions
'''
class AsecDb_Suggestion(Base):
    __tablename__ = 'suggestions'
    __table_args__= {
        'mysql_engine':'InnoDB',
        'mysql_charset':'utf8'
        }
    id=          Column(MEDIUMINT, primary_key=True)
    suggestion_group_id =   Column(BINARY(16),nullable=False)
    filename      = Column(VARCHAR(255),nullable=False)
    location     = Column(VARCHAR(255),nullable=False)
    datetime =  Column(DATETIME,default= datetime.now)

    def __repr__(self):
        return "<Suggestions('%s','%s','%s','%s')>" % (self.id,str(UUID(bytes=self.suggestion_group_id)),self.filename,self.location)

class AsecDb_Suggestion_pattern(Base):
    __tablename__ = 'suggestion_pattern'
    __table_args__= {
        'mysql_engine':'InnoDB',
        'mysql_charset':'utf8'
        }
    id=          Column(MEDIUMINT, primary_key=True)
    suggestion_group_id =   Column(BINARY(16),nullable=False)
    pattern_json = Column (TEXT,nullable=False)

    def __repr__(self):
        return "<SuggestionPattern('%s','%s','%s')>" % (self.id,str(UUID(bytes=self.suggestion_group_id)),self.pattern_json)
    
class AsecDb_AlarmCoincidence(Base):
    __tablename__='alarm_coincidence'
    __table_args__= {              
        'mysql_engine':'InnoDB',
        'mysql_charset':'utf8'
        }
    id = Column(MEDIUMINT, primary_key=True)
    sensor_id =   Column(BINARY(16),nullable=False)
    sample_log= Column(TEXT)
    data = Column(TEXT)
    datetime =  Column(DATETIME,default= datetime.now())
  
    def __repr__ (self):
        return "<AlarmCoincidence('%s', '%s','%s')>" % (self.id,self.log_id,data)


class AsecDb_Notification(Base):
    __tablename__='notification'
    __table_args__= {              
        'mysql_engine':'InnoDB',
        'mysql_charset':'utf8'
        }
    id = Column(MEDIUMINT, primary_key=True)
    sensor_id =   Column(BINARY(16),nullable=False)
    plugin_id= Column(MEDIUMINT,nullable=False)
    rule_name= Column(VARCHAR(45) ,nullable=False)
    log_file = Column(VARCHAR(45) ,nullable=False)
    datetime =  Column(DATETIME,default= datetime.now())
  
    def __repr__ (self):
        return "<Notifications('%s','%s','%s','%s','%s')>" % (self.id,str(UUID(bytes=self.sensor_id)),self.plugin_id,self.rule_name)

'''
Class Listener
Manages connections from our connection pool, checking if they're actually alive.
'''
class Listener (sqlalchemy.interfaces.PoolListener):
  def __init__(self):
    self.retried = False
  def checkout (self, dbapi_con, con_record, con_proxy):
    try:
      dbapi_con.cursor().execute('select now()')
    except _mysql_exceptions.OperationalError:
      if self.retried:
        self.retried = False
        raise
      self.retried = True
      raise sqlalchemy.exc.DisconnectionError

'''
Class ASECModel
Manages ASEC model
'''
class ASECModel:
    __db_name = None
    __db_user = None
    __db_pass = None
  
    # Connect to database (call only once)
    def connect (self, db_host, db_name, db_user, db_pass):
        self.__db_host = db_host
        self.__db_name = db_name
        self.__db_user = db_user
        self.__db_pass = db_pass
  
        delay = 0
  
        # Try to connect 10 times
        for i in range(1, MAX_RETRIES + 1):
            time.sleep(delay)
            try:
                self.__engine = create_engine ('mysql+mysqldb://%s:%s@%s/%s' % (self.__db_user, self.__db_pass, self.__db_host, self.__db_name), echo=False, listeners = [Listener()], pool_size=10)
  
                # Create our sessionmaker class.
                Base.metadata.create_all (self.__engine)
                self.__SessionMaker = sessionmaker(bind=self.__engine)
                self.__session = self.__SessionMaker ()
                break
            except Exception, str:
                delay = i * 5
                logger.info ("Couldn't connect to ASEC DB, trying again in %d seconds" % delay)
  
        if i == MAX_RETRIES:
            logger.error ("Cannot connect to database, exiting...")
            return
        else:
            logger.info ("Connected to database")
  
        # Create locks to preven race conditions.
        self.__lock = Lock ()

    def set_sample_log(self,obj):
        return self.__set_data(obj)


    def set_alarm_coincidence(self,obj):
        return self.__set_data(obj)


    def __set_data(self,obj):
        obj_id = 0
        tries = 0
        while tries < MAX_RETRIES:
            if isinstance(obj,AsecDb_AlarmCoincidence) or \
               isinstance(obj,AsecDb_Notification) or \
               isinstance(obj,AsecDb_Suggestion) or \
               isinstance(obj,AsecDb_Suggestion_pattern):
                try:
                    self.__lock.acquire()
                    merged_data = self.__session.merge(obj)
                    self.__session.flush()
                    obj_id = obj.id
                    self.__session.commit()
                except Exception,msg:
                    self.__session.rollback ()
                    self.__lock.release ()
                    tries += 1
                    time.sleep(1)
                    continue
                else:
                    logger.debug ('Commited log')
                    self.__lock.release()
                    break
            else:
                logger.error ('Type of object is not AsecDb_SampleLog: %s' % type(obj))
        if tries >= MAX_RETRIES:
            logger.error ('Cannot commit new samplelog ')
            raise
        return obj_id


    def set_notification(self,obj):
        return self.__set_data(obj)


    def set_suggestion(self,obj):
        return self.__set_data(obj)

    def set_suggestion_pattern(self,obj):
        return self.__set_data(obj)


    def get_suggestion(self, suggestion_id):
        """Retrieves the suggestion with the suggestion group id passed as input param
        @param suggestion_id: The suggestion to look for.
        """
        tries = 0
        suggestion = None
        self.__lock.acquire ()
        while tries < MAX_RETRIES:
            try:
                suggestion = self.__session.query(AsecDb_Suggestion).filter(AsecDb_Suggestion.suggestion_group_id==UUID(suggestion_id).bytes).one()
                tries = MAX_RETRIES
            except _mysql_exceptions.OperationalError, (code, msg):
                self.__session.rollback ()
                self.__lock.release ()
                logger.error ("Unrecoverable error connecting to database: %s" % msg)
            except Exception, msg:
                self.__session.rollback ()
                tries += 1
                time.sleep (1)
                continue
        self.__lock.release ()
        return suggestion


    def get_suggestions_patterns(self,suggestion_id):
        tries = 0
        patterns = []
        self.__lock.acquire ()
        while tries < MAX_RETRIES:
            try:
                patterns = self.__session.query(AsecDb_Suggestion_pattern).\
                                            filter(AsecDb_Suggestion_pattern.suggestion_group_id==UUID(suggestion_id).bytes).all()
                tries = MAX_RETRIES
            except _mysql_exceptions.OperationalError, (code, msg):
                self.__session.rollback ()
                self.__lock.release ()
                logger.error ("Unrecoverable error connecting to database: %s" % msg)
            except Exception, msg:
                logger.error("Error: :%s"% str(msg))
                self.__session.rollback ()
                tries += 1
                time.sleep (1)
                continue
        self.__lock.release ()
        return patterns


    def delete_suggestion(self,suggestion_id):

        tries = 0
        self.__lock.acquire ()
        logger.info("Suggestion_id_ %s" % suggestion_id)
        while tries < MAX_RETRIES:
            try:
                sid_uuid = UUID(suggestion_id)
                self.__session.query(AsecDb_Suggestion_pattern).\
                                            filter(AsecDb_Suggestion_pattern.suggestion_group_id==sid_uuid.bytes).delete()
                self.__session.query(AsecDb_Suggestion).filter(AsecDb_Suggestion.suggestion_group_id==sid_uuid.bytes).delete()
                self.__session.flush()
                self.__session.commit()
                tries = MAX_RETRIES
            except _mysql_exceptions.OperationalError, (code, msg):
                self.__session.rollback ()
                self.__lock.release ()
                logger.error ("Unrecoverable error connecting to database: %s" % msg)
            except Exception, msg:
                logger.error("Error: %s"% str(msg))
                logger.error(traceback.format_exc())
                self.__session.rollback ()
                tries += 1
                time.sleep (1)
                continue
        self.__lock.release ()


