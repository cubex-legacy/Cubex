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

PIDS=`ps ax | grep "/cubex" | grep "$CLASSNAME" | grep -E "$INST" | grep -v grep | grep -v bash | awk '{print $1}'`

if [ "$PIDS" = "" ]
then
  echo "Not running"
else
  for PID in $PIDS
  do
    echo "Killing Cubex class $CLASSNAME PID $PID"
    kill $PID
  done

  ENDTIME=$(( `date +%s` + $TIMEOUT ))

  while [ "$PIDS" != "" ]
  do
    NEWPIDS=""
    for PID in $PIDS
    do
      kill -0 $PID >/dev/null 2>&1
      if [ $? -eq 0 ]
      then
        NEWPIDS="$NEWPIDS $PID"
      fi
    done

    PIDS="$NEWPIDS"

    sleep 1

    if [ `date +%s` -ge $ENDTIME ]
    then
      break
    fi
  done

  for PID in $PIDS
  do
    kill -0 $PID >/dev/null 2>&1
    if [ $? -eq 0 ]
    then
      echo "Terminating PID $PID"
      kill -9 $PID
    fi
  done
fi
