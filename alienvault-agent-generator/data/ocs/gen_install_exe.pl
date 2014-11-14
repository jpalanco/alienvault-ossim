#!/usr/bin/perl

$lic = "(c) Alienvault LLC 2010, this is not free software, if unsure you're not allowed to reproduce it\n";
print $lic;


use Config::Tiny;

if(!-f "./OcsAgentSetup.exe"){
die("Need to have OCS installer file under ./OcsAgentSetup.exe");
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

# Grab admin ip
$admin_ip = getprop( "_", "admin_ip" );

# Write server ip to installer file
open(INFILE,"<ocs-installer.nsi.from");
open(OUTFILE,">ocs-installer.nsi");
while($line = <INFILE>){
if($line =~ /server.*FORCE.*NOW/){
print OUTFILE "Exec '\"$INSTDIR\OcsAgentSetup.exe\" \"/S /server:$admin_ip /np /FORCE /NOW\"'
\n";
} else {
print OUTFILE $line;
}
}

close INFILE;
close OUTFILE;

#Create the installer exe
system("sh make.sh; mv ocs-agent-alienvault-installer.exe ocs_installer_$agent_ip.exe");

print "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\nCongratulations. Your unattended OCS installer is waiting at ocs_installer_$agent_ip.exe\n\n\n\n\n";
