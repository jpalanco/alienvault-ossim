package AV::Profile;

# Usage: profile( qw(func1 func2 .. funcn) );
#        profile_packages( qw(package1 package2 .. packagen) );

# sub profile based heavily on example 'profile' of the book 'High-Order
# Perl', whose license is provided below:
#
# This software is Copyright 2005 by Elsevier Inc.  You may use it
# under the terms of the license at http://perl.plover.com/hop/LICENSE.txt .
#
# LICENSE:
#
# from Higher-Order Perl by Mark Dominus, published by
#    Morgan Kaufmann Publishers, Copyright 2005 by Elsevier Inc.
#

###
### profile
###

## Chapter 3 section 12.2

use strict;
use warnings;

use AV::Log::File;
use Time::HiRes 'time';
use Devel::Symdump;
use Scalar::Util 'set_prototype';

use parent qw(Exporter);
our @EXPORT_OK = qw(profile profile_packages dump_report);

my ( %time, %calls );

sub profile {
    no strict 'refs';
    no warnings 'redefine';
    for my $name (@_) {
        my $func = \&$name;
        my $stub = set_prototype(
            sub {
                my $start   = time;
                my $return  = $func->(@_);
                my $end     = time;
                my $elapsed = $end - $start;
                $calls{$name} += 1;
                $time{$name}  += $elapsed;
                return $return;
        }, prototype $func);
        *$name = $stub;
    }
    return;
}

sub profile_packages {
    profile( Devel::Symdump->functions(@_) );
    return;
}

sub dump_report {
    my $report = sprintf "%-30s %9s %11s\n", 'Function', '# calls',
        'Elapsed (s)';
    for my $name ( sort { $time{$b} <=> $time{$a} } ( keys %time ) ) {
        $report .= sprintf "%-30s %9d %11.2f\n", $name, $calls{$name},
            $time{$name};
    }
    console_log_file($report);

    # Reset counters after producing a report
    %time  = ();
    %calls = ();

    return;
}

1;
