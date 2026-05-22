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

if (!isset($_SESSION['user_id']) || !isset($_SESSION['guest']) || !isset($_SESSION['admin'])) {
  header("Location: jars_logout.php");
  exit();
}

if ($_SESSION['admin'] != true) {
  header("Location: jars_logout.php");
  exit();
}

require_once('config.php');

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $error = 'Unable to Connect to Database';
}

// CLEANUP OLD MESSAGES

$sql_query = $pdo->prepare("DELETE FROM announcements WHERE end_date < (CURRENT_DATE - INTERVAL 7 DAY);");
$sql_query->execute();

// ADD/CHANGE ANNOUNCEMENT

function change_announcement($pdo) {
  if ($_POST['end_date'] == '') {
    $end_date = null;
  } else {
    $end_date = $_POST['end_date'];
  }

  try {
    if ($_POST['record_id'] == -1) {
      $sql_query = $pdo->prepare("INSERT INTO announcements VALUES (NULL, :message, :start, :end);");
    } else {
      $sql_query = $pdo->prepare("UPDATE announcements SET announcement = :message, start_date = :start, end_date = :end WHERE id = :recordid;");
      $sql_query->bindParam(':recordid', $_POST['record_id'], PDO::PARAM_INT);
    }

    $sql_query->bindParam(':message', $_POST['message'], PDO::PARAM_STR);
    $sql_query->bindParam(':start', $_POST['start_date'], PDO::PARAM_STR);
    $sql_query->bindParam(':end', $end_date, PDO::PARAM_STR);
    $sql_query->execute();
  } catch (PDOException $e) {
    echo $e;
    exit();
  }
}

// DELETE ANNOUNCEMENT

function delete_announcement($pdo) {
  try {
    $sql_query = $pdo->prepare("DELETE FROM announcements WHERE id = :recordid;");
    $sql_query->bindParam(':recordid', $_POST['record_id'], PDO::PARAM_INT);
    $sql_query->execute();
  } catch (PDOException $e) {
    exit();
  }
}

if (isset($_POST['mode']) && isset($_POST['record_id'])) {
  switch ($_POST['mode']) {
    case "change":
      change_announcement($pdo);
      break;
    case "delete":
      delete_announcement($pdo);
      break;
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="language" content="English" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<?php
  echo '  <title>' . ORG_NAME . " Announcement Manager</title>\n";
?>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body>
<?php
    echo '  <form id="ann_form" method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
?>
    <input type="hidden" id="ann_record_id" name="record_id" value="">
    <input type="hidden" id="ann_message" name="message" value="">
    <input type="hidden" id="ann_start_date" name="start_date" value="">
    <input type="hidden" id="ann_end_date" name="end_date" value="">
    <input type="hidden" id="ann_mode" name="mode" value="">
  </form>
  <div class="content_page">
<?php
  $date = new DateTimeImmutable();
  $base = basename(__FILE__);
  require_once('jars_header.php');

  echo '    <div class="content_top">Welcome ' . $_SESSION['user_id'] . "</div>\n";
  echo "    <hr>\n";
  echo '    <div class="content_main">' . "\n";

  $sql_query = $pdo->prepare("SELECT * FROM announcements ORDER BY start_date, end_date;");
  $sql_query->execute();
  $result = $sql_query->fetchAll(PDO::FETCH_ASSOC);

  if (!$result) {
    echo '      <div class="message_ok">Please Create Your First Announcement</div>' . "\n";
  }
?>
        <table id="table_logs">
          <thead>
            <tr>
              <th class="th_log_pl">ANNOUNCEMENT</th>
              <th class="th_log_al">START DATE</th>
              <th class="th_log_al">END DATE</th>
              <th class="th_log">OE</th>
              <th class="th_log">UPD</th>
              <th class="th_log_p">DEL</th>
            </tr>
          </thead>
          <tbody>
<?php
  foreach ($result as $announcement) {
    echo "          <tr>\n";
    echo "            <td>\n";
    echo '              <textarea class="textarea_ann" data-id="' . $announcement['id'] . '" rows="1" cols="80" autocorrect="on" maxlength="100">' . $announcement['announcement'] . "</textarea>\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo '              <input type="date" class="start_date" value="' . $announcement['start_date'] . '" min="2026-01-01" max="2040-12-31">' . "\n";
    echo "            </td>\n";
    echo "            <td>\n";
    echo '              <input type="date" class="end_date" value="' . $announcement['end_date'] . '" min="2026-01-01" max="2040-12-31"';

    if ($announcement['end_date'] === null) {
      echo ' disabled';
    }

    echo ">\n";
    echo "            </td>\n";
    echo '            <td class="checkbox_log_ac">' . "\n";
    echo '              <input type="checkbox" class="cbx_no_end" onclick="toggle_no_end(this);"';

    if ($announcement['end_date'] === null) {
      echo ' checked';
    }

    echo '>';
    echo "            </td>\n";
    echo '            <td class="image_ac">' . "\n";
    echo '              <img class="img_update_ann" src="images/save_green.png" onclick="ann_update(this);">' . "\n";
    echo "            </td>\n";
    echo '            <td class="image_ac">' . "\n";
    echo '              <img class="img_delete_ann" src="images/delete_red.png" onclick="ann_delete(this);">' . "\n";
    echo "            </td>\n";
    echo "          </tr>\n";
  }
?>
          <tr>
            <td>
              <textarea class="textarea_ann" data-id="-1" rows="1" cols="80" autocorrect="on" maxlength="100" placeholder="New announcement" oninput="ann_check(this);"></textarea>
            </td>
            <td>
<?php
  echo '              <input type="date" class="start_date" value="' . $date->format('Y-m-d') . '" min="2026-01-01" max="2040-12-31">' . "\n";
?>
            </td>
            <td>
              <input type="date" class="end_date" value="" min="2026-01-01" max="2040-12-31">
            </td>
            <td class="checkbox_log_ac">
              <input type="checkbox" class="cbx_no_end" onclick="toggle_no_end(this);">
            </td>
            <td class="image_ac">
              <img class="img_update_ann" src="images/save_gs.png" onclick="ann_update(this);" disabled>
            </td>
            <td class="image_ac"></td>
          </tr>
        </tbody>
      </table>
    </div>
<?php
  require_once('jars_footer.php');
?>
  </div>
  <script type="text/javascript" src="js/jars_admin.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
