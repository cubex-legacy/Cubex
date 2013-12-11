#!/bin/bash

if [ $# -lt 1 ]
then
  echo "Usage: $0 className"
  exit 1
fi

CLASSNAME="$1"
INST="$2"

if [ "$INST" != "" ]
then
  INST="$INST(\s|$)"
fi

PID=`ps ax | grep "/cubex" | grep "$CLASSNAME" | grep -E "$INST" | grep -v grep | awk '{print $1}'`

if [ "$PID" = "" ]
then
  echo "not running"
else
  for P in $PID
  do
    echo "Killing Cubex Class $CLASSNAME PID $P"
    kill $P
  done
fi
