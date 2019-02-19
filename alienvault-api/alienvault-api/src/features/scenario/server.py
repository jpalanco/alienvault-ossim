from feature_utils import remotely_backup_file,remotely_restore_file,remotely_create_sample_yml_file, \
    remotely_remove_file, set_plugin_add_hosts, set_plugin_delete_hosts, touch_file,remotely_copy_file, \
    remotely_create_sample_client_keys_file
from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
from db.methods.system import get_system_id_from_local
CONFIG_FILE = "/etc/ossim/ossim_setup.conf"
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)

raw_nfsen_good="""
$BASEDIR = "/usr";
$BINDIR = "${BASEDIR}/bin";
$LIBEXECDIR = "${BASEDIR}/libexec/nfsen";
$CONFDIR = "/etc/nfsen";
$HTMLDIR = "/var/www/nfsen/";
$DOCDIR = "${HTMLDIR}/doc";
$VARDIR = "/var/nfsen";
$PROFILESTATDIR = "/var/nfsen/profiles";
$PROFILEDATADIR = "/var/cache/nfdump/flows/";
$BACKEND_PLUGINDIR = "${LIBEXECDIR}/plugins";
$FRONTEND_PLUGINDIR = "${HTMLDIR}/plugins";
$PREFIX = "/usr/bin";
$USER = "www-data";
$WWWUSER = "www-data";
$WWWGROUP = "www-data";
$BUFFLEN = 200000;
$SUBDIRLAYOUT = "7";
$ZIPcollected = "0";
$ZIPprofiles = "1";
$PROFILERS = "6";
$DISKLIMIT = "98";
$PROFILERS = "6";
%sources = (
        'FBE2341E0B9C39C5BBD0DEDC7EEC38DC'    => { 'port' => '555', 'col' => '#0000ff', 'type' => 'netflow' },
);
$low_water = 90;
$syslog_facility = 'local3';
@plugins = (
    # profile    # module
    # [ '*',     'demoplugin' ],
);
%PluginConf = (
        # For plugin demoplugin
        demoplugin => {
                # scalar
                param2 => 42,
                # hash
                param1 => { 'key' => 'value' },
        },
        # for plugin otherplugin
        otherplugin => [ 
                # array
                'mary had a little lamb' 
        ],
);
$MAIL_FROM   = 'your@from.example.net';
$SMTP_SERVER = 'localhost';
$MAIL_BODY       = q{ 
Alert '@alert@' triggered at timeslot @timeslot@
};
1;
"""

raw_nfsen_bad="""
$BASEDIR = "/usr";
$BINDIR = "${BASEDIR}/bin";
$LIBEXECDIR = "${BASEDIR}/libexec/nfsen";
$CONFDIR = "/etc/nfsen";
$HTMLDIR = "/var/www/nfsen/";
$DOCDIR = "${HTMLDIR}/doc";
$VARDIR = "/var/nfsen";
$PROFILESTATDIR = "/var/nfsen/profiles";
$PROFILEDATADIR = "/var/cache/nfdump/flows/";
$BACKEND_PLUGINDIR = "${LIBEXECDIR}/plugins";
$FRONTEND_PLUGINDIR = "${HTMLDIR}/plugins";
$PREFIX = "/usr/bin";
$USER = "www-data";
$WWWUSER = "www-data";
$WWWGROUP = "www-data";
$BUFFLEN = 200000;
$SUBDIRLAYOUT = "7";
$ZIPcollected = "0";
$ZIPprofiles = "1";
$PROFILERS = "6";
$DISKLIMIT = "98";
$PROFILERS = "6";
%sources = (
        FBE2341E0B9C39C5BBD0DEDC7EEC38DC'    => { 'port' => '555', 'col' => '#0000ff', 'type' => 'netflow' },
);
$low_water = 90;
$syslog_facility = 'local3';
@plugins = (
    # profile    # module
    # [ '*',     'demoplugin' ],
);
%PluginConf = (
        # For plugin demoplugin
        demoplugin => {
                # scalar
                param2 => 42,
                # hash
                param1 => { 'key' => 'value' },
        },
        # for plugin otherplugin
        otherplugin => [ 
                # array
                'mary had a little lamb' 
        ],
);
$MAIL_FROM   = 'your@from.example.net';
$SMTP_SERVER = 'localhost';
$MAIL_BODY       = q{ 
Alert '@alert@' triggered at timeslot @timeslot@
};
1;
"""
def prepare_nfsen_scenario1():
    remotely_backup_file(ossim_setup.get_general_admin_ip(),"/etc/nfsen/nfsen.conf","/etc/nfsen/nfsen.conf.bk")
    f = open("/tmp/nfsen.conf", 'w')
    f.write(raw_nfsen_good)
    f.close()

    if not remotely_copy_file(ossim_setup.get_general_admin_ip(),"/tmp/nfsen.conf", "/etc/nfsen/nsfen.conf"):
        print "Cannot write the nfsen.conf file"
        raise KeyboardInterrupt()


def restore_nfsen_scenario1():
    remotely_restore_file(ossim_setup.get_general_admin_ip(), "/etc/nfsen/nfsen.conf.bk","/etc/nfsen/nfsen.conf")


def prepare_nfsen_scenario2():
    remotely_backup_file(ossim_setup.get_general_admin_ip(),"/etc/nfsen/nfsen.conf","/etc/nfsen/nfsen.conf.bk")
    f = open("/tmp/nfsen_bad.conf", 'w')
    f.write(raw_nfsen_bad)
    f.close()
    if not remotely_copy_file(ossim_setup.get_general_admin_ip(),"/tmp/nfsen_bad.conf", "/etc/nfsen/nfsen.conf"):
        print "Cannot write the nfsen.conf file"
        raise KeyboardInterrupt()

def restore_nfsen_scenario2():
    remotely_restore_file(ossim_setup.get_general_admin_ip(), "/etc/nfsen/nfsen.conf.bk","/etc/nfsen/nfsen.conf")

