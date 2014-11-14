Summary:   Basic Analysis and Security Engine
Name:      base
Version:   1.0.2
Release:   1
License:   GPL
Group:     Applications/Security
URL:       http://secureideas.sourceforge.net
Source0:   %{name}-%{version}.tar.gz
Requires: php

%description
BASE is the Basic Analysis and Security Engine. It is based on the code from  the Analysis Console for intrusion Database (ACID) project. This application provides a web front-end to query and analyze the alerts coming from a SNORT IDS system. 

%files
%defattr(-,apache,apache,0755)
%{_datadir}/base/
%{_datadir}/doc/base
%{_sysconfdir}/base/
%{_sysconfdir}/httpd/conf.d/base.conf

%post
ln -sf %{_datadir}/base/base_conf.php %{_sysconfdir}/base/


%changelog
* Wed Mar 30 2005 Juan Manuel Lorenzo <juanma at ossim.net>
- Initial build.
