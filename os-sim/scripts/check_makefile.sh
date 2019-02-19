#!/bin/bash
# Script for basic analyse of makefile.am and configure.ac dependencies
# Check misisng files and directories
# Run this script against your local CVS copy
#
# Sample usage : ./check_makefile.sh /home/laurent/workspace/OSSIM/os-sim
#
# Laurent Licour 24/04/07   llicour@ossim.net


function check_missing_makefile()
{

echo
echo "[*] Checking for missing Makefile.am"

echo "$MISSING_MAKEFILE_EXCLUSION" | grep -v "^$" > $TMPFILE

res=`find . -type d | cut -c 3- | grep -v "CVS$" | grep -v -f $TMPFILE` 
for dir in $res
do
  if [ ! -f "$dir/Makefile.am" ]
  then
    echo $dir
  fi
done

}


function check_missing_file()
{

echo
echo "[*] Checking for missing files in Makefile.am"

echo "$MISSING_FILE_EXCLUSION" | grep -v "^$" > $TMPFILE

res=`find . -name "Makefile.am"`
for file in $res
do
  dir=`dirname $file`
  res2=`find $dir -mindepth 1 -maxdepth 1 -type f | cut -c 3- | grep -v "Makefile.am" | grep -v -f $TMPFILE`
  for file2 in $res2
  do
    file3=`basename $file2`
    grep -q "\b$file3\b" $file
    if [ $? -ne 0 ]
    then
      echo "$file2"
    fi
  done
done

}


function check_missing_dir()
{

echo
echo "[*] Checking for missing directory in Makefile.am"

echo "$MISSING_DIR_EXCLUSION" | grep -v "^$" > $TMPFILE

res=`find . -name "Makefile.am"` 
for file in $res
do
  dir=`dirname $file`
  res2=`find $dir -mindepth 1 -maxdepth 1 -type d | cut -c 3- | grep -v "CVS$" | grep -v -f $TMPFILE`
  for dir2 in $res2
  do
    dir3=`basename $dir2`
    grep -q "\b$dir3\b" $file
    if [ $? -ne 0 ]
    then
      echo "$dir2"
    fi
  done
done

}


function check_missing_makefile_configure()
{

echo
echo "[*] Checking for missing Makefile in configure.ac"

echo "$MISSING_MAKEFILE_CONFIGURE_EXCLUSION" | grep -v "^$" > $TMPFILE

CONF="$DEST/configure.ac"

if [ ! -f $CONF ]
then
  echo "-> Fichier $CONF absent"
  return
fi

res=`find . -name "Makefile.am" | cut -c 3- | rev | cut -c 4- | rev | grep -v -f $TMPFILE`  
for file in $res
do
  grep -q "\b$file\b" $CONF
  if [ $? -ne 0 ]
  then
    echo "$file"
  fi
done

}


TMPFILE=`mktemp`



MISSING_MAKEFILE_EXCLUSION="
^debian$
^debian/patches$
^doc
^contrib/vmossim$
^contrib/vmossim/monit$
^contrib/vmossim/splashy$
^contrib/vmossim/scripts$
^contrib/vmossim/scripts/sql$
^contrib/vmossim/scripts/lib$
^contrib/vmossim/scripts/lang$
^contrib/vmossim/scripts/tools$
^etc/httpd$
^frameworkd$
^frameworkd/ossimframework$
"

MISSING_FILE_EXCLUSION="
^src/.*.h$
^src/Makefile.server$
^src/config.dtd$
^LICENSE$
^BUGS$
^autogen.sh$
^FILES$
^CONFIG$
^AUTHORS$
^FAQ$
^README$
^COPYING$
^README.phpgacl$
^ChangeLog$
^INSTALL.Debian$
^INSTALL$
^README.oinkmaster$
^INSTALL.src$
^TODO$
^configure.ac$
^os-sim.spec$
^README.sensors$
^NEWS$
^README.nessus$
^etc/agent/plugins/postfix.xml$
^etc/ossim.ini$
^etc/init.d/ossimserver$
^etc/init.d/ossim$
^etc/server/renum.pl$
^mrtg/mrtg.diff$
"

MISSING_DIR_EXCLUSION="
^contrib/vmossim$
^doc$
^agent$
^debian$
^frameworkd$
^etc/agent$
^etc/httpd$
"

MISSING_MAKEFILE_CONFIGURE_EXCLUSION="
"


if [ $# -ne 1 ]
then
  echo "Usage : $0 <cvs dir>"
  exit 1
fi

if [ ! -d "$1" ]
then
  echo "This seems not to be an existing directory"
  exit
fi

DEST=$1
cd $DEST

echo "Note : edit following variables for exlusions :"
echo " - MISSING_MAKEFILE_EXCLUSION"
echo " - MISSING_FILE_EXCLUSION"
echo " - MISSING_DIR_EXCLUSION"
echo " - MISSING_MAKEFILE_CONFIGURE_EXCLUSION"
echo

check_missing_makefile
check_missing_file
check_missing_dir
check_missing_makefile_configure

rm $TMPFILE
