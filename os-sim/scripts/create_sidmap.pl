#!/usr/bin/perl -w

# Modified by jmlorenzo at alienvault 29-10-09
# - Updated to the latest version found of create_sidmap.pl by Andreas Östling
# - Defaults classtype to misc-activity when no classtype is found on the rule
# 
# Originally modified by dkarg and dgil
#
# Copyright (c) 2004-2006 Andreas Östling <andreaso@it.su.se>
# All rights reserved.
#
#  Redistribution and use in source and binary forms, with or
#  without modification, are permitted provided that the following
#  conditions are met:
#
#  1. Redistributions of source code must retain the above
#     copyright notice, this list of conditions and the following
#     disclaimer.
#
#  2. Redistributions in binary form must reproduce the above
#     copyright notice, this list of conditions and the following
#     disclaimer in the documentation and/or other materials
#     provided with the distribution.
#
#  3. Neither the name of the author nor the names of its
#     contributors may be used to endorse or promote products
#     derived from this software without specific prior written
#     permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
# CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
# INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
# MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
# CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
# SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
# NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
# HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
# CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
# OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
# EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


use strict;

sub get_next_entry($ $ $ $ $ $);
sub parse_singleline_rule($ $ $);
sub update_ossim_db();

# Files to ignore.
my %skipfiles = (
    'deleted.rules' => 1,
);

# Regexp to match the start of a multi-line rule.
# %ACTIONS% will be replaced with content of $config{actions} later.
my $MULTILINE_RULE_REGEXP  = '^\s*#*\s*(?:%ACTIONS%)'.
                             '\s.*\\\\\s*\n$'; # ';

# Regexp to match a single-line rule.
my $SINGLELINE_RULE_REGEXP = '^\s*#*\s*(?:%ACTIONS%)'.
                             '\s.+;\s*\)\s*$'; # ';

my $USAGE = << "RTFM";

Parse active rules in *.rules in one or more directories and create a SID
map. Result is sent to standard output, which can be redirected to a
sid-msg.map file.

Usage: $0 <rulesdir> [rulesdir2, ...]

RTFM

my $verbose = 1;

my (%sidmap, %sidinfo, %config);

my @rulesdirs = @ARGV;

die($USAGE) unless ($#rulesdirs > -1);

$config{rule_actions} = "alert|drop|log|pass|reject|sdrop|activate|dynamic";

$SINGLELINE_RULE_REGEXP =~ s/%ACTIONS%/$config{rule_actions}/;
$MULTILINE_RULE_REGEXP  =~ s/%ACTIONS%/$config{rule_actions}/;

# Dump SQL. Default off.
# Be quiet. Default off.
my $dump = 0;
my $quiet = 1;


# suppress gen_id 1, sig_id 1852, track by_dst, ip 10.1.1.0/24
# Read in all rules from each rules file (*.rules) in each rules dir.
# into %sidmap.
foreach my $rulesdir (@rulesdirs) {
    opendir(RULESDIR, "$rulesdir") or die("could not open \"$rulesdir\": $!\n");

    while (my $file = readdir(RULESDIR)) {
        next unless ($file =~ /\.rules$/);
        next if ($skipfiles{$file});

        open(FILE, "$rulesdir/$file") or die("could not open \"$rulesdir/$file\": $!\n");
        my @file = <FILE>;
        close(FILE);

        my ($single, $multi, $nonrule, $msg, $sid);

        while (get_next_entry(\@file, \$single, \$multi, \$nonrule, \$msg, \$sid)) {
            if (defined($single)) {

                warn("WARNING: duplicate SID: $sid (discarding old)\n") if (exists($sidmap{$sid}));

                $sidmap{$sid} = "$sid || $msg";

              # Print all references. Borrowed from Brian Caswell's regen-sidmap script.
                my $ref = $single;
                while ($ref =~ s/(.*)reference\s*:\s*([^\;]+)(.*)$/$1 $3/) {
                    $sidmap{$sid} .= " || $2"
                }

                $sidmap{$sid} .= "\n";
	        
		my $ref2 = $single;
                if ($ref2 =~ /\(msg\s*:\s*([^\;]+)(.*)classtype\s*:\s*([^\;]+)/i) {
                    $sidinfo{$sid}{"msg"} = $1;
                    $sidinfo{$sid}{"classtype"} = $3;
                #Those rules that dont have a classtype will have misc-activity by default
		# Juan Manuel Lorenzo
		} elsif ($ref2 =~ /\(msg\s*:\s*([^\;]+)/i) {
                    $sidinfo{$sid}{"msg"} = $1;
                    $sidinfo{$sid}{"classtype"} = "misc-activity";
		}

                my $category = $file;
                $category =~ s/([^\.]+)\.rules/$1/;
                $sidinfo{$sid}{"category"} = $category;
            }
        }
    }
}

# Print results.
#foreach my $sid (sort { $a <=> $b } keys(%sidmap)) {
#    print "$sidmap{$sid}";
#}

update_ossim_db();

# Same as in oinkmaster.pl.
sub get_next_entry($ $ $ $ $ $)
{
    my $arr_ref     = shift;
    my $single_ref  = shift;
    my $multi_ref   = shift;
    my $nonrule_ref = shift;
    my $msg_ref     = shift;
    my $sid_ref     = shift;

    undef($$single_ref);
    undef($$multi_ref);
    undef($$nonrule_ref);
    undef($$msg_ref);
    undef($$sid_ref);

    my $line = shift(@$arr_ref) || return(0);
    my $disabled = 0;
    my $broken   = 0;

  # Possible beginning of multi-line rule?
    if ($line =~ /$MULTILINE_RULE_REGEXP/oi) {
        $$single_ref = $line;
        $$multi_ref  = $line;

        $disabled = 1 if ($line =~ /^\s*#/);

      # Keep on reading as long as line ends with "\".
        while (!$broken && $line =~ /\\\s*\n$/) {

          # Remove trailing "\" and newline for single-line version.
            $$single_ref =~ s/\\\s*\n//;

          # If there are no more lines, this can not be a valid multi-line rule.
            if (!($line = shift(@$arr_ref))) {

                warn("\nWARNING: got EOF while parsing multi-line rule: $$multi_ref\n")
                  if ($config{verbose});

                @_ = split(/\n/, $$multi_ref);

                undef($$multi_ref);
                undef($$single_ref);

              # First line of broken multi-line rule will be returned as a non-rule line.
                $$nonrule_ref = shift(@_) . "\n";
                $$nonrule_ref =~ s/\s*\n$/\n/;    # remove trailing whitespaces

              # The rest is put back to the array again.
                foreach $_ (reverse((@_))) {
                    unshift(@$arr_ref, "$_\n");
                }

                return (1);   # return non-rule
            }

          # Multi-line continuation.
            $$multi_ref .= $line;

          # If there are non-comment lines in the middle of a disabled rule,
          # mark the rule as broken to return as non-rule lines.
            if ($line !~ /^\s*#/ && $disabled) {
                $broken = 1;
            } elsif ($line =~ /^\s*#/ && !$disabled) {
                # comment line (with trailing slash) in the middle of an active rule - ignore it
            } else {
                $line =~ s/^\s*#*\s*//;  # remove leading # in single-line version
                $$single_ref .= $line;
            }

        } # while line ends with "\"

      # Single-line version should now be a valid rule.
      # If not, it wasn't a valid multi-line rule after all.
        if (!$broken && parse_singleline_rule($$single_ref, $msg_ref, $sid_ref)) {

            $$single_ref =~ s/^\s*//;     # remove leading whitespaces
            $$single_ref =~ s/^#+\s*/#/;  # remove whitespaces next to leading #
            $$single_ref =~ s/\s*\n$/\n/; # remove trailing whitespaces

            $$multi_ref  =~ s/^\s*//;
            $$multi_ref  =~ s/\s*\n$/\n/;
            $$multi_ref  =~ s/^#+\s*/#/;

            return (1);   # return multi
        } else {
            warn("\nWARNING: invalid multi-line rule: $$single_ref\n")
              if ($config{verbose} && $$multi_ref !~ /^\s*#/);

            @_ = split(/\n/, $$multi_ref);

            undef($$multi_ref);
            undef($$single_ref);

          # First line of broken multi-line rule will be returned as a non-rule line.
            $$nonrule_ref = shift(@_) . "\n";
            $$nonrule_ref =~ s/\s*\n$/\n/;   # remove trailing whitespaces

          # The rest is put back to the array again.
            foreach $_ (reverse((@_))) {
                unshift(@$arr_ref, "$_\n");
            }

            return (1);   # return non-rule
        }
     } elsif (parse_singleline_rule($line, $msg_ref, $sid_ref)) {
        $$single_ref = $line;
        $$single_ref =~ s/^\s*//;
        $$single_ref =~ s/^#+\s*/#/;
        $$single_ref =~ s/\s*\n$/\n/;

        return (1);   # return single
    } else {                          # non-rule line

      # Do extra check and warn if it *might* be a rule anyway,
      # but that we just couldn't parse for some reason.
        warn("\nWARNING: line may be a rule but it could not be parsed ".
             "(missing sid or msg?): $line\n")
          if ($config{verbose} && $line =~ /^\s*alert .+msg\s*:\s*".+"\s*;/);

        $$nonrule_ref = $line;
        $$nonrule_ref =~ s/\s*\n$/\n/;

        return (1);   # return non-rule
    }
}



# Same as in oinkmaster.pl.
sub parse_singleline_rule($ $ $)
{
    my $line    = shift;
    my $msg_ref = shift;
    my $sid_ref = shift;

    if ($line =~ /$SINGLELINE_RULE_REGEXP/oi) {

        if ($line =~ /\bmsg\s*:\s*"(.+?)"\s*;/i) {
            $$msg_ref = $1;
        } else {
            return (0);
        }

        if ($line =~ /\bsid\s*:\s*(\d+)\s*;/i) {
            $$sid_ref = $1;
        } else {
            return (0);
        }

        return (1);
    }

    return (0);
}
sub get_category_id($ $)
{
    (my $conn, my $name) = @_;

    my $query = "SELECT * FROM category WHERE name = '$name'";
    my $stm = $conn->prepare($query);
    $stm->execute();

    my $row = $stm->fetchrow_hashref;
    if(!exists($row->{"id"})) {
        return 117; # misc
    }
    $stm->finish();

    return $row->{"id"};
}

sub get_class_info($ $)
{
    (my $conn, my $name) = @_;

    if(!defined($name)){
        my @info = (102,3);
        return \@info;
    } else {
        my $query = "SELECT * FROM classification WHERE name = '$name'";
        my $stm = $conn->prepare($query);
        $stm->execute();

        my $row = $stm->fetchrow_hashref;

        my @info = ($row->{"id"},
                    $row->{"priority"},$row->{"description"});
        $stm->finish();
        return \@info;
    }
}

sub update_ossim_db()
{
    use DBI;
    use ossim_conf;


    #
    #  OSSIM db connect
    #
    my $dsn = "dbi:" .
        $ossim_conf::ossim_data->{"ossim_type"} . ":" .
        $ossim_conf::ossim_data->{"ossim_base"} . ":" .
        $ossim_conf::ossim_data->{"ossim_host"} . ":" .
        $ossim_conf::ossim_data->{"ossim_port"} . ":";

    my $conn = DBI->connect($dsn,
        $ossim_conf::ossim_data->{"ossim_user"},
        $ossim_conf::ossim_data->{"ossim_pass"})
        or die "Can't connect to Database\n";

    #
    # Rel/Prio rules
    #
    my %rel_rules = ();
    my %prio_rules = ();
    my %exceptions = ();
    my %rel_classification_rules = ();
    my %prio_classification_rules = ();
    
    #$rel_rules{"trojan|malware"} = 2;
    #$prio_rules{"trojan|malware"} = 2;
    #$exceptions{"300"}++;
    #$rel_classification_rules{"Denial of Service"} = 3;
    #$prio_classification_rules{"Denial of Service"} = 3;
    
    #
    #  get all snort rules from ossim db 
    #  and store them in %db_sids hash table
    #
    my $query = "SELECT * FROM plugin_sid WHERE plugin_id = 1001 ORDER BY sid"; # Ignore context for the moment
    my $stm = $conn->prepare($query);
    $stm->execute();

    my %db_sids;
    while (my $row = $stm->fetchrow_hashref) {
        $db_sids{$row->{"sid"}} = $row;
    }
    $stm->finish();

    #
    my %groups_rules = ();
    my %plugin_groups_sids = ();
    my %plugin_groups_id = ();
    
    #$groups_rules{"backdoor"} = "BD;Test BD";
    
    #
    #  get all plugin groups from ossim db 
    #  and store them in %plugin_groups_id and %plugin_groups_sids hash tables
    #
    $query = "SELECT g.name, hex(g.group_id) as group_id, gd.plugin_sid
                    FROM plugin_group_descr AS gd
                    LEFT JOIN plugin_group AS g ON g.group_id = gd.group_id
                    WHERE gd.plugin_id =1001";
    $stm = $conn->prepare($query);
    $stm->execute();

    while (my $row = $stm->fetchrow_hashref) {
        $plugin_groups_id{$row->{"name"}} = $row->{"group_id"};
        my @tmp = split(/\,/,$row->{"plugin_sid"});
        foreach my $sid (@tmp) {
            $plugin_groups_sids{$row->{"name"}}{$sid} = 1;
        }
    }
    $stm->finish();
    foreach my $sid (sort { $a <=> $b } keys(%sidinfo)) {
        my $msg = $sidinfo{$sid}{"msg"};
        if(!defined($msg)){ $msg = "Undefined msg, please check"; }
        $msg =~ s/\\/\\\\/g;
        $msg =~ s/\'/\\\'/g;
        $msg =~ s/\-\-/\-/g; # sql comments (s/--/-)
        # For creation and update of plugins groups
        foreach my $rl (keys %groups_rules) {
            if ($msg =~ /$rl/i) {
                my @tmp = split(/\;/,$groups_rules{$rl});
                my $group_name = $tmp[0];
                my $group_description = $tmp[1];
                if(defined($plugin_groups_id{$group_name}) && !defined($plugin_groups_sids{$group_name}{$sid})){ # update plugin group
                    my $query = "UPDATE plugin_group_descr SET plugin_sid=CONCAT(plugin_sid,',','$sid') WHERE plugin_id=1001 AND group_id=unhex('".$plugin_groups_id{$group_name}."')";
                        my $stm = $conn->prepare($query);
                        $stm->execute();
                        $stm->finish();
                        
                        # save sid in plugin_groups_sids hash table
                        $plugin_groups_sids{$group_name}{$sid} = 1;
                }
                elsif (!defined($plugin_groups_id{$group_name})) { # create new plugin group
                    my $group_id = genUUID($conn);
                    my $query = "INSERT INTO plugin_group (group_id, group_ctx, name, descr) VALUES (unhex('$group_id'), 0x0, '$group_name', '$group_description')";
                    my $stm = $conn->prepare($query);
                    $stm->execute();
                    $stm->finish();
                    $query = "INSERT INTO plugin_group_descr (group_id, group_ctx, plugin_id, plugin_ctx, plugin_sid) VALUES (unhex('$group_id'), 0x0, 1001, 0x0, $sid)";
                    $stm = $conn->prepare($query);
                    $stm->execute();
                    $stm->finish();
                    
                    # save group_name and sid in plugin_groups_id and plugin_groups_sids hash tables
                    $plugin_groups_id{$group_name} = $group_id;
                    $plugin_groups_sids{$group_name}{$sid} = 1;
                }
                last;
            }
        }
        if (not exists($db_sids{$sid})){
            my $category_id =
                get_category_id($conn, $sidinfo{$sid}{"category"});
            my $info =
                get_class_info ($conn, $sidinfo{$sid}{"classtype"});
            my ($class_id, $priority, $description) = (${$info}[0], ${$info}[1], ${$info}[2]);
            my $reliability = 1;
            #
            # Modify reliability and/or priority with first matched rule
            my $rasigned = 0;
            my $pasigned = 0;
            if (!$exceptions{$sid}) {
                foreach my $rl (sort {$rel_classification_rules{$b}>=$rel_classification_rules{$a}} (keys %rel_classification_rules)) {
                    if ( $sidinfo{$sid}{"classtype"} =~ m/$rl/i || $description =~ m/$rl/i ) {
                        $reliability = $rel_classification_rules{$rl};
                        $rasigned = 1;
                        last;
                    }
                }
                if (!$rasigned) {
                    foreach my $rl (sort {$rel_rules{$b}>=$rel_rules{$a}} (keys %rel_rules)) {
                        if ($msg =~ m/$rl/i) {
                            $reliability = $rel_rules{$rl};
                            last;
                        }
                    }
                }
                foreach my $rl (sort {$prio_classification_rules{$b}>=$prio_classification_rules{$a}} (keys %prio_classification_rules)) {
                    if ($sidinfo{$sid}{"classtype"} =~ m/$rl/i || $description =~ m/$rl/i ) {
                        $priority = $prio_classification_rules{$rl};
                        $pasigned = 1;
                        last;
                    }
                }
                if (!$pasigned) {
                    foreach my $rl (sort {$prio_rules{$b}>=$prio_rules{$a}} (keys %prio_rules)) {
                        if ($msg =~ m/$rl/i) {
                            $priority = $prio_rules{$rl};
                            last;
                        }
                    }
                }
            }
            #
            my $query = "INSERT INTO plugin_sid (plugin_id, plugin_ctx, sid, category_id, class_id, name, reliability, priority) VALUES (1001, 0x0, $sid, $category_id, $class_id, 'snort: $msg', $reliability, $priority)";

            if($dump){
                print "$query\n";
            } else {
                my $stm = $conn->prepare($query);
                $stm->execute();
                $stm->finish();
                print "Inserting $msg: [1001:$sid:$reliability:$priority]\n" unless ($quiet);
            }

        }
    }

    # update reference, reference_system and sig_reference
    # preload reference_system
    print "Loading from reference_system...";
    my %ref_system_ids = ();
    $query = "SELECT ref_system_id,ref_system_name FROM alienvault_siem.reference_system";
    $stm = $conn->prepare($query);
    $stm->execute();
    while (my $row = $stm->fetchrow_hashref) {
        my $rname = $row->{"ref_system_name"}; $rname =~ s/^ *| *$//g;
        $ref_system_ids{$rname} = $row->{"ref_system_id"};
    }
    $stm->finish();
    # preload references
    print "done\nLoading from references...";
    my %ref_ids = ();
    $query = "SELECT ref_id,ref_system_id,ref_tag FROM alienvault_siem.reference";
    $stm = $conn->prepare($query);
    $stm->execute();
    while (my $row = $stm->fetchrow_hashref) {
        $ref_ids{$row->{"ref_system_id"}}{$row->{"ref_tag"}} = $row->{"ref_id"};
    }
    $stm->finish();
    print "done\n";
    #
    my $plugin_id = 1001;
    my $values = "";
    print "Updating sidmap";
    foreach my $sid (sort { $a <=> $b } keys(%sidmap)) {
        print "Plugin_id $plugin_id, sid $sid\n" unless ($quiet);
        chop $sidmap{$sid};
        my (@sig_data) = split(/\|\|/, $sidmap{$sid});
        my $i = 0;

        foreach my $detail (@sig_data) {
            if ($i>1) {
                print ".";
                if ($detail =~ /,/) {
                    my (@ref) = split(/,/, $detail);
                    $ref[0] = lc $ref[0]; $ref[0] =~ s/^ *| *$//g;
                    if (not exists($ref_system_ids{$ref[0]})) {
                        $ref_system_ids{$ref[0]} = new_reference_system($conn,$ref[0]);
                    }
                    my $ref_system_id = $ref_system_ids{$ref[0]};
                    if (not exists($ref_ids{$ref_system_id}{$ref[1]})) {
                        $ref_ids{$ref_system_id}{$ref[1]} = new_reference($conn,$ref_system_id,$ref[1]);
                    }
                    my $ref_id = $ref_ids{$ref_system_id}{$ref[1]};
                    $values .= ",(0x0,$plugin_id,$sid,$ref_id)";
                }
            }
            $i++;
        }
        print "------------------\n" unless ($quiet);
    }
    print "\n";
    if ($values ne "") {
        print "Insert into sig_reference...";
        $values =~ s/^\,//;
        $query = "INSERT IGNORE INTO alienvault_siem.sig_reference (ctx,plugin_id,plugin_sid,ref_id) VALUES $values";
        $stm = $conn->prepare($query);
        $stm->execute();
        $stm->finish();
        print "done\n";
    }
    $query = "delete from alienvault_siem.reference where ref_tag like '%whitehats%'";
    $stm = $conn->prepare($query);
    $stm->execute();
    $stm->finish();
    $conn->disconnect();
}

sub new_reference($ $ $)
{
	(my $conn, my $ref_system_id, my $tag) = @_;
    $tag = quotemeta $tag;
	my $query = "INSERT INTO alienvault_siem.reference (ref_system_id,ref_tag) VALUES ($ref_system_id,'$tag')";
	my $stm = $conn->prepare($query);
	$stm->execute();
	$stm->finish();
	# get last id
	$query = "SELECT LAST_INSERT_ID() as last_id";
	$stm = $conn->prepare($query);
	$stm->execute();
	my $row = $stm->fetchrow_hashref;
	my $ref_id = $row->{"last_id"};
	$stm->finish();
	return $ref_id;
}

sub new_reference_system($ $)
{
	(my $conn, my $ref) = @_;
	# insert new
	my $query = "INSERT INTO alienvault_siem.reference_system (ref_system_name) VALUES ('$ref')";
	my $stm = $conn->prepare($query);
	$stm->execute();
	$stm->finish();
	# get last id
	$query = "SELECT LAST_INSERT_ID() as last_id";
	$stm = $conn->prepare($query);
	$stm->execute();
	my $row = $stm->fetchrow_hashref;
	my $ref_system_id = $row->{"last_id"};
	$stm->finish();
	return $ref_system_id;
}
sub genID ($ $){
    (my $dbh, my $table) = @_;
    
    my $sth_lastid;
    my $stm;
    
    my $query = "UPDATE $table SET id=LAST_INSERT_ID(id+1)";
    $stm = $dbh->prepare($query);
    $stm->execute();
    $stm->finish();
    
    my $last_id_query = "SELECT LAST_INSERT_ID() as lastid";
    $sth_lastid = $dbh->prepare($last_id_query);
    $sth_lastid->execute;
    my ($last_id) = $sth_lastid->fetchrow_array;
    $sth_lastid->finish;
    return $last_id;
}
sub genUUID ($){
    (my $dbh) = @_;
    
    my $sth_lastid;    
    my $last_id_query = "SELECT REPLACE(UUID(),'-','')";
    $sth_lastid = $dbh->prepare($last_id_query);
    $sth_lastid->execute;
    my ($last_id) = $sth_lastid->fetchrow_array;
    $sth_lastid->finish;
    return $last_id;
}
