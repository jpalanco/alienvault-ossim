package SOAP::Transport::HTTP::Daemon::ForkOnAccept;

use v5.10;
use strict;
use warnings;
use Scalar::Util qw(blessed);
use vars qw(@ISA);
use SOAP::Transport::HTTP;
use HTTP::Daemon::SSL;
use Carp;

@ISA = qw(SOAP::Transport::HTTP::Daemon);

sub handle {
    my $self = shift;

CLIENT:
    while ( 1 ) {
        my $c = $self->accept;

        carp 'socket: bad connection' and next
            unless defined $c;

        my $pid = fork;

        # We are going to close the new connection on one of two conditions
        #  1. The fork failed ($pid is undefined)
        #  2. We are the parent ($pid != 0)
        unless ( defined $pid && $pid == 0 ) {
            $c->close();
            next;
        }

        # From this point on, we are the child.
        $self->close();   # Close the listening socket (always done in children)

        HTTP::Daemon::ClientConn::SSL->start_SSL ($c,
                                                  SSL_server => 1,
                                                  Timeout            => 30,
                                                  #SSL_startHandshake => 0,
                                                  SSL_cipher_list    => 'HIGH',
                                                  SSL_server         => 1,
                                                  SSL_key_file       => '/etc/ssl/private/ssl-cert-snakeoil.key',
                                                  SSL_cert_file      => '/etc/ssl/certs/ssl-cert-snakeoil.pem',
            );

        if ( $c->HTTP::Daemon::ClientConn::SSL::opened() != 1 )
        {
            print "Handshake failed!!!\n";
        }

        # Handle requests as they come in
        while ( my $r = $c->get_request ) {
            $self->request($r);
            $self->SOAP::Transport::HTTP::Server::handle;
            $c->send_response( $self->response );
        }
        $c->close();
        return;
    }
}

1;
