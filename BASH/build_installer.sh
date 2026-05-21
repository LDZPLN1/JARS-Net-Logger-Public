#!/bin/bash

mkdir ../build
mkdir ../build/conf-available
mkdir ../build/html

cp ../BASH/install.sh ../build/.
cp ../BASH/update.sh ../build/.
cp ../SQL/db_init_inst.sql ../build/.
cp ../Apache/* ../build/conf-available/.
cp -r ../HTML/* ../build/html/.

cd ..
tar -czf jars-logger.tar.gz build

rm -rf ./build
