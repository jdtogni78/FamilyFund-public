#!/bin/bash
. ~/dstrader_config.sh
. $DSTRADER_DIR/opt/log.sh

function validate_email() {
  if [ -z "${GPG_EMAIL}" ]; then
    logErr set GPG_EMAIL
    kill -INT $$
  fi
}

function dstrader_encr() {
  FILE=$1
  validate_email
  if [ -f ${FILE} ]; then
    logInfo "Encrypting ${FILE} with ${GPG_EMAIL}"
    rm -f ${FILE}.encr && \
    gpg --batch --yes -o ${FILE}.encr -e -r ${GPG_EMAIL} ${FILE} && \
    dstrader_clear ${FILE}
  fi
}

function dstrader_decr() {
  FILE=$1
  validate_email
  # lets first encript when file is recreated
  dstrader_encr ${FILE}
  # usually we will only decript though

  logInfo "Decrypting ${FILE}"
  gpg --batch --yes -o ${FILE} -d ${FILE}.encr

  logInfo "### After decrypting ${FILE}"
  ls ${FILE} ${FILE}.encr 2> /dev/null

  echo .
}

function dstrader_clear() {
  FILE=$1
  logInfo "Clear ${FILE}"
  shred -un 3 ${FILE}

  logInfo "### After clear ${FILE}"
  ls ${FILE} ${FILE}.encr 2> /dev/null

  echo .
}