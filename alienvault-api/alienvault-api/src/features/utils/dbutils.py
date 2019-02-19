import os
import logging
from subprocess import call
from nose.tools import assert_equal,assert_not_equal
from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler

CONFIG_FILE = "/etc/ossim/ossim_setup.conf"
BK_NAME = "/tmp/tests_backups.sql"
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)

def backup_db(dbname):
    rt = True

    cmd = "mysqldump --insert-ignore --no-autocommit " \
          "--skip-triggers --single-transaction  " \
          "--hex-blob --add-drop-database " \
          "--databases %s -h %s -u %s -p%s -c -f >%s" % (
              dbname,
              ossim_setup.get_database_db_ip(),
              ossim_setup.get_database_user(),
              ossim_setup.get_database_pass(),
              BK_NAME)
    try:
        os.system(cmd)
    except Exception, e:
        logging.error("Backup Error: %s" % str(e))
        rt = False
    return rt, BK_NAME


def restore_db(sqlfilename):
    rt = True
    cmd = "mysql -h %s -u %s -p%s < %s" % (
              ossim_setup.get_database_db_ip(),
              ossim_setup.get_database_user(),
              ossim_setup.get_database_pass(),
              sqlfilename)
    try:
        os.system(cmd)
    except Exception, e:
        logging.error("Restore Error: %s" % str(e))
        rt = False
    return rt, sqlfilename


def backup_database_tables (context,filename,*args):
    # Backup each table to filename
    with open(filename,"w") as f:
        ret = call(["/usr/bin/mysqldump",
                "-u%s" % context.dbuser,
                "-p%s" % context.dbpass,
               "-h%s" % context.dbip,
               "--hex-blob",
               "-r%s" % filename,
                "alienvault_api"] +  list(args))

    assert_equal (0, ret, "Can't dump tables")

def restore_database_tables (context,filename):
        ret = call("/usr/bin/mysql " + " " +  \
                "-u%s" % context.dbuser + " " + \
                "-p%s" % context.dbpass + " " + \
                "-h%s" % context.dbip + " "+ \
                "alienvault_api < %s" % filename,shell=True)

        assert_equal (0,ret, "Can't restore database")