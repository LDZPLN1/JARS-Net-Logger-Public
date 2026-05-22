<?php
ob_start();
session_start();

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

*/

// CHECK IF USER SHOULD BE HERE, IF NOT DESTROY SESSION AND FORCE THEM TO THE LOGIN PAGE

if (!isset($_SESSION['user_id']) || !isset($_SESSION['guest']) || !isset($_SESSION['admin']) || !isset($_SESSION['net_id'])) {
  header("Location: jars_logout.php");
  exit();
}

require_once('config.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="language" content="English" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<?php
  echo '  <title>' . ORG_NAME . " Net Log Viewer</title>\n";
?>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body>
<?php
  if ($_SESSION['net_id'] == 0) {
    echo "  <script>\n";
    echo "    alert('No net selected (Maybe you added a new net and have not logged out/in to select it). Please select a net at log in and try again');\n";
    echo "    window.location.href = 'jars_logout.php';\n";
    echo "  </script>\n";
  }

  echo '  <div class="content_page">' . "\n";

  $date = new DateTimeImmutable();
  $base = basename(__FILE__);
  require_once('jars_header.php');

  echo '    <div id="title_text" class="message_green" data-id="' . $_SESSION['net_id'] . '">' . $_SESSION['net_name'] . "</div>\n";
?>
    <hr>
    <div class="content_top_flex">
      <div>
        <span id="label_net_control" hidden>Net Control:</span><span id="net_control"></span>
      </div>
      <div>
        <span>Log Date:</span><input type="date" id="log_date" value="2026-01-01" min="2026-01-01" max="2040-12-31" onchange="update_log();">
      </div>
      <div>
        <span id="label_check_ins" hidden>Check-Ins:</span><span id="check_ins"></span>&nbsp;<span id="check_ins_unique"></span>
      </div>
    </div>
    <hr id="dynamic_hr" hidden>
    <div id="content_main" class="content_main" hidden>
      <table id="table_logs">
        <thead>
          <tr>
            <th class="th_log_pl">CALLSIGN</th>
            <th class="th_log">ANN</th>
            <th class="th_log">MOB</th>
            <th class="th_log">POR</th>
            <th class="th_log">S/T</th>
            <th class="th_log">E/L</th>
            <th class="th_log">I/O</th>
            <th class="th_log">CPN</th>
            <th class="th_log_viewer_pl">PREFERRED NAME</th>
            <th class="th_log_al">LOCATION</th>
            <th class="th_log_al">NOTES</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
<?php
  require_once('jars_footer.php');
?>
  </div>
  <script type="text/javascript" src="js/jars_net_log_viewer.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
