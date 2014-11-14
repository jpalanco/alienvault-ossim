Summary:   PDF Generator library for PHP
Name:      php-fpdf
Version:   1.53
Release:   1
License:   Freeware
Group:     System Environment/Libraries
URL:       http://www.fpdf.org/
BuildArch: noarch
Source:   %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot

%description
FPDF is a PHP class which allows to generate PDF files with pure PHP,
that is to say without using the PDFlib library. F from FPDF stands
for Free: you may use it for any kind of usage and modify it to suit
your needs.

%prep
%setup -q
%install
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/php/fpdf
%{__cp} -r * $RPM_BUILD_ROOT/usr/share/php/fpdf

%files
%defattr(-,root,root,0755)
/

%clean
rm -rf $RPM_BUILD_ROOT

%changelog
* Tue Oct 23 2007 Tomas V.V.Cox <tvvcox@ossim.net> 3.3.7
- Initial .spec release
