#!/usr/bin/perl

$lic = "(c) Alienvault LLC 2010, this is not free software, if unsure you're not allowed to reproduce it\n";
print $lic;

use Config::Tiny;

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
print "Supply IP of the host for which you want to generate an OSSIM Agents installer executable\n";
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

# Grab admin ip
$admin_ip = getprop( "_", "admin_ip" );

system("cd ocs; perl gen_install_exe.pl $agent_ip; mv ocs_installer_$agent_ip.exe ../ocs_installer.exe; cd ..");
system("cd ossec; perl gen_install_exe.pl $agent_ip; mv ossec_installer_$agent_ip.exe ../ossec_installer.exe; cd ..");

system("sh make.sh; mv alienvault_agents.exe alienvault_agents_$agent_ip.exe");


print "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\nCongratulations. Your unattended OSSIM Agents installer is waiting at alienvault_agents_$agent_ip.exe\n\n\n\n\n";
