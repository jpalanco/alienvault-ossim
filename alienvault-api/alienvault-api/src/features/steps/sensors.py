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
import db
from db.models.alienvault import Users
from db.models.alienvault import Sensor, System
from sqlalchemy.orm.exc import NoResultFound, MultipleResultsFound
from behave.log_capture import capture
import uuid
from apimethods.utils import  get_ip_str_from_bytes
import random
import sys
from avconfig.ossimsetupconfig import  AVOssimSetupConfigHandler
from apimethods.utils import get_ip_bin_from_str, get_ip_str_from_bytes, get_bytes_from_uuid, get_uuid_string_from_bytes


@behave.then ('JSON response key "{key_path}" match ossim sensors table')
def then_json_sensors(context,key_path):
    sensors = json.loads(context.result.getvalue())
    for path in key_path.split("."):
        sensors = sensors.get(path)
        assert sensors != None, "Key %s object not found " % key_path
    for s_data, value in sensors.items():
        print (s_data)
        s_uuid = sensors[s_data]['sensor_id']
        u = uuid.UUID(s_uuid)
        result = db.session.query(Sensor).filter (Sensor.id == u.bytes).all()
        for r in result:
            dp = r.serialize
            assert dp['id'] == s_uuid, "Bad id in API response" # Redundant check
            assert  dp['ip'] == value['admin_ip'], "Bad admin_ip in API response"
            assert dp['name'] == value['hostname'],"Bad name  in API response"
            # Verify the sensor_id
            try:
                system_info = db.session.query(System).filter(
                    System.admin_ip == get_ip_bin_from_str(value['admin_ip'])
                ).one()
                sensor_uuid = system_info.serialize['sensor_id']
            except NoResultFound:
                assert False,"Can't find admin_ip from API response in database"
            except Exception, msg: 
                assert False, "Exception " + str(msg)
            assert sensor_uuid == value['sensor_id'],"Bad system_id in API response"
@behave.given(u'I select a random uuid for sensor and store in variable "{var_name}"')
def given_select_sensor_uuid (context,var_name):
     result =  db.session.query(Sensor).all()
     sensor = random.choice (result)
     context.alienvault[var_name] = sensor.serialize['id']

@behave.then(u'JSON response interfaces are in ossim_setup.conf')
def then_json_sensor_interfaces (context):
    interfaces = json.loads(context.result.getvalue())
    ifaces = interfaces['data']['interfaces']
    setup = AVOssimSetupConfigHandler (logfile="/dev/null")
    if_setup =  setup.get_sensor_interfaces_list()
    #Compare
    assert_equal(set(if_setup).issubset (set (ifaces)), True,"The API list %s not equals to the file list %s" % (str(ifaces),str(if_setup)))


#Verify the sensor properties of a sensor
@behave.then(u'JSON response properties are equal to sensor with uuid in variable "{var_uuid}"')
def then_json_sensor_uuid (context,var_uuid):
    sensor = json.loads (context.result.getvalue())
    u = uuid.UUID(context.alienvault[var_uuid])
    try:
        dbsensor = db.session.query(Sensor).filter (Sensor.id == u.bytes).one().serialize
        assert dbsensor['ip'] == sensor['data']['sensor']['ip'], "API response != from db values"
        assert dbsensor['tzone'] == sensor['data']['sensor']['tzone'], "API response != from db values: tzone"
        assert dbsensor['id'] == sensor['data']['sensor']['id'],"API response != from db values: id"
        assert dbsensor['name'] == sensor['data']['sensor']['name'],"API response != from db values: name"
        assert dbsensor['port'] == sensor['data']['sensor']['port'],"API response != from db values: port"
        assert dbsensor['priority'] == sensor['data']['sensor']['priority'],"API response != from db values: priority"


        
    except NoResultFound:
        assert False,"Can't find sensor with uuid %s in database " % var_uuid
    except MultipleResultsFound, msg:
        assert False,"Multiples result for query. Internal database error uuid => %s" % var_uuid

@behave.given('I select a group of interfaces from variable "{var_src}" and store in variable "{var_dst}"')
def given_select_iface_group (context,var_src,var_dst):
    ifaces = context.alienvault[var_src].split(',')
    context.alienvault[var_dst] = ",".join(random.sample (ifaces,random.randint (1,len(ifaces))))

# Verify the networks


@behave.then(u'The sensor networks are equal to string "{st_network}"')
def then_sensor_network_verify(context,st_network):
    # I need to get 
    url = "https://127.0.0.1:40011/av/api/1.0/sensor/" + context.alienvault['s_uuid']+"/network"
    j = context.execute_steps(unicode("When I send a GET request to url \"%s\"" % url))
    print (j)

@behave.given(u'I create a ossec agent in the sensor with id "{var_id}" in variable "{var_uuid}"')
def given_create_ossec_agent(context, var_id, var_uuid):
    u = uuid.UUID(context.alienvault[var_uuid])
    # Add a agent
    # I need to use here some function to add and agent
    pass
# vim:ts=4:expandtab
