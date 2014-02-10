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
  INST="\s$INST(\s|$)"
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
  sleep 5
  for P in $PID
  do
    kill -0 $P >/dev/null 2>&1 && kill -9 $P
  done
fi
