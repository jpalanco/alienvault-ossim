package Linux::APT;

use strict;
use warnings;

our $VERSION = '0.02';

=head1 NAME

Linux::APT - Interface with APT for Debian distributions

=head1 DESCRIPTION

Perl interface to C<apt-get> and C<apt-cache>.
If Debian's C<aptpkg> modules were on CPAN, this module (probably) wouldn't be necessary.
This is just a wrapper around the C<apt> tools along with some regular expression magic
to capture interesting pieces of information/warnings/errors in the process.
It doesn't do I<everything> that is possible, but it should fill the most typical needs.
Features will be added on request or my own need.
Please file a wishlist bug report on the CPAN bug tracker with your feature requests.

All (or almost all?) features require root privileges.
If you can use C<sudo> to provide this functionality, see C<new> to see how to do this.

It's not ready for production use, but you're welcome to give it a try.
I<Please> file bug reports if you come across any problems/bugs/etc.
Patches are always welcomed, of course.

=head1 EXAMPLE

  my $apt = Linux::APT->new;
  my $update = $apt->update;
  my $toupgrade = $apt->toupgrade;
  my $upgraded = $apt->install(keys(%{$toupgrade->{packages}}));

=head1 METHODS

=head2 new

  my $apt = Linux::APT->new;

  # only if you _really_ want to see what's going on...
  my $apt = Linux::APT->new(debug => 1);

  # if you want to use an alternate apt-get/apt-cache binary
  my $apt = Linux::APT->new(
    aptget => '/some/path/to/apt-get',
    aptcache => '/some/path/to/apt-cache',
  );

  # if you have special needs (like sudo, etc)
  my $apt = Linux::APT->new(
    aptget => '/usr/bin/sudo /some/path/to/apt-get -s', # sudo and no-act
    aptcache => '/usr/bin/sudo /some/path/to/apt-cache', # sudo
  );

Creates an instance of Linux::APT, just like you would expect.

If you have special needs for only one function (install maybe?), make a separate instance
with your special needs (flags, sudo, etc) and use that instance for your special need.

If your special need can't be accommodated via the C<aptget> option above, let me know and
I'll attempt to implement whatever it is that you need within the module or make your special
need a bit more "accessible" to you.
File a bug report on the CPAN bug tracker.
Patches welcome, of course.

Arguments available:

=over

=item debug

Set to C<1> to enable, defaults to C<0>.

=item aptget

Specify the C<apt-get> binary to use along with any special flags or command line tricks (sudo, chroot, fakeroot, etc).
Defaults to C<`which apt-get`>.

=item aptcache

Specify the C<apt-cache> binary to use along with any special flags or command line tricks (sudo, chroot, fakeroot, etc).
Defaults to C<`which apt-cache`>.

=back

=cut

sub new
{
  my $class = shift;
  my $self = {};
  my %args = @_;

  $self->{debug} = $args{debug};

  $self->{aptget} = $args{aptget} || `which apt-get`;
  chomp($self->{aptget});
  die qq(apt-get doesn't appear to be available.\n) unless $self->{aptget};

  $self->{aptcache} = $args{aptcache} || `which apt-cache`;
  chomp($self->{aptcache});
  die qq(apt-cache doesn't appear to be available.\n) unless $self->{aptcache};

  return bless($self, $class);
}

=head2 update

  my $update = $apt->update;

  warn "There were errors...\n" if $update->{error};
  warn "There were warnings...\n" if $update->{warning};

Update apt cache.
Basically equivalent to C<apt-get update>.

Returns hashref containing these items:

=over

=item error

Arrayref of errors.

=item warning

Arrayref of warnings.

=item speed

Network transfer speed of update.

=item time

Wallclock time it took to update.

=item size

Amount of received transferred during update.

=back

=cut

sub update
{
  my $self = shift;
  my $update = {};

  if (open(APT, "$self->{aptget} -q update 2>&1 |"))
  {
    while (my $line = <APT>)
    {
      chomp($line);
      print qq($line\n) if $self->{debug};
      if ($line =~ m#Fetched (\d+\S+) in (.*?) \((\d+\S+?)\)#i)
      {
        $update->{size} = $1;
        $update->{time} = $2;
        $update->{speed} = $3;
      }
      elsif ($line =~ s#^W: ##) # warning
      {
        my $warning = {};
        $warning->{message} = $line;
        push(@{$update->{warning}}, $warning);
      }
      elsif ($line =~ s#^E: ##) # error
      {
        my $error = {};
        $error->{message} = $line;
        push(@{$update->{error}}, $error);
      }
    }
    close(APT);
  }
  else
  {
    die "Couldn't use APT: $!\n";
  }

  return $update;
}

=head2 toupgrade

  my $toupgrade = $apt->toupgrade;

Returns hashref of packages, errors, and warnings:

=over

=item warning

Warnings, if any.

=item error

Errors, if any.

=item packages

Contains a hashref of updateable packages.
Keys are package names.
Each update is a hashref containing these items:

=over

=item current

Currently installed version.

=item new

Version to be installed.

=back

=back

=cut

sub toupgrade
{
  my $self = shift;
  my $updates = {};

  if (open(APT, "echo n | $self->{aptget} -q -V dist-upgrade -s 2>&1 |"))
  {
    while (my $line = <APT>)
    {
      chomp($line);
      print qq($line\n) if $self->{debug};
      #if ($line =~ m#^\s+(\S+)\s+\((\S+)\s+=>\s+(\S+)\)#)
      #if ($line =~ m#^Inst\s+(\S+)\s+\[(\S+)\]\s+\((\S+)\s+(\S+)\)#)
      if ($line =~ m#^Inst\s+(\S+)\s+\[(\S+)\]\s+\((\S+)\s+(\S+)\s+(\S+)\)#)
      {
        my $update = {};
        my $package = $1;
        $update->{current} = $2;
        $update->{new} = $3;
        $update->{source} = $4;
        my @sizes = `apt-cache show $1 | grep "^Size: " `;
        my $size = pop(@sizes); 
			$size =~ s/\n//g; 
			$size =~ s/Size: //g;
        $update->{size} = $size;
        $updates->{packages}->{$package} = $update;
      }
      elsif ($line =~ s#^W: ##) # warning
      {
        my $warning = {};
        $warning->{message} = $line;
        push(@{$updates->{warning}}, $warning);
      }
      elsif ($line =~ s#^E: ##) # error
      {
        my $error = {};
        $error->{message} = $line;
        push(@{$updates->{error}}, $error);
      }
    }
    close(APT);
  }

  return $updates;
}

=head2 search

  my $search = $apt->search('^t\w+d$', 'perl');

  my $search = $apt->search({in => ['all']}, '^t\w+d$', 'perl'); # 'all' is default

  my $search = $apt->search({in=>['name', 'description']},
    'linux[\s-]image', 'linux[\s-]source', 'linux kernel image');

  my $search = $apt->search({in => ['description']}, 'linux kernel source');

Requires one or more search arguments in regex format.  Optional options as first
argument in hashref format.

Return a hashref of packages that match the regex search.

=over

=item packages

Multiple searches can be specified.  Each search is a hash key then broken
down by each matching package name and it's summary.

=back

=cut

sub search
{
  my $self = shift;
  my $search = {};
  my @args = @_;
  my $opts = {
    in => ['all'],
  };

  if (ref($args[0]) eq 'HASH')
  {
    my $optarg = shift;
    foreach my $arg (keys(%{$optarg}))
    {
      $opts->{$arg} = $optarg->{$arg};
    }
  }

  foreach my $pkg (@args) 
  {
    if (open(APT, "$self->{'aptcache'} search '$pkg' 2>&1 |")) 
    {
      while (my $line = <APT>) 
      {
        my $okay = 0;
        $okay = 1 if (grep(m/all/, @{$opts->{in}}));
        chomp($line);
        print qq($line\n) if $self->{'debug'};
        if ($line =~ m/^(\S+)\s+-\s+(.*)$/) 
        {
          my ($name, $desc) = ($1, $2);
          chomp($desc);
          $okay = 1 if (grep(m/name/, @{$opts->{in}}) && $name =~ m/$pkg/i);
          $okay = 1 if (grep(m/description/, @{$opts->{in}}) && $desc =~ m/$pkg/i);
          next unless $okay;
          $search->{$pkg}->{$name} = $desc;
        }
      }
    }
    close(APT);
  }

  return $search;
}

=head2 install

  # install or upgrade the specified packages (and all deps)
  my $install = $apt->install('nautilus', 'libcups2', 'rhythmbox');

  # just a dry run
  my $install = $apt->install('-test', 'nautilus', 'libcups2', 'rhythmbox');

  # upgrade all upgradable packages with a name containing "pulseaudio" (and all deps)
  my $toupgrade = $apt->toupgrade;
  my $install = $apt->install(grep(m/pulseaudio/i, keys(%{$toupgrade->{packages}})));

Install a list of packages.
If the packages are already installed, they will be upgraded if an upgrade is available.

Pass in these optional options:

=over

=item -force

If you wish to force an update (eg: C<WARNING: The following packages cannot be authenticated!>),
pass C<-force> as one of your arguments (same effect as C<apt-get --force-yes install $packages>).

=item -test

If you just want to know what packages would be installed/upgraded/removed, pass C<-test>
as one of your arguments.
No actions will actually take place, only the actions that would have been performed will be captured.
This is useful when you want to ensure some bad thing doesn't happen on accident (like removing
C<apache2-mpm-worker> when you install C<php5>) or to allow you to present the proposed changes to the
user via a user interface (GUI, webapp, etc).

=back

Returns hashref of packages, errors, and warnings:

=over

=item warning

Warnings, if any.

=item error

Errors, if any.

=item packages

Contains a hashref of installed/upgraded packages.
Keys are package names.
Each item is a hashref containing these items:

=over

=item current

Currently installed version (after install/upgrade attempt).
This version is found via an experimental technique and might fail (though it has yet to fail for me).
Let me know if you find a bug or have a problem with this value.

=item new

Version to be installed.
If C<new == current>, the action seems to have succeeded.

=item old

Version that was installed before the upgrade was performed.
If C<old == current>, the action seems to have failed.

=back

=back

=cut

sub install
{
  my $self = shift;
  my @install = @_;

  my $action = 'install';
  my $force = '';
  my $noop = 0;
  my $packages = '';
  my $installed = {};

  foreach my $install (@install)
  {
    if ($install eq '-force')
    {
      $force = '--force-yes';
      next;
    }
    elsif ($install eq '-test')
    {
      $noop = 1;
      next;
    }
    elsif ($install eq '-remove')
    {
      $action = 'remove';
      next;
    }
    elsif ($install eq '-purge')
    {
      $action = 'purge';
      next;
    }

    (my $package = $install) =~ s/[^a-z0-9\+\-_\.]//ig;
    $packages .= $package.' ';
  }

  my $state = '';
  my $notreally = ($noop ? 'echo n |' : '');
  my $justsayyes = ($noop ? '-s' : "-y $force");

  if (open(APT, "$notreally $self->{aptget} $justsayyes -q -V $action $packages 2>&1 |"))
  {
    while (my $line = <APT>)
    {
      chomp($line);
      print qq($line\n) if $self->{debug};
      if ($line =~ m/The following packages will be REMOVED:/i)
      {
        $state = 'removed';
      }
      elsif ($line =~ m/The following NEW packages will be installed:/i)
      {
        $state = 'installed';
      }
      elsif ($line =~ m/The following packages will be upgraded:/i)
      {
        $state = 'upgraded';
      }
      elsif ($line =~ m#^\s+(\S+)\s+\((\S+)\s+=>\s+(\S+)\)#) # upgrading
      {
        my $update = {};
        my $package = $1;
        $update->{old} = $2;
        $update->{new} = $3;
        $package =~ s/\*$//;
        $installed->{packages}->{$package} = $update;
        $installed->{$state}->{$package} = $installed->{packages}->{$package};
      }
      elsif ($line =~ m#^\s+(\S+)\s+\((\S+)\)#) # installing
      {
        my $update = {};
        my $package = $1;
        my $version = $2;
        $package =~ s/\*$//;
        if ($state eq 'removed')
        {
          $installed->{$state}->{$package} = $version
        }
        else
        {
          $update->{new} = $version;
          $installed->{packages}->{$package} = $update if $state;
          $installed->{$state}->{$package} = $installed->{packages}->{$package} if $state;
        }
      }
      elsif ($line =~ m/^(\d+)\s+upgraded,\s+(\d+)\s+newly\s+installed,\s+(\d+)\s+to\s+remove\s+and\s+(\d+)\s+not\s+upgraded./i)
      {
        $state = '';
        $installed->{intended}->{upgraded} = $1;
        $installed->{intended}->{installed} = $2;
        $installed->{intended}->{removed} = $3;
        $installed->{intended}->{upgradable} = $4;
      }
      elsif ($line =~ s#^W: ##) # warning
      {
        my $warning = {};
        $warning->{message} = $line;
        push(@{$installed->{warning}}, $warning);
      }
      elsif ($line =~ s#^E: ##) # error
      {
        my $error = {};
        $error->{message} = $line;
        push(@{$installed->{error}}, $error);
      }
    }
    close(APT);
  }

  unless ($noop)
  {
    foreach my $package (keys(%{$installed->{packages}}))
    {
      if (open(APT, "$self->{aptcache} showpkg $package |"))
      {
        while (my $line = <APT>)
        {
          chomp($line);
          print qq($line\n) if $self->{debug};
          if ($line =~ m#^(\S+)\s+.*?\(/var/lib/dpkg/status\)#)
          {
            $installed->{packages}->{$package}->{current} = $1;
          }
        }
        close(APT);
      }
    }
  }

  return $installed;
}

=head2 remove

  my $removed = $apt->remove('php5', 'php5-common');

  # just a dry run
  my $removed = $apt->remove('-test', 'php5', 'php5-common');

Remove a list of packages.
Arguments are the exact same as C<install>.
Returns the exact same as C<install>.

=cut

sub remove
{
  my $self = shift;
  return $self->install('-remove', @_);
}

=head2 purge

  my $removed = $apt->purge('php5', 'php5-common');

  # just a dry run
  my $removed = $apt->purge('-test', 'php5', 'php5-common');

Purge a list of packages.
Arguments are the exact same as C<install>.
Returns the exact same as C<install>.

=cut

sub purge
{
  my $self = shift;
  return $self->install('-purge', @_);
}

=head1 TODO

=over

=item (update this todo list...)

=item Add functions to modify the C<sources.list>.

=item Add C<dist-upgrade> functionality.

=item Add function to show version(s) of currently installed specified package(s).

=item Determine other necessary features. (please use the CPAN bug tracker to request features)

=back

=head1 BUGS/WISHLIST

B<REPORT BUGS!>
Report any bugs to the CPAN bug tracker.  Bug reports are adored.

To wishlist something, use the CPAN bug tracker (set as wishlist).
I'd be happy to implement useful functionality in this module on request.

=head1 PARTICIPATION

I'd be very, very happy to accept patches in diff format.  Or...

If you wish to hack on this code, please fork the git repository found at:
L<http://github.com/dustywilson/Linux--APT/>

If you have some goodness to push back to that repository, just use the
"pull request" button on the github site or let me know where to pull from.

=head1 THANKS

=over

=item Nicholas DeClario

=over

=item Patch to provide initial search function for version 0.02.

=back

=back

=head1 COPYRIGHT/LICENSE

Copyright 2009 Megagram.  You can use any one of these licenses: Perl Artistic, GPL (version >= 2), BSD.

=head2 Perl Artistic License

Read it at L<http://dev.perl.org/licenses/artistic.html>.
This is the license we prefer.

=head2 GNU General Public License (GPL) Version 2

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see http://www.gnu.org/licenses/

See the full license at L<http://www.gnu.org/licenses/>.

=head2 GNU General Public License (GPL) Version 3

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see http://www.gnu.org/licenses/

See the full license at L<http://www.gnu.org/licenses/>.

=head2 BSD License

  Copyright (c) 2009 Megagram.
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, are permitted
  provided that the following conditions are met:

      * Redistributions of source code must retain the above copyright notice, this list of conditions
      and the following disclaimer.
      * Redistributions in binary form must reproduce the above copyright notice, this list of conditions
      and the following disclaimer in the documentation and/or other materials provided with the
      distribution.
      * Neither the name of Megagram nor the names of its contributors may be used to endorse
      or promote products derived from this software without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
  PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
  INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
  OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
  IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

=cut

1;
