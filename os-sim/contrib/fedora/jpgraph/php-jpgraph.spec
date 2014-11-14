Summary:   JpGraph is a Object-Oriented Graph creating library for PHP
Name:      php-jpgraph
Version:   2.2
Release:   1
License:   QPL
Group:     System Environment/Libraries
URL:       http://www.aditus.nu/jpgraph/
Source:   %{name}-%{version}.tar.gz
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot
Requires:  php >= 5.1 php-gd

%description
JpGraph is a Object-Oriented Graph creating library for PHP

%prep
%setup -q
%install
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/php/jpgraph
%{__mv} src/*.php $RPM_BUILD_ROOT/usr/share/php/jpgraph
%{__mv} src/*.dat $RPM_BUILD_ROOT/usr/share/php/jpgraph
%{__mv} src/lang $RPM_BUILD_ROOT/usr/share/php/jpgraph

%files
%defattr(-,root,root,0755)
%doc QPL.txt README VERSION docs src/Examples
/usr/share/php/jpgraph

%clean
rm -rf $RPM_BUILD_ROOT

%changelog
* Tue Oct 23 2007 Tomas V.V.Cox <tvvcox@ossim.net> 3.3.7
- Initial .spec release
