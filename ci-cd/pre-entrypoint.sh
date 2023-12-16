#!/bin/sh

set -e

# PROD Pre-run script
# gets auto-run by nginx-unit entrypoint if placed inside /docker-entrypoint.d/

echo "Running pre-start script..."

# Put anything you want to run in production here
# For example, loading secrets files or running a pre-compilation or install script
