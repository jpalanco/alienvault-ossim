#!/usr/bin/env bash

# Start a server if not already running
n_running_servers=$(pgrep -fc 'python runserver.py')
if [ "$n_running_servers" == 0 ]; then {
    python runserver.py &
    server_ppid=$!
}; fi

pushd avapi

# Cleanup and prepare directories.
rm -rf apidocs/build
rm -rf static/apidocs
mkdir -p static

# Build docs
sphinx-build -b html -d apidocs/build/doctrees apidocs static/apidocs

# Kill server if we started it.
if [ "$n_running_servers" == 0 ]; then pkill -P $server_ppid; fi

popd
