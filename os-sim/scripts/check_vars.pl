#!/usr/bin/perl
#
# License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2014 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

if(!$ARGV[0]){
print "Specify a directory to check\n";
exit;
}

use File::Find;

@directories = ();

$directories[0] = $ARGV[0];

find(\&wanted,  @directories);

sub wanted {
#if(/.php$/s || /.inc$/s){ 
if(/.php$/s){
print "Checking $File::Find::name\n"; 
&check($File::Find::name);
}
}

sub check {
@union = @intersection = @difference = ();
@vars;
@validates;
%count = ();
%vars = ();
$code_file = shift;
open(INPUT, "<$code_file");
while(<INPUT>){
if(/(\$.*)=.*(POST|GET|REQUEST)\(('|")(.*)('|")\)/){

if(!exists($vars{$1}{'request'})){
print "Assigning $4 to $1\n";
$vars{$1}{"request"} = $4;
} else {
if($vars{$1}{"request"} ne $4){
print "Warning, $1 request redefined:" . $vars{$1}{"request"} . " != " . $4 . "\n";
}
}

next;
} # end checking for the var assignment

if(/ossim_valid\((\$[^,]*),(.*)$/){

if(!exists($vars{$1}{'ossim_valid'})){
print "Validating $1 using $2\n";
$vars{$1}{"ossim_valid"} = $2;
} else {
if($vars{$1}{"ossim_valid"} ne $2){
print "Warning, $1 validation redefined:" . $vars{$1}{"ossim_valid"} . " != " . $2 . "\n";
}
}

} # end checking for ossim_valid

if(/(^.*(\$\w+).*$)/){
print "Using $2 at $1\n";
} # end use checking before validation

}
close INPUT;

foreach $element (@array1, @array2) { $count{$element}++ }
foreach $element (keys %count) {
push @union, $element;
push @{ $count{$element} > 1 ? \@intersection : \@difference }, $element;
}  


}
