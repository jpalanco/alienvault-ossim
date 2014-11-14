#!/usr/bin/perl

# Call this program using either with:
# - no args (takes info from /etc/network/interfaces)
# - IP address
# - eth address
#
# Optional second arg is wether it's a sensor configuration, in which some steps are omitted. Value is 1.

use ossim_conf;
use DBI;

# We do retrieve the profile now installed
open(INFILE, "</etc/vmossim-profile");
$profile = <INFILE>;
close INFILE;


$dsn = "dbi:" .
$ossim_conf::ossim_data->{"ossim_type"} . ":" .
$ossim_conf::ossim_data->{"ossim_base"} . ":" .
$ossim_conf::ossim_data->{"ossim_host"} . ":" .
$ossim_conf::ossim_data->{"ossim_port"} . ":";

$conn = DBI->connect($dsn, 
$ossim_conf::ossim_data->{"ossim_user"},
$ossim_conf::ossim_data->{"ossim_pass"})
or die "Can't connect to Database\n";

if($ARGV[0] =~ /^\d+\.\d+\.\d+\.\d+$/){
$ip = $ARGV[0];
} else {
    $interface = "eth0";
    if($ARGV[0] =~ /(\w+)/){
        $interface = $1;
    }
open(INFILE, "</etc/network/interfaces");

while(<INFILE>){
    if(/^iface $interface/){
        while(<INFILE>){
            if(/\s+address\s+(\d+\.\d+\.\d+\.\d+)/){
            $ip = $1;
            last;
            }
        }
    last;
    }
}
close INFILE;

}

&replace_ip("[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}.*vmossim", "$ip vmossim", "/etc/hosts", "/etc/hosts.temp.new");

if (($profile == "all-in-one") || ($profile == "sensor")) {
    &replace_ip("ip = [0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}", "ip = $ip", "/etc/ossim/agent/config.cfg", "/etc/ossim/agent/config.cfg.temp.new");
    &replace_ip("sensor = [0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}", "sensor = $ip", "/etc/ossim/agent/config.cfg", "/etc/ossim/agent/config.cfg.temp.new");
    &replace_ip("sensor_name=[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}\\.[0-9]\\{1,3\\}", "sensor_name=$ip", "/etc/snort/snort.conf", "/etc/snort.conf.temp.new");
}

# server is always listening in 0.0.0.0, no need to change this ip in server's
# config
#if (($profile == "server") || ($profile == "all-in-one")) {
# replace_ip('\(.*\<server.*ip="\)[0-9.]*\(".*\)', "\\1$ip\\2", "/etc/ossim/server/config.xml", "/etc/ossim/server/config.xml.temp.new");
#}

if ($profile == "all-in-one") {
    # Update DB sensor
    my $query = "UPDATE sensor set ip = \"$ip\" where name = \"vmossim\";";

    my $sth = $conn->prepare($query);
    $sth->execute();

    # Update host entry

    my $query = "UPDATE host set ip = \"$ip\" where hostname = \"vmossim\";";

    my $sth = $conn->prepare($query);
    $sth->execute();

    # Update default ntop link

    my $query = "UPDATE config set value = \"http://$ip:3000\" where conf = \"ntop_link\";";
    my $sth = $conn->prepare($query);
    $sth->execute();
} #end if all-in-one

sub replace_ip{
($regexp, $dest, $from, $to) = @_;
system("cat $from | sed 's/$regexp/$dest/g' > $to; mv $to $from");
}

