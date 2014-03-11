#!/bin/bash

#
# Requires fpm: https://github.com/jordansissel/fpm
#

rsync -av --delete --delete-excluded --exclude=".svn" $(dirname $0) /tmp/
fpm -f -s dir -t rpm -n propagator -C /tmp/$(basename $(dirname $0)) --prefix=/var/www/html/propagator .
