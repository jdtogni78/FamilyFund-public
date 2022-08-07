#!/usr/bin/env bash

env=$1
shift
# --user=root --host=127.0.0.1 --port=3306 -p123456
dir=../../../database
dt=$(date +'%Y%m%d')
file=${dir}/familyfund_ddl_${dt}.sql
echo "Generating $file"
cat ${dir}/drop_all.sql > $file
mysqldump --column-statistics=FALSE familyfund_${env} --no-data --skip-add-locks --tz-utc --routines $* \
    | sed -e $'s/),(/),\\\n(/g' -e 's/ AUTO_INCREMENT=[0-9]*//g' \
    >> $file
