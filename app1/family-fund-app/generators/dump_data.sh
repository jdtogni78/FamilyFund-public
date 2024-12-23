#!/usr/bin/env bash

env=$1
shift
# --user=root --host=127.0.0.1 --port=3306 -p123456
dir=../../../database
dt=$(date +'%Y%m%d')
file=${dir}/${env}/familyfund_${env}_data_${dt}.sql
echo "Generating $file"
cat ${dir}/truncate_all.sql > $file
mysqldump --column-statistics=FALSE familyfund_${env} --no-create-info $* \
    | sed -e $'s/),(/),\\\n(/g' >> $file


