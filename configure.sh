#!/bin/bash

# Run this script only once after the project is first downloaded
# This script will get some files and permissions set up for the example database

# Create a dummy file for data/sample.csv and data/sample.db
touch data/sample.csv
touch data/sample.db

# Set permissions on the data directory so that www-data can write to it
chmod a+w data data/sample.*

