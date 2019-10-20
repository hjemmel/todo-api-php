#!/usr/bin/env bash
#https://stackoverflow.com/questions/5274343/replacing-environment-variables-in-a-properties-file

perl -p -e 's/\$\{([^}]+)\}/defined $ENV{$1} ? $ENV{$1} : $&/eg; s/\$\{([^}]+)\}//eg'