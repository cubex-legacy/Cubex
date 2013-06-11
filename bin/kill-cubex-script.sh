#!/bin/bash

if [ $# -ne 1 ]
then
        echo "Usage: $0 className"
        exit 1
fi

CLASSNAME="$1"

PID=`ps ax | grep "cubex $CLASSNAME" | grep -v grep | awk '{print $1}' | xargs echo`

if [ "$PID" = "" ]
then
        echo "not running"
else
  for P in $PID
  do
          echo "Killing $CLASSNAME PID $P"
          kill $P
        done
fi
