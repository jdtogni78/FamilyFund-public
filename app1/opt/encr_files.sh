#!/bin/bash

. ~/familyfund_config.sh
. $DS_OPT_BASE/opt/log.sh
. $DS_OPT_BASE/opt/encr_files_lib.sh

# always try encrypting
logInfo Always encrypt first to hide clear text file on first run

case "$1" in
  setup)
    logInfo 'macos https://gpgtools.org/'
    logInfo 'gpg --full-generate-key'
    logInfo GPG_EMAIL=$GPG_EMAIL
    logInfo 'gpg --output public.gpg --armor --export $GPG_EMAIL'
    logInfo 'gpg --output private.pgp --armor --export-secret-key $GPG_EMAIL'
    logInfo 'scp *.gpg $SERVER:~'
    logInfo 'ssh $SERVER:~'
    logInfo 'gpg --import public.key'
    logInfo 'gpg --import private.key'
    ;;
  decrypt)
    cd "$DS_OPT_BASE/"   ; ds_encr .env     ; ds_decr .env
    ;;
  clear)
    cd "$DS_OPT_BASE/"   ; ds_encr .env
    ;;
  *)
    logError "Usage: encr_files.sh {decrypt|clear}"
    exit 1
esac

exit 0
