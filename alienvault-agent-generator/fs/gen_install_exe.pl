#!/usr/bin/perl

$lic = "(c) Alienvault LLC 2010, this is not free software, if unsure you're not allowed to reproduce it\n";
print $lic;


use Config::Tiny;
use File::Copy;

if(!-d "./installer"){
  die("Need to have OSSEC installer files under ./installer");
}

if(!$ARGV[0]){
  print "Usage: gen_install_exe.pl <agent_id>\n";
  exit;
}

# Sanity check
if(!($ARGV[0] =~ /^(\d+)$/)){
   die("The id is a number\n");
}

$agent_id = $ARGV[0];

if(! -d "/var/ossec/"){
  print "Local OSSEC installation required under /var/ossec/, exiting\n";
  exit;
}

$line = `grep $agent_id /var/ossec/etc/client.keys`;
$line =~ /^(\d+)\s(\S+)\s(\S+)\s(\S+)$/;
my $agent_ip = $3;
print "Agent ip: $agent_ip\nAgent_id: $agent_id\n";
my $route_get = qx(ip route get $agent_ip | head -1| tr -s " ");
my @parts = split " ", $route_get;
my $server_ip = pop @parts;
print "Server ip: $server_ip\nAgent ip: $agent_ip\n";

# Write server ip to config file
# open(INFILE,"<installer/default-ossec.conf");
# open(OUTFILE,">installer/ossec.conf");
# while($line = <INFILE>){
#    if($line =~ /server-ip/){
#      print OUTFILE "<server-ip>$admin_ip</server-ip>\n";
#    } else {
#       print OUTFILE $line;
#    }
# }
# 
# close INFILE;
# close OUTFILE;

copy("installer/default-ossec.conf","installer/ossec.conf") or die ("Impossible to set up remote ossec.conf");
!system (qq{sed -i s/INSERT_HERE_SERVER_IP/$server_ip/g installer/ossec.conf}) or die ("Imposible to set up destination server");

# Find line in client.keys
$line = `grep $agent_id /var/ossec/etc/client.keys`;
print $line;
#001 w2012 192.168.2.142 ae6c943a6b2a9ffe3c7ea0aff52f3352eb7222f476dcae4cda757afc3ae58d3a
$line =~ /^(\d+)\s(\S+)\s(\S+)\s(\S+)$/;
print "DIG->$1 NAME->$2 IP->$3 KEY->$4";
$agent_name = $2;
$agent_ip = $3;
$agent_key = $4;

system("grep '^$agent_id' /var/ossec/etc/client.keys > installer/client.keys");
#Create the installer exe
system("mkdir -p /usr/share/ossec-generator/agents; cd /usr/share/ossec-generator/installer/; sh make.sh; mv ossec-agent-alienvault-installer.exe /usr/share/ossec-generator/agents/ossec_installer_$agent_id.exe; /usr/share/ossec-generator/perms.sh");

print "Congratulations. Your unattended OSSEC installer is waiting at ossec_installer_$agent_id.exe\n";
