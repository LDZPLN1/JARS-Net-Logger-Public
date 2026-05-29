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

?>
    <div class="header">
      <div class="header_logo">
<?php
  echo '        <img src="' . LOGO_IMAGE . '" title="JARS Net Logger Version ' . APP_VERSION . '" style="height: ' . LOGO_HEIGHT . 'px; width: ' . LOGO_WIDTH . 'px;">' . "\n";
?>
      </div>
      <div class="header_left">
<?php
  if ($base == 'index.php') {
    $page_title = 'Log In';
  } else {
    $base_name = pathinfo($base, PATHINFO_FILENAME);
    $base_name = str_replace('_', ' ', $base_name);
    $base_name = substr($base_name, 5);
    $page_title = ucwords($base_name);
  }

  echo '        <div class="header_org">' . ORG_LONG_NAME . "</div>\n";
  echo '        <div class="header_title">' . ORG_NAME . ' ' . $page_title . "</div>\n";

  if (isset($_SESSION['user_id'])) {
    echo '        <div class="header_user">' . $_SESSION['user_id'];

    if (isset($_SESSION['user_name'])) echo ' (' . $_SESSION['user_name'] . ')';

    echo "</div>\n";
  }

  echo '        <div class="header_text">' . "\n";
  echo '          ' . $date->format('l') . ', ' . '<span id="header_date">' . $date->format('Y-m-d') . "</span>\n";
?>
        </div>
      </div>
<?php if ($base != 'index.php') require_once "jars_nav_menu.php"; ?>
    </div>
    <hr>
