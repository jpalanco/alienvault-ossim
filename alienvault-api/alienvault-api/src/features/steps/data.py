#
# Steps to verify the current_status database
#
from nose.tools import assert_equal,assert_not_equal
import behave
import decorator
import jinja2
import jpath
import json
import os
import purl
import requests
import time
import logging
import ast
import StringIO
import urllib
import random
import string
import re
from dateutil import parser as dateparser
from sqlalchemy.orm.exc import NoResultFound,MultipleResultsFound
from behave.log_capture import capture
import paramiko
from ConfigParser import ConfigParser
from db.models.alienvault_api import Current_Status, Status_Message, Monitor_Data
#from api.lib.utils import accepted_values, accepted_types
from apimethods.utils import get_ip_bin_from_str, get_ip_str_from_bytes, get_bytes_from_uuid, get_uuid_string_from_bytes
import uuid
from sqlalchemy.sql import func
from db.models.alienvault import System

from db.models.alienvault import  Host,Net,Sensor,Users,Net_Cidrs,Server
from sqlalchemy.sql import func
from sqlalchemy import and_,or_
import db
from time import sleep


@behave.then('Verify the current_status result')
def then_verify_current_status(context):
    # Read all the messaages and verify that the status messages and compare with the database info
    q = db.session.query(Current_Status).all()
    j = json.loads(context.result.getvalue())
    # By default, we return ONLY 50
    assert_equal (len(q),int(j['data']['total']))
    
    # Verify the json
    for m in j['data']['messages']:
        #Verify if the message is in the databas
        try:
            q = db.session.query(Current_Status).filter(Current_Status.message_id == uuid.UUID(m['message_id']).bytes, Current_Status.component_id == uuid.UUID(m['component_id']).bytes).one()
        
        except  NoResultFound:
            assert "No result for message_id == %s" % str(m['message_id'])
        except  MultipleResultsFound:
            assert "Multiple results for message_id = %s" % str(m['message_id'])
        # Verify the fields
        data = q.serialize
        print (data)
        print (m)
        assert_equal(
            data['message_description']
                .replace(
                    "TIMESTAMP",
                    data["creation_time"].strftime("%Y-%m-%d %H:%M:%S") + " UTC"
                )
                .decode("utf-8"),
            m['message_description']
        )
        assert_equal (data['message_level'],m['message_level'])
        assert_equal (data['component_id'],m['component_id'])
        assert_equal(data['component_ip'], m['component_ip'])
        mip = m['component_ip'].split(',')
        for ip in data['component_ip'].split(','):
            assert ip in mip,"Bad ip in message response"  
        assert_equal (data['component_name'],m['component_name'])
        assert_equal(
            data['creation_time'].strftime("%Y-%m-%d %H:%M:%S"),
            dateparser.parse(m["creation_time"]).strftime("%Y-%m-%d %H:%M:%S")
        )
        assert_equal (data['viewed'], m['viewed'])
        assert_equal (data['component_type'], m['component_type'])

@behave.given('Select a random asset of type "{var_type}" and store component_id in var "{var_uuid}"')
def when_select_random_asset (context,var_type,var_uuid):
    # First, select net,host,system,server,user,sensor
    value = None
    while  value is None:
        asset = var_type
        try:
            value = {
                'user': str(uuid.UUID(db.session.query(Users).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'net' : str(uuid.UUID(db.session.query(Net).order_by(func.rand()).limit(1).one().serialize['id'])),
                'host': str(uuid.UUID(db.session.query(Host).order_by(func.rand()).limit(1).one().serialize['id'])),
                'sensor': str(uuid.UUID(db.session.query(Sensor).order_by(func.rand()).limit(1).one().serialize['id'])),
                'system': str(uuid.UUID(db.session.query(System).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'server': str(uuid.UUID(db.session.query(Server).order_by(func.rand()).limit(1).one().serialize['id'])),
    
          }.get(asset)
        except NoResultFound:
            assert None,"No asset of type '%s' in system" % asset
        # Verify if the hosts is in the current_status
        q = db.session.query(Current_Status).filter(Current_Status.component_id == get_bytes_from_uuid (value)).all()
        if len(q) == 0:
            value = None
            
    context.alienvault[var_uuid] = value


@behave.given('Select a random asset and store component_id in var "{var_uuid}"')
def when_select_random_asset (context,var_uuid):
    # First, select net,host,system,server,user,sensor
    l = ['user','net','host','system','server','sensor']
    value = None
    while  value is None:
        assert l != [], "I can't select any component_to make the call"
        asset = random.choice (l)
        try:
            value = {
                'user': str(uuid.UUID(db.session.query(Users).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'net' : str(uuid.UUID(db.session.query(Net).order_by(func.rand()).limit(1).one().serialize['id'])),
                'host': str(uuid.UUID(db.session.query(Host).order_by(func.rand()).limit(1).one().serialize['id'])),
                'sensor': str(uuid.UUID(db.session.query(Sensor).order_by(func.rand()).limit(1).one().serialize['id'])),
                'system': str(uuid.UUID(db.session.query(System).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'server': str(uuid.UUID(db.session.query(Server).order_by(func.rand()).limit(1).one().serialize['id'])),
    
          }.get(asset)
        except NoResultFound:
            l.remove (asset)
    context.alienvault[var_uuid] = value

@behave.given('Select a random entry from current_status and store entry in var "{var}"')
def given_select_random_current_status(context,var):
    try:
        q = db.session.query(Current_Status).order_by(func.rand()).limit(1).one()
        print (q.serialize['message_id'])
        context.alienvault[var] = q.serialize
    except NoResultFound:
        assert None,"No entries in current_local"

@behave.given(u'Select a random entry from status_message and store entry in var "{var}"')
def given_select_random_status_message(context,var):
     try:
        q = db.session.query(Status_Message).order_by(func.rand()).limit(1).one()
        context.alienvault[var] = q.serialize
     except NoResultFound:
        assert None,"No entries in status_message"


@behave.given(u'I store asset type with component_id "{var_uuid}" in var "{var_type}"')
def given_store_asset_type(context,var_uuid,var_type):
    try:
        db.session.expire_all()
        q = db.session.query(Current_Status).filter ( Current_Status.component_id == get_bytes_from_uuid(context.alienvault[var_uuid])
            
        ).limit(1).one()
        context.alienvault[var_type] = q.serialize['component_type']
    except NoResultFound:
        assert None,"No entry with uuid '%s'" % context.alienvault[var_uuid]

@behave.given(u'I select a random message and store id in  var "{var_mid}"')
def given_select_random_message(context,var_mid):
    try:
        db.session.expire_all()
        q = db.session.query(Status_Message).order_by(func.rand()).limit(1).one().serialize
        print (q['id'])
        context.alienvault[var_mid] = q['id']
    except NoResultFound:
        assert None,"No entries in status message" 


@behave.given(u'I create or update a current_status entry with component_id "{var_cid}" message id "{var_mid}" asset type "{var_type}" and viewed "{viewed}"')
def given_create_or_update_current_status_entry(context, var_cid, var_mid, var_type, viewed):
    #Using merging http://docs.sqlalchemy.org/en/latest/orm/session.html#merging to create or update    
    # Check if we must create or delete
    print (context.alienvault[var_cid])
    print (context.alienvault[var_mid])
    try:
        entry = db.session.query(Current_Status).filter(and_(  Current_Status.component_id == get_bytes_from_uuid(context.alienvault[var_cid]),
             Current_Status.message_id ==  context.alienvault[var_mid])).one()
        entry.component_type =  context.alienvault[var_type]
        entry.viewed = viewed
    except  NoResultFound:
        # Create a new entry 
        entry = Current_Status()
        entry.id = uuid.uuid4().bytes
        entry.message_id = get_bytes_from_uuid(context.alienvault[var_mid])
        entry.component_id = get_bytes_from_uuid(context.alienvault[var_cid])
        entry.component_type = context.alienvault[var_type]
        entry.viewed = viewed
    try:
        db.session.begin()
        new_obj = db.session.merge(entry)
        db.session.commit()
    except Exception,e:
        print ("Error: %s" %str(e))
        db.session.rollback()
    

@behave.given(u'I store the id of current_status entry with component_id "{comp_id_key}" message id "{msg_id_key}" asset type "{type_key}" in var "{stat_id_key}"')
def given_store_current_status_entry_id(context, comp_id_key, msg_id_key, type_key, stat_id_key):
    entry = None
    try:
        entry = db.session.query(Current_Status).filter(
            and_(
                Current_Status.component_id == get_bytes_from_uuid(context.alienvault[comp_id_key]),
                Current_Status.message_id == get_bytes_from_uuid(context.alienvault[msg_id_key]),
                Current_Status.component_type == context.alienvault[type_key]
            )
        ).one()
    except NoResultFound:
        assert None, "Current_Status entry was not found"

    context.alienvault[stat_id_key] = entry.serialize['id'] if entry is not None else ""


@behave.then(u'I verify that all results has level equals to "{var_st}"')
def then_verify_level(context,var_st):  
    j = json.loads(context.result.getvalue())
    for entry in j['data']['messages']:
        assert_equal (entry['message_level'], var_st)

@behave.then(u'I verify that all results has level equals to string list "{var_list}"')
def then_verify_list_level(context,var_list):
    j = json.loads(context.result.getvalue())
    sts = var_list.split(",")
    for entry in j['data']['messages']:
        assert entry['message_level'] in sts,"Level %s not in list" % entry['message_level']


    

        
    

        

        
@behave.given(u'Select a random host in current_status and component_id in var "{var_uuid}"')
def given_select_cid_from_current_status (context,var_uuid):
    try:
        q = db.session.query(Current_Status).filter(Current_Status.component_type == 'host').all()
        assert_not_equal (0,len(q),"We need at least a entry in current_status table")
        cid = random.choice(q).serialize['component_id']
        context.alienvault[var_uuid] = cid
        
    except NoResultFound:
        assert None,"No entries associated with hosts in current_status"

def _return_total_status ():
    total_users = len(db.session.query(Users).all())
    total_net = len (db.session.query(Net).all())
    total_host = len(db.session.query(Host).all())
    total_sensor = len (db.session.query(Sensor).all())
    total_server = len (db.session.query(Server).all())
    total_system = len(db.session.query(System).all())
    total_msg = len(db.session.query(Status_Message).all())
    return (total_users + total_net + total_host+ total_sensor + total_server + total_system)* total_msg
    

@behave.given(u'I generate "{var_n}" current_status entries') # The host and net must exists in each table')
def given_gen_status_message(context,var_n):
    db.session.query(Current_Status).delete() # Delete all current_status entries
    total = 0
    total_msg =  _return_total_status()
    assert int(var_n) <= total_msg, "We don't have enought messages and asset to generate %d current_status entries " % int(var_n)
    while total < int(var_n):
        ctype = random.choice (['net','host','user','sensor','server','system'])
        
        entry = Current_Status()
        try:
            c_id = {
                'user': str(uuid.UUID(db.session.query(Users).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'net' : str(uuid.UUID(db.session.query(Net).order_by(func.rand()).limit(1).one().serialize['id'])),
                'host': str(uuid.UUID(db.session.query(Host).order_by(func.rand()).limit(1).one().serialize['id'])),
                'sensor': str(uuid.UUID(db.session.query(Sensor).order_by(func.rand()).limit(1).one().serialize['id'])),
                'system': str(uuid.UUID(db.session.query(System).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'server':  str(uuid.UUID(db.session.query(Server).order_by(func.rand()).limit(1).one().serialize['id'])),
    
          }.get(ctype)
        except NoResultFound:
           assert None,"Can't load a asset of type '%s'" % ctype
       # We have now the component_id
       # Select a msg_id
        try:
           msg_entry = db.session.query(Status_Message).order_by(func.rand()).limit(1).one().serialize
        except NoResultFound:
           assert None,"Can't load a message entry"
        entry.id = get_bytes_from_uuid (str(uuid.uuid1()))
        entry.message_id = get_bytes_from_uuid(msg_entry['id'])
        entry.component_id =  get_bytes_from_uuid (c_id)
        entry.component_type = ctype
        entry.viewed = random.choice([True, False])
        entry.additional_info = """{"msg_id": "Random generate message"}"""
        # check
        q = db.session.query(Current_Status).filter(and_( Current_Status.message_id == entry.message_id, Current_Status.component_id == entry.component_id)).all()
        if len(q) > 0:
            continue
        db.session.begin() 
        db.session.merge(entry)
        db.session.commit()
        total = total + 1


@behave.given(u'I generate "{var_n}" current_status entries of type "{var_type}"') # The host and net must exists in each table')
def given_gen_status_message(context,var_n,var_type):
    db.session.query(Current_Status).delete() # Delete all current_status entries
    ctypes = var_type.split(",")
    total = 0
    while (total < 100):
        ctype = random.choice (ctypes)
        
        assert (ctype in ['net','host','user','sensor','server','system']) == True, "Unknown type '%s'" % ctype
        entry = Current_Status()
        # Select a entry 
        try:
            c_id = {
                'user': str(uuid.UUID(db.session.query(Users).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'net' : str(uuid.UUID(db.session.query(Net).order_by(func.rand()).limit(1).one().serialize['id'])),
                'host': str(uuid.UUID(db.session.query(Host).order_by(func.rand()).limit(1).one().serialize['id'])),
                'sensor': str(uuid.UUID(db.session.query(Sensor).order_by(func.rand()).limit(1).one().serialize['id'])),
                'system': str(uuid.UUID(db.session.query(System).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'server':  str(uuid.UUID(db.session.query(Server).order_by(func.rand()).limit(1).one().serialize['id']))
    
          }.get(ctype)
        except NoResultFound:
           assert None,"Can't load a asset of type '%s'" % ctype
       # We have now the component_id
       # Select a msg_id
        try:
           msg_entry = db.session.query(Status_Message).order_by(func.rand()).limit(1).one().serialize
        except NoResultFound:
           assert None,"Can't load a message entry"
        entry.id = get_bytes_from_uuid (str(uuid.uuid1()))
        entry.message_id = get_bytes_from_uuid(msg_entry['id'])
        entry.component_id =  get_bytes_from_uuid (c_id)
      
        entry.component_type = ctype
        entry.viewed = False
        entry.suppressed = False 
        entry.additional_info = """{"id": "Random generate message"}"""
        db.session.begin() 
        db.session.merge(entry)
        db.session.commit()
        total = total + 1


@behave.given(u'I generate "{var_n}" monitor_data entries of type "{var_type}"')
def given_gen_monitor_data(context,var_n,var_type):
    db.session.query(Monitor_Data).delete() # Delete all current_status entries
    ctypes = var_type.split(",")
    for x in range (0,int(var_n)):
        ctype = random.choice (ctypes)
        
        assert (ctype in ['net','host','user','sensor','server','system']) == True, "Unknown type '%s'" % ctype
        entry = Monitor_Data()
        # Select a entry 
        try:
            c_id = {
                'user': str(uuid.UUID(db.session.query(Users).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'net' : str(uuid.UUID(db.session.query(Net).order_by(func.rand()).limit(1).one().serialize['id'])),
                'host': str(uuid.UUID(db.session.query(Host).order_by(func.rand()).limit(1).one().serialize['id'])),
                'sensor': str(uuid.UUID(db.session.query(Host).order_by(func.rand()).limit(1).one().serialize['id'])),
                'system': str(uuid.UUID(db.session.query(System).order_by(func.rand()).limit(1).one().serialize['uuid'])),
                'server':  str(uuid.UUID(db.session.query(Server).order_by(func.rand()).limit(1).one().serialize['id'])),

    
          }.get(ctype)
        except NoResultFound:
           assert None,"Can't load a asset of type '%s'" % ctype
       # We have now the component_id
       # Select a msg_id
        entry.component_id =  get_bytes_from_uuid (c_id)
        entry.monitor_id = 1
        entry.component_type = "system"
        sleep(1)
        entry.data = "{\"msg\": \"Texto de prueba\"}"
        db.session.begin() 
        db.session.merge(entry)
        db.session.commit()

        
@behave.then(u'I verify that no current_status with "{var_uuid}" in database')
def then_current_status_verify (context,var_uuid):
    q =  db.session.query(Current_Status).filter ( Current_Status.component_id ==  get_bytes_from_uuid (context.alienvault[var_uuid])).all()
    assert_equal (0, len(q),"Not all current_status messages deleted")


@behave.then(u'I verify that no monitor_data with "{var_uuid}" in database')
def then_monitor_data_verify (context,var_uuid):
    q = db.session.query(Monitor_Data).filter (Monitor_Data.component_id ==  get_bytes_from_uuid (context.alienvault[var_uuid])).all()
    assert_equal (0, len(q),"Not all monitor_data messages deleted")

@behave.then(u'I clean the status_message database')
def then_current_status(context):
    db.session.query(Current_Status).delete()

        
@behave.given(u'I clean the status_message database')
def then_current_status(context):
    db.session.query(Current_Status).delete()
@behave.then(u'All responses must have component_id equals to var "{var_cid}" and levels equals to "{var_info}"')
def then_very_component_id_and_levels (context,var_cid,var_info):
    levels = var_info.split(",")
    j = json.loads(context.result.getvalue())
    for msg in j['data']['messages']:
        assert msg['component_id'] == context.alienvault[var_cid],"Bad component_id in response from API: %s" % msg['component_id']
        assert (msg['message_level'] in levels) == True, "Bad level %s" % msg['message_level']

@behave.then(u'All responses must be of type "{var_type}"')
def then_verify_response_types (context,var_type):
    j = json.loads(context.result.getvalue())
    for msg in j['data']['messages']:
        assert_equal (msg['component_type'],var_type)


@behave.then(u'All responses must have component_id equals to var "{var_cid}"')
def then_response_verify_cid (context,var_cid):
    j = json.loads(context.result.getvalue())
    for msg in j['data']['messages']:
        assert_equal (msg['component_id'], context.alienvault[var_cid])
@behave.then(u'All responses must have level in "{var_level}"')
def then_response_verify_level(context,var_level):
    j = json.loads(context.result.getvalue())
    levels = var_level.split(',')
    for msg in j['data']['messages']:
        assert (msg['message_level'] in levels) == True,"Bad level %s" % msg['level']
        
    

   

    


 
    
        

 
        

    
