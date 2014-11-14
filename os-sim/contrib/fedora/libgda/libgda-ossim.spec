# Default provider build options (MySQL, Postgres & unixODBC)
#
# Package build options:
# --with tds
# --with db2
# --with oracle
# --with sqlite
# --with sybase
# --with mdb
# --with ldap
# --with firebird
# --without mysql
# --without odbc
# --without postgres
#

%define           FREETDS  0
%define           IBMDB2   0
%define           MYSQL    1
%define           ODBC     0
%define           ORACLE   0
%define           POSTGRES 0
%define           SQLITE   0
%define           SYBASE   0
%define 	  MDB	   0
%define		  LDAP	   0
%define		  FIREBIRD 0

%{?_with_tds:%define FREETDS 	1}
%{?_with_db2:%define IBMDB2 	1}
%{?_with_ldap:%define LDAP 	1}
%{?_with_mdb:%define MDB 	1}
%{?_with_oracle:%define ORACLE 	1}
%{?_with_sqlite:%define SQLITE 	1}
%{?_with_sybase:%define SYBASE 	1}
%{?_with_firebird:%define FIREBIRD  1}
%{?_without_mysql:%define MYSQL 0}
%{?_without_odbc:%define ODBC 	0}
%{?_without_postgres:%define POSTGRES 	0}

Summary:          Library for writing gnome database programs
Name:             libgda-ossim
Version:          1.2.3
Release:          1
Epoch:		  1
Source:           %{name}-%{version}.tar.gz
URL:              http://www.gnome-db.org/
Group:            System Environment/Libraries
License:          LGPL
BuildRoot:        %{_tmppath}/%{name}-%{version}-root
BuildRequires:    pkgconfig >= 0.8
Requires:         glib2 >= 2.0.0
Requires:         libxml2
Requires:         libxslt >= 1.0.9
Requires:	  ncurses
BuildRequires:    glib2-devel >= 2.0.0
BuildRequires:    libxml2-devel
BuildRequires:    libxslt-devel >= 1.0.9
BuildRequires:    ncurses-devel
BuildRequires:    scrollkeeper
BuildRequires:    groff
BuildRequires:    readline-devel

# Must begin with "/"
%define     OSSIM_PREFIX /usr/share/ossim/libgda-%{version}


%if %{FREETDS}
BuildRequires:    freetds-devel
%endif

%if %{MYSQL}
BuildRequires:    mysql-devel
%endif

%if %{POSTGRES}
BuildRequires:    postgresql-devel
%endif

%if %{ODBC}
BuildRequires:    unixODBC-devel
%endif

%if %{SQLITE}
BuildRequires:	  sqlite-devel
%endif

%if %{MDB}
BuildRequires:	  mdbtools-devel
%endif

%if %{LDAP}
BuildRequires:	  openldap-devel
%endif

%description
libgda is a library that eases the task of writing
gnome database programs.

%prep
%setup -q

%build
%if %{FIREBIRD}
CONFIG="$CONFIG --with-firebird"
%else
CONFIG="$CONFIG --without-firebird"
%endif

%if %{FREETDS}
CONFIG="$CONFIG --with-tds"
%else
CONFIG="$CONFIG --without-tds"
%endif

%if %{IBMDB2}
CONFIG="$CONFIG --with-ibmdb2"
%else
CONFIG="$CONFIG --without-ibmdb2"
%endif

%if %{MYSQL}
CONFIG="$CONFIG --with-mysql"
%else
CONFIG="$CONFIG --without-mysql"
%endif

%if %{POSTGRES}
CONFIG="$CONFIG --with-postgres"
%else
CONFIG="$CONFIG --without-postgres"
%endif

%if %{ODBC}
CONFIG="$CONFIG --with-odbc"
%else
CONFIG="$CONFIG --without-odbc"
%endif

%if %{ORACLE}
CONFIG="$CONFIG --with-oracle"
%else
CONFIG="$CONFIG --without-oracle"
%endif

%if %{SQLITE}
CONFIG="$CONFIG --with-sqlite"
%else
CONFIG="$CONFIG --without-sqlite"
%endif

%if %{SYBASE}
CONFIG="$CONFIG --with-sybase"
%else
CONFIG="$CONFIG --without-sybase"
%endif

%if %{MDB}
CONFIG="$CONFIG --with-mdb"
%else
CONFIG="$CONFIG --without-mdb"
%endif

%if %{LDAP}
CONFIG="$CONFIG --with-ldap"
%else
CONFIG="$CONFIG --without-ldap"
%endif

./configure --prefix=%{OSSIM_PREFIX} --libdir=%{OSSIM_PREFIX}/lib $CONFIG --disable-gtk-doc
make %{?_smp_mflags}

%install
rm -rf %{buildroot}
make install DESTDIR=%{buildroot}

# Cleanup unnecessary, unpackaged files
rm -f %{buildroot}%{OSSIM_PREFIX}/lib/*.{a,la}
rm -f %{buildroot}%{OSSIM_PREFIX}/lib/libgda/providers/*.{a,la}
rm -rf %{buildroot}%{OSSIM_PREFIX}/share/gtk-doc

%post
echo "%{OSSIM_PREFIX}/lib" > /etc/ld.so.conf.d/%{name}.conf
/sbin/ldconfig

%postun
rm -f /etc/ld.so.conf.d/%{name}.conf
/sbin/ldconfig

%clean
rm -rf %{buildroot}

%files
%{OSSIM_PREFIX}

%changelog
* Wed Sep 26 2007 Tomas V.V.Cox <tvvcox@ossim.net>
First .spec release (based on the original libgda.spec file found in the libgda-1.2.3.tar.gz source package)
