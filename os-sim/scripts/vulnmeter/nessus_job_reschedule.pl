#!/usr/bin/perl


use strict;
use warnings;

use DBI;

use Date::Calc qw(:all);
use DateTime;


my $dbhost = `grep ^ossim_host= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbhost);
my $dbuser = `grep ^ossim_user= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep ^ossim_pass= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbpass);


my %CONFIG = ();

$CONFIG{'DATABASENAME'} = "alienvault";
$CONFIG{'DATABASEHOST'} = $dbhost;
$CONFIG{'DATABASEDSN'} = "DBI:mysql";
$CONFIG{'DATABASEUSER'} = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;


### MAIN BEGIN ###

my $schedid = $ARGV[0];

#year of the next scan
my $next_check_year = "";
#month of the next scan
my $next_check_month = "";
#first day of the month of the next scan
my $next_check_origin_datetime = "";
#if there is a new check time
my $find = 0;
#Number of months to be tested
my $i = 0;

#connection to the database
my ( $dbh, $sth, $sql );

$dbh = conn_db();

# Retrieve all required information for scan re-scheduling including user timezone
# We perform a left join and including fk_name as key to join in order to prevent:
# 1º Remove users which could be problematic. In this case, since next_CHECK is already in UTC we can use to calculate the next_check
# 2º Advance option > send email to an entity => the field v.username will be an Entity UUID so it requires fk_name as well
$sql = qq{
    SELECT begin,
           substr(next_CHECK,-6) AS scan_time,
           DATE(next_CHECK),
           day_of_week-1,
           day_of_month,
           next_CHECK,
           timezone
    FROM vuln_job_schedule v
        LEFT JOIN users u ON v.username=u.login COLLATE utf8_unicode_ci OR v.fk_name=u.login COLLATE utf8_unicode_ci
    WHERE id='$schedid';
};

$sth=$dbh->prepare( $sql );
$sth->execute;

my ( $bd_begin, $scan_time, $db_next_check, $db_day_of_week, $db_week_of_month, $db_next_CHECK, $timezone ) = $sth->fetchrow_array;
$sth->finish;

if ( !defined($bd_begin)         ||
     !defined($db_next_check)    ||
     !defined($scan_time)        ||
     !defined($db_day_of_week)   ||
     !defined($db_week_of_month) ||
     !defined($db_next_CHECK)
   )
{
    die "Failed to fetch from the DB all required scheduling data for schedid $schedid!";
}

my $scan_hour = "";
my $scan_minute = "";
my $tz = "";

#if there is no timezone means the user is erased and as far the db_next_CHECK is already in UTC time zone we can
#fixe time zone as UTC and time retrieve from this also the day when the scan should happen
if ( !defined($timezone) ){
    $scan_hour = int (substr $db_next_CHECK, 8, 2);
    $scan_minute = int (substr $db_next_CHECK, 10, 2);
    $tz = DateTime::TimeZone->new( name => "UTC" );

    #the db_day_of_week is calculated from db_next_CHECK in order to apply the UTC conversion possible changes
    my $tmp_datetime = DateTime->new(
                        year       => int (substr $db_next_CHECK, 0, 4),
                        month      => int (substr $db_next_CHECK, 4, 2),
                        day        => int (substr $db_next_CHECK, 6, 2)
                      );
    $db_day_of_week = $tmp_datetime->day_of_week;
}
else{
    $scan_hour = int (substr $scan_time, 0, 2);
    $scan_minute = int (substr $scan_time, 2, 2);
    $tz = DateTime::TimeZone->new( name => $timezone );
}

my $begin_date = DateTime->new(
  year       => int(substr $bd_begin, 0, 4),
  month      => int(substr $bd_begin, 4, 2),
  day        => int(substr $bd_begin, 6, 2),
  hour       => $scan_hour,
  minute     => $scan_minute
);

$next_check_year = int((split /-/, $db_next_check)[0]);
$next_check_month = int((split /-/, $db_next_check)[1]);

$next_check_origin_datetime = DateTime->new(
  year       => $next_check_year,
  month      => $next_check_month,
  day        => 1,
  hour       => $scan_hour,
  minute     => $scan_minute
);

# Start looking for the next scan time one month after
$next_check_origin_datetime = $next_check_origin_datetime + DateTime::Duration->new( months => 1 );

my $next_check_origin_datetime_last_day = DateTime->last_day_of_month(
    year       => $next_check_origin_datetime->year,
    month      => $next_check_origin_datetime->month,
    hour       => $scan_hour,
    minute     => $scan_minute
);


# Variable to count the number of days to be added from the first day of the next month
my $next_check = "";

# Look for a date which matches with the criteria in the next 12 months.
# If not the scan will be scheduled for the first day of the last checked month.
while (! $find && $i < 12) {
    # Calculating how many days should be added to the first day of the month for the next scan
    $next_check = ($db_day_of_week - $next_check_origin_datetime->day_of_week) % 7 + 7 * ($db_week_of_month - 1);

    # Save current nect_check for later checks
    my $previous_next_check_origin_datetime = $next_check_origin_datetime;
    $next_check_origin_datetime = $next_check_origin_datetime + DateTime::Duration->new( days => $next_check );

    #if the calculate date is within the month we have found it
    if ($next_check_origin_datetime_last_day >= $next_check_origin_datetime &&
        $begin_date <= $next_check_origin_datetime)
    {
        $find = 1
    }
    else {
        $next_check_origin_datetime = DateTime->new(
            year       => $next_check_origin_datetime->year,
            month      => $next_check_origin_datetime->month,
            day        => 1,
            hour       => $scan_hour,
            minute     => $scan_minute
        );
        # If the next_check data has not move forward force it
        if ($previous_next_check_origin_datetime->month == $next_check_origin_datetime->month)
        {
            $next_check_origin_datetime = $next_check_origin_datetime + DateTime::Duration->new( months => 1 );
        }

        $next_check_origin_datetime_last_day = DateTime->last_day_of_month(
            year       => $next_check_origin_datetime->year,
            month      => $next_check_origin_datetime->month,
            hour       => $scan_hour,
            minute     => $scan_minute
        );
    }
    $i = $i + 1;
}

# If the user is not in UTC adjust DB stored date to UTC
if ( ! $tz->is_utc() )
{
    my ( $offset ) = $tz->offset_for_datetime( $next_check_origin_datetime );
    my ( $localtime_next_check ) = $next_check_origin_datetime + DateTime::Duration->new( seconds => $offset );

    if ( $localtime_next_check->day_of_year() > $next_check_origin_datetime->day_of_year() )
    {
        $next_check_origin_datetime = $next_check_origin_datetime - DateTime::Duration->new( days => 1 );
    }
    elsif ( $localtime_next_check->day_of_year() < $next_check_origin_datetime->day_of_year() )
    {
        $next_check_origin_datetime = $next_check_origin_datetime + DateTime::Duration->new( days => 1 );
    }
}
$db_next_CHECK = $next_check_origin_datetime->ymd("")."$scan_time";

$sql = qq{ UPDATE vuln_job_schedule SET next_CHECK = '$db_next_CHECK' WHERE id='$schedid'; };
$sth=$dbh->prepare( $sql );
$sth->execute;
$sth->finish;


disconn_db($dbh);
exit 0;

### MAIN END ###

### PROCEDURES ###

sub conn_db {
    $dbh = DBI->connect("$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'}:$CONFIG{'DATABASEHOST'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");

    $sql = qq{ SET SESSION time_zone='+00:00' };

    safe_db_write ( $sql, 5 );

    return $dbh;
}

#disconnect from db
sub disconn_db {
    my ( $dbh ) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}


#safe write code to help prevent complete job failure
sub safe_db_write {
    my ( $sql_insert, $specified_level ) = @_;

    if ( check_dbOK() == "0" ) { $dbh = conn_db(); }

    eval {
        $dbh->do( $sql_insert );
    };
    warn "[$$] Test connection - Failed\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }
}

#check db is up
sub check_dbOK {
    my $sql = "SELECT count( hostname ) FROM vuln_nessus_servers WHERE 1";

    eval {
            $dbh->do( $sql );
    };

    warn "[$$] Test connection - Failed\n" . $dbh->errstr . "\n\n" if ($@);
    if ( $@ ) { return 0; }
    return 1;
}

#EOF
