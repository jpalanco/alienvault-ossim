#!/bin/bash

# Update debian repository generating Packages.gz and Sources.gz
# Packages and sources must be in the REPO_DIR directory:

REPO_DIR=debian

dpkg-scanpackages $REPO_DIR /dev/null | gzip -9c > $REPO_DIR/Packages.gz
dpkg-scansources $REPO_DIR /dev/null | gzip -9c > $REPO_DIR/Sources.gz

