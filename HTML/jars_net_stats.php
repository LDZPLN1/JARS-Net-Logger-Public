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
  echo '  <title>' . ORG_NAME . " Net Stats</title>\n";
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
    <div class="content_top_grid">
      <div>
        <span>Time Period:</span>
        <select id="time_period" onchange="update_charts();">
          <option value="7">7 Days</option>
          <option value="30">30 Days</option>
          <option value="365">1 Year</option>
          <option value="mtd" selected>Month to Date</option>
          <option value="ytd">Year to Date</option>
        </select>
      </div>
      <div class="message_green">
        <div id="vis_count"></div>
      </div>
      <div></div>
    </div>
    <hr>
    <div class="chart_grid">
      <div class="chart_center">
        <canvas id="r1_c1"></canvas>
      </div>
      <div>
        <canvas id="r1_c2"></canvas>
      </div>
      <div>
        <canvas id="r2_c1"></canvas>
      </div>
      <div>
        <canvas id="r2_c2"></canvas>
      </div>
    </div>
<?php
  require_once('jars_footer.php');
?>
  </div>
  <script type="text/javascript" src="js/chart.umd.min.js"></script>
  <script type="text/javascript" src="js/jars_net_stats.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
