#!/bin/bash

#
#  Generate a clean ossim source tarball ready to be released
#
#  * This script uses rsync, so it needs to be installed
#
#  * The tarball can be made using a source directory or the
#    sourceforge CVS repository (if a directory is not specified)
#
#  * The following directories/files are excluded from the tarball:
#     - /debian/
#     - CVS/
#     - empty directories
#     - hidden files
#     - backup files
#
#  * The version number is obtained from the source, the tarball
#    is named with it. Also, version mismatches are checked.
#

OSSIM_TMP_DIR=/tmp
OSSIM_TARBALL_NAME=ossim
OSSIM_SRC_DIR=$1

#
#  check that rsync is installed
#
RSYNC=`which rsync`
if [ "x$RSYNC" == "x" ]; then
    echo " ERROR: Sorry, you need to install rsync in order to use this script"
    exit
fi

#
#  If a source directory is not specified by the user,
#  check out the ossim cvs version
#
if [ "x$OSSIM_SRC_DIR" == "x" ]; then
    cd $OSSIM_TMP_DIR
    echo " WARNING: No source directory specified... "
    echo " Downloading Ossim source from CVS repository "
    echo " (this might take a while)... "
    `cvs -d:pserver:anonymous@os-sim.cvs.sourceforge.net:/cvsroot/os-sim co os-sim`
    OSSIM_SRC_DIR="$OSSIM_TMP_DIR/os-sim"
    cd -
fi

#
#  Check if the directory specified is really an ossim source directory
#
if [ ! -f $OSSIM_SRC_DIR/include/classes/about.inc ] || \
   [ ! -f $OSSIM_SRC_DIR/db/ossim_config.sql ]
then
    echo " ERROR: Sorry, it seems that the directory specified "
    echo " does not contains an ossim source directory "
    exit
fi

#
#  Get current version from source
#  This version number will be used in the tarball name
#
VERSION=`grep "this->version\s*=" $OSSIM_SRC_DIR/include/classes/about.inc  | sed -e 's/.*"\(.*\)";/\1/'`

#
#  Check for version mismatches
#
VERSION2=`grep "ossim_schema_version" $OSSIM_SRC_DIR/db/ossim_config.sql | sed -e "s/.*'\(.*\)');/\1/"`
if [ "$VERSION" != "$VERSION2" ]; then
    echo " ERROR: There is a version mismatch [ $VERSION != $VERSION2 ] "
    echo " Review the following files: "
    echo " - 'include/classes/about.inc' (version) "
    echo " - 'db/ossim_config.sql' (ossim_schema_version) "
    exit
fi

#
#  Generate the tarball using rsync
#
rm -rf ossim-$VERSION
$RSYNC -av --exclude=/debian/ --exclude=CVS/ --exclude=.* --exclude=*~ --prune-empty-dirs $OSSIM_SRC_DIR/ $OSSIM_TARBALL_NAME-$VERSION/
tar -czf "$OSSIM_TMP_DIR/$OSSIM_TARBALL_NAME"-"$VERSION".tar.gz $OSSIM_TARBALL_NAME-$VERSION
echo
echo "*** Ossim tarball created in $OSSIM_TMP_DIR/"$OSSIM_TARBALL_NAME"_"$VERSION".tar.gz ***"

#
#  Cleanup tmp files
#
rm -rf ossim-$VERSION

