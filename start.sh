#!/bin/bash
DIR=./App

if [ ! -n "$DIR" ] ;then
    echo "you have not choice Application directory !"
    exit
fi

php easyswoole stop
php easyswoole start --d

fswatch $DIR | while read file
do
   echo "${file} was modify" >> ./Temp/reload.log 2>&1
   php easyswoole reload
done
