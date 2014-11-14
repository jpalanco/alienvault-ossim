Summary: ADOdb Python
Name: python-adodb
Version: 2.0.2
Release: 1
Source0: %{name}-%{version}.tar.gz
License: BSD
Group: Development/Libraries
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot
Prefix: %{_prefix}
BuildArch: noarch
Vendor: John Lim <jlim#natsoft.com.my>
Url: http://adodb.sourceforge.net/#pydownload

%description
Adodb libs for python python

%prep
%setup

%build
python setup.py build

%install
python setup.py install --root=$RPM_BUILD_ROOT
find $RPM_BUILD_ROOT -name '*.pyo' -exec rm -f {} ';'

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
/usr/lib
