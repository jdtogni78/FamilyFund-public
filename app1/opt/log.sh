#!/bin/bash

export LOGGER

function setLogger() {
  export LOGGER="$1"
}

function log() {
  level=$(shift)
  dt=$(date +"%Y-%m-%d %T")
  echo "[$LOGGER] $dt $level $*"
}

function logInfo()  { log "INFO " $*; }
function logErr()   { log "ERROR" $*; }
function logWarn()  { log "WARN " $*; }
function logDebug() { log "DEBUG" $*; }
