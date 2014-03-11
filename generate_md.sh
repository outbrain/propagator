#!/bin/bash

#
# Requires fpm: https://github.com/jordansissel/fpm
#

echo $(dirname $0)
echo $(basename $(dirname $0))


python ~/bin/html2text.py $(dirname $0)/views/about.php > $(dirname $0)/README.md
python ~/bin/html2text.py $(dirname $0)/views/manual_content.php > $(dirname $0)/MANUAL.md
