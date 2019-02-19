#!/usr/bin/perl

use strict;
use warnings;

use List::MoreUtils qw(uniq);

my $ossim_setup_conf_file = '/etc/ossim/ossim_setup.conf';
my $iptables_rules_framework = "/etc/iptables/rules009-framework.iptables";


### MAIN ###

if ( ! -f $ossim_setup_conf_file ) {
    print "WARNING: $ossim_setup_conf_file file not found!\n";
    exit 0
}

if ( ! -f $iptables_rules_framework ) {
    print "WARNING: $iptables_rules_framework file not found!\n";
    exit 0
}

sleep 10;

print "Configuring firewall rules for Squid proxy...\n";

my @registered_systems=`alienvault-api get_registered_systems --list | perl -npe 's/.*?;(\\d+\\.\\d+\\.*?)/\$1/'`;
my $acl = join(" ",@registered_systems);

$acl =~ s/;/ /g;
$acl =~ s/\s+/ /g;
$acl =~ s/\n//g;

my $admin_ip = "";
my $vpn_net = "";

$admin_ip = `cat $ossim_setup_conf_file | grep ^admin_ip= | cut -d = -f 2`;
chomp $admin_ip;
$vpn_net = `cat $ossim_setup_conf_file | grep ^vpn_net= | cut -d = -f 2`;
chomp $vpn_net;

my @hosts = uniq( split / /, $acl );

my @hosts_selected = ();


foreach my $host (@hosts) {

    if ( index($host, $vpn_net) != -1 ) {
        print "Host $host matches VPN network $vpn_net! EXCLUDING it...\n";
    } elsif ( $host eq $admin_ip ) {
        print "Host $host matches admin_ip $admin_ip! EXCLUDING it...\n";
    } else {
        print "Host $host matches neither VPN network $vpn_net not admin_ip $admin_ip. INCLUDING it...\n";
        push @hosts_selected, $host;
    }

}


print "\nHosts selected for allowing access to Squid proxy: @hosts_selected\n\n";

system("/usr/bin/perl", "-p", "-i", "-ne", 's/^.*--dport[[:blank:]]+3128.*$//g', $iptables_rules_framework);
system("/bin/sed", "-i", "-e", '/^[[:blank:]]*$/d', $iptables_rules_framework);

open( my $fh, '>>', $iptables_rules_framework );

foreach my $host ( @hosts_selected ) {
    print $fh "-A INPUT  -p tcp -m state --state NEW -m tcp -s $host --dport 3128 -j ACCEPT\n";
}

close $fh;


system("/usr/share/alienvault-center/lib/reset_redis_firewall.sh");

system("/etc/network/if-pre-up.d/iptables");

print "Finished configuring firewall rules for Squid proxy...\n";

#EOF
