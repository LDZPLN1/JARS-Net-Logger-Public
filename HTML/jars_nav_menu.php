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

REVISION 20260528.01

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

  // DATA EXP LINKS FOR ADMIN USERS (DOWNLOAD FILE)

  const EXP_LOG_LINKS = [
    'ADI/ADIF Format' => 'jars_export_net_logs.php?format=adi',
    'CSV Format' => 'jars_export_net_logs.php?format=csv',
    'SQL Format' => 'jars_export_net_logs.php?format=sql'
  ];

  const EXP_VIS_LINKS = [
    'CSV Format' => 'jars_export_visitor_list.php?format=csv',
    'SQL Format' => 'jars_export_visitor_list.php?format=sql'
  ];

  $self = array_search($base, APP_LINKS);

  if ($self) {
#    unset(APP_LINKS[$self]);
  }

  $self = array_search($base, USR_LINKS);

  if ($self) {
#    unset(USR_LINKS[$self]);
  }

  $self = array_search($base, ADM_LINKS);

  if ($self) {
#    unset(ADM_LINKS[$self]);
  }

  if (!isset($en_log_entry)) {
    $en_log_entry = false;
  }

  if (!isset($en_live_log)) {
    $en_live_log = false;
  }

  $show_settings = (((!$_SESSION['guest'] && count(USR_LINKS) > 0) || $en_log_entry) && !$en_live_log);
  $show_admin = ($_SESSION['admin'] && (count(ADM_LINKS) > 0 || count(EXP_LOG_LINKS) > 0 || count(EXP_VIS_LINKS) > 0));
  $show_exp_log = (count(EXP_LOG_LINKS) > 0);
  $show_exp_vis = (count(EXP_VIS_LINKS) > 0);
  $show_links = (((count(APP_LINKS) > 0 || count(WEB_LINKS) > 0) && !$en_live_log) || (count(WEB_LINKS) > 0 && $en_live_log));
  $show_menu = (!$en_live_log || ($show_links && $en_live_log));

  $hr_main = ($show_settings || $show_admin || $show_links);
  $hr_admin = (($show_admin && count(ADM_LINKS) > 0) && (count(EXP_LOG_LINKS) > 0 || count(EXP_VIS_LINKS) > 0));
  $hr_link = (count(APP_LINKS) > 0 && count(WEB_LINKS) > 0);

  if ($show_menu) {
    echo '      <div class="header_right">' . "\n";
    echo "        <nav>\n";
    echo '          <ul class="nav_menu">' . "\n";
    echo '            <li class="nav_menu_icon">' . "\n";
    echo '              <span><img class="image_menu" src="images/menu.png"></span>' . "\n";
    echo '              <ul class="nav_main_menu">' . "\n";
  }

  if ($show_settings) {
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Settings</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";

    if($en_log_entry) {
      echo '                    <li><a id="auto_hide" class="cursor" onclick="toggle_auto_hide();">Enable Auto Hide</a></li>' . "\n";
    }

    if ($_SESSION['guest'] == false) {
      foreach (USR_LINKS as $title => $link) {
        echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }
    }

    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if ($show_admin) {
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Admin</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";

    foreach (ADM_LINKS as $title => $link) {
      echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
    }

    if ($hr_admin) {
      echo "                    <hr>\n";
    }

    if ($show_exp_log) {
      echo '                    <li class="nav_sub_menu_2">' . "\n";
      echo "                      <span>Export Net Logs</span>\n";
      echo '                      <ul class="nav_items_2">' . "\n";

      foreach (EXP_LOG_LINKS as $title => $link) {
        echo '                        <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }

      echo "                      </ul>\n";
      echo "                    </li>\n";
    }

    if ($show_exp_vis) {
      echo '                    <li class="nav_sub_menu_2">' . "\n";
      echo "                      <span>Export Visitor List</span>\n";
      echo '                      <ul class="nav_items_2">' . "\n";

      foreach (EXP_VIS_LINKS as $title => $link) {
        echo '                        <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }

      echo "                      </ul>\n";
      echo "                    </li>\n";
    }

    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if ($show_links) {
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Links</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";

    if (!$en_live_log) {
      foreach (APP_LINKS as $title => $link) {
        echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }
    }

    if ($hr_link) {
      echo "                    <hr>\n";
    }

    foreach (WEB_LINKS as $title => $link) {
      echo '                    <li><a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $title . '</a></li>' . "\n";
    }

    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if (!$en_live_log) {
    if ($hr_main) {
      echo "                <hr>\n";
    }

    echo '                <li><a href="jars_logout.php">Log Out</a></li>' . "\n";
  }

  if ($show_menu) {
    echo "              </ul>\n";
    echo "            </li>\n";
    echo "          </ul>\n";
    echo "        </nav>\n";
    echo "      </div>\n";
  }
