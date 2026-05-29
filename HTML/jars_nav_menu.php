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

REVISION 20260529.01

*/

  // INTERNAL APPLICATION LINKS FOR ALL USERS (OPEN IN SAME TAB)

  $app_links = [
    'Net Log Entry' => 'jars_net_log_entry.php',
    'Net Log Viewer' => 'jars_net_log_viewer.php',
    'Net Log Stats' => 'jars_net_stats.php',
    'User Guide' => 'docs/JARS_Net_Logger_User_Guide.docx'
  ];

  // INTERNAL APPLICATION LINKS FOR NON-GUEST USERS (OPEN IN SAME TAB)

  $usr_links = [
    'Change Password' => 'jars_change_password.php'
  ];

  // INTERNAL APPLICATION LINKS FOR ADMIN USERS (OPEN IN SAME TAB)

  $adm_links = [
    'Net Manager' => 'jars_net_manager.php',
    'User Manager' => 'jars_user_manager.php',
    'Announcement<br>Manager' => 'jars_announcement_manager.php',
    'Callsign Update' => 'jars_callsign_update.php'
  ];

  // DATA EXP LINKS FOR ADMIN USERS (DOWNLOAD FILE)

  $exp_log_links = [
    'ADI/ADIF Format' => 'jars_export_net_logs.php?format=adi',
    'CSV Format' => 'jars_export_net_logs.php?format=csv',
    'SQL Format' => 'jars_export_net_logs.php?format=sql'
  ];

  $exp_vis_links = [
    'CSV Format' => 'jars_export_visitor_list.php?format=csv',
    'SQL Format' => 'jars_export_visitor_list.php?format=sql'
  ];

  $self = array_search($base, $app_links);
  if ($self !== false) unset($app_links[$self]);

  $self = array_search($base, $usr_links);
  if ($self !== false) unset($usr_links[$self]);

  $self = array_search($base, $adm_links);
  if ($self !== false) unset($adm_links[$self]);

  if (!isset($en_log_entry)) $en_log_entry = false;
  if (!isset($en_live_log)) $en_live_log = false;

  $admin = (!isset($_SESSION['admin'])) ? false : $_SESSION['admin'];
  $guest = (!isset($_SESSION['guest'])) ? false : $_SESSION['guest'];

  $dsp_set = (((!$guest && count($usr_links) > 0) || $en_log_entry) && !$en_live_log);
  $dsp_adm = ($admin && (count($adm_links) > 0 || count($exp_log_links) > 0 || count($exp_vis_links) > 0));
  $dsp_exp_log = (count($exp_log_links) > 0);
  $dsp_exp_vis = (count($exp_vis_links) > 0);
  $dsp_lnk = (((count($app_links) > 0 || count(WEB_LINKS) > 0) && !$en_live_log) || (count(WEB_LINKS) > 0 && $en_live_log));
  $dsp_mnu = (!$en_live_log || ($dsp_lnk && $en_live_log));

  $dsp_hr_mnu = ($dsp_set || $dsp_adm || $dsp_lnk);
  $dsp_hr_adm = (($dsp_adm && count($adm_links) > 0) && (count($exp_log_links) > 0 || count($exp_vis_links) > 0));
  $dsp_hr_lnk = (count($app_links) > 0 && count(WEB_LINKS) > 0);

  if ($dsp_mnu) {
    echo '      <div class="header_right">' . "\n";
    echo "        <nav>\n";
    echo '          <ul class="nav_menu">' . "\n";
    echo '            <li class="nav_menu_icon">' . "\n";
    echo '              <svg class="image_menu" fill="currentColor"><use xlink:href="images/sprites.svg#three-dots-vertical"/></svg>' . "\n";
    echo '              <ul class="nav_main_menu">' . "\n";
  }

  if ($dsp_set) {
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Settings</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";

    if($en_log_entry) echo '                    <li><a id="auto_hide" class="cursor" onclick="toggle_auto_hide();">Enable Auto Hide</a></li>' . "\n";

    if ($_SESSION['guest'] == false) {
      foreach ($usr_links as $title => $link) {
        echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }
    }

    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if ($dsp_adm) {
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Admin</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";

    foreach ($adm_links as $title => $link) {
      echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
    }

    if ($dsp_hr_adm) echo "                    <hr>\n";

    if ($dsp_exp_log) {
      echo '                    <li class="nav_sub_menu_2">' . "\n";
      echo "                      <span>Export Net Logs</span>\n";
      echo '                      <ul class="nav_items_2">' . "\n";

      foreach ($exp_log_links as $title => $link) {
        echo '                        <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }

      echo "                      </ul>\n";
      echo "                    </li>\n";
    }

    if ($dsp_exp_vis) {
      echo '                    <li class="nav_sub_menu_2">' . "\n";
      echo "                      <span>Export Visitor List</span>\n";
      echo '                      <ul class="nav_items_2">' . "\n";

      foreach ($exp_vis_links as $title => $link) {
        echo '                        <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }

      echo "                      </ul>\n";
      echo "                    </li>\n";
    }

    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if ($dsp_lnk) {
    echo '                <li class="nav_sub_menu_1">' . "\n";
    echo "                  <span>Links</span>\n";
    echo '                  <ul class="nav_items_1">' . "\n";

    if (!$en_live_log) {
      foreach ($app_links as $title => $link) {
        echo '                    <li><a href="' . $link . '">' . $title . "</a></li>\n";
      }
    }

    if ($dsp_hr_lnk) echo "                    <hr>\n";

    foreach (WEB_LINKS as $title => $link) {
      echo '                    <li><a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $title . '</a></li>' . "\n";
    }

    echo "                  </ul>\n";
    echo "                </li>\n";
  }

  if (!$en_live_log) {
    if ($dsp_hr_mnu) echo "                <hr>\n";

    echo '                <li><a href="jars_logout.php">Log Out</a></li>' . "\n";
  }

  if ($dsp_mnu) {
    echo "              </ul>\n";
    echo "            </li>\n";
    echo "          </ul>\n";
    echo "        </nav>\n";
    echo "      </div>\n";
  }
