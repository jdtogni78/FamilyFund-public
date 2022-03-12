#!/bin/bash
. ~/familyfund_config.sh
. $DS_OPT_DIR/opt/log.sh

function validate_email() {
  if [ -z "${GPG_EMAIL}" ]; then
    logErr set GPG_EMAIL
    kill -INT $$
  fi
}

function ds_encr() {
  FILE=$1
  validate_email
  if [ -f ${FILE} ]; then
    logInfo "Encrypting ${FILE} with ${GPG_EMAIL}"
    rm -f ${FILE}.encr && \
    gpg --batch --yes -o ${FILE}.encr -e -r ${GPG_EMAIL} ${FILE} && \
    ds_clear ${FILE}
  fi
}

function ds_decr() {
  FILE=$1
  validate_email
  # lets first encript when file is recreated
  ds_encr ${FILE}
  # usually we will only decript though

  logInfo "Decrypting ${FILE}"
  gpg --batch --yes -o ${FILE} -d ${FILE}.encr

  logInfo "### After decrypting ${FILE}"
  ls ${FILE} ${FILE}.encr 2> /dev/null

  echo .
}

function ds_clear() {
  FILE=$1
  logInfo "Clear ${FILE}"
  shred -un 3 ${FILE}

  logInfo "### After clear ${FILE}"
  ls ${FILE} ${FILE}.encr 2> /dev/null

  echo .
}