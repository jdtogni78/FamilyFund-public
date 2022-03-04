#!/bin/bash

. ~/dstrader_config.sh
. $DSTRADER_DIR/opt/log.sh
. $DSTRADER_DIR/opt/encr_files_lib.sh

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
    cd "$DSTRADER_DIR/$RUNTIME"   ; dstrader_encr mail.properties     ; dstrader_decr mail.properties
    cd "$DSTRADER_DIR/auto_login" ; dstrader_encr dstrader.properties ; dstrader_decr dstrader.properties
    ;;
  clear)
    cd "$DSTRADER_DIR/$RUNTIME"   ; dstrader_encr mail.properties
    cd "$DSTRADER_DIR/auto_login" ; dstrader_encr dstrader.properties
    ;;
  *)
    logError "Usage: encr_files.sh {decrypt|clear}"
    exit 1
esac

exit 0
