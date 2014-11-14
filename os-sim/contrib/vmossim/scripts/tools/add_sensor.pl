#!/usr/bin/perl

sub usage{
print "$0 sensor_name sensor_address\n";
exit;
}

if(!($ARGV[1] =~ /^\d+\.\d+\.\d+\.\d+$/)){
&usage;
}

$name = $ARGV[0];
$ip = $ARGV[1];

use ossim_conf;
use DBI;

$dsn = "dbi:" .
$ossim_conf::ossim_data->{"ossim_type"} . ":" .
$ossim_conf::ossim_data->{"ossim_base"} . ":" .
$ossim_conf::ossim_data->{"ossim_host"} . ":" .
$ossim_conf::ossim_data->{"ossim_port"} . ":";

$conn = DBI->connect($dsn, 
$ossim_conf::ossim_data->{"ossim_user"},
$ossim_conf::ossim_data->{"ossim_pass"})
or die "Can't connect to Database\n";

# Insert sensor
my $query = "INSERT INTO sensor VALUES(\"$name\", \"$ip\", 5, 40001, 0, \"$name\");";
my $sth = $conn->prepare($query);
$sth->execute();

my $query = "INSERT INTO host VALUES(\"$ip\", \"$name\", 2, 300, 300, 0, 4, \"\", \"Default\", \"\");";
my $sth = $conn->prepare($query);
$sth->execute();

my $query = "GRANT ALL ON *.* TO root@\"$ip\" IDENTIFIED BY \"root\";";
my $sth = $conn->prepare($query);
$sth->execute();

my $query = "GRANT ALL ON *.* TO root@\"$name\" IDENTIFIED BY \"root\";";
my $sth = $conn->prepare($query);
$sth->execute();
