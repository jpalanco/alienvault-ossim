#!/usr/bin/perl
#
#

use Config::Tiny;

	my $conf=$ARGV[0];
	my $Config = Config::Tiny->new();
	$Config = Config::Tiny->read( $conf );


sub getprop {
my $section = shift;
my $property = shift;

return $Config->{$section}->{$property};

}

$argc=@ARGV;

if($ARGV[1] =~ /^set$/)
{
	if($argc!=5)
	{
		print "Invalid number of arguments for set";
		exit(10);
	}

	$Config->{$ARGV[2]}->{$ARGV[3]}=$ARGV[4];
	$Config->write($conf);
	exit(0);
}

if($ARGV[1] =~ /^get$/)
{
	if($argc!=4)
	{
		print "Invalid number of agruments for get";
		exit(10);
	}

	my $a=getprop($ARGV[2],$ARGV[3]);
	print $a;
	exit(1);
}

print "Invalid option\n";
exit(123);

