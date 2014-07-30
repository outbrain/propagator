#!/bin/bash

# Simple packaging of orchestrator
#
# Requires fpm: https://github.com/jordansissel/fpm
#

release_version="1.0.1"
release_dir=/tmp/propagator-release
release_files_dir=$release_dir/propagator
rm -rf $release_dir/*
mkdir -p $release_files_dir/

cd  $(dirname $0)
rsync -av --delete --delete-excluded --exclude=".svn" --exclude=".git" ./ $release_files_dir/

cd $release_dir
fpm -v "${release_version}" -f -s dir -t rpm -n propagator -C $release_files_dir --prefix=/var/www/html/propagator .

echo "---"
echo "Done. Find releases in $release_dir"
