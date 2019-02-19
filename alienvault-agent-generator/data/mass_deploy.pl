#!/usr/bin/perl

$lic = "(c) Alienvault LLC 2010, this is not free software, if unsure you're not allowed to reproduce it\n";
print $lic;

$| = 1;
$deploy_cmd = "deploy.pl";

if(!$ARGV[2]){
print "\nUsage:\n\n";
print "$0 target_ip_file username password\n\n\n";
print "($0 will generate installation packages for target_ips if not already present)\n";
print "Please make sure 'winexe' is in your path (part of the wmi-client package)\n";
print "target_ip_file expects one ip per line, followed by optional username and password information. Domain information optional.\n\n";
print "Sample lines:\n";
print "192.168.1.40\n";
print "192.168.1.41\n";
print "192.168.1.69:wmi:wmi\n";
print "192.168.1.70:Domain\\User:password\n";
print "\n\n\n";
exit;
}

if(!-r $ARGV[0]){
print "Please check '$ARGV[0]' credential file, unable to read it.\n";
exit;
}

if(!-f $deploy_cmd){
print "Missing $deploy_cmd, required for correct operation\n";
}

$cred_file = $ARGV[0];
$user = $ARGV[1];
$pass = $ARGV[2];

if($user =~ /'/){
print "Sorry, ' (single quotation mark) is not allowed in username\n";
exit;
}

if($pass =~ /'/){
print "Sorry, ' (single quotation mark) is not allowed in password\n";
exit;
}

# Start doing stuff
open(CRED,"<$cred_file") or die "Unable to open $cred_file\n";
while(<CRED>){
chop;
$ip = "";
$ip_user = "";
$ip_pass = "";
if(/^((\d+)\.(\d+)\.(\d+)\.(\d+))$/){
# Simple ip
if($5 < 0 || $2 < 0 || $3 < 0 || $4 < 0 || $5 > 255 || $2 > 255 || $3 > 255 || $4 > 255){
print "Malformed ip at line $_, skipping\n";
next;
} else {
$ip = $1;
$ip_user = $user;
$ip_pass = $pass;
}
} elsif (/^((\d+)\.(\d+)\.(\d+)\.(\d+)):(.*):(.*)$/) {
# IP with credentials
$ip_user = $6;
$ip_pass = $7;
$ip = $1;

if($ip =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/){

if($1 < 0 || $2 < 0 || $3 < 0 || $4 < 0 || $1 > 255 || $2 > 255 || $3 > 255 || $4 > 255){
print "Malformed ip at line $_, skipping\n";
next;
} else {
}

}

if($ip_user =~ /'/){
print "Sorry, ' (single quotation mark) is not allowed in username for $ip\n";
next;
}

if($ip_pass =~ /'/){
print "Sorry, ' (single quotation mark) is not allowed in password for $ip\n";
next;
}



} else {
# Crap
print "Unable to extract line $_, skipping\n";
next;
} 

print "Calling $deploy_cmd with $ip, $ip_user, $ip_pass\n";
system("perl $deploy_cmd $ip '$ip_user' '$ip_pass'");

}
close CRED;

# Sanity check

# Say goodbye
print "Done.\n";
print "\n";
