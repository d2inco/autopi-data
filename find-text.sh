#!/bin/bash

printf "\n--------\n"
find . -name log -prune -o -ls | egrep -i "$*"

printf "\n--------\n"

egrep -ir --exclude-dir=.git/ --exclude-dir=log/ "$*"