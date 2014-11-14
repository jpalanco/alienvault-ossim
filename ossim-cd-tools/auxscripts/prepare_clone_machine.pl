#!/usr/bin/perl
#
#
#
use v5.10;

use Config::Tiny;
use Switch;
#use DBI;
#use MIME::Base64;
#use Frontier::Daemon;

#use avlog;
#use avparseconf;
use lib '/usr/share/alienvault-center/lib';
use AV::ConfigParser;
use Avtools;

# Program version (DO NOT TOUCH THIS)
my $VER = "0.0.2 release 1";
my $web = "http://www.alienvault.com";
my $config_file    = "/etc/ossim/ossim_setup.conf";
my $dialog_active = 0;
my $dialog_bin = "/usr/bin/dialog";
my $nfsenconf = "/etc/nfsen/nfsen.conf";

# function
sub main();             # main program body
sub parse_argv();       # requires defined %config
sub config_check();     # requires defined %configcolor;  # colours configuration
sub help();             # help(string: $helpCmd);
sub console_log($);
sub verbose_log($);
sub debug_log($);
sub warning($);
sub error($);
sub file_log($);
sub trim($);
sub readconfig ($);		# read config for daemon mode
sub sensor_references();

#
# MAIN
#

sub main() {
	### main
	#

	$percent=10; dp("Parsing gloval variables");
	my %config = AV::ConfigParser::current_config();
	my @profiles_arr;


	if ($config{'profile'} eq "all-in-one")
	{
		@profiles_arr = ("Server","Database","Framework","Sensor");
	}
	else
	{
		@profiles_arr = split( /,\s*/, $config{'profile'} );
	}

	foreach my $profile (@profiles_arr)
	{

		given($profile)
		{
			when ( m/Database/ )  { $profile_database=1;  }
			when ( m/Server/ )    { $profile_server=1;    }
			when ( m/Framework/ ) { $profile_framework=1; }
			when ( m/Sensor/ )    { $profile_sensor=1;    }
		}

	}

	dp("Exec common task");
	common_task();

    # Get bbdd access
    my $dbhost = `grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
    my $dbuser = `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
    my $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);

	if ( $profile_server == 1 || $profile_framework == 1 || $profile_database == 1 )
	{

        # debug dump
        #system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault config server sensor system > /tmp/database.1.sql 2>> /tmp/err1");

        #rebuild, but do not complete
        if ( $profile_database == 1 )
        {
			verbose_log("Exec Rebuild database");
			dp("Dropping Alienvault Center database");
			system("echo \"TRUNCATE TABLE avcenter.current_local;TRUNCATE TABLE avcenter.current_remote;\"| ossim-db mysql 2>> /tmp/err");

			verbose_log("Database Profile: Set lock create database");

			system("zcat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_data_config.sql.gz | ossim-db alienvault 2>> /tmp/err");
			system("zcat /usr/share/doc/ossim-mysql/contrib/02-create_alienvault_data_data.sql.gz | ossim-db alienvault 2>> /tmp/err");
			system("echo \"CALL alarm_taxonomy_populate();\" | ossim-db alienvault 2>> /tmp/err");

            # debug dump
			#system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault config server sensor system > /tmp/database.2.sql 2>> /tmp/err1");
			#system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers avcenter current_local > /tmp/center.0.sql 2>> /tmp/err1");
        }

        # stop frameworkd
        if ( -x "/etc/init.d/ossim-framework" )
        {
   			dp("Stopping Frameworkd");
			console_log("Stoping frameworkd");
			system("/etc/init.d/ossim-framework stop");
        }

        # remove last config
        if ( -f "/etc/ossim/ossim_setup.conf_last" )
        {
			dp("Removing ossim_setup.conf_last");
			console_log("Remove ossim_setup.conf_last");
			unlink("/etc/ossim/ossim_setup.conf_last");
        }

        # force db build at the end of the process
		verbose_log("Stopping cron.");
		$command = "/etc/init.d/cron stop";
		debug_log("$command");
		system($command);

		verbose_log("Removing 1 cron file related to avcenter if it exists");
        if ( -f "/etc/cron.d/av_system_cache" )
        {
			$command = "rm -f /etc/cron.d/av_system_cache";
			debug_log("$command");
			system($command);
        }
        else
        {
		    verbose_log("OK, not found, skipping removal of /etc/cron.d/av_system_cache");
        }


        # S profiles
        if ( $profile_framework == 1 && !$profile_database)
        {
            # compliance aditional data, if any
            if ( -s "/var/lib/dpkg/info/ossim-compliance.postinst" )
            {
                verbose_log("Reconfiguring ossim-compliance");
                system("dpkg-reconfigure ossim-compliance");
            }
            # wizard aditional data
            if ( -f "/var/lib/dpkg/info/alienvault-wizard.postinst" )
            {
                verbose_log("Reconfiguring alienvault-wizard");
                system("dpkg-reconfigure alienvault-wizard");
            }
        }

        if ( !$profile_database )
        {
            # stop processes
            $command = "test -x /etc/init.d/monit && /etc/init.d/monit stop; test -x /etc/init.d/ossim-agent && /etc/init.d/ossim-agent stop; test -x /etc/init.d/ossim-server && /etc/init.d/ossim-server stop";
			debug_log("$command");
			system($command);
        }

        # new main password
        if ( $profile_database == 1 && $profile_server == 1 && $profile_framework == 1 )
        {

			my $pass=`</dev/urandom tr -dc A-Za-z0-9| (head -c \$1 > /dev/null 2>&1 || head -c 10)`;
			system("sed -i 's:^pass=.*:pass=$pass:' /etc/ossim/ossim_setup.conf");

			system("echo \"update mysql.user set password=PASSWORD('$pass') where User='$dbuser';flush privileges;\" | mysql -h $dbhost -p$dbpass -u $dbuser 2>> /tmp/err");

            $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);

        }

		if ( $profile_sensor == 1 )
		{
			# clean nfsen.conf
		    my $command = "perl -npe \"s/[A-F0-9]{32}/ossim/i\" $nfsenconf > /tmp/nfsen.conf;cp /tmp/nfsen.conf $nfsenconf";
		    debug_log("$command");
		    system($command);
		}

            # Needed for Database profile
            system("echo \"replace into config values('ossim_server_version','pro');\" | ossim-db alienvault 2>> /tmp/err");

        # main reconfig
		verbose_log("Exec Alienvault Reconfig");
		$command = "alienvault-reconfig -c -v -d > /tmp/second_reconfig 2>&1";
		debug_log("$command");
		system($command);

        # debug dump
        #system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault config server sensor system > /tmp/database.3.sql 2>> /tmp/err1");
        #system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers avcenter current_local > /tmp/center.1.sql 2>> /tmp/err1");

	}

	if ( $profile_sensor == 1 )
	{
	    # stop agent and delete file
		system("/etc/init.d/ossim-agent stop") if ( -f "/etc/init.d/ossim-agent" );
		unlink("/etc/ossim/agent/agentuuid.dat");
	}

	dp("Finished Cloning (please be patient)");

    # launch latest actions
	verbose_log("Adding avcenter related tasks to cron");
	$command = "bash /var/lib/dpkg/info/ossim-cd-tools.postinst configure";
	debug_log("$command");
	system($command);

	verbose_log("Starting cron");
	$command = "/etc/init.d/cron restart";
	debug_log("$command");
	system($command);


    #if ( $profile_server == 1 || $profile_framework == 1 || $profile_database == 1 ) {
    if ( $profile_database == 1 )
    {
		system("echo \"DELETE FROM alienvault.config WHERE conf='exp_date'\" | ossim-db 2>> /tmp/err");
    }

    # restart center to regenerate avcenter bbdd
    my $sts = `ps ax | grep centerd | grep -v grep | wc -l`;
    if ($sts < 1)
    {
        $command = "/etc/init.d/alienvault-center restart";
        debug_log("$command");
        system($command) ;
        sleep 10;
    }

    # debug dump
    #system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault config server sensor system > /tmp/database.4.sql 2>> /tmp/err1");
    #system("mysqldump -h $dbhost -p$dbpass -u $dbuser -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers avcenter current_local > /tmp/center.2.sql 2>> /tmp/err1");

    # Rebuild certificates and keys
    system("rm -rf /var/ossim/ssl/*");
    if ( $profile_server == 1 )
    {
	    system("bash /var/lib/dpkg/info/alienvault-crypto.postinst configure");
    }

    $UUID=`/usr/bin/alienvault-system-id`;

    system("/etc/init.d/alienvault-api stop");
    system( "sed -i 's:^dir[ \t]*=.*:dir             = /var/ossim/ssl/${UUID}:' /etc/ssl/openssl.cnf");

	# Set the certificate path for ansible
    system( "sed -i 's:.*:${UUID}:' /etc/ansible/keys");

	# fix nfsen
	#
	if ( $profile_framework == 1 )
	{

		if ( -f "$nfsenconf" )
		{
            {
			    package Nfsen::Config;
                require "$nfsenconf";
            }
            no warnings;
			}

		for my $flowname ( keys %Nfsen::Config::sources )
		{

			$flowname =~ s/\n//g;
			$source_common = qq*    => { 'port' => '$Nfsen::Config::sources{$flowname}{'port'}', 'col' => '$Nfsen::Config::sources{$flowname}{'col'}', 'type' => '$Nfsen::Config::sources{$flowname}{'type'}' }*;
			debug_log("Framework Profile: source_common: $source_common");

			$source_orig = qq*'$flowname'$source_common*;
			debug_log("Framework Profile: $source_orig");

			$source_mod = qq*'ossim'$source_common*;
			debug_log("Framework Profile: $source_mod");

            my $command = qq{sed -i "s:$source_orig:$source_mod:" $nfsenconf};
            debug_log($command);
            system($command);

		}
        system("nfsen reconfig");

    }

    # Api start again
	system("/etc/init.d/alienvault-api start");
        if ( $profile_framework == 1 || $profile_database == 1 )
	{
	    sensor_references();
        }

	$percent=100;
	dp("Configuration Finished (Thank you for your patience ;) )");

}

#
# END MAIN
#

sub sensor_references()
{
    verbose_log("Latest sensor checks");

    my $conn    = Avtools::get_database();

    my $query = "SELECT COUNT(id) as total from alienvault.sensor";
    my $sth   = $conn->prepare($query);
    $sth->execute();
    my ($cnt) = $sth->fetchrow_array();

    if ($cnt <= 1)
    {
        my $query = "SELECT HEX(id) from alienvault.sensor";
        my $sth   = $conn->prepare($query);
        $sth->execute();
        my ($sensor_id) = $sth->fetchrow_array();

        my $command = "CALL _orphans_of_sensor('$sensor_id');";
        verbose_log($command);
        debug_log($command);
        Avtools::execute_query_without_return($command);
    }

}


sub common_task()
{

	console_log("Stoping monit");dp("Stoping monit");
	system("/etc/init.d/monit stop");

	console_log("Clearing alienvault-center");dp("Clearing alienvault-center");
	system("/etc/init.d/alienvault-center stop") if ( -f "/etc/init.d/alienvault-center" );
	#unlink("/etc/alienvault-center/alienvault-center-uuid") if ( -f "/etc/alienvault-center/alienvault-center-uuid" );

	console_log("clearing  ssh");dp("clearing ssh");
	system("rm -rf /etc/ssh/ssh_host_*");
	system("dpkg-reconfigure openssh-server");
}

# Parse and check arguments
parse_argv();
if (config_check())
{
    main();
}
else
{
    error("Configuration check failed.");
}

sub parse_argv()
{

	# no arguments?
	#if ($#ARGV == -1) {
#               print "use --help or -h\n"
	#}

	# scan command line arguments
	foreach (@ARGV)
	{
		my @parms = split(/=/);
		#my @parms = split(//);
		if (($parms[0] eq "--help") || ($parms[0] eq "-h"))           { help(); }
		elsif (($parms[0] eq "--console-log") || ($parms[0] eq "-c")) { $CONSOLELOG = 1; }
		elsif (($parms[0] eq "--verbose") || ($parms[0] eq "-v"))     { $VERBOSELOG = 1; $CONSOLELOG = 1; }
		elsif (($parms[0] eq "--debug") || ($parms[0] eq "-d"))       { $DEBUGLOG = 1; }
		elsif (($parms[0] eq "--quiet") || ($parms[0] eq "-q"))       { $dialog_active = 0; }
		elsif (($parms[0] eq "--add_vpnnode") || ($parms[0] eq "-avpn"))
		{
			if ($parms[1] eq "") { error("ip needed (example: --add_vpnnode=192.168.1.100");}
			$config{'add_vpnnode'} = "$parms[1]";
			$dialog_active = 0;
		}
		else
		{
			error("Unknown argument $_ from command line.");
		}
		undef @parms;
	}
}

sub config_check()
{

	# Checks configuration validity
	my $noerror = 1;

	if ( $CONSOLELOG == 1 || $DEBUGLOG == 1 || $VERBOSELOG == 1 ) {
		console_log("Console log mode on");
		$dialog_active = 0;
	}

	if ((exists $config{'add_sensor'}) && (! exists $config{'add_sensor_name'})) {
		error("necesitas especificar un nombre");
		$noerror = 1;
	}

	return $noerror;
}

sub help()
{
	print <<EOF;

$program_name $VER Help ($^O, perl $])

Usage examples:
$program_name [options]

Command line options:

	--help (or -h)
	  Displays this help message.

	--console-log (or -c)
	  Enable logging of messages to console.

	--verbose (or -v)
	  Enable verbose.

	--debug (or -d)
	  Enable debug mode. (insane)

	--quiet (or -q)
	  quiet mode.

For more info, please visit $web

EOF
	exit;
}

sub console_log($)
{
	if ($CONSOLELOG)
	{
		my $TIME = localtime(time());
		my $LOG = shift;
		print "$TIME $LOG\n";
		undef $LOG;
	}
}
sub error($)
{
	# Error output
	my $LOG = shift;
	die "ERROR: $LOG Exiting.\n";
}
sub warning($)
{
	# Warning output
	if ($WARNING)
	{
		my $LOG = shift;
		print STDERR "WARNING: $LOG\n";
		undef $LOG;
	}
}
sub file_log($)
{
	if ($FILELOG)
	{
		my $TIME2 = time();
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

		$mon += 1;
		my $montwo = sprintf("%02d", $mon);
		my $yeartwo = sprintf("%02d", $year % 100);
		my $mdaytwo = sprintf("%02d", $mday);
		my $hourtwo = sprintf("%02d", $hour);
		my $mintwo = sprintf("%02d", $min);
		my $sectwo = sprintf("%02d", $msec);
		my $TIME = "$montwo/$mdaytwo/$yeartwo;$hour:$min:$sec;$TIME2";

		open LOGFILE, ">> $log_dir/ossim-reconfig.log" or die "Error open log file $!";

		my $LOG = shift;
		print LOGFILE "$TIME;$LOG\n";
		undef $LOG;
		close(LOGFILE);
	}
}
sub verbose_log($)
{
	if ($VERBOSELOG)
	{
		my $TIME = localtime(time());
		my $LOG = shift;
		print "$TIME + $LOG\n";
		undef $LOG;
	}
}
sub debug_log($)
{
	if ($DEBUGLOG)
	{
		my $TIME = localtime(time());
		my $LOG = shift;
		print "$TIME ++ $LOG\n";
		undef $LOG;
	}
}

sub trim($)
{
   my $string = shift;
   $string =~ s/^\s+//;
   $string =~ s/\s+$//;
   return $string;
}

sub trimstr (@)
{
	my @str = @_;

	for (@str)
	{
		chomp;
		s/^[\t\s]+//;
		s/[\t\s]+$//;
	}

	return @str;
}

sub trunc ($)
{
	my $file = shift;
	open my $fh, ">$file" or die "Can't write $file: $!\n";
	print $fh '';
	close $fh;
}

sub logmsg ($$)
{
	my ($config, $msg) = @_;
	my $logfile = $configd->{'daemon.logfile'};

	open my $fh, ">>$logfile" or die "Can't write logfile $logfile: $!\n";
	print $fh localtime()." (PID $$): $msg\n";
	close $fh;
}


sub readconfig ($)
{
	my $configfile = shift;

	open my $fh, $configfile or die "Can't read $configfile\n";
	my %configd;

	while (<$fh>)
	{
		next if /^[\t\w]+#/;
		s/#.*//;

		my ($key, $val) = trimstr split '=', $_, 2;
		next unless defined $val;

		$config{$key} = $val;
	}

	close $fh;

	# Check
	my $msg = 'Missing property:';

	foreach (qw(wd pidfile logfile)) {
		my $key = "daemon.$_";
		die "$msg $key\n" unless exists $configd{$key};
	}

	logmsg \%configd => "Reading $configfile complete";
	return \%configd;

	sub dp($)
	{

        my $locate = shift;
        if ($dialog_active)
        {
        #   system("$dialog_bin --gauge \"$locate\" 10 50 $percent");

            my $BACKTITLE=" \"AlienVault Reconfiguring Clone System Script\"";
            my $TITLE="AlienVault Reconfig";
            my $BODY="Please be patient...";
            my($dlg) = "echo $percent | $dialog_bin --stdout --title \"$TITLE\" --backtitle $BACKTITLE --gauge \"$locate\" 10 50 ";
            my($rslt) = qx{ $dlg };
            my($status) = $? >> 8;
            $percent = $percent+2 ;
        }
    }
}
