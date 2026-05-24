<?php

/*

Copyright (c) 2026 Douglas Graham
All rights reserved.

This file is part of the JARS Net Logger

JARS Net Logger is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option)
any later version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.

REVISION 20260523.01

*/

// INTERNAL APPLICATION LINKS FOR ALL USERS (OPEN IN SAME TAB)

  const APP_LINKS = [
    'Net Log Entry' => 'jars_net_log_entry.php',
    'Net Log Viewer' => 'jars_net_log_viewer.php',
    'Net Log Stats' => 'jars_net_stats.php',
    'User Guide' => 'docs/JARS_Net_Logger_User_Guide.docx'
  ];

  // INTERNAL APPLICATION LINKS FOR NON-GUEST USERS (OPEN IN SAME TAB)

  const USR_LINKS = [
    'Change Password' => 'jars_change_password.php'
  ];

  // INTERNAL APPLICATION LINKS FOR ADMIN USERS (OPEN IN SAME TAB)

  const ADM_LINKS = [
    'Net Manager' => 'jars_net_manager.php',
    'User Manager' => 'jars_user_manager.php',
    'Announcement<br>Manager' => 'jars_announcement_manager.php',
    'Callsign Update' => 'jars_callsign_update.php'
  ];

  // DATA EXPORT LINKS FOR ADMIN USERS (DOWNLOAD FILE)

  const EXPORT_LINKS = [
    'Export Net Logs (CSV)' => 'jars_export_net_logs_csv.php',
    'Export Net Logs (ADI)' => 'jars_export_net_logs_adi.php',
    'Export Net Logs (SQL)' => 'jars_export_net_logs_sql.php',
    'Export Visitor List (CSV)' => 'jars_export_visitor_list_csv.php',
    'Export Visitor List (SQL)' => 'jars_export_visitor_list_sql.php'
  ];

  if (!isset($en_log_entry)) {
    $en_log_entry = false;
  }

  if (!isset($en_live_log)) {
    $en_live_log = false;
  }

  echo '      <div class="header_right">' . "\n";
  echo "        <nav>\n";
  echo '          <ul class="nav_menu">' . "\n";
  echo '            <li class="nav_menu_icon">' . "\n";
  echo '              <span><img class="image_menu" src="images/menu.png"></span>' . "\n";
  echo '              <ul class="nav_main_menu">' . "\n";

  $add_hr = false;
  $show_settings = false;
  $web_only = $en_live_log;

  if (!$en_live_log) {
    if ($en_log_entry == true) {
      $show_settings = true;
    }

    if ($_SESSION['guest'] == false && count(USR_LINKS) > 0) {
      $first_link = USR_LINKS[array_key_first(USR_LINKS)];

      if (!(count(USR_LINKS) == 1 && $base == $first_link)) {
        $show_settings = true;
      }
    }

    if ($show_settings) {
      $add_hr = true;
      echo '                <li class="nav_sub_menu_1">' . "\n";
      echo "                  <span>Settings</span>\n";
      echo '                  <ul class="nav_items_1">' . "\n";
    }

    if($en_log_entry) {
      echo '                    <li><a id="auto_hide" class="cursor" onclick="toggle_auto_hide();">Enable Auto Hide</a></li>' . "\n";
    }

    if ($_SESSION['guest'] == false) {
      foreach (USR_LINKS as $title => $link) {
        if (!str_contains($link, $base)) {
          echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
        }
      }
    }

    if ($show_settings) {
      echo "                  </ul>\n";
      echo "                </li>\n";
    }

    if ($_SESSION['admin'] == true && (count(ADM_LINKS) > 0 || count(EXPORT_LINKS) > 0)) {
      $first_link = ADM_LINKS[array_key_first(ADM_LINKS)];

      if (!(count(ADM_LINKS) == 1 && $base == $first_link) || count(EXPORT_LINKS) > 0) {
        $add_hr = true;
        echo '                <li class="nav_sub_menu_1">' . "\n";
        echo "                  <span>Admin</span>\n";
        echo '                  <ul class="nav_items_1">' . "\n";

        foreach (ADM_LINKS as $title => $link) {
          if (!str_contains($link, $base)) {
            echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
          }
        }

        if (count(EXPORT_LINKS) > 0) {
          echo '                    <li class="nav_sub_menu_2">' . "\n";
          echo "                      <span>Export Data</span>\n";
          echo '                      <ul class="nav_items_2">' . "\n";

          foreach (EXPORT_LINKS as $title => $link) {
            echo '                        <li><a href="' . $link . '">' . $title . "</a></li>\n";
          }

          echo "                      </ul>\n";
          echo "                    </li>\n";
        }

        echo "                  </ul>\n";
        echo "                </li>\n";
      }
    }
  }

  if (count(APP_LINKS) > 0 || count(WEB_LINKS) > 0) {
    $add_hr = true;
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Links</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";
  }

  if (!$en_live_log) {
    if (count(APP_LINKS) > 0) {
      foreach (APP_LINKS as $title => $link) {
        if (!str_contains($link, $base)) {
          echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
        }
      }
    }

    if (count(APP_LINKS) > 0 && count(WEB_LINKS) >0) {
      echo "                    <hr>\n";
    }
  }

  if (count(WEB_LINKS) > 0) {
    foreach (WEB_LINKS as $title => $link) {
      echo '                    <li><a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $title . '</a></li>' . "\n";
    }
  }

  if (count(APP_LINKS) > 0 || count(WEB_LINKS) > 0) {
    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if (!$en_live_log) {
    if ($add_hr == true) {
      echo "                <hr>\n";
    }

    echo '                <li><a href="jars_logout.php">Log Out</a></li>' . "\n";
  }

  echo "              </ul>\n";
  echo "            </li>\n";
  echo "          </ul>\n";
  echo "        </nav>\n";
  echo "      </div>\n";
