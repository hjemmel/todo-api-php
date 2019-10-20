#!/usr/bin/env bash

#https://stackoverflow.com/questions/19331497/set-environment-variables-from-file-of-key-value-pairs

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 DIRECTORY1 DIRECTORY2" >&2
  exit 1
fi

files=$*
for i in ${files}
do
    export $(grep -E -v '^#' "$i" | xargs -0)
done

