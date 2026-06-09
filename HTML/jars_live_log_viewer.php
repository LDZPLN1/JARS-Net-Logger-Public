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

REVISION 20260609.01

*/

require_once('config.php');

// CHECK IF LIVE LOG IS ACTIVE

if (!isset($_SESSION['live_log_net_id'])) {
  if (isset($_SESSION['live_log_net_name'])) unset($_SESSION['live_log_net_name']);

  header("Location: index.php");
  exit();
}

// CREATE DATABASE CONNECTION

try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, base64_decode(DB_PASSWORD));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $error = 'Error connecting to database';
}

// GET LIST OF ANNOUNCEMENTS FOR BANNER

  $sql_query = $pdo->prepare("SELECT * FROM `announcements` WHERE start_date <= CURRENT_DATE AND (CURRENT_DATE <= end_date OR end_date IS NULL) ORDER BY start_date DESC, end_date;");
  $sql_query->execute();

  $messages = [];

  while ($row = $sql_query->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = $row["announcement"];
  }

  $banners = json_encode($messages);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="language" content="English" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <title><?php echo ORG_NAME; ?> Live Log Viewer</title>
  <link rel='stylesheet' type='text/css' href='style.css'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inclusive+Sans:ital,wght@0,300..700;1,300..700&family=Poetsen+One&display=swap" rel="stylesheet">
</head>

<body>
  <div class="content_page">
<?php
  $date = new DateTimeImmutable();
  $base = basename(__FILE__);
  $en_live_log = true;
  require_once('jars_header.php');

  echo '    <div id="title_text" class="message_green" data-id="' . $_SESSION['live_log_net_id'] . '">' . $_SESSION['live_log_net_name'] . "</div>\n";
?>
    <hr>
    <div class="content_top_banner"><span id="banner_message" class="banner_message"></span></div>
    <hr>
    <div class="content_top_flex">
      <div>
        <span>Net Control:</span><span id="net_control"></span>
      </div>
      <div>
        <span>Check-Ins:</span><span id="check_ins"></span>&nbsp;<span id="check_ins_unique"></span>
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
<?php require_once('jars_footer.php'); ?>
  </div>
  <div id="jars_chat" class="jars_chat"><img src="images/chat_red.png" id="chat_icon" class="chat_icon" onclick="toggle_chat();"></div>
  <div id="chat_container" class="chat_container">
    <div class="chat_header">JARS Chat <span id="chat_count"></span></div>
    <div class="chat_callsign"><span class="pad_right">Callsign/Name:</span><input type="text" id="input_chat_callsign" maxlength="16" value="" onChange="sender_update();"></div>
    <div id="chat_message_list" class="chat_message_list"></div>
    <div class="input_chat_text">
      <input type="text" id="input_chat" placeholder="Type a message...">
      <button class="button_green" onclick="send_message()">Send</button>
    </div>
  </div>
  <script>
    const banners = <?php echo $banners; ?>;
  </script>
  <script type="text/javascript" src="/socket.io/socket.io.js"></script>
  <script type="text/javascript" src="js/jars_live_log_viewer.js"></script>
  <script type="text/javascript" src="js/jars_chat.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
