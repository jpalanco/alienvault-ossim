package AV::Debian::Suricata;

use v5.10;
use strict;

use YAML::XS qw{LoadFile DumpFile};

use AV::ConfigParser;
use AV::Log;

sub update_config {
    my %config = AV::ConfigParser::current_config;

    my $suricata_file      = '/etc/suricata/suricata.yaml';
    my $suricata_initd     = '/etc/init.d/suricata';
    my $default_file       = '/etc/default/suricata';
    my $plugin_file        = '/etc/ossim/agent/plugins/suricata.cfg';
    my $fasttoggle         = 'no';
    my $httplogtoggle      = 'no';
    my $filelogtoggle      = 'yes';
    my $statstoggle        = 'no';
    my $filelogpath        = '/var/log/suricata/suricata.log';
    my $filename           = 'suricata';
    my @header             = ( '%YAML 1.1' );
    my $interface          = "$config{'sensor_interfaces'}";
    my $homenet            = "[$config{'sensor_networks'}]";
    my $externalnet        = '!$HOME_NET';
    my $tcpestablished     = 3600;
    my $stream_maxsessions = 262144;
    my @rulespro		        = (
        'emerging_pro-attack_response.rules',
        'emerging_pro-ciarmy.rules',
        'emerging_pro-current_events.rules',
        'emerging_pro-dns.rules',
        'emerging_pro-dos.rules',
        'emerging_pro-exploit.rules',
        'emerging_pro-ftp.rules',
        'emerging_pro-icmp_info.rules',
        'emerging_pro-imap.rules',
        'emerging_pro-info.rules',
        'emerging_pro-malware.rules',
        'emerging_pro-misc.rules',
        'emerging_pro-mobile_malware.rules',
        'emerging_pro-pop3.rules',
        'emerging_pro-rbn.rules',
        'emerging_pro-scada_special.rules',
        'emerging_pro-scan.rules',
        'emerging_pro-shellcode.rules',
        'emerging_pro-smtp.rules',
        'emerging_pro-sql.rules',
        'emerging_pro-telnet.rules',
        'emerging_pro-tftp.rules',
        'emerging_pro-trojan.rules',
        'emerging_pro-user_agents.rules',
        'emerging_pro-virus.rules',
        'emerging_pro-voip.rules',
        'emerging_pro-web_client.rules',
        'emerging_pro-worm.rules',
        'emerging_pro-decoder-events.rules',
        'emerging_pro-files.rules',
        'emerging_pro-http-events.rules',
        'emerging_pro-smtp-events.rules',
        'emerging_pro-stream-events.rules'
    );
    my @rulesdisabled           = (
        'emerging_pro-activex.rules',
        'emerging_pro-botcc.rules',
        'emerging_pro-chat.rules',
        'emerging_pro-compromised.rules',
        'emerging_pro-deleted.rules',
        'emerging_pro-drop.rules',
        'emerging_pro-dshield.rules',
        'emerging_pro-games.rules',
        'emerging_pro-icmp.rules',
        'emerging_pro-inappropriate.rules',
        'emerging_pro-netbios.rules',
        'emerging_pro-p2p.rules',
        'emerging_pro-policy.rules',
        'emerging_pro-rbn-malvertisers.rules',
        'emerging_pro-rpc.rules',
        'emerging_pro-scada.rules',
        'emerging_pro-snmp.rules',
        'emerging_pro-tor.rules',
        'emerging_pro-web_server.rules',
        'emerging_pro-web_specific_apps.rules'
    );

    my $suricata_conf = LoadFile($suricata_file);

    my $dg            = system ('dpkg -l | grep alienvault-10g-tools > /dev/null');
    $dg >>= 8;

    if ( $dg == 0 ) {
        console_log("Updating Suricata configuration");
        $suricata_conf->{'outputs'}->[0]->{'fast'}->{'enabled'}  = $fasttoggle;
        $suricata_conf->{'outputs'}->[0]->{'fast'}->{'filename'} = $filename;
        $suricata_conf->{'outputs'}->[2]->{'http-log'}->{'enabled'} = $httplogtoggle;
        $suricata_conf->{'outputs'}->[7]->{'stats'}->{'enabled'} = $statstoggle;
        $suricata_conf->{'logging'}->{'outputs'}->[1]->{'file'}->{'enabled'} = $filelogtoggle;
        $suricata_conf->{'logging'}->{'outputs'}->[1]->{'file'}->{'filename'} = $filelogpath;
        $suricata_conf->{'vars'}->{'HOME_NET'}     = $homenet;
        $suricata_conf->{'vars'}->{'EXTERNAL_NET'} = $externalnet;
        $suricata_conf->{'vars'}->{'address-groups'}->{'HOME_NET'} = $homenet;
        $suricata_conf->{'flow-timeouts'}->{'tcp'}->{'established'} = $tcpestablished;
        $suricata_conf->{'stream'}->{'max_sessions'} = $stream_maxsessions;

        delete $suricata_conf->{'pfring'};
        my $index = 0;
        $interface =~
          s/ //g;    #remove extra spaces between interfaces, if present
        my @interface_ary = split ',', $interface;
        for my $iface (@interface_ary) {
            $suricata_conf->{'pfring'}->[$index]->{'interface'} = $iface;
            $suricata_conf->{'pfring'}->[$index]->{'cluster-type'} = 'cluster_round_robin';
            $suricata_conf->{'pfring'}->[$index]->{'cluster-id'} = '99';
            $suricata_conf->{'pfring'}->[$index]->{'threads'}    = '1';
            $index++;
        }
	delete $suricata_conf->{'rule-files'};
	$index = 0;
        for my $rule (@rulespro) {
            $suricata_conf->{'rule-files'}->[$index] = $rule;
	    $index++;
        }
    push @header, "\n# Suricata Configuration optimized for Enterprise Sensor";
    }
    else {
        console_log("Updating Suricata configuration");
        $suricata_conf->{'outputs'}->[0]->{'fast'}->{'enabled'}  = $fasttoggle;
        $suricata_conf->{'outputs'}->[0]->{'fast'}->{'filename'} = $filename;
        $suricata_conf->{'outputs'}->[2]->{'http-log'}->{'enabled'} = $httplogtoggle;
        $suricata_conf->{'outputs'}->[7]->{'stats'}->{'enabled'} = $statstoggle;
        $suricata_conf->{'logging'}->{'outputs'}->[1]->{'file'}->{'enabled'} = $filelogtoggle;
        $suricata_conf->{'logging'}->{'outputs'}->[1]->{'file'}->{'filename'} = $filelogpath;
        $suricata_conf->{'vars'}->{'HOME_NET'}     = $homenet;
        $suricata_conf->{'vars'}->{'EXTERNAL_NET'} = $externalnet;
        $suricata_conf->{'vars'}->{'address-groups'}->{'HOME_NET'} = $homenet;

        delete $suricata_conf->{'pfring'};
        my $index = 0;
        $interface =~
          s/ //g;    #remove extra spaces between interfaces, if present
        my @interface_ary = split ',', $interface;
        for my $iface (@interface_ary) {
            $suricata_conf->{'pfring'}->[$index]->{'interface'} = $iface;
            $suricata_conf->{'pfring'}->[$index]->{'cluster-type'} = 'cluster_round_robin';
            $suricata_conf->{'pfring'}->[$index]->{'cluster-id'} = '99';
            $suricata_conf->{'pfring'}->[$index]->{'threads'}    = '1';
            $index++;
        }
    }

    DumpFile( "$suricata_file.2", $suricata_conf );

    open my $fh_input, '<', "$suricata_file.2"
      or warn "Can't read old file: $!";
    open my $fh_output, '>', "$suricata_file"
      or warn "Can't write new file: $!";

    say $fh_output @header;
    while (<$fh_input>) {
        print $fh_output $_;
    }

    close $fh_output;
    unlink "$suricata_file.2";

    my $command = "sed -i \"s:SURCONF=.*:SURCONF=/etc/suricata/suricata.yaml:\" $default_file";
    debug_log("$command");
    system($command);

    $command = "sed -i \"s/LISTENMODE=.*/LISTENMODE=pcap/\" $default_file";
    debug_log("$command");
    system($command);

    $command = "sed -i \"s/IFACE=.*/IFACE=$interface/\" $default_file";
    debug_log("$command");
    system($command);

    $command = "sed -i \"s/interface=.*/interface=$interface/\" $plugin_file";
    debug_log("$command");
    system($command);

    my @interface_ary = split ',', $interface;
    my $pfring_ifaces = join ( " --pfring=",@interface_ary) ;

    $command = qq{sed -i "s:^SURICATA_OPTIONS=.*:SURICATA_OPTIONS=\\\" -c \\\$SURCONF --pidfile \\\$PIDFILE -D --pfring=$pfring_ifaces\\\":" $suricata_initd};
    debug_log("$command");
    system($command);


}
1;
