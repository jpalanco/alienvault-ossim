Summary:   Generic Access Control Lists
Name:      phpgacl
Version:   3.3.7
Release:   1
License:   LGPL
Group:     System Environment/Libraries
URL:       http://phpgacl.sourceforge.net
Source:    %{name}-%{version}.tar.gz
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot

%description
Generic Access Control Lists for php

%prep
%setup -q
%install
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/php/phpgacl
%{__cp} -r * $RPM_BUILD_ROOT/usr/share/php/phpgacl

%files
/

%clean
rm -rf $RPM_BUILD_ROOT

%changelog
* Tue Oct 23 2007 Tomas V.V.Cox <tvvcox@ossim.net> 3.3.7
- Initial .spec release
