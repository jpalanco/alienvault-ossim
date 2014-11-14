#!/usr/bin/perl

if(!$ARGV[0])
{
	print "Usage: $0 filename\n";
	exit();
}

open(IN,"<$ARGV[0]") or die "Can't open $ARGV[0]";

if($ARGV[0] =~ /\/([^\/]*)$/)
{
	print "---- $1\n";
}

while(<IN>)
{
#alert tcp $EXTERNAL_NET any -> $HOME_NET 22 (msg:"EXPLOIT SSH server banner overflow"; flow:established,from_server; content:"SSH-"; nocase; isdataat:200,relative; pcre:"/^SSH-\s[^\n]{200}/ism"; reference:bugtraq,5287; classtype:misc-attack; sid:1838; rev:6;)
#if(/alert.*\s+(\d+)\s+\(msg:"([^"]*)".*sid:(\d+)/){
	if(/reference:bugtraq,(\d+).*sid:(\d+)/)
	{
		$bugtraq_id = $1;
		$sid = $2;
		print "bugtraq $sid, $bugtraq_id \n";

	}

	if(/reference:cve,(\d+)\-(\d+).*sid:(\d+)/)
	{
		$cve_id = $1."-".$2;
		$sid = $3;
		print "cve $sid, $cve_id \n";

	}


}

close IN;
