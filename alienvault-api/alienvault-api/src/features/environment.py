import time
from pwd import getpwuid
import requests
from subprocess import call
import random
import hashlib
import uuid
import string
import StringIO
import tempfile
import shutil
import signal
import datetime
from tempfile import NamedTemporaryFile

import os
from os import getuid

from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler

CONFIG_FILE = "/etc/ossim/ossim_setup.conf"
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)
from feature_utils import remotely_backup_file, \
    remotely_restore_file, \
    remotely_create_sample_yml_file, \
    remotely_remove_file, \
    set_plugin_add_hosts, \
    set_plugin_delete_hosts, \
    touch_file

from scenario.setup import prepare_scenario,restore_scenario
from utils.dbutils import backup_database_tables,restore_database_tables
import db
from db.models.alienvault import Users

def before_step(contex, step):
    pass

def before_feature(context, feature):
    celerybeat_stop = ["Status operations","Host operations"]
    # Start celery beat
    if feature.name in celerybeat_stop:
        time.sleep(30)
        ret = call(["/usr/share/python/alienvault-api/scripts/venv_celerybeat.sh","stop"]) 
        #assert_equal (0, ret, "Can't stop celery beat")

    if feature.name == "Status operations":
        # Gen a tempfile
        f =  NamedTemporaryFile (prefix="/tmp/bk.",delete=False)
        context.tempfile = f.name
        f.close()
        backup_database_tables(context, context.tempfile,"current_status","status_message","monitor_data")

    if feature.name == "Sensor detector operations":
        # Create an empty file if the configuration doesn't exist!
        if not touch_file(ossim_setup.get_general_admin_ip(), "/etc/ossim/agent/config.yml"):
            print ('Can\'t touch!r')
            raise KeyboardInterrupt()
        if not set_plugin_add_hosts():
            print ("Can't add the hosts to test")
            raise KeyboardInterrupt()
        if not remotely_backup_file(ossim_setup.get_general_admin_ip(),"/etc/ossim/agent/config.yml", "/tmp/config.yml.bddbk"):
            print ("Can't backup")
            raise KeyboardInterrupt()
        if not remotely_create_sample_yml_file(ossim_setup.get_general_admin_ip()):
            #print "Can't create sample yml file"
            raise KeyboardInterrupt()


def after_feature(context, feature):
    celerybeat_start = ["Status operations","Host operations"]
    # Start celery beat
    if feature.name in celerybeat_start:
        #ret = call(["/usr/share/python/alienvault-api/scripts/venv_celerybeat.sh","start"]) 
        #assert_equal (0, ret, "Can't start celery beat")
        # Wait for start
        time.sleep(10)
    
    if feature.name == "Status operations":
        restore_database_tables (context,context.tempfile) 
        os.remove (context.tempfile)
    if feature.name == "Sensor detector operations":
        if not set_plugin_delete_hosts():
            print ("Can't delete hosts")
            raise KeyboardInterrupt()

        if not remotely_restore_file(ossim_setup.get_general_admin_ip(), "/tmp/config.yml.bddbk","/etc/ossim/agent/config.yml"):
            print ("Something wrong happen while restoring the yml file")
            raise KeyboardInterrupt()

        files_to_remove = ["/tmp/config.yml.bddbk", "/tmp/config_test.yml"]
        for f in files_to_remove:
            if not remotely_remove_file(ossim_setup.get_general_admin_ip(), f):
                print ("Can't remove the file %s" % f)
                raise KeyboardInterrupt()


def before_all(context):
    context.config.setup_logging()
    context.alienvault = {}
    context.internal_vault = {}
    context.result = StringIO.StringIO()
    context.resultheader = StringIO.StringIO()
    context.cookies = {}

    try:
        context.tempdir = tempfile.mkdtemp (suffix =".behave")
    except:
        assert False,"Can't create tempdir"
    # We must run under "avapi" user
    assert getpwuid(getuid())[0] == 'avapi','This tests must run with user id == avapi'
    # We must run under the virtual enviroment with all the needed modules
    assert os.environ.get('VIRTUAL_ENV') is not None,'This tests must run under the alienvault-api-core virtual enviroment'
    assert os.environ['VIRTUAL_ENV'] == '/usr/share/python/alienvault-api-core','This tests must run under the api_core virtual enviroment'
    # Verify we're under a ossim enviroment
    # file test: I have /etc/ossim/ossim_setup.conf
    assert os.path.exists ('/etc/ossim/ossim_setup.conf')

    # Verify each API is UP and running. Make a call using libcurl to 
    # the auth API
    response = requests.get(
        "https://127.0.0.1:40011/av/api/1.0/auth/login",
        verify=False,
        params={"username": "abc", "password": "abc"},
        headers={"Accept": "application/json"}
    )
    assert response.json().get("status") == 'error', 'Bad return from API'
    assert response.status_code == 401, 'Bad return from API'

    # Insert a admin user:
    cp = db.session.query(Users).filter (Users.login == 'admin').one()
    u = Users()
    for k in cp.serialize.keys():
      u.__dict__[k] = cp.__dict__[k]
    # Generate a random string of 8 chars
    user = "".join([random.choice(string.ascii_letters + string.digits) for n in xrange(8)])
    upass = "".join([random.choice(string.ascii_letters + string.digits) for n in xrange(8)])
    u.login = user

    u.av_pass = hashlib.md5 (upass).hexdigest()
    u.last_logon_try = datetime.datetime.now()
    context.internal_vault['admin_user'] = user
    context.internal_vault['admin_pass'] = upass
    u.uuid = uuid.uuid4().get_bytes()
    db.session.begin()
    db.session.add (u)
    db.session.commit ()
    context.urlparams = {}
    # 
    #s = Alienvault_Sensor.query.all()
    #context.alienvault['sensors_uuid'] = [k['id'] for k in [i.serialize for i in s]]
    # We need the cooke jar path file 
    # Increment the db connection
    #conn = db.session.connection()
    #r = conn.execute("SET SESSION wait_timeout = 28800;")
    # I'm going to use the _mysql module to get a copy of current_status && status_message
    context.dbip = ossim_setup.get_database_db_ip()
    context.dbuser = ossim_setup.get_database_user()
    context.dbpass = ossim_setup.get_database_pass()


def before_scenario(context, scenario):
    #logging.info("Before Scenario %s " % scenario)
    ## Seed empty HTTP headers so steps do not need to check and create.
    #context.headers = {}

    ## Seed empty Jinja2 template data so steps do not need to check and create.
    #context.template_data = {}

    ## Default repeat attempt counts and delay for polling GET.
    #context.n_attempts = 20
    #context.pause_between_attempts = 0.1

    ## No authentication by default.
    #context.auth = ('admin','alien4ever')
    prepare_scenario(scenario.name)

    # Clear the context
    context.urlparams = {}
    context.result.truncate(0)
    context.alienvault = {}
    if hasattr(context,'api_server'):
        delattr(context,'api_server')

def after_scenario(context,scenario):
    restore_scenario(scenario.name)

def after_all(context):
    # Restore database backup
    #rt = extract_file(BK_NAME+".gz","/tmp/original.sql")
    #if not rt:
    #    return
    #rt = restore_db("/tmp/original.sql")
    #if not rt:
    #    return
    #remove_file(BK_NAME+".gz")
    #remove_file("/tmp/original.sql")
    #db.session.begin()
    db.session.query(Users).filter (Users.login == context.internal_vault['admin_user']).delete()
    #db.session.commit()
    if hasattr(context,'tempdir'):
        if os.path.exists ( context.tempdir ):
            shutil.rmtree (context.tempdir)
    if hasattr(context,'smtp_pid'):
        os.kill (context.smtp_pid,signal.SIGTERM)
        delattr (context,'smtp_pid')
