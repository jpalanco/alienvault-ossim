#!/usr/bin/perl

use strict;
use warnings;

use lib '/usr/share/alienvault-center/lib';
use AV::ConfigParser;
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

my %config      = AV::ConfigParser::current_config;
my %config_last = AV::ConfigParser::last_config;

print "Configuring firewall rules for Squid proxy...\n";
sleep 10;

#Remove squid rules
system("/usr/bin/perl", "-p", "-i", "-ne", "s/^.*--dport[[:blank:]]+3182.*\$//g", $iptables_rules_framework);
system("/bin/sed", "-i", "-e", '/^[[:blank:]]*$/d', $iptables_rules_framework);


#Add squid rules if proxy is enabled
if ( $config{'update_proxy'} eq "alienvault-proxy" ){
    my @registered_systems=`alienvault-api get_registered_systems --list | perl -npe 's/.*?;(\\d+\\.\\d+\\.*?)/\$1/'`;
    my $acl = join(" ",@registered_systems);

    $acl =~ s/;/ /g;
    $acl =~ s/\s+/ /g;
    $acl =~ s/\n//g;

    my $admin_ip = $config{'admin_ip'};
    my $vpn_net = $config{'vpn_net'};

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

    open( my $fh, '>>', $iptables_rules_framework );

    foreach my $host ( @hosts_selected ) {
        print $fh "-A INPUT  -p tcp -m state --state NEW -m tcp -s $host --dport 3128 -j ACCEPT\n";
    }

    close $fh;
}

system("/usr/share/alienvault-center/lib/reset_redis_firewall.sh");

system("/etc/network/if-pre-up.d/iptables");

print "Finished configuring firewall rules for Squid proxy...\n";

#EOF

