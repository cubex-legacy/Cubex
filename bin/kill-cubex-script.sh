#!/bin/bash

CLASSNAME=""
INST=""
TIMEOUT=10

while [ $# -gt 0 ]
do
     case "$1" in
          -t=*|--timeout=*)
               TIMEOUT=`echo "$1" | cut -d'=' -f2`
          ;;
          *)
               if [ "$CLASSNAME" = "" ]
               then
                    CLASSNAME="$1"
               elif [ "$INST" = "" ]
               then
                    INST="$1"
               fi
          ;;
     esac
     shift
done

if [ "$CLASSNAME" = "" ] || [ -z "${TIMEOUT##*[!0-9]*}" ]
then
  echo "Usage: `basename "$0"` className [instanceName] [-t|--timeout=<seconds>]"
  exit 1
fi

if [ "$INST" != "" ]
then
  INST="\s$INST(\s|$)"
fi

PID=`ps ax | grep "/cubex" | grep "$CLASSNAME" | grep -E "$INST" | grep -v grep | grep -v bash | awk '{print $1}'`

if [ "$PID" = "" ]
then
  echo "Not running"
else
  for P in $PID
  do
    echo "Killing Cubex Class $CLASSNAME PID $P"
    kill $P
  done
  sleep $TIMEOUT
  for P in $PID
  do
    kill -0 $P >/dev/null 2>&1 && kill -9 $P
  done
fi
