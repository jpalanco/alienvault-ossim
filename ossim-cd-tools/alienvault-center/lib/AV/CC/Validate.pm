#
# License:
#
#  Copyright (c) 2011-2014 AlienVault
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

package AV::CC::Validate;

use v5.10;
use strict;
use warnings;

my %valid = (
    'OSS_ALPHA' => 'A-Za-z0-9',
    'OSS_DIGIT' => '0-9',
    'OSS_BINARY' => '0-1',
    'OSS_LETTER' => 'A-Za-z',
    'OSS_HEX' => '0-9A-Fa-f',
    'OSS_SPACE' => '\s',
    'OSS_SCORE' => '_\-',
    'OSS_DOT' => '\.',
    'OSS_COLON' => ':',
    'OSS_COMMA' => ',',
    'OSS_AT' => '@',
    'OSS_BRACKET' => '\[\]\{\}',
    'OSS_SLASH' => '\/',
    'OSS_NL' =>, '\r\n',
    'OSS_UUID' => '[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}',
    'OSS_IP_ADDR' => '(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}'
);
 
sub ossim_valid {

    my ( $str, $validate ) = @_;

    return 1 if ($str eq '' || $validate eq '');
    return 1 if ($str eq '' && $validate =~ /OSS_NULLABLE/);
    
    my @split           = split(',',$validate);
    if ($#split > 0)
    {
        # Composed valid characters
        my $validate_string = '[^';
        foreach my $v (@split)
        {
            $validate_string .= $valid{$v} if (defined($valid{$v}));
        }
        $validate_string .= ']';
    
        return 1 if ($validate_string eq '[^]' );
        
        return ($str =~ /$validate_string/) ? 0 : 1;
    }
    else
    {
        # Exact match
        return 0 if (!defined($valid{$split[0]}));
        
        my $validate_string = '^' . $valid{$split[0]} . '$';
        return ($str =~ /$validate_string/i) ? 1 : 0;
    }
}

1;
