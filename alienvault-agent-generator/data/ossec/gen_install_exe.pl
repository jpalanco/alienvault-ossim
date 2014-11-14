#!/usr/bin/perl

$lic = "(c) Alienvault LLC 2010, this is not free software, if unsure you're not allowed to reproduce it\n";
print $lic;


use Config::Tiny;

if(!-d "./installer"){
die("Need to have OSSEC installer files under ./installer");
}

if(!-f "ossec-batch-manager.pl"){
die("Need ossec-batch-manager.pl in pwd");
}

# Var declaration
my $config_file="/etc/ossim/ossim_setup.conf";
my $conf = Config::Tiny->new();
$conf = Config::Tiny->read($config_file);

sub getprop {
    my $section  = shift;
    my $property = shift;
    return $conf->{$section}->{$property};
}

if(!$ARGV[0]){
print "Supply IP of the host for which you want to generate an installer executable\n";
exit;
}

# Sanity check
if($ARGV[0] =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/){
if($1 < 0 || $2 < 0 || $3 < 0 || $4 < 0 || $1 > 255 || $2 > 255 || $3 > 255 || $4 > 255){
die("Please enter a valid IP");
}
} else {
die("Please enter an IP");
}

$agent_ip = $ARGV[0];

if(! -d "/var/ossec/"){
print "Local OSSEC installation required under /var/ossec/, exiting\n";
exit;
}

# Grab admin ip
$admin_ip = getprop( "_", "admin_ip" );

# Write server ip to config file
open(INFILE,"<installer/default-ossec.conf");
open(OUTFILE,">installer/ossec.conf");
while($line = <INFILE>){
if($line =~ /server-ip/){
print OUTFILE "<server-ip>$admin_ip</server-ip>\n";
} else {
print OUTFILE $line;
}
}

close INFILE;
close OUTFILE;

# Check if our host is already inside ossec's DB
open(TMP,"grep -c ' $agent_ip ' /var/ossec/etc/client.keys|");
$tmp = <TMP>;
close TMP;

#Create the client key file
if($tmp == 1){
system("grep ' $agent_ip ' /var/ossec/etc/client.keys > installer/client.keys");
} else {
system("perl ossec-batch-manager.pl -a -p $agent_ip -n $agent_ip");
system("grep ' $agent_ip ' /var/ossec/etc/client.keys > installer/client.keys");
}

#Create the installer exe
system("cd ./installer/; sh make.sh; mv ossec-agent-alienvault-installer.exe ../ossec_installer_$agent_ip.exe; cd ..");

print "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\nCongratulations. Your unattended OSSEC installer is waiting at ossec_installer_$agent_ip.exe\n\n\n\n\n";
