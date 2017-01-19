#!/bin/bash

if [ ! "$#" -eq 2 ]
then
 echo "Usage $0 glpi_git_dir release";
 exit ;
fi

INIT_DIR=$1;
RELEASE=$2;

# test glpi_cvs_dir
if [ ! -e $INIT_DIR ]
then
 echo "$1 does not exist";
 exit ;
fi

INIT_PWD=$PWD;

if [ -e /tmp/glpi ]
then
 echo "Delete existing temp directory";
\rm -rf /tmp/glpi;
fi

echo "Copy to  /tmp directory";
git checkout-index -a -f --prefix=/tmp/glpi/

echo "Move to this directory";
cd /tmp/glpi;


echo "Delete bigdumps and older sql files";
\rm install/mysql/glpi-0.3*;
\rm install/mysql/glpi-0.4*;
\rm install/mysql/glpi-0.5*;
\rm install/mysql/glpi-0.6*;
\rm install/mysql/glpi-0.7-*;
\rm install/mysql/glpi-0.71*;
\rm install/mysql/glpi-0.72*;
\rm install/mysql/glpi-0.78*;
\rm install/mysql/glpi-0.80*;
\rm install/mysql/glpi-0.83*;
\rm install/mysql/glpi-0.84*;
\rm install/mysql/glpi-0.85*;
\rm install/mysql/glpi-0.90*;
\rm install/mysql/irm*;

echo "Retrieve PHP vendor"
composer install --no-dev --optimize-autoloader --prefer-dist --quiet

echo "Clean PHP vendor"
\rm -rf vendor/bin;
\find vendor/ -type f -name "build.xml" -exec rm -rf {} \;
\find vendor/ -type f -name "build.properties" -exec rm -rf {} \;
\find vendor/ -type f -name "composer.json" -exec rm -rf {} \;
\find vendor/ -type f -name "composer.lock" -exec rm -rf {} \;
\find vendor/ -type f -name "changelog.md" -exec rm -rf {} \;
\find vendor/ -type f -name "*phpunit.xml.dist" -exec rm -rf {} \;
\find vendor/ -type f -name ".gitignore" -exec rm -rf {} \;
\find vendor/ -type d -name "test*" -prune -exec rm -rf {} \;
\find vendor/ -type d -name "doc*" -prune -exec rm -rf {} \;
\find vendor/ -type d -name "example*" -prune -exec rm -rf {} \;
\find vendor/ -type d -name "design" -prune -exec rm -rf {} \;

echo "Delete various scripts and directories"
\rm -rf tools;
\rm -rf phpunit;
\rm -rf tests;
\rm -rf .gitignore;
\rm -rf .travis.yml;
\rm -rf phpunit.xml.dist;
\rm -rf composer.json;
\rm -rf composer.lock;
\rm -rf .composer.hash;
\rm -rf ISSUE_TEMPLATE.md;
\find pics/ -type f -name "*.eps" -exec rm -rf {} \;

echo "Creating tarball";
cd ..;
tar czf "glpi-$RELEASE.tgz" glpi


cd $INIT_PWD;


echo "Deleting temp directory";
\rm -rf /tmp/glpi;

echo "The Tarball is in the /tmp directory";
