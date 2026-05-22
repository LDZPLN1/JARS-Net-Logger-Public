#!/bin/bash

echo "JARS Net Logger Update Tool"
echo "Created by AB9XA, version 1.03"
echo

sudo -v

echo
echo "This script will update an existing installation of the JARS Net Logger web application"
echo

UPDATE_DIR='./html'
APACHE_CONF='jars-net-logger.conf'

# FILE LIST

FILES=("./favicon.ico")
FILES+=("api/jars_api.php")
FILES+=("docs/JARS_Net_Logger_User_Guide.docx")
FILES+=("images/chat_green.png")
FILES+=("images/chat_red.png")
FILES+=("images/delete_gs.png")
FILES+=("images/delete_red.png")
FILES+=("images/jars_logo.png")
FILES+=("images/menu.png")
FILES+=("images/save_green.png")
FILES+=("images/save_gs.png")
FILES+=("images/save_orange.png")
FILES+=("images/save_red.png")
FILES+=("index.php")
FILES+=("jars_announcement_manager.php")
FILES+=("jars_callsign_update.php")
FILES+=("jars_change_password.php")
FILES+=("jars_export_net_logs_adi.php")
FILES+=("jars_export_net_logs_csv.php")
FILES+=("jars_export_net_logs_sql.php")
FILES+=("jars_export_visitor_list_csv.php")
FILES+=("jars_export_visitor_list_sql.php")
FILES+=("jars_footer.php")
FILES+=("jars_header.php")
FILES+=("jars_live_log_viewer.php")
FILES+=("jars_logout.php")
FILES+=("jars_nav_menu.php")
FILES+=("jars_net_log_entry.php")
FILES+=("jars_net_log_viewer.php")
FILES+=("jars_net_manager.php")
FILES+=("jars_net_stats.php")
FILES+=("jars_user_manager.php")
FILES+=("js/chart.umd.min.js")
FILES+=("js/jars_admin.js")
FILES+=("js/jars_change_password.js")
FILES+=("js/jars_chat.js")
FILES+=("js/jars_live_log_viewer.js")
FILES+=("js/jars_login.js")
FILES+=("js/jars_net_log_entry.js")
FILES+=("js/jars_net_log_viewer.js")
FILES+=("js/jars_net_stats.js")
FILES+=("style.css")

# VERIFY SOURCE FILES EXIST

for FILE in "${FILES[@]}"; do
  if [ ! -f "$UPDATE_DIR/$FILE" ]; then
    echo "Unable to find source file [$FILE]"
    exit 1
  fi
done

# VERIFY APACHE CONF FILE EXISTS

if [ ! -f "/etc/apache2/conf-available/$APACHE_CONF" ]; then
  echo "Unable to find Apache configuration file"
  exit 1
fi

# GET INSTALL PATHS

VDIR_PATH=$(grep -oP '(?<=Alias ).*(?=/api/jars_api.php)' /etc/apache2/conf-available/"$APACHE_CONF" | awk '{ print $2 }')
VDIR_SHORT_PATH='/'${VDIR_PATH##*/}

# VERIFY VIRTUAL DIRECTORY PATH EXISTS

if [ ! -d "$VDIR_PATH" ]; then
  echo "Unable to find virtual directory path [$VDIR_PATH]"
  exit 1
fi

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

# CONFIGURE VIRTUAL DIRECTORY PATH FOR API CALLS

sed -i "s#const dir_path = .*;#const dir_path = '$VDIR_SHORT_PATH';#" ./html/js/jars_admin.js

# UPDATE FILES

FILE_COPIED='false'

for FILE in "${FILES[@]}"; do
  SUB_PATH="${FILE%%/*}"

  if [ "$SUB_PATH" == "$FILE" ]; then
    SUB_PATH=''
  else
    SUB_PATH="$SUB_PATH/"
  fi

  if [ ! -f "$VDIR_PATH/$FILE" ]; then
    echo "Copying new file [$FILE]"
    sudo cp "$UPDATE_DIR/$FILE" "$VDIR_PATH/$SUB_PATH."
    FILE_COPIED="true"
  else
    if ! diff "$UPDATE_DIR/$FILE" "$VDIR_PATH/$FILE" &> /dev/null ; then
      echo "Updating file [$FILE]"
      sudo cp "$UPDATE_DIR/$FILE" "$VDIR_PATH/$SUB_PATH."
      FILE_COPIED="true"
    fi
  fi
done

if [ "$FILE_COPIED" == "false" ]; then
  echo "No changes needed"
fi

VERSION=$(grep APP_VERSION ./html/config.php | awk '{ print $4 }')
sudo sed -i "s/APP_VERSION.*/APP_VERSION = $VERSION/" "$VDIR_PATH"/config.php

sudo chown -R www-data:www-data "$VDIR_PATH"
sudo chmod 640 "$VDIR_PATH"/config.php

echo
echo "Update complete"
