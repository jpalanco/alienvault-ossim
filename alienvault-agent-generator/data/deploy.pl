#!/usr/bin/perl

$lic = "(c) Alienvault LLC 2010, this is not free software, if unsure you're not allowed to reproduce it\n";
print $lic;

$| = 1;

if(!$ARGV[2]){
print "\nUsage:\n\n";
print "$0 target_ip username password\n\n\n";
print "($0 will generate installation packages for target_ip if not already present)\n";
print "Please make sure 'winexe' is in your path (part of the wmi-client package)\n";
print "\n\n\n";
exit;
}

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


# Sanity check
if($ARGV[0] =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/){
if($1 < 0 || $2 < 0 || $3 < 0 || $4 < 0 || $1 > 255 || $2 > 255 || $3 > 255 || $4 > 255){
die("Please enter a valid IP");
}
} else {
die("Please enter an IP");
}

$ip = $ARGV[0];
$user = $ARGV[1];
$pass = $ARGV[2];
$no_output = " 2>&1 >& /dev/null"; # Debug
#$no_output = " "; # No Debug
# Grab admin ip
$admin_ip = getprop( "_", "admin_ip" );

# We'll need this dir later
$tmp_dir = "~/.alienvault_agent_deployment/tmp/";
$log_dir = "~/.alienvault_agent_deployment/log/";
system("mkdir -p $tmp_dir");
system("mkdir -p $log_dir");

if($user =~ /'/){
print "Sorry, ' (single quotation mark) is not allowed in username\n";
exit;
}

if($pass =~ /'/){
print "Sorry, ' (single quotation mark) is not allowed in password\n";
exit;
}



sub check_execution(){
# Checks credentials (credential security), execute permission, connectivity, etc...

$cmd = "winexe -U '$user'%'$pass' //$ip \"print AVT_AABBCC\"";

$retval = system($cmd . $no_output);

if($retval != 0){
print "Unable to connect to remote host, please try the following command manually in order to check for errors\n";
print $cmd;
print "\n";
exit;
} else {
print "\n[+] Connection established, remote command execution confirmed on $ip with provided credentials.\n";
} 
}

sub try_mount(){

##### Try mount
$mount_cmd = "smbmount \"//$ip/ADMIN\$\" $tmp_dir -o user='$user',pass='$pass'";
$retval = system($mount_cmd . $no_output);

if($retval != 0){
print "Unable to mount $ip ADMIN$, please check configuration\n";
print "Cmd was: $mount_cmd\n";
exit;
} else {
print "[+] ADMIN\$ mount confirmed\n";
}

##### Try write on mount (need to unmount before exiting from now on)
$touch_cmd = "touch $tmp_dir/AVT_BBCCDD";
system($touch_cmd . $no_output);

##### Check Write
$cmd = "ls -la $tmp_dir/AVT_BBCCDD 2>&1 >& /dev/null; echo \$?";
system($cmd . $no_output);
if($retval != 0){
print "Unable to write on ADMIN\$, please check permissions\n";
print "Cmd was: $touch_cmd, $cmd\n";
system("umount $tmp_dir");
exit;
} else {
print "[+] Successful write on ADMIN\$ at $ip\n";
}

##### Check Delete
$cmd = "rm -f $tmp_dir/AVT_BBCCDD 2>&1 >& /dev/null; echo \$?";
$retval = system($cmd . $no_output);
if($retval != 0){
print "Unable to delete from ADMIN\$, please check permissions\n";
print "Cmd was: $cmd\n";
system("umount $tmp_dir");
exit;
} else {
print "[+] Successful delete from ADMIN\$ at $ip\n";
}

# Return leaving the share mounted
}

sub ping_server(){
$cmd = "winexe -U '$user'%'$pass' //$ip \"ping -n 1 $admin_ip\" > $log_dir/$ip.ping.log; echo \$?";
$retval = system($cmd . $no_output);

if($retval != 0){
print "[-] Unable to ping $admin_ip from $ip, this might be normal, moving on.\n";
print "[-] Logs at $log_dir/$ip.ping.log\n";
} else {
print "[+] Successful ping from $ip to $admin_ip\n";
}
}

sub check_and_generate_installer(){
if(!-f "alienvault_agents_$ip.exe"){
print "[+] $ip agent files not found, generating\n";
system("perl gen_ossim_agents.pl $ip" . $no_output);
if(-f "alienvault_agents_$ip.exe"){
print "[+] $ip agent files successfully generated.\n";
} else {
print "[-] $ip agent files not created, please check 'perl gen_ossim_agents.pl $ip' output\n";
system("umount $tmp_dir");
exit;
}
} else {
print "[+] $ip agent files found.\n";
}
}

sub copy_and_execute_installer(){

open(CMD,"winexe -U '$user'%'$pass' //$ip \"cmd /c echo %windir%\"|");
$windir = <CMD>;
$windir =~ s/\r\n//g;
close CMD;
print "[+] Windir at $windir\n";

$retval = system("cp alienvault_agents_$ip.exe $tmp_dir" . $no_output);

if($retval != 0){
print "Unable to copy alienvault_agents_$ip.exe to $tmp_dir (//$ip/ADMIN\$), please check permissions\n";
system("umount $tmp_dir");
exit;
} else {
print "[+] Successfully copied alienvault_agents_$ip.exe to ADMIN\$ on $ip\n";
}

$cmd = "winexe -U '$user'%'$pass' //$ip \"cmd /c $windir\\alienvault_agents_$ip.exe\"; echo $?";
$retval = system($cmd . $no_output);

if($retval != 0){
print "Unable to execute alienvault_agents_$ip.exe on (//$ip/ADMIN\$), please check permissions\n\n";
system("umount $tmp_dir");
exit;
} else {
print "[+] Successfully executed alienvault_agents_$ip.exe on ADMIN\$ at $ip\n";
}

}

# Start doing stuff
check_execution();
# Execution works, let's get windir
try_mount();
ping_server();
check_and_generate_installer();
copy_and_execute_installer();

# Unmount at the end
system("umount $tmp_dir");

# Say goodbye
print "Done.\n";
print "\n";
