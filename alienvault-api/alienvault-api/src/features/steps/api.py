"""Reasonably complete set of BDD steps for testing a REST API.

Some state is set-up and shared between steps using the context variable. It is
reset at the start of every scenario by
:func:`restapiblueprint.features.environment.before_scenario`

"""
from __future__ import print_function
import json
import StringIO
import random
import string
import tempfile
import shutil
from ConfigParser import ConfigParser
import uuid
import asyncore
import signal
from subprocess import call, Popen, PIPE
import time
from sqlalchemy.orm.exc import NoResultFound,MultipleResultsFound
import paramiko
import os
import behave
import db
from db.models.alienvault import Users
from db.models.alienvault import System
from avconfig.ossimsetupconfig import  AVOssimSetupConfigHandler
from db.models.alienvault import Sensor, Acl_Sensors
from celerymethods.celery_manager import   CeleryManager
from commom import make_request, dereference_step_parameters_and_data, resolve_table_vars
from apimethods.system.cache import flush_cache
from apimethods.utils import get_ip_str_from_bytes, get_bytes_from_uuid
from steps.smtpdebugger import SMTPDebugger
from nose.tools import assert_equal,assert_not_equal

@behave.given('I add POST data key "{var_key}","{var_data}"')
def add_post(context, var_key, var_data):
    context.urlparams[var_key] = var_data

@behave.when('I send a {request_type} request to url "{url}"')
@dereference_step_parameters_and_data
def get_request(context, request_type, url):
    c = make_request(context,url,request_type)


@behave.when('I send a {request_type} request to url stored in the variable "{var_url}"')
@dereference_step_parameters_and_data
def get_request_with_url_stored_in_context(context, request_type, var_url):
    c = make_request(context,context.alienvault[var_url],request_type)


@behave.then('The http status code must be "{code}"')
@dereference_step_parameters_and_data
def then_verify_status_code (context,code):
    assert_equal (int(code),context.resultcode)

@behave.given(u'I set url param "{key}" to variable "{key_vault}"')
def set_param(context,key,key_vault):
    context.urlparams[key] = context.alienvault.get(key_vault)


@behave.given('I set url param "{key}" to key "{key_vault}" from static vault')
def set_param(context,key,key_vault):
  context.urlparams[key] = context.internal_vault.get(key_vault)


@behave.given(u'I set url param "{key}" to string "{st}"')
def given_set_param_string (context,key,st):
    context.urlparams[key] = st


@behave.given('I set url param "{key}" to key "{key_vault}" from vault')
@dereference_step_parameters_and_data
def set_param(context,key,key_vault):
  context.urlparams[key] = context.alienvault.get(key_vault)

@behave.given(u'I set server to "{server_url}"')
@dereference_step_parameters_and_data
def set_server (context,server_url):
    context.alienvault['server'] = server_url

@behave.given(u'I set username to "{username}"')
@dereference_step_parameters_and_data
def set_username(context,username):
  context.username = username

@behave.given(u'I set password to "{password}"')
@dereference_step_parameters_and_data
def set_password (context,password):
  context.password = password


@behave.then('The returned JSON must be "{json_data}"')
@dereference_step_parameters_and_data
def then_compare_json (context,json_data):
  j1 = json.loads (context.result.getvalue())
  j2 = json.loads (json_data)
  assert_equal (j1,j2)

@behave.then('Store the cookies into vault with key "{key_vault}"')
@dereference_step_parameters_and_data
def the_store_cookies_from_request (context,key_vault):
  context.alienvault[key_vault] = context.cookies

@behave.given('I set cookies from key "{cookies}" from vault')
@dereference_step_parameters_and_data
def given_set_cookies_from_vault (context,cookies):
  context.cookies = context.alienvault[cookies]

@behave.given('I set username and password to ghost administrator')
@dereference_step_parameters_and_data
def given_ghost_admin (context):
  context.username = context.internal_vault['admin_user']
  context.password = context.internal_vault['admin_pass']

@behave.given('I log into the ossim API using "{url}"')
@dereference_step_parameters_and_data
def given_ossim_login (context,url):
    context.urlparams = {'username':context.username,'password':context.password}
    c = make_request(context, url,is_login=True)


@behave.given('I generate a random string with len "{len_string}" and store in vault key "{vault_key}"')
@dereference_step_parameters_and_data
def given_gen_rand_string (context,len_string,vault_key):
  context.alienvault[vault_key] = "".join([random.choice(string.ascii_letters + string.digits) for n in xrange(int(len_string))])


@behave.given('I clear the cookies')
@dereference_step_parameters_and_data
def given_clear_cookies (context):
    context.cookies = {}


@behave.given('I generate a non-existent username with len "{len_string}" and store in vault key "{vault_key}"')
@dereference_step_parameters_and_data
def given_random_username (context,len_string,vault_key):
    fend = True
    while fend:
        tempuser =  "".join([random.choice(string.ascii_letters + string.digits) for n in xrange(int(len_string))])
        try:
            result_set = db.session.query(Users).filter (Users.login == tempuser).one()
        except NoResultFound:
            context.alienvault[vault_key] = tempuser
            fend = False 


@behave.then('I print request result')
@dereference_step_parameters_and_data
def then_print_request(context):
    print("RESULT: %s" % context.result.getvalue())


@behave.then ('JSON response has key "{key}" and value equals to string "{s}"')
@dereference_step_parameters_and_data
def then_json_has_key_with_string_value(context,key,s):
    j = json.loads (context.result.getvalue())
    obj = j
    for path in key.split("."):
        assert obj.get(path) != None,"Bad key"
        obj = obj.get(path)

    assert str(obj) == str(s), "JSON %s:  Item [%s] = %s not found" % (context.result.getvalue(),key,s)

@behave.then ('JSON response has key "{key_path}"')
def then_json_has_key (context,key_path):
    """ 
    For the momemt, the key path doesn't support embebed "."
    """
    j = json.loads (context.result.getvalue())
    obj = j
    for path in key_path.split ("."):
        assert obj.get(path) != None, "JSON %s has no key %s" % (context.result.getvalue(),key_path)
        obj = obj.get(path)

@behave.given(u'I make url with paths and store it in variable "{var_name}"')
@resolve_table_vars
def when_make_url (context,var_name):
    assert hasattr(context,'table'),"This step need a table with url parts"
    assert hasattr(context,'resolved_table'),"Context.resovled_table missing"
    urlpath = []
    for row in context.resolved_table:
        urlpath.append(row[0])
    context.alienvault[var_name] = "/".join([str(x) for x in urlpath])


# TODO: Extract remote command execution (via popen) to a separate func in apimethods.utils
@behave.given(u'I get the interfaces for sensor for uuid stored in variable "{var_uuid}" and store in variable "{var_ifaces}"')
def given_get_interfaces (context, var_uuid,var_ifaces):
    #print context.alienvault[var_uuid]
    u = uuid.UUID(context.alienvault[var_uuid])
    tempdir= ""
    try:
        dbsensor = db.session.query(Sensor).filter (Sensor.id == u.bytes).one()
        ip_sensor = dbsensor.serialize['ip']
        # Create a tempdir
        tempdir =  tempfile.mkdtemp (suffix =".behave")
        # Get the private pass used in ssh to communicate with other sensors
        config = ConfigParser()
        assert config.read ("/etc/ansible/ansible.cfg")[0] == "/etc/ansible/ansible.cfg", "Can\'t load ansible.cfg file"
        sshkey = config.get("defaults","private_key_file")
        ssh = paramiko.SSHClient()
        #print "ip_ => " + str(ip_sensor)
        ssh = Popen(
            ["ssh", "-i", sshkey, "-l", "avapi", ip_sensor, "/sbin/ifconfig -s -a"],
            shell=False, # this protects you against most of the risk associated with piping commands to the shell
            stdout=PIPE,
            stderr=PIPE
        )
        (stdoutdata, stderrdata) = ssh.communicate()
        lines = filter(None, stdoutdata.splitlines())
        # The format is
        # Iface   MTU Met   RX-OK RX-ERR RX-DRP RX-OVR    TX-OK TX-ERR TX-DRP TX-OVR Flg
        # eth0       1500 0    339579      0      0 0         47558      0      0      0 BMPRU
        # lo        16436 0  1336360692      0      0 0      1336360692      0      0      0 LRU
        context.alienvault[var_ifaces] = ",".join([ x.split()[0].strip() for x in lines[1:] if x.split()[0].strip() != 'lo'])
    except NoResultFound:
        assert False,"Can't find sensor with uuid %s in database " % context.alienvault[var_uuid]
    except MultipleResultsFound, msg:
        assert False,"Multiples result for query. Internal database error uuid => %s" % var_uuid
    except OSError,msg:
        assert False,"I/O Error: %s" % str(msg)
    if tempdir != "":
        shutil.rmtree (tempdir)



@behave.then(u'I store the key "{var_key}" from result in variable "{var}"')
def then_store_var_from_result(context,var_key,var):
    j = json.loads (context.result.getvalue())
    v = j
    for key in var_key.split("."):
        v = v[key]
    context.alienvault[var]= v


@behave.then(u'I verify the job with job_id in variable "{var_jobid}" has type "{var_task_type}" after wait "{var_wait}" seconds')
def then_verify_job_with_var_id(context, var_jobid,var_task_type,var_wait):
    jid = context.alienvault.get (var_jobid)
    assert jid != None, "Bad job_id %s" % var_jobid
    # This is a celery.events.Event
    #wait 
    time.sleep(float(var_wait))
    ev = CeleryManager.get_job_status (jid)
    assert ev !=None, "No status for task %s in celery_job"
    assert ev['type'] == var_task_type, "Bad type  for task id: %s  Real: %s Must be:%s" % (var_jobid,ev['type'],var_task_type)


@behave.given(u'I generate a random uuid and store in variable "{var_uuid}"')
def given_generate_random_uuid (context,var_uuid):
    context.alienvault[var_uuid] = str(uuid.uuid1())


@behave.then(u'The ossim_setup.conf key "{var_setup_key}" for sensor variable "{var_sensor}" is equal to variable "{var_value}"')
def then_verify_sensor_key (context,var_setup_key,var_sensor,var_value):
    tempdir =  tempfile.mkdtemp (suffix =".behave")
    u = uuid.UUID(context.alienvault[var_sensor])
    try:
        dbsensor = db.session.query(Sensor).filter (Sensor.id == u.bytes).one()
        ip_sensor = dbsensor.ip
        config = ConfigParser()
        assert config.read ("/etc/ansible/ansible.cfg")[0] == "/etc/ansible/ansible.cfg", "Can\'t load ansible.cfg file"
        sshkey = config.get("defaults","private_key_file")
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect (ip_sensor,username="avapi",key_filename=sshkey)
        t = ssh.get_transport()
        sftp = paramiko.SFTPClient.from_transport(t)
        tempconf = os.path.join (tempdir,"ossim_setup.conf")
        sftp.get ("/etc/ossim/ossim_setup.conf",tempconf)
        ssh.close() 
        cfg =  AVOssimSetupConfigHandler (filename=tempconf,logfile="/dev/null")
        if var_setup_key == "[sensor]/sensor_ctx":
            sctx  = cfg.get_sensor_ctx()
            assert sctx == context.alienvault[var_value]
        else:
            assert False,"Unknow ket %s" % var_setup_key

    except OSError,msg:
        assert False,"I/O Error: %s" % str(msg)
    except NoResultFound:
        assert False,"Can't find sensor with uuid %s in database " % var_uuid
    except MultipleResultsFound, msg:
        assert False,"Multiples result for query. Internal database error uuid => %s" % var_uuid

    if tempdir != "":
        shutil.rmtree (tempdir)

@behave.given(u'I set the API server to "{var_server}"')
def given_set_api_server (context,var_server):
    context.api_server = var_server

@behave.given(u'I select a random ethernet interface for system with uuid in variable "{var_uuid}" and store in variable "{var_iface}"')
def given_select_iface_ethernet(context,var_uuid,var_iface):
    #print ">>>>" + context.alienvault[var_uuid]
    context.execute_steps ("Given I get the interfaces for system for uuid stored in variable \"%s\" and store in variable \"%s\"" % (var_uuid,var_iface))
    v = context.alienvault[var_iface].split (",")
    eth = [x for x in v if x != 'lo']
    context.alienvault[var_iface]= random.choice(eth)

# TODO: Extract remote command execution (via popen) to a separate func in apimethods.utils
@behave.given('I get the interfaces for system for uuid stored in variable "{var_uuid}" and store in variable "{var_ifaces}"')
def given_get_system_ifaces (context,var_uuid,var_ifaces):
    uuid = get_bytes_from_uuid(context.alienvault[var_uuid])
    tempdir= ""
    try:
        dbsensor = db.session.query(System).filter(System.id  == uuid).one()
        ip_system = dbsensor.serialize.get("admin_ip")
        # Create a tempdir
        tempdir =  tempfile.mkdtemp (suffix =".behave")
        # Get the private pass used in ssh to communicate with other sensors
        config = ConfigParser()
        assert config.read ("/etc/ansible/ansible.cfg")[0] == "/etc/ansible/ansible.cfg", "Can\'t load ansible.cfg file"
        sshkey = config.get("defaults","private_key_file")
        ssh = Popen(
            ["ssh", "-i", sshkey, "-l", "avapi", ip_system, "/sbin/ifconfig -s -a"],
            shell=False,
            stdout=PIPE,
            stderr=PIPE
        )
        (stdoutdata, stderrdata) = ssh.communicate()
        lines = filter(None, stdoutdata.splitlines())
        # The format is
        # Iface   MTU Met   RX-OK RX-ERR RX-DRP RX-OVR    TX-OK TX-ERR TX-DRP TX-OVR Flg
        # eth0       1500 0    339579      0      0 0         47558      0      0      0 BMPRU
        # lo        16436 0  1336360692      0      0 0      1336360692      0      0      0 LRU
        context.alienvault[var_ifaces] = ",".join([ x.split()[0].strip() for x in lines[1:] if x.split()[0].strip() != 'lo'])
    except NoResultFound:
        assert False,"Can't find sensor with uuid %s in database " % context.alienvault[var_uuid]
    except MultipleResultsFound, msg:
        assert False,"Multiples result for query. Internal database error uuid => %s" % var_uuid
    except OSError,msg:
        assert False,"I/O Error: %s" % str(msg)
    if tempdir != "":
        shutil.rmtree (tempdir)

 
@behave.given(u'I store the sensor_ctx in variable "{var_ctx}" for sensor with uuid in variable "{var_uuid}"')
def give_store_sensor_ctx(context,var_ctx,var_uuid):
    u = uuid.UUID(context.alienvault[var_uuid])
    dbsensor = db.session.query(Sensor).filter (Sensor.id == u.bytes).one()
    # Get from system table the 
    current_sensor = db.session.query(System).filter (System.admin_ip == dbsensor.ip).one()
    acl_sensor = db.session.query(Acl_Sensors).filter (Acl_Sensors.sensor_id == current_sensor.sensor_id).one() 
    context.alienvault[var_ctx] = acl_sensor.serialize['entity_id']


@behave.then(u'I print status of job with id in variable "{var_jobid}"')
def then_get_jobid_result (context,var_jobid):
    ev = CeleryManager.get_job_status (context.alienvault[var_jobid])


@behave.given(u'I create a ghost SMTP server on port "{var_port}"')
def given_create_ghost_smtp (context,var_port):
    # Using fork() to do that. THe asyncore module doesn't handle threads very well ...
    pid = os.fork()
    if pid == 0: # Child:
        smtp = SMTPDebugger(('localhost',int(var_port)),None)
        asyncore.loop()
    else:
        context.smtp_pid = pid


@behave.then(u'I destroy the ghost SMTP server')
def then_destroy_smtp (context):
    if hasattr(context,'smtp_pid'):
        os.kill (context.smtp_pid,signal.SIGTERM)
        delattr (context,'smtp_pid')

@behave.given(u'I set variable "{var_var}" to string "{var_string}"')
def when_set_variable(context,var_var,var_string):
    context.alienvault[var_var] = var_string

@behave.given(u'I generate a random uuid and store in var "{var_uuid}"')
def given_generate_randon_uuid(context,var_uuid):
    context.alienvault[var_uuid] = str(uuid.uuid4())

@behave.given(u'Select key "{var_key}" from dict "{var_dict}" and store in var "{var_value}"')
def given_select_key_dict (context,var_key,var_dict,var_value):
    context.alienvault[var_value] = context.alienvault[var_dict][var_key]

@behave.given(u'I stop celery')
def given_stop_celery(context):
    ret = call(["/usr/share/python/alienvault-api/scripts/venv_celerybeat.sh","stop"])
    assert_equal (0, ret, "Can't stop celery beat")

@behave.given(u'I start celery')
def give_start_celery(context):
    ret = call(["/usr/share/python/alienvault-api/scripts/venv_celerybeat.sh","start"]) 
    assert_equal (0, ret, "Can't start celery beat")
    time.sleep(10)

@behave.given(u'I log in the server "{var_server_ip}" using a ghost administrator')
def given_log_using_ghost_administrator(context,var_server_ip):
    context.api_server = var_server_ip
    context.execute_steps(u'Given I set username and password to ghost administrator')
    context.execute_steps(u"Given I log into the ossim API using \"https://%s:40011/av/api/1.0/auth/login\"" % var_server_ip)

@behave.then(u'I flush API cache')
def then_flush_api_cache(context):
    flush_cache()    
# vim:ts=4:expandtab
