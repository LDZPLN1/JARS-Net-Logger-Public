#!/bin/bash

echo "JARS Net Logger Install Configuration Tool"
echo "Created by AB9XA, version 1.02"
echo

sudo -v

echo
echo "This script will make the changes necessary to install the JARS Net Logger web"
echo "application to a web server running on Ubuntu 24.04 or Debian 13"
echo
echo "Before you proceed, ensure you have completed the configurations steps for"
echo "Apache and MariaDB as outlined in the user guide."
echo
echo "If you have not performed these steps, press CTRL-C to cancel this script and"
echo "re-run it once these steps are done."
echo

SKIP_MARIA=0

if [ "$#" -eq 1 ]; then
  if [ "$1" == '--remotedb' ]; then
    SKIP_MARIA=1
  fi
fi

# FILE LIST

FILES=("./conf-available/jars-net-logger.conf")
FILES+=("./db_init_inst.sql")
FILES+=("./html/./favicon.ico")
FILES+=("./html/api/jars_api.php")
FILES+=("./html/config.php")
FILES+=("./html/images/chat_green.png")
FILES+=("./html/images/chat_red.png")
FILES+=("./html/images/delete_gs.png")
FILES+=("./html/images/delete_red.png")
FILES+=("./html/images/menu.png")
FILES+=("./html/images/save_green.png")
FILES+=("./html/images/save_gs.png")
FILES+=("./html/images/save_orange.png")
FILES+=("./html/images/save_red.png")
FILES+=("./html/index.php")
FILES+=("./html/jars_announcement_manager.php")
FILES+=("./html/jars_callsign_update.php")
FILES+=("./html/jars_change_password.php")
FILES+=("./html/jars_export_net_logs.php")
FILES+=("./html/jars_export_visitor_list.php")
FILES+=("./html/jars_footer.php")
FILES+=("./html/jars_header.php")
FILES+=("./html/jars_live_log_viewer.php")
FILES+=("./html/jars_logout.php")
FILES+=("./html/jars_nav_menu.php")
FILES+=("./html/jars_net_log_entry.php")
FILES+=("./html/jars_net_log_viewer.php")
FILES+=("./html/jars_net_manager.php")
FILES+=("./html/jars_net_stats.php")
FILES+=("./html/jars_user_manager.php")
FILES+=("./html/js/chart.umd.min.js")
FILES+=("./html/js/jars_admin.js")
FILES+=("./html/js/jars_change_password.js")
FILES+=("./html/js/jars_chat.js")
FILES+=("./html/js/jars_live_log_viewer.js")
FILES+=("./html/js/jars_login.js")
FILES+=("./html/js/jars_net_log_entry.js")
FILES+=("./html/js/jars_net_log_viewer.js")
FILES+=("./html/js/jars_net_stats.js")
FILES+=("./html/style.css")

# REQUIRED PACKAGE LIST

PKGS=("apache2")
PKGS+=("php")
PKGS+=("php-mysql")

if [ "$SKIP_MARIA" == "0" ]; then
  PKGS+=("mariadb-server")
fi

# VERIFY SOURCE FILES EXIST

for FILE in "${FILES[@]}"; do
  if [ ! -f "$FILE" ]; then
    echo "Unable to find source file [$FILE]"
    exit
  fi
done

# GET OS VERSION

if [ -f /etc/os-release ]; then
  OS_NAME=$(grep -oP '(?<=PRETTY_NAME=").*(?=")' /etc/os-release)

  if [ ! "$OS_NAME" ]; then
   echo "Unable to determine OS"
   exit
  fi
fi

OS_SHORT_NAME=$(echo "$OS_NAME" | awk {'print $1'})

if [ "$OS_SHORT_NAME" != "Ubuntu" ] && [ "$OS_SHORT_NAME" != "Debian" ]; then
  echo "Unsuppoted OS, exiting"
  exit
fi

# VERIFY REQUIRED PACKAGES ARE INSTALLED

echo "Checking for required packages... "

for PKG in "${PKGS[@]}"; do
  if ! dpkg -s "$PKG" 2>/dev/null | grep -q "install ok installed"; then
    echo "Missing package [$PKG]"
    exit
  fi
done

echo

# GET VIRTUAL DIRECTORY PATH AND VERIFY IT EXISTS

read -p "Enter full system path to virtual directory [/var/www/html]: " VDIR_PATH

if [ "$VDIR_PATH" == "" ]; then
  VDIR_PATH="/var/www/html"
fi

if [ ! -d "$VDIR_PATH" ]; then
  echo "Virual directory path not found, exiting"
  exit
fi

if [ "$VDIR_PATH" == "/var/www/html" ]; then
  VDIR_SHORT_PATH=''
else
  VDIR_SHORT_PATH='/'${VDIR_PATH##*/}
fi

echo
echo "Configuring Apache... "

# CONFIGURE APACHE CONF FILE

sed -i "s#%VDIR%#$VDIR_PATH#g" ./conf-available/jars-net-logger.conf
sed -i "s#%VDIR_SHORT%#$VDIR_SHORT_PATH#g" ./conf-available/jars-net-logger.conf
sudo cp ./conf-available/jars-net-logger.conf /etc/apache2/conf-available/.
sudo a2enconf jars-net-logger 1>/dev/null

echo "Updating Javascript Files... "

# CONFIGURE VIRTUAL DIRECTORY PATH FOR API CALLS

sed -i "s#const dir_path = .*;#const dir_path = '$VDIR_SHORT_PATH';#" ./html/js/jars_net_log_entry.js
sed -i "s#const dir_path = .*;#const dir_path = '$VDIR_SHORT_PATH';#" ./html/js/jars_net_log_viewer.js
sed -i "s#const dir_path = .*;#const dir_path = '$VDIR_SHORT_PATH';#" ./html/js/jars_net_stats.js

echo
echo "Enter the following database configuration details:"
echo
read -p "Enter the database host name [localhost]: " DB_HOST

if [ "$DB_HOST" == "" ]; then
  DB_HOST="localhost"
fi

read -p "Enter the database name: " DB_NAME
read -p "Enter the database user name: " DB_USER
read -sp "Enter the database user's password: " DB_PASSWORD

DB_PASSWORD64=$(echo -n "$DB_PASSWORD" | base64)

echo
echo
echo "Updating Database Configuration... "

sed -i "s/%DB_HOST%/$DB_HOST/" ./html/config.php
sed -i "s/%DB_NAME%/$DB_NAME/" ./html/config.php
sed -i "s/%DB_USER%/$DB_USER/" ./html/config.php
sed -i "s/%DB_PASSWORD64%/$DB_PASSWORD64/" ./html/config.php
sed -i "s/%DB_NAME%/$DB_NAME/" ./db_init_inst.sql

echo

if [ "$SKIP_MARIA" == "0" ]; then
  echo "Creating database tables... "
  mysql -u "$DB_USER" --password="$DB_PASSWORD" "$DB_NAME" < ./db_init_inst.sql
else
  echo
  echo "Remember to import db_init_inst.sql on the database server to initialize the database"
  echo
fi

echo "Copying files to virtual directory... "

sudo cp -r ./html/* "$VDIR_PATH"/.
sudo a2enmod rewrite 1>/dev/null
sudo a2enmod headers 1>/dev/null
sudo a2enmod expires 1>/dev/null
sudo a2enmod proxy_http 1>/dev/null
sudo systemctl restart apache2

echo

sudo touch /var/log/jars-net-logger.log
sudo chown www-data:www-data /var/log/jars-net-logger.log
sudo chmod 644 /var/log/jars-net-logger.log

echo "Configuration complete"
